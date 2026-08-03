<?php
// Esta pantalla es EXCLUSIVAMENTE de edición — la creación de cuentas es
// solo desde Talento Humano (ver empleados_th.php / usuario_desde_empleado.php).
$errors   = $_SESSION['_form_errors'] ?? [];
$oldInput = $_SESSION['_old_input']   ?? [];
unset($_SESSION['_form_errors'], $_SESSION['_old_input']);
$v = fn(string $k) => htmlspecialchars($oldInput[$k] ?? ($usuario[$k] ?? ''), ENT_QUOTES, 'UTF-8');
$action = APP_URL . '/admin/usuarios/' . (int)$usuario['id_usuario'];

$nivelActual = (int)($oldInput['nivel_jerarquia'] ?? $usuario['nivel_jerarquia'] ?? 0);
$nivelOpts   = [0=>'Operativo',1=>'Analista',2=>'Jefatura',3=>'Director',4=>'Super Admin'];
$nivelColors = [0=>'#6c757d',1=>'#17a2b8',2=>'#0056b3',3=>'#ffc107',4=>'#dc3545'];
$nivelIcons  = [0=>'fa-user',1=>'fa-user-tie',2=>'fa-briefcase',3=>'fa-building',4=>'fa-crown'];
?>

<!-- Back + title -->
<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-2);">
    <a href="<?= APP_URL ?>/admin/usuarios" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Usuarios
    </a>
    <span style="color:var(--color-text-muted);">/</span>
    <span style="color:var(--color-text-muted);font-size:var(--font-size-sm);">Editar</span>
</div>

<div class="page-header" style="margin-bottom:var(--sp-5);">
    <div>
        <h2 class="page-title">
            <i class="fa-solid fa-user-pen" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
            Editar Usuario
        </h2>
        <p class="page-subtitle">
            Modificando cuenta <code><?= htmlspecialchars($usuario['cedula'] ?? '', ENT_QUOTES, 'UTF-8') ?></code>
        </p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.errorList('Corrige los siguientes errores', <?= json_encode(array_values($errors)) ?>));</script>
<?php endif; ?>

<form method="POST" action="<?= $action ?>" id="usr-form">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <div style="display:grid;grid-template-columns:1fr 320px;gap:var(--sp-5);align-items:start;">

        <!-- Left: Main data -->
        <div>
            <div class="card" style="margin-bottom:var(--sp-4);">
                <div class="card-header">
                    <i class="fa-solid fa-id-card" style="color:var(--color-primary);"></i>
                    <span class="card-title">Datos de Acceso</span>
                </div>
                <div class="card-body">
                    <div class="grid-2" style="gap:var(--sp-4);">

                        <div class="form-group">
                            <label class="form-label">Cédula</label>
                            <input type="text" class="form-control" readonly
                                   style="background:var(--color-surface-2);cursor:not-allowed;"
                                   value="<?= htmlspecialchars($usuario['cedula'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <span class="form-help">Login del usuario — se toma de Talento Humano, no se edita acá</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nombre Completo *</label>
                            <input type="text" name="nombre_completo" class="form-control" required maxlength="150"
                                   value="<?= $v('nombre_completo') ?>" placeholder="Apellido Nombre">
                        </div>

                        <div class="form-group" style="grid-column:1/-1;">
                            <label class="form-label">Correo Electrónico *</label>
                            <input type="email" name="correo" class="form-control" required maxlength="150"
                                   value="<?= $v('correo') ?>" placeholder="usuario@apmpuerto.com">
                        </div>

                    </div>
                </div>
            </div>

            <?php if (!empty($todosRoles)): ?>
            <div class="card">
                <div class="card-header">
                    <i class="fa-solid fa-key" style="color:var(--color-primary);"></i>
                    <span class="card-title">Roles Asignados</span>
                    <?php
                    $rolesOld = isset($oldInput['roles']) ? array_map('intval', $oldInput['roles']) : $rolesAsignadosIds;
                    $assignedCount = count($rolesOld);
                    ?>
                    <span class="badge badge-primary" style="margin-left:auto;"><?= $assignedCount ?> asignado<?= $assignedCount !== 1 ? 's' : '' ?></span>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:var(--sp-2);">
                        <?php foreach ($todosRoles as $r):
                            $checked = in_array((int)$r['id_rol'], $rolesOld, true);
                        ?>
                        <label class="role-chip <?= $checked ? 'checked' : '' ?>"
                               style="display:flex;align-items:center;gap:var(--sp-2);padding:var(--sp-2) var(--sp-3);
                                      border:1.5px solid <?= $checked ? 'var(--color-primary)' : 'var(--color-border)' ?>;
                                      border-radius:var(--radius-md);cursor:pointer;transition:all var(--transition);
                                      background:<?= $checked ? 'color-mix(in srgb,var(--color-primary) 6%,transparent)' : 'transparent' ?>;"
                               onmouseenter="this.style.borderColor='var(--color-primary)'"
                               onmouseleave="if(!this.querySelector('input').checked){this.style.borderColor='var(--color-border)';this.style.background='transparent';}">
                            <input type="checkbox" name="roles[]" value="<?= $r['id_rol'] ?>" <?= $checked ? 'checked' : '' ?>
                                   onchange="updateChip(this)">
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:var(--font-size-sm);font-weight:var(--font-weight-medium);
                                            white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                    <?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);">
                                    <code><?= htmlspecialchars($r['codigo'], ENT_QUOTES, 'UTF-8') ?></code>
                                </div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: Settings -->
        <div>
            <div class="card" style="margin-bottom:var(--sp-4);">
                <div class="card-header">
                    <i class="fa-solid fa-sliders" style="color:var(--color-primary);"></i>
                    <span class="card-title">Configuración</span>
                </div>
                <div class="card-body">

                    <div class="form-group">
                        <label class="form-label">Departamento *</label>
                        <select name="id_departamento" class="form-control" required>
                            <option value="">Seleccione…</option>
                            <?php foreach ($deptos as $d): ?>
                            <option value="<?= $d['id_departamento'] ?>"
                                <?= (string)($oldInput['id_departamento'] ?? $usuario['id_departamento'] ?? '') === (string)$d['id_departamento'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Nivel Jerárquico *</label>
                        <div style="display:flex;flex-direction:column;gap:var(--sp-1);">
                            <?php foreach ($nivelOpts as $val => $lbl): ?>
                            <label style="display:flex;align-items:center;gap:var(--sp-2);padding:var(--sp-2);
                                          border-radius:var(--radius-md);cursor:pointer;
                                          border:1.5px solid <?= $nivelActual === $val ? $nivelColors[$val] : 'var(--color-border)' ?>;
                                          background:<?= $nivelActual === $val ? "color-mix(in srgb,{$nivelColors[$val]} 8%,transparent)" : 'transparent' ?>;
                                          transition:all var(--transition);"
                                   onclick="selectNivel(<?= $val ?>)">
                                <input type="radio" name="nivel_jerarquia" value="<?= $val ?>"
                                       <?= $nivelActual === $val ? 'checked' : '' ?> style="display:none;">
                                <div style="width:28px;height:28px;border-radius:var(--radius-full);
                                            background:color-mix(in srgb,<?= $nivelColors[$val] ?> 15%,transparent);
                                            color:<?= $nivelColors[$val] ?>;
                                            display:flex;align-items:center;justify-content:center;
                                            font-size:0.7rem;flex-shrink:0;">
                                    <i class="fa-solid <?= $nivelIcons[$val] ?>"></i>
                                </div>
                                <span style="font-size:var(--font-size-sm);font-weight:var(--font-weight-medium);"><?= $lbl ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom:0;">
                        <label class="form-label">Estado de la Cuenta</label>
                        <select name="estado" class="form-control">
                            <option value="1" <?= (string)($oldInput['estado'] ?? $usuario['estado'] ?? 1) === '1' ? 'selected' : '' ?>>
                                ✓ Activo
                            </option>
                            <option value="0" <?= (string)($oldInput['estado'] ?? $usuario['estado'] ?? 1) === '0' ? 'selected' : '' ?>>
                                ✗ Inactivo
                            </option>
                        </select>
                    </div>

                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:var(--sp-2);">
                <button type="submit" class="btn btn-primary" style="justify-content:center;">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar Cambios
                </button>
                <a href="<?= APP_URL ?>/admin/usuarios" class="btn btn-ghost" data-spa style="justify-content:center;">
                    Cancelar
                </a>
            </div>
        </div>
    </div>
</form>

<style>
@media (max-width: 860px) {
    #usr-form > div { grid-template-columns: 1fr !important; }
}
</style>

<script>
function selectNivel(val) {
    document.querySelectorAll('[name="nivel_jerarquia"]').forEach((radio, i) => {
        const lbl = radio.closest('label');
        if (parseInt(radio.value) === val) {
            radio.checked = true;
            // re-apply selected style via PHP colors embedded in data attr
            lbl.style.borderColor = lbl.querySelector('.fa-solid').parentElement.style.color || 'var(--color-primary)';
        } else {
            radio.checked = false;
            lbl.style.borderColor = 'var(--color-border)';
            lbl.style.background  = 'transparent';
        }
    });
}
function updateChip(cb) {
    const lbl = cb.closest('label');
    if (cb.checked) {
        lbl.style.borderColor = 'var(--color-primary)';
        lbl.style.background  = 'color-mix(in srgb,var(--color-primary) 6%,transparent)';
    } else {
        lbl.style.borderColor = 'var(--color-border)';
        lbl.style.background  = 'transparent';
    }
}
</script>
