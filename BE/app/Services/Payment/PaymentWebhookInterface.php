<?php

namespace App\Services\Payment;

use Illuminate\Http\Request;

/**
 * Interface mở rộng dành cho Webhook các cổng thanh toán hoặc ngân hàng trong tương lai
 * (Ví dụ: VietQR Pro, Sepay, Casso, VNPay, MoMo...).
 * 
 * Lưu ý: Tuyệt đối không tạo webhook giả lập hoặc tạo thông tin tài khoản giả.
 * Chỉ tích hợp khi có thông tin kết nối và chữ ký số chính thức từ đối tác.
 */
interface PaymentWebhookInterface
{
    /**
     * Xác thực chữ ký số (HMAC/RSA) hoặc Secret Token từ nhà cung cấp Webhook
     */
    public function verifySignature(Request $request): bool;

    /**
     * Phân tích và xử lý payload biến động số dư / thanh toán
     * 
     * @return array [
     *    'status' => bool, 
     *    'booking_id' => int|null, 
     *    'amount' => float|null, 
     *    'transaction_ref' => string|null,
     *    'message' => string
     * ]
     */
    public function parseTransaction(Request $request): array;
}
