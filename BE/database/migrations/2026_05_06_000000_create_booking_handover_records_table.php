<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cập nhật bảng cars với số km hiện tại và loại nhiên liệu/năng lượng
        Schema::table('cars', function (Blueprint $table) {
            $table->unsignedBigInteger('current_km')->default(0)->after('price_per_day');
            $table->string('fuel_type', 32)->default('electric')->after('type'); // electric, gasoline, diesel
        });

        // 2. Tạo bảng booking_handover_records (Biên bản bàn giao & nhận trả xe gắn với đơn)
        Schema::create('booking_handover_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->string('type', 32); // 'delivery' (bàn giao xe), 'return' (nhận trả xe)
            $table->dateTime('handover_time'); // Thời điểm thực tế
            $table->unsignedBigInteger('staff_id')->nullable(); // Nhân viên thực hiện
            
            // Thông số kỹ thuật vận hành
            $table->unsignedBigInteger('current_km'); // Số km đồng hồ táp-lô lúc thực hiện
            $table->string('fuel_type', 32)->default('electric'); // electric, gasoline, diesel
            $table->unsignedTinyInteger('fuel_or_battery_level'); // Mức pin hoặc nhiên liệu (0 - 100%)

            // Hiện trạng và phụ kiện kiểm tra
            $table->text('exterior_status')->nullable(); // Hiện trạng ngoại thất (vết xước, móp méo, đèn...)
            $table->text('interior_status')->nullable(); // Hiện trạng nội thất (vệ sinh, ghế da, mùi...)
            $table->json('checklist_items')->nullable(); // Checklist phụ kiện: sạc, lốp dự phòng, kích, giấy tờ...
            $table->text('notes')->nullable(); // Ghi chú thêm của nhân viên kiểm tra
            $table->json('images')->nullable(); // Danh sách tên file ảnh chứng từ (lưu private)

            // Quy trình điều chỉnh số km nếu km trả < km giao
            $table->boolean('is_odometer_adjusted')->default(false);
            $table->text('odometer_adjustment_reason')->nullable();

            // Cơ chế ghi nhận xác nhận của khách hàng
            $table->boolean('customer_confirmed')->default(false);
            $table->dateTime('customer_confirmed_at')->nullable();
            $table->string('customer_confirmation_method', 64)->nullable(); // account_confirmed, otp_confirmed, direct_verified
            $table->text('customer_notes')->nullable();

            $table->timestamps();

            $table->foreign('staff_id')->references('id')->on('admins')->nullOnDelete();
            $table->unique(['booking_id', 'type'], 'unique_booking_handover_type');
            $table->index(['booking_id', 'type']);
        });

        // 3. Tạo bảng booking_surcharges (Phụ phí phát sinh sau nhận trả xe)
        Schema::create('booking_surcharges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('handover_record_id')->nullable()->constrained('booking_handover_records')->nullOnDelete();
            $table->string('type', 64); // late_return, excess_km, cleaning, fuel_difference, damage, other
            $table->unsignedBigInteger('amount'); // Số tiền VND integer, không dùng float
            $table->text('reason'); // Lý do phụ phí cụ thể
            $table->json('evidence_images')->nullable(); // Ảnh chứng từ đính kèm
            $table->boolean('is_deducted_from_deposit')->default(false);
            $table->foreignId('transaction_id')->nullable()->constrained('booking_transactions')->nullOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('admins')->nullOnDelete();
            $table->index(['booking_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_surcharges');
        Schema::dropIfExists('booking_handover_records');

        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn(['current_km', 'fuel_type']);
        });
    }
};
