<?php
// Esta pantalla es EXCLUSIVAMENTE de edición — la creación de cuentas es
// solo desde Talento Humano (ver empleados_th.php / usuario_desde_empleado.php).
// Rediseño 2026-08-13 (a pedido explícito del usuario): un solo <form>, un
// solo botón de guardado -- datos+roles+permisos individuales viajan juntos
// a AdminController::guardarUsuarioCompleto() en una sola transacción.
// Antes eran 2 acciones separadas (actualizarUsuario() + guardarPermisosUsuario()
// por AJAX aparte); esa segunda fallaba en silencio por el bug de
// `const PortalAlert` (ver memoria inactividad_sesion) y de paso confundía
// -- "guardar cambios" de arriba y "guardar permisos" de abajo parecían
// independientes cuando en realidad son la misma cuenta.
$errors   = $_SESSION['_form_errors'] ?? [];
$oldInput = $_SESSION['_old_input']   ?? [];
unset($_SESSION['_form_errors'], $_SESSION['_old_input']);
$v = fn(string $k) => htmlspecialchars($oldInput[$k] ?? ($usuario[$k] ?? ''), ENT_QUOTES, 'UTF-8');
$action = APP_URL . '/admin/usuarios/' . (int)$usuario['id_usuario'] . '/completo';

$nivelActual = (int)($oldInput['nivel_jerarquia'] ?? $usuario['nivel_jerarquia'] ?? 0);
$nivelOpts   = [0=>'Operativo',1=>'Analista',2=>'Jefatura',3=>'Director',4=>'Super Admin'];
$nivelColors = [0=>'#6c757d',1=>'#17a2b8',2=>'#0056b3',3=>'#ffc107',4=>'#dc3545'];
$nivelIcons  = [0=>'fa-user',1=>'fa-user-tie',2=>'fa-briefcase',3=>'fa-building',4=>'fa-crown'];

/**
 * Permisos individuales (override por usuario, cascada usuario > rol —
 * Fase 0 del sistema central de permisos). Aplana $treePermisosUsuario a
 * filas [nivel, nodo] igual que rol_permisos.php, con el nivel que le
 * daría el rol (referencia) y si hay una EXCEPCIÓN guardada encima.
 */
function permisosUsuario_flatten(array $tree): array {
    $rows = [];
    foreach ($tree as $modId => $mod) {
        $modRows = [];
        if ($mod['raiz']) $modRows[] = ['tipo' => 'nodo', 'lvl' => 1, 'n' => $mod['raiz']];
        foreach ($mod['areas'] as $area) {
            if ($area['nodo']) $modRows[] = ['tipo' => 'nodo', 'lvl' => 2, 'n' => $area['nodo']];
            foreach ($area['items'] as $item) {
                if ($item['nodo']) $modRows[] = ['tipo' => 'nodo', 'lvl' => 3, 'n' => $item['nodo']];
                foreach ($item['subitems'] as $sub) {
                    $modRows[] = ['tipo' => 'nodo', 'lvl' => 4, 'n' => $sub];
                }
            }
        }
        $rows[] = ['tipo' => 'modulo', 'modId' => $modId, 'mod' => $mod, 'total' => count($modRows)];
        array_push($rows, ...$modRows);
    }
    return $rows;
}
$filasPermisosUsuario = isset($treePermisosUsuario) ? permisosUsuario_flatten($treePermisosUsuario) : [];
$overridesActivos     = $overridesActivos ?? [];
$rolNivelMap           = $rolNivelMap ?? [];
$excepcionesActivas   = count($overridesActivos);

$rolesOld = isset($oldInput['roles']) ? array_map('intval', $oldInput['roles']) : $rolesAsignadosIds;
$assignedCount = count($rolesOld);
$nombreUsuario = $v('nombre_completo') ?: htmlspecialchars($usuario['cedula'] ?? '', ENT_QUOTES, 'UTF-8');
$iniciales = '';
foreach (array_slice(preg_split('/\s+/', trim($usuario['nombre_completo'] ?? '')), 0, 2) as $w) { $iniciales .= mb_strtoupper(mb_substr($w, 0, 1)); }
?>

<?php if (!empty($errors)): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.errorList('Corrige los siguientes errores', <?= json_encode(array_values($errors)) ?>));</script>
<?php endif; ?>

<div class="uform">

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-3);">
    <a href="<?= APP_URL ?>/admin/usuarios" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Usuarios
    </a>
</div>

<form method="POST" action="<?= $action ?>" id="usr-form" data-bypass>
<input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

<div class="gx uform-hero">
    <div class="uform-avatar"><?= htmlspecialchars($iniciales ?: '?', ENT_QUOTES, 'UTF-8') ?></div>
    <div class="uform-hero-body">
        <div class="uform-hero-name"><?= $nombreUsuario ?></div>
        <div class="uform-hero-meta">
            <code><?= htmlspecialchars($usuario['cedula'] ?? '', ENT_QUOTES, 'UTF-8') ?></code>
            <span class="sep"></span>
            <span><i class="fa-solid fa-building" style="opacity:.6;"></i> Talento Humano</span>
        </div>
    </div>
    <div class="uform-hero-stats">
        <div class="uform-stat"><b><?= $assignedCount ?></b><span>Rol<?= $assignedCount === 1 ? '' : 'es' ?></span></div>
        <div class="uform-stat"><b id="permu-count-badge"><?= $excepcionesActivas ?></b><span>Excepción<?= $excepcionesActivas === 1 ? '' : 'es' ?></span></div>
    </div>
</div>

<div class="uform-grid">
    <!-- Left: identidad + roles -->
    <div>
        <div class="gx uform-card">
            <div class="uform-card-head"><i class="fa-solid fa-id-card"></i> Datos de Acceso</div>
            <div class="uform-card-body">
                <div class="uform-2col">
                    <div class="form-group">
                        <label class="form-label">Cédula</label>
                        <input type="text" class="form-control" readonly
                               style="background:var(--g-bg-soft);cursor:not-allowed;"
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
        <div class="gx uform-card">
            <div class="uform-card-head">
                <i class="fa-solid fa-key"></i> Roles Asignados
                <span class="uform-badge" style="margin-left:auto;" id="roles-count-badge"><?= $assignedCount ?> asignado<?= $assignedCount !== 1 ? 's' : '' ?></span>
            </div>
            <div class="uform-card-body">
                <div class="uform-role-grid">
                    <?php foreach ($todosRoles as $r):
                        $checked = in_array((int)$r['id_rol'], $rolesOld, true);
                    ?>
                    <label class="uform-role-chip <?= $checked ? 'checked' : '' ?>">
                        <input type="checkbox" name="roles[]" value="<?= $r['id_rol'] ?>" <?= $checked ? 'checked' : '' ?> onchange="uformUpdateChip(this)">
                        <div style="flex:1;min-width:0;">
                            <div class="uform-role-name"><?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="uform-role-code"><code><?= htmlspecialchars($r['codigo'], ENT_QUOTES, 'UTF-8') ?></code></div>
                        </div>
                        <i class="fa-solid fa-check uform-role-tick"></i>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: configuración -->
    <div>
        <div class="gx uform-card">
            <div class="uform-card-head"><i class="fa-solid fa-sliders"></i> Configuración</div>
            <div class="uform-card-body">
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
                    <div style="display:flex;flex-direction:column;gap:6px;">
                        <?php foreach ($nivelOpts as $val => $lbl): ?>
                        <label class="uform-nivel-opt" style="border-color:<?= $nivelActual === $val ? $nivelColors[$val] : 'var(--g-bd)' ?>;
                                      background:<?= $nivelActual === $val ? "color-mix(in srgb,{$nivelColors[$val]} 8%,transparent)" : 'transparent' ?>;"
                               onclick="uformSelectNivel(<?= $val ?>)">
                            <input type="radio" name="nivel_jerarquia" value="<?= $val ?>"
                                   <?= $nivelActual === $val ? 'checked' : '' ?> style="display:none;">
                            <div class="uform-nivel-ico" style="background:color-mix(in srgb,<?= $nivelColors[$val] ?> 15%,transparent);color:<?= $nivelColors[$val] ?>;">
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
                        <option value="1" <?= (string)($oldInput['estado'] ?? $usuario['estado'] ?? 1) === '1' ? 'selected' : '' ?>>✓ Activo</option>
                        <option value="0" <?= (string)($oldInput['estado'] ?? $usuario['estado'] ?? 1) === '0' ? 'selected' : '' ?>>✗ Inactivo</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($filasPermisosUsuario)): ?>
<div class="gx uform-card" style="margin-top:var(--sp-4);">
    <div class="uform-card-head"><i class="fa-solid fa-user-shield"></i> Permisos Individuales (excepciones)</div>
    <div class="uform-card-body" style="padding-top:0;">
        <p class="uform-hint">
            Por defecto este usuario tiene los permisos de su(s) rol(es). Marcá una excepción acá SOLO para
            darle o quitarle acceso a una pantalla puntual — el resto sigue gobernado por el rol.
        </p>

        <div class="permu-toolbar">
            <div class="permu-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="permu-q" placeholder="Buscar por nombre…" oninput="permuSearch(this.value)" autocomplete="off">
            </div>
            <div class="permu-quick">
                <button type="button" onclick="permuClearAll()"><i class="fa-solid fa-rotate-left"></i> Quitar todas las excepciones</button>
            </div>
        </div>

        <div class="permu-table-wrap">
            <table class="permu-table" id="permu-table">
                <thead>
                    <tr>
                        <th class="col-nodo">Módulo / Pantalla</th>
                        <th class="col-chk">Ver</th>
                        <th class="col-chk">Crear</th>
                        <th class="col-chk">Editar</th>
                        <th class="col-chk">Total</th>
                        <th class="col-reset"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($filasPermisosUsuario as $f): ?>
                <?php if ($f['tipo'] === 'modulo'): $mod = $f['mod']; ?>
                    <tr class="permu-modrow" style="--mod-c:<?= $mod['color'] ?>;">
                        <td colspan="6">
                            <span class="modico"><i class="fa-solid <?= $mod['icon'] ?>"></i></span>
                            <?= htmlspecialchars($mod['label'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                    </tr>
                <?php else:
                    $n = $f['n']; $lvl = $f['lvl']; $key = $n['key'];
                    $activo      = array_key_exists($key, $overridesActivos);
                    $nivelRol    = (int)($rolNivelMap[$key] ?? 0);
                    $nivelActivo = $activo ? (int)$overridesActivos[$key] : $nivelRol;
                ?>
                    <tr class="permu-noderow <?= $activo ? 'is-override' : '' ?>"
                        data-permu-row="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
                        data-search="<?= strtolower(htmlspecialchars($n['descripcion'], ENT_QUOTES, 'UTF-8')) ?>"
                        data-rol-nivel="<?= $nivelRol ?>">
                        <td class="col-nodo">
                            <div class="pn-nodo" style="--lvl:<?= $lvl - 1 ?>;">
                                <span class="pn-dot l<?= $lvl ?>"></span>
                                <span class="pn-desc" title="<?= htmlspecialchars($n['descripcion'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($n['descripcion'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="pn-badge" data-role="badge"></span>
                            </div>
                            <input type="hidden" data-role="value" value="<?= $nivelActivo ?>">
                            <input type="hidden" data-role="active" value="<?= $activo ? 1 : 0 ?>">
                        </td>
                        <?php foreach ([1,2,3,4] as $val): ?>
                        <td class="col-chk" style="--step-c:var(--l<?= $val ?>);">
                            <label class="pn-cb c<?= $val ?>">
                                <input type="checkbox" data-lvl="<?= $val ?>" <?= $nivelActivo >= $val ? 'checked' : '' ?> onchange="permuToggle(this)">
                                <span class="box"><i class="fa-solid fa-check"></i></span>
                            </label>
                        </td>
                        <?php endforeach; ?>
                        <td class="col-reset">
                            <button type="button" class="permu-reset-btn" title="Quitar excepción, volver a heredar del rol" onclick="permuReset(this)"><i class="fa-solid fa-xmark"></i></button>
                        </td>
                    </tr>
                <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Barra de guardado — ÚNICA, pegada abajo, para toda la pantalla -->
<div class="uform-savebar">
    <div class="uform-savebar-hint" id="uform-dirty-hint" style="visibility:hidden;">
        <i class="fa-solid fa-circle-exclamation"></i> Cambios sin guardar
    </div>
    <a href="<?= APP_URL ?>/admin/usuarios" class="btn btn-ghost" data-spa>Cancelar</a>
    <button type="submit" class="btn btn-primary" id="uform-save-btn">
        <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
    </button>
</div>

</form>
</div>

<style>
.uform {
    --g-bg: var(--surface-app, var(--color-surface)); --g-bg-soft: var(--accent-app, var(--color-surface-2)); --g-bd: var(--border-app, var(--color-border));
    --l1: #8b5cf6; --l2: #3b82f6; --l3: #22c55e; --l4: #f59e0b;
    padding-bottom: 76px; /* espacio para la barra de guardado fija */
}
.uform .gx { background:var(--g-bg); border:1px solid var(--g-bd); border-radius:var(--radius-lg); box-shadow:var(--shadow-app, none); }

.uform-hero { display:flex; align-items:center; gap:var(--sp-4); padding:var(--sp-4) var(--sp-5); margin-bottom:var(--sp-4); }
.uform-avatar { width:48px; height:48px; border-radius:var(--radius-lg); flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:.95rem; font-weight:800; background:linear-gradient(135deg, var(--color-primary), color-mix(in srgb, var(--color-primary) 55%, #000)); color:#fff; }
.uform-hero-body { flex:1; min-width:0; }
.uform-hero-name { font-size:var(--font-size-xl); font-weight:var(--font-weight-bold); color:var(--color-text); letter-spacing:-.01em; }
.uform-hero-meta { display:flex; align-items:center; gap:var(--sp-3); margin-top:4px; font-size:var(--font-size-xs); color:var(--color-text-muted); }
.uform-hero-meta code { font-family:var(--font-mono); background:var(--g-bg-soft); border:1px solid var(--g-bd); padding:2px 7px; border-radius:var(--radius-sm); color:var(--color-primary); font-weight:600; }
.uform-hero-meta .sep { width:3px; height:3px; border-radius:50%; background:var(--color-text-light); }
.uform-hero-stats { display:flex; gap:var(--sp-5); flex-shrink:0; }
.uform-stat { text-align:right; }
.uform-stat b { display:block; font-size:var(--font-size-lg); font-weight:800; color:var(--color-text); }
.uform-stat span { font-size:10.5px; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:.05em; }

.uform-grid { display:grid; grid-template-columns:1fr 320px; gap:var(--sp-4); align-items:start; }
.uform-card { margin-bottom:var(--sp-4); }
.uform-card-head { display:flex; align-items:center; gap:8px; padding:12px var(--sp-4); font-weight:700; font-size:var(--font-size-sm); color:var(--color-text); border-bottom:1px solid var(--g-bd); }
.uform-card-head i { color:var(--color-primary); }
.uform-card-body { padding:var(--sp-4); }
.uform-2col { display:grid; grid-template-columns:1fr 1fr; gap:var(--sp-4); }
.uform-hint { font-size:var(--font-size-xs); color:var(--color-text-muted); line-height:1.5; margin:0 0 var(--sp-3); }
.uform-badge { font-size:10.5px; font-weight:700; padding:2px 9px; border-radius:var(--radius-full); background:color-mix(in srgb,var(--color-primary) 14%,transparent); color:var(--color-primary); }

.uform-role-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:8px; }
.uform-role-chip { position:relative; display:flex; align-items:center; gap:8px; padding:9px 11px; border:1.5px solid var(--g-bd); border-radius:var(--radius-md); cursor:pointer; transition:all .15s ease; background:transparent; }
.uform-role-chip:hover { border-color:color-mix(in srgb,var(--color-primary) 45%,var(--g-bd)); }
.uform-role-chip.checked { border-color:var(--color-primary); background:color-mix(in srgb,var(--color-primary) 7%,transparent); }
.uform-role-chip input { position:absolute; opacity:0; width:0; height:0; }
.uform-role-name { font-size:var(--font-size-sm); font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.uform-role-code code { font-size:10px; color:var(--color-text-muted); }
.uform-role-tick { font-size:9px; color:transparent; transition:color .15s ease; flex-shrink:0; }
.uform-role-chip.checked .uform-role-tick { color:var(--color-primary); }

.uform-nivel-opt { display:flex; align-items:center; gap:8px; padding:8px; border-radius:var(--radius-md); cursor:pointer; border:1.5px solid var(--g-bd); transition:all .15s ease; }
.uform-nivel-ico { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.68rem; flex-shrink:0; }

/* Barra de guardado unica, fija abajo */
.uform-savebar { position:sticky; bottom:0; z-index:20; display:flex; align-items:center; gap:var(--sp-3); justify-content:flex-end; padding:12px var(--sp-4); margin-top:var(--sp-4); background:var(--g-bg); border:1px solid var(--g-bd); border-radius:var(--radius-lg); box-shadow:0 -4px 16px -8px rgba(0,0,0,.15); }
.uform-savebar-hint { margin-right:auto; font-size:var(--font-size-xs); color:#f59e0b; font-weight:600; display:flex; align-items:center; gap:6px; }

/* ── Permisos individuales (checklist estilo roles) ── */
.permu-toolbar { display:flex; align-items:center; gap:var(--sp-4); flex-wrap:wrap; margin-bottom:var(--sp-3); }
.permu-search { position:relative; flex:1; min-width:220px; max-width:320px; }
.permu-search i { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:var(--color-text-light); font-size:11px; }
.permu-search input { width:100%; padding:8px 11px 8px 2rem; border-radius:var(--radius-md); border:1px solid var(--g-bd); background:var(--g-bg-soft); color:var(--color-text); font-size:var(--font-size-sm); }
.permu-search input:focus { outline:none; border-color:var(--color-primary); box-shadow:0 0 0 3px color-mix(in srgb,var(--color-primary) 16%,transparent); }
.permu-quick { margin-left:auto; }
.permu-quick button { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:var(--radius-md); border:1px solid var(--g-bd); background:transparent; color:var(--color-text-muted); font-size:var(--font-size-xs); font-weight:600; cursor:pointer; }
.permu-quick button:hover { color:var(--color-text); background:var(--g-bg-soft); }

.permu-table-wrap { overflow-x:auto; border:1px solid var(--g-bd); border-radius:var(--radius-md); }
table.permu-table { width:100%; border-collapse:separate; border-spacing:0; font-size:var(--font-size-sm); }
table.permu-table thead th { background:var(--g-bg-soft); border-bottom:1px solid var(--g-bd); padding:9px 8px; font-size:10.5px; font-weight:700; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:.06em; white-space:nowrap; }
table.permu-table th.col-nodo { text-align:left; padding-left:var(--sp-4); }
table.permu-table th.col-chk { text-align:center; width:64px; }
table.permu-table th.col-reset { width:36px; }

tr.permu-modrow td { padding:8px 12px 8px 14px; font-weight:700; font-size:12px; color:var(--color-text); background:var(--g-bg-soft); border-left:3px solid var(--mod-c, var(--color-primary)); border-bottom:1px solid var(--g-bd); border-top:1px solid var(--g-bd); }
tr.permu-modrow:first-child td { border-top:none; }
tr.permu-modrow .modico { display:inline-flex; align-items:center; justify-content:center; width:19px; height:19px; border-radius:6px; background:color-mix(in srgb, var(--mod-c, var(--color-primary)) 16%, transparent); color:var(--mod-c, var(--color-primary)); margin-right:8px; font-size:.62rem; }

tr.permu-noderow td { border-bottom:1px solid var(--g-bd); padding:5px 8px; vertical-align:middle; }
tr.permu-noderow:hover td { background:color-mix(in srgb, var(--g-bg-soft) 65%, transparent); }
tr.permu-noderow.is-override td.col-nodo { border-left:2px solid #f59e0b; }
tr.permu-noderow.is-override td:first-child { padding-left:6px; }
tr.permu-noderow:not(.is-override) .pn-cb .box { opacity:.45; }
tr.permu-noderow:not(.is-override) .permu-reset-btn { visibility:hidden; }

.pn-nodo { display:flex; align-items:center; gap:8px; padding-left:calc(var(--lvl) * 16px); min-height:22px; }
.pn-dot { width:6px; height:6px; border-radius:50%; flex-shrink:0; }
.pn-dot.l1 { background:var(--l1); } .pn-dot.l2 { background:var(--l2); } .pn-dot.l3 { background:var(--l3); } .pn-dot.l4 { background:var(--l4); }
.pn-desc { color:var(--color-text); font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:280px; }
.pn-badge { margin-left:auto; font-size:9px; font-weight:600; padding:2px 7px; border-radius:var(--radius-full); white-space:nowrap; flex-shrink:0; }

td.col-chk { text-align:center; background:color-mix(in srgb, var(--g-bg-soft) 55%, transparent); border-left:1px solid var(--g-bd); }
td.col-reset { text-align:center; }
.pn-cb { position:relative; display:inline-flex; width:17px; height:17px; }
.pn-cb input { position:absolute; opacity:0; width:100%; height:100%; margin:0; cursor:pointer; z-index:1; }
.pn-cb .box { position:absolute; inset:0; border:1.5px solid var(--g-bd); border-radius:5px; background:var(--g-bg); transition:all .12s ease; display:flex; align-items:center; justify-content:center; }
.pn-cb .box i { font-size:9px; color:#fff; opacity:0; transform:scale(.4); transition:all .15s ease; }
.pn-cb input:checked ~ .box i { opacity:1; transform:scale(1); }
.pn-cb.c1 input:checked ~ .box { background:var(--l1); border-color:var(--l1); }
.pn-cb.c2 input:checked ~ .box { background:var(--l2); border-color:var(--l2); }
.pn-cb.c3 input:checked ~ .box { background:var(--l3); border-color:var(--l3); }
.pn-cb.c4 input:checked ~ .box { background:var(--l4); border-color:var(--l4); }

.permu-reset-btn { border:none; background:transparent; color:var(--color-text-light); cursor:pointer; width:22px; height:22px; border-radius:5px; display:inline-flex; align-items:center; justify-content:center; font-size:11px; }
.permu-reset-btn:hover { color:#dc3545; background:color-mix(in srgb, #dc3545 10%, transparent); }

@media (max-width: 860px) {
    .uform-grid { grid-template-columns:1fr; }
    .pn-desc { max-width:150px; }
}
</style>

<script>
// var, no const/let: esta vista puede re-ejecutar su <script> inline si se
// revisita por SPA-nav (data-spa) sin reload completo -- const/let a nivel
// superior revienta "Identifier already declared" en la segunda pasada
// (mismo bug ya documentado y corregido en /admin/inactividad).
var PERMU_LABELS = {0:'Sin acceso',1:'Ver',2:'Crear',3:'Editar',4:'Total'};
var PERMU_COLORS = {0:['var(--color-text-muted)','transparent'],1:['var(--l1)','color-mix(in srgb,var(--l1) 12%,transparent)'],2:['var(--l2)','color-mix(in srgb,var(--l2) 12%,transparent)'],3:['var(--l3)','color-mix(in srgb,var(--l3) 12%,transparent)'],4:['var(--l4)','color-mix(in srgb,var(--l4) 14%,transparent)']};

function uformMarkDirty() {
    var hint = document.getElementById('uform-dirty-hint');
    if (hint) hint.style.visibility = 'visible';
}

function uformSelectNivel(val) {
    document.querySelectorAll('[name="nivel_jerarquia"]').forEach(function (radio) {
        var lbl = radio.closest('.uform-nivel-opt');
        var ico = lbl.querySelector('.uform-nivel-ico');
        var color = ico.style.color;
        if (parseInt(radio.value) === val) {
            radio.checked = true;
            lbl.style.borderColor = color;
            lbl.style.background = 'color-mix(in srgb,' + color + ' 8%, transparent)';
        } else {
            radio.checked = false;
            lbl.style.borderColor = 'var(--g-bd)';
            lbl.style.background = 'transparent';
        }
    });
    uformMarkDirty();
}
function uformUpdateChip(cb) {
    cb.closest('.uform-role-chip').classList.toggle('checked', cb.checked);
    var badge = document.getElementById('roles-count-badge');
    var n = document.querySelectorAll('input[name="roles[]"]:checked').length;
    if (badge) badge.textContent = n + ' asignado' + (n === 1 ? '' : 's');
    document.querySelectorAll('.uform-stat b')[0].textContent = n;
    uformMarkDirty();
}

// Permisos individuales -- interacción directa igual que el checklist de
// roles: tocar un checkbox EN SÍ crea la excepción (no hace falta un
// interruptor "Aplicar" aparte). "Quitar excepción" (el ×) vuelve la fila a
// heredar del rol y la saca del submit. Solo las filas marcadas is-override
// viajan -- una fila jamás tocada nunca crea una excepción.
function permuRow(el) { return el.closest('tr.permu-noderow'); }

function permuApplyRow(tr, value, isOverride) {
    value = Math.max(0, Math.min(4, value));
    tr.querySelectorAll('td.col-chk input[type=checkbox]').forEach(function (cb) {
        cb.checked = parseInt(cb.dataset.lvl) <= value;
    });
    tr.querySelector('[data-role="value"]').value = value;
    tr.querySelector('[data-role="active"]').value = isOverride ? 1 : 0;
    tr.classList.toggle('is-override', isOverride);
    var badge = tr.querySelector('[data-role="badge"]');
    var rolNivel = parseInt(tr.dataset.rolNivel || '0');
    if (isOverride) {
        badge.textContent = 'Excepción: ' + PERMU_LABELS[value];
        badge.style.color = PERMU_COLORS[value][0];
        badge.style.background = PERMU_COLORS[value][1];
    } else {
        badge.textContent = 'Hereda: ' + PERMU_LABELS[rolNivel];
        badge.style.color = 'var(--color-text-muted)';
        badge.style.background = 'transparent';
    }
}

window.permuToggle = function (checkbox) {
    var tr = permuRow(checkbox);
    var lvl = parseInt(checkbox.dataset.lvl);
    var newValue = checkbox.checked ? lvl : (lvl - 1);
    permuApplyRow(tr, newValue, true);
    permuUpdateCount();
    uformMarkDirty();
};

window.permuReset = function (btn) {
    var tr = permuRow(btn);
    var rolNivel = parseInt(tr.dataset.rolNivel || '0');
    permuApplyRow(tr, rolNivel, false);
    permuUpdateCount();
    uformMarkDirty();
};

window.permuClearAll = function () {
    document.querySelectorAll('tr.permu-noderow.is-override').forEach(function (tr) {
        permuApplyRow(tr, parseInt(tr.dataset.rolNivel || '0'), false);
    });
    permuUpdateCount();
    uformMarkDirty();
};

function permuUpdateCount() {
    var n = document.querySelectorAll('tr.permu-noderow.is-override').length;
    var els = document.querySelectorAll('#permu-count-badge');
    els.forEach(function (el) { el.textContent = n; });
}

window.permuSearch = function (q) {
    q = q.toLowerCase().trim();
    var currentModTr = null, anyInMod = false;
    document.querySelectorAll('#permu-table tbody tr').forEach(function (tr) {
        if (tr.classList.contains('permu-modrow')) {
            if (currentModTr) currentModTr.style.display = anyInMod || !q ? '' : 'none';
            currentModTr = tr; anyInMod = false; return;
        }
        var hit = !q || (tr.dataset.search || '').includes(q);
        tr.style.display = hit ? '' : 'none';
        if (hit) anyInMod = true;
    });
    if (currentModTr) currentModTr.style.display = anyInMod || !q ? '' : 'none';
};

// Guardado ÚNICO: un solo submit, AJAX, todo junto (datos + roles + excepciones).
document.getElementById('usr-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var btn = document.getElementById('uform-save-btn');
    var original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando…';

    var form = this;
    var body = new URLSearchParams();
    body.set('_csrf_token', <?= json_encode($csrf) ?>);
    body.set('nombre_completo', form.nombre_completo.value);
    body.set('correo', form.correo.value);
    body.set('id_departamento', form.id_departamento.value);
    body.set('nivel_jerarquia', form.nivel_jerarquia.value);
    body.set('estado', form.estado.value);
    form.querySelectorAll('input[name="roles[]"]:checked').forEach(function (cb) { body.append('roles[]', cb.value); });
    document.querySelectorAll('#permu-table [data-permu-row]').forEach(function (tr) {
        if (tr.querySelector('[data-role="active"]').value !== '1') return;
        var key = tr.getAttribute('data-permu-row');
        var nivel = tr.querySelector('[data-role="value"]').value;
        body.set('overrides[' + key + ']', nivel);
    });

    fetch(<?= json_encode($action) ?>, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: body.toString(),
    }).then(function (r) { return r.json(); }).then(function (d) {
        btn.disabled = false;
        btn.innerHTML = original;
        if (d.ok) {
            var hint = document.getElementById('uform-dirty-hint');
            if (hint) hint.style.visibility = 'hidden';
            PortalAlert.success(d.msg || 'Usuario guardado correctamente.');
        } else {
            PortalAlert.error(d.msg || 'No se pudo guardar el usuario.');
        }
    }).catch(function (err) {
        btn.disabled = false;
        btn.innerHTML = original;
        console.error('Error guardando usuario:', err);
        PortalAlert.error('Error de conexión al guardar.');
    });
});

document.querySelectorAll('#usr-form input, #usr-form select').forEach(function (el) {
    el.addEventListener('change', uformMarkDirty);
});

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#permu-table tr.permu-noderow').forEach(function (tr) {
        var active = tr.querySelector('[data-role="active"]').value === '1';
        var value = parseInt(tr.querySelector('[data-role="value"]').value);
        permuApplyRow(tr, value, active);
    });
});
</script>
