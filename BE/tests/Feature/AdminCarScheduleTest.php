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

class AdminCarScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Admin $admin;
    protected Car $car1;
    protected Car $car2;
    protected CarCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CarCategory::create([
            'name'        => 'SUV 7 chỗ',
            'slug'        => 'suv-7-cho',
            'description' => 'Xe gầm cao gia đình',
        ]);

        $this->car1 = Car::create([
            'category_id'   => $this->category->id,
            'name'          => 'Ford Everest Titanium',
            'price_per_day' => 1600000,
            'status'        => 'available',
            'city'          => 'Đà Nẵng',
            'image'         => 'everest.png',
        ]);

        $this->car2 = Car::create([
            'category_id'   => $this->category->id,
            'name'          => 'Hyundai SantaFe Premium',
            'price_per_day' => 1500000,
            'status'        => 'maintenance', // Xe đang bảo trì tổng quát
            'city'          => 'Hà Nội',
            'image'         => 'santafe.png',
        ]);

        $this->customer = User::create([
            'ho_ten'        => 'Nguyễn Văn Khách',
            'email'         => 'khach_calendar@example.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0901234567',
            'is_active'     => 1,
        ]);

        $this->admin = Admin::create([
            'ho_ten'        => 'Điều Phối Viên Đội Xe',
            'email'         => 'fleet_manager@example.com',
            'password'      => Hash::make('admin123456'),
            'so_dien_thoai' => '0988112233',
            'vai_tro'       => 'admin',
            'is_active'     => 1,
        ]);
    }

    /**
     * Test 1: Kiểm tra phân quyền truy cập endpoint lịch xe.
     * - Khách vãng lai / chưa đăng nhập -> 401
     * - Tài khoản User thường (Sanctum) -> 403 (không có guard admin)
     * - Tài khoản Admin đang hoạt động -> 200
     */
    public function test_access_authorization_for_car_schedule_api()
    {
        // 1. Unauthenticated request
        $resGuest = $this->getJson('/api/admin/calendar/schedule');
        $resGuest->assertStatus(401);

        // 2. Regular customer calling admin schedule
        Sanctum::actingAs($this->customer, ['*']);
        $resCustomer = $this->getJson('/api/admin/calendar/schedule');
        $resCustomer->assertStatus(401); // Guard admin rejects customer

        // 3. Admin calling endpoint
        Sanctum::actingAs($this->admin, ['*'], 'admin');
        $resAdmin = $this->getJson('/api/admin/calendar/schedule');
        $resAdmin->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'cars',
                    'pagination',
                    'window',
                    'stats',
                    'categories',
                ]
            ]);
    }

    /**
     * Test 2: Kiểm tra chính xác logic giao thoa khoảng thời gian [start_date, end_date):
     * Cửa sổ xem: từ 2026-05-10 00:00:00 đến 2026-05-17 23:59:59 (1 tuần)
     * - Đơn 1: Trước cửa sổ (2026-05-01 đến 2026-05-08) -> KHÔNG lấy
     * - Đơn 2: Chạm ranh giới bắt đầu (2026-05-08 đến 2026-05-10 00:00:00) -> KHÔNG lấy (nửa mở)
     * - Đơn 3: Bắt đầu trước và kết thúc trong tuần (2026-05-08 đến 2026-05-12 12:00:00) -> CÓ lấy
     * - Đơn 4: Nằm trọn vẹn trong tuần (2026-05-13 đến 2026-05-15 10:00:00) -> CÓ lấy
     * - Đơn 5: Bắt đầu trong tuần và kết thúc sau tuần (2026-05-16 đến 2026-05-20) -> CÓ lấy
     * - Đơn 6: Chạm ranh giới kết thúc (2026-05-17 23:59:59 đến 2026-05-22) -> KHÔNG lấy
     * - Đơn 7: Sau cửa sổ (2026-05-25 đến 2026-05-30) -> KHÔNG lấy
     */
    public function test_half_open_date_interval_intersection_and_boundaries()
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $viewStart = '2026-05-10 00:00:00';
        $viewEnd = '2026-05-17 23:59:59';

        // Đơn 1: Trước cửa sổ
        Booking::create([
            'ma_dat_xe'  => 'BK-BEFORE',
            'user_id'    => $this->customer->id,
            'car_id'     => $this->car1->id,
            'start_date' => '2026-05-01 08:00:00',
            'end_date'   => '2026-05-08 17:00:00',
            'tong_tien'  => 5000000,
            'tinh_trang' => Booking::STATUS_CONFIRMED,
        ]);

        // Đơn 2: Chạm ranh giới bắt đầu đúng lúc viewStart (trả lúc 2026-05-10 00:00:00)
        Booking::create([
            'ma_dat_xe'  => 'BK-TOUCH-START',
            'user_id'    => $this->customer->id,
            'car_id'     => $this->car1->id,
            'start_date' => '2026-05-05 08:00:00',
            'end_date'   => '2026-05-10 00:00:00',
            'tong_tien'  => 4000000,
            'tinh_trang' => Booking::STATUS_CONFIRMED,
        ]);

        // Đơn 3: Bắt đầu trước và kết thúc trong tuần
        Booking::create([
            'ma_dat_xe'  => 'BK-OVERLAP-LEFT',
            'user_id'    => $this->customer->id,
            'car_id'     => $this->car1->id,
            'start_date' => '2026-05-08 08:00:00',
            'end_date'   => '2026-05-12 12:00:00',
            'tong_tien'  => 6000000,
            'tinh_trang' => Booking::STATUS_CONFIRMED,
        ]);

        // Đơn 4: Nằm trọn vẹn trong tuần
        Booking::create([
            'ma_dat_xe'  => 'BK-INSIDE-WEEK',
            'user_id'    => $this->customer->id,
            'car_id'     => $this->car1->id,
            'start_date' => '2026-05-13 08:00:00',
            'end_date'   => '2026-05-15 10:00:00',
            'tong_tien'  => 3200000,
            'tinh_trang' => Booking::STATUS_PENDING,
        ]);

        // Đơn 5: Bắt đầu trong tuần và kết thúc sau tuần
        Booking::create([
            'ma_dat_xe'  => 'BK-OVERLAP-RIGHT',
            'user_id'    => $this->customer->id,
            'car_id'     => $this->car1->id,
            'start_date' => '2026-05-16 09:00:00',
            'end_date'   => '2026-05-20 18:00:00',
            'tong_tien'  => 6400000,
            'tinh_trang' => Booking::STATUS_CONFIRMED,
        ]);

        // Đơn 6: Nhận xe đúng lúc kết thúc cửa sổ xem (2026-05-17 23:59:59)
        Booking::create([
            'ma_dat_xe'  => 'BK-TOUCH-END',
            'user_id'    => $this->customer->id,
            'car_id'     => $this->car1->id,
            'start_date' => '2026-05-17 23:59:59',
            'end_date'   => '2026-05-22 12:00:00',
            'tong_tien'  => 7000000,
            'tinh_trang' => Booking::STATUS_CONFIRMED,
        ]);

        // Đơn 7: Hoàn toàn sau cửa sổ xem
        Booking::create([
            'ma_dat_xe'  => 'BK-AFTER',
            'user_id'    => $this->customer->id,
            'car_id'     => $this->car1->id,
            'start_date' => '2026-05-25 08:00:00',
            'end_date'   => '2026-05-30 18:00:00',
            'tong_tien'  => 8000000,
            'tinh_trang' => Booking::STATUS_CONFIRMED,
        ]);

        $res = $this->getJson("/api/admin/calendar/schedule?start_date=2026-05-10&end_date=2026-05-17&car_id={$this->car1->id}");
        $res->assertStatus(200);

        $cars = $res->json('data.cars');
        $this->assertCount(1, $cars);

        $bookings = $cars[0]['bookings'];
        $bookingCodes = collect($bookings)->pluck('ma_dat_xe')->toArray();

        // Chỉ đúng 3 đơn giao thoa được lấy: BK-OVERLAP-LEFT, BK-INSIDE-WEEK, BK-OVERLAP-RIGHT
        $this->assertCount(3, $bookingCodes);
        $this->assertContains('BK-OVERLAP-LEFT', $bookingCodes);
        $this->assertContains('BK-INSIDE-WEEK', $bookingCodes);
        $this->assertContains('BK-OVERLAP-RIGHT', $bookingCodes);

        // Các đơn ngoài ranh giới tuyệt đối không được trả về
        $this->assertNotContains('BK-BEFORE', $bookingCodes);
        $this->assertNotContains('BK-TOUCH-START', $bookingCodes);
        $this->assertNotContains('BK-TOUCH-END', $bookingCodes);
        $this->assertNotContains('BK-AFTER', $bookingCodes);
    }

    /**
     * Test 3: Đơn kéo dài qua nhiều ngày và xuyên tháng (Cross-month bookings).
     * Khi xem tháng 5 hoặc tháng 6, đơn đều phải hiển thị đầy đủ.
     */
    public function test_booking_spanning_across_multiple_days_and_months()
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        // Đơn xuyên từ cuối tháng 5 sang đầu tháng 6
        Booking::create([
            'ma_dat_xe'  => 'BK-CROSS-MONTH',
            'user_id'    => $this->customer->id,
            'car_id'     => $this->car1->id,
            'start_date' => '2026-05-28 08:00:00',
            'end_date'   => '2026-06-05 18:00:00',
            'tong_tien'  => 12800000,
            'tinh_trang' => Booking::STATUS_CONFIRMED,
        ]);

        // 1. Xem toàn bộ tháng 5
        $resMay = $this->getJson("/api/admin/calendar/schedule?start_date=2026-05-01&end_date=2026-05-31&car_id={$this->car1->id}");
        $resMay->assertStatus(200);
        $mayBookings = collect($resMay->json('data.cars.0.bookings'))->pluck('ma_dat_xe');
        $this->assertContains('BK-CROSS-MONTH', $mayBookings);

        // 2. Xem toàn bộ tháng 6
        $resJune = $this->getJson("/api/admin/calendar/schedule?start_date=2026-06-01&end_date=2026-06-30&car_id={$this->car1->id}");
        $resJune->assertStatus(200);
        $juneBookings = collect($resJune->json('data.cars.0.bookings'))->pluck('ma_dat_xe');
        $this->assertContains('BK-CROSS-MONTH', $juneBookings);
    }

    /**
     * Test 4: Phân biệt trạng thái booking ngữ nghĩa và xe bảo trì tổng quát.
     * - Chờ duyệt -> pending, holds_schedule = true
     * - Đã hủy -> cancelled, holds_schedule = false
     * - Xe bảo trì -> is_maintenance = true, có thông báo bảo trì, không bịa mốc ngày
     */
    public function test_status_classification_and_maintenance_handling()
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        // Booking chờ duyệt (pending)
        Booking::create([
            'ma_dat_xe'  => 'BK-PENDING-CHECK',
            'user_id'    => $this->customer->id,
            'car_id'     => $this->car1->id,
            'start_date' => Carbon::now()->addDays(2)->format('Y-m-d 08:00:00'),
            'end_date'   => Carbon::now()->addDays(4)->format('Y-m-d 18:00:00'),
            'tong_tien'  => 3200000,
            'tinh_trang' => Booking::STATUS_PENDING,
        ]);

        // Booking đã hủy (cancelled)
        Booking::create([
            'ma_dat_xe'  => 'BK-CANCELLED-CHECK',
            'user_id'    => $this->customer->id,
            'car_id'     => $this->car1->id,
            'start_date' => Carbon::now()->addDays(5)->format('Y-m-d 08:00:00'),
            'end_date'   => Carbon::now()->addDays(6)->format('Y-m-d 18:00:00'),
            'tong_tien'  => 1600000,
            'tinh_trang' => Booking::STATUS_CANCELLED,
        ]);

        $windowStart = Carbon::now()->format('Y-m-d');
        $windowEnd = Carbon::now()->addDays(10)->format('Y-m-d');

        $res = $this->getJson("/api/admin/calendar/schedule?start_date={$windowStart}&end_date={$windowEnd}");
        $res->assertStatus(200);

        $cars = collect($res->json('data.cars'))->keyBy('id');

        // Xe 1 (Available)
        $this->assertFalse($cars[$this->car1->id]['is_maintenance']);
        $this->assertNull($cars[$this->car1->id]['maintenance_notice']);

        $car1Bookings = collect($cars[$this->car1->id]['bookings'])->keyBy('ma_dat_xe');
        $this->assertEquals('pending', $car1Bookings['BK-PENDING-CHECK']['status_key']);
        $this->assertTrue($car1Bookings['BK-PENDING-CHECK']['holds_schedule']);

        $this->assertEquals('cancelled', $car1Bookings['BK-CANCELLED-CHECK']['status_key']);
        $this->assertFalse($car1Bookings['BK-CANCELLED-CHECK']['holds_schedule']);

        // Xe 2 (Maintenance): Hiển thị đúng giới hạn hiện tại, không bịa ngày bắt đầu/kết thúc bảo trì
        $this->assertTrue($cars[$this->car2->id]['is_maintenance']);
        $this->assertStringContainsString('bảo dưỡng tổng quát', $cars[$this->car2->id]['maintenance_notice']);
    }
}
