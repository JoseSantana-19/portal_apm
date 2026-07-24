<div style="animation:pageFadeIn .3s ease both;">
<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-arrow-right-from-bracket" style="color:#dc3545;margin-right:var(--sp-2);"></i> Egresos de Bodega</h2>
        <p class="page-subtitle">Salidas y entregas de bienes hacia áreas</p>
    </div>
    <a href="<?= APP_URL ?>/inventario/ingresos" class="btn btn-outline btn-sm" data-spa><i class="fa-solid fa-arrow-right-to-bracket"></i> Ver ingresos</a>
</div>

<div class="card"><div class="card-body" style="padding:0;overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;">
        <thead><tr>
            <?php foreach (['Secuencial','Área','Fecha','Responsable','Motivo','Ítems','Creado por'] as $h): ?>
            <th style="text-align:left;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted,var(--color-text-muted));padding:var(--sp-2) var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));"><?= $h ?></th>
            <?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php if (empty($egresos)): ?>
            <tr><td colspan="7" style="text-align:center;padding:var(--sp-8);color:var(--text-muted,var(--color-text-muted));"><i class="fa-solid fa-inbox" style="font-size:1.6rem;opacity:.4;display:block;margin-bottom:8px;"></i>No hay egresos registrados.</td></tr>
        <?php else: foreach ($egresos as $g): ?>
            <tr style="border-bottom:1px solid var(--border-app,var(--color-border));font-size:.85rem;">
                <td style="padding:var(--sp-2) var(--sp-3);font-family:var(--font-mono,monospace);color:#dc3545;font-weight:600;"><?= htmlspecialchars($g['secuencial'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><strong><?= htmlspecialchars($g['area_nombre'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td style="padding:var(--sp-2) var(--sp-3);color:var(--text-muted,var(--color-text-muted));"><?= htmlspecialchars(is_string($g['fecha']) ? $g['fecha'] : ($g['fecha'] instanceof DateTime ? $g['fecha']->format('Y-m-d') : ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><?= htmlspecialchars($g['responsable_nombre'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);font-size:.8rem;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($g['motivo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><span class="badge badge-secondary"><?= (int)($g['num_items'] ?? 0) ?></span></td>
                <td style="padding:var(--sp-2) var(--sp-3);font-size:.78rem;color:var(--text-muted,var(--color-text-muted));"><?= htmlspecialchars($g['creado_por'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div></div>
</div>
