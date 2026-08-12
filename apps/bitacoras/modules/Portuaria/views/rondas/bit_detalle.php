<?php
/**
 * Vista: Consulta bitácora de rondas (página completa).
 * Reemplaza bit_consulta.php del origen (contenido puro, sin head/navbar/sidebar:
 * eso ya lo pone views/layouts/main.php).
 * Variables: $row (array|null), $desdeDashboard (bool), $abrirModal (bool)
 */
?>
<div class="container my-4">
    <?php if ($row === null): ?>
        <div class="alert alert-warning">No se encontró el registro de bitácora.</div>
        <a href="rondas" class="btn btn-primary">Ir a bitácora</a>
    <?php else: ?>
        <p class="text-muted small mb-2">Detalle #<?php echo (int) $row['id_detalle']; ?></p>
        <a href="rondas" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Volver a bitácora</a>
    <?php endif; ?>
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
                    <a href="dashboard-jefe" class="btn btn-secondary">Volver al Dashboard</a>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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
