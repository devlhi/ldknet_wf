<?php

use App\Http\Controllers\GuestController;
use Illuminate\Support\Facades\Route;

Route::get('tagihan', [GuestController::class, 'tagihanHome']);
Route::post('tagihan/check', [GuestController::class, 'tagihanCheck']);
Route::get('tagihan/{any}', [GuestController::class, 'tagihanDetail']);
Route::post('tagihan/process', [GuestController::class, 'tagihanProcess']);

Route::get('invoice/{any}', [GuestController::class, 'invoiceView']);
Route::get('cek', [GuestController::class, 'cek']);
Route::post('cek/proses', [GuestController::class, 'cekProses']);
