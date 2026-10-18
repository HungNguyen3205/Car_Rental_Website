<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\BookingTransaction;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Authorization\PermissionMatrix;
use App\Services\Export\CsvExportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemSettingsAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $owner;
    protected Admin $dispatcher;
    protected Admin $accountant;
    protected User $user;
    protected CarCategory $category;
    protected Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Owner (Toàn quyền)
        $this->owner = Admin::create([
            'ho_ten'      => 'Chủ Hệ Thống',
            'email'       => 'owner@carrental.test',
            'password'    => bcrypt('Owner@123456'),
            'role'        => 'admin',
            'admin_role'  => PermissionMatrix::ROLE_OWNER,
            'permissions' => PermissionMatrix::getDefaultPermissionsForRole(PermissionMatrix::ROLE_OWNER),
            'tinh_trang'  => 1,
        ]);

        // 2. Dispatcher (Điều phối)
        $this->dispatcher = Admin::create([
            'ho_ten'      => 'Điều Phối Viên',
            'email'       => 'dispatcher@carrental.test',
            'password'    => bcrypt('Dispatcher@123456'),
            'role'        => 'admin',
            'admin_role'  => PermissionMatrix::ROLE_DISPATCHER,
            'permissions' => PermissionMatrix::getDefaultPermissionsForRole(PermissionMatrix::ROLE_DISPATCHER),
            'tinh_trang'  => 1,
        ]);

        // 3. Accountant (Kế toán)
        $this->accountant = Admin::create([
            'ho_ten'      => 'Kế Toán Viên',
            'email'       => 'accountant@carrental.test',
            'password'    => bcrypt('Accountant@123456'),
            'role'        => 'admin',
            'admin_role'  => PermissionMatrix::ROLE_ACCOUNTANT,
            'permissions' => PermissionMatrix::getDefaultPermissionsForRole(PermissionMatrix::ROLE_ACCOUNTANT),
            'tinh_trang'  => 1,
        ]);

        // 4. Regular User
        $this->user = User::create([
            'ho_ten'        => 'Khách Hàng Thử Nghiệm',
            'email'         => 'customer@carrental.test',
            'so_dien_thoai' => '0987654321',
            'password'      => bcrypt('Customer@123456'),
            'is_active'     => 1,
        ]);

        // 5. Category & Car
        $this->category = CarCategory::create([
            'name'        => 'SUV Điện 7 Chỗ',
            'description' => 'Xe gầm cao gia đình hiện đại',
        ]);

        $this->car = Car::create([
            'name'          => 'VinFast VF9 Plus',
            'price_per_day' => 2500000,
            'category_id'   => $this->category->id,
            'status'        => 'available',
            'is_active'     => true,
            'city'          => 'Hà Nội',
        ]);
    }

    /**
     * Test 1: Endpoint cấu hình public trả đúng field cho phép, có cache, không lộ secret.
     */
    public function test_public_settings_endpoint_caching_and_no_secrets(): void
    {
        // Thêm một secret nội bộ không được phép xuất hiện ở public API
        SystemSetting::create([
            'key'         => 'stripe_secret_key',
            'value'       => 'sk_live_very_secret_api_key_12345',
            'type'        => 'string',
            'group'       => 'payment_gateway',
            'is_public'   => false,
            'description' => 'Khóa bí mật thanh toán',
        ]);

        SystemSetting::clearPublicCache();

        $response = $this->getJson('/api/settings/public');
        $response->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'data'   => [
                    'site_name'   => 'DriveRent',
                    'hotline'     => '1900 1234',
                    'bank_name'   => 'MBBank (Ngân hàng TMCP Quân Đội)',
                    'bank_account_number' => '0971120038',
                ],
            ]);

        // Đảm bảo không chứa secret key
        $data = $response->json('data');
        $this->assertArrayNotHasKey('stripe_secret_key', $data);
        $this->assertArrayNotHasKey('password', $data);

        // Kiểm tra cơ chế Cache::remember hoạt động
        $this->assertTrue(Cache::has('system_settings_public'));
    }

    /**
     * Test 2: Quyền cập nhật cấu hình: Chỉ Owner/Settings.manage được sửa; Dispatcher bị chặn 403.
     */
    public function test_admin_settings_authorization_and_cache_invalidation(): void
    {
        // Dispatcher không có quyền settings.manage -> HTTP 403
        $resDispatcher = $this->actingAs($this->dispatcher, 'admin')
            ->putJson('/api/admin/settings', [
                'site_name' => 'Hacked Name',
            ]);
        $resDispatcher->assertStatus(403);

        // Owner có toàn quyền -> Cập nhật thành công
        $resOwner = $this->actingAs($this->owner, 'admin')
            ->putJson('/api/admin/settings', [
                'site_name' => 'DriveRentals Premium',
                'hotline'   => '1900 9999',
            ]);
        $resOwner->assertStatus(200)
            ->assertJson([
                'status' => 1,
            ]);

        // Cache công khai phải được làm mới
        $publicRes = $this->getJson('/api/settings/public');
        $publicRes->assertStatus(200);
        $this->assertEquals('DriveRentals Premium', $publicRes->json('data.site_name'));
        $this->assertEquals('1900 9999', $publicRes->json('data.hotline'));
    }

    /**
     * Test 3: Thay đổi thông tin ngân hàng ghi nhận Audit Log và không sửa ngầm snapshot đơn cũ.
     */
    public function test_bank_settings_change_creates_audit_log_and_preserves_old_snapshots(): void
    {
        // 1. Tạo một đơn đặt xe cũ với số tiền và giao dịch xác nhận
        $booking = Booking::create([
            'ma_dat_xe'            => 'BK_TEST_001',
            'user_id'              => $this->user->id,
            'car_id'               => $this->car->id,
            'start_date'           => Carbon::now()->addDays(2),
            'end_date'             => Carbon::now()->addDays(5),
            'tong_tien'            => 7500000,
            'tinh_trang'           => 1,
            'is_thanh_toan'        => 1,
            'payment_confirmed_at' => Carbon::now(),
            'payment_confirmed_by' => $this->accountant->id,
        ]);

        $tx = BookingTransaction::create([
            'booking_id'       => $booking->id,
            'type'             => BookingTransaction::TYPE_RENTAL_PAYMENT,
            'amount'           => 7500000,
            'status'           => BookingTransaction::STATUS_CONFIRMED,
            'method'           => 'bank_transfer',
            'reference_code'   => 'OLD_BANK_REF_001',
            'transaction_time' => Carbon::now(),
            'created_by'       => $this->accountant->id,
        ]);

        // 2. Admin cập nhật tài khoản ngân hàng mới
        $updateRes = $this->actingAs($this->owner, 'admin')
            ->putJson('/api/admin/settings', [
                'bank_name'           => 'Vietcombank (Ngân hàng Ngoại Thương)',
                'bank_account_number' => '0071000999888',
                'bank_account_holder' => 'CONG TY CP THUE XE XANH',
                'bank_bin'            => '970436',
            ]);
        $updateRes->assertStatus(200);

        // 3. Kiểm tra Audit Log được ghi nhận với hành động settings.bank_updated
        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'settings.bank_updated',
            'target_type' => 'SystemSetting',
        ]);

        // 4. Kiểm tra snapshot của đơn và giao dịch cũ hoàn toàn bất biến, không bị sửa ngầm
        $freshTx = BookingTransaction::find($tx->id);
        $this->assertEquals('OLD_BANK_REF_001', $freshTx->reference_code);
        $this->assertEquals(7500000, $freshTx->amount);
    }

    /**
     * Test 4: CRUD danh mục xe: Validation, tên trùng, và bảo vệ khi đang có xe sử dụng.
     */
    public function test_car_category_crud_and_protection_when_in_use(): void
    {
        // 1. Thêm mới danh mục xe hợp lệ
        $createRes = $this->actingAs($this->owner, 'admin')
            ->postJson('/api/admin/car-categories', [
                'name'        => 'Sedan Hạng Sang',
                'description' => 'Xe sang phục vụ hội nghị và sự kiện',
            ]);
        $createRes->assertStatus(201);
        $newCatId = $createRes->json('data.id');

        // 2. Thử tạo trùng tên -> Bị từ chối HTTP 422
        $dupRes = $this->actingAs($this->owner, 'admin')
            ->postJson('/api/admin/car-categories', [
                'name' => 'Sedan Hạng Sang',
            ]);
        $dupRes->assertStatus(422);

        // 3. Cập nhật danh mục mới
        $updateRes = $this->actingAs($this->owner, 'admin')
            ->putJson("/api/admin/car-categories/{$newCatId}", [
                'name'        => 'Sedan Hạng Sang VIP',
                'description' => 'Cập nhật mô tả VIP',
            ]);
        $updateRes->assertStatus(200);

        // 4. Xóa danh mục mới (chưa có xe nào) -> Thành công
        $delEmptyRes = $this->actingAs($this->owner, 'admin')
            ->deleteJson("/api/admin/car-categories/{$newCatId}");
        $delEmptyRes->assertStatus(200);

        // 5. Thử xóa danh mục đang có xe ($this->category đang gán cho $this->car) -> Phải BỊ CHẶN 422
        $delProtectedRes = $this->actingAs($this->owner, 'admin')
            ->deleteJson("/api/admin/car-categories/{$this->category->id}");
        $delProtectedRes->assertStatus(422)
            ->assertJson([
                'status' => 0,
            ]);
        $this->assertStringContainsString('Không thể xóa', $delProtectedRes->json('message'));
        $this->assertDatabaseHas('car_categories', ['id' => $this->category->id]);
    }

    /**
     * Test 5: Xuất danh sách đơn đặt xe (CSV) đúng bộ lọc, định dạng và phân quyền vai trò.
     */
    public function test_booking_export_filters_and_role_permissions(): void
    {
        // Tạo 2 đơn: 1 đơn đã duyệt, 1 đơn chờ duyệt
        $b1 = Booking::create([
            'ma_dat_xe'     => 'BK_EXPORT_001',
            'user_id'       => $this->user->id,
            'car_id'        => $this->car->id,
            'start_date'    => '2026-06-01 10:00:00',
            'end_date'      => '2026-06-03 10:00:00',
            'tong_tien'     => 5000000,
            'tinh_trang'    => 1, // Đã xác nhận
            'is_thanh_toan' => 1,
        ]);

        $b2 = Booking::create([
            'ma_dat_xe'     => 'BK_EXPORT_002',
            'user_id'       => $this->user->id,
            'car_id'        => $this->car->id,
            'start_date'    => '2026-07-01 10:00:00',
            'end_date'      => '2026-07-04 10:00:00',
            'tong_tien'     => 7500000,
            'tinh_trang'    => 0, // Chờ duyệt
            'is_thanh_toan' => 0,
        ]);

        // Kế toán xuất với bộ lọc tinh_trang = 1
        $response = $this->actingAs($this->accountant, 'admin')
            ->get('/api/admin/bookings/export?tinh_trang=1');

        $response->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('content-type'));

        $content = $response->streamedContent();

        // Kiểm tra UTF-8 BOM
        $this->assertStringStartsWith(CsvExportService::UTF8_BOM, $content);

        // Kiểm tra khớp đơn #HD-{b1->id} và không chứa #HD-{b2->id}
        $this->assertStringContainsString("#HD-{$b1->id}", $content);
        $this->assertStringNotContainsString("#HD-{$b2->id}", $content);

        // Kế toán thấy các cột tài chính
        $this->assertStringContainsString('Tổng tiền thuê (VND)', $content);
        $this->assertStringContainsString('5.000.000 đ', $content);

        // Bây giờ Điều phối viên (Dispatcher) xuất file -> Không có các cột tài chính
        $dispResponse = $this->actingAs($this->dispatcher, 'admin')
            ->get('/api/admin/bookings/export?tinh_trang=1');
        $dispResponse->assertStatus(200);
        $dispContent = $dispResponse->streamedContent();

        $this->assertStringNotContainsString('Tổng tiền thuê (VND)', $dispContent);
        $this->assertStringContainsString('Địa điểm nhận xe', $dispContent);
    }

    /**
     * Test 6: Chống Spreadsheet Formula Injection (CSV Injection).
     * Dữ liệu do người dùng nhập bắt đầu bằng =, +, -, @ được escape bằng dấu nháy đơn.
     */
    public function test_formula_injection_protection_in_csv_export(): void
    {
        // Người dùng nhập ghi chú và họ tên độc hại nhằm khai thác CVE-2014-3524 / Formula Execution
        $maliciousUser = User::create([
            'ho_ten'        => '=cmd|\'/C calc\'!A0',
            'email'         => 'attacker@carrental.test',
            'so_dien_thoai' => '+84999888777',
            'password'      => bcrypt('Pass@123456'),
            'is_active'     => 1,
        ]);

        $booking = Booking::create([
            'ma_dat_xe'     => 'BK_MALICIOUS_001',
            'user_id'       => $maliciousUser->id,
            'car_id'        => $this->car->id,
            'start_date'    => '2026-08-01 10:00:00',
            'end_date'      => '2026-08-03 10:00:00',
            'tong_tien'     => 5000000,
            'tinh_trang'    => 1,
            'is_thanh_toan' => 1,
            'ly_do_thue'    => '-2+5+cmd|calc',
            'cancel_reason' => '@SUM(1+1)*cmd',
        ]);

        $response = $this->actingAs($this->owner, 'admin')
            ->get('/api/admin/bookings/export?keyword=attacker');
        $response->assertStatus(200);

        $content = $response->streamedContent();

        // Kiểm tra xem các ký tự nguy hiểm đã được bọc nháy đơn '
        $this->assertStringContainsString("'=cmd|", $content);
        $this->assertStringContainsString("'-2+5+cmd|calc", $content);
        $this->assertStringContainsString("'@SUM(1+1)*cmd", $content);
    }

    /**
     * Test 7: Phân quyền xuất báo cáo doanh thu: Chặn Dispatcher 403, cho phép Accountant.
     */
    public function test_revenue_export_authorization(): void
    {
        // 1. Dispatcher thử xuất báo cáo doanh thu -> Phải bị từ chối 403
        $dispRes = $this->actingAs($this->dispatcher, 'admin')
            ->getJson('/api/admin/reports/export');
        $dispRes->assertStatus(403);

        // 2. Kế toán xuất báo cáo -> Thành công HTTP 200 trả về file CSV UTF-8
        $accRes = $this->actingAs($this->accountant, 'admin')
            ->get('/api/admin/reports/export');
        $accRes->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $accRes->headers->get('content-type'));

        $content = $accRes->streamedContent();
        $this->assertStringStartsWith(CsvExportService::UTF8_BOM, $content);
        $this->assertStringContainsString('Kỳ báo cáo (Tháng)', $content);
        $this->assertStringContainsString('Doanh thu thực thu hợp lệ (VND)', $content);
    }
}
