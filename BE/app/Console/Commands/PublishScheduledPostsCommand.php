<?php

namespace App\Console\Commands;

use App\Services\Marketing\PostSchedulerService;
use Illuminate\Console\Command;

class PublishScheduledPostsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketing:publish-scheduled {--limit=20 : Maximum number of posts to process in one run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra và tự động xuất bản các bài viết marketing đã đến lịch (Website & Facebook)';

    /**
     * Execute the console command.
     */
    public function handle(PostSchedulerService $schedulerService): int
    {
        $limit = (int) $this->option('limit') ?: 20;
        $this->info("Bắt đầu kiểm tra bài viết đến lịch xuất bản (tối đa {$limit} bài)...");

        $result = $schedulerService->processDueSchedules($limit);

        $this->line("Kết quả:");
        $this->line("- Đã xử lý: {$result['processed']}");
        $this->line("- Xuất bản thành công: {$result['published']}");
        $this->line("- Lỗi xuất bản: {$result['failed']}");
        $this->line("- Quá hạn (Overdue flagged): {$result['overdue_flagged']}");

        return Command::SUCCESS;
    }
}
