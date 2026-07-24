<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-5);">
    <h2 style="font-size:var(--font-size-xl);font-weight:var(--font-weight-bold);">
        <i class="fa-solid fa-chart-bar" style="color:var(--color-warning);margin-right:var(--sp-2);"></i>
        Reportes de Bitácoras
    </h2>
    <a href="<?= APP_URL ?>/bitacoras" class="btn btn-outline" data-spa>
        <i class="fa-solid fa-list"></i> Ver Listado
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:var(--sp-5);">

    <!-- Por estado -->
    <div class="card">
        <h3 style="font-size:var(--font-size-base);font-weight:var(--font-weight-semibold);margin-bottom:var(--sp-4);">Por Estado</h3>
        <?php foreach ($resumen as $r): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--sp-3);">
            <span style="font-size:var(--font-size-sm);"><?= htmlspecialchars($r['estado'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="badge badge-primary"><?= number_format((int)$r['total']) ?></span>
        </div>
        <?php endforeach; ?>
        <div id="chart-estados" style="min-height:200px;margin-top:var(--sp-4);"></div>
    </div>

    <!-- Por categoría -->
    <div class="card">
        <h3 style="font-size:var(--font-size-base);font-weight:var(--font-weight-semibold);margin-bottom:var(--sp-4);">Por Categoría</h3>
        <table>
            <thead><tr><th>Categoría</th><th>Total</th></tr></thead>
            <tbody>
            <?php foreach ($porCategoria as $c): ?>
            <tr>
                <td><?= htmlspecialchars($c['categoria'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge badge-info"><?= number_format((int)$c['total']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
createChart('#chart-estados', {
    chart: { type: 'donut', height: 200 },
    series: [<?= implode(',', array_map(fn($r) => (int)$r['total'], $resumen)) ?>],
    labels: [<?= implode(',', array_map(fn($r) => '"' . addslashes($r['estado']) . '"', $resumen)) ?>],
    legend: { position: 'bottom' },
    dataLabels: { enabled: true },
});
</script>
