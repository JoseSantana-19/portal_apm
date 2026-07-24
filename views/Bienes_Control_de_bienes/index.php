<?php
/**
 * Control de Bienes & Inventario Module View.
 * A high-fidelity, slate-navy modular subproject dashboard.
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = dirname($scriptName);
$baseUrl = str_replace('\\', '/', $baseUrl);
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}
?>
<div class="bienes-container">
    <!-- Header Banner -->
    <div class="welcome-banner" style="background: linear-gradient(135deg, #092c3e, #0c4a6e); border-bottom: 4px solid var(--accent-color);">
        <div class="welcome-text">
            <h2 class="welcome-title"><i data-lucide="box" style="display:inline-block; vertical-align:middle; margin-right:10px;"></i>Control de Bienes & Inventario Portuario</h2>
            <p class="welcome-desc">Registro digital e inspección física de los activos fijos institucionales. Administra laptops, servidores, vehículos de carga y maquinaria portuaria pesada asignada a colaboradores.</p>
        </div>
        <div class="welcome-visual">
            <i data-lucide="package" class="visual-icon"></i>
        </div>
    </div>

    <!-- Live Inventory Statistics KPIs -->
    <div class="kpi-grid" style="margin-top: 20px;">
        <div class="kpi-card" title="Total de bienes muebles registrados">
            <div class="kpi-card-left">
                <span class="kpi-label">TOTAL ACTIVOS</span>
                <span class="kpi-value"><?= number_format($stats['total_items'] ?? 1240) ?></span>
                <span class="kpi-trend text-info"><i data-lucide="database"></i> En base de datos</span>
            </div>
            <div class="kpi-card-right bg-primary-light">
                <i data-lucide="box" class="kpi-icon text-primary-color"></i>
            </div>
        </div>

        <div class="kpi-card" title="Bienes asignados a colaboradores activos">
            <div class="kpi-card-left">
                <span class="kpi-label">ASIGNADOS</span>
                <span class="kpi-value"><?= number_format($stats['assigned'] ?? 985) ?></span>
                <span class="kpi-trend text-success"><i data-lucide="user-check"></i> En uso oficial</span>
            </div>
            <div class="kpi-card-right bg-accent-light">
                <i data-lucide="users" class="kpi-icon text-accent-color"></i>
            </div>
        </div>

        <div class="kpi-card" title="Bienes en mantenimiento técnico">
            <div class="kpi-card-left">
                <span class="kpi-label">EN SOPORTE / MANT.</span>
                <span class="kpi-value"><?= number_format($stats['maintenance'] ?? 15) ?></span>
                <span class="kpi-trend text-warning"><i data-lucide="wrench"></i> 3 críticos en muelle</span>
            </div>
            <div class="kpi-card-right bg-warning-light">
                <i data-lucide="alert-circle" class="kpi-icon text-warning-color"></i>
            </div>
        </div>

        <div class="kpi-card" title="Bienes disponibles en bodega central">
            <div class="kpi-card-left">
                <span class="kpi-label">STOCK BODEGA</span>
                <span class="kpi-value"><?= number_format($stats['available'] ?? 240) ?></span>
                <span class="kpi-trend text-info"><i data-lucide="check-circle-2"></i> Listo para entrega</span>
            </div>
            <div class="kpi-card-right bg-info-light">
                <i data-lucide="archive" class="kpi-icon text-info-color"></i>
            </div>
        </div>
    </div>

    <!-- Active Inventory Lists / Move logs -->
    <div class="section-container" style="margin-top: 30px;">
        <h3 class="section-title"><i data-lucide="history" style="display:inline-block; vertical-align:middle; margin-right:8px;"></i>Historial de Movimientos de Activos</h3>
        <p class="section-subtitle">Auditoría transaccional de asignaciones, devoluciones y mantenimientos técnicos gestionados de forma modular.</p>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="font-family: 'Fira Code', monospace;">Código Activo</th>
                        <th>Descripción del Bien</th>
                        <th>Responsable Asignado</th>
                        <th style="font-family: 'Fira Code', monospace;">Fecha Transacción</th>
                        <th style="text-align: center;">Tipo de Operación</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($movements)): ?>
                        <?php foreach ($movements as $m): ?>
                            <tr>
                                <td style="font-weight: bold; font-family: 'Fira Code', monospace; color: var(--text-accent);">
                                    <?= htmlspecialchars($m['id']) ?>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="font-weight: 500; color: var(--text-color);"><?= htmlspecialchars($m['description']) ?></span>
                                        <span style="font-size: 0.75rem; color: var(--text-muted);">Categoría: Equipos Tecnológicos</span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($m['responsible']) ?></td>
                                <td style="font-family: 'Fira Code', monospace; color: var(--text-muted);"><?= htmlspecialchars($m['date']) ?></td>
                                <td style="text-align: center;">
                                    <?php if ($m['type'] === 'ASIGNACIÓN'): ?>
                                        <span class="badge badge-success"><i data-lucide="arrow-up-right" style="width:12px; height:12px;"></i> Asignación</span>
                                    <?php elseif ($m['type'] === 'DEVOLUCIÓN'): ?>
                                        <span class="badge badge-info"><i data-lucide="arrow-down-left" style="width:12px; height:12px;"></i> Devolución</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><i data-lucide="wrench" style="width:12px; height:12px;"></i> Mantenimiento</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <button class="btn btn-secondary" style="padding: 4px 8px; font-size: 0.75rem;" onclick="alert('Auditoría física firmada digitalmente para <?= $m['id'] ?>')">
                                        <i data-lucide="file-check" style="width:14px; height:14px;"></i>
                                        <span>Auditar</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No se registran movimientos recientes.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
