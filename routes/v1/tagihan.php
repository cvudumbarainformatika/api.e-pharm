<?php

use App\Http\Controllers\Api\v1\TagihanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'tagihan'
], function () {
    Route::get('/piutang', [TagihanController::class, 'piutang']);
    Route::get('/transaksi-tertagih', [TagihanController::class, 'transaksiTertagih']);
    Route::get('/tagihan', [TagihanController::class, 'tagihan']);
    Route::post('/store', [TagihanController::class, 'store']);
});
