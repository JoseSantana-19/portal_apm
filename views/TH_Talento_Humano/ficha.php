<?php
/**
 * Detailed personal card view. Shows contracts, adendas, medical logs, and forms.
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = dirname($scriptName);
$baseUrl = str_replace('\\', '/', $baseUrl);
if ($baseUrl === '/' || $baseUrl === '\\') {
    $baseUrl = '';
}
?>
<style>
    .th-ficha-tabs-wrapper {
        display: flex;
        gap: 12px;
        border-bottom: 1.5px solid var(--border-app, var(--border-color));
        padding-bottom: 4px;
        margin-bottom: 24px;
        overflow-x: auto;
        scrollbar-width: none; /* Hide scrollbar Firefox */
    }
    
    .th-ficha-tabs-wrapper::-webkit-scrollbar {
        display: none; /* Hide scrollbar Chrome/Safari */
    }

    .th-tab-btn {
        background: transparent;
        border: none;
        outline: none;
        color: var(--text-muted);
        font-family: 'Outfit', sans-serif;
        font-size: 13.5px;
        font-weight: 700;
        padding: 12px 20px;
        border-radius: 8px 8px 0 0;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }

    .th-tab-btn i {
        width: 16px;
        height: 16px;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), color 0.3s ease;
    }

    .th-tab-btn:hover {
        color: var(--text-app, var(--text-color));
        background: rgba(30, 58, 138, 0.04);
        transform: translateY(-2px);
    }
    
    .th-tab-btn:hover i {
        transform: scale(1.15) rotate(5deg);
    }

    .th-tab-btn.active {
        color: var(--accent-color, #5b21b6);
        background: rgba(91, 33, 182, 0.06);
        border-radius: 8px;
    }

    /* Floating glowing accent underline for the active tab */
    .th-tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -5.5px;
        left: 12px;
        right: 12px;
        height: 3.5px;
        background: var(--accent-color, #5b21b6);
        border-radius: 4px;
        box-shadow: 0 0 12px var(--accent-color);
        animation: tabIndicatorPulse 1.8s infinite alternate;
    }

    @keyframes tabIndicatorPulse {
        from { opacity: 0.7; transform: scaleX(0.95); }
        to { opacity: 1; transform: scaleX(1); }
    }
</style>

<div class="ficha-personal-container">
    <!-- Back Button -->
    <div style="margin-bottom: 20px;">
        <a href="<?= $baseUrl ?>/talento-humano" class="btn btn-secondary btn-icon-left" data-spa>
            <i data-lucide="arrow-left"></i>
            <span>Volver a la Lista de Personal</span>
        </a>
    </div>

    <!-- Personal Profile Summary Banner -->
    <div class="welcome-banner" style="background: linear-gradient(135deg, #1e3a8a, #0f172a); border-bottom: 4px solid var(--accent-color);">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="avatar-circle" style="width: 70px; height: 70px; font-size: 2rem;">
                <span><?= strtoupper(substr($profile['nombre_completo'], 0, 1)) ?></span>
            </div>
            <div class="welcome-text">
                <h2 class="welcome-title"><?= htmlspecialchars($profile['nombre_completo']) ?></h2>
                <p class="welcome-desc"><?= htmlspecialchars($profile['cargo_institucional']) ?> — <?= htmlspecialchars($profile['nombre_departamento']) ?></p>
            </div>
        </div>
        <div style="margin-left: auto;">
            <span class="badge <?= $profile['activo'] ? 'badge-success' : 'badge-danger' ?>" style="font-size: 0.9rem; padding: 6px 14px;">
                <?= $profile['activo'] ? 'CONTRATO ACTIVO' : 'CONTRATO INACTIVO' ?>
            </span>
        </div>
    </div>

    <!-- Core Grid: Profile info (Left) + Medical Logs Adder (Right) -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-top: 25px;" class="grid-responsive-layout">
        
        <!-- Left: Core Profile Information Card -->
        <div class="card" style="padding: 20px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px;">
            <div style="border-bottom: 2px solid var(--border-color); padding-bottom: 10px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <i data-lucide="contact" style="color: var(--accent-color);"></i>
                <h3 style="margin: 0; font-size: 1.15rem; color: var(--text-color);">Información del Expediente</h3>
            </div>
            
            <div class="profile-details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px 30px;">
                <div>
                    <span class="pd-lbl" style="display:block; font-size:0.8rem; color:var(--text-muted); text-transform:uppercase;">Cédula de Identidad</span>
                    <span class="pd-val code" style="font-size:1.05rem; font-weight:bold; color:var(--text-color);"><?= htmlspecialchars($profile['cedula']) ?></span>
                </div>
                <div>
                    <span class="pd-lbl" style="display:block; font-size:0.8rem; color:var(--text-muted); text-transform:uppercase;">Correo Electrónico</span>
                    <span class="pd-val" style="font-size:1.05rem; color:var(--text-color);"><?= htmlspecialchars($profile['correo']) ?></span>
                </div>
                <div>
                    <span class="pd-lbl" style="display:block; font-size:0.8rem; color:var(--text-muted); text-transform:uppercase;">Edad / Género</span>
                    <span class="pd-val" style="font-size:1.05rem; color:var(--text-color);"><?= htmlspecialchars($profile['edad']) ?> años / <?= htmlspecialchars($profile['genero']) ?></span>
                </div>
                <div>
                    <span class="pd-lbl" style="display:block; font-size:0.8rem; color:var(--text-muted); text-transform:uppercase;">Fecha de Ingreso</span>
                    <span class="pd-val" style="font-size:1.05rem; color:var(--text-color);"><?= date('d/m/Y', strtotime($profile['fecha_ingreso'])) ?> (<?= $profile['meses_servicio'] ?> meses)</span>
                </div>
                <div>
                    <span class="pd-lbl" style="display:block; font-size:0.8rem; color:var(--text-muted); text-transform:uppercase;">Tipo Contrato Vigente</span>
                    <span class="pd-val" style="font-size:1.05rem; color:var(--accent-color); font-weight:bold;"><?= htmlspecialchars($profile['contrato_tipo_vigente'] ?? 'N/A') ?></span>
                </div>
                <div>
                    <span class="pd-lbl" style="display:block; font-size:0.8rem; color:var(--text-muted); text-transform:uppercase;">Remuneración Nominal</span>
                    <span class="pd-val text-success" style="font-size:1.15rem; font-weight:bold; font-family:'Fira Code', monospace;">$<?= number_format($profile['remuneracion'] ?? 0, 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Right: Register Medical Novelty (Transaccional) -->
        <div class="card" style="padding: 20px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px;">
            <div style="border-bottom: 2px solid var(--border-color); padding-bottom: 10px; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <i data-lucide="heart-pulse" style="color: #f43f5e;"></i>
                <h3 style="margin: 0; font-size: 1.15rem; color: var(--text-color);">Registrar Novedad Médica</h3>
            </div>

            <form action="<?= $baseUrl ?>/talento-humano/novedad-medica" method="POST" class="medical-form" id="medical-form">
                <input type="hidden" name="id_empleado" value="<?= $profile['id_empleado'] ?>">

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="tipo_novedad" class="form-label" style="font-size: 0.8rem;">Tipo de Incidencia</label>
                    <select id="tipo_novedad" name="tipo_novedad" class="form-control" required style="padding: 8px;">
                        <option value="">-- Seleccionar Tipo --</option>
                        <option value="Licencia por Enfermedad">Licencia por Enfermedad</option>
                        <option value="Maternidad/Paternidad">Maternidad / Paternidad</option>
                        <option value="Cita Médica Seguro Social">Cita Médica Seguro Social</option>
                        <option value="Accidente de Trabajo">Accidente de Trabajo</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="fecha_inicio" class="form-label" style="font-size: 0.8rem;">Fecha de Inicio</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control" required style="padding: 6px;">
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="fecha_fin" class="form-label" style="font-size: 0.8rem;">Fecha de Fin (Opcional)</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" class="form-control" style="padding: 6px;">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="descripcion" class="form-label" style="font-size: 0.8rem;">Observaciones Médicas</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="3" placeholder="Detallar diagnóstico u observaciones..." style="padding: 8px; font-size: 0.85rem;" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="background:#f43f5e; border-color:#f43f5e; font-weight:bold;">
                    <i data-lucide="plus-circle" style="margin-right:6px; display:inline-block; vertical-align:middle; width: 16px; height: 16px;"></i>
                    <span>Registrar Incidente</span>
                </button>
            </form>
        </div>
    </div>

    <!-- History Tabs (Contracts, Adendas, Medical logs) -->
    <div class="section-container" style="margin-top: 30px; padding: 20px;">
        <!-- Tabs Buttons Header -->
        <div class="th-ficha-tabs-wrapper" style="border-bottom:1.5px solid var(--border-color); margin-bottom: 20px;">
            <button class="th-tab-btn active" onclick="switchFichaTab('contracts-tab', this)">
                <i data-lucide="file-text"></i> Historial Contratos
            </button>
            <button class="th-tab-btn" onclick="switchFichaTab('adendas-tab', this)">
                <i data-lucide="trending-up"></i> Historial Adendas
            </button>
            <button class="th-tab-btn" onclick="switchFichaTab('medicals-tab', this)">
                <i data-lucide="activity"></i> Historial Médico
            </button>
        </div>

        <!-- Tab 1: Contracts -->
        <div id="contracts-tab" class="ficha-tab-panel" style="display: block;">
            <?php if (!empty($contracts)): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tipo de Contrato</th>
                                <th>Fecha de Inicio</th>
                                <th>Fecha de Fin</th>
                                <th>Remuneración</th>
                                <th style="text-align: center;">Estado</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contracts as $c): ?>
                                <tr>
                                    <td style="font-weight: bold;"><?= htmlspecialchars($c['tipo_contrato']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($c['fecha_inicio'])) ?></td>
                                    <td><?= $c['fecha_fin'] ? date('d/m/Y', strtotime($c['fecha_fin'])) : 'Indefinido' ?></td>
                                    <td style="font-family: 'Fira Code', monospace; font-weight: bold; color: var(--accent-color);">$<?= number_format($c['remuneracion'], 2) ?></td>
                                    <td style="text-align: center;">
                                        <span class="badge <?= $c['estado'] === 'VIGENTE' ? 'badge-success' : 'badge-danger' ?>" style="font-size:0.75rem;">
                                            <?= htmlspecialchars($c['estado']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($c['observaciones'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-muted); font-size: 0.9rem; padding: 20px;">No se registran contratos anteriores en el expediente.</p>
            <?php endif; ?>
        </div>

        <!-- Tab 2: Salary Adendas -->
        <div id="adendas-tab" class="ficha-tab-panel" style="display: none;">
            <?php if (!empty($adendas)): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Fecha Adenda</th>
                                <th>Descripción</th>
                                <th>Tipo Modificación</th>
                                <th>Valor Anterior</th>
                                <th>Valor Nuevo</th>
                                <th>Registrado Por</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adendas as $a): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($a['fecha_adenda'])) ?></td>
                                    <td style="font-weight: bold;"><?= htmlspecialchars($a['descripcion']) ?></td>
                                    <td><?= htmlspecialchars($a['tipo_modificacion']) ?></td>
                                    <td style="font-family: 'Fira Code', monospace; color: var(--text-muted);">$<?= number_format($a['valor_anterior'], 2) ?></td>
                                    <td style="font-family: 'Fira Code', monospace; font-weight: bold; color: var(--accent-color);">$<?= number_format($a['valor_nuevo'], 2) ?></td>
                                    <td><?= htmlspecialchars($a['registrado_por'] ?? 'Sistema') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-muted); font-size: 0.9rem; padding: 20px;">No se registran incrementos o adendas contractuales en el expediente.</p>
            <?php endif; ?>
        </div>

        <!-- Tab 3: Medical Leaves -->
        <div id="medicals-tab" class="ficha-tab-panel" style="display: none;">
            <?php if (!empty($medicals)): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tipo de Novedad</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Descripción / Diagnóstico</th>
                                <th>Registrado Por</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicals as $m): ?>
                                <tr>
                                    <td style="font-weight: bold; color: #f43f5e;"><?= htmlspecialchars($m['tipo_novedad']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($m['fecha_inicio'])) ?></td>
                                    <td><?= $m['fecha_fin'] ? date('d/m/Y', strtotime($m['fecha_fin'])) : 'No especificada' ?></td>
                                    <td><?= htmlspecialchars($m['descripcion']) ?></td>
                                    <td><?= htmlspecialchars($m['registrado_por'] ?? 'Sistema') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: var(--text-muted); font-size: 0.9rem; padding: 20px;">No se registran novedades médicas históricas en el expediente.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function switchFichaTab(tabId, button) {
        // Hide all tab panels
        document.querySelectorAll(".ficha-tab-panel").forEach(p => p.style.display = "none");
        
        // Remove active class from buttons
        document.querySelectorAll(".th-tab-btn").forEach(b => b.classList.remove("active"));
        
        // Show current panel
        const panel = document.getElementById(tabId);
        if (panel) {
            panel.style.display = "block";
        }
        
        // Add active class to clicked button
        if (button) {
            button.classList.add("active");
        }
    }
    
    // Auto-initialize Lucide Icons on view render
    document.addEventListener("DOMContentLoaded", function() {
        lucide.createIcons();
    });
</script>
