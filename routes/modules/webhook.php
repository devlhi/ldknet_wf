<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhook/whatsapp', [WebhookController::class, 'whatsapp']);
Route::get('webhook/whatsapp/meta', [WebhookController::class, 'whatsappMetaVerify']);
Route::post('webhook/whatsapp/meta', [WebhookController::class, 'whatsappMeta']);
