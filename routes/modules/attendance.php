<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'level:admin,developer'])->prefix('admin')->group(function () {
    Route::get('karyawan', [AdminAttendanceController::class, 'employees']);
    Route::post('karyawan/add', [AdminAttendanceController::class, 'storeEmployee']);
    Route::post('karyawan/password/{id}', [AdminAttendanceController::class, 'resetPassword']);
    Route::post('karyawan/status/{id}', [AdminAttendanceController::class, 'toggleEmployee']);

    Route::get('absensi/rekap', [AdminAttendanceController::class, 'report']);
    Route::get('absensi/rekap/export', [AdminAttendanceController::class, 'exportCsv']);
    Route::post('absensi/rekap/update/{id}', [AdminAttendanceController::class, 'updateEntry']);
});
