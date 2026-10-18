<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Booking;
use App\Models\Car;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\Authorization\PermissionMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminMediaLibraryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $ownerAdmin;
    protected Admin $dispatcherAdmin;
    protected Admin $accountantAdmin;
    protected Admin $restrictedAdmin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerAdmin = Admin::create([
            'ho_ten'        => 'Giám Đốc Hệ Thống',
            'email'         => 'owner_media@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901111001',
            'role'          => PermissionMatrix::ROLE_OWNER,
            'tinh_trang'    => 1,
        ]);

        $this->dispatcherAdmin = Admin::create([
            'ho_ten'        => 'Điều Phối Viên Xe',
            'email'         => 'dispatcher_media@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901111002',
            'role'          => PermissionMatrix::ROLE_DISPATCHER,
            'tinh_trang'    => 1,
        ]);

        $this->accountantAdmin = Admin::create([
            'ho_ten'        => 'Kế Toán Trưởng',
            'email'         => 'accountant_media@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901111003',
            'role'          => PermissionMatrix::ROLE_ACCOUNTANT,
            'tinh_trang'    => 1,
        ]);

        $this->restrictedAdmin = Admin::create([
            'ho_ten'        => 'Nhân Viên Hạn Chế',
            'email'         => 'restricted_media@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0901111004',
            'role'          => 'restricted_staff',
            'tinh_trang'    => 1,
        ]);

        $this->clientUser = User::create([
            'ho_ten'        => 'Nguyễn Văn Khách',
            'email'         => 'khach_media@driverent.vn',
            'password'      => 'Password@123',
            'so_dien_thoai' => '0909999888',
            'dia_chi'       => 'Hà Nội',
            'trang_thai'    => 1,
        ]);
    }

    protected function tearDown(): void
    {
        // Dọn dẹp các tệp test nếu có phát sinh
        $testDirs = [
            public_path('uploads/media/' . date('Y/m')),
            storage_path('app/private/media/' . date('Y/m')),
        ];

        foreach ($testDirs as $dir) {
            if (File::exists($dir)) {
                // Chỉ xóa các file test media_*
                $files = File::glob($dir . '/media_*');
                foreach ($files as $file) {
                    @File::delete($file);
                }
            }
        }

        parent::tearDown();
    }

    /**
     * Test 1: Upload nhiều tệp hợp lệ (ảnh xe, tài liệu)
     */
    public function test_admin_can_upload_multiple_valid_files(): void
    {
        $file1 = UploadedFile::fake()->image('vf8_exterior.jpg', 1200, 800);
        $file2 = UploadedFile::fake()->image('vf8_interior.png', 1000, 700);

        $response = $this->actingAs($this->ownerAdmin, 'admin')
            ->postJson('/api/admin/media', [
                'files'       => [$file1, $file2],
                'collection'  => MediaAsset::COLLECTION_CARS,
                'visibility'  => MediaAsset::VISIBILITY_PUBLIC,
                'description' => 'Bộ ảnh xe VinFast VF8 phục vụ trưng bày',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status'  => 1,
                'message' => 'Tải lên thành công 2 tệp!',
            ]);

        $this->assertDatabaseCount('media_assets', 2);

        $asset = MediaAsset::where('original_name', 'vf8_exterior.jpg')->first();
        $this->assertNotNull($asset);
        $this->assertEquals(MediaAsset::VISIBILITY_PUBLIC, $asset->visibility);
        $this->assertEquals(MediaAsset::COLLECTION_CARS, $asset->collection);
        $this->assertEquals(1200, $asset->width);
        $this->assertEquals(800, $asset->height);
        $this->assertNotNull($asset->url);
        $this->assertStringContainsString('/uploads/media/', $asset->url);
    }

    /**
     * Test 2: Chặn tệp giả mạo MIME hoặc chứa mã độc thực thi
     */
    public function test_system_rejects_fake_mime_and_malicious_script(): void
    {
        // Giả mạo file PHP đổi đuôi thành .png
        $fakePhpFile = UploadedFile::fake()->createWithContent(
            'malicious_script.png',
            "<?php echo 'system compromised'; phpinfo(); ?>"
        );

        $response = $this->actingAs($this->ownerAdmin, 'admin')
            ->postJson('/api/admin/media', [
                'files'      => [$fakePhpFile],
                'collection' => MediaAsset::COLLECTION_MARKETING,
            ]);

        $response->assertStatus(422)
            ->assertJson(['status' => 0]);

        // Đảm bảo không bản ghi nào được lưu vào cơ sở dữ liệu
        $this->assertDatabaseCount('media_assets', 0);
    }

    /**
     * Test 3: Tệp riêng tư (Biên lai / Giấy tờ) được cô lập an toàn, chỉ truy cập qua streaming có quyền
     */
    public function test_private_receipts_isolated_and_protected_by_permissions(): void
    {
        // 1. Tải lên biên lai thanh toán (tự động phân loại là private)
        $receiptFile = UploadedFile::fake()->image('bank_transfer_receipt.jpg', 600, 900);

        $uploadResponse = $this->actingAs($this->ownerAdmin, 'admin')
            ->postJson('/api/admin/media', [
                'files'      => [$receiptFile],
                'collection' => MediaAsset::COLLECTION_RECEIPTS,
            ]);

        $uploadResponse->assertStatus(201);
        $asset = MediaAsset::first();

        // Đảm bảo file được đánh dấu private và không có URL public
        $this->assertEquals(MediaAsset::VISIBILITY_PRIVATE, $asset->visibility);
        $this->assertNull($asset->url);
        $this->assertStringContainsString('/api/admin/media/' . $asset->id . '/stream', $asset->display_url);

        // 2. Khách vãng lai không đăng nhập truy cập stream -> Bị chặn 403
        auth('admin')->forgetUser();
        $this->app['auth']->forgetGuards();

        $guestResponse = $this->get('/api/admin/media/' . $asset->id . '/stream');
        $guestResponse->assertStatus(403);

        // 3. Nhân viên không có quyền xem đơn/thanh toán -> Bị chặn 403
        $unauthorizedStaffResponse = $this->actingAs($this->restrictedAdmin, 'admin')
            ->get('/api/admin/media/' . $asset->id . '/stream');
        $unauthorizedStaffResponse->assertStatus(403);

        // 4. Kế toán (có quyền bookings.view / payments.manage) -> Truy cập thành công
        $accountantResponse = $this->actingAs($this->accountantAdmin, 'admin')
            ->get('/api/admin/media/' . $asset->id . '/stream');
        $accountantResponse->assertStatus(200);
        $accountantResponse->assertHeader('Content-Security-Policy', "default-src 'none'");
        $accountantResponse->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /**
     * Test 4: Không được xóa tài nguyên đang sử dụng mà không cảnh báo; trả HTTP 422 kèm chi tiết xe
     */
    public function test_in_use_media_asset_cannot_be_deleted(): void
    {
        // 1. Tạo asset ảnh xe
        $carImage = UploadedFile::fake()->image('vf9_blue.jpg', 1200, 800);
        $uploadResponse = $this->actingAs($this->ownerAdmin, 'admin')
            ->postJson('/api/admin/media', [
                'files'      => [$carImage],
                'collection' => MediaAsset::COLLECTION_CARS,
            ]);
        $uploadResponse->assertStatus(201);
        $asset = MediaAsset::first();

        // 2. Gán asset này làm ảnh đại diện cho xe VinFast VF9
        $car = Car::create([
            'name'               => 'VinFast VF9 Plus Blue',
            'license_plate'      => '30A-999.88',
            'city'               => 'hn',
            'type'               => 'SUV',
            'price_per_day'      => 2000000,
            'image'              => $asset->url,
            'status'             => 'available',
            'publication_status' => Car::PUB_STATUS_PUBLISHED,
        ]);

        // 3. Kiểm tra API tra cứu tham chiếu (references)
        $refResponse = $this->actingAs($this->ownerAdmin, 'admin')
            ->getJson("/api/admin/media/{$asset->id}/references");
        $refResponse->assertStatus(200)
            ->assertJsonPath('data.in_use', true)
            ->assertJsonPath('data.references.total_count', 1);

        // 4. Cố tình xóa asset đang sử dụng -> Bị chặn 422 với mã ASSET_IN_USE
        $deleteResponse = $this->actingAs($this->ownerAdmin, 'admin')
            ->deleteJson("/api/admin/media/{$asset->id}");

        $deleteResponse->assertStatus(422)
            ->assertJson([
                'status'     => 0,
                'error_code' => 'ASSET_IN_USE',
            ]);

        // Đảm bảo asset và xe vẫn tồn tại an toàn trong hệ thống
        $this->assertDatabaseHas('media_assets', ['id' => $asset->id]);
        $this->assertDatabaseHas('cars', ['id' => $car->id]);
    }

    /**
     * Test 5: Tài nguyên không còn tham chiếu được xóa an toàn khỏi DB và đĩa
     */
    public function test_unused_media_asset_can_be_deleted_safely(): void
    {
        $file = UploadedFile::fake()->image('temporary_unused.jpg', 400, 300);
        $this->actingAs($this->ownerAdmin, 'admin')
            ->postJson('/api/admin/media', ['files' => [$file]]);

        $asset = MediaAsset::first();
        $this->assertNotNull($asset);

        $filePath = $asset->getAbsolutePath();
        $this->assertTrue(File::exists($filePath));

        // Xóa asset chưa từng được gán vào xe hay đơn hàng
        $response = $this->actingAs($this->ownerAdmin, 'admin')
            ->deleteJson("/api/admin/media/{$asset->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 1,
                'message' => 'Đã xóa tài nguyên thành công.',
            ]);

        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        $this->assertFalse(File::exists($filePath));
    }

    /**
     * Test 6: Tìm kiếm, lọc danh mục, chế độ hiển thị và phân trang
     */
    public function test_media_filtering_search_and_pagination(): void
    {
        // Tạo 3 asset với metadata khác nhau
        $file1 = UploadedFile::fake()->image('vinfast_vf7_banner.jpg', 800, 600);
        $file2 = UploadedFile::fake()->image('hyundai_ioniq5.jpg', 800, 600);
        $file3 = UploadedFile::fake()->image('honda_crv.jpg', 800, 600);

        $this->actingAs($this->ownerAdmin, 'admin')->postJson('/api/admin/media', [
            'files'      => [$file1],
            'collection' => MediaAsset::COLLECTION_MARKETING,
            'alt_text'   => 'Banner VinFast VF7 Đỏ',
        ]);

        $this->actingAs($this->ownerAdmin, 'admin')->postJson('/api/admin/media', [
            'files'      => [$file2],
            'collection' => MediaAsset::COLLECTION_CARS,
            'alt_text'   => 'Xe Hyundai Điện',
        ]);

        $this->actingAs($this->ownerAdmin, 'admin')->postJson('/api/admin/media', [
            'files'      => [$file3],
            'collection' => MediaAsset::COLLECTION_CARS,
            'alt_text'   => 'Xe Honda CRV',
        ]);

        // 1. Tìm kiếm theo từ khóa 'VinFast'
        $searchResponse = $this->actingAs($this->dispatcherAdmin, 'admin')
            ->getJson('/api/admin/media?search=VinFast');

        $searchResponse->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.original_name', 'vinfast_vf7_banner.jpg');

        // 2. Lọc theo collection = 'cars'
        $filterResponse = $this->actingAs($this->dispatcherAdmin, 'admin')
            ->getJson('/api/admin/media?collection=cars');

        $filterResponse->assertStatus(200)
            ->assertJsonPath('meta.total', 2);

        // 3. Phân trang per_page = 1
        $pageResponse = $this->actingAs($this->dispatcherAdmin, 'admin')
            ->getJson('/api/admin/media?per_page=1');

        $pageResponse->assertStatus(200)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 3);
    }
}
