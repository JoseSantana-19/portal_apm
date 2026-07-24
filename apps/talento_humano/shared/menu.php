<?php
/* shared/menu.php – Menú lateral único compartido entre todas las vistas.
   Incluir con: require_once ROOT . '/shared/menu.php';
   Requiere que BASE_URL e IMG_URL estén definidos en config_routes.php. */

// Detectar ruta actual para resaltar el ítem activo
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
if (!function_exists('navActive')) {
    function navActive(string $path, string $uri): string {
        return (str_contains($uri, $path) ? ' active' : '');
    }
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-wrap">
            <img src="<?= IMG_URL ?>/logoapm.png" alt="Logo APM">
        </div>
        <div class="logo-text nav-text">
            <span class="logo-title">Portal Portuario</span>
            <span class="logo-subtitle">Talento Humano – APM</span>
        </div>
        <button class="icon-btn sidebar-toggle" id="sidebarToggle"
                onclick="closeSidebar()" aria-label="Cerrar menú lateral">
            <i class="bi bi-list" id="sidebarToggleIcon"></i>
        </button>
    </div>

    <div class="sidebar-body">
        <!-- ══ PORTAL APM (app embebida) ══ -->
        <a class="nav-item" href="<?= preg_replace('#/apps/talento_humano$#', '', BASE_URL) ?>/dashboard"
           data-label="Volver al Portal APM" style="border:1px dashed rgba(148,163,184,.45);margin-bottom:8px;">
            <i class="bi bi-arrow-left-circle"></i>
            <span class="nav-text">Portal APM</span>
        </a>

        <!-- ══ PRINCIPAL ══ -->
        <div class="nav-group">
            <div class="nav-label-group nav-text">
                <i class="bi bi-pin-angle-fill nav-label-icon"></i>
                <span>Principal</span>
            </div>

            <a class="nav-item<?= navActive('/talento-humano/inicio', $currentUri) ?: (($currentUri === '' || $currentUri === '/' || $currentUri === BASE_URL || $currentUri === BASE_URL . '/talento-humano') ? ' active' : '') ?>"
               href="<?= BASE_URL ?>/talento-humano/inicio"
               data-label="Inicio / Dashboard">
                <i class="bi bi-house-door"></i>
                <span class="nav-text">Inicio</span>
                <span class="nav-badge nav-text">Activo</span>
            </a>

            <a class="nav-item<?= navActive('/talento-humano/directorio', $currentUri) ?>"
               href="<?= BASE_URL ?>/talento-humano/directorio"
               data-label="Directorio de Personal">
                <i class="bi bi-people-fill"></i>
                <span class="nav-text">Directorio de Personal</span>
            </a>

            <a class="nav-item<?= navActive('/talento-humano/estudio-seguridad', $currentUri) ?>"
               href="<?= BASE_URL ?>/talento-humano/estudio-seguridad"
               data-label="Formato Socioeconómico">
                <i class="bi bi-shield-shaded"></i>
                <span class="nav-text">Formato Socioeconómico</span>
            </a>

            <a class="nav-item<?= navActive('/talento-humano/accion-personal', $currentUri) ?>"
               href="<?= BASE_URL ?>/talento-humano/accion-personal"
               data-label="Acción de Personal">
                <i class="bi bi-file-earmark-text-fill"></i>
                <span class="nav-text">Acción de Personal</span>
                <span class="nav-badge nav-text">Fase 2</span>
            </a>
        </div>


        <!-- ══ GESTIÓN OPERATIVA ══ -->
        <div class="nav-group">
            <div class="nav-label-group nav-text">
                <i class="bi bi-people nav-label-icon"></i>
                <span>Gestión Operativa</span>
            </div>

            <!-- ── Biblioteca de Formularios (primer ítem) ── -->
            <a class="nav-item<?= navActive('/talento-humano/biblioteca', $currentUri) ?>"
               href="<?= BASE_URL ?>/talento-humano/biblioteca"
               data-label="Biblioteca de Formularios">
                <i class="bi bi-archive-fill"></i>
                <span class="nav-text">Biblioteca</span>
                <span class="nav-badge nav-text">Nuevo</span>
            </a>

            <!-- ── Ítems previos (mantenidos en código, ocultos visualmente hasta previo aviso) ── -->
            <a class="nav-item<?= navActive('/talento-humano/asistencia', $currentUri) ?>"
               href="<?= BASE_URL ?>/talento-humano/asistencia"
               data-label="Asistencia y Turnos">
                <i class="bi bi-clock-history"></i>
                <span class="nav-text">Asistencia y Turnos</span>
            </a>

            <a class="nav-item<?= navActive('/talento-humano/vacaciones', $currentUri) ?>"
               href="<?= BASE_URL ?>/talento-humano/vacaciones"
               data-label="Vacaciones y Ausencias">
                <i class="bi bi-calendar-check"></i>
                <span class="nav-text">Vacaciones y Ausencias</span>
            </a>

            <a class="nav-item<?= navActive('/talento-humano/desempeno', $currentUri) ?>"
               href="<?= BASE_URL ?>/talento-humano/desempeno"
               data-label="Evaluación y Desempeño">
                <i class="bi bi-bar-chart-steps"></i>
                <span class="nav-text">Evaluación y Desempeño</span>
            </a>

            <a class="nav-item<?= navActive('/talento-humano/capacitacion', $currentUri) ?>"
               href="<?= BASE_URL ?>/talento-humano/capacitacion"
               data-label="Capacitación y Desarrollo">
                <i class="bi bi-mortarboard"></i>
                <span class="nav-text">Capacitación y Desarrollo</span>
            </a>
        </div>


        <!-- ══ ADMINISTRACIÓN Y SEGURIDAD ══ -->
        <div class="nav-group">
            <div class="nav-label-group nav-text">
                <i class="bi bi-gear-wide-connected nav-label-icon"></i>
                <span>Administración y Seguridad</span>
            </div>

            <a class="nav-item<?= navActive('/admin/usuarios', $currentUri) ?>"
               href="<?= BASE_URL ?>/admin/usuarios"
               data-label="Gestión de Usuarios">
                <i class="bi bi-person-gear"></i>
                <span class="nav-text">Gestión de Usuarios</span>
            </a>

            <a class="nav-item<?= navActive('/admin/roles', $currentUri) ?>"
               href="<?= BASE_URL ?>/admin/roles"
               data-label="Roles y Permisos">
                <i class="bi bi-shield-shaded"></i>
                <span class="nav-text">Roles y Permisos</span>
            </a>

            <a class="nav-item<?= navActive('/admin/politicas', $currentUri) ?>"
               href="<?= BASE_URL ?>/admin/politicas"
               data-label="Políticas y Normativas">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span class="nav-text">Políticas y Normativas</span>
            </a>
        </div>

        <!-- ══ AUDITORÍA Y CONTROL ══ -->
        <div class="nav-group">
            <div class="nav-label-group nav-text">
                <i class="bi bi-bar-chart-line nav-label-icon"></i>
                <span>Auditoría y Control</span>
            </div>

            <a class="nav-item<?= navActive('/reportes', $currentUri) ?>"
               href="<?= BASE_URL ?>/reportes"
               data-label="Reportes Generales">
                <i class="bi bi-graph-up-arrow"></i>
                <span class="nav-text">Reportes Generales</span>
            </a>

            <a class="nav-item<?= navActive('/auditoria/logs', $currentUri) ?>"
               href="<?= BASE_URL ?>/auditoria/logs"
               data-label="Logs de Actividad">
                <i class="bi bi-journal-text"></i>
                <span class="nav-text">Logs de Actividad</span>
                <span class="nav-badge-admin nav-text">Admin</span>
            </a>
        </div>

    </div><!-- /sidebar-body -->

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper(substr($usuarioNombre ?? 'UA', 0, 2)) ?></div>
            <div class="nav-text">
                <div class="user-name"><?= htmlspecialchars($usuarioNombre ?? 'USUARIO APM') ?></div>
                <div class="user-role"><?= htmlspecialchars($usuarioRol ?? 'Administrador TH') ?></div>
            </div>
            <button class="icon-btn nav-text" title="Cerrar sesión">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </div>
    </div>
</aside>
