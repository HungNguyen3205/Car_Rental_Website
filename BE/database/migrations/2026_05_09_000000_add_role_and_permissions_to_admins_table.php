<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('role', 30)->default('owner')->after('dia_chi');
            $table->json('permissions')->nullable()->after('role');
        });

        // Bảo toàn quyền cho tất cả tài khoản admin hiện có, tránh tự khóa chủ hệ thống
        DB::table('admins')->update([
            'role' => 'owner',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['role', 'permissions']);
        });
    }
};
