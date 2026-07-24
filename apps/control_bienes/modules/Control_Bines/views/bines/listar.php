<?php
/**
 * INV_INVENTARIO.PHP - Vista de Dashboard Principal de InvInventario
 * Lazy-load: las filas de la tabla se cargan únicamente al presionar "Mostrar datos"
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
?>
<style>
th.sortable:hover {
    background: rgba(37, 99, 235, 0.05);
    color: var(--primary) !important;
}
th.sortable i {
    transition: all 0.2s ease;
}
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
<div class="stats-row animate-fade-in">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div>
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Bienes</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
        <div>
            <div class="stat-value"><?= isset($stats['porEstado']['Operativo']) ? $stats['porEstado']['Operativo'] : 0 ?></div>
            <div class="stat-label">Operativos</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-wrench"></i></div>
        <div>
            <div class="stat-value"><?= isset($stats['porEstado']['En Mantenimiento']) ? $stats['porEstado']['En Mantenimiento'] : 0 ?></div>
            <div class="stat-label">Mantenimiento</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa-solid fa-truck-fast"></i></div>
        <div>
            <div class="stat-value"><?= isset($stats['porEstado']['En Tránsito']) ? $stats['porEstado']['En Tránsito'] : 0 ?></div>
            <div class="stat-label">En Tránsito</div>
        </div>
    </div>
</div>

<!-- Barra de Filtros y Búsqueda -->
<div class="filter-section animate-fade-in">
    <div class="filter-tabs">
        <a href="index.php?route=inventario" class="filter-tab <?= empty($filtros['categoria']) ? 'active' : '' ?>">Todos los Bienes</a>
        <?php foreach ($categorias as $cat):
            $isActive = ($filtros['categoria'] === $cat['nombre'] || $filtros['categoria'] === $cat['id']) ? 'active' : '';
        ?>
            <a href="index.php?route=inventario&categoria=<?= urlencode($cat['id']) ?>" class="filter-tab <?= $isActive ?>"><?= htmlspecialchars($cat['nombre']) ?></a>
        <?php endforeach; ?>
    </div>

    <form action="index.php" method="GET" class="filter-controls" id="filtros-form">
        <input type="hidden" name="route" value="inventario">
        <input type="hidden" name="categoria" id="filtro-categoria" value="<?= htmlspecialchars($filtros['categoria'] ?? '') ?>">

        <div class="filter-group" style="flex:2;">
            <label>Término de Búsqueda</label>
            <input type="text" name="termino" placeholder="Buscar por código, nombre, marca..." value="<?= htmlspecialchars($filtros['termino']) ?>">
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
            <button type="submit" class="btn-primary" style="height:40px;"><i class="fa-solid fa-filter"></i> Buscar</button>
        </div>
    </form>
</div>

<!-- Panel de Tabla con Lazy Load -->
<div class="panel animate-fade-in">
    <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <h3 id="tbl-titulo" style="margin:0;">Registros del Inventario — <span style="color:var(--text-muted);font-weight:500;font-size:14px;">Presione "Mostrar datos" para cargar</span></h3>
        <button id="btn-mostrar-datos" class="btn-primary" onclick="cargarDatosInventario()" style="display:flex;align-items:center;gap:8px;padding:10px 22px;border-radius:10px;">
            <i class="fa-solid fa-table-list" id="icon-mostrar"></i>
            <span id="lbl-mostrar">Mostrar datos</span>
        </button>
    </div>

    <div class="table-responsive">
        <table id="tabla-inventario">
            <thead>
                <tr>
                    <th class="sortable" onclick="ordenarPor('secuencial')" style="cursor:pointer; user-select:none;">Secuencial <i class="fa-solid fa-sort" id="sort-secuencial" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th class="sortable" onclick="ordenarPor('nombre')" style="cursor:pointer; user-select:none;">Equipo / Contenedor <i class="fa-solid fa-sort" id="sort-nombre" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th class="sortable" onclick="ordenarPor('categoria')" style="cursor:pointer; user-select:none;">Categoría <i class="fa-solid fa-sort" id="sort-categoria" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th class="sortable" onclick="ordenarPor('unidad')" style="cursor:pointer; user-select:none;">Unidad <i class="fa-solid fa-sort" id="sort-unidad" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th class="sortable" onclick="ordenarPor('valor')" style="cursor:pointer; user-select:none;">Base ($) <i class="fa-solid fa-sort" id="sort-valor" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th class="sortable" onclick="ordenarPor('iva')" style="cursor:pointer; user-select:none; width:80px; text-align:center;">IVA <i class="fa-solid fa-sort" id="sort-iva" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th class="sortable" onclick="ordenarPor('total')" style="cursor:pointer; user-select:none;">Total ($) <i class="fa-solid fa-sort" id="sort-total" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th class="sortable" onclick="ordenarPor('estado')" style="cursor:pointer; user-select:none;">Estado <i class="fa-solid fa-sort" id="sort-estado" style="margin-left:5px; font-size:11px; opacity:0.6;"></i></th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbody-inventario">
                <!-- Los datos se inyectan aquí vía AJAX al pulsar "Mostrar datos" -->
                <tr id="tr-placeholder">
                    <td colspan="9" style="text-align:center;padding:48px 20px;color:var(--text-muted);">
                        <i class="fa-solid fa-table-list" style="font-size:40px;display:block;margin-bottom:14px;opacity:0.25;"></i>
                        <strong style="font-size:15px;display:block;margin-bottom:6px;">Datos no cargados</strong>
                        <span style="font-size:13px;">Presione el botón <strong>"Mostrar datos"</strong> para consultar los registros.</span>
                    </td>
                </tr>
            </tbody>
        </table>
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
    $(document).ready(function() {
        // Ocultar la paginación manual y botón mostrar datos heredado
        $('#paginacion-container').hide();
        $('#btn-mostrar-datos').hide();
        
        // Inicializar DataTables
        table = $('#tabla-inventario').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'index.php?route=inventario&action=listarAjax',
                type: 'GET',
                data: function(d) {
                    d.categoria = $('#filtro-categoria').val() || '';
                    d.unidad_id = $('select[name="unidad_id"]').val() || '';
                    d.estado = $('select[name="estado"]').val() || '';
                    d.termino = $('input[name="termino"]').val() || '';
                }
            },
            columns: [
                { data: 'secuencial' },
                { data: 'nombre' },
                { data: 'categoria' },
                { data: 'unidad' },
                { data: 'valor' },
                { data: 'iva', className: 'text-center' },
                { data: 'total' },
                { data: 'estado' },
                { data: 'acciones', orderable: false }
            ],
            pageLength: 50,
            lengthMenu: [10, 25, 50, 100],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            dom: '<"top"i>rt<"bottom"flp><"clear">',
            ordering: true,
            order: [[0, 'desc']]
        });

        // Interceptar el envío del formulario de filtros
        $('#filtros-form').on('submit', function(e) {
            e.preventDefault();
            table.ajax.reload();
        });

        // Interceptar el cambio en filtros select
        $('select[name="unidad_id"], select[name="estado"]').on('change', function() {
            table.ajax.reload();
        });

        // Interceptar clic en pestañas de categoría
        $('.filter-tab').on('click', function(e) {
            e.preventDefault();
            $('.filter-tab').removeClass('active');
            $(this).addClass('active');
            
            var href = $(this).attr('href');
            var catVal = '';
            if (href && href.indexOf('categoria=') !== -1) {
                catVal = decodeURIComponent(href.split('categoria=')[1]);
            }
            $('#filtro-categoria').val(catVal);
            table.ajax.reload();
        });
    });

    /* ========== Modal InvInventario ========== */
    function abrirModalInventario() {
        document.getElementById('inv-modal-title').textContent = 'Registrar Equipo';
        document.getElementById('inv-inp-id').value = '0';
        document.getElementById('inv-inp-producto-id').value = '';
        document.getElementById('inv-inp-nombre').value = '';
        document.getElementById('inv-inp-marca').value = '';
        document.getElementById('inv-inp-categoria').value = '';
        document.getElementById('inv-inp-zona').value = '';
        document.getElementById('inv-inp-estado').value = '';
        document.getElementById('inv-inp-responsable').value = '';
        document.getElementById('inv-inp-valor').value = '';
        document.getElementById('inv-inp-obs').value = '';
        var selProd = document.getElementById('inv-sel-producto');
        if (selProd) selProd.value = '';
        document.getElementById('inv-modal').classList.add('active');
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
        // Auto-completar nombre si está vacío
        if (!document.getElementById('inv-inp-nombre').value) {
            document.getElementById('inv-inp-nombre').value = opt.getAttribute('data-nombre') || '';
        }
        // Auto-completar categoría
        var catId = opt.getAttribute('data-categoria');
        if (catId) {
            document.getElementById('inv-inp-categoria').value = catId;
        }
        // Auto-completar precio si está vacío
        var precio = parseFloat(opt.getAttribute('data-precio') || '0');
        if (precio > 0 && !document.getElementById('inv-inp-valor').value) {
            document.getElementById('inv-inp-valor').value = precio.toFixed(2);
        }
    }

    function cerrarModalInventario() {
        document.getElementById('inv-modal').classList.remove('active');
    }

    function editarRegistroInventario(item) {
        document.getElementById('inv-modal-title').textContent = 'Editar Registro';
        document.getElementById('inv-inp-id').value = item.id;
        document.getElementById('inv-inp-nombre').value = item.nombre;
        document.getElementById('inv-inp-marca').value = item.marca;
        document.getElementById('inv-inp-categoria').value = item.categoria_id;
        document.getElementById('inv-inp-zona').value = item.zona_id;
        document.getElementById('inv-inp-estado').value = item.estado_id;
        document.getElementById('inv-inp-responsable').value = item.responsable_id ? item.responsable_id : '';
        document.getElementById('inv-inp-valor').value = item.valor;
        document.getElementById('inv-inp-obs').value = item.observaciones;
        document.getElementById('inv-modal').classList.add('active');
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
                            mkCampo(colorTema,'fa-list-ol','Secuencial','<code style="font-family:monospace;font-weight:700;">' + item.secuencial + '</code>') +
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
                                (function() {
                                    var html = '';
                                    var aplicaIvaId = parseInt(item.producto_aplica_iva || 1);
                                    (item.tipos_iva || []).forEach(function(tipo) {
                                        var isApplied = (parseInt(tipo.id) === aplicaIvaId);
                                        var rate = parseFloat(tipo.tasa_iva);
                                        var calc = isApplied ? (vBase * (rate / 100)) : 0;
                                        
                                        var style = isApplied ? 'font-weight:700;color:var(--primary); font-size:13px;' : 'color:var(--text-muted);opacity:0.5;';
                                        var label = isApplied ? '⚡ ' + tipo.nombre : tipo.nombre;
                                        
                                        html += '<div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:12.5px;' + style + '">' +
                                            '<span>' + label + ':</span>' +
                                            '<strong>$' + calc.toLocaleString('es-EC',{minimumFractionDigits:2}) + '</strong>' +
                                        '</div>';
                                    });
                                    return html;
                                })() +
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
