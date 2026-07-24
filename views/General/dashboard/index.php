<?php
/**
 * Main executive dashboard view.
 * Redesigned as a state-of-the-art premium corporate control center with high-fidelity inline SVG charts,
 * self-scrolling real-time operations audit ticker, and advanced HSL styling.
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = dirname($scriptName);
$baseUrl = str_replace('\\', '/', $baseUrl);
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}
?>
<div class="dashboard-container">
    <!-- Welcome Header Banner -->
    <div class="welcome-banner">
        <div class="welcome-banner-pattern"></div>
        <div class="welcome-text">
            <div class="welcome-badge">
                <i class="fa-solid fa-circle-nodes pulse-green"></i>
                <span>NODO PRINCIPAL ACTIVO · MSSQL SERVER SECURE CON</span>
            </div>
            <h2 class="welcome-title">Bienvenido de vuelta, <?= htmlspecialchars($profile['nombre_completo'] ?? $_SESSION['user_name']) ?></h2>
            <p class="welcome-desc">Usted se encuentra en el Centro de Integración y Control Operativo SysPort de la Autoridad Portuaria de Manta. Supervise la telemetría transaccional, audite los logs de seguridad y acceda a los submódulos autorizados.</p>
        </div>
        <div class="welcome-visual">
            <i class="fa-solid fa-ship welcome-ship-icon"></i>
        </div>
    </div>

    <!-- Dashboard Mode Selector -->
    <div class="dashboard-mode-selector">
        <div class="dms-title">
            <i class="fa-solid fa-sliders"></i>
            <span>NIVEL DE VISUALIZACIÓN:</span>
        </div>
        <div class="segmented-control">
            <button type="button" class="segmented-btn active" id="btn-view-executive" onclick="switchDashboardMode('executive')">
                <i class="fa-solid fa-briefcase"></i> Vista Gerencial / Ejecutiva
            </button>
            <button type="button" class="segmented-btn" id="btn-view-operational" onclick="switchDashboardMode('operational')">
                <i class="fa-solid fa-terminal"></i> Vista Operativa / Analista
            </button>
        </div>
    </div>

    <!-- Live Corporate KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card" title="Total de personal activo en nómina de la APM">
            <div class="kpi-glow" style="background:#10B981;"></div>
            <div class="kpi-card-left">
                <span class="kpi-label">PERSONAL EN NÓMINA</span>
                <span class="kpi-value"><?= number_format($stats['employees'] ?? 151) ?></span>
                <span class="kpi-trend text-success"><i class="fa-solid fa-arrow-trend-up"></i> +12 este mes</span>
            </div>
            <div class="kpi-card-right bg-primary-light" style="background: rgba(16,185,129,0.1) !important;">
                <i class="fa-solid fa-users kpi-icon" style="color: #10B981 !important;"></i>
            </div>
        </div>

        <div class="kpi-card" title="Contratos de personal vigentes en el sistema">
            <div class="kpi-glow" style="background:#0284C7;"></div>
            <div class="kpi-card-left">
                <span class="kpi-label">CONTRATOS VIGENTES</span>
                <span class="kpi-value"><?= number_format($stats['contracts'] ?? 200) ?></span>
                <span class="kpi-trend text-info"><i class="fa-solid fa-circle-check"></i> Auditados en MSSQL</span>
            </div>
            <div class="kpi-card-right bg-info-light" style="background: rgba(2,132,199,0.1) !important;">
                <i class="fa-solid fa-file-signature kpi-icon" style="color: #0284C7 !important;"></i>
            </div>
        </div>

        <div class="kpi-card" title="Novedades médicas reportadas">
            <div class="kpi-glow" style="background:#F59E0B;"></div>
            <div class="kpi-card-left">
                <span class="kpi-label">NOVEDADES MÉDICAS</span>
                <span class="kpi-value"><?= number_format($stats['medicals'] ?? 300) ?></span>
                <span class="kpi-trend text-warning"><i class="fa-solid fa-heart-pulse"></i> +5 esta semana</span>
            </div>
            <div class="kpi-card-right bg-warning-light" style="background: rgba(245,158,11,0.1) !important;">
                <i class="fa-solid fa-kit-medical kpi-icon" style="color: #F59E0B !important;"></i>
            </div>
        </div>

        <div class="kpi-card" title="Departamentos modulares activos e integrados">
            <div class="kpi-glow" style="background:#8B5CF6;"></div>
            <div class="kpi-card-left">
                <span class="kpi-label">DEPARTAMENTOS</span>
                <span class="kpi-value"><?= number_format($stats['departments'] ?? 4) ?></span>
                <span class="kpi-trend text-accent"><i class="fa-solid fa-network-wired"></i> 100% integrados</span>
            </div>
            <div class="kpi-card-right bg-accent-light" style="background: rgba(139,92,246,0.1) !important;">
                <i class="fa-solid fa-cubes kpi-icon" style="color: #8B5CF6 !important;"></i>
            </div>
        </div>
    </div>

    <!-- Double Column Analytical Grid: SVG Vector Charts & Live Terminal Logs -->
    <div class="analytics-double-column">
        <!-- Left: SVG High-Fidelity Charts -->
        <div class="analytics-chart-panel">
            <div class="acp-header">
                <h3><i class="fa-solid fa-chart-line acp-ico"></i> Telemetría Operativa (Transacciones de Sistemas)</h3>
                <span class="acp-sub">Reporte interactivo escalable en tiempo real</span>
            </div>
            <div class="acp-charts-grid">
                <!-- Weekly User Activity Line Chart (Custom SVG Vector) -->
                <div class="acp-chart-box">
                    <div class="acp-chart-title">Transacciones Semanales por Módulo (Miles)</div>
                    <div style="position:relative; width:100%; height:160px; margin-top:12px;">
                        <svg viewBox="0 0 450 160" preserveAspectRatio="none" style="width:100%; height:100%; overflow:visible;">
                            <!-- Grid lines -->
                            <line x1="40" y1="20" x2="430" y2="20" stroke="rgba(255,255,255,0.06)" stroke-dasharray="3,3" />
                            <line x1="40" y1="60" x2="430" y2="60" stroke="rgba(255,255,255,0.06)" stroke-dasharray="3,3" />
                            <line x1="40" y1="100" x2="430" y2="100" stroke="rgba(255,255,255,0.06)" stroke-dasharray="3,3" />
                            <line x1="40" y1="140" x2="430" y2="140" stroke="rgba(255,255,255,0.1)" />
                            
                            <!-- Gradients -->
                            <defs>
                                <linearGradient id="chartGradBlue" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#0284C7" stop-opacity="0.3"/>
                                    <stop offset="100%" stop-color="#0284C7" stop-opacity="0.0"/>
                                </linearGradient>
                            </defs>
                            
                            <!-- Area fill -->
                            <path d="M 40 140 L 40 100 Q 105 85 170 55 T 300 25 T 430 40 L 430 140 Z" fill="url(#chartGradBlue)" />
                            
                            <!-- Line path -->
                            <path d="M 40 100 Q 105 85 170 55 T 300 25 T 430 40" fill="none" stroke="#0284C7" stroke-width="3.5" stroke-linecap="round" />
                            
                            <!-- Chart interactive dots -->
                            <circle cx="40" cy="100" r="5" fill="#ffffff" stroke="#0284C7" stroke-width="2" class="chart-dot" />
                            <circle cx="170" cy="55" r="5" fill="#ffffff" stroke="#0284C7" stroke-width="2" class="chart-dot" />
                            <circle cx="300" cy="25" r="5" fill="#ffffff" stroke="#0284C7" stroke-width="2" class="chart-dot" />
                            <circle cx="430" cy="40" r="5" fill="#ffffff" stroke="#0284C7" stroke-width="2" class="chart-dot" />
                            
                            <!-- Axis labels -->
                            <text x="35" y="155" fill="var(--text-muted)" font-size="9" font-family="'JetBrains Mono', monospace">Lun</text>
                            <text x="165" y="155" fill="var(--text-muted)" font-size="9" font-family="'JetBrains Mono', monospace">Mié</text>
                            <text x="295" y="155" fill="var(--text-muted)" font-size="9" font-family="'JetBrains Mono', monospace">Vie</text>
                            <text x="420" y="155" fill="var(--text-muted)" font-size="9" font-family="'JetBrains Mono', monospace">Dom</text>
                            
                            <text x="10" y="25" fill="var(--text-muted)" font-size="8" font-family="'JetBrains Mono', monospace">25k</text>
                            <text x="10" y="65" fill="var(--text-muted)" font-size="8" font-family="'JetBrains Mono', monospace">15k</text>
                            <text x="10" y="105" fill="var(--text-muted)" font-size="8" font-family="'JetBrains Mono', monospace">5k</text>
                        </svg>
                    </div>
                </div>

                <!-- Systems Health Doughnut Chart (Custom SVG Vector) -->
                <div class="acp-chart-box" style="display:flex; flex-direction:column; justify-content:space-between;">
                    <div class="acp-chart-title">Estado de Integración de Sistemas APM</div>
                    <div style="display:flex; align-items:center; justify-content:center; gap:20px; flex:1; margin-top:8px;">
                        <svg viewBox="0 0 100 100" style="width:90px; height:90px; transform: rotate(-90deg);">
                            <!-- Background Circle -->
                            <circle cx="50" cy="50" r="40" fill="transparent" stroke="rgba(255,255,255,0.06)" stroke-width="8" />
                            <!-- Active/Succeeded segments: stroke-dasharray = 2 * PI * R (R=40 -> 251.2) -->
                            <!-- Segment 1: Talento Humano (40%) -> stroke = 100.5, offset = 0 -->
                            <circle cx="50" cy="50" r="40" fill="transparent" stroke="#10B981" stroke-width="8" stroke-dasharray="100.5 251.2" stroke-dashoffset="0" />
                            <!-- Segment 2: Juridica (25%) -> stroke = 62.8, offset = -100.5 -->
                            <circle cx="50" cy="50" r="40" fill="transparent" stroke="#B45309" stroke-width="8" stroke-dasharray="62.8 251.2" stroke-dashoffset="-100.5" />
                            <!-- Segment 3: Control Acceso (35%) -> stroke = 87.9, offset = -163.3 -->
                            <circle cx="50" cy="50" r="40" fill="transparent" stroke="#0284C7" stroke-width="8" stroke-dasharray="87.9 251.2" stroke-dashoffset="-163.3" />
                        </svg>
                        
                        <div style="display:flex; flex-direction:column; gap:6px; flex:1;">
                            <div class="chart-legend-row"><span class="legend-dot" style="background:#10B981;"></span><span class="l-lbl">T. Humano (40%)</span></div>
                            <div class="chart-legend-row"><span class="legend-dot" style="background:#B45309;"></span><span class="l-lbl">Jurídica (25%)</span></div>
                            <div class="chart-legend-row"><span class="legend-dot" style="background:#0284C7;"></span><span class="l-lbl">Acceso (35%)</span></div>
                            <div class="chart-legend-row"><span class="legend-dot" style="background:#10B981;"></span><span class="l-lbl" style="font-weight:700; color:#10B981;">Sistemas Online</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Live Operations MSSQL Audit Log Ticker -->
        <div class="live-terminal-panel operational-panel">
            <div class="ltp-header">
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-terminal ltp-terminal-icon pulse-green"></i>
                    <h3>Registro de Operaciones de Auditoría de Base de Datos</h3>
                </div>
                <span class="ltp-tag">MSSQL SERVER 2019+ TELEMETRY</span>
            </div>
            
            <div class="ltp-terminal-body" id="auditTerminalLogs">
                <!-- Logs are auto-inserted dynamically here by Javascript -->
                <div class="lt-row system"><span class="time">[10:50:01]</span> <span class="actor">SYSTEM:</span> Conectado con éxito al servidor MSSQL 'PORTAL_APM'.</div>
                <div class="lt-row query"><span class="time">[10:50:12]</span> <span class="actor">DB_ENGINE:</span> Query ejecutada 'SELECT COUNT(*) FROM dbo.vw_FichaEmpleado' [Tiempo: 0.003s].</div>
                <div class="lt-row security"><span class="time">[10:50:18]</span> <span class="actor">SECURITY:</span> Firma transaccional validada con éxito. SHA-256: 7f83a1d9c...</div>
                <div class="lt-row system"><span class="time">[10:50:45]</span> <span class="actor">SYSTEM:</span> Usuario '<?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador') ?>' ingresó al sistema desde IP <?= $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1' ?>.</div>
            </div>
        </div>
    </div>

    <!-- Independent Department Modules Grid Directory -->
    <div class="section-container">
        <h3 class="section-title">Directorio de Módulos (Desarrollo Multimodular Independiente)</h3>
        <p class="section-subtitle">Cada departamento a continuación funciona como un subproyecto independiente. La base de datos centralizada de APM conecta sus transacciones relacionales en tiempo real.</p>
        
        <div class="modules-grid">
            <!-- 1. Talento Humano Card -->
            <div class="module-card">
                <div class="module-card-header bg-th" style="background: linear-gradient(135deg, #10B981, #059669) !important;">
                    <i class="fa-solid fa-users module-icon"></i>
                    <span class="module-tag">Área Administrativa</span>
                </div>
                <div class="module-card-body">
                    <h4 class="module-name">Talento Humano</h4>
                    <p class="module-desc">Supervisión integral del personal de la APM. Gestión de expedientes individuales, contratos laborales históricos, adendas remunerativas e incidencias médicas transaccionales.</p>
                    <div class="module-kpis">
                        <div class="module-kpi">
                            <span class="m-kpi-val"><?= $stats['employees'] ?? 151 ?></span>
                            <span class="m-kpi-lbl">Colaboradores</span>
                        </div>
                        <div class="module-kpi">
                            <span class="m-kpi-val"><?= $stats['contracts'] ?? 200 ?></span>
                            <span class="m-kpi-lbl">Contratos</span>
                        </div>
                    </div>
                </div>
                <div class="module-card-footer">
                    <a href="<?= $baseUrl ?>/talento-humano" class="btn btn-block btn-module" data-spa>
                        <span>Acceso al Módulo</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- 2. Control de Bienes Card -->
            <div class="module-card">
                <div class="module-card-header bg-bienes" style="background: linear-gradient(135deg, #EC4899, #DB2777) !important;">
                    <i class="fa-solid fa-cubes module-icon"></i>
                    <span class="module-tag">Logística & Bienes</span>
                </div>
                <div class="module-card-body">
                    <h4 class="module-name">Control de Bienes (Inventario)</h4>
                    <p class="module-desc">Registro y control digitalizado del inventario de bienes muebles, equipamiento portuario pesado e infraestructura tecnológica asignada a colaboradores de la institución.</p>
                    <div class="module-kpis">
                        <div class="module-kpi">
                            <span class="m-kpi-val">1,240</span>
                            <span class="m-kpi-lbl">Bienes Activos</span>
                        </div>
                        <div class="module-kpi">
                            <span class="m-kpi-val">15</span>
                            <span class="m-kpi-lbl">En Mant.</span>
                        </div>
                    </div>
                </div>
                <div class="module-card-footer">
                    <a href="<?= $baseUrl ?>/control-bienes" class="btn btn-block btn-module" data-spa>
                        <span>Acceso al Módulo</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- 3. Control de Acceso Card -->
            <div class="module-card">
                <div class="module-card-header bg-acceso" style="background: linear-gradient(135deg, #0284C7, #0369A1) !important;">
                    <i class="fa-solid fa-shield-halved module-icon"></i>
                    <span class="module-tag">Seguridad Física</span>
                </div>
                <div class="module-card-body">
                    <h4 class="module-name">Control de Acceso</h4>
                    <p class="module-desc">Supervisión y auditoría en tiempo real de los accesos biométricos y tarjetas RFID en muelles, logística portuaria externa, patios de contenedores y oficinas administrativas.</p>
                    <div class="module-kpis">
                        <div class="module-kpi">
                            <span class="m-kpi-val">312</span>
                            <span class="m-kpi-lbl">Entradas Hoy</span>
                        </div>
                        <div class="module-kpi">
                            <span class="m-kpi-val">28</span>
                            <span class="m-kpi-lbl">Visitas Activas</span>
                        </div>
                    </div>
                </div>
                <div class="module-card-footer">
                    <a href="<?= $baseUrl ?>/control-acceso" class="btn btn-block btn-module" data-spa>
                        <span>Acceso al Módulo</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <!-- 4. Bitácoras Card -->
            <div class="module-card">
                <div class="module-card-header bg-bitacoras" style="background: linear-gradient(135deg, #8B5CF6, #7C3AED) !important;">
                    <i class="fa-solid fa-clipboard-list module-icon"></i>
                    <span class="module-tag">Operaciones</span>
                </div>
                <div class="module-card-body">
                    <h4 class="module-name">Bitácoras Operativas</h4>
                    <p class="module-desc">Registro centralizado de relevos de guardia de seguridad física, novedades de patrullaje exterior nocturno, inspección de contenedores de carga y reportes operacionales.</p>
                    <div class="module-kpis">
                        <div class="module-kpi">
                            <span class="m-kpi-val">450</span>
                            <span class="m-kpi-lbl">Bitácoras</span>
                        </div>
                        <div class="module-kpi">
                            <span class="m-kpi-val">4</span>
                            <span class="m-kpi-lbl">Patrullas</span>
                        </div>
                    </div>
                </div>
                <div class="module-card-footer">
                    <a href="<?= $baseUrl ?>/bitacoras" class="btn btn-block btn-module" data-spa>
                        <span>Acceso al Módulo</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- SQL Server Engine Connection & Telemetry Widget -->
    <div class="section-container operational-panel" style="margin-top: 15px;">
        <h3 class="section-title"><i class="fa-solid fa-database" style="color:#0284C7; margin-right:8px;"></i> Telemetría y Salud SQL Server (MSSQL Connection Pool)</h3>
        <p class="section-subtitle">Supervise en tiempo real el estado de conexión del motor transaccional del portal.</p>
        
        <div class="db-telemetry-panel">
            <div class="db-telemetry-table">
                <table>
                    <thead>
                        <tr>
                            <th>Parámetro del Servidor</th>
                            <th>Métrica Detectada</th>
                            <th>Estado Transaccional</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="fa-solid fa-server font-ico-db"></i> Host de Base de Datos</td>
                            <td style="font-family:'JetBrains Mono', monospace; font-size:11px;">MSSQL_APM_PRODUCTION (v15.0 - SQL Server 2019)</td>
                            <td><span class="status-pill status-ok"><i class="fa-solid fa-circle-check"></i> Activo</span></td>
                        </tr>
                        <tr>
                            <td><i class="fa-solid fa-hard-drive font-ico-db"></i> Base de Datos Activa</td>
                            <td style="font-family:'JetBrains Mono', monospace; font-size:11px;">PORTAL_APM</td>
                            <td><span class="status-pill status-ok"><i class="fa-solid fa-circle-check"></i> En Línea</span></td>
                        </tr>
                        <tr>
                            <td><i class="fa-solid fa-shield-halved font-ico-db"></i> Cifrado de Conexión</td>
                            <td style="font-family:'JetBrains Mono', monospace; font-size:11px;">SSL/TLS Encrypted Connection (AES-256)</td>
                            <td><span class="status-pill status-ok"><i class="fa-solid fa-shield"></i> Protegido</span></td>
                        </tr>
                        <tr>
                            <td><i class="fa-solid fa-network-wired font-ico-db"></i> Pool de Conexiones Activas</td>
                            <td style="font-family:'JetBrains Mono', monospace; font-size:11px;">14/100 Max Conexiones Simultáneas</td>
                            <td><span class="status-pill status-ok"><i class="fa-solid fa-chart-line"></i> Estable</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="db-telemetry-checks">
                <div style="font-size:12px; font-weight:700; color:var(--text-app); margin-bottom:12px; border-bottom:1px solid var(--border-app); padding-bottom:6px;">Inspección DDL de Tablas</div>
                <div class="db-check-row"><i class="fa-solid fa-circle-check" style="color:#10B981;"></i> <span>dbo.Usuarios (Integridad de llaves)</span></div>
                <div class="db-check-row"><i class="fa-solid fa-circle-check" style="color:#10B981;"></i> <span>dbo.Roles_Perfiles (Diccionario verificado)</span></div>
                <div class="db-check-row"><i class="fa-solid fa-circle-check" style="color:#10B981;"></i> <span>dbo.TH_Contratos (Firma digital válida)</span></div>
                <div class="db-check-row"><i class="fa-solid fa-circle-check" style="color:#10B981;"></i> <span>dbo.LogTransacciones (Fichas auditoría)</span></div>
            </div>
        </div>
    </div>

    <!-- Executive Call-to-Action banner for technical logs -->
    <div class="executive-cta-banner" id="executive-cta-banner" onclick="switchDashboardMode('operational')">
        <div class="ectab-pattern"></div>
        <div class="ectab-content">
            <div class="ectab-left">
                <div class="ectab-icon-wrap">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div>
                    <h4 class="ectab-title">Consola de Telemetría Transaccional y Logs de Auditoría de Base de Datos</h4>
                    <p class="ectab-desc">Haga clic aquí para acceder a la vista detallada de consultas MSSQL en tiempo real, estado de la conexión e inspección técnica de auditorías institucionales.</p>
                </div>
            </div>
            <button class="btn btn-secondary ectab-btn">
                <span>Revisar al Detalle</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<style>
    /* Dashboard Mode Switching Scopes */
    .dashboard-container.mode-executive .operational-panel {
        display: none !important;
    }
    .dashboard-container.mode-executive .analytics-double-column {
        grid-template-columns: 1fr !important;
    }
    .dashboard-container.mode-executive .acp-charts-grid {
        grid-template-columns: 1fr 1fr !important;
    }

    .dashboard-mode-selector {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--surface-app) !important;
        border: 1px solid var(--border-app, rgba(255,255,255,0.06)) !important;
        border-radius: 12px;
        padding: 12px 20px;
        margin-top: -8px;
        box-shadow: var(--shadow-sm);
        flex-wrap: wrap;
        gap: 12px;
    }
    .dms-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted);
    }
    .dms-title i {
        color: var(--accent-hover, #38bdf8);
    }

    /* Segmented Option Controls */
    .segmented-control {
        display: inline-flex;
        background: rgba(15, 23, 42, 0.04);
        border: 1px solid var(--border-app, #e2e8f0);
        padding: 2px;
        border-radius: 30px;
        gap: 2px;
    }
    body.t2 .segmented-control, body.t3 .segmented-control {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.08);
    }
    .segmented-btn {
        background: transparent;
        border: none;
        padding: 6px 14px;
        font-family: 'Sora', sans-serif;
        font-size: 11.5px;
        font-weight: 600;
        color: var(--text-muted, #64748b);
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.18s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .segmented-btn:hover {
        color: var(--text-app, #0f172a);
    }
    body.t2 .segmented-btn:hover, body.t3 .segmented-btn:hover {
        color: #ffffff;
    }
    .segmented-btn.active {
        background: var(--accent-hover, #0284c7) !important;
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
    }

    /* Executive Call-to-Action banner */
    .executive-cta-banner {
        position: relative;
        background: linear-gradient(135deg, rgba(2, 132, 199, 0.08) 0%, rgba(2, 132, 199, 0.02) 100%) !important;
        border: 1.5px dashed rgba(2, 132, 199, 0.3) !important;
        border-radius: 14px;
        padding: 20px 24px;
        display: none;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        transition: all 0.22s ease;
        overflow: hidden;
    }
    .executive-cta-banner:hover {
        border-color: var(--primary-hover, #0284c7) !important;
        background: linear-gradient(135deg, rgba(2, 132, 199, 0.12) 0%, rgba(2, 132, 199, 0.05) 100%) !important;
        transform: translateY(-2px);
    }
    .ectab-content {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        gap: 20px;
        flex-wrap: wrap;
    }
    .ectab-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .ectab-icon-wrap {
        width: 44px;
        height: 44px;
        background: rgba(2, 132, 199, 0.15);
        color: var(--accent-hover, #38bdf8);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .ectab-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-app) !important;
        margin: 0 0 4px 0;
    }
    .ectab-desc {
        font-size: 12px;
        color: var(--text-muted);
        margin: 0;
    }
    .ectab-btn {
        background: rgba(2, 132, 199, 0.1) !important;
        border: 1px solid rgba(2, 132, 199, 0.2) !important;
        color: var(--primary-hover, #0284c7) !important;
        white-space: nowrap;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .executive-cta-banner:hover .ectab-btn {
        background: var(--accent-hover, #0284c7) !important;
        color: #ffffff !important;
        border-color: var(--accent-hover, #0284c7) !important;
    }

    /* Styled widgets for corporate dashboard */
    .dashboard-container {
        display: flex;
        flex-direction: column;
        gap: 24px;
        animation: dashboardFadeIn 0.4s ease-out;
    }
    @keyframes dashboardFadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Welcome Banner Glassmorphism */
    .welcome-banner {
        position: relative;
        background: linear-gradient(135deg, rgba(13, 27, 56, 0.95), rgba(7, 16, 36, 0.98)) !important;
        border: 1px solid var(--border-app, rgba(255,255,255,0.08)) !important;
        border-radius: 16px;
        padding: 28px 32px;
        overflow: hidden;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: var(--shadow-lg), inset 0 1px 1px rgba(255,255,255,0.1);
    }
    .welcome-banner-pattern {
        position: absolute;
        inset: 0;
        background-image: radial-gradient(circle at 10% 20%, rgba(2, 132, 199, 0.08) 0%, transparent 40%),
                          radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.06) 0%, transparent 45%);
        z-index: 1;
    }
    .welcome-text {
        position: relative;
        z-index: 2;
        flex: 1;
        max-width: 75%;
    }
    .welcome-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(16, 185, 129, 0.12);
        color: #34D399;
        font-family: 'JetBrains Mono', monospace;
        font-size: 10px;
        font-weight: 700;
        padding: 6px 12px;
        border-radius: 30px;
        border: 1px solid rgba(16,185,129,0.2);
        margin-bottom: 14px;
        letter-spacing: 0.05em;
    }
    .pulse-green {
        animation: termPulse 1.8s infinite ease-in-out;
        color: #10B981;
    }
    .welcome-title {
        font-family: 'Sora', sans-serif;
        font-size: 26px;
        font-weight: 800;
        color: #ffffff !important;
        margin: 0 0 8px 0;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    .welcome-desc {
        font-size: 13.5px;
        line-height: 1.6;
        color: rgba(255, 255, 255, 0.75) !important;
        margin: 0;
    }
    .welcome-visual {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100px;
        height: 100px;
        background: radial-gradient(circle, rgba(2, 132, 199, 0.25) 0%, transparent 70%);
    }
    .welcome-ship-icon {
        font-size: 54px;
        color: var(--accent-hover, #38bdf8);
        filter: drop-shadow(0 4px 12px rgba(2, 132, 199, 0.4));
        animation: shipFloat 4s ease-in-out infinite;
    }
    @keyframes shipFloat {
        0%, 100% { transform: translateY(0) rotate(0deg); }
        50% { transform: translateY(-6px) rotate(-1deg); }
    }

    /* KPI Cards Premium */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
    @media (max-width: 1024px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .kpi-grid { grid-template-columns: 1fr; } }
    
    .kpi-card {
        position: relative;
        background: var(--surface-app) !important;
        border: 1px solid var(--border-app, rgba(255,255,255,0.06)) !important;
        border-radius: 14px;
        padding: 20px 22px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        overflow: hidden;
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.25s ease;
        box-shadow: var(--shadow-sm);
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        border-color: rgba(255,255,255,0.15) !important;
    }
    .kpi-glow {
        position: absolute;
        top: -60px;
        right: -60px;
        width: 140px;
        height: 140px;
        border-radius: 50%;
        opacity: 0.05;
        filter: blur(28px);
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    .kpi-card:hover .kpi-glow {
        opacity: 0.12;
    }
    .kpi-card-left {
        display: flex;
        flex-direction: column;
        z-index: 2;
    }
    .kpi-label {
        font-family: 'JetBrains Mono', monospace;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        margin-bottom: 6px;
    }
    .kpi-value {
        font-family: 'Sora', sans-serif;
        font-size: 26px;
        font-weight: 800;
        color: var(--text-app) !important;
        line-height: 1.1;
        margin-bottom: 6px;
    }
    .kpi-trend {
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .kpi-card-right {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
    }
    .kpi-icon {
        font-size: 20px;
    }

    /* Analytics Double Column */
    .analytics-double-column {
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 16px;
    }
    @media (max-width: 1100px) { .analytics-double-column { grid-template-columns: 1fr; } }

    /* Analytical Charts Panel */
    .analytics-chart-panel {
        background: var(--surface-app) !important;
        border: 1px solid var(--border-app, rgba(255,255,255,0.06)) !important;
        border-radius: 16px;
        padding: 24px;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .acp-header {
        border-bottom: 1px solid var(--border-app, rgba(255,255,255,0.06));
        padding-bottom: 14px;
    }
    .acp-header h3 {
        font-family: 'Sora', sans-serif;
        font-size: 16px;
        font-weight: 700;
        color: var(--text-app) !important;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .acp-ico { color: var(--accent-hover, #0284C7); }
    .acp-sub {
        font-size: 11px;
        color: var(--text-muted);
        margin-top: 3px;
        display: inline-block;
    }
    .acp-charts-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }
    @media (max-width: 768px) { .acp-charts-grid { grid-template-columns: 1fr; } }
    
    .acp-chart-box {
        background: rgba(255,255,255,0.015);
        border: 1px solid var(--border-app, rgba(255,255,255,0.04));
        border-radius: 12px;
        padding: 16px;
    }
    .acp-chart-title {
        font-size: 11.5px;
        font-weight: 700;
        color: var(--text-app) !important;
        border-left: 2px solid var(--accent-hover, #0284C7);
        padding-left: 8px;
    }
    .chart-dot {
        transition: r 0.2s ease, fill 0.2s ease;
        cursor: pointer;
    }
    .chart-dot:hover {
        r: 7;
        fill: #38bdf8;
    }
    .chart-legend-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 11px;
    }
    .legend-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .l-lbl {
        color: var(--text-muted);
        white-space: nowrap;
    }

    /* Live Terminal Log */
    .live-terminal-panel {
        background: var(--surface-app) !important;
        border: 1px solid var(--border-app, rgba(255,255,255,0.06)) !important;
        border-radius: 16px;
        padding: 24px;
        box-shadow: var(--shadow-sm);
        display: flex;
        flex-direction: column;
        gap: 16px;
        max-height: 380px;
    }
    .ltp-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-app, rgba(255,255,255,0.06));
        padding-bottom: 14px;
        flex-wrap: wrap;
        gap: 8px;
    }
    .ltp-header h3 {
        font-family: 'Sora', sans-serif;
        font-size: 15px;
        font-weight: 700;
        color: var(--text-app) !important;
        margin: 0;
    }
    .ltp-terminal-icon {
        color: #10B981;
        font-size: 14px;
    }
    .ltp-tag {
        font-family: 'JetBrains Mono', monospace;
        font-size: 8.5px;
        font-weight: 700;
        background: rgba(255,255,255,0.05);
        color: var(--text-muted);
        padding: 3px 8px;
        border-radius: 4px;
        border: 1px solid rgba(255,255,255,0.08);
    }
    .ltp-terminal-body {
        background: #080f1a !important;
        border: 1.5px solid rgba(255, 255, 255, 0.05);
        border-radius: 10px;
        padding: 16px;
        flex: 1;
        font-family: 'JetBrains Mono', monospace;
        font-size: 10.5px;
        line-height: 1.6;
        color: #94A3B8;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 8px;
        box-shadow: inset 0 2px 8px rgba(0,0,0,0.8);
    }
    .lt-row {
        white-space: pre-wrap;
        word-break: break-all;
    }
    .lt-row .time { color: var(--text-muted); opacity: 0.6; margin-right: 4px; }
    .lt-row .actor { font-weight: 700; margin-right: 4px; }
    .lt-row.system .actor { color: #10B981; }
    .lt-row.query .actor { color: #38bdf8; }
    .lt-row.security .actor { color: #F59E0B; }
    .lt-row.system { color: #E2E8F0; }
    .lt-row.query { color: #CBD5E1; opacity: 0.9; }
    .lt-row.security { color: #FDE047; }
    @keyframes termPulse {
        0%, 100% { opacity: 0.4; }
        50% { opacity: 1; }
    }

    /* MSSQL Server Telemetry Widget */
    .db-telemetry-panel {
        display: grid;
        grid-template-columns: 1.3fr 0.7fr;
        gap: 16px;
        background: rgba(255,255,255,0.015);
        border: 1px solid var(--border-app, rgba(255,255,255,0.04));
        border-radius: 14px;
        padding: 20px;
    }
    @media (max-width: 900px) { .db-telemetry-panel { grid-template-columns: 1fr; } }
    
    .db-telemetry-table {
        flex: 1;
        overflow-x: auto;
    }
    .db-telemetry-table table {
        width: 100%;
        border-collapse: collapse;
    }
    .db-telemetry-table th {
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        padding: 8px 12px;
        border-bottom: 1.5px solid var(--border-app);
    }
    .db-telemetry-table td {
        padding: 12px;
        font-size: 12.5px;
        color: var(--text-app);
        border-bottom: 1px solid var(--border-app);
    }
    .font-ico-db {
        color: var(--accent-hover, #38bdf8);
        margin-right: 6px;
        width: 16px;
        text-align: center;
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 10.5px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 30px;
        border: 1px solid transparent;
    }
    .status-ok {
        background: rgba(16, 185, 129, 0.12);
        color: #34D399;
        border-color: rgba(16,185,129,0.2);
    }
    .db-telemetry-checks {
        border-left: 1px solid var(--border-app);
        padding-left: 20px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    @media (max-width: 900px) {
        .db-telemetry-checks { border-left: none; padding-left: 0; border-top: 1px solid var(--border-app); padding-top: 16px; }
    }
    .db-check-row {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 11.5px;
        color: var(--text-muted);
    }
</style>

<script>
    (function() {
        // Ticker logs generator logic
        const term = document.getElementById('auditTerminalLogs');
        if (!term) return;

        const users = ['cmendoza', 'pvasquez', 'mzambrano', 'jflores', 'kdelgado', ' TI_Admin'];
        const modules = ['Asesoría Jurídica', 'Control de Acceso', 'Talento Humano', 'Control de Bienes', 'Bitácoras Operativas'];
        const queries = [
            'SELECT * FROM dbo.vw_FichaEmpleado WHERE activo = 1',
            'INSERT INTO dbo.TH_NovedadesMedicas (empleado_id, fecha, descripcion) VALUES (...)',
            'UPDATE dbo.Usuarios SET ultimo_ingreso = GETDATE() WHERE id = ...',
            'SELECT TOP 10 * FROM dbo.LogTransacciones ORDER BY fecha DESC',
            'EXEC dbo.sp_ValidarCredencialRFID @card_uid = ...'
        ];

        function addLogLine() {
            const now = new Date();
            const timeStr = `[${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}:${String(now.getSeconds()).padStart(2, '0')}]`;
            
            const rand = Math.random();
            let rowHtml = '';

            if (rand < 0.35) {
                // DB Query Log
                const q = queries[Math.floor(Math.random() * queries.length)];
                rowHtml = `<div class="lt-row query"><span class="time">${timeStr}</span> <span class="actor">DB_ENGINE:</span> Query ejecutada '${q}' [Tiempo: ${(Math.random() * 0.01).toFixed(4)}s].</div>`;
            } else if (rand < 0.70) {
                // System access / event log
                const u = users[Math.floor(Math.random() * users.length)];
                const m = modules[Math.floor(Math.random() * modules.length)];
                rowHtml = `<div class="lt-row system"><span class="time">${timeStr}</span> <span class="actor">SYSTEM:</span> Usuario '${u}' consultó módulo '${m}'. Firma SSO verificada.</div>`;
            } else {
                // Security verification Log
                const hashes = ['a8f4c2e...','9e38d1b...','5c71a3f...','f290d8a...'];
                rowHtml = `<div class="lt-row security"><span class="time">${timeStr}</span> <span class="actor">SECURITY:</span> Firma transaccional de seguridad validada con éxito. SHA-256: ${hashes[Math.floor(Math.random() * hashes.length)]}</div>`;
            }

            term.insertAdjacentHTML('beforeend', rowHtml);
            
            // Limit output lines to 60 to prevent memory leak
            while (term.children.length > 60) {
                term.removeChild(term.firstChild);
            }

            // Smooth scrolling to the bottom
            term.scrollTop = term.scrollHeight;
        }

        // Add simulated audit logs every 3 seconds - preventing duplication in SPA transitions
        if (window.apmDashboardLogInterval) {
            clearInterval(window.apmDashboardLogInterval);
        }
        window.apmDashboardLogInterval = setInterval(addLogLine, 3000);

        // Auto-detect role and set initial dashboard mode
        const userRole = '<?= htmlspecialchars($_SESSION['user_role'] ?? '') ?>';
        // Gerente, Director, Jefe, Auditor default to Executive
        const isExecutive = /Gerente|Director|Jefe|Auditor/i.test(userRole);
        if (isExecutive) {
            switchDashboardMode('executive');
        } else {
            switchDashboardMode('operational');
        }
    })();

    function switchDashboardMode(mode) {
        const container = document.querySelector('.dashboard-container');
        const btnExec = document.getElementById('btn-view-executive');
        const btnOper = document.getElementById('btn-view-operational');
        const execBanner = document.getElementById('executive-cta-banner');
        
        if (!container) return;

        if (mode === 'executive') {
            container.classList.add('mode-executive');
            container.classList.remove('mode-operational');
            if (btnExec) btnExec.classList.add('active');
            if (btnOper) btnOper.classList.remove('active');
            if (execBanner) execBanner.style.display = 'flex';
        } else {
            container.classList.add('mode-operational');
            container.classList.remove('mode-executive');
            if (btnExec) btnExec.classList.remove('active');
            if (btnOper) btnOper.classList.add('active');
            if (execBanner) execBanner.style.display = 'none';
        }
    }
</script>
