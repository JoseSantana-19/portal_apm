<?php
$success = SessionHelper::getFlash('success');
$total   = count($deptos);
$desdeTh = count(array_filter($deptos, fn($d) => (int)$d['origen_th'] === 1));
?>

<?php if ($success): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.success(<?= json_encode($success) ?>));</script>
<?php endif; ?>

<div class="page-header">
    <div>
        <h2 class="page-title">
            <i class="fa-solid fa-building" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
            Departamentos
        </h2>
        <p class="page-subtitle">Estructura organizacional — sincronizada automáticamente desde Talento Humano</p>
    </div>
</div>

<div style="display:flex;gap:var(--sp-3);margin-bottom:var(--sp-5);flex-wrap:wrap;">
    <div style="background:var(--card-bg);border:1px solid var(--color-border-light);border-radius:var(--radius-md);
                padding:var(--sp-3) var(--sp-4);display:flex;align-items:center;gap:var(--sp-3);box-shadow:var(--shadow-xs);">
        <div style="width:36px;height:36px;border-radius:var(--radius-md);background:color-mix(in srgb,var(--color-primary) 12%,transparent);
                    display:flex;align-items:center;justify-content:center;color:var(--color-primary);">
            <i class="fa-solid fa-sitemap" style="font-size:0.9rem;"></i>
        </div>
        <div>
            <div style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);line-height:1;"><?= $total ?></div>
            <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);">Departamentos totales</div>
        </div>
    </div>
    <div style="background:var(--card-bg);border:1px solid var(--color-border-light);border-radius:var(--radius-md);
                padding:var(--sp-3) var(--sp-4);display:flex;align-items:center;gap:var(--sp-3);box-shadow:var(--shadow-xs);">
        <div style="width:36px;height:36px;border-radius:var(--radius-md);background:color-mix(in srgb,var(--color-success) 12%,transparent);
                    display:flex;align-items:center;justify-content:center;color:var(--color-success);">
            <i class="fa-solid fa-rotate" style="font-size:0.9rem;"></i>
        </div>
        <div>
            <div style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);line-height:1;"><?= $desdeTh ?></div>
            <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);">Sincronizados desde Talento Humano</div>
        </div>
    </div>
</div>

<div class="alert alert-info" style="margin-bottom:var(--sp-4);">
    <i class="fa-solid fa-circle-info"></i>
    El nombre y la jerarquía de los departamentos marcados <span class="badge badge-success" style="margin:0 2px;"><i class="fa-solid fa-rotate" style="font-size:8px;"></i> TH</span>
    se actualizan solos cuando cambian en Talento Humano — no se editan a mano acá. Solo se pueden ajustar su ícono, color y estado.
</div>

<div class="card">
    <div class="table-responsive-wrapper">
        <table id="deptos-table" data-dt data-dt-cols-noorder="5" data-dt-page-length="25">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Origen</th>
                    <th>Nivel</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($deptos as $d): ?>
            <tr>
                <td>
                    <code style="background:color-mix(in srgb,var(--color-primary) 8%,transparent);
                                 color:var(--color-primary);padding:2px 6px;border-radius:var(--radius-sm);
                                 font-size:var(--font-size-xs);">
                        <?= htmlspecialchars($d['codigo'], ENT_QUOTES, 'UTF-8') ?>
                    </code>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <?php if (!empty($d['icono'])): ?>
                        <span style="width:26px;height:26px;border-radius:var(--radius-sm);display:inline-flex;align-items:center;justify-content:center;background:color-mix(in srgb,<?= htmlspecialchars($d['color_badge'] ?: 'var(--color-primary)', ENT_QUOTES, 'UTF-8') ?> 14%,transparent);color:<?= htmlspecialchars($d['color_badge'] ?: 'var(--color-primary)', ENT_QUOTES, 'UTF-8') ?>;flex-shrink:0;">
                            <i class="fa-solid fa-<?= htmlspecialchars($d['icono'], ENT_QUOTES, 'UTF-8') ?>" style="font-size:0.75rem;"></i>
                        </span>
                        <?php endif; ?>
                        <span class="dt-truncate dt-truncate-md" style="font-weight:var(--font-weight-medium);" title="<?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </td>
                <td>
                    <?php if ((int)$d['origen_th'] === 1): ?>
                    <span class="badge badge-success" title="codigo_uorg: <?= htmlspecialchars($d['codigo_uorg_th'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid fa-rotate" style="font-size:8px;"></i> Talento Humano
                    </span>
                    <?php else: ?>
                    <span class="badge badge-gray">Manual (portal)</span>
                    <?php endif; ?>
                </td>
                <td><span style="font-weight:600;font-size:var(--font-size-xs);"><?= (int)$d['nivel'] ?></span></td>
                <td>
                    <span class="badge <?= $d['estado'] ? 'badge-success' : 'badge-gray' ?>">
                        <i class="fa-solid fa-circle" style="font-size:5px;vertical-align:middle;margin-right:3px;"></i>
                        <?= $d['estado'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </td>
                <td style="text-align:right;white-space:nowrap;">
                    <div class="dt-actions">
                        <a href="<?= APP_URL ?>/admin/departamentos/<?= $d['id_departamento'] ?>/editar"
                           class="btn btn-ghost btn-sm" data-spa title="Editar departamento">
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
