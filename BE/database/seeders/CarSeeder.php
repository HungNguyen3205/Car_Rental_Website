<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CarSeeder extends Seeder
{
    public function run(): void
    {
        // Tắt kiểm tra khóa ngoại để truncate bảng cars (do đang có booking tham chiếu)
        Schema::disableForeignKeyConstraints();
        DB::table('cars')->truncate();
        Schema::enableForeignKeyConstraints();

        DB::table('cars')->insert([
            [
                'name' => 'VinFast VF8 2024',
                'price_per_day' => 1200000,
                'type' => 'Electric',
                'status' => 'available',
                'image' => 'https://images.unsplash.com/photo-1662010021854-e67c538ea7a9?auto=format&fit=crop&q=80&w=800',
                'category_id' => 4,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'name' => 'Toyota Camry 2.5Q',
                'price_per_day' => 1500000,
                'type' => 'Sedan',
                'status' => 'available',
                'image' => 'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?auto=format&fit=crop&q=80&w=800',
                'category_id' => 2,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'name' => 'Mercedes-Benz C200',
                'price_per_day' => 2500000,
                'type' => 'Luxury',
                'status' => 'available',
                'image' => 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?auto=format&fit=crop&q=80&w=800',
                'category_id' => 5,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'name' => 'Mazda CX-5 Premium',
                'price_per_day' => 1300000,
                'type' => 'SUV',
                'status' => 'available',
                'image' => 'https://images.unsplash.com/photo-1580273916550-e323be2ae537?auto=format&fit=crop&q=80&w=800',
                'category_id' => 1,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'name' => 'Hyundai SantaFe 2024',
                'price_per_day' => 1600000,
                'type' => 'SUV',
                'status' => 'available',
                'image' => 'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&q=80&w=800',
                'category_id' => 1,
                'created_at' => now(), 'updated_at' => now()
            ],
            [
                'name' => 'Audi A6 2024',
                'price_per_day' => 3200000,
                'type' => 'Luxury',
                'status' => 'available',
                'image' => 'https://images.unsplash.com/photo-1606152421802-db97b9c7a11b?auto=format&fit=crop&q=80&w=800',
                'category_id' => 5,
                'created_at' => now(), 'updated_at' => now()
            ],
        ]);
    }
}
