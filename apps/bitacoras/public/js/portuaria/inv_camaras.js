/* js/inv_camaras.js
   Maestro de Cámaras CCTV - PHP puro + Fetch API
*/
(function () {
    'use strict';

    const API_URL = 'bitacoras/camara/apiInventario';
    let registros = [];

    function $(id) {
        return document.getElementById(id);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showAlert(message, type = 'success') {
        const box = $('invCamAlert');
        if (!box) return;

        box.className = `alert alert-${type} apm-no-print`;
        box.textContent = message;
        box.classList.remove('d-none');

        window.clearTimeout(showAlert.timer);
        showAlert.timer = window.setTimeout(() => {
            box.classList.add('d-none');
        }, 4500);
    }

    function toast(message, type = 'success') {
        if (typeof window.mostrarToast === 'function') {
            window.mostrarToast(message, type);
            return;
        }

        showAlert(message, type === 'error' ? 'danger' : type);
    }

    async function requestJson(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
        });

        let payload = null;

        try {
            payload = await response.json();
        } catch (error) {
            throw new Error('La respuesta del servidor no es JSON válido.');
        }

        if (!response.ok || !payload.ok) {
            throw new Error(payload.message || 'No se pudo completar la operación.');
        }

        return payload;
    }

    function estadoBadge(estado) {
        const activo = Number(estado) === 1;
        const cls = activo ? 'text-bg-success' : 'text-bg-secondary';
        const txt = activo ? 'Activo' : 'Inactivo';
        return `<span class="badge ${cls}">${txt}</span>`;
    }

    function codigoPrincipal(item) {
        return item.codigo_secuencial || item.codigo || item.cod_old || `ID ${item.id_camara}`;
    }

    function renderTabla(items) {
        const tbody = $('tbodyInvCamaras');
        const badge = $('invTotalBadge');

        if (!tbody) return;

        if (badge) {
            badge.textContent = `${items.length} registro(s)`;
        }

        if (!items.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No se encontraron cámaras con los filtros aplicados.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = items.map((item) => {
            const activo = Number(item.estado) === 1;
            const accionEstado = activo
                ? `<button type="button" class="btn btn-sm btn-outline-danger" data-action="eliminar" data-id="${item.id_camara}" title="Desactivar"><i class="bi bi-trash"></i></button>`
                : `<button type="button" class="btn btn-sm btn-outline-success" data-action="activar" data-id="${item.id_camara}" title="Activar"><i class="bi bi-check-circle"></i></button>`;

            return `
                <tr>
                    <td>
                        <span class="badge text-bg-light apm-code-badge">${escapeHtml(codigoPrincipal(item))}</span>
                        ${item.cod_old ? `<div class="small text-muted">Old: ${escapeHtml(item.cod_old)}</div>` : ''}
                    </td>
                    <td>${escapeHtml(item.ip || '—')}</td>
                    <td>${escapeHtml(item.ubicacion || '—')}</td>
                    <td>${escapeHtml(item.detalle || '—')}</td>
                    <td>${escapeHtml(item.tipo || '—')}</td>
                    <td>
                        <div>${escapeHtml(item.marca || '—')}</div>
                        <div class="small text-muted">${escapeHtml(item.modelo || '')}</div>
                    </td>
                    <td>${escapeHtml(item.grabador || '—')}</td>
                    <td>${estadoBadge(item.estado)}</td>
                    <td class="text-end apm-no-print">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary" data-action="editar" data-id="${item.id_camara}" title="Editar"><i class="bi bi-pencil-square"></i></button>
                            ${accionEstado}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function limpiarFormulario() {
        const form = $('formInvCamara');
        if (form) form.reset();

        const campos = [
            'inv_id_camara',
            'inv_codigo_secuencial',
            'inv_sec_camara',
        ];

        campos.forEach((id) => {
            if ($(id)) $(id).value = '';
        });

        const btn = $('btnInvGuardar');
        if (btn) {
            btn.innerHTML = '<i class="bi bi-save me-1"></i>Guardar';
        }
    }

    function asignarSelectFlexible(id, valor) {
        const select = $(id);
        const valorLimpio = String(valor || '').trim();

        if (!select) {
            return;
        }

        if (!valorLimpio) {
            select.value = '';
            return;
        }

        const opciones = Array.from(select.options);
        const opcionExacta = opciones.find((opcion) => opcion.value === valorLimpio);

        if (opcionExacta) {
            select.value = opcionExacta.value;
            return;
        }

        const opcionSimilar = opciones.find((opcion) =>
            opcion.value.toUpperCase() === valorLimpio.toUpperCase() ||
            opcion.textContent.trim().toUpperCase() === valorLimpio.toUpperCase()
        );

        if (opcionSimilar) {
            select.value = opcionSimilar.value;
            return;
        }

        const opcionNueva = document.createElement('option');
        opcionNueva.value = valorLimpio;
        opcionNueva.textContent = valorLimpio;
        opcionNueva.dataset.dynamic = 'true';
        select.appendChild(opcionNueva);
        select.value = valorLimpio;
    }

    function cargarEnFormulario(item) {
        $('inv_id_camara').value = item.id_camara || '';
        $('inv_codigo_secuencial').value = item.codigo_secuencial || '';
        $('inv_sec_camara').value = item.sec_camara || '';
        $('inv_cod_old').value = item.cod_old || '';
        $('inv_codigo').value = item.codigo || '';
        $('inv_ip').value = item.ip || '';
        $('inv_mac').value = item.mac || '';
        $('inv_tipo').value = item.tipo || '';
        $('inv_tecnologia').value = item.tecnologia || '';
        asignarSelectFlexible('inv_marca', item.marca || '');
        $('inv_modelo').value = item.modelo || '';
        $('inv_serie').value = item.serie || '';
        $('inv_ubicacion').value = item.ubicacion || '';
        asignarSelectFlexible('inv_grabador', item.grabador || '');
        $('inv_detalle').value = item.detalle || '';
        $('inv_caracteristica').value = item.caracteristica || '';

        const btn = $('btnInvGuardar');
        if (btn) {
            btn.innerHTML = '<i class="bi bi-check2-square me-1"></i>Actualizar';
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async function listar() {
        const params = new URLSearchParams();
        params.set('action', 'listar');
        params.set('q', $('invFiltroBuscar')?.value.trim() || '');
        params.set('estado', $('invFiltroEstado')?.value || '1');

        const tbody = $('tbodyInvCamaras');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">Cargando cámaras...</td></tr>';
        }

        try {
            const payload = await requestJson(`${API_URL}?${params.toString()}`);
            registros = payload.data || [];
            renderTabla(registros);
        } catch (error) {
            registros = [];
            renderTabla([]);
            toast(error.message, 'error');
        }
    }

    function debounce(fn, delay = 350) {
        let timer = null;

        return function (...args) {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => {
                fn.apply(this, args);
            }, delay);
        };
    }

    async function obtener(id) {
        const params = new URLSearchParams();
        params.set('action', 'obtener');
        params.set('id', id);

        const payload = await requestJson(`${API_URL}?${params.toString()}`);
        return payload.data;
    }

    function validarFormulario() {
        const ip = $('inv_ip')?.value.trim() || '';
        const codigo = $('inv_codigo')?.value.trim() || '';
        const codOld = $('inv_cod_old')?.value.trim() || '';
        const ubicacion = $('inv_ubicacion')?.value.trim() || '';
        const detalle = $('inv_detalle')?.value.trim() || '';

        if (!ip && !codigo && !codOld) {
            toast('Ingrese al menos IP, código actual o código antiguo.', 'error');
            return false;
        }

        if (!ubicacion) {
            toast('Ingrese la ubicación de la cámara.', 'error');
            $('inv_ubicacion')?.focus();
            return false;
        }

        if (!detalle) {
            toast('Ingrese el detalle o sitio de la cámara.', 'error');
            $('inv_detalle')?.focus();
            return false;
        }

        return true;
    }

    async function guardar(event) {
        event.preventDefault();

        if (!validarFormulario()) {
            return;
        }

        const form = $('formInvCamara');
        const fd = new FormData(form);
        fd.set('action', 'guardar');
        fd.set('id_camara', $('inv_id_camara')?.value || '');

        const btn = $('btnInvGuardar');
        const original = btn ? btn.innerHTML : '';

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
        }

        try {
            const payload = await requestJson(API_URL, {
                method: 'POST',
                body: fd,
            });

            toast(payload.message || 'Registro guardado correctamente.', 'success');
            limpiarFormulario();
            await listar();
        } catch (error) {
            toast(error.message, 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        }
    }

    async function cambiarEstado(id, activar = false) {
        const mensaje = activar
            ? '¿Desea activar nuevamente esta cámara?'
            : '¿Desea desactivar esta cámara del inventario?';

        if (!window.confirm(mensaje)) {
            return;
        }

        const fd = new FormData();
        fd.set('action', activar ? 'activar' : 'eliminar');
        fd.set('id_camara', id);

        try {
            const payload = await requestJson(API_URL, {
                method: 'POST',
                body: fd,
            });

            toast(payload.message || 'Estado actualizado correctamente.', 'success');
            await listar();
        } catch (error) {
            toast(error.message, 'error');
        }
    }

    function textoReporte(item) {
        return [
            item.codigo_secuencial || '',
            item.cod_old || '',
            item.codigo || '',
            item.ip || '',
            item.ubicacion || '',
            item.detalle || '',
            item.tipo || '',
            item.marca || '',
            item.modelo || '',
            item.tecnologia || '',
            item.grabador || '',
            Number(item.estado) === 1 ? 'Activo' : 'Inactivo',
        ];
    }

    function exportarExcel() {
        if (!registros.length) {
            toast('No existen datos para exportar.', 'error');
            return;
        }

        const encabezados = [
            'Código secuencial',
            'Código antiguo',
            'Código actual',
            'IP / Equipo',
            'Ubicación',
            'Detalle / Sitio',
            'Tipo',
            'Marca',
            'Modelo',
            'Tecnología',
            'Grabador',
            'Estado',
        ];

        const filas = registros.map((item) => textoReporte(item));
        let html = '<html><head><meta charset="utf-8"></head><body>';
        html += '<h2>Maestro de Cámaras CCTV</h2>';
        html += '<table border="1"><thead><tr>';
        encabezados.forEach((h) => { html += `<th>${escapeHtml(h)}</th>`; });
        html += '</tr></thead><tbody>';
        filas.forEach((fila) => {
            html += '<tr>';
            fila.forEach((v) => { html += `<td>${escapeHtml(v)}</td>`; });
            html += '</tr>';
        });
        html += '</tbody></table></body></html>';

        const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `maestro_camaras_cctv_${new Date().toISOString().slice(0, 10)}.xls`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    function abrirReporte() {
        if (!registros.length) {
            toast('No existen datos para generar el reporte.', 'error');
            return;
        }

        const fecha = new Date().toLocaleString('es-EC');
        const rows = registros.map((item) => `
            <tr>
                <td>${escapeHtml(item.codigo_secuencial || '')}</td>
                <td>${escapeHtml(item.cod_old || '')}</td>
                <td>${escapeHtml(item.codigo || '')}</td>
                <td>${escapeHtml(item.ip || '')}</td>
                <td>${escapeHtml(item.ubicacion || '')}</td>
                <td>${escapeHtml(item.detalle || '')}</td>
                <td>${escapeHtml(item.tipo || '')}</td>
                <td>${escapeHtml(item.marca || '')} ${escapeHtml(item.modelo || '')}</td>
                <td>${Number(item.estado) === 1 ? 'Activo' : 'Inactivo'}</td>
            </tr>
        `).join('');

        const win = window.open('', '_blank', 'width=1100,height=750');
        if (!win) {
            toast('El navegador bloqueó la ventana del reporte.', 'error');
            return;
        }

        win.document.write(`
            <!doctype html>
            <html lang="es">
            <head>
                <meta charset="utf-8">
                <title>Reporte Maestro de Cámaras CCTV</title>
                <style>
                    body { font-family: Arial, sans-serif; color: #111827; margin: 24px; }
                    h1 { color: #0b5ed7; margin-bottom: 4px; }
                    .meta { font-size: 12px; color: #4b5563; margin-bottom: 16px; }
                    table { width: 100%; border-collapse: collapse; font-size: 11px; }
                    th { background: #0b5ed7; color: #fff; text-align: left; padding: 7px; border: 1px solid #0b5ed7; }
                    td { padding: 6px; border: 1px solid #cbd5e1; }
                    tr:nth-child(even) td { background: #f8fafc; }
                    .firmas { margin-top: 48px; display: flex; justify-content: space-between; gap: 32px; }
                    .firma { width: 45%; text-align: center; border-top: 1px solid #111827; padding-top: 8px; font-size: 12px; }
                    @media print { button { display:none; } }
                </style>
            </head>
            <body>
                <button onclick="window.print()">Imprimir / Guardar PDF</button>
                <h1>Maestro de Cámaras CCTV</h1>
                <div class="meta">Autoridad Portuaria de Manta · Generado: ${escapeHtml(fecha)} · Total: ${registros.length}</div>
                <table>
                    <thead>
                        <tr>
                            <th>Cód. sec.</th>
                            <th>Cód. antiguo</th>
                            <th>Cód. actual</th>
                            <th>IP</th>
                            <th>Ubicación</th>
                            <th>Detalle / Sitio</th>
                            <th>Tipo</th>
                            <th>Marca / Modelo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
                <div class="firmas">
                    <div class="firma">Responsable CCTV</div>
                    <div class="firma">Supervisor / Revisión</div>
                </div>
            </body>
            </html>
        `);
        win.document.close();
    }

    function configurarEventos() {
        $('formInvCamara')?.addEventListener('submit', guardar);
        $('btnInvLimpiar')?.addEventListener('click', limpiarFormulario);
        $('btnInvBuscar')?.addEventListener('click', listar);
        $('btnInvExcel')?.addEventListener('click', exportarExcel);
        $('btnInvReporte')?.addEventListener('click', abrirReporte);
        $('invFiltroEstado')?.addEventListener('change', listar);

        const buscarAutomatico = debounce(() => {
            listar();
        }, 350);

        $('invFiltroBuscar')?.addEventListener('input', buscarAutomatico);

        $('invFiltroBuscar')?.addEventListener('search', () => {
            listar();
        });

        $('invFiltroBuscar')?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                listar();
            }
        });

        $('tbodyInvCamaras')?.addEventListener('click', async (event) => {
            const btn = event.target.closest('button[data-action]');
            if (!btn) return;

            const id = btn.dataset.id;
            const action = btn.dataset.action;

            if (action === 'editar') {
                try {
                    const item = await obtener(id);
                    cargarEnFormulario(item);
                } catch (error) {
                    toast(error.message, 'error');
                }
            }

            if (action === 'eliminar') {
                cambiarEstado(id, false);
            }

            if (action === 'activar') {
                cambiarEstado(id, true);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        configurarEventos();
        listar();
    });
})();
