<?php

final class DocumentoFirmadoService
{
    public const MAX_BYTES = 20 * 1024 * 1024;

    public function guardar(array $archivo): array
    {
        $error = (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException($this->mensajeCarga($error));
        }
        $temporal = (string)($archivo['tmp_name'] ?? '');
        if ($temporal === '' || !is_uploaded_file($temporal)) {
            throw new InvalidArgumentException('La carga no proviene de una solicitud válida.');
        }
        $nombre = trim((string)($archivo['name'] ?? 'documento_firmado.pdf'));
        $tamano = (int)($archivo['size'] ?? 0);
        $this->validarPdf($temporal, $tamano);

        $relativa = 'documentos-firmados/' . date('Y/m') . '/' . bin2hex(random_bytes(20)) . '.pdf';
        $absoluta = Config::privateDirectory() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativa);
        $directorio = dirname($absoluta);
        if (!is_dir($directorio) && !mkdir($directorio, 0700, true) && !is_dir($directorio)) {
            throw new RuntimeException('No fue posible preparar el repositorio documental privado.');
        }
        if (!move_uploaded_file($temporal, $absoluta)) {
            throw new RuntimeException('No fue posible incorporar el PDF al repositorio privado.');
        }
        @chmod($absoluta, 0600);
        return [
            'nombre_original' => mb_substr($nombre !== '' ? basename($nombre) : 'documento_firmado.pdf', 0, 255),
            'ruta_privada' => $relativa,
            'mime_type' => 'application/pdf',
            'tamano_bytes' => filesize($absoluta) ?: $tamano,
            'sha256' => hash_file('sha256', $absoluta),
            'ruta_absoluta' => $absoluta,
        ];
    }

    public function validarPdf(string $ruta, int $tamano = 0): void
    {
        $tamano = $tamano > 0 ? $tamano : (int)@filesize($ruta);
        // Los temporales de carga de IIS/FastCGI pueden no resolver mediante
        // realpath() aunque is_uploaded_file() ya haya validado su origen.
        // Se usa la ruta temporal entregada por PHP hasta move_uploaded_file().
        if ($ruta === '' || !is_file($ruta) || $tamano <= 0) {
            throw new InvalidArgumentException('Seleccione un archivo PDF no vacío.');
        }
        if ($tamano > self::MAX_BYTES) {
            throw new InvalidArgumentException('El documento supera el límite de 20 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
        $inicio = (string)file_get_contents($ruta, false, null, 0, 8);
        if ($mime !== 'application/pdf' || !str_starts_with($inicio, '%PDF-')) {
            throw new InvalidArgumentException('El archivo no es un PDF válido.');
        }
        $muestra = (string)file_get_contents($ruta, false, null, 0, min($tamano, 2 * 1024 * 1024));
        if (stripos($muestra, '/Encrypt') !== false) {
            throw new InvalidArgumentException('El PDF está protegido con contraseña. Suba una copia completa sin cifrado.');
        }
    }

    public function resolverRuta(string $relativa): string
    {
        $raiz = realpath(Config::privateDirectory());
        $archivo = realpath(Config::privateDirectory() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativa));
        if ($raiz === false || $archivo === false || !is_file($archivo)
            || !str_starts_with(strtolower($archivo), strtolower($raiz . DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('El archivo firmado no se encuentra disponible.');
        }
        return $archivo;
    }

    public function eliminarSiExiste(?string $ruta): void
    {
        if (is_string($ruta) && $ruta !== '' && is_file($ruta)) {
            @unlink($ruta);
        }
    }

    private function mensajeCarga(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El documento supera el tamaño permitido.',
            UPLOAD_ERR_PARTIAL => 'La carga del documento quedó incompleta. Intente nuevamente.',
            UPLOAD_ERR_NO_FILE => 'Seleccione el PDF completo, escaneado y firmado.',
            default => 'No fue posible recibir el documento firmado.',
        };
    }
}
