<?php

use App\Http\Controllers\Api\v1\DetailTransactionController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'detail-transaksi'
], function () {
    Route::get('/index', [DetailTransactionController::class, 'index']);
    Route::post('/store', [DetailTransactionController::class, 'store']);
    Route::post('/destroy', [DetailTransactionController::class, 'destroy']);
});
