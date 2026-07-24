<?php
/**
 * BODEGA_EGRESOS.PHP - Vista del Módulo de InvEgreso de Insumos de Bodega
 * Recreada desde cero para máxima compatibilidad y estabilidad total.
 * Compatible con PHP 7.4, 8.3 y 8.4
 */

// Calcular estadísticas rápidas de los egresos
$totalEgresos = count($egresos);
$totalItemsOut = 0;
foreach ($egresos as $egr) {
    $totalItemsOut += (int)$egr['total_items'];
}

// Crear un mapeo JSON de stock por item para validación reactiva en el cliente
$stockMap = [];
foreach ($itemsInventario as $item) {
    $stockMap[$item['id']] = [
        'secuencial' => $item['secuencial'],
        'nombre' => $item['nombre'],
        'stock' => (int)$item['cantidad']
    ];
}
$stockMapJson = json_encode($stockMap);
?>

<!-- InvCabecera de Página -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1>Egreso de Insumos de Bodega</h1>
        <p>Despacho y entrega de bienes e insumos a departamentos y personal. Período Fiscal: <strong><?= htmlspecialchars(($periodoActivo ? $periodoActivo['nombre'] : 'Sin Período Activo')) ?></strong></p>
    </div>
    <div>
        <button class="btn-primary" onclick="abrirModalEgreso()"><i class="fa-solid fa-plus"></i> Registrar Egreso / Despacho</button>
    </div>
</div>

<!-- Stats Cards en Tiempo Real -->
<div class="stats-row animate-fade-in">
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-circle-arrow-up"></i></div>
        <div>
            <div class="stat-value"><?= $totalEgresos ?></div>
            <div class="stat-label">Despachos Registrados</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-boxes-packing"></i></div>
        <div>
            <div class="stat-value"><?= $totalItemsOut ?></div>
            <div class="stat-label">Unidades Entregadas</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-hotel"></i></div>
        <div>
            <div class="stat-value"><?= count($areas) ?></div>
            <div class="stat-label">Áreas Destinatarias</div>
        </div>
    </div>
</div>

<!-- Barra de Filtros y Búsqueda -->
<div class="filter-section animate-fade-in">
    <div class="filter-tabs">
        <span class="filter-tab active">Historial de Salidas / Egresos Realizados</span>
    </div>
    
    <form action="index.php" method="GET" class="filter-controls">
        <input type="hidden" name="route" value="egresos">
        
        <div class="filter-group" style="flex:2;">
            <label>Búsqueda General</label>
            <input type="text" name="termino" placeholder="Buscar por secuencial, motivo, observaciones..." value="<?= htmlspecialchars($filtros['termino']) ?>">
        </div>

        <div class="filter-group">
            <label>Área Destino</label>
            <select name="area_id">
                <option value="">Todas...</option>
                <?php foreach ($areas as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= ($filtros['area_id'] == $a['id']) ? 'selected' : '' ?>><?= htmlspecialchars($a['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
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
            <a href="index.php?route=egresos" class="btn-outline" style="height:40px;display:flex;align-items:center;justify-content:center;" title="Limpiar Filtros"><i class="fa-solid fa-eraser"></i></a>
            <button type="submit" class="btn-primary" style="height:40px;"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </div>
    </form>
</div>

<!-- Tabla Principal de Resultados -->
<div class="panel animate-fade-in">
    <div class="panel-header">
        <h3>Histórico de Salidas Despachadas (<?= count($egresos) ?> registros)</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Código de Egreso</th>
                    <th>Área Solicitante / Destino</th>
                    <th>Fecha de Despacho</th>
                    <th>Responsable de Entrega</th>
                    <th>Cant. Ítems</th>
                    <th>Motivo de Egreso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($egresos)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:var(--text-muted);">
                            <i class="fa-solid fa-inbox" style="font-size:32px; display:block; margin-bottom:12px; opacity:0.4;"></i>
                            No se encontraron egresos registrados
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($egresos as $egr): ?>
                        <tr>
                            <td class="secuencial-cell"><?= htmlspecialchars($egr['secuencial']) ?></td>
                            <td><strong><?= htmlspecialchars($egr['area_destino']) ?></strong></td>
                            <td><?= htmlspecialchars($egr['fecha']) ?></td>
                            <td><?= htmlspecialchars($egr['responsable']) ?></td>
                            <td style="text-align:center;"><span class="status-badge dispatched" style="background:#e0e7ff;color:#4338ca;"><?= $egr['total_items'] ?> unid.</span></td>
                            <td><span style="font-size:13px;color:var(--text-muted);display:block;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($egr['motivo']) ?></span></td>
                            <td class="acciones-cell">
                                <button class="btn-accion btn-ver" onclick="verDetallesEgreso(<?= $egr['id'] ?>)" title="Ver Detalle de Salida"><i class="fa-solid fa-eye"></i></button>
                                <a href="index.php?route=egresos&action=acta&id=<?= $egr['id'] ?>" target="_blank" class="btn-accion btn-editar" title="Ver Acta Oficial (Imprimir / PDF)" style="color:var(--primary-blue);"><i class="fa-solid fa-file-pdf"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Registro de InvEgreso / Despacho -->
<div class="modal-overlay" id="egr-modal">
    <div class="modal-content" style="max-width: 750px;">
        <div class="modal-header">
            <h2>Registrar Egreso / Despacho</h2>
            <button class="modal-close" onclick="cerrarModalEgreso()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="index.php?route=egresos&action=guardar" method="POST" onsubmit="return validarFormularioEgreso()">
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>Centro de Consumo Destinatario</label>
                        <select name="area_id" id="egr-inp-area" required>
                            <option value="">Seleccionar centro de consumo...</option>
                            <?php foreach ($centrosConsumo as $cc): ?>
                                <option value="<?= $cc['id'] ?>"><?= htmlspecialchars($cc['codigo']) ?> - <?= htmlspecialchars($cc['nombre']) ?> (Grupo: <?= htmlspecialchars($cc['grupo_nombre']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fecha de Salida</label>
                        <input type="date" name="fecha" id="egr-inp-fecha" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Funcionario / Responsable Receptor</label>
                    <select name="responsable_id" id="egr-inp-responsable" required>
                        <option value="">Seleccionar funcionario...</option>
                        <?php foreach ($personal as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> (<?= htmlspecialchars($p['area_actual']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Motivo de Salida / Despacho</label>
                    <input type="text" name="motivo" id="egr-inp-motivo" required placeholder="Ej: Abastecimiento mensual, Reparación emergente de grúa...">
                </div>

                <div class="form-group">
                    <label>Observaciones Adicionales</label>
                    <textarea name="observaciones" id="egr-inp-obs" placeholder="Detalles de la entrega, firmas pendientes..."></textarea>
                </div>

                <!-- Sección dinámica InvCabecera-Detalle -->
                <div style="border-top:1px solid var(--border-color);padding-top:16px;margin-top:16px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                        <h4 style="font-size:14px;color:var(--text-main);font-weight:600;"><i class="fa-solid fa-list"></i> Detalle de Salida</h4>
                        <button type="button" class="btn-outline" style="padding:6px 12px;font-size:12px;" onclick="agregarFilaDetalleEgreso()"><i class="fa-solid fa-plus"></i> Agregar Fila</button>
                    </div>

                    <div style="max-height: 250px; overflow-y: auto;">
                        <table style="width:100%;font-size:13px;" id="tabla-detalles-egreso">
                            <thead>
                                <tr style="background:var(--secondary-bg);">
                                    <th style="padding:8px 12px;text-align:left;font-size:11px;">Ítem / Producto con Existencia</th>
                                    <th style="padding:8px 12px;text-align:center;font-size:11px;width:120px;">Cant. Salida</th>
                                    <th style="padding:8px 12px;text-align:center;font-size:11px;width:150px;">Stock Disponible</th>
                                    <th style="padding:8px 12px;font-size:11px;width:50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbody-detalles-egreso">
                                <!-- La primera fila se inyectará dinámicamente al abrir -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span id="stock-inv_error-global" style="color:var(--danger);font-size:12px;font-weight:600;display:none;margin-right:auto;"><i class="fa-solid fa-triangle-exclamation"></i> Hay errores de stock en la lista.</span>
                <button type="button" class="btn-outline" onclick="cerrarModalEgreso()">Cancelar</button>
                <button type="submit" class="btn-primary" id="btn-submit-egreso"><i class="fa-solid fa-save"></i> Registrar Egreso</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Consulta Rápida Detalle de InvEgreso -->
<div class="modal-overlay" id="egr-modal-detalle">
    <div class="modal-content modal-content-wide">
        <div class="modal-header">
            <h2>Detalle de Despacho / Egreso</h2>
            <button class="modal-close" onclick="cerrarDetallesEgreso()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body" id="egr-det-content">
            <!-- Cargado dinámicamente -->
        </div>
    </div>
</div>

<!-- Configuración de Bodega y Stock Map serializado en HTML -->
<div id="bodega-egresos-config" data-stock-map="<?= htmlspecialchars($stockMapJson, ENT_QUOTES, 'UTF-8') ?>" style="display:none;"></div>

<!-- Lógica JS Ultra-Compatible y Telemetría -->
<script>
    console.log('[Telemetría Bodega] Iniciando carga de scripts en inv_bodega_egresos.php');

    // Serializar centros de consumo para autocompletar responsable
    const centrosConsumoData = <?= json_encode($centrosConsumo) ?>;

    document.addEventListener('DOMContentLoaded', () => {
        const areaSelect = document.getElementById('egr-inp-area');
        if (areaSelect) {
            areaSelect.addEventListener('change', function() {
                const ccId = this.value;
                if (ccId) {
                    const cc = centrosConsumoData.find(c => c.id == ccId);
                    if (cc && cc.funcionario) {
                        const respSelect = document.getElementById('egr-inp-responsable');
                        if (respSelect) {
                            let encontrado = false;
                            for (let i = 0; i < respSelect.options.length; i++) {
                                const opt = respSelect.options[i];
                                const optTextClean = opt.text.split('(')[0].trim().toLowerCase();
                                const ccFuncClean = cc.funcionario.trim().toLowerCase();
                                if (optTextClean.includes(ccFuncClean) || ccFuncClean.includes(optTextClean)) {
                                    respSelect.value = opt.value;
                                    encontrado = true;
                                    console.log('[Telemetría Bodega] Responsable autocompletado: ' + cc.funcionario);
                                    break;
                                }
                            }
                            if (!encontrado) {
                                console.log('[Telemetría Bodega] Funcionario ' + cc.funcionario + ' no encontrado en el catálogo de personal.');
                            }
                        }
                    }
                }
            });
        }
    });

    var stockMap = {};
    try {
        var configEl = document.getElementById('bodega-egresos-config');
        if (configEl) {
            stockMap = JSON.parse(configEl.dataset.stockMap);
            console.log('[Telemetría Bodega] Mapa de stock cargado exitosamente.');
        }
    } catch (e) {
        console.error('[Telemetría Bodega] Error al cargar mapa de stock:', e);
    }

    var filaIndexEgreso = 1;

    // Construcción ultra-compatible de plantilla HTML usando matriz de cadenas
    var filaEgresoTemplate = [
        '<tr>',
        '    <td style="padding:8px 12px;">',
        '        <select name="items[{INDEX}][item_id]" required class="select-item" onchange="verificarStockFila(this)" style="width:100%;padding:6px;border:1px solid var(--border-color);border-radius:4px;background:var(--secondary-bg);">',
        '            <option value="">Seleccionar ítem...</option>',
        <?php foreach ($itemsInventario as $item): ?>
        '            <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['secuencial']) ?> - <?= htmlspecialchars(str_replace(array("'", "\"", "\r", "\n"), " ", $item['nombre']), ENT_QUOTES, 'UTF-8') ?> (Marca: <?= htmlspecialchars(str_replace(array("'", "\"", "\r", "\n"), " ", $item['marca']), ENT_QUOTES, 'UTF-8') ?>) [Stock: <?= $item['cantidad'] ?>]</option>',
        <?php endforeach; ?>
        '        </select>',
        '    </td>',
        '    <td style="padding:8px 12px;">',
        '        <input type="number" name="items[{INDEX}][cantidad]" required min="1" class="cant-item" value="1" oninput="verificarStockFila(this)" style="width:100%;padding:6px;border:1px solid var(--border-color);border-radius:4px;background:var(--secondary-bg);text-align:center;">',
        '    </td>',
        '    <td style="padding:8px 12px;text-align:center;">',
        '        <span class="stock-display" style="font-weight:600;color:var(--text-muted);">--</span>',
        '    </td>',
        '    <td style="padding:8px 12px;text-align:center;">',
        '        <button type="button" class="btn-accion btn-eliminar" onclick="eliminarFilaDetalleEgreso(this)" style="width:24px;height:24px;border-radius:4px;"><i class="fa-solid fa-trash-can"></i></button>',
        '    </td>',
        '</tr>'
    ].join('\n');

    console.log('[Telemetría Bodega] Plantilla filaEgresoTemplate inicializada correctamente.');

    function abrirModalEgreso() {
        console.log('[Telemetría Bodega] abrirModalEgreso() llamado.');
        try {
            document.getElementById('egr-inp-area').value = '';
            document.getElementById('egr-inp-motivo').value = '';
            document.getElementById('egr-inp-obs').value = '';
            document.getElementById('egr-inp-responsable').value = '';
            document.getElementById('stock-inv_error-global').style.display = 'none';
            document.getElementById('btn-submit-egreso').disabled = false;
            
            var tbody = document.getElementById('tbody-detalles-egreso');
            if (tbody) {
                tbody.innerHTML = filaEgresoTemplate.replace(/{INDEX}/g, '0');
                console.log('[Telemetría Bodega] Fila base (0) inyectada en tbody-detalles-egreso.');
            } else {
                console.error('[Telemetría Bodega] tbody-detalles-egreso no se encuentra en el DOM.');
            }
            
            filaIndexEgreso = 1;
            var modal = document.getElementById('egr-modal');
            if (modal) {
                modal.classList.add('active');
                console.log('[Telemetría Bodega] Modal overlay de egresos activo.');
            } else {
                console.error('[Telemetría Bodega] El overlay #egr-modal no existe.');
            }
        } catch (e) {
            console.error('[Telemetría Bodega] Falló abrirModalEgreso():', e);
            alert('Error al abrir el formulario: ' + e.message);
        }
    }

    function cerrarModalEgreso() {
        console.log('[Telemetría Bodega] cerrarModalEgreso() llamado.');
        var modal = document.getElementById('egr-modal');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    function agregarFilaDetalleEgreso() {
        console.log('[Telemetría Bodega] agregarFilaDetalleEgreso() llamado. Índice actual: ' + filaIndexEgreso);
        try {
            var tbody = document.getElementById('tbody-detalles-egreso');
            if (tbody) {
                var tempDiv = document.createElement('tbody');
                tempDiv.innerHTML = filaEgresoTemplate.replace(/{INDEX}/g, filaIndexEgreso);
                var newRow = tempDiv.firstElementChild;
                tbody.appendChild(newRow);
                filaIndexEgreso++;
                console.log('[Telemetría Bodega] Fila agregada con éxito.');
            }
        } catch (e) {
            console.error('[Telemetría Bodega] Falló agregarFilaDetalleEgreso():', e);
        }
    }

    function eliminarFilaDetalleEgreso(btn) {
        console.log('[Telemetría Bodega] eliminarFilaDetalleEgreso() llamado.');
        try {
            var tr = btn.closest('tr');
            var tbody = document.getElementById('tbody-detalles-egreso');
            if (tbody && tbody.rows.length <= 1) {
                alert('Debe tener al menos un ítem para registrar el egreso.');
                return;
            }
            if (tr) {
                tr.parentNode.removeChild(tr);
                console.log('[Telemetría Bodega] Fila de egreso removida.');
            }
            verificarEstatusErroresGlobal();
        } catch (e) {
            console.error('[Telemetría Bodega] Falló eliminarFilaDetalleEgreso():', e);
        }
    }

    function verificarStockFila(element) {
        try {
            var tr = element.closest('tr');
            var select = tr.querySelector('.select-item');
            var input = tr.querySelector('.cant-item');
            var display = tr.querySelector('.stock-display');

            if (!select || !input || !display) return;

            if (select.value === '') {
                display.textContent = '--';
                display.style.color = 'var(--text-muted)';
                return;
            }

            var itemId = parseInt(select.value);
            var itemInfo = stockMap[itemId];
            var stockDisponible = itemInfo ? itemInfo.stock : 0;
            var cantSalida = parseInt(input.value) || 0;

            display.textContent = stockDisponible + ' unidades';

            if (cantSalida > stockDisponible) {
                display.style.color = 'var(--danger)';
                display.innerHTML = 'Excede stock<br><span style="font-size:10px;">(Máx: ' + stockDisponible + ')</span>';
                tr.style.background = 'rgba(239, 68, 68, 0.03)';
            } else {
                display.style.color = 'var(--success)';
                tr.style.background = 'transparent';
            }

            verificarEstatusErroresGlobal();
        } catch (e) {
            console.error('[Telemetría Bodega] Falló verificarStockFila():', e);
        }
    }

    function verificarEstatusErroresGlobal() {
        try {
            var rows = document.querySelectorAll('#tbody-detalles-egreso tr');
            var errorStock = false;

            rows.forEach(function(tr) {
                var select = tr.querySelector('.select-item');
                var input = tr.querySelector('.cant-item');
                if (select && select.value !== '') {
                    var itemId = parseInt(select.value);
                    var itemInfo = stockMap[itemId];
                    var stockDisponible = itemInfo ? itemInfo.stock : 0;
                    var cantSalida = parseInt(input.value) || 0;

                    if (cantSalida > stockDisponible) {
                        errorStock = true;
                    }
                }
            });

            var errorLabel = document.getElementById('stock-inv_error-global');
            var btnSubmit = document.getElementById('btn-submit-egreso');

            if (errorLabel && btnSubmit) {
                if (errorStock) {
                    errorLabel.style.display = 'inline-block';
                    btnSubmit.disabled = true;
                    btnSubmit.style.opacity = '0.5';
                    btnSubmit.style.cursor = 'not-allowed';
                } else {
                    errorLabel.style.display = 'none';
                    btnSubmit.disabled = false;
                    btnSubmit.style.opacity = '1';
                    btnSubmit.style.cursor = 'pointer';
                }
            }
        } catch (e) {
            console.error('[Telemetría Bodega] Falló verificarEstatusErroresGlobal():', e);
        }
    }

    function validarFormularioEgreso() {
        console.log('[Telemetría Bodega] validarFormularioEgreso() llamado.');
        var selects = document.querySelectorAll('#tbody-detalles-egreso select');
        var selectedIds = [];
        for (var i = 0; i < selects.length; i++) {
            var s = selects[i];
            if (s.value === '') {
                alert('Por favor complete todos los ítems seleccionados.');
                return false;
            }
            if (selectedIds.indexOf(s.value) !== -1) {
                alert('No se permiten ítems duplicados en el mismo egreso de bodega. Modifique la cantidad en una sola fila.');
                return false;
            }
            selectedIds.push(s.value);
        }
        return true;
    }

    function cerrarDetallesEgreso() {
        console.log('[Telemetría Bodega] cerrarDetallesEgreso() llamado.');
        var modal = document.getElementById('egr-modal-detalle');
        if (modal) {
            modal.classList.remove('active');
        }
    }

    function verDetallesEgreso(id) {
        console.log('[Telemetría Bodega] verDetallesEgreso() llamado para ID: ' + id);
        var content = document.getElementById('egr-det-content');
        var modal = document.getElementById('egr-modal-detalle');
        
        if (!content || !modal) {
            console.error('[Telemetría Bodega] Los contenedores del visor no existen.');
            return;
        }

        content.innerHTML = '<div style="text-align:center;padding:40px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:32px;color:var(--primary);"></i><p style="margin-top:12px;">Cargando detalles de egreso...</p></div>';
        modal.classList.add('active');

        fetch('index.php?route=egresos&action=verDetalle&id=' + id)
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
            .then(function(egr) {
                if (egr.inv_error) throw new Error(egr.inv_error);

                var tablaItems = '';
                egr.detalles.forEach(function(det) {
                    tablaItems += 
                        '<tr style="border-bottom: 1px solid var(--border-color);">' +
                            '<td style="padding: 8px 10px;font-family: monospace;font-weight: 700;color: var(--text-color);">' + det.item_secuencial + '</td>' +
                            '<td style="padding: 8px 10px;">' +
                                '<strong style="color:var(--text-color);">' + det.item_nombre + '</strong><br>' +
                                '<span style="font-size:11px;color:var(--text-muted);">' + det.item_categoria + ' | Marca: ' + det.item_marca + '</span>' +
                            '</td>' +
                            '<td style="padding: 8px 10px;text-align:center;">' +
                                '<span class="status-badge dispatched" style="background:rgba(239, 68, 68, 0.08);color:#ef4444;font-weight:700;padding:2px 8px;border-radius:6px;font-size:11px;">' + det.cantidad + ' u.</span>' +
                            '</td>' +
                        '</tr>';
                });

                var obsText = egr.observaciones ? egr.observaciones : 'Sin observaciones adicionales registradas.';

                content.innerHTML = 
                    '<div class="detalle-header" style="display:flex;align-items:center;gap:18px;margin-bottom:24px;border-bottom:1px solid var(--border-color);padding-bottom:20px;">' +
                        '<div class="detalle-icono" style="font-size:36px;color:#ef4444;background:rgba(239,68,68,0.1);width:68px;height:68px;border-radius:18px;display:flex;align-items:center;justify-content:center;box-shadow: 0 4px 12px rgba(0,0,0,0.05);"><i class="fa-solid fa-circle-arrow-up"></i></div>' +
                        '<div style="flex:1;">' +
                            '<h2 style="margin:0 0 6px 0;font-size:22px;font-weight:700;color:var(--text-color);letter-spacing:-0.5px;">Egreso: ' + egr.secuencial + '</h2>' +
                            '<span style="font-size:13px;color:var(--text-muted);font-weight:500;"><i class="fa-solid fa-calendar-day" style="margin-right:6px;"></i>Fecha de despacho: <strong>' + egr.fecha + '</strong></span>' +
                        '</div>' +
                    '</div>' +

                    '<div class="modal-detail-layout">' +
                        '<div>' +
                            '<div class="detalle-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">' +
                                '<div class="detalle-campo" style="background:var(--panel-bg);padding:12px 16px;border-radius:12px;border:1px solid var(--border-color);display:flex;flex-direction:column;gap:4px;">' +
                                    '<label style="display:block;font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;"><i class="fa-solid fa-hotel" style="margin-right:6px;color:#ef4444;"></i>Área Solicitante / Destino</label>' +
                                    '<span style="font-size:14px;font-weight:700;color:var(--text-color);">' + egr.area_destino + '</span>' +
                                '</div>' +
                                '<div class="detalle-campo" style="background:var(--panel-bg);padding:12px 16px;border-radius:12px;border:1px solid var(--border-color);display:flex;flex-direction:column;gap:4px;">' +
                                    '<label style="display:block;font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;"><i class="fa-solid fa-user-tie" style="margin-right:6px;color:#ef4444;"></i>Responsable de Entrega</label>' +
                                    '<span style="font-size:14px;font-weight:700;color:var(--text-color);">' + egr.responsable + '</span>' +
                                '</div>' +
                            '</div>' +
                            
                            '<div style="background:linear-gradient(135deg, rgba(239,68,68,0.02) 0%, rgba(239,68,68,0.06) 100%);padding:12px 16px;border-radius:12px;margin-bottom:16px;border:1px solid rgba(239,68,68,0.08);">' +
                                '<span style="display:block;font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;"><i class="fa-solid fa-file-signature" style="color:#ef4444;margin-right:6px;"></i>Motivo del Egreso</span>' +
                                '<p style="font-size:13px;font-weight:600;margin:0;color:var(--text-color);line-height:1.4;">' + egr.motivo + '</p>' +
                            '</div>' +
                            
                            '<div class="detalle-campo" style="margin-bottom:16px;">' +
                                '<label style="display:flex;align-items:center;gap:8px;font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;"><i class="fa-solid fa-message" style="color:#ef4444;"></i>Observaciones Históricas</label>' +
                                '<span style="font-size:13px;line-height:1.5;display:block;background:var(--panel-bg);padding:12px 14px;border-radius:12px;border:1px solid var(--border-color);color:var(--text-color);font-style:italic;max-height: 90px; overflow-y: auto;">' + obsText + '</span>' +
                            '</div>' +
                        '</div>' +
                        
                        '<div>' +
                            '<h4 style="font-size:12px;margin:0 0 10px 0;font-weight:700;color:var(--text-color);text-transform:uppercase;letter-spacing:0.5px;display:flex;align-items:center;gap:8px;"><i class="fa-solid fa-boxes-stacked" style="color:#ef4444;"></i> Insumos Despachados</h4>' +
                            '<div style="border:1px solid var(--border-color);border-radius:12px;overflow:hidden;margin-bottom:16px;background:var(--panel-bg);max-height: 250px; overflow-y: auto;">' +
                                '<table style="width:100%;font-size:12px;border-collapse:collapse;">' +
                                    '<thead>' +
                                        '<tr style="background:rgba(0,0,0,0.02);border-bottom:1px solid var(--border-color);">' +
                                            '<th style="padding:8px;text-align:left;font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Código</th>' +
                                            '<th style="padding:8px;text-align:left;font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Ítem / Insumo</th>' +
                                            '<th style="padding:8px;text-align:center;font-size:10px;color:var(--text-muted);font-weight:700;text-transform:uppercase;width:110px;">Cant.</th>' +
                                        '</tr>' +
                                    '</thead>' +
                                    '<tbody>' +
                                        tablaItems +
                                    '</tbody>' +
                                '</table>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +

                    '<div style="display:flex;justify-content:flex-end;margin-top:16px;gap:12px;border-top:1px solid var(--border-color);padding-top:16px;">' +
                        '<a href="index.php?route=egresos&action=acta&id=' + egr.id + '" target="_blank" class="btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;background:#ef4444;border-color:#ef4444;"><i class="fa-solid fa-file-pdf"></i> Imprimir Acta</a>' +
                    '</div>';
                console.log('[Telemetría Bodega] Detalles de egreso renderizados.');
            })
            .catch(function(err) {
                console.error('[Telemetría Bodega] Falló verDetallesEgreso():', err);
                content.innerHTML = 
                    '<div style="text-align:center;padding:40px;color:var(--danger);">' +
                        '<i class="fa-solid fa-triangle-exclamation" style="font-size:32px;margin-bottom:12px;"></i>' +
                        '<p style="font-weight:600;">Error al cargar detalles</p>' +
                        '<p style="font-size:12px;color:var(--text-muted);margin-top:4px;">' + err.message + '</p>' +
                    '</div>';
            });
    }

    console.log('[Telemetría Bodega] Todos los scripts de inv_bodega_egresos.php cargados exitosamente.');
</script>
