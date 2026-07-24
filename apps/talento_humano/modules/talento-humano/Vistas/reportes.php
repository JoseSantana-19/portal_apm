<?php /* reportes.php – Vista: Panel de Reportes Generales */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Generales | Talento Humano – APM</title>
    <meta name="description" content="Panel de generación de reportes del módulo de Talento Humano — Autoridad Portuaria de Manta.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
    <style>
        .report-card {
            background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 20px;
            cursor: pointer; transition: all .2s; border-left: 4px solid transparent;
        }
        .report-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .report-card.selected { border-left-color: var(--teal-500); background: #f0fdfa; }
        .report-card h4 { font-size: .9rem; font-weight: 700; color: var(--navy-900); margin: 0 0 4px; }
        .report-card p  { font-size: .8rem; color: var(--ink-600); margin: 0; }
        .report-icon { width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; font-size: 1.3rem; margin-bottom: 12px; flex-shrink: 0; }
        .filter-panel { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 22px; }
        .filter-panel h4 { font-size: .85rem; font-weight: 700; color: var(--navy-900); margin-bottom: 16px; }
        .form-field { margin-bottom: 14px; }
        .form-field label { display: block; font-size: .8rem; font-weight: 600; color: var(--navy-900); margin-bottom: 6px; }
        .form-field input, .form-field select {
            width: 100%; box-sizing: border-box; padding: 10px 13px;
            border: 1px solid var(--line); border-radius: 10px; font-size: .85rem;
            background: #fff; outline: none; transition: border-color .2s;
        }
        .form-field input:focus, .form-field select:focus {
            border-color: var(--teal-500); box-shadow: 0 0 0 3px rgba(18,180,199,.12);
        }
        .export-btns { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
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
                <section class="hero" id="hero-reportes">
                    <div>
                        <div class="hero-kicker">Talento Humano · Análisis de Datos</div>
                        <h2>Reportes Generales</h2>
                        <p>Seleccione el tipo de reporte, aplique los filtros necesarios y exporte en el formato deseado. Los reportes se generarán desde la base de datos en tiempo real.</p>
                    </div>
                    <div class="metrics" style="grid-template-columns:repeat(2,1fr);">
                        <div class="metric-card" style="border-left:4px solid var(--ocean-700);">
                            <div class="metric-label"><i class="bi bi-file-earmark-bar-graph"></i> Reportes disponibles</div>
                            <div class="metric-value">6</div>
                            <div class="metric-foot">Tipos de informe</div>
                        </div>
                        <div class="metric-card" style="border-left:4px solid #10b981;">
                            <div class="metric-label"><i class="bi bi-cloud-download"></i> Formatos</div>
                            <div class="metric-value">3</div>
                            <div class="metric-foot">CSV · PDF · Excel</div>
                        </div>
                    </div>
                </section>

                <!-- TIPOS DE REPORTE -->
                <section class="card" style="padding:20px; margin-bottom:20px;">
                    <h3 style="font-size:.9rem; font-weight:700; color:var(--navy-900); margin-bottom:16px;">
                        <i class="bi bi-grid" style="color:var(--ocean-700);"></i> Seleccione el Tipo de Reporte
                    </h3>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:14px;" id="gridTiposReporte">

                        <div class="report-card selected" onclick="seleccionarReporte(this, 'nomina')" id="rep-nomina">
                            <div class="report-icon" style="background:rgba(14,116,144,.1); color:var(--ocean-700);"><i class="bi bi-people-fill"></i></div>
                            <h4>Nómina Completa</h4>
                            <p>Lista de todos los funcionarios activos con su RMU y cargo.</p>
                        </div>

                        <div class="report-card" onclick="seleccionarReporte(this, 'cumpleanos')" id="rep-cumpleanos">
                            <div class="report-icon" style="background:rgba(245,158,11,.1); color:#b45309;"><i class="bi bi-cake2"></i></div>
                            <h4>Cumpleaños del Mes</h4>
                            <p>Funcionarios que cumplen años en el período seleccionado.</p>
                        </div>

                        <div class="report-card" onclick="seleccionarReporte(this, 'por_area')" id="rep-por_area">
                            <div class="report-icon" style="background:rgba(99,102,241,.1); color:#4338ca;"><i class="bi bi-diagram-3-fill"></i></div>
                            <h4>Personal por Área</h4>
                            <p>Distribución del personal agrupada por dirección y departamento.</p>
                        </div>

                        <div class="report-card" onclick="seleccionarReporte(this, 'vacaciones')" id="rep-vacaciones">
                            <div class="report-icon" style="background:rgba(16,185,129,.1); color:#059669;"><i class="bi bi-calendar-check"></i></div>
                            <h4>Vacaciones y Ausencias</h4>
                            <p>Solicitudes aprobadas, pendientes y saldos vacacionales.</p>
                        </div>

                        <div class="report-card" onclick="seleccionarReporte(this, 'acciones')" id="rep-acciones">
                            <div class="report-icon" style="background:rgba(239,68,68,.1); color:#dc2626;"><i class="bi bi-file-earmark-text"></i></div>
                            <h4>Acciones de Personal</h4>
                            <p>Registro de movimientos: ingresos, traslados, cesaciones.</p>
                        </div>

                        <div class="report-card" onclick="seleccionarReporte(this, 'contratos')" id="rep-contratos">
                            <div class="report-icon" style="background:rgba(147,51,234,.1); color:#7c3aed;"><i class="bi bi-file-earmark-lock"></i></div>
                            <h4>Por Tipo de Contrato</h4>
                            <p>Clasificación del personal por modalidad contractual LOSEP.</p>
                        </div>

                    </div>
                </section>

                <!-- FILTROS Y EXPORTACIÓN -->
                <div style="display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start;">

                    <!-- PANEL DE FILTROS -->
                    <div class="filter-panel">
                        <h4><i class="bi bi-funnel" style="color:var(--ocean-700);"></i> Filtros del Reporte: <span id="tituloReporteActivo" style="color:var(--ocean-700);">Nómina Completa</span></h4>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                            <div class="form-field">
                                <label><i class="bi bi-calendar-event"></i> Fecha Desde</label>
                                <input type="date" id="filtroFechaDesde" value="<?= date('Y-01-01') ?>">
                            </div>
                            <div class="form-field">
                                <label><i class="bi bi-calendar-event"></i> Fecha Hasta</label>
                                <input type="date" id="filtroFechaHasta" value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="form-field">
                            <label><i class="bi bi-building"></i> Dirección / Proceso Institucional</label>
                            <select id="filtroDireccion">
                                <option value="">— Todas las Direcciones —</option>
                                <option value="gobernantes">Procesos Gobernantes</option>
                                <option value="sustantivos">Procesos Sustantivos</option>
                                <option value="adjetivos">Procesos Adjetivos</option>
                                <option value="dpe">Dirección de Planificación Estratégica</option>
                                <option value="daf">Dirección Administrativa Financiera</option>
                                <option value="dip">Dirección de Infraestructura Portuaria</option>
                                <option value="ddsp">Dirección de Delegación de Servicios Portuarios</option>
                                <option value="gg">Gerencia General</option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label><i class="bi bi-briefcase"></i> Tipo de Contrato</label>
                            <select id="filtroContrato">
                                <option value="">— Todos los tipos —</option>
                                <option value="nombramiento">Nombramiento Permanente</option>
                                <option value="contrato">Contrato de Servicios</option>
                                <option value="ocasional">Contrato Ocasional</option>
                            </select>
                        </div>

                        <div class="form-field">
                            <label><i class="bi bi-person-check"></i> Estado del Funcionario</label>
                            <select id="filtroEstado">
                                <option value="">— Todos los estados —</option>
                                <option value="activo">Activo</option>
                                <option value="permiso">Con Permiso</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>

                        <div class="export-btns">
                            <button class="btn btn-primary" onclick="generarReporte('csv')">
                                <i class="bi bi-file-earmark-excel"></i> Exportar CSV / Excel
                            </button>
                            <button class="btn btn-outline" onclick="generarReporte('pdf')">
                                <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                            </button>
                            <button class="btn btn-ghost" onclick="previsualizarReporte()">
                                <i class="bi bi-eye"></i> Vista Previa
                            </button>
                        </div>
                    </div>

                    <!-- PANEL DE INFORMACIÓN -->
                    <div style="display:flex; flex-direction:column; gap:14px;">
                        <div class="card" style="padding:18px; border-left:4px solid var(--ocean-700);">
                            <h4 style="font-size:.85rem; font-weight:700; color:var(--navy-900); margin-bottom:10px;">
                                <i class="bi bi-lightbulb" style="color:#f59e0b;"></i> ¿Cómo funciona?
                            </h4>
                            <ol style="font-size:.8rem; color:var(--ink-600); margin:0; padding-left:18px; line-height:1.8;">
                                <li>Seleccione el tipo de reporte de la grilla superior.</li>
                                <li>Aplique los filtros por rango de fechas, dirección o estado.</li>
                                <li>Haga clic en <strong>Exportar CSV/Excel</strong> para descarga inmediata.</li>
                                <li>Use <strong>Exportar PDF</strong> para reportes formales (TCPDF en producción).</li>
                            </ol>
                        </div>

                        <div class="card" style="padding:18px; background:#fffbeb; border:1px solid rgba(245,158,11,.3);">
                            <h4 style="font-size:.82rem; font-weight:700; color:#92400e; margin-bottom:8px;">
                                <i class="bi bi-cone-striped"></i> Modo Prototipo
                            </h4>
                            <p style="font-size:.78rem; color:#92400e; margin:0; line-height:1.6;">
                                La exportación CSV descarga los datos de la tabla en pantalla.
                                La exportación real a PDF se conectará con <strong>TCPDF / DomPDF</strong> en la siguiente fase.
                            </p>
                        </div>

                        <div class="card" style="padding:18px; border-left:4px solid #10b981;">
                            <h4 style="font-size:.82rem; font-weight:700; color:var(--navy-900); margin-bottom:8px;">
                                <i class="bi bi-shield-check" style="color:#10b981;"></i> Auditoría Automática
                            </h4>
                            <p style="font-size:.78rem; color:var(--ink-600); margin:0; line-height:1.6;">
                                Cada exportación quedará registrada automáticamente en el módulo de <strong>Logs de Auditoría</strong> con usuario, fecha, hora e IP de origen.
                            </p>
                        </div>
                    </div>

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
});

const nombresReporte = {
    nomina:      'Nómina Completa',
    cumpleanos:  'Cumpleaños del Mes',
    por_area:    'Personal por Área',
    vacaciones:  'Vacaciones y Ausencias',
    acciones:    'Acciones de Personal',
    contratos:   'Por Tipo de Contrato'
};

let reporteActivo = 'nomina';

function seleccionarReporte(card, tipo) {
    document.querySelectorAll('.report-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    reporteActivo = tipo;
    const titulo = document.getElementById('tituloReporteActivo');
    if (titulo) titulo.textContent = nombresReporte[tipo] || tipo;
    showToast(`Reporte seleccionado: ${nombresReporte[tipo]}`, 'info');
}

function generarReporte(formato) {
    const nombre = nombresReporte[reporteActivo] || reporteActivo;
    const desde = document.getElementById('filtroFechaDesde').value;
    const hasta = document.getElementById('filtroFechaHasta').value;

    if (formato === 'csv') {
        // Descarga CSV simulada con datos de ejemplo
        const BOM = '\uFEFF';
        const data = `"Cédula","Nombres","Cargo","Dirección","RMU","Estado"\n` +
            `"1308126646","ZAMBRANO DELGADO HECTOR FERNANDO","Jefe de Sistemas","Dir. Planificación","$1500.00","Activo"\n` +
            `"1311567890","PEREZ MORALES JUAN CARLOS","Economista","Dir. Financiera","$1200.00","Activo"\n` +
            `"0923456781","TORRES VEGA ANA MARIA","Analista RRHH","Talento Humano","$950.00","Activo"`;
        const blob = new Blob([BOM + data], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.download = `Reporte_${nombre.replace(/ /g,'_')}_APM_${desde}_${hasta}.csv`;
        link.href = URL.createObjectURL(blob);
        link.click();
        showToast(`✅ Reporte "${nombre}" descargado en CSV.`, 'success');
    } else if (formato === 'pdf') {
        showToast(`📄 Generando PDF de "${nombre}"... (TCPDF se integrará en la próxima fase)`, 'info');
    }
}

function previsualizarReporte() {
    showToast(`🔍 Vista previa de "${nombresReporte[reporteActivo]}" — próximamente disponible.`, 'info');
}
</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
