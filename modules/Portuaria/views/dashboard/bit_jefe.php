<?php
/**
 * Vista de Dashboard Jefatura.
 * Variables: $kpis
 * TODO: Migrar el HTML completo de bit_dashboard_jefe.php aquí.
 */
?>
<h1 class="h3 text-primary fw-bold mb-4">Panel de Jefatura</h1>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <div class="text-muted small">Visitas activas</div>
                <div class="display-5 fw-bold text-primary"><?= $kpis['visitas_activas'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <div class="text-muted small">Rondas hoy</div>
                <div class="display-5 fw-bold text-success"><?= $kpis['rondas_hoy'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <div class="text-muted small">Alertas críticas (24h)</div>
                <div class="display-5 fw-bold text-danger"><?= $kpis['alertas_criticas_24h'] ?></div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>
    Gráficos y feed de movimientos: pendiente de migrar desde <code>bit_dashboard_jefe.php</code>.
</div>
