<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminCustomerCreateRequest;
use App\Http\Requests\Admin\AdminCustomerSearchRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\UserActionIdRequest;
use App\Http\Requests\Auth\UserLoginRequest;
use App\Http\Requests\Auth\UserRegisterRequest;
use App\Mail\ResetPasswordMail;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(UserLoginRequest $request)
    {
        $credentials = $request->validated();
        $throttleKey = 'login:' . $request->ip() . '|' . Str::lower($credentials['email']);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'status'  => 0,
                'message' => "Bạn đã thử đăng nhập quá nhiều lần. Vui lòng thử lại sau {$seconds} giây!",
            ], 429);
        }

        // 1. Check in Admin table
        $admin = Admin::where('email', $credentials['email'])->first();
        if ($admin && Hash::check($credentials['password'], $admin->password)) {
            RateLimiter::clear($throttleKey);

            if ((int) ($admin->tinh_trang ?? 1) === 0 || (int) ($admin->is_active ?? 1) === 0) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Tài khoản Admin đã bị khóa!',
                ], 403);
            }

            return response()->json([
                'status'  => 1,
                'message' => 'Đăng nhập Admin thành công!',
                'token'   => $admin->createToken('token_admin')->plainTextToken,
                'user'    => [
                    'id'            => $admin->id,
                    'ho_ten'        => $admin->ho_ten,
                    'role'          => 'admin',
                    'admin_role'    => $admin->role,
                    'permissions'   => $admin->all_permissions,
                    'email'         => $admin->email,
                    'so_dien_thoai' => $admin->so_dien_thoai,
                    'dia_chi'       => $admin->dia_chi,
                ],
            ]);
        }

        // 2. Check in User (Customer) table
        $user = User::where('email', $credentials['email'])->first();
        if ($user && Hash::check($credentials['password'], $user->password)) {
            RateLimiter::clear($throttleKey);

            if ((int) ($user->tinh_trang ?? 1) === 0) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Tài khoản chưa được kích hoạt hoặc đã bị khóa!',
                ], 403);
            }

            return response()->json([
                'status'  => 1,
                'message' => 'Đăng nhập thành công!',
                'token'   => $user->createToken('token_client')->plainTextToken,
                'user'    => [
                    'id'            => $user->id,
                    'ho_ten'        => $user->ho_ten,
                    'role'          => 'customer',
                    'email'         => $user->email,
                    'so_dien_thoai' => $user->so_dien_thoai,
                    'dia_chi'       => $user->dia_chi,
                    'ngay_sinh'     => $user->ngay_sinh,
                ],
            ]);
        }

        RateLimiter::hit($throttleKey, 60);

        return response()->json([
            'status'  => 0,
            'message' => 'Tài khoản hoặc mật khẩu không đúng!',
        ], 401);
    }

    public function register(UserRegisterRequest $request)
    {
        try {
            $validated = $request->validated();

            // Safe atomic database transaction with no transaction leak on early return
            $user = DB::transaction(function () use ($validated) {
                return User::create([
                    'ho_ten'        => $validated['ho_ten'],
                    'email'         => $validated['email'],
                    'password'      => Hash::make($validated['password']),
                    'so_dien_thoai' => $validated['so_dien_thoai'],
                    'ngay_sinh'     => $validated['ngay_sinh'] ?? null,
                    'gioi_tinh'     => $validated['gioi_tinh'] ?? 1,
                    'dia_chi'       => $validated['dia_chi'] ?? null,
                    'tinh_trang'    => 1, // Auto-activate
                    'hash_active'   => (string) Str::uuid(),
                ]);
            });

            return response()->json([
                'status'  => 1,
                'message' => 'Đăng ký thành công! Vui lòng kích hoạt tài khoản.',
                'data'    => [
                    'id'     => $user->id,
                    'ho_ten' => $user->ho_ten,
                    'email'  => $user->email,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('User register error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra trong quá trình đăng ký. Vui lòng thử lại sau!',
            ], 500);
        }
    }

    public function active($hash)
    {
        $user = User::where('hash_active', $hash)->first();
        if ($user) {
            $user->tinh_trang = 1;
            $user->save();
            return response()->json([
                'status'  => 1,
                'message' => 'Kích hoạt tài khoản thành công!',
            ]);
        }

        return response()->json([
            'status'  => 0,
            'message' => 'Mã kích hoạt tài khoản không tồn tại hoặc đã hết hạn!',
        ], 404);
    }

    public function checkLogin()
    {
        $user = Auth::guard('admin')->user() ?? Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'status' => 0,
                'user'   => null,
            ]);
        }

        $isAdmin = $user instanceof Admin;
        return response()->json([
            'status' => 1,
            'user'   => [
                'id'            => $user->id,
                'ho_ten'        => $user->ho_ten,
                'email'         => $user->email,
                'role'          => $isAdmin ? 'admin' : 'customer',
                'admin_role'    => $isAdmin ? $user->role : null,
                'permissions'   => $isAdmin ? $user->all_permissions : [],
                'so_dien_thoai' => $user->so_dien_thoai ?? null,
                'dia_chi'       => $user->dia_chi ?? null,
            ],
        ]);
    }

    public function logout()
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
        if ($user) {
            $user->currentAccessToken()?->delete();
        }
        return response()->json([
            'status'  => 1,
            'message' => 'Đăng xuất khỏi thiết bị hiện tại thành công!',
        ]);
    }

    public function logoutAll()
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
        if ($user) {
            $user->tokens()->delete();
        }
        return response()->json([
            'status'  => 1,
            'message' => 'Đã đăng xuất khỏi tất cả các thiết bị thành công!',
        ]);
    }

    public function getProfile()
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn cần đăng nhập để xem thông tin hồ sơ!',
            ], 401);
        }

        return response()->json([
            'status' => 1,
            'data'   => $user,
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn cần đăng nhập để cập nhật hồ sơ!',
            ], 401);
        }

        try {
            $validated = $request->validated();
            $user->update([
                'ho_ten'        => $validated['ho_ten'],
                'so_dien_thoai' => $validated['so_dien_thoai'],
                'ngay_sinh'     => $validated['ngay_sinh'] ?? $user->ngay_sinh,
                'gioi_tinh'     => $validated['gioi_tinh'] ?? $user->gioi_tinh,
                'dia_chi'       => $validated['dia_chi'] ?? $user->dia_chi,
            ]);

            return response()->json([
                'status'  => 1,
                'message' => 'Cập nhật thông tin thành công!',
                'data'    => $user->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Update profile error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi cập nhật hồ sơ!',
            ], 500);
        }
    }

    public function doiMatKhau(ChangePasswordRequest $request)
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn cần đăng nhập để đổi mật khẩu!',
            ], 401);
        }

        try {
            $validated = $request->validated();

            // 1. Kiểm tra mật khẩu hiện tại (trừ trường hợp tài khoản mạng xã hội chưa từng đặt mật khẩu)
            if (!empty($user->password)) {
                if (empty($validated['current_password']) || !Hash::check($validated['current_password'], $user->password)) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Mật khẩu hiện tại không chính xác!',
                    ], 422);
                }

                if (Hash::check($validated['password'], $user->password)) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Mật khẩu mới không được trùng với mật khẩu hiện tại!',
                    ], 422);
                }
            }

            // 2. Cập nhật mật khẩu mới
            $user->password = Hash::make($validated['password']);
            $user->save();

            // 3. Chính sách thu hồi phiên: Mặc định thu hồi tất cả các phiên trên thiết bị khác để bảo đảm an toàn
            $revokeOthers = $validated['revoke_other_sessions'] ?? true;
            if ($revokeOthers) {
                $currentTokenId = $user->currentAccessToken()?->id;
                if ($currentTokenId) {
                    $user->tokens()->where('id', '!=', $currentTokenId)->delete();
                }
            }

            return response()->json([
                'status'  => 1,
                'message' => 'Đổi mật khẩu thành công! Các phiên đăng nhập trên thiết bị khác đã được thu hồi an toàn.',
            ]);
        } catch (\Exception $e) {
            Log::error('Change password error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi đổi mật khẩu!',
            ], 500);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $email = Str::lower(trim($request->email));
        $accountType = $request->account_type ?? 'customer';
        $throttleKey = 'forgot-password:' . $request->ip() . '|' . $email;

        // Rate limit: tối đa 3 lần trong 60 giây
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'status'  => 0,
                'message' => "Bạn đã gửi yêu cầu quá thường xuyên. Vui lòng thử lại sau {$seconds} giây!",
            ], 429);
        }
        RateLimiter::hit($throttleKey, 60);

        // Tìm tài khoản theo đúng loại tài khoản chỉ định để không gửi nhầm giữa customer và admin
        $account = ($accountType === 'admin')
            ? Admin::where('email', $email)->first()
            : User::where('email', $email)->first();

        // Chỉ tạo token nếu tài khoản tồn tại và không bị khóa
        if ($account && (int) ($account->tinh_trang ?? 1) !== 0 && (int) ($account->is_active ?? 1) !== 0) {
            $rawToken = Str::random(64);
            $hashedToken = Hash::make($rawToken);

            DB::table('password_reset_tokens')->updateOrInsert(
                [
                    'email'        => $email,
                    'account_type' => $accountType,
                ],
                [
                    'token'      => $hashedToken,
                    'created_at' => now(),
                ]
            );

            $feUrl = env('FRONTEND_URL', 'http://localhost:5173');
            $resetUrl = "{$feUrl}/reset-password?token={$rawToken}&email=" . urlencode($email) . "&type={$accountType}";

            try {
                Mail::to($email)->send(new ResetPasswordMail($resetUrl, $account->ho_ten, $accountType, 60));
            } catch (\Exception $e) {
                Log::error('Send password reset email failed: ' . $e->getMessage());
            }
        }

        // Luôn trả về phản hồi đồng nhất để không tiết lộ thông tin email có tồn tại hay không
        return response()->json([
            'status'  => 1,
            'message' => 'Nếu địa chỉ email tồn tại trên hệ thống, chúng tôi đã gửi hướng dẫn khôi phục mật khẩu đến email của bạn.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $email = Str::lower(trim($request->email));
        $accountType = $request->account_type ?? 'customer';

        // 1. Tìm bản ghi token theo email và loại tài khoản
        $record = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('account_type', $accountType)
            ->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Mã đặt lại mật khẩu không hợp lệ hoặc đã được sử dụng!',
            ], 400);
        }

        // 2. Kiểm tra thời hạn hiệu lực (60 phút)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->where('account_type', $accountType)
                ->delete();

            return response()->json([
                'status'  => 0,
                'message' => 'Mã đặt lại mật khẩu đã hết hạn (chỉ có hiệu lực trong 60 phút). Vui lòng gửi lại yêu cầu mới!',
            ], 400);
        }

        // 3. Tìm tài khoản tương ứng
        $model = ($accountType === 'admin') ? Admin::class : User::class;
        $account = $model::where('email', $email)->first();

        if (!$account) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không tìm thấy tài khoản tương ứng!',
            ], 404);
        }

        if ((int) ($account->tinh_trang ?? 1) === 0 || (int) ($account->is_active ?? 1) === 0) {
            return response()->json([
                'status'  => 0,
                'message' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên!',
            ], 403);
        }

        // 4. Cập nhật mật khẩu mới
        $account->password = Hash::make($request->password);
        $account->save();

        // 5. Xóa token ngay lập tức (Bảo đảm token chỉ dùng một lần duy nhất)
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('account_type', $accountType)
            ->delete();

        // 6. Chính sách thu hồi phiên: Hủy toàn bộ token cũ trên tất cả thiết bị
        $account->tokens()->delete();

        // 7. Cấp token mới cho phiên hiện tại
        $tokenName = ($accountType === 'admin') ? 'token_admin' : 'token_client';
        $newToken = $account->createToken($tokenName)->plainTextToken;

        $isAdmin = $account instanceof Admin;
        return response()->json([
            'status'  => 1,
            'message' => 'Đặt lại mật khẩu thành công! Tất cả các phiên đăng nhập cũ trên thiết bị khác đã được thu hồi an toàn.',
            'token'   => $newToken,
            'user'    => [
                'id'            => $account->id,
                'ho_ten'        => $account->ho_ten,
                'role'          => $isAdmin ? 'admin' : 'customer',
                'admin_role'    => $isAdmin ? $account->role : null,
                'permissions'   => $isAdmin ? $account->all_permissions : [],
                'email'         => $account->email,
                'so_dien_thoai' => $account->so_dien_thoai ?? null,
                'dia_chi'       => $account->dia_chi ?? null,
            ],
        ]);
    }

    public function getAllUsers()
    {
        $users = User::select('id', 'ho_ten', 'email', 'so_dien_thoai', 'dia_chi', 'tinh_trang as is_active', 'created_at', DB::raw("'user' as role"))
            ->orderByDesc('id')
            ->get();
        $admins = DB::table('admins')
            ->select('id', 'ho_ten', 'email', 'so_dien_thoai', 'dia_chi', 'tinh_trang as is_active', 'created_at', DB::raw("'admin' as role"))
            ->orderByDesc('id')
            ->get();
        
        return response()->json([
            'status' => 1,
            'data'   => $users->concat($admins),
        ]);
    }

    public function statusUser(UserActionIdRequest $request)
    {
        try {
            $user = User::find($request->id);
            if (!$user) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Người dùng không tồn tại trong hệ thống!',
                ], 404);
            }

            $user->tinh_trang = $user->tinh_trang ? 0 : 1;
            $user->save();

            return response()->json([
                'status'  => 1,
                'message' => 'Đã đổi trạng thái người dùng thành công!',
                'data'    => [
                    'id'         => $user->id,
                    'tinh_trang' => $user->tinh_trang,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Status user error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi đổi trạng thái người dùng!',
            ], 500);
        }
    }

    public function searchCustomers(AdminCustomerSearchRequest $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền truy cập dữ liệu khách hàng!'
            ], 403);
        }

        $term = trim($request->get('query', $request->get('q', '')));
        $perPage = (int) $request->get('per_page', 10);
        if ($perPage < 1 || $perPage > 50) {
            $perPage = 10;
        }

        $query = User::query()
            ->select('id', 'ho_ten', 'email', 'so_dien_thoai', 'dia_chi', 'ngay_sinh', 'gioi_tinh', 'tinh_trang', 'created_at');

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('ho_ten', 'like', "%{$term}%")
                  ->orWhere('so_dien_thoai', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $paginated = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'status'     => 1,
            'data'       => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ]
        ]);
    }

    public function quickCreateCustomer(AdminCustomerCreateRequest $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền tạo hồ sơ khách hàng!'
            ], 403);
        }

        try {
            $validated = $request->validated();

            // Tạo chuỗi ngẫu nhiên bảo mật cao nếu admin không nhập mật khẩu (không dùng mật khẩu mặc định công khai)
            $password = !empty($validated['password'])
                ? $validated['password']
                : Str::random(32);

            $user = User::create([
                'ho_ten'        => $validated['ho_ten'],
                'email'         => $validated['email'],
                'password'      => Hash::make($password),
                'so_dien_thoai' => $validated['so_dien_thoai'],
                'dia_chi'       => $validated['dia_chi'] ?? null,
                'ngay_sinh'     => $validated['ngay_sinh'] ?? null,
                'gioi_tinh'     => $validated['gioi_tinh'] ?? 1,
                'tinh_trang'    => 1, // Kích hoạt để có thể đặt xe
                'hash_active'   => (string) Str::uuid(),
            ]);

            return response()->json([
                'status'  => 1,
                'message' => 'Tạo mới hồ sơ khách hàng thành công!',
                'data'    => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Quick create customer error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi tạo hồ sơ khách hàng. Vui lòng thử lại sau!'
            ], 500);
        }
    }
}
