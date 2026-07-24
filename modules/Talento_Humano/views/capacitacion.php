<?php
/** Capacitación y Desarrollo — fragmento SPA (DEMO: datos de muestra). */
$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$capacitaciones = $capacitaciones ?? []; $r = $resumen ?? [];
$badge = fn($est) => match ($est) {
    'Completado' => 'badge-success', 'En Curso' => 'badge-info', 'Planificado' => 'badge-warning', default => 'badge-info',
};
?>
<div style="animation:pageFadeIn .35s ease-out;">
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:var(--sp-4);">
        <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--primary-app),var(--primary-hover));display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-chalkboard-user" style="color:#fff;font-size:18px;"></i></div>
        <div><h2 style="font-size:1.35rem;font-weight:800;color:var(--text-app);margin:0;">Capacitación y Desarrollo</h2><p style="font-size:.78rem;color:var(--text-muted);margin:2px 0 0;"><?= (int)($r['total_horas'] ?? 0) ?> horas · <?= (int)($r['certificados'] ?? 0) ?> certificados</p></div>
    </div>
    <div class="alert alert-warning"><i class="fa-solid fa-flask"></i> Módulo demostrativo — datos de muestra. Pendiente de registro real de cursos y certificaciones (fase siguiente).</div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:var(--sp-4);">
        <?php foreach ($capacitaciones as $c): ?>
        <div class="card">
            <div class="card-body">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:8px;">
                    <strong style="color:var(--text-app);font-size:.95rem;"><?= $e($c['titulo']) ?></strong>
                    <span class="badge <?= $badge($c['estado']) ?>" style="flex-shrink:0;"><?= $e($c['estado']) ?></span>
                </div>
                <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:10px;"><?= $e($c['institucion']) ?> · <?= $e($c['modalidad']) ?> · <?= (int)$c['duracion_h'] ?>h</div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;font-size:.75rem;">
                    <span class="badge badge-info"><i class="fa-solid fa-tag"></i> <?= $e($c['categoria']) ?></span>
                    <span class="badge badge-info"><i class="fa-solid fa-users"></i> <?= count($c['participantes']) ?> part.</span>
                    <span class="badge badge-success"><i class="fa-solid fa-certificate"></i> <?= (int)$c['certificados'] ?> cert.</span>
                </div>
                <div style="font-size:.72rem;color:var(--text-muted);margin-top:10px;"><i class="fa-regular fa-calendar"></i> <?= date('d/m/Y', strtotime($c['fecha_inicio'])) ?> — <?= date('d/m/Y', strtotime($c['fecha_fin'])) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
