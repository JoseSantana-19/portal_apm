<?php
$notifModel = new UsuarioModel();
$notifCount = $notifModel->getNotifCount((int)($_SESSION['user_id'] ?? 0));
$nivel      = (int)($_SESSION['nivel_jerarquia'] ?? 0);
$userName   = $_SESSION['nombre_completo'] ?? 'Usuario';
$currentTheme = $_SESSION['tema'] ?? 'light';

$shortName = explode(' ', $userName)[0];
?>
<header class="topbar" id="topbar">

    <!-- Left: sidebar toggle + brand + breadcrumb -->
    <div class="topbar-left">
        <button id="toggle-sidebar-btn" class="topbar-btn" title="Menú">
            <i data-lucide="menu" id="hamburgerIcon"></i>
        </button>
        <div class="topbar-brand">
            <img src="<?= APP_URL ?>/imgs/logoapm.png" alt="Logo APM" class="topbar-brand-logo" onerror="this.style.display='none'">
            <span class="topbar-brand-name">Autoridad Portuaria de Manta</span>
        </div>
        <nav class="topbar-breadcrumb" aria-label="Ruta">
            <span id="breadcrumb-text">Dashboard</span>
        </nav>
    </div>

    <!-- Right: search, theme, notifs, user -->
    <div class="topbar-right">

        <!-- Search (optional, opens modal) -->
        <button type="button" class="topbar-btn" id="search-btn" title="Buscar">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>

        <!-- Theme toggle -->
        <div class="theme-dropdown-container" style="position:relative;">
            <button class="topbar-btn" onclick="C.toggleThemeMenu(event)" title="Cambiar tema" id="theme-toggle-btn">
                <i class="fa-solid fa-palette"></i>
            </button>
            <div id="tbThemeDropdown" class="tb-theme-dropdown">
                <div style="padding:4px 10px 6px;font-size:0.68rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:var(--text-muted);border-bottom:1px solid var(--border-app);margin-bottom:4px;">Tema visual</div>
                <button onclick="C.selectThemeDirect(1,event)" data-theme="1" class="tb-theme-item">
                    <span style="width:10px;height:10px;border-radius:50%;background:#2E75B6;display:inline-block;flex-shrink:0;"></span>
                    ⚓ Institucional
                </button>
                <button onclick="C.selectThemeDirect(2,event)" data-theme="2" class="tb-theme-item">
                    <span style="width:10px;height:10px;border-radius:50%;background:#3B82F6;display:inline-block;flex-shrink:0;"></span>
                    ◈ Cyber Dark
                </button>
                <button onclick="C.selectThemeDirect(3,event)" data-theme="3" class="tb-theme-item">
                    <span style="width:10px;height:10px;border-radius:50%;background:#10B981;display:inline-block;flex-shrink:0;"></span>
                    〜 Porto Glass
                </button>
            </div>
        </div>

        <!-- Notifications -->
        <div class="topbar-notif-wrap">
            <button type="button" class="topbar-btn topbar-notif-btn" id="notif-btn"
                    title="Notificaciones">
                <i class="fa-solid fa-bell"></i>
                <?php if ($notifCount > 0): ?>
                <span class="notif-badge" id="notif-badge"><?= min($notifCount, 99) ?></span>
                <?php else: ?>
                <span class="notif-badge hidden" id="notif-badge">0</span>
                <?php endif; ?>
            </button>
            <!-- Dropdown -->
            <div class="notif-dropdown" id="notif-dropdown" hidden>
                <div class="notif-header">
                    <span class="notif-title">Notificaciones</span>
                    <button type="button" class="btn btn-ghost btn-sm" id="mark-all-read">Marcar todo leído</button>
                </div>
                <div class="notif-list" id="notif-list">
                    <div class="notif-empty">
                        <i class="fa-regular fa-bell-slash"></i>
                        <span>Sin notificaciones</span>
                    </div>
                </div>
                <a href="<?= APP_URL ?>/notificaciones" class="notif-footer" data-spa>
                    Ver todas <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- User menu -->
        <div class="topbar-user-wrap">
            <button type="button" class="topbar-user" id="user-menu-btn">
                <div class="topbar-user-avatar">
                    <?php
                    $words = explode(' ', $userName);
                    $init  = '';
                    foreach (array_slice($words, 0, 2) as $w) { $init .= mb_strtoupper(mb_substr($w, 0, 1)); }
                    echo htmlspecialchars($init, ENT_QUOTES, 'UTF-8');
                    ?>
                </div>
                <span class="topbar-user-name"><?= htmlspecialchars($shortName, ENT_QUOTES, 'UTF-8') ?></span>
                <i class="fa-solid fa-chevron-down topbar-chevron"></i>
            </button>
            <!-- Dropdown -->
            <div class="user-dropdown" id="user-dropdown" hidden>
                <div class="user-dropdown-header">
                    <div class="user-dropdown-name"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="user-dropdown-role">
                        <?= $nivel >= 3 ? 'Director / Gerente' : ($nivel >= 2 ? 'Jefatura' : ($nivel >= 1 ? 'Analista' : 'Operativo')) ?>
                    </div>
                </div>
                <div class="user-dropdown-items">
                    <a href="<?= APP_URL ?>/perfil" class="user-dropdown-item" data-spa>
                        <i class="fa-solid fa-user"></i> Mi Perfil
                    </a>
                    <a href="<?= APP_URL ?>/cambiar-contrasena" class="user-dropdown-item" data-spa>
                        <i class="fa-solid fa-lock"></i> Cambiar Contraseña
                    </a>
                    <?php if ($nivel >= 3): ?>
                    <a href="<?= APP_URL ?>/admin/usuarios" class="user-dropdown-item" data-spa>
                        <i class="fa-solid fa-users-gear"></i> Administración
                    </a>
                    <?php endif; ?>
                    <hr class="user-dropdown-divider">
                    <a href="<?= APP_URL ?>/logout" class="user-dropdown-item user-dropdown-logout">
                        <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>

    </div><!-- /topbar-right -->
</header>
