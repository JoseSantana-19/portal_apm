(function () {
    'use strict';

    const cfg = JSON.parse(document.getElementById('if-list-config').textContent);
    const money = new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' });
    const q = (selector) => document.querySelector(selector);
    const keys = {
        token: 'ingresos_factura_sesion_token',
        shown: 'ingresos_factura_datos_mostrados',
        away: 'ingresos_factura_fuera_desde',
        table: 'ingresos_factura_estado_tabla'
    };
    const lifetimeMs = Math.max(60, Number(cfg.vigenciaSegundos || 600)) * 1000;
    let table;
    let dataLoaded = false;
    let loading = false;

    function escapeHtml(value) {
        const node = document.createElement('div');
        node.textContent = value == null ? '' : String(value);
        return node.innerHTML;
    }

    function iso(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }

    function readableDate(value) {
        const match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? `${match[3]}/${match[2]}/${match[1]}` : escapeHtml(value || '—');
    }

    function setDates(type) {
        const today = new Date(`${cfg.hoy}T12:00:00`);
        let from = '';
        let to = '';
        if (type === 'hoy') from = to = iso(today);
        if (type === 'mes') {
            from = iso(new Date(today.getFullYear(), today.getMonth(), 1));
            to = iso(today);
        }
        if (type === 'anio') {
            from = iso(new Date(today.getFullYear(), 0, 1));
            to = iso(today);
        }
        q('#if-fecha-desde').value = from;
        q('#if-fecha-hasta').value = to;
    }

    function resetSessionState() {
        if (sessionStorage.getItem(keys.token) === String(cfg.tokenSesion)) return;
        sessionStorage.setItem(keys.token, String(cfg.tokenSesion));
        sessionStorage.removeItem(keys.shown);
        sessionStorage.removeItem(keys.away);
        sessionStorage.removeItem(keys.table);
    }

    function awayLimitExceeded() {
        const leftAt = Number(sessionStorage.getItem(keys.away) || 0);
        return leftAt > 0 && Date.now() - leftAt >= lifetimeMs;
    }

    function registerExit() {
        if (sessionStorage.getItem(keys.shown) === '1') {
            sessionStorage.setItem(keys.away, String(Date.now()));
        }
    }

    function setLoadingState(active) {
        loading = active;
        q('#if-mostrar').disabled = active;
        q('#if-empty-show').disabled = active;
        q('#if-mostrar-icon').className = active ? 'fa-solid fa-rotate fa-spin' : (dataLoaded ? 'fa-solid fa-rotate' : 'fa-solid fa-table-list');
        q('#if-mostrar-label').textContent = active ? 'Consultando…' : (dataLoaded ? 'Actualizar datos' : 'Mostrar datos');
    }

    function showEmptyState(reason) {
        const expired = reason === 'ausencia';
        const failed = reason === 'error';
        q('#if-empty-icon').className = failed ? 'fa-solid fa-triangle-exclamation' : (expired ? 'fa-solid fa-clock-rotate-left' : 'fa-solid fa-database');
        q('#if-empty-title').textContent = failed ? 'No fue posible cargar la consulta' : (expired ? 'La consulta anterior fue liberada' : 'Consulta lista para cargar');
        q('#if-empty-message').textContent = failed
            ? 'Revise la conexión e intente mostrar los datos nuevamente.'
            : (expired
                ? 'La pantalla permaneció sin uso más tiempo del configurado. Vuelva a cargar los datos para trabajar con información actualizada.'
                : 'Para optimizar el sistema, las facturas no se consultan automáticamente.');
        q('#if-list-empty').hidden = false;
        q('#if-table-shell').hidden = true;
    }

    function hideData(reason) {
        dataLoaded = false;
        sessionStorage.removeItem(keys.shown);
        sessionStorage.removeItem(keys.away);
        if (table) table.clear();
        const body = q('#if-facturas tbody');
        if (body) body.innerHTML = '';
        showEmptyState(reason || 'inicial');
        q('#if-list-subtitle').textContent = reason === 'ausencia'
            ? 'La consulta se liberó después de permanecer sin uso.'
            : 'La consulta se carga únicamente cuando la necesita.';
        q('#if-query-state').classList.remove('active');
        q('#if-query-state').innerHTML = '<i class="fa-regular fa-circle"></i> Datos sin cargar';
        setLoadingState(false);
    }

    function loadData() {
        if (loading) return;
        dataLoaded = true;
        q('#if-list-empty').hidden = true;
        q('#if-table-shell').hidden = false;
        setLoadingState(true);
        table.ajax.reload(function () {
            sessionStorage.setItem(keys.shown, '1');
            sessionStorage.removeItem(keys.away);
            q('#if-list-subtitle').textContent = 'Consulta actualizada y disponible mientras permanezca trabajando en este apartado.';
            q('#if-query-state').classList.add('active');
            q('#if-query-state').innerHTML = '<i class="fa-solid fa-circle-check"></i> Datos visibles';
            setLoadingState(false);
        }, true);
    }

    resetSessionState();
    setDates('hoy');

    if (typeof $ !== 'undefined' && $.fn && $.fn.dataTable) {
        $.fn.dataTable.ext.errMode = 'none';
    }

    table = $('#if-facturas').DataTable({
        processing: true,
        serverSide: true,
        deferLoading: 0,
        searchDelay: 350,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        stateSave: true,
        stateSaveCallback: function (_settings, data) {
            sessionStorage.setItem(keys.table, JSON.stringify(data));
        },
        stateLoadCallback: function () {
            const saved = sessionStorage.getItem(keys.table);
            if (!saved) return null;
            try { return JSON.parse(saved); } catch (_error) { return null; }
        },
        ajax: {
            url: cfg.url,
            data: function (data) {
                data.fecha_desde = q('#if-fecha-desde').value;
                data.fecha_hasta = q('#if-fecha-hasta').value;
                data.estado = q('#if-estado').value;
            },
            error: function () {
                hideData('error');
            }
        },
        order: [[1, 'desc']],
        columns: [
            { data: 'numero_factura', render: (value) => `<strong class="if-invoice-number"><i class="fa-regular fa-file-lines"></i>${escapeHtml(value)}</strong>` },
            { data: 'fecha_factura', render: (value) => `<span class="if-date-cell">${readableDate(value)}</span>` },
            { data: 'proveedor_codigo', defaultContent: '—', render: (value) => `<span class="if-provider-code">${escapeHtml(value || '—')}</span>` },
            { data: 'descripcion', defaultContent: '—', render: (value) => `<span class="if-description-cell" title="${escapeHtml(value || '')}">${escapeHtml(value || '—')}</span>` },
            { data: 'total', className: 'dt-body-right', render: (value) => `<strong class="if-amount-cell">${money.format(Number(value || 0))}</strong>` },
            { data: 'estado', render: (value) => `<span class="if-status ${String(value).toLowerCase()}"><i class="fa-solid fa-circle"></i>${escapeHtml(value)}</span>` },
            { data: 'id_factura', orderable: false, searchable: false, className: 'dt-body-center', render: (id) => `<a class="if-open-record" href="index.php?route=ingresos&amp;action=facturaIngreso&amp;id=${Number(id)}" title="Abrir factura" aria-label="Abrir factura"><i class="fa-regular fa-eye"></i></a>` }
        ],
        dom: '<"if-dt-tools"lf>rt<"if-dt-footer"ip>',
        language: {
            search: 'Buscar factura, código o descripción:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ facturas',
            infoEmpty: 'Sin facturas cargadas',
            infoFiltered: '(filtrado de _MAX_)',
            zeroRecords: 'No se encontraron facturas con estos filtros',
            processing: 'Consultando facturas…',
            paginate: { previous: 'Anterior', next: 'Siguiente' }
        }
    });

    if (cfg.busquedaInicial) table.search(cfg.busquedaInicial);

    document.querySelectorAll('[data-periodo]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.querySelectorAll('[data-periodo]').forEach((item) => item.classList.remove('active'));
            button.classList.add('active');
            setDates(button.dataset.periodo);
            if (dataLoaded) loadData();
        });
    });

    ['#if-fecha-desde', '#if-fecha-hasta', '#if-estado'].forEach(function (selector) {
        q(selector).addEventListener('change', function () {
            if (dataLoaded) loadData();
        });
    });

    q('#if-mostrar').addEventListener('click', loadData);
    q('#if-empty-show').addEventListener('click', loadData);

    const savedQuery = sessionStorage.getItem(keys.shown) === '1';
    if (savedQuery && !awayLimitExceeded()) {
        loadData();
    } else {
        hideData(savedQuery ? 'ausencia' : 'inicial');
    }

    window.addEventListener('pagehide', registerExit);
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            registerExit();
        } else if (dataLoaded) {
            if (awayLimitExceeded()) {
                hideData('ausencia');
            } else {
                sessionStorage.removeItem(keys.away);
            }
        }
    });
})();
