<?php
// core/Database.php – Clase de conexión PDO a SQL Server (APM)


class Conexion
{
    private static $conexion = null;

    /**
     * En 'production': se ocultan rutas internas; el usuario solo ve mensaje institucional.
     * En 'development': se muestra el cuadro rojo detallado con archivo y línea del error.
     */
    public static function conectar()
    {
        if (self::$conexion === null) {
            try {
                $config    = Config::database();
                $servidor  = $config['server'];
                $baseDatos = $config['database'];
                // '' (config/connections.php en desarrollo) => Autenticación de
                // Windows: PDO_SQLSRV la usa cuando UID/PWD no se especifican,
                // lo que solo ocurre pasando null (no una cadena vacía).
                $usuario   = $config['user'] !== '' ? $config['user'] : null;
                $clave     = $config['password'] !== '' ? $config['password'] : null;

                // DSN limpio y estándar para evitar errores de sintaxis de palabras clave
                // PDO_SQLSRV documenta estas opciones como 1/0 o true/false.
                $encrypt = !empty($config['encrypt']) ? 'true' : 'false';
                $trustCertificate = !empty($config['trust_server_certificate']) ? 'true' : 'false';
                // Driver ODBC explícito (permite elegir entre 17/18 cuando hay
                // varios instalados en la máquina) -- whitelist para que un
                // valor de config nunca inyecte texto arbitrario en el DSN.
                $driver = (string)($config['driver'] ?? 'ODBC Driver 18 for SQL Server');
                $driversPermitidos = ['ODBC Driver 18 for SQL Server', 'ODBC Driver 17 for SQL Server'];
                if (!in_array($driver, $driversPermitidos, true)) {
                    throw new RuntimeException('El controlador ODBC configurado no está permitido.');
                }
                $dsn = "sqlsrv:Driver={{$driver}};Server=$servidor;Database=$baseDatos;Encrypt=$encrypt;TrustServerCertificate=$trustCertificate";

                // Opciones de inicialización segura con codificación forzada de Microsoft
                self::$conexion = new PDO($dsn, $usuario, $clave, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Esta constante nativa configura de forma segura el UTF-8 en el driver
                    PDO::SQLSRV_ATTR_ENCODING    => PDO::SQLSRV_ENCODING_UTF8
                ]);

            } catch (PDOException $e) {
                // Dirige el log al módulo de conexión base (sin ruta expuesta)
                self::registrarErrorLog($e, 'Core', false);
                throw new RuntimeException(
                    'Error de comunicación institucional: el servicio no se encuentra disponible.',
                    0,
                    $e
                );
            }
        }
        return self::$conexion;
    }

    /**
     * Usa ROOT (constante global definida en index.php) en lugar de
     * dirname(__FILE__) para que la ruta de logs sea siempre relativa a la raíz del
     * proyecto, independientemente de dónde esté ubicado este archivo.
     *
     * - Cada módulo tiene una subcarpeta normalizada en /storage/logs/.
     * - Archivo independiente por cada día: log_YYYY-MM-DD.txt
     * - En producción, el usuario NUNCA ve rutas internas (C:\wamp64\...).
     *
     * @param Throwable $e                 La excepción/error capturado (Throwable, no solo Exception --
     *                                     así también cubre Error/TypeError, ver mismos catch(Throwable) de Auth.php).
     * @param string    $modulo            Nombre del módulo (ej: 'talento-humano', 'Core').
     * @param bool      $detenerEjecucion  Si es false, solo escribe el log y retorna (no hace die/exit).
     *                                     Úsalo false en métodos de modelo con valores de fallback.
     */
    public static function registrarErrorLog(Throwable $e, string $modulo = 'talento-humano', bool $detenerEjecucion = true): void
    {
        $fechaActual = date('Y-m-d');
        $horaActual  = date('H:i:s');

        // RECTIFICADO v2.1: Usa ROOT para ruta absoluta estable desde cualquier ubicación
        // ROOT se define en index.php como define('ROOT', __DIR__)
        $raiz = defined('ROOT') ? ROOT : dirname(__DIR__);

        // Repositorio central fuera del árbol público. La normalización evita
        // duplicados históricos como Talento_Humano y talento-humano.
        $moduloNormalizado = strtolower(trim($modulo));
        $moduloNormalizado = str_replace('_', '-', $moduloNormalizado);
        $moduloNormalizado = preg_replace('/[^a-z0-9-]+/', '-', $moduloNormalizado) ?: 'sistema';
        $directorioLog = $raiz . '/storage/logs/' . trim($moduloNormalizado, '-');

        if (!is_dir($directorioLog)) {
            mkdir($directorioLog, 0755, true);
        }

        // RECTIFICADO: Un archivo de texto por cada día (ej: log_2026-05-25.txt)
        $archivoLog = $directorioLog . "/log_" . $fechaActual . ".txt";

        $traza = "[$horaActual] EXCEPCIÓN -> " . $e->getMessage()
               . " en " . $e->getFile()
               . " (Línea " . $e->getLine() . ")" . PHP_EOL;

        file_put_contents($archivoLog, $traza, FILE_APPEND | LOCK_EX);

        // Si el llamador indicó que NO debe detener la ejecución (ej: fallback en modelo), retornar
        if (!$detenerEjecucion) {
            return;
        }

        // RECTIFICADO: Control estricto de visualización por entorno
        if (Config::isProduction()) {
            // En producción: NUNCA se exponen rutas internas ni mensajes técnicos
            die("Error de comunicación institucional: El servicio no se encuentra disponible. La incidencia ha sido reportada a la Dirección de TI.");
        } else {
            // En desarrollo: muestra el cuadro rojo detallado para depuración
            echo "<div style='background:#f8d7da; color:#721c24; padding:20px; border:1px solid #f5c6cb; font-family:monospace; margin:10px;'>";
            echo "<h3>&#9888; Excepción del Framework MVC — Módulo: " . htmlspecialchars($modulo) . "</h3>";
            echo "<p><b>Mensaje:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><b>Archivo:</b> " . htmlspecialchars($e->getFile()) . " (Línea " . $e->getLine() . ")</p>";
            echo "<p><b>Log guardado en:</b> storage/logs/" . htmlspecialchars($moduloNormalizado) . "/log_" . $fechaActual . ".txt</p>";
            echo "</div>";
            exit;
        }
    }
}
