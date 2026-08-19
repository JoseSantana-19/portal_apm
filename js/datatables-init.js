/**
 * Inicializador único de DataTables 3.0.1 para todas las tablas de Central.
 *
 * `var` a nivel superior, no `const`/`let`: el sidebar/main.js re-ejecuta
 * scripts inline en navegación SPA sin recarga completa -- const/let acá
 * revienta "Identifier already declared" en la segunda pasada (mismo
 * gotcha ya documentado y corregido varias veces este proyecto).
 *
 * Uso: <table data-dt data-dt-order="1,desc" data-dt-cols-noorder="4,5">...
 * data-dt-order        = "columna,dir" inicial (default: sin orden extra, respeta el HTML)
 * data-dt-cols-noorder = lista de índices de columna (0-based) sin orden ni búsqueda (ej: acciones
 *                        que tenga <a>/<button> simples, SIN onclick con JSON -- ver gotcha #1)
 * data-dt-page-length  = filas por página inicial (default 10)
 * data-dt-search-input = selector CSS de un <input> ya existente en la vista
 *                        (buscador propio con su estilo) para usar EN VEZ del
 *                        buscador que DataTables genera solo -- evita 2
 *                        buscadores redundantes en pantallas que ya traían uno.
 *
 * GOTCHAS REALES (confirmados en vivo, no teóricos -- cuelgan el navegador
 * ENTERO, no solo la tabla, reproducidos aislando atributos uno por uno):
 * 1. Celdas con <button onclick="...JSON/texto con acentos..."> (confirmAction,
 *    json_encode embebido -- típico en columnas "Acciones" de este proyecto)
 *    + la auto-detección de tipo de columna de DataTables 3.0.1 (regex de
 *    fecha/numérico corriendo sobre el texto de la celda). Fix: `type:'string'`
 *    fijo en TODAS las columnas de entrada (ver columnDefs abajo), no opcional.
 * 2. `layout` con un slot puesto en `null` (ej. `topStart:null` para ocultar
 *    el buscador propio de DataTables) + una tabla con MUCHAS celdas pesadas
 *    -- con pocas filas no se nota, a partir de cierto volumen cuelga. Fix:
 *    el `layout` NUNCA lleva `null` -- siempre los 4 slots estándar;
 *    "ocultar" el buscador propio cuando hay uno externo se hace por CSS
 *    (.dt-external-search .dt-search), nunca vaciando su slot.
 * 3. Un modo "minimal" (paging/searching/info:false pero layout pidiendo
 *    igual esos slots) SE PROBÓ y colgó 2/2 veces -- incluso en una tabla
 *    sin onclick-JSON, solo texto plano. No existe ese modo acá a propósito:
 *    si una pantalla ya tiene su propio filtro+paginado del lado del
 *    servidor (Auditoría, con exportación Excel/PDF sincronizada a esos
 *    filtros por query string), NO se le aplica DataTable -- se deja la
 *    tabla plana tal cual, es más seguro que una config a medio apagar.
 */
window.initDataTables = function (scope) {
    scope = scope || document;
    var tables = scope.querySelectorAll('table[data-dt]');

    tables.forEach(function (el) {
        if (window.DataTable && window.DataTable.isDataTable(el)) {
            window.DataTable(el).destroy();
        }

        var noOrderAttr = el.getAttribute('data-dt-cols-noorder');
        var noOrderCols = noOrderAttr ? noOrderAttr.split(',').map(function (n) { return parseInt(n, 10); }) : [];
        // { targets:'_all', type:'string' } SIEMPRE primero: ver gotcha #1
        // arriba, evita que la auto-deteccion de tipo cuelgue el navegador
        // con celdas que traen botones onclick con JSON/acentos.
        var columnDefs = [{ targets: '_all', type: 'string' }];
        if (noOrderCols.length) {
            columnDefs.push({ targets: noOrderCols, orderable: false, searchable: false });
        }

        var orderAttr = el.getAttribute('data-dt-order');
        var order = [[0, 'asc']];
        if (orderAttr) {
            var parts = orderAttr.split(',');
            order = [[parseInt(parts[0], 10), parts[1] || 'asc']];
        } else {
            order = [];
        }

        var pageLength = parseInt(el.getAttribute('data-dt-page-length') || '10', 10);
        var externalSearchSel = el.getAttribute('data-dt-search-input');

        // Layout SIEMPRE con los 4 slots estándar -- ver gotcha #2 arriba.
        var layout = {
            topStart: 'search',
            topEnd: 'pageLength',
            bottomStart: 'info',
            bottomEnd: 'paging',
        };

        try {
            var dt = new window.DataTable(el, {
                // APP_URL (sin "window.") -- shell.php lo declara con
                // `const APP_URL` en un <script> inline; un const de nivel
                // superior NUNCA se adjunta a `window`, pero SÍ queda visible
                // como identificador simple para cualquier <script> posterior
                // en el mismo documento (ver main.js, que ya lo usa así).
                // `window.APP_URL` daba `undefined` -> el fetch del idioma
                // pedía literalmente "undefined/public/..." y fallaba -> el
                // "i18n file loading error" que reportó el usuario, y las
                // tablas quedaban en inglés (fallback silencioso de DataTables).
                language: { url: APP_URL + '/public/librerias/datatables-core/es-ES.json' },
                order: order,
                columnDefs: columnDefs,
                pageLength: pageLength,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
                layout: layout,
            });
            el.classList.add('dt-ready');

            if (externalSearchSel) {
                // El buscador propio de DataTables se oculta por CSS puro
                // (:has() -- ver datatables-theme.css), NO por JS: tanto
                // `el.closest('.dt-container')` como la API oficial
                // `dt.table().container()` COLGARON el navegador entero acá
                // (probado, reproducido) -- cualquier lectura del DOM/API de
                // DataTables inmediatamente despues de construir la tabla es
                // sospechosa, mejor no tocarla para nada más que buscar.
                var input = document.querySelector(externalSearchSel);
                if (input) {
                    input.value = '';
                    input.addEventListener('input', function () {
                        dt.search(input.value).draw();
                    });
                }
            }
        } catch (e) {
            console.error('[DataTables] No se pudo inicializar tabla', el, e);
            el.classList.add('dt-ready');
        }
    });
};

document.addEventListener('DOMContentLoaded', function () { window.initDataTables(); });
// main.js dispara 'spa-content-loaded' en window (no document) despues de
// reemplazar #main-spa-container y re-ejecutar los <script> de la vista
// nueva -- sin payload, por eso se re-escanea document entero.
window.addEventListener('spa-content-loaded', function () { window.initDataTables(); });
