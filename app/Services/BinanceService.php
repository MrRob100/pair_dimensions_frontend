<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BinanceService
{
    protected string $apiKey;
    protected string $secret;
    protected string $baseUrl;

    public function __construct()
    {
        $testnet = config('binance.testnet');
        $keys = $testnet ? config('binance.testnet_keys') : config('binance.live');

        $this->apiKey = $keys['api_key'];
        $this->secret = $keys['secret'];
        $this->baseUrl = $testnet
            ? 'https://testnet.binance.vision'
            : 'https://api.binance.com';
    }

    public function fetchBalance(): array
    {
        return $this->signedRequest('GET', '/api/v3/account');
    }

    public function fetchTicker(string $symbol): array
    {
        return $this->publicRequest('/api/v3/ticker/24hr', ['symbol' => $symbol]);
    }

    public function fetchOHLCV(string $symbol, string $interval = '1d', ?int $startTime = null, ?int $limit = null): array
    {
        $params = array_filter([
            'symbol' => $symbol,
            'interval' => $interval,
            'startTime' => $startTime,
            'limit' => $limit,
        ]);

        return $this->publicRequest('/api/v3/klines', $params);
    }

    public function createOrder(string $symbol, string $type, string $side, float $quantity, ?float $price = null): array
    {
        $params = array_filter([
            'symbol' => $symbol,
            'side' => strtoupper($side),
            'type' => strtoupper($type),
            'quantity' => $quantity,
            'price' => $price,
            'timeInForce' => $price ? 'GTC' : null,
        ]);

        return $this->signedRequest('POST', '/api/v3/order', $params);
    }

    public function fetchOpenOrders(?string $symbol = null): array
    {
        $params = array_filter(['symbol' => $symbol]);

        return $this->signedRequest('GET', '/api/v3/openOrders', $params);
    }

    public function exchangeInfo(string $symbol): array
    {
        return $this->publicRequest('/api/v3/exchangeInfo', ['symbol' => $symbol]);
    }

    public function cancelOrder(string $orderId, string $symbol): array
    {
        return $this->signedRequest('DELETE', '/api/v3/order', [
            'symbol' => $symbol,
            'orderId' => $orderId,
        ]);
    }

    protected function publicRequest(string $endpoint, array $params = []): array
    {
        $response = Http::timeout(10)
            ->get($this->baseUrl . $endpoint, $params);

        return $response->json();
    }

    protected function signedRequest(string $method, string $endpoint, array $params = []): array
    {
        $params['timestamp'] = (int) (microtime(true) * 1000);
        $params['recvWindow'] = 5000;

        $query = http_build_query($params);
        $signature = hash_hmac('sha256', $query, $this->secret);
        $query .= '&signature=' . $signature;

        $url = $this->baseUrl . $endpoint . '?' . $query;

        $response = Http::timeout(10)
            ->withHeaders(['X-MBX-APIKEY' => $this->apiKey])
            ->withBody('', 'application/x-www-form-urlencoded')
            ->$method($url);

        return $response->json();
    }
}
