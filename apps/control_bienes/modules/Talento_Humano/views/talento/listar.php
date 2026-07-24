<?php
/**
 * TALENTO_HUMANO.PHP - Vista de Gestión del Personal y Reasignación de Áreas
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
?>

<!-- InvCabecera de Página -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1>Talento Humano</h1>
        <p>Directorio de operadores, supervisores y auditores del puerto. Reasigna al personal de departamento manteniendo un registro exacto de las fechas de inicio y fin de cada rol.</p>
    </div>
    <div>
        <button class="btn-primary" onclick="abrirModalPersonal()"><i class="fa-solid fa-user-plus"></i> Registrar Empleado</button>
    </div>
</div>

<!-- Listado del Personal -->
<div class="panel animate-fade-in" style="margin-bottom: 24px;">
    <div class="panel-header">
        <h3>Directorio Activo (<?= count($personal) ?> empleados)</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Nombre Completo</th>
                    <th>Cédula / Identificación</th>
                    <th>Área de Trabajo Actual</th>
                    <th>Asignado Desde</th>
                    <th>Historial de Áreas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($personal)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:var(--text-muted);">
                            <i class="fa-solid fa-users" style="font-size:32px; display:block; margin-bottom:12px; opacity:0.4;"></i>
                            No hay empleados registrados en el sistema
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($personal as $p): ?>
                        <tr>
                            <td><strong>#<?= $p['id'] ?></strong></td>
                            <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($p['nombre']) ?></td>
                            <td><?= htmlspecialchars($p['identificacion']) ?></td>
                            <td>
                                <span class="status-badge transit" style="background:rgba(59,130,246,0.1);color:var(--primary);font-weight:600;">
                                    <i class="fa-solid fa-network-wired"></i> <?= htmlspecialchars($p['area_actual'] ? $p['area_actual'] : 'Sin Asignación') ?>
                                </span>
                            </td>
                            <td><?= $p['asignado_desde'] ? $p['asignado_desde'] : '<span style="color:var(--text-muted);font-style:italic;">Indefinida</span>' ?></td>
                            <td>
                                <button class="btn-primary btn-sm" onclick="verHistorialPersonal(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nombre']) ?>')" style="background:rgba(59,130,246,0.1);color:var(--primary);border:1px solid rgba(59,130,246,0.2);">
                                    <i class="fa-solid fa-clock-rotate-left"></i> Ver Historial (Áreas)
                                </button>
                            </td>
                            <td class="acciones-cell">
                                <button class="btn-accion btn-ver" onclick="abrirModalReasignacion(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nombre']) ?>', <?= $p['area_id_actual'] ? $p['area_id_actual'] : 0 ?>)" title="Reasignar Área / Departamento" style="color:var(--warning);background:rgba(245,158,11,0.1);"><i class="fa-solid fa-right-left"></i> Reasignar</button>
                                <button class="btn-accion btn-editar" onclick="editarPersonal(<?= htmlspecialchars(json_encode($p)) ?>)" title="Editar Datos Personales"><i class="fa-solid fa-pen"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Registro / Edición de Personal -->
<div class="modal-overlay" id="personal-modal">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header">
            <h2 id="personal-modal-title">Registrar Empleado</h2>
            <button class="modal-close" onclick="cerrarModalPersonal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="index.php?route=talento&action=guardar" method="POST">
            <input type="hidden" name="id" id="pers-inp-id" value="0">
            <div class="modal-body">
                <div class="form-group">
                    <label>Nombre Completo</label>
                    <input type="text" name="nombre" id="pers-inp-nombre" required placeholder="Ej: Carlos Alfredo Mendoza">
                </div>

                <div class="form-group">
                    <label>Cédula de Identidad / Pasaporte</label>
                    <input type="text" name="identificacion" id="pers-inp-identificacion" required placeholder="Ej: 0958172635">
                </div>

                <div class="form-group" id="pers-group-area">
                    <label>Área de Asignación Inicial</label>
                    <select name="area_id" id="pers-inp-area" required>
                        <option value="">Seleccionar Área...</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="cerrarModalPersonal()">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reasignación de Área -->
<div class="modal-overlay" id="reasignar-modal">
    <div class="modal-content" style="max-width: 480px;">
        <div class="modal-header" style="background:rgba(245,158,11,0.03);border-bottom:1px solid rgba(245,158,11,0.1);">
            <h2 style="color:#d97706;"><i class="fa-solid fa-right-left"></i> Reasignar Área / Departamento</h2>
            <button class="modal-close" onclick="cerrarModalReasignacion()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="index.php?route=talento&action=reasignar" method="POST">
            <input type="hidden" name="personal_id" id="reasig-inp-personal-id" value="0">
            <div class="modal-body">
                <div style="background:rgba(59,130,246,0.05);padding:12px;border-radius:8px;margin-bottom:16px;border:1px solid rgba(59,130,246,0.1);">
                    Empleado: <strong id="reasig-txt-nombre" style="color:var(--primary);">...</strong>
                </div>
                
                <div class="form-group">
                    <label>Nueva Área de Trabajo</label>
                    <select name="area_id" id="reasig-inp-area" required>
                        <option value="">Seleccionar nueva área...</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Fecha de Cambio de Área (Transferencia)</label>
                    <input type="date" name="fecha_cambio" required value="<?= date('Y-m-d') ?>">
                    <span style="display:block;font-size:11px;color:var(--text-muted);margin-top:4px;">
                        El sistema cerrará automáticamente la asignación activa anterior con la fecha del cambio y abrirá una nueva asignación ininterrumpida.
                    </span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline" onclick="cerrarModalReasignacion()">Cancelar</button>
                <button type="submit" class="btn-primary" style="background:#f59e0b;"><i class="fa-solid fa-exchange-alt"></i> Confirmar Transferencia</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Historial de Áreas del Empleado -->
<div class="modal-overlay" id="historial-modal">
    <div class="modal-content" style="max-width: 640px;">
        <div class="modal-header">
            <h2>Historial de Movimientos de Departamento</h2>
            <button class="modal-close" onclick="cerrarHistorialPersonal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div style="background:rgba(59,130,246,0.05);padding:12px;border-radius:8px;margin-bottom:20px;border:1px solid rgba(59,130,246,0.1);">
                Colaborador: <strong id="hist-txt-nombre" style="color:var(--primary);">...</strong>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Área / Departamento</th>
                            <th>Fecha de Ingreso</th>
                            <th>Fecha de Salida / Fin</th>
                            <th>Estado de Asignación</th>
                        </tr>
                    </thead>
                    <tbody id="hist-tbl-body">
                        <!-- Cargado por AJAX dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function abrirModalPersonal() {
        document.getElementById('personal-modal-title').textContent = 'Registrar Empleado';
        document.getElementById('pers-inp-id').value = '0';
        document.getElementById('pers-inp-nombre').value = '';
        document.getElementById('pers-inp-identificacion').value = '';
        document.getElementById('pers-inp-area').value = '';
        document.getElementById('pers-group-area').style.display = 'block';
        document.getElementById('pers-inp-area').required = true;
        document.getElementById('personal-modal').classList.add('active');
    }

    function cerrarModalPersonal() {
        document.getElementById('personal-modal').classList.remove('active');
    }

    function editarPersonal(p) {
        document.getElementById('personal-modal-title').textContent = 'Editar Datos Personales';
        document.getElementById('pers-inp-id').value = p.id;
        document.getElementById('pers-inp-nombre').value = p.nombre;
        document.getElementById('pers-inp-identificacion').value = p.identificacion;
        // Ocultar combo de área en edición simple (las áreas se cambian por el botón especial Reasignar)
        document.getElementById('pers-group-area').style.display = 'none';
        document.getElementById('pers-inp-area').required = false;
        document.getElementById('personal-modal').classList.add('active');
    }

    function abrirModalReasignacion(id, nombre, areaId) {
        document.getElementById('reasig-inp-personal-id').value = id;
        document.getElementById('reasig-txt-nombre').textContent = nombre;
        document.getElementById('reasig-inp-area').value = areaId;
        document.getElementById('reasignar-modal').classList.add('active');
    }

    function cerrarModalReasignacion() {
        document.getElementById('reasignar-modal').classList.remove('active');
    }

    function cerrarHistorialPersonal() {
        document.getElementById('historial-modal').classList.remove('active');
    }

    function verHistorialPersonal(id, nombre) {
        document.getElementById('hist-txt-nombre').textContent = nombre;
        const tbody = document.getElementById('hist-tbl-body');
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:24px;"><i class="fa-solid fa-spinner fa-spin" style="font-size:24px;color:var(--primary);"></i><p style="margin-top:8px;">Buscando historial de reasignaciones...</p></td></tr>';
        document.getElementById('historial-modal').classList.add('active');

        fetch('index.php?route=talento&action=historialAjax&personal_id=' + id)
            .then(res => res.json())
            .then(logs => {
                if (logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted);">Sin historial de reasignación registrado</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                logs.forEach(l => {
                    const fin = l.fecha_fin ? l.fecha_fin : '<span style="color:#10b981;font-weight:600;"><i class="fa-solid fa-circle-check"></i> Vigente / Activo</span>';
                    const activeBadge = l.fecha_fin ? '<span class="status-badge inactive">Histórico</span>' : '<span class="status-badge active">Activo</span>';
                    
                    tbody.innerHTML += `
                        <tr>
                            <td><strong style="color:var(--text-color);">${l.area_nombre}</strong></td>
                            <td>${l.fecha_inicio}</td>
                            <td>${fin}</td>
                            <td>${activeBadge}</td>
                        </tr>
                    `;
                });
            });
    }
</script>
