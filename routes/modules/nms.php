<?php

use App\Http\Controllers\Admin\NmsController;
use Illuminate\Support\Facades\Route;

// Public monitor - no session, signed URL only
Route::get('/nms/monitor', [NmsController::class, 'publicMonitor'])
    ->name('nms.public.monitor')
    ->middleware('signed');

Route::get('/nms/monitor/data/map', [NmsController::class, 'publicMapData']);
Route::get('/nms/monitor/data/status/{id}', [NmsController::class, 'publicDeviceStatus']);

Route::middleware(['auth', 'level:admin,developer'])->prefix('admin/nms')->group(function () {
    Route::get('/', [NmsController::class, 'index']);
    Route::get('/sla', [NmsController::class, 'slaReport']);
    Route::get('/sla/settings/global', [NmsController::class, 'slaSettingsGlobalForm']);
    Route::post('/sla/settings/global', [NmsController::class, 'slaSettingsGlobalStore']);
    Route::get('/sla/settings/{id}', [NmsController::class, 'slaSettingsForm']);
    Route::post('/sla/settings/{id}', [NmsController::class, 'slaSettingsStore']);
    Route::get('/map-data', [NmsController::class, 'mapData']);
    Route::get('/device/add', [NmsController::class, 'deviceAddForm']);
    Route::post('/device/add', [NmsController::class, 'deviceStore']);
    Route::get('/device/edit/{id}', [NmsController::class, 'deviceEditForm']);
    Route::post('/device/update/{id}', [NmsController::class, 'deviceUpdate']);
    Route::get('/device/delete/{id}', [NmsController::class, 'deviceDelete']);
    Route::get('/device/detail/{id}', [NmsController::class, 'deviceDetail']);
    Route::get('/device/poll/{id}', [NmsController::class, 'poll']);
    Route::get('/device/status/{id}', [NmsController::class, 'checkStatus']);
    Route::get('/device/metrics/{id}/{port}', [NmsController::class, 'metricsHistory']);
    Route::post('/link/add', [NmsController::class, 'linkStore']);
    Route::get('/link/delete/{id}', [NmsController::class, 'linkDelete']);
});
