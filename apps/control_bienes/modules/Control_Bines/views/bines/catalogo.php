<?php
/**
 * INV_ITEMS.PHP - Catálogo de Ítems agrupado por Grupo
 * Vista de lista colapsable optimizada: carga ítems de forma diferida (Lazy Load AJAX) dentro de los acordeones.
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
$totalGruposCatalogo = 0;
$totalItemsCatalogo = 0;
$valorCatalogo = 0.0;
foreach ($resumenCategorias as $resumenCatalogo) {
    if ((int)$resumenCatalogo['total_items'] <= 0) continue;
    $totalGruposCatalogo++;
    $totalItemsCatalogo += (int)$resumenCatalogo['total_items'];
    $valorCatalogo += (float)$resumenCatalogo['total_value'];
}
$limpiarCodigoVisible = static function ($nombre) {
    return trim((string)preg_replace('/\s*\([0-9.]+\)\s*$/u', '', (string)$nombre));
};
?>
<style>
.catalogo-hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 28px;
    align-items: center;
    padding: 28px 30px;
    margin-bottom: 20px;
    border-radius: 18px;
    color: #fff;
    background: linear-gradient(125deg, #172554 0%, #1d4ed8 58%, #06b6d4 120%);
    box-shadow: 0 18px 40px rgba(30, 64, 175, 0.2);
}
.catalogo-hero::after {
    content: '';
    position: absolute;
    width: 260px;
    height: 260px;
    right: -80px;
    top: -125px;
    border-radius: 50%;
    background: rgba(255,255,255,.1);
}
.catalogo-hero-copy { position: relative; z-index: 1; }
.catalogo-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 9px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: #bae6fd;
}
.catalogo-hero h1 { margin: 0 0 7px; font-size: 27px; letter-spacing: -.5px; }
.catalogo-hero p { margin: 0; max-width: 720px; color: rgba(255,255,255,.78); line-height: 1.55; }
.catalogo-stats { position: relative; z-index: 1; display: flex; gap: 10px; }
.catalogo-stat {
    min-width: 112px;
    padding: 13px 16px;
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 13px;
    background: rgba(255,255,255,.11);
    backdrop-filter: blur(8px);
}
.catalogo-stat strong { display: block; font-size: 21px; line-height: 1.1; }
.catalogo-stat span { display: block; margin-top: 4px; font-size: 10px; font-weight: 700; color: rgba(255,255,255,.7); text-transform: uppercase; letter-spacing: .05em; }
.catalogo-buscador {
    margin-bottom: 24px;
    padding: 18px 20px;
    border: 1px solid var(--border-color);
    border-radius: 15px;
    background: var(--panel-bg);
    box-shadow: 0 8px 24px rgba(15,23,42,.06);
}
.catalogo-buscador .filter-controls { padding: 0; }
.catalogo-buscador input[type="text"] { width: 100%; height: 44px; padding-left: 42px; box-sizing: border-box; background: var(--secondary-bg); }
.catalogo-search-wrap { position: relative; width: 100%; }
.catalogo-search-wrap > i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--primary); z-index: 1; }
.catalogo-section-title { display: flex; align-items: end; justify-content: space-between; gap: 18px; margin: 0 2px 14px; }
.catalogo-section-title h2 { margin: 0; font-size: 17px; color: var(--text-color); }
.catalogo-section-title p { margin: 4px 0 0; font-size: 12.5px; color: var(--text-muted); }
.catalogo-result-count { font-size: 12px; font-weight: 700; color: var(--primary); background: rgba(37,99,235,.09); padding: 7px 11px; border-radius: 999px; white-space: nowrap; }
.grupo-tabla-section {
    --grupo-color: #2563eb;
    --grupo-soft: rgba(37,99,235,.1);
    margin-bottom: 14px;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid var(--border-color);
    background: var(--panel-bg);
    box-shadow: 0 7px 22px rgba(15,23,42,.06);
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.grupo-tabla-section:hover {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--grupo-color) 35%, var(--border-color));
    box-shadow: 0 13px 30px rgba(15,23,42,.1);
}
.grupo-tabla-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 17px 20px;
    cursor: pointer;
    user-select: none;
    background: linear-gradient(90deg, var(--grupo-soft), transparent 48%);
    border-left: 4px solid var(--grupo-color);
    transition: background .2s ease;
}
.grupo-tabla-header:hover { background: linear-gradient(90deg, var(--grupo-soft), rgba(148,163,184,.04) 65%); }
.grupo-icono-box {
    width: 48px; height: 48px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 19px;
    flex-shrink: 0;
    color: var(--grupo-color);
    background: var(--grupo-soft);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--grupo-color) 18%, transparent);
}
.grupo-info { flex: 1; }
.grupo-info h2 {
    margin: 0 0 6px;
    font-size: 14.5px;
    font-weight: 800;
    color: var(--text-color);
    letter-spacing: -.15px;
}
.grupo-meta { display: flex; flex-wrap: wrap; gap: 7px; }
.grupo-meta span { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; color: var(--text-muted); }
.grupo-meta span + span::before { content: ''; width: 3px; height: 3px; margin-right: 2px; border-radius: 50%; background: var(--grupo-color); }
.grupo-meta strong { color: var(--text-color); }
.grupo-toggle-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 14px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    border: 1px solid color-mix(in srgb, var(--grupo-color) 22%, var(--border-color));
    transition: all .2s ease;
    background: var(--panel-bg);
    color: var(--grupo-color);
}
.grupo-toggle-badge:hover { color: #fff; background: var(--grupo-color); }
.grupo-tabla-body { display: none; }
.grupo-tabla-body.abierto { display: block; border-top: 1px solid var(--border-color); }
.grupo-tabla-body table { margin: 0; }
.grupo-tabla-body th, .grupo-tabla-body td {
    padding: 12px 15px;
    font-size: 13px;
}
.grupo-tabla-body thead tr {
    background: var(--secondary-bg);
}
.grupo-tabla-body tbody tr:hover { background: color-mix(in srgb, var(--grupo-color) 4%, var(--panel-bg)); }
.grupo-tabla-body .dataTables_wrapper { padding: 14px 16px 18px; }
.grupo-tabla-body .dataTables_filter input {
    min-width: 260px;
    height: 38px;
    border: 1px solid var(--border-color);
    border-radius: 9px;
    background: var(--panel-bg);
    color: var(--text-color);
}
.grupo-tabla-body .dt-buttons { display:flex; gap:7px; margin-bottom:10px; }
.grupo-tabla-body .dt-button {
    border: 1px solid color-mix(in srgb, var(--grupo-color) 30%, var(--border-color)) !important;
    border-radius: 8px !important;
    color: var(--grupo-color) !important;
    background: var(--panel-bg) !important;
    font-weight: 700;
}
.qty-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 700;
}
.qty-badge.ok    { background: rgba(16,185,129,0.12); color: #10b981; }
.qty-badge.warn  { background: rgba(245,158,11,0.12);  color: #f59e0b; }
.qty-badge.empty { background: rgba(239,68,68,0.12);   color: #ef4444; }
@media (max-width: 900px) {
    .catalogo-hero { grid-template-columns: 1fr; }
    .catalogo-stats { flex-wrap: wrap; }
    .catalogo-stat { flex: 1; }
}
@media (max-width: 620px) {
    .catalogo-hero { padding: 23px 20px; }
    .catalogo-hero h1 { font-size: 23px; }
    .grupo-tabla-header { align-items: flex-start; padding: 15px; }
    .grupo-toggle-badge span { display: none; }
    .catalogo-section-title { align-items: flex-start; flex-direction: column; }
}
</style>

<!-- Presentación y resumen del catálogo -->
<section class="catalogo-hero animate-fade-in">
    <div class="catalogo-hero-copy">
        <div class="catalogo-eyebrow"><i class="fa-solid fa-layer-group"></i> Inventario organizado</div>
        <h1>Catálogo de Ítems</h1>
        <p>Explora los bienes por grupo contable, consulta existencias y valores, y encuentra registros mediante su nombre, marca o código interno.</p>
    </div>
    <div class="catalogo-stats">
        <div class="catalogo-stat"><strong><?= number_format($totalGruposCatalogo) ?></strong><span>Grupos visibles</span></div>
        <div class="catalogo-stat"><strong><?= number_format($totalItemsCatalogo) ?></strong><span>Ítems activos</span></div>
        <div class="catalogo-stat"><strong><?= htmlspecialchars(CommonHelper::formatearImporte($valorCatalogo)) ?></strong><span>Valor inventariado</span></div>
    </div>
</section>

<!-- Barra de Búsqueda -->
<div class="catalogo-buscador animate-fade-in">
    <form action="index.php" method="GET" class="filter-controls">
        <input type="hidden" name="route" value="items">
        <div class="filter-group" style="flex:2;">
            <label><i class="fa-solid fa-magnifying-glass" style="color:var(--primary);margin-right:5px;"></i> Buscar en el catálogo</label>
            <div class="catalogo-search-wrap">
                <i class="fa-solid fa-search"></i>
                <input type="text" name="termino" id="txt-termino"
                       placeholder="Nombre, marca o código interno..."
                       value="<?= htmlspecialchars($filtros['termino'] ?? '') ?>">
            </div>
        </div>
        <div class="filter-actions">
            <a href="index.php?route=items" class="btn-outline" style="height:40px;display:flex;align-items:center;justify-content:center;" title="Limpiar">
                <i class="fa-solid fa-eraser"></i>
            </a>
            <button type="submit" class="btn-primary" style="height:40px;">
                <i class="fa-solid fa-search"></i> Buscar
            </button>
        </div>
    </form>
</div>

<!-- Catálogo Agrupado en Tablas -->
<div class="catalogo-section-title animate-fade-in">
    <div>
        <h2>Grupos del catálogo</h2>
        <p>Selecciona un grupo para consultar sus ítems sin recargar la página.</p>
    </div>
    <span class="catalogo-result-count"><?= number_format($totalGruposCatalogo) ?> grupo<?= $totalGruposCatalogo === 1 ? '' : 's' ?></span>
</div>
<div class="animate-fade-in" id="catalogo-grupos">
<?php if (empty($resumenCategorias)): ?>
    <div class="panel" style="text-align:center;padding:60px;color:var(--text-muted);">
        <i class="fa-solid fa-folder-open" style="font-size:48px;display:block;margin-bottom:16px;opacity:0.3;"></i>
        <h3>No se encontraron ítems</h3>
        <p>Ajusta los términos de búsqueda o registra un nuevo equipo en Inventario General.</p>
    </div>
<?php else:
    $paletaGrupos = [
        ['color' => '#2563eb', 'soft' => 'rgba(37,99,235,.10)',  'icono' => 'fa-boxes-stacked'],
        ['color' => '#0284c7', 'soft' => 'rgba(2,132,199,.10)',   'icono' => 'fa-cubes'],
        ['color' => '#4f46e5', 'soft' => 'rgba(79,70,229,.10)',  'icono' => 'fa-layer-group'],
        ['color' => '#0369a1', 'soft' => 'rgba(3,105,161,.10)',  'icono' => 'fa-box-open'],
        ['color' => '#1d4ed8', 'soft' => 'rgba(29,78,216,.10)',  'icono' => 'fa-warehouse'],
        ['color' => '#0891b2', 'soft' => 'rgba(8,145,178,.10)',  'icono' => 'fa-tags'],
    ];

    $grupoIdx = 0;
    foreach ($resumenCategorias as $catResumen):
        $grupoId     = (int)$catResumen['categoria_id'];
        $grupoNombre = $catResumen['categoria_nombre'];
        $grupoNombreVisible = $limpiarCodigoVisible($grupoNombre);
        $p           = $paletaGrupos[$grupoIdx % count($paletaGrupos)];
        $color       = $p['color'];
        $soft        = $p['soft'];
        $icono       = $p['icono'];
        
        $total       = (int)$catResumen['total_items'];
        // Ocultar categorías vacías bajo filtros activos
        if ($total <= 0) continue;

        $totalQty    = number_format((float)$catResumen['total_qty'], 0);
        $valorTotal  = CommonHelper::formatearImporte($catResumen['total_value'], false);
        
        $bodyId      = 'grp-body-' . $grupoIdx;
        $grupoIdx++;
?>
    <div class="grupo-tabla-section animate-fade-in" style="--grupo-color:<?= $color ?>;--grupo-soft:<?= $soft ?>;">
        <!-- InvCabecera del Grupo -->
        <div class="grupo-tabla-header" onclick="toggleGrupoTabla(<?= $grupoId ?>, '<?= $bodyId ?>', this)">
            <div class="grupo-icono-box">
                <i class="fa-solid <?= $icono ?>"></i>
            </div>
            <div class="grupo-info">
                <h2><?= htmlspecialchars($grupoNombreVisible) ?></h2>
                <div class="grupo-meta">
                    <span><i class="fa-regular fa-rectangle-list"></i> <strong><?= $total ?></strong> ítem<?= $total !== 1 ? 's' : '' ?></span>
                    <span><i class="fa-solid fa-boxes-stacked"></i> Cantidad <strong><?= $totalQty ?></strong></span>
                    <span><i class="fa-solid fa-coins"></i> Valor <strong>$<?= $valorTotal ?></strong></span>
                </div>
            </div>
            <div class="grupo-toggle-badge" id="badge-<?= $bodyId ?>">
                <i class="fa-solid fa-eye" id="badge-icon-<?= $bodyId ?>"></i>
                <span id="badge-lbl-<?= $bodyId ?>">Ver ítems</span>
            </div>
        </div>

        <!-- Tabla de Ítems del Grupo (colapsable y cargada por AJAX) -->
        <div class="grupo-tabla-body" id="<?= $bodyId ?>" data-cargado="false" data-grupo-nombre="<?= htmlspecialchars($grupoNombreVisible) ?>">
            <div class="table-responsive">
                <table id="catalogo-tabla-<?= $grupoId ?>" class="catalogo-datatable display" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:105px;">Código</th>
                            <th>Descripción / Nombre</th>
                            <th>Marca</th>
                            <th style="width:120px;">Unidad</th>
                            <th style="width:100px;text-align:center;">Existencia</th>
                            <th style="width:110px;text-align:right;">Precio Base</th>
                            <th style="width:120px;text-align:right;">Total</th>
                            <th style="width:90px;text-align:center;">Estado</th>
                            <th class="columna-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="items-ajax-tbody">
                        <!-- Filas inyectadas dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Modal: Editar Ítem -->
<div class="modal-overlay" id="inv-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="inv-modal-title">Editar Registro</h2>
            <button class="modal-close" onclick="document.getElementById('inv-modal').classList.remove('active')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="index.php?route=inventario&action=guardar" method="POST">
            <input type="hidden" name="id" id="inv-inp-id" value="0">
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
                        <label>Ubicación / Zona</label>
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
                        <label>Responsable</label>
                        <select name="responsable_id" id="inv-inp-responsable">
                            <option value="">Sin Responsable</option>
                            <?php foreach ($personal as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Valor Monetario Base ($)</label>
                        <input type="number" data-price-input step="<?= htmlspecialchars(CommonHelper::pasoPrecio()) ?>" name="valor" id="inv-inp-valor" required placeholder="Ej: <?= htmlspecialchars(number_format(8500, CommonHelper::decimalesPrecio(), '.', '')) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="observaciones" id="inv-inp-obs" placeholder="Detalles adicionales..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="document.getElementById('inv-modal').classList.remove('active')">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Detalle del Ítem -->
<div class="modal-overlay" id="inv-modal-detalle">
    <div class="modal-content modal-content-wide">
        <div class="modal-header">
            <h2>Detalle Completo del Registro</h2>
            <button class="modal-close" onclick="document.getElementById('inv-modal-detalle').classList.remove('active')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="inv-det-content"></div>
    </div>
</div>

<!-- Botón Flotante para Desplazarse Arriba -->
<button id="btn-back-to-top" style="position:fixed;bottom:30px;right:30px;width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg, var(--primary), #2563eb);color:white;border:none;box-shadow:0 6px 16px rgba(37,99,235,0.25);cursor:pointer;display:none;align-items:center;justify-content:center;font-size:20px;z-index:999;transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);opacity:0;transform:translateY(10px);"><i class="fa-solid fa-arrow-up"></i></button>

<script>
/* ===== Toggle y Carga Diferida (Lazy Load) de Categorías ===== */
function toggleGrupoTabla(categoriaId, bodyId, headerEl) {
    var body      = document.getElementById(bodyId);
    var badge     = document.getElementById('badge-' + bodyId);
    var badgeIco  = document.getElementById('badge-icon-' + bodyId);
    var badgeLbl  = document.getElementById('badge-lbl-' + bodyId);
    var abierto   = body.classList.contains('abierto');

    if (abierto) {
        body.classList.remove('abierto');
        badgeIco.className = 'fa-solid fa-eye';
        badgeLbl.textContent = 'Ver ítems';
        return;
    }

    body.classList.add('abierto');
    badgeIco.className = 'fa-solid fa-eye-slash';
    badgeLbl.textContent = 'Ocultar';

    // Si ya está cargado, no volver a solicitar
    if (body.getAttribute('data-cargado') === 'true') {
        return;
    }

    var tbody = body.querySelector('.items-ajax-tbody');
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:32px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;color:var(--primary);margin-right:8px;display:block;margin-bottom:10px;"></i>Consultando registros en la base de datos...</td></tr>';

    var termino = document.getElementById('txt-termino').value;

    fetch('index.php?route=inventario&action=obtenerItemsCatalogo&categoria_id=' + categoriaId + '&termino=' + encodeURIComponent(termino))
        .then(function(r) { return r.json(); })
        .then(function(items) {
            body.setAttribute('data-cargado', 'true');
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:24px;color:var(--text-muted);"><i class="fa-solid fa-inbox" style="margin-right:6px;"></i>No se encontraron ítems en esta categoría.</td></tr>';
                return;
            }

            var html = '';
            items.forEach(function(item) {
                var cant = parseInt(item.cantidad) || 1;
                var qtyClass = cant <= 0 ? 'empty' : (cant <= 5 ? 'warn' : 'ok');
                var qtyIcon = cant <= 0 ? 'fa-circle-xmark' : (cant <= 5 ? 'fa-triangle-exclamation' : 'fa-circle-check');
                var totalVal = parseFloat(item.valor) * cant;

                var itemJson = JSON.stringify(item).replace(/"/g, '&quot;');
                var unidadStr = item.unidad_abrev || item.unidad_nombre || 'u.';
                var codigoProducto = item.producto_codigo || item.codigo_clasificacion || item.secuencial || item.codigo || '—';

                html += '<tr>' +
                    '<td><code style="font-family:monospace;font-weight:800;font-size:12px;color:var(--primary);white-space:nowrap;">' + limpiarCodigoVisible(codigoProducto) + '</code></td>' +
                    '<td><strong style="display:block;color:var(--text-color);font-size:13.5px;">' + (item.nombre || '') + '</strong>' +
                        '<span style="display:block;margin-top:3px;font-size:11px;color:var(--text-muted);">' + limpiarCodigoVisible(item.categoria || '') + '</span></td>' +
                    '<td style="font-size:13px;color:var(--text-muted);">' + (item.marca || '') + '</td>' +
                    '<td style="font-size:13px;color:var(--text-muted);font-weight:600;">' + unidadStr + '</td>' +
                    '<td style="text-align:center;">' +
                        '<span class="qty-badge ' + qtyClass + '">' +
                            '<i class="fa-solid ' + qtyIcon + '" style="font-size:11px;"></i> ' + cant +
                        '</span>' +
                    '</td>' +
                    '<td style="font-size:13px;font-weight:600;text-align:right;color:var(--text-color);">' + window.InvMoney.formatPrice(item.valor) + '</td>' +
                    '<td style="text-align:right;"><strong style="font-size:13px;color:var(--primary);">' +
                        '$' + totalVal.toLocaleString('es-EC', {minimumFractionDigits:2, maximumFractionDigits:2}) +
                        '</strong><span style="display:block;font-size:10.5px;color:var(--text-muted);">×' + cant + ' und.</span>' +
                    '</td>' +
                    '<td style="text-align:center;"><span class="status-badge ' + (item.estadoClase || 'inactive') + '" style="font-size:11px;padding:3px 8px;">' +
                        (item.estado || 'Desconocido') + '</span></td>' +
                    '<td class="acciones-cell columna-acciones">' +
                        '<button class="btn-accion btn-ver" onclick="verDetallesInventario(' + item.id + ')" title="Ver Detalle"><i class="fa-solid fa-eye"></i></button>' +
                        '<button class="btn-accion btn-editar" onclick="editarRegistroInventario(' + itemJson + ')" title="Editar en Maestro de Ítems"><i class="fa-solid fa-pen"></i></button>' +
                    '</td>' +
                '</tr>';
            });

            tbody.innerHTML = html;
            inicializarTablaCatalogo(body);
        })
        .catch(function(e) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:48px;color:#ef4444;"><i class="fa-solid fa-triangle-exclamation" style="font-size:32px;display:block;margin-bottom:12px;"></i>Error al consultar los ítems. Intente de nuevo.</td></tr>';
        });
}

/* ===== Edición centralizada en Maestro de Ítems ===== */
function editarRegistroInventario(item) {
    var productoId = parseInt(item && item.producto_id, 10);
    var destino = 'index.php?route=inv_items_sistema';
    if (productoId > 0) {
        destino += '&edit_id=' + encodeURIComponent(productoId);
    }
    window.location.href = destino;
}

function inicializarTablaCatalogo(body) {
    if (!window.jQuery || !$.fn.DataTable) return;
    var tabla = body.querySelector('table.catalogo-datatable');
    if (!tabla || $.fn.DataTable.isDataTable(tabla)) return;
    var nombreGrupo = body.getAttribute('data-grupo-nombre') || 'Catálogo de ítems';
    var nombreArchivo = ('catalogo_' + nombreGrupo).normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-zA-Z0-9_-]+/g, '_').toLowerCase();
    var botones = [];
    var formatoExcel = { body: function(data, row, column, node) {
        var texto = node ? node.textContent.trim() : String(data || '').replace(/<[^>]*>/g, '').trim();
        return column === 0 ? '\u200C' + texto : texto;
    } };
    if ($.fn.dataTable.Buttons) {
        botones = [
            { extend: 'excelHtml5', text: '<i class="fa-solid fa-file-excel"></i> Excel completo', title: nombreGrupo + ' - Completo', filename: nombreArchivo + '_completo', exportOptions: { columns: [0,1,2,3,4,5,6,7], format: formatoExcel } },
            { extend: 'excelHtml5', text: '<i class="fa-regular fa-file-excel"></i> Excel resumido', title: nombreGrupo + ' - Resumido', filename: nombreArchivo + '_resumido', exportOptions: { columns: [0,1,3,4,6,7], format: formatoExcel } },
            { extend: 'pdfHtml5', text: '<i class="fa-solid fa-file-pdf"></i> PDF', title: nombreGrupo, filename: nombreArchivo, orientation: 'landscape', pageSize: 'A4', exportOptions: { columns: [0,1,2,3,4,5,6,7] } }
        ];
    }
    $(tabla).DataTable({
        deferRender: true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, 'asc']],
        columnDefs: [{ targets: 8, orderable: false, searchable: false }],
        dom: '<"catalogo-dt-tools"Bf>rt<"bottom"lip><"clear">',
        buttons: botones,
        language: {
            search: 'Buscar en este grupo:',
            searchPlaceholder: 'Nombre, marca, unidad…',
            lengthMenu: 'Mostrar _MENU_',
            info: '_START_ a _END_ de _TOTAL_ ítems',
            infoEmpty: 'Sin ítems',
            zeroRecords: 'No se encontraron coincidencias',
            paginate: { previous: 'Anterior', next: 'Siguiente' }
        }
    });
}

/* ===== Modal Detalle (AJAX) ===== */
function verDetallesInventario(id) {
    var content = document.getElementById('inv-det-content');
    content.innerHTML = '<div style="text-align:center;padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:32px;color:var(--primary);"></i><p style="margin-top:12px;">Cargando información...</p></div>';
    document.getElementById('inv-modal-detalle').classList.add('active');

    fetch('index.php?route=inventario&action=verDetalle&id=' + id)
        .then(function(r){ return r.json(); })
        .then(function(item) {
            var colores = {
                'Maquinaria Pesada': ['#ef4444','fa-truck-monster'],
                'Contenedores':      ['#3b82f6','fa-box-open'],
                'Equipos de Muelle': ['#10b981','fa-life-ring'],
                'Vehículos':         ['#f59e0b','fa-truck-pickup'],
                'Herramientas':      ['#8b5cf6','fa-wrench']
            };
            var c = colores[item.categoria] || ['var(--primary)','fa-box'];
            var colorTema = c[0]; var icono = c[1];
            var bgTema = colorTema.startsWith('#') ? colorTema + '1a' : 'rgba(59,130,246,0.1)';
            var resp   = item.responsable || 'Sin Responsable';
            var obs    = item.observaciones || 'Sin observaciones.';
            var vBase  = parseFloat(item.valor);
            var ivaCal = parseFloat(item.iva_calculado);
            var vTotal = parseFloat(item.valor_total);

            content.innerHTML =
                '<div style="display:flex;align-items:center;gap:18px;margin-bottom:24px;border-bottom:1px solid var(--border-color);padding-bottom:20px;">' +
                    '<div style="font-size:32px;color:' + colorTema + ';background:' + bgTema + ';width:62px;height:62px;border-radius:16px;display:flex;align-items:center;justify-content:center;"><i class="fa-solid ' + icono + '"></i></div>' +
                    '<div style="flex:1;"><h2 style="margin:0 0 6px 0;font-size:20px;font-weight:700;">' + item.nombre + '</h2>' +
                    '<span class="status-badge ' + item.estadoClase + '">' + item.estado + '</span></div>' +
                '</div>' +
                '<div class="modal-detail-layout">' +
                    '<div><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">' +
                        mkD(colorTema,'fa-tags','Categoría',limpiarCodigoVisible(item.categoria)) +
                        mkD(colorTema,'fa-copyright','Marca',item.marca) +
                        mkD(colorTema,'fa-location-dot','Zona',item.zona) +
                        mkD(colorTema,'fa-user-tie','Responsable','<span style="color:var(--primary);font-weight:700;">' + resp + '</span>') +
                        mkD(colorTema,'fa-boxes-stacked','Existencia','<strong style="font-size:18px;">' + item.cantidad + '</strong> und.') +
                    '</div></div>' +
                    '<div>' +
                        '<div style="background:linear-gradient(135deg,rgba(59,130,246,0.04),rgba(59,130,246,0.08));padding:16px;border-radius:14px;margin-bottom:14px;border:1px solid rgba(59,130,246,0.12);">' +
                            '<h4 style="margin:0 0 12px 0;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;"><i class="fa-solid fa-calculator" style="color:var(--primary);margin-right:6px;"></i>Valores</h4>' +
                            '<div style="display:flex;justify-content:space-between;margin-bottom:7px;font-size:12.5px;color:var(--text-muted);"><span>Precio unitario:</span><strong>' + window.InvMoney.formatPrice(vBase) + '</strong></div>' +
                            '<div style="display:flex;justify-content:space-between;margin-bottom:7px;font-size:12.5px;color:var(--text-muted);"><span>IVA (' + item.tasa_iva + '%):</span><strong>' + window.InvMoney.formatAmount(ivaCal) + '</strong></div>' +
                            '<div style="display:flex;justify-content:space-between;font-size:14px;border-top:1px dashed var(--border-color);padding-top:10px;font-weight:700;"><span>Total:</span><strong style="font-size:16px;color:var(--primary);">' + window.InvMoney.formatAmount(vTotal) + '</strong></div>' +
                        '</div>' +
                        '<div style="background:var(--panel-bg);padding:12px;border-radius:12px;border:1px solid var(--border-color);">' +
                            '<label style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:5px;"><i class="fa-solid fa-message" style="color:' + colorTema + ';margin-right:5px;"></i>Observaciones</label>' +
                            '<span style="font-size:13px;line-height:1.5;display:block;font-style:italic;">' + obs + '</span>' +
                        '</div>' +
                    '</div>' +
                '</div>';
        });
}

function mkD(c,i,l,v){
    return '<div style="background:var(--panel-bg);padding:11px 14px;border-radius:10px;border:1px solid var(--border-color);display:flex;flex-direction:column;gap:3px;">' +
        '<label style="font-size:10.5px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;"><i class="fa-solid ' + i + '" style="margin-right:5px;color:' + c + ';"></i>' + l + '</label>' +
        '<span style="font-size:13.5px;font-weight:600;color:var(--text-color);">' + v + '</span>' +
    '</div>';
}

function limpiarCodigoVisible(nombre) {
    return String(nombre || '').replace(/\s*\([0-9.]+\)\s*$/, '').trim();
}

/* ===== Botón Volver Arriba ===== */
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
