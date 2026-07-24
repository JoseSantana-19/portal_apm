<?php
/**
 * REPORTES_VARIOS.PHP - Vista Unificada de Reportes Varios
 * Compatible con PHP 7.4, 8.3 y 8.4
 */

// Calcular estadísticas rápidas según el reporte activo
$totalRegistros = count($datosReporte);
$extraStat = 0;
$valorTotalReporte = 0.0;

if ($tabActivo === 'items') {
    foreach ($datosReporte as $item) {
        $extraStat += (int)$item['cantidad'];
        $valorTotalReporte += (float)($item['cantidad'] * $item['valor']);
    }
} elseif ($tabActivo === 'compras') {
    foreach ($datosReporte as $compra) {
        $extraStat += (int)$compra['cantidad'];
        $valorTotalReporte += (float)$compra['subtotal'];
    }
} elseif ($tabActivo === 'mensual') {
    foreach ($datosReporte as $m) {
        $extraStat += (int)$m['total_ordenes'];
        $valorTotalReporte += (float)$m['total_valor'];
    }
}
?>

<!-- InvCabecera de Página -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1>Reportes Varios de Auditoría</h1>
        <p>Listados consolidados, compras de insumos, auditoría de consumos y cortes financieros históricos.</p>
    </div>
    <?php if ($generarReporte && !empty($datosReporte)): ?>
    <div>
        <a href="index.php?route=reportes&action=imprimir&tab=<?= $tabActivo ?>&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>&proveedor=<?= urlencode($proveedor) ?>&termino=<?= urlencode($termino) ?>&id_inicio=<?= urlencode($idInicio) ?>&id_fin=<?= urlencode($idFin) ?>" 
           target="_blank" 
           class="btn-primary" 
           style="text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-file-pdf"></i> Imprimir Reporte Oficial
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Stats Rápido del Reporte Seleccionado -->
<div class="stats-row animate-fade-in" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-calculator"></i></div>
        <div>
            <div class="stat-value"><?= $totalRegistros ?></div>
            <div class="stat-label">Registros Listados</div>
        </div>
    </div>
    <?php if ($tabActivo === 'items' || $tabActivo === 'compras'): ?>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div>
                <div class="stat-value"><?= number_format($extraStat) ?></div>
                <div class="stat-label">Unidades Totales</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <div>
                <div class="stat-value">$<?= number_format($valorTotalReporte, 2) ?></div>
                <div class="stat-label">Valoración Total</div>
            </div>
        </div>
    <?php elseif ($tabActivo === 'mensual'): ?>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-cart-shopping"></i></div>
            <div>
                <div class="stat-value"><?= number_format($extraStat) ?></div>
                <div class="stat-label">Órdenes Realizadas</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <div>
                <div class="stat-value">$<?= number_format($valorTotalReporte, 2) ?></div>
                <div class="stat-label">Presupuesto Invertido</div>
            </div>
        </div>
    <?php else: ?>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fa-solid fa-clock"></i></div>
            <div>
                <div class="stat-value"><?= date('H:i') ?></div>
                <div class="stat-label">Hora del Reporte</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-calendar-check"></i></div>
            <div>
                <div class="stat-value"><?= date('d/M') ?></div>
                <div class="stat-label">Fecha del Sistema</div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Estructura por Pestañas Interiores -->
<div class="filter-section animate-fade-in" style="padding-bottom: 0; margin-bottom: 24px;">
    <div class="filter-tabs" style="border-bottom: 1px solid var(--border-color); display:flex; gap:16px;">
        
        <a href="index.php?route=reportes&tab=proveedores<?= $generarReporte ? '&generar=1' : '' ?>&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>&proveedor=<?= urlencode($proveedor) ?>&termino=<?= urlencode($termino) ?>&id_inicio=<?= urlencode($idInicio) ?>&id_fin=<?= urlencode($idFin) ?>" 
           class="filter-tab <?= ($tabActivo === 'proveedores') ? 'active' : '' ?>" 
           style="text-decoration:none; padding:12px 16px; border-bottom:3px solid <?= ($tabActivo === 'proveedores') ? 'var(--primary)' : 'transparent' ?>; display:flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-truck-field"></i> Listado de Proveedores
        </a>

        <a href="index.php?route=reportes&tab=centros_consumo<?= $generarReporte ? '&generar=1' : '' ?>&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>&proveedor=<?= urlencode($proveedor) ?>&termino=<?= urlencode($termino) ?>&id_inicio=<?= urlencode($idInicio) ?>&id_fin=<?= urlencode($idFin) ?>" 
           class="filter-tab <?= ($tabActivo === 'centros_consumo') ? 'active' : '' ?>" 
           style="text-decoration:none; padding:12px 16px; border-bottom:3px solid <?= ($tabActivo === 'centros_consumo') ? 'var(--primary)' : 'transparent' ?>; display:flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-building-flag"></i> Listado de Centros de Consumo
        </a>

        <a href="index.php?route=reportes&tab=items<?= $generarReporte ? '&generar=1' : '' ?>&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>&proveedor=<?= urlencode($proveedor) ?>&termino=<?= urlencode($termino) ?>&id_inicio=<?= urlencode($idInicio) ?>&id_fin=<?= urlencode($idFin) ?>" 
           class="filter-tab <?= ($tabActivo === 'items') ? 'active' : '' ?>" 
           style="text-decoration:none; padding:12px 16px; border-bottom:3px solid <?= ($tabActivo === 'items') ? 'var(--primary)' : 'transparent' ?>; display:flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-box"></i> Listado de Ítems
        </a>

        <a href="index.php?route=reportes&tab=compras<?= $generarReporte ? '&generar=1' : '' ?>&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>&proveedor=<?= urlencode($proveedor) ?>&termino=<?= urlencode($termino) ?>&id_inicio=<?= urlencode($idInicio) ?>&id_fin=<?= urlencode($idFin) ?>" 
           class="filter-tab <?= ($tabActivo === 'compras') ? 'active' : '' ?>" 
           style="text-decoration:none; padding:12px 16px; border-bottom:3px solid <?= ($tabActivo === 'compras') ? 'var(--primary)' : 'transparent' ?>; display:flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-shopping-bag"></i> Compras a Proveedores
        </a>

        <a href="index.php?route=reportes&tab=mensual<?= $generarReporte ? '&generar=1' : '' ?>&fecha_inicio=<?= urlencode($fechaInicio) ?>&fecha_fin=<?= urlencode($fechaFin) ?>&proveedor=<?= urlencode($proveedor) ?>&termino=<?= urlencode($termino) ?>&id_inicio=<?= urlencode($idInicio) ?>&id_fin=<?= urlencode($idFin) ?>" 
           class="filter-tab <?= ($tabActivo === 'mensual') ? 'active' : '' ?>" 
           style="text-decoration:none; padding:12px 16px; border-bottom:3px solid <?= ($tabActivo === 'mensual') ? 'var(--primary)' : 'transparent' ?>; display:flex; align-items:center; gap:8px;">
            <i class="fa-solid fa-calendar-days"></i> Reporte Mensual Órdenes
        </a>
    </div>

    <!-- Barra de Filtros Dinámica según la pestaña activa -->
    <form action="index.php" method="GET" class="filter-controls" style="padding:16px 0; margin-top:0;">
        <input type="hidden" name="route" value="reportes">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tabActivo) ?>">
        <input type="hidden" name="generar" value="1">

        <!-- Filtro: Rango de ID (Para todos los reportes) -->
        <div class="filter-group" style="max-width:120px;">
            <label><i class="fa-solid fa-hashtag"></i> ID Desde</label>
            <input type="number" name="id_inicio" placeholder="Mínimo" value="<?= htmlspecialchars($idInicio) ?>" min="1" style="width:100%; box-sizing:border-box;">
        </div>
        <div class="filter-group" style="max-width:120px;">
            <label><i class="fa-solid fa-hashtag"></i> ID Hasta</label>
            <input type="number" name="id_fin" placeholder="Máximo" value="<?= htmlspecialchars($idFin) ?>" min="1" style="width:100%; box-sizing:border-box;">
        </div>

        <!-- Filtro: Rango de fecha (solo para compras y reporte mensual) -->
        <?php if ($tabActivo === 'compras' || $tabActivo === 'mensual'): ?>
            <div class="filter-group">
                <label><i class="fa-solid fa-calendar"></i> Fecha Desde</label>
                <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fechaInicio) ?>">
            </div>
            <div class="filter-group">
                <label><i class="fa-solid fa-calendar"></i> Fecha Hasta</label>
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fechaFin) ?>">
            </div>
        <?php endif; ?>

        <!-- Filtro: Proveedor (solo para compras a proveedores) -->
        <?php if ($tabActivo === 'compras'): ?>
            <div class="filter-group" style="flex:1.5;">
                <label><i class="fa-solid fa-truck-field"></i> Filtrar por Proveedor</label>
                <select name="proveedor">
                    <option value="">Todos los proveedores...</option>
                    <?php foreach ($proveedores as $prov): ?>
                        <option value="<?= htmlspecialchars($prov['nombre']) ?>" <?= ($proveedor === $prov['nombre']) ? 'selected' : '' ?>><?= htmlspecialchars($prov['nombre']) ?> (RUC: <?= htmlspecialchars($prov['ruc']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <!-- Filtro: Búsqueda General (para todos los reportes) -->
        <div class="filter-group" style="flex:2;">
            <label><i class="fa-solid fa-magnifying-glass"></i> Búsqueda por ID / Término</label>
            <input type="text" name="termino" placeholder="Buscar por ID, códigos, nombres..." value="<?= htmlspecialchars($termino) ?>">
        </div>

        <div class="filter-actions" style="margin-top:auto;">
            <a href="index.php?route=reportes&tab=<?= $tabActivo ?>" class="btn-outline" style="height:40px; display:flex; align-items:center; justify-content:center; width:40px;" title="Limpiar Filtros">
                <i class="fa-solid fa-eraser"></i>
            </a>
            <button type="submit" class="btn-primary" style="height:40px;"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </div>
    </form>
</div>

<?php if ($generarReporte): ?>
<!-- Panel de Datos -->
<div class="panel animate-fade-in">
    <div class="panel-header">
        <h3 style="margin:0;">Resultados del Reporte (<?= $totalRegistros ?> registros cargados)</h3>
    </div>
    
    <div class="table-responsive">
        <?php if (empty($datosReporte)): ?>
            <div style="text-align:center; padding:60px 40px; color:var(--text-muted);">
                <i class="fa-solid fa-database" style="font-size:42px; display:block; margin-bottom:16px; opacity:0.3;"></i>
                <strong style="font-size:15px; color:var(--text-color);">No se encontraron registros coincidentes</strong>
                <p style="margin-top:6px; font-size:13px;">Prueba ajustando los filtros o realizando otra búsqueda general.</p>
            </div>
        <?php else: ?>
            <table>
                
                <!-- 1. Reporte: Proveedores -->
                <?php if ($tabActivo === 'proveedores'): ?>
                    <thead>
                        <tr>
                            <th style="width: 80px;">Código ID</th>
                            <th>Razón Social / Nombre del Proveedor</th>
                            <th style="width: 180px;">RUC / Identificación</th>
                            <th>Contacto y Dirección física</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datosReporte as $r): ?>
                            <tr>
                                <td><strong>#<?= $r['id'] ?></strong></td>
                                <td style="font-weight: 600; color: var(--text-color);"><?= htmlspecialchars($r['nombre']) ?></td>
                                <td><code style="background:var(--border-color); padding:3px 8px; border-radius:5px; font-weight:700; font-size:12px;"><?= htmlspecialchars($r['ruc']) ?></code></td>
                                <td style="font-size: 13px; color: var(--text-muted);"><?= htmlspecialchars($r['extra'] ?? 'Sin contacto registrado') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                <!-- 2. Reporte: Centros de Consumo -->
                <?php elseif ($tabActivo === 'centros_consumo'): ?>
                    <thead>
                        <tr>
                            <th style="width: 100px;">Código</th>
                            <th>Descripción / Puesto del Centro</th>
                            <th>Funcionario Responsable</th>
                            <th>Grupo Organizativo</th>
                            <th style="text-align:center; width: 130px;">Total Despachos</th>
                            <th style="text-align:center; width: 130px;">Unids Entregadas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datosReporte as $r): ?>
                            <tr>
                                <td><code style="background:var(--border-color); padding:3px 8px; border-radius:5px; font-weight:700; font-size:12px; color:var(--primary);"><?= htmlspecialchars($r['codigo']) ?></code></td>
                                <td style="font-weight: 600; color: var(--text-color);"><?= htmlspecialchars($r['nombre']) ?></td>
                                <td><strong><?= htmlspecialchars($r['funcionario']) ?></strong></td>
                                <td><span class="status-badge active" style="background:rgba(139,92,246,0.1); color:#8b5cf6; border-color:rgba(139,92,246,0.2); font-size:11px;"><?= htmlspecialchars($r['grupo_nombre']) ?> (<?= htmlspecialchars($r['grupo_codigo']) ?>)</span></td>
                                <td style="text-align:center;"><strong><?= $r['total_egresos'] ?></strong></td>
                                <td style="text-align:center;"><span class="status-badge dispatched" style="font-weight:700;"><?= $r['total_items'] ?> u.</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                <!-- 3. Reporte: Items de Catálogo -->
                <?php elseif ($tabActivo === 'items'): ?>
                    <thead>
                        <tr>
                            <th style="width: 100px;">Secuencial</th>
                            <th>Nombre del Producto / Insumo</th>
                            <th>Marca</th>
                            <th>Categoría / Grupo</th>
                            <th style="text-align:center; width: 100px;">Existencia</th>
                            <th style="text-align:right; width: 120px;">Costo Base</th>
                            <th style="text-align:right; width: 140px;">Valor Total Stock</th>
                        </tr>
                    </thead>
                    <tbody id="reporte-items-tbody">
                        <!-- Las filas se inyectan dinámicamente con paginación JS -->
                    </tbody>

                <!-- 4. Reporte: Compras a Proveedores -->
                <?php elseif ($tabActivo === 'compras'): ?>
                    <thead>
                        <tr>
                            <th style="width: 100px;">Ingreso</th>
                            <th style="width: 110px;">Fecha</th>
                            <th>Proveedor</th>
                            <th>Producto / Insumo</th>
                            <th style="text-align:center; width: 90px;">Cantidad</th>
                            <th style="text-align:right; width: 110px;">V. Unitario</th>
                            <th style="text-align:right; width: 130px;">Subtotal ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datosReporte as $r): ?>
                            <tr>
                                <td class="secuencial-cell"><?= htmlspecialchars($r['ingreso_codigo']) ?></td>
                                <td><?= htmlspecialchars($r['fecha']) ?></td>
                                <td style="font-weight:600; color:var(--text-color);"><?= htmlspecialchars($r['proveedor']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($r['item_nombre']) ?></strong><br>
                                    <span style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($r['item_secuencial']) ?></span>
                                </td>
                                <td style="text-align:center;"><span class="status-badge transit" style="background:#e0f2fe; color:#0369a1; font-weight:700;"><?= $r['cantidad'] ?> <?= htmlspecialchars($r['unidad'] ?? 'u.') ?></span></td>
                                <td style="text-align:right; color:var(--text-color); font-weight:600;">$<?= number_format($r['valor_unitario'], 2) ?></td>
                                <td style="text-align:right; font-weight:700; color:var(--primary);">$<?= number_format($r['subtotal'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--secondary-bg); font-weight:700;">
                            <td colspan="4" style="text-align:right; padding:16px;">Sumatoria Total de Adquisiciones:</td>
                            <td style="text-align:center; font-size:14px; color:var(--text-color);"><?= number_format($extraStat) ?> u.</td>
                            <td></td>
                            <td style="text-align:right; font-size:15px; color:#10b981;">$<?= number_format($valorTotalReporte, 2) ?></td>
                        </tr>
                    </tfoot>

                <!-- 5. Reporte: Mensual de Órdenes de Compras -->
                <?php elseif ($tabActivo === 'mensual'): ?>
                    <thead>
                        <tr>
                            <th>Mes Fiscal de Adquisición</th>
                            <th style="text-align:center; width: 150px;">Total de Órdenes</th>
                            <th style="text-align:center; width: 150px;">Volumen de Items</th>
                            <th style="text-align:right; width: 180px;">Presupuesto Invertido ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datosReporte as $r): ?>
                            <tr>
                                <td style="font-size:14px; font-weight:700; color:var(--text-color);">
                                    <i class="fa-regular fa-calendar-check" style="margin-right:8px; color:var(--primary);"></i>
                                    <?php 
                                    $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
                                    $parts = explode('-', $r['mes']);
                                    echo isset($meses[$parts[1]]) ? $meses[$parts[1]] . ' ' . $parts[0] : $r['mes'];
                                    ?>
                                </td>
                                <td style="text-align:center;"><span class="status-badge active" style="font-weight:700;"><?= $r['total_ordenes'] ?> órdenes</span></td>
                                <td style="text-align:center;"><span class="status-badge transit" style="font-weight:700;"><?= number_format($r['total_items']) ?> unidades</span></td>
                                <td style="text-align:right; font-weight:700; color:var(--primary); font-size:15px;">$<?= number_format($r['total_valor'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:var(--secondary-bg); font-weight:700;">
                            <td style="padding:16px;">Acumulados Consolidados del Período:</td>
                            <td style="text-align:center; font-size:14px;"><?= number_format($extraStat) ?> ord.</td>
                            <td style="text-align:center; font-size:14px;">
                                <?php
                                $sumaItemsTotal = 0;
                                foreach ($datosReporte as $d) $sumaItemsTotal += (int)$d['total_items'];
                                echo number_format($sumaItemsTotal) . ' u.';
                                ?>
                            </td>
                            <td style="text-align:right; font-size:15px; color:#10b981;">$<?= number_format($valorTotalReporte, 2) ?></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>

            </table>
        <?php endif; ?>
    </div>

    <!-- Paginación para Reporte de Ítems (Solo si está activo y tiene datos) -->
    <?php if ($tabActivo === 'items' && !empty($datosReporte)): ?>
        <div id="reporte-items-paginacion" style="display:flex;justify-content:space-between;align-items:center;padding:14px 24px;border-top:1px solid var(--border-color);background:var(--secondary-bg);flex-wrap:wrap;gap:12px;border-bottom-left-radius:10px;border-bottom-right-radius:10px;">
            <div style="font-size:13px;color:var(--text-muted);">
                Mostrando <span id="rep-pag-rango" style="font-weight:700;color:var(--text-color);">0 - 0</span> de <span id="rep-pag-total" style="font-weight:700;color:var(--text-color);">0</span> registros
            </div>
            <div style="display:flex;align-items:center;gap:6px;" id="rep-pag-botones">
                <!-- Botones dinámicos inyectados por JS -->
            </div>
        </div>
    <?php endif; ?>
</div>
<?php else: ?>
<!-- Llamada a la acción para generación bajo demanda -->
<div class="glass-placeholder animate-fade-in" style="text-align:center; padding:60px 40px; background: rgba(255, 255, 255, 0.4); backdrop-filter: blur(12px); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.6); box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07); margin-top: 24px;">
    <i class="fa-solid fa-file-invoice" style="font-size:54px; display:block; margin-bottom:20px; color: var(--primary); opacity: 0.8; filter: drop-shadow(0 4px 6px rgba(59, 130, 246, 0.2));"></i>
    <h3 style="font-size:18px; color:var(--text-color); margin-bottom:8px; font-weight:700;">Generación de Reporte Bajo Demanda</h3>
    <p style="color:var(--text-muted); max-width:500px; margin:0 auto 24px auto; font-size:14px; line-height:1.6;">
        Para agilizar la carga del sistema, este listado se genera únicamente cuando lo solicitas. Ajusta los filtros en la parte superior y haz clic en el botón <strong>Filtrar</strong> para obtener los datos oficiales actualizados.
    </p>
    <button type="button" onclick="document.querySelector('.filter-controls').submit();" class="btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:12px 24px; font-size:14px; font-weight:600; border-radius:8px; border: none; cursor: pointer;">
        <i class="fa-solid fa-play"></i> Cargar Listado Oficial
    </button>
</div>
<?php endif; ?>

<?php if ($tabActivo === 'items' && !empty($datosReporte)): ?>
<script>
var _repItems = <?php echo json_encode(array_values($datosReporte)); ?>;
var _repPage = 1;
var _repLimit = 50;

function renderReporteItems(page) {
    if (!page) page = 1;
    _repPage = page;

    var tbody = document.getElementById('reporte-items-tbody');
    if (!tbody) return;

    var start = (page - 1) * _repLimit;
    var end = Math.min(start + _repLimit, _repItems.length);
    var html = '';

    for (var idx = start; idx < end; idx++) {
        var r = _repItems[idx];
        var cant = parseFloat(r.cantidad) || 0;
        var valor = parseFloat(r.valor) || 0;
        var total = cant * valor;

        var statusBadge = '';
        if (cant <= 0) {
            statusBadge = '<span class="status-badge inactive" style="font-weight:700;">Agotado (0)</span>';
        } else if (cant <= 5) {
            statusBadge = '<span class="status-badge pending" style="font-weight:700;">Crítico (' + cant + ')</span>';
        } else {
            statusBadge = '<span class="status-badge active" style="font-weight:700;">' + cant + ' ' + (r.unidad_abreviatura || 'u.') + '</span>';
        }

        html += '<tr>' +
            '<td class="secuencial-cell">' + (r.secuencial || '') + '</td>' +
            '<td style="font-weight: 600; color: var(--text-color);">' + (r.nombre || '') + '</td>' +
            '<td><strong>' + (r.marca || '') + '</strong></td>' +
            '<td><span class="status-badge transit" style="font-size:11px;">' + (r.categoria_nombre || '') + '</span></td>' +
            '<td style="text-align:center;">' + statusBadge + '</td>' +
            '<td style="text-align:right; font-weight:600; color:var(--text-color);">$ ' + valor.toLocaleString('es-EC', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
            '<td style="text-align:right; font-weight:700; color:var(--primary);">$ ' + total.toLocaleString('es-EC', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</td>' +
        '</tr>';
    }

    tbody.innerHTML = html;
    renderReportePaginacion(_repItems.length, _repLimit, page);
}

function renderReportePaginacion(total, limit, activePage) {
    var rangoSpan = document.getElementById('rep-pag-rango');
    var totalSpan = document.getElementById('rep-pag-total');
    var botonesDiv = document.getElementById('rep-pag-botones');

    if (!rangoSpan || !totalSpan || !botonesDiv) return;

    totalSpan.textContent = total;

    var totalPages = Math.ceil(total / limit);
    var start = (activePage - 1) * limit + 1;
    var end = Math.min(activePage * limit, total);
    rangoSpan.textContent = start + ' - ' + end;

    var html = '';
    
    // Botón Anterior
    var prevDisabled = activePage === 1 ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : '';
    html += '<button type="button" class="btn-outline" style="padding:6px 12px;font-size:12px;border-radius:6px;" onclick="renderReporteItems(' + (activePage - 1) + ')" ' + prevDisabled + '><i class="fa-solid fa-angle-left"></i> Anterior</button>';

    // Rango de páginas (máximo 5 botones)
    var startPage = Math.max(1, activePage - 2);
    var endPage = Math.min(totalPages, activePage + 2);

    if (startPage > 1) {
        html += '<button type="button" class="btn-outline" style="padding:6px 10px;font-size:12px;border-radius:6px;" onclick="renderReporteItems(1)">1</button>';
        if (startPage > 2) html += '<span style="color:var(--text-muted);padding:0 4px;font-size:12px;">...</span>';
    }

    for (var p = startPage; p <= endPage; p++) {
        var activeStyle = p === activePage ? 'background:var(--primary);color:white;border-color:var(--primary);font-weight:700;' : '';
        html += '<button type="button" class="btn-outline" style="padding:6px 10px;font-size:12px;border-radius:6px;' + activeStyle + '" onclick="renderReporteItems(' + p + ')">' + p + '</button>';
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += '<span style="color:var(--text-muted);padding:0 4px;font-size:12px;">...</span>';
        html += '<button type="button" class="btn-outline" style="padding:6px 10px;font-size:12px;border-radius:6px;" onclick="renderReporteItems(' + totalPages + ')">' + totalPages + '</button>';
    }

    // Botón Siguiente
    var nextDisabled = activePage === totalPages ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : '';
    html += '<button type="button" class="btn-outline" style="padding:6px 12px;font-size:12px;border-radius:6px;" onclick="renderReporteItems(' + (activePage + 1) + ')" ' + nextDisabled + '>Siguiente <i class="fa-solid fa-angle-right"></i></button>';

    botonesDiv.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    renderReporteItems(1);
});
</script>
<?php endif; ?>
