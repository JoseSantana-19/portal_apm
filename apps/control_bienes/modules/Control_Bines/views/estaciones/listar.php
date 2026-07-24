<?php
/**
 * TH_CABECERAS.PHP - Vista de Arquitectura de Datos / Tablas Maestras
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
?>

<!-- InvCabecera de Página -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1>Tablas de Cabecera</h1>
        <p>Configura la arquitectura relacional básica del sistema portuario: categorías, zonas, estados, marcas, líneas navieras, unidades de medida y productos.</p>
    </div>
    <div>
        <button class="btn-primary" onclick="abrirModalCabecera()"><i class="fa-solid fa-plus"></i> Agregar Elemento</button>
    </div>
</div>

<!-- Grid de Control y Tabla -->
<div style="display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start;" class="animate-fade-in">
    
    <!-- Panel Izquierdo: Selección de Tabla -->
    <div class="panel" style="padding:16px;">
        <h4 style="margin:0 0 16px 0;font-size:14px;color:var(--text-color);text-transform:uppercase;letter-spacing:0.05em;">Tablas Disponibles</h4>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <a href="index.php?route=cabeceras&tabla=categorias" class="filter-tab <?= ($tablaActiva === 'categorias') ? 'active' : '' ?>" style="text-align:left;display:flex;justify-content:between;align-items:center;text-decoration:none;padding:12px 16px;border-radius:8px;">
                <span><i class="fa-solid fa-tags" style="margin-right:8px;width:16px;"></i> Categorías (Grupos)</span>
                <span class="count-badge" style="background:var(--border-color);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;margin-left:auto;"><?= isset($conteos['categorias']) ? $conteos['categorias'] : 0 ?></span>
            </a>
            <a href="index.php?route=cabeceras&tabla=productos" class="filter-tab <?= ($tablaActiva === 'productos') ? 'active' : '' ?>" style="text-align:left;display:flex;justify-content:between;align-items:center;text-decoration:none;padding:12px 16px;border-radius:8px;">
                <span><i class="fa-solid fa-box-open" style="margin-right:8px;width:16px;"></i> Catálogo de Productos</span>
                <span class="count-badge" style="background:var(--border-color);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;margin-left:auto;"><?= isset($conteos['productos']) ? $conteos['productos'] : 0 ?></span>
            </a>
            <a href="index.php?route=cabeceras&tabla=unidades" class="filter-tab <?= ($tablaActiva === 'unidades') ? 'active' : '' ?>" style="text-align:left;display:flex;justify-content:between;align-items:center;text-decoration:none;padding:12px 16px;border-radius:8px;">
                <span><i class="fa-solid fa-calculator" style="margin-right:8px;width:16px;"></i> Unidades de Medida</span>
                <span class="count-badge" style="background:var(--border-color);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;margin-left:auto;"><?= isset($conteos['unidades']) ? $conteos['unidades'] : 0 ?></span>
            </a>
            <a href="index.php?route=cabeceras&tabla=tipos_iva" class="filter-tab <?= ($tablaActiva === 'tipos_iva') ? 'active' : '' ?>" style="text-align:left;display:flex;justify-content:between;align-items:center;text-decoration:none;padding:12px 16px;border-radius:8px;">
                <span><i class="fa-solid fa-file-invoice-dollar" style="margin-right:8px;width:16px;"></i> Tasas de IVA</span>
                <span class="count-badge" style="background:var(--border-color);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;margin-left:auto;"><?= isset($conteos['tipos_iva']) ? $conteos['tipos_iva'] : 0 ?></span>
            </a>
            <a href="index.php?route=cabeceras&tabla=zonas" class="filter-tab <?= ($tablaActiva === 'zonas') ? 'active' : '' ?>" style="text-align:left;display:flex;justify-content:between;align-items:center;text-decoration:none;padding:12px 16px;border-radius:8px;">
                <span><i class="fa-solid fa-map-location-dot" style="margin-right:8px;width:16px;"></i> Zonas / Terminales</span>
                <span class="count-badge" style="background:var(--border-color);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;margin-left:auto;"><?= isset($conteos['zonas']) ? $conteos['zonas'] : 0 ?></span>
            </a>
            <a href="index.php?route=cabeceras&tabla=estados" class="filter-tab <?= ($tablaActiva === 'estados') ? 'active' : '' ?>" style="text-align:left;display:flex;justify-content:between;align-items:center;text-decoration:none;padding:12px 16px;border-radius:8px;">
                <span><i class="fa-solid fa-circle-info" style="margin-right:8px;width:16px;"></i> Estados Operativos</span>
                <span class="count-badge" style="background:var(--border-color);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;margin-left:auto;"><?= isset($conteos['estados']) ? $conteos['estados'] : 0 ?></span>
            </a>
            <a href="index.php?route=cabeceras&tabla=marcas" class="filter-tab <?= ($tablaActiva === 'marcas') ? 'active' : '' ?>" style="text-align:left;display:flex;justify-content:between;align-items:center;text-decoration:none;padding:12px 16px;border-radius:8px;">
                <span><i class="fa-solid fa-copyright" style="margin-right:8px;width:16px;"></i> Marcas</span>
                <span class="count-badge" style="background:var(--border-color);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;margin-left:auto;"><?= isset($conteos['marcas']) ? $conteos['marcas'] : 0 ?></span>
            </a>
            <a href="index.php?route=cabeceras&tabla=lineas" class="filter-tab <?= ($tablaActiva === 'lineas') ? 'active' : '' ?>" style="text-align:left;display:flex;justify-content:between;align-items:center;text-decoration:none;padding:12px 16px;border-radius:8px;">
                <span><i class="fa-solid fa-ship" style="margin-right:8px;width:16px;"></i> Líneas Navieras</span>
                <span class="count-badge" style="background:var(--border-color);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;margin-left:auto;"><?= isset($conteos['lineas']) ? $conteos['lineas'] : 0 ?></span>
            </a>
        </div>
    </div>

    <!-- Panel Derecho: Tabla de Resultados -->
    <div class="panel" style="position:relative;">
        <div class="panel-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
            <h3 style="text-transform: capitalize; margin:0; flex:1; min-width:200px;">
                Tabla Maestra: <?= htmlspecialchars($tablaActiva === 'categorias' ? 'Categorías (Grupos)' : ($tablaActiva === 'unidades' ? 'Unidades de Medida' : ($tablaActiva === 'tipos_iva' ? 'Tasas de IVA' : $tablaActiva))) ?>
            </h3>
            
            <!-- Buscadores -->
            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <!-- Buscador Individual (Filtro local en tiempo real) -->
                <div style="position:relative; min-width:240px;">
                    <i class="fa-solid fa-filter" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:12px;"></i>
                    <input type="text" id="buscador-individual" placeholder="Filtrar esta tabla..." 
                           style="padding: 8px 12px 8px 32px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--secondary-bg); color: var(--text-color); font-size: 13px; width: 100%; transition: all 0.3s;"
                           oninput="filtrarTablaIndividual(this.value)">
                    <span id="buscador-individual-count" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); font-size:11px; font-weight:600; color:white; background:var(--primary-blue); padding:1px 6px; border-radius:10px; display:none;">0</span>
                </div>

                <button id="btn-toggle-datos" class="btn-outline btn-sm"
                        onclick="toggleDatosCabecera()"
                        style="display:flex; align-items:center; gap:8px; padding:8px 18px; border-radius:8px; font-size:13px; height:36px;">
                    <i class="fa-solid fa-table" id="icon-toggle-datos"></i>
                    <span id="lbl-toggle-datos">Mostrar datos</span>
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <?php if ($tablaActiva === 'productos'): ?>
                            <th>Nombre del Producto</th>
                            <th>Grupo / Categoría</th>
                            <th>Unidad de Medida</th>
                            <th>Aplica IVA</th>
                        <?php elseif ($tablaActiva === 'tipos_iva'): ?>
                            <th>Descripción</th>
                            <th>Tasa de IVA (%)</th>
                        <?php elseif ($tablaActiva === 'unidades'): ?>
                            <th>Nombre de la Unidad</th>
                            <th>Abreviatura</th>
                        <?php else: ?>
                            <th>Nombre / Descripción</th>
                            <?php if ($tablaActiva === 'estados'): ?>
                                <th>Indicador Visual</th>
                            <?php endif; ?>
                            <th>Información Adicional / Extra</th>
                        <?php endif; ?>
                        <th style="width: 100px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-datos-cabecera" style="display:none;">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="<?= $tablaActiva === 'productos' ? 6 : 5 ?>" style="text-align:center; padding:40px; color:var(--text-muted);">
                                <i class="fa-solid fa-database" style="font-size:32px; display:block; margin-bottom:12px; opacity:0.4;"></i>
                                No se encontraron registros en esta tabla
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr id="row-<?= $item['id'] ?>" class="animate-fade-in">
                                <td><strong>#<?= $item['id'] ?></strong></td>
                                <?php if ($tablaActiva === 'productos'): ?>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><span class="status-badge transit"><?= htmlspecialchars($item['grupo_nombre']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($item['unidad_nombre']) ?></strong></td>
                                    <td>
                                        <?php if ((int)$item['aplica_iva'] === 1): ?>
                                            <span class="status-badge active">SÍ (Aplica IVA)</span>
                                        <?php else: ?>
                                            <span class="status-badge inactive">NO (Exento)</span>
                                        <?php endif; ?>
                                    </td>
                                <?php elseif ($tablaActiva === 'tipos_iva'): ?>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><strong style="color:var(--primary); font-size: 15px;"><?= number_format($item['tasa_iva'], 2) ?>%</strong></td>
                                <?php elseif ($tablaActiva === 'unidades'): ?>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><code style="background:var(--border-color); padding:4px 8px; border-radius:4px; font-weight:600;"><?= htmlspecialchars($item['extra']) ?></code></td>
                                <?php else: ?>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <?php if ($tablaActiva === 'estados'): ?>
                                        <td><span class="status-badge <?= htmlspecialchars($item['clase']) ?>"><?= htmlspecialchars($item['nombre']) ?></span></td>
                                    <?php endif; ?>
                                    <td><?= !empty($item['extra']) ? htmlspecialchars($item['extra']) : '<span style="color:var(--text-muted);font-style:italic;">Ninguna</span>' ?></td>
                                <?php endif; ?>
                                <td class="acciones-cell">
                                    <button class="btn-accion btn-editar" onclick="editarCabecera(<?= htmlspecialchars(json_encode($item)) ?>)" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Crear / Editar Cabecera -->
<div class="modal-overlay" id="cabecera-modal">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header">
            <h2 id="cabecera-modal-title">Nuevo Elemento</h2>
            <button class="modal-close" onclick="cerrarModalCabecera()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="index.php?route=cabeceras&action=guardar" method="POST">
            <input type="hidden" name="tabla" value="<?= htmlspecialchars($tablaActiva) ?>">
            <input type="hidden" name="id" id="cab-inp-id" value="0">
            <div class="modal-body">
                <div class="form-group">
                    <label id="lbl-nombre-elemento">Nombre del Elemento</label>
                    <input type="text" name="nombre" id="cab-inp-nombre" required placeholder="Ej: Nueva Categoría o Marca">
                </div>

                <?php if ($tablaActiva === 'estados'): ?>
                    <div class="form-group">
                        <label>Color / Clase Visual</label>
                        <select name="clase" id="cab-inp-clase" required>
                            <option value="active">Verde (Operativo / Activo)</option>
                            <option value="pending">Naranja / Amarillo (Mantenimiento / Pendiente)</option>
                            <option value="inactive">Rojo (Fuera de Servicio / Inactivo)</option>
                            <option value="transit">Púrpura / Morado (En Tránsito / Movimiento)</option>
                            <option value="dispatched">Gris (Despachado / Entregado)</option>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if ($tablaActiva === 'productos'): ?>
                    <div class="form-group">
                        <label>Grupo / Categoría</label>
                        <select name="grupo_id" id="prod-inp-grupo" required>
                            <option value="">Seleccionar Grupo...</option>
                            <?php foreach ($categoriasList as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Unidad de Medida</label>
                        <select name="unidad_id" id="prod-inp-unidad" required>
                            <option value="">Seleccionar Unidad...</option>
                            <?php foreach ($unidadesList as $uni): ?>
                                <option value="<?= $uni['id'] ?>"><?= htmlspecialchars($uni['nombre']) ?> (<?= htmlspecialchars($uni['extra']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tributación de IVA</label>
                        <select name="aplica_iva" id="prod-inp-iva" required>
                            <option value="1" selected>Aplica tasa de IVA del Período</option>
                            <option value="0">Exento de IVA (Tasa 0%)</option>
                        </select>
                    </div>
                <?php elseif ($tablaActiva === 'tipos_iva'): ?>
                    <div class="form-group">
                        <label>Tasa Impositiva (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="tasa_iva" id="iva-inp-tasa" required placeholder="Ej: 15.00">
                    </div>
                <?php else: ?>
                    <div class="form-group" id="group-extra">
                        <label id="lbl-extra-elemento">Detalle Adicional (Extra)</label>
                        <input type="text" name="extra" id="cab-inp-extra" placeholder="Descripción adicional o código ISO...">
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="cerrarModalCabecera()">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const tablaActiva = <?= json_encode($tablaActiva) ?>;

    /* ========== Toggle mostrar/ocultar datos de la tabla ========== */
    var _datosVisibles = false;
    function toggleDatosCabecera() {
        const tbody  = document.getElementById('tbody-datos-cabecera');
        const icono  = document.getElementById('icon-toggle-datos');
        const lbl    = document.getElementById('lbl-toggle-datos');
        const btn    = document.getElementById('btn-toggle-datos');

        _datosVisibles = !_datosVisibles;

        if (_datosVisibles) {
            tbody.style.display = '';
            icono.className = 'fa-solid fa-eye-slash';
            lbl.textContent = 'Ocultar datos';
            btn.classList.remove('btn-outline');
            btn.classList.add('btn-primary');
        } else {
            tbody.style.display = 'none';
            icono.className = 'fa-solid fa-table';
            lbl.textContent = 'Mostrar datos';
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline');
        }
    }

    function abrirModalCabecera() {
        document.getElementById('cabecera-modal-title').textContent = 'Agregar Elemento';
        document.getElementById('cab-inp-id').value = '0';
        document.getElementById('cab-inp-nombre').value = '';
        
        // Ajustar labels según tabla
        const lblNombre = document.getElementById('lbl-nombre-elemento');
        if (lblNombre) {
            if (tablaActiva === 'productos') lblNombre.textContent = 'Nombre del Producto';
            else if (tablaActiva === 'unidades') lblNombre.textContent = 'Nombre de la Unidad';
            else if (tablaActiva === 'tipos_iva') lblNombre.textContent = 'Descripción del Tipo de IVA';
            else lblNombre.textContent = 'Nombre del Elemento';
        }

        const lblExtra = document.getElementById('lbl-extra-elemento');
        if (lblExtra) {
            if (tablaActiva === 'unidades') lblExtra.textContent = 'Abreviatura (Abre.)';
            else lblExtra.textContent = 'Detalle Adicional (Extra)';
        }

        const clase = document.getElementById('cab-inp-clase');
        if (clase) clase.value = 'active';

        const extra = document.getElementById('cab-inp-extra');
        if (extra) extra.value = '';

        const prodGrupo = document.getElementById('prod-inp-grupo');
        if (prodGrupo) prodGrupo.value = '';

        const prodUnidad = document.getElementById('prod-inp-unidad');
        if (prodUnidad) prodUnidad.value = '';

        const prodIva = document.getElementById('prod-inp-iva');
        if (prodIva) prodIva.value = '1';

        const ivaTasa = document.getElementById('iva-inp-tasa');
        if (ivaTasa) ivaTasa.value = '';

        document.getElementById('cabecera-modal').classList.add('active');
    }

    function cerrarModalCabecera() {
        document.getElementById('cabecera-modal').classList.remove('active');
    }

    function editarCabecera(item) {
        document.getElementById('cabecera-modal-title').textContent = 'Editar Elemento';
        document.getElementById('cab-inp-id').value = item.id;
        document.getElementById('cab-inp-nombre').value = item.nombre;
        
        // Ajustar labels según tabla
        const lblNombre = document.getElementById('lbl-nombre-elemento');
        if (lblNombre) {
            if (tablaActiva === 'productos') lblNombre.textContent = 'Nombre del Producto';
            else if (tablaActiva === 'unidades') lblNombre.textContent = 'Nombre de la Unidad';
            else if (tablaActiva === 'tipos_iva') lblNombre.textContent = 'Descripción del Tipo de IVA';
            else lblNombre.textContent = 'Nombre del Elemento';
        }

        const lblExtra = document.getElementById('lbl-extra-elemento');
        if (lblExtra) {
            if (tablaActiva === 'unidades') lblExtra.textContent = 'Abreviatura (Abre.)';
            else lblExtra.textContent = 'Detalle Adicional (Extra)';
        }

        const clase = document.getElementById('cab-inp-clase');
        if (clase && item.clase) clase.value = item.clase;

        const extra = document.getElementById('cab-inp-extra');
        if (extra) extra.value = item.extra ? item.extra : '';

        const prodGrupo = document.getElementById('prod-inp-grupo');
        if (prodGrupo && item.grupo_id) prodGrupo.value = item.grupo_id;

        const prodUnidad = document.getElementById('prod-inp-unidad');
        if (prodUnidad && item.unidad_id) prodUnidad.value = item.unidad_id;

        const prodIva = document.getElementById('prod-inp-iva');
        if (prodIva && item.aplica_iva !== undefined) prodIva.value = item.aplica_iva;

        const ivaTasa = document.getElementById('iva-inp-tasa');
        if (ivaTasa && item.tasa_iva !== undefined) ivaTasa.value = item.tasa_iva;

        document.getElementById('cabecera-modal').classList.add('active');
    }

    /* ========== FILTRADO Y BÚSQUEDA DUAL (INDIVIDUAL Y GLOBAL) ========== */

    // 1. Filtrado Individual en Tiempo Real (Cliente)
    function filtrarTablaIndividual(val) {
        const query = val.toLowerCase().trim();
        const tbody = document.getElementById('tbody-datos-cabecera');
        if (!tbody) return;
        
        // Si no son visibles los datos, los mostramos automáticamente al buscar
        if (query.length > 0 && typeof _datosVisibles !== 'undefined' && !_datosVisibles) {
            toggleDatosCabecera();
        }

        const rows = tbody.getElementsByTagName('tr');
        let visibleCount = 0;
        let totalCount = 0;
        
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            if (row.id === 'no-results-search-row' || (row.cells.length <= 1 && row.querySelector('td').getAttribute('colspan'))) {
                continue; // Saltar fila de "Sin registros" o de "No resultados"
            }
            
            totalCount++;
            let textMatch = false;
            
            // Buscar coincidencia en todas las celdas de texto (excepto la columna de acciones)
            for (let j = 0; j < row.cells.length - 1; j++) {
                const cellText = row.cells[j].innerText.toLowerCase();
                if (cellText.includes(query)) {
                    textMatch = true;
                    break;
                }
            }
            
            if (textMatch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }
        
        // Actualizar el contador en la interfaz
        const countBadge = document.getElementById('buscador-individual-count');
        if (countBadge) {
            if (query.length > 0) {
                countBadge.textContent = visibleCount;
                countBadge.style.display = 'inline-block';
                if (visibleCount === 0) {
                    countBadge.style.background = 'var(--danger)';
                } else {
                    countBadge.style.background = 'var(--primary-blue)';
                }
            } else {
                countBadge.style.display = 'none';
            }
        }
        
        // Fila de estado vacío para el buscador individual
        let noResultsRow = document.getElementById('no-results-search-row');
        if (visibleCount === 0 && totalCount > 0 && query.length > 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.id = 'no-results-search-row';
                const colspan = rows[0] ? rows[0].cells.length : 5;
                noResultsRow.innerHTML = `
                    <td colspan="${colspan}" style="text-align:center; padding:40px; color:var(--text-muted);">
                        <i class="fa-solid fa-magnifying-glass" style="font-size:24px; display:block; margin-bottom:12px; opacity:0.3;"></i>
                        No se encontraron coincidencias locales para "<strong>${val}</strong>"
                    </td>
                `;
                tbody.appendChild(noResultsRow);
            }
        } else {
            if (noResultsRow) {
                noResultsRow.remove();
            }
        }
    }

    // 2. Auto-resaltado y Scroll al cargar fila indicada
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const highlightId = urlParams.get('highlight');
        if (highlightId) {
            // Si la tabla está oculta, mostrarla primero
            if (typeof _datosVisibles !== 'undefined' && !_datosVisibles) {
                toggleDatosCabecera();
            }
            
            setTimeout(() => {
                const row = document.getElementById(`row-${highlightId}`);
                if (row) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.classList.add('highlighted-row');
                    
                    // Quitar iluminación después de unos segundos
                    setTimeout(() => {
                        row.classList.remove('highlighted-row');
                    }, 4000);
                }
            }, 300);
        }
    });
</script>
