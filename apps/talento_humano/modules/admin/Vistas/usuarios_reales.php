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
            <div class="admin-disclosure-body"><form method="post" action="<?= BASE_URL ?>/admin/usuarios/crear" class="admin-form-grid">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Auth::csrfToken()) ?>">
                <div class="field"><label>Funcionario</label><select name="empleado_id" id="empleadoCuenta" required><option value="">Seleccione</option><?php foreach($empleados as $e): ?><option value="<?= (int)$e['empleado_id'] ?>" data-nombre="<?= htmlspecialchars($e['nombre']) ?>" data-correo="<?= htmlspecialchars($e['correo_institucional']??'') ?>"><?= htmlspecialchars($e['identificacion'].' · '.$e['nombre']) ?></option><?php endforeach; ?></select></div>
                <div class="field"><label>Nombre</label><input id="nombreCuenta" name="nombre" required></div>
                <div class="field"><label>Correo</label><input id="correoCuenta" type="email" name="correo" required></div>
                <div class="field"><label>Usuario</label><input name="usuario" pattern="[a-z0-9._-]{4,50}" required></div>
                <div class="field"><label>Rol</label><select name="rol_id" required><?php foreach($roles as $r): ?><option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['nombre']) ?></option><?php endforeach; ?></select></div>
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
document.getElementById('empleadoCuenta')?.addEventListener('change',e=>{const o=e.target.selectedOptions[0];document.getElementById('nombreCuenta').value=o?.dataset.nombre||'';document.getElementById('correoCuenta').value=o?.dataset.correo||''});
// Hash SHA-256 de la clave inicial (ver js/password-hash.js, cargado por
// footer_scripts.php) -- el atributo pattern del <input> ya exigió
// mayúscula/minúscula/número/símbolo del lado del navegador ANTES de que
// este listener corra (la validación nativa de formulario bloquea el
// evento submit si el pattern no matchea).
document.querySelector('form[action$="/admin/usuarios/crear"]')?.addEventListener('submit', function (e) {
    if (!window.hashPasswordFieldsBeforeSubmit) return;
    e.preventDefault();
    var form = e.target;
    hashPasswordFieldsBeforeSubmit(form, ['password']).then(function () { form.submit(); });
});
<?php if(!empty($_GET['msg'])): ?>addEventListener('DOMContentLoaded',()=>showToast(<?= json_encode($_GET['msg']) ?>,<?= ($_GET['ok']??'0')==='1'?"'success'":"'error'" ?>));<?php endif; ?>
</script>
<?php require ROOT.'/shared/footer_scripts.php'; ?>
</body></html>
