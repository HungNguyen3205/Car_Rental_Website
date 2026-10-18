<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Car;
use App\Models\CarCategory;
use App\Services\Authorization\PermissionMatrix;
use App\Services\Location\LocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCarCategoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $adminWithPerms;
    protected Admin $adminWithoutPerms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminWithPerms = Admin::create([
            'ho_ten'        => 'Quản Trị Viên Xe',
            'email'         => 'admin_cat@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901111222',
            'role'          => PermissionMatrix::ROLE_OWNER,
            'tinh_trang'    => 1,
        ]);

        $this->adminWithoutPerms = Admin::create([
            'ho_ten'        => 'Kế Toán Viên',
            'email'         => 'accountant_cat@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0903333444',
            'role'          => PermissionMatrix::ROLE_ACCOUNTANT,
            'tinh_trang'    => 1,
        ]);
    }

    public function test_admin_can_list_categories_with_search_filter_and_pagination(): void
    {
        $cat1 = CarCategory::create(['name' => 'Sedan Hạng Sang', 'description' => 'Xe 4 cửa cao cấp', 'is_active' => true]);
        $cat2 = CarCategory::create(['name' => 'SUV Đô Thị', 'description' => 'Gầm cao đa dụng', 'is_active' => true]);
        $cat3 = CarCategory::create(['name' => 'Hatchback Cũ', 'description' => 'Dòng xe ngưng kinh doanh', 'is_active' => false]);

        // Gán 1 xe vào cat1
        Car::create([
            'category_id'        => $cat1->id,
            'name'               => 'BMW Series 5',
            'city'               => 'sg',
            'type'               => 'Sedan',
            'price_per_day'      => 1500000,
            'status'             => 'available',
            'publication_status' => Car::PUB_STATUS_PUBLISHED,
        ]);

        // 1. Lấy danh sách đầy đủ (mặc định)
        $response = $this->actingAs($this->adminWithPerms, 'admin')
            ->getJson('/api/admin/car-categories');

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);

        $this->assertCount(3, $response->json('data'));
        $cat1Data = collect($response->json('data'))->firstWhere('id', $cat1->id);
        $this->assertNotNull($cat1Data);
        $this->assertEquals(1, $cat1Data['cars_count']);

        // 2. Tìm kiếm theo từ khóa
        $searchRes = $this->actingAs($this->adminWithPerms, 'admin')
            ->getJson('/api/admin/car-categories?search=SUV');

        $searchRes->assertStatus(200);
        $this->assertCount(1, $searchRes->json('data'));
        $this->assertEquals('SUV Đô Thị', $searchRes->json('data.0.name'));

        // 3. Lọc theo trạng thái active/inactive
        $inactiveRes = $this->actingAs($this->adminWithPerms, 'admin')
            ->getJson('/api/admin/car-categories?status=inactive');

        $inactiveRes->assertStatus(200);
        $this->assertCount(1, $inactiveRes->json('data'));
        $this->assertEquals('Hatchback Cũ', $inactiveRes->json('data.0.name'));

        // 4. Phân trang
        $pagedRes = $this->actingAs($this->adminWithPerms, 'admin')
            ->getJson('/api/admin/car-categories?paginate=true&per_page=2&page=1');

        $pagedRes->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'stats' => ['total', 'active_count', 'inactive_count', 'total_cars_assigned'],
            ]);

        $this->assertEquals(3, $pagedRes->json('meta.total'));
        $this->assertEquals(2, $pagedRes->json('stats.active_count'));
        $this->assertEquals(1, $pagedRes->json('stats.inactive_count'));
    }

    public function test_admin_can_create_category_with_validation(): void
    {
        $payload = [
            'name'        => 'Xe Thể Thao 2 Cửa',
            'description' => 'Hiệu năng cao, phong cách thể thao',
            'is_active'   => true,
        ];

        $response = $this->actingAs($this->adminWithPerms, 'admin')
            ->postJson('/api/admin/car-categories', $payload);

        $response->assertStatus(201)
            ->assertJson(['status' => 1])
            ->assertJsonPath('data.name', 'Xe Thể Thao 2 Cửa');

        $this->assertDatabaseHas('car_categories', [
            'name'      => 'Xe Thể Thao 2 Cửa',
            'is_active' => true,
        ]);

        // Thử tạo trùng tên -> 422
        $duplicateResponse = $this->actingAs($this->adminWithPerms, 'admin')
            ->postJson('/api/admin/car-categories', $payload);

        $duplicateResponse->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_update_and_toggle_category_status(): void
    {
        $cat = CarCategory::create([
            'name'        => 'Dòng Phổ Thông',
            'description' => 'Mô tả ban đầu',
            'is_active'   => true,
        ]);

        // Cập nhật thông tin
        $updateRes = $this->actingAs($this->adminWithPerms, 'admin')
            ->putJson("/api/admin/car-categories/{$cat->id}", [
                'name'        => 'Dòng Phổ Thông Tiết Kiệm',
                'description' => 'Mô tả cập nhật mới',
                'is_active'   => false,
            ]);

        $updateRes->assertStatus(200)
            ->assertJson(['status' => 1])
            ->assertJsonPath('data.name', 'Dòng Phổ Thông Tiết Kiệm')
            ->assertJsonPath('data.is_active', false);

        // Toggle status qua endpoint status
        $toggleRes = $this->actingAs($this->adminWithPerms, 'admin')
            ->patchJson("/api/admin/car-categories/{$cat->id}/status");

        $toggleRes->assertStatus(200)
            ->assertJson(['status' => 1])
            ->assertJsonPath('data.is_active', true);

        $this->assertTrue($cat->fresh()->is_active);
    }

    public function test_category_in_use_cannot_be_deleted_and_returns_422(): void
    {
        $cat = CarCategory::create(['name' => 'SUV Gia Đình', 'is_active' => true]);

        // Gán xe vào danh mục
        Car::create([
            'category_id'        => $cat->id,
            'name'               => 'VinFast VF9 Plus',
            'city'               => 'sg',
            'type'               => 'SUV',
            'price_per_day'      => 2000000,
            'status'             => 'available',
            'publication_status' => Car::PUB_STATUS_PUBLISHED,
        ]);

        // Thao tác xóa phải bị chặn
        $response = $this->actingAs($this->adminWithPerms, 'admin')
            ->deleteJson("/api/admin/car-categories/{$cat->id}");

        $response->assertStatus(422)
            ->assertJson([
                'status'     => 0,
                'error_code' => 'CATEGORY_IN_USE',
                'cars_count' => 1,
            ]);

        // Bản ghi danh mục vẫn còn trong cơ sở dữ liệu
        $this->assertDatabaseHas('car_categories', ['id' => $cat->id]);
    }

    public function test_category_without_cars_can_be_deleted(): void
    {
        $cat = CarCategory::create(['name' => 'Danh Mục Trống', 'is_active' => true]);

        $response = $this->actingAs($this->adminWithPerms, 'admin')
            ->deleteJson("/api/admin/car-categories/{$cat->id}");

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);

        $this->assertDatabaseMissing('car_categories', ['id' => $cat->id]);
    }

    public function test_client_only_sees_active_categories(): void
    {
        CarCategory::create(['name' => 'Active Category', 'is_active' => true]);
        CarCategory::create(['name' => 'Inactive Category', 'is_active' => false]);

        // Public /api/car-categories
        $response = $this->getJson('/api/car-categories');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Active Category', $data[0]['name']);

        // Client /api/client/categories
        $clientRes = $this->getJson('/api/client/categories');
        $clientRes->assertStatus(200);
        $this->assertCount(1, $clientRes->json('data'));
    }

    public function test_location_service_and_city_filtering_preserves_cars(): void
    {
        $cat = CarCategory::create(['name' => 'Đa dụng', 'is_active' => true]);

        // Tạo xe ở các định dạng thành phố khác nhau
        $carHcm = Car::create([
            'category_id'        => $cat->id,
            'name'               => 'Car HCM',
            'city'               => 'sg',
            'price_per_day'      => 1000000,
            'publication_status' => Car::PUB_STATUS_PUBLISHED,
            'status'             => 'available',
        ]);

        $carHanoi = Car::create([
            'category_id'        => $cat->id,
            'name'               => 'Car Hanoi',
            'city'               => 'hn',
            'price_per_day'      => 1100000,
            'publication_status' => Car::PUB_STATUS_PUBLISHED,
            'status'             => 'available',
        ]);

        $carNt = Car::create([
            'category_id'        => $cat->id,
            'name'               => 'Car Nha Trang',
            'city'               => 'nt',
            'price_per_day'      => 1200000,
            'publication_status' => Car::PUB_STATUS_PUBLISHED,
            'status'             => 'available',
        ]);

        // 1. Lọc qua slug 'sg'
        $resSg = $this->getJson('/api/client/car/data?city=sg');
        $resSg->assertStatus(200);
        $carIdsSg = collect($resSg->json('data'))->pluck('id')->toArray();
        $this->assertContains($carHcm->id, $carIdsSg);
        $this->assertNotContains($carHanoi->id, $carIdsSg);

        // 2. Lọc qua mã code tỉnh '79' (mã TP.HCM từ open-api)
        $res79 = $this->getJson('/api/client/car/data?city=79');
        $res79->assertStatus(200);
        $carIds79 = collect($res79->json('data'))->pluck('id')->toArray();
        $this->assertContains($carHcm->id, $carIds79);

        // 3. Lọc qua tên tiếng Việt 'TP. Hồ Chí Minh'
        $resHcmName = $this->getJson('/api/client/car/data?city=' . urlencode('TP. Hồ Chí Minh'));
        $resHcmName->assertStatus(200);
        $carIdsHcmName = collect($resHcmName->json('data'))->pluck('id')->toArray();
        $this->assertContains($carHcm->id, $carIdsHcmName);

        // 4. Lọc Nha Trang / Khánh Hòa qua mã '56'
        $res56 = $this->getJson('/api/client/car/data?city=56');
        $res56->assertStatus(200);
        $carIds56 = collect($res56->json('data'))->pluck('id')->toArray();
        $this->assertContains($carNt->id, $carIds56);

        // 5. Kiểm tra trong AdminCarController filter
        $adminRes = $this->actingAs($this->adminWithPerms, 'admin')
            ->getJson('/api/admin/cars/data?city=sg');
        $adminRes->assertStatus(200);
        $adminCarIds = collect($adminRes->json('data'))->pluck('id')->toArray();
        $this->assertContains($carHcm->id, $adminCarIds);
        $this->assertNotContains($carHanoi->id, $adminCarIds);
    }

    public function test_body_type_and_fuel_type_separation(): void
    {
        $cat = CarCategory::create(['name' => 'Xe Điện Hiện Đại', 'is_active' => true]);

        // Xe điện VinFast VF8 có body type là SUV và fuel_type là electric
        $car = Car::create([
            'category_id'        => $cat->id,
            'name'               => 'VinFast VF8 Eco',
            'type'               => 'SUV',
            'fuel_type'          => 'electric',
            'price_per_day'      => 1200000,
            'city'               => 'sg',
            'publication_status' => Car::PUB_STATUS_PUBLISHED,
            'status'             => 'available',
        ]);

        // Lọc theo body type 'SUV' vẫn tìm thấy VF8
        $resType = $this->getJson('/api/client/car/data?type=SUV');
        $resType->assertStatus(200);
        $ids = collect($resType->json('data'))->pluck('id')->toArray();
        $this->assertContains($car->id, $ids);

        // Lọc theo fuel_type 'electric' cũng tìm thấy VF8
        $resFuel = $this->getJson('/api/client/car/data?fuel_type=electric');
        $resFuel->assertStatus(200);
        $fuelIds = collect($resFuel->json('data'))->pluck('id')->toArray();
        $this->assertContains($car->id, $fuelIds);

        // Đảm bảo Electric không phải là body type loại trừ SUV
        $this->assertEquals('SUV', $car->type);
        $this->assertEquals('electric', $car->fuel_type);
    }

    public function test_category_permissions(): void
    {
        // Admin không có quyền cars.manage hoặc settings.manage
        $response = $this->actingAs($this->adminWithoutPerms, 'admin')
            ->postJson('/api/admin/car-categories', ['name' => 'Category Hacker']);

        $response->assertStatus(403);
    }
}
