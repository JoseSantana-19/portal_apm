(() => {
    const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();
    function enhance(table) {
        if (!table || table.dataset.autoSearchReady === '1' || table.dataset.autoSearch === 'off') return;
        if (table.matches('.dataTable') || table.closest('.dataTables_wrapper')) return;
        const rows = [...table.querySelectorAll(':scope > tbody > tr')].filter(row => row.querySelectorAll('td').length > 1);
        if (rows.length < 10) return;
        const area = table.closest('.wf-card,.content-panel,.panel,.card') || table.parentElement;
        if (area?.querySelector('.wf-list-filters,.dataTables_filter,.maestro-table-search,[data-table-search]')) return;
        table.dataset.autoSearchReady = '1';
        const toolbar = document.createElement('div');
        toolbar.className = 'inv-auto-search';
        toolbar.dataset.tableSearch = '1';
        toolbar.innerHTML = '<label><i class="fa-solid fa-magnifying-glass"></i><input type="search" placeholder="Buscar en esta lista…" autocomplete="off"></label><span></span>';
        const input = toolbar.querySelector('input');
        const counter = toolbar.querySelector('span');
        const wrapper = table.closest('.table-responsive') || table;
        wrapper.parentNode.insertBefore(toolbar, wrapper);
        const filter = () => {
            const term = normalize(input.value.trim());
            let visible = 0;
            rows.forEach(row => {
                row.hidden = term !== '' && !normalize(row.textContent).includes(term);
                if (!row.hidden) visible++;
            });
            counter.textContent = visible + ' de ' + rows.length + ' registros';
        };
        input.oninput = filter;
        filter();
    }
    const scan = root => root.querySelectorAll?.('table').forEach(enhance);
    document.addEventListener('DOMContentLoaded', () => {
        scan(document);
        const observer = new MutationObserver(mutations => mutations.forEach(mutation => mutation.addedNodes.forEach(node => {
            if (node.nodeType === 1) {
                if (node.matches?.('table')) enhance(node);
                const parentTable = node.closest?.('table');
                if (parentTable) enhance(parentTable);
                scan(node);
            }
        })));
        observer.observe(document.body, {childList: true, subtree: true});
    });
})();
