<template>
    <div class="bg-gray-900 rounded-lg shadow-lg p-4 mb-6">
        <div class="flex items-center gap-3">
            <input
                v-model="symbol1"
                type="text"
                placeholder="Symbol 1"
                class="w-28 px-3 py-1.5 bg-gray-800 border border-gray-600 rounded text-gray-200 text-sm uppercase placeholder-gray-500 focus:outline-none focus:border-indigo-500"
                @keyup.enter="show"
            />
            <span class="text-gray-500">/</span>
            <input
                v-model="symbol2"
                type="text"
                placeholder="Symbol 2"
                class="w-28 px-3 py-1.5 bg-gray-800 border border-gray-600 rounded text-gray-200 text-sm uppercase placeholder-gray-500 focus:outline-none focus:border-indigo-500"
                @keyup.enter="show"
            />
            <button
                @click="show"
                :disabled="!canShow || pairState.chartLoading"
                :class="canShow && !pairState.chartLoading
                    ? 'px-4 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium rounded transition-colors'
                    : 'px-4 py-1.5 bg-gray-700 text-gray-500 text-sm font-medium rounded cursor-not-allowed'"
            >Show</button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { pairState, loadCharts } from '../store/pairState.js';

const symbol1 = ref('');
const symbol2 = ref('');

const canShow = computed(() => symbol1.value.trim() && symbol2.value.trim());

// When a pair is selected elsewhere, populate the inputs
watch(() => pairState.activePair, (pair) => {
    if (pair) {
        symbol1.value = pair.symbol1;
        symbol2.value = pair.symbol2;
    }
}, { immediate: true });

function show() {
    if (!canShow.value || pairState.chartLoading) return;
    const s1 = symbol1.value.trim().toUpperCase();
    const s2 = symbol2.value.trim().toUpperCase();
    loadCharts(null, s1, s2);
}
</script>
