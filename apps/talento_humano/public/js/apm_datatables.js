(() => {
    'use strict';

    const instances = new Map();
    const parseBoolean = (value, fallback = true) => value === undefined ? fallback : value !== 'false';
    const parseOrder = value => {
        if (!value) return [];
        try {
            const parsed = JSON.parse(value);
            return Array.isArray(parsed) ? parsed : [];
        } catch (_) {
            return [];
        }
    };
    const parseIndexes = value => String(value || '')
        .split(',')
        .map(item => Number.parseInt(item.trim(), 10))
        .filter(Number.isInteger);

    const language = table => ({
        emptyTable: table.dataset.dtEmpty || 'No existen registros para mostrar.',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Sin registros disponibles',
        infoFiltered: '(filtrados de _MAX_ registros)',
        lengthMenu: 'Mostrar _MENU_ registros',
        loadingRecords: 'Cargando registros…',
        processing: 'Procesando…',
        search: '',
        searchPlaceholder: table.dataset.dtSearchPlaceholder || 'Buscar en la tabla…',
        zeroRecords: 'No se encontraron coincidencias.',
        paginate: {
            first: 'Primera',
            last: 'Última',
            next: 'Siguiente',
            previous: 'Anterior'
        },
        aria: {
            orderable: 'Ordenar por esta columna',
            orderableReverse: 'Invertir el orden de esta columna'
        }
    });

    const removeEmptyRows = table => {
        table.querySelectorAll('tbody tr[data-dt-empty]').forEach(row => row.remove());
    };

    const bindExternalControls = (table, instance) => {
        if (!table.id) return;
        document.querySelectorAll(`[data-dt-filter-target="#${CSS.escape(table.id)}"]`).forEach(control => {
            if (control.dataset.dtFilterBound === 'true') return;
            const column = Number.parseInt(control.dataset.dtColumn || '', 10);
            if (!Number.isInteger(column)) return;
            const apply = () => {
                const value = control.value.trim();
                if (control.dataset.dtFilterMode === 'exact' && value) {
                    const escaped = value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    instance.column(column).search(`^${escaped}$`, { regex: true, smart: false }).draw();
                } else {
                    instance.column(column).search(value).draw();
                }
            };
            control.addEventListener('change', apply);
            control.dataset.dtFilterBound = 'true';
            if (control.value) apply();
        });
    };

    const initialize = table => {
        if (!(table instanceof HTMLTableElement) || instances.has(table) || typeof window.DataTable !== 'function') return null;
        removeEmptyRows(table);
        const disabledColumns = parseIndexes(table.dataset.dtOrderDisabled);
        const pageLength = Math.max(1, Number.parseInt(table.dataset.dtPageLength || '25', 10) || 25);
        const paging = parseBoolean(table.dataset.dtPaging, true);
        const searching = parseBoolean(table.dataset.dtSearching, true);
        const searchControl = parseBoolean(table.dataset.dtSearchControl, searching) && searching;
        const info = parseBoolean(table.dataset.dtInfo, true);
        const lengthChange = parseBoolean(table.dataset.dtLengthChange, paging);
        const options = {
            autoWidth: false,
            deferRender: true,
            pageLength,
            lengthMenu: [10, 25, 50, 100],
            paging,
            searching,
            info,
            lengthChange,
            order: parseOrder(table.dataset.dtOrder),
            language: language(table),
            columnDefs: disabledColumns.length ? [{ targets: disabledColumns, orderable: false }] : [],
            layout: {
                topStart: null,
                topEnd: searchControl ? 'search' : null,
                bottomStart: [
                    ...(lengthChange ? ['pageLength'] : []),
                    ...(info ? ['info'] : [])
                ],
                bottomEnd: paging ? 'paging' : null
            }
        };
        const instance = new window.DataTable(table, options);
        const container = table.closest('.dt-container');
        container?.classList.add('apm-datatable-shell');
        if (table.dataset.dtCompact === 'true') container?.classList.add('apm-datatable-compact');
        table.dataset.dtReady = 'true';
        instances.set(table, instance);
        bindExternalControls(table, instance);
        table.dispatchEvent(new CustomEvent('apm:datatable-ready', { detail: { instance } }));
        return instance;
    };

    const initializeWithin = (root = document) => {
        if (root instanceof HTMLTableElement && root.matches('[data-apm-datatable]')) initialize(root);
        root.querySelectorAll?.('table[data-apm-datatable]').forEach(initialize);
    };

    const adjustWithin = (root = document) => {
        root.querySelectorAll?.('table[data-apm-datatable]').forEach(table => {
            const instance = instances.get(table);
            instance?.columns?.adjust();
        });
    };

    document.addEventListener('DOMContentLoaded', () => initializeWithin());
    document.addEventListener('apm:modal-opened', event => {
        initializeWithin(event.target);
        window.requestAnimationFrame(() => adjustWithin(event.target));
    });

    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => mutation.addedNodes.forEach(node => {
            if (!(node instanceof Element)) return;
            initializeWithin(node);
        }));
    });
    document.addEventListener('DOMContentLoaded', () => observer.observe(document.body, { childList: true, subtree: true }));

    window.apmDataTables = { initialize, initializeWithin, adjustWithin, get: table => instances.get(table) || null };
})();
