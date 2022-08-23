<?php

use App\Http\Controllers\Api\v1\TransactionController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi'
], function () {
    Route::get('/index', [TransactionController::class, 'index']);
    Route::get('/get-by-id', [TransactionController::class, 'getById']);
    Route::post('/store', [TransactionController::class, 'store']);
    Route::post('/destroy', [TransactionController::class, 'destroy']);
});
