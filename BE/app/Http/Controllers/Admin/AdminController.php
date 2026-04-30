<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function getData()
    {
        $data = Admin::all();
        return response()->json([
            'status' => 1,
            'data'   => $data,
        ]);
    }

    public function createData(Request $request)
    {
        $check = Admin::where('email', $request->email)->first();
        if ($check) {
            return response()->json([
                'status'  => 0,
                'message' => 'Email admin đã tồn tại!',
            ]);
        }

        Admin::create([
            'ho_ten'        => $request->ho_ten,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'so_dien_thoai' => $request->so_dien_thoai,
            'tinh_trang'    => $request->tinh_trang,
        ]);

        return response()->json([
            'status'  => 1,
            'message' => 'Đã thêm mới admin thành công!',
        ]);
    }

    public function updateData(Request $request)
    {
        $admin = Admin::find($request->id);
        if ($admin) {
            $data = $request->all();
            if ($request->password) {
                $data['password'] = Hash::make($request->password);
            } else {
                unset($data['password']);
            }
            $admin->update($data);

            return response()->json([
                'status'  => 1,
                'message' => 'Đã cập nhật admin thành công!',
            ]);
        }
    }

    public function deleteData(Request $request)
    {
        $admin = Admin::find($request->id);
        if ($admin) {
            $admin->delete();
            return response()->json([
                'status'  => 1,
                'message' => 'Đã xóa admin thành công!',
            ]);
        }
    }

    public function statusData(Request $request)
    {
        $admin = Admin::find($request->id);
        if ($admin) {
            $admin->tinh_trang = !$admin->tinh_trang;
            $admin->save();
            return response()->json([
                'status'  => 1,
                'message' => 'Đã đổi trạng thái admin thành công!',
            ]);
        }
    }

    public function promoteUser(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = User::find($request->id);
            if (!$user) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Người dùng không tồn tại!',
                ]);
            }

            // Check if email already exists in admins
            $check = Admin::where('email', $user->email)->first();
            if ($check) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Email này đã là admin!',
                ]);
            }

            // Create Admin
            Admin::create([
                'ho_ten'        => $user->ho_ten,
                'email'         => $user->email,
                'password'      => $user->password, // Keep the same password hash
                'so_dien_thoai' => $user->so_dien_thoai,
                'tinh_trang'    => 1,
            ]);

            // Delete User
            $user->delete();

            DB::commit();
            return response()->json([
                'status'  => 1,
                'message' => 'Đã nâng cấp người dùng lên Admin thành công!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ]);
        }
    }
}
