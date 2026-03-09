<template><!-- --></template>

<script setup>
import { watch } from 'vue';
import { pairState } from '../store/pairState.js';

function syncHearts() {
    document.querySelectorAll('[data-pair-heart]').forEach(btn => {
        const id = parseInt(btn.dataset.pairHeart, 10);
        const watching = pairState.watchingPairIds.has(id);
        btn.textContent = watching ? '❤️' : '🤍';
        btn.classList.toggle('opacity-100', watching);
        btn.classList.toggle('opacity-40', !watching);
        btn.classList.toggle('hover:opacity-100', !watching);
    });
}

watch(() => pairState.watchingPairIds.size, syncHearts);
</script>
