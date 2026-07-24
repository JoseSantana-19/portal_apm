(function () {
    'use strict';

    var LS_USUARIO = 'reporte_supervisor_usuario';
    var chartHora = null;
    var chartTipo = null;

    var elFecha = document.getElementById('filtroFecha');
    var elUsuario = document.getElementById('usuarioGenera');
    var elErr = document.getElementById('bloqueErrorApi');

    function showErr(msg) {
        if (!elErr) return;
        elErr.textContent = msg || '';
        elErr.classList.toggle('d-none', !msg);
    }

    function getUsuario() {
        var u = (elUsuario && elUsuario.value) ? elUsuario.value.trim() : '';
        if (!u) {
            try { u = localStorage.getItem(LS_USUARIO) || ''; } catch (e) {}
        }
        return u.trim();
    }

    function saveUsuario(u) {
        try { localStorage.setItem(LS_USUARIO, u); } catch (e) {}
    }

    function fmtFechaDMY(iso) {
        if (!iso || iso.length < 10) return iso;
        var p = iso.split('-');
        return p[2] + '/' + p[1] + '/' + p[0];
    }

    function horaLocalParaInputTime() {
        var d = new Date();
        var h = String(d.getHours()).padStart(2, '0');
        var m = String(d.getMinutes()).padStart(2, '0');
        return h + ':' + m;
    }

    /** Convierte "9:5" o "09:05:00" a "09:05" para input[type=time] */
    function horaApiAInput(t) {
        if (t == null || t === '') return '';
        var s = String(t).trim();
        var p = s.split(':');
        if (p.length < 2) return s;
        var hh = String(parseInt(p[0], 10)).padStart(2, '0');
        var mm = String(parseInt(p[1], 10)).padStart(2, '0');
        return hh + ':' + mm;
    }

    function renderEncabezado(r) {
        document.getElementById('hdrNumero').textContent = r.numero_reporte != null ? String(r.numero_reporte) : '—';
        document.getElementById('hdrFecha').textContent = fmtFechaDMY(r.fecha_reporte || '');
        document.getElementById('hdrUsuario').textContent = r.usuario_genera || '—';
        var c = r.creado_en || '';
        document.getElementById('hdrCreado').textContent = c ? (c.replace('T', ' ').substring(0, 19)) : '—';
    }

    function renderResumen(res) {
        document.getElementById('resTotal').textContent = String(res.total_visitas != null ? res.total_visitas : 0);
        document.getElementById('resActivas').textContent = String(res.visitas_activas != null ? res.visitas_activas : 0);
        document.getElementById('resProveedores').textContent = String(res.proveedores != null ? res.proveedores : 0);
    }

    function renderNovedades(list) {
        var tb = document.getElementById('tbodyNovedades');
        if (!tb) return;
        tb.innerHTML = '';
        if (!list || !list.length) {
            tb.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No hay novedades registradas para este día.</td></tr>';
            return;
        }
        list.forEach(function (n) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-idnovedad', String(n.idnovedad));
            tr.innerHTML =
                '<td class="text-nowrap">' + escapeHtml(n.hora || '') + '</td>' +
                '<td>' + escapeHtml(n.descripcion || '') + '</td>' +
                '<td><span class="badge bg-light text-dark border">' + escapeHtml(n.estado || '') + '</span></td>' +
                '<td class="text-end">' +
                '<button type="button" class="btn btn-sm btn-outline-primary btn-edit-novedad me-1" data-id="' + n.idnovedad + '">Editar</button>' +
                '<button type="button" class="btn btn-sm btn-outline-danger btn-del-novedad" data-id="' + n.idnovedad + '">Eliminar</button>' +
                '</td>';
            tb.appendChild(tr);
        });
    }

    function escapeHtml(s) {
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function destroyCharts() {
        if (chartHora) { chartHora.destroy(); chartHora = null; }
        if (chartTipo) { chartTipo.destroy(); chartTipo = null; }
    }

    function estaModoOscuro() {
        return document.documentElement.getAttribute('data-theme') === 'dark' ||
            document.documentElement.classList.contains('portal-dark-mode') ||
            document.body.classList.contains('portal-dark-mode');
    }

    function obtenerTemaGrafico() {
        var dark = estaModoOscuro();

        return {
            texto: dark ? '#f8fafc' : '#374151',
            textoSuave: dark ? '#e5e7eb' : '#6b7280',
            grilla: dark ? 'rgba(203, 213, 225, 0.18)' : 'rgba(17, 24, 39, 0.10)',
            borde: dark ? 'rgba(248, 250, 252, 0.28)' : 'rgba(17, 24, 39, 0.18)',
            fondoGrafico: dark ? '#172033' : '#ffffff'
        };
    }

    function renderCharts(chHora, chTipo) {
        destroyCharts();
        var ctx1 = document.getElementById('chartPorHora');
        var ctx2 = document.getElementById('chartPorTipo');
        if (!ctx1 || !ctx2 || typeof Chart === 'undefined') return;

        var tema = obtenerTemaGrafico();

        chartHora = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: (chHora && chHora.labels) ? chHora.labels : [],
                datasets: [{
                    label: 'Visitas',
                    data: (chHora && chHora.values) ? chHora.values : [],
                    backgroundColor: 'rgba(13, 110, 253, 0.55)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            color: tema.texto,
                            maxRotation: 45,
                            minRotation: 45,
                            font: { size: 9 }
                        },
                        grid: {
                            color: tema.grilla,
                            borderColor: tema.borde
                        },
                        border: {
                            color: tema.borde
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: tema.texto,
                            stepSize: 1,
                            precision: 0
                        },
                        grid: {
                            color: tema.grilla,
                            borderColor: tema.borde
                        },
                        border: {
                            color: tema.borde
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                        labels: { color: tema.texto }
                    },
                    tooltip: {
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff'
                    }
                }
            }
        });

        chartTipo = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: (chTipo && chTipo.labels) ? chTipo.labels : [],
                datasets: [{
                    data: (chTipo && chTipo.values) ? chTipo.values : [],
                    backgroundColor: ['#0d6efd', '#6c757d', '#198754', '#fd7e14', '#6f42c1'],
                    borderColor: tema.fondoGrafico,
                    hoverBorderColor: tema.fondoGrafico,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: tema.texto,
                            boxWidth: 18,
                            padding: 14,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff'
                    }
                }
            }
        });
    }

    function actualizarTemaGraficos() {
        if (chartHora) {
            var tema = obtenerTemaGrafico();
            if (chartHora.options.scales && chartHora.options.scales.x) {
                chartHora.options.scales.x.ticks.color = tema.texto;
                chartHora.options.scales.x.grid.color = tema.grilla;
                chartHora.options.scales.x.border.color = tema.borde;
            }
            if (chartHora.options.scales && chartHora.options.scales.y) {
                chartHora.options.scales.y.ticks.color = tema.texto;
                chartHora.options.scales.y.grid.color = tema.grilla;
                chartHora.options.scales.y.border.color = tema.borde;
            }
            chartHora.update('none');
        }

        if (chartTipo) {
            var temaTipo = obtenerTemaGrafico();
            if (chartTipo.options.plugins && chartTipo.options.plugins.legend && chartTipo.options.plugins.legend.labels) {
                chartTipo.options.plugins.legend.labels.color = temaTipo.texto;
            }
            if (chartTipo.data.datasets && chartTipo.data.datasets[0]) {
                chartTipo.data.datasets[0].borderColor = temaTipo.fondoGrafico;
                chartTipo.data.datasets[0].hoverBorderColor = temaTipo.fondoGrafico;
            }
            chartTipo.update('none');
        }
    }

    function aplicarDatos(data) {
        showErr('');
        if (data.reporte) renderEncabezado(data.reporte);
        if (data.resumen) renderResumen(data.resumen);
        if (data.novedades) renderNovedades(data.novedades);
        renderCharts(data.chart_por_hora, data.chart_por_tipo);
        if (data.contexto_datos && data.contexto_datos.modo === 'general' && data.contexto_datos.mensaje) {
            if (window.showToast) window.showToast(data.contexto_datos.mensaje, 'info');
        }
    }

    function aplicarDatosParciales(data) {
        if (data.novedades) renderNovedades(data.novedades);
        if (data.resumen) renderResumen(data.resumen);
        if (data.chart_por_hora || data.chart_por_tipo) {
            renderCharts(data.chart_por_hora, data.chart_por_tipo);
        }
        if (data.contexto_datos && data.contexto_datos.modo === 'general' && data.contexto_datos.mensaje) {
            if (window.showToast) window.showToast(data.contexto_datos.mensaje, 'info');
        }
    }

    function cargarDatos() {
        var fecha = elFecha ? elFecha.value : '';
        var usuario = getUsuario();
        if (!usuario && elUsuario) {
            if (window.showToast) window.showToast('Indique el usuario que genera el reporte.', 'error');
            return;
        }
        if (elUsuario && usuario) elUsuario.value = usuario;

        var url = 'apis/bit_reporte_supervisor_api.php?action=datos&fecha=' + encodeURIComponent(fecha) + '&usuario=' + encodeURIComponent(usuario);
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) {
                    showErr(data.message || 'Error al cargar el reporte.');
                    if (window.showToast) window.showToast(data.message || 'Error', 'error');
                    return;
                }
                saveUsuario(usuario);
                aplicarDatos(data);
            })
            .catch(function () {
                showErr('Error de conexión con el servidor.');
                if (window.showToast) window.showToast('Error de conexión.', 'error');
            });
    }

    document.getElementById('btnCargarReporte').addEventListener('click', cargarDatos);
    document.getElementById('btnGuardarSupervisor').addEventListener('click', function () {
        var fecha = elFecha ? elFecha.value : '';
        var usuario = getUsuario();
        if (!usuario) {
            if (window.showToast) window.showToast('Escriba el supervisor o responsable del reporte.', 'error');
            return;
        }
        var fd = new FormData();
        fd.append('action', 'reporte_actualizar_supervisor');
        fd.append('fecha', fecha);
        fd.append('usuario', usuario);
        fetch('apis/bit_reporte_supervisor_api.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) {
                    if (window.showToast) window.showToast(data.message || 'Error', 'error');
                    return;
                }
                saveUsuario(usuario);
                if (data.reporte) renderEncabezado(data.reporte);
                if (window.showToast) window.showToast(data.message || 'Actualizado.', 'success');
            })
            .catch(function () { if (window.showToast) window.showToast('Error de conexión.', 'error'); });
    });
    if (elFecha) elFecha.addEventListener('change', cargarDatos);

    document.getElementById('formNuevaNovedad').addEventListener('submit', function (e) {
        e.preventDefault();
        var fecha = elFecha ? elFecha.value : '';
        var usuario = getUsuario();
        var desc = document.getElementById('novaDescripcion').value.trim();
        var est = document.getElementById('novaEstado').value;
        if (!desc) {
            if (window.showToast) window.showToast('La descripción no puede estar vacía.', 'error');
            return;
        }
        var fd = new FormData();
        fd.append('action', 'novedad_crear');
        fd.append('fecha', fecha);
        fd.append('usuario', usuario);
        fd.append('descripcion', desc);
        fd.append('estado', est);
        var nh = document.getElementById('novaHora');
        if (nh && nh.value) fd.append('hora', nh.value);
        fetch('apis/bit_reporte_supervisor_api.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) {
                    if (window.showToast) window.showToast(data.message || 'Error', 'error');
                    return;
                }
                document.getElementById('novaDescripcion').value = '';
                if (nh) nh.value = horaLocalParaInputTime();
                aplicarDatosParciales(data);
                if (window.showToast) window.showToast(data.message || 'Guardado.', 'success');
            })
            .catch(function () { if (window.showToast) window.showToast('Error de conexión.', 'error'); });
    });

    document.getElementById('tbodyNovedades').addEventListener('click', function (e) {
        var btnE = e.target.closest('.btn-edit-novedad');
        var btnD = e.target.closest('.btn-del-novedad');
        if (btnE) {
            var id = btnE.getAttribute('data-id');
            var row = btnE.closest('tr');
            var cells = row ? row.cells : null;
            if (!cells || cells.length < 4) return;
            document.getElementById('editIdnovedad').value = id;
            var editHora = document.getElementById('editHora');
            if (editHora) editHora.value = horaApiAInput(cells[0].textContent.trim());
            document.getElementById('editDescripcion').value = cells[1].textContent.trim();
            var est = cells[2].textContent.trim();
            var fechaInfo = document.getElementById('editFechaInfo');
            if (fechaInfo && elFecha && elFecha.value) {
                fechaInfo.textContent = 'Fecha de la novedad: ' + fmtFechaDMY(elFecha.value) + ' (no se puede cambiar; solo la hora).';
            }
            var sel = document.getElementById('editEstado');
            if (sel) {
                sel.value = est;
                if (sel.value !== est) {
                    var o = document.createElement('option');
                    o.value = est;
                    o.textContent = est;
                    sel.appendChild(o);
                    sel.value = est;
                }
            }
            var m = new bootstrap.Modal(document.getElementById('modalEditarNovedad'));
            m.show();
        }
        if (btnD) {
            var idDel = btnD.getAttribute('data-id');
            if (!confirm('¿Eliminar esta novedad?')) return;
            var fd = new FormData();
            fd.append('action', 'novedad_eliminar');
            fd.append('idnovedad', idDel);
            fetch('apis/bit_reporte_supervisor_api.php', { method: 'POST', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok) {
                        if (window.showToast) window.showToast(data.message || 'Error', 'error');
                        return;
                    }
                    aplicarDatosParciales(data);
                    if (window.showToast) window.showToast(data.message || 'Eliminada.', 'success');
                })
                .catch(function () { if (window.showToast) window.showToast('Error de conexión.', 'error'); });
        }
    });

    document.getElementById('btnGuardarEdicionNovedad').addEventListener('click', function () {
        var id = document.getElementById('editIdnovedad').value;
        var desc = document.getElementById('editDescripcion').value.trim();
        var est = document.getElementById('editEstado').value;
        var horaEd = document.getElementById('editHora');
        var horaVal = horaEd && horaEd.value ? horaEd.value : '';
        if (!desc) {
            if (window.showToast) window.showToast('La descripción no puede estar vacía.', 'error');
            return;
        }
        if (!horaVal) {
            if (window.showToast) window.showToast('Indique la hora.', 'error');
            return;
        }
        var fd = new FormData();
        fd.append('action', 'novedad_actualizar');
        fd.append('idnovedad', id);
        fd.append('hora', horaVal);
        fd.append('descripcion', desc);
        fd.append('estado', est);
        fetch('apis/bit_reporte_supervisor_api.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) {
                    if (window.showToast) window.showToast(data.message || 'Error', 'error');
                    return;
                }
                aplicarDatosParciales(data);
                if (window.showToast) window.showToast(data.message || 'Actualizado.', 'success');
                var modalEl = document.getElementById('modalEditarNovedad');
                var inst = bootstrap.Modal.getInstance(modalEl);
                if (inst) inst.hide();
            })
            .catch(function () { if (window.showToast) window.showToast('Error de conexión.', 'error'); });
    });

    function initPage() {
        try {
            var u = localStorage.getItem(LS_USUARIO);
            if (u && elUsuario) elUsuario.value = u;
        } catch (e) {}
        if (elUsuario && !String(elUsuario.value || '').trim()) {
            elUsuario.value = 'Supervisor';
        }
        var nh = document.getElementById('novaHora');
        if (nh && !nh.value) nh.value = horaLocalParaInputTime();

        var btnTema = document.getElementById('themeToggle');
        if (btnTema) {
            btnTema.addEventListener('click', function () {
                setTimeout(actualizarTemaGraficos, 120);
            });
        }

        window.addEventListener('storage', function (e) {
            if (e.key === 'apm_theme_mode') {
                setTimeout(actualizarTemaGraficos, 120);
            }
        });

        cargarDatos();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPage);
    } else {
        initPage();
    }
})();
