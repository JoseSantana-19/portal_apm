<?php
/**
 * Catálogo único de navegación y permisos.
 *
 * Todo módulo visible agregado aquí aparece automáticamente tanto en el menú
 * lateral como en Gestión de Permisos. Si no declara permission_sections,
 * recibe una sección general con el mismo nombre del módulo.
 */
return [
    'operaciones' => [
        'titulo_seccion' => 'Operaciones de Terminal',
        'items' => [
            'busqueda_global' => [
                'label' => 'Búsqueda Global',
                'icon' => 'fa-magnifying-glass',
                'title' => 'Buscador Inteligente del Sistema',
                'permission_label' => 'Consulta general',
            ],
            'inventario' => [
                'label' => 'Dashboard general',
                'icon' => 'fa-chart-line',
                'title' => 'Dashboard general de inventario y operaciones',
                'permission_label' => 'Dashboard general',
            ],
            'items' => [
                'label' => 'Catálogo de Ítems',
                'icon' => 'fa-box',
                'title' => 'Catálogo Visual por Grupos',
                'permission_label' => 'Catálogo de productos',
            ],
            'inv_items_sistema' => [
                'label' => 'Maestro de Ítems',
                'icon' => 'fa-cubes',
                'title' => 'Maestro de Ítems de Inventarios',
                'permission_label' => 'Registros del sistema',
            ],
        ],
    ],
    'consultas' => [
        'titulo_seccion' => 'Reportes',
        'items' => [
            'reportes' => [
                'label' => 'Centro de reportes',
                'icon' => 'fa-file-lines',
                'title' => 'Reportes integrales de todas las operaciones',
                'permission_label' => 'Consulta e impresión de reportes',
            ],
        ],
    ],
    'bodega' => [
        'titulo_seccion' => 'Bodega',
        'items' => [
            'requisiciones' => [
                'label' => '1. Requisiciones',
                'icon' => 'fa-clipboard-list',
                'title' => 'Solicitudes internas de productos',
                'permission_label' => 'Requisiciones de productos',
            ],
            'ordenes_compra' => [
                'label' => '2. Órdenes de compra',
                'icon' => 'fa-cart-shopping',
                'title' => 'Preparación y aprobación de órdenes',
                'permission_label' => 'Órdenes de compra',
            ],
            'ingresos' => [
                'label' => '3. Ingresos con Factura',
                'icon' => 'fa-file-invoice-dollar',
                'title' => 'Ingresos a bodega respaldados por factura',
                'permission_label' => 'Facturas e ingresos a bodega',
            ],
            'egresos' => [
                'label' => '4. Egresos de bodega',
                'icon' => 'fa-dolly',
                'title' => 'Despacho de requisiciones y salida de existencias',
                'permission_label' => 'Egresos y despachos de bodega',
            ],
        ],
    ],
    'datos' => [
        'titulo_seccion' => 'Arquitectura de Datos',
        'items' => [
            'inv_maestros' => [
                'label' => 'Maestros',
                'icon' => 'fa-layer-group',
                'title' => 'Gestión de Grupos, Productos, Unidades e IVA',
                'permission_sections' => [
                    'categorias' => 'Grupos y categorías',
                    'productos' => 'Catálogo de productos',
                    'proveedores' => 'Proveedores',
                    'unidades' => 'Unidades de medida',
                    'tipos_iva' => 'Tipos de IVA',
                    'grupo_centros_consumo' => 'Grupos de centros de consumo',
                    'centros_consumo' => 'Centros de consumo',
                ],
            ],
            'inv_periodos' => [
                'label' => 'Períodos e IVA',
                'icon' => 'fa-calendar-days',
                'title' => 'Gestión de Períodos e IVA Variable',
                'permission_label' => 'Períodos contables',
            ],
            'inv_secuenciales' => [
                'label' => 'Secuenciales de Índice',
                'icon' => 'fa-list-ol',
                'title' => 'Contadores Automáticos',
                'permission_label' => 'Contadores automáticos',
            ],
            'inv_parametros' => [
                'label' => 'Parámetros monetarios',
                'icon' => 'fa-sliders',
                'title' => 'Precisión de precios e importes',
                'permission_label' => 'Parámetros monetarios',
            ],
        ],
    ],
    'rrhh' => [
        'titulo_seccion' => 'Gestión de Personal',
        'items' => [
            'talento_directorio' => [
                'label' => 'Directorio de Personal',
                'icon' => 'fa-users',
                'title' => 'Listado de Funcionarios',
                'permission_label' => 'Funcionarios y fichas',
            ],
        ],
    ],
    'sistema' => [
        'titulo_seccion' => 'Sistema y Logs',
        'items' => [
            'inv_bitacora' => [
                'label' => 'Bitácora del Sistema',
                'icon' => 'fa-clock-rotate-left',
                'title' => 'Log de Auditoría Completo',
                'permission_label' => 'Auditoría y eventos',
            ],
            'usuarios' => [
                'label' => 'Gestión de Usuarios',
                'icon' => 'fa-user-shield',
                'title' => 'Control de Acceso y Roles',
                'permission_label' => 'Usuarios y parámetros',
            ],
            'inv_permisos' => [
                'label' => 'Gestión de Permisos',
                'icon' => 'fa-key',
                'title' => 'Asignar permisos por usuario',
                'permission_label' => 'Matriz de permisos',
                'solo_admin' => true,
            ],
        ],
    ],
];
