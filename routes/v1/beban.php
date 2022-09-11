<?php

use App\Http\Controllers\Api\v1\BebanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'beban'
], function () {
    Route::get('/index', [BebanController::class, 'index']);
    Route::get('/beban', [BebanController::class, 'beban']);
    Route::post('/store', [BebanController::class, 'store']);
    Route::post('/destroy', [BebanController::class, 'destroy']);
});
