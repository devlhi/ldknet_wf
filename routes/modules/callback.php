<?php

use App\Http\Controllers\CallbackController;
use Illuminate\Support\Facades\Route;

Route::post('callback/paydisini', [CallbackController::class, 'callbackPaydisini']);
Route::post('callback/tripay', [CallbackController::class, 'tripayCallback']);
Route::post('callback/duitku', [CallbackController::class, 'duitkuCallback']);
Route::post('callback/testing', [CallbackController::class, 'paydisiniTesting']);
