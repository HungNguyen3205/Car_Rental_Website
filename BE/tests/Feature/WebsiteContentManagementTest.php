<?php

namespace Tests\Feature;

use App\Http\Controllers\Public\ContentController as PublicContentController;
use App\Models\Admin;
use App\Models\WebsiteContent;
use App\Models\WebsiteContentVersion;
use App\Services\Authorization\PermissionMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebsiteContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $owner;
    protected Admin $viewer;
    protected Admin $editor;
    protected Admin $publisher;
    protected Admin $unauthorized;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Owner
        $this->owner = Admin::create([
            'ho_ten'        => 'Chủ Hệ Thống',
            'email'         => 'owner_cms@test.com',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901111111',
            'role'          => PermissionMatrix::ROLE_OWNER,
            'permissions'   => PermissionMatrix::getDefaultPermissionsForRole(PermissionMatrix::ROLE_OWNER),
            'tinh_trang'    => 1,
        ]);

        // 2. Viewer (chỉ xem nháp/preview)
        $this->viewer = Admin::create([
            'ho_ten'        => 'Nhân Viên Xem Nháp',
            'email'         => 'viewer_cms@test.com',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901111112',
            'role'          => PermissionMatrix::ROLE_DISPATCHER,
            'permissions'   => [PermissionMatrix::PERM_CONTENT_VIEW],
            'tinh_trang'    => 1,
        ]);

        // 3. Editor (soạn thảo & lưu nháp)
        $this->editor = Admin::create([
            'ho_ten'        => 'Biên Tập Viên CMS',
            'email'         => 'editor_cms@test.com',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901111113',
            'role'          => PermissionMatrix::ROLE_DISPATCHER,
            'permissions'   => [
                PermissionMatrix::PERM_CONTENT_VIEW,
                PermissionMatrix::PERM_CONTENT_EDIT,
            ],
            'tinh_trang'    => 1,
        ]);

        // 4. Publisher (toàn quyền xuất bản & khôi phục)
        $this->publisher = Admin::create([
            'ho_ten'        => 'Trưởng Nhóm Xuất Bản CMS',
            'email'         => 'publisher_cms@test.com',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901111114',
            'role'          => PermissionMatrix::ROLE_DISPATCHER,
            'permissions'   => [
                PermissionMatrix::PERM_CONTENT_VIEW,
                PermissionMatrix::PERM_CONTENT_EDIT,
                PermissionMatrix::PERM_CONTENT_PUBLISH,
            ],
            'tinh_trang'    => 1,
        ]);

        // 5. Unauthorized
        $this->unauthorized = Admin::create([
            'ho_ten'        => 'Nhân Viên Không Quyền CMS',
            'email'         => 'unauth_cms@test.com',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901111115',
            'role'          => PermissionMatrix::ROLE_DISPATCHER,
            'permissions'   => [PermissionMatrix::PERM_BOOKINGS_VIEW],
            'tinh_trang'    => 1,
        ]);

        // Xóa cache trước mỗi test
        Cache::flush();
    }

    /**
     * Test 1: Khởi tạo ban đầu luôn có đủ 8 sections từ migration với phiên bản v1
     */
    public function test_baseline_migration_contains_all_eight_sections_with_version_one(): void
    {
        $this->assertDatabaseCount('website_contents', 8);
        $this->assertDatabaseHas('website_contents', [
            'section_key' => 'home_hero',
            'version'     => 1,
            'status'      => 'published',
        ]);
        $this->assertDatabaseHas('website_contents', [
            'section_key' => 'home_rental_steps',
            'version'     => 1,
        ]);
        $this->assertDatabaseHas('website_contents', [
            'section_key' => 'home_bento',
            'version'     => 1,
        ]);
        $this->assertDatabaseHas('website_contents', [
            'section_key' => 'home_faq',
            'version'     => 1,
        ]);
        $this->assertDatabaseHas('website_contents', [
            'section_key' => 'about_page',
            'version'     => 1,
        ]);
        $this->assertDatabaseHas('website_contents', [
            'section_key' => 'contact_page',
            'version'     => 1,
        ]);
        $this->assertDatabaseHas('website_contents', [
            'section_key' => 'process_page',
            'version'     => 1,
        ]);
        $this->assertDatabaseHas('website_contents', [
            'section_key' => 'policies_page',
            'version'     => 1,
        ]);
    }

    /**
     * Test 2: Public endpoint cách ly hoàn toàn bản nháp và chỉ trả về published_content
     */
    public function test_public_endpoint_returns_only_published_content_and_isolates_draft(): void
    {
        $hero = WebsiteContent::where('section_key', 'home_hero')->first();
        $originalTitle = $hero->published_content['title'];

        // Cập nhật nháp trực tiếp trong DB
        $hero->draft_content = array_merge($hero->draft_content, [
            'title' => 'TIÊU ĐỀ BẢN NHÁP BÍ MẬT ĐANG SOẠN THẢO',
        ]);
        $hero->has_unpublished_changes = true;
        $hero->save();

        // 1. Gọi public all
        $res = $this->getJson('/api/content');
        $res->assertStatus(200);
        $this->assertEquals($originalTitle, $res->json('data.home_hero.content.title'));
        $this->assertStringNotContainsString('BẢN NHÁP BÍ MẬT', $res->json('data.home_hero.content.title'));

        // 2. Gọi public single
        $resSingle = $this->getJson('/api/content/home_hero');
        $resSingle->assertStatus(200);
        $this->assertEquals($originalTitle, $resSingle->json('data.content.title'));
    }

    /**
     * Test 3: Endpoint Preview riêng tư chỉ dành cho Admin đã xác thực có quyền
     */
    public function test_private_preview_requires_admin_authentication_and_permission(): void
    {
        $hero = WebsiteContent::where('section_key', 'home_hero')->first();
        $hero->draft_content = array_merge($hero->draft_content, [
            'title' => 'BẢN NHÁP HERO SẮP RA MẮT',
        ]);
        $hero->has_unpublished_changes = true;
        $hero->save();

        // 1. Khách vãng lai chưa đăng nhập -> 401
        $resGuest = $this->getJson('/api/content/home_hero/preview');
        $resGuest->assertStatus(401);

        // 2. Admin không có quyền content -> 403
        Sanctum::actingAs($this->unauthorized, ['*'], 'admin');
        $resUnauth = $this->getJson('/api/content/home_hero/preview');
        $resUnauth->assertStatus(403);

        // 3. Admin có quyền viewer -> 200 và thấy được draft content
        Sanctum::actingAs($this->viewer, ['*'], 'admin');
        $resViewer = $this->getJson('/api/content/home_hero/preview');
        $resViewer->assertStatus(200);
        $this->assertTrue($resViewer->json('data.is_preview'));
        $this->assertEquals('BẢN NHÁP HERO SẮP RA MẮT', $resViewer->json('data.content.title'));
    }

    /**
     * Test 4: Kiểm tra phân quyền thực thi (Save Draft, Publish, Restore)
     */
    public function test_permission_enforcement_for_draft_publish_and_restore(): void
    {
        $payload = [
            'content' => [
                'eyebrow'            => 'DRIVERENT MỚI',
                'title'              => 'XE SANG PHONG CÁCH MỚI',
                'description'        => 'Mô tả mới tinh.',
                'primary_cta_text'   => 'Tìm xe',
                'primary_cta_action' => 'scroll_to_search',
                'secondary_cta_text' => 'Đội xe',
                'secondary_cta_url'  => '/fleet',
                'visual_note'        => 'Ghi chú',
            ],
        ];

        // 1. Viewer cố tình lưu nháp -> 403
        Sanctum::actingAs($this->viewer, ['*'], 'admin');
        $res = $this->postJson('/api/admin/content/home_hero/draft', $payload);
        $res->assertStatus(403);

        // 2. Editor lưu nháp -> 200
        Sanctum::actingAs($this->editor, ['*'], 'admin');
        $resEdit = $this->postJson('/api/admin/content/home_hero/draft', $payload);
        $resEdit->assertStatus(200);
        $this->assertDatabaseHas('website_contents', [
            'section_key'             => 'home_hero',
            'has_unpublished_changes' => true,
        ]);

        // 3. Editor cố tình publish -> 403
        $resPubFail = $this->postJson('/api/admin/content/home_hero/publish');
        $resPubFail->assertStatus(403);

        // 4. Publisher thực hiện publish -> 200
        Sanctum::actingAs($this->publisher, ['*'], 'admin');
        $resPubSuccess = $this->postJson('/api/admin/content/home_hero/publish');
        $resPubSuccess->assertStatus(200);
        $this->assertEquals(2, $resPubSuccess->json('data.version'));
        $this->assertFalse($resPubSuccess->json('data.has_unpublished_changes'));
    }

    /**
     * Test 5: Validation bảo mật chống XSS và kiểm tra URL CTA hợp lệ
     */
    public function test_cta_url_validation_and_malicious_script_rejection(): void
    {
        Sanctum::actingAs($this->editor, ['*'], 'admin');

        // Thử chèn javascript: URL vào secondary_cta_url
        $badCtaPayload = [
            'content' => [
                'eyebrow'            => 'DRIVERENT',
                'title'              => 'XE SANG',
                'description'        => 'Mô tả',
                'primary_cta_text'   => 'Tìm xe',
                'secondary_cta_text' => 'Xem xe',
                'secondary_cta_url'  => 'javascript:alert(document.cookie)',
            ],
        ];
        $res1 = $this->postJson('/api/admin/content/home_hero/draft', $badCtaPayload);
        $res1->assertStatus(422);

        // Thử chèn thẻ <script> vào trường title
        $xssPayload = [
            'content' => [
                'eyebrow'            => 'DRIVERENT',
                'title'              => 'Tiêu đề <script>alert(1)</script>',
                'description'        => 'Mô tả',
                'primary_cta_text'   => 'Tìm xe',
                'secondary_cta_text' => 'Xem xe',
                'secondary_cta_url'  => '/fleet',
            ],
        ];
        $res2 = $this->postJson('/api/admin/content/home_hero/draft', $xssPayload);
        $res2->assertStatus(422);

        // Thử URL CTA hợp lệ (đường dẫn nội bộ '/fleet' hoặc URL 'https://example.com')
        $validPayload = [
            'content' => [
                'eyebrow'            => 'DRIVERENT AN TOÀN',
                'title'              => 'TIÊU ĐỀ CHUẨN',
                'description'        => 'Mô tả hợp lệ',
                'primary_cta_text'   => 'Tìm xe',
                'primary_cta_action' => 'scroll_to_search',
                'secondary_cta_text' => 'Xem xe',
                'secondary_cta_url'  => '/fleet?category=suv',
                'visual_note'        => 'Ghi chú an toàn',
            ],
        ];
        $res3 = $this->postJson('/api/admin/content/home_hero/draft', $validPayload);
        $res3->assertStatus(200);
    }

    /**
     * Test 6: Ngăn chặn editor can thiệp công thức giá hoặc trạng thái booking trong CMS
     */
    public function test_forbids_tampering_pricing_formula_or_booking_status(): void
    {
        Sanctum::actingAs($this->editor, ['*'], 'admin');

        $tamperPayload = [
            'content' => [
                'eyebrow'            => 'DRIVERENT',
                'title'              => 'XE SANG',
                'description'        => 'Mô tả',
                'primary_cta_text'   => 'Tìm xe',
                'secondary_cta_text' => 'Đội xe',
                'secondary_cta_url'  => '/fleet',
                'pricing_formula'    => 'price * 0.5', // Cố tình tiêm công thức giảm giá
            ],
        ];

        $res = $this->postJson('/api/admin/content/home_hero/draft', $tamperPayload);
        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['pricing_formula']);
    }

    /**
     * Test 7: Làm mới Cache khi xuất bản nội dung mới
     */
    public function test_cache_invalidation_upon_publishing(): void
    {
        // 1. Lần đầu truy cập công khai -> ghi nhớ vào Cache
        $firstGet = $this->getJson('/api/content/home_hero');
        $firstGet->assertStatus(200);
        $initialTitle = $firstGet->json('data.content.title');

        // Xác nhận Cache đã được lưu
        $cachedValue = Cache::get(PublicContentController::PUBLIC_CACHE_KEY_PREFIX . 'home_hero');
        $this->assertNotNull($cachedValue);
        $this->assertEquals($initialTitle, $cachedValue['content']['title']);

        // 2. Xuất bản nội dung mới qua Publisher
        Sanctum::actingAs($this->publisher, ['*'], 'admin');
        $newTitle = 'XE ĐIỆN VINFAST TIÊN PHONG 2026';
        $publishRes = $this->postJson('/api/admin/content/home_hero/publish', [
            'content' => [
                'eyebrow'            => 'DRIVERENT / XU HƯỚNG MỚI',
                'title'              => $newTitle,
                'description'        => 'Mô tả mới sau khi xuất bản.',
                'primary_cta_text'   => 'Khám phá ngay',
                'primary_cta_action' => 'scroll_to_search',
                'secondary_cta_text' => 'Xem bộ sưu tập',
                'secondary_cta_url'  => '/fleet',
                'visual_note'        => 'Hình ảnh xe điện mới.',
            ],
        ]);
        $publishRes->assertStatus(200);

        // 3. Cache cũ phải bị xóa ngay lập tức
        $this->assertNull(Cache::get(PublicContentController::PUBLIC_CACHE_KEY_PREFIX . 'home_hero'));

        // 4. Lần lấy tiếp theo qua public endpoint phải trả về nội dung mới xuất bản
        $secondGet = $this->getJson('/api/content/home_hero');
        $secondGet->assertStatus(200);
        $this->assertEquals($newTitle, $secondGet->json('data.content.title'));
    }

    /**
     * Test 8: Lịch sử phiên bản và khôi phục (Version Rollback)
     */
    public function test_version_history_and_restore_functionality(): void
    {
        Sanctum::actingAs($this->owner, ['*'], 'admin');

        $hero = WebsiteContent::where('section_key', 'home_hero')->first();
        $this->assertEquals(1, $hero->version);

        // 1. Xuất bản phiên bản v2
        $this->postJson('/api/admin/content/home_hero/publish', [
            'content' => array_merge($hero->published_content, [
                'title' => 'PHIÊN BẢN V2 HERO',
            ]),
        ])->assertStatus(200);

        // 2. Xuất bản phiên bản v3
        $this->postJson('/api/admin/content/home_hero/publish', [
            'content' => array_merge($hero->published_content, [
                'title' => 'PHIÊN BẢN V3 HERO MỚI NHẤT',
            ]),
        ])->assertStatus(200);

        // Kiểm tra danh sách phiên bản
        $resVersions = $this->getJson('/api/admin/content/home_hero/versions');
        $resVersions->assertStatus(200);
        $this->assertCount(3, $resVersions->json('data.versions'));

        // Lấy version 1 (snapshot ban đầu)
        $v1Record = WebsiteContentVersion::where('content_id', $hero->id)->where('version', 1)->first();
        $this->assertNotNull($v1Record);

        // 3. Khôi phục về v1
        $restoreRes = $this->postJson("/api/admin/content/home_hero/restore/{$v1Record->id}");
        $restoreRes->assertStatus(200);
        $this->assertEquals(4, $restoreRes->json('data.version')); // tạo bản v4 với nội dung của v1

        // 4. Kiểm tra public endpoint thấy nội dung đã khôi phục
        $publicRes = $this->getJson('/api/content/home_hero');
        $publicRes->assertStatus(200);
        $this->assertEquals($v1Record->content['title'], $publicRes->json('data.content.title'));
    }
}
