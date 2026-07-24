<?php $success = SessionHelper::getFlash('success'); ?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-5);">
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-address-book" style="color:var(--color-info);margin-right:var(--sp-2);"></i>
        Registro de Visitantes
    </h2>
    <div style="display:flex;gap:var(--sp-2);">
        <a href="<?= APP_URL ?>/acceso" class="btn btn-outline" data-spa>
            <i class="fa-solid fa-id-card"></i> Control Acceso
        </a>
        <a href="<?= APP_URL ?>/acceso/visitantes/nuevo" class="btn btn-primary" data-spa>
            <i class="fa-solid fa-plus"></i> Nuevo Visitante
        </a>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr><th>Documento</th><th>Nombre</th><th>Empresa</th><th>Correo</th><th>Teléfono</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php if (empty($visitantes)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--color-text-muted);padding:var(--sp-8);">Sin visitantes registrados</td></tr>
            <?php else: ?>
            <?php foreach ($visitantes as $vis): ?>
            <tr>
                <td><?= htmlspecialchars($vis['cedula'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <a href="<?= APP_URL ?>/acceso/visitantes/<?= $vis['id_visitante'] ?>" data-spa
                       style="color:var(--color-primary);text-decoration:none;font-weight:var(--font-weight-medium);">
                        <?= htmlspecialchars($vis['nombres'] . ' ' . $vis['apellidos'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($vis['empresa'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($vis['correo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($vis['telefono'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge badge-success">Registrado</span></td>
                <td>
                    <a href="<?= APP_URL ?>/acceso/visitantes/<?= $vis['id_visitante'] ?>" class="btn btn-ghost btn-sm" data-spa>
                        <i class="fa-solid fa-eye"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
