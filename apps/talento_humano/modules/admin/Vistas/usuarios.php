<?php /* usuarios.php – Vista: Gestión de Usuarios del Sistema */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios | Administración – APM</title>
    <meta name="description" content="Gestión de cuentas de acceso al Sistema de Talento Humano de la APM.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/toast.css">
    <style>
        .estado-activo   { background:rgba(16,185,129,.12); color:#059669; border:1px solid rgba(16,185,129,.25); }
        .estado-inactivo { background:rgba(107,114,128,.1);  color:#4b5563; border:1px solid rgba(107,114,128,.2); }
        .estado-pill { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:999px; font-size:.78rem; font-weight:600; }
        .rol-pill { padding:3px 10px; border-radius:8px; font-size:.75rem; font-weight:600; display:inline-block; }
        .rol-superadmin { background:rgba(239,68,68,.12);  color:#dc2626; border:1px solid rgba(239,68,68,.2); }
        .rol-admin      { background:rgba(99,102,241,.12);  color:#4338ca; border:1px solid rgba(99,102,241,.2); }
        .rol-analista   { background:rgba(16,180,199,.12);  color:var(--ocean-700); border:1px solid rgba(16,180,199,.2); }
        .rol-consultor  { background:rgba(16,185,129,.12);  color:#059669; border:1px solid rgba(16,185,129,.2); }
        .rol-supervisor { background:rgba(245,158,11,.12);  color:#b45309; border:1px solid rgba(245,158,11,.2); }
        .user-avatar-sm { width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg,var(--navy-900),var(--ocean-600)); color:#fff; display:grid; place-items:center; font-weight:700; font-size:.82rem; flex-shrink:0; }
        .modal-overlay { position:fixed; inset:0; background:rgba(10,19,30,.55); backdrop-filter:blur(4px); z-index:100; display:none; align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#fff; border-radius:var(--radius-lg); padding:28px; max-width:520px; width:90%; box-shadow:var(--shadow-lg); animation:floatIn .3s ease both; }
        .form-field { margin-bottom:14px; }
        .form-field label { display:block; font-size:.83rem; font-weight:600; color:var(--navy-900); margin-bottom:6px; }
        .form-field input, .form-field select { width:100%; padding:11px 14px; border:1px solid var(--line); border-radius:10px; font-size:.88rem; outline:none; background:#fff; transition:border .2s; }
        .form-field input:focus, .form-field select:focus { border-color:var(--teal-500); box-shadow:0 0 0 3px rgba(18,180,199,.15); }
        .metric-card--total    { border-left:4px solid var(--ocean-700); }
        .metric-card--activos2 { border-left:4px solid #10b981; }
        .metric-card--inact    { border-left:4px solid #6b7280; }
    </style>
</head>
<body>
<div id="overlay" class="overlay" onclick="closeSidebar()"></div>
<button class="sidebar-open-btn" id="sidebarOpenBtn" onclick="openSidebar()" title="Abrir menú lateral" aria-label="Abrir menú lateral">
    <i class="bi bi-layout-sidebar"></i>
</button>

<!-- MODAL: CREAR NUEVO USUARIO (con buscador inteligente y autocompletado) -->
<div class="modal-overlay" id="modalUser">
    <div class="modal-box" style="max-width:640px; padding:0; overflow:hidden;">
        <div style="background:linear-gradient(135deg,var(--navy-900),var(--ocean-700)); padding:22px 28px; color:#fff;">
            <h3 style="margin:0 0 4px; color:#fff; font-size:1.1rem;"><i class="bi bi-person-plus-fill"></i> Crear Nueva Cuenta de Acceso</h3>
            <p style="margin:0; font-size:.82rem; opacity:.7;">El funcionario debe estar previamente registrado en el Directorio de Personal.</p>
        </div>
        <div style="padding:24px 28px;">
            <form id="formUser" onsubmit="return guardarUser(event)">

                <!-- PASO 1: BÚSqueda del empleado -->
                <h6 style="font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:var(--ocean-700); border-bottom:1px solid #e2e8f0; padding-bottom:8px; margin-bottom:14px;">
                    <i class="bi bi-search"></i> 1. Seleccionar Funcionario del Directorio
                </h6>
                <div style="background:#f0f9ff; border:1px solid rgba(16,180,199,.25); border-radius:12px; padding:14px 16px; margin-bottom:20px;">
                    <label style="display:block; font-size:.83rem; font-weight:600; color:var(--navy-900); margin-bottom:8px;">
                        Buscar por Cédula o Nombre
                    </label>
                    <input type="text"
                           id="buscadorEmpleadoUsuario"
                           list="listaDirectorioUsuarios"
                           onchange="autocompletarDatosUsuario()"
                           oninput="autocompletarDatosUsuario()"
                           autocomplete="off"
                           placeholder="Ej: Escriba 1308... o el apellido..."
                           style="width:100%; box-sizing:border-box; padding:10px 14px; border:1.5px solid rgba(16,180,199,.4); border-radius:10px; font-size:.88rem; outline:none; transition:border-color .2s;"
                           onfocus="this.style.borderColor='var(--teal-500)'"
                           onblur="this.style.borderColor='rgba(16,180,199,.4)'">
                    <datalist id="listaDirectorioUsuarios">
                        <option value="1308126646">ZAMBRANO DELGADO HECTOR FERNANDO</option>
                        <option value="1311567890">PEREZ MORALES JUAN CARLOS</option>
                        <option value="0923456781">TORRES VEGA ANA MARIA</option>
                        <option value="1309876543">PALMA TEJENA MICHAEL</option>
                    </datalist>
                    <div style="font-size:.75rem; color:var(--ink-600); margin-top:6px;">
                        <i class="bi bi-keyboard"></i> Escriba para filtrar automáticamente.
                    </div>
                </div>

                <!-- PASO 2: Configuración de credenciales -->
                <h6 style="font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.12em; color:var(--ocean-700); border-bottom:1px solid #e2e8f0; padding-bottom:8px; margin-bottom:14px;">
                    <i class="bi bi-shield-lock"></i> 2. Configuración de Credenciales
                </h6>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                    <div class="form-field" style="margin:0;">
                        <label>Nombre del Empleado</label>
                        <input type="text" id="usNombreDisplay" readonly placeholder="Se llenará automáticamente..."
                               style="background:#f8fafc; color:#64748b; cursor:default;">
                    </div>
                    <div class="form-field" style="margin:0;">
                        <label>Correo Institucional</label>
                        <input type="email" id="usCorreoDisplay" name="correo" readonly placeholder="@apm.gob.ec"
                               style="background:#f8fafc; color:#64748b; cursor:default;">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; margin-bottom:14px;">
                    <div class="form-field" style="margin:0;">
                        <label>Usuario Sugerido <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="u-usuario" name="username" required
                               placeholder="Ej: hzambrano"
                               pattern="[a-z0-9._]+" title="Solo minúsculas, números, puntos y guiones bajos"
                               style="font-weight:700; color:var(--ocean-700);">
                    </div>
                    <div class="form-field" style="margin:0;">
                        <label>Rol del Sistema <span style="color:#ef4444;">*</span></label>
                        <select id="u-rol" name="rol_id" required>
                            <option value="">Seleccione el rol...</option>
                            <option value="1">Super Administrador</option>
                            <option value="2">Administrador TH</option>
                            <option value="3">Analista RRHH</option>
                            <option value="4">Consultor (Solo lectura)</option>
                            <option value="5">Supervisor</option>
                        </select>
                    </div>
                    <div class="form-field" style="margin:0;">
                        <label>Contraseña Temporal</label>
                        <div style="display:flex; gap:6px;">
                            <input type="text" id="u-pass" name="password" value="Apm.2026*" readonly
                                   style="background:#f8fafc; color:#64748b; flex:1; min-width:0;">
                            <button class="btn btn-ghost" type="button" title="Generar clave aleatoria"
                                    onclick="generarClaveAleatoria()"
                                    style="padding:0 10px; flex-shrink:0;">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:12px 14px; margin-bottom:4px; font-size:.83rem; color:#1d4ed8;">
                    <i class="bi bi-envelope-check-fill"></i>
                    Se enviará un correo automáticamente al funcionario con sus credenciales de acceso al sistema.
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px;">
                    <button type="button" class="btn btn-ghost" onclick="cerrarUser()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Crear Cuenta de Acceso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="app">
    <?php require_once ROOT . '/shared/menu.php'; ?>

    <section class="content">
        <header class="topbar">
            <div class="topbar-left">
                <div class="brand">
                    <img src="<?= LOGO_URL ?>/logoapm.png" alt="Logo APM">
                    <div>
                        <h1>Autoridad Portuaria de Manta</h1>
                        <p>Administración y Seguridad</p>
                    </div>
                </div>
            </div>
            <div class="topbar-actions">
                <div class="icon-chip"><i class="bi bi-calendar-event"></i><span id="currentDate">--</span></div>
                <div class="user-pill"><span><?= htmlspecialchars($usuarioNombre ?? 'Administrador') ?></span><small>APM</small></div>
            </div>
        </header>

        <main class="main">
            <div class="content-shell">

                <!-- HERO -->
                <section class="hero" id="hero-usuarios">
                    <div>
                        <div class="hero-kicker">Administración · Acceso al Sistema</div>
                        <h2>Gestión de Usuarios</h2>
                        <p>Crea y administra las cuentas de acceso al Portal Portuario. Solo los funcionarios designados como administrativos deben tener acceso.</p>
                        <div class="hero-actions">
                            <button class="btn btn-primary" id="btn-nuevo-usuario" onclick="abrirUser()">
                                <i class="bi bi-person-plus-fill"></i> Nuevo Usuario
                            </button>
                            <a href="<?= BASE_URL ?>/admin/roles" class="btn btn-outline" id="btn-ir-roles">
                                <i class="bi bi-shield-shaded"></i> Gestionar Roles
                            </a>
                        </div>
                    </div>
                    <div class="metrics" style="grid-template-columns:repeat(3,1fr);">
                        <div class="metric-card metric-card--total">
                            <div class="metric-label"><i class="bi bi-people"></i> Total usuarios</div>
                            <div class="metric-value"><?= $total ?></div>
                            <div class="metric-foot">Cuentas registradas</div>
                        </div>
                        <div class="metric-card metric-card--activos2">
                            <div class="metric-label"><i class="bi bi-person-check"></i> Activos</div>
                            <div class="metric-value"><?= $activos ?></div>
                            <div class="metric-foot">Con acceso habilitado</div>
                        </div>
                        <div class="metric-card metric-card--inact">
                            <div class="metric-label"><i class="bi bi-person-slash"></i> Inactivos</div>
                            <div class="metric-value"><?= $inactivos ?></div>
                            <div class="metric-foot">Acceso suspendido</div>
                        </div>
                    </div>
                </section>

                <!-- TABLA USUARIOS -->
                <section class="card table-card" id="seccion-usuarios">
                    <div class="card-header">
                        <div>
                            <h3><i class="bi bi-people-fill"></i> Usuarios del sistema</h3>
                            <p>Lista de cuentas de acceso. Vincule cada usuario a un empleado de la institución.</p>
                        </div>
                        <span class="chip"><i class="bi bi-shield-lock"></i> <?= $total ?> cuentas</span>
                    </div>
                    <div class="toolbar">
                        <div class="input search-input">
                            <i class="bi bi-search"></i>
                            <input type="text" id="userSearch" oninput="filtrarUsuarios()" placeholder="Buscar por usuario, nombre o rol...">
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table id="tablaUsuarios">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Empleado vinculado</th>
                                    <th>Correo institucional</th>
                                    <th>Rol</th>
                                    <th>Último acceso</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody">
                            <?php foreach ($usuarios as $u): ?>
                                <?php
                                $iniciales = implode('', array_map(fn($p) => strtoupper(substr($p,0,1)), array_slice(explode(' ', $u['nombre']), 0, 2)));
                                $rolCls = match($u['rol']) {
                                    'Super Administrador' => 'rol-superadmin',
                                    'Administrador TH'    => 'rol-admin',
                                    'Analista RRHH'       => 'rol-analista',
                                    'Consultor'           => 'rol-consultor',
                                    'Supervisor'          => 'rol-supervisor',
                                    default               => ''
                                };
                                ?>
                                <tr class="table-row" data-search="<?= strtolower($u['usuario'].' '.$u['nombre'].' '.$u['rol']) ?>">
                                    <td>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div class="user-avatar-sm"><?= $iniciales ?></div>
                                            <div>
                                                <div style="font-weight:700; font-size:.88rem; font-family:monospace;"><?= htmlspecialchars($u['usuario']) ?></div>
                                                <small style="color:var(--ink-600);">ID #<?= $u['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($u['empleado_id']): ?>
                                            <span style="font-size:.85rem; color:var(--navy-900);"><?= htmlspecialchars($u['nombre']) ?></span>
                                        <?php else: ?>
                                            <span style="font-size:.82rem; color:var(--ink-600); font-style:italic;">Cuenta de sistema</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:.83rem; color:var(--ink-600);"><?= htmlspecialchars($u['email']) ?></td>
                                    <td><span class="rol-pill <?= $rolCls ?>"><?= htmlspecialchars($u['rol']) ?></span></td>
                                    <td style="font-size:.8rem; color:var(--ink-600);"><?= $u['ultimo_acceso'] ?></td>
                                    <td>
                                        <span class="estado-pill <?= $u['estado'] === 'Activo' ? 'estado-activo' : 'estado-inactivo' ?>"
                                              id="badge_estado_<?= $u['id'] ?>">
                                            <i class="bi <?= $u['estado'] === 'Activo' ? 'bi-check-circle' : 'bi-x-circle' ?>"></i>
                                            <?= $u['estado'] ?>
                                        </span>
                                    </td>
                                    <td class="action-cell">
                                        <button class="btn btn-outline" style="padding:6px 10px; font-size:.8rem;" onclick="showToast('Editando usuario: <?= addslashes($u['usuario']) ?>', 'info')" title="Editar usuario">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <!-- Toggle de estado dinámico -->
                                        <div class="form-check form-switch" style="display:inline-flex; align-items:center; margin:0 4px;" title="<?= $u['estado'] === 'Activo' ? 'Desactivar' : 'Activar' ?>">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                   style="cursor:pointer; width:2rem; height:1rem;"
                                                   id="toggle_<?= $u['id'] ?>"
                                                   <?= $u['estado'] === 'Activo' ? 'checked' : '' ?>
                                                   onchange="cambiarEstadoUsuario(this, <?= $u['id'] ?>, '<?= addslashes($u['usuario']) ?>')">
                                        </div>
                                        <!-- Botón resetear contraseña -->
                                        <button class="btn btn-ghost" style="padding:6px 10px; font-size:.8rem;"
                                                onclick="resetearCredenciales('<?= addslashes($u['usuario']) ?>')"
                                                title="Restablecer contraseña">
                                            <i class="bi bi-key"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            </div>
        </main>
    </section>
</div>

<div id="toastContainer" class="toast-container"></div>
<script src="<?= BASE_URL ?>/public/js/layout_sidebar.js"></script>
<script src="<?= BASE_URL ?>/public/js/toast.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('currentDate');
    if (el) el.textContent = new Date().toLocaleDateString('es-EC', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
});
function abrirUser() { document.getElementById('modalUser').classList.add('open'); }
function cerrarUser() {
    document.getElementById('modalUser').classList.remove('open');
    document.getElementById('formUser').reset();
    document.getElementById('usNombreDisplay').value = '';
    document.getElementById('usCorreoDisplay').value = '';
    document.getElementById('buscadorEmpleadoUsuario').value = '';
}
function guardarUser(e) {
    e.preventDefault();
    const nombre = document.getElementById('usNombreDisplay').value;
    const usuario = document.getElementById('u-usuario').value;
    const rol = document.getElementById('u-rol').value;
    if (!nombre) { showToast('Busque y seleccione un funcionario primero.', 'error'); return false; }
    if (!rol) { showToast('Seleccione un rol para el usuario.', 'error'); return false; }
    showToast(`✅ Cuenta de acceso creada para ${nombre} (${usuario}).`, 'success');
    cerrarUser();
    return false;
}

/* ── Directorio simulado para autocompletado (Modo Prototipo) ────────── */
const directorioMock = {
    '1308126646': { nombres: 'ZAMBRANO DELGADO HECTOR FERNANDO', correo: 'h.zambrano@apm.gob.ec', username: 'hzambrano' },
    '1311567890': { nombres: 'PEREZ MORALES JUAN CARLOS',        correo: 'j.perez@apm.gob.ec',       username: 'jperez'    },
    '0923456781': { nombres: 'TORRES VEGA ANA MARIA',            correo: 'a.torres@apm.gob.ec',      username: 'atorres'   },
    '1309876543': { nombres: 'PALMA TEJENA MICHAEL',             correo: 'm.palma@apm.gob.ec',       username: 'mpalma'    }
};

function autocompletarDatosUsuario() {
    const cedula = document.getElementById('buscadorEmpleadoUsuario').value.trim();
    const emp = directorioMock[cedula];
    if (emp) {
        document.getElementById('usNombreDisplay').value = emp.nombres;
        document.getElementById('usCorreoDisplay').value = emp.correo;
        document.getElementById('u-usuario').value = emp.username;
    } else {
        document.getElementById('usNombreDisplay').value = '';
        document.getElementById('usCorreoDisplay').value = '';
        document.getElementById('u-usuario').value = '';
    }
}

/* ── Toggle de estado dinámico ──────────────────────────────────────── */
function cambiarEstadoUsuario(toggle, idUsuario, nombreUsuario) {
    const badge = document.getElementById('badge_estado_' + idUsuario);
    if (!badge) return;
    if (toggle.checked) {
        badge.className = 'estado-pill estado-activo';
        badge.innerHTML = '<i class="bi bi-check-circle"></i> Activo';
        showToast(`✅ Usuario "${nombreUsuario}" activado correctamente.`, 'success');
    } else {
        badge.className = 'estado-pill estado-inactivo';
        badge.innerHTML = '<i class="bi bi-x-circle"></i> Inactivo';
        showToast(`⚠️ Usuario "${nombreUsuario}" suspendido. Ya no puede acceder al sistema.`, 'error');
    }
    // Aquí en el futuro: fetch('/admin/usuarios/estado', { method:'POST', body: JSON.stringify({id:idUsuario, activo:toggle.checked}) })
}

/* ── Restablecer contraseña ───────────────────────────────────────────── */
function resetearCredenciales(nombreUsuario) {
    if (confirm(`¿Desea restablecer la contraseña de "${nombreUsuario}"?\n\nSe generará una clave temporal que será enviada al correo institucional del funcionario.`)) {
        showToast(`🔑 Clave temporal generada. Correo enviado a ${nombreUsuario}.`, 'success');
        // Aquí en el futuro: fetch('/admin/usuarios/reset-password', { method:'POST', body: JSON.stringify({usuario: nombreUsuario}) })
    }
}

/* ── Generador de clave aleatoria ──────────────────────────────────────── */
function generarClaveAleatoria() {
    const chars = 'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#!';
    let pass = '';
    for (let i = 0; i < 10; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
    document.getElementById('u-pass').value = pass;
    showToast('Clave temporal generada: ' + pass, 'info');
}

function toggleEstado(usuario, estadoActual) {
    const nuevo = estadoActual === 'Activo' ? 'desactivado' : 'activado';
    showToast(`Usuario "${usuario}" ha sido ${nuevo}.`, estadoActual === 'Activo' ? 'error' : 'success');
}
function filtrarUsuarios() {
    const q = document.getElementById('userSearch').value.toLowerCase();
    document.querySelectorAll('#userTableBody tr').forEach(tr => {
        tr.style.display = tr.dataset.search?.includes(q) ? '' : 'none';
    });
}
document.getElementById('modalUser')?.addEventListener('click', e => {
    if (e.target === document.getElementById('modalUser')) cerrarUser();
});
</script>
<?php require_once ROOT . '/shared/footer_scripts.php'; ?>
</body>
</html>
