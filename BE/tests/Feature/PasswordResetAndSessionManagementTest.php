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

class PasswordResetAndSessionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login:127.0.0.1|test@example.com');
        RateLimiter::clear('forgot-password:127.0.0.1|test@example.com');
    }

    /**
     * Test 1: Quên mật khẩu không tiết lộ email có tồn tại hay không
     */
    public function test_forgot_password_does_not_reveal_if_email_exists()
    {
        Mail::fake();

        // 1a. Email không hề tồn tại trong hệ thống
        $responseNonExistent = $this->postJson('/api/forgot-password', [
            'email'        => 'nonexistent_account@driverent.vn',
            'account_type' => 'customer',
        ]);

        $responseNonExistent->assertStatus(200)
            ->assertJson([
                'status'  => 1,
                'message' => 'Nếu địa chỉ email tồn tại trên hệ thống, chúng tôi đã gửi hướng dẫn khôi phục mật khẩu đến email của bạn.',
            ]);
        Mail::assertNothingSent();

        // 1b. Email có tồn tại thực sự
        $user = User::create([
            'ho_ten'        => 'Khách Hàng Thật',
            'email'         => 'real_customer@driverent.vn',
            'password'      => bcrypt('OldPassword123'),
            'so_dien_thoai' => '0901234567',
            'tinh_trang'    => 1,
        ]);

        $responseReal = $this->postJson('/api/forgot-password', [
            'email'        => 'real_customer@driverent.vn',
            'account_type' => 'customer',
        ]);

        // Phản hồi phải hoàn toàn trùng khớp 100% với trường hợp không tồn tại
        $responseReal->assertStatus(200)
            ->assertJson([
                'status'  => 1,
                'message' => 'Nếu địa chỉ email tồn tại trên hệ thống, chúng tôi đã gửi hướng dẫn khôi phục mật khẩu đến email của bạn.',
            ]);

        // Email giả lập được gửi đi mà không gửi mail thực tế
        Mail::assertSent(ResetPasswordMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->accountType === 'customer';
        });
    }

    /**
     * Test 2: Token reset được lưu dạng hash, không lưu plaintext trong database
     */
    public function test_reset_token_stored_hashed_not_plaintext()
    {
        Mail::fake();

        $user = User::create([
            'ho_ten'        => 'Bảo Mật User',
            'email'         => 'security_test@driverent.vn',
            'password'      => bcrypt('OldPassword123'),
            'so_dien_thoai' => '0901234568',
            'tinh_trang'    => 1,
        ]);

        $this->postJson('/api/forgot-password', [
            'email'        => 'security_test@driverent.vn',
            'account_type' => 'customer',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', 'security_test@driverent.vn')
            ->where('account_type', 'customer')
            ->first();

        $this->assertNotNull($record);
        // Token lưu trong DB phải bắt đầu bằng $2y$ (Bcrypt hash)
        $this->assertStringStartsWith('$2y$', $record->token);
    }

    /**
     * Test 3: Đặt lại mật khẩu bằng token hợp lệ, cập nhật mật khẩu và thu hồi các phiên cũ
     */
    public function test_reset_password_updates_password_and_revokes_old_tokens()
    {
        $user = User::create([
            'ho_ten'        => 'Nguyễn Văn Đặt Lại',
            'email'         => 'reset_success@driverent.vn',
            'password'      => bcrypt('OldPassword123'),
            'so_dien_thoai' => '0901234569',
            'tinh_trang'    => 1,
        ]);

        // Tạo 2 token đăng nhập cũ của user trên các thiết bị khác
        $oldToken1 = $user->createToken('device_desktop')->plainTextToken;
        $oldToken2 = $user->createToken('device_mobile')->plainTextToken;
        $this->assertEquals(2, $user->tokens()->count());

        // Sinh token đặt lại mật khẩu
        $rawToken = 'SECURE_RANDOM_TOKEN_XYZ_1234567890';
        DB::table('password_reset_tokens')->insert([
            'email'        => 'reset_success@driverent.vn',
            'account_type' => 'customer',
            'token'        => Hash::make($rawToken),
            'created_at'   => now(),
        ]);

        // Thực hiện đặt lại mật khẩu
        $response = $this->postJson('/api/reset-password', [
            'email'                 => 'reset_success@driverent.vn',
            'token'                 => $rawToken,
            'password'              => 'NewSecurePass2026',
            'password_confirmation' => 'NewSecurePass2026',
            'account_type'          => 'customer',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 1,
            ]);

        // Kiểm tra mật khẩu mới đăng nhập được, mật khẩu cũ bị từ chối
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePass2026', $user->password));
        $this->assertFalse(Hash::check('OldPassword123', $user->password));

        // Chính sách thu hồi: 2 token cũ trên thiết bị khác đã bị thu hồi
        // Chỉ còn lại 1 token mới duy nhất được cấp cho phiên vừa reset
        $this->assertEquals(1, $user->tokens()->count());
    }

    /**
     * Test 4: Token một lần không thể dùng lại lần thứ hai
     */
    public function test_reset_token_cannot_be_reused()
    {
        $user = User::create([
            'ho_ten'        => 'Người Dùng Test',
            'email'         => 'single_use@driverent.vn',
            'password'      => bcrypt('OldPassword123'),
            'so_dien_thoai' => '0901234570',
            'tinh_trang'    => 1,
        ]);

        $rawToken = 'SINGLE_USE_TOKEN_ABCD_999999';
        DB::table('password_reset_tokens')->insert([
            'email'        => 'single_use@driverent.vn',
            'account_type' => 'customer',
            'token'        => Hash::make($rawToken),
            'created_at'   => now(),
        ]);

        // Lần 1: Thành công
        $res1 = $this->postJson('/api/reset-password', [
            'email'                 => 'single_use@driverent.vn',
            'token'                 => $rawToken,
            'password'              => 'FirstNewPass2026',
            'password_confirmation' => 'FirstNewPass2026',
            'account_type'          => 'customer',
        ]);
        $res1->assertStatus(200);

        // Lần 2 với cùng token đó: Bị từ chối HTTP 400
        $res2 = $this->postJson('/api/reset-password', [
            'email'                 => 'single_use@driverent.vn',
            'token'                 => $rawToken,
            'password'              => 'SecondNewPass2026',
            'password_confirmation' => 'SecondNewPass2026',
            'account_type'          => 'customer',
        ]);
        $res2->assertStatus(400)
            ->assertJson([
                'status'  => 0,
                'message' => 'Mã đặt lại mật khẩu không hợp lệ hoặc đã được sử dụng!',
            ]);
    }

    /**
     * Test 5: Token hết hạn (quá 60 phút) bị từ chối
     */
    public function test_expired_token_is_rejected()
    {
        $user = User::create([
            'ho_ten'        => 'Người Dùng Test Hết Hạn',
            'email'         => 'expired_token@driverent.vn',
            'password'      => bcrypt('OldPassword123'),
            'so_dien_thoai' => '0901234571',
            'tinh_trang'    => 1,
        ]);

        $rawToken = 'EXPIRED_TOKEN_123';
        DB::table('password_reset_tokens')->insert([
            'email'        => 'expired_token@driverent.vn',
            'account_type' => 'customer',
            'token'        => Hash::make($rawToken),
            'created_at'   => Carbon::now()->subMinutes(61), // Tạo từ 61 phút trước
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email'                 => 'expired_token@driverent.vn',
            'token'                 => $rawToken,
            'password'              => 'NewPasswordValid123',
            'password_confirmation' => 'NewPasswordValid123',
            'account_type'          => 'customer',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 0,
            ]);
    }

    /**
     * Test 6: Đổi mật khẩu khi đăng nhập phải kiểm tra mật khẩu hiện tại
     */
    public function test_change_password_requires_correct_current_password()
    {
        $user = User::create([
            'ho_ten'        => 'Đổi Mật Khẩu User',
            'email'         => 'change_pass@driverent.vn',
            'password'      => bcrypt('CurrentSecret123'),
            'so_dien_thoai' => '0901234572',
            'tinh_trang'    => 1,
        ]);

        $token = $user->createToken('test_auth')->plainTextToken;

        // 6a. Mật khẩu hiện tại sai -> 422
        $resWrong = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/change-password', [
                'current_password'      => 'WrongPassword999',
                'password'              => 'BrandNewPass2026',
                'password_confirmation' => 'BrandNewPass2026',
            ]);

        $resWrong->assertStatus(422)
            ->assertJson([
                'status'  => 0,
                'message' => 'Mật khẩu hiện tại không chính xác!',
            ]);

        // 6b. Mật khẩu hiện tại đúng -> 200
        $resCorrect = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/change-password', [
                'current_password'      => 'CurrentSecret123',
                'password'              => 'BrandNewPass2026',
                'password_confirmation' => 'BrandNewPass2026',
            ]);

        $resCorrect->assertStatus(200)
            ->assertJson([
                'status'  => 1,
                'message' => 'Đổi mật khẩu thành công! Các phiên đăng nhập trên thiết bị khác đã được thu hồi an toàn.',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('BrandNewPass2026', $user->password));
    }

    /**
     * Test 7: Phân biệt rõ loại tài khoản khi User và Admin có chung email
     */
    public function test_distinguish_user_and_admin_with_same_email()
    {
        $commonEmail = 'duality_email@driverent.vn';

        $admin = Admin::create([
            'ho_ten'        => 'Quản Trị Viên Duality',
            'email'         => $commonEmail,
            'password'      => bcrypt('AdminOriginal123'),
            'so_dien_thoai' => '0901234573',
            'tinh_trang'    => 1,
        ]);

        $customer = User::create([
            'ho_ten'        => 'Khách Hàng Duality',
            'email'         => $commonEmail,
            'password'      => bcrypt('CustomerOriginal123'),
            'so_dien_thoai' => '0901234574',
            'tinh_trang'    => 1,
        ]);

        $rawToken = 'TOKEN_FOR_CUSTOMER_ONLY_111';
        DB::table('password_reset_tokens')->insert([
            'email'        => $commonEmail,
            'account_type' => 'customer',
            'token'        => Hash::make($rawToken),
            'created_at'   => now(),
        ]);

        // Reset với account_type = 'customer'
        $response = $this->postJson('/api/reset-password', [
            'email'                 => $commonEmail,
            'token'                 => $rawToken,
            'password'              => 'CustomerNewPass2026',
            'password_confirmation' => 'CustomerNewPass2026',
            'account_type'          => 'customer',
        ]);
        $response->assertStatus(200);

        $customer->refresh();
        $admin->refresh();

        // Mật khẩu của Customer thay đổi
        $this->assertTrue(Hash::check('CustomerNewPass2026', $customer->password));
        // Mật khẩu của Admin KHÔNG HỀ BỊ THAY ĐỔI
        $this->assertTrue(Hash::check('AdminOriginal123', $admin->password));
        $this->assertFalse(Hash::check('CustomerNewPass2026', $admin->password));
    }

    /**
     * Test 8: Tài khoản bị khóa không tiếp tục dùng token cũ để gọi API được bảo vệ
     */
    public function test_locked_account_cannot_call_protected_api_with_old_token()
    {
        $user = User::create([
            'ho_ten'        => 'Người Dùng Bị Khóa',
            'email'         => 'to_be_locked@driverent.vn',
            'password'      => bcrypt('Secret123'),
            'so_dien_thoai' => '0901234575',
            'tinh_trang'    => 1,
        ]);

        // User đăng nhập và có token
        $token = $user->createToken('active_session')->plainTextToken;

        // Gọi API khi tài khoản còn hoạt động -> 200
        $resActive = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/check-login');
        $resActive->assertStatus(200);

        // Quản trị viên khóa tài khoản
        $user->tinh_trang = 0;
        $user->save();

        // Lần gọi tiếp theo với token cũ -> Bị từ chối HTTP 403 và token bị thu hồi
        $resLocked = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/check-login');
        $resLocked->assertStatus(403)
            ->assertJson([
                'status'  => 0,
                'message' => 'Tài khoản của bạn đã bị khóa hoặc chưa được kích hoạt. Phiên làm việc đã bị thu hồi!',
            ]);

        // Xác nhận token đã bị xóa khỏi cơ sở dữ liệu
        $this->assertEquals(0, $user->tokens()->count());
    }

    /**
     * Test 9: Phân biệt đăng xuất phiên hiện tại với đăng xuất mọi thiết bị
     */
    public function test_logout_current_session_vs_logout_all_devices()
    {
        $user = User::create([
            'ho_ten'        => 'Multi Device User',
            'email'         => 'multi_device@driverent.vn',
            'password'      => bcrypt('Secret123'),
            'so_dien_thoai' => '0901234576',
            'tinh_trang'    => 1,
        ]);

        $tokenDeviceA = $user->createToken('phone_token')->plainTextToken;
        $tokenDeviceB = $user->createToken('laptop_token')->plainTextToken;
        $this->assertEquals(2, $user->tokens()->count());

        // Đăng xuất từ thiết bị A (/logout) -> Chỉ thu hồi tokenDeviceA
        $resLogout = $this->withHeader('Authorization', 'Bearer ' . $tokenDeviceA)
            ->postJson('/api/logout');
        $resLogout->assertStatus(200);

        // Thiết bị B vẫn còn token hợp lệ
        $this->assertEquals(1, $user->tokens()->count());
        $resCheckB = $this->withHeader('Authorization', 'Bearer ' . $tokenDeviceB)
            ->getJson('/api/check-login');
        $resCheckB->assertStatus(200);

        // Thiết bị B thực hiện đăng xuất tất cả (/logout-all) -> Thu hồi toàn bộ
        $resLogoutAll = $this->withHeader('Authorization', 'Bearer ' . $tokenDeviceB)
            ->postJson('/api/logout-all');
        $resLogoutAll->assertStatus(200);

        $this->assertEquals(0, $user->tokens()->count());
    }

    /**
     * Test 10: Rate limit khi gửi yêu cầu quên mật khẩu liên tục
     */
    public function test_forgot_password_rate_limiting()
    {
        Mail::fake();

        // Gửi 3 yêu cầu đầu tiên -> 200
        for ($i = 0; $i < 3; $i++) {
            $res = $this->postJson('/api/forgot-password', [
                'email'        => 'ratelimit@driverent.vn',
                'account_type' => 'customer',
            ]);
            $res->assertStatus(200);
        }

        // Lần thứ 4 trong vòng 1 phút -> Bị chặn 429 Too Many Requests
        $resBlocked = $this->postJson('/api/forgot-password', [
            'email'        => 'ratelimit@driverent.vn',
            'account_type' => 'customer',
        ]);
        $resBlocked->assertStatus(429);
    }
}
