<?php
$v = fn(array $arr, string $key, $default = 0) => $arr[$key] ?? $default;
$porCategoria = array_map(fn($c) => ['label' => $c['categoria'] ?? 'Sin categoría', 'value' => (int)$c['total']], $kpis['por_categoria'] ?? []);
?>
<div style="animation:pageFadeIn .3s ease both;">

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-boxes-stacked" style="color:#10B981;margin-right:var(--sp-2);"></i> Control de Bienes</h2>
        <p class="page-subtitle">Inventario institucional · datos en vivo desde la BD inventario</p>
    </div>
    <a href="<?= APP_URL ?>/apps/control_bienes/" class="btn btn-primary btn-sm" data-no-spa>
        <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir módulo completo
    </a>
</div>

<!-- KPIs en vivo -->
<div style="display:flex;gap:var(--sp-3);margin-bottom:var(--sp-4);flex-wrap:wrap;">
    <?php foreach ([
        ['fa-boxes-stacked',      $v($kpis, 'total'),          'Total de bienes',   '#10B981'],
        ['fa-circle-check',       $v($kpis, 'operativos'),     'Operativos',        '#0284C7'],
        ['fa-screwdriver-wrench', $v($kpis, 'mantenimiento'),  'En mantenimiento',  '#F59E0B'],
        ['fa-dollar-sign',        null,                        'Valor total',       '#8B5CF6'],
    ] as [$ico, $n, $l, $c]): ?>
    <div class="card" style="flex:1;min-width:160px;"><div class="card-body" style="display:flex;align-items:center;gap:var(--sp-3);">
        <div style="width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,<?= $c ?> 13%,transparent);color:<?= $c ?>;"><i class="fa-solid <?= $ico ?>"></i></div>
        <div><div style="font-size:1.45rem;font-weight:800;line-height:1;"><?= $n === null ? '$' . number_format($v($kpis, 'valor_total'), 2) : number_format((int)$n) ?></div><div style="font-size:.7rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));"><?= $l ?></div></div>
    </div></div>
    <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-4);align-items:start;" class="hub-grid-2">
    <div class="card">
        <div class="card-body">
            <h3 style="font-size:.95rem;font-weight:700;margin:0 0 var(--sp-3);"><i class="fa-solid fa-tags" style="color:#10B981;margin-right:6px;"></i>Bienes por Categoría</h3>
            <?= apm_chart_bars($porCategoria, '#10B981') ?>
        </div>
    </div>

    <div class="card"><div class="card-body">
        <h3 style="font-size:.95rem;font-weight:700;margin:0 0 var(--sp-3);"><i class="fa-solid fa-bolt" style="color:#fd7e14;margin-right:6px;"></i>Sobre este módulo</h3>
        <div style="display:flex;flex-direction:column;gap:var(--sp-2);">
            <p style="font-size:.82rem;color:var(--text-muted,var(--color-text-muted));margin:0 0 var(--sp-2);">
                Control de Bienes es un módulo independiente (BD propia, app
                embebida) integrado al portal por sesión única — el catálogo
                completo, movimientos, ingresos y egresos viven en su sistema
                completo, no dentro de Portal APM.
            </p>
            <a href="<?= APP_URL ?>/apps/control_bienes/" data-no-spa class="btn btn-outline" style="justify-content:flex-start;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir Control de Bienes
            </a>
        </div>
    </div></div>
</div>

<style>
@media (max-width: 900px) { .hub-grid-2 { grid-template-columns: 1fr !important; } }
</style>
</div>
