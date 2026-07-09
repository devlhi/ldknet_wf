<?php

use App\Http\Controllers\Server\AcsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('server')->group(function () {
    Route::get('acs', [AcsController::class, 'home']);
    Route::post('acs/add', [AcsController::class, 'add']);
    Route::post('acs/delete/{id}', [AcsController::class, 'deleteData']);
    Route::get('acs/connect/{id}', [AcsController::class, 'connect']);
    Route::get('acs/connect', [AcsController::class, 'connect']);
    Route::post('acs/device/edit', [AcsController::class, 'edit']);
    Route::post('acs/device/change/ssid', [AcsController::class, 'changeSsid']);
    Route::post('acs/device/change/password', [AcsController::class, 'changePassword']);
    Route::post('acs/device/refresh', [AcsController::class, 'refresh']);
    Route::post('acs/update/{id}', [AcsController::class, 'update']);
    Route::get('acs/dashboard', [AcsController::class, 'dashboard']);
});
