<?php
/**
 * XlsxWriter — Generador mínimo de archivos .xlsx (Office Open XML) SIN dependencias
 * (no requiere Composer ni la extensión ZipArchive). Construye el paquete ZIP a mano
 * usando el método "stored" (sin compresión) + CRC32, produciendo un .xlsx válido que
 * Excel/LibreOffice abren de forma nativa (sin avisos de formato).
 *
 * Uso:
 *   $x = new XlsxWriter('Auditoría');
 *   $x->setColumns([['Fecha',22], ['Usuario',26], ...]);   // encabezados + ancho
 *   $x->addRow(['2026-07-07 10:00', 'admin', ...]);          // filas (strings)
 *   $bin = $x->build();                                      // string binario .xlsx
 */
class XlsxWriter
{
    private string $sheetName;
    private array $columns = [];   // [ [titulo, ancho], ... ]
    private array $rows = [];      // [ [c1,c2,...], ... ]

    public function __construct(string $sheetName = 'Hoja1')
    {
        $this->sheetName = mb_substr(preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $sheetName), 0, 31);
    }

    public function setColumns(array $columns): void { $this->columns = $columns; }
    public function addRow(array $cells): void { $this->rows[] = $cells; }

    public function build(): string
    {
        $files = [
            '[Content_Types].xml'        => $this->contentTypes(),
            '_rels/.rels'                => $this->rels(),
            'xl/workbook.xml'            => $this->workbook(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRels(),
            'xl/styles.xml'              => $this->styles(),
            'xl/worksheets/sheet1.xml'   => $this->sheet(),
        ];
        return $this->zip($files);
    }

    /* ── Partes XML ─────────────────────────────────────────────────────── */

    private function esc(string $s): string
    {
        return str_replace(["&","<",">","\"","'"], ["&amp;","&lt;","&gt;","&quot;","&apos;"], $s);
    }

    private function colLetter(int $i): string   // 0 → A, 26 → AA
    {
        $s = '';
        $i++;
        while ($i > 0) { $m = ($i - 1) % 26; $s = chr(65 + $m) . $s; $i = intdiv($i - 1, 26); }
        return $s;
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->esc($this->sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function styles(): string
    {
        // s=0 normal; s=1 encabezado (negrita, fondo azul, texto blanco)
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            .   '<font><sz val="11"/><name val="Calibri"/></font>'
            .   '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            .   '<fill><patternFill patternType="none"/></fill>'
            .   '<fill><patternFill patternType="gray125"/></fill>'
            .   '<fill><patternFill patternType="solid"><fgColor rgb="FF16324F"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .   '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"><alignment vertical="center"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function cell(string $ref, string $value, int $style = 0): string
    {
        $s = $style ? ' s="' . $style . '"' : '';
        return '<c r="' . $ref . '"' . $s . ' t="inlineStr"><is><t xml:space="preserve">'
            . $this->esc($value) . '</t></is></c>';
    }

    private function sheet(): string
    {
        $cols = '';
        if ($this->columns) {
            $cols = '<cols>';
            foreach ($this->columns as $i => $c) {
                $w = (float)($c[1] ?? 16);
                $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
            }
            $cols .= '</cols>';
        }

        $rowsXml = '';
        $r = 1;
        // Fila de encabezado
        if ($this->columns) {
            $cells = '';
            foreach ($this->columns as $i => $c) {
                $cells .= $this->cell($this->colLetter($i) . $r, (string)($c[0] ?? ''), 1);
            }
            $rowsXml .= '<row r="' . $r . '" ht="18" customHeight="1">' . $cells . '</row>';
            $r++;
        }
        // Datos
        foreach ($this->rows as $row) {
            $cells = '';
            foreach (array_values($row) as $i => $v) {
                $cells .= $this->cell($this->colLetter($i) . $r, (string)$v, 0);
            }
            $rowsXml .= '<row r="' . $r . '">' . $cells . '</row>';
            $r++;
        }

        $dim = 'A1:' . $this->colLetter(max(0, count($this->columns) - 1)) . max(1, $r - 1);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="' . $dim . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . $cols
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . '<autoFilter ref="' . $dim . '"/>'
            . '</worksheet>';
    }

    /* ── Empaquetado ZIP (método stored, sin compresión) ────────────────── */

    private function zip(array $files): string
    {
        $localHeaders = '';
        $centralDir   = '';
        $offset       = 0;
        $count        = 0;

        foreach ($files as $name => $content) {
            $crc = crc32($content);
            $len = strlen($content);
            $nameLen = strlen($name);

            $local = "\x50\x4b\x03\x04"        // firma local file header
                . pack('v', 20)                 // versión necesaria
                . pack('v', 0)                  // flags
                . pack('v', 0)                  // método: 0 = stored
                . pack('v', 0) . pack('v', 0)   // hora/fecha mod
                . pack('V', $crc)
                . pack('V', $len)               // comprimido = sin comprimir
                . pack('V', $len)
                . pack('v', $nameLen)
                . pack('v', 0)                  // extra len
                . $name . $content;

            $central = "\x50\x4b\x01\x02"       // firma central directory
                . pack('v', 20) . pack('v', 20) // versión creada / necesaria
                . pack('v', 0) . pack('v', 0)   // flags / método
                . pack('v', 0) . pack('v', 0)   // hora/fecha
                . pack('V', $crc)
                . pack('V', $len) . pack('V', $len)
                . pack('v', $nameLen)
                . pack('v', 0) . pack('v', 0)   // extra / comment len
                . pack('v', 0) . pack('v', 0)   // disk / internal attrs
                . pack('V', 0)                  // external attrs
                . pack('V', $offset)            // offset del local header
                . $name;

            $localHeaders .= $local;
            $centralDir   .= $central;
            $offset       += strlen($local);
            $count++;
        }

        $eocd = "\x50\x4b\x05\x06"
            . pack('v', 0) . pack('v', 0)
            . pack('v', $count) . pack('v', $count)
            . pack('V', strlen($centralDir))
            . pack('V', strlen($localHeaders))
            . pack('v', 0);

        return $localHeaders . $centralDir . $eocd;
    }
}
