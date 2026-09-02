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
                        <h2><?= $modoMovimiento ? 'Movimiento interno de personal' : 'Nómina de Personal' ?></h2>
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
                        <table id="employeeTable" data-apm-datatable data-dt-page-length="50"
                               data-dt-order='[[<?= $mostrarMovimiento ? 3 : 2 ?>,"asc"]]'
                               data-dt-order-disabled="<?= $mostrarMovimiento ? '0,7' : '6' ?>"
                               data-dt-searching="true" data-dt-search-control="false"
                               data-dt-empty="No hay funcionarios disponibles.">
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
                                <tr data-dt-empty><td colspan="<?= $mostrarMovimiento ? 8 : 7 ?>" style="text-align:center;padding:24px;">No hay funcionarios disponibles.</td></tr>
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
                                    <?php if($mostrarMovimiento): ?><td class="selection-cell"><input type="checkbox" class="empleado-check" value="<?= $id ?>" onchange="registrarSeleccion(this)" aria-label="Seleccionar <?= htmlspecialchars($nombre) ?>"></td><?php endif; ?>
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
                </section>
            </div>
        </main>
    </section>
</div>

<?php $nuevoCodigoId = filter_input(INPUT_GET, 'nuevo_codigo_id', FILTER_VALIDATE_INT) ?: 0; ?>
<?php if ($nuevoCodigoId > 0): ?>
<div class="labor-success-overlay" id="laborSuccessOverlay" role="dialog" aria-modal="true" aria-labelledby="laborSuccessTitle">
    <div class="labor-success-dialog">
        <div class="labor-success-icon"><i class="bi bi-file-earmark-check"></i></div>
        <div>
            <span class="labor-success-kicker">Expediente registrado</span>
            <h3 id="laborSuccessTitle">El funcionario pertenece al Código del Trabajo</h3>
            <p>El registro inicial quedó guardado. Puede abrir ahora el Formulario Abreviado Laboral, revisar los datos y generar su impresión.</p>
        </div>
        <div class="labor-success-actions">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('laborSuccessOverlay').remove()">Continuar en Nómina</button>
            <a class="btn btn-primary" href="<?= BASE_URL ?>/talento-humano/accion-personal?id=<?= (int)$nuevoCodigoId ?>&amp;tipo=INGRESO&amp;origen=alta">
                <i class="bi bi-box-arrow-up-right"></i> Abrir Formulario Abreviado
            </a>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if (!empty($_GET['msg'])): ?>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        showToast(<?= json_encode(htmlspecialchars($_GET['msg'])) ?>, <?= ($_GET['ok'] ?? '0') === '1' ? "'success'" : "'error'" ?>);
    });
</script>
<?php endif; ?>

<script>
/* DataTables conserva los filtros institucionales y los modos del directorio. */
let searchFrame=null;
let directoryTable=null;
const selectedEmployeeIds=new Set();
const movementMode=<?= $modoMovimiento ? 'true' : 'false' ?>;
function filterTable() {
    if(!directoryTable)return;
    const query    = document.getElementById('searchInput').value.trim();
    const dept     = document.getElementById('departmentFilter').value;
    const contrato = document.getElementById('contratoFilter').value;
    const est      = document.getElementById('statusFilter').value;
    const offset=movementMode?1:0;
    directoryTable.search(query);
    directoryTable.column(3+offset).search(dept);
    directoryTable.column(4+offset).search(contrato);
    directoryTable.column(5+offset).search(est);
    directoryTable.page('first').draw();
}
function programarBusqueda(){
    if(searchFrame!==null)cancelAnimationFrame(searchFrame);
    searchFrame=requestAnimationFrame(()=>{searchFrame=null;filterTable();});
}
function resetFilters() {
    if(searchFrame!==null)cancelAnimationFrame(searchFrame);
    document.getElementById('searchInput').value        = '';
    document.getElementById('departmentFilter').value   = '';
    document.getElementById('contratoFilter').value     = '';
    document.getElementById('statusFilter').value       = movementMode ? 'Activo' : '';
    const globalSearch=document.getElementById('globalSearch');
    if(globalSearch)globalSearch.value='';
    filterTable();
}
function actualizarSeleccion(){
    const seleccion=[...selectedEmployeeIds];
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
    const visibles=directoryTable
        ? directoryTable.rows({page:'current',search:'applied'}).nodes().toArray().map(tr=>tr.querySelector('.empleado-check')).filter(c=>c&&!c.disabled)
        : [];
    const selectAll=document.getElementById('seleccionarVisibles');
    if(selectAll){const marcados=visibles.filter(c=>c.checked).length;selectAll.checked=visibles.length>0&&marcados===visibles.length;selectAll.indeterminate=marcados>0&&marcados<visibles.length;}
}
function registrarSeleccion(input){
    const id=String(input.value);
    if(input.checked)selectedEmployeeIds.add(id);else selectedEmployeeIds.delete(id);
    actualizarSeleccion();
}
function seleccionarFilasVisibles(marcar){
    if(!directoryTable)return;
    directoryTable.rows({page:'current',search:'applied'}).nodes().each(tr=>{const c=tr.querySelector('.empleado-check');if(c&&!c.disabled){c.checked=marcar;if(marcar)selectedEmployeeIds.add(String(c.value));else selectedEmployeeIds.delete(String(c.value));}});
    actualizarSeleccion();
}
function abrirMovimientoGrupal(){
    const ids=[...selectedEmployeeIds];
    if(ids.length<2){showToast?.('Seleccione al menos dos empleados activos.','error');return;}
    window.location.href=`<?= BASE_URL ?>/talento-humano/empleado/movimiento-grupal?ids=${encodeURIComponent(ids.join(','))}`;
}
document.getElementById('employeeTable')?.addEventListener('apm:datatable-ready',event=>{
    directoryTable=event.detail.instance;
    directoryTable.on('draw',()=>{
        const info=directoryTable.page.info();
        document.getElementById('resultCount').textContent=info.recordsDisplay;
        document.getElementById('noDataMessage').classList.add('hidden');
        directoryTable.rows({page:'current',search:'applied'}).nodes().each(tr=>{const c=tr.querySelector('.empleado-check');if(c)c.checked=selectedEmployeeIds.has(String(c.value));});
        actualizarSeleccion();
    });
    filterTable();
});
document.addEventListener('DOMContentLoaded', () => {
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
    if(movementMode)showToast?.('Seleccione un funcionario para moverlo o dos o más para un movimiento grupal.','info');
});
</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
