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
        // 1. Chuyên mục bài viết
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        // 2. Bài viết (Posts)
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->string('featured_image', 500)->nullable();
            
            $table->foreignId('category_id')->nullable()->constrained('post_categories')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('admins')->nullOnDelete();
            
            // Trạng thái xuất bản
            $table->string('status', 30)->default('draft')->index(); // draft, pending_review, published, archived
            $table->timestamp('published_at')->nullable()->index();
            
            // Quy trình kiểm duyệt
            $table->foreignId('reviewer_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Quy tắc sửa sau khi xuất bản (bản sửa đổi chờ duyệt, không leak ra công khai)
            $table->json('pending_changes')->nullable();
            $table->boolean('has_pending_revision')->default(false)->index();
            
            // Lượt xem & SEO
            $table->unsignedBigInteger('views_count')->default(0);
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords', 255)->nullable();
            
            // Xóa mềm
            $table->softDeletes();
            $table->timestamps();

            // Chỉ mục tối ưu hóa truy vấn công khai và quản trị
            $table->index(['status', 'published_at', 'deleted_at'], 'posts_public_feed_idx');
            $table->index(['author_id', 'status'], 'posts_author_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('post_categories');
    }
};
