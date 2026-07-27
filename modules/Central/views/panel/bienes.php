<?php
$v = fn(array $arr, string $key, $default = 0) => $arr[$key] ?? $default;
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-5);flex-wrap:wrap;gap:var(--sp-3);">
    <div>
        <h2 class="page-title">
            <i class="fa-solid fa-boxes-stacked" style="color:#10B981;margin-right:var(--sp-2);"></i>
            Control de Bienes
        </h2>
        <p class="page-subtitle">Datos en vivo desde la BD inventario.</p>
    </div>
    <a href="<?= APP_URL ?>/apps/control_bienes/" class="btn btn-primary" data-no-spa>
        <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir Control de Bienes
    </a>
</div>

<div class="kpi-grid">
    <div class="kpi-card" title="Total de bienes">
        <div class="kpi-glow" style="background:#10B981;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Total de Bienes</span>
            <span class="kpi-value"><?= number_format($v($kpis, 'total')) ?></span>
        </div>
        <div class="kpi-card-right bg-accent-light" style="background: rgba(16,185,129,0.1) !important;">
            <i class="fa-solid fa-boxes-stacked kpi-icon" style="color: #10B981 !important;"></i>
        </div>
    </div>

    <div class="kpi-card" title="Operativos">
        <div class="kpi-glow" style="background:#0284C7;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Operativos</span>
            <span class="kpi-value"><?= number_format($v($kpis, 'operativos')) ?></span>
        </div>
        <div class="kpi-card-right bg-primary-light" style="background: rgba(2,132,199,0.1) !important;">
            <i class="fa-solid fa-circle-check kpi-icon" style="color: #0284C7 !important;"></i>
        </div>
    </div>

    <div class="kpi-card" title="En mantenimiento">
        <div class="kpi-glow" style="background:#F59E0B;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">En Mantenimiento</span>
            <span class="kpi-value"><?= number_format($v($kpis, 'mantenimiento')) ?></span>
        </div>
        <div class="kpi-card-right bg-warning-light" style="background: rgba(245,158,11,0.1) !important;">
            <i class="fa-solid fa-screwdriver-wrench kpi-icon" style="color: #F59E0B !important;"></i>
        </div>
    </div>

    <div class="kpi-card" title="Valor total">
        <div class="kpi-glow" style="background:#8B5CF6;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Valor Total</span>
            <span class="kpi-value">$<?= number_format($v($kpis, 'valor_total'), 2) ?></span>
        </div>
        <div class="kpi-card-right" style="background: rgba(139,92,246,0.1) !important;">
            <i class="fa-solid fa-dollar-sign kpi-icon" style="color: #8B5CF6 !important;"></i>
        </div>
    </div>
</div>

<div class="chart-card anim-up anim-d2" style="margin-top:var(--sp-4);">
    <div class="chart-card-header">
        <div>
            <div class="chart-title">Bienes por Categoría</div>
        </div>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Categoría</th><th style="text-align:right;">Bienes</th></tr></thead>
            <tbody>
                <?php if (empty($kpis['por_categoria'])): ?>
                <tr><td colspan="2" style="text-align:center;color:var(--color-text-muted);padding:var(--sp-5);">Sin categorías registradas.</td></tr>
                <?php else: foreach ($kpis['por_categoria'] as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['categoria'] ?? 'Sin categoría', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align:right;"><?= number_format((int)$c['total']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
