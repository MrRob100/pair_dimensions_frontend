<?php

use App\Http\Controllers\Api\BinanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/pair/lookup', function (Request $request) {
    $s1 = strtoupper($request->input('symbol1', ''));
    $s2 = strtoupper($request->input('symbol2', ''));
    $pair = \App\Models\Pair::where('symbol_1', $s1)->where('symbol_2', $s2)->first();
    if (!$pair) {
        return response()->json(['found' => false]);
    }
    $pairUse = \App\Models\PairUse::where('pair_id', $pair->id)->latest('id')->first();
    return response()->json([
        'found' => true,
        'pair_id' => $pair->id,
        'status' => $pairUse?->status,
    ]);
});
Route::get('/pair/holdings', function (Request $request) {
    $pairId = $request->input('pair_id');
    if (!$pairId) return response()->json(['holdings' => null]);
    $state = \App\Models\LiveState::where('pair_id', $pairId)->first();
    if (!$state) return response()->json(['holdings' => null]);
    return response()->json([
        'holdings' => [
            'coin1' => $state->holdings_coin1,
            'coin2' => $state->holdings_coin2,
            'usdt' => $state->holdings_usdt,
            'position' => $state->pair_position,
        ],
    ]);
});
Route::get('/binance/chart-data', [BinanceController::class, 'getChartData']);
Route::get('/binance/balances', [BinanceController::class, 'getBalances']);
Route::post('/binance/swap', [BinanceController::class, 'swap']);
Route::post('/binance/input', [BinanceController::class, 'addInput']);
Route::post('/binance/shave', [BinanceController::class, 'addShave']);
