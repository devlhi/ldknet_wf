<?php

use App\Http\Controllers\Admin\ServicesController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
    Route::get('services', [ServicesController::class, 'services']);
    Route::post('services/add', [ServicesController::class, 'addService']);
    Route::post('services/update', [ServicesController::class, 'service_update']);
    Route::get('services/edit/{any}', [ServicesController::class, 'services_edit']);
    Route::post('services/delete/{any}', [ServicesController::class, 'servicesDelete']);
    Route::post('services/sync/get_profiles', [ServicesController::class, 'getProfileRouter']);
    Route::post('services/sync/update', [ServicesController::class, 'serviceSyncUpdate']);
    Route::get('services/sync/{any}', [ServicesController::class, 'serviceSync']);
});
