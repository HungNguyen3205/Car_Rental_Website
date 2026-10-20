<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AuditLog;
use App\Models\Car;
use App\Models\CarCategory;
use App\Models\User;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $owner;
    protected Admin $auditor;
    protected Admin $unauthorizedStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = Admin::create([
            'ho_ten'    => 'Owner Admin',
            'email'     => 'owner@driverentals.com',
            'password'  => bcrypt('password123'),
            'role'      => 'owner',
            'tinh_trang' => 1,
        ]);

        $this->auditor = Admin::create([
            'ho_ten'      => 'Auditor Staff',
            'email'       => 'auditor@driverentals.com',
            'password'    => bcrypt('password123'),
            'role'        => 'custom',
            'permissions' => ['audit_logs.view'],
            'tinh_trang'   => 1,
        ]);

        $this->unauthorizedStaff = Admin::create([
            'ho_ten'      => 'Support Staff',
            'email'       => 'support@driverentals.com',
            'password'    => bcrypt('password123'),
            'role'        => 'custom',
            'permissions' => ['bookings.view'],
            'tinh_trang'   => 1,
        ]);
    }

    /**
     * Test 1: Quyền truy cập: Chỉ Owner hoặc Admin có quyền audit_logs.view được truy cập.
     */
    public function test_audit_logs_rbac_authorization(): void
    {
        // 1. Unauthenticated -> 401
        $resGuest = $this->getJson('/api/admin/audit-logs');
        $resGuest->assertStatus(401);

        // 2. Nhân viên không có quyền audit_logs.view -> 403
        $resUnauthorized = $this->actingAs($this->unauthorizedStaff, 'admin')
            ->getJson('/api/admin/audit-logs');
        $resUnauthorized->assertStatus(403);

        // 3. Nhân viên có quyền audit_logs.view -> 200
        $resAuditor = $this->actingAs($this->auditor, 'admin')
            ->getJson('/api/admin/audit-logs');
        $resAuditor->assertStatus(200)
            ->assertJsonPath('status', 1);

        // 4. Owner có toàn quyền -> 200
        $resOwner = $this->actingAs($this->owner, 'admin')
            ->getJson('/api/admin/audit-logs');
        $resOwner->assertStatus(200)
            ->assertJsonPath('status', 1);
    }

    /**
     * Test 2: Lọc theo khoảng ngày (date_from, date_to và alias start_date, end_date).
     */
    public function test_audit_logs_date_range_filtering(): void
    {
        // Tạo log ở 3 mốc thời gian khác nhau:
        // 1. 5 ngày trước
        $logOld = AuditLog::create([
            'actor_type'   => 'admin',
            'actor_id'     => $this->owner->id,
            'actor_name'   => 'Owner',
            'action'       => 'car.created',
            'target_type'  => 'Car',
            'target_id'    => '101',
            'target_label' => 'VinFast VF8 Old',
        ]);
        \Illuminate\Support\Facades\DB::table('audit_logs')->where('id', $logOld->id)->update([
            'created_at' => Carbon::now()->subDays(5)->toDateTimeString(),
            'updated_at' => Carbon::now()->subDays(5)->toDateTimeString(),
        ]);

        // 2. Hôm qua
        $logYesterday = AuditLog::create([
            'actor_type'   => 'admin',
            'actor_id'     => $this->owner->id,
            'actor_name'   => 'Owner',
            'action'       => 'car.updated',
            'target_type'  => 'Car',
            'target_id'    => '102',
            'target_label' => 'VinFast VF9 Yesterday',
        ]);
        \Illuminate\Support\Facades\DB::table('audit_logs')->where('id', $logYesterday->id)->update([
            'created_at' => Carbon::now()->subDays(1)->toDateTimeString(),
            'updated_at' => Carbon::now()->subDays(1)->toDateTimeString(),
        ]);

        // 3. Hôm nay
        $logToday = AuditLog::create([
            'actor_type'   => 'admin',
            'actor_id'     => $this->owner->id,
            'actor_name'   => 'Owner',
            'action'       => 'settings.updated',
            'target_type'  => 'SystemSetting',
            'target_id'    => null,
            'target_label' => 'Cấu hình hệ thống',
        ]);

        // Lọc trong khoảng [Hôm qua, Hôm nay]
        $resRange = $this->actingAs($this->auditor, 'admin')
            ->getJson('/api/admin/audit-logs?' . http_build_query([
                'date_from' => Carbon::now()->subDays(1)->toDateString(),
                'date_to'   => Carbon::now()->toDateString(),
            ]));

        $resRange->assertStatus(200);
        $ids = collect($resRange->json('data'))->pluck('id')->all();

        $this->assertContains($logToday->id, $ids);
        $this->assertContains($logYesterday->id, $ids);
        $this->assertNotContains($logOld->id, $ids);

        // Kiểm tra alias start_date & end_date
        $resAlias = $this->actingAs($this->auditor, 'admin')
            ->getJson('/api/admin/audit-logs?' . http_build_query([
                'start_date' => Carbon::now()->toDateString(),
                'end_date'   => Carbon::now()->toDateString(),
            ]));

        $resAlias->assertStatus(200);
        $aliasIds = collect($resAlias->json('data'))->pluck('id')->all();
        $this->assertContains($logToday->id, $aliasIds);
        $this->assertNotContains($logYesterday->id, $aliasIds);
    }

    /**
     * Test 3: Phân trang (Pagination) và giới hạn per_page an toàn.
     */
    public function test_audit_logs_pagination_and_per_page_bounds(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            AuditLog::create([
                'actor_type'   => 'admin',
                'actor_id'     => $this->owner->id,
                'actor_name'   => 'Owner',
                'action'       => 'booking.created',
                'target_type'  => 'Booking',
                'target_id'    => (string) $i,
                'target_label' => "Đơn đặt xe #{$i}",
            ]);
        }

        // Trang 1 với per_page = 10
        $resPage1 = $this->actingAs($this->auditor, 'admin')
            ->getJson('/api/admin/audit-logs?per_page=10&page=1');

        $resPage1->assertStatus(200)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.per_page', 10)
            ->assertJsonPath('pagination.total', 25)
            ->assertJsonPath('pagination.last_page', 3);

        $this->assertCount(10, $resPage1->json('data'));

        // Trang 2
        $resPage2 = $this->actingAs($this->auditor, 'admin')
            ->getJson('/api/admin/audit-logs?per_page=10&page=2');

        $resPage2->assertStatus(200)
            ->assertJsonPath('pagination.current_page', 2);
        $this->assertCount(10, $resPage2->json('data'));

        // Trang 3 (còn lại 5 bản ghi)
        $resPage3 = $this->actingAs($this->auditor, 'admin')
            ->getJson('/api/admin/audit-logs?per_page=10&page=3');

        $resPage3->assertStatus(200);
        $this->assertCount(5, $resPage3->json('data'));

        // Nhập per_page tiêu cực hoặc quá lớn (>100) -> Tự giới hạn về 15
        $resInvalid = $this->actingAs($this->auditor, 'admin')
            ->getJson('/api/admin/audit-logs?per_page=9999');
        $this->assertEquals(15, $resInvalid->json('pagination.per_page'));
    }

    /**
     * Test 4: Khử trùng dữ liệu nhạy cảm (Sanitization):
     * Mật khẩu, token, secret key, cvv KHÔNG BAO GIỜ bị lộ qua response API.
     */
    public function test_audit_logs_sensitive_payload_sanitization(): void
    {
        // Giả sử có một bản ghi audit log chứa thông tin nhạy cảm
        $sensitiveLog = AuditLog::create([
            'actor_type'   => 'admin',
            'actor_id'     => $this->owner->id,
            'actor_name'   => 'Owner',
            'action'       => 'admin.password_reset',
            'target_type'  => 'Admin',
            'target_id'    => '2',
            'target_label' => 'Reset mật khẩu nhân viên',
            'old_values'   => [
                'ho_ten'          => 'Staff One',
                'password'        => '$2y$12$e0MYzXy638dSecretHash1234567890',
                'remember_token'  => 'secret_remember_token_abc123',
                'api_token'       => 'Bearer plain_token_9999',
            ],
            'new_values'   => [
                'ho_ten'          => 'Staff One Renamed',
                'password'        => '$2y$12$newPlaintextPasswordHash9999',
                'stripe_secret'   => 'sk_live_very_secret_key_123',
                'customer_cvv'    => '123',
            ],
        ]);

        $res = $this->actingAs($this->auditor, 'admin')
            ->getJson('/api/admin/audit-logs?search=Reset');

        $res->assertStatus(200);
        $data = $res->json('data');
        $this->assertNotEmpty($data);

        $returnedLog = collect($data)->firstWhere('id', $sensitiveLog->id);
        $this->assertNotNull($returnedLog);

        // Kiểm tra old_values đã bị [REDACTED]
        $this->assertEquals('Staff One', $returnedLog['old_values']['ho_ten']);
        $this->assertEquals('[REDACTED]', $returnedLog['old_values']['password']);
        $this->assertEquals('[REDACTED]', $returnedLog['old_values']['remember_token']);
        $this->assertEquals('[REDACTED]', $returnedLog['old_values']['api_token']);

        // Kiểm tra new_values đã bị [REDACTED]
        $this->assertEquals('Staff One Renamed', $returnedLog['new_values']['ho_ten']);
        $this->assertEquals('[REDACTED]', $returnedLog['new_values']['password']);
        $this->assertEquals('[REDACTED]', $returnedLog['new_values']['stripe_secret']);
        $this->assertEquals('[REDACTED]', $returnedLog['new_values']['customer_cvv']);

        // Khẳng định toàn bộ chuỗi response không chứa chuỗi mật khẩu/token nguyên thủy
        $rawContent = $res->getContent();
        $this->assertStringNotContainsString('$2y$12$e0MYzXy638dSecretHash', $rawContent);
        $this->assertStringNotContainsString('sk_live_very_secret_key_123', $rawContent);
    }

    /**
     * Test 5: Lọc theo hành động (action), actor_type, target_type và tìm kiếm từ khóa.
     */
    public function test_audit_logs_advanced_filters(): void
    {
        // Log 1: Booking
        AuditLog::create([
            'actor_type'   => 'user',
            'actor_id'     => 10,
            'actor_name'   => 'Nguyen Van Khach',
            'actor_email'  => 'khach@gmail.com',
            'action'       => 'booking.created',
            'target_type'  => 'Booking',
            'target_id'    => '501',
            'target_label' => 'Đơn #BK-501',
        ]);

        // Log 2: Car
        AuditLog::create([
            'actor_type'   => 'admin',
            'actor_id'     => $this->owner->id,
            'actor_name'   => 'Admin Boss',
            'actor_email'  => 'boss@driverentals.com',
            'action'       => 'car.updated',
            'target_type'  => 'Car',
            'target_id'    => '88',
            'target_label' => 'VinFast VF9 Plus',
        ]);

        // 1. Lọc theo action = booking.created
        $resAction = $this->actingAs($this->auditor, 'admin')
            ->getJson('/api/admin/audit-logs?action=booking.created');
        $resAction->assertStatus(200);
        $this->assertEquals(1, $resAction->json('pagination.total'));
        $this->assertEquals('booking.created', $resAction->json('data.0.action'));

        // 2. Lọc theo actor_type = user
        $resActor = $this->actingAs($this->auditor, 'admin')
            ->getJson('/api/admin/audit-logs?actor_type=user');
        $resActor->assertStatus(200);
        $this->assertEquals('Nguyen Van Khach', $resActor->json('data.0.actor_name'));

        // 3. Lọc theo target_type = Car
        $resTarget = $this->actingAs($this->auditor, 'admin')
            ->getJson('/api/admin/audit-logs?target_type=Car');
        $resTarget->assertStatus(200);
        $this->assertEquals('VinFast VF9 Plus', $resTarget->json('data.0.target_label'));

        // 4. Tìm kiếm từ khóa tự do (email hoặc tên xe)
        $resSearch = $this->actingAs($this->auditor, 'admin')
            ->getJson('/api/admin/audit-logs?search=VF9');
        $resSearch->assertStatus(200);
        $this->assertEquals(1, $resSearch->json('pagination.total'));
        $this->assertEquals('VinFast VF9 Plus', $resSearch->json('data.0.target_label'));
    }
}
