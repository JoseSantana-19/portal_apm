<?php
/** Formulario de funcionario (alta/edición) — fragmento SPA. */
$e  = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$ed = !empty($modoEdicion);
$m  = $empleado ?? [];
$val = fn($k, $d = '') => $e($m[$k] ?? $d);
$areas  = $areas  ?? [];
$cargos = $cargos ?? [];
$id = (int)($m['empleado_id'] ?? 0);
$sel = fn($a, $b) => ((string)$a === (string)$b) ? 'selected' : '';
?>
<div style="animation:pageFadeIn .35s ease-out;max-width:960px;">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:var(--sp-5);">
        <a href="<?= APP_URL ?>/th/directorio" class="btn btn-ghost btn-sm" data-spa><i class="fa-solid fa-arrow-left"></i></a>
        <h2 style="font-size:1.3rem;font-weight:800;color:var(--text-app);margin:0;">
            <i class="fa-solid fa-user-<?= $ed ? 'pen' : 'plus' ?>" style="color:var(--primary-hover);margin-right:6px;"></i>
            <?= $ed ? 'Editar Funcionario' : 'Nuevo Funcionario' ?>
        </h2>
    </div>

    <form method="POST" action="<?= APP_URL ?>/th/empleado/guardar" autocomplete="off">
        <?= SecurityHelper::csrfField() ?>
        <?php if ($ed): ?><input type="hidden" name="empleado_id" value="<?= $id ?>"><?php endif; ?>

        <!-- Identificación -->
        <div class="card" style="margin-bottom:var(--sp-4);">
            <div class="card-header"><i class="fa-solid fa-id-card" style="color:var(--primary-hover);"></i><span class="card-title">Identificación</span></div>
            <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--sp-3);">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Tipo</label>
                    <select name="tipo_identificacion" class="form-control">
                        <?php foreach (['CEDULA','PASAPORTE','RUC'] as $t): ?>
                        <option value="<?= $t ?>" <?= $sel($t, $m['tipo_identificacion'] ?? 'CEDULA') ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Cédula / Identificación *</label>
                    <input type="text" name="identificacion" class="form-control" required value="<?= $val('identificacion') ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-control">
                        <option value="1" <?= $sel(1, $m['estado'] ?? 1) ?>>Activo</option>
                        <option value="0" <?= $sel(0, $m['estado'] ?? 1) ?>>Inactivo</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Datos personales -->
        <div class="card" style="margin-bottom:var(--sp-4);">
            <div class="card-header"><i class="fa-solid fa-user" style="color:var(--primary-hover);"></i><span class="card-title">Datos personales</span></div>
            <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--sp-3);">
                <div class="form-group" style="margin:0;"><label class="form-label">Apellidos *</label><input type="text" name="apellidos" class="form-control" required value="<?= $val('apellidos') ?>"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Nombres *</label><input type="text" name="nombres" class="form-control" required value="<?= $val('nombres') ?>"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Fecha de nacimiento</label><input type="date" name="fecha_nacimiento" class="form-control" value="<?= $val('fecha_nacimiento') ?>"></div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Sexo</label>
                    <select name="sexo" class="form-control">
                        <?php /* La BD th_empleados sólo admite M/F (CHECK). */ ?>
                        <?php foreach (['M'=>'Masculino','F'=>'Femenino'] as $k=>$lbl): ?>
                        <option value="<?= $k ?>" <?= $sel($k, $m['sexo'] ?? 'M') ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Estado civil</label>
                    <select name="estado_civil" class="form-control">
                        <option value="">—</option>
                        <?php foreach (['Soltero','Casado','Divorciado','Viudo','Union Libre'] as $ec): ?>
                        <option value="<?= $ec ?>" <?= $sel($ec, $m['estado_civil'] ?? '') ?>><?= $ec ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;"><label class="form-label">Nacionalidad</label><input type="text" name="nacionalidad" class="form-control" value="<?= $val('nacionalidad', 'Ecuatoriana') ?>"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Cargas familiares</label><input type="number" min="0" name="cargas_familiares" class="form-control" value="<?= $val('cargas_familiares', '0') ?>"></div>
            </div>
        </div>

        <!-- Ubicación institucional -->
        <div class="card" style="margin-bottom:var(--sp-4);">
            <div class="card-header"><i class="fa-solid fa-sitemap" style="color:var(--primary-hover);"></i><span class="card-title">Ubicación institucional</span></div>
            <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--sp-3);">
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Dirección / Unidad</label>
                    <select name="unidad_id" class="form-control">
                        <option value="">— Seleccione —</option>
                        <?php foreach ($areas as $a): ?>
                        <option value="<?= (int)$a['unidad_id'] ?>" <?= $sel($a['unidad_id'], $m['unidad_id'] ?? '') ?>><?= $e($a['nombre_unidad']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Puesto / Cargo</label>
                    <select name="puesto_id" class="form-control">
                        <option value="">— Seleccione —</option>
                        <?php foreach ($cargos as $c): ?>
                        <option value="<?= (int)$c['puesto_id'] ?>" <?= $sel($c['puesto_id'], $m['puesto_id'] ?? '') ?>><?= $e($c['nombre_puesto']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;"><label class="form-label">Fecha de ingreso</label><input type="date" name="fecha_ingreso" class="form-control" value="<?= $val('fecha_ingreso') ?>"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Sueldo (RMU)</label><input type="number" step="0.01" min="0" name="sueldo_rmu" class="form-control" value="<?= $val('sueldo_rmu', '0') ?>"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Código IESS</label><input type="text" name="codigo_iess" class="form-control" value="<?= $val('codigo_iess') ?>"></div>
            </div>
        </div>

        <!-- Contacto -->
        <div class="card" style="margin-bottom:var(--sp-4);">
            <div class="card-header"><i class="fa-solid fa-address-card" style="color:var(--primary-hover);"></i><span class="card-title">Contacto</span></div>
            <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--sp-3);">
                <div class="form-group" style="margin:0;"><label class="form-label">Correo institucional</label><input type="email" name="correo_institucional" class="form-control" value="<?= $val('correo_institucional') ?>"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Correo personal</label><input type="email" name="correo_personal" class="form-control" value="<?= $val('correo_personal') ?>"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Teléfono móvil</label><input type="text" name="telefono_movil" class="form-control" value="<?= $val('telefono_movil') ?>"></div>
                <div class="form-group" style="margin:0;"><label class="form-label">Ciudad de residencia</label><input type="text" name="ciudad_residencia" class="form-control" value="<?= $val('ciudad_residencia', 'Manta') ?>"></div>
                <div class="form-group" style="margin:0;grid-column:1/-1;"><label class="form-label">Dirección domiciliaria</label><input type="text" name="direccion_domiciliaria" class="form-control" value="<?= $val('direccion_domiciliaria') ?>"></div>
            </div>
        </div>

        <!-- Bancario -->
        <div class="card" style="margin-bottom:var(--sp-4);">
            <div class="card-header"><i class="fa-solid fa-building-columns" style="color:var(--primary-hover);"></i><span class="card-title">Información bancaria</span></div>
            <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:var(--sp-3);">
                <div class="form-group" style="margin:0;"><label class="form-label">Institución bancaria</label><input type="text" name="institucion_bancaria" class="form-control" value="<?= $val('institucion_bancaria') ?>"></div>
                <div class="form-group" style="margin:0;">
                    <label class="form-label">Tipo de cuenta</label>
                    <select name="tipo_cuenta_bancaria" class="form-control">
                        <option value="">—</option>
                        <?php foreach (['Ahorros','Corriente'] as $tc): ?>
                        <option value="<?= $tc ?>" <?= $sel($tc, $m['tipo_cuenta_bancaria'] ?? '') ?>><?= $tc ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;"><label class="form-label">Número de cuenta</label><input type="text" name="numero_cuenta_bancaria" class="form-control" value="<?= $val('numero_cuenta_bancaria') ?>"></div>
            </div>
        </div>

        <div style="display:flex;gap:var(--sp-2);justify-content:flex-end;">
            <a href="<?= APP_URL ?>/th/directorio" class="btn btn-ghost" data-spa>Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $ed ? 'Actualizar' : 'Guardar' ?></button>
        </div>
    </form>
</div>
