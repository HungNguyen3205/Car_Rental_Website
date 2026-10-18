<?php

namespace Tests\Feature;

use App\Exceptions\CarBookingValidationException;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\User;
use App\Services\Booking\BookingLifecycleService;
use App\Services\Booking\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingLifecycleTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $otherCustomer;
    protected Admin $admin;
    protected Car $car;
    protected CarCategory $category;
    protected BookingLifecycleService $lifecycleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'ho_ten'        => 'Nguyễn Văn Khách',
            'email'         => 'khach@test.com',
            'so_dien_thoai' => '0901234567',
            'password'      => bcrypt('password123'),
        ]);

        $this->otherCustomer = User::create([
            'ho_ten'        => 'Lê Thị Khách Khác',
            'email'         => 'khachkhac@test.com',
            'so_dien_thoai' => '0909999888',
            'password'      => bcrypt('password123'),
        ]);

        $this->admin = Admin::create([
            'ho_ten'        => 'Trần Quản Trị',
            'email'         => 'admin@test.com',
            'so_dien_thoai' => '0987654321',
            'password'      => bcrypt('admin123'),
            'tinh_trang'    => 1,
        ]);

        $this->category = CarCategory::create([
            'name'        => 'Sedan Hạng C',
            'description' => 'Dòng xe thông minh tiết kiệm năng lượng',
        ]);

        $this->car = Car::create([
            'name'          => 'VinFast VF6 Plus',
            'category_id'   => $this->category->id,
            'price_per_day' => 850000,
            'status'        => 1,
            'city'          => 'Hà Nội',
        ]);

        $this->lifecycleService = app(BookingLifecycleService::class);
    }

    /**
     * TEST 1: Vòng đời tuần tự chuẩn qua Admin: 0 -> 1 -> 3 -> 4 -> 5
     * Ghi nhận đầy đủ audit log: người thao tác, thời điểm, ghi chú từng bước.
     */
    public function test_full_valid_admin_lifecycle_transitions_from_pending_to_completed(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_LIFE01',
            'start_date'    => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => Booking::STATUS_PENDING, // 0
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        // Bước 1: 0 (Pending) -> 1 (Confirmed)
        $res1 = $this->postJson('/api/admin/bookings/status', [
            'id'         => $booking->id,
            'tinh_trang' => Booking::STATUS_CONFIRMED,
        ]);
        $res1->assertStatus(200);
        $this->assertEquals(Booking::STATUS_CONFIRMED, $booking->fresh()->tinh_trang);

        // Bước 2: 1 (Confirmed) -> 3 (In Progress / Đã bàn giao xe)
        $res2 = $this->postJson('/api/admin/bookings/status', [
            'id'          => $booking->id,
            'tinh_trang'  => Booking::STATUS_IN_PROGRESS,
            'status_note' => 'Bàn giao xe chìa khóa số 1, ODO 12,500km, pin 95%',
        ]);
        $res2->assertStatus(200);
        $fresh2 = $booking->fresh();
        $this->assertEquals(Booking::STATUS_IN_PROGRESS, $fresh2->tinh_trang);
        $this->assertNotNull($fresh2->delivered_at);
        $this->assertEquals($this->admin->id, $fresh2->delivered_by);
        $this->assertStringContainsString('Bàn giao xe chìa khóa số 1', $fresh2->status_note);

        // Bước 3: 3 (In Progress) -> 4 (Returned / Đã nhận trả xe)
        $res3 = $this->postJson('/api/admin/bookings/status', [
            'id'          => $booking->id,
            'tinh_trang'  => Booking::STATUS_RETURNED,
            'status_note' => 'Nhận xe về bãi, ODO 12,850km, ngoại quan nguyên vẹn',
        ]);
        $res3->assertStatus(200);
        $fresh3 = $booking->fresh();
        $this->assertEquals(Booking::STATUS_RETURNED, $fresh3->tinh_trang);
        $this->assertNotNull($fresh3->returned_at);
        $this->assertEquals($this->admin->id, $fresh3->returned_by);
        $this->assertStringContainsString('Nhận xe về bãi', $fresh3->status_note);

        // Bước 4: 4 (Returned) -> 5 (Completed / Hoàn tất đóng đơn)
        $res4 = $this->postJson('/api/admin/bookings/status', [
            'id'          => $booking->id,
            'tinh_trang'  => Booking::STATUS_COMPLETED,
            'status_note' => 'Quyết toán hoàn tất và trả cọc cho khách',
        ]);
        $res4->assertStatus(200);
        $fresh4 = $booking->fresh();
        $this->assertEquals(Booking::STATUS_COMPLETED, $fresh4->tinh_trang);
        $this->assertNotNull($fresh4->completed_at);
        $this->assertEquals($this->admin->id, $fresh4->completed_by);
        $this->assertStringContainsString('Quyết toán hoàn tất', $fresh4->status_note);

        // Kiểm tra timeline 5 bước đều hoàn thành
        $timeline = $this->lifecycleService->getTimelineEvents($fresh4);
        $this->assertCount(5, $timeline);
        foreach ($timeline as $event) {
            $this->assertTrue($event['completed']);
        }
    }

    /**
     * TEST 2: Khách hàng được phép hủy đơn khi đang ở trạng thái Chờ xác nhận (0 -> 2).
     * Ghi nhận audit: cancelled_by_type = 'user', cancelled_by_id = user_id, lý do hủy.
     */
    public function test_customer_can_cancel_pending_booking_with_reason(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_LIFE02',
            'start_date'    => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => Booking::STATUS_PENDING,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $response = $this->postJson('/api/user/bookings/huy-lich', [
            'id'            => $booking->id,
            'cancel_reason' => 'Tôi có việc đột xuất nên phải dời chuyến công tác',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 1,
                'message' => 'Hủy đơn đặt xe thành công!',
            ]);

        $fresh = $booking->fresh();
        $this->assertEquals(Booking::STATUS_CANCELLED, $fresh->tinh_trang);
        $this->assertNotNull($fresh->cancelled_at);
        $this->assertEquals('user', $fresh->cancelled_by_type);
        $this->assertEquals($this->customer->id, $fresh->cancelled_by_id);
        $this->assertEquals('Tôi có việc đột xuất nên phải dời chuyến công tác', $fresh->cancel_reason);
    }

    /**
     * TEST 3: Khách hàng KHÔNG được phép hủy khi đơn đã được duyệt hoặc đang bàn giao.
     */
    public function test_customer_cannot_cancel_confirmed_or_in_progress_booking(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        // Đơn đã duyệt (1)
        $bookingConfirmed = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_LIFE03A',
            'start_date'    => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $res1 = $this->postJson('/api/user/bookings/huy-lich', [
            'id'            => $bookingConfirmed->id,
            'cancel_reason' => 'Muốn tự hủy',
        ]);

        $res1->assertStatus(400);
        $this->assertEquals(Booking::STATUS_CONFIRMED, $bookingConfirmed->fresh()->tinh_trang);

        // Đơn đang thuê (3)
        $bookingInProgress = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_LIFE03B',
            'start_date'    => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => Booking::STATUS_IN_PROGRESS,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $res2 = $this->postJson('/api/user/bookings/huy-lich', [
            'id'            => $bookingInProgress->id,
            'cancel_reason' => 'Muốn tự hủy khi đang đi xe',
        ]);

        $res2->assertStatus(400);
        $this->assertEquals(Booking::STATUS_IN_PROGRESS, $bookingInProgress->fresh()->tinh_trang);
    }

    /**
     * TEST 4: Khách hàng không thể hủy đơn của khách hàng khác (Bảo mật 404).
     */
    public function test_customer_cannot_cancel_another_customer_booking(): void
    {
        Sanctum::actingAs($this->otherCustomer, ['*']);

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_LIFE04',
            'start_date'    => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => Booking::STATUS_PENDING,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $response = $this->postJson('/api/user/bookings/huy-lich', [
            'id'            => $booking->id,
            'cancel_reason' => 'Hủy trộm',
        ]);

        $response->assertStatus(404);
        $this->assertEquals(Booking::STATUS_PENDING, $booking->fresh()->tinh_trang);
    }

    /**
     * TEST 5: Admin hủy đơn phải cung cấp lý do (Bắt buộc validation).
     */
    public function test_admin_cannot_cancel_booking_without_reason(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_LIFE05',
            'start_date'    => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        // Cố tình hủy nhưng không truyền cancel_reason
        $response = $this->postJson('/api/admin/bookings/status', [
            'id'         => $booking->id,
            'tinh_trang' => Booking::STATUS_CANCELLED,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cancel_reason']);

        $this->assertEquals(Booking::STATUS_CONFIRMED, $booking->fresh()->tinh_trang);
    }

    /**
     * TEST 6: Admin hủy đơn có lý do -> Lưu đầy đủ audit admin.
     */
    public function test_admin_can_cancel_confirmed_booking_with_mandatory_reason(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_LIFE06',
            'start_date'    => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $response = $this->postJson('/api/admin/bookings/status', [
            'id'            => $booking->id,
            'tinh_trang'    => Booking::STATUS_CANCELLED,
            'cancel_reason' => 'Khách hàng gọi điện xác nhận hủy do thời tiết mưa bão',
        ]);

        $response->assertStatus(200);
        $fresh = $booking->fresh();
        $this->assertEquals(Booking::STATUS_CANCELLED, $fresh->tinh_trang);
        $this->assertNotNull($fresh->cancelled_at);
        $this->assertEquals('admin', $fresh->cancelled_by_type);
        $this->assertEquals($this->admin->id, $fresh->cancelled_by_id);
        $this->assertEquals('Khách hàng gọi điện xác nhận hủy do thời tiết mưa bão', $fresh->cancel_reason);
    }

    /**
     * TEST 7: Chặn tất cả các bước nhảy cóc trái phép (Illegal step skipping).
     * 0 -> 3 (Cấm giao xe khi chưa duyệt)
     * 0 -> 5 (Cấm hoàn tất khi chưa giao & nhận trả)
     * 1 -> 5 (Cấm hoàn tất trực tiếp từ đã duyệt)
     * 3 -> 5 (Cấm hoàn tất trực tiếp khi chưa nhận trả xe)
     */
    public function test_illegal_skipping_transitions_are_blocked(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        // Case A: 0 -> 3
        $bookingPending = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_SKIP_A',
            'start_date'    => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => Booking::STATUS_PENDING,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $resA = $this->postJson('/api/admin/bookings/status', [
            'id'         => $bookingPending->id,
            'tinh_trang' => Booking::STATUS_IN_PROGRESS,
        ]);
        $resA->assertStatus(422);
        $this->assertEquals(Booking::STATUS_PENDING, $bookingPending->fresh()->tinh_trang);

        // Case B: 0 -> 5
        $resB = $this->postJson('/api/admin/bookings/status', [
            'id'         => $bookingPending->id,
            'tinh_trang' => Booking::STATUS_COMPLETED,
        ]);
        $resB->assertStatus(422);
        $this->assertStringContainsString('Không thể hoàn tất đơn đặt xe trước khi hoàn thành bàn giao và nhận trả', $resB->json('message'));

        // Case C: 1 -> 5
        $bookingConfirmed = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_SKIP_C',
            'start_date'    => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $resC = $this->postJson('/api/admin/bookings/status', [
            'id'         => $bookingConfirmed->id,
            'tinh_trang' => Booking::STATUS_COMPLETED,
        ]);
        $resC->assertStatus(422);

        // Case D: 3 -> 5 (Đang thuê không được nhảy cóc sang hoàn tất nếu chưa qua nhận trả 4)
        $bookingInProgress = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_SKIP_D',
            'start_date'    => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => Booking::STATUS_IN_PROGRESS,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $resD = $this->postJson('/api/admin/bookings/status', [
            'id'         => $bookingInProgress->id,
            'tinh_trang' => Booking::STATUS_COMPLETED,
        ]);
        $resD->assertStatus(422);
    }

    /**
     * TEST 8: Chặn chuyển ngược hoặc sửa trạng thái kết thúc (Terminal states).
     * 5 (Completed) không được đổi sang bất kỳ trạng thái nào khác.
     * 2 (Cancelled) không được kích hoạt lại.
     */
    public function test_illegal_terminal_state_changes_are_blocked(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        // Hoàn tất (5)
        $completedBooking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_TERM01',
            'start_date'    => Carbon::now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'tong_tien'     => 1700000,
            'tinh_trang'    => Booking::STATUS_COMPLETED,
            'is_thanh_toan' => Booking::PAYMENT_PAID,
        ]);

        $resCompleted = $this->postJson('/api/admin/bookings/status', [
            'id'         => $completedBooking->id,
            'tinh_trang' => Booking::STATUS_CONFIRMED,
        ]);
        $resCompleted->assertStatus(422);
        $this->assertStringContainsString('Đơn đặt xe đã hoàn tất đóng đơn thành công, không thể thay đổi trạng thái nữa', $resCompleted->json('message'));

        // Đã hủy (2)
        $cancelledBooking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_TERM02',
            'start_date'    => Carbon::now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'tong_tien'     => 1700000,
            'tinh_trang'    => Booking::STATUS_CANCELLED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $resCancelled = $this->postJson('/api/admin/bookings/status', [
            'id'         => $cancelledBooking->id,
            'tinh_trang' => Booking::STATUS_CONFIRMED,
        ]);
        $resCancelled->assertStatus(422);
        $this->assertStringContainsString('Không thể kích hoạt lại đơn đặt xe đã hủy', $resCancelled->json('message'));
    }

    /**
     * TEST 9: Tính lũy đẳng (Idempotency) - Gửi lại cùng trạng thái không báo lỗi.
     * Có thể cập nhật thêm ghi chú mà không làm thay đổi trạng thái hay tạo lỗi.
     */
    public function test_idempotent_transition_succeeds_without_error(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_IDEMP01',
            'start_date'    => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
            'status_note'   => 'Ghi chú ban đầu',
        ]);

        // Gửi lại cùng trạng thái CONFIRMED (1) kèm bổ sung ghi chú
        $response = $this->postJson('/api/admin/bookings/status', [
            'id'          => $booking->id,
            'tinh_trang'  => Booking::STATUS_CONFIRMED,
            'status_note' => 'Bổ sung ghi chú kiểm tra định kỳ',
        ]);

        $response->assertStatus(200);
        $fresh = $booking->fresh();
        $this->assertEquals(Booking::STATUS_CONFIRMED, $fresh->tinh_trang);
        $this->assertStringContainsString('Ghi chú ban đầu', $fresh->status_note);
        $this->assertStringContainsString('Bổ sung ghi chú kiểm tra định kỳ', $fresh->status_note);
    }

    /**
     * TEST 10: Quy tắc giữ/nhả lịch xe rõ ràng và tương thích BookingService::isCarAvailable:
     * - Trạng thái 0, 1, 3, 4, 5 đều giữ lịch xe (isHoldingSchedule() = true).
     * - Trạng thái 2 (Cancelled) nhả lịch xe (isHoldingSchedule() = false).
     */
    public function test_schedule_holding_and_releasing_lifecycle_rules(): void
    {
        $startDate = Carbon::now()->addDays(10)->format('Y-m-d H:i:s');
        $endDate   = Carbon::now()->addDays(14)->format('Y-m-d H:i:s');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_SCHED01',
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'tong_tien'     => 3400000,
            'tinh_trang'    => Booking::STATUS_PENDING,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $bookingService = app(BookingService::class);

        // 1. Khi ở trạng thái 0 (Pending): Xe không khả dụng cho khoảng ngày đó
        $this->assertTrue($booking->isHoldingSchedule());
        $this->assertFalse($bookingService->isCarAvailable($this->car->id, $startDate, $endDate));

        // 2. Chuyển sang 1 (Confirmed): Vẫn giữ lịch
        $booking->update(['tinh_trang' => Booking::STATUS_CONFIRMED]);
        $this->assertTrue($booking->fresh()->isHoldingSchedule());
        $this->assertFalse($bookingService->isCarAvailable($this->car->id, $startDate, $endDate));

        // 3. Chuyển sang 3 (In Progress): Vẫn giữ lịch
        $booking->update(['tinh_trang' => Booking::STATUS_IN_PROGRESS]);
        $this->assertTrue($booking->fresh()->isHoldingSchedule());
        $this->assertFalse($bookingService->isCarAvailable($this->car->id, $startDate, $endDate));

        // 4. Chuyển sang 4 (Returned): Vẫn giữ lịch
        $booking->update(['tinh_trang' => Booking::STATUS_RETURNED]);
        $this->assertTrue($booking->fresh()->isHoldingSchedule());
        $this->assertFalse($bookingService->isCarAvailable($this->car->id, $startDate, $endDate));

        // 5. Chuyển sang 2 (Cancelled): GIẢI PHÓNG LỊCH XE
        $booking->update([
            'tinh_trang'         => Booking::STATUS_CANCELLED,
            'cancelled_at'       => Carbon::now(),
            'cancelled_by_type'  => 'admin',
            'cancelled_by_id'    => $this->admin->id,
            'cancel_reason'      => 'Hủy theo yêu cầu',
        ]);
        $this->assertFalse($booking->fresh()->isHoldingSchedule());
        // Bây giờ xe đã khả dụng trở lại!
        $this->assertTrue($bookingService->isCarAvailable($this->car->id, $startDate, $endDate));
    }

    /**
     * TEST 11: Không xóa đơn để thay cho hủy (Yêu cầu nghiêm ngặt).
     * - Cấm xóa đơn đang hoạt động (tinh_trang != 2).
     * - Cấm xóa đơn đã thanh toán (is_thanh_toan = 1).
     * - Chỉ cho xóa đơn chưa thanh toán đã hủy hợp lệ.
     */
    public function test_delete_booking_prohibits_replacing_cancellation(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        // Đơn đang hoạt động (Pending)
        $activeBooking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_DEL01',
            'start_date'    => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(8)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => Booking::STATUS_PENDING,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $resActive = $this->postJson('/api/admin/bookings/delete', [
            'id' => $activeBooking->id,
        ]);
        $resActive->assertStatus(422);
        $this->assertStringContainsString('Không thể xóa đơn đặt xe đang hoạt động để thay thế cho việc hủy đơn', $resActive->json('message'));
        $this->assertDatabaseHas('bookings', ['id' => $activeBooking->id]);

        // Đơn đã hủy nhưng đã thu tiền
        $paidCancelledBooking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_DEL02',
            'start_date'    => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(8)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => Booking::STATUS_CANCELLED,
            'is_thanh_toan' => Booking::PAYMENT_PAID,
            'cancel_reason' => 'Khách hủy sau khi thanh toán',
        ]);

        $resPaid = $this->postJson('/api/admin/bookings/delete', [
            'id' => $paidCancelledBooking->id,
        ]);
        $resPaid->assertStatus(422);
        $this->assertStringContainsString('Không thể xóa đơn đặt xe đã có phát sinh thanh toán tài chính', $resPaid->json('message'));
        $this->assertDatabaseHas('bookings', ['id' => $paidCancelledBooking->id]);

        // Đơn đã hủy chưa thanh toán -> Được phép xóa dọn dẹp
        $unpaidCancelled = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_DEL03',
            'start_date'    => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(8)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => Booking::STATUS_CANCELLED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
            'cancel_reason' => 'Đơn rác test',
        ]);

        $resClean = $this->postJson('/api/admin/bookings/delete', [
            'id' => $unpaidCancelled->id,
        ]);
        $resClean->assertStatus(200);
        $this->assertDatabaseMissing('bookings', ['id' => $unpaidCancelled->id]);
    }

    /**
     * TEST 12: Đơn lịch sử (tinh_trang 0, 1, 2) vẫn tương thích và hiển thị chính xác
     * trên cả API Admin và API Khách hàng, có timeline và status_meta đầy đủ.
     */
    public function test_historical_legacy_bookings_compatibility_and_display(): void
    {
        // Tạo các bản ghi lịch sử cũ (không có các trường delivered_at, returned_at...)
        $legacyPending = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_LEGACY_0',
            'start_date'    => Carbon::now()->subDays(10)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->subDays(7)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => 0,
            'is_thanh_toan' => 0,
        ]);

        $legacyConfirmed = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_LEGACY_1',
            'start_date'    => Carbon::now()->subDays(5)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->subDays(2)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => 1,
            'is_thanh_toan' => 1,
        ]);

        $legacyCancelled = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_LEGACY_2',
            'start_date'    => Carbon::now()->subDays(1)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2550000,
            'tinh_trang'    => 2,
            'is_thanh_toan' => 0,
        ]);

        // 1. Kiểm tra API Khách hàng
        Sanctum::actingAs($this->customer, ['*']);
        $clientRes = $this->getJson('/api/user/bookings/data');
        $clientRes->assertStatus(200);
        $clientData = $clientRes->json('data');
        $this->assertNotEmpty($clientData);

        // Mỗi đơn đều có status_meta và timeline
        foreach ($clientData as $item) {
            $this->assertArrayHasKey('status_meta', $item);
            $this->assertArrayHasKey('timeline', $item);
            $this->assertNotEmpty($item['status_meta']['label']);
        }

        // 2. Kiểm tra API Admin
        Sanctum::actingAs($this->admin, ['*'], 'admin');
        $adminRes = $this->getJson('/api/admin/bookings/data');
        $adminRes->assertStatus(200);
        $adminData = $adminRes->json('data');
        $this->assertNotEmpty($adminData);

        foreach ($adminData as $item) {
            $this->assertArrayHasKey('next_actions', $item);
            $this->assertArrayHasKey('status_meta', $item);
        }
    }

    /**
     * TEST 13: Đơn hủy đã thu tiền vẫn giữ lịch sử thanh toán và yêu cầu xử lý hoàn tiền riêng.
     */
    public function test_paid_cancelled_booking_requires_refund_and_preserves_payment_record(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car->id,
            'ma_dat_xe'            => 'HDTX_REFUND01',
            'start_date'           => Carbon::now()->addDays(4)->format('Y-m-d H:i:s'),
            'end_date'             => Carbon::now()->addDays(7)->format('Y-m-d H:i:s'),
            'tong_tien'            => 2550000,
            'tinh_trang'           => Booking::STATUS_CONFIRMED,
            'is_thanh_toan'        => Booking::PAYMENT_PAID,
            'payment_confirmed_at' => Carbon::now(),
            'payment_confirmed_by' => $this->admin->id,
        ]);

        $response = $this->postJson('/api/admin/bookings/status', [
            'id'            => $booking->id,
            'tinh_trang'    => Booking::STATUS_CANCELLED,
            'cancel_reason' => 'Khách hàng đổi lịch sang tháng sau',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'          => 1,
                'requires_refund' => true,
            ]);

        $fresh = $booking->fresh();
        $this->assertEquals(Booking::STATUS_CANCELLED, $fresh->tinh_trang);
        // NGUYÊN TẮC: is_thanh_toan vẫn là 1 (Không được xóa thành 0)
        $this->assertEquals(Booking::PAYMENT_PAID, $fresh->is_thanh_toan);
        $this->assertNotNull($fresh->payment_confirmed_at);
        $this->assertEquals($this->admin->id, $fresh->payment_confirmed_by);
    }
}
