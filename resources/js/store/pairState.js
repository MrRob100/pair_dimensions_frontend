import { reactive } from 'vue';

export const pairState = reactive({
    watchingPairIds: new Set(),
    livePairIds: new Set(),
    pairStatuses: {},
    allKnownPairs: [],
    activePair: null,
    chartData: null,
    chartLoading: false,
    chartError: null,
});

// Initialize from server-bootstrapped data
const boot = window.__PAIR_STATE__ || {};
if (boot.watchingPairIds) boot.watchingPairIds.forEach(id => pairState.watchingPairIds.add(id));
if (boot.livePairIds) boot.livePairIds.forEach(id => pairState.livePairIds.add(id));
if (boot.pairStatuses) Object.assign(pairState.pairStatuses, boot.pairStatuses);
if (boot.watchingPairs) boot.watchingPairs.forEach(p => pairState.allKnownPairs.push(p));
if (boot.livePairs) {
    const existingIds = new Set(pairState.allKnownPairs.map(p => p.id));
    boot.livePairs.forEach(p => {
        if (!existingIds.has(p.id)) pairState.allKnownPairs.push(p);
    });
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]').content;
}

function registerPair(pairId, symbol1, symbol2) {
    if (!pairState.allKnownPairs.find(p => p.id === pairId)) {
        pairState.allKnownPairs.push({ id: pairId, symbol_1: symbol1, symbol_2: symbol2 });
    }
}

export async function watchPair(pairId, symbol1, symbol2) {
    const res = await fetch(`/pair-use/watch/${pairId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
        },
    });
    if (res.ok) {
        const data = await res.json();
        registerPair(pairId, symbol1, symbol2);
        if (data.watching) {
            pairState.watchingPairIds.add(pairId);
            if (!pairState.livePairIds.has(pairId)) {
                pairState.pairStatuses[pairId] = 'watching';
            }
        } else {
            pairState.watchingPairIds.delete(pairId);
            if (!pairState.livePairIds.has(pairId)) {
                delete pairState.pairStatuses[pairId];
            }
        }
    }
}

export async function setPairStatus(pairId) {
    const res = await fetch(`/pair-use/set-status/${pairId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
        },
    });
    if (res.ok) {
        const data = await res.json();
        const newStatus = data.status;
        if (newStatus && newStatus !== 'archived') {
            pairState.pairStatuses[pairId] = newStatus;
        } else {
            delete pairState.pairStatuses[pairId];
        }
        if (newStatus === 'live') {
            pairState.livePairIds.add(pairId);
            pairState.watchingPairIds.delete(pairId);
        } else {
            pairState.livePairIds.delete(pairId);
        }
        if (newStatus === 'watching') {
            pairState.watchingPairIds.add(pairId);
        }
    }
}

export async function loadCharts(pairId, symbol1, symbol2) {
    pairState.activePair = { pairId, symbol1, symbol2 };
    pairState.chartData = null;
    pairState.chartLoading = true;
    pairState.chartError = null;

    try {
        const response = await fetch(`/api/binance/chart-data?symbol1=${symbol1}&symbol2=${symbol2}`);
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.error || 'Failed to fetch data');
        }
        pairState.chartData = result.data;
    } catch (error) {
        pairState.chartError = error.message;
    } finally {
        pairState.chartLoading = false;
    }
}

export function closeCharts() {
    pairState.activePair = null;
    pairState.chartData = null;
    pairState.chartLoading = false;
    pairState.chartError = null;
}
