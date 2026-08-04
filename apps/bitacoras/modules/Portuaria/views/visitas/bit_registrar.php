<?php
/**
 * Vista: Registrar ingreso de visita.
 * Reemplaza el HTML de bit_registrar_visita.php
 * Variables: $funcionarios, $destinos, $motivos, $niveles, $dbaseDisponible, $errorCode
 */
?>
<div class="container-fluid py-2 formulario-ingreso-container">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0 formulario-ingreso-card">
                        <div class="card-header bg-white py-2">
                            <h1 class="h5 text-primary mb-0">Registrar ingreso de visita / proveedor</h1>
                            <p class="text-muted mb-0 small lh-1">
                                La fecha y hora de ingreso se registran automáticamente desde el servidor.
                            </p>
                        </div>

                        <div class="card-body formulario-ingreso-body">
                            <?php if (isset($_GET['error']) && $_GET['error'] === '1'): ?>
                                <div class="alert alert-danger mb-3">
                                    Ocurrió un error al guardar la visita. Intente nuevamente.
                                </div>
                            <?php endif; ?>
                            <?php if (isset($_GET['error']) && $_GET['error'] === '2'): ?>
                                <div class="alert alert-danger mb-3">
                                    La identificación ingresada no es válida.
                                </div>
                            <?php endif; ?>
                            <?php if (isset($_GET['error']) && $_GET['error'] === '3'): ?>
                                <div class="alert alert-warning mb-3">
                                    Esta persona ya tiene una visita activa. Debe registrar salida antes de crear un nuevo ingreso.
                                </div>
                            <?php endif; ?>
                            <?php if (empty($funcionarios)): ?>
                                <?php if (!$dbaseDisponible): ?>
                                    <div class="alert alert-warning mb-3">
                                        <strong>Extensión dbase no instalada o no habilitada.</strong> No se pudieron cargar funcionarios activos desde <code>rolmaes.DBF</code>.
                                        Puede habilitarla o usar <a href="importar-funcionarios" class="alert-link">Importar funcionarios</a> como contingencia.
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning mb-3">
                                        No hay funcionarios activos disponibles para el selector. Verifique sincronización DBF y estado/FEC_SALIDA en la tabla local.
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div id="formErrorsBox" class="alert alert-danger formulario-alertas mb-3 position-relative pe-4" role="alert" style="display: none;">
                                <button type="button" class="btn-close position-absolute top-0 end-0 m-2" id="formErrorsBoxClose" aria-label="Cerrar"></button>
                                <h6 class="alert-heading mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Corrija los siguientes errores:</h6>
                                <ul id="formErrorsList" class="mb-0 ps-3 small"></ul>
                            </div>

                            <form action="bitacoras/visita/guardar" method="post" autocomplete="off" id="formIngreso">
                                <div class="row g-3 formulario-fila">

<!-- 
                                <div class="col-12 col-md-8">
                                        <label class="form-label fw-semibold">N° Cédula</label>
                                        <div class="input-group">
                                            <input type="text" name="cedula" id="cedula" class="form-control" required maxlength="10" inputmode="numeric">
                                            <button class="btn btn-outline-primary" type="button" id="btnBuscarCedula" title="Buscar por cédula">
                                                <i class="bi bi-search"></i>
                                            </button>
                                            <button class="btn btn-outline-success" type="button" id="btnNuevaPersona" title="Registrar nueva persona" data-bs-toggle="modal" data-bs-target="#modalPersona">
                                                <i class="bi bi-person-plus"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            La cédula debe tener exactamente 10 dígitos numéricos.
                                        </div>
                                        <div class="text-danger small" id="cedula_error" style="display:none;"></div>
                                    </div>


                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">Nombre</label>
                                        <input type="text" name="nombre" id="nombre" class="form-control" required maxlength="100">
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">Apellido</label>
                                        <input type="text" name="apellido" id="apellido" class="form-control" required maxlength="100">
                                    </div>

                                    <div class="row g-3">


                                    <div class="row g-3"> -->

        <!-- CEDULA (Select2 + mismo comportamiento: búsqueda por cédula, rellenar nombre/apellido) -->
        <div class="col-12 col-sm-6 col-lg-4">
            <label class="form-label fw-semibold">N° Cédula</label>

            <div class="input-group apm-input-group-cedula">
                <input type="hidden" name="nidentificacion" id="nidentificacion">
                <select id="cedula_selector" class="form-select" data-placeholder="Buscar por cédula..." style="width:100%">
                    <option value="">Buscar por cédula...</option>
                    <option value="9999999999">Visitante sin cédula (Guest)</option>
                </select>

                <button class="btn btn-outline-success"
                        type="button"
                        id="btnNuevaPersona"
                        title="Registrar nueva persona"
                        data-bs-toggle="modal"
                        data-bs-target="#modalPersona">
                    <i class="bi bi-person-plus"></i>
                </button>
            </div>
            <!-- CEDULA 
            <div class="form-text">
                La cédula debe tener exactamente 10 dígitos numéricos.
            </div>
                            -->
            
                 
            <div class="form-check mt-1 small">
                <input class="form-check-input" type="checkbox" id="visitante_guest" name="visitante_guest" value="1" autocomplete="off">
                <label class="form-check-label text-muted" for="visitante_guest">Visitante sin cédula (Guest)</label>
            </div>
            <div class="text-danger small"
                id="cedula_error"
                style="display:none;">
            </div>
        </div>

        <!-- NOMBRES / APELLIDOS (mismos ids que usa registrar_visita.js) -->
        <div class="col-12 col-sm-6 col-lg-4">
            <label class="form-label fw-semibold">Nombres</label>
            <input type="text"
                name="nombres"
                id="nombres"
                class="form-control"
                required
                maxlength="100">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="form-label fw-semibold">Apellidos</label>
            <input type="text"
                name="apellidos"
                id="apellidos"
                class="form-control"
                required
                maxlength="100">
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
            <label class="form-label fw-semibold">Género <span class="text-muted fw-normal">(opcional)</span></label>
            <select name="genero" id="genero" class="form-select">
                <option value="">Sin especificar</option>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
            </select>
        </div>

        </div>

        <div class="row g-3 formulario-fila formulario-seccion">
            <!-- Empresa | Fecha/hora | Nivel de importancia -->
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Empresa / Personal</label>
                <div class="input-group apm-input-group-empresa">
                    <select name="id_empresa" id="id_empresa" class="form-select" data-placeholder="Buscar empresa por nombre o RUC" style="width:100%">
                        <option value="">Visita personal (sin empresa)</option>
                    </select>
                    <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#modalEmpresa" title="Agregar empresa">
                        <i class="bi bi-building-add"></i>
                    </button>
                </div>
                <div class="form-text small">Escriba para filtrar o buscar; puede buscar por nombre o RUC.</div>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Fecha y hora de visita</label>
                <input type="datetime-local"
                    name="fecha_hora_visita"
                    id="fecha_hora_visita"
                    class="form-control"
                    value="<?php echo date('Y-m-d\TH:i'); ?>"
                    required>
                <small class="form-text text-muted">Ingreso registrado desde el servidor</small>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold" for="id_nivel_incidente">Nivel de importancia</label>
                <select name="id_nivel_incidente" id="id_nivel_incidente" class="form-select" required>
                    <?php if (!empty($niveles)): ?>
                        <?php foreach ($niveles as $row): ?>
                            <?php $nid = (int)$row['id_incidentes']; ?>
                            <option value="<?php echo $nid; ?>"<?php echo $nid === 1 ? ' selected' : ''; ?>><?php echo htmlspecialchars($row['descripcion']); ?></option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="1" selected>Normal</option>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="row g-3 formulario-fila formulario-seccion">
            <!-- Funcionario | Destino | Motivo -->
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Funcionario que lo solicita</label>
                <div class="input-group apm-input-group-funcionario">
                    <select name="id_funcionario" id="id_funcionario" class="form-select" data-placeholder="Buscar funcionario opcional...">
                        <option value="">Sin funcionario asignado</option>
                        <?php foreach ($funcionarios as $row): ?>
                            <?php
                            $texto = $row['nombre'] . ' - ' . $row['cargo'];
                            if (!empty($row['cedula'])) {
                                $texto .= ' (' . $row['cedula'] . ')';
                            }
                            ?>
                            <option value="<?php echo (int)$row['id_funcionario']; ?>"><?php echo htmlspecialchars($texto); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#modalFuncionario" title="Agregar funcionario"><i class="bi bi-person-badge"></i></button>
                </div>
                <div class="form-text small">Campo opcional: puede guardar el ingreso sin seleccionar funcionario.</div>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Destino</label>
                <div class="input-group apm-input-group-funcionario">
                    <select name="id_destino" id="id_destino" class="form-select" required>
                        <option value="">Seleccione destino...</option>
                        <?php foreach ($destinos as $row): ?>
                            <option value="<?php echo $row['id_destino']; ?>"><?php echo htmlspecialchars($row['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#modalDestino" title="Agregar nuevo destino" aria-label="Agregar nuevo destino"><i class="bi bi-plus"></i></button>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold">Motivo</label>
                <div class="input-group apm-input-group-motivo">
                    <select name="id_motivo" id="id_motivo" class="form-select" required data-placeholder="Buscar motivo...">
                        <option value="">Buscar motivo...</option>
                        <?php foreach ($motivos as $row): ?>
                            <option value="<?php echo (int)$row['id_motivo']; ?>">
                                <?php echo htmlspecialchars($row['descripcion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button 
                        class="btn btn-outline-success" 
                        type="button" 
                        data-bs-toggle="modal" 
                        data-bs-target="#modalMotivo" 
                        title="Agregar nuevo motivo"
                        aria-label="Agregar nuevo motivo">
                        <i class="bi bi-plus"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-3 formulario-fila formulario-seccion">
            <div class="col-12">
                <label class="form-label fw-semibold" for="detalle_motivo">Detalle del Motivo <span class="text-muted small">(opcional)</span></label>
                <textarea name="detalle_motivo" id="detalle_motivo" class="form-control" rows="2" maxlength="500" placeholder="Ingrese contexto adicional de la visita..."></textarea>
                <div class="form-text small">Puede dejarlo vacío si no aplica.</div>
            </div>
        </div>

        <div class="row g-3 formulario-fila formulario-seccion formulario-botones">
<div class="col-12 text-end pt-0">
<a href="dashboard" class="btn btn-outline-secondary me-2">Cancelar</a>
<button type="submit" class="btn btn-primary" id="btnGuardarIngreso">Guardar ingreso</button>
</div>

</form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <!-- jQuery → Bootstrap → plugins (Select2, toast) -->
<!-- jQuery debe cargar antes que Select2 (bootstrap.js y layout_sidebar.js ya los agrega el layout) -->
<script src="<?php echo htmlspecialchars($url_jquery_js); ?>"></script>
<script src="<?php echo htmlspecialchars($url_select2_js); ?>"></script>
<script src="<?php echo htmlspecialchars($url_toast_js); ?>"></script>

<!-- Modales para persona, empresa y destino -->
<div class="modal fade" id="modalPersona" tabindex="-1" aria-labelledby="modalPersonaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formModalPersona" class="needs-validation" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="modalPersonaLabel">Registrar nueva persona</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
              <label class="form-label fw-semibold" for="persona_tidentif">Tipo de identificación</label>
              <select id="persona_tidentif" name="persona_tidentif" class="form-select" required>
                  <option value="CEDULA" selected>Cédula</option>
              </select>
              <div class="invalid-feedback">Seleccione el tipo de identificación.</div>
          </div>

          <div class="mb-3">
              <label class="form-label fw-semibold" for="persona_nidentificacion">N° identificación</label>
              <input
                  type="text"
                  id="persona_nidentificacion"
                  name="persona_nidentificacion"
                  class="form-control"
                  maxlength="10"
                  inputmode="numeric"
                  pattern="[0-9]{10}"
                  placeholder="Ej: 1301234567"
                  required
              >
              <div class="invalid-feedback">Ingrese una cédula válida de 10 dígitos.</div>
          </div>

          <div class="mb-3">
              <label class="form-label fw-semibold" for="persona_nombres">Nombres</label>
              <input
                  type="text"
                  id="persona_nombres"
                  name="persona_nombres"
                  class="form-control"
                  maxlength="100"
                  required
              >
              <div class="invalid-feedback">Ingrese los nombres.</div>
          </div>

          <div class="mb-3">
              <label class="form-label fw-semibold" for="persona_apellidos">Apellidos</label>
              <input
                  type="text"
                  id="persona_apellidos"
                  name="persona_apellidos"
                  class="form-control"
                  maxlength="100"
                  required
              >
              <div class="invalid-feedback">Ingrese los apellidos.</div>
          </div>

          <div class="text-danger small" id="persona_error" style="display:none;"></div>
        </div>

        <div class="modal-footer justify-content-between">
          <a href="catalogos/personas" class="btn btn-outline-primary btn-sm"><i class="bi bi-list-ul"></i> Abrir maestro</a>
          <div>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success" id="btnGuardarPersona">Guardar</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEmpresa" tabindex="-1" aria-labelledby="modalEmpresaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formModalEmpresa" class="needs-validation" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="modalEmpresaLabel">Registrar nueva empresa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
              <label class="form-label fw-semibold" for="empresa_ruc">RUC</label>
              <input type="text" id="empresa_ruc" name="empresa_ruc" class="form-control" maxlength="13" inputmode="numeric" placeholder="Máx. 13 dígitos (SRI)" required>
              <div class="invalid-feedback">Ingrese el RUC.</div>
          </div>
          <div class="mb-3">
              <label class="form-label fw-semibold" for="empresa_empresa">Nombre comercial (empresa)</label>
              <input type="text" id="empresa_empresa" name="empresa_empresa" class="form-control" maxlength="150" required>
              <div class="invalid-feedback">Ingrese el nombre comercial.</div>
          </div>
          <div class="mb-3">
              <label class="form-label fw-semibold" for="empresa_razonsocial">Razón social</label>
              <input type="text" id="empresa_razonsocial" name="empresa_razonsocial" class="form-control" maxlength="150" required>
              <div class="invalid-feedback">Ingrese la razón social.</div>
          </div>
          <div class="text-danger small" id="empresa_error" style="display:none;"></div>
        </div>
        <div class="modal-footer justify-content-between">
          <a href="catalogos/empresas" class="btn btn-outline-primary btn-sm"><i class="bi bi-list-ul"></i> Abrir maestro</a>
          <div>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success" id="btnGuardarEmpresa">Guardar</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDestino" tabindex="-1" aria-labelledby="modalDestinoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalDestinoLabel">Agregar nuevo destino</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label fw-semibold">Nombre del destino</label>
            <input type="text" id="destino_nombre" class="form-control" maxlength="150">
        </div>
        <div class="text-danger small" id="destino_error" style="display:none;"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <a href="catalogos/destinos" class="btn btn-outline-primary btn-sm"><i class="bi bi-list-ul"></i> Abrir maestro</a>
        <div>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-success" id="btnGuardarDestino">Guardar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalMotivo" tabindex="-1" aria-labelledby="modalMotivoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalMotivoLabel">Agregar nuevo motivo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label fw-semibold">Descripción del motivo</label>
            <input type="text" id="motivo_descripcion" class="form-control" maxlength="200">
        </div>
        <div class="text-danger small" id="motivo_error" style="display:none;"></div>
      </div>
      <div class="modal-footer justify-content-between">
        <a href="catalogos/motivos" class="btn btn-outline-primary btn-sm"><i class="bi bi-list-ul"></i> Abrir maestro</a>
        <div>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-success" id="btnGuardarMotivo">Guardar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalFuncionario" tabindex="-1" aria-labelledby="modalFuncionarioLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formModalFuncionario" class="needs-validation" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="modalFuncionarioLabel">Agregar nuevo funcionario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
              <label class="form-label fw-semibold" for="funcionario_cedula">Cédula</label>
              <input type="text" id="funcionario_cedula" name="funcionario_cedula" class="form-control" maxlength="20" inputmode="numeric" placeholder="Solo números" required>
              <div class="invalid-feedback">Ingrese la cédula.</div>
          </div>
          <div class="mb-3">
              <label class="form-label fw-semibold" for="funcionario_nombre">Nombre del funcionario</label>
              <input type="text" id="funcionario_nombre" name="funcionario_nombre" class="form-control" maxlength="150" required>
              <div class="invalid-feedback">Ingrese el nombre.</div>
          </div>
          <div class="mb-3">
              <label class="form-label fw-semibold" for="funcionario_cargo">Cargo</label>
              <input type="text" id="funcionario_cargo" name="funcionario_cargo" class="form-control" maxlength="100" required>
              <div class="invalid-feedback">Ingrese el cargo.</div>
          </div>
          <div class="text-danger small" id="funcionario_error" style="display:none;"></div>
        </div>
        <div class="modal-footer justify-content-between">
          <a href="catalogos/funcionarios" class="btn btn-outline-primary btn-sm"><i class="bi bi-list-ul"></i> Abrir maestro</a>
          <div>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-success" id="btnGuardarFuncionario">Guardar</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="<?php echo htmlspecialchars($url_js_validaciones_ec . '?v=' . @filemtime(ROOT_PATH . '/public/js/portuaria/validaciones_ecuador.js')); ?>"></script>
<script src="<?php echo htmlspecialchars($url_js_registro); ?>"></script>