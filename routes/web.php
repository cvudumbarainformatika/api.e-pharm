<?php

use App\Http\Controllers\AutogeneratorController;
use App\Http\Controllers\PrintController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::view("login", "login");
Route::view("print.penjualan", "print");
Route::get('/autogen', [AutogeneratorController::class, 'index']);
Route::get('/coba', [AutogeneratorController::class, 'coba']);
Route::get('/cari', [AutogeneratorController::class, 'cari']);
Route::get('/print', [PrintController::class, 'print']);
