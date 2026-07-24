<?php
/**
 * Vista: Modal de detalle de visita (fragmento AJAX, sin layout).
 * Reemplaza el bloque modal_only=1 de bit_consulta_visitas.php
 * Variable: $visita (array de detalle('id_visita' etc.))
 */
$desdeDashboard = (($_GET['from'] ?? '') === 'dashboard');
?>
<div class="modal fade" id="modalVisitaDetalle" tabindex="-1" aria-labelledby="modalVisitaDetalleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisitaDetalleLabel">Detalle de visita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">Visitante</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars(trim($visita['nombres'] . ' ' . $visita['apellidos'])) ?></dd>
                    <dt class="col-sm-4">Identificación</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)$visita['nidentificacion']) ?></dd>
                    <dt class="col-sm-4">Tipo</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)$visita['tipo_visitante']) ?></dd>
                    <dt class="col-sm-4">Empresa</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($visita['empresa'] ?? '')) ?></dd>
                    <dt class="col-sm-4">Funcionario</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)$visita['funcionario']) ?></dd>
                    <dt class="col-sm-4">Destino</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)$visita['destino']) ?></dd>
                    <dt class="col-sm-4">Motivo</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)$visita['motivo']) ?></dd>
                    <?php if (!empty($visita['detalle_motivo'])): ?>
                    <dt class="col-sm-4">Detalle motivo</dt>
                    <dd class="col-sm-8"><?= nl2br(htmlspecialchars((string)$visita['detalle_motivo'])) ?></dd>
                    <?php endif; ?>
                    <dt class="col-sm-4">Nivel</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($visita['nivel_desc'] ?? '—')) ?></dd>
                    <dt class="col-sm-4">Fecha</dt>
                    <dd class="col-sm-8"><?= $visita['fecha_visita'] instanceof DateTimeInterface ? htmlspecialchars($visita['fecha_visita']->format('d/m/Y')) : '' ?></dd>
                    <dt class="col-sm-4">Entrada / Salida</dt>
                    <dd class="col-sm-8">
                        <?php
                        $he = $visita['hora_entrada'] instanceof DateTimeInterface ? $visita['hora_entrada']->format('H:i') : '';
                        $hs = $visita['hora_salida'] instanceof DateTimeInterface ? $visita['hora_salida']->format('H:i') : 'Pendiente';
                        echo htmlspecialchars($he . ' / ' . $hs);
                        ?>
                    </dd>
                </dl>
            </div>
            <div class="modal-footer">
                <a href="visitas" class="btn btn-primary">Listado de visitas</a>
                <?php if ($desdeDashboard): ?>
                    <a href="bit_dashboard_jefe.php" class="btn btn-secondary">Volver al Dashboard</a>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
