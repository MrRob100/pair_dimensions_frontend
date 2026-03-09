<template>
    <div v-if="livePairs.length > 0" class="bg-gray-900 rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-100 mb-3">⚡ Live</h2>
        <div class="flex flex-wrap gap-2">
            <button
                v-for="pair in livePairs"
                :key="pair.id"
                @click="loadCharts(pair.id, pair.symbol_1, pair.symbol_2)"
                class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-gray-200 text-sm font-medium rounded-full border border-gray-600 hover:border-gray-400 transition-colors duration-150"
            >{{ pair.symbol_1 }}/{{ pair.symbol_2 }}</button>
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
</script>
