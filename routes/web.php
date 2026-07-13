<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

// Landing page publik ANNORTY NET; pengguna login diarahkan ke dashboard-nya.
Route::get('/', [LandingController::class, 'index']);

// Authentication
Route::prefix('auth')->group(function () {
    Route::get('login', [AuthController::class, 'index'])->name('login');
    // Rate-limit: cegah brute-force password (per IP).
    Route::post('auth', [AuthController::class, 'auth'])->middleware('throttle:10,1');
    Route::get('logout', [AuthController::class, 'logout']);
    Route::get('forgot', [AuthController::class, 'forgot']);
    // Rate-limit: cegah spam email reset password.
    Route::post('sendforgot', [AuthController::class, 'sendforgot'])->middleware('throttle:5,10');
    Route::get('reset-password/{token}', [AuthController::class, 'resetpassword']);
    // Rate-limit: cegah brute-force token reset.
    Route::post('reset-password/{token}/update', [AuthController::class, 'updateResetPassword'])->middleware('throttle:10,10');
});

// Admin
Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index']);
    Route::get('dashboard', [AdminController::class, 'index']);
    Route::get('dashboard/transactions', [AdminController::class, 'transactions']);
    Route::get('dashboard/mikrotik', [AdminController::class, 'mikrotikStats']);
});

// Route per modul admin/server (hasil migrasi bertahap dari CI4)
foreach (glob(__DIR__.'/modules/*.php') as $moduleRoutes) {
    require $moduleRoutes;
}
