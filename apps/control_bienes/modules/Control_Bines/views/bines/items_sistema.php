<?php
/**
 * ITEMS_SISTEMA.PHP - Vista de Ítems del Sistema de Inventarios
 * Rediseño Visual Completo Premium y Optimizado de Alta Fidelidad.
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
?>
<style>
/* ===== Estilos del Maestro de Ítems ===== */
.its-inv_layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}

.its-inv_layout.list-active {
    grid-template-columns: 1fr !important;
}

.its-inv_layout.list-active .preview-container {
    display: none !important;
}

@media (max-width: 1024px) {
    .its-inv_layout {
        grid-template-columns: 1fr;
    }
}

.its-form-card {
    background: var(--panel-bg);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    display: flex;
    flex-direction: column;
}

/* Tabs con Estilo Corporativo Moderno */
.its-tabs {
    display: flex;
    border-bottom: 1px solid var(--border-color);
    background: linear-gradient(to right, var(--secondary-bg), var(--panel-bg));
    align-items: center;
    padding: 0 12px;
}
.its-tab {
    padding: 18px 24px;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    color: var(--text-muted);
    border-bottom: 3px solid transparent;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    letter-spacing: 0.5px;
    text-transform: uppercase;
    user-select: none;
    display: flex;
    align-items: center;
    gap: 8px;
}
.its-tab:hover {
    color: var(--primary);
}
.its-tab.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: var(--panel-bg);
}
.its-tab-content { display: none; }
.its-tab-content.active { display: block; }

/* Action Hub / Toolbar Premium */
.its-action-hub {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    background: var(--secondary-bg);
    border-bottom: 1px solid var(--border-color);
    flex-wrap: wrap;
    gap: 16px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.its-action-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.its-btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 14px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--panel-bg);
    cursor: pointer;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-color);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.its-btn-action:hover {
    border-color: var(--primary);
    color: var(--primary);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}
.its-btn-action:active {
    transform: translateY(0);
}
.its-btn-action i {
    font-size: 14px;
}

.its-btn-action.primary-gradient {
    background: linear-gradient(135deg, #10b981, #059669);
    border-color: #10b981;
    color: white;
}
.its-btn-action.primary-gradient:hover {
    background: linear-gradient(135deg, #059669, #047857);
    box-shadow: 0 4px 12px rgba(16,185,129,0.2);
}

.its-btn-action.blue-gradient {
    background: linear-gradient(135deg, var(--primary), #2563eb);
    border-color: var(--primary);
    color: white;
}
.its-btn-action.blue-gradient:hover {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    box-shadow: 0 4px 12px rgba(59,130,246,0.2);
}

.its-btn-action.danger-gradient {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-color: #ef4444;
    color: white;
}
.its-btn-action.danger-gradient:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    box-shadow: 0 4px 12px rgba(239,68,68,0.2);
}

/* Glassmorphic Form Cards */
.form-section-card {
    background: var(--panel-bg);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 20px;
    margin-bottom: 20px;
}
.form-section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    color: var(--text-color);
    letter-spacing: 0.5px;
    border-bottom: 1px dashed var(--border-color);
    padding-bottom: 8px;
}

.its-fields-grid-new {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}

.its-field-row-new {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.its-field-row-new label {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--text-color);
}

.its-field-input-new {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    font-size: 13.5px;
    color: var(--text-color);
    background: var(--bg-color);
    outline: none;
    transition: all 0.2s;
    font-family: inherit;
}
.its-field-input-new:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
    background: var(--panel-bg);
}
.its-field-input-new[readonly] {
    background: var(--secondary-bg);
    color: var(--text-muted);
    cursor: not-allowed;
}
.its-field-input-new.mono {
    font-family: monospace;
    font-weight: 700;
    color: var(--primary);
}

/* Live Preview Card - Simulación Interactiva */
.preview-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.preview-card {
    background: linear-gradient(135deg, var(--panel-bg) 0%, var(--secondary-bg) 100%);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.05);
    position: relative;
    overflow: hidden;
}

.preview-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 120px;
    height: 120px;
    background: radial-gradient(circle, rgba(37,99,235,0.06) 0%, transparent 70%);
    border-radius: 50%;
}

.preview-badge-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.preview-badge-cat {
    padding: 4px 12px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    background: rgba(37,99,235,0.1);
    color: var(--primary);
}

.preview-code {
    font-family: monospace;
    font-weight: 700;
    font-size: 12px;
    color: var(--text-muted);
}

.preview-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-color);
    margin-bottom: 8px;
    line-height: 1.3;
}

.preview-desc {
    font-size: 13px;
    color: var(--text-muted);
    margin-bottom: 20px;
    font-style: italic;
    line-height: 1.4;
}

.preview-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    background: var(--panel-bg);
    padding: 16px;
    border-radius: 14px;
    border: 1px solid var(--border-color);
    margin-bottom: 20px;
}

.preview-stat-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.preview-stat-label {
    font-size: 11px;
    color: var(--text-muted);
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: 0.3px;
}
.preview-stat-val {
    font-size: 14px;
    font-weight: 700;
    color: var(--text-color);
}

.preview-meter-container {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.preview-meter-header {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}
.preview-meter-bar {
    width: 100%;
    height: 8px;
    background: var(--border-color);
    border-radius: 10px;
    overflow: hidden;
}
.preview-meter-fill {
    width: 0%;
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s ease, background-color 0.3s ease;
}

.table-responsive {
    border-radius: 0;
}

/* Custom Searchable Select Styles */
.custom-select-container {
    position: relative;
    width: 100%;
}
.custom-select-trigger {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--bg-color);
    cursor: pointer;
    font-size: 13.5px;
    color: var(--text-color);
    user-select: none;
    transition: all 0.2s ease;
}
.custom-select-trigger:hover {
    border-color: var(--primary);
}
.custom-select-container.open .custom-select-trigger {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
}
.custom-select-dropdown {
    display: none;
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    width: 100%;
    background: var(--panel-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    z-index: 1000;
    max-height: 280px;
    flex-direction: column;
}
.custom-select-container.open .custom-select-dropdown {
    display: flex;
}
.custom-select-search-container {
    position: relative;
    padding: 8px;
    border-bottom: 1px solid var(--border-color);
}
.custom-select-search-input {
    width: 100%;
    padding: 8px 12px 8px 30px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 13px;
    background: var(--bg-color);
    color: var(--text-color);
    outline: none;
}
.custom-select-search-container i {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 12px;
    color: var(--text-muted);
}
.custom-select-options {
    overflow-y: auto;
    max-height: 220px;
    padding: 4px;
}
.custom-select-option {
    padding: 8px 12px;
    font-size: 13px;
    color: var(--text-color);
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.15s ease;
}
.custom-select-option:hover {
    background: rgba(37, 99, 235, 0.08);
    color: var(--primary);
}
.custom-select-option.selected {
    background: var(--primary);
    color: white;
    font-weight: 600;
}
.custom-select-option.hidden {
    display: none;
}
.custom-select-no-results {
    padding: 12px;
    text-align: center;
    font-size: 12.5px;
    color: var(--text-muted);
}
@keyframes savedRowPulse {
    0%, 100% { box-shadow: inset 0 0 0 0 rgba(16,185,129,0); }
    35% { box-shadow: inset 4px 0 0 #10b981; background: rgba(16,185,129,.11); }
}
.saved-row-highlight { animation: savedRowPulse 2.4s ease; }
.maestro-items-hero {
    padding: 24px 26px;
    border-radius: 18px;
    color: #fff;
    background: linear-gradient(125deg, #172554 0%, #1d4ed8 55%, #0891b2 115%);
    box-shadow: 0 18px 38px rgba(30,64,175,.2);
}
.maestro-items-hero .page-title h1,
.maestro-items-hero .page-title p,
.maestro-items-hero label { color: #fff !important; }
.maestro-items-hero .page-title p { opacity: .78; }
.maestro-items-hero select { background: rgba(255,255,255,.96); color:#172554; border-color:rgba(255,255,255,.35); }
#maestro-items-table_wrapper { padding: 16px 20px 20px; }
#maestro-items-table_filter input { min-width:300px; height:40px; border:1px solid var(--border-color); border-radius:10px; background:var(--panel-bg); color:var(--text-color); }
</style>

<!-- InvCabecera de Página -->
<div class="page-header maestro-items-hero animate-fade-in">
    <div class="page-title">
        <h1>Maestro de Ítems</h1>
        <p>Mantenimiento y configuración del catálogo global de productos e insumos portuarios.</p>
    </div>
    <div style="display:flex;gap:12px;align-items:center;">
        <label style="font-size:12.5px;font-weight:700;color:var(--text-muted);">Grupo activo:</label>
        <select id="filtro-grupo" onchange="filtrarPorGrupo()" class="its-field-input-new" style="width:240px;">
            <option value="0">— Todos los Grupos —</option>
            <?php foreach ($grupos as $g): ?>
                <option value="<?= $g['id'] ?>" <?= ($grupoId == $g['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($g['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="its-inv_layout animate-fade-in">
    
    <!-- ===== COLUMNA IZQUIERDA: Formulario o Lista de Items ===== -->
    <div class="its-form-card">
        <!-- Tabs Superiores -->
        <div class="its-tabs">
            <div class="its-tab active" id="tab-campos" onclick="cambiarTab('campos')"><i class="fa-solid fa-pen-to-square"></i> Ficha de Campos</div>
            <div class="its-tab"        id="tab-lista"  onclick="cambiarTab('lista')"><i class="fa-solid fa-table-list"></i> Lista Completa</div>
            <div style="flex:1;display:flex;align-items:center;justify-content:flex-end;padding:8px 0;color:var(--text-muted);font-size:12px;font-weight:700;">
                <i class="fa-solid fa-bolt" style="color:var(--primary);margin-right:7px;"></i> Búsqueda rápida disponible en Lista Completa
            </div>
        </div>

        <!-- Action Hub / Toolbar Superior Premium -->
        <div class="its-action-hub" id="items-action-hub">
            <!-- Navegación -->
            <div class="its-action-group">
                <button class="its-btn-action" onclick="navegarPrimero()" title="Primer Registro">
                    <i class="fa-solid fa-angles-left"></i>
                </button>
                <button class="its-btn-action" onclick="navegarAnterior()" title="Anterior">
                    <i class="fa-solid fa-angle-left"></i>
                </button>
                <button class="its-btn-action" onclick="navegarSiguiente()" title="Siguiente">
                    <i class="fa-solid fa-angle-right"></i>
                </button>
                <button class="its-btn-action" onclick="navegarUltimo()" title="Último Registro">
                    <i class="fa-solid fa-angles-right"></i>
                </button>
            </div>
            
            <!-- CRUD -->
            <div class="its-action-group">
                <button class="its-btn-action primary-gradient" onclick="nuevoItem()" title="Nuevo Producto">
                    <i class="fa-solid fa-plus-circle"></i> Nuevo
                </button>
                <button class="its-btn-action blue-gradient" onclick="modificarItem()" title="Editar Campos">
                    <i class="fa-solid fa-edit"></i> Editar
                </button>
                <button class="its-btn-action" onclick="guardarItem()" title="Guardar Cambios">
                    <i class="fa-solid fa-save" style="color:#10b981;"></i> Guardar
                </button>
                <button class="its-btn-action danger-gradient" onclick="cancelarEdicion()" title="Cancelar / Restablecer">
                    <i class="fa-solid fa-rotate-left"></i> Cancelar
                </button>
            </div>
        </div>

        <!-- ===== CONTENIDO TAB: CAMPOS ===== -->
        <div class="its-tab-content active" id="content-campos" style="padding:24px;">
            <form id="form-item" action="index.php?route=inv_items_sistema&action=guardar" method="POST">
                <input type="hidden" name="id"  id="form-id"  value="0">
                <input type="hidden" name="copiar_desde_id" id="form-copiar-desde-id" value="0">
                
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <!-- Columna Interna Izquierda -->
                    <div>
                        <!-- SECCIÓN 1: IDENTIFICACIÓN -->
                        <div class="form-section-card">
                            <div class="form-section-header">
                                <i class="fa-solid fa-tag" style="color:var(--primary);"></i> Identificación del Ítem
                            </div>
                            <div class="its-fields-grid-new">
                                <div class="its-field-row-new">
                                    <label>Código Maestro</label>
                                    <input type="text" name="codigo" id="form-codigo" class="its-field-input-new mono" placeholder="Auto-generado secuencial" readonly>
                                </div>
                                <div class="its-field-row-new">
                                    <label>Nombre del Ítem</label>
                                    <input type="text" name="nombre" id="form-nombre" class="its-field-input-new" required placeholder="Ej: Aceite Hidráulico SAE 40" oninput="actualizarLivePreview()">
                                </div>
                                <div class="its-field-row-new" id="row-plantilla-select">
                                    <label style="color: var(--primary); font-weight: 700;">Copiar desde plantilla (Ítem existente)</label>
                                    <select id="form-copiar-plantilla" class="its-field-input-new">
                                        <option value="">— Seleccionar ítem para copiar campos —</option>
                                    </select>
                                </div>
                                <div class="its-field-row-new">
                                    <label>Grupo / Categoría Contable</label>
                                    <select name="grupo_id" id="form-grupo" class="its-field-input-new" required onchange="detectarTipoBienDesdeGrupo(); filtrarPlantillasPorGrupo(); actualizarLivePreview()">
                                        <option value="">Seleccionar grupo contable...</option>
                                        <?php foreach ($grupos as $g): ?>
                                            <option value="<?= $g['id'] ?>" data-codigo="<?= htmlspecialchars($g['codigo'] ?? '') ?>"><?= htmlspecialchars($g['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="its-field-row-new">
                                    <label>Tipo de bien</label>
                                    <input type="hidden" name="tipo_bien" id="form-tipo-bien" value="CC">
                                    <input type="text" id="form-tipo-bien-label" class="its-field-input-new" value="Bien de consumo corriente" readonly style="margin-bottom:16px;">
                                    <div id="row-responsable-activo" style="display:none;margin-bottom:16px;">
                                        <label style="display:block;margin-bottom:6px;">Responsable del activo fijo</label>
                                        <select name="responsable_id" id="form-responsable" class="its-field-input-new" disabled>
                                            <option value="">Seleccionar funcionario...</option>
                                            <?php foreach ($personal as $p): ?>
                                                <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?><?= !empty($p['area_actual']) ? ' ('.htmlspecialchars($p['area_actual']).')' : '' ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small style="display:block;margin-top:6px;color:var(--text-muted);">Disponible solamente para bienes de activo fijo.</small>
                                    </div>
                                </div>
                                <div class="its-field-row-new">
                                    <label>Detalle Adicional / Especificaciones</label>
                                    <input type="text" name="descripcion" id="form-descripcion" class="its-field-input-new" placeholder="Ej: Marca Chevron / Grado Industrial" oninput="actualizarLivePreview()">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Columna Interna Derecha -->
                    <div>
                        <!-- SECCIÓN 2: CONTROL CONTABLE & COSTOS -->
                        <div class="form-section-card">
                            <div class="form-section-header">
                                <i class="fa-solid fa-calculator" style="color:#10b981;"></i> Control de Costos y Saldos
                            </div>
                            <div class="its-fields-grid-new">
                                <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:12px;">
                                    <div class="its-field-row-new">
                                        <label>Unidad de Medida</label>
                                        <select name="unidad_id" id="form-unidad" class="its-field-input-new" required onchange="actualizarLivePreview()">
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($unidades as $u): ?>
                                                <option value="<?= $u['id'] ?>">
                                                    <?= htmlspecialchars($u['nombre']) ?> <?= !empty($u['extra']) ? ' ('.$u['extra'].')' : '' ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="its-field-row-new">
                                        <label>¿Aplica IVA?</label>
                                        <select name="aplica_iva" id="form-iva" class="its-field-input-new" required onchange="calcularTotal()">
                                            <option value="1">Sí aplica — período vigente <?= number_format((float)$tasaIvaVigente, 2) ?>%</option>
                                            <option value="0">No aplica IVA</option>
                                        </select>
                                        <small style="color:var(--text-muted);">La tasa se obtiene automáticamente del período <?= htmlspecialchars($periodoActivo['nombre'] ?? 'sin período activo') ?>.</small>
                                    </div>
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                    <div class="its-field-row-new">
                                        <label>Existencia Actual</label>
                                        <input type="number" step="0.0001" name="existencia_actual" id="form-existencia" class="its-field-input-new" placeholder="0.0000" value="0" oninput="calcularTotal()">
                                    </div>
                                    <div class="its-field-row-new">
                                        <label>Precio Promedio Unitario</label>
                                        <input type="number" step="0.0001" name="precio_promedio" id="form-precio" class="its-field-input-new" placeholder="0.0000" value="0" oninput="calcularTotal()">
                                    </div>
                                </div>
                                <div class="its-field-row-new">
                                    <label>Total Contable Calculado</label>
                                    <input type="text" id="form-total" class="its-field-input-new mono" readonly placeholder="0.0000" style="background:rgba(16,185,129,0.03);color:#10b981;font-weight:700;font-size:15px;border-color:rgba(16,185,129,0.2);">
                                </div>
                            </div>
                        </div>
                        
                        <!-- SECCIÓN 3: CONTROL DE ALMACÉN -->
                        <div class="form-section-card">
                            <div class="form-section-header">
                                <i class="fa-solid fa-warehouse" style="color:#f59e0b;"></i> Configuración de Almacén
                            </div>
                            <div class="its-fields-grid-new">
                                <div class="its-field-row-new">
                                    <label>Ubicación Física en Almacén</label>
                                    <select name="ubicacion" id="form-ubicacion" class="its-field-input-new" required>
                                        <option value="">Seleccionar ubicación...</option>
                                        <option value="bodega principal">bodega principal</option>
                                        <option value="bodega patio 300">bodega patio 300</option>
                                        <option value="bodega3">bodega3</option>
                                        <option value="bodega4">bodega4</option>
                                    </select>
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                    <div class="its-field-row-new">
                                        <label>Existencia Mínima</label>
                                        <input type="number" step="0.01" name="existencia_min" id="form-exmin" class="its-field-input-new" placeholder="0.00" value="0" oninput="actualizarLivePreview()">
                                    </div>
                                    <div class="its-field-row-new">
                                        <label>Existencia Máxima</label>
                                        <input type="number" step="0.01" name="existencia_max" id="form-exmax" class="its-field-input-new" placeholder="0.00" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- ===== CONTENIDO TAB: LISTA ===== -->
        <div class="its-tab-content" id="content-lista">
            <div class="table-responsive">
                <table id="maestro-items-table" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:90px;">Código</th>
                            <th>Descripción del Producto</th>
                            <th>Categoría Contable</th>
                            <th style="width:90px;">Unidad</th>
                            <th style="width:80px;text-align:center;">Existencia</th>
                            <th style="width:100px;">Precio Unit.</th>
                            <th style="width:100px;">Valor Total</th>
                            <th style="width:65px;text-align:center;">IVA</th>
                            <th class="columna-acciones">Cargar</th>
                        </tr>
                    </thead>
                    <tbody id="lista-tbody">
                        <!-- Las filas se renderizan dinámicamente con paginación JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
/* ===== Datos del catálogo actual para navegación ===== */
var _items = <?= json_encode(array_values($items)) ?>;
var _plantillas = <?= json_encode(array_values($plantillas ?? $items)) ?>;
var _currentIdx = -1; // -1 = ninguno seleccionado
var _tasaIvaVigente = <?= json_encode((float)$tasaIvaVigente) ?>;

/* ===== Tabs ===== */
function cambiarTab(tab) {
    document.querySelectorAll('.its-tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.its-tab-content').forEach(function(t) { t.classList.remove('active'); });
    document.getElementById('tab-'     + tab).classList.add('active');
    document.getElementById('content-' + tab).classList.add('active');
    var actionHub = document.getElementById('items-action-hub');
    if (actionHub) actionHub.style.display = tab === 'campos' ? '' : 'none';
    
    // Quitar la vista previa al mostrar la lista para aprovechar el ancho completo
    var inv_layout = document.querySelector('.its-inv_layout');
    if (inv_layout) {
        if (tab === 'lista') {
            inv_layout.classList.add('list-active');
        } else {
            inv_layout.classList.remove('list-active');
        }
    }
    if (tab === 'lista' && _listaTable) {
        setTimeout(function() { _listaTable.columns.adjust(); }, 0);
    }
}

/* ===== Cálculo automático de total y Live Preview ===== */
function calcularTotal() {
    var precio = parseFloat(document.getElementById('form-precio').value) || 0;
    var exist  = parseFloat(document.getElementById('form-existencia').value) || 0;
    var subtotal = precio * exist;
    var aplicaIva = parseInt(document.getElementById('form-iva').value, 10) === 1;
    var total  = (subtotal + (aplicaIva ? subtotal * (_tasaIvaVigente / 100) : 0)).toFixed(4);
    
    document.getElementById('form-total').value = total;
    actualizarLivePreview();
}

function detectarTipoBienDesdeGrupo() {
    var grupo = document.getElementById('form-grupo');
    var tipo = document.getElementById('form-tipo-bien');
    var etiqueta = document.getElementById('form-tipo-bien-label');
    if (!grupo || !tipo || !etiqueta) return;

    var opcion = grupo.options[grupo.selectedIndex];
    var codigo = opcion ? (opcion.getAttribute('data-codigo') || '').trim() : '';
    var nombreCategoria = opcion && opcion.value ? opcion.textContent.trim() : '';
    var esActivoFijo = codigo.indexOf('1.4.') === 0;
    tipo.value = esActivoFijo ? 'AF' : 'CC';
    etiqueta.value = nombreCategoria
        ? (esActivoFijo ? 'Activo fijo — ' : 'Consumo corriente — ') + nombreCategoria
        : (esActivoFijo ? 'Bien de activo fijo' : 'Bien de consumo corriente');
    actualizarResponsablePorTipo();
}

function actualizarResponsablePorTipo() {
    var tipo = document.getElementById('form-tipo-bien');
    var fila = document.getElementById('row-responsable-activo');
    var responsable = document.getElementById('form-responsable');
    if (!tipo || !fila || !responsable) return;

    var esActivoFijo = tipo.value === 'AF';
    fila.style.display = esActivoFijo ? 'block' : 'none';
    responsable.disabled = !esActivoFijo;
    responsable.required = false;
    if (!esActivoFijo) responsable.value = '';
}

actualizarResponsablePorTipo();

function actualizarLivePreview() {
    if (!document.getElementById('si-nombre')) return;
    // 1. Nombre y Código
    var nombre = document.getElementById('form-nombre').value.trim();
    var codigo = document.getElementById('form-codigo').value.trim();
    var desc = document.getElementById('form-descripcion').value.trim();
    
    document.getElementById('si-nombre').textContent = nombre !== '' ? nombre : 'Nuevo Ítem del Catálogo';
    document.getElementById('si-desc').textContent = desc !== '' ? desc : 'Especificación detallada del producto...';
    document.getElementById('pv-codigo').textContent = codigo !== '' ? 'CÓD: ' + codigo : 'CÓD: ------';
    
    // 2. Grupo
    var selectGrupo = document.getElementById('form-grupo');
    var grupoTxt = selectGrupo.selectedIndex > 0 ? selectGrupo.options[selectGrupo.selectedIndex].text : 'Sin Asignar';
    document.getElementById('si-grupo').textContent = grupoTxt;
    
    // 3. Unidad
    var selectUnidad = document.getElementById('form-unidad');
    var unidadTxt = selectUnidad.selectedIndex > 0 ? selectUnidad.options[selectUnidad.selectedIndex].text.split('(')[0].trim() : 'u.';
    document.getElementById('si-unidad').textContent = unidadTxt;
    
    // 4. Precios e InvInventario
    var existencia = parseFloat(document.getElementById('form-existencia').value) || 0;
    var precio = parseFloat(document.getElementById('form-precio').value) || 0;
    var exMin = parseFloat(document.getElementById('form-exmin').value) || 0;
    
    document.getElementById('pv-stock').textContent = existencia.toLocaleString('es-EC', {minimumFractionDigits: 2, maximumFractionDigits: 4});
    document.getElementById('pv-precio').textContent = '$' + precio.toLocaleString('es-EC', {minimumFractionDigits: 2, maximumFractionDigits: 4});
    
    // 5. Barra e Indicador de Estado del Stock
    var bar = document.getElementById('pv-status-bar');
    var pctText = document.getElementById('pv-status-pct');
    var textStatus = document.getElementById('pv-status-text');
    
    if (existencia === 0) {
        bar.style.width = '100%';
        bar.style.backgroundColor = '#ef4444'; // Rojo (Sin Stock)
        pctText.textContent = '0%';
        textStatus.textContent = 'Sin Existencia';
        textStatus.style.color = '#ef4444';
    } else if (exMin > 0 && existencia <= exMin) {
        bar.style.width = '40%';
        bar.style.backgroundColor = '#f59e0b'; // Amarillo (Stock Bajo)
        pctText.textContent = Math.round((existencia / exMin) * 100) + '%';
        textStatus.textContent = 'Stock Crítico / Bajo';
        textStatus.style.color = '#f59e0b';
    } else {
        bar.style.width = '100%';
        bar.style.backgroundColor = '#10b981'; // Verde (Stock Saludable)
        pctText.textContent = '100%';
        textStatus.textContent = 'Stock Saludable / Óptimo';
        textStatus.style.color = '#10b981';
    }
}

/* ===== Filtrar por grupo (GET) ===== */
function filtrarPorGrupo() {
    var gid = document.getElementById('filtro-grupo').value;
    var url = 'index.php?route=inv_items_sistema&grupo_id=' + encodeURIComponent(gid);
    window.location.href = url;
}

/* ===== Nuevo ítem (limpiar formulario) ===== */
function nuevoItem() {
    _currentIdx = -1;
    limpiarFormulario();
    cambiarTab('campos');
    document.getElementById('form-nombre').focus();
    actualizarLivePreview();
}

function updateSelectValue(selectId, value) {
    var selectEl = document.getElementById(selectId);
    if (selectEl) {
        selectEl.value = value;
        // Trigger standard change event
        var changeEv = new Event('change', { bubbles: true });
        selectEl.dispatchEvent(changeEv);
        // Trigger custom sync event for our searchable widget
        var syncEv = new Event('sync-custom');
        selectEl.dispatchEvent(syncEv);
    }
}

function limpiarFormulario() {
    document.getElementById('form-id').value           = '0';
    document.getElementById('form-copiar-desde-id').value = '0';
    document.getElementById('form-codigo').value       = '';
    updateSelectValue('form-grupo', '');
    updateSelectValue('form-tipo-bien', 'CC');
    updateSelectValue('form-responsable', '');
    actualizarResponsablePorTipo();
    document.getElementById('form-nombre').value       = '';
    document.getElementById('form-descripcion').value  = '';
    updateSelectValue('form-unidad', '');
    document.getElementById('form-existencia').value   = '0';
    document.getElementById('form-precio').value       = '0';
    document.getElementById('form-total').value        = '0.0000';
    document.getElementById('form-iva').value          = '1';
    
    // Reset Ubicación dropdown and remove legacy options
    var selectUbicacion = document.getElementById('form-ubicacion');
    if (selectUbicacion) {
        var baseOpts = ['bodega principal', 'bodega patio 300', 'bodega3', 'bodega4', 'bodega1', 'bodega2'];
        for (var i = selectUbicacion.options.length - 1; i >= 0; i--) {
            var optVal = selectUbicacion.options[i].value;
            if (optVal !== '' && baseOpts.indexOf(optVal) === -1) {
                selectUbicacion.remove(i);
            }
        }
        selectUbicacion.value = '';
    }
    
    document.getElementById('form-exmin').value        = '0';
    document.getElementById('form-exmax').value        = '0';
    var statusCard = document.getElementById('item-status-card');
    if (statusCard) statusCard.style.display = 'none';
    
    // Show template copy row and reset it
    var pRow = document.getElementById('row-plantilla-select');
    if (pRow) pRow.style.display = 'flex';
    updateSelectValue('form-copiar-plantilla', '');
    
    document.querySelectorAll('#content-lista tbody tr').forEach(function(r){ r.style.background = ''; });
    actualizarLivePreview();
}

function cancelarEdicion() { limpiarFormulario(); }
function modificarItem()   { document.getElementById('form-nombre').focus(); }
function guardarItem()     { document.getElementById('form-item').requestSubmit(); }

function mostrarToastAjax(mensaje, tipo) {
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + (tipo === 'error' ? 'inv_error' : tipo) + ' show';
    toast.innerHTML = '<i class="fa-solid ' + (tipo === 'success' ? 'fa-check-circle' : 'fa-circle-exclamation') + '"></i><span></span>';
    toast.querySelector('span').textContent = mensaje;
    document.body.appendChild(toast);
    setTimeout(function() {
        toast.classList.remove('show');
        setTimeout(function() { toast.remove(); }, 400);
    }, 3500);
}

function itemCoincideConVista(item) {
    var grupoActivo = parseInt(document.getElementById('filtro-grupo').value || '0', 10);
    if (grupoActivo > 0 && parseInt(item.grupo_id || '0', 10) !== grupoActivo) return false;
    return true;
}

function enfocarFilaItem(itemId, pagina) {
    cambiarTab('lista');
    renderListaTable(pagina);
    setTimeout(function() {
        var fila = document.getElementById('row-' + itemId);
        if (!fila) return;
        fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
        fila.classList.remove('saved-row-highlight');
        void fila.offsetWidth;
        fila.classList.add('saved-row-highlight');
    }, 80);
}

function guardarItemSinRecargar(event) {
    event.preventDefault();
    var form = event.currentTarget;
    if (!form.reportValidity()) return;

    var boton = document.querySelector('.its-btn-action[onclick="guardarItem()"]');
    if (boton) boton.disabled = true;
    var copiarDesde = parseInt(document.getElementById('form-copiar-desde-id').value || '0', 10);
    if (copiarDesde > 0) document.getElementById('form-id').value = '0';
    var datos = new FormData(form);
    datos.append('is_ajax', '1');

    fetch(form.action, {
        method: 'POST',
        body: datos,
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function(respuesta) {
        return respuesta.json().then(function(data) {
            if (!respuesta.ok || !data.success) throw new Error(data.mensaje || 'No fue posible guardar el ítem.');
            return data;
        });
    }).then(function(data) {
        var item = data.item;
        var indice = _items.findIndex(function(actual) { return String(actual.id) === String(item.id); });
        var coincide = itemCoincideConVista(item);

        if (indice >= 0 && !coincide) {
            _items.splice(indice, 1);
            renderListaTable(Math.min(_listaPage, Math.max(1, Math.ceil(_items.length / _listaLimit))));
            mostrarToastAjax(data.mensaje + '. El registro ya no coincide con el filtro actual.', 'success');
            limpiarFormulario();
            cambiarTab('lista');
            return;
        }

        if (indice >= 0) {
            _items[indice] = item;
        } else if (coincide) {
            _items.push(item);
            indice = _items.length - 1;
        }

        var indicePlantilla = _plantillas.findIndex(function(actual) { return String(actual.id) === String(item.id); });
        if (indicePlantilla >= 0) _plantillas[indicePlantilla] = item;
        else _plantillas.push(item);

        cargarItemEnFormulario(item);
        var pagina = indice >= 0 ? Math.floor(indice / _listaLimit) + 1 : _listaPage;
        enfocarFilaItem(item.id, pagina);
        mostrarToastAjax(data.mensaje + ' sin recargar la lista.', 'success');
    }).catch(function(error) {
        mostrarToastAjax(error.message || 'Error al guardar el ítem.', 'error');
    }).finally(function() {
        if (boton) boton.disabled = false;
    });
}

/* ===== Cargar ítem en el formulario de Campos ===== */
function cargarItemEnFormulario(item) {
    document.getElementById('form-id').value           = item.id;
    document.getElementById('form-copiar-desde-id').value = '0';
    document.getElementById('form-codigo').value       = item.codigo || '';
    updateSelectValue('form-grupo', item.grupo_id);
    detectarTipoBienDesdeGrupo();
    updateSelectValue('form-responsable', document.getElementById('form-tipo-bien').value === 'AF' ? (item.responsable_id || '') : '');
    document.getElementById('form-nombre').value       = item.nombre;
    document.getElementById('form-descripcion').value  = item.descripcion || '';
    updateSelectValue('form-unidad', item.unidad_id);
    document.getElementById('form-existencia').value   = parseFloat(item.existencia_actual || 0).toFixed(4);
    document.getElementById('form-precio').value       = parseFloat(item.precio_promedio || 0).toFixed(4);
    document.getElementById('form-iva').value          = item.aplica_iva;
    
    // Ubicacion: check if option already exists or map legacy names
    var valUbicacion = item.ubicacion || '';
    if (valUbicacion === 'bodega1') valUbicacion = 'bodega principal';
    if (valUbicacion === 'bodega2') valUbicacion = 'bodega patio 300';
    
    var selectUbicacion = document.getElementById('form-ubicacion');
    if (selectUbicacion) {
        var optionExists = false;
        for (var i = 0; i < selectUbicacion.options.length; i++) {
            if (selectUbicacion.options[i].value === valUbicacion) {
                optionExists = true;
                break;
            }
        }
        if (!optionExists && valUbicacion !== '') {
            var opt = document.createElement('option');
            opt.value = valUbicacion;
            opt.textContent = valUbicacion;
            selectUbicacion.appendChild(opt);
        }
        selectUbicacion.value = valUbicacion;
    }
    
    document.getElementById('form-exmin').value        = parseFloat(item.existencia_min || 0).toFixed(2);
    document.getElementById('form-exmax').value        = parseFloat(item.existencia_max || 0).toFixed(2);
    calcularTotal();

    // Activar tarjeta de estado de registro
    var statusCard = document.getElementById('item-status-card');
    if (statusCard) statusCard.style.display = 'block';

    // Hide template copy row
    var pRow = document.getElementById('row-plantilla-select');
    if (pRow) pRow.style.display = 'none';

    // Resaltar fila activa en la lista
    document.querySelectorAll('#lista-tbody tr').forEach(function(r){ r.style.background = ''; });
    var rowEl = document.getElementById('row-' + item.id);
    if (rowEl) rowEl.style.background = 'rgba(37,99,235,0.06)';

    // Buscar índice en el array local
    for (var i = 0; i < _items.length; i++) {
        if (_items[i].id == item.id) { _currentIdx = i; break; }
    }

    cambiarTab('campos');
}

/* ===== Navegación P/A/S/U ===== */
function navegarPrimero() {
    if (_items.length > 0) { _currentIdx = 0; cargarItemEnFormulario(_items[0]); }
}
function navegarUltimo() {
    if (_items.length > 0) { _currentIdx = _items.length - 1; cargarItemEnFormulario(_items[_currentIdx]); }
}
function navegarSiguiente() {
    if (_items.length === 0) return;
    _currentIdx = Math.min(_currentIdx + 1, _items.length - 1);
    cargarItemEnFormulario(_items[_currentIdx]);
}
function navegarAnterior() {
    if (_items.length === 0) return;
    _currentIdx = Math.max(_currentIdx - 1, 0);
    cargarItemEnFormulario(_items[_currentIdx]);
}

/* ===== Paginación Dinámica del Lado del Cliente ===== */
var _listaPage = 1;
var _listaLimit = 50;
var _listaTable = null;

function escaparMaestro(valor) {
    var nodo = document.createElement('div');
    nodo.textContent = valor == null ? '' : String(valor);
    return nodo.innerHTML;
}

function iniciarMaestroItemsDataTable() {
    if (_listaTable || !window.jQuery || !$.fn.DataTable) return _listaTable;
    _listaTable = $('#maestro-items-table').DataTable({
        data: _items,
        deferRender: true,
        pageLength: _listaLimit,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, 'asc']],
        columns: [
            { data: 'codigo', render: function(v, type) { return type === 'display' ? '<code style="font-family:monospace;font-weight:700;font-size:12px;color:var(--primary);">' + escaparMaestro(v || '—') + '</code>' : (v || ''); } },
            { data: null, render: function(item, type) { var texto = [item.nombre || '', item.descripcion || ''].join(' '); return type === 'display' ? '<strong style="display:block;font-size:13px;color:var(--text-color);">' + escaparMaestro(item.nombre || '') + '</strong>' + (item.descripcion ? '<span style="font-size:11px;color:var(--text-muted);display:block;margin-top:2px;">' + escaparMaestro(item.descripcion) + '</span>' : '') : texto; } },
            { data: 'grupo_nombre', render: function(v, type) { return type === 'display' ? '<span class="status-badge transit" style="font-size:11px;background:#eef2ff;color:#4f46e5;font-weight:600;">' + escaparMaestro(v || '') + '</span>' : (v || ''); } },
            { data: null, render: function(item) { return escaparMaestro(item.unidad_abrev || item.unidad_nombre || 'u.'); } },
            { data: 'existencia_actual', className: 'dt-body-center', render: function(v, type, item) { var n = Number(v || 0); if (type !== 'display') return n; var bajo = Number(item.existencia_min || 0) > 0 && n <= Number(item.existencia_min); return '<span style="font-weight:700;color:' + (bajo ? '#f59e0b' : 'var(--text-color)') + ';">' + n.toFixed(2) + '</span>'; } },
            { data: 'precio_promedio', render: function(v, type) { var n=Number(v || 0); return type === 'display' ? '$' + n.toFixed(2) : n; } },
            { data: null, render: function(item, type) { var n=Number(item.precio_promedio || 0)*Number(item.existencia_actual || 0); return type === 'display' ? '<strong style="color:#10b981;">$' + n.toFixed(2) + '</strong>' : n; } },
            { data: 'aplica_iva', className: 'dt-body-center', render: function(v, type) { var aplica=Number(v)===1; return type === 'display' ? '<span class="status-badge ' + (aplica ? 'active' : 'inactive') + '" style="font-size:10px;">' + (aplica ? 'Sí (' + _tasaIvaVigente.toFixed(2) + '%)' : 'No aplica') + '</span>' : (aplica ? 'Sí' : 'No'); } },
            { data: null, orderable: false, searchable: false, className: 'acciones-cell columna-acciones', render: function() { return '<button type="button" class="btn-accion btn-ver maestro-cargar-item" title="Cargar en Ficha"><i class="fa-solid fa-pen-to-square"></i></button>'; } }
        ],
        createdRow: function(row, item) { row.id = 'row-' + item.id; row.style.cursor = 'pointer'; },
        language: {
            search: 'Buscar en Maestro de Ítems:',
            searchPlaceholder: 'Código, nombre, descripción, categoría…',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Sin registros',
            zeroRecords: 'No se encontraron ítems coincidentes',
            paginate: { previous: 'Anterior', next: 'Siguiente' }
        }
    });
    $('#maestro-items-table tbody').on('click', 'tr', function() {
        var item = _listaTable.row(this).data();
        if (item) cargarItemEnFormulario(item);
    });
    return _listaTable;
}

function renderListaTable(page) {
    if (!page) page = 1;
    _listaPage = page;

    if (window.jQuery && $.fn.DataTable) {
        var tabla = iniciarMaestroItemsDataTable();
        if (tabla) {
            tabla.clear().rows.add(_items).draw(false);
            var paginas = tabla.page.info().pages;
            tabla.page(Math.max(0, Math.min(page - 1, Math.max(0, paginas - 1)))).draw('page');
            return;
        }
    }

    var tbody = document.getElementById('lista-tbody');
    if (!tbody) return;

    if (_items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:48px;color:var(--text-muted);"><i class="fa-solid fa-inbox" style="font-size:36px;display:block;margin-bottom:12px;opacity:0.3;"></i><strong>Sin registros encontrados</strong></td></tr>';
        renderListaPaginacion(0, _listaLimit, 1);
        return;
    }

    var start = (page - 1) * _listaLimit;
    var end = Math.min(start + _listaLimit, _items.length);
    var html = '';

    for (var idx = start; idx < end; idx++) {
        var item = _items[idx];
        
        // Calcular total contable
        var precio = parseFloat(item.precio_promedio) || 0;
        var exist = parseFloat(item.existencia_actual) || 0;
        var total = precio * exist;

        var exMin = parseFloat(item.existencia_min) || 0;
        var exStatus = (exist <= exMin && exMin > 0) ? 'warn' : 'ok';
        var exColor = exStatus === 'warn' ? '#f59e0b' : 'var(--text-color)';

        var isApplied = parseInt(item.aplica_iva, 10) === 1;
        var ivaNombre = isApplied ? ('Sí (' + _tasaIvaVigente.toFixed(2) + '%)') : 'No aplica';
        var ivaClass = isApplied ? 'active' : 'inactive';

        var itemJson = JSON.stringify(item).replace(/"/g, '&quot;');

        html += '<tr id="row-' + item.id + '" style="cursor:pointer;" onclick="cargarItemEnFormulario(' + itemJson + ')">' +
            '<td><code style="font-family:monospace;font-weight:700;font-size:12px;color:var(--primary);">' + (item.codigo || '—') + '</code></td>' +
            '<td><strong style="display:block;font-size:13px;color:var(--text-color);">' + (item.nombre || '') + '</strong>' +
            (item.descripcion ? '<span style="font-size:11px;color:var(--text-muted);display:block;margin-top:2px;">' + item.descripcion + '</span>' : '') + '</td>' +
            '<td><span class="status-badge transit" style="font-size:11px;background:#eef2ff;color:#4f46e5;font-weight:600;">' + (item.grupo_nombre || '') + '</span></td>' +
            '<td style="font-size:12px;color:var(--text-muted);">' + (item.unidad_abrev || item.unidad_nombre || 'u.') + '</td>' +
            '<td style="text-align:center;"><span style="font-weight:700;font-size:13px;color:' + exColor + ';">' + exist.toFixed(2) + '</span></td>' +
            '<td style="font-size:13px;">$' + precio.toFixed(2) + '</td>' +
            '<td><strong style="color:#10b981;font-size:13px;">$' + total.toFixed(2) + '</strong></td>' +
            '<td style="text-align:center;"><span class="status-badge ' + ivaClass + '" style="font-size:10px;">' + ivaNombre + '</span></td>' +
            '<td class="acciones-cell columna-acciones" onclick="event.stopPropagation()">' +
                '<button type="button" class="btn-accion btn-ver" onclick="cargarItemEnFormulario(' + itemJson + ')" title="Cargar en Ficha"><i class="fa-solid fa-pen-to-square"></i></button>' +
            '</td>' +
        '</tr>';
    }

    tbody.innerHTML = html;
    renderListaPaginacion(_items.length, _listaLimit, page);
}

function renderListaPaginacion(total, limit, activePage) {
    var rangoSpan = document.getElementById('lista-pag-rango');
    var totalSpan = document.getElementById('lista-pag-total');
    var botonesDiv = document.getElementById('lista-pag-botones');

    if (!rangoSpan || !totalSpan || !botonesDiv) return;

    totalSpan.textContent = total;

    if (total === 0) {
        rangoSpan.textContent = '0 - 0';
        botonesDiv.innerHTML = '';
        return;
    }

    var totalPages = Math.ceil(total / limit);
    var start = (activePage - 1) * limit + 1;
    var end = Math.min(activePage * limit, total);
    rangoSpan.textContent = start + ' - ' + end;

    var html = '';
    
    // Botón Anterior
    var prevDisabled = activePage === 1 ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : '';
    html += '<button type="button" class="btn-outline" style="padding:6px 12px;font-size:12px;border-radius:6px;" onclick="renderListaTable(' + (activePage - 1) + ')" ' + prevDisabled + '><i class="fa-solid fa-angle-left"></i> Anterior</button>';

    // Rango de páginas (máximo 5 botones)
    var startPage = Math.max(1, activePage - 2);
    var endPage = Math.min(totalPages, activePage + 2);

    if (startPage > 1) {
        html += '<button type="button" class="btn-outline" style="padding:6px 10px;font-size:12px;border-radius:6px;" onclick="renderListaTable(1)">1</button>';
        if (startPage > 2) html += '<span style="color:var(--text-muted);padding:0 4px;font-size:12px;">...</span>';
    }

    for (var p = startPage; p <= endPage; p++) {
        var activeStyle = p === activePage ? 'background:var(--primary);color:white;border-color:var(--primary);font-weight:700;' : '';
        html += '<button type="button" class="btn-outline" style="padding:6px 10px;font-size:12px;border-radius:6px;' + activeStyle + '" onclick="renderListaTable(' + p + ')">' + p + '</button>';
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += '<span style="color:var(--text-muted);padding:0 4px;font-size:12px;">...</span>';
        html += '<button type="button" class="btn-outline" style="padding:6px 10px;font-size:12px;border-radius:6px;" onclick="renderListaTable(' + totalPages + ')">' + totalPages + '</button>';
    }

    // Botón Siguiente
    var nextDisabled = activePage === totalPages ? 'disabled style="opacity:0.4;cursor:not-allowed;"' : '';
    html += '<button type="button" class="btn-outline" style="padding:6px 12px;font-size:12px;border-radius:6px;" onclick="renderListaTable(' + (activePage + 1) + ')" ' + nextDisabled + '>Siguiente <i class="fa-solid fa-angle-right"></i></button>';

    botonesDiv.innerHTML = html;
}

// Escuchar cambios para actualizar Vista Previa de forma reactiva
document.getElementById('form-existencia').addEventListener('input', calcularTotal);
document.getElementById('form-precio').addEventListener('input', calcularTotal);

/* ===== Buscador Inclusivo / Fuzzy Search y Custom Selects ===== */
function normalizarTextoJS(str) {
    if (!str) return '';
    return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
}

function initSearchableSelect(selectId, placeholder) {
    var selectEl = document.getElementById(selectId);
    if (!selectEl) return;

    selectEl.style.display = 'none';

    var container = document.createElement('div');
    container.className = 'custom-select-container';
    container.id = 'custom-select-' + selectId;

    var trigger = document.createElement('div');
    trigger.className = 'custom-select-trigger';
    
    var triggerText = document.createElement('span');
    triggerText.textContent = placeholder || 'Seleccionar...';
    trigger.appendChild(triggerText);

    var triggerIcon = document.createElement('i');
    triggerIcon.className = 'fa-solid fa-chevron-down';
    trigger.appendChild(triggerIcon);

    container.appendChild(trigger);

    var dropdown = document.createElement('div');
    dropdown.className = 'custom-select-dropdown';

    var searchContainer = document.createElement('div');
    searchContainer.className = 'custom-select-search-container';
    
    var searchIcon = document.createElement('i');
    searchIcon.className = 'fa-solid fa-magnifying-glass';
    searchContainer.appendChild(searchIcon);

    var searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Buscar...';
    searchInput.className = 'custom-select-search-input';
    searchContainer.appendChild(searchInput);

    dropdown.appendChild(searchContainer);

    var optionsDiv = document.createElement('div');
    optionsDiv.className = 'custom-select-options';
    dropdown.appendChild(optionsDiv);

    var noResultsDiv = document.createElement('div');
    noResultsDiv.className = 'custom-select-no-results';
    noResultsDiv.textContent = 'Sin resultados';
    noResultsDiv.style.display = 'none';
    dropdown.appendChild(noResultsDiv);

    container.appendChild(dropdown);
    selectEl.parentNode.insertBefore(container, selectEl.nextSibling);

    function syncOptions() {
        optionsDiv.innerHTML = '';
        var selectedText = '';
        var hasSelected = false;

        Array.from(selectEl.options).forEach(function(opt) {
            if (opt.value === '' && selectEl.options.length > 1) {
                if (opt.selected) {
                    selectedText = opt.textContent;
                    hasSelected = true;
                }
                return;
            }

            var optionEl = document.createElement('div');
            optionEl.className = 'custom-select-option';
            optionEl.textContent = opt.textContent;
            optionEl.dataset.value = opt.value;

            if (opt.selected) {
                optionEl.classList.add('selected');
                selectedText = opt.textContent;
                hasSelected = true;
            }

            optionEl.addEventListener('click', function(e) {
                e.stopPropagation();
                selectEl.value = opt.value;
                
                var event = new Event('change', { bubbles: true });
                selectEl.dispatchEvent(event);

                container.querySelectorAll('.custom-select-option').forEach(function(el) {
                    el.classList.remove('selected');
                });
                optionEl.classList.add('selected');
                triggerText.textContent = opt.textContent;
                
                container.classList.remove('open');
                searchInput.value = '';
                filterOptions('');
            });

            optionsDiv.appendChild(optionEl);
        });

        if (!hasSelected || selectedText === '') {
            triggerText.textContent = placeholder || 'Seleccionar...';
        } else {
            triggerText.textContent = selectedText;
        }
    }

    trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        var isOpen = container.classList.contains('open');
        
        document.querySelectorAll('.custom-select-container').forEach(function(c) {
            c.classList.remove('open');
        });

        if (!isOpen) {
            container.classList.add('open');
            searchInput.focus();
            syncOptions();
        } else {
            container.classList.remove('open');
        }
    });

    document.addEventListener('click', function(e) {
        if (!container.contains(e.target)) {
            container.classList.remove('open');
        }
    });

    function filterOptions(query) {
        var cleanQuery = normalizarTextoJS(query);
        var visibleCount = 0;

        container.querySelectorAll('.custom-select-option').forEach(function(optionEl) {
            var text = normalizarTextoJS(optionEl.textContent);
            if (text.indexOf(cleanQuery) > -1) {
                optionEl.classList.remove('hidden');
                visibleCount++;
            } else {
                optionEl.classList.add('hidden');
            }
        });

        if (visibleCount === 0) {
            noResultsDiv.style.display = 'block';
        } else {
            noResultsDiv.style.display = 'none';
        }
    }

    searchInput.addEventListener('input', function(e) {
        filterOptions(e.target.value);
    });

    syncOptions();

    selectEl.addEventListener('sync-custom', function() {
        syncOptions();
    });
}

function populatePlantillaSelect() {
    var selectPlantilla = document.getElementById('form-copiar-plantilla');
    if (selectPlantilla && typeof _plantillas !== 'undefined') {
        selectPlantilla.innerHTML = '<option value="">— Seleccionar ítem para copiar campos —</option>';
        _plantillas.forEach(function(item) {
            var opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = (item.codigo ? '[' + item.codigo + '] ' : '') + item.nombre;
            selectPlantilla.appendChild(opt);
        });
        var syncEv = new Event('sync-custom');
        selectPlantilla.dispatchEvent(syncEv);
    }
}

function filtrarPlantillasPorGrupo() {
    var grupo = document.getElementById('form-grupo');
    var selectPlantilla = document.getElementById('form-copiar-plantilla');
    if (!grupo || !selectPlantilla) return;
    var grupoId = String(grupo.value || '');
    selectPlantilla.innerHTML = '<option value="">— Seleccionar ítem para copiar campos —</option>';
    // Priorizar el grupo elegido, sin ocultar las plantillas de otros grupos.
    // Esto permite copiar campos hacia un grupo de activo fijo distinto.
    var plantillasOrdenadas = _plantillas.slice().sort(function(a, b) {
        var aMismoGrupo = grupoId !== '' && String(a.grupo_id) === grupoId ? 0 : 1;
        var bMismoGrupo = grupoId !== '' && String(b.grupo_id) === grupoId ? 0 : 1;
        return aMismoGrupo - bMismoGrupo;
    });
    plantillasOrdenadas.forEach(function(item) {
        var opt = document.createElement('option');
        opt.value = item.id;
        var esOtroGrupo = grupoId !== '' && String(item.grupo_id) !== grupoId;
        opt.textContent = (item.codigo ? '[' + item.codigo + '] ' : '') + item.nombre + (esOtroGrupo ? ' - otro grupo' : '');
        selectPlantilla.appendChild(opt);
    });
    selectPlantilla.value = '';
    selectPlantilla.dispatchEvent(new Event('sync-custom'));
}

// Cargar plantilla cuando el usuario selecciona una
document.getElementById('form-copiar-plantilla').addEventListener('change', function(e) {
    var selectedId = e.target.value;
    if (!selectedId) return;
    
    var item = _plantillas.find(function(it) {
        return String(it.id) === String(selectedId);
    });
    
    if (item) {
        var grupoDestino = document.getElementById('form-grupo').value;
        _currentIdx = -1;
        document.getElementById('form-id').value = '0';
        document.getElementById('form-codigo').value = '';
        document.getElementById('form-copiar-desde-id').value = item.id;
        if (!grupoDestino) {
            updateSelectValue('form-grupo', item.grupo_id);
        } else {
            detectarTipoBienDesdeGrupo();
        }
        document.getElementById('form-nombre').value = item.nombre;
        document.getElementById('form-descripcion').value = item.descripcion || '';
        updateSelectValue('form-unidad', item.unidad_id);
        
        var selectIva = document.getElementById('form-iva');
        if (selectIva) {
            selectIva.value = item.aplica_iva;
            var changeEv = new Event('change', { bubbles: true });
            selectIva.dispatchEvent(changeEv);
        }
        
        var valUbicacion = item.ubicacion || '';
        var selectUbicacion = document.getElementById('form-ubicacion');
        if (selectUbicacion) {
            var optionExists = false;
            for (var i = 0; i < selectUbicacion.options.length; i++) {
                if (selectUbicacion.options[i].value === valUbicacion) {
                    optionExists = true;
                    break;
                }
            }
            if (!optionExists && valUbicacion !== '') {
                var opt = document.createElement('option');
                opt.value = valUbicacion;
                opt.textContent = valUbicacion;
                selectUbicacion.appendChild(opt);
            }
            selectUbicacion.value = valUbicacion;
            var changeEv = new Event('change', { bubbles: true });
            selectUbicacion.dispatchEvent(changeEv);
        }
        
        document.getElementById('form-exmin').value = parseFloat(item.existencia_min || 0).toFixed(2);
        document.getElementById('form-exmax').value = parseFloat(item.existencia_max || 0).toFixed(2);
        document.getElementById('form-precio').value = parseFloat(item.precio_promedio || 0).toFixed(4);
        document.getElementById('form-existencia').value = '0';
        
        calcularTotal();
        actualizarLivePreview();
        
        setTimeout(function() {
            updateSelectValue('form-copiar-plantilla', '');
        }, 100);
    }
});

// Cargar la primera página e inicializar selects al iniciar
document.addEventListener('DOMContentLoaded', function() {
    renderListaTable(1);
    document.getElementById('form-item').addEventListener('submit', guardarItemSinRecargar);
    
    // Iniciar items en el select de copiar desde plantilla
    populatePlantillaSelect();
    
    // Inicializar widgets de selects buscadores
    initSearchableSelect('form-copiar-plantilla', '— Seleccionar ítem para copiar campos —');
    initSearchableSelect('form-grupo', 'Seleccionar grupo contable...');
    initSearchableSelect('form-unidad', 'Seleccionar...');
    
    actualizarLivePreview();

    // Cargar automáticamente el ítem especificado en la URL si existe (edit_id o id)
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('nuevo') === '1') {
        nuevoItem();
        document.getElementById('form-nombre').value = urlParams.get('nombre') || '';
        document.getElementById('form-descripcion').value = urlParams.get('descripcion') || '';
        document.getElementById('form-precio').value = Number(urlParams.get('precio') || 0).toFixed(4);
        calcularTotal();
    }
    var editId = urlParams.get('edit_id') || urlParams.get('id');
    if (editId) {
        var foundItem = null;
        if (typeof _items !== 'undefined' && Array.isArray(_items)) {
            for (var i = 0; i < _items.length; i++) {
                if (_items[i].id == editId || _items[i].producto_id == editId) {
                    foundItem = _items[i];
                    break;
                }
            }
        }
        if (foundItem) {
            cargarItemEnFormulario(foundItem);
        } else {
            fetch('index.php?route=inv_items_sistema&action=ver&id=' + editId)
                .then(function(r) { return r.json(); })
                .then(function(item) {
                    if (item && item.id) {
                        cargarItemEnFormulario(item);
                    }
                })
                .catch(function(e) { console.log('Info:', e); });
        }
    }
});
</script>

<!-- Botón Flotante para Desplazarse Arriba -->
<button id="btn-back-to-top" style="position:fixed;bottom:30px;right:30px;width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg, var(--primary), #2563eb);color:white;border:none;box-shadow:0 6px 16px rgba(37,99,235,0.25);cursor:pointer;display:none;align-items:center;justify-content:center;font-size:20px;z-index:999;transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);opacity:0;transform:translateY(10px);"><i class="fa-solid fa-arrow-up"></i></button>

<script>
window.addEventListener('scroll', function() {
    var btn = document.getElementById('btn-back-to-top');
    if (!btn) return;
    if (window.scrollY > 300) {
        btn.style.display = 'flex';
        setTimeout(function() {
            btn.style.opacity = '1';
            btn.style.transform = 'translateY(0)';
        }, 10);
    } else {
        btn.style.opacity = '0';
        btn.style.transform = 'translateY(10px)';
        setTimeout(function() {
            if (window.scrollY <= 300) btn.style.display = 'none';
        }, 300);
    }
});
document.getElementById('btn-back-to-top').addEventListener('click', function() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});
</script>
