(function () {
    'use strict';

    var POLL_MS = 10000;
    var apiUrl = 'apis/bit_get_dashboard_live.php';
    var chartVisitas = null;
    var chartRondas = null;
    var previousMaxMovId = 0;

    function pad(n) {
        return n < 10 ? '0' + n : String(n);
    }

    /** Solo hora HH:mm (columna compacta). */
    function formatSoloHora(iso) {
        if (!iso) return '—';
        var d = new Date(iso);
        if (isNaN(d.getTime())) return '—';
        return pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function badgeClassTipo(r) {
        var t = (r.tipo_evento || '').toUpperCase();
        var desc = r.descripcion || '';
        if (t === 'INGRESO') {
            return 'bg-success';
        }
        if (t === 'SALIDA') {
            return 'bg-info text-dark';
        }
        if (t === 'RONDA') {
            if (/\(Cr[ií]tico\)/i.test(desc) || /Crítico/i.test(desc)) {
                return 'bg-danger';
            }
            if (/\(Medio\)/i.test(desc)) {
                return 'bg-warning text-dark';
            }
            if (/\(Normal\)/i.test(desc)) {
                return 'bg-secondary';
            }
            return 'bg-dark';
        }
        return 'bg-secondary';
    }

    function labelUsuario(r) {
        var u = r.usuario_nombre;
        if (u != null && String(u).trim() !== '') {
            return String(u).trim();
        }
        return 'Sistema';
    }

    function appendFromDashboard(url) {
        if (!url) {
            return url;
        }
        if (url.indexOf('bitacoras/visita/detalle') === -1 && url.indexOf('bit_consulta.php') === -1) {
            return url;
        }
        return url + (url.indexOf('?') !== -1 ? '&' : '?') + 'from=dashboard';
    }

    function modalOnlyUrl(url) {
        if (!url) return url;
        return url + (url.indexOf('?') !== -1 ? '&' : '?') + 'modal_only=1';
    }

    function esc(s) {
        var t = document.createElement('div');
        t.textContent = s == null ? '' : String(s);
        return t.innerHTML;
    }

    function maxMovId(rows) {
        var m = 0;
        rows.forEach(function (r) {
            var id = parseInt(r.id_movimiento, 10);
            if (!isNaN(id) && id > m) m = id;
        });
        return m;
    }

    function buildVerUrl(row) {
        var tipo = (row.tipo_evento || '').toUpperCase();
        var lt = row.link_tipo;
        var lid = row.link_id;
        if (lt === 'visita' && lid) {
            return 'bitacoras/visita/detalle?id=' + encodeURIComponent(String(lid));
        }
        if (lt === 'ronda' && lid) {
            return 'bit_consulta.php?id_detalle=' + encodeURIComponent(String(lid)) + '&action=view';
        }
        if ((tipo === 'INGRESO' || tipo === 'SALIDA') && lid) {
            return 'bitacoras/visita/detalle?id=' + encodeURIComponent(String(lid));
        }
        if (tipo === 'RONDA' && lid) {
            return 'bit_consulta.php?id_detalle=' + encodeURIComponent(String(lid)) + '&action=view';
        }
        if (tipo === 'INGRESO' || tipo === 'SALIDA') {
            return 'visitas';
        }
        if (tipo === 'RONDA') {
            return 'rondas';
        }
        return null;
    }

    function renderFeed(rows, highlightIds) {
        var tbody = document.getElementById('apmDashFeedBody');
        if (!tbody) return;
        highlightIds = highlightIds || {};
        tbody.innerHTML = '';
        if (!rows || rows.length === 0) {
            var trEmpty = document.createElement('tr');
            trEmpty.innerHTML =
                '<td colspan="6" class="text-center text-muted py-5 border-0">' +
                '<span class="d-inline-block opacity-75">Sin actividad reciente registrada hoy</span>' +
                '</td>';
            tbody.appendChild(trEmpty);
            return;
        }
        rows.forEach(function (r) {
            var tr = document.createElement('tr');
            var idNum = parseInt(r.id_movimiento, 10);
            if (highlightIds[idNum]) {
                tr.classList.add('apm-dash-row-new');
                setTimeout(function () {
                    tr.classList.remove('apm-dash-row-new');
                }, 2000);
            }
            var verUrl = appendFromDashboard(buildVerUrl(r));
            var btn = '<span class="text-muted small">—</span>';
            if (verUrl) {
                if (verUrl.indexOf('bitacoras/visita/detalle') !== -1 || verUrl.indexOf('bit_consulta.php') !== -1) {
                    btn = '<a class="btn btn-sm btn-outline-primary apm-dash-open-modal" data-modal-url="' + esc(modalOnlyUrl(verUrl)) + '" href="' + esc(verUrl) + '">Ver detalle</a>';
                } else {
                    btn = '<a class="btn btn-sm btn-outline-primary" href="' + esc(verUrl) + '">Ver detalle</a>';
                }
            }
            var badgeCls = badgeClassTipo(r);
            tr.innerHTML =
                '<td class="text-nowrap small">' +
                esc(formatSoloHora(r.fecha_hora)) +
                '</td>' +
                '<td><span class="badge ' +
                esc(badgeCls) +
                '">' +
                esc(r.tipo_evento) +
                '</span></td>' +
                '<td class="small">' +
                esc(labelUsuario(r)) +
                '</td>' +
                '<td class="small">' +
                esc(r.turno || '—') +
                '</td>' +
                '<td class="small">' +
                esc((r.descripcion || '').length > 120 ? (r.descripcion || '').slice(0, 117) + '…' : r.descripcion || '') +
                '</td>' +
                '<td class="text-end">' +
                btn +
                '</td>';
            tbody.appendChild(tr);
        });
    }

    function openDashboardModal(url) {
        var host = document.getElementById('apmDashboardModalHost');
        if (!host || !url) {
            window.location.href = url || 'bit_dashboard_jefe.php';
            return;
        }
        fetch(url, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function (html) {
                host.innerHTML = html;
                var modalEl = host.querySelector('.modal');
                if (!modalEl || !window.bootstrap || !bootstrap.Modal) {
                    window.location.href = url.replace('modal_only=1', '').replace(/[\?&]$/, '');
                    return;
                }
                var instance = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalEl.addEventListener('hidden.bs.modal', function () {
                    host.innerHTML = '';
                    var backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                    document.body.classList.remove('modal-open');
                }, { once: true });
                instance.show();
            })
            .catch(function () {
                window.location.href = url.replace('modal_only=1', '').replace(/[\?&]$/, '');
            });
    }

    function applyKpis(k) {
        var e1 = document.getElementById('kpiVisitasActivas');
        var e2 = document.getElementById('kpiRondasHoy');
        var e3 = document.getElementById('kpiAlertasCrit');
        if (e1) e1.textContent = String(k.visitas_activas != null ? k.visitas_activas : 0);
        if (e2) e2.textContent = String(k.rondas_hoy != null ? k.rondas_hoy : 0);
        if (e3) e3.textContent = String(k.alertas_criticas_24h != null ? k.alertas_criticas_24h : 0);
    }

    /**
     * Degradado vertical bajo la línea (relleno suave bajo la curva).
     * @param {Array<{pos:number,color:string}>} stops
     */
    function gradientFillUnderLine(context, stops) {
        var chart = context.chart;
        var c = chart.ctx;
        var area = chart.chartArea;
        if (!area || !stops || stops.length === 0) {
            return stops && stops[0] ? stops[0].color : 'transparent';
        }
        var g = c.createLinearGradient(0, area.top, 0, area.bottom);
        stops.forEach(function (s) {
            g.addColorStop(s.pos, s.color);
        });
        return g;
    }

    function gradientVisitas(ctx) {
        try {
            return gradientFillUnderLine(ctx, [
                { pos: 0, color: 'rgba(13, 110, 253, 0.42)' },
                { pos: 0.45, color: 'rgba(13, 110, 253, 0.12)' },
                { pos: 1, color: 'rgba(13, 110, 253, 0)' }
            ]);
        } catch (e) {
            return 'rgba(13, 110, 253, 0.15)';
        }
    }

    function gradientRondas(ctx) {
        try {
            return gradientFillUnderLine(ctx, [
                { pos: 0, color: 'rgba(25, 135, 84, 0.42)' },
                { pos: 0.45, color: 'rgba(25, 135, 84, 0.12)' },
                { pos: 1, color: 'rgba(25, 135, 84, 0)' }
            ]);
        } catch (e) {
            return 'rgba(25, 135, 84, 0.15)';
        }
    }

    function updateCharts(ch) {
        if (!ch || !window.Chart) return;
        var labels = ch.labels || [];
        if (chartVisitas) {
            chartVisitas.data.labels = labels;
            chartVisitas.data.datasets[0].data = ch.visitas || [];
            chartVisitas.update('none');
        }
        if (chartRondas) {
            chartRondas.data.labels = labels;
            chartRondas.data.datasets[0].data = ch.rondas || [];
            chartRondas.update('none');
        }
    }

    function initCharts(initial) {
        var ch = initial.charts || {};
        var labels = ch.labels || [];
        var common = {
            type: 'line',
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(17,24,39,0.92)',
                        titleColor: '#fff',
                        bodyColor: '#e5e7eb',
                        borderColor: 'rgba(255,255,255,0.15)',
                        borderWidth: 1,
                        padding: 10
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        };
        var cv = document.getElementById('chartVisitasSemana');
        var cr = document.getElementById('chartRondasSemana');
        if (cv && window.Chart) {
            chartVisitas = new Chart(cv, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Visitas',
                            data: ch.visitas || [],
                            borderColor: '#0d6efd',
                            borderWidth: 2,
                            backgroundColor: function (ctx) {
                                return gradientVisitas(ctx);
                            },
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#0d6efd'
                        }
                    ]
                },
                options: common.options
            });
        }
        if (cr && window.Chart) {
            chartRondas = new Chart(cr, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Rondas',
                            data: ch.rondas || [],
                            borderColor: '#198754',
                            borderWidth: 2,
                            backgroundColor: function (ctx) {
                                return gradientRondas(ctx);
                            },
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                            pointBackgroundColor: '#198754'
                        }
                    ]
                },
                options: common.options
            });
        }
    }

    function syncLabel() {
        var el = document.getElementById('apmDashLastSync');
        if (el) {
            var d = new Date();
            el.textContent = 'Actualizado ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
        }
    }

    function processPayload(data) {
        if (!data || !data.ok) return;
        applyKpis(data.kpis || {});
        updateCharts(data.charts || {});
        var rows = data.movimientos || [];
        var maxId = maxMovId(rows);
        var highlight = {};
        rows.forEach(function (r) {
            var idn = parseInt(r.id_movimiento, 10);
            if (!isNaN(idn) && idn > previousMaxMovId) {
                highlight[idn] = true;
            }
        });
        previousMaxMovId = maxId;
        renderFeed(rows, highlight);
        syncLabel();
    }

    function fetchLive() {
        fetch(apiUrl, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) {
                if (r.status === 403) {
                    window.location.href = 'dashboard?msg=acceso_denegado';
                    return null;
                }
                return r.json();
            })
            .then(function (data) {
                if (data) processPayload(data);
            })
            .catch(function () {
                var el = document.getElementById('apmDashLastSync');
                if (el) el.textContent = 'Error de red';
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var initial = window.APM_DASH_INITIAL || {};
        applyKpis(initial.kpis || {});
        initCharts(initial);
        updateCharts(initial.charts || {});
        var rows = initial.movimientos || [];
        previousMaxMovId = maxMovId(rows);
        renderFeed(rows, {});
        syncLabel();

        setInterval(fetchLive, POLL_MS);
    });

    document.addEventListener('click', function (ev) {
        var link = ev.target.closest('a.apm-dash-open-modal');
        if (!link) return;
        ev.preventDefault();
        openDashboardModal(link.getAttribute('data-modal-url'));
    });
})();
