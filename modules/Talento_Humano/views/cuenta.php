<?php
/** Crear cuenta de acceso desde un empleado TH — fragmento SPA (solo admin). */
$e   = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$m   = $empleado ?? [];
$id  = (int)($m['id'] ?? $m['empleado_id'] ?? 0);
$ced = $m['identificacion'] ?? $m['cedula'] ?? '';
$full = trim(($m['apellidos'] ?? '') . ' ' . ($m['nombres'] ?? ''));
$sel = fn($a, $b) => ((string)$a === (string)$b) ? 'selected' : '';
$deptoAuto = $deptoAuto ?? 0; $rolAuto = $rolAuto ?? 0;
$departamentos = $departamentos ?? []; $roles = $roles ?? [];
?>
<div style="animation:pageFadeIn .35s ease-out;max-width:760px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:var(--sp-5);">
        <a href="<?= APP_URL ?>/th/directorio" class="btn btn-ghost btn-sm" data-spa><i class="fa-solid fa-arrow-left"></i></a>
        <div>
            <h2 style="font-size:1.3rem;font-weight:800;color:var(--text-app);margin:0;">
                <i class="fa-solid fa-user-shield" style="color:var(--primary-hover);margin-right:6px;"></i> Crear cuenta de acceso
            </h2>
            <p style="font-size:.78rem;color:var(--text-muted);margin:2px 0 0;">Para <strong><?= $e($full) ?></strong> · cédula <?= $e($ced) ?></p>
        </div>
    </div>

    <div class="alert alert-info" style="margin-bottom:var(--sp-4);">
        <i class="fa-solid fa-wand-magic-sparkles"></i>
        Departamento y rol se <strong>autosugieren</strong> según la unidad (<?= $e($m['direccion_area'] ?: $m['codigo_uorg'] ?? '—') ?>) y el puesto (<?= $e($m['cargo'] ?: '—') ?>). Puedes ajustarlos antes de crear.
    </div>

    <form method="POST" action="<?= APP_URL ?>/th/empleado/cuenta">
        <?= SecurityHelper::csrfField() ?>
        <input type="hidden" name="empleado_id" value="<?= $id ?>">
        <input type="hidden" name="identificacion" value="<?= $e($ced) ?>">

        <div class="card" style="margin-bottom:var(--sp-4);">
            <div class="card-header"><i class="fa-solid fa-id-card" style="color:var(--primary-hover);"></i><span class="card-title">Datos de la cuenta</span></div>
            <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:var(--sp-3);">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" name="nombre_completo" class="form-control" required value="<?= $e($full) ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Correo</label>
                    <input type="email" name="correo" class="form-control" required value="<?= $e($m['correo_institucional'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="nombre_usuario" class="form-control" required value="<?= $e($sugUsuario ?? '') ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Contraseña temporal</label>
                    <input type="text" name="password" class="form-control" required minlength="6" value="<?= $e($sugPassword ?? '') ?>">
                    <small style="color:var(--text-muted);font-size:.72rem;">El funcionario deberá cambiarla al primer ingreso.</small>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:var(--sp-4);">
            <div class="card-header"><i class="fa-solid fa-wand-magic-sparkles" style="color:var(--primary-hover);"></i><span class="card-title">Acceso (autosugerido)</span></div>
            <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:var(--sp-3);">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Departamento</label>
                    <select name="id_departamento" class="form-control">
                        <option value="">— Sin departamento —</option>
                        <?php foreach ($departamentos as $d): ?>
                        <option value="<?= (int)$d['id_departamento'] ?>" <?= $sel($d['id_departamento'], $deptoAuto) ?>><?= $e($d['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Rol (define qué módulos verá)</label>
                    <select name="id_rol" class="form-control" required>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= (int)$r['id_rol'] ?>" <?= $sel($r['id_rol'], $rolAuto) ?>>
                            <?= $e($r['nombre']) ?> (nivel <?= (int)$r['nivel_jerarquia'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color:var(--text-muted);font-size:.72rem;">El nivel de jerarquía se toma del rol.</small>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:var(--sp-2);justify-content:flex-end;">
            <a href="<?= APP_URL ?>/th/directorio" class="btn btn-ghost" data-spa>Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Crear cuenta</button>
        </div>
    </form>
</div>
