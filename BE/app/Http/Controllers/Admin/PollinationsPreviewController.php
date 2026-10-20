<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GeneratePollinationsPreview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PollinationsPreviewController extends Controller
{
    private function actorId(): int
    {
        return (int) Auth::guard('admin')->id();
    }

    private function owned(string $id): array
    {
        abort_unless(Str::isUuid($id), 404);
        $item = Cache::get('pollinations-preview:' . $id);
        abort_unless($item && $item['admin_id'] === $this->actorId(), 404);
        return $item;
    }

    public function config()
    {
        return response()->json(['status' => 1, 'data' => [
            'enabled' => (bool) config('pollinations.enabled'),
            'configured' => config('pollinations.mode') === 'classic' || (bool) config('pollinations.api_key'),
            'mode' => config('pollinations.mode'),
            'model' => config('pollinations.model'),
            'daily_limit' => (int) config('pollinations.daily_limit'),
        ]]);
    }

    public function store(Request $request)
    {
        abort_unless(config('pollinations.enabled'), 503, 'Máy chủ chưa bật Pollinations.');
        abort_unless(config('pollinations.mode') === 'classic' || config('pollinations.api_key'), 503, 'Máy chủ chưa cấu hình Pollinations API key.');
        abort_if(in_array(config('cache.default'), ['array', 'null'], true), 503, 'Cần cache bền vững dùng chung web và worker.');
        abort_if(in_array(config('queue.default'), ['sync', 'null', 'deferred', 'background'], true), 503, 'Cần queue database/redis và worker marketing-images.');
        abort_if((int) config('queue.connections.' . config('queue.default') . '.retry_after', 180) <= 110, 503, 'Đặt retry_after của queue lớn hơn 110 giây (đề xuất 180).');
        $data = $request->validate(['prompt' => ['required', 'string', 'min:20', 'max:3000'], 'seed' => ['nullable', 'integer', 'min:0', 'max:2147483647']]);
        $adminId = $this->actorId();
        $lock = Cache::lock('pollinations-submit:' . $adminId, 10);
        abort_unless($lock->get(), 429, 'Yêu cầu đang được tạo.');
        try {
            $rateKey = 'pollinations-daily:' . $adminId . ':' . now()->format('Y-m-d');
            abort_if(RateLimiter::tooManyAttempts($rateKey, max(1, (int) config('pollinations.daily_limit'))), 429, 'Đã đạt hạn mức ảnh thử hôm nay.');
            RateLimiter::hit($rateKey, 86400);
            $id = (string) Str::uuid();
            Cache::put('pollinations-preview:' . $id, [
                'admin_id' => $adminId, 'status' => 'queued',
                'prompt' => $data['prompt'] . "\nVehicle marketing illustration. No people, no faces, no text, no watermarks added by the prompt. Do not claim this is a photograph of an actual rental vehicle.",
                'seed' => $data['seed'] ?? random_int(0, 2147483647), 'created_at' => now()->toIso8601String(),
            ], now()->addDay());
            try {
                GeneratePollinationsPreview::dispatch($id)->onQueue(config('pollinations.queue'));
            } catch (\Throwable $e) {
                Cache::forget('pollinations-preview:' . $id);
                RateLimiter::decrement($rateKey);
                return response()->json(['message' => 'Không đưa được yêu cầu vào hàng đợi.'], 503);
            }
            return response()->json(['status' => 1, 'data' => ['id' => $id, 'status' => 'queued']], 202);
        } finally { $lock->release(); }
    }

    public function show(string $id)
    {
        $item = $this->owned($id);
        if (in_array($item['status'], ['queued', 'processing'], true) && now()->diffInMinutes(\Carbon\Carbon::parse($item['created_at']), true) > 15) {
            $item['status'] = 'failed';
            $item['message'] = 'Tác vụ quá lâu. Kiểm tra worker marketing-images trước khi thử lại.';
        }
        return response()->json(['status' => 1, 'data' => ['id' => $id, 'status' => $item['status'], 'message' => $item['message'] ?? null]]);
    }

    public function image(string $id)
    {
        $item = $this->owned($id);
        abort_unless($item['status'] === 'ready' && Storage::disk('local')->exists($item['path']), 404);
        return Storage::disk('local')->response($item['path'], null, ['Content-Type' => $item['mime'], 'Cache-Control' => 'private, no-store']);
    }

    public function approve(string $id)
    {
        $lock = Cache::lock('pollinations-approve:' . $id, 10);
        abort_unless($lock->get(), 409, 'Ảnh đang được áp dụng.');
        try {
            $item = $this->owned($id);
            abort_unless($item['status'] === 'ready', 409, 'Ảnh chưa sẵn sàng.');
            $path = 'posts/pollinations/' . $id . '.' . $item['extension'];
            if (!Storage::disk('public')->exists($path)) {
                abort_unless(Storage::disk('public')->put($path, Storage::disk('local')->get($item['path'])), 500);
            }
            // This only returns a cover URL. Post saving/review/publishing is still explicit.
            return response()->json(['status' => 1, 'data' => ['url' => Storage::disk('public')->url($path), 'alt' => 'Ảnh xe minh họa được tạo bằng AI']]);
        } finally { $lock->release(); }
    }
}
