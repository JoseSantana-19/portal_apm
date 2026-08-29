<?php
$success = SessionHelper::getFlash('success');
$nombre  = $usuario['nombre_completo'] ?? '';
$words   = explode(' ', trim($nombre));
$init    = '';
foreach (array_slice($words, 0, 2) as $w) { $init .= mb_strtoupper(mb_substr($w, 0, 1)); }

$nivelMap = [0=>'Operativo',1=>'Analista',2=>'Jefatura',3=>'Director',4=>'Super Admin'];
$nivelColorMap = [0=>'#6c757d',1=>'#17a2b8',2=>'#0056b3',3=>'#ffc107',4=>'#dc3545'];
$nivel = (int)($usuario['nivel_jerarquia'] ?? 0);

$fmtFecha = function ($v) {
    if (!$v) return null;
    if ($v instanceof DateTime) return $v->format('d/m/Y H:i');
    return date('d/m/Y H:i', strtotime((string)$v));
};
$mfaActivo = !empty($usuario['requiere_mfa']);
?>

<?php if ($success): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.success(<?= json_encode($success) ?>));</script>
<?php endif; ?>

<div class="uform">

<div class="gx uform-hero">
    <?php if (!empty($fotoUrl)): ?>
    <div class="uform-avatar uform-avatar-photo" style="background-image:url('<?= htmlspecialchars($fotoUrl, ENT_QUOTES, 'UTF-8') ?>');"></div>
    <?php else: ?>
    <div class="uform-avatar"><?= htmlspecialchars($init ?: '?', ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <div class="uform-hero-body">
        <div class="uform-hero-name"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></div>
        <?php if (!empty($usuario['cargo'])): ?>
        <div class="uform-hero-cargo"><?= htmlspecialchars($usuario['cargo'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="uform-hero-meta">
            <code><i class="fa-regular fa-id-card" style="margin-right:4px;opacity:0.7;"></i><?= htmlspecialchars($usuario['cedula'] ?? '', ENT_QUOTES, 'UTF-8') ?></code>
            <span class="sep"></span>
            <span class="badge" style="background:color-mix(in srgb, <?= $nivelColorMap[$nivel] ?> 15%, transparent);color:<?= $nivelColorMap[$nivel] ?>;border-color:color-mix(in srgb, <?= $nivelColorMap[$nivel] ?> 30%, transparent);">
                <i class="fa-solid fa-crown" style="font-size:8px;"></i>
                <?= $nivelMap[$nivel] ?? 'Operativo' ?>
            </span>
            <?php if (!empty($usuario['departamento'])): ?>
            <span class="sep"></span>
            <span><i class="fa-solid fa-building" style="opacity:.6;margin-right:4px;"></i><?= htmlspecialchars($usuario['departamento'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="uform-hero-stats">
        <div class="uform-stat">
            <b><i class="fa-solid fa-key" style="color:var(--color-primary);font-size:0.9em;margin-right:4px;"></i><?= count($roles) ?></b>
            <span>Rol<?= count($roles) === 1 ? '' : 'es' ?></span>
        </div>
        <div class="uform-stat">
            <b style="color:<?= $mfaActivo ? 'var(--color-success)' : 'var(--color-warning)' ?>;">
                <i class="fa-solid <?= $mfaActivo ? 'fa-shield-check' : 'fa-shield' ?>"></i> <?= $mfaActivo ? 'ACTIVO' : 'INACTIVO' ?>
            </b>
            <span>Seguridad 2FA</span>
        </div>
    </div>
</div>

<div class="uform-grid">
    <div>
        <!-- Información de la Cuenta -->
        <div class="gx uform-card">
            <div class="uform-card-head">
                <i class="fa-solid fa-id-card"></i> Información de la Cuenta
            </div>
            <div class="uform-card-body">
                <form method="POST" action="<?= APP_URL ?>/perfil">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="uform-2col">
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-lock" style="font-size:10px;opacity:0.6;margin-right:4px;"></i> Cédula de Identidad</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($usuario['cedula'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly style="background:var(--g-bg-soft);cursor:not-allowed;font-family:var(--font-mono);font-weight:600;">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><i class="fa-solid fa-lock" style="font-size:10px;opacity:0.6;margin-right:4px;"></i> Nombre Completo</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>" readonly style="background:var(--g-bg-soft);cursor:not-allowed;font-weight:600;">
                        </div>
                        <div class="form-group" style="grid-column:1/-1;">
                            <label class="form-label"><i class="fa-solid fa-envelope" style="color:var(--color-primary);margin-right:4px;"></i> Correo Electrónico Institucional</label>
                            <input type="email" name="correo" class="form-control"
                                   value="<?= htmlspecialchars($usuario['correo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="150" placeholder="tu.correo@apm.gob.ec" style="height:42px;">
                            <span class="form-help" style="font-size:0.75rem;color:var(--color-text-muted);display:flex;align-items:center;gap:5px;margin-top:6px;">
                                <i class="fa-solid fa-circle-info" style="color:var(--color-primary);"></i> Único campo editable directamente — nombre y cédula sincronizan desde Talento Humano.
                            </span>
                        </div>
                    </div>
                    <div style="margin-top:var(--sp-4);display:flex;justify-content:flex-end;">
                        <button type="submit" class="btn btn-primary" style="height:38px;padding:0 20px;">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Roles Asignados -->
        <?php if (!empty($roles)): ?>
        <div class="gx uform-card">
            <div class="uform-card-head">
                <i class="fa-solid fa-key"></i> Roles Asignados en el Sistema
                <span class="uform-badge" style="margin-left:auto;"><?= count($roles) ?> rol<?= count($roles) === 1 ? '' : 'es' ?></span>
            </div>
            <div class="uform-card-body">
                <div class="uform-role-grid">
                    <?php foreach ($roles as $r): ?>
                    <div class="uform-role-chip checked">
                        <div style="width:28px;height:28px;border-radius:var(--radius-sm);background:color-mix(in srgb,var(--color-primary) 14%,transparent);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa-solid fa-shield-halved" style="font-size:0.75rem;"></i>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div class="uform-role-name" title="<?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($r['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="uform-role-code"><code><?= htmlspecialchars($r['codigo'], ENT_QUOTES, 'UTF-8') ?></code></div>
                        </div>
                        <i class="fa-solid fa-circle-check uform-role-tick" style="color:var(--color-success);font-size:12px;"></i>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div>
        <!-- Seguridad -->
        <div class="gx uform-card">
            <div class="uform-card-head"><i class="fa-solid fa-shield-halved"></i> Seguridad & Accesos</div>
            <div class="uform-card-body">
                <div class="profile-security-status <?= $mfaActivo ? 'on' : 'off' ?>">
                    <div class="profile-security-icon"><i class="fa-solid <?= $mfaActivo ? 'fa-shield-check' : 'fa-triangle-exclamation' ?>"></i></div>
                    <div>
                        <div style="font-weight:700;font-size:.88rem;color:var(--color-text);">Verificación en dos pasos <?= $mfaActivo ? 'Activa' : 'Desactivada' ?></div>
                        <div style="font-size:.76rem;color:var(--color-text-muted);margin-top:3px;line-height:1.4;">
                            <?= $mfaActivo
                                ? 'Tu cuenta está protegida con código 2FA para inicio de sesión y módulos sensibles.'
                                : 'Recomendado: añade una capa de protección adicional mediante código temporal.' ?>
                        </div>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:var(--sp-2);margin-top:var(--sp-4);">
                    <a href="<?= APP_URL ?>/perfil/seguridad" class="btn btn-outline" style="width:100%;justify-content:center;height:38px;" data-spa>
                        <i class="fa-solid fa-mobile-screen-button"></i> <?= $mfaActivo ? 'Administrar 2FA / Dispositivo' : 'Configurar 2FA' ?>
                    </a>
                    <a href="<?= APP_URL ?>/cambiar-contrasena" class="btn btn-outline" style="width:100%;justify-content:center;height:38px;" data-spa>
                        <i class="fa-solid fa-key"></i> Cambiar Contraseña
                    </a>
                </div>
            </div>
        </div>

        <!-- Registro de Actividad -->
        <div class="gx uform-card">
            <div class="uform-card-head"><i class="fa-solid fa-clock-rotate-left"></i> Registro de Actividad</div>
            <div class="uform-card-body" style="font-size:.82rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--g-bd);">
                    <span style="color:var(--color-text-muted);display:flex;align-items:center;gap:6px;">
                        <i class="fa-regular fa-calendar-plus" style="font-size:11px;"></i> Creación de cuenta
                    </span>
                    <span style="font-weight:600;font-size:var(--font-size-xs);"><?= htmlspecialchars($fmtFecha($usuario['fecha_creacion'] ?? null) ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;">
                    <span style="color:var(--color-text-muted);display:flex;align-items:center;gap:6px;">
                        <i class="fa-solid fa-right-to-bracket" style="font-size:11px;"></i> Última sesión anterior
                    </span>
                    <span style="font-weight:600;font-size:var(--font-size-xs);"><?= htmlspecialchars($fmtFecha($ultimoAcceso) ?? 'Primera sesión', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        </div>

        <!-- Mis últimas acciones -->
        <div class="gx uform-card">
            <div class="uform-card-head"><i class="fa-solid fa-list-check"></i> Mis Últimas Acciones</div>
            <div class="uform-card-body" style="padding-top:var(--sp-2);padding-bottom:var(--sp-2);">
                <?php if (empty($actividadPropia)): ?>
                <p style="font-size:.8rem;color:var(--color-text-muted);padding:8px 0;margin:0;">Sin actividad registrada todavía.</p>
                <?php else: foreach ($actividadPropia as $act):
                    $fa = $act['fecha_registro'];
                    if ($fa instanceof DateTime) { $fa = $fa->format('d/m/Y H:i'); }
                    elseif (is_string($fa)) { $fa = date('d/m/Y H:i', strtotime($fa)); }
                    else { $fa = '—'; }
                    $ok = ($act['resultado'] ?? '') === 'EXITO';
                ?>
                <div class="uform-mini-activity">
                    <i class="fa-solid <?= $ok ? 'fa-circle-check' : 'fa-circle-xmark' ?>" style="color:var(--color-<?= $ok ? 'success' : 'danger' ?>);font-size:11px;"></i>
                    <div style="flex:1;min-width:0;">
                        <span style="font-weight:600;"><?= htmlspecialchars($act['operacion'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        <span style="color:var(--color-text-muted);"> en <?= htmlspecialchars($act['modulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <span style="color:var(--color-text-muted);font-size:.72rem;white-space:nowrap;"><?= htmlspecialchars($fa, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

</div>

<style>
.uform {
    --g-bg: var(--surface-app, var(--color-surface));
    --g-bg-soft: var(--accent-app, var(--color-surface-2));
    --g-bd: var(--border-app, var(--color-border));
}
.uform .gx {
    background: var(--g-bg);
    border: 1px solid var(--g-bd);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-app, none);
    backdrop-filter: var(--backdrop, none);
}
.uform-hero {
    display: flex;
    align-items: center;
    gap: var(--sp-4);
    padding: var(--sp-5);
    margin-bottom: var(--sp-4);
}
.uform-avatar {
    width: 68px;
    height: 68px;
    font-size: 1.4rem;
    border-radius: var(--radius-lg);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
    color: #ffffff;
    box-shadow: 0 4px 14px color-mix(in srgb, var(--color-primary) 30%, transparent);
    border: 2px solid rgba(255, 255, 255, 0.15);
}
.uform-hero-body {
    flex: 1;
    min-width: 0;
}
.uform-avatar-photo {
    background-size: cover;
    background-position: center;
    background-color: var(--g-bg-soft);
}
.uform-hero-name {
    font-size: var(--font-size-xl);
    font-weight: var(--font-weight-bold);
    color: var(--color-text);
    letter-spacing: -0.01em;
}
.uform-hero-cargo {
    font-size: var(--font-size-sm);
    color: var(--color-primary);
    font-weight: 600;
    margin-top: 2px;
}
.uform-mini-activity {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid var(--g-bd);
    font-size: .78rem;
}
.uform-mini-activity:last-child {
    border-bottom: none;
}
.uform-hero-meta {
    display: flex;
    align-items: center;
    gap: var(--sp-3);
    margin-top: 6px;
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
    flex-wrap: wrap;
}
.uform-hero-meta code {
    font-family: var(--font-mono);
    background: var(--g-bg-soft);
    border: 1px solid var(--g-bd);
    padding: 3px 8px;
    border-radius: var(--radius-sm);
    color: var(--color-primary);
    font-weight: 600;
}
.uform-hero-meta .sep {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: var(--color-text-light);
}
.uform-hero-stats {
    display: flex;
    gap: var(--sp-5);
    flex-shrink: 0;
}
.uform-stat {
    text-align: right;
}
.uform-stat b {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    font-size: var(--font-size-base);
    font-weight: 800;
    color: var(--color-text);
}
.uform-stat span {
    font-size: 11px;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
}
.uform-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: var(--sp-4);
    align-items: start;
}
.uform-card {
    margin-bottom: var(--sp-4);
}
.uform-card-head {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px var(--sp-5);
    font-weight: 700;
    font-size: var(--font-size-sm);
    color: var(--color-text);
    border-bottom: 1px solid var(--g-bd);
}
.uform-card-head i {
    color: var(--color-primary);
}
.uform-card-body {
    padding: var(--sp-5);
}
.uform-2col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--sp-4);
}
.uform-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: var(--radius-full);
    background: color-mix(in srgb, var(--color-primary) 14%, transparent);
    color: var(--color-primary);
    border: 1px solid color-mix(in srgb, var(--color-primary) 28%, transparent);
}
.uform-role-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}
.uform-role-chip {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border: 1px solid var(--g-bd);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-primary) 5%, transparent);
    transition: var(--transition);
}
.uform-role-chip:hover {
    border-color: var(--color-primary);
    background: color-mix(in srgb, var(--color-primary) 10%, transparent);
}
.uform-role-name {
    font-size: var(--font-size-sm);
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: var(--color-text);
}
.uform-role-code code {
    font-size: 10px;
    color: var(--color-text-muted);
}
.uform-role-tick {
    flex-shrink: 0;
}
.profile-security-status {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    border-radius: var(--radius-md);
}
.profile-security-status.on {
    background: color-mix(in srgb, var(--color-success) 10%, transparent);
    border: 1px solid color-mix(in srgb, var(--color-success) 30%, transparent);
}
.profile-security-status.off {
    background: color-mix(in srgb, var(--color-warning) 10%, transparent);
    border: 1px solid color-mix(in srgb, var(--color-warning) 30%, transparent);
}
.profile-security-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1rem;
}
.profile-security-status.on .profile-security-icon {
    background: color-mix(in srgb, var(--color-success) 20%, transparent);
    color: var(--color-success);
}
.profile-security-status.off .profile-security-icon {
    background: color-mix(in srgb, var(--color-warning) 20%, transparent);
    color: var(--color-warning);
}
@media (max-width: 860px) {
    .uform-grid { grid-template-columns: 1fr; }
    .uform-2col { grid-template-columns: 1fr; }
    .uform-hero { flex-direction: column; align-items: flex-start; }
    .uform-hero-stats { width: 100%; justify-content: space-between; margin-top: var(--sp-2); }
}
</style>
