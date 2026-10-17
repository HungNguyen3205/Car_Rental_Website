<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCreateBookingForCustomerTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected User $customerA;
    protected User $customerB;
    protected Car $car;
    protected CarCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CarCategory::create([
            'name'        => 'SUV Sang Trọng',
            'slug'        => 'suv-sang-trong',
            'description' => 'Xe gầm cao tiện nghi',
        ]);

        $this->car = Car::create([
            'name'          => 'Mercedes-Benz GLC 300',
            'price_per_day' => 2000000,
            'status'        => 'available',
            'city'          => 'sg',
            'type'          => 'SUV 5 chỗ',
            'image'         => 'glc300.png',
            'category_id'   => $this->category->id,
        ]);

        $this->admin = Admin::create([
            'ho_ten'        => 'Quản Trị Viên Hỗ Trợ',
            'email'         => 'admin_support@example.com',
            'password'      => Hash::make('admin_pass123'),
            'so_dien_thoai' => '0909000111',
            'tinh_trang'    => 1,
        ]);

        $this->customerA = User::create([
            'ho_ten'        => 'Nguyễn Thị Hương',
            'email'         => 'huong.nguyen@example.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0912345678',
            'tinh_trang'    => 1,
        ]);

        $this->customerB = User::create([
            'ho_ten'        => 'Trần Tuấn Kiệt',
            'email'         => 'kiet.tran@example.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0988776655',
            'tinh_trang'    => 1,
        ]);
    }

    /**
     * TEST 1: Admin tìm kiếm khách hàng theo tên, email, số điện thoại có phân trang
     */
    public function test_admin_can_search_customers_with_pagination(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        // Tìm kiếm theo số điện thoại
        $responsePhone = $this->getJson('/api/admin/users/search?q=0988776655');
        $responsePhone->assertStatus(200)
            ->assertJson([
                'status' => 1,
            ]);
        $data = $responsePhone->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Trần Tuấn Kiệt', $data[0]['ho_ten']);

        // Tìm kiếm theo tên
        $responseName = $this->getJson('/api/admin/users/search?q=Hương');
        $responseName->assertStatus(200);
        $this->assertEquals('Nguyễn Thị Hương', $responseName->json('data.0.ho_ten'));

        // Kiểm tra cấu trúc phân trang
        $this->assertArrayHasKey('pagination', $responseName->json());
        $this->assertEquals(1, $responseName->json('pagination.current_page'));
    }

    /**
     * TEST 2: Tài khoản thường không có quyền tìm kiếm khách hàng hoặc gọi API admin
     */
    public function test_customer_cannot_search_customers_or_call_admin_api(): void
    {
        Sanctum::actingAs($this->customerA, ['*']);

        $response = $this->getJson('/api/admin/users/search?q=test');
        $response->assertStatus(401); // Không thuộc guard admin
    }

    /**
     * TEST 3: Admin tạo mới khách hàng với validation chặt chẽ và mật khẩu an toàn
     */
    public function test_admin_can_quick_create_customer(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $response = $this->postJson('/api/admin/users/quick-create', [
            'ho_ten'        => 'Lê Hoàng Nam',
            'email'         => 'hoangnam@example.com',
            'so_dien_thoai' => '0933221100',
            'dia_chi'       => '456 Lê Duẩn, Quận 1',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 1,
                'message' => 'Tạo mới hồ sơ khách hàng thành công!',
            ]);

        $createdUser = User::where('email', 'hoangnam@example.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('0933221100', $createdUser->so_dien_thoai);
        // Mật khẩu được sinh ngẫu nhiên bảo mật cao, không bao giờ là mật khẩu công khai yếu (như 123456)
        $this->assertFalse(Hash::check('123456', $createdUser->password));
        $this->assertFalse(Hash::check('password', $createdUser->password));
    }

    /**
     * TEST 4: Admin đặt xe hộ đúng khách hàng cụ thể; tách biệt user_id và created_by_admin_id
     */
    public function test_admin_creates_booking_for_specific_customer_succeeds(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $startDate = Carbon::now()->addDays(3)->format('Y-m-d 10:00:00');
        $endDate   = Carbon::now()->addDays(6)->format('Y-m-d 10:00:00'); // 3 ngày

        $response = $this->postJson('/api/admin/bookings/create-for-customer', [
            'customer_id'     => $this->customerB->id,
            'car_id'          => $this->car->id,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'dia_chi_nhan_xe' => 'Sân bay Tân Sơn Nhất',
            'dia_chi_tra_xe'  => 'Sân bay Tân Sơn Nhất',
            'ly_do_thue'      => 'Khách VIP công tác',
            'is_thanh_toan'   => 1, // Admin thu tiền mặt trực tiếp
            'tinh_trang'      => 1, // Duyệt luôn
            'payment_note'    => 'Khách thanh toán tiền mặt tại quầy',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 1,
            ]);
        $this->assertStringContainsString('Trần Tuấn Kiệt', $response->json('message'));

        $booking = Booking::where('user_id', $this->customerB->id)->first();
        $this->assertNotNull($booking);
        $this->assertEquals($this->customerB->id, $booking->user_id);
        $this->assertEquals($this->admin->id, $booking->created_by_admin_id);
        $this->assertEquals(6000000, (float) $booking->tong_tien); // 3 ngày * 2.000.000
        $this->assertEquals(1, $booking->is_thanh_toan);
        $this->assertEquals(1, $booking->tinh_trang);
        $this->assertEquals($this->admin->id, $booking->payment_confirmed_by);

        // Khách hàng B xem danh sách hành trình của mình -> Có đơn này
        Sanctum::actingAs($this->customerB, ['*']);
        $respB = $this->getJson('/api/user/bookings/data');
        $respB->assertStatus(200);
        $this->assertCount(1, $respB->json('data'));
        $this->assertEquals($booking->id, $respB->json('data.0.id'));

        // Khách hàng A xem danh sách hành trình -> Không hề có đơn này, lịch sử hoàn toàn cô lập
        Sanctum::actingAs($this->customerA, ['*']);
        $respA = $this->getJson('/api/user/bookings/data');
        $respA->assertStatus(200);
        $this->assertCount(0, $respA->json('data'));
    }

    /**
     * TEST 5: Thiếu customer_id bị từ chối với HTTP 422
     */
    public function test_admin_create_booking_fails_when_customer_is_missing(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $response = $this->postJson('/api/admin/bookings/create-for-customer', [
            'car_id'     => $this->car->id,
            'start_date' => Carbon::now()->addDays(1)->format('Y-m-d 10:00:00'),
            'end_date'   => Carbon::now()->addDays(2)->format('Y-m-d 10:00:00'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);
        $this->assertEquals(0, $response->json('status'));
        $this->assertStringContainsString('khách hàng', $response->json('errors.customer_id.0'));
    }

    /**
     * TEST 6: customer_id không tồn tại trong hệ thống bị từ chối với HTTP 422
     */
    public function test_admin_create_booking_fails_when_customer_does_not_exist(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $response = $this->postJson('/api/admin/bookings/create-for-customer', [
            'customer_id' => 8888888, // ID ảo không tồn tại
            'car_id'      => $this->car->id,
            'start_date'  => Carbon::now()->addDays(1)->format('Y-m-d 10:00:00'),
            'end_date'    => Carbon::now()->addDays(2)->format('Y-m-d 10:00:00'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);
        $this->assertStringContainsString('không tồn tại', $response->json('errors.customer_id.0'));
    }

    /**
     * TEST 7: Khách hàng thường cố gọi endpoint admin đặt xe hộ bị từ chối với HTTP 401
     */
    public function test_regular_customer_cannot_call_admin_create_booking_endpoint(): void
    {
        Sanctum::actingAs($this->customerA, ['*']);

        $response = $this->postJson('/api/admin/bookings/create-for-customer', [
            'customer_id' => $this->customerB->id,
            'car_id'      => $this->car->id,
            'start_date'  => Carbon::now()->addDays(1)->format('Y-m-d 10:00:00'),
            'end_date'    => Carbon::now()->addDays(2)->format('Y-m-d 10:00:00'),
        ]);

        $response->assertStatus(401);
    }

    /**
     * TEST 8: Admin đặt xe hộ tuân thủ quy tắc chống trùng lịch của BookingService
     */
    public function test_admin_create_booking_enforces_schedule_conflict_prevention(): void
    {
        // 1. Tạo đơn trước đó từ ngày 5 đến ngày 10
        Booking::create([
            'user_id'       => $this->customerA->id,
            'car_id'        => $this->car->id,
            'start_date'    => Carbon::now()->addDays(5)->format('Y-m-d 10:00:00'),
            'end_date'      => Carbon::now()->addDays(10)->format('Y-m-d 10:00:00'),
            'tinh_trang'    => 1,
            'tong_tien'     => 10000000,
            'is_thanh_toan' => 1,
            'ma_dat_xe'     => 'EXIST001',
        ]);

        Sanctum::actingAs($this->admin, ['*'], 'admin');

        // 2. Admin cố đặt trùng lịch từ ngày 7 đến ngày 12
        $response = $this->postJson('/api/admin/bookings/create-for-customer', [
            'customer_id' => $this->customerB->id,
            'car_id'      => $this->car->id,
            'start_date'  => Carbon::now()->addDays(7)->format('Y-m-d 10:00:00'),
            'end_date'    => Carbon::now()->addDays(12)->format('Y-m-d 10:00:00'),
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'status'     => 0,
                'error_type' => 'SCHEDULE_CONFLICT',
            ]);
    }

    /**
     * TEST 9: Xóa bỏ hoàn toàn fallback User::first(): Admin gọi datLichXe phía client khi không có tài khoản khách sẽ bị chặn
     */
    public function test_admin_cannot_fallback_to_user_first_in_client_booking_endpoint(): void
    {
        // Admin này không có record trong users table cùng email
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'          => $this->car->id,
            'start_date'      => Carbon::now()->addDays(20)->format('Y-m-d 10:00:00'),
            'end_date'        => Carbon::now()->addDays(22)->format('Y-m-d 10:00:00'),
            'dia_chi_nhan_xe' => 'Quận 1',
            'dia_chi_tra_xe'  => 'Quận 1',
        ]);

        // Tuyệt đối không được fallback gán bừa vào User #1 (customerA hay customerB)
        $response->assertStatus(422)
            ->assertJson([
                'status' => 0,
            ]);
        $this->assertStringContainsString('Đặt xe hộ', $response->json('message'));
        $this->assertEquals(0, Booking::where('user_id', $this->customerA->id)->count());
    }
}
