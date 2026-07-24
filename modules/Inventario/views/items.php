<?php $success = SessionHelper::getFlash('success'); $error = SessionHelper::getFlash('error'); ?>
<style>
.itm-table { width:100%; border-collapse:collapse; }
.itm-table th { text-align:left; font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted,var(--color-text-muted)); padding:var(--sp-2) var(--sp-3); border-bottom:1px solid var(--border-app,var(--color-border)); }
.itm-table td { padding:var(--sp-2) var(--sp-3); border-bottom:1px solid var(--border-app,var(--color-border)); font-size:.85rem; }
.itm-group { font-weight:700; font-size:.8rem; color:#fd7e14; padding:var(--sp-3) var(--sp-3) 6px; text-transform:uppercase; letter-spacing:.04em; }
</style>

<div style="animation:pageFadeIn .3s ease both;">
<?php if ($success): ?><div class="alert alert-success" style="margin-bottom:var(--sp-3);"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger" style="margin-bottom:var(--sp-3);"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-cubes" style="color:#fd7e14;margin-right:var(--sp-2);"></i> Ítems del Sistema</h2>
        <p class="page-subtitle">Catálogo de productos del inventario (<?= count($items) ?>)</p>
    </div>
</div>

<form method="GET" action="<?= APP_URL ?>/inventario/items" style="margin-bottom:var(--sp-4);display:flex;gap:var(--sp-2);flex-wrap:wrap;">
    <input type="text" name="termino" value="<?= htmlspecialchars($termino, ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Buscar producto o código…" style="max-width:280px;">
    <select name="grupo_id" class="form-control" style="max-width:240px;">
        <option value="0">Todos los grupos</option>
        <?php foreach ($grupos as $g): ?><option value="<?= (int)$g['id'] ?>" <?= $grupoId === (int)$g['id'] ? 'selected' : '' ?>><?= htmlspecialchars($g['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Filtrar</button>
</form>

<div class="card"><div class="card-body" style="padding:0;">
<?php if (empty($itemsPorGrupo)): ?>
    <div style="text-align:center;padding:var(--sp-8) 0;color:var(--text-muted,var(--color-text-muted));"><i class="fa-solid fa-box-open" style="font-size:1.8rem;opacity:.4;display:block;margin-bottom:8px;"></i>Sin productos.</div>
<?php else: foreach ($itemsPorGrupo as $grupo => $lista): ?>
    <div class="itm-group"><?= htmlspecialchars($grupo, ENT_QUOTES, 'UTF-8') ?></div>
    <table class="itm-table">
        <thead><tr><th>Código</th><th>Producto</th><th>Unidad</th><th style="text-align:center;">IVA</th><th style="text-align:right;">Precio prom.</th><th style="text-align:right;">Existencia</th></tr></thead>
        <tbody>
        <?php foreach ($lista as $it): ?>
            <tr>
                <td style="font-family:var(--font-mono,monospace);font-size:.8rem;color:var(--text-muted,var(--color-text-muted));"><?= htmlspecialchars($it['codigo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><strong><?= htmlspecialchars($it['nombre'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td style="font-size:.82rem;"><?= htmlspecialchars($it['unidad_nombre'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td style="text-align:center;"><span class="badge <?= (int)($it['aplica_iva'] ?? 0) ? 'badge-success':'badge-secondary' ?>"><?= (int)($it['aplica_iva'] ?? 0) ? 'Sí':'No' ?></span></td>
                <td style="text-align:right;">$<?= number_format((float)($it['precio_promedio'] ?? 0), 2) ?></td>
                <td style="text-align:right;font-weight:600;"><?= number_format((float)($it['existencia_actual'] ?? 0), 0) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; endif; ?>
</div></div>
</div>
