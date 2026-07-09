<?php

use App\Http\Controllers\AutoController;
use App\Models\CronSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Penjadwalan cron — waktu tiap task diatur user dari UI (admin/setting/cron),
// tersimpan di tabel cron_setting. Server cukup 1 baris crontab:
//   * * * * * php artisan schedule:run
// Dibungkus try/catch + hasTable agar aman saat DB belum siap (mis. saat migrate).
try {
    if (Schema::hasTable('cron_setting')) {
        foreach (CronSetting::where('enabled', 1)->get() as $cron) {
            Schedule::call(fn () => app()->call([app(AutoController::class), $cron->task]))
                ->dailyAt($cron->time)
                ->name('cron_'.$cron->task)
                ->withoutOverlapping();
        }
    }
} catch (Throwable $e) {
    // DB belum siap / tabel belum ada — abaikan agar artisan tetap jalan.
}
