<?php
/**
 * Vista: Modal de detalle de registro de bitácora de rondas (fragmento AJAX, sin layout).
 * Reemplaza el bloque modal_only=1 de bit_consulta.php (origen, nunca portado a este módulo).
 * Variables: $row (array|null), $desdeDashboard (bool)
 */
if ($row === null) {
    echo '<div class="alert alert-warning m-3">No se encontró el registro de bitácora.</div>';
    return;
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
                    <a href="dashboard-jefe" class="btn btn-secondary">Volver al Dashboard</a>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
