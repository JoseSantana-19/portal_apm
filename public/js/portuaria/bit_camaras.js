(function () {
    'use strict';

    const API_URL = 'bitacoras/camara/api';

    const $ = (id) => document.getElementById(id);

    const state = {
        editId: 0,
        orden: 'ASC',
        items: [],
        itemsBase: [],
        camaras: [],
        motivos: [],
        motivoAutoTexto: '',
        catalogosEstados: {
            tipo_registro: [],
            estado_camara: [],
            nivel_alerta: []
        }
    };

    const TIPO_ACTIVIDAD_DIARIA = '102';
    const TIPO_NOVEDAD_CAMARA = '103';

    const sugerenciasActividadBase = [
        'Monitoreo general de cámaras durante el turno.',
        'Revisión de cámaras del área administrativa.',
        'Verificación de accesos principales mediante CCTV.',
        'Supervisión de cámaras operativas durante el turno.',
        'Turno sin novedades relevantes en el sistema CCTV.',
        'Coordinación con personal de Seguridad Operativa.',
        'Verificación de grabación en equipos NVR / DVR.',
        'Revisión visual de áreas críticas del terminal portuario.',
        'Seguimiento preventivo a cámaras de acceso y salida.',
        'Control visual de ingreso y salida de personal y proveedores.'
    ];

    function toast(message, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            alert(message);
        }
    }

    function debounce(fn, delay = 450) {
        let timer = null;

        return function (...args) {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => fn.apply(this, args), delay);
        };
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }


    function normalizarTextoBusqueda(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .toLowerCase()
            .trim();
    }

    function truncarTexto(value, max = 120) {
        const texto = String(value || '');
        return texto.length > max ? `${texto.slice(0, max - 1)}…` : texto;
    }

    function insertarEnTextarea(textarea, texto) {
        const inicio = typeof textarea.selectionStart === 'number' ? textarea.selectionStart : textarea.value.length;
        const fin = typeof textarea.selectionEnd === 'number' ? textarea.selectionEnd : textarea.value.length;
        const valor = textarea.value;

        textarea.value = `${valor.slice(0, inicio)}${texto}${valor.slice(fin)}`;
        textarea.focus();

        const posicion = inicio + texto.length;

        if (textarea.setSelectionRange) {
            textarea.setSelectionRange(posicion, posicion);
        }
    }

    function obtenerSugerenciasActividad() {
        const vistas = new Set();
        const salida = [];

        function agregar(texto) {
            const limpio = String(texto || '').trim();
            const clave = normalizarTextoBusqueda(limpio);

            if (!limpio || vistas.has(clave)) {
                return;
            }

            vistas.add(clave);
            salida.push(limpio);
        }

        (state.itemsBase.length ? state.itemsBase : state.items)
            .filter((item) => !esTipoNovedad(item.tipo_registro))
            .forEach((item) => agregar(item.novedad));

        sugerenciasActividadBase.forEach(agregar);

        return salida;
    }

    function filtrarSugerenciasActividad(valor) {
        const termino = normalizarTextoBusqueda(valor);
        const sugerencias = obtenerSugerenciasActividad();

        if (!termino) {
            return sugerencias.slice(0, 10);
        }

        return sugerencias
            .filter((texto) => normalizarTextoBusqueda(texto).includes(termino))
            .slice(0, 10);
    }

    function cerrarSugerenciasActividad() {
        const panel = $('bcActividadSug');

        if (panel) {
            panel.classList.remove('apm-open');
            panel.setAttribute('aria-hidden', 'true');
        }
    }

    function renderSugerenciasActividad() {
        const textarea = $('bcNovedad');
        const panel = $('bcActividadSug');

        if (!textarea || !panel || !esActividadDiaria()) {
            cerrarSugerenciasActividad();
            return;
        }

        if (document.activeElement !== textarea) {
            cerrarSugerenciasActividad();
            return;
        }

        const sugerencias = filtrarSugerenciasActividad(textarea.value);

        panel.innerHTML = '';

        if (!sugerencias.length) {
            cerrarSugerenciasActividad();
            return;
        }

        sugerencias.forEach((texto) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action text-start';
            item.title = texto;
            item.innerHTML = `
                <span class="d-block">${escapeHtml(truncarTexto(texto, 120))}</span>
                ${texto.length > 120 ? '<span class="text-muted">Texto completo al elegir</span>' : ''}
            `;

            item.addEventListener('mousedown', (event) => {
                event.preventDefault();
            });

            item.addEventListener('click', () => {
                insertarEnTextarea(textarea, texto);
                cerrarSugerenciasActividad();
            });

            panel.appendChild(item);
        });

        panel.classList.add('apm-open');
        panel.setAttribute('aria-hidden', 'false');
    }

    const renderSugerenciasActividadDebounced = debounce(renderSugerenciasActividad, 120);

    function configurarSugerenciasActividad() {
        const textarea = $('bcNovedad');
        const panel = $('bcActividadSug');

        if (!textarea || !panel) {
            return;
        }

        textarea.addEventListener('input', renderSugerenciasActividadDebounced);
        textarea.addEventListener('focus', renderSugerenciasActividadDebounced);

        textarea.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                cerrarSugerenciasActividad();
            }
        });

        document.addEventListener('click', (event) => {
            const wrap = $('bcActividadWrap');

            if (wrap && !wrap.contains(event.target)) {
                cerrarSugerenciasActividad();
            }
        });
    }

    function fechaEs(fecha) {
        if (!fecha) return '';

        const partes = fecha.split('-');

        if (partes.length !== 3) {
            return fecha;
        }

        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    }

    function horaCorta(hora) {
        if (!hora) return '';
        return String(hora).slice(0, 5);
    }

    function horaActualInput() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');

        return `${h}:${m}`;
    }

    function normalizarEstado(estado) {
        const valorOriginal = String(estado ?? '').trim();
        const valor = valorOriginal.toUpperCase();

        if (valorOriginal === '100' || valor === 'NO OPER' || valor === 'NO OPERATIVA') {
            return 'NO OPERATIVA';
        }

        if (valorOriginal === '101' || valor === 'OPER' || valor === 'OPERATIVA') {
            return 'OPERATIVA';
        }

        return '';
    }

    function estadoCodigo(estado) {
        const normalizado = normalizarEstado(estado);

        if (normalizado === 'NO OPERATIVA') {
            return '100';
        }

        if (normalizado === 'OPERATIVA') {
            return '101';
        }

        return '';
    }

    function normalizarNivel(nivel) {
        const valor = String(nivel || '').toUpperCase().trim();

        if (valor === '106' || valor === 'CRITICO' || valor === 'CRÍTICO') {
            return 'Crítico';
        }

        if (valor === '105' || valor === 'MEDIO') {
            return 'Medio';
        }

        return 'Normal';
    }

    function nivelCodigo(nivel) {
        const normalizado = normalizarNivel(nivel);

        if (normalizado === 'Crítico') {
            return '106';
        }

        if (normalizado === 'Medio') {
            return '105';
        }

        return '104';
    }

    function normalizarTipoRegistroCodigo(tipo) {
        const valor = String(tipo ?? '').toUpperCase().trim();

        if (valor === '103' || valor === '201' || valor === 'NOVEDAD_CAMARA' || valor === 'NOVEDAD DE CÁMARA' || valor === 'NOVEDAD DE CAMARA') {
            return TIPO_NOVEDAD_CAMARA;
        }

        return TIPO_ACTIVIDAD_DIARIA;
    }

    function esTipoNovedad(tipo) {
        return normalizarTipoRegistroCodigo(tipo) === TIPO_NOVEDAD_CAMARA;
    }

    function esTipoActividad(tipo) {
        return normalizarTipoRegistroCodigo(tipo) === TIPO_ACTIVIDAD_DIARIA;
    }

    function tipoRegistroActual() {
        return normalizarTipoRegistroCodigo($('bcTipoRegistro')?.value || TIPO_ACTIVIDAD_DIARIA);
    }

    function esActividadDiaria() {
        return tipoRegistroActual() === TIPO_ACTIVIDAD_DIARIA;
    }

    function esNovedadCamara() {
        return tipoRegistroActual() === TIPO_NOVEDAD_CAMARA;
    }

    function textoTipoRegistro(tipo) {
        return esTipoNovedad(tipo) ? 'Novedad de cámara' : 'Actividad diaria';
    }

    function tipoRegistroBadge(tipo) {
        if (esTipoNovedad(tipo)) {
            return '<span class="badge text-bg-warning text-dark">Novedad de cámara</span>';
        }

        return '<span class="badge text-bg-info text-dark">Actividad diaria</span>';
    }

    function catalogosEstadosFallback() {
        return {
            tipo_registro: [
                { idestado: 102, texto: 'Actividad diaria', descripcion: 'ACTIVIDAD_DIARIA' },
                { idestado: 103, texto: 'Novedad de cámara', descripcion: 'NOVEDAD_CAMARA' }
            ],
            estado_camara: [
                { idestado: 101, texto: 'OPERATIVA', descripcion: 'OPERATIVA' },
                { idestado: 100, texto: 'NO OPERATIVA', descripcion: 'NO OPERATIVA' }
            ],
            nivel_alerta: [
                { idestado: 104, texto: 'Normal', descripcion: 'NORMAL' },
                { idestado: 105, texto: 'Medio', descripcion: 'MEDIO' },
                { idestado: 106, texto: 'Crítico', descripcion: 'CRITICO' }
            ]
        };
    }

    function normalizarCatalogoEstado(items, fallbackItems) {
        const entrada = Array.isArray(items) ? items : [];
        const salida = entrada
            .map((item) => ({
                idestado: Number(item.idestado || item.id || item.value || 0),
                texto: String(item.texto || item.descripcion || item.label || '').trim(),
                descripcion: String(item.descripcion || item.texto || '').trim(),
                detalle: String(item.detalle || '').trim()
            }))
            .filter((item) => item.idestado > 0 && item.texto !== '');

        return salida.length ? salida : fallbackItems;
    }

    function normalizarCatalogosEstados(data) {
        const fallback = catalogosEstadosFallback();

        return {
            tipo_registro: normalizarCatalogoEstado(data?.tipo_registro, fallback.tipo_registro),
            estado_camara: normalizarCatalogoEstado(data?.estado_camara, fallback.estado_camara),
            nivel_alerta: normalizarCatalogoEstado(data?.nivel_alerta, fallback.nivel_alerta)
        };
    }

    function setOpcionesSelect(selectId, items, config = {}) {
        const select = $(selectId);

        if (!select) {
            return;
        }

        const valorActual = String(select.value || '').trim();
        const valorDefecto = String(config.defaultValue || '').trim();
        const incluirTodos = Boolean(config.includeAll);
        const textoTodos = config.allText || 'Todos';

        select.innerHTML = '';

        if (incluirTodos) {
            const optTodos = document.createElement('option');
            optTodos.value = '';
            optTodos.textContent = textoTodos;
            select.appendChild(optTodos);
        }

        items.forEach((item) => {
            const opt = document.createElement('option');
            opt.value = String(item.idestado);
            opt.textContent = item.texto;

            if (item.detalle) {
                opt.title = item.detalle;
            }

            select.appendChild(opt);
        });

        const valores = Array.from(select.options).map((opt) => opt.value);

        if (valorActual && valores.includes(valorActual)) {
            select.value = valorActual;
        } else if (valorDefecto && valores.includes(valorDefecto)) {
            select.value = valorDefecto;
        } else if (select.options.length) {
            select.selectedIndex = 0;
        }
    }

    function cargarSelectsEstadosDesdeCatalogo() {
        const catalogos = normalizarCatalogosEstados(state.catalogosEstados);
        state.catalogosEstados = catalogos;

        setOpcionesSelect('bcTipoRegistro', catalogos.tipo_registro, { defaultValue: TIPO_ACTIVIDAD_DIARIA });
        setOpcionesSelect('bcFiltroTipo', catalogos.tipo_registro, { includeAll: true, allText: 'Todos', defaultValue: '' });
        setOpcionesSelect('bcEstadoCamara', catalogos.estado_camara, { defaultValue: '101' });
        setOpcionesSelect('bcNivelAlerta', catalogos.nivel_alerta, { defaultValue: '104' });
    }


    function filtroTipoRegistroActual() {
        const valor = String($('bcFiltroTipo')?.value || '').trim();
        return valor ? normalizarTipoRegistroCodigo(valor) : '';
    }

    function aplicarFiltroTipoRegistros(items) {
        const tipo = filtroTipoRegistroActual();

        if (!tipo) {
            return items;
        }

        return items.filter((item) => normalizarTipoRegistroCodigo(item.tipo_registro) === tipo);
    }

    function nivelRequiereDetalle() {
        const nivel = normalizarNivel($('bcNivelAlerta')?.value || 'Normal');

        return esNovedadCamara() && (nivel === 'Medio' || nivel === 'Crítico');
    }

    function obtenerMotivoSeleccionado() {
        const id = String($('bcMotivoCamara')?.value || '');

        if (!id) {
            return null;
        }

        return state.motivos.find((motivo) => String(motivo.id_motivo_camara) === id) || null;
    }

    function textoMotivo(item) {
        if (!item) {
            return '';
        }

        return item.motivo_descripcion || item.descripcion || '';
    }

    function renderMotivosSelect(idSeleccionado = '') {
        const select = $('bcMotivoCamara');

        if (!select) {
            return;
        }

        const selected = String(idSeleccionado || select.value || '');

        select.innerHTML = '<option value="">Seleccione un motivo...</option>' + state.motivos.map((motivo) => {
            const codigo = motivo.codigo_motivo ? `${motivo.codigo_motivo} - ` : '';
            const nivel = motivo.nivel_sugerido ? ` (${motivo.nivel_sugerido})` : '';
            const text = `${codigo}${motivo.descripcion}${nivel}`;
            const value = String(motivo.id_motivo_camara);

            return `<option value="${escapeHtml(value)}" ${value === selected ? 'selected' : ''}>${escapeHtml(text)}</option>`;
        }).join('');
    }

    function aplicarMotivoSeleccionado() {
        const motivo = obtenerMotivoSeleccionado();

        if (!motivo) {
            if ($('bcNovedad') && state.motivoAutoTexto && $('bcNovedad').value.trim() === state.motivoAutoTexto) {
                $('bcNovedad').value = '';
            }

            state.motivoAutoTexto = '';
            actualizarCamposAlerta();
            return;
        }

        const nivelSugerido = normalizarNivel(motivo.nivel_sugerido || 'Medio');
        const textoMotivo = String(motivo.descripcion || '').trim();

        if ($('bcNivelAlerta') && nivelSugerido !== 'Normal') {
            $('bcNivelAlerta').value = nivelCodigo(nivelSugerido);
        }

        if ($('bcNovedad') && textoMotivo) {
            const textoActual = $('bcNovedad').value.trim();

            // Si el campo está vacío, o si contenía el motivo anterior autocompletado,
            // se reemplaza automáticamente por el nuevo motivo seleccionado.
            if (!textoActual || textoActual === state.motivoAutoTexto) {
                $('bcNovedad').value = textoMotivo;
                state.motivoAutoTexto = textoMotivo;
            }
        }

        actualizarCamposAlerta();
    }

    function limpiarDatosCamaraSeleccionada() {
        if ($('bcIdCamara')) $('bcIdCamara').value = '';
        if ($('bcCamaraIp')) $('bcCamaraIp').value = '';
        if ($('bcUbicacion')) $('bcUbicacion').value = '';
        if ($('bcSitio')) $('bcSitio').value = '';
        if ($('bcInvGrabador')) $('bcInvGrabador').value = '';
        if ($('bcInvCodOld')) $('bcInvCodOld').value = '';
        if ($('bcInvCodigo')) $('bcInvCodigo').value = '';
        if ($('bcInvTipo')) $('bcInvTipo').value = '';
        if ($('bcInvMarca')) $('bcInvMarca').value = '';

        if (window.jQuery && $('bcCamaraSelect')) {
            window.jQuery('#bcCamaraSelect').val(null).trigger('change');
        } else if ($('bcCamaraSelect')) {
            $('bcCamaraSelect').value = '';
        }
    }

    function actualizarCamposAlerta() {
        const actividad = esActividadDiaria();
        const novedadCamara = esNovedadCamara();
        const nivel = normalizarNivel($('bcNivelAlerta')?.value || 'Normal');
        const requiereDetalle = nivelRequiereDetalle();

        document.querySelectorAll('.bc-bloque-camara').forEach((campo) => {
            campo.classList.toggle('d-none', actividad);
        });

        document.querySelectorAll('.bc-campo-alerta').forEach((campo) => {
            campo.classList.toggle('d-none', !requiereDetalle);
        });

        if ($('bcPanelAlerta')) {
            $('bcPanelAlerta').classList.toggle('d-none', !requiereDetalle);
        }

        if ($('bcCamaraSelect')) {
            $('bcCamaraSelect').required = novedadCamara;
            $('bcCamaraSelect').disabled = actividad;
        }

        if ($('bcEstadoCamara')) {
            $('bcEstadoCamara').disabled = actividad;
            if (actividad) {
                $('bcEstadoCamara').value = '101';
            } else if (!$('bcEstadoCamara').value) {
                $('bcEstadoCamara').value = '101';
            }
        }

        // No forzar el nivel de alerta en Actividad diaria.
        // Antes se reiniciaba a Normal cada vez que se cambiaba el select,
        // por eso no permitía seleccionar Medio o Crítico en actividades diarias.

        if ($('bcMotivoCamara')) {
            $('bcMotivoCamara').required = requiereDetalle;
            $('bcMotivoCamara').disabled = !requiereDetalle;

            if (!requiereDetalle) {
                $('bcMotivoCamara').value = '';
            }
        }

        if ($('bcNovedad')) {
            $('bcNovedad').required = true;
            $('bcNovedad').disabled = false;

            if (actividad) {
                $('bcNovedad').placeholder = 'Describa la actividad u observación...';
                $('bcNovedad').classList.add('apm-cctv-textarea-actividad');
            } else {
                $('bcNovedad').placeholder = 'Describa novedades, detalles u observaciones de la cámara durante el turno...';
                $('bcNovedad').classList.remove('apm-cctv-textarea-actividad');
            }
        }

        if ($('bcNovedadWrap')) {
            $('bcNovedadWrap').classList.remove('col-md-8');
            $('bcNovedadWrap').classList.add('col-12');

            if (novedadCamara) {
                $('bcNovedadWrap').classList.add('col-md-8');
            }
        }

        if ($('bcTipoRegistroWrap')) {
            $('bcTipoRegistroWrap').classList.remove('col-md-6', 'col-md-4');
            $('bcTipoRegistroWrap').classList.add('col-12', 'col-sm-6', 'col-lg-3');
        }

        if ($('bcSugerenciasActividadInfo')) {
            $('bcSugerenciasActividadInfo').classList.toggle('d-none', !actividad);
        }

        if (!actividad) {
            cerrarSugerenciasActividad();
        }

        if ($('bcNovedadLabel')) {
            $('bcNovedadLabel').textContent = actividad ? 'Actividad' : 'Novedades/Detalles u observaciones';
        }

        if ($('bcNovedadHelp')) {
            $('bcNovedadHelp').textContent = actividad
                ? 'Registre actividades de monitoreo, revisión, coordinación o supervisión del turno.'
                : 'Describa novedades, detalles u observaciones de la cámara seleccionada.';
        }

        if ($('bcObservaciones')) {
            // Se conserva oculto por compatibilidad con el API, pero ya no se muestra ni se exige.
            if ($('bcObservaciones')) {
            $('bcObservaciones').value = '';
        }
            $('bcObservaciones').required = false;
        }

        if (actividad) {
            limpiarDatosCamara();
        }
    }

    function textoCamara(camara) {
        if (!camara) {
            return '';
        }

        if (camara.texto) {
            return camara.texto;
        }

        const partes = [
            camara.ip,
            camara.detalle,
            camara.ubicacion,
            camara.tipo,
            camara.marca,
            camara.modelo,
            camara.cod_old ? `Cod. Antiguo: ${camara.cod_old}` : '',
            camara.codigo ? `Código: ${camara.codigo}` : ''
        ].filter(Boolean);

        return partes.join(' - ');
    }

    function guardarCamarasEnEstado(items = []) {
        items.forEach((camara) => {
            if (!camara || !camara.id_camara) {
                return;
            }

            const id = String(camara.id_camara);
            const index = state.camaras.findIndex((item) => String(item.id_camara) === id);

            if (index >= 0) {
                state.camaras[index] = {
                    ...state.camaras[index],
                    ...camara
                };
            } else {
                state.camaras.push(camara);
            }
        });
    }

    function limpiarDatosCamara() {
        if ($('bcIdCamara')) $('bcIdCamara').value = '';

        if ($('bcCamaraSelect')) {
            const select = $('bcCamaraSelect');
            select.value = '';

            if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && jQuery(select).data('select2')) {
                jQuery(select).val(null).trigger('change.select2');
            }
        }

        if ($('bcCamaraIp')) $('bcCamaraIp').value = '';
        if ($('bcUbicacion')) $('bcUbicacion').value = '';
        if ($('bcSitio')) $('bcSitio').value = '';
        if ($('bcInvGrabador')) $('bcInvGrabador').value = '';
        if ($('bcInvCodOld')) $('bcInvCodOld').value = '';
        if ($('bcInvCodigo')) $('bcInvCodigo').value = '';
        if ($('bcInvTipo')) $('bcInvTipo').value = '';
        if ($('bcInvMarca')) $('bcInvMarca').value = '';
    }

    function llenarDatosCamara(camara) {
        if (!camara) {
            limpiarDatosCamara();
            return false;
        }

        $('bcIdCamara').value = camara.id_camara || '';
        $('bcCamaraIp').value = camara.ip || '';
        $('bcUbicacion').value = camara.ubicacion || '';
        $('bcSitio').value = camara.detalle || camara.sitio || '';
        $('bcInvGrabador').value = camara.grabador || camara.inv_grabador || '';
        $('bcInvCodOld').value = camara.cod_old || camara.inv_cod_old || '';
        $('bcInvCodigo').value = camara.codigo || camara.inv_codigo || '';
        $('bcInvTipo').value = camara.tipo || camara.inv_tipo || '';
        $('bcInvMarca').value = [
            camara.marca || camara.inv_marca || '',
            camara.modelo || camara.inv_modelo || ''
        ].filter(Boolean).join(' / ');

        return true;
    }

    function renderCamaras() {
        const select = $('bcCamaraSelect');

        if (!select) {
            return;
        }

        if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && jQuery(select).data('select2')) {
            if (!select.value) {
                select.innerHTML = '<option value=""></option>';
            }
            return;
        }

        if (!state.camaras.length) {
            select.innerHTML = '<option value="">No hay cámaras encontradas...</option>';
            return;
        }

        select.innerHTML = '<option value="">Buscar cámara...</option>' + state.camaras.map((camara) => `
            <option value="${camara.id_camara}">
                ${escapeHtml(textoCamara(camara))}
            </option>
        `).join('');
    }

    async function cargarCamaras(q = '') {
        try {
            const data = await apiGet({
                action: 'buscar_camaras',
                q
            });

            guardarCamarasEnEstado(data.data || []);
            renderCamaras();
        } catch (error) {
            toast(error.message, 'error');
        }
    }

    function seleccionarCamaraEnControl(camara) {
        const select = $('bcCamaraSelect');

        if (!select || !camara || !camara.id_camara) {
            return;
        }

        const id = String(camara.id_camara);
        const texto = textoCamara(camara);

        if (!Array.from(select.options).some((option) => option.value === id)) {
            select.appendChild(new Option(texto, id, true, true));
        }

        select.value = id;

        if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && jQuery(select).data('select2')) {
            jQuery(select).val(id).trigger('change.select2');
        }
    }

    function seleccionarCamaraPorId(idCamara) {
        const id = String(idCamara || '');

        if (!id) {
            limpiarDatosCamara();
            return false;
        }

        const camara = state.camaras.find((item) => String(item.id_camara) === id);

        if (!camara) {
            return false;
        }

        seleccionarCamaraEnControl(camara);
        llenarDatosCamara(camara);

        return true;
    }

    function plantillaResultadoCamara(item) {
        if (item.loading) {
            return item.text;
        }

        const camara = item.camara || state.camaras.find((row) => String(row.id_camara) === String(item.id)) || {};
        const ip = camara.ip || 'Sin IP';
        const detalle = [camara.detalle, camara.ubicacion].filter(Boolean).join(' / ');
        const equipo = [camara.tipo, camara.marca, camara.modelo].filter(Boolean).join(' - ');
        const codigo = [
            camara.cod_old ? `Cod. Antiguo: ${camara.cod_old}` : '',
            camara.codigo ? `Código: ${camara.codigo}` : ''
        ].filter(Boolean).join(' | ');

        return jQuery(`
            <div class="apm-cctv-camara-option">
                <div class="apm-cctv-camara-ip">${escapeHtml(ip)}</div>
                <div>${escapeHtml(detalle || item.text || '')}</div>
                <div class="apm-cctv-camara-meta">${escapeHtml([equipo, codigo].filter(Boolean).join(' · '))}</div>
            </div>
        `);
    }

    function inicializarSelectorCamara() {
        const select = $('bcCamaraSelect');

        if (!select) {
            return;
        }

        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            const $select = jQuery(select);

            if ($select.data('select2')) {
                return;
            }

            $select.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: select.dataset.placeholder || 'Buscar cámara...',
                allowClear: true,
                minimumInputLength: 0,
                dropdownCssClass: 'apm-cctv-select2-dropdown',
                language: {
                    inputTooShort: function () {
                        return 'Escriba para buscar la cámara...';
                    },
                    searching: function () {
                        return 'Buscando cámaras...';
                    },
                    noResults: function () {
                        return 'No se encontraron cámaras.';
                    },
                    errorLoading: function () {
                        return 'No se pudieron cargar las cámaras.';
                    }
                },
                ajax: {
                    url: API_URL,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            action: 'buscar_camaras',
                            q: params.term || ''
                        };
                    },
                    processResults: function (data) {
                        const items = data && data.ok !== false ? (data.data || []) : [];

                        guardarCamarasEnEstado(items);

                        return {
                            results: items.map((camara) => ({
                                id: String(camara.id_camara),
                                text: textoCamara(camara),
                                camara
                            }))
                        };
                    },
                    cache: true
                },
                templateResult: plantillaResultadoCamara,
                templateSelection: function (item) {
                    return item.text || '';
                },
                escapeMarkup: function (markup) {
                    return markup;
                }
            });

            $select.on('select2:select', function (event) {
                const data = event.params && event.params.data ? event.params.data : null;
                const camara = data?.camara || state.camaras.find((item) => String(item.id_camara) === String(data?.id || select.value));

                if (camara) {
                    guardarCamarasEnEstado([camara]);
                    seleccionarCamaraEnControl(camara);
                    llenarDatosCamara(camara);
                }
            });

            $select.on('select2:clear', function () {
                limpiarDatosCamara();
            });

            return;
        }

        select.addEventListener('change', function () {
            seleccionarCamaraPorId(this.value);
        });
    }

    async function apiGet(params) {
        const url = `${API_URL}?${new URLSearchParams(params).toString()}`;

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json().catch(() => null);

        if (!response.ok || !data || data.ok === false) {
            throw new Error(data?.message || 'No se pudo procesar la solicitud.');
        }

        return data;
    }

    async function apiPost(payload) {
        const formData = new FormData();

        Object.keys(payload).forEach((key) => {
            formData.append(key, payload[key] ?? '');
        });

        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json().catch(() => null);

        if (!response.ok || !data || data.ok === false) {
            throw new Error(data?.message || 'No se pudo guardar la información.');
        }

        return data;
    }

    async function cargarMotivos() {
        try {
            const data = await apiGet({
                action: 'motivos'
            });

            state.motivos = data.data || [];
            renderMotivosSelect();
        } catch (error) {
            state.motivos = [];
            renderMotivosSelect();
            toast(error.message, 'error');
        }
    }

    function aplicarHorasPorTurno() {
        const turno = $('bcTurno')?.value || '';

        if (turno === 'Mañana') {
            $('bcHoraInicio').value = '07:00';
            $('bcHoraFin').value = '15:00';
            return;
        }

        if (turno === 'Tarde') {
            $('bcHoraInicio').value = '15:00';
            $('bcHoraFin').value = '23:00';
            return;
        }

        if (turno === 'Noche') {
            $('bcHoraInicio').value = '23:00';
            $('bcHoraFin').value = '07:00';
        }
    }

    async function cargarContexto() {
        try {
            const data = await apiGet({
                action: 'context'
            });

            if (data.catalogos_estados) {
                state.catalogosEstados = normalizarCatalogosEstados(data.catalogos_estados);
                cargarSelectsEstadosDesdeCatalogo();
            } else {
                cargarSelectsEstadosDesdeCatalogo();
            }

            $('bcUsuario').value = data.usuario?.nombres || 'Usuario Seguridad Integral';
            $('bcFechaServidor').value = fechaEs(data.fecha_servidor || '');
            $('bcHoraServidor').value = data.hora_servidor || '';
            $('bcFecha').value = data.fecha_operativa || data.fecha_servidor || '';
            $('bcFiltroFecha').value = data.fecha_operativa || data.fecha_servidor || '';
            $('bcTurno').value = data.turno || 'Mañana';
            $('bcFiltroTurno').value = data.turno || '';
            $('bcHoraInicio').value = horaCorta(data.hora_inicio || '07:00');
            $('bcHoraFin').value = horaCorta(data.hora_fin || '15:00');
            $('bcConsolista').value = data.usuario?.nombres || 'Usuario Seguridad Integral';
            $('bcSecuencia').value = data.secuencia || '';
            $('bcHoraRegistro').value = horaActualInput();

            await cargarMotivos();
            await cargarCamaras('');
            await listarRegistros();
        } catch (error) {
            toast(error.message, 'error');
        }
    }

    function limpiarFormularioDetalle() {
        state.editId = 0;

        $('bcId').value = '';
        $('bcIdCamara').value = '';
        $('bcHoraRegistro').value = horaActualInput();

        if ($('bcTipoRegistro')) {
            $('bcTipoRegistro').value = TIPO_ACTIVIDAD_DIARIA;
        }

        if ($('bcRolResponsable')) {
            $('bcRolResponsable').value = 'Consolista';
        }

        limpiarDatosCamara();

        $('bcNovedad').value = '';
        state.motivoAutoTexto = '';
        $('bcEstadoCamara').value = '101';

        if ($('bcNivelAlerta')) {
            $('bcNivelAlerta').value = '104';
        }

        if ($('bcMotivoCamara')) {
            $('bcMotivoCamara').value = '';
        }

        if ($('bcObservaciones')) {
            $('bcObservaciones').value = '';
        }

        actualizarCamposAlerta();

        $('bcBtnGuardar').innerHTML = '<i class="bi bi-save me-1"></i>Guardar';
        $('bcBtnCancelar').classList.add('d-none');
    }

    function obtenerPayloadFormulario() {
        return {
            action: 'guardar',
            id_bitacora_camara: $('bcId').value || '',
            id_camara: $('bcIdCamara') ? $('bcIdCamara').value || '' : '',
            tipo_registro: $('bcTipoRegistro') ? tipoRegistroActual() : TIPO_ACTIVIDAD_DIARIA,
            rol_responsable: $('bcRolResponsable') ? $('bcRolResponsable').value || 'Consolista' : 'Consolista',
            fecha: $('bcFecha').value,
            secuencia: $('bcSecuencia').value.trim(),
            turno: $('bcTurno').value,
            hora_inicio: $('bcHoraInicio').value,
            hora_fin: $('bcHoraFin').value,
            consolista: $('bcConsolista').value.trim(),
            hora_registro: $('bcHoraRegistro').value,
            id_motivo_camara: $('bcMotivoCamara') ? $('bcMotivoCamara').value || '' : '',
            novedad: $('bcNovedad').value.trim(),
            camara_ip: $('bcCamaraIp').value.trim(),
            ubicacion: $('bcUbicacion').value.trim(),
            sitio: $('bcSitio').value.trim(),
            estado_camara: estadoCodigo($('bcEstadoCamara').value),
            nivel_alerta: $('bcNivelAlerta') ? nivelCodigo($('bcNivelAlerta').value) : '104',
            observaciones: $('bcObservaciones') ? $('bcObservaciones').value.trim() : ''
        };
    }

    function validarFormulario(payload) {
        if (!payload.fecha) {
            toast('Seleccione la fecha del reporte.', 'error');
            return false;
        }

        if (!payload.turno) {
            toast('Seleccione el turno.', 'error');
            return false;
        }

        if (!payload.hora_registro) {
            toast('Ingrese la hora del registro.', 'error');
            return false;
        }

        if (!payload.tipo_registro) {
            toast('Seleccione el tipo de registro.', 'error');
            return false;
        }

        if (esTipoActividad(payload.tipo_registro)) {
            if (!payload.novedad) {
                toast('Ingrese la actividad diaria realizada durante el turno.', 'error');
                return false;
            }

            return true;
        }

        if (!payload.id_camara) {
            toast('Seleccione una cámara del inventario para registrar la novedad.', 'error');
            return false;
        }

        if (!payload.estado_camara) {
            toast('Seleccione si la cámara está OPERATIVA o NO OPERATIVA.', 'error');
            return false;
        }

        if (!payload.nivel_alerta) {
            toast('Seleccione el nivel de alerta.', 'error');
            return false;
        }

        if (!payload.novedad) {
            toast('Ingrese la actividad o las novedades/detalles u observaciones de la cámara.', 'error');
            return false;
        }

        return true;
    }

    async function guardarRegistro() {
        const payload = obtenerPayloadFormulario();

        if (!validarFormulario(payload)) {
            return;
        }

        try {
            $('bcBtnGuardar').disabled = true;

            const data = await apiPost(payload);

            toast(data.message || 'Registro guardado correctamente.', 'success');
            limpiarFormularioDetalle();

            $('bcFiltroFecha').value = $('bcFecha').value;
            $('bcFiltroTurno').value = $('bcTurno').value;

            await listarRegistros();
        } catch (error) {
            toast(error.message, 'error');
        } finally {
            $('bcBtnGuardar').disabled = false;
        }
    }

    function estadoBadge(estado) {
        const normalizado = normalizarEstado(estado);

        if (normalizado === 'NO OPERATIVA') {
            return '<span class="badge text-bg-danger">NO OPERATIVA</span>';
        }

        return '<span class="badge text-bg-success">OPERATIVA</span>';
    }

    function nivelBadge(nivel) {
        const normalizado = normalizarNivel(nivel);

        if (normalizado === 'Crítico') {
            return '<span class="badge text-bg-danger">Crítico</span>';
        }

        if (normalizado === 'Medio') {
            return '<span class="badge text-bg-warning text-dark">Medio</span>';
        }

        return '<span class="badge text-bg-success">Normal</span>';
    }

    function renderTabla(items) {
        const tbody = $('bcTablaBody');
        const total = items.length;
        const totalActividades = items.filter((item) => !esTipoNovedad(item.tipo_registro)).length;
        const totalNovedades = items.filter((item) => esTipoNovedad(item.tipo_registro)).length;

        $('bcContador').textContent = `${total} registro(s)`;

        if ($('bcTotalActividades')) {
            $('bcTotalActividades').textContent = `${totalActividades} actividad(es)`;
        }

        if ($('bcTotalNovedades')) {
            $('bcTotalNovedades').textContent = `${totalNovedades} novedad(es)`;
        }

        if (!items.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="text-center text-muted py-4">
                        No hay registros para la fecha seleccionada.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = items.map((item) => {
            const marcaModelo = [item.inv_marca, item.inv_modelo].filter(Boolean).join(' / ');
            const codigoCamara = [item.inv_codigo, item.inv_cod_old ? `Old: ${item.inv_cod_old}` : ''].filter(Boolean).join(' · ');
            const camaraResumen = esTipoNovedad(item.tipo_registro)
                ? `<div class="apm-cctv-camara-resumen"><span class="fw-semibold">${escapeHtml(item.camara_ip || 'Sin IP')}</span><small>${escapeHtml([codigoCamara, marcaModelo].filter(Boolean).join(' | ') || 'Cámara seleccionada')}</small></div>`
                : '<span class="text-muted">No aplica</span>';

            return `
                <tr>
                    <td class="fw-semibold text-primary">${escapeHtml(item.codigo_bitacora || '')}</td>
                    <td>${tipoRegistroBadge(item.tipo_registro)}</td>
                    <td>${fechaEs(item.fecha)}</td>
                    <td class="fw-semibold">${escapeHtml(horaCorta(item.hora_registro))}</td>
                    <td>${escapeHtml(textoMotivo(item) || '—')}</td>
                    <td>${escapeHtml(truncarTexto(item.novedad, 150))}</td>
                    <td>${camaraResumen}</td>
                    <td>${escapeHtml(item.ubicacion || '—')}</td>
                    <td class="text-center">${estadoBadge(item.estado_camara)}</td>
                    <td class="text-center">${nivelBadge(item.nivel_alerta)}</td>
                    <td class="text-center apm-no-print">
                        <div class="btn-group btn-group-sm">
                            <button type="button"
                                    class="btn btn-outline-primary bc-btn-editar"
                                    data-id="${item.id_bitacora_camara}"
                                    title="Editar">
                                <i class="bi bi-pencil-square"></i>
                            </button>

                            <button type="button"
                                    class="btn btn-outline-danger bc-btn-eliminar"
                                    data-id="${item.id_bitacora_camara}"
                                    title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function listarRegistros() {
        try {
            const fecha = $('bcFiltroFecha').value || $('bcFecha').value;
            const turno = $('bcFiltroTurno').value || '';
            const q = $('bcFiltroQ').value.trim();

            const data = await apiGet({
                action: 'listar',
                fecha,
                turno,
                q,
                orden: state.orden
            });

            state.itemsBase = data.data || [];
            state.items = aplicarFiltroTipoRegistros(state.itemsBase);
            renderTabla(state.items);
            renderSugerenciasActividad();
        } catch (error) {
            toast(error.message, 'error');
        }
    }

    async function cargarParaEditar(id) {
        try {
            const data = await apiGet({
                action: 'obtener',
                id
            });

            const item = data.data;

            state.editId = item.id_bitacora_camara;

            $('bcId').value = item.id_bitacora_camara;
            $('bcIdCamara').value = item.id_camara || '';

            if ($('bcTipoRegistro')) {
                $('bcTipoRegistro').value = normalizarTipoRegistroCodigo(item.tipo_registro || TIPO_ACTIVIDAD_DIARIA);
            }

            if ($('bcRolResponsable')) {
                $('bcRolResponsable').value = item.rol_responsable || 'Consolista';
            }

            if (item.id_camara && !seleccionarCamaraPorId(item.id_camara)) {
                const camaraEditada = {
                    id_camara: item.id_camara,
                    ip: item.camara_ip || '',
                    ubicacion: item.ubicacion || '',
                    detalle: item.sitio || item.inv_detalle || '',
                    cod_old: item.inv_cod_old || '',
                    codigo: item.inv_codigo || '',
                    tipo: item.inv_tipo || '',
                    marca: item.inv_marca || '',
                    modelo: item.inv_modelo || '',
                    grabador: item.inv_grabador || ''
                };

                guardarCamarasEnEstado([camaraEditada]);
                seleccionarCamaraEnControl(camaraEditada);
                llenarDatosCamara(camaraEditada);
            }

            $('bcFecha').value = item.fecha;
            $('bcSecuencia').value = item.secuencia;
            $('bcTurno').value = item.turno;
            $('bcHoraInicio').value = horaCorta(item.hora_inicio);
            $('bcHoraFin').value = horaCorta(item.hora_fin);
            $('bcConsolista').value = item.consolista;
            $('bcHoraRegistro').value = horaCorta(item.hora_registro);

            if ($('bcMotivoCamara')) {
                renderMotivosSelect(item.id_motivo_camara || '');
                $('bcMotivoCamara').value = item.id_motivo_camara || '';
            }

            $('bcNovedad').value = item.novedad;
            state.motivoAutoTexto = '';
            $('bcEstadoCamara').value = estadoCodigo(item.estado_camara) || '101';

            if ($('bcNivelAlerta')) {
                $('bcNivelAlerta').value = nivelCodigo(item.nivel_alerta);
            }

            if ($('bcObservaciones')) {
                $('bcObservaciones').value = item.observaciones || '';
            }

            actualizarCamposAlerta();

            $('bcBtnGuardar').innerHTML = '<i class="bi bi-check2-square me-1"></i>Actualizar';
            $('bcBtnCancelar').classList.remove('d-none');

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

            toast('Registro cargado para edición.', 'info');
        } catch (error) {
            toast(error.message, 'error');
        }
    }

    async function eliminarRegistro(id) {
        const confirmar = confirm('¿Seguro que desea eliminar este registro de la bitácora?');

        if (!confirmar) {
            return;
        }

        try {
            const data = await apiPost({
                action: 'eliminar',
                id_bitacora_camara: id
            });

            toast(data.message || 'Registro eliminado.', 'success');

            if (String(state.editId) === String(id)) {
                limpiarFormularioDetalle();
            }

            await listarRegistros();
        } catch (error) {
            toast(error.message, 'error');
        }
    }

    function exportarCsv() {
        if (!state.items.length) {
            toast('No hay registros para exportar.', 'info');
            return;
        }

        const fechaReporte = $('bcFiltroFecha').value || $('bcFecha').value || '';
        const turnoFiltro = $('bcFiltroTurno') ? $('bcFiltroTurno').value : '';
        const turnoReporte = turnoFiltro || 'Todos';
        const secuencia = $('bcSecuencia') ? $('bcSecuencia').value.trim() : '';
        const consolista = $('bcConsolista') ? $('bcConsolista').value.trim() : '';
        const fechaGeneracion = new Date().toLocaleString('es-EC');

        const total = state.items.length;
        const totalOper = state.items.filter((item) => normalizarEstado(item.estado_camara) === 'OPERATIVA').length;
        const totalNoOper = state.items.filter((item) => normalizarEstado(item.estado_camara) === 'NO OPERATIVA').length;
        const totalNormal = state.items.filter((item) => normalizarNivel(item.nivel_alerta) === 'Normal').length;
        const totalMedio = state.items.filter((item) => normalizarNivel(item.nivel_alerta) === 'Medio').length;
        const totalCritico = state.items.filter((item) => normalizarNivel(item.nivel_alerta) === 'Crítico').length;
        const totalActividades = state.items.filter((item) => !esTipoNovedad(item.tipo_registro)).length;
        const totalNovedadesCamara = state.items.filter((item) => esTipoNovedad(item.tipo_registro)).length;

        const filas = state.items.map((item, index) => {
            const estado = normalizarEstado(item.estado_camara);
            const nivel = normalizarNivel(item.nivel_alerta);
            const estadoClase = estado === 'NO OPERATIVA' ? 'estado-no-oper' : 'estado-oper';
            const nivelClase = nivel === 'Crítico'
                ? 'nivel-critico'
                : (nivel === 'Medio' ? 'nivel-medio' : 'nivel-normal');

            return `
                <tr>
                    <td class="center">${index + 1}</td>
                    <td class="text">${escapeHtml(item.codigo_bitacora || '')}</td>
                    <td class="text">${escapeHtml(textoTipoRegistro(item.tipo_registro))}</td>
                    <td class="text">${escapeHtml(fechaEs(item.fecha))}</td>
                    <td class="center">${escapeHtml(horaCorta(item.hora_registro))}</td>
                    <td>${escapeHtml(textoMotivo(item) || '')}</td>
                    <td>${escapeHtml(item.novedad || '')}</td>
                    <td class="text">${escapeHtml(item.inv_cod_old || '')}</td>
                    <td class="text">${escapeHtml(item.inv_codigo || '')}</td>
                    <td class="text">${escapeHtml(item.camara_ip || '')}</td>
                    <td>${escapeHtml(item.inv_tipo || '')}</td>
                    <td>${escapeHtml(item.inv_marca || '')}</td>
                    <td>${escapeHtml(item.inv_modelo || '')}</td>
                    <td>${escapeHtml(item.ubicacion || '')}</td>
                    <td>${escapeHtml(item.sitio || '')}</td>
                    <td class="${estadoClase} center">${escapeHtml(estado)}</td>
                    <td class="${nivelClase} center">${escapeHtml(nivel)}</td>
                    <td>${escapeHtml(item.consolista || '')}</td>
                </tr>
            `;
        }).join('');

        const htmlExcel = `
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .titulo-principal {
            background-color: #004d7a;
            color: #ffffff;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            height: 36px;
        }

        .subtitulo {
            background-color: #0077b6;
            color: #ffffff;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            height: 30px;
        }

        .info-label {
            background-color: #d9eaf7;
            color: #000000;
            font-weight: bold;
            border: 1px solid #7f8c8d;
            text-align: center;
        }

        .info-value {
            border: 1px solid #7f8c8d;
            text-align: center;
        }

        .resumen-oper,
        .nivel-normal {
            background-color: #d1fae5;
            color: #047857;
            font-weight: bold;
            border: 1px solid #7f8c8d;
            text-align: center;
        }

        .resumen-no-oper,
        .nivel-critico,
        .estado-no-oper {
            background-color: #fee2e2;
            color: #b91c1c;
            font-weight: bold;
            border: 1px solid #7f8c8d;
            text-align: center;
        }

        .nivel-medio {
            background-color: #fef3c7;
            color: #92400e;
            font-weight: bold;
            border: 1px solid #7f8c8d;
            text-align: center;
        }

        .estado-oper {
            background-color: #d1fae5;
            color: #047857;
            font-weight: bold;
            text-align: center;
        }

        .header {
            background-color: #004d7a;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            border: 1px solid #000000;
        }

        td {
            border: 1px solid #7f8c8d;
            padding: 5px;
            vertical-align: top;
        }

        .center {
            text-align: center;
        }

        .text {
            mso-number-format: "\\@";
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <td colspan="20" class="titulo-principal">
                AUTORIDAD PORTUARIA DE MANTA
            </td>
        </tr>

        <tr>
            <td colspan="20" class="subtitulo">
                DIRECCIÓN DE SEGURIDAD INTEGRAL - REPORTE DIARIO DE CÁMARAS CCTV
            </td>
        </tr>

        <tr>
            <td colspan="20"></td>
        </tr>

        <tr>
            <td class="info-label" colspan="2">Fecha del reporte</td>
            <td class="info-value" colspan="2">${escapeHtml(fechaEs(fechaReporte))}</td>

            <td class="info-label" colspan="2">Secuencia</td>
            <td class="info-value" colspan="2">${escapeHtml(secuencia)}</td>

            <td class="info-label" colspan="2">Turno</td>
            <td class="info-value" colspan="2">${escapeHtml(turnoReporte)}</td>

            <td class="info-label" colspan="2">Total</td>
            <td class="info-value" colspan="6">${total}</td>
        </tr>

        <tr>
            <td class="info-label" colspan="2">Consolista</td>
            <td class="info-value" colspan="4">${escapeHtml(consolista)}</td>

            <td class="info-label" colspan="2">Generado</td>
            <td class="info-value" colspan="4">${escapeHtml(fechaGeneracion)}</td>

            <td class="resumen-oper" colspan="2">OPERATIVA: ${totalOper}</td>
            <td class="resumen-no-oper" colspan="6">NO OPERATIVA: ${totalNoOper}</td>
        </tr>

        <tr>
            <td class="nivel-normal" colspan="6">Normal: ${totalNormal}</td>
            <td class="nivel-medio" colspan="6">Medio: ${totalMedio}</td>
            <td class="nivel-critico" colspan="8">Crítico: ${totalCritico}</td>
        </tr>

        <tr>
            <td class="info-label" colspan="10">Actividades diarias: ${totalActividades}</td>
            <td class="info-label" colspan="10">Novedades de cámara: ${totalNovedadesCamara}</td>
        </tr>

        <tr>
            <td colspan="20"></td>
        </tr>

        <tr>
            <td class="header">N°</td>
            <td class="header">Código bitácora</td>
            <td class="header">Tipo registro</td>
            <td class="header">Fecha</td>
            <td class="header">Hora</td>
            <td class="header">Motivo</td>
            <td class="header">Actividad / Novedad</td>
            <td class="header">Código antiguo</td>
            <td class="header">Código</td>
            <td class="header">Cámara IP</td>
            <td class="header">Tipo</td>
            <td class="header">Marca</td>
            <td class="header">Modelo</td>
            <td class="header">Ubicación</td>
            <td class="header">Detalle</td>
            <td class="header">Estado</td>
            <td class="header">Nivel</td>
            <td class="header">Consolista</td>
        </tr>

        ${filas}
    </table>
</body>
</html>
`;

        const blob = new Blob(['\ufeff' + htmlExcel], {
            type: 'application/vnd.ms-excel;charset=utf-8;'
        });

        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');

        a.href = url;
        a.download = `bitacora_camaras_${fechaReporte || 'reporte'}.xls`;

        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        URL.revokeObjectURL(url);
    }

    function imprimirReporte() {
        if (!state.items.length) {
            toast('No hay registros para imprimir.', 'info');
            return;
        }

        const fechaReporte = $('bcFiltroFecha').value || $('bcFecha').value || '';
        const turnoFiltro = $('bcFiltroTurno') ? $('bcFiltroTurno').value : '';
        const turnoReporte = turnoFiltro || 'Todos';
        const secuencia = $('bcSecuencia') ? $('bcSecuencia').value.trim() : '';
        const consolista = $('bcConsolista') ? $('bcConsolista').value.trim() : '';
        const fechaGeneracion = new Date().toLocaleString('es-EC');

        const baseUrl = window.location.origin + window.location.pathname.replace(/[^/]*$/, '');
        const logoUrl = `${baseUrl}imgs/logoapm.png`;

        const total = state.items.length;
        const totalOper = state.items.filter((item) => normalizarEstado(item.estado_camara) === 'OPERATIVA').length;
        const totalNoOper = state.items.filter((item) => normalizarEstado(item.estado_camara) === 'NO OPERATIVA').length;
        const totalNormal = state.items.filter((item) => normalizarNivel(item.nivel_alerta) === 'Normal').length;
        const totalMedio = state.items.filter((item) => normalizarNivel(item.nivel_alerta) === 'Medio').length;
        const totalCritico = state.items.filter((item) => normalizarNivel(item.nivel_alerta) === 'Crítico').length;
        const totalActividades = state.items.filter((item) => !esTipoNovedad(item.tipo_registro)).length;
        const totalNovedadesCamara = state.items.filter((item) => esTipoNovedad(item.tipo_registro)).length;

        const filas = state.items.map((item, index) => {
            const marcaModelo = [item.inv_marca, item.inv_modelo].filter(Boolean).join(' / ');
            const estado = normalizarEstado(item.estado_camara);
            const nivel = normalizarNivel(item.nivel_alerta);

            return `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td>${escapeHtml(item.codigo_bitacora || '')}</td>
                    <td>${escapeHtml(textoTipoRegistro(item.tipo_registro))}</td>
                    <td>${escapeHtml(fechaEs(item.fecha))}</td>
                    <td>${escapeHtml(horaCorta(item.hora_registro))}</td>
                    <td>${escapeHtml(item.inv_cod_old || '')}</td>
                    <td>${escapeHtml(item.inv_codigo || '')}</td>
                    <td>${escapeHtml(item.camara_ip || '')}</td>
                    <td>${escapeHtml(item.inv_tipo || '')}</td>
                    <td>${escapeHtml(marcaModelo)}</td>
                    <td>${escapeHtml(item.ubicacion || '')}</td>
                    <td>${escapeHtml(item.sitio || '')}</td>
                    <td class="text-center ${estado === 'NO OPERATIVA' ? 'estado-no-oper' : 'estado-oper'}">
                        ${escapeHtml(estado)}
                    </td>
                    <td class="text-center ${nivel === 'Crítico' ? 'nivel-critico' : (nivel === 'Medio' ? 'nivel-medio' : 'nivel-normal')}">
                        ${escapeHtml(nivel)}
                    </td>
                    <td>${escapeHtml(textoMotivo(item) || '')}</td>
                    <td>${escapeHtml(item.novedad || '')}</td>
                </tr>
            `;
        }).join('');

        const html = `
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte Bitácora de Cámaras CCTV</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-size: 11px;
        }

        .reporte {
            width: 100%;
        }

        .encabezado {
            display: grid;
            grid-template-columns: 230px 1fr 210px;
            align-items: center;
            gap: 12px;
            border: 1px solid #111827;
            padding: 8px 10px;
            margin-bottom: 8px;
        }

        .marca {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .marca img {
            width: 58px;
            height: 58px;
            object-fit: contain;
        }

        .marca-texto {
            font-weight: 700;
            font-size: 13px;
            line-height: 1.15;
            text-transform: uppercase;
        }

        .titulo {
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 14px;
            line-height: 1.25;
        }

        .codigo {
            border-left: 1px solid #111827;
            padding-left: 10px;
            font-size: 11px;
            line-height: 1.5;
        }

        .datos {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            border: 1px solid #111827;
            border-bottom: 0;
        }

        .dato {
            border-right: 1px solid #111827;
            padding: 5px 6px;
            min-height: 34px;
        }

        .dato:last-child {
            border-right: 0;
        }

        .dato-label {
            display: block;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .dato-value {
            font-size: 11px;
        }

        .resumen {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 8px 0;
            font-size: 11px;
        }

        .resumen span {
            border: 1px solid #111827;
            padding: 4px 8px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #111827;
            padding: 4px 5px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }

        th {
            background: #d9eaf7;
            text-transform: uppercase;
            font-size: 9px;
            text-align: center;
        }

        td {
            font-size: 9px;
        }

        .text-center {
            text-align: center;
        }

        .estado-oper,
        .nivel-normal {
            font-weight: 700;
            color: #047857;
        }

        .estado-no-oper,
        .nivel-critico {
            font-weight: 700;
            color: #b91c1c;
        }

        .nivel-medio {
            font-weight: 700;
            color: #92400e;
        }

        .col-n {
            width: 28px;
        }

        .col-bit {
            width: 78px;
        }

        .col-fecha {
            width: 65px;
        }

        .col-hora {
            width: 48px;
        }

        .col-cod {
            width: 65px;
        }

        .col-ip {
            width: 75px;
        }

        .col-tipo {
            width: 70px;
        }

        .col-marca {
            width: 115px;
        }

        .col-ubi {
            width: 85px;
        }

        .col-detalle {
            width: 95px;
        }

        .col-estado {
            width: 78px;
        }

        .col-nivel {
            width: 58px;
        }

        .footer {
            margin-top: 18px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            font-size: 11px;
        }

        .firma {
            text-align: center;
            padding-top: 28px;
        }

        .firma-linea {
            border-top: 1px solid #111827;
            padding-top: 4px;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="reporte">

        <div class="encabezado">
            <div class="marca">
                <img src="${logoUrl}" alt="Logo APM">
                <div class="marca-texto">
                    Autoridad<br>
                    Portuaria<br>
                    de Manta
                </div>
            </div>

            <div class="titulo">
                Dirección de Seguridad Integral<br>
                Reporte Diario de Cámaras CCTV
            </div>

            <div class="codigo">
                <strong>CÓDIGO:</strong><br>
                BIT-CCTV-01<br>
                <strong>GENERADO:</strong><br>
                ${escapeHtml(fechaGeneracion)}
            </div>
        </div>

        <div class="datos">
            <div class="dato">
                <span class="dato-label">Fecha</span>
                <span class="dato-value">${escapeHtml(fechaEs(fechaReporte))}</span>
            </div>

            <div class="dato">
                <span class="dato-label">Secuencia</span>
                <span class="dato-value">${escapeHtml(secuencia)}</span>
            </div>

            <div class="dato">
                <span class="dato-label">Turno</span>
                <span class="dato-value">${escapeHtml(turnoReporte)}</span>
            </div>

            <div class="dato">
                <span class="dato-label">Consolista</span>
                <span class="dato-value">${escapeHtml(consolista)}</span>
            </div>

            <div class="dato">
                <span class="dato-label">Total registros</span>
                <span class="dato-value">${total}</span>
            </div>
        </div>

        <div class="resumen">
            <span>OPERATIVA: ${totalOper}</span>
            <span>NO OPERATIVA: ${totalNoOper}</span>
            <span>Normal: ${totalNormal}</span>
            <span>Medio: ${totalMedio}</span>
            <span>Crítico: ${totalCritico}</span>
            <span>Actividades diarias: ${totalActividades}</span>
            <span>Novedades de cámara: ${totalNovedadesCamara}</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-n">N°</th>
                    <th class="col-bit">Cód. bitácora</th>
                    <th>Tipo registro</th>
                    <th class="col-fecha">Fecha</th>
                    <th class="col-hora">Hora</th>
                    <th class="col-cod">Cód. antiguo</th>
                    <th class="col-cod">Código</th>
                    <th class="col-ip">IP / Equipo</th>
                    <th class="col-tipo">Tipo</th>
                    <th class="col-marca">Marca / Modelo</th>
                    <th class="col-ubi">Ubicación</th>
                    <th class="col-detalle">Detalle</th>
                    <th class="col-estado">Estado</th>
                    <th class="col-nivel">Nivel</th>
                    <th>Motivo</th>
                    <th>Actividad / Novedad</th>
                </tr>
            </thead>

            <tbody>
                ${filas}
            </tbody>
        </table>

        <div class="footer">
            <div class="firma">
                <div class="firma-linea">Consolista CCTV</div>
            </div>

            <div class="firma">
                <div class="firma-linea">Supervisor de Seguridad Integral</div>
            </div>
        </div>

    </div>
</body>
</html>
`;

        const printWindow = window.open('', '_blank', 'width=1200,height=800');

        if (!printWindow) {
            toast('El navegador bloqueó la ventana de impresión. Permita ventanas emergentes para este sitio.', 'error');
            return;
        }

        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();

        printWindow.focus();

        setTimeout(function () {
            printWindow.print();
        }, 600);
    }

    function configurarEventos() {
        $('bcTurno').addEventListener('change', aplicarHorasPorTurno);

        if ($('bcTipoRegistro')) {
            $('bcTipoRegistro').addEventListener('change', function () {
                if (esTipoNovedad(this.value)) {
                    if ($('bcNivelAlerta')) {
                        $('bcNivelAlerta').value = '105';
                    }

                    if ($('bcEstadoCamara') && !$('bcEstadoCamara').value) {
                        $('bcEstadoCamara').value = '101';
                    }
                }

                actualizarCamposAlerta();
            });
        }

        if ($('bcNivelAlerta')) {
            $('bcNivelAlerta').addEventListener('change', actualizarCamposAlerta);
        }

        if ($('bcMotivoCamara')) {
            $('bcMotivoCamara').addEventListener('change', aplicarMotivoSeleccionado);
        }

        $('bcBtnGuardar').addEventListener('click', guardarRegistro);
        $('bcBtnCancelar').addEventListener('click', limpiarFormularioDetalle);

        $('bcBtnBuscar').addEventListener('click', listarRegistros);
        $('bcBtnRefrescar').addEventListener('click', listarRegistros);

        const buscarRegistrosAutomatico = debounce(listarRegistros, 450);

        if ($('bcFiltroQ')) {
            $('bcFiltroQ').addEventListener('input', buscarRegistrosAutomatico);

            $('bcFiltroQ').addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    listarRegistros();
                }
            });
        }

        if ($('bcFiltroFecha')) {
            $('bcFiltroFecha').addEventListener('change', listarRegistros);
        }

        if ($('bcFiltroTurno')) {
            $('bcFiltroTurno').addEventListener('change', listarRegistros);
        }

        if ($('bcFiltroTipo')) {
            $('bcFiltroTipo').addEventListener('change', function () {
                state.items = aplicarFiltroTipoRegistros(state.itemsBase.length ? state.itemsBase : state.items);
                renderTabla(state.items);
            });
        }

        $('bcFiltroOrden').addEventListener('change', function () {
            state.orden = this.value === 'DESC' ? 'DESC' : 'ASC';
            listarRegistros();
        });

        $('bcBtnExcel').addEventListener('click', exportarCsv);
        $('bcBtnPdf').addEventListener('click', imprimirReporte);

        inicializarSelectorCamara();
        configurarSugerenciasActividad();

        const btnBuscarCamara = $('bcBtnBuscarCamara');
        const inputBuscarCamara = $('bcBuscarCamara');

        if (btnBuscarCamara && inputBuscarCamara) {
            btnBuscarCamara.addEventListener('click', function () {
                cargarCamaras(inputBuscarCamara.value.trim());
            });

            inputBuscarCamara.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    cargarCamaras(inputBuscarCamara.value.trim());
                }
            });
        }

        $('bcTablaBody').addEventListener('click', function (event) {
            const btnEditar = event.target.closest('.bc-btn-editar');
            const btnEliminar = event.target.closest('.bc-btn-eliminar');

            if (btnEditar) {
                cargarParaEditar(btnEditar.dataset.id);
                return;
            }

            if (btnEliminar) {
                eliminarRegistro(btnEliminar.dataset.id);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        cargarSelectsEstadosDesdeCatalogo();
        configurarEventos();
        actualizarCamposAlerta();
        cargarContexto();
    });
})();