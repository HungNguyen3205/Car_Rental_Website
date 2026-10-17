<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminCustomerCreateRequest;
use App\Http\Requests\Admin\AdminCustomerSearchRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\UserActionIdRequest;
use App\Http\Requests\Auth\UserLoginRequest;
use App\Http\Requests\Auth\UserRegisterRequest;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(UserLoginRequest $request)
    {
        $credentials = $request->validated();

        // 1. Check in Admin table
        $admin = Admin::where('email', $credentials['email'])->first();
        if ($admin && Hash::check($credentials['password'], $admin->password)) {
            if ($admin->tinh_trang == 0) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Tài khoản Admin đã bị bộ khóa!',
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
                    'email'         => $admin->email,
                    'so_dien_thoai' => $admin->so_dien_thoai,
                    'dia_chi'       => $admin->dia_chi,
                ],
            ]);
        }

        // 2. Check in User (Customer) table
        $user = User::where('email', $credentials['email'])->first();
        if ($user && Hash::check($credentials['password'], $user->password)) {
            if ($user->tinh_trang == 0) {
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
        $user = Auth::guard('sanctum')->user();
        return response()->json([
            'status' => $user ? 1 : 0,
            'user'   => $user
        ]);
    }

    public function logout()
    {
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $user->tokens()->delete();
        }
        return response()->json([
            'status'  => 1,
            'message' => 'Đăng xuất thành công!',
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
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn cần đăng nhập để đổi mật khẩu!',
            ], 401);
        }

        try {
            $validated = $request->validated();
            $user->update(['password' => Hash::make($validated['password'])]);

            return response()->json([
                'status'  => 1,
                'message' => 'Đổi mật khẩu thành công!',
            ]);
        } catch (\Exception $e) {
            Log::error('Change password error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi đổi mật khẩu!',
            ], 500);
        }
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
