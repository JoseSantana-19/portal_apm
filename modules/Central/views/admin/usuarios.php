<?php
/**
 * Gestión de Usuarios — Central Portal APM
 * Administración centralizada de identidades, jerarquías, MFA y roles institucionales.
 */
$success = SessionHelper::getFlash('success');
$nivelLabels = [0=>'Operativo', 1=>'Analista', 2=>'Jefatura', 3=>'Director', 4=>'Super Admin'];
$nivelClasses = [
    0 => 'admin-badge-operativo',
    1 => 'admin-badge-analista',
    2 => 'admin-badge-jefe',
    3 => 'admin-badge-director',
    4 => 'admin-badge-super'
];

$total    = count($usuarios);
$activos  = count(array_filter($usuarios, fn($u) => !empty($u['estado'])));
$inactivos = $total - $activos;
$conMfa   = count(array_filter($usuarios, fn($u) => !empty($u['mfa_activado_en']) || !empty($u['requiere_mfa'])));
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
            <div class="admin-header-icon">
                <i class="fa-solid fa-users-gear"></i>
            </div>
            <div>
                <div class="admin-header-eyebrow">
                    <i class="fa-solid fa-shield-halved"></i> Administración &bull; Identidad y Accesos
                </div>
                <h1 class="admin-header-title">Gestión de Usuarios</h1>
                <div class="admin-header-subtitle">
                    Administra cuentas, niveles jerárquicos, políticas MFA y asignación de permisos
                </div>
            </div>
        </div>

        <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;align-items:center;">
            <a href="<?= APP_URL ?>/admin/usuarios/export/excel" class="btn-dash" title="Exportar todos los usuarios a Excel">
                <i class="fa-solid fa-file-excel" style="color:#10B981;"></i> Excel
            </a>
            <a href="<?= APP_URL ?>/admin/usuarios/export/pdf" class="btn-dash" target="_blank" rel="noopener" title="Exportar directorio a PDF">
                <i class="fa-solid fa-file-pdf" style="color:#EF4444;"></i> PDF
            </a>
            <a href="<?= APP_URL ?>/admin/usuarios/nuevo" class="btn-dash btn-dash-primary" data-spa title="Vincular nuevo usuario desde nómina de Talento Humano">
                <i class="fa-solid fa-user-plus"></i> Nuevo Usuario (desde TH)
            </a>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         STATISTICS GRID (4 CARDS)
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-stat-grid">
        <!-- 1. Total -->
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #0284C7 15%, transparent);color:#0284C7;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $total ?></div>
                <div class="admin-stat-label">Total Cuentas</div>
            </div>
        </div>

        <!-- 2. Activos -->
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #10B981 15%, transparent);color:#10B981;">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $activos ?></div>
                <div class="admin-stat-label">Cuentas Activas</div>
            </div>
        </div>

        <!-- 3. Inactivos -->
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #EF4444 15%, transparent);color:#EF4444;">
                <i class="fa-solid fa-user-xmark"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $inactivos ?></div>
                <div class="admin-stat-label">Inactivas</div>
            </div>
        </div>

        <!-- 4. Con MFA -->
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #8B5CF6 15%, transparent);color:#8B5CF6;">
                <i class="fa-solid fa-fingerprint"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $conMfa ?></div>
                <div class="admin-stat-label">Con MFA Activo</div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         USERS DATATABLE
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-card">
        <div class="dash-card-header">
            <div>
                <div class="dash-card-title">
                    <i class="fa-solid fa-address-book" style="color:var(--primary-hover);"></i>
                    Directorio Institucional de Usuarios
                </div>
                <div class="dash-card-subtitle">Listado oficial de credenciales activas del Portal APM</div>
            </div>
            <span class="beacon-pulse"><span class="beacon-dot"></span> Sincronizado</span>
        </div>

        <div class="dash-table-wrap">
            <table id="usr-table" class="dash-table" data-dt data-dt-page-length="25">
                <thead>
                    <tr>
                        <th>Usuario / Identificación</th>
                        <th>Nombre Completo</th>
                        <th>Correo Institucional</th>
                        <th>Nivel Jerárquico</th>
                        <th>Departamento</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:var(--sp-8);color:var(--text-muted);">
                        <i class="fa-solid fa-users-slash" style="font-size:2rem;margin-bottom:8px;display:block;"></i>
                        Sin usuarios registrados en el sistema
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($usuarios as $u):
                    $nivel       = (int)($u['nivel_jerarquia'] ?? 0);
                    $nivelLbl    = $nivelLabels[$nivel] ?? 'Operativo';
                    $badgeClass  = $nivelClasses[$nivel] ?? 'admin-badge-operativo';
                    $initials    = implode('', array_map(fn($w) => strtoupper(mb_substr($w, 0, 1)),
                                    array_slice(explode(' ', $u['nombre_completo'] ?? ($u['cedula'] ?? 'US')), 0, 2)));
                    $avatarColors = ['#0284C7', '#10B981', '#F59E0B', '#8B5CF6', '#EF4444'];
                    $avatarBg     = $avatarColors[$nivel % count($avatarColors)];
                    $isSelf       = (int)($u['id_usuario'] ?? 0) === (int)($_SESSION['user_id'] ?? 0);
                    $hasMfa       = !empty($u['mfa_activado_en']) || !empty($u['requiere_mfa']);
                ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div class="admin-avatar" style="background:<?= $avatarBg ?>;">
                                <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div>
                                <div style="font-weight:700;color:var(--text-app);line-height:1.2;">
                                    <?= htmlspecialchars($u['nombre_usuario'] ?? 'usuario', ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($isSelf): ?>
                                    <span class="badge badge-info" style="font-size:0.62rem;padding:1px 6px;">Tú</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-family:var(--font-code);font-size:0.72rem;color:var(--text-muted);margin-top:2px;">
                                    <i class="fa-regular fa-id-card" style="margin-right:2px;"></i> <?= htmlspecialchars($u['cedula'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:600;color:var(--text-app);"><?= htmlspecialchars($u['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if ($hasMfa): ?>
                        <span style="font-size:0.68rem;color:#8B5CF6;font-weight:700;display:inline-flex;align-items:center;gap:3px;margin-top:2px;">
                            <i class="fa-solid fa-shield-check"></i> MFA Activo
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="color:var(--text-muted);font-size:0.78rem;">
                            <?= htmlspecialchars($u['correo'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $badgeClass ?>" style="font-size:0.72rem;font-weight:700;">
                            <?= $nivelLbl ?>
                        </span>
                    </td>
                    <td>
                        <span style="font-size:0.78rem;font-weight:600;color:var(--text-app);">
                            <?= htmlspecialchars($u['departamento'] ?? 'Sin Departamento', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($u['estado'])): ?>
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
                            <a href="<?= APP_URL ?>/admin/usuarios/<?= $u['id_usuario'] ?>/editar"
                               class="btn btn-ghost btn-sm" data-spa title="Editar usuario y credenciales">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="<?= APP_URL ?>/admin/usuarios/<?= $u['id_usuario'] ?>/export/pdf"
                               class="btn btn-ghost btn-sm" target="_blank" rel="noopener" title="Descargar Ficha PDF">
                                <i class="fa-solid fa-file-pdf" style="color:#EF4444;"></i>
                            </a>
                            <a href="<?= APP_URL ?>/admin/usuarios/<?= $u['id_usuario'] ?>/export/excel"
                               class="btn btn-ghost btn-sm" title="Exportar Ficha a Excel">
                                <i class="fa-solid fa-file-excel" style="color:#10B981;"></i>
                            </a>

                            <?php if (!$isSelf && !empty($u['estado'])): ?>
                            <form method="POST" action="<?= APP_URL ?>/admin/usuarios/<?= $u['id_usuario'] ?>/eliminar" style="display:inline;">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <button type="button" class="btn btn-ghost btn-sm" style="color:var(--danger);" title="Desactivar cuenta"
                                        onclick="PortalAlert.confirmAction('¿Desactivar al usuario <?= htmlspecialchars($u['nombre_completo'], ENT_QUOTES) ?>?', this.form, {title:'¿Desactivar usuario?', confirmText:'Sí, desactivar'})">
                                    <i class="fa-solid fa-user-slash"></i>
                                </button>
                            </form>
                            <?php elseif (!$isSelf && empty($u['estado'])): ?>
                            <form method="POST" action="<?= APP_URL ?>/admin/usuarios/<?= $u['id_usuario'] ?>/activar" style="display:inline;">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <button type="button" class="btn btn-ghost btn-sm" style="color:var(--success);" title="Activar cuenta"
                                        onclick="PortalAlert.confirmAction('¿Reactivar al usuario <?= htmlspecialchars($u['nombre_completo'], ENT_QUOTES) ?>?', this.form, {title:'¿Activar usuario?', confirmText:'Sí, activar'})">
                                    <i class="fa-solid fa-user-check"></i>
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
