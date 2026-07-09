<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'index']);

// Authentication
Route::prefix('auth')->group(function () {
    Route::get('login', [AuthController::class, 'index'])->name('login');
    Route::post('auth', [AuthController::class, 'auth']);
    Route::get('logout', [AuthController::class, 'logout']);
    Route::get('forgot', [AuthController::class, 'forgot']);
    Route::post('sendforgot', [AuthController::class, 'sendforgot']);
    Route::get('reset-password/{token}', [AuthController::class, 'resetpassword']);
    Route::post('reset-password/{token}/update', [AuthController::class, 'updateResetPassword']);
});

// Admin
Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'index']);
    Route::get('dashboard', [AdminController::class, 'index']);
    Route::get('dashboard/transactions', [AdminController::class, 'transactions']);
});

// Route per modul admin/server (hasil migrasi bertahap dari CI4)
foreach (glob(__DIR__.'/modules/*.php') as $moduleRoutes) {
    require $moduleRoutes;
}
