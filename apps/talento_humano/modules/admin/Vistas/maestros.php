<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Maestros y denominaciones | APM</title>
    <?php require ROOT . '/shared/head_assets.php'; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin_compact.css">
</head>
<body><div class="app">
<?php require ROOT . '/shared/menu.php'; ?>
<section class="content"><?php $topbarTitle='Estructura y cargos';$topbarSubtitle='Direcciones, áreas y denominaciones';$topbarShowSearch=true;require ROOT.'/shared/topbar.php'; ?>
<main class="main"><div class="content-shell master-section admin-page">
<section class="master-grid">
    <form class="card master-form" method="post" action="<?= BASE_URL ?>/admin/maestros/unidad/guardar">
        <h3><?= $unidadEditar ? 'Editar unidad' : 'Crear dirección / área' ?></h3>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><input type="hidden" name="unidad_id" value="<?= (int)($unidadEditar['unidad_id'] ?? 0) ?>">
        <div class="master-field"><label>Nombre</label><input name="nombre_unidad" maxlength="150" required value="<?= htmlspecialchars($unidadEditar['nombre_unidad'] ?? '') ?>"></div>
        <div class="master-field"><label>Dirección padre</label><select name="unidad_padre_id"><option value="">Ninguna: es una Dirección</option><?php foreach ($unidades as $u): if ($u['unidad_padre_id'] !== null) continue; ?><option value="<?= (int)$u['unidad_id'] ?>" <?= (int)($unidadEditar['unidad_padre_id'] ?? 0)===(int)$u['unidad_id']?'selected':'' ?>><?= htmlspecialchars($u['nombre_unidad']) ?></option><?php endforeach; ?></select><small>Seleccione una dirección para crear el registro como Área.</small></div>
        <div class="master-field"><label>Tipo de proceso</label><select name="tipo_proceso"><?php foreach (Catalogos::TIPOS_PROCESO as $tipo): ?><option <?= ($unidadEditar['tipo_proceso'] ?? '')===$tipo?'selected':'' ?>><?= htmlspecialchars($tipo) ?></option><?php endforeach; ?></select></div>
        <div class="master-field"><label>Estado</label><select name="activo"><option value="1" <?= (int)($unidadEditar['activo'] ?? 1)===1?'selected':'' ?>>Activo</option><option value="0" <?= isset($unidadEditar['activo'])&&(int)$unidadEditar['activo']===0?'selected':'' ?>>Inactivo (baja lógica)</option></select></div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Guardar unidad</button>
    </form>
    <div class="card"><div class="card-header"><div><h3>Estructura organizacional</h3><p>Las áreas aparecen debajo de su dirección.</p></div></div><div class="master-table"><table><thead><tr><th>Código</th><th>Tipo</th><th>Nombre</th><th>Dirección padre</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach($unidades as $u): ?><tr><td><?= htmlspecialchars($u['codigo_uorg']) ?></td><td><?= htmlspecialchars($u['tipo_unidad']) ?></td><td><?= htmlspecialchars($u['nombre_unidad']) ?></td><td><?= htmlspecialchars($u['direccion_padre'] ?? '—') ?></td><td class="state-<?= (int)$u['activo'] ?>"><?= (int)$u['activo']===1?'Activo':'Inactivo' ?></td><td><a class="btn btn-outline" href="<?= BASE_URL ?>/admin/maestros?unidad_id=<?= (int)$u['unidad_id'] ?>">Editar</a></td></tr><?php endforeach; ?></tbody></table></div></div>
</section>
<section class="master-grid">
    <form class="card master-form" method="post" action="<?= BASE_URL ?>/admin/maestros/puesto/guardar">
        <h3><?= $puestoEditar ? 'Editar denominación' : 'Agregar denominación libre' ?></h3>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><input type="hidden" name="puesto_id" value="<?= (int)($puestoEditar['puesto_id'] ?? 0) ?>">
        <div class="master-field"><label>Denominación del puesto</label><input name="nombre_puesto" maxlength="150" required value="<?= htmlspecialchars($puestoEditar['nombre_puesto'] ?? '') ?>"></div>
        <div class="master-field"><label>Remuneración unificada</label><input name="remuneracion_unificada" type="number" min="0" step="0.01" value="<?= htmlspecialchars($puestoEditar['remuneracion_unificada'] ?? '0.00') ?>"></div>
        <div class="master-field"><label>Estado</label><select name="activo"><option value="1" <?= (int)($puestoEditar['activo'] ?? 1)===1?'selected':'' ?>>Activo</option><option value="0" <?= isset($puestoEditar['activo'])&&(int)$puestoEditar['activo']===0?'selected':'' ?>>Inactivo (baja lógica)</option></select></div>
        <button class="btn btn-primary" type="submit"><i class="bi bi-plus-circle"></i> Guardar denominación</button>
    </form>
    <div class="card"><div class="card-header"><div><h3>Cargos / puestos</h3><p>Catálogo de denominaciones disponible en todo el sistema.</p></div></div><div class="master-table"><table><thead><tr><th>Código</th><th>Denominación</th><th>RMU</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach($puestos as $p): ?><tr><td><?= htmlspecialchars($p['codigo_puesto']) ?></td><td><?= htmlspecialchars($p['nombre_puesto']) ?></td><td>$<?= number_format((float)$p['remuneracion_unificada'],2) ?></td><td class="state-<?= (int)$p['activo'] ?>"><?= (int)$p['activo']===1?'Activo':'Inactivo' ?></td><td><a class="btn btn-outline" href="<?= BASE_URL ?>/admin/maestros?puesto_id=<?= (int)$p['puesto_id'] ?>">Editar</a></td></tr><?php endforeach; ?></tbody></table></div></div>
</section>
</div></main></section></div>
<?php if(!empty($_GET['msg'])): ?><script>addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_GET['msg']) ?>,<?= ($_GET['ok']??'0')==='1'?"'success'":"'error'" ?>));</script><?php endif; ?>
<?php require ROOT.'/shared/footer_scripts.php'; ?>
</body></html>
