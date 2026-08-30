<?php
/**
 * MAESTROS.PHP - Vista de Gestión de Datos Maestros Modernizada
 * Gestión Completa de:
 * 1. Grupo de Productos (Categorías con Código)
 * 2. Grupo de Centros de Consumo (Nuevo)
 * 3. Productos (Catálogo)
 * 4. Centros de Consumo (Nuevo)
 * 5. Proveedores (Nuevo)
 * 6. Unidades de Medida
 * 7. Tasas de IVA
 * 
 * Compatible con PHP 7.4, 8.3 y 8.4
 */

// Tabla activa (por defecto 'categorias' = Grupo de Productos)
$tablaActiva = isset($tablaActiva) ? $tablaActiva : 'categorias';
$tipoBien = isset($tipoBien) && $tipoBien === 'AF' ? 'AF' : 'CC';
$soloLectura = isset($soloLectura) ? (bool)$soloLectura : false;
if (!empty($_permisoVista['readonly'])) $soloLectura = true;
$columnasMaestro = [
    'categorias' => 5,
    'grupo_centros_consumo' => 4,
    'productos' => 7,
    'centros_consumo' => 6,
    'proveedores' => 5,
    'unidades' => 4,
    'tipos_iva' => 4,
];
$totalColumnasMaestro = $columnasMaestro[$tablaActiva] ?? 5;
?>

<style>
.maestro-tipo-lista{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;padding:0 20px 16px}.maestro-tipo-opcion{display:flex;align-items:center;justify-content:center;gap:8px;min-height:40px;padding:8px 14px;border:1px solid var(--border-color);border-radius:8px;background:var(--panel-bg);color:var(--text-color);font-size:13px;font-weight:700;text-decoration:none;transition:border-color .18s,background .18s,color .18s}.maestro-tipo-opcion:hover{border-color:#93c5fd;background:#f8fbff}.maestro-tipo-opcion.activo-cc{border-color:#3b82f6;background:#eff6ff;color:#2563eb}.maestro-tipo-opcion.activo-af{border-color:#64748b;background:#f8fafc;color:#334155}.maestro-tipo-opcion strong{font-size:12px}@media(max-width:720px){.maestro-tipo-lista{grid-template-columns:1fr;padding-left:14px;padding-right:14px}}
.prov-modal{max-width:920px!important}.prov-intro{display:flex;align-items:flex-start;gap:12px;padding:14px 16px;margin-bottom:18px;border:1px solid #bfdbfe;border-radius:12px;background:linear-gradient(135deg,#eff6ff,#f8fafc);color:#1e3a8a}.prov-intro i{font-size:22px;color:#2563eb;margin-top:2px}.prov-intro strong{display:block;margin-bottom:3px}.prov-intro span{font-size:12px;color:#475569}.prov-section{padding:16px;border:1px solid var(--border-color);border-radius:14px;background:var(--panel-bg);margin-bottom:14px}.prov-section-title{display:flex;align-items:center;gap:8px;margin:0 0 14px;font-size:13px;font-weight:800;color:var(--text-color)}.prov-section-title i{color:var(--primary)}.prov-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:14px}.prov-col-3{grid-column:span 3}.prov-col-4{grid-column:span 4}.prov-col-5{grid-column:span 5}.prov-col-6{grid-column:span 6}.prov-col-7{grid-column:span 7}.prov-col-8{grid-column:span 8}.prov-col-12{grid-column:span 12}.prov-card-title{display:flex;align-items:center;gap:8px}.prov-code{font-size:10px;padding:3px 7px;border-radius:999px;background:#eff6ff;color:#2563eb;font-weight:800}.prov-contact{display:block;font-weight:700;color:var(--text-color)}.prov-meta{display:block;margin-top:3px;color:var(--text-muted);font-size:11px;line-height:1.5}.maestro-selectores{display:flex;align-items:end;gap:12px;flex-wrap:wrap;padding:16px 20px;border-bottom:1px solid var(--border-color);background:rgba(59,130,246,.035)}.maestro-selector{min-width:240px}.maestro-selector.tipo{min-width:250px}.maestro-selector label{display:block;margin-bottom:6px;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted)}.maestro-selector select{width:100%;height:40px;border:1px solid var(--border-color);border-radius:9px;background:var(--panel-bg);color:var(--text-color);font-weight:700;padding:0 11px}.maestro-selector-info{display:flex;align-items:center;gap:8px;min-height:40px;padding:8px 12px;border-radius:9px;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:700}@media(max-width:760px){.prov-grid>*{grid-column:span 12!important}.prov-modal{max-width:96%!important}.maestro-selector,.maestro-selector.tipo{min-width:100%;width:100%}}
.dt-maestros-top{display:flex;justify-content:flex-end;padding:10px 20px;border-bottom:1px solid var(--border-color)}.dt-button{border:1px solid var(--border-color)!important;border-radius:8px!important;background:var(--panel-bg)!important;color:var(--text-color)!important;font-size:12px!important;font-weight:700!important;padding:8px 12px!important}.dt-button:hover{border-color:#93c5fd!important;background:#eff6ff!important;color:#1d4ed8!important}#tabla-maestros_wrapper{width:100%}#tabla-maestros_wrapper table.dataTable{width:100%!important;margin:0!important;border-collapse:collapse!important;table-layout:auto}#tabla-maestros_wrapper table.dataTable th,#tabla-maestros_wrapper table.dataTable td{white-space:normal!important;overflow-wrap:anywhere;word-break:normal;padding:10px 8px;font-size:12px;line-height:1.35}#tabla-maestros_wrapper table.dataTable thead th{font-size:11px}#tabla-maestros_wrapper table.dataTable.no-footer{border-bottom:0}#tabla-maestros_wrapper .dtr-details{width:100%;padding:8px 12px!important}#tabla-maestros_wrapper .dtr-title{min-width:130px;font-weight:800}.table-responsive:has(#tabla-maestros){overflow:visible}@media(max-width:1100px){#tabla-maestros_wrapper table.dataTable th,#tabla-maestros_wrapper table.dataTable td{padding:8px 6px;font-size:11px}.prov-card-title{align-items:flex-start;flex-direction:column;gap:3px}}
.maestro-table-toolbar{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:11px 20px;border-bottom:1px solid var(--border-color);background:linear-gradient(90deg,rgba(59,130,246,.025),rgba(245,158,11,.025))}.maestro-table-search{display:flex;align-items:center;gap:8px;flex:1;min-width:0}.maestro-search-box{position:relative;width:min(430px,100%)}.maestro-search-box i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#64748b;font-size:12px;pointer-events:none}.maestro-search-box input{width:100%;height:38px;padding:8px 13px 8px 34px;border:1px solid #cbd5e1;border-radius:9px;background:var(--panel-bg);color:var(--text-color);font-size:13px;transition:border-color .18s,box-shadow .18s}.maestro-search-box input:focus{border-color:#60a5fa;box-shadow:0 0 0 3px rgba(59,130,246,.1);outline:0}.maestro-server-search{display:flex;align-items:center;gap:8px;flex:1;flex-wrap:wrap}.maestro-server-search .maestro-search-box{flex:1;min-width:230px}.maestro-server-search select{height:38px;padding:0 10px;border:1px solid #cbd5e1;border-radius:9px;background:var(--panel-bg);color:var(--text-color)}#maestro-export-actions{display:flex;align-items:center;justify-content:flex-end;min-width:max-content}#maestro-export-actions .dt-buttons{display:flex;gap:7px}.dt-maestros-buttons{display:none}@media(max-width:720px){.maestro-table-toolbar{align-items:stretch;flex-direction:column;padding:10px 14px}.maestro-table-search,.maestro-search-box,.maestro-server-search{width:100%}#maestro-export-actions{justify-content:flex-start}.maestro-server-search .btn-primary{height:38px}}
.dt-maestros-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 20px;border-top:1px solid var(--border-color);background:rgba(248,250,252,.7)}.dt-maestros-footer .dataTables_info{padding:0!important;color:var(--text-muted);font-size:12px}.dt-maestros-footer .dataTables_paginate{padding:0!important}@media(max-width:640px){.dt-maestros-footer{align-items:flex-start;flex-direction:column;padding:10px 14px}}
</style>

<!-- InvCabecera de Página -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <?php if ($tablaActiva === 'busqueda_global'): ?>
            <h1>Búsqueda Global Inteligente</h1>
            <p>Rastrea e identifica de forma centralizada cualquier registro en todos los módulos del sistema portuario.</p>
        <?php else: ?>
            <h1>Gestión de Maestros</h1>
            <p>Administración y relación del catálogo corporativo, proveedores, infraestructura de consumo y tributación impositiva.</p>
        <?php endif; ?>
    </div>
    <?php if ($tablaActiva !== 'busqueda_global' && !$soloLectura): ?>
    <div>
        <button class="btn-primary" onclick="abrirModalMaestro()" id="btn-agregar-maestro">
            <i class="fa-solid fa-plus"></i> Agregar Registro
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Grid principal -->
<div style="display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start;" class="animate-fade-in">

    <!-- Panel Izquierdo: Navegación de Secciones (Sidebar de Pestañas Estilizado) -->
    <div class="panel" style="padding:16px;">
        <h4 style="margin:0 0 16px 0;font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;font-weight:700;">
            <i class="fa-solid fa-folder-tree" style="margin-right:6px;color:var(--primary);"></i> Estructura de Datos
        </h4>
        <div style="display:flex;flex-direction:column;gap:6px;">

            <!-- Búsqueda Global -->
            <a href="index.php?route=inv_maestros&tabla=busqueda_global"
               class="filter-tab <?= ($tablaActiva === 'busqueda_global') ? 'active' : '' ?>"
               style="text-align:left;display:flex;align-items:center;text-decoration:none;padding:12px 16px;border-radius:10px;gap:10px;margin-bottom:6px;border: 1px solid <?= ($tablaActiva === 'busqueda_global') ? 'var(--secondary-blue)' : 'transparent' ?>; background: <?= ($tablaActiva === 'busqueda_global') ? 'var(--primary-blue)' : 'rgba(59, 130, 246, 0.04)' ?>;">
                <i class="fa-solid fa-magnifying-glass" style="width:16px;color:<?= ($tablaActiva === 'busqueda_global') ? 'white' : 'var(--secondary-blue)' ?>;"></i>
                <span style="flex:1;font-weight:700;color:<?= ($tablaActiva === 'busqueda_global') ? 'white' : 'var(--text-color)' ?>;">Búsqueda Global</span>
                <span style="background:rgba(255,255,255,0.25);color:<?= ($tablaActiva === 'busqueda_global') ? 'var(--primary-blue)' : 'var(--secondary-blue)' ?>;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700;box-shadow: 0 1px 3px rgba(0,0,0,0.1);">★</span>
            </a>

            <!-- Grupo de Productos -->
            <a href="index.php?route=inv_maestros&tabla=categorias"
               class="filter-tab <?= ($tablaActiva === 'categorias') ? 'active' : '' ?>"
               style="text-align:left;display:flex;align-items:center;text-decoration:none;padding:12px 16px;border-radius:10px;gap:10px;">
                <i class="fa-solid fa-tags" style="width:16px;color:<?= ($tablaActiva === 'categorias') ? 'white' : 'var(--text-muted)' ?>;"></i>
                <span style="flex:1;">1. Grupo de Productos</span>
                <span style="background:rgba(255,255,255,0.2);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;"><?= ($conteos['categorias_cc'] ?? 0) + ($conteos['categorias_af'] ?? 0) ?></span>
            </a>

            <!-- Grupo de Centros de Consumo -->
            <a href="index.php?route=inv_maestros&tabla=grupo_centros_consumo"
               class="filter-tab <?= ($tablaActiva === 'grupo_centros_consumo') ? 'active' : '' ?>"
               style="text-align:left;display:flex;align-items:center;text-decoration:none;padding:12px 16px;border-radius:10px;gap:10px;">
                <i class="fa-solid fa-sitemap" style="width:16px;color:<?= ($tablaActiva === 'grupo_centros_consumo') ? 'white' : 'var(--text-muted)' ?>;"></i>
                <span style="flex:1;">2. Grupos Consumo (Áreas)</span>
                <span style="background:rgba(255,255,255,0.2);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;"><?= isset($conteos['grupo_centros_consumo']) ? $conteos['grupo_centros_consumo'] : 0 ?></span>
            </a>

            <!-- Productos -->
            <a href="index.php?route=inv_maestros&tabla=productos"
               class="filter-tab <?= ($tablaActiva === 'productos') ? 'active' : '' ?>"
               style="text-align:left;display:flex;align-items:center;text-decoration:none;padding:12px 16px;border-radius:10px;gap:10px;">
                <i class="fa-solid fa-box" style="width:16px;color:<?= ($tablaActiva === 'productos') ? 'white' : 'var(--text-muted)' ?>;"></i>
                <span style="flex:1;">3. Catálogo Productos</span>
                <span style="background:rgba(255,255,255,0.2);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;"><?= ($conteos['productos_cc'] ?? 0) + ($conteos['productos_af'] ?? 0) ?></span>
            </a>

            <!-- Centros de Consumo -->
            <a href="index.php?route=inv_maestros&tabla=centros_consumo"
               class="filter-tab <?= ($tablaActiva === 'centros_consumo') ? 'active' : '' ?>"
               style="text-align:left;display:flex;align-items:center;text-decoration:none;padding:12px 16px;border-radius:10px;gap:10px;">
                <i class="fa-solid fa-building-flag" style="width:16px;color:<?= ($tablaActiva === 'centros_consumo') ? 'white' : 'var(--text-muted)' ?>;"></i>
                <span style="flex:1;">4. Centros de Consumo</span>
                <span style="background:rgba(255,255,255,0.2);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;"><?= isset($conteos['centros_consumo']) ? $conteos['centros_consumo'] : 0 ?></span>
            </a>

            <!-- Proveedores -->
            <a href="index.php?route=inv_maestros&tabla=proveedores"
               class="filter-tab <?= ($tablaActiva === 'proveedores') ? 'active' : '' ?>"
               style="text-align:left;display:flex;align-items:center;text-decoration:none;padding:12px 16px;border-radius:10px;gap:10px;">
                <i class="fa-solid fa-truck-field" style="width:16px;color:<?= ($tablaActiva === 'proveedores') ? 'white' : 'var(--text-muted)' ?>;"></i>
                <span style="flex:1;">5. Proveedores</span>
                <span style="background:rgba(255,255,255,0.2);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;"><?= isset($conteos['proveedores']) ? $conteos['proveedores'] : 0 ?></span>
            </a>

            <!-- Unidades de Medida -->
            <a href="index.php?route=inv_maestros&tabla=unidades"
               class="filter-tab <?= ($tablaActiva === 'unidades') ? 'active' : '' ?>"
               style="text-align:left;display:flex;align-items:center;text-decoration:none;padding:12px 16px;border-radius:10px;gap:10px;">
                <i class="fa-solid fa-calculator" style="width:16px;color:<?= ($tablaActiva === 'unidades') ? 'white' : 'var(--text-muted)' ?>;"></i>
                <span style="flex:1;">6. Unidades de Medida</span>
                <span style="background:rgba(255,255,255,0.2);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;"><?= isset($conteos['unidades']) ? $conteos['unidades'] : 0 ?></span>
            </a>

            <!-- Tasas de IVA -->
            <a href="index.php?route=inv_maestros&tabla=tipos_iva"
               class="filter-tab <?= ($tablaActiva === 'tipos_iva') ? 'active' : '' ?>"
               style="text-align:left;display:flex;align-items:center;text-decoration:none;padding:12px 16px;border-radius:10px;gap:10px;">
                <i class="fa-solid fa-file-invoice-dollar" style="width:16px;color:<?= ($tablaActiva === 'tipos_iva') ? 'white' : 'var(--text-muted)' ?>;"></i>
                <span style="flex:1;">7. Tasas de IVA</span>
                <span style="background:rgba(255,255,255,0.2);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;"><?= isset($conteos['tipos_iva']) ? $conteos['tipos_iva'] : 0 ?></span>
            </a>

        </div>

        <!-- Info de la sección activa (Branding Relacional) -->
        <div style="margin-top:20px;padding:14px;background:rgba(37, 99, 235, 0.05);border-radius:10px;border:1px solid rgba(37, 99, 235, 0.15);">
            <?php
            $infoSecciones = [
                'busqueda_global' => ['icono' => 'fa-magnifying-glass', 'color' => '#3b82f6', 'titulo' => 'Buscador Centralizado', 'desc' => 'Rastrea e identifica cualquier registro (Bienes, Transacciones, Maestros, Usuarios o Bitácora) en todo el puerto.'],
                'categorias' => ['icono' => 'fa-tags',               'color' => '#3b82f6', 'titulo' => 'Grupo de Productos',      'desc' => 'Estructura contable y grupos clasificadores (con códigos) para agrupar los insumos.'],
                'grupo_centros_consumo' => ['icono' => 'fa-sitemap', 'color' => '#8b5cf6', 'titulo' => 'Áreas / Departamentos', 'desc' => 'Grupos de consumo obtenidos de las áreas activas de Talento Humano.'],
                'productos'  => ['icono' => 'fa-box', 'color' => '#10b981', 'titulo' => 'Catálogo de Productos', 'desc' => 'Catálogo separado entre bienes de consumo corriente y activos fijos.'],
                'centros_consumo' => ['icono' => 'fa-users', 'color' => '#ec4899', 'titulo' => 'Funcionarios', 'desc' => 'Centros de consumo obtenidos de los funcionarios activos y relacionados con su área.'],
                'proveedores' => ['icono' => 'fa-truck-field',       'color' => '#f59e0b', 'titulo' => 'Proveedores Oficiales',  'desc' => 'Entidades externas habilitadas para comercializar y realizar ingresos a bodega.'],
                'unidades'   => ['icono' => 'fa-calculator',         'color' => '#06b6d4', 'titulo' => 'Unidades de Medida',    'desc' => 'Unidades físicas (U., Gl., Kg.) para la valoración exacta de insumos.'],
                'tipos_iva'  => ['icono' => 'fa-file-invoice-dollar','color' => '#ef4444', 'titulo' => 'Tasas de IVA',          'desc' => 'Tasas impositivas aplicables a productos del catálogo.'],
            ];
            $info = isset($infoSecciones[$tablaActiva]) ? $infoSecciones[$tablaActiva] : $infoSecciones['categorias'];
            ?>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <div style="width:30px;height:30px;border-radius:8px;background:<?= $info['color'] ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa-solid <?= $info['icono'] ?>" style="color:<?= $info['color'] ?>;font-size:13px;"></i>
                </div>
                <strong style="font-size:13px;color:var(--text-color);"><?= $info['titulo'] ?></strong>
            </div>
            <p style="margin:0;font-size:12px;color:var(--text-muted);line-height:1.5;"><?= $info['desc'] ?></p>
        </div>
    </div>

    <?php if ($tablaActiva !== 'busqueda_global'): ?>
    <!-- Panel Derecho: Tabla de Datos (Estilizada Modernamente con badges) -->
    <div class="panel" style="position:relative;">
        <?php if (in_array($tablaActiva, ['categorias', 'productos'], true)): ?>
        <form class="maestro-selectores" method="GET" action="index.php">
            <input type="hidden" name="route" value="inv_maestros">
            <input type="hidden" name="tabla" value="<?= htmlspecialchars($tablaActiva) ?>">
            <div class="maestro-selector tipo">
                <label><i class="fa-solid fa-list"></i> Lista de tipo de bien</label>
                <select name="tipo" onchange="this.form.submit()">
                    <option value="CC" <?= $tipoBien === 'CC' ? 'selected' : '' ?>>Bienes de consumo corriente</option>
                    <option value="AF" <?= $tipoBien === 'AF' ? 'selected' : '' ?>>Activos fijos</option>
                </select>
            </div>
            <div class="maestro-selector-info"><i class="fa-solid <?= $tipoBien === 'AF' ? 'fa-building-shield' : 'fa-box-open' ?>"></i><?= $tipoBien === 'AF' ? 'Lista activa: Activos fijos' : 'Lista activa: Consumo corriente' ?></div>
        </form>
        <?php endif; ?>
        <div class="panel-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
            <h3 style="margin:0; flex:1; min-width:250px;">
                <?php
                $titulos = [
                    'categorias' => '<i class="fa-solid fa-tags" style="margin-right:8px;color:#3b82f6;"></i> Grupo de Productos (Categorías)',
                    'grupo_centros_consumo' => '<i class="fa-solid fa-sitemap" style="margin-right:8px;color:#8b5cf6;"></i> Grupos de Consumo: Áreas / Departamentos',
                    'productos'  => '<i class="fa-solid fa-box" style="margin-right:8px;color:#10b981;"></i> Catálogo de Productos',
                    'centros_consumo' => '<i class="fa-solid fa-users" style="margin-right:8px;color:#ec4899;"></i> Centros de Consumo: Funcionarios',
                    'proveedores' => '<i class="fa-solid fa-truck-field" style="margin-right:8px;color:#f59e0b;"></i> Proveedores',
                    'unidades'   => '<i class="fa-solid fa-calculator" style="margin-right:8px;color:#06b6d4;"></i> Unidades de Medida',
                    'tipos_iva'  => '<i class="fa-solid fa-file-invoice-dollar" style="margin-right:8px;color:#ef4444;"></i> Tasas de IVA impositivas',
                ];
                echo isset($titulos[$tablaActiva]) ? $titulos[$tablaActiva] : $tablaActiva;
                ?>
            </h3>
        </div>
        <?php if ($soloLectura): ?>
        <div style="margin:0 20px 16px;padding:10px 12px;border-radius:9px;background:rgba(59,130,246,.08);color:var(--text-muted);font-size:12px;">
            <i class="fa-solid fa-database" style="color:#3b82f6;margin-right:6px;"></i>
            Datos oficiales de Talento Humano. Esta sección es de consulta y se actualiza desde su sistema de origen.
        </div>
        <?php endif; ?>
        <div class="maestro-table-toolbar">
            <div class="maestro-table-search">
                <?php if ($tablaActiva === 'productos'): ?>
                <!-- DataTables inserta aquí su buscador nativo al inicializar. -->
                <?php elseif ($paginacion !== null): ?>
                <form method="GET" action="index.php" class="maestro-server-search">
                    <input type="hidden" name="route" value="inv_maestros">
                    <input type="hidden" name="tabla" value="<?= htmlspecialchars($tablaActiva) ?>">
                    <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipoBien) ?>">
                    <div class="maestro-search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="buscar_maestro" value="<?= htmlspecialchars($busquedaMaestro ?? '') ?>" placeholder="Buscar en este maestro..."></div>
                    <select name="por_pagina" onchange="this.form.submit()">
                        <?php foreach ([25, 50, 100] as $cantidadPagina): ?>
                        <option value="<?= $cantidadPagina ?>" <?= (int)$paginacion['por_pagina'] === $cantidadPagina ? 'selected' : '' ?>><?= $cantidadPagina ?> por página</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-primary" style="height:38px;padding:0 12px;"><i class="fa-solid fa-search"></i> Buscar</button>
                </form>
                <?php else: ?>
                <div class="maestro-search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="buscador-individual" value="<?= htmlspecialchars($busquedaMaestro ?? '') ?>" placeholder="Buscar en este maestro..." oninput="filtrarTablaIndividual(this.value)"></div>
                <?php endif; ?>
            </div>
            <div id="maestro-export-actions"></div>
        </div>
        <div class="table-responsive">
            <table id="tabla-maestros" class="display responsive" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <?php if ($tablaActiva === 'categorias'): ?>
                            <th style="width:140px;">Código</th>
                            <th>Nombre del Grupo</th>
                            <th>Detalle / Descripción</th>
                        <?php elseif ($tablaActiva === 'grupo_centros_consumo'): ?>
                            <!-- Columna conservada para una posible reactivación:
                            <th style="width:100px;">Código</th>
                            -->
                            <th>Área / Departamento</th>
                            <th>Estructura Superior</th>
                        <?php elseif ($tablaActiva === 'productos'): ?>
                            <th style="width:110px;">Código</th>
                            <th>Nombre del Producto</th>
                            <th>Grupo / Categoría</th>
                            <th>Unidad de Medida</th>
                            <th><?= $tipoBien === 'AF' ? 'Unidades registradas' : 'IVA' ?></th>
                        <?php elseif ($tablaActiva === 'centros_consumo'): ?>
                            <th style="width:120px;">Cédula</th>
                            <th>Funcionario</th>
                            <th>Cargo</th>
                            <th>Área / Departamento</th>
                        <?php elseif ($tablaActiva === 'proveedores'): ?>
                            <th style="width:160px;">RUC / Identificación</th>
                            <th>Proveedor</th>
                            <th>Contacto y ubicación</th>
                        <?php elseif ($tablaActiva === 'unidades'): ?>
                            <th>Nombre de la Unidad</th>
                            <th>Abreviatura</th>
                        <?php elseif ($tablaActiva === 'tipos_iva'): ?>
                            <th>Descripción</th>
                            <th>Tasa (%)</th>
                        <?php endif; ?>
                        <th class="columna-acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-datos-inv_maestros">
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                            <tr id="row-<?= $item['id'] ?>" class="animate-fade-in">
                                <td><strong>#<?= $item['id'] ?></strong></td>
                                <?php if ($tablaActiva === 'categorias'): ?>
                                    <td><code style="background:var(--border-color);padding:3px 8px;border-radius:5px;font-weight:700;font-size:12px;color:var(--primary);"><?= htmlspecialchars($item['codigo'] ?? '—') ?></code></td>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td style="color:var(--text-muted);font-size:13px;"><?= !empty($item['extra']) ? htmlspecialchars($item['extra']) : '<em>—</em>' ?></td>
                                <?php elseif ($tablaActiva === 'grupo_centros_consumo'): ?>
                                    <!-- Código interno conservado para uso futuro:
                                    <td><code><?= htmlspecialchars($item['codigo'] ?? '—') ?></code></td>
                                    -->
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><strong><?= htmlspecialchars($item['representante'] ?? '—') ?></strong></td>
                                <?php elseif ($tablaActiva === 'productos'): ?>
                                    <td><code style="background:var(--border-color);padding:3px 8px;border-radius:5px;font-weight:700;font-size:12px;"><?= htmlspecialchars($item['codigo'] ?? '—') ?></code></td>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><span class="status-badge transit" style="font-size: 11px;"><?= htmlspecialchars($item['grupo_nombre']) ?></span></td>
                                    <td><code style="background:var(--secondary-bg);padding:3px 6px;border-radius:4px;font-size:11px;font-weight:600;"><?= htmlspecialchars($item['unidad_nombre']) ?></code></td>
                                    <td>
                                        <?php if ($tipoBien === 'AF'): ?>
                                        <span class="status-badge active"><?= (int)($item['unidades_registradas'] ?? 1) ?> unidad(es)</span>
                                        <?php else: ?>
                                        <?php
                                        $ivaNombre = '0%';
                                        foreach ($tiposIvaList as $tipo) {
                                            if ((int)$tipo['id'] === (int)$item['aplica_iva']) {
                                                $ivaNombre = number_format($tipo['tasa_iva'], 0) . '%';
                                                break;
                                            }
                                        }
                                        ?>
                                        <span class="status-badge <?= (float)$ivaNombre > 0 ? 'active' : 'inactive' ?>"><?= $ivaNombre ?></span>
                                        <?php endif; ?>
                                    </td>
                                <?php elseif ($tablaActiva === 'centros_consumo'): ?>
                                    <td><code style="background:var(--border-color);padding:3px 8px;border-radius:5px;font-weight:700;font-size:12px;"><?= htmlspecialchars($item['codigo'] ?? '—') ?></code></td>
                                    <td><strong><?= htmlspecialchars($item['funcionario'] ?? '—') ?></strong></td>
                                    <td style="color:var(--text-muted);font-size:12px;"><?= htmlspecialchars($item['extra'] ?? '—') ?></td>
                                    <td><span class="status-badge active" style="background:rgba(139,92,246,0.1);color:#8b5cf6;border-color:rgba(139,92,246,0.2);"><?= htmlspecialchars($item['grupo_nombre'] ?? '—') ?></span></td>
                                <?php elseif ($tablaActiva === 'proveedores'): ?>
                                    <td><code style="background:var(--border-color);padding:4px 8px;border-radius:5px;font-weight:700;"><?= htmlspecialchars($item['ruc']) ?></code></td>
                                    <td><span class="prov-card-title"><strong><?= htmlspecialchars($item['nombre']) ?></strong></span><span class="prov-meta"><?= htmlspecialchars($item['representante'] ?: 'Sin representante registrado') ?></span></td>
                                    <td><span class="prov-contact"><?= htmlspecialchars($item['telefono1'] ?: ($item['email'] ?: 'Sin contacto')) ?></span><span class="prov-meta"><?= htmlspecialchars(trim(($item['ciudad'] ?? '').' · '.($item['direccion'] ?? ''), ' ·') ?: 'Sin ubicación') ?></span></td>
                                <?php elseif ($tablaActiva === 'unidades'): ?>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><code style="background:var(--border-color);padding:3px 8px;border-radius:5px;font-weight:600;"><?= htmlspecialchars((string)($item['extra'] ?? '')) ?: '—' ?></code></td>
                                <?php elseif ($tablaActiva === 'tipos_iva'): ?>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><strong style="color:var(--primary);font-size:15px;"><?= number_format($item['tasa_iva'], 2) ?>%</strong></td>
                                <?php endif; ?>
                                <td class="acciones-cell columna-acciones">
                                    <?php if (!empty($item['solo_lectura']) || $soloLectura): ?>
                                    <span class="status-badge transit" title="Dato administrado en el sistema de origen"><i class="fa-solid fa-lock"></i> Consulta</span>
                                    <?php else: ?>
                                    <button class="btn-accion btn-editar" onclick="editarMaestro(<?= htmlspecialchars(json_encode($item)) ?>)" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($paginacion !== null): ?>
        <?php
            $paginaActual = (int)$paginacion['pagina'];
            $totalPaginas = (int)$paginacion['total_paginas'];
            $desde = $paginacion['total'] > 0 ? (($paginaActual - 1) * (int)$paginacion['por_pagina']) + 1 : 0;
            $hasta = min($paginacion['total'], $paginaActual * (int)$paginacion['por_pagina']);
            $urlPagina = 'index.php?route=inv_maestros&tabla=' . urlencode($tablaActiva)
                . '&tipo=' . urlencode($tipoBien)
                . '&buscar_maestro=' . urlencode($busquedaMaestro ?? '')
                . '&por_pagina=' . (int)$paginacion['por_pagina'];
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 20px;border-top:1px solid var(--border-color);flex-wrap:wrap;">
            <span style="font-size:12px;color:var(--text-muted);">Mostrando <?= $desde ?>–<?= $hasta ?> de <strong><?= (int)$paginacion['total'] ?></strong> registros</span>
            <div style="display:flex;align-items:center;gap:8px;">
                <?php if ($paginaActual > 1): ?><a class="btn-outline" style="text-decoration:none;padding:7px 10px;" href="<?= htmlspecialchars($urlPagina . '&pagina=' . ($paginaActual - 1)) ?>"><i class="fa-solid fa-chevron-left"></i> Anterior</a><?php endif; ?>
                <span style="font-size:12px;font-weight:700;">Página <?= $paginaActual ?> de <?= $totalPaginas ?></span>
                <?php if ($paginaActual < $totalPaginas): ?><a class="btn-outline" style="text-decoration:none;padding:7px 10px;" href="<?= htmlspecialchars($urlPagina . '&pagina=' . ($paginaActual + 1)) ?>">Siguiente <i class="fa-solid fa-chevron-right"></i></a><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Panel Derecho: Búsqueda Global Centralizada con Filtros Avanzados -->
    <div class="panel glass-panel animate-fade-in" style="padding: 24px; min-height: 480px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: var(--search-shadow); background: var(--panel-bg);">
        <form action="index.php" method="GET" id="global-search-inv_maestros-form">
            <input type="hidden" name="route" value="inv_maestros">
            <input type="hidden" name="tabla" value="busqueda_global">

            <!-- Fila 1: Caja de Búsqueda Destacada -->
            <div class="search-showcase" style="margin-bottom: 20px; position: relative; display: flex; align-items: center; background: var(--secondary-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 4px;">
                <i class="fa-solid fa-magnifying-glass search-showcase-icon" style="position: absolute; left: 16px; color: var(--secondary-blue); font-size: 16px;"></i>
                <input type="text" name="q" value="<?= htmlspecialchars((string)$q) ?>" 
                       class="search-showcase-input" placeholder="Buscar por códigos, inv_secuenciales, nombres, marcas, responsables... (Ctrl+K)" 
                       id="main-global-search-input" autofocus autocomplete="off"
                       style="border: none; background: transparent; outline: none; width: 100%; padding: 12px 16px 12px 42px; font-size: 15px; color: var(--text-color); font-weight: 500;">
                <span class="search-shortcut-hint" style="position: absolute; right: 16px; background: var(--border-color); color: var(--text-muted); padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; letter-spacing: 0.05em; pointer-events: none;">Ctrl + K</span>
            </div>

            <!-- Fila 2: Filtros de Ámbito y Límite de Búsqueda -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px; background: rgba(59, 130, 246, 0.02); padding: 16px; border-radius: 12px; border: 1px solid var(--border-color);">
                
                <!-- Filtro: Campo de Búsqueda -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; display: block;"><i class="fa-solid fa-square-poll-horizontal" style="margin-right: 5px; color: var(--secondary-blue);"></i> Ámbito del Campo</label>
                    <select name="campo" onchange="this.form.submit()" style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); font-size: 13px; outline: none;">
                        <option value="todos" <?= $campoBusqueda === 'todos' ? 'selected' : '' ?>>Todos los campos (Amplio)</option>
                        <option value="codigo" <?= $campoBusqueda === 'codigo' ? 'selected' : '' ?>>Solo Código / Secuencial / ID</option>
                        <option value="nombre" <?= $campoBusqueda === 'nombre' ? 'selected' : '' ?>>Solo Nombre / Descripción / Proveedor</option>
                        <option value="extra" <?= $campoBusqueda === 'extra' ? 'selected' : '' ?>>Solo Ubicación / Marca / Detalle Adicional</option>
                    </select>
                </div>

                <!-- Filtro: Límite de Resultados -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px; display: block;"><i class="fa-solid fa-list-ol" style="margin-right: 5px; color: var(--secondary-blue);"></i> Mostrar un Máximo de</label>
                    <select name="limite" onchange="this.form.submit()" style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--card-bg); color: var(--text-color); font-size: 13px; outline: none;">
                        <option value="10" <?= $limiteResultados === 10 ? 'selected' : '' ?>>10 coincidencias</option>
                        <option value="25" <?= $limiteResultados === 25 ? 'selected' : '' ?>>25 coincidencias</option>
                        <option value="50" <?= $limiteResultados === 50 ? 'selected' : '' ?>>50 coincidencias (Estándar)</option>
                        <option value="100" <?= $limiteResultados === 100 ? 'selected' : '' ?>>100 coincidencias (Detallado)</option>
                    </select>
                </div>
            </div>

            <!-- Fila 3: Filtros de Módulo Activos (Chips Checkbox) -->
            <div style="margin-bottom: 24px;">
                <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); margin-bottom: 10px; display: block;"><i class="fa-solid fa-cubes" style="margin-right: 5px; color: var(--secondary-blue);"></i> Módulos a Rastrear</label>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    
                    <!-- Inventario -->
                    <?php $chkInventario = in_array('inventario', $modulosSeleccionados); ?>
                    <label class="filter-checkbox-card" style="padding: 8px 14px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="modulos[]" value="inventario" <?= $chkInventario ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span class="checkbox-visual" style="border-radius: 50%;"></span>
                        <i class="fa-solid fa-ship" style="color:#1e40af; font-size:11px;"></i>
                        <span style="font-size:12px; font-weight:600;">Inventario</span>
                    </label>

                    <!-- Bodega -->
                    <?php $chkBodega = in_array('bodega', $modulosSeleccionados); ?>
                    <label class="filter-checkbox-card" style="padding: 8px 14px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="modulos[]" value="bodega" <?= $chkBodega ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span class="checkbox-visual" style="border-radius: 50%;"></span>
                        <i class="fa-solid fa-truck-ramp-box" style="color:#10b981; font-size:11px;"></i>
                        <span style="font-size:12px; font-weight:600;">Bodega</span>
                    </label>

                    <!-- Maestros -->
                    <?php $chkMaestros = in_array('inv_maestros', $modulosSeleccionados); ?>
                    <label class="filter-checkbox-card" style="padding: 8px 14px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="modulos[]" value="inv_maestros" <?= $chkMaestros ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span class="checkbox-visual" style="border-radius: 50%;"></span>
                        <i class="fa-solid fa-folder-tree" style="color:#8b5cf6; font-size:11px;"></i>
                        <span style="font-size:12px; font-weight:600;">Tablas Maestras</span>
                    </label>

                    <!-- Usuarios -->
                    <?php $chkUsuarios = in_array('usuarios', $modulosSeleccionados); ?>
                    <label class="filter-checkbox-card" style="padding: 8px 14px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="modulos[]" value="usuarios" <?= $chkUsuarios ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span class="checkbox-visual" style="border-radius: 50%;"></span>
                        <i class="fa-solid fa-user-shield" style="color:#ec4899; font-size:11px;"></i>
                        <span style="font-size:12px; font-weight:600;">Usuarios</span>
                    </label>

                    <!-- Bitácora -->
                    <?php $chkBitacora = in_array('inv_bitacora', $modulosSeleccionados); ?>
                    <label class="filter-checkbox-card" style="padding: 8px 14px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="modulos[]" value="inv_bitacora" <?= $chkBitacora ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span class="checkbox-visual" style="border-radius: 50%;"></span>
                        <i class="fa-solid fa-clock-rotate-left" style="color:#64748b; font-size:11px;"></i>
                        <span style="font-size:12px; font-weight:600;">Bitácora</span>
                    </label>

                </div>
            </div>
        </form>

        <!-- Visualización de Resultados o Estados Vacíos -->
        <div style="border-top: 1px solid var(--border-color); padding-top: 20px;">
            <?php if (strlen((string)$q) < 2): ?>
                <!-- Estado Inicial / Sin búsqueda -->
                <div style="text-align:center; padding:50px 20px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                    <div class="animate-float" style="width:80px; height:80px; border-radius:50%; background:rgba(59,130,246,0.06); display:flex; align-items:center; justify-content:center; color:var(--secondary-blue); font-size:32px; margin-bottom:20px;">
                        <i class="fa-solid fa-search"></i>
                    </div>
                    <h3 style="font-size:16px; font-weight:700; margin-bottom:6px; color:var(--text-color);">Buscador General del Puerto</h3>
                    <p style="color:var(--text-muted); font-size:13.5px; max-width:440px; margin:0 auto 20px; line-height:1.5;">
                        Escribe un término con un mínimo de **2 caracteres** para rastrear coincidencias en toda la base de datos portuaria.
                    </p>
                    
                    <!-- Chips de sugerencias -->
                    <div style="display:flex; justify-content:center; gap:8px; flex-wrap:wrap; max-width:550px;">
                        <span style="font-size:11.5px; color:var(--text-muted); width:100%; margin-bottom:4px; font-weight:600;">Sugerencias de búsqueda rápida:</span>
                        <a onclick="lanzarSugerencia('Grúa')" class="suggestion-chip"><i class="fa-solid fa-ship"></i> Grúa</a>
                        <a onclick="lanzarSugerencia('Kalmar')" class="suggestion-chip"><i class="fa-solid fa-copyright"></i> Kalmar</a>
                        <a onclick="lanzarSugerencia('ING-')" class="suggestion-chip"><i class="fa-solid fa-file-invoice"></i> Inflows</a>
                        <a onclick="lanzarSugerencia('EGR-')" class="suggestion-chip"><i class="fa-solid fa-truck-arrow-right"></i> Outflows</a>
                        <a onclick="lanzarSugerencia('admin')" class="suggestion-chip"><i class="fa-solid fa-user-shield"></i> admin</a>
                    </div>
                </div>

            <?php elseif (empty($resultados)): ?>
                <!-- Sin Resultados Coincidentes -->
                <div style="text-align:center; padding:50px 20px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                    <div style="width:70px; height:70px; border-radius:50%; background:rgba(239,68,68,0.06); display:flex; align-items:center; justify-content:center; color:var(--danger); font-size:28px; margin-bottom:18px;">
                        <i class="fa-solid fa-magnifying-glass-minus"></i>
                    </div>
                    <h3 style="font-size:16px; font-weight:700; margin-bottom:6px; color:var(--text-color);">Sin coincidencias halladas</h3>
                    <p style="color:var(--text-muted); font-size:13.5px; max-width:440px; margin:0 auto 16px; line-height:1.5;">
                        No se encontraron registros para "<strong><?= htmlspecialchars((string)$q) ?></strong>" con los filtros delimitados.
                    </p>
                    
                    <div style="background:var(--secondary-bg); border-radius:10px; padding:14px; border:1px solid var(--border-color); text-align:left; max-width:400px; width:100%;">
                        <strong style="font-size:11px; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-muted); display:block; margin-bottom:6px;">Sugerencias de Corrección:</strong>
                        <ul style="margin:0; padding-left:18px; font-size:12.5px; color:var(--text-color); line-height:1.5;">
                            <li>Verifica haber marcado al menos un módulo arriba.</li>
                            <li>Comprueba que la ortografía de los nombres sea correcta.</li>
                            <li>Prueba con palabras más genéricas o códigos correlativos.</li>
                        </ul>
                    </div>
                </div>

            <?php else: ?>
                <!-- Resultados Encontrados -->
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; border-bottom:1px solid var(--border-color); padding-bottom:10px;">
                    <span style="font-weight:700; font-size:13px; color:var(--text-color);">
                        Se encontraron <strong style="color:var(--secondary-blue); font-size:15px;"><?= count($resultados) ?></strong> resultados de búsqueda.
                    </span>
                    <span style="font-size:11.5px; color:var(--text-muted); background:var(--border-color); padding:3px 8px; border-radius:30px; font-weight:600;">
                        Término: "<?= htmlspecialchars((string)$q) ?>"
                    </span>
                </div>

                <!-- Lista de Resultados Dinámicos -->
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach ($resultados as $res): ?>
                        <?php 
                            $bgOpacityColor = $res['color'] . '10'; // 10% opacidad
                        ?>
                        <a href="<?= $res['url'] ?>" class="result-card" style="--module-accent: <?= $res['color'] ?>; --module-bg: <?= $bgOpacityColor ?>;">
                            <!-- Icono de Módulo -->
                            <div class="result-icon-wrapper">
                                <i class="fa-solid <?= $res['icon'] ?>"></i>
                            </div>

                            <!-- Información principal -->
                            <div style="flex:1; min-width:0;">
                                <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
                                    <span style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:<?= $res['color'] ?>; background:<?= $bgOpacityColor ?>; padding:1px 6px; border-radius:4px;">
                                        <?= htmlspecialchars((string)$res['modulo_label']) ?>
                                    </span>
                                    <span style="font-size:10.5px; color:var(--text-muted); font-weight:600;">
                                        ID #<?= $res['id'] ?>
                                    </span>
                                </div>
                                <h4 style="font-size:13.5px; font-weight:700; color:var(--text-color); margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars((string)$res['titulo']) ?>
                                </h4>
                                <p style="font-size:11.5px; color:var(--text-muted); font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars((string)$res['subtitulo']) ?>
                                </p>
                                <?php if (!empty($res['detalle'])): ?>
                                    <p style="font-size:11px; color:var(--text-muted); font-style:italic; margin-top:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                        <?= htmlspecialchars((string)$res['detalle']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <!-- Flecha de redirección -->
                            <div style="color:var(--text-muted); font-size:12px; padding-left:8px; display:flex; align-items:center; gap:4px; font-weight:600; flex-shrink:0;">
                                <span style="font-size:10.5px; opacity:0; transition: opacity 0.2s;" class="go-text">Ir</span>
                                <i class="fa-solid fa-chevron-right" style="transition: transform 0.2s;"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ===== Modal: Crear / Editar Maestro (Modernizado Glassmorphism) ===== -->
<div class="modal-overlay" id="maestro-modal">
    <div class="modal-content <?= $tablaActiva === 'proveedores' ? 'prov-modal' : '' ?>" style="max-width:520px; border-radius:16px;">
        <div class="modal-header" style="border-bottom:1px solid var(--border-color); padding-bottom:16px;">
            <h2 id="maestro-modal-title" style="font-size:18px; font-weight:700; color:var(--text-main);">Nuevo Registro</h2>
            <button class="modal-close" onclick="cerrarModalMaestro()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="index.php?route=inv_maestros&action=guardar" method="POST" autocomplete="off" id="form-maestro">
            <input type="hidden" name="tabla" value="<?= htmlspecialchars($tablaActiva) ?>">
            <input type="hidden" name="tipo_bien" value="<?= htmlspecialchars($tipoBien) ?>">
            <input type="hidden" name="id"    id="mae-inp-id" value="0">
            <div class="modal-body" style="padding-top:20px;">

                <!-- Caso 1: Categorías (Grupo de Productos) -->
                <?php if ($tablaActiva === 'categorias'): ?>
                    <div style="display:grid; grid-template-columns:1fr 2fr; gap:16px;">
                        <div class="form-group">
                            <label>Código Contable</label>
                            <input type="text" name="codigo" id="mae-inp-codigo" required placeholder="Ej: 1.3.1.01.">
                        </div>
                        <div class="form-group">
                            <label>Nombre del Grupo</label>
                            <input type="text" name="nombre" id="mae-inp-nombre" required placeholder="Ej: Combustibles y Lubricantes">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Descripción / Detalle Adicional</label>
                        <input type="text" name="extra" id="mae-inp-extra" placeholder="Detalle contable o clasificación general...">
                    </div>

                <!-- Caso 2: Grupo de Centros de Consumo -->
                <?php elseif ($tablaActiva === 'grupo_centros_consumo'): ?>
                    <div style="display:grid; grid-template-columns:1fr 2.5fr; gap:16px;">
                        <div class="form-group">
                            <label>Código</label>
                            <input type="text" name="codigo" id="mae-inp-codigo" required placeholder="Ej: 04">
                        </div>
                        <div class="form-group">
                            <label>Grupo de Consumo (Departamento)</label>
                            <input type="text" name="nombre" id="mae-inp-nombre" required placeholder="Ej: CASA MILITAR PRESIDENCIAL">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Funcionario Representante</label>
                        <select name="representante_id" id="mae-inp-representante-id" required>
                            <option value="">Seleccionar funcionario de Talento Humano...</option>
                            <?php foreach ($personalList as $persona): ?>
                                <option value="<?= (int)$persona['id'] ?>"><?= htmlspecialchars($persona['nombre']) ?><?= !empty($persona['area_actual']) ? ' (' . htmlspecialchars($persona['area_actual']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color:var(--text-muted);">Se muestran solamente funcionarios activos.</small>
                    </div>

                <!-- Caso 3: Catálogo de Productos -->
                <?php elseif ($tablaActiva === 'productos'): ?>
                    <div class="form-group">
                        <label>Nombre del Producto</label>
                        <input type="text" name="nombre" id="mae-inp-nombre" required placeholder="Ej: Aceite para Motores Hidráulicos">
                    </div>
                    <div class="form-group">
                        <label>Grupo de Productos (Categoría)</label>
                        <select name="grupo_id" id="mae-inp-grupo" required>
                            <option value="">Seleccionar grupo...</option>
                            <?php foreach ($categoriasList as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['codigo'] ?? '') ?> <?= htmlspecialchars($cat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label>Unidad de Medida</label>
                            <select name="unidad_id" id="mae-inp-unidad" required>
                                <option value="">Seleccionar unidad...</option>
                                <?php foreach ($unidadesList as $uni): ?>
                                    <option value="<?= $uni['id'] ?>"><?= htmlspecialchars($uni['nombre']) ?> (<?= htmlspecialchars($uni['extra'] ?? '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tasa del IVA</label>
                            <select name="aplica_iva" id="mae-inp-iva" required>
                                <?php foreach ($tiposIvaList as $tipo): ?>
                                    <option value="<?= $tipo['id'] ?>"><?= htmlspecialchars($tipo['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                <!-- Caso 4: Centros de Consumo -->
                <?php elseif ($tablaActiva === 'centros_consumo'): ?>
                    <div class="form-group">
                        <label>Grupo de Centros de Consumo (Área General)</label>
                        <select name="grupo_id" id="mae-inp-grupo" required>
                            <option value="">Seleccionar grupo organizativo...</option>
                            <?php foreach ($grupoCentrosList as $gc): ?>
                                <option value="<?= $gc['id'] ?>"><?= htmlspecialchars($gc['codigo']) ?> - <?= htmlspecialchars($gc['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 2.5fr; gap:16px;">
                        <div class="form-group">
                            <label>Código Centro</label>
                            <input type="text" name="codigo" id="mae-inp-codigo" required placeholder="Ej: 0002">
                        </div>
                        <div class="form-group">
                            <label>Puesto / Descripción del Centro</label>
                            <input type="text" name="nombre" id="mae-inp-nombre" required placeholder="Ej: SEGURIDAD INMEDIATA PRESIDENCIAL">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Funcionario Responsable</label>
                        <select name="funcionario_id" id="mae-inp-funcionario-id" required>
                            <option value="">Seleccionar funcionario de Talento Humano...</option>
                            <?php foreach ($personalList as $persona): ?>
                                <option value="<?= (int)$persona['id'] ?>"><?= htmlspecialchars($persona['nombre']) ?><?= !empty($persona['area_actual']) ? ' (' . htmlspecialchars($persona['area_actual']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color:var(--text-muted);">El responsable se guarda con su ID oficial.</small>
                    </div>

                <!-- Caso 5: Proveedores -->
                <?php elseif ($tablaActiva === 'proveedores'): ?>
                    <div class="prov-intro"><i class="fa-solid fa-building-circle-check"></i><div><strong>Ficha centralizada del proveedor</strong><span>La información registrada aquí se reutiliza en órdenes de compra, facturas e ingresos a bodega.</span></div></div>
                    <section class="prov-section"><h3 class="prov-section-title"><i class="fa-solid fa-id-card"></i> Identificación</h3><div class="prov-grid">
                        <input type="hidden" name="codigo" id="mae-inp-codigo">
                        <div class="form-group prov-col-4"><label>RUC / cédula</label><div style="display:flex;gap:8px"><input type="text" name="ruc" id="mae-inp-ruc" required placeholder="1391234567001" maxlength="13" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'')"><button type="button" class="btn-primary" id="btn-buscar-ruc" onclick="ejecutarBusquedaRuc()" title="Consultar identificación"><i class="fa-solid fa-magnifying-glass"></i></button></div><small id="lookup-indicator" style="display:block;margin-top:4px;font-size:11px;color:var(--text-muted)"></small></div>
                        <div class="form-group prov-col-8"><label>Razón social / nombre</label><input type="text" name="nombre" id="mae-inp-nombre" required maxlength="255" placeholder="Ej: Importadora Andina S.A."></div>
                        <div class="form-group prov-col-12"><label>Representante o contacto principal</label><input type="text" name="representante" id="mae-inp-representante" maxlength="255" placeholder="Nombre de la persona de contacto"></div>
                    </div></section>
                    <section class="prov-section"><h3 class="prov-section-title"><i class="fa-solid fa-location-dot"></i> Ubicación</h3><div class="prov-grid">
                        <div class="form-group prov-col-8"><label>Dirección</label><input type="text" name="direccion" id="mae-inp-direccion" maxlength="500" placeholder="Calle principal, numeración y sector"></div>
                        <div class="form-group prov-col-4"><label>Ciudad</label><input type="text" name="ciudad" id="mae-inp-ciudad" maxlength="150" placeholder="Ej: Manta"></div>
                        <div class="form-group prov-col-12"><label>Referencia</label><input type="text" name="referencia" id="mae-inp-referencia" maxlength="500" placeholder="Punto de referencia o instrucciones de entrega"></div>
                    </div></section>
                    <section class="prov-section"><h3 class="prov-section-title"><i class="fa-solid fa-address-book"></i> Canales de contacto</h3><div class="prov-grid">
                        <div class="form-group prov-col-3"><label>Teléfono principal</label><input type="tel" name="telefono1" id="mae-inp-telefono1" maxlength="50" placeholder="05 260 0000"></div>
                        <div class="form-group prov-col-3"><label>Teléfono alterno</label><input type="tel" name="telefono2" id="mae-inp-telefono2" maxlength="50" placeholder="09 9000 0000"></div>
                        <div class="form-group prov-col-3"><label>Fax</label><input type="tel" name="fax" id="mae-inp-fax" maxlength="50" placeholder="Opcional"></div>
                        <div class="form-group prov-col-3"><label>Correo electrónico</label><input type="email" name="email" id="mae-inp-email" maxlength="180" placeholder="ventas@proveedor.com"></div>
                    </div></section>
                    <input type="hidden" name="extra" id="mae-inp-extra">

                <!-- Caso 6: Unidades de Medida -->
                <?php elseif ($tablaActiva === 'unidades'): ?>
                    <div class="form-group">
                        <label>Nombre de la Unidad de Medida</label>
                        <input type="text" name="nombre" id="mae-inp-nombre" required placeholder="Ej: Litros">
                    </div>
                    <div class="form-group">
                        <label>Abreviatura Oficial</label>
                        <input type="text" name="extra" id="mae-inp-extra" required placeholder="Ej: L, gl, kg, und...">
                    </div>

                <!-- Caso 7: Tasas de IVA -->
                <?php elseif ($tablaActiva === 'tipos_iva'): ?>
                    <div class="form-group">
                        <label>Descripción / Título impositivo</label>
                        <input type="text" name="nombre" id="mae-inp-nombre" required placeholder="Ej: IVA 15% (General)">
                    </div>
                    <div class="form-group">
                        <label>Tasa impositiva (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="tasa_iva" id="mae-inp-tasa" required placeholder="Ej: 15.00">
                    </div>
                <?php endif; ?>

            </div>
            <div class="modal-footer" style="border-top:1px solid var(--border-color); padding-top:16px; margin-top:20px;">
                <button type="button" class="btn-outline" onclick="cerrarModalMaestro()">Cancelar</button>
                <button type="submit" class="btn-primary"><i class="fa-solid fa-save"></i> Guardar Registro</button>
            </div>
        </form>
    </div>
</div>

<script>
    const tablaActiva = <?= json_encode($tablaActiva) ?>;
    const tipoBienActivo = <?= json_encode($tipoBien) ?>;
    let tablaMaestrosDT = null;

    function escaparHtmlMaestro(valor) {
        const div = document.createElement('div');
        div.textContent = valor == null || valor === '' ? '—' : String(valor);
        return div.innerHTML;
    }

    function mostrarToastMaestro(mensaje, tipo) {
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + (tipo === 'error' ? 'inv_error' : tipo) + ' show';
        toast.innerHTML = '<i class="fa-solid ' + (tipo === 'success' ? 'fa-check-circle' : 'fa-circle-exclamation') + '"></i><span></span>';
        toast.querySelector('span').textContent = mensaje;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 3500);
    }

    function registroMaestroCoincide(item) {
        if (tablaActiva === 'categorias') {
            const prefijo = tipoBienActivo === 'AF' ? '1.4.' : '1.3.';
            if (String(item.codigo || '').indexOf(prefijo) !== 0) return false;
        }
        if (tablaActiva === 'productos' && String(item.tipo_bien || 'CC') !== tipoBienActivo) return false;

        const buscador = document.querySelector('[name="buscar_maestro"]') || document.getElementById('buscador-individual');
        const termino = buscador ? buscador.value.toLowerCase().trim() : '';
        if (!termino) return true;
        return [item.id, item.codigo, item.nombre, item.extra, item.ruc, item.representante, item.direccion,
            item.ciudad, item.email, item.telefono1, item.telefono2, item.fax, item.referencia,
            item.grupo_nombre, item.unidad_nombre]
            .join(' ').toLowerCase().indexOf(termino) !== -1;
    }

    function contenidoFilaMaestro(item) {
        let html = '<td><strong>#' + escaparHtmlMaestro(item.id) + '</strong></td>';
        if (tablaActiva === 'categorias') {
            html += '<td><code>' + escaparHtmlMaestro(item.codigo) + '</code></td>'
                + '<td style="font-weight:600;">' + escaparHtmlMaestro(item.nombre) + '</td>'
                + '<td>' + escaparHtmlMaestro(item.extra) + '</td>';
        } else if (tablaActiva === 'productos') {
            const ivaSelect = document.getElementById('mae-inp-iva');
            let ivaTexto = item.aplica_iva == 1 ? 'Sí aplica' : 'No aplica';
            if (ivaSelect) {
                const opcion = Array.from(ivaSelect.options).find(opt => String(opt.value) === String(item.aplica_iva));
                if (opcion) ivaTexto = opcion.textContent.trim();
            }
            html += '<td><code>' + escaparHtmlMaestro(item.codigo) + '</code></td>'
                + '<td style="font-weight:600;">' + escaparHtmlMaestro(item.nombre) + '</td>'
                + '<td><span class="status-badge transit">' + escaparHtmlMaestro(item.grupo_nombre) + '</span></td>'
                + '<td><code>' + escaparHtmlMaestro(item.unidad_nombre) + '</code></td>'
                + '<td><span class="status-badge active">' + escaparHtmlMaestro(ivaTexto) + '</span></td>';
        } else if (tablaActiva === 'proveedores') {
            html += '<td><code>' + escaparHtmlMaestro(item.ruc) + '</code></td>'
                + '<td><span class="prov-card-title"><strong>' + escaparHtmlMaestro(item.nombre) + '</strong></span><span class="prov-meta">' + escaparHtmlMaestro(item.representante || 'Sin representante registrado') + '</span></td>'
                + '<td><span class="prov-contact">' + escaparHtmlMaestro(item.telefono1 || item.email || 'Sin contacto') + '</span><span class="prov-meta">' + escaparHtmlMaestro([item.ciudad,item.direccion].filter(Boolean).join(' · ') || 'Sin ubicación') + '</span></td>';
        } else if (tablaActiva === 'unidades') {
            html += '<td style="font-weight:600;">' + escaparHtmlMaestro(item.nombre) + '</td>'
                + '<td><code>' + escaparHtmlMaestro(item.extra) + '</code></td>';
        } else if (tablaActiva === 'tipos_iva') {
            html += '<td style="font-weight:600;">' + escaparHtmlMaestro(item.nombre) + '</td>'
                + '<td><strong>' + Number(item.tasa_iva || 0).toFixed(2) + '%</strong></td>';
        }
        return html + '<td class="acciones-cell columna-acciones"><button type="button" class="btn-accion btn-editar fila-editar-maestro" title="Editar"><i class="fa-solid fa-pen"></i></button></td>';
    }

    function actualizarFilaMaestro(item) {
        const tbody = document.getElementById('tbody-datos-inv_maestros');
        const selectorFila = '#row-' + item.id;
        let fila = tablaMaestrosDT ? tablaMaestrosDT.row(selectorFila).node() : document.getElementById('row-' + item.id);

        if (!registroMaestroCoincide(item)) {
            if (fila && tablaMaestrosDT) tablaMaestrosDT.row(fila).remove().draw(false);
            else if (fila) fila.remove();
            return null;
        }

        if (!fila) {
            fila = document.createElement('tr');
            fila.id = 'row-' + item.id;
        }

        fila.innerHTML = contenidoFilaMaestro(item);
        fila.querySelector('.fila-editar-maestro').addEventListener('click', () => editarMaestro(item));
        if (tablaMaestrosDT) {
            const filaApi = tablaMaestrosDT.row(selectorFila);
            if (filaApi.any()) filaApi.invalidate('dom').draw(false);
            else tablaMaestrosDT.row.add(fila).draw(false);
        } else if (!fila.isConnected) {
            tbody.prepend(fila);
        }
        return fila;
    }

    function guardarMaestroSinRecargar(event) {
        event.preventDefault();
        const form = event.currentTarget;
        if (!form.reportValidity()) return;
        const boton = form.querySelector('[type="submit"]');
        boton.disabled = true;

        const datos = new FormData(form);
        datos.append('is_ajax', '1');
        fetch(form.action, {
            method: 'POST', body: datos, credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(respuesta => respuesta.json().then(data => {
            if (!respuesta.ok || !data.success) throw new Error(data.mensaje || 'No fue posible guardar el maestro.');
            return data;
        })).then(data => {
            const fila = actualizarFilaMaestro(data.registro);
            cerrarModalMaestro();
            if (fila) {
                const filtroLocal = document.getElementById('buscador-individual');
                if (filtroLocal) filtrarTablaIndividual(filtroLocal.value);
                fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
                fila.classList.remove('highlighted-row');
                void fila.offsetWidth;
                fila.classList.add('highlighted-row');
                setTimeout(() => fila.classList.remove('highlighted-row'), 4000);
            }
            mostrarToastMaestro(data.mensaje + ' sin recargar la lista.', 'success');
        }).catch(error => {
            mostrarToastMaestro(error.message || 'Error al guardar el maestro.', 'error');
        }).finally(() => {
            boton.disabled = false;
        });
    }

    function abrirModalMaestro() {
        document.getElementById('maestro-modal-title').textContent = tablaActiva === 'proveedores' ? 'Nuevo proveedor' : 'Nuevo Registro';
        document.getElementById('mae-inp-id').value = '0';
        document.getElementById('mae-inp-nombre').value = '';

        var extra = document.getElementById('mae-inp-extra');
        if (extra) extra.value = '';
        var codigo = document.getElementById('mae-inp-codigo');
        if (codigo) codigo.value = '';
        var representante = document.getElementById('mae-inp-representante-id');
        if (representante) representante.value = '';
        var funcionario = document.getElementById('mae-inp-funcionario-id');
        if (funcionario) funcionario.value = '';
        var ruc = document.getElementById('mae-inp-ruc');
        if (ruc) ruc.value = '';
        ['representante','direccion','ciudad','email','telefono1','telefono2','fax','referencia'].forEach(function(campo){
            var input = document.getElementById('mae-inp-' + campo);
            if (input) input.value = '';
        });
        var grupo = document.getElementById('mae-inp-grupo');
        if (grupo) grupo.value = '';
        var unidad = document.getElementById('mae-inp-unidad');
        if (unidad) unidad.value = '';
        var iva = document.getElementById('mae-inp-iva');
        if (iva) iva.value = '1';
        var tasa = document.getElementById('mae-inp-tasa');
        if (tasa) tasa.value = '';

        var indicator = document.getElementById('lookup-indicator');
        if (indicator) indicator.innerHTML = '';

        document.getElementById('maestro-modal').classList.add('active');
    }

    function cerrarModalMaestro() {
        document.getElementById('maestro-modal').classList.remove('active');
    }

    function ejecutarBusquedaRuc() {
        const rucInput = document.getElementById('mae-inp-ruc');
        const nombreInput = document.getElementById('mae-inp-nombre');
        const extraInput = document.getElementById('mae-inp-extra');
        const direccionInput = document.getElementById('mae-inp-direccion');
        const indicator = document.getElementById('lookup-indicator');

        if (!rucInput) return;
        const ruc = rucInput.value.trim();

        if (ruc.length !== 10 && ruc.length !== 13) {
            alert('Por favor ingrese una identificación válida (10 dígitos para cédula o 13 dígitos para RUC).');
            return;
        }

        indicator.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Consultando en cascada (Paso 1: Local)...';
        indicator.style.color = 'var(--text-muted)';

        const tipo = (ruc.length === 13) ? 'empresa' : 'persona';

        fetch('index.php?route=inv_lookup&ruc=' + encodeURIComponent(ruc) + '&tipo=' + encodeURIComponent(tipo))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (nombreInput) nombreInput.value = data.nombre;
                    if (extraInput && data.extra) {
                        extraInput.value = data.extra;
                    }
                    if (direccionInput && data.extra && !direccionInput.value) direccionInput.value = data.extra;
                    indicator.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + data.nombre + ' | Obtenido de: <strong>' + data.origen + '</strong>';
                    indicator.style.color = '#10b981';
                } else {
                    indicator.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + data.mensaje;
                    indicator.style.color = 'var(--danger)';
                }
            })
            .catch(err => {
                indicator.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Error de conexión con el servicio de búsqueda';
                indicator.style.color = 'var(--danger)';
            });
    }

    function editarMaestro(item) {
        document.getElementById('maestro-modal-title').textContent = tablaActiva === 'proveedores' ? 'Editar proveedor' : 'Editar Registro';
        document.getElementById('mae-inp-id').value = item.id;
        document.getElementById('mae-inp-nombre').value = item.nombre || '';

        var extra = document.getElementById('mae-inp-extra');
        if (extra) extra.value = item.extra || '';
        var codigo = document.getElementById('mae-inp-codigo');
        if (codigo) codigo.value = item.codigo || '';
        var representante = document.getElementById('mae-inp-representante-id');
        if (representante) representante.value = item.representante_id || '';
        var funcionario = document.getElementById('mae-inp-funcionario-id');
        if (funcionario) funcionario.value = item.funcionario_id || '';
        var ruc = document.getElementById('mae-inp-ruc');
        if (ruc) ruc.value = item.ruc || '';
        ['representante','direccion','ciudad','email','telefono1','telefono2','fax','referencia'].forEach(function(campo){
            var input = document.getElementById('mae-inp-' + campo);
            if (input) input.value = item[campo] || '';
        });
        
        var grupo = document.getElementById('mae-inp-grupo');
        if (grupo && item.grupo_id) grupo.value = item.grupo_id;
        var unidad = document.getElementById('mae-inp-unidad');
        if (unidad && item.unidad_id) unidad.value = item.unidad_id;
        var iva = document.getElementById('mae-inp-iva');
        if (iva && item.aplica_iva !== undefined) iva.value = item.aplica_iva;
        var tasa = document.getElementById('mae-inp-tasa');
        if (tasa && item.tasa_iva !== undefined) tasa.value = item.tasa_iva;

        var indicator = document.getElementById('lookup-indicator');
        if (indicator) indicator.innerHTML = '';

        document.getElementById('maestro-modal').classList.add('active');
    }

    /* ========== FILTRADO Y BÚSQUEDA DUAL (INDIVIDUAL Y GLOBAL) ========== */

    // 1. Filtrado Individual en Tiempo Real (Cliente)
    function filtrarTablaIndividual(val) {
        if (tablaMaestrosDT) {
            tablaMaestrosDT.search(val).draw();
            return;
        }
        const query = val.toLowerCase().trim();
        const tbody = document.getElementById('tbody-datos-inv_maestros');
        if (!tbody) return;
        
        const rows = tbody.getElementsByTagName('tr');
        let visibleCount = 0;
        let totalCount = 0;
        
        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            if (row.id === 'no-results-search-row' || (row.cells.length <= 1 && row.querySelector('td').getAttribute('colspan'))) {
                continue; // Saltar fila de "Sin registros" o de "No resultados"
            }
            
            totalCount++;
            let textMatch = false;
            
            // Buscar coincidencia en todas las celdas de texto (excepto la columna de acciones)
            for (let j = 0; j < row.cells.length - 1; j++) {
                const cellText = row.cells[j].innerText.toLowerCase();
                if (cellText.includes(query)) {
                    textMatch = true;
                    break;
                }
            }
            
            if (textMatch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        }
        
        // Actualizar el contador en la interfaz
        const countBadge = document.getElementById('buscador-individual-count');
        if (countBadge) {
            if (query.length > 0) {
                countBadge.textContent = visibleCount;
                countBadge.style.display = 'inline-block';
                if (visibleCount === 0) {
                    countBadge.style.background = 'var(--danger)';
                } else {
                    countBadge.style.background = 'var(--primary-blue)';
                }
            } else {
                countBadge.style.display = 'none';
            }
        }
        
        // Fila de estado vacío para el buscador individual
        let noResultsRow = document.getElementById('no-results-search-row');
        if (visibleCount === 0 && totalCount > 0 && query.length > 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.id = 'no-results-search-row';
                const colspan = rows[0] ? rows[0].cells.length : 5;
                noResultsRow.innerHTML = `
                    <td colspan="${colspan}" style="text-align:center; padding:40px; color:var(--text-muted);">
                        <i class="fa-solid fa-magnifying-glass" style="font-size:24px; display:block; margin-bottom:12px; opacity:0.3;"></i>
                        No se encontraron coincidencias locales para "<strong>${val}</strong>"
                    </td>
                `;
                tbody.appendChild(noResultsRow);
            }
        } else {
            if (noResultsRow) {
                noResultsRow.remove();
            }
        }
    }

    // 2. Búsqueda Global Dedicada con Sugerencias y Hovers
    function lanzarSugerencia(termino) {
        const searchInput = document.getElementById('main-global-search-input');
        if (searchInput) {
            searchInput.value = termino;
            searchInput.focus();
            searchInput.form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const formMaestro = document.getElementById('form-maestro');
        if (formMaestro) formMaestro.addEventListener('submit', guardarMaestroSinRecargar);

        if (window.jQuery && $.fn.DataTable && document.getElementById('tabla-maestros')) {
            const tieneBotones = !!($.fn.dataTable && $.fn.dataTable.Buttons);
            const tieneResponsive = !!($.fn.dataTable && $.fn.dataTable.Responsive);
            const paginacionLocal = tablaActiva === 'centros_consumo' || tablaActiva === 'productos';
            const buscadorDataTable = tablaActiva === 'productos' ? 'f' : '';
            const tituloExportacion = 'Maestros - ' + document.querySelector('.panel-header h3').innerText.trim();
            tablaMaestrosDT = $('#tabla-maestros').DataTable({
                dom: (tieneBotones ? '<"dt-maestros-buttons"B>' : '') + buscadorDataTable + 't' + (paginacionLocal ? '<"dt-maestros-footer"lip>' : ''),
                responsive: tieneResponsive ? { details: { type: 'inline', target: 'tr' } } : false,
                autoWidth: false,
                paging: paginacionLocal,
                pageLength: 50,
                searching: true,
                info: paginacionLocal,
                order: [],
                columnDefs: [
                    { targets: -1, orderable: false, searchable: false, className: 'columna-acciones', responsivePriority: 1 },
                    { targets: 0, responsivePriority: 4 },
                    { targets: 1, responsivePriority: 2 },
                    { targets: 2, responsivePriority: 1 }
                ],
                buttons: tieneBotones ? (tablaActiva === 'productos' ? [
                    { text: '<i class="fa-solid fa-file-excel"></i> Excel completo', action: function () { exportarCatalogoMaestro('completo'); } },
                    { text: '<i class="fa-regular fa-file-excel"></i> Excel resumido', action: function () { exportarCatalogoMaestro('resumido'); } },
                    { extend: 'print', text: '<i class="fa-solid fa-print"></i> Imprimir', title: tituloExportacion, exportOptions: { columns: ':not(.columna-acciones)' } }
                ] : [
                    { extend: 'print', text: '<i class="fa-solid fa-print"></i> Imprimir', title: tituloExportacion, exportOptions: { columns: ':not(.columna-acciones)' } }
                ]) : [],
                language: {
                    search: 'Buscar productos:',
                    zeroRecords: 'No se encontraron coincidencias',
                    emptyTable: 'No hay registros en este maestro',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ funcionarios',
                    infoEmpty: 'No hay funcionarios para mostrar',
                    paginate: { previous: 'Anterior', next: 'Siguiente' }
                }
            });
            if (tieneBotones) tablaMaestrosDT.buttons().container().appendTo('#maestro-export-actions');
            if (tablaActiva === 'productos') {
                const filtroDataTable = document.getElementById('tabla-maestros_filter');
                const zonaBusqueda = document.querySelector('.maestro-table-search');
                if (filtroDataTable && zonaBusqueda) zonaBusqueda.appendChild(filtroDataTable);
                const entradaDataTable = filtroDataTable ? filtroDataTable.querySelector('input') : null;
                if (entradaDataTable) entradaDataTable.placeholder = 'Código, nombre, grupo o unidad…';
            }
            const filtroInicial = document.getElementById('buscador-individual');
            if (filtroInicial && filtroInicial.value.trim()) tablaMaestrosDT.search(filtroInicial.value).draw();
        }
        // Efecto hover sutil al pasar el mouse por las tarjetas de resultado
        const cards = document.querySelectorAll('.result-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                const text = card.querySelector('.go-text');
                const arrow = card.querySelector('.fa-chevron-right');
                if (text) text.style.opacity = '1';
                if (arrow) arrow.style.transform = 'translateX(4px)';
            });
            card.addEventListener('mouseleave', () => {
                const text = card.querySelector('.go-text');
                const arrow = card.querySelector('.fa-chevron-right');
                if (text) text.style.opacity = '0';
                if (arrow) arrow.style.transform = 'translateX(0)';
            });
        });

        // 3. Auto-resaltado y Scroll al cargar fila indicada (redirigidos con highlight)
        const urlParams = new URLSearchParams(window.location.search);
        const highlightId = urlParams.get('highlight');
        if (highlightId) {
            setTimeout(() => {
                const row = document.getElementById(`row-${highlightId}`);
                if (row) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.classList.add('highlighted-row');
                    
                    // Quitar iluminación después de unos segundos
                    setTimeout(() => {
                        row.classList.remove('highlighted-row');
                    }, 4000);
                }
            }, 300);
        }
    });

    function exportarCatalogoMaestro(modo) {
        const buscador = document.querySelector('[name="buscar_maestro"]') || document.getElementById('buscador-individual');
        const params = new URLSearchParams({
            route: 'inv_maestros',
            action: 'exportarProductosExcel',
            tipo: tipoBienActivo,
            modo: modo,
            buscar: buscador ? buscador.value.trim() : (tablaMaestrosDT ? tablaMaestrosDT.search() : '')
        });
        window.location.href = 'index.php?' + params.toString();
    }
</script>
<?php if ($tablaActiva === 'proveedores' && !empty($_GET['nuevo']) && !$soloLectura): ?>
<script>
window.addEventListener('load',function(){
    if(typeof abrirModalMaestro==='function') abrirModalMaestro();
    var params = new URLSearchParams(window.location.search);
    var nombre = document.getElementById('mae-inp-nombre');
    var ruc = document.getElementById('mae-inp-ruc');
    if (nombre && params.get('nombre')) nombre.value = params.get('nombre');
    if (ruc && params.get('ruc')) ruc.value = params.get('ruc').replace(/\D/g, '').slice(0, 13);
});
</script>
<?php endif; ?>
