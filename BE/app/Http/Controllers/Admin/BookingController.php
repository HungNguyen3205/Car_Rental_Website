<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\ConfirmPaymentRequest;
use App\Http\Requests\Booking\StatusBookingRequest;
use App\Models\Booking;
use App\Services\Payment\BookingPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    protected BookingPaymentService $paymentService;

    public function __construct(BookingPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function getDataAdmin()
    {
        $data = Booking::join('cars', 'bookings.car_id', '=', 'cars.id')
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->leftJoin('admins as payment_admins', 'bookings.payment_confirmed_by', '=', 'payment_admins.id')
            ->select(
                'bookings.*', 
                'cars.name as car_name', 
                'users.ho_ten as user_name', 
                'users.email as user_email',
                'payment_admins.ho_ten as confirmed_by_name'
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
