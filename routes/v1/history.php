<?php

use App\Http\Controllers\Api\v1\HistoryController;
use App\Http\Controllers\Api\v1\TransactionController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'history'
], function () {
    Route::get('/index', [HistoryController::class, 'index']);
    Route::get('/nama-status', [HistoryController::class, 'namaStatus']);
    Route::get('/cari', [TransactionController::class, 'search']);
    Route::get('/nama', [HistoryController::class, 'nama']);
});
