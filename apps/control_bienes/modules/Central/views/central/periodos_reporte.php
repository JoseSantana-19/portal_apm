<?php
/**
 * PERIODOS_REPORTE.PHP - Reportes de Auditoría e IVA Dinámico por Período / Fechas
 * Compatible con PHP 7.4, 8.3 y 8.4
 */

$sumBase = 0.0;
$sumIva = 0.0;
$sumTotal = 0.0;
?>

<!-- InvCabecera de Página -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1>Reportes Contables por Período</h1>
        <p>Generación de reportes de activos portuarios asignados con límites de fechas. Resuelve el IVA retroactivo que estuvo vigente en dicho lapso temporal.</p>
    </div>
    <div>
        <a href="index.php?route=inv_periodos" class="btn-outline" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;"><i class="fa-solid fa-arrow-left"></i> Volver a Períodos</a>
    </div>
</div>

<!-- Filtros de Fecha de Reporte -->
<div class="panel animate-fade-in" style="margin-bottom: 24px;">
    <div class="panel-header">
        <h3>Filtro por Rango de Fechas (Límites de Auditoría)</h3>
    </div>
    <form action="index.php" method="GET" style="padding:20px;display:grid;grid-template-columns:1fr 1fr auto;gap:16px;align-items:end;">
        <input type="hidden" name="route" value="inv_periodos">
        <input type="hidden" name="action" value="generarReporte">
        
        <div class="form-group" style="margin:0;">
            <label>Fecha de Inicio del Período</label>
            <input type="date" name="fecha_inicio" required value="<?= htmlspecialchars($fechaInicio) ?>">
        </div>
        
        <div class="form-group" style="margin:0;">
            <label>Fecha de Fin del Período</label>
            <input type="date" name="fecha_fin" required value="<?= htmlspecialchars($fechaFin) ?>">
        </div>
        
        <button type="submit" class="btn-primary" style="height:40px;"><i class="fa-solid fa-sync"></i> Generar Reporte</button>
    </form>
</div>

<!-- Resultados del Reporte -->
<div class="panel animate-fade-in">
    <div class="panel-header" style="display:flex;justify-content:between;align-items:center;padding:16px 20px;">
        <div>
            <h3><?= htmlspecialchars($reporte['periodo_nombre']) ?></h3>
            <p style="margin:2px 0 0 0;font-size:12px;color:var(--text-muted);">
                Origen del Reporte: <strong><?= ($reporte['origen'] === 'respaldo_historico') ? 'Respaldo Histórico Congelado (Cierre)' : 'Consulta Dinámica Activa' ?></strong> (Tasa IVA Aplicada: <strong><?= $reporte['tasa_iva'] ?>%</strong>).
            </p>
        </div>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Secuencial</th>
                    <th>Equipo / Contenedor</th>
                    <th>Marca</th>
                    <th>Categoría</th>
                    <th>Ubicación</th>
                    <th>Responsable</th>
                    <th>Área de Talento Humano</th>
                    <th>Valor Base</th>
                    <th>IVA (<?= $reporte['tasa_iva'] ?>%)</th>
                    <th>Total Valorado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reporte['datos'])): ?>
                    <tr>
                        <td colspan="10" style="text-align:center; padding:50px; color:var(--text-muted);">
                            <i class="fa-solid fa-file-excel" style="font-size:40px; display:block; margin-bottom:12px; opacity:0.3;"></i>
                            No se encontraron registros de bienes para el rango de fechas especificado
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reporte['datos'] as $row): 
                        // Mapear campos dependiendo de si es consulta activa o respaldo histórico
                        $sec = isset($row['secuencial']) ? $row['secuencial'] : '';
                        $nom = isset($row['nombre_historico']) ? $row['nombre_historico'] : '';
                        $marca = isset($row['marca_historica']) ? $row['marca_historica'] : '';
                        $cat = isset($row['categoria_historica']) ? $row['categoria_historica'] : '';
                        $zona = isset($row['zona_historica']) ? $row['zona_historica'] : '';
                        $resp = isset($row['responsable_historico']) ? $row['responsable_historico'] : 'Sin Responsable';
                        $area = isset($row['area_talento_historica']) ? $row['area_talento_historica'] : 'Sin Asignar';
                        
                        $vBase = (float)(isset($row['valor_historico']) ? $row['valor_historico'] : $row['valor']);
                        $tasaIvaRow = (float)(isset($row['iva_aplicado']) ? $row['iva_aplicado'] : $reporte['tasa_iva']);
                        $vIva = $vBase * ($tasaIvaRow / 100);
                        $vTotal = $vBase + $vIva;

                        // Sumadores
                        $sumBase += $vBase;
                        $sumIva += $vIva;
                        $sumTotal += $vTotal;
                    ?>
                        <tr>
                            <td class="secuencial-cell"><?= htmlspecialchars((string)$sec) ?></td>
                            <td><strong><?= htmlspecialchars((string)$nom) ?></strong></td>
                            <td><?= htmlspecialchars((string)$marca) ?></td>
                            <td><span class="cat-badge" style="--cat-color:#64748b;"><?= htmlspecialchars((string)$cat) ?></span></td>
                            <td><?= htmlspecialchars((string)$zona) ?></td>
                            <td><?= htmlspecialchars((string)$resp) ?></td>
                            <td><span style="font-weight:600;color:var(--primary);"><?= htmlspecialchars((string)$area) ?></span></td>
                            <td>$<?= number_format($vBase, 2) ?></td>
                            <td>$<?= number_format($vIva, 2) ?></td>
                            <td><strong>$<?= number_format($vTotal, 2) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($reporte['datos'])): ?>
                <tfoot>
                    <tr style="background:rgba(59,130,246,0.05);font-weight:700;border-top:2px solid var(--border-color);color:var(--text-color);">
                        <td colspan="7" style="text-align:right;padding:16px;">VALORES TOTALES CONSOLIDADOS:</td>
                        <td style="font-size:15px;">$<?= number_format($sumBase, 2) ?></td>
                        <td style="font-size:15px;">$<?= number_format($sumIva, 2) ?></td>
                        <td style="font-size:16px;color:var(--primary);">$<?= number_format($sumTotal, 2) ?></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
