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
    balances: {},
    prices: {},
    balancesLoading: false,
    swapLoading: false,
    swapError: null,
    swapResult: null,
    pairHoldings: null,
    isTestnet: false,
    accountBalances: {},
    accountPrices: {},
    accountLoading: false,
});

// Initialize from server-bootstrapped data
const boot = window.__PAIR_STATE__ || {};
if (boot.watchingPairIds) boot.watchingPairIds.forEach(id => pairState.watchingPairIds.add(id));
if (boot.livePairIds) boot.livePairIds.forEach(id => pairState.livePairIds.add(id));
if (boot.pairStatuses) Object.assign(pairState.pairStatuses, boot.pairStatuses);
if (boot.watchingPairs) boot.watchingPairs.forEach(p => pairState.allKnownPairs.push(p));
if (boot.isTestnet != null) pairState.isTestnet = boot.isTestnet;
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
    const urlPairId = pairId || 0;
    const params = pairId ? '' : `?symbol1=${encodeURIComponent(symbol1)}&symbol2=${encodeURIComponent(symbol2)}`;
    const res = await fetch(`/pair-use/watch/${urlPairId}${params}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
        },
    });
    if (res.ok) {
        const data = await res.json();
        const resolvedId = data.pair_id || pairId;
        registerPair(resolvedId, symbol1, symbol2);

        // Update activePair with the resolved pairId if it was null
        if (!pairId && pairState.activePair &&
            pairState.activePair.symbol1 === symbol1 &&
            pairState.activePair.symbol2 === symbol2) {
            pairState.activePair.pairId = resolvedId;
        }

        if (data.watching) {
            pairState.watchingPairIds.add(resolvedId);
            if (!pairState.livePairIds.has(resolvedId)) {
                pairState.pairStatuses[resolvedId] = 'watching';
            }
        } else {
            pairState.watchingPairIds.delete(resolvedId);
            if (!pairState.livePairIds.has(resolvedId)) {
                delete pairState.pairStatuses[resolvedId];
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
    // If no pairId, look up whether this pair exists in the DB
    if (!pairId) {
        try {
            const lookup = await fetch(`/api/pair/lookup?symbol1=${encodeURIComponent(symbol1)}&symbol2=${encodeURIComponent(symbol2)}`);
            const result = await lookup.json();
            if (result.found) {
                pairId = result.pair_id;
                registerPair(pairId, symbol1, symbol2);
                if (result.status === 'watching') {
                    pairState.watchingPairIds.add(pairId);
                    pairState.pairStatuses[pairId] = 'watching';
                } else if (result.status === 'live') {
                    pairState.livePairIds.add(pairId);
                    pairState.pairStatuses[pairId] = 'live';
                }
            }
        } catch (e) {
            // Continue without pairId
        }
    }

    pairState.activePair = { pairId, symbol1, symbol2 };
    pairState.chartData = null;
    pairState.chartLoading = true;
    pairState.chartError = null;

    // Fetch charts, balances, and pair holdings in parallel
    fetchBalances();
    fetchPairHoldings();

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

export async function fetchBalances() {
    const livePairs = pairState.allKnownPairs.filter(p => pairState.livePairIds.has(p.id));

    const symbolSet = new Set();
    livePairs.forEach(p => {
        symbolSet.add(p.symbol_1);
        symbolSet.add(p.symbol_2);
    });

    // Include active pair symbols
    if (pairState.activePair) {
        symbolSet.add(pairState.activePair.symbol1);
        symbolSet.add(pairState.activePair.symbol2);
    }

    if (symbolSet.size === 0) return;

    const params = new URLSearchParams();
    symbolSet.forEach(s => params.append('symbols[]', s));

    pairState.balancesLoading = true;
    try {
        const res = await fetch(`/api/binance/balances?${params}`);
        const data = await res.json();
        if (data.balances) {
            Object.assign(pairState.balances, data.balances);
        }
        if (data.prices) {
            Object.assign(pairState.prices, data.prices);
        }
    } catch (e) {
        console.error('Failed to fetch balances:', e);
    } finally {
        pairState.balancesLoading = false;
    }
}

export async function fetchPairHoldings() {
    const pairId = pairState.activePair?.pairId;
    if (!pairId) {
        pairState.pairHoldings = null;
        return;
    }
    try {
        const res = await fetch(`/api/pair/holdings?pair_id=${pairId}`);
        const data = await res.json();
        pairState.pairHoldings = data.holdings;
    } catch (e) {
        console.error('Failed to fetch pair holdings:', e);
    }
}

// Fetch balances on load if there are live pairs
if (pairState.livePairIds.size > 0) {
    fetchBalances();
}

// Fetch account-level BTC + USDT balances on load
fetchAccountBalances();

export async function fetchAccountBalances() {
    pairState.accountLoading = true;
    try {
        const params = new URLSearchParams();
        params.append('symbols[]', 'BTC');
        params.append('symbols[]', 'USDT');
        const res = await fetch(`/api/binance/balances?${params}`);
        const data = await res.json();
        if (data.balances) {
            pairState.accountBalances = data.balances;
        }
        if (data.prices) {
            pairState.accountPrices = data.prices;
        }
    } catch (e) {
        console.error('Failed to fetch account balances:', e);
    } finally {
        pairState.accountLoading = false;
    }
}

export async function swapAssets(from, to) {
    pairState.swapLoading = true;
    pairState.swapError = null;
    pairState.swapResult = null;

    try {
        const res = await fetch('/api/binance/swap', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                from,
                to,
                pair_id: pairState.activePair?.pairId ?? null,
            }),
        });
        const data = await res.json();
        if (!res.ok) {
            throw new Error(data.error || 'Swap failed');
        }
        pairState.swapResult = data;
        // Refresh balances after swap
        await fetchBalances();
    } catch (e) {
        pairState.swapError = e.message;
        throw e;
    } finally {
        pairState.swapLoading = false;
    }
}

export function closeCharts() {
    pairState.activePair = null;
    pairState.chartData = null;
    pairState.chartLoading = false;
    pairState.chartError = null;
}
