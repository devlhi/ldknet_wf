<?php

use App\Http\Controllers\Admin\FinanceController;
use Illuminate\Support\Facades\Route;

/*
 * Modul Finance (area admin) — migrasi dari CI4 app/Config/Routes.php
 * group admin (AdminController::cashflows*, kas*, report*, invoice*, bayar, pay).
 * URL path sama persis dengan CI4.
 *
 * Level 'finance' disertakan: semua view finance di-port dari sisi admin dan
 * meng-hardcode URL admin/finance/* (form action, tab laporan, tombol delete).
 * Tanpa ini user level finance menekan endpoint ini -> 403. Semua route di grup
 * ini murni operasi keuangan sehingga aman untuk role finance.
 */
Route::middleware(['auth', 'level:admin,developer,finance'])->prefix('admin')->group(function () {
    // Cash flows category
    Route::get('finance/cash-flows/category', [FinanceController::class, 'cashflowsCategory']);
    Route::get('finance/cash-flows/category/delete/{any}', [FinanceController::class, 'cashflowsCategoryDelete']);
    Route::post('finance/cash-flows/category/add', [FinanceController::class, 'cashflowsCategoryAdd']);

    // Cash flows
    Route::get('finance/cash-flows', [FinanceController::class, 'cashflows']);
    Route::post('finance/cash-flows/add', [FinanceController::class, 'cashFlowsAdd']);

    // Kas
    Route::get('finance/kas', [FinanceController::class, 'kas']);
    Route::post('finance/kas/add', [FinanceController::class, 'kasAdd']);

    // Report
    Route::get('finance/report', [FinanceController::class, 'report']);
    Route::get('finance/report/cash-flows', [FinanceController::class, 'reportCashFlows']);
    Route::post('finance/report/filter', [FinanceController::class, 'reportFilter']);
    Route::post('finance/report/export', [FinanceController::class, 'exportExcelReport']);
    Route::post('finance/report/cash-flows/filter', [FinanceController::class, 'reportFilterCashFlows']);
    Route::post('finance/report/cash-flows/export', [FinanceController::class, 'exportExcelCashFlows']);
    Route::get('finance/report/customers', [FinanceController::class, 'reportCustomers']);
    Route::post('finance/report/customers/export', [FinanceController::class, 'exportExcelCustomers']);
    Route::post('finance/report/customers/filter', [FinanceController::class, 'reportFilterCustomers']);
    Route::get('finance/report/new/customers', [FinanceController::class, 'reportNewCustomers']);
    Route::post('finance/report/new/customers/filter', [FinanceController::class, 'reportFilterNewCustomers']);
    Route::post('finance/report/new/customers/export', [FinanceController::class, 'exportExcelNewCustomers']);

    // Invoice
    Route::get('finance/invoice', [FinanceController::class, 'invoice']);
    Route::get('finance/invoice/edit/{any}', [FinanceController::class, 'editInvoice']);
    Route::get('finance/invoice/status/{any}', [FinanceController::class, 'invoiceGatewayStatus'])->middleware('throttle:20,1');
    Route::post('finance/invoice/update', [FinanceController::class, 'invoiceUpdate']);
    Route::get('finance/invoice/detail/{any}', [FinanceController::class, 'detailInvoice']);
    Route::get('finance/invoice/generate', [FinanceController::class, 'generateInvoice']);
    Route::post('finance/invoice/generate/proses', [FinanceController::class, 'prosesGenerateInvoice']);
    Route::get('finance/invoice/bayar/{any}', [FinanceController::class, 'bayar']);
    Route::post('finance/invoice/pay', [FinanceController::class, 'pay']);
    Route::get('finance/invoice/print/{any}', [FinanceController::class, 'invoicePrint']);
    Route::post('finance/invoice/bulk-delete', [FinanceController::class, 'bulkDeleteInvoice']);
    Route::get('finance/invoice/delete/{any}', [FinanceController::class, 'deleteInvoice']);
});

/*
 * Endpoint AJAX global finance/* (di CI4 rute global tanpa prefix admin,
 * dipanggil oleh view cash-flows & invoice). Middleware level ditambah
 * 'finance' karena area finance memakai endpoint yang sama.
 */
Route::middleware(['auth', 'level:admin,developer,finance'])->group(function () {
    Route::post('finance/cash-flows/filter/getdata', [FinanceController::class, 'getDataFilterCashFlows']);
    Route::get('finance/cash-flows/filter/ambil-data/{bulan}/{tahun}', [FinanceController::class, 'ambilDataCashFlows']);

    Route::post('finance/report/filter/getdata', [FinanceController::class, 'getDataFilter']);
    Route::get('finance/report/filter/ambil-data-statistik/{bulan}/{tahun}', [FinanceController::class, 'ambilDataStatistik']);

    Route::post('finance/invoice/filter/getdata', [FinanceController::class, 'getDataFilterInvoice']);
    Route::get('finance/invoice/filter/ambil-data/{bulan}/{tahun}', [FinanceController::class, 'ambilDataInvoice']);

    // CI4: ajax/getcategory (AjaxController::getCategory) — dipakai halaman bayar invoice
    Route::post('ajax/getcategory', [FinanceController::class, 'getCategoryAjax']);
});
