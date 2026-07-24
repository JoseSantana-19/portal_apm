<?php $success = SessionHelper::getFlash('success'); ?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-5);">
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-arrows-rotate" style="color:var(--color-success);margin-right:var(--sp-2);"></i>
        Movimientos de Bienes
    </h2>
    <div style="display:flex;gap:var(--sp-2);">
        <a href="<?= APP_URL ?>/bienes" class="btn btn-outline" data-spa><i class="fa-solid fa-boxes-stacked"></i> Inventario</a>
        <a href="<?= APP_URL ?>/bienes/movimientos/nuevo" class="btn btn-primary" data-spa><i class="fa-solid fa-plus"></i> Nuevo</a>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Tipo</th><th>Bien</th><th>Código</th><th>Responsable</th><th>Fecha</th><th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($movimientos)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--color-text-muted);padding:var(--sp-8);">Sin movimientos</td></tr>
            <?php else: ?>
            <?php foreach ($movimientos as $m):
                $fecha = $m['fecha_movimiento'];
                if ($fecha instanceof DateTime) { $fecha = $fecha->format('d/m/Y'); }
                elseif (is_string($fecha) && $fecha) { $fecha = date('d/m/Y', strtotime($fecha)); }
                $tipoClass = match($m['tipo_movimiento'] ?? '') {
                    'Asignacion'    => 'badge-success',
                    'Transferencia' => 'badge-info',
                    'Baja'          => 'badge-danger',
                    'Reparacion'    => 'badge-warning',
                    default         => 'badge-secondary',
                };
            ?>
            <tr>
                <td><?= $m['id_movimiento'] ?></td>
                <td><span class="badge <?= $tipoClass ?>"><?= htmlspecialchars($m['tipo_movimiento'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                <td>
                    <a href="<?= APP_URL ?>/bienes/<?= $m['id_activo'] ?? '' ?>" data-spa
                       style="color:var(--color-primary);text-decoration:none;">
                        <?= htmlspecialchars($m['bien_nombre'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </td>
                <td><code><?= htmlspecialchars($m['codigo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></code></td>
                <td><?= htmlspecialchars($m['responsable'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></td>
                <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars(mb_substr($m['observaciones'] ?? '', 0, 60), ENT_QUOTES, 'UTF-8') ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
