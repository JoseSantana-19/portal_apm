(function () {
    'use strict';

    const cfg = JSON.parse(document.getElementById('if-form-config').textContent);
    const money = new Intl.NumberFormat('es-EC', { style: 'currency', currency: 'USD' });
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
    let providerIds = new Set(qa('#if-proveedor option[value]').map((option) => Number(option.value)));

    function ivaByRate(rate) {
        return cfg.tiposIva.find((item) => Math.abs(Number(item.tasa) - Number(rate)) < 0.0001) || null;
    }

    function setEditing(value) {
        editing = Boolean(value && cfg.puedeModificar && !cfg.vistaPrevia);
        qa('.if-editable').forEach((field) => { field.disabled = !editing; });
        qa('.if-row-edit, .if-row-remove').forEach((button) => { button.hidden = !editing; });
        q('#if-agregar-producto').disabled = !editing;
        q('#if-aplicar-iva').disabled = !editing;
        q('#if-guardar').disabled = !editing;
        q('#if-editar').disabled = editing || cfg.esNueva || cfg.estado !== 'REGISTRADA' || !cfg.puedeModificar;
        q('#if-limpiar').disabled = !editing;
        q('#if-document-page').classList.toggle('if-editing', editing);
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
            q('#if-proveedor').innerHTML = '<option value="">Seleccione…</option>' + providers.map((provider) => {
                const code = provider.codigo || `PRV-${provider.id}`;
                return `<option value="${Number(provider.id)}">${esc(code)} · ${esc(provider.nombre)}</option>`;
            }).join('');
            providerIds = new Set(providers.map((provider) => Number(provider.id)));
            q('#if-proveedor').value = newProviders.length === 1 ? String(newProviders[0].id) : (current ? String(current) : '');
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
        qa('#if-lineas tr').forEach((row) => {
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
        const subtotal = data.quantity * data.price;
        const tax = Math.round(subtotal * data.rate) / 100;
        const total = subtotal + tax;
        data.subtotal = subtotal;
        data.tax = tax;
        data.total = total;
        const rateLabel = data.applies ? `${data.rate.toLocaleString('es-EC')}%` : 'No aplica';
        row.dataset.itemId = String(data.itemId);
        row.innerHTML = `<td class="if-fixed-cell if-row-order">${esc(q('#if-orden').value)}</td>
            <td class="if-fixed-cell if-row-invoice">${esc(q('#if-numero').value || 'Pendiente')}</td>
            <td>${esc(data.pedido || '—')}</td><td>${esc(data.requisicion || '—')}</td>
            <td class="if-item-code"><strong>${esc(data.code)}</strong></td><td class="if-item-description">${esc(data.product)}</td>
            <td class="if-number">${data.quantity.toLocaleString('es-EC')}</td><td class="if-money">${money.format(data.price)}</td>
            <td class="if-money">${money.format(subtotal)}</td><td><span class="if-line-tax ${data.applies ? 'active' : ''}">${rateLabel}</span></td>
            <td class="if-money">${money.format(tax)}</td><td class="if-money"><strong>${money.format(total)}</strong></td>
            <td>${esc(data.reference || '—')}</td><td class="if-row-actions"><button class="if-row-edit" type="button" title="Editar producto"><i class="fa-solid fa-pen"></i></button><button class="if-row-remove" type="button" title="Quitar producto"><i class="fa-solid fa-trash"></i></button></td>
            <td class="if-line-hidden"><input type="hidden" name="items[${data.index}][item_id]" value="${data.itemId}"><input type="hidden" name="items[${data.index}][pedido]" value="${esc(data.pedido)}"><input type="hidden" name="items[${data.index}][requisicion]" value="${esc(data.requisicion)}"><input type="hidden" name="items[${data.index}][cantidad]" value="${data.quantity}"><input type="hidden" name="items[${data.index}][precio_unitario]" value="${data.price.toFixed(4)}"><input type="hidden" name="items[${data.index}][aplica_iva]" value="${data.applies}"><input type="hidden" name="items[${data.index}][iva_tipo_id]" value="${data.typeId || ''}"><input type="hidden" name="items[${data.index}][referencia]" value="${esc(data.reference)}"></td>`;
        row.querySelector('.if-row-edit').hidden = !editing;
        row.querySelector('.if-row-remove').hidden = !editing;
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
        q('#if-info-price').textContent = money.format(Number(data.price || 0));
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

    function summary() {
        const bases = { 0: 0 };
        cfg.tiposIva.forEach((type) => { bases[Number(type.tasa)] = bases[Number(type.tasa)] || 0; });
        let subtotal = 0;
        let tax = 0;
        let total = 0;
        qa('#if-lineas tr').forEach((row) => {
            const data = row._lineData;
            bases[data.rate] = (bases[data.rate] || 0) + data.subtotal;
            subtotal += data.subtotal;
            tax += data.tax;
            total += data.total;
        });
        q('#if-subtotal-general').textContent = money.format(subtotal);
        q('#if-bases').innerHTML = Object.keys(bases).sort((a, b) => Number(a) - Number(b)).map((rateValue) => {
            const rate = Number(rateValue);
            const label = rate === 0 ? 'Base 0%' : `Base IVA ${rate.toLocaleString('es-EC')}%`;
            return `<div class="if-summary-row ${Number(bases[rate]) > 0 ? 'if-summary-active' : ''}"><span>${label}</span><strong>${money.format(bases[rate])}</strong></div>`;
        }).join('');
        q('#if-total-iva').textContent = money.format(tax);
        q('#if-total').textContent = money.format(total);
        q('#if-empty-lines').hidden = qa('#if-lineas tr').length > 0;
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
        q('#if-product-editor').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function closeEditor() {
        q('#if-product-editor').classList.remove('active');
        resetDraft();
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
        q('#if-draft-price').value = Number(line?.price ?? product.precio_actual ?? 0).toFixed(4);
        const applies = line ? Number(line.applies) : Number(product.aplica_iva);
        q('#if-draft-applies').value = applies ? '1' : '0';
        q('#if-draft-tax').value = applies ? String(line?.typeId || defaultTaxType()?.id || '') : '';
        q('#if-draft-reference').value = line?.reference || '';
        q('#if-editor-title').textContent = editingRow ? 'Editar producto confirmado' : 'Complete los datos del producto';
        draftTotals();
    }

    function draftTotals() {
        const quantity = Math.max(0, Number(q('#if-draft-quantity').value || 0));
        const price = Math.max(0, Number(q('#if-draft-price').value || 0));
        const applies = q('#if-draft-applies').value === '1';
        q('#if-draft-tax').disabled = !applies;
        if (!applies) q('#if-draft-tax').value = '';
        if (applies && !q('#if-draft-tax').value) q('#if-draft-tax').value = defaultTaxType()?.id || '';
        const rate = applies ? Number(q('#if-draft-tax').selectedOptions[0]?.dataset.tasa || 0) : 0;
        const subtotal = quantity * price;
        const tax = Math.round(subtotal * rate) / 100;
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
                { data: null, render: (row) => `<div class="if-catalog-product"><strong>${esc(row.codigo)}</strong><span>${esc(row.nombre)}</span></div>` },
                { data: 'existencia', className: 'dt-body-center', render: (value) => `<span class="if-stock-badge">${Number(value || 0).toLocaleString('es-EC')}</span>` },
                { data: 'precio_actual', className: 'dt-body-right', render: (value) => `<strong class="if-db-value">${money.format(Number(value || 0))}</strong>` },
                { data: 'aplica_iva', className: 'dt-body-center', render: (value) => Number(value) ? '<span class="if-tax-badge yes">Aplica</span>' : '<span class="if-tax-badge no">No aplica</span>' },
                { data: null, orderable: false, searchable: false, className: 'dt-body-center', render: () => '<button type="button" class="if-select-product"><i class="fa-solid fa-arrow-right"></i> Seleccionar</button>' }
            ],
            language: {
                search: 'Buscar producto:',
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

    q('#if-editar').addEventListener('click', () => setEditing(true));
    q('#if-imprimir').addEventListener('click', () => window.print());
    q('#if-limpiar').addEventListener('click', () => {
        if (!editing) return;
        q('#if-form').reset();
        q('#if-fecha').value = cfg.hoy;
        q('#if-lineas').innerHTML = '';
        lineIndex = 0;
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
        if (!qa('#if-lineas tr').length) {
            event.preventDefault();
            window.alert('Agregue y confirme al menos un producto en la factura.');
        }
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

    async function extractPdfText(file) {
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
        if (text.replace(/\s/g, '').length >= 80) return text.trim();

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
            const nextHasQuantityAndPrices = /^.{0,18}\b\d+[.,]\d{2,4}\b.*\b\d+[.,]\d{2,4}\b.*\b\d+[.,]\d{2,4}\b/.test(next);
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
            const decimals = Array.from(line.matchAll(/\d+[.,]\d{2,4}/g));
            if (decimals.length < 3) return;
            const quantityToken = decimals[0];
            const code = line.slice(0, quantityToken.index).replace(/^[\s#|.-]+|[\s|.-]+$/g, '').trim();
            if (!code || /CANTIDAD|PRECIO|DESCRIPCI[ÓO]N/i.test(code)) return;
            const afterQuantityStart = quantityToken.index + quantityToken[0].length;
            const nextNumber = decimals[1];
            const description = line.slice(afterQuantityStart, nextNumber.index).replace(/^[\s|:.-]+|[\s|:.-]+$/g, '').trim();
            if (description.length < 3 || !/[A-ZÁÉÍÓÚÑ]/i.test(description)) return;
            const quantity = Math.max(1, scanNumber(quantityToken[0]));
            const price = Math.max(0, scanNumber(nextNumber[0]));
            const total = Math.max(0, scanNumber(decimals[decimals.length - 1][0]));
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
        const compact = (value) => normalizeScanText(value).replace(/\s/g, '');
        const candidateCode = compact(candidate.code);
        const rowCodes = [row.codigo, row.codigo_maestro, row.codigo_clasificacion].map(compact).filter(Boolean);
        if (candidateCode && rowCodes.some((code) => code === candidateCode)) return 120;
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
        const terms = [candidate.code.replace(/\s/g, ''), candidate.code, candidate.description.split(/\s+/).slice(0, 7).join(' ')].filter(Boolean);
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
                const status = line.product ? 'Listo' : (line.matches?.length ? `${line.matches.length} posible(s)` : 'No encontrado');
                const actions = line.product ? '<span></span>' : `<span class="if-scan-choice"><button type="button" class="if-scan-create" data-scan-review-product="${index}"><i class="fa-solid fa-magnifying-glass"></i> Revisar</button><button type="button" class="if-scan-create secondary" data-scan-create-product="${index}"><i class="fa-solid fa-plus"></i> Crear</button></span>`;
                return `<div class="if-scanned-line"><strong>${esc(line.code || line.product?.codigo || 'Sin código')}</strong><span>${esc(line.product?.nombre || line.description)}</span><span>${line.quantity.toLocaleString('es-EC')} u.</span><span>${money.format(unitPrice)}</span><span>${money.format(lineTotal)}</span><span class="${line.product ? 'matched' : 'unmatched'}">${status}</span>${actions}</div>`;
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
            const text = await extractPdfText(file);
            if (!text || text.replace(/\s/g, '').length < 20) throw new Error('El documento no contiene texto reconocible.');
            const header = scanHeader(text);
            const lines = await Promise.all(scanLineCandidates(text).map(matchScannedProduct));
            scannedData = { ...header, lines, text };
            renderScanReview(scannedData, file.name);
            const matched = lines.filter((line) => line.product).length;
            setScanStatus(`Lectura terminada: ${lines.length} producto(s) leídos y ${matched} relacionado(s) con inventario.`, 'success');
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

    function applyScan() {
        if (!scannedData || !editing) return;
        if (scannedData.invoice) q('#if-numero').value = scannedData.invoice;
        if (scannedData.date) q('#if-fecha').value = scannedData.date;
        if (scannedData.provider) q('#if-proveedor').value = String(scannedData.provider.id);
        if (scannedData.description) q('#if-descripcion').value = scannedData.description;
        if (scannedData.rate !== null) q('#if-iva-default').value = String(scannedData.rate);
        let added = 0;
        scannedData.lines.filter((line) => line.product).forEach((line) => {
            const applies = Number(line.product.aplica_iva) ? 1 : 0;
            const taxType = applies ? (ivaByRate(scannedData.rate ?? cfg.tasaPredeterminada) || defaultTaxType()) : null;
            const exists = qa('#if-lineas tr').some((row) => Number(row.dataset.itemId) === Number(line.product.id));
            if (!exists && addOrUpdateLine(line.product, { cantidad: line.quantity, precio_unitario: Number(line.price || line.product.precio_actual || 0), grava_iva: applies, iva_tipo_id: taxType?.id || '', iva_porcentaje: applies ? Number(taxType?.tasa || 0) : 0 })) added++;
        });
        updateRowDocumentData();
        q('#if-scan-review').classList.remove('active');
        setScanStatus(`Datos aplicados${added ? ` y ${added} producto(s) agregados` : ''}. Revise la factura antes de guardarla.`, 'success');
        scannedData = null;
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
