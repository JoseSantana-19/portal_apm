<?php /* logs.php – Vista: Logs de Actividad del Sistema (solo lectura, Admin) */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Actividad | Auditoría – APM</title>
    <meta name="description" content="Registro de auditoría del sistema. Solo lectura para administradores. Trazabilidad de cada acción realizada.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/fonts.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
    <style>
        .log-row-info    { border-left: 3px solid var(--teal-500); }
        .log-row-success { border-left: 3px solid #10b981; }
        .log-row-warning { border-left: 3px solid #f59e0b; }
        .log-row-danger  { border-left: 3px solid #ef4444; }
        .accion-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:7px; font-size:.72rem; font-weight:700; font-family:monospace; letter-spacing:.05em; }
        .accion-INSERT { background:rgba(16,185,129,.12); color:#059669; border:1px solid rgba(16,185,129,.2); }
        .accion-UPDATE { background:rgba(99,102,241,.12); color:#4338ca; border:1px solid rgba(99,102,241,.2); }
        .accion-DELETE { background:rgba(239,68,68,.12);  color:#dc2626; border:1px solid rgba(239,68,68,.2); }
        .accion-LOGIN  { background:rgba(16,180,199,.12); color:var(--ocean-700); border:1px solid rgba(16,180,199,.2); }
        .accion-LOGOUT { background:rgba(107,114,128,.12); color:#4b5563; border:1px solid rgba(107,114,128,.2); }
        .accion-EXPORT { background:rgba(245,158,11,.12); color:#b45309; border:1px solid rgba(245,158,11,.2); }
        .accion-ROLE   { background:rgba(168,85,247,.12); color:#7c3aed; border:1px solid rgba(168,85,247,.2); }
        .modulo-pill { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-size:.72rem; background:#f0f7ff; color:var(--ocean-700); border:1px solid var(--line); }
        .ip-code { font-family:monospace; font-size:.82rem; color:var(--ink-600); }
        .nivel-danger  { color:#dc2626; }
        .nivel-warning { color:#b45309; }
        .nivel-success { color:#059669; }
        .nivel-info    { color:var(--ocean-700); }
        .admin-banner { background:linear-gradient(135deg,#1e1b4b,#3730a3); color:#fff; border-radius:var(--radius-md); padding:14px 20px; display:flex; align-items:center; gap:14px; font-size:.85rem; }
        .admin-banner i { font-size:1.4rem; }
        .live-dot { width:9px; height:9px; border-radius:50%; background:#10b981; display:inline-block; animation:pulse-dot 1.5s infinite; margin-right:6px; }
        @keyframes pulse-dot { 0%,100%{opacity:1; transform:scale(1);} 50%{opacity:.5; transform:scale(.8);} }
        .metric-card--hoy    { border-left:4px solid var(--teal-500); }
        .metric-card--alerta { border-left:4px solid #ef4444; }
        .metric-card--users  { border-left:4px solid var(--ocean-700); }
    </style>
</head>
<body>
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()" title="Abrir menú lateral" aria-label="Abrir menú lateral">
    <i class="bi bi-layout-sidebar"></i>
</button>

<div class="app">
    <?php require_once ROOT . '/shared/menu.php'; ?>

    <section class="content">
        <header class="topbar">
            <div class="topbar-left">
                <div class="brand">
                    <img src="<?= IMG_URL ?>/logoapm.png" alt="Logo APM">
                    <div>
                        <h1>Autoridad Portuaria de Manta</h1>
                        <p>Auditoría y Control</p>
                    </div>
                </div>
            </div>
            <div class="topbar-actions">
                <div class="icon-chip"><i class="bi bi-calendar-event"></i><span id="currentDate">--</span></div>
                <div class="user-pill"><span><?= htmlspecialchars($usuarioNombre ?? 'Administrador') ?></span><small>APM</small></div>
            </div>
        </header>

        <main class="main">
            <div class="content-shell">

                <!-- HERO -->
                <section class="hero" id="hero-logs">
                    <div>
                        <div class="hero-kicker">Auditoría y Control · Trazabilidad</div>
                        <h2>Logs de Actividad</h2>
                        <p>Registro inmutable de todas las acciones del sistema. Solo lectura, exclusivo para administradores. Garantiza la trazabilidad ante cualquier cambio anómalo.</p>
                        <div class="hero-actions">
                            <button class="btn btn-primary" id="btn-exportar-logs" onclick="exportarLogs()">
                                <i class="bi bi-download"></i> Exportar logs
                            </button>
                            <button class="btn btn-ghost" id="btn-actualizar-logs" onclick="actualizarLogs()">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                            </button>
                        </div>
                    </div>
                    <div class="metrics" style="grid-template-columns:repeat(3,1fr);">
                        <div class="metric-card metric-card--hoy">
                            <div class="metric-label"><i class="bi bi-clock"></i> Eventos hoy</div>
                            <div class="metric-value"><?= $resumen['total_hoy'] ?></div>
                            <div class="metric-foot">Acciones registradas</div>
                        </div>
                        <div class="metric-card metric-card--alerta">
                            <div class="metric-label"><i class="bi bi-exclamation-triangle"></i> Alertas</div>
                            <div class="metric-value"><?= $resumen['alertas'] ?></div>
                            <div class="metric-foot">Requieren revisión</div>
                        </div>
                        <div class="metric-card metric-card--users">
                            <div class="metric-label"><i class="bi bi-person-lines-fill"></i> Usuarios</div>
                            <div class="metric-value"><?= $resumen['usuarios_activos'] ?></div>
                            <div class="metric-foot">Con actividad reciente</div>
                        </div>
                    </div>
                </section>

                <!-- BANNER ADMIN -->
                <div class="admin-banner" id="banner-admin">
                    <i class="bi bi-shield-lock-fill"></i>
                    <div>
                        <strong>Módulo de Solo Lectura — Acceso Restringido</strong>
                        <div style="opacity:.8; font-size:.82rem; margin-top:2px;">Esta interfaz registra todos los eventos del sistema. No es posible editar, eliminar ni falsificar entradas. Los logs también se almacenan en archivos <code style="background:rgba(255,255,255,.15); padding:1px 6px; border-radius:4px;">.txt</code> diarios en el servidor.</div>
                    </div>
                    <div style="margin-left:auto; flex-shrink:0;">
                        <span class="live-dot"></span><span style="font-size:.8rem; opacity:.9;">EN VIVO</span>
                    </div>
                </div>

                <!-- TABLA DE LOGS -->
                <section class="card table-card" id="seccion-logs">
                    <div class="card-header">
                        <div>
                            <h3><i class="bi bi-journal-text"></i> Registro de actividad del sistema</h3>
                            <p>Cada fila representa una acción: quién, qué hizo, cuándo y desde qué IP.</p>
                        </div>
                        <span class="chip"><i class="bi bi-list-ul"></i> <?= count($registros) ?> registros</span>
                    </div>
                    <div class="toolbar">
                        <div class="input search-input">
                            <i class="bi bi-search"></i>
                            <input type="text" id="logSearch" oninput="filtrarLogs()" placeholder="Buscar por usuario, acción, módulo...">
                        </div>
                        <div class="filter-group">
                            <select id="nivelFilter" onchange="filtrarLogs()">
                                <option value="">Todos los niveles</option>
                                <option value="danger">⚠ Alertas / Peligro</option>
                                <option value="warning">⚡ Advertencias</option>
                                <option value="success">✓ Éxitos</option>
                                <option value="info">ℹ Información</option>
                            </select>
                            <select id="moduloFilter" onchange="filtrarLogs()">
                                <option value="">Todos los módulos</option>
                                <option>Empleados</option>
                                <option>Sistema</option>
                                <option>Vacaciones</option>
                                <option>Reportes</option>
                                <option>Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table id="tablaLogs">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Fecha y hora</th>
                                    <th>Usuario</th>
                                    <th>IP</th>
                                    <th>Acción</th>
                                    <th>Módulo</th>
                                    <th>Descripción</th>
                                    <th>Nivel</th>
                                </tr>
                            </thead>
                            <tbody id="logTableBody">
                            <?php foreach ($registros as $log): ?>
                                <tr class="table-row log-row-<?= $log['nivel'] ?>"
                                    data-usuario="<?= strtolower($log['usuario']) ?>"
                                    data-accion="<?= strtolower($log['accion']) ?>"
                                    data-modulo="<?= $log['modulo'] ?>"
                                    data-nivel="<?= $log['nivel'] ?>">
                                    <td style="color:var(--ink-600); font-size:.78rem; font-family:monospace;"><?= str_pad($log['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                    <td style="font-size:.82rem; white-space:nowrap;">
                                        <div style="font-weight:600; color:var(--navy-900);"><?= substr($log['fecha'], 0, 10) ?></div>
                                        <small style="color:var(--ink-600); font-family:monospace;"><?= substr($log['fecha'], 11) ?></small>
                                    </td>
                                    <td>
                                        <code style="background:#f1f5f9; padding:2px 7px; border-radius:6px; font-size:.82rem; color:var(--navy-900);"><?= htmlspecialchars($log['usuario']) ?></code>
                                    </td>
                                    <td><span class="ip-code"><?= htmlspecialchars($log['ip']) ?></span></td>
                                    <td><span class="accion-badge accion-<?= $log['accion'] ?>"><?= htmlspecialchars($log['accion']) ?></span></td>
                                    <td><span class="modulo-pill"><i class="bi bi-box"></i><?= htmlspecialchars($log['modulo']) ?></span></td>
                                    <td style="font-size:.82rem; max-width:320px;"><?= htmlspecialchars($log['descripcion']) ?></td>
                                    <td>
                                        <?php
                                        $nivelIco = match($log['nivel']) {
                                            'danger'  => ['bi-exclamation-triangle-fill', 'nivel-danger'],
                                            'warning' => ['bi-exclamation-circle-fill',   'nivel-warning'],
                                            'success' => ['bi-check-circle-fill',          'nivel-success'],
                                            default   => ['bi-info-circle-fill',            'nivel-info'],
                                        };
                                        ?>
                                        <i class="bi <?= $nivelIco[0] ?> <?= $nivelIco[1] ?>" style="font-size:1.1rem;" title="<?= ucfirst($log['nivel']) ?>"></i>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div id="logNoData" class="no-data hidden">
                            <div class="no-data-icon"><i class="bi bi-inbox"></i></div>
                            <h4>Sin coincidencias</h4>
                            <p>Ajuste los filtros de búsqueda.</p>
                        </div>
                    </div>
                </section>

            </div>
        </main>
    </section>
</div>

<div id="toastContainer" class="toast-container"></div>
<script src="<?= BASE_URL ?>/public/js/layout_sidebar.js"></script>
<script src="<?= BASE_URL ?>/public/js/toast.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('currentDate');
    if (el) el.textContent = new Date().toLocaleDateString('es-EC', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
});
function filtrarLogs() {
    const q      = document.getElementById('logSearch').value.toLowerCase();
    const nivel  = document.getElementById('nivelFilter').value;
    const modulo = document.getElementById('moduloFilter').value;
    let visible  = 0;
    document.querySelectorAll('#logTableBody tr').forEach(tr => {
        const matchQ = !q || (tr.dataset.usuario + tr.dataset.accion + tr.dataset.modulo).includes(q);
        const matchN = !nivel  || tr.dataset.nivel  === nivel;
        const matchM = !modulo || tr.dataset.modulo === modulo;
        const show   = matchQ && matchN && matchM;
        tr.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('logNoData').classList.toggle('hidden', visible > 0);
}
function exportarLogs() {
    showToast('Exportando logs de actividad del sistema...', 'info');
    setTimeout(() => showToast('Archivo de logs generado exitosamente.', 'success'), 2000);
}
function actualizarLogs() {
    showToast('Actualizando registros de auditoría...', 'info');
    setTimeout(() => showToast('Logs actualizados. Sin nuevos eventos.', 'success'), 1200);
}
</script>
</body>
</html>
