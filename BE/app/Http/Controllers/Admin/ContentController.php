<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\ContentController as PublicContentController;
use App\Models\WebsiteContent;
use App\Models\WebsiteContentVersion;
use App\Services\Audit\AuditService;
use App\Services\Authorization\PermissionMatrix;
use App\Services\Content\ContentValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContentController extends Controller
{
    public function __construct(
        protected ContentValidationService $validationService
    ) {}

    /**
     * Lấy danh sách toàn bộ 8 khối nội dung website
     */
    public function index(): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $this->authorizeAdmin($admin, [
            PermissionMatrix::PERM_CONTENT_VIEW,
            PermissionMatrix::PERM_CONTENT_EDIT,
            PermissionMatrix::PERM_CONTENT_PUBLISH,
            PermissionMatrix::PERM_CONTENT_MANAGE,
        ]);

        $sections = WebsiteContent::query()
            ->with(['publisher:id,ho_ten,email', 'updater:id,ho_ten,email'])
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => 1,
            'data'   => $sections,
        ]);
    }

    /**
     * Chi tiết 1 khối nội dung (gồm draft_content và published_content)
     */
    public function show(string $sectionKey): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $this->authorizeAdmin($admin, [
            PermissionMatrix::PERM_CONTENT_VIEW,
            PermissionMatrix::PERM_CONTENT_EDIT,
            PermissionMatrix::PERM_CONTENT_PUBLISH,
            PermissionMatrix::PERM_CONTENT_MANAGE,
        ]);

        $record = WebsiteContent::query()
            ->where('section_key', $sectionKey)
            ->with(['publisher:id,ho_ten,email', 'updater:id,ho_ten,email'])
            ->first();

        if (!$record) {
            return response()->json([
                'status'  => 0,
                'message' => "Không tìm thấy khối nội dung [{$sectionKey}].",
            ], 404);
        }

        return response()->json([
            'status' => 1,
            'data'   => $record,
        ]);
    }

    /**
     * Lưu bản nháp (Draft) cho một khối nội dung
     */
    public function saveDraft(Request $request, string $sectionKey): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $this->authorizeAdmin($admin, [
            PermissionMatrix::PERM_CONTENT_EDIT,
            PermissionMatrix::PERM_CONTENT_MANAGE,
        ]);

        $record = WebsiteContent::query()->where('section_key', $sectionKey)->first();
        if (!$record) {
            return response()->json([
                'status'  => 0,
                'message' => "Không tìm thấy khối nội dung [{$sectionKey}].",
            ], 404);
        }

        $inputContent = $request->input('content');
        if (!is_array($inputContent)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Nội dung chỉnh sửa phải là một object dữ liệu hợp lệ.',
            ], 422);
        }

        // Validate dữ liệu theo schema block xác định trước
        $validated = $this->validationService->validateSectionContent($sectionKey, $inputContent);

        $oldDraft = $record->draft_content;
        $record->draft_content = $validated;
        $record->has_unpublished_changes = true;
        $record->updated_by = $admin->id;
        $record->save();

        AuditService::log(
            action: 'content.draft_saved',
            targetType: 'website_content',
            targetId: $record->id,
            targetLabel: "{$record->title} ({$record->section_key})",
            newValues: ['has_unpublished_changes' => true],
            oldValues: ['has_unpublished_changes' => $record->getOriginal('has_unpublished_changes')],
            customActor: $admin
        );

        return response()->json([
            'status'  => 1,
            'message' => 'Đã lưu bản nháp thành công.',
            'data'    => $record,
        ]);
    }

    /**
     * Xuất bản (Publish) bản nháp ra trang công khai
     */
    public function publish(Request $request, string $sectionKey): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $this->authorizeAdmin($admin, [
            PermissionMatrix::PERM_CONTENT_PUBLISH,
            PermissionMatrix::PERM_CONTENT_MANAGE,
        ]);

        $record = WebsiteContent::query()->where('section_key', $sectionKey)->first();
        if (!$record) {
            return response()->json([
                'status'  => 0,
                'message' => "Không tìm thấy khối nội dung [{$sectionKey}].",
            ], 404);
        }

        // Nếu client truyền kèm payload mới khi ấn xuất bản, validate và cập nhật nháp trước
        if ($request->has('content') && is_array($request->input('content'))) {
            $validated = $this->validationService->validateSectionContent($sectionKey, $request->input('content'));
            $record->draft_content = $validated;
        }

        $contentToPublish = $record->draft_content ?? $record->published_content;
        if (empty($contentToPublish)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Không có nội dung để xuất bản.',
            ], 422);
        }

        // Đảm bảo validate lại toàn bộ lần cuối trước khi ghi đè công khai
        $this->validationService->validateSectionContent($sectionKey, $contentToPublish);

        DB::transaction(function () use ($record, $contentToPublish, $admin, $sectionKey) {
            $newVersion = $record->version + 1;

            $record->published_content = $contentToPublish;
            $record->draft_content = $contentToPublish;
            $record->status = 'published';
            $record->has_unpublished_changes = false;
            $record->version = $newVersion;
            $record->published_at = now();
            $record->publisher_id = $admin->id;
            $record->updated_by = $admin->id;
            $record->save();

            // Lưu snapshot vào bảng lịch sử phiên bản
            $record->recordSnapshot($admin->id, "Xuất bản phiên bản v{$newVersion}");

            // Làm mới Cache tức thì
            Cache::forget(PublicContentController::PUBLIC_CACHE_KEY_ALL);
            Cache::forget(PublicContentController::PUBLIC_CACHE_KEY_PREFIX . $sectionKey);

            AuditService::log(
                action: 'content.published',
                targetType: 'website_content',
                targetId: $record->id,
                targetLabel: "{$record->title} ({$record->section_key}) v{$newVersion}",
                newValues: ['version' => $newVersion, 'published_at' => (string) now()],
                oldValues: ['version' => $record->getOriginal('version')],
                customActor: $admin
            );
        });

        $record->load(['publisher:id,ho_ten,email', 'updater:id,ho_ten,email']);

        return response()->json([
            'status'  => 1,
            'message' => "Xuất bản khối [{$record->title}] thành công phiên bản v{$record->version}!",
            'data'    => $record,
        ]);
    }

    /**
     * Lấy danh sách lịch sử phiên bản của một khối nội dung
     */
    public function versions(string $sectionKey): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $this->authorizeAdmin($admin, [
            PermissionMatrix::PERM_CONTENT_VIEW,
            PermissionMatrix::PERM_CONTENT_EDIT,
            PermissionMatrix::PERM_CONTENT_PUBLISH,
            PermissionMatrix::PERM_CONTENT_MANAGE,
        ]);

        $record = WebsiteContent::query()->where('section_key', $sectionKey)->first();
        if (!$record) {
            return response()->json([
                'status'  => 0,
                'message' => "Không tìm thấy khối nội dung [{$sectionKey}].",
            ], 404);
        }

        $versions = WebsiteContentVersion::query()
            ->where('content_id', $record->id)
            ->with('creator:id,ho_ten,email')
            ->orderBy('version', 'desc')
            ->get();

        return response()->json([
            'status' => 1,
            'data'   => [
                'current_version' => $record->version,
                'versions'        => $versions,
            ],
        ]);
    }

    /**
     * Khôi phục (Rollback/Restore) về một phiên bản lịch sử
     */
    public function restoreVersion(Request $request, string $sectionKey, int $versionId): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        $this->authorizeAdmin($admin, [
            PermissionMatrix::PERM_CONTENT_PUBLISH,
            PermissionMatrix::PERM_CONTENT_MANAGE,
        ]);

        $record = WebsiteContent::query()->where('section_key', $sectionKey)->first();
        if (!$record) {
            return response()->json([
                'status'  => 0,
                'message' => "Không tìm thấy khối nội dung [{$sectionKey}].",
            ], 404);
        }

        $targetVersion = WebsiteContentVersion::query()
            ->where('id', $versionId)
            ->where('content_id', $record->id)
            ->first();

        if (!$targetVersion) {
            return response()->json([
                'status'  => 0,
                'message' => "Không tìm thấy phiên bản lịch sử chỉ định.",
            ], 404);
        }

        $restoredContent = $targetVersion->content;
        // Validate dữ liệu phiên bản cần khôi phục
        $this->validationService->validateSectionContent($sectionKey, $restoredContent);

        DB::transaction(function () use ($record, $targetVersion, $restoredContent, $admin, $sectionKey) {
            $newVersion = $record->version + 1;

            $record->published_content = $restoredContent;
            $record->draft_content = $restoredContent;
            $record->status = 'published';
            $record->has_unpublished_changes = false;
            $record->version = $newVersion;
            $record->published_at = now();
            $record->publisher_id = $admin->id;
            $record->updated_by = $admin->id;
            $record->save();

            // Ghi nhận snapshot khôi phục
            $record->recordSnapshot(
                $admin->id,
                "Khôi phục từ phiên bản v{$targetVersion->version}"
            );

            // Làm mới Cache
            Cache::forget(PublicContentController::PUBLIC_CACHE_KEY_ALL);
            Cache::forget(PublicContentController::PUBLIC_CACHE_KEY_PREFIX . $sectionKey);

            AuditService::log(
                action: 'content.restored',
                targetType: 'website_content',
                targetId: $record->id,
                targetLabel: "{$record->title} khôi phục từ v{$targetVersion->version} thành v{$newVersion}",
                newValues: ['restored_from_version' => $targetVersion->version, 'version' => $newVersion],
                oldValues: ['version' => $record->getOriginal('version')],
                customActor: $admin
            );
        });

        $record->load(['publisher:id,ho_ten,email', 'updater:id,ho_ten,email']);

        return response()->json([
            'status'  => 1,
            'message' => "Đã khôi phục thành công nội dung về phiên bản v{$targetVersion->version} (tạo mới v{$record->version})!",
            'data'    => $record,
        ]);
    }

    /**
     * Lấy đối tượng admin đang đăng nhập
     */
    protected function getAuthenticatedAdmin()
    {
        $admin = Auth::guard('admin')->user() ?? Auth::guard('sanctum')->user();
        if (!$admin) {
            abort(response()->json([
                'status'  => 0,
                'message' => 'Phiên đăng nhập không hợp lệ hoặc đã hết hạn.',
            ], 401));
        }
        return $admin;
    }

    /**
     * Kiểm tra quyền thực thi của Admin
     */
    protected function authorizeAdmin($admin, array $permissions): void
    {
        if (method_exists($admin, 'isOwner') && $admin->isOwner()) {
            return;
        }

        if (method_exists($admin, 'hasPermission')) {
            foreach ($permissions as $permission) {
                if ($admin->hasPermission($permission)) {
                    return;
                }
            }
        }

        abort(response()->json([
            'status'  => 0,
            'message' => 'Bạn không có quyền thực hiện thao tác này trên hệ thống CMS.',
        ], 403));
    }
}
