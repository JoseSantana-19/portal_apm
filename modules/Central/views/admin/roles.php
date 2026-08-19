<?php
$success    = SessionHelper::getFlash('success');
$nivelLabels = [0=>'Operativo',1=>'Analista',2=>'Jefatura',3=>'Director',4=>'Super Admin'];
$nivelColors = [0=>'badge-gray',1=>'badge-info',2=>'badge-primary',3=>'badge-warning',4=>'badge-danger'];

$total   = count($roles);
$activos = count(array_filter($roles, fn($r) => $r['estado']));
?>

<?php if ($success): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.success(<?= json_encode($success) ?>));</script>
<?php endif; ?>

<!-- Header -->
<div class="page-header">
    <div>
        <h2 class="page-title">
            <i class="fa-solid fa-key" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
            Gestión de Roles
        </h2>
        <p class="page-subtitle">Define roles y configura los permisos de acceso por módulo</p>
    </div>
    <div style="display:flex;gap:var(--sp-2);">
        <a href="<?= APP_URL ?>/admin/roles/matriz" class="btn btn-ghost" data-spa title="Ver quién tiene acceso a cada módulo">
            <i class="fa-solid fa-table-cells"></i> Matriz de Permisos
        </a>
        <a href="<?= APP_URL ?>/admin/roles/nuevo" class="btn btn-primary" data-spa>
            <i class="fa-solid fa-plus"></i> Nuevo Rol
        </a>
    </div>
</div>

<!-- Stats -->
<div style="display:flex;gap:var(--sp-3);margin-bottom:var(--sp-5);flex-wrap:wrap;">
    <div style="background:var(--card-bg);border:1px solid var(--color-border-light);border-radius:var(--radius-md);
                padding:var(--sp-3) var(--sp-4);display:flex;align-items:center;gap:var(--sp-3);box-shadow:var(--shadow-xs);">
        <div style="width:36px;height:36px;border-radius:var(--radius-md);background:color-mix(in srgb,var(--color-primary) 12%,transparent);
                    display:flex;align-items:center;justify-content:center;color:var(--color-primary);">
            <i class="fa-solid fa-layer-group" style="font-size:0.9rem;"></i>
        </div>
        <div>
            <div style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);line-height:1;"><?= $total ?></div>
            <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);">Roles totales</div>
        </div>
    </div>
    <div style="background:var(--card-bg);border:1px solid var(--color-border-light);border-radius:var(--radius-md);
                padding:var(--sp-3) var(--sp-4);display:flex;align-items:center;gap:var(--sp-3);box-shadow:var(--shadow-xs);">
        <div style="width:36px;height:36px;border-radius:var(--radius-md);background:color-mix(in srgb,var(--color-success) 12%,transparent);
                    display:flex;align-items:center;justify-content:center;color:var(--color-success);">
            <i class="fa-solid fa-shield-check" style="font-size:0.9rem;"></i>
        </div>
        <div>
            <div style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);line-height:1;"><?= $activos ?></div>
            <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);">Activos</div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="table-responsive-wrapper">
        <table id="roles-table" data-dt data-dt-page-length="25">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre del Rol</th>
                    <th>Departamento</th>
                    <th>Nivel mín.</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($roles)): ?>
            <tr><td colspan="6">
                <div class="empty-state">
                    <i class="fa-solid fa-key"></i>
                    <h3>Sin roles configurados</h3>
                    <p>Crea el primer rol con el botón de arriba.</p>
                </div>
            </td></tr>
            <?php else: ?>
            <?php foreach ($roles as $r):
                $nivel      = (int)($r['nivel_jerarquia'] ?? 0);
                $nivelLbl   = $nivelLabels[$nivel] ?? $nivel;
                $nivelColor = $nivelColors[$nivel] ?? 'badge-gray';
            ?>
            <tr>
                <td>
                    <code style="background:color-mix(in srgb,var(--color-primary) 8%,transparent);
                                 color:var(--color-primary);padding:2px 6px;border-radius:var(--radius-sm);
                                 font-size:var(--font-size-xs);">
                        <?= htmlspecialchars($r['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </code>
                </td>
                <td>
                    <div style="font-weight:var(--font-weight-medium);"><?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if (!empty($r['descripcion'])): ?>
                    <div class="dt-truncate dt-truncate-md" style="font-size:var(--font-size-xs);color:var(--color-text-muted);margin-top:2px;" title="<?= htmlspecialchars($r['descripcion'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars(mb_substr($r['descripcion'], 0, 80), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td><span class="dt-truncate dt-truncate-sm" title="<?= htmlspecialchars($r['departamento'] ?? 'Global', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($r['departamento'] ?? 'Global', ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><span class="badge <?= $nivelColor ?>"><?= $nivelLbl ?></span></td>
                <td>
                    <span class="badge <?= $r['estado'] ? 'badge-success' : 'badge-gray' ?>">
                        <i class="fa-solid fa-circle" style="font-size:5px;vertical-align:middle;margin-right:3px;"></i>
                        <?= $r['estado'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </td>
                <td style="text-align:right;white-space:nowrap;">
                    <div class="dt-actions">
                        <a href="<?= APP_URL ?>/admin/roles/<?= $r['id_rol'] ?>/editar"
                           class="btn btn-ghost btn-sm" data-spa title="Editar rol">
                            <i class="fa-solid fa-pencil"></i>
                        </a>
                        <a href="<?= APP_URL ?>/admin/roles/<?= $r['id_rol'] ?>/permisos"
                           class="btn btn-ghost btn-sm" data-spa title="Configurar permisos"
                           style="color:var(--color-primary);">
                            <i class="fa-solid fa-shield-halved"></i>
                        </a>
                        <?php if ($r['estado']): ?>
                        <form method="POST" action="<?= APP_URL ?>/admin/roles/<?= $r['id_rol'] ?>/eliminar">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="button" class="btn btn-ghost btn-sm" style="color:var(--color-danger);" title="Desactivar rol"
                                    onclick="PortalAlert.confirmAction('¿Desactivar el rol «<?= htmlspecialchars($r['nombre'], ENT_QUOTES) ?>»?', this.form, {title:'¿Desactivar rol?', confirmText:'Sí, desactivar'})">
                                <i class="fa-solid fa-ban"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="POST" action="<?= APP_URL ?>/admin/roles/<?= $r['id_rol'] ?>/activar">
                            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="button" class="btn btn-ghost btn-sm" style="color:var(--color-success);" title="Activar rol"
                                    onclick="PortalAlert.confirmAction('¿Activar el rol «<?= htmlspecialchars($r['nombre'], ENT_QUOTES) ?>»?', this.form, {title:'¿Activar rol?', confirmText:'Sí, activar'})">
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
