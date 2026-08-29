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
$assetVersion = static function (string $ruta): int {
    $archivo = ROOT_PATH . ltrim($ruta, '/');
    return is_file($archivo) ? (int)filemtime($archivo) : 1;
};
if (empty($_SESSION['perfil_foto_csrf'])) {
    $_SESSION['perfil_foto_csrf'] = bin2hex(random_bytes(24));
}

// Una sola fuente alimenta el menú y la matriz de Gestión de Permisos.
$menuItems = require ROOT_PATH . 'config/navigation.php';
foreach ($menuItems as &$grupoMenu) {
    foreach (($grupoMenu['items'] ?? []) as $routeMenu => $itemMenu) {
        if (!empty($itemMenu['solo_admin']) && !$esAdminActual) unset($grupoMenu['items'][$routeMenu]);
    }
}
unset($grupoMenu);
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
    <?php if (in_array($routeActiva, ['inv_maestros', 'items'], true)): ?>
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
        <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <?php endif; ?>
    <link rel="stylesheet" href="public/css/inv_estilos.css?v=<?= $assetVersion('public/css/inv_estilos.css') ?>">
    <?php if (defined('PORTAL_ROOT_URL')): ?>
    <!-- SweetAlert2 CSS (aviso de inactividad centralizado del portal) -->
    <link rel="stylesheet" href="<?= PORTAL_ROOT_URL ?>/public/librerias/Otras_librerias/sweetalert2/sweetalert2.min.css">
    <script src="<?= PORTAL_ROOT_URL ?>/js/password-hash.js?v=<?= @filemtime(dirname(rtrim(ROOT_PATH, '/'), 2) . '/js/password-hash.js') ?: time() ?>"></script>
    <?php endif; ?>

    <!-- Assets de Talento Humano (Cargados de forma condicional) -->
    <?php if (strpos($routeActiva, 'talento') === 0): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="public/css/talento_custom.css?v=<?= $assetVersion('public/css/talento_custom.css') ?>">
        <script src="public/js/talento_humano.js?v=<?= $assetVersion('public/js/talento_humano.js') ?>"></script>
    <?php endif; ?>

    <script>
        // Pre-cargar tema para evitar parpadeos visuales en el renderizado
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
        if (localStorage.getItem('sidebar_collapsed') === '1') {
            document.documentElement.classList.add('sidebar-collapsed-preload');
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
            <?php
            // Puente SSO activo (vino del portal) o login propio de una cédula
            // que también existe en PORTAL_APM.CORE_Usuarios: sí puede volver.
            // Cuenta exclusiva de este módulo (sin cédula real en el portal):
            // no hay portal al que volver, se oculta.
            $tieneAccesoPortal = !empty($_SESSION['user_id']) || !empty($_SESSION['tiene_acceso_portal']);
            ?>
            <?php if ($tieneAccesoPortal): ?>
            <a href="../../dashboard" class="menu-item" title="Volver al Portal APM" style="border:1px dashed rgba(148,163,184,.4);margin-bottom:8px;">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Portal APM</span>
            </a>
            <?php endif; ?>
            <?php
            // Cargar permisos del usuario actual para filtrar el menú.
            // Puenteada desde el portal: cross-DB fn_TienePermisoNodo por
            // opción MOIS (POLITICAS de Router, pantalla completa — sin
            // sub-alcance). Nativa: sistema propio granular del origen
            // (inv_permisos / inv_permisos_detalle vía PermisoModel).
            $permisosDelUsuario = [];
            $matrizPermisosUsuario = [];
            if (!$esAdminActual) {
                $puenteadaMenu = !empty($_SESSION['user_id']);
                if ($puenteadaMenu) {
                    require_once ROOT_PATH . 'core/Controller.php';
                    $rutaAOpcion = [
                        'inventario' => 2, 'items' => 3, 'inv_items_sistema' => 4,
                        'cabeceras' => 5, 'inv_maestros' => 6, 'ingresos' => 7, 'egresos' => 8,
                        'talento_directorio' => 9, 'inv_bitacora' => 10, 'reportes' => 11,
                        'inv_periodos' => 12, 'inv_secuenciales' => 13, 'usuarios' => 14, 'inv_permisos' => 15,
                    ];
                    $probeLayout = new class extends Controller {
                        public function nivel(int $idUsuario, int $opcion): int {
                            for ($n = 4; $n >= 1; $n--) {
                                if ($this->tienePermisoPortal($idUsuario, $opcion, $n)) return $n;
                            }
                            return 0;
                        }
                    };
                    foreach ($menuItems as $infoSeccionProbe) {
                        foreach (($infoSeccionProbe['items'] ?? []) as $routeKeyProbe => $itemProbe) {
                            $permisoMenuProbe = $itemProbe['permission'] ?? $routeKeyProbe;
                            // Ruta sin nodo MOIS mapeado todavía: se deja visible
                            // (mismo criterio que Router::checkPermisosPuente).
                            if (!isset($rutaAOpcion[$permisoMenuProbe])) {
                                $permisosDelUsuario[] = $permisoMenuProbe;
                                continue;
                            }
                            if ($probeLayout->nivel((int)$_SESSION['user_id'], $rutaAOpcion[$permisoMenuProbe]) >= 1) {
                                $permisosDelUsuario[] = $permisoMenuProbe;
                            }
                        }
                    }
                } else {
                    $usuarioIdActual = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
                    if ($usuarioIdActual > 0) {
                        require_once ROOT_PATH . 'modules/Credenciales/models/PermisoModel.php';
                        $_permisoModel = new InvPermiso();
                        $permisosDelUsuario = $_permisoModel->obtenerPermisosUsuario($usuarioIdActual);
                        $matrizPermisosUsuario = $_permisoModel->obtenerMatrizUsuario($usuarioIdActual);
                    }
                }
            }
            foreach ($menuItems as $seccion => $info):
                // Filtrar los items de esta sección según inv_permisos
                $itemsVisibles = [];
                foreach ($info['items'] as $routeKey => $item) {
                    $permisoMenu = $item['permission'] ?? $routeKey;
                    if ($esAdminActual || in_array($permisoMenu, $permisosDelUsuario)) {
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
                    $routeDestino = $item['route'] ?? $routeKey;
                    $url = "index.php?route=" . $routeDestino;
                    if (!empty($item['vista'])) {
                        $url .= '&vista=' . urlencode($item['vista']);
                    }
                    if ($routeKey === 'busqueda_global') {
                        $url = "index.php?route=inv_maestros&tabla=busqueda_global";
                    }
                    if (!$esAdminActual && $routeKey === 'egresos' && !empty($matrizPermisosUsuario['egresos'])) {
                        foreach (['ordenes','facturas','ingresos','kardex'] as $_scope) {
                            $_regla = $matrizPermisosUsuario['egresos'][$_scope] ?? $matrizPermisosUsuario['egresos']['*'] ?? [];
                            if (!empty($_regla['read']) || !empty($_regla['full'])) { $url = 'index.php?route=egresos&vista='.$_scope; break; }
                        }
                    }
                    if (!$esAdminActual && $routeKey === 'inv_maestros' && !empty($matrizPermisosUsuario['inv_maestros'])) {
                        foreach (['categorias','productos','proveedores','unidades','tipos_iva','grupo_centros_consumo','centros_consumo'] as $_scope) {
                            $_regla = $matrizPermisosUsuario['inv_maestros'][$_scope] ?? $matrizPermisosUsuario['inv_maestros']['*'] ?? [];
                            if (!empty($_regla['read']) || !empty($_regla['full'])) { $url = 'index.php?route=inv_maestros&tabla='.$_scope; break; }
                        }
                    }
                    $activeClass = ($routeActiva === $routeDestino) ? 'active' : '';
                    if (!empty($item['vista'])) {
                        $vistaActualMenu = $_GET['vista'] ?? 'notas';
                        $activeClass = ($routeActiva === $routeDestino && $vistaActualMenu === $item['vista']) ? 'active' : '';
                    }
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
                <button id="hamburger-btn" class="hamburger-btn" title="Alternar Menú" aria-controls="sidebar" aria-expanded="true">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <a class="header-institution" href="index.php?route=inventario" aria-label="Ir al inicio de Autoridad Portuaria de Manta">
                    <img src="logoapm.png" alt="" width="38" height="38" decoding="async">
                    <span>Autoridad Portuaria de Manta</span>
                </a>
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
                if (!isset($_SESSION['usuario']['departamento']) && !empty($_SESSION['usuario']['id'])) {
                    require_once ROOT_PATH . 'modules/Credenciales/models/UsuarioModel.php';
                    $_usuarioPerfilModel = new UsuarioModel();
                    $_contextoPerfil = $_usuarioPerfilModel->obtenerContextoPerfil($_SESSION['usuario']);
                    $_SESSION['usuario']['departamento'] = $_contextoPerfil['departamento'];
                    $_SESSION['usuario']['cargo'] = $_contextoPerfil['cargo'];
                }
                $departamentoUsuario = trim((string)($_SESSION['usuario']['departamento'] ?? ''))
                    ?: (strtolower($rolUsuario) === 'administrador' ? 'Administración del Sistema' : 'Departamento no asignado');
                $cargoUsuario = trim((string)($_SESSION['usuario']['cargo'] ?? '')) ?: $rolUsuario;
                $partesNombre = preg_split('/\s+/u', trim($nombreUsuario), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $siglasUsuario = '';
                foreach (array_slice($partesNombre, 0, 2) as $parteNombre) {
                    $siglasUsuario .= function_exists('mb_substr') ? mb_substr($parteNombre, 0, 1, 'UTF-8') : substr($parteNombre, 0, 1);
                }
                $siglasUsuario = strtoupper($siglasUsuario ?: 'US');
                $usuarioIdCabecera = (int)($_SESSION['usuario']['id'] ?? $_SESSION['usuario_id'] ?? 0);
                $fotoPerfilUrl = '';
                if ($usuarioIdCabecera > 0) {
                    foreach (['webp', 'jpg', 'png'] as $extensionFoto) {
                        $rutaFoto = 'public/uploads/perfiles/usuario-' . $usuarioIdCabecera . '.' . $extensionFoto;
                        if (is_file(ROOT_PATH . $rutaFoto)) {
                            $versionFoto = (int)($_SESSION['perfil_foto_version'] ?? filemtime(ROOT_PATH . $rutaFoto));
                            $fotoPerfilUrl = $rutaFoto . '?v=' . $versionFoto;
                            break;
                        }
                    }
                }
                ?>
                <div class="user-profile">
                    <div class="user-info">
                        <h4><?= htmlspecialchars($nombreUsuario) ?></h4>
                        <p><?= htmlspecialchars($rolUsuario) ?></p>
                    </div>
                    <button type="button" class="profile-photo-control" id="profile-photo-open" title="Cambiar foto de perfil" aria-haspopup="dialog" aria-controls="profile-photo-modal">
                        <span class="user-avatar" id="header-user-avatar" aria-hidden="true">
                            <?php if ($fotoPerfilUrl !== ''): ?>
                                <img src="<?= htmlspecialchars($fotoPerfilUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="40" height="40" decoding="async">
                            <?php else: ?>
                                <span class="user-avatar-initials"><?= htmlspecialchars($siglasUsuario) ?></span>
                            <?php endif; ?>
                            <span class="profile-photo-overlay"><i class="fa-solid fa-camera"></i></span>
                        </span>
                        <span class="profile-hover-card" role="tooltip">
                            <span class="profile-hover-photo">
                                <?php if ($fotoPerfilUrl !== ''): ?>
                                    <img src="<?= htmlspecialchars($fotoPerfilUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" width="68" height="68" decoding="async">
                                <?php else: ?>
                                    <span><?= htmlspecialchars($siglasUsuario) ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="profile-hover-details">
                                <strong><?= htmlspecialchars($nombreUsuario) ?></strong>
                                <span class="profile-hover-department"><i class="fa-solid fa-building"></i><?= htmlspecialchars($departamentoUsuario) ?></span>
                                <small><?= htmlspecialchars($cargoUsuario) ?></small>
                                <em><i class="fa-solid fa-camera"></i> Clic para cambiar la foto</em>
                            </span>
                        </span>
                        <span class="sr-only">Cambiar foto de perfil de <?= htmlspecialchars($nombreUsuario) ?></span>
                    </button>
                </div>

                <a href="index.php?route=logout" class="icon-btn" title="Cerrar Sesión" style="color: var(--danger); background: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; transition: all 0.3s; text-decoration:none;" onmouseover="this.style.background='rgba(239,68,68,0.2)'" onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <div class="content-area">
            <!-- Inyección de Contenido Específico -->
            <?php if (!empty($_permisoVista['readonly'])): ?>
                <div class="permission-readonly-banner" role="status"><i class="fa-solid fa-eye"></i><span><strong>Modo de solo lectura.</strong> Puedes consultar la información, pero las opciones para crear, editar, procesar o eliminar están deshabilitadas.</span></div>
            <?php endif; ?>
            <div id="permission-content" class="<?= !empty($_permisoVista['readonly']) ? 'permission-readonly' : '' ?>">
                <?= $contenido ?>
            </div>
        </div>
    </main>

    <div class="profile-editor-modal" id="profile-photo-modal" role="dialog" aria-modal="true" aria-labelledby="profile-editor-title" hidden>
        <div class="profile-editor-backdrop" data-profile-close></div>
        <section class="profile-editor-panel">
            <header class="profile-editor-heading">
                <div>
                    <span class="profile-editor-kicker">Perfil de usuario</span>
                    <h2 id="profile-editor-title">Ajustar foto de perfil</h2>
                    <p>Selecciona, amplía y mueve la imagen hasta obtener el encuadre deseado.</p>
                </div>
                <button type="button" class="profile-editor-close" data-profile-close aria-label="Cerrar editor"><i class="fa-solid fa-xmark"></i></button>
            </header>

            <div class="profile-editor-body">
                <div class="profile-editor-workspace">
                    <div class="profile-crop-stage" id="profile-crop-stage">
                        <canvas id="profile-crop-canvas" width="320" height="320" aria-label="Área para ajustar la foto"></canvas>
                        <div class="profile-crop-placeholder" id="profile-crop-placeholder">
                            <i class="fa-regular fa-image"></i>
                            <strong>Selecciona una imagen</strong>
                            <span>JPG, PNG o WEBP · máximo 8 MB</span>
                        </div>
                        <div class="profile-crop-guide" aria-hidden="true"></div>
                    </div>

                    <div class="profile-editor-controls">
                        <label class="profile-select-button" for="profile-photo-input"><i class="fa-solid fa-folder-open"></i> Elegir imagen</label>
                        <input type="file" id="profile-photo-input" accept="image/jpeg,image/png,image/webp" data-csrf="<?= htmlspecialchars($_SESSION['perfil_foto_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                        <label class="profile-zoom-control" for="profile-photo-zoom">
                            <i class="fa-solid fa-image"></i>
                            <input type="range" id="profile-photo-zoom" min="1" max="3" step="0.01" value="1" disabled>
                            <i class="fa-solid fa-image"></i>
                        </label>
                        <small><i class="fa-solid fa-arrows-up-down-left-right"></i> Arrastra la imagen para cambiar el encuadre.</small>
                    </div>
                </div>

                <aside class="profile-editor-preview">
                    <span class="profile-editor-kicker">Vista previa</span>
                    <h3>Así se verá en la cabecera</h3>
                    <div class="profile-header-preview">
                        <div class="profile-header-preview-info">
                            <strong><?= htmlspecialchars($nombreUsuario) ?></strong>
                            <span><?= htmlspecialchars($rolUsuario) ?></span>
                        </div>
                        <div class="profile-header-preview-avatar">
                            <canvas id="profile-preview-canvas" width="160" height="160"></canvas>
                            <span id="profile-preview-fallback"><?= htmlspecialchars($siglasUsuario) ?></span>
                        </div>
                    </div>
                    <div class="profile-editor-note">
                        <i class="fa-solid fa-gauge-high"></i>
                        <div><strong>Optimizada automáticamente</strong><span>Se guardará a 160 × 160 px para cargar rápidamente.</span></div>
                    </div>
                </aside>
            </div>

            <footer class="profile-editor-actions">
                <button type="button" class="btn-outline" data-profile-close>Cancelar</button>
                <button type="button" class="btn-primary" id="profile-photo-save" disabled><i class="fa-solid fa-check"></i> Guardar foto</button>
            </footer>
        </section>
    </div>

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
                const mediaMovil = window.matchMedia('(max-width: 768px)');
                const aplicarEstadoMenu = () => {
                    if (mediaMovil.matches) {
                        sidebar.classList.remove('collapsed');
                        sidebar.classList.remove('mobile-open');
                        hamburgerBtn.setAttribute('aria-expanded', 'false');
                    } else {
                        const contraido = localStorage.getItem('sidebar_collapsed') === '1';
                        sidebar.classList.toggle('collapsed', contraido);
                        sidebar.classList.remove('mobile-open');
                        hamburgerBtn.setAttribute('aria-expanded', contraido ? 'false' : 'true');
                    }
                    document.documentElement.classList.remove('sidebar-collapsed-preload');
                };

                aplicarEstadoMenu();

                hamburgerBtn.addEventListener('click', () => {
                    if (mediaMovil.matches) {
                        const abierto = sidebar.classList.toggle('mobile-open');
                        hamburgerBtn.setAttribute('aria-expanded', abierto ? 'true' : 'false');
                    } else {
                        const contraido = sidebar.classList.toggle('collapsed');
                        localStorage.setItem('sidebar_collapsed', contraido ? '1' : '0');
                        hamburgerBtn.setAttribute('aria-expanded', contraido ? 'false' : 'true');
                    }
                });

                sidebar.querySelectorAll('.menu-item').forEach(item => {
                    item.addEventListener('click', () => {
                        if (mediaMovil.matches) {
                            sidebar.classList.remove('mobile-open');
                            hamburgerBtn.setAttribute('aria-expanded', 'false');
                        }
                    });
                });

                if (typeof mediaMovil.addEventListener === 'function') {
                    mediaMovil.addEventListener('change', aplicarEstadoMenu);
                } else {
                    mediaMovil.addListener(aplicarEstadoMenu);
                }
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
            <p>Ocultarás las notificaciones de tu bandeja. Esta acción solo afecta a tu usuario: no elimina alertas de otras personas ni registros de auditoría.</p>
            <div class="confirm-modal-actions">
                <button id="confirm-cancel-btn" class="btn-confirm-cancel">Cancelar</button>
                <button id="confirm-ok-btn" class="btn-confirm-ok">Vaciar Alertas</button>
            </div>
        </div>
    </div>

    <script src="public/js/app_ajax.js"></script>
    <?php if (!empty($_permisoVista['readonly'])): ?>
    <style>
        .permission-readonly-banner{display:flex;align-items:flex-start;gap:11px;padding:13px 15px;margin-bottom:16px;border:1px solid #bfdbfe;border-radius:12px;background:#eff6ff;color:#1e40af;font-size:12px;line-height:1.5}.permission-readonly-banner i{margin-top:2px;font-size:15px}.permission-readonly .btn-editar,.permission-readonly .btn-eliminar,.permission-readonly [data-permission-action="create"],.permission-readonly [data-permission-action="edit"]{display:none!important}.permission-readonly form.permission-form-disabled{position:relative;opacity:.72}.permission-readonly form.permission-form-disabled :is(input,select,textarea,button){cursor:not-allowed}
    </style>
    <script>
    (()=>{
        const root=document.getElementById('permission-content');
        if(!root)return;
        const mutaciones=/(abrirmodal|editar|eliminar|borrar|guardar|registrar|aprobar|despachar|marcarsinexistencias|reiniciar|ejecutarcorte|ababriringreso)/i;
        const accionesUrl=/(?:action=|route=)(?:[^&]*(?:crear|editar|eliminar|borrar|guardar|aprobar|ingresar|reiniciar|ejecutarcorte|test))/i;
        const aplicar=()=>{
            root.querySelectorAll('form').forEach(form=>{
                if((form.getAttribute('method')||'get').toLowerCase()!=='post'||form.dataset.permissionReady)return;
                form.dataset.permissionReady='1';form.classList.add('permission-form-disabled');form.setAttribute('aria-disabled','true');
                form.querySelectorAll('input,select,textarea,button').forEach(control=>{control.disabled=true});
            });
            root.querySelectorAll('a[href],button[onclick],[role="button"][onclick]').forEach(control=>{
                const href=control.getAttribute('href')||'',onclick=control.getAttribute('onclick')||'';
                if(accionesUrl.test(href)||mutaciones.test(onclick)){control.hidden=true;control.style.setProperty('display','none','important');control.setAttribute('aria-hidden','true')}
            });
        };
        aplicar();document.addEventListener('DOMContentLoaded',aplicar,{once:true});setTimeout(aplicar,800);setTimeout(aplicar,2200);
    })();
    </script>
    <?php endif; ?>
    <script src="public/js/perfil_header.js?v=<?= $assetVersion('public/js/perfil_header.js') ?>"></script>

    <?php if (defined('PORTAL_ROOT_URL')): ?>
    <!-- Aviso de inactividad — vendorizado desde el portal (PORTAL_ROOT_URL, ver config/globals.php) -->
    <script>
        // window.X explícito: un const de nivel superior no queda accesible
        // como window.APP_INACTIVIDAD en un navegador real (js/inactivity-
        // warning.js lee justo esa propiedad) — con const el aviso nunca se
        // disparaba.
        // logoutUrl apunta al login PROPIO de Control de Bienes (no al del
        // portal): es el único módulo con login independiente, y hay cuentas
        // exclusivas de acá sin usuario en el portal — mandarlas al login del
        // portal las dejaría sin forma de volver a entrar.
        window.APP_INACTIVIDAD = {
            timeoutSegundos: <?= (int)($_SESSION['_inactividad_segundos'] ?? 600) ?>,
            avisoSegundos:   <?= (int)($_SESSION['_inactividad_aviso'] ?? 60) ?>,
            keepaliveUrl:    '<?= PORTAL_ROOT_URL ?>/api/keepalive',
            logoutUrl:       'index.php?route=logout'
        };
    </script>
    <script src="<?= PORTAL_ROOT_URL ?>/public/librerias/Otras_librerias/sweetalert2/sweetalert2.all.min.js"></script>
    <!-- ?v=time(): cache-busting real — evita que el navegador siga usando
         una copia vieja de este archivo cacheada de una visita anterior. -->
    <script src="<?= PORTAL_ROOT_URL ?>/js/inactivity-warning.js?v=<?= time() ?>"></script>
    <?php endif; ?>
</body>
</html>
