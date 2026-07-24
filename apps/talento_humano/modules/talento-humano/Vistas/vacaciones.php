<?php /* vacaciones.php – Vista: Módulo Vacaciones y Ausencias */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacaciones y Ausencias | Talento Humano – APM</title>
    <meta name="description" content="Portal de solicitudes de vacaciones y control de ausencias del personal de la APM.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
    <style>
        .estado-pendiente { background: rgba(245,158,11,.12); color: #b45309; border: 1px solid rgba(245,158,11,.3); }
        .estado-aprobada  { background: rgba(16,185,129,.12);  color: #059669; border: 1px solid rgba(16,185,129,.25); }
        .estado-rechazada { background: rgba(239,68,68,.12);   color: #dc2626; border: 1px solid rgba(239,68,68,.25); }
        .estado-pill { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:999px; font-size:.78rem; font-weight:600; }
        .tipo-pill { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:8px; font-size:.78rem; background:#f0f7ff; color:var(--ocean-700); border:1px solid var(--line); }
        .saldo-bar { height:8px; background:#e5e7eb; border-radius:999px; overflow:hidden; margin-top:6px; }
        .saldo-fill { height:100%; background:linear-gradient(90deg, var(--ocean-700), var(--teal-500)); border-radius:999px; transition:width .5s; }
        .saldo-card { background:#fff; border:1px solid var(--line); border-radius:var(--radius-md); padding:16px; }
        .saldo-card h4 { margin:0 0 4px; font-size:.9rem; font-weight:700; color:var(--navy-900); }
        .saldo-card small { font-size:.78rem; color:var(--ink-600); }
        .saldo-nums { display:flex; gap:16px; margin-top:8px; }
        .saldo-num { text-align:center; }
        .saldo-num span { display:block; font-size:1.3rem; font-weight:800; color:var(--navy-900); }
        .saldo-num small { font-size:.72rem; color:var(--ink-600); }
        .modal-overlay { position:fixed; inset:0; background:rgba(10,19,30,.55); backdrop-filter:blur(4px); z-index:100; display:none; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#fff; border-radius:var(--radius-lg); padding:28px; max-width:540px; width:90%; box-shadow:var(--shadow-lg); animation:floatIn .3s ease both; }
        .modal-box h3 { margin:0 0 16px; color:var(--navy-900); font-family:var(--font-display); }
        .form-field { margin-bottom:14px; }
        .form-field label { display:block; font-size:.83rem; font-weight:600; color:var(--navy-900); margin-bottom:6px; }
        .form-field input, .form-field select, .form-field textarea {
            width:100%; padding:11px 14px; border:1px solid var(--line); border-radius:10px;
            font-size:.88rem; outline:none; background:#fff; transition:border .2s;
        }
        .form-field input:focus, .form-field select:focus, .form-field textarea:focus {
            border-color:var(--teal-500); box-shadow:0 0 0 3px rgba(18,180,199,.15);
        }
        .btn-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:20px; }
        .metric-card--pendientes { border-left: 4px solid #f59e0b; }
        .metric-card--aprobadas  { border-left: 4px solid #10b981; }
        .tab-nav { display:flex; gap:4px; padding:4px; background:#f1f5f9; border-radius:12px; margin-bottom:20px; }
        .tab-btn { flex:1; padding:10px 16px; border:none; background:transparent; border-radius:10px; cursor:pointer; font-weight:600; font-size:.85rem; color:var(--ink-600); transition:all .2s; }
        .tab-btn.active { background:#fff; color:var(--navy-900); box-shadow:0 2px 8px rgba(0,0,0,.08); }
        .tab-content { display:none; }
        .tab-content.active { display:block; }
        /* Modal Detalles Solicitud */
        .detalle-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; }
        .detalle-item { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px; }
        .detalle-item .d-label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--ink-600); margin-bottom:4px; }
        .detalle-item .d-value { font-size:.9rem; font-weight:600; color:var(--navy-900); }
        .modal-header-det { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid #e2e8f0; }
        .btn-close-modal { background:none; border:none; cursor:pointer; color:#64748b; font-size:1.3rem; padding:4px; border-radius:8px; transition:background .2s; }
        .btn-close-modal:hover { background:#f1f5f9; color:var(--navy-900); }
        .btn-danger { background:rgba(239,68,68,.1); color:#dc2626; border:1px solid rgba(239,68,68,.3); }
        .btn-danger:hover { background:rgba(239,68,68,.18); }
    </style>
</head>
<body>
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()" title="Abrir menú lateral" aria-label="Abrir menú lateral">
    <i class="bi bi-layout-sidebar"></i>
</button>

<!-- Modal nueva solicitud -->
<div class="modal-overlay" id="modalSolicitud">
    <div class="modal-box">
        <h3><i class="bi bi-calendar-plus" style="color:var(--ocean-600)"></i> Nueva Solicitud</h3>
        <form id="formSolicitud" onsubmit="return guardarSolicitud(event)">
            <div class="form-field">
                <label>Tipo de ausencia</label>
                <select required id="sol-tipo">
                    <option value="">— Seleccione —</option>
                    <option value="Vacaciones">Vacaciones</option>
                    <option value="Permiso Médico">Permiso Médico</option>
                    <option value="Permiso por Calamidad">Permiso por Calamidad</option>
                    <option value="Comisión de Servicio">Comisión de Servicio</option>
                    <option value="Licencia sin Remuneración">Licencia sin Remuneración</option>
                </select>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-field">
                    <label>Fecha inicio</label>
                    <input type="date" id="sol-inicio" required>
                </div>
                <div class="form-field">
                    <label>Fecha fin</label>
                    <input type="date" id="sol-fin" required>
                </div>
            </div>
            <div class="form-field">
                <label>Motivo / Justificación</label>
                <textarea rows="3" id="sol-motivo" required placeholder="Describa el motivo de la solicitud..."></textarea>
            </div>
            <div class="btn-actions">
                <button type="button" class="btn btn-ghost" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Enviar solicitud</button>
            </div>
        </form>
    </div>
</div>

<div class="app">
    <?php require_once ROOT . '/shared/menu.php'; ?>

    <section class="content">
        <header class="topbar">
            <div class="topbar-left">
                <div class="brand">
                    <img src="<?= IMG_URL ?>/logoapm.png" alt="Logo APM">
                    <div>
                        <h1>Autoridad Portuaria de Manta</h1>
                        <p>Módulo Talento Humano</p>
                    </div>
                </div>
            </div>
            <div class="topbar-actions">
                <div class="icon-chip"><i class="bi bi-calendar-event"></i><span id="currentDate">--</span></div>
                <div class="user-pill"><span><?= htmlspecialchars($usuarioNombre ?? 'Usuario TH') ?></span><small>APM</small></div>
            </div>
        </header>

        <main class="main">
            <div class="content-shell">

                <!-- HERO -->
                <section class="hero" id="hero-vacaciones">
                    <div>
                        <div class="hero-kicker">Gestión Operativa · Solicitudes</div>
                        <h2>Vacaciones y Ausencias</h2>
                        <p>Portal de solicitudes. Gestiona los días disponibles, registra permisos y aprueba o rechaza solicitudes del personal.</p>
                        <div class="hero-actions">
                            <button class="btn btn-primary" id="btn-nueva-solicitud" onclick="abrirModal()">
                                <i class="bi bi-calendar-plus"></i> Nueva Solicitud
                            </button>
                            <button class="btn btn-ghost" id="btn-exportar-vac">
                                <i class="bi bi-file-earmark-excel"></i> Exportar
                            </button>
                        </div>
                    </div>
                    <div class="metrics" style="grid-template-columns: repeat(2, 1fr);">
                        <div class="metric-card metric-card--pendientes">
                            <div class="metric-label"><i class="bi bi-hourglass-split"></i> Pendientes</div>
                            <div class="metric-value"><?= $total_pendientes ?></div>
                            <div class="metric-foot">Requieren aprobación</div>
                        </div>
                        <div class="metric-card metric-card--aprobadas">
                            <div class="metric-label"><i class="bi bi-calendar-check"></i> Aprobadas</div>
                            <div class="metric-value"><?= $total_aprobadas ?></div>
                            <div class="metric-foot">Este período</div>
                        </div>
                    </div>
                </section>

                <!-- TABS -->
                <div class="card" style="padding:20px;">
                    <div class="tab-nav">
                        <button class="tab-btn active" onclick="showTabVac('tab-solicitudes')" id="tabBtnVac-solicitudes"><i class="bi bi-list-ul"></i> Solicitudes</button>
                        <button class="tab-btn" onclick="showTabVac('tab-saldos')" id="tabBtnVac-saldos"><i class="bi bi-bank2"></i> Saldo vacacional</button>
                    </div>

                    <!-- TAB: Solicitudes -->
                    <div class="tab-content active" id="tab-solicitudes">
                        <div class="table-wrap">
                            <table id="tablaSolicitudes">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Funcionario</th>
                                        <th>Tipo</th>
                                        <th>Período solicitado</th>
                                        <th>Días</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($solicitudes as $s): ?>
                                    <tr class="table-row">
                                        <td style="color:var(--ink-600); font-size:.82rem;">#<?= $s['id'] ?></td>
                                        <td>
                                            <div class="name-meta">
                                                <span><?= htmlspecialchars($s['nombre']) ?></span>
                                                <small><?= htmlspecialchars($s['cargo']) ?> — <?= htmlspecialchars($s['departamento']) ?></small>
                                            </div>
                                        </td>
                                        <td><span class="tipo-pill"><i class="bi bi-tag"></i><?= htmlspecialchars($s['tipo']) ?></span></td>
                                        <td>
                                            <span style="font-size:.82rem; color:var(--navy-900);">
                                                <?= date('d/m/Y', strtotime($s['fecha_inicio'])) ?> → <?= date('d/m/Y', strtotime($s['fecha_fin'])) ?>
                                            </span>
                                        </td>
                                        <td style="text-align:center; font-weight:700;"><?= $s['dias_solicitados'] ?></td>
                                        <td>
                                            <?php
                                            $cls = match($s['estado']) {
                                                'Pendiente' => 'estado-pendiente',
                                                'Aprobada'  => 'estado-aprobada',
                                                'Rechazada' => 'estado-rechazada',
                                                default     => ''
                                            };
                                            $ico = match($s['estado']) {
                                                'Pendiente' => 'bi-hourglass-split',
                                                'Aprobada'  => 'bi-check-circle',
                                                'Rechazada' => 'bi-x-circle',
                                                default     => 'bi-circle'
                                            };
                                            ?>
                                            <span class="estado-pill <?= $cls ?>">
                                                <i class="bi <?= $ico ?>"></i><?= $s['estado'] ?>
                                            </span>
                                        </td>
                                        <td class="action-cell">
                                            <?php if ($s['estado'] === 'Pendiente'): ?>
                                                <button class="btn btn-outline" style="padding:6px 10px; font-size:.8rem;" onclick="verDetallesSolicitud(<?= $s['id'] ?>)" title="Ver detalles">
                                                    <i class="bi bi-eye"></i> Ver
                                                </button>
                                                <button class="btn" style="padding:6px 10px; font-size:.8rem; background:rgba(16,185,129,.1); color:#059669; border:1px solid rgba(16,185,129,.3);" onclick="aprobarDesdeModal(<?= $s['id'] ?>)" title="Aprobar">
                                                    <i class="bi bi-check-lg"></i> Aprobar
                                                </button>
                                                <button class="btn btn-danger" style="padding:6px 10px; font-size:.8rem;" onclick="rechazarSolicitud(<?= $s['id'] ?>)" title="Rechazar">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-ghost" style="padding:6px 10px; font-size:.8rem;" onclick="verDetallesSolicitud(<?= $s['id'] ?>)">
                                                    <i class="bi bi-eye"></i> Ver
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB: Saldos vacacionales -->
                    <div class="tab-content" id="tab-saldos">
                        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px,1fr)); gap:16px; padding-top:4px;">
                        <?php foreach ($saldos as $s): ?>
                            <?php $pct = $s['dias_acumulados'] > 0 ? round(($s['dias_disponibles'] / $s['dias_acumulados']) * 100) : 0; ?>
                            <div class="saldo-card">
                                <h4><?= htmlspecialchars($s['nombre']) ?></h4>
                                <div class="saldo-bar">
                                    <div class="saldo-fill" style="width:<?= $pct ?>%"></div>
                                </div>
                                <small><?= $pct ?>% disponible</small>
                                <div class="saldo-nums">
                                    <div class="saldo-num"><span><?= $s['dias_acumulados'] ?></span><small>Acumulados</small></div>
                                    <div class="saldo-num"><span style="color:#dc2626;"><?= $s['dias_usados'] ?></span><small>Usados</small></div>
                                    <div class="saldo-num"><span style="color:#059669;"><?= $s['dias_disponibles'] ?></span><small>Disponibles</small></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </section>
</div>

<div id="toastContainer" class="toast-container"></div>

<!-- MODAL: VER DETALLES DE SOLICITUD -->
<div class="modal-overlay" id="modalDetalles">
    <div class="modal-box" style="max-width:580px;">
        <div class="modal-header-det">
            <div>
                <h3 style="margin:0 0 2px;"><i class="bi bi-file-text" style="color:var(--ocean-600)"></i> Detalles de la Solicitud <span id="det-id-titulo" style="color:var(--ocean-600);"></span></h3>
                <p style="margin:0; font-size:.83rem; color:var(--ink-600);">Revise la información antes de aprobar o rechazar.</p>
            </div>
            <button class="btn-close-modal" onclick="cerrarDetalles()" title="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Bloque de datos del funcionario -->
        <div style="display:flex; align-items:center; gap:12px; background:#f0f7ff; border-radius:12px; padding:14px 16px; margin-bottom:18px; border:1px solid rgba(16,180,199,.2);">
            <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--navy-900),var(--ocean-600));color:#fff;display:grid;place-items:center;font-weight:800;font-size:1rem;flex-shrink:0;" id="det-avatar">ZH</div>
            <div>
                <div style="font-weight:700; color:var(--navy-900);" id="det-nombre">--</div>
                <small style="color:var(--ink-600);" id="det-cargo-dept">--</small>
            </div>
            <span id="det-estado-badge" class="estado-pill" style="margin-left:auto;"></span>
        </div>

        <div class="detalle-row">
            <div class="detalle-item">
                <div class="d-label"><i class="bi bi-tag"></i> Tipo de Ausencia</div>
                <div class="d-value" id="det-tipo">--</div>
            </div>
            <div class="detalle-item">
                <div class="d-label"><i class="bi bi-calendar-range"></i> Días Solicitados</div>
                <div class="d-value" id="det-dias">--</div>
            </div>
            <div class="detalle-item">
                <div class="d-label"><i class="bi bi-calendar-event"></i> Fecha Inicio</div>
                <div class="d-value" id="det-inicio">--</div>
            </div>
            <div class="detalle-item">
                <div class="d-label"><i class="bi bi-calendar-event"></i> Fecha Fin</div>
                <div class="d-value" id="det-fin">--</div>
            </div>
        </div>

        <div class="detalle-item" style="margin-bottom:16px;">
            <div class="d-label"><i class="bi bi-chat-quote"></i> Motivo / Justificación</div>
            <div class="d-value" id="det-motivo" style="font-weight:400; font-size:.88rem; line-height:1.5;">--</div>
        </div>

        <div id="det-acciones-pendiente" class="btn-actions" style="display:none;">
            <button type="button" class="btn btn-ghost" onclick="cerrarDetalles()">Cerrar</button>
            <button type="button" class="btn" id="det-btn-rechazar"
                    style="background:rgba(239,68,68,.1); color:#dc2626; border:1px solid rgba(239,68,68,.3);"
                    onclick="rechazarDesdeModal()">
                <i class="bi bi-x-circle"></i> Rechazar
            </button>
            <button type="button" class="btn btn-primary" id="det-btn-aprobar" onclick="aprobarDesdeModal()">
                <i class="bi bi-check-circle"></i> Aprobar Solicitud
            </button>
        </div>
        <div id="det-acciones-cerrar" class="btn-actions" style="display:none;">
            <button type="button" class="btn btn-primary" onclick="cerrarDetalles()">Cerrar</button>
        </div>
    </div>
</div>
<script src="<?= BASE_URL ?>/public/js/layout_sidebar.js"></script>
<script src="<?= BASE_URL ?>/public/js/toast.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('currentDate');
    if (el) el.textContent = new Date().toLocaleDateString('es-EC', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    document.getElementById('btn-exportar-vac')?.addEventListener('click', () => exportarTablaCSV('tablaSolicitudes', 'Reporte_Vacaciones_APM.csv'));
});
function showTabVac(tabId) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId)?.classList.add('active');
    document.getElementById('tabBtnVac-' + tabId.replace('tab-',''))?.classList.add('active');
}
function abrirModal() { document.getElementById('modalSolicitud').classList.add('open'); }
function cerrarModal() { document.getElementById('modalSolicitud').classList.remove('open'); }
function guardarSolicitud(e) {
    e.preventDefault();
    showToast('Solicitud enviada correctamente. Pendiente de aprobación.', 'success');
    cerrarModal();
    e.target.reset();
    return false;
}

/* ── Datos simulados de solicitudes (Mock Data) ─────────────────────────── */
const mockSolicitudes = {
    1: {
        id: 1,
        nombre: 'ZAMBRANO DELGADO HECTOR FERNANDO',
        cargo: 'Jefe de Sistemas — Tecnologías de la Información',
        tipo: 'Vacaciones',
        inicio: '2026-06-02',
        fin: '2026-06-13',
        dias: 10,
        estado: 'Pendiente',
        motivo: 'Vacaciones anuales correspondientes al período 2025-2026, según saldo vacacional disponible de 12 días.',
        avatar: 'ZH'
    },
    2: {
        id: 2,
        nombre: 'PEREZ MORALES JUAN CARLOS',
        cargo: 'Economista — Financiero',
        tipo: 'Permiso Médico',
        inicio: '2026-05-28',
        fin: '2026-05-30',
        dias: 3,
        estado: 'Aprobada',
        motivo: 'Atención médica especializada por cirugía programada. Se adjunta certificado médico.',
        avatar: 'PJ'
    },
    3: {
        id: 3,
        nombre: 'TORRES VEGA ANA MARIA',
        cargo: 'Analista de RRHH — Talento Humano',
        tipo: 'Comisión de Servicio',
        inicio: '2026-06-15',
        fin: '2026-06-17',
        dias: 3,
        estado: 'Pendiente',
        motivo: 'Capacitación en LOSEP y normativa IESS — Quito. Convocatoria institucional del Ministerio de Trabajo.',
        avatar: 'TA'
    }
};

let idSolicitudActiva = null;

function verDetallesSolicitud(id) {
    const s = mockSolicitudes[id];
    if (!s) return;
    idSolicitudActiva = id;

    document.getElementById('det-id-titulo').textContent = '#' + s.id;
    document.getElementById('det-avatar').textContent = s.avatar;
    document.getElementById('det-nombre').textContent = s.nombre;
    document.getElementById('det-cargo-dept').textContent = s.cargo;
    document.getElementById('det-tipo').textContent = s.tipo;
    document.getElementById('det-dias').textContent = s.dias + ' días';
    document.getElementById('det-inicio').textContent = new Date(s.inicio + 'T12:00').toLocaleDateString('es-EC', {day:'2-digit', month:'long', year:'numeric'});
    document.getElementById('det-fin').textContent = new Date(s.fin + 'T12:00').toLocaleDateString('es-EC', {day:'2-digit', month:'long', year:'numeric'});
    document.getElementById('det-motivo').textContent = s.motivo;

    const badge = document.getElementById('det-estado-badge');
    const clsMap = { 'Pendiente': 'estado-pendiente', 'Aprobada': 'estado-aprobada', 'Rechazada': 'estado-rechazada' };
    badge.className = 'estado-pill ' + (clsMap[s.estado] || '');
    badge.innerHTML = s.estado;

    document.getElementById('det-acciones-pendiente').style.display = s.estado === 'Pendiente' ? 'flex' : 'none';
    document.getElementById('det-acciones-cerrar').style.display = s.estado !== 'Pendiente' ? 'flex' : 'none';

    document.getElementById('modalDetalles').classList.add('open');
}

function cerrarDetalles() {
    document.getElementById('modalDetalles').classList.remove('open');
    idSolicitudActiva = null;
}

function aprobarDesdeModal(id) {
    const realId = id || idSolicitudActiva;
    if (realId) verDetallesSolicitud(realId);
    showToast(`✅ Solicitud #${realId || idSolicitudActiva} aprobada. Se notificó al funcionario.`, 'success');
    cerrarDetalles();
}

function rechazarDesdeModal() {
    showToast(`❌ Solicitud #${idSolicitudActiva} rechazada.`, 'error');
    cerrarDetalles();
}

function aprobarSolicitud(id) { verDetallesSolicitud(id); }
function rechazarSolicitud(id) { showToast(`Solicitud #${id} rechazada.`, 'error'); }

document.getElementById('modalSolicitud')?.addEventListener('click', e => {
    if (e.target === document.getElementById('modalSolicitud')) cerrarModal();
});
document.getElementById('modalDetalles')?.addEventListener('click', e => {
    if (e.target === document.getElementById('modalDetalles')) cerrarDetalles();
});

/* ── Exportar tabla a CSV ─────────────────────────────────────────────── */
function exportarTablaCSV(tablaID, filename = 'reporte.csv') {
    const csv = [];
    const rows = document.querySelectorAll('table#' + tablaID + ' tr');
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length - 1; j++)
            row.push('"' + cols[j].innerText.trim().replace(/"/g, '""') + '"');
        csv.push(row.join(','));
    }
    const BOM = '\uFEFF';
    const csvFile = new Blob([BOM + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.download = filename;
    link.href = window.URL.createObjectURL(csvFile);
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    showToast('✅ Reporte descargado: ' + filename, 'success');
}
</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
