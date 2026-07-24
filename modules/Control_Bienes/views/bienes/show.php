<?php
$id = (int)$bien['id_activo'];
$estadoClass = match($bien['estado'] ?? '') {
    'Activo'        => 'badge-success',
    'En Reparacion' => 'badge-warning',
    'Baja'          => 'badge-danger',
    default         => 'badge-secondary',
};
$cardAccent = match($bien['estado'] ?? '') {
    'Activo'        => 'card-accent-ok',
    'En Reparacion' => 'card-accent-warn',
    'Baja'          => 'card-accent-fail',
    default         => 'card-accent',
};
$success = SessionHelper::getFlash('success');
$error   = SessionHelper::getFlash('error');
?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="animation:pageFadeIn 0.3s ease-out both;">

<div class="page-heading">
    <div style="display:flex;align-items:center;gap:var(--sp-3);">
        <a href="<?= APP_URL ?>/bienes" class="btn btn-ghost btn-sm" data-spa><i class="fa-solid fa-arrow-left"></i></a>
        <div class="page-title">
            <div class="page-title-icon" style="background:color-mix(in srgb,var(--success) 14%,transparent);color:var(--success);">
                <i class="fa-solid fa-cube"></i>
            </div>
            <?= htmlspecialchars($bien['nombre'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <span class="badge <?= $estadoClass ?>"><?= htmlspecialchars($bien['estado'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php if ($bien['estado'] !== 'Baja'): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/bienes/<?= $id ?>/editar" class="btn btn-outline btn-sm" data-spa>
            <i class="fa-solid fa-pencil"></i> Editar
        </a>
        <a href="<?= APP_URL ?>/bienes/movimientos/nuevo?bien=<?= $id ?>" class="btn btn-outline btn-sm" data-spa>
            <i class="fa-solid fa-arrows-rotate"></i> Movimiento
        </a>
        <button class="btn btn-danger btn-sm" id="btn-baja">
            <i class="fa-solid fa-trash"></i> Dar Baja
        </button>
    </div>
    <?php endif; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5);">

    <div class="card <?= $cardAccent ?>">
        <div class="section-header">
            <div class="section-title">
                <span class="section-title-mark"></span>
                Información del Bien
            </div>
        </div>
        <div class="data-grid">
            <?php
            $fields = [
                'Código'       => $bien['codigo']            ?? '—',
                'Categoría'    => $bien['categoria']         ?? '—',
                'Departamento' => $bien['departamento']      ?? '—',
                'Valor'        => '$' . number_format((float)($bien['valor_adquisicion'] ?? 0), 2),
                'Adquisición'  => $bien['fecha_adquisicion']
                    ? date('d/m/Y', strtotime($bien['fecha_adquisicion'])) : '—',
            ];
            foreach ($fields as $lbl => $val):
            ?>
            <div>
                <div class="data-label"><?= $lbl ?></div>
                <div class="data-value"><?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($bien['descripcion'])): ?>
        <div style="margin-top:var(--sp-4);padding-top:var(--sp-3);border-top:1px solid var(--border-app);">
            <div class="data-label" style="margin-bottom:var(--sp-2);">Descripción</div>
            <p style="font-size:0.875rem;color:var(--text-app);margin:0;line-height:1.6;">
                <?= htmlspecialchars($bien['descripcion'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="section-header">
            <div class="section-title">
                <span class="section-title-mark"></span>
                Historial de Movimientos
            </div>
        </div>
        <?php if (empty($movimientos)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fa-solid fa-arrows-rotate"></i></div>
            <div class="empty-state-title">Sin movimientos</div>
            <div class="empty-state-hint">No se han registrado movimientos para este bien.</div>
        </div>
        <?php else: ?>
        <div style="overflow-y:auto;max-height:300px;">
        <?php foreach ($movimientos as $m):
            $fecha = $m['fecha_movimiento'];
            if ($fecha instanceof DateTime) { $fecha = $fecha->format('d/m/Y'); }
            elseif (is_string($fecha)) { $fecha = date('d/m/Y', strtotime($fecha)); }
        ?>
        <div class="activity-item" style="padding:var(--sp-2) 0;">
            <div class="activity-avatar" style="width:28px;height:28px;font-size:0.65rem;">
                <?= mb_strtoupper(mb_substr($m['tipo_movimiento'] ?? 'M', 0, 2)) ?>
            </div>
            <div class="activity-content">
                <div class="activity-text"><?= htmlspecialchars($m['tipo_movimiento'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="activity-time"><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($m['responsable'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- Baja form -->
<?php if ($bien['estado'] !== 'Baja'): ?>
<div class="card card-accent-fail" id="form-baja" style="display:none;margin-top:var(--sp-4);">
    <div class="section-header">
        <div class="section-title" style="color:var(--danger);">
            <span class="section-title-mark" style="background:var(--danger);"></span>
            Dar de Baja
        </div>
    </div>
    <form method="POST" action="<?= APP_URL ?>/bienes/<?= $id ?>/dar-baja">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group">
            <label class="form-label">Motivo de Baja *</label>
            <textarea name="motivo" class="form-control" rows="2" required placeholder="Describa el motivo..."></textarea>
        </div>
        <div style="display:flex;gap:var(--sp-3);justify-content:flex-end;margin-top:var(--sp-4);">
            <button type="button" class="btn btn-outline" id="btn-cancelar-baja">Cancelar</button>
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Confirmar Baja</button>
        </div>
    </form>
</div>
<script>
document.getElementById('btn-baja').addEventListener('click', function() {
    document.getElementById('form-baja').style.display = 'block';
    this.style.display = 'none';
});
document.getElementById('btn-cancelar-baja').addEventListener('click', function() {
    document.getElementById('form-baja').style.display = 'none';
    document.getElementById('btn-baja').style.display = 'inline-flex';
});
</script>
<?php endif; ?>

</div>
