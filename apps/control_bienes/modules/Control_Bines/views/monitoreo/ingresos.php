<?php
$puedeCrear = !empty($_permisoVista['create']) || !empty($_permisoVista['full']);
$config = [
    'hoy' => date('Y-m-d'),
    'busquedaInicial' => trim((string)($_GET['termino'] ?? '')),
    'url' => 'index.php?route=ingresos&action=facturasIngresoDataTable',
    'vigenciaSegundos' => max(60, (int)($tiempoVigenciaConsulta ?? 600)),
    'tokenSesion' => (string)($tokenSesionConsulta ?? session_id()),
];
?>
<link rel="stylesheet" href="public/css/inv_ingresos_factura.css?v=<?= (int)@filemtime(ROOT_PATH.'public/css/inv_ingresos_factura.css') ?>">
<link rel="stylesheet" href="public/css/inv_flujo_bodega.css?v=<?= (int)@filemtime(ROOT_PATH.'public/css/inv_flujo_bodega.css') ?>">

<div class="if-page-header if-list-hero">
  <div>
    <div class="if-breadcrumb"><span>Bodega</span><i class="fa-solid fa-chevron-right"></i> Ingresos con factura</div>
    <h1>Ingresos a Bodega con Factura</h1>
    <p>Consulte los comprobantes cuando los necesite y abra su ficha para revisar el ingreso completo.</p>
  </div>
  <?php if($puedeCrear): ?>
    <a class="if-add if-hero-add" href="index.php?route=ingresos&amp;action=facturaIngreso"><i class="fa-solid fa-file-circle-plus"></i><span>Nueva factura</span></a>
  <?php endif; ?>
</div>
<?php $pasoActivo=3; require __DIR__.'/_flujo_bodega.php'; ?>

<?php if(!$esquemaDisponible): ?>
  <div class="if-schema-warning"><i class="fa-solid fa-triangle-exclamation"></i><div><strong>Falta actualizar la base de datos.</strong><span>Ejecute la migración <code>inv_20260813_ingresos_factura_v2.sql</code>.</span></div></div>
<?php else: ?>
<section class="if-filter-card if-list-filters">
  <div class="if-filter-intro"><span><i class="fa-solid fa-sliders"></i></span><div><strong>Filtrar comprobantes</strong><small>Defina el período y pulse Mostrar datos.</small></div></div>
  <div class="if-period-buttons" role="group" aria-label="Período rápido">
    <button type="button" class="active" data-periodo="hoy">Hoy</button>
    <button type="button" data-periodo="mes">Mes</button>
    <button type="button" data-periodo="anio">Año</button>
    <button type="button" data-periodo="todos">Todos</button>
  </div>
  <label><span>Desde</span><input type="date" id="if-fecha-desde"></label>
  <label><span>Hasta</span><input type="date" id="if-fecha-hasta"></label>
  <label><span>Estado</span><select id="if-estado"><option value="">Todos</option><option>REGISTRADA</option><option>INGRESADA</option><option>ANULADA</option></select></label>
  <button type="button" class="btn-primary if-show-data" id="if-mostrar"><i class="fa-solid fa-table-list" id="if-mostrar-icon"></i><span id="if-mostrar-label">Mostrar datos</span></button>
</section>

<section class="if-table-card if-invoice-list-card">
  <div class="if-table-heading">
    <div class="if-table-heading-title"><span class="if-table-heading-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span><div><h2>Facturas de ingreso</h2><p id="if-list-subtitle">La consulta se carga únicamente cuando la necesita.</p></div></div>
    <span class="if-query-state" id="if-query-state"><i class="fa-regular fa-circle"></i> Datos sin cargar</span>
  </div>

  <div class="if-list-empty" id="if-list-empty">
    <span class="if-list-empty-icon"><i class="fa-solid fa-database" id="if-empty-icon"></i></span>
    <h3 id="if-empty-title">Consulta lista para cargar</h3>
    <p id="if-empty-message">Para optimizar el sistema, las facturas no se consultan automáticamente.</p>
    <button type="button" class="btn-primary" id="if-empty-show"><i class="fa-solid fa-bolt"></i> Mostrar datos ahora</button>
  </div>

  <div class="if-invoice-table-shell" id="if-table-shell">
    <table id="if-facturas" class="display responsive nowrap" style="width:100%">
      <thead><tr><th>Número de factura</th><th>Fecha</th><th>Código proveedor</th><th>Descripción</th><th>Monto</th><th>Estado</th><th>Acciones</th></tr></thead>
    </table>
  </div>
</section>

<script type="application/json" id="if-list-config"><?= json_encode($config, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
<script src="public/js/inv_ingresos_factura.js?v=<?= (int)@filemtime(ROOT_PATH.'public/js/inv_ingresos_factura.js') ?>"></script>
<?php endif; ?>
