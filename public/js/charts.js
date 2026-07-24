/**
 * Portal APM — ApexCharts wrapper
 * Listens for spa:loaded and data-chart attributes to init charts.
 */
(function () {
    'use strict';

    // Default options aligned with CSS variables — read at init time so themes work
    function getDefaults() {
        const style  = getComputedStyle(document.documentElement);
        const get    = (v) => style.getPropertyValue(v).trim();
        const text   = get('--color-text')       || '#1e293b';
        const muted  = get('--color-text-muted') || '#94a3b8';
        const border = get('--color-border')     || '#e2e8f0';
        const bg     = get('--card-bg')          || '#ffffff';
        const primary  = get('--color-primary')  || '#2563eb';
        const success  = get('--color-success')  || '#16a34a';
        const warning  = get('--color-warning')  || '#d97706';
        const danger   = get('--color-danger')   || '#dc2626';
        const info     = get('--color-info')     || '#0891b2';

        return {
            chart: { background: 'transparent', fontFamily: get('--font-family') || 'inherit', toolbar: { show: false } },
            theme: { mode: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light' },
            colors: [primary, success, warning, danger, info],
            grid: { borderColor: border, strokeDashArray: 4 },
            xaxis: { labels: { style: { colors: muted } }, axisBorder: { color: border }, axisTicks: { color: border } },
            yaxis: { labels: { style: { colors: muted } } },
            legend: { labels: { colors: text } },
            tooltip: { theme: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light' },
        };
    }

    // Merge deep (shallow, 2 levels) — avoids lodash dep
    function merge(base, override) {
        const out = Object.assign({}, base);
        for (const k in override) {
            if (override[k] && typeof override[k] === 'object' && !Array.isArray(override[k])) {
                out[k] = Object.assign({}, base[k] || {}, override[k]);
            } else {
                out[k] = override[k];
            }
        }
        return out;
    }

    /* ── Chart registry ─────────────────────────────────── */
    const registry = {};

    /**
     * Create an ApexChart on an element.
     * @param {string|HTMLElement} target  - selector or DOM element
     * @param {object}             options - ApexCharts options (merged with defaults)
     * @returns {ApexCharts|null}
     */
    window.createChart = function(target, options) {
        if (typeof ApexCharts === 'undefined') return null;
        const el = typeof target === 'string' ? document.querySelector(target) : target;
        if (!el) return null;

        const id = el.id || ('chart-' + Math.random().toString(36).slice(2));
        el.id = id;

        // Destroy existing if re-rendering
        if (registry[id]) {
            registry[id].destroy();
            delete registry[id];
        }

        const finalOpts = merge(getDefaults(), options);
        const chart     = new ApexCharts(el, finalOpts);
        chart.render();
        registry[id] = chart;
        return chart;
    };

    /**
     * Destroy a chart by element id.
     */
    window.destroyChart = function(id) {
        if (registry[id]) { registry[id].destroy(); delete registry[id]; }
    };

    /**
     * Destroy all charts (called before SPA navigation).
     */
    window.destroyAllCharts = function() {
        Object.keys(registry).forEach(id => { registry[id].destroy(); delete registry[id]; });
    };

    /* ── Auto-init [data-chart] elements ────────────────── */
    function initDataCharts(scope) {
        const root = scope || document;
        root.querySelectorAll('[data-chart]').forEach(el => {
            let opts;
            try { opts = JSON.parse(el.dataset.chartOptions || '{}'); } catch { opts = {}; }
            const type = el.dataset.chart;
            if (!type) return;
            createChart(el, Object.assign({ chart: { type } }, opts));
        });
    }

    /* ── Re-init charts on SPA navigation ──────────────── */
    document.addEventListener('spa:loaded', () => {
        destroyAllCharts();
        // Small delay so PHP-rendered content scripts can inject series data
        setTimeout(() => initDataCharts(document.getElementById('main-content')), 50);
    });

    /* ── Re-apply theme on theme change ─────────────────── */
    const observer = new MutationObserver(() => {
        Object.values(registry).forEach(chart => {
            chart.updateOptions({ theme: { mode: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light' } }, false, false);
        });
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

    /* ── Init on first page load ─────────────────────────── */
    document.addEventListener('DOMContentLoaded', () => initDataCharts());

})();
