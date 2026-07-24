<?php
/**
 * ReportPdf — Generador de PDF reutilizable (fpdf) para portal_apm.
 *   ReportPdf::tabla()  → reporte tabular (lista) con membrete, encabezado repetido y zebra.
 *   ReportPdf::ficha()  → documento de detalle (clave/valor) por secciones.
 * Ambos envían el PDF inline al navegador y hacen exit.
 *
 * Requiere que fpdf ya esté cargado (require libs/fpdf/fpdf.php) por el llamador.
 */
class ReportPdf
{
    private static function utf(string $s): string
    {
        return (string)(@iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $s) ?: '');
    }

    private static function membrete(FPDF $pdf, string $titulo, string $subtitulo, float $anchoUtil): void
    {
        $logo = ROOT . '/public/img/logoapm.png';
        if (file_exists($logo)) { @$pdf->Image($logo, 10, 8, 20); }
        $pdf->SetXY(32, 9);
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->Cell(0, 6, self::utf('AUTORIDAD PORTUARIA DE MANTA'), 0, 1, 'L');
        $pdf->SetX(32);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(0, 6, self::utf($titulo), 0, 1, 'L');
        if ($subtitulo !== '') {
            $pdf->SetX(32);
            $pdf->SetFont('Arial', '', 8); $pdf->SetTextColor(90, 90, 90);
            $pdf->Cell(0, 5, self::utf($subtitulo), 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0);
        }
        $pdf->SetX(32);
        $pdf->SetFont('Arial', 'I', 8); $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(0, 5, self::utf('Generado: ' . date('d/m/Y H:i')), 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Tabla / listado.
     * @param array $cols  [[titulo, ancho_mm], ...]
     * @param array $rows  [[celda, celda, ...], ...] (strings)
     */
    public static function tabla(string $orient, string $titulo, string $subtitulo, array $cols, array $rows, string $archivo = 'reporte.pdf'): void
    {
        $anchoUtil = ($orient === 'L') ? 277.0 : 190.0;
        $limiteY   = ($orient === 'L') ? 195.0 : 275.0;

        $pdf = new FPDF($orient, 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);

        $head = function () use ($pdf, $titulo, $subtitulo, $cols, $anchoUtil) {
            $pdf->AddPage();
            self::membrete($pdf, $titulo, $subtitulo, $anchoUtil);
            $pdf->Ln(2);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetFillColor(22, 50, 79); $pdf->SetTextColor(255, 255, 255);
            foreach ($cols as $c) { $pdf->Cell($c[1], 7, self::utf($c[0]), 1, 0, 'C', true); }
            $pdf->Ln();
            $pdf->SetTextColor(0, 0, 0); $pdf->SetFont('Arial', '', 7);
        };

        $fit = function (string $s, float $w) use ($pdf): string {
            $s = self::utf($s);
            if ($pdf->GetStringWidth($s) <= $w - 2) return $s;
            while (strlen($s) > 1 && $pdf->GetStringWidth($s . '...') > $w - 2) { $s = substr($s, 0, -1); }
            return $s . '...';
        };

        $head();
        $fill = false;
        foreach ($rows as $row) {
            if ($pdf->GetY() > $limiteY) { $head(); $fill = false; }
            $pdf->SetFillColor(244, 247, 250);
            $vals = array_values($row);
            foreach ($cols as $i => $c) {
                $pdf->Cell($c[1], 6, $fit((string)($vals[$i] ?? ''), $c[1]), 1, 0, 'L', $fill);
            }
            $pdf->Ln();
            $fill = !$fill;
        }
        if (empty($rows)) {
            $pdf->Cell($anchoUtil, 8, self::utf('Sin registros.'), 1, 1, 'C');
        }

        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'I', 7); $pdf->SetTextColor(90, 90, 90);
        $pdf->Cell(0, 5, self::utf('Total de registros: ' . count($rows)), 0, 0, 'R');

        $pdf->Output('I', $archivo);
        exit;
    }

    /**
     * Ficha de detalle (clave/valor) por secciones. Vertical (P).
     * @param array $secciones  [ ['Sección', [ [etiqueta, valor], ... ] ], ... ]
     */
    public static function ficha(string $titulo, string $subtitulo, array $secciones, string $archivo = 'ficha.pdf'): void
    {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        self::membrete($pdf, $titulo, $subtitulo, 190);
        $pdf->SetDrawColor(0, 51, 102); $pdf->SetLineWidth(0.6);
        $pdf->Line(10, $pdf->GetY() + 1, 200, $pdf->GetY() + 1);
        $pdf->Ln(5);

        foreach ($secciones as $sec) {
            [$nombre, $filas] = $sec;
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(220, 230, 241);
            $pdf->Cell(190, 7, self::utf('  ' . $nombre), 0, 1, 'L', true);
            $pdf->Ln(1);
            foreach ($filas as [$et, $val]) {
                $pdf->SetFont('Arial', 'B', 9); $pdf->Cell(55, 6, self::utf($et), 0, 0, 'L');
                $pdf->SetFont('Arial', '', 9);
                $pdf->MultiCell(135, 6, self::utf((string)$val !== '' ? (string)$val : '—'), 0, 'L');
            }
            $pdf->Ln(3);
        }

        $pdf->Output('I', $archivo);
        exit;
    }
}
