<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('car_categories')->insert([
            ['name' => 'SUV', 'description' => 'Xe thể thao đa dụng, gầm cao, phù hợp gia đình', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sedan', 'description' => 'Xe 4 cửa sang trọng, lịch lãm', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hatchback', 'description' => 'Xe nhỏ gọn, linh hoạt trong đô thị', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Xe điện', 'description' => 'Xe xanh hiện đại, tiết kiệm nhiên liệu', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Xe sang', 'description' => 'Xe cao cấp, thương hiệu nổi tiếng', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
