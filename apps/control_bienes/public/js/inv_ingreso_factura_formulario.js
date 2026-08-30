(function () {
    'use strict';

    const cfg = JSON.parse(document.getElementById('if-form-config').textContent);
    const money = { format: value => window.InvMoney.formatAmount(value) };
    const priceMoney = { format: value => window.InvMoney.formatPrice(value) };
    const q = (selector) => document.querySelector(selector);
    const qa = (selector) => Array.from(document.querySelectorAll(selector));
    const esc = (value) => {
        const node = document.createElement('div');
        node.textContent = value == null ? '' : String(value);
        return node.innerHTML;
    };

    let editing = Boolean(cfg.esNueva && cfg.puedeModificar);
    let lineIndex = 0;
    let productTable = null;
    let draftProduct = null;
    let editingRow = null;
    let scannedData = null;
    let quickSearchTimer = null;
    let quickSearchController = null;
    let quickActiveInput = null;
    let providerIds = new Set(qa('#if-proveedor option[value]').map((option) => Number(option.value)));
    const invoiceDraftKey = `sysport:factura-ingreso:${Number(cfg.usuarioId || 0)}`;
    let restoringDraft = false;
    let restoredQuickDraft = null;
    let restoredEditorDraft = null;

    const currentUrl = new URL(window.location.href);
    if (currentUrl.searchParams.get('borrador_guardado') === '1') {
        try { localStorage.removeItem(invoiceDraftKey); } catch (_) {}
        currentUrl.searchParams.delete('borrador_guardado');
        window.history.replaceState({}, '', currentUrl);
    }

    function ivaByRate(rate) {
        return cfg.tiposIva.find((item) => Math.abs(Number(item.tasa) - Number(rate)) < 0.0001) || null;
    }

    function setEditing(value) {
        editing = Boolean(value && cfg.puedeModificar && !cfg.vistaPrevia);
        qa('.if-editable').forEach((field) => { field.disabled = !editing; });
        qa('#if-lineas tr').forEach(renderRow);
        q('#if-agregar-producto').disabled = !editing;
        q('#if-aplicar-iva').disabled = !editing;
        q('#if-guardar').disabled = !editing;
        q('#if-editar').disabled = editing || cfg.esNueva || cfg.estado !== 'REGISTRADA' || !cfg.puedeModificar;
        q('#if-limpiar').disabled = !editing;
        q('#if-document-page').classList.toggle('if-editing', editing);
        q('#if-quick-lines').hidden = !editing;
        if (editing) {
            ensureQuickRow();
            restoreTransientDraft();
        }
        else hideQuickResults();
        if (!editing) closeEditor();
    }

    async function refreshProviders() {
        if (!cfg.proveedoresUrl || !cfg.esNueva) return;
        try {
            const response = await fetch(cfg.proveedoresUrl, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;
            const payload = await response.json();
            const providers = Array.isArray(payload.proveedores) ? payload.proveedores : [];
            cfg.proveedores = providers;
            const current = Number(q('#if-proveedor').value || 0);
            const newProviders = providers.filter((provider) => !providerIds.has(Number(provider.id)));
            q('#if-proveedor').innerHTML = '<option value="">Escriba para buscar…</option>' + providers.map((provider) => {
                const code = provider.codigo || `PRV-${provider.id}`;
                const ruc = provider.ruc ? ` · ${esc(provider.ruc)}` : '';
                return `<option value="${Number(provider.id)}">${esc(code)} · ${esc(provider.nombre)}${ruc}</option>`;
            }).join('');
            providerIds = new Set(providers.map((provider) => Number(provider.id)));
            q('#if-proveedor').value = newProviders.length === 1 ? String(newProviders[0].id) : (current ? String(current) : '');
            q('#if-proveedor').dispatchEvent(new Event('change', { bubbles: true }));
            if (newProviders.length === 1) q('#if-proveedor').classList.add('if-field-updated');
        } catch (_) {
            // Se conserva la lista cargada si la actualización no está disponible.
        }
    }
    window.addEventListener('focus', async () => {
        await refreshProviders();
        if (productTable) productTable.ajax.reload(null, false);
        await refreshScannedMatches();
    });

    function updateRowDocumentData() {
        qa('#if-lineas tr, #if-quick-lines tr').forEach((row) => {
            row.querySelector('.if-row-order').textContent = q('#if-orden').value;
            row.querySelector('.if-row-invoice').textContent = q('#if-numero').value || 'Pendiente';
        });
    }
    q('#if-numero').addEventListener('input', updateRowDocumentData);

    function lineDataFrom(product, detail, index) {
        const applies = detail.grava_iva !== undefined ? Number(detail.grava_iva) : Number(product.aplica_iva);
        const rate = detail.iva_porcentaje !== undefined ? Number(detail.iva_porcentaje) : Number(cfg.tasaPredeterminada);
        const typeId = detail.iva_tipo_id || (ivaByRate(rate)?.id || '');
        return {
            index,
            itemId: Number(product.id),
            code: product.codigo || detail.item_codigo || '',
            product: product.nombre || detail.item_nombre || '',
            stock: Number(product.existencia ?? detail.existencia ?? 0),
            accountCode: product.codigo_contable || detail.codigo_contable || '',
            accountName: product.cuenta_contable || detail.cuenta_contable || '',
            pedido: detail.pedido || '',
            requisicion: detail.requisicion || '',
            quantity: Number(detail.cantidad || 1),
            price: Number(detail.precio_unitario !== undefined ? detail.precio_unitario : product.precio_actual || 0),
            applies: applies ? 1 : 0,
            typeId: applies ? Number(typeId || 0) : 0,
            rate: applies ? Number(rate || 0) : 0,
            reference: detail.referencia || ''
        };
    }

    function renderRow(row) {
        const data = row._lineData;
        const subtotal = window.InvMoney.roundAmount(data.quantity * data.price);
        const tax = window.InvMoney.roundAmount(subtotal * data.rate / 100);
        const total = window.InvMoney.roundAmount(subtotal + tax);
        data.subtotal = subtotal;
        data.tax = tax;
        data.total = total;
        const rateLabel = data.applies ? `${data.rate.toLocaleString('es-EC')}%` : 'No aplica';
        const taxOptions = '<option value="">No aplica</option>' + cfg.tiposIva.map((type) => `<option value="${Number(type.id)}" data-rate="${Number(type.tasa)}" ${data.applies && Number(data.typeId) === Number(type.id) ? 'selected' : ''}>${esc(type.tasa)}%</option>`).join('');
        const pedidoCell = editing ? `<input class="if-inline-pedido" value="${esc(data.pedido)}" maxlength="100" title="Pedido">` : esc(data.pedido || '—');
        const requisitionCell = editing ? `<input class="if-inline-requisition" value="${esc(data.requisicion)}" maxlength="100" title="Requisición">` : esc(data.requisicion || '—');
        const quantityCell = editing ? `<input class="if-inline-quantity if-inline-number" type="number" min="1" step="1" value="${data.quantity}" title="Cantidad">` : data.quantity.toLocaleString('es-EC');
        const taxCell = editing ? `<select class="if-inline-tax" title="Tipo de IVA">${taxOptions}</select>` : `<span class="if-line-tax ${data.applies ? 'active' : ''}">${rateLabel}</span>`;
        const referenceCell = editing ? `<input class="if-inline-reference" value="${esc(data.reference)}" maxlength="255" title="Referencia">` : esc(data.reference || '—');
        row.dataset.itemId = String(data.itemId);
        row.innerHTML = `<td class="if-fixed-cell if-row-order" title="${esc(q('#if-orden').value)}">${esc(q('#if-orden').value)}</td>
            <td class="if-fixed-cell if-row-invoice" title="${esc(q('#if-numero').value || 'Pendiente')}">${esc(q('#if-numero').value || 'Pendiente')}</td>
            <td>${pedidoCell}</td><td>${requisitionCell}</td>
            <td class="if-item-code" title="${esc(data.code)}"><strong>${esc(data.code)}</strong></td><td class="if-item-description" title="${esc(data.product)}">${esc(data.product)}</td>
            <td class="if-number">${quantityCell}</td><td class="if-money" title="${priceMoney.format(data.price)}">${priceMoney.format(data.price)}</td>
            <td class="if-money if-inline-subtotal">${money.format(subtotal)}</td><td>${taxCell}</td>
            <td class="if-money if-inline-tax-value">${money.format(tax)}</td><td class="if-money if-inline-total"><strong>${money.format(total)}</strong></td>
            <td title="${esc(data.reference || '—')}">${referenceCell}</td><td><div class="if-row-actions"><button class="if-row-edit" type="button" title="Buscar otro producto"><i class="fa-solid fa-magnifying-glass"></i></button><button class="if-row-remove" type="button" title="Quitar producto"><i class="fa-solid fa-trash"></i></button></div></td>
            <td class="if-line-hidden"><input class="if-hidden-item" type="hidden" name="items[${data.index}][item_id]" value="${data.itemId}"><input class="if-hidden-pedido" type="hidden" name="items[${data.index}][pedido]" value="${esc(data.pedido)}"><input class="if-hidden-requisition" type="hidden" name="items[${data.index}][requisicion]" value="${esc(data.requisicion)}"><input class="if-hidden-quantity" type="hidden" name="items[${data.index}][cantidad]" value="${data.quantity}"><input type="hidden" name="items[${data.index}][precio_unitario]" value="${window.InvMoney.roundPrice(data.price).toFixed(window.InvMoney.config.priceDecimals)}"><input class="if-hidden-applies" type="hidden" name="items[${data.index}][aplica_iva]" value="${data.applies}"><input class="if-hidden-tax" type="hidden" name="items[${data.index}][iva_tipo_id]" value="${data.typeId || ''}"><input class="if-hidden-reference" type="hidden" name="items[${data.index}][referencia]" value="${esc(data.reference)}"></td>`;
        row.querySelector('.if-row-edit').hidden = !editing;
        row.querySelector('.if-row-remove').hidden = !editing;
        if (row.classList.contains('selected')) showProductInfo(row);
    }

    function syncInlineRow(row) {
        if (!row?._lineData || !editing) return;
        const data = row._lineData;
        data.pedido = row.querySelector('.if-inline-pedido')?.value.trim() || '';
        data.requisicion = row.querySelector('.if-inline-requisition')?.value.trim() || '';
        data.quantity = Math.max(0, Number(row.querySelector('.if-inline-quantity')?.value || 0));
        data.reference = row.querySelector('.if-inline-reference')?.value.trim() || '';
        const taxSelect = row.querySelector('.if-inline-tax');
        data.typeId = Number(taxSelect?.value || 0);
        data.applies = data.typeId > 0 ? 1 : 0;
        data.rate = data.applies ? Number(taxSelect.selectedOptions[0]?.dataset.rate || 0) : 0;
        data.subtotal = window.InvMoney.roundAmount(data.quantity * data.price);
        data.tax = window.InvMoney.roundAmount(data.subtotal * data.rate / 100);
        data.total = window.InvMoney.roundAmount(data.subtotal + data.tax);
        row.querySelector('.if-inline-subtotal').textContent = money.format(data.subtotal);
        row.querySelector('.if-inline-tax-value').textContent = money.format(data.tax);
        row.querySelector('.if-inline-total strong').textContent = money.format(data.total);
        row.querySelector('.if-hidden-pedido').value = data.pedido;
        row.querySelector('.if-hidden-requisition').value = data.requisicion;
        row.querySelector('.if-hidden-quantity').value = data.quantity;
        row.querySelector('.if-hidden-applies').value = data.applies;
        row.querySelector('.if-hidden-tax').value = data.typeId || '';
        row.querySelector('.if-hidden-reference').value = data.reference;
        summary();
        if (row.classList.contains('selected')) showProductInfo(row);
    }

    function clearProductInfo() {
        q('#if-info-product').textContent = 'Seleccione una línea del detalle.';
        q('#if-info-stock').textContent = '—';
        q('#if-info-price').textContent = '—';
        q('#if-info-account-body').innerHTML = '<tr class="if-account-empty"><td colspan="3">Seleccione un producto para consultar su aplicación contable.</td></tr>';
    }

    function showProductInfo(row) {
        qa('#if-lineas tr').forEach((item) => item.classList.toggle('selected', item === row));
        if (!row || !row._lineData) {
            clearProductInfo();
            return;
        }
        const data = row._lineData;
        q('#if-info-product').textContent = `${data.code || 'Sin código'} · ${data.product || 'Producto'}`;
        q('#if-info-stock').textContent = Number(data.stock || 0).toLocaleString('es-EC');
        q('#if-info-price').textContent = priceMoney.format(Number(data.price || 0));
        q('#if-info-account-body').innerHTML = `<tr><td><strong>${esc(data.accountCode || '—')}</strong></td><td><span class="if-account-product">${esc(data.accountName || 'Sin cuenta contable asociada')}</span><small>${esc(data.product || '')}</small></td><td class="if-money"><strong>${money.format(Number(data.total || 0))}</strong></td></tr>`;
    }

    function addOrUpdateLine(product, detail = {}, targetRow = null) {
        const duplicate = qa('#if-lineas tr').find((row) => row !== targetRow && Number(row.dataset.itemId) === Number(product.id));
        if (duplicate) {
            window.alert('Este producto ya está agregado a la factura.');
            return false;
        }
        const row = targetRow || document.createElement('tr');
        const index = targetRow ? targetRow._lineData.index : lineIndex++;
        row._lineData = lineDataFrom(product, detail, index);
        renderRow(row);
        if (!targetRow) q('#if-lineas').appendChild(row);
        showProductInfo(row);
        summary();
        return true;
    }

    function ensureQuickRow() {
        const body = q('#if-quick-lines');
        if (!body || !editing || body.children.length) return;
        const defaultRate = Number(q('#if-iva-default').value || cfg.tasaPredeterminada || 0);
        const row = document.createElement('tr');
        row.className = 'if-quick-row';
        row.innerHTML = `<td class="if-fixed-cell if-row-order" title="${esc(q('#if-orden').value)}">${esc(q('#if-orden').value)}</td>
            <td class="if-fixed-cell if-row-invoice" title="${esc(q('#if-numero').value || 'Pendiente')}">${esc(q('#if-numero').value || 'Pendiente')}</td>
            <td><input class="if-quick-pedido" maxlength="100" placeholder="Opcional" title="Pedido"></td>
            <td><div class="if-quick-requisition-control"><input class="if-quick-requisition" maxlength="100" placeholder="N.º" title="Requisición"><button class="if-quick-load-requisition" type="button" title="Cargar todos los productos de la requisición" aria-label="Cargar requisición"><i class="fa-solid fa-magnifying-glass"></i></button></div></td>
            <td><input class="if-quick-item" autocomplete="off" placeholder="Código o nombre" aria-label="Buscar ítem"></td>
            <td class="if-quick-description" title="Escriba al menos dos caracteres y seleccione una coincidencia">Escriba el ítem…</td>
            <td><input class="if-quick-quantity if-inline-number" type="number" min="1" step="1" value="1" title="Cantidad"></td>
            <td class="if-money">Auto</td><td class="if-money">$0,00</td><td><span class="if-line-tax">${defaultRate > 0 ? `${defaultRate}%` : 'No aplica'}</span></td><td class="if-money">$0,00</td><td class="if-money"><strong>$0,00</strong></td>
            <td><input class="if-quick-reference" maxlength="255" placeholder="Opcional" title="Referencia"></td>
            <td><div class="if-row-actions"><button class="if-quick-clear" type="button" title="Limpiar fila"><i class="fa-solid fa-eraser"></i></button></div></td>`;
        body.appendChild(row);
    }

    function resetQuickRow() {
        hideQuickResults();
        q('#if-quick-lines').innerHTML = '';
        ensureQuickRow();
        saveInvoiceDraft();
    }

    function positionQuickResults(input) {
        const panel = q('#if-quick-results');
        const rect = input.getBoundingClientRect();
        const width = Math.min(Math.max(390, rect.width * 3.7), window.innerWidth - 24);
        panel.style.width = `${width}px`;
        panel.style.left = `${Math.max(12, Math.min(rect.left, window.innerWidth - width - 12))}px`;
        panel.style.top = `${Math.max(12, Math.min(rect.bottom + 4, window.innerHeight - 260))}px`;
    }

    function hideQuickResults() {
        const panel = q('#if-quick-results');
        if (!panel) return;
        panel.classList.remove('active');
        panel.innerHTML = '';
        quickActiveInput = null;
    }

    function selectQuickProduct(row, product) {
        const defaultType = defaultTaxType();
        const applies = Number(product.aplica_iva) && Number(defaultType?.tasa || 0) > 0 ? 1 : 0;
        const detail = {
            pedido: row.querySelector('.if-quick-pedido').value.trim(),
            requisicion: row.querySelector('.if-quick-requisition').value.trim(),
            cantidad: Math.max(1, Number(row.querySelector('.if-quick-quantity').value || 1)),
            precio_unitario: Number(product.precio_actual || 0),
            grava_iva: applies,
            iva_tipo_id: applies ? Number(defaultType?.id || 0) : '',
            iva_porcentaje: applies ? Number(defaultType?.tasa || 0) : 0,
            referencia: row.querySelector('.if-quick-reference').value.trim()
        };
        if (addOrUpdateLine(product, detail)) resetQuickRow();
    }

    async function loadQuickRequisition(row) {
        if (!editing || !cfg.requisicionUrl) return;
        const input = row.querySelector('.if-quick-requisition');
        const button = row.querySelector('.if-quick-load-requisition');
        const number = input.value.trim();
        if (!number) {
            input.focus();
            return;
        }
        button.disabled = true;
        button.querySelector('i').className = 'fa-solid fa-spinner fa-spin';
        setScanStatus(`Consultando requisición ${number}…`, 'loading');
        try {
            const url = new URL(cfg.requisicionUrl, window.location.href);
            url.searchParams.set('numero', number);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('No fue posible consultar la requisición.');
            const payload = await response.json();
            const note = payload?.nota;
            const details = Array.isArray(note?.detalles) ? note.detalles : [];
            if (!payload?.encontrada || !note) {
                setScanStatus(payload?.mensaje || `No se encontró la requisición ${number}.`, 'error');
                return;
            }
            if (!details.length) {
                setScanStatus(`La requisición ${note.numero || number} no tiene productos para cargar.`, 'error');
                return;
            }

            const requestNumber = note.numero || number;
            const pedido = row.querySelector('.if-quick-pedido').value.trim();
            const generalReference = row.querySelector('.if-quick-reference').value.trim();
            const defaultType = defaultTaxType();
            let inserted = 0;
            let combined = 0;
            details.forEach((item) => {
                const itemId = Number(item.item_id || item.id || 0);
                if (!itemId) return;
                const quantity = Math.max(1, Number(item.cantidad_solicitada || item.cantidad || 1));
                const reference = String(item.observacion_bodega || generalReference || note.referencia || '').trim();
                const existing = qa('#if-lineas tr').find((current) => Number(current.dataset.itemId) === itemId);
                if (existing?._lineData) {
                    const data = existing._lineData;
                    data.quantity += quantity;
                    if (pedido) data.pedido = pedido;
                    data.requisicion = requestNumber;
                    if (reference && !data.reference.includes(reference)) data.reference = [data.reference, reference].filter(Boolean).join(' | ');
                    renderRow(existing);
                    combined++;
                    return;
                }
                const applies = Number(item.aplica_iva) && Number(defaultType?.tasa || 0) > 0 ? 1 : 0;
                const product = {
                    id: itemId,
                    codigo: item.item_codigo || item.codigo || item.item_secuencial || item.secuencial || '',
                    nombre: item.item_nombre || item.nombre || '',
                    existencia: Number(item.existencia || 0),
                    precio_actual: Number(item.precio_promedio || item.precio_actual || 0),
                    aplica_iva: Number(item.aplica_iva ?? 1),
                    codigo_contable: item.codigo_contable || '',
                    cuenta_contable: item.cuenta_contable || item.grupo_nombre || ''
                };
                if (addOrUpdateLine(product, {
                    pedido,
                    requisicion: requestNumber,
                    cantidad: quantity,
                    precio_unitario: product.precio_actual,
                    grava_iva: applies,
                    iva_tipo_id: applies ? Number(defaultType?.id || 0) : '',
                    iva_porcentaje: applies ? Number(defaultType?.tasa || 0) : 0,
                    referencia: reference
                })) inserted++;
            });
            summary();
            resetQuickRow();
            setScanStatus(`Requisición ${requestNumber} cargada: ${inserted + combined} producto(s) procesados${combined ? `, ${combined} acumulado(s) con líneas existentes` : ''}.`, 'success');
        } catch (error) {
            setScanStatus(error?.message || 'No fue posible cargar los productos de la requisición.', 'error');
        } finally {
            button.disabled = false;
            button.querySelector('i').className = 'fa-solid fa-magnifying-glass';
        }
    }

    function showQuickResults(input, products) {
        const panel = q('#if-quick-results');
        panel.innerHTML = '';
        quickActiveInput = input;
        positionQuickResults(input);
        if (!products.length) {
            panel.innerHTML = '<div class="if-quick-empty">No se encontraron productos.</div>';
        } else {
            products.forEach((product, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'if-quick-option' + (index === 0 ? ' active' : '');
                const copy = document.createElement('div');
                const code = document.createElement('strong');
                const name = document.createElement('small');
                const stock = document.createElement('span');
                code.textContent = product.codigo || 'Sin código';
                name.textContent = product.nombre || 'Producto';
                stock.textContent = `Exist. ${Number(product.existencia || 0).toLocaleString('es-EC')} · ${priceMoney.format(Number(product.precio_actual || 0))}`;
                copy.append(code, name);
                button.append(copy, stock);
                button.addEventListener('click', () => selectQuickProduct(input.closest('tr'), product));
                panel.appendChild(button);
            });
        }
        panel.classList.add('active');
    }

    async function searchQuickProducts(input) {
        const query = input.value.trim();
        if (query.length < 2) return hideQuickResults();
        if (quickSearchController) quickSearchController.abort();
        quickSearchController = new AbortController();
        quickActiveInput = input;
        positionQuickResults(input);
        q('#if-quick-results').innerHTML = '<div class="if-quick-empty"><i class="fa-solid fa-spinner fa-spin"></i> Buscando…</div>';
        q('#if-quick-results').classList.add('active');
        try {
            const url = new URL(cfg.productosUrl, window.location.href);
            url.searchParams.set('draw', '1');
            url.searchParams.set('start', '0');
            url.searchParams.set('length', '8');
            url.searchParams.set('search[value]', query);
            const response = await fetch(url, { signal: quickSearchController.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('No fue posible buscar.');
            const payload = await response.json();
            if (quickActiveInput === input) showQuickResults(input, Array.isArray(payload.data) ? payload.data : []);
        } catch (error) {
            if (error.name !== 'AbortError' && quickActiveInput === input) showQuickResults(input, []);
        }
    }

    function queueQuickSearch(input) {
        clearTimeout(quickSearchTimer);
        quickSearchTimer = setTimeout(() => searchQuickProducts(input), 220);
    }

    function summary() {
        const impuestosPorTasa = {};
        let subtotal = 0;
        let tax = 0;
        let total = 0;
        qa('#if-lineas tr').forEach((row) => {
            const data = row._lineData;
            impuestosPorTasa[data.rate] = (impuestosPorTasa[data.rate] || 0) + data.tax;
            subtotal += data.subtotal;
            tax += data.tax;
            total += data.total;
        });
        q('#if-subtotal-general').textContent = money.format(subtotal);
        q('#if-bases').innerHTML = Object.keys(impuestosPorTasa).sort((a, b) => Number(a) - Number(b)).map((rateValue) => {
            const rate = Number(rateValue);
            const label = rate === 0 ? 'IVA 0%' : `IVA ${rate.toLocaleString('es-EC')}%`;
            return `<div class="if-summary-row if-summary-active"><span>${label}</span><strong>${money.format(impuestosPorTasa[rate])}</strong></div>`;
        }).join('');
        q('#if-total-iva').textContent = money.format(tax);
        q('#if-total').textContent = money.format(total);
        q('#if-empty-lines').hidden = qa('#if-lineas tr').length > 0;
        saveInvoiceDraft();
    }

    function invoiceDraftPayload() {
        const fields = {};
        ['#if-numero', '#if-fecha', '#if-proveedor', '#if-descripcion', '#if-orden', '#if-iva-default'].forEach((selector) => {
            const field = q(selector);
            if (field) fields[selector] = field.value;
        });
        const quickRow = q('#if-quick-lines tr');
        const quick = quickRow ? {
            pedido: quickRow.querySelector('.if-quick-pedido')?.value || '',
            requisicion: quickRow.querySelector('.if-quick-requisition')?.value || '',
            item: quickRow.querySelector('.if-quick-item')?.value || '',
            cantidad: quickRow.querySelector('.if-quick-quantity')?.value || '1',
            referencia: quickRow.querySelector('.if-quick-reference')?.value || ''
        } : null;
        const editor = q('#if-product-editor').classList.contains('active') && draftProduct ? {
            product: Object.assign({}, draftProduct),
            rowIndex: editingRow?._lineData?.index ?? null,
            line: {
                pedido: q('#if-draft-order').value,
                requisicion: q('#if-draft-requisition').value,
                quantity: q('#if-draft-quantity').value,
                price: q('#if-draft-price').value,
                applies: q('#if-draft-applies').value === '1' ? 1 : 0,
                typeId: q('#if-draft-tax').value,
                reference: q('#if-draft-reference').value
            }
        } : null;
        return {
            savedAt: Date.now(),
            fields,
            lines: qa('#if-lineas tr').map((row) => Object.assign({}, row._lineData)),
            quick,
            editor
        };
    }

    function saveInvoiceDraft() {
        if (!cfg.esNueva || !editing || restoringDraft) return;
        try { localStorage.setItem(invoiceDraftKey, JSON.stringify(invoiceDraftPayload())); } catch (_) {}
    }

    function restoreInvoiceDraft() {
        if (!cfg.esNueva || (cfg.factura?.detalles || []).length) return;
        let draft = null;
        try { draft = JSON.parse(localStorage.getItem(invoiceDraftKey) || 'null'); } catch (_) {}
        if (!draft || Date.now() - Number(draft.savedAt || 0) > 7 * 24 * 60 * 60 * 1000) return;
        restoringDraft = true;
        Object.keys(draft.fields || {}).forEach((selector) => {
            const field = q(selector);
            if (field) {
                field.value = draft.fields[selector] == null ? '' : draft.fields[selector];
                field.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        (draft.lines || []).forEach((line) => addOrUpdateLine({
            id: line.itemId,
            codigo: line.code,
            nombre: line.product,
            existencia: line.stock,
            precio_actual: line.price,
            aplica_iva: line.applies,
            codigo_contable: line.accountCode,
            cuenta_contable: line.accountName
        }, {
            cantidad: line.quantity,
            precio_unitario: line.price,
            grava_iva: line.applies,
            iva_tipo_id: line.typeId,
            iva_porcentaje: line.rate,
            pedido: line.pedido,
            requisicion: line.requisicion,
            referencia: line.reference
        }));
        restoredQuickDraft = draft.quick || null;
        restoredEditorDraft = draft.editor || null;
        restoringDraft = false;
    }

    function restoreTransientDraft() {
        let recovered = false;
        if (restoredQuickDraft) {
            const row = q('#if-quick-lines tr');
            if (row) {
                row.querySelector('.if-quick-pedido').value = restoredQuickDraft.pedido || '';
                row.querySelector('.if-quick-requisition').value = restoredQuickDraft.requisicion || '';
                row.querySelector('.if-quick-item').value = restoredQuickDraft.item || '';
                row.querySelector('.if-quick-quantity').value = restoredQuickDraft.cantidad || '1';
                row.querySelector('.if-quick-reference').value = restoredQuickDraft.referencia || '';
                recovered = Object.values(restoredQuickDraft).some((value) => String(value || '') !== '' && String(value) !== '1');
            }
            restoredQuickDraft = null;
        }
        if (restoredEditorDraft?.product) {
            editingRow = qa('#if-lineas tr').find((row) => Number(row._lineData?.index) === Number(restoredEditorDraft.rowIndex)) || null;
            q('#if-product-editor').classList.add('active');
            chooseProduct(restoredEditorDraft.product, restoredEditorDraft.line || {});
            recovered = true;
            restoredEditorDraft = null;
        }
        if (recovered) setScanStatus('Borrador recuperado automáticamente. Revise los datos y continúe donde quedó.', 'success');
    }

    function defaultTaxType() {
        return ivaByRate(Number(q('#if-iva-default').value || cfg.tasaPredeterminada));
    }

    function resetDraft() {
        draftProduct = null;
        editingRow = null;
        q('#if-draft-order').value = '';
        q('#if-draft-requisition').value = '';
        q('#if-draft-quantity').value = '1';
        q('#if-draft-price').value = '';
        q('#if-draft-applies').value = '1';
        q('#if-draft-tax').value = defaultTaxType()?.id || '';
        q('#if-draft-reference').value = '';
        q('#if-line-editor').classList.remove('active');
        q('#if-product-picker').hidden = false;
        draftTotals();
    }

    function openEditor(row = null) {
        if (!editing) return;
        resetDraft();
        q('#if-product-editor').classList.add('active');
        q('#if-editor-title').textContent = row ? 'Editar producto confirmado' : 'Seleccione un producto';
        if (!productTable) initProductTable(); else productTable.columns.adjust();
        if (row) {
            editingRow = row;
            const data = row._lineData;
            chooseProduct({ id: data.itemId, codigo: data.code, nombre: data.product, existencia: data.stock, precio_actual: data.price, aplica_iva: data.applies, codigo_contable: data.accountCode, cuenta_contable: data.accountName }, data);
        }
        saveInvoiceDraft();
        q('#if-product-editor').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function closeEditor() {
        q('#if-product-editor').classList.remove('active');
        resetDraft();
        saveInvoiceDraft();
    }

    function chooseProduct(product, line = null) {
        draftProduct = product;
        q('#if-product-picker').hidden = true;
        q('#if-line-editor').classList.add('active');
        q('#if-draft-product').textContent = product.nombre || '—';
        q('#if-draft-code').textContent = product.codigo || '—';
        q('#if-draft-stock').textContent = Number(product.existencia || 0).toLocaleString('es-EC');
        q('#if-draft-account').textContent = product.codigo_contable || '—';
        q('#if-draft-account-name').textContent = product.cuenta_contable || 'Sin cuenta asociada';
        q('#if-draft-order').value = line?.pedido || '';
        q('#if-draft-requisition').value = line?.requisicion || '';
        q('#if-draft-quantity').value = String(line?.quantity || 1);
        q('#if-draft-price').value = window.InvMoney.roundPrice(line?.price ?? product.precio_actual ?? 0).toFixed(window.InvMoney.config.priceDecimals);
        window.InvMoney.applyPriceInput(q('#if-draft-price'));
        const applies = line ? Number(line.applies) : Number(product.aplica_iva);
        q('#if-draft-applies').value = applies ? '1' : '0';
        q('#if-draft-tax').value = applies ? String(line?.typeId || defaultTaxType()?.id || '') : '';
        q('#if-draft-reference').value = line?.reference || '';
        q('#if-editor-title').textContent = editingRow ? 'Editar producto confirmado' : 'Complete los datos del producto';
        draftTotals();
        saveInvoiceDraft();
    }

    function draftTotals() {
        const quantity = Math.max(0, Number(q('#if-draft-quantity').value || 0));
        const price = Math.max(0, Number(q('#if-draft-price').value || 0));
        const applies = q('#if-draft-applies').value === '1';
        q('#if-draft-tax').disabled = !applies;
        if (!applies) q('#if-draft-tax').value = '';
        if (applies && !q('#if-draft-tax').value) q('#if-draft-tax').value = defaultTaxType()?.id || '';
        const rate = applies ? Number(q('#if-draft-tax').selectedOptions[0]?.dataset.tasa || 0) : 0;
        const subtotal = window.InvMoney.roundAmount(quantity * price);
        const tax = window.InvMoney.roundAmount(subtotal * rate / 100);
        q('#if-draft-subtotal').textContent = money.format(subtotal);
        q('#if-draft-tax-value').textContent = money.format(tax);
        q('#if-draft-total').textContent = money.format(subtotal + tax);
    }

    function confirmDraft() {
        if (!draftProduct) return;
        const quantity = Number(q('#if-draft-quantity').value || 0);
        if (!Number.isFinite(quantity) || quantity <= 0) {
            q('#if-draft-quantity').focus();
            return;
        }
        const applies = q('#if-draft-applies').value === '1';
        const taxOption = q('#if-draft-tax').selectedOptions[0];
        if (applies && !q('#if-draft-tax').value) {
            q('#if-draft-tax').focus();
            return;
        }
        const detail = {
            pedido: q('#if-draft-order').value.trim(),
            requisicion: q('#if-draft-requisition').value.trim(),
            cantidad: quantity,
            precio_unitario: Number(q('#if-draft-price').value || 0),
            grava_iva: applies ? 1 : 0,
            iva_tipo_id: applies ? Number(q('#if-draft-tax').value) : '',
            iva_porcentaje: applies ? Number(taxOption?.dataset.tasa || 0) : 0,
            referencia: q('#if-draft-reference').value.trim()
        };
        if (addOrUpdateLine(draftProduct, detail, editingRow)) {
            closeEditor();
            q('.if-detail-table-wrap').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function initProductTable() {
        productTable = $('#if-productos').DataTable({
            processing: true,
            serverSide: true,
            searchDelay: 300,
            pageLength: 5,
            lengthChange: false,
            ajax: cfg.productosUrl,
            order: [[0, 'asc']],
            columns: [
                { data: null, render: (row) => `<div class="if-catalog-product"><strong>${esc(row.codigo)}</strong><span>${esc(row.nombre)}</span><small>${esc([row.codigo_interno ? 'Interno '+row.codigo_interno : '',row.codigo_clasificacion ? 'Clasif. '+row.codigo_clasificacion : '',row.cuenta_contable||'',row.marca||'',row.unidad_abrev||row.unidad_nombre||''].filter(Boolean).join(' · '))}</small></div>` },
                { data: 'existencia', className: 'dt-body-center', render: (value) => `<span class="if-stock-badge">${Number(value || 0).toLocaleString('es-EC')}</span>` },
                { data: 'precio_actual', className: 'dt-body-right', render: (value) => `<strong class="if-db-value">${priceMoney.format(Number(value || 0))}</strong>` },
                { data: 'aplica_iva', className: 'dt-body-center', render: (value) => Number(value) ? '<span class="if-tax-badge yes">Aplica</span>' : '<span class="if-tax-badge no">No aplica</span>' },
                { data: null, orderable: false, searchable: false, className: 'dt-body-center', render: () => '<button type="button" class="if-select-product"><i class="fa-solid fa-arrow-right"></i> Seleccionar</button>' }
            ],
            language: {
                search: 'Buscar producto:',
                searchPlaceholder: 'Código, nombre, marca, categoría o unidad…',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ productos',
                infoFiltered: '(filtrado de _MAX_)',
                zeroRecords: 'No se encontraron productos',
                processing: 'Consultando inventario…',
                paginate: { previous: 'Anterior', next: 'Siguiente' }
            }
        });
        q('#if-productos').addEventListener('click', (event) => {
            const button = event.target.closest('.if-select-product');
            if (!button) return;
            const product = productTable.row(button.closest('tr')).data();
            if (product) chooseProduct(product);
        });
    }

    (cfg.factura?.detalles || []).forEach((detail) => addOrUpdateLine({
        id: detail.item_id,
        codigo: detail.item_codigo,
        nombre: detail.item_nombre,
        existencia: detail.existencia,
        precio_actual: detail.precio_unitario,
        aplica_iva: detail.grava_iva,
        codigo_contable: detail.codigo_contable,
        cuenta_contable: detail.cuenta_contable
    }, detail));
    restoreInvoiceDraft();
    if (q('#if-lineas tr')) showProductInfo(q('#if-lineas tr'));
    else clearProductInfo();
    summary();

    q('#if-agregar-producto').addEventListener('click', () => openEditor());
    q('#if-cerrar-editor').addEventListener('click', closeEditor);
    q('#if-change-product').addEventListener('click', () => {
        draftProduct = null;
        q('#if-line-editor').classList.remove('active');
        q('#if-product-picker').hidden = false;
        q('#if-editor-title').textContent = 'Seleccione un producto';
        productTable?.columns.adjust();
    });
    q('#if-confirm-product').addEventListener('click', confirmDraft);
    ['#if-draft-quantity', '#if-draft-applies', '#if-draft-tax'].forEach((selector) => {
        q(selector).addEventListener('input', draftTotals);
        q(selector).addEventListener('change', draftTotals);
    });
    q('#if-lineas').addEventListener('click', (event) => {
        const row = event.target.closest('tr');
        if (!row) return;
        showProductInfo(row);
        if (event.target.closest('.if-row-edit')) openEditor(row);
        if (event.target.closest('.if-row-remove')) {
            row.remove();
            if (editingRow === row) closeEditor();
            const nextRow = q('#if-lineas tr');
            if (nextRow) showProductInfo(nextRow);
            else clearProductInfo();
            summary();
        }
    });
    ['input', 'change'].forEach((eventName) => q('#if-lineas').addEventListener(eventName, (event) => {
        if (event.target.matches('.if-inline-pedido, .if-inline-requisition, .if-inline-quantity, .if-inline-tax, .if-inline-reference')) {
            syncInlineRow(event.target.closest('tr'));
        }
    }));
    q('#if-quick-lines').addEventListener('input', (event) => {
        if (event.target.matches('.if-quick-item')) queueQuickSearch(event.target);
    });
    q('#if-quick-lines').addEventListener('focusin', (event) => {
        if (event.target.matches('.if-quick-item') && event.target.value.trim().length >= 2) queueQuickSearch(event.target);
    });
    q('#if-quick-lines').addEventListener('keydown', (event) => {
        if (event.target.matches('.if-quick-requisition') && event.key === 'Enter') {
            event.preventDefault();
            loadQuickRequisition(event.target.closest('tr'));
            return;
        }
        if (!event.target.matches('.if-quick-item')) return;
        if (event.key === 'Escape') hideQuickResults();
        if (event.key === 'Enter') {
            const first = q('#if-quick-results .if-quick-option');
            if (first) {
                event.preventDefault();
                first.click();
            }
        }
    });
    q('#if-quick-lines').addEventListener('click', (event) => {
        const loadButton = event.target.closest('.if-quick-load-requisition');
        if (loadButton) {
            loadQuickRequisition(loadButton.closest('tr'));
            return;
        }
        if (event.target.closest('.if-quick-clear')) resetQuickRow();
    });
    document.addEventListener('click', (event) => {
        if (!event.target.closest('#if-quick-results') && !event.target.closest('.if-quick-item')) hideQuickResults();
    });
    document.addEventListener('scroll', hideQuickResults, true);
    window.addEventListener('resize', hideQuickResults);

    q('#if-editar').addEventListener('click', () => setEditing(true));
    q('#if-imprimir').addEventListener('click', () => window.print());
    q('#if-limpiar').addEventListener('click', () => {
        if (!editing) return;
        q('#if-form').reset();
        q('#if-fecha').value = cfg.hoy;
        q('#if-lineas').innerHTML = '';
        lineIndex = 0;
        resetQuickRow();
        closeEditor();
        clearProductInfo();
        summary();
    });
    q('#if-aplicar-iva').addEventListener('click', () => {
        if (!editing) return;
        const rate = Number(q('#if-iva-default').value || 0);
        const type = ivaByRate(rate);
        qa('#if-lineas tr').forEach((row) => {
            row._lineData.applies = rate > 0 ? 1 : 0;
            row._lineData.typeId = type?.id || 0;
            row._lineData.rate = rate;
            renderRow(row);
        });
        summary();
    });

    q('#if-anular').addEventListener('click', () => {
        q('#if-anular-panel').classList.add('active');
        q('#if-anular-panel').scrollIntoView({ behavior: 'smooth' });
    });
    q('#if-ingresar').addEventListener('click', () => {
        q('#if-ingresar-panel').classList.add('active');
        q('#if-ingresar-panel').scrollIntoView({ behavior: 'smooth' });
    });
    qa('[data-cancel-panel]').forEach((button) => button.addEventListener('click', () => button.closest('.if-inline-action').classList.remove('active')));
    q('#if-search-record').addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            location.href = `index.php?route=ingresos&termino=${encodeURIComponent(event.target.value.trim())}`;
        }
    });
    q('#if-form').addEventListener('submit', (event) => {
        if (!editing) { event.preventDefault(); return; }
        const invalidQuantity = qa('#if-lineas tr').find((row) => Number(row._lineData?.quantity || 0) <= 0);
        if (invalidQuantity) {
            event.preventDefault();
            const field = invalidQuantity.querySelector('.if-inline-quantity');
            field?.focus();
            field?.reportValidity();
            return;
        }
        const pendingItem = q('#if-quick-lines .if-quick-item');
        if (pendingItem?.value.trim()) {
            event.preventDefault();
            window.alert('Seleccione el producto escrito en la fila rápida o limpie esa fila antes de guardar.');
            pendingItem.focus();
            return;
        }
        if (!qa('#if-lineas tr').length) {
            event.preventDefault();
            window.alert('Agregue y confirme al menos un producto en la factura.');
            return;
        }
        saveInvoiceDraft();
    });

    q('#if-form').addEventListener('input', saveInvoiceDraft);
    q('#if-form').addEventListener('change', saveInvoiceDraft);
    window.addEventListener('offline', saveInvoiceDraft);
    window.addEventListener('pagehide', saveInvoiceDraft);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') saveInvoiceDraft();
    });

    function setScanStatus(message, type = '') {
        const status = q('#if-scan-status');
        status.className = `if-scan-status${type ? ` ${type}` : ''}`;
        status.querySelector('i').className = type === 'loading' ? 'fa-solid fa-spinner fa-spin'
            : type === 'success' ? 'fa-solid fa-circle-check'
                : type === 'error' ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-circle-info';
        status.querySelector('span').textContent = message;
    }

    function loadScanLibrary(id, source, ready) {
        return new Promise((resolve, reject) => {
            if (ready()) { resolve(); return; }
            const current = document.getElementById(id);
            if (current) {
                const started = Date.now();
                const wait = () => ready() ? resolve() : Date.now() - started > 15000 ? reject(new Error('Tiempo de carga agotado.')) : setTimeout(wait, 80);
                wait();
                return;
            }
            const script = document.createElement('script');
            script.id = id;
            script.src = source;
            script.onload = resolve;
            script.onerror = () => reject(new Error('No se pudo cargar el lector del PDF.'));
            document.head.appendChild(script);
        });
    }

    function pdfPageText(content) {
        const rows = new Map();
        (content.items || []).forEach((item) => {
            const y = Math.round(Number(item.transform?.[5] || 0) / 3) * 3;
            if (!rows.has(y)) rows.set(y, []);
            rows.get(y).push({ x: Number(item.transform?.[4] || 0), text: String(item.str || '').trim() });
        });
        return Array.from(rows.entries()).sort((a, b) => b[0] - a[0]).map(([, items]) => items.sort((a, b) => a.x - b.x).map((item) => item.text).filter(Boolean).join(' ')).filter(Boolean).join('\n');
    }

    async function extractPdfText(file, forceVisual = false) {
        await loadScanLibrary('if-pdfjs', 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js', () => Boolean(window.pdfjsLib));
        window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        const pdf = await window.pdfjsLib.getDocument({ data: new Uint8Array(await file.arrayBuffer()) }).promise;
        const pages = Math.min(pdf.numPages, 5);
        let text = '';
        for (let pageNumber = 1; pageNumber <= pages; pageNumber++) {
            setScanStatus(`Leyendo página ${pageNumber} de ${pages}…`, 'loading');
            const page = await pdf.getPage(pageNumber);
            text += `\n${pdfPageText(await page.getTextContent())}`;
        }
        if (!forceVisual && text.replace(/\s/g, '').length >= 80) return text.trim();

        setScanStatus('El PDF es una imagen. Iniciando reconocimiento visual…', 'loading');
        await loadScanLibrary('if-tesseract', 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js', () => Boolean(window.Tesseract));
        text = '';
        for (let pageNumber = 1; pageNumber <= Math.min(pdf.numPages, 3); pageNumber++) {
            const page = await pdf.getPage(pageNumber);
            const viewport = page.getViewport({ scale: 1.7 });
            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
            const result = await window.Tesseract.recognize(canvas, 'spa', { logger: (progress) => {
                if (progress.status === 'recognizing text') setScanStatus(`Reconociendo página ${pageNumber}: ${Math.round((progress.progress || 0) * 100)}%`, 'loading');
            } });
            text += `\n${result.data.text || ''}`;
        }
        return text.trim();
    }

    function normalizeScanText(value) {
        return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase().replace(/[^A-Z0-9]+/g, ' ').trim();
    }

    function scanNumber(value) {
        let clean = String(value || '').replace(/\s/g, '');
        const comma = clean.lastIndexOf(',');
        const dot = clean.lastIndexOf('.');
        if (comma > dot) clean = clean.replace(/\./g, '').replace(',', '.'); else clean = clean.replace(/,/g, '');
        return Number(clean);
    }

    function scanDate(text) {
        const dayFirst = text.match(/\b(\d{1,2})[\/-](\d{1,2})[\/-](20\d{2})\b/);
        if (dayFirst) return `${dayFirst[3]}-${dayFirst[2].padStart(2, '0')}-${dayFirst[1].padStart(2, '0')}`;
        const yearFirst = text.match(/\b(20\d{2})[\/-](\d{1,2})[\/-](\d{1,2})\b/);
        return yearFirst ? `${yearFirst[1]}-${yearFirst[2].padStart(2, '0')}-${yearFirst[3].padStart(2, '0')}` : '';
    }

    function cleanProviderName(value) {
        return String(value || '')
            .split(/\b(?:RUC|IDENTIFICACI[ÓO]N|DIRECCI[ÓO]N|TEL[ÉE]FONO|CELULAR|EMAIL|CORREO|FECHA|FACTURA|OBLIGADO)\b/i)[0]
            .replace(/^(?:RAZ[ÓO]N\s+SOCIAL|PROVEEDOR|EMISOR|CLIENTE)\s*[:\-]?\s*/i, '')
            .replace(/\s{2,}/g, ' ').replace(/^[\s:.-]+|[\s:;,-]+$/g, '').trim().slice(0, 120);
    }

    function scanProvider(text, candidate = null) {
        candidate = candidate || scanProviderCandidate(text);
        const providers = cfg.proveedores || [];
        if (candidate.ruc) {
            const byRuc = providers.find((provider) => String(provider.ruc || '').replace(/\D/g, '') === candidate.ruc);
            if (byRuc) return byRuc;
        }
        const wanted = normalizeScanText(candidate.nombre || '');
        if (wanted.length < 7) return null;
        return providers.find((provider) => {
            const current = normalizeScanText(provider.nombre || '');
            if (current === wanted) return true;
            const shorter = Math.min(current.length, wanted.length);
            return shorter >= 10 && (current.includes(wanted) || wanted.includes(current)) && shorter / Math.max(current.length, wanted.length) >= 0.72;
        }) || null;
    }

    function scanProviderCandidate(text) {
        const ruc = (text.match(/(?:RUC|IDENTIFICACI[ÓO]N)[^0-9]{0,15}(\d{13})/i) || text.match(/\b(\d{13})\b/))?.[1] || '';
        const lines = text.split(/\n+/).map((line) => line.replace(/\s+/g, ' ').trim()).filter(Boolean);
        let name = '';
        for (let index = 0; index < lines.length; index++) {
            const match = lines[index].match(/^(?:RAZ[ÓO]N\s+SOCIAL|PROVEEDOR|EMISOR)\s*[:\-]?\s*(.*)$/i);
            if (!match) continue;
            name = cleanProviderName(match[1]);
            if (!name && lines[index + 1]) name = cleanProviderName(lines[index + 1]);
            if (name) break;
        }
        if (!name && ruc) {
            const index = lines.findIndex((line) => line.includes(ruc));
            const nearby = [lines[index - 1], lines[index + 1]].filter(Boolean).map(cleanProviderName)
                .filter((line) => /[A-ZÁÉÍÓÚÑ]{3}/i.test(line) && !/^(?:RUC|DIRECCI|TEL|FECHA|FACTURA)/i.test(line) && !/^\d+$/.test(line));
            name = nearby.sort((a, b) => b.length - a.length)[0] || '';
        }
        return { ruc, nombre: cleanProviderName(name) };
    }

    function scanHeader(text) {
        const invoiceMatch = text.match(/\b(\d{3}[- ]\d{3}[- ]\d{6,9})\b/)
            || text.match(/(?:FACTURA|COMPROBANTE|N[ÚU]MERO|NRO\.?|NO\.?)\s*(?:N[ÚU]MERO|NRO\.?|NO\.?)?\s*[:#-]\s*([A-Z0-9][A-Z0-9-]{2,30})/i);
        const descriptionMatch = text.match(/(?:CONCEPTO|DETALLE GENERAL|OBSERVACIONES?)\s*[:\-]?\s*([^\n]{4,180})/i)
            || text.match(/DESCRIPCI[ÓO]N\s*:\s*([^\n]{4,180})/i);
        const rateMatch = text.match(/(?:IVA|TARIFA)\s*(\d{1,2}(?:[.,]\d+)?)\s*%/i);
        const providerCandidate = scanProviderCandidate(text);
        return {
            invoice: invoiceMatch ? invoiceMatch[1].trim() : '',
            date: scanDate(text),
            provider: scanProvider(text, providerCandidate),
            providerCandidate,
            description: descriptionMatch ? descriptionMatch[1].trim() : '',
            rate: rateMatch ? scanNumber(rateMatch[1]) : null
        };
    }

    function mergeInvoiceTableLines(text) {
        const lines = text.split(/\n+/).map((line) => line.replace(/\s+/g, ' ').trim()).filter(Boolean);
        const merged = [];
        const codeOnly = /^[A-Z0-9][A-Z0-9./-]{2,24}$/i;
        const header = /^(?:C[ÓO]D(?:IGO)?|CANTIDAD|DESCRIPCI[ÓO]N|DETALLE|PRECIO|SUBSIDIO|DESCUENTO|TOTAL)$/i;
        for (let index = 0; index < lines.length; index++) {
            const current = lines[index];
            const next = lines[index + 1] || '';
            const nextHasQuantityAndPrices = (next.match(/\d+[.,]\d{2,4}\b/g) || []).length >= 2
                && /\b\d+(?:[.,]\d+)?\b/.test(next);
            if (codeOnly.test(current) && !header.test(current) && nextHasQuantityAndPrices) {
                merged.push(`${current} ${next}`);
                index++;
            } else {
                merged.push(current);
            }
        }
        return merged;
    }

    function scanLineCandidates(text) {
        const ignored = /\b(SUBTOTAL|BASE IMPONIBLE|IMPUESTO|IVA|RUC|FACTURA|DESCUENTO TOTAL|TOTAL A PAGAR)\b/i;
        const candidates = [];
        mergeInvoiceTableLines(text).forEach((line) => {
            if (ignored.test(line)) return;
            const numbers = Array.from(line.matchAll(/\b\d+(?:[.,]\d{1,4})?\b/g)).map((match) => ({
                raw: match[0], index: match.index, end: match.index + match[0].length, value: scanNumber(match[0]),
                money: /[.,]\d{2,4}$/.test(match[0])
            })).filter((number) => Number.isFinite(number.value));
            const moneyNumbers = numbers.filter((number) => number.money);
            if (moneyNumbers.length < 2) return;

            const totalToken = moneyNumbers[moneyNumbers.length - 1];
            const priceOptions = moneyNumbers.slice(0, -1);
            let best = null;
            priceOptions.forEach((priceToken) => {
                numbers.filter((number) => number.index < priceToken.index && number.value > 0 && number.value <= 100000).forEach((quantityToken) => {
                    if (/^\d{6,}$/.test(quantityToken.raw)) return;
                    const expected = quantityToken.value * priceToken.value;
                    const error = Math.abs(expected - totalToken.value) / Math.max(1, totalToken.value);
                    if (error > 0.035) return;
                    const distance = priceToken.index - quantityToken.end;
                    const score = 100 - (error * 1000) - Math.min(distance / 40, 10) + (Number.isInteger(quantityToken.value) ? 3 : 0);
                    if (!best || score > best.score) best = { quantityToken, priceToken, score };
                });
            });

            const priceToken = best?.priceToken || priceOptions[priceOptions.length - 1];
            const possibleQuantities = numbers.filter((number) => number.index < priceToken.index
                && number.value > 0 && number.value <= 100000 && !/^\d{6,}$/.test(number.raw));
            const quantityToken = best?.quantityToken || possibleQuantities[possibleQuantities.length - 1];
            if (!quantityToken || !priceToken) return;

            let descriptivePart = line.slice(0, priceToken.index);
            descriptivePart = descriptivePart.slice(0, quantityToken.index)
                + ' ' + descriptivePart.slice(quantityToken.end);
            descriptivePart = descriptivePart.replace(/^[\s#|:.-]+|[\s|:.-]+$/g, '').replace(/\s+/g, ' ').trim();
            const words = descriptivePart.split(' ');
            const codeAt = words.findIndex((word, index) => index < 3
                && /^(?=.*\d)[A-Z0-9][A-Z0-9./-]{2,24}$/i.test(word));
            const code = codeAt >= 0 ? words.splice(codeAt, 1)[0] : '';
            const description = words.join(' ').replace(/^[\s|:.-]+|[\s|:.-]+$/g, '').trim();
            if (description.length < 3 || !/[A-ZÁÉÍÓÚÑ]/i.test(description)) return;
            const quantity = Math.max(0.0001, quantityToken.value);
            const price = Math.max(0, priceToken.value);
            const total = Math.max(0, totalToken.value);
            const key = normalizeScanText(code || description).slice(0, 70);
            if (!key || candidates.some((candidate) => candidate.key === key)) return;
            candidates.push({ key, code, description, quantity, price, total, source: line });
        });
        return candidates.slice(0, 30);
    }

    async function fetchProductsForScan(term) {
        if (!term || term.trim().length < 2) return [];
        const params = new URLSearchParams({ draw: '1', start: '0', length: '15', 'search[value]': term.trim() });
        const response = await fetch(`${cfg.productosUrl}&${params.toString()}`, { headers: { Accept: 'application/json' } });
        const body = await response.text();
        if (!response.ok) throw new Error('No fue posible consultar los productos del inventario. Intente nuevamente.');
        if (!body.trim()) throw new Error('El servidor no devolvió resultados al consultar los productos.');
        try {
            return (JSON.parse(body).data || []);
        } catch (_) {
            throw new Error('La respuesta de productos no tiene un formato válido.');
        }
    }

    function productMatchScore(candidate, row) {
        const wanted = normalizeScanText(candidate.description);
        const current = normalizeScanText(row.nombre);
        if (wanted && current === wanted) return 110;
        if (wanted.length >= 6 && (current.includes(wanted) || wanted.includes(current))) return 95;
        const ignoredWords = new Set(['DE', 'DEL', 'LA', 'EL', 'LOS', 'LAS', 'Y', 'PARA', 'GL', 'UN']);
        const wantedTokens = wanted.split(' ').filter((token) => token.length >= 3 && !ignoredWords.has(token));
        const currentTokens = new Set(current.split(' ').filter((token) => token.length >= 3 && !ignoredWords.has(token)));
        const matches = wantedTokens.filter((token) => currentTokens.has(token)).length;
        const coverage = wantedTokens.length ? matches / wantedTokens.length : 0;
        return coverage >= 0.75 && matches >= 1 ? 70 + coverage * 20 : 0;
    }

    async function matchScannedProduct(candidate) {
        const descriptionTokens = candidate.description.split(/\s+/).filter(Boolean);
        const significant = descriptionTokens.filter((token) => token.length >= 4).sort((a, b) => b.length - a.length);
        const terms = [
            descriptionTokens.slice(0, 7).join(' '),
            descriptionTokens.slice(0, 4).join(' '),
            significant.slice(0, 4).join(' ')
        ].filter(Boolean);
        const collected = new Map();
        for (const term of terms) {
            const rows = await fetchProductsForScan(term);
            rows.forEach((row) => collected.set(Number(row.id), row));
            if (rows.some((row) => productMatchScore(candidate, row) >= 100)) break;
        }
        const ranked = Array.from(collected.values()).map((row) => ({ row, score: productMatchScore(candidate, row) })).sort((a, b) => b.score - a.score);
        const ambiguous = ranked[0] && ranked[1] && ranked[0].score >= 85 && ranked[1].score === ranked[0].score;
        const product = ranked[0] && ranked[0].score >= 85 && !ambiguous ? ranked[0].row : null;
        return { ...candidate, product, matches: product ? [] : ranked.filter((match) => match.score >= 70).slice(0, 5).map((match) => match.row) };
    }

    function renderScanReview(data, fileName) {
        const field = (label, value) => `<div class="if-detected-field ${value ? '' : 'missing'}"><span>${esc(label)}</span><strong>${esc(value || 'No detectado')}</strong></div>`;
        data.fileName = fileName || data.fileName || 'Factura escaneada';
        q('#if-scan-file-name').textContent = data.fileName;
        const providerValue = data.provider?.nombre || data.providerCandidate?.nombre || (data.providerCandidate?.ruc ? `RUC ${data.providerCandidate.ruc}` : '');
        const providerField = field('Proveedor', providerValue) + (!data.provider ? '<button type="button" class="if-scan-create" data-scan-create-provider><i class="fa-solid fa-plus"></i> Revisar y crear proveedor</button>' : '');
        q('#if-scan-detected').innerHTML = field('Número de factura', data.invoice) + field('Fecha', data.date) + `<div class="if-detected-action">${providerField}</div>` + field('IVA', data.rate === null ? '' : `${data.rate}%`) + field('Concepto', data.description);
        q('#if-scan-lines').innerHTML = data.lines.length
            ? `<div class="if-scan-lines-title"><span>${data.lines.length} producto(s) leídos</span><span>${data.lines.filter((line) => line.product).length} relacionados con inventario</span></div><div class="if-scanned-columns"><span>Código</span><span>Producto</span><span>Cantidad</span><span>P. unitario</span><span>Total</span><span>Estado</span><span>Acción</span></div>` + data.lines.map((line, index) => {
                const unitPrice = Number(line.price || line.product?.precio_actual || 0);
                const lineTotal = Number(line.total || (line.quantity * unitPrice));
                const status = line.product ? 'Existente' : 'Se creará al aplicar';
                const actions = line.product ? '<span></span>' : `<span class="if-scan-choice"><button type="button" class="if-scan-create" data-scan-review-product="${index}"><i class="fa-solid fa-magnifying-glass"></i> Revisar coincidencias</button></span>`;
                return `<div class="if-scanned-line"><strong>${esc(line.code || line.product?.codigo || 'Sin código')}</strong><span>${esc(line.product?.nombre || line.description)}</span><span>${line.quantity.toLocaleString('es-EC')} u.</span><span>${priceMoney.format(unitPrice)}</span><span>${money.format(lineTotal)}</span><span class="${line.product ? 'matched' : 'unmatched'}">${status}</span>${actions}</div>`;
            }).join('')
            : '<div class="if-scan-lines-title"><span>No se detectaron productos relacionables</span><span>Puede agregarlos manualmente</span></div>';
        q('#if-scan-review').classList.add('active');
        q('#if-scan-review').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function refreshScannedMatches() {
        if (!scannedData) return;
        if (!scannedData.provider && scannedData.providerCandidate) scannedData.provider = scanProvider(scannedData.text || '', scannedData.providerCandidate);
        scannedData.lines = await Promise.all(scannedData.lines.map((line) => line.product ? line : matchScannedProduct(line)));
        renderScanReview(scannedData, scannedData.fileName);
    }

    async function scanPdf(file) {
        if (!editing || !file) return;
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) { setScanStatus('Seleccione un documento PDF válido.', 'error'); return; }
        if (file.size <= 0 || file.size > 12 * 1024 * 1024) { setScanStatus('El PDF debe pesar máximo 12 MB.', 'error'); return; }
        q('#if-scan-review').classList.remove('active');
        setScanStatus('Preparando el lector de factura…', 'loading');
        try {
            let text = await extractPdfText(file);
            if (!text || text.replace(/\s/g, '').length < 20) throw new Error('El documento no contiene texto reconocible.');
            let candidates = scanLineCandidates(text);
            if (!candidates.length) {
                setScanStatus('No se distinguieron productos en el texto del PDF. Probando lectura visual…', 'loading');
                const visualText = await extractPdfText(file, true);
                const visualCandidates = scanLineCandidates(visualText);
                if (visualCandidates.length) {
                    text = visualText;
                    candidates = visualCandidates;
                }
            }
            const header = scanHeader(text);
            const lines = await Promise.all(candidates.map(matchScannedProduct));
            scannedData = { ...header, lines, text };
            renderScanReview(scannedData, file.name);
            const matched = lines.filter((line) => line.product).length;
            const pending = lines.length - matched;
            setScanStatus(`Lectura terminada: ${lines.length} producto(s) leídos, ${matched} existente(s)${pending ? ` y ${pending} nuevo(s) que se crearán al aplicar` : ''}.`, 'success');
        } catch (error) {
            scannedData = null;
            setScanStatus(error?.message || 'No fue posible leer el PDF. Complete la factura manualmente.', 'error');
        } finally {
            q('#if-pdf-file').value = '';
        }
    }

    function discardScan() {
        scannedData = null;
        q('#if-scan-review').classList.remove('active');
        setScanStatus('El documento se analiza localmente y siempre podrá revisar los datos antes de aplicarlos.');
    }

    async function applyScan() {
        if (!scannedData || !editing) return;
        const applyButton = q('#if-scan-apply');
        applyButton.disabled = true;
        try {
            const unresolved = scannedData.lines.map((line, indice) => ({ line, indice })).filter(({line}) => !line.product);
            let created = 0;
            if (unresolved.length) {
                setScanStatus(`Buscando o creando ${unresolved.length} producto(s) en el catálogo interno…`, 'loading');
                const formData = new FormData();
                formData.set('csrf_token', q('#if-form [name="csrf_token"]').value);
                formData.set('lineas', JSON.stringify(unresolved.map(({line, indice}) => ({
                    indice,
                    nombre: line.description,
                    codigo_proveedor: line.code || '',
                    precio: Number(line.price || 0),
                    aplica_iva: Number(scannedData.rate || cfg.tasaPredeterminada || 0) > 0 ? 1 : 0
                }))));
                const response = await fetch(cfg.resolverProductosUrl, {
                    method: 'POST', body: formData, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(result.error || 'No fue posible procesar los productos detectados.');
                (result.productos || []).forEach((resolved) => {
                    if (scannedData.lines[Number(resolved.indice)] && resolved.producto) {
                        scannedData.lines[Number(resolved.indice)].product = resolved.producto;
                        scannedData.lines[Number(resolved.indice)].created = Boolean(resolved.creado);
                    }
                });
                created = Number(result.creados || 0);
            }
            const unresolvedAfter = scannedData.lines.filter((line) => !line.product);
            if (unresolvedAfter.length) throw new Error(`${unresolvedAfter.length} producto(s) no pudieron relacionarse con el inventario.`);

            if (scannedData.invoice) q('#if-numero').value = scannedData.invoice;
            if (scannedData.date) q('#if-fecha').value = scannedData.date;
            if (scannedData.provider) {
                q('#if-proveedor').value = String(scannedData.provider.id);
                q('#if-proveedor').dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (scannedData.description) q('#if-descripcion').value = scannedData.description;
            if (scannedData.rate !== null) q('#if-iva-default').value = String(scannedData.rate);
            let added = 0;
            let combined = 0;
            scannedData.lines.forEach((line) => {
                const applies = Number(line.product.aplica_iva) ? 1 : 0;
                const taxType = applies ? (ivaByRate(scannedData.rate ?? cfg.tasaPredeterminada) || defaultTaxType()) : null;
                const duplicate = qa('#if-lineas tr').find((row) => Number(row.dataset.itemId) === Number(line.product.id));
                if (duplicate?._lineData) {
                    duplicate._lineData.quantity += Number(line.quantity || 0);
                    renderRow(duplicate);
                    combined++;
                } else if (addOrUpdateLine(line.product, {
                    cantidad: line.quantity,
                    precio_unitario: Number(line.price || line.product.precio_actual || 0),
                    grava_iva: applies, iva_tipo_id: taxType?.id || '',
                    iva_porcentaje: applies ? Number(taxType?.tasa || 0) : 0,
                    referencia: line.code ? `Código proveedor: ${line.code}` : ''
                })) added++;
            });
            updateRowDocumentData();
            q('#if-scan-review').classList.remove('active');
            setScanStatus(`Datos aplicados: ${added} producto(s) agregado(s)${combined ? `, ${combined} acumulado(s)` : ''}${created ? ` y ${created} creado(s) en el catálogo interno` : ''}. Revise la factura antes de guardarla.`, 'success');
            scannedData = null;
            productTable?.ajax.reload(null, false);
        } catch (error) {
            setScanStatus(error?.message || 'No fue posible aplicar los productos detectados.', 'error');
            renderScanReview(scannedData, scannedData?.fileName);
        } finally {
            applyButton.disabled = false;
        }
    }

    q('#if-pdf-file').addEventListener('change', (event) => scanPdf(event.target.files?.[0]));
    q('#if-scan-cancel').addEventListener('click', discardScan);
    q('#if-scan-discard').addEventListener('click', discardScan);
    q('#if-scan-apply').addEventListener('click', applyScan);
    q('#if-scan-review').addEventListener('click', (event) => {
        const providerButton = event.target.closest('[data-scan-create-provider]');
        if (providerButton && scannedData) {
            const candidate = scannedData.providerCandidate || {};
            const params = new URLSearchParams({ route: 'inv_maestros', tabla: 'proveedores', nuevo: '1' });
            if (candidate.nombre) params.set('nombre', candidate.nombre);
            if (candidate.ruc) params.set('ruc', candidate.ruc);
            window.open(`index.php?${params.toString()}`, '_blank', 'noopener');
            setScanStatus('Complete el proveedor en la pestaña nueva. Al regresar se volverá a buscar automáticamente.', 'loading');
            return;
        }
        const reviewButton = event.target.closest('[data-scan-review-product]');
        if (reviewButton && scannedData) {
            const line = scannedData.lines[Number(reviewButton.dataset.scanReviewProduct)];
            if (!line) return;
            openEditor();
            setTimeout(() => {
                if (productTable) productTable.search(line.description || line.code || '').draw();
            }, 50);
            setScanStatus('Seleccione la coincidencia correcta en el catálogo y confirme el producto.', 'loading');
            return;
        }
        const productButton = event.target.closest('[data-scan-create-product]');
        if (productButton && scannedData) {
            const line = scannedData.lines[Number(productButton.dataset.scanCreateProduct)];
            if (!line) return;
            const params = new URLSearchParams({
                route: 'inv_items_sistema', nuevo: '1',
                nombre: line.description || line.code || 'Nuevo ítem',
                descripcion: `Creado desde revisión de factura${line.code ? ` · referencia ${line.code}` : ''}`,
                precio: String(Number(line.price || 0))
            });
            window.open(`index.php?${params.toString()}`, '_blank', 'noopener');
            setScanStatus('Complete el ítem en Maestro de Ítems. Al regresar se volverá a relacionar automáticamente.', 'loading');
        }
    });

    setEditing(editing);
    if (cfg.vistaPrevia) setEditing(false);
})();
