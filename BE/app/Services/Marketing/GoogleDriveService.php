<?php

namespace App\Services\Marketing;

use Google\Client;
use Google\Service\Drive;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class GoogleDriveService
{
    protected ?Client $client = null;
    protected ?Drive $service = null;
    protected ?string $serviceAccountEmail = null;
    protected ?string $configuredFolderId = null;

    public function __construct()
    {
        $this->initClient();
    }

    /**
     * Khởi tạo Google Client với Service Account hoặc OAuth credential
     */
    protected function initClient(): void
    {
        $this->configuredFolderId = SystemSetting::get('GOOGLE_DRIVE_FOLDER_ID') ?: config('services.google_drive.folder_id');

        // 1. Kiểm tra file json credentials service account
        $credentialsJson = SystemSetting::get('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON');
        $credentialsPath = config('services.google_drive.credentials_path', storage_path('app/google-service-account.json'));

        $authConfig = null;
        if (!empty($credentialsJson)) {
            $decoded = json_decode($credentialsJson, true);
            if (is_array($decoded) && isset($decoded['client_email'])) {
                $authConfig = $decoded;
            }
        } elseif (file_exists($credentialsPath)) {
            $raw = file_get_contents($credentialsPath);
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['client_email'])) {
                $authConfig = $decoded;
            }
        }

        if ($authConfig) {
            $this->serviceAccountEmail = $authConfig['client_email'] ?? null;
            try {
                $this->client = new Client();
                $this->client->setAuthConfig($authConfig);
                $this->client->addScope(Drive::DRIVE_READONLY);
                $this->service = new Drive($this->client);
            } catch (Exception $e) {
                Log::error("GoogleDriveService: Khởi tạo client thất bại - " . $e->getMessage());
                $this->client = null;
                $this->service = null;
            }
        }
    }

    /**
     * Lấy email Service Account để admin biết cần chia sẻ quyền Viewer cho email nào
     */
    public function getServiceAccountEmail(): ?string
    {
        return $this->serviceAccountEmail;
    }

    /**
     * Lấy Folder ID cấu hình hiện tại
     */
    public function getConfiguredFolderId(): ?string
    {
        return $this->configuredFolderId;
    }

    /**
     * Trích xuất folder ID nếu người dùng paste cả link https://drive.google.com/drive/folders/...
     */
    public static function extractFolderId(?string $input): ?string
    {
        if (empty($input)) {
            return null;
        }

        $input = trim($input);
        if (preg_match('/folders\/([a-zA-Z0-9_-]+)/', $input, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^[a-zA-Z0-9_-]{15,}$/', $input)) {
            return $input;
        }

        return $input;
    }

    /**
     * Kiểm tra trạng thái kết nối và quyền đọc thư mục Google Drive
     */
    public function verifyFolderAccess(?string $folderId = null): array
    {
        $targetFolderId = self::extractFolderId($folderId ?: $this->configuredFolderId);

        if (empty($this->service)) {
            return [
                'success' => false,
                'status' => 'not_configured',
                'message' => 'Chưa cấu hình Service Account Google Drive hoặc thông tin xác thực không hợp lệ.',
                'service_account_email' => $this->serviceAccountEmail,
            ];
        }

        if (empty($targetFolderId)) {
            return [
                'success' => false,
                'status' => 'missing_folder_id',
                'message' => 'Chưa cung cấp Google Drive Folder ID.',
                'service_account_email' => $this->serviceAccountEmail,
            ];
        }

        try {
            // Lấy thông tin thư mục
            $folder = $this->service->files->get($targetFolderId, [
                'fields' => 'id, name, mimeType, trashed',
                'supportsAllDrives' => true,
            ]);

            if ($folder->getTrashed()) {
                return [
                    'success' => false,
                    'status' => 'trashed',
                    'message' => 'Thư mục Google Drive này đã bị chuyển vào thùng rác (Trash).',
                    'service_account_email' => $this->serviceAccountEmail,
                ];
            }

            // Đếm số lượng ảnh trực tiếp trong thư mục (chỉ tìm trong folder, không đệ quy)
            $query = "'{$targetFolderId}' in parents and trashed = false and (mimeType = 'image/jpeg' or mimeType = 'image/png' or mimeType = 'image/webp')";
            $filesResponse = $this->service->files->listFiles([
                'q' => $query,
                'pageSize' => 10,
                'fields' => 'nextPageToken, files(id, name, mimeType, size)',
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
            ]);

            // Cập nhật timestamp lần đồng bộ gần nhất
            SystemSetting::set('GOOGLE_DRIVE_LAST_SYNCED_AT', now()->toIso8601String(), 'marketing', false);

            return [
                'success' => true,
                'status' => 'connected',
                'message' => 'Kết nối Google Drive thành công và có quyền đọc thư mục.',
                'data' => [
                    'folder_id' => $folder->getId(),
                    'folder_name' => $folder->getName(),
                    'service_account_email' => $this->serviceAccountEmail,
                    'last_synced_at' => now()->toIso8601String(),
                ],
            ];
        } catch (Exception $e) {
            Log::warning("Google Drive Verify Error for folder {$targetFolderId}: " . $e->getMessage());
            return [
                'success' => false,
                'status' => 'permission_denied',
                'message' => 'Không thể đọc thư mục Google Drive. Vui lòng đảm bảo bạn đã chia sẻ thư mục cho email Service Account với quyền "Viewer" (Người xem).',
                'error_detail' => $e->getMessage(),
                'service_account_email' => $this->serviceAccountEmail,
            ];
        }
    }

    /**
     * Lấy toàn bộ danh sách file ảnh trong thư mục Google Drive với phân trang đầy đủ (pageToken)
     * và lập chỉ mục theo tên file chính xác (đã chuẩn hóa Unicode NFC).
     */
    public function listImagesIndexedInFolder(string $folderId): array
    {
        $folderId = self::extractFolderId($folderId);
        if (!$this->service || empty($folderId)) {
            return [];
        }

        $indexedFiles = [];
        $pageToken = null;
        $query = "'{$folderId}' in parents and trashed = false and (mimeType = 'image/jpeg' or mimeType = 'image/png' or mimeType = 'image/webp')";

        do {
            try {
                $response = $this->service->files->listFiles([
                    'q' => $query,
                    'pageSize' => 100,
                    'fields' => 'nextPageToken, files(id, name, mimeType, size, md5Checksum, modifiedTime, thumbnailLink)',
                    'pageToken' => $pageToken,
                    'supportsAllDrives' => true,
                    'includeItemsFromAllDrives' => true,
                ]);

                foreach ($response->getFiles() as $file) {
                    $rawName = $file->getName();
                    $normalizedName = class_exists('\Normalizer') 
                        ? \Normalizer::normalize($rawName, \Normalizer::FORM_C) 
                        : $rawName;
                    
                    $key = mb_strtolower(trim($normalizedName), 'UTF-8');

                    $fileInfo = [
                        'id' => $file->getId(),
                        'name' => $rawName,
                        'normalized_name' => $normalizedName,
                        'mime_type' => $file->getMimeType(),
                        'size' => (int) $file->getSize(),
                        'checksum' => $file->getMd5Checksum() ?: null,
                        'modified_time' => $file->getModifiedTime() ?: null,
                        'thumbnail_link' => $file->getThumbnailLink() ?: null,
                    ];

                    if (!isset($indexedFiles[$key])) {
                        $indexedFiles[$key] = [];
                    }
                    $indexedFiles[$key][] = $fileInfo;
                }

                $pageToken = $response->getNextPageToken();
            } catch (Exception $e) {
                Log::error("Google Drive List Images Exception: " . $e->getMessage());
                break;
            }
        } while (!empty($pageToken));

        return $indexedFiles;
    }

    /**
     * Ghép tên ảnh từ Excel với danh sách file trong Google Drive
     */
    public function matchImageName(string $imageNameFromExcel, array $indexedFiles): array
    {
        $trimmed = trim($imageNameFromExcel);
        if (empty($trimmed)) {
            return [
                'status' => 'empty',
                'file' => null,
                'message' => 'Tên ảnh bị để trống trong file Excel.',
            ];
        }

        $normalized = class_exists('\Normalizer') 
            ? \Normalizer::normalize($trimmed, \Normalizer::FORM_C) 
            : $trimmed;
        $key = mb_strtolower($normalized, 'UTF-8');

        if (!isset($indexedFiles[$key]) || empty($indexedFiles[$key])) {
            return [
                'status' => 'not_found',
                'file' => null,
                'message' => "Không tìm thấy ảnh \"{$trimmed}\" trong thư mục Google Drive đã cấu hình.",
            ];
        }

        $matches = $indexedFiles[$key];
        if (count($matches) > 1) {
            return [
                'status' => 'duplicate',
                'files' => $matches,
                'message' => "Phát hiện " . count($matches) . " file cùng tên \"{$trimmed}\" trong thư mục Drive. Vui lòng đổi tên hoặc xóa bớt file trùng.",
            ];
        }

        $file = $matches[0];
        return [
            'status' => 'matched',
            'file' => $file,
            'message' => 'Đã ghép ảnh chính xác với Google Drive.',
        ];
    }

    /**
     * Tải bản ảnh gốc từ Google Drive về lưu vào storage snapshot của dự án
     * Chống SSRF, decompression bomb và xác thực an toàn
     */
    public function downloadSnapshot(string $driveFileId, int $batchId, string $originalFileName): array
    {
        if (!$this->service) {
            return [
                'success' => false,
                'message' => 'Dịch vụ Google Drive chưa được khởi tạo.',
            ];
        }

        try {
            // 1. Tải nội dung stream từ Google Drive
            $response = $this->service->files->get($driveFileId, [
                'alt' => 'media',
                'supportsAllDrives' => true,
            ]);

            $content = $response->getBody()->getContents();
            $contentLength = strlen($content);

            if ($contentLength === 0) {
                return [
                    'success' => false,
                    'message' => "File ảnh từ Google Drive trống (0 bytes).",
                ];
            }

            // Giới hạn dung lượng tối đa 15MB
            $maxBytes = 15 * 1024 * 1024;
            if ($contentLength > $maxBytes) {
                return [
                    'success' => false,
                    'message' => "File ảnh quá lớn (" . round($contentLength / 1024 / 1024, 2) . "MB). Giới hạn tối đa là 15MB.",
                ];
            }

            // 2. Kiểm tra MIME thực tế từ binary buffer
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($content);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($mime, $allowedMimes, true)) {
                return [
                    'success' => false,
                    'message' => "Định dạng file không an toàn hoặc không được hỗ trợ (MIME nhận diện: {$mime}). Chỉ chấp nhận JPG, PNG, WEBP.",
                ];
            }

            // 3. Chống decompression bomb: Kiểm tra kích thước điểm ảnh pixel (getimagesizefromstring)
            $imageInfo = @getimagesizefromstring($content);
            if ($imageInfo === false) {
                return [
                    'success' => false,
                    'message' => "Dữ liệu ảnh bị hỏng, không thể giải mã hình ảnh hợp lệ.",
                ];
            }

            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $pixels = $width * $height;

            // Chặn ảnh vượt quá 40 Megapixels hoặc kích thước > 8000px mỗi cạnh
            if ($width > 8000 || $height > 8000 || $pixels > 40000000) {
                return [
                    'success' => false,
                    'message' => "Kích thước hình ảnh vượt ngưỡng an toàn ({$width}×{$height}px). Nguy cơ gây tràn tài nguyên bộ nhớ.",
                ];
            }

            // 4. Sinh tên lưu trữ nội bộ an toàn (không dùng đường dẫn tùy ý của người dùng)
            $ext = match ($mime) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };

            $hashPart = substr(md5($driveFileId . '_' . $contentLength), 0, 12);
            $safeStorageName = "snap_b{$batchId}_{$hashPart}_" . time() . ".{$ext}";
            $relativePath = "posts/snapshots/{$batchId}/{$safeStorageName}";

            // 5. Lưu vào storage disk 'public'
            Storage::disk('public')->put($relativePath, $content);

            $checksum = md5($content);

            return [
                'success' => true,
                'path' => $relativePath,
                'url' => Storage::disk('public')->url($relativePath),
                'mime' => $mime,
                'size' => $contentLength,
                'width' => $width,
                'height' => $height,
                'checksum' => $checksum,
            ];
        } catch (Exception $e) {
            Log::error("Google Drive downloadSnapshot error for file {$driveFileId}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Lỗi khi tải ảnh từ Google Drive: " . $e->getMessage(),
            ];
        }
    }
}
