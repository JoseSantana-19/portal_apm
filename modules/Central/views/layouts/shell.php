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
</script>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= APP_URL ?>/js/main.js"></script>

</body>
</html>
