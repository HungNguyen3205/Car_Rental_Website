<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Chạy seeder danh mục trước
        $this->call([
            CategorySeeder::class,
            CarSeeder::class,
        ]);

        // Tạo tài khoản admin mặc định
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'ho_ten' => 'Hệ thống Quản trị',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'tinh_trang' => 1
            ]
        );

        // Tạo tài khoản khách mặc định
        User::updateOrCreate(
            ['email' => 'guest@gmail.com'],
            [
                'ho_ten' => 'Khách hàng Demo',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'tinh_trang' => 1
            ]
        );
    }
}
