<?php

use App\Http\Controllers\Api\v1\PemesananController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'pemesanan'
], function () {
    Route::get('/get-draft', [PemesananController::class, 'getDraft']);
    Route::get('/get-perusahaan', [PemesananController::class, 'getPerusahaan']);
    Route::get('/get-produk', [PemesananController::class, 'getProduk']);
    Route::post('/simpan-produk', [PemesananController::class, 'simpanProduk']);
    Route::post('/hapus-produk', [PemesananController::class, 'hapusProduk']);
});
