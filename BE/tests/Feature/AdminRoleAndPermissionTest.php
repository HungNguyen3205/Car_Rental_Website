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

class AdminRoleAndPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $owner;
    protected Admin $dispatcher;
    protected Admin $accountant;
    protected User $customer;
    protected Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Tạo Chủ hệ thống (Owner)
        $this->owner = Admin::create([
            'ho_ten'        => 'Trần Chủ Hệ Thống',
            'email'         => 'owner@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901111222',
            'dia_chi'       => 'Hà Nội',
            'role'          => PermissionMatrix::ROLE_OWNER,
            'tinh_trang'    => 1,
        ]);

        // 2. Tạo Điều phối viên (Dispatcher)
        $this->dispatcher = Admin::create([
            'ho_ten'        => 'Lê Điều Phối',
            'email'         => 'dispatcher@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0902222333',
            'dia_chi'       => 'Đà Nẵng',
            'role'          => PermissionMatrix::ROLE_DISPATCHER,
            'tinh_trang'    => 1,
        ]);

        // 3. Tạo Kế toán (Accountant)
        $this->accountant = Admin::create([
            'ho_ten'        => 'Phạm Kế Toán',
            'email'         => 'accountant@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0903333444',
            'dia_chi'       => 'TP.HCM',
            'role'          => PermissionMatrix::ROLE_ACCOUNTANT,
            'tinh_trang'    => 1,
        ]);

        // 4. Tạo Khách hàng
        $this->customer = User::create([
            'ho_ten'        => 'Nguyễn Khách Hàng',
            'email'         => 'customer@gmail.com',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0904444555',
            'dia_chi'       => 'Cần Thơ',
            'tinh_trang'    => 1,
        ]);

        // 5. Tạo Xe mẫu
        $category = CarCategory::create([
            'name'        => 'SUV Điện',
            'description' => 'Xe gầm cao thông minh',
        ]);

        $this->car = Car::create([
            'name'          => 'VinFast VF8 Plus',
            'price_per_day' => 1500000,
            'type'          => 'SUV',
            'city'          => 'sg',
            'status'        => 'available',
            'image'         => '/cars/vf8.jpg',
            'category_id'   => $category->id,
            'is_active'     => true,
        ]);
    }

    /**
     * Test: Accountant bị chặn khi cố tạo xe mới (thiếu quyền cars.manage)
     */
    public function test_accountant_cannot_create_car(): void
    {
        $response = $this->actingAs($this->accountant, 'admin')
            ->postJson('/api/admin/cars/create', [
                'name'          => 'VF9 President',
                'price_per_day' => 2500000,
                'category_id'   => $this->car->category_id,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test: Dispatcher có quyền tạo xe thành công (có quyền cars.manage)
     */
    public function test_dispatcher_can_create_car(): void
    {
        $response = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/cars/create', [
                'name'          => 'VinFast VF6 Eco',
                'price_per_day' => 800000,
                'category_id'   => $this->car->category_id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 1);

        $this->assertDatabaseHas('cars', [
            'name' => 'VinFast VF6 Eco',
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
     * Test: Dispatcher bị chặn khi cố xác nhận thanh toán (thiếu quyền payments.manage)
     */
    public function test_dispatcher_cannot_confirm_payment(): void
    {
        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'start_date'    => Carbon::now()->addDays(2),
            'end_date'      => Carbon::now()->addDays(4),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => 0,
            'ma_dat_xe'     => 'BK-DISP-PAY',
        ]);

        $response = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/bookings/confirm-payment', [
                'id'           => $booking->id,
                'payment_note' => 'Thu tiền mặt tại văn phòng',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test: Accountant xác nhận thanh toán thành công (có quyền payments.manage)
     */
    public function test_accountant_can_confirm_payment(): void
    {
        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'start_date'    => Carbon::now()->addDays(2),
            'end_date'      => Carbon::now()->addDays(4),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => 0,
            'ma_dat_xe'     => 'BK-ACC-PAY',
        ]);

        $response = $this->actingAs($this->accountant, 'admin')
            ->postJson('/api/admin/bookings/confirm-payment', [
                'id'           => $booking->id,
                'payment_note' => 'Xác nhận chuyển khoản VCB',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 1);

        $this->assertEquals(1, $booking->fresh()->is_thanh_toan);

        // Kiểm tra audit log
        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'payment.confirmed',
            'actor_type'  => 'admin',
            'actor_id'    => $this->accountant->id,
            'target_type' => 'Booking',
        ]);
    }

    /**
     * Test: Nhân viên không có quyền admins.manage bị chặn truy cập danh sách và thao tác admin
     */
    public function test_non_owner_cannot_access_admin_management(): void
    {
        // Dispatcher gọi API quản trị admin -> 403
        $res1 = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/admins/data');
        $res1->assertStatus(403);

        // Accountant gọi API tạo admin -> 403
        $res2 = $this->actingAs($this->accountant, 'admin')
            ->postJson('/api/admin/admins/create', [
                'ho_ten'        => 'Fake Admin',
                'email'         => 'fake@driverent.vn',
                'password'      => 'Password@123',
                'so_dien_thoai' => '0909999888',
                'role'          => 'owner',
            ]);
        $res2->assertStatus(403);
    }

    /**
     * Test: Chống tự nâng quyền (Self-promotion prevention)
     */
    public function test_admin_cannot_change_own_role(): void
    {
        $response = $this->actingAs($this->dispatcher, 'admin')
            ->postJson('/api/admin/admins/update', [
                'id'            => $this->dispatcher->id,
                'ho_ten'        => $this->dispatcher->ho_ten,
                'email'         => $this->dispatcher->email,
                'so_dien_thoai' => $this->dispatcher->so_dien_thoai,
                'role'          => PermissionMatrix::ROLE_OWNER, // Cố tự nâng lên Owner
            ]);

        // Bị chặn vì không có quyền admins.manage (403) hoặc logic tự nâng quyền
        $this->assertTrue(in_array($response->status(), [403, 422], true));
    }

    /**
     * Test: Bảo vệ Chủ hệ thống cuối cùng (Last Owner Protection)
     */
    public function test_last_owner_cannot_be_deleted_demoted_or_deactivated(): void
    {
        // 1. Thử xóa Chủ hệ thống duy nhất
        $resDelete = $this->actingAs($this->owner, 'admin')
            ->postJson('/api/admin/admins/delete', [
                'id' => $this->owner->id,
            ]);
        $resDelete->assertStatus(422)
            ->assertJsonPath('status', 0);

        // 2. Thử khóa Chủ hệ thống duy nhất
        $resLock = $this->actingAs($this->owner, 'admin')
            ->postJson('/api/admin/admins/status', [
                'id' => $this->owner->id,
            ]);
        $resLock->assertStatus(422)
            ->assertJsonPath('status', 0);

        // 3. Thử hạ quyền Chủ hệ thống duy nhất xuống Dispatcher
        $resDemote = $this->actingAs($this->owner, 'admin')
            ->postJson('/api/admin/admins/update', [
                'id'            => $this->owner->id,
                'ho_ten'        => $this->owner->ho_ten,
                'email'         => $this->owner->email,
                'so_dien_thoai' => $this->owner->so_dien_thoai,
                'role'          => PermissionMatrix::ROLE_DISPATCHER,
            ]);
        // Tự đổi role hoặc hạ quyền chủ cuối cùng đều bị từ chối
        $this->assertTrue(in_array($resDemote->status(), [403, 422], true));
        $this->assertEquals(PermissionMatrix::ROLE_OWNER, $this->owner->fresh()->role);
        $this->assertEquals(1, $this->owner->fresh()->tinh_trang);
    }

    /**
     * Test: Nâng quyền an toàn từ Khách hàng lên Admin không làm mất liên kết đơn hàng (Không xóa User)
     */
    public function test_safe_promote_user_preserves_customer_and_bookings(): void
    {
        // Tạo đơn hàng gắn với khách
        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'start_date'    => Carbon::now()->addDays(5),
            'end_date'      => Carbon::now()->addDays(7),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_PENDING,
            'is_thanh_toan' => 0,
            'ma_dat_xe'     => 'BK-PROMOTE-TEST',
        ]);

        $response = $this->actingAs($this->owner, 'admin')
            ->postJson('/api/admin/users/promote', [
                'id'   => $this->customer->id,
                'role' => PermissionMatrix::ROLE_DISPATCHER,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 1);

        // Bản ghi user trong bảng users KHÔNG BỊ XÓA
        $this->assertDatabaseHas('users', [
            'id'    => $this->customer->id,
            'email' => $this->customer->email,
        ]);

        // Đơn hàng vẫn trỏ đúng user_id
        $this->assertEquals($this->customer->id, $booking->fresh()->user_id);

        // Tài khoản Admin mới được tạo với role dispatcher
        $this->assertDatabaseHas('admins', [
            'email' => $this->customer->email,
            'role'  => PermissionMatrix::ROLE_DISPATCHER,
        ]);

        // Kiểm tra audit log
        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'user.promoted',
            'actor_type'  => 'admin',
            'actor_id'    => $this->owner->id,
            'target_type' => 'User',
            'target_id'   => (string) $this->customer->id,
        ]);
    }

    /**
     * Test: Audit log chỉ cho phép người có quyền audit_logs.view truy cập và có phân trang
     */
    public function test_audit_logs_endpoint_rbac_and_pagination(): void
    {
        // 1. Dispatcher truy cập nhật ký kiểm toán -> 403
        $resForbidden = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/audit-logs');
        $resForbidden->assertStatus(403);

        // 2. Owner truy cập nhật ký kiểm toán -> 200 có dữ liệu
        $resOwner = $this->actingAs($this->owner, 'admin')
            ->getJson('/api/admin/audit-logs?per_page=10');

        $resOwner->assertStatus(200)
            ->assertJsonPath('status', 1)
            ->assertJsonStructure([
                'status',
                'data',
                'pagination' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }
}
