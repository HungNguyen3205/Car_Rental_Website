<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('ngay_sinh')->nullable()->change();
            $table->string('dia_chi')->nullable()->change();
            $table->integer('gioi_tinh')->nullable()->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('ngay_sinh')->nullable(false)->change();
            $table->string('dia_chi')->nullable(false)->change();
        });
    }
};
