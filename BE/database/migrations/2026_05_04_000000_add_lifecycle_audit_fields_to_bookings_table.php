<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Lưu vết chuyển trạng thái bàn giao xe
            $table->dateTime('delivered_at')->nullable()->after('created_by_admin_id');
            $table->unsignedBigInteger('delivered_by')->nullable()->after('delivered_at');

            // Lưu vết nhận trả xe
            $table->dateTime('returned_at')->nullable()->after('delivered_by');
            $table->unsignedBigInteger('returned_by')->nullable()->after('returned_at');

            // Lưu vết hoàn tất đơn
            $table->dateTime('completed_at')->nullable()->after('returned_by');
            $table->unsignedBigInteger('completed_by')->nullable()->after('completed_at');

            // Lưu vết hủy đơn & lý do bắt buộc
            $table->dateTime('cancelled_at')->nullable()->after('completed_by');
            $table->string('cancelled_by_type', 32)->nullable()->after('cancelled_at'); // 'admin' hoặc 'user'
            $table->unsignedBigInteger('cancelled_by_id')->nullable()->after('cancelled_by_type');
            $table->text('cancel_reason')->nullable()->after('cancelled_by_id');

            // Ghi chú nghiệp vụ / ngoại lệ trong quá trình bàn giao hoặc kiểm tra xe
            $table->text('status_note')->nullable()->after('cancel_reason');

            // Ràng buộc khóa ngoại
            $table->foreign('delivered_by')->references('id')->on('admins')->nullOnDelete();
            $table->foreign('returned_by')->references('id')->on('admins')->nullOnDelete();
            $table->foreign('completed_by')->references('id')->on('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['delivered_by']);
            $table->dropForeign(['returned_by']);
            $table->dropForeign(['completed_by']);

            $table->dropColumn([
                'delivered_at',
                'delivered_by',
                'returned_at',
                'returned_by',
                'completed_at',
                'completed_by',
                'cancelled_at',
                'cancelled_by_type',
                'cancelled_by_id',
                'cancel_reason',
                'status_note',
            ]);
        });
    }
};
