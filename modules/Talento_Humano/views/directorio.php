<?php
/** Directorio de Personal — fragmento SPA (BD Talento_Humano). */
$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$empleados = $empleados ?? [];
$rbu = $rbu_vigente ?? '460.00';
$okMsg = SessionHelper::getFlash('success');
$errMsg = SessionHelper::getFlash('error');
$termino = trim($_GET['termino'] ?? '');
if ($termino !== '') {
    $empleados = array_values(array_filter($empleados, function ($x) use ($termino) {
        $hay = mb_strtolower(($x['nombres'] ?? '') . ' ' . ($x['apellidos'] ?? '') . ' ' . ($x['cedula'] ?? '') . ' ' . ($x['cargo'] ?? ''));
        return mb_strpos($hay, mb_strtolower($termino)) !== false;
    }));
}
$activos = count(array_filter($empleados, fn($x) => (int)($x['estado'] ?? 0) === 1));
?>
<div style="animation:pageFadeIn .35s ease-out;">

    <?php if ($okMsg): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $e($okMsg) ?></div><?php endif; ?>
    <?php if ($errMsg): ?><div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?= $e($errMsg) ?></div><?php endif; ?>

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-3);margin-bottom:var(--sp-5);">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--primary-app),var(--primary-hover));display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px color-mix(in srgb,var(--primary-hover) 35%,transparent);">
                <i class="fa-solid fa-address-book" style="color:#fff;font-size:18px;"></i>
            </div>
            <div>
                <h2 style="font-size:1.35rem;font-weight:800;color:var(--text-app);margin:0;line-height:1.2;">
                    Directorio de Personal
                    <span class="badge badge-info" style="font-size:.72rem;vertical-align:middle;margin-left:6px;"><?= count($empleados) ?></span>
                </h2>
                <p style="font-size:.78rem;color:var(--text-muted);margin:2px 0 0;">
                    <?= $activos ?> activos · RBU vigente $<?= $e($rbu) ?>
                </p>
            </div>
        </div>
        <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;">
            <a href="<?= APP_URL ?>/th/accion-personal" class="btn btn-ghost" data-spa style="gap:8px;">
                <i class="fa-solid fa-file-signature"></i> Acción de Personal
            </a>
            <a href="<?= APP_URL ?>/th/empleado/nuevo" class="btn btn-primary" data-spa style="gap:8px;">
                <i class="fa-solid fa-user-plus"></i> Nuevo Funcionario
            </a>
        </div>
    </div>

    <!-- Filtro -->
    <div class="card" style="margin-bottom:var(--sp-5);">
        <div class="card-body">
            <form method="GET" action="<?= APP_URL ?>/th/directorio" style="display:flex;gap:var(--sp-3);flex-wrap:wrap;align-items:flex-end;">
                <div class="form-group" style="flex:1;min-width:220px;margin:0;">
                    <label class="form-label"><i class="fa-solid fa-magnifying-glass" style="margin-right:4px;opacity:.6;"></i> Buscar</label>
                    <input type="text" name="termino" class="form-control" placeholder="Nombre, cédula o cargo..." value="<?= $e($termino) ?>">
                </div>
                <div style="display:flex;gap:var(--sp-2);padding-bottom:1px;">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i> Filtrar</button>
                    <a href="<?= APP_URL ?>/th/directorio" class="btn btn-ghost" data-spa><i class="fa-solid fa-rotate-left"></i> Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card">
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Cédula</th><th>Funcionario</th><th>Cargo</th><th>Dirección / Área</th><th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($empleados)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:var(--sp-12) 0;color:var(--text-muted);">
                        <i class="fa-regular fa-folder-open" style="font-size:2.5rem;display:block;margin-bottom:var(--sp-3);opacity:.3;"></i>
                        <strong style="display:block;font-size:1rem;color:var(--text-app);margin-bottom:4px;">Sin funcionarios</strong>
                        No hay registros que coincidan con la búsqueda.
                    </td></tr>
                <?php else: foreach ($empleados as $emp):
                    $id = (int)($emp['id'] ?? 0);
                    $full = trim(($emp['apellidos'] ?? '') . ' ' . ($emp['nombres'] ?? ''));
                    $words = array_values(array_filter(explode(' ', trim(($emp['nombres'] ?? '') . ' ' . ($emp['apellidos'] ?? '')))));
                    $ini = mb_strtoupper((mb_substr($words[0] ?? '', 0, 1)) . (mb_substr($words[1] ?? '', 0, 1)));
                    $colors = ['#1A3A5C','#0891B2','#7C3AED','#059669','#D97706','#DC2626'];
                    $ac = $colors[abs(crc32((string)($emp['cedula'] ?? 'x'))) % count($colors)];
                    $activo = (int)($emp['estado'] ?? 0) === 1;
                ?>
                    <tr>
                        <td><code style="font-family:var(--font-code);font-size:.78rem;color:var(--text-muted);background:var(--accent-app);padding:2px 6px;border-radius:4px;"><?= $e($emp['cedula']) ?></code></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:34px;height:34px;border-radius:9px;background:<?= $ac ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;"><?= $e($ini) ?></div>
                                <a href="<?= APP_URL ?>/th/empleado/<?= $id ?>/perfil" data-spa style="color:var(--primary-hover);font-weight:600;font-size:.875rem;text-decoration:none;"><?= $e($full) ?></a>
                            </div>
                        </td>
                        <td style="color:var(--text-muted);font-size:.83rem;"><?= $e($emp['cargo'] ?: '—') ?></td>
                        <td style="font-size:.83rem;"><?= $e($emp['direccion_area'] ?: '—') ?></td>
                        <td><span class="badge <?= $activo ? 'badge-success' : 'badge-danger' ?>"><i class="fa-solid fa-circle" style="font-size:5px;vertical-align:middle;"></i> <?= $activo ? 'Activo' : 'Inactivo' ?></span></td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:4px;justify-content:flex-end;">
                                <a href="<?= APP_URL ?>/th/empleado/<?= $id ?>/perfil" class="btn btn-ghost btn-sm" data-spa title="Ver expediente"><i class="fa-solid fa-eye"></i></a>
                                <a href="<?= APP_URL ?>/th/empleado/<?= $id ?>/editar" class="btn btn-ghost btn-sm" data-spa title="Editar"><i class="fa-solid fa-pencil"></i></a>
                                <a href="<?= APP_URL ?>/th/accion-personal?id=<?= $id ?>" class="btn btn-ghost btn-sm" data-spa title="Acción de Personal"><i class="fa-solid fa-file-signature"></i></a>
                                <a href="<?= APP_URL ?>/th/empleado/ficha?id=<?= $id ?>" class="btn btn-ghost btn-sm" target="_blank" rel="noopener" title="Ficha PDF"><i class="fa-solid fa-file-pdf"></i></a>
                                <?php if (!empty($esAdmin)):
                                    $tiene = isset($conCuenta[$emp['cedula']]); ?>
                                    <?php if ($tiene): ?>
                                        <span class="btn btn-ghost btn-sm" title="Ya tiene cuenta de acceso" style="color:var(--success,#28a745);cursor:default;"><i class="fa-solid fa-user-check"></i></span>
                                    <?php else: ?>
                                        <a href="<?= APP_URL ?>/th/empleado/<?= $id ?>/cuenta" class="btn btn-ghost btn-sm" data-spa title="Crear cuenta de acceso"><i class="fa-solid fa-user-shield"></i></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <form method="POST" action="<?= APP_URL ?>/th/empleado/eliminar" style="display:inline;" onsubmit="return confirm('¿Dar de baja a <?= $e(addslashes($full)) ?>?');">
                                    <?= SecurityHelper::csrfField() ?>
                                    <input type="hidden" name="empleado_id" value="<?= $id ?>">
                                    <button type="submit" class="btn btn-ghost btn-sm" title="Dar de baja" style="color:var(--danger,#dc3545);"><i class="fa-solid fa-user-slash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
