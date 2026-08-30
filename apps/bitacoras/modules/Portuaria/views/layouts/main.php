<?php
/**
 * Layout principal MVC.
 * Reutiliza el navbar y sidebar originales para mantener el mismo look.
 *
 * Variables inyectadas desde Controller::view():
 *   - $file: ruta absoluta de la vista del módulo a renderizar
 *   - Todas las variables pasadas en $data ya están disponibles vía extract()
 */
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($pageTitle ?? 'Autoridad Portuaria de Manta') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <base href="<?= htmlspecialchars(base_url('/')) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url_bootstrap_css); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url_icons_css); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url_variables_css); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url_layout_css); ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($url_componentes_css); ?>">
    <!-- SweetAlert2 CSS (aviso de inactividad) — APP_URL absoluto: el <base href> de acá arriba apunta a esta app, no al portal -->
    <link rel="stylesheet" href="<?= APP_URL ?>/public/librerias/Otras_librerias/sweetalert2/sweetalert2.min.css">
    <?php if (!empty($extraCss)): ?>
        <?php foreach ((array)$extraCss as $css): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="bg-light">

<?php include VIEWS_LAYOUT_PATH . '/bit_navbar.php'; ?>

<div class="apm-layout">
    <?php include VIEWS_LAYOUT_PATH . '/bit_sidebar.php'; ?>
    <main class="apm-main">
        <div class="container-fluid my-4">
            <?php require $file; ?>
        </div>
    </main>
</div>

<script src="<?= htmlspecialchars($url_bootstrap_js); ?>"></script>
<script src="<?= htmlspecialchars($url_layout_sidebar_js); ?>"></script>
<?php if (!empty($extraJs)): ?>
    <?php foreach ((array)$extraJs as $jsFile): ?>
        <script src="<?= htmlspecialchars($jsFile) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Aviso de inactividad — APP_URL acá ya apunta a la raíz del portal, ver config/app.php -->
<script>
    // window.X explícito: un const de nivel superior no queda accesible como
    // window.APP_INACTIVIDAD en un navegador real (js/inactivity-warning.js
    // lee justo esa propiedad) — con const el aviso nunca se disparaba.
    window.APP_INACTIVIDAD = {
        timeoutSegundos: <?= (int)($_SESSION['_inactividad_segundos'] ?? 1800) ?>,
        avisoSegundos:   <?= (int)($_SESSION['_inactividad_aviso'] ?? 60) ?>,
        keepaliveUrl:    '<?= APP_URL ?>/api/keepalive',
        logoutUrl:       '<?= APP_URL ?>/logout'
    };
</script>
<script src="<?= APP_URL ?>/public/librerias/Otras_librerias/sweetalert2/sweetalert2.all.min.js"></script>
<script src="<?= APP_URL ?>/js/alerts.js"></script>
<!-- ?v=time(): cache-busting real — evita que el navegador siga usando una
     copia vieja de este archivo cacheada de una visita anterior. -->
<script src="<?= APP_URL ?>/js/inactivity-warning.js?v=<?= time() ?>"></script>
</body>
</html>
