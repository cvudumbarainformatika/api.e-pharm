<?php

use App\Http\Controllers\Api\v1\SettingController;
use Illuminate\Support\Facades\Route;

Route::group([
    // 'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'public'
], function () {
    Route::get('/info', [SettingController::class, 'publicInfo']);
});
