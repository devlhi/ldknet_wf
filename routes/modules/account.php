<?php

use App\Http\Controllers\Admin\AccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
    Route::get('account', [AccountController::class, 'account']);
    Route::post('account/changepassword', [AccountController::class, 'changepassword']);
});
