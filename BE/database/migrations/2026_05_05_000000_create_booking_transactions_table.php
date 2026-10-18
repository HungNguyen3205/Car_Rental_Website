<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            
            // Loại giao dịch:
            // - rental_payment    : Thu tiền thuê xe (trả trước hoặc theo đợt)
            // - rental_refund     : Hoàn tiền thuê xe cho khách
            // - deposit_collection: Thu tiền cọc bảo đảm hoàn lại (không phải doanh thu)
            // - deposit_refund    : Hoàn trả tiền cọc cho khách
            // - deposit_deduction : Khấu trừ tiền cọc (bồi thường thiệt hại, phụ phí phát sinh)
            $table->string('type', 40);

            // Số tiền dùng số nguyên VND (integer / unsignedBigInteger), tuyệt đối không dùng float
            $table->unsignedBigInteger('amount');

            // Trạng thái giao dịch: confirmed (xác nhận), reversed (đã đảo/điều chỉnh)
            $table->string('status', 20)->default('confirmed');

            // Phương thức thanh toán: bank_transfer, cash, credit_card, pos, other
            $table->string('method', 50)->default('bank_transfer');

            // Mã tham chiếu: Mã đối soát ngân hàng, số phiếu thu/chi, mã giao dịch
            $table->string('reference_code', 100)->nullable();

            // Thời điểm phát sinh giao dịch thực tế
            $table->dateTime('transaction_time');

            // Quản trị viên/Kế toán ghi nhận giao dịch
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();

            // Ghi chú giao dịch
            $table->text('note')->nullable();

            // Lý do bắt buộc nếu là giao dịch khấu trừ tiền cọc (deposit_deduction)
            $table->text('deduction_reason')->nullable();

            // Khóa chống gửi trùng lặp (Idempotency Key)
            $table->string('idempotency_key', 100)->nullable()->unique();

            $table->timestamps();

            // Đánh index phục vụ truy vấn sổ cái & báo cáo
            $table->index(['booking_id', 'type', 'status']);
            $table->index(['transaction_time']);
            $table->index(['status', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_transactions');
    }
};
