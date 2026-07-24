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

<!-- Configuración de Inactividad de Sesión (Parámetros) -->
<div class="panel animate-fade-in" style="margin-bottom: 24px;">
    <div class="panel-header">
        <h3 style="display:flex;align-items:center;gap:10px;">
            <i class="fa-solid fa-user-clock" style="color:var(--primary);"></i>
            Seguridad y Expiración de Sesión (Parámetro Global)
        </h3>
    </div>
    <div style="padding: 24px; background: var(--panel-bg); border-radius: 0 0 16px 16px;">
        <div style="display: grid; grid-template-columns: 1fr 320px; gap: 40px; align-items: start;">
            <div>
                <h4 style="color: var(--text-color); margin-bottom: 8px; font-size: 16px;">¿Cómo funciona el tiempo de inactividad?</h4>
                <p style="color: var(--text-muted); font-size: 14px; line-height: 1.6; margin-bottom: 16px;">
                    Cuando un operador permanece inactivo (sin realizar peticiones, búsquedas o registrar transacciones) por más tiempo del permitido, su sesión es destruida automáticamente por el sistema y se le redirige a la pantalla de bloqueo.
                </p>
                <p style="color: var(--text-muted); font-size: 14px; line-height: 1.6;">
                    <strong>Recomendación:</strong> Para terminales portuarias operativas con alto tráfico, se sugiere configurar entre <strong>300 y 600 segundos</strong> (5 a 10 minutos). Para pruebas rápidas del flujo de relogin, puedes establecerlo en <strong>30 segundos</strong>.
                </p>
            </div>
            
            <div style="background: rgba(59, 130, 246, 0.04); border: 1px dashed rgba(59, 130, 246, 0.2); padding: 20px; border-radius: 12px;">
                <form action="index.php?route=usuarios&action=guardarParametro" method="POST">
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label style="display:block; margin-bottom:8px; font-weight:600; font-size:13px; color:var(--text-color);">Tiempo de Inactividad Permitido</label>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <input type="number" name="tiempo_inactividad" value="<?= htmlspecialchars($tiempoInactividad) ?>" min="10" required style="width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--input-bg); color: var(--text-color); font-weight: 600; text-align: center;">
                            <span style="font-weight: 600; color: var(--text-muted); font-size: 14px;">segundos</span>
                        </div>
                    </div>
                    
                    <div style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:16px;">
                        <button type="button" class="btn-outline" onclick="setTiempo(30)" style="padding: 6px 10px; font-size: 11px; border-radius: 6px; cursor:pointer;">30s (Test)</button>
                        <button type="button" class="btn-outline" onclick="setTiempo(60)" style="padding: 6px 10px; font-size: 11px; border-radius: 6px; cursor:pointer;">1 min</button>
                        <button type="button" class="btn-outline" onclick="setTiempo(300)" style="padding: 6px 10px; font-size: 11px; border-radius: 6px; cursor:pointer;">5 min</button>
                        <button type="button" class="btn-outline" onclick="setTiempo(600)" style="padding: 6px 10px; font-size: 11px; border-radius: 6px; cursor:pointer;">10 min</button>
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%; padding: 10px; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600; border-radius: 8px; cursor:pointer; border:none; color:white;">
                        <i class="fa-solid fa-clock-rotate-left"></i> Actualizar Parámetro
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function setTiempo(segundos) {
        const input = document.querySelector('input[name="tiempo_inactividad"]');
        if (input) {
            input.value = segundos;
        }
    }
</script>

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
