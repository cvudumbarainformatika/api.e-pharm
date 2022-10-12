<?php

use App\Http\Controllers\Api\v1\SettingController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'setting'
], function () {
    Route::get('/info', [SettingController::class, 'getInfo']);
    Route::post('/store-info', [SettingController::class, 'storeInfo']);
    Route::get('/menu', [SettingController::class, 'getMenu']);
    Route::post('/store-menu', [SettingController::class, 'storeMenu']);
    // Route::post('/destroy', [SettingController::class, 'destroy']);
});
