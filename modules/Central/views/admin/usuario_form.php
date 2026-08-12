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

/**
 * Permisos individuales (override por usuario, cascada usuario > rol —
 * Fase 0 del sistema central de permisos). Aplana $treePermisosUsuario a
 * filas [nivel, nodo] igual que rol_permisos.php, pero acá 'permiso' > 0
 * significa "tiene una EXCEPCIÓN guardada", no "el rol se lo permite" —
 * son dos cosas distintas a propósito.
 */
function permisosUsuario_flatten(array $tree): array {
    $rows = [];
    foreach ($tree as $mod) {
        if ($mod['raiz']) $rows[] = ['tipo' => 'nodo', 'lvl' => 1, 'n' => $mod['raiz']];
        foreach ($mod['areas'] as $area) {
            if ($area['nodo']) $rows[] = ['tipo' => 'nodo', 'lvl' => 2, 'n' => $area['nodo']];
            foreach ($area['items'] as $item) {
                if ($item['nodo']) $rows[] = ['tipo' => 'nodo', 'lvl' => 3, 'n' => $item['nodo']];
                foreach ($item['subitems'] as $sub) {
                    $rows[] = ['tipo' => 'nodo', 'lvl' => 4, 'n' => $sub];
                }
            }
        }
    }
    return $rows;
}
$filasPermisosUsuario = isset($treePermisosUsuario) ? permisosUsuario_flatten($treePermisosUsuario) : [];
$overridesActivos     = $overridesActivos ?? [];
$excepcionesActivas   = count($overridesActivos);
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

<?php if (!empty($filasPermisosUsuario)): ?>
<div class="card" id="permisos-individuales" style="margin-top:var(--sp-5);">
    <div class="card-header">
        <i class="fa-solid fa-user-shield" style="color:var(--color-primary);"></i>
        <span class="card-title">Permisos Individuales (excepciones)</span>
        <span class="badge <?= $excepcionesActivas ? 'badge-warning' : 'badge-gray' ?>" style="margin-left:auto;">
            <?= $excepcionesActivas ?> excepción<?= $excepcionesActivas === 1 ? '' : 'es' ?> activa<?= $excepcionesActivas === 1 ? '' : 's' ?>
        </span>
    </div>
    <div class="card-body">
        <div class="alert alert-info" style="margin-bottom:var(--sp-4);">
            <i class="fa-solid fa-circle-info"></i>
            Por defecto este usuario tiene los permisos de sus roles asignados. Activá una excepción acá SOLO para
            darle o quitarle acceso a un ítem puntual, distinto de lo que su rol permite — el resto sigue
            gobernado por el rol, sin tocar nada más.
        </div>

        <div class="perm-usr-table-wrap" style="overflow-x:auto;">
            <table class="perm-usr-table" id="permu-table" style="width:100%;border-collapse:collapse;font-size:var(--font-size-sm);">
                <thead>
                    <tr style="border-bottom:1px solid var(--color-border-light);">
                        <th style="text-align:left;padding:8px var(--sp-2);font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);">Excepción</th>
                        <th style="text-align:left;padding:8px var(--sp-2);font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);">Módulo / Pantalla</th>
                        <th style="text-align:center;padding:8px var(--sp-2);font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);">Nivel</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($filasPermisosUsuario as $f):
                    $n = $f['n']; $lvl = $f['lvl']; $key = $n['key'];
                    $activo = array_key_exists($key, $overridesActivos);
                    $nivel  = $activo ? (int)$overridesActivos[$key] : 0;
                ?>
                    <tr data-permu-row="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" style="border-bottom:1px solid var(--color-border-light);">
                        <td style="padding:6px var(--sp-2);">
                            <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                                <input type="checkbox" data-permu-toggle <?= $activo ? 'checked' : '' ?>
                                       onchange="permuToggleRow(this)">
                                <span style="font-size:var(--font-size-xs);color:var(--color-text-muted);">Aplicar</span>
                            </label>
                        </td>
                        <td style="padding:6px var(--sp-2);">
                            <span style="padding-left:<?= ($lvl - 1) * 16 ?>px;color:var(--color-text);">
                                <?= htmlspecialchars($n['descripcion'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td style="padding:4px var(--sp-2);text-align:center;">
                            <select data-permu-nivel style="min-width:120px;" <?= $activo ? '' : 'disabled' ?> onchange="permuMarkDirty()">
                                <option value="0" <?= $nivel === 0 ? 'selected' : '' ?>>Sin acceso</option>
                                <option value="1" <?= $nivel === 1 ? 'selected' : '' ?>>Ver</option>
                                <option value="2" <?= $nivel === 2 ? 'selected' : '' ?>>Ver + Crear</option>
                                <option value="3" <?= $nivel === 3 ? 'selected' : '' ?>>Ver + Crear + Editar</option>
                                <option value="4" <?= $nivel === 4 ? 'selected' : '' ?>>Acceso total</option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="display:flex;justify-content:flex-end;margin-top:var(--sp-4);padding-top:var(--sp-4);border-top:1px solid var(--color-border-light);">
            <button type="button" class="btn btn-primary" id="permu-save-btn" onclick="permuGuardar()">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Permisos Individuales
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

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

// Permisos individuales: cada fila solo viaja en el POST si su checkbox
// "Aplicar" está tildado -- así una fila nunca tocada NO crea una excepción
// (nivel_crud=0 explícito revoca aunque el rol dé acceso; distinto de "no
// hay excepción, se hereda del rol").
function permuToggleRow(cb) {
    const tr = cb.closest('tr');
    const select = tr.querySelector('[data-permu-nivel]');
    select.disabled = !cb.checked;
    if (!cb.checked) select.value = '0';
}
function permuMarkDirty() { /* reservado para resaltar cambios sin guardar si hiciera falta */ }

function permuGuardar() {
    const btn = document.getElementById('permu-save-btn');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando…';

    const body = new URLSearchParams();
    body.set('_csrf_token', <?= json_encode($csrf) ?>);
    document.querySelectorAll('#permu-table [data-permu-row]').forEach(function (tr) {
        const toggle = tr.querySelector('[data-permu-toggle]');
        if (!toggle.checked) return;
        const key = tr.getAttribute('data-permu-row');
        const nivel = tr.querySelector('[data-permu-nivel]').value;
        body.set('overrides[' + key + ']', nivel);
    });

    fetch(<?= json_encode(APP_URL . '/admin/usuarios/' . (int)$usuario['id_usuario'] . '/permisos') ?>, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: body.toString(),
    }).then(function (r) { return r.json(); }).then(function (d) {
        btn.disabled = false;
        btn.innerHTML = original;
        if (d.ok) {
            if (window.PortalAlert) { PortalAlert.success(d.msg || 'Permisos individuales guardados.'); }
            else { location.reload(); }
        } else if (window.PortalAlert) {
            PortalAlert.error(d.msg || 'No se pudieron guardar los permisos individuales.');
        }
    }).catch(function () {
        btn.disabled = false;
        btn.innerHTML = original;
        location.reload();
    });
}
</script>
