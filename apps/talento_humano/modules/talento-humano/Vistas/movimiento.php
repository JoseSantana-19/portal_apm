<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Movimiento interno | APM</title>
    <?php require ROOT . '/shared/head_assets.php'; ?>
</head>
<body>
<div class="app">
    <?php require ROOT . '/shared/menu.php'; ?>
    <section class="content">
        <?php $topbarTitle='Movimiento interno';$topbarSubtitle='Sin Acción de Personal';$topbarShowSearch=false;$topbarBackUrl=BASE_URL.'/talento-humano/directorio?modo=movimiento';$topbarBackLabel='Volver';require ROOT.'/shared/topbar.php'; ?>
        <main class="main"><div class="content-shell">
            <section class="card">
                <div class="card-header"><div><h3>Mover servidor a otra área</h3><p>Actualiza la asignación y conserva el historial, sin generar documento legal.</p></div></div>
                <form class="movement-grid" method="post" action="<?= BASE_URL ?>/talento-humano/empleado/mover">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
                    <input type="hidden" name="empleado_id" value="<?= (int)$empleado['empleado_id'] ?>">
                    <div class="movement-current">
                        <strong><?= htmlspecialchars(trim(($empleado['apellidos'] ?? '') . ' ' . ($empleado['nombres'] ?? ''))) ?></strong>
                        <span>C.I. <?= htmlspecialchars($empleado['cedula'] ?? '') ?></span>
                        <span>Área actual: <?= htmlspecialchars($empleado['direccion_area'] ?? 'Sin asignar') ?></span>
                        <span>Cargo actual: <?= htmlspecialchars($empleado['cargo'] ?? 'Sin asignar') ?></span>
                    </div>
                    <div class="movement-field"><label for="fecha_movimiento">Fecha efectiva</label><input id="fecha_movimiento" name="fecha_movimiento" type="date" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="movement-field"><label for="unidad_destino_id">Área / Dirección de destino</label><select id="unidad_destino_id" name="unidad_destino_id" required><option value="">Seleccione...</option><?php foreach ($areas as $area): ?><option value="<?= (int)$area['unidad_id'] ?>"><?= htmlspecialchars(($area['unidad_padre_id'] ? '↳ ' : '') . $area['nombre_unidad']) ?></option><?php endforeach; ?></select></div>
                    <div class="movement-field"><label for="puesto_destino_id">Denominación / Cargo de destino</label><select id="puesto_destino_id" name="puesto_destino_id" required><option value="">Seleccione...</option><?php foreach ($cargos as $cargo): ?><option value="<?= (int)$cargo['puesto_id'] ?>"><?= htmlspecialchars($cargo['nombre_puesto']) ?></option><?php endforeach; ?></select></div>
                    <div class="movement-field" style="grid-column:1/-1"><label for="motivo">Motivo del movimiento</label><textarea id="motivo" name="motivo" rows="4" maxlength="500" required placeholder="Detalle la necesidad institucional y observaciones..."></textarea></div>
                    <div class="movement-actions"><a class="btn btn-outline" href="<?= BASE_URL ?>/talento-humano/directorio">Cancelar</a><button class="btn btn-primary" type="submit"><i class="bi bi-arrow-left-right"></i> Registrar movimiento</button></div>
                </form>
            </section>
        </div></main>
    </section>
</div>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
