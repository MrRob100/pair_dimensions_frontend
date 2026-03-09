<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lightweight Charts Test</title>
    @vite(['resources/css/app.css'])
    @vite('resources/js/test-lwc.js')
    <style>
        #trading-chart {
            width: 100%;
            height: 600px;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h1 class="text-2xl font-bold text-gray-900">Lightweight Charts Test</h1>
                <p class="text-gray-600 mt-1">TradingView Lightweight Charts with hardcoded candlestick data</p>
            </div>

            <div class="p-6">
                <div id="trading-chart"></div>
            </div>
        </div>
    </div>

</body>
</html>