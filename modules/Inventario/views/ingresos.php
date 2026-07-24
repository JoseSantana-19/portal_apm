<div style="animation:pageFadeIn .3s ease both;">
<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-arrow-right-to-bracket" style="color:#28a745;margin-right:var(--sp-2);"></i> Ingresos de Bodega</h2>
        <p class="page-subtitle">Entradas de mercadería y bienes al inventario</p>
    </div>
    <a href="<?= APP_URL ?>/inventario/egresos" class="btn btn-outline btn-sm" data-spa><i class="fa-solid fa-arrow-right-from-bracket"></i> Ver egresos</a>
</div>

<div style="display:flex;gap:var(--sp-3);margin-bottom:var(--sp-4);flex-wrap:wrap;">
    <?php foreach ([['fa-arrow-right-to-bracket',$stats['ingresos']??0,'Ingresos','#28a745'],['fa-arrow-right-from-bracket',$stats['egresos']??0,'Egresos','#dc3545'],['fa-truck-field',$stats['proveedores']??0,'Proveedores','#0056b3']] as [$ico,$n,$l,$c]): ?>
    <div class="card" style="flex:1;min-width:150px;"><div class="card-body" style="display:flex;align-items:center;gap:var(--sp-3);">
        <div style="width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,<?= $c ?> 13%,transparent);color:<?= $c ?>;"><i class="fa-solid <?= $ico ?>"></i></div>
        <div><div style="font-size:1.3rem;font-weight:800;line-height:1;"><?= (int)$n ?></div><div style="font-size:.7rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));"><?= $l ?></div></div>
    </div></div>
    <?php endforeach; ?>
</div>

<div class="card"><div class="card-body" style="padding:0;overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;">
        <thead><tr>
            <?php foreach (['Secuencial','Proveedor','Fecha','Responsable','Ítems','Total','Creado por'] as $h): ?>
            <th style="text-align:left;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted,var(--color-text-muted));padding:var(--sp-2) var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));"><?= $h ?></th>
            <?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php if (empty($ingresos)): ?>
            <tr><td colspan="7" style="text-align:center;padding:var(--sp-8);color:var(--text-muted,var(--color-text-muted));"><i class="fa-solid fa-inbox" style="font-size:1.6rem;opacity:.4;display:block;margin-bottom:8px;"></i>No hay ingresos registrados.</td></tr>
        <?php else: foreach ($ingresos as $g): ?>
            <tr style="border-bottom:1px solid var(--border-app,var(--color-border));font-size:.85rem;">
                <td style="padding:var(--sp-2) var(--sp-3);font-family:var(--font-mono,monospace);color:#28a745;font-weight:600;"><?= htmlspecialchars($g['secuencial'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><strong><?= htmlspecialchars($g['proveedor'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td style="padding:var(--sp-2) var(--sp-3);color:var(--text-muted,var(--color-text-muted));"><?= htmlspecialchars(is_string($g['fecha']) ? $g['fecha'] : ($g['fecha'] instanceof DateTime ? $g['fecha']->format('Y-m-d') : ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><?= htmlspecialchars($g['responsable_nombre'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><span class="badge badge-secondary"><?= (int)($g['num_items'] ?? 0) ?></span></td>
                <td style="padding:var(--sp-2) var(--sp-3);font-weight:700;color:#28a745;">$<?= number_format((float)($g['total'] ?? 0), 2) ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);font-size:.78rem;color:var(--text-muted,var(--color-text-muted));"><?= htmlspecialchars($g['creado_por'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div></div>
</div>
