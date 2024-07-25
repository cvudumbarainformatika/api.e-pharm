<?php

use App\Http\Controllers\Api\v1\LaporanBaruController;
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
    Route::get('/total-retur-by-date', [LaporanController::class, 'getTotalReturByDate']);
    Route::get('/get-by-date', [LaporanController::class, 'getByDate']);
    Route::get('/get-stok', [LaporanBaruController::class, 'ambilStok']);
    Route::get('/more-stok', [LaporanController::class, 'moreStok']);
    Route::get('/cari', [LaporanController::class, 'cari']);
    Route::get('/stok-transaction', [LaporanBaruController::class, 'stokTransaction']);
    Route::get('/all-stok', [LaporanController::class, 'allStok']);
    // Route::get('/get-laporan-keuangan', [LaporanBaruController::class, 'laporanKeuangan']);
    Route::get('/new-get-laporan-keuangan', [LaporanBaruController::class, 'newLaporanKeuangan']);
    Route::get('/single-product', [LaporanBaruController::class, 'singleProduct']);
});
Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'laporan/baru'
], function () {
    Route::get('/single-product', [LaporanBaruController::class, 'singleProduct']);
});
