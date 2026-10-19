<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Post;
use App\Models\PostCategory;
use App\Services\Authorization\PermissionMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostManagementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $owner;
    protected Admin $author;
    protected Admin $reviewer;
    protected Admin $publisher;
    protected Admin $unauthorizedStaff;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Chủ hệ thống (Owner - toàn quyền)
        $this->owner = Admin::create([
            'ho_ten'        => 'Chủ Hệ Thống',
            'email'         => 'owner_posts@test.com',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901000001',
            'role'          => PermissionMatrix::ROLE_OWNER,
            'permissions'   => PermissionMatrix::getDefaultPermissionsForRole(PermissionMatrix::ROLE_OWNER),
            'tinh_trang'    => 1,
        ]);

        // 2. Tác giả (Author - chỉ có quyền posts.create, posts.view)
        $this->author = Admin::create([
            'ho_ten'        => 'Tác Giả Biên Tập',
            'email'         => 'author@test.com',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901000002',
            'role'          => PermissionMatrix::ROLE_DISPATCHER,
            'permissions'   => [
                PermissionMatrix::PERM_POSTS_VIEW,
                PermissionMatrix::PERM_POSTS_CREATE,
            ],
            'tinh_trang'    => 1,
        ]);

        // 3. Kiểm duyệt viên (Reviewer - posts.view, posts.review)
        $this->reviewer = Admin::create([
            'ho_ten'        => 'Kiểm Duyệt Viên',
            'email'         => 'reviewer@test.com',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901000003',
            'role'          => PermissionMatrix::ROLE_DISPATCHER,
            'permissions'   => [
                PermissionMatrix::PERM_POSTS_VIEW,
                PermissionMatrix::PERM_POSTS_REVIEW,
            ],
            'tinh_trang'    => 1,
        ]);

        // 4. Quản lý xuất bản (Publisher - posts.view, posts.review, posts.publish, posts.delete)
        $this->publisher = Admin::create([
            'ho_ten'        => 'Trưởng Ban Xuất Bản',
            'email'         => 'publisher@test.com',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901000004',
            'role'          => PermissionMatrix::ROLE_DISPATCHER,
            'permissions'   => [
                PermissionMatrix::PERM_POSTS_VIEW,
                PermissionMatrix::PERM_POSTS_CREATE,
                PermissionMatrix::PERM_POSTS_REVIEW,
                PermissionMatrix::PERM_POSTS_PUBLISH,
                PermissionMatrix::PERM_POSTS_DELETE,
            ],
            'tinh_trang'    => 1,
        ]);

        // 5. Nhân viên không có quyền (Chỉ có bookings.view)
        $this->unauthorizedStaff = Admin::create([
            'ho_ten'        => 'Nhân Viên Không Quyền',
            'email'         => 'no_perm@test.com',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901000005',
            'role'          => PermissionMatrix::ROLE_ACCOUNTANT,
            'permissions'   => [PermissionMatrix::PERM_BOOKINGS_VIEW],
            'tinh_trang'    => 1,
        ]);
    }

    /**
     * 1. Kiểm tra phân quyền: Tác giả không thể tự ý xuất bản trực tiếp, nhân viên không quyền bị từ chối
     */
    public function test_permission_checks_for_author_reviewer_and_publisher(): void
    {
        // Nhân viên không có quyền bị từ chối truy cập API quản lý bài viết
        Sanctum::actingAs($this->unauthorizedStaff, ['*'], 'admin');
        $res = $this->getJson('/api/admin/posts');
        $res->assertStatus(403);

        // Tác giả có thể xem danh sách
        Sanctum::actingAs($this->author, ['*'], 'admin');
        $res = $this->getJson('/api/admin/posts');
        $res->assertStatus(200);

        // Tác giả tạo bài nháp thành công
        $resDraft = $this->postJson('/api/admin/posts', [
            'title'   => 'Bài viết hướng dẫn thuê xe',
            'content' => 'Nội dung chi tiết của bài viết hướng dẫn...',
            'action'  => 'save_draft',
        ]);
        $resDraft->assertStatus(200);
        $resDraft->assertJsonPath('data.status', Post::STATUS_DRAFT);

        // Tác giả cố tình chọn action=publish mà không có quyền xuất bản -> Bị chặn 403
        $resPublish = $this->postJson('/api/admin/posts', [
            'title'   => 'Bài viết thử xuất bản trái phép',
            'content' => 'Nội dung bài viết...',
            'action'  => 'publish',
        ]);
        $resPublish->assertStatus(403);

        // Trưởng ban xuất bản có thể xuất bản bài viết trực tiếp
        Sanctum::actingAs($this->publisher, ['*'], 'admin');
        $resPublisher = $this->postJson('/api/admin/posts', [
            'title'   => 'Thông báo chính thức từ ban giám đốc',
            'content' => 'Nội dung thông báo chính thức...',
            'action'  => 'publish',
        ]);
        $resPublisher->assertStatus(200);
        $resPublisher->assertJsonPath('data.status', Post::STATUS_PUBLISHED);
    }

    /**
     * 2. API công khai CHỈ trả về bài viết đã xuất bản hợp lệ, không lộ nháp, bài chờ duyệt, bài lưu trữ hoặc thùng rác
     */
    public function test_public_api_only_returns_valid_published_posts(): void
    {
        // Tạo các loại bài viết khác nhau
        $draft = Post::create([
            'title'        => 'Bản nháp bí mật nội bộ',
            'slug'         => 'ban-nhap-bi-mat',
            'content'      => 'Nội dung không được lộ',
            'author_id'    => $this->author->id,
            'status'       => Post::STATUS_DRAFT,
        ]);

        $pending = Post::create([
            'title'        => 'Bài viết chờ duyệt biên tập',
            'slug'         => 'bai-viet-cho-duyet',
            'content'      => 'Nội dung đang chờ ban biên tập xét duyệt',
            'author_id'    => $this->author->id,
            'status'       => Post::STATUS_PENDING_REVIEW,
        ]);

        $archived = Post::create([
            'title'        => 'Bài viết đã lưu trữ năm 2024',
            'slug'         => 'bai-viet-luu-tru-2024',
            'content'      => 'Nội dung cũ đã lưu trữ',
            'author_id'    => $this->author->id,
            'status'       => Post::STATUS_ARCHIVED,
            'published_at' => now()->subYear(),
        ]);

        $futureScheduled = Post::create([
            'title'        => 'Bài viết hẹn giờ xuất bản tương lai',
            'slug'         => 'bai-viet-hen-gio-tuong-lai',
            'content'      => 'Nội dung sẽ xuất bản vào tuần sau',
            'author_id'    => $this->author->id,
            'status'       => Post::STATUS_PUBLISHED,
            'published_at' => now()->addDays(7), // Chưa đến giờ xuất bản
        ]);

        $trashed = Post::create([
            'title'        => 'Bài viết đã bị xóa vào thùng rác',
            'slug'         => 'bai-viet-thung-rac',
            'content'      => 'Nội dung đã xóa',
            'author_id'    => $this->author->id,
            'status'       => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
        $trashed->delete(); // Soft delete

        $validPublished = Post::create([
            'title'        => 'Cẩm nang du lịch và thuê xe VinFast VF8',
            'slug'         => 'cam-nang-thue-xe-vf8',
            'summary'      => 'Kinh nghiệm lái xe xuyên Việt với dòng xe điện VF8 hiện đại',
            'content'      => 'Chi tiết hành trình 2.000km từ Hà Nội đến TP.HCM với xe điện...',
            'author_id'    => $this->author->id,
            'status'       => Post::STATUS_PUBLISHED,
            'published_at' => now()->subHours(2),
        ]);

        // Gọi API công khai danh sách bài viết
        $res = $this->getJson('/api/posts');
        $res->assertStatus(200);

        $items = $res->json('data.data');
        $this->assertCount(1, $items);
        $this->assertEquals($validPublished->id, $items[0]['id']);
        $this->assertEquals('cam-nang-thue-xe-vf8', $items[0]['slug']);

        // Gọi API chi tiết bài viết công khai
        $resDetail = $this->getJson('/api/posts/cam-nang-thue-xe-vf8');
        $resDetail->assertStatus(200);
        $resDetail->assertJsonPath('data.post.id', $validPublished->id);
        $this->assertEquals(1, $validPublished->fresh()->views_count);

        // Kiểm tra không để lộ các bài chưa xuất bản hợp lệ qua slug
        $this->getJson("/api/posts/{$draft->slug}")->assertStatus(404);
        $this->getJson("/api/posts/{$pending->slug}")->assertStatus(404);
        $this->getJson("/api/posts/{$archived->slug}")->assertStatus(404);
        $this->getJson("/api/posts/{$futureScheduled->slug}")->assertStatus(404);
        $this->getJson("/api/posts/{$trashed->slug}")->assertStatus(404);
    }

    /**
     * 3. Kiểm tra tính duy nhất của slug và tự động xử lý trùng lặp (Slug collision resolution)
     */
    public function test_unique_slug_generation_and_collision_handling(): void
    {
        Sanctum::actingAs($this->owner, ['*'], 'admin');

        // Tạo bài thứ nhất
        $res1 = $this->postJson('/api/admin/posts', [
            'title'   => 'Kinh nghiệm thuê xe tự lái',
            'content' => 'Nội dung bài 1...',
            'action'  => 'save_draft',
        ]);
        $res1->assertStatus(200);
        $slug1 = $res1->json('data.slug');
        $this->assertEquals('kinh-nghiem-thue-xe-tu-lai', $slug1);

        // Tạo bài thứ hai với cùng tiêu đề
        $res2 = $this->postJson('/api/admin/posts', [
            'title'   => 'Kinh nghiệm thuê xe tự lái',
            'content' => 'Nội dung bài 2...',
            'action'  => 'save_draft',
        ]);
        $res2->assertStatus(200);
        $slug2 = $res2->json('data.slug');
        $this->assertEquals('kinh-nghiem-thue-xe-tu-lai-1', $slug2);

        // Tạo bài thứ ba với cùng tiêu đề
        $res3 = $this->postJson('/api/admin/posts', [
            'title'   => 'Kinh nghiệm thuê xe tự lái',
            'content' => 'Nội dung bài 3...',
            'action'  => 'save_draft',
        ]);
        $res3->assertStatus(200);
        $slug3 = $res3->json('data.slug');
        $this->assertEquals('kinh-nghiem-thue-xe-tu-lai-2', $slug3);

        // Cập nhật lại bài 1 nhưng giữ nguyên slug không bị đụng độ với chính nó
        $id1 = $res1->json('data.id');
        $resUpdate = $this->putJson("/api/admin/posts/{$id1}", [
            'title'   => 'Kinh nghiệm thuê xe tự lái (Cập nhật)',
            'slug'    => 'kinh-nghiem-thue-xe-tu-lai',
            'content' => 'Nội dung bài 1 đã cập nhật...',
        ]);
        $resUpdate->assertStatus(200);
        $this->assertEquals('kinh-nghiem-thue-xe-tu-lai', $resUpdate->json('data.slug'));
    }

    /**
     * 4. QUY TẮC CỐT LÕI: Bài sửa sau khi xuất bản bởi người không có quyền publish
     * sẽ được đưa vào buffer pending_changes, tuyệt đối KHÔNG đè lên nội dung công khai
     */
    public function test_published_post_editing_rule_non_publisher_edits_go_to_pending_changes(): void
    {
        // 1. Tạo bài viết đã xuất bản
        $post = Post::create([
            'title'        => 'Nội dung gốc đã duyệt ban đầu',
            'slug'         => 'bai-viet-goc-da-duyet',
            'summary'      => 'Tóm tắt bài viết ban đầu',
            'content'      => 'Nội dung an toàn đã được ban giám đốc xuất bản ra công chúng.',
            'author_id'    => $this->author->id,
            'status'       => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        // 2. Tác giả (không có quyền xuất bản) chỉnh sửa bài viết đã xuất bản này
        Sanctum::actingAs($this->author, ['*'], 'admin');
        $resEdit = $this->putJson("/api/admin/posts/{$post->id}", [
            'title'          => 'Nội dung sửa đổi mới chưa duyệt',
            'summary'        => 'Tóm tắt mới chưa qua kiểm duyệt',
            'content'        => 'Đoạn văn nhạy cảm mới thêm vào chưa được ban biên tập thông qua!',
            'revision_notes' => 'Cập nhật thêm thông tin khuyến mãi',
        ]);
        $resEdit->assertStatus(200);

        // Kiểm tra trong cơ sở dữ liệu:
        $fresh = $post->fresh();
        $this->assertTrue($fresh->has_pending_revision);
        $this->assertNotNull($fresh->pending_changes);
        $this->assertEquals('Nội dung sửa đổi mới chưa duyệt', $fresh->pending_changes['title']);
        $this->assertEquals('Đoạn văn nhạy cảm mới thêm vào chưa được ban biên tập thông qua!', $fresh->pending_changes['content']);

        // BÀI VIẾT GỐC TRÊN CSDL VẪN GIỮ NGUYÊN NỘI DUNG AN TOÀN BAN ĐẦU
        $this->assertEquals('Nội dung gốc đã duyệt ban đầu', $fresh->title);
        $this->assertEquals('Nội dung an toàn đã được ban giám đốc xuất bản ra công chúng.', $fresh->content);

        // 3. Khách công khai truy cập bài viết -> Vẫn chỉ đọc được nội dung gốc an toàn
        $resPublic = $this->getJson('/api/posts/bai-viet-goc-da-duyet');
        $resPublic->assertStatus(200);
        $this->assertEquals('Nội dung gốc đã duyệt ban đầu', $resPublic->json('data.post.title'));
        $this->assertEquals('Nội dung an toàn đã được ban giám đốc xuất bản ra công chúng.', $resPublic->json('data.post.content'));

        // 4. Trưởng ban xuất bản vào xem xét và DUYỆT BẢN SỬA ĐỔI (Approve Revision)
        Sanctum::actingAs($this->publisher, ['*'], 'admin');
        $resApprove = $this->postJson("/api/admin/posts/{$post->id}/review", [
            'action' => 'approve_revision',
        ]);
        $resApprove->assertStatus(200);

        // Bây giờ nội dung bài viết chính thức đã được cập nhật
        $afterApproval = $post->fresh();
        $this->assertFalse($afterApproval->has_pending_revision);
        $this->assertNull($afterApproval->pending_changes);
        $this->assertEquals('Nội dung sửa đổi mới chưa duyệt', $afterApproval->title);
        $this->assertEquals('Đoạn văn nhạy cảm mới thêm vào chưa được ban biên tập thông qua!', $afterApproval->content);

        // Khách công khai giờ đây mới đọc được nội dung mới sau khi đã được duyệt
        $resPublicUpdated = $this->getJson('/api/posts/bai-viet-goc-da-duyet');
        $resPublicUpdated->assertStatus(200);
        $this->assertEquals('Nội dung sửa đổi mới chưa duyệt', $resPublicUpdated->json('data.post.title'));
    }

    /**
     * 5. Kiểm tra thùng rác (Soft delete), khôi phục (Restore) và xóa vĩnh viễn (Force Delete)
     */
    public function test_soft_delete_trash_filter_restore_and_force_delete(): void
    {
        $post = Post::create([
            'title'        => 'Bài viết cần kiểm tra xóa và khôi phục',
            'slug'         => 'bai-viet-kiem-tra-xoa',
            'content'      => 'Nội dung kiểm tra xóa...',
            'author_id'    => $this->author->id,
            'status'       => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($this->publisher, ['*'], 'admin');

        // Xóa bài viết -> Vào thùng rác (Soft delete)
        $resDel = $this->deleteJson("/api/admin/posts/{$post->id}");
        $resDel->assertStatus(200);
        $this->assertSoftDeleted('posts', ['id' => $post->id]);

        // Không còn xuất hiện trên API công khai
        $this->getJson('/api/posts/bai-viet-kiem-tra-xoa')->assertStatus(404);

        // Không xuất hiện trong danh sách admin mặc định
        $resIndex = $this->getJson('/api/admin/posts');
        $this->assertCount(0, $resIndex->json('data.data'));

        // Xuất hiện khi lọc thùng rác (status=trashed)
        $resTrash = $this->getJson('/api/admin/posts?status=trashed');
        $this->assertCount(1, $resTrash->json('data.data'));
        $this->assertEquals($post->id, $resTrash->json('data.data.0.id'));

        // Khôi phục bài viết (Restore)
        $resRestore = $this->postJson("/api/admin/posts/{$post->id}/restore");
        $resRestore->assertStatus(200);
        $this->assertNotSoftDeleted('posts', ['id' => $post->id]);

        // Đã xuất hiện lại trên API công khai
        $this->getJson('/api/posts/bai-viet-kiem-tra-xoa')->assertStatus(200);

        // Xóa lại vào thùng rác rồi xóa vĩnh viễn (Force Delete)
        $this->deleteJson("/api/admin/posts/{$post->id}");
        $resForce = $this->deleteJson("/api/admin/posts/{$post->id}/force");
        $resForce->assertStatus(200);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    /**
     * 6. Kiểm tra quản lý chuyên mục và phân loại bài viết
     */
    public function test_category_management_and_post_categorization(): void
    {
        Sanctum::actingAs($this->owner, ['*'], 'admin');

        // Tạo chuyên mục mới
        $resCat = $this->postJson('/api/admin/posts/categories', [
            'name'        => 'Kinh nghiệm lái xe',
            'description' => 'Các mẹo hữu ích cho tài xế mới',
        ]);
        $resCat->assertStatus(200);
        $catId = $resCat->json('data.id');
        $catSlug = $resCat->json('data.slug');
        $this->assertEquals('kinh-nghiem-lai-xe', $catSlug);

        // Tạo bài viết thuộc chuyên mục
        $resPost = $this->postJson('/api/admin/posts', [
            'title'       => 'Cách sạc pin xe điện an toàn tại trạm',
            'content'     => 'Hướng dẫn sử dụng súng sạc VinFast...',
            'category_id' => $catId,
            'action'      => 'publish',
        ]);
        $resPost->assertStatus(200);

        // Lọc bài viết công khai theo slug chuyên mục
        $resFilter = $this->getJson("/api/posts?category={$catSlug}");
        $resFilter->assertStatus(200);
        $this->assertCount(1, $resFilter->json('data.data'));
        $this->assertEquals($catId, $resFilter->json('data.data.0.category_id'));

        // API danh sách chuyên mục công khai trả về đúng số lượng bài
        $resPublicCats = $this->getJson('/api/posts/categories');
        $resPublicCats->assertStatus(200);
        $found = collect($resPublicCats->json('data'))->firstWhere('id', $catId);
        $this->assertNotNull($found);
        $this->assertEquals(1, $found['posts_count']);
    }
}
