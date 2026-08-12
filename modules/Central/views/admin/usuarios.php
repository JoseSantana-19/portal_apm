<?php
$success = SessionHelper::getFlash('success');
$nivelLabels = [0=>'Operativo',1=>'Analista',2=>'Jefatura',3=>'Director',4=>'Super Admin'];
$nivelColors = [0=>'badge-gray',1=>'badge-info',2=>'badge-primary',3=>'badge-warning',4=>'badge-danger'];

$total    = count($usuarios);
$activos  = count(array_filter($usuarios, fn($u) => $u['estado']));
$inactivos = $total - $activos;
?>

<?php if ($success): ?>
<script>document.addEventListener('DOMContentLoaded', () => PortalAlert.success(<?= json_encode($success) ?>));</script>
<?php endif; ?>

<!-- Header -->
<div class="page-header">
    <div>
        <h2 class="page-title">
            <i class="fa-solid fa-users-gear" style="color:var(--color-primary);margin-right:var(--sp-2);"></i>
            Gestión de Usuarios
        </h2>
        <p class="page-subtitle">Administra cuentas, niveles jerárquicos y asignación de roles</p>
    </div>
    <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;">
        <a href="<?= APP_URL ?>/admin/usuarios/export/excel" class="btn btn-ghost" title="Exportar todos a Excel"><i class="fa-solid fa-file-excel" style="color:#1D6F42;"></i> Excel</a>
        <a href="<?= APP_URL ?>/admin/usuarios/export/pdf" class="btn btn-ghost" target="_blank" rel="noopener" title="Exportar todos a PDF"><i class="fa-solid fa-file-pdf" style="color:#c0392b;"></i> PDF</a>
        <a href="<?= APP_URL ?>/admin/usuarios/nuevo" class="btn btn-primary" data-spa>
            <i class="fa-solid fa-user-plus"></i> Nuevo Usuario
        </a>
    </div>
</div>

<!-- Stats -->
<div style="display:flex;gap:var(--sp-3);margin-bottom:var(--sp-5);flex-wrap:wrap;">
    <div style="background:var(--card-bg);border:1px solid var(--color-border-light);border-radius:var(--radius-md);
                padding:var(--sp-3) var(--sp-4);display:flex;align-items:center;gap:var(--sp-3);box-shadow:var(--shadow-xs);">
        <div style="width:36px;height:36px;border-radius:var(--radius-md);background:color-mix(in srgb,var(--color-primary) 12%,transparent);
                    display:flex;align-items:center;justify-content:center;color:var(--color-primary);">
            <i class="fa-solid fa-users" style="font-size:0.9rem;"></i>
        </div>
        <div>
            <div style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);line-height:1;"><?= $total ?></div>
            <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);">Total</div>
        </div>
    </div>
    <div style="background:var(--card-bg);border:1px solid var(--color-border-light);border-radius:var(--radius-md);
                padding:var(--sp-3) var(--sp-4);display:flex;align-items:center;gap:var(--sp-3);box-shadow:var(--shadow-xs);">
        <div style="width:36px;height:36px;border-radius:var(--radius-md);background:color-mix(in srgb,var(--color-success) 12%,transparent);
                    display:flex;align-items:center;justify-content:center;color:var(--color-success);">
            <i class="fa-solid fa-user-check" style="font-size:0.9rem;"></i>
        </div>
        <div>
            <div style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);line-height:1;"><?= $activos ?></div>
            <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);">Activos</div>
        </div>
    </div>
    <?php if ($inactivos > 0): ?>
    <div style="background:var(--card-bg);border:1px solid var(--color-border-light);border-radius:var(--radius-md);
                padding:var(--sp-3) var(--sp-4);display:flex;align-items:center;gap:var(--sp-3);box-shadow:var(--shadow-xs);">
        <div style="width:36px;height:36px;border-radius:var(--radius-md);background:color-mix(in srgb,var(--color-danger) 12%,transparent);
                    display:flex;align-items:center;justify-content:center;color:var(--color-danger);">
            <i class="fa-solid fa-user-xmark" style="font-size:0.9rem;"></i>
        </div>
        <div>
            <div style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);line-height:1;"><?= $inactivos ?></div>
            <div style="font-size:var(--font-size-xs);color:var(--color-text-muted);">Inactivos</div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Search + table -->
<div class="card">
    <?php if (!empty($usuarios)): ?>
    <div style="padding:var(--sp-4);border-bottom:1px solid var(--color-border-light);">
        <div style="position:relative;max-width:340px;">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:var(--sp-3);top:50%;transform:translateY(-50%);color:var(--color-text-muted);font-size:var(--font-size-xs);pointer-events:none;"></i>
            <input type="text" id="usr-search" placeholder="Buscar por nombre, cédula o correo…"
                   class="form-control" style="padding-left:calc(var(--sp-3)*2 + 0.75rem);"
                   oninput="filterUsers(this.value)">
        </div>
    </div>
    <?php endif; ?>

    <div style="overflow-x:auto;">
        <table id="usr-table">
            <thead>
                <tr>
                    <th>Cédula</th>
                    <th>Nombre Completo</th>
                    <th>Correo</th>
                    <th>Nivel</th>
                    <th>Departamento</th>
                    <th>Estado</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($usuarios)): ?>
            <tr><td colspan="7">
                <div class="empty-state">
                    <i class="fa-solid fa-users-slash"></i>
                    <h3>Sin usuarios registrados</h3>
                    <p>Crea el primer usuario con el botón de arriba.</p>
                </div>
            </td></tr>
            <?php else: ?>
            <?php foreach ($usuarios as $u):
                $nivel = (int)($u['nivel_jerarquia'] ?? 0);
                $nivelLbl   = $nivelLabels[$nivel] ?? 'Operativo';
                $nivelColor = $nivelColors[$nivel] ?? 'badge-gray';
                $initials   = implode('', array_map(fn($w) => strtoupper(mb_substr($w,0,1)),
                                array_slice(explode(' ', $u['nombre_completo'] ?? ($u['cedula'] ?? '')), 0, 2)));
                $avatarColors = ['#0056b3','#28a745','#17a2b8','#6f42c1','#dc3545'];
                $avatarBg     = $avatarColors[$nivel % count($avatarColors)];
                $isSelf       = $u['id_usuario'] == ($_SESSION['user_id'] ?? 0);
            ?>
            <tr data-search="<?= strtolower(htmlspecialchars(($u['cedula']??'').' '.$u['nombre_completo'].' '.($u['correo']??''), ENT_QUOTES)) ?>">
                <td>
                    <div style="display:flex;align-items:center;gap:var(--sp-2);">
                        <div style="width:32px;height:32px;border-radius:var(--radius-full);background:<?= $avatarBg ?>;
                                    display:flex;align-items:center;justify-content:center;
                                    color:#fff;font-size:var(--font-size-xs);font-weight:var(--font-weight-bold);flex-shrink:0;">
                            <?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <code style="font-size:var(--font-size-xs);"><?= htmlspecialchars($u['cedula'] ?? '—', ENT_QUOTES, 'UTF-8') ?></code>
                        <?php if ($isSelf): ?><span class="badge badge-info" style="font-size:0.6rem;">Tú</span><?php endif; ?>
                    </div>
                </td>
                <td style="font-weight:var(--font-weight-medium);"><?= htmlspecialchars($u['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></td>
                <td style="color:var(--color-text-muted);"><?= htmlspecialchars($u['correo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge <?= $nivelColor ?>"><?= $nivelLbl ?></span></td>
                <td><?= htmlspecialchars($u['departamento'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <span class="badge <?= $u['estado'] ? 'badge-success' : 'badge-gray' ?>">
                        <i class="fa-solid fa-circle" style="font-size:5px;vertical-align:middle;margin-right:3px;"></i>
                        <?= $u['estado'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </td>
                <td style="text-align:right;white-space:nowrap;">
                    <a href="<?= APP_URL ?>/admin/usuarios/<?= $u['id_usuario'] ?>/editar"
                       class="btn btn-ghost btn-sm" data-spa title="Editar usuario">
                        <i class="fa-solid fa-pencil"></i>
                    </a>
                    <a href="<?= APP_URL ?>/admin/usuarios/<?= $u['id_usuario'] ?>/export/pdf"
                       class="btn btn-ghost btn-sm" target="_blank" rel="noopener" title="Ficha PDF del usuario">
                        <i class="fa-solid fa-file-pdf"></i>
                    </a>
                    <a href="<?= APP_URL ?>/admin/usuarios/<?= $u['id_usuario'] ?>/export/excel"
                       class="btn btn-ghost btn-sm" title="Exportar usuario a Excel">
                        <i class="fa-solid fa-file-excel"></i>
                    </a>
                    <?php if (!$isSelf && $u['estado']): ?>
                    <form method="POST" action="<?= APP_URL ?>/admin/usuarios/<?= $u['id_usuario'] ?>/eliminar" style="display:inline;">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" class="btn btn-ghost btn-sm" style="color:var(--color-danger);" title="Desactivar"
                                onclick="PortalAlert.confirmAction('¿Desactivar a <?= htmlspecialchars($u['nombre_completo'], ENT_QUOTES) ?>?', this.form, {title:'¿Desactivar usuario?', confirmText:'Sí, desactivar'})">
                            <i class="fa-solid fa-ban"></i>
                        </button>
                    </form>
                    <?php elseif (!$u['estado']): ?>
                    <form method="POST" action="<?= APP_URL ?>/admin/usuarios/<?= $u['id_usuario'] ?>/activar" style="display:inline;">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" class="btn btn-sm" title="Activar"
                                style="background:color-mix(in srgb, var(--color-success) 15%, transparent);color:var(--color-success);border:1px solid color-mix(in srgb, var(--color-success) 45%, transparent);font-weight:700;"
                                onclick="PortalAlert.confirmAction('¿Activar a <?= htmlspecialchars($u['nombre_completo'], ENT_QUOTES) ?>?', this.form, {title:'¿Activar usuario?', confirmText:'Sí, activar'})">
                            <i class="fa-solid fa-circle-check"></i> Activar
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div id="usr-empty-search" style="display:none;padding:var(--sp-8);text-align:center;color:var(--color-text-muted);">
        <i class="fa-solid fa-magnifying-glass" style="font-size:1.5rem;opacity:0.3;margin-bottom:var(--sp-2);display:block;"></i>
        Sin resultados para la búsqueda
    </div>
</div>

<script>
function filterUsers(q) {
    q = q.toLowerCase().trim();
    let visible = 0;
    document.querySelectorAll('#usr-table tbody tr[data-search]').forEach(tr => {
        const match = !q || tr.dataset.search.includes(q);
        tr.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    const empty = document.getElementById('usr-empty-search');
    if (empty) empty.style.display = (q && visible === 0) ? 'block' : 'none';
}
</script>
