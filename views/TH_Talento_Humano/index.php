<?php
/**
 * Talento Humano list dashboard.
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = dirname($scriptName);
$baseUrl = str_replace('\\', '/', $baseUrl);
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}
?>
<div class="th-dashboard-container">
    <div class="welcome-banner" style="background: linear-gradient(135deg, #0f172a, #1e3a5f); border-bottom: 4px solid var(--accent-color);">
        <div class="welcome-text">
            <h2 class="welcome-title"><i data-lucide="contact" style="display:inline-block; vertical-align:middle; margin-right:10px;"></i>Módulo de Talento Humano Central</h2>
            <p class="welcome-desc">Gestión integral del personal portuario. Filtra por departamentos, busca expedientes, descarga reportes ejecutivos en formato CSV y supervisa novedades de nómina.</p>
        </div>
        <div class="welcome-visual">
            <i data-lucide="users" class="visual-icon"></i>
        </div>
    </div>

    <!-- Live Alerts -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" style="margin-top: 15px;">
            <i data-lucide="check-circle" class="alert-icon"></i>
            <span><?= htmlspecialchars($_SESSION['success']) ?></span>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger" style="margin-top: 15px;">
            <i data-lucide="alert-triangle" class="alert-icon"></i>
            <span><?= htmlspecialchars($_SESSION['error']) ?></span>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Interactive Filters Section -->
    <div class="section-container" style="margin-top: 20px;">
        <form action="<?= $baseUrl ?>/talento-humano" method="GET" class="filter-form" id="th-filter-form" data-spa-form>
            <div class="filter-row">
                <!-- Search Input -->
                <div class="form-group flex-2">
                    <label for="search" class="form-label">Buscar Colaborador</label>
                    <div class="input-wrapper">
                        <i data-lucide="search" class="input-icon"></i>
                        <input type="text" id="search" name="search" class="form-control" placeholder="Buscar por Nombre, Cédula o Correo..." value="<?= htmlspecialchars($filters['search']) ?>">
                    </div>
                </div>

                <!-- Department Selector -->
                <div class="form-group">
                    <label for="dept" class="form-label">Departamento</label>
                    <div class="input-wrapper">
                        <select id="dept" name="dept" class="form-control">
                            <option value="">-- Todos los Departamentos --</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= htmlspecialchars($d['codigo_depto']) ?>" <?= $filters['dept'] == $d['codigo_depto'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['nombre_departamento']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Status Selector -->
                <div class="form-group">
                    <label for="active" class="form-label">Estado Laboral</label>
                    <div class="input-wrapper">
                        <select id="active" name="active" class="form-control">
                            <option value="1" <?= $filters['active'] === '1' ? 'selected' : '' ?>>Activos</option>
                            <option value="0" <?= $filters['active'] === '0' ? 'selected' : '' ?>>Inactivos</option>
                            <option value="" <?= $filters['active'] === '' ? 'selected' : '' ?>>Todos</option>
                        </select>
                    </div>
                </div>

                <!-- Actions buttons -->
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary" title="Aplicar Filtros">
                        <i data-lucide="sliders-horizontal"></i>
                        <span>Filtrar</span>
                    </button>
                    
                    <a href="<?= $baseUrl ?>/talento-humano/exportar?search=<?= urlencode($filters['search']) ?>&dept=<?= urlencode($filters['dept']) ?>&active=<?= urlencode($filters['active']) ?>" class="btn btn-secondary btn-export" title="Descargar nómina filtrada a CSV">
                        <i data-lucide="download"></i>
                        <span>Exportar CSV</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Personal Cards Grid List -->
    <div class="section-container" style="margin-top: 20px; background: transparent; border: none; padding: 0;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
            <span style="font-family:'Fira Code', monospace; font-size:0.9rem; color:var(--text-muted);">Mostrando <?= count($employees) ?> de <?= $total ?> expedientes de personal</span>
        </div>

        <?php if (!empty($employees)): ?>
            <div class="personal-grid">
                <?php foreach ($employees as $emp): ?>
                    <div class="personal-card <?= !$emp['activo'] ? 'inactive' : '' ?>">
                        <!-- Header with dynamic department theme -->
                        <div class="personal-card-header">
                            <div class="avatar-circle">
                                <span><?= strtoupper(substr($emp['nombre_completo'], 0, 1)) ?></span>
                            </div>
                            <div class="personal-card-meta">
                                <h4 class="personal-fullname" title="<?= htmlspecialchars($emp['nombre_completo']) ?>"><?= htmlspecialchars($emp['nombre_completo']) ?></h4>
                                <span class="personal-role"><?= htmlspecialchars($emp['cargo_institucional']) ?></span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="personal-card-body">
                            <div class="personal-data-row">
                                <span class="pd-lbl">Cédula:</span>
                                <span class="pd-val code"><?= htmlspecialchars($emp['cedula']) ?></span>
                            </div>
                            <div class="personal-data-row">
                                <span class="pd-lbl">Departamento:</span>
                                <span class="pd-val text-truncate" title="<?= htmlspecialchars($emp['nombre_departamento']) ?>"><?= htmlspecialchars($emp['nombre_departamento']) ?></span>
                            </div>
                            <div class="personal-data-row">
                                <span class="pd-lbl">Contrato:</span>
                                <span class="pd-val" style="color:var(--accent-color); font-weight:bold;"><?= htmlspecialchars($emp['contrato_tipo_vigente'] ?? 'SIN CONTRATO') ?></span>
                            </div>
                            <div class="personal-data-row">
                                <span class="pd-lbl">Remuneración:</span>
                                <span class="pd-val text-success" style="font-weight:bold; font-family:'Fira Code', monospace;">$<?= number_format($emp['remuneracion'] ?? 0, 2) ?></span>
                            </div>
                            <div class="personal-data-row">
                                <span class="pd-lbl">Novedades Médicas:</span>
                                <?php if ($emp['novedades_activas'] > 0): ?>
                                    <span class="badge badge-warning"><i data-lucide="activity"></i> <?= $emp['novedades_activas'] ?> activas</span>
                                <?php else: ?>
                                    <span class="badge badge-success">0 reportadas</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Card Footer actions -->
                        <div class="personal-card-footer">
                            <a href="<?= $baseUrl ?>/talento-humano/ficha?id=<?= $emp['id_empleado'] ?>" class="btn btn-block btn-secondary" data-spa>
                                <i data-lucide="eye" style="margin-right:6px;"></i>
                                <span>Ver Ficha de Personal</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Custom Professional Pagination Controls -->
            <?php if ($pages > 1): ?>
                <div class="pagination-wrapper">
                    <nav class="pagination-nav">
                        <!-- Previous Page -->
                        <?php if ($page > 1): ?>
                            <a href="<?= $baseUrl ?>/talento-humano?page=<?= $page - 1 ?>&search=<?= urlencode($filters['search']) ?>&dept=<?= urlencode($filters['dept']) ?>&active=<?= urlencode($filters['active']) ?>" class="btn-page" title="Página Anterior" data-spa>
                                <i data-lucide="chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Page numbers -->
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($pages, $page + 2);
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <a href="<?= $baseUrl ?>/talento-humano?page=<?= $i ?>&search=<?= urlencode($filters['search']) ?>&dept=<?= urlencode($filters['dept']) ?>&active=<?= urlencode($filters['active']) ?>" class="btn-page <?= $i === $page ? 'active' : '' ?>" data-spa>
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Next Page -->
                        <?php if ($page < $pages): ?>
                            <a href="<?= $baseUrl ?>/talento-humano?page=<?= $page + 1 ?>&search=<?= urlencode($filters['search']) ?>&dept=<?= urlencode($filters['dept']) ?>&active=<?= urlencode($filters['active']) ?>" class="btn-page" title="Página Siguiente" data-spa>
                                <i data-lucide="chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="card" style="padding: 50px; text-align: center;">
                <i data-lucide="folder-open" style="width: 48px; height: 48px; color: var(--text-muted); margin: 0 auto 15px;"></i>
                <h4 style="margin: 0; font-size: 1.2rem; color: var(--text-muted);">No se encontraron expedientes de personal</h4>
                <p style="margin: 5px 0 0; color: var(--text-muted); font-size: 0.9rem;">Prueba modificando tus términos de búsqueda o filtros.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    (function() {
        const form = document.getElementById('th-filter-form');
        if (form) {
            const autoSubmit = function() {
                form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            };
            const deptSelect = document.getElementById('dept');
            const activeSelect = document.getElementById('active');
            if (deptSelect) deptSelect.addEventListener('change', autoSubmit);
            if (activeSelect) activeSelect.addEventListener('change', autoSubmit);
        }
    })();
</script>
