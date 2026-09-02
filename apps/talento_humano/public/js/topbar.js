/* Comportamiento compartido del encabezado institucional. */
document.addEventListener('DOMContentLoaded', () => {
    const date = document.getElementById('currentDate');
    if (date) {
        const iso = date.dataset.institutionalDate || '';
        const parts = iso.split('-').map(Number);
        const institutionalDate = parts.length === 3 && parts.every(Number.isFinite)
            ? new Date(parts[0], parts[1] - 1, parts[2], 12, 0, 0)
            : new Date();
        date.textContent = institutionalDate.toLocaleDateString('es-EC', {
            day: '2-digit', month: 'long', year: 'numeric'
        });
    }

    const form = document.querySelector('.global-search-form');
    const input = document.getElementById('globalSearch');
    const status = document.getElementById('globalSearchStatus');
    const results = document.getElementById('globalSearchResults');
    let searchTimer = null;
    let searchRequest = null;

    const closeSearchResults = () => {
        if (!results || !input) return;
        results.hidden = true;
        input.setAttribute('aria-expanded', 'false');
    };

    const renderSearchResults = items => {
        if (!results || !input) return;
        results.textContent = '';
        if (!items.length) {
            const empty = document.createElement('div');
            empty.className = 'global-search-empty';
            empty.textContent = 'No se encontraron funcionarios con esos datos y estado.';
            results.append(empty);
        } else {
            items.forEach(item => {
                const option = document.createElement('a');
                option.className = 'global-search-option';
                option.href = `${window.BASE_URL || ''}/talento-humano/empleado/perfil/${encodeURIComponent(item.cedula)}`;
                option.setAttribute('role', 'option');
                option.dataset.searchOption = '1';

                const main = document.createElement('span');
                main.className = 'global-search-option-main';
                const name = document.createElement('strong');
                name.textContent = item.nombre;
                const identification = document.createElement('small');
                identification.textContent = `${item.cargo || 'Sin cargo'} · ${item.cedula}`;
                main.append(name, identification);

                const badge = document.createElement('span');
                badge.className = `global-search-state is-${item.estado === 'Activo' ? 'active' : 'inactive'}`;
                badge.textContent = item.estado;
                option.append(main, badge);
                results.append(option);
            });
        }
        results.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    };

    const requestSearchResults = () => {
        if (!form || !input || !results) return;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(async () => {
            searchRequest?.abort();
            searchRequest = new AbortController();
            const endpoint = form.dataset.searchEndpoint;
            const query = new URLSearchParams({ q: input.value.trim() });
            if (status?.value === 'Activo') query.set('estado', '1');
            if (status?.value === 'Inactivo') query.set('estado', '0');
            try {
                results.hidden = false;
                results.innerHTML = '<div class="global-search-empty">Buscando personal…</div>';
                input.setAttribute('aria-expanded', 'true');
                const response = await fetch(`${endpoint}?${query}`, {
                    headers: { Accept: 'application/json' },
                    signal: searchRequest.signal
                });
                if (!response.ok) throw new Error('No fue posible consultar el directorio.');
                const payload = await response.json();
                renderSearchResults(Array.isArray(payload.items) ? payload.items : []);
            } catch (error) {
                if (error.name === 'AbortError') return;
                renderSearchResults([]);
            }
        }, 220);
    };

    form?.addEventListener('submit', event => {
        const query = input?.value.trim() ?? '';
        if (!query && !status?.value) {
            event.preventDefault();
            input?.focus();
            requestSearchResults();
        }
    });
    input?.addEventListener('focus', requestSearchResults);
    input?.addEventListener('input', requestSearchResults);
    status?.addEventListener('change', requestSearchResults);
    input?.addEventListener('keydown', event => {
        if (!results || results.hidden || event.key !== 'ArrowDown') return;
        const first = results.querySelector('[data-search-option]');
        if (first) {
            event.preventDefault();
            first.focus();
        }
    });
    results?.addEventListener('keydown', event => {
        const options = [...results.querySelectorAll('[data-search-option]')];
        const index = options.indexOf(document.activeElement);
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            options[Math.min(index + 1, options.length - 1)]?.focus();
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (index <= 0) input?.focus(); else options[index - 1]?.focus();
        } else if (event.key === 'Escape') {
            closeSearchResults();
            input?.focus();
        }
    });

    const themeToggle = document.getElementById('themeToggle');
    const updateThemeButton = theme => {
        if (!themeToggle) return;
        const dark = theme === 'dark';
        themeToggle.setAttribute('aria-pressed', dark ? 'true' : 'false');
        themeToggle.setAttribute('aria-label', dark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
        themeToggle.title = dark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';
    };

    updateThemeButton(document.documentElement.dataset.theme || 'light');
    themeToggle?.addEventListener('click', () => {
        const current = document.documentElement.dataset.theme || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.dataset.theme = next;
        try {
            localStorage.setItem('apm.theme', next);
        } catch (_) {
            // El tema sigue funcionando durante la sesión si el almacenamiento está bloqueado.
        }
        updateThemeButton(next);
    });


    const popovers = [
        { button: document.getElementById('notificationToggle'), panel: document.getElementById('notificationPanel') },
        { button: document.getElementById('profileToggle'), panel: document.getElementById('profilePanel') }
    ].filter(item => item.button && item.panel);

    const closePopover = (item, restoreFocus = false) => {
        item.panel.hidden = true;
        item.button.setAttribute('aria-expanded', 'false');
        item.button.closest('.topbar-popover')?.classList.remove('is-open');
        if (restoreFocus) item.button.focus();
    };

    const openPopover = item => {
        popovers.forEach(other => {
            if (other !== item) closePopover(other);
        });
        item.panel.hidden = false;
        item.button.setAttribute('aria-expanded', 'true');
        item.button.closest('.topbar-popover')?.classList.add('is-open');
    };

    popovers.forEach(item => {
        item.button.addEventListener('click', event => {
            event.stopPropagation();
            item.panel.hidden ? openPopover(item) : closePopover(item);
        });
        item.panel.addEventListener('click', event => event.stopPropagation());
    });

    document.addEventListener('click', event => {
        popovers.forEach(item => closePopover(item));
        if (!form?.contains(event.target)) closeSearchResults();
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        closeSearchResults();
        const openItem = popovers.find(item => !item.panel.hidden);
        if (openItem) closePopover(openItem, true);
    });
});
