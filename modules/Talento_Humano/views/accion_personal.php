<?php
/** Acción de Personal (LOSEP Art. 21) — fragmento SPA. Selección de servidor por round-trip. */
$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$emp    = $empleado ?? null;
$areas  = $areas  ?? [];
$cargos = $cargos ?? [];
$acciones = $acciones ?? [];
$nro    = $nroAccion ?? '';
$empId  = (int)($emp['id'] ?? $emp['empleado_id'] ?? 0);
$sel    = fn($a, $b) => ((string)$a === (string)$b) ? 'selected' : '';
$okMsg  = SessionHelper::getFlash('success');
$errMsg = SessionHelper::getFlash('error');
$tipos  = ['INGRESO','REINGRESO','RESTITUCIÓN','REINTEGRO','ASCENSO','TRASLADO','TRASPASO',
           'CAMBIO ADMINISTRATIVO','INTERCAMBIO VOLUNTARIO','LICENCIA','COMISIÓN DE SERVICIOS',
           'INCREMENTO RMU','SUBROGACIÓN','ENCARGO','CESACIÓN DE FUNCIONES','DESTITUCIÓN',
           'VACACIONES','SANCIONES','REVISIÓN CLASIFICACIÓN PUESTO','OTRO'];
?>
<div style="animation:pageFadeIn .35s ease-out;max-width:1000px;">

    <?php if ($okMsg): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= $e($okMsg) ?></div><?php endif; ?>
    <?php if ($errMsg): ?><div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation"></i> <?= $e($errMsg) ?></div><?php endif; ?>

    <div style="display:flex;align-items:center;gap:12px;margin-bottom:var(--sp-5);">
        <a href="<?= APP_URL ?>/th/directorio" class="btn btn-ghost btn-sm" data-spa><i class="fa-solid fa-arrow-left"></i></a>
        <div>
            <h2 style="font-size:1.3rem;font-weight:800;color:var(--text-app);margin:0;"><i class="fa-solid fa-file-signature" style="color:var(--primary-hover);margin-right:6px;"></i> Acción de Personal</h2>
            <p style="font-size:.78rem;color:var(--text-muted);margin:2px 0 0;">Documento legal · N° <strong><?= $e($nro) ?></strong></p>
        </div>
    </div>

    <!-- Selección de servidor -->
    <div class="card" style="margin-bottom:var(--sp-4);">
        <div class="card-header"><i class="fa-solid fa-user-magnifying-glass" style="color:var(--primary-hover);"></i><span class="card-title">Servidor público</span></div>
        <div class="card-body">
            <form method="GET" action="<?= APP_URL ?>/th/accion-personal" style="display:flex;gap:var(--sp-2);align-items:flex-end;flex-wrap:wrap;">
                <div class="form-group" style="margin:0;min-width:240px;">
                    <label class="form-label">Buscar por cédula</label>
                    <input type="text" name="cedula" class="form-control" placeholder="Cédula del funcionario" value="<?= $e($emp['cedula'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="margin-bottom:1px;"><i class="fa-solid fa-magnifying-glass"></i> Cargar</button>
            </form>
            <?php if ($emp): ?>
            <div style="margin-top:var(--sp-3);padding:12px 14px;border-radius:10px;background:var(--accent-app);display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <i class="fa-solid fa-user-check" style="color:var(--primary-hover);"></i>
                <strong style="color:var(--text-app);"><?= $e(trim(($emp['apellidos'] ?? '') . ' ' . ($emp['nombres'] ?? ''))) ?></strong>
                <span style="color:var(--text-muted);font-size:.85rem;"><?= $e($emp['cedula']) ?> · <?= $e($emp['cargo'] ?: 'Sin cargo') ?> · <?= $e($emp['direccion_area'] ?: 'Sin área') ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

<?php if ($emp): ?>
    <form method="POST" action="<?= APP_URL ?>/th/accion-personal/guardar">
        <?= SecurityHelper::csrfField() ?>
        <input type="hidden" name="numero_accion" value="<?= $e($nro) ?>">
        <input type="hidden" name="empleado_id" value="<?= $empId ?>">
        <input type="hidden" name="actual_unidad_id" value="<?= (int)($emp['unidad_id'] ?? 0) ?>">
        <input type="hidden" name="actual_puesto_id" value="<?= (int)($emp['puesto_id'] ?? 0) ?>">
        <input type="hidden" name="actual_remuneracion" value="<?= $e($emp['sueldo_rmu'] ?? 0) ?>">

        <!-- Tipo + vigencia -->
        <div class="card" style="margin-bottom:var(--sp-4);">
            <div class="card-header"><i class="fa-solid fa-gavel" style="color:var(--primary-hover);"></i><span class="card-title">Tipo de acción y vigencia</span></div>
            <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--sp-3);">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Tipo de acción *</label>
                    <select name="tipo_accion" class="form-control" required>
                        <option value="">— Seleccione —</option>
                        <?php foreach ($tipos as $t): ?><option value="<?= $e($t) ?>"><?= $e($t) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;"><label class="form-label">Rige desde *</label><input type="date" name="rige_desde" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Rige hasta (opcional)</label><input type="date" name="rige_hasta" class="form-control"></div>
                <div class="form-group" style="margin:0;grid-column:1/-1;"><label class="form-label">Especificación (si aplica "OTRO")</label><input type="text" name="explicacion_otro" class="form-control"></div>
            </div>
        </div>

        <!-- Situación actual vs propuesta -->
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:var(--sp-4);margin-bottom:var(--sp-4);">
            <div class="card">
                <div class="card-header" style="background:var(--accent-app);"><i class="fa-solid fa-location-crosshairs" style="color:var(--text-muted);"></i><span class="card-title">Situación actual</span></div>
                <div class="card-body">
                    <div class="form-group" style="margin:0 0 var(--sp-2);"><label class="form-label">Unidad</label><input class="form-control" value="<?= $e($emp['direccion_area'] ?: '—') ?>" disabled></div>
                    <div class="form-group" style="margin:0 0 var(--sp-2);"><label class="form-label">Puesto</label><input class="form-control" value="<?= $e($emp['cargo'] ?: '—') ?>" disabled></div>
                    <div class="form-group" style="margin:0;"><label class="form-label">Remuneración</label><input class="form-control" value="$ <?= number_format((float)($emp['sueldo_rmu'] ?? 0), 2) ?>" disabled></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-arrow-trend-up" style="color:var(--primary-hover);"></i><span class="card-title">Situación propuesta</span></div>
                <div class="card-body">
                    <div class="form-group" style="margin:0 0 var(--sp-2);">
                        <label class="form-label">Unidad propuesta</label>
                        <select name="propuesta_unidad_id" class="form-control">
                            <option value="">— Igual —</option>
                            <?php foreach ($areas as $a): ?><option value="<?= (int)$a['unidad_id'] ?>" <?= $sel($a['unidad_id'], $emp['unidad_id'] ?? '') ?>><?= $e($a['nombre_unidad']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0 0 var(--sp-2);">
                        <label class="form-label">Puesto propuesto</label>
                        <select name="propuesta_puesto_id" class="form-control">
                            <option value="">— Igual —</option>
                            <?php foreach ($cargos as $c): ?><option value="<?= (int)$c['puesto_id'] ?>" <?= $sel($c['puesto_id'], $emp['puesto_id'] ?? '') ?>><?= $e($c['nombre_puesto']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;"><label class="form-label">Remuneración propuesta</label><input type="number" step="0.01" min="0" name="propuesta_remuneracion" class="form-control" value="<?= $e($emp['sueldo_rmu'] ?? 0) ?>"></div>
                </div>
            </div>
        </div>

        <!-- Motivación -->
        <div class="card" style="margin-bottom:var(--sp-4);">
            <div class="card-header"><i class="fa-solid fa-scroll" style="color:var(--primary-hover);"></i><span class="card-title">Motivación / fundamento legal *</span></div>
            <div class="card-body">
                <textarea name="motivacion_texto" class="form-control" rows="4" required placeholder="Fundamento legal de la acción..."></textarea>
            </div>
        </div>

        <div style="display:flex;gap:var(--sp-2);justify-content:flex-end;">
            <a href="<?= APP_URL ?>/th/directorio" class="btn btn-ghost" data-spa>Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Registrar Acción</button>
        </div>
    </form>
<?php else: ?>
    <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> Busque un funcionario por cédula para generar una Acción de Personal.</div>
<?php endif; ?>

    <!-- Acciones registradas -->
    <div class="card" style="margin-top:var(--sp-5);">
        <div class="card-header" style="display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-list-check" style="color:var(--primary-hover);"></i><span class="card-title">Acciones registradas</span>
            <span class="badge badge-info"><?= count($acciones) ?></span>
            <div style="margin-left:auto;display:flex;gap:var(--sp-2);">
                <a href="<?= APP_URL ?>/th/accion-personal/export/excel" class="btn btn-ghost btn-sm" title="Exportar a Excel"><i class="fa-solid fa-file-excel" style="color:#1D6F42;"></i> Excel</a>
                <a href="<?= APP_URL ?>/th/accion-personal/export/pdf" class="btn btn-ghost btn-sm" target="_blank" rel="noopener" title="Exportar a PDF"><i class="fa-solid fa-file-pdf" style="color:#c0392b;"></i> PDF</a>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead><tr><th>N°</th><th>Fecha</th><th>Funcionario</th><th>Tipo</th><th>Estado</th><th style="text-align:right;">PDF</th></tr></thead>
                <tbody>
                <?php if (empty($acciones)): ?>
                    <tr><td colspan="6" style="text-align:center;padding:var(--sp-8) 0;color:var(--text-muted);">Sin acciones registradas.</td></tr>
                <?php else: foreach ($acciones as $a): ?>
                    <tr>
                        <td style="font-family:var(--font-code);font-size:.78rem;"><?= $e($a['numero_accion']) ?></td>
                        <td style="font-size:.8rem;white-space:nowrap;"><?= !empty($a['fecha_elaboracion']) ? date('d/m/Y', strtotime($a['fecha_elaboracion'])) : '—' ?></td>
                        <td style="font-size:.85rem;font-weight:600;"><?= $e($a['funcionario']) ?></td>
                        <td style="font-size:.83rem;"><?= $e($a['tipo_accion']) ?></td>
                        <td><span class="badge badge-success"><?= $e($a['estado_documento'] ?: 'Aprobado') ?></span></td>
                        <td style="text-align:right;"><a href="<?= APP_URL ?>/th/accion-personal/imprimir?id=<?= (int)$a['accion_id'] ?>" class="btn btn-ghost btn-sm" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i></a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
