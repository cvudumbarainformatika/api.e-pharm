<?php

use App\Http\Controllers\Api\v1\LaporanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'laporan'
], function () {
    Route::get('/get-hutang-supplier', [LaporanController::class, 'getHutangSupplier']);
    Route::get('/get-piutang-customer', [LaporanController::class, 'getPiutangCustomer']);
    Route::get('/total-by-date', [LaporanController::class, 'getTotalByDate']);
    Route::get('/get-by-date', [LaporanController::class, 'getByDate']);
    // Route::get('/get-by-date', [LaporanController::class, 'getByDate']);
});
