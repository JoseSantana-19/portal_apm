<?php
/**
 * Sidebar Component — SysPort APM Ultra-Elite Edition
 * 4-Level hierarchical MOIS navigation with:
 * - Seamless Integrated Header Branding (No cut-out box)
 * - Quick Search (Ctrl+K) with Real-Time Keyword Text Highlighting (<mark>)
 * - Recent Navigation History Pills (Auto-recorded per session)
 * - Pinned Favorites System (localStorage)
 * - Clean Typography without sub-item icon clutter
 * - Crisp, focused active indicator strictly on the currently visited screen
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

// Contador de notificaciones no leídas para badge dinámico
$unreadNotifsCount = 0;
try {
    $db = Database::getInstance();
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $rowUnread = $db->fetch($db->query(
        'SELECT COUNT(*) AS total FROM CORE_Notificaciones WHERE (id_usuario=? OR id_usuario IS NULL) AND leida=0',
        [[$uid, SQLSRV_PARAM_IN]]
    ));
    $unreadNotifsCount = (int)($rowUnread['total'] ?? 0);
} catch (Throwable $e) {
    $unreadNotifsCount = 0;
}

function apm_sidebar_link(string $appUrl, string $rawUrl): string {
    $rawUrl = ltrim($rawUrl, '/');
    if (str_starts_with($rawUrl, 'apps/')) {
        return $appUrl . '/ir?destino=' . rawurlencode($rawUrl);
    }
    return $appUrl . '/' . $rawUrl;
}

/* Modo módulo activo (enfoque individual para subsistemas integrados) */
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
    $__prefijosModulo = [
        '/apps/talento_humano' => 11, '/apps/control_bienes' => 12, '/apps/bitacoras' => 13,
        '/panel/talento-humano' => 11, '/panel/bienes' => 12,
    ];
    foreach ($__prefijosModulo as $__p => $__m) {
        if ($currentPath === $__p || str_starts_with($currentPath, $__p . '/')) { $sidebarFocusId = $__m; break; }
    }
}
$__modulosApartado = [11, 12, 13];
if ($sidebarFocusId !== null && (!in_array($sidebarFocusId, $__modulosApartado, true) || !isset($userMenu[$sidebarFocusId]))) {
    $sidebarFocusId = null;
}
$sidebarFocus = ($sidebarFocusId !== null);
if ($sidebarFocus) {
    $userMenu = [$sidebarFocusId => $userMenu[$sidebarFocusId]];
}

if (!function_exists('normalizeFaIcon')) {
    function normalizeFaIcon(?string $icon): string {
        if (empty($icon)) return 'fa-solid fa-folder';
        if (str_starts_with($icon, 'ti-')) { $icon = substr($icon, 3); }
        $mappings = [
            'door-enter' => 'fa-solid fa-right-to-bracket', 'log-in' => 'fa-solid fa-right-to-bracket',
            'id-badge' => 'fa-solid fa-id-card', 'car' => 'fa-solid fa-car',
            'chart-line' => 'fa-solid fa-chart-line', 'plus' => 'fa-solid fa-plus',
            'inbox' => 'fa-solid fa-inbox', 'archive' => 'fa-solid fa-box-archive',
            'scale' => 'fa-solid fa-scale-balanced', 'building-factory' => 'fa-solid fa-industry',
            'chart-bar' => 'fa-solid fa-chart-bar', 'user-check' => 'fa-solid fa-user-check',
            'shield-check' => 'fa-solid fa-shield-halved', 'file-text' => 'fa-solid fa-file-lines',
            'users' => 'fa-solid fa-users', 'settings' => 'fa-solid fa-sliders',
            'briefcase' => 'fa-solid fa-briefcase', 'anchor' => 'fa-solid fa-anchor',
            'user-search' => 'fa-solid fa-user-gear', 'receipt' => 'fa-solid fa-receipt',
            'book' => 'fa-solid fa-book', 'database' => 'fa-solid fa-database',
            'log-out' => 'fa-solid fa-right-from-bracket', 'x' => 'fa-solid fa-xmark',
            'chevron-right' => 'fa-solid fa-chevron-right', 'circle' => 'fa-regular fa-circle',
            'video' => 'fa-solid fa-video', 'trending-up' => 'fa-solid fa-chart-line',
            'activity' => 'fa-solid fa-wave-square', 'heart-pulse' => 'fa-solid fa-heart-pulse',
            'layers' => 'fa-solid fa-layer-group', 'layout-grid' => 'fa-solid fa-table-cells-large',
            'clipboard-list' => 'fa-solid fa-clipboard-list', 'box' => 'fa-solid fa-box',
            'contact' => 'fa-solid fa-address-card', 'arrow-right' => 'fa-solid fa-arrow-right',
            'check-circle-2' => 'fa-solid fa-circle-check', 'ship' => 'fa-solid fa-ship',
            'server' => 'fa-solid fa-server', 'wallet' => 'fa-solid fa-wallet',
            'calculator' => 'fa-solid fa-calculator', 'pencil-alt' => 'fa-solid fa-pen-to-square',
            'shield' => 'fa-solid fa-shield-halved', 'package' => 'fa-solid fa-box',
            'clipboard' => 'fa-solid fa-clipboard-list', 'user' => 'fa-solid fa-user',
            'key' => 'fa-solid fa-key', 'eye' => 'fa-solid fa-eye',
            'bell' => 'fa-solid fa-bell', 'cubes' => 'fa-solid fa-cubes-stacked'
        ];
        $mapped = $mappings[$icon] ?? null;
        if ($mapped) return $mapped;
        if (str_starts_with($icon, 'fa-')) return 'fa-solid ' . $icon;
        return 'fa-solid fa-' . $icon;
    }
}
?>

<aside class="sidebar" id="sidebar">
    <!-- Close button for mobile -->
    <button class="sm-close" id="sidebar-close-btn" title="Cerrar menú">
        <i class="fa-solid fa-xmark"></i>
    </button>
    
    <!-- Branding Header (100% integrado sin cortes de fondo) -->
    <div class="sm-branding-wrapper">
        <a href="<?= APP_URL ?>/dashboard" class="sm-header-block" style="text-decoration: none;" title="Portal Principal APM">
            <div class="sm-logo-icon">
                <img src="<?= APP_URL ?>/imgs/logoapm.png" alt="Logo APM">
            </div>
            <div class="sm-title-text">
                <h2><span>Sys</span>Port</h2>
                <p>AUTORIDAD PORTUARIA</p>
            </div>
        </a>
    </div>

    <!-- Buscador Rápido de Menú (Ctrl + K) -->
    <div class="sidebar-search-box">
        <div class="sidebar-search-inner">
            <i class="fa-solid fa-magnifying-glass sidebar-search-icon"></i>
            <input type="text" id="sidebarSearchInput" class="sidebar-search-input" placeholder="Buscar pantalla..." autocomplete="off" aria-label="Buscar en el menú">
            <span class="sidebar-search-badge" id="sidebarSearchKbd">Ctrl K</span>
            <button type="button" id="sidebarSearchClear" class="sidebar-search-clear" title="Limpiar búsqueda" style="display:none;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         SECCIÓN DINÁMICA: HISTORIAL DE RUTAS RECIENTES
         ══════════════════════════════════════════════════════ -->
    <div id="sb-recent-section" class="sb-recent-section" style="display:none;">
        <div class="sb-recent-header">
            <span><i class="fa-solid fa-clock-rotate-left"></i> RECIENTES</span>
            <button type="button" class="sb-pinned-clear" onclick="clearAllRecents()" title="Limpiar historial">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="sb-recent-pills" class="sb-recent-pills"></div>
    </div>

    <!-- Module accordion navigation -->
    <div class="sidebar-mods" id="sidebarMods">
        
        <!-- ══════════════════════════════════════════════════════
             SECCIÓN DINÁMICA: FAVORITOS
             ══════════════════════════════════════════════════════ -->
        <div id="sb-favorites-section" class="sb-pinned-section" style="display:none;">
            <div class="sidebar-mods-title" style="display:flex;align-items:center;justify-content:space-between;padding:4px 6px 6px;">
                <span><i class="fa-solid fa-star" style="color:#F59E0B;margin-right:4px;"></i> FAVORITOS</span>
                <button type="button" class="sb-pinned-clear" onclick="clearAllFavorites()" title="Limpiar favoritos">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div id="sb-favorites-list" class="sb-pinned-list"></div>
        </div>

        <div class="sidebar-search-empty" id="sidebarSearchEmpty">
            <i class="fa-solid fa-magnifying-glass-chart" style="font-size:1.4rem;display:block;margin-bottom:6px;opacity:.5;"></i>
            Sin resultados para esa búsqueda.
        </div>

        <?php if (!empty($sidebarFocus)): ?>
            <a href="<?= APP_URL ?>/dashboard" data-no-spa class="sb-focus-back-btn">
                <i class="fa-solid fa-arrow-left"></i> Volver a Todos los Módulos
            </a>
            <div class="sidebar-mods-title">MÓDULO ACTIVO</div>
        <?php else: ?>
            <div class="sidebar-mods-title">MÓDULOS DEL PORTAL</div>
        <?php endif; ?>

        <?php
        $moduleColors = [
            1 => '#0284C7', 2 => '#0284C7', 3 => '#EF4444', 4 => '#F59E0B',
            5 => '#10B981', 6 => '#0284C7', 7 => '#64748B', 8 => '#8B5CF6',
            9 => '#0EA5E9', 10 => '#10B981', 11 => '#10B981', 12 => '#0284C7',
            13 => '#F59E0B',
        ];
        ?>

        <?php if (!empty($userMenu)): ?>
            <?php foreach ($userMenu as $modId => $mod): ?>
                <?php
                $modColor = $mod['color'] ?? ($moduleColors[(int)$modId] ?? '#0284C7');
                
                // Verificar si algún hijo está activo
                $isModActive = false;
                foreach ($mod['areas'] as $area) {
                    foreach ($area['items'] as $opt) {
                        if ($currentPath === '/' . ltrim($opt['url'] ?? '', '/')) {
                            $isModActive = true; break 2;
                        }
                        foreach ($opt['children'] as $subopt) {
                            if ($currentPath === '/' . ltrim($subopt['url'] ?? '', '/')) {
                                $isModActive = true; break 3;
                            }
                        }
                    }
                }
                if (!empty($sidebarFocus)) $isModActive = true;

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
                        $modUrl = apm_sidebar_link(APP_URL, $soloItem['url']);
                        $modActiveDirecta = ($currentPath === '/' . ltrim($soloItem['url'], '/'));
                    ?>
                    <!-- Level 1 Direct Link (Module with single target) -->
                    <a class="sm-header <?= $modActiveDirecta ? 'active' : '' ?>" id="smhdr-<?= $modId ?>" href="<?= $modUrl ?>" <?= !empty($soloItem['spa']) ? 'data-spa' : 'data-no-spa' ?>
                       title="<?= htmlspecialchars($mod['label'] ?? '') ?>">
                        <div class="sm-icon" style="background: <?= $modColor ?> !important;">
                            <i class="<?= normalizeFaIcon($mod['icon'] ?? '') ?>"></i>
                        </div>
                        <span class="sm-name"><?= htmlspecialchars($mod['label'] ?? '') ?></span>
                    </a>
                    <?php else: ?>
                    <!-- Level 1 Accordion Header (Module) -->
                    <button type="button" class="sm-header <?= $isModActive ? 'open' : '' ?>" id="smhdr-<?= $modId ?>" onclick="toggleSidebarModule('<?= $modId ?>')"
                            title="<?= htmlspecialchars($mod['label'] ?? '') ?>">
                        <div class="sm-icon" style="background: <?= $modColor ?> !important;">
                            <i class="<?= normalizeFaIcon($mod['icon'] ?? '') ?>"></i>
                        </div>
                        <span class="sm-name"><?= htmlspecialchars($mod['label'] ?? '') ?></span>
                        <i class="fa-solid fa-chevron-right sm-chevron"></i>
                    </button>

                    <div class="sm-tree <?= $isModActive ? 'open' : '' ?>" id="smtree-<?= $modId ?>">
                        <div class="sb-area">
                            <?php $singleArea = count($mod['areas']) === 1; ?>
                            <!-- Level 2: Sub-area / Area (NO ICONS - Clean Typography) -->
                            <?php foreach ($mod['areas'] as $areaId => $area): ?>
                                <?php
                                $isAreaActive = false;
                                foreach ($area['items'] as $opt) {
                                    if ($currentPath === '/' . ltrim($opt['url'] ?? '', '/')) {
                                        $isAreaActive = true; break;
                                    }
                                    foreach ($opt['children'] as $subopt) {
                                        if ($currentPath === '/' . ltrim($subopt['url'] ?? '', '/')) {
                                            $isAreaActive = true; break 2;
                                        }
                                    }
                                }
                                if (!empty($sidebarFocus)) $isAreaActive = true;

                                $areaItems = $area['items'];
                                $areaSoloItem = (count($areaItems) === 1 && empty(reset($areaItems)['children']) && !empty(reset($areaItems)['url']))
                                    ? reset($areaItems) : null;
                                $areaEsLinkDirecto = !$singleArea && $areaSoloItem !== null;
                                ?>
                                <div style="margin-bottom: <?= $singleArea ? '2px' : '3px' ?>;">
                                    <?php if ($areaEsLinkDirecto):
                                        $areaUrl = apm_sidebar_link(APP_URL, $areaSoloItem['url']);
                                        $areaActivaDirecta = ($currentPath === '/' . ltrim($areaSoloItem['url'], '/'));
                                        $esNotif = (strpos($areaUrl, '/notificaciones') !== false);
                                    ?>
                                    <a class="sb-area-btn sb-area-link <?= $areaActivaDirecta ? 'active' : '' ?>" href="<?= $areaUrl ?>" <?= !empty($areaSoloItem['spa']) ? 'data-spa' : 'data-no-spa' ?>
                                       title="<?= htmlspecialchars($area['label'] ?? '') ?>">
                                        <span class="sab-label"><?= htmlspecialchars($area['label'] ?? '') ?></span>
                                        <?php if ($esNotif && $unreadNotifsCount > 0): ?>
                                        <span class="sb-badge-notif"><?= $unreadNotifsCount ?></span>
                                        <?php endif; ?>
                                        <span class="sb-fav-btn" role="button" tabindex="0" onclick="toggleFavorite(event, '<?= htmlspecialchars($area['label'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($areaUrl, ENT_QUOTES) ?>', '<?= normalizeFaIcon($mod['icon'] ?? 'circle') ?>')" title="Fijar en Favoritos">
                                            <i class="fa-regular fa-star"></i>
                                        </span>
                                    </a>
                                    </div>
                                    <?php continue; endif; ?>

                                    <?php if (!$singleArea): ?>
                                    <button type="button" class="sb-area-btn <?= $isAreaActive ? 'open' : '' ?>" id="sba-<?= $areaId ?>" onclick="toggleSidebarArea('<?= $areaId ?>')" title="<?= htmlspecialchars($area['label'] ?? '') ?>">
                                        <span class="sab-label"><?= htmlspecialchars($area['label'] ?? '') ?></span>
                                        <i class="fa-solid fa-chevron-right sab-chevron"></i>
                                    </button>
                                    <?php endif; ?>

                                    <div class="sb-items <?= ($singleArea || $isAreaActive) ? 'open' : '' ?>" id="sbi-<?= $areaId ?>">
                                        <!-- Level 3: Menu Option (NO ICONS - Clean Typography) -->
                                        <?php foreach ($area['items'] as $opt): ?>
                                            <?php if (!empty($opt['children'])): ?>
                                                <?php 
                                                $isSubActive = false;
                                                foreach ($opt['children'] as $subopt) {
                                                    if ($currentPath === '/' . ltrim($subopt['url'] ?? '', '/')) {
                                                        $isSubActive = true; break;
                                                    }
                                                }
                                                ?>
                                                <!-- Collapsible Level 3 Option -->
                                                <div style="margin-bottom: 2px;">
                                                    <button type="button" class="sb-subopt-btn <?= $isSubActive ? 'open' : '' ?>" onclick="toggleSidebarSubopt('<?= $opt['id'] ?>')" id="sbso-<?= $opt['id'] ?>">
                                                        <span class="sso-label"><?= htmlspecialchars($opt['label']) ?></span>
                                                        <i class="fa-solid fa-chevron-right sso-chevron"></i>
                                                    </button>
                                                    <div class="sb-subitems <?= $isSubActive ? 'open' : '' ?>" id="sbsi-<?= $opt['id'] ?>">
                                                        <!-- Level 4: Deepest Links (NO ICONS - Clean Lineage) -->
                                                        <?php foreach ($opt['children'] as $subopt): ?>
                                                            <?php 
                                                            $subUrl = apm_sidebar_link(APP_URL, $subopt['url'] ?? '');
                                                            $subActive = ($currentPath === '/' . ltrim($subopt['url'] ?? '', '/'));
                                                            ?>
                                                            <a href="<?= $subUrl ?>" class="sb-subitem <?= $subActive ? 'active' : '' ?>" <?= !empty($subopt['spa']) ? 'data-spa' : 'data-no-spa' ?> id="sit-<?= $subopt['id'] ?>"
                                                               title="<?= htmlspecialchars($subopt['label']) ?>">
                                                                <span class="sib-label"><?= htmlspecialchars($subopt['label']) ?></span>
                                                                <span class="sb-fav-btn" role="button" tabindex="0" onclick="toggleFavorite(event, '<?= htmlspecialchars($subopt['label'], ENT_QUOTES) ?>', '<?= htmlspecialchars($subUrl, ENT_QUOTES) ?>', '<?= normalizeFaIcon($mod['icon'] ?? 'circle') ?>')" title="Fijar en Favoritos">
                                                                    <i class="fa-regular fa-star"></i>
                                                                </span>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <!-- Direct Level 3 Link -->
                                                <?php 
                                                $linkUrl = apm_sidebar_link(APP_URL, $opt['url'] ?? '');
                                                $isActive = ($currentPath === '/' . ltrim($opt['url'] ?? '', '/'));
                                                $esNotif = (strpos($linkUrl, '/notificaciones') !== false);
                                                ?>
                                                <a href="<?= $linkUrl ?>" class="sb-item-btn sb-item <?= $isActive ? 'active' : '' ?>" <?= !empty($opt['spa']) ? 'data-spa' : 'data-no-spa' ?> id="sit-<?= $opt['id'] ?>"
                                                   title="<?= htmlspecialchars($opt['label']) ?>">
                                                    <span class="sib-label"><?= htmlspecialchars($opt['label']) ?></span>
                                                    <?php if ($esNotif && $unreadNotifsCount > 0): ?>
                                                    <span class="sb-badge-notif"><?= $unreadNotifsCount ?></span>
                                                    <?php endif; ?>
                                                    <span class="sb-fav-btn" role="button" tabindex="0" onclick="toggleFavorite(event, '<?= htmlspecialchars($opt['label'], ENT_QUOTES) ?>', '<?= htmlspecialchars($linkUrl, ENT_QUOTES) ?>', '<?= normalizeFaIcon($mod['icon'] ?? 'circle') ?>')" title="Fijar en Favoritos">
                                                        <i class="fa-regular fa-star"></i>
                                                    </span>
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

    <!-- ══════════════════════════════════════════════════════
         FOOTER: USER MICRO-CARD
         ══════════════════════════════════════════════════════ -->
    <div class="sidebar-divider"></div>
    <div class="sidebar-user-card">
        <a href="<?= APP_URL ?>/perfil" data-spa style="display:flex;align-items:center;gap:10px;text-decoration:none;flex:1;min-width:0;" title="Ir a Mi Perfil">
            <div class="sidebar-user-avatar-wrap">
                <div class="sidebar-user-avatar">
                    <?php
                    $name = $_SESSION['nombre_completo'] ?? 'Usuario';
                    $words = explode(' ', $name);
                    $initials = '';
                    foreach (array_slice($words, 0, 2) as $w) { $initials .= mb_strtoupper(mb_substr($w, 0, 1)); }
                    echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');
                    ?>
                </div>
                <span class="sidebar-user-dot"></span>
            </div>
            <div style="flex:1;min-width:0;">
                <div class="sidebar-user-name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="sidebar-user-role">
                    <?php
                    $nivel = (int)($_SESSION['nivel_jerarquia'] ?? 0);
                    echo $nivel >= 4 ? 'Super Admin' : ($nivel >= 3 ? 'Director' : ($nivel >= 2 ? 'Jefatura' : ($nivel >= 1 ? 'Analista' : 'Operativo')));
                    ?>
                </div>
            </div>
        </a>
        <a href="<?= APP_URL ?>/logout" class="sidebar-logout-btn" title="Cerrar Sesión">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>
</aside>

<style>
/* ═══════════════════════════════════════════════════════════════════════════
   SIDEBAR AG-UI PRO MAX (INTEGRATED BRANDING, CLEAN HIGHLIGHT, RECENT TAGS)
   ═══════════════════════════════════════════════════════════════════════════ */
.sidebar {
    background: var(--bg-sidebar) !important;
    border-right: 1px solid var(--border-app) !important;
    color: var(--text-app);
    transition: width 0.28s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.28s ease, padding 0.28s ease;
    z-index: 1000;
    display: flex;
    flex-direction: column;
}

.sm-branding-wrapper {
    padding: 16px 14px 12px;
    flex-shrink: 0;
    border-bottom: 1px solid var(--border-app);
    margin-bottom: 10px;
}

.sm-header-block {
    display: flex;
    align-items: center;
    gap: 10px;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
}

.sm-logo-icon {
    background: rgba(255, 255, 255, 0.08) !important;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(255, 255, 255, 0.12);
    padding: 4px;
    flex-shrink: 0;
    box-shadow: none !important;
}
.sm-logo-icon img { width: 100%; height: 100%; object-fit: contain; }

.sm-title-text h2 {
    font-family: var(--font-body);
    font-size: 1.15rem;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.02em;
    color: var(--text-app);
}
.sm-title-text h2 span { color: #38bdf8; font-weight: 800; }
.sm-title-text p {
    font-family: var(--font-body);
    font-size: 8.5px;
    font-weight: 700;
    margin: 1px 0 0 0;
    color: var(--text-muted);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

/* ── Buscador ── */
.sidebar-search-box {
    padding: 0 10px 8px;
    flex-shrink: 0;
}
.sidebar-search-inner {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    background: var(--accent-app);
    border: 1px solid var(--border-app);
    border-radius: var(--radius-md);
    transition: var(--ease);
}
.sidebar-search-inner:focus-within {
    border-color: var(--primary-hover);
}
.sidebar-search-icon { font-size: 11px; color: var(--text-muted); }
.sidebar-search-input {
    flex: 1;
    min-width: 0;
    border: none;
    background: transparent;
    outline: none;
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--text-app);
}
.sidebar-search-input::placeholder { color: var(--text-muted); }
.sidebar-search-badge {
    font-size: 9px;
    font-weight: 800;
    color: var(--text-muted);
    background: var(--surface-app);
    border: 1px solid var(--border-app);
    padding: 1px 5px;
    border-radius: 4px;
}
.sidebar-search-clear {
    border: none;
    background: transparent;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.sidebar-search-empty {
    display: none;
    padding: 14px 10px;
    text-align: center;
    font-size: 0.78rem;
    color: var(--text-muted);
}
.sidebar-search-empty.show { display: block; }

/* ── Resaltado de Texto en Búsqueda (<mark>) ── */
mark.sb-search-highlight {
    background: #F59E0B !important;
    color: #000000 !important;
    font-weight: 800 !important;
    border-radius: 2px;
    padding: 0 2px;
    box-shadow: 0 0 4px rgba(245, 158, 11, 0.6);
}

/* ── Historial de Rutas Recientes ── */
.sb-recent-section {
    margin: 0 10px 8px;
    padding: 6px 8px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--border-app);
}
.sb-recent-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 8.5px;
    font-weight: 700;
    color: var(--text-muted);
    letter-spacing: 0.08em;
    margin-bottom: 5px;
}
.sb-recent-header i { font-size: 8.5px; margin-right: 4px; color: var(--primary-hover); }
.sb-recent-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}
.sb-recent-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    border-radius: 5px;
    background: var(--accent-app);
    color: var(--text-app);
    font-size: 10px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--ease);
    border: 1px solid var(--border-app);
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sb-recent-pill:hover {
    background: var(--primary-hover);
    color: #ffffff;
    border-color: var(--primary-hover);
}

/* ── Favoritos & Pinned ── */
.sb-pinned-section {
    margin: 0 6px 10px;
    padding: 6px;
    border-radius: 8px;
    background: color-mix(in srgb, #F59E0B 8%, transparent);
    border: 1px dashed color-mix(in srgb, #F59E0B 35%, transparent);
}
.sb-pinned-list {
    display: flex;
    flex-direction: column;
    gap: 3px;
    margin-top: 4px;
}
.sb-pinned-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-app);
    text-decoration: none;
    transition: var(--ease);
}
.sb-pinned-item:hover {
    background: color-mix(in srgb, #F59E0B 18%, transparent);
    color: #F59E0B;
}
.sb-pinned-clear {
    border: none;
    background: transparent;
    color: var(--text-muted);
    font-size: 10px;
    cursor: pointer;
}

/* ── Module Level 1 ── */
.sm-section {
    margin-bottom: 3px;
}
.sm-header {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    background: transparent;
    border: none;
    cursor: pointer;
    text-align: left;
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text-app);
    transition: background var(--ease);
    text-decoration: none;
}
.sm-header:hover {
    background: var(--accent-app);
}
.sm-icon {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    color: #fff !important;
    flex-shrink: 0;
    border: 1px solid rgba(255, 255, 255, 0.15);
}
.sm-name {
    flex: 1;
    white-space: normal;
    word-break: break-word;
    line-height: 1.3;
}
.sm-chevron {
    color: var(--text-muted);
    font-size: 9px;
    transition: transform 0.22s ease;
    flex-shrink: 0;
}
.sm-header.open .sm-chevron {
    transform: rotate(90deg);
}

/* ── Level 2: Areas (NO ICONS) ── */
.sb-area {
    padding: 2px 4px 4px 8px;
}
.sb-area-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 8px 6px 14px;
    background: transparent;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    color: var(--text-muted);
    letter-spacing: 0.04em;
    text-transform: uppercase;
    transition: var(--ease);
    text-decoration: none;
}
.sb-area-btn:hover {
    background: var(--accent-app);
    color: var(--text-app);
}
.sb-area-btn.open {
    color: var(--text-app);
}
.sab-label {
    flex: 1;
    white-space: normal;
    word-break: break-word;
}
.sab-chevron {
    font-size: 8px;
    color: var(--text-muted);
    transition: transform 0.2s ease;
}
.sb-area-btn.open .sab-chevron {
    transform: rotate(90deg);
}

/* ── Level 3: Items & Collapsibles (NO ICONS) ── */
.sb-item-btn,
.sb-subopt-btn {
    width: 100%;
    display: flex;
    align-items: center;
    padding: 5.5px 8px 5.5px 22px;
    background: transparent;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-align: left;
    font-size: 12px;
    font-weight: 500;
    color: var(--text-muted);
    transition: var(--ease);
    text-decoration: none;
    position: relative;
}
.sb-item-btn:hover,
.sb-subopt-btn:hover {
    background: var(--accent-app);
    color: var(--text-app);
}
.sb-subopt-btn.open {
    color: var(--text-app);
}
.sso-label,
.sib-label {
    flex: 1;
    white-space: normal;
    word-break: break-word;
    line-height: 1.35;
}
.sso-chevron {
    font-size: 8px;
    color: var(--text-muted);
    transition: transform 0.2s ease;
}
.sb-subopt-btn.open .sso-chevron {
    transform: rotate(90deg);
}

/* ── Level 4: Subitems (NO ICONS, Clean Lineage) ── */
.sb-subitems {
    margin-left: 26px;
    padding-left: 8px;
    border-left: 1.5px solid var(--border-app);
}
.sb-subitem {
    display: flex;
    align-items: center;
    padding: 4.5px 8px 4.5px 8px;
    background: transparent;
    border-radius: 5px;
    font-size: 11.5px;
    font-weight: 400;
    color: var(--text-muted);
    transition: var(--ease);
    text-decoration: none;
    position: relative;
}
.sb-subitem:hover {
    background: var(--accent-app);
    color: var(--text-app);
}

/* ── STRICT ACTIVE STATE (SHADOWS & HIGHLIGHT ONLY ON CURRENT SCREEN) ── */
.sb-item-btn.active,
.sb-subitem.active,
.sb-area-link.active,
.sm-header.active {
    background: color-mix(in srgb, var(--mod-color, var(--primary-hover)) 12%, transparent) !important;
    color: var(--mod-color, var(--primary-hover)) !important;
    font-weight: 700 !important;
    border-left: 3px solid var(--mod-color, var(--primary-hover));
}

/* ── Star Favorite Toggle Button ── */
.sb-fav-btn {
    opacity: 0;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 10px;
    margin-left: auto;
    padding: 2px 4px;
    transition: var(--ease);
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.sb-item-btn:hover .sb-fav-btn,
.sb-subitem:hover .sb-fav-btn,
.sb-area-btn:hover .sb-fav-btn {
    opacity: 0.6;
}
.sb-fav-btn:hover {
    opacity: 1 !important;
    color: #F59E0B !important;
}

/* ── Badges Dinámicos ── */
.sb-badge-notif {
    background: #EF4444;
    color: #ffffff;
    font-size: 9px;
    font-weight: 800;
    padding: 1px 6px;
    border-radius: 99px;
    margin-left: auto;
}

/* ── Footer User Card ── */
.sidebar-user-card {
    padding: 9px 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--accent-app);
    border-radius: var(--radius-md);
    margin: 4px 10px 10px;
    border: 1px solid var(--border-app);
    flex-shrink: 0;
}
.sidebar-user-avatar-wrap {
    position: relative;
    flex-shrink: 0;
}
.sidebar-user-avatar {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-sm);
    background: linear-gradient(135deg, var(--primary-app), var(--primary-hover));
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 800;
}
.sidebar-user-dot {
    position: absolute;
    bottom: -1px;
    right: -1px;
    width: 8px;
    height: 8px;
    background: #10B981;
    border: 1.5px solid var(--surface-app);
    border-radius: 50%;
}
.sidebar-user-name {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--text-app);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}
.sidebar-user-role {
    font-size: 0.68rem;
    color: var(--text-muted);
    font-weight: 500;
    margin-top: 1px;
}
.sidebar-logout-btn {
    width: 26px;
    height: 26px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    transition: var(--ease);
    text-decoration: none;
    font-size: 11px;
}
.sidebar-logout-btn:hover {
    background: rgba(239, 68, 68, 0.15);
    color: #EF4444;
}

/* ── Focus Mode Back Button ── */
.sb-focus-back-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 8px 10px;
    padding: 8px 10px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--text-app);
    background: var(--accent-app);
    border: 1px solid var(--border-app);
    transition: var(--ease);
}
.sb-focus-back-btn:hover {
    background: var(--primary-hover);
    color: #ffffff;
}

/* ── Accordion Trees ── */
.sm-tree {
    display: block;
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.28s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease;
}
.sm-tree.open {
    max-height: 3000px;
    opacity: 1;
}

.sb-items {
    display: block;
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.26s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease;
}
.sb-items.open {
    max-height: 1500px;
    opacity: 1;
}

.sb-subitems {
    display: block;
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.22s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.2s ease;
}
.sb-subitems.open {
    max-height: 1000px;
    opacity: 1;
}

/* Durante la búsqueda, forzar apertura */
.sidebar-mods.searching .sm-tree,
.sidebar-mods.searching .sb-items,
.sidebar-mods.searching .sb-subitems {
    max-height: none !important;
    opacity: 1 !important;
    overflow: visible !important;
}
.sidebar-mods.searching .sm-chevron,
.sidebar-mods.searching .sab-chevron,
.sidebar-mods.searching .sso-chevron {
    transform: rotate(90deg);
}
</style>

<script>
window.toggleSidebarModule = function(modId) {
    const btn = document.getElementById('smhdr-' + modId);
    const tree = document.getElementById('smtree-' + modId);
    if (!btn || !tree) return;

    const isOpen = tree.classList.contains('open');

    document.querySelectorAll('.sm-header').forEach(h => {
        if (h.id !== 'smhdr-' + modId) h.classList.remove('open');
    });
    document.querySelectorAll('.sm-tree').forEach(t => {
        if (t.id !== 'smtree-' + modId) t.classList.remove('open');
    });

    if (isOpen) {
        btn.classList.remove('open');
        tree.classList.remove('open');
        localStorage.setItem('apm_sidebar_open_module', '');
    } else {
        btn.classList.add('open');
        tree.classList.add('open');
        tree.querySelectorAll('.sb-area-btn').forEach(a => a.classList.add('open'));
        tree.querySelectorAll('.sb-items').forEach(s => s.classList.add('open'));
        localStorage.setItem('apm_sidebar_open_module', modId);
    }
};

window.toggleSidebarArea = function(areaId) {
    const btn = document.getElementById('sba-' + areaId);
    const items = document.getElementById('sbi-' + areaId);
    if (!btn || !items) return;
    const isOpen = items.classList.contains('open');
    btn.classList.toggle('open', !isOpen);
    items.classList.toggle('open', !isOpen);
};

window.toggleSidebarSubopt = function(optId) {
    const btn = document.getElementById('sbso-' + optId);
    const subitems = document.getElementById('sbsi-' + optId);
    if (!btn || !subitems) return;
    const isOpen = subitems.classList.contains('open');
    btn.classList.toggle('open', !isOpen);
    subitems.classList.toggle('open', !isOpen);
};

// ── Manejo de Favoritos ──
function getFavorites() {
    try {
        return JSON.parse(localStorage.getItem('sysport_menu_favs') || '[]');
    } catch(e) { return []; }
}

function saveFavorites(favs) {
    localStorage.setItem('sysport_menu_favs', JSON.stringify(favs));
    renderFavorites();
}

function toggleFavorite(e, title, url, icon) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    let favs = getFavorites();
    const idx = favs.findIndex(f => f.url === url);
    if (idx >= 0) {
        favs.splice(idx, 1);
        if (window.PortalAlert) PortalAlert.success(`"${title}" eliminado de favoritos.`);
    } else {
        favs.push({ title, url, icon });
        if (window.PortalAlert) PortalAlert.success(`"${title}" fijado en favoritos ⭐`);
    }
    saveFavorites(favs);
}

function clearAllFavorites() {
    saveFavorites([]);
}

function renderFavorites() {
    const section = document.getElementById('sb-favorites-section');
    const list = document.getElementById('sb-favorites-list');
    if (!section || !list) return;

    const favs = getFavorites();
    if (favs.length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = 'block';
    list.innerHTML = favs.map(f => `
        <a href="${f.url}" class="sb-pinned-item" data-spa>
            <i class="${f.icon}" style="font-size:10px;color:#F59E0B;"></i>
            <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${f.title}</span>
            <i class="fa-solid fa-arrow-right" style="font-size:8px;opacity:.5;"></i>
        </a>
    `).join('');
}

// ── Manejo de Historial de Rutas Recientes ──
function getRecentHistory() {
    try {
        return JSON.parse(localStorage.getItem('sysport_recent_history') || '[]');
    } catch(e) { return []; }
}

function syncSidebarActiveState(path) {
    const cleanPath = (path || window.location.pathname || '').replace(/\/$/, '');
    document.querySelectorAll('#sidebarMods a[href]').forEach(a => {
        const href = (a.getAttribute('href') || '').replace(/\/$/, '');
        const isMatch = (href === cleanPath) || (cleanPath === '' && href.endsWith('/dashboard'));
        a.classList.toggle('active', isMatch);
    });
}

function recordCurrentPageAsRecent() {
    const path = window.location.pathname;
    if (!path || path.includes('/login') || path.includes('/logout') || path.includes('/ir?') || path.includes('/export/')) return;
    
    syncSidebarActiveState(path);

    let title = '';
    if (path.endsWith('/dashboard/operativo')) {
        title = 'Dashboard Operativo';
    } else if (path.endsWith('/dashboard') || path.endsWith('/dashboard/ejecutivo')) {
        title = 'Dashboard Ejecutivo';
    } else if (path.includes('/admin/usuarios')) {
        title = 'Gestión de Usuarios';
    } else if (path.includes('/admin/roles')) {
        title = 'Roles y Permisos';
    } else if (path.includes('/admin/auditoria')) {
        title = 'Auditoría del Sistema';
    } else if (path.includes('/admin/modulos')) {
        title = 'Módulos';
    } else if (path.includes('/admin/inactividad')) {
        title = 'Inactividad de Sesión';
    } else if (path.includes('/admin/departamentos')) {
        title = 'Departamentos';
    } else if (path.includes('/perfil')) {
        title = 'Mi Perfil';
    } else if (path.includes('/notificaciones')) {
        title = 'Notificaciones';
    } else {
        const activeLink = document.querySelector('#sidebarMods a.active .sib-label, #sidebarMods a.active .sab-label, #sidebarMods a.active .sm-name');
        title = activeLink ? activeLink.textContent.trim() : (document.title.replace('— SysPort', '').replace('SysPort', '').trim() || 'Pantalla');
    }

    if (!title) return;

    let history = getRecentHistory();
    history = history.filter(item => item.url !== path && item.title !== title);
    history.unshift({ title, url: path });
    // Estrictamente las últimas 3 pantallas recientes
    if (history.length > 3) history = history.slice(0, 3);

    localStorage.setItem('sysport_recent_history', JSON.stringify(history));
    renderRecentHistory();
}

function clearAllRecents() {
    localStorage.setItem('sysport_recent_history', JSON.stringify([]));
    renderRecentHistory();
}

function renderRecentHistory() {
    const section = document.getElementById('sb-recent-section');
    const container = document.getElementById('sb-recent-pills');
    if (!section || !container) return;

    const history = getRecentHistory();
    if (history.length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = 'block';
    container.innerHTML = history.map(item => `
        <a href="${item.url}" class="sb-recent-pill" data-spa title="${item.title}">
            <i class="fa-regular fa-compass" style="font-size:8.5px;color:var(--primary-hover);"></i>
            <span>${item.title}</span>
        </a>
    `).join('');
}

document.addEventListener("DOMContentLoaded", function() {
    renderFavorites();
    recordCurrentPageAsRecent();

    const sb = document.getElementById('sidebar');

    // Listener para actualizar historial y navegación activa cuando se navega por SPA
    window.addEventListener('spa-content-loaded', function() {
        recordCurrentPageAsRecent();
    });
    window.addEventListener('popstate', function() {
        recordCurrentPageAsRecent();
    });

    // Atajo de teclado Ctrl+K o / para buscar
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey && e.key.toLowerCase() === 'k') || (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA')) {
            e.preventDefault();
            const searchInput = document.getElementById('sidebarSearchInput');
            if (searchInput) {
                if (sb && sb.classList.contains('collapsed')) {
                    sb.classList.remove('collapsed');
                }
                searchInput.focus();
                searchInput.select();
            }
        } else if (e.key === 'Escape') {
            const searchInput = document.getElementById('sidebarSearchInput');
            if (searchInput && document.activeElement === searchInput) {
                searchInput.value = '';
                searchInput.blur();
                restoreSearchState();
            }
        }
    });

    // ── Buscador Reactivo con Resaltado de Texto (<mark>) ──
    const sidebarMods = document.getElementById('sidebarMods');
    const searchInput = document.getElementById('sidebarSearchInput');
    const searchClear = document.getElementById('sidebarSearchClear');
    const searchEmpty = document.getElementById('sidebarSearchEmpty');
    let savedOpenState = null;

    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function removeTextHighlights() {
        if (!sidebarMods) return;
        sidebarMods.querySelectorAll('.sm-name, .sab-label, .sib-label, .sso-label').forEach(label => {
            if (label.getAttribute('data-orig-text')) {
                label.textContent = label.getAttribute('data-orig-text');
                label.removeAttribute('data-orig-text');
            }
        });
    }

    function applyTextHighlight(label, query) {
        if (!label.getAttribute('data-orig-text')) {
            label.setAttribute('data-orig-text', label.textContent);
        }
        const orig = label.getAttribute('data-orig-text');
        const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
        label.innerHTML = orig.replace(regex, '<mark class="sb-search-highlight">$1</mark>');
    }

    function restoreSearchState() {
        if (!sidebarMods) return;
        sidebarMods.classList.remove('searching');
        removeTextHighlights();
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

    if (sidebarMods && searchInput) {
        const normalize = (s) => (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

        searchInput.addEventListener('input', function() {
            const rawQuery = this.value.trim();
            const q = normalize(rawQuery);
            if (q === '') { restoreSearchState(); return; }

            if (searchClear) searchClear.style.display = 'flex';

            if (!sidebarMods.classList.contains('searching')) {
                savedOpenState = Array.from(
                    sidebarMods.querySelectorAll('.sm-header, .sm-tree, .sb-area-btn, .sb-items, .sb-subopt-btn, .sb-subitems')
                ).map(el => ({ el, open: el.classList.contains('open') }));
                sidebarMods.classList.add('searching');
            }

            const leaves = Array.from(sidebarMods.querySelectorAll('a[href]:not(.sb-pinned-item)'));
            let anyVisible = false;

            leaves.forEach(leaf => {
                const label = leaf.querySelector('.sib-label, .sm-name, .sso-label, .sab-label');
                if (!label) return;
                
                const rawText = label.getAttribute('data-orig-text') || label.textContent;
                const text = normalize(rawText);
                const match = text.includes(q);
                
                leaf.style.display = match ? '' : 'none';
                leaf.setAttribute('data-search-touched', '1');
                
                if (match) {
                    anyVisible = true;
                    applyTextHighlight(label, rawQuery);
                } else {
                    if (label.getAttribute('data-orig-text')) {
                        label.textContent = label.getAttribute('data-orig-text');
                        label.removeAttribute('data-orig-text');
                    }
                }
            });

            sidebarMods.querySelectorAll('.sb-area-btn:not(.sb-area-link), .sb-subopt-btn').forEach(btn => {
                const wrap = btn.parentElement.querySelector(':scope > .sb-items, :scope > .sb-subitems');
                const row = btn.parentElement;
                const hasVisible = wrap ? Array.from(wrap.querySelectorAll('a[href]')).some(a => a.style.display !== 'none') : false;
                row.style.display = hasVisible ? '' : 'none';
                row.setAttribute('data-search-touched', '1');
            });

            sidebarMods.querySelectorAll('.sm-section').forEach(section => {
                const hasVisible = Array.from(section.querySelectorAll('a[href]')).some(a => a.style.display !== 'none');
                section.style.display = hasVisible ? '' : 'none';
                section.setAttribute('data-search-touched', '1');
            });

            if (searchEmpty) searchEmpty.classList.toggle('show', !anyVisible);
        });

        if (searchClear) {
            searchClear.addEventListener('click', function() {
                searchInput.value = '';
                restoreSearchState();
                searchInput.focus();
            });
        }
    }
});
</script>
