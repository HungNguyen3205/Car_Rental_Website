<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostImportBatch;
use App\Models\PostSchedule;
use App\Models\SystemSetting;
use App\Services\Marketing\FacebookPublishService;
use App\Services\Marketing\GoogleDriveService;
use App\Services\Marketing\PostExcelImportService;
use App\Services\Marketing\PostSchedulerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MarketingPostImportAndSchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected PostCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        // Tạo Admin có quyền quản trị
        $this->admin = Admin::create([
            'username' => 'marketing_admin',
            'email' => 'marketing@test.com',
            'ho_ten' => 'Marketing Manager',
            'password' => bcrypt('Password123!'),
            'role' => 'admin',
            'permissions' => ['posts.view', 'posts.create', 'posts.edit', 'posts.publish', 'posts.delete', 'settings.edit', 'admins.manage'],
            'is_active' => true,
        ]);

        $this->category = PostCategory::create([
            'name' => 'Tin Tức VinFast',
            'slug' => 'tin-tuc-vinfast',
            'is_active' => true,
        ]);
    }

    /**
     * Tạo file Excel tạm để test
     */
    protected function createTestExcelFile(array $rows, array $headers = ['content', 'tên ảnh', 'ngày đăng', 'giờ đăng']): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Ghi tiêu đề
        foreach ($headers as $colIdx => $h) {
            $sheet->setCellValue([$colIdx + 1, 1], $h);
        }

        // Ghi dữ liệu
        foreach ($rows as $rowIdx => $row) {
            foreach ($row as $colIdx => $val) {
                $sheet->setCellValue([$colIdx + 1, $rowIdx + 2], $val);
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'test_excel_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Test 1: Đề xuất tiêu đề Website bằng quy tắc xác định từ dòng đầu (KHÔNG DÙNG AI)
     */
    public function test_suggest_title_deterministic_rule_without_ai()
    {
        // Câu có dấu chấm kết thúc
        $content1 = "Trải nghiệm dòng xe điện VinFast VF9 đẳng cấp thương gia. Không gian 7 chỗ ngồi cực kỳ rộng rãi và êm ái.";
        $this->assertEquals("Trải nghiệm dòng xe điện VinFast VF9 đẳng cấp thương gia", PostExcelImportService::suggestTitle($content1));

        // Dòng đầu có bullet point
        $content2 = "- Thuê xe điện VinFast VF8 giá tốt tại Đà Nẵng và Hội An. Đầy đủ tiện nghi du lịch gia đình.";
        $this->assertEquals("Thuê xe điện VinFast VF8 giá tốt tại Đà Nẵng và Hội An", PostExcelImportService::suggestTitle($content2));

        // Dòng đầu quá dài, cắt theo từ nguyên vẹn
        $content3 = "Khám phá dịch vụ thuê xe điện tự lái VinFast cao cấp tại thành phố Hồ Chí Minh cùng những ưu đãi vô cùng hấp dẫn dành cho quý khách hàng mới trong mùa du lịch 2026";
        $title3 = PostExcelImportService::suggestTitle($content3);
        $this->assertLessThanOrEqual(80, mb_strlen($title3, 'UTF-8'));
        $this->assertStringStartsWith("Khám phá dịch vụ thuê xe điện tự lái", $title3);
    }

    /**
     * Test 2: Đọc file Excel 4 cột chuẩn, kiểm tra ngày giờ, công thức và dòng trống
     */
    public function test_excel_parser_validates_dates_formulas_and_empty_rows()
    {
        $service = new PostExcelImportService();

        $futureDate = Carbon::now('Asia/Ho_Chi_Minh')->addDays(5)->format('d/m/Y');
        $pastDate = Carbon::now('Asia/Ho_Chi_Minh')->subDays(2)->format('d/m/Y');

        $rows = [
            // Dòng 1: Hợp lệ
            ['Giới thiệu VinFast VF9 sang trọng.', 'vf9.jpg', $futureDate, '09:00'],
            // Dòng 2: Ngày quá khứ -> Phải báo lỗi
            ['Nội dung xe cũ.', 'vf8.png', $pastDate, '10:00'],
            // Dòng 3: Chứa công thức Excel -> Phải phát hiện và báo lỗi
            ['=SUM(A1:A5)', 'vf5.jpg', $futureDate, '11:00'],
            // Dòng 4: Hoàn toàn trống -> Phải bỏ qua
            [null, null, null, null],
            // Dòng 5: Thiếu tên ảnh -> Lỗi
            ['Bài viết thiếu ảnh.', '', $futureDate, '12:00'],
        ];

        $excelPath = $this->createTestExcelFile($rows);

        try {
            $result = $service->parseSpreadsheet($excelPath);

            $this->assertEquals(4, $result['total_rows_read']); // 5 dòng trừ 1 dòng trống
            $this->assertEquals(1, $result['skipped_empty_rows']);
            $this->assertEquals(1, $result['valid_rows_count']);
            $this->assertEquals(3, $result['error_rows_count']);

            // Kiểm tra lỗi dòng quá khứ
            $this->assertStringContainsString('quá khứ', $result['rows'][1]['errors'][0]);

            // Kiểm tra lỗi công thức
            $this->assertStringContainsString('công thức', $result['rows'][2]['errors'][0]);

            // Kiểm tra lỗi thiếu ảnh
            $this->assertStringContainsString('Thiếu tên file ảnh', $result['rows'][3]['errors'][0]);
        } finally {
            @unlink($excelPath);
        }
    }

    /**
     * Test 3: Ghép ảnh Google Drive theo tên file chính xác và phát hiện trùng lặp
     */
    public function test_google_drive_match_image_exact_and_duplicate_detection()
    {
        $driveService = new GoogleDriveService();

        $mockIndexedFiles = [
            'vf9.jpg' => [
                ['id' => 'drive_id_1', 'name' => 'vf9.jpg', 'mime_type' => 'image/jpeg', 'size' => 102400]
            ],
            'vf8-duplicate.png' => [
                ['id' => 'drive_id_2a', 'name' => 'vf8-duplicate.png', 'mime_type' => 'image/png', 'size' => 204800],
                ['id' => 'drive_id_2b', 'name' => 'vf8-duplicate.png', 'mime_type' => 'image/png', 'size' => 204800],
            ],
        ];

        // Khớp chính xác
        $res1 = $driveService->matchImageName('vf9.jpg', $mockIndexedFiles);
        $this->assertEquals('matched', $res1['status']);
        $this->assertEquals('drive_id_1', $res1['file']['id']);

        // Trùng lặp 2 file cùng tên trên Drive
        $res2 = $driveService->matchImageName('vf8-duplicate.png', $mockIndexedFiles);
        $this->assertEquals('duplicate', $res2['status']);
        $this->assertCount(2, $res2['files']);

        // Không tìm thấy
        $res3 = $driveService->matchImageName('not-exist.jpg', $mockIndexedFiles);
        $this->assertEquals('not_found', $res3['status']);
    }

    /**
     * Test 4: Chống import trùng bằng Fingerprint nghiệp vụ
     */
    public function test_fingerprint_generation_and_deduplication()
    {
        $content = "  Giới thiệu xe điện   VinFast VF9 cao cấp  \nTrải nghiệm đỉnh cao. ";
        $image = "VF9-Image.JPG";
        $scheduledAtIso = "2026-10-25T08:30:00+07:00";
        $channel = "both";

        $fp1 = PostSchedule::generateFingerprint($content, $image, $scheduledAtIso, $channel);
        $fp2 = PostSchedule::generateFingerprint("Giới thiệu xe điện VinFast VF9 cao cấp Trải nghiệm đỉnh cao.", "vf9-image.jpg", $scheduledAtIso, $channel);

        // Khoảng trắng thừa và hoa/thường tên ảnh được chuẩn hóa đồng nhất
        $this->assertEquals($fp1, $fp2);
        $this->assertEquals(64, strlen($fp1));
    }

    /**
     * Test 5: Tải file mẫu Excel 4 cột từ Controller
     */
    public function test_admin_can_download_excel_template()
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get('api/admin/post-schedules/template');

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('content-type'));
    }

    /**
     * Test 6: Scheduler xử lý xuất bản độc lập giữa Website và Facebook, xử lý timeout outcome_unknown
     */
    public function test_scheduler_channel_isolation_and_facebook_timeout_outcome_unknown()
    {
        // Cấu hình Facebook Page
        SystemSetting::set('FACEBOOK_PAGE_ID', '1000100010001', 'marketing', false);
        SystemSetting::set('FACEBOOK_PAGE_ACCESS_TOKEN', 'EAAG_valid_test_token_string', 'marketing', false);

        // 1. Tạo snapshot ảnh giả lập trong storage
        $snapshotPath = 'posts/snapshots/1/snap_test_123.jpg';
        Storage::disk('public')->put($snapshotPath, 'fake_image_content');

        // Tạo bài viết lên lịch đăng cho cả hai kênh
        $schedule = PostSchedule::create([
            'batch_id' => null,
            'row_index' => 2,
            'fingerprint' => 'test_fingerprint_channel_isolation_1',
            'content' => 'Nội dung bài viết thử nghiệm xuất bản đa kênh.',
            'title_suggested' => 'Tiêu đề đề xuất bài viết',
            'title' => 'Tiêu đề duyệt bài viết',
            'category_id' => $this->category->id,
            'drive_image_name' => 'vf9.jpg',
            'image_snapshot_path' => $snapshotPath,
            'channel' => 'both',
            'scheduled_at' => Carbon::now('UTC')->subMinute(), // Đã đến lịch
            'status_website' => 'scheduled',
            'status_facebook' => 'scheduled',
            'created_by_admin_id' => $this->admin->id,
        ]);

        // Mock Facebook Graph API bị Timeout ConnectionException
        Http::fake([
            'graph.facebook.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out');
            }
        ]);

        $scheduler = app(PostSchedulerService::class);
        $result = $scheduler->publishSingleSchedule($schedule->id);

        $schedule->refresh();

        // Kênh Website: Phải thành công (Published) và đã tạo Post
        $this->assertEquals('published', $schedule->status_website);
        $this->assertNotNull($schedule->post_id);
        $createdPost = Post::find($schedule->post_id);
        $this->assertNotNull($createdPost);
        $this->assertEquals('published', $createdPost->status);
        $this->assertEquals('Tiêu đề duyệt bài viết', $createdPost->title);

        // Kênh Facebook: Do timeout -> Phải chuyển sang 'outcome_unknown' để đối soát, KHÔNG retry mù
        $this->assertEquals('outcome_unknown', $schedule->status_facebook);
        $this->assertStringContainsString('timeout', strtolower($schedule->facebook_error));
    }

    /**
     * Test 7: Lịch quá hạn lâu (Server Downtime) không tự động xả dồn đăng hàng loạt
     */
    public function test_scheduler_marks_severely_overdue_posts_as_failed_instead_of_mass_publishing()
    {
        $snapshotPath = 'posts/snapshots/1/snap_overdue.jpg';
        Storage::disk('public')->put($snapshotPath, 'fake_image_content');

        // Bài viết bị trễ 24 tiếng do máy chủ tắt
        $schedule = PostSchedule::create([
            'batch_id' => null,
            'row_index' => 3,
            'fingerprint' => 'test_fingerprint_overdue_1',
            'content' => 'Nội dung bài viết quá hạn 24 tiếng.',
            'title_suggested' => 'Tiêu đề quá hạn',
            'title' => 'Tiêu đề quá hạn',
            'category_id' => $this->category->id,
            'drive_image_name' => 'vf8.png',
            'image_snapshot_path' => $snapshotPath,
            'channel' => 'website',
            'scheduled_at' => Carbon::now('UTC')->subHours(24),
            'status_website' => 'scheduled',
            'status_facebook' => 'not_applicable',
            'created_by_admin_id' => $this->admin->id,
        ]);

        $scheduler = app(PostSchedulerService::class);
        $result = $scheduler->publishSingleSchedule($schedule->id);

        $schedule->refresh();

        $this->assertEquals('overdue', $result['status']);
        $this->assertEquals('failed', $schedule->status_website);
        $this->assertStringContainsString('quá hạn', $schedule->website_error);
        $this->assertNull($schedule->post_id); // Không tự tiện tạo bài đăng khi đã trễ quá lâu
    }
}
