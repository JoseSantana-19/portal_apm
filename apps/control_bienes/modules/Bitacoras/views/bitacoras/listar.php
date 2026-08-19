<?php
/**
 * BIT_BITACORA.PHP - Vista de Bitácora del Sistema y Auditoría
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
?>
<style>
.audit-filter-grid{display:grid;grid-template-columns:repeat(4,minmax(180px,1fr));gap:13px;align-items:end;width:100%}.audit-filter-grid .filter-actions{display:flex;gap:7px}.audit-table{min-width:1160px}.audit-user strong,.audit-user small,.audit-origin strong,.audit-origin small,.audit-route strong,.audit-route small{display:block}.audit-user small,.audit-origin small,.audit-route small{margin-top:3px;color:var(--text-muted);font-size:10px}.audit-result{display:inline-flex;margin:0 6px 4px 0;padding:4px 8px;border-radius:999px;background:#fff7ed;color:#9a3412;font-size:10px;font-weight:800}.audit-result.error{background:#fee2e2;color:#991b1b}.audit-context{max-width:460px;white-space:normal;line-height:1.45}@media(max-width:900px){.audit-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.audit-filter-grid{grid-template-columns:1fr}}
</style>

<!-- InvCabecera de Página -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1>Bitácora de Auditoría</h1>
        <p>Historial inmutable de movimientos del sistema. Supervisa altas, bajas, reasignaciones de áreas portuarias, consultas y modificaciones realizadas.</p>
    </div>
    <div>
        <a href="index.php?route=inv_bitacora&action=exportar" class="btn-outline" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;"><i class="fa-solid fa-download"></i> Exportar Bitácora CSV</a>
    </div>
</div>

<!-- Stats de Auditoría -->
<div class="stats-row animate-fade-in" style="margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div>
            <div class="stat-value"><?= $stats['total'] ?></div>
            <div class="stat-label">Total Operaciones</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="fa-solid fa-square-plus"></i></div>
        <div>
            <div class="stat-value"><?= $stats['CREAR'] ?></div>
            <div class="stat-label">Inserciones (CREAR)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="fa-solid fa-pen-to-square"></i></div>
        <div>
            <div class="stat-value"><?= $stats['ACTUALIZAR'] ?></div>
            <div class="stat-label">Ediciones (UPDATE)</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="fa-solid fa-trash-can"></i></div>
        <div>
            <div class="stat-value"><?= $stats['ELIMINAR'] ?></div>
            <div class="stat-label">Eliminaciones (DELETE)</div>
        </div>
    </div>
</div>

<!-- Barra de Filtros y Búsqueda -->
<div class="filter-section animate-fade-in" style="margin-bottom:24px;">
    <form action="index.php" method="GET" class="filter-controls audit-filter-grid">
        <input type="hidden" name="route" value="inv_bitacora">
        <div class="filter-group" style="margin:0;"><label>Usuario o cuenta</label><input type="text" name="usuario" placeholder="Nombre o usuario" value="<?= htmlspecialchars($filtros['usuario'] ?? '') ?>"></div>
        <div class="filter-group" style="margin:0;"><label>Dirección IP</label><input type="text" name="ip" placeholder="Ej: 192.168.1.20" value="<?= htmlspecialchars($filtros['ip'] ?? '') ?>"></div>
        
        <div class="filter-group" style="margin:0;">
            <label>Descripción o Código Secuencial</label>
            <input type="text" name="termino" placeholder="Buscar por palabra clave..." value="<?= htmlspecialchars($filtros['termino']) ?>">
        </div>

        <div class="filter-group" style="margin:0;">
            <label>Módulo</label>
            <select name="modulo">
                <option value="">Todos los Módulos</option>
                <option value="inv" <?= ($filtros['modulo'] === 'inv') ? 'selected' : '' ?>>Inventario Portuario</option>
                <option value="th" <?= ($filtros['modulo'] === 'th') ? 'selected' : '' ?>>Cabeceras Maestras (TH)</option>
                <option value="per" <?= ($filtros['modulo'] === 'per') ? 'selected' : '' ?>>Períodos e IVA</option>
                <option value="acc" <?= ($filtros['modulo'] === 'acc') ? 'selected' : '' ?>>Talento y Acceso</option>
                <option value="seq" <?= ($filtros['modulo'] === 'seq') ? 'selected' : '' ?>>Secuenciales</option>
            </select>
        </div>

        <div class="filter-group" style="margin:0;">
            <label>Acción / Tipo</label>
            <select name="tipo">
                <option value="">Todas las Acciones</option>
                <option value="CREAR" <?= ($filtros['tipo'] === 'CREAR') ? 'selected' : '' ?>>CREAR (Altas)</option>
                <option value="ACTUALIZAR" <?= ($filtros['tipo'] === 'ACTUALIZAR') ? 'selected' : '' ?>>ACTUALIZAR (Ediciones)</option>
                <option value="ELIMINAR" <?= ($filtros['tipo'] === 'ELIMINAR') ? 'selected' : '' ?>>ELIMINAR (Bajas)</option>
                <option value="ACCESO" <?= ($filtros['tipo'] === 'ACCESO') ? 'selected' : '' ?>>ACCESO (Navegación)</option>
                <option value="CONSULTA" <?= ($filtros['tipo'] === 'CONSULTA') ? 'selected' : '' ?>>CONSULTA (Detalles)</option>
                <option value="EXPORTAR" <?= ($filtros['tipo'] === 'EXPORTAR') ? 'selected' : '' ?>>EXPORTAR (Informes)</option>
                <option value="CIERRE" <?= ($filtros['tipo'] === 'CIERRE') ? 'selected' : '' ?>>CIERRE (Cortes de Período)</option>
            </select>
        </div>

        <div class="filter-group" style="margin:0;"><label>Resultado</label><select name="resultado"><option value="">Todos</option><option value="OK" <?= ($filtros['resultado'] ?? '') === 'OK' ? 'selected' : '' ?>>Correcto</option><option value="ERROR_403" <?= ($filtros['resultado'] ?? '') === 'ERROR_403' ? 'selected' : '' ?>>Acceso denegado</option><option value="ERROR_500" <?= ($filtros['resultado'] ?? '') === 'ERROR_500' ? 'selected' : '' ?>>Error interno</option></select></div>
        <div class="filter-group" style="margin:0;"><label>Desde</label><input type="date" name="desde" value="<?= htmlspecialchars($filtros['desde'] ?? '') ?>"></div>
        <div class="filter-group" style="margin:0;"><label>Hasta</label><input type="date" name="hasta" value="<?= htmlspecialchars($filtros['hasta'] ?? '') ?>"></div>
        <div class="filter-actions" style="margin:0;">
            <a href="index.php?route=inv_bitacora" class="btn-outline" style="height:40px;display:flex;align-items:center;justify-content:center;" title="Limpiar"><i class="fa-solid fa-eraser"></i></a>
            <button type="submit" class="btn-primary" style="height:40px;"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </div>
    </form>
</div>

<!-- Tabla de Logs de Auditoría -->
<div class="panel animate-fade-in">
    <div class="panel-header">
        <h3>Historial Completo (Últimas 500 operaciones)</h3>
    </div>
    <div class="table-responsive">
        <table class="audit-table">
            <thead><tr><th>Fecha / evento</th><th>Usuario</th><th>Origen</th><th>Ruta / acción</th><th>Módulo</th><th>Tipo</th><th>Descripción operativa</th></tr></thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:var(--text-muted);">
                            <i class="fa-solid fa-ghost" style="font-size:32px; display:block; margin-bottom:12px; opacity:0.4;"></i>
                            No se encontraron logs de auditoría con los filtros indicados
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): 
                        // Asignar color según acción
                        $actionClass = 'status-badge';
                        $tipo = strtoupper($l['tipo']);
                        if ($tipo === 'CREAR') $actionClass .= ' active'; // Verde
                        elseif ($tipo === 'ACTUALIZAR') $actionClass .= ' pending'; // Amarillo / Naranja
                        elseif ($tipo === 'ELIMINAR') $actionClass .= ' inactive'; // Rojo
                        elseif ($tipo === 'CIERRE' || $tipo === 'EXPORTAR') $actionClass .= ' transit'; // Púrpura
                        else $actionClass .= ' dispatched'; // Gris
                        
                        // Modulo label
                        $modLabel = 'Sistema';
                        if ($l['modulo'] === 'inv') $modLabel = 'Inventario Portuario';
                        elseif ($l['modulo'] === 'th') $modLabel = 'Cabeceras (TH)';
                        elseif ($l['modulo'] === 'per') $modLabel = 'Períodos e IVA';
                        elseif ($l['modulo'] === 'acc') $modLabel = 'Talento y Acceso';
                        elseif ($l['modulo'] === 'seq') $modLabel = 'Secuenciales';
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($l['fecha']) ?></strong><span class="ab-detail"><?= htmlspecialchars($l['secuencial']) ?><?= !empty($l['duracion_ms']) ? ' · '.number_format((float)$l['duracion_ms'],2).' ms' : '' ?></span></td>
                            <td class="audit-user"><strong><?= htmlspecialchars($l['usuario_nombre'] ?? 'Sistema') ?></strong><small><?= htmlspecialchars(($l['usuario_login'] ?? 'sin cuenta').(!empty($l['rol']) ? ' · '.$l['rol'] : '')) ?></small></td>
                            <td class="audit-origin"><strong><?= htmlspecialchars($l['ip'] ?? '—') ?></strong><small><?= htmlspecialchars($l['equipo'] ?? 'No informado') ?></small></td>
                            <td class="audit-route"><strong><?= htmlspecialchars($l['ruta'] ?? '—') ?></strong><small><?= htmlspecialchars(($l['metodo_http'] ?? '').' · '.($l['accion'] ?? 'index')) ?></small></td>
                            <td style="font-weight:500;"><i class="fa-regular fa-folder" style="margin-right:6px;"></i> <?= $modLabel ?></td>
                            <td><span class="<?= $actionClass ?>"><?= $tipo ?></span></td>
                            <td class="audit-context"><?php
                                $resultado = strtoupper((string)($l['resultado'] ?? 'OK'));
                                $esDenegado = $resultado === 'ERROR_403' || $tipo === 'DENEGADO';
                                $esError = str_starts_with($resultado, 'ERROR') && !$esDenegado;
                                if ($esDenegado || $esError):
                            ?><span class="audit-result <?= $esError ? 'error' : '' ?>"><?= $esDenegado ? 'ACCESO DENEGADO' : htmlspecialchars(str_replace('_', ' ', $resultado)) ?></span><?php endif; ?><?= htmlspecialchars($l['descripcion']) ?><?php if (!empty($l['request_id'])): ?><span class="ab-detail">Solicitud: <?= htmlspecialchars($l['request_id']) ?></span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
