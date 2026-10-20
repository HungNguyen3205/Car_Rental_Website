<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PostCategory;
use App\Models\PostImportBatch;
use App\Models\PostSchedule;
use App\Models\SystemSetting;
use App\Services\Marketing\FacebookPublishService;
use App\Services\Marketing\GoogleDriveService;
use App\Services\Marketing\PostExcelImportService;
use App\Services\Marketing\PostSchedulerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;

class PostImportScheduleController extends Controller
{
    protected GoogleDriveService $driveService;
    protected PostExcelImportService $excelService;
    protected FacebookPublishService $facebookService;
    protected PostSchedulerService $schedulerService;

    public function __construct(
        GoogleDriveService $driveService,
        PostExcelImportService $excelService,
        FacebookPublishService $facebookService,
        PostSchedulerService $schedulerService
    ) {
        $this->driveService = $driveService;
        $this->excelService = $excelService;
        $this->facebookService = $facebookService;
        $this->schedulerService = $schedulerService;
    }

    protected function currentAdminId(): ?int
    {
        return Auth::guard('admin')->id();
    }

    /**
     * Tải file Excel mẫu 4 cột chuẩn nghiệp vụ
     */
    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Lịch Đăng Bài');

        // Tiêu đề 4 cột
        $sheet->setCellValue('A1', 'content');
        $sheet->setCellValue('B1', 'tên ảnh');
        $sheet->setCellValue('C1', 'ngày đăng');
        $sheet->setCellValue('D1', 'giờ đăng');

        // Dòng ví dụ 1
        $sheet->setCellValue('A2', 'Trải nghiệm đỉnh cao cùng VinFast VF9 Plus - Mẫu SUV thuần điện 7 chỗ cỡ lớn đẳng cấp thương gia. Không gian rộng rãi, trang bị ghế cơ trưởng massage, nội thất da cao cấp.');
        $sheet->setCellValue('B2', 'vf9-danang.jpg');
        $sheet->setCellValue('C2', Carbon::now('Asia/Ho_Chi_Minh')->addDays(2)->format('d/m/Y'));
        $sheet->setCellValue('D2', '08:30');

        // Dòng ví dụ 2
        $sheet->setCellValue('A3', 'Du lịch cuối tuần thảnh thơi cùng VinFast VF8 Eco. Cảm giác lái đầm chắc, tăng tốc ấn tượng và chi phí thuê xe siêu tiết kiệm chỉ từ 900.000đ/ngày.');
        $sheet->setCellValue('B3', 'vf8-city.png');
        $sheet->setCellValue('C3', Carbon::now('Asia/Ho_Chi_Minh')->addDays(3)->format('d/m/Y'));
        $sheet->setCellValue('D3', '14:00');

        // Định dạng cột
        $sheet->getColumnDimension('A')->setWidth(65);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(15);

        // Header style
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $fileName = 'mau_lich_dang_bai_4_cot.xlsx';
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /**
     * Lấy cấu hình kết nối Google Drive
     */
    public function getDriveConfig()
    {
        $folderId = SystemSetting::get('GOOGLE_DRIVE_FOLDER_ID');
        $email = $this->driveService->getServiceAccountEmail();
        $lastSynced = SystemSetting::get('GOOGLE_DRIVE_LAST_SYNCED_AT');

        return response()->json([
            'status' => 1,
            'data' => [
                'folder_id' => $folderId,
                'service_account_email' => $email,
                'has_service_account' => !empty($email),
                'last_synced_at' => $lastSynced,
            ],
        ]);
    }

    /**
     * Cập nhật Folder ID hoặc Service Account JSON
     */
    public function updateDriveConfig(Request $request)
    {
        $data = $request->validate([
            'folder_id' => ['nullable', 'string', 'max:255'],
            'service_account_json' => ['nullable', 'string'],
        ]);

        $adminId = $this->currentAdminId();

        if (isset($data['folder_id'])) {
            $cleaned = GoogleDriveService::extractFolderId($data['folder_id']);
            SystemSetting::set('GOOGLE_DRIVE_FOLDER_ID', $cleaned, 'marketing', false, $adminId);
        }

        if (!empty($data['service_account_json'])) {
            $decoded = json_decode($data['service_account_json'], true);
            if (!is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Nội dung Service Account JSON không hợp lệ. Phải chứa client_email và private_key.',
                ], 422);
            }
            SystemSetting::set('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON', $data['service_account_json'], 'marketing', false, $adminId);
        }

        AuditLog::create([
            'admin_id' => $adminId,
            'action' => 'UPDATE_DRIVE_CONFIG',
            'target_type' => 'SystemSetting',
            'target_id' => 0,
            'details' => 'Cập nhật cấu hình kết nối Google Drive',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Đã lưu cấu hình Google Drive thành công.',
        ]);
    }

    /**
     * Kiểm tra quyền đọc thư mục Google Drive (Read-only, không tạo/xóa dữ liệu)
     */
    public function testDriveConnection(Request $request)
    {
        $folderId = $request->input('folder_id');
        $res = $this->driveService->verifyFolderAccess($folderId);

        return response()->json([
            'status' => $res['success'] ? 1 : 0,
            'data' => $res,
            'message' => $res['message'],
        ]);
    }

    /**
     * Lấy cấu hình Facebook Page
     */
    public function getFacebookConfig()
    {
        $status = $this->facebookService->getConnectionStatus();
        return response()->json([
            'status' => 1,
            'data' => $status,
        ]);
    }

    /**
     * Cập nhật thông tin Facebook Page
     */
    public function updateFacebookConfig(Request $request)
    {
        $data = $request->validate([
            'page_id' => ['required', 'string', 'max:100'],
            'page_access_token' => ['nullable', 'string'],
            'page_name' => ['nullable', 'string', 'max:255'],
        ]);

        $adminId = $this->currentAdminId();

        SystemSetting::set('FACEBOOK_PAGE_ID', trim($data['page_id']), 'marketing', false, $adminId);

        if (!empty($data['page_access_token'])) {
            SystemSetting::set('FACEBOOK_PAGE_ACCESS_TOKEN', trim($data['page_access_token']), 'marketing', false, $adminId);
        }

        if (isset($data['page_name'])) {
            SystemSetting::set('FACEBOOK_PAGE_NAME', trim($data['page_name']), 'marketing', true, $adminId);
        }

        AuditLog::create([
            'admin_id' => $adminId,
            'action' => 'UPDATE_FACEBOOK_CONFIG',
            'target_type' => 'SystemSetting',
            'target_id' => 0,
            'details' => 'Cập nhật cấu hình Facebook Page ID: ' . $data['page_id'],
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Đã lưu cấu hình Facebook Page.',
        ]);
    }

    /**
     * Kiểm tra kết nối Facebook Page qua Graph API
     */
    public function testFacebookConnection()
    {
        $res = $this->facebookService->verifyPageConnection();
        return response()->json([
            'status' => $res['success'] ? 1 : 0,
            'data' => $res,
            'message' => $res['message'],
        ]);
    }

    /**
     * Bước 1 & 2 & 3: Tải file Excel lên để xem trước (Preview) & Ghép ảnh Google Drive
     */
    public function previewImport(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'], // Tối đa 10MB
            'drive_folder_id' => ['nullable', 'string', 'max:255'],
            'channel' => ['nullable', 'string', 'in:website,facebook,both'],
        ]);

        $uploadedFile = $request->file('file');
        $folderId = $request->input('drive_folder_id') ?: SystemSetting::get('GOOGLE_DRIVE_FOLDER_ID');
        $channel = $request->input('channel', 'both');

        if (empty($folderId)) {
            return response()->json([
                'status' => 0,
                'message' => 'Chưa cấu hình Google Drive Folder ID. Vui lòng nhập Folder ID của thư mục chứa ảnh.',
            ], 422);
        }

        // 1. Kiểm tra quyền truy cập thư mục Google Drive
        $driveVerify = $this->driveService->verifyFolderAccess($folderId);
        if (!$driveVerify['success']) {
            return response()->json([
                'status' => 0,
                'message' => 'Không thể kết nối Google Drive: ' . $driveVerify['message'],
                'drive_verify' => $driveVerify,
            ], 422);
        }

        // 2. Đọc file Excel 4 cột
        try {
            $parsedExcel = $this->excelService->parseSpreadsheet($uploadedFile->getRealPath());
        } catch (Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Lỗi đọc file Excel: ' . $e->getMessage(),
            ], 422);
        }

        // 3. Lấy và lập chỉ mục toàn bộ ảnh trong thư mục Drive (phân trang đầy đủ)
        $indexedImages = $this->driveService->listImagesIndexedInFolder($folderId);

        // 4. Ghép ảnh, kiểm tra tính hợp lệ và kiểm tra trùng lặp
        $previewRows = [];
        $validCount = 0;
        $errorCount = 0;

        foreach ($parsedExcel['rows'] as $r) {
            $imageName = $r['image_name'];
            $matchResult = $this->driveService->matchImageName($imageName, $indexedImages);

            $rowStatus = $r['status'];
            $rowErrors = $r['errors'];

            if ($matchResult['status'] !== 'matched') {
                $rowStatus = 'error';
                $rowErrors[] = $matchResult['message'];
            }

            // Kiểm tra trùng lịch đã có trong hệ thống bằng fingerprint
            $fingerprint = null;
            $isDuplicate = false;
            if ($r['scheduled_at_iso']) {
                $fingerprint = PostSchedule::generateFingerprint($r['content'], $imageName, $r['scheduled_at_iso'], $channel);
                $existing = PostSchedule::where('fingerprint', $fingerprint)->first();
                if ($existing) {
                    $rowStatus = 'error';
                    $rowErrors[] = "Lịch đăng này đã tồn tại trong hệ thống (Tạo ngày {$existing->created_at->format('d/m/Y H:i')}, Trạng thái: {$existing->status_website}/{$existing->status_facebook}).";
                    $isDuplicate = true;
                }
            }

            if ($rowStatus === 'valid') {
                $validCount++;
            } else {
                $errorCount++;
            }

            $previewRows[] = [
                'row_number' => $r['row_number'],
                'content' => $r['content'],
                'content_preview' => $r['content_preview'],
                'title_suggested' => $r['title_suggested'],
                'title' => $r['title_suggested'],
                'image_name' => $imageName,
                'drive_match' => $matchResult,
                'scheduled_display' => $r['scheduled_display'],
                'scheduled_at' => $r['scheduled_at'],
                'scheduled_at_iso' => $r['scheduled_at_iso'],
                'status' => $rowStatus,
                'is_duplicate' => $isDuplicate,
                'fingerprint' => $fingerprint,
                'errors' => $rowErrors,
            ];
        }

        return response()->json([
            'status' => 1,
            'data' => [
                'file_name' => $uploadedFile->getClientOriginalName(),
                'drive_folder_id' => $folderId,
                'drive_folder_name' => $driveVerify['data']['folder_name'] ?? 'Thư mục Drive',
                'channel' => $channel,
                'total_rows' => count($previewRows),
                'valid_rows_count' => $validCount,
                'error_rows_count' => $errorCount,
                'skipped_empty_rows' => $parsedExcel['skipped_empty_rows'],
                'rows' => $previewRows,
            ],
        ]);
    }

    /**
     * Bước 4: Xác nhận Import -> Tải ảnh Snapshot về máy chủ -> Lên lịch đăng bài
     */
    public function confirmImport(Request $request)
    {
        $data = $request->validate([
            'file_name' => ['required', 'string', 'max:255'],
            'drive_folder_id' => ['required', 'string', 'max:255'],
            'channel' => ['required', 'string', 'in:website,facebook,both'],
            'default_category_id' => ['nullable', 'exists:post_categories,id'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.row_number' => ['required', 'integer'],
            'rows.*.content' => ['required', 'string'],
            'rows.*.title' => ['required', 'string', 'max:255'],
            'rows.*.title_suggested' => ['nullable', 'string', 'max:255'],
            'rows.*.image_name' => ['required', 'string', 'max:255'],
            'rows.*.drive_file_id' => ['required', 'string', 'max:255'],
            'rows.*.scheduled_at' => ['required', 'date'],
            'rows.*.fingerprint' => ['nullable', 'string', 'max:64'],
        ]);

        $adminId = $this->currentAdminId();
        $channel = $data['channel'];

        // Kiểm tra điều kiện kênh Facebook
        if ($channel === 'facebook' || $channel === 'both') {
            $fbStatus = $this->facebookService->getConnectionStatus();
            if (!$fbStatus['is_ready']) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Facebook Page chưa được kết nối hợp lệ. Không thể chọn kênh Facebook.',
                ], 422);
            }
        }

        // 1. Tạo bản ghi Batch
        $batch = new PostImportBatch();
        $batch->file_name = $data['file_name'];
        $batch->drive_folder_id = $data['drive_folder_id'];
        $batch->channel = $channel;
        $batch->default_category_id = $data['default_category_id'] ?? null;
        $batch->created_by_admin_id = $adminId;
        $batch->status = 'draft';
        $batch->total_rows = count($data['rows']);
        $batch->save();

        $savedSchedules = [];
        $failedRows = [];

        // 2. Tải snapshot từng ảnh từ Google Drive về lưu vào storage
        foreach ($data['rows'] as $r) {
            $driveFileId = $r['drive_file_id'];
            $imageName = $r['image_name'];

            $downloadResult = $this->driveService->downloadSnapshot($driveFileId, $batch->id, $imageName);

            if (!$downloadResult['success']) {
                $failedRows[] = [
                    'row_number' => $r['row_number'],
                    'image_name' => $imageName,
                    'error' => $downloadResult['message'],
                ];
                continue;
            }

            // Sinh fingerprint duy nhất
            $scheduledAtCarbon = Carbon::parse($r['scheduled_at']);
            $fingerprint = $r['fingerprint'] ?: PostSchedule::generateFingerprint(
                $r['content'],
                $imageName,
                $scheduledAtCarbon->toIso8601String(),
                $channel
            );

            // Kiểm tra trùng fingerprint
            if (PostSchedule::where('fingerprint', $fingerprint)->exists()) {
                $failedRows[] = [
                    'row_number' => $r['row_number'],
                    'image_name' => $imageName,
                    'error' => 'Lịch đăng trùng lặp với một bài đã tồn tại.',
                ];
                continue;
            }

            $schedule = new PostSchedule();
            $schedule->batch_id = $batch->id;
            $schedule->row_index = $r['row_number'];
            $schedule->fingerprint = $fingerprint;
            $schedule->content = $r['content'];
            $schedule->title = trim($r['title']);
            $schedule->title_suggested = trim($r['title_suggested'] ?? $r['title']);
            $schedule->category_id = $data['default_category_id'] ?? null;
            $schedule->drive_image_name = $imageName;
            $schedule->drive_file_id = $driveFileId;
            $schedule->image_snapshot_path = $downloadResult['path'];
            $schedule->image_mime = $downloadResult['mime'];
            $schedule->image_size = $downloadResult['size'];
            $schedule->image_checksum = $downloadResult['checksum'];
            $schedule->channel = $channel;
            $schedule->scheduled_at = $scheduledAtCarbon->utc();
            $schedule->created_by_admin_id = $adminId;

            // Đặt trạng thái ban đầu cho từng kênh
            $schedule->status_website = ($channel === 'website' || $channel === 'both') ? 'scheduled' : 'not_applicable';
            $schedule->status_facebook = ($channel === 'facebook' || $channel === 'both') ? 'scheduled' : 'not_applicable';

            $schedule->save();
            $savedSchedules[] = $schedule;
        }

        // Cập nhật thống kê batch
        $batch->valid_rows = count($savedSchedules);
        $batch->error_rows = count($failedRows);
        $batch->confirmed_by_admin_id = $adminId;
        $batch->confirmed_at = now();
        $batch->status = count($savedSchedules) > 0 ? 'scheduled' : 'failed';
        $batch->save();

        AuditLog::create([
            'admin_id' => $adminId,
            'action' => 'CONFIRM_POST_IMPORT',
            'target_type' => 'PostImportBatch',
            'target_id' => $batch->id,
            'details' => "Xác nhận lên lịch import {$batch->valid_rows} bài viết (Kênh: {$channel})",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'status' => 1,
            'message' => "Đã lên lịch thành công {$batch->valid_rows} bài viết từ đợt import #BATCH-{$batch->id}!",
            'data' => [
                'batch_id' => $batch->id,
                'scheduled_count' => count($savedSchedules),
                'failed_count' => count($failedRows),
                'failed_rows' => $failedRows,
            ],
        ]);
    }

    /**
     * Danh sách bài viết trong Lịch đăng bài (Hỗ trợ lọc đa điều kiện)
     */
    public function listSchedules(Request $request)
    {
        $query = PostSchedule::with(['batch', 'category', 'post']);

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->input('batch_id'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        if ($request->filled('status_website')) {
            $query->where('status_website', $request->input('status_website'));
        }

        if ($request->filled('status_facebook')) {
            $query->where('status_facebook', $request->input('status_facebook'));
        }

        if ($request->filled('search')) {
            $search = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('content', 'like', $search)
                  ->orWhere('drive_image_name', 'like', $search);
            });
        }

        if ($request->filled('date_from')) {
            $query->where('scheduled_at', '>=', Carbon::parse($request->input('date_from'))->startOfDay()->utc());
        }

        if ($request->filled('date_to')) {
            $query->where('scheduled_at', '<=', Carbon::parse($request->input('date_to'))->endOfDay()->utc());
        }

        $perPage = (int) $request->input('per_page', 15);
        $schedules = $query->orderBy('scheduled_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 1,
            'data' => $schedules,
        ]);
    }

    /**
     * Danh sách các Đợt import (Batches)
     */
    public function listBatches(Request $request)
    {
        $batches = PostImportBatch::with(['defaultCategory', 'createdByAdmin'])
            ->withCount('schedules')
            ->orderBy('id', 'desc')
            ->paginate((int)$request->input('per_page', 15));

        return response()->json([
            'status' => 1,
            'data' => $batches,
        ]);
    }

    /**
     * Chi tiết một lịch đăng
     */
    public function getScheduleDetail(int $id)
    {
        $schedule = PostSchedule::with(['batch', 'category', 'post'])->findOrFail($id);

        return response()->json([
            'status' => 1,
            'data' => $schedule,
        ]);
    }

    /**
     * Cập nhật thông tin lịch đăng khi chưa xuất bản (Title, Content, Scheduled Time, Category)
     */
    public function updateSchedule(Request $request, int $id)
    {
        $schedule = PostSchedule::findOrFail($id);

        if ($schedule->status_website === 'published' && $schedule->status_facebook === 'published') {
            return response()->json([
                'status' => 0,
                'message' => 'Bài viết đã xuất bản hoàn tất trên cả hai kênh, không thể chỉnh sửa lịch.',
            ], 422);
        }

        if ($schedule->locked_at && $schedule->locked_at->gt(now()->subMinutes(5))) {
            return response()->json([
                'status' => 0,
                'message' => 'Lịch đang trong quá trình xuất bản, không thể can thiệp lúc này.',
            ], 409);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'scheduled_at' => ['required', 'date'],
            'category_id' => ['nullable', 'exists:post_categories,id'],
        ]);

        $newScheduledAt = Carbon::parse($data['scheduled_at']);
        if ($newScheduledAt->lessThanOrEqualTo(now('Asia/Ho_Chi_Minh'))) {
            return response()->json([
                'status' => 0,
                'message' => 'Thời gian đăng mới phải ở trong tương lai.',
            ], 422);
        }

        $schedule->title = trim($data['title']);
        $schedule->content = $data['content'];
        $schedule->scheduled_at = $newScheduledAt->utc();
        $schedule->category_id = $data['category_id'] ?? null;

        // Nếu trạng thái đang là failed hoặc outcome_unknown, chuyển lại thành scheduled
        if ($schedule->status_website === 'failed') {
            $schedule->status_website = 'scheduled';
            $schedule->website_error = null;
        }
        if ($schedule->status_facebook === 'failed') {
            $schedule->status_facebook = 'scheduled';
            $schedule->facebook_error = null;
        }

        $schedule->save();

        AuditLog::create([
            'admin_id' => $this->currentAdminId(),
            'action' => 'UPDATE_POST_SCHEDULE',
            'target_type' => 'PostSchedule',
            'target_id' => $schedule->id,
            'details' => "Cập nhật lịch đăng #{$schedule->id} sang lúc {$newScheduledAt->format('d/m/Y H:i')}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Đã cập nhật lịch đăng thành công.',
            'data' => $schedule,
        ]);
    }

    /**
     * Hủy lịch đăng bài chưa thực hiện
     */
    public function cancelSchedule(Request $request, int $id)
    {
        $schedule = PostSchedule::findOrFail($id);

        if ($schedule->status_website === 'published' && $schedule->status_facebook === 'published') {
            return response()->json([
                'status' => 0,
                'message' => 'Bài viết đã xuất bản trên tất cả các kênh, không thể hủy lịch.',
            ], 422);
        }

        if ($schedule->status_website !== 'published' && $schedule->status_website !== 'not_applicable') {
            $schedule->status_website = 'cancelled';
        }

        if ($schedule->status_facebook !== 'published' && $schedule->status_facebook !== 'not_applicable') {
            $schedule->status_facebook = 'cancelled';
        }

        $schedule->save();

        AuditLog::create([
            'admin_id' => $this->currentAdminId(),
            'action' => 'CANCEL_POST_SCHEDULE',
            'target_type' => 'PostSchedule',
            'target_id' => $schedule->id,
            'details' => "Hủy lịch đăng bài #{$schedule->id}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'status' => 1,
            'message' => "Đã hủy lịch đăng #{$schedule->id}.",
        ]);
    }

    /**
     * Xuất bản ngay lập tức một bài theo yêu cầu của Admin (Publish Now)
     */
    public function publishNow(Request $request, int $id)
    {
        $res = $this->schedulerService->publishSingleSchedule($id);

        AuditLog::create([
            'admin_id' => $this->currentAdminId(),
            'action' => 'PUBLISH_NOW_SCHEDULE',
            'target_type' => 'PostSchedule',
            'target_id' => $id,
            'details' => "Thực hiện lệnh Đăng ngay bài #{$id}: " . json_encode($res),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'status' => ($res['status'] === 'success') ? 1 : 0,
            'message' => ($res['status'] === 'success') ? 'Đã xuất bản bài viết thành công!' : ($res['message'] ?? 'Xuất bản thất bại.'),
            'data' => $res,
        ]);
    }

    /**
     * Thử lại (Retry) lịch bị lỗi
     */
    public function retrySchedule(Request $request, int $id)
    {
        $schedule = PostSchedule::findOrFail($id);

        if ($schedule->status_website === 'failed') {
            $schedule->status_website = 'scheduled';
            $schedule->website_error = null;
        }

        if ($schedule->status_facebook === 'failed' || $schedule->status_facebook === 'outcome_unknown') {
            $schedule->status_facebook = 'scheduled';
            $schedule->facebook_error = null;
        }

        $schedule->locked_at = null;
        $schedule->locked_by = null;
        $schedule->save();

        AuditLog::create([
            'admin_id' => $this->currentAdminId(),
            'action' => 'RETRY_POST_SCHEDULE',
            'target_type' => 'PostSchedule',
            'target_id' => $id,
            'details' => "Đặt lại lịch retry cho bài #{$id}",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'status' => 1,
            'message' => 'Đã đưa bài viết trở lại hàng đợi sẵn sàng xuất bản.',
            'data' => $schedule,
        ]);
    }
}
