<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\Http;
use DomainException;

/** Adapted from TOOL-MKT PollinationsImageProvider; no cookies or paid fallback. */
class PollinationsImageService
{
    public function generate(string $prompt, int $seed): array
    {
        if (!config('pollinations.enabled')) {
            throw new DomainException('Chưa bật Pollinations trên máy chủ.');
        }
        $mode = config('pollinations.mode');
        $maxBytes = (int) config('pollinations.max_bytes');
        $request = Http::connectTimeout(15)->timeout((int) config('pollinations.timeout'))
            ->withOptions(['allow_redirects' => false]);
        if ($mode === 'classic') {
            // Fixed upstream host: callers cannot supply an arbitrary URL or headers.
            $response = $request->get('https://image.pollinations.ai/prompt/' . rawurlencode($prompt), [
                'width' => 768, 'height' => 768, 'seed' => $seed, 'safe' => 'true',
            ]);
        } elseif ($mode === 'keyed') {
            $key = config('pollinations.api_key');
            if (!$key) {
                throw new DomainException('Thiếu POLLINATIONS_API_KEY. Không tự chuyển sang provider khác.');
            }
            $response = $request->withToken($key)->acceptJson()->post('https://gen.pollinations.ai/v1/images/generations', [
                'prompt' => $prompt, 'model' => config('pollinations.model'),
                'n' => 1, 'size' => '1024x1024', 'quality' => 'low',
                'response_format' => 'b64_json',
            ]);
        } else {
            throw new DomainException('POLLINATIONS_MODE không hợp lệ.');
        }
        if (!$response->successful()) {
            $message = match ($response->status()) {
                401, 403 => 'Pollinations: key không hợp lệ hoặc thiếu quyền model.',
                402 => 'Pollinations: hết Pollen/ngân sách hoặc chưa đến lượt miễn phí. Không tự trả phí.',
                429 => 'Pollinations đang giới hạn lượt. Hãy thử lại sau.',
                default => 'Pollinations chưa trả ảnh thành công. Hãy thử lại sau.',
            };
            // Never surface upstream body, request URL, API key or raw exception.
            throw new DomainException($message);
        }
        if ($mode === 'keyed') {
            $encoded = $response->json('data.0.b64_json');
            if (!is_string($encoded) || strlen($encoded) > (int) ceil($maxBytes * 4 / 3) + 16) {
                throw new DomainException('Phản hồi ảnh rỗng hoặc vượt dung lượng cho phép.');
            }
            $bytes = base64_decode($encoded, true);
        } else {
            $bytes = $response->body();
        }
        if (!is_string($bytes) || $bytes === '' || strlen($bytes) > $maxBytes) {
            throw new DomainException('Dữ liệu ảnh không hợp lệ hoặc quá lớn.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $size = @getimagesizefromstring($bytes);
        if (!isset($extensions[$mime]) || !$size || $size[0] < 256 || $size[1] < 256 || $size[0] * $size[1] > 16000000) {
            throw new DomainException('Pollinations trả dữ liệu không phải ảnh hợp lệ.');
        }
        return ['bytes' => $bytes, 'mime' => $mime, 'extension' => $extensions[$mime]];
    }
}
