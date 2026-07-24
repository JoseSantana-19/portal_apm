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
?>

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
    <?php if ($tablaActiva !== 'busqueda_global'): ?>
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
                <span style="background:rgba(255,255,255,0.2);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;"><?= isset($conteos['categorias']) ? $conteos['categorias'] : 0 ?></span>
            </a>

            <!-- Grupo de Centros de Consumo -->
            <a href="index.php?route=inv_maestros&tabla=grupo_centros_consumo"
               class="filter-tab <?= ($tablaActiva === 'grupo_centros_consumo') ? 'active' : '' ?>"
               style="text-align:left;display:flex;align-items:center;text-decoration:none;padding:12px 16px;border-radius:10px;gap:10px;">
                <i class="fa-solid fa-sitemap" style="width:16px;color:<?= ($tablaActiva === 'grupo_centros_consumo') ? 'white' : 'var(--text-muted)' ?>;"></i>
                <span style="flex:1;">2. Gr. Centros Consumo</span>
                <span style="background:rgba(255,255,255,0.2);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;"><?= isset($conteos['grupo_centros_consumo']) ? $conteos['grupo_centros_consumo'] : 0 ?></span>
            </a>

            <!-- Productos -->
            <a href="index.php?route=inv_maestros&tabla=productos"
               class="filter-tab <?= ($tablaActiva === 'productos') ? 'active' : '' ?>"
               style="text-align:left;display:flex;align-items:center;text-decoration:none;padding:12px 16px;border-radius:10px;gap:10px;">
                <i class="fa-solid fa-box" style="width:16px;color:<?= ($tablaActiva === 'productos') ? 'white' : 'var(--text-muted)' ?>;"></i>
                <span style="flex:1;">3. Catálogo Productos</span>
                <span style="background:rgba(255,255,255,0.2);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;"><?= isset($conteos['productos']) ? $conteos['productos'] : 0 ?></span>
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
                'grupo_centros_consumo' => ['icono' => 'fa-sitemap', 'color' => '#8b5cf6', 'titulo' => 'Gr. Centros de Consumo',  'desc' => 'Grupos organizativos o departamentos generales del puerto.'],
                'productos'  => ['icono' => 'fa-box',                'color' => '#10b981', 'titulo' => 'Catálogo de Productos', 'desc' => 'Ítems vinculados a un grupo de productos, unidad y tributación.'],
                'centros_consumo' => ['icono' => 'fa-building-flag','color' => '#ec4899', 'titulo' => 'Centros de Consumo',      'desc' => 'Oficinas/Puestos de trabajo vinculados a un departamento con un funcionario a cargo.'],
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
        <div class="panel-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
            <h3 style="margin:0; flex:1; min-width:250px;">
                <?php
                $titulos = [
                    'categorias' => '<i class="fa-solid fa-tags" style="margin-right:8px;color:#3b82f6;"></i> Grupo de Productos (Categorías)',
                    'grupo_centros_consumo' => '<i class="fa-solid fa-sitemap" style="margin-right:8px;color:#8b5cf6;"></i> Grupo de Centros de Consumo',
                    'productos'  => '<i class="fa-solid fa-box" style="margin-right:8px;color:#10b981;"></i> Catálogo de Productos',
                    'centros_consumo' => '<i class="fa-solid fa-building-flag" style="margin-right:8px;color:#ec4899;"></i> Centros de Consumo',
                    'proveedores' => '<i class="fa-solid fa-truck-field" style="margin-right:8px;color:#f59e0b;"></i> Proveedores',
                    'unidades'   => '<i class="fa-solid fa-calculator" style="margin-right:8px;color:#06b6d4;"></i> Unidades de Medida',
                    'tipos_iva'  => '<i class="fa-solid fa-file-invoice-dollar" style="margin-right:8px;color:#ef4444;"></i> Tasas de IVA impositivas',
                ];
                echo isset($titulos[$tablaActiva]) ? $titulos[$tablaActiva] : $tablaActiva;
                ?>
            </h3>
            <!-- Buscador Individual (Filtro local en tiempo real) -->
            <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <div style="position:relative; min-width:240px;">
                    <i class="fa-solid fa-filter" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--text-muted); font-size:12px;"></i>
                    <input type="text" id="buscador-individual" placeholder="Filtrar esta tabla..." 
                           style="padding: 8px 12px 8px 32px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--secondary-bg); color: var(--text-color); font-size: 13px; width: 100%; transition: all 0.3s;"
                           oninput="filtrarTablaIndividual(this.value)">
                    <span id="buscador-individual-count" style="position:absolute; right:12px; top:50%; transform:translateY(-50%); font-size:11px; font-weight:600; color:white; background:var(--primary-blue); padding:1px 6px; border-radius:10px; display:none;">0</span>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <?php if ($tablaActiva === 'categorias'): ?>
                            <th style="width:140px;">Código</th>
                            <th>Nombre del Grupo</th>
                            <th>Detalle / Descripción</th>
                        <?php elseif ($tablaActiva === 'grupo_centros_consumo'): ?>
                            <th style="width:100px;">Código</th>
                            <th>Nombre del Departamento</th>
                            <th>Funcionario Representante</th>
                        <?php elseif ($tablaActiva === 'productos'): ?>
                            <th style="width:110px;">Código</th>
                            <th>Nombre del Producto</th>
                            <th>Grupo / Categoría</th>
                            <th>Unidad de Medida</th>
                            <th>IVA</th>
                        <?php elseif ($tablaActiva === 'centros_consumo'): ?>
                            <th style="width:100px;">Código</th>
                            <th>Puesto / Descripción del Centro</th>
                            <th>Funcionario a Cargo</th>
                            <th>Grupo Organizativo</th>
                        <?php elseif ($tablaActiva === 'proveedores'): ?>
                            <th>Razón Social / Nombre</th>
                            <th style="width:160px;">RUC / Identificación</th>
                            <th>Contacto y Dirección</th>
                        <?php elseif ($tablaActiva === 'unidades'): ?>
                            <th>Nombre de la Unidad</th>
                            <th>Abreviatura</th>
                        <?php elseif ($tablaActiva === 'tipos_iva'): ?>
                            <th>Descripción</th>
                            <th>Tasa (%)</th>
                        <?php endif; ?>
                        <th style="width:90px; text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-datos-inv_maestros">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="<?= $tablaActiva === 'productos' || $tablaActiva === 'centros_consumo' ? 6 : 5 ?>" style="text-align:center;padding:52px;color:var(--text-muted);">
                                <i class="fa-solid fa-inbox" style="font-size:40px;display:block;margin-bottom:14px;opacity:0.25;"></i>
                                <strong style="display:block;margin-bottom:6px;">Sin registros en la tabla</strong>
                                <span style="font-size:13px;">Usa el botón <strong>"Agregar Registro"</strong> para ingresar datos.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr id="row-<?= $item['id'] ?>" class="animate-fade-in">
                                <td><strong>#<?= $item['id'] ?></strong></td>
                                <?php if ($tablaActiva === 'categorias'): ?>
                                    <td><code style="background:var(--border-color);padding:3px 8px;border-radius:5px;font-weight:700;font-size:12px;color:var(--primary);"><?= htmlspecialchars($item['codigo'] ?? '—') ?></code></td>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td style="color:var(--text-muted);font-size:13px;"><?= !empty($item['extra']) ? htmlspecialchars($item['extra']) : '<em>—</em>' ?></td>
                                <?php elseif ($tablaActiva === 'grupo_centros_consumo'): ?>
                                    <td><code style="background:var(--border-color);padding:3px 8px;border-radius:5px;font-weight:700;font-size:12px;"><?= htmlspecialchars($item['codigo'] ?? '—') ?></code></td>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><strong><?= htmlspecialchars($item['representante'] ?? '—') ?></strong></td>
                                <?php elseif ($tablaActiva === 'productos'): ?>
                                    <td><code style="background:var(--border-color);padding:3px 8px;border-radius:5px;font-weight:700;font-size:12px;"><?= htmlspecialchars($item['codigo'] ?? '—') ?></code></td>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><span class="status-badge transit" style="font-size: 11px;"><?= htmlspecialchars($item['grupo_nombre']) ?></span></td>
                                    <td><code style="background:var(--secondary-bg);padding:3px 6px;border-radius:4px;font-size:11px;font-weight:600;"><?= htmlspecialchars($item['unidad_nombre']) ?></code></td>
                                    <td>
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
                                    </td>
                                <?php elseif ($tablaActiva === 'centros_consumo'): ?>
                                    <td><code style="background:var(--border-color);padding:3px 8px;border-radius:5px;font-weight:700;font-size:12px;"><?= htmlspecialchars($item['codigo'] ?? '—') ?></code></td>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><strong><?= htmlspecialchars($item['funcionario'] ?? '—') ?></strong></td>
                                    <td><span class="status-badge active" style="background:rgba(139,92,246,0.1);color:#8b5cf6;border-color:rgba(139,92,246,0.2);"><?= htmlspecialchars($item['grupo_nombre'] ?? '—') ?></span></td>
                                <?php elseif ($tablaActiva === 'proveedores'): ?>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><code style="background:var(--border-color);padding:4px 8px;border-radius:5px;font-weight:700;"><?= htmlspecialchars($item['ruc']) ?></code></td>
                                    <td style="color:var(--text-muted);font-size:13px;"><?= !empty($item['extra']) ? htmlspecialchars($item['extra']) : '<em>—</em>' ?></td>
                                <?php elseif ($tablaActiva === 'unidades'): ?>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><code style="background:var(--border-color);padding:3px 8px;border-radius:5px;font-weight:600;"><?= htmlspecialchars($item['extra']) ?></code></td>
                                <?php elseif ($tablaActiva === 'tipos_iva'): ?>
                                    <td style="font-weight:600;color:var(--text-color);"><?= htmlspecialchars($item['nombre']) ?></td>
                                    <td><strong style="color:var(--primary);font-size:15px;"><?= number_format($item['tasa_iva'], 2) ?>%</strong></td>
                                <?php endif; ?>
                                <td class="acciones-cell" style="text-align:center;">
                                    <button class="btn-accion btn-editar" onclick="editarMaestro(<?= htmlspecialchars(json_encode($item)) ?>)" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
    <div class="modal-content" style="max-width:520px; border-radius:16px;">
        <div class="modal-header" style="border-bottom:1px solid var(--border-color); padding-bottom:16px;">
            <h2 id="maestro-modal-title" style="font-size:18px; font-weight:700; color:var(--text-main);">Nuevo Registro</h2>
            <button class="modal-close" onclick="cerrarModalMaestro()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="index.php?route=inv_maestros&action=guardar" method="POST" autocomplete="off">
            <input type="hidden" name="tabla" value="<?= htmlspecialchars($tablaActiva) ?>">
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
                        <input type="text" name="representante" id="mae-inp-representante" required placeholder="Ej: MAYOR JHON BARRERA">
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
                                    <option value="<?= $uni['id'] ?>"><?= htmlspecialchars($uni['nombre']) ?> (<?= htmlspecialchars($uni['extra']) ?>)</option>
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
                        <input type="text" name="funcionario" id="mae-inp-funcionario" required placeholder="Ej: MAYOR FABRICIO CORONEL">
                    </div>

                <!-- Caso 5: Proveedores -->
                <?php elseif ($tablaActiva === 'proveedores'): ?>
                    <div class="form-group">
                        <label>Razón Social / Nombre del Proveedor</label>
                        <input type="text" name="nombre" id="mae-inp-nombre" required placeholder="Ej: Importadora Andina S.A.">
                    </div>
                    <div class="form-group">
                        <label>RUC / Cédula del Proveedor</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" name="ruc" id="mae-inp-ruc" required placeholder="Ej: 1391234567001" maxlength="13" oninput="this.value=this.value.replace(/[^0-9]/g,'')" style="flex: 1;">
                            <button type="button" class="btn-primary" id="btn-buscar-ruc" onclick="ejecutarBusquedaRuc()" style="padding: 10px 14px; height: 38px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-size: 13px;">
                                <i class="fa-solid fa-magnifying-glass"></i> Consultar
                            </button>
                        </div>
                        <small id="lookup-indicator" style="display: block; margin-top: 4px; font-size: 11px; color: var(--text-muted);"></small>
                    </div>
                    <div class="form-group">
                        <label>Contacto, Teléfono y Dirección</label>
                        <input type="text" name="extra" id="mae-inp-extra" placeholder="Dirección, teléfono, contacto corporativo...">
                    </div>

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

    function abrirModalMaestro() {
        document.getElementById('maestro-modal-title').textContent = 'Nuevo Registro';
        document.getElementById('mae-inp-id').value = '0';
        document.getElementById('mae-inp-nombre').value = '';

        var extra = document.getElementById('mae-inp-extra');
        if (extra) extra.value = '';
        var codigo = document.getElementById('mae-inp-codigo');
        if (codigo) codigo.value = '';
        var representante = document.getElementById('mae-inp-representante');
        if (representante) representante.value = '';
        var funcionario = document.getElementById('mae-inp-funcionario');
        if (funcionario) funcionario.value = '';
        var ruc = document.getElementById('mae-inp-ruc');
        if (ruc) ruc.value = '';
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
        document.getElementById('maestro-modal-title').textContent = 'Editar Registro';
        document.getElementById('mae-inp-id').value = item.id;
        document.getElementById('mae-inp-nombre').value = item.nombre || '';

        var extra = document.getElementById('mae-inp-extra');
        if (extra) extra.value = item.extra || '';
        var codigo = document.getElementById('mae-inp-codigo');
        if (codigo) codigo.value = item.codigo || '';
        var representante = document.getElementById('mae-inp-representante');
        if (representante) representante.value = item.representante || '';
        var funcionario = document.getElementById('mae-inp-funcionario');
        if (funcionario) funcionario.value = item.funcionario || '';
        var ruc = document.getElementById('mae-inp-ruc');
        if (ruc) ruc.value = item.ruc || '';
        
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
</script>
