<?php /* desempeno.php – Vista: Módulo Evaluación y Desempeño */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluación y Desempeño | Talento Humano – APM</title>
    <meta name="description" content="Formularios digitales de evaluación del rendimiento del personal de la APM.">
    <?php require ROOT . '/shared/head_assets.php'; ?>
    <style>
        .nivel-excelente { background:rgba(16,185,129,.12); color:#059669; border:1px solid rgba(16,185,129,.25); }
        .nivel-satisfactorio { background:rgba(99,102,241,.12); color:#4338ca; border:1px solid rgba(99,102,241,.25); }
        .nivel-pendiente { background:rgba(245,158,11,.12); color:#b45309; border:1px solid rgba(245,158,11,.3); }
        .nivel-proceso { background:rgba(16,180,199,.12); color:var(--ocean-700); border:1px solid rgba(16,180,199,.3); }
        .nivel-pill { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:999px; font-size:.78rem; font-weight:600; }
        .score-bar { display:flex; align-items:center; gap:10px; }
        .score-track { flex:1; height:8px; background:#e5e7eb; border-radius:999px; overflow:hidden; }
        .score-fill { height:100%; border-radius:999px; transition:width .6s; }
        .score-fill.excelente { background:linear-gradient(90deg, #10b981, #34d399); }
        .score-fill.satisfactorio { background:linear-gradient(90deg, #6366f1, #818cf8); }
        .score-fill.bajo { background:linear-gradient(90deg, #ef4444, #f87171); }
        .score-val { font-weight:800; font-size:.95rem; color:var(--navy-900); min-width:40px; text-align:right; }
        .objetivos-prog { display:flex; align-items:center; gap:6px; font-size:.82rem; }
        .obj-dot { width:8px; height:8px; border-radius:50%; background:var(--teal-500); display:inline-block; }
        .eval-card { background:#fff; border:1px solid var(--line); border-radius:var(--radius-md); overflow:hidden; transition:transform .2s, box-shadow .2s; }
        .eval-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-md); }
        .eval-card-head { padding:16px 20px; background:linear-gradient(135deg, #f8fbff, #f1f7ff); border-bottom:1px solid var(--line); display:flex; align-items:center; justify-content:space-between; }
        .eval-card-body { padding:16px 20px; }
        .eval-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px,1fr)); gap:16px; }
        .modal-overlay { position:fixed; inset:0; background:rgba(10,19,30,.55); backdrop-filter:blur(4px); z-index:100; display:none; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#fff; border-radius:var(--radius-lg); padding:28px; max-width:620px; width:90%; max-height:85vh; overflow-y:auto; box-shadow:var(--shadow-lg); animation:floatIn .3s ease both; }
        .form-field { margin-bottom:14px; }
        .form-field label { display:block; font-size:.83rem; font-weight:600; color:var(--navy-900); margin-bottom:6px; }
        .form-field input, .form-field select, .form-field textarea {
            width:100%; padding:11px 14px; border:1px solid var(--line); border-radius:10px; font-size:.88rem; outline:none; background:#fff; transition:border .2s;
        }
        .form-field input:focus, .form-field select:focus, .form-field textarea:focus { border-color:var(--teal-500); box-shadow:0 0 0 3px rgba(18,180,199,.15); }
        .criteria-row { display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid #f1f5f9; }
        .criteria-label { flex:1; font-size:.87rem; color:var(--navy-900); font-weight:500; }
        .criteria-stars { display:flex; gap:4px; }
        .star { font-size:1.2rem; cursor:pointer; color:#d1d5db; transition:color .15s; }
        .star.filled, .star:hover { color:#f59e0b; }
        .metric-card--completadas { border-left:4px solid #10b981; }
        .metric-card--pendientes  { border-left:4px solid #f59e0b; }
        .metric-card--proceso     { border-left:4px solid #6366f1; }
        .metric-card--promedio    { border-left:4px solid var(--teal-500); }
    </style>
</head>
<body>
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()" title="Abrir menú lateral" aria-label="Abrir menú lateral">
    <i class="bi bi-layout-sidebar"></i>
</button>

<!-- Modal formulario de evaluación -->
<div class="modal-overlay" id="modalEval">
    <div class="modal-box">
        <h3 style="margin:0 0 4px; color:var(--navy-900);"><i class="bi bi-bar-chart-steps" style="color:var(--ocean-600)"></i> Formulario de Evaluación</h3>
        <p style="margin:0 0 20px; font-size:.85rem; color:var(--ink-600);">Calificación semestral/anual del desempeño del funcionario.</p>
        <form id="formEval" onsubmit="return guardarEval(event)">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div class="form-field">
                    <label>Empleado evaluado</label>
                    <select id="eval-empleado">
                        <option>ZAMBRANO DELGADO HECTOR</option>
                        <option>PEREZ MORALES JUAN CARLOS</option>
                        <option>TORRES VEGA ANA MARIA</option>
                        <option>PALMA TEJENA MICHAEL</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Período de evaluación</label>
                    <select id="eval-periodo">
                        <option>2026 - Semestral</option>
                        <option>2025 - Anual</option>
                    </select>
                </div>
            </div>
            <h4 style="color:var(--navy-900); font-size:.9rem; margin:8px 0 4px;">Criterios de evaluación <small style="color:var(--ink-600); font-weight:400;">(1 = Deficiente, 5 = Excelente)</small></h4>
            <?php
            $criterios = [
                'Cumplimiento de objetivos institucionales',
                'Calidad del trabajo y precisión',
                'Puntualidad y asistencia',
                'Trabajo en equipo y colaboración',
                'Iniciativa y proactividad',
                'Comunicación efectiva',
                'Conocimiento del puesto',
                'Orientación al servicio ciudadano',
            ];
            foreach ($criterios as $i => $c): ?>
            <div class="criteria-row">
                <span class="criteria-label"><?= $c ?></span>
                <div class="criteria-stars" data-criteria="<?= $i ?>">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                    <span class="star" data-val="<?= $s ?>" onclick="setStar(this)" title="<?= $s ?>">★</span>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="form-field" style="margin-top:16px;">
                <label>Observaciones del evaluador</label>
                <textarea rows="3" placeholder="Fortalezas, áreas de mejora, recomendaciones..."></textarea>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:16px;">
                <button type="button" class="btn btn-ghost" onclick="cerrarEval()">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Guardar evaluación</button>
            </div>
        </form>
    </div>
</div>

<div class="app">
    <?php require_once ROOT . '/shared/menu.php'; ?>

    <section class="content">
        <?php $topbarSubtitle='Prototipo — Evaluación y Desempeño';$topbarShowSearch=true;require ROOT.'/shared/topbar.php'; ?>

        <main class="main">
            <div class="content-shell">

                <!-- HERO -->
                <section class="hero" id="hero-desempeno">
                    <div>
                        <div class="hero-kicker">Gestión Operativa · Evaluación</div>
                        <h2>Evaluación y Desempeño</h2>
                        <p>Formularios digitales para calificar el rendimiento del personal según objetivos institucionales por período semestral o anual.</p>
                        <div class="hero-actions">
                            <button class="btn btn-primary" id="btn-nueva-eval" onclick="abrirEval()">
                                <i class="bi bi-clipboard2-check"></i> Nueva Evaluación
                            </button>
                            <button class="btn btn-ghost" id="btn-exportar-eval">
                                <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                            </button>
                        </div>
                    </div>
                    <div class="metrics" style="grid-template-columns:repeat(2,1fr);">
                        <div class="metric-card metric-card--completadas">
                            <div class="metric-label"><i class="bi bi-check-circle"></i> Completadas</div>
                            <div class="metric-value"><?= $resumen['completadas'] ?></div>
                            <div class="metric-foot">Evaluaciones cerradas</div>
                        </div>
                        <div class="metric-card metric-card--pendientes">
                            <div class="metric-label"><i class="bi bi-hourglass-split"></i> Pendientes</div>
                            <div class="metric-value"><?= $resumen['pendientes'] ?></div>
                            <div class="metric-foot">Sin iniciar</div>
                        </div>
                        <div class="metric-card metric-card--proceso">
                            <div class="metric-label"><i class="bi bi-gear-wide"></i> En proceso</div>
                            <div class="metric-value"><?= $resumen['en_proceso'] ?></div>
                            <div class="metric-foot">En curso</div>
                        </div>
                        <div class="metric-card metric-card--promedio">
                            <div class="metric-label"><i class="bi bi-star-half"></i> Promedio general</div>
                            <div class="metric-value"><?= $resumen['promedio'] ?>%</div>
                            <div class="metric-foot">Período actual</div>
                        </div>
                    </div>
                </section>

                <!-- EVALUACIONES GRID -->
                <section class="card" style="padding:20px;">
                    <div class="card-header" style="margin:-20px -20px 20px; border-radius:var(--radius-lg) var(--radius-lg) 0 0;">
                        <div>
                            <h3><i class="bi bi-people"></i> Evaluaciones del Período</h3>
                            <p>Estado de calificación por funcionario.</p>
                        </div>
                        <span class="chip"><i class="bi bi-bar-chart"></i> <?= count($evaluaciones) ?> evaluaciones</span>
                    </div>
                    <div class="eval-grid">
                    <?php foreach ($evaluaciones as $ev): ?>
                        <?php
                        $nivelCls = match($ev['nivel']) {
                            'Excelente'     => 'nivel-excelente',
                            'Satisfactorio' => 'nivel-satisfactorio',
                            'En proceso'    => 'nivel-proceso',
                            default         => 'nivel-pendiente'
                        };
                        $scoreCls = $ev['calificacion'] >= 90 ? 'excelente' : ($ev['calificacion'] >= 70 ? 'satisfactorio' : 'bajo');
                        $iniciales = implode('', array_map(fn($p) => strtoupper(substr($p,0,1)), array_slice(explode(' ', $ev['nombre']), 0, 2)));
                        ?>
                        <div class="eval-card">
                            <div class="eval-card-head">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div style="width:40px; height:40px; border-radius:12px; background:linear-gradient(135deg,var(--navy-900),var(--ocean-600)); color:#fff; display:grid; place-items:center; font-weight:700; font-size:.85rem;">
                                        <?= $iniciales ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700; font-size:.9rem; color:var(--navy-900);"><?= htmlspecialchars(implode(' ', array_slice(explode(' ', $ev['nombre']), 0, 3))) ?></div>
                                        <small style="color:var(--ink-600);"><?= htmlspecialchars($ev['cargo']) ?></small>
                                    </div>
                                </div>
                                <span class="nivel-pill <?= $nivelCls ?>">
                                    <?php if ($ev['nivel'] === 'Excelente') echo '<i class="bi bi-star-fill"></i>'; ?>
                                    <?= $ev['nivel'] ?>
                                </span>
                            </div>
                            <div class="eval-card-body">
                                <div style="font-size:.8rem; color:var(--ink-600); margin-bottom:10px;">
                                    <i class="bi bi-calendar3"></i> <?= $ev['periodo'] ?>
                                    <span style="margin-left:10px;"><i class="bi bi-person-check"></i> <?= htmlspecialchars($ev['evaluador']) ?></span>
                                </div>
                                <?php if (!is_null($ev['calificacion'])): ?>
                                <div class="score-bar" style="margin-bottom:10px;">
                                    <div class="score-track">
                                        <div class="score-fill <?= $scoreCls ?>" style="width:<?= $ev['calificacion'] ?>%"></div>
                                    </div>
                                    <span class="score-val"><?= $ev['calificacion'] ?>%</span>
                                </div>
                                <?php else: ?>
                                <div style="color:var(--ink-600); font-size:.82rem; margin-bottom:10px; padding:8px; background:#f8f9fa; border-radius:8px; text-align:center;">
                                    <i class="bi bi-hourglass"></i>
                                    <?= $ev['estado'] === 'En proceso' ? 'Evaluación en curso...' : 'Pendiente de iniciar' ?>
                                </div>
                                <?php endif; ?>
                                <div class="objetivos-prog">
                                    <span class="obj-dot"></span>
                                    <span style="color:var(--ink-600);">Objetivos: <strong style="color:var(--navy-900);"><?= $ev['objetivos_met'] ?>/<?= $ev['objetivos_total'] ?></strong> cumplidos</span>
                                </div>
                                <div style="display:flex; gap:8px; margin-top:14px;">
                                    <button class="btn btn-outline" style="flex:1; padding:8px; font-size:.8rem;" onclick="showToast('Abriendo detalles de evaluación...', 'info')">
                                        <i class="bi bi-eye"></i> Ver detalles
                                    </button>
                                    <?php if ($ev['estado'] !== 'Completada'): ?>
                                    <button class="btn btn-primary" style="flex:1; padding:8px; font-size:.8rem;" onclick="abrirEval()">
                                        <i class="bi bi-pencil"></i> Evaluar
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </section>

            </div>
        </main>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('currentDate');
    if (el) el.textContent = new Date().toLocaleDateString('es-EC', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    document.getElementById('btn-exportar-eval')?.addEventListener('click', () => showToast('Generando reporte PDF de evaluaciones...', 'info'));
});
function abrirEval() { document.getElementById('modalEval').classList.add('open'); }
function cerrarEval() { document.getElementById('modalEval').classList.remove('open'); }
function guardarEval(e) {
    e.preventDefault();
    showToast('Evaluación guardada correctamente.', 'success');
    cerrarEval();
    return false;
}
function setStar(el) {
    const container = el.parentElement;
    const val = parseInt(el.dataset.val);
    container.querySelectorAll('.star').forEach(s => {
        s.classList.toggle('filled', parseInt(s.dataset.val) <= val);
    });
}
document.getElementById('modalEval')?.addEventListener('click', e => {
    if (e.target === document.getElementById('modalEval')) cerrarEval();
});
</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
