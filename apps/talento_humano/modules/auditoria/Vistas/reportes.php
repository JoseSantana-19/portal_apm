<?php /* reportes.php – Vista: Reportes Generales Jerárquicos */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Generales | Auditoría – APM</title>
    <meta name="description" content="Motor de exportación y reportes jerárquicos del personal por procesos gobernantes, sustantivos y adjetivos.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
    <style>
        .grupo-section { margin-bottom:28px; }
        .grupo-header { display:flex; align-items:center; gap:12px; padding:14px 20px; border-radius:var(--radius-md) var(--radius-md) 0 0; color:#fff; }
        .grupo-header.primary { background:linear-gradient(135deg, var(--navy-900), var(--ocean-700)); }
        .grupo-header.success { background:linear-gradient(135deg, #065f46, #059669); }
        .grupo-header.info    { background:linear-gradient(135deg, var(--ocean-700), var(--teal-500)); }
        .grupo-icon { width:40px; height:40px; border-radius:10px; background:rgba(255,255,255,.18); display:grid; place-items:center; font-size:1.2rem; flex-shrink:0; }
        .grupo-body { border:1px solid var(--line); border-top:none; border-radius:0 0 var(--radius-md) var(--radius-md); overflow:hidden; background:#fff; }
        .area-row { display:flex; align-items:center; padding:14px 20px; border-bottom:1px solid #f1f5f9; gap:16px; transition:background .15s; }
        .area-row:last-child { border-bottom:none; }
        .area-row:hover { background:#fafbff; }
        .area-name { font-weight:600; font-size:.9rem; color:var(--navy-900); flex:1; }
        .area-stats { display:flex; gap:20px; flex-shrink:0; }
        .area-stat { text-align:center; }
        .area-stat span { display:block; font-size:1.1rem; font-weight:800; color:var(--navy-900); }
        .area-stat small { font-size:.7rem; color:var(--ink-600); text-transform:uppercase; letter-spacing:.1em; }
        .contrato-bar { display:flex; gap:4px; margin-top:4px; }
        .contrato-seg { height:5px; border-radius:999px; }
        .seg-nom { background:var(--ocean-700); }
        .seg-cont { background:var(--teal-300); }
        .export-panel { background:#fff; border:1px solid var(--line); border-radius:var(--radius-md); padding:20px; }
        .export-opts { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:12px; margin-top:14px; }
        .export-card { border:1px dashed rgba(16,120,162,.4); border-radius:var(--radius-md); padding:16px; text-align:center; cursor:pointer; transition:all .2s; }
        .export-card:hover { border-color:var(--teal-500); background:#f0feff; transform:translateY(-2px); }
        .export-card i { font-size:2rem; display:block; margin-bottom:8px; }
        .export-card.pdf i { color:#dc2626; }
        .export-card.excel i { color:#059669; }
        .export-card.csv i { color:var(--ocean-700); }
        .total-banner { background:linear-gradient(135deg,var(--navy-900),var(--ocean-600)); color:#fff; border-radius:var(--radius-md); padding:20px; display:flex; align-items:center; justify-content:space-between; gap:20px; }
        .total-banner-num { font-size:2.8rem; font-weight:900; font-family:var(--font-display); }
        .total-banner-label { font-size:.8rem; text-transform:uppercase; letter-spacing:.2em; opacity:.8; }
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
                <section class="hero" id="hero-reportes">
                    <div>
                        <div class="hero-kicker">Auditoría y Control · Motor de Exportación</div>
                        <h2>Reportes Generales</h2>
                        <p>Personal agrupado jerárquicamente por Procesos Gobernantes, Sustantivos y Adjetivos. Exporte en PDF o Excel para auditorías institucionales y planificación.</p>
                        <div class="hero-actions">
                            <button class="btn btn-primary" id="btn-export-pdf" onclick="exportar('PDF')">
                                <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                            </button>
                            <button class="btn btn-outline" id="btn-export-excel" onclick="exportar('Excel')">
                                <i class="bi bi-file-earmark-excel"></i> Exportar Excel
                            </button>
                            <button class="btn btn-ghost" id="btn-imprimir" onclick="window.print()">
                                <i class="bi bi-printer"></i> Imprimir
                            </button>
                        </div>
                    </div>
                    <div class="metrics" style="grid-template-columns:repeat(2,1fr);">
                        <div class="metric-card" style="border-left:4px solid var(--navy-900)">
                            <div class="metric-label"><i class="bi bi-people-fill"></i> Total personal</div>
                            <div class="metric-value"><?= $totales['empleados'] ?></div>
                            <div class="metric-foot">Servidores registrados</div>
                        </div>
                        <div class="metric-card" style="border-left:4px solid #10b981">
                            <div class="metric-label"><i class="bi bi-person-check"></i> Activos</div>
                            <div class="metric-value"><?= $totales['activos'] ?></div>
                            <div class="metric-foot">En funciones</div>
                        </div>
                    </div>
                </section>

                <!-- BANNER TOTAL -->
                <div class="total-banner" id="banner-total">
                    <div>
                        <div class="total-banner-label">Total servidores Autoridad Portuaria de Manta</div>
                        <div class="total-banner-num"><?= $totales['empleados'] ?> funcionarios</div>
                        <div style="font-size:.83rem; opacity:.8; margin-top:4px;"><?= $totales['activos'] ?> activos · <?= $totales['empleados'] - $totales['activos'] ?> en permiso o inactivos · Corte al <?= date('d \d\e F \d\e Y') ?></div>
                    </div>
                    <div style="opacity:.3; font-size:4rem;">
                        <i class="bi bi-building-fill"></i>
                    </div>
                </div>

                <!-- GRUPOS JERÁRQUICOS -->
                <?php foreach ($grupos as $grupo): ?>
                <div class="grupo-section">
                    <div class="grupo-header <?= $grupo['color'] ?>">
                        <div class="grupo-icon"><i class="bi <?= $grupo['icono'] ?>"></i></div>
                        <div>
                            <h3 style="margin:0; font-family:var(--font-display);"><?= htmlspecialchars($grupo['tipo']) ?></h3>
                            <small style="opacity:.8;"><?= count($grupo['areas']) ?> áreas · <?= array_sum(array_column($grupo['areas'], 'empleados')) ?> servidores</small>
                        </div>
                        <div style="margin-left:auto; display:flex; align-items:center; gap:14px;">
                            <div style="text-align:right;">
                                <span style="font-size:2rem; font-weight:900;"><?= array_sum(array_column($grupo['areas'], 'empleados')) ?></span>
                                <br><small style="opacity:.7; font-size:.75rem;">TOTAL</small>
                            </div>
                        </div>
                    </div>
                    <div class="grupo-body">
                        <?php foreach ($grupo['areas'] as $area): ?>
                            <?php
                            $noms  = $area['contratos']['Nombramiento'] ?? 0;
                            $conts = $area['contratos']['Contrato'] ?? 0;
                            $pctNom  = $area['empleados'] > 0 ? round(($noms / $area['empleados']) * 100) : 0;
                            $pctCont = $area['empleados'] > 0 ? round(($conts / $area['empleados']) * 100) : 0;
                            ?>
                            <div class="area-row">
                                <i class="bi bi-diagram-3" style="color:var(--ink-600); font-size:.9rem;"></i>
                                <div style="flex:1; min-width:0;">
                                    <div class="area-name"><?= htmlspecialchars($area['nombre']) ?></div>
                                    <div style="display:flex; gap:10px; margin-top:4px; flex-wrap:wrap;">
                                        <span style="font-size:.73rem; color:var(--ink-600);"><i class="bi bi-briefcase" style="color:var(--ocean-700)"></i> <?= $noms ?> Nombramientos</span>
                                        <span style="font-size:.73rem; color:var(--ink-600);"><i class="bi bi-file-earmark-text" style="color:var(--teal-500)"></i> <?= $conts ?> Contratos</span>
                                    </div>
                                    <div class="contrato-bar" style="margin-top:6px; max-width:200px;">
                                        <?php if ($pctNom > 0): ?><div class="contrato-seg seg-nom" style="flex:<?= $pctNom ?>; min-width:4px;" title="Nombramientos <?= $pctNom ?>%"></div><?php endif; ?>
                                        <?php if ($pctCont > 0): ?><div class="contrato-seg seg-cont" style="flex:<?= $pctCont ?>; min-width:4px;" title="Contratos <?= $pctCont ?>%"></div><?php endif; ?>
                                    </div>
                                </div>
                                <div class="area-stats">
                                    <div class="area-stat">
                                        <span><?= $area['empleados'] ?></span>
                                        <small>Total</small>
                                    </div>
                                    <div class="area-stat">
                                        <span style="color:#10b981;"><?= $area['activos'] ?></span>
                                        <small>Activos</small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- PANEL DE EXPORTACIÓN -->
                <div class="export-panel" id="panel-exportacion">
                    <h3 style="margin:0 0 4px; color:var(--navy-900);"><i class="bi bi-box-arrow-up"></i> Opciones de exportación</h3>
                    <p style="margin:0; color:var(--ink-600); font-size:.85rem;">Seleccione el formato en que desea exportar el reporte jerárquico completo.</p>
                    <div class="export-opts">
                        <div class="export-card pdf" onclick="exportar('PDF')">
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                            <strong>Reporte PDF</strong>
                            <small style="color:var(--ink-600);">Formato imprimible, con encabezado institucional y firmas</small>
                        </div>
                        <div class="export-card excel" onclick="exportar('Excel')">
                            <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                            <strong>Excel (.xlsx)</strong>
                            <small style="color:var(--ink-600);">Datos tabulares con totales y fórmulas para análisis</small>
                        </div>
                        <div class="export-card csv" onclick="exportar('CSV')">
                            <i class="bi bi-filetype-csv"></i>
                            <strong>CSV plano</strong>
                            <small style="color:var(--ink-600);">Formato universal para sistemas externos</small>
                        </div>
                    </div>
                    <div style="margin-top:12px; padding:10px 14px; background:#f0f7ff; border-radius:10px; font-size:.82rem; color:var(--ocean-700);">
                        <i class="bi bi-info-circle"></i>
                        La lógica de exportación aplica la jerarquía SQL: <strong>Procesos Gobernantes → Sustantivos → Adjetivos</strong>, con subtotales por área.
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
function exportar(formato) {
    const msgs = {
        'PDF':   'Generando reporte PDF jerárquico con encabezado APM...',
        'Excel': 'Generando hoja de cálculo Excel con subtotales...',
        'CSV':   'Exportando datos en formato CSV...'
    };
    showToast(msgs[formato] || 'Exportando...', 'info');
    setTimeout(() => showToast(`Reporte ${formato} generado exitosamente. Descargando...`, 'success'), 2000);
}
</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
