<?php
$v = fn(array $arr, string $key, $default = 0) => $arr[$key] ?? $default;
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-5);flex-wrap:wrap;gap:var(--sp-3);">
    <div>
        <h2 class="page-title">
            <i class="fa-solid fa-users" style="color:#0284C7;margin-right:var(--sp-2);"></i>
            Talento Humano
        </h2>
        <p class="page-subtitle">Datos en vivo desde la BD Talento_Humano.</p>
    </div>
    <a href="<?= APP_URL ?>/apps/talento_humano/" class="btn btn-primary" data-no-spa>
        <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir Talento Humano
    </a>
</div>

<div class="kpi-grid">
    <div class="kpi-card" title="Empleados Activos">
        <div class="kpi-glow" style="background:#0284C7;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Empleados Activos</span>
            <span class="kpi-value"><?= number_format($v($kpis, 'total')) ?></span>
        </div>
        <div class="kpi-card-right bg-primary-light" style="background: rgba(2,132,199,0.1) !important;">
            <i class="fa-solid fa-users kpi-icon" style="color: #0284C7 !important;"></i>
        </div>
    </div>

    <div class="kpi-card" title="Nuevos este mes">
        <div class="kpi-glow" style="background:#10B981;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Nuevos este Mes</span>
            <span class="kpi-value"><?= number_format($v($kpis, 'nuevos_mes')) ?></span>
        </div>
        <div class="kpi-card-right bg-accent-light" style="background: rgba(16,185,129,0.1) !important;">
            <i class="fa-solid fa-user-plus kpi-icon" style="color: #10B981 !important;"></i>
        </div>
    </div>

    <div class="kpi-card" title="Género">
        <div class="kpi-glow" style="background:#8B5CF6;"></div>
        <div class="kpi-card-left">
            <span class="kpi-label">Masculino / Femenino</span>
            <span class="kpi-value"><?= number_format($v($kpis, 'masculino')) ?> / <?= number_format($v($kpis, 'femenino')) ?></span>
        </div>
        <div class="kpi-card-right" style="background: rgba(139,92,246,0.1) !important;">
            <i class="fa-solid fa-venus-mars kpi-icon" style="color: #8B5CF6 !important;"></i>
        </div>
    </div>
</div>

<div class="chart-card anim-up anim-d2" style="margin-top:var(--sp-4);">
    <div class="chart-card-header">
        <div>
            <div class="chart-title">Empleados por Unidad Organizacional</div>
            <div class="chart-subtitle">Top 6</div>
        </div>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="table">
            <thead><tr><th>Unidad</th><th style="text-align:right;">Empleados</th></tr></thead>
            <tbody>
                <?php if (empty($kpis['por_unidad'])): ?>
                <tr><td colspan="2" style="text-align:center;color:var(--color-text-muted);padding:var(--sp-5);">Sin datos de unidad organizacional.</td></tr>
                <?php else: foreach ($kpis['por_unidad'] as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['nombre_unidad'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align:right;"><?= number_format((int)$u['total']) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
