<?php
/**
 * ACC_USUARIOS.PHP - Vista de Gestión de Usuarios y Accesos
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
?>

<style>
.time-settings-panel { overflow:hidden; border:1px solid rgba(37,99,235,.12); box-shadow:0 12px 34px rgba(15,23,42,.055); }
.time-settings-header {
    display:flex; align-items:center; justify-content:space-between; gap:16px; padding:20px 24px;
    background:linear-gradient(135deg,rgba(37,99,235,.075),rgba(14,165,233,.025)); border-bottom:1px solid var(--border-color);
}
.time-settings-title { display:flex; align-items:center; gap:13px; }
.time-settings-title-icon {
    width:44px; height:44px; display:grid; place-items:center; flex:0 0 auto; border-radius:13px;
    color:var(--primary); background:rgba(37,99,235,.11); font-size:18px;
}
.time-settings-title h3 { margin:0 0 3px; font-size:17px; color:var(--text-color); }
.time-settings-title p { margin:0; color:var(--text-muted); font-size:12px; }
.time-settings-badge {
    display:inline-flex; align-items:center; gap:7px; padding:7px 11px; border-radius:999px;
    background:rgba(16,185,129,.1); color:#047857; font-size:11px; font-weight:750; white-space:nowrap;
}
.time-settings-body { padding:24px; }
.time-settings-info {
    display:flex; align-items:flex-start; gap:12px; margin-bottom:20px; padding:14px 16px;
    border:1px solid rgba(59,130,246,.13); border-radius:12px; background:rgba(59,130,246,.035);
    color:var(--text-muted); font-size:13px; line-height:1.55;
}
.time-settings-info i { margin-top:2px; color:var(--primary); }
.time-settings-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
.time-setting-card {
    min-width:0; padding:18px; border:1px solid var(--border-color); border-radius:14px;
    background:var(--panel-bg); transition:border-color .2s ease, box-shadow .2s ease, transform .2s ease;
}
.time-setting-card:focus-within { border-color:rgba(37,99,235,.45); box-shadow:0 0 0 3px rgba(37,99,235,.08); transform:translateY(-1px); }
.time-setting-card-head { display:flex; align-items:center; gap:10px; margin-bottom:14px; }
.time-setting-card-icon {
    width:36px; height:36px; display:grid; place-items:center; flex:0 0 auto; border-radius:10px; font-size:14px;
}
.time-setting-card.blue .time-setting-card-icon { color:#2563eb; background:rgba(37,99,235,.1); }
.time-setting-card.amber .time-setting-card-icon { color:#d97706; background:rgba(245,158,11,.12); }
.time-setting-card.green .time-setting-card-icon { color:#059669; background:rgba(16,185,129,.11); }
.time-setting-card label { display:block; min-width:0; color:var(--text-color); font-size:13px; font-weight:750; line-height:1.3; }
.time-setting-card select {
    width:100%; height:42px; padding:0 12px; border:1px solid var(--border-color); border-radius:9px;
    background:var(--input-bg); color:var(--text-color); font-weight:650; cursor:pointer;
}
.time-setting-card small { display:block; min-height:34px; margin-top:9px; color:var(--text-muted); font-size:11px; line-height:1.45; }
.time-settings-footer {
    display:flex; align-items:center; justify-content:space-between; gap:16px; margin-top:20px; padding-top:18px;
    border-top:1px solid var(--border-color);
}
.time-settings-summary { display:flex; align-items:center; gap:8px; color:var(--text-muted); font-size:12px; }
.time-settings-save { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:42px; padding:10px 18px; }
.users-list-tools,.users-pagination { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.users-list-tools form { margin:0; }
.users-list-tools select { padding:7px 10px; border:1px solid var(--border-color); border-radius:8px; background:var(--input-bg); color:var(--text-color); }
.users-pagination { padding:14px 20px; border-top:1px solid var(--border-color); background:var(--secondary-bg); }
.users-pagination-info,.users-pagination-page { color:var(--text-muted); font-size:12px; }
.users-pagination-actions { display:flex; align-items:center; gap:10px; }
@media (max-width:1050px) { .time-settings-grid { grid-template-columns:1fr 1fr; } .time-setting-card.green { grid-column:1 / -1; } }
@media (max-width:700px) {
    .time-settings-header,.time-settings-footer { align-items:flex-start; flex-direction:column; }
    .time-settings-badge { display:none; }
    .time-settings-body { padding:18px; }
    .time-settings-grid { grid-template-columns:1fr; }
    .time-setting-card.green { grid-column:auto; }
    .time-settings-save { width:100%; }
}
</style>

<!-- InvCabecera de Página -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1>Gestión de Usuarios</h1>
        <p>Controla el acceso de los operadores, supervisores y auditores del sistema. Configura sus roles operativos e inhabilita cuentas temporales.</p>
    </div>
    <div>
        <?php if (!empty($esAdmin)): ?>
        <span class="status-badge active"><i class="fa-solid fa-arrows-rotate"></i> Sincronizado con Talento Humano</span>
        <?php else: ?>
        <span class="status-badge transit"><i class="fa-solid fa-lock"></i> Solo consulta</span>
        <?php endif; ?>
    </div>
</div>

<!-- Listado de Usuarios -->
<div class="panel animate-fade-in" style="margin-bottom: 24px;">
    <div class="panel-header users-list-tools">
        <h3>Cuentas del Sistema (<?= (int)$paginacion['total'] ?> usuarios)</h3>
        <form method="GET" action="index.php">
            <input type="hidden" name="route" value="usuarios">
            <label for="usuarios-por-pagina" style="font-size:12px;color:var(--text-muted);">Mostrar</label>
            <select id="usuarios-por-pagina" name="por_pagina" onchange="this.form.submit()">
                <?php foreach ([25, 50, 100] as $cantidad): ?>
                    <option value="<?= $cantidad ?>" <?= (int)$paginacion['por_pagina'] === $cantidad ? 'selected' : '' ?>><?= $cantidad ?> usuarios</option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 120px;">Secuencial</th>
                    <th>Nombre de Cuenta</th>
                    <th>Cédula (Login)</th>
                    <th>Rol / Permisos</th>
                    <th>Inactividad</th>
                    <th>Estado de Acceso</th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:var(--text-muted);">
                            <i class="fa-solid fa-user-lock" style="font-size:32px; display:block; margin-bottom:12px; opacity:0.4;"></i>
                            No hay cuentas de usuario creadas
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $usr): 
                        // Badge para roles
                        $rolClass = 'status-badge';
                        if ($usr['rol'] === 'Administrador') $rolClass .= ' active';
                        elseif ($usr['rol'] === 'Supervisor') $rolClass .= ' transit';
                        elseif ($usr['rol'] === 'Auditor') $rolClass .= ' pending';
                        else $rolClass .= ' dispatched';
                    ?>
                        <tr>
                            <td class="secuencial-cell"><?= htmlspecialchars($usr['secuencial']) ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div class="user-avatar" style="width:36px;height:36px;font-size:13px;border-radius:10px;font-weight:700;display:flex;align-items:center;justify-content:center;background:rgba(59,130,246,0.1);color:var(--primary);">
                                        <?= strtoupper(substr($usr['nombre'], 0, 2)) ?>
                                    </div>
                                    <strong style="color:var(--text-color);"><?= htmlspecialchars($usr['nombre']) ?></strong>
                                </div>
                            </td>
                            <td>
                                <code style="background:rgba(59, 130, 246, 0.08); padding:6px 10px; border-radius:8px; font-weight:600; color:var(--primary); font-family: monospace; font-size:13px;">
                                    <?= htmlspecialchars($usr['usuario']) ?>
                                </code>
                            </td>
                            <td><span class="<?= $rolClass ?>"><?= htmlspecialchars($usr['rol']) ?></span></td>
                            <td>
                                <?php if (!array_key_exists('tiempo_inactividad', $usr) || $usr['tiempo_inactividad'] === null): ?>
                                    <span class="status-badge transit">Hereda <?= max(1, (int)$tiempoInactividad / 60) ?> min</span>
                                <?php else: ?>
                                    <span class="status-badge active"><?= max(1, (int)$usr['tiempo_inactividad'] / 60) ?> min</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int)$usr['activo'] === 1): ?>
                                    <span class="status-badge active"><i class="fa-solid fa-circle-check"></i> Activo / Habilitado</span>
                                <?php else: ?>
                                    <span class="status-badge inactive"><i class="fa-solid fa-ban"></i> Suspendido / Desactivado</span>
                                <?php endif; ?>
                            </td>
                            <td class="acciones-cell columna-acciones">
                                <?php if (!empty($esAdmin)): ?>
                                <button class="btn-accion btn-editar" onclick="editarUsuario(<?= htmlspecialchars(json_encode($usr)) ?>)" title="Editar Perfil y Rol"><i class="fa-solid fa-pen"></i></button>
                                <?php else: ?>
                                <span class="status-badge transit" title="Solo el Administrador puede modificar cuentas"><i class="fa-solid fa-lock"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
        $paginaActual = (int)$paginacion['pagina'];
        $totalPaginas = (int)$paginacion['total_paginas'];
        $porPagina = (int)$paginacion['por_pagina'];
        $desde = (int)$paginacion['total'] > 0 ? (($paginaActual - 1) * $porPagina) + 1 : 0;
        $hasta = min((int)$paginacion['total'], $paginaActual * $porPagina);
        $urlPagina = 'index.php?route=usuarios&por_pagina=' . $porPagina . '&pagina=';
    ?>
    <div class="users-pagination">
        <span class="users-pagination-info">Mostrando <?= $desde ?>–<?= $hasta ?> de <strong><?= (int)$paginacion['total'] ?></strong> usuarios</span>
        <div class="users-pagination-actions">
            <?php if ($paginaActual > 1): ?>
                <a class="btn-outline" style="text-decoration:none;padding:7px 10px;" href="<?= htmlspecialchars($urlPagina . ($paginaActual - 1)) ?>"><i class="fa-solid fa-chevron-left"></i> Anterior</a>
            <?php endif; ?>
            <span class="users-pagination-page">Página <?= $paginaActual ?> de <?= $totalPaginas ?></span>
            <?php if ($paginaActual < $totalPaginas): ?>
                <a class="btn-outline" style="text-decoration:none;padding:7px 10px;" href="<?= htmlspecialchars($urlPagina . ($paginaActual + 1)) ?>">Siguiente <i class="fa-solid fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Configuración de sesión y carga de inventario -->
<?php if (!empty($esAdmin)): ?>
<div class="panel time-settings-panel animate-fade-in" style="margin-bottom:24px;">
    <div class="time-settings-header">
        <div class="time-settings-title">
            <div class="time-settings-title-icon"><i class="fa-solid fa-user-clock"></i></div>
            <div>
                <h3>Tiempos de sesión y visualización</h3>
                <p>Seguridad de acceso y consultas bajo demanda</p>
            </div>
        </div>
        <span class="time-settings-badge"><i class="fa-solid fa-shield-halved"></i> Valor predeterminado</span>
    </div>
    <div class="time-settings-body">
        <div class="time-settings-info">
            <i class="fa-solid fa-circle-info"></i>
            <span>El aviso protege las sesiones desatendidas. Inventario General mantiene sus datos mientras se trabaja en el apartado y solo libera la consulta después de permanecer fuera durante el tiempo seleccionado.</span>
        </div>
        <form action="index.php?route=usuarios&action=guardarParametro" method="POST">
            <div class="time-settings-grid">
                <div class="time-setting-card blue">
                    <div class="time-setting-card-head">
                        <div class="time-setting-card-icon"><i class="fa-solid fa-person-circle-question"></i></div>
                        <label for="tiempo-inactividad">Avisar después de inactividad</label>
                    </div>
                    <select id="tiempo-inactividad" name="tiempo_inactividad" required>
                        <?php foreach ([60 => '1 minuto', 300 => '5 minutos', 600 => '10 minutos', 900 => '15 minutos', 1800 => '30 minutos'] as $segundos => $etiqueta): ?>
                            <option value="<?= $segundos ?>" <?= (int)$tiempoInactividad === $segundos ? 'selected' : '' ?>><?= $etiqueta ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Tiempo sin actividad antes de preguntar si el operador continúa presente.</small>
                </div>

                <div class="time-setting-card amber">
                    <div class="time-setting-card-head">
                        <div class="time-setting-card-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                        <label for="tiempo-gracia">Tolerancia fija para responder</label>
                    </div>
                    <select id="tiempo-gracia" disabled aria-disabled="true">
                        <option value="300" selected>5 minutos</option>
                    </select>
                    <small>Siempre habrá 5 minutos después del aviso antes de cerrar la sesión.</small>
                </div>

                <div class="time-setting-card green">
                    <div class="time-setting-card-head">
                        <div class="time-setting-card-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                        <label for="tiempo-inventario">Tiempo fuera de Inventario General</label>
                    </div>
                    <select id="tiempo-inventario" name="tiempo_vigencia_inventario" required>
                        <?php foreach ([300 => '5 minutos', 600 => '10 minutos', 900 => '15 minutos', 1800 => '30 minutos', 3600 => '60 minutos'] as $segundos => $etiqueta): ?>
                            <option value="<?= $segundos ?>" <?= (int)$tiempoVigenciaInventario === $segundos ? 'selected' : '' ?>><?= $etiqueta ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small>Solo comienza a contar al salir del apartado o dejar de visualizarlo.</small>
                </div>
            </div>
            <div class="time-settings-footer">
                <span class="time-settings-summary"><i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Los cambios se aplicarán a las sesiones activas en su siguiente navegación.</span>
                <button type="submit" class="btn-primary time-settings-save">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar configuración
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal: Registro / Edición de InvUsuario -->
<?php if (!empty($esAdmin)): ?>
<div class="modal-overlay" id="usuario-modal">
    <div class="modal-content" style="max-width: 440px;">
        <div class="modal-header">
            <h2 id="usuario-modal-title">Registrar Usuario</h2>
            <button class="modal-close" onclick="cerrarModalUsuario()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="index.php?route=usuarios&action=guardar" method="POST">
            <input type="hidden" name="id" id="usr-inp-id" value="0">
            <div class="modal-body">
                <div class="form-group">
                    <label>Nombre del Operador / Auditor</label>
                    <input type="text" name="nombre" id="usr-inp-nombre" required placeholder="Ej: Diana Elizabeth Torres">
                </div>

                <div class="form-group">
                    <label>Cédula (Login)</label>
                    <input type="text" name="usuario" id="usr-inp-usuario" required placeholder="Cédula del funcionario" pattern="^[a-zA-Z0-9._-]+$" title="Cédula del funcionario o usuario local del administrador.">
                </div>

                <div class="form-group">
                    <label id="usr-lbl-contrasena">Contraseña de Acceso</label>
                    <input type="password" name="contrasena" id="usr-inp-contrasena" placeholder="Contraseña de acceso seguro">
                    <small id="usr-help-contrasena" style="color: var(--text-muted); font-size: 11px; display: block; margin-top: 4px;"></small>
                </div>

                <div class="form-group">
                    <label>Rol Funcional (Permisos)</label>
                    <select name="rol" id="usr-inp-rol" required>
                        <option value="Administrador">Administrador (Control Total)</option>
                        <option value="Supervisor">Supervisor (Aprobaciones de Cierre)</option>
                        <option value="Operador" selected>Operador (Edición y Carga)</option>
                        <option value="Auditor">Auditor (Consulta y Descarga CSV)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tiempo de inactividad de este usuario</label>
                    <select name="tiempo_inactividad_usuario" id="usr-inp-tiempo-inactividad">
                        <option value="">Usar tiempo global (<?= max(1, (int)$tiempoInactividad / 60) ?> minutos)</option>
                        <?php foreach ([60 => '1 minuto', 300 => '5 minutos', 600 => '10 minutos', 900 => '15 minutos', 1800 => '30 minutos', 3600 => '1 hora', 7200 => '2 horas', 14400 => '4 horas'] as $segundos => $etiqueta): ?>
                            <option value="<?= $segundos ?>"><?= $etiqueta ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color:var(--text-muted);font-size:11px;display:block;margin-top:4px;">Al cumplirse este tiempo aparecerá el aviso; después tendrá 5 minutos para responder.</small>
                </div>

                <div class="form-group" id="usr-group-activo">
                    <label>Estado de Cuenta</label>
                    <select name="activo" id="usr-inp-activo">
                        <option value="1">Habilitado (Permitir Ingreso)</option>
                        <option value="0">Suspendido (Bloquear Ingreso)</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="cerrarModalUsuario()">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
<?php if (!empty($esAdmin)): ?>
    function abrirModalUsuario() {
        document.getElementById('usuario-modal-title').textContent = 'Registrar Usuario';
        document.getElementById('usr-inp-id').value = '0';
        document.getElementById('usr-inp-nombre').value = '';
        document.getElementById('usr-inp-usuario').value = '';
        document.getElementById('usr-inp-contrasena').value = '';
        document.getElementById('usr-inp-contrasena').required = true;
        document.getElementById('usr-lbl-contrasena').innerHTML = 'Contraseña de Acceso <span style="color:var(--danger)">*</span>';
        document.getElementById('usr-help-contrasena').textContent = 'Obligatoria para la creación de la cuenta.';
        document.getElementById('usr-inp-rol').value = 'Operador';
        document.getElementById('usr-inp-tiempo-inactividad').value = '';
        document.getElementById('usr-group-activo').style.display = 'none';
        document.getElementById('usr-inp-activo').value = '1';
        document.getElementById('usuario-modal').classList.add('active');
    }

    function cerrarModalUsuario() {
        document.getElementById('usuario-modal').classList.remove('active');
    }

    function editarUsuario(usr) {
        document.getElementById('usuario-modal-title').textContent = 'Editar Usuario';
        document.getElementById('usr-inp-id').value = usr.id;
        document.getElementById('usr-inp-nombre').value = usr.nombre;
        document.getElementById('usr-inp-usuario').value = usr.usuario || '';
        const cuentaTalento = String(usr.secuencial || '').startsWith('TH-');
        document.getElementById('usr-inp-nombre').readOnly = cuentaTalento;
        document.getElementById('usr-inp-usuario').readOnly = cuentaTalento;
        document.getElementById('usr-inp-contrasena').value = '';
        document.getElementById('usr-inp-contrasena').required = false;
        document.getElementById('usr-lbl-contrasena').innerHTML = 'Contraseña (Dejar en blanco para conservar la actual)';
        document.getElementById('usr-help-contrasena').textContent = 'Solo llene este campo si desea restablecer o cambiar la contraseña actual.';
        document.getElementById('usr-inp-rol').value = usr.rol;
        document.getElementById('usr-inp-tiempo-inactividad').value = usr.tiempo_inactividad || '';
        document.getElementById('usr-group-activo').style.display = 'block';
        document.getElementById('usr-inp-activo').value = usr.activo;
        document.getElementById('usuario-modal').classList.add('active');
    }
<?php endif; ?>
</script>
