<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('car_id')->constrained('cars')->cascadeOnDelete();
            $table->string('ma_dat_xe')->unique();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->text('ly_do_thue')->nullable();
            $table->decimal('tong_tien', 12, 2);
            $table->tinyInteger('tinh_trang')->default(0); // 0: Chờ xác nhận, 1: Đã xác nhận, 2: Đã hủy
            $table->tinyInteger('is_thanh_toan')->default(0); // 0: Chưa thanh toán, 1: Đã thanh toán
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
