<?php
/* accion_personal.php – Vista: Formulario Fase 2 – Acción de Personal (APM)
   Carga datos del empleado seleccionado (situación actual) y permite capturar
   la situación propuesta. Completamente reactivo con JavaScript Vanilla.
   Sin dependencias externas de Bootstrap; usa el design system propio del portal. */

$emp  = $empleado    ?? [];
$hist = $historialLab ?? [];
$nro  = $nroAccion   ?? ('APM-TH-' . date('Y') . '-001');
$preselCedula = $preselCedula ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acción de Personal | Talento Humano APM</title>
    <meta name="description" content="Formulario de Acción de Personal — Autoridad Portuaria de Manta.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/fonts.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
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
        <!-- TOPBAR -->
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
                <a href="<?= BASE_URL ?>/talento-humano/directorio" class="btn btn-ghost">
                    <i class="bi bi-arrow-left"></i> Volver al Directorio
                </a>
            </div>
        </header>

        <main class="main">
            <div class="content-shell">
                <!-- ENCABEZADO DEL DOCUMENTO -->
                <div class="accion-header">
                    <div>
                        <h2><i class="bi bi-file-earmark-text"></i> Formulario de Acción de Personal</h2>
                        <p>Art. 21 LOSEP — Registro oficial de movimiento de personal de la APM</p>
                    </div>
                    <span class="accion-num" id="displayNroAccion"><?= htmlspecialchars($nro) ?></span>
                </div>

                <!-- BUSCADOR DE FUNCIONARIO (conectado a BD real via AJAX) -->
                <div class="buscador-banner">
                    <span>
                        <i class="bi bi-person-badge" style="color:#0e7490;"></i>
                        <strong>Buscar funcionario por cédula:</strong>
                        Ingrese el número de cédula y pulse Buscar para autocompletar la Situación Actual.
                    </span>
                    <div style="display:flex; flex-direction:column; gap:6px; min-width:340px;">
                        <div style="display:flex; gap:8px; align-items:center;">
                            <input type="text" inputmode="numeric" pattern="[0-9]*"
                                   id="inputBuscarCedula"
                                   placeholder="Ej: 1300000000"
                                   maxlength="13"
                                   style="padding:9px 14px; border:1.5px solid rgba(14,116,144,.4); border-radius:10px;
                                          font-size:.83rem; font-weight:600; background:#ecfeff; flex:1;
                                          outline:none; transition:border-color .2s; letter-spacing:.05em;"
                                   onfocus="this.style.borderColor='#0e7490'"
                                   onblur="this.style.borderColor='rgba(14,116,144,.4)'"
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();buscarPorCedula();}">
                            <button type="button" onclick="buscarPorCedula()"
                                    id="btnBuscarServidor"
                                    style="padding:9px 16px; background:#0e7490; color:#fff; border:none;
                                           border-radius:10px; font-size:.83rem; font-weight:700; cursor:pointer;
                                           transition:background .2s; white-space:nowrap;"
                                    onmouseover="this.style.background='#0891b2'"
                                    onmouseout="this.style.background='#0e7490'">
                                <i class="bi bi-search"></i> Autocompletar
                            </button>
                        </div>
                        <span id="estadoBusqueda" style="font-size:.73rem; color:#155e75; opacity:.8;">
                            <i class="bi bi-info-circle"></i> Ingrese la cédula y pulse Autocompletar o presione Enter.
                        </span>
                    </div>
                </div>

                <!-- FORMULARIO PRINCIPAL -->
                <section class="card form-card">
                    <form method="POST" action="<?= BASE_URL ?>/talento-humano/accion-personal/guardar"
                          id="formAccionPersonal">

                        <!-- Campos ocultos: identificación y claves de situación actual -->
                        <input type="hidden" name="numero_accion"              id="nroAccionInput"        value="<?= htmlspecialchars($nro) ?>">
                        <input type="hidden" name="empleado_id"                id="hidEmpleadoId"         value="<?= (int)($emp['empleado_id'] ?? 0) ?>">
                        <input type="hidden" name="actual_unidad_id"           id="hidUnidadId"           value="0">
                        <input type="hidden" name="actual_puesto_id"           id="hidPuestoId"           value="0">
                        <!-- Campos hidden que AJAX llena al buscar la cédula (viajan al guardar()) -->
                        <input type="hidden" name="actual_unidad_id_hidden"    id="hidActualUnidadId"     value="0">
                        <input type="hidden" name="actual_puesto_id_hidden"    id="hidActualPuestoId"     value="0">
                        <input type="hidden" name="actual_remuneracion_hidden" id="hidActualRemuneracion" value="0">

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
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px;">
                            <div class="accion-field">
                                <label>Fecha de Elaboración</label>
                                <input type="date" name="fecha_elaboracion" id="inpFechaElab"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="accion-field">
                                <label>Rige desde</label>
                                <input type="date" name="rige_desde" id="inpRigeDesde"
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="accion-field">
                                <label>Rige hasta <small style="color:#94a3b8;">(vacío = permanente)</small></label>
                                <input type="date" name="rige_hasta" id="inpRigeHasta">
                            </div>
                        </div>

                        <!-- ══ SECCIÓN 2: TIPO DE ACCIÓN ════════════════════════════════════ -->
                        <div class="section-sep"><span><i class="bi bi-list-check"></i> 2 · Motivo de la Acción (Art. 21 LOSEP)</span></div>
                        <input type="hidden" name="tipo_accion" id="hidTipoAccion" required>
                        <div class="tipo-accion-grid" id="tipoAccionGrid">
                            <div class="tipo-chip" data-value="INGRESO"  onclick="seleccionarTipo(this)">
                                <i class="bi bi-person-check-fill"></i> Ingreso
                            </div>
                            <div class="tipo-chip" data-value="ASCENSO"  onclick="seleccionarTipo(this)">
                                <i class="bi bi-graph-up-arrow"></i> Ascenso / Incremento RMU
                            </div>
                            <div class="tipo-chip" data-value="TRASLADO" onclick="seleccionarTipo(this)">
                                <i class="bi bi-arrow-left-right"></i> Traslado / Cambio Admin.
                            </div>
                            <div class="tipo-chip" data-value="COMISION" onclick="seleccionarTipo(this)">
                                <i class="bi bi-briefcase-fill"></i> Comisión de Servicios
                            </div>
                            <div class="tipo-chip" data-value="VACACIONES" onclick="seleccionarTipo(this)">
                                <i class="bi bi-umbrella"></i> Vacaciones
                            </div>
                            <div class="tipo-chip" data-value="CESACION" onclick="seleccionarTipo(this)">
                                <i class="bi bi-door-open"></i> Cesación de Funciones
                            </div>
                            <div class="tipo-chip" data-value="OTRO"     onclick="seleccionarTipo(this)">
                                <i class="bi bi-three-dots"></i> Otro (Detallar)
                            </div>
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
                                <div class="accion-field">
                                    <label>Remuneración Mensual Unificada ($)</label>
                                    <input type="text" id="inpSueldoActual" name="actual_remuneracion" readonly
                                           style="font-weight:700; text-align:right;"
                                           value="<?= htmlspecialchars($hist['remuneracion'] ?? '') ?>">
                                </div>
                                <div class="accion-field">
                                    <label>Tipo de Contrato</label>
                                    <input type="text" id="inpContratoActual" readonly
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
                                <div class="accion-field">
                                    <label>Proceso Institucional Propuesto</label>
                                    <select class="inputs-propuesta" name="propuesta_proceso" id="propProceso">
                                        <option value="">Seleccione...</option>
                                        <option value="Procesos Gobernantes">Procesos Gobernantes</option>
                                        <option value="Procesos Sustantivos">Procesos Sustantivos</option>
                                        <option value="Procesos Adjetivos">Procesos Adjetivos</option>
                                    </select>
                                </div>
                                <div class="accion-field">
                                    <label>Nueva Unidad Administrativa <span class="req">*</span></label>
                                    <select class="inputs-propuesta" name="propuesta_unidad" id="propUnidad">
                                        <option value="">Seleccione el área...</option>
                                        <?php foreach (($areas ?? []) as $area): ?>
                                        <option value="<?= (int)$area['unidad_id'] ?>">
                                            <?= htmlspecialchars($area['nombre_unidad']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="propuesta_unidad_id" id="hidPropUnidadId" value="0">
                                </div>
                                <div class="accion-field">
                                    <label>Nueva Denominación del Puesto <span class="req">*</span></label>
                                    <select class="inputs-propuesta" name="propuesta_puesto" id="propPuesto">
                                        <option value="">Seleccione el cargo...</option>
                                        <?php foreach (($cargos ?? []) as $cargo): ?>
                                        <option value="<?= (int)$cargo['puesto_id'] ?>">
                                            <?= htmlspecialchars($cargo['nombre_puesto']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="propuesta_puesto_id" id="hidPropPuestoId" value="0">
                                </div>
                                <div class="accion-field">
                                    <label>Nueva Remuneración Mensual ($) <span class="req">*</span></label>
                                    <input type="number" step="0.01" min="0"
                                           class="inputs-propuesta" name="propuesta_remuneracion"
                                           id="propSueldo" placeholder="0.00"
                                           style="text-align:right; font-weight:700;">
                                </div>
                                <div class="accion-field">
                                    <label>Tipo de Contrato Propuesto</label>
                                    <select class="inputs-propuesta" name="propuesta_contrato" id="propContrato">
                                        <option value="">Sin cambio</option>
                                        <option value="Nombramiento">Nombramiento Permanente</option>
                                        <option value="Contrato">Contrato de Servicios</option>
                                        <option value="Contrato Ocasional">Contrato Ocasional</option>
                                    </select>
                                </div>
                            </div>
                        </div><!-- /split-wrap -->

                        <!-- ══ SECCIÓN 4: MOTIVACIÓN Y NOTIFICACIÓN ══════════════════════ -->
                        <div class="section-sep"><span><i class="bi bi-chat-quote-fill"></i> 4 · Motivación y Notificación</span></div>
                        <div class="firma-grid">
                            <div class="accion-field" style="grid-column:1/-1;">
                                <label>Motivación / Fundamento Legal <span class="req">*</span></label>
                                <textarea name="motivacion_texto" rows="4" required
                                          placeholder="Describa el fundamento legal y la justificación institucional para esta acción de personal (Art. 21 LOSEP)..."></textarea>
                            </div>
                            <div class="accion-field">
                                <label>Presentó Declaración Juramentada</label>
                                <select name="presento_declaracion">
                                    <option value="NO APLICA">No aplica</option>
                                    <option value="SI">Sí, presentó</option>
                                    <option value="NO">No presentó</option>
                                </select>
                            </div>
                            <div class="accion-field">
                                <label>
                                    <input type="checkbox" name="notificacion_electronica"
                                           id="chkNotificacion" onchange="toggleNotificacion()"
                                           style="width:auto; margin-right:6px;">
                                    Notificar por correo electrónico
                                </label>
                                <input type="email" name="correo_notificacion" id="inpCorreoNotif"
                                       placeholder="correo@apm.gob.ec" disabled
                                       style="margin-top:8px;">
                            </div>
                        </div>

                        <!-- ══ ACCIONES ══════════════════════════════════════════════════ -->
                        <div class="accion-actions">
                            <a href="<?= BASE_URL ?>/talento-humano/directorio" class="btn btn-outline">
                                <i class="bi bi-x-lg"></i> Cancelar
                            </a>
                            <button type="button" class="btn btn-ghost" onclick="imprimirVista()"
                                    id="btn-imprimir-accion">
                                <i class="bi bi-printer"></i> Vista Previa / Imprimir
                            </button>
                            <button type="submit" class="btn btn-primary" id="btn-generar-accion"
                                    onclick="return validarFormulario()">
                                <i class="bi bi-file-earmark-check-fill"></i> Generar Documento
                            </button>
                        </div>

                    </form>
                </section>
            </div>
        </main>
    </section>
</div>

<div id="toastContainer" class="toast-container"></div>

<?php if (!empty($_GET['msg'])): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        showToast(<?= json_encode(htmlspecialchars($_GET['msg'])) ?>,
                  <?= ($_GET['ok'] ?? '0') === '1' ? "'success'" : "'error'" ?>);
    });
</script>
<?php endif; ?>

<script src="<?= BASE_URL ?>/public/js/layout_sidebar.js"></script>
<script src="<?= BASE_URL ?>/public/js/toast.js"></script>
<script src="<?= BASE_URL ?>/public/js/talento_humano.js"></script>
<script>
/* ══════════════════════════════════════════════════════════════════════════
   accion_personal.js – Lógica interactiva exclusiva de este formulario
   ══════════════════════════════════════════════════════════════════════════ */

/* BASE_URL inyectado desde PHP para que fetch() funcione en cualquier entorno */
const BASE_URL = '<?= BASE_URL ?>';

/* ── 1. Inicialización al cargar la página ───────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    // Fecha actual en topbar
    document.getElementById('currentDate').textContent =
        new Date().toLocaleDateString('es-EC', { weekday: 'short', year: 'numeric', month: 'long', day: 'numeric' });

    // Si la URL trae ?cedula= o ?id=, autocompletar al cargar (viene del Directorio)
    const params  = new URLSearchParams(window.location.search);
    const cedUrl  = params.get('cedula');
    const idUrl   = params.get('id');

    if (cedUrl) {
        // Modo cédula: el link del directorio puede traer la cédula directamente
        document.getElementById('inputBuscarCedula').value = cedUrl;
        buscarPorCedula();
    } else if (idUrl && parseInt(idUrl) > 0) {
        // Modo ID: buscar por ID interno y mostrar la cédula al completar
        fetch(`${BASE_URL}/talento-humano/accion-personal/buscar-servidor?id=${parseInt(idUrl)}`)
            .then(r => r.json())
            .then(json => {
                if (json.success && json.data) {
                    document.getElementById('inputBuscarCedula').value = json.data.cedula ?? '';
                    llenarFormularioConEmpleado(json.data);
                    document.getElementById('estadoBusqueda').innerHTML =
                        `<i class="bi bi-check-circle-fill" style="color:#059669"></i> Expediente cargado: ${json.data.nombres} ${json.data.apellidos}`;
                    document.getElementById('estadoBusqueda').style.color = '#059669';
                }
            })
            .catch(() => {});
    }
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
        btnBuscar.innerHTML = '<i class="bi bi-search"></i> Autocompletar';
    }
}

/* ── 3. Llenar formulario con datos reales desde la BD ───────────────────── */
function llenarFormularioConEmpleado(emp) {
    // Campos de cabecera del formulario
    document.getElementById('inpCedula').value      = emp.cedula        ?? '';
    document.getElementById('inpNombres').value     = `${emp.apellidos ?? ''} ${emp.nombres ?? ''}`.trim();
    document.getElementById('hidEmpleadoId').value  = emp.id            ?? 0;
    document.getElementById('hidUnidadId').value    = emp.id            ?? 0;

    // Situación actual (bloque izquierdo — readonly, datos directos de la BD)
    document.getElementById('inpProcesoActual').value  = emp.tipo_contrato  ?? '';
    document.getElementById('inpUnidadActual').value   = emp.direccion_area ?? '';
    document.getElementById('inpPuestoActual').value   = emp.cargo          ?? '';
    document.getElementById('inpSueldoActual').value   = '';               // sin campo en la vista
    document.getElementById('inpContratoActual').value = emp.tipo_contrato  ?? '';

    // Campos ocultos que viajan al servidor al hacer submit (Situación Actual)
    document.getElementById('hidActualUnidadId').value     = emp.unidad_id            ?? 0;
    document.getElementById('hidActualPuestoId').value     = emp.puesto_id            ?? 0;
    document.getElementById('hidActualRemuneracion').value = emp.remuneracion_unificada ?? 0;

    // Limpiar lado propuesta para comenzar desde cero
    limpiarPropuesta();
}

/* ── 6. Selección del tipo de acción (chips interactivos) ─────────────────── */
function seleccionarTipo(chip) {
    document.querySelectorAll('.tipo-chip').forEach(c => c.classList.remove('selected'));
    chip.classList.add('selected');
    const valor = chip.dataset.value;
    document.getElementById('hidTipoAccion').value = valor;

    // Mostrar/ocultar campo "otro"
    document.getElementById('bloqueOtro').style.display = valor === 'OTRO' ? 'block' : 'none';

    // Reglas de negocio: bloquear propuesta en acciones que no cambian posición
    aplicarReglasNegocio(valor);
}

function aplicarReglasNegocio(accion) {
    const bloquear = ['VACACIONES', 'CESACION'].includes(accion);
    document.querySelectorAll('.inputs-propuesta').forEach(el => {
        el.disabled = bloquear;
        el.style.opacity = bloquear ? '.45' : '1';
        if (bloquear) el.value = '';
    });

    if (bloquear) {
        showToast?.(`Para acciones de tipo "${accion}" no se requiere Situación Propuesta.`, 'info');
    }
}

/* ── 7. Notificación por correo ───────────────────────────────────────────── */
function toggleNotificacion() {
    const chk   = document.getElementById('chkNotificacion');
    const input = document.getElementById('inpCorreoNotif');
    input.disabled = !chk.checked;
    if (!chk.checked) input.value = '';
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
    document.getElementById('bloqueOtro').style.display = 'none';
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
    const cedula = document.getElementById('inpCedula').value.trim();
    const tipo   = document.getElementById('hidTipoAccion').value.trim();
    const motiv  = document.querySelector('textarea[name="motivacion_texto"]').value.trim();

    if (!cedula) {
        showToast?.('Seleccione un empleado antes de continuar.', 'error');
        return false;
    }
    if (!tipo) {
        showToast?.('Debe seleccionar el tipo de Acción de Personal.', 'error');
        return false;
    }
    if (!motiv) {
        showToast?.('Ingrese la motivación / fundamento legal de la acción.', 'error');
        return false;
    }
    showToast?.('Enviando documento al servidor...', 'info');
    return true;
}

/* ── 10. Vista previa / PDF del empleado seleccionado ────────────────────── */
function imprimirVista() {
    const empId = document.getElementById('hidEmpleadoId')?.value?.trim();
    if (!empId || empId === '0') {
        showToast?.('Primero busque y seleccione un funcionario para generar la ficha PDF.', 'error');
        return;
    }
    // Abre el PDF de la ficha integral del funcionario en nueva pestaña
    const url = `${BASE_URL}/talento-humano/empleado/imprimir-ficha?id=${empId}`;
    showToast?.('Generando ficha PDF del funcionario...', 'info');
    window.open(url, '_blank', 'noopener,noreferrer');
}
</script>
</body>
</html>
