<?php

use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
    Route::get('setting/website', [SettingController::class, 'website']);
    Route::post('setting/website/update', [SettingController::class, 'websiteUpdate']);
    Route::get('setting/company', [SettingController::class, 'company']);
    Route::post('setting/company/update', [SettingController::class, 'companyUpdate']);
    Route::get('setting/notification', [SettingController::class, 'notification']);
    Route::post('setting/notification/update', [SettingController::class, 'notificationUpdate']);
    Route::get('setting/cron', [SettingController::class, 'cron']);
    Route::post('setting/cron/update', [SettingController::class, 'cronUpdate']);
});
