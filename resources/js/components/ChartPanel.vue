<template>
    <div v-if="pairState.activePair" class="bg-gray-900 rounded-lg shadow-lg p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center">
                <button
                    @click="onHeartClick"
                    :title="isWatching ? 'Unwatch this pair' : 'Watch this pair'"
                    :class="['text-2xl leading-none transition-opacity duration-150 focus:outline-none mr-3', isWatching ? 'opacity-100' : 'opacity-40 hover:opacity-100']"
                >{{ isWatching ? '❤️' : '🤍' }}</button>
                <h2 class="text-xl font-bold text-gray-100">
                    Charts for {{ pairState.activePair.symbol1 }}/{{ pairState.activePair.symbol2 }}
                </h2>
                <button @click="onStatusClick" :class="statusClasses">{{ statusLabel }}</button>
            </div>
            <button @click="closeCharts" class="text-gray-400 hover:text-gray-200 text-xl font-bold px-3 py-1 rounded hover:bg-gray-700">×</button>
        </div>

        <div v-if="pairState.chartLoading" class="flex justify-center items-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-400"></div>
            <span class="ml-2 text-gray-400">Loading charts...</span>
        </div>

        <div v-else-if="pairState.chartError" class="mt-4 p-4 bg-red-950 border border-red-800 rounded-lg">
            <p class="text-red-400">Failed to load chart data: {{ pairState.chartError }}</p>
        </div>

        <div v-else-if="pairState.chartData" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-gray-800 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-300 mb-3 text-center">{{ pairState.chartData.symbol1_usdt.symbol }}</h3>
                <CandlestickChart :chart-data="pairState.chartData.symbol1_usdt" />
            </div>
            <div class="bg-gray-800 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-300 mb-3 text-center">{{ pairState.chartData.cross_pair.symbol }}</h3>
                <CandlestickChart :chart-data="pairState.chartData.cross_pair" :with-m-a="true" />
            </div>
            <div class="bg-gray-800 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-300 mb-3 text-center">{{ pairState.chartData.symbol2_usdt.symbol }}</h3>
                <CandlestickChart :chart-data="pairState.chartData.symbol2_usdt" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { pairState, watchPair, setPairStatus, closeCharts } from '../store/pairState.js';
import CandlestickChart from './CandlestickChart.vue';

const BASE_BTN = 'ml-3 inline-flex items-center gap-1.5 px-3 py-1 text-sm font-medium rounded border transition-colors duration-150 focus:outline-none';

const isWatching = computed(() =>
    pairState.activePair ? pairState.watchingPairIds.has(pairState.activePair.pairId) : false
);

const currentStatus = computed(() =>
    pairState.activePair ? (pairState.pairStatuses[pairState.activePair.pairId] ?? null) : null
);

const statusLabel = computed(() => {
    if (currentStatus.value === 'live') return '🪨 Archive';
    if (currentStatus.value === 'watching') return '⚡ Go Live';
    return '🤍 Watch';
});

const statusClasses = computed(() => {
    if (currentStatus.value === 'live') {
        return `${BASE_BTN} bg-amber-900/40 border-amber-700 text-amber-300 hover:bg-amber-800/60`;
    }
    return `${BASE_BTN} bg-gray-700 border-gray-600 text-gray-200 hover:bg-gray-600`;
});

async function onHeartClick() {
    if (!pairState.activePair) return;
    const { pairId, symbol1, symbol2 } = pairState.activePair;
    await watchPair(pairId, symbol1, symbol2);
}

async function onStatusClick() {
    if (!pairState.activePair) return;
    await setPairStatus(pairState.activePair.pairId);
}
</script>
