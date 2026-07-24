<?php
/** Vista rápida de visitas (solo lectura) — shell del portal. */
$fmt = function ($v, string $f = 'H:i') {
    if ($v instanceof DateTimeInterface) return $v->format($f);
    return is_string($v) && $v !== '' ? $v : '—';
};
$activas = 0;
foreach ($visitas as $vv) { if (empty($vv['hora_salida'])) $activas++; }
?>
<div style="animation:pageFadeIn .3s ease both;">

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-list-check" style="color:#0891b2;margin-right:var(--sp-2);"></i> Visitas — Vista Rápida</h2>
        <p class="page-subtitle">Consulta de solo lectura sobre la bitácora de visitas portuarias</p>
    </div>
    <div style="display:flex;gap:var(--sp-2);">
        <a href="<?= APP_URL ?>/portuaria" class="btn btn-outline btn-sm" data-spa><i class="fa-solid fa-anchor"></i> Panel Portuario</a>
        <a href="<?= APP_URL ?>/visitas" class="btn btn-primary btn-sm"><i class="fa-solid fa-arrow-up-right-from-square"></i> Gestión completa</a>
    </div>
</div>

<div class="card" style="margin-bottom:var(--sp-4);"><div class="card-body">
    <form method="get" action="<?= APP_URL ?>/portuaria/visitas-resumen" style="display:flex;gap:var(--sp-3);flex-wrap:wrap;align-items:flex-end;">
        <div>
            <label style="display:block;font-size:.72rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));margin-bottom:4px;">Fecha</label>
            <input type="date" name="fecha" value="<?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?>" class="form-control" style="padding:8px 10px;border:1px solid var(--border-app,var(--color-border));border-radius:8px;background:var(--bg-app,transparent);color:var(--text-app,inherit);">
        </div>
        <div style="flex:1;min-width:220px;">
            <label style="display:block;font-size:.72rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));margin-bottom:4px;">Buscar (visitante, cédula, empresa, destino)</label>
            <input type="text" name="q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej.: Zambrano, 1301…, Logística" class="form-control" style="width:100%;padding:8px 10px;border:1px solid var(--border-app,var(--color-border));border-radius:8px;background:var(--bg-app,transparent);color:var(--text-app,inherit);">
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
        <?php if ($fecha !== '' || $q !== ''): ?>
            <a href="<?= APP_URL ?>/portuaria/visitas-resumen" class="btn btn-outline btn-sm" data-spa>Limpiar</a>
        <?php endif; ?>
    </form>
</div></div>

<div style="display:flex;gap:var(--sp-3);margin-bottom:var(--sp-4);flex-wrap:wrap;">
    <?php foreach ([
        ['fa-list-ul',    count($visitas),           'Resultados',   '#0891b2'],
        ['fa-user-clock', $activas,                  'Sin salida',   '#28a745'],
        ['fa-calendar',   $fecha !== '' ? $fecha : 'Todas', 'Fecha filtro', '#6c757d'],
    ] as [$ico, $n, $l, $c]): ?>
    <div class="card" style="flex:1;min-width:150px;"><div class="card-body" style="display:flex;align-items:center;gap:var(--sp-3);">
        <div style="width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,<?= $c ?> 13%,transparent);color:<?= $c ?>;"><i class="fa-solid <?= $ico ?>"></i></div>
        <div><div style="font-size:1.1rem;font-weight:800;line-height:1.2;"><?= htmlspecialchars((string)$n, ENT_QUOTES, 'UTF-8') ?></div><div style="font-size:.7rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));"><?= $l ?></div></div>
    </div></div>
    <?php endforeach; ?>
</div>

<div class="card"><div class="card-body" style="padding:0;overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;">
        <thead><tr>
            <?php foreach (['Visitante','Identificación','Tipo','Empresa','Destino','Motivo','Fecha','Entrada','Salida'] as $h): ?>
            <th style="text-align:left;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted,var(--color-text-muted));padding:var(--sp-2) var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));"><?= $h ?></th>
            <?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php if (empty($visitas)): ?>
            <tr><td colspan="9" style="text-align:center;padding:var(--sp-8);color:var(--text-muted,var(--color-text-muted));"><i class="fa-solid fa-inbox" style="font-size:1.6rem;opacity:.4;display:block;margin-bottom:8px;"></i>Sin resultados para el filtro aplicado.</td></tr>
        <?php else: foreach ($visitas as $v): ?>
            <tr style="border-bottom:1px solid var(--border-app,var(--color-border));font-size:.84rem;">
                <td style="padding:var(--sp-2) var(--sp-3);"><strong><?= htmlspecialchars($v['visitante'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td style="padding:var(--sp-2) var(--sp-3);font-family:var(--font-mono,monospace);"><?= htmlspecialchars($v['nidentificacion'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);color:var(--text-muted,var(--color-text-muted));"><?= htmlspecialchars($v['tipo_visitante'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><?= htmlspecialchars($v['empresa'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><?= htmlspecialchars($v['destino'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);color:var(--text-muted,var(--color-text-muted));max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($v['motivo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><?= $fmt($v['fecha_visita'], 'Y-m-d') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><?= $fmt($v['hora_entrada']) ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);">
                    <?php if (empty($v['hora_salida'])): ?>
                        <span style="background:color-mix(in srgb,#28a745 15%,transparent);color:#28a745;font-size:.7rem;padding:2px 8px;border-radius:20px;font-weight:700;">EN PUERTO</span>
                    <?php else: ?>
                        <?= $fmt($v['hora_salida']) ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div></div>
<p style="font-size:.72rem;color:var(--text-muted,var(--color-text-muted));margin-top:var(--sp-2);">Máximo 150 resultados. Para edición, salidas y exportaciones usar la <a href="<?= APP_URL ?>/visitas" style="color:#0891b2;">gestión completa del módulo</a>.</p>
</div>
