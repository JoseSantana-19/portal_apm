<?php
/** Historial laboral jerárquico / Reportes — fragmento SPA. */
$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$historial = $historial ?? [];
$filtro = $filtro_cargo ?? '';
$expQs = $filtro !== '' ? ('?' . http_build_query(['cargo' => $filtro])) : '';
?>
<div style="animation:pageFadeIn .35s ease-out;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-3);margin-bottom:var(--sp-5);">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--primary-app),var(--primary-hover));display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-timeline" style="color:#fff;font-size:18px;"></i>
            </div>
            <div>
                <h2 style="font-size:1.35rem;font-weight:800;color:var(--text-app);margin:0;">Historial / Reportes</h2>
                <p style="font-size:.78rem;color:var(--text-muted);margin:2px 0 0;">Trayectoria laboral jerárquica con fusiones organizacionales</p>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:var(--sp-2);align-items:flex-end;">
            <div style="display:flex;gap:var(--sp-2);">
                <a href="<?= APP_URL ?>/th/reporte/export/excel<?= $expQs ?>" class="btn btn-ghost btn-sm" title="Exportar a Excel"><i class="fa-solid fa-file-excel" style="color:#1D6F42;"></i> Excel</a>
                <a href="<?= APP_URL ?>/th/reporte/export/pdf<?= $expQs ?>" class="btn btn-ghost btn-sm" target="_blank" rel="noopener" title="Exportar a PDF"><i class="fa-solid fa-file-pdf" style="color:#c0392b;"></i> PDF</a>
            </div>
            <form method="GET" action="<?= APP_URL ?>/th/reporte" style="display:flex;gap:var(--sp-2);align-items:flex-end;">
                <div class="form-group" style="margin:0;min-width:200px;">
                    <label class="form-label">Filtrar por cargo</label>
                    <input type="text" name="cargo" class="form-control" placeholder="Ej: DIRECTOR" value="<?= $e($filtro) ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="margin-bottom:1px;"><i class="fa-solid fa-filter"></i></button>
            </form>
        </div>
    </div>

    <?php if (empty($historial)): ?>
    <div class="card"><div class="card-body">
        <div class="alert alert-info" style="margin:0;">
            <i class="fa-solid fa-circle-info"></i>
            No hay registros de historial laboral todavía. La tabla <code>th_historial_laboral</code> fue creada durante la integración; cárguela con los periodos de asignación de cada funcionario para poblar este reporte.
        </div>
    </div></div>
    <?php else: ?>
    <div class="card"><div style="overflow-x:auto;">
        <table>
            <thead><tr>
                <th>Cédula</th><th>Funcionario</th><th>Puesto</th><th>Dirección (histórico)</th>
                <th>Sub-área</th><th>Unificada en</th><th>Desde</th><th>Hasta</th><th>Años</th>
            </tr></thead>
            <tbody>
            <?php foreach ($historial as $h): ?>
                <tr>
                    <td><code style="font-family:var(--font-code);font-size:.78rem;color:var(--text-muted);background:var(--accent-app);padding:2px 6px;border-radius:4px;"><?= $e($h['cedula']) ?></code></td>
                    <td style="font-weight:600;font-size:.85rem;"><?= $e($h['funcionario']) ?></td>
                    <td style="font-size:.83rem;color:var(--text-muted);"><?= $e($h['nombre_puesto']) ?></td>
                    <td style="font-size:.83rem;"><?= $e($h['departamento_historico']) ?></td>
                    <td style="font-size:.83rem;"><?= $e($h['sub_area'] ?: '—') ?></td>
                    <td style="font-size:.83rem;"><?= $e($h['direccion_actual_unificada']) ?></td>
                    <td style="font-size:.8rem;white-space:nowrap;"><?= !empty($h['fecha_desde']) ? date('d/m/Y', strtotime($h['fecha_desde'])) : '—' ?></td>
                    <td style="font-size:.8rem;white-space:nowrap;"><?= !empty($h['fecha_hasta']) ? date('d/m/Y', strtotime($h['fecha_hasta'])) : 'Actual' ?></td>
                    <td><span class="badge badge-info"><?= (int)($h['anios_permanencia'] ?? 0) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div></div>
    <?php endif; ?>
</div>
