(() => {
    const normalize = value => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase();
    const money = value => window.InvMoney.formatAmount(value);
    const priceMoney = value => window.InvMoney.formatPrice(value);

    const listConfigNode=document.getElementById('oc-list-config');
    if(listConfigNode&&document.getElementById('oc-history-table')&&window.jQuery&&$.fn?.DataTable){
        const cfg=JSON.parse(listConfigNode.textContent);let loaded=false;const esc=value=>$('<div>').text(value==null?'':String(value)).html();
        const iso=date=>`${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
        const setDates=type=>{const today=new Date(`${cfg.hoy}T12:00:00`);let from='',to='';if(type==='hoy')from=to=iso(today);if(type==='mes'){from=iso(new Date(today.getFullYear(),today.getMonth(),1));to=iso(today)}if(type==='anio'){from=iso(new Date(today.getFullYear(),0,1));to=iso(today)}document.getElementById('oc-date-from').value=from;document.getElementById('oc-date-to').value=to};setDates('hoy');
        const table=$('#oc-history-table').DataTable({processing:true,serverSide:true,deferLoading:0,searchDelay:350,pageLength:10,lengthMenu:[[10,25,50,100],[10,25,50,100]],order:[[3,'desc']],responsive:true,
            ajax:{url:cfg.url,data:d=>{d.fecha_desde=document.getElementById('oc-date-from').value;d.fecha_hasta=document.getElementById('oc-date-to').value;d.estado=document.getElementById('oc-list-state').value}},
            columns:[
                {data:'secuencial',render:v=>`<strong class="wf-code">${esc(v)}</strong>`},
                {data:null,render:r=>esc(r.origen==='FACTURA'?'Factura directa':r.requisicion_secuencial?'Requisición '+r.requisicion_secuencial:'Orden directa')},
                {data:'proveedor',render:(v,_t,r)=>`${esc(v)}<small>${esc(r.proveedor_ruc||'')}</small>`},{data:'fecha'},{data:'total_lineas',className:'dt-body-center'},
                {data:'subtotal',className:'dt-body-right',render:v=>money(v)},{data:'iva',className:'dt-body-right',render:v=>money(v)},{data:null,className:'dt-body-right',render:r=>`<strong>${money(Number(r.subtotal||0)+Number(r.iva||0))}</strong>`},
                {data:'estado',render:v=>`<span class="wf-status ${['APROBADA','CERRADA'].includes(v)?'done':v==='PENDIENTE'?'pending':'inactive'}">${esc(v)}</span>`},
                {data:null,orderable:false,searchable:false,render:r=>{if(r.estado==='PENDIENTE'&&cfg.puedeEditar){return `<div class="oc-actions"><a class="btn-outline" href="index.php?route=ordenes_compra&amp;action=editarOrdenCompraForm&amp;id=${Number(r.id_orden)}"><i class="fa-solid fa-pen"></i> Editar</a><form action="index.php?route=ordenes_compra&amp;action=aprobarOrdenCompra" method="post" onsubmit="return confirm('¿Aprobar esta orden de compra?')"><input type="hidden" name="csrf_token" value="${esc(cfg.csrf)}"><input type="hidden" name="orden_id" value="${Number(r.id_orden)}"><button class="btn-primary" ${cfg.periodoActivo?'':'disabled'}><i class="fa-solid fa-check"></i> Aprobar</button></form></div>`}if(r.estado==='APROBADA')return `<a class="btn-primary" href="index.php?route=ingresos&amp;action=facturaIngreso&amp;orden_id=${Number(r.id_orden)}"><i class="fa-solid fa-file-invoice"></i> Factura</a>`;return '—'}}
            ],dom:'<"inv-dt-top"lf>rt<"inv-dt-footer"ip>',language:{search:'Buscar:',searchPlaceholder:'Orden, proveedor o detalle…',processing:'Consultando órdenes…',lengthMenu:'Mostrar _MENU_',info:'Mostrando _START_ a _END_ de _TOTAL_',infoEmpty:'Sin registros',zeroRecords:'No se encontraron órdenes.',paginate:{previous:'Anterior',next:'Siguiente'}}
        });
        table.on('draw',()=>{if(loaded)document.getElementById('oc-visible-count').innerHTML=`<i class="fa-solid fa-circle-check"></i> ${table.page.info().recordsDisplay} registro(s)`});
        const load=()=>{loaded=true;document.getElementById('oc-list-empty').hidden=true;document.getElementById('oc-table-shell').hidden=false;table.columns.adjust();if(table.responsive)table.responsive.recalc();const button=document.getElementById('oc-show-data');button.disabled=true;button.querySelector('span').textContent='Consultando…';table.ajax.reload(()=>{document.getElementById('oc-visible-count').innerHTML=`<i class="fa-solid fa-circle-check"></i> ${table.page.info().recordsDisplay} registro(s)`;button.disabled=false;button.querySelector('span').textContent='Actualizar datos'},true)};
        document.getElementById('oc-show-data').onclick=load;document.querySelectorAll('[data-oc-period]').forEach(button=>button.onclick=()=>{document.querySelectorAll('[data-oc-period]').forEach(x=>x.classList.remove('active'));button.classList.add('active');setDates(button.dataset.ocPeriod);if(loaded)load()});['oc-date-from','oc-date-to','oc-list-state'].forEach(id=>document.getElementById(id).onchange=()=>{if(loaded)load()});
    }

    const form = document.getElementById('oc-form');
    if (!form) return;
    const config = window.ocOrderConfig || {iva: 0, requisiciones: {}, detalles: []};
    const lines = document.getElementById('oc-lines');
    const template = document.getElementById('oc-line-template');
    const results = document.getElementById('oc-product-results');
    let activeInput = null;
    let timer = null;
    let controller = null;

    function refresh() {
        let subtotal = 0, base0 = 0, taxBase = 0, tax = 0;
        [...lines.children].forEach((row, index) => {
            const quantity = Math.max(0, Number(row.querySelector('.oc-quantity').value || 0));
            const price = Math.max(0, Number(row.querySelector('.oc-price').value || 0));
            const base = window.InvMoney.roundAmount(quantity * price);
            const taxed = row.querySelector('.oc-grava').value === '1';
            const rate = taxed ? Number(row.querySelector('.oc-rate').value || config.iva || 0) : 0;
            const lineTax = window.InvMoney.roundAmount(base * rate / 100);
            subtotal += base;
            if (taxed) taxBase += base; else base0 += base;
            tax += lineTax;
            row.querySelector('.oc-row-number').textContent = index + 1;
            row.querySelector('.oc-line-subtotal').textContent = money(base);
            row.querySelector('.oc-line-tax').textContent = money(lineTax);
            row.querySelector('.oc-line-total').textContent = money(base + lineTax);
            const fields = {
                '.oc-item-id': 'item_id', '.oc-order-note': 'pedido_numero', '.oc-requisition-number': 'requisicion_numero',
                '.oc-reference': 'referencia', '.oc-quantity': 'cantidad', '.oc-price': 'precio', '.oc-grava': 'grava_iva',
                '.oc-rate': 'iva_porcentaje', '.oc-specifications': 'especificaciones_tecnicas'
            };
            Object.entries(fields).forEach(([selector, name]) => row.querySelector(selector).name = `items[${index}][${name}]`);
        });
        document.getElementById('oc-subtotal').textContent = money(subtotal);
        document.getElementById('oc-base0').textContent = money(base0);
        document.getElementById('oc-tax-base').textContent = money(taxBase);
        document.getElementById('oc-tax').textContent = money(tax);
        document.getElementById('oc-total').textContent = money(subtotal + tax);
    }

    function addLine(data = {}) {
        const row = template.content.firstElementChild.cloneNode(true);
        const search = row.querySelector('.oc-product-search');
        lines.appendChild(row);
        row.querySelector('.oc-remove').onclick = () => {
            row.remove();
            if (!lines.children.length) addLine(); else refresh();
        };
        row.querySelectorAll('.oc-quantity,.oc-price,.oc-grava').forEach(control => control.addEventListener('input', refresh));
        search.addEventListener('input', () => {
            row.querySelector('.oc-item-id').value = '';
            row.querySelector('.oc-item-code').textContent = '—';
            queueSearch(search);
        });
        search.addEventListener('focus', () => { if (search.value.trim().length >= 2) queueSearch(search); });
        search.addEventListener('keydown', event => {
            if (event.key === 'Escape') hideResults();
            if (event.key === 'Enter') {
                const first = results.querySelector('.oc-product-option');
                if (first) { event.preventDefault(); first.click(); }
            }
        });
        row.querySelector('.oc-order-note').value = data.pedido_numero || '';
        row.querySelector('.oc-requisition-number').value = data.requisicion_numero || '';
        row.querySelector('.oc-reference').value = data.referencia || data.observacion_bodega || '';
        row.querySelector('.oc-quantity').value = Number(data.cantidad || data.cantidad_pendiente || data.cantidad_solicitada || 1);
        row.querySelector('.oc-price').value = window.InvMoney.roundPrice(data.precio_unitario_estimado ?? data.precio_promedio ?? 0).toFixed(window.InvMoney.config.priceDecimals);
        window.InvMoney.applyPriceInput(row.querySelector('.oc-price'));
        row.querySelector('.oc-grava').value = String(data.grava_iva === undefined ? 1 : Number(data.grava_iva));
        row.querySelector('.oc-rate').value = Number(data.iva_porcentaje ?? config.iva ?? 0);
        row.querySelector('.oc-specifications').value = data.especificaciones_tecnicas || '';
        if (data.item_id) selectProduct(row, data);
        refresh();
        return row;
    }

    function selectProduct(row, product) {
        const id = product.item_id || product.id || '';
        const code = product.item_codigo || product.codigo || product.item_secuencial || product.secuencial || '';
        const name = product.item_nombre || product.nombre || '';
        row.querySelector('.oc-item-id').value = id;
        row.querySelector('.oc-item-code').textContent = code || '—';
        row.querySelector('.oc-product-search').value = (code ? code + ' · ' : '') + name;
        row.querySelector('.oc-product-search').setCustomValidity('');
        if (!row.querySelector('.oc-price').value || Number(row.querySelector('.oc-price').value) === 0) {
            row.querySelector('.oc-price').value = window.InvMoney.roundPrice(product.precio_promedio || 0).toFixed(window.InvMoney.config.priceDecimals);
        }
        hideResults();
        refresh();
    }

    function queueSearch(input) {
        activeInput = input;
        clearTimeout(timer);
        const query = input.value.trim();
        if (query.length < 2) return hideResults();
        timer = setTimeout(() => searchProducts(input, query), 220);
    }

    async function searchProducts(input, query) {
        if (controller) controller.abort();
        controller = new AbortController();
        showResults(input, [{loading: true}]);
        try {
            const response = await fetch('index.php?route=ordenes_compra&action=buscarProductosRequisicion&q=' + encodeURIComponent(query), {signal: controller.signal});
            const data = await response.json();
            if (activeInput === input) showResults(input, data.productos || []);
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
        if (products[0]?.loading) results.innerHTML = '<div class="oc-product-empty"><i class="fa-solid fa-spinner fa-spin"></i> Buscando productos…</div>';
        else if (!products.length) results.innerHTML = '<div class="oc-product-empty">No se encontraron productos.</div>';
        else products.forEach(product => {
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'oc-product-option';
            const copy = document.createElement('div'); const title = document.createElement('strong'); const detail = document.createElement('small'); const stock = document.createElement('span');
            title.textContent = (product.codigo || product.secuencial || '') + ' · ' + (product.nombre || '');
            detail.textContent = [product.codigo_interno ? 'Interno '+product.codigo_interno : '', product.codigo_clasificacion ? 'Clasif. '+product.codigo_clasificacion : '', product.grupo_nombre || 'Sin grupo', product.marca || '', product.unidad_abrev || product.unidad_nombre || ''].filter(Boolean).join(' · ');
            stock.textContent = 'Existencia ' + Number(product.existencia || 0) + '\n' + priceMoney(product.precio_promedio);
            copy.append(title, detail); button.append(copy, stock);
            button.onclick = () => selectProduct(input.closest('tr'), product);
            results.appendChild(button);
        });
        results.classList.add('active');
    }
    function hideResults() { results.classList.remove('active'); results.innerHTML = ''; activeInput = null; }

    function loadRequisition(id) {
        const requisition = config.requisiciones?.[id];
        if (!requisition) return;
        const hasData = [...lines.querySelectorAll('.oc-item-id')].some(input => input.value);
        if (hasData && !confirm('Se reemplazarán los productos actuales con los de la requisición. ¿Continuar?')) {
            document.getElementById('oc-requisition').value = '';
            document.getElementById('oc-requisition').dispatchEvent(new Event('change', {bubbles: true}));
            return;
        }
        lines.innerHTML = '';
        (requisition.detalles || []).forEach(detail => {
            const reference = detail.observacion_bodega || '';
            const orderMatch = reference.match(/Pedido:\s*([^|]+)/i);
            addLine({...detail, requisicion_numero: requisition.secuencial || '', pedido_numero: orderMatch ? orderMatch[1].trim() : '', referencia: reference});
        });
        if (!lines.children.length) addLine({requisicion_numero: requisition.secuencial || ''});
    }

    async function saveProvider() {
        const status = document.getElementById('oc-provider-status');
        const name = form.elements.quick_nombre.value.trim();
        if (!name) { status.textContent = 'Indique la razón social.'; status.className = 'error'; return; }
        const data = new FormData();
        data.set('csrf_token', config.csrf || '');
        ['nombre','ruc','representante','telefono1','email','ciudad','direccion'].forEach(field => data.set(field, form.elements['quick_' + field].value.trim()));
        const button = document.getElementById('oc-save-provider');
        button.disabled = true; status.className = ''; status.textContent = 'Guardando proveedor…';
        try {
            const response = await fetch('index.php?route=ordenes_compra&action=crearProveedorRapido', {method: 'POST', body: data});
            const result = await response.json();
            if (!response.ok || !result.proveedor) throw new Error(result.error || 'No fue posible guardar el proveedor.');
            const provider = result.proveedor;
            const select = document.getElementById('oc-provider');
            const option = new Option(((provider.codigo ? provider.codigo + ' · ' : '') + provider.nombre + (provider.ruc ? ' · ' + provider.ruc : '')), provider.id, true, true);
            select.add(option); select.dispatchEvent(new Event('change', {bubbles: true}));
            document.getElementById('oc-quick-provider').hidden = true;
            status.textContent = '';
        } catch (error) { status.textContent = error.message; status.className = 'error'; }
        finally { button.disabled = false; }
    }

    document.getElementById('oc-add-line').onclick = () => addLine();
    document.getElementById('oc-requisition').addEventListener('change', event => { if (event.target.value) loadRequisition(event.target.value); });
    document.getElementById('oc-show-provider').onclick = () => document.getElementById('oc-quick-provider').hidden = false;
    document.getElementById('oc-hide-provider').onclick = () => document.getElementById('oc-quick-provider').hidden = true;
    document.getElementById('oc-save-provider').onclick = saveProvider;
    document.addEventListener('click', event => { if (!event.target.closest('.oc-product-results') && !event.target.closest('.oc-product-cell')) hideResults(); });
    document.addEventListener('scroll', hideResults, true);
    window.addEventListener('resize', hideResults);
    form.addEventListener('submit', event => {
        const ids = []; let invalid = null;
        [...lines.children].forEach(row => {
            const id = row.querySelector('.oc-item-id').value;
            const search = row.querySelector('.oc-product-search');
            search.setCustomValidity(id ? '' : 'Seleccione un producto desde los resultados.');
            if (!id && !invalid) invalid = search;
            if (id) ids.push(id);
        });
        if (invalid || new Set(ids).size !== ids.length) {
            event.preventDefault();
            if (invalid) { invalid.reportValidity(); invalid.focus(); }
            else alert('No repita productos en la misma orden de compra.');
        }
    });

    if (config.detalles?.length) config.detalles.forEach(addLine); else addLine();
})();
