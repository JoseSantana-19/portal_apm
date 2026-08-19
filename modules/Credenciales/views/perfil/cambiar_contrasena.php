<div class="pw-view-wrap">

<div class="page-header" style="margin-bottom:var(--sp-4);">
    <div style="display:flex;align-items:center;gap:var(--sp-3);">
        <a href="<?= APP_URL ?>/perfil" class="btn btn-ghost btn-sm" data-spa title="Volver a Mi Perfil">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h2 class="page-title">
                <i class="fa-solid fa-lock" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
                Cambiar Contraseña
            </h2>
            <p class="page-subtitle">Actualiza tu clave de acceso para proteger tu cuenta y sesiones en el portal</p>
        </div>
    </div>
</div>

<?php if ($error): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.error(<?= json_encode($error) ?>));</script>
<?php endif; ?>

<div class="pw-grid">
    <!-- Columna Principal: Formulario -->
    <div>
        <form method="POST" action="<?= APP_URL ?>/cambiar-contrasena" id="form-pass" autocomplete="off">
            <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div class="card pw-card">
                <div class="pw-card-header">
                    <i class="fa-solid fa-key" style="color:var(--color-primary);"></i>
                    <span>Actualizar Credenciales</span>
                </div>

                <div class="pw-card-body">
                    <!-- Contraseña Actual -->
                    <div class="form-group">
                        <label class="form-label" for="pass-actual">
                            <i class="fa-solid fa-lock" style="color:var(--color-text-muted);font-size:11px;margin-right:4px;"></i> Contraseña Actual <span style="color:var(--color-danger);">*</span>
                        </label>
                        <div class="pw-input-wrapper">
                            <input type="password" name="contrasena_actual" id="pass-actual" class="form-control" required
                                   autocomplete="current-password" placeholder="Ingresa tu contraseña actual">
                            <button type="button" class="pw-toggle-btn" onclick="togglePasswordVisibility('pass-actual', this)" tabindex="-1" title="Mostrar/ocultar contraseña">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div style="border-top:1px solid var(--color-border);margin:var(--sp-4) 0;"></div>

                    <!-- Nueva Contraseña -->
                    <div class="form-group">
                        <label class="form-label" for="pass-nueva">
                            <i class="fa-solid fa-shield-halved" style="color:var(--color-primary);font-size:11px;margin-right:4px;"></i> Nueva Contraseña <span style="color:var(--color-danger);">*</span>
                        </label>
                        <div class="pw-input-wrapper">
                            <input type="password" name="contrasena_nueva" class="form-control" required minlength="8"
                                   id="pass-nueva" autocomplete="new-password" placeholder="Crea una contraseña segura de mínimo 8 caracteres">
                            <button type="button" class="pw-toggle-btn" onclick="togglePasswordVisibility('pass-nueva', this)" tabindex="-1" title="Mostrar/ocultar contraseña">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <div class="pw-strength-track"><div class="pw-strength-fill" id="pw-strength-fill"></div></div>
                        <div class="pw-strength-meta">
                            <span class="pw-strength-title">Fortaleza de la clave:</span>
                            <span class="pw-strength-label" id="pw-strength-label">—</span>
                        </div>
                    </div>

                    <!-- Confirmar Nueva Contraseña -->
                    <div class="form-group" style="margin-top:var(--sp-4);">
                        <label class="form-label" for="pass-confirma">
                            <i class="fa-solid fa-circle-check" style="color:var(--color-text-muted);font-size:11px;margin-right:4px;"></i> Confirmar Nueva Contraseña <span style="color:var(--color-danger);">*</span>
                        </label>
                        <div class="pw-input-wrapper">
                            <input type="password" name="contrasena_confirma" class="form-control" required
                                   id="pass-confirma" autocomplete="new-password" placeholder="Vuelve a escribir la nueva contraseña">
                            <button type="button" class="pw-toggle-btn" onclick="togglePasswordVisibility('pass-confirma', this)" tabindex="-1" title="Mostrar/ocultar contraseña">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <div id="pass-match-msg" class="pw-match-feedback" style="display:none;"></div>
                    </div>

                    <div style="display:flex;gap:var(--sp-3);justify-content:flex-end;align-items:center;margin-top:var(--sp-5);">
                        <a href="<?= APP_URL ?>/perfil" class="btn btn-outline" data-spa>Cancelar</a>
                        <button type="submit" class="btn btn-primary" id="btn-submit-pass" disabled style="height:38px;padding:0 22px;">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar Nueva Contraseña
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Columna Lateral: Requisitos y Buenas Prácticas -->
    <div style="display:flex;flex-direction:column;gap:var(--sp-4);">
        <!-- Requisitos de la Política -->
        <div class="card pw-card">
            <div class="pw-card-header">
                <i class="fa-solid fa-list-check" style="color:var(--color-primary);"></i>
                <span>Requisitos de la Política</span>
            </div>
            <div class="pw-card-body">
                <p style="font-size:0.78rem;color:var(--color-text-muted);margin:0 0 12px 0;line-height:1.4;">
                    Para garantizar la seguridad del portal institucional, tu nueva clave debe cumplir:
                </p>
                <ul class="pw-requisitos" id="pw-requisitos">
                    <li data-req="len">
                        <div class="pw-req-bullet"><i class="fa-solid fa-circle-dot"></i></div>
                        <span>Mínimo 8 caracteres</span>
                    </li>
                    <li data-req="lower">
                        <div class="pw-req-bullet"><i class="fa-solid fa-circle-dot"></i></div>
                        <span>Una letra minúscula (a-z)</span>
                    </li>
                    <li data-req="upper">
                        <div class="pw-req-bullet"><i class="fa-solid fa-circle-dot"></i></div>
                        <span>Una letra mayúscula (A-Z)</span>
                    </li>
                    <li data-req="num">
                        <div class="pw-req-bullet"><i class="fa-solid fa-circle-dot"></i></div>
                        <span>Al menos un número (0-9)</span>
                    </li>
                    <li data-req="special">
                        <div class="pw-req-bullet"><i class="fa-solid fa-circle-dot"></i></div>
                        <span>Un símbolo especial (!@#$...)</span>
                    </li>
                </ul>

                <div class="pw-note">
                    <i class="fa-solid fa-rotate-left" style="color:var(--color-primary);font-size:13px;flex-shrink:0;margin-top:2px;"></i>
                    <span><strong>Historial:</strong> No puedes reutilizar ninguna de tus <strong>últimas 5 contraseñas</strong> anteriores.</span>
                </div>
            </div>
        </div>

        <!-- Recomendaciones de Seguridad -->
        <div class="card pw-card">
            <div class="pw-card-header">
                <i class="fa-solid fa-shield-halved" style="color:var(--color-primary);"></i>
                <span>Seguridad de la Cuenta</span>
            </div>
            <div class="pw-card-body">
                <div style="font-size:0.78rem;color:var(--color-text-muted);line-height:1.5;display:flex;flex-direction:column;gap:10px;">
                    <div style="display:flex;align-items:flex-start;gap:8px;">
                        <i class="fa-solid fa-check" style="color:var(--color-success);margin-top:3px;font-size:11px;"></i>
                        <span>No utilices contraseñas idénticas a las de tus correos personales o redes sociales.</span>
                    </div>
                    <div style="display:flex;align-items:flex-start;gap:8px;">
                        <i class="fa-solid fa-check" style="color:var(--color-success);margin-top:3px;font-size:11px;"></i>
                        <span>Cierra sesión al terminar tus actividades si compartes equipo de trabajo.</span>
                    </div>
                </div>

                <div style="margin-top:var(--sp-4);border-top:1px solid var(--color-border);padding-top:var(--sp-3);">
                    <a href="<?= APP_URL ?>/perfil/seguridad" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;" data-spa>
                        <i class="fa-solid fa-mobile-screen-button"></i> Administrar Verificación 2FA
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<style>
.pw-view-wrap {
    animation: pageFadeIn 0.3s ease-out;
}
.pw-grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: var(--sp-4);
    align-items: start;
}
.pw-card {
    background: var(--surface-app, var(--color-surface));
    border: 1px solid var(--border-app, var(--color-border));
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-app, none);
    backdrop-filter: var(--backdrop, none);
    overflow: hidden;
}
.pw-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px var(--sp-5);
    font-weight: 700;
    font-size: var(--font-size-sm);
    color: var(--color-text);
    background: var(--accent-app, var(--color-surface-2));
    border-bottom: 1px solid var(--border-app, var(--color-border));
}
.pw-card-body {
    padding: var(--sp-5);
}
.pw-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}
.pw-input-wrapper input {
    padding-right: 44px !important;
    height: 40px;
}
.pw-toggle-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 30px;
    height: 30px;
    background: transparent;
    border: none;
    color: var(--color-text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-sm);
    transition: var(--transition);
}
.pw-toggle-btn:hover {
    color: var(--color-primary);
    background: var(--color-surface-2);
}
.pw-strength-track {
    height: 6px;
    border-radius: 99px;
    background: var(--color-surface-2, #e2e8f0);
    margin-top: 10px;
    overflow: hidden;
    border: 1px solid var(--color-border);
}
.pw-strength-fill {
    height: 100%;
    width: 0%;
    border-radius: 99px;
    background: var(--color-danger);
    transition: width 0.25s ease, background-color 0.25s ease;
}
.pw-strength-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 6px;
    font-size: 11.5px;
}
.pw-strength-title {
    color: var(--color-text-muted);
}
.pw-strength-label {
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.pw-requisitos {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.pw-requisitos li {
    font-size: 12px;
    color: var(--color-text-muted);
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border-radius: var(--radius-md);
    background: var(--color-surface-2);
    border: 1px solid var(--color-border);
    transition: all 0.2s ease;
}
.pw-req-bullet {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 10px;
    color: var(--color-text-light);
    transition: all 0.2s ease;
}
.pw-requisitos li.ok {
    color: var(--color-text);
    background: color-mix(in srgb, var(--color-success) 8%, transparent);
    border-color: color-mix(in srgb, var(--color-success) 28%, transparent);
    font-weight: 600;
}
.pw-requisitos li.ok .pw-req-bullet {
    color: var(--color-success);
    background: color-mix(in srgb, var(--color-success) 20%, transparent);
}
.pw-requisitos li.ok .pw-req-bullet i::before {
    content: "\f058"; /* fa-circle-check */
}
.pw-match-feedback {
    font-size: var(--font-size-xs);
    font-weight: 600;
    margin-top: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: var(--radius-md);
}
.pw-note {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-top: 14px;
    padding: 10px 14px;
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--color-primary) 7%, transparent);
    border: 1px solid color-mix(in srgb, var(--color-primary) 20%, transparent);
    font-size: 11.5px;
    color: var(--color-text-muted);
    line-height: 1.4;
}
@media (max-width: 900px) {
    .pw-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// var, no const/let: esta vista puede re-ejecutar su <script> inline si se
// revisita por SPA-nav (data-spa) sin reload completo -- mismo gotcha ya
// documentado varias veces en este proyecto.
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
        }
        if (reqs[key]) cumplidos++;
    });

    var pct = (cumplidos / 5) * 100;
    pwFill.style.width = pct + '%';
    var color = 'var(--color-danger, #dc3545)', label = 'Muy débil';
    if (cumplidos >= 5) { color = 'var(--color-success, #22c55e)'; label = 'Excelente'; }
    else if (cumplidos >= 4) { color = '#84cc16'; label = 'Fuerte'; }
    else if (cumplidos >= 3) { color = 'var(--color-warning, #f59e0b)'; label = 'Aceptable'; }
    else if (cumplidos >= 1) { color = 'var(--color-danger, #dc3545)'; label = 'Débil'; }
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
            pwMsg.style.background = 'color-mix(in srgb, var(--color-success) 10%, transparent)';
            pwMsg.style.color = 'var(--color-success)';
            pwMsg.style.border = '1px solid color-mix(in srgb, var(--color-success) 28%, transparent)';
            pwMsg.innerHTML = '<i class="fa-solid fa-circle-check"></i> Las contraseñas coinciden correctamente';
        } else {
            pwMsg.style.background = 'color-mix(in srgb, var(--color-danger) 10%, transparent)';
            pwMsg.style.color = 'var(--color-danger)';
            pwMsg.style.border = '1px solid color-mix(in srgb, var(--color-danger) 28%, transparent)';
            pwMsg.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Las contraseñas no coinciden';
        }
    }
    pwBtn.disabled = !(okRequisitos && okMatch && document.getElementById('pass-actual').value.length > 0);
}

pwNueva.addEventListener('input', pwChecarTodo);
pwConfirma.addEventListener('input', pwChecarTodo);
document.getElementById('pass-actual')?.addEventListener('input', pwChecarTodo);
</script>
