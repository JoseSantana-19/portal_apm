<?php
/**
 * shared/footer_scripts.php
 * Incluye el contenedor de toasts y los scripts JS del sistema.
 * Usar con: require_once ROOT . '/shared/footer_scripts.php';
 * justo antes del </body> de cada vista.
 */
?>
<div id="toastContainer" class="toast-container"></div>
<script src="<?= BASE_URL ?>/public/js/layout_sidebar.js"></script>
<script src="<?= BASE_URL ?>/public/js/toast.js"></script>
<script src="<?= BASE_URL ?>/public/js/topbar.js"></script>
<script src="<?= BASE_URL ?>/public/js/talento_humano.js"></script>
<?php if (defined('PORTAL_ROOT_URL')): ?>
<!-- Aviso de inactividad centralizado (mismo SweetAlert2 que Portal, Control
     de Bienes y Bitácoras) en vez del modal propio de esta app -- decisión
     explícita del usuario, para visual único en las 4 apps. El backend de
     inactividad SIGUE siendo el propio de TH (Auth::renewSession()/
     expireForInactivity(), cascada usuario > módulo TALENTO_HUMANO > global
     leída de PORTAL_APM, ver Auth::resolveInactividad()) -- solo cambia la
     interfaz que avisa. -->
<script>
    window.APP_INACTIVIDAD = {
        timeoutSegundos: <?= (int)Auth::idleTtl() ?>,
        avisoSegundos: <?= (int)Auth::idleAviso() ?>,
        keepaliveUrl: '<?= BASE_URL ?>/sesion/renovar',
        logoutUrl: '<?= BASE_URL ?>/sesion/expirar',
        logoutViaPost: true,
        csrfToken: '<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES) ?>'
    };
</script>
<link rel="stylesheet" href="<?= PORTAL_ROOT_URL ?>/public/librerias/Otras_librerias/sweetalert2/sweetalert2.min.css">
<script src="<?= PORTAL_ROOT_URL ?>/public/librerias/Otras_librerias/sweetalert2/sweetalert2.all.min.js"></script>
<script src="<?= PORTAL_ROOT_URL ?>/js/inactivity-warning.js?v=<?= time() ?>"></script>
<?php endif; ?>
