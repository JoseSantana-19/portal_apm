<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Maestros y denominaciones | APM</title>
    <?php require ROOT . '/shared/head_assets.php'; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin_compact.css">
    <style>
        /* ── Buscador de tabla ───────────────────────────────────────────── */
        .master-search-wrap {
            padding: 12px 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .master-search-icon {
            color: var(--ocean-600, #0891b2);
            font-size: 1rem;
            flex-shrink: 0;
        }
        .master-search-input {
            width: 100%;
            padding: 7px 12px;
            border: 1.5px solid rgba(14,116,144,.35);
            border-radius: 8px;
            font-size: .82rem;
            font-weight: 500;
            background: #f0fdff;
            color: #0f172a;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .master-search-input:focus {
            border-color: var(--ocean-500, #06b6d4);
            box-shadow: 0 0 0 3px rgba(6,182,212,.15);
            background: #fff;
        }
        .master-search-input::placeholder { color: #94a3b8; }
        .master-search-count {
            font-size: .75rem;
            color: #64748b;
            padding: 4px 16px 10px;
            min-height: 22px;
        }
        .master-search-count strong { color: var(--ocean-700, #0e7490); }
        .master-no-results { display: none; }
        .master-no-results td {
            text-align: center;
            padding: 24px;
            color: #94a3b8;
            font-size: .85rem;
        }
        mark.hl {
            background: rgba(6,182,212,.22);
            color: inherit;
            border-radius: 3px;
            padding: 0 1px;
        }
    </style>
</head>
<body><div class="app">
<?php require ROOT . '/shared/menu.php'; ?>
<section class="content">
<?php $topbarTitle='Estructura y cargos';$topbarSubtitle='Direcciones, áreas y denominaciones';$topbarShowSearch=true;require ROOT.'/shared/topbar.php'; ?>
<main class="main"><div class="content-shell master-section admin-page">

<!-- ══ BLOQUE 1: Dirección / Área ══════════════════════════════════════ -->
<section class="master-grid">
    <form class="card master-form" method="post" action="<?= BASE_URL ?>/admin/maestros/unidad/guardar" id="formUnidad">
        <h3><?= $unidadEditar ? 'Editar unidad' : 'Crear dirección / área' ?></h3>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
        <input type="hidden" name="unidad_id" value="<?= (int)($unidadEditar['unidad_id'] ?? 0) ?>">

        <div class="master-field">
            <label>Nombre</label>
            <input name="nombre_unidad" id="inpNombreUnidad"
                   maxlength="150" required autocomplete="off"
                   placeholder="Escribe para filtrar la tabla →"
                   value="<?= htmlspecialchars($unidadEditar['nombre_unidad'] ?? '') ?>">
        </div>

        <div class="master-field">
            <label>Dirección padre</label>
            <select name="unidad_padre_id">
                <option value="">Ninguna: es una Dirección</option>
                <?php foreach ($unidades as $u): if ($u['unidad_padre_id'] !== null) continue; ?>
                <option value="<?= (int)$u['unidad_id'] ?>"
                    <?= (int)($unidadEditar['unidad_padre_id'] ?? 0) === (int)$u['unidad_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['nombre_unidad']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <small>Seleccione una dirección para crear el registro como Área.</small>
        </div>

        <div class="master-field">
            <label>Tipo de proceso</label>
            <select name="tipo_proceso">
                <?php foreach (Catalogos::TIPOS_PROCESO as $tipo): ?>
                <option <?= ($unidadEditar['tipo_proceso'] ?? '') === $tipo ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tipo) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="master-field">
            <label>Estado</label>
            <select name="activo">
                <option value="1" <?= (int)($unidadEditar['activo'] ?? 1) === 1 ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= isset($unidadEditar['activo']) && (int)$unidadEditar['activo'] === 0 ? 'selected' : '' ?>>Inactivo (baja lógica)</option>
            </select>
        </div>

        <button class="btn btn-primary" type="submit">
            <i class="bi bi-check2-circle"></i> Guardar unidad
        </button>
    </form>

    <div class="card">
        <div class="card-header"><div>
            <h3>Estructura organizacional</h3>
            <p>Las áreas aparecen debajo de su dirección.</p>
        </div></div>

        <div class="master-search-wrap">
            <i class="bi bi-search master-search-icon"></i>
            <input type="search" id="buscarUnidad" class="master-search-input"
                   placeholder="Buscar por nombre, código o tipo..."
                   autocomplete="off" aria-label="Buscar en estructura organizacional">
        </div>
        <div class="master-search-count" id="cntUnidad"></div>

        <div class="master-table">
            <table id="tablaUnidades" data-apm-datatable data-dt-searching="false" data-dt-page-length="25"
                   data-dt-order='[[2,"asc"]]' data-dt-order-disabled="5"
                   data-dt-empty="No existen unidades registradas.">
                <thead><tr><th>Código</th><th>Tipo</th><th>Nombre</th><th>Dirección padre</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach($unidades as $u): ?>
                <tr data-search="<?= strtolower(htmlspecialchars(
                    ($u['codigo_uorg'] ?? '').' '.($u['tipo_unidad'] ?? '').' '.($u['nombre_unidad'] ?? '').' '.($u['direccion_padre'] ?? '')
                )) ?>">
                    <td><?= htmlspecialchars($u['codigo_uorg']) ?></td>
                    <td><?= htmlspecialchars($u['tipo_unidad']) ?></td>
                    <td class="col-nombre"><?= htmlspecialchars($u['nombre_unidad']) ?></td>
                    <td><?= htmlspecialchars($u['direccion_padre'] ?? '—') ?></td>
                    <td class="state-<?= (int)$u['activo'] ?>"><?= (int)$u['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                    <td><a class="btn btn-outline" href="<?= BASE_URL ?>/admin/maestros?unidad_id=<?= (int)$u['unidad_id'] ?>">Editar</a></td>
                </tr>
                <?php endforeach; ?>
                <tr class="master-no-results" id="emptyUnidad" data-dt-empty>
                    <td colspan="6"><i class="bi bi-search"></i> Sin resultados para esa búsqueda.</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ══ BLOQUE 2: Cargos / Puestos ══════════════════════════════════════ -->
<section class="master-grid">
    <form class="card master-form" method="post" action="<?= BASE_URL ?>/admin/maestros/puesto/guardar" id="formPuesto">
        <h3><?= $puestoEditar ? 'Editar denominación' : 'Agregar denominación libre' ?></h3>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
        <input type="hidden" name="puesto_id" value="<?= (int)($puestoEditar['puesto_id'] ?? 0) ?>">

        <div class="master-field">
            <label>Denominación del puesto</label>
            <input name="nombre_puesto" id="inpNombrePuesto"
                   maxlength="150" required autocomplete="off"
                   placeholder="Escribe para filtrar la tabla →"
                   value="<?= htmlspecialchars($puestoEditar['nombre_puesto'] ?? '') ?>">
        </div>

        <div class="master-field">
            <label>Remuneración unificada</label>
            <input name="remuneracion_unificada" type="number" min="0" step="0.01"
                   value="<?= htmlspecialchars($puestoEditar['remuneracion_unificada'] ?? '0.00') ?>">
        </div>

        <div class="master-field">
            <label>Estado</label>
            <select name="activo">
                <option value="1" <?= (int)($puestoEditar['activo'] ?? 1) === 1 ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= isset($puestoEditar['activo']) && (int)$puestoEditar['activo'] === 0 ? 'selected' : '' ?>>Inactivo (baja lógica)</option>
            </select>
        </div>

        <button class="btn btn-primary" type="submit">
            <i class="bi bi-plus-circle"></i> Guardar denominación
        </button>
    </form>

    <div class="card">
        <div class="card-header"><div>
            <h3>Cargos / puestos</h3>
            <p>Catálogo de denominaciones disponible en todo el sistema.</p>
        </div></div>

        <div class="master-search-wrap">
            <i class="bi bi-search master-search-icon"></i>
            <input type="search" id="buscarPuesto" class="master-search-input"
                   placeholder="Buscar por denominación, código o RMU..."
                   autocomplete="off" aria-label="Buscar en cargos y puestos">
        </div>
        <div class="master-search-count" id="cntPuesto"></div>

        <div class="master-table">
            <table id="tablaPuestos" data-apm-datatable data-dt-searching="false" data-dt-page-length="25"
                   data-dt-order='[[1,"asc"]]' data-dt-order-disabled="4"
                   data-dt-empty="No existen cargos o puestos registrados.">
                <thead><tr><th>Código</th><th>Denominación</th><th>RMU</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                <?php foreach($puestos as $p): ?>
                <tr data-search="<?= strtolower(htmlspecialchars(
                    ($p['codigo_puesto'] ?? '').' '.($p['nombre_puesto'] ?? '').' '.number_format((float)$p['remuneracion_unificada'],2)
                )) ?>">
                    <td><?= htmlspecialchars($p['codigo_puesto']) ?></td>
                    <td class="col-nombre"><?= htmlspecialchars($p['nombre_puesto']) ?></td>
                    <td>$<?= number_format((float)$p['remuneracion_unificada'],2) ?></td>
                    <td class="state-<?= (int)$p['activo'] ?>"><?= (int)$p['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                    <td><a class="btn btn-outline" href="<?= BASE_URL ?>/admin/maestros?puesto_id=<?= (int)$p['puesto_id'] ?>">Editar</a></td>
                </tr>
                <?php endforeach; ?>
                <tr class="master-no-results" id="emptyPuesto" data-dt-empty>
                    <td colspan="5"><i class="bi bi-search"></i> Sin resultados para esa búsqueda.</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

</div></main></section></div>

<?php if(!empty($_GET['msg'])): ?><script>addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_GET['msg']) ?>,<?= ($_GET['ok']??'0')==='1'?"'success'":"'error'" ?>));</script><?php endif; ?>

<script>
/* ══ Live search — Estructura y Cargos ════════════════════════════════
   Escribir en el campo del formulario izquierdo filtra la tabla derecha
   al instante. El buscador de la tabla también funciona independiente.
   Ambos se sincronizan de forma bidireccional.
════════════════════════════════════════════════════════════════════ */
(function () {
    'use strict';

    function bindLiveSearch(cfg) {
        const table   = document.getElementById(cfg.tableId);
        const counter = document.getElementById(cfg.counterId);
        if (!table) return;

        function run(value, source) {
            if (source !== 'search' && cfg.searchInput && cfg.searchInput.value !== value) cfg.searchInput.value = value;
            if (source !== 'form'   && cfg.formInput   && cfg.formInput.value   !== value) cfg.formInput.value   = value;
            const dataTable=window.apmDataTables?.get(table);
            if(!dataTable)return;
            dataTable.search(value.trim()).page('first').draw();
            const info=dataTable.page.info();
            counter.textContent=value.trim()
                ? `${info.recordsDisplay} de ${info.recordsTotal} registros coinciden.`
                : `${info.recordsTotal} registros en total.`;
        }

        if (cfg.formInput)   cfg.formInput.addEventListener('input',   () => run(cfg.formInput.value,   'form'));
        if (cfg.searchInput) cfg.searchInput.addEventListener('input', () => run(cfg.searchInput.value, 'search'));

        table.addEventListener('apm:datatable-ready',()=>run((cfg.formInput && cfg.formInput.value) || '', 'init'));
    }

    document.addEventListener('DOMContentLoaded', () => {
        bindLiveSearch({
            formInput:   document.getElementById('inpNombreUnidad'),
            searchInput: document.getElementById('buscarUnidad'),
            tableId:     'tablaUnidades',
            counterId:   'cntUnidad',
            emptyId:     'emptyUnidad',
        });
        bindLiveSearch({
            formInput:   document.getElementById('inpNombrePuesto'),
            searchInput: document.getElementById('buscarPuesto'),
            tableId:     'tablaPuestos',
            counterId:   'cntPuesto',
            emptyId:     'emptyPuesto',
        });
    });
})();
</script>

<?php require ROOT.'/shared/footer_scripts.php'; ?>
</body></html>
