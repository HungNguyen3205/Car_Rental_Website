<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingPaymentConfirmationTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerA;
    protected User $customerB;
    protected Admin $admin;
    protected Car $car;
    protected Booking $bookingA;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Tạo category & car
        $category = CarCategory::create([
            'name'        => 'SUV Điện',
            'slug'        => 'suv-dien',
            'description' => 'Dòng xe thể thao đa dụng',
        ]);

        $this->car = Car::create([
            'category_id'   => $category->id,
            'name'          => 'VinFast VF8 Plus',
            'price_per_day' => 1200000,
            'status'        => 'available',
            'city'          => 'Hà Nội',
            'image'         => 'vf8.png',
        ]);

        // 2. Tạo 2 khách hàng
        $this->customerA = User::create([
            'ho_ten'        => 'Nguyễn Văn A',
            'email'         => 'khach_a@example.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0912345678',
            'is_active'     => 1,
        ]);

        $this->customerB = User::create([
            'ho_ten'        => 'Trần Thị B',
            'email'         => 'khach_b@example.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0987654321',
            'is_active'     => 1,
        ]);

        // 3. Tạo tài khoản Admin
        $this->admin = Admin::create([
            'ho_ten'        => 'Quản Trị Viên',
            'email'         => 'admin@driverent.vn',
            'password'      => Hash::make('admin123'),
            'so_dien_thoai' => '0900000000',
            'is_active'     => 1,
        ]);

        // 4. Tạo đơn đặt xe cho khách A (chưa thanh toán, chưa duyệt lịch)
        $this->bookingA = Booking::create([
            'user_id'       => $this->customerA->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX000100',
            'start_date'    => now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_date'      => now()->addDays(3)->format('Y-m-d H:i:s'),
            'ly_do_thue'    => 'Công tác',
            'tong_tien'     => 2400000,
            'tinh_trang'    => 0, // Chờ duyệt lịch
            'is_thanh_toan' => 0, // Chưa thanh toán
        ]);
    }

    /**
     * KIỂM THỬ 1: Khách hàng KHÔNG THỂ tự xác nhận đã thanh toán qua checkPayment.
     * API kiểm tra thanh toán chỉ đọc trạng thái; tuyệt đối không cập nhật is_thanh_toan = 1.
     */
    public function test_customer_cannot_confirm_payment_via_chatbot_check_payment(): void
    {
        Sanctum::actingAs($this->customerA, ['*']);

        $response = $this->postJson('/api/chatbot/check-payment', [
            'ma_dat_xe' => $this->bookingA->ma_dat_xe,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'         => 1,
                'payment_status' => 'unpaid',
                'is_thanh_toan'  => 0,
                'tinh_trang'     => 0,
            ]);

        // Kiểm tra trực tiếp cơ sở dữ liệu: Phải giữ nguyên 0, không được bị đổi thành 1
        $fresh = $this->bookingA->fresh();
        $this->assertEquals(0, $fresh->is_thanh_toan, 'Cảnh báo bảo mật: is_thanh_toan đã bị tự động thay đổi!');
        $this->assertEquals(0, $fresh->tinh_trang, 'Cảnh báo bảo mật: tinh_trang thuê đã bị tự động kích hoạt!');
        $this->assertNull($fresh->payment_confirmed_at);
        $this->assertNull($fresh->payment_confirmed_by);
    }

    /**
     * KIỂM THỬ 2: Khách hàng B KHÔNG ĐƯỢC PHÉP xem thông tin thanh toán của Khách hàng A.
     */
    public function test_customer_cannot_check_payment_of_another_customer(): void
    {
        Sanctum::actingAs($this->customerB, ['*']);

        $response = $this->postJson('/api/chatbot/check-payment', [
            'ma_dat_xe' => $this->bookingA->ma_dat_xe,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status'  => 0,
                'message' => 'Không tìm thấy thông tin đơn đặt xe với mã: ' . $this->bookingA->ma_dat_xe,
            ]);
    }

    /**
     * KIỂM THỬ 3: Khách hàng B KHÔNG THỂ tải biên lai vào đơn đặt xe của Khách hàng A.
     */
    public function test_customer_cannot_upload_bill_to_another_customer_booking(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->customerB, ['*']);

        $file = UploadedFile::fake()->image('fake_bill.png');

        $response = $this->postJson('/api/user/bookings/upload-bill', [
            'booking_id' => $this->bookingA->id,
            'bill_image' => $file,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status'  => 0,
                'message' => 'Không tìm thấy đơn hàng!',
            ]);

        $fresh = $this->bookingA->fresh();
        $this->assertNull($fresh->bill_image);
    }

    /**
     * KIỂM THỬ 4: Admin duyệt biên lai thủ công thành công qua endpoint có phân quyền.
     * Lưu vết người xác nhận và thời điểm xác nhận.
     * ĐẶC BIỆT: Không tự ý chuyển trạng thái thuê (tinh_trang) chỉ vì thanh toán thành công!
     */
    public function test_admin_can_manually_confirm_payment_with_audit_log_and_isolated_rental_status(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $response = $this->postJson('/api/admin/bookings/confirm-payment', [
            'id'           => $this->bookingA->id,
            'payment_note' => 'Đã đối soát khớp tiền vào Techcombank',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'            => 1,
                'already_confirmed' => false,
            ]);

        $fresh = $this->bookingA->fresh();

        // 1. Trạng thái thanh toán phải là 1
        $this->assertEquals(1, $fresh->is_thanh_toan);
        
        // 2. Phải lưu vết người xác nhận và thời điểm xác nhận
        $this->assertNotNull($fresh->payment_confirmed_at);
        $this->assertEquals($this->admin->id, $fresh->payment_confirmed_by);
        $this->assertEquals('Đã đối soát khớp tiền vào Techcombank', $fresh->payment_note);

        // 3. Nghiệp vụ cốt lõi: tinh_trang đơn thuê KHÔNG ĐƯỢC tự chuyển thành 1 (phải giữ nguyên 0)
        $this->assertEquals(0, $fresh->tinh_trang, 'Quy tắc nghiệp vụ vi phạm: tinh_trang đơn thuê bị tự động chuyển khi thanh toán!');
    }

    /**
     * KIỂM THỬ 5: Xử lý an toàn khi xác nhận lặp (Idempotency).
     * Gọi xác nhận nhiều lần không gây lỗi và không ghi nhận doanh thu trùng lặp.
     */
    public function test_idempotent_duplicate_payment_confirmation(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        // Lần 1: Xác nhận thanh toán
        $this->postJson('/api/admin/bookings/confirm-payment', [
            'id'           => $this->bookingA->id,
            'payment_note' => 'Xác nhận lần 1',
        ])->assertStatus(200);

        $freshFirst = $this->bookingA->fresh();
        $firstConfirmedAt = $freshFirst->payment_confirmed_at;

        // Lần 2: Thao tác bấm lại hoặc mạng bị lặp request
        $responseSecond = $this->postJson('/api/admin/bookings/confirm-payment', [
            'id'           => $this->bookingA->id,
            'payment_note' => 'Bấm nhầm lần 2',
        ]);

        $responseSecond->assertStatus(200)
            ->assertJson([
                'status'            => 1,
                'already_confirmed' => true,
            ]);

        $freshSecond = $this->bookingA->fresh();
        $this->assertEquals(1, $freshSecond->is_thanh_toan);
        $this->assertEquals(
            $firstConfirmedAt->toIso8601String(), 
            $freshSecond->payment_confirmed_at->toIso8601String(),
            'Idempotency bị vi phạm: Thời điểm thanh toán bị ghi đè khi gọi lặp!'
        );
    }

    /**
     * KIỂM THỬ 6: Người dùng thông thường hoặc chưa đăng nhập không thể gọi endpoint duyệt của admin.
     */
    public function test_unauthorized_user_cannot_confirm_payment(): void
    {
        // 1. Không đăng nhập
        $this->postJson('/api/admin/bookings/confirm-payment', ['id' => $this->bookingA->id])
            ->assertStatus(401);

        // 2. Đăng nhập dưới vai trò User thông thường
        Sanctum::actingAs($this->customerA, ['*']);
        $this->postJson('/api/admin/bookings/confirm-payment', ['id' => $this->bookingA->id])
            ->assertStatus(401);
    }
}
