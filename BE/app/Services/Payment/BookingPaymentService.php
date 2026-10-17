<?php

namespace App\Services\Payment;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingPaymentService
{
    /**
     * Xác nhận thanh toán thủ công bởi Admin/Kế toán sau khi đối soát sao kê ngân hàng.
     * 
     * Đảm bảo:
     * 1. An toàn khi gọi nhiều lần (Idempotency): Không ghi nhận doanh thu trùng lặp.
     * 2. Phân tách rõ ràng giữa trạng thái thanh toán (is_thanh_toan) và trạng thái đơn thuê (tinh_trang).
     * 3. Lưu vết người xác nhận (payment_confirmed_by) và thời điểm (payment_confirmed_at).
     * 
     * @param Booking $booking
     * @param int|string $adminId
     * @param string|null $note
     * @return array
     */
    public function confirmManualPayment(Booking $booking, $adminId, ?string $note = null): array
    {
        return DB::transaction(function () use ($booking, $adminId, $note) {
            // Khóa bản ghi để tránh tranh chấp dữ liệu (Race conditions)
            $lockedBooking = Booking::lockForUpdate()->find($booking->id);

            if (!$lockedBooking) {
                return [
                    'status'            => 0,
                    'already_confirmed' => false,
                    'message'           => 'Không tìm thấy đơn đặt xe để xác nhận thanh toán!',
                    'data'              => null,
                ];
            }

            // Kiểm tra trạng thái idempotent: nếu đã thanh toán thì giữ nguyên, không ghi đè vết
            if ($lockedBooking->is_thanh_toan === 1) {
                return [
                    'status'            => 1,
                    'already_confirmed' => true,
                    'message'           => 'Đơn đặt xe #' . $lockedBooking->ma_dat_xe . ' đã được xác nhận thanh toán trước đó.',
                    'data'              => $lockedBooking,
                ];
            }

            // Cập nhật trạng thái thanh toán và lưu vết kiểm toán
            $lockedBooking->is_thanh_toan        = 1;
            $lockedBooking->payment_confirmed_at = Carbon::now();
            $lockedBooking->payment_confirmed_by = $adminId;
            $lockedBooking->payment_note         = $note ?: 'Admin đã đối soát biên lai và xác nhận thanh toán thủ công';
            
            // TUYỆT ĐỐI KHÔNG tự ý thay đổi tinh_trang đơn thuê!
            // tinh_trang được quản lý độc lập bởi quy trình duyệt xe/giao nhận xe.

            $lockedBooking->save();

            return [
                'status'            => 1,
                'already_confirmed' => false,
                'message'           => 'Đã xác nhận thanh toán cho đơn đặt xe #' . $lockedBooking->ma_dat_xe . ' thành công!',
                'data'              => $lockedBooking,
            ];
        });
    }

    /**
     * Lấy trạng thái thanh toán phục vụ hiển thị & kiểm tra (Chỉ đọc)
     * 
     * @param Booking $booking
     * @return array
     */
    public function getPaymentStatusInfo(Booking $booking): array
    {
        if ($booking->is_thanh_toan === 1) {
            return [
                'code'          => 'confirmed',
                'label'         => 'Đã xác nhận thanh toán',
                'is_paid'       => 1,
                'has_receipt'   => (bool) $booking->bill_image,
                'confirmed_at'  => $booking->payment_confirmed_at?->format('d/m/Y H:i'),
                'confirmed_by'  => $booking->payment_confirmed_by,
                'note'          => $booking->payment_note,
                'message'       => 'Đơn hàng đã được đối soát và xác nhận thanh toán thành công.'
            ];
        }

        if (!empty($booking->bill_image)) {
            return [
                'code'          => 'pending_verification',
                'label'         => 'Chờ đối soát biên lai',
                'is_paid'       => 0,
                'has_receipt'   => true,
                'confirmed_at'  => null,
                'confirmed_by'  => null,
                'note'          => null,
                'message'       => 'Biên lai đã được tải lên và đang chờ Admin/Kế toán đối soát sao kê tài khoản.'
            ];
        }

        return [
            'code'          => 'unpaid',
            'label'         => 'Chưa thanh toán',
            'is_paid'       => 0,
            'has_receipt'   => false,
            'confirmed_at'  => null,
            'confirmed_by'  => null,
            'note'          => null,
            'message'       => 'Đơn hàng chưa được thanh toán. Vui lòng chuyển khoản theo mã VietQR.'
        ];
    }
}
