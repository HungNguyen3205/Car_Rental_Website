<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateBillsToPrivateStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bills:migrate-private {--dry-run : Check files without moving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Di chuyển an toàn các biên lai từ thư mục public/uploads/bills sang storage riêng tư có phân quyền';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $this->info($dryRun ? 'Đang kiểm tra biên lai (Chế độ Dry-Run)...' : 'Bắt đầu di chuyển biên lai sang private storage...');

        $privateDir = storage_path('app/private/bills');
        if (!File::exists($privateDir)) {
            File::makeDirectory($privateDir, 0755, true);
        }

        $bookings = Booking::whereNotNull('bill_image')->where('bill_image', '!=', '')->get();
        $migrated = 0;
        $missing = 0;
        $alreadyPrivate = 0;

        foreach ($bookings as $booking) {
            $currentPath = $booking->bill_image;
            $filename = basename($currentPath);

            // Kiểm tra nếu đã ở private storage
            if (str_starts_with($currentPath, 'private/bills/')) {
                $alreadyPrivate++;
                continue;
            }

            // Tìm file tại public uploads
            $publicFilePath = public_path($currentPath);
            if (!File::exists($publicFilePath)) {
                $fallbackPath = public_path('uploads/bills/' . $filename);
                if (File::exists($fallbackPath)) {
                    $publicFilePath = $fallbackPath;
                } else {
                    $this->warn("Không tìm thấy file vật lý cho đơn #{$booking->ma_dat_xe}: {$currentPath}");
                    $missing++;
                    continue;
                }
            }

            $destinationPath = $privateDir . DIRECTORY_SEPARATOR . $filename;

            if ($dryRun) {
                $this->line("[Dry-Run] Sẽ di chuyển: {$publicFilePath} -> {$destinationPath}");
                $migrated++;
            } else {
                // Di chuyển file an toàn (Copy trước rồi xóa public)
                File::copy($publicFilePath, $destinationPath);
                
                // Cập nhật đường dẫn trong database
                $booking->bill_image = 'private/bills/' . $filename;
                $booking->save();

                // Xóa file public để không bị truy cập trái phép
                File::delete($publicFilePath);

                $this->info("Đã bảo mật biên lai cho đơn #{$booking->ma_dat_xe}: {$filename}");
                $migrated++;
            }
        }

        $this->newLine();
        $this->info("Kết quả di chuyển biên lai:");
        $this->info("- Đã bảo mật chuyển đổi: {$migrated} tệp");
        $this->info("- Đã ở trạng thái riêng tư: {$alreadyPrivate} tệp");
        if ($missing > 0) {
            $this->warn("- Thiếu file vật lý cũ: {$missing} tệp");
        }

        return Command::SUCCESS;
    }
}
