<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Usuarios | APM</title>
    <?php require ROOT.'/shared/head_assets.php'; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/admin_compact.css">
</head>
<body><div class="app">
<?php require ROOT.'/shared/menu.php'; ?>
<section class="content">
    <?php $topbarTitle='Gestión de usuarios';$topbarSubtitle='Administración y seguridad';$topbarShowSearch=true;require ROOT.'/shared/topbar.php'; ?>
    <main class="main"><div class="content-shell admin-page">
        <section class="admin-section-head"><div><h1>Usuarios del sistema</h1><p>Administración de cuentas, estados y mecanismos de acceso.</p></div><span class="admin-count-chip"><i class="bi bi-person-check"></i><?= (int)$activos ?> activos de <?= (int)$total ?></span></section>
        <?php if(!empty($claveTemporal)): ?><div class="alert" style="padding:16px;background:#fff7ed;border:1px solid #fdba74;border-radius:12px;margin-bottom:16px"><strong>Clave temporal (se muestra una sola vez):</strong> <code><?= htmlspecialchars($claveTemporal) ?></code></div><?php endif; ?>
        <details class="admin-disclosure">
            <summary><span class="admin-disclosure-icon"><i class="bi bi-person-plus-fill"></i></span><span class="admin-disclosure-copy"><span>Crear una cuenta</span><small>Abra este bloque únicamente cuando necesite habilitar un nuevo acceso.</small></span><i class="bi bi-chevron-down admin-disclosure-chevron"></i></summary>
            <div class="admin-disclosure-body"><form method="post" action="<?= BASE_URL ?>/admin/usuarios/crear" class="admin-form-grid" id="formCrearCuenta">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">

                <?php
                // Serializar lista de empleados para el JS del buscador
                $empleadosJs = array_map(fn($e) => [
                    'id'      => (int)$e['empleado_id'],
                    'cedula'  => $e['identificacion'],
                    'nombre'  => $e['nombre'],
                    'correo'  => $e['correo_institucional'] ?? '',
                    'puesto'  => (int)$e['puesto_id'],
                    'label'   => $e['identificacion'].' · '.$e['nombre'],
                ], $empleados);
                ?>

                <!-- Buscador de funcionario con autocompletado -->
                <div class="field" id="wrapperFuncionario">
                    <label for="buscarFuncionario">Funcionario</label>
                    <input
                        type="text"
                        id="buscarFuncionario"
                        placeholder="Escriba cédula o nombre..."
                        autocomplete="off"
                        required
                    >
                    <!-- Input oculto que lleva el empleado_id al servidor -->
                    <input type="hidden" name="empleado_id" id="empleadoCuenta">
                    <!-- Lista desplegable — se posiciona via JS con getBoundingClientRect -->
                    <ul id="listadoFuncionarios" style="
                        display:none;position:fixed;z-index:9999;
                        max-height:260px;overflow-y:auto;
                        background:#fff;border:1px solid #e5e7eb;border-radius:10px;
                        box-shadow:0 8px 24px rgba(0,0,0,.15);padding:4px 0;list-style:none;
                        min-width:320px;
                    "></ul>
                    <small id="funcionarioHint" style="color:#6b7280;display:none"></small>
                </div>

                <div class="field"><label>Nombre completo</label><input id="nombreCuenta" name="nombre" required></div>
                <div class="field"><label>Correo</label><input id="correoCuenta" type="email" name="correo" required></div>
                <div class="field"><label>Usuario <small style="color:#6b7280;font-weight:400">(se asigna desde la cédula)</small></label><input id="usuarioCuenta" name="usuario" readonly style="background:#f3f4f6;cursor:not-allowed;color:#374151" placeholder="Se autocompletará al seleccionar el funcionario" required></div>
                <div class="field"><label>Rol</label><select name="rol_id" id="rolCuenta" required><?php foreach($roles as $r): ?><option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['nombre']) ?></option><?php endforeach; ?></select><small id="rolHint" style="color:#6b7280;display:none"></small></div>
                <div class="field"><label>Clave inicial segura</label><input type="password" name="password" minlength="12" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{12,}" required autocomplete="new-password"><small>Mínimo 12 caracteres con mayúscula, minúscula, número y símbolo.</small></div>
                <div class="admin-form-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-person-plus"></i> Crear cuenta</button></div>
            </form></div>
        </details>
        <section class="card admin-table-card"><div class="admin-table-scroll"><table><thead><tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Estado</th><th>2FA</th><th>Último acceso</th><th>Acciones</th></tr></thead><tbody>
        <?php foreach($usuarios as $u): ?><tr>
            <td><?= htmlspecialchars($u['usuario']) ?></td><td><?= htmlspecialchars($u['nombre']) ?><br><small><?= htmlspecialchars($u['correo']) ?></small></td><td><?= htmlspecialchars($u['rol']) ?></td>
            <td><?= $u['estado']?'Activo':'Inactivo' ?><?= $u['debe_cambiar_clave']?' · Cambio de clave pendiente':'' ?></td><td><span class="badge <?= $u['mfa_habilitado']?'badge-active':'badge-muted' ?>"><?= $u['mfa_habilitado']?'Activo':'Pendiente' ?></span></td><td><?= htmlspecialchars((string)($u['ultimo_acceso']??'Nunca')) ?></td>
            <td><div class="admin-row-actions">
                <form method="post" action="<?= BASE_URL ?>/admin/usuarios/estado" style="display:inline"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="estado" value="<?= $u['estado']?0:1 ?>"><button class="btn btn-outline" type="submit"><?= $u['estado']?'Desactivar':'Activar' ?></button></form>
                <form method="post" action="<?= BASE_URL ?>/admin/usuarios/resetear-clave" style="display:inline" onsubmit="return confirm('¿Restablecer la clave y cerrar sus sesiones?')"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>"><button class="btn btn-ghost" type="submit">Restablecer clave</button></form>
                <?php if($u['mfa_habilitado']): ?><form method="post" action="<?= BASE_URL ?>/admin/usuarios/resetear-mfa" style="display:inline" onsubmit="return confirm('¿Restablecer el segundo factor y cerrar las sesiones de este usuario?')"><input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>"><input type="hidden" name="usuario_id" value="<?= (int)$u['id'] ?>"><button class="btn btn-ghost" type="submit">Restablecer 2FA</button></form><?php endif; ?>
            </div></td>
        </tr><?php endforeach; ?>
        </tbody></table></div></section>
    </div></main>
</section></div>
<script>
// ── Datos de empleados y mapa de roles (generados desde PHP) ──────────────────
const EMPLEADOS       = <?= json_encode(array_values($empleadosJs), JSON_THROW_ON_ERROR) ?>;
const ROLES_POR_PUESTO= <?= json_encode($rolesPorPuesto ?? [], JSON_THROW_ON_ERROR) ?>;

// ── Referencias DOM ───────────────────────────────────────────────────────────
const inputBuscar     = document.getElementById('buscarFuncionario');
const inputHidden     = document.getElementById('empleadoCuenta');
const listado         = document.getElementById('listadoFuncionarios');
const funcionarioHint = document.getElementById('funcionarioHint');
const selectRol       = document.getElementById('rolCuenta');
const rolHint         = document.getElementById('rolHint');

let seleccionado = null; // objeto empleado actualmente seleccionado

// ── Posicionar el dropdown justo debajo del input (position:fixed) ────────────
function posicionarListado() {
    if (!inputBuscar || !listado) return;
    const r = inputBuscar.getBoundingClientRect();
    listado.style.top   = (r.bottom + 4) + 'px';
    listado.style.left  = r.left + 'px';
    listado.style.width = r.width + 'px';
}

// ── Función: normalizar texto para comparación insensible a tildes ─────────────
function normalizar(s) {
    return (s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
}

// ── Función: mostrar/ocultar lista de resultados ──────────────────────────────
function mostrarListado(items) {
    listado.innerHTML = '';
    if (!items.length) {
        listado.innerHTML = '<li style="padding:10px 14px;color:#9ca3af;font-size:.85rem">Sin resultados</li>';
        posicionarListado();
        listado.style.display = 'block';
        return;
    }
    items.slice(0, 40).forEach(emp => {
        const li = document.createElement('li');
        li.style.cssText = 'padding:9px 14px;cursor:pointer;display:flex;flex-direction:column;gap:1px;transition:background .12s';
        li.innerHTML = `
            <span style="font-size:.88rem;font-weight:600;color:#111827">${emp.cedula}</span>
            <span style="font-size:.82rem;color:#6b7280">${emp.nombre}</span>`;
        li.addEventListener('mouseenter', () => li.style.background = '#f3f4f6');
        li.addEventListener('mouseleave', () => li.style.background = '');
        li.addEventListener('mousedown', (e) => { e.preventDefault(); elegirEmpleado(emp); });
        listado.appendChild(li);
    });
    posicionarListado();
    listado.style.display = 'block';
}

// ── Función: aplicar selección de un empleado ─────────────────────────────────
function elegirEmpleado(emp) {
    seleccionado = emp;
    inputBuscar.value = emp.cedula + ' · ' + emp.nombre;
    inputHidden.value = emp.id;
    listado.style.display    = 'none';
    inputBuscar.style.borderColor = '#22c55e';
    funcionarioHint.textContent   = '✓ Funcionario seleccionado.';
    funcionarioHint.style.color   = '#16a34a';
    funcionarioHint.style.display = 'block';

    // Rellenar campos derivados
    document.getElementById('nombreCuenta').value  = emp.nombre;
    document.getElementById('correoCuenta').value  = emp.correo;
    document.getElementById('usuarioCuenta').value = emp.cedula;

    // Sugerir rol según puesto
    aplicarSugerenciaRol(String(emp.puesto));
}

// ── Función: sugerir rol y colorear opciones del select ───────────────────────
function aplicarSugerenciaRol(puestoId) {
    if (!selectRol) return;
    const rolesVal = ROLES_POR_PUESTO[puestoId] || [];

    [...selectRol.options].forEach(opt => {
        const esValido = !opt.value || rolesVal.includes(parseInt(opt.value, 10));
        opt.style.color     = esValido ? '' : '#9ca3af';
        opt.style.fontStyle = esValido ? '' : 'italic';
        opt.textContent = opt.textContent.replace(' ✓','').replace(' ⚠','');
        if (opt.value && rolesVal.includes(parseInt(opt.value, 10))) opt.textContent += ' ✓';
    });

    if (rolesVal.length > 0) {
        selectRol.value         = String(rolesVal[0]);
        rolHint.textContent     = '✓ Rol sugerido según el puesto del funcionario.';
        rolHint.style.color     = '#16a34a';
        rolHint.style.display   = 'block';
    } else if (puestoId) {
        rolHint.textContent     = '⚠ Este puesto no tiene un rol predefinido. Seleccione manualmente.';
        rolHint.style.color     = '#d97706';
        rolHint.style.display   = 'block';
    } else {
        rolHint.style.display   = 'none';
    }
}

// ── Evento: filtrar mientras escribe ─────────────────────────────────────────
inputBuscar?.addEventListener('input', function () {
    const q = normalizar(this.value.trim());
    // Si borraron texto, limpiar selección previa
    if (seleccionado && normalizar(seleccionado.cedula + ' ' + seleccionado.nombre).indexOf(q) === -1) {
        limpiarSeleccion(false);
    }
    if (!q) { listado.style.display = 'none'; return; }
    const filtrados = EMPLEADOS.filter(e =>
        normalizar(e.cedula).includes(q) || normalizar(e.nombre).includes(q)
    );
    mostrarListado(filtrados);
});

// ── Evento: abrir lista al hacer foco si hay texto ────────────────────────────
inputBuscar?.addEventListener('focus', function () {
    if (this.value.trim() && !seleccionado) {
        this.dispatchEvent(new Event('input'));
    }
});

// ── Cerrar lista al perder foco ───────────────────────────────────────────────
inputBuscar?.addEventListener('blur', () => {
    setTimeout(() => { listado.style.display = 'none'; }, 150);
});

// ── Reposicionar al hacer scroll o resize ────────────────────────────────────
window.addEventListener('scroll', () => { if (listado.style.display !== 'none') posicionarListado(); }, true);
window.addEventListener('resize', () => { if (listado.style.display !== 'none') posicionarListado(); });

// ── Navegar con teclado ───────────────────────────────────────────────────────
inputBuscar?.addEventListener('keydown', function (e) {
    const items = [...listado.querySelectorAll('li')];
    const activo = listado.querySelector('li.ac-active');
    let idx = items.indexOf(activo);
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        idx = Math.min(idx + 1, items.length - 1);
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        idx = Math.max(idx - 1, 0);
    } else if (e.key === 'Enter' && activo) {
        e.preventDefault();
        activo.dispatchEvent(new MouseEvent('mousedown'));
        return;
    } else if (e.key === 'Escape') {
        listado.style.display = 'none'; return;
    } else { return; }
    items.forEach(li => { li.classList.remove('ac-active'); li.style.background = ''; });
    if (items[idx]) {
        items[idx].classList.add('ac-active');
        items[idx].style.background = '#eff6ff';
        items[idx].scrollIntoView({ block: 'nearest' });
    }
});

// ── Botón limpiar (click en ×) — queda como fallback al escribir ─────────────
function limpiarSeleccion(focoInput = true) {
    seleccionado              = null;
    inputBuscar.value         = '';
    inputHidden.value         = '';
    inputBuscar.style.borderColor = '';
    funcionarioHint.style.display = 'none';
    document.getElementById('nombreCuenta').value  = '';
    document.getElementById('correoCuenta').value  = '';
    document.getElementById('usuarioCuenta').value = '';
    listado.style.display     = 'none';
    rolHint.style.display     = 'none';
    if (focoInput) inputBuscar.focus();
}

// ── Validar antes de enviar que se seleccionó un funcionario ──────────────────
document.getElementById('formCrearCuenta')?.addEventListener('submit', function (e) {
    if (!inputHidden.value) {
        e.preventDefault();
        inputBuscar.style.borderColor = '#ef4444';
        funcionarioHint.textContent   = '⚠ Debe seleccionar un funcionario de la lista.';
        funcionarioHint.style.color   = '#ef4444';
        funcionarioHint.style.display = 'block';
        inputBuscar.focus();
    }
});

// Hash SHA-256 de la clave inicial (ver js/password-hash.js, cargado por
// footer_scripts.php) -- el atributo pattern del <input> ya exigió
// mayúscula/minúscula/número/símbolo del lado del navegador ANTES de que
// este listener corra (la validación nativa de formulario bloquea el
// evento submit si el pattern no matchea). Se registra DESPUÉS del
// validador de funcionario de arriba y respeta su preventDefault() --
// si esa validación ya canceló el envío, este listener no debe forzarlo.
document.getElementById('formCrearCuenta')?.addEventListener('submit', function (e) {
    if (e.defaultPrevented || !window.hashPasswordFieldsBeforeSubmit) return;
    e.preventDefault();
    var form = e.target;
    hashPasswordFieldsBeforeSubmit(form, ['password']).then(function () { form.submit(); });
});
<?php if(!empty($_GET['msg'])): ?>addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_GET['msg']) ?>,<?= ($_GET['ok']??'0')==='1'?"'success'":"'error'" ?>));<?php endif; ?>
</script>
<?php require ROOT.'/shared/footer_scripts.php'; ?>
</body></html>
