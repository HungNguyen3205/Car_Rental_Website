<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarDetailAndSpecificationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'ho_ten'        => 'Quản Trị Viên',
            'email'         => 'admin_test@driverent.vn',
            'so_dien_thoai' => '0901234567',
            'password'      => bcrypt('password123'),
            'id_chuc_vu'    => 1,
            'is_active'     => 1,
        ]);

        $this->category = CarCategory::create([
            'name'        => 'SUV Điện Cao Cấp',
            'description' => 'Dòng SUV điện VinFast',
        ]);
    }

    /**
     * Test 1: Lấy chi tiết xe với đầy đủ thông số thực và bộ ảnh
     */
    public function test_client_can_get_car_detail_with_real_specs_and_gallery()
    {
        $car = Car::create([
            'name'                => 'VinFast VF9 Plus',
            'price_per_day'       => 2200000,
            'type'                => 'SUV 7 chỗ',
            'city'                => 'sg',
            'status'              => 'available',
            'image'               => '/assets/images/vf9.png',
            'category_id'         => $this->category->id,
            'current_km'          => 12500,
            'fuel_type'           => 'electric',
            'seats'               => 7,
            'battery_capacity'    => '123 kWh',
            'range_per_charge'    => '438 km',
            'transmission'        => 'Tự động 1 cấp',
            'year_of_manufacture' => 2024,
            'gallery'             => [
                '/assets/images/vf9.png',
                '/assets/images/cars/vf8.png',
            ],
            'rental_conditions'   => [
                'requires_id_card' => true,
                'requires_license' => 'B2 trở lên',
                'deposit_amount'   => 15000000,
            ],
            'extra_fees_info'     => [
                'excess_km_rate' => 3000,
                'daily_km_limit' => 350,
                'late_fee_hourly'=> 100000,
            ],
            'description'         => 'Mẫu SUV điện cỡ lớn hàng đầu của VinFast.',
            'is_active'           => true,
        ]);

        $response = $this->getJson("/api/client/car/detail/{$car->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'data'   => [
                    'id'                  => $car->id,
                    'name'                => 'VinFast VF9 Plus',
                    'category_name'       => 'SUV Điện Cao Cấp',
                    'seats'               => 7,
                    'battery_capacity'    => '123 kWh',
                    'range_per_charge'    => '438 km',
                    'transmission'        => 'Tự động 1 cấp',
                    'year_of_manufacture' => 2024,
                    'fuel_type'           => 'electric',
                    'is_active'           => true,
                    'description'         => 'Mẫu SUV điện cỡ lớn hàng đầu của VinFast.',
                ]
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data['gallery']);
        $this->assertEquals(2200000, $data['price_per_day']);
    }

    /**
     * Test 2: Xe không tồn tại trả về lỗi 404
     */
    public function test_get_detail_returns_404_when_car_not_found()
    {
        $response = $this->getJson('/api/client/car/detail/999999');

        $response->assertStatus(404)
            ->assertJson([
                'status'  => 0,
                'message' => 'Không tìm thấy xe phù hợp',
            ]);
    }

    /**
     * Test 3: Xe thiếu thông số tùy chọn thì trả về null/empty, không tự gán bừa giá trị mặc định
     */
    public function test_car_with_sparse_data_does_not_fabricate_specs()
    {
        $car = Car::create([
            'name'          => 'Toyota Camry 2.5Q',
            'price_per_day' => 1200000,
            'type'          => 'Sedan',
            'city'          => 'hn',
            'status'        => 'available',
            'image'         => '/assets/images/cars/camry.png',
            'fuel_type'     => 'gasoline',
            'seats'         => null, // Chưa có dữ liệu
            'battery_capacity' => null, // Xe xăng không có pin EV
            'range_per_charge' => null,
            'is_active'     => true,
        ]);

        $response = $this->getJson("/api/client/car/detail/{$car->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNull($data['seats']);
        $this->assertNull($data['battery_capacity']);
        $this->assertNull($data['range_per_charge']);
        $this->assertEquals('gasoline', $data['fuel_type']);
    }

    /**
     * Test 4: Xe ngừng phục vụ hoặc bảo dưỡng được hiển thị trạng thái chính xác
     */
    public function test_inactive_or_maintenance_car_shows_proper_state()
    {
        $stoppedCar = Car::create([
            'name'          => 'VinFast VF5 Plus',
            'price_per_day' => 600000,
            'city'          => 'sg',
            'status'        => 'available',
            'is_active'     => false, // Ngừng phục vụ
        ]);

        $response = $this->getJson("/api/client/car/detail/{$stoppedCar->id}");
        $response->assertStatus(200);
        $this->assertFalse($response->json('data.is_active'));
    }

    /**
     * Test 5: Endpoint kiểm tra khả dụng & tính giá quote theo quy tắc server
     */
    public function test_server_quote_and_availability_check()
    {
        $car = Car::create([
            'name'          => 'VinFast VF8 Eco',
            'price_per_day' => 1500000,
            'city'          => 'sg',
            'status'        => 'available',
            'is_active'     => true,
        ]);

        $user = User::create([
            'ho_ten'        => 'Nguyễn Văn A',
            'email'         => 'khachhang_a@gmail.com',
            'password'      => bcrypt('password123'),
            'so_dien_thoai' => '0911222333',
        ]);

        // Tạo 1 booking đã giữ chỗ từ ngày 20 đến ngày 22
        Booking::create([
            'user_id'         => $user->id,
            'car_id'          => $car->id,
            'ma_dat_xe'       => 'HDTX100001',
            'start_date'      => '2026-06-20 08:00:00',
            'end_date'        => '2026-06-22 18:00:00',
            'tong_tien'       => 4500000,
            'tinh_trang'      => 1, // Đã duyệt
            'is_thanh_toan'   => 0,
        ]);

        // 5a. Kiểm tra khung giờ trùng lặp -> Báo không khả dụng
        $resConflict = $this->postJson('/api/client/car/check-availability', [
            'car_id'     => $car->id,
            'start_date' => '2026-06-21 09:00:00',
            'end_date'   => '2026-06-23 09:00:00',
        ]);

        $resConflict->assertStatus(200)
            ->assertJson([
                'status'       => 1,
                'is_available' => false,
            ]);

        // 5b. Kiểm tra khung giờ trống -> Báo khả dụng và tính tiền chính xác (3 ngày: 25 đến 28)
        $resAvailable = $this->postJson('/api/client/car/check-availability', [
            'car_id'     => $car->id,
            'start_date' => '2026-06-25 08:00:00',
            'end_date'   => '2026-06-28 08:00:00',
        ]);

        $resAvailable->assertStatus(200)
            ->assertJson([
                'status'        => 1,
                'is_available'  => true,
                'days'          => 3,
                'price_per_day' => 1500000,
                'total_price'   => 4500000,
            ]);

        // 5c. Ngày kết thúc <= ngày bắt đầu -> Bị validation 422
        $resInvalidDate = $this->postJson('/api/client/car/check-availability', [
            'car_id'     => $car->id,
            'start_date' => '2026-06-25 08:00:00',
            'end_date'   => '2026-06-24 08:00:00',
        ]);
        $resInvalidDate->assertStatus(422);
    }

    /**
     * Test 6: Admin có thể cập nhật thông số xe và bộ ảnh
     */
    public function test_admin_can_update_car_specs_and_gallery()
    {
        $car = Car::create([
            'name'          => 'VinFast VF7 Plus',
            'price_per_day' => 1600000,
            'city'          => 'sg',
            'status'        => 'available',
            'category_id'   => $this->category->id,
        ]);

        $response = $this->actingAs($this->admin, 'admin')->postJson('/api/admin/cars/update', [
            'id'                  => $car->id,
            'name'                => 'VinFast VF7 Plus AWD',
            'price_per_day'       => 1700000,
            'seats'               => 5,
            'battery_capacity'    => '75.3 kWh',
            'range_per_charge'    => '431 km',
            'transmission'        => 'Tự động AWD',
            'year_of_manufacture' => 2024,
            'fuel_type'           => 'electric',
            'gallery'             => [
                '/assets/images/cars/vf7.png',
                '/assets/images/vf7.png',
            ],
            'description'         => 'VinFast VF7 thiết kế tương lai.',
            'is_active'           => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 1,
                'message' => 'Cập nhật xe thành công!',
            ]);

        $car->refresh();
        $this->assertEquals('VinFast VF7 Plus AWD', $car->name);
        $this->assertEquals(1700000, $car->price_per_day);
        $this->assertEquals(5, $car->seats);
        $this->assertEquals('75.3 kWh', $car->battery_capacity);
        $this->assertEquals(2, count($car->gallery));
    }
}
