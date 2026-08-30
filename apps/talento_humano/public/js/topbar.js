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
    form?.addEventListener('submit', event => {
        const query = input?.value.trim() ?? '';
        if (!query) {
            event.preventDefault();
            input?.focus();
        }
    });

    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = themeToggle?.querySelector('i');
    const updateThemeButton = theme => {
        if (!themeToggle || !themeIcon) return;
        const dark = theme === 'dark';
        themeIcon.className = dark ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
        const label = dark ? 'Activar modo claro' : 'Activar modo oscuro';
        themeToggle.setAttribute('aria-label', label);
        themeToggle.title = label;
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

    document.addEventListener('click', () => popovers.forEach(item => closePopover(item)));
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        const openItem = popovers.find(item => !item.panel.hidden);
        if (openItem) closePopover(openItem, true);
    });
});
