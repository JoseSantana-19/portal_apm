<?php
/** Asistencia y Turnos — fragmento SPA (DEMO: datos de muestra). */
$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$registros = $registros ?? []; $r = $resumen ?? [];
$badge = fn($est) => match ($est) {
    'Normal' => 'badge-success', 'Atraso' => 'badge-warning',
    'Ausente' => 'badge-danger', 'Horas Extra' => 'badge-info', default => 'badge-info',
};
?>
<div style="animation:pageFadeIn .35s ease-out;">
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:var(--sp-4);">
        <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--primary-app),var(--primary-hover));display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-user-clock" style="color:#fff;font-size:18px;"></i></div>
        <div><h2 style="font-size:1.35rem;font-weight:800;color:var(--text-app);margin:0;">Asistencia y Turnos</h2><p style="font-size:.78rem;color:var(--text-muted);margin:2px 0 0;">Jornada del <?= $e($fecha_hoy ?? date('d/m/Y')) ?></p></div>
    </div>
    <div class="alert alert-warning"><i class="fa-solid fa-flask"></i> Módulo demostrativo — datos de muestra. Pendiente de integración con el reloj biométrico (fase siguiente).</div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:var(--sp-3);margin-bottom:var(--sp-4);">
        <?php foreach ([['Registros',$r['total_registros']??0,'fa-list','var(--primary-hover)'],['Presentes',$r['presentes']??0,'fa-user-check','#28a745'],['Ausentes',$r['ausentes']??0,'fa-user-xmark','#dc3545'],['Atrasos',$r['atrasos']??0,'fa-clock','#fd7e14'],['Horas extra',number_format((float)($r['horas_extras']??0),1),'fa-hourglass','#17a2b8']] as $kpi): ?>
        <div class="card"><div class="card-body" style="display:flex;align-items:center;gap:12px;">
            <i class="fa-solid <?= $kpi[2] ?>" style="font-size:1.4rem;color:<?= $kpi[3] ?>;"></i>
            <div><div style="font-size:1.4rem;font-weight:800;color:var(--text-app);line-height:1;"><?= $e($kpi[1]) ?></div><div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;"><?= $e($kpi[0]) ?></div></div>
        </div></div>
        <?php endforeach; ?>
    </div>

    <div class="card"><div style="overflow-x:auto;"><table>
        <thead><tr><th>Funcionario</th><th>Cargo</th><th>Entrada</th><th>Salida</th><th>Horas</th><th>Extra</th><th>Atraso</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($registros as $x): ?>
            <tr>
                <td style="font-weight:600;font-size:.85rem;"><?= $e($x['nombre']) ?></td>
                <td style="font-size:.82rem;color:var(--text-muted);"><?= $e($x['cargo']) ?></td>
                <td style="font-size:.82rem;"><?= $e($x['hora_entrada'] ?: '—') ?></td>
                <td style="font-size:.82rem;"><?= $e($x['hora_salida'] ?: '—') ?></td>
                <td style="font-size:.82rem;"><?= $e(number_format((float)$x['horas_trabajadas'], 2)) ?></td>
                <td style="font-size:.82rem;"><?= $e(number_format((float)$x['horas_extras'], 2)) ?></td>
                <td style="font-size:.82rem;"><?= (int)$x['atraso_min'] ?> min</td>
                <td><span class="badge <?= $badge($x['estado']) ?>"><?= $e($x['estado']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>
</div>
