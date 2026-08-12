/* layout_sidebar.js – Control y persistencia de la barra lateral */

const SIDEBAR_SCROLL_KEY = 'apm.sidebar.scrollTop';
const SIDEBAR_HIDDEN_KEY = 'apm.sidebar.hidden';

function sidebarStorageGet(key) {
    try { return window.sessionStorage.getItem(key); } catch (_) { return null; }
}

function sidebarStorageSet(key, value) {
    try { window.sessionStorage.setItem(key, String(value)); } catch (_) { /* almacenamiento no disponible */ }
}

function saveSidebarState() {
    const body = document.querySelector('.sidebar-body');
    if (body) sidebarStorageSet(SIDEBAR_SCROLL_KEY, Math.max(0, Math.round(body.scrollTop)));
    if (window.innerWidth > 1024) {
        sidebarStorageSet(SIDEBAR_HIDDEN_KEY, document.body.classList.contains('sidebar-hidden') ? '1' : '0');
    }
}

function openSidebar() {
    const isMobile = window.innerWidth <= 1024;
    document.body.classList.remove('sidebar-hidden');
    if (isMobile) document.body.classList.add('sidebar-open');
    syncSidebarToggleState();
    saveSidebarState();
}

function closeSidebar() {
    document.body.classList.remove('sidebar-open');
    document.body.classList.add('sidebar-hidden');
    syncSidebarToggleState();
    saveSidebarState();
}

function toggleSidebar() {
    document.body.classList.contains('sidebar-hidden') ? openSidebar() : closeSidebar();
}

function syncSidebarToggleState() {
    const btn  = document.getElementById('sidebarToggle');
    const icon = document.getElementById('sidebarToggleIcon');
    if (!btn || !icon) return;
    const isHidden = document.body.classList.contains('sidebar-hidden');
    icon.className = 'bi bi-x-lg';
    btn.classList.toggle('is-open', !isHidden);
    btn.setAttribute('aria-expanded', !isHidden);
    btn.title = 'Cerrar menú lateral';
}

window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) document.body.classList.remove('sidebar-open');
    syncSidebarToggleState();
});

document.addEventListener('DOMContentLoaded', () => {
    const body = document.querySelector('.sidebar-body');
    const savedScrollValue = sidebarStorageGet(SIDEBAR_SCROLL_KEY);
    const savedScroll = savedScrollValue === null ? null : Number(savedScrollValue);
    const savedHidden = sidebarStorageGet(SIDEBAR_HIDDEN_KEY);

    if (window.innerWidth > 1024 && savedHidden !== null) {
        document.body.classList.toggle('sidebar-hidden', savedHidden === '1');
    }

    if (body) {
        requestAnimationFrame(() => {
            if (savedScroll !== null && Number.isFinite(savedScroll) && savedScroll >= 0) {
                body.scrollTop = savedScroll;
            } else {
                document.querySelector('.sidebar .nav-item.active')?.scrollIntoView({ block: 'nearest' });
            }
        });

        let scrollFrame = null;
        body.addEventListener('scroll', () => {
            if (scrollFrame !== null) cancelAnimationFrame(scrollFrame);
            scrollFrame = requestAnimationFrame(() => {
                scrollFrame = null;
                sidebarStorageSet(SIDEBAR_SCROLL_KEY, Math.max(0, Math.round(body.scrollTop)));
            });
        }, { passive: true });
    }

    document.querySelectorAll('.sidebar a.nav-item').forEach((link) => {
        link.addEventListener('click', saveSidebarState);
    });
    syncSidebarToggleState();
});

window.addEventListener('pagehide', saveSidebarState);
