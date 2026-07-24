<?php
/** Vacaciones y Ausencias — fragmento SPA (DEMO: datos de muestra). */
$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$solicitudes = $solicitudes ?? []; $saldos = $saldos ?? [];
$badge = fn($est) => match ($est) {
    'Aprobada' => 'badge-success', 'Pendiente' => 'badge-warning', 'Rechazada' => 'badge-danger', default => 'badge-info',
};
?>
<div style="animation:pageFadeIn .35s ease-out;">
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:var(--sp-4);">
        <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--primary-app),var(--primary-hover));display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-umbrella-beach" style="color:#fff;font-size:18px;"></i></div>
        <div><h2 style="font-size:1.35rem;font-weight:800;color:var(--text-app);margin:0;">Vacaciones y Ausencias</h2><p style="font-size:.78rem;color:var(--text-muted);margin:2px 0 0;"><?= (int)($total_pendientes ?? 0) ?> pendientes · <?= (int)($total_aprobadas ?? 0) ?> aprobadas</p></div>
    </div>
    <div class="alert alert-warning"><i class="fa-solid fa-flask"></i> Módulo demostrativo — datos de muestra. Pendiente de tabla de solicitudes y flujo de aprobación (fase siguiente).</div>

    <div class="card" style="margin-bottom:var(--sp-4);">
        <div class="card-header"><i class="fa-solid fa-paper-plane" style="color:var(--primary-hover);"></i><span class="card-title">Solicitudes</span></div>
        <div style="overflow-x:auto;"><table>
            <thead><tr><th>Funcionario</th><th>Tipo</th><th>Desde</th><th>Hasta</th><th>Días</th><th>Estado</th><th>Aprobado por</th></tr></thead>
            <tbody>
            <?php foreach ($solicitudes as $s): ?>
                <tr>
                    <td style="font-weight:600;font-size:.85rem;"><?= $e($s['nombre']) ?></td>
                    <td style="font-size:.82rem;"><?= $e($s['tipo']) ?></td>
                    <td style="font-size:.8rem;"><?= date('d/m/Y', strtotime($s['fecha_inicio'])) ?></td>
                    <td style="font-size:.8rem;"><?= date('d/m/Y', strtotime($s['fecha_fin'])) ?></td>
                    <td><span class="badge badge-info"><?= (int)$s['dias_solicitados'] ?></span></td>
                    <td><span class="badge <?= $badge($s['estado']) ?>"><?= $e($s['estado']) ?></span></td>
                    <td style="font-size:.8rem;color:var(--text-muted);"><?= $e($s['aprobado_por'] ?: '—') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>

    <div class="card">
        <div class="card-header"><i class="fa-solid fa-calendar-check" style="color:var(--primary-hover);"></i><span class="card-title">Saldo vacacional</span></div>
        <div style="overflow-x:auto;"><table>
            <thead><tr><th>Funcionario</th><th>Acumulados</th><th>Usados</th><th>Disponibles</th></tr></thead>
            <tbody>
            <?php foreach ($saldos as $s): ?>
                <tr>
                    <td style="font-weight:600;font-size:.85rem;"><?= $e($s['nombre']) ?></td>
                    <td style="font-size:.85rem;"><?= (int)$s['dias_acumulados'] ?></td>
                    <td style="font-size:.85rem;"><?= (int)$s['dias_usados'] ?></td>
                    <td><span class="badge badge-success"><?= (int)$s['dias_disponibles'] ?> días</span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
</div>
