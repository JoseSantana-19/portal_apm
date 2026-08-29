<?php /* asistencia.php – Vista: Módulo Asistencia y Turnos */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistencia y Turnos | Talento Humano – APM</title>
    <meta name="description" content="Registro y control de asistencia del personal de la Autoridad Portuaria de Manta.">
    <?php require ROOT . '/shared/head_assets.php'; ?>
    <style>
        /* ── Estilos propios del módulo Asistencia ── */
        .estado-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 999px; font-size: .78rem; font-weight: 600;
        }
        .estado-normal   { background: rgba(16,185,129,.12); color: #059669; border: 1px solid rgba(16,185,129,.25); }
        .estado-atraso   { background: rgba(245,158,11,.12);  color: #b45309; border: 1px solid rgba(245,158,11,.3); }
        .estado-ausente  { background: rgba(239,68,68,.12);   color: #dc2626; border: 1px solid rgba(239,68,68,.25); }
        .estado-extra    { background: rgba(99,102,241,.12);   color: #4338ca; border: 1px solid rgba(99,102,241,.25); }
        .time-cell       { font-family: monospace; font-size: .92rem; color: var(--navy-900); font-weight: 600; }
        .time-none       { color: var(--ink-600); font-style: italic; font-size: .82rem; }
        .extras-badge    { background: rgba(99,102,241,.15); color: #4338ca; padding: 2px 8px; border-radius: 6px; font-size: .78rem; font-weight: 700; }
        .atraso-badge    { background: rgba(245,158,11,.15);  color: #b45309; padding: 2px 8px; border-radius: 6px; font-size: .78rem; font-weight: 700; }
        .metric-card--presentes  { border-left: 4px solid #10b981; }
        .metric-card--ausentes   { border-left: 4px solid #ef4444; }
        .metric-card--atrasos    { border-left: 4px solid #f59e0b; }
        .metric-card--extras     { border-left: 4px solid #6366f1; }
        .import-zone {
            border: 2px dashed var(--line); border-radius: var(--radius-md);
            padding: 32px; text-align: center; color: var(--ink-600);
            background: #f8fbff; transition: border-color .2s;
        }
        .import-zone:hover { border-color: var(--teal-500); }
        .import-zone i { font-size: 2rem; color: var(--ocean-600); margin-bottom: 8px; display: block; }
        .tab-nav { display: flex; gap: 4px; padding: 4px; background: #f1f5f9; border-radius: 12px; margin-bottom: 20px; }
        .tab-btn { flex: 1; padding: 10px 16px; border: none; background: transparent; border-radius: 10px; cursor: pointer; font-weight: 600; font-size: .85rem; color: var(--ink-600); transition: all .2s; }
        .tab-btn.active { background: #fff; color: var(--navy-900); box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
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
        <?php $topbarSubtitle='Prototipo — Asistencia y Turnos';$topbarShowSearch=true;require ROOT.'/shared/topbar.php'; ?>

        <main class="main">
            <div class="content-shell">

                <!-- HERO -->
                <section class="hero" id="hero-asistencia">
                    <div>
                        <div class="hero-kicker">Gestión Operativa · Control de Jornada</div>
                        <h2>Asistencia y Turnos</h2>
                        <p>Registro diario de entradas y salidas. El sistema calcula automáticamente atrasos, horas extras y ausencias.</p>
                        <div class="hero-actions">
                            <button class="btn btn-primary" id="btn-registrar-asistencia" onclick="showTab('tab-registrar')">
                                <i class="bi bi-fingerprint"></i> Registrar Asistencia
                            </button>
                            <button class="btn btn-outline" id="btn-importar" onclick="showTab('tab-importar')">
                                <i class="bi bi-upload"></i> Importar Biométrico
                            </button>
                            <button class="btn btn-ghost" id="btn-exportar-asistencia">
                                <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                            </button>
                        </div>
                    </div>
                    <div class="metrics" style="grid-template-columns: repeat(2, 1fr);">
                        <div class="metric-card metric-card--presentes">
                            <div class="metric-label"><i class="bi bi-person-check"></i> Presentes hoy</div>
                            <div class="metric-value"><?= $resumen['presentes'] ?></div>
                            <div class="metric-foot">de <?= $resumen['total_registros'] ?> esperados</div>
                        </div>
                        <div class="metric-card metric-card--ausentes">
                            <div class="metric-label"><i class="bi bi-person-x"></i> Ausentes</div>
                            <div class="metric-value"><?= $resumen['ausentes'] ?></div>
                            <div class="metric-foot">Sin registro de entrada</div>
                        </div>
                        <div class="metric-card metric-card--atrasos">
                            <div class="metric-label"><i class="bi bi-clock-history"></i> Con atrasos</div>
                            <div class="metric-value"><?= $resumen['atrasos'] ?></div>
                            <div class="metric-foot">Llegada tardía</div>
                        </div>
                        <div class="metric-card metric-card--extras">
                            <div class="metric-label"><i class="bi bi-lightning-charge"></i> Horas extras</div>
                            <div class="metric-value"><?= number_format($resumen['horas_extras'], 1) ?>h</div>
                            <div class="metric-foot">Total acumulado hoy</div>
                        </div>
                    </div>
                </section>

                <!-- TABS -->
                <div class="card" style="padding: 20px;">
                    <div class="tab-nav" id="tabNav">
                        <button class="tab-btn active" onclick="showTab('tab-registros')" id="tabBtn-registros">
                            <i class="bi bi-list-check"></i> Registros del día
                        </button>
                        <button class="tab-btn" onclick="showTab('tab-registrar')" id="tabBtn-registrar">
                            <i class="bi bi-fingerprint"></i> Nuevo registro
                        </button>
                        <button class="tab-btn" onclick="showTab('tab-importar')" id="tabBtn-importar">
                            <i class="bi bi-cloud-upload"></i> Importar biométrico
                        </button>
                    </div>

                    <!-- TAB: Registros del día -->
                    <div class="tab-content active" id="tab-registros">
                        <div class="card-header" style="border-radius: 12px 12px 0 0; margin: -20px -20px 16px;">
                            <div>
                                <h3><i class="bi bi-calendar3"></i> Registros – <?= $fecha_hoy ?></h3>
                                <p>Lista de marcaciones del día actual del personal.</p>
                            </div>
                            <span class="chip"><i class="bi bi-people"></i> <?= count($registros) ?> funcionarios</span>
                        </div>
                        <div class="table-wrap">
                            <table id="tablaAsistencia">
                                <thead>
                                    <tr>
                                        <th>Funcionario</th>
                                        <th>Departamento</th>
                                        <th>Entrada</th>
                                        <th>Salida</th>
                                        <th>Horas</th>
                                        <th>Extras / Atraso</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($registros as $r): ?>
                                    <tr class="table-row">
                                        <td>
                                            <div class="name-meta">
                                                <span><?= htmlspecialchars($r['nombre']) ?></span>
                                                <small><?= htmlspecialchars($r['cargo']) ?></small>
                                            </div>
                                        </td>
                                        <td><span class="dept-pill"><?= htmlspecialchars($r['departamento']) ?></span></td>
                                        <td>
                                            <?php if ($r['hora_entrada']): ?>
                                                <span class="time-cell"><i class="bi bi-box-arrow-in-right" style="color:var(--teal-500)"></i> <?= $r['hora_entrada'] ?></span>
                                            <?php else: ?>
                                                <span class="time-none">— Sin registro</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($r['hora_salida']): ?>
                                                <span class="time-cell"><i class="bi bi-box-arrow-right" style="color:var(--ink-600)"></i> <?= $r['hora_salida'] ?></span>
                                            <?php else: ?>
                                                <span class="time-none">— Sin registro</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:center; font-weight:700;"><?= $r['horas_trabajadas'] > 0 ? number_format($r['horas_trabajadas'], 1).'h' : '—' ?></td>
                                        <td>
                                            <?php if ($r['horas_extras'] > 0): ?>
                                                <span class="extras-badge">+<?= number_format($r['horas_extras'], 1) ?>h extra</span>
                                            <?php endif; ?>
                                            <?php if ($r['atraso_min'] > 0): ?>
                                                <span class="atraso-badge"><?= $r['atraso_min'] ?>min atraso</span>
                                            <?php endif; ?>
                                            <?php if ($r['horas_extras'] == 0 && $r['atraso_min'] == 0 && $r['hora_entrada']): ?>
                                                <span style="color:var(--ink-600); font-size:.82rem;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $claseEstado = [
                                                'Normal'     => 'estado-normal',
                                                'Atraso'     => 'estado-atraso',
                                                'Ausente'    => 'estado-ausente',
                                                'Horas Extra'=> 'estado-extra',
                                            ][$r['estado']] ?? '';
                                            $iconoEstado = [
                                                'Normal'     => 'bi-check-circle',
                                                'Atraso'     => 'bi-clock-history',
                                                'Ausente'    => 'bi-x-circle',
                                                'Horas Extra'=> 'bi-lightning-charge',
                                            ][$r['estado']] ?? 'bi-circle';
                                            ?>
                                            <span class="estado-pill <?= $claseEstado ?>">
                                                <i class="bi <?= $iconoEstado ?>"></i>
                                                <?= htmlspecialchars($r['estado']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB: Nuevo registro manual -->
                    <div class="tab-content" id="tab-registrar">
                        <div class="card-header" style="border-radius: 12px 12px 0 0; margin: -20px -20px 20px;">
                            <div>
                                <h3><i class="bi bi-fingerprint"></i> Registrar Marcación Manual</h3>
                                <p>Úselo solo cuando el biométrico presente fallos técnicos.</p>
                            </div>
                        </div>
                        <form id="formRegistroManual" onsubmit="return registrarMarcacion(event)" style="max-width: 600px; margin: 0 auto;">
                            <div style="display: grid; gap: 16px;">
                                <div>
                                    <label style="font-size:.85rem; font-weight:600; color:var(--navy-900); display:block; margin-bottom:6px;">Cédula / ID Empleado</label>
                                    <input type="text" id="inp-cedula" placeholder="Ej: 1312345678" required
                                           style="width:100%; padding:12px 16px; border:1px solid var(--line); border-radius:12px; font-size:.9rem; outline:none;">
                                </div>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                                    <div>
                                        <label style="font-size:.85rem; font-weight:600; color:var(--navy-900); display:block; margin-bottom:6px;">Fecha</label>
                                        <input type="date" id="inp-fecha" value="<?= date('Y-m-d') ?>" required
                                               style="width:100%; padding:12px 16px; border:1px solid var(--line); border-radius:12px; font-size:.9rem; outline:none;">
                                    </div>
                                    <div>
                                        <label style="font-size:.85rem; font-weight:600; color:var(--navy-900); display:block; margin-bottom:6px;">Tipo de marcación</label>
                                        <select id="inp-tipo" required style="width:100%; padding:12px 16px; border:1px solid var(--line); border-radius:12px; font-size:.9rem; outline:none; background:#fff;">
                                            <option value="entrada">Entrada</option>
                                            <option value="salida">Salida</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label style="font-size:.85rem; font-weight:600; color:var(--navy-900); display:block; margin-bottom:6px;">Hora</label>
                                    <input type="time" id="inp-hora" value="<?= date('H:i') ?>" required
                                           style="width:100%; padding:12px 16px; border:1px solid var(--line); border-radius:12px; font-size:.9rem; outline:none;">
                                </div>
                                <div>
                                    <label style="font-size:.85rem; font-weight:600; color:var(--navy-900); display:block; margin-bottom:6px;">Motivo de registro manual</label>
                                    <textarea id="inp-motivo" placeholder="Ej: Fallo del reloj biométrico" rows="3" required
                                              style="width:100%; padding:12px 16px; border:1px solid var(--line); border-radius:12px; font-size:.9rem; outline:none; resize:vertical;"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" id="btn-submit-registro">
                                    <i class="bi bi-check-circle"></i> Guardar Marcación
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- TAB: Importar biométrico -->
                    <div class="tab-content" id="tab-importar">
                        <div class="card-header" style="border-radius: 12px 12px 0 0; margin: -20px -20px 20px;">
                            <div>
                                <h3><i class="bi bi-cloud-upload"></i> Importar desde Reloj Biométrico</h3>
                                <p>Suba el archivo exportado por el reloj de control de asistencia.</p>
                            </div>
                        </div>
                        <div style="max-width: 600px; margin: 0 auto;">
                            <div class="import-zone" id="dropZone">
                                <i class="bi bi-file-earmark-spreadsheet"></i>
                                <p style="font-weight:600; margin:0 0 4px;">Arrastre el archivo aquí o haga clic para seleccionarlo</p>
                                <small>Formatos soportados: .xls, .xlsx, .csv — Máx. 10 MB</small>
                                <br><br>
                                <input type="file" id="fileInput" accept=".xls,.xlsx,.csv" style="display:none;">
                                <button class="btn btn-outline" onclick="document.getElementById('fileInput').click()" id="btn-select-file">
                                    <i class="bi bi-folder2-open"></i> Seleccionar archivo
                                </button>
                            </div>
                            <div style="margin-top:20px; padding:16px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px;">
                                <h4 style="margin:0 0 8px; color:#166534; font-size:.9rem;"><i class="bi bi-info-circle"></i> Formato esperado del archivo</h4>
                                <p style="margin:0; font-size:.82rem; color:#166534;">El archivo debe contener las columnas: <strong>ID_EMPLEADO, CEDULA, FECHA (YYYY-MM-DD), HORA, TIPO (E/S)</strong></p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('currentDate');
    if (el) el.textContent = new Date().toLocaleDateString('es-EC', { weekday:'long', year:'numeric', month:'long', day:'numeric' });

    document.getElementById('btn-exportar-asistencia')?.addEventListener('click', () => {
        showToast('Exportación de reporte de asistencia en proceso...', 'info');
    });

    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    if (dropZone && fileInput) {
        dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.style.borderColor = 'var(--teal-500)'; });
        dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor = ''; });
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            dropZone.style.borderColor = '';
            showToast('Archivo recibido. Procesando importación...', 'success');
        });
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) showToast(`Archivo "${fileInput.files[0].name}" seleccionado. Procesando...`, 'success');
        });
    }
});

function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId)?.classList.add('active');
    const btnId = 'tabBtn-' + tabId.replace('tab-','');
    document.getElementById(btnId)?.classList.add('active');
}

function registrarMarcacion(e) {
    e.preventDefault();
    showToast('Marcación manual registrada correctamente.', 'success');
    e.target.reset();
    document.getElementById('inp-fecha').value = new Date().toISOString().split('T')[0];
    setTimeout(() => showTab('tab-registros'), 1500);
    return false;
}
</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
