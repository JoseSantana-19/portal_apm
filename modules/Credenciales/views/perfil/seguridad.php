<?php
/**
 * Seguridad de la Cuenta & MFA (Elite Edition) — Central Portal APM
 * Gestión de autenticación en dos pasos (TOTP / RFC 6238) con inputs OTP automáticos y blindaje criptográfico.
 */
if (!empty($error))   { echo '<script>document.addEventListener("DOMContentLoaded",()=>PortalAlert.error(' . json_encode($error) . '));</script>'; }
if (!empty($success)) { echo '<script>document.addEventListener("DOMContentLoaded",()=>PortalAlert.success(' . json_encode($success) . '));</script>'; }

$fmtFecha = function ($v) {
    if (!$v) return null;
    if ($v instanceof DateTime) return $v->format('d/m/Y H:i');
    return date('d/m/Y H:i', strtotime((string)$v));
};
$secretoFormateado = !empty($enrollment['secret']) ? trim(chunk_split($enrollment['secret'], 4, ' ')) : '';
?>

<div class="dashboard-wrapper anim-up anim-d0">

    <!-- ══════════════════════════════════════════════════════════════
         PREMIUM HEADER
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-page-header">
        <div class="admin-header-title-group">
            <div class="admin-header-icon" style="background:linear-gradient(135deg, #8B5CF6, #6D28D9);">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <div class="admin-header-eyebrow">
                    <i class="fa-solid fa-fingerprint"></i> Mi Cuenta &bull; Verificación en Dos Pasos (2FA)
                </div>
                <h1 class="admin-header-title">Seguridad de la Cuenta</h1>
                <div class="admin-header-subtitle">
                    Protege tu sesión con tokens dinámicos TOTP compatibles con Google Authenticator, Microsoft Authenticator y Authy
                </div>
            </div>
        </div>

        <div style="display:flex;gap:var(--sp-2);align-items:center;">
            <a href="<?= APP_URL ?>/perfil" class="btn-dash" data-spa title="Volver a Mi Perfil">
                <i class="fa-solid fa-arrow-left"></i> Volver a Mi Perfil
            </a>
        </div>
    </div>

    <div style="max-width:760px;margin:0 auto;">
        <!-- ══════════════════════════════════════════════════════════════
             STATUS MASTER CARD
             ══════════════════════════════════════════════════════════════ -->
        <div class="account-card">
            <div class="account-card-body" style="padding:var(--sp-5);">
                <div style="display:flex;align-items:flex-start;gap:16px;">
                    <div style="width:52px;height:52px;border-radius:var(--radius-md);background:color-mix(in srgb, <?= $mfaActivo ? '#10B981' : '#F59E0B' ?> 15%, transparent);color:<?= $mfaActivo ? '#10B981' : '#F59E0B' ?>;display:flex;align-items:center;justify-content:center;font-size:1.45rem;flex-shrink:0;">
                        <i class="fa-solid <?= $mfaActivo ? 'fa-shield-check' : 'fa-triangle-exclamation' ?>"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                            <h3 style="margin:0;font-size:1.2rem;font-weight:800;color:var(--text-app);">
                                Verificación en dos pasos <?= $mfaActivo ? 'Activada' : 'Desactivada' ?>
                            </h3>
                            <span class="badge badge-<?= $mfaActivo ? 'success' : 'warning' ?>" style="font-size:0.75rem;font-weight:800;">
                                <?= $mfaActivo ? 'Protección Activa' : 'Protección Básica' ?>
                            </span>
                        </div>
                        <p style="font-size:0.85rem;color:var(--text-muted);margin:6px 0 0;line-height:1.45;">
                            <?= $mfaActivo
                                ? 'Tu cuenta exige un código de 6 dígitos al iniciar sesión y al acceder a los subsistemas de Talento Humano, Control de Bienes y Bitácoras Portuarias.'
                                : 'Añade una capa de blindaje: cada vez que ingreses, se te solicitará un código de 6 dígitos generado en tu teléfono móvil.' ?>
                        </p>
                        <?php if ($mfaActivo && !empty($activadoEn)): ?>
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:8px;font-family:var(--font-code);">
                            <i class="fa-regular fa-clock" style="margin-right:4px;"></i> Activada el <?= htmlspecialchars($fmtFecha($activadoEn), ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$mfaActivo && empty($enrollment)): ?>
        <!-- Paso 0: Botón de Inicio de Configuración -->
        <div class="account-card">
            <div class="account-card-header">
                <div style="display:flex;align-items:center;gap:10px;">
                    <i class="fa-solid fa-mobile-screen-button" style="color:var(--primary-hover);"></i>
                    <span>Configurar Nueva Aplicación Autenticadora</span>
                </div>
            </div>
            <div class="account-card-body" style="text-align:center;padding:var(--sp-6);">
                <div style="width:72px;height:72px;border-radius:50%;background:color-mix(in srgb, var(--primary-hover) 15%, transparent);color:var(--primary-hover);display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px;">
                    <i class="fa-solid fa-qrcode"></i>
                </div>
                <h4 style="font-size:1.15rem;font-weight:800;color:var(--text-app);margin:0 0 6px;">Vincula tu teléfono inteligente</h4>
                <p style="font-size:0.85rem;color:var(--text-muted);max-width:480px;margin:0 auto var(--sp-5);">
                    Genera una clave secreta para sincronizarla con cualquier aplicación TOTP de tu preferencia (Google Authenticator, Microsoft Authenticator, 1Password, etc.).
                </p>
                <form method="POST" action="<?= APP_URL ?>/perfil/seguridad/preparar">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-primary" style="padding:0 28px;height:44px;font-size:0.9rem;">
                        <i class="fa-solid fa-key"></i> Iniciar Configuración 2FA
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$mfaActivo && !empty($enrollment)): ?>
        <!-- Paso 1 & 2: Asistente de Configuración -->
        <div class="account-card">
            <div class="account-card-header">
                <div style="display:flex;align-items:center;gap:10px;">
                    <i class="fa-solid fa-key" style="color:var(--primary-hover);"></i>
                    <span>Paso 1: Registra la clave en tu aplicación</span>
                </div>
                <span class="badge badge-info" style="font-size:0.68rem;">Expira en 10 min</span>
            </div>
            <div class="account-card-body">
                <p style="font-size:0.85rem;color:var(--text-muted);margin:0 0 var(--sp-3);">
                    En tu aplicación autenticadora (Google Authenticator o Microsoft Authenticator), selecciona <strong>"Ingresar clave de configuración"</strong> e introduce esta clave:
                </p>
                <div style="position:relative;margin-bottom:12px;">
                    <div id="mfa-secret-text" style="padding:16px 20px;border:2px dashed var(--primary-hover);background:color-mix(in srgb, var(--primary-hover) 8%, var(--surface-app));border-radius:var(--radius-md);font-family:var(--font-code);font-size:1.25rem;font-weight:800;letter-spacing:0.15em;text-align:center;color:var(--text-app);user-select:all;">
                        <?= htmlspecialchars($secretoFormateado, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <div style="display:flex;justify-content:center;margin-bottom:14px;">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($enrollment['secret'], ENT_QUOTES, 'UTF-8') ?>').then(() => PortalAlert.success('Clave secreta copiada al portapapeles.'));">
                        <i class="fa-solid fa-copy"></i> Copiar Clave Secreta
                    </button>
                </div>

                <details style="font-size:0.75rem;color:var(--text-muted);">
                    <summary style="cursor:pointer;font-weight:600;">Ver enlace URI técnico (otpauth://)</summary>
                    <div style="margin-top:6px;padding:8px 12px;background:var(--accent-app);border-radius:var(--radius-sm);word-break:break-all;font-family:var(--font-code);">
                        <?= htmlspecialchars($enrollment['uri'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </details>
            </div>
        </div>

        <div class="account-card">
            <div class="account-card-header">
                <div style="display:flex;align-items:center;gap:10px;">
                    <i class="fa-solid fa-check-double" style="color:var(--primary-hover);"></i>
                    <span>Paso 2: Confirma el código de 6 dígitos</span>
                </div>
            </div>
            <div class="account-card-body" style="text-align:center;">
                <p style="font-size:0.85rem;color:var(--text-muted);margin:0 0 var(--sp-3);">
                    Ingresa el código que muestra tu teléfono móvil para verificar la sincronización:
                </p>
                
                <form method="POST" action="<?= APP_URL ?>/perfil/seguridad/activar" id="form-mfa-otp">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="codigo" id="real-otp-code">
                    
                    <!-- 6 Digit Visual Boxes -->
                    <div class="otp-input-container">
                        <input type="text" class="otp-digit-field" maxlength="1" pattern="[0-9]" inputmode="numeric" autofocus data-idx="0">
                        <input type="text" class="otp-digit-field" maxlength="1" pattern="[0-9]" inputmode="numeric" data-idx="1">
                        <input type="text" class="otp-digit-field" maxlength="1" pattern="[0-9]" inputmode="numeric" data-idx="2">
                        <span style="font-weight:800;color:var(--text-muted);font-size:1.4rem;">-</span>
                        <input type="text" class="otp-digit-field" maxlength="1" pattern="[0-9]" inputmode="numeric" data-idx="3">
                        <input type="text" class="otp-digit-field" maxlength="1" pattern="[0-9]" inputmode="numeric" data-idx="4">
                        <input type="text" class="otp-digit-field" maxlength="1" pattern="[0-9]" inputmode="numeric" data-idx="5">
                    </div>

                    <button type="submit" class="btn btn-primary" id="btn-submit-otp" disabled style="height:44px;padding:0 30px;font-size:0.9rem;margin-top:10px;">
                        <i class="fa-solid fa-shield-check"></i> Confirmar y Activar 2FA
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($mfaActivo): ?>
        <!-- Desactivación Segura de 2FA -->
        <div class="account-card" style="border-color:color-mix(in srgb, #EF4444 35%, var(--border-app));">
            <div class="account-card-header" style="color:#EF4444;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <i class="fa-solid fa-triangle-exclamation" style="color:#EF4444;"></i>
                    <span>Desactivar Verificación en Dos Pasos</span>
                </div>
            </div>
            <div class="account-card-body">
                <p style="font-size:0.83rem;color:var(--text-muted);margin:0 0 var(--sp-4);">
                    Por motivos de seguridad institucional, para desactivar el segundo factor debes confirmar tu contraseña actual y un código OTP vigente:
                </p>
                <form method="POST" action="<?= APP_URL ?>/perfil/seguridad/desactivar" onsubmit="return handleDesactivarMfaSubmit(event)">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:var(--sp-4);">
                        <div class="form-group">
                            <label class="form-label" style="font-size:0.75rem;font-weight:700;">Contraseña Actual</label>
                            <input type="password" name="clave" class="form-control" autocomplete="current-password" required placeholder="Tu contraseña" style="height:42px;">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size:0.75rem;font-weight:700;">Código 2FA (6 dígitos)</label>
                            <input type="text" name="codigo" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required placeholder="000000" class="form-control" style="font-family:var(--font-code);letter-spacing:0.15em;text-align:center;font-weight:700;height:42px;">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline" style="color:#EF4444;border-color:#EF4444;margin-top:var(--sp-4);height:40px;">
                        <i class="fa-solid fa-shield-slash"></i> Desactivar Verificación 2FA
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Banner Informativo -->
        <div style="display:flex;align-items:flex-start;gap:12px;padding:16px;border-radius:var(--radius-md);background:var(--accent-app);border:1px solid var(--border-app);font-size:0.8rem;color:var(--text-muted);line-height:1.45;margin-top:var(--sp-4);">
            <i class="fa-solid fa-circle-info" style="color:var(--primary-hover);font-size:16px;margin-top:2px;"></i>
            <div>
                <strong style="color:var(--text-app);">Aviso de Seguridad Institucional:</strong> No compartas tu clave secreta ni captures fotos de la misma. Si pierdes el acceso a tu teléfono, un Administrador General puede restablecer tu factor 2FA desde la consola de Gestión de Usuarios.
            </div>
        </div>
    </div>

</div>

<script>
// Manejo automático de inputs de 6 dígitos OTP
var otpFields = document.querySelectorAll('.otp-digit-field');
var realInput = document.getElementById('real-otp-code');
var otpSubmitBtn = document.getElementById('btn-submit-otp');

if (otpFields.length === 6) {
    otpFields.forEach(function(field, idx) {
        field.addEventListener('input', function(e) {
            var val = this.value.replace(/[^0-9]/g, '');
            this.value = val;
            if (val && idx < 5) {
                otpFields[idx + 1].focus();
            }
            updateRealOtp();
        });

        field.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !this.value && idx > 0) {
                otpFields[idx - 1].focus();
            }
        });

        field.addEventListener('paste', function(e) {
            e.preventDefault();
            var pasteData = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
            if (pasteData.length >= 6) {
                for (var i = 0; i < 6; i++) {
                    otpFields[i].value = pasteData[i];
                }
                otpFields[5].focus();
                updateRealOtp();
            }
        });
    });

    function updateRealOtp() {
        var full = '';
        otpFields.forEach(f => full += f.value);
        if (realInput) realInput.value = full;
        if (otpSubmitBtn) otpSubmitBtn.disabled = full.length !== 6;
    }
}

function handleDesactivarMfaSubmit(e) {
    e.preventDefault();
    const form = e.target;
    PortalAlert.confirmAction('¿Confirmas que deseas desactivar la verificación en dos pasos de tu cuenta? Tu nivel de seguridad disminuirá.', function() {
        if (!window.hashPasswordFieldsBeforeSubmit) {
            form.submit();
            return;
        }
        hashPasswordFieldsBeforeSubmit(form, ['clave']).then(function() {
            form.submit();
        });
    }, {
        title: '¿Desactivar 2FA?',
        confirmText: 'Sí, desactivar 2FA',
        icon: 'warning'
    });
    return false;
}
</script>
