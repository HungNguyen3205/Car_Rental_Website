<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\Contact;
use App\Models\CustomerNote;
use App\Models\User;
use App\Services\Authorization\PermissionMatrix;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $ownerAdmin;
    protected Admin $staffAdmin;
    protected Admin $financeAdmin;
    protected CarCategory $category;
    protected Car $car1;
    protected Car $car2;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Owner Admin (Toàn quyền quản trị)
        $this->ownerAdmin = Admin::create([
            'ho_ten'        => 'Chủ Hệ Thống',
            'email'         => 'owner@vinfast-rentals.vn',
            'password'      => Hash::make('OwnerSecure123!'),
            'so_dien_thoai' => '0901112233',
            'role'          => PermissionMatrix::ROLE_OWNER,
            'permissions'   => ['*'],
            'tinh_trang'    => 1,
        ]);

        // 2. Chuyên viên vận hành (chỉ có bookings.view, không có quyền tài chính)
        $this->staffAdmin = Admin::create([
            'ho_ten'        => 'Chuyên viên Điều Phối',
            'email'         => 'staff@vinfast-rentals.vn',
            'password'      => Hash::make('StaffSecure123!'),
            'so_dien_thoai' => '0902223344',
            'role'          => PermissionMatrix::ROLE_DISPATCHER,
            'permissions'   => [PermissionMatrix::PERM_BOOKINGS_VIEW],
            'tinh_trang'    => 1,
        ]);

        // 3. Kế toán viên (có quyền xem tài chính reports.view)
        $this->financeAdmin = Admin::create([
            'ho_ten'        => 'Kế toán Trưởng',
            'email'         => 'finance@vinfast-rentals.vn',
            'password'      => Hash::make('FinanceSecure123!'),
            'so_dien_thoai' => '0903334455',
            'role'          => PermissionMatrix::ROLE_ACCOUNTANT,
            'permissions'   => [
                PermissionMatrix::PERM_BOOKINGS_VIEW,
                PermissionMatrix::PERM_REPORTS_VIEW,
                PermissionMatrix::PERM_PAYMENTS_MANAGE,
            ],
            'tinh_trang'    => 1,
        ]);

        // Tạo danh mục và xe mẫu
        $this->category = CarCategory::create([
            'name'      => 'SUV Điện',
            'slug'      => 'suv-dien',
            'is_active' => true,
        ]);

        $this->car1 = Car::create([
            'name'               => 'VinFast VF9 Plus',
            'category_id'        => $this->category->id,
            'license_plate'      => '51K-888.99',
            'price_per_day'      => 2500000,
            'status'             => 'active',
            'publication_status' => 'published',
            'type'               => 'SUV',
            'seats'              => 7,
            'transmission'       => 'Tự động',
            'fuel'               => 'Điện',
            'location'           => 'Hồ Chí Minh',
        ]);

        $this->car2 = Car::create([
            'name'               => 'VinFast VF8 Eco',
            'category_id'        => $this->category->id,
            'license_plate'      => '51H-777.66',
            'price_per_day'      => 1800000,
            'status'             => 'active',
            'publication_status' => 'published',
            'type'               => 'SUV',
            'seats'              => 5,
            'transmission'       => 'Tự động',
            'fuel'               => 'Điện',
            'location'           => 'Hà Nội',
        ]);
    }

    /**
     * Test 1: Quyền truy cập - Khách chưa đăng nhập bị chặn 401, chuyên viên có quyền xem được danh sách
     */
    public function test_customer_list_authorization_and_server_pagination(): void
    {
        // Tạo 3 khách hàng
        for ($i = 1; $i <= 3; $i++) {
            User::create([
                'ho_ten'        => "Khách hàng {$i}",
                'email'         => "khach{$i}@test.com",
                'password'      => Hash::make('password123'),
                'so_dien_thoai' => "091122334{$i}",
                'tinh_trang'    => 1,
            ]);
        }

        // Chưa đăng nhập -> 401
        $guestRes = $this->getJson('/api/admin/customers');
        $guestRes->assertStatus(401);

        // Đăng nhập là Admin -> 200, phân trang máy chủ, có thống kê KPI
        $adminRes = $this->actingAs($this->staffAdmin, 'admin')
            ->getJson('/api/admin/customers?per_page=2');

        $adminRes->assertStatus(200)
            ->assertJsonPath('status', 1)
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.per_page', 2)
            ->assertJsonPath('counts.total', 3)
            ->assertJsonPath('counts.active', 3)
            ->assertJsonPath('data.data.0.entity_type', 'user');
    }

    /**
     * Test 2: Trùng ID giữa hai bảng (User ID=1 và Admin ID=1 cùng tồn tại)
     * Thao tác trên Khách #1 tuyệt đối KHÔNG ảnh hưởng đến Admin #1
     */
    public function test_two_tables_with_same_id_do_not_interfere(): void
    {
        // Lấy admin có ID = 1 (hoặc admin đầu tiên)
        $admin1 = Admin::first();
        $this->assertNotNull($admin1);
        $admin1InitialStatus = $admin1->tinh_trang;
        $admin1InitialName = $admin1->ho_ten;

        // Tạo User có ID trùng với ID của Admin (nếu chưa có User ID = 1)
        $user1 = User::create([
            'id'            => $admin1->id,
            'ho_ten'        => 'Khách hàng Trùng ID',
            'email'         => 'trungid@test.com',
            'password'      => Hash::make('SecretPass123!'),
            'so_dien_thoai' => '0988776655',
            'tinh_trang'    => 1,
            'nhu_cau_thue'  => User::DEMAND_SELF_DRIVE,
        ]);

        $this->assertEquals($admin1->id, $user1->id, 'ID của Admin và User phải giống hệt nhau để test va chạm!');

        // 1. Thực hiện khóa tài khoản Khách hàng ID=1
        $lockRes = $this->actingAs($this->ownerAdmin, 'admin')
            ->postJson("/api/admin/customers/{$user1->id}/status");

        $lockRes->assertStatus(200);

        // Kiểm tra User #1 đã bị khóa
        $user1->refresh();
        $this->assertEquals(0, $user1->tinh_trang);

        // Kiểm tra Admin #1 VẪN GIỮ NGUYÊN trạng thái, không hề bị khóa nhầm!
        $admin1->refresh();
        $this->assertEquals($admin1InitialStatus, $admin1->tinh_trang, 'Trạng thái của Admin không được bị thay đổi khi thao tác trên User cùng ID!');

        // 2. Thực hiện cập nhật hồ sơ Khách hàng ID=1
        $updateRes = $this->actingAs($this->ownerAdmin, 'admin')
            ->putJson("/api/admin/customers/{$user1->id}", [
                'ho_ten'            => 'Tên Khách Đã Đổi',
                'nhu_cau_thue'      => User::DEMAND_MONTHLY,
                'ngan_sach_du_kien' => 15000000,
            ]);

        $updateRes->assertStatus(200);

        // Khách hàng đổi tên thành công
        $user1->refresh();
        $this->assertEquals('Tên Khách Đã Đổi', $user1->ho_ten);

        // Admin VẪN GIỮ NGUYÊN tên ban đầu!
        $admin1->refresh();
        $this->assertEquals($admin1InitialName, $admin1->ho_ten, 'Tên của Admin không được bị ghi đè!');
    }

    /**
     * Test 3: Lịch sử đơn và liên hệ được cô lập đúng từng khách, không bị lẫn chéo
     */
    public function test_customer_history_isolation(): void
    {
        $userA = User::create([
            'ho_ten'        => 'Khách Hàng A',
            'email'         => 'khachA@test.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0901111111',
            'tinh_trang'    => 1,
        ]);

        $userB = User::create([
            'ho_ten'        => 'Khách Hàng B',
            'email'         => 'khachB@test.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0902222222',
            'tinh_trang'    => 1,
        ]);

        // Đơn của khách A
        $bookingA = Booking::create([
            'user_id'       => $userA->id,
            'car_id'        => $this->car1->id,
            'ma_dat_xe'     => 'BK-A-001',
            'start_date'    => Carbon::now()->addDays(1),
            'end_date'      => Carbon::now()->addDays(3),
            'tong_tien'     => 5000000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_PAID,
        ]);

        // Đơn của khách B
        $bookingB = Booking::create([
            'user_id'       => $userB->id,
            'car_id'        => $this->car2->id,
            'ma_dat_xe'     => 'BK-B-002',
            'start_date'    => Carbon::now()->addDays(5),
            'end_date'      => Carbon::now()->addDays(7),
            'tong_tien'     => 3600000,
            'tinh_trang'    => Booking::STATUS_PENDING,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        // Liên hệ của khách A
        Contact::create([
            'user_id'       => $userA->id,
            'ho_ten'        => $userA->ho_ten,
            'email'         => $userA->email,
            'so_dien_thoai' => $userA->so_dien_thoai,
            'tieu_de'       => 'Hỏi hợp đồng khách A',
            'noi_dung'      => 'Nội dung liên hệ của A',
            'trang_thai'    => 0,
        ]);

        // Xem hồ sơ khách A
        $resA = $this->actingAs($this->ownerAdmin, 'admin')
            ->getJson("/api/admin/customers/{$userA->id}");

        $resA->assertStatus(200);

        // Đơn của A có 1 đơn mã BK-A-001, tuyệt đối không có BK-B-002
        $resA->assertJsonCount(1, 'data.bookings')
            ->assertJsonPath('data.bookings.0.ma_dat_xe', 'BK-A-001');

        $resA->assertJsonCount(1, 'data.contacts')
            ->assertJsonPath('data.contacts.0.tieu_de', 'Hỏi hợp đồng khách A');
    }

    /**
     * Test 4: Thông tin tài chính chỉ hiển thị theo quyền (Kế toán/Owner thấy số tiền, Dispatcher bị ẩn)
     */
    public function test_financial_information_visibility_by_permission(): void
    {
        $user = User::create([
            'ho_ten'        => 'Khách VIP Doanh Nghiệp',
            'email'         => 'vip@enterprise.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0933999888',
            'tinh_trang'    => 1,
        ]);

        Booking::create([
            'user_id'       => $user->id,
            'car_id'        => $this->car1->id,
            'ma_dat_xe'     => 'BK-VIP-888',
            'start_date'    => Carbon::now()->subDays(10),
            'end_date'      => Carbon::now()->subDays(5),
            'tong_tien'     => 12500000,
            'tinh_trang'    => Booking::STATUS_COMPLETED,
            'is_thanh_toan' => Booking::PAYMENT_PAID,
        ]);

        // 1. Chuyên viên vận hành (staffAdmin: không có quyền reports.view hay payments.manage)
        $staffRes = $this->actingAs($this->staffAdmin, 'admin')
            ->getJson("/api/admin/customers/{$user->id}");

        $staffRes->assertStatus(200)
            ->assertJsonPath('data.stats.can_view_financial', false)
            ->assertJsonPath('data.stats.total_spent', null)
            ->assertJsonPath('data.bookings.0.tong_tien', null)
            ->assertJsonPath('data.bookings.0.tong_tien_formatted', '[Bảo mật doanh thu]');

        // 2. Kế toán viên (financeAdmin: có quyền reports.view) -> Thấy đầy đủ số tiền
        $financeRes = $this->actingAs($this->financeAdmin, 'admin')
            ->getJson("/api/admin/customers/{$user->id}");

        $financeRes->assertStatus(200)
            ->assertJsonPath('data.stats.can_view_financial', true)
            ->assertJsonPath('data.stats.total_spent', 12500000)
            ->assertJsonPath('data.bookings.0.tong_tien', 12500000);
    }

    /**
     * Test 5: Cập nhật hồ sơ qua API riêng (nhu cầu, ngân sách, xe quan tâm, ghi chú an toàn)
     */
    public function test_update_customer_profile_and_add_care_notes(): void
    {
        $user = User::create([
            'ho_ten'        => 'Nguyễn Thị Minh Khai',
            'email'         => 'khai.nguyen@test.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0918273645',
            'tinh_trang'    => 1,
        ]);

        // 1. Cập nhật hồ sơ hợp lệ
        $updateRes = $this->actingAs($this->ownerAdmin, 'admin')
            ->putJson("/api/admin/customers/{$user->id}", [
                'ho_ten'            => 'Nguyễn Thị Minh Khai (Đã xác minh)',
                'so_dien_thoai'     => '0918273645',
                'dia_chi'           => '123 Lê Lợi, Quận 1, TP.HCM',
                'nhu_cau_thue'      => User::DEMAND_WITH_DRIVER,
                'ngan_sach_du_kien' => 3000000,
                'car_id'            => $this->car1->id,
                'ghi_chu_so_thich'  => 'Thích xe có trang bị ghế trẻ em, nước suối và tài xế biết tiếng Anh.',
                'gplx_hang'         => 'B2',
            ]);

        $updateRes->assertStatus(200)
            ->assertJsonPath('status', 1)
            ->assertJsonPath('data.nhu_cau_thue', User::DEMAND_WITH_DRIVER)
            ->assertJsonPath('data.gplx_hang', 'B2');

        // 2. Thử nhập số thẻ ngân hàng vào ghi chú tự do -> Bị chặn 422
        $sensitiveRes = $this->actingAs($this->ownerAdmin, 'admin')
            ->putJson("/api/admin/customers/{$user->id}", [
                'ho_ten'           => 'Nguyễn Thị Minh Khai',
                'ghi_chu_so_thich' => 'Số thẻ visa: 4111 2222 3333 4444 hạn dùng 12/28',
            ]);

        $sensitiveRes->assertStatus(422)
            ->assertJsonPath('status', 0);

        // 3. Thêm ghi chú chăm sóc khách hàng theo thời gian
        $noteRes = $this->actingAs($this->ownerAdmin, 'admin')
            ->postJson("/api/admin/customers/{$user->id}/notes", [
                'content' => 'Khách gọi xác nhận cần thuê thêm 1 xe cho đoàn đối tác vào tuần tới.',
            ]);

        $noteRes->assertStatus(200)
            ->assertJsonPath('status', 1)
            ->assertJsonPath('data.content', 'Khách gọi xác nhận cần thuê thêm 1 xe cho đoàn đối tác vào tuần tới.');

        $this->assertDatabaseHas('customer_notes', [
            'user_id' => $user->id,
            'content' => 'Khách gọi xác nhận cần thuê thêm 1 xe cho đoàn đối tác vào tuần tới.',
        ]);
    }

    /**
     * Test 6: Khóa tài khoản khách hàng KHÔNG xóa lịch sử đơn
     */
    public function test_locking_customer_does_not_delete_booking_history(): void
    {
        $user = User::create([
            'ho_ten'        => 'Khách Cần Khóa Tạm',
            'email'         => 'lockme@test.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0977665544',
            'tinh_trang'    => 1,
        ]);

        $booking = Booking::create([
            'user_id'       => $user->id,
            'car_id'        => $this->car2->id,
            'ma_dat_xe'     => 'BK-LOCK-99',
            'start_date'    => Carbon::now()->subDays(2),
            'end_date'      => Carbon::now()->subDay(),
            'tong_tien'     => 3600000,
            'tinh_trang'    => Booking::STATUS_COMPLETED,
            'is_thanh_toan' => Booking::PAYMENT_PAID,
        ]);

        // Thực hiện khóa khách
        $lockRes = $this->actingAs($this->ownerAdmin, 'admin')
            ->postJson("/api/admin/customers/{$user->id}/status");

        $lockRes->assertStatus(200);

        $user->refresh();
        $this->assertEquals(0, $user->tinh_trang);

        // Đơn đặt xe VẪN TỒN TẠI NGUYÊN VẸN trong database!
        $this->assertDatabaseHas('bookings', [
            'id'        => $booking->id,
            'ma_dat_xe' => 'BK-LOCK-99',
            'user_id'   => $user->id,
        ]);
    }

    /**
     * Test 7: Liên kết an toàn yêu cầu tư vấn (Lead) của khách vãng lai với tài khoản khách hàng
     */
    public function test_link_guest_contact_lead_to_customer_account(): void
    {
        $user = User::create([
            'ho_ten'        => 'Lê Hoàng Nam',
            'email'         => 'nam.le@gmail.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0944556677',
            'tinh_trang'    => 1,
        ]);

        // Một lead gửi từ web liên hệ lúc chưa đăng ký tài khoản (user_id = null)
        $lead = Contact::create([
            'user_id'       => null,
            'ho_ten'        => 'Lê Hoàng Nam',
            'email'         => 'nam.le@gmail.com',
            'so_dien_thoai' => '0944556677',
            'tieu_de'       => 'Hỏi thủ tục thuê xe VF9 tự lái',
            'noi_dung'      => 'Tôi muốn tìm hiểu điều kiện cọc khi thuê VF9',
            'trang_thai'    => 0,
        ]);

        $this->assertNull($lead->user_id);

        // Admin thực hiện liên kết lead này vào tài khoản của khách
        $linkRes = $this->actingAs($this->ownerAdmin, 'admin')
            ->postJson("/api/admin/customers/{$user->id}/link-contact", [
                'contact_id' => $lead->id,
            ]);

        $linkRes->assertStatus(200)
            ->assertJsonPath('status', 1);

        $lead->refresh();
        $this->assertEquals($user->id, $lead->user_id, 'Lead phải được liên kết đúng với user_id của khách!');
    }
}
