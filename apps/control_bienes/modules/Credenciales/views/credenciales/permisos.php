<?php
/**
 * PERMISOS.PHP - Vista de Gestión de Permisos por InvUsuario (Solo Admin)
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
?>
<style>
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
.perm-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary); }
.perm-option-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
.perm-option-label { flex: 1; font-size: 13.5px; font-weight: 500; color: var(--text-color); }
.perm-select-all-bar {
    display: flex; gap: 8px; padding: 10px 14px; background: var(--secondary-bg);
    border-bottom: 1px solid var(--border-color); border-radius: 8px 8px 0 0;
    margin-bottom: 8px;
}
</style>

<!-- InvCabecera -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1><i class="fa-solid fa-key" style="color:var(--primary);margin-right:10px;"></i>Gestión de Permisos</h1>
        <p>Asigna qué opciones del menú puede acceder cada usuario del sistema. Solo el Administrador puede modificar estos ajustes.</p>
    </div>
</div>

<div class="perm-grid animate-fade-in">
    <!-- Panel Izquierdo: Lista de Usuarios -->
    <div class="panel" style="padding:16px;">
        <h4 style="margin:0 0 14px 0;font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.08em;font-weight:700;">
            <i class="fa-solid fa-users" style="margin-right:6px;"></i> Usuarios del Sistema
        </h4>
        <div class="perm-user-list">
            <?php foreach ($usuarios as $usr):
                $pCount = isset($permisosPorUsuario[$usr['id']]) ? count($permisosPorUsuario[$usr['id']]) : 0;
                $esAdmin = strtolower($usr['rol']) === 'administrador';
            ?>
            <div class="perm-user-card <?= $esAdmin ? '' : '' ?>"
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

    <!-- Panel Derecho: Configuración de Permisos -->
    <div class="panel" id="panel-inv_permisos">
        <!-- Estado inicial: ningún usuario seleccionado -->
        <div id="inv_permisos-placeholder" style="padding:60px;text-align:center;color:var(--text-muted);">
            <i class="fa-solid fa-hand-pointer" style="font-size:40px;display:block;margin-bottom:16px;opacity:0.3;"></i>
            <strong style="display:block;margin-bottom:6px;">Selecciona un usuario</strong>
            <span style="font-size:13px;">Haz clic en un usuario del panel izquierdo para configurar sus inv_permisos de acceso.</span>
        </div>

        <!-- Formulario de inv_permisos (visible tras seleccionar usuario) -->
        <div id="inv_permisos-form" style="display:none;">
            <div class="panel-header" style="display:flex;align-items:center;justify-content:space-between;">
                <div>
                    <h3 id="inv_permisos-user-nombre" style="margin:0;font-size:16px;font-weight:700;"></h3>
                    <p id="inv_permisos-user-rol" style="margin:0;font-size:12px;color:var(--text-muted);"></p>
                </div>
                <div style="display:flex;gap:8px;">
                    <button class="btn-outline" onclick="marcarTodos(true)" style="font-size:13px;padding:8px 16px;">
                        <i class="fa-solid fa-check-double"></i> Seleccionar Todo
                    </button>
                    <button class="btn-outline" onclick="marcarTodos(false)" style="font-size:13px;padding:8px 16px;">
                        <i class="fa-solid fa-xmark"></i> Quitar Todo
                    </button>
                    <button class="btn-primary" onclick="guardarPermisos()" id="btn-guardar-inv_permisos" style="padding:8px 20px;">
                        <i class="fa-solid fa-save"></i> Guardar
                    </button>
                </div>
            </div>

            <div id="admin-notice" style="display:none;padding:20px;background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.2);margin:16px;border-radius:12px;">
                <i class="fa-solid fa-crown" style="color:#f59e0b;margin-right:8px;"></i>
                <strong style="color:#f59e0b;">Usuario Administrador</strong>
                <p style="margin:4px 0 0 0;font-size:13px;color:var(--text-muted);">Los Administradores tienen acceso total al sistema. Los inv_permisos individuales no aplican para este rol.</p>
            </div>

            <form id="form-inv_permisos" action="index.php?route=inv_permisos&action=guardar" method="POST">
                <input type="hidden" name="usuario_id" id="perm-usuario-id" value="">
                <input type="hidden" name="is_ajax" value="1">

                <div style="padding:16px 20px;" id="inv_permisos-checkboxes">
                    <?php foreach ($rutas as $seccion => $secInfo): ?>
                    <div class="perm-section-title"><?= htmlspecialchars($secInfo['titulo']) ?></div>
                    <?php
                    $secColores = ['operaciones' => '#3b82f6', 'datos' => '#10b981', 'sistema' => '#8b5cf6'];
                    $secColor   = $secColores[$seccion] ?? 'var(--primary)';
                    foreach ($secInfo['items'] as $rk => $rInfo): ?>
                    <div class="perm-option-row">
                        <input type="checkbox" class="perm-checkbox perm-check"
                               name="inv_permisos[]" value="<?= $rk ?>"
                               id="chk-<?= $rk ?>">
                        <div class="perm-option-icon" style="background:<?= $secColor ?>18;color:<?= $secColor ?>;">
                            <i class="fa-solid <?= $rInfo['icon'] ?>"></i>
                        </div>
                        <label class="perm-option-label" for="chk-<?= $rk ?>"><?= htmlspecialchars($rInfo['label']) ?></label>
                        <code style="font-size:11px;color:var(--text-muted);background:var(--border-color);padding:2px 6px;border-radius:4px;"><?= $rk ?></code>
                    </div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var _usuarioSeleccionado = 0;
var _esAdmin = false;

// Permisos pre-cargados desde PHP
var _permisosPorUsuario = <?= json_encode($permisosPorUsuario) ?>;

function seleccionarUsuario(id, nombre, rol) {
    // Resaltar la tarjeta
    document.querySelectorAll('.perm-user-card').forEach(function(c){ c.classList.remove('selected'); });
    document.getElementById('user-card-' + id).classList.add('selected');

    _usuarioSeleccionado = id;
    _esAdmin = (rol.toLowerCase() === 'administrador');

    // Actualizar encabezado
    document.getElementById('inv_permisos-user-nombre').textContent = nombre;
    document.getElementById('inv_permisos-user-rol').textContent    = 'Rol: ' + rol;
    document.getElementById('perm-usuario-id').value = id;

    // Mostrar/ocultar aviso de admin
    document.getElementById('admin-notice').style.display = _esAdmin ? 'block' : 'none';

    // Marcar checkboxes según inv_permisos actuales
    var permActuales = _permisosPorUsuario[id] || [];
    document.querySelectorAll('.perm-check').forEach(function(chk) {
        chk.checked = _esAdmin || permActuales.indexOf(chk.value) !== -1;
        chk.disabled = _esAdmin; // Admin no se puede editar
    });

    // Mostrar formulario
    document.getElementById('inv_permisos-placeholder').style.display = 'none';
    document.getElementById('inv_permisos-form').style.display        = 'block';
    document.getElementById('btn-guardar-inv_permisos').disabled      = _esAdmin;
}

function marcarTodos(estado) {
    if (_esAdmin) return;
    document.querySelectorAll('.perm-check').forEach(function(c){ c.checked = estado; });
}

function guardarPermisos() {
    if (!_usuarioSeleccionado || _esAdmin) return;

    var btn = document.getElementById('btn-guardar-inv_permisos');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

    var form = document.getElementById('form-inv_permisos');
    var data = new FormData(form);

    fetch('index.php?route=inv_permisos&action=guardar', {
        method: 'POST',
        body: data
    })
    .then(function(r){ return r.json(); })
    .then(function(res) {
        if (res.success) {
            // Actualizar cache local
            var nuevos = [];
            document.querySelectorAll('.perm-check:checked').forEach(function(c){ nuevos.push(c.value); });
            _permisosPorUsuario[_usuarioSeleccionado] = nuevos;

            // Actualizar badge del usuario
            var badge = document.getElementById('badge-' + _usuarioSeleccionado);
            if (badge) badge.textContent = nuevos.length;

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
        alert('Error de conexión al guardar inv_permisos.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-save"></i> Guardar';
    });
}
</script>
