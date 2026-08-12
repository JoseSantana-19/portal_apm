<?php
/* estudio_seguridad.php – Vista: Formato Estudio de Seguridad Socioeconómico
   Código: APM-BASC-TH-FO-002 | Fecha: 01/04/2019
   Formulario completo en 3 partes correspondientes a las páginas 1, 2 y 3 del documento oficial. */

$e              = $empleado      ?? [];
$codigoFormato  = $codigoFormato ?? 'APM-BASC-TH-FO-002';
$fechaFormato   = $fechaFormato  ?? '01/04/2019';
$modoImpresion  = $modoImpresion ?? false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estudio de Seguridad Socioeconómico | Talento Humano APM</title>
    <meta name="description" content="Formato Estudio de Seguridad Socioeconómico — Autoridad Portuaria de Manta. Código APM-BASC-TH-FO-002.">
    <?php require ROOT . '/shared/head_assets.php'; ?>
    <style>
        /* ── Estilos exclusivos Estudio Seguridad ──────────────────────────── */

        /* Barra de partes (pestañas de parte 1, 2, 3) */
        .partes-nav {
            display: flex;
            gap: 4px;
            padding: 0 24px;
            background: linear-gradient(135deg, #1e1b4b, #3730a3);
            overflow-x: auto;
            scrollbar-width: none;
        }
        .partes-nav::-webkit-scrollbar { display: none; }
        .parte-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 14px 20px;
            border: none; background: transparent;
            color: rgba(255,255,255,.65);
            font-size: .82rem; font-weight: 600;
            font-family: var(--font-sans);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: color .2s, border-color .2s;
            white-space: nowrap;
        }
        .parte-btn i { font-size: 1rem; }
        .parte-btn:hover { color: #fff; background: rgba(255,255,255,.08); }
        .parte-btn.active {
            color: #c7d2fe;
            border-bottom-color: #c7d2fe;
            background: rgba(255,255,255,.06);
        }
        .parte-num {
            background: rgba(99,102,241,.3); border: 1px solid rgba(165,180,252,.4);
            color: #c7d2fe; border-radius: 999px;
            font-size: .62rem; padding: 2px 7px; font-weight: 700;
        }
        .parte-btn.active .parte-num { background: #c7d2fe; color: #1e1b4b; }

        .parte-panel { display: none; animation: floatIn .35s ease both; }
        .parte-panel.active { display: block; }

        /* Encabezado del formulario */
        .seg-header {
            background: linear-gradient(135deg, #1e1b4b 0%, #3730a3 60%, #4338ca 100%);
            border-radius: 20px; padding: 24px 28px;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 16px; margin-bottom: 8px;
        }
        .seg-header-text h2 { color: #fff; font-size: 1.3rem; margin: 0 0 6px; font-family: var(--font-display); }
        .seg-header-text p  { color: rgba(255,255,255,.75); font-size: .84rem; margin: 0; }
        .seg-codigo-badge {
            background: rgba(165,180,252,.2); border: 1px solid rgba(165,180,252,.4);
            color: #c7d2fe; padding: 6px 18px; border-radius: 999px;
            font-size: .8rem; font-weight: 700; letter-spacing: .06em;
        }
        .seg-person-selector {
            margin: 18px 24px 14px; padding: 16px 18px; border-radius: 14px;
            border: 1px solid rgba(99,102,241,.28);
            background: linear-gradient(135deg, rgba(99,102,241,.08), rgba(14,165,233,.05));
        }
        .seg-person-selector label { display:block; color:#312e81; font-size:.82rem; font-weight:800; margin-bottom:7px; }
        .seg-person-search { position:relative; }
        .seg-person-search input {
            width:100%; border:1.5px solid rgba(99,102,241,.35); border-radius:11px;
            padding:11px 42px 11px 14px; background:#fff; font-size:.88rem; outline:none;
        }
        .seg-person-search input:focus { border-color:#4f46e5; box-shadow:0 0 0 3px rgba(99,102,241,.14); }
        .seg-person-search > i { position:absolute; right:14px; top:12px; color:#6366f1; pointer-events:none; }
        .seg-person-results {
            display:none; position:absolute; left:0; right:0; top:calc(100% + 6px); z-index:45;
            max-height:310px; overflow-y:auto; background:#fff; border:1px solid rgba(99,102,241,.28);
            border-radius:12px; box-shadow:0 20px 50px rgba(30,41,59,.2);
        }
        .seg-person-results.open { display:block; }
        .seg-person-option { width:100%; border:0; border-bottom:1px solid #e8eaf4; background:#fff; padding:10px 12px; text-align:left; cursor:pointer; }
        .seg-person-option:last-child { border-bottom:0; }
        .seg-person-option:hover, .seg-person-option:focus { background:#eef2ff; outline:none; }
        .seg-person-option strong { display:block; color:#1e1b4b; font-size:.83rem; }
        .seg-person-option small { display:block; color:#64748b; margin-top:2px; }
        .seg-person-status { display:block; margin-top:7px; color:#475569; font-size:.75rem; }

        /* Sección de aviso de confidencialidad */
        .seg-aviso {
            background: linear-gradient(135deg, rgba(99,102,241,.08), rgba(165,180,252,.05));
            border: 1px solid rgba(99,102,241,.2); border-radius: 14px;
            padding: 16px 20px; margin: 0 24px 16px;
            font-size: .84rem; color: var(--ink-600); line-height: 1.6;
        }
        .seg-aviso strong { color: var(--navy-900); }

        /* Datos de encabezado rápido */
        .seg-intro-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;
            margin: 0 24px 20px; padding: 18px 20px;
            background: #f8fbff; border: 1px solid var(--line); border-radius: 14px;
        }
        .seg-intro-field label { font-size: .78rem; font-weight: 700; color: var(--ink-600); display: block; margin-bottom: 4px; }
        .seg-intro-field input { width: 100%; border: 1px solid var(--line); border-radius: 10px; padding: 9px 12px; font-size: .85rem; background: #fff; }
        .seg-intro-field input:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.15); outline: none; }

        /* Secciones internas */
        .seg-section {
            margin: 0 24px 20px;
            border: 1px solid var(--line); border-radius: 16px; overflow: hidden;
        }
        .seg-section-header {
            background: linear-gradient(135deg, #1e1b4b, #3730a3);
            padding: 12px 18px; display: flex; align-items: center; gap: 10px;
            color: #fff; font-size: .82rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
        }
        .seg-section-header i { font-size: 1rem; color: #c7d2fe; }
        .seg-section-body { padding: 18px 20px; background: #fff; }

        /* Grid de campos del formulario */
        .seg-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .seg-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .seg-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .seg-field { display: flex; flex-direction: column; gap: 5px; }
        .seg-field.seg-span-2 { grid-column: span 2; }
        .seg-field.seg-span-3 { grid-column: span 3; }
        .seg-field.seg-span-4 { grid-column: span 4; }
        .seg-field label { font-size: .78rem; font-weight: 600; color: var(--ink-600); }
        .seg-field input, .seg-field select, .seg-field textarea {
            border: 1px solid var(--line); border-radius: 10px;
            padding: 9px 12px; font-size: .84rem; font-family: var(--font-sans);
            background: #fafbfc; outline: none;
            transition: border .2s, box-shadow .2s;
        }
        .seg-field input:focus, .seg-field select:focus, .seg-field textarea:focus {
            border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.15); background: #fff;
        }
        .seg-field textarea { resize: vertical; min-height: 70px; }
        #formEstudioSeguridad input[type="number"] { appearance: textfield; -moz-appearance: textfield; }
        #formEstudioSeguridad input[type="number"]::-webkit-inner-spin-button,
        #formEstudioSeguridad input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        .seg-field-help { color:#64748b; font-size:.7rem; line-height:1.35; }
        .seg-form-error { margin:16px 24px 0; padding:12px 15px; border:1px solid #fecaca; border-radius:11px; background:#fef2f2; color:#991b1b; font-size:.82rem; }
        .btn-seg[disabled] { cursor:wait; opacity:.7; }

        /* Sub-sección dentro de una sección */
        .seg-subsection-title {
            font-size: .76rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .15em; color: #4338ca; margin: 16px 0 10px;
            display: flex; align-items: center; gap: 8px;
        }
        .seg-subsection-title::after { content: ''; flex: 1; height: 1px; background: rgba(99,102,241,.2); }

        /* Tabla de hijos */
        .seg-hijos-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .seg-hijos-table th { background: rgba(99,102,241,.1); color: #3730a3; font-size: .75rem; text-transform: uppercase; letter-spacing: .1em; padding: 8px 10px; border: 1px solid rgba(99,102,241,.2); text-align: center; }
        .seg-hijos-table td { border: 1px solid rgba(99,102,241,.15); padding: 0; }
        .seg-hijos-table td input {
            width: 100%; border: none; background: transparent; padding: 8px 10px;
            font-size: .83rem; font-family: var(--font-sans); outline: none;
        }
        .seg-hijos-table td input:focus { background: rgba(99,102,241,.05); }

        /* Tabla de experiencia laboral */
        .seg-exp-table { width: 100%; border-collapse: collapse; margin-top: 6px; overflow-x: auto; display: block; }
        .seg-exp-table th { background: rgba(99,102,241,.1); color: #3730a3; font-size: .68rem; text-transform: uppercase; padding: 7px 6px; border: 1px solid rgba(99,102,241,.2); white-space: nowrap; }
        .seg-exp-table td { border: 1px solid rgba(99,102,241,.15); padding: 0; }
        .seg-exp-table td input {
            width: 100%; min-width: 80px; border: none; background: transparent;
            padding: 8px 6px; font-size: .8rem; font-family: var(--font-sans); outline: none;
        }
        .seg-exp-table td input:focus { background: rgba(99,102,241,.05); }

        /* Tabla vivienda/vehículo */
        .seg-viv-table { width: 100%; border-collapse: collapse; }
        .seg-viv-table th { background: rgba(99,102,241,.1); color: #3730a3; font-size: .74rem; text-transform: uppercase; padding: 8px 10px; border: 1px solid rgba(99,102,241,.2); text-align: center; }
        .seg-viv-table td { border: 1px solid rgba(99,102,241,.15); padding: 0; }
        .seg-viv-table td input { width: 100%; border: none; background: transparent; padding: 8px 10px; font-size: .83rem; font-family: var(--font-sans); outline: none; }
        .seg-viv-table input[type="checkbox"] { width: auto; margin: auto; display: block; transform: scale(1.2); }

        /* Nota certificación */
        .seg-nota {
            background: #f8f8ff; border: 1px solid rgba(99,102,241,.15);
            border-radius: 12px; padding: 14px 18px; margin: 0 24px 20px;
            font-size: .84rem; color: var(--ink-600); line-height: 1.6;
        }

        /* Pie del formulario */
        .seg-footer {
            margin: 0 24px 24px;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 14px;
            padding-top: 18px; border-top: 1px solid var(--line);
        }

        /* Modal de vista previa */
        .preview-overlay {
            position: fixed; inset: 0; z-index: 1000;
            background: rgba(5,15,30,.65); backdrop-filter: blur(6px);
            display: flex; align-items: flex-start; justify-content: center;
            padding: 20px; overflow-y: auto;
            opacity: 0; pointer-events: none;
            transition: opacity .3s;
        }
        .preview-overlay.open { opacity: 1; pointer-events: auto; }
        .preview-modal {
            background: #fff; border-radius: 20px;
            width: 100%; max-width: 860px;
            box-shadow: 0 30px 80px rgba(0,0,0,.35);
            transform: translateY(30px); transition: transform .3s; overflow: hidden;
        }
        .preview-overlay.open .preview-modal { transform: translateY(0); }
        .preview-modal-header {
            background: linear-gradient(135deg, #1e1b4b, #3730a3);
            padding: 18px 24px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .preview-modal-header h4 { color: #fff; margin: 0; font-size: 1rem; }
        .preview-modal-close {
            width: 36px; height: 36px; border-radius: 10px;
            background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.25);
            color: #fff; cursor: pointer; display: grid; place-items: center;
            font-size: 1.1rem; transition: background .2s;
        }
        .preview-modal-close:hover { background: rgba(255,255,255,.32); }
        .preview-modal-toolbar {
            padding: 12px 24px; background: #f8fbff; border-bottom: 1px solid var(--line);
            display: flex; gap: 10px; flex-wrap: wrap;
        }
        .preview-modal-body { padding: 0; max-height: 75vh; overflow-y: auto; }

        /* Doc A4 */
        .doc-a4 {
            width: 210mm; max-width: 100%; margin: 20px auto; padding: 18mm 20mm;
            background: #fff; box-shadow: 0 2px 20px rgba(0,0,0,.08);
            font-family: 'Times New Roman', serif; font-size: 10pt; color: #111; line-height: 1.4;
        }
        .doc-a4 table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .doc-a4 table td, .doc-a4 table th { border: 1px solid #555; padding: 4px 6px; font-size: 9pt; }
        .doc-a4 table th { background: #e8e8e8; text-align: center; font-weight: bold; }
        .doc-header-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .doc-header-table td { border: 1px solid #333; padding: 6px 8px; vertical-align: top; }
        .doc-logo-cell { width: 80px; text-align: center; }
        .doc-title-cell { text-align: center; font-weight: bold; font-size: 12pt; }
        .doc-code-cell { font-size: 8.5pt; }
        .doc-section-title { font-weight: bold; text-decoration: underline; margin: 12px 0 6px; }

        /* Botones en este formulario */
        .btn-seg {
            border: none; border-radius: 999px; padding: 10px 18px; font-size: .84rem;
            font-weight: 600; cursor: pointer; display: inline-flex; align-items: center;
            gap: 8px; transition: transform .2s, box-shadow .2s;
        }
        .btn-seg--primary {
            background: linear-gradient(135deg, #3730a3, #4f46e5);
            color: #fff; box-shadow: 0 8px 20px rgba(55,48,163,.3);
        }
        .btn-seg--primary:hover { transform: translateY(-1px); }
        .btn-seg--ghost { background: #fff; color: #3730a3; border: 1px solid rgba(55,48,163,.25); }
        .btn-seg--ghost:hover { background: #f0f0ff; }
        .btn-seg--outline-red { background: #fff; color: #ef4444; border: 1px solid rgba(239,68,68,.4); }

        /* Responsive */
        @media (max-width: 768px) {
            .seg-grid { grid-template-columns: repeat(2, 1fr); }
            .seg-field.seg-span-3, .seg-field.seg-span-4 { grid-column: span 2; }
            .seg-intro-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 560px) {
            .seg-grid { grid-template-columns: 1fr; }
            .seg-field.seg-span-2, .seg-field.seg-span-3, .seg-field.seg-span-4 { grid-column: span 1; }
            .seg-grid-2, .seg-grid-3 { grid-template-columns: 1fr; }
        }

        @media print {
            body > *:not(.preview-overlay) { display: none !important; }
            .preview-overlay { position: static; background: none; padding: 0; overflow: visible; opacity: 1 !important; pointer-events: auto !important; }
            .preview-modal { box-shadow: none; transform: none !important; }
            .preview-modal-header, .preview-modal-toolbar, .preview-modal-close { display: none !important; }
            .preview-modal-body { max-height: none; overflow: visible; }
            .doc-a4 { box-shadow: none; margin: 0; width: 100%; padding: 10mm; }
        }
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
            <?php $topbarShowSearch=false;$topbarBackUrl=BASE_URL.'/talento-humano/biblioteca';$topbarBackLabel='Volver a Biblioteca';require ROOT.'/shared/topbar.php'; ?>

            <main class="main">
                <div class="content-shell">

                    <!-- ENCABEZADO -->
                    <div class="seg-header">
                        <div class="seg-header-text">
                            <h2><i class="bi bi-shield-shaded"></i> Formato Estudio de Seguridad Socioeconómico</h2>
                            <p>Código: <?= $codigoFormato ?> &nbsp;|&nbsp; Fecha: <?= $fechaFormato ?> &nbsp;|&nbsp; Información de carácter <strong style="color:#c7d2fe;">confidencial</strong></p>
                        </div>
                        <div>
                            <span class="seg-codigo-badge"><?= $codigoFormato ?></span>
                        </div>
                    </div>

                    <section class="card form-card">

                        <!-- BARRA DE PARTES -->
                        <div class="partes-nav" role="tablist">
                            <button class="parte-btn active" id="parte-btn-1" onclick="switchParte(1)" role="tab" aria-selected="true">
                                <i class="bi bi-person-vcard"></i>
                                Parte 1 – Info General
                                <span class="parte-num">1</span>
                            </button>
                            <button class="parte-btn" id="parte-btn-2" onclick="switchParte(2)" role="tab" aria-selected="false">
                                <i class="bi bi-people-fill"></i>
                                Parte 2 – Familia y Académico
                                <span class="parte-num">2</span>
                            </button>
                            <button class="parte-btn" id="parte-btn-3" onclick="switchParte(3)" role="tab" aria-selected="false">
                                <i class="bi bi-briefcase-fill"></i>
                                Parte 3 – Laboral y Bienes
                                <span class="parte-num">3</span>
                            </button>
                        </div>

                        <!-- Encabezado del formulario (modo creación) -->
                        <div class="card-header form-header" style="background: linear-gradient(135deg,#1e1b4b,#3730a3); color:#fff; border-bottom:none;">
                            <div>
                                <h3><i class="bi bi-shield-shaded"></i> Estudio de Seguridad Socioeconómico</h3>
                                <p>Complete los datos en las 3 partes del formulario. Información confidencial APM.</p>
                            </div>
                            <?php if (!empty($e['estudio_id'])): ?>
                            <a class="btn-seg btn-seg--ghost" style="font-size:.8rem;" target="_blank"
                               href="<?= BASE_URL ?>/talento-humano/estudio-seguridad/imprimir?estudio_id=<?= (int)$e['estudio_id'] ?>">
                                <i class="bi bi-eye"></i> Vista previa PDF oficial
                            </a>
                            <?php else: ?>
                            <button type="button" class="btn-seg btn-seg--ghost" onclick="showToast('Guarde el formulario para generar las 4 páginas oficiales.','info')" style="font-size:.8rem;">
                                <i class="bi bi-eye"></i> Vista previa PDF oficial
                            </button>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>/talento-humano/estudio-seguridad/guardar" id="formEstudioSeguridad">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
                            <input type="hidden" name="estudio_id" value="<?= (int)($e['estudio_id'] ?? 0) ?>">
                            <input type="hidden" name="empleado_id" value="<?= (int)($e['empleado_id'] ?? $e['id'] ?? 0) ?>">

                            <?php if (!empty($errorFormulario)): ?>
                            <div class="seg-form-error" role="alert"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($errorFormulario) ?></div>
                            <?php endif; ?>

                            <div class="seg-person-selector">
                                <label for="busquedaPersonalSocio"><i class="bi bi-person-search"></i> Seleccionar servidor público</label>
                                <div class="seg-person-search">
                                    <input type="search" id="busquedaPersonalSocio" autocomplete="off"
                                           placeholder="Escriba cédula, nombres o apellidos..."
                                           value="<?= !empty($e['id']) ? htmlspecialchars(trim(($e['cedula'] ?? '').' · '.($e['apellidos'] ?? '').' '.($e['nombres'] ?? ''))) : '' ?>"
                                           aria-autocomplete="list" aria-controls="resultadosPersonalSocio">
                                    <i class="bi bi-search"></i>
                                    <div id="resultadosPersonalSocio" class="seg-person-results" role="listbox"></div>
                                </div>
                                <span class="seg-person-status" id="estadoPersonalSocio">
                                    <?php if (!empty($e['id'])): ?>
                                        <i class="bi bi-check-circle-fill" style="color:#059669"></i> Expediente seleccionado y campos institucionales precargados.
                                    <?php else: ?>
                                        <i class="bi bi-info-circle"></i> Seleccione primero a la persona registrada en el directorio.
                                    <?php endif; ?>
                                </span>
                            </div>

                            <!-- Aviso de confidencialidad -->
                            <div class="seg-aviso">
                                <i class="bi bi-info-circle-fill" style="color:#6366f1;"></i>
                                <strong>Aviso de Confidencialidad:</strong> Autoridad Portuaria de Manta requiere la siguiente información para el estudio de seguridad de la institución. Estos datos serán clasificados como información <strong>confidencial</strong> y podrán ser presentados ante las autoridades de control cuando así lo requieran. La información registrada será confirmada por servidores de la entidad.
                            </div>

                            <!-- Datos de cabecera -->
                            <div class="seg-intro-grid">
                                <div class="seg-intro-field">
                                    <label for="fecha_vinculacion">Fecha de Vinculación</label>
                                    <input type="date" id="fecha_vinculacion" name="fecha_vinculacion" value="<?= htmlspecialchars($e['fecha_vinculacion'] ?? $e['fecha_ingreso'] ?? '') ?>">
                                </div>
                                <div class="seg-intro-field">
                                    <label for="cargo_cabecera">Cargo</label>
                                    <input type="text" id="cargo_cabecera" name="cargo_cabecera" placeholder="Cargo del funcionario" value="<?= htmlspecialchars($e['cargo_cabecera'] ?? $e['cargo'] ?? '') ?>">
                                </div>
                                <div class="seg-intro-field">
                                    <label for="nombre_cabecera">Nombre Completo</label>
                                    <input type="text" id="nombre_cabecera" name="nombre_cabecera" placeholder="Nombres y Apellidos" value="<?= htmlspecialchars($e['nombre_cabecera'] ?? trim(($e['nombres'] ?? '').' '.($e['apellidos'] ?? ''))) ?>">
                                </div>
                            </div>

                            <!-- ════════════════════════════════════════════════
                                 PARTE 1 – INFORMACIÓN GENERAL DEL SERVIDOR
                            ════════════════════════════════════════════════ -->
                            <div class="parte-panel active" id="parte-panel-1" role="tabpanel">

                                <!-- Información del Servidor -->
                                <div class="seg-section">
                                    <div class="seg-section-header">
                                        <i class="bi bi-person-badge-fill"></i>
                                        I. Información General – Información del Servidor
                                    </div>
                                    <div class="seg-section-body">
                                        <div class="seg-grid">
                                            <div class="seg-field">
                                                <label for="tipo_doc_ident">Tipo de Documento Ident.</label>
                                                <select id="tipo_doc_ident" name="tipo_doc_ident">
                                                    <option value="">Seleccione...</option>
                                                    <option value="CEDULA">Cédula</option>
                                                    <option value="PASAPORTE">Pasaporte</option>
                                                    <option value="OTRO">Otro</option>
                                                </select>
                                            </div>
                                            <div class="seg-field">
                                                <label for="nro_documento">Nº de Documento</label>
                                                <input type="text" id="nro_documento" name="nro_documento" placeholder="Ej: 1312344567" value="<?= htmlspecialchars($e['cedula'] ?? '') ?>">
                                            </div>
                                            <div class="seg-field">
                                                <label for="nacionalidad">Nacionalidad</label>
                                                <input type="text" id="nacionalidad" name="nacionalidad" placeholder="Ej: Ecuatoriana">
                                            </div>
                                            <div class="seg-field">
                                                <label for="anios_residencia">Años de Residencia (Extranjeros)</label>
                                                <input type="number" id="anios_residencia" name="anios_residencia" placeholder="-" min="0">
                                            </div>
                                            <div class="seg-field">
                                                <label for="libreta_militar">Libreta Militar (SI/NO)</label>
                                                <select id="libreta_militar" name="libreta_militar">
                                                    <option value="">Seleccione...</option>
                                                    <option value="SI">Sí</option>
                                                    <option value="NO">No</option>
                                                </select>
                                            </div>
                                            <div class="seg-field">
                                                <label for="nro_libreta_militar">Nº Libreta Militar</label>
                                                <input type="text" id="nro_libreta_militar" name="nro_libreta_militar" placeholder="-">
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="tipo_relacion">Relación (Servidor / Pasante / Convenio)</label>
                                                <select id="tipo_relacion" name="tipo_relacion">
                                                    <option value="">Seleccione...</option>
                                                    <option value="SERVIDOR">Servidor</option>
                                                    <option value="PASANTE">Pasante</option>
                                                    <option value="CONVENIO">Convenio</option>
                                                </select>
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="apellidos">Apellidos</label>
                                                <input type="text" id="apellidos" name="apellidos" placeholder="Ej: MARRASQUÍN MALDONADO">
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="nombres">Nombres</label>
                                                <input type="text" id="nombres" name="nombres" placeholder="Ej: MARÍA GABRIELA">
                                            </div>
                                            <div class="seg-field">
                                                <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                                                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= htmlspecialchars($e['fecha_nac'] ?? '') ?>">
                                            </div>
                                            <div class="seg-field">
                                                <label for="edad">Edad</label>
                                                <input type="text" id="edad" name="edad" placeholder="Ej: 32 años" readonly style="background:#f0f0f7;">
                                            </div>
                                            <div class="seg-field">
                                                <label for="lugar_nacimiento">Lugar de Nacimiento</label>
                                                <input type="text" id="lugar_nacimiento" name="lugar_nacimiento" placeholder="Ej: Manta">
                                            </div>
                                            <div class="seg-field">
                                                <label for="provincia_ciudad_nac">Provincia - Ciudad Nac.</label>
                                                <input type="text" id="provincia_ciudad_nac" name="provincia_ciudad_nac" placeholder="Ej: Manabí - Manta">
                                            </div>
                                            <div class="seg-field">
                                                <label for="genero">Género</label>
                                                <select id="genero" name="genero">
                                                    <option value="">Seleccione...</option>
                                                    <option value="MASCULINO">Masculino</option>
                                                    <option value="FEMENINO">Femenino</option>
                                                    <option value="OTRO">Otro</option>
                                                </select>
                                            </div>
                                            <div class="seg-field">
                                                <label for="tipo_sangre">Tipo de Sangre</label>
                                                <select id="tipo_sangre" name="tipo_sangre">
                                                    <option value="">Seleccione...</option>
                                                    <?php foreach (['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $s): ?>
                                                        <option value="<?= $s ?>"><?= $s ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="seg-field">
                                                <label for="estado_civil">Estado Civil</label>
                                                <select id="estado_civil" name="estado_civil">
                                                    <option value="">Seleccione...</option>
                                                    <option value="Soltero/a">Soltero/a</option>
                                                    <option value="Casado/a">Casado/a</option>
                                                    <option value="Divorciado/a">Divorciado/a</option>
                                                    <option value="Union Libre">Unión Libre</option>
                                                    <option value="Viudo/a">Viudo/a</option>
                                                </select>
                                            </div>
                                            <div class="seg-field">
                                                <label for="discapacidad">Discapacidad</label>
                                                <select id="discapacidad" name="discapacidad" onchange="toggleDiscapacidad()">
                                                    <option value="NO">No</option>
                                                    <option value="SI">Sí</option>
                                                </select>
                                            </div>
                                            <div class="seg-field" id="seg-tipo-discapacidad" style="display:none;">
                                                <label for="tipo_discapacidad">Tipo de Discapacidad</label>
                                                <input type="text" id="tipo_discapacidad" name="tipo_discapacidad" placeholder="-">
                                            </div>
                                            <div class="seg-field" id="seg-nro-conadis" style="display:none;">
                                                <label for="nro_carnet_conadis">Nº Carnet CONADIS</label>
                                                <input type="text" id="nro_carnet_conadis" name="nro_carnet_conadis" placeholder="-">
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="servidor_carrera">Servidor Carrera</label>
                                                <input type="text" id="servidor_carrera" name="servidor_carrera" placeholder="-">
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="nro_servidor_carrera">Nº</label>
                                                <input type="text" id="nro_servidor_carrera" name="nro_servidor_carrera" placeholder="-">
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="auto_identificacion">Auto Identificación Étnica</label>
                                                <select id="auto_identificacion" name="auto_identificacion">
                                                    <option value="">Seleccione...</option>
                                                    <option value="MESTIZO">Mestizo</option>
                                                    <option value="INDIGENA">Indígena</option>
                                                    <option value="AFROECUATORIANO">Afroecuatoriano</option>
                                                    <option value="MONTUBIO">Montubio</option>
                                                    <option value="BLANCO">Blanco</option>
                                                    <option value="OTRO">Otro</option>
                                                </select>
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="nacionalidad_indigena">Nacionalidad Indígena</label>
                                                <input type="text" id="nacionalidad_indigena" name="nacionalidad_indigena" placeholder="-">
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="dir_calle_principal">Dirección Calle Principal</label>
                                                <input type="text" id="dir_calle_principal" name="dir_calle_principal" placeholder="Ej: Avenida 114">
                                            </div>
                                            <div class="seg-field">
                                                <label for="numero_domicilio">Número</label>
                                                <input type="text" id="numero_domicilio" name="numero_domicilio" placeholder="S/N">
                                            </div>
                                            <div class="seg-field">
                                                <label for="calle_secundaria">Calle Secundaria</label>
                                                <input type="text" id="calle_secundaria" name="calle_secundaria" placeholder="Ej: Calle F7">
                                            </div>
                                            <div class="seg-field">
                                                <label for="parroquia">Parroquia</label>
                                                <input type="text" id="parroquia" name="parroquia" placeholder="Ej: Tarqui">
                                            </div>
                                            <div class="seg-field">
                                                <label for="canton">Cantón</label>
                                                <input type="text" id="canton" name="canton" placeholder="Ej: Manta">
                                            </div>
                                            <div class="seg-field">
                                                <label for="provincia_dom">Provincia</label>
                                                <input type="text" id="provincia_dom" name="provincia_dom" placeholder="Ej: Manabí">
                                            </div>
                                            <div class="seg-field seg-span-4">
                                                <label for="referencia_domiciliaria">Referencia Domiciliaria</label>
                                                <input type="text" id="referencia_domiciliaria" name="referencia_domiciliaria" placeholder="Ej: Cerca de la escuela...">
                                            </div>
                                            <div class="seg-field">
                                                <label for="tel_domicilio">Teléfono Domicilio</label>
                                                <input type="tel" id="tel_domicilio" name="tel_domicilio" placeholder="05XXXXXXX">
                                            </div>
                                            <div class="seg-field">
                                                <label for="tel_celular">Teléfono Celular</label>
                                                <input type="tel" id="tel_celular" name="tel_celular" placeholder="09XXXXXXXX">
                                            </div>
                                            <div class="seg-field">
                                                <label for="tel_trabajo">Teléfono Trabajo</label>
                                                <input type="tel" id="tel_trabajo" name="tel_trabajo" placeholder="(05)XXXXXXX">
                                            </div>
                                            <div class="seg-field">
                                                <label for="extension">Número de Extensión</label>
                                                <input type="text" id="extension" name="extension" placeholder="Ej: 1206">
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="correo_institucional">Correo Electrónico</label>
                                                <input type="email" id="correo_institucional" name="correo_institucional" placeholder="usuario@puertodemanta.gob.ec">
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="correo_alternativo">Correo Electrónico Alternativo</label>
                                                <input type="email" id="correo_alternativo" name="correo_alternativo" placeholder="correo@gmail.com">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Datos de Contacto -->
                                <div class="seg-section">
                                    <div class="seg-section-header">
                                        <i class="bi bi-telephone-fill"></i>
                                        Datos de Contacto
                                    </div>
                                    <div class="seg-section-body">
                                        <div class="seg-grid">
                                            <div class="seg-field seg-span-2">
                                                <label for="contacto_nombre">Nombres y Apellidos</label>
                                                <input type="text" id="contacto_nombre" name="contacto_nombre" placeholder="Nombre del contacto">
                                                <small class="seg-field-help">Se precarga desde el expediente; puede cambiarlo solo para este estudio.</small>
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="contacto_parentesco">Parentesco con el Servidor</label>
                                                <input type="text" id="contacto_parentesco" name="contacto_parentesco" placeholder="Ej: Madre, Padre, Hermano/a">
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="contacto_tel_conv">Teléfono Convencional</label>
                                                <input type="tel" id="contacto_tel_conv" name="contacto_tel_conv" placeholder="S/N">
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="contacto_tel_cel">Teléfono Celular</label>
                                                <input type="tel" id="contacto_tel_cel" name="contacto_tel_cel" placeholder="09XXXXXXXX">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Declaración de Bienes -->
                                <div class="seg-section">
                                    <div class="seg-section-header">
                                        <i class="bi bi-file-earmark-check-fill"></i>
                                        Declaración de Bienes
                                    </div>
                                    <div class="seg-section-body">
                                        <div class="seg-grid-2">
                                            <div class="seg-field">
                                                <label for="nro_otorgamiento">Nº de Otorgamiento</label>
                                                <input type="text" id="nro_otorgamiento" name="nro_otorgamiento" placeholder="Ej: 9703123">
                                            </div>
                                            <div class="seg-field">
                                                <label for="fecha_ingreso_bienes">Fecha de Ingreso</label>
                                                <input type="date" id="fecha_ingreso_bienes" name="fecha_ingreso_bienes">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Navegación -->
                                <div style="margin:0 24px 24px;display:flex;justify-content:flex-end;">
                                    <button type="button" class="btn-seg btn-seg--primary" onclick="switchParte(2)">
                                        Parte 2: Familia y Académico <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>
                            </div><!-- /parte-panel-1 -->

                            <!-- ════════════════════════════════════════════════
                                 PARTE 2 – FAMILIA / ACADÉMICO / CAPACITACIONES
                            ════════════════════════════════════════════════ -->
                            <div class="parte-panel" id="parte-panel-2" role="tabpanel">

                                <!-- Información Bancaria -->
                                <div class="seg-section">
                                    <div class="seg-section-header">
                                        <i class="bi bi-bank"></i>
                                        Información Bancaria
                                    </div>
                                    <div class="seg-section-body">
                                        <div class="seg-grid-3">
                                            <div class="seg-field">
                                                <label for="banco">Institución Bancaria</label>
                                                <input type="text" id="banco" name="banco" placeholder="Ej: Banco del Pichincha">
                                            </div>
                                            <div class="seg-field">
                                                <label for="tipo_cuenta">Tipo de Cuenta</label>
                                                <select id="tipo_cuenta" name="tipo_cuenta">
                                                    <option value="">Seleccione...</option>
                                                    <option value="AHORRO">Ahorro</option>
                                                    <option value="CORRIENTE">Corriente</option>
                                                </select>
                                            </div>
                                            <div class="seg-field">
                                                <label for="nro_cuenta">Nº de Cuenta</label>
                                                <input type="text" id="nro_cuenta" name="nro_cuenta" placeholder="Ej: 2205074061">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Grupo Familiar - Cónyuge -->
                                <div class="seg-section">
                                    <div class="seg-section-header">
                                        <i class="bi bi-people-fill"></i>
                                        II. Grupo Familiar
                                    </div>
                                    <div class="seg-section-body">
                                        <div class="seg-subsection-title">
                                            <i class="bi bi-person-heart"></i> Información del Cónyuge
                                        </div>
                                        <div class="seg-grid">
                                            <div class="seg-field seg-span-2">
                                                <label for="conyuge_nombres">Nombres y Apellidos</label>
                                                <input type="text" id="conyuge_nombres" name="conyuge_nombres" placeholder="-">
                                            </div>
                                            <div class="seg-field">
                                                <label for="conyuge_tipo_doc">Tipo de Documento Ident.</label>
                                                <select id="conyuge_tipo_doc" name="conyuge_tipo_doc">
                                                    <option value="">-</option>
                                                    <option value="CEDULA">Cédula</option>
                                                    <option value="PASAPORTE">Pasaporte</option>
                                                </select>
                                            </div>
                                            <div class="seg-field">
                                                <label for="conyuge_nro_doc">Nº de Documento</label>
                                                <input type="text" id="conyuge_nro_doc" name="conyuge_nro_doc" placeholder="-">
                                            </div>
                                            <div class="seg-field">
                                                <label for="conyuge_fecha_nac">Fecha de Nacimiento</label>
                                                <input type="date" id="conyuge_fecha_nac" name="conyuge_fecha_nac">
                                            </div>
                                            <div class="seg-field">
                                                <label for="conyuge_tipo_relacion">Tipo de Relación</label>
                                                <select id="conyuge_tipo_relacion" name="conyuge_tipo_relacion">
                                                    <option value="">Seleccione...</option>
                                                    <option value="CASADO">Casado/a</option>
                                                    <option value="UNION_LIBRE">Unión Libre</option>
                                                </select>
                                            </div>
                                            <div class="seg-field">
                                                <label for="conyuge_nivel_instruccion">Nivel de Instrucción</label>
                                                <select id="conyuge_nivel_instruccion" name="conyuge_nivel_instruccion">
                                                    <option value="">-</option>
                                                    <option value="PRIMARIA">Primaria</option>
                                                    <option value="SECUNDARIA">Secundaria</option>
                                                    <option value="TERCER NIVEL">Tercer Nivel</option>
                                                    <option value="POSGRADO">Posgrado</option>
                                                </select>
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="conyuge_ocupacion">Ocupación</label>
                                                <input type="text" id="conyuge_ocupacion" name="conyuge_ocupacion" placeholder="-">
                                            </div>
                                        </div>

                                        <div class="seg-subsection-title" style="margin-top:20px;">
                                            <i class="bi bi-people"></i> Información de Hijos (de menor a mayor)
                                        </div>
                                        <div style="overflow-x:auto;">
                                            <table class="seg-hijos-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width:25%;">Campo</th>
                                                        <th>Hijo 1</th>
                                                        <th>Hijo 2</th>
                                                        <th>Hijo 3</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td style="padding:8px;font-size:.78rem;font-weight:600;background:#f8f8ff;">Nombres y Apellidos</td>
                                                        <td><input type="text" name="hijo_nombre_1" placeholder="-"></td>
                                                        <td><input type="text" name="hijo_nombre_2" placeholder="-"></td>
                                                        <td><input type="text" name="hijo_nombre_3" placeholder="-"></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:8px;font-size:.78rem;font-weight:600;background:#f8f8ff;">Fecha de Nacimiento</td>
                                                        <td><input type="date" name="hijo_fnac_1"></td>
                                                        <td><input type="date" name="hijo_fnac_2"></td>
                                                        <td><input type="date" name="hijo_fnac_3"></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:8px;font-size:.78rem;font-weight:600;background:#f8f8ff;">Tipo de Documento</td>
                                                        <td><input type="text" name="hijo_tipo_doc_1" placeholder="Cédula"></td>
                                                        <td><input type="text" name="hijo_tipo_doc_2" placeholder="Cédula"></td>
                                                        <td><input type="text" name="hijo_tipo_doc_3" placeholder="Cédula"></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:8px;font-size:.78rem;font-weight:600;background:#f8f8ff;">Número de Documento</td>
                                                        <td><input type="text" name="hijo_nro_doc_1" placeholder="-"></td>
                                                        <td><input type="text" name="hijo_nro_doc_2" placeholder="-"></td>
                                                        <td><input type="text" name="hijo_nro_doc_3" placeholder="-"></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:8px;font-size:.78rem;font-weight:600;background:#f8f8ff;">Edad</td>
                                                        <td><input type="text" name="hijo_edad_1" placeholder="-"></td>
                                                        <td><input type="text" name="hijo_edad_2" placeholder="-"></td>
                                                        <td><input type="text" name="hijo_edad_3" placeholder="-"></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:8px;font-size:.78rem;font-weight:600;background:#f8f8ff;">Nivel de Instrucción</td>
                                                        <td><input type="text" name="hijo_instruccion_1" placeholder="-"></td>
                                                        <td><input type="text" name="hijo_instruccion_2" placeholder="-"></td>
                                                        <td><input type="text" name="hijo_instruccion_3" placeholder="-"></td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding:8px;font-size:.78rem;font-weight:600;background:#f8f8ff;">Ocupación</td>
                                                        <td><input type="text" name="hijo_ocupacion_1" placeholder="-"></td>
                                                        <td><input type="text" name="hijo_ocupacion_2" placeholder="-"></td>
                                                        <td><input type="text" name="hijo_ocupacion_3" placeholder="-"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información Académica -->
                                <div class="seg-section">
                                    <div class="seg-section-header">
                                        <i class="bi bi-mortarboard-fill"></i>
                                        III. Información Académica
                                    </div>
                                    <div class="seg-section-body">
                                        <div class="seg-subsection-title">
                                            <i class="bi bi-book"></i> Instrucción
                                        </div>
                                        <div class="seg-grid">
                                            <div class="seg-field seg-span-2">
                                                <label for="nivel_instruccion">Nivel de Instrucción</label>
                                                <select id="nivel_instruccion" name="nivel_instruccion">
                                                    <option value="">Seleccione...</option>
                                                    <option value="PRIMARIA">Primaria</option>
                                                    <option value="SECUNDARIA">Secundaria</option>
                                                    <option value="TERCER NIVEL">Tercer Nivel</option>
                                                    <option value="POSGRADO">Posgrado</option>
                                                </select>
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="institucion_educativa">Institución Educativa</label>
                                                <input type="text" id="institucion_educativa" name="institucion_educativa" placeholder="Ej: Universidad Laica Eloy Alfaro de Manabí">
                                            </div>
                                            <div class="seg-field">
                                                <label for="tipo_periodo">Tipo de Período</label>
                                                <select id="tipo_periodo" name="tipo_periodo">
                                                    <option value="">-</option>
                                                    <option value="SEMESTRAL">Semestral</option>
                                                    <option value="ANUAL">Anual</option>
                                                    <option value="TRIMESTRAL">Trimestral</option>
                                                </select>
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="area_conocimiento">Área de Conocimiento</label>
                                                <input type="text" id="area_conocimiento" name="area_conocimiento" placeholder="Ej: Negocios Internacionales">
                                            </div>
                                            <div class="seg-field">
                                                <label for="egresado">Egresado (SI/NO)</label>
                                                <select id="egresado" name="egresado">
                                                    <option value="NO">No</option>
                                                    <option value="SI">Sí</option>
                                                </select>
                                            </div>
                                            <div class="seg-field seg-span-4">
                                                <label for="titulo_academico">Título</label>
                                                <input type="text" id="titulo_academico" name="titulo_academico" placeholder="Ej: Ingeniera en Comercio Exterior y Negocios Internacionales">
                                            </div>
                                        </div>

                                        <div class="seg-subsection-title" style="margin-top:20px;">
                                            <i class="bi bi-award"></i> Información sobre Capacitaciones
                                        </div>
                                        <div class="seg-grid" style="margin-bottom:16px;">
                                            <div class="seg-field seg-span-4">
                                                <label for="cap1_evento">Evento</label>
                                                <input type="text" id="cap1_evento" name="cap1_evento" placeholder="Ej: Curso virtual: Ética, Integridad y Transparencia en la Gestión Pública">
                                            </div>
                                            <div class="seg-field">
                                                <label for="cap1_tipo">Tipo de Evento/Capacit.</label>
                                                <select id="cap1_tipo" name="cap1_tipo">
                                                    <option value="">-</option>
                                                    <option value="VIRTUAL">Virtual</option>
                                                    <option value="PRESENCIAL">Presencial</option>
                                                    <option value="SEMIPRESENCIAL">Semipresencial</option>
                                                </select>
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="cap1_auspiciante">Auspiciante</label>
                                                <input type="text" id="cap1_auspiciante" name="cap1_auspiciante" placeholder="Ej: P.N.U.D. Ecuador">
                                            </div>
                                            <div class="seg-field">
                                                <label for="cap1_tipo_cert">Tipo de Certificado</label>
                                                <select id="cap1_tipo_cert" name="cap1_tipo_cert">
                                                    <option value="">-</option>
                                                    <option value="DIGITAL">Digital</option>
                                                    <option value="FISICO">Físico</option>
                                                    <option value="AMBOS">Ambos</option>
                                                </select>
                                            </div>
                                            <div class="seg-field seg-span-3">
                                                <label for="cap1_certificado_por">Certificado Por</label>
                                                <input type="text" id="cap1_certificado_por" name="cap1_certificado_por" placeholder="Ej: Secretaría de Política Pública Anticorrupción">
                                            </div>
                                            <div class="seg-field">
                                                <label for="cap1_fecha_inicio">Fecha de Inicio</label>
                                                <input type="date" id="cap1_fecha_inicio" name="cap1_fecha_inicio">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div style="margin:0 24px 24px;display:flex;justify-content:space-between;">
                                    <button type="button" class="btn-seg btn-seg--ghost" onclick="switchParte(1)">
                                        <i class="bi bi-arrow-left"></i> Parte 1: Info General
                                    </button>
                                    <button type="button" class="btn-seg btn-seg--primary" onclick="switchParte(3)">
                                        Parte 3: Laboral y Bienes <i class="bi bi-arrow-right"></i>
                                    </button>
                                </div>
                            </div><!-- /parte-panel-2 -->

                            <!-- ════════════════════════════════════════════════
                                 PARTE 3 – CAPACITACIONES ADICIONALES / LABORAL
                            ════════════════════════════════════════════════ -->
                            <div class="parte-panel" id="parte-panel-3" role="tabpanel">

                                <!-- Más capacitaciones -->
                                <div class="seg-section">
                                    <div class="seg-section-header">
                                        <i class="bi bi-award-fill"></i>
                                        Información sobre Capacitaciones (continuación)
                                    </div>
                                    <div class="seg-section-body">
                                        <div class="seg-grid" style="margin-bottom:16px;">
                                            <div class="seg-field seg-span-4">
                                                <label for="cap2_evento">Evento 2</label>
                                                <input type="text" id="cap2_evento" name="cap2_evento" placeholder="Ej: Operador del Sistema Nacional de Contratación Pública">
                                            </div>
                                            <div class="seg-field">
                                                <label for="cap2_tipo">Tipo de Evento/Capacit.</label>
                                                <select id="cap2_tipo" name="cap2_tipo">
                                                    <option value="">-</option>
                                                    <option value="VIRTUAL">Virtual</option>
                                                    <option value="PRESENCIAL">Presencial</option>
                                                    <option value="SEMIPRESENCIAL">Semipresencial</option>
                                                </select>
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="cap2_auspiciante">Auspiciante</label>
                                                <input type="text" id="cap2_auspiciante" name="cap2_auspiciante" placeholder="Ej: Servicio Nacional de Contratación Pública">
                                            </div>
                                            <div class="seg-field">
                                                <label for="cap2_tipo_cert">Tipo de Certificado</label>
                                                <select id="cap2_tipo_cert" name="cap2_tipo_cert">
                                                    <option value="">-</option>
                                                    <option value="DIGITAL">Digital</option>
                                                    <option value="FISICO">Físico</option>
                                                </select>
                                            </div>
                                            <div class="seg-field seg-span-3">
                                                <label for="cap2_certificado_por">Certificado Por</label>
                                                <input type="text" id="cap2_certificado_por" name="cap2_certificado_por" placeholder="">
                                            </div>
                                            <div class="seg-field">
                                                <label for="cap2_fecha_inicio">Fecha de Inicio</label>
                                                <input type="date" id="cap2_fecha_inicio" name="cap2_fecha_inicio">
                                            </div>
                                        </div>
                                        <div class="seg-subsection-title"><i class="bi bi-award"></i> Capacitación 3</div>
                                        <div class="seg-grid">
                                            <div class="seg-field seg-span-4">
                                                <label for="cap3_evento">Evento 3</label>
                                                <input type="text" id="cap3_evento" name="cap3_evento" placeholder="Nombre del evento o capacitación">
                                            </div>
                                            <div class="seg-field">
                                                <label for="cap3_tipo">Tipo de Evento/Capacit.</label>
                                                <select id="cap3_tipo" name="cap3_tipo">
                                                    <option value="">-</option><option value="VIRTUAL">Virtual</option><option value="PRESENCIAL">Presencial</option><option value="SEMIPRESENCIAL">Semipresencial</option>
                                                </select>
                                            </div>
                                            <div class="seg-field seg-span-2">
                                                <label for="cap3_auspiciante">Auspiciante</label>
                                                <input type="text" id="cap3_auspiciante" name="cap3_auspiciante">
                                            </div>
                                            <div class="seg-field">
                                                <label for="cap3_tipo_cert">Tipo de Certificado</label>
                                                <select id="cap3_tipo_cert" name="cap3_tipo_cert"><option value="">-</option><option value="DIGITAL">Digital</option><option value="FISICO">Físico</option></select>
                                            </div>
                                            <div class="seg-field seg-span-3">
                                                <label for="cap3_certificado_por">Certificado Por</label>
                                                <input type="text" id="cap3_certificado_por" name="cap3_certificado_por">
                                            </div>
                                            <div class="seg-field">
                                                <label for="cap3_fecha_inicio">Fecha de Inicio</label>
                                                <input type="date" id="cap3_fecha_inicio" name="cap3_fecha_inicio">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Experiencia Laboral -->
                                <div class="seg-section">
                                    <div class="seg-section-header">
                                        <i class="bi bi-briefcase-fill"></i>
                                        IV. Experiencia Laboral (3 últimos empleos si los hubiere tenido)
                                    </div>
                                    <div class="seg-section-body">
                                        <div style="overflow-x:auto;">
                                            <table class="seg-exp-table">
                                                <thead>
                                                    <tr>
                                                        <th>Nombre de Institución</th>
                                                        <th>Tipo de Instit.</th>
                                                        <th>Unidad Administ.</th>
                                                        <th>Cargo</th>
                                                        <th>Antigüedad</th>
                                                        <th>Jefe Inmediato</th>
                                                        <th>Teléfono</th>
                                                        <th>Fecha Ingreso</th>
                                                        <th>Motivo Ingreso</th>
                                                        <th>Fecha Retiro</th>
                                                        <th>Motivo Retiro</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php for ($i = 1; $i <= 3; $i++): ?>
                                                    <tr>
                                                        <td><input type="text" name="exp_institucion_<?= $i ?>" placeholder="-"></td>
                                                        <td>
                                                            <select name="exp_tipo_<?= $i ?>" style="min-width:80px;">
                                                                <option value="">-</option>
                                                                <option value="PUBLICA">Pública</option>
                                                                <option value="PRIVADA">Privada</option>
                                                                <option value="PUBLICO-PRIVADA">Público-Privada</option>
                                                            </select>
                                                        </td>
                                                        <td><input type="text" name="exp_unidad_<?= $i ?>" placeholder="-"></td>
                                                        <td><input type="text" name="exp_cargo_<?= $i ?>" placeholder="-"></td>
                                                        <td><input type="text" name="exp_antiguedad_<?= $i ?>" placeholder="-"></td>
                                                        <td><input type="text" name="exp_jefe_<?= $i ?>" placeholder="-"></td>
                                                        <td><input type="tel" name="exp_tel_<?= $i ?>" placeholder="-"></td>
                                                        <td><input type="date" name="exp_fecha_ingreso_<?= $i ?>"></td>
                                                        <td><input type="text" name="exp_motivo_ingreso_<?= $i ?>" placeholder="-"></td>
                                                        <td><input type="date" name="exp_fecha_retiro_<?= $i ?>"></td>
                                                        <td><input type="text" name="exp_motivo_retiro_<?= $i ?>" placeholder="-"></td>
                                                    </tr>
                                                    <?php endfor; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vivienda y Vehículo -->
                                <div class="seg-section">
                                    <div class="seg-section-header">
                                        <i class="bi bi-house-fill"></i>
                                        Vivienda y Vehículo
                                    </div>
                                    <div class="seg-section-body">
                                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;flex-wrap:wrap;">
                                            <div>
                                                <p style="font-size:.8rem;font-weight:700;color:var(--navy-900);margin:0 0 10px;">VIVIENDA</p>
                                                <div style="display:flex;gap:20px;">
                                                    <label style="display:flex;align-items:center;gap:8px;font-size:.85rem;cursor:pointer;">
                                                        <input type="radio" name="vivienda_tipo" value="PROPIA" style="width:auto;"> Propia
                                                    </label>
                                                    <label style="display:flex;align-items:center;gap:8px;font-size:.85rem;cursor:pointer;">
                                                        <input type="radio" name="vivienda_tipo" value="ARRENDADA" style="width:auto;"> Arrendada
                                                    </label>
                                                    <label style="display:flex;align-items:center;gap:8px;font-size:.85rem;cursor:pointer;">
                                                        <input type="radio" name="vivienda_tipo" value="OTROS" style="width:auto;"> Otros
                                                    </label>
                                                </div>
                                            </div>
                                            <div>
                                                <p style="font-size:.8rem;font-weight:700;color:var(--navy-900);margin:0 0 10px;">VEHÍCULO</p>
                                                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;">
                                                    <div class="seg-field">
                                                        <label for="vehiculo_marca">Marca</label>
                                                        <input type="text" id="vehiculo_marca" name="vehiculo_marca" placeholder="-">
                                                    </div>
                                                    <div class="seg-field">
                                                        <label for="vehiculo_modelo">Modelo</label>
                                                        <input type="text" id="vehiculo_modelo" name="vehiculo_modelo" placeholder="-">
                                                    </div>
                                                    <div class="seg-field">
                                                        <label for="vehiculo_placa">Placa</label>
                                                        <input type="text" id="vehiculo_placa" name="vehiculo_placa" placeholder="-">
                                                    </div>
                                                    <div class="seg-field">
                                                        <label for="vehiculo_valor">Valor ($)</label>
                                                        <input type="number" id="vehiculo_valor" name="vehiculo_valor" placeholder="0.00" step="0.01">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nota de certificación -->
                                <div class="seg-nota">
                                    <i class="bi bi-info-circle-fill" style="color:#6366f1;"></i>
                                    <strong>Nota:</strong> Certifico que la información aquí suministrada es verdadera y podrá ser verificada en cualquier momento por la institución. Así mismo estoy dispuesto a brindar una ampliación de cualquier aspecto de los datos registrados.
                                </div>

                                <!-- FOOTER DEL FORMULARIO -->
                                <div class="seg-footer">
                                    <div style="font-size:.82rem;color:var(--ink-600);display:flex;align-items:center;gap:8px;">
                                        <i class="bi bi-shield-lock-fill" style="color:#6366f1;"></i>
                                        Código: <?= $codigoFormato ?> | Fecha: <?= $fechaFormato ?>
                                    </div>
                                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                        <button type="button" class="btn-seg btn-seg--ghost" onclick="switchParte(2)">
                                            <i class="bi bi-arrow-left"></i> Parte 2
                                        </button>
                                        <?php if (empty($e['estudio_id'])): ?>
                                        <button type="button" class="btn-seg btn-seg--ghost" onclick="showToast('Guarde el formulario para generar las 4 páginas oficiales.','info')">
                                            <i class="bi bi-eye"></i> Vista previa oficial
                                        </button>
                                        <?php endif; ?>
                                        <?php if (!empty($e['estudio_id'])): ?>
                                        <a class="btn-seg btn-seg--outline-red" target="_blank"
                                           href="<?= BASE_URL ?>/talento-humano/estudio-seguridad/imprimir?estudio_id=<?= (int)$e['estudio_id'] ?>">
                                            <i class="bi bi-file-earmark-pdf-fill"></i> PDF oficial (4 páginas)
                                        </a>
                                        <?php endif; ?>
                                        <button type="submit" class="btn-seg btn-seg--primary" id="btnGuardarEstudio">
                                            <i class="bi bi-save"></i> Guardar formulario
                                        </button>
                                    </div>
                                </div>

                            </div><!-- /parte-panel-3 -->

                        </form>
                    </section>
                </div><!-- /content-shell -->
            </main>
        </section>
    </div>


    <!-- ══ MODAL DE VISTA PREVIA ═══════════════════════════════════════════ -->
    <div class="preview-overlay" id="preview-seg-modal" role="dialog" aria-modal="true">
        <div class="preview-modal">
            <div class="preview-modal-header">
                <h4><i class="bi bi-shield-shaded"></i> Vista Previa — <?= $codigoFormato ?></h4>
                <button class="preview-modal-close" onclick="cerrarPreview()" aria-label="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="preview-modal-toolbar">
                <button class="btn-seg btn-seg--primary" onclick="imprimirFormulario()" style="padding:9px 16px;font-size:.82rem;">
                    <i class="bi bi-printer-fill"></i> Imprimir / Guardar PDF
                </button>
                <button class="btn-seg btn-seg--ghost" onclick="cerrarPreview()" style="padding:9px 16px;font-size:.82rem;">
                    <i class="bi bi-x"></i> Cerrar
                </button>
            </div>
            <div class="preview-modal-body" id="preview-modal-content">
                <!-- Contenido generado dinámicamente por JS -->
            </div>
        </div>
    </div>

    <script>
        const PERSONAL_SOCIO = <?= json_encode($selectorPersonal ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const SOCIO_BASE_URL = '<?= BASE_URL ?>';
        const normalizarPersonalSocio = valor => String(valor ?? '')
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es')
            .replace(/[^a-z0-9]+/g, ' ').trim();
        const escaparPersonalSocio = valor => String(valor ?? '').replace(/[&<>"']/g, caracter => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
        })[caracter]);
        PERSONAL_SOCIO.forEach(persona => {
            persona.indice = normalizarPersonalSocio(`${persona.cedula} ${persona.apellidos} ${persona.nombres} ${persona.cargo} ${persona.area}`);
        });

        function coincidenciasPersonalSocio() {
            const valor = document.getElementById('busquedaPersonalSocio').value;
            const tokens = normalizarPersonalSocio(valor).split(/\s+/).filter(Boolean);
            if (!tokens.length) return [];
            return PERSONAL_SOCIO.filter(persona => tokens.every(token => persona.indice.includes(token))).slice(0, 10);
        }

        function mostrarPersonalSocio() {
            const contenedor = document.getElementById('resultadosPersonalSocio');
            const resultados = coincidenciasPersonalSocio();
            if (!document.getElementById('busquedaPersonalSocio').value.trim()) {
                contenedor.innerHTML = '';
                contenedor.classList.remove('open');
                return;
            }
            contenedor.innerHTML = resultados.length ? resultados.map(persona => `
                <button type="button" class="seg-person-option" role="option" data-persona-id="${Number(persona.id)}">
                    <strong>${escaparPersonalSocio(persona.apellidos)} ${escaparPersonalSocio(persona.nombres)}</strong>
                    <small>C.I. ${escaparPersonalSocio(persona.cedula)} · ${escaparPersonalSocio(persona.cargo || persona.area)}</small>
                </button>`).join('') : '<div class="seg-person-option"><strong>Sin coincidencias</strong><small>Busque por otra parte de la cédula o del nombre.</small></div>';
            contenedor.classList.add('open');
        }

        function seleccionarPersonalSocio(id) {
            const persona = PERSONAL_SOCIO.find(fila => Number(fila.id) === Number(id));
            if (!persona) return;
            document.getElementById('estadoPersonalSocio').innerHTML = '<i class="bi bi-hourglass-split"></i> Cargando expediente institucional...';
            window.location.assign(`${SOCIO_BASE_URL}/talento-humano/estudio-seguridad?id=${Number(persona.id)}`);
        }

        const buscadorPersonalSocio = document.getElementById('busquedaPersonalSocio');
        buscadorPersonalSocio.addEventListener('input', mostrarPersonalSocio);
        buscadorPersonalSocio.addEventListener('focus', mostrarPersonalSocio);
        buscadorPersonalSocio.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                const primera = coincidenciasPersonalSocio()[0];
                if (primera) seleccionarPersonalSocio(primera.id);
            }
            if (event.key === 'Escape') document.getElementById('resultadosPersonalSocio').classList.remove('open');
        });
        document.getElementById('resultadosPersonalSocio').addEventListener('click', event => {
            const opcion = event.target.closest('[data-persona-id]');
            if (opcion) seleccionarPersonalSocio(opcion.dataset.personaId);
        });
        document.addEventListener('click', event => {
            if (!event.target.closest('.seg-person-search')) document.getElementById('resultadosPersonalSocio').classList.remove('open');
        });

        /* Fecha actual */
        document.getElementById('currentDate').textContent =
            new Date().toLocaleDateString('es-EC', { day:'2-digit', month:'long', year:'numeric' });

        /* Cambio de partes */
        function switchParte(n) {
            document.querySelectorAll('.parte-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.parte-panel').forEach(p => p.classList.remove('active'));
            document.getElementById('parte-btn-' + n).classList.add('active');
            document.getElementById('parte-panel-' + n).classList.add('active');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        /* Mostrar/ocultar campos de discapacidad */
        function toggleDiscapacidad() {
            const val = document.getElementById('discapacidad').value;
            const show = val === 'SI';
            document.getElementById('seg-tipo-discapacidad').style.display = show ? '' : 'none';
            document.getElementById('seg-nro-conadis').style.display = show ? '' : 'none';
        }

        /* Calcular edad automáticamente */
        document.getElementById('fecha_nacimiento').addEventListener('change', function() {
            const dob  = new Date(this.value);
            const hoy  = new Date();
            let age    = hoy.getFullYear() - dob.getFullYear();
            const m    = hoy.getMonth() - dob.getMonth();
            if (m < 0 || (m === 0 && hoy.getDate() < dob.getDate())) age--;
            document.getElementById('edad').value = age + ' años';
        });

        /* Modal vista previa */
        function abrirPreview() {
            // Leer valores actuales del formulario para la vista previa
            const getV = id => { const el = document.getElementById(id); return el ? el.value : ''; };
            const logoUrl = '<?= IMG_URL ?>/logoapm.png';

            const html = `
            <div class="doc-a4" style="font-family:'Times New Roman',serif;font-size:10pt;color:#111;">
                <table style="width:100%;border-collapse:collapse;margin-bottom:14px;">
                    <tr>
                        <td style="width:80px;border:1px solid #333;text-align:center;padding:6px;">
                            <img src="${logoUrl}" style="width:55px;" alt="Logo APM">
                            <div style="font-size:7pt;font-weight:bold;">MANTA</div>
                        </td>
                        <td style="border:1px solid #333;text-align:center;font-weight:bold;font-size:12pt;padding:6px;">
                            FORMATO ESTUDIO DE SEGURIDAD - SOCIO ECONÓMICO<br>
                            <span style="font-size:8.5pt;font-weight:normal;">Autoridad Portuaria de Manta</span>
                        </td>
                        <td style="border:1px solid #333;padding:6px;font-size:8.5pt;vertical-align:top;">
                            <strong>Código:</strong> APM-BASC-TH-FO-002<br>
                            <strong>Fecha:</strong> 01/04/2019<br>
                            <strong>Página 1 de 4</strong>
                        </td>
                    </tr>
                </table>

                <p style="font-size:9pt;margin-bottom:10px;">
                    Autoridad Portuaria de Manta requiere la siguiente información para el estudio de seguridad de la institución, estos datos serán clasificados como información confidencial y podrán ser presentados ante las autoridades de control cuando así lo requieran.
                </p>

                <table style="border:none;margin-bottom:8px;width:100%;">
                    <tr>
                        <td style="border:none;"><strong>Fecha de Vinculación:</strong> ${getV('fecha_vinculacion') || '________________'}</td>
                        <td style="border:none;"><strong>Cargo:</strong> ${getV('cargo_cabecera') || '________________'}</td>
                    </tr>
                    <tr><td colspan="2" style="border:none;"><strong>Nombre:</strong> ${getV('nombre_cabecera') || '________________'}</td></tr>
                </table>

                <div style="font-weight:bold;text-decoration:underline;margin:10px 0 6px;">I. INFORMACIÓN GENERAL</div>
                <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
                    <tr><th colspan="4" style="border:1px solid #555;padding:4px 6px;background:#e8e8e8;text-align:center;">INFORMACIÓN DEL SERVIDOR</th></tr>
                    <tr>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;width:25%;"><strong>TIPO DOC. IDENT:</strong></td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;">${getV('tipo_doc_ident')}</td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;width:25%;"><strong>Nº DOCUMENTO:</strong></td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;">${getV('nro_documento')}</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;"><strong>APELLIDOS:</strong></td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;">${getV('apellidos')}</td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;"><strong>NOMBRES:</strong></td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;">${getV('nombres')}</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;"><strong>FECHA NACIMIENTO:</strong></td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;">${getV('fecha_nacimiento')}</td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;"><strong>EDAD:</strong></td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;">${getV('edad')}</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;"><strong>GÉNERO:</strong></td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;">${getV('genero')}</td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;"><strong>ESTADO CIVIL:</strong></td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;">${getV('estado_civil')}</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;"><strong>DIR. PRINCIPAL:</strong></td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;">${getV('dir_calle_principal')}</td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;"><strong>CANTÓN:</strong></td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;">${getV('canton')}</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;"><strong>TEL. CELULAR:</strong></td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;">${getV('tel_celular')}</td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;"><strong>CORREO:</strong></td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;">${getV('correo_institucional')}</td>
                    </tr>
                </table>

                <div style="font-weight:bold;text-decoration:underline;margin:12px 0 6px;">II. GRUPO FAMILIAR</div>
                <table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
                    <tr><th colspan="2" style="border:1px solid #555;padding:4px 6px;background:#e8e8e8;">INFORMACIÓN DEL CÓNYUGE</th></tr>
                    <tr>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;width:40%;"><strong>NOMBRES Y APELLIDOS:</strong></td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;">${getV('conyuge_nombres')}</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;"><strong>TIPO DE RELACIÓN:</strong></td>
                        <td style="border:1px solid #555;padding:4px 6px;font-size:9pt;">${getV('conyuge_tipo_relacion')}</td>
                    </tr>
                </table>

                <div style="font-weight:bold;text-decoration:underline;margin:12px 0 6px;">IV. EXPERIENCIA LABORAL</div>
                <table style="width:100%;border-collapse:collapse;margin-bottom:8px;font-size:8pt;">
                    <tr>
                        <th style="border:1px solid #555;padding:3px 4px;background:#e8e8e8;">Institución</th>
                        <th style="border:1px solid #555;padding:3px 4px;background:#e8e8e8;">Tipo</th>
                        <th style="border:1px solid #555;padding:3px 4px;background:#e8e8e8;">Cargo</th>
                        <th style="border:1px solid #555;padding:3px 4px;background:#e8e8e8;">F. Ingreso</th>
                        <th style="border:1px solid #555;padding:3px 4px;background:#e8e8e8;">F. Retiro</th>
                        <th style="border:1px solid #555;padding:3px 4px;background:#e8e8e8;">Motivo</th>
                    </tr>
                    ${[1,2,3,4].map(i => `<tr>
                        <td style="border:1px solid #555;padding:3px 4px;">${getV('exp_institucion_'+i)}</td>
                        <td style="border:1px solid #555;padding:3px 4px;">${getV('exp_tipo_'+i)}</td>
                        <td style="border:1px solid #555;padding:3px 4px;">${getV('exp_cargo_'+i)}</td>
                        <td style="border:1px solid #555;padding:3px 4px;">${getV('exp_fecha_ingreso_'+i)}</td>
                        <td style="border:1px solid #555;padding:3px 4px;">${getV('exp_fecha_retiro_'+i)}</td>
                        <td style="border:1px solid #555;padding:3px 4px;">${getV('exp_motivo_retiro_'+i)}</td>
                    </tr>`).join('')}
                </table>

                <p style="font-size:9pt;font-style:italic;margin-top:12px;">
                    <strong>Nota:</strong> Certifico que la información aquí suministrada es verdadera y podrá ser verificada en cualquier momento por la institución.
                </p>

                <div style="margin-top:50px;display:flex;justify-content:space-between;">
                    <div style="text-align:center;width:40%;"><div style="border-top:1px solid #333;padding-top:4px;margin-top:60px;">Firma del Servidor</div></div>
                    <div style="text-align:center;width:40%;"><div style="border-top:1px solid #333;padding-top:4px;margin-top:60px;">Responsable de Seguridad</div></div>
                </div>
            </div>`;

            document.getElementById('preview-modal-content').innerHTML = html;
            document.getElementById('preview-seg-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function cerrarPreview() {
            document.getElementById('preview-seg-modal').classList.remove('open');
            document.body.style.overflow = '';
        }

        document.getElementById('preview-seg-modal').addEventListener('click', e => {
            if (e.target === e.currentTarget) cerrarPreview();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') cerrarPreview();
        });

        function imprimirFormulario() {
            const content = document.getElementById('preview-modal-content').innerHTML;
            const w = window.open('', '_blank');
            w.document.write(`<!DOCTYPE html><html lang="es"><head>
                <meta charset="UTF-8">
                <title>APM-BASC-TH-FO-002 – Estudio de Seguridad Socioeconómico</title>
                <style>
                    body { font-family: 'Times New Roman', serif; font-size: 10pt; color: #111; margin: 10mm; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
                    td, th { border: 1px solid #555; padding: 4px 6px; font-size: 9pt; }
                    th { background: #e8e8e8; text-align: center; font-weight: bold; }
                    @page { size: A4; margin: 15mm; }
                </style>
            </head><body>${content}</body></html>`);
            w.document.close();
            w.focus();
            setTimeout(() => { w.print(); w.close(); }, 500);
        }
        const datosGuardados = <?= json_encode($estudio ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        Object.entries(datosGuardados).forEach(([nombre, valor]) => {
            document.querySelectorAll(`[name="${CSS.escape(nombre)}"]`).forEach(campo => {
                if (campo.type === 'radio' || campo.type === 'checkbox') {
                    campo.checked = String(campo.value).toUpperCase() === String(valor ?? '').toUpperCase();
                } else if (valor !== null && valor !== undefined && typeof valor !== 'object') {
                    campo.value = valor;
                }
            });
        });

        document.getElementById('formEstudioSeguridad').addEventListener('submit', event => {
            const empleadoId = Number(event.currentTarget.elements.empleado_id?.value || 0);
            if (empleadoId <= 0) {
                event.preventDefault();
                switchParte(1);
                document.getElementById('busquedaPersonalSocio').focus();
                showToast('Seleccione un servidor público antes de guardar.','error');
                return;
            }
            const boton=document.getElementById('btnGuardarEstudio');
            if (boton) {
                boton.disabled=true;
                boton.innerHTML='<i class="bi bi-hourglass-split"></i> Guardando...';
            }
        });
    </script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
