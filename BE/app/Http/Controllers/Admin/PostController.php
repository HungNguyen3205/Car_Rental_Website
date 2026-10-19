<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Post;
use App\Models\PostCategory;
use App\Services\Audit\AuditService;
use App\Services\Authorization\PermissionMatrix;
use App\Services\Security\HtmlSanitizerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Lấy danh sách bài viết trang quản trị kèm bộ lọc, tìm kiếm, phân trang và thống kê KPI
     */
    public function index(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (!$admin) {
            return response()->json(['status' => 0, 'message' => 'Chưa xác thực quản trị viên!'], 401);
        }

        $filters = [
            'search'               => $request->input('search'),
            'status'               => $request->input('status', 'all'),
            'category_id'          => $request->input('category_id', 'all'),
            'author_id'            => $request->input('author_id', 'all'),
            'has_pending_revision' => $request->input('has_pending_revision'),
        ];

        $perPage = max(1, min((int) $request->input('per_page', 12), 100));

        // Base query
        $query = Post::query()
            ->with(['category:id,name,slug', 'author:id,ho_ten,email', 'reviewer:id,ho_ten'])
            ->filter($filters);

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = strtolower($request->input('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['id', 'title', 'views_count', 'published_at', 'created_at', 'updated_at'], true)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderByDesc('id');
        }

        $paginated = $query->paginate($perPage);

        // KPI Counts
        $kpi = [
            'total'             => Post::count(),
            'draft'             => Post::where('status', Post::STATUS_DRAFT)->count(),
            'pending_review'    => Post::where('status', Post::STATUS_PENDING_REVIEW)->count(),
            'published'         => Post::where('status', Post::STATUS_PUBLISHED)->count(),
            'archived'          => Post::where('status', Post::STATUS_ARCHIVED)->count(),
            'trashed'           => Post::onlyTrashed()->count(),
            'pending_revisions' => Post::where('has_pending_revision', true)->count(),
        ];

        // Danh sách chuyên mục và tác giả phục vụ dropdown lọc
        $categories = PostCategory::orderBy('display_order')->orderBy('name')->get(['id', 'name', 'slug']);
        $authors = Admin::whereHas('posts')->select(['id', 'ho_ten', 'email'])->get();

        return response()->json([
            'status' => 1,
            'data'   => $paginated,
            'kpi'    => $kpi,
            'meta'   => [
                'categories' => $categories,
                'authors'    => $authors,
                'user_permissions' => [
                    'can_create'  => $this->canCreate($admin),
                    'can_review'  => $this->canReview($admin),
                    'can_publish' => $this->canPublish($admin),
                    'can_delete'  => $this->canDelete($admin),
                ],
            ],
        ]);
    }

    /**
     * Danh sách chuyên mục bài viết
     */
    public function getCategories(): JsonResponse
    {
        $categories = PostCategory::withCount(['posts'])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 1,
            'data'   => $categories,
        ]);
    }

    /**
     * Thêm chuyên mục mới
     */
    public function storeCategory(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (!$this->canCreate($admin) && !$this->canPublish($admin)) {
            return response()->json(['status' => 0, 'message' => 'Bạn không có quyền tạo chuyên mục!'], 403);
        }

        $validated = $request->validate([
            'name'          => 'required|string|max:150',
            'slug'          => 'nullable|string|max:150',
            'description'   => 'nullable|string|max:1000',
            'is_active'     => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'], '-')
            : PostCategory::generateUniqueSlug($validated['name']);

        if (PostCategory::where('slug', $slug)->exists()) {
            $slug = PostCategory::generateUniqueSlug($validated['name']);
        }

        $category = PostCategory::create([
            'name'          => trim($validated['name']),
            'slug'          => $slug,
            'description'   => $validated['description'] ?? null,
            'is_active'     => $validated['is_active'] ?? true,
            'display_order' => $validated['display_order'] ?? 0,
        ]);

        AuditService::log('post_category.created', 'PostCategory', $category->id, $category->name, $category->toArray());

        return response()->json([
            'status'  => 1,
            'message' => 'Tạo chuyên mục thành công!',
            'data'    => $category,
        ]);
    }

    /**
     * Cập nhật chuyên mục
     */
    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (!$this->canPublish($admin)) {
            return response()->json(['status' => 0, 'message' => 'Bạn không có quyền chỉnh sửa chuyên mục!'], 403);
        }

        $category = PostCategory::findOrFail($id);

        $validated = $request->validate([
            'name'          => 'required|string|max:150',
            'slug'          => 'nullable|string|max:150',
            'description'   => 'nullable|string|max:1000',
            'is_active'     => 'boolean',
            'display_order' => 'integer|min:0',
        ]);

        $old = $category->toArray();

        $slug = !empty($validated['slug'])
            ? Str::slug($validated['slug'], '-')
            : PostCategory::generateUniqueSlug($validated['name'], $category->id);

        $category->update([
            'name'          => trim($validated['name']),
            'slug'          => $slug,
            'description'   => $validated['description'] ?? null,
            'is_active'     => $validated['is_active'] ?? $category->is_active,
            'display_order' => $validated['display_order'] ?? $category->display_order,
        ]);

        AuditService::log('post_category.updated', 'PostCategory', $category->id, $category->name, $category->toArray(), $old);

        return response()->json([
            'status'  => 1,
            'message' => 'Cập nhật chuyên mục thành công!',
            'data'    => $category,
        ]);
    }

    /**
     * Xóa chuyên mục
     */
    public function destroyCategory(int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (!$this->canPublish($admin)) {
            return response()->json(['status' => 0, 'message' => 'Bạn không có quyền xóa chuyên mục!'], 403);
        }

        $category = PostCategory::withCount('posts')->findOrFail($id);

        if ($category->posts_count > 0) {
            // Chuyển các bài viết sang chuyên mục rỗng thay vì xóa bài
            Post::where('category_id', $category->id)->update(['category_id' => null]);
        }

        $old = $category->toArray();
        $category->delete();

        AuditService::log('post_category.deleted', 'PostCategory', $id, $old['name'], null, $old);

        return response()->json([
            'status'  => 1,
            'message' => 'Đã xóa chuyên mục!',
        ]);
    }

    /**
     * Xem chi tiết một bài viết trong trang quản trị
     */
    public function show(int $id): JsonResponse
    {
        $post = Post::withTrashed()
            ->with(['category', 'author:id,ho_ten,email,role', 'reviewer:id,ho_ten,email'])
            ->findOrFail($id);

        return response()->json([
            'status' => 1,
            'data'   => $post,
        ]);
    }

    /**
     * Xem trước riêng tư dành cho Quản trị viên / Biên tập viên
     * Yêu cầu xác thực quyền nội bộ, tuyệt đối không lộ bản nháp/bản chưa duyệt ra API công khai
     */
    public function preview(int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (!$admin) {
            return response()->json(['status' => 0, 'message' => 'Chưa xác thực quản trị viên!'], 401);
        }

        $post = Post::withTrashed()
            ->with(['category:id,name,slug', 'author:id,ho_ten,email', 'reviewer:id,ho_ten'])
            ->findOrFail($id);

        return response()->json([
            'status'       => 1,
            'data'         => $post,
            'preview_mode' => true,
            'preview_info' => [
                'previewed_by'     => $admin->ho_ten,
                'previewed_at'     => now()->toIso8601String(),
                'is_actual_public' => $post->status === Post::STATUS_PUBLISHED && $post->published_at && $post->published_at <= now() && !$post->trashed(),
            ],
        ]);
    }

    /**
     * Tạo bài viết mới (hỗ trợ lưu nháp, kiểm soát HTML allowlist, alt text)
     */
    public function store(Request $request): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (!$this->canCreate($admin) && !$this->canPublish($admin)) {
            return response()->json(['status' => 0, 'message' => 'Bạn không có quyền tạo bài viết!'], 403);
        }

        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'slug'               => 'nullable|string|max:255',
            'summary'            => 'nullable|string|max:1000',
            'content'            => 'required|string',
            'featured_image'     => 'nullable|string|max:500',
            'featured_image_alt' => 'nullable|string|max:255',
            'category_id'        => 'nullable|exists:post_categories,id',
            'action'             => 'required|in:save_draft,submit_review,publish',
            'published_at'       => 'nullable|date',
            'meta_title'         => 'nullable|string|max:255',
            'meta_description'   => 'nullable|string|max:500',
            'meta_keywords'      => 'nullable|string|max:255',
        ]);

        // Kiểm tra quyền xuất bản nếu chọn action = publish
        if ($validated['action'] === 'publish' && !$this->canPublish($admin)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn chưa có quyền xuất bản bài viết! Vui lòng lưu nháp hoặc gửi yêu cầu kiểm duyệt.',
            ], 403);
        }

        // Tạo slug duy nhất
        $slug = !empty($validated['slug'])
            ? Post::generateUniqueSlug($validated['slug'])
            : Post::generateUniqueSlug($validated['title']);

        // Làm sạch nội dung HTML theo Allowlist nghiêm ngặt để chống XSS
        $cleanContent = HtmlSanitizerService::clean($validated['content']);

        // Xác định trạng thái và thời điểm xuất bản
        $status = match ($validated['action']) {
            'publish'       => Post::STATUS_PUBLISHED,
            'submit_review' => Post::STATUS_PENDING_REVIEW,
            default         => Post::STATUS_DRAFT,
        };

        $publishedAt = null;
        if ($status === Post::STATUS_PUBLISHED) {
            $publishedAt = !empty($validated['published_at'])
                ? date('Y-m-d H:i:s', strtotime($validated['published_at']))
                : now();
        }

        $post = Post::create([
            'title'              => trim($validated['title']),
            'slug'               => $slug,
            'summary'            => $validated['summary'] ?? null,
            'content'            => $cleanContent,
            'featured_image'     => $validated['featured_image'] ?? null,
            'featured_image_alt' => $validated['featured_image_alt'] ?? null,
            'category_id'        => $validated['category_id'] ?? null,
            'author_id'          => $admin->id,
            'status'             => $status,
            'published_at'       => $publishedAt,
            'meta_title'         => $validated['meta_title'] ?? null,
            'meta_description'   => $validated['meta_description'] ?? null,
            'meta_keywords'      => $validated['meta_keywords'] ?? null,
        ]);

        AuditService::log('post.created', 'Post', $post->id, $post->title, $post->toArray());

        $message = match ($status) {
            Post::STATUS_PUBLISHED      => 'Bài viết đã được xuất bản thành công!',
            Post::STATUS_PENDING_REVIEW => 'Bài viết đã được gửi đến ban biên tập chờ duyệt!',
            default                     => 'Lưu bản nháp thành công!',
        };

        return response()->json([
            'status'  => 1,
            'message' => $message,
            'data'    => $post->load(['category', 'author:id,ho_ten']),
        ]);
    }

    /**
     * Cập nhật bài viết
     * Quy tắc an toàn:
     * 1. Xử lý xung đột sửa đổi (Optimistic Locking) thông qua expected_updated_at.
     * 2. Làm sạch nội dung HTML bằng Allowlist.
     * 3. Nếu bài viết đang ở trạng thái đã xuất bản (published) và người sửa KHÔNG có quyền posts.publish,
     *    thay đổi sẽ được lưu vào pending_changes để chờ duyệt, tuyệt đối không đè lên nội dung công khai!
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (!$admin) {
            return response()->json(['status' => 0, 'message' => 'Chưa xác thực quản trị viên!'], 401);
        }

        $post = Post::findOrFail($id);

        // Kiểm tra quyền chỉnh sửa
        if (!$this->canEditPost($admin, $post)) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bạn không có quyền chỉnh sửa bài viết này!',
            ], 403);
        }

        // XỬ LÝ XUNG ĐỘT CHỈNH SỬA (OPTIMISTIC LOCKING)
        if (!$request->boolean('force_overwrite') && $request->filled('expected_updated_at')) {
            $clientTime = strtotime($request->input('expected_updated_at'));
            $serverTime = $post->updated_at ? $post->updated_at->timestamp : 0;
            // Nếu server có bản cập nhật mới hơn thời điểm client tải trên 1 giây
            if ($clientTime && $serverTime > ($clientTime + 1)) {
                return response()->json([
                    'status'            => 0,
                    'conflict'          => true,
                    'message'           => 'Bài viết đã được chỉnh sửa và lưu bởi một phiên làm việc khác kể từ lần cuối bạn tải dữ liệu! Vui lòng làm mới để xem nội dung mới nhất.',
                    'server_updated_at' => $post->updated_at->toIso8601String(),
                    'server_title'      => $post->title,
                ], 409);
            }
        }

        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'slug'                => 'nullable|string|max:255',
            'summary'             => 'nullable|string|max:1000',
            'content'             => 'required|string',
            'featured_image'      => 'nullable|string|max:500',
            'featured_image_alt'  => 'nullable|string|max:255',
            'category_id'         => 'nullable|exists:post_categories,id',
            'action'              => 'nullable|in:save,submit_review,publish,archive',
            'published_at'        => 'nullable|date',
            'revision_notes'      => 'nullable|string|max:500',
            'meta_title'          => 'nullable|string|max:255',
            'meta_description'    => 'nullable|string|max:500',
            'meta_keywords'       => 'nullable|string|max:255',
            'expected_updated_at' => 'nullable|string',
            'force_overwrite'     => 'nullable|boolean',
        ]);

        $old = $post->toArray();
        $isPublisher = $this->canPublish($admin);

        // Làm sạch nội dung HTML theo Allowlist nghiêm ngặt
        $cleanContent = HtmlSanitizerService::clean($validated['content']);

        // QUY TẮC ĐẶC BIỆT: BÀI ĐÃ XUẤT BẢN ĐƯỢC CHỈNH SỬA
        if ($post->status === Post::STATUS_PUBLISHED) {
            if (!$isPublisher) {
                // Tác giả không có quyền xuất bản: Lưu vào buffer pending_changes để duyệt
                $pendingChanges = [
                    'title'              => trim($validated['title']),
                    'summary'            => $validated['summary'] ?? null,
                    'content'            => $cleanContent,
                    'featured_image'     => $validated['featured_image'] ?? $post->featured_image,
                    'featured_image_alt' => $validated['featured_image_alt'] ?? $post->featured_image_alt,
                    'category_id'        => $validated['category_id'] ?? $post->category_id,
                    'meta_title'         => $validated['meta_title'] ?? $post->meta_title,
                    'meta_description'   => $validated['meta_description'] ?? $post->meta_description,
                    'meta_keywords'      => $validated['meta_keywords'] ?? $post->meta_keywords,
                    'editor_id'          => $admin->id,
                    'editor_name'        => $admin->ho_ten,
                    'submitted_at'       => now()->toIso8601String(),
                    'revision_notes'     => $validated['revision_notes'] ?? 'Đề xuất cập nhật nội dung bài viết đã đăng',
                ];

                $post->pending_changes = $pendingChanges;
                $post->has_pending_revision = true;
                $post->save();

                AuditService::log('post.revision_submitted', 'Post', $post->id, $post->title, $pendingChanges, $old);

                return response()->json([
                    'status'  => 1,
                    'message' => 'Nội dung chỉnh sửa đã được lưu vào bản duyệt sửa đổi. Bài viết công khai hiện tại vẫn giữ nguyên cho đến khi người có thẩm quyền phê duyệt.',
                    'data'    => $post->load(['category', 'author:id,ho_ten']),
                ]);
            }

            // Nếu người sửa có quyền xuất bản: Cập nhật trực tiếp và xóa cờ pending revision nếu có
            $slug = !empty($validated['slug'])
                ? Post::generateUniqueSlug($validated['slug'], $post->id)
                : $post->slug;

            $updateData = [
                'title'                => trim($validated['title']),
                'slug'                 => $slug,
                'summary'              => $validated['summary'] ?? null,
                'content'              => $cleanContent,
                'featured_image'       => $validated['featured_image'] ?? $post->featured_image,
                'featured_image_alt'   => $validated['featured_image_alt'] ?? $post->featured_image_alt,
                'category_id'          => $validated['category_id'] ?? null,
                'meta_title'           => $validated['meta_title'] ?? null,
                'meta_description'     => $validated['meta_description'] ?? null,
                'meta_keywords'        => $validated['meta_keywords'] ?? null,
                'has_pending_revision' => false,
                'pending_changes'      => null,
            ];

            if (!empty($validated['published_at'])) {
                $updateData['published_at'] = date('Y-m-d H:i:s', strtotime($validated['published_at']));
            }

            if (($validated['action'] ?? '') === 'archive') {
                $updateData['status'] = Post::STATUS_ARCHIVED;
            }

            $post->update($updateData);

            AuditService::log('post.updated_published', 'Post', $post->id, $post->title, $post->toArray(), $old);

            return response()->json([
                'status'  => 1,
                'message' => 'Cập nhật bài viết xuất bản thành công!',
                'data'    => $post->load(['category', 'author:id,ho_ten']),
            ]);
        }

        // BÀI VIẾT ĐANG Ở TRẠNG THÁI DRAFT / PENDING REVIEW / ARCHIVED
        $slug = !empty($validated['slug'])
            ? Post::generateUniqueSlug($validated['slug'], $post->id)
            : $post->slug;

        $targetStatus = $post->status;
        $action = $validated['action'] ?? 'save';

        if ($action === 'submit_review') {
            $targetStatus = Post::STATUS_PENDING_REVIEW;
        } elseif ($action === 'publish') {
            if (!$isPublisher) {
                return response()->json(['status' => 0, 'message' => 'Bạn không có quyền xuất bản bài viết!'], 403);
            }
            $targetStatus = Post::STATUS_PUBLISHED;
        } elseif ($action === 'archive') {
            if (!$isPublisher) {
                return response()->json(['status' => 0, 'message' => 'Bạn không có quyền lưu trữ bài viết!'], 403);
            }
            $targetStatus = Post::STATUS_ARCHIVED;
        }

        $publishedAt = $post->published_at;
        if ($targetStatus === Post::STATUS_PUBLISHED && empty($publishedAt)) {
            $publishedAt = !empty($validated['published_at'])
                ? date('Y-m-d H:i:s', strtotime($validated['published_at']))
                : now();
        } elseif (!empty($validated['published_at'])) {
            $publishedAt = date('Y-m-d H:i:s', strtotime($validated['published_at']));
        }

        $post->update([
            'title'              => trim($validated['title']),
            'slug'               => $slug,
            'summary'            => $validated['summary'] ?? null,
            'content'            => $cleanContent,
            'featured_image'     => $validated['featured_image'] ?? $post->featured_image,
            'featured_image_alt' => $validated['featured_image_alt'] ?? $post->featured_image_alt,
            'category_id'        => $validated['category_id'] ?? null,
            'status'             => $targetStatus,
            'published_at'       => $publishedAt,
            'meta_title'         => $validated['meta_title'] ?? null,
            'meta_description'   => $validated['meta_description'] ?? null,
            'meta_keywords'      => $validated['meta_keywords'] ?? null,
        ]);

        AuditService::log('post.updated', 'Post', $post->id, $post->title, $post->toArray(), $old);

        return response()->json([
            'status'  => 1,
            'message' => 'Cập nhật bài viết thành công!',
            'data'    => $post->load(['category', 'author:id,ho_ten']),
        ]);
    }

    /**
     * Quy trình kiểm duyệt bài viết (Duyệt, Yêu cầu sửa, Duyệt bản sửa đổi)
     */
    public function submitOrReview(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (!$admin) {
            return response()->json(['status' => 0, 'message' => 'Chưa xác thực quản trị viên!'], 401);
        }

        $post = Post::findOrFail($id);

        $validated = $request->validate([
            'action'           => 'required|in:submit_review,approve_review,reject_review,approve_revision,reject_revision',
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $action = $validated['action'];
        $old = $post->toArray();

        // 1. Tác giả gửi duyệt bài nháp
        if ($action === 'submit_review') {
            if (!$this->canEditPost($admin, $post)) {
                return response()->json(['status' => 0, 'message' => 'Bạn không có quyền gửi duyệt bài này!'], 403);
            }

            $post->update([
                'status'           => Post::STATUS_PENDING_REVIEW,
                'rejection_reason' => null,
            ]);

            AuditService::log('post.submitted_for_review', 'Post', $post->id, $post->title, $post->toArray(), $old);

            return response()->json([
                'status'  => 1,
                'message' => 'Đã gửi bài viết đến ban biên tập để kiểm duyệt!',
                'data'    => $post,
            ]);
        }

        // 2. Kiểm duyệt viên duyệt bài chờ xuất bản
        if ($action === 'approve_review') {
            if (!$this->canReview($admin) && !$this->canPublish($admin)) {
                return response()->json(['status' => 0, 'message' => 'Bạn không có quyền kiểm duyệt bài viết!'], 403);
            }

            // Nếu người duyệt có quyền xuất bản, xuất bản luôn; nếu không, giữ trạng thái chờ xuất bản
            $status = $this->canPublish($admin) ? Post::STATUS_PUBLISHED : Post::STATUS_PENDING_REVIEW;
            $publishedAt = $status === Post::STATUS_PUBLISHED ? ($post->published_at ?? now()) : $post->published_at;

            $post->update([
                'status'           => $status,
                'published_at'     => $publishedAt,
                'reviewer_id'      => $admin->id,
                'reviewed_at'      => now(),
                'rejection_reason' => null,
            ]);

            AuditService::log('post.approved', 'Post', $post->id, $post->title, $post->toArray(), $old);

            return response()->json([
                'status'  => 1,
                'message' => $status === Post::STATUS_PUBLISHED ? 'Đã duyệt và xuất bản bài viết thành công!' : 'Đã duyệt bài viết! Chờ xuất bản.',
                'data'    => $post->load(['category', 'author:id,ho_ten', 'reviewer:id,ho_ten']),
            ]);
        }

        // 3. Kiểm duyệt viên yêu cầu sửa đổi / từ chối duyệt
        if ($action === 'reject_review') {
            if (!$this->canReview($admin) && !$this->canPublish($admin)) {
                return response()->json(['status' => 0, 'message' => 'Bạn không có quyền kiểm duyệt bài viết!'], 403);
            }

            $reason = trim($validated['rejection_reason'] ?? 'Cần chỉnh sửa nội dung bài viết theo yêu cầu ban biên tập');

            $post->update([
                'status'           => Post::STATUS_DRAFT,
                'reviewer_id'      => $admin->id,
                'reviewed_at'      => now(),
                'rejection_reason' => $reason,
            ]);

            AuditService::log('post.rejected', 'Post', $post->id, $post->title, ['rejection_reason' => $reason], $old);

            return response()->json([
                'status'  => 1,
                'message' => 'Đã gửi phản hồi yêu cầu tác giả chỉnh sửa lại bài viết!',
                'data'    => $post,
            ]);
        }

        // 4. Duyệt bản sửa đổi của bài đã xuất bản (Approve Revision)
        if ($action === 'approve_revision') {
            if (!$this->canPublish($admin)) {
                return response()->json(['status' => 0, 'message' => 'Chỉ người có quyền xuất bản mới được duyệt bản sửa đổi của bài đã đăng!'], 403);
            }

            if (!$post->has_pending_revision || empty($post->pending_changes)) {
                return response()->json(['status' => 0, 'message' => 'Bài viết này hiện không có bản sửa đổi nào chờ duyệt!'], 422);
            }

            $rev = $post->pending_changes;

            // Áp dụng nội dung mới vào bài viết chính thức
            $post->update([
                'title'                => $rev['title'] ?? $post->title,
                'summary'              => $rev['summary'] ?? $post->summary,
                'content'              => $rev['content'] ?? $post->content,
                'featured_image'       => $rev['featured_image'] ?? $post->featured_image,
                'featured_image_alt'   => $rev['featured_image_alt'] ?? $post->featured_image_alt,
                'category_id'          => $rev['category_id'] ?? $post->category_id,
                'meta_title'           => $rev['meta_title'] ?? $post->meta_title,
                'meta_description'     => $rev['meta_description'] ?? $post->meta_description,
                'meta_keywords'        => $rev['meta_keywords'] ?? $post->meta_keywords,
                'reviewer_id'          => $admin->id,
                'reviewed_at'          => now(),
                'has_pending_revision' => false,
                'pending_changes'      => null,
                'rejection_reason'     => null,
            ]);

            AuditService::log('post.revision_approved', 'Post', $post->id, $post->title, $post->toArray(), $old);

            return response()->json([
                'status'  => 1,
                'message' => 'Đã duyệt và áp dụng bản sửa đổi lên bài viết công khai thành công!',
                'data'    => $post->load(['category', 'author:id,ho_ten']),
            ]);
        }

        // 5. Từ chối bản sửa đổi của bài đã xuất bản (Reject Revision)
        if ($action === 'reject_revision') {
            if (!$this->canReview($admin) && !$this->canPublish($admin)) {
                return response()->json(['status' => 0, 'message' => 'Bạn không có quyền từ chối bản sửa đổi!'], 403);
            }

            $reason = trim($validated['rejection_reason'] ?? 'Bản sửa đổi không đáp ứng tiêu chuẩn biên tập');

            $post->update([
                'has_pending_revision' => false,
                'pending_changes'      => null,
                'rejection_reason'     => $reason,
            ]);

            AuditService::log('post.revision_rejected', 'Post', $post->id, $post->title, ['rejection_reason' => $reason], $old);

            return response()->json([
                'status'  => 1,
                'message' => 'Đã hủy bỏ bản sửa đổi đề xuất!',
                'data'    => $post,
            ]);
        }

        return response()->json(['status' => 0, 'message' => 'Hành động không hợp lệ!'], 400);
    }

    /**
     * Xuất bản bài viết
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (!$this->canPublish($admin)) {
            return response()->json(['status' => 0, 'message' => 'Bạn không có quyền xuất bản bài viết!'], 403);
        }

        $post = Post::findOrFail($id);
        $old = $post->toArray();

        $publishedAt = $request->input('published_at')
            ? date('Y-m-d H:i:s', strtotime($request->input('published_at')))
            : ($post->published_at ?? now());

        $post->update([
            'status'           => Post::STATUS_PUBLISHED,
            'published_at'     => $publishedAt,
            'reviewer_id'      => $admin->id,
            'reviewed_at'      => now(),
            'rejection_reason' => null,
        ]);

        AuditService::log('post.published', 'Post', $post->id, $post->title, $post->toArray(), $old);

        return response()->json([
            'status'  => 1,
            'message' => 'Đã xuất bản bài viết thành công!',
            'data'    => $post->load(['category', 'author:id,ho_ten']),
        ]);
    }

    /**
     * Chuyển bài viết sang trạng thái lưu trữ
     */
    public function archive(int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (!$this->canPublish($admin)) {
            return response()->json(['status' => 0, 'message' => 'Bạn không có quyền lưu trữ bài viết!'], 403);
        }

        $post = Post::findOrFail($id);
        $old = $post->toArray();

        $post->update([
            'status' => Post::STATUS_ARCHIVED,
        ]);

        AuditService::log('post.archived', 'Post', $post->id, $post->title, $post->toArray(), $old);

        return response()->json([
            'status'  => 1,
            'message' => 'Đã chuyển bài viết vào kho lưu trữ!',
            'data'    => $post,
        ]);
    }

    /**
     * Xóa mềm bài viết (Đưa vào thùng rác)
     */
    public function destroy(int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (!$this->canDelete($admin) && !$this->canPublish($admin)) {
            return response()->json(['status' => 0, 'message' => 'Bạn không có quyền xóa bài viết!'], 403);
        }

        $post = Post::findOrFail($id);
        $old = $post->toArray();

        $post->delete();

        AuditService::log('post.soft_deleted', 'Post', $post->id, $post->title, null, $old);

        return response()->json([
            'status'  => 1,
            'message' => 'Đã đưa bài viết vào thùng rác!',
        ]);
    }

    /**
     * Khôi phục bài viết từ thùng rác
     */
    public function restore(int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (!$this->canDelete($admin) && !$this->canPublish($admin)) {
            return response()->json(['status' => 0, 'message' => 'Bạn không có quyền khôi phục bài viết!'], 403);
        }

        $post = Post::onlyTrashed()->findOrFail($id);
        $post->restore();

        AuditService::log('post.restored', 'Post', $post->id, $post->title, $post->toArray());

        return response()->json([
            'status'  => 1,
            'message' => 'Đã khôi phục bài viết từ thùng rác thành công!',
            'data'    => $post,
        ]);
    }

    /**
     * Xóa vĩnh viễn bài viết khỏi hệ thống
     */
    public function forceDelete(int $id): JsonResponse
    {
        $admin = $this->getAuthenticatedAdmin();
        if (!$this->canDelete($admin) && !$this->canPublish($admin)) {
            return response()->json(['status' => 0, 'message' => 'Bạn không có quyền xóa vĩnh viễn bài viết!'], 403);
        }

        $post = Post::onlyTrashed()->findOrFail($id);
        $old = $post->toArray();

        $post->forceDelete();

        AuditService::log('post.force_deleted', 'Post', $id, $old['title'], null, $old);

        return response()->json([
            'status'  => 1,
            'message' => 'Đã xóa vĩnh viễn bài viết!',
        ]);
    }

    // ==========================================
    // HELPER AUTHORIZATION METHODS
    // ==========================================

    protected function getAuthenticatedAdmin(): ?Admin
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            $user = Auth::guard('sanctum')->user();
            if ($user instanceof Admin) {
                $admin = $user;
            }
        }
        return $admin;
    }

    protected function canCreate(Admin $admin): bool
    {
        return $admin->isOwner() || $admin->hasPermission(PermissionMatrix::PERM_POSTS_CREATE);
    }

    protected function canReview(Admin $admin): bool
    {
        return $admin->isOwner() || $admin->hasPermission(PermissionMatrix::PERM_POSTS_REVIEW);
    }

    protected function canPublish(Admin $admin): bool
    {
        return $admin->isOwner() || $admin->hasPermission(PermissionMatrix::PERM_POSTS_PUBLISH);
    }

    protected function canDelete(Admin $admin): bool
    {
        return $admin->isOwner() || $admin->hasPermission(PermissionMatrix::PERM_POSTS_DELETE);
    }

    protected function canEditPost(Admin $admin, Post $post): bool
    {
        if ($admin->isOwner()) {
            return true;
        }

        if ($admin->hasPermission(PermissionMatrix::PERM_POSTS_PUBLISH) || $admin->hasPermission(PermissionMatrix::PERM_POSTS_REVIEW)) {
            return true;
        }

        // Tác giả chỉ được sửa bài của mình
        if ($admin->hasPermission(PermissionMatrix::PERM_POSTS_CREATE) && (int) $post->author_id === (int) $admin->id) {
            return true;
        }

        return false;
    }
}
