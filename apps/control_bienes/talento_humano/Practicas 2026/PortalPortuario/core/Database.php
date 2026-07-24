<?php
// core/Database.php – Clase de conexión PDO a SQL Server (APM)


class Conexion
{
    private static $conexion = null;

    /**
     * En 'production': se ocultan rutas internas; el usuario solo ve mensaje institucional.
     * En 'development': se muestra el cuadro rojo detallado con archivo y línea del error.
     */
    private const ENTORNO = 'development';

    public static function conectar()
    {
        if (self::$conexion === null) {
            try {
                $servidor  = "JAVIER";
                $baseDatos = "Talento_Humano";
                $usuario   = "sa";
                $clave     = "123456";

                // DSN limpio y estándar para evitar errores de sintaxis de palabras clave
                $dsn = "sqlsrv:Server=$servidor;Database=$baseDatos";

                // Opciones de inicialización segura con codificación forzada de Microsoft
                self::$conexion = new PDO($dsn, $usuario, $clave, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    // Esta constante nativa configura de forma segura el UTF-8 en el driver
                    PDO::SQLSRV_ATTR_ENCODING    => PDO::SQLSRV_ENCODING_UTF8
                ]);

            } catch (PDOException $e) {
                // Dirige el log al módulo de conexión base (sin ruta expuesta)
                self::registrarErrorLog($e, 'Core');
                die("Error de comunicación institucional: El servicio no se encuentra disponible. La incidencia ha sido reportada a la Dirección de TI.");
            }
        }
        return self::$conexion;
    }

    /**
     * Usa ROOT (constante global definida en index.php) en lugar de
     * dirname(__FILE__) para que la ruta de logs sea siempre relativa a la raíz del
     * proyecto, independientemente de dónde esté ubicado este archivo.
     *
     * - Cada módulo tiene su propia subcarpeta protegida: /modules/{Modulo}/log/
     * - Archivo independiente por cada día: log_YYYY-MM-DD.txt
     * - En producción, el usuario NUNCA ve rutas internas (C:\wamp64\...).
     *
     * @param Exception $e                 La excepción capturada.
     * @param string    $modulo            Nombre del módulo (ej: 'talento-humano', 'Core').
     * @param bool      $detenerEjecucion  Si es false, solo escribe el log y retorna (no hace die/exit).
     *                                     Úsalo false en métodos de modelo con valores de fallback.
     */
    public static function registrarErrorLog(Exception $e, string $modulo = 'talento-humano', bool $detenerEjecucion = true): void
    {
        $fechaActual = date('Y-m-d');
        $horaActual  = date('H:i:s');

        // RECTIFICADO v2.1: Usa ROOT para ruta absoluta estable desde cualquier ubicación
        // ROOT se define en index.php como define('ROOT', __DIR__)
        $raiz = defined('ROOT') ? ROOT : dirname(__DIR__);

        // Ruta interna: /modules/{Modulo}/log/ — nunca expuesta al navegador
        $directorioLog = $raiz . "/modules/" . $modulo . "/log";

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
        if (self::ENTORNO === 'production') {
            // En producción: NUNCA se exponen rutas internas ni mensajes técnicos
            die("Error de comunicación institucional: El servicio no se encuentra disponible. La incidencia ha sido reportada a la Dirección de TI.");
        } else {
            // En desarrollo: muestra el cuadro rojo detallado para depuración
            echo "<div style='background:#f8d7da; color:#721c24; padding:20px; border:1px solid #f5c6cb; font-family:monospace; margin:10px;'>";
            echo "<h3>&#9888; Excepción del Framework MVC — Módulo: " . htmlspecialchars($modulo) . "</h3>";
            echo "<p><b>Mensaje:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><b>Archivo:</b> " . htmlspecialchars($e->getFile()) . " (Línea " . $e->getLine() . ")</p>";
            echo "<p><b>Log guardado en:</b> modules/" . htmlspecialchars($modulo) . "/log/log_" . $fechaActual . ".txt</p>";
            echo "</div>";
            exit;
        }
    }
}
