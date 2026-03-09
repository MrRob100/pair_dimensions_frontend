<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BinanceController extends Controller
{
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

            return response()->json([
                'success' => true,
                'data' => [
                    'symbol1_usdt' => [
                        'symbol' => "{$symbol1}USDT",
                        'data' => $symbol1UsdtData
                    ],
                    'cross_pair' => [
                        'symbol' => "{$symbol1}{$symbol2}",
                        'data' => $crossPairData
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
