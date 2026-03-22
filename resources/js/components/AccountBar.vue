<template>
    <div class="bg-gray-900 rounded-lg shadow-lg p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <div v-if="pairState.accountLoading" class="text-sm text-gray-500">Loading balances...</div>
            <template v-else>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 uppercase tracking-wide">BTC</span>
                    <span class="text-sm font-medium text-gray-200">{{ formatBalance(btcFree) }}</span>
                    <span v-if="btcUsd != null" class="text-xs text-gray-500">${{ formatUsd(btcUsd) }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 uppercase tracking-wide">USDT</span>
                    <span class="text-sm font-medium text-gray-200">{{ formatBalance(usdtFree) }}</span>
                </div>
            </template>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-block w-2 h-2 rounded-full" :class="pairState.isTestnet ? 'bg-yellow-400' : 'bg-green-400'"></span>
            <span class="text-xs font-medium" :class="pairState.isTestnet ? 'text-yellow-400' : 'text-green-400'">
                {{ pairState.isTestnet ? 'Testnet' : 'Live' }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { pairState } from '../store/pairState.js';

const btcFree = computed(() => {
    const bal = pairState.accountBalances['BTC'];
    return bal ? parseFloat(bal.free) : 0;
});

const usdtFree = computed(() => {
    const bal = pairState.accountBalances['USDT'];
    return bal ? parseFloat(bal.free) : 0;
});

const btcUsd = computed(() => {
    const price = pairState.accountPrices['BTC'];
    if (price == null) return null;
    return btcFree.value * price;
});

function formatBalance(value) {
    if (value >= 1000) return value.toLocaleString(undefined, { maximumFractionDigits: 2 });
    if (value >= 1) return value.toLocaleString(undefined, { maximumFractionDigits: 4 });
    return value.toLocaleString(undefined, { maximumFractionDigits: 8 });
}

function formatUsd(value) {
    return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
</script>
