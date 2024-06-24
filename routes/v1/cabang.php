<?php

use App\Http\Controllers\Api\v1\CabangController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'cabang'
], function () {
    Route::get('/index', [CabangController::class, 'index']);
    Route::get('/all', [CabangController::class, 'allCabang']);
    Route::post('/store', [CabangController::class, 'store']);
    Route::post('/destroy', [CabangController::class, 'destroy']);
});
