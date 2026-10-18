<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            if (!Schema::hasColumn('cars', 'publication_status')) {
                // draft: Bản nháp, published: Đã đăng công khai, archived: Lưu trữ tạm ngưng
                $table->string('publication_status', 30)->default('published')->after('status')->index();
            }
            if (!Schema::hasColumn('cars', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Bảo toàn trạng thái công khai cho toàn bộ dữ liệu xe cũ hiện có
        if (Schema::hasColumn('cars', 'is_active')) {
            DB::table('cars')->where('is_active', 1)->update(['publication_status' => 'published']);
            DB::table('cars')->where('is_active', 0)->update(['publication_status' => 'archived']);
        } else {
            DB::table('cars')->update(['publication_status' => 'published']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            if (Schema::hasColumn('cars', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('cars', 'publication_status')) {
                $table->dropColumn('publication_status');
            }
        });
    }
};
