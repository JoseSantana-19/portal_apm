<?php
/**
 * shared/head_assets.php
 * Incluye todos los assets CSS locales necesarios.
 * Usar con: require_once ROOT . '/shared/head_assets.php';
 * dentro del <head> de cada vista, DESPUÉS de las etiquetas <meta>.
 */
    $assetVersion = static fn(string $path): string => (string)(@filemtime(ROOT . $path) ?: '1');
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
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/vendor/fonts/google-fonts.css?v=<?= $assetVersion('/public/vendor/fonts/google-fonts.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/vendor/bootstrap-icons/bootstrap-icons.min.css?v=<?= $assetVersion('/public/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/vendor/datatables/dataTables.dataTables.min.css?v=<?= $assetVersion('/public/vendor/datatables/dataTables.dataTables.min.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css?v=<?= $assetVersion('/public/css/variables.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css?v=<?= $assetVersion('/public/css/layout.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css?v=<?= $assetVersion('/public/css/toast.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/topbar-enhanced.css?v=<?= $assetVersion('/public/css/topbar-enhanced.css') ?>" media="screen">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/theme.css?v=<?= $assetVersion('/public/css/theme.css') ?>" media="screen">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/form_drafts.css?v=<?= $assetVersion('/public/css/form_drafts.css') ?>" media="screen">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/searchable_select.css?v=<?= $assetVersion('/public/css/searchable_select.css') ?>" media="screen">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/apm_datatables.css?v=<?= $assetVersion('/public/css/apm_datatables.css') ?>" media="screen">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/visual-polish.css?v=<?= $assetVersion('/public/css/visual-polish.css') ?>" media="screen">
<?php if (defined('PORTAL_ROOT_URL')): ?>
    <link rel="stylesheet" href="<?= PORTAL_ROOT_URL ?>/public/librerias/Otras_librerias/sweetalert2/sweetalert2.min.css">
<?php endif; ?>
