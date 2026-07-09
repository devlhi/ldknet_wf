<?php

use App\Http\Controllers\Admin\AccAssetController;
use App\Http\Controllers\Admin\AccExpenseController;
use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\AccProductController;
use App\Http\Controllers\Admin\AccPurchaseBillController;
use App\Http\Controllers\Admin\AccSalesInvoiceController;
use Illuminate\Support\Facades\Route;

/*
 * Modul Accounting (double-entry) — fitur baru, tabel prefix acc_.
 * Tidak menyentuh skema legacy CI4. Akses: admin, developer, finance.
 */
Route::middleware(['auth', 'level:admin,developer,finance'])->prefix('admin/accounting')->group(function () {
    Route::get('/', [AccountingController::class, 'index']);

    // Chart of Accounts
    Route::get('accounts', [AccountingController::class, 'accounts']);
    Route::post('accounts/store', [AccountingController::class, 'accountStore']);
    Route::post('accounts/update/{id}', [AccountingController::class, 'accountUpdate']);
    Route::get('accounts/delete/{id}', [AccountingController::class, 'accountDelete']);

    // Jurnal Umum
    Route::get('journals', [AccountingController::class, 'journals']);
    Route::get('journals/create', [AccountingController::class, 'journalCreate']);
    Route::post('journals/store', [AccountingController::class, 'journalStore']);
    Route::get('journals/detail/{id}', [AccountingController::class, 'journalShow']);
    Route::get('journals/edit/{id}', [AccountingController::class, 'journalEdit']);
    Route::post('journals/update/{id}', [AccountingController::class, 'journalUpdate']);
    Route::get('journals/delete/{id}', [AccountingController::class, 'journalDelete']);

    // Kontak
    Route::get('contacts', [AccountingController::class, 'contacts']);
    Route::post('contacts/store', [AccountingController::class, 'contactStore']);
    Route::post('contacts/update/{id}', [AccountingController::class, 'contactUpdate']);
    Route::get('contacts/delete/{id}', [AccountingController::class, 'contactDelete']);

    // Produk & Jasa
    Route::get('products', [AccProductController::class, 'index']);
    Route::post('products/store', [AccProductController::class, 'store']);
    Route::post('products/update/{id}', [AccProductController::class, 'update']);
    Route::get('products/delete/{id}', [AccProductController::class, 'delete']);

    // Faktur Penjualan
    Route::get('sales', [AccSalesInvoiceController::class, 'index']);
    Route::get('sales/create', [AccSalesInvoiceController::class, 'create']);
    Route::post('sales/store', [AccSalesInvoiceController::class, 'store']);
    Route::get('sales/detail/{id}', [AccSalesInvoiceController::class, 'show']);
    Route::get('sales/edit/{id}', [AccSalesInvoiceController::class, 'edit']);
    Route::post('sales/update/{id}', [AccSalesInvoiceController::class, 'update']);
    Route::get('sales/delete/{id}', [AccSalesInvoiceController::class, 'delete']);
    Route::post('sales/pay/{id}', [AccSalesInvoiceController::class, 'pay']);

    // Tagihan Pembelian
    Route::get('purchases', [AccPurchaseBillController::class, 'index']);
    Route::get('purchases/create', [AccPurchaseBillController::class, 'create']);
    Route::post('purchases/store', [AccPurchaseBillController::class, 'store']);
    Route::get('purchases/detail/{id}', [AccPurchaseBillController::class, 'show']);
    Route::get('purchases/edit/{id}', [AccPurchaseBillController::class, 'edit']);
    Route::post('purchases/update/{id}', [AccPurchaseBillController::class, 'update']);
    Route::get('purchases/delete/{id}', [AccPurchaseBillController::class, 'delete']);
    Route::post('purchases/pay/{id}', [AccPurchaseBillController::class, 'pay']);

    // Biaya / Pengeluaran
    Route::get('expenses', [AccExpenseController::class, 'index']);
    Route::post('expenses/store', [AccExpenseController::class, 'store']);
    Route::post('expenses/update/{id}', [AccExpenseController::class, 'update']);
    Route::get('expenses/delete/{id}', [AccExpenseController::class, 'delete']);

    // Aset Tetap
    Route::get('assets', [AccAssetController::class, 'index']);
    Route::post('assets/store', [AccAssetController::class, 'store']);
    Route::get('assets/detail/{id}', [AccAssetController::class, 'show']);
    Route::post('assets/update/{id}', [AccAssetController::class, 'update']);
    Route::get('assets/delete/{id}', [AccAssetController::class, 'delete']);
    Route::post('assets/depreciate/{id}', [AccAssetController::class, 'depreciate']);

    // Laporan
    Route::get('reports/ledger', [AccountingController::class, 'reportLedger']);
    Route::get('reports/trial-balance', [AccountingController::class, 'reportTrialBalance']);
    Route::get('reports/profit-loss', [AccountingController::class, 'reportProfitLoss']);
    Route::get('reports/balance-sheet', [AccountingController::class, 'reportBalanceSheet']);
    Route::get('reports/cash-flow', [AccountingController::class, 'reportCashFlow']);
});
