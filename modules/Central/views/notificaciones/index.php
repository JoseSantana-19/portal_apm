<?php
/**
 * Centro de Notificaciones (Elite Edition) — Central Portal APM
 * Consola reactiva de avisos institucionales, alertas de inventario, bitácoras y eventos del sistema.
 */
$total = count($notifs ?? []);
$noLeidas = count(array_filter($notifs ?? [], fn($n) => empty($n['leida'])));
$altas = count(array_filter($notifs ?? [], fn($n) => ((int)($n['prioridad'] ?? 0)) >= 3));
$medias = count(array_filter($notifs ?? [], fn($n) => ((int)($n['prioridad'] ?? 0)) === 2));
?>

<div class="dashboard-wrapper anim-up anim-d0">

    <!-- ══════════════════════════════════════════════════════════════
         PREMIUM HEADER
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-page-header">
        <div class="admin-header-title-group">
            <div class="admin-header-icon" style="background:linear-gradient(135deg, #F59E0B, #D97706);">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div>
                <div class="admin-header-eyebrow">
                    <i class="fa-solid fa-inbox"></i> Mi Cuenta &bull; Centro de Alertas
                </div>
                <h1 class="admin-header-title">Centro de Notificaciones</h1>
                <div class="admin-header-subtitle">
                    Avisos en tiempo real, alertas de inventario, rondas de vigilancia y eventos de ciberseguridad
                </div>
            </div>
        </div>

        <div style="display:flex;gap:var(--sp-2);align-items:center;">
            <?php if ($noLeidas > 0): ?>
            <form method="POST" action="<?= APP_URL ?>/notificaciones/marcar-leidas">
                <input type="hidden" name="_csrf_token" value="<?= SecurityHelper::csrfToken() ?>">
                <button type="submit" class="btn-dash btn-dash-primary">
                    <i class="fa-solid fa-check-double"></i> Marcar Todo como Leído (<?= $noLeidas ?>)
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         STATISTICS GRID
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-stat-grid">
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #0284C7 15%, transparent);color:#0284C7;">
                <i class="fa-solid fa-inbox"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $total ?></div>
                <div class="admin-stat-label">Total Notificaciones</div>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #F59E0B 15%, transparent);color:#F59E0B;">
                <i class="fa-solid fa-envelope-open-text"></i>
            </div>
            <div>
                <div class="admin-stat-num" style="color:<?= $noLeidas > 0 ? '#F59E0B' : 'inherit' ?>;"><?= $noLeidas ?></div>
                <div class="admin-stat-label">Pendientes por Leer</div>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #EF4444 15%, transparent);color:#EF4444;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
                <div class="admin-stat-num" style="color:<?= $altas > 0 ? '#EF4444' : 'inherit' ?>;"><?= $altas ?></div>
                <div class="admin-stat-label">Prioridad Alta</div>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #10B981 15%, transparent);color:#10B981;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $total - $noLeidas ?></div>
                <div class="admin-stat-label">Leídas / Atendidas</div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         FILTER & SEARCH CONTROLS
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-card" style="margin-bottom:var(--sp-4);padding:var(--sp-4);">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <!-- Category Tabs -->
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <button type="button" class="notif-tab-btn active" onclick="filtrarNotificaciones('todas', this)">
                    <i class="fa-solid fa-list"></i> Todas (<?= $total ?>)
                </button>
                <button type="button" class="notif-tab-btn" onclick="filtrarNotificaciones('no-leidas', this)">
                    <i class="fa-solid fa-envelope"></i> No leídas (<?= $noLeidas ?>)
                </button>
                <button type="button" class="notif-tab-btn" onclick="filtrarNotificaciones('alta', this)">
                    <i class="fa-solid fa-triangle-exclamation" style="color:#EF4444;"></i> Alta Prioridad (<?= $altas ?>)
                </button>
            </div>

            <!-- Search Bar -->
            <div style="position:relative;min-width:240px;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:12px;"></i>
                <input type="text" id="notif-search-input" placeholder="Filtrar por texto..." class="form-control" style="padding-left:34px;height:36px;font-size:0.8rem;" oninput="buscarNotificaciones(this.value)">
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         NOTIFICATIONS STREAM
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-card">
        <div class="dash-card-header">
            <div>
                <div class="dash-card-title">
                    <i class="fa-solid fa-stream" style="color:var(--primary-hover);"></i>
                    Bandeja de Avisos y Eventos
                </div>
                <div class="dash-card-subtitle">Flujo cronológico de alertas emitidas por el generador de eventos</div>
            </div>
            <span class="beacon-pulse"><span class="beacon-dot"></span> Tiempo Real</span>
        </div>

        <div id="notif-container">
            <?php if (empty($notifs)): ?>
            <div style="text-align:center;padding:var(--sp-10);color:var(--text-muted);">
                <div style="width:72px;height:72px;border-radius:50%;background:color-mix(in srgb, #10B981 15%, transparent);color:#10B981;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 14px;">
                    <i class="fa-solid fa-bell-slash"></i>
                </div>
                <h4 style="font-size:1.15rem;font-weight:800;color:var(--text-app);margin:0 0 4px;">Sin notificaciones pendientes</h4>
                <p style="font-size:0.85rem;margin:0;">Estás al día con todos los avisos y alertas operativas del sistema.</p>
            </div>
            <?php else: ?>
            <?php foreach ($notifs as $n):
                $prio = (int)($n['prioridad'] ?? 0);
                $prioColors = [3 => '#EF4444', 2 => '#F59E0B', 1 => '#0284C7', 0 => '#64748B'];
                $prioLabels = [3 => 'Alta', 2 => 'Media', 1 => 'Informativa', 0 => 'General'];
                $prioIcons  = [3 => 'fa-triangle-exclamation', 2 => 'fa-circle-exclamation', 1 => 'fa-circle-info', 0 => 'fa-bell'];
                $pColor = $prioColors[$prio] ?? '#0284C7';
                $pLbl   = $prioLabels[$prio] ?? 'Informativa';
                $pIco   = $prioIcons[$prio]  ?? 'fa-bell';

                $fecha = $n['fecha_creacion'];
                if ($fecha instanceof DateTime) { $fecha = $fecha->format('d/m/Y H:i'); }
                elseif (is_string($fecha)) { $fecha = date('d/m/Y H:i', strtotime($fecha)); }
                $esNoLeida = empty($n['leida']);
                $tipoPrio = $prio >= 3 ? 'alta' : ($prio === 2 ? 'media' : 'baja');
            ?>
            <div class="notif-item-card <?= $esNoLeida ? 'unread' : '' ?>" data-prio="<?= $tipoPrio ?>" data-leida="<?= $esNoLeida ? '0' : '1' ?>" data-texto="<?= htmlspecialchars(mb_strtolower(($n['titulo'] ?? '') . ' ' . ($n['mensaje'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                <div class="notif-icon-circle" style="background:color-mix(in srgb, <?= $pColor ?> 15%, transparent);color:<?= $pColor ?>;">
                    <i class="fa-solid <?= $pIco ?>"></i>
                </div>

                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:4px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <strong style="font-size:0.92rem;color:var(--text-app);">
                                <?= htmlspecialchars($n['titulo'] ?? 'Aviso del Sistema', ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                            <?php if ($esNoLeida): ?>
                            <span class="badge badge-warning" style="font-size:0.65rem;padding:2px 7px;font-weight:800;">
                                <i class="fa-solid fa-sparkles" style="font-size:7px;"></i> Nuevo
                            </span>
                            <?php endif; ?>
                        </div>
                        <span style="font-size:0.75rem;color:var(--text-muted);font-family:var(--font-code);">
                            <i class="fa-regular fa-clock" style="margin-right:3px;"></i> <?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <div style="font-size:0.83rem;color:var(--text-muted);line-height:1.45;">
                        <?= htmlspecialchars($n['mensaje'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;flex-wrap:wrap;gap:8px;">
                        <span class="badge" style="background:color-mix(in srgb, <?= $pColor ?> 12%, transparent);color:<?= $pColor ?>;font-size:0.7rem;font-weight:700;">
                            Prioridad <?= $pLbl ?>
                        </span>

                        <?php if (!empty($n['url_accion'])): 
                            $rawUrl = $n['url_accion'];
                            $linkHref = (strpos($rawUrl, 'http://') === 0 || strpos($rawUrl, 'https://') === 0) 
                                ? $rawUrl 
                                : APP_URL . '/' . ltrim($rawUrl, '/');
                            $isExternalApp = strpos($rawUrl, '/apps/') !== false;
                        ?>
                        <a href="<?= htmlspecialchars($linkHref, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-ghost btn-sm" <?= $isExternalApp ? 'data-no-spa target="_blank"' : 'data-spa' ?> style="font-size:0.78rem;padding:3px 12px;font-weight:700;color:var(--primary-hover);">
                            Ver Detalle <i class="fa-solid fa-arrow-right" style="font-size:10px;margin-left:4px;"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
var filtroActual = 'todas';
var textoFiltro = '';

function filtrarNotificaciones(tipo, btn) {
    filtroActual = tipo;
    document.querySelectorAll('.notif-tab-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    aplicarFiltros();
}

function buscarNotificaciones(txt) {
    textoFiltro = txt.trim().toLowerCase();
    aplicarFiltros();
}

function aplicarFiltros() {
    var items = document.querySelectorAll('.notif-item-card');
    items.forEach(function(item) {
        var prio = item.getAttribute('data-prio');
        var leida = item.getAttribute('data-leida');
        var texto = item.getAttribute('data-texto') || '';

        var matchTipo = true;
        if (filtroActual === 'no-leidas') {
            matchTipo = leida === '0';
        } else if (filtroActual === 'alta') {
            matchTipo = prio === 'alta';
        }

        var matchTexto = true;
        if (textoFiltro) {
            matchTexto = texto.indexOf(textoFiltro) !== -1;
        }

        if (matchTipo && matchTexto) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}
</script>
