<?php /* capacitacion.php – Vista: Módulo Capacitación y Desarrollo */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capacitación y Desarrollo | Talento Humano – APM</title>
    <meta name="description" content="Registro de cursos, talleres y certificaciones del personal de la Autoridad Portuaria de Manta.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
    <style>
        .estado-completado { background:rgba(16,185,129,.12); color:#059669; border:1px solid rgba(16,185,129,.25); }
        .estado-en-curso   { background:rgba(99,102,241,.12);  color:#4338ca; border:1px solid rgba(99,102,241,.25); }
        .estado-planificado{ background:rgba(245,158,11,.12);  color:#b45309; border:1px solid rgba(245,158,11,.3); }
        .estado-pill { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:999px; font-size:.78rem; font-weight:600; }
        .cat-pill { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:8px; font-size:.75rem; font-weight:600; background:#f0f7ff; color:var(--ocean-700); border:1px solid var(--line); }
        .tipo-taller    { background:rgba(168,85,247,.1); color:#7c3aed; border-color:rgba(168,85,247,.2); }
        .tipo-curso     { background:rgba(16,180,199,.1); color:var(--ocean-700); border-color:rgba(16,180,199,.2); }
        .tipo-cert      { background:rgba(245,158,11,.1); color:#b45309; border-color:rgba(245,158,11,.2); }
        .cap-card { background:#fff; border:1px solid var(--line); border-radius:var(--radius-md); overflow:hidden; transition:transform .2s, box-shadow .2s; }
        .cap-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-md); }
        .cap-head { padding:14px 18px; background:linear-gradient(135deg,#f8fbff,#f1f7ff); border-bottom:1px solid var(--line); display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
        .cap-body { padding:14px 18px; }
        .cap-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; }
        .cap-meta { display:flex; flex-wrap:wrap; gap:10px; margin-top:10px; }
        .cap-meta-item { display:inline-flex; align-items:center; gap:5px; font-size:.78rem; color:var(--ink-600); }
        .part-list { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
        .part-tag { background:#f0f4ff; border:1px solid #c7d2fe; color:#4338ca; padding:2px 8px; border-radius:6px; font-size:.72rem; font-weight:600; }
        .modal-overlay { position:fixed; inset:0; background:rgba(10,19,30,.55); backdrop-filter:blur(4px); z-index:100; display:none; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#fff; border-radius:var(--radius-lg); padding:28px; max-width:560px; width:90%; max-height:85vh; overflow-y:auto; box-shadow:var(--shadow-lg); animation:floatIn .3s ease both; }
        .form-field { margin-bottom:14px; }
        .form-field label { display:block; font-size:.83rem; font-weight:600; color:var(--navy-900); margin-bottom:6px; }
        .form-field input, .form-field select, .form-field textarea {
            width:100%; padding:11px 14px; border:1px solid var(--line); border-radius:10px; font-size:.88rem; outline:none; background:#fff; transition:border .2s;
        }
        .form-field input:focus, .form-field select:focus, .form-field textarea:focus { border-color:var(--teal-500); box-shadow:0 0 0 3px rgba(18,180,199,.15); }
        .metric-card--completados { border-left:4px solid #10b981; }
        .metric-card--en-curso    { border-left:4px solid #6366f1; }
        .metric-card--horas       { border-left:4px solid var(--teal-500); }
        .metric-card--certificados{ border-left:4px solid #f59e0b; }
    </style>
</head>
<body>
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()" title="Abrir menú lateral" aria-label="Abrir menú lateral">
    <i class="bi bi-layout-sidebar"></i>
</button>

<!-- Modal registrar capacitación -->
<div class="modal-overlay" id="modalCap">
    <div class="modal-box">
        <h3 style="margin:0 0 4px; color:var(--navy-900);"><i class="bi bi-mortarboard" style="color:var(--ocean-600)"></i> Registrar Capacitación</h3>
        <p style="margin:0 0 20px; font-size:.85rem; color:var(--ink-600);">Ingrese los datos del curso, taller o certificación.</p>
        <form id="formCap" onsubmit="return guardarCap(event)">
            <div class="form-field">
                <label>Título del evento</label>
                <input type="text" id="cap-titulo" placeholder="Ej: Seguridad Portuaria ISPS Code" required>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-field">
                    <label>Tipo</label>
                    <select id="cap-tipo">
                        <option value="Curso">Curso</option>
                        <option value="Taller">Taller</option>
                        <option value="Certificación">Certificación</option>
                        <option value="Seminario">Seminario</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Modalidad</label>
                    <select id="cap-modalidad">
                        <option>Presencial</option>
                        <option>Virtual</option>
                        <option>Híbrido</option>
                    </select>
                </div>
            </div>
            <div class="form-field">
                <label>Institución organizadora</label>
                <input type="text" id="cap-institucion" placeholder="Ej: SECAP, IAEN, Ministerio del Trabajo" required>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px;">
                <div class="form-field">
                    <label>Fecha inicio</label>
                    <input type="date" id="cap-inicio" required>
                </div>
                <div class="form-field">
                    <label>Fecha fin</label>
                    <input type="date" id="cap-fin" required>
                </div>
                <div class="form-field">
                    <label>Horas</label>
                    <input type="number" id="cap-horas" min="1" placeholder="40" required>
                </div>
            </div>
            <div class="form-field">
                <label>Categoría temática</label>
                <select id="cap-categoria">
                    <option>Gestión Pública</option>
                    <option>Seguridad</option>
                    <option>Tecnología</option>
                    <option>Liderazgo</option>
                    <option>Finanzas</option>
                    <option>Recursos Humanos</option>
                    <option>Operaciones</option>
                </select>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:16px;">
                <button type="button" class="btn btn-ghost" onclick="cerrarCap()">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Registrar</button>
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
                <section class="hero" id="hero-capacitacion">
                    <div>
                        <div class="hero-kicker">Gestión Operativa · Desarrollo</div>
                        <h2>Capacitación y Desarrollo</h2>
                        <p>Registro institucional de cursos, talleres y certificaciones otorgados al personal. Información clave para auditorías y planificación de carrera.</p>
                        <div class="hero-actions">
                            <button class="btn btn-primary" id="btn-nueva-cap" onclick="abrirCap()">
                                <i class="bi bi-plus-circle"></i> Registrar Capacitación
                            </button>
                            <button class="btn btn-ghost" id="btn-exportar-cap">
                                <i class="bi bi-file-earmark-excel"></i> Exportar para auditoría
                            </button>
                        </div>
                    </div>
                    <div class="metrics" style="grid-template-columns:repeat(2,1fr);">
                        <div class="metric-card metric-card--completados">
                            <div class="metric-label"><i class="bi bi-patch-check"></i> Completados</div>
                            <div class="metric-value"><?= $resumen['completados'] ?></div>
                            <div class="metric-foot">Eventos cerrados</div>
                        </div>
                        <div class="metric-card metric-card--en-curso">
                            <div class="metric-label"><i class="bi bi-play-circle"></i> En curso</div>
                            <div class="metric-value"><?= $resumen['en_curso'] ?></div>
                            <div class="metric-foot">Activos ahora</div>
                        </div>
                        <div class="metric-card metric-card--horas">
                            <div class="metric-label"><i class="bi bi-clock"></i> Horas impartidas</div>
                            <div class="metric-value"><?= $resumen['total_horas'] ?>h</div>
                            <div class="metric-foot">Total acumulado</div>
                        </div>
                        <div class="metric-card metric-card--certificados">
                            <div class="metric-label"><i class="bi bi-award"></i> Certificados</div>
                            <div class="metric-value"><?= $resumen['certificados'] ?></div>
                            <div class="metric-foot">Emitidos al personal</div>
                        </div>
                    </div>
                </section>

                <!-- FILTROS -->
                <div class="card" style="padding:16px 20px; display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
                    <span style="font-weight:600; font-size:.85rem; color:var(--navy-900);"><i class="bi bi-funnel"></i> Filtrar por:</span>
                    <button class="btn btn-primary" onclick="filtrarCap('')" id="fBtn-all" style="padding:7px 14px; font-size:.8rem;">Todos</button>
                    <button class="btn btn-ghost" onclick="filtrarCap('Completado')" id="fBtn-comp" style="padding:7px 14px; font-size:.8rem;">Completados</button>
                    <button class="btn btn-ghost" onclick="filtrarCap('En Curso')" id="fBtn-enc" style="padding:7px 14px; font-size:.8rem;">En curso</button>
                    <button class="btn btn-ghost" onclick="filtrarCap('Planificado')" id="fBtn-plan" style="padding:7px 14px; font-size:.8rem;">Planificados</button>
                </div>

                <!-- CAPACITACIONES GRID -->
                <div class="cap-grid" id="capGrid">
                <?php foreach ($capacitaciones as $cap): ?>
                    <?php
                    $estadoCls = match($cap['estado']) {
                        'Completado'  => 'estado-completado',
                        'En Curso'    => 'estado-en-curso',
                        'Planificado' => 'estado-planificado',
                        default       => ''
                    };
                    $tipoCls = match($cap['tipo']) {
                        'Taller'       => 'tipo-taller',
                        'Curso'        => 'tipo-curso',
                        'Certificación'=> 'tipo-cert',
                        default        => ''
                    };
                    $iconoEstado = match($cap['estado']) {
                        'Completado'  => 'bi-check-circle-fill',
                        'En Curso'    => 'bi-play-circle-fill',
                        'Planificado' => 'bi-calendar-event-fill',
                        default       => 'bi-circle'
                    };
                    ?>
                    <div class="cap-card" data-estado="<?= $cap['estado'] ?>">
                        <div class="cap-head">
                            <div>
                                <h4 style="margin:0 0 6px; font-size:.95rem; color:var(--navy-900);"><?= htmlspecialchars($cap['titulo']) ?></h4>
                                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                    <span class="cat-pill <?= $tipoCls ?>"><i class="bi bi-bookmark"></i><?= $cap['tipo'] ?></span>
                                    <span class="cat-pill"><i class="bi bi-tag"></i><?= $cap['categoria'] ?></span>
                                </div>
                            </div>
                            <span class="estado-pill <?= $estadoCls ?>" style="flex-shrink:0;">
                                <i class="bi <?= $iconoEstado ?>"></i><?= $cap['estado'] ?>
                            </span>
                        </div>
                        <div class="cap-body">
                            <div class="cap-meta">
                                <span class="cap-meta-item"><i class="bi bi-building"></i><?= htmlspecialchars($cap['institucion']) ?></span>
                                <span class="cap-meta-item"><i class="bi bi-laptop"></i><?= $cap['modalidad'] ?></span>
                                <span class="cap-meta-item"><i class="bi bi-clock"></i><?= $cap['duracion_h'] ?>h</span>
                                <span class="cap-meta-item"><i class="bi bi-calendar3"></i><?= date('d/m/Y', strtotime($cap['fecha_inicio'])) ?> → <?= date('d/m/Y', strtotime($cap['fecha_fin'])) ?></span>
                            </div>
                            <?php if (!empty($cap['participantes'])): ?>
                            <div style="margin-top:10px;">
                                <small style="font-size:.75rem; color:var(--ink-600); font-weight:600; text-transform:uppercase; letter-spacing:.1em;">Participantes:</small>
                                <div class="part-list">
                                    <?php foreach ($cap['participantes'] as $p): ?>
                                    <span class="part-tag"><?= htmlspecialchars(implode(' ', array_slice(explode(' ', $p), 0, 2))) ?></span>
                                    <?php endforeach; ?>
                                    <?php if ($cap['certificados'] > 0): ?>
                                    <span style="background:#fef3c7; border:1px solid #fde68a; color:#92400e; padding:2px 8px; border-radius:6px; font-size:.72rem; font-weight:700;">
                                        <i class="bi bi-award"></i> <?= $cap['certificados'] ?> certificado<?= $cap['certificados'] > 1 ? 's' : '' ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php else: ?>
                            <div style="margin-top:10px; padding:8px; background:#f9fafb; border-radius:8px; text-align:center; font-size:.8rem; color:var(--ink-600);">
                                <i class="bi bi-person-plus"></i> Sin participantes asignados aún
                            </div>
                            <?php endif; ?>
                            <div style="display:flex; gap:8px; margin-top:14px;">
                                <button class="btn btn-outline" style="flex:1; padding:7px; font-size:.8rem;" onclick="showToast('Viendo detalles de: <?= addslashes(htmlspecialchars($cap['titulo'])) ?>', 'info')">
                                    <i class="bi bi-eye"></i> Ver detalles
                                </button>
                                <?php if ($cap['estado'] === 'Planificado'): ?>
                                <button class="btn btn-primary" style="flex:1; padding:7px; font-size:.8rem;" onclick="showToast('Asignando participantes...', 'info')">
                                    <i class="bi bi-person-plus"></i> Asignar
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>

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
    document.getElementById('btn-exportar-cap')?.addEventListener('click', () => showToast('Generando reporte de capacitaciones para auditoría...', 'info'));
});
function abrirCap() { document.getElementById('modalCap').classList.add('open'); }
function cerrarCap() { document.getElementById('modalCap').classList.remove('open'); }
function guardarCap(e) {
    e.preventDefault();
    showToast('Capacitación registrada exitosamente.', 'success');
    cerrarCap();
    e.target.reset();
    return false;
}
function filtrarCap(estado) {
    document.querySelectorAll('.cap-card').forEach(card => {
        const show = !estado || card.dataset.estado === estado;
        card.style.display = show ? '' : 'none';
    });
    document.querySelectorAll('[id^="fBtn-"]').forEach(b => b.classList.replace('btn-primary', 'btn-ghost'));
    const active = !estado ? document.getElementById('fBtn-all') : document.getElementById('fBtn-' + (estado === 'Completado' ? 'comp' : estado === 'En Curso' ? 'enc' : 'plan'));
    active?.classList.replace('btn-ghost', 'btn-primary');
}
document.getElementById('modalCap')?.addEventListener('click', e => {
    if (e.target === document.getElementById('modalCap')) cerrarCap();
});
</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
