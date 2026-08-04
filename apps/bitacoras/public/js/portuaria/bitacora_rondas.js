(function () {
    'use strict';

    /** Clave del turno/día mostrado (fecha_operativa|turno) para detectar cambios sin recargar la página */
    var brCtxTurnoClave = null;
    /** Último turno/fecha según el reloj del servidor (solo para vigilancia de cambio de turno real) */
    var brServidorCtxClave = null;
    /** Frases desde API (frecuencia / recientes) */
    var sugerenciasApi = [];
    /** Lista combinada: primero actividades del turno actual en tabla, luego API */
    var sugerenciasLista = [];
    var ultimasFilasPreview = [];
    var ultimasFilasBusqueda = [];
    var brEditandoId = null;
    /** Fecha calendario (Y-m-d) del campo hora_registro al editar (p. ej. cambio de día en turno Noche) */
    var brEditandoFechaHora = null;
    var brEsAdmin = false;
    var brOrdenPreview = 'ASC';
    var brOrdenBusqueda = 'DESC';
    var brStorageOrdenPreviewKey = 'apm_br_preview_order_default';
    var brGuardiaCedula = '';
    var brFechaOperativaHoy = '';
    var brFechaServidorYmd = '';
    var brFechaSeleccionada = '';
    var brDiasEdicionGuardia = 1;
    var brDiasEdicionActualUsuario = null;
    var brPuedeConfigurarDiasEdicion = false;
    var sugTimer = null;
    var verificarTurnoTimer = null;
    /** Mapa Mañana/Tarde/Noche → hora_inicio/hora_fin por defecto (desde API context). */
    var brTurnoHorasDefault = {};

    function fetchJson(url, init) {
        init = init || {};
        return fetch(url, init).then(function (res) {
            return res.text().then(function (text) {
                var trimmed = text.replace(/^\uFEFF/, '').trim();
                try {
                    return JSON.parse(trimmed);
                } catch (parseErr) {
                    throw new Error(
                        'Respuesta no JSON (HTTP ' + res.status + '). Revise la pestaña Red (F12) o el servidor.'
                    );
                }
            });
        });
    }

    function esc(s) {
        if (s == null || s === undefined) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function fmtFechaDMY(ymd) {
        if (!ymd || ymd.length < 10) return ymd || '—';
        var p = ymd.split('-');
        if (p.length !== 3) return ymd;
        return p[2] + '/' + p[1] + '/' + p[0];
    }

    function formatoYmdLocal(date) {
        if (!(date instanceof Date)) return '';
        var y = date.getFullYear();
        var m = String(date.getMonth() + 1).padStart(2, '0');
        var d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function claveTurno(data) {
        if (!data) return null;
        return String(data.fecha_operativa || '') + '|' + String(data.turno || '');
    }

    function setBrTurnoValue(turnoKey) {
        var sel = document.getElementById('brTurno');
        if (!sel) return;
        var k = String(turnoKey || '').trim();
        if (k === '') return;
        sel.value = k;
    }

    function horasDefectoTurnoHard(turnoKey) {
        var k = String(turnoKey || '').trim();
        var map = {
            Mañana: { hora_inicio: '07:00', hora_fin: '15:00' },
            Tarde: { hora_inicio: '15:00', hora_fin: '23:00' },
            Noche: { hora_inicio: '23:00', hora_fin: '07:00' }
        };
        return map[k] || map.Mañana;
    }

    /** Rellena inicio/fin según turno (valores por defecto del servidor o respaldo local). */
    function aplicarHorasVentanaDesdeTurno(turnoKey) {
        var hi = document.getElementById('brHoraInicio');
        var hf = document.getElementById('brHoraFin');
        if (!hi || !hf) return;
        var k = String(turnoKey || '').trim();
        var pack =
            brTurnoHorasDefault && brTurnoHorasDefault[k] ? brTurnoHorasDefault[k] : horasDefectoTurnoHard(k);
        var a = pack.hora_inicio ? String(pack.hora_inicio).trim() : '';
        var b = pack.hora_fin ? String(pack.hora_fin).trim() : '';
        hi.value = a.length >= 5 ? a.slice(0, 5) : '';
        hf.value = b.length >= 5 ? b.slice(0, 5) : '';
    }

    /** Sobrescribe con lo guardado en la cabecera del usuario para fecha/turno actual. */
    function aplicarMiCabeceraHorasDesdeListado(mh) {
        if (!mh) return;
        var hi = document.getElementById('brHoraInicio');
        var hf = document.getElementById('brHoraFin');
        if (hi && mh.hora_inicio) hi.value = String(mh.hora_inicio).trim().slice(0, 5);
        if (hf && mh.hora_fin) hf.value = String(mh.hora_fin).trim().slice(0, 5);
    }

    function aplicarRelojCabeceraDesdeContexto(data) {
        if (!data) return;
        var bf = document.getElementById('brFecha');
        var bh = document.getElementById('brHora');
        var fs = data.fecha_servidor;
        if (bf && fs) {
            bf.value = fs.length >= 10 ? fmtFechaDMY(fs) : fs;
        }
        if (bh && data.hora_servidor) {
            bh.value = data.hora_servidor;
        }
    }

    /** Une textos del turno visible + API sin duplicados (prioridad a lo ya listado). */
    function recombinaSugerencias() {
        var seen = {};
        var out = [];
        (ultimasFilasPreview || []).forEach(function (r) {
            var a = String((r && r.actividad) || '').trim();
            if (a.length > 0 && !seen[a]) {
                seen[a] = true;
                out.push(a);
            }
        });
        (sugerenciasApi || []).forEach(function (s) {
            var a = String(s || '').trim();
            if (a.length > 0 && !seen[a]) {
                seen[a] = true;
                out.push(a);
            }
        });
        sugerenciasLista = out;
    }

    function claseFilaPorAlerta(idAlerta) {
        var n = parseInt(idAlerta, 10);
        if (n === 3) return 'apm-ronda-critica';
        if (n === 2) return 'apm-ronda-medio';
        return 'apm-ronda-normal';
    }

    function nivelBadgeHtml(idAlerta, label) {
        var n = parseInt(idAlerta, 10);
        var cls = 'apm-nivel-badge-normal';
        if (n === 3) cls = 'apm-nivel-badge-critico';
        else if (n === 2) cls = 'apm-nivel-badge-medio';
        return '<span class="apm-nivel-badge ' + cls + '">' + esc(label || '') + '</span>';
    }

    function renderFilaTurno(r, fechaOp) {
        var tr = document.createElement('tr');
        var cls = claseFilaPorAlerta(r.id_alerta);
        if (cls) tr.className = cls;

        var fechaCell = r.hora_registro_fecha ? fmtFechaDMY(r.hora_registro_fecha) : '—';
        var badge = '';
        if (r.cambio_dia) {
            badge =
                ' <span class="badge bg-info text-dark apm-badge-cambio-dia" title="Registro después de medianoche (mismo turno Noche)">Cambio de día</span>';
        }

        var hora = r.hora_registro_hora || '—';
        var guardiaHtml = '';
        if (brEsAdmin) {
            guardiaHtml =
                '<td class="apm-cell-guardia">' +
                '<span class="apm-guardia-nombre">' +
                esc(r.guardia_nombres || '') +
                '</span>' +
                '<span class="text-muted apm-guardia-cedula">' +
                esc(r.guardia_cedula || '') +
                '</span></td>';
        }

        var btnEditarHtml = '';
        if (r.puede_editar) {
            btnEditarHtml =
                '<button type="button" class="btn btn-sm btn-outline-primary br-btn-editar" data-id="' +
                esc(r.id_detalle || '') +
                '" title="Editar registro">' +
                '<i class="bi bi-pencil"></i>' +
                '</button>';
        } else {
            btnEditarHtml = '<span class="text-muted small">—</span>';
        }

        tr.innerHTML =
            '<td>' +
            esc(fechaCell) +
            badge +
            '</td>' +
            '<td class="text-nowrap">' +
            esc(hora) +
            '</td>' +
            guardiaHtml +
            '<td>' +
            esc(r.actividad || '') +
            '</td>' +
            '<td>' +
            nivelBadgeHtml(r.id_alerta, r.alerta_desc || '') +
            '</td>' +
            '<td class="text-center">' +
            btnEditarHtml +
            '</td>';
        return tr;
    }

    function renderFilasTurno(filas, fechaOp) {
        var tbody = document.getElementById('tablaTurnoBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        if (!filas || filas.length === 0) {
            tbody.innerHTML =
                '<tr><td colspan="' + (brEsAdmin ? '6' : '5') + '" class="text-muted text-center py-3 small">Sin registros en este turno.</td></tr>';
            return;
        }
        var filasOrdenadas = ordenarFilasPreview(filas);
        filasOrdenadas.forEach(function (r) {
            tbody.appendChild(renderFilaTurno(r, fechaOp));
        });
        if (normalizarOrdenPreview(brOrdenPreview) === 'ASC') {
            scrollPreviewToBottom();
        }
    }

    function scrollPreviewToBottom() {
        var wrap = document.getElementById('brPreviewScroll');
        if (!wrap) return;
        requestAnimationFrame(function () {
            wrap.scrollTop = wrap.scrollHeight;
        });
    }

    function getStorageOrdenPreviewKey() {
        if (brGuardiaCedula && String(brGuardiaCedula).trim() !== '') {
            return 'apm_br_preview_order_' + String(brGuardiaCedula).trim();
        }
        return brStorageOrdenPreviewKey;
    }

    function normalizarOrdenPreview(value) {
        return String(value || '').toUpperCase() === 'DESC' ? 'DESC' : 'ASC';
    }

    function parseFilaTs(r) {
        var fecha = String((r && r.hora_registro_fecha) || '');
        var hora = String((r && r.hora_registro_hora) || '');
        if (fecha && hora) {
            var iso = fecha + 'T' + hora;
            var ms = Date.parse(iso);
            if (!isNaN(ms)) return ms;
        }
        return parseInt((r && r.id_detalle) || '0', 10) || 0;
    }

    function ordenarFilasPreview(filas) {
        var orden = normalizarOrdenPreview(brOrdenPreview);
        var out = (filas || []).slice();
        out.sort(function (a, b) {
            var ta = parseFilaTs(a);
            var tb = parseFilaTs(b);
            if (ta === tb) return 0;
            if (orden === 'ASC') return ta - tb;
            return tb - ta;
        });
        return out;
    }

    function aplicarOrdenPreviewVisual() {
        var icon = document.getElementById('brOrdenIcon');
        var txt = document.getElementById('brOrdenTexto');
        var opciones = document.querySelectorAll('.br-orden-opcion');
        var orden = normalizarOrdenPreview(brOrdenPreview);
        if (icon) {
            icon.className = orden === 'ASC' ? 'bi bi-sort-numeric-up' : 'bi bi-sort-numeric-down';
        }
        if (txt) {
            txt.textContent = orden === 'ASC' ? 'Más antiguo' : 'Más reciente';
        }
        if (opciones && opciones.length) {
            opciones.forEach(function (btn) {
                var isActive = normalizarOrdenPreview(btn.getAttribute('data-orden')) === orden;
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-current', isActive ? 'true' : 'false');
            });
        }
    }

    function cargarOrdenPreview() {
        var key = getStorageOrdenPreviewKey();
        try {
            var saved = localStorage.getItem(key);
            brOrdenPreview = normalizarOrdenPreview(saved);
        } catch (_err) {
            brOrdenPreview = 'ASC';
        }
        aplicarOrdenPreviewVisual();
    }

    function guardarOrdenPreview() {
        var key = getStorageOrdenPreviewKey();
        try {
            localStorage.setItem(key, normalizarOrdenPreview(brOrdenPreview));
        } catch (_err) {}
    }

    function setOrdenPreview(orden, renderNow) {
        var next = normalizarOrdenPreview(orden);
        if (brOrdenPreview !== next) {
            brOrdenPreview = next;
            guardarOrdenPreview();
        }
        aplicarOrdenPreviewVisual();
        if (renderNow) {
            renderFilasTurno(ultimasFilasPreview, null);
        }
    }

    function esFechaYmdValida(v) {
        return /^\d{4}-\d{2}-\d{2}$/.test(String(v || ''));
    }

    function fechaMinimaDesdeServidor(fechaServidorYmd, diasPermitidos) {
        if (!esFechaYmdValida(fechaServidorYmd)) return '';
        var p = fechaServidorYmd.split('-');
        var dt = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        dt.setDate(dt.getDate() - (parseInt(diasPermitidos, 10) || 0));
        return formatoYmdLocal(dt);
    }

    function aplicarPoliticaEdicionDesdeContexto(data) {
        if (!data) return;
        brPuedeConfigurarDiasEdicion = !!data.puede_configurar_dias_edicion;
        brDiasEdicionGuardia = parseInt(data.dias_edicion_guardia, 10) || 1;
        brDiasEdicionActualUsuario =
            data.dias_edicion_actual_usuario === null || data.dias_edicion_actual_usuario === undefined
                ? null
                : parseInt(data.dias_edicion_actual_usuario, 10);

        var wrapAdmin = document.getElementById('brAdminDiasEdicionWrap');
        var selAdmin = document.getElementById('brAdminDiasEdicion');
        if (wrapAdmin) wrapAdmin.classList.toggle('d-none', !brPuedeConfigurarDiasEdicion);
        if (selAdmin) selAdmin.value = String(brDiasEdicionGuardia);

        var fdReg = document.getElementById('brFechaRegistroEdicion');
        var help = document.getElementById('brHelpFechaRegistroEdicion');
        if (fdReg) {
            if (brDiasEdicionActualUsuario === null) {
                fdReg.removeAttribute('min');
            } else {
                var minYmd = fechaMinimaDesdeServidor(String(data.fecha_servidor || ''), brDiasEdicionActualUsuario);
                if (esFechaYmdValida(minYmd)) fdReg.setAttribute('min', minYmd);
            }
        }
        if (help) {
            if (brDiasEdicionActualUsuario === null) {
                help.textContent = 'Sin límite';
            } else {
                help.textContent = 'Hasta ' + brDiasEdicionActualUsuario + ' día(s)';
            }
        }
    }

    function actualizarTituloPreview() {
        var tit = document.getElementById('brPreviewTitulo');
        if (!tit) return;
        if (brFechaSeleccionada && brFechaOperativaHoy && brFechaSeleccionada !== brFechaOperativaHoy) {
            tit.textContent = 'Consulta de Turno — ' + fmtFechaDMY(brFechaSeleccionada);
        } else {
            tit.textContent = 'Previsualización — turno actual';
        }
    }

    function aplicarModoRegistroPorFecha() {
        var esHoyOperativo = !!(brFechaSeleccionada && brFechaOperativaHoy && brFechaSeleccionada === brFechaOperativaHoy);
        var enModoEdicion = brEditandoId !== null;
        var permitirEdicion = esHoyOperativo || enModoEdicion;
        var act = document.getElementById('brActividad');
        var al = document.getElementById('brAlerta');
        var hr = document.getElementById('brHoraRegistro');
        var hIni = document.getElementById('brHoraInicio');
        var hFin = document.getElementById('brHoraFin');
        var fdReg = document.getElementById('brFechaRegistroEdicion');
        var btnG = document.getElementById('brBtnGrabar');
        var btnCE = document.getElementById('brBtnCancelarEdicion');
        var aviso = document.getElementById('brAvisoFechaPasada');
        if (act) act.disabled = !permitirEdicion;
        if (al) al.disabled = !permitirEdicion;
        if (hr) hr.disabled = !permitirEdicion;
        if (fdReg) fdReg.disabled = !permitirEdicion;
        if (hIni) hIni.disabled = !permitirEdicion;
        if (hFin) hFin.disabled = !permitirEdicion;
        if (btnG) btnG.disabled = !permitirEdicion;
        if (btnCE) btnCE.disabled = false;
        if (aviso) aviso.classList.toggle('d-none', esHoyOperativo || enModoEdicion);
    }

    function padHoraParaInput(h) {
        if (h == null || h === undefined) return '';
        var p = String(h).trim().split(':');
        if (p.length < 2) return '';
        return [p[0].padStart(2, '0'), p[1].padStart(2, '0'), (p[2] || '00').padStart(2, '0')].join(':');
    }

    function setDefaultHoraRegistro() {
        var el = document.getElementById('brHoraRegistro');
        if (!el) return;
        var brH = document.getElementById('brHora');
        if (brH && brH.value) {
            var norm = padHoraParaInput(brH.value);
            if (norm) {
                el.value = norm;
                return;
            }
        }
        var n = new Date();
        el.value =
            String(n.getHours()).padStart(2, '0') +
            ':' +
            String(n.getMinutes()).padStart(2, '0') +
            ':' +
            String(n.getSeconds()).padStart(2, '0');
    }

    function armarUrlListadoTurno() {
        var base = 'bitacoras/ronda/api?action=list_turno';
        if (esFechaYmdValida(brFechaSeleccionada)) {
            base += '&fecha=' + encodeURIComponent(brFechaSeleccionada);
        }
        var ts = document.getElementById('brTurno');
        if (ts && String(ts.value || '').trim() !== '') {
            base += '&turno=' + encodeURIComponent(String(ts.value).trim());
        }
        return base;
    }

    function aplicarVistaPreviewPorRol() {
        var thGuardia = document.getElementById('brThGuardia');
        if (!thGuardia) return;
        if (brEsAdmin) thGuardia.classList.remove('d-none');
        else thGuardia.classList.add('d-none');
    }

    function renderBusqueda(filas) {
        var tbody = document.getElementById('tablaBusquedaBody');
        if (!tbody) return;
        tbody.innerHTML = '';
        var filasOrdenadas = ordenarFilasBusqueda(filas || []);
        if (!filasOrdenadas || filasOrdenadas.length === 0) {
            tbody.innerHTML =
                '<tr><td colspan="5" class="text-muted text-center py-2 small">Sin resultados.</td></tr>';
            return;
        }
        filasOrdenadas.forEach(function (r) {
            var tr = document.createElement('tr');
            var cls = claseFilaPorAlerta(r.id_alerta);
            if (cls) tr.className = cls;
            tr.innerHTML =
                '<td class="text-nowrap small">' +
                esc(r.hora_registro_txt || '—') +
                '</td>' +
                '<td class="apm-cell-guardia">' +
                '<span class="apm-guardia-nombre">' +
                esc(r.nombres || '') +
                '</span>' +
                '<span class="text-muted apm-guardia-cedula">' +
                esc(r.cedula || '') +
                '</span></td>' +
                '<td>' +
                esc(r.turno || '') +
                '</td>' +
                '<td>' +
                esc(r.actividad || '') +
                '</td>' +
                '<td>' +
                nivelBadgeHtml(r.id_alerta, r.alerta_desc || '') +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function normalizarOrdenBusqueda(value) {
        return String(value || '').toUpperCase() === 'ASC' ? 'ASC' : 'DESC';
    }

    function parseBusquedaTs(r) {
        var iso = String((r && r.hora_registro_iso) || '').trim();
        if (iso) {
            var tIso = Date.parse(iso);
            if (!isNaN(tIso)) return tIso;
        }
        var txt = String((r && r.hora_registro_txt) || '').trim();
        var m = txt.match(/^(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})(?::(\d{2}))?$/);
        if (m) {
            var d = new Date(
                parseInt(m[3], 10),
                parseInt(m[2], 10) - 1,
                parseInt(m[1], 10),
                parseInt(m[4], 10),
                parseInt(m[5], 10),
                parseInt(m[6] || '0', 10)
            );
            var t = d.getTime();
            if (!isNaN(t)) return t;
        }
        return parseInt((r && r.id_detalle) || '0', 10) || 0;
    }

    function ordenarFilasBusqueda(filas) {
        var orden = normalizarOrdenBusqueda(brOrdenBusqueda);
        var out = (filas || []).slice();
        out.sort(function (a, b) {
            var ta = parseBusquedaTs(a);
            var tb = parseBusquedaTs(b);
            if (ta === tb) return 0;
            if (orden === 'ASC') return ta - tb;
            return tb - ta;
        });
        return out;
    }

    function aplicarOrdenBusquedaVisual() {
        var icon = document.getElementById('brBusOrdenIcon');
        var txt = document.getElementById('brBusOrdenTexto');
        var opciones = document.querySelectorAll('.br-bus-orden-opcion');
        var orden = normalizarOrdenBusqueda(brOrdenBusqueda);
        if (icon) {
            icon.className = orden === 'ASC' ? 'bi bi-sort-numeric-up' : 'bi bi-sort-numeric-down';
        }
        if (txt) {
            txt.textContent = orden === 'ASC' ? 'Más antiguos' : 'Más recientes';
        }
        if (opciones && opciones.length) {
            opciones.forEach(function (btn) {
                var isActive = normalizarOrdenBusqueda(btn.getAttribute('data-orden')) === orden;
                btn.classList.toggle('active', isActive);
                btn.setAttribute('aria-current', isActive ? 'true' : 'false');
            });
        }
    }

    function setOrdenBusqueda(orden, renderNow) {
        brOrdenBusqueda = normalizarOrdenBusqueda(orden);
        aplicarOrdenBusquedaVisual();
        if (renderNow) {
            renderBusqueda(ultimasFilasBusqueda);
        }
    }

    function setupOrdenBusqueda() {
        var opciones = document.querySelectorAll('.br-bus-orden-opcion');
        if (!opciones || !opciones.length) {
            aplicarOrdenBusquedaVisual();
            return;
        }
        opciones.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var orden = btn.getAttribute('data-orden') || 'DESC';
                setOrdenBusqueda(orden, true);
            });
        });
        aplicarOrdenBusquedaVisual();
    }

    function getBusquedaFilasOrdenadas() {
        return ordenarFilasBusqueda(ultimasFilasBusqueda || []);
    }

    function getPreviewFilasOrdenadas() {
        return ordenarFilasPreview(ultimasFilasPreview || []);
    }

    function getBusquedaMeta() {
        var d1 = document.getElementById('brBusDesde');
        var d2 = document.getElementById('brBusHasta');
        var q = document.getElementById('brBusQ');
        var desde = d1 ? String(d1.value || '') : '';
        var hasta = d2 ? String(d2.value || '') : '';
        var guardia = q ? String(q.value || '').trim() : '';
        return {
            elaboradoPor: (document.getElementById('brUsuario') || {}).value || 'Usuario',
            fechaTurno: (desde ? fmtFechaDMY(desde) : '—') + (hasta && hasta !== desde ? ' a ' + fmtFechaDMY(hasta) : ''),
            turno: 'Histórico',
            guardia: guardia || 'Todos'
        };
    }

    function getPreviewMeta() {
        var sel = document.getElementById('brTurno');
        var turnoTxt = 'Turno actual';
        if (sel && sel.selectedIndex >= 0 && sel.options[sel.selectedIndex]) {
            turnoTxt = sel.options[sel.selectedIndex].textContent || sel.value || turnoTxt;
        }
        return {
            elaboradoPor: (document.getElementById('brUsuario') || {}).value || 'Usuario',
            fechaTurno: fmtFechaDMY(brFechaSeleccionada || brFechaOperativaHoy || ''),
            turno: turnoTxt,
            guardia: brEsAdmin ? 'Todos (vista admin)' : ((document.getElementById('brUsuario') || {}).value || 'Actual')
        };
    }

    function slugFecha(value) {
        return String(value || '').replace(/[^0-9]/g, '') || 'fecha';
    }

    function getExportFilename(prefijo, ext) {
        var fechaBase = brFechaSeleccionada || brFechaOperativaHoy || formatoYmdLocal(new Date());
        return prefijo + '_' + slugFecha(fechaBase) + '.' + ext;
    }

    function getCellLineStyle() {
        return {
            top: { style: 'thin', color: { rgb: 'FF555555' } },
            bottom: { style: 'thin', color: { rgb: 'FF555555' } },
            left: { style: 'thin', color: { rgb: 'FF555555' } },
            right: { style: 'thin', color: { rgb: 'FF555555' } }
        };
    }

    function applyExcelStyles(ws, headers, tableStartRow, rowCount) {
        var border = getCellLineStyle();
        for (var c = 0; c < headers.length; c++) {
            var headAddr = window.XLSX.utils.encode_cell({ r: tableStartRow, c: c });
            if (!ws[headAddr]) continue;
            ws[headAddr].s = {
                fill: { fgColor: { rgb: 'FF334155' } },
                font: { color: { rgb: 'FFFFFFFF' }, bold: true, sz: 11 },
                alignment: { horizontal: 'center', vertical: 'center' },
                border: border
            };
        }
        for (var r = tableStartRow + 1; r <= tableStartRow + rowCount; r++) {
            for (var c2 = 0; c2 < headers.length; c2++) {
                var addr = window.XLSX.utils.encode_cell({ r: r, c: c2 });
                if (!ws[addr]) continue;
                ws[addr].s = {
                    border: border,
                    alignment: { vertical: 'top', wrapText: true }
                };
            }
        }
    }

    function autoWidthExcel(ws, aoa) {
        var cols = [];
        (aoa || []).forEach(function (row) {
            (row || []).forEach(function (cell, idx) {
                var len = String(cell == null ? '' : cell).length;
                var target = idx === 3 ? Math.min(Math.max(len + 4, 18), 70) : Math.min(Math.max(len + 3, 12), 35);
                cols[idx] = Math.max(cols[idx] || 10, target);
            });
        });
        ws['!cols'] = cols.map(function (w) { return { wch: w }; });
    }

    function addExcelFirmas(ws, rowStart) {
        var lineRow = rowStart;
        var labelRow = rowStart + 1;
        window.XLSX.utils.sheet_add_aoa(ws, [['______________', '', '', '______________']], { origin: { r: lineRow, c: 1 } });
        window.XLSX.utils.sheet_add_aoa(ws, [['ENTREGUE CONFORME', '', '', 'RECIBÍ CONFORME']], { origin: { r: labelRow, c: 1 } });
        var lineStyle = { alignment: { horizontal: 'center' }, font: { bold: true, sz: 10 } };
        var labelStyle = { alignment: { horizontal: 'center' }, font: { bold: true, sz: 10 } };
        var leftLine = window.XLSX.utils.encode_cell({ r: lineRow, c: 1 });
        var rightLine = window.XLSX.utils.encode_cell({ r: lineRow, c: 4 });
        var leftLabel = window.XLSX.utils.encode_cell({ r: labelRow, c: 1 });
        var rightLabel = window.XLSX.utils.encode_cell({ r: labelRow, c: 4 });
        if (ws[leftLine]) ws[leftLine].s = lineStyle;
        if (ws[rightLine]) ws[rightLine].s = lineStyle;
        if (ws[leftLabel]) ws[leftLabel].s = labelStyle;
        if (ws[rightLabel]) ws[rightLabel].s = labelStyle;
    }

    function exportarExcelComun(filas, meta, filename) {
        if (!filas.length) {
            if (window.showToast) window.showToast('No hay resultados para exportar.', 'info');
            return;
        }
        if (!(window.XLSX && window.XLSX.utils)) {
            if (window.showToast) window.showToast('No se pudo cargar la librería Excel.', 'error');
            return;
        }
        var headers = ['Fecha', 'Hora', 'Guardia', 'Actividad', 'Nivel'];
        var aoaBase = [
            ['AUTORIDAD PORTUARIA DE MANTA'],
            ['BITÁCORA DE RONDAS'],
            [],
            ['Elaborado por:', meta.elaboradoPor || '—'],
            ['Fecha de Turno:', meta.fechaTurno || '—'],
            ['Turno:', meta.turno || '—'],
            []
        ];
        var tableStartRow = aoaBase.length;
        aoaBase.push(headers);
        filas.forEach(function (r) {
            aoaBase.push([r.fecha || '—', r.hora || '—', r.guardia || '—', r.actividad || '', r.nivel || '']);
        });
        var ws = window.XLSX.utils.aoa_to_sheet(aoaBase);

        // Merge visual para títulos institucionales.
        ws['!merges'] = [
            { s: { r: 0, c: 0 }, e: { r: 0, c: 4 } },
            { s: { r: 1, c: 0 }, e: { r: 1, c: 4 } }
        ];

        // Estilos de encabezado institucional.
        var t1 = window.XLSX.utils.encode_cell({ r: 0, c: 0 });
        var t2 = window.XLSX.utils.encode_cell({ r: 1, c: 0 });
        if (ws[t1]) {
            ws[t1].s = {
                font: { bold: true, sz: 16, color: { rgb: 'FF0F172A' } },
                alignment: { horizontal: 'center', vertical: 'center' }
            };
        }
        if (ws[t2]) {
            ws[t2].s = {
                font: { bold: true, sz: 12, color: { rgb: 'FF1E293B' } },
                alignment: { horizontal: 'center', vertical: 'center' }
            };
        }

        // Metadatos en bloque previo a tabla.
        [3, 4, 5].forEach(function (r) {
            var a = window.XLSX.utils.encode_cell({ r: r, c: 0 });
            var b = window.XLSX.utils.encode_cell({ r: r, c: 1 });
            if (ws[a]) ws[a].s = { font: { bold: true, sz: 10 } };
            if (ws[b]) ws[b].s = { font: { sz: 10 } };
        });

        applyExcelStyles(ws, headers, tableStartRow, filas.length);
        addExcelFirmas(ws, tableStartRow + filas.length + 4); // 3 filas en blanco + firmas
        autoWidthExcel(ws, aoaBase);
        ws['!freeze'] = { xSplit: 0, ySplit: tableStartRow + 1 };
        var wb = window.XLSX.utils.book_new();
        wb.Props = {
            Title: 'Bitácora de rondas',
            Author: meta.elaboradoPor || 'APM'
        };
        window.XLSX.utils.book_append_sheet(wb, ws, 'Bitacora');
        window.XLSX.writeFile(wb, filename);
    }

    function drawPdfHeader(doc, meta) {
        doc.setFontSize(16);
        doc.setFont(undefined, 'bold');
        doc.text('AUTORIDAD PORTUARIA DE MANTA', 14, 12);
        doc.setFontSize(12);
        doc.text('BITÁCORA DE RONDAS', 14, 18);
        doc.setDrawColor(51, 65, 85);
        doc.line(14, 21, 283, 21);
        doc.setFont(undefined, 'normal');
        doc.setFontSize(9);
        doc.text('Datos del Reporte', 14, 27);
        doc.text('Elaborado por: ' + (meta.elaboradoPor || '—'), 14, 32);
        doc.text('Fecha de Turno: ' + (meta.fechaTurno || '—'), 14, 37);
        doc.text('Turno: ' + (meta.turno || '—'), 14, 42);
    }

    function drawPdfFirmas(doc, yStart) {
        var pageHeight = doc.internal.pageSize.getHeight();
        var y = yStart + 14;
        if (y > pageHeight - 22) {
            doc.addPage();
            y = 20;
        }
        doc.setDrawColor(70, 70, 70);
        var leftX = 70;
        var rightX = 210;
        doc.line(leftX - 30, y, leftX + 30, y);
        doc.line(rightX - 30, y, rightX + 30, y);
        doc.setFontSize(9);
        doc.text('ENTREGUE CONFORME', leftX, y + 5, { align: 'center' });
        doc.text('RECIBÍ CONFORME', rightX, y + 5, { align: 'center' });
    }

    function exportarPdfComun(filas, meta, filename) {
        if (!filas.length) {
            if (window.showToast) window.showToast('No hay resultados para exportar.', 'info');
            return;
        }
        if (!(window.jspdf && window.jspdf.jsPDF)) {
            if (window.showToast) window.showToast('No se pudo cargar la librería PDF.', 'error');
            return;
        }
        try {
            var doc = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            if (typeof doc.autoTable !== 'function') {
                if (window.showToast) window.showToast('No se pudo cargar la librería PDF (autoTable).', 'error');
                return;
            }
            drawPdfHeader(doc, meta);
            var body = filas.map(function (r) {
                return [r.fecha || '—', r.hora || '—', r.guardia || '—', r.actividad || '', r.nivel || ''];
            });
            doc.autoTable({
                startY: 48,
                head: [['Fecha', 'Hora', 'Guardia', 'Actividad', 'Nivel']],
                body: body,
                theme: 'grid',
                styles: { fontSize: 8, cellPadding: 2, valign: 'top' },
                headStyles: { fillColor: [11, 94, 215], textColor: [255, 255, 255] },
                columnStyles: { 3: { cellWidth: 120 } }
            });
            var yAfter = doc.lastAutoTable ? doc.lastAutoTable.finalY : 55;
            drawPdfFirmas(doc, yAfter);
            doc.save(filename);
        } catch (err) {
            if (window.showToast) window.showToast('No se pudo generar el PDF.', 'error');
            if (window.console && console.error) console.error('Error exportarPdfComun:', err);
        }
    }

    function mapFilaPreview(r) {
        return {
            fecha: fmtFechaDMY(r.hora_registro_fecha || brFechaSeleccionada || brFechaOperativaHoy || ''),
            hora: String(r.hora_registro_hora || '—'),
            guardia: brEsAdmin
                ? (String(r.guardia_nombres || '') + (r.guardia_cedula ? ' (' + r.guardia_cedula + ')' : ''))
                : ((document.getElementById('brUsuario') || {}).value || ''),
            actividad: String(r.actividad || ''),
            nivel: String(r.alerta_desc || '')
        };
    }

    function mapFilaBusqueda(r) {
        var txt = String(r.hora_registro_txt || '');
        var m = txt.match(/^(\d{2}\/\d{2}\/\d{4})\s+(.+)$/);
        return {
            fecha: m ? m[1] : '—',
            hora: m ? m[2] : '—',
            guardia: String(r.nombres || '') + (r.cedula ? ' (' + r.cedula + ')' : ''),
            actividad: String(r.actividad || ''),
            nivel: String(r.alerta_desc || '')
        };
    }

    function setupExportesReporte() {
        var btnPrevPdf = document.getElementById('brBtnExportPreviewPdf');
        var btnPrevExcel = document.getElementById('brBtnExportPreviewExcel');
        var btnBusPdf = document.getElementById('brBtnExportBusquedaPdf');
        var btnBusExcel = document.getElementById('brBtnExportBusquedaExcel');

        if (btnPrevPdf) {
            btnPrevPdf.addEventListener('click', function () {
                var rows = getPreviewFilasOrdenadas().map(mapFilaPreview);
                exportarPdfComun(rows, getPreviewMeta(), getExportFilename('Bitacora_Rondas_TurnoActual', 'pdf'));
            });
        }
        if (btnPrevExcel) {
            btnPrevExcel.addEventListener('click', function () {
                var rows = getPreviewFilasOrdenadas().map(mapFilaPreview);
                exportarExcelComun(rows, getPreviewMeta(), getExportFilename('Bitacora_Rondas_TurnoActual', 'xlsx'));
            });
        }
        if (btnBusPdf) {
            btnBusPdf.addEventListener('click', function () {
                var rows = getBusquedaFilasOrdenadas().map(mapFilaBusqueda);
                exportarPdfComun(rows, getBusquedaMeta(), getExportFilename('Bitacora_Rondas_Busqueda', 'pdf'));
            });
        }
        if (btnBusExcel) {
            btnBusExcel.addEventListener('click', function () {
                var rows = getBusquedaFilasOrdenadas().map(mapFilaBusqueda);
                exportarExcelComun(rows, getBusquedaMeta(), getExportFilename('Bitacora_Rondas_Busqueda', 'xlsx'));
            });
        }
    }

    function aplicarCamposContexto(data) {
        document.getElementById('brUsuario').value = data.usuario.nombres + ' (' + data.usuario.cedula + ')';
        brGuardiaCedula = String((data && data.usuario && data.usuario.cedula) || '').trim();
        cargarOrdenPreview();
        brFechaOperativaHoy = String((data && data.fecha_operativa) || '');
        brFechaServidorYmd = esFechaYmdValida(String((data && data.fecha_servidor) || '')) ? String(data.fecha_servidor) : '';
        if (!esFechaYmdValida(brFechaSeleccionada)) {
            brFechaSeleccionada = brFechaOperativaHoy;
        }
        var fechaInput = document.getElementById('brFechaOp');
        if (fechaInput && esFechaYmdValida(brFechaSeleccionada)) {
            fechaInput.value = brFechaSeleccionada;
        }
        actualizarTituloPreview();
        aplicarModoRegistroPorFecha();
        aplicarRelojCabeceraDesdeContexto(data);
        aplicarPoliticaEdicionDesdeContexto(data);
        brTurnoHorasDefault = data.turno_horas_default || {};
        setBrTurnoValue(data.turno);
        aplicarHorasVentanaDesdeTurno(data.turno);

        var sel = document.getElementById('brAlerta');
        sel.innerHTML = '';
        (data.niveles_alerta || []).forEach(function (n) {
            var o = document.createElement('option');
            o.value = n.id_alerta;
            o.textContent = n.descripcion;
            sel.appendChild(o);
        });
        if (brEditandoId === null) {
            setDefaultHoraRegistro();
        }
    }

    function cargarContexto() {
        return fetchJson('bitacoras/ronda/api?action=context', { credentials: 'same-origin' }).then(
            function (data) {
                if (!data.ok) throw new Error(data.message || 'Error');
                aplicarCamposContexto(data);
                brEsAdmin = !!data.es_admin;
                aplicarVistaPreviewPorRol();
                brCtxTurnoClave = claveTurno(data);
                brServidorCtxClave = claveTurno(data);
                return data;
            }
        );
    }

    /**
     * Lista la previsualización del turno actual.
     * @param {boolean} [actualizarSugerencias=true] Si false (refresco manual), no mezcla filas con la API de sugerencias ni reabre el panel flotante.
     */
    function cargarListadoTurno(actualizarSugerencias) {
        if (actualizarSugerencias === undefined || actualizarSugerencias === null) {
            actualizarSugerencias = true;
        }
        return fetchJson(armarUrlListadoTurno(), { credentials: 'same-origin' }).then(
            function (data) {
                if (!data.ok) throw new Error(data.message || 'Error');
                brEsAdmin = !!data.es_admin;
                aplicarVistaPreviewPorRol();
                brCtxTurnoClave = claveTurno(data);
                if (esFechaYmdValida(data.fecha_operativa)) {
                    brFechaSeleccionada = data.fecha_operativa;
                }
                var fechaInput = document.getElementById('brFechaOp');
                if (fechaInput && esFechaYmdValida(brFechaSeleccionada)) {
                    fechaInput.value = brFechaSeleccionada;
                }
                actualizarTituloPreview();
                aplicarModoRegistroPorFecha();
                setBrTurnoValue(data.turno);
                aplicarMiCabeceraHorasDesdeListado(data.mi_cabecera_horas);
                ultimasFilasPreview = data.filas || [];
                renderFilasTurno(data.filas, data.fecha_operativa);
                if (actualizarSugerencias) {
                    recombinaSugerencias();
                    var ta = document.getElementById('brActividad');
                    var panel = document.getElementById('brActividadSug');
                    if (ta && panel) {
                        renderPanelSugerencias(ta, panel);
                    }
                } else {
                    var panelSolo = document.getElementById('brActividadSug');
                    if (panelSolo) panelSolo.classList.remove('apm-open');
                }
                return data;
            }
        );
    }

    /** Solo tabla de previsualización (sin tocar sugerencias de actividad ni API sugerencias_actividad). */
    function cargarPrevisualizacion() {
        return cargarListadoTurno(false);
    }

    function cargarSugerenciasActividad() {
        return fetchJson('bitacoras/ronda/api?action=sugerencias_actividad', {
            credentials: 'same-origin'
        })
            .then(function (data) {
                if (data && data.ok && Array.isArray(data.sugerencias)) {
                    sugerenciasApi = data.sugerencias;
                } else {
                    sugerenciasApi = [];
                }
                recombinaSugerencias();
                return sugerenciasLista;
            })
            .catch(function () {
                sugerenciasApi = [];
                recombinaSugerencias();
                return sugerenciasLista;
            });
    }

    function truncarLabel(s, max) {
        max = max || 100;
        if (!s || s.length <= max) return s;
        return s.slice(0, max - 1) + '…';
    }

    function insertarEnTextarea(ta, texto) {
        var start = typeof ta.selectionStart === 'number' ? ta.selectionStart : ta.value.length;
        var end = typeof ta.selectionEnd === 'number' ? ta.selectionEnd : ta.value.length;
        var v = ta.value;
        ta.value = v.slice(0, start) + texto + v.slice(end);
        var pos = start + texto.length;
        ta.focus();
        if (ta.setSelectionRange) {
            ta.setSelectionRange(pos, pos);
        }
    }

    function filtrarSugerencias(needle) {
        var n = (needle || '').trim().toLowerCase();
        var base = sugerenciasLista.slice();
        if (n.length < 1) {
            return base.slice(0, 10);
        }
        return base.filter(function (s) {
            return String(s).toLowerCase().indexOf(n) !== -1;
        }).slice(0, 10);
    }

    function renderPanelSugerencias(ta, panel) {
        var filtradas = filtrarSugerencias(ta.value);
        panel.innerHTML = '';
        if (filtradas.length === 0) {
            panel.classList.remove('apm-open');
            return;
        }
        filtradas.forEach(function (texto) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action text-start';
            item.setAttribute('title', texto);
            item.innerHTML =
                '<span class="d-block">' + esc(truncarLabel(texto, 120)) + '</span>' +
                (texto.length > 120 ? '<span class="text-muted">Texto completo al elegir</span>' : '');
            item.addEventListener('mousedown', function (ev) {
                ev.preventDefault();
            });
            item.addEventListener('click', function () {
                insertarEnTextarea(ta, texto);
                panel.classList.remove('apm-open');
                ta.focus();
            });
            panel.appendChild(item);
        });
        panel.classList.add('apm-open');
    }

    function setupAutocompletadoActividad() {
        var ta = document.getElementById('brActividad');
        var panel = document.getElementById('brActividadSug');
        if (!ta || !panel) return;

        function scheduleRefresh() {
            if (sugTimer) clearTimeout(sugTimer);
            sugTimer = setTimeout(function () {
                renderPanelSugerencias(ta, panel);
            }, 120);
        }

        ta.addEventListener('input', scheduleRefresh);
        ta.addEventListener('focus', function () {
            scheduleRefresh();
        });
        ta.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') {
                panel.classList.remove('apm-open');
            }
        });

        document.addEventListener('click', function (ev) {
            var wrap = document.getElementById('brActividadWrap');
            if (wrap && !wrap.contains(ev.target)) {
                panel.classList.remove('apm-open');
            }
        });
    }

    function verificarCambioTurnoDesdeServidor(mostrarToast) {
        return fetchJson('bitacoras/ronda/api?action=context', { credentials: 'same-origin' })
            .then(function (data) {
                if (!data.ok) return null;
                var nueva = claveTurno(data);
                if (brServidorCtxClave !== null && nueva !== brServidorCtxClave) {
                    brServidorCtxClave = nueva;
                    aplicarCamposContexto(data);
                    return cargarListadoTurno().then(function () {
                        if (mostrarToast && window.showToast) {
                            window.showToast('Turno o día operativo actualizado. Tabla alineada al turno actual.', 'info');
                        }
                    });
                }
                brServidorCtxClave = nueva;
                aplicarRelojCabeceraDesdeContexto(data);
                return null;
            })
            .catch(function () {
                return null;
            });
    }

    function iniciarVigilanciaTurno() {
        if (verificarTurnoTimer) clearInterval(verificarTurnoTimer);
        verificarTurnoTimer = setInterval(function () {
            verificarCambioTurnoDesdeServidor(true);
        }, 120000);

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                verificarCambioTurnoDesdeServidor(true);
            }
        });
    }

    function grabar() {
        var act = document.getElementById('brActividad');
        var al = document.getElementById('brAlerta');
        if (!act || !al) return;

        var fd = new FormData();
        fd.append('actividad', act.value.trim());
        fd.append('id_alerta', al.value);
        var hrIn = document.getElementById('brHoraRegistro');
        if (hrIn && hrIn.value) {
            fd.append('hora_registro', hrIn.value);
        }
        if (esFechaYmdValida(brFechaSeleccionada)) {
            fd.append('fecha_operativa', brFechaSeleccionada);
        } else if (esFechaYmdValida(brFechaOperativaHoy)) {
            fd.append('fecha_operativa', brFechaOperativaHoy);
        }
        var ts = document.getElementById('brTurno');
        if (ts && String(ts.value || '').trim() !== '') {
            fd.append('turno', String(ts.value).trim());
        }
        var hIni = document.getElementById('brHoraInicio');
        var hFin = document.getElementById('brHoraFin');
        if (hIni) {
            fd.append('hora_inicio', String(hIni.value || '').trim());
        }
        if (hFin) {
            fd.append('hora_fin', String(hFin.value || '').trim());
        }
        if (brEditandoId !== null) {
            fd.append('id_detalle', String(brEditandoId));
            var fdReg = document.getElementById('brFechaRegistroEdicion');
            var fechaRegistroFinal = '';
            if (fdReg && esFechaYmdValida(String(fdReg.value || '').trim())) {
                fechaRegistroFinal = String(fdReg.value).trim();
            } else if (brEditandoFechaHora && esFechaYmdValida(brEditandoFechaHora)) {
                fechaRegistroFinal = brEditandoFechaHora;
            }

            if (fechaRegistroFinal !== '') {
                if (esFechaYmdValida(brFechaServidorYmd) && fechaRegistroFinal > brFechaServidorYmd) {
                    if (window.showToast) window.showToast('La fecha del registro no puede ser posterior a la fecha actual del servidor.', 'error');
                    return;
                }
                if (brDiasEdicionActualUsuario !== null) {
                    var minPermitida = fechaMinimaDesdeServidor(brFechaServidorYmd, brDiasEdicionActualUsuario);
                    if (esFechaYmdValida(minPermitida) && fechaRegistroFinal < minPermitida) {
                        if (window.showToast) {
                            window.showToast(
                                'No tiene permisos para editar registros de más de ' + brDiasEdicionActualUsuario + ' días de antigüedad',
                                'error'
                            );
                        }
                        return;
                    }
                }
                fd.append('fecha_registro', fechaRegistroFinal);
            }
        }

        var btn = document.getElementById('brBtnGrabar');
        if (btn) btn.disabled = true;

        fetchJson('bitacoras/ronda/api', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (data) {
                if (!data.ok) {
                    if (window.showToast) window.showToast(data.message || 'Error', 'error');
                    else alert(data.message || 'Error');
                    return;
                }
                limpiarEdicion();
                act.value = '';
                if (window.showToast) window.showToast(data.message || 'Guardado', 'success');
                return cargarSugerenciasActividad().then(function () {
                    return cargarListadoTurno();
                });
            })
            .catch(function (e) {
                var msg = e && e.message ? e.message : 'Error de red o respuesta inválida.';
                if (window.showToast) window.showToast(msg, 'error');
                else alert(msg);
            })
            .then(function () {
                if (btn) btn.disabled = false;
            });
    }

    function setModoEdicion(idDetalle, actividad, idAlerta, horaRegistroHms, fechaHoraRegistroYmd) {
        brEditandoId = parseInt(idDetalle, 10);
        if (!(brEditandoId > 0)) {
            brEditandoId = null;
            brEditandoFechaHora = null;
            return;
        }
        brEditandoFechaHora = null;
        if (esFechaYmdValida(fechaHoraRegistroYmd)) {
            brEditandoFechaHora = String(fechaHoraRegistroYmd).trim();
        }
        var act = document.getElementById('brActividad');
        var al = document.getElementById('brAlerta');
        var hr = document.getElementById('brHoraRegistro');
        var wrapFd = document.getElementById('brWrapFechaRegistroEdicion');
        var fdReg = document.getElementById('brFechaRegistroEdicion');
        var btn = document.getElementById('brBtnGrabar');
        var btnCancel = document.getElementById('brBtnCancelarEdicion');
        if (wrapFd) wrapFd.classList.remove('d-none');
        if (fdReg) {
            if (brEditandoFechaHora) {
                fdReg.value = brEditandoFechaHora;
            } else {
                fdReg.value = '';
            }
        }
        if (act) {
            act.value = actividad || '';
            act.focus();
        }
        if (al) {
            al.value = String(idAlerta || '');
        }
        if (hr) {
            var norm = padHoraParaInput(horaRegistroHms);
            if (norm) {
                hr.value = norm;
            } else {
                setDefaultHoraRegistro();
            }
        }
        if (btn) {
            btn.innerHTML = '<i class="bi bi-pencil-square me-1"></i>Actualizar registro';
            btn.classList.remove('apm-btn-institucional');
            btn.classList.add('btn-warning');
        }
        if (btnCancel) btnCancel.classList.remove('d-none');
        aplicarModoRegistroPorFecha();
    }

    function limpiarEdicion() {
        brEditandoId = null;
        brEditandoFechaHora = null;
        var wrapFd = document.getElementById('brWrapFechaRegistroEdicion');
        var fdReg = document.getElementById('brFechaRegistroEdicion');
        if (wrapFd) wrapFd.classList.add('d-none');
        if (fdReg) fdReg.value = '';
        var btn = document.getElementById('brBtnGrabar');
        var btnCancel = document.getElementById('brBtnCancelarEdicion');
        setDefaultHoraRegistro();
        if (btn) {
            btn.innerHTML = '<i class="bi bi-save me-1"></i>Grabar';
            btn.classList.remove('btn-warning');
            btn.classList.add('apm-btn-institucional');
        }
        if (btnCancel) btnCancel.classList.add('d-none');
        aplicarModoRegistroPorFecha();
    }

    function setupAccionesTablaTurno() {
        var tb = document.getElementById('tablaTurnoBody');
        if (!tb) return;
        tb.addEventListener('click', function (ev) {
            var btn = ev.target.closest('.br-btn-editar');
            if (!btn) return;
            var id = parseInt(btn.getAttribute('data-id') || '0', 10);
            if (!(id > 0)) return;
            var fila = (ultimasFilasPreview || []).find(function (r) {
                return parseInt(r.id_detalle, 10) === id;
            });
            if (!fila) return;
            setModoEdicion(
                id,
                fila.actividad || '',
                fila.id_alerta || '',
                fila.hora_registro_hora || '',
                fila.hora_registro_fecha || brFechaSeleccionada || brFechaOperativaHoy || ''
            );
        });
    }

    function setupTogglePreview() {
        var btn = document.getElementById('brBtnTogglePreview');
        var txt = document.getElementById('brToggleTxt');
        var box = document.getElementById('brPreviewCollapse');
        if (!btn || !txt || !box) return;
        box.addEventListener('shown.bs.collapse', function () {
            btn.setAttribute('aria-expanded', 'true');
            txt.textContent = 'Ocultar';
        });
        box.addEventListener('hidden.bs.collapse', function () {
            btn.setAttribute('aria-expanded', 'false');
            txt.textContent = 'Mostrar';
        });
    }

    function setupOrdenPreview() {
        var opciones = document.querySelectorAll('.br-orden-opcion');
        if (!opciones || !opciones.length) {
            aplicarOrdenPreviewVisual();
            return;
        }
        opciones.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var orden = btn.getAttribute('data-orden') || 'DESC';
                setOrdenPreview(orden, true);
            });
        });
        aplicarOrdenPreviewVisual();
    }

    function setupFiltroFechaOperativa() {
        var input = document.getElementById('brFechaOp');
        var btnHoy = document.getElementById('brBtnFechaHoy');
        if (input) {
            input.addEventListener('change', function () {
                var v = String(input.value || '').trim();
                if (!esFechaYmdValida(v)) return;
                brFechaSeleccionada = v;
                actualizarTituloPreview();
                aplicarModoRegistroPorFecha();
                cargarPrevisualizacion().catch(function () {});
            });
        }
        if (btnHoy) {
            btnHoy.addEventListener('click', function () {
                if (!esFechaYmdValida(brFechaOperativaHoy)) {
                    brFechaOperativaHoy = formatoYmdLocal(new Date());
                }
                brFechaSeleccionada = brFechaOperativaHoy;
                if (input) input.value = brFechaSeleccionada;
                actualizarTituloPreview();
                aplicarModoRegistroPorFecha();
                cargarPrevisualizacion().catch(function () {});
            });
        }
    }

    function buscar() {
        var d1 = document.getElementById('brBusDesde');
        var d2 = document.getElementById('brBusHasta');
        var q = document.getElementById('brBusQ');
        if (!d1.value || !d2.value) {
            if (window.showToast) window.showToast('Indique rango de fechas.', 'info');
            return;
        }
        var url =
            'bitacoras/ronda/api?action=buscar&fecha_desde=' +
            encodeURIComponent(d1.value) +
            '&fecha_hasta=' +
            encodeURIComponent(d2.value) +
            '&q=' +
            encodeURIComponent(q ? q.value.trim() : '');

        fetchJson(url, { credentials: 'same-origin' })
            .then(function (data) {
                if (!data.ok) {
                    if (window.showToast) window.showToast(data.message || 'Error', 'error');
                    return;
                }
                ultimasFilasBusqueda = Array.isArray(data.filas) ? data.filas : [];
                renderBusqueda(ultimasFilasBusqueda);
            })
            .catch(function (e) {
                var msg = e && e.message ? e.message : 'Error de red o respuesta inválida.';
                if (window.showToast) window.showToast(msg, 'error');
            });
    }

    function guardarDiasEdicionGuardia() {
        if (!brPuedeConfigurarDiasEdicion) return;
        var sel = document.getElementById('brAdminDiasEdicion');
        var btn = document.getElementById('brBtnGuardarDiasEdicion');
        if (!sel) return;
        var dias = parseInt(String(sel.value || '').trim(), 10) || 1;
        var permitidos = [1, 3, 5, 7];
        if (permitidos.indexOf(dias) === -1) dias = 1;
        if (btn) btn.disabled = true;
        fetchJson('bitacoras/ronda/api?action=set_dias_edicion_guardia&dias=' + encodeURIComponent(String(dias)), {
            credentials: 'same-origin'
        })
            .then(function (data) {
                if (!data.ok) {
                    if (window.showToast) window.showToast(data.message || 'No se pudo guardar.', 'error');
                    return null;
                }
                if (window.showToast) window.showToast(data.message || 'Configuración actualizada.', 'success');
                return cargarContexto();
            })
            .catch(function (e) {
                var msg = e && e.message ? e.message : 'Error de red.';
                if (window.showToast) window.showToast(msg, 'error');
            })
            .then(function () {
                if (btn) btn.disabled = false;
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var hoy = new Date();
        var y = hoy.getFullYear();
        var m = String(hoy.getMonth() + 1).padStart(2, '0');
        var d = String(hoy.getDate()).padStart(2, '0');
        var iso = y + '-' + m + '-' + d;
        var bd = document.getElementById('brBusDesde');
        var bh = document.getElementById('brBusHasta');
        if (bd) bd.value = iso;
        if (bh) bh.value = iso;

        setupAutocompletadoActividad();
        setupAccionesTablaTurno();
        setupTogglePreview();
        setupOrdenPreview();
        setupOrdenBusqueda();
        setupExportesReporte();
        setupFiltroFechaOperativa();

        var selTurno = document.getElementById('brTurno');
        if (selTurno) {
            selTurno.addEventListener('change', function () {
                aplicarHorasVentanaDesdeTurno(selTurno.value);
                actualizarTituloPreview();
                cargarListadoTurno().catch(function () {});
            });
        }

        cargarContexto()
            .then(function () {
                return cargarSugerenciasActividad();
            })
            .then(function () {
                return cargarListadoTurno();
            })
            .then(function () {
                iniciarVigilanciaTurno();
            })
            .catch(function (e) {
                var hint = e && e.message ? e.message : 'No se pudo cargar. ¿Ejecutó la migración SQL?';
                var tb = document.getElementById('tablaTurnoBody');
                if (tb) {
                    tb.innerHTML =
                        '<tr><td colspan="' + (brEsAdmin ? '6' : '5') + '" class="text-danger text-center py-3 small">' + esc(hint) + '</td></tr>';
                }
            });

        var btnG = document.getElementById('brBtnGrabar');
        if (btnG) btnG.addEventListener('click', grabar);
        var btnCE = document.getElementById('brBtnCancelarEdicion');
        if (btnCE)
            btnCE.addEventListener('click', function () {
                limpiarEdicion();
                var act = document.getElementById('brActividad');
                if (act) {
                    act.value = '';
                    act.focus();
                }
            });

        var btnR = document.getElementById('brBtnRefrescar');
        if (btnR) {
            btnR.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                cargarPrevisualizacion().catch(function () {});
            });
        }

        var btnB = document.getElementById('brBtnBuscar');
        if (btnB) btnB.addEventListener('click', buscar);
        var btnDias = document.getElementById('brBtnGuardarDiasEdicion');
        if (btnDias) btnDias.addEventListener('click', guardarDiasEdicionGuardia);
    });
})();
