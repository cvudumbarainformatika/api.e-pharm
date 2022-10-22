<?php

use App\Http\Controllers\Api\v1\HutangController;
use App\Http\Controllers\Api\v1\LaporanController;
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
Route::get('/retur', [AutogeneratorController::class, 'retur']);
Route::get('/print', [PrintController::class, 'print']);
Route::get('/hutang', [HutangController::class, 'bayar']);
Route::get('/time', function () {
    echo time();
});
Route::get('/get-stok', [LaporanController::class, 'ambilStok']);
