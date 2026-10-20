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
        // 1. Bảng lưu trữ đợt import Excel
        Schema::create('post_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('file_name', 255);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('skipped_empty_rows')->default(0);
            $table->string('drive_folder_id', 255)->nullable();
            
            // Trạng thái đợt import: draft (vừa tải lên/đang preview), ready (đã tải ảnh snapshot thành công), scheduled (đã xác nhận lên lịch), completed (tất cả đã chạy xong), partially_failed, cancelled
            $table->string('status', 30)->default('draft')->index();
            $table->string('channel', 30)->default('both'); // website, facebook, both
            $table->foreignId('default_category_id')->nullable()->constrained('post_categories')->nullOnDelete();
            
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('confirmed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('cancelled_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
        });

        // 2. Bảng lưu từng bài viết được lên lịch đăng đa kênh
        Schema::create('post_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->nullable()->constrained('post_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_index')->default(0); // Dòng trong Excel
            
            // Chống import trùng bằng fingerprint định danh nghiệp vụ
            $table->string('fingerprint', 64)->unique();
            
            // Nội dung và tiêu đề đề xuất (không dùng AI)
            $table->longText('content');
            $table->string('title_suggested', 255);
            $table->string('title', 255);
            $table->foreignId('category_id')->nullable()->constrained('post_categories')->nullOnDelete();
            
            // Thông tin ảnh Drive và snapshot cục bộ
            $table->string('drive_image_name', 255);
            $table->string('drive_file_id', 255)->nullable();
            $table->string('drive_file_modified_time', 64)->nullable();
            $table->string('drive_file_checksum', 64)->nullable();
            $table->string('image_snapshot_path', 500)->nullable(); // Snapshot file an toàn lưu trong storage
            $table->string('image_mime', 100)->nullable();
            $table->unsignedBigInteger('image_size')->nullable();
            
            // Kênh xuất bản và ID đích
            $table->string('channel', 30)->default('both')->index(); // website, facebook, both
            $table->string('target_facebook_page_id', 100)->nullable();
            $table->dateTime('scheduled_at')->index(); // Thời điểm đăng chuẩn hóa UTC
            
            // Trạng thái từng kênh độc lập
            // Các giá trị: pending, scheduled, publishing, published, failed, outcome_unknown, cancelled, not_applicable
            $table->string('status_website', 30)->default('not_applicable')->index();
            $table->string('status_facebook', 30)->default('not_applicable')->index();
            
            // Liên kết kết quả đăng thành công
            $table->foreignId('post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->string('facebook_post_id', 150)->nullable();
            $table->string('facebook_post_url', 500)->nullable();
            
            // Nhật ký lỗi chi tiết từng kênh
            $table->text('website_error')->nullable();
            $table->text('facebook_error')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
            
            // Atomic Claim & Lock chống 2 worker đăng trùng
            $table->dateTime('locked_at')->nullable()->index();
            $table->string('locked_by', 100)->nullable();
            
            $table->dateTime('published_at')->nullable();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            
            $table->timestamps();

            // Index tối ưu truy vấn scheduler
            $table->index(['scheduled_at', 'status_website', 'status_facebook'], 'schedules_due_lookup_idx');
            $table->index(['batch_id', 'status_website', 'status_facebook'], 'schedules_batch_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_schedules');
        Schema::dropIfExists('post_import_batches');
    }
};
