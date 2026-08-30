<?php
/**
 * Encabezado superior compartido.
 * Variables opcionales:
 * $topbarTitle, $topbarSubtitle, $topbarShowSearch,
 * $topbarBackUrl, $topbarBackLabel, $topbarBackIcon.
 */
$topbarTitle = $topbarTitle ?? 'Autoridad Portuaria de Manta';
$topbarSubtitle = $topbarSubtitle ?? 'Módulo Talento Humano';
$topbarShowSearch = $topbarShowSearch ?? true;
// Sin valor explícito: "Volver al Portal APM" solo si esta sesión TH tiene
// contraparte en el portal (puente de identidad, en cualquiera de los dos
// sentidos -- ver Auth::loginTrusted()/Auth::syncPortalSession()). Cuentas
// exclusivas de TH (sin acceso al portal) no ven el botón, igual que en
// Control de Bienes.
$topbarBackUrl = $topbarBackUrl ?? (
    (!empty($_SESSION['tiene_acceso_portal']) && defined('PORTAL_ROOT_URL')) ? PORTAL_ROOT_URL : null
);
$topbarBackLabel = $topbarBackLabel ?? 'Volver al Portal APM';
$topbarBackIcon = $topbarBackIcon ?? 'bi-arrow-left';
$topbarUser = Auth::user() ?? [];
$topbarContext = class_exists(TopbarService::class)
    ? TopbarService::context($topbarUser)
    : [
        'name' => (string)($topbarUser['name'] ?? 'Usuario Talento Humano'),
        'role' => (string)($topbarUser['role'] ?? 'APM'),
        'email' => '',
        'identification' => '',
        'photo' => 'public/img/default_avatar.png',
        'initials' => 'AP',
        'notifications' => [],
    ];
$topbarNotifications = is_array($topbarContext['notifications'] ?? null)
    ? $topbarContext['notifications']
    : [];
$topbarNotificationCount = count($topbarNotifications);
$topbarPhoto = BASE_URL . '/' . ltrim((string)($topbarContext['photo'] ?? 'public/img/default_avatar.png'), '/');
$topbarName = trim((string)($topbarContext['name'] ?? 'Usuario Talento Humano'));
$topbarRole = trim((string)($topbarContext['role'] ?? 'APM'));
$topbarIdentification = trim((string)($topbarContext['identification'] ?? ''));
?>
<header class="topbar">
    <div class="topbar-left">
        <div class="brand">
            <img src="<?= IMG_URL ?>/logoapm.png" alt="Logo APM">
            <div>
                <h1><?= htmlspecialchars($topbarTitle) ?></h1>
                <p><?= htmlspecialchars($topbarSubtitle) ?></p>
            </div>
        </div>
    </div>

    <div class="topbar-actions">
        <?php if ($topbarShowSearch): ?>
        <form class="search global-search-form" action="<?= BASE_URL ?>/talento-humano/directorio" method="get" role="search">
            <button type="submit" aria-label="Buscar personal" title="Buscar personal">
                <i class="bi bi-search" aria-hidden="true"></i>
            </button>
            <input type="search" id="globalSearch" name="q" value="<?= htmlspecialchars((string)($_GET['q'] ?? '')) ?>"
                   placeholder="Buscar personal en la plataforma" autocomplete="off" aria-label="Buscar personal">
        </form>
        <?php endif; ?>

        <div class="icon-chip topbar-date-chip" title="Fecha actual">
            <i class="bi bi-calendar-event" aria-hidden="true"></i><span id="currentDate" data-institutional-date="<?= htmlspecialchars(InstitutionalClock::todayIso()) ?>">--</span>
        </div>

        <button class="topbar-icon-button" id="themeToggle" type="button"
                aria-label="Activar modo oscuro" title="Activar modo oscuro">
            <i class="bi bi-moon-stars-fill" aria-hidden="true"></i>
        </button>

        <div class="topbar-popover">
            <button class="topbar-icon-button" id="notificationToggle" type="button"
                    aria-label="Notificaciones<?= $topbarNotificationCount > 0 ? ': ' . $topbarNotificationCount . ' pendientes' : '' ?>"
                    aria-controls="notificationPanel" aria-expanded="false" title="Notificaciones">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <?php if ($topbarNotificationCount > 0): ?>
                    <span class="topbar-notification-badge" aria-hidden="true"><?= min($topbarNotificationCount, 99) ?></span>
                <?php endif; ?>
            </button>
            <section class="topbar-panel topbar-notification-panel" id="notificationPanel" hidden aria-label="Centro de notificaciones">
                <header class="topbar-panel-header">
                    <div>
                        <strong>Notificaciones</strong>
                        <small><?= $topbarNotificationCount > 0 ? $topbarNotificationCount . ' asunto(s) por atender' : 'Todo está al día' ?></small>
                    </div>
                    <i class="bi bi-bell-fill" aria-hidden="true"></i>
                </header>
                <div class="topbar-notification-list">
                    <?php if ($topbarNotifications): ?>
                        <?php foreach ($topbarNotifications as $notification): ?>
                        <a class="topbar-notification-item tone-<?= htmlspecialchars((string)($notification['tone'] ?? 'info')) ?>"
                           href="<?= BASE_URL . htmlspecialchars((string)($notification['url'] ?? '/talento-humano/inicio')) ?>">
                            <span class="topbar-notification-icon"><i class="bi <?= htmlspecialchars((string)($notification['icon'] ?? 'bi-info-circle')) ?>" aria-hidden="true"></i></span>
                            <span>
                                <strong><?= htmlspecialchars((string)($notification['title'] ?? 'Aviso')) ?></strong>
                                <small><?= htmlspecialchars((string)($notification['text'] ?? '')) ?></small>
                            </span>
                            <i class="bi bi-chevron-right topbar-notification-arrow" aria-hidden="true"></i>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="topbar-empty-state">
                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                            <strong>Sin novedades pendientes</strong>
                            <span>No hay alertas que requieran su atención.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <?php if ($topbarBackUrl): ?>
        <a href="<?= htmlspecialchars($topbarBackUrl) ?>" class="btn btn-ghost topbar-back">
            <i class="bi <?= htmlspecialchars($topbarBackIcon) ?>" aria-hidden="true"></i>
            <span><?= htmlspecialchars($topbarBackLabel) ?></span>
        </a>
        <?php endif; ?>

        <div class="topbar-popover topbar-profile-popover">
            <button class="topbar-profile-trigger" id="profileToggle" type="button"
                    aria-controls="profilePanel" aria-expanded="false" title="Cuenta de usuario">
                <img src="<?= htmlspecialchars($topbarPhoto) ?>" alt="Foto de <?= htmlspecialchars($topbarName) ?>">
                <span class="topbar-profile-copy">
                    <strong><?= htmlspecialchars($topbarName) ?></strong>
                    <small><?= htmlspecialchars($topbarRole) ?></small>
                </span>
                <i class="bi bi-chevron-down topbar-profile-chevron" aria-hidden="true"></i>
            </button>
            <section class="topbar-panel topbar-profile-panel" id="profilePanel" hidden aria-label="Opciones de cuenta">
                <div class="topbar-profile-summary">
                    <img src="<?= htmlspecialchars($topbarPhoto) ?>" alt="">
                    <div>
                        <strong><?= htmlspecialchars($topbarName) ?></strong>
                        <small><?= htmlspecialchars((string)($topbarContext['email'] ?? '')) ?: htmlspecialchars($topbarRole) ?></small>
                    </div>
                </div>
                <nav aria-label="Cuenta de usuario">
                    <?php if ($topbarIdentification !== ''): ?>
                    <a href="<?= BASE_URL ?>/talento-humano/empleado/perfil/<?= rawurlencode($topbarIdentification) ?>">
                        <i class="bi bi-person-vcard" aria-hidden="true"></i><span>Mi perfil institucional</span>
                    </a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/cuenta/seguridad">
                        <i class="bi bi-shield-check" aria-hidden="true"></i><span>Seguridad y doble factor</span>
                    </a>
                    <a href="<?= BASE_URL ?>/cuenta/cambiar-clave">
                        <i class="bi bi-key" aria-hidden="true"></i><span>Cambiar contraseña</span>
                    </a>
                </nav>
                <form method="post" action="<?= BASE_URL ?>/logout">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
                    <button type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i><span>Cerrar sesión</span></button>
                </form>
            </section>
        </div>
    </div>
</header>
