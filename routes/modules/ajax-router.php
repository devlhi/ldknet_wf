<?php

use App\Http\Controllers\AjaxController;
use App\Http\Controllers\Server\RouterController;
use Illuminate\Support\Facades\Route;

Route::post('ajax/cekcoupon/{any?}', [AjaxController::class, 'cekcoupon']);

Route::middleware(['auth', 'level:admin,developer,user'])->group(function () {
    Route::get('router/traffic/pppoe/{any}', [RouterController::class, 'getTrafficPPPOE']);
    Route::get('router/traffic/hotspot/{any}', [RouterController::class, 'getTrafficPPPOE']);
    Route::get('router/bandwith/pppoe/{any}', [RouterController::class, 'getBandwithPPPOE']);
    Route::get('router/traffic', [RouterController::class, 'getTraffic']);
    Route::post('router/traffic/fix', [RouterController::class, 'getTrafficFixes']);
});
