<?php
/**
 * Vista: Bitácora operativa de cámaras CCTV.
 * Variables disponibles: $pageTitle
 */
$url_js_bitacora_camaras = 'public/js/portuaria/bit_camaras.js?v=' . (
    is_file(ROOT_PATH . '/public/js/portuaria/bit_camaras.js')
        ? (string) filemtime(ROOT_PATH . '/public/js/portuaria/bit_camaras.js')
        : '1'
);
?>
<style>
    body.apm-cctv-page { background-color: #F4F7FB; }
    body.apm-cctv-page .apm-main { background-color: #F4F7FB; padding-top: .35rem !important; }
    .apm-card-pro { border: 0; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); margin-bottom: .75rem; }
    .apm-card-pro .card-header { padding: .45rem .75rem !important; }
    .apm-card-pro .card-body { padding: .65rem .85rem !important; }
    .apm-card-pro .form-control, .apm-card-pro .form-select { border-radius: .6rem; min-height: 36px; padding-top: .35rem; padding-bottom: .35rem; }
    .apm-card-pro textarea.form-control:focus, .apm-card-pro .form-control:focus, .apm-card-pro .form-select:focus { border-color: #86b7fe; box-shadow: 0 0 0 .2rem rgba(13,110,253,.15); }
    .apm-cctv-info .form-label { font-size: 0.72rem; margin-bottom: .18rem; color: #6c757d; letter-spacing: 0.02em; }
    .apm-btn-institucional { background-color: #0b5ed7; border-color: #0a58ca; color: #fff; }
    .apm-btn-institucional:hover, .apm-btn-institucional:focus { background-color: #0a58ca; border-color: #084298; color: #fff; }
    .apm-tabla-cctv { border-collapse: separate; border-spacing: 0 5px; }
    .apm-tabla-cctv thead th { background: #5f6f82; font-size: 0.68rem; text-transform: uppercase; letter-spacing: 1px; white-space: nowrap; color: #f1f3f5; border: 0; border-bottom: 1px solid #d1d8e0; padding: .45rem .55rem; }
    .apm-tabla-cctv td { font-size: 0.82rem; vertical-align: middle; padding: .45rem .55rem; border: 0; border-top: 1px solid #eef2f6; border-bottom: 1px solid #e2e8ef; background: #fff; }
    .apm-tabla-cctv tbody tr:nth-child(even) td { background: #f8fafc; }
    .apm-tabla-cctv tbody tr td:first-child { border-top-left-radius: .45rem; border-bottom-left-radius: .45rem; }
    .apm-tabla-cctv tbody tr td:last-child { border-top-right-radius: .45rem; border-bottom-right-radius: .45rem; }
    .apm-cctv-textarea-actividad { min-height: 68px; }
    .apm-cctv-actividad-wrap { position: relative; z-index: 20; overflow: visible; }
    .apm-cctv-actividad-sug { position: absolute; left: 0; right: 0; top: 100%; margin-top: 2px; z-index: 1060; max-height: 240px; overflow-y: auto; display: none; box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.12); border-radius: 0.375rem; background: #ffffff; }
    .apm-cctv-actividad-sug.apm-open { display: block; }
    .apm-cctv-actividad-sug .list-group-item { cursor: pointer; font-size: 0.8125rem; padding: 0.4rem 0.65rem; border-color: #dee2e6; }
    .apm-cctv-actividad-sug .list-group-item:hover { background: #f8f9fa; }
    .apm-cctv-camara-selector .select2-container--bootstrap-5 .select2-selection { min-height: 43px; border-radius: .6rem; border-color: #ced4da; display: flex; align-items: center; }
    .apm-cctv-camara-selector .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { color: #212529; line-height: 1.35; padding-left: .15rem; padding-right: 1.8rem; }
    .apm-cctv-inventario-box { background: #f8fafc; border: 1px solid #e4e9f0; border-radius: .75rem; padding: .5rem; }
    .apm-cctv-inventario-box .form-control { min-height: 32px; padding-top: .25rem; padding-bottom: .25rem; font-size: .85rem; }
    .apm-cctv-inventario-box .form-label { font-size: .7rem; color: #6c757d; margin-bottom: .2rem; }
    @media print {
        .apm-sidebar, .navbar, .apm-no-print, .btn, .card-header .dropdown { display: none !important; }
        .apm-main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        body { background: #fff !important; }
        .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
        .apm-tabla-cctv thead th { background: #e9ecef !important; color: #000 !important; }
    }
</style>

<div class="container-fluid mt-2 mb-3 px-2 px-md-3 apm-cctv-compact-container">
    <div class="row g-3 align-items-start">
        <div class="col-12">
            <div class="card apm-card-pro apm-cctv-info shadow-sm apm-cctv-info-horizontal">
                <div class="card-body py-2">
                    <input type="hidden" id="bcUsuario">
                    <input type="hidden" id="bcFechaServidor">
                    <input type="hidden" id="bcHoraServidor">
                    <input type="hidden" id="bcConsolista" value="Usuario Seguridad Integral">

                    <div class="row g-2 align-items-end">
                        <div class="col-12 col-lg-2 apm-cctv-info-titulo">
                            <span class="fw-semibold text-primary small d-block">
                                <i class="bi bi-calendar-check me-1"></i>Información del reporte
                            </span>
                            <small class="text-muted">Turno y fecha operativa.</small>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label class="form-label small mb-1" for="bcFecha">Fecha del reporte</label>
                            <input type="date" class="form-control form-control-sm" id="bcFecha">
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label class="form-label small mb-1" for="bcSecuencia">Secuencia</label>
                            <input type="text" class="form-control form-control-sm" id="bcSecuencia" maxlength="100" placeholder="Ej: JUNIO">
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label class="form-label small mb-1" for="bcTurno">Turno</label>
                            <select class="form-select form-select-sm fw-semibold text-primary" id="bcTurno">
                                <option value="Mañana">Mañana (07:00 - 15:00)</option>
                                <option value="Tarde">Tarde (15:00 - 23:00)</option>
                                <option value="Noche">Noche (23:00 - 07:00)</option>
                            </select>
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label small mb-1" for="bcHoraInicio">Hora inicio</label>
                            <input type="time" class="form-control form-control-sm" id="bcHoraInicio" step="60">
                        </div>
                        <div class="col-6 col-lg-2">
                            <label class="form-label small mb-1" for="bcHoraFin">Hora fin</label>
                            <input type="time" class="form-control form-control-sm" id="bcHoraFin" step="60">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card apm-card-pro apm-card-nuevo-reg shadow-sm">
                <div class="card-header bg-white border-bottom py-1">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span class="fw-semibold text-primary small">
                            <i class="bi bi-plus-circle me-1"></i>Nuevo registro CCTV
                        </span>
                        <small class="text-muted">Actividades del turno o novedades específicas de cámaras.</small>
                    </div>
                </div>
                <div class="card-body">
                    <input type="hidden" id="bcId">
                    <input type="hidden" id="bcIdCamara">
                    <input type="hidden" id="bcRolResponsable" value="Consolista">

                    <div class="row g-2">
                        <div class="col-12 col-sm-6 col-lg-3 bc-control-principal" id="bcTipoRegistroWrap">
                            <label class="form-label" for="bcTipoRegistro">Tipo de registro</label>
                            <select class="form-select fw-semibold" id="bcTipoRegistro">
                                <option value="102">Actividad diaria</option>
                                <option value="103">Novedad de cámara</option>
                            </select>
                            <small class="text-muted">Use actividad diaria cuando no exista una falla específica de cámara.</small>
                        </div>

                        <div class="col-12 bc-bloque-camara">
                            <label class="form-label" for="bcCamaraSelect">Cámara registrada en inventario</label>
                            <div class="apm-cctv-camara-selector">
                                <select class="form-select" id="bcCamaraSelect" data-placeholder="Buscar por IP, puerta, código, marca, modelo o ubicación..." style="width: 100%;">
                                    <option value=""></option>
                                </select>
                            </div>
                            <div class="form-text apm-cctv-help">
                                Escriba una IP, puerta, código, marca, modelo o ubicación. Al seleccionar una cámara se cargan automáticamente sus datos.
                            </div>
                        </div>

                        <div class="col-12 bc-bloque-camara">
                            <div class="apm-cctv-inventario-box">
                                <div class="row g-2">
                                    <div class="col-12 col-md-3"><label class="form-label">IP / Equipo</label><input type="text" class="form-control bg-light" id="bcCamaraIp" readonly></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Ubicación</label><input type="text" class="form-control bg-light" id="bcUbicacion" readonly></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Detalle / Sitio</label><input type="text" class="form-control bg-light" id="bcSitio" readonly></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Grabador</label><input type="text" class="form-control bg-light" id="bcInvGrabador" readonly></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Código antiguo</label><input type="text" class="form-control bg-light" id="bcInvCodOld" readonly></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Código</label><input type="text" class="form-control bg-light" id="bcInvCodigo" readonly></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Tipo</label><input type="text" class="form-control bg-light" id="bcInvTipo" readonly></div>
                                    <div class="col-12 col-md-3"><label class="form-label">Marca / Modelo</label><input type="text" class="form-control bg-light" id="bcInvMarca" readonly></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-3 bc-bloque-camara">
                            <label class="form-label" for="bcEstadoCamara">Estado operativo</label>
                            <select class="form-select fw-semibold" id="bcEstadoCamara">
                                <option value="101">OPERATIVA</option>
                                <option value="100">NO OPERATIVA</option>
                            </select>
                        </div>

                        <div class="col-12 d-none" id="bcPanelAlerta">
                            <div class="alert alert-warning small mb-0 py-2">
                                <i class="bi bi-exclamation-triangle me-1"></i>La novedad de cámara requiere seleccionar el motivo y registrar novedades.
                            </div>
                        </div>

                        <div class="col-12 col-md-4 d-none bc-campo-alerta bc-campo-motivo-camara">
                            <label class="form-label" for="bcMotivoCamara">Motivo de novedad</label>
                            <select class="form-select fw-semibold" id="bcMotivoCamara">
                                <option value="">Seleccione un motivo...</option>
                            </select>
                        </div>

                        <div class="col-12 bc-campo-novedad" id="bcNovedadWrap">
                            <label class="form-label d-flex justify-content-between align-items-center gap-2" for="bcNovedad">
                                <span id="bcNovedadLabel">Actividad</span>
                                <small class="text-muted fw-normal" id="bcSugerenciasActividadInfo">Sugerencias flotantes al escribir</small>
                            </label>
                            <div class="apm-cctv-actividad-wrap" id="bcActividadWrap">
                                <textarea class="form-control apm-cctv-textarea" id="bcNovedad" rows="3" maxlength="8000" placeholder="Describa la actividad u observación..." autocomplete="off"></textarea>
                                <div class="list-group apm-cctv-actividad-sug border" id="bcActividadSug" aria-hidden="true"></div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3 bc-control-principal" id="bcNivelAlertaWrap">
                            <label class="form-label" for="bcNivelAlerta">Nivel de alerta</label>
                            <select class="form-select fw-semibold" id="bcNivelAlerta">
                                <option value="104">Normal</option>
                                <option value="105">Medio</option>
                                <option value="106">Crítico</option>
                            </select>
                        </div>

                        <div class="col-12 col-sm-6 col-lg-3 bc-control-principal" id="bcHoraRegistroWrap">
                            <label class="form-label" for="bcHoraRegistro">Hora del registro</label>
                            <input type="time" class="form-control" id="bcHoraRegistro" step="60">
                        </div>

                        <input type="hidden" id="bcObservaciones" value="">
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2 mt-2 apm-no-print">
                        <button type="button" class="btn btn-outline-secondary d-none" id="bcBtnCancelar"><i class="bi bi-x-circle me-1"></i>Cancelar edición</button>
                        <button type="button" class="btn apm-btn-institucional" id="bcBtnGuardar"><i class="bi bi-save me-1"></i>Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card apm-card-pro shadow-sm">
        <div class="card-header bg-white border-bottom py-2">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <span class="fw-semibold text-primary small"><i class="bi bi-search me-1"></i>Consulta de registros</span>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center apm-cctv-resumen-badges">
                    <span class="badge text-bg-info text-dark" id="bcTotalActividades">0 actividad(es)</span>
                    <span class="badge text-bg-warning text-dark" id="bcTotalNovedades">0 novedad(es)</span>
                    <span class="badge text-bg-secondary" id="bcContador">0 registro(s)</span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-2 align-items-end apm-no-print">
                <div class="col-12 col-md-2"><label class="form-label small mb-1">Fecha</label><input type="date" class="form-control form-control-sm" id="bcFiltroFecha"></div>
                <div class="col-12 col-md-2">
                    <label class="form-label small mb-1">Turno</label>
                    <select class="form-select form-select-sm" id="bcFiltroTurno">
                        <option value="">Todos</option><option value="Mañana">Mañana</option><option value="Tarde">Tarde</option><option value="Noche">Noche</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small mb-1">Tipo</label>
                    <select class="form-select form-select-sm" id="bcFiltroTipo">
                        <option value="">Todos</option><option value="102">Actividad diaria</option><option value="103">Novedad de cámara</option>
                    </select>
                </div>
                <div class="col-12 col-md-3"><label class="form-label small mb-1">Buscar</label><input type="text" class="form-control form-control-sm" id="bcFiltroQ" placeholder="Filtrar coincidencia..."></div>
                <div class="col-12 col-md-1">
                    <label class="form-label small mb-1">Orden</label>
                    <select class="form-select form-select-sm" id="bcFiltroOrden"><option value="ASC">Más antiguos</option><option value="DESC">Más recientes</option></select>
                </div>
                <div class="col-12 col-md-2">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm apm-btn-institucional flex-fill" id="bcBtnBuscar"><i class="bi bi-search me-1"></i>Buscar</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" id="bcBtnRefrescar"><i class="bi bi-arrow-clockwise me-1"></i>Refrescar</button>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 my-3 apm-no-print">
                <button type="button" class="btn btn-sm btn-outline-success" id="bcBtnExcel"><i class="bi bi-file-earmark-excel me-1"></i>Exportar Excel</button>
                <button type="button" class="btn btn-sm btn-outline-danger" id="bcBtnPdf"><i class="bi bi-file-earmark-pdf me-1"></i>Imprimir / PDF</button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 apm-tabla-cctv" id="bcTabla">
                    <thead>
                        <tr>
                            <th>Cód.</th><th>Tipo registro</th><th>Fecha</th><th>Hora</th><th>Motivo</th><th>Actividad / Novedad</th><th>Cámara</th><th>Ubicación</th><th>Estado</th><th>Nivel</th><th class="text-center apm-no-print">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="bcTablaBody">
                        <tr><td colspan="11" class="text-muted text-center py-4">Cargando registros...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo htmlspecialchars($url_jquery_js ?? ''); ?>"></script>
<script src="<?php echo htmlspecialchars($url_select2_js ?? ''); ?>"></script>
<script src="<?php echo htmlspecialchars($url_toast_js ?? ''); ?>"></script>
<script src="<?php echo htmlspecialchars($url_js_bitacora_camaras); ?>"></script>