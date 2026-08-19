<?php
if ($error)   { echo '<script>document.addEventListener("DOMContentLoaded",()=>PortalAlert.error(' . json_encode($error) . '));</script>'; }
if ($success) { echo '<script>document.addEventListener("DOMContentLoaded",()=>PortalAlert.success(' . json_encode($success) . '));</script>'; }

$fmtFecha = function ($v) {
    if (!$v) return null;
    if ($v instanceof DateTime) return $v->format('d/m/Y H:i');
    return date('d/m/Y H:i', strtotime((string)$v));
};
// Secreto en grupos de 4 -- mas facil de teclear a mano en la app autenticadora.
$secretoFormateado = $enrollment ? trim(chunk_split($enrollment['secret'], 4, ' ')) : '';
?>

<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-5);">
    <a href="<?= APP_URL ?>/perfil" class="btn btn-ghost btn-sm" data-spa><i class="fa-solid fa-arrow-left"></i></a>
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-shield-halved" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
        Seguridad de la Cuenta
    </h2>
</div>

<div class="uform" style="max-width:640px;">
    <div class="gx uform-card">
        <div class="uform-card-body">
            <div class="profile-security-status <?= $mfaActivo ? 'on' : 'off' ?>" style="margin-bottom:0;">
                <div class="profile-security-icon"><i class="fa-solid <?= $mfaActivo ? 'fa-shield-check' : 'fa-shield' ?>"></i></div>
                <div>
                    <div style="font-weight:700;font-size:.95rem;">Verificación en dos pasos <?= $mfaActivo ? 'activa' : 'inactiva' ?></div>
                    <div style="font-size:.8rem;color:var(--color-text-muted);margin-top:2px;">
                        <?= $mfaActivo
                            ? 'Tu cuenta pide un código de 6 dígitos, además de la contraseña, al iniciar sesión y al entrar a Talento Humano, Control de Bienes o Bitácoras.'
                            : 'Agregá una capa extra: además de tu contraseña, un código de tu celular.' ?>
                    </div>
                    <?php if ($mfaActivo && $activadoEn): ?>
                    <div style="font-size:.72rem;color:var(--color-text-light);margin-top:6px;">Activada el <?= htmlspecialchars($fmtFecha($activadoEn), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$mfaActivo && !$enrollment): ?>
    <!-- Paso 0: nada configurado -->
    <div class="gx uform-card">
        <div class="uform-card-body">
            <form method="POST" action="<?= APP_URL ?>/perfil/seguridad/preparar">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-mobile-screen-button"></i> Configurar aplicación autenticadora
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$mfaActivo && $enrollment): ?>
    <!-- Paso 1: mostrar clave + confirmar código -->
    <div class="gx uform-card">
        <div class="uform-card-head"><i class="fa-solid fa-key"></i> 1. Agregá la cuenta</div>
        <div class="uform-card-body">
            <p style="font-size:.85rem;color:var(--color-text-muted);margin:0 0 var(--sp-3);">
                En Google Authenticator, Microsoft Authenticator o Authy, elegí "Ingresar clave de configuración" y pegá esto:
            </p>
            <div class="mfa-secret-box"><?= htmlspecialchars($secretoFormateado, ENT_QUOTES, 'UTF-8') ?></div>
            <details style="margin-top:10px;">
                <summary style="cursor:pointer;font-size:.78rem;color:var(--color-text-muted);">Mostrar URI técnica (otpauth://)</summary>
                <div class="mfa-secret-box" style="margin-top:8px;font-size:.68rem;letter-spacing:0;word-break:break-all;"><?= htmlspecialchars($enrollment['uri'], ENT_QUOTES, 'UTF-8') ?></div>
            </details>
        </div>
    </div>

    <div class="gx uform-card">
        <div class="uform-card-head"><i class="fa-solid fa-check-double"></i> 2. Confirmá el código</div>
        <div class="uform-card-body">
            <form method="POST" action="<?= APP_URL ?>/perfil/seguridad/activar" style="display:flex;gap:var(--sp-3);align-items:center;flex-wrap:wrap;">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="text" name="codigo" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required
                       placeholder="000000" class="form-control" style="max-width:160px;font-family:var(--font-mono);font-size:1.1rem;letter-spacing:.15em;text-align:center;" autofocus>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Activar</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($mfaActivo): ?>
    <div class="gx uform-card">
        <div class="uform-card-head" style="color:#dc3545;"><i class="fa-solid fa-triangle-exclamation" style="color:#dc3545;"></i> Desactivar protección</div>
        <div class="uform-card-body">
            <p style="font-size:.82rem;color:var(--color-text-muted);margin:0 0 var(--sp-3);">
                Pide tu contraseña actual y un código vigente de tu aplicación autenticadora.
            </p>
            <form method="POST" action="<?= APP_URL ?>/perfil/seguridad/desactivar"
                  onsubmit="return confirm('¿Seguro que querés desactivar la verificación en dos pasos?')">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <div class="uform-2col">
                    <div class="form-group">
                        <label class="form-label">Contraseña actual</label>
                        <input type="password" name="clave" class="form-control" autocomplete="current-password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Código de 6 dígitos</label>
                        <input type="text" name="codigo" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required
                               class="form-control" style="font-family:var(--font-mono);letter-spacing:.1em;">
                    </div>
                </div>
                <button type="submit" class="btn btn-outline" style="color:#dc3545;border-color:#dc3545;margin-top:var(--sp-3);">
                    <i class="fa-solid fa-shield-halved"></i> Desactivar verificación en dos pasos
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="mfa-tip">
        <i class="fa-solid fa-circle-info"></i>
        No compartas tu clave secreta ni le saques captura. Si perdés el dispositivo, un administrador puede restablecer tu segundo factor desde Gestión de Usuarios.
    </div>
</div>

<style>
.uform { --g-bg: var(--surface-app, var(--color-surface)); --g-bg-soft: var(--accent-app, var(--color-surface-2)); --g-bd: var(--border-app, var(--color-border)); }
.uform .gx { background:var(--g-bg); border:1px solid var(--g-bd); border-radius:var(--radius-lg); box-shadow:var(--shadow-app, none); }
.uform-card { margin-bottom:var(--sp-4); }
.uform-card-head { display:flex; align-items:center; gap:8px; padding:12px var(--sp-4); font-weight:700; font-size:var(--font-size-sm); color:var(--color-text); border-bottom:1px solid var(--g-bd); }
.uform-card-head i { color:var(--color-primary); }
.uform-card-body { padding:var(--sp-4); }
.uform-2col { display:grid; grid-template-columns:1fr 1fr; gap:var(--sp-4); }
.profile-security-status { display:flex; align-items:flex-start; gap:12px; padding:4px; border-radius:var(--radius-md); }
.profile-security-status.on .profile-security-icon { background:color-mix(in srgb,#22c55e 18%,transparent); color:#22c55e; }
.profile-security-status.off .profile-security-icon { background:color-mix(in srgb,#f59e0b 18%,transparent); color:#f59e0b; }
.profile-security-icon { width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1rem; }
.mfa-secret-box { padding:14px 16px; border:1.5px dashed var(--color-primary); background:color-mix(in srgb,var(--color-primary) 6%,transparent); border-radius:var(--radius-md); font-family:var(--font-mono); font-size:1.05rem; letter-spacing:.1em; word-break:break-all; text-align:center; color:var(--color-text); }
.mfa-tip { display:flex; align-items:flex-start; gap:10px; padding:12px 14px; border-radius:var(--radius-md); background:var(--g-bg-soft); font-size:.78rem; color:var(--color-text-muted); line-height:1.5; }
@media (max-width:600px) { .uform-2col { grid-template-columns:1fr; } }
</style>
