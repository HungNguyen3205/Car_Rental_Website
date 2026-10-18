<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarCategory;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarCategoryController extends Controller
{
    /**
     * Danh sách danh mục xe cho quản trị viên (kèm số lượng xe đang gán).
     */
    public function index(): JsonResponse
    {
        $categories = CarCategory::withCount('cars')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 1,
            'data'   => $categories,
        ]);
    }

    /**
     * Tạo mới danh mục xe có validation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:car_categories,name',
            'description' => 'nullable|string|max:500',
        ]);

        $category = CarCategory::create($validated);

        AuditService::log(
            action: 'category.created',
            targetType: 'CarCategory',
            targetId: $category->id,
            targetLabel: $category->name,
            newValues: $category->toArray()
        );

        return response()->json([
            'status'  => 1,
            'message' => 'Thêm mới danh mục xe thành công!',
            'data'    => $category->loadCount('cars'),
        ], 201);
    }

    /**
     * Cập nhật danh mục xe có validation trùng tên.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $category = CarCategory::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:car_categories,name,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        $oldValues = $category->toArray();
        $category->update($validated);

        AuditService::log(
            action: 'category.updated',
            targetType: 'CarCategory',
            targetId: $category->id,
            targetLabel: $category->name,
            newValues: $category->toArray(),
            oldValues: $oldValues
        );

        return response()->json([
            'status'  => 1,
            'message' => 'Cập nhật danh mục xe thành công!',
            'data'    => $category->fresh()->loadCount('cars'),
        ]);
    }

    /**
     * Xóa danh mục xe - Có cơ chế BẢO VỆ chặn xóa khi đang có xe sử dụng.
     */
    public function destroy($id): JsonResponse
    {
        $category = CarCategory::findOrFail($id);
        $carsCount = $category->cars()->count();

        // Bảo vệ toàn vẹn: Không cho xóa nếu đang có xe sử dụng
        if ($carsCount > 0) {
            return response()->json([
                'status'  => 0,
                'message' => "Không thể xóa danh mục '{$category->name}' vì đang có {$carsCount} xe liên kết! Vui lòng chuyển hoặc xóa xe trước khi xóa danh mục.",
            ], 422);
        }

        $oldValues = $category->toArray();
        $categoryName = $category->name;
        $category->delete();

        AuditService::log(
            action: 'category.deleted',
            targetType: 'CarCategory',
            targetId: $id,
            targetLabel: $categoryName,
            oldValues: $oldValues
        );

        return response()->json([
            'status'  => 1,
            'message' => "Đã xóa danh mục '{$categoryName}' thành công!",
        ]);
    }
}
