(() => {
    'use strict';

    const raw = window.INV_PRICE_CONFIG || {};
    const clamp = (value, min, max, fallback) => {
        const parsed = Number.parseInt(value, 10);
        return Number.isFinite(parsed) ? Math.min(max, Math.max(min, parsed)) : fallback;
    };
    const config = Object.freeze({
        priceDecimals: clamp(raw.priceDecimals, 0, 12, 8),
        amountDecimals: clamp(raw.amountDecimals, 0, 8, 2),
        priceStep: String(raw.priceStep || '0.00000001')
    });
    const round = (value, decimals) => {
        const number = Number(value || 0);
        if (!Number.isFinite(number)) return 0;
        const factor = 10 ** decimals;
        return Math.round((number + Number.EPSILON) * factor) / factor;
    };
    const format = (value, decimals, symbol = true) => {
        const formatted = round(value, decimals).toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
        return (symbol ? '$' : '') + formatted;
    };

    window.InvMoney = Object.freeze({
        config,
        roundPrice: value => round(value, config.priceDecimals),
        roundAmount: value => round(value, config.amountDecimals),
        formatPrice: (value, symbol = true) => format(value, config.priceDecimals, symbol),
        formatAmount: (value, symbol = true) => format(value, config.amountDecimals, symbol),
        applyPriceInput(input) {
            if (!input) return;
            input.step = config.priceStep;
            input.dataset.priceInput = '1';
        }
    });

    const initialize = root => root.querySelectorAll?.('input[data-price-input]').forEach(window.InvMoney.applyPriceInput);
    document.addEventListener('DOMContentLoaded', () => initialize(document));
    document.addEventListener('inv:money:init', event => initialize(event.detail?.root || document));
})();
