<?php // $notifs injected by NotificacionesController::index() ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-5);">
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-bell" style="color:var(--color-warning);margin-right:var(--sp-2);"></i>
        Notificaciones
    </h2>
    <?php if (!empty($notifs)): ?>
    <form method="POST" action="<?= APP_URL ?>/notificaciones/marcar-leidas">
        <input type="hidden" name="_csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
        <button type="submit" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-check-double"></i> Marcar todo leído
        </button>
    </form>
    <?php endif; ?>
</div>

<div class="card">
    <?php if (empty($notifs)): ?>
    <div class="empty-state" style="padding:var(--sp-10);">
        <i class="fa-regular fa-bell-slash" style="font-size:3rem;color:var(--color-text-muted);display:block;margin-bottom:var(--sp-3);"></i>
        <p>Sin notificaciones pendientes</p>
    </div>
    <?php else: ?>
    <?php foreach ($notifs as $n):
        $prioClass = [3 => 'high', 2 => 'medium'][(int)($n['prioridad'] ?? 0)] ?? 'low';
        $fecha = $n['fecha_creacion'];
        if ($fecha instanceof DateTime) { $fecha = $fecha->format('d/m/Y H:i'); }
        elseif (is_string($fecha)) { $fecha = date('d/m/Y H:i', strtotime($fecha)); }
    ?>
    <div class="alert-item <?= !$n['leida'] ? 'unread' : '' ?>"
         style="<?= !$n['leida'] ? 'background:color-mix(in srgb,var(--color-primary) 3%,transparent);' : '' ?>">
        <div class="alert-priority <?= $prioClass ?>"></div>
        <div class="alert-body">
            <div class="alert-title-text"><?= htmlspecialchars($n['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="alert-meta">
                <?= htmlspecialchars($n['mensaje'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                &mdash; <span style="color:var(--color-text-muted);"><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
        <?php if (!empty($n['url_accion'])): 
            $rawUrl = $n['url_accion'];
            $linkHref = (strpos($rawUrl, 'http://') === 0 || strpos($rawUrl, 'https://') === 0) 
                ? $rawUrl 
                : APP_URL . '/' . ltrim($rawUrl, '/');
            $isExternalApp = strpos($rawUrl, '/apps/') !== false;
        ?>
        <a href="<?= htmlspecialchars($linkHref, ENT_QUOTES, 'UTF-8') ?>" class="alert-action-link" <?= $isExternalApp ? 'data-no-spa target="_blank"' : 'data-spa' ?>>Ver</a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
