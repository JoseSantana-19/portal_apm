<?php
/**
 * Vista: Maestro de Motivos / Novedades CCTV.
 * Variables disponibles: $pageTitle
 * Extraída de bit_motivos_camaras.php (contenido puro, sin head/navbar/sidebar:
 * eso ya lo pone views/layouts/main.php).
 */
$url_js_bit_motivos = 'public/js/portuaria/bit_motivos_camaras.js?v=' . (
    is_file(ROOT_PATH . '/public/js/portuaria/bit_motivos_camaras.js')
        ? (string) filemtime(ROOT_PATH . '/public/js/portuaria/bit_motivos_camaras.js')
        : '1'
);
?>
<style>
    body.apm-bit-motivos-page,
    body.apm-bit-motivos-page .apm-main {
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

    .apm-code-badge {
        font-family: Consolas, Monaco, monospace;
        letter-spacing: .02rem;
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

<div id="bitMotAlert" class="alert d-none apm-no-print" role="alert"></div>

<div class="row g-3 align-items-start">
    <div class="col-12 col-xl-4">
        <div class="card apm-card-pro mb-3">
            <div class="card-header bg-white border-bottom">
                <strong class="text-primary">
                    <i class="bi bi-plus-circle me-1"></i>
                    Registro de motivo
                </strong>
                <p class="small text-muted mb-0">El código del motivo se genera desde acc_secuenciales.</p>
            </div>
            <div class="card-body">
                <form id="formBitMotivo" autocomplete="off">
                    <input type="hidden" id="bit_id_motivo_camara" name="id_motivo_camara" value="">

                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1" for="bit_codigo_motivo">Código</label>
                            <input type="text" class="form-control" id="bit_codigo_motivo" placeholder="Automático" readonly>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1" for="bit_sec_motivo">Secuencial</label>
                            <input type="text" class="form-control" id="bit_sec_motivo" placeholder="Automático" readonly>
                        </div>

                        <div class="col-12">
                            <label class="form-label small mb-1" for="bit_descripcion">Descripción del motivo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="bit_descripcion" name="descripcion" maxlength="180" required placeholder="Ej: Sin señal, imagen borrosa, problema de grabación...">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1" for="bit_nivel_sugerido">Nivel sugerido</label>
                            <select class="form-select" id="bit_nivel_sugerido" name="nivel_sugerido">
                                <option value="Normal">Normal</option>
                                <option value="Medio" selected>Medio</option>
                                <option value="Crítico">Crítico</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-1" for="bit_requiere_observacion">Requiere observación</label>
                            <select class="form-select" id="bit_requiere_observacion" name="requiere_observacion">
                                <option value="1" selected>Sí</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-info small mt-3 mb-3 py-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Estos motivos sirven para clasificar novedades en la bitácora de cámaras CCTV.
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary" id="btnBitMotLimpiar">
                            <i class="bi bi-eraser me-1"></i>Limpiar
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnBitMotGuardar">
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
                            Lista general de motivos
                        </strong>
                        <p class="small text-muted mb-0">Permite búsqueda individual, edición, activación/desactivación y reporte.</p>
                    </div>
                    <span class="badge text-bg-secondary" id="bitMotTotalBadge">0 registro(s)</span>
                </div>
            </div>
            <div class="card-body border-bottom apm-no-print">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-lg-4">
                        <label class="form-label small mb-1" for="bitMotFiltroBuscar">Buscar</label>
                        <input type="search" id="bitMotFiltroBuscar" class="form-control" placeholder="Código o descripción...">
                    </div>
                    <div class="col-12 col-sm-6 col-lg-2">
                        <label class="form-label small mb-1" for="bitMotFiltroNivel">Nivel</label>
                        <select id="bitMotFiltroNivel" class="form-select">
                            <option value="">Todos</option>
                            <option value="Normal">Normal</option>
                            <option value="Medio">Medio</option>
                            <option value="Crítico">Crítico</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-2">
                        <label class="form-label small mb-1" for="bitMotFiltroEstado">Estado</label>
                        <select id="bitMotFiltroEstado" class="form-select">
                            <option value="1">Activos</option>
                            <option value="0">Inactivos</option>
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                            <button type="button" class="btn btn-outline-primary" id="btnBitMotBuscar">
                                <i class="bi bi-search me-1"></i>Buscar
                            </button>
                            <button type="button" class="btn btn-outline-success" id="btnBitMotExcel">
                                <i class="bi bi-file-earmark-excel me-1"></i>Excel
                            </button>
                            <button type="button" class="btn btn-outline-dark" id="btnBitMotReporte">
                                <i class="bi bi-printer me-1"></i>Reporte
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 apm-table-maestro" id="tablaBitMotivos">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Nivel sugerido</th>
                            <th>Requiere observación</th>
                            <th>Estado</th>
                            <th class="text-end apm-no-print">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyBitMotivos">
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Cargando motivos...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo htmlspecialchars($GLOBALS['url_toast_js'] ?? ''); ?>"></script>
<script src="<?php echo htmlspecialchars($url_js_bit_motivos); ?>"></script>
