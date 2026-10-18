<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\AdminCarActionRequest;
use App\Http\Requests\Car\AdminCarCreateRequest;
use App\Http\Requests\Car\AdminCarUpdateRequest;
use App\Models\Car;
use App\Models\CarCategory;
use App\Services\Audit\AuditService;
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
                'name'                => $validated['name'],
                'price_per_day'       => $validated['price_per_day'],
                'type'                => $validated['type'] ?? 'Sedan',
                'city'                => $validated['city'] ?? 'sg',
                'status'              => $validated['status'] ?? 'available',
                'image'               => $validated['image'] ?? '/assets/images/cars/vf9.png',
                'category_id'         => $validated['category_id'] ?? 1,
                'fuel_type'           => $validated['fuel_type'] ?? 'electric',
                'seats'               => $validated['seats'] ?? null,
                'battery_capacity'    => $validated['battery_capacity'] ?? null,
                'range_per_charge'    => $validated['range_per_charge'] ?? null,
                'transmission'        => $validated['transmission'] ?? null,
                'year_of_manufacture' => $validated['year_of_manufacture'] ?? null,
                'gallery'             => $validated['gallery'] ?? [],
                'rental_conditions'   => $validated['rental_conditions'] ?? null,
                'extra_fees_info'     => $validated['extra_fees_info'] ?? null,
                'description'         => $validated['description'] ?? null,
                'is_active'           => $validated['is_active'] ?? true,
            ]);

            AuditService::log(
                'car.created',
                'Car',
                $car->id,
                "Xe {$car->name} (#CAR-{$car->id})",
                [
                    'name'          => $car->name,
                    'price_per_day' => $car->price_per_day,
                    'city'          => $car->city,
                    'status'        => $car->status,
                ]
            );

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

            // Whitelist updated fields, no uncontrolled mass assignment
            $fields = [
                'name',
                'price_per_day',
                'type',
                'city',
                'status',
                'image',
                'category_id',
                'fuel_type',
                'seats',
                'battery_capacity',
                'range_per_charge',
                'transmission',
                'year_of_manufacture',
                'gallery',
                'rental_conditions',
                'extra_fees_info',
                'description',
                'is_active',
            ];

            $updateData = [];
            foreach ($fields as $field) {
                if (array_key_exists($field, $validated)) {
                    $updateData[$field] = $validated[$field];
                }
            }

            $oldData = [
                'name'          => $car->name,
                'price_per_day' => $car->price_per_day,
                'status'        => $car->status,
                'city'          => $car->city,
            ];

            $car->update($updateData);

            AuditService::log(
                'car.updated',
                'Car',
                $car->id,
                "Xe {$car->name} (#CAR-{$car->id})",
                $updateData,
                $oldData
            );

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

            $carName = $car->name;
            $carId = $car->id;
            $car->delete();

            AuditService::log(
                'car.deleted',
                'Car',
                $carId,
                "Xe {$carName} (#CAR-{$carId})",
                null,
                ['name' => $carName]
            );

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

            $oldStatus = $car->status;
            $car->status = ($car->status === 'available' ? 'maintenance' : 'available');
            $car->save();

            AuditService::log(
                'car.status_changed',
                'Car',
                $car->id,
                "Xe {$car->name} (#CAR-{$car->id})",
                ['status' => $car->status],
                ['status' => $oldStatus]
            );

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
