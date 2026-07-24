<?php
/**
 * Dynamic Form Rendering Engine — Premium High Fidelity UI/UX Cockpit
 * Renders operational grids, parameters, mock data, and instant micro-interactions for any Menu Option.
 */
$color = $option['color_tema'] ?? '#0F172A';
$icon = normalizeFaIcon($option['icono'] ?? 'circle');
$label = $option['descripcion_interfaz'] ?? 'Formulario General';
$dottedCode = $option['codigo_secuencial'] ?? '0.0.0.0';
$depto = $option['nombre_departamento'] ?? 'Dirección General';

// Generate some highly realistic mock records for visual dense layout
$mockRecords = [
    ['id' => 'REC-0932', 'code' => 'APM-TH-2026-01', 'date' => '2026-05-15', 'operator' => 'Dra. Ana Castro Lara', 'status' => 'Procesado', 'amount' => '$1,200.00'],
    ['id' => 'REC-0933', 'code' => 'APM-ADM-2026-04', 'date' => '2026-05-18', 'operator' => 'Marco Recalde', 'status' => 'Aprobado', 'amount' => '$3,400.00'],
    ['id' => 'REC-0934', 'code' => 'APM-INF-2026-11', 'date' => '2026-05-22', 'operator' => 'Ing. Felipe Mora', 'status' => 'Pendiente', 'amount' => '$2,800.00'],
    ['id' => 'REC-0935', 'code' => 'APM-JUR-2026-08', 'date' => '2026-05-26', 'operator' => 'Abg. Rosa Pita', 'status' => 'Procesado', 'amount' => '$4,500.00'],
    ['id' => 'REC-0936', 'code' => 'APM-FIN-2026-02', 'date' => '2026-05-30', 'operator' => 'Hugo Muñoz Cevallos', 'status' => 'Aprobado', 'amount' => '$6,100.00']
];
?>

<style>
    .df-cockpit-wrapper {
        animation: dfFadeSlideIn 0.3s ease-out;
        display: flex;
        flex-direction: column;
        gap: 24px;
        max-width: 1400px;
        margin: 0 auto;
        width: 100%;
    }

    @keyframes dfFadeSlideIn {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Premium Header Card */
    .df-header-card {
        padding: 24px 30px;
        border-radius: 16px;
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
    }

    .df-header-gradient {
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, <?= $color ?>f0 0%, #0F172A 100%);
        z-index: 1;
    }

    .df-header-content {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .df-icon-box {
        width: 58px;
        height: 58px;
        border-radius: 12px;
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        border: 1px solid rgba(255,255,255,0.15);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .df-title-text h2 {
        margin: 0;
        font-family: 'Outfit', sans-serif;
        font-size: 21px;
        font-weight: 800;
        letter-spacing: -0.01em;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .df-title-text p {
        margin: 4px 0 0 0;
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        opacity: 0.8;
    }

    .df-header-badge-box {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 6px;
    }

    .df-code-badge {
        font-family: 'Fira Code', 'JetBrains Mono', monospace;
        font-size: 11px;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 20px;
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.25);
        color: white;
        text-shadow: 0 1px 2px rgba(0,0,0,0.15);
    }

    /* Content Cards */
    .df-panel-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
    }

    .df-card {
        background: var(--surface-app, #ffffff);
        border: 1.5px solid var(--border-app, #e2e8f0);
        border-radius: 16px;
        padding: 24px;
        box-shadow: var(--shadow, 0 1px 3px rgba(0,0,0,0.05));
    }

    /* Interactive Form Inputs */
    .df-form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 18px;
        margin-bottom: 24px;
    }

    .df-form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .df-form-group label {
        font-family: 'Inter', sans-serif;
        font-size: 12px;
        font-weight: 700;
        color: var(--text-app, #0F172A);
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .df-input {
        width: 100%;
        padding: 10px 14px;
        background: var(--surface-app, #ffffff);
        border: 1.5px solid var(--border-app, #e2e8f0);
        border-radius: 8px;
        color: var(--text-app, #0F172A);
        font-family: 'Inter', sans-serif;
        font-size: 13.5px;
        outline: none;
        transition: all 0.2s ease;
    }

    .df-input:focus {
        border-color: <?= $color ?>;
        box-shadow: 0 0 0 3px <?= $color ?>20;
    }

    /* Form Actions */
    .df-actions-row {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
        border-top: 1.5px solid var(--border-app, #e2e8f0);
        padding-top: 20px;
        flex-wrap: wrap;
    }

    /* High Fidelity Table styles */
    .df-table-container {
        border-radius: 12px;
        border: 1.5px solid var(--border-app, #e2e8f0);
        overflow: hidden;
        margin-top: 16px;
    }

    .df-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .df-table th {
        background: rgba(15, 23, 42, 0.02);
        padding: 12px 16px;
        font-family: 'Inter', sans-serif;
        font-size: 11px;
        font-weight: 700;
        color: var(--text-muted, #64748B);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1.5px solid var(--border-app, #e2e8f0);
    }

    .df-table td {
        padding: 12px 16px;
        font-family: 'Inter', sans-serif;
        font-size: 13px;
        border-bottom: 1px solid var(--border-app, #e2e8f0);
        color: var(--text-app, #0F172A);
    }

    .df-table tr:last-child td {
        border-bottom: none;
    }

    .df-table tr:hover td {
        background: <?= $color ?>08;
    }

    /* Telemetry Toast style */
    .df-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 100000;
        padding: 16px 20px;
        border-radius: 12px;
        background: #0F172A;
        border: 1.5px solid rgba(255,255,255,0.08);
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        color: white;
        display: flex;
        align-items: center;
        gap: 12px;
        font-family: 'Inter', sans-serif;
        font-size: 13.5px;
        font-weight: 500;
        transform: translateY(20px);
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .df-toast.show {
        transform: translateY(0);
        opacity: 1;
        pointer-events: auto;
    }
</style>

<div class="df-cockpit-wrapper">
    <!-- Breadcrumbs -->
    <div style="font-family:'Inter',sans-serif; font-size:12px; color:var(--text-muted); display:flex; align-items:center; gap:8px;">
        <span>Módulos</span>
        <i class="fa-solid fa-chevron-right" style="font-size:9px; opacity:0.6;"></i>
        <span><?= htmlspecialchars($depto) ?></span>
        <i class="fa-solid fa-chevron-right" style="font-size:9px; opacity:0.6;"></i>
        <span style="font-weight:600; color:<?= $color ?>;"><?= htmlspecialchars($label) ?></span>
    </div>

    <!-- Header Banner Card -->
    <div class="df-header-card">
        <div class="df-header-gradient"></div>
        <div class="df-header-content">
            <div class="df-icon-box">
                <i class="<?= $icon ?>"></i>
            </div>
            <div class="df-title-text">
                <h2><?= htmlspecialchars($label) ?></h2>
                <p><i class="fa-solid fa-building" style="margin-right:4px; opacity:0.8;"></i> Área Operativa: <strong><?= htmlspecialchars($depto) ?></strong></p>
            </div>
        </div>
        <div class="df-header-badge-box">
            <span class="df-code-badge" title="Código de Jerarquía Secuencial"><?= $dottedCode ?></span>
            <span style="font-family:'Inter',sans-serif; font-size:10px; color:rgba(255,255,255,0.7);"><i class="fa-solid fa-clock" style="margin-right:4px;"></i> Actualizado: Real-Time</span>
        </div>
    </div>

    <!-- Interactive Panel -->
    <div class="df-panel-container">
        <!-- Parameters and Filters -->
        <div class="df-card">
            <div style="border-bottom:1.5px solid var(--border-app); padding-bottom:14px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <h3 style="margin:0; font-family:'Outfit',sans-serif; font-size:16px; font-weight:700; color:var(--text-app);"><i class="fa-solid fa-sliders" style="margin-right:6px; color:<?= $color ?>;"></i> Parámetros de Operación</h3>
                    <p style="margin:2px 0 0 0; font-family:'Inter',sans-serif; font-size:12px; color:var(--text-muted);">Especifique filtros para la consulta o ingrese nuevos datos de registro.</p>
                </div>
                <span class="badge" style="background:<?= $color ?>15; border-color:<?= $color ?>30; color:<?= $color ?>; font-weight:700;">FORMULARIO ACTIVO</span>
            </div>

            <!-- Parameters Grid -->
            <form id="df-operation-form" onsubmit="handleMockSubmit(event)">
                <div class="df-form-grid">
                    <div class="df-form-group">
                        <label>Código Referencia</label>
                        <input type="text" class="df-input" name="ref_code" placeholder="Ej: APM-<?= $option['codigo_depto'] ?>-2026" required>
                    </div>

                    <div class="df-form-group">
                        <label>Fecha Proceso</label>
                        <input type="date" class="df-input" name="proc_date" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="df-form-group">
                        <label>Colaborador Responsable</label>
                        <input type="text" class="df-input" name="operator" value="<?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador') ?>" required readonly>
                    </div>

                    <div class="df-form-group">
                        <label>Estado de Transacción</label>
                        <select class="df-input" name="status">
                            <option value="Aprobado">Aprobado</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Procesado">Procesado</option>
                        </select>
                    </div>
                </div>

                <div class="df-actions-row">
                    <button type="button" class="btn btn-secondary" onclick="simulateReset()"><i class="fa-solid fa-rotate-left"></i> Limpiar Filtros</button>
                    <button type="button" class="btn btn-secondary" onclick="simulateSearch()"><i class="fa-solid fa-magnifying-glass"></i> Consultar Base</button>
                    <button type="submit" class="btn" style="background:<?= $color ?>; color:white; border:none; box-shadow:0 4px 12px <?= $color ?>30;" id="df-save-btn"><i class="fa-solid fa-save"></i> Procesar Registro</button>
                </div>
            </form>
        </div>

        <!-- Dense Data Mock Table -->
        <div class="df-card">
            <div style="border-bottom:1.5px solid var(--border-app); padding-bottom:14px; margin-bottom:16px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                <div>
                    <h3 style="margin:0; font-family:'Outfit',sans-serif; font-size:16px; font-weight:700; color:var(--text-app);"><i class="fa-solid fa-table-list" style="margin-right:6px; color:<?= $color ?>;"></i> Bitácora General de Datos</h3>
                    <p style="margin:2px 0 0 0; font-family:'Inter',sans-serif; font-size:12px; color:var(--text-muted);">Registros cargados de manera transaccional desde el módulo: <strong><?= htmlspecialchars($depto) ?></strong></p>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button class="btn btn-secondary" style="padding:6px 12px; font-size:12px;" onclick="simulateExport()"><i class="fa-solid fa-file-csv" style="color:#166534;"></i> Exportar CSV</button>
                </div>
            </div>

            <!-- Table visual -->
            <div class="df-table-container">
                <table class="df-table">
                    <thead>
                        <tr>
                            <th>ID Registro</th>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Operador Responsable</th>
                            <th style="text-align: center;">Estado</th>
                            <th style="text-align: right;">Total Transacción</th>
                        </tr>
                    </thead>
                    <tbody id="df-mock-tbody">
                        <?php foreach ($mockRecords as $rec): 
                            $statusBadgeClass = 'badge-success';
                            if ($rec['status'] === 'Pendiente') $statusBadgeClass = 'badge-danger';
                            else if ($rec['status'] === 'Procesado') $statusBadgeClass = 'badge-primary';
                        ?>
                            <tr id="row-<?= $rec['id'] ?>">
                                <td style="font-family:'Fira Code','JetBrains Mono',monospace; font-size:12px; font-weight:600; color:<?= $color ?>;"><?= $rec['id'] ?></td>
                                <td style="font-weight:600;"><?= $rec['code'] ?></td>
                                <td><?= $rec['date'] ?></td>
                                <td><?= $rec['operator'] ?></td>
                                <td style="text-align: center;"><span class="badge <?= $statusBadgeClass ?>"><?= $rec['status'] ?></span></td>
                                <td style="text-align: right; font-weight:700; font-family:'Outfit',sans-serif;"><?= $rec['amount'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Telemetry success toast -->
<div class="df-toast" id="df-success-toast">
    <div style="width:24px; height:24px; border-radius:50%; background:#10B981; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
        <i class="fa-solid fa-check" style="color:white; font-size:12px;"></i>
    </div>
    <div style="flex:1;">
        <strong style="display:block; font-size:13px; color:white;" id="df-toast-title">Acción Completada</strong>
        <span style="font-size:11.5px; color:#94A3B8;" id="df-toast-message">Operación ejecutada con éxito.</span>
    </div>
</div>

<script>
    // Micro-interactions and simulation handlers
    function showTelemetryToast(title, message) {
        const toast = document.getElementById('df-success-toast');
        document.getElementById('df-toast-title').innerText = title;
        document.getElementById('df-toast-message').innerText = message;
        
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
        }, 2200);
    }

    function handleMockSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const saveBtn = document.getElementById('df-save-btn');
        
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Guardando...';
        
        const code = form.ref_code.value;
        const date = form.proc_date.value;
        const operator = form.operator.value;
        const status = form.status.value;
        
        setTimeout(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fa-solid fa-save"></i> Procesar Registro';
            
            // Add a mock row to the table dynamically
            const tbody = document.getElementById('df-mock-tbody');
            const newId = 'REC-' + Math.floor(Math.random() * 9000 + 1000);
            const statusBadgeClass = status === 'Pendiente' ? 'badge-danger' : (status === 'Procesado' ? 'badge-primary' : 'badge-success');
            
            const tr = document.createElement('tr');
            tr.id = 'row-' + newId;
            tr.style.background = 'rgba(16, 185, 129, 0.05)';
            tr.innerHTML = `
                <td style="font-family:'Fira Code','JetBrains Mono',monospace; font-size:12px; font-weight:600; color:<?= $color ?>;">${newId}</td>
                <td style="font-weight:600;">${code}</td>
                <td>${date}</td>
                <td>${operator}</td>
                <td style="text-align: center;"><span class="badge ${statusBadgeClass}">${status}</span></td>
                <td style="text-align: right; font-weight:700; font-family:'Outfit',sans-serif;">$${(Math.random() * 5000 + 500).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}</td>
            `;
            
            // Insert at the top of the table body
            tbody.insertBefore(tr, tbody.firstChild);
            
            // Highlight row temporarily
            setTimeout(() => {
                tr.style.background = '';
                tr.style.transition = 'background 0.5s ease';
            }, 1000);
            
            showTelemetryToast("Registro Guardado", `La transacción ${newId} se ha grabado en el sistema.`);
            form.reset();
            form.operator.value = "<?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador') ?>";
        }, 800);
    }

    function simulateSearch() {
        const tbody = document.getElementById('df-mock-tbody');
        tbody.style.opacity = "0.5";
        
        showTelemetryToast("Consultando Base", "Buscando coincidencias de datos en tiempo real...");
        
        setTimeout(() => {
            tbody.style.opacity = "1";
            showTelemetryToast("Consulta Exitosa", "Se han cargado los registros transaccionales más recientes.");
        }, 600);
    }

    function simulateReset() {
        document.getElementById('df-operation-form').reset();
        document.getElementById('df-operation-form').operator.value = "<?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador') ?>";
        showTelemetryToast("Filtros Limpiados", "Se han restablecido los campos de consulta.");
    }

    function simulateExport() {
        showTelemetryToast("Generando Reporte", "Compilando registros transaccionales en formato CSV...");
        setTimeout(() => {
            showTelemetryToast("Exportación Lista", "Reporte descargado correctamente en su equipo local.");
        }, 700);
    }
</script>
