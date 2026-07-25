<?php /* formulario.php – Vista: formulario de 5 pestañas para crear/editar empleados */
$e = $empleado ?? [];  // alias corto
$modo = $modoEdicion ? 'EDICION' : 'CREACION';
$tituloForm = $modoEdicion ? 'Modificar expediente' : 'Registrar nuevo funcionario';
$iconoForm = $modoEdicion ? 'bi-pencil-square' : 'bi-person-badge';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $modoEdicion ? 'Editar funcionario' : 'Nuevo funcionario' ?> | Talento Humano APM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
</head>

<body>
    <div id="overlay" class="overlay" onclick="closeSidebar()"></div>
    <button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()" title="Abrir menú lateral"
        aria-label="Abrir menú lateral">
        <i class="bi bi-layout-sidebar"></i>
    </button>

    <div class="app">
        <?php require_once ROOT . '/shared/menu.php'; ?>

        <section class="content">
            <header class="topbar">
                <div class="topbar-left">
                    <div class="brand">
                        <img src="<?= LOGO_URL ?>/logoapm.png" alt="Logo APM">
                        <div>
                            <h1>Autoridad Portuaria de Manta</h1>
                            <p>Modulo Talento Humano</p>
                        </div>
                    </div>
                </div>
                <div class="topbar-actions">
                    <div class="icon-chip"><i class="bi bi-calendar-event"></i><span id="currentDate">--</span></div>
                    <a href="<?= BASE_URL ?>/talento-humano" class="btn btn-ghost">
                        <i class="bi bi-arrow-left"></i> Volver al directorio
                    </a>
                </div>
            </header>

            <main class="main">
                <div class="content-shell">
                    <section class="card form-card">
                        <!-- BARRA DE PESTAÑAS -->
                        <div class="form-tabs-nav" role="tablist">
                            <button class="tab-btn active" id="tab-personal" onclick="switchTab('personal')" role="tab"
                                aria-selected="true">
                                <i class="bi bi-person-vcard"></i> Personal <span class="tab-badge">1</span>
                            </button>
                            <button class="tab-btn" id="tab-laboral" onclick="switchTab('laboral')" role="tab"
                                aria-selected="false">
                                <i class="bi bi-briefcase"></i> Laboral <span class="tab-badge">2</span>
                            </button>
                            <button class="tab-btn" id="tab-contacto" onclick="switchTab('contacto')" role="tab"
                                aria-selected="false">
                                <i class="bi bi-geo-alt"></i> Contacto <span class="tab-badge">3</span>
                            </button>
                            <button class="tab-btn" id="tab-formacion" onclick="switchTab('formacion')" role="tab"
                                aria-selected="false">
                                <i class="bi bi-mortarboard"></i> Formaci&oacute;n <span class="tab-badge">4</span>
                            </button>
                            <button class="tab-btn" id="tab-obs" onclick="switchTab('obs')" role="tab"
                                aria-selected="false">
                                <i class="bi bi-chat-left-text"></i> Notas <span class="tab-badge">5</span>
                            </button>
                        </div>

                        <div class="card-header form-header">
                            <div>
                                <h3><i class="bi <?= $iconoForm ?>"></i> <?= $tituloForm ?></h3>
                                <p>Complete, edite y valide el expediente del servidor publico.</p>
                            </div>
                            <span class="badge <?= $modoEdicion ? 'badge-edit' : 'badge-create' ?>">MODO:
                                <?= $modo ?></span>
                        </div>

                        <!-- 🛠️ BANNER MODO SIMULACIÓN -->
                        <?php if (!$modoEdicion): ?>
                        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;
                                    background:linear-gradient(135deg,rgba(16,180,199,.1),rgba(99,102,241,.08));
                                    border:1px solid rgba(16,180,199,.3); border-radius:12px;
                                    padding:14px 18px; margin:0 20px 0;">
                            <div style="font-size:.85rem; color:var(--navy-900);">
                                <i class="bi bi-info-circle-fill" style="color:var(--teal-500);"></i>
                                <strong>Modo Prototipo:</strong> Complete el formulario o utilice datos simulados para probar la validación y los campos dinámicos.
                            </div>
                            <button type="button" class="btn btn-outline" id="btn-autocompletar-form"
                                    onclick="simularFormularioPrincipal()"
                                    style="font-size:.82rem; padding:8px 16px;">
                                <i class="bi bi-magic"></i> Autocompletar Formulario
                            </button>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="<?= BASE_URL ?>/talento-humano/empleado/guardar" enctype="multipart/form-data">
                            <input type="hidden" name="empId" value="<?= htmlspecialchars($e['id'] ?? '') ?>">

                            <!-- ── TAB 1: PERSONAL ── -->
                            <div class="form-tab-panel active" id="panel-personal" role="tabpanel">

                                <!-- BLOQUE FOTO DEL EMPLEADO -->
                                <div class="foto-upload-block">
                                    <div class="foto-preview-wrap">
                                        <img id="fotoPreview"
                                             src="<?= !empty($e['ruta_foto']) && file_exists(ROOT . '/' . $e['ruta_foto'])
                                                       ? BASE_URL . '/' . htmlspecialchars($e['ruta_foto'])
                                                       : BASE_URL . '/public/img/default_avatar.png' ?>"
                                             alt="Foto del funcionario"
                                             class="foto-preview-img">
                                        <label for="foto" class="foto-overlay" title="Cambiar foto">
                                            <i class="bi bi-camera-fill"></i>
                                            <span>Cambiar foto</span>
                                        </label>
                                    </div>
                                    <div class="foto-info">
                                        <h4 class="foto-info-title"><i class="bi bi-person-badge"></i> Foto de perfil</h4>
                                        <p class="foto-info-desc">Suba una foto institucional del funcionario. Formatos: JPG, PNG o WEBP. Tamaño máximo: 2 MB.</p>
                                        <input type="file"
                                               id="foto"
                                               name="foto"
                                               accept="image/jpeg,image/png,image/webp"
                                               onchange="previewFoto(this)"
                                               class="foto-file-input">
                                        <label for="foto" class="btn btn-outline foto-btn-select">
                                            <i class="bi bi-upload"></i> Seleccionar imagen
                                        </label>
                                        <span id="fotoNombreArchivo" class="foto-nombre-archivo">Ningún archivo seleccionado</span>
                                        <?php if (!empty($e['ruta_foto'])): ?>
                                            <span class="foto-actual-badge"><i class="bi bi-check-circle-fill"></i> Foto actual guardada</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <fieldset>
                                    <legend class="section-title"><i class="bi bi-person-vcard"></i> Informacion
                                        personal</legend>
                                    <div class="form-grid">
                                        <div class="field span-2">
                                            <label for="cedula">Cedula / Pasaporte <span
                                                    class="required">*</span></label>
                                            <input type="text" id="cedula" name="cedula" required
                                                pattern="[0-9A-Za-z]{10,15}"
                                                value="<?= htmlspecialchars($e['cedula'] ?? '') ?>"
                                                placeholder="Ej: 1308126646">
                                        </div>
                                        <div class="field span-4">
                                            <label for="nombres">Apellidos y nombres completos <span
                                                    class="required">*</span></label>
                                            <input type="text" id="nombres" name="nombres" required
                                                value="<?= htmlspecialchars($e['nombres'] ?? '') ?>"
                                                placeholder="Ej: PEREZ ZAMBRANO JUAN CARLOS">
                                        </div>
                                        <div class="field span-2">
                                            <label for="fecha_nac">Fecha de nacimiento</label>
                                            <input type="date" id="fecha_nac" name="fecha_nac"
                                                value="<?= htmlspecialchars($e['fecha_nac'] ?? '') ?>"
                                                onchange="evaluarDiscapacidad()">
                                        </div>
                                        <div class="field span-2">
                                            <label for="condicion_especial">Condicion Especial</label>
                                            <select id="condicion_especial" name="condicion_especial"
                                                onchange="evaluarDiscapacidad()">
                                                <?php foreach (['Ninguna', 'Tercera Edad', 'Discapacidad', 'Ambas'] as $opt): ?>
                                                    <option value="<?= $opt ?>" <?= ($e['condicion_especial'] ?? '') === $opt ? 'selected' : '' ?>>
                                                        <?= $opt === 'Tercera Edad' ? 'Tercera Edad (65+)' : ($opt === 'Ambas' ? 'Tercera Edad y Discapacidad' : $opt) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <!-- Sub-bloque discapacidad -->
                                        <div class="disability-sub span-6" id="sub_bloque_discapacidad">
                                            <div class="field">
                                                <label for="tipo_discapacidad">Tipo de Discapacidad <span
                                                        class="required">*</span></label>
                                                <select id="tipo_discapacidad" name="tipo_discapacidad">
                                                    <option value="">Seleccione tipo...</option>
                                                    <?php foreach (['Fisica' => 'Fisica / Motriz', 'Auditiva' => 'Auditiva', 'Visual' => 'Visual', 'Intelectual' => 'Intelectual', 'Psicosocial' => 'Psicosocial'] as $val => $label): ?>
                                                        <option value="<?= $val ?>" <?= ($e['tipo_discapacidad'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="field">
                                                <label for="porcentaje_discapacidad">Grado (%) <span
                                                        class="required">*</span></label>
                                                <input type="number" id="porcentaje_discapacidad"
                                                    name="porcentaje_discapacidad" min="0" max="100"
                                                    value="<?= htmlspecialchars($e['porcentaje_discapacidad'] ?? '') ?>"
                                                    placeholder="Ej: 40">
                                            </div>
                                        </div>
                                        <div class="field span-2">
                                            <label for="genero">Genero</label>
                                            <select id="genero" name="genero">
                                                <option value="">Seleccione...</option>
                                                <?php foreach (['Masculino', 'Femenino', 'Otro'] as $g): ?>
                                                    <option value="<?= $g ?>" <?= ($e['genero'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field span-2">
                                            <label for="estado_civil">Estado civil</label>
                                            <select id="estado_civil" name="estado_civil">
                                                <?php foreach (['Soltero/a', 'Casado/a', 'Divorciado/a', 'Union Libre'] as $ec): ?>
                                                    <option value="<?= $ec ?>" <?= ($e['estado_civil'] ?? '') === $ec ? 'selected' : '' ?>><?= $ec ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field span-2">
                                            <label for="nacionalidad">Nacionalidad</label>
                                            <div id="contenedorNacionalidades">
                                                <div class="input-group-nac" style="display:flex; gap:8px; margin-bottom:6px;">
                                                    <input type="text" id="nacionalidad" name="nacionalidad"
                                                           value="<?= htmlspecialchars($e['nacionalidad'] ?? '') ?>"
                                                           class="inputs-nacionalidad"
                                                           placeholder="Ej: Ecuatoriana" style="flex:1;">
                                                </div>
                                            </div>
                                            <button type="button" onclick="agregarNacionalidad()"
                                                    style="display:inline-flex; align-items:center; gap:6px; padding:5px 12px;
                                                           background:none; border:1px dashed var(--teal-500); color:var(--teal-500);
                                                           border-radius:8px; font-size:.78rem; font-weight:600; cursor:pointer;
                                                           margin-top:4px;">
                                                <i class="bi bi-plus-circle"></i> Agregar nacionalidad
                                            </button>
                                        </div>
                                        <div class="field span-2">
                                            <label for="sangre">Tipo de sangre</label>
                                            <select id="sangre" name="sangre">
                                                <option value="">Seleccione...</option>
                                                <?php foreach (['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'] as $s): ?>
                                                    <option value="<?= $s ?>" <?= ($e['sangre'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </fieldset>
                            </div><!-- /panel-personal -->

                            <!-- ── TAB 2: LABORAL ── -->
                            <div class="form-tab-panel" id="panel-laboral" role="tabpanel">
                                <fieldset>
                                    <legend class="section-title"><i class="bi bi-briefcase"></i> Datos laborales APM
                                    </legend>
                                    <div class="form-grid">
                                        <div class="field span-3">
                                            <label for="unidad_id">Departamento / Dirección <span
                                                    class="required">*</span></label>
                                            <select id="unidad_id" name="unidad_id" required>
                                                <option value="">Seleccione dirección...</option>
                                                <?php
                                                $areasDisp = $areas ?? [];
                                                foreach ($areasDisp as $area):
                                                    $selArea = ((string)($e['unidad_id'] ?? '') === (string)$area['unidad_id']) ? 'selected' : '';
                                                ?>
                                                    <option value="<?= htmlspecialchars($area['unidad_id']) ?>" <?= $selArea ?>>
                                                        <?= htmlspecialchars($area['nombre_unidad']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field span-3">
                                            <label for="puesto_id">Cargo / Puesto <span
                                                    class="required">*</span></label>
                                            <select id="puesto_id" name="puesto_id" required>
                                                <option value="">Seleccione cargo...</option>
                                                <?php
                                                $cargosDisp = $cargos ?? [];
                                                foreach ($cargosDisp as $puesto):
                                                    $selPuesto = ((string)($e['puesto_id'] ?? '') === (string)$puesto['puesto_id']) ? 'selected' : '';
                                                ?>
                                                    <option value="<?= htmlspecialchars($puesto['puesto_id']) ?>" <?= $selPuesto ?>>
                                                        <?= htmlspecialchars($puesto['nombre_puesto']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field span-2">
                                            <label for="tipo_contrato">Tipo de contrato</label>
                                            <select id="tipo_contrato" name="tipo_contrato">
                                                <?php foreach (['Nombramiento Permanente', 'Nombramiento Provisional', 'Contrato Ocasional', 'Codigo del Trabajo'] as $tc): ?>
                                                    <option value="<?= $tc ?>" <?= ($e['tipo_contrato'] ?? '') === $tc ? 'selected' : '' ?>><?= $tc ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field span-2">
                                            <label for="fecha_ingreso">Fecha de ingreso</label>
                                            <input type="date" id="fecha_ingreso" name="fecha_ingreso"
                                                value="<?= htmlspecialchars($e['fecha_ingreso'] ?? '') ?>">
                                        </div>
                                        <div class="field span-2">
                                            <label for="sueldo">Sueldo / RMU ($)</label>
                                            <input type="number" id="sueldo" name="sueldo" step="0.01"
                                                value="<?= htmlspecialchars($e['sueldo'] ?? '') ?>" placeholder="0.00">
                                        </div>
                                        <div class="field span-3">
                                            <label for="ciudad_residencia">Ciudad de Residencia <span
                                                    class="required">*</span></label>
                                            <input type="text" id="ciudad_residencia" name="ciudad_residencia" required
                                                value="<?= htmlspecialchars($e['ciudad_residencia'] ?? '') ?>"
                                                placeholder="Ej: Manta">
                                        </div>
                                        <div class="field span-2">
                                            <label for="estado">Estado del funcionario</label>
                                            <select id="estado" name="estado">
                                                <?php foreach (['Activo' => 'Activo / En funciones', 'Permiso' => 'Licencia / Vacaciones', 'Inactivo' => 'Inactivo / Desvinculado'] as $val => $label): ?>
                                                    <option value="<?= $val ?>" <?= ($e['estado'] ?? 'Activo') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field span-2">
                                            <label for="jornada">Jornada</label>
                                            <select id="jornada" name="jornada">
                                                <?php foreach (['Completa', 'Parcial', 'Rotativa'] as $j): ?>
                                                    <option value="<?= $j ?>" <?= ($e['jornada'] ?? 'Completa') === $j ? 'selected' : '' ?>><?= $j ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </fieldset>
                            </div><!-- /panel-laboral -->

                            <!-- ── TAB 3: CONTACTO ── -->
                            <div class="form-tab-panel" id="panel-contacto" role="tabpanel">
                                <fieldset>
                                    <legend class="section-title"><i class="bi bi-geo-alt"></i> Contacto y emergencias
                                    </legend>
                                    <div class="form-grid">
                                        <div class="field span-3">
                                            <label for="correo">Correo institucional</label>
                                            <input type="email" id="correo" name="correo"
                                                value="<?= htmlspecialchars($e['correo'] ?? '') ?>"
                                                placeholder="usuario@puertodemanta.gob.ec">
                                        </div>
                                        <div class="field span-3">
                                            <label for="telefono">Telefono movil</label>
                                            <input type="tel" id="telefono" name="telefono"
                                                value="<?= htmlspecialchars($e['telefono'] ?? '') ?>"
                                                placeholder="09XXXXXXXX">
                                        </div>
                                        <div class="field span-6">
                                            <label for="direccion">Direccion domiciliaria</label>
                                            <textarea id="direccion" name="direccion"
                                                placeholder="Barrio, calle principal y secundaria..."><?= htmlspecialchars($e['direccion'] ?? '') ?></textarea>
                                        </div>
                                        <div class="field span-3">
                                            <label for="contacto_emergencia">Contacto en emergencia</label>
                                            <input type="text" id="contacto_emergencia" name="contacto_emergencia"
                                                value="<?= htmlspecialchars($e['contacto_emergencia'] ?? '') ?>"
                                                placeholder="Nombre del familiar">
                                        </div>
                                        <div class="field span-2">
                                            <label for="emergencia_relacion">Relacion</label>
                                            <input type="text" id="emergencia_relacion" name="emergencia_relacion"
                                                value="<?= htmlspecialchars($e['emergencia_relacion'] ?? '') ?>"
                                                placeholder="Ej: Madre">
                                        </div>
                                        <div class="field span-1">
                                            <label for="tel_emergencia">Telefono</label>
                                            <input type="tel" id="tel_emergencia" name="tel_emergencia"
                                                value="<?= htmlspecialchars($e['tel_emergencia'] ?? '') ?>"
                                                placeholder="09XXXXXXX">
                                        </div>
                                    </div>
                                </fieldset>
                            </div><!-- /panel-contacto -->

                            <!-- ── TAB 4: FORMACIÓN ── -->
                            <div class="form-tab-panel" id="panel-formacion" role="tabpanel">
                                <fieldset>
                                    <legend class="section-title"><i class="bi bi-mortarboard"></i> Documentos y
                                        formacion</legend>
                                    <div class="form-grid">
                                        <div class="field span-2">
                                            <label for="nivel_estudio">Nivel de estudio</label>
                                            <select id="nivel_estudio" name="nivel_estudio">
                                                <option value="">Seleccione...</option>
                                                <?php foreach (['Secundaria', 'Tecnico', 'Tercer Nivel', 'Posgrado'] as $ne): ?>
                                                    <option value="<?= $ne ?>" <?= ($e['nivel_estudio'] ?? '') === $ne ? 'selected' : '' ?>><?= $ne ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field span-4">
                                            <label for="titulo">Titulo profesional</label>
                                            <input type="text" id="titulo" name="titulo"
                                                value="<?= htmlspecialchars($e['titulo'] ?? '') ?>"
                                                placeholder="Ej: Ingenieria en Sistemas">
                                        </div>
                                        <div class="field span-2">
                                            <label for="iess">Codigo IESS</label>
                                            <input type="text" id="iess" name="iess"
                                                value="<?= htmlspecialchars($e['iess'] ?? '') ?>"
                                                placeholder="IESS-000001">
                                        </div>
                                    </div>
                                </fieldset>
                            </div><!-- /panel-formacion -->

                            <!-- ── TAB 5: NOTAS ── -->
                            <div class="form-tab-panel" id="panel-obs" role="tabpanel">
                                <fieldset>
                                    <legend class="section-title"><i class="bi bi-chat-left-text"></i> Observaciones
                                    </legend>
                                    <div class="form-grid">
                                        <div class="field span-6">
                                            <label for="observaciones">Notas internas</label>
                                            <textarea id="observaciones" name="observaciones"
                                                placeholder="Observaciones, cambios recientes, procesos pendientes..."><?= htmlspecialchars($e['observaciones'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </fieldset>
                            </div><!-- /panel-obs -->

                            <!-- FOOTER DEL FORMULARIO -->
                            <div class="form-footer">
                                <div class="helper-text">
                                    <i class="bi bi-info-circle"></i>
                                    Los campos marcados con (*) son obligatorios para el registro APM.
                                </div>
                                <div class="btn-group">
                                    <a href="<?= BASE_URL ?>/talento-humano" class="btn btn-outline">
                                        <i class="bi bi-x-lg"></i> Cancelar
                                    </a>
                                    <?php if ($modoEdicion): ?>
                                        <button type="button" class="btn btn-danger"
                                            onclick="if(confirm('¿Eliminar este registro?')){document.getElementById('deleteForm').submit();}">
                                            <i class="bi bi-trash3"></i> Eliminar
                                        </button>
                                        <!-- Botón PDF: solo disponible cuando el expediente ya está guardado -->
                                        <?php if (!empty($e['id'])): ?>
                                        <a href="<?= BASE_URL ?>/talento-humano/empleado/imprimir-ficha?id=<?= (int)$e['id'] ?>"
                                           target="_blank"
                                           class="btn btn-outline"
                                           title="Abrir la Ficha Integral en PDF (nueva pestaña)"
                                           style="border-color: var(--rose-500, #e11d48); color: var(--rose-500, #e11d48);">
                                            <i class="bi bi-file-earmark-pdf-fill"></i> Imprimir Ficha PDF
                                        </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button type="submit"
                                        class="btn <?= $modoEdicion ? 'btn-warning' : 'btn-primary' ?>">
                                        <i class="bi bi-<?= $modoEdicion ? 'arrow-repeat' : 'save' ?>"></i>
                                        <?= $modoEdicion ? 'Actualizar cambios' : 'Guardar funcionario' ?>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if ($modoEdicion): ?>
                            <form id="deleteForm" method="POST" action="<?= BASE_URL ?>/talento-humano/empleado/eliminar"
                                style="display:none;">
                                <input type="hidden" name="id" value="<?= (int) ($e['id'] ?? 0) ?>">
                            </form>
                        <?php endif; ?>
                    </section>
                </div>
            </main>
        </section>
    </div>

    <div id="toastContainer" class="toast-container"></div>

    <script src="<?= BASE_URL ?>/public/js/layout_sidebar.js"></script>
    <script src="<?= BASE_URL ?>/public/js/toast.js"></script>
    <script src="<?= BASE_URL ?>/public/js/talento_humano.js"></script>
    <script>
        /* Inicializar estado del bloque discapacidad al cargar en modo edición */
        window.addEventListener('DOMContentLoaded', () => {
            evaluarDiscapacidad();
        });

        /* Previsualización de foto antes de guardar */
        function previewFoto(input) {
            const MAX_MB   = 2;
            const MAX_BYTES = MAX_MB * 1024 * 1024;
            const allowed  = ['image/jpeg', 'image/png', 'image/webp'];
            const file     = input.files[0];
            const label    = document.getElementById('fotoNombreArchivo');
            const preview  = document.getElementById('fotoPreview');

            if (!file) return;

            if (!allowed.includes(file.type)) {
                showToast?.('Solo se permiten imágenes JPG, PNG o WEBP.', 'error');
                input.value = '';
                return;
            }
            if (file.size > MAX_BYTES) {
                showToast?.('La imagen supera el límite de 2 MB.', 'error');
                input.value = '';
                return;
            }

            label.textContent = file.name;
            const reader = new FileReader();
            reader.onload = e => { preview.src = e.target.result; };
            reader.readAsDataURL(file);
            showToast?.('Imagen lista. Guarde el formulario para confirmar.', 'info');
        }

        /* ── MOCK DATA SIMULATION ─────────────────────────────────────────── */
        const mockDataFuncionario = {
            cedula:           '1308126646',
            nombres:          'PEREZ ZAMBRANO JUAN CARLOS',
            fecha_nac:        '1985-10-15',
            condicion_especial: 'Ninguna',
            genero:           'Masculino',
            estado_civil:     'Casado/a',
            sangre:           'O+',
            nacionalidades:   ['Ecuatoriana', 'Española']  // Doble nacionalidad
        };

        function simularFormularioPrincipal() {
            // Ir a la pestaña Personal primero
            switchTab('personal');

            // Campos de texto
            const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
            set('cedula',  mockDataFuncionario.cedula);
            set('nombres', mockDataFuncionario.nombres);
            set('fecha_nac', mockDataFuncionario.fecha_nac);

            // Dropdowns
            set('condicion_especial', mockDataFuncionario.condicion_especial);
            set('genero',             mockDataFuncionario.genero);
            set('estado_civil',       mockDataFuncionario.estado_civil);
            set('sangre',             mockDataFuncionario.sangre);

            // Disparar cálculo de condición especial
            evaluarDiscapacidad?.();

            // Lógica dinámica para múltiple nacionalidad
            const contenedor = document.getElementById('contenedorNacionalidades');
            if (contenedor) {
                // Limpiar dejando solo el primer input
                contenedor.innerHTML = `
                    <div class="input-group-nac" style="display:flex; gap:8px; margin-bottom:6px;">
                        <input type="text" name="nacionalidad" class="inputs-nacionalidad"
                               placeholder="Ej: Ecuatoriana" style="flex:1;">
                    </div>`;

                mockDataFuncionario.nacionalidades.forEach((nac, i) => {
                    if (i === 0) {
                        contenedor.querySelector('.inputs-nacionalidad').value = nac;
                    } else {
                        agregarNacionalidad();
                        const inputs = contenedor.querySelectorAll('.inputs-nacionalidad');
                        inputs[inputs.length - 1].value = nac;
                    }
                });
            } else {
                // Fallback: campo simple
                set('nacionalidad', mockDataFuncionario.nacionalidades[0]);
            }

            showToast?.('✅ Datos de prueba cargados. Verifique los campos de doble nacionalidad.', 'success');
        }

        function agregarNacionalidad() {
            const contenedor = document.getElementById('contenedorNacionalidades');
            if (!contenedor) return;
            const div = document.createElement('div');
            div.className = 'input-group-nac';
            div.style.cssText = 'display:flex; gap:8px; margin-bottom:6px;';
            div.innerHTML = `
                <input type="text" name="nacionalidad" class="inputs-nacionalidad"
                       placeholder="Ej: Española, Italiana..." style="flex:1;">
                <button type="button" onclick="this.parentElement.remove()" title="Eliminar"
                        style="padding:6px 10px; background:none; border:1px solid #ef4444;
                               color:#ef4444; border-radius:8px; cursor:pointer;">
                    <i class="bi bi-trash"></i>
                </button>`;
            contenedor.appendChild(div);
        }
    </script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>

</html>