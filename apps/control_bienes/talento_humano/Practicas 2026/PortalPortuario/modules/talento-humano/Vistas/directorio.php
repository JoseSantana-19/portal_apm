<?php /* directorio.php – Vista: tabla de funcionarios con topbar, hero y filtros */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directorio de Funcionarios | Talento Humano APM</title>
    <meta name="description" content="Directorio de servidores públicos de la Autoridad Portuaria de Manta.">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/fonts.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
</head>
<body>
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()" title="Abrir menú lateral" aria-label="Abrir menú lateral">
    <i class="bi bi-layout-sidebar"></i>
</button>

<div class="app">
    <?php require_once ROOT . '/shared/menu.php'; ?>

    <section class="content">
        <!-- TOPBAR -->
        <header class="topbar">
            <div class="topbar-left">
                <div class="brand">
                    <img src="<?= IMG_URL ?>/logoapm.png" alt="Logo APM">
                    <div>
                        <h1>Autoridad Portuaria de Manta</h1>
                        <p>Modulo Talento Humano</p>
                    </div>
                </div>
            </div>
            <div class="topbar-actions">
                <div class="search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="globalSearch" placeholder="Buscar en toda la plataforma...">
                </div>
                <div class="icon-chip">
                    <i class="bi bi-calendar-event"></i>
                    <span id="currentDate">--</span>
                </div>
                <button class="icon-btn notify" title="Notificaciones">
                    <i class="bi bi-bell"></i>
                    <span class="notify-dot"></span>
                </button>
                <div class="user-pill">
                    <span>Usuario Talento Humano</span>
                    <small>APM</small>
                </div>
            </div>
        </header>

        <main class="main">
            <div class="content-shell">
                <!-- HERO / MÉTRICAS -->
                <section class="hero">
                    <div>
                        <div class="hero-kicker">Centro de Mando · Expedientes APM</div>
                        <h2>Directorio de Personal</h2>
                        <p>Tabla maestra de todos los funcionarios. Busque, filtre y abra el expediente detallado de cada servidor. Emita Acciones de Personal directamente desde cada fila.</p>
                        <div class="hero-actions">
                            <a href="<?= BASE_URL ?>/talento-humano/empleado/crear" class="btn btn-primary" id="btn-nuevo-expediente">
                                <i class="bi bi-person-plus-fill"></i> + Nuevo Expediente
                            </a>
                            <a href="<?= BASE_URL ?>/talento-humano/accion-personal" class="btn btn-outline" id="btn-accion-general">
                                <i class="bi bi-file-earmark-text"></i> Acción de Personal
                            </a>
                            <button class="btn btn-ghost" id="btn-exportar-directorio" onclick="showToast('Exportando directorio de funcionarios...', \'info\')">
                                <i class="bi bi-file-earmark-arrow-down"></i> Exportar
                            </button>
                        </div>
                    </div>
                    <?php
                    $total     = count($empleados);
                    // La vista view_th_iddatosempledo devuelve 'estado' como número: 1=Activo, 0=Inactivo
                    $activos   = count(array_filter($empleados, fn($e) => (int)($e['estado'] ?? 0) === 1));
                    $permisos  = 0; // La vista actual no distingue permisos; se expande en siguiente versión
                    $inactivos = count(array_filter($empleados, fn($e) => (int)($e['estado'] ?? 1) === 0));
                    ?>
                    <div class="metrics">
                        <div class="metric-card"><div class="metric-label">Registrados</div><div class="metric-value"><?= $total ?></div><div class="metric-foot">Directorio APM</div></div>
                        <div class="metric-card"><div class="metric-label">Activos</div><div class="metric-value"><?= $activos ?></div><div class="metric-foot">En funciones hoy</div></div>
                        <div class="metric-card"><div class="metric-label">Permisos</div><div class="metric-value"><?= $permisos ?></div><div class="metric-foot">Con permisos vigentes</div></div>
                        <div class="metric-card"><div class="metric-label">Inactivos</div><div class="metric-value"><?= $inactivos ?></div><div class="metric-foot">Historial y desvinculados</div></div>
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
                            <input type="text" id="searchInput" oninput="filterTable()" placeholder="Buscar por cedula, nombre o cargo...">
                        </div>
                        <div class="filter-group">
                            <select id="departmentFilter" onchange="filterTable()">
                                <option value="">Todas las áreas</option>
                                <?php
                                // Leer la columna 'direccion_area' que devuelve view_th_iddatosempledo
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
                                // Mapeo exacto con la vista view_th_iddatosempledo
                                $nombre   = isset($emp['nombres'], $emp['apellidos'])
                                            ? trim($emp['nombres'] . ' ' . $emp['apellidos'])
                                            : ($emp['apellidos_nombres'] ?? $emp['nombres'] ?? '');
                                $cedula   = $emp['cedula']              ?? $emp['cedula_pasaporte']  ?? '';
                                $depto    = $emp['direccion_area']      ?? $emp['departamento']      ?? '';
                                $cargo    = $emp['cargo']               ?? $emp['denominacion_puesto'] ?? '';
                                $id       = (int)($emp['id']            ?? $emp['empleado_id']       ?? 0);
                                $correo   = $emp['correo_institucional'] ?? $emp['correo']            ?? '';
                                $contrato = $emp['tipo_contrato']        ?? 'N/A';

                                // Traducir estado numérico (1=Activo, 0=Inactivo) a texto
                                $estado_num = (int)($emp['estado'] ?? 1);
                                $estado     = ($estado_num === 1) ? 'Activo' : 'Inactivo';

                                $statusClass = match($estado) {
                                    'Activo'  => 'status-active',
                                    'Permiso' => 'status-leave',
                                    default   => 'status-inactive'
                                };
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
                                            $fotoSrc  = (!empty($rutaFoto) && file_exists(ROOT . '/' . $rutaFoto))
                                                        ? BASE_URL . '/' . htmlspecialchars($rutaFoto)
                                                        : BASE_URL . '/public/img/default_avatar.png';
                                            ?>
                                            <div class="avatar avatar-foto">
                                                <img src="<?= $fotoSrc ?>"
                                                     alt="<?= htmlspecialchars(mb_substr($nombre, 0, 1)) ?>"
                                                     onerror="this.src='<?= BASE_URL ?>/public/img/default_avatar.png'">
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
                                    <td class="action-cell" style="white-space:nowrap;">
                                        <!-- Acción de Personal – Acción principal destacada -->
                                        <a href="<?= BASE_URL ?>/talento-humano/accion-personal?id=<?= $id ?>&cedula=<?= urlencode($cedula) ?>"
                                           class="btn btn-primary" title="Emitir Acción de Personal"
                                           style="padding:6px 10px; font-size:.8rem;">
                                            <i class="bi bi-file-earmark-text"></i> Acción
                                        </a>
                                        <!-- Ver historial laboral -->
                                        <a href="<?= BASE_URL ?>/talento-humano/reporte?empleado_id=<?= $id ?>"
                                           class="btn btn-outline" title="Ver historial laboral"
                                           style="padding:6px 10px; font-size:.8rem;">
                                            <i class="bi bi-clock-history"></i>
                                        </a>
                                        <!-- Editar expediente -->
                                        <a href="<?= BASE_URL ?>/talento-humano/empleado/editar?id=<?= $id ?>"
                                           class="btn btn-outline" title="Editar expediente"
                                           style="padding:6px 10px; font-size:.8rem;">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <!-- Imprimir Ficha PDF -->
                                        <a href="<?= BASE_URL ?>/talento-humano/empleado/imprimir-ficha?id=<?= $id ?>"
                                           class="btn btn-outline" title="Imprimir Ficha PDF"
                                           target="_blank"
                                           style="padding:6px 10px; font-size:.8rem; color:#c0392b; border-color:#c0392b;">
                                            <i class="bi bi-file-pdf"></i>
                                        </a>
                                        <!-- Eliminar -->
                                        <a href="<?= BASE_URL ?>/talento-humano/empleado/borrar?id=<?= $id ?>"
                                           class="btn btn-danger" title="Eliminar registro"
                                           style="padding:6px 10px; font-size:.8rem;"
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
                            <p>Ajuste los filtros de busqueda.</p>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </section>
</div>

<div id="toastContainer" class="toast-container"></div>

<?php if (!empty($_GET['msg'])): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        showToast(<?= json_encode(htmlspecialchars($_GET['msg'])) ?>, <?= ($_GET['ok'] ?? '0') === '1' ? "'success'" : "'error'" ?>);
    });
</script>
<?php endif; ?>

<script src="<?= BASE_URL ?>/public/js/layout_sidebar.js"></script>
<script src="<?= BASE_URL ?>/public/js/toast.js"></script>
<script src="<?= BASE_URL ?>/public/js/talento_humano.js"></script>
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
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('currentDate').textContent =
        new Date().toLocaleDateString('es-EC', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
});
</script>
</body>
</html>
