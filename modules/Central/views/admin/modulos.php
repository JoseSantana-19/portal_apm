<?php
/**
 * Catálogo de Módulos — Central Portal APM
 * Registro unificado de subsistemas institucionales (nativos y embebidos).
 */
$success = SessionHelper::getFlash('success');
$error   = SessionHelper::getFlash('error');
$h = fn($val) => htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');

$total = count($modulos);
$activos = count(array_filter($modulos, fn($m) => !empty($m['estado'])));
$embebidos = count(array_filter($modulos, fn($m) => ($m['tipo'] ?? '') === 'embebido'));
$nativos = $total - $embebidos;
?>

<?php if ($success): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.success(<?= json_encode($success) ?>));</script>
<?php endif; ?>
<?php if ($error): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.error(<?= json_encode($error) ?>));</script>
<?php endif; ?>

<div class="dashboard-wrapper anim-up anim-d0">

    <!-- ══════════════════════════════════════════════════════════════
         PREMIUM ADMIN HEADER
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-page-header">
        <div class="admin-header-title-group">
            <div class="admin-header-icon" style="background:linear-gradient(135deg, #0284C7, #0369A1);">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <div class="admin-header-eyebrow">
                    <i class="fa-solid fa-shield-halved"></i> Administración &bull; Ecosistema SysPort
                </div>
                <h1 class="admin-header-title">Módulos del Portal</h1>
                <div class="admin-header-subtitle">
                    Registro central de módulos nativos y embebidos integrados al ecosistema APM
                </div>
            </div>
        </div>

        <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;align-items:center;">
            <a href="<?= APP_URL ?>/admin/modulos/nuevo" class="btn-dash btn-dash-primary" data-spa title="Registrar nuevo subsistema">
                <i class="fa-solid fa-plus"></i> Nuevo Módulo
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
                <div class="admin-stat-label">Módulos Totales</div>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #10B981 15%, transparent);color:#10B981;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $activos ?></div>
                <div class="admin-stat-label">Módulos Activos</div>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #8B5CF6 15%, transparent);color:#8B5CF6;">
                <i class="fa-solid fa-cube"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $nativos ?></div>
                <div class="admin-stat-label">Módulos Nativos</div>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #F59E0B 15%, transparent);color:#F59E0B;">
                <i class="fa-solid fa-network-wired"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $embebidos ?></div>
                <div class="admin-stat-label">Embebidos (SSO)</div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         MODULES DATATABLE
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-card">
        <div class="dash-card-header">
            <div>
                <div class="dash-card-title">
                    <i class="fa-solid fa-cubes-stacked" style="color:var(--primary-hover);"></i>
                    Catálogo de Subsistemas Registrados
                </div>
                <div class="dash-card-subtitle">Control de conexión a bases de datos y activación en menú</div>
            </div>
        </div>

        <div class="dash-table-wrap">
            <table id="modulos-table" class="dash-table" data-dt data-dt-cols-noorder="6" data-dt-page-length="25">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Módulo</th>
                        <th>Código</th>
                        <th>Tipo de Integración</th>
                        <th>Conexión / Base URL</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($modulos as $m): 
                    $mColor = !empty($m['color']) ? $m['color'] : '#0284C7';
                    $mIcon = !empty($m['icono']) ? $m['icono'] : 'fa-cube';
                ?>
                <tr data-modulo-row="<?= (int)$m['id_modulo'] ?>">
                    <td>
                        <code style="color:var(--text-muted);font-size:0.75rem;"><?= (int)$m['id_modulo'] ?></code>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="width:34px;height:34px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;
                                         background:color-mix(in srgb, <?= $h($mColor) ?> 15%, transparent);color:<?= $h($mColor) ?>;flex-shrink:0;font-size:0.95rem;">
                                <i class="fa-solid <?= $h($mIcon) ?>"></i>
                            </span>
                            <span style="font-weight:700;color:var(--text-app);"><?= $h($m['nombre']) ?></span>
                        </div>
                    </td>
                    <td>
                        <code style="background:color-mix(in srgb, var(--primary-hover) 10%, transparent);
                                     color:var(--primary-hover);padding:3px 8px;border-radius:var(--radius-sm);
                                     font-size:0.75rem;font-weight:700;"><?= $h($m['codigo']) ?></code>
                    </td>
                    <td>
                        <span class="badge <?= $m['tipo'] === 'embebido' ? 'badge-info' : 'badge-gray' ?>" style="font-size:0.7rem;font-weight:700;">
                            <?= $m['tipo'] === 'embebido' ? 'Embebido (Patrón B / SSO)' : 'Nativo Central' ?>
                        </span>
                    </td>
                    <td style="font-size:0.75rem;color:var(--text-muted);">
                        <?php if ($m['base_url']): ?>
                        <div style="display:flex;align-items:center;gap:4px;"><i class="fa-solid fa-link" style="color:var(--primary-hover);font-size:9px;"></i> <?= $h($m['base_url']) ?></div>
                        <?php endif; ?>
                        <?php if ($m['conexion_bd']): ?>
                        <div style="display:flex;align-items:center;gap:4px;margin-top:2px;"><i class="fa-solid fa-database" style="color:#10B981;font-size:9px;"></i> <?= $h($m['conexion_bd']) ?></div>
                        <?php endif; ?>
                        <?php if (!$m['base_url'] && !$m['conexion_bd']): ?>&mdash;<?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="badge <?= $m['estado'] ? 'badge-success' : 'badge-gray' ?>"
                                data-toggle-modulo="<?= (int)$m['id_modulo'] ?>" title="Clic para alternar estado" style="cursor:pointer;border:none;">
                            <i class="fa-solid fa-circle" style="font-size:5px;vertical-align:middle;margin-right:3px;"></i>
                            <?= $m['estado'] ? 'Activo' : 'Inactivo' ?>
                        </button>
                    </td>
                    <td style="text-align:right;white-space:nowrap;">
                        <div class="dt-actions">
                            <a href="<?= APP_URL ?>/admin/modulos/<?= (int)$m['id_modulo'] ?>/editar"
                               class="btn btn-ghost btn-sm" data-spa title="Editar módulo">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
document.querySelectorAll('[data-toggle-modulo]').forEach(btn => {
    btn.addEventListener('click', async function () {
        const id = this.dataset.toggleModulo;
        const row = document.querySelector(`[data-modulo-row="${id}"]`);
        this.disabled = true;
        try {
            const res = await fetch(`<?= APP_URL ?>/admin/modulos/${id}/toggle`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            if (data.ok) {
                const activo = data.estado === 1;
                this.className = `badge ${activo ? 'badge-success' : 'badge-gray'}`;
                this.innerHTML = `<i class="fa-solid fa-circle" style="font-size:5px;vertical-align:middle;margin-right:3px;"></i> ${activo ? 'Activo' : 'Inactivo'}`;
                PortalAlert.success(data.mensaje || 'Estado actualizado.');
            } else {
                PortalAlert.error(data.error || 'No se pudo cambiar el estado.');
            }
        } catch (e) {
            PortalAlert.error('Error de comunicación con el servidor.');
        } finally {
            this.disabled = false;
        }
    });
});
</script>
