<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('dia_chi_nhan_xe')->nullable()->after('tong_tien');
            $table->string('dia_chi_tra_xe')->nullable()->after('dia_chi_nhan_xe');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['dia_chi_nhan_xe', 'dia_chi_tra_xe']);
        });
    }
};
