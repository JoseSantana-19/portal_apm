<?php
/**
 * Vista: Maestro de Cámaras CCTV (Inventario).
 * Variables disponibles: $pageTitle
 * Extraída de bit_inv_camaras.php (contenido puro, sin head/navbar/sidebar:
 * eso ya lo pone views/layouts/main.php).
 */
$url_js_inv_camaras = 'public/js/portuaria/inv_camaras.js?v=' . (
    is_file(ROOT_PATH . '/public/js/portuaria/inv_camaras.js')
        ? (string) filemtime(ROOT_PATH . '/public/js/portuaria/inv_camaras.js')
        : '1'
);
?>
<style>
    body.apm-inv-camaras-page,
    body.apm-inv-camaras-page .apm-main {
        background-color: #F4F7FB;
    }

    .apm-card-pro {
        border: 0;
        border-radius: .85rem;
        box-shadow: 0 0.125rem 0.35rem rgba(0, 0, 0, 0.075);
    }

    .apm-title-card {
        background: linear-gradient(135deg, #0b5ed7 0%, #084298 100%);
        color: #fff;
        border-radius: .9rem;
        padding: 1rem 1.25rem;
    }

    .apm-title-card p {
        color: rgba(255,255,255,.82);
    }

    .apm-table-maestro thead th {
        font-size: .73rem;
        text-transform: uppercase;
        letter-spacing: .05rem;
        white-space: nowrap;
    }

    .apm-table-maestro td {
        font-size: .86rem;
        vertical-align: middle;
    }

    .apm-table-maestro .btn {
        white-space: nowrap;
    }

    .apm-code-badge {
        font-family: Consolas, Monaco, monospace;
        letter-spacing: .02rem;
    }

    .apm-form-help {
        font-size: .78rem;
    }

    @media print {
        .apm-sidebar,
        .navbar,
        .apm-no-print,
        .btn,
        form,
        .card-header .badge {
            display: none !important;
        }

        .apm-main {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        body {
            background: #fff !important;
        }
    }
</style>

<div id="invCamAlert" class="alert d-none apm-no-print" role="alert"></div>

<div class="row g-3 align-items-start">
    <div class="col-12 col-xl-4">
        <div class="card apm-card-pro mb-3">
            <div class="card-header bg-white border-bottom">
                <strong class="text-primary">
                    <i class="bi bi-plus-circle me-1"></i>
                    Registro de cámara
                </strong>
            </div>
            <div class="card-body">
                <form id="formInvCamara" autocomplete="off">
                    <input type="hidden" id="inv_id_camara" name="id_camara" value="">

                    <input type="hidden" id="inv_codigo_secuencial" value="">
                    <input type="hidden" id="inv_sec_camara" value="">

                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1" for="inv_cod_old">Código antiguo</label>
                            <input type="text" class="form-control" id="inv_cod_old" name="cod_old" maxlength="30" placeholder="Ej: 006088">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1" for="inv_codigo">Código actual</label>
                            <input type="text" class="form-control" id="inv_codigo" name="codigo" maxlength="30" placeholder="Ej: 11827201">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1" for="inv_ip">IP / Equipo</label>
                            <input type="text" class="form-control" id="inv_ip" name="ip" maxlength="50" placeholder="Ej: 20.20.20.17">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1" for="inv_mac">MAC</label>
                            <input type="text" class="form-control" id="inv_mac" name="mac" maxlength="80" placeholder="Opcional">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1" for="inv_tipo">Tipo</label>
                            <input type="text" class="form-control" id="inv_tipo" name="tipo" maxlength="80" placeholder="Bala, Domo, PTZ...">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1" for="inv_tecnologia">Tecnología</label>
                            <select class="form-select" id="inv_tecnologia" name="tecnologia">
                                <option value="">Seleccione...</option>
                                <option value="IP">IP</option>
                                <option value="ANALOGA">ANÁLOGA</option>
                                <option value="HD-TVI">HD-TVI</option>
                                <option value="OTRA">OTRA</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1" for="inv_marca">Marca</label>
                            <select class="form-select" id="inv_marca" name="marca">
                                <option value="">Seleccione...</option>
                                <option value="Hikvision">Hikvision</option>
                                <option value="Dahua">Dahua</option>
                                <option value="Samsung">Samsung</option>
                                <option value="SAMSUNG">SAMSUNG</option>
                                <option value="Hanwha Techwin">Hanwha Techwin</option>
                                <option value="Uniview">Uniview</option>
                                <option value="Axis">Axis</option>
                                <option value="Bosch">Bosch</option>
                                <option value="Honeywell">Honeywell</option>
                                <option value="Tiandy">Tiandy</option>
                                <option value="Vivotek">Vivotek</option>
                                <option value="Avigilon">Avigilon</option>
                                <option value="CP Plus">CP Plus</option>
                                <option value="Genérica">Genérica</option>
                                <option value="OTRA">OTRA</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1" for="inv_modelo">Modelo</label>
                            <input type="text" class="form-control" id="inv_modelo" name="modelo" maxlength="120" placeholder="Modelo del equipo">
                        </div>

                        <div class="col-12">
                            <label class="form-label small mb-1" for="inv_serie">Serie</label>
                            <input type="text" class="form-control" id="inv_serie" name="serie" maxlength="120" placeholder="Número de serie del equipo">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1" for="inv_ubicacion">Ubicación <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="inv_ubicacion" name="ubicacion" maxlength="150" required placeholder="Ej: TPyC">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1" for="inv_grabador">Grabador</label>
                            <select class="form-select" id="inv_grabador" name="grabador">
                                <option value="">Seleccione...</option>
                                <option value="NVR">NVR</option>
                                <option value="DVR">DVR</option>
                                <option value="XVR">XVR</option>
                                <option value="HVR">HVR</option>
                                <option value="DVR:Adm">DVR:Adm</option>
                                <option value="NVR Principal">NVR Principal</option>
                                <option value="NVR CCTV">NVR CCTV</option>
                                <option value="Servidor de grabación">Servidor de grabación</option>
                                <option value="No aplica">No aplica</option>
                                <option value="OTRO">OTRO</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label small mb-1" for="inv_detalle">Detalle / Sitio <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="inv_detalle" name="detalle" maxlength="200" required placeholder="Ej: Entrada peatonal">
                        </div>

                        <div class="col-12">
                            <label class="form-label small mb-1" for="inv_caracteristica">Características</label>
                            <textarea class="form-control" id="inv_caracteristica" name="caracteristica" maxlength="250" rows="2" placeholder="Resolución, lente, IR, soporte, observaciones técnicas..."></textarea>
                        </div>
                    </div>

                    <div class="alert alert-info small mt-3 mb-3 py-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Para guardar se requiere ubicación y detalle. Además debe existir al menos IP, código actual o código antiguo.
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" id="btnInvLimpiar">
                            <i class="bi bi-eraser me-1"></i>Limpiar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnInvGuardar">
                            <i class="bi bi-save me-1"></i>Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <div class="card apm-card-pro">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <strong class="text-primary">
                            <i class="bi bi-search me-1"></i>
                            Lista general de cámaras
                        </strong>
                        <p class="small text-muted mb-0">Permite búsqueda individual, edición, activación/desactivación y reporte.</p>
                    </div>
                    <span class="badge text-bg-secondary" id="invTotalBadge">0 registro(s)</span>
                </div>
            </div>
            <div class="card-body border-bottom apm-no-print">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-lg-6">
                        <label class="form-label small mb-1" for="invFiltroBuscar">Buscar</label>
                        <input type="search" id="invFiltroBuscar" class="form-control" placeholder="IP, código, ubicación, marca, modelo, grabador...">
                    </div>
                    <div class="col-12 col-sm-6 col-lg-2">
                        <label class="form-label small mb-1" for="invFiltroEstado">Estado</label>
                        <select id="invFiltroEstado" class="form-select">
                            <option value="1">Activas</option>
                            <option value="0">Inactivas</option>
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                            <button type="button" class="btn btn-outline-primary" id="btnInvBuscar">
                                <i class="bi bi-search me-1"></i>Buscar
                            </button>
                            <button type="button" class="btn btn-outline-success" id="btnInvExcel">
                                <i class="bi bi-file-earmark-excel me-1"></i>Excel
                            </button>
                            <button type="button" class="btn btn-outline-dark" id="btnInvReporte">
                                <i class="bi bi-printer me-1"></i>Reporte
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 apm-table-maestro" id="tablaInvCamaras">
                    <thead class="table-light">
                        <tr>
                            <th>Cód. sec.</th>
                            <th>IP / Equipo</th>
                            <th>Ubicación</th>
                            <th>Detalle / Sitio</th>
                            <th>Tipo</th>
                            <th>Marca / Modelo</th>
                            <th>Grabador</th>
                            <th>Estado</th>
                            <th class="text-end apm-no-print">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyInvCamaras">
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Cargando cámaras...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo htmlspecialchars($GLOBALS['url_toast_js'] ?? ''); ?>"></script>
<script src="<?php echo htmlspecialchars($url_js_inv_camaras); ?>"></script>
