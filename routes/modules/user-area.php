<?php

use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:user'])->prefix('user')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('dashboard', [UserController::class, 'index']);
    Route::get('service', [UserController::class, 'service']);
    Route::get('invoice', [UserController::class, 'invoice']);
    Route::get('invoice/detail/{any}', [UserController::class, 'invoiceDetail']);
    Route::get('invoice/payment/{any}', [UserController::class, 'invoicePayment']);
    Route::post('invoice/pembayaran', [UserController::class, 'invoicePembayaran']);
    Route::get('invoice/cek/{any}', [UserController::class, 'invoiceCek']);
    Route::get('invoice/cekbank/{any}', [UserController::class, 'invoiceCekBank']);
    Route::get('invoice/history', [UserController::class, 'invoiceHistory']);
    Route::get('invoice/print/{any}', [UserController::class, 'invoicePrint']);
    Route::get('account', [UserController::class, 'account']);
    Route::post('account/changepassword', [UserController::class, 'changepassword']);
});
