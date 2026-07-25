// Logo institucional único (imgs/logoapm.png en la raíz de portal_apm),
// resuelto contra la URL propia del script para no depender de la
// profundidad de la página actual (el router usa index.php?route=...).
const __SIDEBAR_MENU_SCRIPT_URL = document.currentScript ? document.currentScript.src : '';

class SidebarMenu extends HTMLElement {
    constructor() {
        super();
    }

    connectedCallback() {
        const logoUrl = new URL('../../../../imgs/logoapm.png', __SIDEBAR_MENU_SCRIPT_URL).href;
        this.innerHTML = `
            <div class="sidebar-header">
                <img src="${logoUrl}" alt="Logo APM Portuario" class="sidebar-logo">
                <h2>Sistema Inventario</h2>
            </div>
            
            <div class="sidebar-menu">
                <div class="menu-section">Operaciones de Terminal</div>
                <a href="inventario.html" class="menu-item" id="nav-inventario" title="Inventario General">
                    <i class="fa-solid fa-ship"></i>
                    <span>Inventario General</span>
                </a>
                <a href="items.html" class="menu-item" id="nav-items" title="Catálogo de Ítems">
                    <i class="fa-solid fa-box"></i>
                    <span>Catálogo de Ítems</span>
                </a>
                <a href="#" class="menu-item" title="Maquinaria y Grúas (RTG)">
                    <i class="fa-solid fa-truck-monster"></i>
                    <span>Maquinaria y Grúas</span>
                </a>
                <a href="#" class="menu-item" title="Inspecciones y Mantenimiento">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>Mantenimiento Muelle</span>
                </a>

                <div class="menu-section">Arquitectura de Datos</div>
                <a href="#" class="menu-item" title="Terminales y Muelles (BD)">
                    <i class="fa-solid fa-anchor"></i>
                    <span>Terminales y Muelles</span>
                </a>
                <a href="#" class="menu-item" title="Parámetros Aduaneros">
                    <i class="fa-solid fa-file-invoice"></i>
                    <span>Parámetros Aduaneros</span>
                </a>
                <a href="#" class="menu-item" title="Secuenciales de Manifiestos">
                    <i class="fa-solid fa-list-ol"></i>
                    <span>Secuenciales de Índice</span>
                </a>
                
                <div class="menu-section">Sistema</div>
                <a href="#" class="menu-item" title="Auditoría de Saltos">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Auditoría de Saltos</span>
                </a>
                <a href="#" class="menu-item" title="Gestión de Operadores">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>Gestión de Operadores</span>
                </a>
                <a href="#" class="menu-item" title="Configuración Portuaria">
                    <i class="fa-solid fa-gear"></i>
                    <span>Configuración Portuaria</span>
                </a>
            </div>
        `;

        // Lógica para marcar como activo el menú
        const currentPath = window.location.pathname;
        if(currentPath.includes('items.html')) {
            const navItems = this.querySelector('#nav-items');
            if(navItems) navItems.classList.add('active');
        } else if (currentPath.includes('inventario.html')) {
            const navInv = this.querySelector('#nav-inventario');
            if(navInv) navInv.classList.add('active');
        } else {
            // Default o Index
            const navInv = this.querySelector('#nav-inventario');
            if(navInv) navInv.classList.add('active');
        }

        this.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', (e) => {
                this.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
                e.currentTarget.classList.add('active');
            });
        });
    }
}

// Registrar el componente web independiente
customElements.define('sidebar-menu', SidebarMenu);
