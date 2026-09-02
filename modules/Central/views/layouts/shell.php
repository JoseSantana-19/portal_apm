<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . ' — ' : '' ?>SysPort</title>
<link rel="icon" type="image/png" href="<?= APP_URL ?>/public/img/favicon.png">
<!-- Google Fonts pairings -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<!-- CSS -->
<link rel="stylesheet" href="<?= APP_URL ?>/css/variables.css?v=<?= @filemtime(ROOT_PATH . '/css/variables.css') ?: 1 ?>">
<link rel="stylesheet" href="<?= APP_URL ?>/css/style.css?v=<?= @filemtime(ROOT_PATH . '/css/style.css') ?: 1 ?>">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- SweetAlert2 (vendorizado localmente, ver public/librerias/Otras_librerias/sweetalert2) -->
<link rel="stylesheet" href="<?= APP_URL ?>/public/librerias/Otras_librerias/sweetalert2/sweetalert2.min.css">
<!-- DataTables 3.0.1 (vendorizado localmente, ver public/librerias/datatables-core) + tema propio -->
<link rel="stylesheet" href="<?= APP_URL ?>/public/librerias/datatables-core/dataTables.dataTables.min.css">
<link rel="stylesheet" href="<?= APP_URL ?>/css/datatables-theme.css?v=<?= @filemtime(ROOT_PATH . '/css/datatables-theme.css') ?: 1 ?>">
<!-- ApexCharts (sync — must be available before view inline scripts run) -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
<script src="<?= APP_URL ?>/public/js/charts.js"></script>
</head>
<body class="t1">
<script>
    (function() {
        var t = localStorage.getItem('apm_theme') || '1';
        document.body.classList.remove('t1','t2','t3');
        document.body.classList.add('t' + t);
    })();
</script>

<div class="app-shell">

    <!-- SIDEBAR -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- Backdrop oscuro detrás del sidebar en mobile/tablet (<=1024px) --
         solo visible cuando el sidebar está abierto en ese rango; en
         desktop el sidebar empuja el contenido, no lo tapa, así que acá
         no hace falta oscurecer nada. -->
    <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

    <!-- MAIN WRAPPER -->
    <div class="main-wrapper" id="main-wrapper">

        <!-- TOPBAR -->
        <?php require __DIR__ . '/topbar.php'; ?>

        <!-- CONTENT -->
        <main class="main-content" id="main-spa-container">
            <?= $content ?? '' ?>
        </main>

    </div><!-- /main-wrapper -->

</div><!-- /app-shell -->

<!-- SPA Loader -->
<div id="spa-loader"><div class="spa-spinner"></div></div>

<!-- Toast container -->
<div id="toast-container"></div>

<!-- JS -->
<script>
    const APP_URL = '<?= APP_URL ?>';
    const APP_USER = {
        id:    <?= (int)($_SESSION['user_id'] ?? 0) ?>,
        nivel: <?= (int)($_SESSION['nivel_jerarquia'] ?? 0) ?>,
        tema:  '<?= htmlspecialchars($_SESSION['tema'] ?? 'light', ENT_QUOTES, 'UTF-8') ?>'
    };
    // window.X explícito (no const/let): un `const` de nivel superior en un
    // <script> NUNCA se adjunta a `window` en un navegador real — solo crea
    // un binding léxico global invisible para `window.APP_INACTIVIDAD`, que
    // es justo lo que lee js/inactivity-warning.js. Con const, ese script
    // siempre recibía `undefined` y se abortaba en silencio (sin error de
    // consola) — bug real, confirmado con Playwright, no solo supuesto.
    window.APP_INACTIVIDAD = {
        timeoutSegundos: <?= (int)($_SESSION['_inactividad_segundos'] ?? 1800) ?>,
        avisoSegundos:   <?= (int)($_SESSION['_inactividad_aviso'] ?? 60) ?>,
        keepaliveUrl:    APP_URL + '/api/keepalive',
        logoutUrl:       APP_URL + '/logout'
    };
</script>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= APP_URL ?>/public/librerias/Otras_librerias/sweetalert2/sweetalert2.all.min.js"></script>
<script src="<?= APP_URL ?>/js/alerts.js"></script>
<script src="<?= APP_URL ?>/js/password-hash.js?v=<?= @filemtime(ROOT_PATH . '/js/password-hash.js') ?: time() ?>"></script>
<script src="<?= APP_URL ?>/public/librerias/datatables-core/dataTables.min.js"></script>
<script src="<?= APP_URL ?>/js/datatables-init.js?v=<?= @filemtime(ROOT_PATH . '/js/datatables-init.js') ?: time() ?>"></script>
<!-- ?v=filemtime: cache-busting — sin esto, un navegador que ya tenía este
     archivo en caché de una visita anterior puede seguir usando una copia
     vieja indefinidamente aunque el archivo en el servidor ya se corrigió. -->
<script src="<?= APP_URL ?>/js/inactivity-warning.js?v=<?= @filemtime(ROOT_PATH . '/js/inactivity-warning.js') ?: time() ?>"></script>
<script src="<?= APP_URL ?>/js/main.js?v=<?= @filemtime(ROOT_PATH . '/js/main.js') ?: time() ?>"></script>

</body>
</html>
