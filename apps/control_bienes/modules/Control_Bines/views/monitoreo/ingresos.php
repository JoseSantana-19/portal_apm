<?php
/**
 * BODEGA_INGRESOS.PHP - Vista del Módulo de InvIngreso de Insumos a Bodega
 * Recreada desde cero para máxima compatibilidad y estabilidad total.
 * Compatible con PHP 7.4, 8.3 y 8.4
 */

// Calcular estadísticas rápidas de los ingresos
$totalIngresos = count($ingresos);
$totalItemsIn = 0;
$valorTotalIn = 0.0;
foreach ($ingresos as $ing) {
    $totalItemsIn += (int)$ing['total_items'];
    $valorTotalIn += (float)$ing['total_valor'];
}
?>

<!-- InvCabecera de Página -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1>Ingreso de Insumos a Bodega</h1>
        <p>Registro y control de ingresos de productos, insumos y bienes. Período Fiscal: <strong><?= htmlspecialchars(($periodoActivo ? $periodoActivo['nombre'] : 'Sin Período Activo')) ?></strong></p>
    </div>
    <div>
        <button class="btn-primary" onclick="abrirModalIngreso()"><i class="fa-solid fa-plus"></i> Registrar Ingreso a Bodega</button>
    </div>
</div>

<!-- Stats Cards en Tiempo Real -->
<div class="stats-row animate-fade-in">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-circle-arrow-down"></i></div>
        <div>
            <div class="stat-value"><?= $totalIngresos ?></div>
            <div class="stat-label">Ingresos Registrados</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-boxes-packing"></i></div>
        <div>
            <div class="stat-value"><?= $totalItemsIn ?></div>
            <div class="stat-label">Unidades Ingresadas</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div>
            <div class="stat-value">$<?= number_format($valorTotalIn, 2) ?></div>
            <div class="stat-label">Valor Total Ingresado</div>
        </div>
    </div>
</div>

<!-- Barra de Filtros y Búsqueda -->
<div class="filter-section animate-fade-in">
    <div class="filter-tabs">
        <span class="filter-tab active">Historial de Ingresos a Bodega</span>
    </div>
    
    <form action="index.php" method="GET" class="filter-controls">
        <input type="hidden" name="route" value="ingresos">
        
        <div class="filter-group" style="flex:2;">
            <label>Búsqueda General</label>
            <input type="text" name="termino" placeholder="Buscar por secuencial, proveedor..." value="<?= htmlspecialchars($filtros['termino']) ?>">
        </div>

        <div class="filter-group">
            <label>Proveedor</label>
            <input type="text" name="proveedor" placeholder="Nombre de proveedor" value="<?= htmlspecialchars($filtros['proveedor']) ?>">
        </div>
        
        <div class="filter-group">
            <label>Desde</label>
            <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($filtros['fecha_inicio']) ?>">
        </div>
        
        <div class="filter-group">
            <label>Hasta</label>
            <input type="date" name="fecha_fin" value="<?= htmlspecialchars($filtros['fecha_fin']) ?>">
        </div>

        <div class="filter-group">
            <label>Responsable</label>
            <select name="responsable_id">
                <option value="">Todos...</option>
                <?php foreach ($personal as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($filtros['responsable_id'] == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-actions">
            <a href="index.php?route=ingresos" class="btn-outline" style="height:40px;display:flex;align-items:center;justify-content:center;" title="Limpiar Filtros"><i class="fa-solid fa-eraser"></i></a>
            <button type="submit" class="btn-primary" style="height:40px;"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </div>
    </form>
</div>

<!-- Tabla Principal de Resultados -->
<div class="panel animate-fade-in">
    <div class="panel-header">
        <h3>Histórico de Ingresos Realizados (<?= count($ingresos) ?> registros)</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Código de Ingreso</th>
                    <th>Proveedor / Entidad</th>
                    <th>Fecha de Registro</th>
                    <th>Responsable</th>
                    <th>Cant. Ítems</th>
                    <th>Valoración Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ingresos)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:var(--text-muted);">
                            <i class="fa-solid fa-inbox" style="font-size:32px; display:block; margin-bottom:12px; opacity:0.4;"></i>
                            No se encontraron ingresos registrados
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ingresos as $ing): ?>
                        <tr>
                            <td class="secuencial-cell"><?= htmlspecialchars($ing['secuencial']) ?></td>
                            <td><strong><?= htmlspecialchars($ing['proveedor']) ?></strong></td>
                            <td><?= htmlspecialchars($ing['fecha']) ?></td>
                            <td><?= htmlspecialchars($ing['responsable']) ?></td>
                            <td style="text-align:center;"><span class="status-badge transit" style="background:#e0f2fe;color:#0369a1;"><?= $ing['total_items'] ?> unid.</span></td>
                            <td><strong>$<?= number_format((float)$ing['total_valor'], 2) ?></strong></td>
                            <td class="acciones-cell">
                                <button class="btn-accion btn-ver" onclick="verDetallesIngreso(<?= $ing['id'] ?>)" title="Ver Detalle de Ingreso"><i class="fa-solid fa-eye"></i></button>
                                <a href="index.php?route=ingresos&action=acta&id=<?= $ing['id'] ?>" target="_blank" class="btn-accion btn-editar" title="Ver Acta Oficial (Imprimir / PDF)" style="color:var(--primary-blue);"><i class="fa-solid fa-file-pdf"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Registro de InvIngreso a Bodega -->
<div class="modal-overlay" id="ing-modal">
    <div class="modal-content" style="max-width: 750px;">
        <div class="modal-header">
            <h2>Registrar Ingreso de Insumos</h2>
            <button class="modal-close" onclick="cerrarModalIngreso()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="index.php?route=ingresos&action=guardar" method="POST" onsubmit="return validarFormularioIngreso()">
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>Proveedor / Dependencia Correspondiente</label>
                        <select name="proveedor" id="ing-inp-proveedor" required style="width:100%;padding:10px;border:1px solid var(--border-color);border-radius:10px;background:var(--panel-bg);color:var(--text-color);">
                            <option value="">Seleccionar Proveedor...</option>
                            <?php foreach ($proveedores as $prov): ?>
                                <option value="<?= htmlspecialchars($prov['nombre']) ?>"><?= htmlspecialchars($prov['nombre']) ?> (RUC: <?= htmlspecialchars($prov['ruc']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fecha de Ingreso</label>
                        <input type="date" name="fecha" id="ing-inp-fecha" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Responsable de Bodega / Almacén</label>
                    <select name="responsable_id" id="ing-inp-responsable" required>
                        <option value="">Seleccionar...</option>
                        <?php foreach ($personal as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['area_actual']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Observaciones y Detalles Adicionales</label>
                    <textarea name="observaciones" id="ing-inp-obs" placeholder="Detalle de facturación, estado del transporte..."></textarea>
                </div>

                <!-- Sección dinámica InvCabecera-Detalle -->
                <div style="border-top:1px solid var(--border-color);padding-top:16px;margin-top:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                        <h4 style="font-size:14px;color:var(--text-main);font-weight:600;"><i class="fa-solid fa-list"></i> Detalle de Ítems e Insumos</h4>
                        <button type="button" class="btn-outline" style="padding:6px 12px;font-size:12px;" onclick="agregarFilaDetalleIngreso()"><i class="fa-solid fa-plus"></i> Agregar Fila</button>
                    </div>

                    <div style="max-height: 250px; overflow-y: auto;">
                        <table style="width:100%;font-size:13px;" id="tabla-detalles-ingreso">
                            <thead>
                                <tr style="background:var(--secondary-bg);">
                                    <th style="padding:8px 12px;font-size:11px;">Ítem / Producto de Inventario</th>
                                    <th style="padding:8px 12px;font-size:11px;width:100px;">Cantidad</th>
                                    <th style="padding:8px 12px;font-size:11px;width:120px;">V. Unitario ($)</th>
                                    <th style="padding:8px 12px;font-size:11px;width:50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbody-detalles-ingreso">
                                <!-- La primera fila se inyectará dinámicamente al abrir -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="cerrarModalIngreso()">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Registrar Ingreso</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Consulta Rápida Detalle de InvIngreso -->
<div class="modal-overlay" id="ing-modal-detalle">
    <div class="modal-content modal-content-wide">
        <div class="modal-header">
            <h2>Detalle de Ingreso a Bodega</h2>
            <button class="modal-close" onclick="cerrarDetallesIngreso()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="ing-det-content">
            <!-- Cargado dinámicamente -->
        </div>
    </div>
</div>

<script>
    console.log('[Telemetría Bodega] Iniciando carga de scripts en inv_bodega_ingresos.php');

    // Serializar catálogo de inventario para autocompletar
    const itemsInventarioData = <?= json_encode($itemsInventario) ?>;

    document.addEventListener('DOMContentLoaded', () => {
        const tbody = document.getElementById('tbody-detalles-ingreso');
        if (tbody) {
            tbody.addEventListener('change', function(e) {
                if (e.target.classList.contains('select-item')) {
                    const itemId = e.target.value;
                    const tr = e.target.closest('tr');
                    const valInput = tr.querySelector('.val-item');
                    if (itemId && valInput) {
                        const item = itemsInventarioData.find(i => i.id == itemId);
                        if (item) {
                            valInput.value = parseFloat(item.valor).toFixed(2);
                            console.log('[Telemetría Bodega] Autocompletado costo de producto ID ' + itemId + ': $' + item.valor);
                        }
                    }
                }
            });
        }
    });

    var filaIndexIngreso = 1;

    // Construcción ultra-compatible de plantilla HTML usando matriz de cadenas
    // previene cualquier inv_error de saltos de línea asíncronos o comillas en navegadores viejos.
    var filaIngresoTemplate = [
        '<tr>',
        '    <td style="padding:8px 12px;">',
        '        <select name="items[{INDEX}][item_id]" required class="select-item" style="width:100%;padding:6px;border:1px solid var(--border-color);border-radius:4px;background:var(--secondary-bg);">',
        '            <option value="">Seleccionar ítem...</option>',
        <?php foreach ($itemsInventario as $item): ?>
        '            <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['secuencial']) ?> - <?= htmlspecialchars(str_replace(array("'", "\"", "\r", "\n"), " ", $item['nombre']), ENT_QUOTES, 'UTF-8') ?> (Marca: <?= htmlspecialchars(str_replace(array("'", "\"", "\r", "\n"), " ", $item['marca']), ENT_QUOTES, 'UTF-8') ?>)</option>',
        <?php endforeach; ?>
        '        </select>',
        '    </td>',
        '    <td style="padding:8px 12px;">',
        '        <input type="number" name="items[{INDEX}][cantidad]" required min="1" class="cant-item" value="1" style="width:100%;padding:6px;border:1px solid var(--border-color);border-radius:4px;background:var(--secondary-bg);text-align:center;">',
        '    </td>',
        '    <td style="padding:8px 12px;">',
        '        <input type="number" step="0.01" name="items[{INDEX}][valor_unitario]" required min="0.01" class="val-item" placeholder="0.00" style="width:100%;padding:6px;border:1px solid var(--border-color);border-radius:4px;background:var(--secondary-bg);">',
        '    </td>',
        '    <td style="padding:8px 12px;text-align:center;">',
        '        <button type="button" class="btn-accion btn-eliminar" onclick="eliminarFilaDetalleIngreso(this)" style="width:24px;height:24px;border-radius:4px;"><i class="fa-solid fa-trash-can"></i></button>',
        '    </td>',
        '</tr>'
    ].join('\n');

    console.log('[Telemetría Bodega] Plantilla filaIngresoTemplate inicializada correctamente.');

    function abrirModalIngreso() {
        console.log('[Telemetría Bodega] abrirModalIngreso() ejecutado.');
        try {
            document.getElementById('ing-inp-proveedor').value = '';
            document.getElementById('ing-inp-obs').value = '';
            document.getElementById('ing-inp-responsable').value = '';
            
            var tbody = document.getElementById('tbody-detalles-ingreso');
            if (tbody) {
                tbody.innerHTML = filaIngresoTemplate.replace(/{INDEX}/g, '0');
                console.log('[Telemetría Bodega] Fila base (0) inyectada en tbody-detalles-ingreso.');
            } else {
                console.error('[Telemetría Bodega] tbody-detalles-ingreso no se encuentra en el DOM.');
            }
            
            filaIndexIngreso = 1;
            var modal = document.getElementById('ing-modal');
            if (modal) {
                modal.classList.add('active');
                console.log('[Telemetría Bodega] Modal overlays activo.');
            } else {
                console.error('[Telemetría Bodega] El overlay #ing-modal no se encuentra en el DOM.');
            }
        } catch (e) {
            console.error('[Telemetría Bodega] Falló abrirModalIngreso():', e);
            alert('Error al inicializar formulario: ' + e.message);
        }
    }

    function cerrarModalIngreso() {
        console.log('[Telemetría Bodega] cerrarModalIngreso() ejecutado.');
        var modal = document.getElementById('ing-modal');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    function agregarFilaDetalleIngreso() {
        console.log('[Telemetría Bodega] agregarFilaDetalleIngreso() llamado. Índice actual: ' + filaIndexIngreso);
        try {
            var tbody = document.getElementById('tbody-detalles-ingreso');
            if (tbody) {
                var tempDiv = document.createElement('tbody');
                tempDiv.innerHTML = filaIngresoTemplate.replace(/{INDEX}/g, filaIndexIngreso);
                var newRow = tempDiv.firstElementChild;
                tbody.appendChild(newRow);
                filaIndexIngreso++;
                console.log('[Telemetría Bodega] Fila agregada con éxito.');
            }
        } catch (e) {
            console.error('[Telemetría Bodega] Falló agregarFilaDetalleIngreso():', e);
        }
    }

    function eliminarFilaDetalleIngreso(btn) {
        console.log('[Telemetría Bodega] eliminarFilaDetalleIngreso() llamado.');
        try {
            var tr = btn.closest('tr');
            var tbody = document.getElementById('tbody-detalles-ingreso');
            if (tbody && tbody.rows.length <= 1) {
                alert('Debe tener al menos un ítem para registrar el ingreso a bodega.');
                return;
            }
            if (tr) {
                tr.parentNode.removeChild(tr);
                console.log('[Telemetría Bodega] Fila removida de forma segura.');
            }
        } catch (e) {
            console.error('[Telemetría Bodega] Falló eliminarFilaDetalleIngreso():', e);
        }
    }

    function validarFormularioIngreso() {
        console.log('[Telemetría Bodega] validarFormularioIngreso() llamado.');
        var selects = document.querySelectorAll('#tbody-detalles-ingreso select');
        var selectedIds = [];
        for (var i = 0; i < selects.length; i++) {
            var s = selects[i];
            if (s.value === '') {
                alert('Por favor complete todos los ítems seleccionados.');
                return false;
            }
            if (selectedIds.indexOf(s.value) !== -1) {
                alert('No se permiten ítems duplicados en el mismo ingreso a bodega. Modifique la cantidad en una sola fila.');
                return false;
            }
            selectedIds.push(s.value);
        }
        return true;
    }

    function cerrarDetallesIngreso() {
        console.log('[Telemetría Bodega] cerrarDetallesIngreso() llamado.');
        var modal = document.getElementById('ing-modal-detalle');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    function verDetallesIngreso(id) {
        console.log('[Telemetría Bodega] verDetallesIngreso() llamado para ID: ' + id);
        var content = document.getElementById('ing-det-content');
        var modal = document.getElementById('ing-modal-detalle');
        
        if (!content || !modal) {
            console.error('[Telemetría Bodega] Los contenedores del visor no existen.');
            return;
        }

        content.innerHTML = '<div style="text-align:center;padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:32px;color:var(--primary);"></i><p style="margin-top:12px;">Cargando detalles de bodega...</p></div>';
        modal.classList.add('active');

        fetch('index.php?route=ingresos&action=verDetalle&id=' + id)
            .then(function(res) {
                if (!res.ok) throw new Error('Error HTTP ' + res.status);
                return res.text();
            })
            .then(function(text) {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('[Telemetría Bodega] Respuesta no válida:', text);
                    throw new Error('La respuesta del servidor no tiene formato JSON válido.');
                }
            })
            .then(function(ing) {
                if (ing.inv_error) throw new Error(ing.inv_error);

                var totalCosto = 0;
                var tablaItems = '';
                ing.detalles.forEach(function(det) {
                    var subtotal = parseFloat(det.cantidad) * parseFloat(det.valor_unitario);
                    totalCosto += subtotal;
                    tablaItems += 
                        '<tr style="border-bottom: 1px solid var(--border-color);">' +
                            '<td style="padding: 8px 10px;font-family: monospace;font-weight: 700;color: var(--text-color);">' + det.item_secuencial + '</td>' +
                            '<td style="padding: 8px 10px;">' +
                                '<strong style="color:var(--text-color);">' + det.item_nombre + '</strong><br>' +
                                '<span style="font-size:11px;color:var(--text-muted);">' + det.item_categoria + ' | Marca: ' + det.item_marca + '</span>' +
                            '</td>' +
                            '<td style="padding: 8px 10px;text-align:center;">' +
                                '<span class="status-badge transit" style="background:rgba(3, 105, 161, 0.08);color:#0369a1;font-weight:700;padding:2px 8px;border-radius:6px;font-size:11px;">' + det.cantidad + ' u.</span>' +
                            '</td>' +
                            '<td style="padding: 8px 10px;text-align:right;color:var(--text-color);font-weight:600;">$' + parseFloat(det.valor_unitario).toFixed(2) + '</td>' +
                            '<td style="padding: 8px 10px;text-align:right;font-weight:700;color:var(--primary);">' +
                                '$' + subtotal.toLocaleString('es-EC', {minimumFractionDigits: 2, maximumFractionDigits: 2}) +
                            '</td>' +
                        '</tr>';
                });

                var obsText = ing.observaciones ? ing.observaciones : 'Sin observaciones adicionales registradas.';

                content.innerHTML = 
                    '<div class="detalle-header" style="display:flex;align-items:center;gap:18px;margin-bottom:24px;border-bottom:1px solid var(--border-color);padding-bottom:20px;">' +
                        '<div class="detalle-icono" style="font-size:36px;color:#0284c7;background:rgba(2,132,199,0.1);width:68px;height:68px;border-radius:18px;display:flex;align-items:center;justify-content:center;box-shadow: 0 4px 12px rgba(0,0,0,0.05);"><i class="fa-solid fa-circle-arrow-down"></i></div>' +
                        '<div style="flex:1;">' +
                            '<h2 style="margin:0 0 6px 0;font-size:22px;font-weight:700;color:var(--text-color);letter-spacing:-0.5px;">Ingreso: ' + ing.secuencial + '</h2>' +
                            '<span style="font-size:13px;color:var(--text-muted);font-weight:500;"><i class="fa-solid fa-calendar-day" style="margin-right:6px;"></i>Fecha de ingreso: <strong>' + ing.fecha + '</strong></span>' +
                        '</div>' +
                    '</div>' +

                    '<div class="modal-detail-layout">' +
                        '<div>' +
                            '<div class="detalle-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">' +
                                '<div class="detalle-campo" style="background:var(--panel-bg);padding:12px 16px;border-radius:12px;border:1px solid var(--border-color);display:flex;flex-direction:column;gap:4px;">' +
                                    '<label style="display:block;font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;"><i class="fa-solid fa-truck" style="margin-right:6px;color:#0284c7;"></i>Proveedor / Origen</label>' +
                                    '<span style="font-size:14px;font-weight:700;color:var(--text-color);">' + ing.proveedor + '</span>' +
                                '</div>' +
                                '<div class="detalle-campo" style="background:var(--panel-bg);padding:12px 16px;border-radius:12px;border:1px solid var(--border-color);display:flex;flex-direction:column;gap:4px;">' +
                                    '<label style="display:block;font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;"><i class="fa-solid fa-user-tie" style="margin-right:6px;color:#0284c7;"></i>Responsable de Bodega</label>' +
                                    '<span style="font-size:14px;font-weight:700;color:var(--text-color);">' + ing.responsable + '</span>' +
                                '</div>' +
                            '</div>' +
                            
                            '<div class="detalle-campo" style="margin-bottom:16px;">' +
                                '<label style="display:flex;align-items:center;gap:8px;font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;"><i class="fa-solid fa-message" style="color:#0284c7;"></i>Observaciones Adicionales</label>' +
                                '<span style="font-size:13px;line-height:1.5;display:block;background:var(--panel-bg);padding:12px 14px;border-radius:12px;border:1px solid var(--border-color);color:var(--text-color);font-style:italic;max-height: 110px; overflow-y: auto;">' + obsText + '</span>' +
                            '</div>' +
                        '</div>' +
                        
                        '<div>' +
                            '<h4 style="font-size:12px;margin:0 0 10px 0;font-weight:700;color:var(--text-color);text-transform:uppercase;letter-spacing:0.5px;display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-boxes-stacked" style="color:#0284c7;"></i> Insumos Detallados</h4>' +
                            '<div style="border:1px solid var(--border-color);border-radius:12px;overflow:hidden;margin-bottom:16px;background:var(--panel-bg);max-height: 180px; overflow-y: auto;">' +
                                '<table style="width:100%;font-size:12px;border-collapse:collapse;">' +
                                    '<thead>' +
                                        '<tr style="background:rgba(0,0,0,0.02);border-bottom:1px solid var(--border-color);">' +
                                            '<th style="padding:8px;text-align:left;font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Código</th>' +
                                            '<th style="padding:8px;text-align:left;font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Ítem</th>' +
                                            '<th style="padding:8px;text-align:center;font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;width:60px;">Cant.</th>' +
                                            '<th style="padding:8px;text-align:right;font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;width:70px;">Unit.</th>' +
                                            '<th style="padding:8px;text-align:right;font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;width:80px;">Total</th>' +
                                        '</tr>' +
                                    '</thead>' +
                                    '<tbody>' +
                                        tablaItems +
                                    '</tbody>' +
                                '</table>' +
                            '</div>' +

                            '<div style="background:linear-gradient(135deg, rgba(16,185,129,0.04) 0%, rgba(16,185,129,0.08) 100%);padding:12px 16px;border-radius:12px;border:1px solid rgba(16,185,129,0.12);display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">' +
                                '<span style="font-size:13px;font-weight:700;color:var(--text-color);"><i class="fa-solid fa-file-invoice-dollar" style="color:#10b981;margin-right:8px;"></i>Total:</span>' +
                                '<strong style="font-size:16px;color:#10b981;letter-spacing:-0.3px;">$' + totalCosto.toLocaleString('es-EC', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +

                    '<div style="display:flex;justify-content:flex-end;margin-top:16px;gap:12px;border-top:1px solid var(--border-color);padding-top:16px;">' +
                        '<a href="index.php?route=ingresos&action=acta&id=' + ing.id + '" target="_blank" class="btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;background:#0284c7;border-color:#0284c7;"><i class="fa-solid fa-file-pdf"></i> Imprimir Acta</a>' +
                    '</div>';
                console.log('[Telemetría Bodega] Detalles renderizados en content.');
            })
            .catch(function(err) {
                console.error('[Telemetría Bodega] Falló verDetallesIngreso():', err);
                content.innerHTML = 
                    '<div style="text-align:center;padding:40px;color:var(--danger);">' +
                        '<i class="fa-solid fa-triangle-exclamation" style="font-size:32px;margin-bottom:12px;"></i>' +
                        '<p style="font-weight:600;">Error al cargar detalles</p>' +
                        '<p style="font-size:12px;color:var(--text-muted);margin-top:4px;">' + err.message + '</p>' +
                    '</div>';
            });
    }

    console.log('[Telemetría Bodega] Todos los scripts de inv_bodega_ingresos.php cargados exitosamente.');
</script>
