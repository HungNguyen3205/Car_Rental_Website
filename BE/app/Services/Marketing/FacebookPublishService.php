<?php

namespace App\Services\Marketing;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class FacebookPublishService
{
    protected ?string $appId = null;
    protected ?string $appSecret = null;
    protected ?string $pageId = null;
    protected ?string $pageAccessToken = null;
    protected ?string $pageName = null;
    protected string $graphVersion = 'v21.0';
    protected int $timeout = 35;
    protected string $baseUrl;

    public function __construct()
    {
        $this->appId = SystemSetting::get('FACEBOOK_APP_ID') ?: config('services.facebook.app_id');
        $this->appSecret = SystemSetting::get('FACEBOOK_APP_SECRET') ?: config('services.facebook.app_secret');
        $this->pageId = SystemSetting::get('FACEBOOK_PAGE_ID') ?: config('services.facebook.page_id');
        $this->pageAccessToken = SystemSetting::get('FACEBOOK_PAGE_ACCESS_TOKEN') ?: config('services.facebook.page_access_token');
        $this->pageName = SystemSetting::get('FACEBOOK_PAGE_NAME');

        $this->graphVersion = config('services.facebook.graph_version', 'v21.0');
        $this->timeout = (int) config('services.facebook.timeout', 35);
        $this->baseUrl = "https://graph.facebook.com/{$this->graphVersion}";
    }

    /**
     * Kiểm tra trạng thái sẵn sàng kết nối Facebook Page
     */
    public function getConnectionStatus(): array
    {
        $hasCredentials = !empty($this->pageId) && !empty($this->pageAccessToken);

        if (!$hasCredentials) {
            return [
                'is_ready' => false,
                'status' => 'not_configured',
                'message' => 'Chưa cấu hình Facebook Page ID hoặc Page Access Token.',
                'page_id' => $this->pageId,
                'page_name' => $this->pageName,
            ];
        }

        return [
            'is_ready' => true,
            'status' => 'configured',
            'message' => 'Facebook Page đã được cấu hình sẵn sàng.',
            'page_id' => $this->pageId,
            'page_name' => $this->pageName,
            'graph_version' => $this->graphVersion,
        ];
    }

    /**
     * Xác thực Page Token và lấy thông tin Page trực tiếp từ Graph API (Không đăng thử bài thật)
     */
    public function verifyPageConnection(): array
    {
        if (empty($this->pageId) || empty($this->pageAccessToken)) {
            return [
                'success' => false,
                'message' => 'Thiếu Page ID hoặc Page Access Token trong cấu hình hệ thống.',
            ];
        }

        // Kiểm tra token mock giả lập trong môi trường dev/testing
        if (str_starts_with($this->pageAccessToken, 'mock_') || str_starts_with($this->pageAccessToken, 'dummy_')) {
            return [
                'success' => true,
                'mock' => true,
                'data' => [
                    'id' => $this->pageId,
                    'name' => $this->pageName ?: 'VinFast Rental Page (Mock)',
                ],
                'message' => 'Kết nối thành công (Môi trường giả lập / Mock Token).',
            ];
        }

        try {
            $response = Http::timeout($this->timeout)->get("{$this->baseUrl}/{$this->pageId}", [
                'access_token' => $this->pageAccessToken,
                'fields' => 'id,name,category,link,verification_status',
            ]);

            if ($response->failed()) {
                $error = $response->json('error') ?? [];
                Log::warning("Facebook verifyPageConnection failed: " . ($error['message'] ?? $response->body()));
                return [
                    'success' => false,
                    'message' => $error['message'] ?? 'Page Token không hợp lệ hoặc đã hết hạn.',
                    'error_code' => $error['code'] ?? null,
                ];
            }

            $pageData = $response->json();
            if (!empty($pageData['name'])) {
                SystemSetting::set('FACEBOOK_PAGE_NAME', $pageData['name'], 'marketing', true);
            }

            return [
                'success' => true,
                'mock' => false,
                'data' => $pageData,
                'message' => "Kết nối thành công tới Facebook Page \"{$pageData['name']}\".",
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'message' => 'Không thể kết nối tới máy chủ Facebook Graph API (Timeout hoặc lỗi mạng).',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi kiểm tra Facebook: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Xuất bản bài viết kèm ảnh snapshot lên Facebook Page
     * Xử lý chặt chẽ timeout: Nếu timeout khi POST ảnh -> chuyển outcome_unknown để đối soát, KHÔNG retry mù.
     */
    public function publishPhotoPost(string $caption, string $snapshotStoragePath): array
    {
        if (empty($this->pageId) || empty($this->pageAccessToken)) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Chưa cấu hình Facebook Page ID hoặc Page Access Token.',
            ];
        }

        // Lấy đường dẫn file tuyệt đối từ snapshot
        $fullPath = Storage::disk('public')->path($snapshotStoragePath);
        if (!file_exists($fullPath)) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => "Không tìm thấy file snapshot ảnh trên máy chủ: {$snapshotStoragePath}",
            ];
        }

        // Xử lý mock token an toàn trong test/dev (không gọi Meta thật)
        if (str_starts_with($this->pageAccessToken, 'mock_') || str_starts_with($this->pageAccessToken, 'dummy_')) {
            $mockPostId = "{$this->pageId}_" . time() . "_" . random_int(1000, 9999);
            return [
                'success' => true,
                'status' => 'published',
                'post_id' => $mockPostId,
                'post_url' => "https://facebook.com/{$mockPostId}",
                'message' => 'Đã xuất bản thành công lên Facebook (Mock mode).',
            ];
        }

        try {
            $fileName = basename($fullPath);
            $fileContents = file_get_contents($fullPath);

            $response = Http::timeout($this->timeout)
                ->attach('source', $fileContents, $fileName)
                ->post("{$this->baseUrl}/{$this->pageId}/photos", [
                    'access_token' => $this->pageAccessToken,
                    'message' => $caption,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $photoId = $data['id'] ?? null;
                $postId = $data['post_id'] ?? $photoId;
                $postUrl = $postId ? "https://facebook.com/{$postId}" : null;

                return [
                    'success' => true,
                    'status' => 'published',
                    'post_id' => $postId,
                    'post_url' => $postUrl,
                    'data' => $data,
                ];
            }

            // Ghi log lỗi không log token
            $error = $response->json('error') ?? [];
            $errorMessage = $error['message'] ?? 'Lỗi không xác định khi đăng bài lên Facebook.';
            $errorCode = $error['code'] ?? null;

            Log::error("Facebook Publish Photo Failed [Page: {$this->pageId}]: {$errorMessage}", [
                'code' => $errorCode,
                'type' => $error['type'] ?? null,
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'message' => "Facebook Graph API Error ({$errorCode}): {$errorMessage}",
                'error_code' => $errorCode,
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // CRITICAL: Request có thể đã tới Facebook nhưng phản hồi bị timeout.
            // Chuyển sang outcome_unknown để admin đối soát, KHÔNG retry mù làm trùng bài!
            Log::warning("Facebook Publish Timeout for snapshot {$snapshotStoragePath}: " . $e->getMessage());

            return [
                'success' => false,
                'status' => 'outcome_unknown',
                'message' => 'Yêu cầu đăng Facebook bị timeout (phản hồi quá lâu). Bài viết có thể đã xuất bản trên Page. Vui lòng kiểm tra đối soát trên Page trước khi thao tác tiếp.',
            ];
        } catch (Exception $e) {
            Log::error("Facebook Publish Exception: " . $e->getMessage());

            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Lỗi hệ thống khi đăng bài: ' . $e->getMessage(),
            ];
        }
    }
}
