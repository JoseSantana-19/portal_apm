<?php
$nombre   = $_SESSION['nombre_completo'] ?? 'Usuario';
$shortName = explode(' ', $nombre)[0];
$nivel    = (int)($_SESSION['nivel_jerarquia'] ?? 0);

$th       = $kpis['th']       ?? [];
$bienes   = $kpis['bienes']   ?? [];
$bitacora = $kpis['bitacora'] ?? [];
$acceso   = $kpis['acceso']   ?? [];

$v = fn($arr, $key, $default = 0) => $arr[$key] ?? $default;

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Buenos días' : ($hour < 18 ? 'Buenas tardes' : 'Buenas noches');
?>

<!-- Welcome bar -->
<div class="anim-up anim-d0" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-6);flex-wrap:wrap;gap:var(--sp-3);">
    <div>
        <div class="exec-badge">
            <i class="fa-solid fa-chart-gantt" style="font-size:0.6rem;"></i>
            Vista Operativa
        </div>
        <div class="ops-welcome">
            <?= $greeting ?>, <span class="exec-name-hl"><?= htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div style="font-size:var(--font-size-sm);color:var(--text-muted);margin-top:3px;">
            <?= date('d/m/Y') ?> &mdash; <?= date('H:i') ?>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:var(--sp-2);flex-wrap:wrap;">
        <span class="refresh-indicator">
            <span class="refresh-dot"></span> En vivo
        </span>
        <?php if ($nivel >= 2): ?>
        <a href="<?= APP_URL ?>/dashboard/ejecutivo" class="exec-switch-btn" data-spa>
            <i class="fa-solid fa-chart-pie"></i> Vista Ejecutiva
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Live Corporate KPI Cards -->
<div class="kpi-grid">

    <div class="kpi-card" title="Empleados">
        <div class="kpi-glow" style="background:#0284C7;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Empleados</span>
            <span class="kpi-value"><?= number_format((int)$v($th, 'total_empleados')) ?></span>
        </div>
        <div class="kpi-card-right bg-primary-light" style="background: rgba(2,132,199,0.1) !important;">
            <i class="fa-solid fa-users kpi-icon" style="color: #0284C7 !important;"></i>
        </div>
    </div>

    <div class="kpi-card" title="Visitas Pendientes de Salida">
        <div class="kpi-glow" style="background:#0284C7;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Visitas en Curso</span>
            <span class="kpi-value"><?= number_format((int)$v($bitacora, 'eventos_pendientes')) ?></span>
        </div>
        <div class="kpi-card-right bg-info-light" style="background: rgba(2,132,199,0.1) !important;">
            <i class="fa-solid fa-person-walking-arrow-right kpi-icon" style="color: #0284C7 !important;"></i>
        </div>
    </div>

    <div class="kpi-card" title="Bienes Activos">
        <div class="kpi-glow" style="background:#10B981;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Bienes Activos</span>
            <span class="kpi-value"><?= number_format((int)$v($bienes, 'bienes_activos')) ?></span>
        </div>
        <div class="kpi-card-right bg-accent-light" style="background: rgba(16,185,129,0.1) !important;">
            <i class="fa-solid fa-boxes-stacked kpi-icon" style="color: #10B981 !important;"></i>
        </div>
    </div>

    <div class="kpi-card" title="Eventos Mes">
        <div class="kpi-glow" style="background:#F59E0B;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Eventos Mes</span>
            <span class="kpi-value"><?= number_format((int)$v($bitacora, 'eventos_mes')) ?></span>
        </div>
        <div class="kpi-card-right bg-warning-light" style="background: rgba(245,158,11,0.1) !important;">
            <i class="fa-solid fa-book-open kpi-icon" style="color: #F59E0B !important;"></i>
        </div>
    </div>

    <div class="kpi-card" title="Ingresos Hoy">
        <div class="kpi-glow" style="background:#EF4444;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Ingresos Hoy</span>
            <span class="kpi-value"><?= number_format((int)$v($acceso, 'ingresos_hoy')) ?></span>
        </div>
        <div class="kpi-card-right bg-danger-light" style="background: rgba(239,68,68,0.1) !important;">
            <i class="fa-solid fa-person-walking-arrow-right kpi-icon" style="color: #EF4444 !important;"></i>
        </div>
    </div>

</div>

<!-- Main two-column grid: activity feed + tasks -->
<div class="ops-grid">

    <!-- Activity feed (left) -->
    <div>
        <div class="activity-feed anim-up anim-d2">
            <div class="activity-feed-header">
                <span style="font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);">
                    <i class="fa-solid fa-clock-rotate-left" style="margin-right:var(--sp-2);color:var(--color-primary);"></i>
                    Actividad Reciente
                </span>
                <span class="refresh-indicator">
                    <span class="refresh-dot"></span> En vivo
                </span>
            </div>

            <?php if (empty($actividad)): ?>
            <div class="notif-empty" style="padding:var(--sp-6);">
                <i class="fa-regular fa-clock"></i>
                <p>Sin actividad registrada</p>
            </div>
            <?php else: ?>
            <?php foreach ($actividad as $act):
                $modWords = explode('_', $act['modulo'] ?? '');
                $modInit  = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)), $modWords));
                $modInit  = mb_substr($modInit, 0, 2);
                $fecha    = $act['fecha_creacion'];
                if ($fecha instanceof DateTime) { $fecha = $fecha->format('d/m/Y H:i'); }
                elseif (is_string($fecha)) { $fecha = date('d/m/Y H:i', strtotime($fecha)); }
                else { $fecha = '—'; }
            ?>
            <div class="activity-item">
                <div class="activity-avatar"><?= htmlspecialchars($modInit, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="activity-content">
                    <div class="activity-text">
                        <strong><?= htmlspecialchars($act['nombre_completo'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?></strong>
                        — <?= htmlspecialchars($act['operacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        en <?= htmlspecialchars($act['modulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="activity-time">
                        <?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($act['descripcion'])): ?>
                        &mdash; <?= htmlspecialchars(mb_substr($act['descripcion'], 0, 80), ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="badge badge-<?= $act['resultado'] === 'EXITO' ? 'success' : 'danger' ?>">
                    <?= htmlspecialchars($act['resultado'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Charts below activity -->
        <div class="charts-grid" style="margin-top:var(--sp-4);">
            <div class="chart-card anim-up anim-d3">
                <div class="chart-card-header">
                    <div class="chart-title">Control de Acceso — Hoy</div>
                </div>
                <div id="chart-acceso-hoy" style="min-height:200px;"></div>
            </div>
            <div class="chart-card anim-up anim-d4">
                <div class="chart-card-header">
                    <div class="chart-title">Bitácoras — Últimos 7 días</div>
                </div>
                <div id="chart-bitacoras" style="min-height:200px;"></div>
            </div>
        </div>
    </div>

    <!-- Tasks panel (right) -->
    <div class="tasks-panel anim-up anim-d2">
        <div class="tasks-panel-header">
            <span style="font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);">
                <i class="fa-solid fa-list-check" style="margin-right:var(--sp-2);color:var(--color-primary);"></i>
                Accesos Rápidos
            </span>
        </div>

        <?php
        $accesos = [
            ['icon'=>'fa-solid fa-user-plus',       'label'=>'Talento Humano',       'url'=>'/apps/talento_humano/',     'color'=>'var(--color-primary)'],
            ['icon'=>'fa-solid fa-cube',             'label'=>'Control de Bienes',     'url'=>'/apps/control_bienes/',     'color'=>'var(--color-success)'],
            ['icon'=>'fa-solid fa-pen-to-square',    'label'=>'Nueva Bitácora',        'url'=>'/portuaria',                'color'=>'var(--color-warning)'],
            ['icon'=>'fa-solid fa-qrcode',           'label'=>'Registrar Ingreso',     'url'=>'/acceso/ingresar',          'color'=>'var(--color-danger)'],
            ['icon'=>'fa-solid fa-chart-line',       'label'=>'Reportes',              'url'=>'/reportes',                 'color'=>'var(--color-primary)'],
        ];
        foreach ($accesos as $item):
        ?>
        <div class="task-item">
            <div style="width:32px;height:32px;border-radius:var(--radius-md);background:color-mix(in srgb,<?= $item['color'] ?> 15%,transparent);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="<?= $item['icon'] ?>" style="color:<?= $item['color'] ?>;font-size:0.8rem;"></i>
            </div>
            <a href="<?= APP_URL . htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') ?>"
               class="task-text" style="text-decoration:none;color:var(--color-text);" data-spa>
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <i class="fa-solid fa-chevron-right" style="color:var(--color-text-muted);font-size:0.75rem;"></i>
        </div>
        <?php endforeach; ?>

        <!-- Pending tasks for the module -->
        <div class="tasks-panel-header" style="border-top:1px solid var(--color-border);margin-top:var(--sp-2);">
            <span style="font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);">
                <i class="fa-solid fa-circle-exclamation" style="margin-right:var(--sp-2);color:var(--color-warning);"></i>
                Pendientes
            </span>
        </div>

        <div class="task-item">
            <div class="task-priority-dot" style="background:var(--color-info);"></div>
            <span class="task-text">
                <?= number_format((int)$v($bienes, 'bienes_mantenimiento')) ?> bienes en mantenimiento
            </span>
        </div>
        <div class="task-item">
            <div class="task-priority-dot" style="background:var(--color-danger);"></div>
            <span class="task-text">
                <?= number_format((int)$v($bitacora, 'eventos_pendientes')) ?> eventos de bitácora pendientes
            </span>
        </div>
    </div>

</div>

<script>
(function() {
    createChart('#chart-acceso-hoy', {
        chart: { type: 'donut', height: 200 },
        series: [
            <?= (int)$v($acceso, 'empleados_hoy')   ?>,
            <?= (int)$v($acceso, 'visitantes_hoy')  ?>,
            <?= (int)$v($acceso, 'proveedores_hoy') ?>,
        ],
        labels: ['Empleados', 'Visitantes', 'Proveedores'],
        legend: { position: 'bottom' },
        dataLabels: { enabled: true },
        plotOptions: { pie: { donut: { size: '55%' } } },
    });

    createChart('#chart-bitacoras', {
        chart: { type: 'bar', height: 200 },
        series: [{ name: 'Eventos', data: <?= json_encode($kpis['bitacora_semana'] ?? [0,0,0,0,0,0,0]) ?> }],
        xaxis: { categories: ['L','M','X','J','V','S','D'] },
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
        dataLabels: { enabled: false },
    });
})();
</script>
