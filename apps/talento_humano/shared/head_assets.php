<?php
/**
 * shared/head_assets.php
 * Incluye todos los assets CSS necesarios (CDN + locales).
 * Usar con: require_once ROOT . '/shared/head_assets.php';
 * dentro del <head> de cada vista, DESPUÉS de las etiquetas <meta>.
 */
?>
    <script>
        (() => {
            try {
                const saved = localStorage.getItem('apm.theme');
                const preferred = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.dataset.theme = saved === 'dark' || saved === 'light' ? saved : preferred;
            } catch (_) {
                document.documentElement.dataset.theme = 'light';
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/topbar-enhanced.css" media="screen">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/theme.css" media="screen">
<?php if (defined('PORTAL_ROOT_URL')): ?>
    <link rel="stylesheet" href="<?= PORTAL_ROOT_URL ?>/public/librerias/Otras_librerias/sweetalert2/sweetalert2.min.css">
<?php endif; ?>
