<?php
/**
 * Mi Perfil (Elite Pro Edition) — Central Portal APM
 * Centro Integral de Identidad Institucional, Talento Humano, Ciberseguridad 2FA, Sesiones y Auditoría Forense.
 */
$success = SessionHelper::getFlash('success');
$nombre  = $usuario['nombre_completo'] ?? ($empleadoTh ? ($empleadoTh['nombres'] . ' ' . $empleadoTh['apellidos']) : 'Usuario');
$words   = explode(' ', trim($nombre));
$init    = '';
foreach (array_slice($words, 0, 2) as $w) { $init .= mb_strtoupper(mb_substr($w, 0, 1)); }

$nivelMap = [0=>'Operativo', 1=>'Analista', 2=>'Jefatura', 3=>'Director', 4=>'Super Admin'];
$nivelClasses = [
    0 => 'admin-badge-operativo',
    1 => 'admin-badge-analista',
    2 => 'admin-badge-jefe',
    3 => 'admin-badge-director',
    4 => 'admin-badge-super'
];
$nivel = (int)($usuario['nivel_jerarquia'] ?? 0);

$fmtFecha = function ($v, $conHora = false) {
    if (!$v) return '—';
    if ($v instanceof DateTime) return $v->format($conHora ? 'd/m/Y H:i' : 'd/m/Y');
    return date($conHora ? 'd/m/Y H:i' : 'd/m/Y', strtotime((string)$v));
};
$mfaActivo = !empty($usuario['requiere_mfa']);

// Calcular antigüedad institucional real desde fecha_ingreso en TH o fecha_creacion en Portal
$fechaIngreso = $empleadoTh['fecha_ingreso'] ?? ($usuario['fecha_creacion'] ?? null);
$antiguedad = 'Miembro reciente';
if ($fechaIngreso) {
    $ts = ($fechaIngreso instanceof DateTime) ? $fechaIngreso->getTimestamp() : strtotime((string)$fechaIngreso);
    $dias = max(1, (int)((time() - $ts) / 86400));
    if ($dias >= 365) {
        $anios = floor($dias / 365);
        $meses = floor(($dias % 365) / 30);
        $antiguedad = "{$anios} " . ($anios == 1 ? 'año' : 'años') . ($meses > 0 ? " {$meses}m" : '');
    } elseif ($dias >= 30) {
        $meses = floor($dias / 30);
        $antiguedad = "{$meses} " . ($meses == 1 ? 'mes' : 'meses');
    } else {
        $antiguedad = "{$dias} días";
    }
}

$cargoOficial = $empleadoTh['nombre_puesto'] ?? ($usuario['cargo'] ?? 'Servidor Público');
$unidadOficial = $empleadoTh['nombre_unidad'] ?? ($usuario['departamento'] ?? 'Autoridad Portuaria de Manta');
$tieneTh = !empty($empleadoTh);
?>

<?php if ($success): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.success(<?= json_encode($success) ?>));</script>
<?php endif; ?>

<div class="dashboard-wrapper anim-up anim-d0">

    <!-- ══════════════════════════════════════════════════════════════
         ELITE HERO PROFILE BANNER
         ══════════════════════════════════════════════════════════════ -->
    <div class="profile-hero-banner">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:24px;">
            <div style="display:flex;align-items:center;gap:24px;flex:1;min-width:320px;">
                <div class="account-avatar-wrapper">
                    <?php if (!empty($fotoUrl)): ?>
                    <div class="account-avatar-huge" style="background-image:url('<?= htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8') ?>');"></div>
                    <?php else: ?>
                    <div class="account-avatar-huge"><?= htmlspecialchars($init ?: 'AP', ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <div class="account-online-dot" title="Sesión activa y canal cifrado SSL"></div>
                </div>

                <div class="account-name-group">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <h1 style="font-size:1.65rem;font-weight:900;letter-spacing:-0.03em;color:var(--text-app);margin:0;">
                            <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
                        </h1>
                        <span class="badge <?= $nivelClasses[$nivel] ?? 'admin-badge-operativo' ?>" style="font-size:0.75rem;font-weight:800;letter-spacing:0.02em;">
                            <i class="fa-solid fa-crown" style="font-size:8px;margin-right:4px;"></i>
                            <?= $nivelMap[$nivel] ?? 'Operativo' ?>
                        </span>
                        <?php if ($mfaActivo): ?>
                        <span class="badge badge-success" style="font-size:0.7rem;padding:3px 8px;display:inline-flex;align-items:center;gap:4px;">
                            <i class="fa-solid fa-shield-check" style="font-size:8px;"></i> 2FA Activo
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="account-cargo-text" style="font-size:0.95rem;font-weight:700;color:var(--primary-hover);margin-top:4px;display:flex;align-items:center;gap:6px;">
                        <i class="fa-solid fa-briefcase"></i> <?= htmlspecialchars($cargoOficial, ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <div class="account-meta-chips" style="margin-top:8px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                        <code style="font-family:var(--font-code);font-size:0.75rem;background:var(--accent-app);padding:4px 10px;border-radius:var(--radius-sm);border:1px solid var(--border-app);color:var(--text-app);font-weight:700;">
                            <i class="fa-regular fa-id-card" style="margin-right:4px;color:var(--primary-hover);"></i> <?= htmlspecialchars($usuario['cedula'] ?? ($empleadoTh['identificacion'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                        </code>

                        <span style="display:inline-flex;align-items:center;gap:5px;color:var(--text-muted);font-size:0.8rem;background:var(--accent-app);padding:4px 10px;border-radius:var(--radius-sm);border:1px solid var(--border-app);">
                            <i class="fa-solid fa-building" style="color:var(--primary-hover);"></i> <?= htmlspecialchars($unidadOficial, ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <?php if ($tieneTh): ?>
                        <span class="badge badge-info" style="font-size:0.7rem;padding:3px 9px;">
                            <i class="fa-solid fa-circle-check" style="font-size:8px;margin-right:3px;"></i> Sincronizado Talento Humano
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Action Toolbar -->
            <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;align-items:center;">
                <a href="<?= APP_URL ?>/perfil/export/pdf" target="_blank" rel="noopener" class="btn-dash" title="Visualizar Ficha Institucional Completa en PDF">
                    <i class="fa-solid fa-file-pdf" style="color:#EF4444;"></i> Ficha PDF
                </a>
                <a href="<?= APP_URL ?>/cambiar-contrasena" class="btn-dash" data-spa title="Cambiar clave de acceso">
                    <i class="fa-solid fa-key" style="color:var(--primary-hover);"></i> Cambiar Clave
                </a>
                <a href="<?= APP_URL ?>/perfil/seguridad" class="btn-dash btn-dash-primary" data-spa title="Configurar verificación 2FA y Llaves">
                    <i class="fa-solid fa-shield-halved"></i> 2FA / Seguridad
                </a>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         STATISTICS GRID (4 KPI MASTER CARDS)
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-stat-grid">
        <!-- 1. Jerarquía -->
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #0284C7 15%, transparent);color:#0284C7;">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div>
                <div class="admin-stat-num">Nivel <?= $nivel ?></div>
                <div class="admin-stat-label"><?= $nivelMap[$nivel] ?? 'Operativo' ?> &bull; Portal APM</div>
            </div>
        </div>

        <!-- 2. Roles & Módulos -->
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #8B5CF6 15%, transparent);color:#8B5CF6;">
                <i class="fa-solid fa-key"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= count($roles) ?> <?= count($roles) === 1 ? 'Rol' : 'Roles' ?></div>
                <div class="admin-stat-label">Privilegios Transaccionales</div>
            </div>
        </div>

        <!-- 3. Blindaje MFA -->
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, <?= $mfaActivo ? '#10B981' : '#F59E0B' ?> 15%, transparent);color:<?= $mfaActivo ? '#10B981' : '#F59E0B' ?>;">
                <i class="fa-solid <?= $mfaActivo ? 'fa-fingerprint' : 'fa-triangle-exclamation' ?>"></i>
            </div>
            <div>
                <div class="admin-stat-num" style="color:<?= $mfaActivo ? '#10B981' : '#F59E0B' ?>;">
                    <?= $mfaActivo ? 'Blindado' : 'Básico' ?>
                </div>
                <div class="admin-stat-label"><?= $mfaActivo ? '2FA TOTP RFC 6238' : '2FA Desactivado' ?></div>
            </div>
        </div>

        <!-- 4. Antigüedad -->
        <div class="admin-stat-card">
            <div class="admin-stat-icon" style="background:color-mix(in srgb, #10B981 15%, transparent);color:#10B981;">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div>
                <div class="admin-stat-num"><?= $antiguedad ?></div>
                <div class="admin-stat-label">Trayectoria Institucional</div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         INTERACTIVE TABS NAVIGATION
         ══════════════════════════════════════════════════════════════ -->
    <div class="profile-nav-tabs">
        <button type="button" class="profile-nav-tab active" onclick="switchProfileTab('tab-ficha', this)">
            <i class="fa-solid fa-id-card"></i> Ficha de Talento Humano
        </button>
        <button type="button" class="profile-nav-tab" onclick="switchProfileTab('tab-seguridad', this)">
            <i class="fa-solid fa-shield-halved"></i> Ciberseguridad & Sesión
        </button>
        <button type="button" class="profile-nav-tab" onclick="switchProfileTab('tab-roles', this)">
            <i class="fa-solid fa-user-gear"></i> Roles & Privilegios (<?= count($roles) ?>)
        </button>
        <button type="button" class="profile-nav-tab" onclick="switchProfileTab('tab-contacto', this)">
            <i class="fa-solid fa-user-pen"></i> Actualizar Contacto
        </button>
        <button type="button" class="profile-nav-tab" onclick="switchProfileTab('tab-actividad', this)">
            <i class="fa-solid fa-clock-rotate-left"></i> Historial de Actividad
        </button>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 1: FICHA INSTITUCIONAL COMPLETA (TALENTO HUMANO)
         ══════════════════════════════════════════════════════════════ -->
    <div id="tab-ficha" class="profile-tab-pane active">
        <div style="display:flex;flex-direction:column;gap:var(--sp-4);">

            <!-- Sección 1: Datos Personales e Identidad -->
            <div class="account-card">
                <div class="account-card-header">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="fa-solid fa-address-card" style="color:var(--primary-hover);"></i>
                        <span>1. Identidad y Datos Personales (Registro Civil & TH)</span>
                    </div>
                    <span class="badge badge-info" style="font-size:0.72rem;">Datos Oficiales</span>
                </div>
                <div class="account-card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;">
                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-regular fa-id-badge"></i> Cédula / Identificación</div>
                            <div class="profile-field-value font-mono"><?= htmlspecialchars($usuario['cedula'] ?? ($empleadoTh['identificacion'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-user"></i> Nombres y Apellidos</div>
                            <div class="profile-field-value"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-cake-candles"></i> Fecha de Nacimiento</div>
                            <div class="profile-field-value"><?= htmlspecialchars($fmtFecha($empleadoTh['fecha_nacimiento'] ?? null)) ?></div>
                        </div>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-venus-mars"></i> Sexo / Género</div>
                            <div class="profile-field-value">
                                <?php
                                $s = $empleadoTh['sexo'] ?? '';
                                echo $s === 'M' ? 'Masculino' : ($s === 'F' ? 'Femenino' : ($s ?: '—'));
                                ?>
                            </div>
                        </div>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-heart"></i> Estado Civil</div>
                            <div class="profile-field-value"><?= htmlspecialchars($empleadoTh['estado_civil'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-droplet" style="color:#EF4444;"></i> Tipo de Sangre</div>
                            <div class="profile-field-value"><?= htmlspecialchars($empleadoTh['tipo_sangre'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-earth-americas"></i> Nacionalidad</div>
                            <div class="profile-field-value"><?= htmlspecialchars($empleadoTh['nacionalidad'] ?? 'Ecuatoriana', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-file-contract"></i> Tipo de Documento</div>
                            <div class="profile-field-value"><?= htmlspecialchars($empleadoTh['tipo_identificacion'] ?? 'Cédula de Identidad', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección 2: Régimen Laboral e Institucional -->
            <div class="account-card">
                <div class="account-card-header">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="fa-solid fa-briefcase" style="color:var(--primary-hover);"></i>
                        <span>2. Información Institucional & Régimen Laboral</span>
                    </div>
                    <span class="badge badge-success" style="font-size:0.72rem;">Servidor Activo APM</span>
                </div>
                <div class="account-card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;">
                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-sitemap"></i> Dirección / Unidad Organizacional</div>
                            <div class="profile-field-value" style="font-weight:800;color:var(--primary-hover);"><?= htmlspecialchars($unidadOficial, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-award"></i> Puesto / Cargo Institucional</div>
                            <div class="profile-field-value" style="font-weight:800;"><?= htmlspecialchars($cargoOficial, ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-calendar-day"></i> Fecha de Ingreso a la Institución</div>
                            <div class="profile-field-value"><?= htmlspecialchars($fmtFecha($empleadoTh['fecha_ingreso'] ?? ($usuario['fecha_creacion'] ?? null))) ?></div>
                        </div>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-file-signature"></i> Régimen / Tipo de Contrato</div>
                            <div class="profile-field-value"><?= htmlspecialchars($empleadoTh['tipo_contrato'] ?? 'Nombramiento Permanente / LOSEP', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <?php if (!empty($empleadoTh['sueldo_rmu'])): ?>
                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-money-bill-wave" style="color:#10B981;"></i> Remuneración Mensual Unificada (RMU)</div>
                            <div class="profile-field-value" style="color:#10B981;font-weight:800;font-family:var(--font-code);">
                                $<?= number_format((float)$empleadoTh['sueldo_rmu'], 2) ?> USD
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($empleadoTh['codigo_iess'])): ?>
                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-notes-medical"></i> Afiliación Patronal IESS</div>
                            <div class="profile-field-value font-mono"><?= htmlspecialchars($empleadoTh['codigo_iess'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($empleadoTh['partida_individual'])): ?>
                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-money-check-dollar"></i> Partida Presupuestaria</div>
                            <div class="profile-field-value font-mono"><?= htmlspecialchars($empleadoTh['partida_individual'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-fingerprint"></i> ID de Servidor TH</div>
                            <div class="profile-field-value font-mono">EMP_ID #<?= (int)($usuario['id_empleado_th'] ?? ($empleadoTh['empleado_id'] ?? 0)) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección 3: Contacto, Ubicación y Emergencias -->
            <div class="account-card">
                <div class="account-card-header">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="fa-solid fa-map-location-dot" style="color:var(--primary-hover);"></i>
                        <span>3. Ubicación, Contacto & Emergencias</span>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="switchProfileTab('tab-contacto')">
                        <i class="fa-solid fa-pen-to-square"></i> Editar Contacto
                    </button>
                </div>
                <div class="account-card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;">
                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-envelope"></i> Correo Electrónico Institucional</div>
                            <div class="profile-field-value"><?= htmlspecialchars($usuario['correo'] ?? ($empleadoTh['correo_institucional'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-regular fa-envelope"></i> Correo Electrónico Personal</div>
                            <div class="profile-field-value"><?= htmlspecialchars($empleadoTh['correo_personal'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-mobile-screen"></i> Teléfono Móvil / Celular</div>
                            <div class="profile-field-value font-mono"><?= htmlspecialchars($empleadoTh['telefono_movil'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-phone"></i> Teléfono Convencional</div>
                            <div class="profile-field-value font-mono"><?= htmlspecialchars($empleadoTh['telefono_convencional'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="profile-field-block">
                            <div class="profile-field-label"><i class="fa-solid fa-city"></i> Ciudad de Residencia</div>
                            <div class="profile-field-value"><?= htmlspecialchars($empleadoTh['ciudad_residencia'] ?? 'Manta', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <div class="profile-field-block" style="grid-column:1/-1;">
                            <div class="profile-field-label"><i class="fa-solid fa-house"></i> Dirección Domiciliaria</div>
                            <div class="profile-field-value"><?= htmlspecialchars($empleadoTh['direccion_domiciliaria'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>

                        <?php if (!empty($empleadoTh['contacto_emergencia'])): ?>
                        <div class="profile-field-block" style="grid-column:1/-1;background:color-mix(in srgb, #EF4444 8%, var(--surface-app));border-color:color-mix(in srgb, #EF4444 25%, transparent);">
                            <div class="profile-field-label" style="color:#EF4444;"><i class="fa-solid fa-truck-medical"></i> Contacto de Emergencia</div>
                            <div class="profile-field-value" style="font-weight:700;">
                                <?= htmlspecialchars($empleadoTh['contacto_emergencia'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($empleadoTh['emergencia_relacion'])): ?>
                                    <span style="font-weight:normal;color:var(--text-muted);">(<?= htmlspecialchars($empleadoTh['emergencia_relacion'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                <?php endif; ?>
                                <?php if (!empty($empleadoTh['tel_emergencia'])): ?>
                                    &bull; <span class="font-mono" style="color:var(--primary-hover);"><?= htmlspecialchars($empleadoTh['tel_emergencia'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 2: CIBERSEGURIDAD, 2FA & SESIÓN
         ══════════════════════════════════════════════════════════════ -->
    <div id="tab-seguridad" class="profile-tab-pane">
        <div class="account-grid-2col">
            <!-- 2FA Box -->
            <div class="account-card">
                <div class="account-card-header">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="fa-solid fa-fingerprint" style="color:var(--primary-hover);"></i>
                        <span>Autenticación de Dos Factores (TOTP RFC 6238)</span>
                    </div>
                </div>

                <div class="account-card-body">
                    <div style="display:flex;align-items:flex-start;gap:16px;padding:18px;border-radius:var(--radius-md);background:color-mix(in srgb, <?= $mfaActivo ? '#10B981' : '#F59E0B' ?> 10%, var(--surface-app));border:1px solid color-mix(in srgb, <?= $mfaActivo ? '#10B981' : '#F59E0B' ?> 25%, transparent);">
                        <div style="width:44px;height:44px;border-radius:50%;background:<?= $mfaActivo ? '#10B981' : '#F59E0B' ?>;color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.25rem;">
                            <i class="fa-solid <?= $mfaActivo ? 'fa-shield-check' : 'fa-triangle-exclamation' ?>"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:800;font-size:0.95rem;color:var(--text-app);">
                                Estado de Protección: <?= $mfaActivo ? 'Ciberseguridad Blindada' : 'Protección Básica (Sin 2FA)' ?>
                            </div>
                            <p style="font-size:0.82rem;color:var(--text-muted);margin:5px 0 0;line-height:1.45;">
                                <?= $mfaActivo
                                    ? 'Tu token TOTP está verificado y activo. Se solicitará código dinámico temporal en cada inicio de sesión y al cambiar entre módulos sensibles.'
                                    : 'Añade una capa de ciberseguridad a tu cuenta vinculando Google Authenticator, Microsoft Authenticator o llave TOTP.' ?>
                            </p>
                        </div>
                    </div>

                    <div style="margin-top:var(--sp-4);display:flex;gap:12px;flex-wrap:wrap;">
                        <a href="<?= APP_URL ?>/perfil/seguridad" class="btn btn-primary" style="flex:1;justify-content:center;height:42px;" data-spa>
                            <i class="fa-solid fa-mobile-screen-button"></i> <?= $mfaActivo ? 'Gestionar Llave 2FA' : 'Activar 2FA Ahora' ?>
                        </a>
                        <a href="<?= APP_URL ?>/cambiar-contrasena" class="btn btn-outline" style="flex:1;justify-content:center;height:42px;" data-spa>
                            <i class="fa-solid fa-key"></i> Cambiar Contraseña
                        </a>
                    </div>
                </div>
            </div>

            <!-- Trazabilidad de Sesión -->
            <div class="account-card">
                <div class="account-card-header">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="fa-solid fa-network-wired" style="color:var(--primary-hover);"></i>
                        <span>Trazabilidad de la Sesión y Conexión</span>
                    </div>
                </div>

                <div class="account-card-body" style="font-size:0.85rem;display:flex;flex-direction:column;gap:14px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="color:var(--text-muted);"><i class="fa-solid fa-user-lock" style="margin-right:6px;"></i> Usuario de Acceso:</span>
                        <strong style="color:var(--text-app);font-family:var(--font-code);"><?= htmlspecialchars($usuario['nombre_usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border-app);padding-top:10px;">
                        <span style="color:var(--text-muted);"><i class="fa-regular fa-calendar-plus" style="margin-right:6px;"></i> Registro en Portal:</span>
                        <strong style="color:var(--text-app);font-family:var(--font-code);"><?= htmlspecialchars($fmtFecha($usuario['fecha_creacion'] ?? null, true)) ?></strong>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border-app);padding-top:10px;">
                        <span style="color:var(--text-muted);"><i class="fa-solid fa-clock-rotate-left" style="margin-right:6px;"></i> Último Acceso Exitoso:</span>
                        <strong style="color:var(--text-app);font-family:var(--font-code);"><?= htmlspecialchars($fmtFecha($ultimoAcceso, true) ?: 'Sesión actual activa') ?></strong>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--border-app);padding-top:10px;">
                        <span style="color:var(--text-muted);"><i class="fa-solid fa-shield-halved" style="margin-right:6px;"></i> Estado de Conexión:</span>
                        <span class="badge badge-success" style="font-size:0.72rem;"><i class="fa-solid fa-signal" style="font-size:8px;"></i> Conectado (SSL)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 3: ROLES & PRIVILEGIOS ASIGNADOS
         ══════════════════════════════════════════════════════════════ -->
    <div id="tab-roles" class="profile-tab-pane">
        <div class="account-card">
            <div class="account-card-header">
                <div style="display:flex;align-items:center;gap:10px;">
                    <i class="fa-solid fa-key" style="color:var(--primary-hover);"></i>
                    <span>Catálogo de Roles y Permisos Asignados a tu Cuenta</span>
                </div>
                <a href="<?= APP_URL ?>/admin/roles/matriz" class="btn btn-ghost btn-sm" data-spa>
                    <i class="fa-solid fa-table-cells"></i> Ver Matriz Global de Accesos
                </a>
            </div>

            <div class="account-card-body">
                <?php if (empty($roles)): ?>
                <div style="text-align:center;padding:var(--sp-8);color:var(--text-muted);">
                    <i class="fa-solid fa-shield-slash" style="font-size:2.5rem;margin-bottom:8px;display:block;opacity:0.4;"></i>
                    Sin roles personalizados asignados (Acceso estándar según nivel jerárquico)
                </div>
                <?php else: ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:var(--sp-4);">
                    <?php foreach ($roles as $r): ?>
                    <div style="padding:16px;border-radius:var(--radius-md);background:var(--accent-app);border:1px solid var(--border-app);display:flex;align-items:center;gap:14px;box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                        <div style="width:42px;height:42px;border-radius:var(--radius-md);background:linear-gradient(135deg, var(--primary-app), var(--primary-hover));color:#ffffff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:800;color:var(--text-app);font-size:0.92rem;">
                                <?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div style="margin-top:4px;">
                                <code style="font-size:0.72rem;background:color-mix(in srgb, var(--primary-hover) 10%, transparent);color:var(--primary-hover);padding:2px 7px;border-radius:var(--radius-sm);font-weight:700;">
                                    <?= htmlspecialchars($r['codigo'], ENT_QUOTES, 'UTF-8') ?>
                                </code>
                            </div>
                        </div>
                        <i class="fa-solid fa-circle-check" style="color:#10B981;font-size:1.2rem;" title="Rol Activo y Verificado"></i>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 4: ACTUALIZACIÓN DE CONTACTO (FORMULARIO)
         ══════════════════════════════════════════════════════════════ -->
    <div id="tab-contacto" class="profile-tab-pane">
        <div class="account-card">
            <div class="account-card-header">
                <div style="display:flex;align-items:center;gap:10px;">
                    <i class="fa-solid fa-user-pen" style="color:var(--primary-hover);"></i>
                    <span>Actualizar Canales de Contacto y Residencia</span>
                </div>
                <span class="badge badge-success" style="font-size:0.72rem;">Sincronización Automática</span>
            </div>

            <div class="account-card-body">
                <form method="POST" action="<?= APP_URL ?>/perfil">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    
                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:var(--sp-4);">
                        <div class="form-group">
                            <label class="form-label" style="font-size:0.78rem;font-weight:700;">
                                <i class="fa-solid fa-envelope" style="color:var(--primary-hover);margin-right:4px;"></i> Correo Electrónico Institucional
                            </label>
                            <input type="email" name="correo" class="form-control"
                                   value="<?= htmlspecialchars($usuario['correo'] ?? ($empleadoTh['correo_institucional'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="150" placeholder="usuario@apm.gob.ec" required style="height:42px;">
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-size:0.78rem;font-weight:700;">
                                <i class="fa-regular fa-envelope" style="color:var(--primary-hover);margin-right:4px;"></i> Correo Electrónico Personal
                            </label>
                            <input type="email" name="correo_personal" class="form-control"
                                   value="<?= htmlspecialchars($empleadoTh['correo_personal'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="150" placeholder="usuario@gmail.com" style="height:42px;">
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-size:0.78rem;font-weight:700;">
                                <i class="fa-solid fa-mobile-screen" style="color:var(--primary-hover);margin-right:4px;"></i> Teléfono Móvil / Celular
                            </label>
                            <input type="text" name="telefono_movil" class="form-control"
                                   value="<?= htmlspecialchars($empleadoTh['telefono_movil'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="20" placeholder="0991234567" style="height:42px;">
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-size:0.78rem;font-weight:700;">
                                <i class="fa-solid fa-phone" style="color:var(--primary-hover);margin-right:4px;"></i> Teléfono Convencional / Fijo
                            </label>
                            <input type="text" name="telefono_convencional" class="form-control"
                                   value="<?= htmlspecialchars($empleadoTh['telefono_convencional'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="20" placeholder="052620000" style="height:42px;">
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-size:0.78rem;font-weight:700;">
                                <i class="fa-solid fa-city" style="color:var(--primary-hover);margin-right:4px;"></i> Ciudad de Residencia
                            </label>
                            <input type="text" name="ciudad_residencia" class="form-control"
                                   value="<?= htmlspecialchars($empleadoTh['ciudad_residencia'] ?? 'Manta', ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="80" placeholder="Manta, Manabí" style="height:42px;">
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-size:0.78rem;font-weight:700;">
                                <i class="fa-solid fa-location-dot" style="color:var(--primary-hover);margin-right:4px;"></i> Dirección Domiciliaria
                            </label>
                            <input type="text" name="direccion_domiciliaria" class="form-control"
                                   value="<?= htmlspecialchars($empleadoTh['direccion_domiciliaria'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="200" placeholder="Calle, Av., Barrio / Ciudadela" style="height:42px;">
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-size:0.78rem;font-weight:700;">
                                <i class="fa-solid fa-user-plus" style="color:#EF4444;margin-right:4px;"></i> Nombre Contacto de Emergencia
                            </label>
                            <input type="text" name="contacto_emergencia" class="form-control"
                                   value="<?= htmlspecialchars($empleadoTh['contacto_emergencia'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="120" placeholder="Nombre completo" style="height:42px;">
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-size:0.78rem;font-weight:700;">
                                <i class="fa-solid fa-people-arrows" style="color:#EF4444;margin-right:4px;"></i> Parentesco / Relación
                            </label>
                            <input type="text" name="emergencia_relacion" class="form-control"
                                   value="<?= htmlspecialchars($empleadoTh['emergencia_relacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="50" placeholder="Cónyuge, Padre, Madre, Hermano/a" style="height:42px;">
                        </div>

                        <div class="form-group">
                            <label class="form-label" style="font-size:0.78rem;font-weight:700;">
                                <i class="fa-solid fa-phone-flip" style="color:#EF4444;margin-right:4px;"></i> Teléfono de Emergencia
                            </label>
                            <input type="text" name="tel_emergencia" class="form-control"
                                   value="<?= htmlspecialchars($empleadoTh['tel_emergencia'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="20" placeholder="0987654321" style="height:42px;">
                        </div>
                    </div>

                    <div style="margin-top:var(--sp-6);display:flex;justify-content:flex-end;gap:12px;">
                        <button type="submit" class="btn btn-primary" style="padding:0 28px;height:42px;font-weight:700;">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar y Sincronizar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════
         TAB 5: HISTORIAL DE ACTIVIDAD FORENSE
         ══════════════════════════════════════════════════════════════ -->
    <div id="tab-actividad" class="profile-tab-pane">
        <div class="dash-card">
            <div class="dash-card-header">
                <div>
                    <div class="dash-card-title">
                        <i class="fa-solid fa-list-timeline" style="color:var(--primary-hover);"></i>
                        Bitácora Forense de tus Acciones y Accesos
                    </div>
                    <div class="dash-card-subtitle">Registro cronológico inmutable de tus últimas transacciones y logins al portal</div>
                </div>
            </div>

            <div class="dash-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Módulo del Portal</th>
                            <th>Operación Realizada</th>
                            <th>Resultado Transaccional</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($actividadPropia)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;padding:var(--sp-8);color:var(--text-muted);">
                            Sin actividad auditada registrada para esta cuenta
                        </td>
                    </tr>
                    <?php else: foreach ($actividadPropia as $act):
                        $fa = $act['fecha_registro'];
                        if ($fa instanceof DateTime) { $fa = $fa->format('d/m/Y H:i:s'); }
                        elseif (is_string($fa)) { $fa = date('d/m/Y H:i:s', strtotime($fa)); }
                        else { $fa = '—'; }
                        $ok = ($act['resultado'] ?? '') === 'EXITO';
                    ?>
                    <tr>
                        <td style="font-family:var(--font-code);font-size:0.75rem;color:var(--text-muted);white-space:nowrap;">
                            <i class="fa-regular fa-clock" style="margin-right:4px;"></i> <?= htmlspecialchars($fa, ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <span class="dash-mod-badge"><?= htmlspecialchars($act['modulo'] ?? 'CORE', ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <strong style="color:var(--text-app);font-size:0.83rem;"><?= htmlspecialchars($act['operacion'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                        </td>
                        <td>
                            <span class="badge badge-<?= $ok ? 'success' : 'danger' ?>" style="font-size:0.72rem;">
                                <i class="fa-solid fa-<?= $ok ? 'check' : 'xmark' ?>" style="font-size:8px;"></i> <?= htmlspecialchars($act['resultado'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<style>
.profile-field-block {
    background: var(--accent-app);
    border: 1px solid var(--border-app);
    border-radius: var(--radius-md);
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.profile-field-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    display: flex;
    align-items: center;
    gap: 6px;
}
.profile-field-value {
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-app);
}
</style>

<script>
function switchProfileTab(tabId, btn) {
    document.querySelectorAll('.profile-nav-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.profile-tab-pane').forEach(p => p.classList.remove('active'));
    if (btn) btn.classList.add('active');
    else {
        const matchingBtn = document.querySelector(`.profile-nav-tab[onclick*="${tabId}"]`);
        if (matchingBtn) matchingBtn.classList.add('active');
    }
    const target = document.getElementById(tabId);
    if (target) target.classList.add('active');
}
</script>
