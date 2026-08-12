<?php
/**
 * ACC_USUARIOS.PHP - Vista de Gestión de Usuarios y Accesos
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
?>

<!-- InvCabecera de Página -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1>Gestión de Usuarios</h1>
        <p>Controla el acceso de los operadores, supervisores y auditores del sistema. Configura sus roles operativos e inhabilita cuentas temporales.</p>
    </div>
    <div>
        <button class="btn-primary" onclick="abrirModalUsuario()"><i class="fa-solid fa-user-shield"></i> Registrar Usuario</button>
    </div>
</div>

<!-- Listado de Usuarios -->
<div class="panel animate-fade-in" style="margin-bottom: 24px;">
    <div class="panel-header">
        <h3>Cuentas del Sistema (<?= count($usuarios) ?> usuarios)</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 120px;">Secuencial</th>
                    <th>Nombre de Cuenta</th>
                    <th>Usuario (Login)</th>
                    <th>Rol / Permisos</th>
                    <th>Estado de Acceso</th>
                    <th style="width: 120px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:var(--text-muted);">
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
                                <?php if ((int)$usr['activo'] === 1): ?>
                                    <span class="status-badge active"><i class="fa-solid fa-circle-check"></i> Activo / Habilitado</span>
                                <?php else: ?>
                                    <span class="status-badge inactive"><i class="fa-solid fa-ban"></i> Suspendido / Desactivado</span>
                                <?php endif; ?>
                            </td>
                            <td class="acciones-cell">
                                <button class="btn-accion btn-editar" onclick="editarUsuario(<?= htmlspecialchars(json_encode($usr)) ?>)" title="Editar Perfil y Rol"><i class="fa-solid fa-pen"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Configuración de Inactividad de Sesión -->
<div class="panel animate-fade-in" style="margin-bottom: 24px;">
    <div class="panel-header">
        <h3 style="display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-user-clock" style="color:var(--primary);"></i>
            Seguridad y Expiración de Sesión
        </h3>
    </div>
    <div style="padding: 24px; background: var(--panel-bg); border-radius: 0 0 16px 16px;">
        <div style="display:flex;align-items:flex-start;gap:16px;background:rgba(59,130,246,.06);border:1px dashed rgba(59,130,246,.25);border-radius:12px;padding:20px;">
            <i class="fa-solid fa-circle-info" style="color:var(--primary);font-size:20px;margin-top:2px;"></i>
            <div>
                <p style="color: var(--text-color); font-size: 14px; line-height: 1.6; margin: 0 0 6px;">
                    El tiempo de inactividad de este módulo ahora se administra de forma <strong>centralizada</strong>
                    junto con el resto del sistema (Talento Humano, Bitácoras y el Portal), para que no haya dos
                    valores distintos compitiendo entre sí.
                </p>
                <p style="color: var(--text-muted); font-size: 13px; line-height: 1.6; margin: 0 0 14px;">
                    Valor actual efectivo para este módulo: <strong><?= (int)($tiempoInactividad ?? 600) ?> segundos</strong>.
                </p>
                <?php if (defined('PORTAL_ROOT_URL')): ?>
                <a href="<?= PORTAL_ROOT_URL ?>/admin/inactividad" target="_blank" rel="noopener"
                   style="display:inline-flex;align-items:center;gap:8px;padding:9px 16px;border-radius:8px;background:var(--primary);color:#fff;font-weight:600;font-size:13px;text-decoration:none;">
                    <i class="fa-solid fa-hourglass-half"></i> Configurar en el Portal APM
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Registro / Edición de InvUsuario -->
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
                    <label>Nombre de Usuario (Login)</label>
                    <input type="text" name="usuario" id="usr-inp-usuario" required placeholder="Ej: diana.torres" pattern="^[a-zA-Z0-9._-]+$" title="Solo letras, números, puntos, guiones y guiones bajos.">
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

<script>
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
        document.getElementById('usr-inp-contrasena').value = '';
        document.getElementById('usr-inp-contrasena').required = false;
        document.getElementById('usr-lbl-contrasena').innerHTML = 'Contraseña (Dejar en blanco para conservar la actual)';
        document.getElementById('usr-help-contrasena').textContent = 'Solo llene este campo si desea restablecer o cambiar la contraseña actual.';
        document.getElementById('usr-inp-rol').value = usr.rol;
        document.getElementById('usr-group-activo').style.display = 'block';
        document.getElementById('usr-inp-activo').value = usr.activo;
        document.getElementById('usuario-modal').classList.add('active');
    }
</script>
