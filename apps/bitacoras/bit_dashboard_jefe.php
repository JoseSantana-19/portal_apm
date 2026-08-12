<?php
/**
 * Panel estadístico (jefatura TI / Gerencia) — actualización por polling.
 */
require_once __DIR__ . '/includes/bit_auth_guard.php';
require_once __DIR__ . '/includes/bit_auth_permissions.php';
require_once __DIR__ . '/includes/bit_dashboard_jefe_data.php';
require_once __DIR__ . '/conexion/conexion.php';

if (!apm_can_acceder_dashboard_jefe()) {
    header('Location: ../../dashboard?msg=acceso_denegado');
    exit;
}

include_once __DIR__ . '/rutas/config_rutas.php';

$kpis = apm_dashboard_jefe_kpis($conn);
$charts = apm_dashboard_jefe_series_semana($conn);
$movimientos = apm_dashboard_jefe_movimientos($conn, 10);

$url_js_dash = 'public/js/portuaria/dashboard_jefe.js?v=' . (is_file(__DIR__ . '/public/js/portuaria/dashboard_jefe.js') ? (string) filemtime(__DIR__ . '/public/js/portuaria/dashboard_jefe.js') : '1');
$chartsJson = json_encode($charts, JSON_UNESCAPED_UNICODE);
$movJson = json_encode($movimientos, JSON_UNESCAPED_UNICODE);
$kpisJson = json_encode($kpis, JSON_UNESCAPED_UNICODE);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Panel jefatura — Estadísticas | APM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_bootstrap_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_icons_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_variables_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_layout_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_componentes_css); ?>">
    <style>
        .apm-dash-kpi-num { font-size: 2.75rem; font-weight: 700; line-height: 1.1; }
        .apm-dash-feed-wrap { max-height: 420px; overflow: auto; }
        a.apm-dash-kpi-link { cursor: pointer; text-decoration: none; color: inherit; display: block; transition: box-shadow 0.15s ease; }
        a.apm-dash-kpi-link:hover { box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.1) !important; }
        tr.apm-dash-row-new > td {
            animation: apmDashPulse 2s ease-out 1;
            background-color: rgba(13, 110, 253, 0.12) !important;
        }
        @keyframes apmDashPulse {
            0% { background-color: rgba(25, 135, 84, 0.35); }
            100% { background-color: rgba(13, 110, 253, 0.12); }
        }
        .apm-dash-table-wrap {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .apm-dash-table thead th {
            background: linear-gradient(180deg, #1b3f6b 0%, #16375d 100%);
            color: #fff;
            font-weight: 600;
            border-bottom: 0;
            letter-spacing: 0.01em;
        }
        .apm-dash-table tbody td {
            border-color: #eef2f7;
        }
    </style>
</head>
<body class="bg-light">
<?php include __DIR__ . '/modules/Portuaria/views/layouts/bit_navbar.php'; ?>

<div class="apm-layout">
    <?php include __DIR__ . '/modules/Portuaria/views/layouts/bit_sidebar.php'; ?>

    <main class="apm-main">
        <div class="container-fluid my-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
                <div>
                    <h1 class="h3 text-primary fw-bold mb-1">Panel de estadísticas</h1>
                    <p class="text-muted small mb-0">Vista en tiempo casi real (actualización cada 10 s) — Gerencia y Tecnología de la Información.</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-secondary" id="apmDashLastSync">Sincronizando…</span>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <a href="visitas" class="card shadow-sm border-0 h-100 apm-dash-kpi-link" title="Abrir listado de visitas">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase">Visitas activas ahora</div>
                            <div class="apm-dash-kpi-num text-primary" id="kpiVisitasActivas"><?php echo (int) $kpis['visitas_activas']; ?></div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-md-4">
                    <a href="rondas" class="card shadow-sm border-0 h-100 apm-dash-kpi-link" title="Abrir bitácora de rondas">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase">Rondas de hoy</div>
                            <div class="apm-dash-kpi-num text-success" id="kpiRondasHoy"><?php echo (int) $kpis['rondas_hoy']; ?></div>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body">
                            <div class="text-muted small text-uppercase">Alertas críticas (24 h)</div>
                            <div class="apm-dash-kpi-num text-danger" id="kpiAlertasCrit"><?php echo (int) $kpis['alertas_criticas_24h']; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-2"><strong>Visitas (7 días)</strong></div>
                        <div class="card-body" style="height:280px;">
                            <canvas id="chartVisitasSemana"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-2"><strong>Rondas / actividades (7 días)</strong></div>
                        <div class="card-body" style="height:280px;">
                            <canvas id="chartRondasSemana"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <strong>Actividad reciente</strong>
                    <span class="small text-muted">Últimos movimientos registrados en el sistema</span>
                </div>
                <div class="card-body p-0 apm-dash-feed-wrap apm-dash-table-wrap shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless table-sm mb-0 align-middle apm-dash-table" id="tablaActividadReciente">
                            <thead class="sticky-top">
                                <tr>
                                    <th>Hora</th>
                                    <th>Tipo</th>
                                    <th>Usuario</th>
                                    <th>Turno</th>
                                    <th>Detalle</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="apmDashFeedBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<div id="apmDashboardModalHost"></div>

<script src="<?php echo htmlspecialchars($url_bootstrap_js); ?>"></script>
<script src="<?php echo htmlspecialchars($url_layout_sidebar_js); ?>"></script>
<script src="<?php echo htmlspecialchars($url_chart_js); ?>"></script>
<script>
window.APM_DASH_INITIAL = {
    kpis: <?php echo $kpisJson !== false ? $kpisJson : '{}'; ?>,
    charts: <?php echo $chartsJson !== false ? $chartsJson : '{}'; ?>,
    movimientos: <?php echo $movJson !== false ? $movJson : '[]'; ?>
};
</script>
<script src="<?php echo htmlspecialchars($url_js_dash); ?>"></script>
</body>
</html>
