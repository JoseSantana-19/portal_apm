<?php
/**
 * Inactividad de Sesión — Central Portal APM
 * Políticas de desconexión por inactividad: cascada global > por módulo > por usuario.
 */
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

<div class="dashboard-wrapper anim-up anim-d0">

    <!-- ══════════════════════════════════════════════════════════════
         PREMIUM ADMIN HEADER
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-page-header">
        <div class="admin-header-title-group">
            <div class="admin-header-icon" style="background:linear-gradient(135deg, #F59E0B, #D97706);">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div>
                <div class="admin-header-eyebrow">
                    <i class="fa-solid fa-shield-halved"></i> Administración &bull; Políticas de Sesión
                </div>
                <h1 class="admin-header-title">Tiempo de Inactividad de Sesión</h1>
                <div class="admin-header-subtitle">
                    Configuración de timeout y cierre automático de sesiones por seguridad (global, módulos y usuarios)
                </div>
            </div>
        </div>

        <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;align-items:center;">
            <button type="button" class="btn-dash btn-dash-primary" onclick="previewAvisoInactividad()">
                <i class="fa-solid fa-eye"></i> Vista Previa del Aviso
            </button>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         POLICY EXPLANATION BANNER
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-card" style="margin-bottom:var(--sp-5);background:color-mix(in srgb, var(--primary-hover) 8%, var(--surface-app));border-color:color-mix(in srgb, var(--primary-hover) 30%, transparent);">
        <div style="display:flex;align-items:flex-start;gap:14px;padding:var(--sp-4);">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--primary-hover);color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;">
                <i class="fa-solid fa-circle-info"></i>
            </div>
            <div style="flex:1;font-size:0.83rem;color:var(--text-app);line-height:1.45;">
                <strong style="color:var(--primary-hover);">Regla de Cascada de Políticas:</strong>
                Si el usuario tiene un ajuste individual personalizado, este tiene máxima prioridad.
                De lo contrario, se aplica la política del módulo en el que se encuentra trabajando (Portal Central, Talento Humano, Control de Bienes o Bitácoras).
                Si el módulo no tiene política propia, se hereda el <strong>Valor Global</strong> por defecto.
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         GLOBAL TIMEOUT CARD
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-card" style="margin-bottom:var(--sp-5);">
        <div class="dash-card-header">
            <div>
                <div class="dash-card-title">
                    <i class="fa-solid fa-globe" style="color:var(--primary-hover);"></i>
                    Valor Global por Defecto
                </div>
                <div class="dash-card-subtitle">Aplica a todas las sesiones que no tengan una regla específica</div>
            </div>
            <span class="badge badge-info" style="font-size:0.7rem;">Base Institucional</span>
        </div>

        <div style="padding:var(--sp-5);">
            <form method="POST" action="<?= APP_URL ?>/admin/inactividad/global" id="form-global" onsubmit="return guardarInactividadYRecargar(event, this)">
                <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:var(--sp-5);">
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:0.8rem;margin-bottom:6px;">Cerrar sesión tras inactividad</label>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <input type="number" name="segundos" id="global-segundos" min="30" value="<?= (int)($global['segundos'] ?? 1800) ?>" class="form-control" style="width:130px;font-weight:800;font-size:1.1rem;text-align:center;" required>
                            <span style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">
                                segundos (<strong style="color:var(--text-app);" id="global-segundos-h"><?= $e($humano($global['segundos'] ?? 1800)) ?></strong>)
                            </span>
                        </div>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;">
                            <?php foreach ($presets as $s => $lbl): ?>
                            <button type="button" class="timeframe-pill" onclick="setVal('global-segundos', <?= $s ?>)"><?= $e($lbl) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="form-label" style="font-weight:700;font-size:0.8rem;margin-bottom:6px;">Tiempo de aviso previo (Countdown)</label>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <input type="number" name="aviso" id="global-aviso" min="5" value="<?= (int)($global['aviso'] ?? 60) ?>" class="form-control" style="width:130px;font-weight:800;font-size:1.1rem;text-align:center;" required>
                            <span style="font-size:0.85rem;color:var(--text-muted);">segundos antes del cierre</span>
                        </div>
                    </div>
                </div>

                <div style="margin-top:var(--sp-4);display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Valor Global
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         POLICIES BY MODULE
         ══════════════════════════════════════════════════════════════ -->
    <div style="margin-bottom:var(--sp-5);">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:var(--sp-3);">
            <i class="fa-solid fa-cubes-stacked" style="color:var(--primary-hover);font-size:1.1rem;"></i>
            <h3 style="font-size:1.1rem;font-weight:800;color:var(--text-app);margin:0;">Políticas por Módulo</h3>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:var(--sp-4);">
            <?php
            $modIcons = ['CENTRAL' => 'fa-building-shield', 'TALENTO_HUMANO' => 'fa-users', 'CONTROL_BIENES' => 'fa-boxes-stacked', 'BITACORAS' => 'fa-anchor'];
            $modColors = ['CENTRAL' => '#0284C7', 'TALENTO_HUMANO' => '#10B981', 'CONTROL_BIENES' => '#0284C7', 'BITACORAS' => '#F59E0B'];
            foreach ($porModulo as $code => $m):
                $tienePropio = $m['segundos'] !== null;
                $segEfectivo = $tienePropio ? $m['segundos'] : $global['segundos'];
                $avisoEfectivo = $tienePropio ? $m['aviso'] : $global['aviso'];
                $formId = 'form-mod-' . strtolower($code);
                $mColor = $modColors[$code] ?? '#0284C7';
            ?>
            <div class="dash-card">
                <div style="padding:var(--sp-4);display:flex;flex-direction:column;gap:12px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;border-radius:var(--radius-sm);background:color-mix(in srgb, <?= $mColor ?> 15%, transparent);color:<?= $mColor ?>;display:flex;align-items:center;justify-content:center;font-size:1rem;">
                                <i class="fa-solid <?= $e($modIcons[$code] ?? 'fa-cube') ?>"></i>
                            </div>
                            <strong style="font-size:0.9rem;color:var(--text-app);"><?= $e($m['label']) ?></strong>
                        </div>
                    </div>

                    <div>
                        <span class="badge badge-<?= $tienePropio ? 'warning' : 'info' ?>" style="font-size:0.7rem;">
                            <i class="fa-solid <?= $tienePropio ? 'fa-pen' : 'fa-link' ?>"></i>
                            <?= $tienePropio ? 'Ajuste Propio' : 'Hereda Global' ?> &bull; <?= $e($humano($segEfectivo)) ?>
                        </span>
                    </div>

                    <form method="POST" action="<?= APP_URL ?>/admin/inactividad/modulo/<?= $e($code) ?>" id="<?= $formId ?>" onsubmit="return guardarInactividadYRecargar(event, this)">
                        <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                        <label style="display:flex;align-items:center;gap:7px;font-size:0.78rem;color:var(--text-muted);margin-bottom:8px;cursor:pointer;">
                            <input type="checkbox" name="usar_global" value="1" <?= !$tienePropio ? 'checked' : '' ?>
                                   onchange="document.getElementById('<?= $formId ?>-campos').style.display = this.checked ? 'none' : 'flex';">
                            Usar política global por defecto
                        </label>
                        <div id="<?= $formId ?>-campos" style="display:<?= $tienePropio ? 'flex' : 'none' ?>;gap:8px;margin-bottom:10px;">
                            <input type="number" name="segundos" min="30" value="<?= (int)($m['segundos'] ?? $global['segundos']) ?>" class="form-control" style="width:100px;font-weight:700;text-align:center;" title="Segundos timeout">
                            <input type="number" name="aviso" min="5" value="<?= (int)($m['aviso'] ?? $global['aviso']) ?>" class="form-control" style="width:90px;font-weight:700;text-align:center;" title="Segundos de aviso">
                        </div>
                        <button type="submit" class="btn btn-ghost btn-sm" style="width:100%;">
                            <i class="fa-solid fa-check"></i> Aplicar al Módulo
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         CUSTOM POLICIES BY INDIVIDUAL USER
         ══════════════════════════════════════════════════════════════ -->
    <?php $conOverride = count(array_filter($usuarios, fn($u) => $u['inactividad_segundos_override'] !== null || $u['inactividad_aviso_segundos_override'] !== null)); ?>
    <div class="dash-card">
        <div class="dash-card-header">
            <div>
                <div class="dash-card-title">
                    <i class="fa-solid fa-user-gear" style="color:var(--primary-hover);"></i>
                    Excepciones Individuales por Usuario
                </div>
                <div class="dash-card-subtitle">Personal con tiempos de sesión personalizados o extendidos</div>
            </div>
            <?php if ($conOverride > 0): ?>
            <span class="badge badge-warning" style="font-size:0.7rem;"><?= $conOverride ?> con ajuste propio</span>
            <?php endif; ?>
        </div>

        <div class="dash-table-wrap">
            <table id="inact-tabla-usuarios" class="dash-table" data-dt data-dt-page-length="25">
                <thead>
                    <tr>
                        <th>Usuario / Cédula</th>
                        <th>Departamento</th>
                        <th>Nivel</th>
                        <th>Tiempo de Inactividad</th>
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
                    $inactivo = (int)($u['estado'] ?? 1) === 0;
                ?>
                    <tr style="<?= $inactivo ? 'opacity:.5;' : '' ?>">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="admin-avatar" style="background:#0284C7;">
                                    <?= $e($initials) ?>
                                </div>
                                <div>
                                    <div style="font-weight:700;color:var(--text-app);font-size:0.83rem;">
                                        <?= $e($u['nombre_completo']) ?>
                                        <?php if ($inactivo): ?>
                                        <span class="badge badge-danger" style="font-size:0.6rem;">Inactivo</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:0.7rem;color:var(--text-muted);font-family:var(--font-code);">
                                        <?= $e($u['cedula']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-size:0.78rem;color:var(--text-muted);">
                                <?= $e($u['departamento'] ?: '—') ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-gray" style="font-size:0.7rem;">
                                <?= $e($nivelLabels[(int)$u['nivel_jerarquia']] ?? $u['nivel_jerarquia']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($tieneOverride): ?>
                            <span class="badge badge-warning" style="font-size:0.72rem;">
                                <i class="fa-solid fa-user-pen"></i>
                                <?= $e($u['inactividad_segundos_override'] !== null ? $humano($u['inactividad_segundos_override']) : 'hereda') ?>
                                &bull; aviso <?= $e($u['inactividad_aviso_segundos_override'] !== null ? $u['inactividad_aviso_segundos_override'] . 's' : 'hereda') ?>
                            </span>
                            <?php else: ?>
                            <span class="badge badge-gray" style="font-size:0.72rem;">
                                <i class="fa-solid fa-link"></i> Hereda Módulo / Global
                            </span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;white-space:nowrap;">
                            <div class="dt-actions">
                                <button type="button" class="btn btn-ghost btn-sm" title="Fijar tiempo personalizado"
                                        onclick='editarOverrideUsuario(<?= json_encode([
                                            "id" => (int)$u["id_usuario"], "nombre" => $u["nombre_completo"],
                                            "segundos" => $u["inactividad_segundos_override"], "aviso" => $u["inactividad_aviso_segundos_override"],
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <?php if ($tieneOverride): ?>
                                <form method="POST" action="<?= APP_URL ?>/admin/inactividad/usuario/<?= (int)$u['id_usuario'] ?>" style="display:inline;">
                                    <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                                    <input type="hidden" name="quitar_override" value="1">
                                    <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger);" title="Quitar ajuste individual"
                                            onclick="PortalAlert.confirmAction('¿Restablecer el ajuste individual de <?= $e($u['nombre_completo']) ?> para que vuelva a heredar?', this.form, {title:'¿Restablecer?', confirmText:'Sí, restablecer'})">
                                        <i class="fa-solid fa-trash-can"></i>
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

function editarOverrideUsuario(u) {
    Swal.fire({
        title: portalAlertEscape(u.nombre),
        html: `
            <div style="text-align:left;font-size:0.85rem;">
                <label style="display:block;font-weight:700;margin-bottom:6px;color:var(--text-app);">Cerrar sesión tras (segundos):</label>
                <input type="number" min="30" id="swal-inact-seg" class="swal2-input" style="margin:0 0 14px;width:100%;box-sizing:border-box;"
                       placeholder="Vacío = hereda regla general" value="${u.segundos ?? ''}">
                <label style="display:block;font-weight:700;margin-bottom:6px;color:var(--text-app);">Mostrar aviso (segundos antes):</label>
                <input type="number" min="5" id="swal-inact-avi" class="swal2-input" style="margin:0;width:100%;box-sizing:border-box;"
                       placeholder="Vacío = hereda regla general" value="${u.aviso ?? ''}">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-check"></i> Guardar Ajuste',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#0284C7',
        focusConfirm: false,
        preConfirm: () => {
            const seg = document.getElementById('swal-inact-seg').value;
            const avi = document.getElementById('swal-inact-avi').value;
            if (!seg) { Swal.showValidationMessage('Ingresa los segundos de inactividad.'); return false; }
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
            if (data.ok) { PortalAlert.success('Ajuste guardado correctamente.'); setTimeout(() => location.reload(), 600); }
            else { PortalAlert.error('No se pudo guardar el ajuste.'); }
        });
    });
}

function previewAvisoInactividad() {
    const CIRC = 2 * Math.PI * 42;
    Swal.fire({
        title: 'Tu sesión está por expirar',
        html: `
            <div style="color:inherit;padding:10px 0;">
                <svg width="104" height="104" viewBox="0 0 104 104" style="display:block;margin:0 auto 14px;">
                    <circle cx="52" cy="52" r="42" fill="none" stroke="currentColor" stroke-opacity=".15" stroke-width="7"/>
                    <circle cx="52" cy="52" r="42" fill="none" stroke="#10B981" stroke-width="7" stroke-linecap="round"
                            stroke-dasharray="${CIRC}" stroke-dashoffset="0" transform="rotate(-90 52 52)"/>
                    <text x="52" y="59" text-anchor="middle" font-size="24" font-weight="800" fill="currentColor">0:60</text>
                </svg>
                <p style="margin:0 0 6px;font-size:0.92rem;opacity:0.85;">
                    No detectamos actividad en tu estación de trabajo. Por seguridad institucional, la sesión se cerrará automáticamente.
                </p>
                <p style="margin-top:10px;font-size:0.75rem;opacity:0.6;">(Demostración interactiva de seguridad APM)</p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-check"></i> Seguir Conectado',
        cancelButtonText: 'Cerrar Sesión',
        confirmButtonColor: '#0284C7',
    });
}
</script>
