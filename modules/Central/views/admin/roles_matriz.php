<?php
/**
 * Matriz de Permisos — Central Portal APM
 * Vista consolidada de acceso de roles a cada módulo institucional.
 */
$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$nivelColor = [1 => '#6c757d', 2 => '#0284C7', 3 => '#F59E0B', 4 => '#10B981'];
?>

<div class="dashboard-wrapper anim-up anim-d0">

    <!-- ══════════════════════════════════════════════════════════════
         PREMIUM ADMIN HEADER
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-page-header">
        <div class="admin-header-title-group">
            <div class="admin-header-icon" style="background:linear-gradient(135deg, #0284C7, #0369A1);">
                <i class="fa-solid fa-table-cells"></i>
            </div>
            <div>
                <div class="admin-header-eyebrow">
                    <i class="fa-solid fa-shield-halved"></i> Administración &bull; Matriz de Seguridad
                </div>
                <h1 class="admin-header-title">Matriz Global de Permisos</h1>
                <div class="admin-header-subtitle">
                    Resumen consolidado de cobertura y nivel de acceso por rol en cada subsistema
                </div>
            </div>
        </div>

        <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;align-items:center;">
            <a href="<?= APP_URL ?>/admin/roles" class="btn-dash" data-spa>
                <i class="fa-solid fa-arrow-left"></i> Volver a Roles
            </a>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         STATISTICS GRID
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-stat-grid">
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #0284C7 15%, transparent);color:#0284C7;">
                <i class="fa-solid fa-key"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= (int)$totalRoles ?></div>
                <div class="admin-stat-label">Roles Totales</div>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #10B981 15%, transparent);color:#10B981;">
                <i class="fa-solid fa-cubes"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= count($modulos) ?></div>
                <div class="admin-stat-label">Módulos Auditados</div>
            </div>
        </div>

        <?php if ($sinPermisos > 0): ?>
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #EF4444 15%, transparent);color:#EF4444;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <div class="admin-stat-num" style="color:#EF4444;"><?= (int)$sinPermisos ?></div>
                <div class="admin-stat-label">Roles sin Permisos</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         LEYENDA & EXPLICACIÓN
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-card" style="margin-bottom:var(--sp-4);padding:var(--sp-4);">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;font-size:0.78rem;">
                <strong style="color:var(--text-app);"><i class="fa-solid fa-circle-info" style="color:var(--primary-hover);margin-right:4px;"></i> Leyenda de Acceso:</strong>
                <span style="display:flex;align-items:center;gap:5px;">
                    <span style="width:12px;height:12px;border-radius:3px;background:#10B981;display:inline-block;"></span> Acceso Total
                </span>
                <span style="display:flex;align-items:center;gap:5px;">
                    <span style="width:12px;height:12px;border-radius:3px;background:#F59E0B;display:inline-block;"></span> Acceso Parcial
                </span>
                <span style="display:flex;align-items:center;gap:5px;">
                    <span style="width:12px;height:12px;border-radius:3px;background:var(--border-app);display:inline-block;"></span> Sin Acceso
                </span>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         MATRIX TABLE
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-card">
        <div class="dash-card-header">
            <div>
                <div class="dash-card-title">
                    <i class="fa-solid fa-table-cells-large" style="color:var(--primary-hover);"></i>
                    Matriz de Cobertura de Permisos
                </div>
                <div class="dash-card-subtitle">Relación cruzada de perfiles de usuario frente a subsistemas</div>
            </div>
        </div>

        <div class="dash-table-wrap">
            <table id="matriz-table" class="dash-table" style="min-width:680px;" data-dt data-dt-page-length="25">
                <thead>
                    <tr>
                        <th style="min-width:240px;">Rol Institucional</th>
                        <?php foreach ($modulos as $idMod): 
                            $meta = $moduleMeta[$idMod] ?? ['label'=>"Módulo $idMod", 'icon'=>'fa-folder', 'color'=>'#0284C7'];
                        ?>
                        <th style="text-align:center;min-width:160px;">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                                <i class="fa-solid <?= $e($meta['icon']) ?>" style="color:<?= $e($meta['color']) ?>;font-size:1.1rem;"></i>
                                <span style="font-size:0.75rem;font-weight:700;"><?= $e($meta['label']) ?></span>
                            </div>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($matriz)): ?>
                    <tr><td colspan="<?= count($modulos) + 1 ?>" style="text-align:center;color:var(--text-muted);padding:var(--sp-8);">Sin roles configurados.</td></tr>
                <?php else: foreach ($matriz as $fila):
                    $rol = $fila['rol'];
                    $inactivo = (int)($rol['estado'] ?? 1) === 0;
                    $sinNingunPermiso = true;
                    foreach ($fila['celdas'] as $c) { if ($c['con_acceso'] > 0) { $sinNingunPermiso = false; break; } }
                ?>
                    <tr style="<?= $inactivo ? 'opacity:.55;' : '' ?>">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <?php if ($sinNingunPermiso): ?>
                                <i class="fa-solid fa-triangle-exclamation" style="color:#EF4444;font-size:0.85rem;" title="Este rol no tiene ningún permiso configurado"></i>
                                <?php else: ?>
                                <i class="fa-solid fa-shield-check" style="color:#10B981;font-size:0.85rem;"></i>
                                <?php endif; ?>
                                <div>
                                    <div style="font-weight:700;font-size:0.85rem;color:var(--text-app);">
                                        <?= $e($rol['nombre']) ?>
                                        <?php if ($inactivo): ?>
                                        <span class="badge badge-gray" style="font-size:0.6rem;">Inactivo</span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:0.7rem;color:var(--text-muted);">
                                        <code style="font-size:0.68rem;"><?= $e($rol['codigo']) ?></code> &bull; <?= $e($rol['departamento'] ?: 'Global') ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <?php foreach ($fila['celdas'] as $celda):
                            $tot = (int)$celda['total_nodos'];
                            $con = (int)$celda['con_acceso'];
                            $pct = $tot > 0 ? round(($con / $tot) * 100) : 0;
                            $colorPill = $con === 0 ? 'var(--border-app)' : ($con === $tot ? '#10B981' : '#F59E0B');
                            $textColor = $con === 0 ? 'var(--text-muted)' : '#ffffff';
                        ?>
                        <td style="text-align:center;">
                            <div style="display:inline-flex;flex-direction:column;align-items:center;gap:4px;">
                                <span style="font-size:0.72rem;font-weight:800;padding:2px 8px;border-radius:99px;background:<?= $colorPill ?>;color:<?= $textColor ?>;">
                                    <?= $con ?> / <?= $tot ?> (<?= $pct ?>%)
                                </span>
                                <a href="<?= APP_URL ?>/admin/roles/<?= $rol['id_rol'] ?>/permisos" class="btn btn-ghost btn-sm" style="padding:1px 6px;font-size:0.65rem;" data-spa title="Configurar permisos de este rol">
                                    Editar
                                </a>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
