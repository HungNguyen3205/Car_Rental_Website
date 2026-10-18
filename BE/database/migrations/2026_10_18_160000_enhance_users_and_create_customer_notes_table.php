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
        // 1. Bổ sung các trường hồ sơ nhu cầu khách hàng vào bảng users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'nhu_cau_thue')) {
                $table->string('nhu_cau_thue')->nullable()->after('dia_chi')
                    ->comment('Nhu cầu thuê: self_drive (tự lái), with_driver (có tài), monthly (theo tháng), wedding (xe cưới/sự kiện), travel (du lịch)');
            }

            if (!Schema::hasColumn('users', 'ngan_sach_du_kien')) {
                $table->decimal('ngan_sach_du_kien', 15, 2)->nullable()->after('nhu_cau_thue')
                    ->comment('Ngân sách dự kiến của khách (VNĐ/ngày hoặc gói thuê)');
            }

            if (!Schema::hasColumn('users', 'car_id')) {
                $table->unsignedBigInteger('car_id')->nullable()->after('ngan_sach_du_kien')
                    ->comment('Xe quan tâm yêu thích hàng đầu');
            }

            if (!Schema::hasColumn('users', 'ghi_chu_so_thich')) {
                $table->text('ghi_chu_so_thich')->nullable()->after('car_id')
                    ->comment('Ghi chú sở thích, thói quen và yêu cầu đặc biệt của khách');
            }

            if (!Schema::hasColumn('users', 'gplx_hang')) {
                $table->string('gplx_hang', 20)->nullable()->after('ghi_chu_so_thich')
                    ->comment('Hạng giấy phép lái xe: B1, B2, C, D, E');
            }
        });

        // 2. Tạo bảng lưu nhật ký chăm sóc khách hàng theo thời gian (Customer Notes)
        if (!Schema::hasTable('customer_notes')) {
            Schema::create('customer_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('admin_id')->nullable()->index();
                $table->string('admin_name', 150);
                $table->text('content');
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_notes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nhu_cau_thue',
                'ngan_sach_du_kien',
                'car_id',
                'ghi_chu_so_thich',
                'gplx_hang',
            ]);
        });
    }
};
