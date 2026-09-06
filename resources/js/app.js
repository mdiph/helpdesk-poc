import Alpine from 'alpinejs';
import {
    Chart,
    ArcElement,
    BarElement,
    BarController,
    DoughnutController,
    LineController,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(
    ArcElement, BarElement, BarController, DoughnutController, LineController,
    LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend,
);

Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';
Chart.defaults.color = '#6b7280';
Chart.defaults.plugins.legend.labels.boxWidth = 12;

/**
 * Render every <canvas data-chart="..." data-chart-data="..."> on the page.
 * Keeps chart wiring declarative so Blade views only emit data.
 */
function renderCharts() {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        const type = canvas.dataset.chart;
        const payload = JSON.parse(canvas.dataset.chartData || '{}');
        new Chart(canvas, buildConfig(type, payload));
    });
}

const PALETTE = ['#3366ff', '#00b894', '#fdcb6e', '#e17055', '#6c5ce7', '#0984e3', '#a0aec0'];

function buildConfig(type, payload) {
    const base = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: type === 'doughnut' ? 'right' : 'top' } },
    };

    if (type === 'line') {
        return {
            type: 'line',
            data: {
                labels: payload.labels,
                datasets: payload.datasets.map((ds, i) => ({
                    label: ds.label,
                    data: ds.data,
                    borderColor: PALETTE[i % PALETTE.length],
                    backgroundColor: PALETTE[i % PALETTE.length] + '22',
                    tension: 0.3,
                    fill: true,
                })),
            },
            options: { ...base, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        };
    }

    if (type === 'bar') {
        return {
            type: 'bar',
            data: {
                labels: payload.labels,
                datasets: [{ label: payload.label || 'Tickets', data: payload.data, backgroundColor: PALETTE }],
            },
            options: { ...base, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
        };
    }

    // doughnut
    return {
        type: 'doughnut',
        data: {
            labels: payload.labels,
            datasets: [{ data: payload.data, backgroundColor: PALETTE }],
        },
        options: base,
    };
}

document.addEventListener('DOMContentLoaded', renderCharts);

window.Alpine = Alpine;
Alpine.start();
