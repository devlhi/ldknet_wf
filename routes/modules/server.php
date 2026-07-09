<?php

use App\Http\Controllers\Server\OltController;
use App\Http\Controllers\Server\RouterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('server')->group(function () {

    // Route untuk halaman dashboard olt
    Route::get('olt', [OltController::class, 'home']);
    Route::get('olt/settings', [OltController::class, 'settings']);
    Route::get('olt/bot/whatsapp', [OltController::class, 'botWhatsapp']);
    Route::post('olt/bot/whatsapp/add', [OltController::class, 'botWhatsappAdd']);
    Route::post('olt/bot/whatsapp/delete/{id}', [OltController::class, 'botWhatsappDelete']);

    Route::post('olt/add', [OltController::class, 'add']);
    Route::get('olt/edit/{id}', [OltController::class, 'editData']);
    Route::post('olt/update', [OltController::class, 'updateData']);
    Route::get('olt/delete/{id}', [OltController::class, 'deleteData']);
    Route::get('olt/onu/delete/port/{port}/onu/{onu}/mac/{mac}', [OltController::class, 'deleteOnu']);
    Route::get('olt/connect/{id}', [OltController::class, 'connect']);
    Route::get('olt/dashboard', [OltController::class, 'dashbard']);
    Route::get('olt/pon/{id}', [OltController::class, 'pon']);
    Route::match(['get', 'post'], 'olt/detail/port/{port}/onu/{onu}', [OltController::class, 'detail'])
        ->where(['port' => '[0-9]+', 'onu' => '[0-9]+']);
    Route::post('olt/change/name/port/{port}/onu/{onu}', [OltController::class, 'changename']);
    Route::get('olt/onu/reboot/port/{port}/onu/{onu}', [OltController::class, 'rebootOnu']);
    Route::get('olt/logout', [OltController::class, 'logout']);

    // Route untuk halaman dashboard router
    Route::get('router', [RouterController::class, 'home']);
    Route::post('router/add', [RouterController::class, 'add']);
    Route::get('router/delete/{id}', [RouterController::class, 'deleteData']);
    Route::post('router/update/{id}', [RouterController::class, 'update']);
    Route::get('router/connect/{id}', [RouterController::class, 'connect']);
    Route::get('router/logout', [RouterController::class, 'disconnect']);
    Route::get('router/dashboard', [RouterController::class, 'dashboard']);

    // Hotspot
    Route::get('router/hotspot/users', [RouterController::class, 'users']);
    Route::get('router/hotspot/user/add', [RouterController::class, 'addUser']);
    Route::post('router/hotspot/user/proses', [RouterController::class, 'prosesAddUser']);
    Route::get('router/hotspot/user/delete/{id}', [RouterController::class, 'deleteUser']);
    Route::get('router/hotspot/profile', [RouterController::class, 'profile']);
    Route::post('router/hotspot/profile/add', [RouterController::class, 'prosesAddProfile']);
    Route::get('router/hotspot/active', [RouterController::class, 'hotspotActive']);
    Route::get('router/hotspot/log', [RouterController::class, 'hotspotLog']);
    Route::get('router/hotspot/host', [RouterController::class, 'hotspotHost']);

    // PPP
    Route::get('router/ppp/profile', [RouterController::class, 'pppProfile']);
    Route::get('router/ppp/secret', [RouterController::class, 'pppSecret']);
    Route::get('router/ppp/active', [RouterController::class, 'pppActive']);
    Route::get('router/setting', [RouterController::class, 'users']);
});
