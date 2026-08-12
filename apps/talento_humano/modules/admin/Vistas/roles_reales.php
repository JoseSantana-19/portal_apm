<?php
$definicionCategorias = [
    'operativos' => ['titulo'=>'Módulos operativos','descripcion'=>'Gestión diaria del talento humano','icono'=>'bi-briefcase-fill'],
    'administracion' => ['titulo'=>'Administración y seguridad','descripcion'=>'Cuentas, roles y normativa','icono'=>'bi-shield-lock-fill'],
    'control' => ['titulo'=>'Control y cumplimiento','descripcion'=>'Reportes, trazabilidad y auditoría','icono'=>'bi-clipboard-data-fill'],
    'maestros' => ['titulo'=>'Tablas maestras','descripcion'=>'Catálogos y estructura institucional','icono'=>'bi-diagram-3-fill'],
];
$categorias = array_fill_keys(array_keys($definicionCategorias), []);
foreach ($modulos as $modulo) {
    $codigo = (string)$modulo['codigo'];
    $categoria = $codigo === 'maestros' ? 'maestros'
        : (in_array($codigo, ['usuarios','roles','politicas'], true) ? 'administracion'
        : (in_array($codigo, ['auditoria','reportes'], true) ? 'control' : 'operativos'));
    $categorias[$categoria][] = $modulo;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Roles y permisos | APM</title>
    <?php require ROOT.'/shared/head_assets.php'; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin_compact.css">
</head>
<body><div class="app">
<?php require ROOT.'/shared/menu.php'; ?>
<section class="content">
    <?php $topbarTitle='Roles y permisos';$topbarSubtitle='Matriz RBAC persistente';$topbarShowSearch=true;require ROOT.'/shared/topbar.php'; ?>
    <main class="main"><div class="content-shell admin-page">
        <section class="admin-section-head">
            <div><h1>Control de acceso</h1><p>Expanda únicamente el rol que desea revisar. Los cambios cierran sus sesiones activas.</p></div>
            <span class="admin-count-chip"><i class="bi bi-shield-check"></i><?= count($roles) ?> roles configurados</span>
        </section>

        <?php if(Auth::can('roles','crear')): ?>
        <form method="post" action="<?= BASE_URL ?>/admin/roles/crear" class="card rbac-create">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
            <div class="rbac-create-copy"><i class="bi bi-person-badge-fill"></i><span>Nuevo rol</span></div>
            <div class="field"><label for="nombreRol">NOMBRE DEL ROL</label><input id="nombreRol" name="nombre_rol" maxlength="80" required placeholder="Ej: Analista de Talento Humano"></div>
            <button class="btn btn-primary" type="submit"><i class="bi bi-plus-lg"></i> Crear rol</button>
        </form>
        <?php endif; ?>

        <div class="rbac-list" aria-label="Roles y matrices de permisos">
        <?php foreach($roles as $rol):
            $rolId=(int)$rol['id'];$esSuper=$rolId===1;$panelId='permisos-rol-'.$rolId;
        ?>
        <form method="post" action="<?= BASE_URL ?>/admin/roles/guardar-permisos" class="rbac-role-card" data-role-card data-role-id="<?= $rolId ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
            <input type="hidden" name="rol_id" value="<?= $rolId ?>">
            <button class="rbac-role-toggle" type="button" data-role-toggle aria-expanded="false" aria-controls="<?= $panelId ?>">
                <span class="rbac-role-icon"><i class="bi <?= $esSuper?'bi-shield-fill-check':'bi-person-badge' ?>"></i></span>
                <span class="rbac-role-main">
                    <span class="rbac-role-name"><?= htmlspecialchars($rol['nombre']) ?></span>
                    <span class="rbac-role-meta">
                        <span class="rbac-role-chip"><i class="bi bi-people"></i><?= (int)$rol['usuarios'] ?> usuario(s)</span>
                        <span class="rbac-role-chip <?= (int)$rol['estado']===1?'is-active':'is-inactive' ?>"><i class="bi <?= (int)$rol['estado']===1?'bi-check-circle':'bi-slash-circle' ?>"></i><?= (int)$rol['estado']===1?'Activo':'Inactivo' ?></span>
                        <?php if($esSuper): ?><span class="rbac-role-chip"><i class="bi bi-lock-fill"></i>Protegido</span><?php endif; ?>
                    </span>
                </span>
                <i class="bi bi-chevron-down rbac-chevron" aria-hidden="true"></i>
            </button>

            <div class="rbac-role-panel" id="<?= $panelId ?>" data-role-panel inert>
                <div class="rbac-role-panel-inner"><div class="rbac-role-content">
                    <div class="rbac-groups">
                    <?php foreach($definicionCategorias as $categoriaId=>$categoria): if(!$categorias[$categoriaId]) continue; ?>
                    <section class="rbac-group" data-permission-group aria-labelledby="<?= $panelId.'-'.$categoriaId ?>">
                        <header class="rbac-group-head">
                            <i class="bi <?= $categoria['icono'] ?>"></i>
                            <div class="rbac-group-title"><strong id="<?= $panelId.'-'.$categoriaId ?>"><?= htmlspecialchars($categoria['titulo']) ?></strong><small><?= htmlspecialchars($categoria['descripcion']) ?> · <?= count($categorias[$categoriaId]) ?> módulo(s)</small></div>
                            <label class="rbac-category-all"><input class="rbac-check" type="checkbox" data-category-toggle <?= $esSuper?'disabled':'' ?>><span>Todo el grupo</span></label>
                        </header>
                        <div class="rbac-table-wrap"><table class="rbac-table">
                            <thead><tr><th>Módulo</th><th>Todo</th><th>Ver</th><th>Crear</th><th>Editar</th><th>Eliminar</th></tr></thead>
                            <tbody>
                            <?php foreach($categorias[$categoriaId] as $m): $p=$matriz[$rolId][$m['id']]??[]; ?>
                            <tr>
                                <td><span class="rbac-module-name"><i class="bi bi-grid-1x2-fill"></i><?= htmlspecialchars($m['nombre']) ?></span></td>
                                <td class="rbac-row-all-cell"><input class="rbac-check" type="checkbox" data-row-toggle aria-label="Seleccionar todos los permisos de <?= htmlspecialchars($m['nombre']) ?>" <?= $esSuper?'disabled':'' ?>></td>
                                <?php foreach(['visualizar'=>'Ver','crear'=>'Crear','editar'=>'Editar','eliminar'=>'Eliminar'] as $accion=>$etiqueta): $campo='puede_'.$accion; ?>
                                <td><input class="rbac-check" type="checkbox" data-permission name="permisos[<?= (int)$m['id'] ?>][<?= $accion ?>]" aria-label="<?= $etiqueta ?> <?= htmlspecialchars($m['nombre']) ?>" <?= !empty($p[$campo])?'checked':'' ?> <?= $esSuper?'disabled':'' ?>></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table></div>
                    </section>
                    <?php endforeach; ?>
                    </div>

                    <div class="rbac-actions">
                        <span class="rbac-actions-note"><i class="bi bi-info-circle"></i> “Todo” selecciona las cuatro acciones del módulo.</span>
                        <?php if(!$esSuper): ?><div class="rbac-actions-buttons">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Guardar permisos</button>
                            <button class="btn btn-outline" type="submit" formaction="<?= BASE_URL ?>/admin/roles/estado" name="estado" value="<?= (int)$rol['estado']===1?0:1 ?>" onclick="return confirm('¿Cambiar el estado de este rol?')"><i class="bi <?= (int)$rol['estado']===1?'bi-pause-circle':'bi-play-circle' ?>"></i><?= (int)$rol['estado']===1?'Desactivar rol':'Activar rol' ?></button>
                        </div><?php endif; ?>
                    </div>
                </div></div>
            </div>
        </form>
        <?php endforeach; ?>
        </div>
    </div></main>
</section></div>
<script src="<?= BASE_URL ?>/public/js/rbac_roles.js"></script>
<script><?php if(!empty($_GET['msg'])): ?>addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_GET['msg']) ?>,<?= ($_GET['ok']??'0')==='1'?"'success'":"'error'" ?>));<?php endif; ?></script>
<?php require ROOT.'/shared/footer_scripts.php'; ?>
</body></html>
