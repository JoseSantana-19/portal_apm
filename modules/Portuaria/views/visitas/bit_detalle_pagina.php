<?php
/**
 * Vista: Página completa de detalle de visita.
 * Se usa cuando se accede sin modal_only=1.
 * Variable: $visita (array|false)
 */
?>
<div class="container my-4">
    <?php if ($visita === false): ?>
        <div class="alert alert-warning">No se encontró la visita solicitada.</div>
        <a href="visitas" class="btn btn-primary">Ir al listado</a>
    <?php else: ?>
        <p class="text-muted small mb-2">Vista detalle de visita #<?= (int)$visita['id_visita'] ?></p>
        <a href="visitas" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Volver al listado</a>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Visitante</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars(trim($visita['nombres'] . ' ' . $visita['apellidos'])) ?></dd>
                    <dt class="col-sm-3">Identificación</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars((string)$visita['nidentificacion']) ?></dd>
                    <dt class="col-sm-3">Empresa</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars((string)($visita['empresa'] ?? 'Personal')) ?></dd>
                    <dt class="col-sm-3">Funcionario</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars((string)$visita['funcionario']) ?></dd>
                    <dt class="col-sm-3">Destino</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars((string)$visita['destino']) ?></dd>
                    <dt class="col-sm-3">Motivo</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars((string)$visita['motivo']) ?></dd>
                    <dt class="col-sm-3">Fecha</dt>
                    <dd class="col-sm-9"><?= $visita['fecha_visita'] instanceof DateTimeInterface ? htmlspecialchars($visita['fecha_visita']->format('d/m/Y')) : '' ?></dd>
                </dl>
            </div>
        </div>
    <?php endif; ?>
</div>
