<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Thêm cột biển số xe (license_plate) để phục vụ quản lý xe vật lý cụ thể.
     * Cột này nullable để bảo toàn tuyệt đối 100% dữ liệu các xe đã tồn tại.
     */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            if (!Schema::hasColumn('cars', 'license_plate')) {
                $table->string('license_plate', 50)->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            if (!Schema::hasColumn('cars', 'license_plate')) {
                $table->dropColumn('license_plate');
            }
        });
    }
};
