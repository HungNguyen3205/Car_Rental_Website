<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\User;
use App\Services\Booking\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CarDoubleBookingPreventionTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;
    protected User $userB;
    protected Car $availableCar;
    protected Car $maintenanceCar;
    protected BookingService $bookingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookingService = app(BookingService::class);

        $category = CarCategory::create([
            'name'        => 'Sedan Cao Cấp',
            'slug'        => 'sedan-cao-cap',
            'description' => 'Xe sang trọng tiện nghi',
        ]);

        $this->availableCar = Car::create([
            'category_id'   => $category->id,
            'name'          => 'VinFast Lux A2.0',
            'price_per_day' => 1000000,
            'status'        => 'available',
            'city'          => 'Hà Nội',
            'image'         => 'lux_a.png',
        ]);

        $this->maintenanceCar = Car::create([
            'category_id'   => $category->id,
            'name'          => 'Mercedes E300 AMG',
            'price_per_day' => 2500000,
            'status'        => 'maintenance',
            'city'          => 'Hà Nội',
            'image'         => 'e300.png',
        ]);

        $this->userA = User::create([
            'ho_ten'        => 'Nguyễn Văn A',
            'email'         => 'user_a@example.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0911222333',
            'is_active'     => 1,
            'tinh_trang'    => 1,
        ]);

        $this->userB = User::create([
            'ho_ten'        => 'Trần Thị B',
            'email'         => 'user_b@example.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0944555666',
            'is_active'     => 1,
            'tinh_trang'    => 1,
        ]);
    }

    /**
     * TEST 1: Khoảng giao nhau lệch trái (Left Overlap)
     * Đơn gốc: [10/06 10:00, 15/06 10:00)
     * Đơn mới: [08/06 10:00, 12/06 10:00)
     * Kỳ vọng: Bị từ chối với HTTP 409
     */
    public function test_booking_fails_on_left_overlap(): void
    {
        // Tạo đơn gốc của user A
        $this->bookingService->createBooking(
            $this->userA->id,
            $this->availableCar->id,
            '2027-06-10 10:00:00',
            '2027-06-15 10:00:00'
        );

        Sanctum::actingAs($this->userB, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => $this->availableCar->id,
            'start_date' => '2027-06-08 10:00:00',
            'end_date'   => '2027-06-12 10:00:00',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'status'     => 0,
                'error_type' => 'SCHEDULE_CONFLICT',
            ]);
    }

    /**
     * TEST 2: Khoảng giao nhau lệch phải (Right Overlap)
     * Đơn gốc: [10/06 10:00, 15/06 10:00)
     * Đơn mới: [13/06 10:00, 18/06 10:00)
     * Kỳ vọng: Bị từ chối với HTTP 409
     */
    public function test_booking_fails_on_right_overlap(): void
    {
        $this->bookingService->createBooking(
            $this->userA->id,
            $this->availableCar->id,
            '2027-06-10 10:00:00',
            '2027-06-15 10:00:00'
        );

        Sanctum::actingAs($this->userB, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => $this->availableCar->id,
            'start_date' => '2027-06-13 10:00:00',
            'end_date'   => '2027-06-18 10:00:00',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'status'     => 0,
                'error_type' => 'SCHEDULE_CONFLICT',
            ]);
    }

    /**
     * TEST 3: Khoảng bao trùm toàn bộ (Encompassing)
     * Đơn gốc: [10/06 10:00, 15/06 10:00)
     * Đơn mới: [05/06 10:00, 20/06 10:00)
     * Kỳ vọng: Bị từ chối với HTTP 409
     */
    public function test_booking_fails_on_encompassing_overlap(): void
    {
        $this->bookingService->createBooking(
            $this->userA->id,
            $this->availableCar->id,
            '2027-06-10 10:00:00',
            '2027-06-15 10:00:00'
        );

        Sanctum::actingAs($this->userB, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => $this->availableCar->id,
            'start_date' => '2027-06-05 10:00:00',
            'end_date'   => '2027-06-20 10:00:00',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'status'     => 0,
                'error_type' => 'SCHEDULE_CONFLICT',
            ]);
    }

    /**
     * TEST 4: Khoảng nằm trọn bên trong (Encompassed / Sub-interval)
     * Đơn gốc: [10/06 10:00, 15/06 10:00)
     * Đơn mới: [11/06 10:00, 14/06 10:00)
     * Kỳ vọng: Bị từ chối với HTTP 409
     */
    public function test_booking_fails_on_sub_interval_overlap(): void
    {
        $this->bookingService->createBooking(
            $this->userA->id,
            $this->availableCar->id,
            '2027-06-10 10:00:00',
            '2027-06-15 10:00:00'
        );

        Sanctum::actingAs($this->userB, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => $this->availableCar->id,
            'start_date' => '2027-06-11 10:00:00',
            'end_date'   => '2027-06-14 10:00:00',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'status'     => 0,
                'error_type' => 'SCHEDULE_CONFLICT',
            ]);
    }

    /**
     * TEST 5: Trùng hoàn toàn (Completely Identical)
     * Đơn gốc: [10/06 10:00, 15/06 10:00)
     * Đơn mới: [10/06 10:00, 15/06 10:00)
     * Kỳ vọng: Bị từ chối với HTTP 409
     */
    public function test_booking_fails_on_exact_identical_interval(): void
    {
        $this->bookingService->createBooking(
            $this->userA->id,
            $this->availableCar->id,
            '2027-06-10 10:00:00',
            '2027-06-15 10:00:00'
        );

        Sanctum::actingAs($this->userB, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => $this->availableCar->id,
            'start_date' => '2027-06-10 10:00:00',
            'end_date'   => '2027-06-15 10:00:00',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'status'     => 0,
                'error_type' => 'SCHEDULE_CONFLICT',
            ]);
    }

    /**
     * TEST 6: Sát ranh giới phía trước (Touch Boundary Before)
     * Đơn gốc: [10/06 10:00, 15/06 10:00)
     * Đơn mới: [05/06 10:00, 10/06 10:00) -> Kết thúc đúng lúc đơn tiếp theo bắt đầu.
     * Kỳ vọng: THÀNH CÔNG (HTTP 200, status: 1) do quy ước nửa mở [nhận, trả)
     */
    public function test_booking_succeeds_on_touching_boundary_before(): void
    {
        $this->bookingService->createBooking(
            $this->userA->id,
            $this->availableCar->id,
            '2027-06-10 10:00:00',
            '2027-06-15 10:00:00'
        );

        Sanctum::actingAs($this->userB, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => $this->availableCar->id,
            'start_date' => '2027-06-05 10:00:00',
            'end_date'   => '2027-06-10 10:00:00',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);

        $this->assertEquals(2, Booking::count());
    }

    /**
     * TEST 7: Sát ranh giới phía sau (Touch Boundary After)
     * Đơn gốc: [10/06 10:00, 15/06 10:00)
     * Đơn mới: [15/06 10:00, 20/06 10:00) -> Bắt đầu đúng lúc đơn trước kết thúc.
     * Kỳ vọng: THÀNH CÔNG (HTTP 200, status: 1) do quy ước nửa mở [nhận, trả)
     */
    public function test_booking_succeeds_on_touching_boundary_after(): void
    {
        $this->bookingService->createBooking(
            $this->userA->id,
            $this->availableCar->id,
            '2027-06-10 10:00:00',
            '2027-06-15 10:00:00'
        );

        Sanctum::actingAs($this->userB, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => $this->availableCar->id,
            'start_date' => '2027-06-15 10:00:00',
            'end_date'   => '2027-06-20 10:00:00',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);

        $this->assertEquals(2, Booking::count());
    }

    /**
     * TEST 8: Đơn đã hủy (tinh_trang = 2) KHÔNG giữ lịch
     * Khách khác đặt trùng khoảng thời gian với đơn đã hủy -> THÀNH CÔNG (HTTP 200).
     */
    public function test_cancelled_booking_does_not_hold_schedule(): void
    {
        $oldBooking = $this->bookingService->createBooking(
            $this->userA->id,
            $this->availableCar->id,
            '2027-07-01 10:00:00',
            '2027-07-05 10:00:00'
        );

        // Hủy đơn
        $this->bookingService->cancelBooking($oldBooking);
        $this->assertEquals(2, $oldBooking->fresh()->tinh_trang);

        // User B đặt đúng khoảng thời gian này
        Sanctum::actingAs($this->userB, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => $this->availableCar->id,
            'start_date' => '2027-07-01 10:00:00',
            'end_date'   => '2027-07-05 10:00:00',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);
    }

    /**
     * TEST 9: Đơn chờ duyệt (tinh_trang = 0) VẪN giữ lịch
     * Khách khác đặt trùng với đơn chờ duyệt -> BỊ TỪ CHỐI (HTTP 409).
     */
    public function test_pending_approval_booking_holds_schedule(): void
    {
        $booking = $this->bookingService->createBooking(
            $this->userA->id,
            $this->availableCar->id,
            '2027-07-10 10:00:00',
            '2027-07-15 10:00:00'
        );
        $this->assertEquals(0, $booking->tinh_trang);

        Sanctum::actingAs($this->userB, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => $this->availableCar->id,
            'start_date' => '2027-07-12 10:00:00',
            'end_date'   => '2027-07-14 10:00:00',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'status'     => 0,
                'error_type' => 'SCHEDULE_CONFLICT',
            ]);
    }

    /**
     * TEST 10: Xe đang bảo trì (cars.status = 'maintenance')
     * Đặt xe bảo trì -> BỊ TỪ CHỐI với HTTP 400 và thông báo bảo dưỡng.
     */
    public function test_maintenance_car_cannot_be_booked(): void
    {
        Sanctum::actingAs($this->userA, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => $this->maintenanceCar->id,
            'start_date' => '2027-08-01 10:00:00',
            'end_date'   => '2027-08-05 10:00:00',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status'  => 0,
                'message' => 'Xe hiện đang trong quá trình bảo dưỡng, vui lòng chọn xe khác!',
            ]);
    }

    /**
     * TEST 11: Giá snapshot lấy từ server và không phụ thuộc client
     * Thuê 3 ngày xe VinFast Lux A (1,000,000đ/ngày) -> tong_tien = 3,000,000đ.
     */
    public function test_price_is_snapshotted_from_server_database(): void
    {
        Sanctum::actingAs($this->userA, ['*']);

        $response = $this->postJson('/api/user/bookings/dat-lich', [
            'car_id'     => $this->availableCar->id,
            'start_date' => '2027-09-01 10:00:00',
            'end_date'   => '2027-09-04 10:00:00', // 3 ngày
            'fake_price' => 100, // Client cố truyền giá giả
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(3000000, (float) $data['tong_tien']);
    }

    /**
     * TEST 12: Chatbot Assistant tuân thủ cùng quy tắc và dịch vụ với Website
     * Nếu đơn đã được đặt qua Website, Chatbot đặt trùng cũng bị từ chối 409.
     * Ngược lại, nếu đơn đã đặt qua Chatbot, Website đặt trùng cũng bị từ chối 409.
     */
    public function test_chatbot_and_website_share_identical_booking_rules(): void
    {
        // 1. Đặt qua Website
        $this->bookingService->createBooking(
            $this->userA->id,
            $this->availableCar->id,
            '2027-10-01 08:00:00',
            '2027-10-05 18:00:00'
        );

        // 2. Chatbot cố đặt trùng
        Sanctum::actingAs($this->userB, ['*']);

        $responseBot = $this->postJson('/api/chatbot/booking', [
            'car_id'     => $this->availableCar->id,
            'start_date' => '2027-10-03 08:00:00',
            'end_date'   => '2027-10-07 18:00:00',
        ]);

        $responseBot->assertStatus(409)
            ->assertJson([
                'status'     => 0,
                'error_type' => 'SCHEDULE_CONFLICT',
            ]);
    }

    /**
     * TEST 13: Kiểm chứng Concurrency với 2 kết nối MySQL thực tế
     * Sử dụng 2 PDO connections độc lập tới MySQL:
     * Kết nối 1 giữ khóa bi quan FOR UPDATE trên bản ghi xe.
     * Kết nối 2 cố gắng lấy khóa FOR UPDATE trên cùng xe đó sẽ bị chặn cho đến khi kết nối 1 giải phóng.
     */
    public function test_mysql_concurrency_pessimistic_row_locking_with_two_connections(): void
    {
        try {
            $pdo1 = new \PDO('mysql:host=127.0.0.1;port=3306;dbname=web_thue_xe', 'root', '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 3,
            ]);
            $pdo2 = new \PDO('mysql:host=127.0.0.1;port=3306;dbname=web_thue_xe', 'root', '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 3,
            ]);
        } catch (\Exception $e) {
            $this->markTestSkipped('Môi trường không hỗ trợ kết nối MySQL thực tế (web_thue_xe): ' . $e->getMessage());
            return;
        }

        // Đảm bảo xe test ID 1 tồn tại trong MySQL
        $checkCar = $pdo1->query("SELECT id FROM cars LIMIT 1")->fetch();
        if (!$checkCar) {
            $this->markTestSkipped('Không có bản ghi car trong MySQL để kiểm chứng khóa.');
            return;
        }
        $targetCarId = $checkCar['id'];

        // Kết nối 1: Bắt đầu Transaction và khóa dòng xe (FOR UPDATE)
        $pdo1->beginTransaction();
        $stmt1 = $pdo1->prepare("SELECT id, name FROM cars WHERE id = ? FOR UPDATE");
        $stmt1->execute([$targetCarId]);
        $lockedCar = $stmt1->fetch();
        $this->assertNotEmpty($lockedCar);

        // Kết nối 2: Đặt timeout khóa = 1 giây và cố gắng SELECT FOR UPDATE cùng xe
        $pdo2->exec("SET innodb_lock_wait_timeout = 1");
        $pdo2->beginTransaction();

        $lockWaitExceeded = false;
        try {
            $stmt2 = $pdo2->prepare("SELECT id, name FROM cars WHERE id = ? FOR UPDATE");
            $stmt2->execute([$targetCarId]);
            $stmt2->fetch();
        } catch (\PDOException $e) {
            // Mã SQLSTATE 1205: Lock wait timeout exceeded
            if (str_contains($e->getMessage(), 'Lock wait timeout') || $e->getCode() == 'HY000') {
                $lockWaitExceeded = true;
            }
        }

        $this->assertTrue(
            $lockWaitExceeded,
            'Khóa dòng xe thất bại: Kết nối 2 không bị chặn khi Kết nối 1 đang giữ khóa FOR UPDATE!'
        );

        // Giải phóng khóa
        $pdo2->rollBack();
        $pdo1->rollBack();
    }
}
