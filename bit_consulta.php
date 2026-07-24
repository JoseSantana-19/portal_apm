<?php
/**
 * Detalle de registro de bitácora (modal) — enlazado desde panel jefe.
 */
require_once __DIR__ . '/includes/bit_auth_guard.php';
require_once __DIR__ . '/includes/bit_auth_permissions.php';
require_once __DIR__ . '/conexion/conexion.php';

if (!apm_can_acceder_dashboard_jefe() && !apm_can_acceder_bitacora_rondas()) {
    header('Location: dashboard?msg=acceso_denegado');
    exit;
}

include_once __DIR__ . '/rutas/config_rutas.php';

$idDetalle = isset($_GET['id_detalle']) ? (int) $_GET['id_detalle'] : 0;
$action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
$desdeDashboard = isset($_GET['from']) && $_GET['from'] === 'dashboard';
$modalOnly = isset($_GET['modal_only']) && $_GET['modal_only'] === '1';

$row = null;
if ($idDetalle > 0) {
    $sql = 'SELECT d.id_detalle, d.actividad, d.hora_registro, d.id_alerta, '
        . 'a.descripcion AS alerta_desc, a.color_hex, '
        . 'c.fecha, c.turno, c.id_ronda, u.nombres AS guardia '
        . 'FROM dbo.bit_rondas_detalles d '
        . 'INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = d.id_ronda '
        . 'INNER JOIN dbo.bit_usuarios_apm u ON u.id_usuario = c.id_usuario '
        . 'INNER JOIN dbo.bit_niveles_alerta a ON a.id_alerta = d.id_alerta '
        . 'WHERE d.id_detalle = ?';
    $st = sqlsrv_query($conn, $sql, [$idDetalle]);
    if ($st && ($r = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC))) {
        $row = $r;
    }
}

$abrirModal = ($action === 'view' && $row !== null);

if ($modalOnly) {
    if ($row === null) {
        echo '<div class="alert alert-warning m-3">No se encontró el registro de bitácora.</div>';
        exit;
    }
    ?>
    <div class="modal fade" id="modalBitacoraDetalle" tabindex="-1" aria-labelledby="modalBitacoraDetalleLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalBitacoraDetalleLabel">Registro de bitácora</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4">Guardia</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars((string) $row['guardia']); ?></dd>
                        <dt class="col-sm-4">Fecha operativa</dt>
                        <dd class="col-sm-8"><?php echo $row['fecha'] instanceof DateTimeInterface ? htmlspecialchars($row['fecha']->format('d/m/Y')) : ''; ?></dd>
                        <dt class="col-sm-4">Turno</dt>
                        <dd class="col-sm-8"><?php echo htmlspecialchars((string) $row['turno']); ?></dd>
                        <dt class="col-sm-4">Nivel</dt>
                        <dd class="col-sm-8">
                            <span class="badge" style="background:<?php echo htmlspecialchars((string) ($row['color_hex'] ?? '#6c757d')); ?>">
                                <?php echo htmlspecialchars((string) $row['alerta_desc']); ?>
                            </span>
                        </dd>
                        <dt class="col-sm-4">Hora registro</dt>
                        <dd class="col-sm-8"><?php echo $row['hora_registro'] instanceof DateTimeInterface ? htmlspecialchars($row['hora_registro']->format('d/m/Y H:i')) : ''; ?></dd>
                        <dt class="col-sm-4">Actividad</dt>
                        <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars((string) $row['actividad'])); ?></dd>
                    </dl>
                </div>
                <div class="modal-footer">
                    <a href="rondas" class="btn btn-primary">Abrir bitácora</a>
                    <?php if ($desdeDashboard): ?>
                        <a href="bit_dashboard_jefe.php" class="btn btn-secondary">Volver al Dashboard</a>
                    <?php else: ?>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Consulta bitácora | APM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_bootstrap_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_icons_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_variables_css); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($url_layout_css); ?>">
</head>
<body class="bg-light">
<?php include __DIR__ . '/views/layouts/bit_navbar.php'; ?>

<div class="apm-layout">
    <?php include __DIR__ . '/views/layouts/bit_sidebar.php'; ?>
    <main class="apm-main">
        <div class="container my-4">
            <?php if ($row === null): ?>
                <div class="alert alert-warning">No se encontró el registro de bitácora.</div>
                <a href="rondas" class="btn btn-primary">Ir a bitácora</a>
            <?php else: ?>
                <p class="text-muted small mb-2">Detalle #<?php echo (int) $row['id_detalle']; ?></p>
                <a href="rondas" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Volver a bitácora</a>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php if ($row !== null): ?>
<div class="modal fade" id="modalBitacoraDetalle" tabindex="-1" aria-labelledby="modalBitacoraDetalleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBitacoraDetalleLabel">Registro de bitácora</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">Guardia</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars((string) $row['guardia']); ?></dd>
                    <dt class="col-sm-4">Fecha operativa</dt>
                    <dd class="col-sm-8"><?php echo $row['fecha'] instanceof DateTimeInterface ? htmlspecialchars($row['fecha']->format('d/m/Y')) : ''; ?></dd>
                    <dt class="col-sm-4">Turno</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars((string) $row['turno']); ?></dd>
                    <dt class="col-sm-4">Nivel</dt>
                    <dd class="col-sm-8">
                        <span class="badge" style="background:<?php echo htmlspecialchars((string) ($row['color_hex'] ?? '#6c757d')); ?>">
                            <?php echo htmlspecialchars((string) $row['alerta_desc']); ?>
                        </span>
                    </dd>
                    <dt class="col-sm-4">Hora registro</dt>
                    <dd class="col-sm-8"><?php echo $row['hora_registro'] instanceof DateTimeInterface ? htmlspecialchars($row['hora_registro']->format('d/m/Y H:i')) : ''; ?></dd>
                    <dt class="col-sm-4">Actividad</dt>
                    <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars((string) $row['actividad'])); ?></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <a href="rondas" class="btn btn-primary">Abrir bitácora</a>
                <?php if ($desdeDashboard): ?>
                    <a href="bit_dashboard_jefe.php" class="btn btn-secondary">Volver al Dashboard</a>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="<?php echo htmlspecialchars($url_bootstrap_js); ?>"></script>
<script src="<?php echo htmlspecialchars($url_layout_sidebar_js); ?>"></script>
<?php if ($abrirModal): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('modalBitacoraDetalle');
    if (el && window.bootstrap && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(el).show();
    }
});
</script>
<?php endif; ?>
</body>
</html>
