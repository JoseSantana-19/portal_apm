<?php
/**
 * Vista: Listado de visitas.
 * Reemplaza el HTML de bit_listado_visitas.php
 * Variables: $visitas, $empresas, $funcionarios, $destinos, $motivos,
 * $puedeRegistrarIngreso, $puedeRegistrarSalida, $puedeEditarVisita, $mensaje
 *
 * NOTA: js/listado_visitas.js aún apunta a los endpoints AJAX originales
 * (bit_registrar_salida.php, bit_actualizar_visita.php, bit_actualizar_horas.php).
 * Esos archivos siguen funcionando en paralelo. Cuando migremos ese JS,
 * lo apuntaremos a bitacoras/visita/registrarSalida, etc.
 */
$soloLectura = (!$puedeRegistrarIngreso && !$puedeRegistrarSalida && !$puedeEditarVisita);
?>

<style>
    /* 🛠️ SISTEMA DE ESTILOS UNIFICADO - CORRIGE EL DESFASE DE DATATABLES */
    #tablaVisitas {
        table-layout: fixed !important;
        width: 100% !important;
    }
    /* La tabla tiene 12 columnas — table-layout:fixed + width:100% (arriba)
       arregla el desfase header/body en desktop, pero en pantallas angostas
       fuerza esas 12 columnas a caber en ~300px: cada th se comprime hasta
       partir el texto letra por letra (encontrado en vivo, sesión real,
       390px). El wrapper .table-responsive ya sabe scrollear horizontal —
       solo hace falta devolverle a la tabla su ancho natural para que haya
       algo que scrollear. */
    @media (max-width: 992px) {
        #tablaVisitas {
            table-layout: auto !important;
            width: auto !important;
            min-width: 1100px;
        }
    }
    /* Contenedor DataTables: nunca más ancho que el main (sidebar push) */
    .apm-listado-visitas-page {
        min-width: 0;
        max-width: 100%;
        box-sizing: border-box;
    }
    .apm-listado-visitas-page .card,
    .apm-listado-visitas-page .card-body {
        min-width: 0;
        max-width: 100%;
    }
    .apm-tabla-visitas-wrap {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    .apm-tabla-visitas-wrap .dataTables_wrapper {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box;
    }
    .apm-tabla-visitas-wrap .dataTables_wrapper .dataTables_scroll,
    .apm-tabla-visitas-wrap .dataTables_wrapper .dataTables_scrollHead,
    .apm-tabla-visitas-wrap .dataTables_wrapper .dataTables_scrollBody {
        max-width: 100%;
    }
    .apm-tabla-visitas-wrap .dataTables_wrapper .row {
        margin-left: 0;
        margin-right: 0;
        max-width: 100%;
    }
    .apm-tabla-visitas-wrap table.dataTable {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box;
    }
    #tablaVisitas tbody td.td-truncate {
        min-width: 0;
        vertical-align: middle;
        height: 2.75rem;
        max-height: 2.75rem;
        overflow: hidden;
        padding-top: 0.35rem;
        padding-bottom: 0.35rem;
        line-height: 1.35;
        word-break: normal;
        overflow-wrap: normal;
    }
    #tablaVisitas td.td-truncate .apm-truncate-row {
        min-width: 0;
        max-height: 1.35em;
    }
    #tablaVisitas td.td-truncate .apm-truncate-text {
        flex: 1 1 auto;
        min-width: 0;
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-height: 1.35em;
    }
    #tablaVisitas td.td-truncate .apm-btn-expand {
        line-height: 1;
        color: var(--bs-primary);
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1rem;
    }
    #tablaVisitas td.td-truncate .apm-btn-expand:hover {
        color: var(--bs-link-hover-color, #0a58ca);
    }
    /* Encabezados: espacio a la derecha para flechas de ordenación de DataTables */
    #tablaVisitas thead th {
        white-space: normal;
        vertical-align: bottom;
        line-height: 1.35;
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        padding: 0.5rem 1.35rem 0.5rem 0.5rem;
        min-height: 2.5rem;
        color: var(--bs-secondary-color, #495057);
    }
    #tablaVisitas thead th.apm-th-nivel {
        white-space: nowrap !important; 
        min-width: 140px;               
        line-height: 1.2;
    }
    #tablaVisitas thead th:first-child,
    #tablaVisitas tbody td.apm-col-fecha {
        min-width: 105px;
    }
    #tablaVisitas tbody td.apm-col-fecha {
        white-space: nowrap;
        vertical-align: middle;
        height: 2.75rem;
        max-height: 2.75rem;
        padding: 0.35rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.35;
        box-sizing: border-box;
        overflow: visible;
    }
    #tablaVisitas tbody td.apm-col-fecha .apm-fecha-text {
        white-space: nowrap;
        display: inline-block;
        max-width: none;
    }
    #tablaVisitas tbody td {
        vertical-align: middle;
        font-size: 0.875rem;
        line-height: 1.35;
        padding: 0.35rem 0.5rem;
        height: 2.75rem;
        max-height: 2.75rem;
        overflow: hidden;
        word-break: normal;
        overflow-wrap: normal;
        box-sizing: border-box;
    }
    #tablaVisitas tbody td:last-child {
        white-space: nowrap;
        vertical-align: middle;
        width: 1%;
        max-width: none;
        overflow: visible;
    }
    #tablaVisitas .apm-acciones-group {
        gap: 0.25rem;
        flex-wrap: nowrap;
        justify-content: center;
    }
    #tablaVisitas .apm-acciones-group .btn {
        padding: 0.2rem 0.45rem;
        line-height: 1;
    }
    .apm-tabla-visitas-wrap {
        padding: 0.5rem 0.25rem;
        margin: -0.2rem;
    }
    #tablaVisitas.table-hover > tbody > tr {
        transition: box-shadow 0.22s ease, background-color 0.22s ease;
    }
    #tablaVisitas.table-hover > tbody > tr:hover > td {
        font-size: 0.875rem;
        line-height: 1.35;
        padding: 0.35rem 0.5rem;
        height: 2.75rem;
        max-height: 2.75rem;
    }
    #tablaVisitas.table-hover > tbody > tr:hover {
        position: relative;
        z-index: 5;
        box-shadow: 0 0.35rem 1rem rgba(13, 110, 253, 0.12);
        background-color: #fff;
    }
    /* Popovers del listado: panel claro estilo dashboard */
    .popover.apm-popover-dashboard {
        z-index: 1085;
        max-width: min(96vw, 28rem);
        --bs-popover-bg: #fff;
        --bs-popover-border-color: rgba(13, 110, 253, 0.22);
        --bs-popover-body-padding-x: 0.65rem;
        --bs-popover-body-padding-y: 0.5rem;
        --bs-popover-header-padding-x: 0.65rem;
        --bs-popover-header-padding-y: 0.35rem;
        box-shadow: 0 0.35rem 1.25rem rgba(13, 110, 253, 0.12);
        border: 1px solid rgba(13, 110, 253, 0.18);
    }
    .popover.apm-popover-dashboard .popover-header {
        margin-bottom: 0;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #0d47a1;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid rgba(13, 110, 253, 0.14);
    }
    .popover.apm-popover-dashboard .popover-body {
        color: #0c2d5c;
        font-size: 0.8125rem;
        line-height: 1.5;
        text-align: left;
        white-space: pre-wrap;
        word-break: break-word;
    }
    #modalDetalleTexto .modal-body {
        white-space: pre-wrap;
        word-break: break-word;
    }
    
    .apm-tabla-visitas-wrap .dataTables_wrapper .row:first-child {
        display: flex !important;
        flex-direction: row !important;
        justify-content: space-between !important;
        align-items: center !important;
        width: 100% !important;
        margin-bottom: 0.75rem !important;
    }

    .apm-tabla-visitas-wrap .dataTables_filter {
        text-align: right !important;
        display: flex !important;
        justify-content: flex-end !important;
    }

    .apm-tabla-visitas-wrap .dataTables_filter label {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.5rem !important;
    }

    .apm-tabla-visitas-wrap .dataTables_filter input {
        display: inline-block !important;
        width: auto !important;
    }
</style>

<div class="container-fluid my-4 apm-listado-visitas-page">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 text-primary mb-0">Listado de visitas / proveedores</h1>
            <p class="text-muted small mb-0">
                Registro histórico de ingresos y salidas al edificio administrativo.
            </p>
        </div>
        <div class="text-end">
            <?php if ($puedeRegistrarIngreso): ?>
                <a href="visitas/registrar" class="btn btn-sm btn-primary mb-1">+ Registrar nuevo ingreso</a><br>
            <?php else: ?>
                <button type="button" class="btn btn-sm btn-secondary mb-1" disabled>Solo lectura</button><br>
            <?php endif; ?>
            <span class="badge bg-secondary small">Fecha actual: <?php echo date("d/m/Y"); ?></span>
        </div>
    </div>

    <?php if ($mensaje !== ''): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($mensaje); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive apm-tabla-visitas-wrap">
                <table id="tablaVisitas" class="table table-hover align-top mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Nombre</th>
                            <th>Empresa / Personal</th>
                            <th>Cédula</th>
                            <th>Funcionario</th>
                            <th>Destino</th>
                            <th>Motivo</th>
                            <th class="text-center apm-th-nivel">Nivel de importancia</th>
                            <th>H. Entrada</th>
                            <th>H. Salida</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($visitas)): ?>
                            <?php foreach ($visitas as $row): ?>
                                <tr data-id-visita="<?php echo (int)$row['id_visita']; ?>">
                                    <td class="apm-col-fecha" data-order="<?php echo ($row['fecha_visita'] instanceof DateTime) ? htmlspecialchars($row['fecha_visita']->format('Y-m-d')) : ''; ?>">
                                        <?php
                                        if ($row['fecha_visita'] instanceof DateTime) {
                                            echo '<span class="apm-fecha-text">' . htmlspecialchars($row['fecha_visita']->format('d/m/Y')) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="td-truncate" data-apm-label="Nombre completo">
                                        <?php echo apm_listado_trunc_row($row['nombre_visitante'], 'Nombre visitante'); ?>
                                    </td>
                                    <td class="<?php echo ($row['tipo_visitante'] === 'Personal' || empty($row['empresa'])) ? '' : 'td-truncate'; ?>" data-apm-label="Empresa / Personal">
                                        <?php
                                        if ($row['tipo_visitante'] === 'Personal' || empty($row['empresa'])) {
                                            echo '<span class="badge bg-secondary">Personal</span>';
                                        } else {
                                            $labelEmpresa = $row['empresa'];
                                            if (!empty($row['empresa_ruc'])) {
                                                $labelEmpresa .= ' (' . $row['empresa_ruc'] . ')';
                                            }
                                            echo apm_listado_trunc_row($labelEmpresa, 'Empresa / Personal');
                                        }
                                        ?>
                                    </td>
                                    <td class="<?php echo ($row['nidentificacion'] === '9999999999') ? '' : 'td-truncate'; ?>" data-apm-label="Cédula">
                                        <?php
                                        if ($row['nidentificacion'] === '9999999999') {
                                            echo '<span class="badge bg-warning text-dark">Guest</span>';
                                        } else {
                                            echo apm_listado_trunc_row($row['nidentificacion'], 'Cédula');
                                        }
                                        ?>
                                    </td>
                                    <td class="td-truncate" data-apm-label="Funcionario">
                                        <?php echo apm_listado_trunc_row($row['funcionario'], 'Funcionario'); ?>
                                    </td>
                                    <td class="td-truncate" data-apm-label="Destino">
                                        <?php echo apm_listado_trunc_row($row['destino'], 'Destino'); ?>
                                    </td>
                                    <td class="td-truncate" data-apm-label="Motivo">
                                        <?php
                                        $detalleMotivo = trim((string)($row['detalle_motivo'] ?? ''));
                                        $textoModalMotivo = 'Motivo: ' . (string)$row['motivo'];
                                        if ($detalleMotivo !== '') {
                                            $textoModalMotivo .= "\n\nDetalle del motivo:\n" . $detalleMotivo;
                                        } else {
                                            $textoModalMotivo .= "\n\nDetalle del motivo:\n(No especificado)";
                                        }
                                        echo apm_listado_trunc_row($row['motivo'], 'Motivo y detalle', $textoModalMotivo, [
                                            'force_expand_button' => ($detalleMotivo !== ''),
                                        ]);
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $niv = isset($row['nivel_incidente']) ? trim((string)$row['nivel_incidente']) : '';
                                        $nivelNum = isset($row['nivel_importancia']) ? (int) $row['nivel_importancia'] : 0;
                                        if ($niv === '' && $nivelNum === 0) {
                                            echo '<span class="text-muted">—</span>';
                                        } elseif ($nivelNum === 3) {
                                            echo '<span class="badge bg-danger d-inline-flex align-items-center justify-content-center px-3 text-center">' . htmlspecialchars($niv !== '' ? $niv : 'Crítico') . '</span>';
                                        } elseif ($nivelNum === 2) {
                                            echo '<span class="badge bg-warning text-dark d-inline-flex align-items-center justify-content-center px-3 text-center">' . htmlspecialchars($niv !== '' ? $niv : 'Medio') . '</span>';
                                        } elseif ($nivelNum === 1) {
                                            echo '<span class="badge bg-secondary d-inline-flex align-items-center justify-content-center px-3 text-center">' . htmlspecialchars($niv !== '' ? $niv : 'Normal') . '</span>';
                                        } else {
                                            echo htmlspecialchars($niv !== '' ? $niv : '—');
                                        }
                                        ?>
                                    </td>
                                    <td class="td-truncate" data-order="<?php echo ($row['hora_entrada'] instanceof DateTime) ? htmlspecialchars($row['hora_entrada']->format('H:i')) : ''; ?>">
                                        <?php
                                        if ($row['hora_entrada'] instanceof DateTime) {
                                            echo apm_listado_trunc_row($row['hora_entrada']->format('H:i'), 'Hora de entrada');
                                        }
                                        ?>
                                    </td>
                                    <td class="td-truncate" data-apm-label="Hora de salida">
                                        <?php
                                        if ($row['hora_salida'] instanceof DateTime) {
                                            echo apm_listado_trunc_row($row['hora_salida']->format('H:i'), 'Hora de salida');
                                        } else {
                                            echo '<span class="text-muted text-nowrap">Pendiente</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($row['hora_salida'] === null): ?>
                                            <span class="badge bg-success">Dentro</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Finalizada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm apm-acciones-group d-inline-flex" role="group">
                                            <?php if ($row['nidentificacion'] === '9999999999' && apm_can_asignar_cedula_guest()): ?>
                                                <button type="button" class="btn btn-outline-secondary"
                                                        data-bs-toggle="modal" data-bs-target="#modalAsignarCedula"
                                                        data-id-persona="<?php echo (int)$row['id_persona']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($row['nombre_visitante']); ?>"
                                                        title="Asignar cédula" aria-label="Asignar cédula al visitante Guest">
                                                    <i class="bi bi-person-vcard" aria-hidden="true"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($row['hora_salida'] === null && $puedeRegistrarSalida): ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm btn-registrar-salida"
                                                        data-id-visita="<?php echo (int)$row['id_visita']; ?>"
                                                        title="Registrar salida" aria-label="Registrar salida">
                                                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($puedeEditarVisita): ?>
                                                <button type="button" class="btn btn-outline-primary btn-sm btn-editar-visita"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditarVisita"
                                                    data-id-visita="<?php echo (int)$row['id_visita']; ?>"
                                                    data-id-empresa="<?php echo $row['id_empresa'] !== null ? (int)$row['id_empresa'] : ''; ?>"
                                                    data-id-funcionario="<?php echo (int)$row['id_funcionario']; ?>"
                                                    data-id-destino="<?php echo (int)$row['id_destino']; ?>"
                                                    data-id-motivo="<?php echo (int)$row['id_motivo']; ?>"
                                                    data-detalle-motivo="<?php echo htmlspecialchars((string)($row['detalle_motivo'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-fecha="<?php echo $row['fecha_visita'] instanceof DateTime ? $row['fecha_visita']->format('Y-m-d') : ''; ?>"
                                                    data-hora-entrada="<?php echo $row['hora_entrada'] instanceof DateTime ? $row['hora_entrada']->format('H:i') : ''; ?>"
                                                    data-hora-salida="<?php echo $row['hora_salida'] instanceof DateTime ? $row['hora_salida']->format('H:i') : ''; ?>"
                                                    title="Editar visita" aria-label="Editar visita">
                                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($soloLectura): ?>
                                                <span class="badge bg-light text-dark border">Solo lectura</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td class="text-center text-muted py-3" colspan="12">No se pudo obtener el listado de visitas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-muted small">
            DEMO - Autoridad Portuaria de Manta. Datos de ejemplo con fines de presentación interna.
        </div>
    </div>
</div>

<script src="<?php echo htmlspecialchars($url_jquery_datatables); ?>"></script>
<script src="<?php echo htmlspecialchars($url_toast_js); ?>"></script>
<script src="<?php echo htmlspecialchars($url_datatables_js); ?>"></script>
<script src="<?php echo htmlspecialchars($url_datatables_bootstrap5_js); ?>"></script>

<?php if ($puedeEditarVisita): ?>
<div class="modal fade" id="modalEditarVisita" tabindex="-1" aria-labelledby="modalEditarVisitaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form id="formEditarVisita">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEditarVisitaLabel">Editar visita</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id_visita" id="edit_id_visita">
          <div class="row g-3">
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold">Empresa / Personal</label>
              <select name="id_empresa" id="edit_id_empresa" class="form-select">
                <option value="">Visita personal (sin empresa)</option>
                <?php foreach ($empresas as $r): ?>
                <option value="<?php echo (int)$r['id_empresa']; ?>"><?php echo htmlspecialchars($r['empresa'] . (!empty($r['ruc']) ? ' (' . $r['ruc'] . ')' : '')); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold">Funcionario que lo solicita</label>
              <select name="id_funcionario" id="edit_id_funcionario" class="form-select">
                <option value="">Sin funcionario asignado</option>
                <?php foreach ($funcionarios as $r): ?>
                <option value="<?php echo (int)$r['id_funcionario']; ?>"><?php echo htmlspecialchars($r['nombre'] . ' - ' . $r['cargo']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold">Destino</label>
              <select name="id_destino" id="edit_id_destino" class="form-select" required>
                <option value="">Seleccione...</option>
                <?php foreach ($destinos as $r): ?>
                <option value="<?php echo (int)$r['id_destino']; ?>"><?php echo htmlspecialchars($r['nombre']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label fw-semibold">Motivo</label>
              <select name="id_motivo" id="edit_id_motivo" class="form-select" required>
                <option value="">Seleccione...</option>
                <?php foreach ($motivos as $r): ?>
                <option value="<?php echo (int)$r['id_motivo']; ?>"><?php echo htmlspecialchars($r['descripcion']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold" for="edit_detalle_motivo">Detalle del Motivo <span class="text-muted small">(opcional)</span></label>
              <textarea name="detalle_motivo" id="edit_detalle_motivo" class="form-control" rows="2" maxlength="500" placeholder="Ingrese contexto adicional de la visita..."></textarea>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold">Fecha de visita</label>
              <input type="date" name="fecha_visita" id="edit_fecha_visita" class="form-control bg-light" readonly required>
              <div class="form-text small">Se conserva la fecha registrada por el servidor.</div>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold">Hora de entrada</label>
              <input type="time" name="hora_entrada" id="edit_hora_entrada" class="form-control bg-light" readonly required>
              <div class="form-text small">No se edita manualmente para evitar errores.</div>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold">Hora de salida</label>
              <input type="time" name="hora_salida" id="edit_hora_salida" class="form-control">
              <div class="form-text small">Opcional si aún está dentro.</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (apm_can_asignar_cedula_guest()): ?>
<div class="modal fade" id="modalAsignarCedula" tabindex="-1" aria-labelledby="modalAsignarCedulaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAsignarCedulaLabel">Asignar cédula real a visitante Guest</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-2" id="asignarCedulaNombre"></p>
        <div class="mb-3">
          <label class="form-label fw-semibold">Nueva cédula (10 dígitos)</label>
          <input type="text" id="asignarCedulaInput" class="form-control" maxlength="10" inputmode="numeric" placeholder="Ej: 1234567890">
          <div class="text-danger small mt-1" id="asignarCedulaError" style="display:none;"></div>
        </div>
      </div>
      <div class="modal-footer">
        <input type="hidden" id="asignarCedulaIdPersona" value="">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnAsignarCedula">Asignar cédula</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="modal fade" id="modalDetalleTexto" tabindex="-1" aria-labelledby="modalDetalleTextoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title" id="modalDetalleTextoLabel">Texto completo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body fs-6" id="modalDetalleTextoBody"></div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-sm btn-primary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script src="<?php echo htmlspecialchars($url_js_validaciones_ec . '?v=' . @filemtime(ROOT_PATH . '/public/js/portuaria/validaciones_ecuador.js')); ?>"></script>
<script src="<?php echo htmlspecialchars($url_js_visitas); ?>"></script>