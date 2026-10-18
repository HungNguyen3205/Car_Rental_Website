<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\BookingTransaction;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\Contact;
use App\Models\User;
use App\Services\Authorization\PermissionMatrix;
use App\Services\Export\CsvExportService;
use App\Services\Revenue\RevenueCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardOperationsCenterTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $owner;
    protected Admin $accountant;
    protected Admin $dispatcher;
    protected User $customer;
    protected Car $car1;
    protected Car $car2;
    protected Car $car3;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Quản trị viên theo ma trận vai trò
        $this->owner = Admin::create([
            'ho_ten'        => 'Chủ Hệ Thống',
            'email'         => 'owner@carrental.test',
            'password'      => Hash::make('Secret123!'),
            'role'          => PermissionMatrix::ROLE_OWNER,
            'so_dien_thoai' => '0901000001',
            'tinh_trang'    => 1,
        ]);

        $this->accountant = Admin::create([
            'ho_ten'        => 'Kế Toán Viên',
            'email'         => 'accountant@carrental.test',
            'password'      => Hash::make('Secret123!'),
            'role'          => PermissionMatrix::ROLE_ACCOUNTANT,
            'so_dien_thoai' => '0901000002',
            'tinh_trang'    => 1,
        ]);

        $this->dispatcher = Admin::create([
            'ho_ten'        => 'Điều Phối Viên',
            'email'         => 'dispatcher@carrental.test',
            'password'      => Hash::make('Secret123!'),
            'role'          => PermissionMatrix::ROLE_DISPATCHER,
            'so_dien_thoai' => '0901000003',
            'tinh_trang'    => 1,
        ]);

        // 2. Khách hàng
        $this->customer = User::create([
            'ho_ten'        => 'Nguyễn Văn Khách',
            'email'         => 'customer@carrental.test',
            'password'      => Hash::make('Secret123!'),
            'so_dien_thoai' => '0912345678',
            'is_active'     => 1,
        ]);

        // 3. Danh mục xe và xe mẫu
        $category = CarCategory::create([
            'name'        => 'Sedan Điện',
            'slug'        => 'sedan-dien',
            'description' => 'Xe sedan sang trọng',
        ]);

        $this->car1 = Car::create([
            'name'          => 'VinFast VF8 Lux',
            'price_per_day' => 1200000,
            'status'        => 'available',
            'is_active'     => true,
            'category_id'   => $category->id,
        ]);

        $this->car2 = Car::create([
            'name'          => 'VinFast VF9 Plus',
            'price_per_day' => 1800000,
            'status'        => 'available',
            'is_active'     => true,
            'category_id'   => $category->id,
        ]);

        $this->car3 = Car::create([
            'name'          => 'VinFast VF e34 Maintenance',
            'price_per_day' => 900000,
            'status'        => 'maintenance',
            'is_active'     => true,
            'category_id'   => $category->id,
        ]);
    }

    /**
     * Test 1: Điều phối viên (Dispatcher) có thể vào Dashboard vận hành nhưng số liệu tài chính được bảo mật và ẩn đi.
     */
    public function test_dispatcher_can_access_dashboard_with_redacted_financial_metrics(): void
    {
        // Tạo 1 đơn hàng có số tiền
        Booking::create([
            'ma_dat_xe'            => 'BK-DISP-01',
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car1->id,
            'start_date'           => Carbon::now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_date'             => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'tong_tien'            => 3600000,
            'tinh_trang'           => Booking::STATUS_PENDING,
            'is_thanh_toan'        => 0,
        ]);

        $res = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/statistics');

        $res->assertStatus(200);
        $res->assertJson([
            'status'             => 1,
            'can_view_financial' => false,
        ]);

        $data = $res->json('data');

        // Các chỉ số tài chính phải là null
        $this->assertNull($data['summary']['valid_paid_revenue']);
        $this->assertNull($data['summary']['total_booking_value']);
        $this->assertNull($data['summary']['total_deposit_held']);
        $this->assertEmpty($data['revenue_chart']);

        // Các chỉ số vận hành vẫn hiển thị đầy đủ
        $this->assertEquals(1, $data['summary']['pending_bookings']);
        $this->assertGreaterThanOrEqual(1, $data['summary']['total_cars']);
        $this->assertNotNull($data['fleet_breakdown']);

        // Trong bảng đơn gần đây, số tiền của đơn bị ẩn
        $this->assertNotEmpty($data['recent_bookings']);
        $this->assertNull($data['recent_bookings'][0]['tong_tien']);
    }

    /**
     * Test 2: Kế toán (Accountant) hoặc Chủ hệ thống (Owner) xem được đầy đủ số liệu tài chính và biểu đồ.
     */
    public function test_accountant_can_view_full_financial_metrics_and_charts(): void
    {
        $paidBooking = Booking::create([
            'ma_dat_xe'            => 'BK-FIN-01',
            'user_id'              => $this->customer->id,
            'car_id'               => $this->car1->id,
            'start_date'           => Carbon::now()->subDays(5)->format('Y-m-d H:i:s'),
            'end_date'             => Carbon::now()->subDays(2)->format('Y-m-d H:i:s'),
            'tong_tien'            => 2400000,
            'tinh_trang'           => Booking::STATUS_CONFIRMED,
            'is_thanh_toan'        => 1,
            'payment_confirmed_at' => Carbon::now()->subDays(5),
            'payment_confirmed_by' => $this->accountant->id,
        ]);

        $res = $this->actingAs($this->accountant, 'admin')
            ->getJson('/api/admin/statistics');

        $res->assertStatus(200);
        $res->assertJson([
            'status'             => 1,
            'can_view_financial' => true,
        ]);

        $data = $res->json('data');
        $this->assertEquals(2400000, $data['summary']['valid_paid_revenue']);
        $this->assertEquals(2400000, $data['recent_bookings'][0]['tong_tien']);
        $this->assertNotEmpty($data['revenue_chart']);
    }

    /**
     * Test 3: Tiền cọc đang giữ là số dư lũy kế đến mốc báo cáo, không bị giới hạn trong khoảng ngày lọc và không tính vào doanh thu.
     */
    public function test_cumulative_deposit_balance_snapshot_vs_period_flow(): void
    {
        $pastMonth = Carbon::now()->subDays(45);
        $thisWeek = Carbon::now()->subDays(2);

        $booking1 = Booking::create([
            'ma_dat_xe'     => 'BK-DEP-PAST',
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car1->id,
            'start_date'    => $pastMonth->copy()->addDays(1)->format('Y-m-d H:i:s'),
            'end_date'      => $pastMonth->copy()->addDays(5)->format('Y-m-d H:i:s'),
            'tong_tien'     => 5000000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => 1,
        ]);

        // Cọc thu từ 45 ngày trước (ngoài khoảng 30 ngày)
        BookingTransaction::create([
            'booking_id'       => $booking1->id,
            'type'             => BookingTransaction::TYPE_DEPOSIT_COLLECTION,
            'amount'           => 10000000, // 10 triệu tiền cọc
            'status'           => BookingTransaction::STATUS_CONFIRMED,
            'method'           => 'bank_transfer',
            'transaction_time' => $pastMonth,
            'created_by'       => $this->accountant->id,
        ]);

        $booking2 = Booking::create([
            'ma_dat_xe'     => 'BK-DEP-RECENT',
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car2->id,
            'start_date'    => $thisWeek->copy()->addDays(1)->format('Y-m-d H:i:s'),
            'end_date'      => $thisWeek->copy()->addDays(3)->format('Y-m-d H:i:s'),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => 1,
        ]);

        // Cọc thu trong tuần này
        BookingTransaction::create([
            'booking_id'       => $booking2->id,
            'type'             => BookingTransaction::TYPE_DEPOSIT_COLLECTION,
            'amount'           => 5000000, // 5 triệu tiền cọc
            'status'           => BookingTransaction::STATUS_CONFIRMED,
            'method'           => 'bank_transfer',
            'transaction_time' => $thisWeek,
            'created_by'       => $this->accountant->id,
        ]);

        // Lọc trong 7 ngày gần đây
        $startDate = Carbon::now()->subDays(6)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        $res = $this->actingAs($this->owner, 'admin')
            ->getJson("/api/admin/statistics?start_date={$startDate}&end_date={$endDate}");

        $res->assertStatus(200);
        $summary = $res->json('data.summary');

        // Số dư cọc đang giữ lũy kế đến hôm nay phải là 15.000.000 (10tr + 5tr)
        $this->assertEquals(15000000, $summary['total_deposit_held']);

        // Trong khi cọc phát sinh trong kỳ 7 ngày chỉ là 5.000.000
        $this->assertEquals(5000000, $summary['period_deposit_collected']);

        // Tiền cọc tuyệt đối KHÔNG tính vào doanh thu thuê xe
        $this->assertEquals(0, $summary['net_rental_collected']);
    }

    /**
     * Test 4: Tính toán chính xác giao xe hôm nay, nhận trả hôm nay và xe quá hạn trả.
     */
    public function test_operational_kpi_deliveries_returns_and_overdue_vehicles(): void
    {
        $today = Carbon::today();

        // 1. Giao xe hôm nay (start_date = hôm nay, tinh_trang = 1)
        Booking::create([
            'ma_dat_xe'     => 'BK-DELIVERY-TODAY',
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car1->id,
            'start_date'    => $today->copy()->addHours(9)->format('Y-m-d H:i:s'),
            'end_date'      => $today->copy()->addDays(2)->format('Y-m-d H:i:s'),
            'tong_tien'     => 2400000,
            'tinh_trang'    => Booking::STATUS_CONFIRMED,
            'is_thanh_toan' => 1,
        ]);

        // 2. Nhận trả hôm nay (end_date = hôm nay, tinh_trang = 3 đang thuê)
        Booking::create([
            'ma_dat_xe'     => 'BK-RETURN-TODAY',
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car2->id,
            'start_date'    => $today->copy()->subDays(2)->format('Y-m-d H:i:s'),
            'end_date'      => $today->copy()->addHours(18)->format('Y-m-d H:i:s'),
            'tong_tien'     => 3600000,
            'tinh_trang'    => Booking::STATUS_IN_PROGRESS,
            'is_thanh_toan' => 1,
        ]);

        // 3. Xe quá hạn trả (end_date < now(), tinh_trang = 3 đang thuê, trễ từ hôm qua)
        Booking::create([
            'ma_dat_xe'     => 'BK-OVERDUE-01',
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car1->id,
            'start_date'    => Carbon::now()->subDays(4)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->subDays(1)->format('Y-m-d H:i:s'), // Trễ 1 ngày (24 giờ)
            'tong_tien'     => 4000000,
            'tinh_trang'    => Booking::STATUS_IN_PROGRESS,
            'is_thanh_toan' => 1,
        ]);

        // 4. Biên lai chờ duyệt và yêu cầu tư vấn mới
        Booking::create([
            'ma_dat_xe'     => 'BK-BILL-VERIFY',
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car2->id,
            'start_date'    => Carbon::now()->addDays(1)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(3)->format('Y-m-d H:i:s'),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_PENDING,
            'is_thanh_toan' => 0,
            'bill_image'    => '/uploads/receipts/bill_123.jpg',
        ]);

        Contact::create([
            'ho_ten'        => 'Trần Thị Khách',
            'email'         => 'tranthi@test.com',
            'so_dien_thoai' => '0987654321',
            'tieu_de'       => 'Tư vấn thuê VF9 theo tháng',
            'noi_dung'      => 'Tôi cần thuê xe VF9 trong 3 tháng cho doanh nghiệp.',
            'trang_thai'    => Contact::STATUS_PENDING,
        ]);

        $res = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/statistics');

        $res->assertStatus(200);
        $summary = $res->json('data.summary');
        $actions = $res->json('data.operational_actions');

        $this->assertEquals(1, $summary['deliveries_today']);
        $this->assertEquals(1, $summary['returns_today']);
        $this->assertEquals(1, $actions['overdue_returns']);
        $this->assertEquals(1, $actions['pending_receipts']);
        $this->assertEquals(1, $actions['new_contacts']);

        // Kiểm tra danh sách xe quá hạn
        $this->assertNotEmpty($actions['overdue_bookings']);
        $this->assertEquals('BK-OVERDUE-01', $actions['overdue_bookings'][0]['ma_dat_xe']);
        $this->assertGreaterThanOrEqual(5.5, $actions['overdue_bookings'][0]['hours_overdue']);
    }

    /**
     * Test 5: Phân chia đội xe: Sẵn sàng theo lịch vs Đang lưu hành vs Đang bảo trì.
     */
    public function test_fleet_breakdown_differentiation(): void
    {
        // car3 là bảo trì (status = 'maintenance')
        // car2 đang cho thuê (in_progress)
        Booking::create([
            'ma_dat_xe'     => 'BK-RENTING-FLEET',
            'user_id'       => $this->customer->id,
            'car_id'        => $this->car2->id,
            'start_date'    => Carbon::now()->subDays(1)->format('Y-m-d H:i:s'),
            'end_date'      => Carbon::now()->addDays(2)->format('Y-m-d H:i:s'),
            'tong_tien'     => 3000000,
            'tinh_trang'    => Booking::STATUS_IN_PROGRESS,
            'is_thanh_toan' => 1,
        ]);

        // car1 đang rảnh (status = 'available', không có hợp đồng active)

        $res = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/statistics');

        $res->assertStatus(200);
        $fleet = $res->json('data.fleet_breakdown');

        $this->assertEquals(3, $fleet['total_cars']);
        $this->assertEquals(1, $fleet['available_cars']);   // car1
        $this->assertEquals(1, $fleet['renting_cars']);     // car2
        $this->assertEquals(1, $fleet['maintenance_cars']); // car3
        $this->assertEquals(33.3, $fleet['available_rate']);
    }

    /**
     * Test 6: Biểu đồ doanh thu tự động chuyển sang Daily Trend khi khoảng lọc <= 31 ngày.
     */
    public function test_revenue_chart_switches_to_daily_trend_for_short_date_range(): void
    {
        $startDate = Carbon::now()->subDays(7)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        $res = $this->actingAs($this->owner, 'admin')
            ->getJson("/api/admin/statistics?start_date={$startDate}&end_date={$endDate}");

        $res->assertStatus(200);
        $this->assertEquals('daily', $res->json('data.chart_granularity'));
        $chart = $res->json('data.revenue_chart');
        $this->assertCount(8, $chart); // 7 ngày qua + hôm nay = 8 mốc ngày
    }

    /**
     * Test 7: Xuất file báo cáo tài chính bằng CSV UTF-8 với bộ lọc ngày đồng bộ.
     */
    public function test_revenue_csv_export_with_date_range_and_authorization(): void
    {
        // 1. Dispatcher xuất báo cáo tài chính -> Bị chặn 403
        $dispRes = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/reports/export');
        $dispRes->assertStatus(403);

        // 2. Kế toán xuất báo cáo tài chính với bộ lọc ngày -> 200 CSV UTF-8
        $startDate = Carbon::now()->subDays(7)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');

        $accRes = $this->actingAs($this->accountant, 'admin')
            ->get("/api/admin/reports/export?start_date={$startDate}&end_date={$endDate}");

        $accRes->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $accRes->headers->get('content-type'));

        $content = $accRes->streamedContent();
        $this->assertStringStartsWith(CsvExportService::UTF8_BOM, $content);
        $this->assertStringContainsString('Kỳ báo cáo (Tháng)', $content);
        $this->assertStringContainsString('Doanh thu thực thu hợp lệ (VND)', $content);
    }
}
