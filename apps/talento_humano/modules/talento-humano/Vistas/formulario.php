<?php /* formulario.php – Vista: formulario de 5 pestañas para crear/editar empleados */
$e = $empleado ?? [];  // alias corto
$modo = $modoEdicion ? 'EDICION' : 'CREACION';
$tituloForm = $modoEdicion ? 'Modificar expediente' : 'Registrar nuevo funcionario';
$iconoForm = $modoEdicion ? 'bi-pencil-square' : 'bi-person-badge';
$nacSeleccionadas = $nacionalidadesEmpleado ?? [];
$catalogoNacionalidades = $nacionalidades ?? [];
$regimenLaboral = strtoupper((string)($e['regimen_laboral'] ?? ''));
if ($regimenLaboral === '') {
    $regimenLaboral = str_contains(mb_strtoupper((string)($e['tipo_contrato'] ?? ''), 'UTF-8'), 'CODIGO')
        ? 'CODIGO_TRABAJO'
        : 'LOSEP';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $modoEdicion ? 'Editar funcionario' : 'Nuevo funcionario' ?> | Talento Humano APM</title>
    <?php require ROOT . '/shared/head_assets.php'; ?>
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
            <?php
            $topbarShowSearch=true;
            $topbarBackUrl=BASE_URL.'/talento-humano/directorio';
            $topbarBackLabel='Volver al directorio';
            require ROOT.'/shared/topbar.php';
            ?>

            <main class="main">
                <div class="content-shell">
                    <section class="card form-card">
                        <!-- BARRA DE PESTAÑAS -->
                        <div class="card-header form-header">
                            <div>
                                <span class="form-header-kicker">Expediente institucional</span>
                                <h3><i class="bi <?= $iconoForm ?>"></i> <?= $tituloForm ?></h3>
                                <p>Complete, edite y valide la información del servidor público.</p>
                            </div>
                            <span class="badge <?= $modoEdicion ? 'badge-edit' : 'badge-create' ?>">MODO:
                                <?= $modo ?></span>
                        </div>

                        <div class="form-tabs-nav" role="tablist" aria-label="Secciones del expediente">
                            <button type="button" class="tab-btn active" id="tab-personal" onclick="switchTab('personal')" role="tab"
                                aria-selected="true" aria-controls="panel-personal" tabindex="0">
                                <i class="bi bi-person-vcard"></i> Personal <span class="tab-badge">1</span>
                            </button>
                            <button type="button" class="tab-btn" id="tab-laboral" onclick="switchTab('laboral')" role="tab"
                                aria-selected="false" aria-controls="panel-laboral" tabindex="-1">
                                <i class="bi bi-briefcase"></i> Laboral <span class="tab-badge">2</span>
                            </button>
                            <button type="button" class="tab-btn" id="tab-contacto" onclick="switchTab('contacto')" role="tab"
                                aria-selected="false" aria-controls="panel-contacto" tabindex="-1">
                                <i class="bi bi-geo-alt"></i> Contacto <span class="tab-badge">3</span>
                            </button>
                            <button type="button" class="tab-btn" id="tab-formacion" onclick="switchTab('formacion')" role="tab"
                                aria-selected="false" aria-controls="panel-formacion" tabindex="-1">
                                <i class="bi bi-mortarboard"></i> Formaci&oacute;n <span class="tab-badge">4</span>
                            </button>
                            <button type="button" class="tab-btn" id="tab-obs" onclick="switchTab('obs')" role="tab"
                                aria-selected="false" aria-controls="panel-obs" tabindex="-1">
                                <i class="bi bi-chat-left-text"></i> Notas <span class="tab-badge">5</span>
                            </button>
                        </div>

                        <form id="empleadoForm" method="POST" action="<?= BASE_URL ?>/talento-humano/empleado/guardar" enctype="multipart/form-data" data-draft-context="empleado:<?= $modoEdicion ? (int)($e['empleado_id'] ?? $e['id'] ?? 0) : 'nuevo' ?>">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
                            <input type="hidden" name="empId" value="<?= htmlspecialchars($e['empleado_id'] ?? $e['id'] ?? '') ?>">

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
                                                value="<?= htmlspecialchars($e['cedula'] ?? $e['identificacion'] ?? '') ?>"
                                                placeholder="Ej: 1308126646"
                                                autocomplete="off"
                                                <?= $modoEdicion ? 'data-empid="' . (int)($e['empleado_id'] ?? $e['id'] ?? 0) . '"' : '' ?>>
                                            <?php if (!$modoEdicion): ?>
                                            <!-- Alerta de cédula duplicada (solo en creación) -->
                                            <div id="alertaCedulaDuplicada" style="display:none;margin-top:6px;padding:10px 14px;border-radius:8px;background:#fef3c7;border:1px solid #f59e0b;color:#92400e;font-size:0.85rem;line-height:1.4">
                                                <strong><i class="bi bi-exclamation-triangle-fill" style="color:#d97706"></i> Funcionario ya registrado</strong><br>
                                                <span id="alertaCedulaNombre"></span><br>
                                                <small id="alertaCedulaCargo" style="opacity:0.8"></small>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="field span-2">
                                            <label for="apellidos">Apellidos <span class="required">*</span></label>
                                            <input type="text" id="apellidos" name="apellidos" required
                                                value="<?= htmlspecialchars($e['apellidos'] ?? '') ?>"
                                                placeholder="Ej: PEREZ ZAMBRANO" autocomplete="family-name">
                                        </div>
                                        <div class="field span-2">
                                            <label for="nombres">Nombres <span class="required">*</span></label>
                                            <input type="text" id="nombres" name="nombres" required
                                                value="<?= htmlspecialchars($e['nombres'] ?? '') ?>"
                                                placeholder="Ej: JUAN CARLOS" autocomplete="given-name">
                                        </div>
                                        <div class="field span-2">
                                            <label for="fecha_nac">Fecha de nacimiento</label>
                                            <input type="date" id="fecha_nac" name="fecha_nac"
                                                value="<?= htmlspecialchars($e['fecha_nacimiento'] ?? $e['fecha_nac'] ?? '') ?>"
                                                onchange="evaluarDiscapacidad()">
                                        </div>
                                        <div class="field span-2">
                                            <label for="condicion_especial">Condicion Especial</label>
                                            <select id="condicion_especial" name="condicion_especial"
                                                onchange="evaluarDiscapacidad()">
                                                <?php foreach (['Ninguna', 'Tercera Edad', 'Discapacidad', 'Ambas', 'Sustituto'] as $opt): ?>
                                                    <option value="<?= $opt ?>" <?= ($e['condicion_especial'] ?? '') === $opt ? 'selected' : '' ?>>
                                                        <?= $opt === 'Tercera Edad' ? 'Tercera Edad (65+)' : ($opt === 'Ambas' ? 'Tercera Edad y Discapacidad' : ($opt === 'Sustituto' ? 'Sustituto por cuidado de persona con discapacidad' : $opt)) ?>
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
                                            <label for="genero">Género</label>
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
                                            <label style="display:flex;align-items:center;gap:7px">Nacionalidad
                                                <button type="button" onclick="agregarNacionalidad()" title="Agregar nacionalidad adicional" aria-label="Agregar nacionalidad adicional" style="width:25px;height:25px;border-radius:50%;border:1px solid var(--teal-500);background:#fff;color:var(--teal-500);display:grid;place-items:center;cursor:pointer"><i class="bi bi-plus-lg"></i></button>
                                            </label>
                                            <input type="hidden" id="nacionalidadPrincipal" name="nacionalidad" value="<?= htmlspecialchars($e['nacionalidad'] ?? '') ?>">
                                            <datalist id="listaNacionalidades">
                                                <?php foreach($catalogoNacionalidades as $n): ?>
                                                    <option value="<?= htmlspecialchars($n['nombre']) ?>" data-id="<?= (int)$n['nacionalidad_id'] ?>"><?= htmlspecialchars($n['pais']) ?></option>
                                                    <?php if(($n['codigo_iso']??'')==='EC'): ?><option value="Ecuatoriano" data-id="<?= (int)$n['nacionalidad_id'] ?>">Ecuador - Ecuatoriana</option><?php endif; ?>
                                                <?php endforeach; ?>
                                            </datalist>
                                            <div id="contenedorNacionalidades">
                                                <?php $filasNac=$nacSeleccionadas ?: [['nacionalidad_id'=>'','nombre'=>$e['nacionalidad']??'']]; foreach($filasNac as $idx=>$nac): ?>
                                                <div class="input-group-nac" style="display:flex;gap:6px;margin-bottom:6px">
                                                    <input type="text" list="listaNacionalidades" class="inputs-nacionalidad" value="<?= htmlspecialchars($nac['nombre']??'') ?>" placeholder="Escriba o despliegue nacionalidades" autocomplete="off" style="flex:1" oninput="sincronizarNacionalidad(this)">
                                                    <input type="hidden" name="nacionalidad_ids[]" value="<?= (int)($nac['nacionalidad_id']??0) ?>">
                                                    <button type="button" onclick="abrirNacionalidades(this)" title="Ver nacionalidades" style="width:38px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;cursor:pointer"><i class="bi bi-chevron-down"></i></button>
                                                    <?php if($idx>0): ?><button type="button" onclick="this.parentElement.remove()" title="Eliminar" style="width:38px;border:1px solid #ef4444;border-radius:8px;background:#fff;color:#ef4444;cursor:pointer"><i class="bi bi-trash"></i></button><?php endif; ?>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <small>Puede buscar por país o gentilicio, con texto completo o parcial.</small>
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
                                            <div class="label-with-action">
                                                <label for="unidad_id">Departamento / Dirección <span class="required">*</span></label>
                                                <?php if(Auth::can('maestros','crear')): ?><button type="button" class="quick-add-button" onclick="abrirCatalogoRapido('unidad')" title="Crear dirección o departamento" aria-label="Crear dirección o departamento"><i class="bi bi-plus-lg"></i></button><?php endif; ?>
                                            </div>
                                            <select id="unidad_id" name="unidad_id" required data-searchable-select data-search-placeholder="Buscar dirección o área…">
                                                <option value="">Seleccione dirección...</option>
                                                <?php
                                                $areasDisp = $areas ?? [];
                                                foreach ($areasDisp as $area):
                                                    $selArea = ((string)($e['unidad_id'] ?? '') === (string)$area['unidad_id']) ? 'selected' : '';
                                                    $areaNombre = trim((string)($area['nombre_unidad'] ?? ''));
                                                    $areaPadre = trim((string)($area['direccion_padre'] ?? ''));
                                                    $areaEtiqueta = $areaPadre !== '' ? $areaPadre.' / '.$areaNombre : $areaNombre;
                                                ?>
                                                    <option value="<?= htmlspecialchars($area['unidad_id']) ?>" <?= $selArea ?>>
                                                        <?= htmlspecialchars($areaEtiqueta) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field span-3">
                                            <div class="label-with-action">
                                                <label for="puesto_id">Cargo / Puesto <span class="required">*</span></label>
                                                <?php if(Auth::can('maestros','crear')): ?><button type="button" class="quick-add-button" onclick="abrirCatalogoRapido('puesto')" title="Crear cargo o puesto" aria-label="Crear cargo o puesto"><i class="bi bi-plus-lg"></i></button><?php endif; ?>
                                            </div>
                                            <select id="puesto_id" name="puesto_id" required data-searchable-select data-search-placeholder="Buscar cargo o puesto…">
                                                <option value="">Seleccione cargo...</option>
                                                <?php
                                                $cargosDisp = $cargos ?? [];
                                                foreach ($cargosDisp as $puesto):
                                                    $selPuesto = ((string)($e['puesto_id'] ?? '') === (string)$puesto['puesto_id']) ? 'selected' : '';
                                                ?>
                                                    <option value="<?= htmlspecialchars($puesto['puesto_id']) ?>" data-rmu="<?= htmlspecialchars((string)($puesto['remuneracion_unificada'] ?? 0)) ?>" <?= $selPuesto ?>>
                                                        <?= htmlspecialchars($puesto['nombre_puesto']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field span-2">
                                            <label for="regimen_laboral">Régimen laboral <span class="required">*</span></label>
                                            <select id="regimen_laboral" name="regimen_laboral" required>
                                                <option value="LOSEP" <?= $regimenLaboral === 'LOSEP' ? 'selected' : '' ?>>LOSEP</option>
                                                <option value="CODIGO_TRABAJO" <?= $regimenLaboral === 'CODIGO_TRABAJO' ? 'selected' : '' ?>>Código del Trabajo</option>
                                            </select>
                                            <small>Define el régimen jurídico y la plantilla documental aplicable.</small>
                                        </div>
                                        <div class="field span-2" id="campo_accion_personal_permitida">
                                            <label for="accion_personal_permitida">Acción de personal permitida</label>
                                            <input id="accion_personal_permitida" type="text" value="Sí — formato LOSEP completo" readonly>
                                        </div>
                                        <div class="field span-2">
                                            <label for="tipo_contrato">Tipo de contrato / nombramiento <span class="required">*</span></label>
                                            <select id="tipo_contrato" name="tipo_contrato" required
                                                    data-current-value="<?= htmlspecialchars((string)($e['tipo_contrato'] ?? '')) ?>">
                                            </select>
                                            <small id="ayuda_tipo_contrato"></small>
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
                                            <label for="estado_laboral_lectura">Estado del funcionario</label>
                                            <input id="estado_laboral_lectura" type="text" readonly
                                                value="<?= (int)($e['estado'] ?? 1) === 1 ? 'Activo / En funciones' : 'Inactivo / Desvinculado' ?>">
                                            <small>El estado se modifica únicamente mediante alta, baja, reingreso o Acción de Personal.</small>
                                        </div>
                                        <div class="field span-3">
                                            <label for="jornada">Jornada base contractual</label>
                                            <select id="jornada" name="jornada">
                                                <?php foreach (['Completa', 'Parcial', 'Rotativa', 'Especial'] as $j): ?>
                                                    <option value="<?= $j ?>" <?= ($e['jornada'] ?? 'Completa') === $j ? 'selected' : '' ?>><?= $j ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field span-2">
                                            <label for="horas_jornada">Horas base diarias</label>
                                            <input type="number" id="horas_jornada" name="horas_jornada" min="1" max="24" step="0.5"
                                                value="<?= htmlspecialchars((string)($e['horas_jornada'] ?? (($e['jornada'] ?? 'Completa') === 'Completa' ? 8 : ''))) ?>" placeholder="8">
                                            <small id="ayuda_horas_jornada">La jornada completa utiliza 8 horas. Las excepciones temporales se registran mediante Acción de Personal.</small>
                                        </div>
                                        <div class="field span-2">
                                            <label for="proceso_institucional">Proceso institucional</label>
                                            <input type="text" id="proceso_institucional" name="proceso_institucional" maxlength="150" value="<?= htmlspecialchars($e['proceso_institucional'] ?? '') ?>" placeholder="Ej: Adjetivo de apoyo">
                                        </div>
                                        <div class="field span-2">
                                            <label for="nivel_gestion">Nivel de gestión</label>
                                            <input type="text" id="nivel_gestion" name="nivel_gestion" maxlength="150" value="<?= htmlspecialchars($e['nivel_gestion'] ?? '') ?>" placeholder="Ej: Dirección">
                                        </div>
                                        <div class="field span-2">
                                            <label for="lugar_trabajo">Lugar de trabajo</label>
                                            <input type="text" id="lugar_trabajo" name="lugar_trabajo" maxlength="150" value="<?= htmlspecialchars($e['lugar_trabajo'] ?? 'Manta') ?>" placeholder="Ej: Manta">
                                        </div>
                                        <div class="field span-2">
                                            <label for="grupo_ocupacional">Grupo ocupacional</label>
                                            <input type="text" id="grupo_ocupacional" name="grupo_ocupacional" maxlength="150" value="<?= htmlspecialchars($e['grupo_ocupacional'] ?? '') ?>">
                                        </div>
                                        <div class="field span-2">
                                            <label for="grado_laboral">Grado</label>
                                            <input type="text" id="grado_laboral" name="grado_laboral" maxlength="50" value="<?= htmlspecialchars($e['grado_laboral'] ?? $e['grado'] ?? '') ?>">
                                        </div>
                                        <div class="field span-2">
                                            <label for="partida_individual">Partida individual</label>
                                            <input type="text" id="partida_individual" name="partida_individual" maxlength="100" value="<?= htmlspecialchars($e['partida_individual'] ?? '') ?>">
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
                                        <div class="field span-2">
                                            <label for="ciudad_residencia">Ciudad de residencia <span class="required">*</span></label>
                                            <input type="text" id="ciudad_residencia" name="ciudad_residencia" required
                                                value="<?= htmlspecialchars($e['ciudad_residencia'] ?? '') ?>"
                                                placeholder="Ej: Manta">
                                        </div>
                                        <div class="field span-2">
                                            <label for="correo">Correo institucional</label>
                                            <input type="email" id="correo" name="correo"
                                                value="<?= htmlspecialchars($e['correo'] ?? '') ?>"
                                                placeholder="usuario@puertodemanta.gob.ec">
                                        </div>
                                        <div class="field span-2">
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

    <?php if (Auth::can('maestros', 'crear')):
        $catalogoRapidoConfig = [
            'areas' => $areas ?? [],
            'unidadSelectId' => 'unidad_id',
            'puestoSelectId' => 'puesto_id',
            'rmuTargetId' => 'sueldo',
        ];
        require ROOT.'/shared/catalogo_rapido.php';
    endif; ?>

    <script>
        /* Inicializar estado del bloque discapacidad al cargar en modo edición */
        window.addEventListener('DOMContentLoaded', () => {
            evaluarDiscapacidad();
            inicializarRegimenLaboral();
        });

        const CONTRATOS_POR_REGIMEN = Object.freeze({
            LOSEP: ['Nombramiento Permanente', 'Nombramiento Provisional', 'Contrato Ocasional'],
            CODIGO_TRABAJO: ['Contrato Indefinido']
        });

        function aplicarRegimenLaboral(conservarSeleccion = true) {
            const regimen = document.getElementById('regimen_laboral');
            const contrato = document.getElementById('tipo_contrato');
            const permitido = document.getElementById('accion_personal_permitida');
            const ayuda = document.getElementById('ayuda_tipo_contrato');
            if (!regimen || !contrato) return;
            const codigoTrabajo = regimen.value === 'CODIGO_TRABAJO';
            const anterior = conservarSeleccion ? (contrato.value || contrato.dataset.currentValue || '') : '';
            const opciones = CONTRATOS_POR_REGIMEN[regimen.value] || CONTRATOS_POR_REGIMEN.LOSEP;
            contrato.innerHTML = opciones.map(valor => `<option value="${valor}">${valor}</option>`).join('');
            contrato.value = opciones.includes(anterior) ? anterior : opciones[0];
            contrato.disabled = codigoTrabajo;
            if (permitido) permitido.value = codigoTrabajo
                ? 'No — no se genera Acción de Personal'
                : 'Sí — formato LOSEP completo';
            ayuda.textContent = codigoTrabajo
                ? 'Se asigna Contrato Indefinido y se habilita el Formulario Abreviado Laboral.'
                : 'Las novedades se documentan mediante la Acción de Personal LOSEP completa.';
        }

        function inicializarRegimenLaboral() {
            const regimen = document.getElementById('regimen_laboral');
            const contrato = document.getElementById('tipo_contrato');
            if (!regimen || !contrato) return;
            aplicarRegimenLaboral(true);
            regimen.addEventListener('change', () => aplicarRegimenLaboral(false));
        }

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

        function agregarNacionalidad() {
            const contenedor = document.getElementById('contenedorNacionalidades');
            if (!contenedor) return;
            const div = document.createElement('div');
            div.className = 'input-group-nac';
            div.style.cssText = 'display:flex; gap:6px; margin-bottom:6px;';
            div.innerHTML = `
                <input type="text" list="listaNacionalidades" class="inputs-nacionalidad" placeholder="Escriba o despliegue nacionalidades" autocomplete="off" style="flex:1" oninput="sincronizarNacionalidad(this)">
                <input type="hidden" name="nacionalidad_ids[]" value="0">
                <button type="button" onclick="abrirNacionalidades(this)" title="Ver nacionalidades" style="width:38px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;cursor:pointer"><i class="bi bi-chevron-down"></i></button>
                <button type="button" onclick="this.parentElement.remove()" title="Eliminar" style="width:38px;background:#fff;border:1px solid #ef4444;color:#ef4444;border-radius:8px;cursor:pointer">
                    <i class="bi bi-trash"></i>
                </button>`;
            contenedor.appendChild(div);
            div.querySelector('input[list]').focus();
        }

        function sincronizarNacionalidad(input) {
            const normaliza=v=>String(v||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLocaleLowerCase('es').trim();
            const option=[...document.querySelectorAll('#listaNacionalidades option')].find(o=>normaliza(o.value)===normaliza(input.value));
            input.parentElement.querySelector('input[type="hidden"]').value=option?.dataset.id||'0';
        }

        function abrirNacionalidades(button) {
            const input=button.parentElement.querySelector('input[list]');input.focus();
            if(typeof input.showPicker==='function') input.showPicker();
        }

        document.getElementById('empleadoForm')?.addEventListener('submit', event => {
            const cedula = document.getElementById('cedula');
            if (!cedula?.value.trim()) {
                event.preventDefault();
                cedula?.setCustomValidity('Debe ingresar el número de cédula o pasaporte antes de guardar.');
                cedula?.reportValidity();
                cedula?.focus();
                showToast?.('La cédula o pasaporte es obligatorio; el formulario no fue guardado.', 'error');
                return;
            }
            cedula.setCustomValidity('');
            const regimen = document.getElementById('regimen_laboral');
            const contrato = document.getElementById('tipo_contrato');
            if (regimen?.value === 'CODIGO_TRABAJO' && contrato) {
                contrato.disabled = false;
                contrato.value = 'Contrato Indefinido';
            }
            // Bloquear envio si la cédula está marcada como existente (solo en creación)
            const alertaDiv = document.getElementById('alertaCedulaDuplicada');
            if (alertaDiv && alertaDiv.style.display !== 'none') {
                event.preventDefault();
                showToast?.('La cédula ingresada ya pertenece a un funcionario registrado. No se puede continuar.', 'error');
                document.getElementById('cedula')?.focus();
                return;
            }
            const filas=[...document.querySelectorAll('#contenedorNacionalidades .input-group-nac')];
            for(const fila of filas){
                const texto=fila.querySelector('input[list]').value.trim();
                const id=parseInt(fila.querySelector('input[type="hidden"]').value||'0',10);
                if(texto!==''&&!id){event.preventDefault();showToast?.('Seleccione cada nacionalidad desde la lista desplegable.','error');return;}
            }
            document.getElementById('nacionalidadPrincipal').value=filas[0]?.querySelector('input[list]')?.value?.trim()||'';
        });
        document.getElementById('cedula')?.addEventListener('input', function () {
            this.setCustomValidity('');
        });

        <?php if (!$modoEdicion): ?>
        /* ──────────────────────────────────────────────────────────────────
           Validación AJAX de cédula duplicada en tiempo real (solo creación)
           ────────────────────────────────────────────────────────────────── */
        (function () {
            const camposCedula = document.getElementById('cedula');
            const alertaDiv    = document.getElementById('alertaCedulaDuplicada');
            const alertaNombre = document.getElementById('alertaCedulaNombre');
            const alertaCargo  = document.getElementById('alertaCedulaCargo');
            const btnGuardar   = document.querySelector('#empleadoForm button[type="submit"]');
            let debounceTimer  = null;
            let abortCtrl      = null;

            if (!camposCedula || !alertaDiv) return;

            function ocultarAlerta() {
                alertaDiv.style.display = 'none';
                if (btnGuardar) { btnGuardar.disabled = false; btnGuardar.style.opacity = ''; }
            }

            function mostrarAlerta(nombre, cargo, area) {
                alertaNombre.textContent = nombre;
                alertaCargo.textContent  = (cargo ? cargo : '') + (area ? ' — ' + area : '');
                alertaDiv.style.display  = 'block';
                if (btnGuardar) { btnGuardar.disabled = true; btnGuardar.style.opacity = '0.5'; }
            }

            camposCedula.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                if (abortCtrl) { abortCtrl.abort(); }
                const val = this.value.trim();

                if (val.length < 10) {
                    ocultarAlerta();
                    return;
                }

                debounceTimer = setTimeout(() => {
                    abortCtrl = new AbortController();
                    const url = `<?= BASE_URL ?>/talento-humano/empleado/verificar-cedula?cedula=${encodeURIComponent(val)}`;
                    fetch(url, { signal: abortCtrl.signal })
                        .then(r => r.json())
                        .then(data => {
                            if (data.existe) {
                                mostrarAlerta(data.nombre, data.cargo, data.area);
                                showToast?.(`Cédula ya registrada: ${data.nombre}`, 'error');
                            } else {
                                ocultarAlerta();
                            }
                        })
                        .catch(err => { if (err.name !== 'AbortError') ocultarAlerta(); });
                }, 600);
            });
        })();
        <?php endif; ?>
    </script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>

</html>
