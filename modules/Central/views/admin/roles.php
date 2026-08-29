<?php
/**
 * Gestión de Roles y Permisos — Central Portal APM
 * Administración de roles del sistema, jerarquías y matriz de autorización por módulos.
 */
$success    = SessionHelper::getFlash('success');
$nivelLabels = [0=>'Operativo', 1=>'Analista', 2=>'Jefatura', 3=>'Director', 4=>'Super Admin'];
$nivelClasses = [
    0 => 'admin-badge-operativo',
    1 => 'admin-badge-analista',
    2 => 'admin-badge-jefe',
    3 => 'admin-badge-director',
    4 => 'admin-badge-super'
];

$total   = count($roles);
$activos = count(array_filter($roles, fn($r) => !empty($r['estado'])));
$inactivos = $total - $activos;
?>

<?php if ($success): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.success(<?= json_encode($success) ?>));</script>
<?php endif; ?>

<div class="dashboard-wrapper anim-up anim-d0">

    <!-- ══════════════════════════════════════════════════════════════
         PREMIUM ADMIN HEADER
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-page-header">
        <div class="admin-header-title-group">
            <div class="admin-header-icon" style="background:linear-gradient(135deg, #0284C7, #0369A1);">
                <i class="fa-solid fa-key"></i>
            </div>
            <div>
                <div class="admin-header-eyebrow">
                    <i class="fa-solid fa-shield-halved"></i> Administración &bull; Seguridad y Autorización
                </div>
                <h1 class="admin-header-title">Gestión de Roles</h1>
                <div class="admin-header-subtitle">
                    Define roles institucionales y configura los permisos de acceso y niveles CRUD por módulo
                </div>
            </div>
        </div>

        <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;align-items:center;">
            <a href="<?= APP_URL ?>/admin/roles/matriz" class="btn-dash" data-spa title="Ver matriz comparativa de permisos por rol">
                <i class="fa-solid fa-table-cells" style="color:var(--primary-hover);"></i> Matriz de Permisos
            </a>
            <a href="<?= APP_URL ?>/admin/roles/nuevo" class="btn-dash btn-dash-primary" data-spa title="Crear nuevo rol de sistema">
                <i class="fa-solid fa-plus"></i> Nuevo Rol
            </a>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         STATISTICS GRID
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-stat-grid">
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #0284C7 15%, transparent);color:#0284C7;">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $total ?></div>
                <div class="admin-stat-label">Roles Totales</div>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #10B981 15%, transparent);color:#10B981;">
                <i class="fa-solid fa-shield-check"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $activos ?></div>
                <div class="admin-stat-label">Roles Activos</div>
            </div>
        </div>

        <?php if ($inactivos > 0): ?>
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #EF4444 15%, transparent);color:#EF4444;">
                <i class="fa-solid fa-shield-slash"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $inactivos ?></div>
                <div class="admin-stat-label">Inactivos</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         ROLES DATATABLE
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-card">
        <div class="dash-card-header">
            <div>
                <div class="dash-card-title">
                    <i class="fa-solid fa-user-shield" style="color:var(--primary-hover);"></i>
                    Catálogo de Roles y Privilegios
                </div>
                <div class="dash-card-subtitle">Perfiles configurados para control de acceso basado en roles (RBAC)</div>
            </div>
        </div>

        <div class="dash-table-wrap">
            <table id="roles-table" class="dash-table" data-dt data-dt-page-length="25">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre del Rol</th>
                        <th>Departamento</th>
                        <th>Nivel Jerárquico Mínimo</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($roles)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:var(--sp-8);color:var(--text-muted);">
                        <i class="fa-solid fa-key" style="font-size:2rem;margin-bottom:8px;display:block;"></i>
                        Sin roles configurados en el portal
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($roles as $r):
                    $nivel      = (int)($r['nivel_jerarquia'] ?? 0);
                    $nivelLbl   = $nivelLabels[$nivel] ?? $nivel;
                    $badgeClass = $nivelClasses[$nivel] ?? 'admin-badge-operativo';
                ?>
                <tr>
                    <td>
                        <code style="background:color-mix(in srgb, var(--primary-hover) 10%, transparent);color:var(--primary-hover);padding:3px 8px;border-radius:var(--radius-sm);font-weight:700;font-size:0.75rem;">
                            <?= htmlspecialchars($r['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </code>
                    </td>
                    <td>
                        <div style="font-weight:700;color:var(--text-app);line-height:1.2;">
                            <?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php if (!empty($r['descripcion'])): ?>
                        <div style="font-size:0.72rem;color:var(--text-muted);margin-top:2px;">
                            <?= htmlspecialchars(mb_substr($r['descripcion'], 0, 80), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-size:0.78rem;font-weight:600;color:var(--text-app);">
                            <?= htmlspecialchars($r['departamento'] ?? 'Global / Todos', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $badgeClass ?>" style="font-size:0.72rem;font-weight:700;">
                            <?= $nivelLbl ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($r['estado'])): ?>
                        <span class="badge badge-success" style="font-size:0.72rem;">
                            <i class="fa-solid fa-circle-check" style="margin-right:3px;"></i> Activo
                        </span>
                        <?php else: ?>
                        <span class="badge badge-danger" style="font-size:0.72rem;">
                            <i class="fa-solid fa-ban" style="margin-right:3px;"></i> Inactivo
                        </span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;white-space:nowrap;">
                        <div class="dt-actions">
                            <a href="<?= APP_URL ?>/admin/roles/<?= $r['id_rol'] ?>/permisos"
                               class="btn btn-ghost btn-sm" data-spa title="Configurar permisos por nodo y módulo">
                                <i class="fa-solid fa-shield-halved" style="color:var(--primary-hover);"></i>
                            </a>
                            <a href="<?= APP_URL ?>/admin/roles/<?= $r['id_rol'] ?>/editar"
                               class="btn btn-ghost btn-sm" data-spa title="Editar datos del rol">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>

                            <?php if (!empty($r['estado'])): ?>
                            <form method="POST" action="<?= APP_URL ?>/admin/roles/<?= $r['id_rol'] ?>/eliminar" style="display:inline;">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger);" title="Desactivar rol"
                                        onclick="PortalAlert.confirmAction('¿Desactivar el rol <?= htmlspecialchars($r['nombre'], ENT_QUOTES) ?>?', this.form, {title:'¿Desactivar rol?', confirmText:'Sí, desactivar'})">
                                    <i class="fa-solid fa-ban"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <form method="POST" action="<?= APP_URL ?>/admin/roles/<?= $r['id_rol'] ?>/activar" style="display:inline;">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <button type="button" class="btn btn-ghost btn-sm" style="color:var(--success);" title="Activar rol"
                                        onclick="PortalAlert.confirmAction('¿Reactivar el rol <?= htmlspecialchars($r['nombre'], ENT_QUOTES) ?>?', this.form, {title:'¿Activar rol?', confirmText:'Sí, activar'})">
                                    <i class="fa-solid fa-circle-check"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
