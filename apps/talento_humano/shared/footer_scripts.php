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
<script src="<?= BASE_URL ?>/public/js/talento_humano.js"></script>
