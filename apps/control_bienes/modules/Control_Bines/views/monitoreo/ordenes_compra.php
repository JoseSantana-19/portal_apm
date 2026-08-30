<?php
$puedeCrear = !empty($_permisoVista['create']) || !empty($_permisoVista['full']);
$puedeEditar = !empty($_permisoVista['edit']) || !empty($_permisoVista['full']);
$pasoActivo = 2;
$configLista=['hoy'=>date('Y-m-d'),'url'=>'index.php?route=ordenes_compra&action=ordenesCompraDataTable','puedeEditar'=>$puedeEditar,'periodoActivo'=>!empty($periodoActivo),'csrf'=>csrf_token()];
?>
<link rel="stylesheet" href="public/css/inv_flujo_bodega.css?v=<?= (int)@filemtime(ROOT_PATH.'public/css/inv_flujo_bodega.css') ?>">
<link rel="stylesheet" href="public/css/inv_flujo_bodega_moderno.css?v=<?= (int)@filemtime(ROOT_PATH.'public/css/inv_flujo_bodega_moderno.css') ?>">
<link rel="stylesheet" href="public/css/inv_ordenes_compra.css?v=<?= (int)@filemtime(ROOT_PATH.'public/css/inv_ordenes_compra.css') ?>">
<link rel="stylesheet" href="public/css/inv_consultas_diferidas.css?v=<?= (int)@filemtime(ROOT_PATH.'public/css/inv_consultas_diferidas.css') ?>">
<div class="wf-page">
    <header class="wf-hero"><div class="wf-title"><span><i class="fa-solid fa-cart-shopping"></i></span><div><h1>Órdenes de compra</h1><p>Consulta, prepara y aprueba las compras requeridas para abastecer la bodega.</p></div></div><span class="wf-period <?= $periodoActivo?'active':'inactive' ?>"><i class="fa-solid <?= $periodoActivo?'fa-circle-check':'fa-triangle-exclamation' ?>"></i><?= $periodoActivo?'Período '.htmlspecialchars($periodoActivo['nombre']).' activo':'Sin período activo' ?></span></header>
    <?php require __DIR__.'/_flujo_bodega.php'; ?>
    <nav class="oc-tabs"><a class="active" href="index.php?route=ordenes_compra"><i class="fa-solid fa-list"></i> Lista de órdenes</a><?php if($puedeCrear): ?><a class="<?= $periodoActivo?'':'disabled' ?>" href="<?= $periodoActivo?'index.php?route=ordenes_compra&amp;action=nuevaOrdenCompra':'#' ?>" title="<?= $periodoActivo?'Crear orden':'No existe un período activo' ?>"><i class="fa-solid fa-file-circle-plus"></i> Nueva orden</a><?php endif; ?></nav>
    <div class="oc-period-message <?= $periodoActivo?'active':'inactive' ?>"><i class="fa-solid <?= $periodoActivo?'fa-circle-check':'fa-triangle-exclamation' ?>"></i><span><?= $periodoActivo?'El período está activo. Se permite crear, editar y aprobar órdenes de compra.':'No se puede solicitar una orden de compra porque no existe un período activo.' ?></span></div>
    <?php if(!$esquemaDisponible): ?><section class="wf-card wf-empty"><i class="fa-solid fa-database"></i>El esquema de abastecimiento todavía no está instalado.</section><?php else: ?>
    <section class="inv-query-filter"><div class="inv-query-intro"><span><i class="fa-solid fa-sliders"></i></span><div><strong>Filtrar órdenes</strong><small>Defina el período y pulse Mostrar datos.</small></div></div><div class="inv-query-periods"><button type="button" class="active" data-oc-period="hoy">Hoy</button><button type="button" data-oc-period="mes">Mes</button><button type="button" data-oc-period="anio">Año</button><button type="button" data-oc-period="todos">Todos</button></div><label><span>Desde</span><input id="oc-date-from" type="date"></label><label><span>Hasta</span><input id="oc-date-to" type="date"></label><label><span>Estado</span><select id="oc-list-state"><option value="">Todos</option><option>PENDIENTE</option><option>APROBADA</option><option>CERRADA</option></select></label><button class="btn-primary inv-query-show" type="button" id="oc-show-data"><i class="fa-solid fa-table-list"></i><span>Mostrar datos</span></button></section>
    <section class="wf-card inv-query-card"><div class="wf-card-head"><div><h2>Lista de órdenes de compra</h2><p>Busca por orden, origen, proveedor, fecha, detalle o estado.</p></div><span class="wf-status inv-query-state" id="oc-visible-count"><i class="fa-regular fa-circle"></i> Datos sin cargar</span></div>
        <div class="inv-query-empty" id="oc-list-empty"><i class="fa-solid fa-database"></i><strong>Consulta lista para cargar</strong><span>Las órdenes no se descargan hasta que pulse Mostrar datos.</span></div>
        <div class="table-responsive inv-query-table" id="oc-table-shell" hidden><table class="wf-table oc-history-table" id="oc-history-table" data-auto-search="off"><thead><tr><th>Orden</th><th>Origen</th><th>Proveedor</th><th>Fecha</th><th>Líneas</th><th>Subtotal</th><th>IVA</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead></table></div>
    </section><?php endif; ?>
</div>
<script type="application/json" id="oc-list-config"><?= json_encode($configLista, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
<script src="public/js/inv_ordenes_compra.js?v=<?= (int)@filemtime(ROOT_PATH.'public/js/inv_ordenes_compra.js') ?>"></script>
