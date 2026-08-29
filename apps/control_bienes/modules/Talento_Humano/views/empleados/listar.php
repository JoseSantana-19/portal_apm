<?php /* directorio.php – Vista: tabla de funcionarios con hero y filtros */ ?>
<div class="content-shell">
    <!-- HERO / MÉTRICAS -->
    <section class="hero" style="margin-bottom: 24px; padding: 24px; border-radius: 16px; background: linear-gradient(135deg, var(--primary-color, #0f172a) 0%, #1e293b 100%); color: white; display: flex; justify-content: space-between; align-items: center; gap: 24px;">
        <div>
            <div class="hero-kicker" style="font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.8; margin-bottom: 8px;">Centro de Mando · Expedientes APM</div>
            <h2 style="font-size: 26px; font-weight: 700; margin-bottom: 12px; color: white;">Directorio de Personal</h2>
            <p style="opacity: 0.8; font-size: 14px; margin-bottom: 20px; line-height: 1.5;">Tabla maestra de todos los funcionarios. Busque, filtre y abra el expediente detallado de cada servidor. Emita Acciones de Personal directamente desde cada fila.</p>
            <div class="hero-actions" style="display: flex; gap: 12px; align-items: center;">
                <a href="index.php?route=talento_crear" class="btn btn-primary" id="btn-nuevo-expediente" style="background: var(--accent-color, #3b82f6); border: none; color: white; padding: 10px 18px; border-radius: 10px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 13px;">
                    <i class="bi bi-person-plus-fill"></i> + Nuevo Expediente
                </a>
                <button class="btn btn-ghost" id="btn-exportar-directorio" onclick="showToast('Exportando directorio de funcionarios...', 'info')" style="background: transparent; border: none; color: rgba(255,255,255,0.8); padding: 10px 18px; border-radius: 10px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 13px;">
                    <i class="bi bi-file-earmark-arrow-down"></i> Exportar
                </button>
            </div>
        </div>
        <?php
        $total     = count($empleados);
        $activos   = count(array_filter($empleados, fn($e) => (int)($e['estado'] ?? 0) === 1));
        $permisos  = 2; // Demostración
        $inactivos = count(array_filter($empleados, fn($e) => (int)($e['estado'] ?? 1) === 0));
        ?>
        <div class="metrics" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; min-width: 320px;">
            <div class="metric-card" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 16px; border-radius: 12px;">
                <div class="metric-label" style="font-size: 12px; opacity: 0.7;">Registrados</div>
                <div class="metric-value" style="font-size: 24px; font-weight: 700; color: white; margin: 4px 0;"><?= $total ?></div>
                <div class="metric-foot" style="font-size: 10px; opacity: 0.5;">Directorio APM</div>
            </div>
            <div class="metric-card" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); padding: 16px; border-radius: 12px;">
                <div class="metric-label" style="font-size: 12px; color: #10b981;">Activos</div>
                <div class="metric-value" style="font-size: 24px; font-weight: 700; color: #10b981; margin: 4px 0;"><?= $activos ?></div>
                <div class="metric-foot" style="font-size: 10px; opacity: 0.6; color: #10b981;">En funciones hoy</div>
            </div>
            <div class="metric-card" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); padding: 16px; border-radius: 12px;">
                <div class="metric-label" style="font-size: 12px; color: #f59e0b;">Permisos</div>
                <div class="metric-value" style="font-size: 24px; font-weight: 700; color: #f59e0b; margin: 4px 0;"><?= $permisos ?></div>
                <div class="metric-foot" style="font-size: 10px; opacity: 0.6; color: #f59e0b;">Con permisos vigentes</div>
            </div>
            <div class="metric-card" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 16px; border-radius: 12px;">
                <div class="metric-label" style="font-size: 12px; opacity: 0.7;">Inactivos</div>
                <div class="metric-value" style="font-size: 24px; font-weight: 700; color: white; margin: 4px 0;"><?= $inactivos ?></div>
                <div class="metric-foot" style="font-size: 10px; opacity: 0.5;">Historial desvinculados</div>
            </div>
        </div>
    </section>

    <!-- TABLA DIRECTORIO -->
    <section class="card table-card">
        <div class="card-header">
            <div>
                <h3>Directorio de funcionarios</h3>
                <p>Consulta, filtra y selecciona registros para editar.</p>
            </div>
            <div class="chip">
                <i class="bi bi-lightning-charge"></i>
                Resultados: <span id="resultCount"><?= $total ?></span>
            </div>
        </div>
        <div class="toolbar">
            <div class="input search-input">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" oninput="filterTable()" placeholder="Buscar por cédula, nombre o cargo...">
            </div>
            <div class="filter-group">
                <select id="departmentFilter" onchange="filterTable()">
                    <option value="">Todas las áreas</option>
                    <?php
                    $depts = array_unique(array_column($empleados, 'direccion_area'));
                    sort($depts);
                    foreach ($depts as $d):
                        if (!empty($d)): ?>
                        <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                    <?php endif; endforeach; ?>
                </select>
                <select id="contratoFilter" onchange="filterTable()">
                    <option value="">Tipo de contrato</option>
                    <option value="Nombramiento">Nombramiento</option>
                    <option value="Contrato">Contrato</option>
                </select>
                <select id="statusFilter" onchange="filterTable()">
                    <option value="">Estado general</option>
                    <option value="Activo">Activo</option>
                    <option value="Permiso">Permiso</option>
                    <option value="Inactivo">Inactivo</option>
                </select>
                <button class="btn btn-outline" onclick="resetFilters()" id="btn-limpiar-filtros">
                    <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                </button>
            </div>
        </div>
        <div class="table-wrap">
            <table id="employeeTable">
                <thead>
                    <tr>
                        <th>Cédula</th>
                        <th>Funcionario</th>
                        <th>Área / Departamento</th>
                        <th>Cargo / Contrato</th>
                        <th>Estado</th>
                        <th style="text-align:center; min-width:220px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody">
                <?php if (empty($empleados)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:24px;">No hay funcionarios registrados.</td></tr>
                <?php else: foreach ($empleados as $i => $emp):
                    $nombre   = isset($emp['nombres'], $emp['apellidos'])
                                ? trim($emp['nombres'] . ' ' . $emp['apellidos'])
                                : ($emp['apellidos_nombres'] ?? $emp['nombres'] ?? '');
                    $cedula   = $emp['cedula']              ?? $emp['cedula_pasaporte']  ?? '';
                    $depto    = $emp['direccion_area']      ?? $emp['departamento']      ?? '';
                    $cargo    = $emp['cargo']               ?? $emp['denominacion_puesto'] ?? '';
                    $id       = (int)($emp['id']            ?? $emp['empleado_id']       ?? 0);
                    $correo   = $emp['correo_institucional'] ?? $emp['correo']            ?? '';
                    $contrato = $emp['tipo_contrato']        ?? 'N/A';

                    $estado_num = (int)($emp['estado'] ?? 1);
                    $estado     = ($estado_num === 1) ? 'Activo' : 'Inactivo';

                    $statusClass = ['Activo' => 'status-active', 'Permiso' => 'status-leave'][$estado] ?? 'status-inactive';
                ?>
                    <tr class="table-row" style="animation-delay:<?= $i * 0.04 ?>s"
                        data-nombre="<?= strtolower(htmlspecialchars($nombre)) ?>"
                        data-cedula="<?= htmlspecialchars($cedula) ?>"
                        data-dept="<?= strtolower(htmlspecialchars($depto)) ?>"
                        data-cargo="<?= strtolower(htmlspecialchars($cargo)) ?>"
                        data-contrato="<?= htmlspecialchars($contrato) ?>"
                        data-estado="<?= htmlspecialchars($estado) ?>">
                        <td><?= htmlspecialchars($cedula) ?></td>
                        <td>
                            <div class="name-cell">
                                <?php
                                $rutaFoto = $emp['ruta_foto'] ?? '';
                                $fotoSrc  = (!empty($rutaFoto) && file_exists(ROOT_PATH . $rutaFoto))
                                            ? htmlspecialchars($rutaFoto)
                                            : "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2394a3b8'><path d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/></svg>";
                                ?>
                                <div class="avatar avatar-foto">
                                    <img src="<?= $fotoSrc ?>"
                                         alt="<?= htmlspecialchars(mb_substr($nombre, 0, 1)) ?>"
                                         onerror="this.onerror=null; this.src=&quot;data:image/svg+xml;utf8,&lt;svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2394a3b8'&gt;&lt;path d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/&gt;&lt;/svg&gt;&quot;;">
                                </div>
                                <div class="name-meta">
                                    <span><?= htmlspecialchars($nombre) ?></span>
                                    <small><?= htmlspecialchars($correo ?: 'Sin correo asignado') ?></small>
                                </div>
                            </div>
                        </td>
                        <td><span class="dept-pill"><?= htmlspecialchars($depto) ?></span></td>
                        <td>
                            <div><?= htmlspecialchars($cargo) ?></div>
                            <small><?= htmlspecialchars($contrato) ?></small>
                        </td>
                        <td><span class="status-pill <?= $statusClass ?>"><?= htmlspecialchars($estado) ?></span></td>
                        <td class="action-cell">
                            <!-- Editar expediente -->
                            <a href="index.php?route=talento_editar&id=<?= $id ?>"
                               class="btn-edit" title="Editar expediente">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <!-- Imprimir Ficha PDF -->
                            <a href="index.php?route=talento_imprimir_ficha&id=<?= $id ?>"
                               class="btn-pdf" title="Imprimir Ficha PDF"
                               target="_blank">
                                <i class="bi bi-file-pdf"></i>
                            </a>
                            <!-- Eliminar -->
                            <a href="index.php?route=talento_borrar&id=<?= $id ?>"
                               class="btn-delete" title="Eliminar registro"
                               onclick="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($nombre)) ?>? Esta acción no se puede deshacer.');">
                                <i class="bi bi-trash3"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            <div id="noDataMessage" class="no-data hidden">
                <div class="no-data-icon"><i class="bi bi-inbox"></i></div>
                <h4>No se encontraron registros</h4>
                <p>Ajuste los filtros de búsqueda.</p>
            </div>
        </div>
    </section>
</div>

<script>
/* Filtros de tabla en el lado del cliente */
function filterTable() {
    const q        = document.getElementById('searchInput').value.toLowerCase();
    const dept     = document.getElementById('departmentFilter').value.toLowerCase();
    const contrato = document.getElementById('contratoFilter').value.toLowerCase();
    const est      = document.getElementById('statusFilter').value;
    let visible    = 0;
    document.querySelectorAll('#employeeTableBody tr.table-row').forEach(tr => {
        const matchQ = tr.dataset.nombre.includes(q) ||
                       tr.dataset.cedula.includes(q)  ||
                       tr.dataset.dept.includes(q)    ||
                       tr.dataset.cargo.includes(q);
        const matchD = !dept     || tr.dataset.dept.includes(dept);
        const matchC = !contrato || tr.dataset.contrato.toLowerCase().includes(contrato);
        const matchE = !est      || tr.dataset.estado === est;
        const show   = matchQ && matchD && matchC && matchE;
        tr.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('resultCount').textContent = visible;
    document.getElementById('noDataMessage').classList.toggle('hidden', visible > 0);
}
function resetFilters() {
    document.getElementById('searchInput').value        = '';
    document.getElementById('departmentFilter').value   = '';
    document.getElementById('contratoFilter').value     = '';
    document.getElementById('statusFilter').value       = '';
    filterTable();
}
</script>
