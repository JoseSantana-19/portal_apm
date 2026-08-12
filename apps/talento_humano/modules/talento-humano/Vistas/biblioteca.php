<?php
/* biblioteca.php – Vista: Biblioteca de Formularios
   Muestra todos los formularios del sistema divididos por categorías,
   con vista previa en modal y acceso directo para editar/completar. */
$usuarioNombre = $usuarioNombre ?? 'USUARIO APM';
$usuarioRol    = $usuarioRol    ?? 'Administrador TH';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biblioteca de Formularios | Talento Humano APM</title>
    <meta name="description" content="Biblioteca centralizada de formularios del módulo Talento Humano — Autoridad Portuaria de Manta.">
    <?php require ROOT . '/shared/head_assets.php'; ?>
    <style>
        /* ── Biblioteca — Enterprise v4 ───────────────────────────────── */

        /* ── Hero compacto ── */
        .bib-hero {
            background: linear-gradient(135deg, var(--navy-900) 0%, var(--ocean-700) 55%, #0e7490 100%);
            border-radius: 16px;
            padding: 14px 26px;
            display: flex; align-items: center; justify-content: space-between;
            flex-wrap: wrap; gap: 12px;
            margin-bottom: 6px;
            position: relative; overflow: hidden;
        }
        .bib-hero::before {
            content: '\F3D5';
            font-family: 'bootstrap-icons';
            position: absolute; right: 80px; top: 50%;
            transform: translateY(-50%);
            font-size: 6rem; color: rgba(255,255,255,.05);
            pointer-events: none; line-height: 1;
        }
        .bib-hero-text h2 {
            color: #fff; font-size: 1.2rem;
            margin: 0 0 2px; font-family: var(--font-display);
        }
        .bib-hero-text p { color: rgba(255,255,255,.72); font-size: .82rem; margin: 0; }
        .bib-hero-icon {
            width: 52px; height: 52px;
            background: rgba(255,255,255,.12);
            border-radius: 14px; border: 1px solid rgba(255,255,255,.2);
            display: grid; place-items: center;
            font-size: 1.7rem; color: #a5f3fc;
            flex-shrink: 0;
        }

        /* ── Separadores más ajustados ── */
        .bib-section-title {
            display: flex; align-items: center; gap: 12px;
            margin: 12px 0 10px;
        }
        .bib-section-title::before,
        .bib-section-title::after {
            content: ''; flex: 1; height: 1px;
            background: linear-gradient(90deg, var(--line) 0%, transparent 100%);
        }
        .bib-section-title::after { background: linear-gradient(90deg, transparent 0%, var(--line) 100%); }
        .bib-section-label {
            display: inline-flex; align-items: center; gap: 7px;
            font-size: .7rem; font-weight: 700;
            letter-spacing: .18em; text-transform: uppercase;
            color: var(--ocean-700);
            background: #fff; padding: 5px 14px;
            border-radius: 999px;
            border: 1px solid rgba(14,116,144,.2);
        }

        /* ── Grid tarjetas: más anchas ── */
        .bib-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 16px;
        }

        /* ── Tarjeta ── */
        .form-card-lib {
            background: #fff;
            border-radius: 16px;
            border: 1px solid rgba(0,0,0,.08);
            box-shadow: 0 2px 10px rgba(9,42,62,.06);
            overflow: hidden;
            transition: transform .22s cubic-bezier(.25,.8,.25,1), box-shadow .22s;
            display: flex; flex-direction: column;
            cursor: pointer;
        }
        .form-card-lib:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 36px rgba(0,51,102,.14);
        }

        /* ── Cabecera: franja horizontal compacta ── */
        .form-card-head {
            padding: 13px 16px 11px;
            position: relative;
            display: flex; align-items: center; gap: 12px;
            overflow: hidden;
        }
        .form-card-head::after {
            font-family: 'bootstrap-icons';
            content: attr(data-icon);
            position: absolute; right: -6px; bottom: -10px;
            font-size: 4.5rem; color: rgba(255,255,255,.07);
            pointer-events: none; line-height: 1;
        }
        .form-card-head--blue  { background: linear-gradient(135deg, var(--navy-900), var(--ocean-700)); }
        .form-card-head--teal  { background: linear-gradient(135deg, #0e7490, #0891b2); }
        .form-card-head--indigo{ background: linear-gradient(135deg, #3730a3, #4f46e5); }
        .form-card-head--amber { background: linear-gradient(135deg, #92400e, #d97706); }

        .form-card-icon {
            width: 40px; height: 40px;
            background: rgba(255,255,255,.16);
            border-radius: 11px; border: 1px solid rgba(255,255,255,.22);
            display: grid; place-items: center;
            font-size: 1.25rem; color: #fff;
            flex-shrink: 0;
            transition: transform .2s;
        }
        .form-card-lib:hover .form-card-icon { transform: scale(1.1); }

        .form-card-head-info { flex: 1; min-width: 0; }
        .form-card-head-info h3 {
            color: #fff; font-size: .92rem;
            margin: 0 0 2px; font-family: var(--font-display); font-weight: 700;
        }
        .form-card-head-info p { color: rgba(255,255,255,.75); font-size: .73rem; margin: 0; }

        .form-card-codigo {
            position: absolute; top: 7px; right: 9px;
            background: rgba(0,0,0,.22); color: rgba(255,255,255,.8);
            font-size: .57rem; font-weight: 700; letter-spacing: .1em;
            padding: 2px 7px; border-radius: 999px;
        }

        /* ── Cuerpo ── */
        .form-card-body { padding: 12px 16px 8px; flex: 1; }
        .form-meta-row {
            display: flex; align-items: flex-start; gap: 7px;
            font-size: .78rem; color: var(--ink-600); margin-bottom: 5px; line-height: 1.35;
        }
        .form-meta-row i { color: var(--teal-500); font-size: .8rem; flex-shrink: 0; margin-top: 1px; }

        /* Tags: alto contraste, rectangulares — sin pill */
        .form-tags { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 8px; }
        .form-tag {
            display: inline-flex; align-items: center; gap: 4px;
            background: #eef2f7; color: #2d4a6b;
            font-size: .67rem; font-weight: 700;
            padding: 3px 8px; border-radius: 5px;
            border: 1px solid #d0dcea;
        }
        .form-tag i { color: #4a78a4; font-size: .69rem; }

        /* ── Zona de acciones: columna con 3 botones ── */
        .form-card-actions {
            padding: 11px 16px;
            display: flex; flex-direction: column; gap: 6px;
            border-top: 1px solid #eef2f7;
            background: #f8fafc;
        }
        .btn-lib {
            width: 100%;
            border: none; border-radius: 8px;
            padding: 8px 14px; font-size: .79rem; font-weight: 600;
            cursor: pointer; display: inline-flex; align-items: center;
            justify-content: center; gap: 6px;
            transition: transform .15s, box-shadow .15s, background .15s;
            text-decoration: none;
        }
        /* Primario: turquesa */
        .btn-lib--primary {
            background: linear-gradient(135deg, var(--ocean-700), var(--teal-500));
            color: #fff;
            box-shadow: 0 4px 12px rgba(13,95,135,.2);
        }
        .btn-lib--primary:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(13,95,135,.3); }
        /* Secundario: ghost turquesa */
        .btn-lib--ghost {
            background: #fff; color: var(--ocean-700);
            border: 1px solid rgba(13,95,135,.28);
        }
        .btn-lib--ghost:hover { background: #f0faff; border-color: var(--ocean-700); }
        /* Terciario: Descargar — rojo suave con separador superior */
        .btn-lib--doc {
            background: #fff5f5;
            color: #b91c1c;
            border: 1.5px solid rgba(185,28,28,.35);
            border-radius: 8px;
            font-weight: 700;
            letter-spacing: .01em;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.8);
        }
        .btn-lib--doc:hover {
            background: #fee2e2;
            border-color: #b91c1c;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(185,28,28,.15);
        }

        /* ── Modal de Vista Previa ── */
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
            transform: translateY(30px);
            transition: transform .3s;
            overflow: hidden;
        }
        .preview-overlay.open .preview-modal { transform: translateY(0); }
        .preview-modal-header {
            background: linear-gradient(135deg, var(--navy-900), var(--ocean-700));
            padding: 16px 22px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .preview-modal-header h4 { color: #fff; margin: 0; font-size: 1rem; }
        .preview-modal-close {
            width: 34px; height: 34px; border-radius: 9px;
            background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.25);
            color: #fff; cursor: pointer; display: grid; place-items: center;
            font-size: 1.1rem; transition: background .2s;
        }
        .preview-modal-close:hover { background: rgba(255,255,255,.32); }
        .preview-modal-toolbar {
            padding: 10px 22px; background: #f8fbff;
            border-bottom: 1px solid var(--line);
            display: flex; gap: 8px; flex-wrap: wrap;
        }
        .preview-modal-body {
            padding: 0;
            max-height: 75vh; overflow-y: auto;
        }

        /* Documento A4 dentro del modal */
        .doc-a4 {
            width: 210mm; max-width: 100%;
            margin: 20px auto; padding: 18mm 20mm;
            background: #fff;
            box-shadow: 0 2px 20px rgba(0,0,0,.08);
            font-family: 'Times New Roman', serif;
            font-size: 10pt; color: #111;
            line-height: 1.4;
        }
        .doc-a4 table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .doc-a4 table td, .doc-a4 table th {
            border: 1px solid #555; padding: 4px 6px; font-size: 9pt;
        }
        .doc-a4 table th { background: #e8e8e8; text-align: center; font-weight: bold; }
        .doc-header-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .doc-header-table td { border: 1px solid #333; padding: 6px 8px; vertical-align: top; }
        .doc-logo-cell { width: 80px; text-align: center; }
        .doc-logo-cell img { width: 60px; }
        .doc-title-cell { text-align: center; font-weight: bold; font-size: 12pt; }
        .doc-code-cell { font-size: 8.5pt; }
        .doc-section-title { font-weight: bold; text-decoration: underline; margin: 12px 0 6px; font-size: 10.5pt; }
        .doc-note { font-size: 9pt; font-style: italic; margin-top: 10px; }

        @media (max-width: 768px) {
            .bib-grid { grid-template-columns: 1fr; }
            .doc-a4 { padding: 10mm 8mm; font-size: 9pt; }
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
            <?php $topbarShowSearch=true;require ROOT.'/shared/topbar.php'; ?>

            <main class="main">
                <div class="content-shell">

                    <!-- HERO compacto -->
                    <div class="bib-hero">
                        <div class="bib-hero-text">
                            <h2><i class="bi bi-archive-fill"></i> Biblioteca de Formularios</h2>
                            <p>Consulta, completa, edita y genera vista previa de todos los formularios oficiales del sistema de Talento Humano APM.</p>
                        </div>
                        <div class="bib-hero-icon">
                            <i class="bi bi-folder2-open"></i>
                        </div>
                    </div>

                    <!-- ══ SECCIÓN 1: EXPEDIENTE DEL PERSONAL ══ -->
                    <div class="bib-section-title">
                        <div class="bib-section-label">
                            <i class="bi bi-person-vcard-fill"></i>
                            Expediente del Personal
                        </div>
                    </div>

                    <div class="bib-grid">

                        <!-- Tarjeta 1: Formulario Principal de Registro -->
                        <div class="form-card-lib">
                            <div class="form-card-head form-card-head--blue" data-icon="&#xF4C9;">
                                <span class="form-card-codigo">APM-TH-FO-001</span>
                                <div class="form-card-icon"><i class="bi bi-person-badge-fill"></i></div>
                                <div class="form-card-head-info">
                                    <h3>Formulario Principal de Registro</h3>
                                    <p>Expediente completo &mdash; 5 secciones</p>
                                </div>
                            </div>
                            <div class="form-card-body">
                                <div class="form-meta-row"><i class="bi bi-layers"></i> Personal &bull; Laboral &bull; Contacto &bull; Formaci&oacute;n &bull; Notas</div>
                                <div class="form-meta-row"><i class="bi bi-info-circle"></i> Registro inicial de alta de personal institucional</div>
                                <div class="form-tags">
                                    <span class="form-tag"><i class="bi bi-person"></i> Personal</span>
                                    <span class="form-tag"><i class="bi bi-briefcase"></i> Laboral</span>
                                    <span class="form-tag"><i class="bi bi-mortarboard"></i> Formaci&oacute;n</span>
                                    <span class="form-tag"><i class="bi bi-geo-alt"></i> Contacto</span>
                                </div>
                            </div>
                            <div class="form-card-actions">
                                <a href="<?= BASE_URL ?>/talento-humano/empleado/crear" class="btn-lib btn-lib--primary">
                                    <i class="bi bi-plus-circle"></i> Nuevo registro
                                </a>
                                <button class="btn-lib btn-lib--ghost" onclick="abrirRegistros('registros-expediente')">
                                    <i class="bi bi-folder2-open"></i> Ver registros
                                </button>
                                <a class="btn-lib btn-lib--doc" target="_blank" rel="noopener" href="<?= BASE_URL ?>/talento-humano/empleado/formato-principal-blanco" title="Abrir el formato oficial en PDF">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Descargar Formato
                                </a>
                            </div>
                        </div>

                        <!-- Tarjeta 2: Acci&oacute;n de Personal -->
                        <div class="form-card-lib">
                            <div class="form-card-head form-card-head--teal" data-icon="&#xF3E1;">
                                <span class="form-card-codigo">APM-TH-FO-002</span>
                                <div class="form-card-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                                <div class="form-card-head-info">
                                    <h3>Acci&oacute;n de Personal</h3>
                                    <p>Movimientos, cambios y novedades</p>
                                </div>
                            </div>
                            <div class="form-card-body">
                                <div class="form-meta-row"><i class="bi bi-arrow-left-right"></i> Situaci&oacute;n actual vs. propuesta</div>
                                <div class="form-meta-row"><i class="bi bi-info-circle"></i> Cambio de cargo, unidad, remuneraci&oacute;n, etc.</div>
                                <div class="form-tags">
                                    <span class="form-tag"><i class="bi bi-arrow-left-right"></i> Traslado</span>
                                    <span class="form-tag"><i class="bi bi-cash-stack"></i> Remuneraci&oacute;n</span>
                                    <span class="form-tag"><i class="bi bi-building"></i> Unidad</span>
                                    <span class="form-tag"><i class="bi bi-clipboard2-check"></i> Acci&oacute;n</span>
                                </div>
                            </div>
                            <div class="form-card-actions">
                                <a href="<?= BASE_URL ?>/talento-humano/accion-personal" class="btn-lib btn-lib--primary">
                                    <i class="bi bi-plus-circle"></i> Nueva acci&oacute;n
                                </a>
                                <button class="btn-lib btn-lib--ghost" onclick="abrirRegistros('registros-accion')">
                                    <i class="bi bi-folder2-open"></i> Ver registros
                                </button>
                                <a class="btn-lib btn-lib--doc" target="_blank" href="<?= BASE_URL ?>/talento-humano/accion-personal/formato-blanco" title="Descargar el formato oficial en blanco">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Descargar Formato
                                </a>
                            </div>
                        </div>

                    </div><!-- /bib-grid sección 1 -->


                    <!-- ══ SECCIÓN 2: SEGURIDAD INSTITUCIONAL ══ -->
                    <div class="bib-section-title">
                        <div class="bib-section-label">
                            <i class="bi bi-shield-lock-fill"></i>
                            Seguridad Institucional
                        </div>
                    </div>

                    <div class="bib-grid">

                        <!-- Tarjeta 3: Estudio de Seguridad Socioeconómico -->
                        <div class="form-card-lib">
                            <div class="form-card-head form-card-head--indigo" data-icon="&#xF5DF;">
                                <span class="form-card-codigo">APM-BASC-TH-FO-002</span>
                                <div class="form-card-icon"><i class="bi bi-shield-shaded"></i></div>
                                <div class="form-card-head-info">
                                    <h3>Estudio de Seguridad Socioecon&oacute;mico</h3>
                                    <p>Formato BASC &mdash; 3 partes (4 p&aacute;ginas)</p>
                                </div>
                            </div>
                            <div class="form-card-body">
                                <div class="form-meta-row"><i class="bi bi-layers"></i> Parte 1: Info general &bull; Parte 2: Familia/Acad. &bull; Parte 3: Laboral</div>
                                <div class="form-meta-row"><i class="bi bi-calendar3"></i> Cód.: APM-BASC-TH-FO-002 &mdash; 01/04/2019</div>
                                <div class="form-tags">
                                    <span class="form-tag"><i class="bi bi-person-check"></i> BASC</span>
                                    <span class="form-tag"><i class="bi bi-house-door"></i> Familiar</span>
                                    <span class="form-tag"><i class="bi bi-bank"></i> Bancario</span>
                                    <span class="form-tag"><i class="bi bi-briefcase"></i> Laboral</span>
                                </div>
                            </div>
                            <div class="form-card-actions">
                                <a href="<?= BASE_URL ?>/talento-humano/estudio-seguridad" class="btn-lib btn-lib--primary">
                                    <i class="bi bi-plus-circle"></i> Nuevo estudio
                                </a>
                                <button class="btn-lib btn-lib--ghost" onclick="abrirRegistros('registros-seguridad')">
                                    <i class="bi bi-folder2-open"></i> Ver registros
                                </button>
                                <a class="btn-lib btn-lib--doc" target="_blank" href="<?= BASE_URL ?>/talento-humano/estudio-seguridad/imprimir?blank=1" title="Descargar el formato oficial en blanco">
                                    <i class="bi bi-file-earmark-arrow-down"></i> Descargar Formato
                                </a>
                            </div>
                        </div>

                    </div><!-- /bib-grid sección 2 -->


                </div><!-- /content-shell -->
            </main>
        </section>
    </div>


    <!-- ══ MODALES DE REGISTROS GUARDADOS ═════════════════════════════════ -->

    <!-- REGISTROS: Formulario Principal de Registro -->
    <div class="preview-overlay" id="registros-expediente" role="dialog" aria-modal="true">
        <div class="preview-modal" style="max-width:900px;">
            <div class="preview-modal-header">
                <h4><i class="bi bi-person-badge-fill"></i> Registros — Formulario Principal (APM-TH-FO-001)</h4>
                <button class="preview-modal-close" onclick="cerrarPreview('registros-expediente')" aria-label="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="preview-modal-toolbar">
                <a href="<?= BASE_URL ?>/talento-humano/empleado/crear" class="btn-lib btn-lib--primary">
                    <i class="bi bi-plus-circle"></i> Nuevo registro
                </a>
                <a href="<?= BASE_URL ?>/talento-humano/directorio" class="btn-lib btn-lib--ghost">
                    <i class="bi bi-people-fill"></i> Ver directorio completo
                </a>
            </div>
            <div class="preview-modal-body" style="padding:0;">
                <div style="padding:16px 20px;background:#f8fbff;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <input type="text" placeholder="Buscar por cédula o nombre..." id="busq-expediente"
                           oninput="filtrarTabla('tabla-expediente','busq-expediente')"
                           style="border:1px solid var(--line);border-radius:10px;padding:8px 14px;font-size:.88rem;flex:1;min-width:180px;">
                    <span style="font-size:.82rem;color:var(--text-muted);"><i class="bi bi-info-circle"></i> <?= count($empleados ?? []) ?> expediente(s) registrado(s)</span>
                </div>
                <div style="overflow-x:auto;">
                    <table id="tabla-expediente" style="width:100%;border-collapse:collapse;font-size:.88rem;">
                        <thead>
                            <tr style="background:linear-gradient(135deg,var(--navy-900),var(--ocean-700));color:#fff;">
                                <th style="padding:10px 14px;text-align:left;font-weight:600;">Cédula</th>
                                <th style="padding:10px 14px;text-align:left;font-weight:600;">Funcionario</th>
                                <th style="padding:10px 14px;text-align:left;font-weight:600;">Cargo</th>
                                <th style="padding:10px 14px;text-align:left;font-weight:600;">Área</th>
                                <th style="padding:10px 14px;text-align:center;font-weight:600;">Estado</th>
                                <th style="padding:10px 14px;text-align:center;font-weight:600;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($empleados)): foreach ($empleados as $i => $emp):
                            $nombre   = trim(($emp['nombres'] ?? '') . ' ' . ($emp['apellidos'] ?? ''));
                            $cedula   = $emp['cedula'] ?? '';
                            $cargo    = $emp['cargo'] ?? 'Sin cargo';
                            $area     = $emp['direccion_area'] ?? 'Sin área';
                            $estado_n = (int)($emp['estado'] ?? 1);
                            $empId    = (int)($emp['id'] ?? 0);
                            $estTag   = $estado_n === 1
                                ? '<span style="background:#dcfce7;color:#166534;padding:3px 10px;border-radius:999px;font-size:.78rem;font-weight:700;">Activo</span>'
                                : '<span style="background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:999px;font-size:.78rem;font-weight:700;">Inactivo</span>';
                        ?>
                            <tr style="border-bottom:1px solid var(--line);<?= $i%2===0?'background:#fff;':'background:#f8fbff;' ?>" data-search="<?= strtolower(htmlspecialchars($cedula.' '.$nombre)) ?>">
                                <td style="padding:10px 14px;font-family:monospace;"><?= htmlspecialchars($cedula) ?></td>
                                <td style="padding:10px 14px;font-weight:500;"><?= htmlspecialchars($nombre) ?></td>
                                <td style="padding:10px 14px;color:var(--text-muted);"><?= htmlspecialchars($cargo) ?></td>
                                <td style="padding:10px 14px;color:var(--text-muted);font-size:.82rem;"><?= htmlspecialchars($area) ?></td>
                                <td style="padding:10px 14px;text-align:center;"><?= $estTag ?></td>
                                <td style="padding:10px 14px;text-align:center;white-space:nowrap;">
                                    <a href="<?= BASE_URL ?>/talento-humano/empleado/editar?id=<?= $empId ?>"
                                       title="Ver / Editar expediente"
                                       style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:8px;background:#eff6ff;color:#1d4ed8;border:1px solid rgba(29,78,216,.2);font-size:.8rem;text-decoration:none;margin-right:4px;">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </a>
                                    <a href="<?= BASE_URL ?>/talento-humano/empleado/imprimir-ficha?id=<?= $empId ?>"
                                       target="_blank" title="Imprimir ficha PDF"
                                       style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:8px;background:#f0fdf4;color:#166534;border:1px solid rgba(22,101,52,.2);font-size:.8rem;text-decoration:none;">
                                        <i class="bi bi-printer-fill"></i> PDF
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" style="padding:28px;text-align:center;color:var(--text-muted);"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>No hay expedientes registrados aún.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- REGISTROS: Acción de Personal -->
    <div class="preview-overlay" id="registros-accion" role="dialog" aria-modal="true">
        <div class="preview-modal" style="max-width:900px;">
            <div class="preview-modal-header" style="background:linear-gradient(135deg,#0e7490,#0891b2);">
                <h4><i class="bi bi-file-earmark-text-fill"></i> Registros — Acción de Personal (APM-TH-FO-002)</h4>
                <button class="preview-modal-close" onclick="cerrarPreview('registros-accion')" aria-label="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="preview-modal-toolbar">
                <a href="<?= BASE_URL ?>/talento-humano/accion-personal" class="btn-lib btn-lib--primary">
                    <i class="bi bi-plus-circle"></i> Nueva acción
                </a>
            </div>
            <div class="preview-modal-body" style="padding:0;">
                <div style="padding:16px 20px;background:#f0fdfb;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <input type="text" placeholder="Buscar por cédula o nombre..." id="busq-accion"
                           oninput="filtrarTabla('tabla-accion','busq-accion')"
                           style="border:1px solid var(--line);border-radius:10px;padding:8px 14px;font-size:.88rem;flex:1;min-width:180px;">
                    <span style="font-size:.82rem;color:var(--text-muted);"><i class="bi bi-info-circle"></i> <?= count($acciones ?? []) ?> acción(es) registrada(s)</span>
                </div>
                <div style="overflow-x:auto;">
                    <table id="tabla-accion" style="width:100%;border-collapse:collapse;font-size:.88rem;">
                        <thead>
                            <tr style="background:linear-gradient(135deg,#0e7490,#0891b2);color:#fff;">
                                <th style="padding:10px 14px;text-align:left;">N° Doc.</th>
                                <th style="padding:10px 14px;text-align:left;">Funcionario</th>
                                <th style="padding:10px 14px;text-align:left;">Tipo de Acción</th>
                                <th style="padding:10px 14px;text-align:left;">Fecha</th>
                                <th style="padding:10px 14px;text-align:center;">Estado</th>
                                <th style="padding:10px 14px;text-align:center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Registros de acciones de personal -->
                            <?php if (!empty($acciones)): foreach ($acciones as $i => $accion):
                                $nombre = trim(($accion['nombres'] ?? '') . ' ' . ($accion['apellidos'] ?? ''));
                                $cedula = $accion['identificacion'] ?? '';
                                $nro = $accion['numero_accion'] ?? '';
                                $accionId = (int)($accion['accion_id'] ?? 0);
                            ?>
                            <tr style="border-bottom:1px solid var(--line);<?= $i%2===0?'background:#fff;':'background:#f0fdfb;' ?>" data-search="<?= strtolower(htmlspecialchars($cedula.' '.$nombre)) ?>">
                                <td style="padding:10px 14px;font-family:monospace;font-size:.82rem;"><?= htmlspecialchars($nro) ?></td>
                                <td style="padding:10px 14px;font-weight:500;"><?= htmlspecialchars($nombre) ?></td>
                                <td style="padding:10px 14px;"><span style="background:#e0f2fe;color:#0369a1;padding:3px 8px;border-radius:6px;font-size:.8rem;"><?= htmlspecialchars($accion['tipo_accion'] ?? '') ?></span></td>
                                <td style="padding:10px 14px;color:var(--text-muted);"><?= !empty($accion['fecha_elaboracion']) ? date('d/m/Y',strtotime($accion['fecha_elaboracion'])) : '' ?></td>
                                <?php $estadoAccion=strtoupper((string)($accion['estado_documento'] ?? 'BORRADOR')); ?>
                                <td style="padding:10px 14px;text-align:center;"><span style="background:<?= $estadoAccion==='APROBADO'?'#dcfce7':($estadoAccion==='ANULADO'?'#fee2e2':'#fef3c7') ?>;color:<?= $estadoAccion==='APROBADO'?'#166534':($estadoAccion==='ANULADO'?'#991b1b':'#92400e') ?>;padding:3px 10px;border-radius:999px;font-size:.78rem;font-weight:700;"><?= htmlspecialchars($estadoAccion) ?></span></td>
                                <td style="padding:10px 14px;text-align:center;white-space:nowrap;">
                                    <a target="_blank" href="<?= BASE_URL ?>/talento-humano/accion-personal/imprimir-accion?id=<?= $accionId ?>"
                                       style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:8px;background:#eff6ff;color:#1d4ed8;border:1px solid rgba(29,78,216,.2);font-size:.8rem;text-decoration:none;">
                                        <i class="bi bi-file-earmark-pdf"></i> PDF
                                    </a>
                                    <?php if (in_array($estadoAccion,['BORRADOR','PENDIENTE'],true) && Auth::can('acciones','editar')): ?>
                                    <form method="post" action="<?= BASE_URL ?>/talento-humano/accion-personal/aprobar" style="display:inline" onsubmit="return confirm('¿Aprobar y aplicar esta acción al historial laboral?');">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><input type="hidden" name="accion_id" value="<?= $accionId ?>">
                                        <button class="btn-lib btn-lib--primary" style="padding:5px 9px" type="submit" title="Aprobar"><i class="bi bi-check-circle"></i></button>
                                    </form>
                                    <form method="post" action="<?= BASE_URL ?>/talento-humano/accion-personal/anular" style="display:inline" onsubmit="const m=prompt('Motivo de anulación:');if(!m)return false;this.motivo.value=m;return true;">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><input type="hidden" name="accion_id" value="<?= $accionId ?>"><input type="hidden" name="motivo" value="">
                                        <button class="btn-lib btn-lib--doc" style="padding:5px 9px" type="submit" title="Anular"><i class="bi bi-x-circle"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="6" style="padding:28px;text-align:center;color:var(--text-muted);"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>No hay acciones de personal registradas aún.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- REGISTROS: Estudio de Seguridad Socioeconómico -->
    <div class="preview-overlay" id="registros-seguridad" role="dialog" aria-modal="true">
        <div class="preview-modal" style="max-width:900px;">
            <div class="preview-modal-header" style="background:linear-gradient(135deg,#3730a3,#4f46e5);">
                <h4><i class="bi bi-shield-shaded"></i> Registros — Estudio de Seguridad (APM-BASC-TH-FO-002)</h4>
                <button class="preview-modal-close" onclick="cerrarPreview('registros-seguridad')" aria-label="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="preview-modal-toolbar">
                <a href="<?= BASE_URL ?>/talento-humano/estudio-seguridad" class="btn-lib btn-lib--primary">
                    <i class="bi bi-plus-circle"></i> Nuevo estudio
                </a>
            </div>
            <div class="preview-modal-body" style="padding:0;">
                <div style="padding:16px 20px;background:#f5f3ff;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <input type="text" placeholder="Buscar por cédula o nombre..." id="busq-seguridad"
                           oninput="filtrarTabla('tabla-seguridad','busq-seguridad')"
                           style="border:1px solid var(--line);border-radius:10px;padding:8px 14px;font-size:.88rem;flex:1;min-width:180px;">
                    <span style="font-size:.82rem;color:var(--text-muted);"><i class="bi bi-info-circle"></i> <?= count($estudios ?? []) ?> estudio(s) registrado(s)</span>
                </div>
                <div style="overflow-x:auto;">
                    <table id="tabla-seguridad" style="width:100%;border-collapse:collapse;font-size:.88rem;">
                        <thead>
                            <tr style="background:linear-gradient(135deg,#3730a3,#4f46e5);color:#fff;">
                                <th style="padding:10px 14px;text-align:left;">Código</th>
                                <th style="padding:10px 14px;text-align:left;">Servidor</th>
                                <th style="padding:10px 14px;text-align:left;">Cédula</th>
                                <th style="padding:10px 14px;text-align:left;">Fecha</th>
                                <th style="padding:10px 14px;text-align:center;">Estado</th>
                                <th style="padding:10px 14px;text-align:center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($estudios)): foreach ($estudios as $i => $estudio):
                                $nombre = trim(($estudio['nombres_empleado'] ?? '') . ' ' . ($estudio['apellidos_empleado'] ?? ''));
                                $estudioId  = (int)($estudio['estudio_id'] ?? 0);
                                $cedula = $estudio['identificacion'] ?? '';
                                $cod = $estudio['codigo_formato'] ?? 'APM-BASC-TH-FO-002';
                            ?>
                            <tr style="border-bottom:1px solid var(--line);<?= $i%2===0?'background:#fff;':'background:#f5f3ff;' ?>" data-search="<?= strtolower(htmlspecialchars($cedula.' '.$nombre)) ?>">
                                <td style="padding:10px 14px;font-family:monospace;font-size:.82rem;"><?= htmlspecialchars($cod) ?></td>
                                <td style="padding:10px 14px;font-weight:500;"><?= htmlspecialchars($nombre) ?></td>
                                <td style="padding:10px 14px;color:var(--text-muted);"><?= htmlspecialchars($cedula) ?></td>
                                <td style="padding:10px 14px;color:var(--text-muted);"><?= !empty($estudio['fecha_creacion']) ? date('d/m/Y',strtotime($estudio['fecha_creacion'])) : '' ?></td>
                                <td style="padding:10px 14px;text-align:center;"><span style="background:#ede9fe;color:#5b21b6;padding:3px 10px;border-radius:999px;font-size:.78rem;font-weight:700;"><?= !empty($estudio['estado']) ? 'Registrado' : 'Inactivo' ?></span></td>
                                <td style="padding:10px 14px;text-align:center;white-space:nowrap;">
                                    <a href="<?= BASE_URL ?>/talento-humano/estudio-seguridad?estudio_id=<?= $estudioId ?>"
                                       style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:8px;background:#f5f3ff;color:#5b21b6;border:1px solid rgba(91,33,182,.2);font-size:.8rem;text-decoration:none;">
                                        <i class="bi bi-pencil-square"></i> Editar
                                    </a>
                                    <a target="_blank" href="<?= BASE_URL ?>/talento-humano/estudio-seguridad/imprimir?estudio_id=<?= $estudioId ?>"
                                       style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:8px;background:#fff1f2;color:#be123c;border:1px solid rgba(190,18,60,.2);font-size:.8rem;text-decoration:none;margin-left:4px;">
                                        <i class="bi bi-file-earmark-pdf"></i> PDF
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="6" style="padding:28px;text-align:center;color:var(--text-muted);"><i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>No hay estudios de seguridad registrados aún.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ MODALES DE VISTA PREVIA ══════════════════════════════════════════ -->

    <!-- MODAL: Formulario Principal de Registro -->
    <div class="preview-overlay" id="preview-registro" role="dialog" aria-modal="true">
        <div class="preview-modal">
            <div class="preview-modal-header">
                <h4><i class="bi bi-person-badge-fill"></i> Formulario Principal de Registro — APM-TH-FO-001</h4>
                <button class="preview-modal-close" onclick="cerrarPreview('preview-registro')" aria-label="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="preview-modal-toolbar">
                <button class="btn-lib btn-lib--primary" onclick="imprimirModal('preview-registro')">
                    <i class="bi bi-printer-fill"></i> Imprimir
                </button>
                <a href="<?= BASE_URL ?>/talento-humano/empleado/crear" class="btn-lib btn-lib--ghost">
                    <i class="bi bi-pencil-square"></i> Abrir formulario
                </a>
            </div>
            <div class="preview-modal-body">
                <div class="doc-a4">
                    <table class="doc-header-table">
                        <tr>
                            <td class="doc-logo-cell" rowspan="2">
                                <img src="<?= IMG_URL ?>/logoapm.png" alt="Logo APM" style="width:55px;">
                                <div style="font-size:7pt;font-weight:bold;margin-top:4px;">MANTA</div>
                            </td>
                            <td class="doc-title-cell" rowspan="2" style="font-size:13pt;">
                                FORMULARIO DE REGISTRO DE PERSONAL<br>
                                <span style="font-size:9pt;font-weight:normal;">Autoridad Portuaria de Manta</span>
                            </td>
                            <td class="doc-code-cell">
                                <strong>Código:</strong> APM-TH-FO-001<br>
                                <strong>Fecha:</strong> <?= date('d/m/Y') ?><br>
                                <strong>Versión:</strong> 1.0
                            </td>
                        </tr>
                    </table>

                    <div class="doc-section-title">I. INFORMACIÓN PERSONAL</div>
                    <table>
                        <tr><td style="width:35%;"><strong>Cédula / Pasaporte:</strong></td><td></td><td style="width:30%;"><strong>Nombres y Apellidos:</strong></td><td></td></tr>
                        <tr><td><strong>Fecha de Nacimiento:</strong></td><td></td><td><strong>Género:</strong></td><td></td></tr>
                        <tr><td><strong>Estado Civil:</strong></td><td></td><td><strong>Tipo de Sangre:</strong></td><td></td></tr>
                        <tr><td><strong>Nacionalidad:</strong></td><td></td><td><strong>Condición Especial:</strong></td><td></td></tr>
                        <tr><td><strong>Tipo de Discapacidad:</strong></td><td></td><td><strong>Grado (%):</strong></td><td></td></tr>
                    </table>

                    <div class="doc-section-title">II. DATOS LABORALES</div>
                    <table>
                        <tr><td style="width:35%;"><strong>Departamento / Dirección:</strong></td><td></td><td style="width:30%;"><strong>Cargo / Puesto:</strong></td><td></td></tr>
                        <tr><td><strong>Tipo de Contrato:</strong></td><td></td><td><strong>Fecha de Ingreso:</strong></td><td></td></tr>
                        <tr><td><strong>Sueldo / RMU ($):</strong></td><td></td><td><strong>Jornada:</strong></td><td></td></tr>
                        <tr><td><strong>Ciudad de Residencia:</strong></td><td></td><td><strong>Estado:</strong></td><td></td></tr>
                    </table>

                    <div class="doc-section-title">III. CONTACTO Y EMERGENCIAS</div>
                    <table>
                        <tr><td style="width:35%;"><strong>Correo Institucional:</strong></td><td></td><td style="width:30%;"><strong>Teléfono Móvil:</strong></td><td></td></tr>
                        <tr><td><strong>Dirección Domiciliaria:</strong></td><td colspan="3"></td></tr>
                        <tr><td><strong>Contacto en Emergencia:</strong></td><td></td><td><strong>Relación:</strong></td><td></td></tr>
                        <tr><td><strong>Teléfono Emergencia:</strong></td><td colspan="3"></td></tr>
                    </table>

                    <div class="doc-section-title">IV. FORMACIÓN ACADÉMICA</div>
                    <table>
                        <tr><td style="width:35%;"><strong>Nivel de Estudio:</strong></td><td></td><td style="width:30%;"><strong>Título Profesional:</strong></td><td></td></tr>
                        <tr><td><strong>Código IESS:</strong></td><td colspan="3"></td></tr>
                    </table>

                    <div class="doc-section-title">V. OBSERVACIONES</div>
                    <table><tr><td style="height:60px;vertical-align:top;"><strong>Notas Internas:</strong><br></td></tr></table>

                    <div style="margin-top:30px;display:flex;justify-content:space-between;">
                        <div style="text-align:center;width:40%;">
                            <div style="border-top:1px solid #333;padding-top:4px;margin-top:40px;">Firma del Funcionario</div>
                        </div>
                        <div style="text-align:center;width:40%;">
                            <div style="border-top:1px solid #333;padding-top:4px;margin-top:40px;">Responsable de Talento Humano</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Acción de Personal -->
    <div class="preview-overlay" id="preview-accion" role="dialog" aria-modal="true">
        <div class="preview-modal">
            <div class="preview-modal-header">
                <h4><i class="bi bi-file-earmark-text-fill"></i> Acción de Personal — APM-TH-FO-002</h4>
                <button class="preview-modal-close" onclick="cerrarPreview('preview-accion')" aria-label="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="preview-modal-toolbar">
                <button class="btn-lib btn-lib--primary" onclick="imprimirModal('preview-accion')">
                    <i class="bi bi-printer-fill"></i> Imprimir
                </button>
                <a href="<?= BASE_URL ?>/talento-humano/accion-personal" class="btn-lib btn-lib--ghost">
                    <i class="bi bi-pencil-square"></i> Abrir formulario
                </a>
            </div>
            <div class="preview-modal-body">
                <div class="doc-a4">
                    <table class="doc-header-table">
                        <tr>
                            <td class="doc-logo-cell" rowspan="2">
                                <img src="<?= IMG_URL ?>/logoapm.png" alt="Logo APM" style="width:55px;">
                                <div style="font-size:7pt;font-weight:bold;margin-top:4px;">MANTA</div>
                            </td>
                            <td class="doc-title-cell" rowspan="2" style="font-size:13pt;">
                                ACCIÓN DE PERSONAL<br>
                                <span style="font-size:9pt;font-weight:normal;">Autoridad Portuaria de Manta</span>
                            </td>
                            <td class="doc-code-cell">
                                <strong>Código:</strong> APM-TH-FO-002<br>
                                <strong>Fecha:</strong> <?= date('d/m/Y') ?><br>
                                <strong>Nro:</strong> APM-TH-<?= date('Y') ?>-001
                            </td>
                        </tr>
                    </table>

                    <table style="margin-bottom:14px;">
                        <tr><th colspan="4">DATOS DEL SERVIDOR</th></tr>
                        <tr><td style="width:25%;"><strong>Nombres y Apellidos:</strong></td><td></td><td style="width:25%;"><strong>Cédula:</strong></td><td></td></tr>
                        <tr><td><strong>Cargo:</strong></td><td></td><td><strong>Unidad:</strong></td><td></td></tr>
                    </table>

                    <table style="margin-bottom:14px;">
                        <tr>
                            <th style="width:50%;">SITUACIÓN ACTUAL</th>
                            <th style="width:50%;">SITUACIÓN PROPUESTA</th>
                        </tr>
                        <tr>
                            <td style="vertical-align:top;padding:8px;">
                                <table style="border:none;">
                                    <tr><td style="border:none;"><strong>Cargo:</strong></td><td style="border:none;"></td></tr>
                                    <tr><td style="border:none;"><strong>Unidad:</strong></td><td style="border:none;"></td></tr>
                                    <tr><td style="border:none;"><strong>Remuneración:</strong></td><td style="border:none;"></td></tr>
                                    <tr><td style="border:none;"><strong>Tipo Contrato:</strong></td><td style="border:none;"></td></tr>
                                    <tr><td style="border:none;"><strong>Fecha Inicio:</strong></td><td style="border:none;"></td></tr>
                                </table>
                            </td>
                            <td style="vertical-align:top;padding:8px;">
                                <table style="border:none;">
                                    <tr><td style="border:none;"><strong>Cargo:</strong></td><td style="border:none;"></td></tr>
                                    <tr><td style="border:none;"><strong>Unidad:</strong></td><td style="border:none;"></td></tr>
                                    <tr><td style="border:none;"><strong>Remuneración:</strong></td><td style="border:none;"></td></tr>
                                    <tr><td style="border:none;"><strong>Tipo Contrato:</strong></td><td style="border:none;"></td></tr>
                                    <tr><td style="border:none;"><strong>Fecha Inicio:</strong></td><td style="border:none;"></td></tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <table style="margin-bottom:14px;">
                        <tr><th colspan="2">MOTIVO DE LA ACCIÓN</th></tr>
                        <tr><td style="width:35%;"><strong>Tipo de Acción:</strong></td><td></td></tr>
                        <tr><td><strong>Justificación:</strong></td><td style="height:50px;vertical-align:top;"></td></tr>
                        <tr><td><strong>Normativa Legal:</strong></td><td></td></tr>
                    </table>

                    <div style="margin-top:30px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:16px;">
                        <div style="text-align:center;min-width:150px;">
                            <div style="border-top:1px solid #333;padding-top:4px;margin-top:40px;">Elaborado por</div>
                        </div>
                        <div style="text-align:center;min-width:150px;">
                            <div style="border-top:1px solid #333;padding-top:4px;margin-top:40px;">Revisado por</div>
                        </div>
                        <div style="text-align:center;min-width:150px;">
                            <div style="border-top:1px solid #333;padding-top:4px;margin-top:40px;">Aprobado por</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Estudio de Seguridad Socioeconómico -->
    <div class="preview-overlay" id="preview-seguridad" role="dialog" aria-modal="true">
        <div class="preview-modal">
            <div class="preview-modal-header">
                <h4><i class="bi bi-shield-shaded"></i> Estudio de Seguridad Socioeconómico — APM-BASC-TH-FO-002</h4>
                <button class="preview-modal-close" onclick="cerrarPreview('preview-seguridad')" aria-label="Cerrar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="preview-modal-toolbar">
                <button class="btn-lib btn-lib--primary" onclick="imprimirModal('preview-seguridad')">
                    <i class="bi bi-printer-fill"></i> Imprimir
                </button>
                <a href="<?= BASE_URL ?>/talento-humano/estudio-seguridad" class="btn-lib btn-lib--ghost">
                    <i class="bi bi-pencil-square"></i> Abrir formulario
                </a>
            </div>
            <div class="preview-modal-body" id="preview-seguridad-body">
                <!-- El contenido del doc A4 se comparte con la vista estudio_seguridad.php -->
                <div class="doc-a4">
                    <table class="doc-header-table">
                        <tr>
                            <td class="doc-logo-cell" rowspan="2">
                                <img src="<?= IMG_URL ?>/logoapm.png" alt="Logo APM" style="width:55px;">
                                <div style="font-size:7pt;font-weight:bold;margin-top:4px;">MANTA</div>
                            </td>
                            <td class="doc-title-cell" rowspan="2" style="font-size:12pt;">
                                FORMATO ESTUDIO DE SEGURIDAD - SOCIO ECONÓMICO<br>
                                <span style="font-size:8.5pt;font-weight:normal;">Autoridad Portuaria de Manta</span>
                            </td>
                            <td class="doc-code-cell">
                                <strong>Código:</strong> APM-BASC-TH-FO-002<br>
                                <strong>Fecha:</strong> 01/04/2019<br>
                                <strong>Página 1 de 4</strong>
                            </td>
                        </tr>
                    </table>

                    <p style="font-size:9pt;margin-bottom:12px;">
                        Autoridad Portuaria de Manta requiere la siguiente información para el estudio de seguridad de la institución, estos datos serán clasificados como información confidencial y podrán ser presentados ante las autoridades de control cuando así lo requieran.<br><br>
                        La información que será registrada en el presente formato, será confirmada por servidores de la entidad.
                    </p>
                    <table style="border:none;margin-bottom:8px;">
                        <tr>
                            <td style="border:none;"><strong>Fecha de Vinculación:</strong> ___________________________</td>
                            <td style="border:none;"><strong>Cargo:</strong> ___________________________</td>
                        </tr>
                        <tr><td colspan="2" style="border:none;"><strong>Nombre:</strong> ___________________________</td></tr>
                    </table>

                    <div class="doc-section-title">I. INFORMACIÓN GENERAL</div>
                    <table>
                        <tr><th colspan="4">INFORMACIÓN DEL SERVIDOR</th></tr>
                        <tr><td style="width:30%;"><strong>TIPO DE DOCUMENTO IDENT:</strong></td><td></td><td><strong>Nº DE DOCUMENTO:</strong></td><td></td></tr>
                        <tr><td><strong>NACIONALIDAD:</strong></td><td></td><td><strong>AÑOS DE RESIDENCIA (Extranjeros):</strong></td><td></td></tr>
                        <tr><td><strong>LIBRETA MILITAR (SI-NO):</strong></td><td></td><td><strong>Nº LIBRETA MILITAR:</strong></td><td></td></tr>
                        <tr><td><strong>RELACIÓN (SERVIDOR-PASANTE-CONVENIO):</strong></td><td colspan="3"></td></tr>
                        <tr><td><strong>APELLIDOS:</strong></td><td></td><td><strong>NOMBRES:</strong></td><td></td></tr>
                        <tr><td><strong>FECHA DE NACIMIENTO:</strong></td><td></td><td><strong>EDAD:</strong></td><td></td></tr>
                        <tr><td><strong>LUGAR DE NACIMIENTO:</strong></td><td></td><td><strong>PROVINCIA - CIUDAD NAC:</strong></td><td></td></tr>
                        <tr><td><strong>GÉNERO:</strong></td><td></td><td><strong>TIPO DE SANGRE:</strong></td><td></td></tr>
                        <tr><td><strong>ESTADO CIVIL:</strong></td><td></td><td><strong>DISCAPACIDAD:</strong></td><td></td></tr>
                        <tr><td><strong>TIPO DE DISCAPACIDAD:</strong></td><td></td><td><strong>Nº CARNET CONADIS:</strong></td><td></td></tr>
                        <tr><td><strong>SERVIDOR CARRERA:</strong></td><td></td><td><strong>Nº:</strong></td><td></td></tr>
                        <tr><td><strong>AUTO IDENTIFICACIÓN ÉTNICA:</strong></td><td></td><td><strong>NACIONALIDAD INDÍGENA:</strong></td><td></td></tr>
                        <tr><td><strong>DIRECCIÓN CALLE PRINCIPAL:</strong></td><td></td><td><strong>NÚMERO:</strong></td><td></td></tr>
                        <tr><td><strong>CALLE SECUNDARIA:</strong></td><td></td><td><strong>PARROQUIA:</strong></td><td></td></tr>
                        <tr><td><strong>CANTON:</strong></td><td></td><td><strong>PROVINCIA:</strong></td><td></td></tr>
                        <tr><td><strong>REFERENCIA DOMICILIARIA:</strong></td><td colspan="3"></td></tr>
                        <tr><td><strong>TELÉFONO DOMICILIO:</strong></td><td></td><td><strong>TELÉFONO CELULAR:</strong></td><td></td></tr>
                        <tr><td><strong>TELÉFONO TRABAJO:</strong></td><td></td><td><strong>NÚMERO DE EXTENSIÓN:</strong></td><td></td></tr>
                        <tr><td><strong>CORREO ELECTRÓNICO:</strong></td><td colspan="3"></td></tr>
                        <tr><td><strong>CORREO ALTERNATIVO:</strong></td><td colspan="3"></td></tr>
                    </table>

                    <table style="margin-top:10px;">
                        <tr><th colspan="4">DATOS DE CONTACTO</th></tr>
                        <tr><td style="width:40%;"><strong>NOMBRES Y APELLIDOS:</strong></td><td colspan="3"></td></tr>
                        <tr><td><strong>PARENTESCO CON EL SERVIDOR:</strong></td><td colspan="3"></td></tr>
                        <tr><td><strong>TELÉFONO CONVENCIONAL:</strong></td><td></td><td><strong>TELÉFONO CELULAR:</strong></td><td></td></tr>
                    </table>

                    <table style="margin-top:10px;">
                        <tr><th colspan="4">DECLARACIÓN DE BIENES</th></tr>
                        <tr><td style="width:40%;"><strong>Nº DE OTORGAMIENTO:</strong></td><td></td><td><strong>FECHA DE INGRESO:</strong></td><td></td></tr>
                    </table>

                    <!-- Página 2 -->
                    <div style="border-top:2px dashed #bbb;margin:20px 0;padding-top:12px;">
                        <table class="doc-header-table">
                            <tr>
                                <td class="doc-logo-cell"><img src="<?= IMG_URL ?>/logoapm.png" alt="" style="width:55px;"></td>
                                <td class="doc-title-cell">FORMATO ESTUDIO DE SEGURIDAD - SOCIO ECONÓMICO</td>
                                <td class="doc-code-cell"><strong>Código:</strong> APM-BASC-TH-FO-002<br><strong>Fecha:</strong> 01/04/2019<br><strong>Página 2 de 4</strong></td>
                            </tr>
                        </table>

                        <table style="margin-top:8px;">
                            <tr><th colspan="4">INFORMACIÓN BANCARIA</th></tr>
                            <tr><td style="width:40%;"><strong>INSTITUCIÓN BANCARIA:</strong></td><td></td><td><strong>TIPO DE CUENTA:</strong></td><td></td></tr>
                            <tr><td><strong>Nº DE CUENTA:</strong></td><td colspan="3"></td></tr>
                        </table>

                        <div class="doc-section-title">II. GRUPO FAMILIAR</div>
                        <table style="margin-bottom:10px;">
                            <tr><th colspan="4">INFORMACIÓN DEL CÓNYUGE</th></tr>
                            <tr><td style="width:40%;"><strong>NOMBRES Y APELLIDOS:</strong></td><td colspan="3"></td></tr>
                            <tr><td><strong>TIPO DE DOCUMENTO IDENT:</strong></td><td></td><td><strong>FECHA DE NACIMIENTO:</strong></td><td></td></tr>
                            <tr><td><strong>TIPO DE RELACIÓN:</strong></td><td></td><td><strong>NIVEL DE INSTRUCCIÓN:</strong></td><td></td></tr>
                            <tr><td><strong>OCUPACIÓN:</strong></td><td colspan="3"></td></tr>
                        </table>

                        <table style="margin-bottom:10px;">
                            <tr>
                                <th rowspan="2">INFORMACIÓN DE HIJOS<br><span style="font-weight:normal;font-size:8pt;">Nº DE HIJOS DE MENOR A MAYOR</span></th>
                                <th>1</th><th>2</th><th>3</th>
                            </tr>
                            <tr><td></td><td></td><td></td></tr>
                            <tr><td><strong>NOMBRES Y APELLIDOS:</strong></td><td></td><td></td><td></td></tr>
                            <tr><td><strong>FECHA DE NACIMIENTO:</strong></td><td></td><td></td><td></td></tr>
                            <tr><td><strong>TIPO DE DOCUMENTO:</strong></td><td></td><td></td><td></td></tr>
                            <tr><td><strong>NÚMERO DE DOCUMENTO:</strong></td><td></td><td></td><td></td></tr>
                            <tr><td><strong>EDAD:</strong></td><td></td><td></td><td></td></tr>
                            <tr><td><strong>NIVEL DE INSTRUCCIÓN:</strong></td><td></td><td></td><td></td></tr>
                            <tr><td><strong>OCUPACIÓN:</strong></td><td></td><td></td><td></td></tr>
                        </table>

                        <div class="doc-section-title">III. INFORMACIÓN ACADÉMICA</div>
                        <table style="margin-bottom:10px;">
                            <tr><th colspan="2">INSTRUCCIÓN</th></tr>
                            <tr><td style="width:40%;"><strong>NIVEL DE INSTRUCCIÓN:</strong></td><td></td></tr>
                            <tr><td><strong>INSTITUCIÓN EDUCATIVA:</strong></td><td></td></tr>
                            <tr><td><strong>TIPO DE PERÍODO:</strong></td><td></td></tr>
                            <tr><td><strong>ÁREA DE CONOCIMIENTO:</strong></td><td></td></tr>
                            <tr><td><strong>EGRESADO (SI - NO):</strong></td><td></td></tr>
                            <tr><td><strong>TÍTULO:</strong></td><td></td></tr>
                        </table>

                        <table>
                            <tr><th colspan="2">INFORMACIÓN SOBRE CAPACITACIONES</th></tr>
                            <tr><td style="width:40%;"><strong>EVENTO:</strong></td><td></td></tr>
                            <tr><td><strong>TIPO DE EVENTO/CAPACIT.:</strong></td><td></td></tr>
                            <tr><td><strong>AUSPICIANTE:</strong></td><td></td></tr>
                            <tr><td><strong>TIPO DE CERTIFICADO:</strong></td><td></td></tr>
                            <tr><td><strong>CERTIFICADO POR:</strong></td><td></td></tr>
                            <tr><td><strong>FECHA DE INICIO:</strong></td><td></td></tr>
                        </table>
                    </div>

                    <!-- Página 3 -->
                    <div style="border-top:2px dashed #bbb;margin:20px 0;padding-top:12px;">
                        <table class="doc-header-table">
                            <tr>
                                <td class="doc-logo-cell"><img src="<?= IMG_URL ?>/logoapm.png" alt="" style="width:55px;"></td>
                                <td class="doc-title-cell">FORMATO ESTUDIO DE SEGURIDAD - SOCIO ECONÓMICO</td>
                                <td class="doc-code-cell"><strong>Código:</strong> APM-BASC-TH-FO-002<br><strong>Fecha:</strong> 01/04/2019<br><strong>Página 3 de 4</strong></td>
                            </tr>
                        </table>

                        <table style="margin-top:8px;margin-bottom:10px;">
                            <tr><th colspan="2">INFORMACIÓN SOBRE CAPACITACIONES (continuación)</th></tr>
                            <tr><td style="width:40%;"><strong>EVENTO 2:</strong></td><td></td></tr>
                            <tr><td><strong>TIPO DE EVENTO/CAPACIT.:</strong></td><td></td></tr>
                            <tr><td><strong>AUSPICIANTE:</strong></td><td></td></tr>
                            <tr><td><strong>TIPO DE CERTIFICADO:</strong></td><td></td></tr>
                            <tr><td><strong>CERTIFICADO POR:</strong></td><td></td></tr>
                            <tr><td><strong>FECHA DE INICIO:</strong></td><td></td></tr>
                        </table>

                        <div class="doc-section-title">IV. EXPERIENCIA LABORAL (3 últimos empleos si los hubiere tenido)</div>
                        <table>
                            <tr>
                                <th>NOMBRE DE INSTITUCIÓN</th>
                                <th>TIPO DE INSTIT.</th>
                                <th>UNIDAD ADMINIST.</th>
                                <th>CARGO</th>
                                <th>ANTIGÜEDAD</th>
                                <th>JEFE INMEDIATO</th>
                                <th>TELEF.</th>
                                <th>FECHA INGRESO</th>
                                <th>MOTIVO INGRESO</th>
                                <th>FECHA RETIRO</th>
                                <th>MOTIVO RETIRO</th>
                            </tr>
                            <tr><td style="height:28px;"></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                            <tr><td style="height:28px;"></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                            <tr><td style="height:28px;"></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                        </table>

                        <table style="margin-top:12px;">
                            <tr>
                                <th colspan="3">VIVIENDA</th>
                                <th colspan="4">VEHÍCULO</th>
                            </tr>
                            <tr>
                                <td><strong>PROPIA</strong></td>
                                <td><strong>ARRENDADA</strong></td>
                                <td><strong>OTROS</strong></td>
                                <td><strong>MARCA</strong></td>
                                <td><strong>MODELO</strong></td>
                                <td><strong>PLACA</strong></td>
                                <td><strong>VALOR</strong></td>
                            </tr>
                            <tr><td style="height:22px;"></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                        </table>

                        <p class="doc-note">
                            <strong>Nota:</strong> Certifico que la información aquí suministrada es verdadera y podrá ser verificada en cualquier momento
                            por la institución. Así mismo estoy dispuesto a brindar una ampliación de cualquier aspecto de los datos registrados.
                        </p>

                        <div style="margin-top:40px;display:flex;justify-content:space-between;">
                            <div style="text-align:center;width:40%;">
                                <div style="border-top:1px solid #333;padding-top:4px;margin-top:50px;">Firma del Servidor</div>
                            </div>
                            <div style="text-align:center;width:40%;">
                                <div style="border-top:1px solid #333;padding-top:4px;margin-top:50px;">Responsable de Seguridad</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /preview-seguridad -->

    <script>
        /* Fecha actual */
        document.getElementById('currentDate').textContent =
            new Date().toLocaleDateString('es-EC', { day:'2-digit', month:'long', year:'numeric' });

        /* Abrir/cerrar modales de vista previa */
        function abrirPreview(id) {
            document.getElementById(id).classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function cerrarPreview(id) {
            document.getElementById(id).classList.remove('open');
            document.body.style.overflow = '';
        }

        /* Abrir modal de registros guardados */
        function abrirRegistros(id) {
            document.getElementById(id).classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        /* Filtrar tabla de registros */
        function filtrarTabla(tablaId, inputId) {
            const q = document.getElementById(inputId).value.toLowerCase();
            document.querySelectorAll('#' + tablaId + ' tbody tr').forEach(tr => {
                const txt = (tr.dataset.search || tr.textContent).toLowerCase();
                tr.style.display = txt.includes(q) ? '' : 'none';
            });
        }

        /* Cerrar con Escape */
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.preview-overlay.open').forEach(el => {
                    el.classList.remove('open');
                    document.body.style.overflow = '';
                });
            }
        });

        /* Cerrar click en overlay */
        document.querySelectorAll('.preview-overlay').forEach(el => {
            el.addEventListener('click', e => {
                if (e.target === el) cerrarPreview(el.id);
            });
        });

        /* Imprimir contenido del modal */
        function imprimirModal(id) {
            const modal = document.getElementById(id);
            const body  = modal.querySelector('.doc-a4');
            const originalBody = document.body.innerHTML;
            document.body.innerHTML = body.outerHTML;
            window.print();
            document.body.innerHTML = originalBody;
            location.reload();
        }
    </script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
