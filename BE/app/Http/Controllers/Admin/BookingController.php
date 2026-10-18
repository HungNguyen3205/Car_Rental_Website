<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CarBookingConflictException;
use App\Exceptions\CarBookingValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\AdminCreateBookingRequest;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\ConfirmPaymentRequest;
use App\Http\Requests\Booking\CreateHandoverRecordRequest;
use App\Http\Requests\Booking\RecordTransactionRequest;
use App\Http\Requests\Booking\StatusBookingRequest;
use App\Models\Booking;
use App\Models\BookingHandoverRecord;
use App\Models\BookingSurcharge;
use App\Models\User;
use App\Services\Booking\BookingHandoverService;
use App\Services\Booking\BookingLifecycleService;
use App\Services\Booking\BookingService;
use App\Services\Notification\NotificationService;
use App\Services\Payment\BookingPaymentService;
use App\Services\Revenue\RevenueCalculationService;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    protected BookingPaymentService $paymentService;
    protected BookingService $bookingService;
    protected RevenueCalculationService $revenueService;
    protected BookingLifecycleService $lifecycleService;
    protected BookingHandoverService $handoverService;
    protected NotificationService $notificationService;

    public function __construct(
        BookingPaymentService $paymentService, 
        BookingService $bookingService,
        RevenueCalculationService $revenueService,
        BookingLifecycleService $lifecycleService,
        BookingHandoverService $handoverService,
        NotificationService $notificationService
    ) {
        $this->paymentService = $paymentService;
        $this->bookingService = $bookingService;
        $this->revenueService = $revenueService;
        $this->lifecycleService = $lifecycleService;
        $this->handoverService = $handoverService;
        $this->notificationService = $notificationService;
    }

    public function getDataAdmin(Request $request)
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();

        $data = Booking::join('cars', 'bookings.car_id', '=', 'cars.id')
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->leftJoin('admins as payment_admins', 'bookings.payment_confirmed_by', '=', 'payment_admins.id')
            ->leftJoin('admins as creator_admins', 'bookings.created_by_admin_id', '=', 'creator_admins.id')
            ->leftJoin('admins as delivered_admins', 'bookings.delivered_by', '=', 'delivered_admins.id')
            ->leftJoin('admins as returned_admins', 'bookings.returned_by', '=', 'returned_admins.id')
            ->leftJoin('admins as completed_admins', 'bookings.completed_by', '=', 'completed_admins.id')
            ->select(
                'bookings.*', 
                'cars.name as car_name', 
                'cars.price_per_day as car_price_per_day',
                'cars.current_km as car_current_km',
                'cars.fuel_type as car_fuel_type',
                'users.ho_ten as user_name', 
                'users.email as user_email',
                'users.so_dien_thoai as user_phone',
                'payment_admins.ho_ten as confirmed_by_name',
                'creator_admins.ho_ten as created_by_admin_name',
                'delivered_admins.ho_ten as delivered_by_name',
                'returned_admins.ho_ten as returned_by_name',
                'completed_admins.ho_ten as completed_by_name'
            )
            ->orderByDesc('bookings.id')
            ->get();

        $data->transform(function ($booking) use ($admin) {
            $booking->receipt_url = $booking->bill_image 
                ? url('/api/bookings/' . $booking->id . '/receipt') 
                : null;
            $booking->next_actions = $this->lifecycleService->getNextActions($booking, $admin);
            $booking->timeline = $this->lifecycleService->getTimelineEvents($booking);
            $booking->status_meta = $this->lifecycleService->getStatusMeta((int) $booking->tinh_trang);
            $booking->financial_summary = $booking->getFinancialSummary();
            $booking->has_delivery = $booking->deliveryRecord()->exists();
            $booking->has_return = $booking->returnRecord()->exists();
            return $booking;
        });

        // Tính toán số liệu doanh thu từ cùng một service chuẩn hóa dùng chung
        $revenueSummary = $this->revenueService->calculateRevenueSummary();
            
        return response()->json([
            'status'          => 1,
            'data'            => $data,
            'revenue_summary' => $revenueSummary,
        ]);
    }

    /**
     * Tạo đơn đặt xe hộ cho khách hàng cụ thể (dành riêng cho Admin)
     */
    public function createBookingForCustomer(AdminCreateBookingRequest $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin || !$admin->can('createOnBehalf', Booking::class)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền tạo đơn đặt xe hộ cho khách!'
            ], 403);
        }

        $validated = $request->validated();
        $customer = User::find($validated['customer_id']);
        if (!$customer) {
            return response()->json([
                'status'  => 0,
                'message' => 'Khách hàng không tồn tại trong hệ thống!'
            ], 404);
        }

        try {
            $isPaid = isset($validated['is_thanh_toan']) ? (int) $validated['is_thanh_toan'] : 0;
            $status = isset($validated['tinh_trang']) ? (int) $validated['tinh_trang'] : 0;

            $bookingPayload = [
                'ly_do_thue'          => $validated['ly_do_thue'] ?? ('Đơn đặt hộ bởi Quản trị viên: ' . $admin->ho_ten),
                'dia_chi_nhan_xe'     => $validated['dia_chi_nhan_xe'] ?? null,
                'dia_chi_tra_xe'      => $validated['dia_chi_tra_xe'] ?? null,
                'created_by_admin_id' => $admin->id,
                'tinh_trang'          => $status,
                'is_thanh_toan'       => $isPaid,
            ];

            if ($isPaid === 1) {
                $bookingPayload['payment_confirmed_at'] = Carbon::now();
                $bookingPayload['payment_confirmed_by'] = $admin->id;
                $bookingPayload['payment_note'] = $validated['payment_note'] ?? 'Thanh toán trực tiếp khi admin tạo đơn hộ';
            }

            $booking = $this->bookingService->createBooking(
                (int) $customer->id,
                (int) $validated['car_id'],
                $validated['start_date'],
                $validated['end_date'],
                $bookingPayload
            );

            // Gửi thông báo đặt xe hộ cho khách hàng
            $this->notificationService->notifyBookingCreated($booking);

            // Tải quan hệ chi tiết để trả về phản hồi hoàn chỉnh
            $booking->load(['car', 'user', 'createdByAdmin']);

            return response()->json([
                'status'  => 1,
                'message' => 'Tạo đơn đặt xe hộ thành công cho khách hàng ' . $customer->ho_ten . '! Mã đơn: ' . $booking->ma_dat_xe,
                'data'    => $booking
            ]);
        } catch (CarBookingConflictException $e) {
            return response()->json([
                'status'     => 0,
                'message'    => $e->getMessage(),
                'error_type' => 'SCHEDULE_CONFLICT'
            ], 409);
        } catch (CarBookingValidationException $e) {
            return response()->json([
                'status'  => 0,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        } catch (\Exception $e) {
            Log::error('Admin createBookingForCustomer error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi tạo đơn đặt xe hộ. Vui lòng thử lại sau!',
            ], 500);
        }
    }

    /**
     * Xác nhận thanh toán thủ công độc lập sau khi Admin đối soát sao kê ngân hàng
     */
    public function confirmPayment(ConfirmPaymentRequest $request)
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$admin) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền thực hiện thao tác này!'
            ], 401);
        }

        $booking = Booking::find($request->id);
        if (!$booking || !$admin->can('confirmPayment', $booking)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy đơn đặt xe hoặc bạn không có quyền thao tác!'
            ], 404);
        }

        $validated = $request->validated();

        $result = $this->paymentService->confirmManualPayment(
            $booking, 
            $admin->id, 
            $validated['payment_note'] ?? null
        );

        if (($result['status'] ?? 0) === 1) {
            AuditService::log(
                'payment.confirmed',
                'Booking',
                $booking->id,
                "Đơn đặt xe #{$booking->ma_dat_xe}",
                [
                    'is_thanh_toan' => 1,
                    'payment_note'  => $validated['payment_note'] ?? null,
                ]
            );
        }

        return response()->json($result);
    }

    /**
     * Cập nhật trạng thái thuê xe qua BookingLifecycleService tập trung.
     * ĐỘC LẬP HOÀN TOÀN với trạng thái thanh toán và kiểm tra chặt chẽ các bước chuyển trạng thái hợp lệ.
     */
    public function statusBooking(StatusBookingRequest $request)
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$admin) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền thực hiện thao tác này!'
            ], 401);
        }

        $booking = Booking::find($request->id);
        if (!$booking || !$admin->can('updateStatus', $booking)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy đơn đặt xe hoặc bạn không có quyền cập nhật trạng thái đơn thuê!'
            ], 403);
        }

        $validated = $request->validated();
        $newStatus = (int) $validated['tinh_trang'];

        try {
            $updatedBooking = $this->lifecycleService->transition($booking, $newStatus, $admin, $validated);

            AuditService::log(
                'booking.status_updated',
                'Booking',
                $updatedBooking->id,
                "Đơn đặt xe #{$updatedBooking->ma_dat_xe}",
                [
                    'tinh_trang' => $newStatus,
                    'reason'     => $validated['reason'] ?? null,
                ]
            );

            $message = match ($newStatus) {
                Booking::STATUS_CONFIRMED   => 'Đã duyệt đơn thuê xe thành công! (Trạng thái thanh toán: ' . ((int) $updatedBooking->is_thanh_toan === 1 ? 'Đã thanh toán' : 'Chưa thanh toán') . ')',
                Booking::STATUS_IN_PROGRESS => 'Đã bàn giao xe cho khách hàng thành công! Đơn chuyển sang trạng thái đang thuê.',
                Booking::STATUS_RETURNED    => 'Đã ghi nhận nhận trả xe từ khách hàng! Chờ kiểm tra xe và chốt hoàn tất đơn.',
                Booking::STATUS_COMPLETED   => 'Đã kiểm tra xe và hoàn tất đóng đơn thuê xe thành công!',
                Booking::STATUS_CANCELLED   => (int) $updatedBooking->is_thanh_toan === 1 
                    ? 'Đã hủy đơn thuê xe thành công! Lưu ý: Đơn hàng đã thu tiền trước đó, hệ thống không tự động hoàn tiền. Vui lòng chuyển bộ phận kế toán đối soát xử lý tài chính theo quy định.'
                    : 'Đã hủy đơn thuê xe thành công!',
                default => 'Cập nhật trạng thái đơn thuê xe thành công!'
            };

            return response()->json([
                'status'          => 1,
                'message'         => $message,
                'requires_refund' => $newStatus === Booking::STATUS_CANCELLED && (int) $updatedBooking->is_thanh_toan === 1,
                'data'            => $updatedBooking->fresh([
                    'car', 
                    'user', 
                    'confirmedByAdmin', 
                    'createdByAdmin', 
                    'deliveredByAdmin', 
                    'returnedByAdmin', 
                    'completedByAdmin'
                ]),
                'next_actions'    => $this->lifecycleService->getNextActions($updatedBooking, $admin),
                'timeline'        => $this->lifecycleService->getTimelineEvents($updatedBooking),
            ]);
        } catch (CarBookingConflictException $e) {
            return response()->json([
                'status'     => 0,
                'message'    => $e->getMessage(),
                'error_type' => 'SCHEDULE_CONFLICT'
            ], 409);
        } catch (CarBookingValidationException $e) {
            return response()->json([
                'status'  => 0,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        } catch (\Exception $e) {
            Log::error('Admin statusBooking error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái đơn thuê xe. Vui lòng thử lại!',
            ], 500);
        }
    }

    /**
     * Danh sách đơn hàng cần đối chiếu tài chính (Không tự ý sửa dữ liệu bằng suy đoán)
     */
    public function getReconciliationList(Request $request)
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$admin || (int) ($admin->tinh_trang ?? 1) === 0) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền truy cập dữ liệu đối soát tài chính!'
            ], 403);
        }

        // 1. Đơn đã hủy nhưng đã thu tiền (Cần xử lý hoàn tiền / đối soát tài chính)
        $requiresRefund = Booking::with(['user', 'car'])
            ->where('tinh_trang', Booking::STATUS_CANCELLED)
            ->where('is_thanh_toan', Booking::PAYMENT_PAID)
            ->orderByDesc('id')
            ->get();

        // 2. Đơn có biên lai tải lên nhưng chưa được duyệt thanh toán (Chờ đối soát sao kê)
        $pendingReceipt = Booking::with(['user', 'car'])
            ->where('is_thanh_toan', Booking::PAYMENT_UNPAID)
            ->whereNotNull('bill_image')
            ->where('bill_image', '!=', '')
            ->orderByDesc('id')
            ->get();

        // 3. Dữ liệu lịch sử đã thanh toán nhưng thiếu thông tin kiểm toán (Cần rà soát người duyệt / thời điểm)
        $missingAudit = Booking::with(['user', 'car'])
            ->where('is_thanh_toan', Booking::PAYMENT_PAID)
            ->where('tinh_trang', '!=', Booking::STATUS_CANCELLED)
            ->where(function ($q) {
                $q->whereNull('payment_confirmed_by')
                  ->orWhereNull('payment_confirmed_at');
            })
            ->orderByDesc('id')
            ->get();

        // 4. Đơn đã duyệt xe nhưng chưa thanh toán (Cần đôn đốc thanh toán hoặc thu tiền mặt khi nhận xe)
        $approvedUnpaid = Booking::with(['user', 'car'])
            ->where('tinh_trang', Booking::STATUS_CONFIRMED)
            ->where('is_thanh_toan', Booking::PAYMENT_UNPAID)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status'  => 1,
            'summary' => [
                'requires_refund_count' => $requiresRefund->count(),
                'pending_receipt_count' => $pendingReceipt->count(),
                'missing_audit_count'   => $missingAudit->count(),
                'approved_unpaid_count' => $approvedUnpaid->count(),
                'total_discrepancies'   => $requiresRefund->count() + $pendingReceipt->count() + $missingAudit->count(),
            ],
            'data'    => [
                'requires_refund' => $requiresRefund,
                'pending_receipt' => $pendingReceipt,
                'missing_audit'   => $missingAudit,
                'approved_unpaid' => $approvedUnpaid,
            ]
        ]);
    }

    /**
     * Xóa đơn: Nghiêm cấm xóa đơn để thay cho hủy đơn ("Không xóa đơn để thay cho hủy").
     * Đơn đã thu tiền phải lưu trữ vĩnh viễn phục vụ đối soát tài chính.
     */
    public function deleteBooking(CancelBookingRequest $request)
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$admin) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền thực hiện thao tác này!'
            ], 401);
        }

        $booking = Booking::find($request->id);
        if (!$booking || !$admin->can('delete', $booking)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy đơn để xóa!'
            ], 404);
        }

        if ((int) $booking->tinh_trang !== Booking::STATUS_CANCELLED) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không thể xóa đơn đặt xe đang hoạt động để thay thế cho việc hủy đơn! Vui lòng sử dụng tính năng Hủy đơn để bảo đảm lưu vết kiểm toán và lý do theo quy định.'
            ], 422);
        }

        if ((int) $booking->is_thanh_toan === 1) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không thể xóa đơn đặt xe đã có phát sinh thanh toán tài chính! Đơn cần được lưu trữ vĩnh viễn để phục vụ đối soát kế toán.'
            ], 422);
        }

        $bookingCode = $booking->ma_dat_xe;
        $bookingId = $booking->id;
        $booking->delete();

        AuditService::log(
            'booking.deleted',
            'Booking',
            $bookingId,
            "Đơn đặt xe #{$bookingCode}",
            null,
            ['ma_dat_xe' => $bookingCode]
        );

        return response()->json([
            'status'  => 1,
            'message' => 'Xóa đơn đặt xe thành công!'
        ]);
    }

    /**
     * Lấy sổ giao dịch và số dư tài chính của đơn đặt xe
     */
    public function getTransactions($id, Request $request)
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$admin) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền thực hiện thao tác này!'
            ], 401);
        }

        $booking = Booking::with(['user', 'car'])->find($id);
        if (!$booking) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy đơn đặt xe!'
            ], 404);
        }

        return response()->json([
            'status'  => 1,
            'data'    => $booking->getFinancialSummary(),
            'booking' => [
                'id'            => $booking->id,
                'ma_dat_xe'     => $booking->ma_dat_xe,
                'user_name'     => $booking->user?->ho_ten,
                'car_name'      => $booking->car?->name,
                'start_date'    => $booking->start_date,
                'end_date'      => $booking->end_date,
                'tong_tien'     => $booking->tong_tien,
                'tinh_trang'    => $booking->tinh_trang,
                'is_thanh_toan' => $booking->is_thanh_toan,
            ]
        ]);
    }

    /**
     * Ghi nhận giao dịch tài chính cho đơn đặt xe (Thu tiền thuê nhiều lần, hoàn tiền, thu cọc, hoàn cọc, khấu trừ)
     */
    public function recordTransaction(RecordTransactionRequest $request, $id)
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$admin) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền thực hiện thao tác này!'
            ], 401);
        }

        $booking = Booking::find($id);
        if (!$booking || !$admin->can('recordTransaction', $booking)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy đơn đặt xe hoặc bạn không có quyền ghi nhận giao dịch!'
            ], 403);
        }

        try {
            $result = $this->paymentService->recordTransaction($booking, $request->validated(), $admin);

            if (($result['status'] ?? 0) === 1) {
                AuditService::log(
                    'booking.transaction_recorded',
                    'Booking',
                    $booking->id,
                    "Giao dịch đơn #{$booking->ma_dat_xe}",
                    $request->validated()
                );
            }

            return response()->json($result);
        } catch (CarBookingValidationException $e) {
            return response()->json([
                'status'  => 0,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 422);
        } catch (\Exception $e) {
            Log::error('recordTransaction error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi ghi nhận giao dịch tài chính!'
            ], 500);
        }
    }

    /**
     * Lấy biên bản bàn giao, nhận trả và bảng đối chiếu chênh lệch
     */
    public function getHandovers($id)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return response()->json(['status' => 0, 'message' => 'Bạn không có quyền thực hiện thao tác này!'], 401);
        }

        $booking = Booking::with(['user', 'car'])->find($id);
        if (!$booking) {
            return response()->json(['status' => 0, 'message' => 'Không tìm thấy đơn đặt xe!'], 404);
        }

        $data = $this->handoverService->getHandoverComparison($booking);

        return response()->json([
            'status' => 1,
            'data'   => $data,
        ]);
    }

    /**
     * Lập biên bản bàn giao xe (delivery) hoặc nhận trả xe (return)
     */
    public function createHandover($id, CreateHandoverRecordRequest $request)
    {
        $admin = Auth::guard('admin')->user() ?? $request->user();
        if (!$admin) {
            return response()->json(['status' => 0, 'message' => 'Bạn không có quyền thực hiện thao tác này!'], 401);
        }

        $booking = Booking::with('car')->find($id);
        if (!$booking || !$admin->can('createHandover', $booking)) {
            return response()->json(['status' => 0, 'message' => 'Không tìm thấy đơn đặt xe hoặc bạn không có quyền lập biên bản!'], 403);
        }

        $type = $request->input('type');
        $images = $request->file('images', []);
        if (!is_array($images) && $images) {
            $images = [$images];
        }

        try {
            if ($type === BookingHandoverRecord::TYPE_DELIVERY) {
                $record = $this->handoverService->createDeliveryRecord($booking, $admin, $request->validated(), $images);

                AuditService::log(
                    'booking.handover_created',
                    'BookingHandoverRecord',
                    $record->id,
                    "Biên bản bàn giao xe (Đơn #{$booking->ma_dat_xe})",
                    [
                        'type'        => 'delivery',
                        'odometer_km' => $record->odometer_km,
                    ]
                );

                return response()->json([
                    'status'  => 1,
                    'message' => 'Lập biên bản bàn giao xe thành công! Xe đã chuyển sang trạng thái đang thuê.',
                    'data'    => $record,
                ]);
            } else {
                $surcharges = $request->input('surcharges', []);
                $record = $this->handoverService->createReturnRecord($booking, $admin, $request->validated(), $images, $surcharges);

                AuditService::log(
                    'booking.handover_created',
                    'BookingHandoverRecord',
                    $record->id,
                    "Biên bản nhận trả xe (Đơn #{$booking->ma_dat_xe})",
                    [
                        'type'        => 'return',
                        'odometer_km' => $record->odometer_km,
                    ]
                );

                return response()->json([
                    'status'  => 1,
                    'message' => 'Lập biên bản nhận trả xe thành công! Đơn đã chuyển sang trạng thái đã nhận trả xe.',
                    'data'    => $record,
                ]);
            }
        } catch (CarBookingValidationException $e) {
            return response()->json(['status' => 0, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        } catch (\Exception $e) {
            Log::error('createHandover error: ' . $e->getMessage());
            return response()->json(['status' => 0, 'message' => 'Có lỗi xảy ra khi lập biên bản: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Tải/xem ảnh biên bản bàn giao riêng tư có phân quyền bảo mật
     */
    public function getHandoverImage(Request $request, $id, $filename)
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
        if (!$user && $request->has('token')) {
            $tokenStr = $request->query('token');
            $pat = \Laravel\Sanctum\PersonalAccessToken::findToken($tokenStr);
            if ($pat) {
                $user = $pat->tokenable;
            }
        }

        if (!$user) {
            return response()->json(['status' => 0, 'message' => 'Vui lòng đăng nhập để xem ảnh biên bản!'], 401);
        }

        $booking = Booking::find($id);
        if (!$booking || !$user->can('viewHandover', $booking)) {
            return response()->json(['status' => 0, 'message' => 'Không tìm thấy ảnh chứng từ hoặc bạn không có quyền xem!'], 404);
        }

        $path = $this->handoverService->getPrivateImagePath($booking->id, $filename);
        if (!$path || !File::exists($path)) {
            return response()->json(['status' => 0, 'message' => 'Không tìm thấy tệp ảnh!'], 404);
        }

        return response()->file($path, [
            'Content-Type'  => File::mimeType($path),
            'Cache-Control' => 'private, no-transform, no-store',
        ]);
    }
}
