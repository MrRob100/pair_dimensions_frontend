import './bootstrap';
import { createApp } from 'vue';
import App from './components/App.vue';
import { loadCharts, watchPair, closeCharts } from './store/pairState.js';

// Mount Vue app
const mountEl = document.getElementById('vue-app');
if (mountEl) {
    createApp(App).mount(mountEl);
}

// Window shims for Blade table onclick compatibility
window.loadCharts = (pairId, s1, s2) => loadCharts(pairId, s1, s2);
window.watchPair  = (pairId, s1, s2) => watchPair(pairId, s1, s2);
window.closeCharts = () => closeCharts();

// Add table row hover effects and smooth transitions
document.addEventListener('DOMContentLoaded', function() {
    const sortLinks = document.querySelectorAll('thead a');
    sortLinks.forEach(link => {
        link.addEventListener('click', function() {
            const icon = this.querySelector('svg');
            if (icon) {
                icon.classList.add('animate-spin');
            }
        });
    });

    const percentCells = document.querySelectorAll('td .font-medium');
    percentCells.forEach(cell => {
        cell.addEventListener('mouseenter', function() {
            this.classList.add('scale-105', 'transition-transform', 'duration-200');
        });
        cell.addEventListener('mouseleave', function() {
            this.classList.remove('scale-105');
        });
    });
});
