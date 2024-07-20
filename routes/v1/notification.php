<?php

use App\Http\Controllers\Api\v1\NotificationController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'notification'
], function () {
    Route::get('/unread', [NotificationController::class, 'unread']);
    Route::post('/read', [NotificationController::class, 'read']);
});
