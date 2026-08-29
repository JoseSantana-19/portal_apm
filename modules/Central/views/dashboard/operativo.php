<?php
/**
 * Dashboard Operativo — PORTAL APM
 * Centro de Control Táctico, Monitoreo de Garita, Red CCTV y Tablas Operacionales DataTables.
 */
$nombre    = $_SESSION['nombre_completo'] ?? 'Operador/a';
$shortName = explode(' ', $nombre)[0];
$nivel     = (int)($_SESSION['nivel_jerarquia'] ?? 0);

$currentTimeframe = $kpis['timeframe'] ?? 'today';
$resumen          = $kpis['resumen'] ?? [];
$visitasActivas   = $kpis['visitas_activas'] ?? [];
$camaras          = $kpis['camaras'] ?? [];
$bienesAtencion   = $kpis['bienes_atencion'] ?? [];
$actividad        = $kpis['actividad'] ?? ($actividad ?? []);
$semanaBitacora   = $kpis['bitacora_semana'] ?? ['labels'=>[], 'data'=>[]];
$zonas            = $kpis['zonas'] ?? [];
$afluencia        = $kpis['afluencia_horaria'] ?? ['labels'=>[], 'data'=>[]];

$val = fn($arr, $key, $default = 0) => $arr[$key] ?? $default;

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Buenos días' : ($hour < 18 ? 'Buenas tardes' : 'Buenas noches');
?>

<div class="dashboard-wrapper anim-up anim-d0" id="ops-dashboard-root">

    <!-- ══════════════════════════════════════════════════════════════
         OPERATIONAL STATUS BANNER & COMMAND BAR
         ══════════════════════════════════════════════════════════════ -->
    <div class="ops-status-banner">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
            <div class="beacon-pulse" id="ops-beacon" style="padding:6px 14px;">
                <span class="beacon-dot"></span>
                <span style="font-weight:700;" id="ops-beacon-label">SISTEMAS 100% OPERATIVOS</span>
            </div>
            <div class="ops-shift-chip">
                <i class="fa-solid <?= htmlspecialchars($val($resumen, 'turno_icon', 'fa-sun'), ENT_QUOTES, 'UTF-8') ?>"></i>
                <span><?= htmlspecialchars($val($resumen, 'turno', 'Turno Actual'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>

        <div class="dash-header-actions">
            <!-- Auto-Refresh Selector -->
            <select id="ops-refresh-interval" class="dash-select-refresh" title="Frecuencia de Auto-Refresco">
                <option value="20000" selected>Auto-refresco: 20s</option>
                <option value="45000">Auto-refresco: 45s</option>
                <option value="0">Pausado (Manual)</option>
            </select>

            <button type="button" class="btn-dash" id="btn-ops-manual-refresh" title="Actualizar datos ahora">
                <i class="fa-solid fa-arrows-rotate" id="ops-refresh-icon"></i>
            </button>

            <!-- NOC Wallboard Mode Button -->
            <button type="button" class="btn-dash" id="btn-ops-toggle-wallboard" title="Modo Pantalla Completa Sala de Control / NOC">
                <i class="fa-solid fa-expand"></i> NOC
            </button>

            <!-- Reloj en vivo -->
            <div style="font-family:var(--font-code);font-size:0.85rem;font-weight:700;color:var(--text-app);padding:6px 12px;background:var(--bg-app);border-radius:var(--radius-md);border:1px solid var(--border-app);">
                <i class="fa-regular fa-clock" style="color:var(--primary-hover);margin-right:4px;"></i>
                <span id="ops-live-clock"><?= date('H:i:s') ?></span>
            </div>

            <?php if ($nivel >= 2): ?>
            <a href="<?= APP_URL ?>/dashboard/ejecutivo" class="btn-dash btn-dash-primary" data-spa title="Ver Dashboard Ejecutivo">
                <i class="fa-solid fa-chart-pie"></i> Vista Ejecutiva
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TACTICAL OPERATIONAL KPI CARDS
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-kpi-grid">

        <!-- 1. Visitas en Recinto Portuario -->
        <div class="kpi-master-card" style="--card-accent: #0284C7; cursor:pointer;" onclick="openDrilldown('visitas_activas', 0)">
            <div class="kpi-master-glow"></div>
            <div class="kpi-top-row">
                <span class="kpi-title-label">Visitas en Puerto</span>
                <div class="kpi-icon-pill"><i class="fa-solid fa-person-walking-arrow-right"></i></div>
            </div>
            <div>
                <div class="kpi-val-number" id="ops-kpi-visitas-puerto">
                    <?= number_format((int)$val($resumen, 'visitas_en_puerto')) ?>
                </div>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                    Permanencia activa en recinto
                </div>
            </div>
            <div class="kpi-bottom-row">
                <span>Ingresos Hoy</span>
                <span class="trend-pill info" id="ops-kpi-visitas-hoy">
                    <i class="fa-solid fa-calendar-day"></i> <?= (int)$val($resumen, 'visitas_hoy') ?> Registrados
                </span>
            </div>
        </div>

        <!-- 2. Monitoreo CCTV & Seguridad -->
        <div class="kpi-master-card" style="--card-accent: #10B981; cursor:pointer;" onclick="openDrilldown('camaras', 0)">
            <div class="kpi-master-glow"></div>
            <div class="kpi-top-row">
                <span class="kpi-title-label">Monitoreo CCTV</span>
                <div class="kpi-icon-pill"><i class="fa-solid fa-video"></i></div>
            </div>
            <div>
                <div class="kpi-val-number" id="ops-kpi-camaras-activas">
                    <?= number_format((int)$val($resumen, 'camaras_activas')) ?>
                </div>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                    Cámaras en línea (100% Cobertura)
                </div>
            </div>
            <div class="kpi-bottom-row">
                <span>Estado de Red</span>
                <span class="trend-pill success">
                    <i class="fa-solid fa-signal"></i> Señal Óptima
                </span>
            </div>
        </div>

        <!-- 3. Disponibilidad de Bienes -->
        <div class="kpi-master-card" style="--card-accent: #F59E0B; cursor:pointer;" onclick="openDrilldown('bienes_categoria', 0)">
            <div class="kpi-master-glow"></div>
            <div class="kpi-top-row">
                <span class="kpi-title-label">Parque de Bienes</span>
                <div class="kpi-icon-pill"><i class="fa-solid fa-boxes-stacked"></i></div>
            </div>
            <div>
                <div class="kpi-val-number" id="ops-kpi-bienes-activos">
                    <?= number_format((int)$val($resumen, 'bienes_activos')) ?>
                </div>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                    Activos en funcionamiento operativo
                </div>
            </div>
            <div class="kpi-bottom-row">
                <span>Mantenimiento</span>
                <span class="trend-pill <?= (int)$val($resumen, 'bienes_mantenimiento') > 0 ? 'warning' : 'success' ?>">
                    <i class="fa-solid fa-screwdriver-wrench"></i> <?= (int)$val($resumen, 'bienes_mantenimiento') ?> en Taller
                </span>
            </div>
        </div>

        <!-- 4. Actividad del Turno -->
        <div class="kpi-master-card" style="--card-accent: #8B5CF6; cursor:pointer;" onclick="openDrilldown('seguridad_mfa', 0)">
            <div class="kpi-master-glow"></div>
            <div class="kpi-top-row">
                <span class="kpi-title-label">Transacciones Turno</span>
                <div class="kpi-icon-pill"><i class="fa-solid fa-list-check"></i></div>
            </div>
            <div>
                <div class="kpi-val-number" id="ops-kpi-audit-hoy">
                    <?= number_format((int)$val($resumen, 'operaciones_hoy')) ?>
                </div>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                    Acciones auditadas el día de hoy
                </div>
            </div>
            <div class="kpi-bottom-row">
                <span>Personal Activo</span>
                <span class="trend-pill info">
                    <i class="fa-solid fa-id-badge"></i> <?= (int)$val($resumen, 'empleados_activos') ?> en Nómina
                </span>
            </div>
        </div>

    </div>

    <!-- ══════════════════════════════════════════════════════════════
         MATRIZ DE ZONAS PORTUARIAS & SEMÁFORO DE SEGURIDAD
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-card">
        <div class="dash-card-header">
            <div>
                <div class="dash-card-title">
                    <i class="fa-solid fa-map-location-dot" style="color:var(--primary-hover);"></i>
                    Monitoreo de Vigilancia por Zonas Portuarias
                </div>
                <div class="dash-card-subtitle">Estado operacional en muelle, balanza, garita, TPyC y patios</div>
            </div>
            <span class="beacon-pulse"><span class="beacon-dot"></span> Señal en Vivo</span>
        </div>

        <div class="zone-matrix-grid">
            <?php foreach ($zonas as $z): ?>
            <div class="zone-matrix-card" onclick="openDrilldown('camaras', 0)" style="cursor:pointer;" title="Clic para ver cámaras de esta zona">
                <div class="zone-header-row">
                    <span class="zone-name-text"><?= htmlspecialchars($z['zona'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="zone-status-dot <?= htmlspecialchars($z['estado_sem'] ?? 'normal', ENT_QUOTES, 'UTF-8') ?>"></span>
                </div>
                <div class="zone-info-row">
                    <span><i class="fa-solid fa-video" style="color:var(--primary-hover);margin-right:4px;"></i> <?= (int)$z['operativas'] ?> / <?= (int)$z['total_camaras'] ?> Cámaras</span>
                    <span style="font-weight:700;color:var(--success);">Normal</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         OPERATIONAL LIVE TABLES (DATATABLES CON BUSCADORES EFECTIVOS)
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-content-grid">

        <!-- Left Column: Live Active Visits Table & CCTV Grid -->
        <div class="dash-column">

            <!-- Tablero de Visitas en Curso en Recinto Portuario (DataTables) -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">
                            <i class="fa-solid fa-id-card-clip" style="color:#0284C7;"></i>
                            Control de Permanencia en Recinto Portuario (Garita)
                        </div>
                        <div class="dash-card-subtitle">Personas y vehículos actualmente dentro de las instalaciones con DataTables activo</div>
                    </div>
                    <a href="<?= APP_URL ?>/apps/bitacoras/" class="btn btn-ghost btn-sm" data-no-spa target="_blank" title="Ir a Módulo de Bitácoras">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Gestión Garita
                    </a>
                </div>

                <div class="dash-table-wrap">
                    <table class="dash-table" id="tabla-visitas-operativo" data-dt data-dt-page-length="10">
                        <thead>
                            <tr>
                                <th>Visitante</th>
                                <th>Empresa / Procedencia</th>
                                <th>Motivo de Acceso</th>
                                <th>Fecha</th>
                                <th>Hora Ingreso</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visitasActivas as $v):
                                $horaEntrada = $v['hora_entrada'] ?? '';
                                if ($horaEntrada instanceof DateTime) { $horaEntrada = $horaEntrada->format('H:i'); }
                                elseif (is_string($horaEntrada)) { $horaEntrada = substr($horaEntrada, 0, 5); }
                                $fecha = $v['fecha_visita'] ?? '';
                                if ($fecha instanceof DateTime) { $fecha = $fecha->format('d/m/Y'); }
                                elseif (is_string($fecha)) { $fecha = date('d/m/Y', strtotime($fecha)); }
                            ?>
                            <tr>
                                <td style="font-weight:700;color:var(--text-app);">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div style="width:26px;height:26px;border-radius:50%;background:color-mix(in srgb, #0284C7 15%, transparent);color:#0284C7;display:flex;align-items:center;justify-content:center;font-size:0.75rem;flex-shrink:0;">
                                            <i class="fa-solid fa-user"></i>
                                        </div>
                                        <span><?= htmlspecialchars($v['visitante'] ?? 'Visitante', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight:600;"><?= htmlspecialchars($v['empresa'] ?? 'Particular', ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-info" style="font-size:0.72rem;"><?= htmlspecialchars($v['motivo'] ?? 'Gestión', ENT_QUOTES, 'UTF-8') ?></span>
                                </td>
                                <td style="font-size:0.75rem;color:var(--text-muted);white-space:nowrap;"><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="font-family:var(--font-code);font-weight:700;color:var(--primary-hover);white-space:nowrap;"><?= htmlspecialchars($horaEntrada, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="beacon-pulse" style="padding:2px 8px;font-size:0.68rem;">
                                        <span class="beacon-dot"></span> EN PUERTO
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Gráfico: Curva de Afluencia Horaria en Garita -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">
                            <i class="fa-solid fa-chart-column" style="color:#F59E0B;"></i>
                            Afluencia Horaria en Garitas de Acceso
                        </div>
                        <div class="dash-card-subtitle">Flujo de ingresos por hora del día</div>
                    </div>
                </div>
                <div class="chart-container-box">
                    <div id="chart-ops-afluencia" style="min-height: 200px; width: 100%;"></div>
                </div>
            </div>

            <!-- Matriz de Estado de Cámaras CCTV -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">
                            <i class="fa-solid fa-video" style="color:#10B981;"></i>
                            Inventario de Cámaras de Seguridad (Monitoreo Activo)
                        </div>
                        <div class="dash-card-subtitle">Monitoreo y estado técnico de cámaras</div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="openDrilldown('camaras', 0)">Ver todas</button>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:10px;">
                    <?php foreach (array_slice($camaras, 0, 6) as $cam): ?>
                    <div style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:var(--radius-md);background:var(--bg-app);border:1px solid var(--border-app);">
                        <div style="width:32px;height:32px;border-radius:var(--radius-sm);background:color-mix(in srgb, #10B981 15%, transparent);color:#10B981;display:flex;align-items:center;justify-content:center;font-size:0.9rem;flex-shrink:0;">
                            <i class="fa-solid fa-video"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:0.78rem;font-weight:700;color:var(--text-app);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?= htmlspecialchars($cam['codigo'] ?: 'CAM-' . $cam['ubicacion'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div style="font-size:0.7rem;color:var(--text-muted);">
                                <?= htmlspecialchars($cam['ubicacion'] ?? 'General', ENT_QUOTES, 'UTF-8') ?> &bull; <?= htmlspecialchars($cam['marca'] ?? 'IP', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                        <span style="width:8px;height:8px;border-radius:50%;background:var(--success);" title="En Línea"></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- Right Column: Live Audit Stream & Fast Launchpad -->
        <div class="dash-column">

            <!-- Stream de Actividad y Auditoría Unificada (DataTables) -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">
                            <i class="fa-solid fa-clock-rotate-left" style="color:var(--primary-hover);"></i>
                            Actividad Operativa en Vivo
                        </div>
                        <div class="dash-card-subtitle">Registro cronológico de transacciones cross-DB</div>
                    </div>
                    <a href="<?= APP_URL ?>/admin/auditoria" class="btn btn-ghost btn-sm" data-spa title="Ver Auditoría Completa">
                        Ver todo
                    </a>
                </div>

                <div class="dash-activity-list" id="ops-activity-list">
                    <?php if (empty($actividad)): ?>
                    <div class="notif-empty" style="padding:var(--sp-6);">
                        <i class="fa-regular fa-clock"></i>
                        <p>Sin transacciones recientes registradas</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($actividad as $act):
                        $mod = $act['modulo'] ?? 'PORTAL';
                        $modInitial = mb_substr($mod, 0, 4);
                        $fecha = $act['fecha_creacion'] ?? '';
                        if ($fecha instanceof DateTime) { $fecha = $fecha->format('H:i:s'); }
                        elseif (is_string($fecha)) { $fecha = date('H:i:s', strtotime($fecha)); }
                        $esExito = ($act['resultado'] ?? '') === 'EXITO';
                    ?>
                    <div class="dash-activity-item">
                        <span class="dash-mod-badge"><?= htmlspecialchars($modInitial, ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="dash-activity-body">
                            <div class="dash-activity-text">
                                <strong><?= htmlspecialchars($act['nombre_completo'] ?? 'Operador', ENT_QUOTES, 'UTF-8') ?></strong> &mdash; <?= htmlspecialchars($act['operacion'] ?? 'Registro', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="dash-activity-sub">
                                <?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?> &bull; IP: <?= htmlspecialchars($act['ip_address'] ?? '127.0.0.1', ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($act['descripcion'])): ?>
                                &mdash; <?= htmlspecialchars(mb_substr($act['descripcion'], 0, 45), ENT_QUOTES, 'UTF-8') ?>...
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="badge badge-<?= $esExito ? 'success' : 'danger' ?>" style="font-size:0.65rem;">
                            <?= htmlspecialchars($act['resultado'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gráfico: Serie semanal de bitácoras -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">
                            <i class="fa-solid fa-chart-line" style="color:#0284C7;"></i>
                            Flujo Semanal de Bitácoras
                        </div>
                        <div class="dash-card-subtitle">Eventos operativos registrados por día</div>
                    </div>
                </div>
                <div class="chart-container-box">
                    <div id="chart-ops-bitacoras" style="min-height: 180px; width: 100%;"></div>
                </div>
            </div>

            <!-- Lanzador de Acciones Rápidas del Operador -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div class="dash-card-title">
                        <i class="fa-solid fa-bolt" style="color:#F59E0B;"></i>
                        Lanzador de Operaciones
                    </div>
                </div>
                <div class="quick-tiles-grid">
                    <a href="<?= APP_URL ?>/apps/bitacoras/" class="quick-tile-btn" data-no-spa target="_blank">
                        <i class="fa-solid fa-pen-to-square" style="color:#0284C7;"></i>
                        <span>Nueva Bitácora</span>
                    </a>
                    <a href="<?= APP_URL ?>/apps/control_bienes/" class="quick-tile-btn" data-no-spa target="_blank">
                        <i class="fa-solid fa-boxes-packing" style="color:#10B981;"></i>
                        <span>Control Bienes</span>
                    </a>
                    <a href="<?= APP_URL ?>/apps/talento_humano/" class="quick-tile-btn" data-no-spa target="_blank">
                        <i class="fa-solid fa-address-book" style="color:#8B5CF6;"></i>
                        <span>Directorio TH</span>
                    </a>
                    <a href="<?= APP_URL ?>/reportes" class="quick-tile-btn" data-spa>
                        <i class="fa-solid fa-chart-line" style="color:#EC4899;"></i>
                        <span>Reportes</span>
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- ══════════════════════════════════════════════════════════════
     INTERACTIVE DRILL-DOWN OFFCANVAS DRAWER CON DATATABLES
     ══════════════════════════════════════════════════════════════ -->
<div class="dash-drawer-backdrop" id="drilldown-backdrop" onclick="closeDrilldown()"></div>
<div class="dash-drawer" id="drilldown-drawer">
    <div class="dash-drawer-header">
        <div class="dash-drawer-title" id="drawer-title">
            <i class="fa-solid fa-table-list" style="color:var(--primary-hover);"></i>
            <span>Detalle Analítico</span>
        </div>
        <button type="button" class="dash-drawer-close" onclick="closeDrilldown()" title="Cerrar panel">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="dash-drawer-body">
        <div class="dash-table-wrap">
            <table id="drawer-datatable" class="dash-table" data-dt data-dt-page-length="10" style="width:100%;">
                <thead id="drawer-thead"></thead>
                <tbody id="drawer-tbody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     WALLBOARD FLOATING CONTROLLER
     ══════════════════════════════════════════════════════════════ -->
<div class="wallboard-control-bar">
    <span style="font-size:0.8rem;font-weight:700;color:#38BDF8;">
        <i class="fa-solid fa-desktop" style="margin-right:6px;"></i> SALA DE CONTROL APM
    </span>
    <button type="button" class="btn-dash btn-dash-primary" onclick="toggleWallboardMode()">
        <i class="fa-solid fa-compress"></i> Salir de Pantalla Completa
    </button>
</div>

<!-- ══════════════════════════════════════════════════════════════
     OPERATIONAL APEXCHARTS, DRILLDOWN & AUTO-REFRESH SCRIPT
     ══════════════════════════════════════════════════════════════ -->
<script>
(function() {
    'use strict';

    const isDark = document.body.classList.contains('t2') || document.body.classList.contains('t3');
    const colorText = isDark ? '#E2E8F0' : '#1A253C';
    const colorMuted = isDark ? '#94A3B8' : '#64748B';
    const colorBorder = isDark ? 'rgba(255,255,255,0.08)' : '#DDE4EF';

    // 1. Chart: Weekly Bitácoras
    const semanaData = <?= json_encode($semanaBitacora['data'] ?? [0,0,0,0,0,0,0]) ?>;
    const semanaLabels = <?= json_encode($semanaBitacora['labels'] ?? ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom']) ?>;

    const chartBitacoras = createChart('#chart-ops-bitacoras', {
        chart: {
            type: 'bar',
            height: 180,
            toolbar: { show: false }
        },
        colors: ['#0284C7'],
        series: [{ name: 'Eventos', data: semanaData }],
        xaxis: {
            categories: semanaLabels,
            labels: { style: { colors: colorMuted, fontSize: '11px' } },
            axisBorder: { color: colorBorder }
        },
        yaxis: {
            labels: { style: { colors: colorMuted, fontSize: '11px' } }
        },
        plotOptions: {
            bar: { borderRadius: 4, columnWidth: '45%' }
        },
        dataLabels: { enabled: false },
        grid: { borderColor: colorBorder, strokeDashArray: 3 },
        tooltip: { theme: isDark ? 'dark' : 'light' }
    });

    // 2. Chart: Afluencia Horaria
    const afluenciaLabels = <?= json_encode($afluencia['labels'] ?? []) ?>;
    const afluenciaData = <?= json_encode($afluencia['data'] ?? []) ?>;

    const chartAfluencia = createChart('#chart-ops-afluencia', {
        chart: {
            type: 'area',
            height: 200,
            toolbar: { show: false }
        },
        colors: ['#F59E0B'],
        series: [{ name: 'Ingresos', data: afluenciaData }],
        xaxis: {
            categories: afluenciaLabels,
            labels: { style: { colors: colorMuted, fontSize: '10px' }, rotate: -45 },
            axisBorder: { color: colorBorder }
        },
        yaxis: {
            labels: { style: { colors: colorMuted, fontSize: '10px' } }
        },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 95, 100] }
        },
        stroke: { curve: 'smooth', width: 2 },
        dataLabels: { enabled: false },
        grid: { borderColor: colorBorder, strokeDashArray: 3 },
        tooltip: { theme: isDark ? 'dark' : 'light' }
    });

    // 3. Live Clock
    setInterval(() => {
        const el = document.getElementById('ops-live-clock');
        if (el) {
            const now = new Date();
            el.textContent = now.toTimeString().split(' ')[0];
        }
    }, 1000);

    // 4. Auto-Refresh Engine via AJAX
    let opsRefreshTimer = null;

    function fetchOperationalData() {
        const icon = document.getElementById('ops-refresh-icon');
        if (icon) icon.classList.add('fa-spin');

        fetch(APP_URL + '/api/dashboard/operativo', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(response => {
            if (response && response.ok && response.data) {
                updateOperationalDOM(response.data);
            }
        })
        .catch(err => console.warn('Error fetching operational dashboard update:', err))
        .finally(() => {
            if (icon) icon.classList.remove('fa-spin');
        });
    }

    function updateOperationalDOM(data) {
        if (!data) return;
        const res = data.resumen || {};

        const elVisitas = document.getElementById('ops-kpi-visitas-puerto');
        if (elVisitas && res.visitas_en_puerto !== undefined) {
            elVisitas.textContent = new Intl.NumberFormat('en-US').format(res.visitas_en_puerto);
        }

        const elCamaras = document.getElementById('ops-kpi-camaras-activas');
        if (elCamaras && res.camaras_activas !== undefined) {
            elCamaras.textContent = new Intl.NumberFormat('en-US').format(res.camaras_activas);
        }

        const elBienes = document.getElementById('ops-kpi-bienes-activos');
        if (elBienes && res.bienes_activos !== undefined) {
            elBienes.textContent = new Intl.NumberFormat('en-US').format(res.bienes_activos);
        }

        const elAudit = document.getElementById('ops-kpi-audit-hoy');
        if (elAudit && res.operaciones_hoy !== undefined) {
            elAudit.textContent = new Intl.NumberFormat('en-US').format(res.operaciones_hoy);
        }

        if (chartBitacoras && data.bitacora_semana) {
            chartBitacoras.updateSeries([
                { name: 'Eventos', data: data.bitacora_semana.data || [] }
            ]);
        }
    }

    function setupOpsAutoRefresh() {
        const select = document.getElementById('ops-refresh-interval');
        const beacon = document.getElementById('ops-beacon');
        const label = document.getElementById('ops-beacon-label');

        if (opsRefreshTimer) {
            clearInterval(opsRefreshTimer);
            opsRefreshTimer = null;
        }

        if (!select) return;
        const intervalMs = parseInt(select.value, 10);

        if (intervalMs > 0) {
            if (beacon) beacon.classList.remove('paused');
            if (label) label.textContent = 'SISTEMAS 100% OPERATIVOS';
            opsRefreshTimer = setInterval(fetchOperationalData, intervalMs);
        } else {
            if (beacon) beacon.classList.add('paused');
            if (label) label.textContent = 'PAUSADO (MANUAL)';
        }
    }

    const selectEl = document.getElementById('ops-refresh-interval');
    if (selectEl) selectEl.addEventListener('change', setupOpsAutoRefresh);

    const manualBtn = document.getElementById('btn-ops-manual-refresh');
    if (manualBtn) manualBtn.addEventListener('click', fetchOperationalData);

    setupOpsAutoRefresh();

    // 5. NOC / Wallboard Mode Fullscreen Toggle
    window.toggleWallboardMode = function() {
        document.body.classList.toggle('wallboard-active');
        if (document.body.classList.contains('wallboard-active')) {
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen().catch(() => {});
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen().catch(() => {});
            }
        }
    };

    const btnWallboard = document.getElementById('btn-ops-toggle-wallboard');
    if (btnWallboard) btnWallboard.addEventListener('click', window.toggleWallboardMode);

    // 6. Drilldown Drawer Engine with DataTables
    let drawerDtInstance = null;

    window.openDrilldown = function(tipo, id) {
        const backdrop = document.getElementById('drilldown-backdrop');
        const drawer = document.getElementById('drilldown-drawer');
        const titleEl = document.getElementById('drawer-title');
        const thead = document.getElementById('drawer-thead');
        const tbody = document.getElementById('drawer-tbody');
        const table = document.getElementById('drawer-datatable');

        if (backdrop) backdrop.classList.add('active');
        if (drawer) drawer.classList.add('active');

        if (titleEl) titleEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="color:var(--primary-hover);"></i> Cargando detalle...';

        fetch(APP_URL + '/api/dashboard/drilldown?tipo=' + encodeURIComponent(tipo) + (id ? '&id=' + encodeURIComponent(id) : ''), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(response => {
            if (!response || !response.ok || !response.data) return;
            const d = response.data;

            if (titleEl) {
                titleEl.innerHTML = `<i class="fa-solid fa-table-list" style="color:var(--primary-hover);"></i> <span>${d.titulo || 'Detalle'}</span>`;
            }

            if (drawerDtInstance) {
                drawerDtInstance.destroy();
                drawerDtInstance = null;
            } else if (window.DataTable && window.DataTable.isDataTable(table)) {
                window.DataTable(table).destroy();
            }

            const cols = d.columnas || [];
            let theadHtml = '<tr>';
            cols.forEach(c => theadHtml += `<th>${c}</th>`);
            theadHtml += '</tr>';
            if (thead) thead.innerHTML = theadHtml;

            let tbodyHtml = '';
            (d.items || []).forEach(row => {
                tbodyHtml += '<tr>';
                Object.values(row).forEach(val => {
                    if (typeof val === 'number' && val > 1000) {
                        tbodyHtml += `<td><strong>$${new Intl.NumberFormat('en-US').format(val)}</strong></td>`;
                    } else {
                        tbodyHtml += `<td>${val !== null && val !== undefined ? val : '—'}</td>`;
                    }
                });
                tbodyHtml += '</tr>';
            });
            if (tbody) tbody.innerHTML = tbodyHtml;

            if (window.DataTable) {
                drawerDtInstance = new window.DataTable(table, {
                    language: { url: APP_URL + '/public/librerias/datatables-core/es-ES.json' },
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
                    columnDefs: [{ targets: '_all', type: 'string' }]
                });
            }
        })
        .catch(err => {
            console.warn('Error loading drilldown:', err);
            if (titleEl) titleEl.textContent = 'Error al cargar datos';
        });
    };

    window.closeDrilldown = function() {
        const backdrop = document.getElementById('drilldown-backdrop');
        const drawer = document.getElementById('drilldown-drawer');
        if (backdrop) backdrop.classList.remove('active');
        if (drawer) drawer.classList.remove('active');
    };

    // Trigger DataTables initialization for table on load
    if (typeof window.initDataTables === 'function') {
        window.initDataTables(document);
    }

})();
</script>
