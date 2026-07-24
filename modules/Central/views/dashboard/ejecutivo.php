<?php
$nivel    = (int)($_SESSION['nivel_jerarquia'] ?? 0);
$nombre   = $_SESSION['nombre_completo'] ?? 'Estimado/a';
$shortName = explode(' ', $nombre)[0];

// Extract KPI sections from SP data (structure from sp_GetKPIs_Ejecutivo)
$resumen  = $kpis['resumen']  ?? [];
$th       = $kpis['th']       ?? [];
$bienes   = $kpis['bienes']   ?? [];
$bitacora = $kpis['bitacora'] ?? [];
$acceso   = $kpis['acceso']   ?? [];

// Safe value accessor
$v = fn($arr, $key, $default = 0) => $arr[$key] ?? $default;

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Buenos días' : ($hour < 18 ? 'Buenas tardes' : 'Buenas noches');

$days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$months = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$spanishDate = $days[date('w')] . ', ' . date('d') . ' de ' . $months[date('n')] . ' de ' . date('Y');
?>

<!-- Executive Header -->
<div class="exec-header anim-up anim-d0">
    <div>
        <div class="exec-badge">
            <i class="fa-solid fa-chart-pie" style="font-size:0.6rem;"></i>
            Vista Ejecutiva
        </div>
        <div class="exec-greeting"><?= $greeting ?>, <span class="exec-name-hl"><?= htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8') ?></span></div>
        <div class="exec-date"><?= htmlspecialchars($spanishDate, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div style="display:flex;gap:var(--sp-2);align-items:center;flex-wrap:wrap;">
        <span class="refresh-indicator">
            <span class="refresh-dot"></span> Datos actualizados
        </span>
        <a href="<?= APP_URL ?>/dashboard/operativo" class="exec-switch-btn" data-spa>
            <i class="fa-solid fa-chart-gantt"></i> Vista Operativa
        </a>
    </div>
</div>

<!-- Live Corporate KPI Cards -->
<div class="kpi-grid">

    <div class="kpi-card" title="Empleados Activos">
        <div class="kpi-glow" style="background:#0284C7;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Empleados Activos</span>
            <span class="kpi-value"><?= number_format((int)$v($th, 'total_empleados')) ?></span>
            <span class="kpi-trend text-info" style="color:var(--accent-hover) !important;"><i class="fa-solid fa-file-contract"></i> <?= number_format((int)$v($th, 'contratos_vigentes')) ?> vigentes</span>
        </div>
        <div class="kpi-card-right bg-primary-light" style="background: rgba(2,132,199,0.1) !important;">
            <i class="fa-solid fa-users kpi-icon" style="color: #0284C7 !important;"></i>
        </div>
    </div>

    <div class="kpi-card" title="Bienes Inventariados">
        <div class="kpi-glow" style="background:#10B981;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Bienes Inventariados</span>
            <span class="kpi-value"><?= number_format((int)$v($bienes, 'total_bienes')) ?></span>
            <span class="kpi-trend text-success"><i class="fa-solid fa-boxes-stacked"></i> <?= number_format((int)$v($bienes, 'bienes_activos')) ?> activos</span>
        </div>
        <div class="kpi-card-right bg-accent-light" style="background: rgba(16,185,129,0.1) !important;">
            <i class="fa-solid fa-boxes-stacked kpi-icon" style="color: #10B981 !important;"></i>
        </div>
    </div>

    <div class="kpi-card" title="Eventos del Mes">
        <div class="kpi-glow" style="background:#F59E0B;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Eventos del Mes</span>
            <span class="kpi-value"><?= number_format((int)$v($bitacora, 'eventos_mes')) ?></span>
            <span class="kpi-trend text-warning"><i class="fa-solid fa-circle-exclamation"></i> <?= number_format((int)$v($bitacora, 'eventos_pendientes')) ?> pendientes</span>
        </div>
        <div class="kpi-card-right bg-warning-light" style="background: rgba(245,158,11,0.1) !important;">
            <i class="fa-solid fa-book-open kpi-icon" style="color: #F59E0B !important;"></i>
        </div>
    </div>

    <div class="kpi-card" title="Ingresos Hoy">
        <div class="kpi-glow" style="background:#06B6D4;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Ingresos Hoy</span>
            <span class="kpi-value"><?= number_format((int)$v($acceso, 'ingresos_hoy')) ?></span>
            <span class="kpi-trend text-info" style="color:var(--accent-hover) !important;"><i class="fa-solid fa-user-check"></i> <?= number_format((int)$v($acceso, 'visitantes_activos')) ?> activos</span>
        </div>
        <div class="kpi-card-right bg-info-light" style="background: rgba(6,182,212,0.1) !important;">
            <i class="fa-solid fa-id-card kpi-icon" style="color: #06B6D4 !important;"></i>
        </div>
    </div>

    <div class="kpi-card" title="Alertas Pendientes">
        <div class="kpi-glow" style="background:#EF4444;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Alertas Pendientes</span>
            <span class="kpi-value"><?= count($alertas) ?></span>
            <span class="kpi-trend text-danger"><i class="fa-solid fa-bell"></i> Requieren atención</span>
        </div>
        <div class="kpi-card-right bg-danger-light" style="background: rgba(239,68,68,0.1) !important;">
            <i class="fa-solid fa-triangle-exclamation kpi-icon" style="color: #EF4444 !important;"></i>
        </div>
    </div>

</div>

<!-- Charts + Alerts two-column layout -->
<div style="display:grid;grid-template-columns:1fr 340px;gap:var(--sp-4);align-items:start;">

    <!-- Charts column -->
    <div>
        <!-- Module summary chart -->
        <div class="chart-card anim-up anim-d2" style="margin-bottom:var(--sp-4);">
            <div class="chart-card-header">
                <div>
                    <div class="chart-title">Resumen por Módulo</div>
                    <div class="chart-subtitle">Actividad del mes en curso</div>
                </div>
                <a href="<?= APP_URL ?>/reportes" class="btn btn-ghost btn-sm" data-spa>
                    Ver detalles <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <div id="chart-modulos" style="min-height:250px;"></div>
        </div>

        <!-- TH: contratos próximos a vencer -->
        <div class="chart-card anim-up anim-d3">
            <div class="chart-card-header">
                <div>
                    <div class="chart-title">Contratos — Próximos a Vencer</div>
                    <div class="chart-subtitle">Próximos 90 días</div>
                </div>
                <a href="<?= APP_URL ?>/th/contratos" class="btn btn-ghost btn-sm" data-spa>
                    Talento Humano <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <div id="chart-contratos" style="min-height:220px;"></div>
        </div>
    </div>

    <!-- Alerts panel -->
    <div class="alerts-panel anim-up anim-d2">
        <div class="alerts-panel-header">
            <span style="font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);">
                <i class="fa-solid fa-bell" style="color:var(--color-warning);margin-right:var(--sp-2);"></i>
                Alertas del Sistema
            </span>
            <span class="badge badge-warning"><?= count($alertas) ?></span>
        </div>

        <?php if (empty($alertas)): ?>
        <div class="notif-empty" style="padding:var(--sp-6);">
            <i class="fa-regular fa-circle-check" style="color:var(--color-success);font-size:1.5rem;"></i>
            <p>Sin alertas pendientes</p>
        </div>
        <?php else: ?>
        <?php foreach ($alertas as $alerta):
            $prioClass = match($alerta['prioridad'] ?? 0) {
                3       => 'high',
                2       => 'medium',
                default => 'low',
            };
            $prioLabel = match($alerta['prioridad'] ?? 0) {
                3       => 'Alta',
                2       => 'Media',
                default => 'Baja',
            };
        ?>
        <div class="alert-item">
            <div class="alert-priority <?= $prioClass ?>"></div>
            <div class="alert-body">
                <div class="alert-title-text"><?= htmlspecialchars($alerta['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="alert-meta">
                    <?= htmlspecialchars($alerta['mensaje'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    — <span class="badge badge-<?= $prioClass === 'high' ? 'danger' : ($prioClass === 'medium' ? 'warning' : 'info') ?>"><?= $prioLabel ?></span>
                </div>
            </div>
            <?php if (!empty($alerta['url_accion'])): ?>
            <a href="<?= htmlspecialchars($alerta['url_accion'], ENT_QUOTES, 'UTF-8') ?>"
               class="alert-action-link" data-spa>Ver</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script>
(function() {
    // Module summary donut chart
    createChart('#chart-modulos', {
        chart: { type: 'bar', height: 250 },
        series: [{
            name: 'Registros',
            data: [
                <?= (int)($th['total_empleados']       ?? 0) ?>,
                <?= (int)($bienes['total_bienes']       ?? 0) ?>,
                <?= (int)($bitacora['eventos_mes']      ?? 0) ?>,
                <?= (int)($acceso['ingresos_hoy']       ?? 0) ?>,
            ]
        }],
        xaxis: {
            categories: ['Talento Humano', 'Control Bienes', 'Bitácoras', 'Control Acceso']
        },
        plotOptions: { bar: { borderRadius: 4, horizontal: false } },
        dataLabels: { enabled: false },
    });

    // Contratos area chart (próximos 12 semanas placeholder — SP should return series)
    createChart('#chart-contratos', {
        chart: { type: 'area', height: 220 },
        series: [{
            name: 'Contratos',
            data: [
                <?= (int)($th['contratos_30d'] ?? 0) ?>,
                <?= (int)($th['contratos_60d'] ?? 0) ?>,
                <?= (int)($th['contratos_90d'] ?? 0) ?>,
            ]
        }],
        xaxis: { categories: ['30 días', '60 días', '90 días'] },
        stroke: { curve: 'smooth' },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
        dataLabels: { enabled: false },
    });
})();
</script>
