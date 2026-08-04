document.addEventListener('DOMContentLoaded', function () {
    var API_URL = 'bitacoras/catalogo/api';
    var tablas = {};
    var cargadas = {};

    var defs = {
        personas: {
            title: 'Personas',
            columns: [
                { key: 'nidentificacion', label: 'Identificación' },
                { key: 'tidentif', label: 'Tipo' },
                { key: 'nombres', label: 'Nombres' },
                { key: 'apellidos', label: 'Apellidos' }
            ],
            fields: [
                { name: 'nidentificacion', label: 'N° identificación', required: true, max: 20 },
                { name: 'tidentif', label: 'Tipo identificación', required: true, type: 'select', options: [{ v: 'Cédula', t: 'Cédula' }, { v: 'Pasaporte', t: 'Pasaporte' }, { v: 'RUC', t: 'RUC' }] },
                { name: 'nombres', label: 'Nombres', required: true, max: 100 },
                { name: 'apellidos', label: 'Apellidos', required: true, max: 100 }
            ]
        },
        empresas: {
            title: 'Empresas',
            columns: [
                { key: 'ruc', label: 'RUC' },
                { key: 'empresa', label: 'Empresa' },
                { key: 'razonsocial', label: 'Razón social' }
            ],
            fields: [
                { name: 'ruc', label: 'RUC', required: true, max: 20 },
                { name: 'empresa', label: 'Empresa', required: true, max: 150 },
                { name: 'razonsocial', label: 'Razón social', required: true, max: 150 }
            ]
        },
        funcionarios: {
            title: 'Talento Humano',
            modalLabelSingular: 'Registro de Talento Humano',
            columns: [
                { key: 'cedula', label: 'Cédula' },
                { key: 'nombre', label: 'Nombre' },
                { key: 'cargo', label: 'Cargo' }
            ],
            fields: [
                { name: 'cedula', label: 'Cédula', required: true, max: 20 },
                { name: 'nombre', label: 'Nombre', required: true, max: 150 },
                { name: 'cargo', label: 'Cargo', required: true, max: 100 }
            ]
        },
        destinos: {
            title: 'Destinos',
            columns: [{ key: 'nombre', label: 'Nombre' }],
            fields: [{ name: 'nombre', label: 'Nombre', required: true, max: 150 }]
        },
        motivos: {
            title: 'Motivos',
            columns: [{ key: 'descripcion', label: 'Descripción' }],
            fields: [{ name: 'descripcion', label: 'Descripción', required: true, max: 200 }]
        },
        niveles_incidente: {
            title: 'Niveles de importancia',
            modalLabelSingular: 'Nivel de importancia',
            columns: [
                { key: 'nivel', label: 'Nivel (1–3)' },
                { key: 'descripcion', label: 'Descripción' }
            ],
            fields: [
                {
                    name: 'nivel',
                    label: 'Gravedad (1 Normal · 2 Medio · 3 Crítico)',
                    required: true,
                    type: 'select',
                    options: [
                        { v: '1', t: '1 — Normal' },
                        { v: '2', t: '2 — Medio' },
                        { v: '3', t: '3 — Crítico' }
                    ]
                },
                { name: 'descripcion', label: 'Descripción', required: true, max: 50 }
            ]
        }
    };

    function esc(v) {
        if (v === null || v === undefined) return '';
        return String(v)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function normalizarTipoIdentificacion(valor) {
        var v = String(valor || '').trim();
        if (!v) return '';
        var vn = v
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toUpperCase();
        if (vn === 'C' || vn === 'CEDULA') return 'Cédula';
        if (vn === 'P' || vn === 'PASAPORTE') return 'Pasaporte';
        if (vn === 'R' || vn === 'RUC') return 'RUC';
        return valor || '';
    }

    function pkFor(catalogo) {
        if (catalogo === 'personas') return 'id_persona';
        if (catalogo === 'empresas') return 'id_empresa';
        if (catalogo === 'funcionarios') return 'id_funcionario';
        if (catalogo === 'destinos') return 'id_destino';
        if (catalogo === 'niveles_incidente') return 'id_incidentes';
        return 'id_motivo';
    }

    function getJson(url) {
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { return r.json(); });
    }

    function postForm(fd) {
        return fetch(API_URL, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { return r.json(); });
    }

    /** Alerta de error al desactivar (p. ej. integridad referencial con visitas). */
    function alertarErrorDesactivarCatalogo(mensaje) {
        var msg = mensaje || 'No se pudo desactivar el registro.';
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'No se puede desactivar',
                text: msg,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#dc3545'
            });
        } else if (window.showToast) {
            window.showToast(msg, 'error');
        } else {
            window.alert(msg);
        }
    }

    function buildThead(catalogo) {
        var cfg = defs[catalogo];
        var th = cfg.columns.map(function (c) { 
            return '<th><span class="sort-target">' + esc(c.label) + '</span></th>'; 
        }).join('');
        th += '<th class="text-center" style="width: 130px; min-width: 130px;">Acciones</th>';
        return '<tr>' + th + '</tr>';
    }

    function buildRows(catalogo, items) {
        var cfg = defs[catalogo];
        var pk = pkFor(catalogo);
        return items.map(function (item) {
            var cells = cfg.columns.map(function (c) {
                var valor = item[c.key] || '';
                if (catalogo === 'personas' && c.key === 'tidentif') {
                    valor = normalizarTipoIdentificacion(valor);
                }
                return '<td>' + esc(valor) + '</td>';
            }).join('');
            var id = item[pk];
            var acciones = renderAcciones(catalogo, id);
            return '<tr>' + cells + '<td class="text-center">' + acciones + '</td></tr>';
        }).join('');
    }

    function renderAcciones(catalogo, id) {
        return '' +
            '<button class="btn btn-sm btn-outline-primary me-1 btn-editar" data-catalogo="' + esc(catalogo) + '" data-id="' + esc(id) + '" title="Editar">' +
            '<i class="bi bi-pencil"></i></button>' +
            '<button class="btn btn-sm btn-outline-danger btn-desactivar" data-catalogo="' + esc(catalogo) + '" data-id="' + esc(id) + '" title="Desactivar">' +
            '<i class="bi bi-trash"></i></button>';
    }

    function rowsForDataTable(catalogo, items) {
        var cfg = defs[catalogo];
        var pk = pkFor(catalogo);
        return (items || []).map(function (item) {
            var row = cfg.columns.map(function (c) { return esc(item[c.key] || ''); });
            row.push(renderAcciones(catalogo, item[pk]));
            return row;
        });
    }

    function initOrRefreshDataTable(catalogo) {
        var id = '#tabla-' + catalogo;
        if (tablas[catalogo]) {
            tablas[catalogo].destroy();
            tablas[catalogo] = null;
        }
        tablas[catalogo] = window.jQuery(id).DataTable({
            order: [[0, 'asc']],
            searching: true,
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
            dom: "<'row g-2 align-items-center mb-2'<'col-sm-6'l><'col-sm-6'f>>" +
                 "<'row'<'col-12'tr>>" +
                 "<'row g-2 mt-2'<'col-sm-5'i><'col-sm-7'p>>",
            language: {
                search: 'Buscar:',
                searchPlaceholder: 'Buscar...',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_',
                infoEmpty: 'Sin registros',
                infoFiltered: '(filtrado de _MAX_)',
                paginate: { first: 'Primera', last: 'Última', next: 'Siguiente', previous: 'Anterior' },
                zeroRecords: 'No hay coincidencias'
            },
            autoWidth: false, 
            orderSelector: '.sort-target', // 1️⃣ El ordenamiento nativo SOLO responderá al hacer clic en este elemento
            columnDefs: [
                { 
                    orderable: false, 
                    searchable: false, 
                    targets: -1,
                    width: '130px', 
                    className: 'text-center' 
                }
            ]
        });
    }

    function setTextoBotonTabla(catalogo, visible) {
        var btn = document.querySelector('.btn-cargar-tabla[data-catalogo="' + catalogo + '"]');
        if (!btn) return;
        btn.innerHTML = visible
            ? '<i class="bi bi-eye-slash"></i> Ocultar tabla'
            : '<i class="bi bi-eye"></i> Mostrar tabla';
    }

    function cargarCatalogo(catalogo) {
        var wrap = document.getElementById('wrap-' + catalogo);
        var table = document.getElementById('tabla-' + catalogo);
        if (!wrap || !table) return;

        getJson(API_URL + '?action=list&catalogo=' + encodeURIComponent(catalogo))
            .then(function (res) {
                if (!res.ok) {
                    if (window.showToast) window.showToast(res.message || 'Error al cargar datos.', 'error');
                    return;
                }
                table.querySelector('thead').innerHTML = buildThead(catalogo);
                table.querySelector('tbody').innerHTML = buildRows(catalogo, res.items || []);
                wrap.style.display = 'block';
                setTextoBotonTabla(catalogo, true);
                initOrRefreshDataTable(catalogo);
                cargadas[catalogo] = true;
            })
            .catch(function () {
                if (window.showToast) window.showToast('Error de conexión al cargar catálogo.', 'error');
            });
    }

    /** Al cambiar de pestaña: ocultar tabla, liberar DataTable y forzar recarga fresca en el próximo "Mostrar tabla". */
    function ocultarYLimpiarCatalogo(catalogo) {
        if (!catalogo) return;
        var wrap = document.getElementById('wrap-' + catalogo);
        if (wrap) {
            wrap.style.display = 'none';
        }
        setTextoBotonTabla(catalogo, false);
        if (typeof window.jQuery !== 'undefined' && window.jQuery.fn.DataTable && tablas[catalogo]) {
            try {
                tablas[catalogo].destroy();
            } catch (err) {
                /* tabla no inicializada o instancia ya destruida */
            }
        }
        tablas[catalogo] = null;
        cargadas[catalogo] = false;
    }

    function limpiarToastsVisibles() {
        var c = document.getElementById('toast-container');
        if (!c) return;
        c.querySelectorAll('.toast-item').forEach(function (el) {
            if (el._toastTimer) {
                clearTimeout(el._toastTimer);
            }
            el.remove();
        });
    }

    function limpiarMensajesModalCatalogo() {
        var eb = document.getElementById('catalogoError');
        var fm = document.getElementById('formCatalogo');
        if (eb) {
            eb.style.display = 'none';
            eb.textContent = '';
        }
        if (fm) {
            fm.classList.remove('was-validated');
        }
    }

    var tabsNav = document.getElementById('catalogosTabs');
    if (tabsNav) {
        tabsNav.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (btn) {
            btn.addEventListener('hide.bs.tab', function () {
                var sel = btn.getAttribute('data-bs-target');
                if (!sel) return;
                var pane = document.querySelector(sel);
                if (!pane || !pane.id) return;
                var m = pane.id.match(/^tab-(.+)$/);
                if (!m) return;
                ocultarYLimpiarCatalogo(m[1]);
            });
        });
        tabsNav.addEventListener('shown.bs.tab', function () {
            limpiarToastsVisibles();
            limpiarMensajesModalCatalogo();
        });
    }

    function recargarTablaCatalogo(catalogo) {
        if (!tablas[catalogo]) {
            if (cargadas[catalogo]) return;
            cargarCatalogo(catalogo);
            return;
        }
        getJson(API_URL + '?action=list&catalogo=' + encodeURIComponent(catalogo))
            .then(function (res) {
                if (!res.ok) {
                    if (window.showToast) window.showToast(res.message || 'Error al recargar datos.', 'error');
                    return;
                }
                var dt = tablas[catalogo];
                var rows = rowsForDataTable(catalogo, res.items || []);
                dt.clear();
                if (rows.length) dt.rows.add(rows);
                dt.draw(false);
            })
            .catch(function () {
                if (window.showToast) window.showToast('Error de conexión al recargar catálogo.', 'error');
            });
    }

    var modalEl = document.getElementById('modalCatalogo');
    var modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    var form = document.getElementById('formCatalogo');
    var fieldsWrap = document.getElementById('catalogoFields');
    var errorBox = document.getElementById('catalogoError');

    var IDENTITY_FIELDS = { nidentificacion: true, ruc: true, cedula: true };

    function aplicarValidacionIdentificacionPersona() {
        var catalogoActual = document.getElementById('cat_catalogo');
        if (!catalogoActual || catalogoActual.value !== 'personas') return;

        var tipoEl = document.getElementById('cat_field_tidentif');
        var identEl = document.getElementById('cat_field_nidentificacion');
        if (!identEl) return;

        function ajustarInput() {
            var tipo = normalizarTipoIdentificacion(tipoEl ? tipoEl.value : '');
            var val = String(identEl.value || '');
            if (tipo === 'Cédula') {
                val = val.replace(/\D/g, '').slice(0, 10);
                identEl.setAttribute('maxlength', '10');
                identEl.setAttribute('inputmode', 'numeric');
            } else if (tipo === 'RUC') {
                val = val.replace(/\D/g, '').slice(0, 13);
                identEl.setAttribute('maxlength', '13');
                identEl.setAttribute('inputmode', 'numeric');
            } else {
                val = val.slice(0, 20);
                identEl.setAttribute('maxlength', '20');
                identEl.setAttribute('inputmode', 'text');
            }
            if (identEl.value !== val) identEl.value = val;
        }

        ajustarInput();
        identEl.addEventListener('input', ajustarInput);
        if (tipoEl) tipoEl.addEventListener('change', ajustarInput);
    }

    function aplicarValidacionRucEmpresas() {
        var catalogoActual = document.getElementById('cat_catalogo');
        if (!catalogoActual || catalogoActual.value !== 'empresas') return;
        var rucEl = document.getElementById('cat_field_ruc');
        if (!rucEl) return;

        function ajustarRuc() {
            var val = String(rucEl.value || '').replace(/\D/g, '').slice(0, 13);
            if (rucEl.value !== val) rucEl.value = val;
            rucEl.setAttribute('maxlength', '13');
            rucEl.setAttribute('inputmode', 'numeric');
        }

        ajustarRuc();
        rucEl.addEventListener('input', ajustarRuc);
    }

    function openModal(catalogo, action, data) {
        var cfg = defs[catalogo];
        if (!cfg || !form || !fieldsWrap || !modal) return;
        form.classList.remove('was-validated');
        var tituloModal = cfg.modalLabelSingular
            ? ((action === 'update' ? 'Editar ' : 'Nuevo ') + cfg.modalLabelSingular)
            : ((action === 'update' ? 'Editar ' : 'Nuevo ') + cfg.title.slice(0, -1));
        document.getElementById('modalCatalogoLabel').textContent = tituloModal;
        document.getElementById('cat_action').value = action;
        document.getElementById('cat_catalogo').value = catalogo;
        var pkName = pkFor(catalogo);
        document.getElementById('cat_id').value =
            data && data[pkName] != null && data[pkName] !== '' ? String(data[pkName]) : '';
        errorBox.style.display = 'none';
        errorBox.textContent = '';

        var html = '';
        var isUpdate = action === 'update';
        cfg.fields.forEach(function (f) {
            var val = data && data[f.name] !== undefined ? String(data[f.name]) : '';
            if (catalogo === 'personas' && f.name === 'tidentif') {
                val = normalizarTipoIdentificacion(val);
            }
            var idInput = 'cat_field_' + f.name;
            var lockId = isUpdate && (IDENTITY_FIELDS[f.name] || (catalogo === 'niveles_incidente' && f.name === 'nivel') || (f.name === 'tidentif' && f.type === 'select'));
            if (f.type === 'select') {
                var opts;
                if (lockId) {
                    opts = f.options.map(function (op) {
                        return '<option value="' + esc(op.v) + '"' + (val === op.v ? ' selected' : '') + '>' + esc(op.t) + '</option>';
                    }).join('');
                    html += '<div class="col-12 col-md-6"><label class="form-label fw-semibold" for="' + esc(idInput) + '">' + esc(f.label) + '</label>' +
                        '<div class="input-group apm-lock-group">' +
                        '<span class="input-group-text bg-light text-muted"><i class="bi bi-lock-fill" aria-hidden="true"></i></span>' +
                        '<select class="form-select bg-light text-muted" id="' + esc(idInput) + '" disabled tabindex="-1">' + opts + '</select>' +
                        '</div>' +
                        '<p class="form-text text-muted mb-0 mt-1">La identificación no puede ser modificada por seguridad de identidad</p></div>';
                } else {
                    opts = (f.required ? '<option value="">Seleccione…</option>' : '') +
                        f.options.map(function (op) {
                            return '<option value="' + esc(op.v) + '"' + (val === op.v ? ' selected' : '') + '>' + esc(op.t) + '</option>';
                        }).join('');
                    html += '<div class="col-12 col-md-6"><label class="form-label fw-semibold" for="' + esc(idInput) + '">' + esc(f.label) + '</label>' +
                        '<select class="form-select" id="' + esc(idInput) + '" name="' + esc(f.name) + '"' + (f.required ? ' required' : '') + '>' + opts + '</select>' +
                        '<div class="invalid-feedback">Seleccione un tipo de identificación.</div></div>';
                }
            } else {
                var req = (!lockId && f.required) ? ' required' : '';
                var nameAttr = lockId ? '' : ' name="' + esc(f.name) + '"';
                if (lockId) {
                    html += '<div class="col-12 col-md-6">' +
                        '<label class="form-label fw-semibold" for="' + esc(idInput) + '">' + esc(f.label) + '</label>' +
                        '<div class="input-group apm-lock-group">' +
                        '<span class="input-group-text bg-light text-muted"><i class="bi bi-lock-fill" aria-hidden="true"></i></span>' +
                        '<input type="text" class="form-control bg-light text-muted" id="' + esc(idInput) + '" value="' + esc(val) + '"' +
                        (f.max ? ' maxlength="' + f.max + '"' : '') + ' readonly tabindex="-1">' +
                        '</div>' +
                        '<p class="form-text text-muted mb-0 mt-1">La identificación no puede ser modificada por seguridad de identidad</p>' +
                        '</div>';
                } else {
                    html += '<div class="col-12 col-md-6"><label class="form-label fw-semibold" for="' + esc(idInput) + '">' + esc(f.label) + '</label>' +
                        '<input class="form-control" id="' + esc(idInput) + '"' + nameAttr + ' value="' + esc(val) + '"' +
                        (f.max ? ' maxlength="' + f.max + '"' : '') + req + '>' +
                        '<div class="invalid-feedback">Complete este campo correctamente.</div></div>';
                }
            }
        });
        fieldsWrap.innerHTML = html;
        aplicarValidacionIdentificacionPersona();
        aplicarValidacionRucEmpresas();
        modal.show();
    }

    document.addEventListener('click', function (e) {
        var btnCargar = e.target.closest('.btn-cargar-tabla');
        if (btnCargar) {
            var catalogo = btnCargar.getAttribute('data-catalogo');
            var wrap = document.getElementById('wrap-' + catalogo);
            if (wrap && wrap.style.display !== 'none') {
                ocultarYLimpiarCatalogo(catalogo);
            } else {
                cargarCatalogo(catalogo);
            }
            return;
        }

        var btnCrear = e.target.closest('.btn-crear-registro');
        if (btnCrear) {
            openModal(btnCrear.getAttribute('data-catalogo'), 'create', null);
            return;
        }

        var btnEditar = e.target.closest('.btn-editar');
        if (btnEditar) {
            var cat = btnEditar.getAttribute('data-catalogo');
            var id = btnEditar.getAttribute('data-id');
            getJson(API_URL + '?action=get&catalogo=' + encodeURIComponent(cat) + '&id=' + encodeURIComponent(id))
                .then(function (res) {
                    if (!res.ok || !res.item) {
                        if (window.showToast) window.showToast(res.message || 'No se pudo cargar el registro.', 'error');
                        return;
                    }
                    var item = Object.assign({}, res.item, { id: id });
                    openModal(cat, 'update', item);
                })
                .catch(function () {
                    if (window.showToast) window.showToast('Error de conexión.', 'error');
                });
            return;
        }

        var btnDes = e.target.closest('.btn-desactivar');
        if (btnDes) {
            var c = btnDes.getAttribute('data-catalogo');
            var rid = btnDes.getAttribute('data-id');
            if (!window.confirm('¿Está seguro de desactivar este registro?')) return;
            var fd = new FormData();
            fd.append('action', 'deactivate');
            fd.append('catalogo', c);
            fd.append('id', rid);
            postForm(fd).then(function (res) {
                if (!res.ok) {
                    alertarErrorDesactivarCatalogo(res.message);
                    return;
                }
                if (window.showToast) window.showToast('Registro desactivado correctamente.', 'success');
                recargarTablaCatalogo(c);
            }).catch(function () {
                alertarErrorDesactivarCatalogo('Error de conexión al desactivar el registro.');
            });
        }
    });

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }
            var cat = document.getElementById('cat_catalogo').value;
            var actionActual = document.getElementById('cat_action') ? document.getElementById('cat_action').value : 'create';

            if (cat === 'personas' && actionActual === 'create') {
                var tipoEl = document.getElementById('cat_field_tidentif');
                var identEl = document.getElementById('cat_field_nidentificacion');
                var tipo = normalizarTipoIdentificacion(tipoEl ? tipoEl.value : '');
                var ident = String(identEl ? identEl.value : '').trim();
                var identNum = ident.replace(/\D/g, '');
                
                // 🟢 CORRECCIÓN: Homologación de nombres de funciones según bit_validaciones_ecuador.php
                if (tipo === 'Cédula') {
                    if (identNum.length !== 10 || (typeof ec_validar_cedula_ecuador === 'function' ? !ec_validar_cedula_ecuador(identNum) : (typeof ecValidarCedulaEcuador === 'function' && !ecValidarCedulaEcuador(identNum)))) {
                        form.classList.add('was-validated');
                        errorBox.textContent = typeof APM_MSG_IDENTIFICACION_INVALIDA !== 'undefined' ? APM_MSG_IDENTIFICACION_INVALIDA : 'La identificación ingresada no es válida.';
                        errorBox.style.display = 'block';
                        return;
                    }
                }
                if (tipo === 'RUC') {
                    if (identNum.length !== 13 || (typeof ec_validar_ruc_ecuador === 'function' ? !ec_validar_ruc_ecuador(identNum) : (typeof ecValidarRucEcuador === 'function' && !ecValidarRucEcuador(identNum)))) {
                        form.classList.add('was-validated');
                        errorBox.textContent = typeof APM_MSG_IDENTIFICACION_INVALIDA !== 'undefined' ? APM_MSG_IDENTIFICACION_INVALIDA : 'La identificación ingresada no es válida.';
                        errorBox.style.display = 'block';
                        return;
                    }
                }
            }
            if (cat === 'empresas' && actionActual === 'create') {
                var rucEl = document.getElementById('cat_field_ruc');
                var rucVal = String(rucEl ? rucEl.value : '').trim();
                var rucNum = rucVal.replace(/\D/g, '');
                
                // 🟢 CORRECCIÓN: Homologación de nombres de funciones para el RUC corporativo
                if (rucNum.length !== 13 || (typeof ec_validar_ruc_ecuador === 'function' ? !ec_validar_ruc_ecuador(rucNum) : (typeof ecValidarRucEcuador === 'function' && !ecValidarRucEcuador(rucNum)))) {
                    form.classList.add('was-validated');
                    errorBox.textContent = typeof APM_MSG_IDENTIFICACION_INVALIDA !== 'undefined' ? APM_MSG_IDENTIFICACION_INVALIDA : 'La identificación ingresada no es válida.';
                    errorBox.style.display = 'block';
                    return;
                }
            }
            
            form.classList.remove('was-validated');
            var fd = new FormData(form);
            if (cat === 'personas' && fd.has('nidentificacion')) {
                var t = normalizarTipoIdentificacion(String(fd.get('tidentif') || ''));
                fd.set('tidentif', t);
                var n = String(fd.get('nidentificacion') || '').replace(/\D/g, '');
                fd.set('nidentificacion', t === 'RUC' ? n.slice(0, 13) : n.slice(0, 10));
            }
            if (cat === 'empresas' && fd.has('ruc')) {
                fd.set('ruc', String(fd.get('ruc') || '').replace(/\D/g, '').slice(0, 13));
            }
            postForm(fd).then(function (res) {
                if (!res.ok) {
                    errorBox.textContent = res.message || 'No se pudo guardar.';
                    errorBox.style.display = 'block';
                    return;
                }
                errorBox.style.display = 'none';
                errorBox.textContent = '';
                var catName = fd.get('catalogo');
                if (window.showToast) window.showToast('Registro guardado correctamente.', 'success');
                modal.hide();
                recargarTablaCatalogo(catName);
            }).catch(function () {
                errorBox.textContent = 'Error de comunicación con el servidor.';
                errorBox.style.display = 'block';
            });
        });
    }


    function abrirCatalogoDesdeUrl() {
        try {
            var params = new URLSearchParams(window.location.search);
            var catalogo = params.get('catalogo') || '';
            if (!catalogo || !defs[catalogo]) return;

            var tabBtn = document.querySelector('[data-bs-target="#tab-' + catalogo + '"]');
            if (tabBtn && typeof bootstrap !== 'undefined') {
                bootstrap.Tab.getOrCreateInstance(tabBtn).show();
            }

            setTimeout(function () {
                cargarCatalogo(catalogo);
            }, 250);
        } catch (err) {
            /* navegación por URL no disponible */
        }
    }

    function abrirCatalogoDefaultDesdePaginaMaestro() {
        try {
            var catalogo = window.APM_CATALOGO_DEFAULT || '';
            if (!catalogo || !defs[catalogo]) return;

            var wrap = document.getElementById('wrap-' + catalogo);
            if (wrap) {
                wrap.style.display = 'block';
            }

            setTimeout(function () {
                cargarCatalogo(catalogo);
            }, 150);
        } catch (err) {
            /* maestro individual no disponible */
        }
    }

    abrirCatalogoDefaultDesdePaginaMaestro();
    abrirCatalogoDesdeUrl();

});
