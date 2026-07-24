<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-5);">
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-chart-line" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
        Reportes Globales
    </h2>
    <a href="<?= APP_URL ?>/dashboard" class="btn btn-outline" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Dashboard
    </a>
</div>

<!-- Quick links to module reports -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--sp-4);margin-bottom:var(--sp-6);">
    <?php
    $links = [
        ['icon'=>'fa-solid fa-users',           'label'=>'Talento Humano',    'url'=>'/th/empleados',        'color'=>'var(--color-primary)'],
        ['icon'=>'fa-solid fa-file-contract',   'label'=>'Contratos',         'url'=>'/th/contratos',        'color'=>'var(--color-info)'],
        ['icon'=>'fa-solid fa-boxes-stacked',   'label'=>'Inventario Bienes', 'url'=>'/bienes',              'color'=>'var(--color-success)'],
        ['icon'=>'fa-solid fa-book-open',       'label'=>'Bitácoras',         'url'=>'/bitacoras/reportes',  'color'=>'var(--color-warning)'],
        ['icon'=>'fa-solid fa-id-card',         'label'=>'Control Acceso',    'url'=>'/acceso/reporte',      'color'=>'var(--color-danger)'],
        ['icon'=>'fa-solid fa-shield-halved',   'label'=>'Auditoría',         'url'=>'/admin/auditoria',     'color'=>'var(--color-primary)'],
    ];
    foreach ($links as $l):
    ?>
    <a href="<?= APP_URL . htmlspecialchars($l['url'], ENT_QUOTES, 'UTF-8') ?>" class="hero-glass-card" data-spa
       style="text-decoration:none;display:flex;align-items:center;gap:var(--sp-3);padding:var(--sp-4);transition:box-shadow var(--transition);">
        <div style="width:40px;height:40px;border-radius:var(--radius-md);background:color-mix(in srgb,<?= $l['color'] ?> 15%,transparent);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="<?= $l['icon'] ?>" style="color:<?= $l['color'] ?>;"></i>
        </div>
        <span style="font-size:var(--font-size-sm);font-weight:var(--font-weight-semibold);color:var(--color-text);">
            <?= htmlspecialchars($l['label'], ENT_QUOTES, 'UTF-8') ?>
        </span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Recent audit trail -->
<div class="hero-glass-card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-4);">
        <h3 style="font-size:var(--font-size-base);font-weight:var(--font-weight-semibold);">Actividad Reciente del Sistema</h3>
        <a href="<?= APP_URL ?>/admin/auditoria" class="btn btn-ghost btn-sm" data-spa>Ver todo</a>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr><th>Fecha</th><th>Usuario</th><th>Módulo</th><th>Operación</th><th>IP</th><th>Resultado</th></tr>
            </thead>
            <tbody>
            <?php if (empty($auditoria)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--color-text-muted);padding:var(--sp-6);">Sin registros</td></tr>
            <?php else: ?>
            <?php foreach ($auditoria as $a):
                $fecha = $a['fecha_creacion'];
                if ($fecha instanceof DateTime) { $fecha = $fecha->format('d/m/Y H:i'); }
                elseif (is_string($fecha)) { $fecha = date('d/m/Y H:i', strtotime($fecha)); }
                $resClass = ($a['resultado'] ?? '') === 'EXITO' ? 'badge-success' : 'badge-danger';
            ?>
            <tr>
                <td style="font-size:var(--font-size-xs);white-space:nowrap;"><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($a['nombre_completo'] ?? 'Sistema', ENT_QUOTES, 'UTF-8') ?></td>
                <td><code><?= htmlspecialchars($a['modulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></code></td>
                <td><?= htmlspecialchars($a['operacion'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td style="font-size:var(--font-size-xs);color:var(--color-text-muted);"><?= htmlspecialchars($a['ip_address'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge <?= $resClass ?>"><?= htmlspecialchars($a['resultado'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
