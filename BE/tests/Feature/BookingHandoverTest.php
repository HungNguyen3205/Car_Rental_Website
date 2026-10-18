<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\BookingHandoverRecord;
use App\Models\BookingSurcharge;
use App\Models\BookingTransaction;
use App\Models\Car;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingHandoverTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected User $customerA;
    protected User $customerB;
    protected Car $car;
    protected Booking $bookingConfirmed;
    protected Booking $bookingPending;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'ho_ten'        => 'Admin Vận Hành',
            'email'         => 'admin.ops@example.com',
            'so_dien_thoai' => '0901234567',
            'password'      => bcrypt('password'),
            'tinh_trang'    => 1,
        ]);

        $this->customerA = User::create([
            'ho_ten'        => 'Nguyễn Văn Khách A',
            'email'         => 'khacha@example.com',
            'so_dien_thoai' => '0912345678',
            'password'      => bcrypt('password'),
            'is_active'     => 1,
        ]);

        $this->customerB = User::create([
            'ho_ten'        => 'Trần Thị Khách B',
            'email'         => 'khachb@example.com',
            'so_dien_thoai' => '0987654321',
            'password'      => bcrypt('password'),
            'is_active'     => 1,
        ]);

        $this->car = Car::create([
            'name'          => 'VinFast VF 8 Plus',
            'price_per_day' => 1500000,
            'current_km'    => 12000,
            'type'          => 'SUV',
            'fuel_type'     => 'electric',
            'status'        => 'available',
        ]);

        $this->bookingConfirmed = Booking::create([
            'user_id'       => $this->customerA->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HANDOVER_CONFIRMED_01',
            'start_date'    => Carbon::now()->subDay()->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'tong_tien'     => 4500000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED, // 1: Đã duyệt
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);

        $this->bookingPending = Booking::create([
            'user_id'       => $this->customerA->id,
            'car_id'        => $this->car->id,
            'ma_dat_xe'     => 'HANDOVER_PENDING_02',
            'start_date'    => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(7)->format('Y-m-d H:i:s'),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_PENDING, // 0: Chờ duyệt
            'is_thanh_toan' => Booking::PAYMENT_UNPAID,
        ]);
    }

    /**
     * Test 1: Admin tạo biên bản bàn giao xe hợp lệ khi đơn ở trạng thái Đã duyệt (STATUS_CONFIRMED).
     * Đơn tự động chuyển sang Đang thuê (STATUS_IN_PROGRESS) và xe cập nhật số km.
     */
    public function test_admin_can_create_delivery_handover_when_confirmed(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson(
            "/api/admin/bookings/{$this->bookingConfirmed->id}/handovers",
            [
                'type'                  => 'delivery',
                'current_km'            => 12500,
                'fuel_or_battery_level' => 95,
                'exterior_status'       => 'Sơn xe bóng đẹp, vết trầy nhẹ cản sau 2cm.',
                'interior_status'       => 'Nội thất sạch sẽ, đã khử khuẩn.',
                'notes'                 => 'Giao xe tại Showroom Hà Nội.',
            ]
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);

        $this->assertDatabaseHas('booking_handover_records', [
            'booking_id'            => $this->bookingConfirmed->id,
            'type'                  => 'delivery',
            'current_km'            => 12500,
            'fuel_or_battery_level' => 95,
            'staff_id'              => $this->admin->id,
        ]);

        // Đơn tự động chuyển trạng thái 3 (STATUS_IN_PROGRESS)
        $this->assertEquals(Booking::STATUS_IN_PROGRESS, $this->bookingConfirmed->fresh()->tinh_trang);

        // Xe cập nhật số km hiện tại
        $this->assertEquals(12500, $this->car->fresh()->current_km);
    }

    /**
     * Test 2: Chặn tạo biên bản bàn giao xe khi đơn chưa ở trạng thái Đã duyệt.
     */
    public function test_delivery_fails_if_booking_not_confirmed(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson(
            "/api/admin/bookings/{$this->bookingPending->id}/handovers",
            [
                'type'                  => 'delivery',
                'current_km'            => 12000,
                'fuel_or_battery_level' => 100,
            ]
        );

        $response->assertStatus(422);
        $this->assertEquals(Booking::STATUS_PENDING, $this->bookingPending->fresh()->tinh_trang);
    }

    /**
     * Test 3: Chặn thao tác trùng lặp (Idempotency) - không cho tạo 2 biên bản bàn giao cho 1 đơn.
     */
    public function test_duplicate_delivery_handover_is_rejected(): void
    {
        // Lập lần 1
        $this->actingAs($this->admin, 'admin')->postJson(
            "/api/admin/bookings/{$this->bookingConfirmed->id}/handovers",
            [
                'type'                  => 'delivery',
                'current_km'            => 12000,
                'fuel_or_battery_level' => 90,
            ]
        )->assertStatus(200);

        // Lập lần 2
        $resDup = $this->actingAs($this->admin, 'admin')->postJson(
            "/api/admin/bookings/{$this->bookingConfirmed->id}/handovers",
            [
                'type'                  => 'delivery',
                'current_km'            => 12000,
                'fuel_or_battery_level' => 90,
            ]
        );

        $resDup->assertStatus(422);
    }

    /**
     * Test 4: Chặn nhận trả xe nếu đơn chưa được giao hoặc chưa ở trạng thái Đang thuê.
     */
    public function test_return_handover_fails_if_not_in_progress(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->postJson(
            "/api/admin/bookings/{$this->bookingConfirmed->id}/handovers",
            [
                'type'                  => 'return',
                'current_km'            => 12800,
                'fuel_or_battery_level' => 80,
            ]
        );

        $response->assertStatus(422);
    }

    /**
     * Test 5: Không cho nhận trả với số km nhỏ hơn lúc giao nếu không có lý do điều chỉnh.
     */
    public function test_return_handover_rejects_lower_odometer_without_adjustment_reason(): void
    {
        // 1. Tạo biên bản giao xe (12.500 km)
        $this->actingAs($this->admin, 'admin')->postJson(
            "/api/admin/bookings/{$this->bookingConfirmed->id}/handovers",
            [
                'type'                  => 'delivery',
                'current_km'            => 12500,
                'fuel_or_battery_level' => 90,
            ]
        )->assertStatus(200);

        // 2. Nhận trả xe với 12.000 km (nhỏ hơn lúc giao) và không có lý do điều chỉnh -> Chặn 422
        $resInvalid = $this->actingAs($this->admin, 'admin')->postJson(
            "/api/admin/bookings/{$this->bookingConfirmed->id}/handovers",
            [
                'type'                  => 'return',
                'current_km'            => 12000, // < 12500
                'fuel_or_battery_level' => 80,
            ]
        );

        $resInvalid->assertStatus(422);
        $this->assertStringContainsString('không được nhỏ hơn', $resInvalid->json('message'));
    }

    /**
     * Test 6: Nhận trả xe với số km nhỏ hơn lúc giao ĐƯỢC CHẤP NHẬN nếu có quy trình điều chỉnh và lý do hợp lệ.
     */
    public function test_return_handover_succeeds_with_valid_odometer_adjustment(): void
    {
        // 1. Giao xe 12.500 km
        $this->actingAs($this->admin, 'admin')->postJson(
            "/api/admin/bookings/{$this->bookingConfirmed->id}/handovers",
            [
                'type'                  => 'delivery',
                'current_km'            => 12500,
                'fuel_or_battery_level' => 90,
            ]
        )->assertStatus(200);

        // 2. Nhận trả xe với 500 km kèm quy trình điều chỉnh do thay đồng hồ táp-lô
        $resAdjusted = $this->actingAs($this->admin, 'admin')->postJson(
            "/api/admin/bookings/{$this->bookingConfirmed->id}/handovers",
            [
                'type'                       => 'return',
                'current_km'                 => 500,
                'fuel_or_battery_level'      => 85,
                'is_odometer_adjusted'       => true,
                'odometer_adjustment_reason' => 'Đã thay mới cụm đồng hồ táp-lô tại VinFast An Thái do lỗi hiển thị.',
            ]
        );

        $resAdjusted->assertStatus(200);
        $this->assertEquals(Booking::STATUS_RETURNED, $this->bookingConfirmed->fresh()->tinh_trang);
        $this->assertEquals(500, $this->car->fresh()->current_km);
    }

    /**
     * Test 7: Nhận trả xe bình thường, tính phụ phí và khấu trừ vào tiền cọc bảo đảm.
     */
    public function test_return_handover_calculates_surcharges_and_can_deduct_deposit(): void
    {
        // 1. Nộp tiền cọc bảo đảm 5.000.000đ cho đơn
        BookingTransaction::create([
            'booking_id'       => $this->bookingConfirmed->id,
            'type'             => BookingTransaction::TYPE_DEPOSIT_COLLECTION,
            'amount'           => 5000000,
            'status'           => BookingTransaction::STATUS_CONFIRMED,
            'method'           => 'bank_transfer',
            'created_by'       => $this->admin->id,
            'transaction_time' => Carbon::now(),
        ]);

        // 2. Giao xe (12.500 km, 90% pin)
        $this->actingAs($this->admin, 'admin')->postJson(
            "/api/admin/bookings/{$this->bookingConfirmed->id}/handovers",
            [
                'type'                  => 'delivery',
                'current_km'            => 12500,
                'fuel_or_battery_level' => 90,
            ]
        )->assertStatus(200);

        // 3. Nhận trả xe với phụ phí vệ sinh 300.000đ và phụ phí trễ giờ 200.000đ (có khấu trừ cọc)
        $resReturn = $this->actingAs($this->admin, 'admin')->postJson(
            "/api/admin/bookings/{$this->bookingConfirmed->id}/handovers",
            [
                'type'                  => 'return',
                'current_km'            => 12900, // Đã chạy 400km
                'fuel_or_battery_level' => 60,   // Thiếu 30% pin
                'surcharges'            => [
                    [
                        'type'                     => BookingSurcharge::TYPE_CLEANING,
                        'amount'                   => 300000,
                        'reason'                   => 'Vệ sinh nội thất dính bùn đất và có mùi hải sản.',
                        'is_deducted_from_deposit' => true,
                    ],
                    [
                        'type'                     => BookingSurcharge::TYPE_LATE_RETURN,
                        'amount'                   => 200000,
                        'reason'                   => 'Trả xe trễ 2 giờ so với hợp đồng.',
                        'is_deducted_from_deposit' => true,
                    ]
                ]
            ]
        );

        $resReturn->assertStatus(200);
        $this->assertEquals(Booking::STATUS_RETURNED, $this->bookingConfirmed->fresh()->tinh_trang);

        // Đã lưu 2 phụ phí
        $this->assertDatabaseCount('booking_surcharges', 2);

        // Đã tự động ghi nhận 2 giao dịch khấu trừ cọc (deposit_deduction) vào sổ cái booking_transactions
        $deductions = BookingTransaction::where('booking_id', $this->bookingConfirmed->id)
            ->where('type', BookingTransaction::TYPE_DEPOSIT_DEDUCTION)
            ->get();
        $this->assertCount(2, $deductions);
        $this->assertEquals(500000, $deductions->sum('amount')); // 300k + 200k

        // Số dư cọc đang giữ còn lại: 5.000.000 - 500.000 = 4.500.000đ
        $summary = $this->bookingConfirmed->fresh()->getFinancialSummary();
        $this->assertEquals(4500000, $summary['deposit_held_amount']);
    }

    /**
     * Test 8: Tải ảnh biên bản lên thư mục riêng tư và kiểm tra phân quyền truy cập ảnh.
     */
    public function test_private_handover_image_upload_and_access_control(): void
    {
        $file = UploadedFile::fake()->image('dashboard_front.jpg', 800, 600)->size(1500); // 1.5MB

        $res = $this->actingAs($this->admin, 'admin')->post(
            "/api/admin/bookings/{$this->bookingConfirmed->id}/handovers",
            [
                'type'                  => 'delivery',
                'current_km'            => 12500,
                'fuel_or_battery_level' => 90,
                'images'                => [$file],
            ]
        );

        $res->assertStatus(200);
        $record = BookingHandoverRecord::where('booking_id', $this->bookingConfirmed->id)->first();
        $this->assertNotEmpty($record->images);
        $savedFilename = $record->images[0];

        // 1. Admin được quyền xem ảnh
        Sanctum::actingAs($this->admin, ['*'], 'admin');
        $resAdmin = $this->get(
            "/api/bookings/{$this->bookingConfirmed->id}/handovers/images/{$savedFilename}"
        );
        $resAdmin->assertStatus(200);

        // 2. Khách hàng chủ đơn (Customer A) được quyền xem ảnh
        Sanctum::actingAs($this->customerA, ['*']);
        $resOwner = $this->get(
            "/api/bookings/{$this->bookingConfirmed->id}/handovers/images/{$savedFilename}"
        );
        $resOwner->assertStatus(200);

        // 3. Khách hàng khác (Customer B) bị chặn 404/403
        Sanctum::actingAs($this->customerB, ['*']);
        $resStranger = $this->get(
            "/api/bookings/{$this->bookingConfirmed->id}/handovers/images/{$savedFilename}"
        );
        $resStranger->assertStatus(404);

        // 4. File quá cỡ (> 5MB) bị chặn
        $giantFile = UploadedFile::fake()->image('giant_image.png')->size(6000); // 6MB
        $resOver = $this->actingAs($this->admin, 'admin')->post(
            "/api/admin/bookings/{$this->bookingPending->id}/handovers",
            [
                'type'                  => 'delivery',
                'current_km'            => 12000,
                'fuel_or_battery_level' => 90,
                'images'                => [$giantFile],
            ]
        );
        $resOver->assertStatus(422);
    }

    /**
     * Test 9: Khách hàng tự xác nhận biên bản của chính mình không bịa đặt chữ ký.
     */
    public function test_customer_can_confirm_handover_without_fabricating_signature(): void
    {
        // Tạo biên bản giao xe bởi admin
        $record = BookingHandoverRecord::create([
            'booking_id'            => $this->bookingConfirmed->id,
            'type'                  => 'delivery',
            'handover_time'         => Carbon::now(),
            'staff_id'              => $this->admin->id,
            'current_km'            => 12500,
            'fuel_type'             => 'electric',
            'fuel_or_battery_level' => 95,
        ]);

        // Khách B cố tình xác nhận biên bản của Khách A -> Chặn 403
        Sanctum::actingAs($this->customerB, ['*']);
        $this->postJson(
            "/api/user/bookings/{$this->bookingConfirmed->id}/handovers/confirm",
            [
                'type'  => 'delivery',
                'notes' => 'Tôi muốn hack xác nhận',
            ]
        )->assertStatus(403);

        // Khách A tự xác nhận biên bản của chính mình -> Thành công
        Sanctum::actingAs($this->customerA, ['*']);
        $resConfirm = $this->postJson(
            "/api/user/bookings/{$this->bookingConfirmed->id}/handovers/confirm",
            [
                'type'  => 'delivery',
                'notes' => 'Tôi đã nhận xe đầy đủ phụ kiện và kiểm tra táp-lô 95% pin.',
            ]
        );

        $resConfirm->assertStatus(200);
        $freshRecord = $record->fresh();
        $this->assertTrue($freshRecord->customer_confirmed);
        $this->assertEquals(BookingHandoverRecord::CONFIRM_ACCOUNT, $freshRecord->customer_confirmation_method);
        $this->assertNotNull($freshRecord->customer_confirmed_at);
        $this->assertEquals('Tôi đã nhận xe đầy đủ phụ kiện và kiểm tra táp-lô 95% pin.', $freshRecord->customer_notes);
    }

    /**
     * Test 10: API đối chiếu so sánh bàn giao - nhận trả hiển thị đầy đủ chênh lệch và tuyên bố miễn trừ.
     */
    public function test_handover_comparison_diff_shows_accurate_metrics(): void
    {
        // 1. Tạo Delivery (12.000 km, 90% pin)
        BookingHandoverRecord::create([
            'booking_id'            => $this->bookingConfirmed->id,
            'type'                  => 'delivery',
            'handover_time'         => Carbon::parse($this->bookingConfirmed->start_date),
            'staff_id'              => $this->admin->id,
            'current_km'            => 12000,
            'fuel_type'             => 'electric',
            'fuel_or_battery_level' => 90,
        ]);

        // 2. Tạo Return (12.450 km, 75% pin)
        BookingHandoverRecord::create([
            'booking_id'            => $this->bookingConfirmed->id,
            'type'                  => 'return',
            'handover_time'         => Carbon::parse($this->bookingConfirmed->end_date)->addHours(2), // Trễ 2 giờ
            'staff_id'              => $this->admin->id,
            'current_km'            => 12450,
            'fuel_type'             => 'electric',
            'fuel_or_battery_level' => 75,
        ]);

        $res = $this->actingAs($this->admin, 'admin')->getJson("/api/admin/bookings/{$this->bookingConfirmed->id}/handovers");
        $res->assertStatus(200);

        $diff = $res->json('data.diff');
        $this->assertEquals(450, $diff['km_driven']); // 12450 - 12000
        $this->assertEquals(-15, $diff['fuel_or_battery_diff']); // 75 - 90
        $this->assertEquals(2, $diff['late_hours']);
        $this->assertTrue($diff['is_late']);

        // Kiểm tra tuyên bố pháp lý đúng bản chất biên bản
        $this->assertStringContainsString('không thay thế cho văn bản chứng thực pháp lý', $res->json('data.disclaimer'));
    }
}
