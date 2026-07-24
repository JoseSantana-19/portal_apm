<?php
/** Panel de Talento Humano — hub nativo (shell del portal). */
$fmt = function ($v, string $f = 'Y-m-d') {
    if ($v instanceof DateTimeInterface) return $v->format($f);
    return is_string($v) && $v !== '' ? $v : '—';
};
?>
<div style="animation:pageFadeIn .3s ease both;">

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-users" style="color:#e83e8c;margin-right:var(--sp-2);"></i> Talento Humano — Panel</h2>
        <p class="page-subtitle">Gestión del personal · BD Talento_Humano en vivo</p>
    </div>
    <a href="<?= APP_URL ?>/apps/talento_humano/" data-no-spa class="btn btn-primary btn-sm"><i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir Sistema de Talento Humano</a>
</div>

<!-- KPIs desde la BD del módulo -->
<div style="display:flex;gap:var(--sp-3);margin-bottom:var(--sp-4);flex-wrap:wrap;">
    <?php foreach ([
        ['fa-user-check',   $stats['empleados_activos'], 'Empleados activos',   '#e83e8c'],
        ['fa-sitemap',      $stats['unidades'],          'Unidades organizac.', '#6f42c1'],
        ['fa-id-badge',     $stats['puestos'],           'Puestos activos',     '#0056b3'],
        ['fa-file-signature', $stats['acciones'],        'Acciones de personal','#20c997'],
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
        <h3 style="font-size:.9rem;font-weight:700;margin:0 0 var(--sp-3);"><i class="fa-solid fa-sitemap" style="color:#e83e8c;margin-right:6px;"></i>Empleados activos por unidad</h3>
        <?= apm_chart_bars($chartUnidades ?? [], '#e83e8c') ?>
    </div></div>
    <div class="card"><div class="card-body">
        <h3 style="font-size:.9rem;font-weight:700;margin:0 0 var(--sp-3);"><i class="fa-solid fa-file-signature" style="color:#20c997;margin-right:6px;"></i>Acciones de personal · últimos 6 meses</h3>
        <?= apm_chart_cols($chartAcciones ?? [], '#20c997') ?>
    </div></div>
</div>

<!-- Accesos a secciones -->
<div class="card" style="margin-bottom:var(--sp-4);"><div class="card-body">
    <h3 style="font-size:.95rem;font-weight:700;margin:0 0 var(--sp-3);"><i class="fa-solid fa-compass" style="color:#e83e8c;margin-right:6px;"></i>Secciones del sistema</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(215px,1fr));gap:var(--sp-3);">
        <?php foreach ([
            ['/apps/talento_humano/talento-humano/inicio',          'fa-house',           '#0056b3', 'Inicio / Dashboard',     'Métricas de personal y alertas'],
            ['/apps/talento_humano/talento-humano/directorio',      'fa-address-book',    '#e83e8c', 'Directorio de Personal', 'Fichas, CRUD y perfiles'],
            ['/apps/talento_humano/talento-humano/empleado/crear',  'fa-user-plus',       '#28a745', 'Nuevo Empleado',         'Alta de servidores'],
            ['/apps/talento_humano/talento-humano/accion-personal', 'fa-file-signature',  '#20c997', 'Acción de Personal',     'LOSEP Art. 21 + PDF'],
            ['/apps/talento_humano/talento-humano/reporte',         'fa-chart-column',    '#6610f2', 'Historial / Reportes',   'Filtros y listados'],
            ['/apps/talento_humano/talento-humano/asistencia',      'fa-clock',           '#fd7e14', 'Asistencia',             'Control de jornadas'],
            ['/apps/talento_humano/talento-humano/vacaciones',      'fa-umbrella-beach',  '#17a2b8', 'Vacaciones',             'Solicitudes y saldos'],
            ['/apps/talento_humano/talento-humano/desempeno',       'fa-ranking-star',    '#6f42c1', 'Desempeño',              'Evaluaciones'],
            ['/apps/talento_humano/talento-humano/capacitacion',    'fa-graduation-cap',  '#343a40', 'Capacitación',           'Plan de formación'],
            ['/apps/talento_humano/admin/usuarios',                 'fa-user-shield',     '#dc3545', 'Admin: Usuarios',        'Seguridad del sistema TH'],
            ['/apps/talento_humano/admin/roles',                    'fa-key',             '#8b5cf6', 'Admin: Roles',           'Perfiles y permisos'],
            ['/apps/talento_humano/auditoria/logs',                 'fa-clock-rotate-left','#20c997','Auditoría',              'Logs y reportes de control'],
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

<div style="display:grid;grid-template-columns:3fr 2fr;gap:var(--sp-4);align-items:start;" class="hub-grid-2">
    <!-- Últimos empleados -->
    <div class="card"><div class="card-body" style="padding:0;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));">
            <h3 style="font-size:.95rem;font-weight:700;margin:0;"><i class="fa-solid fa-user-clock" style="color:#e83e8c;margin-right:6px;"></i>Últimos empleados</h3>
            <a href="<?= APP_URL ?>/apps/talento_humano/talento-humano/directorio" class="btn btn-outline btn-sm" data-no-spa>Ver directorio</a>
        </div>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr>
                <?php foreach (['Empleado','Identificación','Unidad','Puesto','Ingreso'] as $h): ?>
                <th style="text-align:left;font-size:.7rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));padding:var(--sp-2) var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));"><?= $h ?></th>
                <?php endforeach; ?>
            </tr></thead>
            <tbody>
            <?php if (empty($empleados)): ?>
                <tr><td colspan="5" style="text-align:center;padding:var(--sp-8);color:var(--text-muted,var(--color-text-muted));">Sin empleados registrados.</td></tr>
            <?php else: foreach ($empleados as $e): ?>
                <tr style="border-bottom:1px solid var(--border-app,var(--color-border));font-size:.84rem;">
                    <td style="padding:var(--sp-2) var(--sp-3);"><strong><?= htmlspecialchars(($e['nombres'] ?? '') . ' ' . ($e['apellidos'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td style="padding:var(--sp-2) var(--sp-3);font-family:var(--font-mono,monospace);"><?= htmlspecialchars($e['identificacion'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);color:var(--text-muted,var(--color-text-muted));"><?= htmlspecialchars($e['unidad'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);"><?= htmlspecialchars($e['puesto'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);"><?= $fmt($e['fecha_ingreso']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div></div>

    <!-- Últimas acciones de personal -->
    <div class="card"><div class="card-body" style="padding:0;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));">
            <h3 style="font-size:.95rem;font-weight:700;margin:0;"><i class="fa-solid fa-file-signature" style="color:#20c997;margin-right:6px;"></i>Últimas acciones</h3>
            <a href="<?= APP_URL ?>/apps/talento_humano/talento-humano/accion-personal" class="btn btn-outline btn-sm" data-no-spa>Ver todas</a>
        </div>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr>
                <?php foreach (['N°','Empleado','Tipo','Estado'] as $h): ?>
                <th style="text-align:left;font-size:.7rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));padding:var(--sp-2) var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));"><?= $h ?></th>
                <?php endforeach; ?>
            </tr></thead>
            <tbody>
            <?php if (empty($acciones)): ?>
                <tr><td colspan="4" style="text-align:center;padding:var(--sp-8);color:var(--text-muted,var(--color-text-muted));">Sin acciones registradas.</td></tr>
            <?php else: foreach ($acciones as $a): ?>
                <tr style="border-bottom:1px solid var(--border-app,var(--color-border));font-size:.82rem;">
                    <td style="padding:var(--sp-2) var(--sp-3);font-family:var(--font-mono,monospace);color:#20c997;font-weight:600;"><?= htmlspecialchars($a['numero_accion'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);"><?= htmlspecialchars($a['empleado'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);color:var(--text-muted,var(--color-text-muted));"><?= htmlspecialchars($a['tipo_accion'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);"><span style="background:color-mix(in srgb,#20c997 15%,transparent);color:#20c997;font-size:.7rem;padding:2px 8px;border-radius:20px;font-weight:700;"><?= htmlspecialchars($a['estado_documento'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div></div>
</div>

<style>
@media (max-width: 1000px) { .hub-grid-2 { grid-template-columns: 1fr !important; } }
</style>
</div>
