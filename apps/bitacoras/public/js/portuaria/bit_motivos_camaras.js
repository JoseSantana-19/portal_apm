/* js/bit_motivos_camaras.js
   Maestro de Motivos CCTV - PHP puro + Fetch API
*/
(function () {
    'use strict';

    const API_URL = 'bitacoras/camara/apiMotivos';
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
        const box = $('bitMotAlert');
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

    function nivelBadge(nivel) {
        const valor = String(nivel || 'Medio');
        if (valor === 'Crítico') {
            return '<span class="badge text-bg-danger">Crítico</span>';
        }
        if (valor === 'Medio') {
            return '<span class="badge text-bg-warning">Medio</span>';
        }
        return '<span class="badge text-bg-success">Normal</span>';
    }

    function estadoBadge(estado) {
        const activo = Number(estado) === 1;
        const cls = activo ? 'text-bg-success' : 'text-bg-secondary';
        const txt = activo ? 'Activo' : 'Inactivo';
        return `<span class="badge ${cls}">${txt}</span>`;
    }

    function observacionBadge(valor) {
        return Number(valor) === 1
            ? '<span class="badge text-bg-info">Sí</span>'
            : '<span class="badge text-bg-light">No</span>';
    }

    function renderTabla(items) {
        const tbody = $('tbodyBitMotivos');
        const badge = $('bitMotTotalBadge');

        if (!tbody) return;

        if (badge) {
            badge.textContent = `${items.length} registro(s)`;
        }

        if (!items.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No se encontraron motivos con los filtros aplicados.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = items.map((item) => {
            const activo = Number(item.estado) === 1;
            const accionEstado = activo
                ? `<button type="button" class="btn btn-sm btn-outline-danger" data-action="eliminar" data-id="${item.id_motivo_camara}" title="Desactivar"><i class="bi bi-trash"></i></button>`
                : `<button type="button" class="btn btn-sm btn-outline-success" data-action="activar" data-id="${item.id_motivo_camara}" title="Activar"><i class="bi bi-check-circle"></i></button>`;

            return `
                <tr>
                    <td>
                        <span class="badge text-bg-light apm-code-badge">${escapeHtml(item.codigo_motivo || '—')}</span>
                        ${item.sec_motivo ? `<div class="small text-muted">Sec: ${escapeHtml(item.sec_motivo)}</div>` : ''}
                    </td>
                    <td>${escapeHtml(item.descripcion || '—')}</td>
                    <td>${nivelBadge(item.nivel_sugerido)}</td>
                    <td>${observacionBadge(item.requiere_observacion)}</td>
                    <td>${estadoBadge(item.estado)}</td>
                    <td class="text-end apm-no-print">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary" data-action="editar" data-id="${item.id_motivo_camara}" title="Editar"><i class="bi bi-pencil-square"></i></button>
                            ${accionEstado}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function limpiarFormulario() {
        const form = $('formBitMotivo');
        if (form) form.reset();

        const campos = [
            'bit_id_motivo_camara',
            'bit_codigo_motivo',
            'bit_sec_motivo',
        ];

        campos.forEach((id) => {
            if ($(id)) $(id).value = '';
        });

        if ($('bit_nivel_sugerido')) {
            $('bit_nivel_sugerido').value = 'Medio';
        }

        if ($('bit_requiere_observacion')) {
            $('bit_requiere_observacion').value = '1';
        }

        const btn = $('btnBitMotGuardar');
        if (btn) {
            btn.innerHTML = '<i class="bi bi-save me-1"></i>Guardar';
        }
    }

    function cargarEnFormulario(item) {
        $('bit_id_motivo_camara').value = item.id_motivo_camara || '';
        $('bit_codigo_motivo').value = item.codigo_motivo || '';
        $('bit_sec_motivo').value = item.sec_motivo || '';
        $('bit_descripcion').value = item.descripcion || '';
        $('bit_nivel_sugerido').value = item.nivel_sugerido || 'Medio';
        $('bit_requiere_observacion').value = String(Number(item.requiere_observacion) === 1 ? 1 : 0);

        const btn = $('btnBitMotGuardar');
        if (btn) {
            btn.innerHTML = '<i class="bi bi-check2-square me-1"></i>Actualizar';
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async function listar() {
        const params = new URLSearchParams();
        params.set('action', 'listar');
        params.set('q', $('bitMotFiltroBuscar')?.value.trim() || '');
        params.set('nivel', $('bitMotFiltroNivel')?.value || '');
        params.set('estado', $('bitMotFiltroEstado')?.value || '1');

        const tbody = $('tbodyBitMotivos');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Cargando motivos...</td></tr>';
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

    async function obtener(id) {
        const params = new URLSearchParams();
        params.set('action', 'obtener');
        params.set('id', id);

        const payload = await requestJson(`${API_URL}?${params.toString()}`);
        return payload.data;
    }

    function validarFormulario() {
        const descripcion = $('bit_descripcion')?.value.trim() || '';

        if (!descripcion) {
            toast('Ingrese la descripción del motivo CCTV.', 'error');
            $('bit_descripcion')?.focus();
            return false;
        }

        return true;
    }

    async function guardar(event) {
        event.preventDefault();

        if (!validarFormulario()) {
            return;
        }

        const form = $('formBitMotivo');
        const fd = new FormData(form);
        fd.set('action', 'guardar');
        fd.set('id_motivo_camara', $('bit_id_motivo_camara')?.value || '');

        const btn = $('btnBitMotGuardar');
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
            ? '¿Desea activar nuevamente este motivo CCTV?'
            : '¿Desea desactivar este motivo CCTV?';

        if (!window.confirm(mensaje)) {
            return;
        }

        const fd = new FormData();
        fd.set('action', activar ? 'activar' : 'eliminar');
        fd.set('id_motivo_camara', id);

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

    function exportarExcel() {
        if (!registros.length) {
            toast('No existen datos para exportar.', 'error');
            return;
        }

        const encabezados = [
            'Código',
            'Secuencial',
            'Descripción',
            'Nivel sugerido',
            'Requiere observación',
            'Estado',
        ];

        let html = '<html><head><meta charset="utf-8"></head><body>';
        html += '<h2>Maestro de Motivos CCTV</h2>';
        html += '<table border="1"><thead><tr>';
        encabezados.forEach((h) => { html += `<th>${escapeHtml(h)}</th>`; });
        html += '</tr></thead><tbody>';
        registros.forEach((item) => {
            html += '<tr>';
            html += `<td>${escapeHtml(item.codigo_motivo || '')}</td>`;
            html += `<td>${escapeHtml(item.sec_motivo || '')}</td>`;
            html += `<td>${escapeHtml(item.descripcion || '')}</td>`;
            html += `<td>${escapeHtml(item.nivel_sugerido || '')}</td>`;
            html += `<td>${Number(item.requiere_observacion) === 1 ? 'Sí' : 'No'}</td>`;
            html += `<td>${Number(item.estado) === 1 ? 'Activo' : 'Inactivo'}</td>`;
            html += '</tr>';
        });
        html += '</tbody></table></body></html>';

        const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `maestro_motivos_cctv_${new Date().toISOString().slice(0, 10)}.xls`;
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
                <td>${escapeHtml(item.codigo_motivo || '')}</td>
                <td>${escapeHtml(item.sec_motivo || '')}</td>
                <td>${escapeHtml(item.descripcion || '')}</td>
                <td>${escapeHtml(item.nivel_sugerido || '')}</td>
                <td>${Number(item.requiere_observacion) === 1 ? 'Sí' : 'No'}</td>
                <td>${Number(item.estado) === 1 ? 'Activo' : 'Inactivo'}</td>
            </tr>
        `).join('');

        const win = window.open('', '_blank', 'width=1000,height=700');
        if (!win) {
            toast('El navegador bloqueó la ventana del reporte.', 'error');
            return;
        }

        win.document.write(`
            <!doctype html>
            <html lang="es">
            <head>
                <meta charset="utf-8">
                <title>Reporte Maestro de Motivos CCTV</title>
                <style>
                    body { font-family: Arial, sans-serif; color: #111827; margin: 24px; }
                    h1 { color: #0b5ed7; margin-bottom: 4px; }
                    .meta { font-size: 12px; color: #4b5563; margin-bottom: 16px; }
                    table { width: 100%; border-collapse: collapse; font-size: 12px; }
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
                <h1>Maestro de Motivos CCTV</h1>
                <div class="meta">Autoridad Portuaria de Manta · Generado: ${escapeHtml(fecha)} · Total: ${registros.length}</div>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Secuencial</th>
                            <th>Descripción</th>
                            <th>Nivel sugerido</th>
                            <th>Requiere observación</th>
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
        $('formBitMotivo')?.addEventListener('submit', guardar);
        $('btnBitMotLimpiar')?.addEventListener('click', limpiarFormulario);
        $('btnBitMotBuscar')?.addEventListener('click', listar);
        $('btnBitMotExcel')?.addEventListener('click', exportarExcel);
        $('btnBitMotReporte')?.addEventListener('click', abrirReporte);
        $('bitMotFiltroEstado')?.addEventListener('change', listar);
        $('bitMotFiltroNivel')?.addEventListener('change', listar);
        $('bitMotFiltroBuscar')?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                listar();
            }
        });

        $('tbodyBitMotivos')?.addEventListener('click', async (event) => {
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
