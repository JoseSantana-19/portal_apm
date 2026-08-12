<?php
// bit_reporte_diario_supervisor.php - Bitácora / reporte operativo diario del supervisor
require_once __DIR__ . '/includes/bit_auth_guard.php';

// Hueco real cerrado (permisos_centrales Fase 3, 2026-08-11): antes esta
// página solo verificaba sesión activa, sin ningún chequeo de permiso.
require_once __DIR__ . '/modules/Portuaria/models/Auth.php';
if (!Auth::canAccederReporteSupervisor()) {
    http_response_code(403);
    echo '<h2 style="text-align:center;margin-top:60px;">Acceso denegado.</h2>';
    exit;
}

require_once __DIR__ . '/conexion/zona_horaria.php';
require_once __DIR__ . '/conexion/conexion.php';
include_once __DIR__ . '/rutas/config_rutas.php';
include_once __DIR__ . '/conexion/conexion_externa.php';
$hoy = date('Y-m-d');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte diario del supervisor | Autoridad Portuaria de Manta</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_bootstrap_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_icons_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_variables_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_layout_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_componentes_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_toast_css); ?>">
</head>
<body class="bg-light">

<?php include __DIR__ . '/modules/Portuaria/views/layouts/bit_navbar.php'; ?>

<div class="apm-layout">
    <?php include __DIR__ . '/modules/Portuaria/views/layouts/bit_sidebar.php'; ?>
    <main class="apm-main">
        <div class="container-fluid my-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                <div>
                    <!-- Supervisor/responsable: texto libre; «Guardar responsable» actualiza encabezado si el reporte del día ya existe. -->
                    <h1 class="h4 text-primary mb-1">Reporte diario del supervisor</h1>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-end">
                    <div>
                        <label class="form-label small mb-0 text-muted" for="filtroFecha">Fecha del reporte</label>
                        <input type="date" id="filtroFecha" class="form-control form-control-sm" value="<?php echo htmlspecialchars($hoy); ?>">
                    </div>
                    <div>
                        <label class="form-label small mb-0 text-muted" for="usuarioGenera">Supervisor / responsable</label>
                        <input type="text" id="usuarioGenera" class="form-control form-control-sm" maxlength="150" placeholder="Ej: Juan Pérez o EBS - Supervisor general" autocomplete="name" title="Texto libre: nombre, cargo o ambos">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnGuardarSupervisor" title="Actualiza «Generado por» del reporte del día seleccionado">
                        <i class="bi bi-person-check me-1"></i>Guardar responsable
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="btnCargarReporte">
                        <i class="bi bi-arrow-clockwise me-1"></i>Actualizar
                    </button>
                </div>
            </div>

            <div id="bloqueErrorApi" class="alert alert-danger d-none" role="alert"></div>

            <div class="card shadow-sm border-0 mb-4" id="cardEncabezado">
                <div class="card-body py-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <span class="text-muted small d-block">N.º reporte</span>
                            <span class="fs-5 fw-semibold text-primary" id="hdrNumero">—</span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">Fecha</span>
                            <span class="fw-semibold" id="hdrFecha">—</span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">Generado por</span>
                            <span class="fw-semibold" id="hdrUsuario">—</span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">Creado</span>
                            <span class="small" id="hdrCreado">—</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small">Total visitas (día)</div>
                            <div class="fs-3 fw-bold text-primary" id="resTotal">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small">Visitas activas (dentro)</div>
                            <div class="fs-3 fw-bold text-success" id="resActivas">0</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="text-muted small">Proveedores (Empresa)</div>
                            <div class="fs-3 fw-bold text-info" id="resProveedores">0</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-2 fw-semibold">Visitas por hora de ingreso</div>
                        <div class="card-body">
                            <canvas id="chartPorHora" height="220"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-2 fw-semibold">Por tipo de visitante</div>
                        <div class="card-body">
                            <canvas id="chartPorTipo" height="220"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-semibold">Novedades del día</span>
                </div>
                <div class="card-body border-bottom">
                    <form id="formNuevaNovedad" class="row g-2 align-items-end">
                        <div class="col-12 col-md-5">
                            <label class="form-label small mb-0" for="novaDescripcion">Descripción</label>
                            <textarea id="novaDescripcion" class="form-control" rows="2" maxlength="2000" placeholder="Registre la novedad u observación..." required></textarea>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-0" for="novaHora">Hora</label>
                            <input type="time" id="novaHora" class="form-control" required title="Hora de la anotación en el día del reporte">
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label small mb-0" for="novaEstado">Estado</label>
                            <select id="novaEstado" class="form-select">
                                <option value="Registrada">Registrada</option>
                                <option value="En seguimiento">En seguimiento</option>
                                <option value="Cerrada">Cerrada</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-plus-lg"></i> Agregar
                            </button>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaNovedades">
                        <thead class="table-light">
                            <tr>
                                <th>Hora</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th class="text-end" style="width:140px">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyNovedades">
                            <tr><td colspan="4" class="text-center text-muted py-4">Cargue el reporte para ver novedades.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="modalEditarNovedad" tabindex="-1" aria-labelledby="modalEditarNovedadLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarNovedadLabel">Editar novedad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editIdnovedad" value="">
                <p class="small text-muted mb-3" id="editFechaInfo">La fecha corresponde al día del reporte y no se modifica.</p>
                <div class="mb-3">
                    <label class="form-label" for="editHora">Hora</label>
                    <input type="time" id="editHora" class="form-control" required title="Solo puede cambiar la hora, no la fecha">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="editDescripcion">Descripción</label>
                    <textarea id="editDescripcion" class="form-control" rows="4" maxlength="2000" required></textarea>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="editEstado">Estado</label>
                    <select id="editEstado" class="form-select">
                        <option value="Registrada">Registrada</option>
                        <option value="En seguimiento">En seguimiento</option>
                        <option value="Cerrada">Cerrada</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarEdicionNovedad">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo htmlspecialchars($url_bootstrap_js); ?>"></script>
<script src="<?php echo htmlspecialchars($url_layout_sidebar_js); ?>"></script>
<script src="<?php echo htmlspecialchars($url_toast_js); ?>"></script>
<script src="<?php echo htmlspecialchars($url_chart_js); ?>"></script>
<script src="<?php echo htmlspecialchars($url_js_reporte); ?>"></script>

</body>
</html>
