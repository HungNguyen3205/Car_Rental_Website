<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'ho_ten' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'ho_ten' => $request->ho_ten,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'so_dien_thoai' => $request->so_dien_thoai,
            'ngay_sinh' => $request->ngay_sinh,
            'gioi_tinh' => $request->gioi_tinh ?? 1,
            'dia_chi' => $request->dia_chi,
            'role' => 'customer',
            'tinh_trang' => 1,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Đăng ký thành công. Vui lòng đăng nhập hệ thống'
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Tài khoản hoặc mật khẩu không đúng'
            ], 401);
        }

        if ($user->tinh_trang == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Tài khoản đã bị khóa'
            ], 403);
        }

        $token = $user->createToken('token_client')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Đăng nhập thành công',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'ho_ten' => $user->ho_ten,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]);
    }

    public function getProfile(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy thông tin người dùng'
            ], 401);
        }

        return response()->json([
            'status' => true,
            'message' => 'Lấy dữ liệu profile thành công',
            'data' => $user
        ]);
    }
}
