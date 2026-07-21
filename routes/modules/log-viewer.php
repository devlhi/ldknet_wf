<?php

use App\Http\Controllers\Admin\LogViewerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
    Route::get('logs', [LogViewerController::class, 'index']);
    Route::post('logs/clear', [LogViewerController::class, 'clear'])->middleware('throttle:5,1');
});
