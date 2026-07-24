<?php
$success = SessionHelper::getFlash('success'); $error = SessionHelper::getFlash('error');
$labels = [
    'categorias'=>'Categorías','zonas'=>'Zonas','estados'=>'Estados','marcas'=>'Marcas','lineas'=>'Líneas',
    'unidades'=>'Unidades','tipos_iva'=>'Tipos de IVA','productos'=>'Productos','proveedores'=>'Proveedores',
    'grupo_centros_consumo'=>'Grupos C. Consumo','centros_consumo'=>'Centros de Consumo',
];
$iconos = [
    'categorias'=>'fa-layer-group','zonas'=>'fa-location-dot','estados'=>'fa-traffic-light','marcas'=>'fa-tag','lineas'=>'fa-ship',
    'unidades'=>'fa-ruler','tipos_iva'=>'fa-percent','productos'=>'fa-cube','proveedores'=>'fa-truck-field',
    'grupo_centros_consumo'=>'fa-sitemap','centros_consumo'=>'fa-building-user',
];
$esEspecial = in_array($tablaActiva, ['productos','tipos_iva','centros_consumo','grupo_centros_consumo','estados'], true);
?>
<style>
.mst-tabs { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:var(--sp-4); }
.mst-tab { display:inline-flex; align-items:center; gap:7px; padding:8px 14px; border-radius:10px; border:1px solid var(--border-app,var(--color-border));
    background:var(--surface-app,var(--color-surface,#fff)); color:var(--text-muted,var(--color-text-muted)); font-size:.8rem; font-weight:600; cursor:pointer; text-decoration:none; transition:all .15s; }
.mst-tab:hover { border-color:#fd7e14; color:#fd7e14; }
.mst-tab.on { background:#fd7e14; color:#fff; border-color:#fd7e14; }
.mst-tab .badge-count { background:rgba(0,0,0,.12); border-radius:10px; padding:1px 7px; font-size:.7rem; }
.mst-table { width:100%; border-collapse:collapse; }
.mst-table th { text-align:left; font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted,var(--color-text-muted)); padding:var(--sp-2) var(--sp-3); border-bottom:1px solid var(--border-app,var(--color-border)); }
.mst-table td { padding:var(--sp-2) var(--sp-3); border-bottom:1px solid var(--border-app,var(--color-border)); font-size:.85rem; }
</style>

<div style="animation:pageFadeIn .3s ease both;">
<?php if ($success): ?><div class="alert alert-success" style="margin-bottom:var(--sp-3);"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" style="margin-bottom:var(--sp-3);"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-database" style="color:#fd7e14;margin-right:var(--sp-2);"></i> Maestros del Inventario</h2>
        <p class="page-subtitle">Arquitectura de datos: categorías, zonas, estados, productos y más</p>
    </div>
</div>

<div class="mst-tabs">
    <?php foreach ($labels as $key => $lbl): ?>
    <a class="mst-tab <?= $tablaActiva === $key ? 'on':'' ?>" href="<?= APP_URL ?>/inventario/maestros?tabla=<?= $key ?>" data-spa>
        <i class="fa-solid <?= $iconos[$key] ?>"></i> <?= $lbl ?> <span class="badge-count"><?= (int)($conteos[$key] ?? 0) ?></span>
    </a>
    <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:var(--sp-4);align-items:start;">
    <!-- Tabla -->
    <div class="card"><div class="card-body" style="padding:0;overflow-x:auto;">
        <table class="mst-table">
            <thead><tr>
                <th>ID</th><th>Nombre</th>
                <?php if ($tablaActiva==='categorias'): ?><th>Código</th><?php endif; ?>
                <?php if ($tablaActiva==='tipos_iva'): ?><th>Tasa</th><?php endif; ?>
                <?php if ($tablaActiva==='proveedores'): ?><th>RUC</th><?php endif; ?>
                <?php if ($tablaActiva==='productos'): ?><th>Grupo</th><th>Unidad</th><?php endif; ?>
                <?php if (!$esEspecial || $tablaActiva==='estados'): ?><th>Detalle</th><?php endif; ?>
                <th></th>
            </tr></thead>
            <tbody>
            <?php if (empty($registros)): ?>
                <tr><td colspan="6" style="text-align:center;padding:var(--sp-6);color:var(--text-muted,var(--color-text-muted));">Sin registros.</td></tr>
            <?php else: foreach ($registros as $r): ?>
                <tr>
                    <td class="inv-mono" style="color:var(--text-muted,var(--color-text-muted));font-family:var(--font-mono,monospace);"><?= (int)$r['id'] ?></td>
                    <td><strong><?= htmlspecialchars($r['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <?php if ($tablaActiva==='categorias'): ?><td style="font-family:var(--font-mono,monospace);font-size:.8rem;"><?= htmlspecialchars($r['codigo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td><?php endif; ?>
                    <?php if ($tablaActiva==='tipos_iva'): ?><td><span class="badge badge-success"><?= number_format((float)($r['tasa_iva'] ?? 0),0) ?>%</span></td><?php endif; ?>
                    <?php if ($tablaActiva==='proveedores'): ?><td style="font-family:var(--font-mono,monospace);font-size:.8rem;"><?= htmlspecialchars($r['ruc'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td><?php endif; ?>
                    <?php if ($tablaActiva==='productos'): ?><td style="font-size:.82rem;"><?= htmlspecialchars($r['grupo_nombre'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td><td style="font-size:.82rem;"><?= htmlspecialchars($r['unidad_nombre'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td><?php endif; ?>
                    <?php if (!$esEspecial || $tablaActiva==='estados'): ?><td style="font-size:.8rem;color:var(--text-muted,var(--color-text-muted));"><?= htmlspecialchars($r['extra'] ?? '', ENT_QUOTES, 'UTF-8') ?></td><?php endif; ?>
                    <td style="text-align:right;">
                        <form method="POST" action="<?= APP_URL ?>/inventario/maestros/eliminar" style="display:inline;" onsubmit="return confirm('¿Eliminar este registro maestro?');">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="tabla" value="<?= htmlspecialchars($tablaActiva, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button class="btn btn-ghost btn-sm" title="Eliminar" style="color:var(--color-danger,#dc3545);"><i class="fa-solid fa-trash-can"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div></div>

    <!-- Form alta rápida -->
    <div class="card"><div class="card-body">
        <h3 style="font-size:.92rem;font-weight:700;margin-bottom:var(--sp-3);"><i class="fa-solid fa-plus" style="color:#fd7e14;"></i> Agregar a <?= htmlspecialchars($labels[$tablaActiva], ENT_QUOTES, 'UTF-8') ?></h3>
        <form method="POST" action="<?= APP_URL ?>/inventario/maestros/guardar" style="display:flex;flex-direction:column;gap:var(--sp-3);">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="tabla" value="<?= htmlspecialchars($tablaActiva, ENT_QUOTES, 'UTF-8') ?>">
            <div><label class="form-label">Nombre *</label><input type="text" name="nombre" class="form-control" required></div>

            <?php if ($tablaActiva==='categorias'): ?>
                <div><label class="form-label">Código</label><input type="text" name="codigo" class="form-control" placeholder="1.3.1.01.0X."></div>
            <?php elseif ($tablaActiva==='tipos_iva'): ?>
                <div><label class="form-label">Tasa IVA (%) *</label><input type="number" step="0.01" name="tasa_iva" class="form-control" required></div>
            <?php elseif ($tablaActiva==='proveedores'): ?>
                <div><label class="form-label">RUC *</label><input type="text" name="ruc" class="form-control" required></div>
            <?php elseif ($tablaActiva==='productos'): ?>
                <div><label class="form-label">Grupo *</label><select name="grupo_id" class="form-control" required>
                    <?php foreach ($categorias as $c): ?><option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select></div>
                <div><label class="form-label">Unidad *</label><select name="unidad_id" class="form-control" required>
                    <?php foreach ($unidades as $u): ?><option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select></div>
                <div><label class="form-label" style="display:flex;align-items:center;gap:8px;"><input type="checkbox" name="aplica_iva" value="1" checked> Aplica IVA</label></div>
            <?php endif; ?>

            <?php if (!in_array($tablaActiva, ['productos','centros_consumo','grupo_centros_consumo'], true)): ?>
                <div><label class="form-label"><?= $tablaActiva==='estados' ? 'Detalle' : 'Descripción / Extra' ?></label><input type="text" name="extra" class="form-control"></div>
            <?php endif; ?>

            <button class="btn btn-primary" style="justify-content:center;"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
            <p style="font-size:.72rem;color:var(--text-muted,var(--color-text-muted));margin:0;">Para editar, elimina y vuelve a crear, o usa el módulo completo.</p>
        </form>
    </div></div>
</div>
</div>
