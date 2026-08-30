<?php
$esNueva=empty($factura);
$estado=$factura['estado']??'NUEVA';
$puedeCrear=!empty($_permisoVista['create'])||!empty($_permisoVista['full']);
$puedeEditar=!empty($_permisoVista['edit'])||!empty($_permisoVista['full']);
$puedeModificar=$esNueva?$puedeCrear:($puedeEditar&&$estado==='REGISTRADA');
$detalleFactura=$factura['detalles']??[];
$tasaPredeterminada=0.0;
foreach($detalleFactura as $linea)$tasaPredeterminada=max($tasaPredeterminada,(float)($linea['iva_porcentaje']??0));
if($esNueva&&$tiposIva){foreach($tiposIva as $tipo)$tasaPredeterminada=max($tasaPredeterminada,(float)$tipo['tasa_iva']);}
$config=[
 'hoy'=>date('Y-m-d'),'esNueva'=>$esNueva,'estado'=>$estado,'puedeModificar'=>$puedeModificar,'vistaPrevia'=>$esVistaPrevia,
 'usuarioId'=>(int)($_SESSION['usuario']['id']??$_SESSION['usuario_id']??0),
 'factura'=>$factura,'tiposIva'=>array_map(static fn($i)=>['id'=>(int)$i['id'],'nombre'=>$i['nombre'],'tasa'=>(float)$i['tasa_iva']],$tiposIva),
 'tasaPredeterminada'=>$tasaPredeterminada,'productosUrl'=>'index.php?route=ingresos&action=productosFacturaDataTable',
 'resolverProductosUrl'=>'index.php?route=ingresos&action=resolverProductosEscaneadosFactura',
 'requisicionUrl'=>'index.php?route=ingresos&action=buscarRequisicionFactura',
 'proveedoresUrl'=>'index.php?route=ingresos&action=proveedoresFacturaJson',
 'proveedores'=>array_map(static fn($p)=>['id'=>(int)$p['id'],'codigo'=>$p['codigo']??'','nombre'=>$p['nombre'],'ruc'=>$p['ruc']??''],$proveedores)
];
?>
<link rel="stylesheet" href="public/css/inv_ingresos_factura.css?v=<?= (int)@filemtime(ROOT_PATH.'public/css/inv_ingresos_factura.css') ?>">
<div class="if-document-page <?= $esVistaPrevia?'if-preview-mode':'' ?>" id="if-document-page">
 <header class="if-document-header">
  <div class="if-document-title"><span class="if-document-logo"><i class="fa-solid fa-warehouse"></i></span><div><h1>Ingreso a bodega</h1><p><?= $esNueva?'Nueva factura de compra':'Factura '.htmlspecialchars($factura['numero_factura']) ?></p></div></div>
  <div class="if-document-top-actions"><a class="btn-outline" href="index.php?route=ingresos"><i class="fa-solid fa-xmark"></i> Cerrar</a><label class="if-document-search"><i class="fa-solid fa-magnifying-glass"></i><input id="if-search-record" placeholder="Buscar ingreso"></label></div>
 </header>

 <?php if($esVistaPrevia): ?><div class="if-preview-banner"><i class="fa-solid fa-eye"></i><span>Vista previa del documento</span><a href="index.php?route=ingresos&amp;action=facturaIngreso&amp;id=<?= (int)$factura['id_factura'] ?>">Volver a la ficha</a></div><?php endif; ?>

 <nav class="if-toolbar" aria-label="Acciones de factura">
  <a class="if-tool" href="index.php?route=ingresos&amp;action=facturaIngreso"><i class="fa-regular fa-file"></i><span>Nuevo</span></a>
  <button class="if-tool" type="button" id="if-editar" <?= !$puedeModificar||$esNueva?'disabled':'' ?>><i class="fa-solid fa-pen"></i><span>Editar</span></button>
  <button class="if-tool" type="button" id="if-anular" <?= !$puedeModificar||$esNueva?'disabled':'' ?>><i class="fa-solid fa-ban"></i><span>Anular</span></button>
  <button class="if-tool if-tool-primary" type="submit" form="if-form" id="if-guardar" <?= !$puedeModificar?'disabled':'' ?>><i class="fa-regular fa-floppy-disk"></i><span>Guardar</span></button>
  <button class="if-tool" type="button" id="if-limpiar" <?= !$esNueva?'disabled':'' ?>><i class="fa-solid fa-eraser"></i><span>Limpiar</span></button>
  <button class="if-tool if-tool-success" type="button" id="if-ingresar" <?= !$puedeModificar||$esNueva?'disabled':'' ?>><i class="fa-solid fa-boxes-stacked"></i><span>Ingresar a bodega</span></button>
  <button class="if-tool" type="button" id="if-imprimir"><i class="fa-solid fa-print"></i><span>Imprimir</span></button>
  <a class="if-tool <?= $esNueva?'disabled':'' ?>" <?= $esNueva?'aria-disabled="true"':'href="index.php?route=ingresos&amp;action=facturaIngreso&amp;id='.(int)$factura['id_factura'].'&amp;preview=1"' ?>><i class="fa-regular fa-eye"></i><span>Vista previa</span></a>
 </nav>

 <?php if(!$esNueva): ?><section class="if-audit-strip if-audit-compact"><div><span>Registrado por</span><strong><?= htmlspecialchars($factura['creado_por']??'—') ?></strong></div><div><span>Fecha de registro</span><strong><?= htmlspecialchars($factura['fecha_creacion']??'—') ?></strong></div><div><span>Última actualización</span><strong><?= htmlspecialchars($factura['fecha_actualizacion']??'—') ?></strong></div><div><span>Actualizado por</span><strong><?= htmlspecialchars($factura['actualizado_por']??'—') ?></strong></div></section><?php endif; ?>

 <form action="index.php?route=ingresos&amp;action=guardarFacturaIngreso" method="post" id="if-form">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="factura_id" value="<?= (int)($factura['id_factura']??0) ?>">
  <section class="if-doc-card if-invoice-data"><div class="if-invoice-card-head"><div class="if-section-heading"><span class="if-section-icon"><i class="fa-regular fa-file-lines"></i></span><div><h2>Datos de la factura</h2><p>Complete los datos manualmente o impórtelos desde el comprobante.</p></div></div><label class="if-scan-pdf" for="if-pdf-file"><i class="fa-solid fa-file-pdf"></i><span><strong>Escanear PDF</strong><small>Completar datos automáticamente</small></span><input id="if-pdf-file" type="file" accept="application/pdf,.pdf" hidden></label></div><div class="if-scan-status" id="if-scan-status" role="status"><i class="fa-solid fa-circle-info"></i><span>El documento se analiza localmente y siempre podrá revisar los datos antes de aplicarlos.</span></div><div class="if-doc-grid">
   <label><span>Fecha</span><input class="if-editable" type="date" name="fecha_factura" id="if-fecha" value="<?= htmlspecialchars($factura['fecha_factura']??date('Y-m-d')) ?>" required></label>
   <label class="if-order-field"><span>No. orden <em>AUTO</em></span><input id="if-orden" value="<?= htmlspecialchars($factura['orden_secuencial']??'Se asignará al guardar') ?>" readonly><small>El sistema genera automáticamente la siguiente orden OCP.</small></label>
   <label><span>Número de factura</span><input class="if-editable" name="numero_factura" id="if-numero" maxlength="100" value="<?= htmlspecialchars($factura['numero_factura']??'') ?>" required></label>
   <label><span>IVA predeterminado</span><select class="if-editable" id="if-iva-default"><option value="0">No aplica / 0%</option><?php foreach($tiposIva as $tipo): ?><option value="<?= (float)$tipo['tasa_iva'] ?>" <?= abs((float)$tipo['tasa_iva']-$tasaPredeterminada)<.0001?'selected':'' ?>><?= htmlspecialchars($tipo['nombre']) ?> (<?= (float)$tipo['tasa_iva'] ?>%)</option><?php endforeach; ?></select></label>
   <label class="if-provider-field"><span>Proveedor</span><div class="if-provider-control"><select class="if-editable" name="proveedor_id" id="if-proveedor" data-searchable-select data-search-placeholder="Buscar proveedor por código, nombre o RUC…" required><option value="">Escriba para buscar…</option><?php foreach($proveedores as $p): ?><option value="<?= (int)$p['id'] ?>" <?= (int)($factura['proveedor_id']??0)===(int)$p['id']?'selected':'' ?>><?= htmlspecialchars(($p['codigo']?:'PRV-'.$p['id']).' · '.$p['nombre'].(!empty($p['ruc'])?' · '.$p['ruc']:'')) ?></option><?php endforeach; ?></select><a class="if-provider-create" id="if-create-provider" href="index.php?route=inv_maestros&amp;tabla=proveedores&amp;nuevo=1" target="_blank" rel="noopener" title="Crear un proveedor nuevo" aria-label="Crear un proveedor nuevo"><i class="fa-solid fa-plus"></i></a></div><small>Escriba el código, nombre o RUC. Si no existe, use el botón para crearlo.</small></label>
   <label class="if-detail-field"><span>Detalle / concepto de compra</span><input class="if-editable" name="descripcion" id="if-descripcion" maxlength="1000" value="<?= htmlspecialchars($factura['descripcion']??'') ?>" placeholder="Motivo general del ingreso"></label>
  </div></section>

  <section class="if-scan-review" id="if-scan-review">
   <div class="if-scan-review-head"><div><span class="if-scan-review-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span><div><h3>Datos detectados</h3><p id="if-scan-file-name">Revise la lectura antes de aplicarla.</p></div></div><button class="if-editor-close" type="button" id="if-scan-cancel" aria-label="Descartar lectura"><i class="fa-solid fa-xmark"></i></button></div>
   <div class="if-scan-detected" id="if-scan-detected"></div>
   <div class="if-scan-lines" id="if-scan-lines"></div>
   <div class="if-scan-actions"><span><i class="fa-solid fa-shield-halved"></i> Ningún dato se incorpora hasta confirmar.</span><button class="btn-outline" type="button" id="if-scan-discard">Descartar</button><button class="btn-primary" type="button" id="if-scan-apply"><i class="fa-solid fa-check"></i> Aplicar datos detectados</button></div>
  </section>

  <section class="if-doc-card if-products-card"><div class="if-card-heading"><div class="if-section-heading"><span class="if-section-icon if-green"><i class="fa-solid fa-box-open"></i></span><div><h2>Detalle de productos</h2><p>Escriba directamente en la fila rápida o use el catálogo para una búsqueda avanzada.</p></div></div><div class="if-card-actions"><button class="btn-outline" type="button" id="if-aplicar-iva"><i class="fa-solid fa-percent"></i> IVA a todas</button><button class="btn-primary" type="button" id="if-agregar-producto"><i class="fa-solid fa-plus"></i> Agregar producto</button></div></div>

   <section class="if-product-editor" id="if-product-editor">
    <div class="if-product-editor-head"><div><span class="if-step-number">1</span><strong id="if-editor-title">Seleccione un producto</strong><small>Prepare la línea y confírmela antes de incorporarla a la factura.</small></div><button class="if-editor-close" type="button" id="if-cerrar-editor" aria-label="Cerrar"><i class="fa-solid fa-xmark"></i></button></div>
    <div class="if-product-picker" id="if-product-picker"><div class="if-catalog-note"><i class="fa-solid fa-database"></i><span>Precio, existencia, configuración tributaria y cuenta contable provienen del inventario.</span></div><div class="if-catalog-table"><table id="if-productos" class="display" style="width:100%"><thead><tr><th>Producto</th><th>Existencia</th><th>Precio base</th><th>IVA</th><th></th></tr></thead></table></div></div>
    <div class="if-line-editor" id="if-line-editor">
     <div class="if-chosen-product"><span class="if-chosen-icon"><i class="fa-solid fa-box"></i></span><div><small>Producto seleccionado</small><strong id="if-draft-product">—</strong><span id="if-draft-code">—</span></div><div><small>Existencia</small><strong id="if-draft-stock">0</strong></div><div><small>Cuenta contable</small><strong id="if-draft-account">—</strong><span id="if-draft-account-name">—</span></div><button class="btn-outline" type="button" id="if-change-product"><i class="fa-solid fa-rotate"></i> Cambiar</button></div>
     <div class="if-line-fields"><label><span>Pedido</span><input id="if-draft-order" maxlength="100" placeholder="Opcional"></label><label><span>Requisición</span><input id="if-draft-requisition" maxlength="100" placeholder="Opcional"></label><label><span>Cantidad</span><input id="if-draft-quantity" type="number" min="1" step="1" value="1"></label><label><span>Precio unitario <em>BASE</em></span><input id="if-draft-price" readonly></label><label><span>Aplica IVA</span><select id="if-draft-applies"><option value="1">Sí</option><option value="0">No</option></select></label><label><span>Tipo de IVA</span><select id="if-draft-tax"><option value="">No aplica / 0%</option><?php foreach($tiposIva as $tipo): ?><option value="<?= (int)$tipo['id'] ?>" data-tasa="<?= (float)$tipo['tasa_iva'] ?>"><?= htmlspecialchars($tipo['nombre']) ?> (<?= (float)$tipo['tasa_iva'] ?>%)</option><?php endforeach; ?></select></label><label class="if-reference-field"><span>Referencia</span><input id="if-draft-reference" maxlength="255" placeholder="Serie, lote u observación opcional"></label></div>
     <div class="if-line-preview"><div><span>Subtotal</span><strong id="if-draft-subtotal">$0.00</strong></div><div><span>IVA</span><strong id="if-draft-tax-value">$0.00</strong></div><div class="if-line-preview-total"><span>Total línea</span><strong id="if-draft-total">$0.00</strong></div><button class="btn-primary" type="button" id="if-confirm-product"><i class="fa-solid fa-check"></i> Confirmar producto</button></div>
    </div>
   </section>

   <div class="if-detail-table-wrap"><table class="if-detail-table"><colgroup><col class="if-col-order"><col class="if-col-invoice"><col class="if-col-request"><col class="if-col-requisition"><col class="if-col-item"><col class="if-col-description"><col class="if-col-quantity"><col class="if-col-money"><col class="if-col-money"><col class="if-col-tax"><col class="if-col-money"><col class="if-col-money"><col class="if-col-reference"><col class="if-col-actions"></colgroup><thead><tr><th>No. orden</th><th>Factura</th><th>Pedido</th><th>Requisición</th><th>Ítem</th><th>Descripción</th><th>Cantidad</th><th>P. unitario</th><th>Subtotal</th><th>% IVA</th><th>IVA</th><th>Total</th><th>Referencia</th><th class="if-actions-heading">Acciones</th></tr></thead><tbody id="if-lineas"></tbody><tbody id="if-quick-lines" aria-label="Captura rápida de productos"></tbody></table></div>
   <div class="if-quick-results" id="if-quick-results" role="listbox" aria-label="Resultados de productos"></div>
   <div class="if-empty-lines" id="if-empty-lines"><i class="fa-solid fa-table-cells-large"></i><span>Aún no ha confirmado productos para esta factura.</span></div>
  </section>

  <div class="if-bottom-grid if-product-bottom-grid">
   <section class="if-doc-card if-product-accounting" id="if-product-info-panel">
    <div class="if-section-heading"><span class="if-section-icon if-violet"><i class="fa-solid fa-box"></i></span><div><h2>Información del producto</h2><p id="if-info-product">Seleccione una línea del detalle.</p></div></div>
    <div class="if-selected-product if-selected-product-compact"><div><span>Existencia</span><strong id="if-info-stock">—</strong></div><div><span>P. unitario</span><strong id="if-info-price">—</strong><small>Precio registrado en inventario</small></div></div>
    <h3>Aplicación contable</h3>
    <div class="if-account-wrap"><table class="if-account-table"><thead><tr><th>Código</th><th>Descripción</th><th class="if-money">Total</th></tr></thead><tbody id="if-info-account-body"><tr class="if-account-empty"><td colspan="3">Seleccione un producto para consultar su aplicación contable.</td></tr></tbody></table></div>
   </section>
   <section class="if-doc-card if-summary if-summary-compact"><div class="if-section-heading"><span class="if-section-icon if-amber"><i class="fa-solid fa-calculator"></i></span><div><h2>Resumen de factura</h2><p>IVA generado por cada tasa utilizada en la factura.</p></div></div><div class="if-summary-content"><div><div class="if-summary-row if-summary-main"><span>Subtotal</span><strong id="if-subtotal-general">$0.00</strong></div><div id="if-bases"></div><div class="if-summary-row if-iva-row"><span>Total IVA</span><strong id="if-total-iva">$0.00</strong></div></div><div class="if-grand-total"><span>TOTAL</span><strong id="if-total">$0.00</strong></div></div></section>
  </div>
 </form>

 <section class="if-inline-action" id="if-anular-panel"><div><h2>Anular factura</h2><p>La anulación no elimina el historial ni los datos de auditoría.</p></div><form action="index.php?route=ingresos&amp;action=anularFacturaIngreso" method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="factura_id" value="<?= (int)($factura['id_factura']??0) ?>"><textarea name="motivo" maxlength="1000" placeholder="Motivo obligatorio de anulación" required></textarea><div><button class="btn-outline" type="button" data-cancel-panel>Cancelar</button><button class="btn-danger">Confirmar anulación</button></div></form></section>
 <section class="if-inline-action" id="if-ingresar-panel"><div><h2>Confirmar ingreso a bodega</h2><p>Actualizará existencias, costo promedio y Kardex.</p></div><form action="index.php?route=ingresos&amp;action=confirmarIngresoFactura" method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="factura_id" value="<?= (int)($factura['id_factura']??0) ?>"><input type="date" name="fecha_ingreso" value="<?= date('Y-m-d') ?>" required><select name="responsable_id" required><option value="">Responsable de bodega…</option><?php foreach($personal as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option><?php endforeach; ?></select><input name="observaciones" maxlength="2000" placeholder="Observaciones de recepción"><div><button class="btn-outline" type="button" data-cancel-panel>Cancelar</button><button class="btn-primary">Confirmar ingreso</button></div></form></section>
</div>
<script type="application/json" id="if-form-config"><?= json_encode($config,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
<script src="public/js/inv_ingreso_factura_formulario.js?v=<?= (int)@filemtime(ROOT_PATH.'public/js/inv_ingreso_factura_formulario.js') ?>"></script>
