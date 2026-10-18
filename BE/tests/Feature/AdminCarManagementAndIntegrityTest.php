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
use Tests\TestCase;

class AdminCarManagementAndIntegrityTest extends TestCase
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

        // 1. Tạo các vai trò Admin
        $this->owner = Admin::create([
            'ho_ten'        => 'Trần Chủ Hệ Thống',
            'email'         => 'owner@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901111222',
            'role'          => PermissionMatrix::ROLE_OWNER,
            'tinh_trang'    => 1,
        ]);

        $this->dispatcher = Admin::create([
            'ho_ten'        => 'Lê Điều Phối',
            'email'         => 'dispatcher@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0902222333',
            'role'          => PermissionMatrix::ROLE_DISPATCHER,
            'tinh_trang'    => 1,
        ]);

        $this->accountant = Admin::create([
            'ho_ten'        => 'Phạm Kế Toán',
            'email'         => 'accountant@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0903333444',
            'role'          => PermissionMatrix::ROLE_ACCOUNTANT,
            'tinh_trang'    => 1,
        ]);

        // 2. Tạo Khách hàng
        $this->customer = User::create([
            'ho_ten'        => 'Nguyễn Khách Hàng',
            'email'         => 'customer@gmail.com',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0904444555',
            'tinh_trang'    => 1,
        ]);

        // 3. Tạo Danh mục xe
        $this->category = CarCategory::create([
            'name'        => 'SUV Điện',
            'slug'        => 'suv-dien',
            'description' => 'Xe gầm cao thông minh',
        ]);
    }

    /**
     * Helper tạo xe
     */
    protected function createCar(array $attributes = []): Car
    {
        return Car::create(array_merge([
            'name'                => 'VinFast VF' . rand(5, 9),
            'price_per_day'       => 1200000,
            'type'                => 'SUV',
            'city'                => 'sg',
            'status'              => 'available',
            'publication_status'  => Car::PUB_STATUS_PUBLISHED,
            'image'               => '/assets/images/cars/vf8.png',
            'category_id'         => $this->category->id,
            'fuel_type'           => 'electric',
            'is_active'           => true,
        ], $attributes));
    }

    /**
     * 1. Test Server-side pagination & backward-compatible array data structure
     */
    public function test_server_side_pagination_and_array_compatibility(): void
    {
        // Tạo 15 xe
        for ($i = 1; $i <= 15; $i++) {
            $this->createCar(['name' => "Xe VinFast #{$i}"]);
        }

        // Gọi API phân trang mặc định (per_page = 10)
        $response = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/cars/data?per_page=10&page=1');

        $response->assertStatus(200)
            ->assertJsonPath('status', 1);

        $json = $response->json();

        // Kiểm tra data luôn là mảng đối tượng để bảo toàn caller cũ
        $this->assertIsArray($json['data']);
        $this->assertCount(10, $json['data']);

        // Kiểm tra cấu trúc meta phân trang
        $this->assertArrayHasKey('meta', $json);
        $this->assertEquals(1, $json['meta']['current_page']);
        $this->assertEquals(2, $json['meta']['last_page']);
        $this->assertEquals(10, $json['meta']['per_page']);
        $this->assertEquals(15, $json['meta']['total']);

        // Kiểm tra bộ đếm tabs
        $this->assertArrayHasKey('counts', $json);
        $this->assertEquals(15, $json['counts']['all']);
        $this->assertEquals(15, $json['counts']['published']);
        $this->assertEquals(0, $json['counts']['draft']);
        $this->assertEquals(0, $json['counts']['archived']);
        $this->assertEquals(0, $json['counts']['trash']);

        // Test caller cũ yêu cầu toàn bộ mảng không phân trang (paginate=false hoặc all=1)
        $noPaginateResponse = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/cars/data?paginate=false');

        $noPaginateResponse->assertStatus(200);
        $this->assertIsArray($noPaginateResponse->json('data'));
        $this->assertCount(15, $noPaginateResponse->json('data'));
    }

    /**
     * 2. Test Tìm kiếm, Lọc và Đếm theo tab
     */
    public function test_server_side_search_and_tabs_filtering(): void
    {
        $this->createCar(['name' => 'VinFast VF9 President', 'publication_status' => Car::PUB_STATUS_PUBLISHED, 'status' => 'available']);
        $this->createCar(['name' => 'VinFast VF8 Plus', 'publication_status' => Car::PUB_STATUS_DRAFT, 'status' => 'available']);
        $this->createCar(['name' => 'VinFast VF5 Eco', 'publication_status' => Car::PUB_STATUS_ARCHIVED, 'status' => 'maintenance']);

        // Lọc theo từ khóa tìm kiếm "VF9"
        $searchRes = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/cars/data?search=VF9');

        $searchRes->assertStatus(200);
        $this->assertCount(1, $searchRes->json('data'));
        $this->assertEquals('VinFast VF9 President', $searchRes->json('data.0.name'));

        // Lọc theo tab "draft"
        $draftRes = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/cars/data?tab=draft');

        $draftRes->assertStatus(200);
        $this->assertCount(1, $draftRes->json('data'));
        $this->assertEquals('VinFast VF8 Plus', $draftRes->json('data.0.name'));

        // Lọc theo trạng thái vận hành "maintenance"
        $maintRes = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/cars/data?status=maintenance');

        $maintRes->assertStatus(200);
        $this->assertCount(1, $maintRes->json('data'));
        $this->assertEquals('VinFast VF5 Eco', $maintRes->json('data.0.name'));

        // Kiểm tra bộ đếm counts
        $counts = $searchRes->json('counts');
        $this->assertEquals(3, $counts['all']);
        $this->assertEquals(1, $counts['published']);
        $this->assertEquals(1, $counts['draft']);
        $this->assertEquals(1, $counts['archived']);
        $this->assertEquals(0, $counts['trash']);
    }

    /**
     * 3. Test Phân quyền & Vai trò bảo vệ API quản lý xe
     */
    public function test_role_and_permission_enforcement(): void
    {
        $car = $this->createCar();

        // 3.1. Accountant chỉ có quyền xem đơn (bookings.view), không có cars.manage
        // Được phép xem danh sách xe
        $viewRes = $this->actingAs($this->accountant, 'admin')
            ->getJson('/api/admin/cars/data');
        $viewRes->assertStatus(200);

        // Bị chặn 403 khi cố tạo xe
        $createRes = $this->actingAs($this->accountant, 'admin')
            ->postJson('/api/admin/cars/create', [
                'name'          => 'Xe lậu',
                'price_per_day' => 1000000,
                'category_id'   => $this->category->id,
            ]);
        $createRes->assertStatus(403);

        // Bị chặn 403 khi cố xóa xe
        $deleteRes = $this->actingAs($this->accountant, 'admin')
            ->postJson('/api/admin/cars/delete', ['id' => $car->id]);
        $deleteRes->assertStatus(403);

        // Bị chặn 403 khi cố đổi trạng thái xuất bản
        $pubRes = $this->actingAs($this->accountant, 'admin')
            ->postJson('/api/admin/cars/publication-status', [
                'id'                 => $car->id,
                'publication_status' => 'draft',
            ]);
        $pubRes->assertStatus(403);

        // 3.2. Dispatcher có quyền cars.manage thực hiện thành công
        $toggleRes = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/status', ['id' => $car->id]);
        $toggleRes->assertStatus(200)
            ->assertJsonPath('status', 1);
    }

    /**
     * Test Khách hàng thường bị chặn hoàn toàn khi truy cập API quản lý xe của admin
     */
    public function test_customer_cannot_access_admin_endpoints(): void
    {
        $custRes = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/admin/cars/data');

        $this->assertTrue(in_array($custRes->status(), [401, 403]));
    }

    /**
     * 4. Test Xe nháp, lưu trữ hoặc đã xóa hoàn toàn KHÔNG xuất hiện hoặc đặt được qua API công khai
     */
    public function test_public_invisibility_and_booking_prevention(): void
    {
        $carPub = $this->createCar(['name' => 'Xe Công Khai', 'publication_status' => Car::PUB_STATUS_PUBLISHED, 'is_active' => true]);
        $carDraft = $this->createCar(['name' => 'Xe Bản Nháp', 'publication_status' => Car::PUB_STATUS_DRAFT, 'is_active' => false]);
        $carArchived = $this->createCar(['name' => 'Xe Lưu Trữ', 'publication_status' => Car::PUB_STATUS_ARCHIVED, 'is_active' => false]);
        $carDeleted = $this->createCar(['name' => 'Xe Đã Xóa', 'publication_status' => Car::PUB_STATUS_PUBLISHED, 'is_active' => true]);
        $carDeleted->delete(); // Soft delete

        // 4.1. Catalog công khai (client/car/data) chỉ chứa xe công khai
        $publicListRes = $this->getJson('/api/client/car/data');
        $publicListRes->assertStatus(200);
        $publicCarIds = collect($publicListRes->json('data'))->pluck('id')->all();

        $this->assertContains($carPub->id, $publicCarIds);
        $this->assertNotContains($carDraft->id, $publicCarIds);
        $this->assertNotContains($carArchived->id, $publicCarIds);
        $this->assertNotContains($carDeleted->id, $publicCarIds);

        // 4.2. Chi tiết xe công khai (client/car/detail/{id})
        $this->getJson('/api/client/car/detail/' . $carPub->id)->assertStatus(200);
        $this->getJson('/api/client/car/detail/' . $carDraft->id)->assertStatus(404);
        $this->getJson('/api/client/car/detail/' . $carArchived->id)->assertStatus(404);
        $this->getJson('/api/client/car/detail/' . $carDeleted->id)->assertStatus(404);

        // 4.3. Kiểm tra tính khả dụng & báo giá (client/car/check-availability)
        $quotePayload = [
            'car_id'     => $carDraft->id,
            'start_date' => Carbon::now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_date'   => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
        ];
        $this->postJson('/api/client/car/check-availability', $quotePayload)->assertStatus(404);

        // 4.4. Thử đặt xe bản nháp qua API đặt xe của khách hàng -> Bị chặn 400
        $bookingPayload = [
            'car_id'     => $carDraft->id,
            'start_date' => Carbon::now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_date'   => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'ly_do_thue' => 'Thử nghiệm bảo mật',
        ];

        $bookDraftRes = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/user/bookings/dat-lich', $bookingPayload);

        $bookDraftRes->assertStatus(400)
            ->assertJsonPath('status', 0);
        $this->assertStringContainsString('không ở trạng thái mở cho thuê công khai', $bookDraftRes->json('message'));

        // 4.5. Thử đặt xe lưu trữ qua API khách hàng -> Bị chặn 400
        $bookingPayload['car_id'] = $carArchived->id;
        $bookArchivedRes = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/user/bookings/dat-lich', $bookingPayload);

        $bookArchivedRes->assertStatus(400)
            ->assertJsonPath('status', 0);

        // 4.6. Thử đặt xe đã xóa mềm -> Bị chặn
        $bookingPayload['car_id'] = $carDeleted->id;
        $bookDeletedRes = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/user/bookings/dat-lich', $bookingPayload);

        $bookDeletedRes->assertStatus(404);
    }

    /**
     * 5. Test Chặn thao tác ảnh hưởng xe có booking đang hoạt động
     */
    public function test_actions_blocked_when_car_has_active_bookings(): void
    {
        $car = $this->createCar(['name' => 'Xe Đang Có Lịch Thuê']);

        // Tạo đơn đặt xe đang hoạt động (tinh_trang = 1: Đã duyệt, lịch trong tương lai)
        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $car->id,
            'start_date'    => Carbon::now()->addDays(1),
            'end_date'      => Carbon::now()->addDays(3),
            'tong_tien'     => 2400000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => 1,
            'ma_dat_xe'     => 'BK-ACTIVE-TEST',
        ]);

        $this->assertTrue($car->hasActiveBookings());

        // 5.1. Chặn xóa mềm khi xe đang có đơn hoạt động
        $deleteRes = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/delete', ['id' => $car->id]);

        $deleteRes->assertStatus(422)
            ->assertJsonPath('status', 0);
        $this->assertStringContainsString('đang có đơn đặt xe hoạt động', $deleteRes->json('message'));
        $this->assertNull($car->fresh()->deleted_at);

        // 5.2. Chặn chuyển trạng thái sang draft khi xe đang có đơn hoạt động
        $pubDraftRes = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/publication-status', [
                'id'                 => $car->id,
                'publication_status' => 'draft',
            ]);

        $pubDraftRes->assertStatus(422);
        $this->assertEquals(Car::PUB_STATUS_PUBLISHED, $car->fresh()->publication_status);

        // 5.3. Chặn chuyển sang archived khi đang có đơn hoạt động
        $pubArchRes = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/publication-status', [
                'id'                 => $car->id,
                'publication_status' => 'archived',
            ]);

        $pubArchRes->assertStatus(422);
        $this->assertEquals(Car::PUB_STATUS_PUBLISHED, $car->fresh()->publication_status);
    }

    /**
     * 6. Test Soft delete, Thùng rác và Khôi phục xe
     */
    public function test_soft_delete_trash_list_and_restore(): void
    {
        $car = $this->createCar(['name' => 'Xe Sắp Xóa']);

        // Xóa xe (Soft delete)
        $deleteRes = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/delete', ['id' => $car->id]);

        $deleteRes->assertStatus(200)
            ->assertJsonPath('status', 1);

        $this->assertNotNull($car->fresh()->deleted_at);

        // Danh sách thông thường không còn chứa xe này
        $activeListRes = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/cars/data?tab=all');
        $this->assertNotContains($car->id, collect($activeListRes->json('data'))->pluck('id')->all());

        // Danh sách thùng rác (tab=trash) chứa xe này
        $trashListRes = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/cars/data?tab=trash');
        $trashListRes->assertStatus(200);
        $this->assertContains($car->id, collect($trashListRes->json('data'))->pluck('id')->all());
        $this->assertEquals(1, $trashListRes->json('counts.trash'));

        // Khôi phục xe từ thùng rác
        $restoreRes = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/restore', ['id' => $car->id]);

        $restoreRes->assertStatus(200)
            ->assertJsonPath('status', 1);

        $this->assertNull($car->fresh()->deleted_at);

        // Xe xuất hiện lại trong danh sách hoạt động
        $afterRestoreRes = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/cars/data?tab=all');
        $this->assertContains($car->id, collect($afterRestoreRes->json('data'))->pluck('id')->all());
    }

    /**
     * 7. Test Không xóa dây chuyền đơn/giao dịch & Chặn xóa vĩnh viễn xe có lịch sử
     */
    public function test_historical_preservation_and_force_delete_rules(): void
    {
        $car = $this->createCar(['name' => 'Xe Đã Hoàn Thành Chuyến Đi']);

        // Tạo đơn đặt xe đã hoàn thành trong quá khứ (không còn là active)
        $pastBooking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $car->id,
            'start_date'    => Carbon::now()->subDays(10),
            'end_date'      => Carbon::now()->subDays(7),
            'tong_tien'     => 3600000,
            'tinh_trang'    => Booking::STATUS_COMPLETED,
            'is_thanh_toan' => 1,
            'ma_dat_xe'     => 'BK-PAST-COMPLETED',
        ]);

        $this->assertFalse($car->hasActiveBookings());

        // Xóa xe (Soft delete)
        $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/delete', ['id' => $car->id])
            ->assertStatus(200);

        // Bảo toàn 100% đơn đặt xe và dữ liệu liên quan, không xóa dây chuyền
        $this->assertDatabaseHas('bookings', [
            'id'     => $pastBooking->id,
            'car_id' => $car->id,
        ]);

        // Thử xóa vĩnh viễn (force-delete) xe này -> Bị chặn 422 để bảo toàn sổ sách tài chính
        $forceRes = $this->actingAs($this->owner, 'admin')
            ->postJson('/api/admin/cars/force-delete', ['id' => $car->id]);

        $forceRes->assertStatus(422)
            ->assertJsonPath('status', 0);
        $this->assertStringContainsString('lịch sử đơn đặt xe', $forceRes->json('message'));

        // Xe vẫn còn trong cơ sở dữ liệu (trong thùng rác)
        $this->assertDatabaseHas('cars', ['id' => $car->id]);

        // Xe không có bất kỳ lịch sử đơn nào thì được phép xóa vĩnh viễn
        $cleanCar = $this->createCar(['name' => 'Xe Mới Tạo Chưa Ai Thuê']);
        $cleanCar->delete();

        $cleanForceRes = $this->actingAs($this->owner, 'admin')
            ->postJson('/api/admin/cars/force-delete', ['id' => $cleanCar->id]);

        $cleanForceRes->assertStatus(200)
            ->assertJsonPath('status', 1);

        $this->assertDatabaseMissing('cars', ['id' => $cleanCar->id]);
    }

    /**
     * 8. Test Ghi Audit Log đầy đủ khi thực hiện các thao tác quản lý xe
     */
    public function test_audit_logs_recorded_for_all_car_actions(): void
    {
        // 8.1. Audit log khi tạo xe
        $createRes = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/create', [
                'name'          => 'VinFast VF3 Plus',
                'price_per_day' => 450000,
                'category_id'   => $this->category->id,
            ]);
        $createRes->assertStatus(200);
        $carId = $createRes->json('data.id');

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'car.created',
            'actor_type'  => 'admin',
            'actor_id'    => $this->dispatcher->id,
            'target_type' => 'Car',
            'target_id'   => $carId,
        ]);

        // 8.2. Audit log khi đổi trạng thái vận hành
        $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/status', ['id' => $carId])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'car.status_changed',
            'actor_type'  => 'admin',
            'actor_id'    => $this->dispatcher->id,
            'target_type' => 'Car',
            'target_id'   => $carId,
        ]);

        // 8.3. Audit log khi đổi trạng thái xuất bản
        $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/publication-status', [
                'id'                 => $carId,
                'publication_status' => 'draft',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'car.publication_status_changed',
            'actor_type'  => 'admin',
            'actor_id'    => $this->dispatcher->id,
            'target_type' => 'Car',
            'target_id'   => $carId,
        ]);

        // 8.4. Audit log khi xóa mềm
        $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/delete', ['id' => $carId])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'car.deleted',
            'actor_type'  => 'admin',
            'actor_id'    => $this->dispatcher->id,
            'target_type' => 'Car',
            'target_id'   => $carId,
        ]);

        // 8.5. Audit log khi khôi phục
        $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/restore', ['id' => $carId])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'car.restored',
            'actor_type'  => 'admin',
            'actor_id'    => $this->dispatcher->id,
            'target_type' => 'Car',
            'target_id'   => $carId,
        ]);
    }
}
