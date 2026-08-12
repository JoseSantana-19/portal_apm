<?php
/**
 * PERMISOS.PHP - Vista de Gestión de Permisos (por Rol y por Usuario, Solo Admin)
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
?>
<style>
.perm-tabs { display: flex; gap: 8px; margin-bottom: 16px; }
.perm-tab-btn {
    padding: 10px 20px; border-radius: 10px 10px 0 0; font-size: 13.5px; font-weight: 700;
    border: 1px solid var(--border-color); border-bottom: none; background: var(--secondary-bg);
    color: var(--text-muted); cursor: pointer; transition: all 0.15s;
}
.perm-tab-btn.active { background: var(--panel-bg); color: var(--primary); border-color: var(--primary); }
.perm-tab-pane { display: none; }
.perm-tab-pane.active { display: block; }

.perm-grid { display: grid; grid-template-columns: 300px 1fr; gap: 20px; align-items: start; }
.perm-user-list { display: flex; flex-direction: column; gap: 6px; }
.perm-user-card {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px; border-radius: 12px; cursor: pointer;
    border: 2px solid var(--border-color); background: var(--panel-bg);
    transition: all 0.2s;
}
.perm-user-card:hover { border-color: var(--primary); background: rgba(37,99,235,0.03); }
.perm-user-card.selected { border-color: var(--primary); background: rgba(37,99,235,0.06); }
.perm-avatar {
    width: 42px; height: 42px; border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), #1d4ed8);
    color: white; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 16px; flex-shrink: 0;
}
.perm-avatar.admin { background: linear-gradient(135deg, #f59e0b, #d97706); }
.perm-user-info { flex: 1; min-width: 0; }
.perm-user-info h4 { margin: 0; font-size: 14px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.perm-user-info p  { margin: 0; font-size: 11px; color: var(--text-muted); }
.perm-count-badge {
    padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 700;
    background: var(--primary); color: white; flex-shrink: 0;
}

.perm-section-title {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; color: var(--text-muted);
    padding: 8px 0 4px 0; margin-top: 12px; border-bottom: 1px solid var(--border-color);
    margin-bottom: 8px;
}
.perm-option-row {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px; border-radius: 10px; margin-bottom: 4px;
    border: 1px solid transparent; transition: all 0.15s;
}
.perm-option-row:hover { background: var(--secondary-bg); border-color: var(--border-color); }
.perm-option-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
.perm-option-label { flex: 1; font-size: 13.5px; font-weight: 500; color: var(--text-color); }
.perm-nivel-select {
    font-size: 12.5px; font-weight: 600; padding: 6px 10px; border-radius: 8px;
    border: 1px solid var(--border-color); background: var(--panel-bg); color: var(--text-color);
    min-width: 168px; cursor: pointer;
}
.perm-nivel-select:disabled { opacity: 0.6; cursor: not-allowed; }
</style>

<!-- Cabecera -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1><i class="fa-solid fa-key" style="color:var(--primary);margin-right:10px;"></i>Gestión de Permisos</h1>
        <p>Asigna qué puede Ver, Crear, Editar y Eliminar cada rol y, si hace falta, cada usuario en particular. Solo el Administrador puede modificar estos ajustes.</p>
    </div>
</div>

<div class="perm-tabs animate-fade-in">
    <button class="perm-tab-btn active" id="tab-btn-rol" onclick="cambiarPestana('rol')">
        <i class="fa-solid fa-user-shield"></i> Permisos por Rol
    </button>
    <button class="perm-tab-btn" id="tab-btn-usuario" onclick="cambiarPestana('usuario')">
        <i class="fa-solid fa-user"></i> Permisos por Usuario (excepciones)
    </button>
</div>

<!-- ===================== PESTAÑA: PERMISOS POR ROL ===================== -->
<div class="perm-tab-pane active" id="tab-pane-rol">
    <div class="perm-grid animate-fade-in">
        <div class="panel" style="padding:16px;">
            <h4 style="margin:0 0 14px 0;font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;font-weight:700;">
                <i class="fa-solid fa-shield-halved" style="margin-right:6px;"></i> Roles Nativos
            </h4>
            <div class="perm-user-list">
                <?php foreach ($roles as $rol):
                    $esAdmin = strtolower($rol['nombre']) === 'administrador';
                ?>
                <div class="perm-user-card" id="rol-card-<?= $rol['id'] ?>"
                     onclick="seleccionarRol(<?= $rol['id'] ?>, '<?= htmlspecialchars($rol['nombre'], ENT_QUOTES) ?>')">
                    <div class="perm-avatar <?= $esAdmin ? 'admin' : '' ?>">
                        <?= strtoupper(substr($rol['nombre'], 0, 1)) ?>
                    </div>
                    <div class="perm-user-info">
                        <h4><?= htmlspecialchars($rol['nombre']) ?></h4>
                        <p><?= $esAdmin ? 'Acceso total del sistema' : 'Rol nativo de Bienes' ?></p>
                    </div>
                    <?php if ($esAdmin): ?>
                        <span class="perm-count-badge" style="background:#f59e0b;" title="Acceso total">∞</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="panel" id="panel-rol">
            <div id="rol-placeholder" style="padding:60px;text-align:center;color:var(--text-muted);">
                <i class="fa-solid fa-hand-pointer" style="font-size:40px;display:block;margin-bottom:16px;opacity:0.3;"></i>
                <strong style="display:block;margin-bottom:6px;">Selecciona un rol</strong>
                <span style="font-size:13px;">Los cambios aquí se reflejan también en el sistema central del portal.</span>
            </div>

            <div id="rol-form" style="display:none;">
                <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <h3 id="rol-nombre" style="margin:0;font-size:16px;font-weight:700;"></h3>
                        <p style="margin:0;font-size:12px;color:var(--text-muted);">Nivel por pantalla</p>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn-outline" onclick="marcarTodosNivel('rol', 4)" style="font-size:13px;padding:8px 16px;">
                            <i class="fa-solid fa-check-double"></i> Total a todo
                        </button>
                        <button class="btn-outline" onclick="marcarTodosNivel('rol', 0)" style="font-size:13px;padding:8px 16px;">
                            <i class="fa-solid fa-xmark"></i> Quitar todo
                        </button>
                        <button class="btn-primary" onclick="guardarRol()" id="btn-guardar-rol" style="padding:8px 20px;">
                            <i class="fa-solid fa-save"></i> Guardar
                        </button>
                    </div>
                </div>

                <div id="rol-admin-notice" style="display:none;padding:20px;background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.2);margin:16px;border-radius:12px;">
                    <i class="fa-solid fa-crown" style="color:#f59e0b;margin-right:8px;"></i>
                    <strong style="color:#f59e0b;">Rol Administrador</strong>
                    <p style="margin:4px 0 0 0;font-size:13px;color:var(--text-muted);">Este rol tiene acceso total al sistema por definición. Los niveles individuales no aplican.</p>
                </div>

                <div style="padding:16px 20px;" id="rol-selects">
                    <?php foreach ($rutas as $seccion => $secInfo): ?>
                    <div class="perm-section-title"><?= htmlspecialchars($secInfo['titulo']) ?></div>
                    <?php
                    $secColores = ['operaciones' => '#3b82f6', 'datos' => '#10b981', 'sistema' => '#8b5cf6'];
                    $secColor   = $secColores[$seccion] ?? 'var(--primary)';
                    foreach ($secInfo['items'] as $rk => $rInfo): ?>
                    <div class="perm-option-row">
                        <div class="perm-option-icon" style="background:<?= $secColor ?>18;color:<?= $secColor ?>;">
                            <i class="fa-solid <?= $rInfo['icon'] ?>"></i>
                        </div>
                        <label class="perm-option-label" for="rol-nivel-<?= $rk ?>"><?= htmlspecialchars($rInfo['label']) ?></label>
                        <select class="perm-nivel-select rol-nivel" id="rol-nivel-<?= $rk ?>" data-route="<?= $rk ?>">
                            <option value="0">Sin acceso</option>
                            <option value="1">Ver</option>
                            <option value="2">Ver + Crear</option>
                            <option value="3">Ver + Crear + Editar</option>
                            <option value="4">Total (+ Eliminar)</option>
                        </select>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===================== PESTAÑA: PERMISOS POR USUARIO ===================== -->
<div class="perm-tab-pane" id="tab-pane-usuario">
    <div class="perm-grid animate-fade-in">
        <div class="panel" style="padding:16px;">
            <h4 style="margin:0 0 14px 0;font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;font-weight:700;">
                <i class="fa-solid fa-users" style="margin-right:6px;"></i> Usuarios del Sistema
            </h4>
            <div class="perm-user-list">
                <?php foreach ($usuarios as $usr):
                    $pCount = isset($nivelesPorUsuario[$usr['id']]) ? count($nivelesPorUsuario[$usr['id']]) : 0;
                    $esAdmin = strtolower($usr['rol']) === 'administrador';
                ?>
                <div class="perm-user-card"
                     id="user-card-<?= $usr['id'] ?>"
                     onclick="seleccionarUsuario(<?= $usr['id'] ?>, '<?= htmlspecialchars($usr['nombre'], ENT_QUOTES) ?>', '<?= htmlspecialchars($usr['rol'], ENT_QUOTES) ?>')">
                    <div class="perm-avatar <?= $esAdmin ? 'admin' : '' ?>">
                        <?= strtoupper(substr($usr['nombre'], 0, 1)) ?>
                    </div>
                    <div class="perm-user-info">
                        <h4><?= htmlspecialchars($usr['nombre']) ?></h4>
                        <p><?= htmlspecialchars($usr['usuario'] ?? '') ?> &bull; <?= htmlspecialchars($usr['rol']) ?></p>
                    </div>
                    <?php if ($esAdmin): ?>
                        <span class="perm-count-badge" style="background:#f59e0b;" title="Acceso total">∞</span>
                    <?php else: ?>
                        <span class="perm-count-badge" id="badge-<?= $usr['id'] ?>"><?= $pCount ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="panel" id="panel-usuario">
            <div id="usuario-placeholder" style="padding:60px;text-align:center;color:var(--text-muted);">
                <i class="fa-solid fa-hand-pointer" style="font-size:40px;display:block;margin-bottom:16px;opacity:0.3;"></i>
                <strong style="display:block;margin-bottom:6px;">Selecciona un usuario</strong>
                <span style="font-size:13px;">Solo hace falta tocar esto si un usuario necesita una excepción distinta a lo que ya le da su rol. "Sin acceso" aquí significa "usar lo que dice el rol", no un bloqueo.</span>
            </div>

            <div id="usuario-form" style="display:none;">
                <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <h3 id="usuario-nombre" style="margin:0;font-size:16px;font-weight:700;"></h3>
                        <p id="usuario-rol" style="margin:0;font-size:12px;color:var(--text-muted);"></p>
                    </div>
                    <div style="display:flex;gap:8px;">
                        <button class="btn-outline" onclick="marcarTodosNivel('usuario', 4)" style="font-size:13px;padding:8px 16px;">
                            <i class="fa-solid fa-check-double"></i> Total a todo
                        </button>
                        <button class="btn-outline" onclick="marcarTodosNivel('usuario', 0)" style="font-size:13px;padding:8px 16px;">
                            <i class="fa-solid fa-xmark"></i> Quitar excepciones
                        </button>
                        <button class="btn-primary" onclick="guardarUsuario()" id="btn-guardar-usuario" style="padding:8px 20px;">
                            <i class="fa-solid fa-save"></i> Guardar
                        </button>
                    </div>
                </div>

                <div id="usuario-admin-notice" style="display:none;padding:20px;background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.2);margin:16px;border-radius:12px;">
                    <i class="fa-solid fa-crown" style="color:#f59e0b;margin-right:8px;"></i>
                    <strong style="color:#f59e0b;">Usuario Administrador</strong>
                    <p style="margin:4px 0 0 0;font-size:13px;color:var(--text-muted);">Los Administradores tienen acceso total al sistema. Las excepciones individuales no aplican para este rol.</p>
                </div>

                <div style="padding:16px 20px;" id="usuario-selects">
                    <?php foreach ($rutas as $seccion => $secInfo): ?>
                    <div class="perm-section-title"><?= htmlspecialchars($secInfo['titulo']) ?></div>
                    <?php foreach ($secInfo['items'] as $rk => $rInfo): ?>
                    <div class="perm-option-row">
                        <div class="perm-option-icon" style="background:var(--secondary-bg);color:var(--text-muted);">
                            <i class="fa-solid <?= $rInfo['icon'] ?>"></i>
                        </div>
                        <label class="perm-option-label" for="usuario-nivel-<?= $rk ?>"><?= htmlspecialchars($rInfo['label']) ?></label>
                        <select class="perm-nivel-select usuario-nivel" id="usuario-nivel-<?= $rk ?>" data-route="<?= $rk ?>">
                            <option value="0">Usar lo del rol</option>
                            <option value="1">Ver</option>
                            <option value="2">Ver + Crear</option>
                            <option value="3">Ver + Crear + Editar</option>
                            <option value="4">Total (+ Eliminar)</option>
                        </select>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var _rolSeleccionado = 0;
var _rolEsAdmin = false;
var _usuarioSeleccionado = 0;
var _usuarioEsAdmin = false;

// Niveles pre-cargados desde PHP: {id: {route_key: nivel}}
var _nivelesPorRol = <?= json_encode($nivelesPorRol) ?>;
var _nivelesPorUsuario = <?= json_encode($nivelesPorUsuario) ?>;

function cambiarPestana(nombre) {
    document.getElementById('tab-btn-rol').classList.toggle('active', nombre === 'rol');
    document.getElementById('tab-btn-usuario').classList.toggle('active', nombre === 'usuario');
    document.getElementById('tab-pane-rol').classList.toggle('active', nombre === 'rol');
    document.getElementById('tab-pane-usuario').classList.toggle('active', nombre === 'usuario');
}

function seleccionarRol(id, nombre) {
    document.querySelectorAll('#tab-pane-rol .perm-user-card').forEach(function(c){ c.classList.remove('selected'); });
    document.getElementById('rol-card-' + id).classList.add('selected');

    _rolSeleccionado = id;
    _rolEsAdmin = (nombre.toLowerCase() === 'administrador');

    document.getElementById('rol-nombre').textContent = nombre;
    document.getElementById('rol-admin-notice').style.display = _rolEsAdmin ? 'block' : 'none';

    var niveles = _nivelesPorRol[id] || {};
    document.querySelectorAll('.rol-nivel').forEach(function(sel) {
        sel.value = _rolEsAdmin ? '4' : String(niveles[sel.dataset.route] || 0);
        sel.disabled = _rolEsAdmin;
    });

    document.getElementById('rol-placeholder').style.display = 'none';
    document.getElementById('rol-form').style.display = 'block';
    document.getElementById('btn-guardar-rol').disabled = _rolEsAdmin;
}

function seleccionarUsuario(id, nombre, rol) {
    document.querySelectorAll('#tab-pane-usuario .perm-user-card').forEach(function(c){ c.classList.remove('selected'); });
    document.getElementById('user-card-' + id).classList.add('selected');

    _usuarioSeleccionado = id;
    _usuarioEsAdmin = (rol.toLowerCase() === 'administrador');

    document.getElementById('usuario-nombre').textContent = nombre;
    document.getElementById('usuario-rol').textContent    = 'Rol: ' + rol;
    document.getElementById('usuario-admin-notice').style.display = _usuarioEsAdmin ? 'block' : 'none';

    var niveles = _nivelesPorUsuario[id] || {};
    document.querySelectorAll('.usuario-nivel').forEach(function(sel) {
        sel.value = _usuarioEsAdmin ? '4' : String(niveles[sel.dataset.route] || 0);
        sel.disabled = _usuarioEsAdmin;
    });

    document.getElementById('usuario-placeholder').style.display = 'none';
    document.getElementById('usuario-form').style.display = 'block';
    document.getElementById('btn-guardar-usuario').disabled = _usuarioEsAdmin;
}

function marcarTodosNivel(cual, nivel) {
    if (cual === 'rol') {
        if (_rolEsAdmin) return;
        document.querySelectorAll('.rol-nivel').forEach(function(s){ s.value = String(nivel); });
    } else {
        if (_usuarioEsAdmin) return;
        document.querySelectorAll('.usuario-nivel').forEach(function(s){ s.value = String(nivel); });
    }
}

function guardarRol() {
    if (!_rolSeleccionado || _rolEsAdmin) return;

    var btn = document.getElementById('btn-guardar-rol');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    var data = new FormData();
    data.append('rol_id', _rolSeleccionado);
    data.append('is_ajax', '1');
    var nuevosNiveles = {};
    document.querySelectorAll('.rol-nivel').forEach(function(sel) {
        var nivel = parseInt(sel.value, 10);
        if (nivel > 0) {
            data.append('niveles[' + sel.dataset.route + ']', nivel);
            nuevosNiveles[sel.dataset.route] = nivel;
        }
    });

    fetch('index.php?route=inv_permisos_rol', { method: 'POST', body: data })
        .then(function(r){ return r.json(); })
        .then(function(res) {
            if (res.success) {
                _nivelesPorRol[_rolSeleccionado] = nuevosNiveles;
                btn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Guardado';
                btn.style.background = '#10b981';
                setTimeout(function() {
                    btn.innerHTML = '<i class="fa-solid fa-save"></i> Guardar';
                    btn.style.background = '';
                    btn.disabled = false;
                }, 2000);
            } else {
                alert('Error: ' + (res.mensaje || 'No se pudo guardar'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-save"></i> Guardar';
            }
        })
        .catch(function() {
            alert('Error de conexión al guardar permisos de rol.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-save"></i> Guardar';
        });
}

function guardarUsuario() {
    if (!_usuarioSeleccionado || _usuarioEsAdmin) return;

    var btn = document.getElementById('btn-guardar-usuario');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    var data = new FormData();
    data.append('usuario_id', _usuarioSeleccionado);
    data.append('is_ajax', '1');
    var nuevosNiveles = {};
    document.querySelectorAll('.usuario-nivel').forEach(function(sel) {
        var nivel = parseInt(sel.value, 10);
        if (nivel > 0) {
            data.append('niveles[' + sel.dataset.route + ']', nivel);
            nuevosNiveles[sel.dataset.route] = nivel;
        }
    });

    fetch('index.php?route=inv_permisos&action=guardar', { method: 'POST', body: data })
        .then(function(r){ return r.json(); })
        .then(function(res) {
            if (res.success) {
                _nivelesPorUsuario[_usuarioSeleccionado] = nuevosNiveles;
                var badge = document.getElementById('badge-' + _usuarioSeleccionado);
                if (badge) badge.textContent = Object.keys(nuevosNiveles).length;

                btn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Guardado';
                btn.style.background = '#10b981';
                setTimeout(function() {
                    btn.innerHTML = '<i class="fa-solid fa-save"></i> Guardar';
                    btn.style.background = '';
                    btn.disabled = false;
                }, 2000);
            } else {
                alert('Error: ' + (res.mensaje || 'No se pudo guardar'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-save"></i> Guardar';
            }
        })
        .catch(function() {
            alert('Error de conexión al guardar permisos de usuario.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-save"></i> Guardar';
        });
}
</script>
