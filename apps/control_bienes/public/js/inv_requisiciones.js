(() => {
    const listConfigNode = document.getElementById('rq-list-config');
    if (listConfigNode && window.jQuery && $.fn?.DataTable) {
        const cfg = JSON.parse(listConfigNode.textContent); let loaded = false;
        const esc = value => $('<div>').text(value == null ? '' : String(value)).html();
        const dateIso = date => `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
        const setDates = type => { const today=new Date(`${cfg.hoy}T12:00:00`); let from='',to=''; if(type==='hoy')from=to=dateIso(today);if(type==='mes'){from=dateIso(new Date(today.getFullYear(),today.getMonth(),1));to=dateIso(today)}if(type==='anio'){from=dateIso(new Date(today.getFullYear(),0,1));to=dateIso(today)}document.getElementById('rq-date-from').value=from;document.getElementById('rq-date-to').value=to; };
        setDates('hoy');
        const table=$('#rq-history-table').DataTable({processing:true,serverSide:true,deferLoading:0,searchDelay:350,pageLength:10,lengthMenu:[[10,25,50,100],[10,25,50,100]],order:[[1,'desc']],responsive:true,
            ajax:{url:cfg.url,data:d=>{d.fecha_desde=document.getElementById('rq-date-from').value;d.fecha_hasta=document.getElementById('rq-date-to').value;d.estado=document.getElementById('rq-list-state').value}},
            columns:[
                {data:'secuencial',render:v=>`<strong class="wf-code">${esc(v)}</strong>`},{data:'fecha_solicitud'},{data:'centro_consumo'},{data:'motivo',defaultContent:'—'},
                {data:'total_productos',className:'dt-body-center'},{data:'total_solicitado',className:'dt-body-center'},{data:'total_entregado',className:'dt-body-center'},
                {data:'estado',render:v=>{const visible=v==='CANCELADA'?'ANULADA':['ENVIADA','EN_REVISION','DISPONIBLE','PARCIAL','SIN_EXISTENCIAS'].includes(v)?'PENDIENTE':v;const cls=['ATENDIDA','CERRADA'].includes(visible)?'done':visible==='ANULADA'?'inactive':'pending';return `<span class="wf-status ${cls}">${esc(visible)}</span>`}},
                {data:null,orderable:false,searchable:false,render:r=>{const visible=r.estado==='CANCELADA'?'ANULADA':['ENVIADA','EN_REVISION','DISPONIBLE','PARCIAL','SIN_EXISTENCIAS'].includes(r.estado)?'PENDIENTE':r.estado;let html=`<div class="rq-row-actions"><a class="btn-outline rq-icon-action" target="_blank" href="index.php?route=requisiciones&amp;action=imprimirRequisicion&amp;id=${Number(r.id_nota)}" title="Imprimir"><i class="fa-solid fa-print"></i></a>`;if(cfg.puedeAnular&&!['ANULADA','ATENDIDA','CERRADA'].includes(visible))html+=`<form action="index.php?route=requisiciones&amp;action=anularRequisicion" method="post" class="rq-cancel-form"><input type="hidden" name="csrf_token" value="${esc(cfg.csrf)}"><input type="hidden" name="nota_id" value="${Number(r.id_nota)}"><input type="hidden" name="motivo_anulacion"><button class="btn-outline rq-icon-action danger" type="submit" title="Anular"><i class="fa-solid fa-ban"></i></button></form>`;return html+'</div>'}}
            ],dom:'<"inv-dt-top"lf>rt<"inv-dt-footer"ip>',language:{search:'Buscar:',searchPlaceholder:'Número, producto, centro o detalle…',processing:'Consultando requisiciones…',lengthMenu:'Mostrar _MENU_',info:'Mostrando _START_ a _END_ de _TOTAL_',infoEmpty:'Sin registros',zeroRecords:'No se encontraron requisiciones.',paginate:{previous:'Anterior',next:'Siguiente'}}
        });
        table.on('draw',()=>{if(loaded)document.getElementById('rq-visible-count').innerHTML=`<i class="fa-solid fa-circle-check"></i> ${table.page.info().recordsDisplay} registro(s)`});
        const load=()=>{loaded=true;document.getElementById('rq-list-empty').hidden=true;document.getElementById('rq-table-shell').hidden=false;table.columns.adjust();if(table.responsive)table.responsive.recalc();const button=document.getElementById('rq-show-data');button.disabled=true;button.querySelector('span').textContent='Consultando…';table.ajax.reload(()=>{const info=table.page.info();document.getElementById('rq-visible-count').innerHTML=`<i class="fa-solid fa-circle-check"></i> ${info.recordsDisplay} registro(s)`;button.disabled=false;button.querySelector('span').textContent='Actualizar datos'},true)};
        document.getElementById('rq-show-data').onclick=load;
        document.querySelectorAll('[data-rq-period]').forEach(button=>button.onclick=()=>{document.querySelectorAll('[data-rq-period]').forEach(x=>x.classList.remove('active'));button.classList.add('active');setDates(button.dataset.rqPeriod);if(loaded)load()});
        ['rq-date-from','rq-date-to','rq-list-state'].forEach(id=>document.getElementById(id).onchange=()=>{if(loaded)load()});
        document.addEventListener('submit',event=>{const cancelForm=event.target.closest('.rq-cancel-form');if(!cancelForm)return;const reason=window.prompt('Indique el motivo de la anulación:');if(!reason||!reason.trim()){event.preventDefault();return}cancelForm.querySelector('[name="motivo_anulacion"]').value=reason.trim()});
    }

    const form = document.getElementById('rq-form');
    const lines = document.getElementById('rq-lines');
    const template = document.getElementById('rq-line-template');
    const results = document.getElementById('rq-product-results');
    if (!form || !lines || !template || !results) return;
    const centerGroup = document.getElementById('rq-center-group');
    const centerPerson = document.getElementById('rq-center-person');
    const noteNumber = document.getElementById('rq-note-number');
    const loadNoteButton = document.getElementById('rq-load-note');
    const noteSummary = document.getElementById('rq-note-summary');
    const initialNoteSummary = noteSummary ? noteSummary.innerHTML : '';

    let activeInput = null;
    let searchTimer = null;
    let searchController = null;
    const noteCache = new Map();
    const money = (value, decimals = 2) => '$' + Number(value || 0).toFixed(decimals);

    function showNoteSummary(type = '', title = '', detail = '') {
        if (!noteSummary) return;
        noteSummary.className = 'rq-multi-note-help' + (type ? ' ' + type : '');
        if (!title) {
            noteSummary.innerHTML = initialNoteSummary;
            return;
        }
        const icon = document.createElement('i');
        icon.className = 'fa-solid ' + (type === 'success' ? 'fa-circle-check' : type === 'warning' ? 'fa-triangle-exclamation' : 'fa-spinner fa-spin');
        const copy = document.createElement('div');
        const strong = document.createElement('strong');
        const span = document.createElement('span');
        strong.textContent = title;
        span.textContent = detail;
        copy.append(strong, span);
        noteSummary.replaceChildren(icon, copy);
    }

    function selectOption(select, value, name = '') {
        if (!select) return false;
        let option = value ? [...select.options].find(item => String(item.value) === String(value)) : null;
        if (!option && name) {
            const normalized = name.trim().toLocaleLowerCase();
            option = [...select.options].find(item => String(item.dataset.nombre || item.textContent.split('·')[0]).trim().toLocaleLowerCase() === normalized);
        }
        if (!option || option.disabled) return false;
        select.value = option.value;
        select.dispatchEvent(new Event('change', {bubbles: true}));
        return true;
    }

    function filterCenterPeople(keepSelection = false) {
        if (!centerGroup || !centerPerson) return;
        const groupId = String(centerGroup.value || '');
        const selected = String(centerPerson.value || '');
        let selectedIsValid = false;
        [...centerPerson.options].forEach(option => {
            if (!option.value) return;
            const matches = groupId !== '' && String(option.dataset.unidad || '') === groupId;
            option.hidden = !matches;
            option.disabled = !matches;
            if (matches && String(option.value) === selected) selectedIsValid = true;
        });
        if (!keepSelection || !selectedIsValid) centerPerson.value = '';
        centerPerson.options[0].textContent = groupId ? 'Escriba para buscar…' : 'Seleccione primero el centro…';
        centerPerson.dispatchEvent(new Event('change', {bubbles: true}));
    }

    if (centerGroup && centerPerson) {
        centerGroup.addEventListener('change', () => filterCenterPeople());
        filterCenterPeople(true);
    }

    function resetForm() {
        form.reset();
        filterCenterPeople();
        lines.innerHTML = '';
        showNoteSummary();
        addLine();
    }

    async function lookupHeaderNote() {
        if (!noteNumber || !loadNoteButton) return;
        if (loadNoteButton.disabled) return;
        const number = noteNumber.value.trim();
        if (!number) {
            showNoteSummary('warning', 'Ingrese el número de la nota', 'Puede escribir el código completo o solamente su parte numérica.');
            noteNumber.focus();
            return;
        }

        loadNoteButton.disabled = true;
        showNoteSummary('', 'Consultando nota de pedido…', 'Espere mientras se recuperan la cabecera y todos sus productos.');
        try {
            const key = number.toLocaleUpperCase();
            let data = noteCache.get(key);
            if (!data) {
                const response = await fetch('index.php?route=requisiciones&action=buscarNotaPedidoRequisicion&numero=' + encodeURIComponent(number));
                if (!response.ok) throw new Error('No fue posible consultar la nota.');
                data = await response.json();
                if (data.encontrada) noteCache.set(key, data);
            }
            if (!data.encontrada || !data.nota) {
                showNoteSummary('warning', 'Nota no encontrada', data.mensaje || 'Revise el número ingresado o complete la requisición manualmente.');
                return;
            }

            const note = data.nota;
            const details = Array.isArray(note.detalles) ? note.detalles : [];
            noteNumber.value = note.numero || number;
            const dateInput = form.querySelector('[name="fecha_solicitud"]');
            const reasonInput = form.querySelector('[name="motivo"]');
            const observationsInput = document.getElementById('rq-observations');
            if (dateInput && note.fecha) dateInput.value = note.fecha;
            if (reasonInput && note.motivo) reasonInput.value = note.motivo;
            if (observationsInput) observationsInput.value = note.observaciones || '';

            if (centerGroup && note.centro_unidad_id) {
                selectOption(centerGroup, note.centro_unidad_id);
                filterCenterPeople(true);
            }
            if (centerPerson && note.centro_persona_id) selectOption(centerPerson, note.centro_persona_id);

            const priority = document.getElementById('rq-priority');
            const priorityMatch = String(note.observaciones || '').match(/Prioridad:\s*(Normal|Urgente|Cr[ií]tica)/i);
            if (priority && priorityMatch) {
                const normalized = priorityMatch[1].toLocaleLowerCase();
                const option = [...priority.options].find(item => item.textContent.toLocaleLowerCase() === normalized);
                if (option) priority.value = option.value;
            }

            lines.innerHTML = '';
            if (details.length) {
                details.forEach(detail => addLine({
                    ...detail,
                    pedido_numero: note.numero || number,
                    pedido_fecha: note.fecha || '',
                    referencia: detail.observacion_bodega || note.referencia || '',
                    nota_cargada: true,
                }));
            } else {
                addLine({pedido_numero: note.numero || number, pedido_fecha: note.fecha || '', nota_cargada: true});
            }

            const context = [
                note.centro_consumo ? 'Centro: ' + note.centro_consumo : '',
                note.estado ? 'Estado: ' + note.estado : '',
            ].filter(Boolean).join(' · ');
            showNoteSummary('success', 'Nota ' + (note.numero || number) + ' cargada correctamente',
                details.length + ' producto' + (details.length === 1 ? '' : 's') + (context ? ' · ' + context : '') + '. Revise la información antes de guardar.');
        } catch (error) {
            showNoteSummary('warning', 'No fue posible consultar la nota', 'La requisición puede completarse manualmente o puede intentar nuevamente.');
        } finally {
            loadNoteButton.disabled = false;
        }
    }

    function refresh() {
        let total = 0;
        [...lines.children].forEach((row, index) => {
            const quantity = Math.max(0, Number(row.querySelector('.rq-quantity').value || 0));
            const price = Number(row.dataset.price || 0);
            row.querySelector('.rq-row-number').textContent = index + 1;
            row.querySelector('.rq-subtotal').textContent = money(quantity * price);
            row.querySelector('.rq-item-id').name = `items[${index}][item_id]`;
            row.querySelector('.rq-quantity').name = `items[${index}][cantidad]`;
            row.querySelector('.rq-reference').name = `items[${index}][referencia]`;
            row.querySelector('.rq-other-reference').name = `items[${index}][otra_referencia]`;
            row.querySelector('.rq-order-number').name = `items[${index}][pedido_numero]`;
            row.querySelector('.rq-order-date').name = `items[${index}][pedido_fecha]`;
            total += quantity * price;
        });
        document.getElementById('rq-total').textContent = money(total);
        document.getElementById('rq-line-count').textContent = lines.children.length + ' producto' + (lines.children.length === 1 ? '' : 's');
    }

    function setRowNoteState(row, type = '', message = '') {
        const state = row.querySelector('.rq-row-note-state');
        state.className = 'rq-row-note-state' + (type ? ' ' + type : '');
        state.title = message;
        state.innerHTML = type === 'loading'
            ? '<i class="fa-solid fa-spinner fa-spin"></i>'
            : type === 'success'
                ? '<i class="fa-solid fa-circle-check"></i>'
                : type === 'warning'
                    ? '<i class="fa-solid fa-triangle-exclamation"></i>'
                    : '';
    }

    function addLine(data = {}, afterRow = null) {
        const row = template.content.firstElementChild.cloneNode(true);
        const productSearch = row.querySelector('.rq-product-search');
        const noteInput = row.querySelector('.rq-order-number');
        const noteSearch = row.querySelector('.rq-row-note-search');

        row.querySelector('.rq-remove').onclick = () => {
            row.remove();
            if (!lines.children.length) addLine();
            else refresh();
        };
        row.querySelector('.rq-quantity').oninput = refresh;

        productSearch.addEventListener('input', () => {
            row.querySelector('.rq-item-id').value = '';
            row.dataset.price = '0';
            row.querySelector('.rq-product-code').textContent = '—';
            row.querySelector('.rq-average').textContent = money(0, 4);
            row.querySelector('.rq-stock').textContent = '0';
            queueProductSearch(productSearch);
            refresh();
        });
        productSearch.addEventListener('focus', () => {
            if (productSearch.value.trim().length >= 2) queueProductSearch(productSearch);
        });
        productSearch.addEventListener('keydown', event => {
            if (event.key === 'Escape') hideResults();
            if (event.key === 'Enter') {
                const first = results.querySelector('.rq-product-option');
                if (first) {
                    event.preventDefault();
                    first.click();
                }
            }
        });

        noteInput.addEventListener('input', () => {
            if (row.dataset.noteLoaded && !row.dataset.notePrevious) {
                row.dataset.notePrevious = row.dataset.noteLoaded;
            }
            delete row.dataset.noteLoaded;
            setRowNoteState(row);
        });
        noteInput.addEventListener('blur', () => lookupRowNote(row));
        noteInput.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                lookupRowNote(row);
            }
        });
        noteSearch.addEventListener('mousedown', event => event.preventDefault());
        noteSearch.addEventListener('click', () => lookupRowNote(row));

        if (afterRow && afterRow.parentElement === lines) afterRow.after(row);
        else lines.appendChild(row);
        if (data.item_id) selectProduct(row, data);
        row.querySelector('.rq-order-number').value = data.pedido_numero || '';
        row.querySelector('.rq-order-date').value = data.pedido_fecha || '';
        row.querySelector('.rq-reference').value = data.referencia || '';
        row.querySelector('.rq-other-reference').value = data.otra_referencia || '';
        row.querySelector('.rq-quantity').value = Number(data.cantidad || data.cantidad_solicitada || 1);
        if (data.nota_cargada) {
            row.dataset.noteLoaded = String(data.pedido_numero || '').toLocaleUpperCase();
            row.dataset.noteAutoLoaded = '1';
            setRowNoteState(row, 'success', 'Nota encontrada y datos completados.');
        }
        refresh();
        return row;
    }

    function selectProduct(row, product) {
        row.dataset.price = String(Number(product.precio_promedio || 0));
        row.dataset.stock = String(Number(product.existencia || 0));
        row.querySelector('.rq-item-id').value = product.item_id || product.id || '';
        const code = product.item_codigo || product.codigo || product.item_secuencial || product.secuencial || '';
        row.querySelector('.rq-product-search').value = (code + ' · ' + (product.item_nombre || product.nombre || '')).replace(/^ · /, '');
        row.querySelector('.rq-product-code').textContent = code || '—';
        row.querySelector('.rq-average').textContent = money(product.precio_promedio, 4);
        row.querySelector('.rq-stock').textContent = Number(product.existencia || 0);
        row.querySelector('.rq-product-search').setCustomValidity('');
        hideResults();
        refresh();
    }

    function clearRowProduct(row) {
        row.dataset.price = '0';
        row.dataset.stock = '0';
        row.querySelector('.rq-item-id').value = '';
        row.querySelector('.rq-product-search').value = '';
        row.querySelector('.rq-product-code').textContent = '—';
        row.querySelector('.rq-average').textContent = money(0, 4);
        row.querySelector('.rq-stock').textContent = '0';
    }

    async function lookupRowNote(row) {
        const input = row.querySelector('.rq-order-number');
        const button = row.querySelector('.rq-row-note-search');
        const number = input.value.trim();
        if (!number) {
            delete row.dataset.noteLoaded;
            setRowNoteState(row);
            return;
        }
        const key = number.toLocaleUpperCase();
        if (row.dataset.noteLoaded === key) return;
        button.disabled = true;
        setRowNoteState(row, 'loading', 'Consultando nota…');

        try {
            let data = noteCache.get(key);
            if (!data) {
                const response = await fetch('index.php?route=requisiciones&action=buscarNotaPedidoRequisicion&numero=' + encodeURIComponent(number));
                if (!response.ok) throw new Error('No fue posible consultar la nota.');
                data = await response.json();
                if (data.encontrada) noteCache.set(key, data);
            }
            if (!data.encontrada || !data.nota) {
                setRowNoteState(row, 'warning', data.mensaje || 'Nota no registrada. Puede completar la fila manualmente.');
                return;
            }

            const note = data.nota;
            const details = note.detalles || [];
            const previousKey = row.dataset.notePrevious || '';
            const replacingLoadedNote = row.dataset.noteAutoLoaded === '1' || previousKey !== '';
            const base = {
                pedido_numero: note.numero || number,
                pedido_fecha: note.fecha || '',
                nota_cargada: true,
            };
            input.value = base.pedido_numero;
            row.querySelector('.rq-order-date').value = base.pedido_fecha;
            if (centerGroup && note.centro_unidad_id) {
                centerGroup.value = String(note.centro_unidad_id);
                centerGroup.dispatchEvent(new Event('change', {bubbles: true}));
                if (centerPerson && note.centro_persona_id) {
                    centerPerson.value = String(note.centro_persona_id);
                    centerPerson.dispatchEvent(new Event('change', {bubbles: true}));
                }
            }

            if (replacingLoadedNote && previousKey) {
                [...lines.children].forEach(candidateRow => {
                    if (candidateRow !== row
                        && candidateRow.dataset.noteAutoLoaded === '1'
                        && candidateRow.dataset.noteLoaded === previousKey) candidateRow.remove();
                });
            }

            const rowIsEmpty = !row.querySelector('.rq-item-id').value;
            if ((rowIsEmpty || replacingLoadedNote) && details.length) {
                const first = details[0];
                selectProduct(row, first);
                row.querySelector('.rq-quantity').value = Number(first.cantidad_solicitada || 1);
                row.querySelector('.rq-reference').value = first.observacion_bodega || note.referencia || '';
                row.querySelector('.rq-other-reference').value = '';
                let previousRow = row;
                details.slice(1).forEach(detail => {
                    previousRow = addLine({
                        ...detail,
                        ...base,
                        referencia: detail.observacion_bodega || note.referencia || '',
                    }, previousRow);
                });
            } else if (replacingLoadedNote && !details.length) {
                clearRowProduct(row);
                row.querySelector('.rq-quantity').value = 1;
                row.querySelector('.rq-reference').value = note.referencia || '';
                row.querySelector('.rq-other-reference').value = '';
            } else if (!row.querySelector('.rq-reference').value) {
                row.querySelector('.rq-reference').value = note.referencia || '';
            }

            row.dataset.noteLoaded = String(base.pedido_numero).toLocaleUpperCase();
            row.dataset.noteAutoLoaded = '1';
            delete row.dataset.notePrevious;
            setRowNoteState(row, 'success', details.length
                ? 'Nota encontrada. Se completaron sus datos y productos.'
                : 'Nota encontrada. Se completaron sus datos.');
            refresh();
        } catch (error) {
            setRowNoteState(row, 'warning', 'No fue posible consultar la nota. Puede completar la fila manualmente.');
        } finally {
            button.disabled = false;
        }
    }

    function queueProductSearch(input) {
        activeInput = input;
        clearTimeout(searchTimer);
        const query = input.value.trim();
        if (query.length < 2) {
            hideResults();
            return;
        }
        searchTimer = setTimeout(() => searchProducts(input, query), 220);
    }

    async function searchProducts(input, query) {
        if (searchController) searchController.abort();
        searchController = new AbortController();
        showResults(input, [{loading: true}]);
        try {
            const response = await fetch('index.php?route=requisiciones&action=buscarProductosRequisicion&q=' + encodeURIComponent(query), {signal: searchController.signal});
            const data = await response.json();
            if (activeInput !== input) return;
            showResults(input, data.productos || []);
        } catch (error) {
            if (error.name !== 'AbortError') showResults(input, []);
        }
    }

    function showResults(input, products) {
        activeInput = input;
        results.innerHTML = '';
        const rect = input.getBoundingClientRect();
        const width = Math.min(Math.max(rect.width, 430), window.innerWidth - 24);
        results.style.width = width + 'px';
        results.style.left = Math.max(12, Math.min(rect.left, window.innerWidth - width - 12)) + 'px';
        results.style.top = Math.max(12, Math.min(rect.bottom + 5, window.innerHeight - 290)) + 'px';
        if (products[0]?.loading) {
            results.innerHTML = '<div class="rq-product-empty"><i class="fa-solid fa-spinner fa-spin"></i> Buscando productos…</div>';
        } else if (!products.length) {
            results.innerHTML = '<div class="rq-product-empty">No se encontraron productos.</div>';
        } else {
            products.forEach(product => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'rq-product-option';
                const copy = document.createElement('div');
                const title = document.createElement('strong');
                const detail = document.createElement('small');
                const stock = document.createElement('span');
                title.textContent = (product.codigo || product.secuencial || '') + ' · ' + (product.nombre || '');
                detail.textContent = [product.codigo_interno ? 'Interno '+product.codigo_interno : '', product.codigo_clasificacion ? 'Clasif. '+product.codigo_clasificacion : '', product.grupo_nombre || 'Sin grupo', product.marca || '', product.unidad_abrev || product.unidad_nombre || ''].filter(Boolean).join(' · ');
                stock.textContent = 'Existencia ' + Number(product.existencia || 0) + '\n' + money(product.precio_promedio, 4);
                copy.append(title, detail);
                button.append(copy, stock);
                button.onclick = () => selectProduct(input.closest('tr'), product);
                results.appendChild(button);
            });
        }
        results.classList.add('active');
    }

    function hideResults() {
        results.classList.remove('active');
        results.innerHTML = '';
        activeInput = null;
    }

    document.getElementById('rq-reset-form').onclick = resetForm;
    document.getElementById('rq-add-line').onclick = () => addLine();
    if (loadNoteButton && noteNumber) {
        loadNoteButton.addEventListener('click', lookupHeaderNote);
        noteNumber.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                lookupHeaderNote();
            }
        });
        noteNumber.addEventListener('input', () => showNoteSummary());
    }
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && results.classList.contains('active')) hideResults();
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('.rq-product-results') && !event.target.closest('.rq-product-input')) hideResults();
    });
    document.addEventListener('scroll', hideResults, true);
    window.addEventListener('resize', hideResults);

    form.addEventListener('submit', event => {
        const rows = [...lines.children];
        let invalid = null;
        rows.forEach(row => {
            const id = row.querySelector('.rq-item-id').value;
            const search = row.querySelector('.rq-product-search');
            const quantity = Number(row.querySelector('.rq-quantity').value || 0);
            const stock = Number(row.dataset.stock || 0);
            const message = !id
                ? 'Seleccione un producto desde los resultados de búsqueda.'
                : quantity <= 0
                    ? 'Ingrese una cantidad mayor que cero.'
                    : quantity > stock
                        ? 'La cantidad supera la existencia disponible (' + stock + ').'
                        : '';
            search.setCustomValidity(message);
            if (message && !invalid) invalid = search;
        });
        if (invalid) {
            event.preventDefault();
            invalid.reportValidity();
            invalid.focus();
        }
    });

    resetForm();
})();
