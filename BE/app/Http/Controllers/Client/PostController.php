<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * Danh sách bài viết công khai
     * CHỈ trả về bài viết đã xuất bản hợp lệ (status = published, published_at <= now(), deleted_at IS NULL)
     * Tuyệt đối không để lộ bản nháp, bài chờ duyệt, bài lưu trữ hoặc tài liệu nội bộ
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->input('per_page', 9), 30));

        $query = Post::published()
            ->with([
                'category:id,name,slug',
                'author:id,ho_ten',
            ])
            ->select([
                'id',
                'title',
                'slug',
                'summary',
                'featured_image',
                'category_id',
                'author_id',
                'published_at',
                'views_count',
            ]);

        // Lọc theo chuyên mục (slug hoặc id)
        if ($request->filled('category')) {
            $catParam = $request->input('category');
            $query->whereHas('category', function ($q) use ($catParam) {
                if (is_numeric($catParam)) {
                    $q->where('id', $catParam);
                } else {
                    $q->where('slug', $catParam);
                }
            });
        }

        // Lọc theo từ khóa tìm kiếm
        if ($request->filled('search')) {
            $keyword = trim($request->input('search'));
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('summary', 'like', "%{$keyword}%");
            });
        }

        // Sắp xếp bài mới nhất lên đầu
        $posts = $query->orderByDesc('published_at')->paginate($perPage);

        return response()->json([
            'status' => 1,
            'data'   => $posts,
        ]);
    }

    /**
     * Chi tiết bài viết công khai theo slug
     */
    public function show(string $slug): JsonResponse
    {
        $post = Post::published()
            ->with([
                'category:id,name,slug,description',
                'author:id,ho_ten',
            ])
            ->where('slug', $slug)
            ->first();

        if (!$post) {
            return response()->json([
                'status'  => 0,
                'message' => 'Bài viết không tồn tại hoặc chưa được công khai!',
            ], 404);
        }

        // Tăng lượt xem an toàn
        $post->increment('views_count');

        // Lấy 3 bài viết liên quan cùng chuyên mục
        $relatedPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->when($post->category_id, fn($q) => $q->where('category_id', $post->category_id))
            ->with(['category:id,name,slug', 'author:id,ho_ten'])
            ->select(['id', 'title', 'slug', 'summary', 'featured_image', 'category_id', 'author_id', 'published_at', 'views_count'])
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        // Ẩn các trường nội bộ không công khai
        $post->makeHidden(['pending_changes', 'has_pending_revision', 'rejection_reason', 'reviewer_id', 'reviewed_at', 'deleted_at']);

        return response()->json([
            'status' => 1,
            'data'   => [
                'post'          => $post,
                'related_posts' => $relatedPosts,
            ],
        ]);
    }

    /**
     * Danh sách chuyên mục có bài viết công khai
     */
    public function categories(): JsonResponse
    {
        $categories = PostCategory::active()
            ->withCount(['posts' => fn($q) => $q->published()])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'display_order']);

        return response()->json([
            'status' => 1,
            'data'   => $categories,
        ]);
    }
}
