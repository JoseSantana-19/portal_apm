<?php
$isEdit   = !empty($rol);
$errors   = $_SESSION['_form_errors'] ?? [];
$oldInput = $_SESSION['_old_input']   ?? [];
unset($_SESSION['_form_errors'], $_SESSION['_old_input']);
$v = fn(string $k) => htmlspecialchars($oldInput[$k] ?? ($rol[$k] ?? ''), ENT_QUOTES, 'UTF-8');
$action = $isEdit
    ? APP_URL . '/admin/roles/' . (int)$rol['id_rol']
    : APP_URL . '/admin/roles';

$nivelActual = (int)($oldInput['nivel_jerarquia'] ?? $rol['nivel_jerarquia'] ?? 0);
$nivelOpts   = [0=>'Operativo',1=>'Analista',2=>'Jefatura',3=>'Director',4=>'Super Admin'];
$nivelColors = [0=>'#6c757d',1=>'#17a2b8',2=>'#0056b3',3=>'#ffc107',4=>'#dc3545'];
$nivelDescs  = [
    0 => 'Acceso básico a módulos asignados',
    1 => 'Puede analizar datos y generar reportes',
    2 => 'Gestión operativa de su área',
    3 => 'Supervisión de múltiples áreas',
    4 => 'Acceso completo al sistema',
];
?>

<!-- Back + breadcrumb -->
<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-2);">
    <a href="<?= APP_URL ?>/admin/roles" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Roles
    </a>
    <span style="color:var(--color-text-muted);">/</span>
    <span style="color:var(--color-text-muted);font-size:var(--font-size-sm);"><?= $isEdit ? 'Editar' : 'Nuevo' ?></span>
</div>

<div class="page-header" style="margin-bottom:var(--sp-5);">
    <div>
        <h2 class="page-title">
            <i class="fa-solid fa-<?= $isEdit ? 'pen-to-square' : 'shield-plus' ?>" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
            <?= $isEdit ? 'Editar Rol' : 'Nuevo Rol' ?>
        </h2>
        <?php if ($isEdit): ?>
        <p class="page-subtitle">
            Modificando <code><?= htmlspecialchars($rol['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?></code>
            — para configurar permisos usa el
            <a href="<?= APP_URL ?>/admin/roles/<?= (int)$rol['id_rol'] ?>/permisos" data-spa
               style="color:var(--color-primary);">gestor de permisos</a>
        </p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger" style="margin-bottom:var(--sp-4);">
    <div>
        <div style="font-weight:var(--font-weight-semibold);margin-bottom:var(--sp-1);">
            <i class="fa-solid fa-triangle-exclamation"></i> Corrige los errores:
        </div>
        <ul style="margin:0 0 0 var(--sp-4);padding:0;">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <div style="display:grid;grid-template-columns:1fr 300px;gap:var(--sp-5);align-items:start;">

        <!-- Left -->
        <div>
            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-tag" style="color:var(--color-primary);"></i>
                    <span class="card-title">Información del Rol</span>
                </div>
                <div class="card-body">
                    <div class="grid-2" style="gap:var(--sp-4);">

                        <div class="form-group">
                            <label class="form-label">Código * <span style="color:var(--color-text-muted);font-weight:normal;">(único, mayúsculas)</span></label>
                            <input type="text" name="codigo" class="form-control" required maxlength="30"
                                   value="<?= $v('codigo') ?>"
                                   <?= $isEdit ? 'readonly style="background:var(--color-surface-2);cursor:not-allowed;text-transform:uppercase;"' : 'style="text-transform:uppercase;"' ?>
                                   placeholder="ADM_RRHH" oninput="this.value=this.value.toUpperCase()">
                            <span class="form-help">Ejemplo: <code>ADM_TH</code>, <code>OPE_ACCESO</code></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Departamento</label>
                            <select name="id_departamento" class="form-control">
                                <option value="">Global (todos los departamentos)</option>
                                <?php foreach ($deptos as $d): ?>
                                <option value="<?= $d['id_departamento'] ?>"
                                    <?= (string)($oldInput['id_departamento'] ?? $rol['id_departamento'] ?? '') === (string)$d['id_departamento'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="grid-column:1/-1;">
                            <label class="form-label">Nombre del Rol *</label>
                            <input type="text" name="nombre" class="form-control" required maxlength="100"
                                   value="<?= $v('nombre') ?>" placeholder="Ej: Administrador de Talento Humano">
                        </div>

                        <div class="form-group" style="grid-column:1/-1;margin-bottom:0;">
                            <label class="form-label">Descripción <span style="color:var(--color-text-muted);font-weight:normal;">(opcional)</span></label>
                            <textarea name="descripcion" class="form-control" rows="3" maxlength="300"
                                      placeholder="Describe brevemente las responsabilidades y alcance de este rol"><?= $v('descripcion') ?></textarea>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Right -->
        <div>
            <div class="card" style="margin-bottom:var(--sp-4);">
                <div class="card-header">
                    <i class="fa-solid fa-sitemap" style="color:var(--color-primary);"></i>
                    <span class="card-title">Nivel Mínimo</span>
                </div>
                <div class="card-body" style="padding-bottom:var(--sp-2);">
                    <div style="display:flex;flex-direction:column;gap:var(--sp-1);">
                        <?php foreach ($nivelOpts as $val => $lbl): ?>
                        <label style="display:flex;align-items:center;gap:var(--sp-2);padding:var(--sp-2);
                                      border-radius:var(--radius-md);cursor:pointer;
                                      border:1.5px solid <?= $nivelActual === $val ? $nivelColors[$val] : 'var(--color-border)' ?>;
                                      background:<?= $nivelActual === $val ? "color-mix(in srgb,{$nivelColors[$val]} 8%,transparent)" : 'transparent' ?>;
                                      transition:all var(--transition);"
                               onclick="selectNivel(<?= $val ?>, '<?= $nivelColors[$val] ?>')">
                            <input type="radio" name="nivel_jerarquia" value="<?= $val ?>"
                                   <?= $nivelActual === $val ? 'checked' : '' ?> style="display:none;"
                                   data-color="<?= $nivelColors[$val] ?>">
                            <div style="width:8px;height:8px;border-radius:var(--radius-full);
                                        background:<?= $nivelColors[$val] ?>;flex-shrink:0;"></div>
                            <div style="flex:1;">
                                <div style="font-size:var(--font-size-sm);font-weight:var(--font-weight-medium);"><?= $lbl ?></div>
                                <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);"><?= $nivelDescs[$val] ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if ($isEdit): ?>
            <div class="card" style="margin-bottom:var(--sp-4);">
                <div class="card-header">
                    <i class="fa-solid fa-toggle-on" style="color:var(--color-primary);"></i>
                    <span class="card-title">Estado</span>
                </div>
                <div class="card-body">
                    <select name="estado" class="form-control">
                        <option value="1" <?= (string)($oldInput['estado'] ?? $rol['estado'] ?? 1) === '1' ? 'selected' : '' ?>>✓ Activo</option>
                        <option value="0" <?= (string)($oldInput['estado'] ?? $rol['estado'] ?? 1) === '0' ? 'selected' : '' ?>>✗ Inactivo</option>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <div style="display:flex;flex-direction:column;gap:var(--sp-2);">
                <button type="submit" class="btn btn-primary" style="justify-content:center;">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <?= $isEdit ? 'Guardar Cambios' : 'Crear Rol' ?>
                </button>
                <?php if ($isEdit): ?>
                <a href="<?= APP_URL ?>/admin/roles/<?= (int)$rol['id_rol'] ?>/permisos"
                   class="btn btn-outline" data-spa style="justify-content:center;">
                    <i class="fa-solid fa-shield-halved"></i> Configurar Permisos
                </a>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/admin/roles" class="btn btn-ghost" data-spa style="justify-content:center;">
                    Cancelar
                </a>
            </div>
        </div>
    </div>
</form>

<style>
@media (max-width: 760px) {
    form > div { grid-template-columns: 1fr !important; }
}
</style>

<script>
function selectNivel(val, color) {
    document.querySelectorAll('[name="nivel_jerarquia"]').forEach(radio => {
        const lbl = radio.closest('label');
        const c = radio.dataset.color;
        if (parseInt(radio.value) === val) {
            radio.checked = true;
            lbl.style.borderColor = c;
            lbl.style.background  = `color-mix(in srgb, ${c} 8%, transparent)`;
        } else {
            radio.checked = false;
            lbl.style.borderColor = 'var(--color-border)';
            lbl.style.background  = 'transparent';
        }
    });
}
</script>
