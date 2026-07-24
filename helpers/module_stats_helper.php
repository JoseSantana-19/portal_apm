<?php
/**
 * module_stats_helper — Badges con datos EN VIVO de las BDs de cada módulo
 * integrado, para el menú lateral del portal.
 *
 *   Módulo 11 (Talento Humano)  → empleados activos      (BD Talento_Humano)
 *   Módulo 12 (Control Bienes)  → bienes activos         (BD inventario)
 *   Módulo 13 (Portuaria)       → visitas sin salida     (BD PortuariaDemo)
 *
 * Cache en sesión (TTL 120 s) para no golpear 3 BDs en cada request.
 * Tolerante a fallos: si una BD no responde, ese badge simplemente no sale.
 */

if (!function_exists('apm_module_badges')) {
    function apm_module_badges(): array
    {
        $ttl = 120;
        if (isset($_SESSION['_mod_badges'], $_SESSION['_mod_badges_ts'])
            && (time() - (int)$_SESSION['_mod_badges_ts']) < $ttl) {
            return $_SESSION['_mod_badges'];
        }

        $badges = [];

        $scalar = function (string $dbName, string $sql): ?int {
            try {
                $opts = [
                    'Database'               => $dbName,
                    'CharacterSet'           => 'UTF-8',
                    'TrustServerCertificate' => true,
                    'Encrypt'                => defined('DB_ENCRYPT') ? DB_ENCRYPT : false,
                    'LoginTimeout'           => 2,
                ];
                if (defined('DB_USER') && DB_USER !== '') { $opts['UID'] = DB_USER; $opts['PWD'] = DB_PASS; }
                $conn = @sqlsrv_connect(DB_SERVER, $opts);
                if ($conn === false) return null;
                $stmt = @sqlsrv_query($conn, $sql);
                $val  = null;
                if ($stmt !== false) {
                    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
                    if ($row !== false && $row !== null) $val = (int)$row[0];
                    sqlsrv_free_stmt($stmt);
                }
                sqlsrv_close($conn);
                return $val;
            } catch (Throwable $e) {
                return null;
            }
        };

        $th = $scalar(defined('DB_TH_NAME') ? DB_TH_NAME : 'Talento_Humano',
                      'SELECT COUNT(*) FROM th_empleados WHERE estado = 1');
        if ($th !== null) $badges[11] = ['n' => $th, 'title' => 'Empleados activos'];

        $inv = $scalar('inventario', 'SELECT COUNT(*) FROM inv_inventario WHERE activo = 1');
        if ($inv !== null) $badges[12] = ['n' => $inv, 'title' => 'Bienes activos'];

        $port = $scalar(defined('DB_PORTUARIA_NAME') ? DB_PORTUARIA_NAME : 'PortuariaDemo',
                        'SELECT COUNT(*) FROM dbo.bit_visitas WHERE hora_salida IS NULL');
        if ($port !== null) $badges[13] = ['n' => $port, 'title' => 'Visitas en puerto'];

        $_SESSION['_mod_badges']    = $badges;
        $_SESSION['_mod_badges_ts'] = time();
        return $badges;
    }
}
