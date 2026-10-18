<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\User;
use App\Services\Revenue\RevenueCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RevenueStandardizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;
    protected Admin $admin;
    protected Car $car;
    protected RevenueCalculationService $revenueService;

    protected function setUp(): void
    {
        parent::setUp();

        $category = CarCategory::create([
            'name'        => 'Sedan Tiêu Chuẩn',
            'slug'        => 'sedan-tieu-chuan',
            'description' => 'Xe phục vụ chạy thử nghiệm doanh thu',
        ]);

        $this->car = Car::create([
            'category_id'   => $category->id,
            'name'          => 'Toyota Camry 2.5Q',
            'price_per_day' => 1500000,
            'status'        => 'available',
            'city'          => 'Hà Nội',
            'image'         => 'camry.png',
        ]);

        $this->customer = User::create([
            'ho_ten'        => 'Khách Hàng Test',
            'email'         => 'khach_revenue@example.com',
            'password'      => Hash::make('password123'),
            'so_dien_thoai' => '0912345678',
            'is_active'     => 1,
        ]);

        $this->admin = Admin::create([
            'ho_ten'        => 'Quản Trị Viên Kế Toán',
            'email'         => 'ketoan@example.com',
            'password'      => Hash::make('admin123456'),
            'so_dien_thoai' => '0988776655',
            'vai_tro'       => 'admin',
            'is_active'     => 1,
        ]);

        $this->revenueService = app(RevenueCalculationService::class);
    }

    /**
     * Test 1: Phân biệt rõ các chỉ số tài chính:
     * - Tổng giá trị đơn đặt (Total Booking Value / GMV)
     * - Giá trị đơn đã duyệt xe (Confirmed Booking Value)
     * - Giá trị đơn được đánh dấu đã thanh toán (Gross Paid Bookings)
     * - Doanh thu đơn hợp lệ đã thanh toán (Net Valid Paid Revenue - không tính đơn hủy)
     * - Tiền đơn hủy cần xử lý hoàn tiền (Paid but Cancelled / Refund Review Value)
     * - Công nợ đơn đã duyệt chưa thu tiền (Unpaid Confirmed)
     */
    public function test_distinguishes_gross_confirmed_paid_valid_and_refund_metrics()
    {
        // 1. Đơn chờ duyệt, chưa thanh toán: 1,000,000 VND
        Booking::create([
            'ma_dat_xe'            => 'BK-PENDING-UNPAID',
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car->id,
            'start_date'           => Carbon::now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_date'             => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'tong_tien'            => 1000000,
            'tinh_trang'           => Booking::STATUS_PENDING,
            'is_thanh_toan'        => 0,
            'created_at'           => Carbon::now(),
        ]);

        // 2. Đơn đã duyệt xe, chưa thanh toán: 2,000,000 VND
        Booking::create([
            'ma_dat_xe'            => 'BK-CONFIRMED-UNPAID',
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car->id,
            'start_date'           => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'end_date'             => Carbon::now()->addDays(4)->format('Y-m-d H:i:s'),
            'tong_tien'            => 2000000,
            'tinh_trang'           => Booking::STATUS_CONFIRMED,
            'is_thanh_toan'        => 0,
            'created_at'           => Carbon::now(),
        ]);

        // 3. Đơn đã duyệt xe VÀ đã thanh toán hợp lệ: 3,000,000 VND
        Booking::create([
            'ma_dat_xe'            => 'BK-CONFIRMED-PAID',
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car->id,
            'start_date'           => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'end_date'             => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
            'tong_tien'            => 3000000,
            'tinh_trang'           => Booking::STATUS_CONFIRMED,
            'is_thanh_toan'        => 1,
            'payment_confirmed_at' => Carbon::now(),
            'payment_confirmed_by' => $this->admin->id,
            'created_at'           => Carbon::now(),
        ]);

        // 4. Đơn đã thu tiền nhưng ĐÃ HỦY (Cần xử lý hoàn tiền): 4,000,000 VND
        Booking::create([
            'ma_dat_xe'            => 'BK-CANCELLED-PAID',
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car->id,
            'start_date'           => Carbon::now()->addDays(7)->format('Y-m-d H:i:s'),
            'end_date'             => Carbon::now()->addDays(8)->format('Y-m-d H:i:s'),
            'tong_tien'            => 4000000,
            'tinh_trang'           => Booking::STATUS_CANCELLED,
            'is_thanh_toan'        => 1,
            'payment_confirmed_at' => Carbon::now(),
            'payment_confirmed_by' => $this->admin->id,
            'created_at'           => Carbon::now(),
        ]);

        // 5. Đơn đã hủy và chưa thanh toán: 500,000 VND
        Booking::create([
            'ma_dat_xe'            => 'BK-CANCELLED-UNPAID',
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car->id,
            'start_date'           => Carbon::now()->addDays(9)->format('Y-m-d H:i:s'),
            'end_date'             => Carbon::now()->addDays(10)->format('Y-m-d H:i:s'),
            'tong_tien'            => 500000,
            'tinh_trang'           => Booking::STATUS_CANCELLED,
            'is_thanh_toan'        => 0,
            'created_at'           => Carbon::now(),
        ]);

        $summary = $this->revenueService->calculateRevenueSummary();

        // 1. Tổng giá trị đơn đặt: 1M + 2M + 3M + 4M + 0.5M = 10,500,000 VND
        $this->assertEquals(10500000, $summary['total_booking_value']);
        $this->assertEquals(5, $summary['total_bookings']);

        // 2. Giá trị đơn đã duyệt xe: 2M + 3M = 5,000,000 VND
        $this->assertEquals(5000000, $summary['confirmed_booking_value']);

        // 3. Giá trị đơn được đánh dấu đã thanh toán (Gross Paid): 3M + 4M = 7,000,000 VND
        $this->assertEquals(7000000, $summary['paid_booking_value']);

        // 4. Doanh thu đơn hợp lệ đã thanh toán (Net Valid Paid): 3,000,000 VND (Không tính đơn hủy 4M)
        $this->assertEquals(3000000, $summary['valid_paid_revenue']);

        // 5. Tiền đơn hủy cần xử lý hoàn tiền: 4,000,000 VND (Đơn 4)
        $this->assertEquals(4000000, $summary['refund_review_value']);

        // 6. Công nợ đơn đã duyệt chưa thu tiền: 2,000,000 VND (Đơn 2)
        $this->assertEquals(2000000, $summary['unpaid_confirmed_value']);
    }

    /**
     * Test 2: Dữ liệu lịch sử thiếu ngày thanh toán (payment_confirmed_at IS NULL):
     * - Phải được thống kê bóc tách trong legacy_missing_date.
     * - Tuyệt đối không bịa ngày hay gán tùy tiện vào biểu đồ tháng khi lọc theo payment_date.
     */
    public function test_legacy_paid_booking_without_payment_date_is_flagged_and_not_fabricated()
    {
        // Tạo đơn lịch sử trước khi có tính năng lưu audit date
        Booking::create([
            'ma_dat_xe'            => 'BK-LEGACY-01',
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car->id,
            'start_date'           => '2025-01-01 08:00:00',
            'end_date'             => '2025-01-02 08:00:00',
            'tong_tien'            => 1800000,
            'tinh_trang'           => Booking::STATUS_CONFIRMED,
            'is_thanh_toan'        => 1,
            'payment_confirmed_at' => null, // Dữ liệu cũ thiếu ngày thanh toán
            'payment_confirmed_by' => null,
            'created_at'           => Carbon::create(2025, 1, 1, 8, 0, 0),
        ]);

        $summary = $this->revenueService->calculateRevenueSummary();
        $this->assertEquals(1, $summary['legacy_missing_date_count']);
        $this->assertEquals(1800000, $summary['legacy_missing_date_value']);

        // Kiểm tra biểu đồ doanh thu theo payment_date
        $stats = $this->revenueService->getDashboardRevenueStats(['date_by' => 'payment_date', 'months' => 6]);
        $this->assertEquals(1, $stats['legacy_undated']['count']);
        $this->assertEquals(1800000, $stats['legacy_undated']['total']);

        // Đơn này không được cộng bừa vào các tháng gần nhất
        foreach ($stats['revenue_chart'] as $monthItem) {
            $this->assertEquals(0, $monthItem['valid_paid_revenue']);
        }
    }

    /**
     * Test 3: Không lấy created_at của đơn làm ngày thu tiền khi date_by = payment_date.
     * Đơn tạo từ 2 tháng trước nhưng tháng này mới thanh toán:
     * - date_by = payment_date: Doanh thu ghi nhận ở THÁNG NÀY.
     * - date_by = booking_date: Doanh thu ghi nhận ở 2 THÁNG TRƯỚC.
     */
    public function test_does_not_use_created_at_as_payment_date_when_date_by_is_payment_date()
    {
        $twoMonthsAgo = Carbon::now()->subMonths(2)->startOfMonth()->addDays(5);
        $thisMonth = Carbon::now()->startOfMonth()->addDays(2);

        $booking = Booking::create([
            'ma_dat_xe'            => 'BK-PAID-LATER',
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car->id,
            'start_date'           => $thisMonth->copy()->addDays(1)->format('Y-m-d H:i:s'),
            'end_date'             => $thisMonth->copy()->addDays(3)->format('Y-m-d H:i:s'),
            'tong_tien'            => 5000000,
            'tinh_trang'           => Booking::STATUS_CONFIRMED,
            'is_thanh_toan'        => 1,
            'payment_confirmed_at' => $thisMonth, // Thanh toán vào tháng này
            'payment_confirmed_by' => $this->admin->id,
        ]);

        // Ghi đè created_at về 2 tháng trước để kiểm tra bóc tách trục ngày
        \Illuminate\Support\Facades\DB::table('bookings')
            ->where('id', $booking->id)
            ->update(['created_at' => $twoMonthsAgo]);

        // 1. Phân bổ theo ngày thanh toán (date_by = payment_date)
        $paymentTrend = $this->revenueService->getMonthlyRevenueTrend(3, RevenueCalculationService::DATE_BY_PAYMENT);
        $chartPayment = collect($paymentTrend['chart'])->keyBy('month');

        $currentMonthStr = Carbon::now()->format('m/Y');
        $twoMonthsAgoStr = $twoMonthsAgo->format('m/Y');

        $this->assertEquals(5000000, $chartPayment[$currentMonthStr]['valid_paid_revenue'] ?? 0);
        $this->assertEquals(0, $chartPayment[$twoMonthsAgoStr]['valid_paid_revenue'] ?? 0);

        // 2. Phân bổ theo ngày đặt xe (date_by = booking_date)
        $bookingTrend = $this->revenueService->getMonthlyRevenueTrend(3, RevenueCalculationService::DATE_BY_BOOKING);
        $chartBooking = collect($bookingTrend['chart'])->keyBy('month');

        $this->assertEquals(0, $chartBooking[$currentMonthStr]['valid_paid_revenue'] ?? 0);
        $this->assertEquals(5000000, $chartBooking[$twoMonthsAgoStr]['valid_paid_revenue'] ?? 0);
    }

    /**
     * Test 4: Đối chiếu tổng số liệu giữa Dashboard (/admin/statistics) và Danh sách đơn (/admin/bookings/data).
     * Phải dùng chung một nguồn logic chuẩn hóa, các con số trùng khớp 100%.
     */
    public function test_dashboard_and_booking_list_reconcile_totals_with_exact_same_unified_service()
    {
        Sanctum::actingAs($this->admin, ['*'], 'admin');

        // Tạo 3 đơn với nhiều trạng thái khác nhau
        Booking::create([
            'ma_dat_xe'            => 'BK-RECON-01',
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car->id,
            'start_date'           => Carbon::now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_date'             => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'tong_tien'            => 2500000,
            'tinh_trang'           => Booking::STATUS_CONFIRMED,
            'is_thanh_toan'        => 1,
            'payment_confirmed_at' => Carbon::now(),
            'payment_confirmed_by' => $this->admin->id,
            'created_at'           => Carbon::now(),
        ]);

        Booking::create([
            'ma_dat_xe'            => 'BK-RECON-02',
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car->id,
            'start_date'           => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'end_date'             => Carbon::now()->addDays(4)->format('Y-m-d H:i:s'),
            'tong_tien'            => 1500000,
            'tinh_trang'           => Booking::STATUS_CANCELLED,
            'is_thanh_toan'        => 1,
            'payment_confirmed_at' => Carbon::now(),
            'payment_confirmed_by' => $this->admin->id,
            'created_at'           => Carbon::now(),
        ]);

        Booking::create([
            'ma_dat_xe'            => 'BK-RECON-03',
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car->id,
            'start_date'           => Carbon::now()->addDays(5)->format('Y-m-d H:i:s'),
            'end_date'             => Carbon::now()->addDays(6)->format('Y-m-d H:i:s'),
            'tong_tien'            => 3000000,
            'tinh_trang'           => Booking::STATUS_CONFIRMED,
            'is_thanh_toan'        => 0,
            'created_at'           => Carbon::now(),
        ]);

        // Gọi API Dashboard
        $dashboardResponse = $this->getJson('/api/admin/statistics');
        $dashboardResponse->assertStatus(200);
        $dashboardData = $dashboardResponse->json('data.summary');

        // Gọi API Bookings List
        $bookingsResponse = $this->getJson('/api/admin/bookings/data');
        $bookingsResponse->assertStatus(200);
        $bookingsData = $bookingsResponse->json('revenue_summary');

        // So khớp đối chiếu 100% giữa hai endpoint
        $this->assertEquals($dashboardData['valid_paid_revenue'], $bookingsData['valid_paid_revenue']);
        $this->assertEquals($dashboardData['total_booking_value'], $bookingsData['total_booking_value']);
        $this->assertEquals($dashboardData['confirmed_booking_value'], $bookingsData['confirmed_booking_value']);
        $this->assertEquals($dashboardData['paid_booking_value'], $bookingsData['paid_booking_value']);
        $this->assertEquals($dashboardData['refund_review_value'], $bookingsData['refund_review_value']);
        $this->assertEquals($dashboardData['unpaid_confirmed_value'], $bookingsData['unpaid_confirmed_value']);
        $this->assertEquals($dashboardData['total_bookings'], $bookingsData['total_bookings']);

        // Xác thực giá trị cụ thể
        $this->assertEquals(2500000, $dashboardData['valid_paid_revenue']);
        $this->assertEquals(7000000, $dashboardData['total_booking_value']);
        $this->assertEquals(1500000, $dashboardData['refund_review_value']);
        $this->assertEquals(3000000, $dashboardData['unpaid_confirmed_value']);
    }

    /**
     * Test 5: Xử lý an toàn khi không có dữ liệu (0, rỗng, null) và làm tròn số nguyên VNĐ.
     */
    public function test_handles_zero_empty_and_null_amounts_without_floating_point_errors()
    {
        $this->assertEquals(0, $this->revenueService->roundCurrency(null));
        $this->assertEquals(0, $this->revenueService->roundCurrency(''));
        $this->assertEquals(0, $this->revenueService->roundCurrency(0));
        $this->assertEquals(1500000, $this->revenueService->roundCurrency(1500000));
        $this->assertEquals(1500000, $this->revenueService->roundCurrency('1500000.00'));
        $this->assertEquals(1500001, $this->revenueService->roundCurrency(1500000.75));

        // Khi DB chưa có đơn nào
        $emptySummary = $this->revenueService->calculateRevenueSummary();
        $this->assertSame(0, $emptySummary['total_booking_value']);
        $this->assertSame(0, $emptySummary['valid_paid_revenue']);
        $this->assertSame(0, $emptySummary['refund_review_value']);
        $this->assertSame(0, $emptySummary['total_bookings']);
    }
}
