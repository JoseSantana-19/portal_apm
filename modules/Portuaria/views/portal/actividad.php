<?php
/** Actividad de seguridad (rondas del día + últimos registros CCTV) — shell del portal. */
$fmt = function ($v, string $f = 'H:i') {
    if ($v instanceof DateTimeInterface) return $v->format($f);
    return is_string($v) && $v !== '' ? $v : '—';
};
?>
<div style="animation:pageFadeIn .3s ease both;">

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div>
        <h2 class="page-title"><i class="fa-solid fa-shield-halved" style="color:#6f42c1;margin-right:var(--sp-2);"></i> Actividad de Seguridad</h2>
        <p class="page-subtitle">Rondas de guardias y monitoreo CCTV del módulo Portuaria</p>
    </div>
    <div style="display:flex;gap:var(--sp-2);">
        <a href="<?= APP_URL ?>/portuaria" class="btn btn-outline btn-sm" data-spa><i class="fa-solid fa-anchor"></i> Panel Portuario</a>
        <a href="<?= APP_URL ?>/rondas" class="btn btn-primary btn-sm"><i class="fa-solid fa-arrow-up-right-from-square"></i> Bitácora completa</a>
    </div>
</div>

<div style="display:flex;gap:var(--sp-3);margin-bottom:var(--sp-4);flex-wrap:wrap;">
    <?php foreach ([
        ['fa-user-clock',           $kpis['visitas_activas'],      'Visitas activas',      '#28a745'],
        ['fa-clipboard-check',      $kpis['rondas_hoy'],           'Registros de ronda hoy','#6f42c1'],
        ['fa-triangle-exclamation', $kpis['alertas_criticas_24h'], 'Alertas críticas 24h',  $kpis['alertas_criticas_24h'] > 0 ? '#dc3545' : '#6c757d'],
    ] as [$ico, $n, $l, $c]): ?>
    <div class="card" style="flex:1;min-width:170px;"><div class="card-body" style="display:flex;align-items:center;gap:var(--sp-3);">
        <div style="width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:color-mix(in srgb,<?= $c ?> 13%,transparent);color:<?= $c ?>;"><i class="fa-solid <?= $ico ?>"></i></div>
        <div><div style="font-size:1.3rem;font-weight:800;line-height:1;"><?= (int)$n ?></div><div style="font-size:.7rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));"><?= $l ?></div></div>
    </div></div>
    <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-4);align-items:start;" class="act-grid-2">

    <!-- Rondas del día -->
    <div class="card"><div class="card-body" style="padding:0;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:var(--sp-2);padding:var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));flex-wrap:wrap;">
            <h3 style="font-size:.95rem;font-weight:700;margin:0;"><i class="fa-solid fa-clipboard-check" style="color:#6f42c1;margin-right:6px;"></i>Rondas — <?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></h3>
            <form method="get" action="<?= APP_URL ?>/portuaria/actividad" style="display:flex;gap:var(--sp-2);">
                <input type="date" name="fecha" value="<?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?>" style="padding:5px 8px;border:1px solid var(--border-app,var(--color-border));border-radius:8px;background:var(--bg-app,transparent);color:var(--text-app,inherit);font-size:.8rem;">
                <button type="submit" class="btn btn-outline btn-sm">Ver</button>
            </form>
        </div>
        <div style="overflow-x:auto;max-height:430px;overflow-y:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr>
                <?php foreach (['Hora','Turno','Guardia','Actividad','Alerta'] as $h): ?>
                <th style="text-align:left;font-size:.7rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));padding:var(--sp-2) var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));position:sticky;top:0;background:var(--surface-app,var(--color-surface));"><?= $h ?></th>
                <?php endforeach; ?>
            </tr></thead>
            <tbody>
            <?php if (empty($rondas)): ?>
                <tr><td colspan="5" style="text-align:center;padding:var(--sp-8);color:var(--text-muted,var(--color-text-muted));"><i class="fa-solid fa-moon" style="font-size:1.6rem;opacity:.4;display:block;margin-bottom:8px;"></i>Sin registros de ronda en esta fecha.</td></tr>
            <?php else: foreach ($rondas as $r): ?>
                <tr style="border-bottom:1px solid var(--border-app,var(--color-border));font-size:.83rem;">
                    <td style="padding:var(--sp-2) var(--sp-3);font-family:var(--font-mono,monospace);"><?= $fmt($r['hora_registro'], 'H:i') ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);"><?= htmlspecialchars($r['turno'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);color:var(--text-muted,var(--color-text-muted));"><?= htmlspecialchars($r['guardia'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);max-width:260px;"><?= htmlspecialchars($r['actividad'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);"><span style="background:color-mix(in srgb,<?= htmlspecialchars($r['alerta_color'], ENT_QUOTES, 'UTF-8') ?> 15%,transparent);color:<?= htmlspecialchars($r['alerta_color'], ENT_QUOTES, 'UTF-8') ?>;font-size:.7rem;padding:2px 8px;border-radius:20px;font-weight:700;"><?= htmlspecialchars($r['alerta'], ENT_QUOTES, 'UTF-8') ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div></div>

    <!-- Últimos registros CCTV -->
    <div class="card"><div class="card-body" style="padding:0;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));">
            <h3 style="font-size:.95rem;font-weight:700;margin:0;"><i class="fa-solid fa-camera" style="color:#fd7e14;margin-right:6px;"></i>Últimos registros CCTV</h3>
            <a href="<?= APP_URL ?>/camaras" class="btn btn-outline btn-sm">Bitácora CCTV</a>
        </div>
        <div style="overflow-x:auto;max-height:430px;overflow-y:auto;">
        <table style="width:100%;border-collapse:collapse;">
            <thead><tr>
                <?php foreach (['Código','Fecha','Hora','Cámara','Novedad','Alerta'] as $h): ?>
                <th style="text-align:left;font-size:.7rem;text-transform:uppercase;color:var(--text-muted,var(--color-text-muted));padding:var(--sp-2) var(--sp-3);border-bottom:1px solid var(--border-app,var(--color-border));position:sticky;top:0;background:var(--surface-app,var(--color-surface));"><?= $h ?></th>
                <?php endforeach; ?>
            </tr></thead>
            <tbody>
            <?php if (empty($cctv)): ?>
                <tr><td colspan="6" style="text-align:center;padding:var(--sp-8);color:var(--text-muted,var(--color-text-muted));"><i class="fa-solid fa-video-slash" style="font-size:1.6rem;opacity:.4;display:block;margin-bottom:8px;"></i>Sin registros de bitácora CCTV.</td></tr>
            <?php else: foreach ($cctv as $c): ?>
                <tr style="border-bottom:1px solid var(--border-app,var(--color-border));font-size:.83rem;">
                    <td style="padding:var(--sp-2) var(--sp-3);font-family:var(--font-mono,monospace);color:#fd7e14;font-weight:600;"><?= htmlspecialchars($c['codigo_bitacora'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);"><?= $fmt($c['fecha'], 'Y-m-d') ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);font-family:var(--font-mono,monospace);"><?= $fmt($c['hora_registro'], 'H:i') ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);"><?= htmlspecialchars($c['camara'] ?? '—', ENT_QUOTES, 'UTF-8') ?><div style="font-size:.7rem;color:var(--text-muted,var(--color-text-muted));"><?= htmlspecialchars($c['ubicacion'] ?? '', ENT_QUOTES, 'UTF-8') ?></div></td>
                    <td style="padding:var(--sp-2) var(--sp-3);max-width:220px;"><?= htmlspecialchars($c['novedad'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="padding:var(--sp-2) var(--sp-3);"><span style="background:color-mix(in srgb,<?= htmlspecialchars($c['alerta_color'], ENT_QUOTES, 'UTF-8') ?> 15%,transparent);color:<?= htmlspecialchars($c['alerta_color'], ENT_QUOTES, 'UTF-8') ?>;font-size:.7rem;padding:2px 8px;border-radius:20px;font-weight:700;"><?= htmlspecialchars($c['alerta'], ENT_QUOTES, 'UTF-8') ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        </div>
    </div></div>
</div>

<style>
@media (max-width: 1100px) { .act-grid-2 { grid-template-columns: 1fr !important; } }
</style>
</div>
