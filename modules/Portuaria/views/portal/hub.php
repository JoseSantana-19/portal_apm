<?php
/** Hub nativo del módulo Portuaria (Control de Acceso) — se renderiza en el shell del portal. */
$fmt = function ($v, string $f = 'H:i') {
    if ($v instanceof DateTimeInterface) return $v->format($f);
    return is_string($v) && $v !== '' ? $v : '—';
};
?>
<div style="animation:pageFadeIn .3s ease both;">

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-anchor" style="color:#0891b2;margin-right:var(--sp-2);"></i> Portuaria — Control de Acceso</h2>
        <p class="page-subtitle">Bitácoras de visitas, rondas de seguridad y CCTV · Autoridad Portuaria de Manta</p>
    </div>
    <a href="<?= APP_URL ?>/visitas" class="btn btn-primary btn-sm"><i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir módulo completo</a>
</div>

<!-- KPIs en vivo -->
<div style="display:flex;gap:var(--sp-3);margin-bottom:var(--sp-4);flex-wrap:wrap;">
    <?php foreach ([
        ['fa-person-walking-arrow-right', $stats['visitas_hoy'],     'Visitas hoy',        '#0891b2'],
        ['fa-user-clock',                 $stats['visitas_activas'], 'Visitas activas',    '#28a745'],
        ['fa-clipboard-check',            $stats['rondas_hoy'],      'Rondas hoy',         '#6f42c1'],
        ['fa-triangle-exclamation',       $stats['alertas_24h'],     'Alertas críticas 24h', $stats['alertas_24h'] > 0 ? '#dc3545' : '#6c757d'],
    ] as [$ico, $n, $l, $c]): ?>
    <div class="card" style="flex:1;min-width:160px;"><div class="card-body" style="display:flex;align-items:center;gap:var(--sp-3);">
        <div style="width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,<?= $c ?> 13%,transparent);color:<?= $c ?>;"><i class="fa-solid <?= $ico ?>"></i></div>
        <div><div style="font-size:1.45rem;font-weight:800;line-height:1;"><?= (int)$n ?></div><div style="font-size:.7rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));"><?= $l ?></div></div>
    </div></div>
    <?php endforeach; ?>
</div>

<!-- Mini-gráficos en vivo -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-4);margin-bottom:var(--sp-4);" class="hub-grid-2">
    <div class="card"><div class="card-body">
        <h3 style="font-size:.9rem;font-weight:700;margin:0 0 var(--sp-3);"><i class="fa-solid fa-chart-column" style="color:#0891b2;margin-right:6px;"></i>Visitas por día · última semana</h3>
        <?= apm_chart_cols($chartDias ?? [], '#0891b2') ?>
    </div></div>
    <div class="card"><div class="card-body">
        <h3 style="font-size:.9rem;font-weight:700;margin:0 0 var(--sp-3);"><i class="fa-solid fa-location-dot" style="color:#6f42c1;margin-right:6px;"></i>Destinos más visitados · 30 días</h3>
        <?= apm_chart_bars($chartDestinos ?? [], '#6f42c1') ?>
    </div></div>
</div>

<!-- Accesos al módulo -->
<div class="card" style="margin-bottom:var(--sp-4);">
    <div class="card-body">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-3);">
            <h3 style="font-size:.95rem;font-weight:700;margin:0;"><i class="fa-solid fa-compass" style="color:#0891b2;margin-right:6px;"></i>Secciones del módulo</h3>
            <span style="font-size:.72rem;color:var(--text-muted,var(--color-text-muted));">Se abren en la interfaz operativa del módulo</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(215px,1fr));gap:var(--sp-3);">
            <?php foreach ([
                ['/visitas/registrar',   'fa-person-circle-plus', '#28a745', 'Registrar Ingreso',    'Alta de visitas y proveedores'],
                ['/visitas',             'fa-list-ul',            '#0891b2', 'Listado de Visitas',   'Consulta, salida y edición'],
                ['/rondas',              'fa-clipboard-check',    '#6f42c1', 'Bitácora de Rondas',   'Turnos y novedades de guardias'],
                ['/camaras',             'fa-camera',             '#fd7e14', 'Bitácora CCTV',        'Registro de monitoreo de cámaras'],
                ['/camaras/inventario',  'fa-server',             '#17a2b8', 'Maestro de Cámaras',   'Inventario técnico CCTV'],
                ['/catalogos',           'fa-database',           '#0056b3', 'Catálogos Maestros',   'Personas, empresas, destinos…'],
                ['/bit_dashboard_jefe.php', 'fa-chart-line',      '#e83e8c', 'Panel Jefatura',       'Estadísticas en tiempo real'],
                ['/bit_reporte_diario_supervisor.php', 'fa-file-lines', '#20c997', 'Reporte Supervisor', 'Novedades diarias por turno'],
                ['/dashboard-ejecutivo', 'fa-chart-pie',          '#6610f2', 'Dashboard Ejecutivo',  'Analítica avanzada (Python)'],
            ] as [$url, $ico, $c, $t, $d]): ?>
            <a href="<?= APP_URL . $url ?>" class="card" style="text-decoration:none;color:inherit;border:1px solid var(--border-app,var(--color-border));transition:transform .15s ease, box-shadow .15s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
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
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:var(--sp-4);align-items:start;" class="hub-grid-2">
    <!-- Últimas visitas -->
    <div class="card">
        <div class="card-body" style="padding:0;">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));">
                <h3 style="font-size:.95rem;font-weight:700;margin:0;"><i class="fa-solid fa-clock-rotate-left" style="color:#0891b2;margin-right:6px;"></i>Últimas visitas</h3>
                <a href="<?= APP_URL ?>/portuaria/visitas-resumen" class="btn btn-outline btn-sm" data-spa>Ver vista rápida</a>
            </div>
            <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr>
                    <?php foreach (['Visitante','Identificación','Empresa','Fecha','Entrada','Salida'] as $h): ?>
                    <th style="text-align:left;font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted,var(--color-text-muted));padding:var(--sp-2) var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));"><?= $h ?></th>
                    <?php endforeach; ?>
                </tr></thead>
                <tbody>
                <?php if (empty($ultimas)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:var(--sp-8);color:var(--text-muted,var(--color-text-muted));"><i class="fa-solid fa-inbox" style="font-size:1.6rem;opacity:.4;display:block;margin-bottom:8px;"></i>Sin visitas registradas.</td></tr>
                <?php else: foreach ($ultimas as $v): ?>
                    <tr style="border-bottom:1px solid var(--border-app,var(--color-border));font-size:.84rem;">
                        <td style="padding:var(--sp-2) var(--sp-3);"><strong><?= htmlspecialchars($v['nombre_visitante'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td style="padding:var(--sp-2) var(--sp-3);font-family:var(--font-mono,monospace);"><?= htmlspecialchars($v['nidentificacion'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:var(--sp-2) var(--sp-3);color:var(--text-muted,var(--color-text-muted));"><?= htmlspecialchars($v['empresa'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:var(--sp-2) var(--sp-3);"><?= $fmt($v['fecha_visita'], 'Y-m-d') ?></td>
                        <td style="padding:var(--sp-2) var(--sp-3);"><?= $fmt($v['hora_entrada']) ?></td>
                        <td style="padding:var(--sp-2) var(--sp-3);">
                            <?php if (empty($v['hora_salida'])): ?>
                                <span class="badge" style="background:color-mix(in srgb,#28a745 15%,transparent);color:#28a745;font-size:.7rem;padding:2px 8px;border-radius:20px;font-weight:700;">EN PUERTO</span>
                            <?php else: ?>
                                <?= $fmt($v['hora_salida']) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <!-- Panel lateral: recursos + vistas rápidas -->
    <div style="display:flex;flex-direction:column;gap:var(--sp-4);">
        <div class="card"><div class="card-body">
            <h3 style="font-size:.95rem;font-weight:700;margin:0 0 var(--sp-3);"><i class="fa-solid fa-bolt" style="color:#fd7e14;margin-right:6px;"></i>Vistas rápidas (en el portal)</h3>
            <div style="display:flex;flex-direction:column;gap:var(--sp-2);">
                <a href="<?= APP_URL ?>/portuaria/visitas-resumen" data-spa class="btn btn-outline" style="justify-content:flex-start;"><i class="fa-solid fa-list-check"></i> Resumen de visitas</a>
                <a href="<?= APP_URL ?>/portuaria/actividad" data-spa class="btn btn-outline" style="justify-content:flex-start;"><i class="fa-solid fa-shield-halved"></i> Actividad de seguridad</a>
            </div>
        </div></div>

        <div class="card"><div class="card-body">
            <h3 style="font-size:.95rem;font-weight:700;margin:0 0 var(--sp-3);"><i class="fa-solid fa-layer-group" style="color:#0891b2;margin-right:6px;"></i>Datos del módulo</h3>
            <?php foreach ([
                ['Cámaras activas (maestro)', $stats['camaras_activas'], 'fa-video'],
                ['Registros CCTV hoy',        $stats['cctv_hoy'],        'fa-camera'],
                ['Personas en catálogo',      $stats['personas'],        'fa-id-card'],
                ['Empresas registradas',      $stats['empresas'],        'fa-building'],
            ] as [$l, $n, $ico]): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:var(--sp-2) 0;border-bottom:1px dashed var(--border-app,var(--color-border));font-size:.83rem;">
                <span style="color:var(--text-muted,var(--color-text-muted));"><i class="fa-solid <?= $ico ?>" style="width:18px;margin-right:6px;"></i><?= $l ?></span>
                <strong><?= (int)$n ?></strong>
            </div>
            <?php endforeach; ?>
            <div style="font-size:.7rem;color:var(--text-muted,var(--color-text-muted));margin-top:var(--sp-2);">BD PortuariaDemo · en vivo</div>
        </div></div>
    </div>
</div>

<style>
@media (max-width: 900px) { .hub-grid-2 { grid-template-columns: 1fr !important; } }
</style>
</div>
