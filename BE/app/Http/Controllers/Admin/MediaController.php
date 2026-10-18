<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Services\Audit\AuditService;
use App\Services\Media\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    protected MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * Danh sách tài nguyên thư viện đa phương tiện có phân trang và bộ lọc.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'visibility', 'collection', 'mime_type']);

        $query = MediaAsset::filter($filters)->orderByDesc('id');

        // Thống kê nhanh
        $totalFiles = MediaAsset::count();
        $totalPublic = MediaAsset::where('visibility', MediaAsset::VISIBILITY_PUBLIC)->count();
        $totalPrivate = MediaAsset::where('visibility', MediaAsset::VISIBILITY_PRIVATE)->count();
        $totalImages = MediaAsset::where('mime_type', 'like', 'image/%')->count();
        $totalDocuments = MediaAsset::where('mime_type', 'like', '%pdf%')->count();
        $totalSizeBytes = MediaAsset::sum('size');

        $stats = [
            'total_files'      => $totalFiles,
            'total_public'     => $totalPublic,
            'total_private'    => $totalPrivate,
            'total_images'     => $totalImages,
            'total_documents'  => $totalDocuments,
            'total_size_bytes' => (int) $totalSizeBytes,
            'total_size_human' => $this->formatBytes($totalSizeBytes),
        ];

        $perPage = (int) $request->input('per_page', 24);
        if ($perPage <= 0 || $perPage > 100) {
            $perPage = 24;
        }

        $paginated = $query->paginate($perPage);

        // Gắn kèm thông tin tham chiếu tóm tắt cho từng asset
        $items = collect($paginated->items())->map(function (MediaAsset $asset) {
            $data = $asset->toArray();
            $refs = $asset->getReferences();
            $data['in_use'] = $refs['total_count'] > 0;
            $data['references_count'] = $refs['total_count'];
            $data['references_summary'] = [
                'cars_featured' => count($refs['cars_featured']),
                'cars_gallery'  => count($refs['cars_gallery']),
                'bookings'      => count($refs['bookings']),
            ];
            return $data;
        });

        return response()->json([
            'status' => 1,
            'data'   => $items,
            'meta'   => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
            'stats'  => $stats,
        ]);
    }

    /**
     * Tải lên một hoặc nhiều tệp vào thư viện.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'files'        => 'required|array|min:1|max:20',
            'files.*'      => 'required|file|max:10240', // 10MB
            'collection'   => 'nullable|string|in:cars,marketing,receipts,handovers,general',
            'visibility'   => 'nullable|string|in:public,private',
            'alt_text'     => 'nullable|string|max:255',
            'description'  => 'nullable|string|max:1000',
        ], [
            'files.required' => 'Vui lòng chọn ít nhất một tệp để tải lên!',
            'files.array'    => 'Dữ liệu tệp tải lên không hợp lệ!',
            'files.max'      => 'Chỉ cho phép tải lên tối đa 20 tệp cùng lúc!',
            'files.*.file'   => 'Dữ liệu phải là tệp hợp lệ!',
            'files.*.max'    => 'Mỗi tệp không được vượt quá 10MB!',
            'collection.in'  => 'Nhóm tài nguyên không hợp lệ!',
            'visibility.in'  => 'Chế độ hiển thị không hợp lệ!',
        ]);

        try {
            $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();

            $files = $request->file('files');
            $options = [
                'collection'  => $request->input('collection', MediaAsset::COLLECTION_GENERAL),
                'visibility'  => $request->input('visibility', MediaAsset::VISIBILITY_PUBLIC),
                'alt_text'    => $request->input('alt_text'),
                'description' => $request->input('description'),
            ];

            $createdAssets = $this->mediaService->uploadMultiple($files, $options, $user);

            AuditService::log(
                action: 'media.upload',
                targetType: 'media_asset',
                targetId: null,
                targetLabel: 'Tải lên ' . count($createdAssets) . ' tệp vào thư viện tài nguyên',
                newValues: ['count' => count($createdAssets), 'collection' => $options['collection']]
            );

            return response()->json([
                'status'  => 1,
                'message' => 'Tải lên thành công ' . count($createdAssets) . ' tệp!',
                'data'    => $createdAssets,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status'  => 0,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('MediaController store error: ' . $e->getMessage());
            return response()->json([
                'status'  => 0,
                'message' => 'Có lỗi xảy ra trong quá trình lưu trữ tệp: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tra cứu chi tiết các bản ghi đang sử dụng asset này.
     */
    public function references(int $id): JsonResponse
    {
        $asset = MediaAsset::findOrFail($id);
        $refs = $asset->getReferences();

        return response()->json([
            'status' => 1,
            'data'   => [
                'asset'      => $asset,
                'references' => $refs,
                'in_use'     => $refs['total_count'] > 0,
            ],
        ]);
    }

    /**
     * Cập nhật thông tin mô tả/alt text/collection của asset.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $asset = MediaAsset::findOrFail($id);

        $validated = $request->validate([
            'alt_text'    => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'collection'  => 'nullable|string|in:cars,marketing,receipts,handovers,general',
        ]);

        $asset->update($validated);

        return response()->json([
            'status'  => 1,
            'message' => 'Cập nhật thông tin tài nguyên thành công!',
            'data'    => $asset,
        ]);
    }

    /**
     * Xóa tài nguyên an toàn: Kiểm tra nghiêm ngặt nơi sử dụng.
     */
    public function destroy(int $id): JsonResponse
    {
        $asset = MediaAsset::findOrFail($id);

        $result = $this->mediaService->deleteAsset($asset);

        if (!$result['success']) {
            return response()->json([
                'status'     => 0,
                'error_code' => 'ASSET_IN_USE',
                'message'    => $result['message'],
                'references' => $result['references'],
            ], 422);
        }

        AuditService::log(
            action: 'media.delete',
            targetType: 'media_asset',
            targetId: (string) $id,
            targetLabel: "Đã xóa tài nguyên '{$asset->original_name}' (ID: {$id})",
            newValues: null,
            oldValues: ['id' => $id, 'filename' => $asset->filename]
        );

        return response()->json([
            'status'  => 1,
            'message' => $result['message'],
        ]);
    }

    /**
     * Stream tệp riêng tư có phân quyền bảo mật cao.
     */
    public function stream(Request $request, int $id): BinaryFileResponse|JsonResponse
    {
        $user = Auth::guard('sanctum')->user() ?? Auth::guard('admin')->user();
        if (!$user && $request->has('token')) {
            $tokenStr = $request->query('token');
            $pat = \Laravel\Sanctum\PersonalAccessToken::findToken($tokenStr);
            if ($pat) {
                $user = $pat->tokenable;
            }
        }

        $asset = MediaAsset::findOrFail($id);

        return $this->mediaService->streamAsset($asset, $user);
    }

    /**
     * Định dạng kích thước byte thành chuỗi người đọc được.
     */
    protected function formatBytes(float|int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
