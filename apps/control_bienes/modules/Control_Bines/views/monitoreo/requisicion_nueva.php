<?php $pasoActivo = 1; ?>
<link rel="stylesheet" href="public/css/inv_flujo_bodega.css?v=<?= (int)@filemtime(ROOT_PATH.'public/css/inv_flujo_bodega.css') ?>">
<link rel="stylesheet" href="public/css/inv_flujo_bodega_moderno.css?v=<?= (int)@filemtime(ROOT_PATH.'public/css/inv_flujo_bodega_moderno.css') ?>">
<link rel="stylesheet" href="public/css/inv_requisiciones_moderno.css?v=<?= (int)@filemtime(ROOT_PATH.'public/css/inv_requisiciones_moderno.css') ?>">

<div class="wf-page rq-create-page">
    <header class="wf-hero">
        <div class="wf-title">
            <span><i class="fa-solid fa-file-circle-plus"></i></span>
            <div><h1>Nueva requisición</h1><p>Registra la cabecera y todos sus productos dentro de una página independiente.</p></div>
        </div>
        <span class="wf-period <?= $periodoActivo?'active':'inactive' ?>">
            <i class="fa-solid <?= $periodoActivo?'fa-circle-check':'fa-triangle-exclamation' ?>"></i>
            <?= $periodoActivo?'Período '.htmlspecialchars($periodoActivo['nombre']).' activo':'Sin período activo' ?>
        </span>
    </header>

    <?php require __DIR__.'/_flujo_bodega.php'; ?>

    <nav class="rq-tabs" aria-label="Apartados de requisiciones">
        <a href="index.php?route=requisiciones"><i class="fa-solid fa-list"></i> Lista de requisiciones</a>
        <a class="active" href="index.php?route=requisiciones&amp;action=nuevaRequisicion"><i class="fa-solid fa-file-circle-plus"></i> Nueva requisición</a>
    </nav>

    <?php if(!$periodoActivo): ?><div class="rq-period-block"><i class="fa-solid fa-lock"></i><strong>Período cerrado.</strong> La creación de requisiciones está deshabilitada hasta que exista un período activo.</div><?php endif; ?>
    <form action="index.php?route=requisiciones&amp;action=guardarSolicitud" method="post" id="rq-form" class="wf-card rq-form-card" <?= $periodoActivo?'':'data-period-locked="1"' ?>>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

        <section class="rq-document-head">
            <div class="rq-head-fields">
                <label><span>N.º de requisición</span><input value="AUTOMÁTICO" readonly aria-label="Número de requisición automático"></label>
                <label class="rq-note-field"><span>N.º de nota de pedido <small>Opcional</small></span><span class="rq-note-control"><input id="rq-note-number" name="documento_referencia" maxlength="100" placeholder="Ej. NPA-00024 o 24" autocomplete="off"><button type="button" id="rq-load-note" title="Cargar nota de pedido"><i class="fa-solid fa-magnifying-glass"></i></button></span></label>
                <label><span>Fecha de requisición</span><input type="date" name="fecha_solicitud" value="<?= date('Y-m-d') ?>" required></label>
                <label class="rq-detail-field"><span>Detalle de requisición</span><input name="motivo" maxlength="500" placeholder="Finalidad de la requisición" required></label>
            </div>
            <div class="rq-head-actions">
                <button class="btn-outline" type="button" id="rq-reset-form"><i class="fa-solid fa-eraser"></i> Limpiar</button>
                <button class="btn-primary" type="submit" <?= $periodoActivo?'':'disabled' ?>><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
            </div>
        </section>

        <aside class="rq-multi-note-help" id="rq-note-summary" aria-live="polite">
            <i class="fa-solid fa-wand-magic-sparkles"></i>
            <div><strong>Carga automática desde la cabecera</strong><span>Escriba el número de la nota y presione Enter o el botón de búsqueda. Se completarán sus datos generales y todos sus productos; después podrá revisarlos o editarlos.</span></div>
        </aside>

        <section class="rq-grid-section">
            <div class="rq-grid-toolbar">
                <div><h3>Detalle de productos</h3><p>Los productos de la nota se cargan automáticamente. También puede agregar o editar filas manualmente.</p></div>
                <button class="btn-outline" type="button" id="rq-add-line"><i class="fa-solid fa-plus"></i> Agregar fila</button>
            </div>
            <div class="rq-grid-wrap">
                <table class="rq-grid">
                    <thead><tr><th>#</th><th>N.º pedido <small>Opcional</small></th><th>Fecha pedido</th><th>Cód. producto</th><th>Producto</th><th>Cantidad</th><th>Prom. unit.</th><th>Subtotal</th><th>Referencia</th><th>Otra referencia</th><th>Existencia</th><th></th></tr></thead>
                    <tbody id="rq-lines"></tbody>
                    <tfoot><tr><td colspan="7"><strong>Total estimado</strong></td><td id="rq-total">$0.00</td><td colspan="4" id="rq-line-count">0 productos</td></tr></tfoot>
                </table>
            </div>
        </section>

        <section class="rq-bottom-fields">
            <label><span>Centro de consumo <small>Área o departamento</small></span><select name="centro_consumo_grupo_id" id="rq-center-group" data-searchable-select data-search-placeholder="Buscar área o departamento…" required><option value="">Escriba para buscar…</option><?php foreach($gruposCentros as $grupo): ?><option value="<?= (int)$grupo['id'] ?>"><?= htmlspecialchars($grupo['nombre']) ?></option><?php endforeach; ?></select></label>
            <label><span>Responsable del centro <small>Persona</small></span><select name="centro_consumo_persona_id" id="rq-center-person" data-searchable-select data-search-placeholder="Buscar responsable…" required><option value="">Seleccione primero el centro…</option><?php foreach($personal as $persona): ?><option value="<?= (int)$persona['id'] ?>" data-unidad="<?= (int)($persona['unidad_id']??0) ?>"><?= htmlspecialchars($persona['nombre'].(!empty($persona['cargo'])?' · '.$persona['cargo']:'')) ?></option><?php endforeach; ?></select></label>
            <label><span>Prioridad</span><select name="prioridad" id="rq-priority"><option>Normal</option><option>Urgente</option><option>Crítica</option></select></label>
            <label class="rq-observations"><span>Observaciones</span><input name="observaciones" id="rq-observations" maxlength="2000" placeholder="Información adicional opcional"></label>
        </section>

        <footer class="rq-form-footer">
            <a class="btn-outline" href="index.php?route=requisiciones"><i class="fa-solid fa-arrow-left"></i> Volver a la lista</a>
            <button class="btn-primary" type="submit" <?= $periodoActivo?'':'disabled' ?>><i class="fa-solid fa-floppy-disk"></i> Guardar requisición</button>
        </footer>
    </form>
</div>

<template id="rq-line-template"><tr><td class="rq-row-number"></td><td><div class="rq-grid-note"><input class="rq-order-number" maxlength="100" placeholder="Ej. NPE-00002" autocomplete="off"><button class="rq-row-note-search" type="button" title="Buscar orden de pedido" aria-label="Buscar orden de pedido"><i class="fa-solid fa-magnifying-glass"></i></button><span class="rq-row-note-state" aria-live="polite"></span></div></td><td><input class="rq-order-date" type="date"></td><td class="rq-product-code">—</td><td class="rq-product-cell"><div class="rq-product-input"><i class="fa-solid fa-magnifying-glass"></i><input class="rq-product-search" placeholder="Código, nombre, marca, categoría o unidad…" autocomplete="off"><input class="rq-item-id" type="hidden" required></div></td><td><input class="rq-quantity" type="number" min="1" step="1" value="1" required></td><td class="rq-average">$0.0000</td><td class="rq-subtotal">$0.00</td><td><input class="rq-reference" maxlength="255"></td><td><input class="rq-other-reference" maxlength="255"></td><td class="rq-stock">0</td><td><button class="rq-remove" type="button" title="Eliminar fila"><i class="fa-solid fa-trash"></i></button></td></tr></template>
<div class="rq-product-results" id="rq-product-results"></div>
<script src="public/js/inv_requisiciones.js?v=<?= (int)@filemtime(ROOT_PATH.'public/js/inv_requisiciones.js') ?>"></script>
