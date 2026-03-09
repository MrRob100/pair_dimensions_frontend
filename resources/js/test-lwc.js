import { createChart, CandlestickSeries } from 'lightweight-charts';

console.log('Lightweight charts loaded');

// Convert the candlestick data to the format expected by lightweight-charts
const candlestickData = [
    { time: '2025-10-01', open: 100, high: 110, low: 90, close: 105 },
    { time: '2025-10-02', open: 105, high: 112, low: 101, close: 108 },
    { time: '2025-10-03', open: 108, high: 115, low: 104, close: 111 },
    { time: '2025-10-04', open: 111, high: 117, low: 107, close: 115 },
    { time: '2025-10-05', open: 115, high: 120, low: 110, close: 118 },
    { time: '2025-10-06', open: 118, high: 125, low: 114, close: 120 },
    { time: '2025-10-07', open: 120, high: 126, low: 117, close: 122 },
    { time: '2025-10-08', open: 122, high: 127, low: 118, close: 125 },
    { time: '2025-10-09', open: 125, high: 130, low: 120, close: 128 },
    { time: '2025-10-10', open: 128, high: 134, low: 124, close: 132 },
    { time: '2025-10-11', open: 132, high: 137, low: 128, close: 130 },
    { time: '2025-10-12', open: 130, high: 135, low: 125, close: 127 },
    { time: '2025-10-13', open: 127, high: 132, low: 122, close: 130 },
    { time: '2025-10-14', open: 130, high: 136, low: 126, close: 134 },
    { time: '2025-10-15', open: 134, high: 140, low: 130, close: 138 },
    { time: '2025-10-16', open: 138, high: 142, low: 133, close: 140 },
    { time: '2025-10-17', open: 140, high: 145, low: 135, close: 143 },
    { time: '2025-10-18', open: 143, high: 148, low: 138, close: 145 },
    { time: '2025-10-19', open: 145, high: 150, low: 140, close: 147 },
    { time: '2025-10-20', open: 147, high: 152, low: 142, close: 150 },
    { time: '2025-10-21', open: 150, high: 155, low: 145, close: 153 },
    { time: '2025-10-22', open: 153, high: 158, low: 148, close: 156 },
    { time: '2025-10-23', open: 156, high: 160, low: 151, close: 158 },
    { time: '2025-10-24', open: 158, high: 163, low: 153, close: 161 },
    { time: '2025-10-25', open: 161, high: 166, low: 156, close: 164 },
    { time: '2025-10-26', open: 164, high: 169, low: 159, close: 167 },
    { time: '2025-10-27', open: 167, high: 172, low: 162, close: 170 },
    { time: '2025-10-28', open: 170, high: 175, low: 165, close: 173 },
    { time: '2025-10-29', open: 173, high: 178, low: 168, close: 176 },
    { time: '2025-10-30', open: 176, high: 181, low: 171, close: 179 },
    { time: '2025-10-31', open: 179, high: 184, low: 174, close: 182 },
    { time: '2025-11-01', open: 182, high: 187, low: 177, close: 185 },
    { time: '2025-11-02', open: 185, high: 190, low: 180, close: 188 },
    { time: '2025-11-03', open: 188, high: 193, low: 183, close: 191 },
    { time: '2025-11-04', open: 191, high: 196, low: 186, close: 194 },
    { time: '2025-11-05', open: 194, high: 199, low: 189, close: 197 },
    { time: '2025-11-06', open: 197, high: 202, low: 192, close: 200 },
    { time: '2025-11-07', open: 200, high: 205, low: 195, close: 203 },
    { time: '2025-11-08', open: 203, high: 208, low: 198, close: 206 },
    { time: '2025-11-09', open: 206, high: 211, low: 201, close: 209 },
    { time: '2025-11-10', open: 209, high: 214, low: 204, close: 212 },
    { time: '2025-11-11', open: 212, high: 217, low: 207, close: 215 },
    { time: '2025-11-12', open: 215, high: 220, low: 210, close: 218 },
    { time: '2025-11-13', open: 218, high: 223, low: 213, close: 221 },
    { time: '2025-11-14', open: 221, high: 226, low: 216, close: 224 },
    { time: '2025-11-15', open: 224, high: 229, low: 219, close: 227 },
    { time: '2025-11-16', open: 227, high: 232, low: 222, close: 230 },
    { time: '2025-11-17', open: 230, high: 235, low: 225, close: 233 },
    { time: '2025-11-18', open: 233, high: 238, low: 228, close: 236 },
    { time: '2025-11-19', open: 236, high: 241, low: 231, close: 239 },
    { time: '2025-11-20', open: 239, high: 244, low: 234, close: 242 },
    { time: '2025-11-21', open: 242, high: 247, low: 237, close: 245 },
    { time: '2025-11-22', open: 245, high: 250, low: 240, close: 248 },
    { time: '2025-11-23', open: 248, high: 253, low: 243, close: 251 },
    { time: '2025-11-24', open: 251, high: 256, low: 246, close: 254 },
    { time: '2025-11-25', open: 254, high: 259, low: 249, close: 257 },
    { time: '2025-11-26', open: 257, high: 262, low: 252, close: 260 },
    { time: '2025-11-27', open: 260, high: 265, low: 255, close: 263 },
    { time: '2025-11-28', open: 263, high: 268, low: 258, close: 266 },
    { time: '2025-11-29', open: 266, high: 271, low: 261, close: 269 },
    { time: '2025-11-30', open: 269, high: 274, low: 264, close: 272 },
    { time: '2025-12-01', open: 272, high: 277, low: 267, close: 275 },
    { time: '2025-12-02', open: 275, high: 280, low: 270, close: 278 },
    { time: '2025-12-03', open: 278, high: 283, low: 273, close: 281 },
    { time: '2025-12-04', open: 281, high: 286, low: 276, close: 284 },
    { time: '2025-12-05', open: 284, high: 289, low: 279, close: 287 },
    { time: '2025-12-06', open: 287, high: 292, low: 282, close: 290 },
    { time: '2025-12-07', open: 290, high: 295, low: 285, close: 293 },
    { time: '2025-12-08', open: 293, high: 298, low: 288, close: 296 },
    { time: '2025-12-09', open: 296, high: 301, low: 291, close: 299 },
];

// Initialize the chart when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    const chartContainer = document.getElementById('trading-chart');
    
    if (!chartContainer) {
        console.error('Chart container not found');
        return;
    }

    // Create the chart
    const chart = createChart(chartContainer, {
        width: chartContainer.clientWidth,
        height: 600,
        layout: {
            backgroundColor: '#ffffff',
            textColor: '#333',
        },
        grid: {
            vertLines: {
                color: '#f0f3fa',
            },
            horzLines: {
                color: '#f0f3fa',
            },
        },
        crosshair: {
            mode: 1,
        },
        rightPriceScale: {
            borderColor: '#cccccc',
        },
        timeScale: {
            borderColor: '#cccccc',
            timeVisible: true,
            secondsVisible: false,
        },
    });

    // Add candlestick series using the correct API
    const candlestickSeries = chart.addSeries(CandlestickSeries, {
        upColor: '#26a69a',
        downColor: '#ef5350',
        borderDownColor: '#ef5350',
        borderUpColor: '#26a69a',
        wickDownColor: '#ef5350',
        wickUpColor: '#26a69a',
    });

    // Set the data
    candlestickSeries.setData(candlestickData);

    // Handle window resize
    window.addEventListener('resize', () => {
        chart.applyOptions({
            width: chartContainer.clientWidth,
        });
    });
});