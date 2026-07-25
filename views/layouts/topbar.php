<?php
/**
 * Topbar Component — Matches PORTAL_APM_UNIFICADO.html topbar.
 * Background image with overlay, hamburger toggle, logo area, theme selector, user info.
 */
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = dirname($scriptName);
$baseUrl = str_replace('\\', '/', $baseUrl);
if ($baseUrl === '/' || $baseUrl === '\\') $baseUrl = '';
?>
<!-- Main workspace container (right side of sidebar) -->
<div class="main-workspace">
    
    <!-- Top Header Bar -->
    <div class="topbar">
        <button class="tb-toggle" id="toggle-sidebar-btn" title="Abrir/Cerrar Menu">
            <i data-lucide="menu" id="hamburgerIcon"></i>
        </button>
        
        <div class="tb-logo-area">
            <img src="<?= $baseUrl ?>/imgs/logoapm.png" alt="Logo APM" style="height:42px; width:auto; object-fit:contain; margin-right:10px;" onerror="this.style.display='none'">
            <div class="tla-name" style="font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700; color: #ffffff; letter-spacing: 0.5px; text-transform: uppercase; white-space: nowrap;">
                AUTORIDAD PORTUARIA DE MANTA
            </div>
        </div>
        
        <!-- Right section -->
        <div class="tb-right">
            <!-- Theme Selection Dropdown -->
            <div class="theme-dropdown-container">
                <button class="tb-icon-btn" title="Seleccionar Tema" onclick="C.toggleThemeMenu(event)">
                    <i data-lucide="palette" style="width:16px;height:16px;"></i>
                </button>
                <div class="tb-theme-dropdown" id="tbThemeDropdown">
                    <div class="tbd-header">Seleccionar Tema</div>
                    <button class="tbd-item" data-theme="1" onclick="C.selectThemeDirect(1, event)">
                        <span class="tbd-dot" style="background:#1A3A5C"></span>
                        <span class="tbd-name">Institucional</span>
                        <i data-lucide="check" class="tbd-check"></i>
                    </button>
                    <button class="tbd-item" data-theme="2" onclick="C.selectThemeDirect(2, event)">
                        <span class="tbd-dot" style="background:#0D1830"></span>
                        <span class="tbd-name">Cyber Dark</span>
                        <i data-lucide="check" class="tbd-check"></i>
                    </button>
                    <button class="tbd-item" data-theme="3" onclick="C.selectThemeDirect(3, event)">
                        <span class="tbd-dot" style="background:#0C4A6E"></span>
                        <span class="tbd-name">Porto Glass</span>
                        <i data-lucide="check" class="tbd-check"></i>
                    </button>
                </div>
            </div>
            
            <!-- User Avatar -->
            <div class="tb-avatar">
                <?= strtoupper(substr($_SESSION['user_name'] ?? 'AD', 0, 2)) ?>
            </div>
            <div class="tb-user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrador') ?></div>
            
            <!-- Logout -->
            <a href="<?= $baseUrl ?>/logout" class="tb-icon-btn" title="Cerrar sesion" style="color: var(--danger);">
                <i data-lucide="log-out" style="width:16px;height:16px;"></i>
            </a>
        </div>
    </div>
