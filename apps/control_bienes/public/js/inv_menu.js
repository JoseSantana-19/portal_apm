/**
 * INV_MENU.JS - Menú Lateral Independiente (Web Component)
 * Sistema de Inventario Portuario - MVC
 * ============================================================
 * Componente web reutilizable para el sidebar de navegación.
 * Se carga en todas las vistas de forma independiente.
 * ============================================================
 */

// Logo institucional único (imgs/logoapm.png en la raíz de portal_apm),
// resuelto contra la URL propia del script para no depender de la
// profundidad de la página actual (el router usa index.php?route=...).
const __INV_MENU_SCRIPT_URL = document.currentScript ? document.currentScript.src : '';

class SidebarMenu extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        const logoUrl = new URL('../../../../imgs/logoapm.png', __INV_MENU_SCRIPT_URL).href;
        this.innerHTML = `
            <div class="sidebar-header">
                <img src="${logoUrl}" alt="Logo APM Portuario" class="sidebar-logo">
                <h2>Sistema Inventario</h2>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-section">Operaciones de Terminal</div>
                <a href="inv_inventario.html" class="menu-item" id="nav-inventario" title="Inventario General">
                    <i class="fa-solid fa-ship"></i>
                    <span>Inventario General</span>
                </a>
                <a href="inv_items.html" class="menu-item" id="nav-items" title="Catálogo de Ítems">
                    <i class="fa-solid fa-box"></i>
                    <span>Catálogo de Ítems</span>
                </a>

                <div class="menu-section">Arquitectura de Datos</div>
                <a href="th_cabeceras.html" class="menu-item" id="nav-cabeceras" title="Tablas de Cabecera">
                    <i class="fa-solid fa-table-columns"></i>
                    <span>Tablas de Cabecera</span>
                </a>
                <a href="seq_secuenciales.html" class="menu-item" id="nav-secuenciales" title="Secuenciales de Índice">
                    <i class="fa-solid fa-list-ol"></i>
                    <span>Secuenciales de Índice</span>
                </a>

                <div class="menu-section">Sistema</div>
                <a href="bit_bitacora.html" class="menu-item" id="nav-bitacora" title="Bitácora del Sistema">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Bitácora del Sistema</span>
                </a>
                <a href="acc_usuarios.html" class="menu-item" id="nav-usuarios" title="Gestión de Usuarios">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>Gestión de Usuarios</span>
                </a>
                <a href="#" class="menu-item" id="nav-config" title="Configuración Portuaria">
                    <i class="fa-solid fa-gear"></i>
                    <span>Configuración</span>
                </a>
            </div>

            <div class="sidebar-footer">
                <div class="sidebar-version">
                    <i class="fa-solid fa-code-branch"></i>
                    <span>v2.0.0 MVC</span>
                </div>
            </div>
        `;

        this._setActiveMenu();
        this._bindMenuEvents();
    }

    _setActiveMenu() {
        const currentPath = window.location.pathname;
        const fileName = currentPath.split('/').pop();
        
        const menuMap = {
            'inv_inventario.html': 'nav-inventario',
            'inv_items.html': 'nav-items',
            'th_cabeceras.html': 'nav-cabeceras',
            'bit_bitacora.html': 'nav-bitacora',
            'acc_usuarios.html': 'nav-usuarios',
            'seq_secuenciales.html': 'nav-secuenciales'
        };

        const activeId = menuMap[fileName] || 'nav-inventario';
        const activeItem = this.querySelector(`#${activeId}`);
        if (activeItem) activeItem.classList.add('active');
    }

    _bindMenuEvents() {
        this.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', (e) => {
                this.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                e.currentTarget.classList.add('active');
            });
        });
    }
}

// Registrar el componente web
customElements.define('sidebar-menu', SidebarMenu);

/**
 * Inicialización global del sidebar (hamburguesa + teclado)
 */
document.addEventListener('DOMContentLoaded', () => {
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const sidebar = document.getElementById('sidebar');

    if (hamburgerBtn && sidebar) {
        hamburgerBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    }

    // Ctrl+K = Búsqueda global
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const search = document.querySelector('.global-search input');
            if (search) search.focus();
        }
    });
});
