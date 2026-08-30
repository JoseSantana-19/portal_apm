<?php
/* shared/menu.php – Menú lateral único compartido entre todas las vistas.
   Incluir con: require_once ROOT . '/shared/menu.php';
   Requiere que BASE_URL e IMG_URL estén definidos en config_routes.php. */

// Detectar ruta actual para resaltar el ítem activo
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$esModoMovimiento = str_contains($currentUri, '/talento-humano/directorio')
    && str_contains($currentUri, 'modo=movimiento');
$usuarioSesion = Auth::user() ?? [];
$usuarioNombre = $usuarioSesion['name'] ?? ($usuarioNombre ?? 'USUARIO APM');
$usuarioRol    = $usuarioSesion['role'] ?? ($usuarioRol ?? 'Usuario');
if (!function_exists('navActive')) {
    function navActive(string $path, string $uri): string {
        return (str_contains($uri, $path) ? ' active' : '');
    }
}
?>
<script>
(() => {
    try {
        if (window.innerWidth > 1024 && sessionStorage.getItem('apm.sidebar.hidden') === '1') {
            document.body.classList.add('sidebar-hidden');
        }
    } catch (_) { /* el menú continúa con su estado predeterminado */ }
})();
</script>
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

            <?php if(Auth::can('directorio','visualizar')): ?><a class="nav-item<?= str_contains($currentUri, '/talento-humano/directorio') && !$esModoMovimiento ? ' active' : '' ?>"
               href="<?= BASE_URL ?>/talento-humano/directorio"
               data-label="Nómina de Personal">
                <i class="bi bi-people-fill"></i>
                <span class="nav-text">Nómina de Personal</span>
            </a><?php endif; ?>
        </div>

        <!-- GESTIÓN DE TALENTO HUMANO -->
        <div class="nav-group">
            <div class="nav-label-group nav-text">
                <i class="bi bi-person-workspace nav-label-icon"></i>
                <span>Gestión de Talento Humano</span>
            </div>
            <?php if(Auth::can('acciones','visualizar')): ?><a class="nav-item<?= navActive('/talento-humano/accion-personal', $currentUri) ?>"
               href="<?= BASE_URL ?>/talento-humano/accion-personal"
               data-label="Acción de Personal">
                <i class="bi bi-file-earmark-text-fill"></i>
                <span class="nav-text">Acción de Personal</span>
            </a><?php endif; ?>

            <?php if(Auth::can('movimientos','visualizar')): ?><a class="nav-item<?= $esModoMovimiento ? ' active' : '' ?>"
               href="<?= BASE_URL ?>/talento-humano/directorio?modo=movimiento"
               data-label="Movimientos internos">
                <i class="bi bi-arrow-left-right"></i>
                <span class="nav-text">Movimientos internos</span>
            </a><?php endif; ?>

            <?php if(Auth::can('socioeconomico','visualizar')): ?><a class="nav-item<?= navActive('/talento-humano/estudio-seguridad', $currentUri) ?>"
               href="<?= BASE_URL ?>/talento-humano/estudio-seguridad"
               data-label="Estudio Socioeconómico">
                <i class="bi bi-shield-shaded"></i>
                <span class="nav-text">Estudio Socioeconómico</span>
            </a><?php endif; ?>

            <?php if(Auth::can('vacaciones','visualizar')): ?><a class="nav-item<?= navActive('/talento-humano/vacaciones', $currentUri) ?>" href="<?= BASE_URL ?>/talento-humano/vacaciones" data-label="Vacaciones">
                <i class="bi bi-calendar-check"></i><span class="nav-text">Vacaciones</span>
            </a><?php endif; ?>

            <?php if(Auth::can('paz_salvo','visualizar')): ?><a class="nav-item<?= navActive('/talento-humano/paz-salvo', $currentUri) ?>" href="<?= BASE_URL ?>/talento-humano/paz-salvo" data-label="Paz y Salvo">
                <i class="bi bi-file-earmark-check-fill"></i><span class="nav-text">Paz y Salvo</span>
            </a><?php endif; ?>
        </div>

        <!-- DOCUMENTOS Y FORMATOS -->
        <div class="nav-group">
            <div class="nav-label-group nav-text">
                <i class="bi bi-folder2-open nav-label-icon"></i>
                <span>Documentos y Formatos</span>
            </div>
            <?php if(Auth::can('biblioteca','visualizar')): ?><a class="nav-item<?= navActive('/talento-humano/biblioteca', $currentUri) ?>"
               href="<?= BASE_URL ?>/talento-humano/biblioteca"
               data-label="Biblioteca de Formularios">
                <i class="bi bi-archive-fill"></i>
                <span class="nav-text">Biblioteca de Formularios</span>
            </a><?php endif; ?>
        </div>


        <!-- ══ ADMINISTRACIÓN Y SEGURIDAD ══ -->
        <div class="nav-group">
            <div class="nav-label-group nav-text">
                <i class="bi bi-gear-wide-connected nav-label-icon"></i>
                <span>Administración y Seguridad</span>
            </div>

            <?php if(Auth::can('maestros','visualizar')): ?><a class="nav-item<?= navActive('/admin/maestros', $currentUri) ?>"
               href="<?= BASE_URL ?>/admin/maestros"
               data-label="Maestros y denominaciones">
                <i class="bi bi-diagram-3-fill"></i>
                <span class="nav-text">Estructura y cargos</span>
            </a><?php endif; ?>

            <?php if(Auth::can('usuarios','visualizar')): ?><a class="nav-item<?= navActive('/admin/usuarios', $currentUri) ?>"
               href="<?= BASE_URL ?>/admin/usuarios"
               data-label="Gestión de Usuarios">
                <i class="bi bi-person-gear"></i>
                <span class="nav-text">Gestión de Usuarios</span>
            </a><?php endif; ?>

            <?php if(Auth::can('roles','visualizar')): ?><a class="nav-item<?= navActive('/admin/roles', $currentUri) ?>"
               href="<?= BASE_URL ?>/admin/roles"
               data-label="Roles y Permisos">
                <i class="bi bi-shield-shaded"></i>
                <span class="nav-text">Roles y Permisos</span>
            </a><?php endif; ?>

            <?php if(Auth::can('politicas','visualizar')): ?><a class="nav-item<?= navActive('/admin/politicas', $currentUri) ?>"
               href="<?= BASE_URL ?>/admin/politicas"
               data-label="Políticas y Normativas">
                <i class="bi bi-journal-bookmark-fill"></i>
                <span class="nav-text">Políticas y Normativas</span>
            </a><?php endif; ?>
        </div>

        <!-- ══ AUDITORÍA Y CONTROL ══ -->
        <div class="nav-group">
            <div class="nav-label-group nav-text">
                <i class="bi bi-bar-chart-line nav-label-icon"></i>
                <span>Auditoría y Control</span>
            </div>

            <?php if(Auth::can('reportes','visualizar')): ?><a class="nav-item<?= navActive('/reportes', $currentUri) ?>"
               href="<?= BASE_URL ?>/reportes"
               data-label="Reportes Generales">
                <i class="bi bi-graph-up-arrow"></i>
                <span class="nav-text">Reportes Generales</span>
            </a><?php endif; ?>

            <?php if(Auth::can('auditoria','visualizar')): ?><a class="nav-item<?= navActive('/auditoria/logs', $currentUri) ?>"
               href="<?= BASE_URL ?>/auditoria/logs"
               data-label="Logs de Actividad">
                <i class="bi bi-journal-text"></i>
                <span class="nav-text">Logs de Actividad</span>
                <span class="nav-badge-admin nav-text">Admin</span>
            </a><?php endif; ?>
            <?php if(Auth::can('auditoria','visualizar')): ?><a class="nav-item<?= navActive('/auditoria/reportes', $currentUri) ?>"
               href="<?= BASE_URL ?>/auditoria/reportes" data-label="Reportes de Auditoría">
                <i class="bi bi-person-lines-fill"></i><span class="nav-text">Reportes de Auditoría</span>
            </a><?php endif; ?>
        </div>

        <!-- PROTOTIPOS: visibles, claramente separados de los módulos productivos -->
        <?php if(Auth::can('prototipos','visualizar')): ?><div class="nav-group nav-group--prototype">
            <div class="nav-label-group nav-text">
                <i class="bi bi-cone-striped nav-label-icon"></i>
                <span>Prototipos / Próximamente</span>
            </div>
            <a class="nav-item<?= navActive('/talento-humano/asistencia', $currentUri) ?>" href="<?= BASE_URL ?>/talento-humano/asistencia" data-label="Asistencia y Turnos">
                <i class="bi bi-clock-history"></i><span class="nav-text">Asistencia y Turnos</span><span class="nav-badge nav-text">Demo</span>
            </a>
            <a class="nav-item<?= navActive('/talento-humano/desempeno', $currentUri) ?>" href="<?= BASE_URL ?>/talento-humano/desempeno" data-label="Evaluación y Desempeño">
                <i class="bi bi-bar-chart-steps"></i><span class="nav-text">Evaluación y Desempeño</span><span class="nav-badge nav-text">Demo</span>
            </a>
            <a class="nav-item<?= navActive('/talento-humano/capacitacion', $currentUri) ?>" href="<?= BASE_URL ?>/talento-humano/capacitacion" data-label="Capacitación y Desarrollo">
                <i class="bi bi-mortarboard"></i><span class="nav-text">Capacitación y Desarrollo</span><span class="nav-badge nav-text">Demo</span>
            </a>
        </div><?php endif; ?>

    </div><!-- /sidebar-body -->

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper(substr($usuarioNombre ?? 'UA', 0, 2)) ?></div>
            <a class="nav-text" href="<?= BASE_URL ?>/cuenta/seguridad" title="Seguridad de la cuenta" style="text-decoration:none;color:inherit;min-width:0">
                <div class="user-name"><?= htmlspecialchars($usuarioNombre ?? 'USUARIO APM') ?></div>
                <div class="user-role"><?= htmlspecialchars($usuarioRol ?? 'Administrador TH') ?></div>
            </a>
            <form method="post" action="<?= BASE_URL ?>/logout" class="nav-text" style="margin:0">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
                <button class="icon-btn" type="submit" title="Cerrar sesión" aria-label="Cerrar sesión">
                    <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</aside>
<script>
(() => {
    try {
        const sidebarSavedScroll = Number(sessionStorage.getItem('apm.sidebar.scrollTop'));
        const sidebarScrollable = document.querySelector('.sidebar-body');
        if (sidebarScrollable && Number.isFinite(sidebarSavedScroll) && sidebarSavedScroll >= 0) {
            sidebarScrollable.scrollTop = sidebarSavedScroll;
        }
    } catch (_) { /* el menú continúa en su posición predeterminada */ }
})();
</script>
