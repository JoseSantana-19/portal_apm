<?php
declare(strict_types=1);

/**
 * Generador OOXML mínimo y autosuficiente.
 *
 * El portal se distribuye sin Composer y PHP 8.5 no tiene ZipArchive. Esta
 * clase crea un contenedor ZIP válido (método DEFLATE) y hojas SpreadsheetML
 * modernas, por lo que la descarga resultante es un .xlsx real.
 *
 * NOTA compatibilidad (adaptado de origen para PHP 7.4/8.3/8.5 simultáneo,
 * ver helpers/polyfills_php74.php): sin `mixed` (PHP 8.0+, tipos quitados en
 * su lugar) ni `: never` (PHP 8.1+, usa `: void`) ni
 * DateTimeImmutable::createFromInterface() (PHP 8.0+, conversión manual).
 */
final class XlsxWriter
{
    /** @var array<int,array{name:string,rows:array<int,array<string,mixed>>}> */
    private array $sheets = [];

    public function addSheet(string $name, array $rows): void
    {
        $base = $this->safeSheetName($name);
        $candidate = $base;
        $suffix = 2;
        $used = array_column($this->sheets, 'name');
        while (in_array($candidate, $used, true)) {
            $tail = ' (' . $suffix++ . ')';
            $candidate = mb_substr($base, 0, 31 - mb_strlen($tail)) . $tail;
        }
        $this->sheets[] = ['name' => $candidate, 'rows' => array_values($rows)];
    }

    public function save(string $path): void
    {
        if ($this->sheets === []) $this->addSheet('Reporte', []);
        $archive = new XlsxZipArchive($path);
        foreach ($this->packageEntries() as $name => $content) $archive->add($name, $content);
        $archive->close();
    }

    public function download(string $filename): void
    {
        $filename = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $filename) ?: 'reporte.xlsx';
        if (!str_ends_with(strtolower($filename), '.xlsx')) $filename .= '.xlsx';
        $tmp = tempnam(sys_get_temp_dir(), 'apm-xlsx-');
        if ($tmp === false) throw new RuntimeException('No se pudo preparar el archivo Excel.');
        try {
            $this->save($tmp);
            while (ob_get_level() > 0) ob_end_clean();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($tmp));
            header('Cache-Control: private, no-store, max-age=0');
            header('X-Content-Type-Options: nosniff');
            readfile($tmp);
        } finally {
            @unlink($tmp);
        }
        exit;
    }

    /** @return array<string,string> */
    private function packageEntries(): array
    {
        $entries = [
            '[Content_Types].xml' => $this->contentTypes(),
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>',
            'docProps/core.xml' => $this->coreProperties(),
            'docProps/app.xml' => $this->appProperties(),
            'xl/workbook.xml' => $this->workbookXml(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRelationships(),
            'xl/styles.xml' => $this->stylesXml(),
        ];
        foreach ($this->sheets as $index => $sheet) {
            $entries['xl/worksheets/sheet' . ($index + 1) . '.xml'] = $this->sheetXml($sheet['rows']);
        }
        return $entries;
    }

    private function contentTypes(): string
    {
        $sheets = '';
        foreach ($this->sheets as $index => $_) {
            $sheets .= '<Override PartName="/xl/worksheets/sheet' . ($index + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>' . $sheets . '</Types>';
    }

    private function coreProperties(): string
    {
        $created = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>Portal Portuario APM</dc:creator><cp:lastModifiedBy>Portal Portuario APM</cp:lastModifiedBy><dc:title>Reporte completo de funcionarios</dc:title><dcterms:created xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:modified></cp:coreProperties>';
    }

    private function appProperties(): string
    {
        $titles = '';
        foreach ($this->sheets as $sheet) $titles .= '<vt:lpstr>' . self::xml($sheet['name']) . '</vt:lpstr>';
        $count = count($this->sheets);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Portal Portuario APM</Application><HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Hojas de cálculo</vt:lpstr></vt:variant><vt:variant><vt:i4>' . $count . '</vt:i4></vt:variant></vt:vector></HeadingPairs><TitlesOfParts><vt:vector size="' . $count . '" baseType="lpstr">' . $titles . '</vt:vector></TitlesOfParts></Properties>';
    }

    private function workbookXml(): string
    {
        $sheets = '';
        foreach ($this->sheets as $index => $sheet) {
            $sheets .= '<sheet name="' . self::xml($sheet['name']) . '" sheetId="' . ($index + 1) . '" r:id="rId' . ($index + 1) . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView/></bookViews><sheets>' . $sheets . '</sheets><calcPr calcId="191029" fullCalcOnLoad="1"/></workbook>';
    }

    private function workbookRelationships(): string
    {
        $rels = '';
        foreach ($this->sheets as $index => $_) {
            $rels .= '<Relationship Id="rId' . ($index + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($index + 1) . '.xml"/>';
        }
        $rels .= '<Relationship Id="rId' . (count($this->sheets) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="2"><numFmt numFmtId="164" formatCode="yyyy-mm-dd"/><numFmt numFmtId="165" formatCode="&quot;$&quot;#,##0.00"/></numFmts><fonts count="2"><font><sz val="10"/><name val="Aptos"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="10"/><name val="Aptos Display"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0E7490"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left/><right/><top/><bottom style="thin"><color rgb="FFD7E3EA"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1"/><xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function sheetXml(array $rows): string
    {
        $headers = $rows !== [] ? array_keys($rows[0]) : ['Sin datos'];
        $matrix = [$headers];
        foreach ($rows as $row) {
            $matrix[] = array_map(static function (string $header) use ($row) { return $row[$header] ?? null; }, $headers);
        }
        $lastColumn = self::columnName(count($headers));
        $lastRow = count($matrix);
        $widths = array_fill(0, count($headers), 10);
        $sheetData = '';
        foreach ($matrix as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $cells = '';
            foreach ($row as $columnIndex => $value) {
                $header = (string)$headers[$columnIndex];
                $widths[$columnIndex] = min(42, max($widths[$columnIndex], min(42, mb_strlen((string)($value ?? '')) + 2)));
                $cells .= $this->cellXml(self::columnName($columnIndex + 1) . $rowNumber, $value, $header, $rowIndex === 0);
            }
            $sheetData .= '<row r="' . $rowNumber . '"' . ($rowIndex === 0 ? ' ht="28" customHeight="1"' : '') . '>' . $cells . '</row>';
        }
        $cols = '';
        foreach ($widths as $index => $width) $cols .= '<col min="' . ($index + 1) . '" max="' . ($index + 1) . '" width="' . $width . '" customWidth="1"/>';
        $filter = $lastRow > 1 ? '<autoFilter ref="A1:' . $lastColumn . $lastRow . '"/>' : '';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:' . $lastColumn . $lastRow . '"/><sheetViews><sheetView showGridLines="0" workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="18"/><cols>' . $cols . '</cols><sheetData>' . $sheetData . '</sheetData>' . $filter . '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/><pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/></worksheet>';
    }

    private function cellXml(string $reference, $value, string $header, bool $isHeader): string
    {
        if ($isHeader) return '<c r="' . $reference . '" t="inlineStr" s="1"><is><t>' . self::xml($value) . '</t></is></c>';
        if ($value === null || $value === '') return '<c r="' . $reference . '" s="0"/>';
        if ($this->isDateHeader($header) && ($serial = $this->excelDate($value)) !== null) {
            return '<c r="' . $reference . '" s="2"><v>' . $serial . '</v></c>';
        }
        if ($this->isCurrencyHeader($header) && is_numeric($value)) {
            return '<c r="' . $reference . '" s="3"><v>' . self::number($value) . '</v></c>';
        }
        if (is_bool($value)) return '<c r="' . $reference . '" t="b" s="0"><v>' . ($value ? '1' : '0') . '</v></c>';
        if (is_numeric($value) && !$this->isIdentifierHeader($header) && !preg_match('/^0\d+$/', (string)$value)) {
            return '<c r="' . $reference . '" s="0"><v>' . self::number($value) . '</v></c>';
        }
        return '<c r="' . $reference . '" t="inlineStr" s="0"><is><t xml:space="preserve">' . self::xml($value) . '</t></is></c>';
    }

    private function isDateHeader(string $header): bool
    {
        $h = strtolower($header);
        return str_contains($h, 'fecha') || str_ends_with($h, '_desde') || str_ends_with($h, '_hasta');
    }

    private function isCurrencyHeader(string $header): bool
    {
        return (bool)preg_match('/rmu|remuner|sueldo|valor/i', $header);
    }

    private function isIdentifierHeader(string $header): bool
    {
        return (bool)preg_match('/(^id$|_id$|cedula|identificacion|pasaporte|telefono|cuenta|codigo|numero|partida|placa)/i', $header);
    }

    /** @param mixed $value */
    private function excelDate($value): ?int
    {
        if ($value instanceof DateTimeInterface) {
            // DateTimeImmutable::createFromInterface() es PHP 8.0+ -- conversión
            // manual equivalente, compatible con 7.4.
            $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value->format('Y-m-d H:i:s')) ?: null;
        } else {
            $text = substr(trim((string)$value), 0, 10);
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $text) ?: null;
        }
        if (!$date) return null;
        $epoch = new DateTimeImmutable('1899-12-30', new DateTimeZone('UTC'));
        return (int)$epoch->diff($date)->format('%r%a');
    }

    private function safeSheetName(string $name): string
    {
        $name = trim(str_replace(['\\','/','?','*','[',']',':'], ' ', $name));
        return mb_substr($name !== '' ? $name : 'Reporte', 0, 31);
    }

    /** @param mixed $value */
    private static function xml($value): string
    {
        $text = (string)($value ?? '');
        $text = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $text) ?? '';
        return htmlspecialchars(mb_substr($text, 0, 32767), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** @param mixed $value */
    private static function number($value): string
    {
        return rtrim(rtrim(number_format((float)$value, 10, '.', ''), '0'), '.');
    }

    private static function columnName(int $number): string
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)) . $name;
            $number = intdiv($number, 26);
        }
        return $name;
    }
}

final class XlsxZipArchive
{
    /** @var resource */
    private $handle;
    /** @var array<int,array{name:string,crc:int,compressed:int,size:int,offset:int,method:int,time:int,date:int}> */
    private array $entries = [];
    private int $offset = 0;

    public function __construct(string $path)
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) throw new RuntimeException('No se pudo crear el contenedor XLSX.');
        $this->handle = $handle;
    }

    public function add(string $name, string $content): void
    {
        $compressed = gzdeflate($content, 6);
        if ($compressed === false) throw new RuntimeException('No se pudo comprimir una parte XLSX.');
        $method = 8;
        $crc = crc32($content);
        [$time, $date] = $this->dosTime();
        $nameLength = strlen($name);
        $header = pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, $method, $time, $date, $crc, strlen($compressed), strlen($content), $nameLength, 0);
        $this->write($header . $name . $compressed);
        $this->entries[] = ['name'=>$name,'crc'=>$crc,'compressed'=>strlen($compressed),'size'=>strlen($content),'offset'=>$this->offset - strlen($header) - $nameLength - strlen($compressed),'method'=>$method,'time'=>$time,'date'=>$date];
    }

    public function close(): void
    {
        $centralOffset = $this->offset;
        foreach ($this->entries as $entry) {
            $nameLength = strlen($entry['name']);
            $header = pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, $entry['method'], $entry['time'], $entry['date'], $entry['crc'], $entry['compressed'], $entry['size'], $nameLength, 0, 0, 0, 0, 0, $entry['offset']);
            $this->write($header . $entry['name']);
        }
        $centralSize = $this->offset - $centralOffset;
        $count = count($this->entries);
        $this->write(pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, $centralSize, $centralOffset, 0));
        fclose($this->handle);
    }

    private function write(string $bytes): void
    {
        $written = fwrite($this->handle, $bytes);
        if ($written === false || $written !== strlen($bytes)) throw new RuntimeException('Escritura incompleta del archivo XLSX.');
        $this->offset += $written;
    }

    /** @return array{0:int,1:int} */
    private function dosTime(): array
    {
        $now = getdate();
        $time = (($now['hours'] & 0x1f) << 11) | (($now['minutes'] & 0x3f) << 5) | (($now['seconds'] >> 1) & 0x1f);
        $year = max(1980, $now['year']);
        $date = (($year - 1980) << 9) | (($now['mon'] & 0x0f) << 5) | ($now['mday'] & 0x1f);
        return [$time, $date];
    }
}
