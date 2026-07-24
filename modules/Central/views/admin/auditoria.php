<?php
/** Auditoría del Sistema — filtros, resumen, tabla completa, export PDF/Excel, paginación. */
$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$f = $filtros ?? ['q'=>'','modulo'=>'','operacion'=>'','resultado'=>'','desde'=>'','hasta'=>''];
$page = $page ?? 1; $total = $total ?? 0; $totalPages = $totalPages ?? 1;
$exitos = $exitos ?? 0; $errores = $errores ?? 0;
$qs = http_build_query(array_filter($f, fn($v) => $v !== ''));          // filtros actuales
$pageQs = fn($p) => http_build_query(array_merge(array_filter($f, fn($v)=>$v!==''), ['pagina'=>$p]));
$fecha = function ($v) {
    if ($v instanceof DateTime) return $v->format('d/m/Y H:i:s');
    if (is_string($v) && $v !== '') return date('d/m/Y H:i:s', strtotime($v));
    return '—';
};
?>
<div style="animation:pageFadeIn .35s ease-out;">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:var(--sp-3);margin-bottom:var(--sp-4);">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--primary-app),var(--primary-hover));display:flex;align-items:center;justify-content:center;">
                <i class="fa-solid fa-shield-halved" style="color:#fff;font-size:18px;"></i>
            </div>
            <div>
                <h2 style="font-size:1.35rem;font-weight:800;color:var(--text-app);margin:0;">Auditoría del Sistema</h2>
                <p style="font-size:.78rem;color:var(--text-muted);margin:2px 0 0;">Registro inmutable de operaciones · <?= number_format($total) ?> resultado(s)</p>
            </div>
        </div>
        <div style="display:flex;gap:var(--sp-2);">
            <a href="<?= APP_URL ?>/admin/auditoria/export/excel<?= $qs ? '?'.$e($qs) : '' ?>" class="btn btn-ghost" title="Exportar a Excel (.xlsx)">
                <i class="fa-solid fa-file-excel" style="color:#1D6F42;"></i> Excel
            </a>
            <a href="<?= APP_URL ?>/admin/auditoria/export/pdf<?= $qs ? '?'.$e($qs) : '' ?>" class="btn btn-ghost" target="_blank" rel="noopener" title="Exportar a PDF">
                <i class="fa-solid fa-file-pdf" style="color:#c0392b;"></i> PDF
            </a>
        </div>
    </div>

    <!-- Resumen -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:var(--sp-3);margin-bottom:var(--sp-4);">
        <?php foreach ([['Resultados',$total,'fa-list','var(--primary-hover)'],['Éxitos',$exitos,'fa-circle-check','#28a745'],['Errores',$errores,'fa-circle-xmark','#dc3545'],['Páginas',$totalPages,'fa-layer-group','#8b5cf6']] as $kpi): ?>
        <div class="card"><div class="card-body" style="display:flex;align-items:center;gap:12px;">
            <i class="fa-solid <?= $kpi[2] ?>" style="font-size:1.4rem;color:<?= $kpi[3] ?>;"></i>
            <div><div style="font-size:1.4rem;font-weight:800;color:var(--text-app);line-height:1;"><?= number_format((int)$kpi[1]) ?></div>
            <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;"><?= $e($kpi[0]) ?></div></div>
        </div></div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <div class="card" style="margin-bottom:var(--sp-4);">
        <div class="card-body">
            <form method="GET" action="<?= APP_URL ?>/admin/auditoria" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:var(--sp-3);align-items:end;">
                <div class="form-group" style="margin:0;grid-column:1/-1;">
                    <label class="form-label"><i class="fa-solid fa-magnifying-glass" style="opacity:.6;margin-right:4px;"></i> Buscar (usuario, detalle, IP, tabla)</label>
                    <input type="text" name="q" class="form-control" value="<?= $e($f['q']) ?>" placeholder="Texto libre…">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Módulo</label>
                    <select name="modulo" class="form-control"><option value="">Todos</option>
                        <?php foreach (($modulos ?? []) as $m): ?><option value="<?= $e($m) ?>" <?= $f['modulo']===$m?'selected':'' ?>><?= $e($m) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Operación</label>
                    <select name="operacion" class="form-control"><option value="">Todas</option>
                        <?php foreach (($operaciones ?? []) as $o): ?><option value="<?= $e($o) ?>" <?= $f['operacion']===$o?'selected':'' ?>><?= $e($o) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Resultado</label>
                    <select name="resultado" class="form-control"><option value="">Todos</option>
                        <?php foreach (($resultados ?? []) as $r): ?><option value="<?= $e($r) ?>" <?= $f['resultado']===$r?'selected':'' ?>><?= $e($r) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" class="form-control" value="<?= $e($f['desde']) ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="hasta" class="form-control" value="<?= $e($f['hasta']) ?>">
                </div>
                <div style="display:flex;gap:var(--sp-2);">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filtrar</button>
                    <a href="<?= APP_URL ?>/admin/auditoria" class="btn btn-ghost" data-spa><i class="fa-solid fa-rotate-left"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card">
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr><th>Fecha</th><th>Usuario</th><th>Módulo</th><th>Operación</th><th>Tabla</th><th>IP</th><th>Resultado</th><th>Detalle</th></tr>
                </thead>
                <tbody>
                <?php if (empty($registros)): ?>
                    <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:var(--sp-10);">
                        <i class="fa-regular fa-folder-open" style="font-size:2rem;display:block;margin-bottom:var(--sp-2);opacity:.3;"></i>
                        Sin registros para los filtros seleccionados.
                    </td></tr>
                <?php else: foreach ($registros as $r):
                    $esErr = ($r['resultado'] ?? '') !== 'EXITO';
                ?>
                    <tr>
                        <td style="white-space:nowrap;font-size:.75rem;color:var(--text-muted);"><?= $e($fecha($r['fecha_registro'] ?? null)) ?></td>
                        <td style="font-size:.83rem;font-weight:600;"><?= $e($r['nombre_usuario'] ?? 'Sistema') ?></td>
                        <td><code style="font-family:var(--font-code);font-size:.72rem;background:var(--accent-app);padding:2px 6px;border-radius:4px;"><?= $e($r['modulo'] ?? '') ?></code></td>
                        <td style="font-size:.8rem;"><?= $e($r['operacion'] ?? '') ?></td>
                        <td style="font-size:.78rem;color:var(--text-muted);"><?= $e($r['tabla_afectada'] ?: '—') ?></td>
                        <td style="font-size:.75rem;color:var(--text-muted);white-space:nowrap;"><?= $e($r['ip_address'] ?: '—') ?></td>
                        <td><span class="badge <?= $esErr ? 'badge-danger' : 'badge-success' ?>"><i class="fa-solid fa-<?= $esErr?'xmark':'check' ?>" style="font-size:8px;"></i> <?= $e($r['resultado'] ?? '') ?></span></td>
                        <td style="font-size:.78rem;color:var(--text-muted);max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= $e($r['detalle'] ?? '') ?>"><?= $e($r['detalle'] ?: '—') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
        <div style="padding:var(--sp-4);display:flex;gap:var(--sp-2);align-items:center;justify-content:center;flex-wrap:wrap;border-top:1px solid var(--border-app);">
            <a href="<?= APP_URL ?>/admin/auditoria?<?= $e($pageQs(max(1,$page-1))) ?>" class="btn btn-ghost btn-sm <?= $page<=1?'':'' ?>" data-spa style="<?= $page<=1?'pointer-events:none;opacity:.4;':'' ?>"><i class="fa-solid fa-chevron-left"></i> Anterior</a>
            <span style="font-size:.82rem;color:var(--text-muted);">Página <strong style="color:var(--text-app);"><?= $page ?></strong> de <?= $totalPages ?></span>
            <a href="<?= APP_URL ?>/admin/auditoria?<?= $e($pageQs(min($totalPages,$page+1))) ?>" class="btn btn-ghost btn-sm" data-spa style="<?= $page>=$totalPages?'pointer-events:none;opacity:.4;':'' ?>">Siguiente <i class="fa-solid fa-chevron-right"></i></a>
        </div>
        <?php endif; ?>
    </div>
</div>
