document.addEventListener('DOMContentLoaded', function () {
    const nidentificacionInput = document.getElementById('nidentificacion');
    const nombresInput = document.getElementById('nombres');
    const apellidosInput = document.getElementById('apellidos');
    const cedulaSelector = document.getElementById('cedula_selector');

    function sincronizarCheckGuest(activo) {
        const checkGuest = document.getElementById('visitante_guest');
        if (!checkGuest) return;
        checkGuest.checked = !!activo;
        checkGuest.classList.toggle('apm-guest-checked', !!activo);
    }


    const cedulaError = document.getElementById('cedula_error');
    const formErrorsBox = document.getElementById('formErrorsBox');
    const formErrorsList = document.getElementById('formErrorsList');
    const formIngreso = document.getElementById('formIngreso');

    function cedulaEsValida(valor) {
        var v = String(valor || '').replace(/\D/g, '');
        if (v.length === 10) {
            return typeof ecValidarCedulaEcuador === 'function'
                ? ecValidarCedulaEcuador(v)
                : /^[0-9]{10}$/.test(v);
        }
        if (v.length === 13) {
            return typeof ecValidarRucEcuador === 'function'
                ? ecValidarRucEcuador(v)
                : /^[0-9]{13}$/.test(v);
        }
        return false;
    }

    var formErrorsBoxTimer = null;
    function setAlertas(errors) {
        if (!formErrorsBox || !formErrorsList) return;
        if (formErrorsBoxTimer) {
            clearTimeout(formErrorsBoxTimer);
            formErrorsBoxTimer = null;
        }
        formErrorsList.innerHTML = '';
        if (errors.length === 0) {
            formErrorsBox.style.display = 'none';
            return;
        }
        var totalLength = 0;
        errors.forEach(function (text) {
            var li = document.createElement('li');
            li.textContent = text;
            formErrorsList.appendChild(li);
            totalLength += (text || '').length;
        });
        formErrorsBox.style.display = 'block';
        var duration = totalLength > 50 ? 8500 : 5500;
        formErrorsBoxTimer = setTimeout(function () {
            setAlertas([]);
            formErrorsBoxTimer = null;
        }, duration);
    }

    var btnCloseErrors = document.getElementById('formErrorsBoxClose');
    if (btnCloseErrors && formErrorsBox) {
        btnCloseErrors.addEventListener('click', function () {
            setAlertas([]);
        });
    }

    function addAlerta(texto) {
        if (!formErrorsList) return;
        var actual = [];
        formErrorsList.querySelectorAll('li').forEach(function (li) { actual.push(li.textContent); });
        if (actual.indexOf(texto) === -1) actual.push(texto);
        setAlertas(actual);
    }

    function quitarAlerta(texto) {
        if (!formErrorsList) return;
        var actual = [];
        formErrorsList.querySelectorAll('li').forEach(function (li) {
            if (li.textContent !== texto) actual.push(li.textContent);
        });
        setAlertas(actual);
    }

    function mostrarErrorCedula(mensaje) {
        if (cedulaError) {
            cedulaError.textContent = mensaje;
            cedulaError.style.display = mensaje ? 'block' : 'none';
        }
        var msg = typeof APM_MSG_IDENTIFICACION_INVALIDA !== 'undefined'
            ? APM_MSG_IDENTIFICACION_INVALIDA
            : 'La identificación ingresada no es válida.';
        if (mensaje) {
            addAlerta(msg);
        } else {
            quitarAlerta(msg);
        }
    }

    function buscarPersonaPorCedula(cedulaParam) {
        var nid = (cedulaParam !== undefined && cedulaParam !== '') ? String(cedulaParam).replace(/[^0-9]/g, '') : (nidentificacionInput ? nidentificacionInput.value.trim() : '');
        if (nid.length === 0) {
            mostrarErrorCedula('');
            return;
        }
        if (!cedulaEsValida(nid)) {
            mostrarErrorCedula(
                typeof APM_MSG_IDENTIFICACION_INVALIDA !== 'undefined'
                    ? APM_MSG_IDENTIFICACION_INVALIDA
                    : 'La identificación ingresada no es válida.'
            );
            return;
        }
        mostrarErrorCedula('');

        fetch('apis/bit_personas_api.php?cedula=' + encodeURIComponent(nid))
            .then(r => r.json())
            .then(data => {
                if (!data.ok) {
                    if (window.showToast) window.showToast(data.message || 'Error al buscar.', 'error', { key: 'cedula' });
                    return;
                }
                if (data.found && data.data) {
                    var d = data.data;
                    nombresInput.value = d.nombres || '';
                    apellidosInput.value = d.apellidos || '';
                    nidentificacionInput.value = nid;
                    if (cedulaSelector && typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                        var $sel = jQuery(cedulaSelector);
                        var opt = $sel.find('option[value="' + nid + '"]');
                        if (opt.length === 0) {
                            opt = new Option((d.nombres || '') + ' ' + (d.apellidos || '') + ' (' + nid + ')', nid, true, true);
                            jQuery(opt).data('nombre', d.nombres).data('apellido', d.apellidos);
                            $sel.append(opt);
                        }
                        $sel.val(nid).trigger('change');
                    }
                } else {
                    if (window.showToast) window.showToast(data.message || 'No se encontraron registros.', 'info', { key: 'cedula' });
                }
            })
            .catch(function () {
                if (window.showToast) window.showToast('Error de conexión al buscar.', 'error', { key: 'cedula' });
            });
    }

    if (cedulaSelector && typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery(cedulaSelector).select2({
            theme: 'bootstrap-5',
            placeholder: 'Buscar por cédula o nombre...',
            allowClear: true,
            width: '100%',
            minimumInputLength: 2,
            language: {
                noResults: function () { return 'No se encontraron resultados'; },
                inputTooShort: function () { return 'Escriba al menos 2 caracteres (identificación o nombre)'; }
            },
            ajax: {
                url: 'apis/bit_personas_api.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { cedula: (params.term || '').trim() };
                },
                processResults: function (data) {
                    if (!data.ok) return { results: [] };
                    function mapPersona(d) {
                        var nom = d.nombres || '';
                        var ape = d.apellidos || '';
                        var id = d.nidentificacion;
                        return {
                            id: id,
                            text: nom + ' ' + ape + ' (' + id + ')',
                            nombre: nom,
                            apellido: ape
                        };
                    }
                    if (data.found && data.data) {
                        return { results: [mapPersona(data.data)] };
                    }
                    if (data.found && data.results && data.results.length) {
                        return {
                            results: data.results.map(mapPersona)
                        };
                    }
                    if (!data.found && data.nidentificacion) {
                        return {
                            results: [{
                                id: data.nidentificacion,
                                text: 'Identificación ' + data.nidentificacion + ' (no encontrada)',
                                nombre: '',
                                apellido: ''
                            }]
                        };
                    }
                    return { results: [] };
                },
                cache: true
            },
            templateSelection: function (item) {
                return item.id || item.text;
            },
            createTag: function () { return null; },
            tags: true
        });
        jQuery(cedulaSelector).on('select2:select', function (e) {
            var data = e.params.data;
            var valorSeleccionado = String(data.id || '');
            var esGuest = valorSeleccionado === '9999999999';

            nidentificacionInput.value = valorSeleccionado;
            sincronizarCheckGuest(esGuest);

            if (esGuest) {
                nombresInput.value = '';
                apellidosInput.value = '';
                nidentificacionInput.removeAttribute('required');
            } else {
                nidentificacionInput.setAttribute('required', 'required');

                if (data.nombre !== undefined && data.nombre !== '') {
                    nombresInput.value = data.nombre;
                    apellidosInput.value = data.apellido || '';
                }

                if (!data.nombre && data.id) {
                    if (window.showToast) window.showToast('Identificación no encontrada. Complete nombres y apellidos o use "Registrar nueva persona".', 'info', { key: 'cedula' });
                }
            }

            if (window.focusSiguienteCampo) window.focusSiguienteCampo('cedula_selector');
            else if (nombresInput) nombresInput.focus();
        });
        jQuery(cedulaSelector).on('select2:clear', function () {
            nidentificacionInput.value = '';
            sincronizarCheckGuest(false);
            nidentificacionInput.setAttribute('required', 'required');
        });
    }

    if (formIngreso) {
        formIngreso.addEventListener('submit', function (e) {
            var errores = [];
            var esGuest = document.getElementById('visitante_guest') && document.getElementById('visitante_guest').checked;
            var c = nidentificacionInput.value.trim();
            if (!esGuest) {
                if (!c) {
                    errores.push('Ingrese el número de identificación.');
                } else if (!cedulaEsValida(c) && c !== '9999999999') {
                    errores.push(
                        typeof APM_MSG_IDENTIFICACION_INVALIDA !== 'undefined'
                            ? APM_MSG_IDENTIFICACION_INVALIDA
                            : 'La identificación ingresada no es válida.'
                    );
                }
            }
            if (!nombresInput.value.trim()) errores.push('Ingrese los nombres.');
            if (!apellidosInput.value.trim()) errores.push('Ingrese los apellidos.');
            // Funcionario opcional: se permite guardar el ingreso sin asignarlo.
            var idDestino = document.getElementById('id_destino');
            if (idDestino && !idDestino.value) errores.push('Seleccione el destino.');
            var idMotivo = document.querySelector('select[name="id_motivo"]');
            if (idMotivo && !idMotivo.value) errores.push('Seleccione el motivo.');
            if (errores.length > 0) {
                e.preventDefault();
                setAlertas(errores);
                if (window.showToast) window.showToast(errores[0] || 'Corrija los errores del formulario.', 'error');
                if (formErrorsBox) formErrorsBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                return;
            }
            if (esGuest) {
                nidentificacionInput.value = '9999999999';
                if (cedulaSelector && typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                    jQuery(cedulaSelector).val('9999999999').trigger('change');
                }
            }
            setAlertas([]);
        });
    }

    var guestCheck = document.getElementById('visitante_guest');
    if (guestCheck && nidentificacionInput) {
        const valorActualGuest = String(nidentificacionInput.value || cedulaSelector?.value || '') === '9999999999';
        sincronizarCheckGuest(valorActualGuest);

        guestCheck.addEventListener('change', function () {
            sincronizarCheckGuest(this.checked);

            if (this.checked) {
                nidentificacionInput.value = '9999999999';
                if (cedulaSelector && typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                    jQuery(cedulaSelector).val('9999999999').trigger('change');
                }
                nombresInput.value = '';
                apellidosInput.value = '';
                nidentificacionInput.removeAttribute('required');
            } else {
                nidentificacionInput.value = '';
                if (cedulaSelector && typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                    jQuery(cedulaSelector).val(null).trigger('change');
                }
                nidentificacionInput.setAttribute('required', 'required');
            }
        });
    }

    const personaNidentificacion = document.getElementById('persona_nidentificacion');
    const personaTidentif = document.getElementById('persona_tidentif');
    const personaNombres = document.getElementById('persona_nombres');
    const personaApellidos = document.getElementById('persona_apellidos');
    const personaError = document.getElementById('persona_error');
    const btnGuardarPersona = document.getElementById('btnGuardarPersona');

    function ajustarPersonaIdentificacionInput() {
        if (!personaNidentificacion || !personaTidentif) return;
        var t = (personaTidentif.value || '').toUpperCase();
        var v = personaNidentificacion.value;
        if (t === 'CEDULA') {
            personaNidentificacion.value = v.replace(/\D/g, '').slice(0, 10);
            personaNidentificacion.setAttribute('maxlength', '10');
            personaNidentificacion.setAttribute('inputmode', 'numeric');
        } else if (t === 'RUC') {
            personaNidentificacion.value = v.replace(/\D/g, '').slice(0, 13);
            personaNidentificacion.setAttribute('maxlength', '13');
            personaNidentificacion.setAttribute('inputmode', 'numeric');
        } else {
            personaNidentificacion.setAttribute('maxlength', '20');
            personaNidentificacion.setAttribute('inputmode', 'text');
        }
    }

    const formModalPersona = document.getElementById('formModalPersona');
    const modalPersonaEl = document.getElementById('modalPersona');
    if (modalPersonaEl) {
        modalPersonaEl.addEventListener('show.bs.modal', function () {
            if (formModalPersona) formModalPersona.classList.remove('was-validated');
            personaError.style.display = 'none';
            personaError.textContent = '';
            if (personaTidentif) personaTidentif.value = 'CEDULA';
            if (personaNidentificacion) personaNidentificacion.value = nidentificacionInput.value.trim();
            if (personaNombres) personaNombres.value = nombresInput.value.trim();
            if (personaApellidos) personaApellidos.value = apellidosInput.value.trim();
            ajustarPersonaIdentificacionInput();
        });
    }

    if (personaTidentif) personaTidentif.addEventListener('change', ajustarPersonaIdentificacionInput);
    if (personaNidentificacion) personaNidentificacion.addEventListener('input', ajustarPersonaIdentificacionInput);

    if (formModalPersona && btnGuardarPersona && personaNidentificacion && personaNombres && personaApellidos) {
        formModalPersona.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!formModalPersona.checkValidity()) {
                formModalPersona.classList.add('was-validated');
                return;
            }
            const nid = personaNidentificacion.value.trim();
            const tid = personaTidentif ? personaTidentif.value.trim() : 'CEDULA';
            const nom = personaNombres.value.trim();
            const ape = personaApellidos.value.trim();

            var nidNum = nid.replace(/\D/g, '');
            if (tid === 'CEDULA' && (!/^\d{10}$/.test(nidNum) || (typeof ecValidarCedulaEcuador === 'function' && !ecValidarCedulaEcuador(nidNum)))) {
                personaError.textContent =
                    typeof APM_MSG_IDENTIFICACION_INVALIDA !== 'undefined'
                        ? APM_MSG_IDENTIFICACION_INVALIDA
                        : 'La identificación ingresada no es válida.';
                personaError.style.display = 'block';
                return;
            }
            if (tid === 'RUC' && (!/^\d{13}$/.test(nidNum) || (typeof ecValidarRucEcuador === 'function' && !ecValidarRucEcuador(nidNum)))) {
                personaError.textContent =
                    typeof APM_MSG_IDENTIFICACION_INVALIDA !== 'undefined'
                        ? APM_MSG_IDENTIFICACION_INVALIDA
                        : 'La identificación ingresada no es válida.';
                personaError.style.display = 'block';
                return;
            }
            if (tid === 'PASAPORTE' && nid.length < 3) {
                personaError.textContent = 'Ingrese un número de pasaporte válido.';
                personaError.style.display = 'block';
                return;
            }

            personaError.style.display = 'none';
            personaError.textContent = '';

            const formData = new FormData();
            formData.append('nidentificacion', tid === 'PASAPORTE' ? nid : nidNum);
            formData.append('tidentif', tid);
            formData.append('nombres', nom);
            formData.append('apellidos', ape);

            fetch('apis/bit_personas_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) {
                    personaError.textContent =
                        data.message != null && String(data.message).trim() !== ''
                            ? String(data.message)
                            : 'Error al guardar la persona.';
                    personaError.style.display = 'block';
                    return;
                }

                var d = data.data || {};
                var nidOut =
                    d.nidentificacion != null && String(d.nidentificacion).trim() !== ''
                        ? String(d.nidentificacion).trim()
                        : nid;
                var nomOut = d.nombres != null ? String(d.nombres) : nom;
                var apeOut = d.apellidos != null ? String(d.apellidos) : ape;

                nidentificacionInput.value = nidOut;
                nombresInput.value = nomOut;
                apellidosInput.value = apeOut;
                if (cedulaSelector && typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                    var $sel = jQuery(cedulaSelector);
                    var opt = $sel.find('option').filter(function () {
                        return this.value === nidOut;
                    });
                    if (opt.length === 0) {
                        var nuevaOpt = new Option(nomOut + ' ' + apeOut + ' (' + nidOut + ')', nidOut, true, true);
                        jQuery(nuevaOpt).data('nombre', nomOut).data('apellido', apeOut);
                        $sel.append(nuevaOpt);
                    }
                    $sel.val(nidOut).trigger('change');
                }
                var toastMsg =
                    data.message != null && String(data.message).trim() !== ''
                        ? String(data.message)
                        : 'Persona registrada correctamente.';
                if (window.showToast) window.showToast(toastMsg, 'success');
                formModalPersona.classList.remove('was-validated');
                const modal = bootstrap.Modal.getInstance(modalPersonaEl);
                if (modal) modal.hide();
            })
            .catch(() => {
                personaError.textContent = 'Error de comunicación con el servidor.';
                personaError.style.display = 'block';
            });
        });
    }

    const empresaEmpresa = document.getElementById('empresa_empresa');
    const empresaRuc = document.getElementById('empresa_ruc');
    const empresaRazonSocial = document.getElementById('empresa_razonsocial');
    const empresaError = document.getElementById('empresa_error');
    const btnGuardarEmpresa = document.getElementById('btnGuardarEmpresa');
    const selectEmpresa = document.getElementById('id_empresa');
    const formModalEmpresa = document.getElementById('formModalEmpresa');
    const modalEmpresaEl = document.getElementById('modalEmpresa');

    if (modalEmpresaEl && empresaEmpresa) {
        modalEmpresaEl.addEventListener('show.bs.modal', function () {
            if (formModalEmpresa) formModalEmpresa.classList.remove('was-validated');
            empresaError.style.display = 'none';
            empresaError.textContent = '';
            empresaEmpresa.value = '';
            if (empresaRuc) empresaRuc.value = '';
            if (empresaRazonSocial) empresaRazonSocial.value = '';
        });
    }

    if (formModalEmpresa && btnGuardarEmpresa && empresaEmpresa && empresaRuc && empresaRazonSocial) {
        formModalEmpresa.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!formModalEmpresa.checkValidity()) {
                formModalEmpresa.classList.add('was-validated');
                return;
            }
            const emp = empresaEmpresa.value.trim();
            const rucLimp = empresaRuc.value.replace(/\D/g, '').slice(0, 13);
            const razon = empresaRazonSocial.value.trim();

            empresaError.style.display = 'none';
            empresaError.textContent = '';

            if (
                rucLimp.length !== 13 ||
                (typeof ecValidarRucEcuador === 'function' && !ecValidarRucEcuador(rucLimp))
            ) {
                empresaError.textContent =
                    typeof APM_MSG_IDENTIFICACION_INVALIDA !== 'undefined'
                        ? APM_MSG_IDENTIFICACION_INVALIDA
                        : 'La identificación ingresada no es válida.';
                empresaError.style.display = 'block';
                return;
            }

            const formData = new FormData();
            formData.append('empresa', emp);
            formData.append('ruc', rucLimp);
            formData.append('razonsocial', razon);

            fetch('apis/bit_empresas_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok || !data.data) {
                    empresaError.textContent = data.message || 'Error al guardar la empresa.';
                    empresaError.style.display = 'block';
                    return;
                }

                const info = data.data;
                const option = document.createElement('option');
                option.value = info.id_empresa;
                option.textContent = info.label;
                option.selected = true;
                selectEmpresa.appendChild(option);
                if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                    jQuery(selectEmpresa).val(info.id_empresa).trigger('change');
                }

                formModalEmpresa.classList.remove('was-validated');
                const modal = bootstrap.Modal.getInstance(modalEmpresaEl);
                if (modal) {
                    modal.hide();
                }
                if (window.showToast) window.showToast('Empresa registrada correctamente.', 'success');
            })
            .catch(() => {
                empresaError.textContent = 'Error de comunicación con el servidor.';
                empresaError.style.display = 'block';
            });
        });
    }

    const destinoNombre = document.getElementById('destino_nombre');
    const destinoError = document.getElementById('destino_error');
    const btnGuardarDestino = document.getElementById('btnGuardarDestino');
    const selectDestino = document.getElementById('id_destino');
    const modalDestinoEl = document.getElementById('modalDestino');

    if (modalDestinoEl) {
        modalDestinoEl.addEventListener('show.bs.modal', function () {
            destinoError.style.display = 'none';
            destinoError.textContent = '';
            destinoNombre.value = '';
        });
    }

    if (btnGuardarDestino) {
        btnGuardarDestino.addEventListener('click', function () {
            const nombre = destinoNombre.value.trim();

            if (!nombre) {
                destinoError.textContent = 'El nombre del destino es obligatorio.';
                destinoError.style.display = 'block';
                return;
            }

            const formData = new FormData();
            formData.append('nombre', nombre);

            fetch('apis/bit_destinos_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok || !data.data) {
                    destinoError.textContent = data.message || 'Error al guardar el destino.';
                    destinoError.style.display = 'block';
                    return;
                }

                const info = data.data;
                const option = document.createElement('option');
                option.value = info.id_destino;
                option.textContent = info.nombre;
                option.selected = true;
                selectDestino.appendChild(option);

                const modal = bootstrap.Modal.getInstance(modalDestinoEl);
                if (modal) {
                    modal.hide();
                }
                if (window.showToast) window.showToast('Destino registrado correctamente.', 'success');
            })
            .catch(() => {
                destinoError.textContent = 'Error de comunicación con el servidor.';
                destinoError.style.display = 'block';
            });
        });
    }

    const motivoDescripcion = document.getElementById('motivo_descripcion');
    const motivoError = document.getElementById('motivo_error');
    const btnGuardarMotivo = document.getElementById('btnGuardarMotivo');
    const selectMotivo = document.getElementById('id_motivo');
    const modalMotivoEl = document.getElementById('modalMotivo');

    if (modalMotivoEl) {
        modalMotivoEl.addEventListener('show.bs.modal', function () {
            motivoError.style.display = 'none';
            motivoError.textContent = '';
            motivoDescripcion.value = '';
        });
    }

    if (btnGuardarMotivo && selectMotivo) {
        btnGuardarMotivo.addEventListener('click', function () {
            const descripcion = motivoDescripcion.value.trim();

            if (!descripcion) {
                motivoError.textContent = 'La descripción del motivo es obligatoria.';
                motivoError.style.display = 'block';
                return;
            }

            const formData = new FormData();
            formData.append('descripcion', descripcion);

            fetch('apis/bit_motivos_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok || !data.data) {
                    motivoError.textContent = data.message || 'Error al guardar el motivo.';
                    motivoError.style.display = 'block';
                    return;
                }

                const info = data.data;
                if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && jQuery(selectMotivo).data('select2')) {
                    var opt = new Option(info.descripcion, String(info.id_motivo), true, true);
                    jQuery(selectMotivo).append(opt).val(String(info.id_motivo)).trigger('change');
                } else {
                    const option = document.createElement('option');
                    option.value = info.id_motivo;
                    option.textContent = info.descripcion;
                    option.selected = true;
                    selectMotivo.appendChild(option);
                }

                const modal = bootstrap.Modal.getInstance(modalMotivoEl);
                if (modal) modal.hide();
                if (window.showToast) window.showToast('Motivo registrado correctamente.', 'success');
            })
            .catch(() => {
                motivoError.textContent = 'Error de comunicación con el servidor.';
                motivoError.style.display = 'block';
            });
        });
    }

    if (selectEmpresa && typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery(selectEmpresa).select2({
            theme: 'bootstrap-5',
            placeholder: 'Buscar empresa por nombre o RUC',
            allowClear: true,
            width: '100%',
            minimumInputLength: 2,
            language: {
                noResults: function () { return 'No se encontraron resultados'; },
                inputTooShort: function () { return 'Escriba al menos 2 caracteres'; }
            },
            ajax: {
                url: 'apis/bit_empresas_api.php',
                dataType: 'json',
                delay: 400,
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    if (!data.ok) return { results: [] };
                    var list = (data.results && data.results.length) ? data.results : [];
                    return {
                        results: list.map(function (d) {
                            return { id: d.id_empresa, text: d.label || ((d.empresa || '') + (d.ruc ? ' (' + d.ruc + ')' : '')) };
                        })
                    };
                },
                cache: true
            }
        });
    }

    (function () {
        var MAX_CEDULA = 10;
        var MAX_RUC = 13;
        var MAX_DOC = 20;
        function onlyDigitsMax(el, max) {
            var v = (el.value || '').replace(/\D/g, '');
            if (v.length > max) v = v.slice(0, max);
            if (el.value !== v) {
                el.value = v;
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
        function isControlKey(e) {
            return e.ctrlKey || e.metaKey || e.altKey ||
                ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Enter'].indexOf(e.key) !== -1;
        }
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('input', '.select2-search__field', function () {
                var $select = jQuery(this).closest('.select2-container').prev('select');
                var id = $select.attr('id');
                if (id === 'cedula_selector') return;
                if (id === 'id_empresa') {
                    var val = this.value || '';
                    if (/^\d*$/.test(val)) onlyDigitsMax(this, MAX_RUC);
                }
            });
            jQuery(document).on('keydown', '.select2-search__field', function (e) {
                var $select = jQuery(this).closest('.select2-container').prev('select');
                if ($select.attr('id') === 'cedula_selector') return;
                if ($select.attr('id') !== 'id_empresa') return;
                if (isControlKey(e)) return;

                // En Empresa / Personal se debe permitir buscar por nombre o por RUC.
                // Si el usuario escribe solo números, se limita a 13 dígitos; si escribe letras,
                // se deja pasar para búsquedas como "paci" → "Pacífico".
                var actual = this.value || '';
                if (/^\d*$/.test(actual) && /^\d$/.test(e.key)) {
                    var digits = actual.replace(/\D/g, '');
                    if (digits.length >= MAX_RUC) e.preventDefault();
                }
            });
            jQuery(document).on('paste', '.select2-search__field', function (e) {
                var $select = jQuery(this).closest('.select2-container').prev('select');
                if ($select.attr('id') === 'cedula_selector') return;
                if ($select.attr('id') !== 'id_empresa') return;

                var el = this;
                var actual = el.value || '';
                var pasted = (e.originalEvent.clipboardData || window.clipboardData).getData('text') || '';
                // Solo intervenimos cuando el contenido pegado es numérico; texto normal se deja pasar.
                if (/^\d*$/.test(actual) && /^\d+$/.test(pasted)) {
                    e.preventDefault();
                    var cur = actual.replace(/\D/g, '');
                    var add = pasted.replace(/\D/g, '');
                    el.value = (cur + add).slice(0, MAX_RUC);
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        }
        var personaNidEl = document.getElementById('persona_nidentificacion');
        var personaTidEl = document.getElementById('persona_tidentif');
        if (personaNidEl) {
            personaNidEl.addEventListener('input', function () {
                var tipo = personaTidEl ? personaTidEl.value : 'CEDULA';
                if (tipo === 'CEDULA') {
                    onlyDigitsMax(this, MAX_CEDULA);
                    return;
                }
                if (tipo === 'RUC') {
                    onlyDigitsMax(this, MAX_RUC);
                    return;
                }
                var v = (this.value || '').replace(/[^a-zA-Z0-9]/g, '');
                if (v.length > MAX_DOC) v = v.slice(0, MAX_DOC);
                if (this.value !== v) this.value = v;
            });
        }
        if (personaTidEl && personaNidEl) {
            personaTidEl.addEventListener('change', function () {
                personaNidEl.dispatchEvent(new Event('input', { bubbles: true }));
            });
        }
        var empresaRucEl = document.getElementById('empresa_ruc');
        if (empresaRucEl) {
            empresaRucEl.addEventListener('input', function () { onlyDigitsMax(this, MAX_RUC); });
        }
        var funcionarioCedulaEl = document.getElementById('funcionario_cedula');
        if (funcionarioCedulaEl) {
            funcionarioCedulaEl.addEventListener('input', function () { onlyDigitsMax(this, MAX_DOC); });
        }
    })();

    (function () {
        var ORDER = ['cedula_selector', 'nombres', 'apellidos', 'id_empresa', 'fecha_hora_visita', 'id_funcionario', 'id_destino', 'id_motivo', 'id_nivel_incidente', 'btnGuardarIngreso'];
        var SELECT2_IDS = ['cedula_selector', 'id_empresa', 'id_funcionario', 'id_motivo'];

        function focusSiguienteCampo(currentId) {
            var idx = ORDER.indexOf(currentId);
            if (idx < 0 || idx >= ORDER.length - 1) return;
            var nextId = ORDER[idx + 1];
            var el = document.getElementById(nextId);
            if (!el) return;
            if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && SELECT2_IDS.indexOf(nextId) !== -1 && jQuery(el).data('select2')) {
                jQuery(el).select2('open');
            } else {
                el.focus();
            }
        }
        window.focusSiguienteCampo = focusSiguienteCampo;

        jQuery(document).on('keydown', '.select2-container', function (e) {
            if (e.key !== 'Enter') return;
            var $sel = jQuery(this).prev('select');
            var id = $sel.attr('id');
            if (SELECT2_IDS.indexOf(id) === -1) return;
            if (jQuery($sel).data('select2') && !jQuery($sel).data('select2').isOpen()) {
                e.preventDefault();
                focusSiguienteCampo(id);
            }
        });

        var nomEl = document.getElementById('nombres');
        var apeEl = document.getElementById('apellidos');
        if (nomEl) nomEl.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); focusSiguienteCampo('nombres'); } });
        if (apeEl) apeEl.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); focusSiguienteCampo('apellidos'); } });

        var fechaHoraVisita = document.getElementById('fecha_hora_visita');
        if (fechaHoraVisita) fechaHoraVisita.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); focusSiguienteCampo('fecha_hora_visita'); } });

        var selectDestino = document.getElementById('id_destino');
        var selectMotivo = document.getElementById('id_motivo');
        var selectNivelIncidente = document.getElementById('id_nivel_incidente');
        if (selectDestino) selectDestino.addEventListener('keydown', function (e) { if (e.key === 'Enter' && this.value) { e.preventDefault(); focusSiguienteCampo('id_destino'); } });
        if (selectMotivo) selectMotivo.addEventListener('keydown', function (e) { if (e.key === 'Enter' && this.value) { e.preventDefault(); focusSiguienteCampo('id_motivo'); } });
        if (selectNivelIncidente) selectNivelIncidente.addEventListener('keydown', function (e) { if (e.key === 'Enter' && this.value) { e.preventDefault(); focusSiguienteCampo('id_nivel_incidente'); } });

        var empSel = document.getElementById('id_empresa');
        if (empSel && typeof jQuery !== 'undefined' && jQuery.fn.select2) jQuery(empSel).on('select2:select', function () { focusSiguienteCampo('id_empresa'); });
    })();

    const funcionarioNombre = document.getElementById('funcionario_nombre');
    const funcionarioCargo = document.getElementById('funcionario_cargo');
    const funcionarioCedula = document.getElementById('funcionario_cedula');
    const funcionarioError = document.getElementById('funcionario_error');
    const selectFuncionario = document.getElementById('id_funcionario');
    const modalFuncionarioEl = document.getElementById('modalFuncionario');

    if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && selectFuncionario) {
        jQuery(selectFuncionario).select2({
            theme: 'bootstrap-5',
            placeholder: 'Buscar funcionario opcional...',
            allowClear: true,
            width: '100%',
            language: { noResults: function () { return 'No se encontraron resultados'; } }
        });
        jQuery(selectFuncionario).on('select2:select', function () {
            if (window.focusSiguienteCampo) window.focusSiguienteCampo('id_funcionario');
        });
    }

    if (typeof jQuery !== 'undefined' && jQuery.fn.select2 && selectMotivo) {
        jQuery(selectMotivo).select2({
            theme: 'bootstrap-5',
            placeholder: 'Buscar motivo...',
            allowClear: true,
            width: '100%',
            minimumResultsForSearch: 0,
            language: { noResults: function () { return 'No se encontraron resultados'; } }
        });
        jQuery(selectMotivo).on('select2:select', function () {
            if (window.focusSiguienteCampo) window.focusSiguienteCampo('id_motivo');
        });
    }

    const formModalFuncionario = document.getElementById('formModalFuncionario');
    if (modalFuncionarioEl) {
        modalFuncionarioEl.addEventListener('show.bs.modal', function () {
            if (formModalFuncionario) formModalFuncionario.classList.remove('was-validated');
            funcionarioError.style.display = 'none';
            funcionarioError.textContent = '';
            funcionarioNombre.value = '';
            funcionarioCargo.value = '';
            if (funcionarioCedula) funcionarioCedula.value = '';
        });
    }

    const btnGuardarFuncionario = document.getElementById('btnGuardarFuncionario');

    if (formModalFuncionario && btnGuardarFuncionario) {
        formModalFuncionario.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!formModalFuncionario.checkValidity()) {
                formModalFuncionario.classList.add('was-validated');
                return;
            }
            const nombre = funcionarioNombre.value.trim();
            const cargo = funcionarioCargo.value.trim();
            const cedulaLimp = funcionarioCedula ? funcionarioCedula.value.replace(/\D/g, '').slice(0, 10) : '';

            if (!nombre || !cargo || !cedulaLimp) {
                funcionarioError.textContent = 'Cédula, nombre y cargo son obligatorios.';
                funcionarioError.style.display = 'block';
                return;
            }

            if (
                cedulaLimp.length !== 10 ||
                (typeof ecValidarCedulaEcuador === 'function' && !ecValidarCedulaEcuador(cedulaLimp))
            ) {
                funcionarioError.textContent =
                    typeof APM_MSG_IDENTIFICACION_INVALIDA !== 'undefined'
                        ? APM_MSG_IDENTIFICACION_INVALIDA
                        : 'La identificación ingresada no es válida.';
                funcionarioError.style.display = 'block';
                return;
            }

            funcionarioError.style.display = 'none';
            funcionarioError.textContent = '';

            const formData = new FormData();
            formData.append('nombre', nombre);
            formData.append('cargo', cargo);
            formData.append('cedula', cedulaLimp);

            fetch('apis/bit_funcionarios_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok || !data.data) {
                    funcionarioError.textContent = data.message || 'Error al guardar el funcionario.';
                    funcionarioError.style.display = 'block';
                    return;
                }

                const info = data.data;
                const option = document.createElement('option');
                option.value = info.id_funcionario;
                option.textContent = info.nombre + ' - ' + info.cargo + (info.cedula ? ' (' + info.cedula + ')' : '');
                option.selected = true;
                selectFuncionario.appendChild(option);
                if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
                    jQuery(selectFuncionario).val(String(info.id_funcionario)).trigger('change');
                }

                formModalFuncionario.classList.remove('was-validated');
                const modal = bootstrap.Modal.getInstance(modalFuncionarioEl);
                if (modal) {
                    modal.hide();
                }
                if (window.showToast) window.showToast('Funcionario registrado correctamente.', 'success');
            })
            .catch(() => {
                funcionarioError.textContent = 'Error de comunicación con el servidor.';
                funcionarioError.style.display = 'block';
            });
        });
    }

    if (typeof apmRestringirInputNumerico === 'function') {
        var er = document.getElementById('empresa_ruc');
        var fc = document.getElementById('funcionario_cedula');
        if (er) apmRestringirInputNumerico(er, 13);
        if (fc) apmRestringirInputNumerico(fc, 10);
    }

});
