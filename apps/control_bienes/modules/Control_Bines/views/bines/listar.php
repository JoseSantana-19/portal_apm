<?php
/**
 * INV_INVENTARIO.PHP - Vista de Dashboard Principal de InvInventario
 * Lazy-load: las filas de la tabla se cargan únicamente al presionar "Mostrar datos"
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
$totalActivosDashboard = max(0, (int)($stats['total'] ?? 0));
$totalConsumoDashboard = max(0, (int)($stats['totalConsumoCorriente'] ?? 0));
$totalFijoDashboard = max(0, (int)($stats['totalActivoFijo'] ?? 0));
$valorDashboard = max(0.0, (float)($stats['valorInventariado'] ?? 0));
$porcentajeConsumo = $totalActivosDashboard > 0 ? ($totalConsumoDashboard / $totalActivosDashboard) * 100 : 0;
$porcentajeFijo = $totalActivosDashboard > 0 ? ($totalFijoDashboard / $totalActivosDashboard) * 100 : 0;
$valorPromedioDashboard = $totalActivosDashboard > 0 ? $valorDashboard / $totalActivosDashboard : 0;
$operativo = is_array($resumenOperativo ?? null) ? $resumenOperativo : [];
$movimientosRecientes = is_array($operativo['movimientos'] ?? null) ? $operativo['movimientos'] : [];
?>
<style>
th.sortable:hover {
    background: rgba(37, 99, 235, 0.05);
    color: var(--primary) !important;
}
th.sortable i {
    transition: all 0.2s ease;
}
.inventory-panel .dataTables_wrapper .top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 16px 24px 8px;
    flex-wrap: wrap;
}
.inventory-panel .dataTables_filter { margin-left: auto; }
.inventory-panel .dataTables_filter label { font-size: 0; }
.inventory-panel .dataTables_filter input {
    width: min(360px, 72vw);
    height: 42px;
    margin: 0;
    padding: 0 15px 0 40px;
    border: 1px solid var(--border-color);
    border-radius: 11px;
    color: var(--text-color);
    background: var(--panel-bg) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%232563eb' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.3-4.3'/%3E%3C/svg%3E") no-repeat 14px center;
    box-sizing: border-box;
}
.inventory-kpis {
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:16px;
    margin-bottom:18px;
}
.inventory-kpis .stat-card {
    position:relative;
    min-width:0;
    overflow:hidden;
    border:1px solid rgba(148,163,184,.22);
    border-radius:18px;
    box-shadow:0 10px 28px rgba(15,23,42,.055);
}
.inventory-kpis .stat-card::after { content:''; position:absolute; inset:auto 0 0; height:4px; background:var(--kpi-accent,#2563eb); }
.inventory-kpis .stat-card:nth-child(1){--kpi-accent:#2563eb}.inventory-kpis .stat-card:nth-child(2){--kpi-accent:#0ea5e9}.inventory-kpis .stat-card:nth-child(3){--kpi-accent:#10b981}.inventory-kpis .stat-card:nth-child(4){--kpi-accent:#f59e0b}
.inventory-kpis .stat-value{font-size:clamp(23px,2.05vw,34px);line-height:1.08;white-space:nowrap;letter-spacing:-.035em}
.inventory-kpis .stat-label{max-width:210px;line-height:1.3}
.inventory-kpis .stat-card:last-child .stat-value{font-size:clamp(20px,1.75vw,30px)}
.inventory-analytics{display:grid;grid-template-columns:minmax(280px,.8fr) minmax(420px,1.2fr) minmax(280px,.8fr);gap:16px;margin-bottom:22px}
.analytics-card{position:relative;overflow:hidden;padding:22px;border:1px solid rgba(148,163,184,.2);border-radius:18px;background:var(--panel-bg);box-shadow:0 12px 32px rgba(15,23,42,.055)}
.analytics-card::before{content:'';position:absolute;width:170px;height:170px;border-radius:50%;right:-85px;top:-95px;background:var(--analytics-glow,rgba(37,99,235,.08))}
.analytics-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:18px}.analytics-heading h3{margin:0 0 4px;font-size:15px}.analytics-heading p{margin:0;color:var(--text-muted);font-size:11.5px}.analytics-heading-icon{width:37px;height:37px;display:grid;place-items:center;border-radius:11px;background:rgba(37,99,235,.1);color:#2563eb}
.analytics-donut-layout{display:flex;align-items:center;justify-content:center;gap:24px;min-height:180px}.analytics-donut{--cc:0deg;position:relative;width:154px;height:154px;flex:0 0 154px;border-radius:50%;background:conic-gradient(#2563eb 0 var(--cc),#10b981 var(--cc) 360deg);box-shadow:inset 0 0 0 1px rgba(255,255,255,.35)}.analytics-donut::after{content:'';position:absolute;inset:21px;border-radius:50%;background:var(--panel-bg);box-shadow:0 0 0 1px rgba(148,163,184,.12)}.analytics-donut-center{position:absolute;inset:0;z-index:1;display:grid;place-content:center;text-align:center}.analytics-donut-center strong{font-size:25px;line-height:1}.analytics-donut-center span{margin-top:5px;color:var(--text-muted);font-size:10px;font-weight:700;text-transform:uppercase}
.analytics-legend{display:grid;gap:13px}.analytics-legend-row{display:grid;grid-template-columns:10px 1fr auto;align-items:center;gap:8px;font-size:11px}.analytics-legend-row i{width:9px;height:9px;border-radius:3px}.analytics-legend-row span{color:var(--text-muted)}.analytics-legend-row strong{text-align:right}.analytics-legend-row small{grid-column:2/4;color:var(--text-muted)}
.analytics-bars{display:grid;gap:18px;padding-top:7px}.analytics-bar-row{display:grid;grid-template-columns:minmax(125px,.8fr) minmax(170px,2fr) 75px;align-items:center;gap:13px}.analytics-bar-label strong,.analytics-bar-label span{display:block}.analytics-bar-label strong{font-size:12px}.analytics-bar-label span{margin-top:3px;color:var(--text-muted);font-size:10px}.analytics-track{height:13px;overflow:hidden;border-radius:999px;background:var(--secondary-bg);box-shadow:inset 0 0 0 1px rgba(148,163,184,.12)}.analytics-fill{height:100%;min-width:5px;border-radius:inherit;background:linear-gradient(90deg,var(--bar-start),var(--bar-end));box-shadow:0 3px 10px color-mix(in srgb,var(--bar-end) 25%,transparent)}.analytics-bar-number{text-align:right}.analytics-bar-number strong{display:block;font-size:13px}.analytics-bar-number span{font-size:10px;color:var(--text-muted)}
.analytics-financial{--analytics-glow:rgba(245,158,11,.1);background:linear-gradient(145deg,var(--panel-bg),color-mix(in srgb,#f59e0b 4%,var(--panel-bg)))}.financial-total{margin:7px 0 18px}.financial-total span{display:block;color:var(--text-muted);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.financial-total strong{display:block;margin-top:7px;font-size:clamp(25px,2.5vw,37px);line-height:1;color:#d97706;letter-spacing:-.04em}.financial-insight{display:grid;grid-template-columns:1fr 1fr;gap:9px}.financial-insight div{padding:12px;border:1px solid rgba(245,158,11,.16);border-radius:12px;background:rgba(255,255,255,.42)}.financial-insight span,.financial-insight strong{display:block}.financial-insight span{color:var(--text-muted);font-size:9px;text-transform:uppercase;font-weight:800}.financial-insight strong{margin-top:6px;font-size:15px}.financial-note{display:flex;gap:8px;margin-top:14px;padding-top:13px;border-top:1px dashed rgba(148,163,184,.3);color:var(--text-muted);font-size:10.5px;line-height:1.45}.financial-note i{color:#f59e0b;margin-top:2px}
.inventory-workspace{display:grid;grid-template-columns:1.15fr .85fr .9fr;gap:16px;margin-bottom:22px}.workspace-card{padding:19px;border:1px solid rgba(148,163,184,.22);border-radius:17px;background:var(--panel-bg);box-shadow:0 10px 28px rgba(15,23,42,.045)}.workspace-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}.workspace-heading h3{margin:0 0 3px;font-size:14px}.workspace-heading p{margin:0;color:var(--text-muted);font-size:10.5px}.workspace-clear{border:0;background:transparent;color:#2563eb;font-size:10px;font-weight:800;cursor:pointer}.priority-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px}.priority-card{display:grid;grid-template-columns:34px 1fr auto;align-items:center;gap:9px;min-height:64px;padding:10px;border:1px solid var(--priority-border,#dbeafe);border-radius:12px;background:var(--priority-bg,#f8fbff);color:var(--text-color);text-align:left;cursor:pointer;transition:transform .18s,border-color .18s,box-shadow .18s}.priority-card:hover,.priority-card.active{transform:translateY(-1px);border-color:var(--priority-color,#2563eb);box-shadow:0 7px 17px color-mix(in srgb,var(--priority-color,#2563eb) 14%,transparent)}.priority-card>i{width:34px;height:34px;display:grid;place-items:center;border-radius:10px;background:color-mix(in srgb,var(--priority-color,#2563eb) 12%,white);color:var(--priority-color,#2563eb)}.priority-card span strong,.priority-card span small{display:block}.priority-card span strong{font-size:11px}.priority-card span small{margin-top:3px;color:var(--text-muted);font-size:9px}.priority-card>b{font-size:18px;color:var(--priority-color,#2563eb)}.quick-action-list{display:grid;gap:8px}.quick-action{display:grid;grid-template-columns:34px 1fr auto;align-items:center;gap:10px;padding:9px 10px;border:1px solid var(--border-color);border-radius:11px;color:var(--text-color);text-decoration:none;transition:border-color .18s,background .18s}.quick-action:hover{border-color:#93c5fd;background:#f8fbff}.quick-action>i:first-child{width:34px;height:34px;display:grid;place-items:center;border-radius:9px;background:#eff6ff;color:#2563eb}.quick-action strong,.quick-action small{display:block}.quick-action strong{font-size:11px}.quick-action small{margin-top:2px;color:var(--text-muted);font-size:9px}.quick-action>i:last-child{color:#94a3b8;font-size:10px}.movement-list{display:grid;gap:8px}.movement-row{display:grid;grid-template-columns:32px 1fr auto;align-items:center;gap:9px;padding-bottom:8px;border-bottom:1px solid rgba(148,163,184,.17)}.movement-row:last-child{padding-bottom:0;border-bottom:0}.movement-icon{width:30px;height:30px;display:grid;place-items:center;border-radius:9px;background:#ecfdf5;color:#059669}.movement-icon.salida{background:#fff7ed;color:#ea580c}.movement-row strong,.movement-row small{display:block}.movement-row strong{overflow:hidden;font-size:10px;text-overflow:ellipsis;white-space:nowrap}.movement-row small{margin-top:2px;color:var(--text-muted);font-size:8.5px}.movement-amount{text-align:right}.movement-amount b,.movement-amount span{display:block}.movement-amount b{font-size:11px}.movement-amount span{margin-top:2px;color:var(--text-muted);font-size:8px}.workspace-empty{padding:22px 8px;color:var(--text-muted);font-size:11px;text-align:center}.workspace-link{display:inline-flex;align-items:center;gap:5px;margin-top:12px;color:#2563eb;font-size:10px;font-weight:800;text-decoration:none}
.inventory-panel {
    overflow: hidden;
    border: 1px solid rgba(37, 99, 235, .12);
    box-shadow: 0 12px 35px rgba(15, 23, 42, .06);
}
.inventory-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    padding: 20px 24px;
    background: linear-gradient(135deg, rgba(37, 99, 235, .07), rgba(14, 165, 233, .025));
    border-bottom: 1px solid var(--border-color);
}
.inventory-panel-title {
    display: flex;
    align-items: center;
    gap: 13px;
}
.inventory-panel-title-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    color: var(--primary);
    background: rgba(37, 99, 235, .1);
}
.inventory-panel-title h3 { margin: 0 0 4px; }
.inventory-panel-title p { margin: 0; color: var(--text-muted); font-size: 13px; }
.inventory-header-actions { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.inventory-query-status {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 7px 11px;
    border-radius: 999px;
    background: rgba(100, 116, 139, .09);
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 700;
}
.inventory-query-status::before {
    content: '';
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #94a3b8;
}
.inventory-query-status.active { background: rgba(16, 185, 129, .1); color: #047857; }
.inventory-query-status.active::before { background: #10b981; box-shadow: 0 0 0 4px rgba(16,185,129,.12); }
.inventory-empty-state {
    margin: 24px;
    padding: 48px 24px;
    border: 1px dashed rgba(37, 99, 235, .25);
    border-radius: 18px;
    text-align: center;
    background: linear-gradient(180deg, rgba(37, 99, 235, .035), transparent);
}
.inventory-empty-icon {
    width: 72px;
    height: 72px;
    display: grid;
    place-items: center;
    margin: 0 auto 18px;
    border-radius: 22px;
    background: rgba(37, 99, 235, .1);
    color: var(--primary);
    font-size: 30px;
}
.inventory-empty-state h4 { margin: 0 0 8px; font-size: 18px; color: var(--text-color); }
.inventory-empty-state p { max-width: 570px; margin: 0 auto 20px; color: var(--text-muted); line-height: 1.55; font-size: 14px; }
.inventory-empty-note { display:block; margin-top:12px; color:var(--text-muted); font-size:12px; }
@media (max-width: 720px) {
    .inventory-panel-header { align-items: flex-start; }
    .inventory-header-actions { width: 100%; }
    .inventory-header-actions .btn-primary { flex: 1; justify-content: center; }
    .inventory-empty-state { margin: 14px; padding: 36px 18px; }
}
@media(max-width:1180px){.inventory-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.inventory-workspace{grid-template-columns:1fr 1fr}.workspace-movements{grid-column:1/-1}.inventory-analytics{grid-template-columns:1fr 1fr}.analytics-financial{grid-column:1/-1}}
@media(max-width:720px){.inventory-kpis,.inventory-workspace,.inventory-analytics{grid-template-columns:1fr}.workspace-movements,.analytics-financial{grid-column:auto}.priority-grid{grid-template-columns:1fr}.analytics-donut-layout{flex-direction:column}.analytics-bar-row{grid-template-columns:1fr 64px}.analytics-track{grid-column:1/-1;grid-row:2}.analytics-bar-number{grid-column:2;grid-row:1}.financial-insight{grid-template-columns:1fr}}
</style>

<!-- InvCabecera de Página -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1>Inventario General</h1>
        <p>Gestión de bienes, maquinarias y contenedores. Período Fiscal Activo: <strong><?= htmlspecialchars(($periodoActivo ? $periodoActivo['nombre'] : 'Sin Período Activo')) ?></strong> (IVA: <strong><?= $tasaIva ?>%</strong>).</p>
    </div>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="index.php?route=inventario&action=exportar<?= !empty($filtros['categoria']) ? '&categoria='.urlencode($filtros['categoria']) : '' ?><?= !empty($filtros['unidad_id']) ? '&unidad_id='.urlencode($filtros['unidad_id']) : '' ?><?= !empty($filtros['estado']) ? '&estado='.urlencode($filtros['estado']) : '' ?><?= !empty($filtros['termino']) ? '&termino='.urlencode($filtros['termino']) : '' ?>"
           class="btn-outline" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;" title="Exportar a CSV">
           <i class="fa-solid fa-file-csv"></i> Exportar CSV
        </a>
        <a href="index.php?route=inventario&action=exportarPdf<?= !empty($filtros['categoria']) ? '&categoria='.urlencode($filtros['categoria']) : '' ?><?= !empty($filtros['unidad_id']) ? '&unidad_id='.urlencode($filtros['unidad_id']) : '' ?><?= !empty($filtros['estado']) ? '&estado='.urlencode($filtros['estado']) : '' ?><?= !empty($filtros['termino']) ? '&termino='.urlencode($filtros['termino']) : '' ?>"
           class="btn-outline" target="_blank" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;border-color:#ef4444;color:#ef4444;background:rgba(239,68,68,0.02);" title="Reporte PDF">
           <i class="fa-solid fa-file-pdf"></i> Exportar PDF
        </a>
        <a href="index.php?route=inv_items_sistema" class="btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;"><i class="fa-solid fa-plus"></i> Registrar Equipo</a>
    </div>
</div>

<!-- Stats Cards -->
<div class="stats-row inventory-kpis animate-fade-in">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-layer-group"></i></div>
        <div>
            <div class="stat-value"><?= number_format((int)($stats['total'] ?? 0)) ?></div>
            <div class="stat-label">Registros activos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div>
            <div class="stat-value"><?= $stats['totalConsumoCorriente'] ?? 0 ?></div>
            <div class="stat-label">Total de bienes de consumo corriente</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div>
            <div class="stat-value"><?= $stats['totalActivoFijo'] ?? 0 ?></div>
            <div class="stat-label">Total de bienes de activo fijo</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-chart-line"></i></div>
        <div>
            <div class="stat-value">$<?= number_format((float)($stats['valorInventariado'] ?? 0), 2) ?></div>
            <div class="stat-label">Valor inventariado</div>
        </div>
    </div>
</div>

<section class="inventory-workspace animate-fade-in" aria-label="Centro operativo del inventario">
    <article class="workspace-card">
        <div class="workspace-heading"><div><h3>Prioridades operativas</h3><p>Abra directamente los registros que requieren atención.</p></div><button type="button" class="workspace-clear" onclick="limpiarSegmentoInventario()">Ver todos</button></div>
        <div class="priority-grid">
            <button type="button" class="priority-card" data-inventory-segment="sin_stock" onclick="aplicarSegmentoInventario('sin_stock','Productos sin existencia')" style="--priority-color:#dc2626;--priority-border:#fecaca;--priority-bg:#fffafa"><i class="fa-solid fa-box-open"></i><span><strong>Sin existencia</strong><small>Consumo corriente agotado</small></span><b><?= number_format((int)($operativo['sin_stock'] ?? 0)) ?></b></button>
            <button type="button" class="priority-card" data-inventory-segment="stock_bajo" onclick="aplicarSegmentoInventario('stock_bajo','Productos con stock bajo')" style="--priority-color:#d97706;--priority-border:#fde68a;--priority-bg:#fffdf5"><i class="fa-solid fa-triangle-exclamation"></i><span><strong>Stock bajo</strong><small>Existencia igual o menor al mínimo</small></span><b><?= number_format((int)($operativo['stock_bajo'] ?? 0)) ?></b></button>
            <button type="button" class="priority-card" data-inventory-segment="mantenimiento" onclick="aplicarSegmentoInventario('mantenimiento','Bienes en mantenimiento')" style="--priority-color:#7c3aed;--priority-border:#ddd6fe;--priority-bg:#fbfaff"><i class="fa-solid fa-screwdriver-wrench"></i><span><strong>Mantenimiento</strong><small>Bienes fuera de operación</small></span><b><?= number_format((int)($operativo['mantenimiento'] ?? 0)) ?></b></button>
            <button type="button" class="priority-card" data-inventory-segment="sin_responsable" onclick="aplicarSegmentoInventario('sin_responsable','Activos sin responsable')" style="--priority-color:#2563eb"><i class="fa-solid fa-user-slash"></i><span><strong>Sin responsable</strong><small>Activos fijos por asignar</small></span><b><?= number_format((int)($operativo['sin_responsable'] ?? 0)) ?></b></button>
        </div>
    </article>
    <article class="workspace-card">
        <div class="workspace-heading"><div><h3>Acciones frecuentes</h3><p>Sus cuatro opciones más utilizadas.</p></div></div>
        <div class="quick-action-list">
            <?php foreach (($accionesFrecuentes ?? []) as $accionFrecuente): ?>
            <a class="quick-action" href="<?= htmlspecialchars($accionFrecuente['url']) ?>"><i class="fa-solid <?= htmlspecialchars($accionFrecuente['icono']) ?>"></i><span><strong><?= htmlspecialchars($accionFrecuente['titulo']) ?></strong><small><?= htmlspecialchars($accionFrecuente['detalle']) ?></small></span><i class="fa-solid fa-chevron-right"></i></a>
            <?php endforeach; ?>
        </div>
    </article>
    <article class="workspace-card workspace-movements">
        <div class="workspace-heading"><div><h3>Movimientos recientes</h3><p>Últimas entradas y salidas registradas.</p></div></div>
        <?php if (!$movimientosRecientes): ?><div class="workspace-empty"><i class="fa-solid fa-clock-rotate-left"></i><br>No hay movimientos recientes.</div><?php endif; ?>
        <div class="movement-list">
            <?php foreach ($movimientosRecientes as $movimiento): $esSalida = strtoupper((string)($movimiento['tipo_movimiento'] ?? '')) === 'EGRESO' || (int)($movimiento['salida'] ?? 0) > 0; $cantidadMovimiento = $esSalida ? (int)($movimiento['salida'] ?? 0) : (int)($movimiento['entrada'] ?? 0); ?>
            <div class="movement-row"><span class="movement-icon <?= $esSalida ? 'salida' : '' ?>"><i class="fa-solid <?= $esSalida ? 'fa-arrow-up' : 'fa-arrow-down' ?>"></i></span><div><strong><?= htmlspecialchars($movimiento['item_nombre'] ?? 'Ítem') ?></strong><small><?= htmlspecialchars(($movimiento['item_codigo'] ?? '').' · '.($movimiento['documento_secuencial'] ?? 'Sin documento')) ?></small></div><div class="movement-amount"><b><?= $esSalida ? '-' : '+' ?><?= number_format($cantidadMovimiento) ?></b><span><?= !empty($movimiento['fecha_movimiento']) ? date('d/m H:i', strtotime($movimiento['fecha_movimiento'])) : '' ?></span></div></div>
            <?php endforeach; ?>
        </div>
        <a class="workspace-link" href="index.php?route=egresos">Ir a movimientos de bodega <i class="fa-solid fa-arrow-right"></i></a>
    </article>
</section>

<section class="inventory-analytics animate-fade-in" aria-label="Resumen estadístico del inventario">
    <article class="analytics-card">
        <div class="analytics-heading"><div><h3>Composición del inventario</h3><p>Participación por tipo de bien</p></div><span class="analytics-heading-icon"><i class="fa-solid fa-chart-pie"></i></span></div>
        <div class="analytics-donut-layout">
            <div class="analytics-donut" style="--cc:<?= number_format($porcentajeConsumo * 3.6, 2, '.', '') ?>deg"><div class="analytics-donut-center"><strong><?= number_format($totalActivosDashboard) ?></strong><span>Activos</span></div></div>
            <div class="analytics-legend">
                <div class="analytics-legend-row"><i style="background:#2563eb"></i><span>Consumo corriente</span><strong><?= number_format($totalConsumoDashboard) ?></strong><small><?= number_format($porcentajeConsumo,1) ?>% del inventario</small></div>
                <div class="analytics-legend-row"><i style="background:#10b981"></i><span>Activo fijo</span><strong><?= number_format($totalFijoDashboard) ?></strong><small><?= number_format($porcentajeFijo,1) ?>% del inventario</small></div>
            </div>
        </div>
    </article>
    <article class="analytics-card">
        <div class="analytics-heading"><div><h3>Distribución general</h3><p>Comparación estadística sobre el total activo</p></div><span class="analytics-heading-icon"><i class="fa-solid fa-chart-simple"></i></span></div>
        <div class="analytics-bars">
            <div class="analytics-bar-row"><div class="analytics-bar-label"><strong>Consumo corriente</strong><span>Bienes operativos de consumo</span></div><div class="analytics-track"><div class="analytics-fill" style="--bar-start:#38bdf8;--bar-end:#2563eb;width:<?= number_format($porcentajeConsumo,2,'.','') ?>%"></div></div><div class="analytics-bar-number"><strong><?= number_format($totalConsumoDashboard) ?></strong><span><?= number_format($porcentajeConsumo,1) ?>%</span></div></div>
            <div class="analytics-bar-row"><div class="analytics-bar-label"><strong>Activo fijo</strong><span>Bienes institucionales permanentes</span></div><div class="analytics-track"><div class="analytics-fill" style="--bar-start:#34d399;--bar-end:#059669;width:<?= number_format($porcentajeFijo,2,'.','') ?>%"></div></div><div class="analytics-bar-number"><strong><?= number_format($totalFijoDashboard) ?></strong><span><?= number_format($porcentajeFijo,1) ?>%</span></div></div>
            <div class="analytics-bar-row"><div class="analytics-bar-label"><strong>Total registrado</strong><span>Base estadística activa</span></div><div class="analytics-track"><div class="analytics-fill" style="--bar-start:#a78bfa;--bar-end:#7c3aed;width:100%"></div></div><div class="analytics-bar-number"><strong><?= number_format($totalActivosDashboard) ?></strong><span>100%</span></div></div>
        </div>
    </article>
    <article class="analytics-card analytics-financial">
        <div class="analytics-heading"><div><h3>Lectura financiera</h3><p>Valor consolidado del inventario</p></div><span class="analytics-heading-icon" style="color:#d97706;background:rgba(245,158,11,.12)"><i class="fa-solid fa-chart-line"></i></span></div>
        <div class="financial-total"><span>Valor inventariado</span><strong>$<?= number_format($valorDashboard,2) ?></strong></div>
        <div class="financial-insight"><div><span>Promedio por registro</span><strong>$<?= number_format($valorPromedioDashboard,2) ?></strong></div><div><span>Registros valorados</span><strong><?= number_format($totalActivosDashboard) ?></strong></div></div>
        <div class="financial-note"><i class="fa-solid fa-circle-info"></i><span>El promedio relaciona el valor consolidado con los registros activos y facilita detectar cambios generales en la valoración.</span></div>
    </article>
</section>

<!-- Barra de Filtros y Búsqueda -->
<div class="filter-section animate-fade-in">
    <form action="index.php" method="GET" class="filter-controls" id="filtros-form">
        <input type="hidden" name="route" value="inventario">

        <div class="filter-group">
            <label>Categoría</label>
            <select name="categoria" id="filtro-categoria">
                <option value="">Todas las Categorías</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['id']) ?>" <?= (isset($filtros['categoria']) && ($filtros['categoria'] == $cat['id'] || $filtros['categoria'] == $cat['nombre'])) ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Unidad de Medida</label>
            <select name="unidad_id">
                <option value="">Todas las Unidades</option>
                <?php foreach ($unidades as $u): ?>
                    <option value="<?= htmlspecialchars($u['id']) ?>" <?= (isset($filtros['unidad_id']) && $filtros['unidad_id'] == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['nombre']) ?><?= !empty($u['extra']) ? ' ('.$u['extra'].')' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Estado del Bien</label>
            <select name="estado">
                <option value="">Todos los Estados</option>
                <?php foreach ($estados as $est): ?>
                    <option value="<?= htmlspecialchars($est['id']) ?>" <?= ($filtros['estado'] == $est['id']) ? 'selected' : '' ?>><?= htmlspecialchars($est['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-actions">
            <a href="index.php?route=inventario" class="btn-outline" style="height:40px;display:flex;align-items:center;justify-content:center;" title="Limpiar Filtros"><i class="fa-solid fa-eraser"></i></a>
            <button type="submit" class="btn-primary" style="height:40px;"><i class="fa-solid fa-filter"></i> Aplicar filtros</button>
        </div>
    </form>
</div>

<!-- Panel de Tabla con Carga Bajo Demanda -->
<div class="panel inventory-panel animate-fade-in">
    <div class="inventory-panel-header">
        <div class="inventory-panel-title">
            <div class="inventory-panel-title-icon"><i class="fa-solid fa-table-list"></i></div>
            <div>
                <h3 id="tbl-titulo">Registros del Inventario</h3>
                <p id="tbl-subtitulo">La consulta se carga únicamente cuando la necesitas.</p>
            </div>
        </div>
        <div class="inventory-header-actions">
            <span id="inventario-estado-consulta" class="inventory-query-status">Datos sin cargar</span>
            <button id="btn-mostrar-datos" class="btn-primary" onclick="cargarDatosInventario()" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:10px;cursor:pointer;">
                <i class="fa-solid fa-table-list" id="icon-mostrar"></i>
                <span id="lbl-mostrar">Mostrar datos</span>
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table id="tabla-inventario">
            <thead>
                <tr>
                    <th style="display:none;">Secuencial</th>
                    <th class="sortable" onclick="ordenarPor('nombre')" style="cursor:pointer; user-select:none;">Descripción <i class="fa-solid fa-sort" id="sort-nombre" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th class="sortable" onclick="ordenarPor('categoria')" style="cursor:pointer; user-select:none;">Categoría <i class="fa-solid fa-sort" id="sort-categoria" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th class="sortable" onclick="ordenarPor('unidad')" style="cursor:pointer; user-select:none;">Unidad <i class="fa-solid fa-sort" id="sort-unidad" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th class="sortable" onclick="ordenarPor('valor')" style="cursor:pointer; user-select:none;">Base ($) <i class="fa-solid fa-sort" id="sort-valor" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th class="sortable" onclick="ordenarPor('iva')" style="cursor:pointer; user-select:none; width:80px; text-align:center;">IVA <i class="fa-solid fa-sort" id="sort-iva" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th class="sortable" onclick="ordenarPor('total')" style="cursor:pointer; user-select:none;">Total ($) <i class="fa-solid fa-sort" id="sort-total" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th class="sortable" onclick="ordenarPor('estado')" style="cursor:pointer; user-select:none;">Estado <i class="fa-solid fa-sort" id="sort-estado" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbody-inventario">
                <!-- Los datos se inyectan dinámicamente vía DataTables AJAX al pulsar "Mostrar datos" -->
            </tbody>
        </table>
    </div>

    <div id="inventario-datos-vencidos" class="inventory-empty-state">
        <div class="inventory-empty-icon"><i id="inventario-empty-icon" class="fa-solid fa-database"></i></div>
        <h4 id="inventario-empty-title">Consulta lista para cargar</h4>
        <p id="inventario-empty-message">Para optimizar el sistema, los registros no se consultan automáticamente. Cárgalos cuando vayas a trabajar con el inventario.</p>
        <button type="button" class="btn-primary" onclick="cargarDatosInventario()" style="display:inline-flex;align-items:center;gap:8px;padding:11px 22px;">
            <i class="fa-solid fa-bolt"></i> Mostrar datos ahora
        </button>
        <span class="inventory-empty-note"><i class="fa-solid fa-shield-halved"></i> Al iniciar una nueva sesión siempre se solicitará una consulta nueva.</span>
    </div>

    <!-- Barra de Paginación Moderna -->
    <div id="paginacion-container" style="display:flex;justify-content:space-between;align-items:center;padding:14px 24px;border-top:1px solid var(--border-color);background:var(--secondary-bg);flex-wrap:wrap;gap:12px;">
        <div style="font-size:13px;color:var(--text-muted);">
            Mostrando <span id="pag-rango" style="font-weight:700;color:var(--text-color);">0 - 0</span> de <span id="pag-total" style="font-weight:700;color:var(--text-color);">0</span> registros
        </div>
        <div style="display:flex;align-items:center;gap:6px;" id="pag-botones">
            <!-- Botones dinámicos inyectados por JS -->
        </div>
    </div>
</div>

<!-- Modal: Registro / Edición de Equipo -->
<div class="modal-overlay" id="inv-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="inv-modal-title">Nuevo Registro</h2>
            <button class="modal-close" onclick="cerrarModalInventario()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="index.php?route=inventario&action=guardar" method="POST">
            <input type="hidden" name="id" id="inv-inp-id" value="0">
            <input type="hidden" name="producto_id" id="inv-inp-producto-id" value="">
            <div class="modal-body">

                <div class="form-group">
                    <label>Nombre del Equipo / Contenedor</label>
                    <input type="text" name="nombre" id="inv-inp-nombre" required placeholder="Ej: Grúa Pórtico RTG-04">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>Marca</label>
                        <input type="text" name="marca" id="inv-inp-marca" required placeholder="Ej: Kalmar">
                    </div>
                    <div class="form-group">
                        <label>Categoría</label>
                        <select name="categoria_id" id="inv-inp-categoria" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>Zona / Terminal</label>
                        <select name="zona_id" id="inv-inp-zona" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($zonas as $z): ?>
                                <option value="<?= $z['id'] ?>"><?= htmlspecialchars($z['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Estado Operativo</label>
                        <select name="estado_id" id="inv-inp-estado" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($estados as $est): ?>
                                <option value="<?= $est['id'] ?>"><?= htmlspecialchars($est['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>Responsable de Operación</label>
                        <select name="responsable_id" id="inv-inp-responsable">
                            <option value="">Sin Responsable (Ninguno)</option>
                            <?php foreach ($personal as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['area_actual']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Valor Monetario Base ($)</label>
                        <input type="number" step="0.01" name="valor" id="inv-inp-valor" required placeholder="Ej: 8500.00">
                    </div>
                </div>

                <div class="form-group">
                    <label>Observaciones Adicionales</label>
                    <textarea name="observaciones" id="inv-inp-obs" placeholder="Detalles de mantenimiento, carga, destino..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="cerrarModalInventario()">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Detalle de Bien -->
<div class="modal-overlay" id="inv-modal-detalle">
    <div class="modal-content modal-content-wide">
        <div class="modal-header">
            <h2>Detalle Completo del Registro</h2>
            <button class="modal-close" onclick="cerrarDetallesInventario()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="inv-det-content">
            <!-- Cargado por AJAX dinámicamente -->
        </div>
    </div>
</div>

<script>
    var table;
    var datosCargados = false;
    var segmentoOperativoActivo = '';
    var tiempoAusenciaInventarioMs = <?= max(60, (int)($tiempoVigenciaInventario ?? 600)) ?> * 1000;
    var tokenSesionInventario = <?= json_encode((string)($_SESSION['inventario_sesion_token'] ?? session_id())) ?>;

    if (sessionStorage.getItem('inventario_sesion_token') !== tokenSesionInventario) {
        sessionStorage.setItem('inventario_sesion_token', tokenSesionInventario);
        sessionStorage.removeItem('inventario_datos_mostrados');
        sessionStorage.removeItem('inventario_fuera_desde');
        sessionStorage.removeItem('inventario_estado_tabla');
    }

    // Prevenir cuadros de diálogo de alerta de DataTables
    if (typeof $ !== 'undefined' && $.fn && $.fn.dataTable) {
        $.fn.dataTable.ext.errMode = 'none';
    }

    function cargarDatosInventario() {
        datosCargados = true;
        $('#tabla-inventario_wrapper').show();
        $('#inventario-datos-vencidos').hide();
        $('#lbl-mostrar').text('Actualizar datos');
        $('#icon-mostrar').attr('class', 'fa-solid fa-rotate fa-spin');
        table.ajax.reload(function() {
            sessionStorage.setItem('inventario_datos_mostrados', '1');
            sessionStorage.removeItem('inventario_fuera_desde');
            $('#icon-mostrar').attr('class', 'fa-solid fa-rotate');
            $('#tbl-subtitulo').text('Consulta actualizada y disponible mientras permanezcas en este apartado.');
            $('#inventario-estado-consulta').addClass('active').text('Datos visibles');
        });
    }

    function aplicarSegmentoInventario(segmento, titulo) {
        segmentoOperativoActivo = segmento || '';
        document.querySelectorAll('[data-inventory-segment]').forEach(function(card) {
            card.classList.toggle('active', card.dataset.inventorySegment === segmentoOperativoActivo);
        });
        $('#tbl-titulo').text(titulo || 'Registros del Inventario');
        $('#tbl-subtitulo').text('Vista operativa filtrada. Puede combinarla con categoría, unidad, estado y búsqueda.');
        cargarDatosInventario();
        setTimeout(function() { document.querySelector('.inventory-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 100);
    }

    function limpiarSegmentoInventario() {
        segmentoOperativoActivo = '';
        document.querySelectorAll('[data-inventory-segment]').forEach(function(card) { card.classList.remove('active'); });
        $('#tbl-titulo').text('Registros del Inventario');
        cargarDatosInventario();
    }

    function mostrarEstadoSinDatos(motivo) {
        var porAusencia = motivo === 'ausencia';
        $('#inventario-empty-icon').attr('class', porAusencia ? 'fa-solid fa-clock-rotate-left' : 'fa-solid fa-database');
        $('#inventario-empty-title').text(porAusencia ? 'La consulta anterior fue liberada' : 'Consulta lista para cargar');
        $('#inventario-empty-message').text(porAusencia
            ? 'Estuviste fuera de Inventario General más tiempo del configurado. Vuelve a mostrar los datos para trabajar con información actualizada.'
            : 'Para optimizar el sistema, los registros no se consultan automáticamente. Cárgalos cuando vayas a trabajar con el inventario.');
        $('#inventario-datos-vencidos').show();
    }

    function ocultarDatosInventario(motivo) {
        datosCargados = false;
        sessionStorage.removeItem('inventario_datos_mostrados');
        sessionStorage.removeItem('inventario_fuera_desde');
        if (table) table.clear();
        $('#tbody-inventario').empty();
        $('#tabla-inventario_wrapper').hide();
        mostrarEstadoSinDatos(motivo || 'inicial');
        $('#lbl-mostrar').text('Mostrar datos');
        $('#icon-mostrar').attr('class', 'fa-solid fa-table-list');
        $('#tbl-subtitulo').text(motivo === 'ausencia'
            ? 'La consulta se liberó después de permanecer fuera de este apartado.'
            : 'La consulta se carga únicamente cuando la necesitas.');
        $('#inventario-estado-consulta').removeClass('active').text('Datos sin cargar');
    }

    function registrarSalidaInventario() {
        if (sessionStorage.getItem('inventario_datos_mostrados') === '1') {
            sessionStorage.setItem('inventario_fuera_desde', String(Date.now()));
        }
    }

    function ausenciaSuperaLimite() {
        var fueraDesde = Number(sessionStorage.getItem('inventario_fuera_desde') || 0);
        return fueraDesde > 0 && (Date.now() - fueraDesde) >= tiempoAusenciaInventarioMs;
    }

    $(document).ready(function() {
        // Ocultar únicamente el contenedor de paginación manual antiguo
        $('#paginacion-container').hide();
        
        // Inicializar DataTables diferido (deferLoading: 0)
        table = $('#tabla-inventario').DataTable({
            processing: true,
            serverSide: true,
            deferLoading: 0,
            searchDelay: 350,
            ajax: {
                url: 'index.php?route=inventario&action=listarAjax',
                type: 'GET',
                data: function(d) {
                    d.categoria = $('#filtro-categoria').val() || '';
                    d.unidad_id = $('select[name="unidad_id"]').val() || '';
                    d.estado = $('select[name="estado"]').val() || '';
                    d.segmento = segmentoOperativoActivo;
                }
            },
            columns: [
                { data: 'secuencial', visible: false },
                { data: 'nombre' },
                { data: 'categoria' },
                { data: 'unidad' },
                { data: 'valor' },
                { data: 'iva', className: 'text-center' },
                { data: 'total' },
                { data: 'estado' },
                { data: 'acciones', orderable: false, className: 'columna-acciones' }
            ],
            pageLength: 50,
            stateSave: true,
            stateSaveCallback: function(settings, data) {
                sessionStorage.setItem('inventario_estado_tabla', JSON.stringify(data));
            },
            stateLoadCallback: function() {
                var estado = sessionStorage.getItem('inventario_estado_tabla');
                if (!estado) return null;
                try { return JSON.parse(estado); } catch (e) { return null; }
            },
            lengthMenu: [10, 25, 50, 100],
            language: {
                processing: 'Consultando inventario...',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Sin registros cargados',
                infoFiltered: '(filtrado de _MAX_ registros)',
                emptyTable: '<div style="text-align:center;padding:36px 20px;color:var(--text-muted);"><i class="fa-solid fa-table-list" style="font-size:38px;display:block;margin-bottom:12px;opacity:0.25;"></i><strong style="font-size:15px;display:block;margin-bottom:4px;">Datos no cargados</strong><span style="font-size:13px;">Presione el botón <strong>"Mostrar datos"</strong> para consultar los registros.</span></div>',
                search: 'Buscar en inventario:',
                searchPlaceholder: 'Código, nombre, marca, categoría…',
                zeroRecords: 'No se encontraron bienes coincidentes',
                paginate: { previous: 'Anterior', next: 'Siguiente' }
            },
            dom: '<"top"f i>rt<"bottom"lp><"clear">',
            ordering: true,
            order: [[0, 'desc']]
        });

        $('#tabla-inventario_wrapper').hide();

        // Interceptar el envío del formulario de filtros
        $('#filtros-form').on('submit', function(e) {
            e.preventDefault();
            cargarDatosInventario();
        });

        // Interceptar cambios en los filtros select
        $('#filtro-categoria, select[name="unidad_id"], select[name="estado"]').on('change', function() {
            cargarDatosInventario();
        });

        var consultaGuardada = sessionStorage.getItem('inventario_datos_mostrados') === '1';
        if (consultaGuardada && !ausenciaSuperaLimite()) {
            cargarDatosInventario();
        } else {
            ocultarDatosInventario(consultaGuardada ? 'ausencia' : 'inicial');
        }

        window.addEventListener('pagehide', registrarSalidaInventario);
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                registrarSalidaInventario();
            } else if (datosCargados) {
                if (ausenciaSuperaLimite()) {
                    ocultarDatosInventario('ausencia');
                } else {
                    sessionStorage.removeItem('inventario_fuera_desde');
                }
            }
        });
    });

    /* ========== Modal InvInventario ========== */
    function abrirModalInventario() {
        window.location.href = 'index.php?route=inv_items_sistema';
    }

    /* Auto-completar desde el catálogo de productos */
    function autoFillDesdeProducto() {
        var sel = document.getElementById('inv-sel-producto');
        if (!sel) return;
        var opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) {
            document.getElementById('inv-inp-producto-id').value = '';
            return;
        }
        document.getElementById('inv-inp-producto-id').value = opt.value;
        if (!document.getElementById('inv-inp-nombre').value) {
            document.getElementById('inv-inp-nombre').value = opt.getAttribute('data-nombre') || '';
        }
        var catId = opt.getAttribute('data-categoria');
        if (catId) {
            document.getElementById('inv-inp-categoria').value = catId;
        }
        var precio = parseFloat(opt.getAttribute('data-precio') || '0');
        if (precio > 0 && !document.getElementById('inv-inp-valor').value) {
            document.getElementById('inv-inp-valor').value = precio.toFixed(2);
        }
    }

    function cerrarModalInventario() {
        document.getElementById('inv-modal').classList.remove('active');
    }

    function editarRegistroInventario(item) {
        var itemId = (typeof item === 'object') ? (item.producto_id || item.id) : item;
        if (itemId) {
            window.location.href = 'index.php?route=inv_items_sistema&edit_id=' + itemId;
        } else {
            window.location.href = 'index.php?route=inv_items_sistema';
        }
    }

    function cerrarDetallesInventario() {
        document.getElementById('inv-modal-detalle').classList.remove('active');
    }

    function verDetallesInventario(id) {
        const content = document.getElementById('inv-det-content');
        content.innerHTML = '<div style="text-align:center;padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:32px;color:var(--primary);"></i><p style="margin-top:12px;">Cargando información del bien...</p></div>';
        document.getElementById('inv-modal-detalle').classList.add('active');

        fetch('index.php?route=inventario&action=verDetalle&id=' + id)
            .then(function(res) { return res.json(); })
            .then(function(item) {
                var icono = 'fa-box', colorTema = 'var(--primary)', bgTema = 'rgba(59,130,246,0.1)';
                if (item.categoria === 'Maquinaria Pesada')   { icono = 'fa-truck-monster'; colorTema = '#ef4444'; bgTema = 'rgba(239,68,68,0.1)'; }
                else if (item.categoria === 'Contenedores')   { icono = 'fa-box-open';      colorTema = '#3b82f6'; bgTema = 'rgba(59,130,246,0.1)'; }
                else if (item.categoria === 'Equipos de Muelle') { icono = 'fa-life-ring';  colorTema = '#10b981'; bgTema = 'rgba(16,185,129,0.1)'; }
                else if (item.categoria === 'Vehículos')      { icono = 'fa-truck-pickup';  colorTema = '#f59e0b'; bgTema = 'rgba(245,158,11,0.1)'; }
                else if (item.categoria === 'Herramientas')   { icono = 'fa-wrench';        colorTema = '#8b5cf6'; bgTema = 'rgba(139,92,246,0.1)'; }

                var vBase  = parseFloat(item.valor);
                var ivaCal = parseFloat(item.iva_calculado);
                var vTotal = parseFloat(item.valor_total);
                var resp   = item.responsable || 'Sin Responsable Asignado';
                var obs    = item.observaciones || 'Sin observaciones registradas.';

                content.innerHTML =
                    '<div style="display:flex;align-items:center;gap:18px;margin-bottom:24px;border-bottom:1px solid var(--border-color);padding-bottom:20px;">' +
                        '<div style="font-size:36px;color:' + colorTema + ';background:' + bgTema + ';width:68px;height:68px;border-radius:18px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid ' + icono + '"></i></div>' +
                        '<div style="flex:1;"><h2 style="margin:0 0 6px 0;font-size:22px;font-weight:700;">' + item.nombre + '</h2><span class="status-badge ' + item.estadoClase + '">' + item.estado + '</span></div>' +
                    '</div>' +
                    '<div class="modal-detail-layout">' +
                        '<div><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">' +
                            mkCampo(colorTema,'fa-tags','Categoría',item.categoria) +
                            mkCampo(colorTema,'fa-copyright','Marca',item.marca) +
                            mkCampo(colorTema,'fa-location-dot','Zona',item.zona) +
                            mkCampo(colorTema,'fa-user-tie','Responsable','<span style="color:var(--primary);font-weight:700;">' + resp + '</span>') +
                            mkCampo(colorTema,'fa-calendar-day','Registro',item.fecha_registro) +
                        '</div></div>' +
                        '<div>' +
                            '<div style="background:linear-gradient(135deg,rgba(59,130,246,0.04),rgba(59,130,246,0.08));padding:16px;border-radius:14px;margin-bottom:16px;border:1px solid rgba(59,130,246,0.12);">' +
                                '<h4 style="margin:0 0 12px 0;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;"><i class="fa-solid fa-calculator" style="color:var(--primary);margin-right:6px;"></i>Valores y Tasas</h4>' +
                                '<div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:12.5px;color:var(--text-muted);"><span>Valor Base:</span><strong>$' + vBase.toLocaleString('es-EC',{minimumFractionDigits:2}) + '</strong></div>' +
                                '<div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:12.5px;font-weight:700;color:var(--primary);"><span>' + (parseInt(item.aplica_iva,10) === 1 ? 'IVA del período (' + parseFloat(item.tasa_iva).toFixed(2) + '%)' : 'IVA (No aplica)') + ':</span><strong>$' + ivaCal.toLocaleString('es-EC',{minimumFractionDigits:2}) + '</strong></div>' +
                                '<div style="display:flex;justify-content:space-between;font-size:14px;border-top:1px dashed var(--border-color);padding-top:10px;font-weight:700;"><span>Costo Total:</span><strong style="font-size:16px;color:var(--primary);">$' + vTotal.toLocaleString('es-EC',{minimumFractionDigits:2}) + '</strong></div>' +
                            '</div>' +
                            '<div style="background:var(--panel-bg);padding:14px;border-radius:12px;border:1px solid var(--border-color);">' +
                                '<label style="display:flex;align-items:center;gap:8px;font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;"><i class="fa-solid fa-message" style="color:' + colorTema + ';"></i>Observaciones</label>' +
                                '<span style="font-size:13.5px;line-height:1.5;display:block;font-style:italic;">' + obs + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
            });
    }

    function mkCampo(color, icon, label, value) {
        return '<div style="background:var(--panel-bg);padding:12px 16px;border-radius:12px;border:1px solid var(--border-color);display:flex;flex-direction:column;gap:4px;">' +
            '<label style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;"><i class="fa-solid ' + icon + '" style="margin-right:6px;color:' + color + ';"></i>' + label + '</label>' +
            '<span style="font-size:14px;font-weight:600;color:var(--text-color);">' + value + '</span>' +
        '</div>';
    }
</script>

<!-- Botón Flotante para Desplazarse Arriba -->
<button id="btn-back-to-top" style="position:fixed;bottom:30px;right:30px;width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg, var(--primary), #2563eb);color:white;border:none;box-shadow:0 6px 16px rgba(37,99,235,0.25);cursor:pointer;display:none;align-items:center;justify-content:center;font-size:20px;z-index:999;transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);opacity:0;transform:translateY(10px);"><i class="fa-solid fa-arrow-up"></i></button>

<script>
window.addEventListener('scroll', function() {
    var btn = document.getElementById('btn-back-to-top');
    if (!btn) return;
    if (window.scrollY > 300) {
        btn.style.display = 'flex';
        setTimeout(function() {
            btn.style.opacity = '1';
            btn.style.transform = 'translateY(0)';
        }, 10);
    } else {
        btn.style.opacity = '0';
        btn.style.transform = 'translateY(10px)';
        setTimeout(function() {
            if (window.scrollY <= 300) btn.style.display = 'none';
        }, 300);
    }
});
document.getElementById('btn-back-to-top').addEventListener('click', function() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});
</script>
