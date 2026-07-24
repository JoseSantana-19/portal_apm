/**
 * APP_AJAX.JS - Script de Interactividad Premium y Filtrado AJAX
 * Provee filtros en tiempo real sin recargar página completa.
 * Compatible con PHP 7.4, 8.3 y 8.4
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Detección automática de página de inventario para búsqueda AJAX en tiempo real
    const formFiltros = document.querySelector('.filter-controls');
    const tableBody = document.querySelector('table tbody');
    
    if (formFiltros && tableBody && window.location.search.includes('route=inventario') && !window.location.search.includes('categoria=')) {
        const inputBusqueda = formFiltros.querySelector('input[name="termino"]');
        const selectZona = formFiltros.querySelector('select[name="zona"]');
        const selectEstado = formFiltros.querySelector('select[name="estado"]');

        if (inputBusqueda && selectZona && selectEstado) {
            const ejecutarFiltrado = () => {
                const term = encodeURIComponent(inputBusqueda.value);
                const zona = encodeURIComponent(selectZona.value);
                const estado = encodeURIComponent(selectEstado.value);
                
                // Mostrar spinner visual en la tabla mientras carga
                tableBody.style.opacity = '0.5';

                fetch(`index.php?route=inventario&action=filtrarAjax&termino=${term}&zona=${zona}&estado=${estado}`)
                    .then(res => res.json())
                    .then(items => {
                        tableBody.style.opacity = '1';
                        
                        if (items.length === 0) {
                            tableBody.innerHTML = `
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:40px; color:var(--text-muted);">
                                        <i class="fa-solid fa-inbox" style="font-size:32px; display:block; margin-bottom:12px; opacity:0.4;"></i>
                                        No se encontraron registros coincidentes
                                    </td>
                                </tr>
                            `;
                            return;
                        }

                        tableBody.innerHTML = '';
                        items.forEach(item => {
                            const valorBase = parseFloat(item.valor);
                            // Resolver tasa IVA (15% por defecto si no viene)
                            const tasaIva = 15.0; 
                            const valorTotal = valorBase + (valorBase * (tasaIva / 100));

                            // Asignar color e ícono por categoría
                            let catColor = '#64748b';
                            let icono = 'fa-box';
                            if (item.categoria === 'Maquinaria Pesada') { catColor = '#ef4444'; icono = 'fa-truck-monster'; }
                            else if (item.categoria === 'Contenedores') { catColor = '#3b82f6'; icono = 'fa-box-open'; }
                            else if (item.categoria === 'Equipos de Muelle') { catColor = '#10b981'; icono = 'fa-life-ring'; }
                            else if (item.categoria === 'Vehículos') { catColor = '#f59e0b'; icono = 'fa-truck-pickup'; }
                            else if (item.categoria === 'Herramientas') { catColor = '#8b5cf6'; icono = 'fa-wrench'; }

                            // Generar fila
                            const tr = document.createElement('tr');
                            tr.className = 'animate-fade-in';
                            tr.innerHTML = `
                                <td class="secuencial-cell">${item.secuencial}</td>
                                <td>
                                    <div class="item-name">
                                        <div class="item-img"><i class="fa-solid ${icono}"></i></div>
                                        <div class="item-info">
                                            <strong>${item.nombre}</strong>
                                            <span>Marca: ${item.marca}</span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="cat-badge" style="--cat-color: ${catColor}">${item.categoria}</span></td>
                                <td>${item.zona}</td>
                                <td>
                                    <strong>$${valorTotal.toLocaleString('es-EC', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                                    <span style="display:block;font-size:11px;color:var(--text-muted);">Base: $${valorBase.toLocaleString('es-EC', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                </td>
                                <td><span class="status-badge ${item.estadoClase}">${item.estado}</span></td>
                                <td class="acciones-cell">
                                    <button class="btn-accion btn-ver" onclick="verDetallesInventario(${item.id})" title="Ver Detalle"><i class="fa-solid fa-eye"></i></button>
                                    <button class="btn-accion btn-editar" onclick="editarRegistroInventario(${JSON.stringify(item).replace(/"/g, '&quot;')})" title="Editar"><i class="fa-solid fa-pen"></i></button>
                                    <a href="index.php?route=inventario&action=eliminar&id=${item.id}" class="btn-accion btn-eliminar" onclick="return confirm('¿Seguro que desea dar de baja este equipo?');" title="Eliminar"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            `;
                            tableBody.appendChild(tr);
                        });
                    })
                    .catch(err => {
                        console.error('Error al filtrar AJAX:', err);
                        tableBody.style.opacity = '1';
                    });
            };

            // Eventos en tiempo real
            let debounceTimer;
            inputBusqueda.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(ejecutarFiltrado, 300); // Evitar peticiones excesivas
            });

            selectZona.addEventListener('change', ejecutarFiltrado);
            selectEstado.addEventListener('change', ejecutarFiltrado);
            
            // Evitar recargar la página al presionar Enter en el formulario
            formFiltros.addEventListener('submit', (e) => {
                e.preventDefault();
                ejecutarFiltrado();
            });
        }
    }
});
