(() => {
    const cards = [...document.querySelectorAll('[data-role-card]')];
    if (!cards.length) return;

    const setOpen = (card, open, remember = true) => {
        const toggle = card.querySelector('[data-role-toggle]');
        const panel = card.querySelector('[data-role-panel]');
        card.classList.toggle('is-open', open);
        toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (panel) panel.inert = !open;
        if (remember) {
            try { sessionStorage.setItem('apm.rbac.openRole', open ? (card.dataset.roleId || '') : ''); } catch (_) {}
        }
    };

    cards.forEach((card) => {
        setOpen(card, false, false);
        card.querySelector('[data-role-toggle]')?.addEventListener('click', () => {
            const willOpen = !card.classList.contains('is-open');
            cards.forEach((other) => setOpen(other, false, false));
            setOpen(card, willOpen);
        });
    });

    let savedRole = '';
    try { savedRole = sessionStorage.getItem('apm.rbac.openRole') || ''; } catch (_) {}
    const requestedRole = new URLSearchParams(location.search).get('rol') || savedRole;
    if (requestedRole) {
        const selected = cards.find((card) => card.dataset.roleId === requestedRole);
        if (selected) setOpen(selected, true, false);
    }

    const updateRowMaster = (row) => {
        const master = row.querySelector('[data-row-toggle]');
        const permissions = [...row.querySelectorAll('[data-permission]')];
        if (!master || !permissions.length) return;
        const checked = permissions.filter((input) => input.checked).length;
        master.checked = checked === permissions.length;
        master.indeterminate = checked > 0 && checked < permissions.length;
    };

    const updateCategoryMaster = (group) => {
        const master = group.querySelector('[data-category-toggle]');
        const permissions = [...group.querySelectorAll('[data-permission]')];
        if (!master || !permissions.length) return;
        const checked = permissions.filter((input) => input.checked).length;
        master.checked = checked === permissions.length;
        master.indeterminate = checked > 0 && checked < permissions.length;
    };

    document.querySelectorAll('[data-permission]').forEach((input) => {
        input.addEventListener('change', () => {
            updateRowMaster(input.closest('tr'));
            updateCategoryMaster(input.closest('[data-permission-group]'));
        });
    });

    document.querySelectorAll('[data-row-toggle]').forEach((master) => {
        const row = master.closest('tr');
        master.addEventListener('change', () => {
            row.querySelectorAll('[data-permission]').forEach((input) => {
                if (!input.disabled) input.checked = master.checked;
            });
            updateRowMaster(row);
            updateCategoryMaster(master.closest('[data-permission-group]'));
        });
        updateRowMaster(row);
    });

    document.querySelectorAll('[data-category-toggle]').forEach((master) => {
        const group = master.closest('[data-permission-group]');
        master.addEventListener('change', () => {
            group.querySelectorAll('[data-permission]').forEach((input) => {
                if (!input.disabled) input.checked = master.checked;
            });
            group.querySelectorAll('tbody tr').forEach(updateRowMaster);
            updateCategoryMaster(group);
        });
        updateCategoryMaster(group);
    });
})();
