<?php
$puedeCrear = !empty($_permisoVista['create']) || !empty($_permisoVista['full']);
$puedeAnular = !empty($_permisoVista['edit']) || !empty($_permisoVista['full']);
$pasoActivo = 1;
$configLista = ['hoy'=>date('Y-m-d'),'url'=>'index.php?route=requisiciones&action=requisicionesDataTable','puedeAnular'=>$puedeAnular,'csrf'=>csrf_token()];
?>
<link rel="stylesheet" href="public/css/inv_flujo_bodega.css?v=<?= (int)@filemtime(ROOT_PATH.'public/css/inv_flujo_bodega.css') ?>">
<link rel="stylesheet" href="public/css/inv_flujo_bodega_moderno.css?v=<?= (int)@filemtime(ROOT_PATH.'public/css/inv_flujo_bodega_moderno.css') ?>">
<link rel="stylesheet" href="public/css/inv_requisiciones_moderno.css?v=<?= (int)@filemtime(ROOT_PATH.'public/css/inv_requisiciones_moderno.css') ?>">
<link rel="stylesheet" href="public/css/inv_consultas_diferidas.css?v=<?= (int)@filemtime(ROOT_PATH.'public/css/inv_consultas_diferidas.css') ?>">

<div class="wf-page">
    <header class="wf-hero">
        <div class="wf-title">
            <span><i class="fa-solid fa-clipboard-list"></i></span>
            <div><h1>Requisiciones</h1><p>Consulta y filtra las requisiciones registradas.</p></div>
        </div>
        <div class="rq-hero-actions">
            <span class="wf-period <?= $periodoActivo?'active':'inactive' ?>">
                <i class="fa-solid <?= $periodoActivo?'fa-circle-check':'fa-triangle-exclamation' ?>"></i>
                <?= $periodoActivo?'Período '.htmlspecialchars($periodoActivo['nombre']).' activo':'Sin período activo' ?>
            </span>
        </div>
    </header>

    <?php require __DIR__.'/_flujo_bodega.php'; ?>

    <nav class="rq-tabs" aria-label="Apartados de requisiciones">
        <a class="active" href="index.php?route=requisiciones"><i class="fa-solid fa-list"></i> Lista de requisiciones</a>
        <?php if($puedeCrear): ?><a href="index.php?route=requisiciones&amp;action=nuevaRequisicion"><i class="fa-solid fa-file-circle-plus"></i> Nueva requisición</a><?php endif; ?>
    </nav>

    <section class="inv-query-filter" aria-label="Filtros de requisiciones">
        <div class="inv-query-intro"><span><i class="fa-solid fa-sliders"></i></span><div><strong>Filtrar requisiciones</strong><small>Defina el período y pulse Mostrar datos.</small></div></div>
        <div class="inv-query-periods"><button type="button" class="active" data-rq-period="hoy">Hoy</button><button type="button" data-rq-period="mes">Mes</button><button type="button" data-rq-period="anio">Año</button><button type="button" data-rq-period="todos">Todos</button></div>
        <label><span>Desde</span><input id="rq-date-from" type="date"></label><label><span>Hasta</span><input id="rq-date-to" type="date"></label>
        <label><span>Estado</span><select id="rq-list-state"><option value="">Todos</option><option value="PENDIENTE">PENDIENTE</option><option value="ATENDIDA">ATENDIDA</option><option value="CERRADA">CERRADA</option><option value="ANULADA">ANULADA</option></select></label>
        <button class="btn-primary inv-query-show" type="button" id="rq-show-data"><i class="fa-solid fa-table-list"></i><span>Mostrar datos</span></button>
    </section>

    <section class="wf-card inv-query-card">
        <div class="wf-card-head">
            <div><h2>Lista de requisiciones</h2><p>Busca por número, fecha, código de producto, centro de consumo, detalle o estado.</p></div>
            <span class="wf-status inv-query-state" id="rq-visible-count"><i class="fa-regular fa-circle"></i> Datos sin cargar</span>
        </div>
        <div class="inv-query-empty" id="rq-list-empty"><i class="fa-solid fa-database"></i><strong>Consulta lista para cargar</strong><span>Las requisiciones no se descargan hasta que pulse Mostrar datos.</span></div>
        <div class="wf-table-wrap inv-query-table" id="rq-table-shell" hidden>
            <table class="wf-table rq-history-table" id="rq-history-table">
                <thead><tr><th>Requisición</th><th>Fecha</th><th>Centro de consumo</th><th>Detalle</th><th>Productos</th><th>Solicitado</th><th>Entregado</th><th>Estado</th><th>Acciones</th></tr></thead>
            </table>
        </div>
    </section>
</div>
<script type="application/json" id="rq-list-config"><?= json_encode($configLista, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
<script src="public/js/inv_requisiciones.js?v=<?= (int)@filemtime(ROOT_PATH.'public/js/inv_requisiciones.js') ?>"></script>
