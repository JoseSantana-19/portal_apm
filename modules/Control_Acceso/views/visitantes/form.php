<?php
$errors = SessionHelper::getFlash('form_errors') ?? [];
$old    = FormHelper::old();
$v      = fn($k) => htmlspecialchars($old[$k] ?? '', ENT_QUOTES, 'UTF-8');
?>

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-5);">
    <a href="<?= APP_URL ?>/acceso/visitantes" class="btn btn-ghost btn-sm" data-spa><i class="fa-solid fa-arrow-left"></i></a>
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-user-plus" style="color:var(--color-info);margin-right:var(--sp-2);"></i>
        Nuevo Visitante
    </h2>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger" style="margin-bottom:var(--sp-4);">
    <ul style="margin:0 0 0 var(--sp-4);">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if (!empty($error_msg = SessionHelper::getFlash('error'))): ?>
<div class="alert alert-danger" style="margin-bottom:var(--sp-4);">
    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<div style="max-width:560px;">
<form method="POST" action="<?= APP_URL ?>/acceso/visitantes">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card">
        <div class="form-group">
            <label class="form-label">Cédula / Documento *</label>
            <input type="text" name="cedula" class="form-control" required maxlength="20"
                   value="<?= $v('cedula') ?>" placeholder="Ej. 1712345678">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-4);margin-top:var(--sp-4);">
            <div class="form-group">
                <label class="form-label">Nombres *</label>
                <input type="text" name="nombres" class="form-control" required maxlength="100"
                       value="<?= $v('nombres') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Apellidos *</label>
                <input type="text" name="apellidos" class="form-control" required maxlength="100"
                       value="<?= $v('apellidos') ?>">
            </div>
        </div>
        <div class="form-group" style="margin-top:var(--sp-4);">
            <label class="form-label">Empresa / Institución</label>
            <input type="text" name="empresa" class="form-control" maxlength="150" value="<?= $v('empresa') ?>">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-4);margin-top:var(--sp-4);">
            <div class="form-group">
                <label class="form-label">Correo</label>
                <input type="email" name="correo" class="form-control" value="<?= $v('correo') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Teléfono</label>
                <input type="tel" name="telefono" class="form-control" value="<?= $v('telefono') ?>">
            </div>
        </div>
        <div style="display:flex;gap:var(--sp-3);justify-content:flex-end;margin-top:var(--sp-5);">
            <a href="<?= APP_URL ?>/acceso/visitantes" class="btn btn-outline" data-spa>Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Registrar</button>
        </div>
    </div>
</form>
</div>
