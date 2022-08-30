<?php

use App\Http\Controllers\Api\v1\PenerimaanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'beban'
], function () {
    Route::get('/index', [PenerimaanController::class, 'index']);
    Route::post('/store', [PenerimaanController::class, 'store']);
    Route::post('/destroy', [PenerimaanController::class, 'destroy']);
});
