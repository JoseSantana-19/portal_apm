<?php
/**
 * Sidebar Drawer Component — Premium UI-UX Pro Max Redesign
 * Supports 4-level deep accordion organization with elegant glassmorphism.
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = dirname($scriptName);
$baseUrl = str_replace('\\', '/', $baseUrl);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($baseUrl !== '' && str_starts_with($currentPath, $baseUrl)) {
    $currentPath = substr($currentPath, strlen($baseUrl));
}
if (!str_starts_with($currentPath, '/')) $currentPath = '/' . $currentPath;

// Fallback menu loader check
if (!isset($userMenu) || empty($userMenu)) {
    $menuObj = new Menu();
    $userMenu = $menuObj->getUserMenu((int)($_SESSION['user_id'] ?? 0));
}

/* ── MODO MÓDULO (apartado individual) ──────────────────────────────
   Si la ruta actual pertenece a un módulo integrado, el sidebar se
   enfoca SOLO en ese módulo: sus opciones individuales expandidas +
   botón para volver al portal. Sensación de sistema propio (igual que
   Bitácoras), manteniendo los temas t1/t2/t3 del principal. */
$sidebarFocusId = null;
foreach ($userMenu as $__mid => $__mod) {
    foreach (($__mod['areas'] ?? []) as $__a) {
        foreach (($__a['items'] ?? []) as $__o) {
            $__u = '/' . ltrim((string)($__o['url'] ?? ''), '/');
            if ($__u !== '/' && $currentPath === $__u) { $sidebarFocusId = (int)$__mid; break 3; }
            foreach (($__o['children'] ?? []) as $__s) {
                $__su = '/' . ltrim((string)($__s['url'] ?? ''), '/');
                if ($__su !== '/' && $currentPath === $__su) { $sidebarFocusId = (int)$__mid; break 4; }
            }
        }
    }
}
if ($sidebarFocusId === null) {
    // Rutas internas del módulo que no están en el menú (formularios, detalle…)
    $__prefijosModulo = [
        '/apps/talento_humano' => 11, '/apps/control_bienes' => 12, '/apps/bitacoras' => 13,
        '/panel/talento-humano' => 11, '/panel/bienes' => 12,
    ];
    foreach ($__prefijosModulo as $__p => $__m) {
        if ($currentPath === $__p || str_starts_with($currentPath, $__p . '/')) { $sidebarFocusId = $__m; break; }
    }
}
// Solo los módulos INTEGRADOS se comportan como apartado individual;
// el resto del portal (dashboard, admin, etc.) mantiene el sidebar completo.
$__modulosApartado = [11, 12, 13];
if ($sidebarFocusId !== null
    && (!in_array($sidebarFocusId, $__modulosApartado, true) || !isset($userMenu[$sidebarFocusId]))) {
    $sidebarFocusId = null;
}
$sidebarFocus = ($sidebarFocusId !== null);
if ($sidebarFocus) {
    $userMenu = [$sidebarFocusId => $userMenu[$sidebarFocusId]];
}

if (!function_exists('normalizeFaIcon')) {
    function normalizeFaIcon(?string $icon): string {
        if (empty($icon)) return 'fa-solid fa-folder';
        if (str_starts_with($icon, 'ti-')) {
            $icon = substr($icon, 3);
        }
        $mappings = [
            'door-enter' => 'fa-solid fa-right-to-bracket',
            'log-in' => 'fa-solid fa-right-to-bracket',
            'id-badge' => 'fa-solid fa-id-card',
            'car' => 'fa-solid fa-car',
            'chart-line' => 'fa-solid fa-chart-line',
            'plus' => 'fa-solid fa-plus',
            'inbox' => 'fa-solid fa-inbox',
            'archive' => 'fa-solid fa-box-archive',
            'scale' => 'fa-solid fa-scale-balanced',
            'building-factory' => 'fa-solid fa-industry',
            'chart-bar' => 'fa-solid fa-chart-bar',
            'user-check' => 'fa-solid fa-user-check',
            'shield-check' => 'fa-solid fa-shield-halved',
            'file-text' => 'fa-solid fa-file-lines',
            'users' => 'fa-solid fa-users',
            'settings' => 'fa-solid fa-sliders',
            'briefcase' => 'fa-solid fa-briefcase',
            'anchor' => 'fa-solid fa-anchor',
            'user-search' => 'fa-solid fa-user-gear',
            'receipt' => 'fa-solid fa-receipt',
            'book' => 'fa-solid fa-book',
            'database' => 'fa-solid fa-database',
            'log-out' => 'fa-solid fa-right-from-bracket',
            'x' => 'fa-solid fa-xmark',
            'chevron-right' => 'fa-solid fa-chevron-right',
            'circle' => 'fa-regular fa-circle',
            'video' => 'fa-solid fa-video',
            'trending-up' => 'fa-solid fa-chart-line',
            'activity' => 'fa-solid fa-wave-square',
            'heart-pulse' => 'fa-solid fa-heart-pulse',
            'layers' => 'fa-solid fa-layer-group',
            'layout-grid' => 'fa-solid fa-table-cells-large',
            'clipboard-list' => 'fa-solid fa-clipboard-list',
            'box' => 'fa-solid fa-box',
            'contact' => 'fa-solid fa-address-card',
            'arrow-right' => 'fa-solid fa-arrow-right',
            'check-circle-2' => 'fa-solid fa-circle-check',
            'ship' => 'fa-solid fa-ship',
            'server' => 'fa-solid fa-server',
            'wallet' => 'fa-solid fa-wallet',
            'calculator' => 'fa-solid fa-calculator',
            
            // Themify map extensions
            'pencil-alt' => 'fa-solid fa-pen-to-square',
            'shield' => 'fa-solid fa-shield-halved',
            'package' => 'fa-solid fa-box',
            'clipboard' => 'fa-solid fa-clipboard-list',
            'user' => 'fa-solid fa-user',
            'key' => 'fa-solid fa-key',
            'eye' => 'fa-solid fa-eye',
            'na' => 'fa-solid fa-ban',
            'close' => 'fa-solid fa-rectangle-xmark'
        ];
        
        $mapped = $mappings[$icon] ?? null;
        if ($mapped) return $mapped;
        
        if (str_starts_with($icon, 'fa-')) {
            return 'fa-solid ' . $icon;
        }
        return 'fa-solid fa-' . $icon;
    }
}
?>

<aside class="sidebar collapsed" id="sidebar">
    <!-- Close button for mobile -->
    <button class="sm-close" id="sidebar-close-btn" title="Cerrar menu">
        <i class="fa-solid fa-xmark"></i>
    </button>
    
    <!-- Branding Header -->
    <a href="<?= APP_URL ?>/dashboard" class="sm-header-block" style="text-decoration: none;" title="Volver al Inicio">
        <div class="sm-icon" style="background: #ffffff !important; width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); padding:5px;">
            <img src="<?= APP_URL ?>/imgs/logoapm.png" alt="Logo APM" style="width:100%; height:100%; object-fit:contain;">
        </div>
        <div class="sm-title-text">
            <h2 style="font-family: var(--font-body); font-size: 18px; font-weight: 800; margin: 0; letter-spacing: -0.02em;">
                <span style="color: #38bdf8; font-weight: 800;">Sys</span>Port
            </h2>
            <p style="font-family: var(--font-body); font-size: 10px; font-weight: 600; margin: 2px 0 0 0; color: var(--text-muted); letter-spacing: 0.06em; text-transform: uppercase;">AUTORIDAD PORTUARIA</p>
        </div>
    </a>
    
    <!-- Module accordion navigation -->
    <div class="sidebar-mods" id="sidebarMods">
        <div class="sidebar-search">
            <i class="fa-solid fa-magnifying-glass sidebar-search-icon"></i>
            <input type="text" id="sidebarSearchInput" class="sidebar-search-input" placeholder="Buscar en el menú..." autocomplete="off" aria-label="Buscar en el menú">
            <button type="button" id="sidebarSearchClear" class="sidebar-search-clear" title="Limpiar búsqueda" style="display:none;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="sidebar-search-empty" id="sidebarSearchEmpty">Sin resultados para esa búsqueda.</div>
        <?php if (!empty($sidebarFocus)): ?>
            <a href="<?= APP_URL ?>/dashboard" data-no-spa
               style="display:flex;align-items:center;gap:8px;margin:0 6px 10px;padding:9px 12px;border-radius:10px;text-decoration:none;font-size:.78rem;font-weight:700;color:var(--text-app);background:var(--accent-app);border:1px solid var(--border-app);">
                <i class="fa-solid fa-arrow-left" style="font-size:.7rem;"></i> Portal APM — todos los módulos
            </a>
            <div class="sidebar-mods-title">MÓDULO ACTIVO</div>
        <?php else: ?>
            <div class="sidebar-mods-title">MÓDULOS CORPORATIVOS</div>
        <?php endif; ?>
        <?php
        // Color distintivo por módulo (id_modulo → color). Coincide con MenuController.
        $moduleColors = [
            1 => '#6f42c1', 2 => '#0056b3', 3 => '#dc3545', 4 => '#fd7e14',
            5 => '#20c997', 6 => '#17a2b8', 7 => '#343a40', 8 => '#8b5cf6',
            9 => '#0ea5e9', 10 => '#28a745', 11 => '#e83e8c', 12 => '#fd7e14',
            13 => '#0891b2',
        ];
        ?>
        <?php if (!empty($userMenu)): ?>
            <?php foreach ($userMenu as $modId => $mod): ?>
                <?php
                $modColor = $mod['color'] ?? ($moduleColors[(int)$modId] ?? '#1A3A5C');
                
                // Determine if this module/direction has any active child
                $isModActive = false;
                foreach ($mod['areas'] as $area) {
                    foreach ($area['items'] as $opt) {
                        if ($currentPath === '/' . ltrim($opt['url'] ?? '', '/')) {
                            $isModActive = true;
                            break 2;
                        }
                        foreach ($opt['children'] as $subopt) {
                            if ($currentPath === '/' . ltrim($subopt['url'] ?? '', '/')) {
                                $isModActive = true;
                                break 3;
                            }
                        }
                    }
                }
                if (!empty($sidebarFocus)) $isModActive = true; // modo módulo: siempre expandido

                // Un módulo con un único ítem navegable (sin hijos) se
                // muestra como link directo en vez de acordeón de un solo
                // elemento -- caso típico hoy para módulos embebidos recién
                // migrados a MOIS (TH/Bienes/Bitácoras), donde por defecto
                // solo el nodo "Inicio"/"Dashboard" tiene permiso otorgado
                // hasta que un admin reparta más nivel_crud por pantalla
                // desde /admin/roles. Con más de un ítem, o con hijos, se
                // mantiene el acordeón de siempre. Se desactiva en modo
                // "módulo activo" (sidebarFocus), donde siempre se quiere
                // ver el árbol expandido aunque tenga un solo ítem.
                $soloItem = null;
                $totalItems = 0;
                foreach ($mod['areas'] as $area) {
                    $totalItems += count($area['items']);
                    if ($totalItems > 1) break;
                    foreach ($area['items'] as $opt) {
                        if (empty($opt['children']) && !empty($opt['url'])) {
                            $soloItem = $opt;
                        }
                    }
                }
                $esLinkDirecto = empty($sidebarFocus) && $totalItems === 1 && $soloItem !== null;
                ?>
                <div class="sm-section" style="--mod-color: <?= $modColor ?>; --mod-color-alpha: <?= $modColor ?>20;">
                    <?php if ($esLinkDirecto):
                        $modUrl = APP_URL . '/' . ltrim($soloItem['url'], '/');
                        $modActiveDirecta = ($currentPath === '/' . ltrim($soloItem['url'], '/'));
                    ?>
                    <!-- Level 1: Módulo con un solo destino -- link directo, sin acordeón -->
                    <a class="sm-header <?= $modActiveDirecta ? 'active' : '' ?>" id="smhdr-<?= $modId ?>" href="<?= $modUrl ?>" <?= !empty($soloItem['spa']) ? 'data-spa' : 'data-no-spa' ?>
                       data-flyout-type="modulo"
                       data-id="<?= htmlspecialchars((string)($mod['id'] ?? '')) ?>"
                       data-title="<?= htmlspecialchars($mod['label'] ?? '') ?>"
                       data-icon="<?= normalizeFaIcon($mod['icon'] ?? '') ?>"
                       data-color="<?= $modColor ?>">
                        <div class="sm-icon" style="background: <?= $modColor ?> !important; box-shadow: 0 2px 8px <?= $modColor ?>40;">
                            <i class="<?= normalizeFaIcon($mod['icon'] ?? '') ?>" style="font-size:13px; color:#fff;"></i>
                        </div>
                        <span class="sm-name"><?= htmlspecialchars($mod['label'] ?? '') ?></span>
                    </a>
                    <?php else: ?>
                    <!-- Level 1: Module/Direction -->
                    <button class="sm-header <?= $isModActive ? 'active open' : '' ?>" id="smhdr-<?= $modId ?>" onclick="toggleSidebarModule('<?= $modId ?>')"
                            data-flyout-type="modulo"
                            data-id="<?= htmlspecialchars((string)($mod['id'] ?? '')) ?>"
                            data-title="<?= htmlspecialchars($mod['label'] ?? '') ?>"
                            data-icon="<?= normalizeFaIcon($mod['icon'] ?? '') ?>"
                            data-color="<?= $modColor ?>">
                        <div class="sm-icon" style="background: <?= $modColor ?> !important; box-shadow: 0 2px 8px <?= $modColor ?>40;">
                            <i class="<?= normalizeFaIcon($mod['icon'] ?? '') ?>" style="font-size:13px; color:#fff;"></i>
                        </div>
                        <span class="sm-name"><?= htmlspecialchars($mod['label'] ?? '') ?></span>
                        <i class="fa-solid fa-chevron-right sm-chevron"></i>
                    </button>

                    <div class="sm-tree <?= $isModActive ? 'open' : '' ?>" id="smtree-<?= $modId ?>">
                        <div class="sb-area" style="padding: 4px 6px;">
                            <?php $singleArea = count($mod['areas']) === 1; ?>
                            <!-- Level 2: Sub-area/Area (skipped when module has only one area) -->
                            <?php foreach ($mod['areas'] as $areaId => $area): ?>
                                <?php
                                $isAreaActive = false;
                                foreach ($area['items'] as $opt) {
                                    if ($currentPath === '/' . ltrim($opt['url'] ?? '', '/')) {
                                        $isAreaActive = true;
                                        break;
                                    }
                                    foreach ($opt['children'] as $subopt) {
                                        if ($currentPath === '/' . ltrim($subopt['url'] ?? '', '/')) {
                                            $isAreaActive = true;
                                            break 2;
                                        }
                                    }
                                }
                                if (!empty($sidebarFocus)) $isAreaActive = true; // modo módulo: áreas abiertas

                                // Área con una sola pantalla real (sin hijos) y sin
                                // hermanas: mostrarla como link directo en vez de
                                // acordeón de un solo elemento -- evita el doble click
                                // (expandir → click) para llegar a algo que es una sola
                                // pantalla, y evita repetir su nombre como encabezado Y
                                // como único hijo. Caso típico: la mayoría de pantallas
                                // de TH/Bienes/Bitácoras son áreas planas de 1 ítem.
                                $areaItems = $area['items'];
                                $areaSoloItem = (count($areaItems) === 1 && empty(reset($areaItems)['children']) && !empty(reset($areaItems)['url']))
                                    ? reset($areaItems) : null;
                                $areaEsLinkDirecto = !$singleArea && $areaSoloItem !== null;
                                ?>
                                <div style="margin-bottom: <?= $singleArea ? '2px' : '6px' ?>;">
                                    <?php if ($areaEsLinkDirecto):
                                        $areaUrl = APP_URL . '/' . ltrim($areaSoloItem['url'], '/');
                                        $areaActivaDirecta = ($currentPath === '/' . ltrim($areaSoloItem['url'], '/'));
                                    ?>
                                    <a class="sb-area-btn sb-area-link <?= $areaActivaDirecta ? 'open' : '' ?>" href="<?= $areaUrl ?>" <?= !empty($areaSoloItem['spa']) ? 'data-spa' : 'data-no-spa' ?>
                                       title="<?= htmlspecialchars($area['label'] ?? '') ?>"
                                       data-flyout-type="area"
                                       data-id="<?= htmlspecialchars((string)($area['id'] ?? '')) ?>"
                                       data-title="<?= htmlspecialchars($area['label'] ?? '') ?>"
                                       data-icon="<?= normalizeFaIcon($area['icon'] ?? 'circle') ?>"
                                       data-color="<?= $modColor ?>"
                                       data-parent="<?= htmlspecialchars($mod['label'] ?? '') ?>">
                                        <i class="<?= normalizeFaIcon($area['icon'] ?? 'circle') ?> sab-icon" style="font-size:11px;"></i>
                                        <span class="sab-label"><?= htmlspecialchars($area['label'] ?? '') ?></span>
                                    </a>
                                    </div>
                                    <?php continue; endif; ?>
                                    <?php if (!$singleArea): ?>
                                    <button class="sb-area-btn <?= $isAreaActive ? 'open' : '' ?>" id="sba-<?= $areaId ?>" onclick="toggleSidebarArea('<?= $areaId ?>')" title="<?= htmlspecialchars($area['label'] ?? '') ?>"
                                            data-flyout-type="area"
                                            data-id="<?= htmlspecialchars((string)($area['id'] ?? '')) ?>"
                                            data-title="<?= htmlspecialchars($area['label'] ?? '') ?>"
                                            data-icon="<?= normalizeFaIcon($area['icon'] ?? 'circle') ?>"
                                            data-color="<?= $modColor ?>"
                                            data-parent="<?= htmlspecialchars($mod['label'] ?? '') ?>">
                                        <i class="<?= normalizeFaIcon($area['icon'] ?? 'circle') ?> sab-icon" style="font-size:11px;"></i>
                                        <span class="sab-label"><?= htmlspecialchars($area['label'] ?? '') ?></span>
                                        <i class="fa-solid fa-chevron-right sab-chevron" style="font-size:9px;"></i>
                                    </button>
                                    <?php endif; ?>

                                    <div class="sb-items <?= ($singleArea || $isAreaActive) ? 'open' : '' ?>" id="sbi-<?= $areaId ?>">
                                        <!-- Level 3: Menu Option -->
                                        <?php foreach ($area['items'] as $opt): ?>
                                            <?php if (!empty($opt['children'])): ?>
                                                <?php 
                                                $isSubActive = false;
                                                foreach ($opt['children'] as $subopt) {
                                                    if ($currentPath === '/' . ltrim($subopt['url'] ?? '', '/')) {
                                                        $isSubActive = true;
                                                        break;
                                                    }
                                                }
                                                ?>
                                                <!-- Collapsible Level 3 Option -->
                                                <div style="margin-bottom: 4px;">
                                                    <button class="sb-subopt-btn <?= $isSubActive ? 'open' : '' ?>" onclick="toggleSidebarSubopt('<?= $opt['id'] ?>')" id="sbso-<?= $opt['id'] ?>">
                                                        <i class="<?= normalizeFaIcon($opt['icon'] ?? 'circle') ?> sso-icon"></i>
                                                        <span class="sso-label"><?= htmlspecialchars($opt['label']) ?></span>
                                                        <i class="fa-solid fa-chevron-right sso-chevron" style="font-size:8px;"></i>
                                                    </button>
                                                    <div class="sb-subitems <?= $isSubActive ? 'open' : '' ?>" id="sbsi-<?= $opt['id'] ?>">
                                                        <!-- Level 4: Links -->
                                                        <?php foreach ($opt['children'] as $subopt): ?>
                                                            <?php 
                                                            $subUrl = APP_URL . '/' . ltrim($subopt['url'] ?? '', '/');
                                                            $subActive = ($currentPath === '/' . ltrim($subopt['url'] ?? '', '/'));
                                                            ?>
                                                            <a href="<?= $subUrl ?>" class="sb-subitem <?= $subActive ? 'active' : '' ?>" <?= !empty($subopt['spa']) ? 'data-spa' : 'data-no-spa' ?> id="sit-<?= $subopt['id'] ?>"
                                                               data-flyout-type="item"
                                                               data-id="<?= htmlspecialchars($subopt['id']) ?>"
                                                               data-title="<?= htmlspecialchars($subopt['label']) ?>"
                                                               data-icon="<?= normalizeFaIcon($subopt['icon'] ?? 'circle') ?>"
                                                               data-color="<?= $modColor ?>"
                                                               data-parent="<?= htmlspecialchars($opt['label']) ?>">
                                                                <i class="<?= normalizeFaIcon($subopt['icon'] ?? 'circle') ?> sib-icon" style="font-size:8px; opacity:0.7; flex-shrink:0;"></i>
                                                                <span class="sib-label"><?= htmlspecialchars($subopt['label']) ?></span>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <!-- Simple Level 3 Link -->
                                                <?php 
                                                $linkUrl = APP_URL . '/' . ltrim($opt['url'] ?? '', '/');
                                                $isActive = ($currentPath === '/' . ltrim($opt['url'] ?? '', '/'));
                                                ?>
                                                <a href="<?= $linkUrl ?>" class="sb-item-btn sb-item <?= $isActive ? 'active' : '' ?>" <?= !empty($opt['spa']) ? 'data-spa' : 'data-no-spa' ?> id="sit-<?= $opt['id'] ?>"
                                                   data-flyout-type="item"
                                                   data-id="<?= htmlspecialchars($opt['id']) ?>"
                                                   data-title="<?= htmlspecialchars($opt['label']) ?>"
                                                   data-icon="<?= normalizeFaIcon($opt['icon'] ?? 'circle') ?>"
                                                   data-color="<?= $modColor ?>"
                                                   data-parent="<?= htmlspecialchars($area['label'] ?? '') ?>">
                                                    <i class="<?= normalizeFaIcon($opt['icon'] ?? 'circle') ?> sib-icon" style="font-size:10px; flex-shrink:0; opacity:0.8;"></i>
                                                    <span class="sib-label"><?= htmlspecialchars($opt['label']) ?></span>
                                                </a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!--
      Menú lateral 100% controlado por CORE_Menu_Nodos + permisos por rol.
      Administración (módulo 2) y Control de Bienes / Inventario (módulo 12)
      se renderizan dentro de la navegación dinámica de arriba.
      Deshabilitar un nodo en /admin/menu (estado=0) lo oculta para todos;
      los permisos por rol (/admin/roles) controlan quién ve cada nodo.
    -->

    <!-- User info at bottom -->
    <div class="sidebar-divider"></div>
    <div class="sidebar-user-card">
        <div class="sidebar-user-avatar">
            <?php
            $name = $_SESSION['nombre_completo'] ?? 'Usuario';
            $words = explode(' ', $name);
            $initials = '';
            foreach (array_slice($words, 0, 2) as $w) { $initials .= mb_strtoupper(mb_substr($w, 0, 1)); }
            echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');
            ?>
        </div>
        <div style="flex:1;min-width:0;">
            <div class="sidebar-user-name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="sidebar-user-role">
                <?php
                $nivel = (int)($_SESSION['nivel_jerarquia'] ?? 0);
                echo $nivel >= 3 ? 'Director / Gerente' : ($nivel >= 2 ? 'Jefatura' : ($nivel >= 1 ? 'Analista' : 'Operativo'));
                ?>
            </div>
        </div>
        <a href="<?= APP_URL ?>/logout" class="sidebar-logout-btn" title="Cerrar Sesión">
            <i class="fa-solid fa-right-from-bracket" style="font-size:13px;"></i>
        </a>
    </div>
</aside>

<!-- Hover details popover modal -->
<div id="sb-flyout" class="sb-flyout-card">
    <div class="sbf-icon" id="sbfIcon"><i class="fa-solid fa-info"></i></div>
    <div class="sbf-content">
        <div style="margin-bottom: 2px;"><span class="sbf-subtitle" id="sbfSubtitle">MÓDULOS</span></div>
        <div class="sbf-title" id="sbfTitle">Título del Ítem</div>
        <div class="sbf-description" id="sbfDescription">Descripción del módulo.</div>
    </div>
</div>

<style>
    /* ═══════════════════════════════════════════════
       SIDEBAR — Premium Theme-Adaptive v3
       Uses CSS variables — works across all 3 themes
    ═══════════════════════════════════════════════ */

    /* Reset the hardcoded dark background; respect CSS variable theme system */
    .sidebar {
        background: var(--bg-sidebar) !important;
        border-right: 1px solid var(--border-app) !important;
        color: var(--text-app);
        transition: width 0.28s cubic-bezier(0.16, 1, 0.3, 1);
        z-index: 1000;
        display: flex;
        flex-direction: column;
    }

    /* ════════════════════════════════════════════
       LEVEL 2 — Area section headers
       Uppercase label + accent bar on open
    ════════════════════════════════════════════ */
    .sb-area-btn {
        position: relative;
        width: 100%;
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 5px 8px 5px 13px;
        background: transparent;
        border: none;
        cursor: pointer;
        text-align: left;
        border-radius: 6px;
        color: var(--text-muted);
        font-size: 10.5px;
        font-weight: 700;
        font-family: var(--font-body);
        letter-spacing: 0.05em;
        text-transform: uppercase;
        transition: background var(--ease), color var(--ease);
    }
    /* Animated left accent bar */
    .sb-area-btn::after {
        content: '';
        position: absolute;
        left: 2px;
        top: 50%;
        transform: translateY(-50%);
        width: 2px;
        height: 0;
        border-radius: 2px;
        background: var(--mod-color, var(--primary-hover));
        transition: height 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .sb-area-btn:hover::after { height: 10px; }
    .sb-area-btn.open::after  { height: 18px; }

    /* Área aplanada a link directo (1 sola pantalla, sin hermanas) --
       misma caja visual que el acordeón, pero <a> navegable y sin chevron. */
    .sb-area-link { text-decoration: none; cursor: pointer; }

    .sb-area-btn:hover {
        background: color-mix(in srgb, var(--mod-color, var(--primary-hover)) 7%, var(--accent-app));
        color: var(--text-app);
    }
    .sb-area-btn.open {
        color: var(--mod-color, var(--primary-hover));
        background: color-mix(in srgb, var(--mod-color, var(--primary-hover)) 8%, transparent);
        box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--mod-color, var(--primary-hover)) 16%, transparent);
    }

    /* Area icon box */
    .sab-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 19px;
        height: 19px;
        border-radius: 5px;
        font-size: 9px;
        flex-shrink: 0;
        transition: all var(--ease);
        background: var(--accent-app);
        border: 1px solid var(--border-app);
        color: var(--text-muted);
    }
    .sb-area-btn:hover .sab-icon,
    .sb-area-btn.open  .sab-icon {
        background: color-mix(in srgb, var(--mod-color, var(--primary-hover)) 14%, transparent);
        border-color: color-mix(in srgb, var(--mod-color, var(--primary-hover)) 35%, transparent);
        color: var(--mod-color, var(--primary-hover));
    }

    .sab-label { flex: 1; white-space: normal; word-break: break-word; line-height: 1.3; }
    .sab-chevron {
        margin-left: auto;
        color: var(--text-muted);
        font-size: 8px;
        flex-shrink: 0;
        transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), color var(--ease);
    }
    .sb-area-btn.open .sab-chevron {
        transform: rotate(90deg);
        color: var(--mod-color, var(--primary-hover));
    }

    /* Level 2 container */
    .sb-items {
        display: block;
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        padding: 0;
        transition: max-height 0.28s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease, padding 0.28s ease;
    }
    .sb-items.open {
        max-height: 1200px;
        opacity: 1;
        padding: 2px 0 3px;
    }

    /* ════════════════════════════════════════════
       LEVEL 3 — Direct option links
    ════════════════════════════════════════════ */
    .sb-item-btn {
        position: relative;
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 5.5px 10px 5.5px 22px;
        background: transparent;
        border: none;
        cursor: pointer;
        text-align: left;
        border-radius: 6px;
        color: var(--text-muted);
        font-size: 12px;
        font-family: var(--font-body);
        font-weight: 500;
        text-decoration: none;
        width: 100%;
        transition: background var(--ease), color var(--ease), padding-left 0.18s ease;
    }
    .sb-item-btn:hover {
        background: var(--accent-app);
        color: var(--text-app);
        padding-left: 25px;
    }
    .sb-item-btn.active {
        background: color-mix(in srgb, var(--mod-color, var(--primary-hover)) 10%, transparent) !important;
        color: var(--mod-color, var(--primary-hover)) !important;
        font-weight: 600;
        padding-left: 25px;
    }
    /* Active left bar */
    .sb-item-btn.active::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 14px;
        border-radius: 2px;
        background: var(--mod-color, var(--primary-hover));
    }

    /* Level 3 icon for direct links */
    .sb-item-btn .sib-icon {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        width: 18px !important;
        height: 18px !important;
        border-radius: 5px;
        font-size: 9px !important;
        flex-shrink: 0;
        opacity: 1 !important;
        background: var(--accent-app);
        border: 1px solid var(--border-app);
        color: var(--text-muted);
        transition: all var(--ease);
    }
    .sb-item-btn:hover .sib-icon,
    .sb-item-btn.active .sib-icon {
        background: color-mix(in srgb, var(--mod-color, var(--primary-hover)) 14%, transparent);
        border-color: color-mix(in srgb, var(--mod-color, var(--primary-hover)) 32%, transparent);
        color: var(--mod-color, var(--primary-hover));
    }

    /* ════════════════════════════════════════════
       LEVEL 3 — Collapsible option buttons
    ════════════════════════════════════════════ */
    .sb-subopt-btn {
        position: relative;
        width: 100%;
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 5.5px 10px 5.5px 22px;
        background: transparent;
        border: none;
        cursor: pointer;
        text-align: left;
        border-radius: 6px;
        color: var(--text-muted);
        font-size: 12px;
        font-family: var(--font-body);
        font-weight: 500;
        transition: background var(--ease), color var(--ease), padding-left 0.18s ease;
    }
    .sb-subopt-btn:hover {
        background: var(--accent-app);
        color: var(--text-app);
        padding-left: 25px;
    }
    .sb-subopt-btn.open {
        color: var(--mod-color, var(--primary-hover));
        background: color-mix(in srgb, var(--mod-color, var(--primary-hover)) 7%, transparent);
        padding-left: 25px;
    }
    .sb-subopt-btn.open .sso-chevron {
        transform: rotate(90deg);
        color: var(--mod-color, var(--primary-hover));
    }
    .sso-chevron {
        margin-left: auto;
        flex-shrink: 0;
        font-size: 8px;
        color: var(--text-muted);
        transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), color var(--ease);
    }
    .sso-label { flex: 1; white-space: normal; word-break: break-word; line-height: 1.3; }

    /* Level 3 icon box (collapsible) */
    .sso-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
        width: 18px;
        height: 18px;
        border-radius: 5px;
        background: var(--accent-app);
        border: 1px solid var(--border-app);
        color: var(--text-muted);
        flex-shrink: 0;
        transition: all var(--ease);
    }
    .sb-subopt-btn:hover .sso-icon,
    .sb-subopt-btn.open  .sso-icon {
        background: color-mix(in srgb, var(--mod-color, var(--primary-hover)) 14%, transparent);
        border-color: color-mix(in srgb, var(--mod-color, var(--primary-hover)) 32%, transparent);
        color: var(--mod-color, var(--primary-hover));
    }

    /* ════════════════════════════════════════════
       LEVEL 4 — Sub-items (deepest level)
    ════════════════════════════════════════════ */
    .sb-subitems {
        display: block;
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        padding: 0 0 0 6px;
        margin: 0 0 0 26px;
        border-left: 1.5px solid transparent;
        transition: max-height 0.26s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease, padding 0.26s ease, border-color 0.26s ease;
    }
    .sb-subitems.open {
        max-height: 800px;
        opacity: 1;
        padding: 2px 0 4px 6px;
        margin: 1px 0 2px 26px;
        border-left-color: color-mix(in srgb, var(--mod-color, var(--border-app)) 22%, var(--border-app));
    }

    .sb-subitem {
        position: relative;
        display: flex;
        align-items: center;
        gap: 7px;
        padding: 4.5px 8px 4.5px 10px;
        color: var(--text-muted);
        text-decoration: none;
        border-radius: 5px;
        font-size: 11.5px;
        font-family: var(--font-body);
        font-weight: 400;
        transition: background var(--ease), color var(--ease), padding-left 0.18s ease;
    }
    .sb-subitem:hover {
        color: var(--text-app);
        background: var(--accent-app);
        padding-left: 14px;
    }
    .sb-subitem.active {
        color: var(--mod-color, var(--primary-hover));
        font-weight: 600;
        background: color-mix(in srgb, var(--mod-color, var(--primary-hover)) 9%, transparent);
        padding-left: 14px;
    }
    /* Active dot indicator for L4 */
    .sb-subitem.active::before {
        content: '';
        position: absolute;
        left: 4px;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: var(--mod-color, var(--primary-hover));
    }

    /* L4 icon */
    .sb-subitem .sib-icon {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        font-size: 7px !important;
        width: 14px !important;
        height: 14px !important;
        border-radius: 4px;
        border: 1px solid var(--border-app);
        color: var(--text-muted);
        flex-shrink: 0;
        opacity: 1 !important;
        background: transparent;
        transition: all var(--ease);
    }
    .sb-subitem:hover .sib-icon,
    .sb-subitem.active .sib-icon {
        border-color: color-mix(in srgb, var(--mod-color, var(--primary-hover)) 35%, transparent);
        color: var(--mod-color, var(--primary-hover));
    }

    /* Animations */
    @keyframes sbReveal {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ── Flyout popover card ── */
    .sb-flyout-card {
        position: fixed;
        z-index: 99999;
        width: 280px;
        padding: 14px 16px;
        display: flex;
        gap: 12px;
        align-items: flex-start;
        transition: opacity 0.2s ease, transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
        transform: translateX(-8px);
        opacity: 0;
        pointer-events: none;
        border-radius: 12px;
        background: rgba(10, 16, 32, 0.97);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 20px 56px rgba(0, 0, 0, 0.55), 0 4px 16px rgba(0, 0, 0, 0.3);
        color: #E2E8F0;
    }
    .sb-flyout-card.show {
        opacity: 1;
        pointer-events: auto;
        transform: translateX(0);
    }

    .sbf-content { flex: 1; min-width: 0; }

    .sbf-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
    }

    .sbf-subtitle {
        font-family: var(--font-body);
        font-size: 9px;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #475569;
        margin-bottom: 3px;
    }

    .sbf-title {
        font-family: var(--font-body);
        font-size: 13.5px;
        font-weight: 700;
        margin-bottom: 4px;
        line-height: 1.3;
        color: #38BDF8;
    }

    .sbf-description {
        font-family: var(--font-body);
        font-size: 11px;
        line-height: 1.5;
        color: #94A3B8;
    }

    /* ── User card at bottom ── */
    .sidebar-user-card {
        padding: 11px 13px;
        display: flex;
        align-items: center;
        gap: 10px;
        background: var(--accent-app);
        border-radius: 10px;
        margin: 4px 10px;
        border: 1px solid var(--border-app);
        transition: background var(--ease);
    }
    .sidebar-user-card:hover {
        background: color-mix(in srgb, var(--accent-app) 60%, var(--primary-hover) 6%);
    }

    .sidebar-user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: linear-gradient(135deg, var(--primary-app), var(--primary-hover));
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        font-family: var(--font-body);
        flex-shrink: 0;
        box-shadow: 0 2px 8px color-mix(in srgb, var(--primary-hover) 35%, transparent);
    }

    .sidebar-user-name {
        font-size: 12.5px;
        font-weight: 600;
        color: var(--text-app, var(--text-color));
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-family: var(--font-body);
        line-height: 1.2;
    }

    .sidebar-user-role {
        font-size: 10px;
        color: var(--text-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-family: var(--font-body);
        font-weight: 500;
        margin-top: 2px;
    }

    .sidebar-logout-btn {
        width: 26px;
        height: 26px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        flex-shrink: 0;
        transition: all var(--ease);
        text-decoration: none;
    }
    .sidebar-logout-btn:hover {
        background: rgba(239, 68, 68, 0.14);
        color: #EF4444;
    }

    /* ═══════════════════════════════════════════════
       Buscador del menú
    ═══════════════════════════════════════════════ */
    .sidebar-search {
        position: sticky;
        top: 0;
        z-index: 6;
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0 8px 12px;
        padding: 9px 11px;
        background: color-mix(in srgb, var(--accent-app) 92%, transparent);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 1px solid var(--border-app);
        border-radius: 10px;
        transition: border-color var(--ease), box-shadow var(--ease);
    }
    .sidebar-search:focus-within {
        border-color: color-mix(in srgb, var(--primary-hover) 55%, var(--border-app));
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary-hover) 16%, transparent);
    }
    .sidebar-search-icon { font-size: 11px; color: var(--text-muted); flex-shrink: 0; }
    .sidebar-search-input {
        flex: 1;
        min-width: 0;
        border: none;
        background: transparent;
        outline: none;
        font-family: var(--font-body);
        font-size: 12.5px;
        font-weight: 500;
        color: var(--text-app);
    }
    .sidebar-search-input::placeholder { color: var(--text-muted); opacity: .85; }
    .sidebar-search-clear {
        border: none;
        background: transparent;
        color: var(--text-muted);
        cursor: pointer;
        font-size: 10px;
        width: 20px;
        height: 20px;
        border-radius: 5px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--ease);
    }
    .sidebar-search-clear:hover { color: var(--text-app); background: var(--border-app); }
    .sidebar-search-empty {
        display: none;
        padding: 10px 16px 4px;
        font-size: 11.5px;
        color: var(--text-muted);
        text-align: center;
        font-style: italic;
    }
    .sidebar-search-empty.show { display: block; }

    /* Durante la búsqueda, todos los niveles de árbol quedan expandidos --
       el filtrado real ocurre ocultando (display:none) los <a> que no
       matchean; los contenedores forzados abiertos solo muestran lo que
       quedó visible adentro. */
    .sidebar-mods.searching .sm-tree,
    .sidebar-mods.searching .sb-items,
    .sidebar-mods.searching .sb-subitems {
        max-height: none !important;
        opacity: 1 !important;
        overflow: visible !important;
        transition: none !important;
    }
    .sidebar-mods.searching .sm-tree { padding: 2px 0 !important; }
    .sidebar-mods.searching .sb-items { padding: 2px 0 3px !important; }
    .sidebar-mods.searching .sb-subitems {
        padding: 2px 0 4px 6px !important;
        margin: 1px 0 2px 26px !important;
        border-left-color: color-mix(in srgb, var(--mod-color, var(--border-app)) 22%, var(--border-app)) !important;
    }
    .sidebar-mods.searching .sm-chevron,
    .sidebar-mods.searching .sab-chevron,
    .sidebar-mods.searching .sso-chevron {
        transform: rotate(90deg);
    }

    /* ═══════════════════════════════════════════════
       Jerarquía visual reforzada -- tarjeta por módulo,
       separación real entre secciones sin tocar colores.
    ═══════════════════════════════════════════════ */
    .sm-section {
        margin: 0 6px 4px;
        border-radius: 12px;
        transition: background var(--ease);
    }
    .sm-header.open {
        border-radius: 10px 10px 0 0;
    }
    .sm-tree.open {
        background: color-mix(in srgb, var(--mod-color, var(--primary-hover)) 3%, transparent);
        border-radius: 0 0 10px 10px;
        padding-bottom: 6px !important;
    }
    .sm-name { letter-spacing: -0.005em; }

    /* ── Sidebar divider ── */
    .sidebar-divider {
        height: 1px;
        background: var(--border-app);
        margin: 8px 14px;
        opacity: 0.6;
    }

    /* ── Foco accesible + feedback táctil ── */
    .sm-header:focus-visible,
    .sb-area-btn:focus-visible,
    .sb-subopt-btn:focus-visible,
    .sb-item-btn:focus-visible,
    .sb-item:focus-visible,
    .sb-subitem:focus-visible,
    .sm-header-block:focus-visible,
    .sidebar-logout-btn:focus-visible {
        outline: 2px solid var(--mod-color, var(--primary-hover));
        outline-offset: 2px;
        border-radius: 6px;
    }
    .sm-header:active,
    .sb-area-btn:active,
    .sb-subopt-btn:active,
    .sb-item-btn:active,
    .sb-item:active,
    .sb-subitem:active {
        transform: scale(0.98);
    }
</style>

<script>
    // Collapse/Expand operations for all levels
    window.toggleSidebarModule = function(modId) {
        const btn = document.getElementById('smhdr-' + modId);
        const tree = document.getElementById('smtree-' + modId);
        if (!btn || !tree) return;

        const isOpen = tree.classList.contains('open');

        // Accordion: collapse all other module trees
        document.querySelectorAll('.sm-header').forEach(h => {
            if (h.id !== 'smhdr-' + modId) h.classList.remove('open');
        });
        document.querySelectorAll('.sm-tree').forEach(t => {
            if (t.id !== 'smtree-' + modId) t.classList.remove('open');
        });

        if (isOpen) {
            btn.classList.remove('open');
            tree.classList.remove('open');
            // Recordar que el usuario cerró todo — no re-abrir por URL al recargar.
            localStorage.setItem('apm_sidebar_open_module', '');
        } else {
            btn.classList.add('open');
            tree.classList.add('open');
            // Auto-expand all area panels so links are immediately visible
            tree.querySelectorAll('.sb-area-btn').forEach(a => a.classList.add('open'));
            tree.querySelectorAll('.sb-items').forEach(s => s.classList.add('open'));
            localStorage.setItem('apm_sidebar_open_module', modId);
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();
    };

    window.toggleSidebarArea = function(areaId) {
        const btn = document.getElementById('sba-' + areaId);
        const items = document.getElementById('sbi-' + areaId);
        if (!btn || !items) return;
        
        const isOpen = items.classList.contains('open');
        if (isOpen) {
            btn.classList.remove('open');
            items.classList.remove('open');
        } else {
            btn.classList.add('open');
            items.classList.add('open');
        }
    };

    window.toggleSidebarSubopt = function(optId) {
        const btn = document.getElementById('sbso-' + optId);
        const subitems = document.getElementById('sbsi-' + optId);
        if (!btn || !subitems) return;
        
        const isOpen = subitems.classList.contains('open');
        if (isOpen) {
            btn.classList.remove('open');
            subitems.classList.remove('open');
        } else {
            btn.classList.add('open');
            subitems.classList.add('open');
        }
    };

    document.addEventListener("DOMContentLoaded", function() {
        const sb = document.getElementById('sidebar');
        const flyout = document.getElementById('sb-flyout');
        if (!sb || !flyout) return;

        // Force move flyout to document.body
        document.body.appendChild(flyout);

        const descriptions = {
            'JURIDICA': 'Dirección encargada del asesoramiento legal y normativo, garantizando la seguridad jurídica y validez contractual de todas las operaciones portuarias.',
            'TH': 'Gestión estratégica del capital humano de la autoridad portuaria, administración de nóminas, planes de capacitación, fichas ocupacionales y desarrollo organizacional.',
            'GERENCIA': 'Alta dirección corporativa y toma de decisiones estratégicas. Monitoreo en tiempo real de KPI operativos, financieros y comerciales para el puerto.',
            'ADMIN': 'Dirección administrativa responsable del control de recursos físicos, activos portuarios, logística de suministros y eficiencia operativa interna.',
            'FINANCIERO': 'Supervisión y control presupuestario integral, tesorería, contabilidad gubernamental, y reportes de rentabilidad y sostenibilidad económica del terminal.',
            'PORTAL': 'Acceso centralizado a los módulos operativos, consultas rápidas de buques, simulador de tasas y configuraciones del sistema.',
            'INFRA': 'Coordinación y supervisión de infraestructuras portuarias, control de acceso físico y automatizado, y fiscalización técnica de obras.',
            'INFRA_CCTV': 'Centro de control de cámaras de videovigilancia perimetral en tiempo real en los muelles de atraque y hangares.',
            'INFRA_SECRETARIA': 'Control documental institucional, digitalización de memorandos, resoluciones y agenda de gerencia general.',
            'INFRA_JEFATURA': 'Gestión jerárquica de aprobaciones de pases de seguridad y control de personal externo autorizado.',
            'INFRA_INSPECTORES': 'Supervisión física de campo de actividades portuarias y operaciones de estiba.',
            'TH_NOMINA': 'Procesamiento mensual de sueldos, aportes al IESS, retenciones de ley, horas extras y décimos de colaboradores.',
            'PLANIF_TIC': 'Área de tecnología responsable de la administración de la red, infraestructura de servidores, base de datos y soporte informático.'
        };

        function normalizeFaIconJS(icon) {
            if (!icon) return 'fa-folder';
            if (icon.startsWith('ti-')) icon = icon.substring(3);
            if (icon.startsWith('fa-')) return icon;
            const mappings = {
                'door-enter': 'fa-right-to-bracket',
                'log-in': 'fa-right-to-bracket',
                'id-badge': 'fa-id-card',
                'car': 'fa-car',
                'chart-line': 'fa-chart-line',
                'plus': 'fa-plus',
                'inbox': 'fa-inbox',
                'archive': 'fa-box-archive',
                'scale': 'fa-scale-balanced',
                'building-factory': 'fa-industry',
                'chart-bar': 'fa-chart-bar',
                'user-check': 'fa-user-check',
                'shield-check': 'fa-shield-halved',
                'file-text': 'fa-file-lines',
                'users': 'fa-users',
                'settings': 'fa-sliders',
                'briefcase': 'fa-briefcase',
                'anchor': 'fa-anchor',
                'user-search': 'fa-user-gear',
                'receipt': 'fa-receipt',
                'book': 'fa-book',
                'database': 'fa-database',
                'log-out': 'fa-right-from-bracket',
                'x': 'fa-xmark',
                'chevron-right': 'fa-chevron-right',
                'circle': 'fa-circle',
                'video': 'fa-video',
                'trending-up': 'fa-chart-line',
                'activity': 'fa-wave-square',
                'heart-pulse': 'fa-heart-pulse',
                'layers': 'fa-layer-group',
                'layout-grid': 'fa-table-cells-large',
                'clipboard-list': 'fa-clipboard-list',
                'box': 'fa-box',
                'contact': 'fa-address-card',
                'arrow-right': 'fa-arrow-right',
                'check-circle-2': 'fa-circle-check',
                'ship': 'fa-ship',
                'server': 'fa-server',
                'wallet': 'fa-wallet',
                'calculator': 'fa-calculator'
            };
            const mapped = mappings[icon] || icon;
            return mapped.startsWith('fa-') ? mapped : 'fa-' + mapped;
        }

        sb.addEventListener('mouseover', (e) => {
            const btn = e.target.closest('.sm-header, .sb-area-btn, .sb-subopt-btn, .sb-subitem, .sb-item-btn, .sb-item');
            if (!btn) {
                flyout.classList.remove('show');
                return;
            }

            const type = btn.getAttribute('data-flyout-type') || 'modulo';
            const id = btn.getAttribute('data-id');
            const title = btn.getAttribute('data-title');
            let icon = btn.getAttribute('data-icon');
            const color = btn.getAttribute('data-color');
            const parent = btn.getAttribute('data-parent');

            if (!id || !title) return;

            const desc = descriptions[id] || descriptions[title] || 'Acceso rápido y seguro al formulario del sistema SysPort.';

            const sbfIcon = document.getElementById('sbfIcon');
            const sbfTitle = document.getElementById('sbfTitle');
            const sbfSubtitle = document.getElementById('sbfSubtitle');
            const sbfDescription = document.getElementById('sbfDescription');

            if (sbfIcon) {
                sbfIcon.style.background = color || '#1E3A8A';
                sbfIcon.innerHTML = `<i class="fa-solid ${normalizeFaIconJS(icon)}"></i>`;
            }

            if (sbfTitle) sbfTitle.textContent = title;
            
            if (sbfSubtitle) {
                if (type === 'modulo') {
                    sbfSubtitle.textContent = 'MÓDULO CORPORATIVO';
                } else if (type === 'area') {
                    sbfSubtitle.textContent = `Área · ${parent || ''}`;
                } else if (type === 'item') {
                    sbfSubtitle.textContent = `Menú · ${parent || ''}`;
                }
            }

            if (sbfDescription) sbfDescription.textContent = desc;

            const btnRect = btn.getBoundingClientRect();
            const x = btnRect.right + 10;
            
            flyout.classList.add('show');
            const flyoutHeight = flyout.offsetHeight || 140;
            let y = btnRect.top + (btnRect.height / 2) - (flyoutHeight / 2);
            
            const margin = 10;
            if (y < margin) y = margin;
            if (y + flyoutHeight > window.innerHeight - margin) {
                y = window.innerHeight - flyoutHeight - margin;
            }

            flyout.style.left = `${x}px`;
            flyout.style.top = `${y}px`;
        });

        sb.addEventListener('mouseleave', () => {
            flyout.classList.remove('show');
        });

        // ── Buscador del menú lateral ──────────────────────────────────
        // Filtra por texto entre los 76+ nodos reales sin depender del
        // estado open/collapsed normal: fuerza expansión de contenedores
        // (clase .searching, ver CSS) y oculta con display:none los <a>
        // que no matchean. Al limpiar, restaura el estado open/closed que
        // había antes de empezar a escribir (incluida la preferencia
        // persistida de apm_sidebar_open_module).
        const sidebarMods = document.getElementById('sidebarMods');
        const searchInput = document.getElementById('sidebarSearchInput');
        const searchClear = document.getElementById('sidebarSearchClear');
        const searchEmpty = document.getElementById('sidebarSearchEmpty');

        if (sidebarMods && searchInput) {
            let savedOpenState = null;

            const normalize = (s) => (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

            function restoreState() {
                sidebarMods.classList.remove('searching');
                sidebarMods.querySelectorAll('[data-search-touched]').forEach(el => {
                    el.style.display = '';
                    el.removeAttribute('data-search-touched');
                });
                if (savedOpenState) {
                    savedOpenState.forEach(({ el, open }) => el.classList.toggle('open', open));
                    savedOpenState = null;
                }
                if (searchEmpty) searchEmpty.classList.remove('show');
                if (searchClear) searchClear.style.display = 'none';
            }

            function applyFilter(rawQuery) {
                const q = normalize(rawQuery.trim());

                if (q === '') { restoreState(); return; }

                if (searchClear) searchClear.style.display = '';

                if (!sidebarMods.classList.contains('searching')) {
                    savedOpenState = Array.from(
                        sidebarMods.querySelectorAll('.sm-header, .sm-tree, .sb-area-btn, .sb-items, .sb-subopt-btn, .sb-subitems')
                    ).map(el => ({ el, open: el.classList.contains('open') }));
                    sidebarMods.classList.add('searching');
                }

                const leaves = Array.from(sidebarMods.querySelectorAll('a[href]'));
                let anyVisible = false;

                leaves.forEach(leaf => {
                    const label = leaf.querySelector('.sib-label, .sm-name, .sso-label, .sab-label');
                    const text = normalize(label ? label.textContent : leaf.textContent);
                    const match = text.includes(q);
                    leaf.style.display = match ? '' : 'none';
                    leaf.setAttribute('data-search-touched', '1');
                    if (match) anyVisible = true;
                });

                // Oculta secciones de módulo completas sin ningún resultado.
                // Oculta filas de grupo-acordeón (área nivel 2 / opción nivel 3
                // colapsable) que quedaron sin ningún <a> visible adentro --
                // si no, se ve un encabezado expandido "vacío" (solo aplica a
                // los que son <button> con hermano .sb-items/.sb-subitems; los
                // que son <a> directos ya se ocultan solos arriba, son leaves).
                sidebarMods.querySelectorAll('.sb-area-btn:not(.sb-area-link), .sb-subopt-btn').forEach(btn => {
                    const wrap = btn.parentElement.querySelector(':scope > .sb-items, :scope > .sb-subitems');
                    const row = btn.parentElement;
                    const hasVisible = wrap ? Array.from(wrap.querySelectorAll('a[href]')).some(a => a.style.display !== 'none') : false;
                    row.style.display = hasVisible ? '' : 'none';
                    row.setAttribute('data-search-touched', '1');
                });

                sidebarMods.querySelectorAll('.sm-section').forEach(section => {
                    const hasVisible = Array.from(section.querySelectorAll('a[href]'))
                        .some(a => a.style.display !== 'none');
                    section.style.display = hasVisible ? '' : 'none';
                    section.setAttribute('data-search-touched', '1');
                });

                if (searchEmpty) searchEmpty.classList.toggle('show', !anyVisible);
            }

            searchInput.addEventListener('input', () => applyFilter(searchInput.value));
            if (searchClear) {
                searchClear.addEventListener('click', () => {
                    searchInput.value = '';
                    restoreState();
                    searchInput.focus();
                });
            }
        }
    });
</script>
