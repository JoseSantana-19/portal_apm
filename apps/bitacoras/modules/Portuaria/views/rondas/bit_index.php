<?php
/**
 * Vista: Bitácora de rondas / Reporte diario de protección.
 * Variables disponibles: $pageTitle, $diasEdicionGuardia, $diasEdicionBitacora,
 * $fechaMinimaEdicionBitacora, $puedeConfigurarDias, $fechaMaxServidor
 * Extraída de bit_rondas.php (contenido puro, sin head/navbar/sidebar:
 * eso ya lo pone views/layouts/main.php).
 *
 * El JS (bitacora_rondas.js) ya llama a bitacoras/ronda/api (RondaController::api()).
 */
$url_js_bitacora_rondas = 'public/js/portuaria/bitacora_rondas.js?v=' . (
    is_file(ROOT_PATH . '/public/js/portuaria/bitacora_rondas.js')
        ? (string) filemtime(ROOT_PATH . '/public/js/portuaria/bitacora_rondas.js')
        : '1'
);
$url_js_xlsx_bundle = 'public/librerias/xlsx-js-style/1.2.0/xlsx.bundle.js?v=' . (
    is_file(ROOT_PATH . '/public/librerias/xlsx-js-style/1.2.0/xlsx.bundle.js')
        ? (string) filemtime(ROOT_PATH . '/public/librerias/xlsx-js-style/1.2.0/xlsx.bundle.js')
        : '1'
);
?>
<style>
    body.apm-rondas-page { background-color: #f8f9fa; }
    .apm-card-pro { border: 0; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); margin-bottom: 1.5rem; }
    .apm-br-perfil-card .card-body { background: linear-gradient(180deg, #fafbfc 0%, #fff 100%); border-radius: 0 0 0.375rem 0.375rem; }
    .apm-br-info-compact .form-label { font-size: 0.7rem; margin-bottom: 0; color: #6c757d; padding-bottom: 0.15rem; letter-spacing: 0.02em; }
    .apm-br-info-compact .apm-br-help-line { font-size: 0.72rem; line-height: 1.35; }
    .apm-br-perfil-card .card-header .apm-br-subtitle { font-size: 0.72rem; }
    .apm-br-turno-fila .form-control,
    .apm-br-turno-fila .form-select {
        padding-left: 0.35rem;
        padding-right: 0.35rem;
    }
    .apm-br-turno-fila select#brTurno {
        font-size: 0.9rem;
        line-height: 1.35;
    }
    .apm-br-textarea-actividad { min-height: 8.5rem; }
    .apm-card-pro .form-control,
    .apm-card-pro .form-select { border-radius: .6rem; }
    .apm-card-pro textarea.form-control:focus,
    .apm-card-pro .form-control:focus,
    .apm-card-pro .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 .2rem rgba(13,110,253,.15);
    }
    .apm-tabla-rondas {
        border-collapse: separate;
        border-spacing: 0 5px;
    }
    .apm-tabla-rondas thead th {
        background: #5f6f82;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        white-space: nowrap;
        color: #f1f3f5;
        border: 0;
        border-bottom: 1px solid #d1d8e0;
        padding: 12px 15px;
    }
    .apm-tabla-rondas td {
        font-size: 0.875rem;
        vertical-align: middle;
        padding: 12px 15px;
        border: 0;
        border-top: 1px solid #eef2f6;
        border-bottom: 1px solid #e2e8ef;
        background: #fff;
    }
    .apm-tabla-rondas tbody tr:nth-child(even) td { background: #f8fafc; }
    .apm-tabla-rondas tbody tr:nth-child(odd) td { background: #ffffff; }
    .apm-tabla-rondas tbody tr td:first-child {
        border-left: 0;
        border-top-left-radius: .45rem;
        border-bottom-left-radius: .45rem;
    }
    .apm-tabla-rondas tbody tr td:last-child {
        border-right: 0;
        border-top-right-radius: .45rem;
        border-bottom-right-radius: .45rem;
    }
    .apm-nivel-badge {
        font-size: .72rem;
        font-weight: 600;
        border-radius: 999px;
        padding: .35rem .7rem;
        display: inline-block;
        line-height: 1.2;
        background: #fff;
    }
    .apm-nivel-badge-critico {
        background: rgba(132, 32, 41, 0.10);
        color: #842029;
        border: 1px solid rgba(132, 32, 41, 0.28);
    }
    .apm-nivel-badge-medio {
        background: rgba(102, 77, 3, 0.10);
        color: #664d03;
        border: 1px solid rgba(102, 77, 3, 0.28);
    }
    .apm-nivel-badge-normal {
        background: rgba(5, 44, 101, 0.10);
        color: #052c65;
        border: 1px solid rgba(5, 44, 101, 0.28);
    }
    .apm-tabla-rondas td.apm-cell-guardia {
        min-width: 12.5rem;
        line-height: 1.5;
        vertical-align: top;
    }
    .apm-tabla-rondas .apm-guardia-nombre { display: block; }
    .apm-tabla-rondas .apm-guardia-cedula {
        display: block;
        margin-top: 0.45rem;
        font-size: 0.8125rem;
    }
    .apm-tabla-busqueda { border-spacing: 0 5px; }
    .apm-tabla-busqueda td { font-size: 0.875rem; vertical-align: middle; }
    .apm-btn-institucional {
        background-color: #0b5ed7;
        border-color: #0a58ca;
        color: #fff;
    }
    .apm-btn-institucional:hover,
    .apm-btn-institucional:focus {
        background-color: #0a58ca;
        border-color: #084298;
        color: #fff;
    }
    .apm-badge-cambio-dia { font-size: 0.65rem; vertical-align: middle; }
    .apm-actividad-wrap {
        position: relative;
        z-index: 20;
        overflow: visible;
    }
    .card.apm-card-nuevo-reg .card-body {
        overflow: visible;
    }
    .apm-actividad-sug {
        position: absolute;
        left: 0;
        right: 0;
        top: 100%;
        margin-top: 2px;
        z-index: 1060;
        max-height: 240px;
        overflow-y: auto;
        display: none;
        box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.12);
        border-radius: 0.375rem;
    }
    .apm-actividad-sug.apm-open { display: block; }
    .apm-actividad-sug .list-group-item {
        cursor: pointer;
        font-size: 0.8125rem;
        padding: 0.4rem 0.65rem;
        border-color: #dee2e6;
    }
    .apm-actividad-sug .list-group-item:hover { background: #f8f9fa; }
    .apm-actividad-sug .text-muted { font-size: 0.7rem; }
    .apm-btn-tabla-toggle .bi { transition: transform .18s ease; }
    .apm-btn-tabla-toggle[aria-expanded="false"] .bi { transform: rotate(-90deg); }
    .apm-card-info .form-label { font-size: .72rem; letter-spacing: .3px; }
    .apm-card-info .form-control { min-height: calc(1.8em + .5rem + 2px); }
    .apm-edit-help-chip {
        display: inline-flex;
        align-items: center;
        margin-left: .45rem;
        padding: 0;
        border: 0;
        background: transparent;
        color: #6c757d;
        font-size: .72rem;
        font-weight: 500;
        line-height: 1.2;
        white-space: nowrap;
        vertical-align: middle;
        letter-spacing: .01em;
    }
    .apm-card-busqueda .apm-busqueda-title {
        font-size: .82rem;
        letter-spacing: .6px;
        text-transform: uppercase;
        color: #6c757d;
        margin-bottom: .75rem;
    }
    .apm-card-busqueda .row.g-3 { margin-bottom: 1rem !important; }
    .apm-preview-scroll {
        max-height: 400px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #b8c1cc #f1f3f5;
    }
    .apm-preview-scroll::-webkit-scrollbar { width: 8px; }
    .apm-preview-scroll::-webkit-scrollbar-track { background: #f1f3f5; }
    .apm-preview-scroll::-webkit-scrollbar-thumb {
        background-color: #b8c1cc;
        border-radius: 999px;
        border: 2px solid #f1f3f5;
    }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
        <h1 class="h4 text-primary mb-1">Reporte diario de protección</h1>
        <p class="text-muted small mb-0">Bitácora de rondas — registro por turno (Mañana, Tarde, Noche).</p>
    </div>
</div>

<div class="row g-3 align-items-start mb-2 mb-md-3">
    <div class="col-12 col-md-5">
        <div class="card apm-card-pro apm-card-info apm-br-perfil-card shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold text-primary small">Información del turno</span>
                <p class="small text-muted mb-0 mt-1 apm-br-subtitle">Contexto de sesión y fecha operativa</p>
            </div>
            <div class="card-body pt-3 pb-3 apm-br-info-compact">
                <div class="row g-2 mb-2">
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="brUsuario">Usuario</label>
                        <input type="text" class="form-control form-control-sm bg-light" id="brUsuario" readonly autocomplete="username">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="brFecha">Fecha (servidor)</label>
                        <input type="text" class="form-control form-control-sm bg-light" id="brFecha" readonly>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="brHora">Hora</label>
                        <input type="text" class="form-control form-control-sm bg-light" id="brHora" readonly>
                    </div>
                </div>
                <div class="row g-2 align-items-end mb-2 apm-br-turno-fila px-1 px-sm-2">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="brTurno">Turno actual</label>
                        <select class="form-select form-select-sm fw-semibold text-primary" id="brTurno" title="Seleccione el turno operativo">
                            <option value="Mañana">Mañana (07:00 - 15:00)</option>
                            <option value="Tarde">Tarde (15:00 - 23:00)</option>
                            <option value="Noche">Noche (23:00 - 07:00)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="brHoraInicio">Inicio del turno</label>
                        <input type="time" class="form-control form-control-sm" id="brHoraInicio" step="60" title="Franja horaria del turno (se guarda al grabar un registro)">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="brHoraFin">Fin del turno</label>
                        <input type="time" class="form-control form-control-sm" id="brHoraFin" step="60" title="En turno noche puede ser menor que el inicio (cruza medianoche); la fecha operativa sigue siendo la misma">
                    </div>
                </div>
                <small class="text-muted fst-italic d-block mb-2 apm-br-help-line">Ajuste la franja antes de grabar. En turno noche, lo registrado después de medianoche sigue en la misma fecha operativa.</small>
                <div class="mb-0">
                    <label class="form-label" for="brFechaOp">Fecha operativa del turno</label>
                    <div class="input-group input-group-sm">
                        <input type="date" class="form-control form-control-sm" id="brFechaOp" title="Seleccione una fecha operativa para consultar el mismo turno.">
                        <button type="button" class="btn btn-outline-secondary" id="brBtnFechaHoy" title="Volver a hoy">Hoy</button>
                    </div>
                </div>
                <?php if ($puedeConfigurarDias): ?>
                    <div class="mt-3" id="brAdminDiasEdicionWrap">
                        <label class="form-label" for="brAdminDiasEdicion">Días permitidos para edición de guardias</label>
                        <div class="input-group input-group-sm">
                            <select class="form-select form-select-sm" id="brAdminDiasEdicion">
                                <option value="1" <?php echo $diasEdicionGuardia === 1 ? 'selected' : ''; ?>>1 día</option>
                                <option value="3" <?php echo $diasEdicionGuardia === 3 ? 'selected' : ''; ?>>3 días</option>
                                <option value="5" <?php echo $diasEdicionGuardia === 5 ? 'selected' : ''; ?>>5 días</option>
                                <option value="7" <?php echo $diasEdicionGuardia === 7 ? 'selected' : ''; ?>>7 días</option>
                            </select>
                            <button type="button" class="btn btn-outline-primary" id="brBtnGuardarDiasEdicion">Guardar</button>
                        </div>
                        <small class="text-muted d-block mt-1">Esta configuración controla cuántos días atrás puede editar un guardia.</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-7">
        <div class="card apm-card-pro apm-card-nuevo-reg shadow-sm border-0">
            <div class="card-header bg-white border-bottom py-2">
                <span class="fw-semibold">Nuevo registro</span>
            </div>
            <div class="card-body">
                <div class="alert alert-warning py-2 px-3 small d-none" id="brAvisoFechaPasada" role="alert">
                    Está consultando un turno pasado. Los nuevos registros solo se permiten en la fecha actual.
                </div>
                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between align-items-center gap-2" for="brActividad">
                        <span>Actividad</span>
                        <small class="text-muted fw-normal">Sugerencias flotantes al escribir</small>
                    </label>
                    <div class="apm-actividad-wrap" id="brActividadWrap">
                        <textarea class="form-control apm-br-textarea-actividad" id="brActividad" rows="5" maxlength="8000" placeholder="Describa la actividad u observación..." required autocomplete="off"></textarea>
                        <div class="list-group apm-actividad-sug border" id="brActividadSug" aria-hidden="true"></div>
                    </div>
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label" for="brAlerta">Nivel de alerta</label>
                        <select class="form-select" id="brAlerta" required></select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3 d-none" id="brWrapFechaRegistroEdicion">
                        <label class="form-label d-flex align-items-center mb-1" for="brFechaRegistroEdicion">
                            <span>Fecha del registro</span>
                            <small class="apm-edit-help-chip" id="brHelpFechaRegistroEdicion">
                                <?php if ($diasEdicionBitacora === null) { ?>
                                    Ventana: sin límite
                                <?php } else { ?>
                                    Ventana: <?php echo (int) $diasEdicionBitacora; ?> día(s)
                                <?php } ?>
                            </small>
                        </label>
                        <input type="date" class="form-control" id="brFechaRegistroEdicion" max="<?php echo htmlspecialchars($fechaMaxServidor, ENT_QUOTES, 'UTF-8'); ?>" <?php if ($fechaMinimaEdicionBitacora !== null) { ?>min="<?php echo htmlspecialchars($fechaMinimaEdicionBitacora, ENT_QUOTES, 'UTF-8'); ?>"<?php } ?> title="Día calendario del registro (al editar)">
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <label class="form-label" for="brHoraRegistro">Hora del registro</label>
                        <input type="time" class="form-control" id="brHoraRegistro" name="hora_registro" step="1" required title="Hora cronológica del registro en la bitácora">
                    </div>
                    <div class="col-12 col-lg-3 d-flex flex-wrap gap-2 align-items-end">
                        <button type="button" class="btn apm-btn-institucional flex-grow-1 flex-lg-grow-0" id="brBtnGrabar">
                            <i class="bi bi-save me-1"></i>Grabar
                        </button>
                        <button type="button" class="btn btn-outline-secondary d-none" id="brBtnCancelarEdicion">
                            Cancelar edición
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-2 mb-md-3">
    <div class="col-12">
        <div class="card apm-card-pro shadow-sm border-0">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                <span class="fw-semibold" id="brPreviewTitulo">Previsualización — turno actual</span>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <button type="button" class="btn btn-sm apm-btn-institucional" id="brBtnExportPreviewPdf">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Exportar PDF
                    </button>
                    <button type="button" class="btn btn-sm apm-btn-institucional" id="brBtnExportPreviewExcel">
                        <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
                    </button>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Orden de previsualización">
                        <button
                            type="button"
                            class="btn apm-btn-institucional dropdown-toggle"
                            id="brOrdenBtn"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="Orden de registros">
                            <i class="bi bi-sort-numeric-up" id="brOrdenIcon"></i>
                            <span id="brOrdenTexto" class="ms-1">Más antiguo</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="brOrdenBtn">
                            <li>
                                <button type="button" class="dropdown-item br-orden-opcion" data-orden="ASC">
                                    <i class="bi bi-sort-numeric-up me-1"></i>Más antiguo
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item br-orden-opcion" data-orden="DESC">
                                    <i class="bi bi-sort-numeric-down me-1"></i>Más reciente
                                </button>
                            </li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary apm-btn-tabla-toggle" id="brBtnTogglePreview" data-bs-toggle="collapse" data-bs-target="#brPreviewCollapse" aria-expanded="true" aria-controls="brPreviewCollapse" title="Mostrar/Ocultar tabla">
                        <i class="bi bi-chevron-down me-1"></i><span id="brToggleTxt">Ocultar</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="brBtnRefrescar" title="Actualizar tabla">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0 collapse show" id="brPreviewCollapse">
                <div class="table-responsive apm-preview-scroll" id="brPreviewScroll">
                    <table class="table table-hover table-sm mb-0 apm-tabla-rondas" id="tablaTurnoActual">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Fecha registro</th>
                                <th scope="col">Hora</th>
                                <th scope="col" id="brThGuardia" class="d-none">Guardia</th>
                                <th scope="col">Actividad</th>
                                <th scope="col">Nivel</th>
                                <th scope="col" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaTurnoBody">
                            <tr><td colspan="5" class="text-muted text-center py-3 small">Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="card apm-card-pro apm-card-busqueda shadow-sm border-0">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                <span class="fw-semibold">Búsqueda de rondas anteriores</span>
                <div class="btn-group btn-group-sm" role="group" aria-label="Orden de búsqueda histórica">
                    <button
                        type="button"
                        class="btn apm-btn-institucional dropdown-toggle"
                        id="brBusOrdenBtn"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        title="Orden de búsqueda histórica">
                        <i class="bi bi-sort-numeric-down" id="brBusOrdenIcon"></i>
                        <span id="brBusOrdenTexto" class="ms-1">Más recientes</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="brBusOrdenBtn">
                        <li>
                            <button type="button" class="dropdown-item br-bus-orden-opcion" data-orden="DESC">
                                <i class="bi bi-sort-numeric-down me-1"></i>Más recientes
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item br-bus-orden-opcion" data-orden="ASC">
                                <i class="bi bi-sort-numeric-up me-1"></i>Más antiguos
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="apm-busqueda-title">Filtro histórico de guardias y turnos</div>
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-0" for="brBusDesde">Desde</label>
                        <input type="date" class="form-control form-control-sm" id="brBusDesde">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-0" for="brBusHasta">Hasta</label>
                        <input type="date" class="form-control form-control-sm" id="brBusHasta">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label small mb-0" for="brBusQ">Guardia (nombre o cédula)</label>
                        <input type="text" class="form-control form-control-sm" id="brBusQ" placeholder="Opcional" maxlength="80" autocomplete="off">
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="button" class="btn btn-sm apm-btn-institucional w-100" id="brBtnBuscar">Buscar</button>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" class="btn btn-sm apm-btn-institucional" id="brBtnExportBusquedaPdf">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Exportar PDF
                    </button>
                    <button type="button" class="btn btn-sm apm-btn-institucional" id="brBtnExportBusquedaExcel">
                        <i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0 apm-tabla-rondas apm-tabla-busqueda" id="tablaBusqueda">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha / hora</th>
                                <th>Guardia</th>
                                <th>Turno</th>
                                <th>Actividad</th>
                                <th>Nivel</th>
                            </tr>
                        </thead>
                        <tbody id="tablaBusquedaBody">
                            <tr><td colspan="5" class="text-muted text-center py-2 small">Use el formulario para buscar.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="public/librerias/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="public/librerias/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script src="<?php echo htmlspecialchars($url_js_xlsx_bundle); ?>"></script>
<script src="<?php echo htmlspecialchars($GLOBALS['url_toast_js'] ?? ''); ?>"></script>
<script src="<?php echo htmlspecialchars($url_js_bitacora_rondas); ?>"></script>

<style id="fix-rondas-dark-card">
/* =========================================================
   FIX DIRECTO BITÁCORA DE RONDAS - MODO OSCURO
   Corrige la caja blanca de Información del turno
========================================================= */

html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact {
    background-color: #172033 !important;
    background-image: none !important;
    color: #e5e7eb !important;
    border-color: #334155 !important;
}

html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact .row,
html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact [class*="col-"],
html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact [class*="g-"],
html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact [class*="mb-"],
html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact div:not(.input-group),
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact .row,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact [class*="col-"],
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact [class*="g-"],
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact [class*="mb-"],
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact div:not(.input-group) {
    background-color: transparent !important;
    background-image: none !important;
}

html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact input,
html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact select,
html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact textarea,
html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact .form-control,
html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact .form-select,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact input,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact select,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact textarea,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact .form-control,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact .form-select {
    background-color: #0f172a !important;
    background-image: none !important;
    color: #f8fafc !important;
    border-color: #475569 !important;
}

html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact input[readonly],
html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact input:disabled,
html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact select:disabled,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact input[readonly],
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact input:disabled,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact select:disabled {
    background-color: #111827 !important;
    color: #cbd5e1 !important;
    border-color: #334155 !important;
    opacity: 1 !important;
}

html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact small,
html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact .text-muted,
html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact .form-text,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact small,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact .text-muted,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact .form-text {
    color: #cbd5e1 !important;
}

html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact .btn,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact .btn {
    background-color: #111827 !important;
    color: #e5e7eb !important;
    border-color: #475569 !important;
}

html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact input[type="date"]::-webkit-calendar-picker-indicator,
html[data-theme="dark"] body.apm-rondas-page .apm-br-info-compact input[type="time"]::-webkit-calendar-picker-indicator,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact input[type="date"]::-webkit-calendar-picker-indicator,
body.portal-dark-mode.apm-rondas-page .apm-br-info-compact input[type="time"]::-webkit-calendar-picker-indicator {
    filter: invert(1) brightness(1.8);
}
</style>
