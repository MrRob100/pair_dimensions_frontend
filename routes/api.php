<?php

use App\Http\Controllers\Api\BinanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/binance/chart-data', [BinanceController::class, 'getChartData']);
