<?php
$errors = $_SESSION['_form_errors'] ?? [];
$old    = $_SESSION['_old_input'] ?? [];
unset($_SESSION['_form_errors'], $_SESSION['_old_input']);
$h = fn($val) => htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
$esEdicion = $modulo !== null;
$val = fn($campo, $default = '') => $h($old[$campo] ?? ($esEdicion ? ($modulo[$campo] ?? $default) : $default));
?>

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-2);">
    <a href="<?= APP_URL ?>/admin/modulos" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Módulos
    </a>
</div>

<div class="page-header" style="margin-bottom:var(--sp-5);">
    <div>
        <h2 class="page-title">
            <i class="fa-solid fa-layer-group" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
            <?= $esEdicion ? 'Editar Módulo' : 'Nuevo Módulo' ?>
        </h2>
        <?php if ($esEdicion): ?>
        <p class="page-subtitle"><code><?= $h($modulo['codigo']) ?></code> · id_modulo <?= (int)$modulo['id_modulo'] ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($errors)): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.errorList('Corrige los errores', <?= json_encode(array_values($errors)) ?>));</script>
<?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/admin/modulos<?= $esEdicion ? '/' . (int)$modulo['id_modulo'] : '' ?>" style="max-width:640px;">
    <input type="hidden" name="_csrf_token" value="<?= $h($csrf) ?>">

    <div class="card">
        <div class="card-header">
            <i class="fa-solid fa-sliders" style="color:var(--color-primary);"></i>
            <span class="card-title">Datos del módulo</span>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:var(--sp-4);">

            <div class="grid-2" style="gap:var(--sp-4);">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">ID de módulo *</label>
                    <input type="number" name="id_modulo" class="form-control" required min="1" max="255"
                           <?= $esEdicion ? 'readonly' : '' ?>
                           value="<?= $esEdicion ? (int)$modulo['id_modulo'] : $h($old['id_modulo'] ?? $siguienteId) ?>">
                    <?php if (!$esEdicion): ?><small style="color:var(--color-text-muted);">Coordenada MOIS (nivel Módulo) — sugerido: siguiente libre.</small><?php endif; ?>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Código *</label>
                    <input type="text" name="codigo" class="form-control" required maxlength="30"
                           <?= $esEdicion ? 'readonly' : '' ?>
                           placeholder="ej: RRHH2" value="<?= $val('codigo') ?>">
                </div>
            </div>

            <div class="form-group" style="margin:0;">
                <label class="form-label">Nombre *</label>
                <input type="text" name="nombre" class="form-control" required maxlength="150" value="<?= $val('nombre') ?>">
            </div>

            <div class="grid-2" style="gap:var(--sp-4);">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Ícono <span style="color:var(--color-text-muted);font-weight:normal;">(clase Font Awesome completa)</span></label>
                    <input type="text" name="icono" class="form-control" maxlength="50" placeholder="fa-folder" value="<?= $val('icono', 'fa-folder') ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Color</label>
                    <input type="color" name="color" class="form-control" style="height:42px;padding:4px;" value="<?= $val('color', '#6c757d') ?>">
                </div>
            </div>

            <div class="form-group" style="margin:0;">
                <label class="form-label">Tipo *</label>
                <select name="tipo" class="form-control" required>
                    <option value="nativo"   <?= $val('tipo', 'nativo') === 'nativo' ? 'selected' : '' ?>>Nativo (parte del portal)</option>
                    <option value="embebido" <?= $val('tipo') === 'embebido' ? 'selected' : '' ?>>Embebido (Patrón B — app propia)</option>
                </select>
            </div>

            <div class="grid-2" style="gap:var(--sp-4);">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Base URL <span style="color:var(--color-text-muted);font-weight:normal;">(solo embebido)</span></label>
                    <input type="text" name="base_url" class="form-control" maxlength="200" placeholder="/apps/mi_modulo" value="<?= $val('base_url') ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Conexión BD <span style="color:var(--color-text-muted);font-weight:normal;">(clave en config/connections.php)</span></label>
                    <input type="text" name="conexion_bd" class="form-control" maxlength="50" placeholder="talento" value="<?= $val('conexion_bd') ?>">
                </div>
            </div>

            <div class="form-group" style="margin:0;max-width:180px;">
                <label class="form-label">Orden</label>
                <input type="number" name="orden" class="form-control" min="0" value="<?= $val('orden', '0') ?>">
            </div>

        </div>
    </div>

    <div style="display:flex;gap:var(--sp-2);margin-top:var(--sp-4);">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> Guardar
        </button>
        <a href="<?= APP_URL ?>/admin/modulos" class="btn btn-ghost" data-spa>Cancelar</a>
    </div>
</form>
