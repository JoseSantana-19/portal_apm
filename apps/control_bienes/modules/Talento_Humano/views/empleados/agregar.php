<?php /* formulario.php – Vista: formulario de 5 pestañas para crear/editar empleados */
$e = $empleado ?? [];  // alias corto
$modo = $modoEdicion ? 'EDICION' : 'CREACION';
$tituloForm = $modoEdicion ? 'Modificar expediente' : 'Registrar nuevo funcionario';
$iconoForm = $modoEdicion ? 'bi-pencil-square' : 'bi-person-badge';
?>
<div class="content-shell">
    <div style="margin-bottom: 20px;">
        <a href="index.php?route=talento_directorio" class="btn btn-outline" style="text-decoration:none; display:inline-flex; align-items:center; gap:8px;">
            <i class="bi bi-arrow-left"></i> Volver al directorio
        </a>
    </div>

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
                <i class="bi bi-mortarboard"></i> Formación <span class="tab-badge">4</span>
            </button>
            <button class="tab-btn" id="tab-obs" onclick="switchTab('obs')" role="tab"
                aria-selected="false">
                <i class="bi bi-chat-left-text"></i> Notas <span class="tab-badge">5</span>
            </button>
        </div>

        <div class="card-header form-header">
            <div>
                <h3><i class="bi <?= $iconoForm ?>"></i> <?= $tituloForm ?></h3>
                <p>Complete, edite y valide el expediente del servidor público.</p>
            </div>
            <span class="badge <?= $modoEdicion ? 'badge-edit' : 'badge-create' ?>">MODO:
                <?= $modo ?></span>
        </div>

        <!-- 🛠️ BANNER MODO SIMULACIÓN -->
        <?php if (!$modoEdicion): ?>
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;
                    background:linear-gradient(135deg,rgba(16,180,199,.1),rgba(99,102,241,.08));
                    border:1px solid rgba(16,180,199,.3); border-radius:12px;
                    padding:14px 18px; margin:0 20px 20px;">
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

        <form method="POST" action="index.php?route=talento_guardar" enctype="multipart/form-data" style="padding: 20px;">
            <input type="hidden" name="empId" value="<?= htmlspecialchars($e['empleado_id'] ?? $e['id'] ?? '') ?>">

            <!-- ── TAB 1: PERSONAL ── -->
            <div class="form-tab-panel active" id="panel-personal" role="tabpanel">

                <!-- BLOQUE FOTO DEL EMPLEADO -->
                <div class="foto-upload-block">
                    <div class="foto-preview-wrap">
                        $rutaFoto = $e['ruta_foto'] ?? '';
                        $fotoSrc = (!empty($rutaFoto) && file_exists(ROOT_PATH . $rutaFoto))
                                   ? htmlspecialchars($rutaFoto)
                                   : "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2394a3b8'><path d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/></svg>";
                        ?>
                        <img id="fotoPreview"
                             src="<?= $fotoSrc ?>"
                             alt="Foto del funcionario"
                             class="foto-preview-img"
                             onerror="this.onerror=null; this.src=&quot;data:image/svg+xml;utf8,&lt;svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2394a3b8'&gt;&lt;path d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/&gt;&lt;/svg&gt;&quot;;">
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
                        <label for="foto" class="btn btn-outline" style="cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="bi bi-upload"></i> Seleccionar imagen
                        </label>
                        <span id="fotoNombreArchivo" class="foto-nombre-archivo">Ningún archivo seleccionado</span>
                        <?php if (!empty($e['ruta_foto'])): ?>
                            <span class="foto-actual-badge" style="color: green; font-size: 11px; margin-left: 10px;"><i class="bi bi-check-circle-fill"></i> Foto actual guardada</span>
                        <?php endif; ?>
                    </div>
                </div>
                <fieldset>
                    <legend class="section-title"><i class="bi bi-person-vcard"></i> Información personal</legend>
                    <div class="form-grid">
                        <div class="field span-2">
                            <label for="cedula">Cédula / Pasaporte <span class="required">*</span></label>
                            <input type="text" id="cedula" name="cedula" required
                                pattern="[0-9A-Za-z-]{10,15}"
                                value="<?= htmlspecialchars($e['cedula'] ?? '') ?>"
                                placeholder="Ej: 1308126646">
                        </div>
                        <div class="field span-4">
                            <label for="nombres">Apellidos y nombres completos <span class="required">*</span></label>
                            <input type="text" id="nombres" name="nombres" required
                                value="<?= htmlspecialchars(($e['apellidos'] ?? '') . ' ' . ($e['nombres'] ?? '')) ?>"
                                placeholder="Ej: PEREZ ZAMBRANO JUAN CARLOS">
                        </div>
                        <div class="field span-2">
                            <label for="fecha_nac">Fecha de nacimiento</label>
                            <?php
                            $fechaNac = '';
                            if (!empty($e['fecha_nac'])) {
                                $fechaNac = date('Y-m-d', strtotime($e['fecha_nac']));
                            }
                            ?>
                            <input type="date" id="fecha_nac" name="fecha_nac"
                                value="<?= $fechaNac ?>"
                                onchange="evaluarDiscapacidad()">
                        </div>
                        <div class="field span-2">
                            <label for="condicion_especial">Condición Especial</label>
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
                        <div class="disability-sub span-6" id="sub_bloque_discapacidad" style="display: none;">
                            <div class="field">
                                <label for="tipo_discapacidad">Tipo de Discapacidad <span class="required">*</span></label>
                                <select id="tipo_discapacidad" name="tipo_discapacidad">
                                    <option value="">Seleccione tipo...</option>
                                    <?php foreach (['Fisica' => 'Fisica / Motriz', 'Auditiva' => 'Auditiva', 'Visual' => 'Visual', 'Intelectual' => 'Intelectual', 'Psicosocial' => 'Psicosocial'] as $val => $label): ?>
                                        <option value="<?= $val ?>" <?= ($e['tipo_discapacidad'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="porcentaje_discapacidad">Grado (%) <span class="required">*</span></label>
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
                                <?php foreach (['Masculino', 'Femenino', 'Otros'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ($e['genero'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field span-2">
                            <label for="estado_civil">Estado civil</label>
                            <select id="estado_civil" name="estado_civil">
                                <option value="">Seleccione...</option>
                                <?php foreach (['Soltero/a', 'Casado/a', 'Divorciado/a', 'Viudo/a', 'Unión de hecho'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ($e['estado_civil'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field span-2">
                            <label for="sangre">Grupo sanguíneo</label>
                            <select id="sangre" name="sangre">
                                <option value="">Seleccione...</option>
                                <?php foreach (['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ($e['sangre'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Campo Dinámico: Doble Nacionalidad -->
                        <div class="field span-6">
                            <label style="display:flex; justify-content:space-between; align-items:center;">
                                <span>Nacionalidad(es) <span class="required">*</span></span>
                                <button type="button" class="btn btn-outline" onclick="agregarNacionalidad()"
                                        style="font-size:.78rem; padding:4px 8px; border-radius:6px;">
                                    <i class="bi bi-plus-circle"></i> Agregar doble nacionalidad
                                </button>
                            </label>
                            <div id="contenedorNacionalidades">
                                <div class="input-group-nac" style="display:flex; gap:8px; margin-bottom:6px;">
                                    <input type="text" name="nacionalidad" class="inputs-nacionalidad" required
                                           value="<?= htmlspecialchars($e['nacionalidad'] ?? 'Ecuatoriana') ?>"
                                           placeholder="Ej: Ecuatoriana" style="flex:1;">
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>

            <!-- ── TAB 2: LABORAL ── -->
            <div class="form-tab-panel" id="panel-laboral" role="tabpanel">
                <fieldset>
                    <legend class="section-title"><i class="bi bi-briefcase"></i> Relación institucional</legend>
                    <div class="form-grid">
                        <div class="field span-3">
                            <label for="unidad_id">Área / Dirección Orgánica <span class="required">*</span></label>
                            <select id="unidad_id" name="unidad_id" required>
                                <option value="">Seleccione área...</option>
                                <?php foreach ($areas as $a): ?>
                                    <option value="<?= $a['unidad_id'] ?>" <?= (int)($e['unidad_id'] ?? 0) === (int)$a['unidad_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a['nombre_unidad']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field span-3">
                            <label for="puesto_id">Puesto / Cargo Nominal <span class="required">*</span></label>
                            <select id="puesto_id" name="puesto_id" required>
                                <option value="">Seleccione cargo...</option>
                                <?php foreach ($cargos as $c): ?>
                                    <option value="<?= $c['puesto_id'] ?>" <?= (int)($e['puesto_id'] ?? 0) === (int)$c['puesto_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nombre_puesto']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field span-2">
                            <label for="tipo_contrato">Tipo de contrato</label>
                            <select id="tipo_contrato" name="tipo_contrato">
                                <option value="">Seleccione...</option>
                                <?php foreach (['Nombramiento', 'Contrato', 'Pasante'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ($e['tipo_contrato'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field span-2">
                            <label for="fecha_ingreso">Fecha de ingreso</label>
                            <?php
                            $fechaIng = '';
                            if (!empty($e['fecha_ingreso'])) {
                                $fechaIng = date('Y-m-d', strtotime($e['fecha_ingreso']));
                            }
                            ?>
                            <input type="date" id="fecha_ingreso" name="fecha_ingreso"
                                value="<?= $fechaIng ?>">
                        </div>
                        <div class="field span-2">
                            <label for="sueldo">Remuneración Mensual ($)</label>
                            <input type="number" id="sueldo" name="sueldo" step="0.01" min="0"
                                value="<?= htmlspecialchars($e['sueldo'] ?? '') ?>"
                                placeholder="Ej: 986.00">
                        </div>
                        <div class="field span-2">
                            <label for="jornada">Jornada de Trabajo</label>
                            <select id="jornada" name="jornada">
                                <?php foreach (['Completa', 'Parcial', 'Especial / Turnos'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ($e['jornada'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field span-2">
                            <label for="estado">Estado Laboral</label>
                            <select id="estado" name="estado">
                                <option value="1" <?= (int)($e['estado'] ?? 1) === 1 ? 'selected' : '' ?>>ACTIVO</option>
                                <option value="0" <?= (int)($e['estado'] ?? 1) === 0 ? 'selected' : '' ?>>INACTIVO / DESVINCULADO</option>
                            </select>
                        </div>
                    </div>
                </fieldset>
            </div>

            <!-- ── TAB 3: CONTACTO ── -->
            <div class="form-tab-panel" id="panel-contacto" role="tabpanel">
                <fieldset>
                    <legend class="section-title"><i class="bi bi-geo-alt"></i> Datos de contacto</legend>
                    <div class="form-grid">
                        <div class="field span-3">
                            <label for="correo">Correo institucional / personal</label>
                            <input type="email" id="correo" name="correo"
                                value="<?= htmlspecialchars($e['correo'] ?? '') ?>"
                                placeholder="ejemplo@puertodemanta.gob.ec">
                        </div>
                        <div class="field span-3">
                            <label for="telefono">Teléfono celular</label>
                            <input type="tel" id="telefono" name="telefono"
                                value="<?= htmlspecialchars($e['telefono'] ?? '') ?>"
                                placeholder="Ej: 0998877665">
                        </div>
                        <div class="field span-3">
                            <label for="telefono_convencional">Teléfono convencional</label>
                            <input type="tel" id="telefono_convencional" name="telefono_convencional"
                                value="<?= htmlspecialchars($e['telefono_convencional'] ?? '') ?>"
                                placeholder="Ej: 052621111">
                        </div>
                        <div class="field span-3">
                            <label for="ciudad_residencia">Ciudad de residencia <span class="required">*</span></label>
                            <input type="text" id="ciudad_residencia" name="ciudad_residencia" required
                                value="<?= htmlspecialchars($e['ciudad_residencia'] ?? 'Manta') ?>"
                                placeholder="Ej: Manta">
                        </div>
                        <div class="field span-6">
                            <label for="direccion">Dirección domiciliaria completa</label>
                            <input type="text" id="direccion" name="direccion"
                                value="<?= htmlspecialchars($e['direccion'] ?? '') ?>"
                                placeholder="Ej: Av. 24 y Calle 15, Barrio Córdova">
                        </div>
                    </div>
                </fieldset>
                <fieldset style="margin-top:20px;">
                    <legend class="section-title"><i class="bi bi-telephone-outbound"></i> Contacto de Emergencia</legend>
                    <div class="form-grid">
                        <div class="field span-3">
                            <label for="contacto_emergencia">Nombre del contacto</label>
                            <input type="text" id="contacto_emergencia" name="contacto_emergencia"
                                value="<?= htmlspecialchars($e['contacto_emergencia'] ?? '') ?>"
                                placeholder="Ej: María Zambrano">
                        </div>
                        <div class="field span-3">
                            <label for="emergencia_relacion">Parentesco / Relación</label>
                            <input type="text" id="emergencia_relacion" name="emergencia_relacion"
                                value="<?= htmlspecialchars($e['emergencia_relacion'] ?? '') ?>"
                                placeholder="Ej: Cónyuge, Madre...">
                        </div>
                        <div class="field span-3">
                            <label for="tel_emergencia">Teléfono de emergencia</label>
                            <input type="tel" id="tel_emergencia" name="tel_emergencia"
                                value="<?= htmlspecialchars($e['tel_emergencia'] ?? '') ?>"
                                placeholder="Ej: 0990000000">
                        </div>
                    </div>
                </fieldset>
            </div>

            <!-- ── TAB 4: FORMACION ── -->
            <div class="form-tab-panel" id="panel-formacion" role="tabpanel">
                <fieldset>
                    <legend class="section-title"><i class="bi bi-mortarboard"></i> Educación y Formación</legend>
                    <div class="form-grid">
                        <div class="field span-2">
                            <label for="nivel_estudio">Nivel de estudios</label>
                            <select id="nivel_estudio" name="nivel_estudio">
                                <option value="">Seleccione...</option>
                                <?php foreach (['Bachiller', 'Tercer Nivel / Tecnólogo', 'Tercer Nivel / Licenciado / Ing', 'Cuarto Nivel / Maestría', 'PhD / Doctorado'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ($e['nivel_estudio'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field span-4">
                            <label for="titulo">Título Obtenido / Carrera</label>
                            <input type="text" id="titulo" name="titulo"
                                value="<?= htmlspecialchars($e['titulo'] ?? '') ?>"
                                placeholder="Ej: Ingeniero en Sistemas, Abogado...">
                        </div>
                        <div class="field span-3">
                            <label for="iess">Código IESS / Afiliación</label>
                            <input type="text" id="iess" name="iess"
                                value="<?= htmlspecialchars($e['iess'] ?? '') ?>"
                                placeholder="Ej: 1308126646001">
                        </div>
                    </div>
                </fieldset>
            </div>

            <!-- ── TAB 5: NOTAS ── -->
            <div class="form-tab-panel" id="panel-obs" role="tabpanel">
                <fieldset>
                    <legend class="section-title"><i class="bi bi-chat-left-text"></i> Observaciones Generales</legend>
                    <div class="field">
                        <textarea id="observaciones" name="observaciones" rows="8"
                                  placeholder="Registre novedades, notas de contratación, advertencias de auditoría o cualquier información complementaria sobre el expediente laboral del funcionario..."><?= htmlspecialchars($e['observaciones'] ?? '') ?></textarea>
                    </div>
                </fieldset>
            </div>

            <div class="form-actions" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px;">
                <a href="index.php?route=talento_directorio" class="btn btn-outline" style="text-decoration:none;">Cancelar</a>
                <button type="submit" class="btn btn-primary" style="background: var(--primary-color, #0f172a); border: none; color: white;">
                    <i class="bi bi-save"></i> Guardar expediente
                </button>
            </div>
        </form>
    </section>
</div>

<script>
function switchTab(tabId) {
    const panels = document.querySelectorAll('.form-tab-panel');
    const tabs = document.querySelectorAll('.tab-btn');
    panels.forEach(p => p.classList.remove('active'));
    tabs.forEach(t => {
        t.classList.remove('active');
        t.setAttribute('aria-selected', 'false');
    });
    
    document.getElementById('panel-' + tabId).classList.add('active');
    const tabBtn = document.getElementById('tab-' + tabId);
    tabBtn.classList.add('active');
    tabBtn.setAttribute('aria-selected', 'true');
}

function previewFoto(input) {
    const preview = document.getElementById('fotoPreview');
    const label = document.getElementById('fotoNombreArchivo');
    const file = input.files[0];
    if (file) {
        label.textContent = file.name;
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; };
        reader.readAsDataURL(file);
        if (typeof showToast === 'function') {
            showToast('Imagen lista. Guarde el formulario para confirmar.', 'info');
        }
    }
}

function evaluarDiscapacidad() {
    const condicion = document.getElementById('condicion_especial').value;
    const subBloque = document.getElementById('sub_bloque_discapacidad');
    const tipoInput = document.getElementById('tipo_discapacidad');
    const pctInput = document.getElementById('porcentaje_discapacidad');
    
    if (condicion === 'Discapacidad' || condicion === 'Ambas') {
        subBloque.style.display = 'flex';
        tipoInput.required = true;
        pctInput.required = true;
    } else {
        subBloque.style.display = 'none';
        tipoInput.required = false;
        pctInput.required = false;
        tipoInput.value = '';
        pctInput.value = '';
    }
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

const mockDataFuncionario = {
    cedula:           '1308126646',
    nombres:          'PEREZ ZAMBRANO JUAN CARLOS',
    fecha_nac:        '1985-10-15',
    condicion_especial: 'Ninguna',
    genero:           'Masculino',
    estado_civil:     'Casado/a',
    sangre:           'O+',
    nacionalidades:   ['Ecuatoriana', 'Española']
};

function simularFormularioPrincipal() {
    switchTab('personal');
    const set = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
    set('cedula',  mockDataFuncionario.cedula);
    set('nombres', mockDataFuncionario.nombres);
    set('fecha_nac', mockDataFuncionario.fecha_nac);
    set('condicion_especial', mockDataFuncionario.condicion_especial);
    set('genero',             mockDataFuncionario.genero);
    set('estado_civil',       mockDataFuncionario.estado_civil);
    set('sangre',             mockDataFuncionario.sangre);
    
    evaluarDiscapacidad();
    
    const contenedor = document.getElementById('contenedorNacionalidades');
    if (contenedor) {
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
    }
    
    if (typeof showToast === 'function') {
        showToast('✅ Datos de prueba cargados.', 'success');
    } else {
        alert('✅ Datos de prueba cargados.');
    }
}

// Disparar en la carga inicial si hay datos
document.addEventListener('DOMContentLoaded', () => {
    evaluarDiscapacidad();
});
</script>
