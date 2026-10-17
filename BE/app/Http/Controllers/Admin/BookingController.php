<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CarBookingConflictException;
use App\Exceptions\CarBookingValidationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\AdminCreateBookingRequest;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\ConfirmPaymentRequest;
use App\Http\Requests\Booking\StatusBookingRequest;
use App\Models\Booking;
use App\Models\User;
use App\Services\Booking\BookingService;
use App\Services\Payment\BookingPaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    protected BookingPaymentService $paymentService;
    protected BookingService $bookingService;

    public function __construct(BookingPaymentService $paymentService, BookingService $bookingService)
    {
        $this->paymentService = $paymentService;
        $this->bookingService = $bookingService;
    }

    public function getDataAdmin()
    {
        $data = Booking::join('cars', 'bookings.car_id', '=', 'cars.id')
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->leftJoin('admins as payment_admins', 'bookings.payment_confirmed_by', '=', 'payment_admins.id')
            ->leftJoin('admins as creator_admins', 'bookings.created_by_admin_id', '=', 'creator_admins.id')
            ->select(
                'bookings.*', 
                'cars.name as car_name', 
                'cars.price_per_day as car_price_per_day',
                'users.ho_ten as user_name', 
                'users.email as user_email',
                'users.so_dien_thoai as user_phone',
                'payment_admins.ho_ten as confirmed_by_name',
                'creator_admins.ho_ten as created_by_admin_name'
            )
            ->orderByDesc('bookings.id')
            ->get();

        $data->transform(function ($booking) {
            $booking->receipt_url = $booking->bill_image 
                ? url('/api/bookings/' . $booking->id . '/receipt') 
                : null;
            return $booking;
        });
            
        return response()->json([
            'status' => 1,
            'data'   => $data
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

        return response()->json($result);
    }

    /**
     * Cập nhật trạng thái thuê xe (Duyệt lịch / Hủy lịch)
     * Độc lập hoàn toàn với trạng thái thanh toán và kiểm tra chuyển trạng thái hợp lệ.
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
        if (!$booking || !$admin->can('confirmPayment', $booking)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy đơn đặt xe hoặc bạn không có quyền thao tác!'
            ], 404);
        }

        $validated = $request->validated();
        $updateData = [];

        // Kiểm tra chuyển trạng thái hợp lệ
        if (isset($validated['tinh_trang'])) {
            $currentStatus = (int) $booking->tinh_trang;
            $newStatus = (int) $validated['tinh_trang'];

            // Nếu đơn đã hủy (2), không cho phép kích hoạt ngược lại vì lịch xe đã bị giải phóng
            if ($currentStatus === 2 && $newStatus !== 2) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Không thể kích hoạt lại đơn đặt xe đã hủy vì lịch xe đã được giải phóng. Vui lòng tạo đơn mới!',
                ], 422);
            }

            $updateData['tinh_trang'] = $newStatus;
        }

        // Nếu admin đồng thời gửi yêu cầu thanh toán
        if (isset($validated['is_thanh_toan']) && (int) $validated['is_thanh_toan'] === 1 && (int) $booking->is_thanh_toan === 0) {
            $adminId = $admin ? $admin->id : 1;
            $this->paymentService->confirmManualPayment($booking, $adminId, $validated['payment_note'] ?? null);
        }

        if (!empty($updateData)) {
            $booking->update($updateData);
        }

        return response()->json([
            'status'  => 1,
            'message' => 'Cập nhật trạng thái đơn hàng thành công!',
            'data'    => $booking->fresh()
        ]);
    }

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

        $booking->delete();
        return response()->json([
            'status'  => 1,
            'message' => 'Xóa đơn đặt xe thành công!'
        ]);
    }
}
