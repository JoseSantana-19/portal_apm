<?php
$pasoActivo = (int)($pasoActivo ?? 1);
$pasosBodega = [
    1 => ['route'=>'requisiciones','label'=>'Requisición','detail'=>'Solicitud interna'],
    2 => ['route'=>'ordenes_compra','label'=>'Orden de compra','detail'=>'Compra autorizada'],
    3 => ['route'=>'ingresos','label'=>'Ingreso con factura','detail'=>'Recepción en bodega'],
    4 => ['route'=>'egresos','label'=>'Egreso de bodega','detail'=>'Entrega y salida'],
];
?>
<div class="wf-flow" aria-label="Flujo de bodega">
 <?php foreach($pasosBodega as $numero=>$paso): ?><a class="wf-step <?= $pasoActivo===$numero?'active':'' ?>" href="index.php?route=<?= htmlspecialchars($paso['route']) ?>"><span><?= $numero ?></span><div><strong><?= htmlspecialchars($paso['label']) ?></strong><small><?= htmlspecialchars($paso['detail']) ?></small></div></a><?php endforeach; ?>
</div>
