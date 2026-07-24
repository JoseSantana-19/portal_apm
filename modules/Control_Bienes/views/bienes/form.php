<?php
$isEdit = !empty($bien);
$old    = FormHelper::old();
$v      = fn($k) => htmlspecialchars($old[$k] ?? ($bien[$k] ?? ''), ENT_QUOTES, 'UTF-8');
$errors = SessionHelper::getFlash('form_errors') ?? [];
$action = $isEdit
    ? APP_URL . '/bienes/' . (int)$bien['id_activo']
    : APP_URL . '/bienes';
?>

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-5);">
    <a href="<?= APP_URL ?>/bienes" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i>
    </a>
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-boxes-stacked" style="color:var(--color-success);margin-right:var(--sp-2);"></i>
        <?= $isEdit ? 'Editar Bien' : 'Registrar Bien' ?>
    </h2>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger" style="margin-bottom:var(--sp-4);">
    <i class="fa-solid fa-triangle-exclamation"></i>
    <ul style="margin:var(--sp-2) 0 0 var(--sp-4);">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5);">

            <div class="form-group">
                <label class="form-label">Código del Bien *</label>
                <input type="text" name="codigo" class="form-control" required maxlength="50"
                       value="<?= $v('codigo') ?>" placeholder="APM-TI-001"
                       <?= $isEdit ? 'readonly' : '' ?>>
            </div>

            <div class="form-group">
                <label class="form-label">Nombre *</label>
                <input type="text" name="nombre" class="form-control" required maxlength="200"
                       value="<?= $v('nombre') ?>" placeholder="Computadora Portátil">
            </div>

            <div class="form-group">
                <label class="form-label">Categoría *</label>
                <select name="id_categoria" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id_categoria'] ?>"
                        <?= ($old['id_categoria'] ?? ($bien['id_categoria'] ?? '')) == $cat['id_categoria'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Departamento Asignado *</label>
                <select name="id_departamento" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($deptos as $d): ?>
                    <option value="<?= $d['id_departamento'] ?>"
                        <?= ($old['id_departamento'] ?? ($bien['id_departamento'] ?? '')) == $d['id_departamento'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Valor Adquisición *</label>
                <input type="number" name="valor_adquisicion" class="form-control" required step="0.01" min="0"
                       value="<?= $v('valor_adquisicion') ?>" placeholder="0.00">
            </div>

            <div class="form-group">
                <label class="form-label">Fecha Adquisición *</label>
                <input type="date" name="fecha_adquisicion" class="form-control" required
                       value="<?= $v('fecha_adquisicion') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <?php foreach (['Activo'=>'Activo','En Reparacion'=>'En Reparación','Baja'=>'Baja'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $v('estado') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Descripción</label>
                <textarea name="descripcion" class="form-control" rows="3"><?= $v('descripcion') ?></textarea>
            </div>

        </div>

        <div style="display:flex;gap:var(--sp-3);justify-content:flex-end;margin-top:var(--sp-5);">
            <a href="<?= APP_URL ?>/bienes<?= $isEdit ? '/' . (int)$bien['id_activo'] : '' ?>"
               class="btn btn-outline" data-spa>Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                <?= $isEdit ? 'Guardar Cambios' : 'Registrar Bien' ?>
            </button>
        </div>
    </div>
</form>
