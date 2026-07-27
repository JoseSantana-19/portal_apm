<div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-2);">
    <a href="<?= APP_URL ?>/admin/usuarios" class="btn btn-ghost btn-sm" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Usuarios
    </a>
    <span style="color:var(--color-text-muted);">/</span>
    <span style="color:var(--color-text-muted);font-size:var(--font-size-sm);">Desde Talento Humano</span>
</div>

<div class="page-header" style="margin-bottom:var(--sp-5);">
    <div>
        <h2 class="page-title">
            <i class="fa-solid fa-user-plus" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
            Crear cuenta desde empleado de Talento Humano
        </h2>
        <p class="page-subtitle">
            Empleados activos en la BD Talento_Humano que todavía no tienen cuenta de acceso al portal.
        </p>
    </div>
</div>

<form method="GET" action="<?= APP_URL ?>/admin/usuarios/desde-th" style="margin-bottom:var(--sp-4);display:flex;gap:var(--sp-2);max-width:420px;">
    <input type="text" name="q" class="form-control" placeholder="Buscar por nombre o cédula…"
           value="<?= htmlspecialchars($buscar, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
</form>

<div class="card">
    <div class="card-body" style="padding:0;overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Cédula</th>
                    <th>Nombre completo</th>
                    <th>Unidad organizacional</th>
                    <th>Correo institucional</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($empleados)): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--color-text-muted);padding:var(--sp-5);">
                    <?= $buscar !== '' ? 'Sin resultados para "' . htmlspecialchars($buscar, ENT_QUOTES, 'UTF-8') . '".' : 'Todos los empleados activos de Talento Humano ya tienen cuenta.' ?>
                </td></tr>
                <?php else: foreach ($empleados as $e): ?>
                <tr>
                    <td><code><?= htmlspecialchars($e['cedula'] ?? '', ENT_QUOTES, 'UTF-8') ?></code></td>
                    <td><?= htmlspecialchars($e['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($e['nombre_unidad'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($e['correo_institucional'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align:right;">
                        <a href="<?= APP_URL ?>/admin/usuarios/desde-th/<?= (int)$e['empleado_id'] ?>/nuevo"
                           class="btn btn-primary btn-sm" data-spa>
                            <i class="fa-solid fa-user-plus"></i> Crear cuenta
                        </a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
