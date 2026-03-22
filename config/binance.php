<?php

return [

    'testnet' => env('BINANCE_TESTNET', true),

    'live' => [
        'api_key' => env('BINANCE_API_KEY'),
        'secret' => env('BINANCE_SECRET'),
    ],

    'testnet_keys' => [
        'api_key' => env('BINANCE_TESTNET_API_KEY'),
        'secret' => env('BINANCE_TESTNET_SECRET'),
    ],

];
