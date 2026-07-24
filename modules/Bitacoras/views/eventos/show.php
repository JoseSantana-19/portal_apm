<?php
$id = (int)$evento['id_evento'];
$estadoClass = match($evento['estado'] ?? '') {
    'Pendiente'  => 'badge-warning',
    'En Proceso' => 'badge-info',
    'Cerrado'    => 'badge-success',
    default      => 'badge-secondary',
};
$cardAccent = match($evento['estado'] ?? '') {
    'Pendiente'  => 'card-accent-warn',
    'En Proceso' => 'card-accent',
    'Cerrado'    => 'card-accent-ok',
    default      => '',
};
$prioInt = (int)($evento['prioridad'] ?? 2);
$prioClass = match(true) {
    $prioInt >= 3 => 'badge-danger',
    $prioInt == 2 => 'badge-warning',
    default       => 'badge-info',
};
$prioLabel = match($prioInt) { 3 => 'Alta', 2 => 'Media', default => 'Baja' };
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
        <a href="<?= APP_URL ?>/bitacoras" class="btn btn-ghost btn-sm" data-spa>
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="page-title">
            <div class="page-title-icon" style="background:color-mix(in srgb,var(--warn) 14%,transparent);color:var(--warn);">
                <i class="fa-solid fa-book-open"></i>
            </div>
            Bitácora <code style="font-size:0.85rem;">#<?= $id ?></code>
        </div>
        <span class="badge <?= $estadoClass ?>"><?= htmlspecialchars($evento['estado'], ENT_QUOTES, 'UTF-8') ?></span>
        <span class="badge <?= $prioClass ?>"><?= $prioLabel ?></span>
    </div>
    <?php if ($evento['estado'] !== 'Cerrado'): ?>
    <div class="page-actions">
        <a href="<?= APP_URL ?>/bitacoras/<?= $id ?>/editar" class="btn btn-outline btn-sm" data-spa>
            <i class="fa-solid fa-pencil"></i> Editar
        </a>
        <button type="button" class="btn btn-success btn-sm" id="btn-cerrar">
            <i class="fa-solid fa-circle-check"></i> Cerrar Bitácora
        </button>
    </div>
    <?php endif; ?>
</div>

<div class="card <?= $cardAccent ?>" style="margin-bottom:var(--sp-4);">
    <h3 style="font-size:1.05rem;font-weight:800;margin:0 0 var(--sp-4);color:var(--text-app);line-height:1.3;">
        <?= htmlspecialchars($evento['titulo'], ENT_QUOTES, 'UTF-8') ?>
    </h3>

    <div class="data-grid" style="margin-bottom:var(--sp-5);">
        <?php
        $fe = $evento['fecha_evento'];
        if ($fe instanceof DateTime) { $feStr = $fe->format('d/m/Y H:i'); }
        elseif (is_string($fe) && $fe) { $feStr = date('d/m/Y H:i', strtotime($fe)); }
        else { $feStr = '—'; }
        $meta = [
            'Categoría'     => $evento['categoria']  ?? '—',
            'Fecha Evento'  => $feStr,
            'Registrado por'=> $evento['creado_por']  ?? '—',
        ];
        foreach ($meta as $lbl => $val):
        ?>
        <div>
            <div class="data-label"><?= $lbl ?></div>
            <div class="data-value"><?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="section-header">
        <div class="section-title"><span class="section-title-mark"></span>Descripción</div>
    </div>
    <p style="font-size:0.875rem;line-height:1.7;color:var(--text-app);margin:0;">
        <?= nl2br(htmlspecialchars($evento['descripcion'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
    </p>

    <?php if (!empty($evento['observaciones'])): ?>
    <div style="margin-top:var(--sp-5);padding-top:var(--sp-4);border-top:1px solid var(--border-app);">
        <div class="section-header">
            <div class="section-title"><span class="section-title-mark" style="background:var(--success);"></span>Resolución</div>
        </div>
        <p style="font-size:0.875rem;line-height:1.7;color:var(--text-app);margin:0;">
            <?= nl2br(htmlspecialchars($evento['observaciones'], ENT_QUOTES, 'UTF-8')) ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<!-- Close form (hidden, toggled) -->
<?php if ($evento['estado'] !== 'Cerrado'): ?>
<div class="card card-accent-ok" id="form-cierre" style="display:none;">
    <div class="section-header">
        <div class="section-title" style="color:var(--success);">
            <span class="section-title-mark" style="background:var(--success);"></span>
            Cerrar Bitácora
        </div>
    </div>
    <form method="POST" action="<?= APP_URL ?>/bitacoras/<?= $id ?>/cerrar">
        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-group">
            <label class="form-label">Resolución / Cierre *</label>
            <textarea name="observaciones" class="form-control" rows="3" required
                      placeholder="Describa cómo se resolvió el evento..."></textarea>
        </div>
        <div style="display:flex;gap:var(--sp-3);justify-content:flex-end;margin-top:var(--sp-4);">
            <button type="button" class="btn btn-outline" id="btn-cancelar-cierre">Cancelar</button>
            <button type="submit" class="btn btn-success">
                <i class="fa-solid fa-circle-check"></i> Confirmar Cierre
            </button>
        </div>
    </form>
</div>
<script>
document.getElementById('btn-cerrar').addEventListener('click', function() {
    document.getElementById('form-cierre').style.display = 'block';
    this.style.display = 'none';
    document.getElementById('form-cierre').scrollIntoView({behavior:'smooth'});
});
document.getElementById('btn-cancelar-cierre').addEventListener('click', function() {
    document.getElementById('form-cierre').style.display = 'none';
    document.getElementById('btn-cerrar').style.display = 'inline-flex';
});
</script>
<?php endif; ?>

</div>
