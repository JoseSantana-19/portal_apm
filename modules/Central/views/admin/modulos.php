<?php
$success = SessionHelper::getFlash('success');
$error   = SessionHelper::getFlash('error');
$h = fn($val) => htmlspecialchars((string)($val ?? ''), ENT_QUOTES, 'UTF-8');
?>

<?php if ($success): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.success(<?= json_encode($success) ?>));</script>
<?php endif; ?>
<?php if ($error): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.error(<?= json_encode($error) ?>));</script>
<?php endif; ?>

<div class="page-header" style="margin-bottom:var(--sp-5);">
    <div>
        <h2 class="page-title">
            <i class="fa-solid fa-layer-group" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
            Módulos del Portal
        </h2>
        <p class="page-subtitle">Registro central de módulos — nativos y embebidos. Un módulo nuevo queda disponible en Estructura del Menú y Roles y Permisos sin tocar código.</p>
    </div>
    <a href="<?= APP_URL ?>/admin/modulos/nuevo" class="btn btn-primary" data-spa>
        <i class="fa-solid fa-plus"></i> Nuevo Módulo
    </a>
</div>

<div style="display:flex;gap:var(--sp-3);margin-bottom:var(--sp-5);flex-wrap:wrap;">
    <div style="background:var(--card-bg);border:1px solid var(--color-border-light);border-radius:var(--radius-md);
                padding:var(--sp-3) var(--sp-4);display:flex;align-items:center;gap:var(--sp-3);box-shadow:var(--shadow-xs);">
        <div style="width:36px;height:36px;border-radius:var(--radius-md);background:color-mix(in srgb,var(--color-primary) 12%,transparent);
                    display:flex;align-items:center;justify-content:center;color:var(--color-primary);">
            <i class="fa-solid fa-layer-group" style="font-size:0.9rem;"></i>
        </div>
        <div>
            <div style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);line-height:1;"><?= $total ?></div>
            <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);">Módulos totales</div>
        </div>
    </div>
    <div style="background:var(--card-bg);border:1px solid var(--color-border-light);border-radius:var(--radius-md);
                padding:var(--sp-3) var(--sp-4);display:flex;align-items:center;gap:var(--sp-3);box-shadow:var(--shadow-xs);">
        <div style="width:36px;height:36px;border-radius:var(--radius-md);background:color-mix(in srgb,var(--color-success) 12%,transparent);
                    display:flex;align-items:center;justify-content:center;color:var(--color-success);">
            <i class="fa-solid fa-circle-check" style="font-size:0.9rem;"></i>
        </div>
        <div>
            <div style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);line-height:1;"><?= $activos ?></div>
            <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);">Activos</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="table-responsive-wrapper">
        <table id="modulos-table" data-dt data-dt-cols-noorder="6" data-dt-page-length="25">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Módulo</th>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Base URL / Conexión</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($modulos as $m): ?>
            <tr data-modulo-row="<?= (int)$m['id_modulo'] ?>">
                <td><code style="color:var(--color-text-muted);font-size:var(--font-size-xs);"><?= (int)$m['id_modulo'] ?></code></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="width:28px;height:28px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;
                                     background:color-mix(in srgb,<?= $h($m['color']) ?> 15%,transparent);color:<?= $h($m['color']) ?>;flex-shrink:0;">
                            <i class="fa-solid <?= $h($m['icono']) ?>" style="font-size:0.8rem;"></i>
                        </span>
                        <span style="font-weight:var(--font-weight-medium);"><?= $h($m['nombre']) ?></span>
                    </div>
                </td>
                <td>
                    <code style="background:color-mix(in srgb,var(--color-primary) 8%,transparent);
                                 color:var(--color-primary);padding:2px 6px;border-radius:var(--radius-sm);
                                 font-size:var(--font-size-xs);"><?= $h($m['codigo']) ?></code>
                </td>
                <td>
                    <span class="badge <?= $m['tipo'] === 'embebido' ? 'badge-info' : 'badge-gray' ?>">
                        <?= $m['tipo'] === 'embebido' ? 'Embebido (Patrón B)' : 'Nativo' ?>
                    </span>
                </td>
                <td style="font-size:var(--font-size-xs);color:var(--color-text-muted);">
                    <?php if ($m['base_url']): ?><div class="dt-truncate dt-truncate-sm" title="<?= $h($m['base_url']) ?>"><i class="fa-solid fa-link" style="font-size:9px;"></i> <?= $h($m['base_url']) ?></div><?php endif; ?>
                    <?php if ($m['conexion_bd']): ?><div class="dt-truncate dt-truncate-sm" title="<?= $h($m['conexion_bd']) ?>"><i class="fa-solid fa-database" style="font-size:9px;"></i> <?= $h($m['conexion_bd']) ?></div><?php endif; ?>
                    <?php if (!$m['base_url'] && !$m['conexion_bd']): ?>&mdash;<?php endif; ?>
                </td>
                <td>
                    <button type="button" class="badge <?= $m['estado'] ? 'badge-success' : 'badge-gray' ?>"
                            data-toggle-modulo="<?= (int)$m['id_modulo'] ?>" title="Clic para alternar estado">
                        <i class="fa-solid fa-circle" style="font-size:5px;vertical-align:middle;margin-right:3px;"></i>
                        <?= $m['estado'] ? 'Activo' : 'Inactivo' ?>
                    </button>
                </td>
                <td style="text-align:right;white-space:nowrap;">
                    <div class="dt-actions">
                        <a href="<?= APP_URL ?>/admin/modulos/<?= (int)$m['id_modulo'] ?>/editar"
                           class="btn btn-ghost btn-sm" data-spa title="Editar módulo">
                            <i class="fa-solid fa-pencil"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var csrf = <?= json_encode($csrf) ?>;
    document.querySelectorAll('[data-toggle-modulo]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-toggle-modulo');
            fetch(<?= json_encode(APP_URL) ?> + '/admin/modulos/' + id + '/toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: '_csrf_token=' + encodeURIComponent(csrf),
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data.ok) { location.reload(); }
            }).catch(function () { location.reload(); });
        });
    });
})();
</script>
