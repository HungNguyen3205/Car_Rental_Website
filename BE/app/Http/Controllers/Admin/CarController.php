<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\AdminCarActionRequest;
use App\Http\Requests\Car\AdminCarCreateRequest;
use App\Http\Requests\Car\AdminCarUpdateRequest;
use App\Models\Car;
use App\Models\CarCategory;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CarController extends Controller
{
    /**
     * Lấy danh sách xe với tìm kiếm, lọc, phân trang phía máy chủ và đếm số lượng theo tab.
     * Duy trì tính tương thích ngược tuyệt đối: res.data.data luôn là mảng đối tượng xe.
     */
    public function getData(Request $request)
    {
        // 1. Thống kê số lượng xe theo các tab trạng thái
        $countAll = Car::count();
        $countPublished = Car::where('publication_status', Car::PUB_STATUS_PUBLISHED)->count();
        $countDraft = Car::where('publication_status', Car::PUB_STATUS_DRAFT)->count();
        $countArchived = Car::where('publication_status', Car::PUB_STATUS_ARCHIVED)->count();
        $countTrash = Car::onlyTrashed()->count();

        $tab = $request->get('tab', 'all');

        // 2. Khởi tạo query tùy thuộc tab thùng rác hay tab thông thường
        if ($tab === 'trash' || $request->get('status') === 'trash') {
            $query = Car::onlyTrashed();
        } else {
            $query = Car::query();

            if ($tab === 'published') {
                $query->where('cars.publication_status', Car::PUB_STATUS_PUBLISHED);
            } elseif ($tab === 'draft') {
                $query->where('cars.publication_status', Car::PUB_STATUS_DRAFT);
            } elseif ($tab === 'archived') {
                $query->where('cars.publication_status', Car::PUB_STATUS_ARCHIVED);
            }
        }

        // 3. Join bảng danh mục để lấy tên danh mục
        $query->leftJoin('car_categories', 'cars.category_id', '=', 'car_categories.id')
            ->select('cars.*', 'car_categories.name as category_name');

        // 4. Tìm kiếm từ khóa (tên xe, mã xe #CAR-ID, loại xe, danh mục)
        if ($request->filled('search')) {
            $search = trim($request->search);
            // Hỗ trợ tìm kiếm theo format "#CAR-12" hoặc "12"
            $cleanedId = preg_replace('/[^0-9]/', '', $search);

            $query->where(function ($q) use ($search, $cleanedId) {
                $q->where('cars.name', 'like', "%{$search}%")
                  ->orWhere('cars.type', 'like', "%{$search}%")
                  ->orWhere('car_categories.name', 'like', "%{$search}%");

                if (!empty($cleanedId)) {
                    $q->orWhere('cars.id', $cleanedId);
                }
            });
        }

        // 5. Lọc theo trạng thái vận hành (available / maintenance)
        if ($request->filled('status') && !in_array($request->status, ['all', 'trash'])) {
            $query->where('cars.status', $request->status);
        }

        // 6. Lọc theo trạng thái xuất bản
        if ($request->filled('publication_status') && $request->publication_status !== 'all') {
            $query->where('cars.publication_status', $request->publication_status);
        }

        // 7. Lọc theo danh mục
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('cars.category_id', $request->category_id);
        }

        // 8. Lọc theo thành phố chuẩn hóa (không mất xe khi nhập slug hoặc tên đầy đủ)
        if ($request->filled('city') && $request->city !== 'all') {
            \App\Services\Location\LocationService::applyCityFilter($query, $request->city, 'cars.city');
        }

        // Sắp xếp
        $query->orderByDesc('cars.id');

        // 9. Kiểm tra xem caller có yêu cầu lấy toàn bộ danh sách không (không phân trang)
        $noPagination = $request->boolean('all') || 
                        $request->get('paginate') === 'false' || 
                        $request->get('per_page') === 'all';

        $counts = [
            'all'       => $countAll,
            'published' => $countPublished,
            'draft'     => $countDraft,
            'archived'  => $countArchived,
            'trash'     => $countTrash,
        ];

        if ($noPagination) {
            $data = $query->get();
            return response()->json([
                'status' => 1,
                'data'   => $data,
                'counts' => $counts,
            ]);
        }

        $perPage = (int) $request->get('per_page', 10);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => 1,
            'data'   => $paginated->items(), // Luôn là mảng array chuẩn
            'meta'   => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ],
            'counts' => $counts,
        ]);
    }

    public function getDetail($id)
    {
        try {
            $car = Car::withTrashed()
                ->leftJoin('car_categories', 'cars.category_id', '=', 'car_categories.id')
                ->where('cars.id', $id)
                ->select('cars.*', 'car_categories.name as category_name')
                ->first();

            if (!$car) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Xe không tồn tại trong hệ thống!',
                ], 404);
            }

            return response()->json([
                'status' => 1,
                'data'   => $car,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin Car getDetail error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Lỗi khi tải thông tin chi tiết xe!',
            ], 500);
        }
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|file|image|mimes:jpeg,jpg,png,webp|max:5120',
        ], [
            'image.required' => 'Vui lòng chọn tệp hình ảnh để tải lên!',
            'image.file'     => 'Dữ liệu tải lên phải là một tệp hợp lệ!',
            'image.image'    => 'Tệp tải lên phải là hình ảnh!',
            'image.mimes'    => 'Hình ảnh chỉ chấp nhận định dạng: jpeg, jpg, png, webp!',
            'image.max'      => 'Kích thước hình ảnh không được vượt quá 5MB!',
        ]);

        try {
            $file = $request->file('image');
            $ext = $file->guessExtension() ?: 'png';
            $filename = 'car_' . time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $ext;
            
            $uploadPath = public_path('uploads/cars');
            if (!\Illuminate\Support\Facades\File::exists($uploadPath)) {
                \Illuminate\Support\Facades\File::makeDirectory($uploadPath, 0755, true);
            }

            $file->move($uploadPath, $filename);
            $relativeUrl = '/uploads/cars/' . $filename;

            return response()->json([
                'status'  => 1,
                'message' => 'Tải ảnh lên thành công!',
                'url'     => $relativeUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin Car uploadImage error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi lưu tệp ảnh. Vui lòng thử lại sau!',
            ], 500);
        }
    }

    public function createData(AdminCarCreateRequest $request)
    {
        try {
            $validated = $request->validated();
            $pubStatus = $validated['publication_status'] ?? Car::PUB_STATUS_PUBLISHED;

            $car = Car::create([
                'name'                => $validated['name'],
                'license_plate'       => $validated['license_plate'] ?? null,
                'price_per_day'       => $validated['price_per_day'],
                'current_km'          => $validated['current_km'] ?? 0,
                'type'                => $validated['type'] ?? 'Sedan',
                'city'                => isset($validated['city']) ? (\App\Services\Location\LocationService::resolveSlug($validated['city']) ?? $validated['city']) : 'sg',
                'status'              => $validated['status'] ?? 'available',
                'publication_status'  => $pubStatus,
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
                'is_active'           => $validated['is_active'] ?? ($pubStatus === Car::PUB_STATUS_PUBLISHED),
            ]);

            AuditService::log(
                'car.created',
                'Car',
                $car->id,
                "Xe {$car->name} (#CAR-{$car->id})",
                [
                    'name'               => $car->name,
                    'license_plate'      => $car->license_plate,
                    'price_per_day'      => $car->price_per_day,
                    'city'               => $car->city,
                    'status'             => $car->status,
                    'publication_status' => $car->publication_status,
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

            // Kiểm soát xung đột ghi đè đồng thời (Optimistic Concurrency Control)
            if ($request->filled('updated_at')) {
                try {
                    $submittedUpdatedAt = \Carbon\Carbon::parse($request->updated_at);
                    if ($car->updated_at && $car->updated_at->timestamp > $submittedUpdatedAt->timestamp) {
                        return response()->json([
                            'status'             => 0,
                            'error_type'         => 'CONCURRENCY_CONFLICT',
                            'message'            => 'Dữ liệu xe này đã được cập nhật bởi một phiên làm việc khác trước đó! Vui lòng làm mới trang để xem thông tin mới nhất.',
                            'current_updated_at' => $car->updated_at->toISOString(),
                            'data'               => $car,
                        ], 409);
                    }
                } catch (\Exception $e) {
                    // Fallback nếu chuỗi ngày gửi lên không parse được
                }
            }

            // Kiểm tra quy tắc: Không được chuyển sang nháp hoặc lưu trữ nếu xe đang có đơn đặt xe hoạt động
            if (isset($validated['publication_status']) && $validated['publication_status'] !== Car::PUB_STATUS_PUBLISHED && $car->publication_status === Car::PUB_STATUS_PUBLISHED) {
                if ($car->hasActiveBookings()) {
                    return response()->json([
                        'status'  => 0,
                        'message' => 'Không thể chuyển xe về trạng thái "' . $validated['publication_status'] . '" vì xe đang có lịch thuê hoạt động hoặc chờ duyệt!',
                    ], 422);
                }
            }

            $fields = [
                'name',
                'license_plate',
                'price_per_day',
                'current_km',
                'type',
                'city',
                'status',
                'publication_status',
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

            if (isset($updateData['city'])) {
                $updateData['city'] = \App\Services\Location\LocationService::resolveSlug($updateData['city']) ?? $updateData['city'];
            }

            if (isset($updateData['publication_status']) && !isset($updateData['is_active'])) {
                $updateData['is_active'] = ($updateData['publication_status'] === Car::PUB_STATUS_PUBLISHED);
            }

            $oldData = [
                'name'               => $car->name,
                'license_plate'      => $car->license_plate,
                'price_per_day'      => $car->price_per_day,
                'status'             => $car->status,
                'publication_status' => $car->publication_status,
                'city'               => $car->city,
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

    /**
     * Soft delete xe: Chuyển xe vào thùng rác.
     * Chặn xóa nếu xe đang có đơn đặt xe hoạt động.
     * Không xóa dây chuyền đơn hoặc giao dịch, bảo toàn 100% dữ liệu lịch sử.
     */
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

            // Quy tắc chặn thao tác ảnh hưởng xe có booking đang hoạt động
            if ($car->hasActiveBookings()) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Không thể xóa xe này vì đang có đơn đặt xe hoạt động hoặc chờ phục vụ!',
                ], 422);
            }

            $carName = $car->name;
            $carId = $car->id;
            $car->delete(); // Soft delete: row remains in database with deleted_at set

            AuditService::log(
                'car.deleted',
                'Car',
                $carId,
                "Xe {$carName} (#CAR-{$carId})",
                ['deleted_at' => now()],
                ['name' => $carName]
            );

            return response()->json([
                'status'  => 1,
                'message' => 'Đã chuyển xe vào thùng rác thành công!',
            ]);
        } catch (\Exception $e) {
            Log::error('Admin Car deleteData error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi chuyển xe vào thùng rác!',
            ], 500);
        }
    }

    /**
     * Khôi phục xe từ thùng rác.
     */
    public function restoreData(AdminCarActionRequest $request)
    {
        try {
            $car = Car::onlyTrashed()->find($request->id);
            if (!$car) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Không tìm thấy xe trong thùng rác hoặc xe chưa từng bị xóa!',
                ], 404);
            }

            $car->restore();

            AuditService::log(
                'car.restored',
                'Car',
                $car->id,
                "Xe {$car->name} (#CAR-{$car->id})",
                ['deleted_at' => null],
                ['name' => $car->name]
            );

            return response()->json([
                'status'  => 1,
                'message' => 'Đã khôi phục xe thành công!',
                'data'    => $car,
            ]);
        } catch (\Exception $e) {
            Log::error('Admin Car restoreData error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi khôi phục xe!',
            ], 500);
        }
    }

    /**
     * Xóa vĩnh viễn xe (chỉ cho phép nếu xe chưa từng phát sinh bất kỳ đơn hay giao dịch nào).
     */
    public function forceDeleteData(AdminCarActionRequest $request)
    {
        try {
            $car = Car::onlyTrashed()->find($request->id);
            if (!$car) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Không tìm thấy xe trong thùng rác!',
                ], 404);
            }

            if ($car->bookings()->exists()) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Không thể xóa vĩnh viễn xe này do đã có lịch sử đơn đặt xe trong hệ thống. Vui lòng giữ trong thùng rác để bảo toàn lịch sử sổ sách!',
                ], 422);
            }

            $carName = $car->name;
            $carId = $car->id;
            $car->forceDelete();

            AuditService::log(
                'car.force_deleted',
                'Car',
                $carId,
                "Xe {$carName} (#CAR-{$carId})",
                null,
                ['name' => $carName]
            );

            return response()->json([
                'status'  => 1,
                'message' => 'Đã xóa vĩnh viễn xe khỏi hệ thống!',
            ]);
        } catch (\Exception $e) {
            Log::error('Admin Car forceDeleteData error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi xóa vĩnh viễn xe!',
            ], 500);
        }
    }

    /**
     * Đổi trạng thái vận hành xe: Sẵn sàng (available) <-> Bảo trì (maintenance).
     */
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
                'message' => 'Cập nhật trạng thái vận hành thành công!',
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

    /**
     * Cập nhật trạng thái xuất bản / nội dung: draft, published, archived.
     */
    public function publicationStatusData(Request $request)
    {
        $request->validate([
            'id'                 => 'required|integer',
            'publication_status' => 'required|in:draft,published,archived',
        ], [
            'id.required'                 => 'Mã định danh xe là bắt buộc!',
            'publication_status.required' => 'Trạng thái nội dung là bắt buộc!',
            'publication_status.in'       => 'Trạng thái nội dung chỉ chấp nhận: draft, published, archived!',
        ]);

        try {
            $car = Car::find($request->id);
            if (!$car) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Xe không tồn tại trong hệ thống!',
                ], 404);
            }

            $newStatus = $request->publication_status;

            // Chặn chuyển về nháp hoặc lưu trữ nếu xe có đơn đặt xe đang hoạt động
            if ($newStatus !== Car::PUB_STATUS_PUBLISHED && $car->hasActiveBookings()) {
                return response()->json([
                    'status'  => 0,
                    'message' => 'Không thể chuyển xe về trạng thái "' . $newStatus . '" vì xe đang có lịch thuê hoạt động hoặc chờ duyệt!',
                ], 422);
            }

            $oldStatus = $car->publication_status;
            $car->publication_status = $newStatus;
            $car->is_active = ($newStatus === Car::PUB_STATUS_PUBLISHED);
            $car->save();

            AuditService::log(
                'car.publication_status_changed',
                'Car',
                $car->id,
                "Xe {$car->name} (#CAR-{$car->id})",
                ['publication_status' => $newStatus, 'is_active' => $car->is_active],
                ['publication_status' => $oldStatus]
            );

            return response()->json([
                'status'  => 1,
                'message' => 'Cập nhật trạng thái xuất bản thành công!',
                'data'    => [
                    'id'                 => $car->id,
                    'publication_status' => $car->publication_status,
                    'is_active'          => $car->is_active,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Admin Car publicationStatusData error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái xuất bản!',
            ], 500);
        }
    }

    /**
     * AI Tách nền và chuẩn hóa tỷ lệ ảnh xe (Canvas 1600x1000, 8:5, WebP).
     */
    public function aiNormalizeImage(Request $request, \App\Services\Car\CarImageAiService $aiService)
    {
        $request->validate([
            'image'     => 'nullable|file|image|mimes:jpeg,jpg,png,webp|max:10240',
            'image_url' => 'nullable|string',
        ], [
            'image.image' => 'Tệp tải lên phải là hình ảnh hợp lệ!',
            'image.mimes' => 'Hình ảnh chỉ chấp nhận định dạng jpeg, jpg, png, webp!',
            'image.max'   => 'Dung lượng ảnh tối đa 10MB!',
        ]);

        if (!$request->hasFile('image') && empty($request->image_url)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Vui lòng cung cấp tệp ảnh hoặc đường dẫn ảnh cần chuẩn hóa!',
            ], 422);
        }

        try {
            $input = $request->hasFile('image') ? $request->file('image') : $request->image_url;
            $result = $aiService->process($input);

            return response()->json([
                'status'  => 1,
                'message' => 'Chuẩn hóa và tách nền ảnh xe thành công!',
                'data'    => $result,
            ]);
        } catch (\DomainException $de) {
            return response()->json([
                'status'  => 0,
                'message' => $de->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Car aiNormalizeImage error: " . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra trong quá trình xử lý tách nền: ' . $e->getMessage(),
            ], 500);
        }
    }
}
