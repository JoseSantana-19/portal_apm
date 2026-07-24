document.addEventListener('DOMContentLoaded', function () {
    if (document.body.getAttribute('data-apm-toast-ingreso') === '1' && window.showToast) {
        window.showToast('Visita registrada correctamente.', 'success');
    }

    // Evita que el mensaje (msg=...) se vuelva a mostrar si el usuario recarga la página.
    try {
        var url = new URL(window.location.href);
        if (url.searchParams.has('msg')) {
            url.searchParams.delete('msg');
            window.history.replaceState({}, '', url.pathname + (url.searchParams.toString() ? ('?' + url.searchParams.toString()) : ''));
        }
    } catch (e) { /* noop */ }

    var tablaVisitas = null;

    function apmPopoverTituloDesdeTd(td) {
        if (!td) return 'Detalle';
        var lab = td.getAttribute('data-apm-label');
        return lab && lab.length ? lab : 'Detalle';
    }

    function disposeListadoTruncPopovers(table) {
        if (!table || typeof bootstrap === 'undefined' || !bootstrap.Popover) return;
        table.querySelectorAll('tbody td.td-truncate .apm-truncate-text').forEach(function (el) {
            var pop = bootstrap.Popover.getInstance(el);
            if (pop) pop.dispose();
            el.removeAttribute('aria-describedby');
        });
    }

    function initListadoTruncation() {
        var table = document.getElementById('tablaVisitas');
        if (!table || typeof bootstrap === 'undefined' || !bootstrap.Popover) return;

        function runPass() {
            disposeListadoTruncPopovers(table);
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    table.querySelectorAll('tbody td.td-truncate').forEach(function (td) {
                        var textEl = td.querySelector('.apm-truncate-text');
                        var btn = td.querySelector('.apm-btn-expand');
                        var rowWrap = textEl ? textEl.closest('.apm-truncate-row') : null;
                        var forceModalBtn = rowWrap && rowWrap.getAttribute('data-apm-expand-modal-only') === '1';
                        if (!textEl) return;
                        var full = (textEl.getAttribute('data-apm-full') || textEl.textContent || '').replace(/\s+/g, ' ').trim();
                        var overflow = textEl.scrollWidth > textEl.clientWidth + 0.5;
                        if (btn) {
                            if ((overflow && full) || forceModalBtn) {
                                btn.classList.remove('d-none');
                            } else {
                                btn.classList.add('d-none');
                            }
                        }
                        if (overflow && full) {
                            new bootstrap.Popover(textEl, {
                                title: apmPopoverTituloDesdeTd(td),
                                content: full,
                                html: false,
                                sanitize: true,
                                placement: 'bottom',
                                trigger: 'hover focus',
                                container: 'body',
                                boundary: 'viewport',
                                customClass: 'apm-popover-dashboard',
                                delay: { show: 200, hide: 0 }
                            });
                        }
                    });
                });
            });
        }

        window.setTimeout(runPass, 80);
        window.setTimeout(runPass, 420);
    }

    var visitasResizeObserver = null;
    var visitasRoTimer = null;

    function ajustarColumnasVisitasDt() {
        if (!tablaVisitas) return;
        var $table = typeof window.jQuery !== 'undefined' ? window.jQuery('#tablaVisitas') : null;
        var $wrapper = $table && $table.length ? $table.closest('.dataTables_wrapper') : null;
        var run = function () {
            try {
                if ($wrapper && $wrapper.length) {
                    $wrapper.css({ width: '100%', maxWidth: '100%', boxSizing: 'border-box' });
                }
                if ($table && $table.length) {
                    $table.css({ width: '100%', maxWidth: '100%', tableLayout: 'fixed' });
                }
                tablaVisitas.columns.adjust();
                tablaVisitas.draw(false);
            } catch (e) { /* noop */ }
        };
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(run);
            });
        } else {
            run();
        }
    }

    var visitasDtOptions = {
        autoWidth: false,
        order: [[0, 'desc'], [8, 'desc']],
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'Todos']],
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_',
            infoEmpty: 'Sin registros',
            infoFiltered: '(filtrado de _MAX_)',
            paginate: { first: 'Primera', last: 'Última', next: 'Siguiente', previous: 'Anterior' },
            zeroRecords: 'No hay coincidencias'
        },
        columnDefs: [
            { width: '7%', targets: 0 },
            { width: '11%', targets: 1 },
            { width: '13%', targets: 2 },
            { width: '7%', targets: 3 },
            { width: '11%', targets: 4 },
            { width: '8%', targets: 5 },
            { width: '10%', targets: 6 },
            { width: '8%', targets: 7 },
            { width: '5%', targets: 8 },
            { width: '6%', targets: 9 },
            { width: '6%', targets: 10 },
            { orderable: false, searchable: false, width: '8%', targets: 11 }
        ],
        drawCallback: function () {
            initListadoTruncation();
        }
    };

    if (typeof $ !== 'undefined' && $.fn.DataTable && document.getElementById('tablaVisitas')) {
        tablaVisitas = $('#tablaVisitas').DataTable(visitasDtOptions);
        window.setTimeout(ajustarColumnasVisitasDt, 400);

        if (typeof window.ResizeObserver === 'function') {
            visitasResizeObserver = new window.ResizeObserver(function () {
                window.clearTimeout(visitasRoTimer);
                visitasRoTimer = window.setTimeout(ajustarColumnasVisitasDt, 32);
            });
            var mainEl = document.querySelector('.apm-main');
            if (mainEl) {
                visitasResizeObserver.observe(mainEl);
            }
            var wrapEl = document.querySelector('.apm-tabla-visitas-wrap');
            if (wrapEl) {
                visitasResizeObserver.observe(wrapEl);
            }
        }
    }

    function apmModalBodyMotivoHtml(rawText) {
        var raw = rawText == null ? '' : String(rawText);
        var sep = '\n\nDetalle del motivo:\n';
        var i = raw.indexOf(sep);
        if (i === -1) {
            return null;
        }
        var bloqueMotivo = raw.substring(0, i).trim();
        var detalle = raw.substring(i + sep.length);
        return '<p class="mb-2 text-break">' + esc(bloqueMotivo) + '</p>' +
            '<p class="mb-1 fw-bold">Detalle del motivo:</p>' +
            '<p class="mb-0 text-break">' + esc(detalle) + '</p>';
    }

    var modalDetalleTexto = document.getElementById('modalDetalleTexto');
    if (modalDetalleTexto && typeof bootstrap !== 'undefined') {
        modalDetalleTexto.addEventListener('show.bs.modal', function (ev) {
            var btn = ev.relatedTarget;
            var body = document.getElementById('modalDetalleTextoBody');
            var titleEl = document.getElementById('modalDetalleTextoLabel');
            if (!body) return;
            if (btn && btn.getAttribute('data-apm-text') !== null) {
                var campo = btn.getAttribute('data-apm-campo') || '';
                var texto = btn.getAttribute('data-apm-text') || '';
                if (campo === 'Motivo y detalle') {
                    var motivoHtml = apmModalBodyMotivoHtml(texto);
                    if (motivoHtml) {
                        body.innerHTML = motivoHtml;
                    } else {
                        body.textContent = texto;
                    }
                } else {
                    body.textContent = texto;
                }
                if (titleEl) titleEl.textContent = campo || 'Texto completo';
            } else {
                body.textContent = '';
                if (titleEl) titleEl.textContent = 'Texto completo';
            }
        });
    }

    window.addEventListener('apm:sidebar-layout-changed', function () {
        ajustarColumnasVisitasDt();
        window.setTimeout(ajustarColumnasVisitasDt, 80);
        window.setTimeout(ajustarColumnasVisitasDt, 340);
    });
    var resizeVisitasTimer;
    window.addEventListener('resize', function () {
        window.clearTimeout(resizeVisitasTimer);
        resizeVisitasTimer = window.setTimeout(ajustarColumnasVisitasDt, 200);
    });

    function getRowByVisitaId(idVisita) {
        var tr = document.querySelector('tr[data-id-visita="' + idVisita + '"]');
        return tr ? $(tr) : null;
    }

    function apmSincronizarAccionesListado($row, d, idVisita) {
        var $g = $row.find('.apm-acciones-group');
        if (!$g.length) return;
        $g.find('.btn-registrar-salida').remove();
        $g.find('button[data-bs-target="#modalAsignarCedula"]').remove();

        var $edit = $g.find('.btn-editar-visita').first();
        if (!$edit.length) return;

        var idVs = String(idVisita == null ? '' : idVisita);
        var nodes = [];

        if (d.mostrar_btn_asignar_cedula_guest) {
            var idP = d.id_persona != null ? String(d.id_persona) : '0';
            var nom = d.nombre_visitante != null ? String(d.nombre_visitante) : '';
            nodes.push($('<button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalAsignarCedula" data-id-persona="' + escAttr(idP) + '" data-nombre="' + escAttr(nom) + '" title="Asignar cédula" aria-label="Asignar cédula al visitante Guest"><i class="bi bi-person-vcard" aria-hidden="true"></i></button>'));
        }
        if (d.mostrar_btn_registrar_salida) {
            nodes.push($('<button type="button" class="btn btn-outline-danger btn-sm btn-registrar-salida" data-id-visita="' + escAttr(idVs) + '" title="Registrar salida" aria-label="Registrar salida"><i class="bi bi-clock-history" aria-hidden="true"></i></button>'));
        }
        if (nodes.length) {
            $edit.before.apply($edit, nodes);
        }
    }

    function esc(s) { return (s == null || s === undefined) ? '' : String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
    function escAttr(s) { return (s == null || s === undefined) ? '' : String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

    function apmHtmlTruncCell(fullText, modalCampo, modalBody, forceExpandModal) {
        var full = (fullText == null || fullText === undefined) ? '' : String(fullText);
        modalBody = modalBody != null ? String(modalBody) : full;
        var rowAttr = forceExpandModal ? ' data-apm-expand-modal-only="1"' : '';
        return '<div class="apm-truncate-row d-flex align-items-center gap-1 min-w-0 w-100"' + rowAttr + '>' +
            '<span class="apm-truncate-text" data-apm-full="' + escAttr(full) + '">' + esc(full) + '</span>' +
            '<button type="button" class="btn btn-link btn-sm p-0 apm-btn-expand d-none" data-bs-toggle="modal" data-bs-target="#modalDetalleTexto" data-apm-campo="' + escAttr(modalCampo) + '" data-apm-text="' + escAttr(modalBody) + '" title="Ver más" aria-label="Ver texto completo"><i class="bi bi-plus-circle fs-6" aria-hidden="true"></i></button></div>';
    }

    function apmHtmlEmpresaListado(empresaLabel) {
        if (empresaLabel === 'Personal' || !empresaLabel) {
            return '<span class="badge bg-secondary">Personal</span>';
        }
        return apmHtmlTruncCell(empresaLabel, 'Empresa / Personal', empresaLabel, false);
    }

    function apmHtmlMotivoListado(motivo, detalleMotivo) {
        var detalle = (detalleMotivo == null) ? '' : String(detalleMotivo).trim();
        var textoModal = 'Motivo: ' + (motivo || '');
        textoModal += '\n\nDetalle del motivo:\n' + (detalle || '(No especificado)');
        return apmHtmlTruncCell(motivo || '', 'Motivo y detalle', textoModal, detalle !== '');
    }

    // 🔄 REEMPLAZO: Cambiado a la ruta amigable de tu nuevo framework MVC
    // Recarga parcial del listado (solo tbody) restaurando los estilos de Bootstrap 5
    function refreshListadoVisitas() {
        var searchVal = '';
        var pageIndex = 0;
        var pageLen = 10;

        if (tablaVisitas) {
            try {
                searchVal = tablaVisitas.search() || '';
                pageIndex = tablaVisitas.page() || 0;
                pageLen = tablaVisitas.page.len() || 10;
            } catch (e) { /* noop */ }
        }

        if (tablaVisitas) {
            disposeListadoTruncPopovers(document.getElementById('tablaVisitas'));
            try { tablaVisitas.destroy(); } catch (e) { /* noop */ }
            tablaVisitas = null;
        }

        fetch('bitacoras/visita/listado', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, 'text/html');
                var newTbody = doc.querySelector('#tablaVisitas tbody');
                var oldTbody = document.querySelector('#tablaVisitas tbody');

                if (newTbody && oldTbody) {
                    oldTbody.innerHTML = newTbody.innerHTML;
                }

                if (typeof $ !== 'undefined' && $.fn.DataTable && document.getElementById('tablaVisitas')) {
                    var optsRefresh = Object.assign({}, visitasDtOptions, { pageLength: pageLen || 10 });
                    tablaVisitas = $('#tablaVisitas').DataTable(optsRefresh);

                    if (searchVal) tablaVisitas.search(searchVal);
                    try { tablaVisitas.page(pageIndex).draw(false); } catch (e) { /* noop */ }
                    
                    // 🔥 CORRECCIÓN VISUAL: Forzar la inicialización del truncamiento y ajustar anchos de columnas de inmediato
                    initListadoTruncation();
                    window.setTimeout(ajustarColumnasVisitasDt, 50);
                    window.setTimeout(ajustarColumnasVisitasDt, 200);
                }
            })
            .catch(function () { /* noop */ });
    }

    function cargarTablaVisitas() {
        refreshListadoVisitas();
    }

    var modalEditarVisita = document.getElementById('modalEditarVisita');
    if (modalEditarVisita) {
        modalEditarVisita.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;
            document.getElementById('edit_id_visita').value = button.getAttribute('data-id-visita') || '';
            document.getElementById('edit_id_empresa').value = button.getAttribute('data-id-empresa') || '';
            document.getElementById('edit_id_funcionario').value = button.getAttribute('data-id-funcionario') || '';
            document.getElementById('edit_id_destino').value = button.getAttribute('data-id-destino') || '';
            document.getElementById('edit_id_motivo').value = button.getAttribute('data-id-motivo') || '';
            document.getElementById('edit_detalle_motivo').value = button.getAttribute('data-detalle-motivo') || '';
            document.getElementById('edit_fecha_visita').value = button.getAttribute('data-fecha') || '';
            document.getElementById('edit_hora_entrada').value = button.getAttribute('data-hora-entrada') || '';
            document.getElementById('edit_hora_salida').value = button.getAttribute('data-hora-salida') || '';

            var fechaEdit = document.getElementById('edit_fecha_visita');
            var horaEntradaEdit = document.getElementById('edit_hora_entrada');
            if (fechaEdit) fechaEdit.readOnly = true;
            if (horaEntradaEdit) horaEntradaEdit.readOnly = true;
        });
    }

    // 🔄 REEMPLAZO: Cambiado a la acción de actualización del Controlador
    var formEditarVisita = document.getElementById('formEditarVisita');
    if (formEditarVisita) {
        formEditarVisita.addEventListener('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(formEditarVisita);
            var elHs = document.getElementById('edit_hora_salida');
            formData.set('hora_salida', elHs && elHs.value ? String(elHs.value).trim() : '');
            var idVisita = formData.get('id_visita');
            if (!idVisita || String(idVisita).trim() === '' || isNaN(parseInt(idVisita, 10))) {
                if (window.showToast) window.showToast('No se encontró el ID de la visita para actualizar.', 'error');
                return;
            }

            // 🟢 Apunta al método actualizar del VisitaController
            fetch('bitacoras/visita/actualizar', { method: 'POST', body: formData })
                .then(function (response) {
                    return response.text().then(function (txt) {
                        var res;
                        try { res = JSON.parse(txt); } catch (err) {
                            throw new Error('Respuesta inválida del servidor al editar la visita.');
                        }
                        if (!response.ok || !res.ok) {
                            throw new Error((res && res.message) ? res.message : 'Error al guardar cambios.');
                        }
                        return res;
                    });
                })
                .then(function (res) {
                    var d = res.data || {};
                    var $row = getRowByVisitaId(idVisita);
                    if ($row && $row.length) {
                        var cells = $row[0].cells;
                        if (cells[0]) {
                            cells[0].className = 'apm-col-fecha';
                            cells[0].innerHTML = '<span class="apm-fecha-text">' + esc(d.fecha_dmy || '') + '</span>';
                            if (d.fecha_ymd) { cells[0].setAttribute('data-order', d.fecha_ymd); } else { cells[0].removeAttribute('data-order'); }
                        }
                        if (cells[2]) {
                            var empLab = d.empresa_label === 'Personal' || !d.empresa_label ? 'Personal' : d.empresa_label;
                            cells[2].setAttribute('data-apm-label', 'Empresa / Personal');
                            cells[2].className = empLab === 'Personal' ? '' : 'td-truncate';
                            cells[2].innerHTML = apmHtmlEmpresaListado(empLab);
                        }
                        if (cells[4]) {
                            cells[4].className = 'td-truncate';
                            cells[4].setAttribute('data-apm-label', 'Funcionario');
                            cells[4].innerHTML = apmHtmlTruncCell(d.funcionario || '', 'Funcionario', d.funcionario || '', false);
                        }
                        if (cells[5]) {
                            cells[5].className = 'td-truncate';
                            cells[5].setAttribute('data-apm-label', 'Destino');
                            cells[5].innerHTML = apmHtmlTruncCell(d.destino || '', 'Destino', d.destino || '', false);
                        }
                        if (cells[6]) {
                            cells[6].className = 'td-truncate';
                            cells[6].setAttribute('data-apm-label', 'Motivo');
                            cells[6].innerHTML = apmHtmlMotivoListado(d.motivo || '', d.detalle_motivo || '');
                        }
                        if (cells[8]) {
                            cells[8].className = 'td-truncate';
                            cells[8].setAttribute('data-apm-label', 'Hora de entrada');
                            cells[8].innerHTML = apmHtmlTruncCell(d.hora_entrada || '', 'Hora de entrada', d.hora_entrada || '', false);
                            if (d.hora_entrada) { cells[8].setAttribute('data-order', d.hora_entrada); } else { cells[8].removeAttribute('data-order'); }
                        }
                        if (cells[9]) {
                            cells[9].setAttribute('data-apm-label', 'Hora de salida');
                            if (d.hora_salida_null) {
                                cells[9].className = '';
                                cells[9].innerHTML = '<span class="text-muted text-nowrap">Pendiente</span>';
                            } else {
                                cells[9].className = 'td-truncate';
                                cells[9].innerHTML = apmHtmlTruncCell(d.hora_salida || '', 'Hora de salida', d.hora_salida || '', false);
                            }
                        }
                        if (cells[10]) cells[10].innerHTML = d.estado_label === 'Dentro' ? '<span class="badge bg-success">Dentro</span>' : '<span class="badge bg-secondary">Finalizada</span>';
                        var btnEdit = $row.find('.btn-editar-visita')[0];
                        if (btnEdit) {
                            btnEdit.setAttribute('data-id-empresa', formData.get('id_empresa') || '');
                            btnEdit.setAttribute('data-id-funcionario', formData.get('id_funcionario') || '');
                            btnEdit.setAttribute('data-id-destino', formData.get('id_destino') || '');
                            btnEdit.setAttribute('data-id-motivo', formData.get('id_motivo') || '');
                            btnEdit.setAttribute('data-detalle-motivo', formData.get('detalle_motivo') || '');
                            btnEdit.setAttribute('data-fecha', d.fecha_ymd || formData.get('fecha_visita') || '');
                            btnEdit.setAttribute('data-hora-entrada', d.hora_entrada || formData.get('hora_entrada') || '');
                            btnEdit.setAttribute('data-hora-salida', d.hora_salida_null ? '' : (d.hora_salida || ''));
                        }
                        apmSincronizarAccionesListado($row, d, idVisita);
                        if (tablaVisitas) {
                            tablaVisitas.row($row[0]).invalidate();
                            tablaVisitas.draw(false);
                        }
                    }
                    if (window.showToast) window.showToast('Los cambios se han actualizado correctamente.', 'success');
                    var modal = bootstrap.Modal.getInstance(modalEditarVisita);
                    if (modal) modal.hide();
                })
                .catch(function (err) {
                    if (window.console && console.error) console.error('Error en editar visita:', err);
                    if (window.showToast) window.showToast((err && err.message) ? err.message : 'Error de conexión.', 'error');
                });
        });
    }

    // 🔄 REEMPLAZO: Cambiado al endpoint modular registrarSalida
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-registrar-salida');
        if (!btn) return;
        e.preventDefault();
        var idVisita = btn.getAttribute('data-id-visita');
        if (!idVisita || !confirm('¿Registrar salida de esta visita?')) return;

        // 🟢 Apunta a la acción modular del controlador
        fetch('bitacoras/visita/registrarSalida?id=' + encodeURIComponent(idVisita), {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                return response.text().then(function (txt) {
                    var data;
                    try { data = JSON.parse(txt); } catch (err) {
                        throw new Error('Respuesta inválida del servidor al registrar salida.');
                    }
                    if (!response.ok || !data.ok) {
                        throw new Error((data && data.message) || 'No se pudo registrar la salida.');
                    }
                    return data;
                });
            })
            .then(function () {
                if (window.showToast) { window.showToast('Salida registrada correctamente', 'success'); } else { window.alert('Salida registrada correctamente'); }
                cargarTablaVisitas();
            })
            .catch(function (err) {
                if (window.console && console.error) console.error('Error en registrar salida:', err);
                if (window.showToast) window.showToast((err && err.message) ? err.message : 'Error de conexión.', 'error');
            });
    });

    // 🔄 REEMPLAZO: Cambiado al controlador correspondiente para actualizar cédula guest
    var modalAsignar = document.getElementById('modalAsignarCedula');
    if (modalAsignar) {
        modalAsignar.addEventListener('show.bs.modal', function (event) {
            var btn = event.relatedTarget;
            if (btn) {
                document.getElementById('asignarCedulaIdPersona').value = btn.getAttribute('data-id-persona') || '';
                document.getElementById('asignarCedulaNombre').textContent = 'Visitante: ' + (btn.getAttribute('data-nombre') || '');
                document.getElementById('asignarCedulaInput').value = '';
                document.getElementById('asignarCedulaError').style.display = 'none';
            }
        });
        document.getElementById('asignarCedulaInput').addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });
        document.getElementById('btnAsignarCedula').addEventListener('click', function () {
            var idPersona = document.getElementById('asignarCedulaIdPersona').value;
            var cedula = document.getElementById('asignarCedulaInput').value.trim();
            var errEl = document.getElementById('asignarCedulaError');
            if (!cedula || cedula.length !== 10 || (typeof ecValidarCedulaEcuador === 'function' && !ecValidarCedulaEcuador(cedula))) {
                errEl.textContent = typeof APM_MSG_IDENTIFICACION_INVALIDA !== 'undefined' ? APM_MSG_IDENTIFICACION_INVALIDA : 'La identificación ingresada no es válida.';
                errEl.style.display = 'block';
                return;
            }
            errEl.style.display = 'none';
            var fd = new FormData();
            fd.append('id_persona', idPersona);
            fd.append('nueva_cedula', cedula);

            // 🟢 Nota: Apunta temporalmente a credenciales/usuario o bitacoras/visita según tu arquitectura de personas (por ahora amigable)
            fetch('bitacoras/visita/actualizarCedulaGuest', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.ok) {
                        if (window.showToast) window.showToast(data.message || 'Cédula asignada correctamente.', 'success');
                        var modal = bootstrap.Modal.getInstance(modalAsignar);
                        if (modal) modal.hide();
                        window.location.reload();
                    } else {
                        errEl.textContent = data.message || 'Error al asignar cédula.';
                        errEl.style.display = 'block';
                    }
                })
                .catch(function () {
                    errEl.textContent = 'Error de conexión.';
                    errEl.style.display = 'block';
                });
        });
    }
});