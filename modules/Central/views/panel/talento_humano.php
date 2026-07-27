<?php
$v = fn(array $arr, string $key, $default = 0) => $arr[$key] ?? $default;
$porUnidad = array_map(fn($u) => ['label' => $u['nombre_unidad'] ?? '—', 'value' => (int)$u['total']], $kpis['por_unidad'] ?? []);
?>
<div style="animation:pageFadeIn .3s ease both;">

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-users" style="color:#0284C7;margin-right:var(--sp-2);"></i> Talento Humano</h2>
        <p class="page-subtitle">Personal de la Autoridad Portuaria de Manta · datos en vivo desde Talento_Humano</p>
    </div>
    <a href="<?= APP_URL ?>/apps/talento_humano/" class="btn btn-primary btn-sm" data-no-spa>
        <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir módulo completo
    </a>
</div>

<!-- KPIs en vivo -->
<div style="display:flex;gap:var(--sp-3);margin-bottom:var(--sp-4);flex-wrap:wrap;">
    <?php foreach ([
        ['fa-users',            $v($kpis, 'total'),      'Empleados activos', '#0284C7'],
        ['fa-user-plus',        $v($kpis, 'nuevos_mes'), 'Nuevos este mes',   '#10B981'],
        ['fa-mars',             $v($kpis, 'masculino'),  'Masculino',         '#3B82F6'],
        ['fa-venus',            $v($kpis, 'femenino'),   'Femenino',          '#EC4899'],
    ] as [$ico, $n, $l, $c]): ?>
    <div class="card" style="flex:1;min-width:160px;"><div class="card-body" style="display:flex;align-items:center;gap:var(--sp-3);">
        <div style="width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,<?= $c ?> 13%,transparent);color:<?= $c ?>;"><i class="fa-solid <?= $ico ?>"></i></div>
        <div><div style="font-size:1.45rem;font-weight:800;line-height:1;"><?= number_format((int)$n) ?></div><div style="font-size:.7rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));"><?= $l ?></div></div>
    </div></div>
    <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-4);align-items:start;" class="hub-grid-2">
    <div class="card">
        <div class="card-body">
            <h3 style="font-size:.95rem;font-weight:700;margin:0 0 var(--sp-3);"><i class="fa-solid fa-sitemap" style="color:#0284C7;margin-right:6px;"></i>Empleados por Unidad Organizacional</h3>
            <?= apm_chart_bars($porUnidad, '#0284C7') ?>
        </div>
    </div>

    <div class="card"><div class="card-body">
        <h3 style="font-size:.95rem;font-weight:700;margin:0 0 var(--sp-3);"><i class="fa-solid fa-bolt" style="color:#fd7e14;margin-right:6px;"></i>Sobre este módulo</h3>
        <div style="display:flex;flex-direction:column;gap:var(--sp-2);">
            <p style="font-size:.82rem;color:var(--text-muted,var(--color-text-muted));margin:0 0 var(--sp-2);">
                Talento Humano es un módulo independiente (BD propia, app embebida)
                integrado al portal por sesión única — el directorio completo,
                acciones de personal, contratos y trámites viven en su sistema
                completo, no dentro de Portal APM.
            </p>
            <a href="<?= APP_URL ?>/apps/talento_humano/" data-no-spa class="btn btn-outline" style="justify-content:flex-start;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir Talento Humano
            </a>
            <a href="<?= APP_URL ?>/admin/usuarios/desde-th" data-spa class="btn btn-outline" style="justify-content:flex-start;">
                <i class="fa-solid fa-user-plus"></i> Crear cuenta de portal desde un empleado
            </a>
        </div>
    </div></div>
</div>

<style>
@media (max-width: 900px) { .hub-grid-2 { grid-template-columns: 1fr !important; } }
</style>
</div>
