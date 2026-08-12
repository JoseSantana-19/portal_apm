<?php
$errors   = $_SESSION['_form_errors'] ?? [];
$oldInput = $_SESSION['_old_input']   ?? [];
unset($_SESSION['_form_errors'], $_SESSION['_old_input']);
$v = fn(string $k, string $default = '') => htmlspecialchars($oldInput[$k] ?? $default, ENT_QUOTES, 'UTF-8');
$h = fn($val) => htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');

$sugDepto        = $sugerido['id_departamento'] ?? null;
$sugRolAnalista  = $sugerido['id_rol_analista'] ?? null;
$sugRolDirector  = $sugerido['id_rol_director'] ?? null;
$nivelOpts   = [0=>'Operativo',1=>'Analista',2=>'Jefatura',3=>'Director',4=>'Super Admin'];

$initials = mb_strtoupper(implode('', array_map(
    fn($w) => mb_substr($w, 0, 1),
    array_slice(explode(' ', trim($empleado['nombre_completo'])), 0, 2)
)));

$telefono = trim((string)($empleado['telefono_movil'] ?? '')) ?: trim((string)($empleado['telefono_convencional'] ?? ''));
$fechaIngresoFmt = null;
if (!empty($empleado['fecha_ingreso'])) {
    $ts = strtotime((string)$empleado['fecha_ingreso']);
    if ($ts !== false) $fechaIngresoFmt = date('d \d\e F \d\e Y', $ts);
}
$empActivo = (int)($empleado['estado'] ?? 0) === 1;
?>
<style>
.nue-wrap {
    --g-bg: var(--surface-app); --g-soft: var(--accent-app); --g-bd: var(--border-app);
    --g-shadow: var(--shadow-app); --g-ink: var(--color-text); --g-muted: var(--color-text-muted);
    max-width: 1080px; margin: 0 auto;
}

/* ── Progreso de 2 pasos: reemplaza el simple link "volver" ─────────── */
.nue-steps { display:flex; align-items:center; gap:var(--sp-2); margin-bottom:var(--sp-5); }
.nue-step { display:flex; align-items:center; gap:8px; font-size:var(--font-size-xs); font-weight:var(--font-weight-semibold); color:var(--g-muted); }
.nue-step-dot { width:22px; height:22px; border-radius:var(--radius-full); display:flex; align-items:center; justify-content:center; font-size:.65rem; flex-shrink:0; border:1.5px solid var(--g-bd); }
.nue-step.done .nue-step-dot { background:var(--color-success); border-color:var(--color-success); color:#fff; }
.nue-step.done { color:var(--g-ink); }
.nue-step.active .nue-step-dot { background:var(--color-primary); border-color:var(--color-primary); color:#fff; }
.nue-step.active { color:var(--g-ink); }
.nue-step-line { width:32px; height:1.5px; background:var(--g-bd); flex-shrink:0; }
.nue-step-line.done { background:var(--color-success); }
.nue-steps a.nue-step { text-decoration:none; }

/* ── Credencial del colaborador ──────────────────────────────────────── */
.credential { background:var(--g-bg); border:1px solid var(--g-bd); border-radius:var(--radius-lg); box-shadow:var(--g-shadow); margin-bottom:var(--sp-5); position:relative; overflow:hidden; }
.credential::before { content:''; position:absolute; inset:0 0 auto 0; height:3px; background:linear-gradient(90deg,var(--color-primary),color-mix(in srgb,var(--color-primary) 25%,transparent) 85%); }

.cred-head { display:flex; align-items:flex-start; gap:var(--sp-4); padding:var(--sp-5) var(--sp-5) var(--sp-4); flex-wrap:wrap; }
.cred-photo, .cred-avatar { width:96px; height:96px; border-radius:var(--radius-full); flex-shrink:0; box-shadow:0 0 0 4px var(--g-soft); }
.cred-photo { object-fit:cover; }
.cred-avatar { background:linear-gradient(150deg,var(--color-primary),color-mix(in srgb,var(--color-primary) 55%,#000)); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:var(--font-weight-bold); font-size:2.1rem; letter-spacing:.02em; }

.cred-id { flex:1; min-width:220px; padding-top:2px; }
.cred-name { font-size:1.6rem; font-weight:800; color:var(--g-ink); line-height:1.15; letter-spacing:-.01em; }
.cred-cargo { font-size:.8rem; font-weight:var(--font-weight-semibold); color:var(--color-primary); text-transform:uppercase; letter-spacing:.05em; margin-top:5px; }
.cred-unidad { font-size:var(--font-size-sm); color:var(--g-muted); margin-top:2px; }
.cred-status { display:inline-flex; align-items:center; gap:6px; font-size:.68rem; font-weight:var(--font-weight-bold); text-transform:uppercase; letter-spacing:.05em; padding:4px 11px; border-radius:var(--radius-full); margin-top:var(--sp-3); }
.cred-status.on { background:color-mix(in srgb,var(--color-success) 15%,transparent); color:var(--color-success); }
.cred-status.off { background:color-mix(in srgb,var(--color-danger) 15%,transparent); color:var(--color-danger); }
.cred-status i { font-size:.5rem; }

/* Sello de verificación — elemento de firma de esta pantalla */
.cred-seal { flex-shrink:0; display:flex; flex-direction:column; align-items:center; justify-content:center; width:84px; height:84px; border-radius:var(--radius-full); border:2px dashed color-mix(in srgb,var(--color-success) 55%,var(--g-bd)); color:var(--color-success); text-align:center; padding:6px; }
.cred-seal i { font-size:1.15rem; margin-bottom:2px; }
.cred-seal span { font-size:.52rem; font-weight:var(--font-weight-bold); text-transform:uppercase; letter-spacing:.04em; line-height:1.2; }

.cred-divider { height:1px; background:var(--g-bd); margin:0 var(--sp-5); }

.cred-body { display:grid; grid-template-columns:1fr 1fr; gap:0; }
.cred-col { padding:var(--sp-4) var(--sp-5); }
.cred-col + .cred-col { border-left:1px solid var(--g-bd); }
.cred-col-title { font-size:.65rem; font-weight:var(--font-weight-bold); text-transform:uppercase; letter-spacing:.08em; color:var(--g-muted); margin-bottom:var(--sp-3); display:flex; align-items:center; gap:6px; }
.cred-col-title i { color:var(--color-primary); }
.cred-fact { display:flex; justify-content:space-between; gap:var(--sp-3); padding:6px 0; font-size:var(--font-size-sm); }
.cred-fact-k { color:var(--g-muted); }
.cred-fact-v { color:var(--g-ink); font-weight:var(--font-weight-medium); text-align:right; }
.cred-fact-v code { font-family:var(--font-mono); background:var(--g-soft); padding:1px 6px; border-radius:var(--radius-sm); font-size:.85em; }
@media (max-width:700px) { .cred-body { grid-template-columns:1fr; } .cred-col + .cred-col { border-left:none; border-top:1px solid var(--g-bd); } }

/* ── Paneles del formulario ──────────────────────────────────────────── */
.nue-panel { background:var(--g-bg); border:1px solid var(--g-bd); border-radius:var(--radius-lg); box-shadow:var(--g-shadow); }
.nue-panel-head { display:flex; align-items:center; gap:var(--sp-2); padding:var(--sp-3) var(--sp-4); border-bottom:1px solid var(--g-bd); font-weight:var(--font-weight-bold); font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; color:var(--g-ink); }
.nue-panel-head i { color:var(--color-primary); font-size:.8rem; }
.nue-panel-body { padding:var(--sp-4); display:flex; flex-direction:column; gap:var(--sp-4); }

.th-auto-badge { display:inline-flex; align-items:center; gap:4px; font-size:.63rem; font-weight:var(--font-weight-bold); text-transform:uppercase; letter-spacing:.04em; color:var(--color-success); background:color-mix(in srgb,var(--color-success) 12%,transparent); padding:2px 8px; border-radius:var(--radius-full); margin-left:var(--sp-2); }
.th-auto-field { border:1px solid color-mix(in srgb,var(--color-success) 35%,var(--g-bd)) !important; background:color-mix(in srgb,var(--color-success) 5%,var(--g-bg)) !important; }

.pwd-row { display:flex; gap:var(--sp-2); }
.pwd-row .form-control { font-family:var(--font-mono); }

.nue-summary { display:flex; flex-wrap:wrap; gap:var(--sp-4); align-items:center; justify-content:space-between; background:var(--g-soft); border:1px solid var(--g-bd); border-radius:var(--radius-lg); padding:var(--sp-4) var(--sp-5); margin-top:var(--sp-5); }
.nue-summary-facts { display:flex; flex-wrap:wrap; gap:var(--sp-5); }
.nue-summary-item { font-size:var(--font-size-xs); }
.nue-summary-item .k { color:var(--g-muted); text-transform:uppercase; letter-spacing:.05em; font-size:.62rem; display:block; margin-bottom:2px; }
.nue-summary-item .val { color:var(--g-ink); font-weight:var(--font-weight-semibold); font-size:var(--font-size-sm); }
.nue-summary-actions { display:flex; gap:var(--sp-2); }

.nue-grid { display:grid; grid-template-columns:1fr 1fr; gap:var(--sp-5); align-items:start; }
@media (max-width:820px) { .nue-grid { grid-template-columns:1fr; } }
</style>

<div class="nue-wrap">

<!-- Progreso: seleccionar colaborador (hecho) → configurar cuenta (actual) -->
<div class="nue-steps">
    <a href="<?= APP_URL ?>/admin/usuarios/desde-th" class="nue-step done" data-spa>
        <span class="nue-step-dot"><i class="fa-solid fa-check"></i></span>
        Seleccionar colaborador
    </a>
    <span class="nue-step-line done"></span>
    <span class="nue-step active">
        <span class="nue-step-dot">2</span>
        Configurar cuenta
    </span>
</div>

<div style="margin-bottom:var(--sp-4);">
    <h2 class="page-title" style="margin:0;">Nueva cuenta de acceso al Portal APM</h2>
    <p class="page-subtitle" style="margin-top:4px;">Complete la información requerida para habilitar el acceso institucional del colaborador seleccionado.</p>
</div>

<!-- Credencial — ficha verificada de Talento Humano, solo lectura -->
<div class="credential">
    <div class="cred-head">
        <?php if ($fotoUrl): ?>
            <img src="<?= $h($fotoUrl) ?>" alt="" class="cred-photo">
        <?php else: ?>
            <div class="cred-avatar"><?= $h($initials) ?></div>
        <?php endif; ?>
        <div class="cred-id">
            <div class="cred-name"><?= $h($empleado['nombre_completo']) ?></div>
            <?php if (!empty($empleado['cargo'])): ?>
            <div class="cred-cargo"><?= $h($empleado['cargo']) ?></div>
            <?php endif; ?>
            <?php if (!empty($empleado['nombre_unidad'])): ?>
            <div class="cred-unidad"><i class="fa-solid fa-sitemap" style="margin-right:4px;opacity:.7;"></i><?= $h($empleado['nombre_unidad']) ?></div>
            <?php endif; ?>
            <div class="cred-status <?= $empActivo ? 'on' : 'off' ?>">
                <i class="fa-solid fa-circle"></i> <?= $empActivo ? 'Activo en Talento Humano' : 'Inactivo en Talento Humano' ?>
            </div>
        </div>
        <div class="cred-seal">
            <i class="fa-solid fa-stamp"></i>
            <span>Verificado<br>Talento Humano</span>
        </div>
    </div>

    <div class="cred-divider"></div>

    <div class="cred-body">
        <div class="cred-col">
            <div class="cred-col-title"><i class="fa-solid fa-briefcase"></i> Información laboral</div>
            <div class="cred-fact"><span class="cred-fact-k">Cédula</span><span class="cred-fact-v"><code><?= $h($empleado['cedula'] ?? '') ?></code></span></div>
            <?php if ($fechaIngresoFmt): ?>
            <div class="cred-fact"><span class="cred-fact-k">Fecha de ingreso</span><span class="cred-fact-v"><?= $h($fechaIngresoFmt) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($empleado['tipo_contrato'])): ?>
            <div class="cred-fact"><span class="cred-fact-k">Tipo de contrato</span><span class="cred-fact-v"><?= $h($empleado['tipo_contrato']) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($empleado['jornada'])): ?>
            <div class="cred-fact"><span class="cred-fact-k">Jornada</span><span class="cred-fact-v"><?= $h($empleado['jornada']) ?></span></div>
            <?php endif; ?>
        </div>
        <div class="cred-col">
            <div class="cred-col-title"><i class="fa-solid fa-address-card"></i> Información de contacto</div>
            <div class="cred-fact"><span class="cred-fact-k">Correo institucional</span><span class="cred-fact-v"><?= $h($empleado['correo_institucional'] ?: '—') ?></span></div>
            <?php if ($telefono !== ''): ?>
            <div class="cred-fact"><span class="cred-fact-k">Teléfono</span><span class="cred-fact-v"><?= $h($telefono) ?></span></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.errorList('Corrige los siguientes errores', <?= json_encode(array_values($errors)) ?>));</script>
<?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/admin/usuarios/desde-th" id="nue-form">
    <input type="hidden" name="_csrf_token" value="<?= $h($csrf) ?>">
    <input type="hidden" name="id_empleado_th" value="<?= (int)$empleado['empleado_id'] ?>">

    <div class="nue-grid">

        <!-- Configuración de acceso -->
        <div class="nue-panel">
            <div class="nue-panel-head"><i class="fa-solid fa-key"></i> Configuración de acceso</div>
            <div class="nue-panel-body">

                <div class="form-group" style="margin:0;">
                    <label class="form-label">Cédula de identidad (usuario de acceso) <span class="th-auto-badge"><i class="fa-solid fa-check"></i> Desde TH</span></label>
                    <input type="text" class="form-control th-auto-field" readonly
                           style="cursor:not-allowed;font-family:var(--font-mono);"
                           value="<?= $h($empleado['cedula'] ?? '') ?>">
                    <span class="form-help">El acceso al sistema se identifica exclusivamente mediante el número de cédula registrado en Talento Humano. No se define un nombre de usuario independiente.</span>
                </div>

                <div class="form-group" style="margin:0;">
                    <label class="form-label">Correo electrónico institucional <span class="th-auto-badge"><i class="fa-solid fa-check"></i> Desde TH</span></label>
                    <input type="email" name="correo" id="f-correo" class="form-control th-auto-field" maxlength="150"
                           value="<?= $v('correo', $empleado['correo_institucional'] ?? '') ?>">
                    <span class="form-help">Tomado del registro de Talento Humano. Puede corregirse antes de confirmar la creación de la cuenta.</span>
                </div>

                <div class="form-group" style="margin:0;">
                    <label class="form-label">Contraseña temporal *</label>
                    <div class="pwd-row">
                        <div style="position:relative;flex:1;">
                            <input type="text" name="contrasena" id="f-pwd" class="form-control" required minlength="8"
                                   placeholder="Mínimo 8 caracteres" oninput="pintarPreview()">
                        </div>
                        <button type="button" class="btn btn-outline" onclick="generarPassword()">
                            <i class="fa-solid fa-dice"></i> Generar
                        </button>
                        <button type="button" class="btn btn-ghost" id="btn-copiar" onclick="copiarPassword()" title="Copiar al portapapeles">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                    <span class="form-help">Se muestra en texto plano únicamente para poder comunicarla al colaborador. Deberá modificarla en su primer inicio de sesión.</span>
                </div>

            </div>
        </div>

        <!-- Asignación organizacional -->
        <div class="nue-panel">
            <div class="nue-panel-head"><i class="fa-solid fa-sitemap"></i> Asignación organizacional</div>
            <div class="nue-panel-body">
                <?php if ($sugerido): ?>
                <div class="alert alert-success" style="margin:0;display:flex;gap:var(--sp-2);align-items:flex-start;">
                    <i class="fa-solid fa-wand-magic-sparkles" style="margin-top:2px;"></i>
                    <div>
                        <strong>Departamento y rol determinados automáticamente</strong> a partir de la unidad
                        organizacional registrada en Talento Humano (<code><?= $h($empleado['codigo_uorg'] ?? '') ?></code>).
                        Pueden modificarse manualmente si la asignación no corresponde.
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-warning" style="margin:0;">
                    <i class="fa-solid fa-circle-info"></i>
                    La unidad organizacional de este empleado (<code><?= $h($empleado['codigo_uorg'] ?? '—') ?></code>)
                    aún no tiene un departamento y rol correspondientes configurados. Selecciónelos manualmente a continuación.
                </div>
                <?php endif; ?>

                <div class="form-group" style="margin:0;">
                    <label class="form-label">Departamento *<?php if ($sugDepto): ?><span class="th-auto-badge"><i class="fa-solid fa-check"></i> Desde TH</span><?php endif; ?></label>
                    <select name="id_departamento" id="f-depto" class="form-control<?= $sugDepto ? ' th-auto-field' : '' ?>" required onchange="pintarPreview()">
                        <option value="">Seleccione…</option>
                        <?php foreach ($deptos as $d): ?>
                        <option value="<?= $d['id_departamento'] ?>"
                            <?= (string)($oldInput['id_departamento'] ?? $sugDepto ?? '') === (string)$d['id_departamento'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin:0;">
                    <label class="form-label">Rol *<?php if ($sugRolAnalista || $sugRolDirector): ?><span class="th-auto-badge"><i class="fa-solid fa-check"></i> Desde TH</span><?php endif; ?></label>
                    <select name="id_rol" id="f-rol" class="form-control<?= ($sugRolAnalista || $sugRolDirector) ? ' th-auto-field' : '' ?>" required onchange="pintarPreview()">
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
                    <label class="form-label">Nivel jerárquico *</label>
                    <select name="nivel_jerarquia" id="f-nivel" class="form-control" required onchange="pintarPreview()">
                        <?php foreach ($nivelOpts as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= (string)($oldInput['nivel_jerarquia'] ?? '1') === (string)$val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

    </div>

    <!-- Resumen + confirmación -->
    <div class="nue-summary">
        <div class="nue-summary-facts">
            <div class="nue-summary-item">
                <span class="k">Colaborador</span>
                <span class="val"><?= $h($empleado['nombre_completo']) ?></span>
            </div>
            <div class="nue-summary-item">
                <span class="k">Correo</span>
                <span class="val" id="pv-correo">—</span>
            </div>
            <div class="nue-summary-item">
                <span class="k">Departamento</span>
                <span class="val" id="pv-depto">—</span>
            </div>
            <div class="nue-summary-item">
                <span class="k">Rol</span>
                <span class="val" id="pv-rol">—</span>
            </div>
            <div class="nue-summary-item">
                <span class="k">Contraseña</span>
                <span class="val" id="pv-pwd" style="font-family:var(--font-mono);">—</span>
            </div>
        </div>
        <div class="nue-summary-actions">
            <a href="<?= APP_URL ?>/admin/usuarios/desde-th" class="btn btn-ghost" data-spa>Cancelar</a>
            <button type="button" class="btn btn-primary" onclick="confirmarCreacion()">
                <i class="fa-solid fa-floppy-disk"></i> Crear cuenta de acceso
            </button>
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
    PortalAlert.success('Contraseña copiada al portapapeles');
}

function confirmarCreacion() {
    const form = document.getElementById('nue-form');
    if (!form.reportValidity()) return;

    const nombre = <?= json_encode($empleado['nombre_completo']) ?>;
    const depto  = document.getElementById('f-depto');
    const rol    = document.getElementById('f-rol');
    const deptoTxt = depto.selectedIndex > 0 ? depto.options[depto.selectedIndex].text : 'sin definir';
    const rolTxt   = rol.selectedIndex > 0 ? (rol.options[rol.selectedIndex].dataset.nombre || rol.options[rol.selectedIndex].text) : 'sin definir';

    Swal.fire({
        icon: 'question',
        title: '¿Confirma la creación de esta cuenta?',
        html: `Se habilitará el acceso al Portal APM para <strong>${portalAlertEscape(nombre)}</strong>
               con departamento <strong>${portalAlertEscape(deptoTxt)}</strong> y rol <strong>${portalAlertEscape(rolTxt)}</strong>.`,
        showCancelButton: true,
        confirmButtonText: 'Sí, crear cuenta',
        cancelButtonText: 'Revisar',
        confirmButtonColor: '#0284C7',
        reverseButtons: true,
    }).then((result) => {
        if (result.isConfirmed) form.submit();
    });
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
