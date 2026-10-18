<?php

namespace App\Services\Payment;

use App\Exceptions\CarBookingValidationException;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\BookingTransaction;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingPaymentService
{
    protected NotificationService $notificationService;

    public function __construct(?NotificationService $notificationService = null)
    {
        $this->notificationService = $notificationService ?? app(NotificationService::class);
    }
    /**
     * Xác nhận thanh toán thủ công bởi Admin/Kế toán sau khi đối soát sao kê ngân hàng.
     * 
     * Đảm bảo:
     * 1. An toàn khi gọi nhiều lần (Idempotency): Không ghi nhận doanh thu trùng lặp.
     * 2. Phân tách rõ ràng giữa trạng thái thanh toán (is_thanh_toan) và trạng thái đơn thuê (tinh_trang).
     * 3. Tự động ghi nhận giao dịch vào sổ cái (booking_transactions) để thống nhất số liệu.
     * 4. Lưu vết người xác nhận (payment_confirmed_by) và thời điểm (payment_confirmed_at).
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

            // Kiểm tra trạng thái idempotent: nếu đã thanh toán đủ thì giữ nguyên, không ghi đè vết
            if ((int) $lockedBooking->is_thanh_toan === Booking::PAYMENT_PAID) {
                return [
                    'status'            => 1,
                    'already_confirmed' => true,
                    'message'           => 'Đơn đặt xe #' . $lockedBooking->ma_dat_xe . ' đã được xác nhận thanh toán trước đó.',
                    'data'              => $lockedBooking,
                ];
            }

            // Tính số tiền thuê còn thiếu để tạo giao dịch thanh toán tương ứng
            $summary = $lockedBooking->getFinancialSummary();
            $remaining = (int) $summary['remaining_rent_amount'];
            $amountToPay = $remaining > 0 ? $remaining : (int) round($lockedBooking->tong_tien);

            $now = Carbon::now();

            // Ghi nhận vào sổ cái giao dịch
            BookingTransaction::create([
                'booking_id'       => $lockedBooking->id,
                'type'             => BookingTransaction::TYPE_RENTAL_PAYMENT,
                'amount'           => $amountToPay,
                'status'           => BookingTransaction::STATUS_CONFIRMED,
                'method'           => 'bank_transfer',
                'reference_code'   => 'XNTT_' . $lockedBooking->ma_dat_xe . '_' . time(),
                'transaction_time' => $now,
                'created_by'       => $adminId,
                'note'             => $note ?: 'Admin đã đối soát biên lai và xác nhận thanh toán thủ công',
            ]);

            // Cập nhật trạng thái thanh toán và lưu vết kiểm toán
            $lockedBooking->is_thanh_toan        = Booking::PAYMENT_PAID;
            $lockedBooking->payment_confirmed_at = $now;
            $lockedBooking->payment_confirmed_by = $adminId;
            $lockedBooking->payment_note         = $note ?: 'Admin đã đối soát biên lai và xác nhận thanh toán thủ công';
            
            // TUYỆT ĐỐI KHÔNG tự ý thay đổi tinh_trang đơn thuê!
            // tinh_trang được quản lý độc lập bởi quy trình duyệt xe/giao nhận xe.

            $lockedBooking->save();

            // Phát thông báo xác nhận thanh toán sau khi commit transaction an toàn
            $this->notificationService->notifyPaymentConfirmed($lockedBooking, $amountToPay);

            return [
                'status'            => 1,
                'already_confirmed' => false,
                'message'           => 'Đã xác nhận thanh toán cho đơn đặt xe #' . $lockedBooking->ma_dat_xe . ' thành công!',
                'data'              => $lockedBooking,
            ];
        });
    }

    /**
     * Ghi nhận giao dịch tài chính (Thu tiền thuê nhiều lần, hoàn tiền thuê, thu cọc, hoàn cọc, khấu trừ cọc).
     * 
     * Tuân thủ các nguyên tắc:
     * - Số tiền là số nguyên VND (integer), không dùng float.
     * - Phân định rõ tiền thuê (rental) và tiền cọc (deposit); không tính cọc là doanh thu.
     * - Khấu trừ cọc bắt buộc kèm lý do rõ ràng và không vượt quá số cọc đang giữ.
     * - Hoàn tiền thuê không vượt quá số tiền thuê thực thu.
     * - Hỗ trợ idempotency_key để chống submit trùng lặp.
     * 
     * @param Booking $booking
     * @param array $payload
     * @param mixed $admin Admin model
     * @return array
     * @throws CarBookingValidationException
     */
    public function recordTransaction(Booking $booking, array $payload, $admin): array
    {
        if (!$admin || !($admin instanceof Admin)) {
            throw new CarBookingValidationException('Chỉ quản trị viên mới có quyền ghi nhận giao dịch tài chính!', 403);
        }

        $type = $payload['type'] ?? null;
        $amount = (int) ($payload['amount'] ?? 0);
        $method = $payload['method'] ?? 'bank_transfer';
        $referenceCode = !empty($payload['reference_code']) ? trim($payload['reference_code']) : null;
        $transactionTime = !empty($payload['transaction_time']) ? Carbon::parse($payload['transaction_time']) : Carbon::now();
        $note = !empty($payload['note']) ? trim($payload['note']) : null;
        $deductionReason = !empty($payload['deduction_reason']) ? trim($payload['deduction_reason']) : null;
        $idempotencyKey = !empty($payload['idempotency_key']) ? trim($payload['idempotency_key']) : null;

        // 1. Kiểm tra loại giao dịch
        $validTypes = [
            BookingTransaction::TYPE_RENTAL_PAYMENT,
            BookingTransaction::TYPE_RENTAL_REFUND,
            BookingTransaction::TYPE_DEPOSIT_COLLECTION,
            BookingTransaction::TYPE_DEPOSIT_REFUND,
            BookingTransaction::TYPE_DEPOSIT_DEDUCTION,
        ];

        if (!in_array($type, $validTypes, true)) {
            throw new CarBookingValidationException('Loại giao dịch không hợp lệ!', 422);
        }

        // 2. Kiểm tra số tiền
        if ($amount <= 0) {
            throw new CarBookingValidationException('Số tiền giao dịch phải là số nguyên VND lớn hơn 0!', 422);
        }

        return DB::transaction(function () use (
            $booking, $admin, $type, $amount, $method, $referenceCode, 
            $transactionTime, $note, $deductionReason, $idempotencyKey
        ) {
            // Khóa bi quan bản ghi đơn đặt xe
            $lockedBooking = Booking::lockForUpdate()->find($booking->id);
            if (!$lockedBooking) {
                throw new CarBookingValidationException('Không tìm thấy đơn đặt xe!', 404);
            }

            // 3. Xử lý Idempotency: Nếu idempotency_key đã tồn tại cho đơn này
            if ($idempotencyKey) {
                $existingTx = BookingTransaction::where('booking_id', $lockedBooking->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existingTx) {
                    return [
                        'status'        => 1,
                        'is_idempotent' => true,
                        'message'       => 'Giao dịch đã được ghi nhận trước đó (Idempotency).',
                        'transaction'   => $existingTx->load('createdByAdmin:id,ho_ten'),
                        'summary'       => $lockedBooking->getFinancialSummary(),
                    ];
                }
            }

            // 4. Lấy số dư hiện hành từ các giao dịch đã xác nhận
            $summary = $lockedBooking->getFinancialSummary();
            $paidRent = (int) $summary['paid_rent_amount'];
            $depositHeld = (int) $summary['deposit_held_amount'];

            // 5. Kiểm tra tính hợp lệ về số dư theo loại giao dịch
            switch ($type) {
                case BookingTransaction::TYPE_RENTAL_PAYMENT:
                    // Thu tiền phí thuê: Cho phép thu nhiều lần (trả trước hoặc trả theo đợt)
                    break;

                case BookingTransaction::TYPE_RENTAL_REFUND:
                    // Hoàn tiền thuê: Không được hoàn vượt quá số tiền thuê khách đã thanh toán
                    if ($amount > $paidRent) {
                        throw new CarBookingValidationException(
                            "Số tiền hoàn ({$amount} VND) không được vượt quá số tiền thuê thực tế đã thu (" . number_format($paidRent) . " VND)!",
                            422
                        );
                    }
                    break;

                case BookingTransaction::TYPE_DEPOSIT_COLLECTION:
                    // Thu tiền cọc bảo đảm hoàn lại (không tính vào doanh thu)
                    break;

                case BookingTransaction::TYPE_DEPOSIT_REFUND:
                    // Hoàn trả tiền cọc: Không được hoàn vượt quá số cọc đang giữ
                    if ($amount > $depositHeld) {
                        throw new CarBookingValidationException(
                            "Số tiền hoàn cọc ({$amount} VND) không được vượt quá số cọc bảo đảm đang giữ (" . number_format($depositHeld) . " VND)!",
                            422
                        );
                    }
                    break;

                case BookingTransaction::TYPE_DEPOSIT_DEDUCTION:
                    // Khấu trừ cọc: Bắt buộc phải có lý do chi tiết (liên kết bồi thường / phụ phí)
                    if (empty($deductionReason)) {
                        throw new CarBookingValidationException('Vui lòng cung cấp lý do khấu trừ tiền cọc (bồi thường hư hỏng, vi phạm hợp đồng hoặc phụ phí)!', 422);
                    }

                    // Khấu trừ không được vượt quá số cọc đang giữ
                    if ($amount > $depositHeld) {
                        throw new CarBookingValidationException(
                            "Số tiền khấu trừ cọc ({$amount} VND) không được vượt quá số cọc bảo đảm đang giữ (" . number_format($depositHeld) . " VND)!",
                            422
                        );
                    }
                    break;
            }

            // 6. Tạo bản ghi giao dịch bất biến (Immutable Ledger Entry)
            $transaction = BookingTransaction::create([
                'booking_id'       => $lockedBooking->id,
                'type'             => $type,
                'amount'           => $amount,
                'status'           => BookingTransaction::STATUS_CONFIRMED,
                'method'           => $method,
                'reference_code'   => $referenceCode,
                'transaction_time' => $transactionTime,
                'created_by'       => $admin->id,
                'note'             => $note,
                'deduction_reason' => $deductionReason,
                'idempotency_key'  => $idempotencyKey,
            ]);

            // 7. Đồng bộ trạng thái thanh toán trên Booking
            $newSummary = $lockedBooking->getFinancialSummary();
            $newNetRentPaid = (int) $newSummary['paid_rent_amount'];
            $newRemaining = (int) $newSummary['remaining_rent_amount'];
            $totalRent = (int) $newSummary['total_rent_fee'];

            if ($newRemaining <= 0 && $newNetRentPaid >= $totalRent && $totalRent > 0) {
                $lockedBooking->is_thanh_toan = Booking::PAYMENT_PAID; // 1: Đã thanh toán đủ
            } elseif ($newNetRentPaid > 0) {
                $lockedBooking->is_thanh_toan = Booking::PAYMENT_PARTIAL; // 2: Đã thanh toán một phần
            } else {
                $lockedBooking->is_thanh_toan = Booking::PAYMENT_UNPAID; // 0: Chưa thanh toán
            }

            if (!$lockedBooking->payment_confirmed_at && $newNetRentPaid > 0) {
                $lockedBooking->payment_confirmed_at = $transactionTime;
                $lockedBooking->payment_confirmed_by = $admin->id;
            }

            $lockedBooking->save();

            return [
                'status'        => 1,
                'is_idempotent' => false,
                'message'       => 'Ghi nhận giao dịch tài chính thành công!',
                'transaction'   => $transaction->load('createdByAdmin:id,ho_ten'),
                'summary'       => $newSummary,
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
        $financialSummary = $booking->getFinancialSummary();

        // 1. Nếu đơn bị hủy và đã thu tiền
        if ((int) $booking->tinh_trang === Booking::STATUS_CANCELLED && (int) $financialSummary['paid_rent_amount'] > 0) {
            return [
                'code'            => 'paid_cancelled_requires_refund',
                'label'           => 'Đã thu tiền (Cần xử lý hoàn tiền)',
                'is_paid'         => 1,
                'requires_refund' => true,
                'has_receipt'     => (bool) $booking->bill_image,
                'confirmed_at'    => $booking->payment_confirmed_at?->format('d/m/Y H:i'),
                'confirmed_by'    => $booking->payment_confirmed_by,
                'note'            => $booking->payment_note,
                'financial'       => $financialSummary,
                'message'         => 'Đơn hàng đã bị hủy nhưng đã phát sinh thu tiền. Kế toán cần đối soát sao kê và xử lý hoàn tiền theo quy định.'
            ];
        }

        // 2. Nếu đã thanh toán đủ 100%
        if ((int) $booking->is_thanh_toan === Booking::PAYMENT_PAID || $financialSummary['payment_status_code'] === 'paid_full') {
            return [
                'code'            => 'confirmed',
                'label'           => 'Đã thanh toán đủ',
                'is_paid'         => 1,
                'requires_refund' => false,
                'has_receipt'     => (bool) $booking->bill_image,
                'confirmed_at'    => $booking->payment_confirmed_at?->format('d/m/Y H:i'),
                'confirmed_by'    => $booking->payment_confirmed_by,
                'note'            => $booking->payment_note,
                'financial'       => $financialSummary,
                'message'         => 'Đơn hàng đã được đối soát và thanh toán đủ 100% phí thuê.'
            ];
        }

        // 3. Nếu đã thanh toán một phần (trả trước / đợt)
        if ((int) $booking->is_thanh_toan === Booking::PAYMENT_PARTIAL || $financialSummary['payment_status_code'] === 'partial') {
            return [
                'code'            => 'partial',
                'label'           => 'Đã thanh toán một phần',
                'is_paid'         => 2,
                'requires_refund' => false,
                'has_receipt'     => (bool) $booking->bill_image,
                'confirmed_at'    => $booking->payment_confirmed_at?->format('d/m/Y H:i'),
                'confirmed_by'    => $booking->payment_confirmed_by,
                'note'            => $booking->payment_note,
                'financial'       => $financialSummary,
                'message'         => 'Khách hàng đã thanh toán một phần tiền thuê. Còn lại: ' . number_format($financialSummary['remaining_rent_amount']) . ' VND.'
            ];
        }

        // 4. Nếu có biên lai chờ đối soát
        if (!empty($booking->bill_image)) {
            return [
                'code'          => 'pending_verification',
                'label'         => 'Chờ đối soát biên lai',
                'is_paid'       => 0,
                'has_receipt'   => true,
                'confirmed_at'  => null,
                'confirmed_by'  => null,
                'note'          => null,
                'financial'     => $financialSummary,
                'message'       => 'Biên lai đã được tải lên và đang chờ Admin/Kế toán đối soát sao kê tài khoản.'
            ];
        }

        // 5. Chưa thanh toán
        return [
            'code'          => 'unpaid',
            'label'         => 'Chưa thanh toán',
            'is_paid'       => 0,
            'has_receipt'   => false,
            'confirmed_at'  => null,
            'confirmed_by'  => null,
            'note'          => null,
            'financial'     => $financialSummary,
            'message'       => 'Đơn hàng chưa được thanh toán. Vui lòng chuyển khoản theo mã VietQR.'
        ];
    }
}
