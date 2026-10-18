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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ho_ten', 100);
            $table->string('email', 150)->index();
            $table->string('so_dien_thoai', 20)->nullable();
            $table->string('tieu_de', 200);
            $table->text('noi_dung');
            $table->tinyInteger('trang_thai')->default(0)->index()->comment('0: Chờ xử lý, 1: Đang xử lý, 2: Đã xử lý');
            $table->text('ghi_chu_noi_bo')->nullable();
            $table->unsignedBigInteger('handled_by')->nullable()->index();
            $table->timestamp('handled_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('handled_by')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
