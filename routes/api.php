<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\AuthController as Auth;
use App\Http\Controllers\api\OrderController as Order;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post("login", [Auth::class, 'login'])->name('login');

Route::group(['middleware' => ['auth:sanctum']], function() {
    Route::post("logout", [Auth::class, 'logout'])->name('logout');

    Route::post('/order/new', [Order::class, 'create']);
    Route::get('/order/{sip_no}', [Order::class, 'detail']);
});
