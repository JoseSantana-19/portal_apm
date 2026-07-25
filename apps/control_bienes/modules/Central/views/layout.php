<?php
/**
 * LAYOUT.PHP - Vista Plantilla Unificada (Layout)
 * Centraliza la estructura HTML, el Sidebar Menu, el Header y el sistema de Toast Notifications.
 * Evita la duplicación de código en todo el sistema.
 * Compatible con PHP 7.4, 8.3 y 8.4
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Resolver ruta activa para iluminar el menú lateral
$routeActiva = isset($_GET['route']) ? $_GET['route'] : 'inventario';

// Verificar rol del usuario para menús exclusivos de admin
if (session_status() === PHP_SESSION_NONE) session_start();
$rolActual = isset($_SESSION['rol']) ? strtolower($_SESSION['rol']) : '';
$esAdminActual = ($rolActual === 'administrador');

// Menú de navegación mapeado
$menuItems = [
    'operaciones' => [
        'titulo_seccion' => 'Operaciones de Terminal',
        'items' => [
            'busqueda_global' => ['label' => 'Búsqueda Global',   'icon' => 'fa-magnifying-glass', 'title' => 'Buscador Inteligente del Sistema'],
            'inventario'    => ['label' => 'Inventario General',   'icon' => 'fa-ship',         'title' => 'Inventario General de Bienes'],
            'items'         => ['label' => 'Catálogo de Ítems',    'icon' => 'fa-box',          'title' => 'Catálogo Visual por Grupos'],
            'inv_items_sistema' => ['label' => 'Ítems del Sistema',    'icon' => 'fa-cubes',        'title' => 'Ítems del Sistema de Inventarios'],
        ]
    ],
    'consultas' => [
        'titulo_seccion' => 'Consulta Reportes',
        'items' => [
            'reportes' => ['label' => 'Reportes Varios', 'icon' => 'fa-chart-pie', 'title' => 'Listados y Reportes Varios de Control'],
        ]
    ],
    'datos' => [
        'titulo_seccion' => 'Arquitectura de Datos',
        'items' => [
            'inv_maestros'     => ['label' => 'Maestros',               'icon' => 'fa-layer-group',   'title' => 'Gestión de Grupos, Productos, Unidades e IVA'],
            'inv_periodos'     => ['label' => 'Períodos e IVA',         'icon' => 'fa-calendar-days', 'title' => 'Gestión de Períodos e IVA Variable'],
            'inv_secuenciales' => ['label' => 'Secuenciales de Índice', 'icon' => 'fa-list-ol',      'title' => 'Contadores Automáticos']
        ]
    ],
    'rrhh' => [
        'titulo_seccion' => 'Gestión de Personal',
        'items' => [
            'talento_directorio'      => ['label' => 'Directorio de Personal',  'icon' => 'fa-users',          'title' => 'Listado de Funcionarios'],
        ]
    ],
    'sistema' => [
        'titulo_seccion' => 'Sistema y Logs',
        'items' => [
            'inv_bitacora' => ['label' => 'Bitácora del Sistema', 'icon' => 'fa-clock-rotate-left', 'title' => 'Log de Auditoría Completo'],
            'usuarios' => ['label' => 'Gestión de Usuarios',  'icon' => 'fa-user-shield',       'title' => 'Control de Acceso y Roles'],
        ]
    ]
];

// Solo el Administrador ve la opción de Permisos
if ($esAdminActual) {
    $menuItems['sistema']['items']['inv_permisos'] = ['label' => 'Gestión de Permisos', 'icon' => 'fa-key', 'title' => 'Asignar inv_permisos por usuario'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titulo) ? htmlspecialchars($titulo) : 'SysPort | APM Manta' ?></title>
    <meta name="description" content="SysPort | APM Manta - Portal Corporativo - Autoridad Portuaria de Manta">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables CSS & JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="public/css/inv_estilos.css?v=<?= time() ?>">
    
    <!-- Assets de Talento Humano (Cargados de forma condicional) -->
    <?php if (strpos($routeActiva, 'talento') === 0): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="public/css/talento_custom.css?v=<?= time() ?>">
        <script src="public/js/talento_humano.js?v=<?= time() ?>"></script>
    <?php endif; ?>

    <script>
        // Pre-cargar tema para evitar parpadeos visuales en el renderizado
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
    <style>
        /* Modal de Confirmación Estilizado */
        .confirm-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(8px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .confirm-modal-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        .confirm-modal-box {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 16px;
            padding: 24px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: scale(0.9);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .confirm-modal-overlay.active .confirm-modal-box {
            transform: scale(1);
        }
        .confirm-modal-icon {
            font-size: 42px;
            color: #ef4444;
            margin-bottom: 16px;
            animation: pulseDanger 2s infinite;
        }
        @keyframes pulseDanger {
            0% { transform: scale(1); filter: drop-shadow(0 0 0 rgba(239, 68, 68, 0.4)); }
            70% { transform: scale(1.05); filter: drop-shadow(0 0 10px rgba(239, 68, 68, 0)); }
            100% { transform: scale(1); filter: drop-shadow(0 0 0 rgba(239, 68, 68, 0)); }
        }
        .confirm-modal-box h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            color: #1e293b;
            font-weight: 700;
        }
        .confirm-modal-box p {
            font-size: 13px;
            color: #64748b;
            margin: 0 0 24px 0;
            line-height: 1.5;
        }
        .confirm-modal-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .btn-confirm-cancel {
            background: rgba(226, 232, 240, 0.8);
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-confirm-cancel:hover {
            background: #e2e8f0;
            color: #334155;
        }
        .btn-confirm-ok {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2);
        }
        .btn-confirm-ok:hover {
            background: #dc2626;
            box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3);
        }

        /* Modo oscuro */
        [data-theme="dark"] .confirm-modal-box {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        [data-theme="dark"] .confirm-modal-box h3 {
            color: #f1f5f9;
        }
        [data-theme="dark"] .confirm-modal-box p {
            color: #94a3b8;
        }
        [data-theme="dark"] .btn-confirm-cancel {
            background: rgba(51, 65, 85, 0.8);
            color: #cbd5e1;
            border: 1px solid #475569;
        }
        [data-theme="dark"] .btn-confirm-cancel:hover {
            background: #334155;
            color: white;
        }

        /* Ajustes menores de layouts y reasignaciones */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: var(--panel-bg, #ffffff);
            color: var(--text-color, #1e293b);
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 9999;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            border-left: 5px solid var(--primary);
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        .toast-success { border-left-color: #10b981; }
        .toast-warning { border-left-color: #f59e0b; }
        .toast-inv_error { border-left-color: #ef4444; }
        .toast-info { border-left-color: #3b82f6; }
        .toast i { font-size: 20px; }
        .toast-success i { color: #10b981; }
        .toast-warning i { color: #f59e0b; }
        .toast-inv_error i { color: #ef4444; }
        .toast-info i { color: #3b82f6; }
    </style>
</head>
<body>

    <!-- Menú Lateral Dinámico Server-Side -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="<?= BASE_URL ?>../../imgs/logoapm.png" alt="SysPort" class="sidebar-logo">
            <div class="sidebar-brand">
                <span class="sidebar-brand-name">SysPort | APM</span>
                <small class="sidebar-brand-sub">Portal Corporativo</small>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <a href="../../dashboard" class="menu-item" title="Volver al Portal APM" style="border:1px dashed rgba(148,163,184,.4);margin-bottom:8px;">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Portal APM</span>
            </a>
            <?php
            // Cargar inv_permisos del usuario actual para filtrar el menú
            $permisosDelUsuario = [];
            if (!$esAdminActual) {
                $usuarioIdActual = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
                if ($usuarioIdActual > 0) {
                    require_once ROOT_PATH . 'modules/Credenciales/models/PermisoModel.php';
                    $_permisoModel = new InvPermiso();
                    $permisosDelUsuario = $_permisoModel->obtenerPermisosUsuario($usuarioIdActual);
                }
            }
            foreach ($menuItems as $seccion => $info):
                // Filtrar los items de esta sección según inv_permisos
                $itemsVisibles = [];
                foreach ($info['items'] as $routeKey => $item) {
                    if ($esAdminActual || in_array($routeKey, $permisosDelUsuario)) {
                        $itemsVisibles[$routeKey] = $item;
                    }
                }
                if (empty($itemsVisibles)) continue;
            ?>
                <div class="menu-section"><?= htmlspecialchars($info['titulo_seccion']) ?></div>
                <?php foreach ($itemsVisibles as $routeKey => $item):
                    $activeClass = ($routeActiva === $routeKey) ? 'active' : '';
                ?>
                    <?php
                    $url = "index.php?route=" . $routeKey;
                    if ($routeKey === 'busqueda_global') {
                        $url = "index.php?route=inv_maestros&tabla=busqueda_global";
                    }
                    $activeClass = ($routeActiva === $routeKey) ? 'active' : '';
                    if ($routeKey === 'busqueda_global' && $routeActiva === 'inv_maestros' && isset($_GET['tabla']) && $_GET['tabla'] === 'busqueda_global') {
                        $activeClass = 'active';
                    }
                    if ($routeKey === 'inv_maestros' && $routeActiva === 'inv_maestros' && isset($_GET['tabla']) && $_GET['tabla'] === 'busqueda_global') {
                        $activeClass = '';
                    }
                    ?>
                    <a href="<?= $url ?>" class="menu-item <?= $activeClass ?>" title="<?= htmlspecialchars($item['title']) ?>">
                        <i class="fa-solid <?= $item['icon'] ?>"></i>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

        <div class="sidebar-footer">
            <div class="sidebar-version">
                <i class="fa-solid fa-code-branch"></i>
                <span>v3.0.0 PHP MVC</span>
            </div>
        </div>
    </aside>

    <!-- Área de Contenido Principal -->
    <main class="main-content">
        <!-- Header Unificado -->
        <header class="top-header">
            <div class="header-left">
                <button id="hamburger-btn" class="hamburger-btn" title="Alternar Menú">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <!-- Buscador removido por redundancia -->
            </div>
            <div class="header-actions">
                <!-- Botón de Modo Oscuro / Claro -->
                <button class="icon-btn" id="theme-toggle-btn" title="Alternar Tema" style="width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: all 0.3s;">
                    <i class="fa-solid fa-moon" id="theme-icon" style="font-size: 18px; color: var(--text-muted); transition: color 0.3s;"></i>
                </button>

                <!-- Campanita con dropdown interactivo de notificaciones reales -->
                <div class="notifications-container" style="position:relative; display:inline-block;">
                    <button class="icon-btn" id="bell-btn" title="Notificaciones del Sistema" style="width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: all 0.3s;">
                        <i class="fa-regular fa-bell"></i>
                        <?php if (!empty($notificacionesNoLeidas)): ?>
                            <span class="badge" id="bell-badge"><?= count($notificacionesNoLeidas) ?></span>
                        <?php endif; ?>
                    </button>
                    
                    <div class="notifications-dropdown" id="notifications-dropdown">
                        <div class="notif-header">
                            <h3>Alertas y Notificaciones</h3>
                            <?php if (!empty($notificacionesNoLeidas)): ?>
                                <span class="notif-count" id="notif-header-count"><?= count($notificacionesNoLeidas) ?> Nuevas</span>
                            <?php endif; ?>
                        </div>
                        <div class="notif-body">
                            <?php if (empty($notificaciones)): ?>
                                <div class="notif-empty">
                                    <i class="fa-regular fa-bell-slash"></i>
                                    <p>No hay alertas en este momento</p>
                                    <span>Todo está en orden en la terminal</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notificaciones as $n):
                                    // Determinar icono
                                    $icon = 'fa-bell';
                                    if ($n['categoria'] === 'stock') {
                                        $icon = $n['tipo'] === 'critica' ? 'fa-box-open' : 'fa-boxes-stacked';
                                    } elseif ($n['categoria'] === 'mantenimiento') {
                                        $icon = $n['tipo'] === 'critica' ? 'fa-triangle-exclamation' : 'fa-wrench';
                                    } elseif ($n['categoria'] === 'ingreso') {
                                        $icon = 'fa-truck-ramp-box';
                                    } elseif ($n['categoria'] === 'egreso') {
                                        $icon = 'fa-truck-arrow-right';
                                    } elseif ($n['categoria'] === 'maestro') {
                                        $icon = 'fa-folder-plus';
                                    } elseif ($n['categoria'] === 'seguridad') {
                                        $icon = 'fa-shield-halved';
                                    }

                                    // Determinar clases de estilo
                                    $class = '';
                                    if ($n['tipo'] === 'critica') {
                                        $class = 'notif-item-critica';
                                    } elseif ($n['tipo'] === 'advertencia') {
                                        $class = 'notif-item-advertencia';
                                    } else {
                                        $class = 'notif-item-info';
                                    }

                                    if (isset($n['no_leida']) && $n['no_leida'] === true) {
                                        $class .= ' no-leida';
                                    }
                                ?>
                                    <div class="notif-item <?= $class ?>" onclick="verNotificacion('<?= htmlspecialchars($n['secuencial'] ?? '') ?>', '<?= htmlspecialchars($n['categoria'] ?? '') ?>')" style="cursor:pointer;">
                                        <div class="notif-icon">
                                            <i class="fa-solid <?= $icon ?>"></i>
                                        </div>
                                        <div class="notif-content">
                                            <div class="notif-title-row">
                                                <strong><?= htmlspecialchars($n['titulo']) ?></strong>
                                                <span class="notif-tag"><?= htmlspecialchars($n['badge']) ?></span>
                                            </div>
                                            <p><?= $n['mensaje'] ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="notif-footer" style="display:flex; justify-content:space-between; align-items:center; padding: 10px 16px; border-top: 1px solid var(--border-color);">
                            <a href="index.php?route=inv_bitacora" style="font-size:11px; text-decoration:none; color:var(--primary-blue); font-weight:600;">Ver Bitácora</a>
                            <a href="javascript:void(0)" onclick="vaciarNotificaciones()" style="font-size:11px; text-decoration:none; color:var(--danger); font-weight:600;"><i class="fa-regular fa-trash-can"></i> Vaciar Alertas</a>
                        </div>
                    </div>
                </div>

                <?php 
                $nombreUsuario = isset($_SESSION['usuario']['nombre']) ? $_SESSION['usuario']['nombre'] : 'Admin Terminal';
                $rolUsuario = isset($_SESSION['usuario']['rol']) ? $_SESSION['usuario']['rol'] : 'Administrador';
                $siglasUsuario = strtoupper(substr($nombreUsuario, 0, 2));
                ?>
                <div class="user-profile" style="display:flex; align-items:center; gap:12px; border-left: 1px solid var(--border-color); padding-left: 15px;">
                    <div class="user-info" style="text-align:right;">
                        <h4 style="margin: 0; font-size: 14px; font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($nombreUsuario) ?></h4>
                        <p style="margin: 0; font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($rolUsuario) ?></p>
                    </div>
                    <div class="user-avatar" title="<?= htmlspecialchars($nombreUsuario) ?>" style="border-radius:10px;font-weight:700;display:flex;align-items:center;justify-content:center;background:rgba(59,130,246,0.1);color:var(--primary-blue);width:36px;height:36px;font-size:13px; user-select: none;"><?= htmlspecialchars($siglasUsuario) ?></div>
                </div>

                <a href="index.php?route=logout" class="icon-btn" title="Cerrar Sesión" style="color: var(--danger); background: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; transition: all 0.3s; text-decoration:none;" onmouseover="this.style.background='rgba(239,68,68,0.2)'" onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <div class="content-area">
            <!-- Inyección de Contenido Específico -->
            <?= $contenido ?>
        </div>
    </main>

    <!-- Sistema de Toast Notifications (Mensajes temporales de sesión) -->
    <?php if (isset($_SESSION['toast'])): ?>
        <div class="toast toast-<?= $_SESSION['toast']['tipo'] ?> show">
            <i class="fa-solid <?= $_SESSION['toast']['tipo'] === 'success' ? 'fa-check-circle' : ($_SESSION['toast']['tipo'] === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle') ?>"></i>
            <span><?= $_SESSION['toast']['mensaje'] ?></span>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    const toast = document.querySelector('.toast');
                    if (toast) {
                        toast.classList.remove('show');
                        setTimeout(() => toast.remove(), 400);
                    }
                }, 3500);
            });
        </script>
        <?php unset($_SESSION['toast']); ?>
    <?php endif; ?>

    <!-- Scripts Globales de Control de UI -->
    <script>
        // Redirección y pre-búsqueda interactiva de notificaciones según su origen
        function verNotificacion(secuencial, categoria) {
            if (!secuencial && (categoria !== 'seguridad' && categoria !== 'maestro')) return;
            
            if (categoria === 'ingreso') {
                window.location.href = 'index.php?route=ingresos&termino=' + encodeURIComponent(secuencial);
            } else if (categoria === 'egreso') {
                window.location.href = 'index.php?route=egresos&termino=' + encodeURIComponent(secuencial);
            } else if (categoria === 'seguridad') {
                window.location.href = 'index.php?route=usuarios';
            } else if (categoria === 'maestro') {
                window.location.href = 'index.php?route=inv_maestros';
            } else {
                window.location.href = 'index.php?route=inventario&termino=' + encodeURIComponent(secuencial);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Hamburguesa Sidebar toggle
            const hamburgerBtn = document.getElementById('hamburger-btn');
            const sidebar = document.getElementById('sidebar');

            if (hamburgerBtn && sidebar) {
                hamburgerBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('collapsed');
                });
            }

            // Control de Dropdown de Notificaciones y AJAX para marcar vistas
            const bellBtn = document.getElementById('bell-btn');
            const dropdown = document.getElementById('notifications-dropdown');
            
            if (bellBtn && dropdown) {
                bellBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpening = !dropdown.classList.contains('active');
                    dropdown.classList.toggle('active');

                    if (isOpening) {
                        // AJAX para marcar todas las notificaciones como vistas
                        fetch('index.php?route=notificaciones_marcar_leidas')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Ocultar contador en la campanita
                                    const badge = document.getElementById('bell-badge');
                                    if (badge) {
                                        badge.style.display = 'none';
                                    }
                                    // Ocultar contador interno del dropdown
                                    const headerCount = document.getElementById('notif-header-count');
                                    if (headerCount) {
                                        headerCount.style.display = 'none';
                                    }
                                    // Quitar indicador visual de no leído (punto azul y background)
                                    document.querySelectorAll('.notif-item.no-leida').forEach(item => {
                                        item.classList.remove('no-leida');
                                    });
                                }
                            })
                            .catch(err => console.error('Error al actualizar vistas:', err));
                    }
                });
                
                document.addEventListener('click', (e) => {
                    if (!dropdown.contains(e.target) && e.target !== bellBtn && !bellBtn.contains(e.target)) {
                        dropdown.classList.remove('active');
                    }
                });
            }

            window.vaciarNotificaciones = function() {
                const modal = document.getElementById('confirm-modal');
                if (modal) {
                    modal.classList.add('active');
                }
            }

            // Control de Tema (Modo Oscuro / Claro)
            const themeToggleBtn = document.getElementById('theme-toggle-btn');
            const themeIcon = document.getElementById('theme-icon');

            // Cargar e inicializar tema de localStorage
            const savedTheme = localStorage.getItem('theme') || 'light';
            updateThemeUI(savedTheme === 'dark');

            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', () => {
                    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                    const newTheme = isDark ? 'light' : 'dark';
                    document.documentElement.setAttribute('data-theme', newTheme);
                    localStorage.setItem('theme', newTheme);
                    updateThemeUI(!isDark);
                });
            }

            function updateThemeUI(isDark) {
                if (isDark) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
                
                if (!themeIcon) return;
                
                if (isDark) {
                    themeIcon.className = 'fa-solid fa-sun';
                    themeIcon.style.color = '#eab308';
                    if (themeToggleBtn) themeToggleBtn.title = "Cambiar a Modo Claro";
                } else {
                    themeIcon.className = 'fa-solid fa-moon';
                    themeIcon.style.color = 'var(--text-muted)';
                    if (themeToggleBtn) themeToggleBtn.title = "Cambiar a Modo Oscuro";
                }
            }

            // Confirm Modal Buttons Listeners
            const confirmCancelBtn = document.getElementById('confirm-cancel-btn');
            const confirmOkBtn = document.getElementById('confirm-ok-btn');
            const confirmModal = document.getElementById('confirm-modal');

            if (confirmCancelBtn && confirmModal) {
                confirmCancelBtn.addEventListener('click', () => {
                    confirmModal.classList.remove('active');
                });
            }

            if (confirmOkBtn && confirmModal) {
                confirmOkBtn.addEventListener('click', () => {
                    confirmModal.classList.remove('active');
                    fetch('index.php?route=notificaciones_vaciar')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Ocultar contador de la campanita
                                const badge = document.getElementById('bell-badge');
                                if (badge) {
                                    badge.style.display = 'none';
                                }
                                // Ocultar contador interno
                                const headerCount = document.getElementById('notif-header-count');
                                if (headerCount) {
                                    headerCount.style.display = 'none';
                                }
                                // Reemplazar el cuerpo del dropdown con la vista vacía
                                const notifBody = document.querySelector('.notif-body');
                                if (notifBody) {
                                    notifBody.innerHTML = `
                                        <div class="notif-empty">
                                            <i class="fa-regular fa-bell-slash"></i>
                                            <p>No hay alertas en este momento</p>
                                            <span>Todo está en orden en la terminal</span>
                                        </div>
                                    `;
                                }
                            }
                        })
                        .catch(err => console.error('Error al vaciar notificaciones:', err));
                });
            }

            // Ctrl+K enfoca o abre la búsqueda global en Maestros
            document.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    const search = document.getElementById('main-global-search-input');
                    if (search) {
                        search.focus();
                    } else {
                        window.location.href = 'index.php?route=inv_maestros&tabla=busqueda_global';
                    }
                }
            });
        });
    </script>
    <!-- Modal de Confirmación Estilizado -->
    <div id="confirm-modal" class="confirm-modal-overlay">
        <div class="confirm-modal-box">
            <div class="confirm-modal-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3>¿Confirmar Eliminación?</h3>
            <p>Estás a punto de borrar por completo el historial de notificaciones y alertas. Esta acción no se puede deshacer y las alertas no volverán a mostrarse en esta sesión.</p>
            <div class="confirm-modal-actions">
                <button id="confirm-cancel-btn" class="btn-confirm-cancel">Cancelar</button>
                <button id="confirm-ok-btn" class="btn-confirm-ok">Vaciar Alertas</button>
            </div>
        </div>
    </div>

    <script src="public/js/app_ajax.js"></script>
</body>
</html>
