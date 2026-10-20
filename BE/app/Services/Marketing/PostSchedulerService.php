<?php

namespace App\Services\Marketing;

use App\Models\Post;
use App\Models\PostSchedule;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class PostSchedulerService
{
    protected FacebookPublishService $facebookService;

    public function __construct(FacebookPublishService $facebookService)
    {
        $this->facebookService = $facebookService;
    }

    /**
     * Chạy tiến trình kiểm tra và xuất bản các bài viết đến lịch (Atomic Lock, chống chạy trùng)
     */
    public function processDueSchedules(int $limit = 20): array
    {
        $nowUtc = Carbon::now('UTC');
        $workerId = gethostname() . ':' . getmypid();

        // 1. Tìm các bài viết đến hạn cần xuất bản
        // Chỉ chọn các bài mà ít nhất một kênh đang 'scheduled' hoặc 'failed' (cần retry), và chưa bị lock bởi worker khác
        $dueScheduleIds = PostSchedule::where('scheduled_at', '<=', $nowUtc)
            ->where(function ($q) {
                $q->where('status_website', 'scheduled')
                  ->orWhere('status_facebook', 'scheduled');
            })
            ->where(function ($q) use ($nowUtc) {
                // Chưa lock hoặc lock quá hạn 10 phút (worker cũ bị chết đột ngột)
                $q->whereNull('locked_at')
                  ->orWhere('locked_at', '<', $nowUtc->copy()->subMinutes(10));
            })
            ->orderBy('scheduled_at', 'asc')
            ->limit($limit)
            ->pluck('id');

        if ($dueScheduleIds->isEmpty()) {
            return [
                'processed' => 0,
                'published' => 0,
                'failed' => 0,
                'overdue_flagged' => 0,
            ];
        }

        $results = [
            'processed' => 0,
            'published' => 0,
            'failed' => 0,
            'overdue_flagged' => 0,
        ];

        foreach ($dueScheduleIds as $scheduleId) {
            $scheduleResult = $this->publishSingleSchedule($scheduleId, $workerId);
            $results['processed']++;

            if ($scheduleResult['status'] === 'overdue') {
                $results['overdue_flagged']++;
            } elseif ($scheduleResult['status'] === 'success') {
                $results['published']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Xuất bản một bài viết cụ thể với Database Transaction & Row Lock
     */
    public function publishSingleSchedule(int $scheduleId, ?string $workerId = null): array
    {
        $workerId = $workerId ?: (gethostname() . ':' . getmypid());

        return DB::transaction(function () use ($scheduleId, $workerId) {
            /** @var PostSchedule $schedule */
            $schedule = PostSchedule::where('id', $scheduleId)->lockForUpdate()->first();

            if (!$schedule) {
                return ['status' => 'not_found', 'message' => 'Lịch đăng không tồn tại.'];
            }

            // Kiểm tra lock
            if ($schedule->locked_at && $schedule->locked_at->gt(now()->subMinutes(5)) && $schedule->locked_by !== $workerId) {
                return ['status' => 'locked', 'message' => 'Lịch đang được xử lý bởi worker khác.'];
            }

            // Lock bản ghi
            $schedule->locked_at = now();
            $schedule->locked_by = $workerId;
            $schedule->save();

            // 1. Kiểm tra chính sách Server Downtime / Quá hạn (Overdue Policy)
            // Nếu bài viết đã trễ hơn 12 tiếng (ví dụ cúp điện/tắt server cả ngày), không tự dồn đăng ồ ạt
            $maxLatenessHours = 12;
            if ($schedule->scheduled_at->lt(now()->subHours($maxLatenessHours))) {
                if ($schedule->status_website === 'scheduled') {
                    $schedule->status_website = 'failed';
                    $schedule->website_error = "Lịch đăng quá hạn ({$schedule->scheduled_at->format('d/m/Y H:i')}) do máy chủ tạm dừng hoạt động. Vui lòng chọn 'Đăng ngay' hoặc 'Đổi lịch'.";
                }
                if ($schedule->status_facebook === 'scheduled') {
                    $schedule->status_facebook = 'failed';
                    $schedule->facebook_error = "Lịch đăng quá hạn ({$schedule->scheduled_at->format('d/m/Y H:i')}) do máy chủ tạm dừng hoạt động. Vui lòng chọn 'Đăng ngay' hoặc 'Đổi lịch'.";
                }
                $schedule->locked_at = null;
                $schedule->locked_by = null;
                $schedule->save();

                return ['status' => 'overdue', 'message' => 'Lịch bị quá hạn do server ngừng hoạt động trước đó.'];
            }

            $websiteSuccess = false;
            $facebookSuccess = false;

            // 2. Xuất bản lên Website nếu kênh yêu cầu
            if ($schedule->channel === 'website' || $schedule->channel === 'both') {
                if ($schedule->status_website === 'scheduled' || $schedule->status_website === 'failed') {
                    $schedule->status_website = 'publishing';
                    $schedule->save();

                    try {
                        $post = $this->createWebsitePost($schedule);
                        $schedule->post_id = $post->id;
                        $schedule->status_website = 'published';
                        $schedule->website_error = null;
                        $websiteSuccess = true;
                    } catch (Exception $e) {
                        Log::error("Schedule #{$schedule->id} Website publish failed: " . $e->getMessage());
                        $schedule->status_website = 'failed';
                        $schedule->website_error = "Lỗi xuất bản Website: " . $e->getMessage();
                    }
                } elseif ($schedule->status_website === 'published') {
                    $websiteSuccess = true;
                }
            }

            // 3. Xuất bản lên Facebook nếu kênh yêu cầu
            if ($schedule->channel === 'facebook' || $schedule->channel === 'both') {
                if ($schedule->status_facebook === 'scheduled' || $schedule->status_facebook === 'failed') {
                    $schedule->status_facebook = 'publishing';
                    $schedule->save();

                    $fbResult = $this->facebookService->publishPhotoPost(
                        $schedule->content,
                        $schedule->image_snapshot_path ?: ''
                    );

                    if ($fbResult['success']) {
                        $schedule->status_facebook = 'published';
                        $schedule->facebook_post_id = $fbResult['post_id'] ?? null;
                        $schedule->facebook_post_url = $fbResult['post_url'] ?? null;
                        $schedule->facebook_error = null;
                        $facebookSuccess = true;
                    } elseif (($fbResult['status'] ?? '') === 'outcome_unknown') {
                        // Timeout: Chuyển sang outcome_unknown để admin đối soát, không retry mù
                        $schedule->status_facebook = 'outcome_unknown';
                        $schedule->facebook_error = $fbResult['message'];
                    } else {
                        $schedule->status_facebook = 'failed';
                        $schedule->facebook_error = $fbResult['message'];
                    }
                } elseif ($schedule->status_facebook === 'published') {
                    $facebookSuccess = true;
                }
            }

            // 4. Cập nhật trạng thái tổng thể và giải phóng lock
            $schedule->last_attempt_at = now();
            $schedule->retry_count = ($schedule->retry_count ?? 0) + 1;
            $schedule->locked_at = null;
            $schedule->locked_by = null;

            if ($websiteSuccess || $facebookSuccess) {
                $schedule->published_at = now();
            }

            $schedule->save();

            // Cập nhật trạng thái đợt import (Batch)
            if ($schedule->batch_id) {
                $this->updateBatchStatus($schedule->batch_id);
            }

            return [
                'status' => ($websiteSuccess || $facebookSuccess) ? 'success' : 'failed',
                'schedule_id' => $schedule->id,
                'status_website' => $schedule->status_website,
                'status_facebook' => $schedule->status_facebook,
            ];
        });
    }

    /**
     * Tạo bản ghi bài viết (Post) trên Website an toàn
     */
    protected function createWebsitePost(PostSchedule $schedule): Post
    {
        $title = !empty($schedule->title) ? trim($schedule->title) : (!empty($schedule->title_suggested) ? trim($schedule->title_suggested) : 'Tin tức xe');
        
        // Tạo slug duy nhất
        $baseSlug = Str::slug($title);
        $slug = $baseSlug ?: 'bai-viet-' . time();
        $counter = 1;
        while (Post::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        // Xử lý sanitize nội dung (bảo toàn ký tự tiếng Việt, xuống dòng an toàn, tránh XSS)
        $cleanContent = $this->sanitizeContentForWeb($schedule->content);
        $summary = mb_strlen($schedule->content, 'UTF-8') > 250 
            ? mb_substr($schedule->content, 0, 250, 'UTF-8') . '...' 
            : $schedule->content;

        $post = new Post();
        $post->title = $title;
        $post->slug = $slug;
        $post->summary = $summary;
        $post->content = $cleanContent;
        $post->featured_image = $schedule->snapshot_url;
        $post->featured_image_alt = $title;
        $post->category_id = $schedule->category_id ?: ($schedule->batch?->default_category_id);
        $post->author_id = $schedule->created_by_admin_id;
        $post->status = 'published';
        $post->published_at = now();
        $post->meta_title = mb_substr($title, 0, 70, 'UTF-8');
        $post->meta_description = mb_substr($summary, 0, 160, 'UTF-8');
        $post->save();

        return $post;
    }

    /**
     * Sanitize nội dung bài viết sang HTML an toàn có phân đoạn thẻ <p>
     */
    protected function sanitizeContentForWeb(string $rawContent): string
    {
        // Chống chèn mã độc HTML/Javascript
        $escaped = htmlspecialchars($rawContent, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Chia các đoạn theo 2 dòng xuống hàng
        $paragraphs = preg_split('/\r\n\r\n|\n\n/', $escaped);
        $html = '';

        foreach ($paragraphs as $p) {
            $pTrim = trim($p);
            if (!empty($pTrim)) {
                // Chuyển xuống dòng đơn thành <br>
                $pWithBr = nl2br($pTrim);
                $html .= "<p>{$pWithBr}</p>\n";
            }
        }

        return $html ?: "<p>{$escaped}</p>";
    }

    /**
     * Cập nhật trạng thái tổng hợp của Batch import
     */
    protected function updateBatchStatus(int $batchId): void
    {
        $batch = \App\Models\PostImportBatch::find($batchId);
        if (!$batch || $batch->status === 'cancelled') return;

        $counts = PostSchedule::where('batch_id', $batchId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN (status_website = 'published' OR status_website = 'not_applicable') AND (status_facebook = 'published' OR status_facebook = 'not_applicable') THEN 1 ELSE 0 END) as fully_done,
                SUM(CASE WHEN status_website = 'failed' OR status_facebook = 'failed' OR status_facebook = 'outcome_unknown' THEN 1 ELSE 0 END) as has_failure
            ")
            ->first();

        if ($counts && $counts->total > 0) {
            if ($counts->fully_done == $counts->total) {
                $batch->status = 'completed';
            } elseif ($counts->has_failure > 0) {
                $batch->status = 'partially_failed';
            } else {
                $batch->status = 'scheduled';
            }
            $batch->save();
        }
    }
}
