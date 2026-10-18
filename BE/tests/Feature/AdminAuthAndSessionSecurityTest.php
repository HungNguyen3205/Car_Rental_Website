<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Models\Admin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminAuthAndSessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login:127.0.0.1|admin@driverent.vn');
        RateLimiter::clear('login:127.0.0.1|customer@driverent.vn');
        RateLimiter::clear('login:127.0.0.1|locked_admin@driverent.vn');
        RateLimiter::clear('login:127.0.0.1|locked_customer@driverent.vn');
        RateLimiter::clear('forgot-password:127.0.0.1|admin@driverent.vn');
        RateLimiter::clear('forgot-password:127.0.0.1|unknown@driverent.vn');
    }

    /**
     * Test 1: Đăng nhập quản trị viên thành công, trả về đúng token, vai trò 'admin' và quyền hạn
     */
    public function test_admin_login_succeeds_and_returns_admin_payload()
    {
        $admin = Admin::create([
            'ho_ten'        => 'Admin Trưởng',
            'email'         => 'admin@driverent.vn',
            'password'      => Hash::make('AdminPass123'),
            'role'          => 'owner',
            'so_dien_thoai' => '0901112222',
            'tinh_trang'    => 1,
            'is_active'     => 1,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'admin@driverent.vn',
            'password' => 'AdminPass123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 1,
                'message' => 'Đăng nhập Admin thành công!',
                'user'    => [
                    'id'         => $admin->id,
                    'ho_ten'     => 'Admin Trưởng',
                    'email'      => 'admin@driverent.vn',
                    'role'       => 'admin',
                    'admin_role' => 'owner',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    /**
     * Test 2: Đăng nhập khách hàng bình thường, trả về vai trò 'customer'
     */
    public function test_regular_customer_login_returns_customer_payload()
    {
        $customer = User::create([
            'ho_ten'        => 'Khách Hàng A',
            'email'         => 'customer@driverent.vn',
            'password'      => Hash::make('CustomerPass123'),
            'so_dien_thoai' => '0903334444',
            'tinh_trang'    => 1,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'customer@driverent.vn',
            'password' => 'CustomerPass123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 1,
                'message' => 'Đăng nhập thành công!',
                'user'    => [
                    'id'     => $customer->id,
                    'ho_ten' => 'Khách Hàng A',
                    'email'  => 'customer@driverent.vn',
                    'role'   => 'customer',
                ],
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    /**
     * Test 3: Khách hàng thường không thể truy cập các route quản trị (/api/admin/*)
     */
    public function test_regular_customer_token_cannot_access_admin_endpoints()
    {
        $customer = User::create([
            'ho_ten'        => 'Khách Không Quyền',
            'email'         => 'customer_noperm@driverent.vn',
            'password'      => Hash::make('CustomerPass123'),
            'so_dien_thoai' => '0905556666',
            'tinh_trang'    => 1,
        ]);

        $token = $customer->createToken('token_client')->plainTextToken;

        // Truy cập endpoint quản trị
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/bookings/data');

        // Bị chặn vì guard admin kiểm tra bảng Admin
        $this->assertTrue(in_array($response->status(), [401, 403]));
    }

    /**
     * Test 4: Quản trị viên (owner/dispatcher) có thể truy cập route quản trị thành công
     */
    public function test_admin_token_can_access_admin_endpoints()
    {
        $admin = Admin::create([
            'ho_ten'        => 'Điều Phối Viên',
            'email'         => 'dispatcher@driverent.vn',
            'password'      => Hash::make('AdminPass123'),
            'role'          => 'owner',
            'so_dien_thoai' => '0907778888',
            'tinh_trang'    => 1,
            'is_active'     => 1,
        ]);

        $token = $admin->createToken('token_admin')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/bookings/data');

        $response->assertStatus(200)
            ->assertJson(['status' => 1]);
    }

    /**
     * Test 5: Token hết hạn hoặc không hợp lệ truy cập API được bảo vệ trả về 401
     */
    public function test_expired_or_invalid_token_returns_401()
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid_or_expired_token_12345')
            ->getJson('/api/check-login');

        $response->assertStatus(401);
    }

    /**
     * Test 6: Tài khoản Admin bị khóa (tinh_trang = 0 hoặc is_active = 0) bị chặn đăng nhập với 403
     */
    public function test_locked_admin_account_login_is_rejected_with_403()
    {
        Admin::create([
            'ho_ten'        => 'Admin Bị Khóa',
            'email'         => 'locked_admin@driverent.vn',
            'password'      => Hash::make('AdminPass123'),
            'role'          => 'dispatcher',
            'so_dien_thoai' => '0908889999',
            'tinh_trang'    => 0,
            'is_active'     => 0,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'locked_admin@driverent.vn',
            'password' => 'AdminPass123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status'  => 0,
                'message' => 'Tài khoản Admin đã bị khóa!',
            ]);
    }

    /**
     * Test 7: Tài khoản Khách hàng bị khóa (tinh_trang = 0) bị chặn đăng nhập với 403
     */
    public function test_locked_customer_account_login_is_rejected_with_403()
    {
        User::create([
            'ho_ten'        => 'Khách Hàng Bị Khóa',
            'email'         => 'locked_customer@driverent.vn',
            'password'      => Hash::make('CustomerPass123'),
            'so_dien_thoai' => '0909990000',
            'tinh_trang'    => 0,
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'locked_customer@driverent.vn',
            'password' => 'CustomerPass123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status'  => 0,
                'message' => 'Tài khoản chưa được kích hoạt hoặc đã bị khóa!',
            ]);
    }

    /**
     * Test 8: Khi tài khoản Admin đang hoạt động bị khóa sau đó, token hiện tại bị thu hồi với 403
     */
    public function test_admin_locked_after_login_has_token_revoked_on_next_request()
    {
        $admin = Admin::create([
            'ho_ten'        => 'Admin Sắp Bị Khóa',
            'email'         => 'will_lock_admin@driverent.vn',
            'password'      => Hash::make('AdminPass123'),
            'role'          => 'owner',
            'so_dien_thoai' => '0901113333',
            'tinh_trang'    => 1,
            'is_active'     => 1,
        ]);

        $token = $admin->createToken('token_admin')->plainTextToken;

        // Yêu cầu lần 1 thành công
        $firstResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/check-login');
        $firstResponse->assertStatus(200);

        // Khóa tài khoản trong DB
        $admin->tinh_trang = 0;
        $admin->save();

        // Yêu cầu lần 2 phải bị chặn 403 và thu hồi toàn bộ token
        $secondResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/check-login');
        $secondResponse->assertStatus(403);

        $this->assertEquals(0, $admin->tokens()->count());
    }

    /**
     * Test 9: Quên mật khẩu không tiết lộ email có tồn tại, không gửi mail thật
     */
    public function test_forgot_password_does_not_reveal_existence_and_fakes_mail()
    {
        Mail::fake();

        // Email chưa từng đăng ký
        $respUnknown = $this->postJson('/api/forgot-password', [
            'email'        => 'unknown@driverent.vn',
            'account_type' => 'admin',
        ]);

        $respUnknown->assertStatus(200)
            ->assertJson([
                'status'  => 1,
                'message' => 'Nếu địa chỉ email tồn tại trên hệ thống, chúng tôi đã gửi hướng dẫn khôi phục mật khẩu đến email của bạn.',
            ]);

        Mail::assertNothingSent();

        // Email admin có tồn tại
        Admin::create([
            'ho_ten'        => 'Admin Quên Pass',
            'email'         => 'admin_forgot@driverent.vn',
            'password'      => Hash::make('Pass12345'),
            'role'          => 'owner',
            'so_dien_thoai' => '0902224444',
            'tinh_trang'    => 1,
            'is_active'     => 1,
        ]);

        $respExist = $this->postJson('/api/forgot-password', [
            'email'        => 'admin_forgot@driverent.vn',
            'account_type' => 'admin',
        ]);

        // Phản hồi hoàn toàn tương đồng không thể suy đoán
        $respExist->assertStatus(200)
            ->assertJson([
                'status'  => 1,
                'message' => 'Nếu địa chỉ email tồn tại trên hệ thống, chúng tôi đã gửi hướng dẫn khôi phục mật khẩu đến email của bạn.',
            ]);

        // Mail được giả lập gửi đi an toàn
        Mail::assertSent(ResetPasswordMail::class, function ($mail) {
            return $mail->hasTo('admin_forgot@driverent.vn') && $mail->accountType === 'admin';
        });
    }

    /**
     * Test 10: Mã token reset mật khẩu hết hạn sau 60 phút sẽ bị từ chối với 400
     */
    public function test_reset_password_token_expired_after_60_minutes_is_rejected()
    {
        Admin::create([
            'ho_ten'        => 'Admin Reset Expiry',
            'email'         => 'admin_expiry@driverent.vn',
            'password'      => Hash::make('OldPass123'),
            'role'          => 'owner',
            'so_dien_thoai' => '0903335555',
            'tinh_trang'    => 1,
            'is_active'     => 1,
        ]);

        $rawToken = 'raw_reset_token_60_min';
        DB::table('password_reset_tokens')->insert([
            'email'        => 'admin_expiry@driverent.vn',
            'token'        => Hash::make($rawToken),
            'account_type' => 'admin',
            'created_at'   => Carbon::now()->subMinutes(61), // Quá hạn 61 phút
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email'                 => 'admin_expiry@driverent.vn',
            'token'                 => $rawToken,
            'password'              => 'NewSecurePass123',
            'password_confirmation' => 'NewSecurePass123',
            'account_type'          => 'admin',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status'  => 0,
                'message' => 'Mã đặt lại mật khẩu đã hết hạn (chỉ có hiệu lực trong 60 phút). Vui lòng gửi lại yêu cầu mới!',
            ]);
    }

    /**
     * Test 11: Endpoint /check-login xác nhận vai trò admin chính xác
     */
    public function test_check_login_identifies_admin()
    {
        $admin = Admin::create([
            'ho_ten'        => 'Admin Check',
            'email'         => 'admin_check@driverent.vn',
            'password'      => Hash::make('Pass12345'),
            'role'          => 'owner',
            'so_dien_thoai' => '0904446666',
            'tinh_trang'    => 1,
            'is_active'     => 1,
        ]);

        $adminToken = $admin->createToken('token_admin')->plainTextToken;

        $adminResp = $this->withHeader('Authorization', 'Bearer ' . $adminToken)
            ->getJson('/api/check-login');
        $adminResp->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'user'   => [
                    'role'       => 'admin',
                    'admin_role' => 'owner',
                ],
            ]);
    }

    /**
     * Test 12: Endpoint /check-login xác nhận vai trò customer chính xác
     */
    public function test_check_login_identifies_customer()
    {
        $customer = User::create([
            'ho_ten'        => 'Customer Check',
            'email'         => 'customer_check@driverent.vn',
            'password'      => Hash::make('Pass12345'),
            'so_dien_thoai' => '0905557777',
            'tinh_trang'    => 1,
        ]);

        $customerToken = $customer->createToken('token_client')->plainTextToken;

        $customerResp = $this->withHeader('Authorization', 'Bearer ' . $customerToken)
            ->getJson('/api/check-login');
        $customerResp->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'user'   => [
                    'role'       => 'customer',
                    'admin_role' => null,
                ],
            ]);
    }
}
