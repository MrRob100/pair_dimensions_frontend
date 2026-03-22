<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pair Dimensions</title>
    <script>
    window.__PAIR_STATE__ = {
        watchingPairIds: @json($watchingPairs->pluck('pair_id')->values()),
        livePairIds: @json($livePairs->pluck('pair_id')->values()),
        pairStatuses: @json($pairStatuses),
        watchingPairs: @json($watchingPairs->filter(fn($u) => $u->pair)->map(fn($u) => ['id' => $u->pair->id, 'symbol_1' => $u->pair->symbol_1, 'symbol_2' => $u->pair->symbol_2])->values()),
        livePairs: @json($livePairs->filter(fn($u) => $u->pair)->map(fn($u) => ['id' => $u->pair->id, 'symbol_1' => $u->pair->symbol_1, 'symbol_2' => $u->pair->symbol_2])->values()),
        isTestnet: @json(config('binance.testnet')),
    }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Vue app: ChartPanel + Live/Watching blocks -->
        <div id="vue-app"></div>

        <div class="bg-gray-900 rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-700">
                <h1 class="text-2xl font-bold text-gray-100">Backtesting Results</h1>
                <p class="text-gray-400 mt-1">Analysis of trading pair performance with scoring</p>

                <div class="mt-4">
                    <form method="GET" action="{{ route('backtesting.index') }}" id="filter-form">
                        <input type="hidden" name="sort" value="{{ $sortBy }}">
                        <input type="hidden" name="direction" value="{{ $sortDirection }}">

                        <label class="inline-flex items-center">
                            <input type="checkbox"
                                   name="unique_pairs"
                                   value="1"
                                   {{ request('unique_pairs') ? 'checked' : '' }}
                                   onchange="document.getElementById('filter-form').submit()"
                                   class="rounded border-gray-600 bg-gray-800 text-indigo-500 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-30">
                            <span class="ml-2 text-sm text-gray-300">Unique pairs (MA Length 20, Threshold 2)</span>
                        </label>
                    </form>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-800">
                        <tr>
                            <th class="px-3 py-3 w-10"></th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Pair
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'ma_length', 'direction' => $sortBy === 'ma_length' && $sortDirection === 'asc' ? 'desc' : 'asc']) }}"
                                   class="flex items-center hover:text-gray-200">
                                    MA Length
                                    @if($sortBy === 'ma_length')
                                        <svg class="ml-1 w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            @if($sortDirection === 'asc')
                                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                            @else
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            @endif
                                        </svg>
                                    @endif
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'thresh', 'direction' => $sortBy === 'thresh' && $sortDirection === 'asc' ? 'desc' : 'asc']) }}"
                                   class="flex items-center hover:text-gray-200">
                                    Threshold
                                    @if($sortBy === 'thresh')
                                        <svg class="ml-1 w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            @if($sortDirection === 'asc')
                                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                            @else
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            @endif
                                        </svg>
                                    @endif
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'profit_percent', 'direction' => $sortBy === 'profit_percent' && $sortDirection === 'asc' ? 'desc' : 'asc']) }}"
                                   class="flex items-center hover:text-gray-200">
                                    Profit %
                                    @if($sortBy === 'profit_percent')
                                        <svg class="ml-1 w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            @if($sortDirection === 'asc')
                                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                            @else
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            @endif
                                        </svg>
                                    @endif
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'profit_percent_against_wbw', 'direction' => $sortBy === 'profit_percent_against_wbw' && $sortDirection === 'asc' ? 'desc' : 'asc']) }}"
                                   class="flex items-center hover:text-gray-200">
                                    Profit vs WBW %
                                    @if($sortBy === 'profit_percent_against_wbw')
                                        <svg class="ml-1 w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            @if($sortDirection === 'asc')
                                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                            @else
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            @endif
                                        </svg>
                                    @endif
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'trade_count', 'direction' => $sortBy === 'trade_count' && $sortDirection === 'asc' ? 'desc' : 'asc']) }}"
                                   class="flex items-center hover:text-gray-200">
                                    Trade Count
                                    @if($sortBy === 'trade_count')
                                        <svg class="ml-1 w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            @if($sortDirection === 'asc')
                                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                            @else
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            @endif
                                        </svg>
                                    @endif
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => 'score', 'direction' => $sortBy === 'score' && $sortDirection === 'asc' ? 'desc' : 'asc']) }}"
                                   class="flex items-center hover:text-gray-200">
                                    Score
                                    @if($sortBy === 'score')
                                        <svg class="ml-1 w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            @if($sortDirection === 'asc')
                                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                            @else
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            @endif
                                        </svg>
                                    @endif
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-gray-900 divide-y divide-gray-700">
                        @forelse($results as $result)
                            <tr class="hover:bg-gray-800 cursor-pointer transition-colors duration-200"
                                onclick="if({{ $result->pair ? $result->pair->id : 0 }}) window.loadCharts({{ $result->pair ? $result->pair->id : 0 }}, '{{ $result->pair ? $result->pair->symbol_1 : '' }}', '{{ $result->pair ? $result->pair->symbol_2 : '' }}')"
                            >
                                <td class="px-3 py-4 whitespace-nowrap text-center">
                                    @if($result->pair)
                                        @php $watching = isset($watchingPairIds[$result->pair->id]); @endphp
                                        <button
                                            data-pair-heart="{{ $result->pair->id }}"
                                            onclick="event.stopPropagation(); window.watchPair({{ $result->pair->id }}, '{{ $result->pair->symbol_1 }}', '{{ $result->pair->symbol_2 }}')"
                                            title="Watch this pair"
                                            class="text-xl leading-none transition-opacity duration-150 focus:outline-none {{ $watching ? 'opacity-100' : 'opacity-40 hover:opacity-100' }}"
                                        >{{ $watching ? '❤️' : '🤍' }}</button>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium text-gray-100">
                                            {{ $result->pair ? $result->pair->symbol_1 . '/' . $result->pair->symbol_2 : 'N/A' }}
                                        </div>
                                        @if($result->pair && $result->pair->type)
                                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-900 text-blue-200">
                                                {{ $result->pair->type }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    {{ $result->ma_length }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    {{ $result->thresh }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="font-medium {{ $result->profit_percent >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                        {{ number_format($result->profit_percent, 2) }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($result->profit_percent_against_wbw !== null)
                                        <span class="font-medium {{ $result->profit_percent_against_wbw >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                            {{ number_format($result->profit_percent_against_wbw, 2) }}%
                                        </span>
                                    @else
                                        <span class="text-gray-600">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                    {{ $result->trade_count ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @php
                                        $latestScore = $result->pair && $result->pair->scoreResults->count() > 0
                                            ? $result->pair->scoreResults->sortByDesc('date_calculated')->first()->score
                                            : ($result->score ?? null);
                                    @endphp

                                    @if($latestScore !== null)
                                        <span class="font-medium text-indigo-400">
                                            {{ number_format($latestScore, 4) }}
                                        </span>
                                    @else
                                        <span class="text-gray-600">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                    No backtesting results found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($results->hasPages())
                <div class="px-6 py-4 border-t border-gray-700">
                    {{ $results->links() }}
                </div>
            @endif
        </div>

        <div class="mt-6 text-center text-sm text-gray-600">
            Showing {{ $results->count() }} of {{ $results->total() }} results
        </div>
    </div>

</body>
</html>
