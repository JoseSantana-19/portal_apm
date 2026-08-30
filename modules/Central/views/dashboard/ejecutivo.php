<?php
/**
 * Dashboard Ejecutivo — PORTAL APM
 * Centro de Comando Estratégico, Analítica Multidimensional Cross-DB y Exploración Drill-Down.
 */
$nivel     = (int)($_SESSION['nivel_jerarquia'] ?? 0);
$nombre    = $_SESSION['nombre_completo'] ?? 'Director/a';
$shortName = explode(' ', $nombre)[0];

$currentTimeframe = $kpis['timeframe'] ?? '30d';
$patrimonio       = $kpis['patrimonio'] ?? [];
$talento          = $kpis['talento'] ?? [];
$seguridad        = $kpis['seguridad_operaciones'] ?? [];
$gobernanza       = $kpis['gobernanza'] ?? [];
$serie14d         = $kpis['serie_temporal'] ?? ['labels'=>[], 'visitas'=>[], 'auditoria'=>[]];
$afluencia        = $seguridad['afluencia_horaria'] ?? ['labels'=>[], 'data'=>[]];
$zonas            = $seguridad['zonas'] ?? [];
$alertas          = $kpis['alertas'] ?? ($alertas ?? []);

$val = fn($arr, $key, $default = 0) => $arr[$key] ?? $default;

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Buenos días' : ($hour < 18 ? 'Buenas tardes' : 'Buenas noches');

$days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$months = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$spanishDate = $days[date('w')] . ', ' . date('d') . ' de ' . $months[date('n')] . ' de ' . date('Y');
?>

<div class="dashboard-wrapper anim-up anim-d0" id="exec-dashboard-root">

    <!-- ══════════════════════════════════════════════════════════════
         EXECUTIVE HEADER & MULTI-CONTROL TOOLBAR
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-header">
        <div class="dash-header-title">
            <div class="dash-tag">
                <i class="fa-solid fa-chart-pie"></i>
                Centro de Comando Estratégico &bull; SysPort APM
            </div>
            <div class="dash-title-text">
                <?= $greeting ?>, <span class="highlight"><?= htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="dash-date-text">
                <i class="fa-regular fa-calendar" style="margin-right:4px;"></i> <?= htmlspecialchars($spanishDate, ENT_QUOTES, 'UTF-8') ?> &mdash; <span id="dash-live-clock"><?= date('H:i:s') ?></span>
            </div>
        </div>

        <div class="dash-header-actions">
            <!-- Selector de Rango Temporal -->
            <div class="timeframe-bar">
                <button type="button" class="timeframe-pill <?= $currentTimeframe === 'today' ? 'active' : '' ?>" data-tf="today">Hoy</button>
                <button type="button" class="timeframe-pill <?= $currentTimeframe === '7d' ? 'active' : '' ?>" data-tf="7d">7 Días</button>
                <button type="button" class="timeframe-pill <?= $currentTimeframe === '30d' ? 'active' : '' ?>" data-tf="30d">30 Días</button>
                <button type="button" class="timeframe-pill <?= $currentTimeframe === '90d' ? 'active' : '' ?>" data-tf="90d">Trimestre</button>
                <button type="button" class="timeframe-pill <?= $currentTimeframe === 'year' ? 'active' : '' ?>" data-tf="year">Año 2026</button>
            </div>

            <!-- Auto-Refresh Selector & Live Beacon -->
            <div class="beacon-pulse" id="exec-beacon" title="Sincronización cross-DB activa">
                <span class="beacon-dot"></span>
                <span id="exec-beacon-label">En vivo</span>
            </div>

            <select id="exec-refresh-interval" class="dash-select-refresh" title="Frecuencia de Auto-Refresco">
                <option value="30000" selected>30s</option>
                <option value="60000">60s</option>
                <option value="0">Pausado</option>
            </select>

            <button type="button" class="btn-dash" id="btn-manual-refresh" title="Actualizar datos ahora">
                <i class="fa-solid fa-arrows-rotate" id="refresh-icon"></i>
            </button>

            <!-- NOC Wallboard Mode Button -->
            <button type="button" class="btn-dash" id="btn-toggle-wallboard" title="Modo Pantalla Completa Sala de Control / NOC">
                <i class="fa-solid fa-expand"></i> NOC
            </button>

            <!-- Export Actions -->
            <a href="<?= APP_URL ?>/dashboard/exportar-excel" class="btn-dash" title="Descargar Reporte Consolidado en Excel / CSV">
                <i class="fa-solid fa-file-excel" style="color:#10B981;"></i> Excel
            </a>

            <a href="<?= APP_URL ?>/dashboard/exportar-pdf" target="_blank" rel="noopener" class="btn-dash btn-dash-primary" title="Visualizar Reporte Oficial en PDF">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>

            <!-- Switch to Ops -->
            <a href="<?= APP_URL ?>/dashboard/operativo" class="btn-dash" data-spa title="Ver Centro de Control Operativo">
                <i class="fa-solid fa-chart-gantt"></i> Vista Operativa
            </a>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         STRATEGIC MASTER KPI CARDS (4 MEGA-METRICS CON DRILL-DOWN)
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-kpi-grid">

        <!-- 1. Patrimonio & Activos -->
        <div class="kpi-master-card" style="--card-accent: #0284C7; cursor:pointer;" onclick="openDrilldown('bienes_categoria', 0)" title="Clic para ver detalle de activos">
            <div class="kpi-master-glow"></div>
            <div class="kpi-top-row">
                <span class="kpi-title-label">Patrimonio en Activos</span>
                <div class="kpi-icon-pill"><i class="fa-solid fa-sack-dollar"></i></div>
            </div>
            <div>
                <div class="kpi-val-number" id="kpi-valor-patrimonio">
                    $<?= number_format((float)$val($patrimonio, 'valor_total'), 0) ?>
                </div>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                    <strong style="color:var(--text-app);" id="kpi-total-bienes"><?= number_format((int)$val($patrimonio, 'total_bienes')) ?></strong> bienes registrados
                </div>
            </div>
            <div class="kpi-bottom-row">
                <span>Disponibilidad</span>
                <span class="trend-pill success" id="kpi-disp-bienes">
                    <i class="fa-solid fa-shield-check"></i> <?= number_format((float)$val($patrimonio, 'tasa_disponibilidad'), 1) ?>% Operativo
                </span>
            </div>
        </div>

        <!-- 2. Talento Humano Institucional -->
        <div class="kpi-master-card" style="--card-accent: #10B981; cursor:pointer;" onclick="openDrilldown('th_unidad', 0)" title="Clic para ver nómina de personal">
            <div class="kpi-master-glow"></div>
            <div class="kpi-top-row">
                <span class="kpi-title-label">Talento Humano Activo</span>
                <div class="kpi-icon-pill"><i class="fa-solid fa-users"></i></div>
            </div>
            <div>
                <div class="kpi-val-number" id="kpi-th-activos">
                    <?= number_format((int)$val($talento, 'activos')) ?>
                </div>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                    Masa salarial: <strong style="color:var(--text-app);" id="kpi-th-masa">$<?= number_format((float)$val($talento, 'masa_salarial'), 0) ?>/mes</strong>
                </div>
            </div>
            <div class="kpi-bottom-row">
                <span>Estructura</span>
                <span class="trend-pill info" id="kpi-th-unidades">
                    <i class="fa-solid fa-sitemap"></i> <?= (int)$val($talento, 'total_direcciones') ?> Direcciones
                </span>
            </div>
        </div>

        <!-- 3. Seguridad & Operaciones Portuarias -->
        <div class="kpi-master-card" style="--card-accent: #F59E0B; cursor:pointer;" onclick="openDrilldown('visitas_activas', 0)" title="Clic para ver visitas activas en garita">
            <div class="kpi-master-glow"></div>
            <div class="kpi-top-row">
                <span class="kpi-title-label">Flujo y Operaciones</span>
                <div class="kpi-icon-pill"><i class="fa-solid fa-anchor"></i></div>
            </div>
            <div>
                <div class="kpi-val-number" id="kpi-visitas-puerto">
                    <?= number_format((int)$val($seguridad, 'visitas_en_puerto')) ?>
                </div>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                    Visitas activas en recinto portuario
                </div>
            </div>
            <div class="kpi-bottom-row">
                <span>Vigilancia CCTV</span>
                <span class="trend-pill warning" id="kpi-cctv-activas" onclick="event.stopPropagation(); openDrilldown('camaras', 0);">
                    <i class="fa-solid fa-video"></i> <?= (int)$val($seguridad, 'camaras_operativas') ?> Cámaras en Línea
                </span>
            </div>
        </div>

        <!-- 4. Gobernanza & Ciberseguridad -->
        <div class="kpi-master-card" style="--card-accent: #8B5CF6; cursor:pointer;" onclick="openDrilldown('seguridad_mfa', 0)" title="Clic para ver cumplimiento MFA">
            <div class="kpi-master-glow"></div>
            <div class="kpi-top-row">
                <span class="kpi-title-label">Gobernanza & Ciberseguridad</span>
                <div class="kpi-icon-pill"><i class="fa-solid fa-shield-halved"></i></div>
            </div>
            <div>
                <div class="kpi-val-number" id="kpi-audit-pct">
                    <?= number_format((float)$val($gobernanza, 'auditorias_exito_pct'), 1) ?>%
                </div>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                    <strong style="color:var(--text-app);" id="kpi-audit-mes"><?= number_format((int)$val($gobernanza, 'auditorias_periodo')) ?></strong> operaciones auditadas
                </div>
            </div>
            <div class="kpi-bottom-row">
                <span>Sesiones Activas</span>
                <span class="trend-pill success" id="kpi-sesiones-activas">
                    <i class="fa-solid fa-user-check"></i> <?= (int)$val($gobernanza, 'sesiones_activas') ?> Concurrentes
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
                    Matriz de Seguridad por Zonas Portuarias (APM)
                </div>
                <div class="dash-card-subtitle">Estado operacional y cobertura CCTV en los sectores estratégicos del puerto</div>
            </div>
            <span class="beacon-pulse"><span class="beacon-dot"></span> Monitoreo en Tiempo Real</span>
        </div>

        <div class="zone-matrix-grid" id="zonas-matrix-container">
            <?php foreach ($zonas as $z): ?>
            <div class="zone-matrix-card" onclick="openDrilldown('camaras', 0)" style="cursor:pointer;" title="Clic para ver cámaras de esta zona">
                <div class="zone-header-row">
                    <span class="zone-name-text"><?= htmlspecialchars($z['zona'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="zone-status-dot <?= htmlspecialchars($z['estado_sem'] ?? 'normal', ENT_QUOTES, 'UTF-8') ?>"></span>
                </div>
                <div class="zone-info-row">
                    <span><i class="fa-solid fa-video" style="color:var(--primary-hover);margin-right:4px;"></i> <?= (int)$z['operativas'] ?> / <?= (int)$z['total_camaras'] ?> Cámaras</span>
                    <span style="font-weight:700;color:var(--success);">Operativo</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         MAIN ANALYTICS SECTION (CHARTS & STRATEGIC BREAKDOWN)
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-content-grid">

        <!-- Left Column: High-Impact Visual Analytics -->
        <div class="dash-column">

            <!-- Chart 1: Spline Area - Operaciones & Auditoría -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">
                            <i class="fa-solid fa-chart-area" style="color:var(--primary-hover);"></i>
                            Dinámica de Operaciones & Trazabilidad
                        </div>
                        <div class="dash-card-subtitle">Evolución de flujo de visitas y transacciones auditadas</div>
                    </div>
                    <span class="badge badge-info" style="font-size:0.7rem;">Serie Cross-DB</span>
                </div>
                <div class="chart-container-box">
                    <div id="chart-tendencia-operaciones" style="min-height: 280px; width: 100%;"></div>
                </div>
            </div>

            <!-- Chart 2 & 3: Donut & Radar Grid -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:var(--sp-4);width:100%;">

                <!-- Donut: Composición Patrimonial -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <div>
                            <div class="dash-card-title">
                                <i class="fa-solid fa-chart-pie" style="color:#0284C7;"></i>
                                Valor Patrimonial
                            </div>
                            <div class="dash-card-subtitle">Top categorías de activos ($ USD)</div>
                        </div>
                        <button type="button" class="btn btn-ghost btn-sm" onclick="openDrilldown('bienes_categoria', 0)" title="Explorar todos los bienes">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </div>
                    <div class="chart-container-box">
                        <div id="chart-patrimonio-donut" style="min-height: 240px; width: 100%;"></div>
                    </div>
                </div>

                <!-- Radar: Salud Operativa Portuaria -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <div>
                            <div class="dash-card-title">
                                <i class="fa-solid fa-compass-drafting" style="color:#8B5CF6;"></i>
                                Índice de Madurez APM
                            </div>
                            <div class="dash-card-subtitle">Rendimiento global por vector</div>
                        </div>
                        <span class="badge badge-success" style="font-size:0.7rem;">Óptimo</span>
                    </div>
                    <div class="chart-container-box">
                        <div id="chart-radar-salud" style="min-height: 240px; width: 100%;"></div>
                    </div>
                </div>

            </div>

            <!-- Chart 4: Curva de Afluencia Horaria en Garita -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">
                            <i class="fa-solid fa-clock-rotate-left" style="color:#F59E0B;"></i>
                            Curva de Afluencia & Horas Pico en Garita
                        </div>
                        <div class="dash-card-subtitle">Distribución horaria del flujo de ingresos (00:00 a 23:00)</div>
                    </div>
                    <span class="trend-pill warning"><i class="fa-solid fa-fire"></i> Pico: 09:00 - 15:00</span>
                </div>
                <div class="chart-container-box">
                    <div id="chart-afluencia-horaria" style="min-height: 220px; width: 100%;"></div>
                </div>
            </div>

            <!-- Chart 5: Barras Horizontales - Dotación por Dirección -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">
                            <i class="fa-solid fa-users-viewfinder" style="color:#10B981;"></i>
                            Dotación de Personal por Dirección
                        </div>
                        <div class="dash-card-subtitle">Distribución del personal en las unidades organizacionales principales</div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="openDrilldown('th_unidad', 0)" title="Explorar nómina">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </div>
                <div class="chart-container-box">
                    <div id="chart-dotacion-barras" style="min-height: 220px; width: 100%;"></div>
                </div>
            </div>

        </div>

        <!-- Right Column: Strategic Alerts, Contracts, Cybersecurity -->
        <div class="dash-column">

            <!-- Salud de Ciberseguridad & MFA -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">
                            <i class="fa-solid fa-fingerprint" style="color:#8B5CF6;"></i>
                            Ciberseguridad & Cumplimiento MFA
                        </div>
                        <div class="dash-card-subtitle">Protección de identidades y acceso seguro</div>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="openDrilldown('seguridad_mfa', 0)">Ver usuarios</button>
                </div>

                <div style="display:flex;align-items:center;gap:14px;padding:10px 0;">
                    <div style="width:60px;height:60px;border-radius:50%;background:color-mix(in srgb, #8B5CF6 15%, transparent);border:3px solid #8B5CF6;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:800;color:#8B5CF6;flex-shrink:0;">
                        <?= (int)$val($gobernanza, 'usuarios_activos') ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.85rem;font-weight:700;color:var(--text-app);">Usuarios Activos en Sistema</div>
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                            Autenticación unificada con MFA disponible en portal
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modalidades Contractuales TH -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">
                            <i class="fa-solid fa-file-signature" style="color:var(--primary-hover);"></i>
                            Modalidades de Contratación
                        </div>
                        <div class="dash-card-subtitle">Estructura jurídica del talento humano</div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:12px;" id="contracts-container">
                    <?php
                    $contratosList = $val($talento, 'contratos', []);
                    $totalActivos = max((int)$val($talento, 'activos', 1), 1);
                    $colors = ['#0284C7', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'];
                    $idx = 0;
                    foreach ($contratosList as $c):
                        $cTotal = (int)($c['total'] ?? 0);
                        $cPct = round(($cTotal / $totalActivos) * 100, 1);
                        $barColor = $colors[$idx % count($colors)];
                        $idx++;
                    ?>
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:0.78rem;margin-bottom:4px;">
                            <span style="font-weight:600;color:var(--text-app);"><?= htmlspecialchars($c['tipo'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            <span style="font-weight:700;color:<?= $barColor ?>;"><?= $cTotal ?> <span style="font-weight:400;color:var(--text-muted);">(<?= $cPct ?>%)</span></span>
                        </div>
                        <div style="width:100%;height:6px;background:color-mix(in srgb, var(--border-app) 70%, transparent);border-radius:99px;overflow:hidden;">
                            <div style="width:<?= $cPct ?>%;height:100%;background:<?= $barColor ?>;border-radius:99px;transition:width 0.8s ease;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Alertas & Notificaciones Ejecutivas -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div>
                        <div class="dash-card-title">
                            <i class="fa-solid fa-bell" style="color:var(--warn);"></i>
                            Alertas y Requerimientos
                        </div>
                        <div class="dash-card-subtitle">Eventos que demandan atención estratégica</div>
                    </div>
                    <span class="badge badge-warning" id="alertas-badge-count"><?= count($alertas) ?></span>
                </div>

                <div style="display:flex;flex-direction:column;gap:10px;" id="alertas-container">
                    <?php if (empty($alertas)): ?>
                    <div class="notif-empty" style="padding:var(--sp-6);">
                        <i class="fa-regular fa-circle-check" style="color:var(--success);font-size:1.8rem;"></i>
                        <p style="margin-top:6px;font-weight:600;">Sin alertas pendientes en el portal</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($alertas as $alerta):
                        $prio = (int)($alerta['prioridad'] ?? 1);
                        $prioClass = $prio >= 3 ? 'danger' : ($prio === 2 ? 'warning' : 'info');
                        $prioLabel = $prio >= 3 ? 'Crítica' : ($prio === 2 ? 'Media' : 'Baja');
                        $fechaCreacion = $alerta['fecha_creacion'] ?? '';
                        if ($fechaCreacion instanceof DateTime) { $fechaCreacion = $fechaCreacion->format('d/m H:i'); }
                        elseif (is_string($fechaCreacion)) { $fechaCreacion = date('d/m H:i', strtotime($fechaCreacion)); }
                    ?>
                    <div style="display:flex;align-items:flex-start;gap:10px;padding:10px;border-radius:var(--radius-md);background:var(--bg-app);border:1px solid var(--border-app);">
                        <div style="width:8px;height:8px;border-radius:50%;margin-top:5px;flex-shrink:0;background:var(--<?= $prioClass === 'danger' ? 'danger' : ($prioClass === 'warning' ? 'warn' : 'accent-hover') ?>);"></div>
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                                <span style="font-size:0.8rem;font-weight:700;color:var(--text-app);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars($alerta['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="badge badge-<?= $prioClass ?>" style="font-size:0.65rem;"><?= $prioLabel ?></span>
                            </div>
                            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;line-height:1.3;">
                                <?= htmlspecialchars($alerta['mensaje'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                        <?php if (!empty($alerta['url_accion'])): 
                            $rawUrl = $alerta['url_accion'];
                            $linkHref = (strpos($rawUrl, 'http://') === 0 || strpos($rawUrl, 'https://') === 0) 
                                ? $rawUrl 
                                : APP_URL . '/' . ltrim($rawUrl, '/');
                            $isExternalApp = strpos($rawUrl, '/apps/') !== false;
                        ?>
                        <a href="<?= htmlspecialchars($linkHref, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-ghost btn-sm" style="padding:2px 8px;font-size:0.72rem;" <?= $isExternalApp ? 'data-no-spa target="_blank"' : 'data-spa' ?>>
                            Ver
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Accesos a Módulos Corporativos -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div class="dash-card-title">
                        <i class="fa-solid fa-cubes-stacked" style="color:var(--primary-hover);"></i>
                        Módulos Corporativos
                    </div>
                </div>
                <div class="quick-tiles-grid">
                    <a href="<?= APP_URL ?>/apps/talento_humano/" class="quick-tile-btn" data-no-spa target="_blank">
                        <i class="fa-solid fa-users" style="color:#10B981;"></i>
                        <span>Talento Humano</span>
                    </a>
                    <a href="<?= APP_URL ?>/apps/control_bienes/" class="quick-tile-btn" data-no-spa target="_blank">
                        <i class="fa-solid fa-boxes-stacked" style="color:#0284C7;"></i>
                        <span>Control Bienes</span>
                    </a>
                    <a href="<?= APP_URL ?>/apps/bitacoras/" class="quick-tile-btn" data-no-spa target="_blank">
                        <i class="fa-solid fa-book-bookmark" style="color:#F59E0B;"></i>
                        <span>Bitácoras</span>
                    </a>
                    <a href="<?= APP_URL ?>/admin/auditoria" class="quick-tile-btn" data-spa>
                        <i class="fa-solid fa-shield-halved" style="color:#8B5CF6;"></i>
                        <span>Auditoría</span>
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
     APEXCHARTS INITIALIZATION, DRILLDOWN & REACTIVE ENGINE
     ══════════════════════════════════════════════════════════════ -->
<script>
(function() {
    'use strict';

    const isDark = document.body.classList.contains('t2') || document.body.classList.contains('t3');
    const colorText = isDark ? '#E2E8F0' : '#1A253C';
    const colorMuted = isDark ? '#94A3B8' : '#64748B';
    const colorBorder = isDark ? 'rgba(255,255,255,0.08)' : '#DDE4EF';

    // 1. Data
    const serie14dLabels = <?= json_encode($serie14d['labels'] ?? []) ?>;
    const serie14dVisitas = <?= json_encode($serie14d['visitas'] ?? []) ?>;
    const serie14dAudit = <?= json_encode($serie14d['auditoria'] ?? []) ?>;

    const topCatRaw = <?= json_encode($val($patrimonio, 'top_categorias', [])) ?>;
    const topCatLabels = topCatRaw.map(c => (c.categoria || '').slice(0, 18));
    const topCatValues = topCatRaw.map(c => Math.round(parseFloat(c.valor_total || 0)));

    const topUnitsRaw = <?= json_encode($val($talento, 'top_unidades', [])) ?>;
    const topUnitsLabels = topUnitsRaw.map(u => (u.unidad || '').slice(0, 20));
    const topUnitsValues = topUnitsRaw.map(u => parseInt(u.total || 0, 10));

    const afluenciaLabels = <?= json_encode($afluencia['labels'] ?? []) ?>;
    const afluenciaData = <?= json_encode($afluencia['data'] ?? []) ?>;

    // 2. Chart 1: Tendencia Operativa
    const chartTendencia = createChart('#chart-tendencia-operaciones', {
        chart: {
            type: 'area',
            height: 280,
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: true, easing: 'easeinout', speed: 600 }
        },
        colors: ['#0284C7', '#8B5CF6'],
        series: [
            { name: 'Visitas Garita', data: serie14dVisitas },
            { name: 'Auditoría Transaccional', data: serie14dAudit }
        ],
        xaxis: {
            categories: serie14dLabels,
            labels: { style: { colors: colorMuted, fontSize: '11px' } },
            axisBorder: { color: colorBorder },
            axisTicks: { color: colorBorder }
        },
        yaxis: {
            labels: { style: { colors: colorMuted, fontSize: '11px' } }
        },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 95, 100] }
        },
        stroke: { curve: 'smooth', width: 2.5 },
        dataLabels: { enabled: false },
        grid: { borderColor: colorBorder, strokeDashArray: 3 },
        legend: { position: 'top', horizontalAlign: 'right', labels: { colors: colorText } },
        tooltip: { theme: isDark ? 'dark' : 'light' }
    });

    // 3. Chart 2: Donut Patrimonio con Click Drilldown
    const chartPatrimonio = createChart('#chart-patrimonio-donut', {
        chart: {
            type: 'donut',
            height: 240,
            animations: { enabled: true, speed: 600 },
            events: {
                dataPointSelection: function(event, chartContext, config) {
                    const selCat = topCatRaw[config.dataPointIndex];
                    if (selCat && selCat.categoria_id) {
                        openDrilldown('bienes_categoria', selCat.categoria_id);
                    } else {
                        openDrilldown('bienes_categoria', 0);
                    }
                }
            }
        },
        colors: ['#0284C7', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'],
        series: topCatValues.length > 0 ? topCatValues : [1],
        labels: topCatLabels.length > 0 ? topCatLabels : ['Sin Datos'],
        plotOptions: {
            pie: {
                donut: {
                    size: '68%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total Activos',
                            color: colorMuted,
                            formatter: () => '$<?= number_format((float)$val($patrimonio, 'valor_total') / 1000000, 1) ?>M'
                        }
                    }
                }
            }
        },
        legend: { position: 'bottom', fontSize: '11px', labels: { colors: colorText } },
        dataLabels: { enabled: false },
        stroke: { width: 2, colors: [isDark ? '#0D162B' : '#ffffff'] },
        tooltip: {
            theme: isDark ? 'dark' : 'light',
            y: { formatter: (val) => '$' + new Intl.NumberFormat('en-US').format(val) }
        }
    });

    // 4. Chart 3: Radar Madurez APM
    const chartRadar = createChart('#chart-radar-salud', {
        chart: { type: 'radar', height: 240, toolbar: { show: false } },
        colors: ['#38BDF8'],
        series: [{ name: 'Puntaje APM', data: [99, 100, 96, 94, 98] }],
        xaxis: {
            categories: ['Disponibilidad Bienes', 'CCTV 100%', 'Cumplimiento TH', 'Control Garita', 'Integridad Audit'],
            labels: { style: { colors: [colorMuted, colorMuted, colorMuted, colorMuted, colorMuted], fontSize: '10px' } }
        },
        yaxis: { max: 100, show: false },
        fill: { opacity: 0.3 },
        stroke: { width: 2 },
        markers: { size: 4, colors: ['#38BDF8'], strokeWidth: 0 },
        tooltip: { theme: isDark ? 'dark' : 'light' }
    });

    // 5. Chart 4: Afluencia Horaria Garita
    const chartAfluencia = createChart('#chart-afluencia-horaria', {
        chart: {
            type: 'bar',
            height: 220,
            toolbar: { show: false }
        },
        colors: ['#F59E0B'],
        series: [{ name: 'Ingresos por Hora', data: afluenciaData }],
        xaxis: {
            categories: afluenciaLabels,
            labels: { style: { colors: colorMuted, fontSize: '10px' }, rotate: -45 },
            axisBorder: { color: colorBorder }
        },
        yaxis: {
            labels: { style: { colors: colorMuted, fontSize: '10px' } }
        },
        plotOptions: {
            bar: { borderRadius: 3, columnWidth: '60%' }
        },
        dataLabels: { enabled: false },
        grid: { borderColor: colorBorder, strokeDashArray: 3 },
        tooltip: { theme: isDark ? 'dark' : 'light' }
    });

    // 6. Chart 5: Barras Dotación TH con Click Drilldown
    const chartDotacion = createChart('#chart-dotacion-barras', {
        chart: {
            type: 'bar',
            height: 220,
            toolbar: { show: false },
            events: {
                dataPointSelection: function(event, chartContext, config) {
                    const selUnit = topUnitsRaw[config.dataPointIndex];
                    if (selUnit && selUnit.unidad_id) {
                        openDrilldown('th_unidad', selUnit.unidad_id);
                    } else {
                        openDrilldown('th_unidad', 0);
                    }
                }
            }
        },
        colors: ['#10B981'],
        series: [{ name: 'Personal Activo', data: topUnitsValues.length > 0 ? topUnitsValues : [0] }],
        plotOptions: {
            bar: { horizontal: true, borderRadius: 4, barHeight: '55%' }
        },
        xaxis: {
            categories: topUnitsLabels.length > 0 ? topUnitsLabels : ['-'],
            labels: { style: { colors: colorMuted, fontSize: '11px' } },
            axisBorder: { color: colorBorder }
        },
        yaxis: {
            labels: { style: { colors: colorText, fontSize: '11px', fontWeight: 600 } }
        },
        dataLabels: {
            enabled: true,
            textAnchor: 'start',
            style: { colors: ['#ffffff'], fontSize: '10px', fontWeight: 700 },
            formatter: (val) => val + ' pers.'
        },
        grid: { borderColor: colorBorder, strokeDashArray: 3 },
        tooltip: { theme: isDark ? 'dark' : 'light' }
    });

    // 7. Live Clock
    setInterval(() => {
        const el = document.getElementById('dash-live-clock');
        if (el) {
            const now = new Date();
            el.textContent = now.toTimeString().split(' ')[0];
        }
    }, 1000);

    // 8. Timeframe Selector Handler
    let activeTimeframe = '<?= htmlspecialchars($currentTimeframe, ENT_QUOTES, 'UTF-8') ?>';
    document.querySelectorAll('.timeframe-pill').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.timeframe-pill').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeTimeframe = this.getAttribute('data-tf') || '30d';
            fetchExecutiveData();
        });
    });

    // 9. Auto-Refresh Engine
    let autoRefreshTimer = null;

    function fetchExecutiveData() {
        const refreshIcon = document.getElementById('refresh-icon');
        if (refreshIcon) refreshIcon.classList.add('fa-spin');

        fetch(APP_URL + '/api/dashboard/ejecutivo?timeframe=' + encodeURIComponent(activeTimeframe), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(response => {
            if (response && response.ok && response.data) {
                updateDashboardDOM(response.data);
            }
        })
        .catch(err => console.warn('Error fetching executive dashboard update:', err))
        .finally(() => {
            if (refreshIcon) refreshIcon.classList.remove('fa-spin');
        });
    }

    function updateDashboardDOM(data) {
        if (!data) return;

        const pat = data.patrimonio || {};
        const th = data.talento || {};
        const seg = data.seguridad_operaciones || {};
        const gob = data.gobernanza || {};

        const elValPat = document.getElementById('kpi-valor-patrimonio');
        if (elValPat && pat.valor_total !== undefined) {
            elValPat.textContent = '$' + new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(pat.valor_total);
        }

        const elTotBienes = document.getElementById('kpi-total-bienes');
        if (elTotBienes && pat.total_bienes !== undefined) {
            elTotBienes.textContent = new Intl.NumberFormat('en-US').format(pat.total_bienes);
        }

        const elDispBienes = document.getElementById('kpi-disp-bienes');
        if (elDispBienes && pat.tasa_disponibilidad !== undefined) {
            elDispBienes.innerHTML = `<i class="fa-solid fa-shield-check"></i> ${pat.tasa_disponibilidad}% Operativo`;
        }

        const elThAct = document.getElementById('kpi-th-activos');
        if (elThAct && th.activos !== undefined) {
            elThAct.textContent = new Intl.NumberFormat('en-US').format(th.activos);
        }

        const elThMasa = document.getElementById('kpi-th-masa');
        if (elThMasa && th.masa_salarial !== undefined) {
            elThMasa.textContent = '$' + new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(th.masa_salarial) + '/mes';
        }

        const elVisitasPto = document.getElementById('kpi-visitas-puerto');
        if (elVisitasPto && seg.visitas_en_puerto !== undefined) {
            elVisitasPto.textContent = new Intl.NumberFormat('en-US').format(seg.visitas_en_puerto);
        }

        const elAuditPct = document.getElementById('kpi-audit-pct');
        if (elAuditPct && gob.auditorias_exito_pct !== undefined) {
            elAuditPct.textContent = gob.auditorias_exito_pct + '%';
        }

        const elAuditMes = document.getElementById('kpi-audit-mes');
        if (elAuditMes && gob.auditorias_periodo !== undefined) {
            elAuditMes.textContent = new Intl.NumberFormat('en-US').format(gob.auditorias_periodo);
        }

        const elSesiones = document.getElementById('kpi-sesiones-activas');
        if (elSesiones && gob.sesiones_activas !== undefined) {
            elSesiones.innerHTML = `<i class="fa-solid fa-user-check"></i> ${gob.sesiones_activas} Concurrentes`;
        }

        // Reactive ApexCharts updates
        if (chartTendencia && data.serie_temporal) {
            chartTendencia.updateSeries([
                { name: 'Visitas Garita', data: data.serie_temporal.visitas || [] },
                { name: 'Auditoría Transaccional', data: data.serie_temporal.auditoria || [] }
            ]);
            chartTendencia.updateOptions({
                xaxis: { categories: data.serie_temporal.labels || [] }
            });
        }
    }

    function setupAutoRefresh() {
        const select = document.getElementById('exec-refresh-interval');
        const beacon = document.getElementById('exec-beacon');
        const beaconLabel = document.getElementById('exec-beacon-label');

        if (autoRefreshTimer) {
            clearInterval(autoRefreshTimer);
            autoRefreshTimer = null;
        }

        if (!select) return;
        const intervalMs = parseInt(select.value, 10);

        if (intervalMs > 0) {
            if (beacon) beacon.classList.remove('paused');
            if (beaconLabel) beaconLabel.textContent = 'En vivo';
            autoRefreshTimer = setInterval(fetchExecutiveData, intervalMs);
        } else {
            if (beacon) beacon.classList.add('paused');
            if (beaconLabel) beaconLabel.textContent = 'Pausado';
        }
    }

    const selectEl = document.getElementById('exec-refresh-interval');
    if (selectEl) selectEl.addEventListener('change', setupAutoRefresh);

    const manualBtn = document.getElementById('btn-manual-refresh');
    if (manualBtn) manualBtn.addEventListener('click', fetchExecutiveData);

    setupAutoRefresh();

    // 10. NOC / Wallboard Mode Fullscreen Toggle
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

    const btnWallboard = document.getElementById('btn-toggle-wallboard');
    if (btnWallboard) {
        btnWallboard.addEventListener('click', window.toggleWallboardMode);
    }

    // 11. Interactive Drilldown Drawer Engine with DataTables
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

            // Destroy previous DataTable instance cleanly
            if (drawerDtInstance) {
                drawerDtInstance.destroy();
                drawerDtInstance = null;
            } else if (window.DataTable && window.DataTable.isDataTable(table)) {
                window.DataTable(table).destroy();
            }

            // Build Headers
            const cols = d.columnas || [];
            let theadHtml = '<tr>';
            cols.forEach(c => theadHtml += `<th>${c}</th>`);
            theadHtml += '</tr>';
            if (thead) thead.innerHTML = theadHtml;

            // Build Body Rows
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

            // Re-initialize DataTable with Full Search and Sorting
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

})();
</script>
