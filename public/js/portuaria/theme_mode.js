(function () {
    'use strict';

    const STORAGE_KEY = 'apm_theme_mode';
    const STYLE_ID = 'apm-dark-mode-runtime-style';

    function injectDarkStyle() {
        if (document.getElementById(STYLE_ID)) {
            return;
        }

        const style = document.createElement('style');
        style.id = STYLE_ID;

        style.textContent = `
/* =========================================================
   MODO OSCURO RUNTIME FINAL
   Corrige Select2, cajas internas y fondos blancos persistentes
========================================================= */

html[data-theme="dark"],
html[data-theme="dark"] body,
body.portal-dark-mode {
    background-color: #0b1220 !important;
    color: #e5e7eb !important;
}

/* Layout principal */
html[data-theme="dark"] .apm-layout,
html[data-theme="dark"] .apm-main,
html[data-theme="dark"] main,
html[data-theme="dark"] .container,
html[data-theme="dark"] .container-fluid,
body.portal-dark-mode .apm-layout,
body.portal-dark-mode .apm-main,
body.portal-dark-mode main,
body.portal-dark-mode .container,
body.portal-dark-mode .container-fluid {
    background-color: #0b1220 !important;
    color: #e5e7eb !important;
}

/* Topbar */
html[data-theme="dark"] .apm-topbar,
html[data-theme="dark"] .navbar.apm-topbar,
body.portal-dark-mode .apm-topbar,
body.portal-dark-mode .navbar.apm-topbar {
    background-color: #0f172a !important;
    color: #e5e7eb !important;
    border-bottom-color: #334155 !important;
}

html[data-theme="dark"] .portal-header-logo h5,
html[data-theme="dark"] .portal-user-name,
body.portal-dark-mode .portal-header-logo h5,
body.portal-dark-mode .portal-user-name {
    color: #f8fafc !important;
}

html[data-theme="dark"] .portal-header-logo small,
html[data-theme="dark"] .portal-user-info small,
body.portal-dark-mode .portal-header-logo small,
body.portal-dark-mode .portal-user-info small {
    color: #cbd5e1 !important;
}

html[data-theme="dark"] .portal-menu-toggle,
html[data-theme="dark"] #btnMenuPrincipal,
body.portal-dark-mode .portal-menu-toggle,
body.portal-dark-mode #btnMenuPrincipal {
    background-color: #1e293b !important;
    color: #f8fafc !important;
}

html[data-theme="dark"] .portal-notification,
body.portal-dark-mode .portal-notification {
    background-color: #1e293b !important;
    color: #f8fafc !important;
}

/* Sidebar */
html[data-theme="dark"] #sidebarMenu.apm-sidebar,
body.portal-dark-mode #sidebarMenu.apm-sidebar {
    background: linear-gradient(160deg, #06263b, #075985) !important;
}

html[data-theme="dark"] #sidebarMenu .nav-link,
body.portal-dark-mode #sidebarMenu .nav-link {
    color: rgba(255, 255, 255, 0.9) !important;
}

html[data-theme="dark"] #sidebarMenu .nav-link:hover,
html[data-theme="dark"] #sidebarMenu .nav-link.active,
body.portal-dark-mode #sidebarMenu .nav-link:hover,
body.portal-dark-mode #sidebarMenu .nav-link.active {
    background-color: rgba(255, 255, 255, 0.16) !important;
    color: #ffffff !important;
}

/* Cards, paneles y contenedores */
html[data-theme="dark"] .card,
html[data-theme="dark"] .card-body,
html[data-theme="dark"] .card-header,
html[data-theme="dark"] .card-footer,
html[data-theme="dark"] .modal-content,
html[data-theme="dark"] .dropdown-menu,
html[data-theme="dark"] .bg-white,
html[data-theme="dark"] .bg-light,
html[data-theme="dark"] .bg-body,
html[data-theme="dark"] .bg-body-tertiary,
body.portal-dark-mode .card,
body.portal-dark-mode .card-body,
body.portal-dark-mode .card-header,
body.portal-dark-mode .card-footer,
body.portal-dark-mode .modal-content,
body.portal-dark-mode .dropdown-menu,
body.portal-dark-mode .bg-white,
body.portal-dark-mode .bg-light,
body.portal-dark-mode .bg-body,
body.portal-dark-mode .bg-body-tertiary {
    background-color: #172033 !important;
    color: #e5e7eb !important;
    border-color: #334155 !important;
}

/* Cajas internas específicas de tus bitácoras */
html[data-theme="dark"] .apm-br-info-compact,
html[data-theme="dark"] .apm-br-form-compact,
html[data-theme="dark"] .apm-cctv-inventario-box,
html[data-theme="dark"] .apm-cctv-card,
html[data-theme="dark"] .apm-cctv-soft-card,
body.portal-dark-mode .apm-br-info-compact,
body.portal-dark-mode .apm-br-form-compact,
body.portal-dark-mode .apm-cctv-inventario-box,
body.portal-dark-mode .apm-cctv-card,
body.portal-dark-mode .apm-cctv-soft-card {
    background-color: #111827 !important;
    color: #e5e7eb !important;
    border-color: #334155 !important;
}

/* Formularios normales */
html[data-theme="dark"] input,
html[data-theme="dark"] select,
html[data-theme="dark"] textarea,
html[data-theme="dark"] .form-control,
html[data-theme="dark"] .form-select,
body.portal-dark-mode input,
body.portal-dark-mode select,
body.portal-dark-mode textarea,
body.portal-dark-mode .form-control,
body.portal-dark-mode .form-select {
    background-color: #0f172a !important;
    color: #f8fafc !important;
    border-color: #475569 !important;
}

html[data-theme="dark"] input:focus,
html[data-theme="dark"] select:focus,
html[data-theme="dark"] textarea:focus,
html[data-theme="dark"] .form-control:focus,
html[data-theme="dark"] .form-select:focus,
body.portal-dark-mode input:focus,
body.portal-dark-mode select:focus,
body.portal-dark-mode textarea:focus,
body.portal-dark-mode .form-control:focus,
body.portal-dark-mode .form-select:focus {
    background-color: #0f172a !important;
    color: #ffffff !important;
    border-color: #38bdf8 !important;
    box-shadow: 0 0 0 0.18rem rgba(56, 189, 248, 0.25) !important;
}

html[data-theme="dark"] input::placeholder,
html[data-theme="dark"] textarea::placeholder,
html[data-theme="dark"] .form-control::placeholder,
body.portal-dark-mode input::placeholder,
body.portal-dark-mode textarea::placeholder,
body.portal-dark-mode .form-control::placeholder {
    color: #94a3b8 !important;
}

/* Disabled / readonly */
html[data-theme="dark"] input[readonly],
html[data-theme="dark"] input:disabled,
html[data-theme="dark"] select:disabled,
html[data-theme="dark"] textarea:disabled,
html[data-theme="dark"] .form-control[readonly],
html[data-theme="dark"] .form-control:disabled,
html[data-theme="dark"] .form-select:disabled,
body.portal-dark-mode input[readonly],
body.portal-dark-mode input:disabled,
body.portal-dark-mode select:disabled,
body.portal-dark-mode textarea:disabled,
body.portal-dark-mode .form-control[readonly],
body.portal-dark-mode .form-control:disabled,
body.portal-dark-mode .form-select:disabled {
    background-color: #111827 !important;
    color: #cbd5e1 !important;
    border-color: #334155 !important;
    opacity: 1 !important;
}

/* Select2 Bootstrap 5 - ESTE ES EL QUE TE QUEDABA BLANCO */
html[data-theme="dark"] .select2-container--bootstrap-5 .select2-selection,
html[data-theme="dark"] .select2-container--bootstrap-5 .select2-selection--single,
html[data-theme="dark"] .select2-container--bootstrap-5 .select2-selection--multiple,
html[data-theme="dark"] span.select2-selection.select2-selection--single,
body.portal-dark-mode .select2-container--bootstrap-5 .select2-selection,
body.portal-dark-mode .select2-container--bootstrap-5 .select2-selection--single,
body.portal-dark-mode .select2-container--bootstrap-5 .select2-selection--multiple,
body.portal-dark-mode span.select2-selection.select2-selection--single {
    background-color: #0f172a !important;
    color: #f8fafc !important;
    border-color: #475569 !important;
    box-shadow: none !important;
}

/* Texto interno de Select2 */
html[data-theme="dark"] .select2-container--bootstrap-5 .select2-selection__rendered,
html[data-theme="dark"] .select2-container--bootstrap-5 .select2-selection__placeholder,
html[data-theme="dark"] .select2-selection__rendered,
html[data-theme="dark"] .select2-selection__placeholder,
body.portal-dark-mode .select2-container--bootstrap-5 .select2-selection__rendered,
body.portal-dark-mode .select2-container--bootstrap-5 .select2-selection__placeholder,
body.portal-dark-mode .select2-selection__rendered,
body.portal-dark-mode .select2-selection__placeholder {
    color: #cbd5e1 !important;
}

/* Flecha de Select2 */
html[data-theme="dark"] .select2-container--bootstrap-5 .select2-selection__arrow b,
body.portal-dark-mode .select2-container--bootstrap-5 .select2-selection__arrow b {
    border-color: #cbd5e1 transparent transparent transparent !important;
}

/* Dropdown de Select2 */
html[data-theme="dark"] .select2-container--bootstrap-5 .select2-dropdown,
html[data-theme="dark"] .select2-dropdown,
body.portal-dark-mode .select2-container--bootstrap-5 .select2-dropdown,
body.portal-dark-mode .select2-dropdown {
    background-color: #111827 !important;
    color: #f8fafc !important;
    border-color: #334155 !important;
}

html[data-theme="dark"] .select2-container--bootstrap-5 .select2-search__field,
html[data-theme="dark"] .select2-search__field,
body.portal-dark-mode .select2-container--bootstrap-5 .select2-search__field,
body.portal-dark-mode .select2-search__field {
    background-color: #0f172a !important;
    color: #f8fafc !important;
    border-color: #475569 !important;
}

html[data-theme="dark"] .select2-results__option,
body.portal-dark-mode .select2-results__option {
    background-color: #111827 !important;
    color: #e5e7eb !important;
}

html[data-theme="dark"] .select2-results__option--highlighted,
html[data-theme="dark"] .select2-results__option--selected,
body.portal-dark-mode .select2-results__option--highlighted,
body.portal-dark-mode .select2-results__option--selected {
    background-color: #075985 !important;
    color: #ffffff !important;
}

/* Select2 cuando está enfocado */
html[data-theme="dark"] .select2-container--bootstrap-5.select2-container--focus .select2-selection,
html[data-theme="dark"] .select2-container--bootstrap-5.select2-container--open .select2-selection,
body.portal-dark-mode .select2-container--bootstrap-5.select2-container--focus .select2-selection,
body.portal-dark-mode .select2-container--bootstrap-5.select2-container--open .select2-selection {
    background-color: #0f172a !important;
    color: #ffffff !important;
    border-color: #38bdf8 !important;
    box-shadow: 0 0 0 0.18rem rgba(56, 189, 248, 0.25) !important;
}

/* Tablas */
html[data-theme="dark"] table,
html[data-theme="dark"] .table,
html[data-theme="dark"] .table-responsive,
body.portal-dark-mode table,
body.portal-dark-mode .table,
body.portal-dark-mode .table-responsive {
    background-color: #111827 !important;
    color: #e5e7eb !important;
    border-color: #334155 !important;
}

html[data-theme="dark"] table th,
html[data-theme="dark"] .table th,
body.portal-dark-mode table th,
body.portal-dark-mode .table th {
    background-color: #1f3a5f !important;
    color: #f8fafc !important;
    border-color: #334155 !important;
}

html[data-theme="dark"] table td,
html[data-theme="dark"] .table td,
body.portal-dark-mode table td,
body.portal-dark-mode .table td {
    background-color: #111827 !important;
    color: #e5e7eb !important;
    border-color: #334155 !important;
}

/* Labels y textos */
html[data-theme="dark"] label,
html[data-theme="dark"] .form-label,
html[data-theme="dark"] h1,
html[data-theme="dark"] h2,
html[data-theme="dark"] h3,
html[data-theme="dark"] h4,
html[data-theme="dark"] h5,
html[data-theme="dark"] h6,
body.portal-dark-mode label,
body.portal-dark-mode .form-label,
body.portal-dark-mode h1,
body.portal-dark-mode h2,
body.portal-dark-mode h3,
body.portal-dark-mode h4,
body.portal-dark-mode h5,
body.portal-dark-mode h6 {
    color: #f8fafc !important;
}

html[data-theme="dark"] .text-muted,
html[data-theme="dark"] .form-text,
html[data-theme="dark"] small,
body.portal-dark-mode .text-muted,
body.portal-dark-mode .form-text,
body.portal-dark-mode small {
    color: #cbd5e1 !important;
}

html[data-theme="dark"] .text-primary,
body.portal-dark-mode .text-primary {
    color: #60a5fa !important;
}

/* Botones claros */
html[data-theme="dark"] .btn-light,
html[data-theme="dark"] .btn-outline-light,
body.portal-dark-mode .btn-light,
body.portal-dark-mode .btn-outline-light {
    background-color: #1e293b !important;
    color: #f8fafc !important;
    border-color: #475569 !important;
}

/* Botón regresar */
html[data-theme="dark"] .btn-regresar-dashboard,
body.portal-dark-mode .btn-regresar-dashboard {
    background-color: #111827 !important;
    color: #93c5fd !important;
    border-color: #334155 !important;
}

/* Alertas */
html[data-theme="dark"] .alert-info,
html[data-theme="dark"] .bg-info-subtle,
html[data-theme="dark"] .text-bg-info,
body.portal-dark-mode .alert-info,
body.portal-dark-mode .bg-info-subtle,
body.portal-dark-mode .text-bg-info {
    background-color: #083344 !important;
    color: #cffafe !important;
    border-color: #155e75 !important;
}

/* Calendarios e íconos de hora */
html[data-theme="dark"] input[type="date"]::-webkit-calendar-picker-indicator,
html[data-theme="dark"] input[type="time"]::-webkit-calendar-picker-indicator,
body.portal-dark-mode input[type="date"]::-webkit-calendar-picker-indicator,
body.portal-dark-mode input[type="time"]::-webkit-calendar-picker-indicator {
    filter: invert(1) brightness(1.8);
}

/* Fondos blancos escritos directo en style="" */
html[data-theme="dark"] [style*="background:#fff"],
html[data-theme="dark"] [style*="background: #fff"],
html[data-theme="dark"] [style*="background:#ffffff"],
html[data-theme="dark"] [style*="background: #ffffff"],
html[data-theme="dark"] [style*="background-color:#fff"],
html[data-theme="dark"] [style*="background-color: #fff"],
html[data-theme="dark"] [style*="background-color:#ffffff"],
html[data-theme="dark"] [style*="background-color: #ffffff"],
body.portal-dark-mode [style*="background:#fff"],
body.portal-dark-mode [style*="background: #fff"],
body.portal-dark-mode [style*="background:#ffffff"],
body.portal-dark-mode [style*="background: #ffffff"],
body.portal-dark-mode [style*="background-color:#fff"],
body.portal-dark-mode [style*="background-color: #fff"],
body.portal-dark-mode [style*="background-color:#ffffff"],
body.portal-dark-mode [style*="background-color: #ffffff"] {
    background-color: #172033 !important;
    color: #e5e7eb !important;
    border-color: #334155 !important;
}
        `;

        document.head.appendChild(style);
    }

    function getTheme() {
        try {
            return localStorage.getItem(STORAGE_KEY) || 'light';
        } catch (e) {
            return 'light';
        }
    }

    function setTheme(theme) {
        try {
            localStorage.setItem(STORAGE_KEY, theme);
        } catch (e) {
            // Si localStorage falla, igual aplicamos el tema visualmente.
        }

        applyTheme(theme);
    }

    function applyTheme(theme) {
        const isDark = theme === 'dark';

        injectDarkStyle();

        document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
        document.documentElement.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');

        document.documentElement.classList.toggle('portal-dark-mode', isDark);

        if (document.body) {
            document.body.classList.toggle('portal-dark-mode', isDark);
        }

        updateButton(isDark);
    }

    function updateButton(isDark) {
        const text = document.getElementById('themeToggleText');
        const icon = document.getElementById('themeToggleIcon');

        if (text) {
            text.textContent = isDark ? 'Modo claro' : 'Modo oscuro';
        }

        if (icon) {
            icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon-stars';
        }
    }

    injectDarkStyle();
    applyTheme(getTheme());

    document.addEventListener('DOMContentLoaded', function () {
        injectDarkStyle();
        applyTheme(getTheme());

        const btn = document.getElementById('themeToggle');

        if (!btn) {
            return;
        }

        btn.addEventListener('click', function () {
            const currentTheme = getTheme();
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';

            setTheme(nextTheme);
        });
    });
})();