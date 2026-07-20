<?php

use App\Http\Controllers\Admin\CoverageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
    Route::get('coverage/get/coordinat', [CoverageController::class, 'getCoordinat']);
    Route::get('coverage/odc', [CoverageController::class, 'odc']);
    Route::post('coverage/odc/add', [CoverageController::class, 'addOdc']);
    Route::post('coverage/odc/update/{any}', [CoverageController::class, 'updateOdc']);
    Route::post('coverage/odc/delete/{any}', [CoverageController::class, 'deleteOdc']);
    Route::get('coverage/area', [CoverageController::class, 'area']);
    Route::post('coverage/area/add', [CoverageController::class, 'areaAdd']);
    Route::post('coverage/area/update/{any}', [CoverageController::class, 'updateArea']);
    Route::post('coverage/area/delete/{any}', [CoverageController::class, 'deleteArea']);
    Route::get('coverage/odp', [CoverageController::class, 'odp']);
    Route::post('coverage/odp/add', [CoverageController::class, 'addodp']);
    Route::post('coverage/odp/update/{any}', [CoverageController::class, 'updateODP']);
    Route::post('coverage/odp/delete/{any}', [CoverageController::class, 'deleteODP']);
    Route::get('coverage/customer', [CoverageController::class, 'getCustomerMap']);
    Route::get('coverage/rxpower', [CoverageController::class, 'rxpower']);
    Route::get('coverage/peta', [CoverageController::class, 'peta']);
    Route::get('coverage/peta/pengaturan', [CoverageController::class, 'petaSettings']);
    Route::post('coverage/peta/pengaturan', [CoverageController::class, 'petaSettingsUpdate']);
    Route::post('coverage/peta/cable', [CoverageController::class, 'storeCable']);
});
