<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminActionIdRequest;
use App\Http\Requests\Admin\AdminCreateRequest;
use App\Http\Requests\Admin\AdminUpdateRequest;
use App\Http\Requests\Admin\PromoteUserRequest;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function getData()
    {
        $data = Admin::select('id', 'ho_ten', 'email', 'so_dien_thoai', 'dia_chi', 'tinh_trang', 'created_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => 1,
            'data'   => $data,
        ]);
    }

    public function createData(AdminCreateRequest $request)
    {
        try {
            $validated = $request->validated();

            $admin = Admin::create([
                'ho_ten'        => $validated['ho_ten'],
                'email'         => $validated['email'],
                'password'      => Hash::make($validated['password']),
                'so_dien_thoai' => $validated['so_dien_thoai'],
                'dia_chi'       => $validated['dia_chi'] ?? null,
                'tinh_trang'    => $validated['tinh_trang'] ?? 1,
            ]);

            return response()->json([
                'status'  => 1,
                'message' => 'Đã thêm mới admin thành công!',
                'data'    => [
                    'id'            => $admin->id,
                    'ho_ten'        => $admin->ho_ten,
                    'email'         => $admin->email,
                    'so_dien_thoai' => $admin->so_dien_thoai,
                    'dia_chi'       => $admin->dia_chi,
                    'tinh_trang'    => $admin->tinh_trang,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Admin createData error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi tạo tài khoản quản trị viên. Vui lòng thử lại sau!',
            ], 500);
        }
    }

    public function updateData(AdminUpdateRequest $request)
    {
        try {
            $validated = $request->validated();
            $admin = Admin::find($validated['id']);
            if (!$admin) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Tài khoản quản trị viên không tồn tại!',
                ], 404);
            }

            // Strictly whitelist updated fields, eliminate uncontrolled mass assignment
            $updateData = [
                'ho_ten'        => $validated['ho_ten'],
                'email'         => $validated['email'],
                'so_dien_thoai' => $validated['so_dien_thoai'],
            ];

            if (array_key_exists('dia_chi', $validated)) {
                $updateData['dia_chi'] = $validated['dia_chi'];
            }
            if (array_key_exists('tinh_trang', $validated)) {
                $updateData['tinh_trang'] = $validated['tinh_trang'];
            }
            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $admin->update($updateData);

            return response()->json([
                'status'  => 1,
                'message' => 'Đã cập nhật admin thành công!',
                'data'    => [
                    'id'            => $admin->id,
                    'ho_ten'        => $admin->ho_ten,
                    'email'         => $admin->email,
                    'so_dien_thoai' => $admin->so_dien_thoai,
                    'dia_chi'       => $admin->dia_chi,
                    'tinh_trang'    => $admin->tinh_trang,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Admin updateData error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi cập nhật tài khoản quản trị viên!',
            ], 500);
        }
    }

    public function deleteData(AdminActionIdRequest $request)
    {
        try {
            $admin = Admin::find($request->id);
            if (!$admin) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Tài khoản quản trị viên không tồn tại!',
                ], 404);
            }

            $admin->delete();
            return response()->json([
                'status'  => 1,
                'message' => 'Đã xóa admin thành công!',
            ]);
        } catch (\Exception $e) {
            Log::error('Admin deleteData error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi xóa tài khoản quản trị viên!',
            ], 500);
        }
    }

    public function statusData(AdminActionIdRequest $request)
    {
        try {
            $admin = Admin::find($request->id);
            if (!$admin) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Tài khoản quản trị viên không tồn tại!',
                ], 404);
            }

            $admin->tinh_trang = $admin->tinh_trang ? 0 : 1;
            $admin->save();

            return response()->json([
                'status'  => 1,
                'message' => 'Đã đổi trạng thái admin thành công!',
                'data'    => [
                    'id'         => $admin->id,
                    'tinh_trang' => $admin->tinh_trang,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Admin statusData error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi đổi trạng thái admin!',
            ], 500);
        }
    }

    public function promoteUser(PromoteUserRequest $request)
    {
        // 1. Kiểm tra điều kiện tồn tại trước khi mở transaction để không bao giờ bị leak transaction
        $user = User::find($request->id);
        if (!$user) {
            return response()->json([
                'status'  => 0,
                'message' => 'Người dùng không tồn tại!',
            ], 404);
        }

        $check = Admin::where('email', $user->email)->first();
        if ($check) {
            return response()->json([
                'status'  => 0,
                'message' => 'Email này đã là admin!',
            ], 422);
        }

        try {
            // 2. Thực thi atomic transaction: tạo Admin và xóa User
            DB::transaction(function () use ($user) {
                Admin::create([
                    'ho_ten'        => $user->ho_ten,
                    'email'         => $user->email,
                    'password'      => $user->password, // Giữ nguyên hash mật khẩu
                    'so_dien_thoai' => $user->so_dien_thoai,
                    'dia_chi'       => $user->dia_chi,
                    'tinh_trang'    => 1,
                ]);

                $user->delete();
            });

            return response()->json([
                'status'  => 1,
                'message' => 'Đã nâng cấp người dùng lên Admin thành công!',
            ]);
        } catch (\Exception $e) {
            Log::error('Promote user error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra trong quá trình nâng cấp tài khoản!',
            ], 500);
        }
    }
}
