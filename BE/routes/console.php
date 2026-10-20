<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('bookings:send-reminders')->hourly();


// Private generated trials expire; approved public covers are not removed here.
\Illuminate\Support\Facades\Artisan::command('marketing:clean-image-trials', function () {
    $disk = \Illuminate\Support\Facades\Storage::disk('local');
    foreach ($disk->files('pollinations-previews') as $path) {
        if ($disk->lastModified($path) < now()->subDays(2)->timestamp) {
            $disk->delete($path);
        }
    }
    $this->info('Expired private image trials cleaned.');
});
\Illuminate\Support\Facades\Schedule::command('marketing:clean-image-trials')->daily()->withoutOverlapping();
