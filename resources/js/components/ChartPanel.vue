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
                <span v-if="pairState.pairHoldings" class="ml-3 text-sm text-gray-400">
                    <span>{{ pairState.activePair.symbol1 }}: <span class="text-cyan-400">{{ formatBalance(pairState.pairHoldings.coin1) }}</span><span v-if="holdingsUsd1 != null" class="text-gray-500"> ${{ formatUsd(holdingsUsd1) }}</span></span>
                    <span class="mx-1">|</span>
                    <span>{{ pairState.activePair.symbol2 }}: <span class="text-cyan-400">{{ formatBalance(pairState.pairHoldings.coin2) }}</span><span v-if="holdingsUsd2 != null" class="text-gray-500"> ${{ formatUsd(holdingsUsd2) }}</span></span>
                    <span v-if="holdingsTotal != null" class="ml-1 text-yellow-400 font-medium">= ${{ formatUsd(holdingsTotal) }}</span>
                </span>
                <span v-else-if="pairState.balancesLoading" class="ml-3 text-xs text-gray-500">loading...</span>
                <template v-if="pairState.activePair.pairId">
                    <button @click="onStatusClick" :class="statusClasses">{{ statusLabel }}</button>
                    <button
                        @click="confirmSwap(pairState.activePair.symbol1, pairState.activePair.symbol2)"
                        :disabled="pairState.swapLoading || !canSwap1to2"
                        :class="canSwap1to2 ? swapBtnClasses : disabledSwapBtnClasses"
                    >{{ pairState.activePair.symbol1 }} → {{ pairState.activePair.symbol2 }}</button>
                    <button
                        @click="confirmSwap(pairState.activePair.symbol2, pairState.activePair.symbol1)"
                        :disabled="pairState.swapLoading || !canSwap2to1"
                        :class="canSwap2to1 ? swapBtnClasses : disabledSwapBtnClasses"
                    >{{ pairState.activePair.symbol2 }} → {{ pairState.activePair.symbol1 }}</button>
                    <span class="ml-4 border-l border-gray-700 pl-4 inline-flex items-center gap-2">
                        <select v-model="inputSymbol" class="px-2 py-1 bg-gray-800 border border-gray-600 rounded text-gray-200 text-sm focus:outline-none focus:border-indigo-500">
                            <option :value="pairState.activePair.symbol1">{{ pairState.activePair.symbol1 }}</option>
                            <option :value="pairState.activePair.symbol2">{{ pairState.activePair.symbol2 }}</option>
                        </select>
                        <input
                            v-model="inputAmount"
                            type="number"
                            step="any"
                            min="0"
                            placeholder="amount"
                            class="w-24 px-2 py-1 bg-gray-800 border border-gray-600 rounded text-gray-200 text-sm focus:outline-none focus:border-indigo-500"
                            @keyup.enter="submitInput('input')"
                        />
                        <button
                            @click="submitInput('input')"
                            :disabled="!canSubmitInput || inputLoading"
                            :class="canSubmitInput && !inputLoading
                                ? `${BASE_BTN} bg-green-900/40 border-green-700 text-green-300 hover:bg-green-800/60`
                                : `${BASE_BTN} bg-gray-800/40 border-gray-700 text-gray-600 cursor-not-allowed`"
                        >Pump</button>
                        <button
                            @click="submitInput('shave')"
                            :disabled="!canSubmitInput || inputLoading"
                            :class="canSubmitInput && !inputLoading
                                ? `${BASE_BTN} bg-red-900/40 border-red-700 text-red-300 hover:bg-red-800/60`
                                : `${BASE_BTN} bg-gray-800/40 border-gray-700 text-gray-600 cursor-not-allowed`"
                        >Shave</button>
                    </span>
                </template>
            </div>
            <span v-if="pairState.activePair.pairId" :class="currentStatus === 'live' ? 'text-green-400 font-semibold text-sm' : 'text-gray-500 text-sm'">
                {{ currentStatus === 'live' ? '⚡ Live' : currentStatus === 'watching' ? '👁 Watching' : '—' }}
            </span>
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
                <CandlestickChart :chart-data="pairState.chartData.cross_pair" :with-m-a="true" :trade-lines="pairState.chartData.cross_pair.trade_lines || []" />
            </div>
            <div class="bg-gray-800 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-300 mb-3 text-center">{{ pairState.chartData.symbol2_usdt.symbol }}</h3>
                <CandlestickChart :chart-data="pairState.chartData.symbol2_usdt" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { pairState, watchPair, setPairStatus, swapAssets, fetchBalances, fetchPairHoldings, fetchAccountBalances } from '../store/pairState.js';
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

const balance1 = computed(() => pairState.activePair ? pairState.balances[pairState.activePair.symbol1] || null : null);
const balance2 = computed(() => pairState.activePair ? pairState.balances[pairState.activePair.symbol2] || null : null);

const usdValue1 = computed(() => {
    if (!balance1.value || pairState.prices[pairState.activePair.symbol1] == null) return null;
    return parseFloat(balance1.value.free) * pairState.prices[pairState.activePair.symbol1];
});

const usdValue2 = computed(() => {
    if (!balance2.value || pairState.prices[pairState.activePair.symbol2] == null) return null;
    return parseFloat(balance2.value.free) * pairState.prices[pairState.activePair.symbol2];
});

const pairTotal = computed(() => {
    if (usdValue1.value == null && usdValue2.value == null) return null;
    return (usdValue1.value || 0) + (usdValue2.value || 0);
});

const holdingsUsd1 = computed(() => {
    if (!pairState.pairHoldings || pairState.pairHoldings.coin1 == null) return null;
    const symbol = pairState.activePair?.symbol1;
    if (!symbol || pairState.prices[symbol] == null) return null;
    return parseFloat(pairState.pairHoldings.coin1) * pairState.prices[symbol];
});

const holdingsUsd2 = computed(() => {
    if (!pairState.pairHoldings || pairState.pairHoldings.coin2 == null) return null;
    const symbol = pairState.activePair?.symbol2;
    if (!symbol || pairState.prices[symbol] == null) return null;
    return parseFloat(pairState.pairHoldings.coin2) * pairState.prices[symbol];
});

const holdingsTotal = computed(() => {
    if (holdingsUsd1.value == null && holdingsUsd2.value == null) return null;
    return (holdingsUsd1.value || 0) + (holdingsUsd2.value || 0);
});

function formatBalance(value) {
    const num = parseFloat(value);
    if (num >= 1000) return num.toLocaleString(undefined, { maximumFractionDigits: 2 });
    if (num >= 1) return num.toLocaleString(undefined, { maximumFractionDigits: 4 });
    return num.toLocaleString(undefined, { maximumFractionDigits: 8 });
}

function formatUsd(value) {
    return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

async function onHeartClick() {
    if (!pairState.activePair) return;
    const { pairId, symbol1, symbol2 } = pairState.activePair;
    await watchPair(pairId, symbol1, symbol2);
}

async function onStatusClick() {
    if (!pairState.activePair) return;
    await setPairStatus(pairState.activePair.pairId);
}

const canSwap1to2 = computed(() => usdValue1.value != null && usdValue1.value >= 1);
const canSwap2to1 = computed(() => usdValue2.value != null && usdValue2.value >= 1);

const swapBtnClasses = computed(() => {
    const disabled = pairState.swapLoading ? 'opacity-50 cursor-not-allowed' : '';
    return `${BASE_BTN} bg-indigo-900/40 border-indigo-700 text-indigo-300 hover:bg-indigo-800/60 ${disabled}`;
});

const disabledSwapBtnClasses = `${BASE_BTN} bg-gray-800/40 border-gray-700 text-gray-600 cursor-not-allowed`;

const inputSymbol = ref('');
const inputAmount = ref('');
const inputLoading = ref(false);

const canSubmitInput = computed(() => {
    const amt = parseFloat(inputAmount.value);
    return inputSymbol.value && amt > 0;
});

// Default symbol selector to symbol1 when pair changes
watch(() => pairState.activePair, (pair) => {
    if (pair) inputSymbol.value = pair.symbol1;
}, { immediate: true });

async function submitInput(type) {
    if (!canSubmitInput.value || inputLoading.value) return;
    const amt = parseFloat(inputAmount.value);
    const endpoint = type === 'input' ? '/api/binance/input' : '/api/binance/shave';

    inputLoading.value = true;
    try {
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                pair_id: pairState.activePair.pairId,
                symbol: inputSymbol.value,
                amount: amt,
            }),
        });
        const data = await res.json();
        if (!res.ok) {
            alert(data.error || `${type} failed`);
            return;
        }
        inputAmount.value = '';
        // Refresh pair holdings, pair balances, and account balances (USDT changed)
        await Promise.all([fetchPairHoldings(), fetchBalances(), fetchAccountBalances()]);
    } catch (e) {
        alert(`${type} failed: ${e.message}`);
    } finally {
        inputLoading.value = false;
    }
}

async function confirmSwap(from, to) {
    if (pairState.swapLoading) return;
    if (!confirm(`Sell ALL ${from} balance and buy ${to}?`)) return;

    try {
        await swapAssets(from, to);
        alert(`Swapped ${from} → ${to} successfully`);
    } catch (e) {
        alert(`Swap failed: ${e.message}`);
    }
}
</script>
