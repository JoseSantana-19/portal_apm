<?php
/**
 * PortDatabase — Conexiones nativas sqlsrv (NO PDO) del módulo Portuaria.
 *
 * Integración de portuaria_demoV4: el módulo usa dos bases de datos propias
 * (`PortuariaDemo` principal con tablas bit_* y `PortuariaExterna` con los
 * maestros APM) sobre la MISMA instancia/credenciales del portal.
 *
 * Replica la API de la clase Database del proyecto origen
 * (connection/conn/connExterna devuelven el recurso sqlsrv crudo) para que
 * los modelos y APIs portados funcionen sin cambios de semántica.
 */
class PortDatabase
{
    /** @var array<string, resource> */
    private static array $instances = [];

    /**
     * @param string $name 'principal' | 'externa'
     * @return resource conexión sqlsrv
     */
    public static function connection(string $name = 'principal')
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        switch ($name) {
            case 'principal':
                $dbName = defined('DB_PORTUARIA_NAME') ? DB_PORTUARIA_NAME : 'PortuariaDemo';
                break;
            case 'externa':
                $dbName = defined('DB_PORTUARIA_EXT_NAME') ? DB_PORTUARIA_EXT_NAME : 'PortuariaExterna';
                break;
            default:
                throw new RuntimeException("Conexión Portuaria desconocida: {$name}");
        }

        $server = defined('DB_SERVER') ? DB_SERVER : '.\\VICTUS';
        $user   = defined('DB_USER')   ? DB_USER   : '';
        $pass   = defined('DB_PASS')   ? DB_PASS   : '';

        $base = [
            'CharacterSet'           => 'UTF-8',
            'ReturnDatesAsStrings'   => false,   // el código origen usa DateTime de sqlsrv
            'TrustServerCertificate' => true,
            'Encrypt'                => defined('DB_ENCRYPT') ? DB_ENCRYPT : false,
        ];
        if ($user !== '') { $base['UID'] = $user; $base['PWD'] = $pass; }

        $conn = @sqlsrv_connect($server, ['Database' => $dbName] + $base);

        // Si la BD no existe en esta instancia, crearla desde master y reconectar
        // (mismo patrón de robustez que InvDatabase/ThHrDatabase).
        if ($conn === false) {
            $master = @sqlsrv_connect($server, ['Database' => 'master'] + $base);
            if ($master !== false) {
                sqlsrv_query($master, "IF DB_ID(N'{$dbName}') IS NULL CREATE DATABASE [{$dbName}];");
                sqlsrv_close($master);
                $conn = @sqlsrv_connect($server, ['Database' => $dbName] + $base);
            }
        }

        if ($conn === false) {
            $err = sqlsrv_errors(SQLSRV_ERR_ALL);
            throw new RuntimeException(
                "No se pudo conectar a la BD del módulo Portuaria ({$dbName}): "
                . ($err[0]['message'] ?? 'desconocido')
            );
        }

        self::$instances[$name] = $conn;
        return $conn;
    }

    /** Conexión principal (PortuariaDemo) — antes la global $conn del origen. */
    public static function conn()
    {
        return self::connection('principal');
    }

    /** Conexión externa (PortuariaExterna) — antes $connExterna. Opcional: null si falla. */
    public static function connExterna()
    {
        try {
            return self::connection('externa');
        } catch (RuntimeException $e) {
            return null;
        }
    }
}
