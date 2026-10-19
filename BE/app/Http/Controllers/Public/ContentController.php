<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\WebsiteContent;
use App\Services\Authorization\PermissionMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ContentController extends Controller
{
    public const PUBLIC_CACHE_KEY_ALL = 'website_public_content';
    public const PUBLIC_CACHE_KEY_PREFIX = 'website_public_content_';

    /**
     * Lấy toàn bộ nội dung công khai đã xuất bản (được Cache tối ưu hiệu năng).
     * Tuyệt đối không để lộ bản nháp (draft_content).
     */
    public function index(): JsonResponse
    {
        $contentMap = Cache::rememberForever(self::PUBLIC_CACHE_KEY_ALL, function () {
            $records = WebsiteContent::query()
                ->where('status', 'published')
                ->get(['section_key', 'title', 'published_content', 'version', 'published_at']);

            $result = [];
            foreach ($records as $record) {
                $result[$record->section_key] = [
                    'section_key'  => $record->section_key,
                    'title'        => $record->title,
                    'content'      => $record->published_content,
                    'version'      => $record->version,
                    'published_at' => $record->published_at,
                ];
            }
            return $result;
        });

        return response()->json([
            'status' => 1,
            'data'   => $contentMap,
        ]);
    }

    /**
     * Lấy nội dung công khai của một section cụ thể
     */
    public function show(string $sectionKey): JsonResponse
    {
        $cacheKey = self::PUBLIC_CACHE_KEY_PREFIX . $sectionKey;

        $content = Cache::rememberForever($cacheKey, function () use ($sectionKey) {
            $record = WebsiteContent::query()
                ->where('section_key', $sectionKey)
                ->where('status', 'published')
                ->first(['section_key', 'title', 'published_content', 'version', 'published_at']);

            if (!$record) {
                return null;
            }

            return [
                'section_key'  => $record->section_key,
                'title'        => $record->title,
                'content'      => $record->published_content,
                'version'      => $record->version,
                'published_at' => $record->published_at,
            ];
        });

        if (!$content) {
            return response()->json([
                'status'  => 0,
                'message' => "Không tìm thấy nội dung cho khối [{$sectionKey}]",
            ], 404);
        }

        return response()->json([
            'status' => 1,
            'data'   => $content,
        ]);
    }

    /**
     * Xem trước (Preview) nội dung nháp riêng tư dành riêng cho Quản trị viên đã xác thực.
     * Người dùng công khai chưa đăng nhập quản trị không thể truy cập.
     */
    public function preview(Request $request, string $sectionKey): JsonResponse
    {
        $admin = Auth::guard('admin')->user() ?? Auth::guard('sanctum')->user();

        if (!$admin) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn cần đăng nhập quản trị viên để xem trước bản nháp.',
            ], 401);
        }

        $canView = (method_exists($admin, 'isOwner') && $admin->isOwner())
            || (method_exists($admin, 'hasPermission') && (
                $admin->hasPermission(PermissionMatrix::PERM_CONTENT_VIEW) ||
                $admin->hasPermission(PermissionMatrix::PERM_CONTENT_EDIT) ||
                $admin->hasPermission(PermissionMatrix::PERM_CONTENT_MANAGE)
            ));

        if (!$canView) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền xem trước bản nháp nội dung website.',
            ], 403);
        }

        $record = WebsiteContent::query()->where('section_key', $sectionKey)->first();

        if (!$record) {
            return response()->json([
                'status'  => 0,
                'message' => "Không tìm thấy khối nội dung [{$sectionKey}].",
            ], 404);
        }

        return response()->json([
            'status' => 1,
            'data'   => [
                'section_key'             => $record->section_key,
                'title'                   => $record->title,
                'content'                 => $record->draft_content ?? $record->published_content,
                'is_preview'              => true,
                'has_unpublished_changes' => $record->has_unpublished_changes,
                'version'                 => $record->version,
                'updated_at'              => $record->updated_at,
            ],
        ]);
    }
}
