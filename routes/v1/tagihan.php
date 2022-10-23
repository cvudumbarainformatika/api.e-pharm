<?php

use App\Http\Controllers\Api\v1\TagihanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'tagihan'
], function () {
    Route::get('/piutang', [TagihanController::class, 'piutang']);
    Route::get('/transaksi-terbayar', [TagihanController::class, 'transaksiTerbayar']);
    Route::get('/tagihan-terbayar', [TagihanController::class, 'tagihanTerbayar']);
    Route::get('/tagihan', [TagihanController::class, 'tagihan']);
    Route::post('/store', [TagihanController::class, 'store']);
    Route::post('/dibayar', [TagihanController::class, 'dibayar']);
});
