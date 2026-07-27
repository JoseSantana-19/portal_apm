<?php
$errors   = $_SESSION['_form_errors'] ?? [];
$oldInput = $_SESSION['_old_input']   ?? [];
unset($_SESSION['_form_errors'], $_SESSION['_old_input']);
$v = fn(string $k, string $default = '') => htmlspecialchars($oldInput[$k] ?? $default, ENT_QUOTES, 'UTF-8');

$sugDepto        = $sugerido['id_departamento'] ?? null;
$sugRolAnalista  = $sugerido['id_rol_analista'] ?? null;
$sugRolDirector  = $sugerido['id_rol_director'] ?? null;
$nivelOpts   = [0=>'Operativo',1=>'Analista',2=>'Jefatura',3=>'Director',4=>'Super Admin'];
?>

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-2);">
    <a href="<?= APP_URL ?>/admin/usuarios/desde-th" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Empleados TH
    </a>
</div>

<div class="page-header" style="margin-bottom:var(--sp-5);">
    <div>
        <h2 class="page-title">
            <i class="fa-solid fa-user-plus" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
            Nueva cuenta — <?= htmlspecialchars($empleado['nombre_completo'], ENT_QUOTES, 'UTF-8') ?>
        </h2>
        <p class="page-subtitle">
            Cédula <code><?= htmlspecialchars($empleado['cedula'] ?? '', ENT_QUOTES, 'UTF-8') ?></code>
            · Unidad <?= htmlspecialchars($empleado['nombre_unidad'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
            — el nombre y la cédula se leen siempre en vivo desde Talento Humano, no se duplican acá.
        </p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger" style="margin-bottom:var(--sp-4);">
    <div>
        <div style="font-weight:var(--font-weight-semibold);margin-bottom:var(--sp-1);">
            <i class="fa-solid fa-triangle-exclamation"></i> Corrige los siguientes errores:
        </div>
        <ul style="margin:0 0 0 var(--sp-4);padding:0;">
            <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php if (!$sugerido): ?>
<div class="alert alert-warning" style="margin-bottom:var(--sp-4);">
    <i class="fa-solid fa-circle-info"></i>
    Esta unidad organizacional no tiene departamento/rol autosugerido en <code>TH_Unidad_Map</code>.
    Elegí manualmente abajo.
</div>
<?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/admin/usuarios/desde-th">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id_empleado_th" value="<?= (int)$empleado['empleado_id'] ?>">

    <div style="display:grid;grid-template-columns:1fr 320px;gap:var(--sp-5);align-items:start;">
        <div class="card" style="margin-bottom:var(--sp-4);">
            <div class="card-header">
                <i class="fa-solid fa-id-card" style="color:var(--color-primary);"></i>
                <span class="card-title">Datos de Acceso</span>
            </div>
            <div class="card-body">
                <div class="grid-2" style="gap:var(--sp-4);">
                    <div class="form-group">
                        <label class="form-label">Nombre de Usuario *</label>
                        <input type="text" name="nombre_usuario" class="form-control" required maxlength="50"
                               value="<?= $v('nombre_usuario') ?>" placeholder="nombre.apellido">
                        <span class="form-help">Solo letras y números, sin espacios</span>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Correo</label>
                        <input type="email" name="correo" class="form-control" maxlength="150"
                               value="<?= $v('correo', $empleado['correo_institucional'] ?? '') ?>">
                        <span class="form-help">Por defecto, el correo institucional de Talento Humano</span>
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label class="form-label">Contraseña Temporal *</label>
                        <input type="password" name="contrasena" class="form-control" required minlength="8"
                               placeholder="Mínimo 8 caracteres">
                        <span class="form-help">El usuario deberá cambiarla en su primer ingreso</span>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card" style="margin-bottom:var(--sp-4);">
                <div class="card-header">
                    <i class="fa-solid fa-sliders" style="color:var(--color-primary);"></i>
                    <span class="card-title">Departamento y Rol</span>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Departamento *</label>
                        <select name="id_departamento" class="form-control" required>
                            <option value="">Seleccione…</option>
                            <?php foreach ($deptos as $d): ?>
                            <option value="<?= $d['id_departamento'] ?>"
                                <?= (string)($oldInput['id_departamento'] ?? $sugDepto ?? '') === (string)$d['id_departamento'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($sugDepto): ?><span class="form-help">Sugerido por su unidad organizacional</span><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Rol *</label>
                        <select name="id_rol" class="form-control" required>
                            <option value="">Seleccione…</option>
                            <?php foreach ($todosRoles as $r):
                                $isSugAnalista = $sugRolAnalista && (int)$r['id_rol'] === (int)$sugRolAnalista;
                                $isSugDirector = $sugRolDirector && (int)$r['id_rol'] === (int)$sugRolDirector;
                                $selected = (string)($oldInput['id_rol'] ?? $sugRolAnalista ?? '') === (string)$r['id_rol'];
                            ?>
                            <option value="<?= $r['id_rol'] ?>" <?= $selected ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?><?= $isSugAnalista ? ' (sugerido — analista)' : ($isSugDirector ? ' (sugerido — jefatura)' : '') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Nivel Jerárquico *</label>
                        <select name="nivel_jerarquia" class="form-control" required>
                            <?php foreach ($nivelOpts as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= (string)($oldInput['nivel_jerarquia'] ?? '1') === (string)$val ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:var(--sp-2);">
                <button type="submit" class="btn btn-primary" style="justify-content:center;">
                    <i class="fa-solid fa-floppy-disk"></i> Crear cuenta
                </button>
                <a href="<?= APP_URL ?>/admin/usuarios/desde-th" class="btn btn-ghost" data-spa style="justify-content:center;">
                    Cancelar
                </a>
            </div>
        </div>
    </div>
</form>
