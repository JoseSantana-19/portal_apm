<?php
/**
 * PERIODOS.PHP - Vista de Gestión de Períodos, Tasas de IVA y Cortes Históricos
 * Compatible con PHP 7.4, 8.3 y 8.4
 */
?>

<!-- InvCabecera de Página -->
<div class="page-header animate-fade-in">
    <div class="page-title">
        <h1>Períodos y Tasas de IVA</h1>
        <p>Configura los límites temporales de facturación/auditoría, ajusta el IVA por período (15%, 8%, 5%) y congela datos históricos mediante cortes de seguridad.</p>
    </div>
    <div>
        <a href="index.php?route=inv_periodos&action=generarReporte" class="btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;"><i class="fa-solid fa-file-invoice-dollar"></i> Reportes por Rango de Fechas</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;align-items:start;" class="animate-fade-in">
    
    <!-- Panel Izquierdo: Período Activo y Cierre -->
    <div class="panel">
        <div class="panel-header">
            <h3>Período Contable Activo</h3>
        </div>
        <div style="padding:20px;">
            <?php if (!$periodoActivo): ?>
                <div style="text-align:center;padding:20px;color:var(--danger);">
                    <i class="fa-solid fa-calendar-times" style="font-size:32px;margin-bottom:8px;"></i>
                    <p><strong>¡Atención!</strong> No existe ningún período fiscal activo actualmente. Los cálculos del IVA podrían fallar.</p>
                </div>
            <?php else: ?>
                <div style="display:flex;align-items:center;justify-content:between;background:rgba(59,130,246,0.05);padding:16px;border-radius:12px;border:1px solid rgba(59,130,246,0.1);margin-bottom:20px;">
                    <div>
                        <h2 style="margin:0;font-size:20px;color:var(--text-color);"><?= htmlspecialchars($periodoActivo['nombre']) ?></h2>
                        <p style="margin:4px 0 0 0;font-size:13px;color:var(--text-muted);">
                            Vigente desde el <strong><?= htmlspecialchars($periodoActivo['fecha_inicio']) ?></strong>
                            <?= !empty($periodoActivo['fecha_fin'])
                                ? 'hasta el <strong>' . htmlspecialchars($periodoActivo['fecha_fin']) . '</strong>.'
                                : '<strong>sin fecha de fin</strong> (cierre manual).' ?>
                        </p>
                    </div>
                    <span class="status-badge active" style="margin-left:auto;">Activo</span>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                    <div style="background:var(--panel-bg);border:1px solid var(--border-color);padding:12px;border-radius:8px;">
                        <span style="display:block;font-size:12px;color:var(--text-muted);font-weight:600;margin-bottom:4px;">TASA IVA VIGENTE</span>
                        <strong style="font-size:24px;color:var(--primary);"><?= $periodoActivo['tasa_iva'] ?>%</strong>
                        <?php
                            // Buscar nombre del tipo IVA en los disponibles
                            $nombreIvaPeriodo = '';
                            if (!empty($tiposIva)) {
                                foreach ($tiposIva as $ti) {
                                    if ((float)$ti['tasa_iva'] === (float)$periodoActivo['tasa_iva']) {
                                        $nombreIvaPeriodo = $ti['nombre'];
                                        break;
                                    }
                                }
                            }
                        ?>
                        <?php if ($nombreIvaPeriodo): ?>
                            <span style="display:block;font-size:11px;color:var(--text-muted);margin-top:4px;"><?= htmlspecialchars($nombreIvaPeriodo) ?></span>
                        <?php endif; ?>
                    </div>
                    <div style="background:var(--panel-bg);border:1px solid var(--border-color);padding:12px;border-radius:8px;">
                        <span style="display:block;font-size:12px;color:var(--text-muted);font-weight:600;margin-bottom:4px;">ESTADO</span>
                        <strong style="font-size:18px;color:#10b981;display:flex;align-items:center;gap:6px;height:32px;"><i class="fa-solid fa-lock-open"></i> Abierto</strong>
                    </div>
                </div>

                <div style="background:rgba(245,158,11,0.05);border:1px solid rgba(245,158,11,0.2);padding:16px;border-radius:12px;margin-bottom:20px;">
                    <h4 style="color:#d97706;margin:0 0 8px 0;display:flex;align-items:center;gap:6px;"><i class="fa-solid fa-triangle-exclamation"></i> Corte y Respaldo Histórico</h4>
                    <p style="font-size:13px;line-height:1.5;color:var(--text-color);margin:0 0 12px 0;">
                        Al ejecutar el corte, el sistema generará una <strong>captura inmutable (foto)</strong> del inventario y del personal (con sus áreas de trabajo vigentes a la fecha). Este registro quedará congelado en una tabla histórica para auditorías futuras, protegiendo los datos financieros aunque pasen 5 o 6 años y los nombres o tasas cambien.
                    </p>
                    <a href="index.php?route=inv_periodos&action=ejecutarCorte&id=<?= $periodoActivo['id'] ?>" class="btn-primary" style="background:#f59e0b;color:white;text-decoration:none;display:inline-flex;align-items:center;gap:8px;" onclick="return confirm('¿Seguro que desea cerrar el período actual y congelar el respaldo histórico? Esta acción no se puede deshacer.');">
                        <i class="fa-solid fa-lock"></i> Ejecutar Cierre y Respaldo
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Panel Derecho: Crear Nuevo Período -->
    <div class="panel">
        <div class="panel-header">
            <h3>Iniciar Nuevo Período Fiscal</h3>
        </div>
        <form action="index.php?route=inv_periodos&action=guardar" method="POST" style="padding:20px;">
            <div class="form-group">
                <label>Nombre del Período</label>
                <input type="text" name="nombre" required placeholder="Ej: Período Fiscal 2026-B">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label>Fecha de Inicio</label>
                    <input type="date" name="fecha_inicio" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Fecha de Fin <span style="font-weight:400;color:var(--text-muted);">(opcional)</span></label>
                    <input type="date" name="fecha_fin" min="<?= date('Y-m-d') ?>">
                    <small style="display:block;margin-top:5px;color:var(--text-muted);line-height:1.35;">Si queda vacía, el período seguirá abierto hasta ejecutar manualmente el cierre y respaldo.</small>
                </div>
            </div>

            <div class="form-group">
                <label>Tasa de IVA del Período</label>
                <select name="tasa_iva" required>
                    <?php if (!empty($tiposIva)): ?>
                        <?php foreach ($tiposIva as $ti): ?>
                            <option value="<?= $ti['tasa_iva'] ?>" <?= ($ti['tasa_iva'] == 15.0) ? 'selected' : '' ?>>
                                <?= number_format($ti['tasa_iva'], 1) ?>% — <?= htmlspecialchars($ti['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="15.0" selected>15.0% (General Nacional)</option>
                        <option value="8.0">8.0% (Frontera / Turismo)</option>
                        <option value="5.0">5.0% (Tasas de Emergencia)</option>
                        <option value="0.0">0.0% (Exento)</option>
                    <?php endif; ?>
                </select>
                <span style="display:block;font-size:11px;color:var(--text-muted);margin-top:4px;">
                    Las tasas disponibles se gestionan en <a href="index.php?route=inv_maestros&tabla=tipos_iva" style="color:var(--primary);">Maestros → IVA</a>.
                </span>
            </div>

            <button type="submit" class="btn-primary" style="width:100%;"><i class="fa-solid fa-calendar-plus"></i> Guardar y Activar Período</button>
        </form>
    </div>
</div>

<!-- Listado Histórico de Períodos y Respaldos -->
<div class="panel animate-fade-in" style="margin-bottom:24px;">
    <div class="panel-header">
        <h3>Historial de Períodos y Respaldos Congelados</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Período</th>
                    <th>Rango Vigente</th>
                    <th>Tipo de IVA</th>
                    <th>Tasa</th>
                    <th>Estado de Cierre</th>
                    <th>Acciones / Respaldos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inv_periodos as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['nombre']) ?></strong></td>
                        <td><?= htmlspecialchars($p['fecha_inicio']) ?> <?= !empty($p['fecha_fin']) ? 'al ' . htmlspecialchars($p['fecha_fin']) : '· Sin fecha final' ?></td>
                        <td>
                            <?php
                                // Buscar nombre del tipo IVA para este período
                                $nomIvaP = '';
                                if (!empty($tiposIva)) {
                                    foreach ($tiposIva as $ti) {
                                        if ((float)$ti['tasa_iva'] === (float)$p['tasa_iva']) {
                                            $nomIvaP = $ti['nombre'];
                                            break;
                                        }
                                    }
                                }
                            ?>
                            <span style="font-size:12px;color:var(--text-muted);"><?= $nomIvaP ? htmlspecialchars($nomIvaP) : '—' ?></span>
                        </td>
                        <td><strong><?= number_format($p['tasa_iva'], 1) ?>%</strong></td>
                        <td>
                            <?php if ($p['estado'] === 'activo'): ?>
                                <span class="status-badge active">Abierto</span>
                            <?php else: ?>
                                <span class="status-badge inactive"><i class="fa-solid fa-lock"></i> Cerrado e Inmutable</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($p['estado'] === 'cerrado'): ?>
                                <a href="index.php?route=inv_periodos&respaldo_id=<?= $p['id'] ?>#historico" class="btn-primary btn-sm" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                                    <i class="fa-solid fa-box-archive"></i> Ver Respaldo Histórico
                                </a>
                            <?php else: ?>
                                <span style="font-size:12px;color:var(--text-muted);font-style:italic;">Período en curso</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ANCLA DE CONTENIDO HISTÓRICO RESPALDADO -->
<?php if ($respaldoPeriodo): ?>
    <div class="panel animate-fade-in" id="historico" style="border-left: 5px solid #f59e0b;">
        <div class="panel-header" style="background:rgba(245,158,11,0.03);display:flex;justify-content:between;align-items:center;padding:16px 20px;">
            <div>
                <h3 style="color:#d97706;"><i class="fa-solid fa-clock-rotate-left"></i> Foto Histórica: <?= htmlspecialchars($respaldoPeriodo['nombre']) ?></h3>
                <p style="margin:2px 0 0 0;font-size:12px;color:var(--text-muted);">
                    Archivo inmutable de equipos congelado el día <strong><?= count($respaldoItems) > 0 ? $respaldoItems[0]['fecha_corte'] : '' ?></strong> con IVA al <strong><?= $respaldoPeriodo['tasa_iva'] ?>%</strong>.
                </p>
            </div>
            <a href="index.php?route=inv_periodos" class="modal-close" style="margin-left:auto;color:var(--text-color);"><i class="fa-solid fa-xmark"></i></a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Secuencial</th>
                        <th>Nombre Histórico</th>
                        <th>Marca</th>
                        <th>Categoría</th>
                        <th>Zona Histórica</th>
                        <th>Responsable</th>
                        <th>Área Personal Histórica</th>
                        <th>Valor Base</th>
                        <th>IVA Aplicado</th>
                        <th>Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($respaldoItems)): ?>
                        <tr>
                            <td colspan="10" style="text-align:center; padding:40px; color:var(--text-muted);">
                                No se encontraron registros de inventario guardados al momento del corte.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($respaldoItems as $ri): 
                            $vBase = (float)$ri['valor_historico'];
                            $ivaTasa = isset($ri['iva_aplicado']) ? (float)$ri['iva_aplicado'] : (isset($ri['iva_applied']) ? (float)$ri['iva_applied'] : (float)$respaldoPeriodo['tasa_iva']);
                            $vIva = $vBase * ($ivaTasa / 100);
                            $vTotal = $vBase + $vIva;
                        ?>
                            <tr>
                                <td class="secuencial-cell"><?= htmlspecialchars((string)($ri['secuencial'] ?? '')) ?></td>
                                <td><strong><?= htmlspecialchars((string)($ri['nombre_historico'] ?? '')) ?></strong></td>
                                <td><?= htmlspecialchars((string)($ri['marca_historica'] ?? '')) ?></td>
                                <td><span class="cat-badge" style="--cat-color: #64748b"><?= htmlspecialchars((string)($ri['categoria_historica'] ?? '')) ?></span></td>
                                <td><?= htmlspecialchars((string)($ri['zona_historica'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($ri['responsable_historico'] ?? '')) ?></td>
                                <td><span style="font-weight:600;color:var(--primary);"><?= htmlspecialchars((string)($ri['area_talento_historica'] ?? '')) ?></span></td>
                                <td><?= htmlspecialchars(CommonHelper::formatearImporte($vBase)) ?></td>
                                <td><?= isset($ri['iva_aplicado']) ? $ri['iva_aplicado'] : $respaldoPeriodo['tasa_iva'] ?>%</td>
                                <td><strong><?= htmlspecialchars(CommonHelper::formatearImporte($vTotal)) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
