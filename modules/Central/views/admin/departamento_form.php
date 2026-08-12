<?php
$errors   = $_SESSION['_form_errors'] ?? [];
unset($_SESSION['_form_errors']);
$esTh = (int)$depto['origen_th'] === 1;
$h = fn($val) => htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
?>

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-2);">
    <a href="<?= APP_URL ?>/admin/departamentos" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Departamentos
    </a>
</div>

<div class="page-header" style="margin-bottom:var(--sp-5);">
    <div>
        <h2 class="page-title">
            <i class="fa-solid fa-building" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
            Editar Departamento
        </h2>
        <p class="page-subtitle">
            <code><?= $h($depto['codigo']) ?></code>
            <?php if ($esTh): ?>
            — <span class="badge badge-success"><i class="fa-solid fa-rotate" style="font-size:8px;"></i> Sincronizado desde Talento Humano</span>
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.errorList('Corrige los errores', <?= json_encode(array_values($errors)) ?>));</script>
<?php endif; ?>

<?php if ($esTh): ?>
<div class="alert alert-info" style="margin-bottom:var(--sp-4);max-width:640px;">
    <i class="fa-solid fa-circle-info"></i>
    El nombre (<strong><?= $h($depto['nombre']) ?></strong>) y la jerarquía de este departamento vienen de
    Talento Humano (unidad <code><?= $h($depto['codigo_uorg_th']) ?></code>) y se actualizan automáticamente.
    Solo el ícono, el color y el estado se administran acá.
</div>
<?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/admin/departamentos/<?= (int)$depto['id_departamento'] ?>" style="max-width:640px;">
    <input type="hidden" name="_csrf_token" value="<?= $h($csrf) ?>">

    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-sliders" style="color:var(--color-primary);"></i>
            <span class="card-title">Datos del departamento</span>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:var(--sp-4);">

            <?php if (!$esTh): ?>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Nombre *</label>
                <input type="text" name="nombre" class="form-control" required maxlength="100" value="<?= $h($depto['nombre']) ?>">
            </div>
            <?php endif; ?>

            <div class="form-group" style="margin:0;">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2" maxlength="255"><?= $h($depto['descripcion']) ?></textarea>
            </div>

            <div class="grid-2" style="gap:var(--sp-4);">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Ícono <span style="color:var(--color-text-muted);font-weight:normal;">(Font Awesome, sin el prefijo)</span></label>
                    <input type="text" name="icono" class="form-control" maxlength="50" placeholder="ej: building, users, gavel" value="<?= $h($depto['icono']) ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Color</label>
                    <input type="color" name="color_badge" class="form-control" style="height:42px;padding:4px;" value="<?= $h($depto['color_badge'] ?: '#0D2B4E') ?>">
                </div>
            </div>

            <div class="form-group" style="margin:0;">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="1" <?= (int)$depto['estado'] === 1 ? 'selected' : '' ?>>✓ Activo</option>
                    <option value="0" <?= (int)$depto['estado'] === 0 ? 'selected' : '' ?>>✗ Inactivo</option>
                </select>
            </div>

        </div>
    </div>

    <div style="display:flex;gap:var(--sp-2);margin-top:var(--sp-4);">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
        </button>
        <a href="<?= APP_URL ?>/admin/departamentos" class="btn btn-ghost" data-spa>Cancelar</a>
    </div>
</form>
