<?php
$h = fn($val) => htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
$c = fn($campo, $default = '') => $h($config[$campo] ?? $default);
$modoActual = $config['modo_escritura'] ?? 'UPDATE_JOIN';
?>

<?php if ($success): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.success(<?= json_encode($success) ?>));</script>
<?php endif; ?>
<?php if ($error): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.error(<?= json_encode($error) ?>));</script>
<?php endif; ?>
<?php if (!empty($errors)): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.errorList('Corrige los errores', <?= json_encode(array_values($errors)) ?>));</script>
<?php endif; ?>

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-2);">
    <a href="<?= APP_URL ?>/admin/modulos" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Módulos
    </a>
</div>

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title">
            <i class="fa-solid fa-arrows-rotate" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
            Sincronización — <?= $h($modulo['nombre']) ?>
        </h2>
        <p class="page-subtitle">
            Cuando se guarda un cambio en <code>/admin/roles/{id}/permisos</code> para un rol mapeado a este módulo
            (<code>CORE_Roles_Modulo_Map</code>), esta configuración le dice al motor genérico
            (<code>SyncPermisosModulo::centralHaciaGenerico</code>) en qué tabla/columnas de
            <code><?= $h($modulo['conexion_bd'] ?: '— sin conexion_bd —') ?></code> reflejarlo. Sin ninguna fila acá,
            el rol se guarda igual en el portal — simplemente no se replica a este módulo.
        </p>
    </div>
</div>

<?php if (empty($modulo['conexion_bd'])): ?>
<div class="alert alert-warning" style="margin-bottom:var(--sp-4);">
    <i class="fa-solid fa-triangle-exclamation"></i>
    Este módulo no tiene <code>conexion_bd</code> configurado (<a href="<?= APP_URL ?>/admin/modulos/<?= (int)$modulo['id_modulo'] ?>/editar" data-spa>editarlo acá</a>).
    Sin eso, el sync no sabe a qué servidor conectarse aunque completes lo de abajo.
</div>
<?php endif; ?>

<form method="POST" action="<?= APP_URL ?>/admin/modulos/<?= (int)$modulo['id_modulo'] ?>/sync" style="max-width:760px;" id="syncForm">
    <input type="hidden" name="_csrf_token" value="<?= $h($csrf) ?>">

    <div class="card" style="margin-bottom:var(--sp-4);">
        <div class="card-header">
            <i class="fa-solid fa-table-cells" style="color:var(--color-primary);"></i>
            <span class="card-title">Tabla de permisos del módulo destino</span>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:var(--sp-4);">

            <div class="grid-2" style="gap:var(--sp-4);">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Tabla de permisos *</label>
                    <input type="text" name="tabla_permisos" class="form-control" required maxlength="128"
                           placeholder="th_permisos_rol" value="<?= $c('tabla_permisos') ?>">
                    <small style="color:var(--color-text-muted);">La tabla del módulo destino donde vive el permiso por rol.</small>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Columna rol_id *</label>
                    <input type="text" name="columna_rol_id" class="form-control" required maxlength="128"
                           placeholder="rol_id" value="<?= $c('columna_rol_id') ?>">
                </div>
            </div>

            <div class="form-group" style="margin:0;">
                <label class="form-label">Modo de escritura *</label>
                <select name="modo_escritura" id="modoEscritura" class="form-control" required>
                    <option value="UPDATE_JOIN"   <?= $modoActual === 'UPDATE_JOIN'   ? 'selected' : '' ?>>UPDATE_JOIN — la fila (rol, módulo) ya existe siempre, necesita JOIN para llegar al identificador</option>
                    <option value="MERGE_DIRECTO" <?= $modoActual === 'MERGE_DIRECTO' ? 'selected' : '' ?>>MERGE_DIRECTO — upsert directo, el identificador ya está en la misma tabla de permisos</option>
                </select>
                <small style="color:var(--color-text-muted);">
                    Ejemplo real UPDATE_JOIN: Talento Humano — <code>th_permisos_rol</code> solo tiene <code>modulo_id</code> numérico,
                    hace falta pasar por <code>th_modulos</code> para llegar a <code>codigo_modulo</code>.
                </small>
            </div>

            <div id="bloqueJoin" style="display:flex;flex-direction:column;gap:var(--sp-4);">
                <div class="grid-2" style="gap:var(--sp-4);">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Tabla de catálogo (JOIN)</label>
                        <input type="text" name="tabla_join" class="form-control" maxlength="128"
                               placeholder="th_modulos" value="<?= $c('tabla_join') ?>">
                    </div>
                    <div class="form-group" style="margin:0;"></div>
                </div>
                <div class="grid-2" style="gap:var(--sp-4);">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Columna de JOIN (lado permisos)</label>
                        <input type="text" name="columna_join_permisos" class="form-control" maxlength="128"
                               placeholder="modulo_id" value="<?= $c('columna_join_permisos') ?>">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Columna de JOIN (lado catálogo)</label>
                        <input type="text" name="columna_join_externa" class="form-control" maxlength="128"
                               placeholder="modulo_id" value="<?= $c('columna_join_externa') ?>">
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin:0;">
                <label class="form-label">Columna identificador *</label>
                <input type="text" name="columna_identificador" class="form-control" required maxlength="128"
                       placeholder="codigo_modulo (o route_key si es MERGE_DIRECTO)" value="<?= $c('columna_identificador') ?>">
                <small style="color:var(--color-text-muted);">
                    En UPDATE_JOIN vive en la tabla de catálogo. En MERGE_DIRECTO vive directo en la tabla de permisos.
                </small>
            </div>

            <div>
                <label class="form-label" style="margin-bottom:var(--sp-2);display:block;">Columnas de nivel de permiso</label>
                <div class="grid-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:var(--sp-3);">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.72rem;">Ver</label>
                        <input type="text" name="columna_visualizar" class="form-control" maxlength="128" value="<?= $c('columna_visualizar', 'puede_visualizar') ?>">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.72rem;">Crear</label>
                        <input type="text" name="columna_crear" class="form-control" maxlength="128" value="<?= $c('columna_crear', 'puede_crear') ?>">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.72rem;">Editar</label>
                        <input type="text" name="columna_editar" class="form-control" maxlength="128" value="<?= $c('columna_editar', 'puede_editar') ?>">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="font-size:0.72rem;">Eliminar</label>
                        <input type="text" name="columna_eliminar" class="form-control" maxlength="128" value="<?= $c('columna_eliminar', 'puede_eliminar') ?>">
                    </div>
                </div>
                <small style="color:var(--color-text-muted);">Se derivan del nivel 1-4 guardado en <code>CORE_Permisos_Nodo</code>: 1=ver, 2=+crear, 3=+editar, 4=+eliminar (cada nivel incluye los anteriores).</small>
            </div>

            <div class="form-group" style="margin:0;">
                <label class="form-label">Procedimiento de auditoría <span style="color:var(--color-text-muted);font-weight:normal;">(opcional)</span></label>
                <input type="text" name="sp_auditoria" class="form-control" maxlength="128"
                       placeholder="sp_th_registrar_auditoria" value="<?= $c('sp_auditoria') ?>">
                <small style="color:var(--color-text-muted);">
                    Si existe, se llama como <code>EXEC sp (@usuario, @modulo, @accion, @descripcion, @ip)</code> tras aplicar los cambios. Dejar vacío si el módulo no tiene uno con esa firma.
                </small>
            </div>

            <div class="form-group" style="margin:0;">
                <label class="form-label">Notas <span style="color:var(--color-text-muted);font-weight:normal;">(internas, no afectan la sincronización)</span></label>
                <textarea name="notas" class="form-control" rows="2" maxlength="500"><?= $c('notas') ?></textarea>
            </div>

            <label style="display:flex;align-items:center;gap:8px;font-weight:600;cursor:pointer;">
                <input type="checkbox" name="estado" value="1" <?= ($config['estado'] ?? 1) ? 'checked' : '' ?> style="width:18px;height:18px;">
                Sincronización activa
            </label>

        </div>
    </div>

    <div style="display:flex;gap:var(--sp-2);">
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> Guardar configuración
        </button>
    </div>
</form>

<?php if ($config): ?>
<div class="card" style="max-width:760px;margin-top:var(--sp-5);">
    <div class="card-header">
        <i class="fa-solid fa-diagram-project" style="color:var(--color-primary);"></i>
        <span class="card-title">Mapeo de nodos (opción del portal → identificador en el módulo)</span>
    </div>
    <div class="card-body">
        <p style="color:var(--color-text-muted);font-size:0.85rem;margin-top:0;">
            "Opción" es el mismo número que <code>CORE_Menu_Nodos.opcion</code> bajo <code>id_modulo=<?= (int)$modulo['id_modulo'] ?></code>
            (ver <a href="<?= APP_URL ?>/admin/menu" data-spa>Estructura del Menú</a> para ver las etiquetas reales de cada opción).
        </p>

        <table class="dash-table" style="margin-bottom:var(--sp-4);">
            <thead><tr><th>Opción</th><th>Identificador externo</th><th>Descripción</th><th style="text-align:right;">—</th></tr></thead>
            <tbody>
            <?php if (empty($nodos)): ?>
                <tr><td colspan="4" style="color:var(--color-text-muted);text-align:center;">Sin nodos mapeados todavía.</td></tr>
            <?php endif; ?>
            <?php foreach ($nodos as $n): ?>
                <tr>
                    <td><code><?= (int)$n['opcion'] ?></code></td>
                    <td><code><?= $h($n['identificador_externo']) ?></code></td>
                    <td style="color:var(--color-text-muted);"><?= $h($n['descripcion']) ?></td>
                    <td style="text-align:right;">
                        <form method="POST" action="<?= APP_URL ?>/admin/modulos/<?= (int)$modulo['id_modulo'] ?>/sync/nodo/<?= (int)$n['opcion'] ?>/eliminar"
                              style="display:inline;" onsubmit="return confirm('¿Eliminar el mapeo de la opción <?= (int)$n['opcion'] ?>?');">
                            <input type="hidden" name="_csrf_token" value="<?= $h($csrf) ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" title="Eliminar">
                                <i class="fa-solid fa-trash" style="color:var(--color-danger);"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <form method="POST" action="<?= APP_URL ?>/admin/modulos/<?= (int)$modulo['id_modulo'] ?>/sync/nodo" style="display:flex;gap:var(--sp-2);align-items:flex-end;flex-wrap:wrap;">
            <input type="hidden" name="_csrf_token" value="<?= $h($csrf) ?>">
            <div class="form-group" style="margin:0;width:90px;">
                <label class="form-label" style="font-size:0.72rem;">Opción *</label>
                <input type="number" name="opcion" class="form-control" required min="1">
            </div>
            <div class="form-group" style="margin:0;width:200px;">
                <label class="form-label" style="font-size:0.72rem;">Identificador externo *</label>
                <input type="text" name="identificador_externo" class="form-control" required maxlength="100" placeholder="codigo_modulo">
            </div>
            <div class="form-group" style="margin:0;flex:1;min-width:180px;">
                <label class="form-label" style="font-size:0.72rem;">Descripción</label>
                <input type="text" name="descripcion" class="form-control" maxlength="200" placeholder="solo para referencia">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Agregar</button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info" style="max-width:760px;margin-top:var(--sp-5);">
    <i class="fa-solid fa-circle-info"></i> Guardá la configuración de arriba primero — recién ahí se puede mapear nodos.
</div>
<?php endif; ?>

<script>
(function(){
    var sel = document.getElementById('modoEscritura');
    var bloque = document.getElementById('bloqueJoin');
    function actualizar(){ bloque.style.display = sel.value === 'UPDATE_JOIN' ? 'flex' : 'none'; }
    sel.addEventListener('change', actualizar);
    actualizar();
})();
</script>
