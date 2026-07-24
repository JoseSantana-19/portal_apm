<?php
/** Panel de Control de Bienes — hub nativo (shell del portal). */
$fmt = function ($v, string $f = 'Y-m-d') {
    if ($v instanceof DateTimeInterface) return $v->format($f);
    return is_string($v) && $v !== '' ? $v : '—';
};
?>
<div style="animation:pageFadeIn .3s ease both;">

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-boxes-stacked" style="color:#fd7e14;margin-right:var(--sp-2);"></i> Control de Bienes — Panel</h2>
        <p class="page-subtitle">Inventario, bodega y configuración · BD inventario en vivo</p>
    </div>
    <a href="<?= APP_URL ?>/apps/control_bienes/" data-no-spa class="btn btn-primary btn-sm"><i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir Sistema de Control de Bienes</a>
</div>

<!-- KPIs desde la BD del módulo -->
<div style="display:flex;gap:var(--sp-3);margin-bottom:var(--sp-4);flex-wrap:wrap;">
    <?php foreach ([
        ['fa-boxes-stacked',              $stats['bienes'],      'Bienes activos',  '#fd7e14'],
        ['fa-cubes',                      $stats['productos'],   'Productos',       '#0056b3'],
        ['fa-arrow-right-to-bracket',     $stats['ingresos'],    'Ingresos bodega', '#28a745'],
        ['fa-arrow-right-from-bracket',   $stats['egresos'],     'Egresos bodega',  '#dc3545'],
        ['fa-truck-field',                $stats['proveedores'], 'Proveedores',     '#17a2b8'],
    ] as [$ico, $n, $l, $c]): ?>
    <div class="card" style="flex:1;min-width:145px;"><div class="card-body" style="display:flex;align-items:center;gap:var(--sp-3);">
        <div style="width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,<?= $c ?> 13%,transparent);color:<?= $c ?>;"><i class="fa-solid <?= $ico ?>"></i></div>
        <div><div style="font-size:1.35rem;font-weight:800;line-height:1;"><?= (int)$n ?></div><div style="font-size:.68rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));"><?= $l ?></div></div>
    </div></div>
    <?php endforeach; ?>
    <div class="card" style="flex:1;min-width:170px;"><div class="card-body" style="display:flex;align-items:center;gap:var(--sp-3);">
        <div style="width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,#6f42c1 13%,transparent);color:#6f42c1;"><i class="fa-solid fa-sack-dollar"></i></div>
        <div><div style="font-size:1.2rem;font-weight:800;line-height:1;">$<?= number_format((float)$stats['valor_total'], 2) ?></div><div style="font-size:.68rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));">Valor inventariado</div></div>
    </div></div>
</div>

<!-- Mini-gráficos en vivo -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-4);margin-bottom:var(--sp-4);" class="hub-grid-2">
    <div class="card"><div class="card-body">
        <h3 style="font-size:.9rem;font-weight:700;margin:0 0 var(--sp-3);"><i class="fa-solid fa-layer-group" style="color:#fd7e14;margin-right:6px;"></i>Bienes activos por categoría</h3>
        <?= apm_chart_bars($chartCategorias ?? [], '#fd7e14') ?>
    </div></div>
    <div class="card"><div class="card-body">
        <h3 style="font-size:.9rem;font-weight:700;margin:0 0 var(--sp-3);"><i class="fa-solid fa-location-dot" style="color:#17a2b8;margin-right:6px;"></i>Bienes activos por zona</h3>
        <?= apm_chart_bars($chartZonas ?? [], '#17a2b8') ?>
    </div></div>
</div>

<!-- Accesos a secciones -->
<div class="card" style="margin-bottom:var(--sp-4);"><div class="card-body">
    <h3 style="font-size:.95rem;font-weight:700;margin:0 0 var(--sp-3);"><i class="fa-solid fa-compass" style="color:#fd7e14;margin-right:6px;"></i>Secciones del sistema</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(215px,1fr));gap:var(--sp-3);">
        <?php foreach ([
            ['/apps/control_bienes/index.php?route=inv_maestros&tabla=busqueda_global', 'fa-magnifying-glass', '#343a40', 'Búsqueda Global', 'Buscador inteligente del sistema'],
            ['/apps/control_bienes/index.php?route=inventario',        'fa-ship',          '#fd7e14', 'Inventario General', 'Bienes registrados y su detalle'],
            ['/apps/control_bienes/index.php?route=items',             'fa-box',           '#0056b3', 'Catálogo de Ítems',  'Catálogo visual por grupos'],
            ['/apps/control_bienes/index.php?route=inv_items_sistema', 'fa-cubes',         '#6f42c1', 'Ítems del Sistema',  'Productos, existencias, historial'],
            ['/apps/control_bienes/index.php?route=reportes',          'fa-chart-pie',     '#e83e8c', 'Reportes Varios',    'Listados, actas y PDF'],
            ['/apps/control_bienes/index.php?route=inv_maestros',      'fa-layer-group',   '#17a2b8', 'Maestros',           'Grupos, productos, unidades e IVA'],
            ['/apps/control_bienes/index.php?route=inv_periodos',      'fa-calendar-days', '#20c997', 'Períodos e IVA',     'Ejercicios y tarifas'],
            ['/apps/control_bienes/index.php?route=talento',           'fa-users-gear',    '#28a745', 'Talento Humano',     'Directorio y reasignación de áreas'],
            ['/apps/control_bienes/index.php?route=inv_secuenciales',  'fa-list-ol',       '#6610f2', 'Secuenciales',       'Contadores automáticos'],
            ['/apps/control_bienes/index.php?route=inv_bitacora',      'fa-clock-rotate-left','#dc3545','Bitácora del Sistema','Log de auditoría completo'],
            ['/apps/control_bienes/index.php?route=usuarios',          'fa-user-shield',   '#8b5cf6', 'Gestión de Usuarios','Control de acceso y roles'],
        ] as [$url, $ico, $c, $t, $d]): ?>
        <a href="<?= APP_URL . $url ?>" data-no-spa class="card" style="text-decoration:none;color:inherit;border:1px solid var(--border-app,var(--color-border));transition:transform .15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
            <div class="card-body" style="display:flex;gap:var(--sp-3);align-items:center;padding:var(--sp-3);">
                <div style="width:38px;height:38px;flex:none;border-radius:10px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,<?= $c ?> 13%,transparent);color:<?= $c ?>;"><i class="fa-solid <?= $ico ?>"></i></div>
                <div style="min-width:0;">
                    <div style="font-weight:700;font-size:.85rem;line-height:1.2;"><?= $t ?></div>
                    <div style="font-size:.72rem;color:var(--text-muted,var(--color-text-muted));white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $d ?></div>
                </div>
                <i class="fa-solid fa-chevron-right" style="margin-left:auto;font-size:.7rem;color:var(--text-muted,var(--color-text-muted));"></i>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div></div>

<!-- Últimos bienes -->
<div class="card"><div class="card-body" style="padding:0;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));">
        <h3 style="font-size:.95rem;font-weight:700;margin:0;"><i class="fa-solid fa-clock-rotate-left" style="color:#fd7e14;margin-right:6px;"></i>Últimos bienes registrados</h3>
        <a href="<?= APP_URL ?>/apps/control_bienes/index.php?route=inventario" class="btn btn-outline btn-sm" data-no-spa>Ver inventario</a>
    </div>
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;">
        <thead><tr>
            <?php foreach (['Secuencial','Bien','Marca','Categoría','Zona','Cant.','Valor','Registro'] as $h): ?>
            <th style="text-align:left;font-size:.7rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));padding:var(--sp-2) var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));"><?= $h ?></th>
            <?php endforeach; ?>
        </tr></thead>
        <tbody>
        <?php if (empty($bienes)): ?>
            <tr><td colspan="8" style="text-align:center;padding:var(--sp-8);color:var(--text-muted,var(--color-text-muted));">Sin bienes registrados.</td></tr>
        <?php else: foreach ($bienes as $b): ?>
            <tr style="border-bottom:1px solid var(--border-app,var(--color-border));font-size:.84rem;">
                <td style="padding:var(--sp-2) var(--sp-3);font-family:var(--font-mono,monospace);color:#fd7e14;font-weight:600;"><?= htmlspecialchars($b['secuencial'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><strong><?= htmlspecialchars($b['nombre'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td style="padding:var(--sp-2) var(--sp-3);color:var(--text-muted,var(--color-text-muted));"><?= htmlspecialchars($b['marca'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><?= htmlspecialchars($b['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><?= htmlspecialchars($b['zona'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);"><?= (int)($b['cantidad'] ?? 0) ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);font-weight:700;color:#28a745;">$<?= number_format((float)($b['valor'] ?? 0), 2) ?></td>
                <td style="padding:var(--sp-2) var(--sp-3);color:var(--text-muted,var(--color-text-muted));"><?= $fmt($b['fecha_registro']) ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
</div></div>
</div>

<style>
@media (max-width: 900px) { .hub-grid-2 { grid-template-columns: 1fr !important; } }
</style>
