<?php
/* accion_personal.php – Vista: Formulario Fase 2 – Acción de Personal (APM)
   Carga datos del empleado seleccionado (situación actual) y permite capturar
   la situación propuesta. Completamente reactivo con JavaScript Vanilla.
   Sin dependencias externas de Bootstrap; usa el design system propio del portal. */

$emp  = $empleado    ?? [];
$hist = $historialLab ?? [];
$nro  = $nroAccion   ?? ('APM-TH-' . date('Y') . '-001');
$preselCedula = $preselCedula ?? '';
$tipoPreseleccionado = $tipoPreseleccionado ?? '';
$accionEdicion = $accionEdicion ?? null;
$esEdicion = is_array($accionEdicion) && !empty($accionEdicion['accion_id']);
$accionIdEdicion = $esEdicion ? (int)$accionEdicion['accion_id'] : 0;
$regimenDocumento = strtoupper((string)($regimenDocumento ?? $accionEdicion['regimen_laboral'] ?? $emp['regimen_laboral'] ?? 'LOSEP'));
$usuarioDocumento = Auth::user() ?? [];
$responsableDocumento = trim((string)($usuarioDocumento['name'] ?? Auth::username()));
$puestoResponsableDocumento = trim((string)($usuarioDocumento['role'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acción de Personal | Talento Humano APM</title>
    <meta name="description" content="Formulario de Acción de Personal — Autoridad Portuaria de Manta.">
    <?php require ROOT . '/shared/head_assets.php'; ?>
    <style>
        /* ── Estilos exclusivos de este formulario ─────────────────────────── */
        .accion-header {
            background: linear-gradient(135deg, var(--navy-800) 0%, #1e3a5f 100%);
            border-radius: 20px;
            padding: 22px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 24px;
        }
        .accion-header h2 { color: #fff; font-size: 1.35rem; margin: 0; }
        .accion-header p  { color: rgba(255,255,255,.7); font-size: .82rem; margin: 2px 0 0; }
        .accion-num {
            background: rgba(18,180,199,.22);
            border: 1px solid rgba(18,180,199,.4);
            color: #a5f3fc;
            padding: 6px 18px;
            border-radius: 999px;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .06em;
        }
        .document-summary {
            position: sticky;
            top: var(--topbar-height, 86px);
            z-index: 24;
            display: grid;
            grid-template-columns: minmax(175px,1.15fr) repeat(4,minmax(115px,.75fr));
            gap: 10px;
            margin: -12px 14px 18px;
            padding: 11px;
            border: 1px solid rgba(14,116,144,.22);
            border-radius: 15px;
            background: rgba(255,255,255,.96);
            box-shadow: 0 12px 28px rgba(15,39,64,.12);
            backdrop-filter: blur(12px);
        }
        .document-summary.is-abbreviated { grid-template-columns:minmax(175px,1.15fr) repeat(3,minmax(130px,.8fr)); }
        .document-summary__item { min-width:0;padding:7px 11px;border-right:1px solid #dbe8ef; }
        .document-summary__item:last-child { border-right:0; }
        .document-summary__label { display:block;margin-bottom:3px;color:#64748b;font-size:.66rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase; }
        .document-summary__value { display:flex;align-items:center;gap:7px;min-height:23px;color:#0f2740;font-size:.82rem;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .document-summary__value .accion-num { padding:4px 11px;background:#e6f9fc;border-color:#9bdce5;color:#0e7490;font-size:.78rem; }
        .document-summary__note { display:block;margin-top:2px;color:#64748b;font-size:.64rem;font-weight:500; }
        /* Banner buscador de funcionario */
        .buscador-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            background: linear-gradient(135deg, rgba(14,116,144,.08), rgba(6,182,212,.04));
            border: 1px solid rgba(14,116,144,.3);
            border-radius: 14px;
            padding: 14px 20px;
            margin-bottom: 22px;
        }
        .buscador-banner span { font-size: .84rem; }
        .personal-autocomplete { position: relative; flex: 1; }
        .personal-results {
            position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 40;
            display: none; max-height: 300px; overflow-y: auto; background: #fff;
            border: 1px solid rgba(14,116,144,.3); border-radius: 12px;
            box-shadow: 0 18px 45px rgba(15,23,42,.18);
        }
        .personal-results.open { display: block; }
        .personal-result {
            width: 100%; border: 0; border-bottom: 1px solid #e5edf3; background: #fff;
            padding: 10px 12px; text-align: left; cursor: pointer; color: #0f2740;
        }
        .personal-result:last-child { border-bottom: 0; }
        .personal-result:hover, .personal-result:focus { background: #ecfeff; outline: none; }
        .personal-result strong { display: block; font-size: .82rem; }
        .personal-result small { display: block; color: #587086; margin-top: 2px; }
        /* Secciones */
        .section-sep {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 24px 0 16px;
        }
        .section-sep::before,
        .section-sep::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(15,23,42,.1);
        }
        .section-sep span {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .15em;
            text-transform: uppercase;
            color: var(--teal-600, #0e7490);
            background: var(--surface);
            padding: 0 12px;
        }
        /* Split-screen comparativa */
        .split-wrap {
            display: grid;
            grid-template-columns: 1fr 1px 1fr;
            gap: 0 24px;
            align-items: start;
        }
        .split-divider {
            background: rgba(15,23,42,.12);
            align-self: stretch;
            border-radius: 2px;
        }
        .split-panel-title {
            text-align: center;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 16px;
        }
        .split-actual  { background: rgba(15,23,42,.06);  color: var(--navy-700); }
        .split-propuesta { background: rgba(18,180,199,.12); color: var(--teal-700, #0e7490); }
        .accion-field { margin-bottom: 14px; }
        .accion-field label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: var(--navy-600, #334155);
            margin-bottom: 5px;
        }
        .accion-field input,
        .accion-field select,
        .accion-field textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 9px 13px;
            border: 1px solid rgba(15,23,42,.18);
            border-radius: 10px;
            font-size: .85rem;
            background: #f8fafc;
            color: var(--navy-800, #1e293b);
            transition: border-color .2s;
        }
        .accion-field input:focus,
        .accion-field select:focus,
        .accion-field textarea:focus {
            outline: none;
            border-color: var(--teal-400, #22d3ee);
            background: #fff;
        }
        .accion-field input[readonly], .accion-field input:disabled,
        .accion-field select:disabled { background: rgba(15,23,42,.04); color: #64748b; cursor: default; }
        .accion-field .req { color: #ef4444; margin-left: 2px; }
        .vigencia-selector,.capture-selector { display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:10px 0 16px; }
        #resumenVigenciaItem[hidden],
        [data-losep-only][hidden] { display:none !important; }
        .vigencia-option,.capture-option { display:flex;gap:10px;align-items:flex-start;padding:13px 15px;border:1px solid #cbdce7;border-radius:12px;background:#f8fafc;cursor:pointer; }
        .vigencia-option:has(input:checked),.capture-option:has(input:checked) { border-color:#0e7490;background:#ecfeff;box-shadow:0 0 0 2px rgba(14,116,144,.08); }
        .vigencia-option input,.capture-option input { width:auto;margin-top:3px;accent-color:#0e7490; }
        .vigencia-option strong,.capture-option strong { display:block;color:#0f2740;font-size:.84rem; }
        .vigencia-option small,.capture-option small { display:block;color:#587086;font-size:.72rem;line-height:1.4;margin-top:2px; }
        .vigencia-help { padding:10px 13px;border-radius:10px;background:#eff6ff;color:#1e4f79;font-size:.76rem;margin-bottom:14px; }
        .compact-schedule-note { display:none;padding:12px 14px;margin-bottom:14px;border-left:4px solid #0e7490;background:#ecfeff;border-radius:10px;color:#155e75;font-size:.78rem; }
        #panelActual.compact-schedule-current > .accion-field:not(.schedule-current) { display:none; }
        #panelActual.compact-schedule-current .schedule-current { padding:16px;border:1px solid #cfe3ed;border-radius:12px;background:#f8fcfe; }
        /* Tipo de acción: chips */
        .tipo-accion-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .tipo-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: 2px solid rgba(15,23,42,.14);
            border-radius: 12px;
            cursor: pointer;
            font-size: .82rem;
            font-weight: 600;
            color: #475569;
            transition: all .2s;
            background: #fff;
            white-space: nowrap;
        }
        .tipo-chip:hover { border-color: var(--teal-400, #22d3ee); color: var(--teal-600, #0e7490); }
        .tipo-chip.selected {
            border-color: var(--teal-500, #06b6d4);
            background: rgba(18,180,199,.1);
            color: var(--teal-700, #0e7490);
        }
        .tipo-chip i { font-size: 1.1rem; }
        /* Firmas / motivación */
        .firma-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 700px) {
            .split-wrap  { grid-template-columns: 1fr; }
            .split-divider { display: none; }
            .firma-grid  { grid-template-columns: 1fr; }
            .document-summary { position:static;grid-template-columns:1fr 1fr;margin:-12px 0 16px; }
            .document-summary__item { border-right:0;border-bottom:1px solid #dbe8ef; }
        }
        /* Barra de acciones del formulario */
        .accion-actions {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
            padding: 20px 0 8px;
            border-top: 1px solid rgba(15,23,42,.1);
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .email-recipient-box { margin-top:8px;padding:9px;border:1px solid rgba(15,23,42,.18);border-radius:10px;background:#f8fafc; }
        .email-recipient-box.is-disabled { opacity:.55;pointer-events:none; }
        .email-recipient-row { display:flex;gap:7px;align-items:center; }
        .email-recipient-row input { margin:0; }
        .email-recipient-add { flex:0 0 auto;border:0;border-radius:8px;padding:9px 11px;background:#0e7490;color:#fff;cursor:pointer;font-weight:700; }
        .email-recipient-chips { display:flex;flex-wrap:wrap;gap:6px;margin-top:8px; }
        .email-recipient-chip { display:inline-flex;align-items:center;gap:6px;max-width:100%;padding:5px 8px;border:1px solid #bae6fd;border-radius:8px;background:#ecfeff;color:#155e75;font-size:.74rem;font-weight:600; }
        .email-recipient-chip span { overflow:hidden;text-overflow:ellipsis; }
        .email-recipient-chip button { border:0;background:transparent;color:#dc2626;cursor:pointer;padding:0;line-height:1; }
        .edit-lock-note { margin:0 0 18px;padding:10px 14px;border-left:4px solid #d97706;border-radius:9px;background:#fffbeb;color:#92400e;font-size:.78rem; }
    </style>
</head>
<body>
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()"
        title="Abrir menú lateral" aria-label="Abrir menú lateral">
    <i class="bi bi-layout-sidebar"></i>
</button>

<div class="app">
    <?php require_once ROOT . '/shared/menu.php'; ?>

    <section class="content">
        <?php
        $topbarShowSearch=true;
        $topbarBackUrl=BASE_URL.($esEdicion?'/talento-humano/biblioteca':'/talento-humano/directorio');
        $topbarBackLabel=$esEdicion?'Volver a Biblioteca':'Volver al Directorio';
        require ROOT.'/shared/topbar.php';
        ?>

        <main class="main">
            <div class="content-shell">
                <!-- ENCABEZADO DEL DOCUMENTO -->
                <div class="accion-header">
                    <div>
                        <h2 id="tituloDocumentoLaboral"><i class="bi bi-file-earmark-text"></i> <?= $regimenDocumento==='CODIGO_TRABAJO'?'Formulario Abreviado Laboral':($esEdicion?'Editar borrador de Acción de Personal':'Formulario de Acción de Personal') ?></h2>
                        <p id="subtituloDocumentoLaboral"><?= $regimenDocumento==='CODIGO_TRABAJO'?'Código del Trabajo — documento laboral abreviado de la APM':($esEdicion?'Corrija la información antes de aprobar o rechazar el documento.':'Art. 21 LOSEP — Registro oficial de movimiento de personal de la APM') ?></p>
                    </div>
                </div>

                <section class="document-summary<?= $regimenDocumento==='CODIGO_TRABAJO'?' is-abbreviated':'' ?>" aria-label="Resumen del documento">
                    <div class="document-summary__item">
                        <span class="document-summary__label">Código previsto</span>
                        <span class="document-summary__value"><i class="bi bi-upc-scan"></i><span class="accion-num" id="displayNroAccion"><?= htmlspecialchars($nro) ?></span></span>
                        <small class="document-summary__note"><?= $esEdicion?'La serie y el funcionario permanecen protegidos.':'SQL Server confirma el correlativo definitivo al guardar.' ?></small>
                    </div>
                    <div class="document-summary__item">
                        <span class="document-summary__label">Tipo de acción</span>
                        <span class="document-summary__value" id="displayTipoAccion"><i class="bi bi-list-check"></i> Pendiente de selección</span>
                    </div>
                    <div class="document-summary__item">
                        <span class="document-summary__label">Régimen / plantilla</span>
                        <span class="document-summary__value" id="displayRegimen"><i class="bi bi-shield-check"></i> <?= $regimenDocumento==='CODIGO_TRABAJO'?'Código del Trabajo · Abreviado':'LOSEP · Completo' ?></span>
                    </div>
                    <div class="document-summary__item" id="resumenVigenciaItem" <?= $regimenDocumento==='CODIGO_TRABAJO'?'hidden':'' ?>>
                        <span class="document-summary__label">Vigencia</span>
                        <span class="document-summary__value" id="displayVigencia"><i class="bi bi-infinity"></i> Permanente</span>
                    </div>
                    <div class="document-summary__item">
                        <span class="document-summary__label">Estado</span>
                        <span class="document-summary__value"><i class="bi bi-pencil-square"></i> <?= $esEdicion?'Editando borrador':'Borrador' ?></span>
                    </div>
                </section>

                <!-- BUSCADOR DE FUNCIONARIO (conectado a BD real via AJAX) -->
                <?php if($esEdicion): ?><div class="edit-lock-note"><i class="bi bi-shield-lock"></i> Está editando un documento pendiente. Puede corregir fechas, situación propuesta, motivación y notificación; el funcionario, el tipo y el código documental no se reemplazan.</div><?php endif; ?>
                <div class="buscador-banner">
                    <span>
                        <i class="bi bi-person-badge" style="color:#0e7490;"></i>
                        <strong><?= $esEdicion?'Funcionario del documento:':'Buscar funcionario:' ?></strong>
                        <?= $esEdicion?'La identidad está bloqueada para conservar la trazabilidad.':'Escriba la cédula, nombres o apellidos. Los resultados aparecen mientras escribe.' ?>
                    </span>
                    <div style="display:flex; flex-direction:column; gap:6px; min-width:340px;">
                        <div style="display:flex; gap:8px; align-items:center;">
                            <div class="personal-autocomplete">
                            <input type="search" id="inputBuscarCedula"
                                   placeholder="Cédula o Nombres"
                                   maxlength="100" autocomplete="off"
                                   style="padding:9px 14px; border:1.5px solid rgba(14,116,144,.4); border-radius:10px;
                                          font-size:.83rem; font-weight:600; background:#ecfeff; width:100%;
                                          outline:none; transition:border-color .2s; letter-spacing:.05em;"
                                   onfocus="this.style.borderColor='#0e7490'"
                                   onblur="this.style.borderColor='rgba(14,116,144,.4)'"
                                   aria-autocomplete="list" aria-controls="resultadosPersonalAccion" <?= $esEdicion?'readonly':'' ?>>
                            <div id="resultadosPersonalAccion" class="personal-results" role="listbox"></div>
                            </div>
                            <button type="button" onclick="buscarPersonaAccion()"
                                    id="btnBuscarServidor"
                                    style="padding:9px 16px; background:#0e7490; color:#fff; border:none;
                                           border-radius:10px; font-size:.83rem; font-weight:700; cursor:pointer;
                                           transition:background .2s; white-space:nowrap;"
                                    onmouseover="this.style.background='#0891b2'"
                                    onmouseout="this.style.background='#0e7490'" <?= $esEdicion?'disabled':'' ?>>
                                <i class="bi bi-search"></i> Seleccionar
                            </button>
                        </div>
                        <span id="estadoBusqueda" style="font-size:.73rem; color:#155e75; opacity:.8;">
                            <i class="bi bi-info-circle"></i> Puede buscar por cualquier parte del nombre o de la cédula.
                        </span>
                    </div>
                </div>

                <!-- FORMULARIO PRINCIPAL -->
                <section class="card form-card">
                    <form method="POST" action="<?= BASE_URL ?>/talento-humano/accion-personal/guardar"
                          id="formAccionPersonal" data-draft-context="accion-personal:<?= $esEdicion?'editar-'.$accionIdEdicion:'nueva' ?>"
                          onsubmit="return validarFormulario()">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
                        <input type="hidden" name="accion_id" value="<?= $accionIdEdicion ?>">

                        <!-- Campos ocultos: identificación y claves de situación actual -->
                        <input type="hidden" name="numero_accion"              id="nroAccionInput"        value="<?= htmlspecialchars($nro) ?>">
                        <input type="hidden" name="empleado_id"                id="hidEmpleadoId"         value="<?= (int)($emp['empleado_id'] ?? 0) ?>">
                        <input type="hidden" name="actual_unidad_id"           id="hidUnidadId"           value="0">
                        <input type="hidden" name="actual_puesto_id"           id="hidPuestoId"           value="0">
                        <!-- Campos hidden que AJAX llena al buscar la cédula (viajan al guardar()) -->
                        <input type="hidden" name="actual_unidad_id_hidden" id="hidActualUnidadId" value="<?= (int)($emp['unidad_id'] ?? 0) ?>">
                        <input type="hidden" name="actual_puesto_id_hidden" id="hidActualPuestoId" value="<?= (int)($emp['puesto_id'] ?? 0) ?>">
                        <input type="hidden" name="actual_remuneracion_hidden" id="hidActualRemuneracion" value="<?= htmlspecialchars($emp['sueldo_rmu'] ?? $emp['remuneracion_mensual'] ?? 0) ?>">

                        <!-- ══ SECCIÓN 1: DATOS DEL SERVIDOR ═══════════════════════════════ -->
                        <div class="section-sep"><span><i class="bi bi-person-badge"></i> 1 · Datos del Servidor Público</span></div>
                        <div style="display:grid; grid-template-columns:1fr 2fr; gap:14px; align-items:end;">
                            <div class="accion-field">
                                <label>Cédula de Identidad / Pasaporte</label>
                                <input type="text" id="inpCedula" name="cedula" readonly
                                       placeholder="Se llenará al seleccionar..."
                                       value="<?= htmlspecialchars($emp['cedula_pasaporte'] ?? $emp['cedula'] ?? $preselCedula) ?>">
                            </div>
                            <div class="accion-field">
                                <label>Apellidos y Nombres Completos</label>
                                <input type="text" id="inpNombres" name="nombres" readonly
                                       placeholder="Se llenará al seleccionar..."
                                       value="<?= htmlspecialchars($emp['apellidos_nombres'] ?? $emp['nombres'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="vigencia-selector" id="bloqueVigenciaLosep" data-losep-only role="radiogroup" aria-label="Modalidad de vigencia">
                            <label class="vigencia-option"><input type="radio" name="modalidad_vigencia" value="PERMANENTE" checked onchange="actualizarVigencia()"><span><strong>Permanente</strong><small>El cambio se mantiene hasta una nueva Acción de Personal.</small></span></label>
                            <label class="vigencia-option"><input type="radio" name="modalidad_vigencia" value="TEMPORAL" onchange="actualizarVigencia()"><span><strong>Temporal con retorno automático</strong><small>Al vencer, reaparece la situación laboral anterior sin emitir otra acción.</small></span></label>
                        </div>
                        <div class="vigencia-help" id="vigenciaHelp" data-losep-only><i class="bi bi-infinity"></i> Vigencia permanente: deje únicamente la fecha desde.</div>
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px;">
                            <div class="accion-field">
                                <label>Fecha de Elaboración</label>
                                <input type="date" name="fecha_elaboracion" id="inpFechaElab"
                                       value="<?= InstitutionalClock::todayIso() ?>" required>
                            </div>
                            <div class="accion-field">
                                <label>Rige desde</label>
                                <input type="date" name="rige_desde" id="inpRigeDesde"
                                       value="<?= InstitutionalClock::todayIso() ?>" required>
                            </div>
                            <div class="accion-field" id="campoRigeHasta" data-losep-only>
                                <label>Rige hasta <span class="req" id="reqRigeHasta" style="display:none">*</span></label>
                                <input type="date" name="rige_hasta" id="inpRigeHasta" disabled>
                            </div>
                        </div>

                        <!-- ══ SECCIÓN 2: TIPO DE ACCIÓN ════════════════════════════════════ -->
                        <div class="section-sep"><span id="tituloTipoDocumento"><i class="bi bi-list-check"></i> 2 · Motivo de la Acción (Art. 21 LOSEP)</span></div>
                        <input type="hidden" name="tipo_accion" id="hidTipoAccion" required>
                        <div class="tipo-accion-grid" id="tipoAccionGrid">
                            <?php foreach([
                                'INGRESO','REINGRESO','RESTITUCIÓN','REINTEGRO','ASCENSO','TRASLADO','SANCIONES',
                                'TRASPASO','CAMBIO ADMINISTRATIVO','INTERCAMBIO VOLUNTARIO','LICENCIA','COMISIÓN DE SERVICIOS',
                                'INCREMENTO RMU','SUBROGACIÓN','ENCARGO','CESACIÓN DE FUNCIONES','DESTITUCIÓN','VACACIONES',
                                'REVISIÓN CLASIFICACIÓN PUESTO','OTRO (DETALLAR)'
                            ] as $tipoOficial): ?>
                            <div class="tipo-chip" data-value="<?= htmlspecialchars($tipoOficial) ?>" onclick="seleccionarTipo(this)">
                                <i class="bi bi-check2-square"></i> <?= htmlspecialchars(ucwords(mb_strtolower($tipoOficial,'UTF-8'))) ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Campo detalle para "Otro" -->
                        <div id="bloqueOtro" style="display:none; margin-top:12px;">
                            <div class="accion-field">
                                <label>Detalle del motivo <span class="req">*</span></label>
                                <input type="text" name="explicacion_otro" id="inpOtroDetalle"
                                       placeholder="Describa brevemente el motivo de la acción...">
                            </div>
                        </div>

                        <!-- ══ SECCIÓN 3: COMPARATIVA SITUACIÓN ACTUAL VS PROPUESTA ════════ -->
                        <div class="section-sep"><span><i class="bi bi-columns-gap"></i> 3 · Situación Actual vs. Situación Propuesta</span></div>
                        <div class="capture-selector" id="bloqueCapturaLosep" data-losep-only role="radiogroup" aria-label="Tipo de captura">
                            <label class="capture-option"><input type="radio" name="modo_captura" value="CAMBIO_LABORAL" checked onchange="actualizarModoCaptura()"><span><strong>Cambio laboral</strong><small>Área, cargo, RMU, contrato u otras condiciones.</small></span></label>
                            <label class="capture-option"><input type="radio" name="modo_captura" value="JORNADA_TEMPORAL" onchange="actualizarModoCaptura()"><span><strong>Solo jornada temporal</strong><small>Lactancia, maternidad, sustitución u horario especial.</small></span></label>
                        </div>
                        <div class="compact-schedule-note" id="compactScheduleNote" data-losep-only><i class="bi bi-clock-history"></i> Complete únicamente la jornada temporal y sus fechas. El cargo y el área permanentes no serán modificados.</div>
                        <div class="split-wrap">

                            <!-- ── LADO IZQUIERDO: SITUACIÓN ACTUAL (readonly, BD) ── -->
                            <div id="panelActual">
                                <div class="split-panel-title split-actual">
                                    <i class="bi bi-lock-fill"></i> Situación Actual
                                    <span style="display:block; font-size:.68rem; font-weight:400; margin-top:2px;">
                                        Datos extraídos del expediente vigente
                                    </span>
                                </div>
                                <div class="accion-field">
                                    <label>Proceso Institucional</label>
                                    <input type="text" id="inpProcesoActual" name="actual_proceso" readonly
                                           value="<?= htmlspecialchars($hist['proceso_institucional'] ?? '') ?>">
                                </div>
                                <div class="accion-field"><label>Nivel de Gestión</label><input type="text" id="inpNivelActual" name="actual_nivel_gestion" readonly value="<?= htmlspecialchars($hist['nivel_gestion'] ?? '') ?>"></div>
                                <div class="accion-field">
                                    <label>Unidad Administrativa (Área)</label>
                                    <input type="text" id="inpUnidadActual" name="actual_unidad" readonly
                                           value="<?= htmlspecialchars($hist['direccion_departamento'] ?? '') ?>">
                                </div>
                                <div class="accion-field">
                                    <label>Denominación del Puesto</label>
                                    <input type="text" id="inpPuestoActual" name="actual_puesto" readonly
                                           value="<?= htmlspecialchars($hist['denominacion_puesto'] ?? '') ?>">
                                </div>
                                <div class="accion-field"><label>Lugar de Trabajo</label><input type="text" id="inpLugarActual" name="actual_lugar_trabajo" readonly value="<?= htmlspecialchars($hist['lugar_trabajo'] ?? 'Manta') ?>"></div>
                                <div class="accion-field"><label>Grupo Ocupacional</label><input type="text" id="inpGrupoActual" name="actual_grupo_ocupacional" readonly value="<?= htmlspecialchars($hist['grupo_ocupacional'] ?? '') ?>"></div>
                                <div class="accion-field"><label>Grado</label><input type="text" id="inpGradoActual" name="actual_grado" readonly value="<?= htmlspecialchars($hist['grado_laboral'] ?? $hist['grado'] ?? '') ?>"></div>
                                <div class="accion-field"><label>Partida Individual</label><input type="text" id="inpPartidaActual" name="actual_partida_presupuestaria" readonly value="<?= htmlspecialchars($hist['partida_individual'] ?? $hist['partida_presupuestaria'] ?? '') ?>"></div>
                                <?php $jornadaBase=(string)($hist['jornada']??'Completa');$horasBase=(float)($hist['horas_jornada']??8); ?>
                                <div class="accion-field schedule-current" data-losep-only><label>Jornada base vigente</label><input type="text" id="inpJornadaActualResumen" readonly value="<?= htmlspecialchars($jornadaBase.' — '.rtrim(rtrim(number_format($horasBase,1,'.',''),'0'),'.').' horas diarias') ?>"><input type="hidden" id="inpJornadaActual" name="actual_jornada" value="<?= htmlspecialchars($jornadaBase) ?>"><input type="hidden" id="inpHorasActual" name="actual_horas_jornada" value="<?= htmlspecialchars((string)$horasBase) ?>"></div>
                                <div class="accion-field">
                                    <label>Remuneración Mensual Unificada ($)</label>
                                    <input type="text" id="inpSueldoActual" name="actual_remuneracion" readonly
                                           style="font-weight:700; text-align:right;"
                                           value="<?= htmlspecialchars($hist['remuneracion'] ?? '') ?>">
                                </div>
                                <div class="accion-field">
                                    <label>Tipo de Contrato</label>
                                    <input type="text" id="inpContratoActual" name="actual_tipo_contrato" readonly
                                           value="<?= htmlspecialchars($hist['tipo_contrato'] ?? '') ?>">
                                </div>
                            </div>

                            <!-- Línea divisoria -->
                            <div class="split-divider"></div>

                            <!-- ── LADO DERECHO: SITUACIÓN PROPUESTA (editable) ── -->
                            <div id="panelPropuesta">
                                <div class="split-panel-title split-propuesta">
                                    <i class="bi bi-pencil-square"></i> Situación Propuesta
                                    <span style="display:block; font-size:.68rem; font-weight:400; margin-top:2px;">
                                        Complete solo los campos que cambian
                                    </span>
                                </div>
                                <div id="camposCambioLaboral">
                                <div class="accion-field">
                                    <label>Proceso Institucional Propuesto</label>
                                    <select class="inputs-propuesta" name="propuesta_proceso" id="propProceso">
                                        <option value="">Seleccione...</option>
                                        <option value="Procesos Gobernantes">Procesos Gobernantes</option>
                                        <option value="Procesos Sustantivos">Procesos Sustantivos</option>
                                        <option value="Procesos Adjetivos">Procesos Adjetivos</option>
                                    </select>
                                </div>
                                <div class="accion-field"><label>Nivel de Gestión Propuesto</label><input class="inputs-propuesta" type="text" name="propuesta_nivel_gestion"></div>
                                <div class="accion-field">
                                    <div class="label-with-action"><label for="propUnidad">Nueva Unidad Administrativa <span class="req">*</span></label><?php if(Auth::can('maestros','crear')): ?><button class="quick-add-button" type="button" onclick="abrirCatalogoRapido('unidad')" title="Crear unidad" aria-label="Crear unidad administrativa"><i class="bi bi-plus-lg"></i></button><?php endif; ?></div>
                                    <select class="inputs-propuesta" name="propuesta_unidad_id" id="propUnidad" data-searchable-select data-search-placeholder="Buscar unidad administrativa…">
                                        <option value="">Seleccione el área...</option>
                                        <?php foreach (($areas ?? []) as $area): ?>
                                        <option value="<?= (int)$area['unidad_id'] ?>">
                                            <?= htmlspecialchars($area['nombre_unidad']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="accion-field">
                                    <div class="label-with-action"><label for="propPuesto">Nueva Denominación del Puesto <span class="req">*</span></label><?php if(Auth::can('maestros','crear')): ?><button class="quick-add-button" type="button" onclick="abrirCatalogoRapido('puesto')" title="Crear cargo" aria-label="Crear cargo o puesto"><i class="bi bi-plus-lg"></i></button><?php endif; ?></div>
                                    <select class="inputs-propuesta" name="propuesta_puesto_id" id="propPuesto" data-searchable-select data-search-placeholder="Buscar cargo propuesto…">
                                        <option value="">Seleccione el cargo...</option>
                                        <?php foreach (($cargos ?? []) as $cargo): ?>
                                        <option value="<?= (int)$cargo['puesto_id'] ?>">
                                            <?= htmlspecialchars($cargo['nombre_puesto']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="accion-field">
                                    <label>Nueva Remuneración Mensual ($) <span class="req">*</span></label>
                                    <input type="number" step="0.01" min="0"
                                           class="inputs-propuesta" name="propuesta_remuneracion"
                                           id="propSueldo" placeholder="0.00"
                                           style="text-align:right; font-weight:700;">
                                </div>
                                <div class="accion-field"><label>Lugar de Trabajo Propuesto</label><input class="inputs-propuesta" type="text" name="propuesta_lugar_trabajo" value="Manta"></div>
                                <div class="accion-field"><label>Grupo Ocupacional Propuesto</label><input class="inputs-propuesta" type="text" name="propuesta_grupo_ocupacional"></div>
                                <div class="accion-field"><label>Grado Propuesto</label><input class="inputs-propuesta" type="text" name="propuesta_grado"></div>
                                <div class="accion-field"><label>Partida Individual Propuesta</label><input class="inputs-propuesta" type="text" name="propuesta_partida_presupuestaria"></div>
                                <div class="accion-field" id="campoContratoPropuesto">
                                    <label>Tipo de Contrato Propuesto</label>
                                    <select class="inputs-propuesta" name="propuesta_contrato" id="propContrato">
                                        <option value="">Sin cambio</option>
                                        <option value="Nombramiento">Nombramiento Permanente</option>
                                        <option value="Contrato">Contrato de Servicios</option>
                                        <option value="Contrato Ocasional">Contrato Ocasional</option>
                                    </select>
                                </div>
                                </div>
                                <div class="accion-field" data-losep-only>
                                    <label id="labelJornadaPropuesta">Jornada propuesta</label>
                                    <select class="inputs-propuesta" name="propuesta_jornada" id="propJornada">
                                        <option value="">Sin cambio</option><option value="Completa">Completa</option><option value="Parcial">Parcial</option><option value="Rotativa">Rotativa</option><option value="Especial">Especial</option><option value="Licencia">Licencia</option>
                                    </select>
                                </div>
                                <div class="accion-field" data-losep-only><label id="labelHorasPropuestas">Horas diarias propuestas</label><input class="inputs-propuesta" type="number" name="propuesta_horas_jornada" id="propHoras" min="1" max="24" step="0.5"><small id="ayudaHorasPropuestas">Déjelo vacío si la jornada no cambia.</small></div>
                                <div class="accion-field" data-losep-only>
                                    <label>Novedad temporal de jornada</label>
                                    <select class="inputs-propuesta" name="tipo_novedad_jornada" id="tipoNovedadJornada" onchange="toggleJornadaTemporal()">
                                        <option id="optionNovedadVacia" value="">No aplica</option><option value="LACTANCIA">Lactancia</option><option value="MATERNIDAD">Licencia por maternidad</option><option value="PATERNIDAD">Licencia por paternidad</option><option value="SUSTITUTO">Jornada temporal por condición de sustituto</option><option value="DISCAPACIDAD">Discapacidad</option><option value="OTRA JORNADA ESPECIAL">Otra jornada especial</option>
                                    </select>
                                </div>
                                <div id="bloqueJornadaTemporal" data-losep-only style="display:none;padding:14px;border:1px solid rgba(14,116,144,.25);border-radius:12px;background:#ecfeff;">
                                    <div class="accion-field"><label>Horario temporal</label><div style="display:grid;grid-template-columns:1fr 1fr;gap:8px"><input type="time" name="hora_entrada_propuesta"><input type="time" name="hora_salida_propuesta"></div></div>
                                    <div class="accion-field"><label>Días aplicables</label><input type="text" name="dias_jornada_propuesta" placeholder="Ej.: lunes a viernes"></div>
                                    <div class="accion-field"><label>Documento de respaldo</label><input type="text" name="documento_jornada" placeholder="Memorando, resolución o certificado"></div>
                                    <small>Use “Rige desde” y “Rige hasta” para delimitar el periodo. Quedará visible en el historial laboral.</small>
                                </div>
                            </div>
                        </div><!-- /split-wrap -->

                        <!-- ══ SECCIÓN 4: MOTIVACIÓN Y NOTIFICACIÓN ══════════════════════ -->
                        <div class="section-sep"><span id="tituloCierreDocumento"><i class="bi bi-chat-quote-fill"></i> 4 · Motivación y Notificación</span></div>
                        <div class="firma-grid">
                            <div class="accion-field" data-losep-only style="grid-column:1/-1;">
                                <label>Motivación / Fundamento Legal <span class="req">*</span></label>
                                <textarea name="motivacion_texto" rows="4" required
                                          placeholder="Describa el fundamento legal y la justificación institucional para esta acción de personal (Art. 21 LOSEP)..."></textarea>
                            </div>
                            <div class="accion-field" id="campoDeclaracionJurada" data-losep-only>
                                <label>Presentó Declaración Juramentada</label>
                                <select name="presento_declaracion">
                                    <option value="NO APLICA">No aplica</option>
                                    <option value="SI">Sí, presentó</option>
                                    <option value="NO">No presentó</option>
                                </select>
                            </div>
                            <div class="accion-field" data-losep-only>
                                <label>
                                    <input type="checkbox" name="notificacion_electronica"
                                           id="chkNotificacion" onchange="toggleNotificacion()"
                                           style="width:auto; margin-right:6px;">
                                    Notificar por correo electrónico a una o más áreas
                                </label>
                                <input type="hidden" name="correo_notificacion" id="inpCorreoNotif" disabled>
                                <div class="email-recipient-box is-disabled" id="emailRecipientBox">
                                    <div class="email-recipient-row">
                                        <input type="email" id="emailRecipientInput" list="correosFrecuentes"
                                               placeholder="Escriba un correo y presione Enter" autocomplete="email">
                                        <button type="button" class="email-recipient-add" id="btnAgregarCorreo" title="Agregar destinatario"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                    <datalist id="correosFrecuentes"></datalist>
                                    <div class="email-recipient-chips" id="emailRecipientChips" aria-live="polite"></div>
                                    <small>Máximo 8 destinatarios y 150 caracteres en total. Los correos usados quedarán disponibles como sugerencias en este equipo.</small>
                                </div>
                            </div>
                            <div class="accion-field" data-losep-only><label>Fecha y hora de notificación</label><input type="datetime-local" name="fecha_notificacion"></div>
                            <div class="accion-field" data-losep-only><label>Medio de notificación</label><input type="text" name="medio_notificacion" placeholder="Correo, Quipux, entrega física..."></div>
                            <div class="accion-field" data-losep-only><label>Número de documento/notificación</label><input type="text" name="documento_notificacion"></div>
                            <div class="accion-field" id="campoResponsableTh" data-losep-only><label>Responsable Talento Humano</label><input type="text" name="responsable_th_nombre"><input type="text" name="responsable_th_puesto" placeholder="Puesto" style="margin-top:6px"></div>
                            <div class="accion-field" id="campoAutoridadNominadora" data-losep-only><label>Autoridad nominadora/delegado</label><input type="text" name="autoridad_nombre"><input type="text" name="autoridad_puesto" placeholder="Puesto" style="margin-top:6px"></div>
                            <div class="accion-field"><label>Responsable de elaboración</label><input type="text" name="elaborador_nombre" value="<?= htmlspecialchars($responsableDocumento) ?>" readonly><input type="text" name="elaborador_puesto" value="<?= htmlspecialchars($puestoResponsableDocumento) ?>" readonly placeholder="Puesto" style="margin-top:6px"></div>
                            <div class="accion-field" id="campoResponsableRevision" data-losep-only><label>Responsable de revisión</label><input type="text" name="revisor_nombre"><input type="text" name="revisor_puesto" placeholder="Puesto" style="margin-top:6px"></div>
                            <div class="accion-field"><label>Responsable de registro y control</label><input type="text" name="registrador_nombre"><input type="text" name="registrador_puesto" placeholder="Puesto" style="margin-top:6px"></div>
                            <div class="accion-field" data-losep-only><label>Responsable que notificó</label><input type="text" name="notificador_nombre"><input type="text" name="notificador_puesto" placeholder="Puesto" style="margin-top:6px"></div>
                        </div>

                        <!-- ══ ACCIONES ══════════════════════════════════════════════════ -->
                        <div class="accion-actions">
                            <a href="<?= BASE_URL ?><?= $esEdicion?'/talento-humano/biblioteca':'/talento-humano/directorio' ?>" class="btn btn-outline"
                               id="btn-cancelar-accion">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </a>
                            <button type="button" class="btn btn-ghost" onclick="abrirModalVistaPrevia()"
                                    id="btn-imprimir-accion">
                                <i class="bi bi-eye-fill"></i> Vista previa e impresión
                            </button>
                            <button type="submit" class="btn btn-primary" id="btn-generar-accion">
                                <i class="bi bi-file-earmark-check-fill"></i> <?= $esEdicion?'Guardar correcciones':'Generar Documento' ?>
                            </button>
                        </div>

                    </form>
                </section>
            </div>
        </main>
    </section>
</div>

<!-- ══ MODAL VISTA PREVIA ACCIÓN DE PERSONAL ════════════════════════════════ -->
<div class="preview-overlay" id="modal-vista-previa" role="dialog" aria-modal="true" style="position:fixed;inset:0;z-index:1000;background:rgba(5,15,30,.65);backdrop-filter:blur(6px);display:flex;align-items:flex-start;justify-content:center;padding:20px;overflow-y:auto;opacity:0;pointer-events:none;transition:opacity .3s;">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:860px;box-shadow:0 30px 80px rgba(0,0,0,.35);transform:translateY(30px);transition:transform .3s;overflow:hidden;" id="modal-vp-inner">
        <div style="background:linear-gradient(135deg,var(--navy-900),var(--ocean-700));padding:18px 24px;display:flex;align-items:center;justify-content:space-between;">
            <h4 id="tituloModalDocumento" style="color:#fff;margin:0;font-size:1rem;"><i class="bi bi-file-earmark-text-fill"></i> Vista Previa — Acción de Personal</h4>
            <button onclick="cerrarModalVistaPrevia()" style="width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.25);color:#fff;cursor:pointer;display:grid;place-items:center;font-size:1.1rem;" aria-label="Cerrar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div style="padding:12px 24px;background:#f8fbff;border-bottom:1px solid var(--line);display:flex;gap:10px;flex-wrap:wrap;">
            <button onclick="imprimirDesdeModal()" style="border:none;border-radius:999px;padding:10px 18px;font-size:.84rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--ocean-700),var(--teal-500));color:#fff;box-shadow:0 6px 16px rgba(13,95,135,.22);">
                <i class="bi bi-printer-fill"></i> Imprimir / Guardar PDF
            </button>
            <button onclick="cerrarModalVistaPrevia()" style="border:1px solid rgba(13,95,135,.25);border-radius:999px;padding:10px 18px;font-size:.84rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;background:#fff;color:var(--ocean-700);">
                <i class="bi bi-x"></i> Cerrar
            </button>
        </div>
        <div style="padding:0;max-height:75vh;overflow-y:auto;" id="modal-vp-body">
            <!-- Contenido generado dinámicamente -->
        </div>
    </div>
</div>

<?php if (Auth::can('maestros', 'crear')):
    $catalogoRapidoConfig = [
        'areas' => $areas ?? [],
        'unidadSelectId' => 'propUnidad',
        'puestoSelectId' => 'propPuesto',
        'rmuTargetId' => 'propSueldo',
    ];
    require ROOT . '/shared/catalogo_rapido.php';
endif; ?>


<?php if (!empty($_GET['msg'])): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        showToast(<?= json_encode(htmlspecialchars($_GET['msg'])) ?>,
                  <?= ($_GET['ok'] ?? '0') === '1' ? "'success'" : "'error'" ?>);
    });
</script>
<?php endif; ?>

<script>
/* ══════════════════════════════════════════════════════════════════════════
   accion_personal.js – Lógica interactiva exclusiva de este formulario
   ══════════════════════════════════════════════════════════════════════════ */

/* BASE_URL inyectado desde PHP para que fetch() funcione en cualquier entorno */
const BASE_URL = '<?= BASE_URL ?>';
const ACCION_EDICION = <?= json_encode($accionEdicion ?: null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const IS_EDITING = Boolean(ACCION_EDICION?.accion_id);
let REGIMEN_DOCUMENTO = <?= json_encode($regimenDocumento, JSON_UNESCAPED_UNICODE) ?>;
const PERSONAL_ACCION = <?= json_encode($selectorPersonal ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const normalizarBusquedaPersonal = valor => String(valor ?? '')
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es')
    .replace(/[^a-z0-9]+/g, ' ').trim();
const escaparResultadoPersonal = valor => String(valor ?? '').replace(/[&<>"']/g, caracter => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
})[caracter]);
PERSONAL_ACCION.forEach(persona => {
    persona.indice = normalizarBusquedaPersonal(`${persona.cedula} ${persona.apellidos} ${persona.nombres} ${persona.cargo} ${persona.area}`);
});

function coincidenciasPersonalAccion() {
    const tokens = normalizarBusquedaPersonal(document.getElementById('inputBuscarCedula').value).split(/\s+/).filter(Boolean);
    if (!tokens.length) return [];
    return PERSONAL_ACCION.filter(persona => tokens.every(token => persona.indice.includes(token))).slice(0, 10);
}

function mostrarResultadosPersonalAccion() {
    const contenedor = document.getElementById('resultadosPersonalAccion');
    const resultados = coincidenciasPersonalAccion();
    if (!document.getElementById('inputBuscarCedula').value.trim()) {
        contenedor.classList.remove('open');
        contenedor.innerHTML = '';
        return;
    }
    contenedor.innerHTML = resultados.length ? resultados.map(persona => `
        <button type="button" class="personal-result" role="option" data-persona-id="${Number(persona.id)}">
            <strong>${escaparResultadoPersonal(persona.apellidos)} ${escaparResultadoPersonal(persona.nombres)}</strong>
            <small>C.I. ${escaparResultadoPersonal(persona.cedula)} · ${escaparResultadoPersonal(persona.cargo || persona.area)}</small>
        </button>`).join('') : '<div class="personal-result"><strong>Sin coincidencias</strong><small>Revise la cédula o el nombre ingresado.</small></div>';
    contenedor.classList.add('open');
}

async function cargarServidorPorId(id) {
    const estado = document.getElementById('estadoBusqueda');
    try {
        const respuesta = await fetch(`${BASE_URL}/talento-humano/accion-personal/buscar-servidor?id=${Number(id)}`);
        const json = await respuesta.json();
        if (!respuesta.ok || !json.success || !json.data) throw new Error(json.message || 'Funcionario no encontrado.');
        document.getElementById('inputBuscarCedula').value = json.data.cedula ?? '';
        llenarFormularioConEmpleado(json.data);
        estado.innerHTML = `<i class="bi bi-check-circle-fill" style="color:#059669"></i> Expediente cargado: ${escaparResultadoPersonal(json.data.apellidos)} ${escaparResultadoPersonal(json.data.nombres)}`;
        estado.style.color = '#059669';
        document.getElementById('resultadosPersonalAccion').classList.remove('open');
        return json.data;
    } catch (error) {
        estado.innerHTML = `<i class="bi bi-x-circle-fill" style="color:#dc2626"></i> ${escaparResultadoPersonal(error.message)}`;
        estado.style.color = '#dc2626';
        return null;
    }
}

function seleccionarPersonalAccion(id) {
    return cargarServidorPorId(id);
}

function buscarPersonaAccion() {
    const primera = coincidenciasPersonalAccion()[0];
    if (primera) return seleccionarPersonalAccion(primera.id);
    const soloDigitos = document.getElementById('inputBuscarCedula').value.replace(/\D/g, '');
    if (soloDigitos.length >= 5) return buscarPorCedula();
    showToast?.('No se encontraron coincidencias para la búsqueda.', 'error');
}

/* ── 1. Inicialización al cargar la página ───────────────────────────────── */
document.addEventListener('DOMContentLoaded', async () => {
    // Si la URL trae ?cedula= o ?id=, autocompletar al cargar (viene del Directorio)
    const params  = new URLSearchParams(window.location.search);
    const cedUrl  = params.get('cedula');
    const idUrl   = params.get('id');

    const buscador = document.getElementById('inputBuscarCedula');
    buscador.addEventListener('input', mostrarResultadosPersonalAccion);
    buscador.addEventListener('focus', mostrarResultadosPersonalAccion);
    buscador.addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); buscarPersonaAccion(); }
        if (event.key === 'Escape') document.getElementById('resultadosPersonalAccion').classList.remove('open');
    });
    document.getElementById('resultadosPersonalAccion').addEventListener('click', event => {
        const opcion = event.target.closest('[data-persona-id]');
        if (opcion) seleccionarPersonalAccion(Number(opcion.dataset.personaId));
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('.personal-autocomplete')) document.getElementById('resultadosPersonalAccion').classList.remove('open');
    });

    if (IS_EDITING) {
        await cargarServidorPorId(Number(ACCION_EDICION.empleado_id));
        aplicarAccionEdicion(ACCION_EDICION);
    } else if (cedUrl) {
        // Modo cédula: el link del directorio puede traer la cédula directamente
        document.getElementById('inputBuscarCedula').value = cedUrl;
        buscarPorCedula();
    } else if (idUrl && parseInt(idUrl) > 0) {
        // Modo ID: buscar por ID interno y mostrar la cédula al completar
        cargarServidorPorId(parseInt(idUrl));
    }
    if (!IS_EDITING) {
        actualizarVigencia();
        actualizarModoCaptura();
        const tipoInicial=<?= json_encode($tipoPreseleccionado,JSON_UNESCAPED_UNICODE) ?>;
        const chipInicial=[...document.querySelectorAll('.tipo-chip')].find(chip=>chip.dataset.value===tipoInicial);
        if(chipInicial)seleccionarTipo(chipInicial);
    }
    inicializarDestinatariosCorreo();
});

/* ── 2. Búsqueda AJAX del funcionario por cédula ───────────────────────────── */
async function buscarPorCedula() {
    const input     = document.getElementById('inputBuscarCedula');
    const estado    = document.getElementById('estadoBusqueda');
    const btnBuscar = document.getElementById('btnBuscarServidor');
    const cedula    = input.value.trim().replace(/\D/g, ''); // solo dígitos

    if (cedula.length < 5) {
        estado.innerHTML   = '<i class="bi bi-exclamation-triangle-fill" style="color:#d97706"></i> Ingrese al menos 5 dígitos.';
        estado.style.color = '#d97706';
        showToast?.('Ingrese una cédula válida (mínimo 5 dígitos).', 'error');
        return;
    }

    // Estado visual: cargando
    btnBuscar.disabled   = true;
    btnBuscar.innerHTML  = '<i class="bi bi-hourglass-split"></i> Buscando...';
    estado.innerHTML     = '<i class="bi bi-hourglass-split"></i> Consultando la base de datos...';
    estado.style.color   = '#0e7490';

    try {
        const resp = await fetch(`${BASE_URL}/talento-humano/accion-personal/buscar-por-cedula?cedula=${encodeURIComponent(cedula)}`);
        if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
        const json = await resp.json();

        if (json.success && json.data) {
            llenarFormularioConEmpleado(json.data);
            estado.innerHTML = `<i class="bi bi-check-circle-fill" style="color:#059669"></i> Expediente cargado: ${json.data.apellidos} ${json.data.nombres}`;
            estado.style.color = '#059669';
            showToast?.(`✅ Expediente de ${json.data.apellidos} ${json.data.nombres} sincronizado correctamente.`, 'success');
        } else {
            limpiarFormulario();
            estado.innerHTML = `<i class="bi bi-x-circle-fill" style="color:#dc2626"></i> ${json.message ?? 'Funcionario no encontrado.'}`;
            estado.style.color = '#dc2626';
            showToast?.(json.message ?? 'Funcionario no encontrado en el sistema.', 'error');
        }
    } catch (err) {
        limpiarFormulario();
        estado.innerHTML = '<i class="bi bi-wifi-off"></i> Error de conexión con el servidor.';
        estado.style.color = '#dc2626';
        showToast?.('Error de conexión al buscar el funcionario.', 'error');
    } finally {
        btnBuscar.disabled  = false;
        btnBuscar.innerHTML = '<i class="bi bi-search"></i> Seleccionar';
    }
}

/* ── 3. Llenar formulario con datos reales desde la BD ───────────────────── */
function llenarFormularioConEmpleado(emp) {
    REGIMEN_DOCUMENTO = String(emp.regimen_laboral || 'LOSEP').toUpperCase();
    // Campos de cabecera del formulario
    document.getElementById('inpCedula').value      = emp.cedula        ?? '';
    document.getElementById('inpNombres').value     = `${emp.apellidos ?? ''} ${emp.nombres ?? ''}`.trim();
    document.getElementById('hidEmpleadoId').value  = emp.id            ?? 0;
    document.getElementById('hidUnidadId').value    = emp.unidad_id     ?? 0;

    // Situación actual (bloque izquierdo — readonly, datos directos de la BD)
    document.getElementById('inpProcesoActual').value  = emp.proceso_institucional ?? emp.tipo_proceso ?? '';
    document.getElementById('inpNivelActual').value    = emp.nivel_gestion ?? '';
    document.getElementById('inpUnidadActual').value   = emp.direccion_area ?? '';
    document.getElementById('inpPuestoActual').value   = emp.cargo          ?? '';
    document.getElementById('inpLugarActual').value    = emp.lugar_trabajo ?? 'Manta';
    document.getElementById('inpGrupoActual').value    = emp.grupo_ocupacional ?? '';
    document.getElementById('inpGradoActual').value    = emp.grado_laboral ?? '';
    document.getElementById('inpPartidaActual').value  = emp.partida_individual ?? '';
    document.getElementById('inpJornadaActual').value  = emp.jornada ?? 'Completa';
    document.getElementById('inpHorasActual').value    = emp.horas_jornada ?? 8;
    document.getElementById('inpJornadaActualResumen').value = `${emp.jornada ?? 'Completa'} — ${Number(emp.horas_jornada ?? 8).toLocaleString('es-EC',{maximumFractionDigits:1})} horas diarias`;
    document.getElementById('inpSueldoActual').value   = Number(emp.remuneracion_mensual ?? emp.sueldo_rmu ?? 0).toFixed(2);
    document.getElementById('inpContratoActual').value = emp.tipo_contrato  ?? '';

    // Campos ocultos que viajan al servidor al hacer submit (Situación Actual)
    document.getElementById('hidActualUnidadId').value     = emp.unidad_id            ?? 0;
    document.getElementById('hidActualPuestoId').value     = emp.puesto_id            ?? 0;
    document.getElementById('hidActualRemuneracion').value = emp.remuneracion_unificada ?? 0;

    // Limpiar lado propuesta para comenzar desde cero
    limpiarPropuesta();
    actualizarPresentacionRegimen();
    if (!IS_EDITING) {
        const tipoInicial = <?= json_encode($tipoPreseleccionado ?: 'INGRESO', JSON_UNESCAPED_UNICODE) ?>;
        const chipInicial = [...document.querySelectorAll('.tipo-chip')].find(chip => chip.dataset.value === tipoInicial);
        if (chipInicial) seleccionarTipo(chipInicial);
    }
}

function actualizarPresentacionRegimen() {
    const codigo = REGIMEN_DOCUMENTO === 'CODIGO_TRABAJO';
    const titulo = document.getElementById('tituloDocumentoLaboral');
    const subtitulo = document.getElementById('subtituloDocumentoLaboral');
    const resumen = document.getElementById('displayRegimen');
    if (titulo) titulo.innerHTML = `<i class="bi bi-file-earmark-text"></i> ${codigo ? 'Formulario Abreviado Laboral' : (IS_EDITING ? 'Editar borrador de Acción de Personal' : 'Formulario de Acción de Personal')}`;
    if (subtitulo) subtitulo.textContent = codigo
        ? 'Código del Trabajo — documento laboral abreviado de la APM'
        : (IS_EDITING ? 'Corrija la información antes de aprobar o rechazar el documento.' : 'Art. 21 LOSEP — Registro oficial de movimiento de personal de la APM');
    if (resumen) resumen.innerHTML = `<i class="bi bi-shield-check"></i> ${codigo ? 'Código del Trabajo · Abreviado' : 'LOSEP · Completo'}`;
    const resumenDocumento = document.querySelector('.document-summary');
    resumenDocumento?.classList.toggle('is-abbreviated', codigo);
    const resumenVigencia = document.getElementById('resumenVigenciaItem');
    if (resumenVigencia) resumenVigencia.hidden = codigo;
    document.querySelectorAll('[data-losep-only]').forEach(bloque => {
        bloque.hidden = codigo;
        bloque.querySelectorAll('input,select,textarea,button').forEach(control => {
            control.disabled = codigo;
        });
    });
    const tituloTipo = document.getElementById('tituloTipoDocumento');
    if (tituloTipo) tituloTipo.innerHTML = codigo
        ? '<i class="bi bi-list-check"></i> 2 · Tipo de movimiento laboral'
        : '<i class="bi bi-list-check"></i> 2 · Motivo de la Acción (Art. 21 LOSEP)';
    const tituloCierre = document.getElementById('tituloCierreDocumento');
    if (tituloCierre) tituloCierre.innerHTML = codigo
        ? '<i class="bi bi-pen"></i> 4 · Responsables del documento'
        : '<i class="bi bi-chat-quote-fill"></i> 4 · Motivación y Notificación';
    const contrato = document.getElementById('propContrato');
    const campoContrato = document.getElementById('campoContratoPropuesto');
    if (contrato) {
        contrato.disabled = codigo;
        if (codigo) contrato.value = '';
    }
    if (campoContrato) campoContrato.hidden = codigo;
    const motivacion = document.querySelector('[name="motivacion_texto"]');
    if (motivacion) {
        motivacion.required = !codigo;
        motivacion.placeholder = 'Describa el fundamento legal y la justificación institucional para esta acción de personal (Art. 21 LOSEP)...';
    }
    if (codigo) {
        const permanente = document.querySelector('input[name="modalidad_vigencia"][value="PERMANENTE"]');
        const cambioLaboral = document.querySelector('input[name="modo_captura"][value="CAMBIO_LABORAL"]');
        if (permanente) permanente.checked = true;
        if (cambioLaboral) cambioLaboral.checked = true;
        const hasta = document.getElementById('inpRigeHasta');
        if (hasta) { hasta.value = ''; hasta.required = false; hasta.disabled = true; }
        const notificacion = document.getElementById('chkNotificacion');
        if (notificacion) notificacion.checked = false;
    } else {
        actualizarVigencia();
        actualizarModoCaptura();
        toggleNotificacion();
    }
    if (!IS_EDITING) actualizarNumeroPrevistoPorRegimen();
}

function actualizarNumeroPrevistoPorRegimen() {
    const tipo = document.getElementById('hidTipoAccion')?.value || 'INGRESO';
    const series={'CAMBIO ADMINISTRATIVO':'CA','LICENCIA':'LI','SANCIONES':'RD','VACACIONES':'VAC'};
    const serie=REGIMEN_DOCUMENTO==='CODIGO_TRABAJO' ? 'CdgT' : (series[tipo]||'MP');
    const numero=`${serie}-001-<?= InstitutionalClock::today()->format('Y') ?>`;
    document.getElementById('displayNroAccion').textContent=numero;
    document.getElementById('nroAccionInput').value=numero;
}

function aplicarAccionEdicion(accion) {
    const asignar = (selector, valor) => {
        const control = document.querySelector(selector);
        if (control && valor !== null && valor !== undefined) control.value = String(valor);
    };
    const modalidad = String(accion.modalidad_vigencia || 'PERMANENTE').toUpperCase();
    const radioVigencia = document.querySelector(`input[name="modalidad_vigencia"][value="${modalidad}"]`);
    if (radioVigencia) radioVigencia.checked = true;
    asignar('[name="fecha_elaboracion"]', String(accion.fecha_elaboracion || '').slice(0,10));
    asignar('[name="rige_desde"]', String(accion.fecha_rige_desde || '').slice(0,10));
    const hasta = document.getElementById('inpRigeHasta');
    hasta.disabled = modalidad !== 'TEMPORAL';
    asignar('[name="rige_hasta"]', String(accion.fecha_rige_hasta || '').slice(0,10));

    const modo = accion.tipo_novedad_jornada ? 'JORNADA_TEMPORAL' : 'CAMBIO_LABORAL';
    const radioModo = document.querySelector(`input[name="modo_captura"][value="${modo}"]`);
    if (radioModo) radioModo.checked = true;

    const chip = [...document.querySelectorAll('.tipo-chip')].find(item => item.dataset.value === accion.tipo_accion);
    if (chip) seleccionarTipo(chip);
    document.querySelectorAll('.tipo-chip').forEach(item => {
        item.setAttribute('aria-disabled', item === chip ? 'false' : 'true');
        if (item !== chip) item.style.opacity = '.48';
    });

    const campos = {
        '[name="explicacion_otro"]':'detalle_otro','[name="propuesta_proceso"]':'propuesta_proceso',
        '[name="propuesta_nivel_gestion"]':'propuesta_nivel_gestion','[name="propuesta_unidad_id"]':'propuesta_unidad_id',
        '[name="propuesta_puesto_id"]':'propuesta_puesto_id','[name="propuesta_remuneracion"]':'propuesta_remuneracion',
        '[name="propuesta_lugar_trabajo"]':'propuesta_lugar_trabajo','[name="propuesta_grupo_ocupacional"]':'propuesta_grupo_ocupacional',
        '[name="propuesta_grado"]':'propuesta_grado','[name="propuesta_partida_presupuestaria"]':'propuesta_partida_presupuestaria',
        '[name="propuesta_contrato"]':'propuesta_tipo_contrato','[name="propuesta_jornada"]':'propuesta_jornada',
        '[name="propuesta_horas_jornada"]':'propuesta_horas_jornada','[name="tipo_novedad_jornada"]':'tipo_novedad_jornada',
        '[name="hora_entrada_propuesta"]':'hora_entrada_propuesta','[name="hora_salida_propuesta"]':'hora_salida_propuesta',
        '[name="dias_jornada_propuesta"]':'dias_jornada_propuesta','[name="documento_jornada"]':'documento_jornada',
        '[name="motivacion_texto"]':'explicacion_legal','[name="presento_declaracion"]':'presento_declaracion',
        '[name="fecha_notificacion"]':'fecha_notificacion','[name="medio_notificacion"]':'medio_notificacion',
        '[name="documento_notificacion"]':'documento_notificacion','[name="responsable_th_nombre"]':'responsable_th_nombre',
        '[name="responsable_th_puesto"]':'responsable_th_puesto','[name="autoridad_nombre"]':'autoridad_nombre',
        '[name="autoridad_puesto"]':'autoridad_puesto','[name="elaborador_nombre"]':'elaborador_nombre',
        '[name="elaborador_puesto"]':'elaborador_puesto','[name="revisor_nombre"]':'revisor_nombre',
        '[name="revisor_puesto"]':'revisor_puesto','[name="registrador_nombre"]':'registrador_nombre',
        '[name="registrador_puesto"]':'registrador_puesto','[name="notificador_nombre"]':'notificador_nombre',
        '[name="notificador_puesto"]':'notificador_puesto'
    };
    Object.entries(campos).forEach(([selector, campo]) => {
        let valor = accion[campo];
        if (campo === 'fecha_notificacion' && valor) valor = String(valor).replace(' ', 'T').slice(0,16);
        asignar(selector, valor ?? '');
    });
    actualizarVigencia();
    actualizarModoCaptura();
    toggleJornadaTemporal();

    destinatariosCorreo = separarCorreos(accion.correo_notificacion || '');
    const notificar = Number(accion.notificacion_electronica || 0) === 1 || destinatariosCorreo.length > 0;
    document.getElementById('chkNotificacion').checked = notificar;
    toggleNotificacion();
    renderizarDestinatariosCorreo();
}

function actualizarVigencia() {
    const novedad = document.getElementById('tipoNovedadJornada')?.value ?? '';
    if (novedad) document.querySelector('input[name="modalidad_vigencia"][value="TEMPORAL"]').checked = true;
    const temporal = document.querySelector('input[name="modalidad_vigencia"]:checked')?.value === 'TEMPORAL';
    const hasta = document.getElementById('inpRigeHasta');
    hasta.disabled = !temporal;
    hasta.required = temporal;
    if (!temporal) hasta.value = '';
    document.getElementById('reqRigeHasta').style.display = temporal ? 'inline' : 'none';
    document.getElementById('vigenciaHelp').innerHTML = temporal
        ? '<i class="bi bi-arrow-counterclockwise"></i> Al finalizar la fecha indicada, el sistema mostrará nuevamente la situación anterior y lo registrará en auditoría.'
        : '<i class="bi bi-infinity"></i> Vigencia permanente: el cambio se mantendrá hasta una nueva Acción de Personal.';
    const resumen = document.getElementById('displayVigencia');
    if (resumen) resumen.innerHTML = temporal
        ? '<i class="bi bi-arrow-counterclockwise"></i> Temporal con retorno'
        : '<i class="bi bi-infinity"></i> Permanente';
}

function actualizarModoCaptura() {
    const compacto = document.querySelector('input[name="modo_captura"]:checked')?.value === 'JORNADA_TEMPORAL';
    document.getElementById('camposCambioLaboral').style.display = compacto ? 'none' : 'block';
    document.getElementById('compactScheduleNote').style.display = compacto ? 'block' : 'none';
    document.getElementById('panelActual').classList.toggle('compact-schedule-current', compacto);
    document.querySelectorAll('#camposCambioLaboral input, #camposCambioLaboral select').forEach(control => {
        control.disabled = compacto;
    });
    document.getElementById('labelJornadaPropuesta').textContent = compacto ? 'Jornada temporal propuesta' : 'Jornada propuesta';
    document.getElementById('labelHorasPropuestas').textContent = compacto ? 'Horas temporales diarias' : 'Horas diarias propuestas';
    const novedad = document.getElementById('tipoNovedadJornada');
    novedad.required = compacto;
    document.getElementById('optionNovedadVacia').textContent = compacto ? 'Seleccione la novedad temporal…' : 'No aplica';
    if (compacto) {
        document.querySelector('input[name="modalidad_vigencia"][value="TEMPORAL"]').checked = true;
        actualizarVigencia();
        novedad.focus();
    }
    toggleJornadaTemporal();
}

function toggleJornadaTemporal() {
    const tipo = document.getElementById('tipoNovedadJornada').value;
    const activa = tipo !== '';
    const parental = ['MATERNIDAD','PATERNIDAD'].includes(tipo);
    document.getElementById('bloqueJornadaTemporal').style.display = activa ? 'block' : 'none';
    const horas = document.getElementById('propHoras');
    const ayuda = document.getElementById('ayudaHorasPropuestas');
    horas.readOnly = false;
    horas.min = '1';
    if (activa) {
        document.querySelector('input[name="modalidad_vigencia"][value="TEMPORAL"]').checked = true;
        if (parental) {
            document.getElementById('propJornada').value = 'Licencia';
            horas.value = '0';
            horas.min = '0';
            horas.required = false;
            horas.readOnly = true;
            if (ayuda) ayuda.textContent = 'La licencia por maternidad o paternidad se registra automáticamente con 0 horas.';
            const licencia = document.querySelector('.tipo-chip[data-value="LICENCIA"]');
            if (licencia) seleccionarTipo(licencia);
        } else {
            document.getElementById('propJornada').value = 'Especial';
            horas.required = true;
            if (tipo === 'SUSTITUTO') {
                horas.value = '6'; horas.readOnly = true;
                if (ayuda) ayuda.textContent = 'La jornada temporal por condición de sustituto utiliza 6 horas.';
            } else {
                if (!Number(horas.value)) horas.value = tipo === 'LACTANCIA' ? '6' : '';
                if (ayuda) ayuda.textContent = 'Ingrese las horas que estarán vigentes únicamente durante el periodo indicado.';
            }
        }
        actualizarVigencia();
    } else {
        horas.required = false;
        horas.readOnly = false;
        if (ayuda) ayuda.textContent = 'Déjelo vacío si la jornada no cambia.';
    }
}

function actualizarVigencia() {
    const novedad = document.getElementById('tipoNovedadJornada')?.value ?? '';
    if (novedad) document.querySelector('input[name="modalidad_vigencia"][value="TEMPORAL"]').checked = true;
    const temporal = document.querySelector('input[name="modalidad_vigencia"]:checked')?.value === 'TEMPORAL';
    const hasta = document.getElementById('inpRigeHasta');
    hasta.disabled = !temporal;
    hasta.required = temporal;
    if (!temporal) hasta.value = '';
    document.getElementById('reqRigeHasta').style.display = temporal ? 'inline' : 'none';
    document.getElementById('vigenciaHelp').innerHTML = temporal
        ? '<i class="bi bi-arrow-counterclockwise"></i> Al finalizar la fecha indicada, el sistema mostrará nuevamente la situación anterior y lo registrará en auditoría.'
        : '<i class="bi bi-infinity"></i> Vigencia permanente: el cambio se mantendrá hasta una nueva Acción de Personal.';
    const resumen = document.getElementById('displayVigencia');
    if (resumen) resumen.innerHTML = temporal
        ? '<i class="bi bi-arrow-counterclockwise"></i> Temporal con retorno'
        : '<i class="bi bi-infinity"></i> Permanente';
}

function actualizarModoCaptura() {
    const compacto = document.querySelector('input[name="modo_captura"]:checked')?.value === 'JORNADA_TEMPORAL';
    document.getElementById('camposCambioLaboral').style.display = compacto ? 'none' : 'block';
    document.getElementById('compactScheduleNote').style.display = compacto ? 'block' : 'none';
    document.getElementById('panelActual').classList.toggle('compact-schedule-current', compacto);
    document.querySelectorAll('#camposCambioLaboral input, #camposCambioLaboral select').forEach(control => {
        control.disabled = compacto;
    });
    document.getElementById('labelJornadaPropuesta').textContent = compacto ? 'Jornada temporal propuesta' : 'Jornada propuesta';
    document.getElementById('labelHorasPropuestas').textContent = compacto ? 'Horas temporales diarias' : 'Horas diarias propuestas';
    const novedad = document.getElementById('tipoNovedadJornada');
    novedad.required = compacto;
    document.getElementById('optionNovedadVacia').textContent = compacto ? 'Seleccione la novedad temporal…' : 'No aplica';
    if (compacto) {
        document.querySelector('input[name="modalidad_vigencia"][value="TEMPORAL"]').checked = true;
        actualizarVigencia();
        novedad.focus();
    }
    toggleJornadaTemporal();
}

function toggleJornadaTemporal() {
    const tipo = document.getElementById('tipoNovedadJornada').value;
    const activa = tipo !== '';
    const parental = ['MATERNIDAD','PATERNIDAD'].includes(tipo);
    document.getElementById('bloqueJornadaTemporal').style.display = activa ? 'block' : 'none';
    const horas = document.getElementById('propHoras');
    const ayuda = document.getElementById('ayudaHorasPropuestas');
    horas.readOnly = false;
    horas.min = '1';
    if (activa) {
        document.querySelector('input[name="modalidad_vigencia"][value="TEMPORAL"]').checked = true;
        if (parental) {
            document.getElementById('propJornada').value = 'Licencia';
            horas.value = '0';
            horas.min = '0';
            horas.required = false;
            horas.readOnly = true;
            if (ayuda) ayuda.textContent = 'La licencia por maternidad o paternidad se registra automáticamente con 0 horas.';
            const licencia = document.querySelector('.tipo-chip[data-value="LICENCIA"]');
            if (licencia) seleccionarTipo(licencia);
        } else {
            document.getElementById('propJornada').value = 'Especial';
            horas.required = true;
            if (tipo === 'SUSTITUTO') {
                horas.value = '6'; horas.readOnly = true;
                if (ayuda) ayuda.textContent = 'La jornada temporal por condición de sustituto utiliza 6 horas.';
            } else {
                if (!Number(horas.value)) horas.value = tipo === 'LACTANCIA' ? '6' : '';
                if (ayuda) ayuda.textContent = 'Ingrese las horas que estarán vigentes únicamente durante el periodo indicado.';
            }
        }
        actualizarVigencia();
    } else {
        horas.required = false;
        horas.readOnly = false;
        if (ayuda) ayuda.textContent = 'Déjelo vacío si la jornada no cambia.';
    }
}

/* ── 6. Selección del tipo de acción (chips interactivos) ─────────────────── */
function seleccionarTipo(chip) {
    if (IS_EDITING && chip.dataset.value !== ACCION_EDICION.tipo_accion) {
        showToast?.('El tipo de acción no puede cambiarse porque define la serie documental. Puede rechazar este borrador y crear uno nuevo si la clasificación es incorrecta.', 'info');
        return;
    }
    document.querySelectorAll('.tipo-chip').forEach(c => c.classList.remove('selected'));
    chip.classList.add('selected');
    const valor = chip.dataset.value;
    document.getElementById('hidTipoAccion').value = valor;
    if (!IS_EDITING) {
        actualizarNumeroPrevistoPorRegimen();
    }
    const resumenTipo=document.getElementById('displayTipoAccion');
    if(resumenTipo) resumenTipo.innerHTML=`<i class="bi bi-list-check"></i> ${escaparResultadoPersonal(valor)}`;

    // Mostrar/ocultar campo "otro"
    document.getElementById('bloqueOtro').style.display = valor.startsWith('OTRO') ? 'block' : 'none';

    // Reglas de negocio: bloquear propuesta en acciones que no cambian posición
    aplicarReglasNegocio(valor);
    if(valor==='VACACIONES'){
        const temporal=document.querySelector('input[name="modalidad_vigencia"][value="TEMPORAL"]');if(temporal){temporal.checked=true;actualizarVigencia();}
    }
}

function aplicarReglasNegocio(accion) {
    const bloquear = ['VACACIONES','CESACIÓN DE FUNCIONES','DESTITUCIÓN','SANCIONES'].includes(accion);
    const compacto = document.querySelector('input[name="modo_captura"]:checked')?.value === 'JORNADA_TEMPORAL';
    document.querySelectorAll('.inputs-propuesta').forEach(el => {
        el.disabled = bloquear || (compacto && Boolean(el.closest('#camposCambioLaboral')));
        el.style.opacity = bloquear ? '.45' : '1';
        if (bloquear) el.value = '';
    });

    if (bloquear) {
        showToast?.(`Para acciones de tipo "${accion}" no se requiere Situación Propuesta.`, 'info');
    }
}

/* ── 7. Notificación por correo ───────────────────────────────────────────── */
const EMAIL_STORAGE_KEY = 'portal_apm_correos_notificacion_frecuentes';
let destinatariosCorreo = [];

function separarCorreos(valor) {
    return [...new Set(String(valor || '').toLowerCase().split(/[\s,;]+/).map(item => item.trim()).filter(Boolean))];
}

function correoValido(correo) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
}

function correosFrecuentesGuardados() {
    try {
        const valor = JSON.parse(localStorage.getItem(EMAIL_STORAGE_KEY) || '[]');
        return Array.isArray(valor) ? valor.filter(correoValido).slice(0, 30) : [];
    } catch (_) {
        return [];
    }
}

function actualizarSugerenciasCorreo() {
    const lista = document.getElementById('correosFrecuentes');
    if (!lista) return;
    lista.innerHTML = correosFrecuentesGuardados()
        .map(correo => `<option value="${escaparResultadoPersonal(correo)}"></option>`).join('');
}

function guardarCorreoFrecuente(correo) {
    const guardados = correosFrecuentesGuardados().filter(item => item !== correo);
    guardados.unshift(correo);
    localStorage.setItem(EMAIL_STORAGE_KEY, JSON.stringify(guardados.slice(0, 30)));
    actualizarSugerenciasCorreo();
}

function renderizarDestinatariosCorreo() {
    const oculto = document.getElementById('inpCorreoNotif');
    const contenedor = document.getElementById('emailRecipientChips');
    const combinado = destinatariosCorreo.join('; ');
    oculto.value = combinado;
    contenedor.innerHTML = destinatariosCorreo.length
        ? destinatariosCorreo.map((correo, indice) => `<span class="email-recipient-chip"><i class="bi bi-envelope-check"></i><span>${escaparResultadoPersonal(correo)}</span><button type="button" data-remove-email="${indice}" aria-label="Quitar ${escaparResultadoPersonal(correo)}"><i class="bi bi-x-lg"></i></button></span>`).join('')
        : '<small>No hay destinatarios agregados.</small>';
}

function agregarCorreoNotificacion() {
    const entrada = document.getElementById('emailRecipientInput');
    const nuevos = separarCorreos(entrada.value);
    if (!nuevos.length || nuevos.some(correo => !correoValido(correo))) {
        showToast?.('Ingrese un correo electrónico válido.', 'error');
        entrada.focus();
        return false;
    }
    const resultado = [...new Set([...destinatariosCorreo, ...nuevos])];
    if (resultado.length > 8 || resultado.join('; ').length > 150) {
        showToast?.('El campo admite hasta 8 destinatarios y 150 caracteres en total.', 'error');
        return false;
    }
    destinatariosCorreo = resultado;
    nuevos.forEach(guardarCorreoFrecuente);
    entrada.value = '';
    renderizarDestinatariosCorreo();
    entrada.focus();
    return true;
}

function inicializarDestinatariosCorreo() {
    actualizarSugerenciasCorreo();
    renderizarDestinatariosCorreo();
    document.getElementById('btnAgregarCorreo').addEventListener('click', agregarCorreoNotificacion);
    document.getElementById('emailRecipientInput').addEventListener('keydown', event => {
        if (event.key === 'Enter' || event.key === ',' || event.key === ';') {
            event.preventDefault();
            agregarCorreoNotificacion();
        }
    });
    document.getElementById('emailRecipientChips').addEventListener('click', event => {
        const boton = event.target.closest('[data-remove-email]');
        if (!boton) return;
        destinatariosCorreo.splice(Number(boton.dataset.removeEmail), 1);
        renderizarDestinatariosCorreo();
    });
    toggleNotificacion();
}

function toggleNotificacion() {
    const activo = document.getElementById('chkNotificacion').checked;
    document.getElementById('inpCorreoNotif').disabled = !activo;
    document.getElementById('emailRecipientBox').classList.toggle('is-disabled', !activo);
    document.getElementById('emailRecipientInput').disabled = !activo;
    document.getElementById('btnAgregarCorreo').disabled = !activo;
    renderizarDestinatariosCorreo();
}

/* ── 8. Limpieza del formulario ───────────────────────────────────────────── */
function limpiarPropuesta() {
    document.querySelectorAll('.inputs-propuesta').forEach(el => {
        el.value    = '';
        el.disabled = false;
        el.style.opacity = '1';
    });
    // Deseleccionar chips
    document.querySelectorAll('.tipo-chip').forEach(c => c.classList.remove('selected'));
    document.getElementById('hidTipoAccion').value = '';
    const resumenTipo=document.getElementById('displayTipoAccion');
    if(resumenTipo) resumenTipo.innerHTML='<i class="bi bi-list-check"></i> Pendiente de selección';
    document.getElementById('bloqueOtro').style.display = 'none';
    toggleJornadaTemporal();
    const compacto = document.querySelector('input[name="modo_captura"]:checked')?.value === 'JORNADA_TEMPORAL';
    document.querySelectorAll('#camposCambioLaboral input, #camposCambioLaboral select').forEach(control => {
        control.disabled = compacto;
    });
}

function limpiarFormulario() {
    document.getElementById('inpCedula').value    = '';
    document.getElementById('inpNombres').value   = '';
    document.getElementById('hidEmpleadoId').value = '0';
    document.getElementById('inpProcesoActual').value = '';
    document.getElementById('inpUnidadActual').value  = '';
    document.getElementById('inpPuestoActual').value  = '';
    document.getElementById('inpSueldoActual').value  = '';
    document.getElementById('inpContratoActual').value = '';
    limpiarPropuesta();
}

/* ── 9. Validación antes de enviar ───────────────────────────────────────── */
function validarFormulario() {
    const codigoTrabajo = REGIMEN_DOCUMENTO === 'CODIGO_TRABAJO';
    const correoPendiente = document.getElementById('emailRecipientInput').value.trim();
    if (!codigoTrabajo && document.getElementById('chkNotificacion').checked && correoPendiente && !agregarCorreoNotificacion()) {
        return false;
    }
    const cedula = document.getElementById('inpCedula').value.trim();
    const tipo   = document.getElementById('hidTipoAccion').value.trim();
    const motiv  = document.querySelector('textarea[name="motivacion_texto"]').value.trim();
    const modalidad = document.querySelector('input[name="modalidad_vigencia"]:checked')?.value;
    const hasta = document.getElementById('inpRigeHasta').value;
    const compacto = document.querySelector('input[name="modo_captura"]:checked')?.value === 'JORNADA_TEMPORAL';
    const novedad = document.getElementById('tipoNovedadJornada').value;

    if (!cedula) {
        showToast?.('Seleccione un empleado antes de continuar.', 'error');
        return false;
    }
    if (!tipo) {
        showToast?.('Debe seleccionar el tipo de Acción de Personal.', 'error');
        return false;
    }
    if (!codigoTrabajo && !motiv) {
        showToast?.('Ingrese la motivación / fundamento legal de la acción.', 'error');
        return false;
    }
    if (!codigoTrabajo && modalidad === 'TEMPORAL' && !hasta) {
        showToast?.('La vigencia temporal requiere una fecha de finalización.', 'error');
        document.getElementById('inpRigeHasta').focus();
        return false;
    }
    if (!codigoTrabajo && compacto && !novedad) {
        showToast?.('Seleccione la novedad de jornada temporal que se aplicará.', 'error');
        document.getElementById('tipoNovedadJornada').focus();
        return false;
    }
    if (!codigoTrabajo && document.getElementById('chkNotificacion').checked && destinatariosCorreo.length === 0) {
        showToast?.('Agregue al menos un destinatario para la notificación electrónica.', 'error');
        document.getElementById('emailRecipientInput').focus();
        return false;
    }
    // Sincronización final: algunos restauradores de borrador reponen el valor
    // inicial del hidden después de renderizar los chips. El servidor siempre
    // debe recibir exactamente la lista visible que confirmó el operador.
    document.getElementById('inpCorreoNotif').value = destinatariosCorreo.join('; ');
    showToast?.('Enviando documento al servidor...', 'info');
    return true;
}

/* ── 10. Modal Vista Previa / Imprimir ─────────────────────────────────── */
function abrirModalVistaPrevia() {
    const empId  = document.getElementById('hidEmpleadoId')?.value?.trim();
    const cedula = document.getElementById('inpCedula')?.value?.trim();
    const nombre = document.getElementById('inpNombres')?.value?.trim();

    if (!empId || empId === '0') {
        showToast?.('Primero busque y seleccione un funcionario para generar la vista previa.', 'error');
        return;
    }

    const getV = id => { const el = document.getElementById(id); return el ? el.value : ''; };
    const getT = id => { const el = document.querySelector(`[name="${id}"]`); return el ? (el.options?.[el.selectedIndex]?.text || el.value) : ''; };

    const tipoAccion   = document.getElementById('hidTipoAccion')?.value || '—';
    const unidadProp   = getT('propuesta_unidad_id');
    const puestoProp   = getT('propuesta_puesto_id');
    const sueldoProp   = getV('propSueldo');
    const contratoProp = getT('propuesta_contrato') || '—';
    const motivacion   = document.querySelector('textarea[name="motivacion_texto"]')?.value || '';
    const declaracion  = getT('presento_declaracion') || 'No aplica';
    const codigoTrabajo = REGIMEN_DOCUMENTO === 'CODIGO_TRABAJO';
    const responsableElaboracion = <?= json_encode($responsableDocumento, JSON_UNESCAPED_UNICODE) ?>;
    const puestoElaboracion = <?= json_encode($puestoResponsableDocumento, JSON_UNESCAPED_UNICODE) ?>;

    /* ── URL del logo usando constante PHP ── */
    const logoUrl  = '<?= IMG_URL ?>/logoapm.png';
    const fechaHoy = '<?= InstitutionalClock::today()->format('d/m/Y') ?>';
    const nroDoc   = document.getElementById('displayNroAccion')?.textContent?.trim()
        || `${codigoTrabajo ? 'CdgT' : 'APM-TH'}-001-<?= InstitutionalClock::today()->format('Y') ?>`;
    const bloqueTipo = codigoTrabajo ? `
        <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
            <tr><th colspan="2" style="border:1.5px solid #444;padding:7px 10px;background:#dce8f0;font-size:10.5pt;text-align:center;">TIPO DE MOVIMIENTO LABORAL</th></tr>
            <tr><td style="border:1px solid #888;padding:6px 10px;width:35%;background:#f7f9fb;"><strong>Movimiento:</strong></td><td style="border:1px solid #888;padding:6px 10px;">${tipoAccion}</td></tr>
        </table>` : `
        <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
            <tr><th colspan="2" style="border:1.5px solid #444;padding:7px 10px;background:#dce8f0;font-size:10.5pt;text-align:center;letter-spacing:.05em;">TIPO Y MOTIVO DE ACCIÓN</th></tr>
            <tr><td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;width:35%;background:#f7f9fb;"><strong>Tipo de Acción:</strong></td><td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;">${tipoAccion}</td></tr>
            <tr><td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;background:#f7f9fb;"><strong>Motivación / Fund. Legal:</strong></td><td style="border:1px solid #888;padding:10px;font-size:9.5pt;line-height:1.6;">${motivacion.replace(/\n/g,'<br>')}</td></tr>
            <tr><td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;background:#f7f9fb;"><strong>Declaración Juramentada:</strong></td><td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;">${declaracion}</td></tr>
        </table>`;
    const bloqueFirmas = codigoTrabajo ? `
        <div style="margin-top:45px;display:flex;justify-content:space-between;gap:28px;">
            <div style="text-align:center;flex:1;"><div style="border-top:1.5px solid #333;padding-top:6px;margin-top:60px;"><strong>Responsable de elaboración</strong><br><span>${responsableElaboracion}</span><br><small>${puestoElaboracion}</small></div></div>
            <div style="text-align:center;flex:1;"><div style="border-top:1.5px solid #333;padding-top:6px;margin-top:60px;"><strong>Responsable de registro y control</strong></div></div>
        </div>` : `
        <div style="margin-top:50px;display:flex;justify-content:space-between;gap:24px;">
            <div style="text-align:center;flex:1;"><div style="border-top:1.5px solid #333;padding-top:6px;margin-top:70px;"><strong>Elaborado por</strong><br><span>${responsableElaboracion}</span><br><small>${puestoElaboracion}</small></div></div>
            <div style="text-align:center;flex:1;"><div style="border-top:1.5px solid #333;padding-top:6px;margin-top:70px;"><strong>Revisado por</strong><br><small>Director/a de TH</small></div></div>
            <div style="text-align:center;flex:1;"><div style="border-top:1.5px solid #333;padding-top:6px;margin-top:70px;"><strong>Aprobado por</strong><br><small>Autoridad nominadora</small></div></div>
        </div>`;

    const html = `
    <div style="box-sizing:border-box;width:100%;max-width:794px;margin:28px auto;padding:30px 36px;background:#fff;box-shadow:0 4px 24px rgba(0,0,0,.1);font-family:'Times New Roman',serif;font-size:11pt;color:#111;line-height:1.55;">

        <!-- ENCABEZADO -->
        <table style="width:100%;border-collapse:collapse;margin-bottom:20px;">
            <tr>
                <td style="width:90px;border:1.5px solid #333;text-align:center;padding:8px;vertical-align:middle;">
                    <img src="${logoUrl}" style="width:64px;display:block;margin:0 auto;" alt="Logo APM"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                    <div style="display:none;font-size:9pt;font-weight:bold;border:1px solid #999;padding:4px;">APM</div>
                    <div style="font-size:7.5pt;font-weight:bold;margin-top:5px;">MANTA</div>
                </td>
                <td style="border:1.5px solid #333;text-align:center;padding:10px 16px;">
                    <div style="font-size:14pt;font-weight:bold;letter-spacing:.03em;">${codigoTrabajo ? 'FORMULARIO ABREVIADO LABORAL' : 'ACCIÓN DE PERSONAL'}</div>
                    <div style="font-size:10pt;margin-top:4px;color:#444;">Autoridad Portuaria de Manta</div>
                </td>
                <td style="width:160px;border:1.5px solid #333;padding:8px 10px;font-size:9pt;vertical-align:top;line-height:1.8;">
                    <strong>Régimen:</strong> ${codigoTrabajo ? 'Código del Trabajo' : 'LOSEP'}<br>
                    <strong>N°:</strong> ${nroDoc}<br>
                    <strong>Fecha:</strong> ${fechaHoy}
                </td>
            </tr>
        </table>

        <!-- DATOS DEL SERVIDOR -->
        <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
            <tr>
                <th colspan="4" style="border:1.5px solid #444;padding:7px 10px;background:#dce8f0;font-size:10.5pt;text-align:center;letter-spacing:.05em;">
                    DATOS DEL SERVIDOR
                </th>
            </tr>
            <tr>
                <td style="border:1px solid #888;padding:6px 10px;width:25%;font-size:9.5pt;background:#f7f9fb;"><strong>Cédula:</strong></td>
                <td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;">${cedula}</td>
                <td style="border:1px solid #888;padding:6px 10px;width:30%;font-size:9.5pt;background:#f7f9fb;"><strong>Nombres y Apellidos:</strong></td>
                <td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;">${nombre}</td>
            </tr>
            <tr>
                <td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;background:#f7f9fb;"><strong>Unidad / Área Actual:</strong></td>
                <td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;">${getV('inpUnidadActual')}</td>
                <td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;background:#f7f9fb;"><strong>Cargo Actual:</strong></td>
                <td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;">${getV('inpPuestoActual')}</td>
            </tr>
            <tr>
                <td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;background:#f7f9fb;"><strong>Tipo de Contrato:</strong></td>
                <td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;">${getV('inpContratoActual')}</td>
                <td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;background:#f7f9fb;"><strong>Proceso Actual:</strong></td>
                <td style="border:1px solid #888;padding:6px 10px;font-size:9.5pt;">${getV('inpProcesoActual')}</td>
            </tr>
        </table>

        <!-- SITUACIÓN ACTUAL vs PROPUESTA -->
        <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
            <tr>
                <th style="border:1.5px solid #444;padding:7px 10px;background:#dce8f0;font-size:10.5pt;width:50%;text-align:center;">SITUACIÓN ACTUAL</th>
                <th style="border:1.5px solid #444;padding:7px 10px;background:#dce8f0;font-size:10.5pt;width:50%;text-align:center;">SITUACIÓN PROPUESTA</th>
            </tr>
            <tr>
                <td style="border:1px solid #888;padding:10px 12px;font-size:9.5pt;vertical-align:top;line-height:2.0;">
                    <strong>Área / Unidad:</strong> ${getV('inpUnidadActual') || '—'}<br>
                    <strong>Cargo / Puesto:</strong> ${getV('inpPuestoActual') || '—'}<br>
                    <strong>Remuneración ($):</strong> ${getV('inpSueldoActual') || '—'}<br>
                    <strong>Tipo de Contrato:</strong> ${getV('inpContratoActual') || '—'}
                </td>
                <td style="border:1px solid #888;padding:10px 12px;font-size:9.5pt;vertical-align:top;line-height:2.0;">
                    <strong>Área / Unidad:</strong> ${unidadProp || '—'}<br>
                    <strong>Cargo / Puesto:</strong> ${puestoProp || '—'}<br>
                    <strong>Remuneración ($):</strong> ${sueldoProp || '—'}<br>
                    <strong>Tipo de Contrato:</strong> ${contratoProp}
                </td>
            </tr>
        </table>

        ${bloqueTipo}
        ${bloqueFirmas}

        <p style="font-size:8pt;color:#888;margin-top:24px;padding-top:10px;border-top:1px solid #e0e0e0;text-align:center;">
            Documento generado el ${fechaHoy} &nbsp;|&nbsp; Sistema de Talento Humano — Autoridad Portuaria de Manta
        </p>
    </div>`;

    document.getElementById('modal-vp-body').innerHTML = html;
    document.getElementById('tituloModalDocumento').innerHTML = `<i class="bi bi-file-earmark-text-fill"></i> Vista Previa — ${codigoTrabajo ? 'Formulario Abreviado Laboral' : 'Acción de Personal'}`;

    const overlay = document.getElementById('modal-vista-previa');
    overlay.style.opacity = '1';
    overlay.style.pointerEvents = 'auto';
    document.getElementById('modal-vp-inner').style.transform = 'translateY(0)';
    document.body.style.overflow = 'hidden';
}


function cerrarModalVistaPrevia() {
    const overlay = document.getElementById('modal-vista-previa');
    overlay.style.opacity = '0';
    overlay.style.pointerEvents = 'none';
    document.getElementById('modal-vp-inner').style.transform = 'translateY(30px)';
    document.body.style.overflow = '';
}

function imprimirDesdeModal() {
    const content = document.getElementById('modal-vp-body').innerHTML;
    const w = window.open('', '_blank');
    w.document.write(`<!DOCTYPE html><html lang="es"><head>
        <meta charset="UTF-8">
        <title>Acción de Personal — APM</title>
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

document.getElementById('modal-vista-previa')?.addEventListener('click', e => {
    if (e.target === e.currentTarget) cerrarModalVistaPrevia();
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') cerrarModalVistaPrevia();
});

/* ── 10b. (legacy) imprimirVista – ya no usada directamente ──────────────── */
function imprimirVista() {
    abrirModalVistaPrevia();
}

</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
