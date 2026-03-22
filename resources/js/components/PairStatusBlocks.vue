<template>
    <div v-if="livePairs.length > 0" class="bg-gray-900 rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-100 mb-3">⚡ Live</h2>
        <div class="flex flex-wrap gap-3">
            <div v-for="pair in livePairs" :key="pair.id" class="flex flex-col items-center gap-1">
                <button
                    @click="loadCharts(pair.id, pair.symbol_1, pair.symbol_2)"
                    class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-200 text-sm font-medium rounded-full border border-gray-600 hover:border-gray-400 transition-colors duration-150"
                >{{ pair.symbol_1 }}/{{ pair.symbol_2 }}</button>
                <div v-if="getBalance(pair.symbol_1) || getBalance(pair.symbol_2)" class="text-xs text-gray-400 space-y-0.5 text-center">
                    <div class="flex gap-2 justify-center">
                        <span v-if="getBalance(pair.symbol_1)">{{ pair.symbol_1 }}: <span class="text-green-400">{{ formatBalance(getBalance(pair.symbol_1).free) }}</span> <span v-if="getUsdValue(pair.symbol_1)" class="text-gray-500">${{ formatUsd(getUsdValue(pair.symbol_1)) }}</span></span>
                        <span v-if="getBalance(pair.symbol_2)">{{ pair.symbol_2 }}: <span class="text-green-400">{{ formatBalance(getBalance(pair.symbol_2).free) }}</span> <span v-if="getUsdValue(pair.symbol_2)" class="text-gray-500">${{ formatUsd(getUsdValue(pair.symbol_2)) }}</span></span>
                    </div>
                    <div v-if="getPairTotal(pair)" class="text-yellow-400 font-medium">
                        Pair: ${{ formatUsd(getPairTotal(pair)) }}
                    </div>
                </div>
                <div v-else-if="pairState.balancesLoading" class="text-xs text-gray-500">loading...</div>
            </div>
        </div>
    </div>

    <div v-if="watchingPairs.length > 0" class="bg-gray-900 rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-100 mb-3">❤️ Watching</h2>
        <div class="flex flex-wrap gap-2">
            <button
                v-for="pair in watchingPairs"
                :key="pair.id"
                @click="loadCharts(pair.id, pair.symbol_1, pair.symbol_2)"
                class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-200 text-sm font-medium rounded-full border border-gray-600 hover:border-gray-400 transition-colors duration-150"
            >{{ pair.symbol_1 }}/{{ pair.symbol_2 }}</button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { pairState, loadCharts } from '../store/pairState.js';

const livePairs = computed(() =>
    pairState.allKnownPairs.filter(p => pairState.livePairIds.has(p.id))
);

const watchingPairs = computed(() =>
    pairState.allKnownPairs.filter(p => pairState.watchingPairIds.has(p.id) && !pairState.livePairIds.has(p.id))
);

function getBalance(symbol) {
    return pairState.balances[symbol] || null;
}

function formatBalance(value) {
    const num = parseFloat(value);
    if (num >= 1000) return num.toLocaleString(undefined, { maximumFractionDigits: 2 });
    if (num >= 1) return num.toLocaleString(undefined, { maximumFractionDigits: 4 });
    return num.toLocaleString(undefined, { maximumFractionDigits: 8 });
}

function getUsdValue(symbol) {
    const bal = getBalance(symbol);
    const price = pairState.prices[symbol];
    if (!bal || price == null) return null;
    return parseFloat(bal.free) * price;
}

function getPairTotal(pair) {
    const usd1 = getUsdValue(pair.symbol_1);
    const usd2 = getUsdValue(pair.symbol_2);
    if (usd1 == null && usd2 == null) return null;
    return (usd1 || 0) + (usd2 || 0);
}

function formatUsd(value) {
    return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
