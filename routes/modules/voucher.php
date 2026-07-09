<?php

use App\Http\Controllers\Server\VoucherController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('server')->group(function () {
    Route::get('voucher/dashboard', [VoucherController::class, 'home']);
    Route::get('voucher/report', [VoucherController::class, 'report']);
    Route::post('voucher/report/filter', [VoucherController::class, 'reportFilter']);
    Route::get('voucher/report/orders', [VoucherController::class, 'reportOrders']);
    Route::post('voucher/report/orders/filter', [VoucherController::class, 'reportOrdersFilter']);
    Route::get('voucher/users', [VoucherController::class, 'users']);
    Route::get('voucher/template/message', [VoucherController::class, 'templateMessage']);
    Route::post('voucher/update/template', [VoucherController::class, 'updateTemplateMessage']);
});
