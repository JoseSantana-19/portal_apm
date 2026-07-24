<?php
$isEdit = !empty($evento);
$old    = FormHelper::old();
$v      = fn($k) => htmlspecialchars($old[$k] ?? ($evento[$k] ?? ''), ENT_QUOTES, 'UTF-8');
$errors = SessionHelper::getFlash('form_errors') ?? [];
$action = $isEdit
    ? APP_URL . '/bitacoras/' . (int)$evento['id_evento']
    : APP_URL . '/bitacoras';
?>

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-5);">
    <a href="<?= APP_URL ?>/bitacoras" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i>
    </a>
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-book-open" style="color:var(--color-warning);margin-right:var(--sp-2);"></i>
        <?= $isEdit ? 'Editar Bitácora' : 'Nueva Bitácora' ?>
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

            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Título *</label>
                <input type="text" name="titulo" class="form-control" required maxlength="200"
                       value="<?= $v('titulo') ?>" placeholder="Descripción breve del evento">
            </div>

            <div class="form-group">
                <label class="form-label">Categoría *</label>
                <select name="id_categoria" class="form-control" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id_categoria'] ?>"
                        <?= ($old['id_categoria'] ?? ($evento['id_categoria'] ?? '')) == $cat['id_categoria'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Prioridad</label>
                <select name="prioridad" class="form-control">
                    <?php
                    $prioActual = (int)($old['prioridad'] ?? $evento['prioridad'] ?? 2);
                    foreach ([1=>'Baja', 2=>'Media', 3=>'Alta'] as $val => $label):
                    ?>
                    <option value="<?= $val ?>" <?= $prioActual === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Fecha del Evento *</label>
                <input type="datetime-local" name="fecha_evento" class="form-control" required
                       value="<?= $v('fecha_evento') ?>">
            </div>

            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Descripción *</label>
                <textarea name="descripcion" class="form-control" rows="5" required><?= $v('descripcion') ?></textarea>
            </div>

        </div>

        <div style="display:flex;gap:var(--sp-3);justify-content:flex-end;margin-top:var(--sp-5);">
            <a href="<?= APP_URL ?>/bitacoras<?= $isEdit ? '/' . (int)$evento['id_evento'] : '' ?>"
               class="btn btn-outline" data-spa>Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                <?= $isEdit ? 'Guardar Cambios' : 'Registrar Evento' ?>
            </button>
        </div>
    </div>
</form>
