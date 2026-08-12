<?php
/**
 * Alta rápida reutilizable de unidades organizacionales y puestos.
 * Configuración esperada en $catalogoRapidoConfig:
 * - areas: catálogo de unidades
 * - unidadSelectId / puestoSelectId: selectores que recibirán el registro
 * - rmuTargetId: campo opcional para copiar la RMU del puesto
 */
$catalogoConfig = array_merge([
    'areas' => [],
    'unidadSelectId' => 'unidad_id',
    'puestoSelectId' => 'puesto_id',
    'rmuTargetId' => '',
], is_array($catalogoRapidoConfig ?? null) ? $catalogoRapidoConfig : []);
$direccionesCatalogo = array_values(array_filter(
    $catalogoConfig['areas'],
    static fn(array $area): bool => empty($area['unidad_padre_id']) && (int)($area['activo'] ?? 1) === 1
));
?>
<dialog id="catalogoRapido" class="catalog-quick-dialog" aria-labelledby="catalogoTitulo">
    <form id="catalogoRapidoForm" class="catalog-quick-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" id="catalogoTipo" value="">
        <header class="catalog-quick-header">
            <div>
                <span class="catalog-quick-kicker">Catálogo institucional</span>
                <h3 id="catalogoTitulo">Nuevo registro</h3>
            </div>
            <button type="button" onclick="cerrarCatalogoRapido()" class="catalog-quick-close" aria-label="Cerrar ventana"><i class="bi bi-x-lg"></i></button>
        </header>

        <div class="catalog-quick-body">
            <div class="field">
                <label for="catalogoNombre">Denominación <span class="required">*</span></label>
                <input id="catalogoNombre" name="nombre" maxlength="150" required autocomplete="off" placeholder="Escriba la denominación oficial">
                <small>Se normalizará en mayúsculas y se validarán duplicados.</small>
            </div>

            <div id="camposUnidad" class="catalog-quick-fields" hidden>
                <div class="field">
                    <label for="catalogoClaseUnidad">Tipo de unidad <span class="required">*</span></label>
                    <select id="catalogoClaseUnidad">
                        <option value="area">Área / Departamento</option>
                        <option value="direccion">Dirección principal</option>
                    </select>
                </div>
                <div class="field" id="catalogoPadreField">
                    <label for="catalogoPadre">Dirección padre <span class="required">*</span></label>
                    <select id="catalogoPadre" name="unidad_padre_id">
                        <option value="">Seleccione la dirección...</option>
                        <?php foreach ($direccionesCatalogo as $direccion): ?>
                        <option value="<?= (int)$direccion['unidad_id'] ?>"><?= htmlspecialchars((string)$direccion['nombre_unidad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="catalogoProceso">Tipo de proceso <span class="required">*</span></label>
                    <select id="catalogoProceso" name="tipo_proceso">
                        <?php foreach (Catalogos::TIPOS_PROCESO as $proceso): ?>
                        <option value="<?= htmlspecialchars($proceso) ?>"><?= htmlspecialchars($proceso) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="camposPuesto" class="catalog-quick-fields" hidden>
                <div class="field">
                    <label for="catalogoRmu">RMU referencial ($)</label>
                    <input id="catalogoRmu" name="remuneracion_unificada" type="number" min="0" max="9999999.99" step="0.01" value="0.00">
                    <small>Puede ajustarse después desde Estructura y cargos.</small>
                </div>
            </div>
        </div>

        <footer class="catalog-quick-actions">
            <button type="button" class="btn btn-outline" onclick="cerrarCatalogoRapido()">Cancelar</button>
            <button type="submit" class="btn btn-primary" id="catalogoGuardar"><i class="bi bi-check-lg"></i> Guardar y seleccionar</button>
        </footer>
    </form>
</dialog>

<script>
(() => {
    const config = <?= json_encode([
        'unidadSelectId' => $catalogoConfig['unidadSelectId'],
        'puestoSelectId' => $catalogoConfig['puestoSelectId'],
        'rmuTargetId' => $catalogoConfig['rmuTargetId'],
        'endpoint' => BASE_URL.'/talento-humano/catalogo',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const dialog = document.getElementById('catalogoRapido');
    const form = document.getElementById('catalogoRapidoForm');
    const typeInput = document.getElementById('catalogoTipo');
    const unitClass = document.getElementById('catalogoClaseUnidad');
    const parentField = document.getElementById('catalogoPadreField');
    const parentSelect = document.getElementById('catalogoPadre');

    const syncUnitType = () => {
        const isArea = unitClass?.value === 'area';
        if (parentField) parentField.hidden = !isArea;
        if (parentSelect) {
            parentSelect.required = isArea;
            if (!isArea) parentSelect.value = '';
        }
    };

    window.abrirCatalogoRapido = (tipo) => {
        if (!dialog || !form || !['unidad', 'puesto'].includes(tipo)) return;
        form.reset();
        typeInput.value = tipo;
        document.getElementById('catalogoTitulo').textContent = tipo === 'unidad'
            ? 'Crear dirección o departamento'
            : 'Crear cargo o puesto';
        document.getElementById('camposUnidad').hidden = tipo !== 'unidad';
        document.getElementById('camposPuesto').hidden = tipo !== 'puesto';
        if (unitClass) unitClass.value = 'area';
        syncUnitType();
        dialog.showModal();
        requestAnimationFrame(() => document.getElementById('catalogoNombre')?.focus());
    };
    window.cerrarCatalogoRapido = () => dialog?.close();
    unitClass?.addEventListener('change', syncUnitType);
    dialog?.addEventListener('click', (event) => {
        if (event.target === dialog) cerrarCatalogoRapido();
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        const tipo = typeInput.value;
        const data = new FormData(form);
        data.set('nombre', document.getElementById('catalogoNombre').value.trim());
        if (tipo === 'unidad' && unitClass?.value === 'direccion') data.set('unidad_padre_id', '');
        const saveButton = document.getElementById('catalogoGuardar');
        saveButton.disabled = true;
        saveButton.innerHTML = '<span class="catalog-quick-spinner" aria-hidden="true"></span> Guardando...';
        try {
            const response = await fetch(`${config.endpoint}/${tipo}`, {
                method: 'POST', body: data, headers: {'X-Requested-With': 'XMLHttpRequest'}
            });
            const result = await response.json().catch(() => ({success:false,message:'Respuesta inválida del servidor.'}));
            if (!response.ok || !result.success) throw new Error(result.message || 'No fue posible guardar el catálogo.');

            const selectId = tipo === 'unidad' ? config.unidadSelectId : config.puestoSelectId;
            const targetSelect = document.getElementById(selectId);
            if (!targetSelect) throw new Error('El registro se guardó, pero no se encontró el selector de destino.');
            const parentText = tipo === 'unidad' && unitClass?.value === 'area'
                ? parentSelect?.options[parentSelect.selectedIndex]?.textContent?.trim() : '';
            const optionText = parentText ? `${parentText} / ${result.nombre}` : result.nombre;
            const option = new Option(optionText, String(result.id), true, true);
            if (tipo === 'puesto') option.dataset.rmu = String(result.rmu ?? 0);
            targetSelect.add(option);
            targetSelect.dispatchEvent(new Event('change', {bubbles:true}));

            if (tipo === 'puesto' && config.rmuTargetId && Number(result.rmu) > 0) {
                const rmuTarget = document.getElementById(config.rmuTargetId);
                if (rmuTarget && Number(rmuTarget.value || 0) === 0) rmuTarget.value = Number(result.rmu).toFixed(2);
            }
            cerrarCatalogoRapido();
            showToast?.(result.message || 'Catálogo guardado correctamente.', 'success');
            document.dispatchEvent(new CustomEvent('catalogo:creado', {detail:{tipo, ...result}}));
        } catch (error) {
            showToast?.(error.message || 'Error de comunicación con el servidor.', 'error');
        } finally {
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="bi bi-check-lg"></i> Guardar y seleccionar';
        }
    });
})();
</script>
