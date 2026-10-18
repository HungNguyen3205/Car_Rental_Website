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
        // 1. Bảng lịch hẹn tư vấn và xem xe (Appointments)
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('contact_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_to')->index();
            $table->unsignedBigInteger('car_id')->nullable()->index();
            
            $table->string('customer_name', 190);
            $table->string('customer_phone', 50);
            $table->string('customer_email', 190)->nullable();
            
            $table->string('meeting_type', 50)->default('viewing'); // viewing (xem xe & lái thử), consultation (tư vấn), online (trực tuyến)
            $table->string('location', 255);
            $table->dateTime('start_time')->index();
            $table->dateTime('end_time')->index();
            $table->string('status', 30)->default('scheduled')->index(); // scheduled, completed, cancelled, no_show
            
            $table->integer('reminder_minutes')->default(60);
            $table->dateTime('reminder_sent_at')->nullable();
            
            $table->text('cancel_reason')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            
            $table->text('result_summary')->nullable(); // Ghi nhận kết quả sau buổi hẹn
            $table->text('notes')->nullable(); // Ghi chú chuẩn bị trước hẹn
            
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('admins')->onDelete('restrict');
            $table->foreign('car_id')->references('id')->on('cars')->onDelete('set null');
            $table->foreign('cancelled_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('admins')->onDelete('set null');

            // Compound index for staff conflict query
            $table->index(['assigned_to', 'status', 'start_time', 'end_time'], 'idx_staff_appointment_conflict');
            $table->index(['start_time', 'end_time'], 'idx_appointment_window');
        });

        // 2. Bảng thông báo nội bộ cho nhân viên quản trị (Admin Notifications)
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->index();
            $table->string('type', 50)->index();
            $table->string('title', 200);
            $table->text('content');
            $table->string('action_url', 255)->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->string('dedup_key', 120)->nullable()->unique();
            $table->timestamps();

            $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            $table->index(['admin_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
        Schema::dropIfExists('appointments');
    }
};
