(() => {
    const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();
    const closeAll = except => document.querySelectorAll('.inv-search-select.open').forEach(wrapper => {
        if (wrapper !== except && wrapper._closeSearch) wrapper._closeSearch();
    });

    function enhance(select) {
        if (!select || select.dataset.searchReady === '1') return;
        select.dataset.searchReady = '1';
        select.classList.add('inv-search-select-native');
        const wrapper = document.createElement('div');
        const trigger = document.createElement('button');
        const dropdown = document.createElement('div');
        const box = document.createElement('div');
        const input = document.createElement('input');
        const results = document.createElement('div');
        wrapper.className = 'inv-search-select';
        trigger.type = 'button';
        trigger.className = 'inv-search-select-trigger';
        trigger.innerHTML = '<span></span><i class="fa-solid fa-chevron-down"></i>';
        dropdown.className = 'inv-search-select-dropdown';
        box.className = 'inv-search-select-box';
        box.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i>';
        input.type = 'search';
        input.autocomplete = 'off';
        input.placeholder = select.dataset.searchPlaceholder || 'Escriba para buscar…';
        results.className = 'inv-search-select-results';
        box.appendChild(input);
        dropdown.append(box, results);
        select.parentNode.insertBefore(wrapper, select);
        wrapper.append(select, trigger);
        document.body.appendChild(dropdown);

        const placeholder = () => select.options[0]?.textContent.trim() || 'Seleccione…';
        const sync = () => {
            const option = select.options[select.selectedIndex];
            trigger.querySelector('span').textContent = option?.value ? option.textContent.trim() : placeholder();
            trigger.title = trigger.querySelector('span').textContent;
            trigger.disabled = select.disabled;
            trigger.classList.remove('invalid');
        };
        const close = () => {
            wrapper.classList.remove('open');
            dropdown.classList.remove('open');
            input.value = '';
        };
        wrapper._closeSearch = close;
        const position = () => {
            const rect = trigger.getBoundingClientRect();
            const margin = 12;
            const width = Math.min(Math.max(rect.width, 360), window.innerWidth - margin * 2);
            dropdown.style.width = width + 'px';
            dropdown.style.left = Math.max(margin, Math.min(rect.left, window.innerWidth - width - margin)) + 'px';
            dropdown.style.top = Math.min(rect.bottom + 6, window.innerHeight - Math.min(340, dropdown.offsetHeight) - margin) + 'px';
        };
        const render = query => {
            const term = normalize(query);
            results.innerHTML = '';
            let count = 0;
            let rendered = 0;
            Array.from(select.options).forEach(option => {
                if (!option.value || option.disabled || option.hidden || (term && !normalize(option.textContent).includes(term))) return;
                count++;
                if (rendered >= 100) return;
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'inv-search-select-option' + (String(select.value) === String(option.value) ? ' active' : '');
                const parts = option.textContent.split(' · ');
                const strong = document.createElement('strong');
                const small = document.createElement('small');
                strong.textContent = parts.shift() || option.textContent;
                small.textContent = parts.join(' · ');
                button.append(strong);
                if (small.textContent) button.append(small);
                button.onclick = () => {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', {bubbles: true}));
                    sync();
                    close();
                };
                results.appendChild(button);
                rendered++;
            });
            if (!count) results.innerHTML = '<div class="inv-search-select-empty">No se encontraron coincidencias.</div>';
            else if (count > rendered) {
                const message = document.createElement('div');
                message.className = 'inv-search-select-empty';
                message.textContent = 'Hay ' + count + ' coincidencias. Escriba más para reducir la lista.';
                results.appendChild(message);
            }
        };

        trigger.onclick = event => {
            event.stopPropagation();
            if (select.disabled) return;
            const opening = !wrapper.classList.contains('open');
            closeAll(wrapper);
            if (!opening) return close();
            wrapper.classList.add('open');
            dropdown.classList.add('open');
            render('');
            position();
            requestAnimationFrame(() => input.focus());
        };
        dropdown.onclick = event => event.stopPropagation();
        input.oninput = () => render(input.value);
        input.onkeydown = event => {
            if (event.key === 'Escape') close();
            if (event.key === 'Enter') {
                const first = results.querySelector('.inv-search-select-option');
                if (first) { event.preventDefault(); first.click(); }
            }
        };
        select.addEventListener('change', sync);
        new MutationObserver(sync).observe(select, {attributes: true, attributeFilter: ['disabled']});
        select.addEventListener('invalid', () => {
            trigger.classList.add('invalid');
            trigger.focus();
        });
        sync();
    }

    const init = root => root.querySelectorAll?.('select[data-searchable-select]').forEach(enhance);
    document.addEventListener('DOMContentLoaded', () => init(document));
    document.addEventListener('inv:searchable-select:init', event => init(event.detail?.root || document));
    document.addEventListener('click', () => closeAll());
    window.addEventListener('resize', () => closeAll());
    document.addEventListener('scroll', event => {
        if (!(event.target instanceof Element) || !event.target.closest('.inv-search-select-results')) closeAll();
    }, true);
})();
