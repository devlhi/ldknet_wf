<?php

use App\Http\Controllers\Admin\GangguanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
    Route::get('gangguan', [GangguanController::class, 'index']);
    Route::get('gangguan/cetak', [GangguanController::class, 'cetak']);
    Route::post('gangguan/status/{id}', [GangguanController::class, 'updateStatus']);
    Route::get('gangguan/pengaturan', [GangguanController::class, 'settings']);
    Route::post('gangguan/pengaturan', [GangguanController::class, 'updateSettings']);
    Route::post('gangguan/broadcast-odp', [GangguanController::class, 'broadcastOdp']);
});
