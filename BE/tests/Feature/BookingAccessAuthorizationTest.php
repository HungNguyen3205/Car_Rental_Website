<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingAccessAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerA;
    protected User $customerB;
    protected Admin $admin;
    protected Car $car;
    protected Booking $bookingA;
    protected Booking $bookingB;

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
            'name'          => 'VinFast VF9 Eco',
            'price_per_day' => 1800000,
            'status'        => 'available',
            'city'          => 'Hà Nội',
            'image'         => 'vf9.png',
        ]);

        // 2. Tạo 2 khách hàng phân biệt
        $this->customerA = User::create([
            'ho_ten'        => 'Khách Hàng A',
            'email'         => 'khach_a@example.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0911111111',
            'is_active'     => 1,
            'tinh_trang'    => 1,
        ]);

        $this->customerB = User::create([
            'ho_ten'        => 'Khách Hàng B',
            'email'         => 'khach_b@example.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0922222222',
            'is_active'     => 1,
            'tinh_trang'    => 1,
        ]);

        // 3. Tạo Admin
        $this->admin = Admin::create([
            'ho_ten'        => 'Quản Trị Viên',
            'email'         => 'admin_test@example.com',
            'password'      => Hash::make('admin123'),
            'so_dien_thoai' => '0999999999',
            'tinh_trang'    => 1,
        ]);

        // 4. Tạo thư mục private bills cho bài kiểm tra
        $privateDir = storage_path('app/private/bills');
        if (!File::exists($privateDir)) {
            File::makeDirectory($privateDir, 0755, true);
        }

        // Tạo 2 file biên lai vật lý trong private storage
        $billPathA = $privateDir . '/bill_test_A.png';
        $billPathB = $privateDir . '/bill_test_B.png';
        file_put_contents($billPathA, 'FAKE_PNG_CONTENT_A');
        file_put_contents($billPathB, 'FAKE_PNG_CONTENT_B');

        // 5. Tạo 2 đơn đặt xe tương ứng cho khách A và khách B
        $this->bookingA = Booking::create([
            'user_id'       => $this->customerA->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'ORDER_A_1001',
            'start_date'    => Carbon::now()->addDays(1)->toDateString(),
            'end_date'      => Carbon::now()->addDays(3)->toDateString(),
            'ly_do_thue'    => 'Công tác',
            'tong_tien'     => 3600000,
            'tinh_trang'    => 0, // Chờ duyệt
            'is_thanh_toan' => 0,
            'bill_image'    => 'private/bills/bill_test_A.png',
        ]);

        $this->bookingB = Booking::create([
            'user_id'       => $this->customerB->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'ORDER_B_2002',
            'start_date'    => Carbon::now()->addDays(5)->toDateString(),
            'end_date'      => Carbon::now()->addDays(7)->toDateString(),
            'ly_do_thue'    => 'Du lịch gia đình',
            'tong_tien'     => 3600000,
            'tinh_trang'    => 0, // Chờ duyệt
            'is_thanh_toan' => 0,
            'bill_image'    => 'private/bills/bill_test_B.png',
        ]);
    }

    protected function tearDown(): void
    {
        // Dọn dẹp các tệp test đã tạo
        $privateDir = storage_path('app/private/bills');
        @unlink($privateDir . '/bill_test_A.png');
        @unlink($privateDir . '/bill_test_B.png');
        @unlink($privateDir . '/legacy_bill.png');
        @unlink(public_path('uploads/bills/legacy_bill.png'));

        parent::tearDown();
    }

    /**
     * TEST 1: Khách A chỉ xem được danh sách đơn của chính mình, không bao giờ thấy đơn của B.
     */
    public function test_customer_a_can_only_view_own_bookings_in_list(): void
    {
        Sanctum::actingAs($this->customerA, ['*']);

        $response = $this->getJson('/api/user/bookings/data');

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->bookingA->id, $data[0]['id']);
        $this->assertNotEquals($this->bookingB->id, $data[0]['id']);
    }

    /**
     * TEST 2: Khách A tra mã đơn của Khách B nhận HTTP 404 (chống enumeration/IDOR).
     * Khách A tra mã đơn của chính mình nhận HTTP 200 kèm dữ liệu đã được làm sạch (sanitized).
     */
    public function test_customer_cannot_lookup_another_customer_booking_code(): void
    {
        Sanctum::actingAs($this->customerA, ['*']);

        // A tra mã của B -> Nhận 404
        $resForbidden = $this->postJson('/api/client/kiem-tra-ma-dat-lich', [
            'ma_dat_xe' => $this->bookingB->ma_dat_xe,
        ]);

        $resForbidden->assertStatus(404)
            ->assertJson([
                'status'  => 0,
                'message' => 'Không tìm thấy thông tin đơn hàng phù hợp!',
            ]);

        // A tra mã của chính A -> Nhận 200 với dữ liệu an toàn
        $resOwn = $this->postJson('/api/client/kiem-tra-ma-dat-lich', [
            'ma_dat_xe' => $this->bookingA->ma_dat_xe,
        ]);

        $resOwn->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'data'   => [
                    'id'        => $this->bookingA->id,
                    'ma_dat_xe' => $this->bookingA->ma_dat_xe,
                ],
            ]);

        // Xác nhận dữ liệu không bị lộ user_id, email, phone hay cột nội bộ
        $data = $resOwn->json('data');
        $this->assertArrayNotHasKey('user_id', $data);
        $this->assertArrayNotHasKey('email', $data);
        $this->assertArrayNotHasKey('so_dien_thoai', $data);
        $this->assertArrayNotHasKey('payment_note', $data);
    }

    /**
     * TEST 3: Khách A KHÔNG THỂ hủy đơn của Khách B bằng cách đổi ID.
     */
    public function test_customer_cannot_cancel_another_customer_booking(): void
    {
        Sanctum::actingAs($this->customerA, ['*']);

        $response = $this->postJson('/api/user/bookings/huy-lich', [
            'id' => $this->bookingB->id,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status'  => 0,
                'message' => 'Không tìm thấy đơn đặt xe!',
            ]);

        // Trạng thái đơn của B phải giữ nguyên (0 - Chờ xác nhận, không bị hủy)
        $this->assertEquals(0, $this->bookingB->fresh()->tinh_trang);
    }

    /**
     * TEST 4: Khách A KHÔNG THỂ tải biên lai vào đơn của Khách B.
     */
    public function test_customer_cannot_upload_receipt_to_another_customer_booking(): void
    {
        Sanctum::actingAs($this->customerA, ['*']);

        $file = UploadedFile::fake()->image('fake_receipt.jpg');

        $response = $this->postJson('/api/user/bookings/upload-bill', [
            'booking_id' => $this->bookingB->id,
            'bill_image' => $file,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status'  => 0,
                'message' => 'Không tìm thấy đơn hàng!',
            ]);

        // Đơn của B vẫn giữ nguyên biên lai ban đầu
        $this->assertEquals('private/bills/bill_test_B.png', $this->bookingB->fresh()->bill_image);
    }

    /**
     * TEST 5: Khách A KHÔNG THỂ tải hoặc xem file biên lai của Khách B qua endpoint riêng tư.
     * Khách A xem biên lai của chính mình thành công (HTTP 200).
     */
    public function test_customer_cannot_download_another_customer_receipt(): void
    {
        Sanctum::actingAs($this->customerA, ['*']);

        // Khách A gọi biên lai của Khách B -> 404
        $resForbidden = $this->get('/api/bookings/' . $this->bookingB->id . '/receipt');
        $resForbidden->assertStatus(404);

        // Khách A gọi biên lai của chính mình -> 200 kèm stream file
        $resOwn = $this->get('/api/bookings/' . $this->bookingA->id . '/receipt');
        $resOwn->assertStatus(200);
        $resOwn->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
        $resOwn->assertHeader('Pragma', 'no-cache');
    }

    /**
     * TEST 6: Khách A KHÔNG THỂ kiểm tra thanh toán đơn của B qua Chatbot bằng cách đổi mã đơn.
     * Chỉ kiểm tra được đơn của chính mình.
     */
    public function test_customer_cannot_check_payment_of_another_customer_via_chatbot(): void
    {
        Sanctum::actingAs($this->customerA, ['*']);

        // A kiểm tra mã của B -> Nhận 404
        $resB = $this->postJson('/api/chatbot/check-payment', [
            'ma_dat_xe' => $this->bookingB->ma_dat_xe,
        ]);

        $resB->assertStatus(404)
            ->assertJson([
                'status'  => 0,
                'message' => 'Không tìm thấy thông tin đơn đặt xe với mã: ' . $this->bookingB->ma_dat_xe,
            ]);

        // A kiểm tra mã của A -> Nhận 200
        $resA = $this->postJson('/api/chatbot/check-payment', [
            'ma_dat_xe' => $this->bookingA->ma_dat_xe,
        ]);

        $resA->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'data'   => [
                    'ma_dat_xe'     => $this->bookingA->ma_dat_xe,
                    'is_thanh_toan' => 0,
                ],
            ]);
    }

    /**
     * TEST 7: Các yêu cầu chưa xác thực (Unauthenticated) đều bị từ chối với HTTP 401.
     */
    public function test_unauthenticated_requests_are_rejected_with_401(): void
    {
        // 1. Xem danh sách đơn
        $this->getJson('/api/user/bookings/data')->assertStatus(401);

        // 2. Hủy đơn
        $this->postJson('/api/user/bookings/huy-lich', ['id' => $this->bookingA->id])->assertStatus(401);

        // 3. Tra cứu mã đơn
        $this->postJson('/api/client/kiem-tra-ma-dat-lich', ['ma_dat_xe' => $this->bookingA->ma_dat_xe])->assertStatus(401);

        // 4. Tải file biên lai
        $this->getJson('/api/bookings/' . $this->bookingA->id . '/receipt')->assertStatus(401);

        // 5. Kiểm tra thanh toán chatbot
        $this->postJson('/api/chatbot/check-payment', ['ma_dat_xe' => $this->bookingA->ma_dat_xe])->assertStatus(401);
    }

    /**
     * TEST 8: Admin có thể truy cập toàn bộ đơn, tải biên lai và duyệt thanh toán.
     * Khách hàng thông thường không thể gọi API quản trị (401/403/404).
     */
    public function test_admin_access_and_customer_forbidden_from_admin_apis(): void
    {
        // Khách A cố gọi API admin -> Bị từ chối
        Sanctum::actingAs($this->customerA, ['*']);
        $resCustomerToAdmin = $this->postJson('/api/admin/bookings/confirm-payment', [
            'id' => $this->bookingB->id,
        ]);
        // Guard auth:admin từ chối token của user thường
        $this->assertTrue(in_array($resCustomerToAdmin->status(), [401, 403]));

        // Admin thật đăng nhập
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        // Admin xem biên lai của đơn A -> Thành công 200
        $resAdminReceipt = $this->get('/api/bookings/' . $this->bookingA->id . '/receipt');
        $resAdminReceipt->assertStatus(200);

        // Admin xem danh sách đơn quản trị -> Đầy đủ cả 2 đơn
        $resAdminList = $this->getJson('/api/admin/bookings/data');
        $resAdminList->assertStatus(200);
        $this->assertCount(2, $resAdminList->json('data'));

        // Admin duyệt thanh toán đơn A -> Thành công 200 kèm audit log
        $resConfirm = $this->postJson('/api/admin/bookings/confirm-payment', [
            'id'           => $this->bookingA->id,
            'payment_note' => 'Đã đối soát sao kê VCB số 998877',
        ]);

        $resConfirm->assertStatus(200)->assertJson(['status' => 1]);
        $freshA = $this->bookingA->fresh();
        $this->assertEquals(1, $freshA->is_thanh_toan);
        $this->assertEquals($this->admin->id, $freshA->payment_confirmed_by);
        $this->assertEquals('Đã đối soát sao kê VCB số 998877', $freshA->payment_note);
    }

    /**
     * TEST 9: Tự động tương thích và di chuyển an toàn biên lai cũ (Legacy fallback)
     * mà không làm mất file hoặc đứt gãy trải nghiệm.
     */
    public function test_legacy_receipt_backward_compatibility_auto_migration(): void
    {
        // Giả lập file cũ nằm ở public/uploads/bills/legacy_bill.png
        $legacyUploadsDir = public_path('uploads/bills');
        if (!File::exists($legacyUploadsDir)) {
            File::makeDirectory($legacyUploadsDir, 0755, true);
        }
        $legacyFilePath = $legacyUploadsDir . '/legacy_bill.png';
        file_put_contents($legacyFilePath, 'LEGACY_BILL_CONTENT_ABC');

        // Đơn của A trỏ tới đường dẫn cũ
        $this->bookingA->update(['bill_image' => 'uploads/bills/legacy_bill.png']);

        Sanctum::actingAs($this->customerA, ['*']);

        // Gọi lấy biên lai
        $response = $this->get('/api/bookings/' . $this->bookingA->id . '/receipt');
        $response->assertStatus(200);

        // Kiểm tra file đã được di chuyển vào private storage
        $privateMigrated = storage_path('app/private/bills/legacy_bill.png');
        $this->assertTrue(File::exists($privateMigrated));
        $this->assertEquals('LEGACY_BILL_CONTENT_ABC', file_get_contents($privateMigrated));

        // File ở public phải bị xóa để đảm bảo tính riêng tư
        $this->assertFalse(File::exists($legacyFilePath));

        // DB được cập nhật sang private/bills/legacy_bill.png
        $this->assertEquals('private/bills/legacy_bill.png', $this->bookingA->fresh()->bill_image);
    }
}
