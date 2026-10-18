<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\User;
use App\Services\Authorization\PermissionMatrix;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminCarEditorWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $owner;
    protected Admin $dispatcher;
    protected Admin $accountant;
    protected User $customer;
    protected CarCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = Admin::create([
            'ho_ten'        => 'Trần Chủ Hệ Thống',
            'email'         => 'owner_editor@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901111222',
            'role'          => PermissionMatrix::ROLE_OWNER,
            'tinh_trang'    => 1,
        ]);

        $this->dispatcher = Admin::create([
            'ho_ten'        => 'Lê Điều Phối',
            'email'         => 'dispatcher_editor@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0902222333',
            'role'          => PermissionMatrix::ROLE_DISPATCHER,
            'tinh_trang'    => 1,
        ]);

        $this->accountant = Admin::create([
            'ho_ten'        => 'Phạm Kế Toán',
            'email'         => 'accountant_editor@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0903333444',
            'role'          => PermissionMatrix::ROLE_ACCOUNTANT,
            'tinh_trang'    => 1,
        ]);

        $this->customer = User::create([
            'ho_ten'        => 'Nguyễn Khách Hàng',
            'email'         => 'customer_editor@gmail.com',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0904444555',
            'tinh_trang'    => 1,
        ]);

        $this->category = CarCategory::create([
            'name'        => 'SUV Điện Đẳng Cấp',
            'slug'        => 'suv-dien-dang-cap',
            'description' => 'Xe cao cấp cho gia đình và doanh nghiệp',
        ]);
    }

    /**
     * 1. Test Admin lấy chi tiết xe đầy đủ phục vụ trang Editor
     */
    public function test_admin_can_get_car_detail_for_editor(): void
    {
        $car = Car::create([
            'name'                => 'VinFast VF9 Plus AWD',
            'license_plate'       => '51K-999.88',
            'price_per_day'       => 2200000,
            'current_km'          => 15000,
            'type'                => 'SUV 7 chỗ',
            'city'                => 'sg',
            'status'              => 'available',
            'publication_status'  => Car::PUB_STATUS_PUBLISHED,
            'image'               => '/assets/images/vf9.png',
            'category_id'         => $this->category->id,
            'fuel_type'           => 'electric',
            'seats'               => 7,
            'battery_capacity'    => '123 kWh',
            'range_per_charge'    => '438 km (NEDC)',
            'transmission'        => 'Tự động 1 cấp',
            'year_of_manufacture' => 2024,
            'gallery'             => ['/assets/images/cars/vf9.png', '/assets/images/cars/vf8.png'],
            'rental_conditions'   => [
                'requires_id_card' => true,
                'requires_license' => 'B2',
                'deposit_amount'   => 15000000,
            ],
            'extra_fees_info'     => [
                'daily_km_limit'   => 350,
                'excess_km_rate'   => 3000,
                'late_fee_hourly'  => 100000,
            ],
            'description'         => 'Mẫu xe đầu bảng VinFast.',
            'is_active'           => true,
        ]);

        $response = $this->actingAs($this->dispatcher, 'admin')
            ->getJson("/api/admin/cars/detail/{$car->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 1)
            ->assertJsonPath('data.name', 'VinFast VF9 Plus AWD')
            ->assertJsonPath('data.license_plate', '51K-999.88')
            ->assertJsonPath('data.category_name', 'SUV Điện Đẳng Cấp')
            ->assertJsonPath('data.rental_conditions.deposit_amount', 15000000);

        $this->assertNotNull($response->json('data.updated_at'));
    }

    /**
     * 2. Test Tạo mới xe với đầy đủ 7 nhóm thông tin & biển số vật lý
     */
    public function test_admin_create_car_with_full_sections(): void
    {
        $payload = [
            'name'                => 'VinFast VF7 Plus',
            'license_plate'       => '30H-777.66',
            'price_per_day'       => 1600000,
            'current_km'          => 8500,
            'type'                => 'Crossover Điện',
            'city'                => 'hn',
            'status'              => 'available',
            'publication_status'  => 'published',
            'image'               => '/assets/images/cars/vf7.png',
            'category_id'         => $this->category->id,
            'fuel_type'           => 'electric',
            'seats'               => 5,
            'battery_capacity'    => '75.3 kWh',
            'range_per_charge'    => '431 km',
            'transmission'        => 'Tự động AWD',
            'year_of_manufacture' => 2024,
            'gallery'             => ['/assets/images/cars/vf7.png'],
            'rental_conditions'   => [
                'requires_id_card' => true,
                'requires_license' => 'B2',
                'deposit_amount'   => 10000000,
            ],
            'extra_fees_info'     => [
                'daily_km_limit'   => 350,
                'excess_km_rate'   => 2500,
                'late_fee_hourly'  => 80000,
            ],
            'description'         => 'Thiết kế tương lai vũ trụ.',
            'is_active'           => true,
        ];

        $response = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/create', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('status', 1);

        $this->assertDatabaseHas('cars', [
            'name'          => 'VinFast VF7 Plus',
            'license_plate' => '30H-777.66',
            'current_km'    => 8500,
            'city'          => 'hn',
        ]);

        // Kiểm tra audit log
        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'car.created',
            'actor_type'  => 'admin',
            'actor_id'    => $this->dispatcher->id,
            'target_type' => 'Car',
        ]);
    }

    /**
     * 3. Test Lỗi 422 theo từng trường dữ liệu bắt buộc & dữ liệu âm
     */
    public function test_validation_errors_422(): void
    {
        // 3.1. Thiếu tên xe và giá thuê âm
        $res = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/create', [
                'name'          => '',
                'price_per_day' => -50000,
            ]);

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price_per_day']);

        // 3.2. Cập nhật thiếu ID xe
        $updateRes = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/update', [
                'name'          => 'Xe test',
                'price_per_day' => 1000000,
            ]);

        $updateRes->assertStatus(422)
            ->assertJsonValidationErrors(['id']);
    }

    /**
     * 4. Test Kiểm soát xung đột ghi đè đồng thời (Optimistic Concurrency Control - 409)
     */
    public function test_optimistic_concurrency_control_prevents_silent_overwrite(): void
    {
        $car = Car::create([
            'name'          => 'VinFast VF8 Eco',
            'price_per_day' => 1400000,
            'status'        => 'available',
            'city'          => 'sg',
            'is_active'     => true,
        ]);

        // Phiên A đọc dữ liệu xe lúc T1
        $t1 = $car->fresh()->updated_at->toISOString();

        // Trong lúc đó, Phiên B cập nhật xe ở thời điểm sau đó 5 giây (T2)
        Carbon::setTestNow(Carbon::now()->addSeconds(5));
        $car->update([
            'name'          => 'VinFast VF8 Eco (Cập nhật bởi Phiên B)',
            'price_per_day' => 1450000,
        ]);
        $t2 = $car->fresh()->updated_at->toISOString();

        // Phiên A cố gắng gửi bản lưu với mốc T1 cũ -> Hệ thống chặn lại với mã 409
        $conflictRes = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/update', [
                'id'            => $car->id,
                'name'          => 'VinFast VF8 Eco (Phiên A cố lưu đè)',
                'price_per_day' => 1500000,
                'updated_at'    => $t1, // Mốc cũ hơn DB
            ]);

        $conflictRes->assertStatus(409)
            ->assertJsonPath('status', 0)
            ->assertJsonPath('error_type', 'CONCURRENCY_CONFLICT');

        $this->assertStringContainsString('đã được cập nhật bởi một phiên làm việc khác', $conflictRes->json('message'));

        // Khi Phiên A đồng ý với mốc T2 mới nhất từ máy chủ -> Lưu thành công
        $successRes = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/update', [
                'id'            => $car->id,
                'name'          => 'VinFast VF8 Eco (Phiên A sau khi đồng bộ)',
                'price_per_day' => 1500000,
                'updated_at'    => $t2, // Mốc mới nhất
            ]);

        $successRes->assertStatus(200)
            ->assertJsonPath('status', 1);

        $this->assertEquals('VinFast VF8 Eco (Phiên A sau khi đồng bộ)', $car->fresh()->name);
        Carbon::setTestNow(); // Reset test time
    }

    /**
     * 5. Test Tải ảnh lên máy chủ có validation định dạng và dung lượng thực tế
     */
    public function test_upload_image_validation_and_storage(): void
    {
        // 5.1. File không phải ảnh (text file) -> Bị chặn 422
        $fakeText = UploadedFile::fake()->create('document.txt', 500, 'text/plain');
        $failRes = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/upload-image', ['image' => $fakeText]);
        $failRes->assertStatus(422)
            ->assertJsonValidationErrors(['image']);

        // 5.2. File vượt quá 5MB -> Bị chặn 422
        $hugeImage = UploadedFile::fake()->image('huge_photo.jpg')->size(6000); // 6MB
        $hugeRes = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/upload-image', ['image' => $hugeImage]);
        $hugeRes->assertStatus(422)
            ->assertJsonValidationErrors(['image']);

        // 5.3. File ảnh hợp lệ -> Lưu thành công và trả về URL thật
        $validImage = UploadedFile::fake()->image('vf8_hero.png', 800, 600)->size(1500); // 1.5MB
        $successRes = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/upload-image', ['image' => $validImage]);

        $successRes->assertStatus(200)
            ->assertJsonPath('status', 1);

        $uploadedUrl = $successRes->json('url');
        $this->assertStringStartsWith('/uploads/cars/car_', $uploadedUrl);

        // Dọn dẹp tệp test nếu có
        $cleanPath = public_path(ltrim($uploadedUrl, '/'));
        if (File::exists($cleanPath)) {
            File::delete($cleanPath);
        }
    }

    /**
     * 6. Test Phân quyền truy cập các endpoint của Editor
     */
    public function test_editor_endpoints_permissions(): void
    {
        $car = Car::create([
            'name'          => 'VinFast VF6 Plus',
            'price_per_day' => 900000,
            'is_active'     => true,
        ]);

        // Accountant không có quyền cars.manage -> Bị chặn 403 khi gọi upload ảnh
        $img = UploadedFile::fake()->image('test.png');
        $this->actingAs($this->accountant, 'admin')
            ->postJson('/api/admin/cars/upload-image', ['image' => $img])
            ->assertStatus(403);

        // Accountant được xem chi tiết (có quyền bookings.view)
        $this->actingAs($this->accountant, 'admin')
            ->getJson("/api/admin/cars/detail/{$car->id}")
            ->assertStatus(200);

        // Khách hàng thông thường gọi API editor -> Bị từ chối
        auth('admin')->forgetUser();
        $custRes = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/admin/cars/detail/{$car->id}");
        $this->assertTrue(in_array($custRes->status(), [401, 403]));
    }

    /**
     * 7. Test Bảo toàn dữ liệu xe cũ không có biển số hoặc thông số tùy chọn
     */
    public function test_preserves_legacy_cars_without_license_plate_or_specs(): void
    {
        // Xe cũ được tạo không có biển số, không có gallery, không có phí bổ sung
        $legacyCar = Car::create([
            'name'          => 'Toyota Vios Số Sàn Cũ',
            'price_per_day' => 600000,
            'city'          => 'sg',
            'is_active'     => true,
        ]);

        $this->assertNull($legacyCar->license_plate);
        $this->assertNull($legacyCar->battery_capacity);

        // Lấy chi tiết xe cũ cho trang Editor: Không bị crash, trả về null tự nhiên không bịa đặt
        $detailRes = $this->actingAs($this->dispatcher, 'admin')
            ->getJson("/api/admin/cars/detail/{$legacyCar->id}");

        $detailRes->assertStatus(200);
        $data = $detailRes->json('data');
        $this->assertNull($data['license_plate']);
        $this->assertNull($data['battery_capacity']);
        $this->assertNull($data['seats']);

        // Cập nhật bổ sung biển số cho xe cũ
        $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/update', [
                'id'            => $legacyCar->id,
                'name'          => $legacyCar->name,
                'price_per_day' => $legacyCar->price_per_day,
                'license_plate' => '51G-123.45',
            ])
            ->assertStatus(200);

        $this->assertEquals('51G-123.45', $legacyCar->fresh()->license_plate);
    }
}
