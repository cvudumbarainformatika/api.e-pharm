<?php

use App\Http\Controllers\Api\v1\TransactionController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi'
], function () {
    Route::get('/index', [TransactionController::class, 'index']);
    Route::get('/with-detail', [TransactionController::class, 'withDetail']);
    Route::get('/with-beban', [TransactionController::class, 'withBeban']);
    Route::get('/with-penerimaan', [TransactionController::class, 'withPenerimaan']);
    Route::get('/history', [TransactionController::class, 'history']);
    Route::get('/expired', [TransactionController::class, 'getExpired']);
    Route::get('/get-by-date', [TransactionController::class, 'getByDate']);
    Route::get('/destroy-draft', [TransactionController::class, 'destroyDraft']);
    Route::post('/store', [TransactionController::class, 'store']);
    Route::post('/destroy', [TransactionController::class, 'destroy']);
});
