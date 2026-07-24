<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-5);">
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-file-lines" style="color:var(--color-info);margin-right:var(--sp-2);"></i>
        Reporte de Acceso
    </h2>
    <a href="<?= APP_URL ?>/acceso" class="btn btn-outline" data-spa>
        <i class="fa-solid fa-arrow-left"></i> Volver
    </a>
</div>

<div class="card">
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>Nombre</th><th>Motivo</th><th>Fecha / Hora</th><th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($registros)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--color-text-muted);padding:var(--sp-8);">Sin registros</td></tr>
            <?php else: ?>
            <?php foreach ($registros as $r):
                $fh = $r['fecha_hora'];
                if ($fh instanceof DateTime) { $fh = $fh->format('d/m/Y H:i'); }
                elseif (is_string($fh)) { $fh = date('d/m/Y H:i', strtotime($fh)); }
                $estBadge = ($r['estado'] ?? '') === 'Activo'
                    ? '<span class="badge badge-warning">En Recinto</span>'
                    : '<span class="badge badge-success">Finalizado</span>';
            ?>
            <tr>
                <td><?= $r['id_registro'] ?></td>
                <td><?= htmlspecialchars($r['persona_visita'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($r['motivo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($fh, ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $estBadge ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
