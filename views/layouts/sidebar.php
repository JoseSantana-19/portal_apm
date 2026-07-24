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
            'circle' => 'fa-regular fa-circle', // Hollow circle for clean visually-dense menu bullets
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

<!-- Google Fonts Import for Premium Typography -->
<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap');
</style>

<aside class="sidebar collapsed" id="sidebar">
    <!-- Close button for mobile -->
    <button class="sm-close" id="sidebar-close-btn" title="Cerrar menu">
        <i class="fa-solid fa-xmark"></i>
    </button>
    
    <!-- Branding Header -->
    <a href="<?= $baseUrl ?>/" class="sm-header-block" style="text-decoration: none;" title="Volver al Inicio">
        <div class="sm-icon" style="background: linear-gradient(135deg, #1E3A8A 0%, #1E40AF 100%) !important; width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);">
            <i class="fa-solid fa-anchor" style="color:#fff; font-size:18px;"></i>
        </div>
        <div class="sm-title-text">
            <h2 style="font-family: 'Outfit', sans-serif; font-size: 19px; font-weight: 800; margin: 0; letter-spacing: -0.02em; color: #fff;">
                <span style="color: #38bdf8; font-weight: 800;">Sys</span>Port
            </h2>
            <p style="font-family: 'Inter', sans-serif; font-size: 10.5px; font-weight: 500; margin: 2px 0 0 0; color: #94A3B8; letter-spacing: 0.02em;">AUTORIDAD PORTUARIA</p>
        </div>
    </a>
    
    <!-- Module accordion navigation -->
    <div class="sidebar-mods">
        <div class="sidebar-mods-title" style="font-family: 'Outfit', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; color: #64748B; padding: 16px 20px 8px;">MÓDULOS CORPORATIVOS</div>
        
        <?php if (!empty($userMenu)): ?>
            <?php foreach ($userMenu as $modId => $mod): ?>
                <?php 
                $modColor = $mod['color'] ?? '#1A3A5C'; 
                
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
                ?>
                <div class="sm-section" style="--mod-color: <?= $modColor ?>; --mod-color-alpha: <?= $modColor ?>20;">
                    <!-- Level 1: Module/Direction -->
                    <button class="sm-header <?= $isModActive ? 'active open' : '' ?>" id="smhdr-<?= $modId ?>" onclick="toggleSidebarModule(<?= $modId ?>)"
                            data-flyout-type="modulo"
                            data-id="<?= htmlspecialchars($mod['code'] ?? '') ?>"
                            data-title="<?= htmlspecialchars($mod['name']) ?>"
                            data-icon="<?= normalizeFaIcon($mod['icon'] ?? '') ?>"
                            data-color="<?= $modColor ?>">
                        <div class="sm-icon" style="background: <?= $modColor ?> !important; box-shadow: 0 2px 8px <?= $modColor ?>40;">
                            <i class="<?= normalizeFaIcon($mod['icon'] ?? '') ?>" style="font-size:13px; color:#fff;"></i>
                        </div>
                        <span class="sm-name" style="font-family: 'Outfit', sans-serif; font-size: 13.5px; font-weight: 600;"><?= htmlspecialchars($mod['name']) ?></span>
                        <i class="fa-solid fa-chevron-right sm-chevron"></i>
                    </button>
                    
                    <div class="sm-tree <?= $isModActive ? 'open' : '' ?>" id="smtree-<?= $modId ?>">
                        <div class="sb-area" style="padding: 4px 6px;">
                            <!-- Level 2: Sub-area/Area -->
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
                                ?>
                                <div style="margin-bottom: 6px;">
                                    <button class="sb-area-btn <?= $isAreaActive ? 'open' : '' ?>" id="sba-<?= $areaId ?>" onclick="toggleSidebarArea(<?= $areaId ?>)" title="<?= htmlspecialchars($area['name']) ?>"
                                            data-flyout-type="area" 
                                            data-id="<?= htmlspecialchars($area['code']) ?>" 
                                            data-title="<?= htmlspecialchars($area['name']) ?>" 
                                            data-icon="<?= normalizeFaIcon($area['icon'] ?? 'circle') ?>" 
                                            data-color="<?= $modColor ?>" 
                                            data-parent="<?= htmlspecialchars($mod['name']) ?>">
                                        <i class="<?= normalizeFaIcon($area['icon'] ?? 'circle') ?> sab-icon" style="font-size:11px; flex-shrink:0;"></i>
                                        <span class="sab-label" style="font-family: 'Inter', sans-serif; font-size: 12px; font-weight: 600;"><?= htmlspecialchars($area['name']) ?></span>
                                        <i class="fa-solid fa-chevron-right sab-chevron" style="font-size:9px; flex-shrink:0;"></i>
                                    </button>
                                    
                                    <div class="sb-items <?= $isAreaActive ? 'open' : '' ?>" id="sbi-<?= $areaId ?>">
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
                                                    <button class="sb-subopt-btn <?= $isSubActive ? 'open' : '' ?>" onclick="toggleSidebarSubopt(<?= $opt['id'] ?>)" id="sbso-<?= $opt['id'] ?>">
                                                        <i class="<?= normalizeFaIcon($opt['icon'] ?? 'circle') ?> sso-icon" style="font-size:9.5px; flex-shrink:0;"></i>
                                                        <span class="sso-label" style="font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 500;"><?= htmlspecialchars($opt['label']) ?></span>
                                                        <i class="fa-solid fa-chevron-right sso-chevron" style="font-size:8px; flex-shrink:0;"></i>
                                                    </button>
                                                    <div class="sb-subitems <?= $isSubActive ? 'open' : '' ?>" id="sbsi-<?= $opt['id'] ?>">
                                                        <!-- Level 4: Links -->
                                                        <?php foreach ($opt['children'] as $subopt): ?>
                                                            <?php 
                                                            $subUrl = $baseUrl . '/' . ltrim($subopt['url'], '/');
                                                            $subActive = ($currentPath === '/' . ltrim($subopt['url'], '/'));
                                                            ?>
                                                            <a href="<?= $subUrl ?>" class="sb-subitem <?= $subActive ? 'active' : '' ?>" data-spa id="sit-<?= $subopt['id'] ?>"
                                                               data-flyout-type="item"
                                                               data-id="<?= htmlspecialchars($subopt['id']) ?>"
                                                               data-title="<?= htmlspecialchars($subopt['label']) ?>"
                                                               data-icon="<?= normalizeFaIcon($subopt['icon'] ?? 'circle') ?>"
                                                               data-color="<?= $modColor ?>"
                                                               data-parent="<?= htmlspecialchars($opt['label']) ?>">
                                                                <i class="<?= normalizeFaIcon($subopt['icon'] ?? 'circle') ?> sib-icon" style="font-size:7.5px; flex-shrink:0; opacity:0.7;"></i>
                                                                <span class="sib-label" style="font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 500;"><?= htmlspecialchars($subopt['label']) ?></span>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <!-- Simple Level 3 Link -->
                                                <?php 
                                                $linkUrl = $baseUrl . '/' . ltrim($opt['url'], '/');
                                                $isActive = ($currentPath === '/' . ltrim($opt['url'], '/'));
                                                ?>
                                                <a href="<?= $linkUrl ?>" class="sb-item-btn sb-item <?= $isActive ? 'active' : '' ?>" data-spa id="sit-<?= $opt['id'] ?>"
                                                   data-flyout-type="item"
                                                   data-id="<?= htmlspecialchars($opt['id']) ?>"
                                                   data-title="<?= htmlspecialchars($opt['label']) ?>"
                                                   data-icon="<?= normalizeFaIcon($opt['icon'] ?? 'circle') ?>"
                                                   data-color="<?= $modColor ?>"
                                                   data-parent="<?= htmlspecialchars($area['name']) ?>">
                                                    <i class="<?= normalizeFaIcon($opt['icon'] ?? 'circle') ?> sib-icon" style="font-size:10px; flex-shrink:0;"></i>
                                                    <span class="sib-label" style="font-family: 'Inter', sans-serif; font-size: 11.5px; font-weight: 500;"><?= htmlspecialchars($opt['label']) ?></span>
                                                </a>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- System database schema link -->
    <div class="sidebar-divider" style="height:1px; background:rgba(255,255,255,0.06); margin:12px 16px;"></div>
    <a href="<?= $baseUrl ?>/base-de-datos" class="sm-header <?= ($currentPath === '/base-de-datos') ? 'active' : '' ?>" data-spa style="margin: 4px 12px; border-radius:10px;"
       data-flyout-type="modulo"
       data-id="SYSTEM_BD"
       data-title="Esquema Relacional"
       data-icon="fa-diagram-project"
       data-color="#6366F1">
        <div class="sm-icon" style="background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%) !important;">
            <i class="fa-solid fa-diagram-project" style="font-size:13px; color:#fff;"></i>
        </div>
        <span class="sm-name" style="font-family: 'Outfit', sans-serif; font-size: 13.5px; font-weight: 600;">Esquema Relacional</span>
    </a>
    
    <!-- User info at bottom -->
    <div class="sidebar-divider" style="height:1px; background:rgba(255,255,255,0.06); margin:12px 16px;"></div>
    <div style="padding: 12px 16px; display:flex; align-items:center; gap:12px; background: rgba(0,0,0,0.15); border-radius: 12px; margin: 4px 12px;">
        <div style="width:36px;height:36px;border-radius:10px;background: linear-gradient(135deg, #1E3A8A 0%, #1E40AF 100%);color:#fff;display:flex;align-items:center;justify-content:center;font-family: 'Outfit', sans-serif;font-size:13px;font-weight:700;box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
            <?= strtoupper(substr($_SESSION['user_name'] ?? 'AD', 0, 2)) ?>
        </div>
        <div style="flex:1;min-width:0;">
            <div style="font-family: 'Outfit', sans-serif;font-size:13px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador') ?></div>
            <div style="font-family: 'Inter', sans-serif;font-size:10.5px;color:#94A3B8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($_SESSION['user_role'] ?? 'TI') ?></div>
        </div>
        <a href="<?= $baseUrl ?>/logout" title="Cerrar Sesión" style="color:#EF4444;font-size:16px; transition: color 0.2s; display:flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px;" onmouseover="this.style.color='#F87171'" onmouseout="this.style.color='#EF4444'">
            <i class="fa-solid fa-right-from-bracket" style="font-size:15px;"></i>
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
    /* Premium Sidebar Styling - Dark Mode Corporate Slate Gradient */
    .sidebar {
        background: linear-gradient(180deg, #0B0F19 0%, #151D30 100%);
        border-right: 1px solid rgba(255, 255, 255, 0.05);
        color: #E2E8F0;
        transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1), transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        z-index: 1000;
        display: flex;
        flex-direction: column;
    }

    /* Sub-menu Options Style (Level 3 collapsible) */
    .sb-subopt-btn {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px 6px 36px; /* Indentation matched with Level 3 direct items */
        background: transparent;
        border: none;
        color: #94A3B8;
        cursor: pointer;
        text-align: left;
        border-radius: 6px;
        transition: all 0.2s;
    }
    .sb-subopt-btn:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.03);
    }
    .sb-subopt-btn.open {
        color: #fff;
    }
    .sb-subopt-btn.open .sso-chevron {
        transform: rotate(90deg);
    }
    .sso-chevron {
        margin-left: auto;
        transition: transform 0.2s;
    }
    .sso-icon {
        font-size: 10px;
        color: #64748B;
        width: 20px;
        height: 20px;
        border-radius: 5px;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .sb-subopt-btn:hover .sso-icon {
        background: rgba(255, 255, 255, 0.07);
        border-color: rgba(255, 255, 255, 0.12);
        color: #fff;
    }
    .sb-subopt-btn.open .sso-icon {
        background: rgba(56, 189, 248, 0.08) !important;
        border-color: rgba(56, 189, 248, 0.2) !important;
        color: #38bdf8 !important;
    }
    .sso-label {
        flex: 1;
        white-space: normal;
        word-wrap: break-word;
        word-break: break-word;
        line-height: 1.3;
    }

    /* Level 4 sub-items style */
    .sb-subitems {
        display: none;
        padding-left: 16px;
        border-left: 1px solid rgba(255, 255, 255, 0.05);
        margin-left: 16px;
        margin-top: 2px;
    }
    .sb-subitems.open {
        display: block;
        animation: slideDown 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .sb-subitem {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 12px;
        color: #64748B;
        text-decoration: none;
        border-radius: 6px;
        margin-bottom: 2px;
        transition: all 0.2s;
    }
    .sb-subitem:hover {
        color: #38bdf8;
        background: rgba(255, 255, 255, 0.03);
    }
    .sb-subitem.active {
        color: #38bdf8;
        font-weight: 600;
        background: rgba(56, 189, 248, 0.08);
    }

    /* Animations */
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .sb-flyout-card {
        position: fixed;
        z-index: 99999;
        width: 290px;
        padding: 16px;
        display: flex;
        gap: 14px;
        align-items: flex-start;
        transition: opacity 0.2s ease, transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        transform: translateX(-10px);
        opacity: 0;
        pointer-events: none;
        border-radius: 12px;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
        color: #E2E8F0;
    }
    .sb-flyout-card.show {
        opacity: 1;
        pointer-events: auto;
        transform: translateX(0);
    }
    .sb-flyout-card .sbf-title { color: #38BDF8; font-family: 'Outfit', sans-serif; }
    .sb-flyout-card .sbf-description { color: #94A3B8; font-family: 'Inter', sans-serif; }

    .sbf-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .sbf-subtitle {
        font-family: 'Outfit', sans-serif;
        font-size: 9.5px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.5;
    }

    .sbf-title {
        font-size: 14.5px;
        font-weight: 700;
        margin-bottom: 4px;
        line-height: 1.3;
    }

    .sbf-description {
        font-size: 11.5px;
        line-height: 1.5;
    }
</style>

<script>
    // Collapse/Expand operations for all levels
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
    });
</script>
