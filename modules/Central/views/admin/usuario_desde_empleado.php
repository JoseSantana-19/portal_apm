<?php
$errors   = $_SESSION['_form_errors'] ?? [];
$oldInput = $_SESSION['_old_input']   ?? [];
unset($_SESSION['_form_errors'], $_SESSION['_old_input']);
$v = fn(string $k, string $default = '') => htmlspecialchars($oldInput[$k] ?? $default, ENT_QUOTES, 'UTF-8');

$sugDepto        = $sugerido['id_departamento'] ?? null;
$sugRolAnalista  = $sugerido['id_rol_analista'] ?? null;
$sugRolDirector  = $sugerido['id_rol_director'] ?? null;
$nivelOpts   = [0=>'Operativo',1=>'Analista',2=>'Jefatura',3=>'Director',4=>'Super Admin'];

$initials = mb_strtoupper(implode('', array_map(
    fn($w) => mb_substr($w, 0, 1),
    array_slice(explode(' ', trim($empleado['nombre_completo'])), 0, 2)
)));
?>
<style>
.nue-wrap {
    --g-bg: var(--surface-app); --g-bg-soft: var(--accent-app); --g-bd: var(--border-app);
    --g-shadow: var(--shadow-app);
}
.nue-wrap .gx { background:var(--g-bg); border:1px solid var(--g-bd); border-radius:var(--radius-lg); box-shadow:var(--g-shadow); }
.gx-head { display:flex; align-items:center; gap:var(--sp-2); padding:var(--sp-3) var(--sp-4); border-bottom:1px solid var(--g-bd); font-weight:var(--font-weight-semibold); font-size:var(--font-size-sm); color:var(--color-text); }
.gx-head i { color:var(--color-primary); font-size:.8rem; }
.gx-body { padding:var(--sp-4); }

.emp-banner { display:flex; align-items:center; gap:var(--sp-3); padding:var(--sp-4); margin-bottom:var(--sp-4); }
.emp-banner-avatar { width:52px; height:52px; border-radius:var(--radius-full); background:linear-gradient(135deg,var(--color-primary),color-mix(in srgb,var(--color-primary) 60%,#000)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:var(--font-weight-bold); font-size:var(--font-size-lg); flex-shrink:0; }
.emp-banner-name { font-size:var(--font-size-lg); font-weight:var(--font-weight-bold); color:var(--color-text); }
.emp-banner-meta { font-size:var(--font-size-xs); color:var(--color-text-muted); margin-top:2px; display:flex; gap:var(--sp-3); flex-wrap:wrap; }
.emp-banner-meta code { font-family:var(--font-mono); background:var(--g-bg-soft); padding:1px 6px; border-radius:var(--radius-sm); }

.pwd-row { display:flex; gap:var(--sp-2); }
.pwd-row .form-control { font-family:var(--font-mono); }

.nue-preview { font-size:var(--font-size-xs); }
.pv-row { display:flex; justify-content:space-between; gap:var(--sp-2); padding:6px 0; border-bottom:1px solid var(--g-bd); }
.pv-row:last-child { border-bottom:none; }
.pv-k { color:var(--color-text-muted); }
.pv-v { color:var(--color-text); font-weight:var(--font-weight-medium); text-align:right; max-width:60%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

.nue-grid { display:grid; grid-template-columns:1fr 320px; gap:var(--sp-5); align-items:start; }
@media (max-width:880px) { .nue-grid { grid-template-columns:1fr; } }
</style>

<div class="nue-wrap">

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-2);">
    <a href="<?= APP_URL ?>/admin/usuarios/desde-th" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Elegir otro empleado
    </a>
</div>

<div style="margin-bottom:var(--sp-4);">
    <div class="nu-eyebrow" style="font-size:var(--font-size-xs);font-weight:var(--font-weight-bold);letter-spacing:.14em;text-transform:uppercase;color:var(--color-primary);display:flex;align-items:center;gap:var(--sp-2);margin-bottom:var(--sp-1);">
        <span style="width:22px;height:2px;background:var(--color-primary);border-radius:2px;display:inline-block;"></span>
        Administración · Nuevo Usuario
    </div>
    <h2 class="page-title" style="margin:0;">Crear cuenta de acceso</h2>
</div>

<div class="gx emp-banner">
    <div class="emp-banner-avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
    <div>
        <div class="emp-banner-name"><?= htmlspecialchars($empleado['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="emp-banner-meta">
            <span><i class="fa-solid fa-id-card"></i> <code><?= htmlspecialchars($empleado['cedula'] ?? '', ENT_QUOTES, 'UTF-8') ?></code></span>
            <span><i class="fa-solid fa-sitemap"></i> <?= htmlspecialchars($empleado['nombre_unidad'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.errorList('Corrige los siguientes errores', <?= json_encode(array_values($errors)) ?>));</script>
<?php endif; ?>

<?php if (!$sugerido): ?>
<div class="alert alert-warning" style="margin-bottom:var(--sp-4);">
    <i class="fa-solid fa-circle-info"></i>
    Esta unidad organizacional no tiene departamento/rol autosugerido en <code>TH_Unidad_Map</code>. Elegí manualmente abajo.
</div>
<?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/admin/usuarios/desde-th" id="nue-form">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id_empleado_th" value="<?= (int)$empleado['empleado_id'] ?>">

    <div class="nue-grid">
        <div style="display:flex;flex-direction:column;gap:var(--sp-4);">

            <div class="gx">
                <div class="gx-head"><i class="fa-solid fa-id-card"></i> Acceso</div>
                <div class="gx-body" style="display:flex;flex-direction:column;gap:var(--sp-4);">

                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Cédula (login)</label>
                        <input type="text" class="form-control" readonly
                               style="background:var(--color-surface-2);cursor:not-allowed;font-family:var(--font-mono);"
                               value="<?= htmlspecialchars($empleado['cedula'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <span class="form-help">No se pide "nombre de usuario" — el login es únicamente por cédula, tomada de Talento Humano.</span>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Correo</label>
                        <input type="email" name="correo" id="f-correo" class="form-control" maxlength="150"
                               value="<?= $v('correo', $empleado['correo_institucional'] ?? '') ?>">
                        <span class="form-help">Por defecto, el correo institucional de Talento Humano</span>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Contraseña Temporal *</label>
                        <div class="pwd-row">
                            <div style="position:relative;flex:1;">
                                <input type="text" name="contrasena" id="f-pwd" class="form-control" required minlength="8"
                                       placeholder="Mínimo 8 caracteres" oninput="pintarPreview()">
                            </div>
                            <button type="button" class="btn btn-outline" onclick="generarPassword()">
                                <i class="fa-solid fa-dice"></i> Generar
                            </button>
                            <button type="button" class="btn btn-ghost" id="btn-copiar" onclick="copiarPassword()" title="Copiar">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                        <span class="form-help">Visible en texto plano para que se la comuniques — el usuario debe cambiarla en su primer ingreso.</span>
                    </div>

                </div>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:var(--sp-4);">

            <div class="gx">
                <div class="gx-head"><i class="fa-solid fa-sliders"></i> Departamento y Rol</div>
                <div class="gx-body" style="display:flex;flex-direction:column;gap:var(--sp-4);">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Departamento *</label>
                        <select name="id_departamento" id="f-depto" class="form-control" required onchange="pintarPreview()">
                            <option value="">Seleccione…</option>
                            <?php foreach ($deptos as $d): ?>
                            <option value="<?= $d['id_departamento'] ?>"
                                <?= (string)($oldInput['id_departamento'] ?? $sugDepto ?? '') === (string)$d['id_departamento'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($sugDepto): ?><span class="form-help"><i class="fa-solid fa-wand-magic-sparkles"></i> Sugerido por su unidad organizacional</span><?php endif; ?>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Rol *</label>
                        <select name="id_rol" id="f-rol" class="form-control" required onchange="pintarPreview()">
                            <option value="">Seleccione…</option>
                            <?php foreach ($todosRoles as $r):
                                $isSugAnalista = $sugRolAnalista && (int)$r['id_rol'] === (int)$sugRolAnalista;
                                $isSugDirector = $sugRolDirector && (int)$r['id_rol'] === (int)$sugRolDirector;
                                $selected = (string)($oldInput['id_rol'] ?? $sugRolAnalista ?? '') === (string)$r['id_rol'];
                            ?>
                            <option value="<?= $r['id_rol'] ?>" data-nombre="<?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?>" <?= $selected ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?><?= $isSugAnalista ? ' (sugerido — analista)' : ($isSugDirector ? ' (sugerido — jefatura)' : '') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Nivel Jerárquico *</label>
                        <select name="nivel_jerarquia" id="f-nivel" class="form-control" required onchange="pintarPreview()">
                            <?php foreach ($nivelOpts as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= (string)($oldInput['nivel_jerarquia'] ?? '1') === (string)$val ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="gx">
                <div class="gx-head"><i class="fa-solid fa-eye"></i> Vista previa</div>
                <div class="gx-body nue-preview">
                    <div class="pv-row"><span class="pv-k">Nombre</span><span class="pv-v"><?= htmlspecialchars($empleado['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div class="pv-row"><span class="pv-k">Login (cédula)</span><span class="pv-v"><?= htmlspecialchars($empleado['cedula'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div class="pv-row"><span class="pv-k">Correo</span><span class="pv-v" id="pv-correo">—</span></div>
                    <div class="pv-row"><span class="pv-k">Departamento</span><span class="pv-v" id="pv-depto">—</span></div>
                    <div class="pv-row"><span class="pv-k">Rol</span><span class="pv-v" id="pv-rol">—</span></div>
                    <div class="pv-row"><span class="pv-k">Contraseña</span><span class="pv-v" id="pv-pwd" style="font-family:var(--font-mono);">—</span></div>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:var(--sp-2);">
                <button type="submit" class="btn btn-primary" style="justify-content:center;">
                    <i class="fa-solid fa-floppy-disk"></i> Crear cuenta
                </button>
                <a href="<?= APP_URL ?>/admin/usuarios/desde-th" class="btn btn-ghost" data-spa style="justify-content:center;">Cancelar</a>
            </div>
        </div>
    </div>
</form>
</div>

<script>
function generarPassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    const arr = new Uint32Array(12);
    crypto.getRandomValues(arr);
    let pwd = '';
    for (let i = 0; i < 12; i++) pwd += chars[arr[i] % chars.length];
    document.getElementById('f-pwd').value = pwd;
    pintarPreview();
}
function copiarPassword() {
    const f = document.getElementById('f-pwd');
    if (!f.value) return;
    navigator.clipboard?.writeText(f.value);
    const btn = document.getElementById('btn-copiar');
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-check" style="color:var(--color-success);"></i>';
    setTimeout(() => { btn.innerHTML = orig; }, 1200);
}
function pintarPreview() {
    const correo = document.getElementById('f-correo').value || '—';
    const depto  = document.getElementById('f-depto');
    const rol    = document.getElementById('f-rol');
    const pwd    = document.getElementById('f-pwd').value;
    document.getElementById('pv-correo').textContent = correo;
    document.getElementById('pv-depto').textContent  = depto.selectedIndex > 0 ? depto.options[depto.selectedIndex].text : '—';
    document.getElementById('pv-rol').textContent    = rol.selectedIndex > 0 ? (rol.options[rol.selectedIndex].dataset.nombre || rol.options[rol.selectedIndex].text) : '—';
    document.getElementById('pv-pwd').textContent     = pwd ? '•'.repeat(Math.min(pwd.length, 14)) : '—';
}
document.addEventListener('DOMContentLoaded', pintarPreview);
</script>
