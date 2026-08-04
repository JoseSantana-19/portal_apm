<?php
// bit_navbar.php — Header principal configurable
// Para cambiar entre logo e imagen, edite SOLO: config/header.php
//
// Vive en views/layouts/ (junto a main.php) porque es un partial de layout
// compartido por el MVC y por las páginas legacy que todavía quedan. Como
// las páginas legacy no siempre tienen ROOT_PATH definido, se calcula acá
// mismo con dirname(__DIR__, 2) como respaldo.
$__apmRoot = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);

require_once $__apmRoot . '/includes/bit_auth_session.php';

$apmUserName = isset($_SESSION['apm_auth']['nombres']) ? (string)$_SESSION['apm_auth']['nombres'] : '';
$apmAreaName = isset($_SESSION['apm_auth']['nom_departa'])
    ? (string)$_SESSION['apm_auth']['nom_departa']
    : (isset($_SESSION['apm_auth']['DEP_NOMBRE'])
        ? (string)$_SESSION['apm_auth']['DEP_NOMBRE']
        : (isset($_SESSION['apm_auth']['nombre_departamento']) ? (string)$_SESSION['apm_auth']['nombre_departamento'] : '')
    );

// Cargar configuración del header
$headerConfig = file_exists($__apmRoot . '/config/header.php')
    ? require $__apmRoot . '/config/header.php'
    : ['modo' => 'logo', 'logo' => ['src' => (function_exists('base_url') ? base_url('../../imgs/logoapm.png') : '/imgs/logoapm.png'), 'alt' => 'Logo APM', 'titulo' => 'Autoridad Portuaria de Manta', 'subtitulo' => 'Control de Visitas'], 'imagen' => ['src' => (function_exists('base_url') ? base_url('../../imgs/logoapm_banner.png') : '/imgs/logoapm_banner.png'), 'alt' => 'APM', 'height' => '60px']];

$headerModo = $headerConfig['modo'] ?? 'logo';
?>
<link rel="stylesheet" href="public/css/portuaria/portal_portuario.css?v=15">
<script>
(function () {
    try {
        var k = 'apmSidebarHidden';
        if (window.sessionStorage.getItem(k) !== '1') return;
        if (!window.matchMedia('(min-width: 992px)').matches) return;
        if (!document.body) return;

        document.body.classList.add('portal-sidebar-hidden', 'apm-sidebar-no-transition');

        function clearNoTrans() {
            document.body.classList.remove('apm-sidebar-no-transition');
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                window.requestAnimationFrame(function () {
                    window.requestAnimationFrame(clearNoTrans);
                });
            });
        } else {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(clearNoTrans);
            });
        }
    } catch (e) {}
})();
</script>
<!-- Header fijo: el contenido pasa por debajo (sticky-top + z-index alto) -->
<nav class="navbar navbar-expand-lg apm-topbar shadow-sm sticky-top">
    <div class="container-fluid portal-header-container">

        <div class="portal-header-logo">

            <button class="btn btn-light portal-menu-toggle" type="button" id="portalSidebarToggle" title="Mostrar/Ocultar menú">
                <i class="bi bi-list"></i>
            </button>

            <?php if ($headerModo === 'imagen'): ?>
                <!-- MODO IMAGEN: banner panorámico -->
                <img src="<?php echo htmlspecialchars($headerConfig['imagen']['src']); ?>"
                     alt="<?php echo htmlspecialchars($headerConfig['imagen']['alt']); ?>"
                     class="portal-header-banner"
                     style="height: <?php echo htmlspecialchars($headerConfig['imagen']['height']); ?>; width: auto; object-fit: contain;">

            <?php else: ?>
                <!-- MODO LOGO: logo cuadrado + título (por defecto) -->
                <img src="<?php echo htmlspecialchars($headerConfig['logo']['src']); ?>"
                     alt="<?php echo htmlspecialchars($headerConfig['logo']['alt']); ?>">

                <div class="portal-title-block">
                    <h5><?php echo htmlspecialchars($headerConfig['logo']['titulo']); ?></h5>
                    <small><?php echo htmlspecialchars($headerConfig['logo']['subtitulo']); ?></small>
                </div>
            <?php endif; ?>

        </div>

        <div class="portal-user-area">

            <div class="portal-user-info">
                <div class="portal-user-name">
                    <?php echo htmlspecialchars($apmUserName ?: 'Usuario Seguridad Integral'); ?>
                </div>
                <small>
                    <?php echo htmlspecialchars($apmAreaName ?: 'Seguridad Integral'); ?>
                </small>
            </div>

            <div class="portal-notification">
                <i class="bi bi-bell-fill"></i>
                <span>3</span>
            </div>
        </div>

    </div>
</nav>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnMenu = document.getElementById('portalSidebarToggle');
    const btnClose = document.getElementById('portalSidebarClose');
    const sidebar = document.getElementById('sidebarMenu');
    const storageKey = 'apmSidebarHidden';

    if (!sidebar) {
        return;
    }

    function isDesktop() {
        return window.matchMedia('(min-width: 992px)').matches;
    }

    function guardarEstadoOculto(oculto) {
        try {
            if (oculto) {
                window.sessionStorage.setItem(storageKey, '1');
            } else {
                window.sessionStorage.removeItem(storageKey);
            }
        } catch (e) {}
    }

    function notificarCambioLayout() {
        window.setTimeout(function () {
            try {
                window.dispatchEvent(new CustomEvent('apm:sidebar-layout-changed'));
            } catch (e) {}
        }, 300);
    }

    function abrirSidebar() {
        document.body.classList.remove('portal-sidebar-hidden');
        guardarEstadoOculto(false);

        if (btnMenu) {
            btnMenu.setAttribute('aria-expanded', 'true');
            btnMenu.setAttribute('title', 'Menú abierto');
        }

        notificarCambioLayout();
    }

    function cerrarSidebar() {
        document.body.classList.add('portal-sidebar-hidden');
        guardarEstadoOculto(true);

        if (btnMenu) {
            btnMenu.setAttribute('aria-expanded', 'false');
            btnMenu.setAttribute('title', 'Mostrar menú');
        }

        notificarCambioLayout();
    }

    if (btnMenu) {
        btnMenu.addEventListener('click', function (event) {
            event.preventDefault();

            if (isDesktop()) {
                abrirSidebar();
                return;
            }

            if (window.bootstrap && window.bootstrap.Offcanvas) {
                const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(sidebar);
                offcanvas.toggle();
            }
        });
    }

    if (btnClose) {
        btnClose.addEventListener('click', function (event) {
            event.preventDefault();

            if (isDesktop()) {
                cerrarSidebar();
                return;
            }

            if (window.bootstrap && window.bootstrap.Offcanvas) {
                const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(sidebar);
                offcanvas.hide();
            }
        });
    }

    if (btnMenu) {
        btnMenu.setAttribute(
            'aria-expanded',
            document.body.classList.contains('portal-sidebar-hidden') ? 'false' : 'true'
        );
    }
});
</script>
<script src="public/js/portuaria/theme_mode.js?v=3"></script>
