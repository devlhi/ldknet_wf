<?php

use App\Http\Controllers\Admin\BroadcastController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
    Route::get('cms/broadcast', [BroadcastController::class, 'broadcast']);
    Route::get('cms/broadcast/whatsapp', [BroadcastController::class, 'broadcastwhatsapp']);
    Route::post('cms/broadcast/email/send', [BroadcastController::class, 'sendbroadcast']);
    Route::post('cms/broadcast/whatsapp/send', [BroadcastController::class, 'sendbroadcastwhatsapp']);
    Route::post('cms/broadcast/sendmedia', [BroadcastController::class, 'sendbroadcastmedia']);
});
