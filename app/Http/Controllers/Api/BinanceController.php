<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveState;
use App\Models\LiveTrade;
use App\Models\Pair;
use App\Models\PairInput;
use App\Services\BinanceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BinanceController extends Controller
{
    public function getBalances(Request $request, BinanceService $binance): JsonResponse
    {
        $symbols = $request->input('symbols', []);

        if (empty($symbols)) {
            return response()->json(['balances' => []]);
        }

        try {
            $account = $binance->fetchBalance();

            if (isset($account['code'])) {
                return response()->json(['error' => $account['msg'] ?? 'API error'], 500);
            }

            $requested = array_map('strtoupper', $symbols);
            $balances = [];

            foreach ($account['balances'] as $b) {
                if (in_array($b['asset'], $requested)) {
                    $balances[$b['asset']] = [
                        'free' => $b['free'],
                        'locked' => $b['locked'],
                    ];
                }
            }

            // Fetch USD prices from live Binance API
            $prices = [];
            $stablecoins = ['USDT', 'USDC', 'TUSD', 'BUSD', 'FDUSD', 'DAI'];
            foreach ($requested as $symbol) {
                if (in_array($symbol, $stablecoins)) {
                    $prices[$symbol] = 1.0;
                } else {
                    $prices[$symbol] = $this->fetchUsdPrice($symbol);
                }
            }

            return response()->json(['balances' => $balances, 'prices' => $prices]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getChartData(Request $request): JsonResponse
    {
        $symbol1 = strtoupper($request->input('symbol1'));
        $symbol2 = strtoupper($request->input('symbol2'));

        if (!$symbol1 || !$symbol2) {
            return response()->json(['error' => 'Both symbol1 and symbol2 are required'], 400);
        }

        try {
            // Calculate 6 months ago timestamp
            $sixMonthsAgo = now()->subMonths(6);
            $startTime = $sixMonthsAgo->timestamp * 1000;

            // Fetch data for all three pairs
            $symbol1UsdtData = $this->fetchBinanceKlines("{$symbol1}USDT", $startTime);
            $symbol2UsdtData = $this->fetchBinanceKlines("{$symbol2}USDT", $startTime);
            
            // Calculate cross-pair data (symbol1/symbol2)
            $crossPairData = $this->calculateCrossPair($symbol1UsdtData, $symbol2UsdtData);

            // Fetch trade lines for this pair
            $tradeLines = $this->getTradeLines($symbol1, $symbol2);

            return response()->json([
                'success' => true,
                'data' => [
                    'symbol1_usdt' => [
                        'symbol' => "{$symbol1}USDT",
                        'data' => $symbol1UsdtData
                    ],
                    'cross_pair' => [
                        'symbol' => "{$symbol1}{$symbol2}",
                        'data' => $crossPairData,
                        'trade_lines' => $tradeLines
                    ],
                    'symbol2_usdt' => [
                        'symbol' => "{$symbol2}USDT",
                        'data' => $symbol2UsdtData
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch chart data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function addInput(Request $request, BinanceService $binance): JsonResponse
    {
        $pairId = $request->input('pair_id');
        $symbol = strtoupper($request->input('symbol'));
        $amount = floatval($request->input('amount'));

        if (!$pairId || !$symbol || $amount <= 0) {
            return response()->json(['error' => 'pair_id, symbol, and positive amount are required'], 400);
        }

        $pair = Pair::find($pairId);
        if (!$pair) {
            return response()->json(['error' => 'Pair not found'], 404);
        }

        $validSymbols = [strtoupper($pair->symbol_1), strtoupper($pair->symbol_2)];
        if (!in_array($symbol, $validSymbols)) {
            return response()->json(['error' => "Symbol must be one of: " . implode(', ', $validSymbols)], 400);
        }

        // The amount is in USDT — buy $amount worth of the coin
        $price = $this->fetchUsdPrice($symbol);
        if (!$price) {
            return response()->json(['error' => "Could not fetch price for {$symbol}"], 500);
        }

        $buyQuantity = $amount / $price;
        $buyQuantity = $this->adjustQuantity("{$symbol}USDT", $buyQuantity, $binance);

        $buyResult = $binance->createOrder("{$symbol}USDT", 'MARKET', 'BUY', $buyQuantity);
        if (isset($buyResult['code'])) {
            return response()->json(['error' => "Buy failed: " . ($buyResult['msg'] ?? 'Unknown error')], 500);
        }

        $filledQty = floatval($buyResult['executedQty']);
        $fillPrice = floatval($buyResult['fills'][0]['price'] ?? $price);
        $usdtSpent = 0;
        foreach ($buyResult['fills'] ?? [] as $fill) {
            $usdtSpent += floatval($fill['qty']) * floatval($fill['price']);
        }

        $input = PairInput::create([
            'pair_id' => $pair->id,
            'type' => 'input',
            'symbol' => $symbol,
            'amount' => $filledQty,
            'amount_usd' => $usdtSpent ?: ($filledQty * $fillPrice),
            'price' => $fillPrice,
        ]);

        $liveState = LiveState::where('pair_id', $pair->id)->first();

        return response()->json([
            'success' => true,
            'input' => $input,
            'order' => $buyResult,
            'holdings' => [
                'coin1' => $liveState->holdings_coin1,
                'coin2' => $liveState->holdings_coin2,
            ],
        ]);
    }

    public function addShave(Request $request, BinanceService $binance): JsonResponse
    {
        $pairId = $request->input('pair_id');
        $symbol = strtoupper($request->input('symbol'));
        $amount = floatval($request->input('amount'));

        if (!$pairId || !$symbol || $amount <= 0) {
            return response()->json(['error' => 'pair_id, symbol, and positive amount are required'], 400);
        }

        $pair = Pair::find($pairId);
        if (!$pair) {
            return response()->json(['error' => 'Pair not found'], 404);
        }

        // Check there's enough allocated
        $liveState = LiveState::where('pair_id', $pair->id)->first();
        if (!$liveState) {
            return response()->json(['error' => 'No allocation for this pair'], 400);
        }

        $symbol1 = strtoupper($pair->symbol_1);
        $available = ($symbol === $symbol1) ? $liveState->holdings_coin1 : $liveState->holdings_coin2;

        if ($amount > $available) {
            return response()->json(['error' => "Cannot shave {$amount} — only {$available} allocated"], 400);
        }

        // Sell the coin for USDT
        $sellQuantity = $this->adjustQuantity("{$symbol}USDT", $amount, $binance);

        $sellResult = $binance->createOrder("{$symbol}USDT", 'MARKET', 'SELL', $sellQuantity);
        if (isset($sellResult['code'])) {
            return response()->json(['error' => "Sell failed: " . ($sellResult['msg'] ?? 'Unknown error')], 500);
        }

        $filledQty = floatval($sellResult['executedQty']);
        $fillPrice = floatval($sellResult['fills'][0]['price'] ?? 0);
        $usdtReceived = 0;
        foreach ($sellResult['fills'] ?? [] as $fill) {
            $usdtReceived += floatval($fill['qty']) * floatval($fill['price']);
        }

        $shave = PairInput::create([
            'pair_id' => $pair->id,
            'type' => 'shave',
            'symbol' => $symbol,
            'amount' => $filledQty,
            'amount_usd' => $usdtReceived ?: ($filledQty * $fillPrice),
            'price' => $fillPrice,
        ]);

        $liveState->refresh();

        return response()->json([
            'success' => true,
            'shave' => $shave,
            'order' => $sellResult,
            'holdings' => [
                'coin1' => $liveState->holdings_coin1,
                'coin2' => $liveState->holdings_coin2,
            ],
        ]);
    }

    public function swap(Request $request, BinanceService $binance): JsonResponse
    {
        $from = strtoupper($request->input('from'));
        $to = strtoupper($request->input('to'));
        $pairId = $request->input('pair_id');

        if (!$from || !$to) {
            return response()->json(['error' => 'Both "from" and "to" are required'], 400);
        }

        try {
            // Look up the pair to determine direction
            $pair = $pairId ? Pair::find($pairId) : null;

            if (!$pair) {
                return response()->json(['error' => 'pair_id is required'], 400);
            }

            $symbol1 = strtoupper($pair->symbol_1);
            $isFromSymbol1 = $from === $symbol1;

            // Get the pair's allocated balance from live_state
            $liveState = LiveState::where('pair_id', $pair->id)->first();

            if (!$liveState) {
                return response()->json(['error' => 'No allocation for this pair. Create an input first.'], 400);
            }

            $fromBalance = $isFromSymbol1 ? $liveState->holdings_coin1 : $liveState->holdings_coin2;

            if ($fromBalance <= 0) {
                return response()->json(['error' => "No {$from} allocated to this pair"], 400);
            }

            // Adjust for lot size before selling
            $fromBalance = $this->adjustQuantity("{$from}USDT", $fromBalance, $binance);

            $positionBefore = $liveState->pair_position ?? 0;
            $positionAfter = ($to === $symbol1) ? 1 : 2;

            // Step 1: Sell all "from" for USDT
            $sellResult = $binance->createOrder("{$from}USDT", 'MARKET', 'SELL', $fromBalance);
            if (isset($sellResult['code'])) {
                return response()->json(['error' => "Sell failed: " . ($sellResult['msg'] ?? 'Unknown error')], 500);
            }

            // Calculate USDT received and average fill price
            $usdtReceived = 0;
            $sellFillPrice = floatval($sellResult['fills'][0]['price'] ?? 0);
            foreach ($sellResult['fills'] ?? [] as $fill) {
                $usdtReceived += floatval($fill['qty']) * floatval($fill['price']) - floatval($fill['commission'] ?? 0);
            }

            if ($usdtReceived <= 0) {
                return response()->json(['error' => 'Sell executed but could not determine USDT received'], 500);
            }

            // Log sell trade
            LiveTrade::create([
                'pair_id' => $pair->id,
                'trade_type' => 'pair_trade',
                'side' => 'sell',
                'symbol' => $from,
                'quantity' => floatval($sellResult['executedQty']),
                'requested_quantity' => $fromBalance,
                'fill_price' => $sellFillPrice,
                'usdt_value' => $usdtReceived,
                'binance_order_id' => $sellResult['orderId'] ?? null,
                'pair_position_before' => $positionBefore,
                'pair_position_after' => $positionBefore, // hasn't changed yet
            ]);

            // Step 2: Buy "to" with USDT received
            $toPrice = $this->fetchUsdPrice($to);
            if (!$toPrice) {
                return response()->json(['error' => "Could not fetch {$to} price. USDT is in your account."], 500);
            }

            $buyQuantity = $usdtReceived / $toPrice;
            $buyQuantity = $this->adjustQuantity("{$to}USDT", $buyQuantity, $binance);

            $buyResult = $binance->createOrder("{$to}USDT", 'MARKET', 'BUY', $buyQuantity);
            if (isset($buyResult['code'])) {
                return response()->json([
                    'error' => "Buy failed: " . ($buyResult['msg'] ?? 'Unknown error'),
                    'sell_result' => $sellResult,
                    'usdt_received' => $usdtReceived,
                ], 500);
            }

            $buyFillPrice = floatval($buyResult['fills'][0]['price'] ?? 0);
            $buyUsdtValue = floatval($buyResult['executedQty']) * $buyFillPrice;

            // Log buy trade
            LiveTrade::create([
                'pair_id' => $pair->id,
                'trade_type' => 'pair_trade',
                'side' => 'buy',
                'symbol' => $to,
                'quantity' => floatval($buyResult['executedQty']),
                'requested_quantity' => $buyQuantity,
                'fill_price' => $buyFillPrice,
                'usdt_value' => $buyUsdtValue,
                'binance_order_id' => $buyResult['orderId'] ?? null,
                'pair_position_before' => $positionBefore,
                'pair_position_after' => $positionAfter,
            ]);

            // Update live_state
            $holdingsCoin1 = ($to === $symbol1) ? floatval($buyResult['executedQty']) : 0;
            $holdingsCoin2 = ($to === $symbol1) ? 0 : floatval($buyResult['executedQty']);

            $liveState->update([
                'pair_position' => $positionAfter,
                'holdings_coin1' => $holdingsCoin1,
                'holdings_coin2' => $holdingsCoin2,
                'holdings_usdt' => 0,
            ]);

            // Calculate cross price for chart line
            $crossPrice = $sellFillPrice / $buyFillPrice;
            if (!$isFromSymbol1) {
                $crossPrice = $buyFillPrice / $sellFillPrice;
            }

            return response()->json([
                'success' => true,
                'sell' => $sellResult,
                'buy' => $buyResult,
                'usdt_intermediate' => $usdtReceived,
                'cross_price' => $crossPrice,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function adjustQuantity(string $symbol, float $quantity, BinanceService $binance): float
    {
        try {
            $info = $binance->exchangeInfo($symbol);
            foreach ($info['symbols'] ?? [] as $s) {
                if ($s['symbol'] === $symbol) {
                    foreach ($s['filters'] as $filter) {
                        if ($filter['filterType'] === 'LOT_SIZE') {
                            $stepSize = floatval($filter['stepSize']);
                            $precision = max(0, -intval(log10($stepSize)));
                            return floor($quantity * pow(10, $precision)) / pow(10, $precision);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Fall through to rounding
        }

        return floor($quantity * 1e6) / 1e6;
    }

    private function fetchUsdPrice(string $symbol): ?float
    {
        try {
            $response = Http::timeout(5)->get('https://api.binance.com/api/v3/ticker/price', [
                'symbol' => $symbol . 'USDT',
            ]);

            if ($response->successful()) {
                return floatval($response->json('price'));
            }
        } catch (\Exception $e) {
            // Fall through
        }

        return null;
    }

    private function fetchBinanceKlines(string $symbol, int $startTime): array
    {
        $cacheKey = "binance_klines_{$symbol}_{$startTime}";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($symbol, $startTime) {
            $response = Http::timeout(10)->get('https://api.binance.com/api/v3/klines', [
                'symbol' => $symbol,
                'interval' => '1d',
                'startTime' => $startTime,
                'limit' => 180
            ]);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch data for {$symbol}");
            }

            $data = $response->json();
            
            return array_map(function ($item) {
                return [
                    'time' => intval($item[0] / 1000), // Convert to seconds
                    'open' => floatval($item[1]),
                    'high' => floatval($item[2]),
                    'low' => floatval($item[3]),
                    'close' => floatval($item[4]),
                    'volume' => floatval($item[5])
                ];
            }, $data);
        });
    }

    private function getTradeLines(string $symbol1, string $symbol2): array
    {
        // Find the pair
        $pair = Pair::where(function ($q) use ($symbol1, $symbol2) {
            $q->where('symbol_1', $symbol1)->where('symbol_2', $symbol2);
        })->orWhere(function ($q) use ($symbol1, $symbol2) {
            $q->where('symbol_1', $symbol2)->where('symbol_2', $symbol1);
        })->first();

        if (!$pair) {
            return [];
        }

        $isInverted = strtoupper($pair->symbol_1) !== $symbol1;

        // Get buy-leg trades (these represent completed swaps — the buy side has the final position)
        $trades = LiveTrade::where('pair_id', $pair->id)
            ->where('side', 'buy')
            ->orderBy('created_at')
            ->get();

        $lines = [];
        foreach ($trades as $buyTrade) {
            // Find the matching sell trade (immediately before this buy, same pair)
            $sellTrade = LiveTrade::where('pair_id', $pair->id)
                ->where('side', 'sell')
                ->where('id', '<', $buyTrade->id)
                ->orderByDesc('id')
                ->first();

            if (!$sellTrade) {
                continue;
            }

            // Determine which is symbol1's price and which is symbol2's
            $symbol1Price = null;
            $symbol2Price = null;

            if ($sellTrade->symbol === $symbol1) {
                // Sold symbol1 (its fill_price is symbol1/USDT), bought symbol2
                $symbol1Price = $sellTrade->fill_price;
                $symbol2Price = $buyTrade->fill_price;
            } else {
                // Sold symbol2, bought symbol1
                $symbol1Price = $buyTrade->fill_price;
                $symbol2Price = $sellTrade->fill_price;
            }

            if ($symbol2Price > 0) {
                $crossPrice = $symbol1Price / $symbol2Price;

                $lines[] = [
                    'price' => $crossPrice,
                    'time' => $buyTrade->created_at->timestamp,
                    'direction' => $sellTrade->symbol . '→' . $buyTrade->symbol,
                ];
            }
        }

        return $lines;
    }

    private function calculateCrossPair(array $symbol1Data, array $symbol2Data): array
    {
        $crossPairData = [];
        
        // Create a lookup map for symbol2 data by timestamp for faster access
        $symbol2Map = [];
        foreach ($symbol2Data as $item) {
            $symbol2Map[$item['time']] = $item;
        }

        foreach ($symbol1Data as $symbol1Item) {
            $timestamp = $symbol1Item['time'];
            
            if (isset($symbol2Map[$timestamp])) {
                $symbol2Item = $symbol2Map[$timestamp];
                
                // Calculate cross-pair: symbol1/symbol2 = (symbol1/USDT) / (symbol2/USDT)
                $crossPairData[] = [
                    'time' => $timestamp,
                    'open' => $symbol1Item['open'] / $symbol2Item['open'],
                    'high' => $symbol1Item['high'] / $symbol2Item['high'],
                    'low' => $symbol1Item['low'] / $symbol2Item['low'], 
                    'close' => $symbol1Item['close'] / $symbol2Item['close'],
                    'volume' => $symbol1Item['volume'] // Use symbol1 volume as reference
                ];
            }
        }

        return $crossPairData;
    }
}
