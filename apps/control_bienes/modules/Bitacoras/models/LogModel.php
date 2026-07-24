<?php
/**
 * LOGMODEL.PHP - Servicio de Registro de Errores y Eventos
 * Ubicado en modules/Bitacoras/models/LogModel.php
 */

class Logger {

    /** @var string Prefijo del módulo (ej: 'inv', 'bod', 'th') */
    private string $modulo;

    /** @var string Entorno activo: 'development' | 'production' */
    private string $entorno;

    /** @var string Ruta absoluta a la carpeta raíz de logs */
    private string $rutaLogs;

    /**
     * @param string $modulo  Prefijo del módulo según rules.md
     *                        Valores válidos: sys|inv|bod|th|cab|per|acc|bit|seq
     */
    public function __construct(string $modulo = 'sys') {
        $this->modulo   = strtolower(trim($modulo));
        $this->entorno  = defined('APP_ENV')       ? APP_ENV        : 'production';
        $this->rutaLogs = defined('APP_LOGS_PATH') ? APP_LOGS_PATH  : ROOT_PATH . 'logs/';
    }

    /**
     * Registra un error crítico.
     * En DEVELOPMENT: relanza la excepción para que sea visible en pantalla.
     * En PRODUCTION:  solo registra en archivo y BD, nunca expone al usuario.
     */
    public function inv_error(string $descripcion, ?\Throwable $excepcion = null, string $accion = ''): void {
        $this->registrar('ERROR', $descripcion, $excepcion, $accion);

        if ($this->entorno === 'development' && $excepcion !== null) {
            // En desarrollo: propagar la excepción para depuración inmediata
            throw $excepcion;
        }

        if ($this->entorno === 'production') {
            // En producción: redirigir a página de error genérica
            if (!headers_sent()) {
                header('Location: index.php?route=error_sistema');
                exit;
            }
        }
    }

    /**
     * Registra una advertencia (WARNING).
     * No relanza excepción ni redirige — solo deja constancia del evento.
     */
    public function warning(string $descripcion, string $accion = '', ?\Throwable $excepcion = null): void {
        $this->registrar('WARNING', $descripcion, $excepcion, $accion);
    }

    /**
     * Registra un evento informativo (INFO).
     */
    public function info(string $descripcion, string $accion = ''): void {
        $this->registrar('INFO', $descripcion, null, $accion);
    }

    /**
     * Construye la entrada de log y la persiste en archivo y opcionalmente en BD.
     */
    private function registrar(
        string $nivel,
        string $descripcion,
        ?\Throwable $excepcion,
        string $accion
    ): void {
        try {
            $ahora    = new \DateTime();
            $fecha    = $ahora->format('Y-m-d H:i:s');
            $usuario  = $this->obtenerUsuarioActual();
            $ip       = $this->obtenerIp();

            // Datos de la excepción (si existe)
            $tipoError    = $excepcion ? get_class($excepcion) : 'LogManual';
            $mensajeExc   = $excepcion ? $excepcion->getMessage() : $descripcion;
            $archivoOrig  = $excepcion ? $excepcion->getFile()    : '';
            $lineaOrig    = $excepcion ? $excepcion->getLine()     : 0;

            // --- 1. Construir la entrada de texto ---
            $separador = str_repeat('=', 60);
            $entrada   = PHP_EOL . $separador . PHP_EOL;
            $entrada  .= "[{$fecha}]" . PHP_EOL;
            $entrada  .= "Nivel     : {$nivel}" . PHP_EOL;
            $entrada  .= "Usuario   : {$usuario}" . PHP_EOL;
            $entrada  .= "IP        : {$ip}" . PHP_EOL;
            $entrada  .= "Módulo    : {$this->modulo}" . PHP_EOL;
            $entrada  .= "Acción    : {$accion}" . PHP_EOL;
            $entrada  .= "Tipo      : {$tipoError}" . PHP_EOL;
            $entrada  .= "Descripción: {$descripcion}" . PHP_EOL;
            if ($excepcion) {
                $entrada .= "Excepción : {$mensajeExc}" . PHP_EOL;
                $entrada .= "Archivo   : {$archivoOrig}" . PHP_EOL;
                $entrada .= "Línea     : {$lineaOrig}" . PHP_EOL;
            }
            $entrada .= $separador . PHP_EOL;

            // --- 2. Escribir en el archivo .txt diario ---
            $this->escribirEnArchivo($entrada, $ahora);

            // --- 3. Registrar en la base de datos (silenciado si falla) ---
            $this->registrarEnBD([
                'modulo'         => $this->modulo,
                'accion'         => $accion,
                'tipo_error'     => $tipoError,
                'descripcion'    => $descripcion . ($excepcion ? (' | ' . $mensajeExc) : ''),
                'archivo_origen' => $archivoOrig,
                'linea_origen'   => $lineaOrig,
                'nivel'          => $nivel,
                'entorno'        => $this->entorno,
                'ip_cliente'     => $ip,
                'fecha_registro' => $fecha,
            ]);

        } catch (\Throwable $interno) {
            if ($this->entorno === 'development') {
                error_log('[Logger interno] ' . $interno->getMessage());
            }
        }
    }

    private function escribirEnArchivo(string $contenido, \DateTime $fecha): void {
        $dirModulo = rtrim($this->rutaLogs, DIRECTORY_SEPARATOR)
                   . DIRECTORY_SEPARATOR . $this->modulo;

        $this->crearDirectorioSiNoExiste($dirModulo);

        $nombreArchivo = 'error_' . $fecha->format('Y_m_d') . '.txt';
        $rutaCompleta  = $dirModulo . DIRECTORY_SEPARATOR . $nombreArchivo;

        file_put_contents($rutaCompleta, $contenido, FILE_APPEND | LOCK_EX);
    }

    private function registrarEnBD(array $datos): void {
        try {
            require_once ROOT_PATH . 'core/Database.php';
            $pdo = Database::getInstance()->getConnection();

            $tabla = ($datos['nivel'] === 'INFO') ? 'inv_log_eventos' : 'inv_log_errores';

            if ($tabla === 'inv_log_errores') {
                $stmt = $pdo->prepare("
                    INSERT INTO inv_log_errores
                        (id_usuario, modulo, accion, tipo_error, descripcion,
                         archivo_origen, linea_origen, nivel, entorno, ip_cliente, fecha_registro)
                    VALUES
                        (:id_usuario, :modulo, :accion, :tipo_error, :descripcion,
                         :archivo_origen, :linea_origen, :nivel, :entorno, :ip_cliente, :fecha_registro)
                ");
                $stmt->execute([
                    ':id_usuario'     => $this->obtenerIdUsuario(),
                    ':modulo'         => $datos['modulo'],
                    ':accion'         => $datos['accion'],
                    ':tipo_error'     => $datos['tipo_error'],
                    ':descripcion'    => $datos['descripcion'],
                    ':archivo_origen' => $datos['archivo_origen'],
                    ':linea_origen'   => $datos['linea_origen'],
                    ':nivel'          => $datos['nivel'],
                    ':entorno'        => $datos['entorno'],
                    ':ip_cliente'     => $datos['ip_cliente'],
                    ':fecha_registro' => $datos['fecha_registro'],
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO inv_log_eventos
                        (id_usuario, modulo, accion, descripcion, resultado, ip_cliente, fecha_registro)
                    VALUES
                        (:id_usuario, :modulo, :accion, :descripcion, :resultado, :ip_cliente, :fecha_registro)
                ");
                $stmt->execute([
                    ':id_usuario'     => $this->obtenerIdUsuario(),
                    ':modulo'         => $datos['modulo'],
                    ':accion'         => $datos['accion'],
                    ':descripcion'    => $datos['descripcion'],
                    ':resultado'      => 'OK',
                    ':ip_cliente'     => $datos['ip_cliente'],
                    ':fecha_registro' => $datos['fecha_registro'],
                ]);
            }
        } catch (\Throwable $e) {
            if ($this->entorno === 'development') {
                error_log('[Logger BD] ' . $e->getMessage());
            }
        }
    }

    private function crearDirectorioSiNoExiste(string $ruta): void {
        if (!is_dir($ruta)) {
            mkdir($ruta, 0755, true);
        }
    }

    private function obtenerUsuarioActual(): string {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (isset($_SESSION['usuario'])) {
            if (is_array($_SESSION['usuario'])) {
                return $_SESSION['usuario']['usuario'] ?? 'sistema';
            }
            return (string)$_SESSION['usuario'];
        }
        return 'sistema';
    }

    private function obtenerIdUsuario(): ?int {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (isset($_SESSION['usuario_id'])) {
            return (int)$_SESSION['usuario_id'];
        }
        if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario']) && isset($_SESSION['usuario']['id'])) {
            return (int)$_SESSION['usuario']['id'];
        }
        return null;
    }

    private function obtenerIp(): string {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return filter_var(trim($ip), FILTER_VALIDATE_IP) ?: '0.0.0.0';
    }
}
