<?php
/**
 * INV_ITEMS.PHP - Catálogo de Ítems agrupado por Grupo
 * Vista de lista colapsable optimizada: carga ítems de forma diferida (Lazy Load AJAX) dentro de los acordeones.
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
?>
<style>
/* ---- Estilos específicos del Catálogo de Ítems (Lista de Acordeones) ---- */
.grupo-tabla-section {
    margin-bottom: 20px;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid var(--border-color);
    background: var(--panel-bg);
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: box-shadow 0.2s;
}
.grupo-tabla-section:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.grupo-tabla-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 18px 24px;
    cursor: pointer;
    user-select: none;
    transition: background 0.2s;
}
.grupo-tabla-header:hover { filter: brightness(0.96); }
.grupo-icono-box {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.grupo-info { flex: 1; }
.grupo-info h2 {
    margin: 0 0 2px 0;
    font-size: 15px;
    font-weight: 700;
    color: var(--text-color);
    letter-spacing: -0.2px;
}
.grupo-info p { margin: 0; font-size: 12px; color: var(--text-muted); }
.grupo-toggle-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 16px;
    border-radius: 8px;
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s;
    background: rgba(255,255,255,0.15);
    color: white;
    backdrop-filter: blur(4px);
}
.grupo-tabla-body { display: none; }
.grupo-tabla-body.abierto { display: block; }
.grupo-tabla-body table { margin: 0; }
.grupo-tabla-body th, .grupo-tabla-body td {
    padding: 10px 14px;
    font-size: 13px;
}
.grupo-tabla-body thead tr {
    background: var(--sidebar-bg, rgba(248,250,252,0.9));
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
</style>

<!-- InvCabecera de Página -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1>Catálogo de Ítems</h1>
        <p>Vista de inventario organizada por grupo contable. Haz clic en un grupo para ver sus productos registrados en tiempo real.</p>
    </div>
</div>

<!-- Barra de Búsqueda -->
<div class="filter-section animate-fade-in" style="margin-bottom:20px;">
    <form action="index.php" method="GET" class="filter-controls">
        <input type="hidden" name="route" value="items">
        <div class="filter-group" style="flex:2;">
            <label>Buscar ítem</label>
            <input type="text" name="termino" id="txt-termino"
                   placeholder="Nombre, código, marca... (Búsqueda insensible a acentos y mayúsculas)"
                   value="<?= htmlspecialchars($filtros['termino'] ?? '') ?>">
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
<div class="animate-fade-in" id="catalogo-grupos">
<?php if (empty($resumenCategorias)): ?>
    <div class="panel" style="text-align:center;padding:60px;color:var(--text-muted);">
        <i class="fa-solid fa-folder-open" style="font-size:48px;display:block;margin-bottom:16px;opacity:0.3;"></i>
        <h3>No se encontraron ítems</h3>
        <p>Ajusta los términos de búsqueda o registra un nuevo equipo en Inventario General.</p>
    </div>
<?php else:
    // Paleta de grupos
    $paleta = [
        'Maquinaria Pesada' => ['color' => '#ef4444', 'bg' => 'linear-gradient(135deg,#ef4444,#dc2626)', 'icono' => 'fa-truck-monster'],
        'Contenedores'      => ['color' => '#3b82f6', 'bg' => 'linear-gradient(135deg,#3b82f6,#2563eb)', 'icono' => 'fa-box-open'],
        'Equipos de Muelle' => ['color' => '#10b981', 'bg' => 'linear-gradient(135deg,#10b981,#059669)', 'icono' => 'fa-life-ring'],
        'Vehículos'         => ['color' => '#f59e0b', 'bg' => 'linear-gradient(135deg,#f59e0b,#d97706)', 'icono' => 'fa-truck-pickup'],
        'Herramientas'      => ['color' => '#8b5cf6', 'bg' => 'linear-gradient(135deg,#8b5cf6,#7c3aed)', 'icono' => 'fa-wrench'],
    ];
    $def = ['color' => '#64748b', 'bg' => 'linear-gradient(135deg,#64748b,#475569)', 'icono' => 'fa-box'];

    $grupoIdx = 0;
    foreach ($resumenCategorias as $catResumen):
        $grupoId     = (int)$catResumen['categoria_id'];
        $grupoNombre = $catResumen['categoria_nombre'];
        $p           = isset($paleta[$grupoNombre]) ? $paleta[$grupoNombre] : $def;
        $color       = $p['color'];
        $bg          = $p['bg'];
        $icono       = $p['icono'];
        
        $total       = (int)$catResumen['total_items'];
        // Ocultar categorías vacías bajo filtros activos
        if ($total <= 0) continue;

        $totalQty    = number_format((float)$catResumen['total_qty'], 0);
        $valorTotal  = number_format((float)$catResumen['total_value'], 2);
        
        $bodyId      = 'grp-body-' . $grupoIdx;
        $grupoIdx++;
?>
    <div class="grupo-tabla-section animate-fade-in">
        <!-- InvCabecera del Grupo -->
        <div class="grupo-tabla-header" onclick="toggleGrupoTabla(<?= $grupoId ?>, '<?= $bodyId ?>', this)"
             style="background:<?= $bg ?>;">
            <div class="grupo-icono-box" style="background:rgba(255,255,255,0.2);">
                <i class="fa-solid <?= $icono ?>" style="color:white;"></i>
            </div>
            <div class="grupo-info">
                <h2 style="color:white;"><?= htmlspecialchars($grupoNombre) ?></h2>
                <p style="color:rgba(255,255,255,0.75);">
                    <?= $total ?> ítem<?= $total !== 1 ? 's' : '' ?> &nbsp;·&nbsp;
                    Cantidad total: <strong style="color:white;"><?= $totalQty ?></strong>
                    &nbsp;·&nbsp; Valor total: <strong style="color:white;">$<?= $valorTotal ?></strong>
                </p>
            </div>
            <div class="grupo-toggle-badge" id="badge-<?= $bodyId ?>">
                <i class="fa-solid fa-eye" id="badge-icon-<?= $bodyId ?>"></i>
                <span id="badge-lbl-<?= $bodyId ?>">Ver ítems</span>
            </div>
        </div>

        <!-- Tabla de Ítems del Grupo (colapsable y cargada por AJAX) -->
        <div class="grupo-tabla-body" id="<?= $bodyId ?>" data-cargado="false">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width:110px;">Código</th>
                            <th>Descripción / Nombre</th>
                            <th>Marca</th>
                            <th style="width:120px;">Unidad</th>
                            <th style="width:100px;text-align:center;">Existencia</th>
                            <th style="width:110px;text-align:right;">Precio Base</th>
                            <th style="width:120px;text-align:right;">Total</th>
                            <th style="width:90px;text-align:center;">Estado</th>
                            <th style="width:80px;text-align:center;">Acciones</th>
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
                        <input type="number" step="0.01" name="valor" id="inv-inp-valor" required placeholder="Ej: 8500.00">
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

                html += '<tr>' +
                    '<td><code style="font-family:monospace;font-weight:700;font-size:12px;background:var(--border-color);padding:2px 8px;border-radius:5px;">' + (item.secuencial || '') + '</code></td>' +
                    '<td><strong style="display:block;color:var(--text-color);font-size:13.5px;">' + (item.nombre || '') + '</strong></td>' +
                    '<td style="font-size:13px;color:var(--text-muted);">' + (item.marca || '') + '</td>' +
                    '<td style="font-size:13px;color:var(--text-muted);font-weight:600;">' + unidadStr + '</td>' +
                    '<td style="text-align:center;">' +
                        '<span class="qty-badge ' + qtyClass + '">' +
                            '<i class="fa-solid ' + qtyIcon + '" style="font-size:11px;"></i> ' + cant +
                        '</span>' +
                    '</td>' +
                    '<td style="font-size:13px;font-weight:600;text-align:right;color:var(--text-color);">$' + parseFloat(item.valor).toFixed(2) + '</td>' +
                    '<td style="text-align:right;"><strong style="font-size:13px;color:var(--primary);">' +
                        '$' + totalVal.toLocaleString('es-EC', {minimumFractionDigits:2, maximumFractionDigits:2}) +
                        '</strong><span style="display:block;font-size:10.5px;color:var(--text-muted);">×' + cant + ' und.</span>' +
                    '</td>' +
                    '<td style="text-align:center;"><span class="status-badge ' + (item.estadoClase || 'inactive') + '" style="font-size:11px;padding:3px 8px;">' +
                        (item.estado || 'Desconocido') + '</span></td>' +
                    '<td class="acciones-cell" style="text-align:center;">' +
                        '<button class="btn-accion btn-ver" onclick="verDetallesInventario(' + item.id + ')" title="Ver Detalle"><i class="fa-solid fa-eye"></i></button>' +
                        '<button class="btn-accion btn-editar" onclick="editarRegistroInventario(' + itemJson + ')" title="Editar"><i class="fa-solid fa-pen"></i></button>' +
                    '</td>' +
                '</tr>';
            });

            tbody.innerHTML = html;
        })
        .catch(function(e) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:48px;color:#ef4444;"><i class="fa-solid fa-triangle-exclamation" style="font-size:32px;display:block;margin-bottom:12px;"></i>Error al consultar los ítems. Intente de nuevo.</td></tr>';
        });
}

/* ===== Modal Editar ===== */
function editarRegistroInventario(item) {
    document.getElementById('inv-modal-title').textContent = 'Editar Registro';
    document.getElementById('inv-inp-id').value       = item.id;
    document.getElementById('inv-inp-nombre').value   = item.nombre;
    document.getElementById('inv-inp-marca').value    = item.marca;
    document.getElementById('inv-inp-categoria').value = item.categoria_id;
    document.getElementById('inv-inp-zona').value     = item.zona_id;
    document.getElementById('inv-inp-estado').value   = item.estado_id;
    document.getElementById('inv-inp-responsable').value = item.responsable_id || '';
    document.getElementById('inv-inp-valor').value    = item.valor;
    document.getElementById('inv-inp-obs').value      = item.observaciones || '';
    document.getElementById('inv-modal').classList.add('active');
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
                        mkD(colorTema,'fa-list-ol','Secuencial','<code style="font-family:monospace;font-weight:700;">' + item.secuencial + '</code>') +
                        mkD(colorTema,'fa-tags','Categoría',item.categoria) +
                        mkD(colorTema,'fa-copyright','Marca',item.marca) +
                        mkD(colorTema,'fa-location-dot','Zona',item.zona) +
                        mkD(colorTema,'fa-user-tie','Responsable','<span style="color:var(--primary);font-weight:700;">' + resp + '</span>') +
                        mkD(colorTema,'fa-boxes-stacked','Existencia','<strong style="font-size:18px;">' + item.cantidad + '</strong> und.') +
                    '</div></div>' +
                    '<div>' +
                        '<div style="background:linear-gradient(135deg,rgba(59,130,246,0.04),rgba(59,130,246,0.08));padding:16px;border-radius:14px;margin-bottom:14px;border:1px solid rgba(59,130,246,0.12);">' +
                            '<h4 style="margin:0 0 12px 0;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;"><i class="fa-solid fa-calculator" style="color:var(--primary);margin-right:6px;"></i>Valores</h4>' +
                            '<div style="display:flex;justify-content:space-between;margin-bottom:7px;font-size:12.5px;color:var(--text-muted);"><span>Precio unitario:</span><strong>$' + vBase.toLocaleString('es-EC',{minimumFractionDigits:2}) + '</strong></div>' +
                            '<div style="display:flex;justify-content:space-between;margin-bottom:7px;font-size:12.5px;color:var(--text-muted);"><span>IVA (' + item.tasa_iva + '%):</span><strong>$' + ivaCal.toLocaleString('es-EC',{minimumFractionDigits:2}) + '</strong></div>' +
                            '<div style="display:flex;justify-content:space-between;font-size:14px;border-top:1px dashed var(--border-color);padding-top:10px;font-weight:700;"><span>Total:</span><strong style="font-size:16px;color:var(--primary);">$' + vTotal.toLocaleString('es-EC',{minimumFractionDigits:2}) + '</strong></div>' +
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
