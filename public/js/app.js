/**
 * Portal APM — SPA Orchestrator
 * Intercepts nav clicks → fetch → injects #main-content, no full reload.
 */
(function () {
    'use strict';

    const main     = document.getElementById('main-content');
    const loader   = document.getElementById('spa-loader');
    const sidebar  = document.getElementById('sidebar');
    const sidebarToggleBtn = document.getElementById('sidebar-toggle');

    /* ── Loader ────────────────────────────────────────── */
    function showLoader()  { loader && loader.classList.add('active'); }
    function hideLoader()  { loader && loader.classList.remove('active'); }

    /* ── Sidebar toggle ────────────────────────────────── */
    sidebarToggleBtn && sidebarToggleBtn.addEventListener('click', () => {
        document.documentElement.classList.toggle('sidebar-collapsed');
        sidebar && sidebar.classList.toggle('collapsed');
    });

    /* ── Breadcrumb ────────────────────────────────────── */
    function updateBreadcrumb(title) {
        const el = document.getElementById('breadcrumb-text');
        if (el && title) el.textContent = title;
    }

    /* ── Active nav link ───────────────────────────────── */
    function updateActiveNav(url) {
        const path = new URL(url, window.location.origin).pathname;
        document.querySelectorAll('.nav-link[href]').forEach(a => {
            const linkPath = new URL(a.href, window.location.origin).pathname;
            a.classList.toggle('active', path.startsWith(linkPath) && linkPath !== '/');
        });
    }

    /* ── SPA navigation ────────────────────────────────── */
    async function navigate(url, pushState = true) {
        if (!main) return;
        showLoader();
        try {
            const res = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': getCsrfToken(),
                },
                credentials: 'same-origin',
            });

            if (res.redirected) {
                window.location.href = res.url;
                return;
            }
            if (!res.ok) {
                main.innerHTML = `<div class="empty-state"><i class="fa-solid fa-circle-exclamation"></i><p>Error ${res.status}</p></div>`;
                return;
            }

            const html = await res.text();
            main.innerHTML = html;

            // Execute inline scripts in the new content
            main.querySelectorAll('script').forEach(oldScript => {
                const s = document.createElement('script');
                s.textContent = oldScript.textContent;
                document.head.appendChild(s).parentNode.removeChild(s);
            });

            if (pushState) {
                history.pushState({ url }, document.title, url);
            }

            updateActiveNav(url);
            updateBreadcrumb(document.title.split('—')[0].trim());

            // Dispatch event so charts.js can init after content load
            document.dispatchEvent(new CustomEvent('spa:loaded', { detail: { url } }));

            main.scrollTo(0, 0);

        } catch (e) {
            main.innerHTML = `<div class="empty-state"><i class="fa-solid fa-wifi"></i><p>Error de conexión. Intente nuevamente.</p></div>`;
            console.error('[SPA]', e);
        } finally {
            hideLoader();
        }
    }

    /* ── Intercept clicks on [data-spa] links ──────────── */
    document.addEventListener('click', e => {
        const link = e.target.closest('a[data-spa]');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript')) return;
        if (link.target === '_blank') return;

        e.preventDefault();
        navigate(href);
    });

    /* ── Browser back/forward ──────────────────────────── */
    window.addEventListener('popstate', e => {
        if (e.state && e.state.url) {
            navigate(e.state.url, false);
        }
    });

    /* ── CSRF token helper ─────────────────────────────── */
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    /* ── Theme switcher ────────────────────────────────── */
    document.addEventListener('click', e => {
        const btn = e.target.closest('.theme-toggle-btn[data-theme-target]');
        if (!btn) return;

        const tema = btn.dataset.themeTarget;
        document.documentElement.dataset.theme = tema;

        document.querySelectorAll('.theme-toggle-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.themeTarget === tema);
        });

        // Persist via AJAX
        const fd = new FormData();
        fd.append('tema', tema);
        fd.append('_token', getCsrfToken());
        fetch(APP_URL + '/set-theme', {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        }).catch(() => {});
    });

    /* ── Sidebar submenu accordion ─────────────────────── */
    document.addEventListener('click', e => {
        const btn = e.target.closest('[data-submenu]');
        if (!btn) return;

        const targetId = btn.dataset.submenu;
        const submenu  = document.getElementById(targetId);
        if (!submenu) return;

        const isOpen = submenu.classList.contains('open');

        // Close all other open submenus
        document.querySelectorAll('.nav-submenu.open').forEach(s => {
            if (s.id !== targetId) {
                s.classList.remove('open');
                s.previousElementSibling && s.previousElementSibling.classList.remove('open');
            }
        });

        submenu.classList.toggle('open', !isOpen);
        btn.classList.toggle('open', !isOpen);
    });

    /* ── Notifications dropdown ────────────────────────── */
    const notifBtn  = document.getElementById('notif-btn');
    const notifDrop = document.getElementById('notif-dropdown');

    notifBtn && notifBtn.addEventListener('click', e => {
        e.stopPropagation();
        const isHidden = notifDrop.hidden;
        closeAllDropdowns();
        notifDrop.hidden = !isHidden;
        if (!notifDrop.hidden) loadNotifications();
    });

    async function loadNotifications() {
        const list = document.getElementById('notif-list');
        if (!list) return;
        try {
            const res  = await fetch(APP_URL + '/notificaciones/recientes', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.items || data.items.length === 0) return;

            list.innerHTML = data.items.map(n => `
                <div class="notif-item ${n.leida ? '' : 'unread'}">
                    <div class="notif-item-icon"><i class="${n.icono ?? 'fa-solid fa-bell'}"></i></div>
                    <div class="notif-item-body">
                        <div class="notif-item-text">${escHtml(n.mensaje)}</div>
                        <div class="notif-item-time">${escHtml(n.fecha_relativa)}</div>
                    </div>
                </div>
            `).join('');

            const badge = document.getElementById('notif-badge');
            if (badge) {
                const unread = data.items.filter(n => !n.leida).length;
                badge.textContent = unread;
                badge.classList.toggle('hidden', unread === 0);
            }
        } catch (e) { /* silent */ }
    }

    const markAllBtn = document.getElementById('mark-all-read');
    markAllBtn && markAllBtn.addEventListener('click', async () => {
        await fetch(APP_URL + '/notificaciones/marcar-leidas', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': getCsrfToken() },
            credentials: 'same-origin',
        });
        document.querySelectorAll('.notif-item.unread').forEach(i => i.classList.remove('unread'));
        const badge = document.getElementById('notif-badge');
        if (badge) { badge.textContent = '0'; badge.classList.add('hidden'); }
    });

    /* ── User dropdown ─────────────────────────────────── */
    const userMenuBtn  = document.getElementById('user-menu-btn');
    const userDropdown = document.getElementById('user-dropdown');

    userMenuBtn && userMenuBtn.addEventListener('click', e => {
        e.stopPropagation();
        const isHidden = userDropdown.hidden;
        closeAllDropdowns();
        userDropdown.hidden = !isHidden;
    });

    function closeAllDropdowns() {
        notifDrop  && (notifDrop.hidden  = true);
        userDropdown && (userDropdown.hidden = true);
    }

    document.addEventListener('click', closeAllDropdowns);

    /* ── Toast notifications ───────────────────────────── */
    window.showToast = function(message, type = 'info', duration = 4000) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const icons = { success: 'fa-circle-check', danger: 'fa-circle-exclamation', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `<i class="fa-solid ${icons[type] ?? icons.info}"></i><span>${escHtml(message)}</span>`;

        container.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));

        setTimeout(() => {
            toast.classList.remove('show');
            toast.addEventListener('transitionend', () => toast.remove());
        }, duration);
    };

    /* ── AJAX form submission helper ───────────────────── */
    window.submitForm = async function(formEl, onSuccess) {
        const fd  = new FormData(formEl);
        const res = await fetch(formEl.action, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        });
        const data = await res.json();
        if (data.ok || data.success) {
            onSuccess && onSuccess(data);
        } else {
            showToast(data.message ?? 'Error al procesar la solicitud.', 'danger');
        }
        return data;
    };

    /* ── XSS-safe HTML escaping ────────────────────────── */
    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /* ── Push initial state so back button works ──────── */
    history.replaceState({ url: window.location.href }, document.title, window.location.href);

})();
