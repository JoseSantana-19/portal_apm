/* layout_sidebar.js – Control de apertura/cierre de la barra lateral */

function openSidebar() {
    const isMobile = window.innerWidth <= 1024;
    document.body.classList.remove('sidebar-hidden');
    if (isMobile) document.body.classList.add('sidebar-open');
    syncSidebarToggleState();
}

function closeSidebar() {
    document.body.classList.remove('sidebar-open');
    document.body.classList.add('sidebar-hidden');
    syncSidebarToggleState();
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
