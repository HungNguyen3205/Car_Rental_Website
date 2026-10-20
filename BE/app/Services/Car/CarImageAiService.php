<?php

namespace App\Services\Car;

use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class CarImageAiService
{
    /**
     * Đường dẫn tới python script xử lý
     */
    protected string $scriptPath;

    public function __construct()
    {
        $this->scriptPath = base_path('scripts/car_processor/process_car_image.py');
    }

    /**
     * Chuẩn hóa và xóa nền ảnh xe theo chuẩn 1600x1000px, 8:5
     *
     * @param UploadedFile|string $input Tệp upload hoặc đường dẫn ảnh nội bộ
     * @return array
     * @throws DomainException
     */
    public function process($input): array
    {
        // 1. Chuẩn bị thư mục lưu trữ
        $originalsDir = public_path('uploads/cars/originals');
        $processedDir = public_path('uploads/cars/processed');
        $thumbsDir = public_path('uploads/cars/thumbs');

        File::ensureDirectoryExists($originalsDir);
        File::ensureDirectoryExists($processedDir);
        File::ensureDirectoryExists($thumbsDir);

        $hash = Str::random(12);
        $timestamp = time();

        // 2. Xác định file gốc
        if ($input instanceof UploadedFile) {
            $ext = strtolower($input->getClientOriginalExtension()) ?: 'png';
            $origFilename = "orig_{$timestamp}_{$hash}.{$ext}";
            $input->move($originalsDir, $origFilename);
            $origPath = $originalsDir . DIRECTORY_SEPARATOR . $origFilename;
            $originalUrl = "/uploads/cars/originals/{$origFilename}";
        } elseif (is_string($input)) {
            // Trường hợp là URL hoặc đường dẫn nội bộ đã có
            $normalizedPath = ltrim(parse_url($input, PHP_URL_PATH), '/');
            $fullPath = public_path($normalizedPath);

            if (!File::exists($fullPath)) {
                throw new DomainException("Tệp ảnh nguồn không tồn tại trên máy chủ.");
            }

            // Copy sang thư mục originals để giữ bản gốc an toàn
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) ?: 'png';
            $origFilename = "orig_{$timestamp}_{$hash}.{$ext}";
            $origPath = $originalsDir . DIRECTORY_SEPARATOR . $origFilename;
            File::copy($fullPath, $origPath);
            $originalUrl = "/uploads/cars/originals/{$origFilename}";
        } else {
            throw new DomainException("Đầu vào không hợp lệ để xử lý ảnh.");
        }

        // 3. Đường dẫn file đầu ra WebP
        $processedFilename = "car_norm_{$timestamp}_{$hash}.webp";
        $thumbFilename = "car_thumb_{$timestamp}_{$hash}.webp";

        $processedPath = $processedDir . DIRECTORY_SEPARATOR . $processedFilename;
        $thumbPath = $thumbsDir . DIRECTORY_SEPARATOR . $thumbFilename;

        // 4. Gọi Python Script
        $pythonBinary = $this->resolvePythonBinary();
        $process = new Process([
            $pythonBinary,
            $this->scriptPath,
            '--input', $origPath,
            '--output', $processedPath,
            '--thumb', $thumbPath
        ]);

        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            Log::error("CarImageAiService failed: " . ($output ?: $errorOutput));

            // Cố gắng đọc thông báo lỗi từ JSON trả về
            $decoded = json_decode($output, true);
            $errorMsg = $decoded['error'] ?? "Xử lý tách nền thất bại. Vui lòng thử lại với ảnh xe có độ phân giải cao và góc chụp rõ hơn.";
            throw new DomainException($errorMsg);
        }

        $resultJson = json_decode($process->getOutput(), true);
        if (!$resultJson || empty($resultJson['success'])) {
            $msg = $resultJson['error'] ?? "Không thể nhận diện hình thể xe trong ảnh.";
            throw new DomainException($msg);
        }

        $processedUrl = "/uploads/cars/processed/{$processedFilename}";
        $thumbnailUrl = "/uploads/cars/thumbs/{$thumbFilename}";

        return [
            'success'       => true,
            'original_url'  => $originalUrl,
            'processed_url' => $processedUrl,
            'thumbnail_url' => $thumbnailUrl,
            'canvas_width'  => $resultJson['canvas_width'] ?? 1600,
            'canvas_height' => $resultJson['canvas_height'] ?? 1000,
            'car_width'     => $resultJson['car_width'] ?? null,
            'car_height'    => $resultJson['car_height'] ?? null,
        ];
    }

    /**
     * Tìm đường dẫn thực thi python
     */
    protected function resolvePythonBinary(): string
    {
        return 'python';
    }
}
