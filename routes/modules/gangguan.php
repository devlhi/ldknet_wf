<?php

use App\Http\Controllers\Admin\GangguanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
    Route::get('gangguan', [GangguanController::class, 'index']);
    Route::post('gangguan/status/{id}', [GangguanController::class, 'updateStatus']);
});
