<?php
/**
 * Cambiar Contraseña (Elite Edition) — Central Portal APM
 * Estudio interactivo de seguridad de claves con generador integrado, medidor de entropía y cifrado SHA-256 en cliente.
 */
?>
<div class="dashboard-wrapper anim-up anim-d0">

    <!-- ══════════════════════════════════════════════════════════════
         PREMIUM HEADER
         ══════════════════════════════════════════════════════════════ -->
    <div class="admin-page-header">
        <div class="admin-header-title-group">
            <div class="admin-header-icon" style="background:linear-gradient(135deg, #0284C7, #0369A1);">
                <i class="fa-solid fa-key"></i>
            </div>
            <div>
                <div class="admin-header-eyebrow">
                    <i class="fa-solid fa-user-shield"></i> Mi Cuenta &bull; Seguridad de Credenciales
                </div>
                <h1 class="admin-header-title">Cambiar Contraseña</h1>
                <div class="admin-header-subtitle">
                    Actualiza tu clave de acceso institucional para mantener protegida tu cuenta y módulos
                </div>
            </div>
        </div>

        <div style="display:flex;gap:var(--sp-2);align-items:center;">
            <a href="<?= APP_URL ?>/perfil" class="btn-dash" data-spa title="Volver a Mi Perfil">
                <i class="fa-solid fa-arrow-left"></i> Volver a Mi Perfil
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
    <script>document.addEventListener('DOMContentLoaded', () => PortalAlert.error(<?= json_encode($error) ?>));</script>
    <?php endif; ?>

    <div class="account-grid-2col">
        <!-- Columna Principal: Formulario -->
        <div>
            <form method="POST" action="<?= APP_URL ?>/cambiar-contrasena" id="form-pass" autocomplete="off">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="account-card">
                    <div class="account-card-header">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <i class="fa-solid fa-shield-halved" style="color:var(--primary-hover);"></i>
                            <span>Actualizar Clave de Acceso</span>
                        </div>
                        <span class="badge badge-info" style="font-size:0.68rem;">Cifrado SHA-256</span>
                    </div>

                    <div class="account-card-body">
                        <!-- Contraseña Actual -->
                        <div class="form-group">
                            <label class="form-label" for="pass-actual" style="font-size:0.78rem;font-weight:700;">
                                <i class="fa-solid fa-lock" style="color:var(--text-muted);font-size:11px;margin-right:4px;"></i> Contraseña Actual <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="pw-input-wrapper" style="position:relative;display:flex;align-items:center;">
                                <input type="password" name="contrasena_actual" id="pass-actual" class="form-control" required
                                       autocomplete="current-password" placeholder="Ingresa tu contraseña actual" style="padding-right:44px;height:42px;">
                                <button type="button" class="pw-toggle-btn" onclick="togglePasswordVisibility('pass-actual', this)" tabindex="-1" title="Mostrar/ocultar contraseña" style="position:absolute;right:8px;background:none;border:none;color:var(--text-muted);cursor:pointer;padding:6px;">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div style="border-top:1px solid var(--border-app);margin:var(--sp-4) 0;"></div>

                        <!-- Nueva Contraseña -->
                        <div class="form-group">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                                <label class="form-label" for="pass-nueva" style="font-size:0.78rem;font-weight:700;margin:0;">
                                    <i class="fa-solid fa-key" style="color:var(--primary-hover);font-size:11px;margin-right:4px;"></i> Nueva Contraseña <span style="color:var(--danger);">*</span>
                                </label>
                                <button type="button" class="btn btn-ghost btn-sm" onclick="generarPasswordSegura()" style="font-size:0.72rem;padding:1px 8px;" title="Generar clave aleatoria de alta seguridad">
                                    <i class="fa-solid fa-wand-magic-sparkles" style="color:var(--primary-hover);"></i> Generar Clave Segura
                                </button>
                            </div>

                            <div class="pw-input-wrapper" style="position:relative;display:flex;align-items:center;">
                                <input type="password" name="contrasena_nueva" class="form-control" required minlength="8"
                                       id="pass-nueva" autocomplete="new-password" placeholder="Crea una clave segura (mínimo 8 caracteres)" style="padding-right:44px;height:42px;">
                                <button type="button" class="pw-toggle-btn" onclick="togglePasswordVisibility('pass-nueva', this)" tabindex="-1" title="Mostrar/ocultar contraseña" style="position:absolute;right:8px;background:none;border:none;color:var(--text-muted);cursor:pointer;padding:6px;">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            
                            <!-- Barra de Fortaleza -->
                            <div class="pw-strength-track" style="height:6px;border-radius:99px;background:var(--accent-app);margin-top:10px;overflow:hidden;border:1px solid var(--border-app);">
                                <div class="pw-strength-fill" id="pw-strength-fill" style="height:100%;width:0%;border-radius:99px;transition:width 0.25s ease, background-color 0.25s ease;"></div>
                            </div>
                            <div class="pw-strength-meta" style="display:flex;align-items:center;justify-content:space-between;margin-top:6px;font-size:0.72rem;">
                                <span style="color:var(--text-muted);">Nivel de entropía y fortaleza:</span>
                                <span id="pw-strength-label" style="font-weight:800;text-transform:uppercase;">—</span>
                            </div>
                        </div>

                        <!-- Confirmar Nueva Contraseña -->
                        <div class="form-group" style="margin-top:var(--sp-4);">
                            <label class="form-label" for="pass-confirma" style="font-size:0.78rem;font-weight:700;">
                                <i class="fa-solid fa-circle-check" style="color:var(--text-muted);font-size:11px;margin-right:4px;"></i> Confirmar Nueva Contraseña <span style="color:var(--danger);">*</span>
                            </label>
                            <div class="pw-input-wrapper" style="position:relative;display:flex;align-items:center;">
                                <input type="password" name="contrasena_confirma" class="form-control" required
                                       id="pass-confirma" autocomplete="new-password" placeholder="Vuelve a escribir la nueva contraseña" style="padding-right:44px;height:42px;">
                                <button type="button" class="pw-toggle-btn" onclick="togglePasswordVisibility('pass-confirma', this)" tabindex="-1" title="Mostrar/ocultar contraseña" style="position:absolute;right:8px;background:none;border:none;color:var(--text-muted);cursor:pointer;padding:6px;">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            <div id="pass-match-msg" class="pw-match-feedback" style="display:none;font-size:0.75rem;font-weight:700;margin-top:8px;padding:8px 12px;border-radius:var(--radius-md);"></div>
                        </div>

                        <div style="display:flex;gap:var(--sp-3);justify-content:flex-end;align-items:center;margin-top:var(--sp-5);">
                            <a href="<?= APP_URL ?>/perfil" class="btn btn-ghost" data-spa>Cancelar</a>
                            <button type="submit" class="btn btn-primary" id="btn-submit-pass" disabled style="height:42px;padding:0 26px;">
                                <i class="fa-solid fa-floppy-disk"></i> Guardar Nueva Contraseña
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Columna Lateral: Requisitos y Seguridad -->
        <div>
            <!-- Requisitos de la Política -->
            <div class="account-card">
                <div class="account-card-header">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="fa-solid fa-list-check" style="color:var(--primary-hover);"></i>
                        <span>Requisitos de Complejidad</span>
                    </div>
                </div>
                <div class="account-card-body">
                    <p style="font-size:0.75rem;color:var(--text-muted);margin:0 0 12px 0;">
                        Tu nueva clave debe satisfacer todos los requisitos de seguridad institucional:
                    </p>
                    <ul class="pw-requisitos" id="pw-requisitos" style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;">
                        <li data-req="len" style="font-size:0.75rem;color:var(--text-muted);display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:var(--radius-md);background:var(--accent-app);border:1px solid var(--border-app);">
                            <div class="pw-req-bullet" style="width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;"><i class="fa-solid fa-circle-dot"></i></div>
                            <span>Mínimo 8 caracteres</span>
                        </li>
                        <li data-req="lower" style="font-size:0.75rem;color:var(--text-muted);display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:var(--radius-md);background:var(--accent-app);border:1px solid var(--border-app);">
                            <div class="pw-req-bullet" style="width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;"><i class="fa-solid fa-circle-dot"></i></div>
                            <span>Una letra minúscula (a-z)</span>
                        </li>
                        <li data-req="upper" style="font-size:0.75rem;color:var(--text-muted);display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:var(--radius-md);background:var(--accent-app);border:1px solid var(--border-app);">
                            <div class="pw-req-bullet" style="width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;"><i class="fa-solid fa-circle-dot"></i></div>
                            <span>Una letra mayúscula (A-Z)</span>
                        </li>
                        <li data-req="num" style="font-size:0.75rem;color:var(--text-muted);display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:var(--radius-md);background:var(--accent-app);border:1px solid var(--border-app);">
                            <div class="pw-req-bullet" style="width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;"><i class="fa-solid fa-circle-dot"></i></div>
                            <span>Al menos un número (0-9)</span>
                        </li>
                        <li data-req="special" style="font-size:0.75rem;color:var(--text-muted);display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:var(--radius-md);background:var(--accent-app);border:1px solid var(--border-app);">
                            <div class="pw-req-bullet" style="width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;"><i class="fa-solid fa-circle-dot"></i></div>
                            <span>Un símbolo especial (!@#$...)</span>
                        </li>
                    </ul>

                    <div style="display:flex;align-items:flex-start;gap:10px;margin-top:14px;padding:10px 12px;border-radius:var(--radius-md);background:color-mix(in srgb, var(--primary-hover) 8%, transparent);border:1px solid color-mix(in srgb, var(--primary-hover) 20%, transparent);font-size:0.75rem;color:var(--text-app);line-height:1.4;">
                        <i class="fa-solid fa-rotate-left" style="color:var(--primary-hover);font-size:13px;flex-shrink:0;margin-top:2px;"></i>
                        <span><strong>Historial:</strong> No puedes reutilizar ninguna de tus <strong>últimas 5 contraseñas</strong> anteriores.</span>
                    </div>
                </div>
            </div>

            <!-- Seguridad Adicional -->
            <div class="account-card">
                <div class="account-card-header">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i class="fa-solid fa-user-lock" style="color:var(--primary-hover);"></i>
                        <span>Protección de Identidad</span>
                    </div>
                </div>
                <div class="account-card-body">
                    <div style="font-size:0.75rem;color:var(--text-muted);line-height:1.5;display:flex;flex-direction:column;gap:10px;">
                        <div style="display:flex;align-items:flex-start;gap:8px;">
                            <i class="fa-solid fa-check" style="color:#10B981;margin-top:3px;font-size:11px;"></i>
                            <span>No compartas tu contraseña institucional con otros funcionarios.</span>
                        </div>
                        <div style="display:flex;align-items:flex-start;gap:8px;">
                            <i class="fa-solid fa-check" style="color:#10B981;margin-top:3px;font-size:11px;"></i>
                            <span>Activa la verificación en dos pasos (2FA) para blindar tus accesos.</span>
                        </div>
                    </div>

                    <div style="margin-top:var(--sp-4);border-top:1px solid var(--border-app);padding-top:var(--sp-3);">
                        <a href="<?= APP_URL ?>/perfil/seguridad" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;" data-spa>
                            <i class="fa-solid fa-mobile-screen-button" style="color:var(--primary-hover);"></i> Administrar Verificación 2FA
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
var pwNueva    = document.getElementById('pass-nueva');
var pwConfirma = document.getElementById('pass-confirma');
var pwMsg      = document.getElementById('pass-match-msg');
var pwBtn      = document.getElementById('btn-submit-pass');
var pwFill     = document.getElementById('pw-strength-fill');
var pwLabel    = document.getElementById('pw-strength-label');

function togglePasswordVisibility(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
}

function generarPasswordSegura() {
    var charsUpper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    var charsLower = 'abcdefghijkmnopqrstuvwxyz';
    var charsNum   = '23456789';
    var charsSym   = '!@#$%^&*()-_=+';
    var all = charsUpper + charsLower + charsNum + charsSym;

    var pass = '';
    pass += charsUpper.charAt(Math.floor(Math.random() * charsUpper.length));
    pass += charsLower.charAt(Math.floor(Math.random() * charsLower.length));
    pass += charsNum.charAt(Math.floor(Math.random() * charsNum.length));
    pass += charsSym.charAt(Math.floor(Math.random() * charsSym.length));

    for (var i = 0; i < 12; i++) {
        pass += all.charAt(Math.floor(Math.random() * all.length));
    }
    // Shuffle
    pass = pass.split('').sort(function(){return 0.5-Math.random()}).join('');

    pwNueva.value = pass;
    pwConfirma.value = pass;
    pwNueva.type = 'text';
    pwConfirma.type = 'text';
    pwChecarTodo();

    if (navigator.clipboard) {
        navigator.clipboard.writeText(pass).then(function() {
            PortalAlert.success('Contraseña segura generada y copiada al portapapeles.');
        });
    }
}

function pwChecarRequisitos() {
    var v = pwNueva.value;
    var reqs = {
        len:     v.length >= 8,
        lower:   /[a-z]/.test(v),
        upper:   /[A-Z]/.test(v),
        num:     /[0-9]/.test(v),
        special: /[^a-zA-Z0-9]/.test(v),
    };
    var cumplidos = 0;
    Object.keys(reqs).forEach(function (key) {
        var li = document.querySelector('#pw-requisitos li[data-req="' + key + '"]');
        if (li) {
            li.classList.toggle('ok', reqs[key]);
            if (reqs[key]) {
                li.style.color = 'var(--text-app)';
                li.style.background = 'color-mix(in srgb, #10B981 10%, transparent)';
                li.style.borderColor = 'color-mix(in srgb, #10B981 30%, transparent)';
                var bullet = li.querySelector('.pw-req-bullet');
                if (bullet) bullet.innerHTML = '<i class="fa-solid fa-circle-check" style="color:#10B981;"></i>';
            } else {
                li.style.color = 'var(--text-muted)';
                li.style.background = 'var(--accent-app)';
                li.style.borderColor = 'var(--border-app)';
                var bullet = li.querySelector('.pw-req-bullet');
                if (bullet) bullet.innerHTML = '<i class="fa-solid fa-circle-dot"></i>';
            }
        }
        if (reqs[key]) cumplidos++;
    });

    var pct = (cumplidos / 5) * 100;
    pwFill.style.width = pct + '%';
    var color = '#EF4444', label = 'Muy débil';
    if (cumplidos >= 5) { color = '#10B981'; label = 'Excelente'; }
    else if (cumplidos >= 4) { color = '#84cc16'; label = 'Fuerte'; }
    else if (cumplidos >= 3) { color = '#F59E0B'; label = 'Aceptable'; }
    else if (cumplidos >= 1) { color = '#EF4444'; label = 'Débil'; }
    else { label = '—'; }
    pwFill.style.background = color;
    pwLabel.textContent = v ? label : '—';
    pwLabel.style.color = color;

    return cumplidos === 5;
}

function pwChecarTodo() {
    var okRequisitos = pwChecarRequisitos();
    var okMatch = false;
    if (!pwConfirma.value) {
        pwMsg.style.display = 'none';
    } else {
        pwMsg.style.display = 'flex';
        okMatch = pwNueva.value === pwConfirma.value;
        if (okMatch) {
            pwMsg.style.background = 'color-mix(in srgb, #10B981 10%, transparent)';
            pwMsg.style.color = '#10B981';
            pwMsg.style.border = '1px solid color-mix(in srgb, #10B981 28%, transparent)';
            pwMsg.innerHTML = '<i class="fa-solid fa-circle-check" style="margin-right:4px;"></i> Las contraseñas coinciden correctamente';
        } else {
            pwMsg.style.background = 'color-mix(in srgb, #EF4444 10%, transparent)';
            pwMsg.style.color = '#EF4444';
            pwMsg.style.border = '1px solid color-mix(in srgb, #EF4444 28%, transparent)';
            pwMsg.innerHTML = '<i class="fa-solid fa-circle-xmark" style="margin-right:4px;"></i> Las contraseñas no coinciden';
        }
    }
    pwBtn.disabled = !(okRequisitos && okMatch && (document.getElementById('pass-actual')?.value.length || 0) > 0);
}

pwNueva?.addEventListener('input', pwChecarTodo);
pwConfirma?.addEventListener('input', pwChecarTodo);
document.getElementById('pass-actual')?.addEventListener('input', pwChecarTodo);

var formPass = document.getElementById('form-pass');
if (formPass) {
    formPass.addEventListener('submit', function (e) {
        if (!window.hashPasswordFieldsBeforeSubmit) return;
        e.preventDefault();
        hashPasswordFieldsBeforeSubmit(formPass, ['contrasena_actual', 'contrasena_nueva', 'contrasena_confirma']).then(function () {
            formPass.submit();
        });
    });
}
</script>
