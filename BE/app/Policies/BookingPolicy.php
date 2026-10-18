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
            // Admin bị khóa tài khoản thì bị từ chối mọi quyền
            if ((int) ($user->tinh_trang ?? 1) === 0) {
                return false;
            }

            // Chủ hệ thống (Owner) có toàn quyền
            if ($user->isOwner()) {
                return true;
            }

            // Kiểm tra quyền nghiệp vụ tương ứng cho các vai trò khác
            return match ($ability) {
                'confirmPayment' => $user->hasPermission('payments.manage'),
                'createOnBehalf', 'manageStatus' => $user->hasPermission('bookings.manage'),
                'view', 'viewReceipt' => $user->hasPermission('bookings.view'),
                default => null,
            };
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

    /**
     * Cập nhật trạng thái đơn thuê (Duyệt lịch / Hủy đơn thuê): Chỉ dành cho Admin
     */
    public function updateStatus($user, Booking $booking): bool
    {
        return ($user instanceof Admin) && (int) ($user->tinh_trang ?? 1) !== 0;
    }

    /**
     * Xem sổ giao dịch: Chủ đơn hoặc Admin
     */
    public function viewTransactions($user, Booking $booking): bool
    {
        return (int) $booking->user_id === (int) $user->id;
    }

    /**
     * Ghi nhận giao dịch tài chính: Chỉ Admin
     */
    public function recordTransaction($user, Booking $booking): bool
    {
        return ($user instanceof Admin) && (int) ($user->tinh_trang ?? 1) !== 0;
    }

    /**
     * Xem biên bản bàn giao/nhận trả xe và ảnh chứng từ: Chủ đơn hoặc Admin
     */
    public function viewHandover($user, Booking $booking): bool
    {
        return (int) $booking->user_id === (int) $user->id;
    }

    /**
     * Lập biên bản bàn giao hoặc nhận trả xe: Chỉ Admin
     */
    public function createHandover($user, Booking $booking): bool
    {
        return ($user instanceof Admin) && (int) ($user->tinh_trang ?? 1) !== 0;
    }

    /**
     * Khách hàng xác nhận biên bản của chính mình
     */
    public function confirmHandoverByCustomer($user, Booking $booking): bool
    {
        return (int) $booking->user_id === (int) $user->id;
    }
}
