<?php
/* directorio.php – Vista: tabla de funcionarios con topbar, hero y filtros */
$puedeCrearEmpleado = Auth::can('empleados', 'crear');
$puedeAccion        = Auth::can('acciones', 'crear');
$puedeEditar        = Auth::can('empleados', 'editar');
$puedeMover         = Auth::can('movimientos', 'visualizar');
$puedeImprimir      = Auth::can('directorio', 'visualizar');
$puedeEliminar      = Auth::can('directorio', 'eliminar');
$modoMovimiento     = (string)($_GET['modo'] ?? '') === 'movimiento';
$mostrarMovimiento  = $modoMovimiento && $puedeMover;
$empleadosListado   = $modoMovimiento
    ? array_values(array_filter($empleados, static fn(array $empleado): bool => (int)($empleado['estado'] ?? 0) === 1))
    : $empleados;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directorio de Funcionarios | Talento Humano APM</title>
    <meta name="description" content="Directorio de servidores públicos de la Autoridad Portuaria de Manta.">
    <?php require ROOT . '/shared/head_assets.php'; ?>
</head>
<body class="directory-page<?= $modoMovimiento ? ' directory-movement-mode' : '' ?>">
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()" title="Abrir menú lateral" aria-label="Abrir menú lateral">
    <i class="bi bi-layout-sidebar"></i>
</button>

<div class="app">
    <?php require_once ROOT . '/shared/menu.php'; ?>

    <section class="content">
        <?php $topbarShowSearch=true; require ROOT.'/shared/topbar.php'; ?>

        <main class="main">
            <div class="content-shell">
                <!-- HERO / MÉTRICAS -->
                <section class="hero">
                    <div>
                        <div class="hero-kicker"><?= $modoMovimiento ? 'Gestión interna · Sin documento legal' : 'Centro de Mando · Expedientes APM' ?></div>
                        <h2><?= $modoMovimiento ? 'Movimiento interno de personal' : 'Directorio de Personal' ?></h2>
                        <p><?= $modoMovimiento
                            ? 'Seleccione funcionarios activos para trasladarlos individualmente o en grupo, sin generar una Acción de Personal.'
                            : 'Tabla maestra de todos los funcionarios. Busque, filtre y abra el expediente detallado de cada servidor.' ?></p>
                        <?php if(!$modoMovimiento): ?><div class="hero-actions">
                            <?php if($puedeCrearEmpleado): ?><a href="<?= BASE_URL ?>/talento-humano/empleado/crear" class="btn btn-primary" id="btn-nuevo-expediente">
                                <i class="bi bi-person-plus-fill"></i> + Nuevo Expediente
                            </a><?php endif; ?>
                            <?php if($puedeAccion): ?><a href="<?= BASE_URL ?>/talento-humano/accion-personal" class="btn btn-outline" id="btn-accion-general">
                                <i class="bi bi-file-earmark-text"></i> Acción de Personal
                            </a><?php endif; ?>
                            <?php if(Auth::can('directorio','editar')): ?><a href="<?= BASE_URL ?>/talento-humano/empleado/exportar"
                               class="btn btn-ghost" id="btn-exportar-directorio"
                               title="Descargar directorio completo en formato CSV">
                                <i class="bi bi-file-earmark-arrow-down"></i> Exportar CSV
                            </a><?php endif; ?>
                        </div><?php endif; ?>
                    </div>
                    <?php
                    $total     = count($empleadosListado);
                    // La vista view_th_iddatosempledo devuelve 'estado' como número: 1=Activo, 0=Inactivo
                    $activos   = count(array_filter($empleadosListado, fn($e) => (int)($e['estado'] ?? 0) === 1));
                    $permisos  = 0; // La vista actual no distingue permisos; se expande en siguiente versión
                    $inactivos = count(array_filter($empleadosListado, fn($e) => (int)($e['estado'] ?? 1) === 0));
                    ?>
                    <div class="metrics">
                        <?php if($modoMovimiento): ?>
                        <div class="metric-card"><div class="metric-label">Disponibles</div><div class="metric-value"><?= $activos ?></div><div class="metric-foot">Funcionarios activos</div></div>
                        <div class="metric-card"><div class="metric-label">Modalidad</div><div class="metric-value"><i class="bi bi-arrow-left-right"></i></div><div class="metric-foot">Individual o grupal</div></div>
                        <?php else: ?>
                        <div class="metric-card"><div class="metric-label">Registrados</div><div class="metric-value"><?= $total ?></div><div class="metric-foot">Directorio APM</div></div>
                        <div class="metric-card"><div class="metric-label">Activos</div><div class="metric-value"><?= $activos ?></div><div class="metric-foot">En funciones hoy</div></div>
                        <div class="metric-card"><div class="metric-label">Permisos</div><div class="metric-value"><?= $permisos ?></div><div class="metric-foot">Con permisos vigentes</div></div>
                        <div class="metric-card"><div class="metric-label">Inactivos</div><div class="metric-value"><?= $inactivos ?></div><div class="metric-foot">Historial y desvinculados</div></div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- TABLA DIRECTORIO -->
                <section class="card table-card">
                    <div class="card-header">
                        <div>
                            <h3><?= $modoMovimiento ? 'Funcionarios disponibles para movimiento' : 'Directorio de funcionarios' ?></h3>
                            <p><?= $modoMovimiento ? 'Seleccione uno para traslado individual o dos o más para traslado grupal.' : 'Consulte, filtre y gestione los expedientes autorizados.' ?></p>
                        </div>
                        <div class="chip">
                            <i class="bi bi-lightning-charge"></i>
                            Resultados: <span id="resultCount"><?= $total ?></span>
                        </div>
                    </div>
                    <div class="toolbar">
                        <div class="input search-input">
                            <i class="bi bi-search"></i>
                            <input type="search" id="searchInput" oninput="programarBusqueda()"
                                   placeholder="Búsqueda de personal"
                                   aria-label="Búsqueda compuesta de funcionarios">
                        </div>
                        <div class="filter-group">
                            <select id="departmentFilter" onchange="programarBusqueda()">
                                <option value="">Todas las áreas</option>
                                <?php
                                // Leer la columna 'direccion_area' que devuelve view_th_iddatosempledo
                                $depts = array_unique(array_column($empleadosListado, 'direccion_area'));
                                sort($depts);
                                foreach ($depts as $d):
                                    if (!empty($d)): ?>
                                    <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                            <select id="contratoFilter" onchange="programarBusqueda()">
                                <option value="">Tipo de contrato</option>
                                <option value="Nombramiento">Nombramiento</option>
                                <option value="Contrato">Contrato</option>
                            </select>
                            <select id="statusFilter" onchange="programarBusqueda()" <?= $modoMovimiento ? 'disabled aria-label="Estado fijo: Activo"' : '' ?>>
                                <?php if(!$modoMovimiento): ?>
                                <option value="">Estado general</option>
                                <option value="Activo">Activo</option>
                                <option value="Permiso">Permiso</option>
                                <option value="Inactivo">Inactivo</option>
                                <?php else: ?><option value="Activo" selected>Solo personal activo</option><?php endif; ?>
                            </select>
                            <button class="btn btn-outline" onclick="resetFilters()" id="btn-limpiar-filtros">
                                <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                            </button>
                        </div>
                    </div>
                    <?php if($mostrarMovimiento): ?><div class="selection-toolbar hidden" id="selectionToolbar" aria-live="polite">
                        <div>
                            <strong><span id="seleccionCount">0</span> seleccionados</strong>
                            <span id="selectionHint">Seleccione al menos dos funcionarios para el movimiento grupal.</span>
                        </div>
                        <button class="btn btn-primary hidden" type="button" id="btnMovimientoGrupal" onclick="abrirMovimientoGrupal()">
                            <i class="bi bi-people-fill"></i> Registrar movimiento grupal
                        </button>
                    </div><?php endif; ?>
                    <div class="table-wrap">
                        <table id="employeeTable">
                            <thead>
                                <tr>
                                    <?php if($mostrarMovimiento): ?><th class="selection-heading"><input type="checkbox" id="seleccionarVisibles" onchange="seleccionarFilasVisibles(this.checked)" aria-label="Seleccionar empleados visibles"></th><?php endif; ?>
                                    <th style="width:58px">N.º</th>
                                    <th>Cédula</th>
                                    <th>Funcionario</th>
                                    <th>Área / Departamento</th>
                                    <th>Cargo / Contrato</th>
                                    <th class="status-heading">Estado</th>
                                    <th class="actions-heading<?= $modoMovimiento ? ' actions-heading--movement' : '' ?>"><?= $modoMovimiento ? 'Mover' : 'Acciones' ?></th>
                                </tr>
                            </thead>
                            <tbody id="employeeTableBody">
                            <?php if (empty($empleadosListado)): ?>
                                <tr><td colspan="<?= $mostrarMovimiento ? 8 : 7 ?>" style="text-align:center;padding:24px;">No hay funcionarios disponibles.</td></tr>
                            <?php else: foreach ($empleadosListado as $i => $emp):
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
                                $estadoFecha = trim((string)($emp['estado_fecha_efectiva'] ?? $emp['fecha_salida'] ?? $emp['fecha_ingreso'] ?? ''));
                                $estadoMotivo = trim((string)($emp['estado_motivo'] ?? ($estado_num === 1 ? 'Relación laboral vigente' : 'Registro inactivo')));
                                $estadoDetalle = $estadoMotivo . ($estadoFecha !== '' ? ' · Fecha efectiva: ' . substr($estadoFecha,0,10) : '');

                                $statusClass = ['Activo' => 'status-active', 'Permiso' => 'status-leave'][$estado] ?? 'status-inactive';
                            ?>
                                <tr class="table-row" style="opacity:1;animation:none"
                                    data-id="<?= $id ?>"
                                    data-nombre="<?= strtolower(htmlspecialchars($nombre)) ?>"
                                    data-cedula="<?= htmlspecialchars($cedula) ?>"
                                    data-dept="<?= strtolower(htmlspecialchars($depto)) ?>"
                                    data-cargo="<?= strtolower(htmlspecialchars($cargo)) ?>"
                                    data-contrato="<?= htmlspecialchars($contrato) ?>"
                                    data-estado="<?= htmlspecialchars($estado) ?>">
                                    <?php if($mostrarMovimiento): ?><td class="selection-cell"><input type="checkbox" class="empleado-check" value="<?= $id ?>" onchange="actualizarSeleccion()" aria-label="Seleccionar <?= htmlspecialchars($nombre) ?>"></td><?php endif; ?>
                                    <td><?= $i + 1 ?></td>
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
                                    <td class="status-cell"><span class="status-pill <?= $statusClass ?>" title="<?= htmlspecialchars($estadoDetalle) ?>"><?= htmlspecialchars($estado) ?></span></td>
                                    <td class="action-cell<?= $modoMovimiento ? ' action-cell--movement' : '' ?>">
                                      <div class="row-actions">
                                        <?php $accionesVisibles = 0; ?>
                                        <?php if($modoMovimiento && $puedeMover): $accionesVisibles++; ?>
                                        <a href="<?= BASE_URL ?>/talento-humano/empleado/movimiento?id=<?= $id ?>"
                                           title="Mover individualmente sin Acción de Personal"
                                           aria-label="Mover individualmente a <?= htmlspecialchars($nombre) ?>"
                                           class="btn btn-primary row-action-movement-primary">
                                            <i class="bi bi-arrow-left-right"></i><span>Mover</span>
                                        </a>
                                        <?php endif; ?>
                                        <?php if(!$modoMovimiento): ?>
                                        <!-- 1: Acción de Personal -->
                                        <?php if($puedeAccion): $accionesVisibles++; ?>
                                        <a href="<?= BASE_URL ?>/talento-humano/accion-personal?id=<?= $id ?>&cedula=<?= urlencode($cedula) ?>"
                                           class="btn btn-primary row-action-primary" title="Acción de Personal" aria-label="Crear Acción de Personal para <?= htmlspecialchars($nombre) ?>">
                                            <i class="bi bi-file-earmark-text"></i><span>Acción</span>
                                        </a><?php endif; ?>
                                        <!-- 2: Historial laboral -->
                                        <?php if($puedeImprimir): $accionesVisibles++; ?>
                                        <a href="<?= BASE_URL ?>/talento-humano/reporte?empleado_id=<?= $id ?>"
                                           title="Ver historial laboral" aria-label="Ver historial laboral de <?= htmlspecialchars($nombre) ?>" class="row-action-icon row-action-history">
                                            <i class="bi bi-clock-history"></i>
                                        </a><?php endif; ?>
                                        <!-- 3: Editar expediente -->
                                        <?php if($puedeEditar): $accionesVisibles++; ?>
                                        <a href="<?= BASE_URL ?>/talento-humano/empleado/editar?id=<?= $id ?>"
                                           title="Editar expediente" aria-label="Editar expediente de <?= htmlspecialchars($nombre) ?>" class="row-action-icon row-action-edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a><?php endif; ?>
                                        <!-- 4: Imprimir Ficha -->
                                        <?php if($puedeImprimir): $accionesVisibles++; ?>
                                        <a href="<?= BASE_URL ?>/talento-humano/empleado/imprimir-ficha?id=<?= $id ?>"
                                           title="Imprimir Formulario Principal completo (PDF)" target="_blank"
                                           aria-label="Imprimir ficha completa de <?= htmlspecialchars($nombre) ?>"
                                           class="row-action-icon row-action-print">
                                            <i class="bi bi-printer-fill"></i>
                                        </a><?php endif; ?>
                                        <!-- 5: Baja lógica segura -->
                                        <?php if($puedeEliminar): $accionesVisibles++; ?>
                                        <?php if($estado_num === 1): ?>
                                        <form method="post" action="<?= BASE_URL ?>/talento-humano/empleado/eliminar"
                                              class="row-action-form"
                                              onsubmit="return confirm('¿Dar de baja a <?= htmlspecialchars(addslashes($nombre)) ?>?');">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
                                            <input type="hidden" name="id" value="<?= $id ?>">
                                            <button type="submit" title="Dar de baja" aria-label="Dar de baja a <?= htmlspecialchars($nombre) ?>" class="row-action-icon row-action-delete">
                                                <i class="bi bi-person-x-fill"></i>
                                            </button>
                                        </form>
                                        <?php else: ?><span class="row-action-icon row-action-delete is-disabled" title="El funcionario ya se encuentra inactivo"><i class="bi bi-person-check"></i></span><?php endif; ?>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if($accionesVisibles === 0): ?><span aria-label="Sin acciones disponibles">—</span><?php endif; ?>
                                      </div>
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
                    <div class="table-pagination" id="tablePagination">
                        <label>Mostrar
                            <select id="pageSize" onchange="cambiarTamanoPagina()">
                                <option value="25">25</option>
                                <option value="50" selected>50</option>
                                <option value="100">100</option>
                            </select>
                            registros
                        </label>
                        <span id="pageSummary">Página 1 de 1</span>
                        <div class="pagination-actions">
                            <button type="button" class="btn btn-outline" id="prevPage" onclick="cambiarPagina(-1)" aria-label="Página anterior"><i class="bi bi-chevron-left"></i></button>
                            <button type="button" class="btn btn-outline" id="nextPage" onclick="cambiarPagina(1)" aria-label="Página siguiente"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </section>
</div>


<?php if (!empty($_GET['msg'])): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        showToast(<?= json_encode(htmlspecialchars($_GET['msg'])) ?>, <?= ($_GET['ok'] ?? '0') === '1' ? "'success'" : "'error'" ?>);
    });
</script>
<?php endif; ?>

<script>
/* Filtros de tabla en el lado del cliente */
let searchFrame=null;
let employeeRows=[];
let filteredRows=[];
let currentPage=1;
const movementMode=<?= $modoMovimiento ? 'true' : 'false' ?>;
const normalizeSearchText = value => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('es')
    .replace(/[^a-z0-9]+/g, ' ')
    .trim();
function renderPage() {
    const pageSize=Math.max(1,Number(document.getElementById('pageSize')?.value||50));
    const totalPages=Math.max(1,Math.ceil(filteredRows.length/pageSize));
    currentPage=Math.min(Math.max(1,currentPage),totalPages);
    const start=(currentPage-1)*pageSize;
    const pageRows=new Set(filteredRows.slice(start,start+pageSize));
    employeeRows.forEach(tr=>{tr.style.display=pageRows.has(tr)?'':'none';});
    document.getElementById('pageSummary').textContent=filteredRows.length
        ? `Registros ${start+1}–${Math.min(start+pageSize,filteredRows.length)} de ${filteredRows.length}`
        : 'Sin registros';
    document.getElementById('prevPage').disabled=currentPage<=1;
    document.getElementById('nextPage').disabled=currentPage>=totalPages;
    document.getElementById('tablePagination').classList.toggle('hidden',filteredRows.length===0);
    const selectAll=document.getElementById('seleccionarVisibles');
    if(selectAll){selectAll.checked=false;selectAll.indeterminate=false;}
}
function filterTable(resetPage=true) {
    const tokens   = normalizeSearchText(document.getElementById('searchInput').value).split(/\s+/).filter(Boolean);
    const dept     = normalizeSearchText(document.getElementById('departmentFilter').value);
    const contrato = normalizeSearchText(document.getElementById('contratoFilter').value);
    const est      = document.getElementById('statusFilter').value;
    filteredRows=employeeRows.filter(tr => {
        const matchQ = tokens.every(token => tr.dataset.searchIndex.includes(token));
        const matchD = !dept     || tr.dataset.deptIndex.includes(dept);
        const matchC = !contrato || tr.dataset.contractIndex.includes(contrato);
        const matchE = !est      || tr.dataset.estado === est;
        return matchQ && matchD && matchC && matchE;
    });
    if(resetPage)currentPage=1;
    document.getElementById('resultCount').textContent = filteredRows.length;
    document.getElementById('noDataMessage').classList.toggle('hidden', filteredRows.length > 0);
    renderPage();
}
function programarBusqueda(){
    if(searchFrame!==null)cancelAnimationFrame(searchFrame);
    searchFrame=requestAnimationFrame(()=>{searchFrame=null;filterTable(true);});
}
function resetFilters() {
    if(searchFrame!==null)cancelAnimationFrame(searchFrame);
    document.getElementById('searchInput').value        = '';
    document.getElementById('departmentFilter').value   = '';
    document.getElementById('contratoFilter').value     = '';
    document.getElementById('statusFilter').value       = movementMode ? 'Activo' : '';
    const globalSearch=document.getElementById('globalSearch');
    if(globalSearch)globalSearch.value='';
    filterTable(true);
}
function cambiarTamanoPagina(){currentPage=1;renderPage();}
function cambiarPagina(delta){currentPage+=delta;renderPage();document.querySelector('.table-card')?.scrollIntoView({behavior:'smooth',block:'start'});}
function actualizarSeleccion(){
    const seleccion=[...document.querySelectorAll('.empleado-check:checked')];
    const count=document.getElementById('seleccionCount');
    const button=document.getElementById('btnMovimientoGrupal');
    const toolbar=document.getElementById('selectionToolbar');
    const hint=document.getElementById('selectionHint');
    if(count)count.textContent=seleccion.length;
    if(button)button.classList.toggle('hidden',seleccion.length<2);
    if(hint)hint.textContent=seleccion.length<2
        ? 'Seleccione al menos otro funcionario para habilitar el movimiento grupal.'
        : 'La selección está lista para registrarse como un solo movimiento.';
    if(toolbar)toolbar.classList.toggle('hidden',seleccion.length===0);
    const visibles=[...document.querySelectorAll('#employeeTableBody tr.table-row')].filter(tr=>tr.style.display!=='none').map(tr=>tr.querySelector('.empleado-check')).filter(c=>c&&!c.disabled);
    const selectAll=document.getElementById('seleccionarVisibles');
    if(selectAll){const marcados=visibles.filter(c=>c.checked).length;selectAll.checked=visibles.length>0&&marcados===visibles.length;selectAll.indeterminate=marcados>0&&marcados<visibles.length;}
}
function seleccionarFilasVisibles(marcar){
    document.querySelectorAll('#employeeTableBody tr.table-row').forEach(tr=>{const c=tr.querySelector('.empleado-check');if(c&&!c.disabled&&tr.style.display!=='none')c.checked=marcar;});actualizarSeleccion();
}
function abrirMovimientoGrupal(){
    const ids=[...document.querySelectorAll('.empleado-check:checked')].map(c=>c.value);
    if(ids.length<2){showToast?.('Seleccione al menos dos empleados activos.','error');return;}
    window.location.href=`<?= BASE_URL ?>/talento-humano/empleado/movimiento-grupal?ids=${encodeURIComponent(ids.join(','))}`;
}
document.addEventListener('DOMContentLoaded', () => {
    employeeRows=[...document.querySelectorAll('#employeeTableBody tr.table-row')];
    employeeRows.forEach(tr=>{
        tr.dataset.searchIndex=normalizeSearchText(`${tr.dataset.nombre} ${tr.dataset.cedula} ${tr.dataset.dept} ${tr.dataset.cargo} ${tr.dataset.contrato}`);
        tr.dataset.deptIndex=normalizeSearchText(tr.dataset.dept);
        tr.dataset.contractIndex=normalizeSearchText(tr.dataset.contrato);
    });
    document.getElementById('currentDate').textContent =
        new Date().toLocaleDateString('es-EC', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    const paramsIniciales=new URLSearchParams(window.location.search);
    const estadoInicial = paramsIniciales.get('estado');
    const consultaInicial=paramsIniciales.get('q')||'';
    if(consultaInicial){document.getElementById('searchInput').value=consultaInicial;const global=document.getElementById('globalSearch');if(global)global.value=consultaInicial;}
    if (estadoInicial && [...document.getElementById('statusFilter').options].some(o => o.value === estadoInicial)) {
        document.getElementById('statusFilter').value = estadoInicial;
    }
    if(movementMode)document.getElementById('statusFilter').value='Activo';
    const global=document.getElementById('globalSearch');
    global?.addEventListener('input',()=>{document.getElementById('searchInput').value=global.value;programarBusqueda();});
    filterTable(true);
    if(movementMode)showToast?.('Seleccione un funcionario para moverlo o dos o más para un movimiento grupal.','info');
});
</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
