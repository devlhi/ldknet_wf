<?php

use App\Http\Controllers\AutoController;
use Illuminate\Support\Facades\Route;

// Endpoint task legacy — kini butuh token (turunan APP_KEY) agar tidak bisa
// dipicu sembarang orang (isolir massal / generate invoice / blast WA).
// Path dipertahankan; token di segmen terakhir (opsional agar route tetap
// terdaftar), tanpa token yang valid -> 403.
$cronGuard = function (string $method) {
    return function ($key = null) use ($method) {
        abort_unless(
            hash_equals(AutoController::cronToken(), (string) $key),
            403,
            'Invalid cron token'
        );

        return app(AutoController::class)->{$method}();
    };
};

Route::any('auto/updatestatus/{key?}', $cronGuard('updatestatus'));
Route::any('auto/isolir/{key?}', $cronGuard('isolir'));
Route::any('auto/cetakinv/{key?}', $cronGuard('cetakinv'));
Route::any('auto/reminder/{key?}', $cronGuard('reminderInvoice'));

// Endpoint cron untuk panel hosting yang hanya bisa hit URL (bukan crontab).
// Diproteksi token unik per-install. Menjalankan Laravel Scheduler.
Route::any('auto/cron/{key}', [AutoController::class, 'cron']);
