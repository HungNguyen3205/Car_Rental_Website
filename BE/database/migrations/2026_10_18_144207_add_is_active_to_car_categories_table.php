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
        Schema::table('car_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('car_categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }
        });

        // Chuẩn hóa an toàn dữ liệu xe cũ: không làm mất dữ liệu, tách rõ thân xe và địa điểm
        if (Schema::hasTable('cars')) {
            // Chuẩn hóa địa điểm về slug chuẩn (sg, hn, dn, nt)
            \DB::table('cars')->whereIn('city', ['Nha Trang', 'Khánh Hòa', 'Khanh Hoa'])->update(['city' => 'nt']);
            \DB::table('cars')->whereIn('city', ['Hồ Chí Minh', 'TP. Hồ Chí Minh', 'TP.HCM', 'TP HCM', 'Saigon', 'Sai Gon'])->update(['city' => 'sg']);
            \DB::table('cars')->whereIn('city', ['Hà Nội', 'Ha Noi', 'Thành phố Hà Nội'])->update(['city' => 'hn']);
            \DB::table('cars')->whereIn('city', ['Đà Nẵng', 'Da Nang', 'Thành phố Đà Nẵng'])->update(['city' => 'dn']);

            // Tách rõ thân xe (body type) và nhiên liệu (fuel type): Electric không phải loại thân xe loại trừ SUV
            \DB::table('cars')->where('type', 'Electric')->where('name', 'like', '%VF8%')->update(['type' => 'SUV']);
            \DB::table('cars')->where('type', 'Luxury')->where('name', 'like', '%Mercedes%')->update(['type' => 'Sedan']);
            \DB::table('cars')->where('type', 'Tự động')->update(['type' => 'SUV', 'transmission' => 'Tự động']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('car_categories', function (Blueprint $table) {
            if (Schema::hasColumn('car_categories', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
