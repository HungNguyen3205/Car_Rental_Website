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
     * Danh sách danh mục xe cho quản trị viên.
     * Hỗ trợ tìm kiếm, lọc theo trạng thái và phân trang linh hoạt,
     * đồng thời bảo toàn cấu trúc dữ liệu array cho các caller cũ.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CarCategory::withCount('cars')
            ->filter($request->only(['search', 'status']));

        // Sắp xếp ưu tiên danh mục đang sử dụng lên trước, sau đó theo tên
        $query->orderByDesc('is_active')->orderBy('name');

        // Thống kê tổng quan nhanh
        $totalCategories = CarCategory::count();
        $activeCount = CarCategory::where('is_active', true)->count();
        $inactiveCount = CarCategory::where('is_active', false)->count();
        $totalCarsAssigned = \DB::table('cars')->whereNotNull('category_id')->count();

        $stats = [
            'total'               => $totalCategories,
            'active_count'        => $activeCount,
            'inactive_count'      => $inactiveCount,
            'total_cars_assigned' => $totalCarsAssigned,
        ];

        // Nếu client yêu cầu phân trang rõ ràng qua 'page' hoặc 'paginate=true'
        if ($request->has('page') || $request->boolean('paginate')) {
            $perPage = (int) $request->input('per_page', 10);
            if ($perPage <= 0 || $perPage > 100) {
                $perPage = 10;
            }

            $paginated = $query->paginate($perPage);

            return response()->json([
                'status' => 1,
                'data'   => $paginated->items(),
                'meta'   => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
                'stats'  => $stats,
            ]);
        }

        // Mặc định trả về toàn bộ dạng array để tương thích hoàn toàn 100% với các component cũ
        $categories = $query->get();

        return response()->json([
            'status' => 1,
            'data'   => $categories,
            'stats'  => $stats,
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
            'is_active'   => 'nullable|boolean',
        ], [
            'name.required' => 'Tên danh mục là bắt buộc!',
            'name.max'      => 'Tên danh mục không được vượt quá 100 ký tự!',
            'name.unique'   => 'Tên danh mục này đã tồn tại trong hệ thống!',
            'description.max' => 'Mô tả không được vượt quá 500 ký tự!',
        ]);

        if (!isset($validated['is_active'])) {
            $validated['is_active'] = true;
        }

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
     * Cập nhật danh mục xe có validation trùng tên và trạng thái hoạt động.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $category = CarCategory::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:100|unique:car_categories,name,' . $id,
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
        ], [
            'name.required' => 'Tên danh mục là bắt buộc!',
            'name.max'      => 'Tên danh mục không được vượt quá 100 ký tự!',
            'name.unique'   => 'Tên danh mục này đã tồn tại trong hệ thống!',
            'description.max' => 'Mô tả không được vượt quá 500 ký tự!',
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
     * Chuyển đổi nhanh trạng thái: Đang sử dụng / Ngừng sử dụng.
     */
    public function toggleStatus($id): JsonResponse
    {
        $category = CarCategory::findOrFail($id);
        $oldStatus = $category->is_active;
        $category->is_active = !$oldStatus;
        $category->save();

        AuditService::log(
            action: 'category.status_toggled',
            targetType: 'CarCategory',
            targetId: $category->id,
            targetLabel: $category->name,
            newValues: ['is_active' => $category->is_active],
            oldValues: ['is_active' => $oldStatus]
        );

        $statusText = $category->is_active ? 'Kích hoạt sử dụng' : 'Ngừng sử dụng';

        return response()->json([
            'status'  => 1,
            'message' => "Đã chuyển trạng thái danh mục '{$category->name}' sang {$statusText} thành công!",
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
                'status'     => 0,
                'error_code' => 'CATEGORY_IN_USE',
                'message'    => "Không thể xóa danh mục '{$category->name}' vì đang có {$carsCount} xe liên kết! Bạn có thể chuyển sang trạng thái 'Ngừng sử dụng' thay vì xóa.",
                'cars_count' => $carsCount,
                'suggestion' => 'Vui lòng chuyển hoặc xóa xe trước, hoặc chuyển danh mục sang Ngừng sử dụng.',
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
