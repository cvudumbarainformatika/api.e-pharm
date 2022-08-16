<?php

use App\Http\Controllers\Api\v1\SatuanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'satuan'
], function () {
    Route::get('/index', [SatuanController::class, 'index']);
    Route::post('/store', [SatuanController::class, 'store']);
    Route::post('/update', [SatuanController::class, 'update']);
    Route::post('/destroy', [SatuanController::class, 'destroy']);
});
