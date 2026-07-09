<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\FinanceController;
use Illuminate\Support\Facades\Route;

/*
 * Modul Finance area (/finance/*) — URL disamakan dengan CI4.
 * Logic memakai FinanceController admin yang sudah di-port 1:1 dari CI4 finance/admin.
 */
Route::middleware(['auth', 'level:finance'])->prefix('finance')->group(function () {
    Route::get('/', [AdminController::class, 'index']);
    Route::get('dashboard', [AdminController::class, 'index']);
    Route::get('dashboard/transactions', [AdminController::class, 'transactions']);
    Route::get('dashboard/mikrotik', [AdminController::class, 'mikrotikStats']);

    Route::get('cash-flows/category', [FinanceController::class, 'cashflowsCategory']);
    Route::post('cash-flows/category/add', [FinanceController::class, 'cashflowsCategoryAdd']);
    Route::get('cash-flows', [FinanceController::class, 'cashflows']);
    Route::post('cash-flows/add', [FinanceController::class, 'cashFlowsAdd']);

    Route::get('kas', [FinanceController::class, 'kas']);
    Route::post('kas/add', [FinanceController::class, 'kasAdd']);

    Route::get('report', [FinanceController::class, 'report']);
    Route::post('report/filter', [FinanceController::class, 'reportFilter']);
    Route::post('report/export', [FinanceController::class, 'exportExcelReport']);
    Route::get('report/cash-flows', [FinanceController::class, 'reportCashFlows']);
    Route::post('report/cash-flows/filter', [FinanceController::class, 'reportFilterCashFlows']);
    Route::post('report/cash-flows/export', [FinanceController::class, 'exportExcelCashFlows']);

    Route::get('invoice', [FinanceController::class, 'invoice']);
    Route::get('invoice/edit/{any}', [FinanceController::class, 'editInvoice']);
    Route::post('invoice/update', [FinanceController::class, 'invoiceUpdate']);
    Route::get('invoice/generate', [FinanceController::class, 'generateInvoice']);
    Route::post('invoice/generate/proses', [FinanceController::class, 'prosesGenerateInvoice']);
    Route::get('invoice/detail/{any}', [FinanceController::class, 'detailInvoice']);

    Route::get('account', [AccountController::class, 'account']);
    Route::post('account/changepassword', [AccountController::class, 'changepassword']);
});
