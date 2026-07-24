<?php
/**
 * Bitacoras Module View.
 * A high-fidelity, slate-navy modular subproject dashboard.
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = dirname($scriptName);
$baseUrl = str_replace('\\', '/', $baseUrl);
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}
?>
<div class="bitacoras-container">
    <!-- Header Banner -->
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e0b36, #3b0764); border-bottom: 4px solid var(--accent-color);">
        <div class="welcome-text">
            <h2 class="welcome-title"><i data-lucide="clipboard-list" style="display:inline-block; vertical-align:middle; margin-right:10px;"></i>Bitácoras Operativas Portuarias</h2>
            <p class="welcome-desc">Registro histórico de relevos de guardia de seguridad física, novedades de patrullaje exterior nocturno, inspección de contenedores de carga y reportes operacionales.</p>
        </div>
        <div class="welcome-visual">
            <i data-lucide="book-open" class="visual-icon"></i>
        </div>
    </div>

    <!-- Security statistics cards KPIs -->
    <div class="kpi-grid" style="margin-top: 20px;">
        <div class="kpi-card" title="Total de bitácoras registradas en el sistema">
            <div class="kpi-card-left">
                <span class="kpi-label">TOTAL BITÁCORAS</span>
                <span class="kpi-value"><?= number_format($stats['total_shifts_logged'] ?? 450) ?></span>
                <span class="kpi-trend text-info"><i data-lucide="database"></i> En base de datos</span>
            </div>
            <div class="kpi-card-right bg-primary-light">
                <i data-lucide="archive" class="kpi-icon text-primary-color"></i>
            </div>
        </div>

        <div class="kpi-card" title="Rondas de patrulla activas en muelles y patios">
            <div class="kpi-card-left">
                <span class="kpi-label">PATRULLAS ACTIVAS</span>
                <span class="kpi-value"><?= number_format($stats['active_patrols'] ?? 4) ?></span>
                <span class="kpi-trend text-info"><i data-lucide="map-pin"></i> Monitoreo GPS</span>
            </div>
            <div class="kpi-card-right bg-info-light">
                <i data-lucide="shield" class="kpi-icon text-info-color"></i>
            </div>
        </div>

        <div class="kpi-card" title="Incidencias menores reportadas hoy">
            <div class="kpi-card-left">
                <span class="kpi-label">INCIDENCIAS REPORTADAS</span>
                <span class="kpi-value"><?= number_format($stats['incidents_reported'] ?? 2) ?></span>
                <span class="kpi-trend text-warning"><i data-lucide="alert-triangle"></i> Turno actual</span>
            </div>
            <div class="kpi-card-right bg-warning-light">
                <i data-lucide="heart-pulse" class="kpi-icon text-warning-color"></i>
            </div>
        </div>

        <div class="kpi-card" title="Estado operativo general del Puerto de Manta">
            <div class="kpi-card-left">
                <span class="kpi-label">ESTADO GENERAL</span>
                <span class="kpi-value" style="font-size: 1.15rem;"><?= htmlspecialchars($stats['operational_status'] ?? '100% OPERATIVO') ?></span>
                <span class="kpi-trend text-success"><i data-lucide="check-circle-2"></i> Sin anomalías</span>
            </div>
            <div class="kpi-card-right bg-accent-light">
                <i data-lucide="activity" class="kpi-icon text-accent-color"></i>
            </div>
        </div>
    </div>

    <!-- Patrol activity logs table -->
    <div class="section-container" style="margin-top: 30px;">
        <h3 class="section-title"><i data-lucide="history" style="display:inline-block; vertical-align:middle; margin-right:8px;"></i>Registro Cronológico de Novedades</h3>
        <p class="section-subtitle">Supervisión en vivo de las incidencias descritas por los oficiales a cargo durante las últimas rondas.</p>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="font-family: 'Fira Code', monospace; width: 140px;">Turno / Fecha</th>
                        <th>Oficial Reportante</th>
                        <th>Descripción detallada del Incidente / Suceso</th>
                        <th style="text-align: center; width: 120px;">Novedad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: bold; font-family: 'Fira Code', monospace; color: var(--text-accent);"><?= htmlspecialchars($l['shift']) ?></span>
                                        <span style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($l['date']) ?></span>
                                    </div>
                                </td>
                                <td style="font-weight: 500;"><?= htmlspecialchars($l['guard']) ?></td>
                                <td style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;"><?= htmlspecialchars($l['incident']) ?></td>
                                <td style="text-align: center;">
                                    <?php if (str_contains(strtolower($l['incident']), 'luminaria') || str_contains(strtolower($l['incident']), 'daño')): ?>
                                        <span class="badge badge-warning"><i data-lucide="alert-triangle" style="width:12px; height:12px;"></i> Advertencia</span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><i data-lucide="check-circle" style="width:12px; height:12px;"></i> Normal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No se registran novedades recientes.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
