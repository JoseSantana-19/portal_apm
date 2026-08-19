<?php
$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$success = SessionHelper::getFlash('success');
$humano = function ($segundos) {
    $segundos = (int)$segundos;
    if ($segundos <= 0) return '—';
    if ($segundos % 3600 === 0) return ($segundos / 3600) . ' h';
    if ($segundos % 60 === 0) return ($segundos / 60) . ' min';
    return $segundos . ' s';
};
$presets = [30 => '30s (prueba)', 60 => '1 min', 300 => '5 min', 600 => '10 min', 1800 => '30 min', 3600 => '1 hora'];
?>
<?php if ($success): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.success(<?= json_encode($success) ?>));</script>
<?php endif; ?>

<style>
.inact-wrap .card { border-radius: var(--radius-lg); }
.inact-presets { display:flex; gap:6px; flex-wrap:wrap; margin:8px 0 14px; }
.inact-preset-btn {
    padding:4px 11px; font-size:.7rem; font-weight:700; border-radius:999px;
    border:1px solid var(--border-app); background:var(--accent-app); color:var(--text-muted);
    cursor:pointer; transition:all .15s ease;
}
.inact-preset-btn:hover { border-color:var(--color-primary); color:var(--color-primary); }
.inact-field-row { display:flex; align-items:center; gap:10px; }
.inact-field-row input[type="number"] {
    width:110px; padding:9px 10px; border-radius:8px; border:1px solid var(--border-app);
    background:var(--surface-app); color:var(--text-app); font-weight:700; text-align:center;
}
.inact-field-row .unit { font-size:.78rem; color:var(--text-muted); font-weight:600; }
.inact-mod-card {
    border:1px solid var(--border-app); border-radius:var(--radius-lg); padding:18px;
    background:var(--card-bg,var(--surface-app)); display:flex; flex-direction:column; gap:10px;
}
.inact-mod-badge-eff {
    display:inline-flex; align-items:center; gap:5px; font-size:.7rem; font-weight:700;
    padding:2px 9px; border-radius:999px;
}
.inact-user-row { display:flex; align-items:center; gap:10px; padding:9px 4px; border-bottom:1px solid var(--border-app); }
.inact-user-row:last-child { border-bottom:none; }
</style>

<div class="inact-wrap" style="animation:pageFadeIn .35s ease-out;">

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <div style="font-size:.72rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--color-primary);display:flex;align-items:center;gap:8px;margin-bottom:4px;">
            <i class="fa-solid fa-shield-halved"></i> Solo Administrador General
        </div>
        <h2 class="page-title">
            <i class="fa-solid fa-hourglass-half" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
            Tiempo de Inactividad
        </h2>
        <p class="page-subtitle">Cuánto puede estar quieta una sesión antes de cerrarse sola — global, por módulo o por persona.</p>
    </div>
    <button type="button" class="btn btn-ghost" onclick="previewAvisoInactividad()">
        <i class="fa-solid fa-eye"></i> Vista previa del aviso
    </button>
</div>

<div class="alert alert-info" style="margin-bottom:var(--sp-5);align-items:flex-start;">
    <i class="fa-solid fa-circle-info" style="margin-top:2px;"></i>
    <div>
        <strong>Cómo se decide el valor final:</strong> si la persona tiene un ajuste individual, ese gana siempre.
        Si no, se usa el ajuste del módulo en el que está (Portal, Talento Humano, Control de Bienes o Bitácoras).
        Si el módulo tampoco tiene uno propio, se aplica el valor global de acá abajo.
        Antes de cerrar la sesión, se muestra un aviso con cuenta regresiva preguntando si querés seguir conectado.
    </div>
</div>

<!-- Global -->
<div class="card" style="margin-bottom:var(--sp-5);">
    <div class="card-header" style="display:flex;align-items:center;gap:10px;">
        <i class="fa-solid fa-globe" style="color:var(--color-primary);"></i>
        <span class="card-title">Valor global por defecto</span>
        <span style="margin-left:auto;font-size:.72rem;color:var(--text-muted);">Aplica a todo lo que no tenga un ajuste más específico</span>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= APP_URL ?>/admin/inactividad/global" id="form-global" onsubmit="return guardarInactividadYRecargar(event, this)">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5);">
                <div>
                    <label class="form-label">Cerrar sesión tras</label>
                    <div class="inact-field-row">
                        <input type="number" name="segundos" id="global-segundos" min="30" value="<?= (int)($global['segundos'] ?? 1800) ?>" required>
                        <span class="unit">segundos (<span id="global-segundos-h"><?= $e($humano($global['segundos'] ?? 1800)) ?></span>)</span>
                    </div>
                    <div class="inact-presets">
                        <?php foreach ($presets as $s => $lbl): ?>
                        <button type="button" class="inact-preset-btn" onclick="setVal('global-segundos', <?= $s ?>)"><?= $e($lbl) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <label class="form-label">Mostrar aviso</label>
                    <div class="inact-field-row">
                        <input type="number" name="aviso" id="global-aviso" min="5" value="<?= (int)($global['aviso'] ?? 60) ?>" required>
                        <span class="unit">segundos antes de cerrarla</span>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:var(--sp-4);">
                <i class="fa-solid fa-floppy-disk"></i> Guardar valor global
            </button>
        </form>
    </div>
</div>

<!-- Por módulo -->
<h3 style="font-size:1rem;font-weight:800;margin-bottom:var(--sp-3);display:flex;align-items:center;gap:8px;">
    <i class="fa-solid fa-shapes" style="color:var(--color-primary);"></i> Por módulo
</h3>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:var(--sp-3);margin-bottom:var(--sp-5);">
    <?php
    $modIcons = ['CENTRAL' => 'fa-building-shield', 'TALENTO_HUMANO' => 'fa-users', 'CONTROL_BIENES' => 'fa-boxes-stacked', 'BITACORAS' => 'fa-anchor'];
    foreach ($porModulo as $code => $m):
        $tienePropio = $m['segundos'] !== null;
        $segEfectivo = $tienePropio ? $m['segundos'] : $global['segundos'];
        $avisoEfectivo = $tienePropio ? $m['aviso'] : $global['aviso'];
        $formId = 'form-mod-' . strtolower($code);
    ?>
    <div class="inact-mod-card">
        <div style="display:flex;align-items:center;gap:10px;">
            <i class="fa-solid <?= $e($modIcons[$code] ?? 'fa-cube') ?>" style="font-size:1.1rem;color:var(--color-primary);"></i>
            <strong style="font-size:.9rem;"><?= $e($m['label']) ?></strong>
        </div>
        <div class="inact-mod-badge-eff" style="background:<?= $tienePropio ? 'color-mix(in srgb,#fd7e14 15%,transparent)' : 'color-mix(in srgb,var(--color-primary) 12%,transparent)' ?>;color:<?= $tienePropio ? '#fd7e14' : 'var(--color-primary)' ?>;width:fit-content;">
            <i class="fa-solid <?= $tienePropio ? 'fa-pen' : 'fa-link' ?>" style="font-size:8px;"></i>
            <?= $tienePropio ? 'Ajuste propio' : 'Hereda el global' ?> · <?= $e($humano($segEfectivo)) ?>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/inactividad/modulo/<?= $e($code) ?>" id="<?= $formId ?>" onsubmit="return guardarInactividadYRecargar(event, this)">
            <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
            <label style="display:flex;align-items:center;gap:7px;font-size:.78rem;color:var(--text-muted);margin-bottom:8px;cursor:pointer;">
                <input type="checkbox" name="usar_global" value="1" <?= !$tienePropio ? 'checked' : '' ?>
                       onchange="document.getElementById('<?= $formId ?>-campos').style.display = this.checked ? 'none' : 'flex';">
                Usar el valor global
            </label>
            <div id="<?= $formId ?>-campos" style="display:<?= $tienePropio ? 'flex' : 'none' ?>;gap:8px;margin-bottom:10px;">
                <input type="number" name="segundos" min="30" value="<?= (int)($m['segundos'] ?? $global['segundos']) ?>"
                       style="width:90px;padding:6px 8px;border-radius:7px;border:1px solid var(--border-app);background:var(--surface-app);color:var(--text-app);font-weight:700;text-align:center;">
                <input type="number" name="aviso" min="5" value="<?= (int)($m['aviso'] ?? $global['aviso']) ?>"
                       style="width:80px;padding:6px 8px;border-radius:7px;border:1px solid var(--border-app);background:var(--surface-app);color:var(--text-app);font-weight:700;text-align:center;"
                       title="Segundos de aviso previo">
            </div>
            <button type="submit" class="btn btn-ghost btn-sm" style="width:100%;">
                <i class="fa-solid fa-check"></i> Aplicar
            </button>
        </form>
    </div>
    <?php endforeach; ?>
</div>

<!-- Por usuario -->
<?php $conOverride = count(array_filter($usuarios, fn($u) => $u['inactividad_segundos_override'] !== null || $u['inactividad_aviso_segundos_override'] !== null)); ?>
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-2);margin-bottom:var(--sp-3);">
    <h3 style="font-size:1rem;font-weight:800;margin:0;display:flex;align-items:center;gap:8px;">
        <i class="fa-solid fa-user-gear" style="color:var(--color-primary);"></i> Por usuario individual
        <span class="badge badge-gray" style="font-weight:600;"><?= count($usuarios) ?></span>
        <?php if ($conOverride > 0): ?>
        <span class="badge badge-warning" style="font-weight:600;"><?= $conOverride ?> con ajuste propio</span>
        <?php endif; ?>
    </h3>
</div>

<div class="card">
    <div class="table-responsive-wrapper">
        <table id="inact-tabla-usuarios" data-dt data-dt-page-length="25">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Departamento</th>
                    <th>Nivel</th>
                    <th>Tiempo de inactividad</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($usuarios)): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:var(--sp-8);">Sin usuarios registrados.</td></tr>
            <?php else: $nivelLabels = [0=>'Operativo',1=>'Analista',2=>'Jefatura',3=>'Director',4=>'Super Admin'];
            foreach ($usuarios as $u):
                $tieneOverride = $u['inactividad_segundos_override'] !== null || $u['inactividad_aviso_segundos_override'] !== null;
                $initials = implode('', array_map(fn($w) => mb_strtoupper(mb_substr($w,0,1)), array_slice(explode(' ', trim($u['nombre_completo'])), 0, 2)));
                $inactivo = (int)$u['estado'] === 0;
            ?>
                <tr style="<?= $inactivo ? 'opacity:.5;' : '' ?>">
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:var(--radius-full);background:color-mix(in srgb,var(--color-primary) 15%,transparent);color:var(--color-primary);display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;flex-shrink:0;"><?= $e($initials) ?></div>
                            <div>
                                <div style="font-weight:600;font-size:.85rem;"><?= $e($u['nombre_completo']) ?><?php if ($inactivo): ?> <span class="badge badge-gray" style="font-size:.65rem;">Inactivo</span><?php endif; ?></div>
                                <div style="font-size:.7rem;color:var(--text-muted);"><code><?= $e($u['cedula']) ?></code></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="dt-truncate dt-truncate-sm" style="font-size:.82rem;color:var(--text-muted);" title="<?= $e($u['departamento'] ?: '—') ?>"><?= $e($u['departamento'] ?: '—') ?></span></td>
                    <td><span class="badge badge-gray"><?= $e($nivelLabels[(int)$u['nivel_jerarquia']] ?? $u['nivel_jerarquia']) ?></span></td>
                    <td>
                        <?php if ($tieneOverride): ?>
                        <span class="badge badge-warning">
                            <i class="fa-solid fa-user-pen" style="font-size:8px;"></i>
                            <?= $e($u['inactividad_segundos_override'] !== null ? $humano($u['inactividad_segundos_override']) : 'hereda') ?>
                            · aviso <?= $e($u['inactividad_aviso_segundos_override'] !== null ? $u['inactividad_aviso_segundos_override'] . 's' : 'hereda') ?>
                        </span>
                        <?php else: ?>
                        <span class="badge badge-gray"><i class="fa-solid fa-link" style="font-size:8px;"></i> Hereda módulo/global</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;white-space:nowrap;">
                        <div class="dt-actions">
                            <button type="button" class="btn btn-ghost btn-sm" title="Fijar ajuste propio"
                                    onclick='editarOverrideUsuario(<?= json_encode([
                                        "id" => (int)$u["id_usuario"], "nombre" => $u["nombre_completo"],
                                        "segundos" => $u["inactividad_segundos_override"], "aviso" => $u["inactividad_aviso_segundos_override"],
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <?php if ($tieneOverride): ?>
                            <form method="POST" action="<?= APP_URL ?>/admin/inactividad/usuario/<?= (int)$u['id_usuario'] ?>">
                                <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                                <input type="hidden" name="quitar_override" value="1">
                                <button type="button" class="btn btn-ghost btn-sm" style="color:var(--color-danger);" title="Quitar ajuste (vuelve a heredar)"
                                        onclick="PortalAlert.confirmAction('¿Quitar el ajuste individual de <?= $e($u['nombre_completo']) ?>? Volverá a heredar el valor del módulo o global.', this.form, {title:'¿Quitar ajuste?', confirmText:'Sí, quitar'})">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<script>
// Estos formularios NO se dejan en manos del SPA genérico (main.js), que
// solo reemplaza el HTML del contenido — la config de inactividad
// (window.APP_INACTIVIDAD) y el temporizador ya corriendo
// (js/inactivity-warning.js) viven en el layout persistente y NO se
// vuelven a ejecutar con un swap de innerHTML. Guardar acá y quedarse en
// la misma pantalla dejaba corriendo el valor VIEJO indefinidamente. Un
// recargue real de página sí vuelve a traer todo fresco desde el servidor.
function guardarInactividadYRecargar(event, form) {
    event.preventDefault();
    event.stopPropagation();
    fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form),
    }).then(() => { location.reload(); })
      .catch(() => { location.reload(); });
    return false;
}

function setVal(id, val) {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = val;
    if (id === 'global-segundos') {
        document.getElementById('global-segundos-h').textContent = humanoSegundos(val);
    }
}
function humanoSegundos(s) {
    s = parseInt(s, 10) || 0;
    if (s % 3600 === 0) return (s / 3600) + ' h';
    if (s % 60 === 0) return (s / 60) + ' min';
    return s + ' s';
}
document.getElementById('global-segundos')?.addEventListener('input', function () {
    document.getElementById('global-segundos-h').textContent = humanoSegundos(this.value);
});

// var (no let/const): esta página se puede recargar vía SPA sin refrescar
// el documento — un `let` de nivel superior revive un
// "Identifier has already been declared" en la segunda visita y mata TODO
// el bloque de script. var sí permite redeclararse sin error.
var inactTablaBody = null;

// Modal de edición — un solo formulario reusado para cualquier fila,
// consistente con el resto del panel (SweetAlert2 en vez de otra pantalla).
function editarOverrideUsuario(u) {
    Swal.fire({
        title: portalAlertEscape(u.nombre),
        html: `
            <div style="text-align:left;">
                <label style="display:block;font-size:.78rem;font-weight:700;margin-bottom:6px;color:var(--text-muted,#666);">Cerrar sesión tras (segundos)</label>
                <input type="number" min="30" id="swal-inact-seg" class="swal2-input" style="margin:0 0 14px;"
                       placeholder="Vacío = hereda módulo/global" value="${u.segundos ?? ''}">
                <label style="display:block;font-size:.78rem;font-weight:700;margin-bottom:6px;color:var(--text-muted,#666);">Mostrar aviso (segundos antes)</label>
                <input type="number" min="5" id="swal-inact-avi" class="swal2-input" style="margin:0;"
                       placeholder="Vacío = hereda módulo/global" value="${u.aviso ?? ''}">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-check"></i>&nbsp; Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#0d6efd',
        focusConfirm: false,
        preConfirm: () => {
            const seg = document.getElementById('swal-inact-seg').value;
            const avi = document.getElementById('swal-inact-avi').value;
            if (!seg) { Swal.showValidationMessage('Ingresá los segundos de inactividad.'); return false; }
            return { seg, avi };
        },
    }).then(result => {
        if (!result.isConfirmed) return;
        const body = new URLSearchParams({ _csrf_token: '<?= $e($csrf) ?>', segundos: result.value.seg, aviso: result.value.avi || 60 });
        fetch(`<?= APP_URL ?>/admin/inactividad/usuario/${u.id}`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body,
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) { PortalAlert.success('Ajuste guardado.'); setTimeout(() => location.reload(), 700); }
            else { PortalAlert.error('No se pudo guardar.'); }
        });
    });
}

// ── Vista previa: mismo diseño del aviso real que ven los usuarios ──
function previewAvisoInactividad() {
    const CIRC = 2 * Math.PI * 42;
    Swal.fire({
        title: 'Tu sesión está por cerrarse',
        html: `
            <div style="color:inherit;">
                <svg width="104" height="104" viewBox="0 0 104 104" style="display:block;margin:0 auto 14px;">
                    <circle cx="52" cy="52" r="42" fill="none" stroke="currentColor" stroke-opacity=".15" stroke-width="7"/>
                    <circle cx="52" cy="52" r="42" fill="none" stroke="#198754" stroke-width="7" stroke-linecap="round"
                            stroke-dasharray="${CIRC}" stroke-dashoffset="0" transform="rotate(-90 52 52)"/>
                    <text x="52" y="59" text-anchor="middle" font-size="24" font-weight="800" fill="currentColor">0:60</text>
                </svg>
                <p style="margin:0 0 4px;font-size:.92rem;opacity:.85;">
                    No detectamos actividad en un buen rato. Por seguridad, la sesión se cerrará sola si no respondés.
                </p>
                <p style="margin-top:10px;font-size:.72rem;opacity:.6;">(Esto es solo una vista previa — no cierra tu sesión real)</p>
            </div>
        `,
        iconHtml: '<i class="fa-regular fa-clock" style="font-size:1.4rem;"></i>',
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-check"></i>&nbsp; Seguir conectado',
        cancelButtonText: 'Cerrar sesión ahora',
        confirmButtonColor: '#0d6efd',
    });
}
</script>
