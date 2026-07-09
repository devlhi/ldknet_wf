<?php

use App\Http\Controllers\Karyawan\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:technician'])->prefix('karyawan')->group(function () {
    Route::get('/', [AttendanceController::class, 'index']);
    Route::get('absensi', [AttendanceController::class, 'index']);
    Route::post('absensi/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('absensi/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('absensi/history', [AttendanceController::class, 'history']);
});
