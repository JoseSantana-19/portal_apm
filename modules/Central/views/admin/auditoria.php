<?php
/**
 * Auditoría del Sistema — Central Portal APM
 * Registro inmutable de transacciones cross-DB, trazabilidad forense y visor de diffs.
 */
$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$f = $filtros ?? ['q'=>'','modulo'=>'','operacion'=>'','resultado'=>'','desde'=>'','hasta'=>''];
$page = $page ?? 1; $total = $total ?? 0; $totalPages = $totalPages ?? 1;
$exitos = $exitos ?? 0; $errores = $errores ?? 0;
$qs = http_build_query(array_filter($f, fn($v) => $v !== ''));
$pageQs = fn($p) => http_build_query(array_merge(array_filter($f, fn($v)=>$v!==''), ['pagina'=>$p]));

$fecha = function ($v) {
    if ($v instanceof DateTime) return $v->format('d/m/Y H:i:s');
    if (is_string($v) && $v !== '') return date('d/m/Y H:i:s', strtotime($v));
    return '—';
};

$ipLabel = function (?string $ip) {
    if (!$ip) return ['—', null];
    if ($ip === '::1' || $ip === '127.0.0.1') return ['Local (Dev)', $ip];
    return [$ip, null];
};
?>

<div class="dashboard-wrapper anim-up anim-d0">

    <!-- ══════════════════════════════════════════════════════════════
         PREMIUM ADMIN HEADER
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-page-header">
        <div class="admin-header-title-group">
            <div class="admin-header-icon" style="background:linear-gradient(135deg, #8B5CF6, #6D28D9);">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <div class="admin-header-eyebrow">
                    <i class="fa-solid fa-shield-halved"></i> Gobernanza &bull; Trazabilidad y Seguridad
                </div>
                <h1 class="admin-header-title">Auditoría del Sistema</h1>
                <div class="admin-header-subtitle">
                    Registro forense inmutable de operaciones cross-DB &bull; <?= number_format($total) ?> evento(s) registrados
                </div>
            </div>
        </div>

        <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;align-items:center;">
            <a href="<?= APP_URL ?>/admin/auditoria/export/excel<?= $qs ? '?'.$e($qs) : '' ?>" class="btn-dash" title="Exportar resultados a Excel (.xlsx)">
                <i class="fa-solid fa-file-excel" style="color:#10B981;"></i> Excel
            </a>
            <a href="<?= APP_URL ?>/admin/auditoria/export/pdf<?= $qs ? '?'.$e($qs) : '' ?>" class="btn-dash" target="_blank" rel="noopener" title="Exportar reporte de auditoría a PDF">
                <i class="fa-solid fa-file-pdf" style="color:#EF4444;"></i> PDF
            </a>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         STATISTICS GRID
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-stat-grid">
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #0284C7 15%, transparent);color:#0284C7;">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= number_format((int)$total) ?></div>
                <div class="admin-stat-label">Total Transacciones</div>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #10B981 15%, transparent);color:#10B981;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= number_format((int)$exitos) ?></div>
                <div class="admin-stat-label">Operaciones Exitosas</div>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #EF4444 15%, transparent);color:#EF4444;">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= number_format((int)$errores) ?></div>
                <div class="admin-stat-label">Errores / Fallos</div>
            </div>
        </div>

        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #8B5CF6 15%, transparent);color:#8B5CF6;">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= number_format((int)$totalPages) ?></div>
                <div class="admin-stat-label">Páginas de Historial</div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         FILTER CONSOLE CARD
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-card" style="margin-bottom:var(--sp-5);">
        <div class="dash-card-header">
            <div>
                <div class="dash-card-title">
                    <i class="fa-solid fa-filter" style="color:var(--primary-hover);"></i>
                    Consola de Filtrado Forense
                </div>
                <div class="dash-card-subtitle">Busca por usuario, IP, módulo, tabla afectada o rango de fechas</div>
            </div>
            <?php if (!empty($qs)): ?>
            <a href="<?= APP_URL ?>/admin/auditoria" class="btn btn-ghost btn-sm" data-spa title="Restablecer filtros">
                <i class="fa-solid fa-rotate-left"></i> Limpiar Filtros
            </a>
            <?php endif; ?>
        </div>

        <div style="padding:var(--sp-4);">
            <form method="GET" action="<?= APP_URL ?>/admin/auditoria" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:var(--sp-3);align-items:end;">
                <div class="form-group" style="margin:0;grid-column:1/-1;">
                    <label class="form-label" style="font-size:0.75rem;font-weight:700;margin-bottom:4px;">
                        <i class="fa-solid fa-magnifying-glass" style="margin-right:4px;color:var(--primary-hover);"></i> Búsqueda General
                    </label>
                    <input type="text" name="q" class="form-control" value="<?= $e($f['q']) ?>" placeholder="Buscar por usuario, detalle, IP, tabla o registro...">
                </div>

                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.75rem;font-weight:700;margin-bottom:4px;">Módulo</label>
                    <select name="modulo" class="form-control">
                        <option value="">Todos los módulos</option>
                        <?php foreach (($modulos ?? []) as $m): ?>
                        <option value="<?= $e($m) ?>" <?= $f['modulo']===$m?'selected':'' ?>><?= $e($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.75rem;font-weight:700;margin-bottom:4px;">Operación</label>
                    <select name="operacion" class="form-control">
                        <option value="">Todas las operaciones</option>
                        <?php foreach (($operaciones ?? []) as $o): ?>
                        <option value="<?= $e($o) ?>" <?= $f['operacion']===$o?'selected':'' ?>><?= $e($o) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.75rem;font-weight:700;margin-bottom:4px;">Resultado</label>
                    <select name="resultado" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach (($resultados ?? []) as $r): ?>
                        <option value="<?= $e($r) ?>" <?= $f['resultado']===$r?'selected':'' ?>><?= $e($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.75rem;font-weight:700;margin-bottom:4px;">Fecha Desde</label>
                    <input type="date" name="desde" class="form-control" value="<?= $e($f['desde']) ?>">
                </div>

                <div class="form-group" style="margin:0;">
                    <label class="form-label" style="font-size:0.75rem;font-weight:700;margin-bottom:4px;">Fecha Hasta</label>
                    <input type="date" name="hasta" class="form-control" value="<?= $e($f['hasta']) ?>">
                </div>

                <div style="display:flex;gap:var(--sp-2);">
                    <button type="submit" class="btn btn-primary" style="flex:1;">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    <a href="<?= APP_URL ?>/admin/auditoria" class="btn btn-ghost" data-spa title="Restablecer">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         AUDIT LOGS TABLE
         ══════════════════════════════════════════════════════════════ -->
    <div class="dash-card">
        <div class="dash-card-header">
            <div>
                <div class="dash-card-title">
                    <i class="fa-solid fa-list-timeline" style="color:var(--primary-hover);"></i>
                    Transacciones Auditadas
                </div>
                <div class="dash-card-subtitle">Bitácora cronológica con captura de cambios antes/después</div>
            </div>
        </div>

        <div class="dash-table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>Usuario</th>
                        <th>Módulo</th>
                        <th>Operación</th>
                        <th>Tabla Afectada</th>
                        <th>Dirección IP</th>
                        <th>Resultado</th>
                        <th>Detalle & Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="8" style="text-align:center;color:var(--text-muted);padding:var(--sp-10);">
                        <i class="fa-regular fa-folder-open" style="font-size:2rem;display:block;margin-bottom:var(--sp-2);opacity:.4;"></i>
                        Sin registros para los filtros seleccionados
                    </td>
                </tr>
                <?php else: foreach ($registros as $r):
                    $esErr = ($r['resultado'] ?? '') !== 'EXITO';
                    [$ipTxt, $ipFull] = $ipLabel($r['ip_address'] ?? null);
                    $tieneCambios = !empty($r['datos_antes']) || !empty($r['datos_despues']);
                ?>
                <tr>
                    <td style="white-space:nowrap;font-size:0.75rem;color:var(--text-muted);font-family:var(--font-code);">
                        <?= $e($fecha($r['fecha_registro'] ?? null)) ?>
                    </td>
                    <td style="font-size:0.82rem;font-weight:700;color:var(--text-app);">
                        <?= $e($r['nombre_usuario'] ?? 'Sistema') ?>
                    </td>
                    <td>
                        <span class="dash-mod-badge">
                            <?= $e($r['modulo'] ?? 'CORE') ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:600;font-size:0.8rem;color:var(--text-app);">
                            <?= $e($r['operacion'] ?? '') ?>
                        </div>
                        <?php if (!empty($r['id_registro'])): ?>
                        <span style="font-family:var(--font-code);font-size:0.68rem;color:var(--text-muted);">
                            ID #<?= $e($r['id_registro']) ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-size:0.75rem;color:var(--text-muted);">
                            <?= $e($r['tabla_afectada'] ?: '—') ?>
                        </span>
                    </td>
                    <td style="font-size:0.75rem;color:var(--text-muted);white-space:nowrap;" <?= $ipFull ? 'title="'.$e($ipFull).'"' : '' ?>>
                        <code style="font-size:0.72rem;"><?= $e($ipTxt) ?></code>
                    </td>
                    <td>
                        <span class="badge badge-<?= $esErr ? 'danger' : 'success' ?>" style="font-size:0.68rem;">
                            <i class="fa-solid fa-<?= $esErr ? 'xmark' : 'check' ?>" style="font-size:8px;"></i> <?= $e($r['resultado'] ?? '') ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span style="font-size:0.75rem;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;" title="<?= $e($r['detalle'] ?? '') ?>">
                                <?= $e($r['detalle'] ?: '—') ?>
                            </span>
                            <?php if ($tieneCambios): ?>
                            <button type="button" class="btn btn-ghost btn-sm" style="padding:2px 8px;flex-shrink:0;"
                                    title="Ver detalle del cambio (Diff Antes/Después)"
                                    onclick='verDetalleAuditoria(<?= json_encode([
                                        'operacion' => $r['operacion'] ?? '',
                                        'tabla'     => $r['tabla_afectada'] ?? '',
                                        'idReg'     => $r['id_registro'] ?? '',
                                        'usuario'   => $r['nombre_usuario'] ?? 'Sistema',
                                        'fecha'     => $fecha($r['fecha_registro'] ?? null),
                                        'antes'     => $r['datos_antes'] ?? null,
                                        'despues'   => $r['datos_despues'] ?? null,
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                <i class="fa-solid fa-eye" style="color:var(--primary-hover);"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ($totalPages > 1): ?>
        <div style="padding:var(--sp-4);display:flex;gap:var(--sp-2);align-items:center;justify-content:center;flex-wrap:wrap;border-top:1px solid var(--border-app);">
            <a href="<?= APP_URL ?>/admin/auditoria?<?= $e($pageQs(max(1,$page-1))) ?>" class="btn btn-ghost btn-sm" data-spa style="<?= $page<=1?'pointer-events:none;opacity:.4;':'' ?>">
                <i class="fa-solid fa-chevron-left"></i> Anterior
            </a>
            <span style="font-size:0.82rem;color:var(--text-muted);">
                Página <strong style="color:var(--text-app);"><?= $page ?></strong> de <?= $totalPages ?>
            </span>
            <a href="<?= APP_URL ?>/admin/auditoria?<?= $e($pageQs(min($totalPages,$page+1))) ?>" class="btn btn-ghost btn-sm" data-spa style="<?= $page>=$totalPages?'pointer-events:none;opacity:.4;':'' ?>">
                Siguiente <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- Script del Visor de Diffs -->
<script>
function verDetalleAuditoria(info) {
    let antes = {}, despues = {};
    try { antes   = info.antes   ? JSON.parse(info.antes)   : {}; } catch (e) {}
    try { despues = info.despues ? JSON.parse(info.despues) : {}; } catch (e) {}

    const claves = Array.from(new Set([...Object.keys(antes), ...Object.keys(despues)])).sort();
    const fmt = (v) => {
        if (v === null || v === undefined || v === '') return '<span style="opacity:.4;">—</span>';
        if (typeof v === 'object') return portalAlertEscape(JSON.stringify(v));
        return portalAlertEscape(String(v));
    };

    let filas = '';
    if (claves.length === 0) {
        filas = '<tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:14px;">Sin datos de campos para esta operación.</td></tr>';
    } else {
        for (const k of claves) {
            const cambio = JSON.stringify(antes[k] ?? null) !== JSON.stringify(despues[k] ?? null);
            filas += `<tr>
                <td style="padding:8px 12px;font-weight:700;white-space:nowrap;color:var(--text-app);">${portalAlertEscape(k)}</td>
                <td style="padding:8px 12px;${cambio ? 'text-decoration:line-through;color:var(--danger);opacity:.7;' : 'color:var(--text-muted);'}">${fmt(antes[k])}</td>
                <td style="padding:8px 12px;${cambio ? 'font-weight:700;color:var(--success);' : 'color:var(--text-app);'}">${fmt(despues[k])}</td>
            </tr>`;
        }
    }

    const idLinea = info.idReg ? ` #${portalAlertEscape(String(info.idReg))}` : '';
    Swal.fire({
        title: `${portalAlertEscape(info.operacion)} — ${portalAlertEscape(info.tabla || '')}${idLinea}`,
        html: `
            <div style="text-align:left;font-size:0.8rem;color:var(--text-muted);margin-bottom:12px;">
                <i class="fa-solid fa-user" style="margin-right:4px;"></i> ${portalAlertEscape(info.usuario)} &bull; <i class="fa-regular fa-clock" style="margin-right:4px;"></i> ${portalAlertEscape(info.fecha)}
            </div>
            <div style="max-height:380px;overflow:auto;border:1px solid var(--border-app);border-radius:var(--radius-md);">
                <table style="width:100%;border-collapse:collapse;font-size:0.82rem;">
                    <thead>
                        <tr style="background:var(--accent-app);border-bottom:1px solid var(--border-app);">
                            <th style="padding:8px 12px;text-align:left;">Campo</th>
                            <th style="padding:8px 12px;text-align:left;">Valor Anterior</th>
                            <th style="padding:8px 12px;text-align:left;">Valor Nuevo</th>
                        </tr>
                    </thead>
                    <tbody>${filas}</tbody>
                </table>
            </div>
        `,
        width: 600,
        confirmButtonText: 'Cerrar',
    });
}
</script>
