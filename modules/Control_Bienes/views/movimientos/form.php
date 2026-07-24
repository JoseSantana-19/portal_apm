<?php
$old    = FormHelper::old();
$errors = SessionHelper::getFlash('form_errors') ?? [];
$preselBien = (int)($_GET['bien'] ?? $old['id_activo'] ?? 0);
?>

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-5);">
    <a href="<?= APP_URL ?>/bienes/movimientos" class="btn btn-ghost btn-sm" data-spa><i class="fa-solid fa-arrow-left"></i></a>
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-arrows-rotate" style="color:var(--color-success);margin-right:var(--sp-2);"></i>
        Registrar Movimiento
    </h2>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger" style="margin-bottom:var(--sp-4);">
    <ul style="margin:0 0 0 var(--sp-4);">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/bienes/movimientos">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5);">

            <div class="form-group">
                <label class="form-label">Bien *</label>
                <select name="id_activo" class="form-control" required>
                    <option value="">Seleccione bien...</option>
                    <?php foreach ($bienes as $b): ?>
                    <option value="<?= $b['id_activo'] ?>" <?= $preselBien == $b['id_activo'] ? 'selected' : '' ?>>
                        [<?= htmlspecialchars($b['codigo'], ENT_QUOTES, 'UTF-8') ?>] <?= htmlspecialchars($b['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Tipo Movimiento *</label>
                <select name="tipo_movimiento" class="form-control" required id="tipo_mov">
                    <option value="">Seleccione...</option>
                    <option value="Asignacion">Asignación</option>
                    <option value="Transferencia">Transferencia</option>
                    <option value="Reparacion">Reparación</option>
                    <option value="Devolucion">Devolución</option>
                    <option value="Baja">Baja</option>
                </select>
            </div>

            <div class="form-group" id="row-origen" style="display:none;">
                <label class="form-label">Departamento Origen</label>
                <select name="id_depto_origen" class="form-control">
                    <option value="">Seleccione...</option>
                    <?php foreach ($deptos as $d): ?>
                    <option value="<?= $d['id_departamento'] ?>"><?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="row-destino" style="display:none;">
                <label class="form-label">Departamento Destino</label>
                <select name="id_depto_destino" class="form-control">
                    <option value="">Seleccione...</option>
                    <?php foreach ($deptos as $d): ?>
                    <option value="<?= $d['id_departamento'] ?>"><?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="grid-column:1/-1;">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="3"></textarea>
            </div>

        </div>

        <div style="display:flex;gap:var(--sp-3);justify-content:flex-end;margin-top:var(--sp-5);">
            <a href="<?= APP_URL ?>/bienes/movimientos" class="btn btn-outline" data-spa>Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-check"></i> Registrar Movimiento
            </button>
        </div>
    </div>
</form>

<script>
document.getElementById('tipo_mov').addEventListener('change', function() {
    const v = this.value;
    const ori  = document.getElementById('row-origen');
    const dest = document.getElementById('row-destino');
    ori.style.display  = (v === 'Transferencia') ? 'block' : 'none';
    dest.style.display = (v === 'Transferencia') ? 'block' : 'none';
});
</script>
