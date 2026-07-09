<?php

use App\Http\Controllers\Admin\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
    Route::get('customers', [CustomerController::class, 'customer']);
    Route::get('customer/data', [CustomerController::class, 'customerData']);
    Route::get('customer/filter', [CustomerController::class, 'ambilDataFilterCustomer']);
    Route::get('customer/add', [CustomerController::class, 'customerService']);
    Route::post('customer/add/proses', [CustomerController::class, 'customerProccessAdd']);
    Route::get('customer/add/{any}', [CustomerController::class, 'customerAdd']);
    Route::get('customer/export', [CustomerController::class, 'exportCSV']);
    Route::get('customer/exportexcel', [CustomerController::class, 'exportExcel']);
    Route::get('customer/detail/{any}', [CustomerController::class, 'customer_detail']);
    Route::get('customer/edit/{any}', [CustomerController::class, 'customer_edit']);
    Route::get('customers/edit/{any}', [CustomerController::class, 'customer_edit']);
    Route::post('customer/update', [CustomerController::class, 'customerUpdateData']);
    Route::post('customer/update/pppoe', [CustomerController::class, 'customerUpdatePPPOE']);
    Route::post('customer/update/odp', [CustomerController::class, 'customerUpdateODP']);
    Route::post('customer/update/tikor', [CustomerController::class, 'customerUpdateTikor']);
    Route::post('customer/update/bio', [CustomerController::class, 'customerUpdateBio']);
    Route::post('customer/update/{any}', [CustomerController::class, 'customerUpdateData']);
    Route::post('customers/update/{any}', [CustomerController::class, 'customerUpdateData']);
    Route::post('customer/gantipass/{any}', [CustomerController::class, 'customerUpdatePassword']);
    Route::get('customer/delete/{any}', [CustomerController::class, 'customer_delete']);
    Route::post('getPPPOEUsers', [CustomerController::class, 'getPPPOEUsers']);

    Route::get('export', [CustomerController::class, 'export']);
});

// Endpoint AJAX untuk dropdown port ODP (CI4: /get-ports & /get-used-ports & /get-odp-by-area, tanpa prefix admin)
Route::middleware(['auth', 'level:admin,developer'])->group(function () {
    Route::post('get-ports', [CustomerController::class, 'getPorts']);
    Route::get('get-used-ports', [CustomerController::class, 'getUsedPorts']);
    Route::post('get-odp-by-area', [CustomerController::class, 'getODPByArea']);
});
