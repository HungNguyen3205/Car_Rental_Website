<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Check in Admin table
        $admin = Admin::where('email', $request->email)->first();
        if ($admin && Hash::check($request->password, $admin->password)) {
            if ($admin->tinh_trang == 0) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Tài khoản Admin đã bị bộ khóa!',
                ]);
            }

            return response()->json([
                'status'  => 1,
                'message' => 'Đăng nhập Admin thành công!',
                'token'   => $admin->createToken('token_admin')->plainTextToken,
                'user'    => [
                    'ho_ten' => $admin->ho_ten,
                    'role'   => 'admin',
                    'email'  => $admin->email
                ],
            ]);
        }

        // 2. Check in User (Customer) table
        $user = User::where('email', $request->email)->first();
        if ($user && Hash::check($request->password, $user->password)) {
            if ($user->tinh_trang == 0) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Tài khoản chưa được kích hoạt hoặc đã bị khóa!',
                ]);
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
                    'so_dien_thoai'  => $user->so_dien_thoai,
                    'dia_chi'       => $user->dia_chi,
                    'ngay_sinh'     => $user->ngay_sinh,
                ],
            ]);
        }

        return response()->json([
            'status'  => 0,
            'message' => 'Tài khoản hoặc mật khẩu không đúng!',
        ]);
    }

    public function register(Request $request)
    {
        DB::beginTransaction();
        try {
            $check_email = User::where('email', $request->email)->first();
            if ($check_email) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Email đã tồn tại!',
                ]);
            }

            $user = User::create([
                'ho_ten'          => $request->ho_ten,
                'email'           => $request->email,
                'password'        => Hash::make($request->password),
                'so_dien_thoai'   => $request->so_dien_thoai,
                'ngay_sinh'       => $request->ngay_sinh,
                'gioi_tinh'       => $request->gioi_tinh ?? 1,
                'dia_chi'         => $request->dia_chi,
                'tinh_trang'      => 1, // Auto-activate for convenience
                'hash_active'     => Str::uuid(),
            ]);

            DB::commit();
            return response()->json([
                'status'  => 1,
                'message' => 'Đăng ký thành công! Vui lòng kích hoạt tài khoản.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ]);
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
        $user = Auth::guard('sanctum')->user();
        return response()->json([
            'status' => 1,
            'data'   => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $user->update($request->only(['ho_ten', 'so_dien_thoai', 'ngay_sinh', 'gioi_tinh', 'dia_chi']));
            return response()->json([
                'status'  => 1,
                'message' => 'Cập nhật thông tin thành công!',
            ]);
        }
    }

    public function doiMatKhau(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $user->update(['password' => Hash::make($request->password)]);
            return response()->json([
                'status'  => 1,
                'message' => 'Đổi mật khẩu thành công!',
            ]);
        }
    }

    public function getAllUsers()
    {
        $users = User::select('id', 'ho_ten', 'email', 'so_dien_thoai', 'tinh_trang as is_active', 'created_at', DB::raw("'user' as role"))->get();
        // Since Admins might not have all fields, we handle them carefully
        $admins = DB::table('admins')->select('id', 'ho_ten', 'email', DB::raw("'' as so_dien_thoai"), 'tinh_trang as is_active', 'created_at', DB::raw("'admin' as role"))->get();
        
        return response()->json([
            'status' => 1,
            'data'   => $users->concat($admins),
        ]);
    }

    public function statusUser(Request $request)
    {
        $user = User::find($request->id);
        if ($user) {
            $user->tinh_trang = !$user->tinh_trang;
            $user->save();
            return response()->json([
                'status'  => 1,
                'message' => 'Đã đổi trạng thái người dùng thành công!',
            ]);
        }
    }
}
