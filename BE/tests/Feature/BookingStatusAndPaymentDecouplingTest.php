<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\User;
use App\Services\Payment\BookingPaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingStatusAndPaymentDecouplingTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Admin $admin;
    protected Car $car;
    protected CarCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'ho_ten'        => 'Nguyễn Văn Khách',
            'email'         => 'khach@test.com',
            'so_dien_thoai' => '0901234567',
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
            'name'        => 'SUV Điện Cao Cấp',
            'description' => 'Dòng xe thể thao đa dụng',
        ]);

        $this->car = Car::create([
            'name'          => 'VinFast VF8 Plus',
            'category_id'   => $this->category->id,
            'price_per_day' => 1200000,
            'status'        => 1,
            'city'          => 'Hà Nội',
        ]);
    }

    /**
     * TEST 1: Duyệt đơn chưa thanh toán -> Chỉ đổi trạng thái thuê (tinh_trang = 1),
     * trạng thái thanh toán VẪN LÀ CHƯA THANH TOÁN (is_thanh_toan = 0).
     */
    public function test_approving_unpaid_booking_updates_rental_status_and_leaves_payment_unpaid(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_TEST01',
            'start_date'    => Carbon::now()->addDays(2),
            'end_date'      => Carbon::now()->addDays(5),
            'tong_tien'     => 3600000,
            'tinh_trang'    => Booking::STATUS_PENDING, // 0: Chờ duyệt
            'is_thanh_toan' => Booking::PAYMENT_UNPAID, // 0: Chưa thanh toán
        ]);

        // Admin duyệt đơn thuê xe (chỉ gửi id và tinh_trang = 1)
        $response = $this->postJson('/api/admin/bookings/status', [
            'id'         => $booking->id,
            'tinh_trang' => Booking::STATUS_CONFIRMED,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 1,
            ]);

        $fresh = $booking->fresh();

        // Trạng thái thuê xe đã được duyệt
        $this->assertEquals(Booking::STATUS_CONFIRMED, $fresh->tinh_trang);

        // NGUYÊN TẮC: Thanh toán tuyệt đối giữ nguyên 0 (chưa thanh toán)
        $this->assertEquals(Booking::PAYMENT_UNPAID, $fresh->is_thanh_toan);
        $this->assertNull($fresh->payment_confirmed_at);
        $this->assertNull($fresh->payment_confirmed_by);
    }

    /**
     * TEST 2: Xác nhận thanh toán -> KHÔNG tự động duyệt lịch thuê hay hoàn tất chuyến (tinh_trang giữ nguyên).
     */
    public function test_confirming_payment_does_not_auto_approve_or_complete_trip(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_TEST02',
            'start_date'    => Carbon::now()->addDays(3),
            'end_date'      => Carbon::now()->addDays(6),
            'tong_tien'     => 3600000,
            'tinh_trang'    => Booking::STATUS_PENDING, // 0: Đang chờ duyệt xe
            'is_thanh_toan' => Booking::PAYMENT_UNPAID, // 0: Chưa thanh toán
        ]);

        // Admin xác nhận thanh toán độc lập sau khi đối soát sao kê
        $response = $this->postJson('/api/admin/bookings/confirm-payment', [
            'id'           => $booking->id,
            'payment_note' => 'Khớp giao dịch ngân hàng VCB 3.600.000đ',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 1,
            ]);

        $fresh = $booking->fresh();

        // Trạng thái thanh toán cập nhật thành 1
        $this->assertEquals(Booking::PAYMENT_PAID, $fresh->is_thanh_toan);
        $this->assertNotNull($fresh->payment_confirmed_at);
        $this->assertEquals($this->admin->id, $fresh->payment_confirmed_by);

        // NGUYÊN TẮC: Trạng thái thuê xe tinh_trang KHÔNG bị tự động kích hoạt hay hoàn tất chuyến
        $this->assertEquals(Booking::STATUS_PENDING, $fresh->tinh_trang, 'Quy tắc vi phạm: Xác nhận thanh toán đã tự động duyệt đơn thuê!');
    }

    /**
     * TEST 3: Hủy đơn đã thu tiền -> KHÔNG tự coi là đã hoàn tiền;
     * Giữ nguyên is_thanh_toan = 1 và gắn cờ cần xử lý tài chính theo quy định.
     */
    public function test_cancelling_paid_booking_does_not_auto_refund_or_wipe_payment(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car->id,
            'ma_dat_xe'            => 'HDTX_TEST03',
            'start_date'           => Carbon::now()->addDays(4),
            'end_date'             => Carbon::now()->addDays(7),
            'tong_tien'            => 3600000,
            'tinh_trang'           => Booking::STATUS_CONFIRMED, // 1: Đã duyệt
            'is_thanh_toan'        => Booking::PAYMENT_PAID,      // 1: Đã thanh toán
            'payment_confirmed_at' => Carbon::now(),
            'payment_confirmed_by' => $this->admin->id,
        ]);

        // Admin hủy đơn đặt xe đã thanh toán (bắt buộc kèm lý do hủy)
        $response = $this->postJson('/api/admin/bookings/status', [
            'id'            => $booking->id,
            'tinh_trang'    => Booking::STATUS_CANCELLED,
            'cancel_reason' => 'Khách hàng báo hủy qua hotline vì đổi lịch công tác',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'          => 1,
                'requires_refund' => true,
            ]);

        $this->assertStringContainsString('đối soát xử lý tài chính', $response->json('message'));

        $fresh = $booking->fresh();

        // 1. Trạng thái thuê chuyển sang Đã hủy (2)
        $this->assertEquals(Booking::STATUS_CANCELLED, $fresh->tinh_trang);

        // 2. NGUYÊN TẮC: is_thanh_toan vẫn là 1 (Không được giả mạo thành đã hoàn tiền hay xóa về 0)
        $this->assertEquals(Booking::PAYMENT_PAID, $fresh->is_thanh_toan);

        // 3. Service cung cấp thông tin chuẩn xác: paid_cancelled_requires_refund
        $paymentService = new BookingPaymentService();
        $statusInfo = $paymentService->getPaymentStatusInfo($fresh);

        $this->assertEquals('paid_cancelled_requires_refund', $statusInfo['code']);
        $this->assertTrue($statusInfo['requires_refund']);
        $this->assertStringContainsString('Cần xử lý hoàn tiền', $statusInfo['label']);
    }

    /**
     * TEST 4: Endpoint cập nhật trạng thái thuê cấm nhận trường tài chính is_thanh_toan.
     */
    public function test_status_endpoint_strictly_prohibits_is_thanh_toan_parameter(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_TEST04',
            'start_date'    => Carbon::now()->addDays(5),
            'end_date'      => Carbon::now()->addDays(8),
            'tong_tien'     => 3600000,
            'tinh_trang'    => Booking::STATUS_PENDING,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        // Cố tình gửi is_thanh_toan = 1 lên endpoint cập nhật trạng thái thuê xe
        $response = $this->postJson('/api/admin/bookings/status', [
            'id'            => $booking->id,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_thanh_toan']);

        $this->assertStringContainsString('không được phép thay đổi trạng thái tài chính', $response->json('errors.is_thanh_toan.0'));

        // Kiểm tra cơ sở dữ liệu: không bị sửa
        $fresh = $booking->fresh();
        $this->assertEquals(Booking::STATUS_PENDING, $fresh->tinh_trang);
        $this->assertEquals(Booking::PAYMENT_UNPAID, $fresh->is_thanh_toan);
    }

    /**
     * TEST 5: Đơn đã duyệt (1) không được chuyển ngược về chờ duyệt (0).
     */
    public function test_cannot_revert_confirmed_booking_back_to_pending(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_TEST05',
            'start_date'    => Carbon::now()->addDays(5),
            'end_date'      => Carbon::now()->addDays(8),
            'tong_tien'     => 3600000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $response = $this->postJson('/api/admin/bookings/status', [
            'id'         => $booking->id,
            'tinh_trang' => Booking::STATUS_PENDING,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 0,
            ]);

        $this->assertStringContainsString('không thể chuyển ngược về trạng thái chờ duyệt', $response->json('message'));
        $this->assertEquals(Booking::STATUS_CONFIRMED, $booking->fresh()->tinh_trang);
    }

    /**
     * TEST 6: Đơn đã hủy (2) không được phép kích hoạt lại.
     */
    public function test_cannot_reactivate_cancelled_booking(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_TEST06',
            'start_date'    => Carbon::now()->addDays(5),
            'end_date'      => Carbon::now()->addDays(8),
            'tong_tien'     => 3600000,
            'tinh_trang'    => Booking::STATUS_CANCELLED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $response = $this->postJson('/api/admin/bookings/status', [
            'id'         => $booking->id,
            'tinh_trang' => Booking::STATUS_CONFIRMED,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 0,
            ]);

        $this->assertStringContainsString('đã hủy vì lịch xe đã được giải phóng', $response->json('message'));
    }

    /**
     * TEST 7: Danh sách đối chiếu tài chính (Reconciliation List) trả về các mục nghi vấn mà không tự ý sửa dữ liệu.
     */
    public function test_reconciliation_endpoint_audits_discrepancies_without_mutating_data(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        // Bản ghi 1: Đơn hủy đã thu tiền
        $bookingRefund = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'RECON_REFUND',
            'start_date'    => Carbon::now()->addDays(1),
            'end_date'      => Carbon::now()->addDays(3),
            'tong_tien'     => 2400000,
            'tinh_trang'    => Booking::STATUS_CANCELLED,
            'is_thanh_toan' => Booking::PAYMENT_PAID,
        ]);

        // Bản ghi 2: Đơn có biên lai nhưng chưa duyệt
        $bookingReceipt = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'RECON_RECEIPT',
            'start_date'    => Carbon::now()->addDays(3),
            'end_date'      => Carbon::now()->addDays(5),
            'tong_tien'     => 2400000,
            'tinh_trang'    => Booking::STATUS_PENDING,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
            'bill_image'    => 'receipts/test.jpg',
        ]);

        // Bản ghi 3: Đã thanh toán nhưng thiếu kiểm toán
        $bookingMissingAudit = Booking::create([
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car->id,
            'ma_dat_xe'            => 'RECON_NO_AUDIT',
            'start_date'           => Carbon::now()->addDays(6),
            'end_date'             => Carbon::now()->addDays(8),
            'tong_tien'            => 2400000,
            'tinh_trang'           => Booking::STATUS_CONFIRMED,
            'is_thanh_toan'        => Booking::PAYMENT_PAID,
            'payment_confirmed_at' => null, // Thiếu vết
            'payment_confirmed_by' => null,
        ]);

        $response = $this->getJson('/api/admin/bookings/reconciliation');

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 1,
                'summary' => [
                    'requires_refund_count' => 1,
                    'pending_receipt_count' => 1,
                    'missing_audit_count'   => 1,
                ]
            ]);

        // Đảm bảo dữ liệu cơ sở dữ liệu KHÔNG bị thay đổi tự động bởi suy đoán
        $this->assertEquals(Booking::STATUS_CANCELLED, $bookingRefund->fresh()->tinh_trang);
        $this->assertEquals(Booking::PAYMENT_PAID, $bookingRefund->fresh()->is_thanh_toan);
        $this->assertNull($bookingMissingAudit->fresh()->payment_confirmed_by);
    }

    /**
     * TEST 8: Khách hàng thường cố gọi endpoint trạng thái hoặc đối chiếu của Admin bị chặn ngay.
     */
    public function test_regular_customer_cannot_call_status_or_reconciliation_endpoints(): void
    {
        Sanctum::actingAs($this->customer, ['*']);

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_TEST08',
            'start_date'    => Carbon::now()->addDays(5),
            'end_date'      => Carbon::now()->addDays(8),
            'tong_tien'     => 3600000,
            'tinh_trang'    => Booking::STATUS_PENDING,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $statusResponse = $this->postJson('/api/admin/bookings/status', [
            'id'         => $booking->id,
            'tinh_trang' => Booking::STATUS_CONFIRMED,
        ]);
        $statusResponse->assertStatus(401);

        $reconResponse = $this->getJson('/api/admin/bookings/reconciliation');
        $reconResponse->assertStatus(401);
    }
}
