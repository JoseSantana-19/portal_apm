<?php
/**
 * Departamentos Institucionales — Central Portal APM
 * Estructura organizacional sincronizada automáticamente desde Talento Humano.
 */
$success = SessionHelper::getFlash('success');
$total   = count($deptos);
$desdeTh = count(array_filter($deptos, fn($d) => (int)$d['origen_th'] === 1));
$manuales = $total - $desdeTh;
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
            <div class="admin-header-icon" style="background:linear-gradient(135deg, #10B981, #059669);">
                <i class="fa-solid fa-building"></i>
            </div>
            <div>
                <div class="admin-header-eyebrow">
                    <i class="fa-solid fa-sitemap"></i> Administración &bull; Estructura Organizacional
                </div>
                <h1 class="admin-header-title">Departamentos</h1>
                <div class="admin-header-subtitle">
                    Direcciones y unidades organizacionales &bull; Sincronizadas desde Talento Humano
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         STATISTICS GRID
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-stat-grid">
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #0284C7 15%, transparent);color:#0284C7;">
                <i class="fa-solid fa-sitemap"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $total ?></div>
                <div class="admin-stat-label">Total Departamentos</div>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #10B981 15%, transparent);color:#10B981;">
                <i class="fa-solid fa-rotate"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $desdeTh ?></div>
                <div class="admin-stat-label">Sincronizados TH</div>
            </div>
        </div>

        <?php if ($manuales > 0): ?>
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #8B5CF6 15%, transparent);color:#8B5CF6;">
                <i class="fa-solid fa-pen-ruler"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $manuales ?></div>
                <div class="admin-stat-label">Manuales (Portal)</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         INFO BANNER
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-card" style="margin-bottom:var(--sp-5);background:color-mix(in srgb, #10B981 8%, var(--surface-app));border-color:color-mix(in srgb, #10B981 30%, transparent);">
        <div style="display:flex;align-items:flex-start;gap:14px;padding:var(--sp-4);">
            <div style="width:36px;height:36px;border-radius:50%;background:#10B981;color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div style="flex:1;font-size:0.83rem;color:var(--text-app);line-height:1.45;">
                <strong>Sincronización Automática con Talento Humano:</strong>
                Las direcciones y unidades marcadas con el distintivo <span class="badge badge-success" style="font-size:0.7rem;padding:2px 8px;"><i class="fa-solid fa-rotate"></i> TH</span>
                actualizan su nombre y jerarquía automáticamente cuando se modifican en el subsistema de Talento Humano.
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         DEPARTMENTS DATATABLE
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-card">
        <div class="dash-card-header">
            <div>
                <div class="dash-card-title">
                    <i class="fa-solid fa-folder-tree" style="color:var(--primary-hover);"></i>
                    Estructura de Unidades Organizacionales
                </div>
                <div class="dash-card-subtitle">Directorio de departamentos y centros de costo</div>
            </div>
        </div>

        <div class="dash-table-wrap">
            <table id="deptos-table" class="dash-table" data-dt data-dt-cols-noorder="5" data-dt-page-length="25">
                <thead>
                    <tr>
                        <th>Código UORG</th>
                        <th>Nombre del Departamento</th>
                        <th>Origen</th>
                        <th>Nivel Jerárquico</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($deptos as $d): ?>
                <tr>
                    <td>
                        <code style="background:color-mix(in srgb, var(--primary-hover) 10%, transparent);
                                     color:var(--primary-hover);padding:3px 8px;border-radius:var(--radius-sm);
                                     font-size:0.75rem;font-weight:700;">
                            <?= htmlspecialchars($d['codigo'], ENT_QUOTES, 'UTF-8') ?>
                        </code>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php if (!empty($d['icono'])): ?>
                            <span style="width:30px;height:30px;border-radius:var(--radius-sm);display:inline-flex;align-items:center;justify-content:center;background:color-mix(in srgb, <?= htmlspecialchars($d['color_badge'] ?: 'var(--primary-hover)', ENT_QUOTES, 'UTF-8') ?> 14%, transparent);color:<?= htmlspecialchars($d['color_badge'] ?: 'var(--primary-hover)', ENT_QUOTES, 'UTF-8') ?>;flex-shrink:0;font-size:0.85rem;">
                                <i class="fa-solid fa-<?= htmlspecialchars($d['icono'], ENT_QUOTES, 'UTF-8') ?>"></i>
                            </span>
                            <?php endif; ?>
                            <span style="font-weight:700;color:var(--text-app);" title="<?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <?php if ((int)$d['origen_th'] === 1): ?>
                        <span class="badge badge-success" title="codigo_uorg: <?= htmlspecialchars($d['codigo_uorg_th'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-size:0.7rem;">
                            <i class="fa-solid fa-rotate" style="font-size:8px;"></i> Talento Humano
                        </span>
                        <?php else: ?>
                        <span class="badge badge-gray" style="font-size:0.7rem;">Manual (Portal)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-weight:700;font-size:0.8rem;color:var(--text-app);">Nivel <?= (int)$d['nivel'] ?></span>
                    </td>
                    <td>
                        <?php if (!empty($d['estado'])): ?>
                        <span class="badge badge-success" style="font-size:0.72rem;">
                            <i class="fa-solid fa-circle-check" style="margin-right:3px;"></i> Activo
                        </span>
                        <?php else: ?>
                        <span class="badge badge-gray" style="font-size:0.72rem;">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;white-space:nowrap;">
                        <div class="dt-actions">
                            <a href="<?= APP_URL ?>/admin/departamentos/<?= $d['id_departamento'] ?>/editar"
                               class="btn btn-ghost btn-sm" data-spa title="Editar color e icono">
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
