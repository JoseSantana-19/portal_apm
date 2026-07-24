<?php $success = SessionHelper::getFlash('success'); ?>

<?php if ($success): ?>
<div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-5);flex-wrap:wrap;gap:var(--sp-3);">
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-id-card" style="color:var(--color-info);margin-right:var(--sp-2);"></i>
        Control de Acceso — Hoy
        <span class="badge badge-info"><?= number_format(count($registros)) ?> registros</span>
    </h2>
    <div style="display:flex;gap:var(--sp-2);">
        <a href="<?= APP_URL ?>/acceso/reporte" class="btn btn-outline" data-spa>
            <i class="fa-solid fa-file-lines"></i> Reporte
        </a>
        <a href="<?= APP_URL ?>/acceso/ingresar" class="btn btn-primary" data-spa>
            <i class="fa-solid fa-person-walking-arrow-right"></i> Registrar Ingreso
        </a>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Motivo</th>
                    <th>Hora Ingreso</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($registros)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--color-text-muted);padding:var(--sp-8);">
                Sin ingresos hoy
            </td></tr>
            <?php else: ?>
            <?php foreach ($registros as $r):
                $sinSalida = ($r['estado'] ?? '') === 'Activo';
                $hora = $r['fecha_hora'];
                if ($hora instanceof DateTime) { $hora = $hora->format('H:i'); }
                elseif (is_string($hora)) { $hora = date('H:i', strtotime($hora)); }
            ?>
            <tr>
                <td><?= $r['id_registro'] ?></td>
                <td><?= htmlspecialchars($r['persona_visita'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($r['motivo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $hora ?></td>
                <td>
                    <?php if ($sinSalida): ?>
                    <span class="badge badge-warning">En Recinto</span>
                    <?php else: ?>
                    <span class="badge badge-success">Finalizado</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($sinSalida): ?>
                    <button type="button"
                            class="btn btn-outline btn-sm"
                            onclick="registrarSalida(<?= $r['id_registro'] ?>, this)"
                            data-csrf="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid fa-person-walking-dashed-line-arrow-right"></i> Salida
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
async function registrarSalida(id, btn) {
    const fd = new FormData();
    fd.append('id_registro', id);
    fd.append('_csrf_token', btn.dataset.csrf);

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';

    const res  = await fetch('<?= APP_URL ?>/acceso/salida', { method: 'POST', body: fd, credentials: 'same-origin' });
    const data = await res.json();
    if (data.ok) {
        showToast('Salida registrada.', 'success');
        btn.closest('tr').querySelector('td:nth-child(5)').innerHTML =
            '<span class="badge badge-success">Finalizado</span>';
        btn.remove();
    } else {
        showToast(data.message || 'Error al registrar salida.', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-person-walking-dashed-line-arrow-right"></i> Salida';
    }
}
</script>
