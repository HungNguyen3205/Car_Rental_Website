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
        // 1. Migrate data
        $admins = DB::table('users')->where('role', 'admin')->get();
        foreach ($admins as $admin) {
            DB::table('admins')->insert([
                'ho_ten'         => $admin->ho_ten,
                'email'          => $admin->email,
                'password'       => $admin->password,
                'so_dien_thoai'  => $admin->so_dien_thoai,
                'tinh_trang'     => $admin->tinh_trang,
                'created_at'     => $admin->created_at,
                'updated_at'     => $admin->updated_at,
            ]);
        }

        // 2. Delete admins from users table
        DB::table('users')->where('role', 'admin')->delete();

        // 3. Remove role column from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'customer'])->default('customer');
        });

        // Re-importing data is complex for rollback, usually not needed if we are careful
    }
};
