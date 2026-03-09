<template>
    <div ref="chartContainer" class="h-64 bg-gray-900 rounded border border-gray-700"></div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { createChart, CandlestickSeries, LineSeries } from 'lightweight-charts';

const props = defineProps({
    chartData: { type: Object, required: true },
    withMA: { type: Boolean, default: false },
});

const chartContainer = ref(null);
let chart = null;
let resizeHandler = null;

function buildChart() {
    if (!chartContainer.value || !props.chartData?.data) return;

    if (chart) {
        chart.remove();
        chart = null;
    }
    if (resizeHandler) {
        window.removeEventListener('resize', resizeHandler);
        resizeHandler = null;
    }

    const candlestickData = props.chartData.data.map(item => ({
        time: item.time,
        open: item.open,
        high: item.high,
        low: item.low,
        close: item.close,
    }));

    chart = createChart(chartContainer.value, {
        width: chartContainer.value.clientWidth,
        height: 256,
        layout: {
            background: { color: '#111827' },
            textColor: '#d1d5db',
        },
        grid: {
            vertLines: { color: '#374151' },
            horzLines: { color: '#374151' },
        },
        crosshair: { mode: 1 },
        rightPriceScale: { borderColor: '#374151' },
        timeScale: {
            borderColor: '#374151',
            timeVisible: true,
            secondsVisible: false,
        },
    });

    const candlestickSeries = chart.addSeries(CandlestickSeries, {
        upColor: '#26a69a',
        downColor: '#ef5350',
        borderDownColor: '#ef5350',
        borderUpColor: '#26a69a',
        wickDownColor: '#ef5350',
        wickUpColor: '#26a69a',
    });
    candlestickSeries.setData(candlestickData);

    if (props.withMA && candlestickData.length >= 25) {
        const period = 25;
        const maData = [];
        for (let i = period - 1; i < candlestickData.length; i++) {
            const sum = candlestickData.slice(i - period + 1, i + 1).reduce((acc, d) => acc + d.close, 0);
            maData.push({ time: candlestickData[i].time, value: sum / period });
        }
        const maSeries = chart.addSeries(LineSeries, {
            color: '#facc15',
            lineWidth: 2,
            priceLineVisible: false,
            lastValueVisible: false,
        });
        maSeries.setData(maData);
    }

    resizeHandler = () => {
        if (chart && chartContainer.value) {
            chart.applyOptions({ width: chartContainer.value.clientWidth });
        }
    };
    window.addEventListener('resize', resizeHandler);
}

onMounted(() => buildChart());

onBeforeUnmount(() => {
    if (chart) chart.remove();
    if (resizeHandler) window.removeEventListener('resize', resizeHandler);
});

watch(() => props.chartData, () => buildChart());
</script>
