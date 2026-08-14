<?php
// bit_sidebar.php - Menú lateral en Offcanvas (Bootstrap 5)
require_once (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)) . '/includes/bit_auth_permissions.php';

$paginaActual = '';

if (!empty($_SERVER['SCRIPT_NAME'])) {
    $paginaActual = basename($_SERVER['SCRIPT_NAME']);
} elseif (!empty($_SERVER['PHP_SELF'])) {
    $paginaActual = basename($_SERVER['PHP_SELF']);
}

$catalogoSidebar = isset($_GET['catalogo']) ? trim((string) $_GET['catalogo']) : '';

function apmSidebarActive($paginaActual, $rutaObjetivo)
{
    return ($paginaActual === $rutaObjetivo) ? 'active' : '';
}

function apmSidebarCatalogoActive($paginaActual, $catalogoActual, $catalogoObjetivo)
{
    return ($paginaActual === 'bit_catalogos.php' && $catalogoActual === $catalogoObjetivo) ? 'active' : '';
}

$puedeRegistrarIngresoSidebar = apm_can_registrar_ingreso();
$puedeVerRegistrosBaseSidebar = apm_can_gestionar_maestros_acceso();
$puedeVerBloqueAdminSidebar = apm_can_ver_bloque_admin();
$puedeBitacoraRondasSidebar = apm_can_acceder_bitacora_rondas();
$puedeDashboardJefeSidebar = apm_can_acceder_dashboard_jefe();
$puedeCctvSidebar = apm_can_acceder_cctv();
$puedeReporteSupervisorSidebar = apm_can_acceder_reporte_supervisor();
$puedeImportarFuncionariosSidebar = apm_can_importar_funcionarios();

$puedeCatPersonasSidebar = apm_can_ver_catalogo_personas();
$puedeCatEmpresasSidebar = apm_can_ver_catalogo_empresas();
$puedeCatDestinosSidebar = apm_can_ver_catalogo_destinos();
$puedeCatMotivosSidebar = apm_can_ver_catalogo_motivos();
$puedeCatFuncionariosSidebar = apm_can_ver_catalogo_funcionarios();
$puedeCatNivelesSidebar = apm_can_ver_catalogo_niveles_incidente();
$puedeCctvInventarioSidebar = apm_can_ver_cctv_inventario();
$puedeCctvMotivosSidebar = apm_can_ver_cctv_motivos();
$puedeCctvBitacoraSidebar = apm_can_ver_cctv_bitacora();

$apmUserNameSidebar = isset($_SESSION['apm_auth']['nombres'])
    ? (string) $_SESSION['apm_auth']['nombres']
    : 'Usuario';

$apmCedulaSidebar = isset($_SESSION['apm_auth']['cedula'])
    ? (string) $_SESSION['apm_auth']['cedula']
    : '';
?>

<div class="offcanvas offcanvas-start apm-sidebar" tabindex="-1" id="sidebarMenu" aria-label="Menú principal">

    <div class="portal-sidebar-header">
        <img src="<?= function_exists('base_url') ? base_url('../../imgs/logoapm.png') : '/imgs/logoapm.png' ?>" alt="Logo APM" class="portal-sidebar-logo">
        <h4>Portal Portuario</h4>

        <button class="portal-sidebar-close" type="button" id="portalSidebarClose" title="Ocultar menú" aria-label="Ocultar menú">
            <i class="bi bi-chevron-left"></i>
        </button>
    </div>

    <nav class="apm-sidebar-nav nav flex-column offcanvas-body pt-3 h-100">

        <!-- ZONA CON SCROLL: aquí van solo las opciones del menú -->
        <div id="sidebarMenuScroll" class="apm-sidebar-scroll">

           <a id="menu-portal-apm"
               class="nav-link"
               href="../../dashboard" title="Volver al Portal APM">
                <i class="bi bi-arrow-left-circle"></i>
                <span>Portal APM</span>
            </a>

           <a id="menu-dashboard"
               class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_index.php'); ?>"
               href="portuaria/dashboard">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>

            <?php if ($puedeDashboardJefeSidebar): ?>
                <a id="menu-dashboard-jefe"
                   class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_dashboard_jefe.php'); ?>"
                   href="bit_dashboard_jefe.php">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Panel jefatura</span>
                </a>
            <?php endif; ?>

            <a id="menu-dashboard-analitica"
               class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_dashboard_analitica.php'); ?>"
               href="dashboard-ejecutivo">
                <i class="bi bi-pie-chart-fill"></i>
                <span>Dashboard Ejecutivo</span>
            </a>

            <?php if ($puedeVerBloqueAdminSidebar): ?>
                <div id="adminGroupWrap">
                    <button id="adminGroupToggle"
                            type="button"
                            class="nav-link w-100 text-start d-flex align-items-center justify-content-between"
                            style="cursor:pointer;"
                            data-bs-toggle="collapse"
                            data-bs-target="#adminGroupItems"
                            aria-expanded="<?php echo in_array($paginaActual, ['bit_registrar_visita.php', 'bit_listado_visitas.php'], true) ? 'true' : 'false'; ?>"
                            aria-controls="adminGroupItems">
                        <span>
                            <i class="bi bi-building"></i>
                            Edificio Administrativo
                        </span>
                        <span id="adminGroupArrow" class="apm-admin-group-arrow-wrap" aria-hidden="true">
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    </button>

                    <div id="adminGroupItems"
                         class="ps-2 collapse <?php echo in_array($paginaActual, ['bit_registrar_visita.php', 'bit_listado_visitas.php'], true) ? 'show' : ''; ?>">

                        <?php if ($puedeRegistrarIngresoSidebar): ?>
                            <a id="menu-registrar"
                               class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_registrar_visita.php'); ?>"
                               href="visitas/registrar">
                                <i class="bi bi-person-plus-fill"></i>
                                <span>Registrar ingreso</span>
                            </a>
                        <?php endif; ?>

                        <a id="menu-listado"
                           class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_listado_visitas.php'); ?>"
                           href="visitas">
                            <i class="bi bi-list-ul"></i>
                            <span>Listado de visitas</span>
                        </a>

                        <?php if ($puedeVerRegistrosBaseSidebar): ?>
                            <a id="menu-registros"
                               class="nav-link <?php echo ($paginaActual === 'bit_catalogos.php' && $catalogoSidebar === '') ? 'active' : ''; ?>"
                               href="catalogos">
                                <i class="bi bi-gear-fill"></i>
                                <span>Registros Base</span>
                            </a>

                            <?php if ($puedeCatPersonasSidebar): ?>
                            <a id="menu-maestro-personas"
                               class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_acc_personas.php'); ?>"
                               href="catalogos/personas">
                                <i class="bi bi-person-vcard"></i>
                                <span>Maestro Personas</span>
                            </a>
                            <?php endif; ?>

                            <?php if ($puedeCatEmpresasSidebar): ?>
                            <a id="menu-maestro-empresas"
                               class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_acc_empresas.php'); ?>"
                               href="catalogos/empresas">
                                <i class="bi bi-building-add"></i>
                                <span>Maestro Empresas</span>
                            </a>
                            <?php endif; ?>

                            <?php if ($puedeCatDestinosSidebar): ?>
                            <a id="menu-maestro-destinos"
                               class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_acc_destinos.php'); ?>"
                               href="catalogos/destinos">
                                <i class="bi bi-signpost-split"></i>
                                <span>Maestro Destinos</span>
                            </a>
                            <?php endif; ?>

                            <?php if ($puedeCatMotivosSidebar): ?>
                            <a id="menu-maestro-motivos"
                               class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_acc_motivos.php'); ?>"
                               href="catalogos/motivos">
                                <i class="bi bi-chat-left-text"></i>
                                <span>Maestro Motivos</span>
                            </a>
                            <?php endif; ?>

                            <?php if ($puedeCatFuncionariosSidebar): ?>
                            <a id="menu-maestro-funcionarios"
                               class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_acc_funcionarios.php'); ?>"
                               href="catalogos/funcionarios">
                                <i class="bi bi-person-badge"></i>
                                <span>Talento Humano</span>
                            </a>
                            <?php endif; ?>

                            <?php if ($puedeCatNivelesSidebar): ?>
                            <a id="menu-maestro-niveles-incidente"
                               class="nav-link"
                               href="catalogos/niveles-incidente">
                                <i class="bi bi-exclamation-triangle"></i>
                                <span>Niveles de importancia</span>
                            </a>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </div>
            <?php endif; ?>

            <?php if ($puedeBitacoraRondasSidebar): ?>
                <div id="segOpWrap">
                    <button id="segOpGroupToggle"
                            type="button"
                            class="nav-link w-100 text-start d-flex align-items-center justify-content-between"
                            style="cursor:pointer;"
                            data-bs-toggle="collapse"
                            data-bs-target="#segOpItems"
                            aria-expanded="<?php echo $paginaActual === 'bit_rondas.php' ? 'true' : 'false'; ?>"
                            aria-controls="segOpItems">
                        <span>
                            <i class="bi bi-shield-check"></i>
                            Supervisores
                        </span>
                        <span id="segOpGroupArrow" class="apm-admin-group-arrow-wrap" aria-hidden="true">
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    </button>

                    <div id="segOpItems"
                         class="ps-2 collapse <?php echo $paginaActual === 'bit_rondas.php' ? 'show' : ''; ?>">
                        <a id="menu-bitacora-rondas"
                           class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_rondas.php'); ?>"
                           href="rondas">
                            <i class="bi bi-clipboard2-pulse"></i>
                            <span>Bitácora de rondas</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($puedeCctvSidebar): ?>
                <?php
                    $paginasCctvSidebar = [
                        'bit_inv_camaras.php',
                        'bit_motivos_camaras.php',
                        'bit_camaras.php',
                    ];
                    $cctvActivoSidebar = in_array($paginaActual, $paginasCctvSidebar, true);
                ?>
                <div id="cctvWrap">
                    <button id="cctvGroupToggle"
                            type="button"
                            class="nav-link w-100 text-start d-flex align-items-center justify-content-between <?php echo $cctvActivoSidebar ? 'active' : ''; ?>"
                            style="cursor:pointer;"
                            data-bs-toggle="collapse"
                            data-bs-target="#cctvItems"
                            aria-expanded="<?php echo $cctvActivoSidebar ? 'true' : 'false'; ?>"
                            aria-controls="cctvItems">
                        <span>
                            <i class="bi bi-camera-video"></i>
                            CCTV Cámaras
                        </span>
                        <span id="cctvGroupArrow" class="apm-admin-group-arrow-wrap" aria-hidden="true">
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    </button>

                    <div id="cctvItems"
                         class="ps-2 collapse <?php echo $cctvActivoSidebar ? 'show' : ''; ?>">
                        <?php if ($puedeCctvInventarioSidebar): ?>
                        <a id="menu-inv-camaras"
                           class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_inv_camaras.php'); ?>"
                           href="camaras/inventario">
                            <i class="bi bi-hdd-network"></i>
                            <span>Maestro de Cámaras</span>
                        </a>
                        <?php endif; ?>

                        <?php if ($puedeCctvMotivosSidebar): ?>
                        <a id="menu-bit-motivos-camaras"
                           class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_motivos_camaras.php'); ?>"
                           href="camaras/motivos">
                            <i class="bi bi-exclamation-triangle"></i>
                            <span>Motivos CCTV</span>
                        </a>
                        <?php endif; ?>

                        <?php if ($puedeCctvBitacoraSidebar): ?>
                        <a id="menu-bitacora-camaras"
                           class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_camaras.php'); ?>"
                           href="camaras">
                            <i class="bi bi-camera-reels"></i>
                            <span>Bitácora de Cámaras</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($puedeReporteSupervisorSidebar): ?>
            <a id="menu-reporte"
               class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_reporte_diario_supervisor.php'); ?>"
               href="bit_reporte_diario_supervisor.php">
                <i class="bi bi-journal-text"></i>
                <span>Reporte supervisor</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeImportarFuncionariosSidebar): ?>
            <a id="menu-importar"
               class="nav-link <?php echo apmSidebarActive($paginaActual, 'bit_importar_funcionarios.php'); ?>"
               href="importar-funcionarios">
                <i class="bi bi-upload"></i>
                <span>Importar funcionarios</span>
            </a>
            <?php endif; ?>

        </div>
        <!-- FIN ZONA CON SCROLL -->

        <!-- ZONA FIJA ABAJO: usuario -->
        <div id="sidebarUserWrap" class="apm-sidebar-user pt-3 border-top border-light border-opacity-25">

            <div id="userActionsItems"
                 class="collapse <?php echo $paginaActual === 'cambiar-password' ? 'show' : ''; ?>">

                <a id="menu-password"
                   class="nav-link <?php echo apmSidebarActive($paginaActual, 'cambiar-contrasena'); ?>"
                   href="../../cambiar-contrasena">
                    <i class="bi bi-key"></i>
                    <span>Cambiar contraseña</span>
                </a>

                <a id="menu-logout" class="nav-link" href="../../logout">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Salir</span>
                </a>
                
                <button id="themeToggle"
                        type="button"
                        class="nav-link w-100 text-start border-0 bg-transparent">
                    <i id="themeToggleIcon" class="bi bi-moon-stars"></i>
                    <span id="themeToggleText">Modo oscuro</span>
                </button>
            </div>


            <button id="userActionsToggle"
                    type="button"
                    class="nav-link w-100 text-start d-flex align-items-center justify-content-between"
                    style="cursor:pointer;"
                    data-bs-toggle="collapse"
                    data-bs-target="#userActionsItems"
                    aria-expanded="<?php echo $paginaActual === 'cambiar-password' ? 'true' : 'false'; ?>"
                    aria-controls="userActionsItems">

                <span class="d-flex align-items-center">
                    <i class="bi bi-person-circle"></i>

                    <span class="d-flex flex-column lh-sm">
                        <span class="small text-truncate fw-semibold" style="max-width: 175px;">
                            <?php echo htmlspecialchars($apmUserNameSidebar); ?>
                        </span>

                        <span class="small opacity-75">
                            CI: <?php echo htmlspecialchars($apmCedulaSidebar); ?>
                        </span>
                    </span>
                </span>

                <span id="userActionsArrow" class="apm-admin-group-arrow-wrap" aria-hidden="true">
                    <i class="bi bi-chevron-right"></i>
                </span>

            </button>

        </div>
        <!-- FIN ZONA FIJA ABAJO -->

    </nav>
</div>