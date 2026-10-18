<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\BookingTransaction;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\User;
use App\Services\Revenue\RevenueCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class BookingDepositAndMultiPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected User $otherCustomer;
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

        $this->otherCustomer = User::create([
            'ho_ten'        => 'Lê Thị Khách Khác',
            'email'         => 'khachkhac@test.com',
            'so_dien_thoai' => '0909999888',
            'password'      => bcrypt('password123'),
        ]);

        $this->admin = Admin::create([
            'ho_ten'        => 'Trần Quản Trị Kế Toán',
            'email'         => 'ketoan@test.com',
            'so_dien_thoai' => '0987654321',
            'password'      => bcrypt('admin123'),
            'tinh_trang'    => 1,
        ]);

        $this->category = CarCategory::create([
            'name'        => 'Sedan Tiêu Chuẩn',
            'description' => 'Xe thử nghiệm thanh toán nhiều đợt',
        ]);

        $this->car = Car::create([
            'name'          => 'VinFast VF7 Plus',
            'category_id'   => $this->category->id,
            'price_per_day' => 1000000,
            'status'        => 1,
            'city'          => 'Hà Nội',
        ]);
    }

    /**
     * TEST 1: Thanh toán một phần (Partial payment / trả trước đợt 1):
     * Đơn 3,000,000 VND -> Thu đợt 1: 1,000,000 VND -> is_thanh_toan = 2 (PAYMENT_PARTIAL).
     */
    public function test_partial_rental_payment(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_MULTI_01',
            'start_date'    => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID, // 0
        ]);

        $response = $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'           => BookingTransaction::TYPE_RENTAL_PAYMENT,
            'amount'         => 1000000,
            'method'         => 'bank_transfer',
            'reference_code' => 'UNC_DOT1_1000K',
            'note'           => 'Khách đặt cọc trước 30% phí thuê xe',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 1,
            ]);

        $fresh = $booking->fresh();
        // Trạng thái cập nhật thành đã thanh toán 1 phần (2)
        $this->assertEquals(Booking::PAYMENT_PARTIAL, $fresh->is_thanh_toan);

        $summary = $fresh->getFinancialSummary();
        $this->assertEquals(3000000, $summary['total_rent_fee']);
        $this->assertEquals(1000000, $summary['paid_rent_amount']);
        $this->assertEquals(2000000, $summary['remaining_rent_amount']);
        $this->assertEquals('partial', $summary['payment_status_code']);
    }

    /**
     * TEST 2: Thanh toán đủ nhiều lần (Multi-installment full payment):
     * Đơn 3,000,000 VND -> Thu đợt 1: 1,000,000 VND -> Thu đợt 2: 2,000,000 VND -> is_thanh_toan = 1 (PAYMENT_PAID).
     */
    public function test_full_rental_payment_across_installments(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_MULTI_02',
            'start_date'    => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        // Đợt 1: 1,000,000 VND
        $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'   => BookingTransaction::TYPE_RENTAL_PAYMENT,
            'amount' => 1000000,
        ])->assertStatus(200);

        // Đợt 2: 2,000,000 VND
        $res2 = $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'   => BookingTransaction::TYPE_RENTAL_PAYMENT,
            'amount' => 2000000,
            'note'   => 'Khách thanh toán 70% còn lại khi nhận xe',
        ]);
        $res2->assertStatus(200);

        $fresh = $booking->fresh();
        // Đã trả đủ 100% -> is_thanh_toan = 1
        $this->assertEquals(Booking::PAYMENT_PAID, $fresh->is_thanh_toan);

        $summary = $fresh->getFinancialSummary();
        $this->assertEquals(3000000, $summary['paid_rent_amount']);
        $this->assertEquals(0, $summary['remaining_rent_amount']);
        $this->assertEquals('paid_full', $summary['payment_status_code']);
    }

    /**
     * TEST 3: Tính lũy đẳng (Idempotency): Gửi lặp cùng idempotency_key không tạo giao dịch trùng lặp.
     */
    public function test_idempotent_duplicate_transaction_collection(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_IDEMP_TX',
            'start_date'    => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $payload = [
            'type'            => BookingTransaction::TYPE_RENTAL_PAYMENT,
            'amount'          => 1500000,
            'idempotency_key' => 'IDEMP_KEY_ABC_123',
            'note'            => 'Giao dịch lần 1',
        ];

        // Gửi lần 1
        $res1 = $this->postJson("/api/admin/bookings/{$booking->id}/transactions", $payload);
        $res1->assertStatus(200);
        $this->assertFalse($res1->json('is_idempotent'));

        // Gửi lại lần 2 với cùng key (do mạng chập chờn hoặc double click)
        $res2 = $this->postJson("/api/admin/bookings/{$booking->id}/transactions", $payload);
        $res2->assertStatus(200);
        $this->assertTrue($res2->json('is_idempotent'));

        // Kiểm tra trong database chỉ có đúng 1 bản ghi giao dịch
        $this->assertEquals(1, BookingTransaction::where('booking_id', $booking->id)->count());
        $summary = $booking->fresh()->getFinancialSummary();
        $this->assertEquals(1500000, $summary['paid_rent_amount']);
    }

    /**
     * TEST 4: Hoàn tiền thuê không được vượt quá số tiền thuê thực tế đã thu.
     */
    public function test_refund_rental_payment_exceeding_paid_amount_is_rejected(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_REFUND_FAIL',
            'start_date'    => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        // Đã đóng 1,000,000 VND
        $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'   => BookingTransaction::TYPE_RENTAL_PAYMENT,
            'amount' => 1000000,
        ])->assertStatus(200);

        // Cố tình hoàn 1,500,000 VND (> 1,000,000 VND) -> Phải bị từ chối
        $resFail = $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'   => BookingTransaction::TYPE_RENTAL_REFUND,
            'amount' => 1500000,
            'note'   => 'Hoàn vượt mức',
        ]);
        $resFail->assertStatus(422);
        $this->assertStringContainsString('không được vượt quá số tiền thuê thực tế đã thu', $resFail->json('message'));

        // Hoàn 500,000 VND (<= 1,000,000 VND) -> Hợp lệ
        $resOk = $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'   => BookingTransaction::TYPE_RENTAL_REFUND,
            'amount' => 500000,
            'note'   => 'Hoàn 50% tiền cọc đặt chỗ do khách xin đổi ngày',
        ]);
        $resOk->assertStatus(200);

        $summary = $booking->fresh()->getFinancialSummary();
        $this->assertEquals(500000, $summary['paid_rent_amount']);
    }

    /**
     * TEST 5: Thu tiền cọc bảo đảm hoàn lại (Deposit Collection) và Hoàn cọc (Deposit Refund).
     * Phân định rõ tiền cọc bảo đảm, không tính nhầm là doanh thu thuê xe.
     */
    public function test_security_deposit_collection_and_full_refund(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_DEP_01',
            'start_date'    => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_IN_PROGRESS,
            'is_thanh_toan' => Booking::PAYMENT_PAID,
        ]);

        // 1. Thu tiền cọc bảo đảm tài sản khi giao xe: 10,000,000 VND
        $resCollect = $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'   => BookingTransaction::TYPE_DEPOSIT_COLLECTION,
            'amount' => 10000000,
            'method' => 'cash',
            'note'   => 'Nhận tiền mặt cọc xe 10 triệu khi bàn giao xe',
        ]);
        $resCollect->assertStatus(200);

        $summary1 = $booking->fresh()->getFinancialSummary();
        $this->assertEquals(10000000, $summary1['deposit_collected_amount']);
        $this->assertEquals(10000000, $summary1['deposit_held_amount']);
        $this->assertEquals(0, $summary1['deposit_refunded_amount']);

        // 2. Hoàn cọc toàn bộ khi nhận xe nguyên vẹn: 10,000,000 VND
        $resRefund = $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'   => BookingTransaction::TYPE_DEPOSIT_REFUND,
            'amount' => 10000000,
            'method' => 'bank_transfer',
            'note'   => 'Hoàn trả lại 100% cọc cho khách qua số tài khoản',
        ]);
        $resRefund->assertStatus(200);

        $summary2 = $booking->fresh()->getFinancialSummary();
        $this->assertEquals(10000000, $summary2['deposit_refunded_amount']);
        $this->assertEquals(0, $summary2['deposit_held_amount']);
    }

    /**
     * TEST 6: Hoàn cọc vượt quá số cọc đang giữ bị từ chối.
     */
    public function test_refund_deposit_exceeding_held_deposit_is_rejected(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_DEP_FAIL',
            'start_date'    => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        // Đang giữ cọc 5,000,000 VND
        $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'   => BookingTransaction::TYPE_DEPOSIT_COLLECTION,
            'amount' => 5000000,
        ])->assertStatus(200);

        // Cố tình hoàn 7,000,000 VND
        $response = $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'   => BookingTransaction::TYPE_DEPOSIT_REFUND,
            'amount' => 7000000,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('không được vượt quá số cọc bảo đảm đang giữ', $response->json('message'));
    }

    /**
     * TEST 7: Khấu trừ tiền cọc (Deposit Deduction):
     * - Bắt buộc phải có lý do (bồi thường trầy xước / phụ phí).
     * - Không được khấu trừ vượt quá số cọc đang giữ.
     * - Không trừ âm tùy ý.
     */
    public function test_deposit_deduction_requires_reason_and_cannot_exceed_held_deposit(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_DEDUCT_01',
            'start_date'    => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_RETURNED,
            'is_thanh_toan' => Booking::PAYMENT_PAID,
        ]);

        // Đang giữ cọc 5,000,000 VND
        $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'   => BookingTransaction::TYPE_DEPOSIT_COLLECTION,
            'amount' => 5000000,
        ])->assertStatus(200);

        // 1. Khấu trừ nhưng KHÔNG cung cấp lý do -> Lỗi 422
        $resNoReason = $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'   => BookingTransaction::TYPE_DEPOSIT_DEDUCTION,
            'amount' => 1500000,
        ]);
        $resNoReason->assertStatus(422)
            ->assertJsonValidationErrors(['deduction_reason']);

        // 2. Khấu trừ vượt quá số cọc đang giữ (6,000,000 > 5,000,000) -> Lỗi 422
        $resOver = $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'             => BookingTransaction::TYPE_DEPOSIT_DEDUCTION,
            'amount'           => 6000000,
            'deduction_reason' => 'Bồi thường thiệt hại',
        ]);
        $resOver->assertStatus(422);
        $this->assertStringContainsString('không được vượt quá số cọc bảo đảm đang giữ', $resOver->json('message'));

        // 3. Khấu trừ hợp lệ 1,500,000 VND kèm lý do
        $resOk = $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'             => BookingTransaction::TYPE_DEPOSIT_DEDUCTION,
            'amount'           => 1500000,
            'deduction_reason' => 'Trầy xước cản trước và bồi thường rửa xe dơ',
            'note'             => 'Biên bản nghiệm thu kỹ thuật số 01/BB-NT',
        ]);
        $resOk->assertStatus(200);

        $summary = $booking->fresh()->getFinancialSummary();
        $this->assertEquals(1500000, $summary['deposit_deducted_amount']);
        $this->assertEquals(3500000, $summary['deposit_held_amount']); // Còn giữ 3.5 triệu
    }

    /**
     * TEST 8: Tiền cọc bảo đảm hoàn lại TUYỆT ĐỐI KHÔNG TÍNH VÀO DOANH THU THUÊ XE.
     */
    public function test_security_deposit_is_strictly_excluded_from_rental_revenue(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        // Tạo 1 đơn có phí thuê 2,000,000 VND
        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_REV_EXCLUDE',
            'start_date'    => Carbon::now()->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2000000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        // Đóng phí thuê 2,000,000 VND
        $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'   => BookingTransaction::TYPE_RENTAL_PAYMENT,
            'amount' => 2000000,
        ])->assertStatus(200);

        // Khách cọc thêm 15,000,000 VND bảo đảm xe
        $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'   => BookingTransaction::TYPE_DEPOSIT_COLLECTION,
            'amount' => 15000000,
        ])->assertStatus(200);

        $revenueService = app(RevenueCalculationService::class);
        $summary = $revenueService->calculateRevenueSummary();

        // DOANH THU THỰC THU CHỈ LÀ 2,000,000 VND, TUYỆT ĐỐI KHÔNG PHẢI 17,000,000 VND!
        $this->assertEquals(2000000, $summary['valid_paid_revenue']);

        // Tiền cọc được bóc tách riêng vào quỹ ký quỹ giữ hộ
        $this->assertEquals(15000000, $summary['total_deposit_held']);
    }

    /**
     * TEST 9: Tính bất biến (Immutability): Cấm sửa hoặc xóa giao dịch đã xác nhận để che giấu lịch sử.
     */
    public function test_confirmed_transactions_are_immutable(): void
    {
        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_IMMUTABLE',
            'start_date'    => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $tx = BookingTransaction::create([
            'booking_id'       => $booking->id,
            'type'             => BookingTransaction::TYPE_RENTAL_PAYMENT,
            'amount'           => 1000000,
            'status'           => BookingTransaction::STATUS_CONFIRMED,
            'method'           => 'cash',
            'transaction_time' => Carbon::now(),
        ]);

        // Cố tình sửa số tiền trực tiếp -> Ném ngoại lệ
        $this->expectException(RuntimeException::class);
        $tx->amount = 2000000;
        $tx->save();
    }

    /**
     * TEST 10: Khách hàng chỉ xem được sổ giao dịch của chính mình (Bảo mật 404 cho đơn người khác).
     */
    public function test_customer_can_only_view_own_transactions(): void
    {
        $booking = Booking::create([
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HDTX_PRIVACY',
            'start_date'    => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        // Khách A xem đơn của chính mình -> 200 OK
        Sanctum::actingAs($this->customer, ['*']);
        $resA = $this->getJson("/api/user/bookings/{$booking->id}/transactions");
        $resA->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_rent_fee',
                    'paid_rent_amount',
                    'remaining_rent_amount',
                    'deposit_held_amount',
                ]
            ]);

        // Khách B cố tình tra cứu đơn của Khách A -> 404 Not Found
        Sanctum::actingAs($this->otherCustomer, ['*']);
        $resB = $this->getJson("/api/user/bookings/{$booking->id}/transactions");
        $resB->assertStatus(404);

        // Khách hàng không thể ghi nhận giao dịch tài chính (403 Forbidden)
        $resForbidden = $this->postJson("/api/admin/bookings/{$booking->id}/transactions", [
            'type'   => BookingTransaction::TYPE_RENTAL_PAYMENT,
            'amount' => 500000,
        ]);
        $this->assertTrue(in_array($resForbidden->status(), [401, 403], true));
    }
}
