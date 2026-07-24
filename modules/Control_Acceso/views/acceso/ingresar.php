<?php
$errors = SessionHelper::getFlash('form_errors') ?? [];
$old    = FormHelper::old();
?>

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-5);">
    <a href="<?= APP_URL ?>/acceso" class="btn btn-ghost btn-sm" data-spa><i class="fa-solid fa-arrow-left"></i></a>
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-person-walking-arrow-right" style="color:var(--color-info);margin-right:var(--sp-2);"></i>
        Registrar Ingreso
    </h2>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger" style="margin-bottom:var(--sp-4);">
    <ul style="margin:0 0 0 var(--sp-4);">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div style="max-width:640px;">
<form method="POST" action="<?= APP_URL ?>/acceso/ingresar">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card">

        <div class="form-group">
            <label class="form-label">Nombre Completo *</label>
            <input type="text" name="persona_visita" class="form-control" required maxlength="150"
                   value="<?= htmlspecialchars($old['persona_visita'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="Nombres y apellidos">
        </div>

        <div class="form-group" style="margin-top:var(--sp-4);">
            <label class="form-label">Motivo de Visita</label>
            <input type="text" name="motivo" class="form-control" maxlength="200"
                   value="<?= htmlspecialchars($old['motivo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   placeholder="Reunión, trámite, entrega, etc.">
        </div>

        <div style="display:flex;gap:var(--sp-3);justify-content:flex-end;margin-top:var(--sp-5);">
            <a href="<?= APP_URL ?>/acceso" class="btn btn-outline" data-spa>Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-circle-check"></i> Registrar Ingreso
            </button>
        </div>
    </div>
</form>
</div>
