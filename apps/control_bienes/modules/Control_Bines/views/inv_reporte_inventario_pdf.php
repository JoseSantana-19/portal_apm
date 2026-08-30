<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Consolidado de Inventario - APM Portuario</title>
    <!-- Google Fonts: Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-blue: #1e3a8a;
            --secondary-blue: #3b82f6;
            --text-dark: #0f172a;
            --text-muted: #475569;
            --border-color: #cbd5e1;
            --bg-light: #f8fafc;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            color: var(--text-dark);
            background: #ffffff;
            line-height: 1.4;
            padding: 30px;
            font-size: 11px;
        }

        /* Contenedor A4 Horizontal */
        .report-container {
            max-width: 1120px;
            margin: 0 auto;
            background: #ffffff;
        }

        /* Encabezado */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid var(--primary-blue);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .brand-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .brand-logo {
            height: 55px;
            object-fit: contain;
        }

        .brand-logo-fallback {
            width: 50px;
            height: 50px;
            background: rgba(30, 58, 138, 0.08);
            border-radius: 8px;
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }

        .brand-title h2 {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary-blue);
            letter-spacing: -0.5px;
        }

        .brand-title p {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            font-weight: 700;
            margin-top: 1px;
        }

        .meta-section {
            text-align: right;
        }

        .meta-section h1 {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 3px;
        }

        .meta-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            color: var(--primary-blue);
            background: rgba(59, 130, 246, 0.1);
            padding: 3px 8px;
            border-radius: 4px;
        }

        .meta-date {
            font-size: 9px;
            color: var(--text-muted);
            margin-top: 4px;
            font-weight: 500;
        }

        /* Tabla de Parámetros de Auditoría y Filtros */
        .parameters-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
            border: 1px solid var(--border-color);
        }

        .parameters-table th {
            background: var(--bg-light);
            color: var(--primary-blue);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 8.5px;
            padding: 6px 12px;
            text-align: left;
            border: 1px solid var(--border-color);
            width: 15%;
            letter-spacing: 0.2px;
        }

        .parameters-table td {
            padding: 6px 12px;
            border: 1px solid var(--border-color);
            color: var(--text-dark);
            width: 35%;
            background: #ffffff;
            font-size: 9px;
            vertical-align: middle;
        }

        .parameters-table td strong {
            color: var(--primary-blue);
        }

        .parameters-table .badge-info {
            display: inline-block;
            font-size: 8.5px;
            background: rgba(30, 58, 138, 0.06);
            color: var(--primary-blue);
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: 600;
            margin-right: 4px;
            border: 1px solid rgba(30, 58, 138, 0.12);
        }


        /* Tabla de InvInventario */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 10px;
        }

        th {
            background: var(--primary-blue);
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            padding: 8px 10px;
            text-align: left;
            border: 1px solid var(--primary-blue);
        }

        td {
            padding: 7px 10px;
            border: 1px solid var(--border-color);
            vertical-align: middle;
        }

        tr:nth-child(even) {
            background-color: var(--bg-light);
        }

        .secuencial-cell {
            font-family: monospace;
            font-weight: 700;
            color: var(--primary-blue);
        }

        .item-title {
            font-weight: 700;
            color: var(--text-dark);
        }

        .item-brand {
            font-size: 8.5px;
            color: var(--text-muted);
            display: block;
            margin-top: 1px;
        }

        /* Badges de Categoría y Estado */
        .cat-badge {
            display: inline-block;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 4px;
            background: #e2e8f0;
            color: #475569;
        }

        .status-badge {
            display: inline-block;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .status-badge.estado-operativo { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
        .status-badge.estado-mantenimiento { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
        .status-badge.estado-desuso { background: rgba(239, 68, 68, 0.1); color: var(--danger-color); }
        .status-badge.estado-transito { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

        /* Footers con totales */
        tfoot tr {
            background: rgba(30, 58, 138, 0.05) !important;
            font-weight: 700;
            border-top: 2px solid var(--primary-blue);
        }

        tfoot td {
            font-size: 11px;
            border-top: 2px solid var(--primary-blue);
        }

        /* Sección de Firmas */
        .signatures-section {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 40px;
            page-break-inside: avoid;
        }

        .signature-card {
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid var(--text-dark);
            margin-bottom: 6px;
            width: 75%;
            margin-left: auto;
            margin-right: auto;
        }

        .signature-role {
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }

        .signature-name {
            font-size: 10.5px;
            font-weight: 600;
            color: var(--text-dark);
        }

        /* Botones de Control Flotantes */
        .print-control-bar {
            position: fixed;
            bottom: 25px;
            right: 25px;
            display: flex;
            gap: 12px;
            z-index: 1000;
        }

        .btn-float {
            background: var(--primary-blue);
            color: #ffffff;
            border: none;
            padding: 12px 20px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 6px 12px rgba(30,58,138,0.3);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-float:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(30,58,138,0.4);
        }

        .btn-float.btn-secondary {
            background: #64748b;
            box-shadow: 0 6px 12px rgba(100,116,139,0.3);
        }

        .btn-float.btn-secondary:hover {
            box-shadow: 0 8px 16px rgba(100,116,139,0.4);
        }

        /* Ajustes de Impresión en Papel / PDF */
        @media print {
            body {
                padding: 0;
                background: #ffffff;
            }
            .report-container {
                max-width: 100%;
                width: 100%;
                border: none;
            }
            .print-control-bar {
                display: none;
            }
            
            /* Evitar cortes feos */
            tr {
                page-break-inside: avoid;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }
        }

        /* Tamaño A4 Landscape */
        @page {
            size: A4 landscape;
            margin: 1.2cm 1.5cm;
        }
    </style>
</head>
<body>

    <!-- Barra de Control Flotante -->
    <div class="print-control-bar">
        <a href="index.php?route=inventario" class="btn-float btn-secondary"><i class="fa-solid fa-arrow-left"></i> Regresar</a>
        <button class="btn-float" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir Reporte</button>
    </div>

    <div class="report-container">
        <!-- Encabezado Institucional -->
        <div class="report-header">
            <div class="brand-section">
                <?php if (file_exists(__DIR__ . '/../../logoapm.png')): ?>
                    <img src="logoapm.png" class="brand-logo" alt="APM Logo">
                <?php else: ?>
                    <div class="brand-logo-fallback"><i class="fa-solid fa-anchor"></i></div>
                <?php endif; ?>
                <div class="brand-title">
                    <h2>AUTORIDAD PORTUARIA DE MANTA</h2>
                    <p>Subgerencia de Logística y Custodia de Bodega</p>
                </div>
            </div>
            <div class="meta-section">
                <h1>REPORTE CONSOLIDADO DEL INVENTARIO</h1>
                <div class="meta-badge">Período Fiscal: <?= htmlspecialchars($periodoActivo['nombre']) ?></div>
                <div class="meta-date">Generado el: <?= date('d/m/Y H:i:s') ?></div>
            </div>
        </div>

        <?php
            // Calcular estadísticas reales del conjunto filtrado para la tabla de parámetros y el pie de tabla
            $totalBienes = count($items);
            $conOperativo = 0;
            $conMantenimiento = 0;
            $conTransito = 0;
            $valorBaseSuma = 0.0;
            $ivaCalculadoSuma = 0.0;

            foreach ($items as $itm) {
                $valorBaseSuma += (float)$itm['valor'];
                if (isset($itm['producto_aplica_iva']) && (int)$itm['producto_aplica_iva'] === 1) {
                    $ivaCalculadoSuma += (float)$itm['valor'] * ($tasaIva / 100);
                }
                if ($itm['estado'] === 'Operativo') $conOperativo++;
                elseif ($itm['estado'] === 'En Mantenimiento') $conMantenimiento++;
                elseif ($itm['estado'] === 'En Tránsito') $conTransito++;
            }

            $valorTotalSuma = $valorBaseSuma + $ivaCalculadoSuma;
        ?>

        <!-- Tabla de Parámetros del Reporte (Administrativa y de Auditoría) -->
        <table class="parameters-table">
            <tr>
                <th>Período Fiscal</th>
                <td><?= htmlspecialchars($periodoActivo['nombre']) ?></td>
                <th>Fecha de Emisión</th>
                <td><?= date('d/m/Y H:i:s') ?></td>
            </tr>
            <tr>
                <th>Tasa IVA Aplicable</th>
                <td><?= htmlspecialchars($tasaIva) ?>%</td>
                <th>Generado Por</th>
                <td><?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? 'Custodio de Bodega') ?></td>
            </tr>
            <tr>
                <th>Filtros de Auditoría</th>
                <td>
                    <?php
                    $pills = [];
                    if (!empty($filtros['categoria'])) {
                        $catNombre = $filtros['categoria'];
                        foreach ($items as $itm) {
                            if ($itm['categoria_id'] == $filtros['categoria'] || $itm['categoria'] == $filtros['categoria']) {
                                $catNombre = $itm['categoria'];
                                break;
                            }
                        }
                        $pills[] = '<span class="badge-info">Categoría:</span>' . htmlspecialchars($catNombre);
                    }
                    if (!empty($filtros['zona'])) {
                        $zonaNombre = $filtros['zona'];
                        foreach ($items as $itm) {
                            if ($itm['zona_id'] == $filtros['zona'] || $itm['zona'] == $filtros['zona']) {
                                $zonaNombre = $itm['zona'];
                                break;
                            }
                        }
                        $pills[] = '<span class="badge-info">Zona/Terminal:</span>' . htmlspecialchars($zonaNombre);
                    }
                    if (!empty($filtros['estado'])) {
                        $estadoNombre = $filtros['estado'];
                        foreach ($items as $itm) {
                            if ($itm['estado_id'] == $filtros['estado'] || $itm['estado'] == $filtros['estado']) {
                                $estadoNombre = $itm['estado'];
                                break;
                            }
                        }
                        $pills[] = '<span class="badge-info">Estado:</span>' . htmlspecialchars($estadoNombre);
                    }
                    if (!empty($filtros['termino'])) {
                        $pills[] = '<span class="badge-info">Búsqueda:</span>"' . htmlspecialchars($filtros['termino']) . '"';
                    }
                    
                    if (empty($pills)) {
                        echo '<span style="color: var(--text-muted); font-style: italic;">Ninguno (Todo el Inventario)</span>';
                    } else {
                        echo implode(" &nbsp;|&nbsp; ", $pills);
                    }
                    ?>
                </td>
                <th>Resumen Consolidado</th>
                <td>
                    <strong>Equipos:</strong> <?= $totalBienes ?> &nbsp;|&nbsp; 
                    <strong>Operativos:</strong> <?= $conOperativo ?> &nbsp;|&nbsp; 
                    <strong>Mantenimiento:</strong> <?= $conMantenimiento ?> &nbsp;|&nbsp; 
                    <strong>Tránsito:</strong> <?= $conTransito ?>
                </td>
            </tr>
        </table>

        <!-- Tabla Principal -->
        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">Secuencial</th>
                    <th style="width: 25%;">Descripción del Equipo / Contenedor</th>
                    <th style="width: 12%;">Categoría</th>
                    <th style="width: 13%;">Ubicación / Zona</th>
                    <th style="width: 15%;">Responsable Custodio</th>
                    <th style="width: 9%;">Estado</th>
                    <th style="width: 9%; text-align: right;">Costo Base</th>
                    <th style="width: 9%; text-align: right;">IVA (<?= $tasaIva ?>%)</th>
                    <th style="width: 10%; text-align: right;">Costo Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center; padding:30px; color:var(--text-muted);">
                            <i class="fa-solid fa-inbox" style="font-size:24px; display:block; margin-bottom:8px; opacity:0.4;"></i>
                            No se encontraron registros de inventario bajo los criterios especificados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): 
                        $valBase = (float)$item['valor'];
                        $valIva = (isset($item['producto_aplica_iva']) && (int)$item['producto_aplica_iva'] === 1)
                            ? $valBase * ($tasaIva / 100)
                            : 0.0;
                        $valTotal = $valBase + $valIva;
                    ?>
                        <tr>
                            <td class="secuencial-cell"><?= htmlspecialchars($item['producto_codigo'] ?? $item['secuencial']) ?></td>
                            <td>
                                <span class="item-title"><?= htmlspecialchars($item['nombre']) ?></span>
                                <span class="item-brand">Marca: <?= htmlspecialchars($item['marca']) ?></span>
                            </td>
                            <td><span class="cat-badge"><?= htmlspecialchars($item['categoria']) ?></span></td>
                            <td><?= htmlspecialchars($item['zona']) ?></td>
                            <td><?= htmlspecialchars($item['responsable'] ?: 'Sin Responsable Asignado') ?></td>
                            <td><span class="status-badge <?= htmlspecialchars((string)($item['estadoClase'] ?? 'inactive')) ?>"><?= htmlspecialchars((string)($item['estado'] ?? 'Desconocido')) ?></span></td>
                            <td style="text-align: right; font-weight: 500;"><?= htmlspecialchars(CommonHelper::formatearPrecio($valBase)) ?></td>
                            <td style="text-align: right; color: var(--text-muted);"><?= htmlspecialchars(CommonHelper::formatearImporte($valIva)) ?></td>
                            <td style="text-align: right; font-weight: 700; color: var(--primary-blue);"><?= htmlspecialchars(CommonHelper::formatearImporte($valTotal)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($items)): ?>
                <tfoot>
                    <tr>
                        <td colspan="6" style="text-align: right; font-weight: 700; letter-spacing: 0.5px;">VALORES TOTALES ACUMULADOS:</td>
                        <td style="text-align: right; font-weight: 700;"><?= htmlspecialchars(CommonHelper::formatearImporte($valorBaseSuma)) ?></td>
                        <td style="text-align: right; font-weight: 700; color: var(--text-muted);"><?= htmlspecialchars(CommonHelper::formatearImporte($ivaCalculadoSuma)) ?></td>
                        <td style="text-align: right; font-weight: 800; color: var(--primary-blue); font-size: 11.5px;"><?= htmlspecialchars(CommonHelper::formatearImporte($valorTotalSuma)) ?></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>

        <!-- Firmas Oficiales de Auditoría -->
        <div class="signatures-section">
            <div class="signature-card">
                <div style="height: 55px;"></div>
                <div class="signature-line"></div>
                <div class="signature-name"><?= htmlspecialchars($_SESSION['usuario']['nombre'] ?? 'Responsable de Bodega') ?></div>
                <div class="signature-role">Preparado por (Custodio de Bienes)</div>
            </div>
            
            <div class="signature-card">
                <div style="height: 55px;"></div>
                <div class="signature-line"></div>
                <div class="signature-name">Supervisión de Auditoría Interna</div>
                <div class="signature-role">Revisado y Validado por</div>
            </div>

            <div class="signature-card">
                <div style="height: 55px;"></div>
                <div class="signature-line"></div>
                <div class="signature-name">Dirección de Logística Portuaria</div>
                <div class="signature-role">Aprobado y Autorizado por</div>
            </div>
        </div>
    </div>

    <!-- Script de Autodisparo del diálogo de impresión -->
    <script>
        window.addEventListener('load', () => {
            setTimeout(() => {
                window.print();
            }, 800);
        });
    </script>
</body>
</html>
