<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\AdminCarActionRequest;
use App\Http\Requests\Car\AdminCarCreateRequest;
use App\Http\Requests\Car\AdminCarUpdateRequest;
use App\Models\Car;
use App\Models\CarCategory;
use Illuminate\Support\Facades\Log;

class CarController extends Controller
{
    public function getData()
    {
        $data = Car::join('car_categories', 'cars.category_id', '=', 'car_categories.id')
            ->select('cars.*', 'car_categories.name as category_name')
            ->orderByDesc('cars.id')
            ->get();
            
        return response()->json([
            'status' => 1,
            'data'   => $data
        ]);
    }

    public function createData(AdminCarCreateRequest $request)
    {
        try {
            $validated = $request->validated();
            $car = Car::create([
                'name'          => $validated['name'],
                'price_per_day' => $validated['price_per_day'],
                'type'          => $validated['type'] ?? 'Sedan',
                'city'          => $validated['city'] ?? 'sg',
                'status'        => $validated['status'] ?? 'available',
                'image'         => $validated['image'] ?? '/assets/images/cars/vf9.png',
                'category_id'   => $validated['category_id'] ?? 1,
            ]);

            return response()->json([
                'status'  => 1,
                'message' => 'Tạo mới xe thành công!',
                'data'    => $car,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin Car createData error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi tạo xe mới. Vui lòng thử lại sau!',
            ], 500);
        }
    }

    public function updateData(AdminCarUpdateRequest $request)
    {
        try {
            $validated = $request->validated();
            $car = Car::find($validated['id']);
            if (!$car) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Xe không tồn tại trong hệ thống!',
                ], 404);
            }

            // Strictly whitelist updated fields, no uncontrolled mass assignment
            $updateData = [
                'name'          => $validated['name'],
                'price_per_day' => $validated['price_per_day'],
            ];

            if (array_key_exists('type', $validated)) {
                $updateData['type'] = $validated['type'];
            }
            if (array_key_exists('city', $validated)) {
                $updateData['city'] = $validated['city'];
            }
            if (array_key_exists('status', $validated)) {
                $updateData['status'] = $validated['status'];
            }
            if (array_key_exists('image', $validated)) {
                $updateData['image'] = $validated['image'];
            }
            if (array_key_exists('category_id', $validated)) {
                $updateData['category_id'] = $validated['category_id'];
            }

            $car->update($updateData);

            return response()->json([
                'status'  => 1,
                'message' => 'Cập nhật xe thành công!',
                'data'    => $car,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin Car updateData error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi cập nhật thông tin xe. Vui lòng thử lại sau!',
            ], 500);
        }
    }

    public function deleteData(AdminCarActionRequest $request)
    {
        try {
            $car = Car::find($request->id);
            if (!$car) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Xe không tồn tại trong hệ thống!',
                ], 404);
            }

            $car->delete();
            return response()->json([
                'status'  => 1,
                'message' => 'Xóa xe thành công!',
            ]);
        } catch (\Exception $e) {
            Log::error('Admin Car deleteData error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Không thể xóa xe lúc này do có ràng buộc dữ liệu!',
            ], 500);
        }
    }

    public function statusData(AdminCarActionRequest $request)
    {
        try {
            $car = Car::find($request->id);
            if (!$car) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Xe không tồn tại trong hệ thống!',
                ], 404);
            }

            $car->status = ($car->status === 'available' ? 'maintenance' : 'available');
            $car->save();

            return response()->json([
                'status'  => 1,
                'message' => 'Cập nhật trạng thái thành công!',
                'data'    => [
                    'id'     => $car->id,
                    'status' => $car->status,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Admin Car statusData error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái xe!',
            ], 500);
        }
    }
}
