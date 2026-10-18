<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendBookingRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:send-reminders {--hours=24 : Khoảng thời gian quét trước giờ nhận/trả xe (mặc định 24h)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quét các đơn đặt xe sắp nhận xe hoặc sắp trả xe và gửi thông báo nhắc lịch với cơ chế chống gửi trùng.';

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours') ?: 24;
        $now = Carbon::now();
        $targetWindow = $now->copy()->addHours($hours);

        $this->info("Bắt đầu quét nhắc lịch từ [{$now->toDateTimeString()}] đến [{$targetWindow->toDateTimeString()}]...");

        $pickupCount = 0;
        $returnCount = 0;
        $skippedPickup = 0;
        $skippedReturn = 0;

        // 1. Quét các đơn sắp nhận xe (Đã duyệt, chuẩn bị nhận trong 24h tới)
        $upcomingPickups = Booking::with(['user', 'car'])
            ->where('tinh_trang', Booking::STATUS_CONFIRMED)
            ->whereBetween('start_date', [$now, $targetWindow])
            ->get();

        foreach ($upcomingPickups as $booking) {
            $sent = $this->notificationService->notifyUpcomingPickup($booking);
            if ($sent) {
                $pickupCount++;
                $this->line(" - [Nhận xe] Đã gửi thông báo cho đơn #{$booking->ma_dat_xe}");
            } else {
                $skippedPickup++;
            }
        }

        // 2. Quét các đơn sắp trả xe (Đang thuê, chuẩn bị trả trong 24h tới)
        $upcomingReturns = Booking::with(['user', 'car'])
            ->where('tinh_trang', Booking::STATUS_IN_PROGRESS)
            ->whereBetween('end_date', [$now, $targetWindow])
            ->get();

        foreach ($upcomingReturns as $booking) {
            $sent = $this->notificationService->notifyUpcomingReturn($booking);
            if ($sent) {
                $returnCount++;
                $this->line(" - [Trả xe] Đã gửi thông báo cho đơn #{$booking->ma_dat_xe}");
            } else {
                $skippedReturn++;
            }
        }

        $this->info("Hoàn tất quét nhắc lịch:");
        $this->info(" - Nhắc nhận xe: {$pickupCount} đã gửi, {$skippedPickup} bỏ qua (đã gửi trước đó).");
        $this->info(" - Nhắc trả xe: {$returnCount} đã gửi, {$skippedReturn} bỏ qua (đã gửi trước đó).");

        return Command::SUCCESS;
    }
}
