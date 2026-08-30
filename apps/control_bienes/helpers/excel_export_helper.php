<?php
/**
 * Genera un libro HTML compatible con Excel, con presentación corporativa,
 * tipos numéricos y columnas dimensionadas. No requiere dependencias externas.
 */
function exportarExcelEstilizado(string $archivo, string $titulo, string $subtitulo, array $columnas, array $filas): void
{
    $priceDecimals = CommonHelper::decimalesPrecio();
    $amountDecimals = CommonHelper::decimalesImporte();
    $priceMask = '#,##0' . ($priceDecimals ? '.' . str_repeat('0', $priceDecimals) : '');
    $amountMask = '#,##0' . ($amountDecimals ? '.' . str_repeat('0', $amountDecimals) : '');
    $esc = static function ($valor): string {
        $texto = (string)$valor;
        if (preg_match('/^[=+\-@]/', $texto)) $texto = "'" . $texto;
        return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
    };

    $numeroColumnas = max(1, count($columnas));
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $archivo) . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    echo "\xEF\xBB\xBF";
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="UTF-8">';
    echo '<style>'
        . 'body{font-family:Calibri,Arial,sans-serif;color:#172033}'
        . 'table{border-collapse:collapse;table-layout:auto}'
        . '.title{height:34px;background:#123f78;color:#fff;font-size:18pt;font-weight:bold;text-align:left}'
        . '.subtitle{height:25px;background:#eaf2fb;color:#40536b;font-size:10pt;text-align:left}'
        . '.meta{height:22px;background:#f7f9fc;color:#65758b;font-size:9pt;text-align:left}'
        . 'th{height:29px;padding:7px 9px;border:1px solid #9fb4cc;background:#1d5f9f;color:#fff;font-size:10pt;font-weight:bold;text-align:center;vertical-align:middle}'
        . 'td{height:23px;padding:5px 8px;border:1px solid #ccd7e4;font-size:10pt;vertical-align:middle}'
        . '.even td{background:#f2f6fb}.text{mso-number-format:"\\@"}.integer{mso-number-format:"0";text-align:right}.decimal{mso-number-format:"0.00";text-align:right}.price{mso-number-format:"\\$' . $priceMask . '";text-align:right}.currency{mso-number-format:"\\$' . $amountMask . '";text-align:right}.percent{mso-number-format:"0.00%";text-align:right}.date{mso-number-format:"yyyy-mm-dd";text-align:center}'
        . '.footer td{height:26px;background:#eaf2fb;color:#123f78;font-weight:bold}'
        . '</style></head><body><table>';

    foreach ($columnas as $columna) {
        $ancho = max(10, min(42, (int)($columna['ancho'] ?? 18)));
        echo '<col style="width:' . $ancho . 'ch">';
    }
    echo '<tr><td class="title" colspan="' . $numeroColumnas . '">' . $esc($titulo) . '</td></tr>';
    echo '<tr><td class="subtitle" colspan="' . $numeroColumnas . '">' . $esc($subtitulo) . '</td></tr>';
    echo '<tr><td class="meta" colspan="' . $numeroColumnas . '">Generado: ' . $esc(date('Y-m-d H:i')) . ' &nbsp;|&nbsp; Registros: ' . count($filas) . '</td></tr>';
    echo '<tr>';
    foreach ($columnas as $columna) echo '<th>' . $esc($columna['titulo'] ?? '') . '</th>';
    echo '</tr>';

    foreach ($filas as $indice => $fila) {
        echo '<tr class="' . ($indice % 2 ? 'even' : 'odd') . '">';
        foreach ($columnas as $posicion => $columna) {
            $valor = $fila[$posicion] ?? '';
            $tipo = $columna['tipo'] ?? 'text';
            $clase = in_array($tipo, ['integer', 'decimal', 'price', 'currency', 'percent', 'date'], true) ? $tipo : 'text';
            if (in_array($clase, ['integer', 'decimal', 'price', 'currency'], true) && is_numeric($valor)) {
                $atributo = ' x:num="' . (float)$valor . '"';
                if ($clase === 'integer') $visible = number_format((float)$valor, 0, '.', ',');
                elseif ($clase === 'price') $visible = CommonHelper::formatearPrecio($valor);
                elseif ($clase === 'currency') $visible = CommonHelper::formatearImporte($valor);
                else $visible = number_format((float)$valor, 2, '.', ',');
            } elseif ($clase === 'percent' && is_numeric($valor)) {
                $atributo = ' x:num="' . ((float)$valor / 100) . '"';
                $visible = number_format((float)$valor, 2, '.', ',') . '%';
            } else {
                $atributo = '';
                $visible = $valor;
            }
            echo '<td class="' . $clase . '"' . $atributo . '>' . $esc($visible) . '</td>';
        }
        echo '</tr>';
    }

    echo '<tr class="footer"><td colspan="' . $numeroColumnas . '">Fin del reporte · ' . count($filas) . ' registro(s)</td></tr>';
    echo '</table></body></html>';
    exit;
}
