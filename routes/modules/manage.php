<?php

use App\Http\Controllers\Admin\ManageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
    Route::get('manage/user', [ManageController::class, 'users']);
    Route::post('manage/user/delete/{id}', [ManageController::class, 'deleteUser']);
    Route::post('manage/user/add', [ManageController::class, 'usersAdd']);
    Route::get('manage/user/edit/{id}', [ManageController::class, 'editUsers']);
    Route::post('manage/user/update/{id}', [ManageController::class, 'updateUsers']);

    Route::get('manage/coupon', [ManageController::class, 'coupon']);
    Route::post('manage/coupon/delete/{id}', [ManageController::class, 'deleteCoupon']);
    Route::get('manage/coupon/update/{id}', [ManageController::class, 'updateCoupon']);
    Route::post('manage/coupon/add', [ManageController::class, 'addCoupon']);
});
