<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acta de Ingreso a Bodega - <?= htmlspecialchars($ingreso['secuencial']) ?></title>
    <!-- Google Fonts: Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #cbd5e1;
            --bg-light: #f8fafc;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            color: var(--text-main);
            background: #ffffff;
            line-height: 1.5;
            padding: 40px;
            font-size: 13px;
        }

        .acta-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid var(--border);
            padding: 40px;
            border-radius: 12px;
            background: #ffffff;
            position: relative;
        }

        /* Encabezado */
        .acta-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            font-size: 24px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-title h2 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--primary);
        }

        .logo-title p {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .acta-meta {
            text-align: right;
        }

        .acta-title {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .acta-code {
            display: inline-block;
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            background: rgba(37, 99, 235, 0.08);
            padding: 4px 10px;
            border-radius: 6px;
            font-family: monospace;
        }

        /* Información Principal */
        .info-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            background: var(--bg-light);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .info-item {
            margin-bottom: 12px;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            display: block;
        }

        .info-value {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-main);
        }

        /* Tabla de Ítems */
        .items-title {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-main);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        th {
            background: var(--primary);
            color: #ffffff;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            padding: 10px 12px;
            text-align: left;
        }

        th:nth-child(3), td:nth-child(3) {
            text-align: center;
        }

        th:nth-child(4), td:nth-child(4),
        th:nth-child(5), td:nth-child(5) {
            text-align: right;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            font-size: 12px;
        }

        tr:last-child td {
            border-bottom: 2px solid var(--primary);
        }

        .item-code {
            font-family: monospace;
            font-weight: 600;
            color: var(--text-muted);
        }

        .item-desc strong {
            display: block;
            color: var(--text-main);
        }

        .item-desc span {
            font-size: 10px;
            color: var(--text-muted);
        }

        /* Totalización */
        .total-box {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 35px;
        }

        .total-card {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .total-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .total-val {
            font-size: 18px;
            font-weight: 800;
            color: #10b981;
        }

        /* Observaciones */
        .obs-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 40px;
        }

        .obs-title {
            font-size: 11px;
            font-weight: 700;
            color: #b45309;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .obs-content {
            font-size: 12px;
            color: #78350f;
            line-height: 1.4;
        }

        /* Sección de Firmas */
        .signatures-section {
            margin-top: 60px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
        }

        .signature-card {
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid var(--text-main);
            margin-bottom: 8px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .signature-role {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }

        .signature-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-main);
        }

        /* Botón de Control Flotante */
        .print-btn-float {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 14px 24px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 10px 15px -3px rgba(37,99,235,0.4);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 1000;
        }

        .print-btn-float:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Ajustes de Impresión */
        @media print {
            body {
                padding: 0;
                background: #ffffff;
            }
            .acta-container {
                border: none;
                padding: 0;
                max-width: 100%;
            }
            .print-btn-float {
                display: none;
            }
        }
    </style>
</head>
<body>

    <!-- Botón Flotante para Imprimir -->
    <button class="print-btn-float" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir Acta</button>

    <div class="acta-container">
        <!-- Encabezado -->
        <div class="acta-header">
            <div class="logo-section">
                <div class="logo-icon"><i class="fa-solid fa-anchor"></i></div>
                <div class="logo-title">
                    <h2>APM PORTUARIO</h2>
                    <p>Terminal de Carga General</p>
                </div>
            </div>
            <div class="acta-meta">
                <div class="acta-title">ACTA DE INGRESO A BODEGA</div>
                <div class="acta-code"><?= htmlspecialchars($ingreso['secuencial']) ?></div>
            </div>
        </div>

        <!-- Información General -->
        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Proveedor / Origen</span>
                    <span class="info-value"><?= htmlspecialchars($ingreso['proveedor']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Responsable de Recepción</span>
                    <span class="info-value"><?= htmlspecialchars($ingreso['responsable']) ?> (CI: <?= htmlspecialchars($ingreso['responsable_identificacion'] ?? '----------') ?>)</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Fecha de Ingreso</span>
                    <span class="info-value"><?= date('d/m/Y', strtotime($ingreso['fecha'])) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Generado Por</span>
                    <span class="info-value"><?= htmlspecialchars($ingreso['creado_por']) ?></span>
                </div>
            </div>
        </div>

        <!-- Título de Ítems -->
        <div class="items-title">
            <i class="fa-solid fa-boxes-stacked" style="color:var(--primary);"></i> Detalle de Bienes e Insumos Ingresados
        </div>

        <!-- Tabla -->
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Código</th>
                    <th style="width: 45%;">Descripción del Ítem</th>
                    <th style="width: 12%;">Cantidad</th>
                    <th style="width: 13%;">V. Unitario</th>
                    <th style="width: 15%;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalCalculado = 0.0;
                foreach ($ingreso['detalles'] as $det): 
                    $subtotal = (int)$det['cantidad'] * (float)$det['valor_unitario'];
                    $totalCalculado += $subtotal;
                ?>
                    <tr>
                        <td class="item-code"><?= htmlspecialchars($det['item_secuencial']) ?></td>
                        <td class="item-desc">
                            <strong><?= htmlspecialchars($det['item_nombre']) ?></strong>
                            <span>Categoría: <?= htmlspecialchars($det['item_categoria']) ?> | Marca: <?= htmlspecialchars($det['item_marca']) ?></span>
                        </td>
                        <td><?= $det['cantidad'] ?> unid.</td>
                        <td>$<?= number_format($det['valor_unitario'], 2) ?></td>
                        <td style="font-weight: 600;">$<?= number_format($subtotal, 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Valor Total -->
        <div class="total-box">
            <div class="total-card">
                <span class="total-label">Total Custodia Ingresada</span>
                <span class="total-val">$<?= number_format($totalCalculado, 2) ?></span>
            </div>
        </div>

        <!-- Observaciones -->
        <?php if (!empty($ingreso['observaciones'])): ?>
            <div class="obs-box">
                <div class="obs-title"><i class="fa-solid fa-circle-info"></i> Observaciones Adicionales</div>
                <div class="obs-content"><?= nl2br(htmlspecialchars($ingreso['observaciones'])) ?></div>
            </div>
        <?php endif; ?>

        <!-- Sección de Firmas -->
        <div class="signatures-section">
            <div class="signature-card">
                <div style="height: 60px;"></div>
                <div class="signature-line"></div>
                <div class="signature-name"><?= htmlspecialchars($ingreso['proveedor']) ?></div>
                <div class="signature-role">Entregado por (Proveedor)</div>
            </div>
            
            <div class="signature-card">
                <div style="height: 60px;"></div>
                <div class="signature-line"></div>
                <div class="signature-name"><?= htmlspecialchars($ingreso['responsable']) ?></div>
                <div class="signature-role">Recibido por (Bodega)</div>
            </div>

            <div class="signature-card">
                <div style="height: 60px;"></div>
                <div class="signature-line"></div>
                <div class="signature-name">Supervisión Bodega</div>
                <div class="signature-role">Autorizado por</div>
            </div>
        </div>
    </div>

    <!-- Script de impresión automática -->
    <script>
        window.addEventListener('load', () => {
            setTimeout(() => {
                window.print();
            }, 800);
        });
    </script>
</body>
</html>
