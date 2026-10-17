<?php

namespace Tests\Feature;

use App\Http\Requests\Booking\UpdateBookingDatesRequest;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BackendValidationStandardizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Admin $admin;
    protected Car $car;
    protected CarCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CarCategory::create([
            'name'        => 'Sedan Cao Cấp',
            'slug'        => 'sedan-cao-cap',
            'description' => 'Xe sang',
        ]);

        $this->car = Car::create([
            'name'          => 'VinFast VF9 Test Validation',
            'price_per_day' => 1500000,
            'status'        => 'available',
            'city'          => 'sg',
            'type'          => 'SUV điện',
            'image'         => '/assets/images/vf9.png',
            'category_id'   => $this->category->id,
        ]);

        $this->customer = User::create([
            'ho_ten'        => 'Khách Hàng Test',
            'email'         => 'customer_val_' . uniqid() . '@example.com',
            'password'      => Hash::make('secret123'),
            'so_dien_thoai' => '0987654321',
            'tinh_trang'    => 1,
        ]);

        $this->admin = Admin::create([
            'ho_ten'        => 'Admin Test Validation',
            'email'         => 'admin_val_' . uniqid() . '@example.com',
            'password'      => Hash::make('admin123'),
            'so_dien_thoai' => '0901234567',
            'tinh_trang'    => 1,
        ]);
    }

    /**
     * TEST 1: Dữ liệu hợp lệ tạo đơn đặt xe thành công (HTTP 200)
     */
    public function test_valid_booking_payload_succeeds(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        $startDate = Carbon::now()->addDays(2)->format('Y-m-d 10:00:00');
        $endDate   = Carbon::now()->addDays(5)->format('Y-m-d 10:00:00');

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'          => $this->car->id,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'dia_chi_nhan_xe' => '123 Nguyễn Huệ, Quận 1',
            'dia_chi_tra_xe'  => '123 Nguyễn Huệ, Quận 1',
            'ly_do_thue'      => 'Cần xe có sạc đầy và rửa sạch',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 1,
            ]);
        $this->assertStringContainsString('Đặt xe thành công', $response->json('message'));

        $this->assertDatabaseHas('bookings', [
            'user_id'         => $this->customer->id,
            'car_id'          => $this->car->id,
            'dia_chi_nhan_xe' => '123 Nguyễn Huệ, Quận 1',
        ]);
    }

    /**
     * TEST 2: Thiếu các trường bắt buộc trả về HTTP 422 kèm cấu trúc lỗi chi tiết từng field
     */
    public function test_missing_required_fields_returns_standard_422(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors' => [
                    'car_id',
                    'start_date',
                    'end_date',
                ],
            ]);

        $this->assertEquals(0, $response->json('status'));
        $this->assertNotEmpty($response->json('message'));
        $this->assertIsArray($response->json('errors.car_id'));
    }

    /**
     * TEST 3: car_id không tồn tại bị từ chối với HTTP 422
     */
    public function test_non_existent_car_id_returns_422(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => 9999999,
            'start_date' => Carbon::now()->addDays(1)->format('Y-m-d 10:00:00'),
            'end_date'   => Carbon::now()->addDays(3)->format('Y-m-d 10:00:00'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['car_id']);
        $this->assertEquals(0, $response->json('status'));
    }

    /**
     * TEST 4: Ngày đảo ngược (end_date <= start_date) trả về HTTP 422
     */
    public function test_reversed_dates_returns_422(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        $startDate = Carbon::now()->addDays(5)->format('Y-m-d 10:00:00');
        $endDate   = Carbon::now()->addDays(2)->format('Y-m-d 10:00:00'); // Trả trước khi nhận

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => $this->car->id,
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
        $this->assertStringContainsString('sau thời gian nhận xe', $response->json('errors.end_date.0'));
    }

    /**
     * TEST 5: Đặt xe với ngày nhận trong quá khứ bị từ chối với HTTP 422
     */
    public function test_booking_in_the_past_returns_422(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => $this->car->id,
            'start_date' => '2020-01-01 10:00:00',
            'end_date'   => '2020-01-05 10:00:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
        $this->assertStringContainsString('quá khứ', $response->json('errors.start_date.0'));
    }

    /**
     * TEST 6: Phân biệt tạo đơn mới với chỉnh sửa đơn cũ (không chặn dữ liệu lịch sử)
     */
    public function test_editing_existing_booking_allows_past_start_date(): void
    {
        $request = new UpdateBookingDatesRequest();
        $rules = $request->rules();

        // Chỉnh sửa đơn cũ: Cho phép start_date trong quá khứ, miễn là end_date > start_date
        $validator = Validator::make([
            'start_date' => '2024-01-01 10:00:00',
            'end_date'   => '2024-01-05 10:00:00',
        ], $rules, $request->messages());

        $this->assertFalse($validator->fails(), 'UpdateBookingDatesRequest không được chặn ngày trong quá khứ của đơn lịch sử.');

        // Nhưng nếu end_date <= start_date thì vẫn phải chặn
        $invalidValidator = Validator::make([
            'start_date' => '2024-01-05 10:00:00',
            'end_date'   => '2024-01-01 10:00:00',
        ], $rules, $request->messages());

        $this->assertTrue($invalidValidator->fails());
    }

    /**
     * TEST 7: Dữ liệu sai kiểu (string thay vì số, định dạng ngày sai) trả về HTTP 422
     */
    public function test_invalid_data_types_returns_422(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => 'not_an_id',
            'start_date' => 'sai-dinh-dang-ngay',
            'end_date'   => 'cung-sai-ngay',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['car_id', 'start_date', 'end_date']);
    }

    /**
     * TEST 8: Khách truyền tổng tiền giả (fake total price) - Server tự tính, tuyệt đối không nhận
     */
    public function test_fake_client_total_price_is_ignored_and_computed_by_server(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        // Thuê 3 ngày: 15/11 đến 18/11 (3 * 1.500.000 = 4.500.000)
        $startDate = Carbon::now()->addDays(10)->format('Y-m-d 10:00:00');
        $endDate   = Carbon::now()->addDays(13)->format('Y-m-d 10:00:00');

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => $this->car->id,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'tong_tien'  => 100, // Cố tình khai 100 VNĐ
            'fake_price' => 500,
        ]);

        $response->assertStatus(200);
        $booking = Booking::where('user_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($booking);
        $this->assertEquals(4500000, (float) $booking->tong_tien);
    }

    /**
     * TEST 9: Giá tiền không được âm trên CRUD xe (Admin)
     */
    public function test_car_crud_rejects_negative_price(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $response = $this->postJson('/api/admin/cars/create', [
            'name'          => 'Xe Giá Âm',
            'price_per_day' => -500000,
            'category_id'   => $this->category->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price_per_day']);
        $this->assertStringContainsString('không được âm', $response->json('errors.price_per_day.0'));
    }

    /**
     * TEST 10: Admin Car update chỉ nhận field cho phép, chặn update không kiểm soát
     */
    public function test_admin_car_update_whitelists_fields(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $response = $this->postJson('/api/admin/cars/update', [
            'id'            => $this->car->id,
            'name'          => 'VinFast VF9 Đã Đổi Tên',
            'price_per_day' => 2000000,
            'unauthorized_field' => 'hacker_injected_value',
        ]);

        $response->assertStatus(200);
        $this->car->refresh();
        $this->assertEquals('VinFast VF9 Đã Đổi Tên', $this->car->name);
        $this->assertEquals(2000000, (float) $this->car->price_per_day);
    }

    /**
     * TEST 11: Trạng thái không hợp lệ bị từ chối; không cho phép chuyển trạng thái sai logic (kích hoạt lại đơn đã hủy)
     */
    public function test_invalid_status_and_illegal_state_transition(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        // 1. Tạo đơn
        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'start_date'    => Carbon::now()->addDays(5),
            'end_date'      => Carbon::now()->addDays(7),
            'tinh_trang'    => 2, // Đơn đã HỦY
            'tong_tien'     => 3000000,
            'is_thanh_toan' => 0,
            'ma_dat_xe'     => 'CAN' . strtoupper(uniqid()),
        ]);

        // 2. Trạng thái nằm ngoài miền giá trị hợp lệ (in:0,1,2)
        $invalidDomainResponse = $this->postJson('/api/admin/bookings/status', [
            'id'         => $booking->id,
            'tinh_trang' => 999, // Không hợp lệ
        ]);
        $invalidDomainResponse->assertStatus(422)
            ->assertJsonValidationErrors(['tinh_trang']);

        // 3. Đơn đã hủy (2) cố tình đổi ngược lại về đã duyệt (1) hoặc chờ duyệt (0)
        $illegalTransitionResponse = $this->postJson('/api/admin/bookings/status', [
            'id'         => $booking->id,
            'tinh_trang' => 1,
        ]);
        $illegalTransitionResponse->assertStatus(422)
            ->assertJson([
                'status' => 0,
            ]);
        $this->assertStringContainsString('đã hủy', $illegalTransitionResponse->json('message'));
    }

    /**
     * TEST 12: Upload biên lai kiểm tra MIME và dung lượng (tối đa 5MB)
     */
    public function test_upload_receipt_validates_mime_and_size(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'start_date'    => Carbon::now()->addDays(1),
            'end_date'      => Carbon::now()->addDays(3),
            'tinh_trang'    => 0,
            'tong_tien'     => 3000000,
            'is_thanh_toan' => 0,
            'ma_dat_xe'     => 'BILL' . strtoupper(uniqid()),
        ]);

        // File giả mạo dạng văn bản .txt nhưng rename .jpg (hoặc mime text/plain)
        $fakeTextFile = UploadedFile::fake()->create('hacker_script.php', 10, 'text/plain');

        $responseMime = $this->postJson('/api/user/bookings/upload-bill', [
            'booking_id' => $booking->id,
            'bill_image' => $fakeTextFile,
        ]);

        $responseMime->assertStatus(422)
            ->assertJsonValidationErrors(['bill_image']);

        // File quá khổ (> 5MB = 5120KB)
        $oversizeFile = UploadedFile::fake()->image('giant_receipt.png')->size(6000); // 6MB

        $responseSize = $this->postJson('/api/user/bookings/upload-bill', [
            'booking_id' => $booking->id,
            'bill_image' => $oversizeFile,
        ]);

        $responseSize->assertStatus(422)
            ->assertJsonValidationErrors(['bill_image']);
    }

    /**
     * TEST 13: Đăng ký người dùng kiểm tra định dạng email và số điện thoại
     */
    public function test_registration_validates_email_and_phone(): void
    {
        $response = $this->postJson('/api/register', [
            'ho_ten'        => 'Test User Regex',
            'email'         => 'invalid-email-format',
            'password'      => '123456',
            'so_dien_thoai' => '12345abc', // Sai số điện thoại
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'so_dien_thoai']);
    }

    /**
     * TEST 14: Không để transaction mở khi return sớm trong PromoteUser
     */
    public function test_promote_user_leaves_no_open_transactions_on_early_return(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $initialLevel = DB::transactionLevel();

        // 1. Gọi promote với user không tồn tại -> Kiểm tra transaction đã đóng
        $responseNotFound = $this->postJson('/api/admin/users/promote', [
            'id' => 888888,
        ]);
        $responseNotFound->assertStatus(422); // Request validation failed vì exists:users,id
        $this->assertEquals($initialLevel, DB::transactionLevel());

        // 2. Gọi promote khi email đã tồn tại trong admin
        $conflictUser = User::create([
            'ho_ten'        => 'Conflict Admin User',
            'email'         => $this->admin->email, // Trùng email với Admin hiện có
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0933445566',
            'tinh_trang'    => 1,
        ]);

        $responseConflict = $this->postJson('/api/admin/users/promote', [
            'id' => $conflictUser->id,
        ]);
        $responseConflict->assertStatus(422)
            ->assertJson(['status' => 0]);
        $this->assertEquals($initialLevel, DB::transactionLevel(), 'Transaction phải được đảm bảo đóng hoàn toàn sau return sớm!');
    }
}
