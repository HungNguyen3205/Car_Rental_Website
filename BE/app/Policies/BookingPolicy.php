<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Bất kỳ quản trị viên (Admin) nào đang kích hoạt (tinh_trang = 1) đều có toàn quyền quản lý
     */
    public function before($user, string $ability): ?bool
    {
        if ($user instanceof Admin) {
            // Admin có quyền nếu tài khoản không bị khóa (tinh_trang != 0)
            return (int) ($user->tinh_trang ?? 1) !== 0;
        }

        return null;
    }

    /**
     * Xem thông tin đơn đặt xe: Chỉ chủ đơn hoặc Admin
     */
    public function view($user, Booking $booking): bool
    {
        return (int) $booking->user_id === (int) $user->id;
    }

    /**
     * Xem / Tải ảnh biên lai chuyển khoản riêng tư: Chỉ chủ đơn hoặc Admin
     */
    public function viewReceipt($user, Booking $booking): bool
    {
        return (int) $booking->user_id === (int) $user->id;
    }

    /**
     * Tải lên biên lai chuyển khoản: Chỉ chủ đơn khi đơn chưa thanh toán và chưa bị hủy
     */
    public function uploadReceipt($user, Booking $booking): bool
    {
        if ((int) $booking->user_id !== (int) $user->id) {
            return false;
        }

        return (int) $booking->is_thanh_toan === 0 && (int) $booking->tinh_trang !== 2;
    }

    /**
     * Hủy đơn đặt xe: Chỉ chủ đơn khi đơn đang ở trạng thái Chờ xác nhận (tinh_trang = 0)
     */
    public function cancel($user, Booking $booking): bool
    {
        if ((int) $booking->user_id !== (int) $user->id) {
            return false;
        }

        return (int) $booking->tinh_trang === 0;
    }

    /**
     * Xác nhận thanh toán: Tuyệt đối chỉ dành cho Admin, khách hàng không có quyền
     */
    public function confirmPayment($user, Booking $booking): bool
    {
        return ($user instanceof Admin) && (int) ($user->tinh_trang ?? 1) !== 0;
    }

    /**
     * Xóa đơn: Chỉ dành cho Admin
     */
    public function delete($user, Booking $booking): bool
    {
        return ($user instanceof Admin) && (int) ($user->tinh_trang ?? 1) !== 0;
    }

    /**
     * Admin tạo đơn đặt xe hộ cho khách hàng: Chỉ dành cho Admin
     */
    public function createOnBehalf($user): bool
    {
        return ($user instanceof Admin) && (int) ($user->tinh_trang ?? 1) !== 0;
    }
}
