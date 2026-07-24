<?php /* logs.php – Vista: Auditoría de Actividad del Sistema (Solo Lectura) */

// Datos simulados de logs (modo prototipo)
$logsSimulados = [
    ['log_id'=>12,'fecha_hora'=>'2026-05-31 02:05:44','usuario'=>'hzambrano','modulo'=>'Acción de Personal','accion'=>'EXPORTAR','descripcion_detalle'=>'Generó documento de acción APM-TH-2026-001 para empleado 1308126646','direccion_ip'=>'192.168.1.10'],
    ['log_id'=>11,'fecha_hora'=>'2026-05-30 16:48:21','usuario'=>'jperez','modulo'=>'Directorio de Personal','accion'=>'ACTUALIZAR','descripcion_detalle'=>'Actualizó email institucional del empleado 0923456781','direccion_ip'=>'192.168.1.25'],
    ['log_id'=>10,'fecha_hora'=>'2026-05-30 14:22:07','usuario'=>'admin','modulo'=>'Gestión de Usuarios','accion'=>'CREAR','descripcion_detalle'=>'Creó cuenta de acceso para mpalma con rol Analista RRHH','direccion_ip'=>'192.168.1.5'],
    ['log_id'=>9,'fecha_hora'=>'2026-05-30 11:05:33','usuario'=>'atorres','modulo'=>'Vacaciones y Ausencias','accion'=>'APROBAR','descripcion_detalle'=>'Aprobó solicitud #1 de vacaciones de ZAMBRANO (10 días)','direccion_ip'=>'192.168.1.18'],
    ['log_id'=>8,'fecha_hora'=>'2026-05-30 09:30:15','usuario'=>'hzambrano','modulo'=>'Directorio de Personal','accion'=>'CREAR','descripcion_detalle'=>'Registró nuevo funcionario: PALMA TEJENA MICHAEL (C.I. 1309876543)','direccion_ip'=>'192.168.1.10'],
    ['log_id'=>7,'fecha_hora'=>'2026-05-29 17:50:02','usuario'=>'admin','modulo'=>'Roles y Permisos','accion'=>'ACTUALIZAR','descripcion_detalle'=>'Modificó permisos del rol "Consultor": desactivó puede_eliminar en módulo Directorio','direccion_ip'=>'192.168.1.5'],
    ['log_id'=>6,'fecha_hora'=>'2026-05-29 15:14:58','usuario'=>'jperez','modulo'=>'Reportes','accion'=>'EXPORTAR','descripcion_detalle'=>'Descargó reporte de Nómina Completa en formato CSV','direccion_ip'=>'192.168.1.25'],
    ['log_id'=>5,'fecha_hora'=>'2026-05-29 12:01:44','usuario'=>'atorres','modulo'=>'Vacaciones y Ausencias','accion'=>'CREAR','descripcion_detalle'=>'Registró solicitud de Comisión de Servicio para 2026-06-15 al 2026-06-17','direccion_ip'=>'192.168.1.18'],
    ['log_id'=>4,'fecha_hora'=>'2026-05-28 10:35:27','usuario'=>'admin','modulo'=>'Sistema','accion'=>'LOGIN','descripcion_detalle'=>'Inicio de sesión exitoso','direccion_ip'=>'192.168.1.5'],
    ['log_id'=>3,'fecha_hora'=>'2026-05-27 16:20:09','usuario'=>'hzambrano','modulo'=>'Acción de Personal','accion'=>'ACTUALIZAR','descripcion_detalle'=>'Actualizó sueldo de empleado 1311567890 de $1100.00 a $1200.00','direccion_ip'=>'192.168.1.10'],
];

$accionColor = [
    'CREAR'    => ['bg'=>'rgba(16,185,129,.12)',  'color'=>'#059669', 'icon'=>'bi-plus-circle-fill'],
    'ACTUALIZAR'=>['bg'=>'rgba(16,180,199,.12)',  'color'=>'#0e7490', 'icon'=>'bi-pencil-fill'],
    'ELIMINAR' => ['bg'=>'rgba(239,68,68,.12)',   'color'=>'#dc2626', 'icon'=>'bi-trash3-fill'],
    'EXPORTAR' => ['bg'=>'rgba(99,102,241,.12)',  'color'=>'#4338ca', 'icon'=>'bi-download'],
    'APROBAR'  => ['bg'=>'rgba(16,185,129,.12)',  'color'=>'#059669', 'icon'=>'bi-check-circle-fill'],
    'LOGIN'    => ['bg'=>'rgba(245,158,11,.12)',  'color'=>'#b45309', 'icon'=>'bi-box-arrow-in-right'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de Auditoría | Administración – APM</title>
    <meta name="description" content="Registro de actividad del sistema de Talento Humano — Autoridad Portuaria de Manta. Solo lectura.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/fonts.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
    <style>
        .accion-badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 3px 10px; border-radius: 8px; font-size: .73rem; font-weight: 700;
        }
        .log-timestamp { font-size: .78rem; color: var(--ink-600); font-family: monospace; }
        .readonly-banner {
            background: linear-gradient(135deg, rgba(15,23,42,.04), rgba(15,23,42,.02));
            border: 1px solid rgba(15,23,42,.12); border-radius: 12px; padding: 12px 16px;
            display: flex; align-items: center; gap: 10px; margin-bottom: 20px;
            font-size: .83rem; color: var(--navy-900);
        }
        .log-user { font-family: monospace; font-size: .83rem; font-weight: 700; color: var(--ocean-700); background: #f0f9ff; padding: 2px 7px; border-radius: 6px; }
        .ip-pill { font-family: monospace; font-size: .75rem; color: var(--ink-600); background: #f1f5f9; padding: 2px 8px; border-radius: 6px; }
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
                        <p>Administración y Seguridad</p>
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
                        <div class="hero-kicker">Administración · Seguridad y Control</div>
                        <h2>Logs de Auditoría</h2>
                        <p>Registro cronológico de todas las acciones realizadas en el sistema. Solo lectura — ningún evento puede ser eliminado ni modificado para garantizar la integridad del rastro de auditoría.</p>
                    </div>
                    <div class="metrics" style="grid-template-columns:repeat(2,1fr);">
                        <div class="metric-card" style="border-left:4px solid var(--ocean-700);">
                            <div class="metric-label"><i class="bi bi-list-check"></i> Eventos registrados</div>
                            <div class="metric-value"><?= count($logsSimulados) ?></div>
                            <div class="metric-foot">En el período actual</div>
                        </div>
                        <div class="metric-card" style="border-left:4px solid #dc2626;">
                            <div class="metric-label"><i class="bi bi-shield-exclamation"></i> Eventos críticos</div>
                            <div class="metric-value">2</div>
                            <div class="metric-foot">Requieren revisión</div>
                        </div>
                    </div>
                </section>

                <!-- BANNER SOLO LECTURA -->
                <div class="readonly-banner">
                    <i class="bi bi-lock-fill" style="font-size:1.2rem; color:#dc2626; flex-shrink:0;"></i>
                    <span>
                        <strong>Módulo de solo lectura.</strong>
                        Por seguridad, los logs de auditoría no pueden ser editados ni eliminados.
                        Este registro cumple con los requerimientos de la <strong>Contraloría General del Estado</strong>.
                    </span>
                </div>

                <!-- TABLA DE LOGS -->
                <section class="card table-card" id="seccion-logs">
                    <div class="card-header">
                        <div>
                            <h3><i class="bi bi-journal-text"></i> Registro de Actividad del Sistema</h3>
                            <p>Ordenado cronológicamente — los eventos más recientes aparecen primero.</p>
                        </div>
                        <span class="chip"><i class="bi bi-database-lock"></i> <?= count($logsSimulados) ?> eventos</span>
                    </div>
                    <div class="toolbar">
                        <div class="input search-input">
                            <i class="bi bi-search"></i>
                            <input type="text" id="logSearch" oninput="filtrarLogs()" placeholder="Buscar por usuario, módulo, acción o IP...">
                        </div>
                        <button class="btn btn-ghost" onclick="exportarTablaCSV('tablaLogs', 'Auditoria_APM_<?= date('Y-m-d') ?>.csv')">
                            <i class="bi bi-download"></i> Exportar
                        </button>
                    </div>
                    <div class="table-wrap">
                        <table id="tablaLogs">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Fecha y Hora</th>
                                    <th>Usuario</th>
                                    <th>Módulo</th>
                                    <th>Acción</th>
                                    <th>Descripción del Evento</th>
                                    <th>IP de Origen</th>
                                </tr>
                            </thead>
                            <tbody id="logTableBody">
                            <?php foreach ($logsSimulados as $log):
                                $a = $accionColor[$log['accion']] ?? ['bg'=>'#f1f5f9','color'=>'#64748b','icon'=>'bi-dot'];
                            ?>
                                <tr class="table-row"
                                    data-search="<?= strtolower($log['usuario'].' '.$log['modulo'].' '.$log['accion'].' '.$log['descripcion_detalle'].' '.$log['direccion_ip']) ?>">
                                    <td style="color:var(--ink-600); font-size:.8rem; font-family:monospace;"><?= $log['log_id'] ?></td>
                                    <td>
                                        <span class="log-timestamp">
                                            <?= date('d/m/Y', strtotime($log['fecha_hora'])) ?><br>
                                            <strong><?= date('H:i:s', strtotime($log['fecha_hora'])) ?></strong>
                                        </span>
                                    </td>
                                    <td><span class="log-user"><?= htmlspecialchars($log['usuario']) ?></span></td>
                                    <td style="font-size:.82rem; color:var(--navy-900);"><?= htmlspecialchars($log['modulo']) ?></td>
                                    <td>
                                        <span class="accion-badge" style="background:<?= $a['bg'] ?>; color:<?= $a['color'] ?>;">
                                            <i class="bi <?= $a['icon'] ?>"></i>
                                            <?= htmlspecialchars($log['accion']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:.8rem; color:var(--ink-600); max-width:300px;">
                                        <?= htmlspecialchars($log['descripcion_detalle']) ?>
                                    </td>
                                    <td><span class="ip-pill"><?= htmlspecialchars($log['direccion_ip']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
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
    const q = document.getElementById('logSearch').value.toLowerCase();
    document.querySelectorAll('#logTableBody tr').forEach(tr => {
        tr.style.display = tr.dataset.search?.includes(q) ? '' : 'none';
    });
}

function exportarTablaCSV(tablaID, filename = 'logs.csv') {
    const csv = [];
    const rows = document.querySelectorAll('table#' + tablaID + ' tr');
    for (let i = 0; i < rows.length; i++) {
        if (rows[i].style.display === 'none') continue;
        const row = [], cols = rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length; j++)
            row.push('"' + cols[j].innerText.trim().replace(/"/g, '""') + '"');
        csv.push(row.join(','));
    }
    const BOM = '\uFEFF';
    const csvFile = new Blob([BOM + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.download = filename;
    link.href = URL.createObjectURL(csvFile);
    link.click();
    showToast('✅ Log de auditoría exportado: ' + filename, 'success');
}
</script>
</body>
</html>
