<?php
/** Evaluación y Desempeño — fragmento SPA (DEMO: datos de muestra). */
$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$evaluaciones = $evaluaciones ?? []; $r = $resumen ?? [];
$badge = fn($est) => match ($est) {
    'Completada' => 'badge-success', 'Pendiente' => 'badge-warning', 'En proceso' => 'badge-info', default => 'badge-info',
};
?>
<div style="animation:pageFadeIn .35s ease-out;">
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:var(--sp-4);">
        <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--primary-app),var(--primary-hover));display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-chart-line" style="color:#fff;font-size:18px;"></i></div>
        <div><h2 style="font-size:1.35rem;font-weight:800;color:var(--text-app);margin:0;">Evaluación y Desempeño</h2><p style="font-size:.78rem;color:var(--text-muted);margin:2px 0 0;">Promedio institucional: <?= $e($r['promedio'] ?? 0) ?></p></div>
    </div>
    <div class="alert alert-warning"><i class="fa-solid fa-flask"></i> Módulo demostrativo — datos de muestra. Pendiente de formularios de evaluación reales (fase siguiente).</div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:var(--sp-3);margin-bottom:var(--sp-4);">
        <?php foreach ([['Completadas',$r['completadas']??0,'fa-circle-check','#28a745'],['En proceso',$r['en_proceso']??0,'fa-spinner','#17a2b8'],['Pendientes',$r['pendientes']??0,'fa-hourglass-half','#fd7e14']] as $kpi): ?>
        <div class="card"><div class="card-body" style="display:flex;align-items:center;gap:12px;">
            <i class="fa-solid <?= $kpi[2] ?>" style="font-size:1.4rem;color:<?= $kpi[3] ?>;"></i>
            <div><div style="font-size:1.4rem;font-weight:800;color:var(--text-app);line-height:1;"><?= (int)$kpi[1] ?></div><div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;"><?= $e($kpi[0]) ?></div></div>
        </div></div>
        <?php endforeach; ?>
    </div>

    <div class="card"><div style="overflow-x:auto;"><table>
        <thead><tr><th>Funcionario</th><th>Cargo</th><th>Período</th><th>Calificación</th><th>Nivel</th><th>Objetivos</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($evaluaciones as $ev): ?>
            <tr>
                <td style="font-weight:600;font-size:.85rem;"><?= $e($ev['nombre']) ?></td>
                <td style="font-size:.82rem;color:var(--text-muted);"><?= $e($ev['cargo']) ?></td>
                <td style="font-size:.82rem;"><?= $e($ev['periodo']) ?></td>
                <td style="font-size:.85rem;font-weight:700;"><?= $ev['calificacion'] !== null ? $e(number_format((float)$ev['calificacion'], 1)) : '—' ?></td>
                <td style="font-size:.82rem;"><?= $e($ev['nivel']) ?></td>
                <td style="font-size:.82rem;"><?= (int)$ev['objetivos_met'] ?>/<?= (int)$ev['objetivos_total'] ?></td>
                <td><span class="badge <?= $badge($ev['estado']) ?>"><?= $e($ev['estado']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>
</div>
