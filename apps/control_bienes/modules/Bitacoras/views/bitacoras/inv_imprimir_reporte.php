<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Oficial - SysPort | APM Manta</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
            padding: 30px;
            background: #ffffff;
        }
        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .report-header img {
            height: 48px;
        }
        .report-title {
            text-align: right;
        }
        .report-title h1 {
            font-size: 16px;
            margin: 0 0 4px 0;
            color: #1e3a8a;
            font-weight: 700;
            text-transform: uppercase;
        }
        .report-title p {
            margin: 0;
            font-size: 10px;
            color: #64748b;
            font-weight: 500;
        }
        .report-metadata {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 25px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .metadata-item {
            font-size: 10px;
        }
        .metadata-item strong {
            color: #475569;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 10px;
        }
        th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            border-bottom: 2px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        tr:nth-child(even) {
            background: #f8fafc;
        }
        .total-row {
            font-weight: 700;
            background: #e2e8f0 !important;
            border-top: 2px solid #cbd5e1;
        }
        .badge {
            background: #e2e8f0;
            color: #334155;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 600;
            display: inline-block;
        }
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .badge-transit { background: #e0f2fe; color: #0369a1; }
        .badge-dispatched { background: #e0e7ff; color: #4338ca; }
        
        .signatures {
            margin-top: 80px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            margin: 0 40px;
            font-size: 10px;
            font-weight: 600;
            color: #475569;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
        .print-btn-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-print {
            background: #3b82f6;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            font-size: 12px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn-print:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>

    <div class="print-btn-container no-print">
        <button class="btn-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Mandar a Imprimir / Guardar PDF</button>
    </div>

    <!-- Encabezado Institucional -->
    <div class="report-header">
        <div style="font-size: 22px; font-weight: 700; color: #1e3a8a;">SysPort | APM</div>
        <div class="report-title">
            <h1>Reporte de Auditoría Interna</h1>
            <p>Autoridad Portuaria de Manta - Terminal Portuario de Manta</p>
        </div>
    </div>

    <!-- Metadatos de la Emisión -->
    <div class="report-metadata">
        <div class="metadata-item">
            <strong>Tipo de Reporte:</strong> 
            <?php
            $titulos = [
                'proveedores' => 'Listado General de Proveedores Oficiales',
                'centros_consumo' => 'Listado de Centros de Consumo (Áreas y Puestos)',
                'items' => 'Listado de Items y Valorización de Stock en Inventario',
                'compras' => 'Listado Consolidado de Compras a Proveedores',
                'mensual' => 'Reporte Mensual de Órdenes de Compras y Presupuesto',
            ];
            echo isset($titulos[$tabActivo]) ? $titulos[$tabActivo] : $tabActivo;
            ?>
        </div>
        <div class="metadata-item" style="text-align: right;">
            <strong>Fecha de Emisión:</strong> <?= date('Y-m-d H:i:s') ?>
        </div>
        <div class="metadata-item">
            <strong>Período Auditado:</strong> 
            <?php if (!empty($fechaInicio) && !empty($fechaFin)): ?>
                <?= htmlspecialchars($fechaInicio) ?> al <?= htmlspecialchars($fechaFin) ?>
            <?php else: ?>
                Histórico Completo
            <?php endif; ?>
            <?php if (!empty($idInicio) || !empty($idFin)): ?>
                | <strong>IDs Filtrados:</strong> #<?= htmlspecialchars($idInicio ?: '1') ?> al #<?= htmlspecialchars($idFin ?: 'Max') ?>
            <?php endif; ?>
        </div>
        <div class="metadata-item" style="text-align: right;">
            <strong>Usuario Emisor:</strong> <?= isset($_SESSION['usuario']['nombre']) ? htmlspecialchars($_SESSION['usuario']['nombre']) : 'Admin Terminal' ?>
        </div>
    </div>

    <!-- Tabla de Contenido -->
    <table>

        <!-- 1. Reporte: Proveedores -->
        <?php if ($tabActivo === 'proveedores'): ?>
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th>Razón Social / Nombre Comercial</th>
                    <th style="width: 140px;">RUC / Identificación</th>
                    <th>Dirección y Detalles de Contacto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($datosReporte as $r): ?>
                    <tr>
                        <td><strong>#<?= $r['id'] ?></strong></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($r['nombre']) ?></td>
                        <td><code><?= htmlspecialchars($r['ruc']) ?></code></td>
                        <td><?= htmlspecialchars($r['extra'] ?? 'Sin contacto registrado') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        <!-- 2. Reporte: Centros de Consumo -->
        <?php elseif ($tabActivo === 'centros_consumo'): ?>
            <thead>
                <tr>
                    <th style="width: 80px;">Código</th>
                    <th>Descripción / Puesto del Centro</th>
                    <th>Funcionario Responsable</th>
                    <th>Grupo Organizativo</th>
                    <th style="text-align:center; width: 100px;">Despachos</th>
                    <th style="text-align:center; width: 100px;">Unids Entregadas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($datosReporte as $r): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($r['codigo']) ?></code></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($r['nombre']) ?></td>
                        <td><?= htmlspecialchars($r['funcionario_actual'] ?? $r['funcionario']) ?></td>
                        <td><?= htmlspecialchars($r['grupo_nombre']) ?> (<?= htmlspecialchars($r['grupo_codigo']) ?>)</td>
                        <td style="text-align:center;"><?= $r['total_egresos'] ?></td>
                        <td style="text-align:center; font-weight:700;"><?= $r['total_items'] ?> u.</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        <!-- 3. Reporte: Items -->
        <?php elseif ($tabActivo === 'items'): ?>
            <thead>
                <tr>
                    <th style="width: 90px;">Secuencial</th>
                    <th>Nombre de Insumo</th>
                    <th>Marca</th>
                    <th>Categoría</th>
                    <th style="text-align:center; width: 90px;">Stock</th>
                    <th style="text-align:right; width: 100px;">Costo Base</th>
                    <th style="text-align:right; width: 120px;">Valor Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sumStock = 0;
                $sumValor = 0.0;
                foreach ($datosReporte as $r): 
                    $sumStock += (int)$r['cantidad'];
                    $sumValor += (float)($r['cantidad'] * $r['valor']);
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['secuencial']) ?></strong></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($r['nombre']) ?></td>
                        <td><?= htmlspecialchars($r['marca']) ?></td>
                        <td><?= htmlspecialchars($r['categoria_nombre']) ?></td>
                        <td style="text-align:center; font-weight:700;"><?= $r['cantidad'] ?> <?= htmlspecialchars($r['unidad_abreviatura'] ?? 'u.') ?></td>
                        <td style="text-align:right;">$<?= number_format($r['valor'], 2) ?></td>
                        <td style="text-align:right; font-weight:700; color:#1e3a8a;">$<?= number_format($r['cantidad'] * $r['valor'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="4" style="text-align:right;">Totales Consolidados de Bodega:</td>
                    <td style="text-align:center;"><?= number_format($sumStock) ?> u.</td>
                    <td></td>
                    <td style="text-align:right;">$<?= number_format($sumValor, 2) ?></td>
                </tr>
            </tbody>

        <!-- 4. Reporte: Compras -->
        <?php elseif ($tabActivo === 'compras'): ?>
            <thead>
                <tr>
                    <th style="width: 90px;">Código</th>
                    <th style="width: 90px;">Fecha</th>
                    <th>Proveedor</th>
                    <th>Producto / Insumo</th>
                    <th style="text-align:center; width: 80px;">Cant.</th>
                    <th style="text-align:right; width: 100px;">V. Unitario</th>
                    <th style="text-align:right; width: 110px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sumCant = 0;
                $sumSub  = 0.0;
                foreach ($datosReporte as $r): 
                    $sumCant += (int)$r['cantidad'];
                    $sumSub  += (float)$r['subtotal'];
                ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['ingreso_codigo']) ?></strong></td>
                        <td><?= htmlspecialchars($r['fecha']) ?></td>
                        <td><?= htmlspecialchars($r['proveedor']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($r['item_nombre']) ?></strong>
                            <span style="font-size: 8px; color: #64748b; display:block;"><?= htmlspecialchars($r['item_secuencial']) ?></span>
                        </td>
                        <td style="text-align:center;"><?= $r['cantidad'] ?> <?= htmlspecialchars($r['unidad'] ?? 'u.') ?></td>
                        <td style="text-align:right;">$<?= number_format($r['valor_unitario'], 2) ?></td>
                        <td style="text-align:right; font-weight:700;">$<?= number_format($r['subtotal'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="4" style="text-align:right;">Valoración Total Adquirida:</td>
                    <td style="text-align:center;"><?= number_format($sumCant) ?> u.</td>
                    <td></td>
                    <td style="text-align:right; color:#10b981;">$<?= number_format($sumSub, 2) ?></td>
                </tr>
            </tbody>

        <!-- 5. Reporte: Mensual -->
        <?php elseif ($tabActivo === 'mensual'): ?>
            <thead>
                <tr>
                    <th>Mes Fiscal de Operación</th>
                    <th style="text-align:center; width: 140px;">Órdenes Emitidas</th>
                    <th style="text-align:center; width: 140px;">Volumen de Items</th>
                    <th style="text-align:right; width: 160px;">Presupuesto Consumido ($)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sumOrd  = 0;
                $sumVol  = 0;
                $sumVal  = 0.0;
                foreach ($datosReporte as $r): 
                    $sumOrd += (int)$r['total_ordenes'];
                    $sumVol += (int)$r['total_items'];
                    $sumVal += (float)$r['total_valor'];
                ?>
                    <tr>
                        <td style="font-weight:700; font-size:11px;">
                            <?php 
                            $meses = ['01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio','07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'];
                            $parts = explode('-', $r['mes']);
                            echo isset($meses[$parts[1]]) ? $meses[$parts[1]] . ' ' . $parts[0] : $r['mes'];
                            ?>
                        </td>
                        <td style="text-align:center;"><?= $r['total_ordenes'] ?> órdenes</td>
                        <td style="text-align:center;"><?= number_format($r['total_items']) ?> unids</td>
                        <td style="text-align:right; font-weight:700;">$<?= number_format($r['total_valor'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td style="padding:12px;">Acumulado del Período:</td>
                    <td style="text-align:center;"><?= number_format($sumOrd) ?> ord.</td>
                    <td style="text-align:center;"><?= number_format($sumVol) ?> u.</td>
                    <td style="text-align:right; color:#10b981;">$<?= number_format($sumVal, 2) ?></td>
                </tr>
            </tbody>
        <?php endif; ?>

    </table>

    <!-- Bloque de Firmas y Validación -->
    <div class="signatures">
        <div>
            <div style="height: 50px;"></div>
            <div class="signature-line">
                Responsable de Bodega General<br>
                <span style="font-size: 8px; font-weight: 500; color: #64748b;">SysPort Terminal Portuario</span>
            </div>
        </div>
        <div>
            <div style="height: 50px;"></div>
            <div class="signature-line">
                Director de Control de Inventarios APM<br>
                <span style="font-size: 8px; font-weight: 500; color: #64748b;">Autoridad Portuaria de Manta</span>
            </div>
        </div>
    </div>

    <script>
        // Imprimir automáticamente al cargar si no está en la vista no-print
        window.addEventListener('load', () => {
            setTimeout(() => {
                // window.print();
            }, 500);
        });
    </script>
</body>
</html>
