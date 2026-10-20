<?php

namespace App\Jobs;

use App\Services\Marketing\PollinationsImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GeneratePollinationsPreview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 20;
    public int $maxExceptions = 1;
    public int $timeout = 110;
    public bool $failOnTimeout = true;

    public function __construct(public string $id) {}

    public function handle(PollinationsImageService $service): void
    {
        $key = 'pollinations-preview:' . $this->id;
        $item = Cache::get($key);
        if (!$item || $item['status'] !== 'queued') return;
        $lock = Cache::lock('pollinations-generation', 130);
        if (!$lock->get()) {
            $this->release(55);
            return;
        }
        try {
            if (config('pollinations.mode') === 'classic' && !Cache::add('pollinations-classic-slot', true, 50)) {
                $this->release(55);
                return;
            }
            $item['status'] = 'processing';
            Cache::put($key, $item, now()->addDay());
            $image = $service->generate($item['prompt'], $item['seed']);
            $path = 'pollinations-previews/' . $this->id . '.' . $image['extension'];
            if (!Storage::disk('local')->put($path, $image['bytes'])) throw new RuntimeException('Không lưu được ảnh thử.');
            $item['status'] = 'ready';
            $item['path'] = $path;
            $item['mime'] = $image['mime'];
            $item['extension'] = $image['extension'];
            Cache::put($key, $item, now()->addDay());
        } catch (\Throwable $e) {
            $item['status'] = 'failed';
            $item['message'] = $e instanceof \DomainException
                ? $e->getMessage() : 'Kết nối/xử lý ảnh thất bại. Kiểm tra cấu hình hoặc thử lại sau.';
            Cache::put($key, $item, now()->addDay());
        } finally {
            $lock->release();
        }
    }

    public function failed(?\Throwable $exception): void
    {
        $key = 'pollinations-preview:' . $this->id;
        $item = Cache::get($key);
        if ($item && in_array($item['status'], ['queued', 'processing'], true)) {
            $item['status'] = 'failed';
            $item['message'] = 'Tác vụ dừng hoặc quá thời gian. Có thể tạo yêu cầu mới.';
            Cache::put($key, $item, now()->addDay());
        }
    }
}
