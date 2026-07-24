<?php
/** Expediente digital del funcionario (solo lectura) — fragmento SPA. */
$e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$m = $empleado ?? null;
?>
<div style="animation:pageFadeIn .35s ease-out;max-width:920px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:var(--sp-5);">
        <a href="<?= APP_URL ?>/th/directorio" class="btn btn-ghost btn-sm" data-spa><i class="fa-solid fa-arrow-left"></i></a>
        <h2 style="font-size:1.3rem;font-weight:800;color:var(--text-app);margin:0;"><i class="fa-solid fa-id-badge" style="color:var(--primary-hover);margin-right:6px;"></i> Expediente del Funcionario</h2>
    </div>

<?php if (!$m): ?>
    <div class="card"><div class="card-body" style="text-align:center;padding:var(--sp-12) 0;color:var(--text-muted);">
        <i class="fa-regular fa-circle-question" style="font-size:2.5rem;display:block;margin-bottom:var(--sp-3);opacity:.3;"></i>
        <strong style="display:block;color:var(--text-app);">No se encontró el funcionario</strong>
        El expediente solicitado no existe o fue dado de baja.
    </div></div>
<?php else:
    $full = trim(($m['apellidos'] ?? '') . ' ' . ($m['nombres'] ?? ''));
    $words = array_values(array_filter(explode(' ', trim(($m['nombres'] ?? '') . ' ' . ($m['apellidos'] ?? '')))));
    $ini = mb_strtoupper((mb_substr($words[0] ?? '', 0, 1)) . (mb_substr($words[1] ?? '', 0, 1)));
    $activo = (int)($m['estado'] ?? 0) === 1;
    $id = (int)($m['id'] ?? $m['empleado_id'] ?? 0);
    $row = function($ico, $lbl, $v) use ($e) {
        echo '<div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid var(--border-app);">'
           . '<i class="fa-solid ' . $ico . '" style="width:18px;color:var(--text-muted);margin-top:2px;"></i>'
           . '<div><div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;">' . $e($lbl) . '</div>'
           . '<div style="font-size:.9rem;color:var(--text-app);font-weight:500;">' . ($v !== '' ? $e($v) : '—') . '</div></div></div>';
    };
?>
    <div class="card" style="margin-bottom:var(--sp-4);">
        <div class="card-body" style="display:flex;align-items:center;gap:18px;flex-wrap:wrap;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,var(--primary-app),var(--primary-hover));color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;"><?= $e($ini) ?></div>
                <div>
                    <div style="font-size:1.15rem;font-weight:800;color:var(--text-app);"><?= $e($full) ?></div>
                    <div style="font-size:.85rem;color:var(--text-muted);"><?= $e($m['cargo'] ?: 'Sin cargo') ?> · <?= $e($m['direccion_area'] ?: 'Sin área') ?></div>
                    <span class="badge <?= $activo ? 'badge-success' : 'badge-danger' ?>" style="margin-top:6px;"><i class="fa-solid fa-circle" style="font-size:5px;"></i> <?= $activo ? 'Activo' : 'Inactivo' ?></span>
                </div>
            </div>
            <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;">
                <a href="<?= APP_URL ?>/th/empleado/<?= $id ?>/editar" class="btn btn-ghost btn-sm" data-spa><i class="fa-solid fa-pencil"></i> Editar</a>
                <a href="<?= APP_URL ?>/th/accion-personal?id=<?= $id ?>" class="btn btn-ghost btn-sm" data-spa><i class="fa-solid fa-file-signature"></i> Acción</a>
                <a href="<?= APP_URL ?>/th/empleado/ficha?id=<?= $id ?>" class="btn btn-primary btn-sm" target="_blank" rel="noopener"><i class="fa-solid fa-file-pdf"></i> Ficha PDF</a>
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:var(--sp-4);">
        <div class="card"><div class="card-header"><i class="fa-solid fa-user" style="color:var(--primary-hover);"></i><span class="card-title">Datos personales</span></div>
            <div class="card-body" style="padding-top:0;">
                <?php $row('fa-id-card','Identificación',$m['cedula'] ?? '');
                      $row('fa-cake-candles','Nacimiento',!empty($m['fecha_nacimiento']) ? date('d/m/Y', strtotime($m['fecha_nacimiento'])) : '');
                      $row('fa-venus-mars','Sexo',$m['sexo'] ?? '');
                      $row('fa-ring','Estado civil',$m['estado_civil'] ?? '');
                      $row('fa-flag','Nacionalidad',$m['nacionalidad'] ?? '');
                      $row('fa-children','Cargas familiares',(string)($m['cargas_familiares'] ?? '0')); ?>
            </div>
        </div>
        <div class="card"><div class="card-header"><i class="fa-solid fa-briefcase" style="color:var(--primary-hover);"></i><span class="card-title">Laboral</span></div>
            <div class="card-body" style="padding-top:0;">
                <?php $row('fa-sitemap','Dirección / Área',$m['direccion_area'] ?? '');
                      $row('fa-user-tie','Puesto',$m['cargo'] ?? '');
                      $row('fa-calendar-day','Ingreso',!empty($m['fecha_ingreso']) ? date('d/m/Y', strtotime($m['fecha_ingreso'])) : '');
                      $row('fa-money-bill','Sueldo (RMU)',isset($m['sueldo_rmu']) ? '$ ' . number_format((float)$m['sueldo_rmu'], 2) : '');
                      $row('fa-hashtag','Código IESS',$m['codigo_iess'] ?? ''); ?>
            </div>
        </div>
        <div class="card"><div class="card-header"><i class="fa-solid fa-address-card" style="color:var(--primary-hover);"></i><span class="card-title">Contacto</span></div>
            <div class="card-body" style="padding-top:0;">
                <?php $row('fa-envelope','Correo institucional',$m['correo_institucional'] ?? '');
                      $row('fa-envelope-open','Correo personal',$m['correo_personal'] ?? '');
                      $row('fa-mobile','Teléfono',$m['telefono_movil'] ?? '');
                      $row('fa-city','Ciudad',$m['ciudad_residencia'] ?? '');
                      $row('fa-location-dot','Dirección',$m['direccion_domiciliaria'] ?? ''); ?>
            </div>
        </div>
        <div class="card"><div class="card-header"><i class="fa-solid fa-building-columns" style="color:var(--primary-hover);"></i><span class="card-title">Bancario</span></div>
            <div class="card-body" style="padding-top:0;">
                <?php $row('fa-landmark','Institución',$m['institucion_bancaria'] ?? '');
                      $row('fa-wallet','Tipo de cuenta',$m['tipo_cuenta_bancaria'] ?? '');
                      $row('fa-hashtag','Número de cuenta',$m['numero_cuenta_bancaria'] ?? ''); ?>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>
