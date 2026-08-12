<?php
$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$nivelColor = [1 => '#6c757d', 2 => '#0d6efd', 3 => '#fd7e14', 4 => '#198754'];
?>
<div style="animation:pageFadeIn .35s ease-out;">

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-2);">
    <a href="<?= APP_URL ?>/admin/roles" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Roles
    </a>
</div>

<!-- Header -->
<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title">
            <i class="fa-solid fa-table-cells" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
            Matriz de Permisos
        </h2>
        <p class="page-subtitle">Qué rol tiene acceso a qué módulo, y con qué nivel — de un vistazo, sin abrir rol por rol.</p>
    </div>
</div>

<!-- Explicación del modelo -->
<div class="alert alert-info" style="margin-bottom:var(--sp-4);align-items:flex-start;">
    <i class="fa-solid fa-circle-info" style="margin-top:2px;"></i>
    <div>
        <strong>El Departamento es solo informativo.</strong> Viene sincronizado desde Talento Humano y agrupa
        usuarios, pero <u>no</u> otorga acceso a ningún módulo por sí solo. El acceso real siempre sigue esta
        cadena: <strong>Usuario → Rol(es) → Permisos por módulo</strong>. Esta matriz resume ese último paso.
        Para el detalle fino (qué opción exacta dentro de cada módulo), abrí el ícono de escudo 🛡️ del rol en
        <a href="<?= APP_URL ?>/admin/roles" data-spa>Gestión de Roles</a>.
    </div>
</div>

<!-- Stats -->
<div style="display:flex;gap:var(--sp-3);margin-bottom:var(--sp-4);flex-wrap:wrap;">
    <div class="card"><div class="card-body" style="display:flex;align-items:center;gap:12px;">
        <i class="fa-solid fa-key" style="font-size:1.3rem;color:var(--color-primary);"></i>
        <div><div style="font-size:1.3rem;font-weight:800;line-height:1;"><?= (int)$totalRoles ?></div>
        <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;">Roles totales</div></div>
    </div></div>
    <div class="card"><div class="card-body" style="display:flex;align-items:center;gap:12px;">
        <i class="fa-solid fa-shapes" style="font-size:1.3rem;color:#0d6efd;"></i>
        <div><div style="font-size:1.3rem;font-weight:800;line-height:1;"><?= count($modulos) ?></div>
        <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;">Módulos con contenido</div></div>
    </div></div>
    <?php if ($sinPermisos > 0): ?>
    <div class="card" style="border-color:color-mix(in srgb,#dc3545 35%,var(--border-app));">
        <div class="card-body" style="display:flex;align-items:center;gap:12px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size:1.3rem;color:#dc3545;"></i>
        <div><div style="font-size:1.3rem;font-weight:800;line-height:1;color:#dc3545;"><?= (int)$sinPermisos ?></div>
        <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;">Roles sin ningún permiso</div></div>
    </div></div>
    <?php endif; ?>
</div>

<!-- Leyenda -->
<div style="display:flex;gap:var(--sp-4);flex-wrap:wrap;margin-bottom:var(--sp-3);font-size:.78rem;color:var(--text-muted);align-items:center;">
    <strong style="color:var(--text-app);">Leyenda:</strong>
    <span style="display:flex;align-items:center;gap:5px;"><span style="width:11px;height:11px;border-radius:3px;background:#198754;display:inline-block;"></span> Acceso completo (todas las opciones)</span>
    <span style="display:flex;align-items:center;gap:5px;"><span style="width:11px;height:11px;border-radius:3px;background:#fd7e14;display:inline-block;"></span> Acceso parcial</span>
    <span style="display:flex;align-items:center;gap:5px;"><span style="width:11px;height:11px;border-radius:3px;background:var(--border-app);display:inline-block;"></span> Sin acceso</span>
</div>

<!-- Buscador -->
<div style="max-width:340px;margin-bottom:var(--sp-3);position:relative;">
    <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.8rem;"></i>
    <input type="text" id="matriz-q" placeholder="Buscar rol por nombre o código…"
           class="form-control" style="padding-left:2.2rem;" oninput="filterMatriz(this.value)">
</div>

<!-- Matriz -->
<div class="card">
    <div style="overflow-x:auto;">
        <table id="matriz-table" style="min-width:640px;">
            <thead>
                <tr>
                    <th style="min-width:220px;">Rol</th>
                    <?php foreach ($modulos as $idMod): $meta = $moduleMeta[$idMod] ?? ['label'=>"Módulo $idMod",'icon'=>'fa-folder','color'=>'#6c757d']; ?>
                    <th style="text-align:center;min-width:150px;">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                            <i class="fa-solid <?= $e($meta['icon']) ?>" style="color:<?= $e($meta['color']) ?>;font-size:1rem;"></i>
                            <span style="font-size:.72rem;font-weight:700;"><?= $e($meta['label']) ?></span>
                        </div>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($matriz)): ?>
                <tr><td colspan="<?= count($modulos) + 1 ?>" style="text-align:center;color:var(--text-muted);padding:var(--sp-8);">Sin roles configurados.</td></tr>
            <?php else: foreach ($matriz as $fila):
                $rol = $fila['rol'];
                $inactivo = (int)$rol['estado'] === 0;
                $sinNingunPermiso = true;
                foreach ($fila['celdas'] as $c) { if ($c['con_acceso'] > 0) { $sinNingunPermiso = false; break; } }
            ?>
                <tr data-search="<?= strtolower($e(($rol['codigo']??'').' '.$rol['nombre'])) ?>" style="<?= $inactivo ? 'opacity:.55;' : '' ?>">
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <?php if ($sinNingunPermiso): ?>
                            <i class="fa-solid fa-triangle-exclamation" style="color:#dc3545;font-size:.75rem;" title="Este rol no tiene ningún permiso configurado"></i>
                            <?php endif; ?>
                            <div>
                                <div style="font-weight:600;font-size:.85rem;"><?= $e($rol['nombre']) ?><?php if ($inactivo): ?> <span class="badge badge-gray" style="font-size:.6rem;">Inactivo</span><?php endif; ?></div>
                                <div style="font-size:.7rem;color:var(--text-muted);">
                                    <code><?= $e($rol['codigo']) ?></code>
                                    · <?= $e($rol['departamento'] ?: 'Sin departamento') ?>
                                    · <?= (int)$fila['usuarios'] ?> usuario<?= (int)$fila['usuarios'] === 1 ? '' : 's' ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <?php foreach ($modulos as $idMod):
                        $c = $fila['celdas'][$idMod];
                        $meta = $moduleMeta[$idMod] ?? ['label' => "Módulo $idMod"];
                        if ($c['con_acceso'] === 0) {
                            $bg = 'transparent'; $fg = 'var(--text-muted)'; $bd = 'var(--border-app)'; $txt = '—';
                        } elseif ($c['con_acceso'] === $c['total']) {
                            $bg = 'color-mix(in srgb, #198754 14%, transparent)'; $fg = '#198754'; $bd = 'color-mix(in srgb, #198754 40%, transparent)';
                            $txt = $nivelLabels[$c['nivel_max']] ?? '';
                        } else {
                            $bg = 'color-mix(in srgb, #fd7e14 14%, transparent)'; $fg = '#fd7e14'; $bd = 'color-mix(in srgb, #fd7e14 40%, transparent)';
                            $txt = $c['con_acceso'] . '/' . $c['total'];
                        }
                    ?>
                    <td style="text-align:center;">
                        <?php if ($c['con_acceso'] > 0): ?>
                        <button type="button" onclick='verDetalleMatriz(<?= json_encode([
                            'rol' => $rol['nombre'], 'modulo' => $meta['label'], 'detalle' => $c['detalle'],
                        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                            style="background:<?= $bg ?>;color:<?= $fg ?>;border:1px solid <?= $bd ?>;border-radius:6px;padding:4px 10px;font-size:.72rem;font-weight:700;cursor:pointer;min-width:64px;">
                            <?= $e($txt) ?>
                        </button>
                        <?php else: ?>
                        <span style="color:<?= $fg ?>;font-size:.8rem;"><?= $e($txt) ?></span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div id="matriz-empty-search" style="display:none;padding:var(--sp-8);text-align:center;color:var(--text-muted);">
        <i class="fa-solid fa-magnifying-glass" style="font-size:1.5rem;opacity:.3;margin-bottom:var(--sp-2);display:block;"></i>
        Sin resultados para la búsqueda
    </div>
</div>

</div>

<script>
function filterMatriz(q) {
    q = q.toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('#matriz-table tbody tr[data-search]').forEach(tr => {
        const match = !q || tr.dataset.search.includes(q);
        tr.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    const empty = document.getElementById('matriz-empty-search');
    if (empty) empty.style.display = (q && visible === 0) ? 'block' : 'none';
}

function verDetalleMatriz(info) {
    const nivelNombre = {0:'Sin acceso',1:'Ver',2:'Crear',3:'Editar',4:'Total'};
    const nivelColor  = {0:'#adb5bd',1:'#6c757d',2:'#0d6efd',3:'#fd7e14',4:'#198754'};
    let filas = '';
    for (const d of info.detalle) {
        const activo = d.nivel > 0;
        filas += `<tr>
            <td style="padding:6px 10px;${activo ? '' : 'color:#adb5bd;'}">${portalAlertEscape(d.nombre || '(sin nombre)')}</td>
            <td style="padding:6px 10px;text-align:right;">
                <span style="display:inline-block;padding:2px 8px;border-radius:99px;font-size:.72rem;font-weight:700;
                             background:color-mix(in srgb, ${nivelColor[d.nivel]} 16%, transparent);color:${nivelColor[d.nivel]};">
                    ${nivelNombre[d.nivel]}
                </span>
            </td>
        </tr>`;
    }
    Swal.fire({
        title: portalAlertEscape(info.rol),
        html: `
            <div style="text-align:left;font-size:.8rem;color:var(--text-muted,#666);margin-bottom:10px;">
                Detalle de acceso en <strong>${portalAlertEscape(info.modulo)}</strong>
            </div>
            <div style="max-height:360px;overflow:auto;border:1px solid rgba(128,128,128,.25);border-radius:8px;">
                <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
                    <tbody>${filas}</tbody>
                </table>
            </div>
        `,
        width: 480,
        confirmButtonText: 'Cerrar',
    });
}
</script>
