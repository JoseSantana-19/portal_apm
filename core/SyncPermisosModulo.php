<?php
/**
 * SyncPermisosModulo — sincroniza CORE_Permisos_Nodo con el RBAC propio de
 * cualquier módulo embebido que tenga mapeo en CORE_Roles_Modulo_Map Y una
 * fila de configuración en CORE_Modulos_Sync_Config (ver
 * db/core_modulos_sync_generico.sql).
 *
 * Motor genérico (centralHaciaGenerico): agregar un módulo nuevo con RBAC
 * propio rol-based (rol_id + columnas booleanas de permiso, con o sin JOIN
 * a un catálogo) es 2 INSERTs de datos — cero PHP nuevo. Ver
 * ModuloController::sync() para la UI de administración.
 *
 * Hasta 2026-09-13 este archivo tenía un método hardcodeado por módulo
 * (centralHaciaTh, centralHaciaBienes) — retirados. Talento Humano migró
 * 1:1 a config genérica (ver migración SQL). Control de Bienes NO se migró:
 * su RBAC real (inv_permisos_detalle) es por usuario, no por rol — un
 * motor "rol del portal → rol del módulo" no le queda; centralHaciaBienes()
 * además apuntaba a una tabla (inv_permisos_rol) que ya no existe en
 * `inventario` desde la actualización de origen del módulo — el sync a
 * Bienes llevaba roto en silencio (sqlsrv_query() nunca revisaba el
 * retorno) probablemente desde esa fecha. Ver nota al final del .sql.
 */
class SyncPermisosModulo {

    /**
     * Punto de entrada único. Se llama una sola vez desde
     * AdminController::guardarPermisos() con TODOS los cambios de nodo del
     * request — internamente reparte por id_modulo y sincroniza cada
     * módulo configurado y con mapeo de rol. Nunca lanza excepción hacia
     * el caller: un módulo destino caído no debe bloquear el guardado de
     * permisos en el portal (ver registrarFalloSync).
     */
    public static function centralHaciaGenerico(int $idRolPortal, array $cambiosPorNodo): void {
        // 1. Agrupar los cambios por id_modulo, igual que antes (solo
        //    nodos de "opción" plana, items=0 y subitems=0 — único nivel
        //    que un módulo externo con este patrón puede reflejar).
        $porModulo = [];
        foreach ($cambiosPorNodo as $key => $nivel) {
            $parts = explode('-', (string)$key);
            if (count($parts) !== 4) continue;
            [$mod, $op, $it, $sub] = array_map('intval', $parts);
            if ($it !== 0 || $sub !== 0) continue;
            $porModulo[$mod][$op] = (int)$nivel;
        }
        if (empty($porModulo)) return;

        $db = Database::getInstance();
        $placeholders = implode(',', array_fill(0, count($porModulo), '?'));
        $params = array_map(static fn($m) => [$m, SQLSRV_PARAM_IN], array_keys($porModulo));
        $configs = $db->fetchAll($db->query(
            "SELECT c.*, m.conexion_bd, m.nombre AS nombre_modulo
             FROM CORE_Modulos_Sync_Config c
             JOIN CORE_Modulos m ON m.id_modulo = c.id_modulo
             WHERE c.estado = 1 AND c.id_modulo IN ($placeholders)",
            $params
        ));

        foreach ($configs as $cfg) {
            self::sincronizarUnModulo($idRolPortal, $cfg, $porModulo[(int)$cfg['id_modulo']]);
        }
    }

    private static function sincronizarUnModulo(int $idRolPortal, array $cfg, array $cambiosDeEsteModulo): void {
        $idModulo = (int)$cfg['id_modulo'];

        $idRolExterno = self::mapaRolExterno($idRolPortal, $idModulo);
        if ($idRolExterno === null) return; // este rol de portal no tiene contraparte en ese módulo

        $conexionLogica = trim((string)($cfg['conexion_bd'] ?? ''));
        if ($conexionLogica === '') {
            self::registrarFalloSync($idRolPortal, $idModulo, 'CORE_Modulos.conexion_bd está vacío — no se puede resolver a qué servidor/base conectar.');
            return;
        }

        $conn = require dirname(__DIR__) . '/config/connections.php';
        $dbCfg = $conn['databases'][$conexionLogica] ?? null;
        if ($dbCfg === null) {
            self::registrarFalloSync($idRolPortal, $idModulo, "conexion_bd='{$conexionLogica}' no existe en config/connections.php → databases.");
            return;
        }

        $opts = ['CharacterSet' => 'UTF-8', 'TrustServerCertificate' => (bool)($conn['options']['trust_cert'] ?? true), 'Database' => $dbCfg['name']];
        if (!empty($conn['credentials']['user'])) {
            $opts['UID'] = $conn['credentials']['user'];
            $opts['PWD'] = $conn['credentials']['pass'];
        }
        $c = @sqlsrv_connect($dbCfg['server'] ?? $conn['server_default'], $opts);
        if ($c === false) {
            self::registrarFalloSync($idRolPortal, $idModulo, 'Sin conexión: ' . json_encode(sqlsrv_errors()));
            return;
        }

        // Validar CADA identificador (tabla/columna) configurado contra el
        // esquema REAL de esta conexión antes de interpolar nada en SQL —
        // nombres de tabla/columna no se pueden parametrizar con "?" en
        // T-SQL, así que se arman como string. Esta doble validación
        // (regex de forma + existencia real en INFORMATION_SCHEMA) es lo
        // que hace seguro interpolarlos: aunque CORE_Modulos_Sync_Config
        // tuviera un valor manipulado, solo puede "colarse" si nombra algo
        // que YA existe en el schema real — no permite inyectar SQL nuevo.
        $modoJoin = strtoupper((string)$cfg['modo_escritura']) === 'UPDATE_JOIN';
        $identificadores = [$cfg['tabla_permisos'], $cfg['columna_rol_id'], $cfg['columna_identificador'],
            $cfg['columna_visualizar'], $cfg['columna_crear'], $cfg['columna_editar'], $cfg['columna_eliminar']];
        if ($modoJoin) {
            $identificadores[] = $cfg['tabla_join'];
            $identificadores[] = $cfg['columna_join_permisos'];
            $identificadores[] = $cfg['columna_join_externa'];
        }
        foreach ($identificadores as $ident) {
            if (!self::validarIdentificador((string)$ident)) {
                self::registrarFalloSync($idRolPortal, $idModulo, "Identificador con forma inválida en la configuración: '{$ident}'.");
                sqlsrv_close($c);
                return;
            }
        }
        if (!self::tablaYColumnasExisten($c, (string)$cfg['tabla_permisos'], array_filter([
                $cfg['columna_rol_id'], $cfg['columna_visualizar'], $cfg['columna_crear'], $cfg['columna_editar'], $cfg['columna_eliminar'],
                $modoJoin ? $cfg['columna_join_permisos'] : $cfg['columna_identificador'],
            ]))) {
            self::registrarFalloSync($idRolPortal, $idModulo, "tabla_permisos='{$cfg['tabla_permisos']}' no existe o le faltan columnas configuradas — revisar CORE_Modulos_Sync_Config.");
            sqlsrv_close($c);
            return;
        }
        if ($modoJoin && !self::tablaYColumnasExisten($c, (string)$cfg['tabla_join'], [$cfg['columna_join_externa'], $cfg['columna_identificador']])) {
            self::registrarFalloSync($idRolPortal, $idModulo, "tabla_join='{$cfg['tabla_join']}' no existe o le faltan columnas configuradas — revisar CORE_Modulos_Sync_Config.");
            sqlsrv_close($c);
            return;
        }

        // 2. Cargar el mapeo opción→identificador_externo de ESTE módulo.
        $db = Database::getInstance();
        $nodos = $db->fetchAll($db->query(
            'SELECT opcion, identificador_externo FROM CORE_Modulos_Sync_Nodos WHERE id_modulo=?',
            [[$idModulo, SQLSRV_PARAM_IN]]
        ));
        $mapaNodos = [];
        foreach ($nodos as $n) { $mapaNodos[(int)$n['opcion']] = (string)$n['identificador_externo']; }

        // 3. Aplicar cada cambio.
        $aplicados = [];
        foreach ($cambiosDeEsteModulo as $opcion => $nivel) {
            $identificador = $mapaNodos[$opcion] ?? null;
            if ($identificador === null) continue; // esta opción del portal no está mapeada para este módulo

            $puedeV = $nivel >= 1 ? 1 : 0; $puedeC = $nivel >= 2 ? 1 : 0;
            $puedeE = $nivel >= 3 ? 1 : 0; $puedeD = $nivel >= 4 ? 1 : 0;

            if ($modoJoin) {
                $sql = sprintf(
                    'UPDATE p SET p.[%s]=?, p.[%s]=?, p.[%s]=?, p.[%s]=?
                     FROM [%s] p JOIN [%s] j ON j.[%s]=p.[%s]
                     WHERE p.[%s]=? AND j.[%s]=?',
                    $cfg['columna_visualizar'], $cfg['columna_crear'], $cfg['columna_editar'], $cfg['columna_eliminar'],
                    $cfg['tabla_permisos'], $cfg['tabla_join'], $cfg['columna_join_externa'], $cfg['columna_join_permisos'],
                    $cfg['columna_rol_id'], $cfg['columna_identificador']
                );
                $params = [$puedeV, $puedeC, $puedeE, $puedeD, $idRolExterno, $identificador];
            } else {
                $sql = sprintf(
                    'MERGE [%1$s] AS t
                     USING (SELECT ? AS r, ? AS i) AS s ON t.[%2$s]=s.r AND t.[%3$s]=s.i
                     WHEN MATCHED THEN UPDATE SET t.[%4$s]=?, t.[%5$s]=?, t.[%6$s]=?, t.[%7$s]=?
                     WHEN NOT MATCHED THEN INSERT ([%2$s],[%3$s],[%4$s],[%5$s],[%6$s],[%7$s])
                         VALUES (s.r, s.i, ?, ?, ?, ?);',
                    $cfg['tabla_permisos'], $cfg['columna_rol_id'], $cfg['columna_identificador'],
                    $cfg['columna_visualizar'], $cfg['columna_crear'], $cfg['columna_editar'], $cfg['columna_eliminar']
                );
                $params = [$idRolExterno, $identificador, $puedeV, $puedeC, $puedeE, $puedeD, $puedeV, $puedeC, $puedeE, $puedeD];
            }

            $stmt = sqlsrv_query($c, $sql, $params);
            if ($stmt === false) {
                self::registrarFalloSync($idRolPortal, $idModulo, "Falló la escritura para opción={$opcion} ({$identificador}): " . json_encode(sqlsrv_errors()));
                continue;
            }
            sqlsrv_free_stmt($stmt);
            $aplicados[$identificador] = $nivel;
        }

        if ($aplicados && !empty($cfg['sp_auditoria'])) {
            if (self::validarIdentificador((string)$cfg['sp_auditoria'])) {
                $descripcion = "Nivel(es) " . json_encode($aplicados) . " aplicado(s) desde /admin/roles/{$idRolPortal}/permisos (rol externo={$idRolExterno}).";
                $stmt = sqlsrv_query($c, "EXEC [{$cfg['sp_auditoria']}] ?, ?, ?, ?, ?",
                    ['CENTRAL', (string)($cfg['nombre_modulo'] ?? 'CORE'), 'SYNC_PERMISO_DESDE_PORTAL', $descripcion, '127.0.0.1']);
                if ($stmt !== false) sqlsrv_free_stmt($stmt);
            }
        }

        sqlsrv_close($c);
    }

    /** ^[A-Za-z_][A-Za-z0-9_]{0,127}$ — regla de identificador de SQL Server (sin corchetes/espacios/puntos). */
    private static function validarIdentificador(string $ident): bool {
        return $ident !== '' && preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,127}$/', $ident) === 1;
    }

    /** Confirma que tabla y TODAS las columnas dadas existen de verdad en esta conexión. */
    private static function tablaYColumnasExisten($conn, string $tabla, array $columnas): bool {
        $columnas = array_values(array_unique(array_filter($columnas, static fn($c) => $c !== null && $c !== '')));
        if ($columnas === []) return true;
        $placeholders = implode(',', array_fill(0, count($columnas), '?'));
        $stmt = sqlsrv_query($conn,
            "SELECT COUNT(DISTINCT COLUMN_NAME) AS n FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME IN ($placeholders)",
            array_merge([$tabla], $columnas)
        );
        if ($stmt === false) return false;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return $row && (int)$row['n'] === count($columnas);
    }

    /** Resuelve id_rol_externo para cualquier id_modulo mapeado en CORE_Roles_Modulo_Map. */
    private static function mapaRolExterno(int $idRolPortal, int $idModulo): ?int {
        $db = Database::getInstance();
        $row = $db->fetch($db->query(
            'SELECT id_rol_externo FROM CORE_Roles_Modulo_Map WHERE id_modulo=? AND id_rol_portal=?',
            [[$idModulo, SQLSRV_PARAM_IN], [$idRolPortal, SQLSRV_PARAM_IN]]
        ));
        return $row ? (int)$row['id_rol_externo'] : null;
    }

    private static function registrarFalloSync(int $idRolPortal, int $idModulo, string $detalle): void {
        ModuleSecurity::audit('CORE', 'SYNC_FALLO', 'CORE_Modulos_Sync_Config', (string)$idModulo, null, null, 'FALLO',
            "Rol portal #{$idRolPortal} → módulo #{$idModulo}: {$detalle}");
    }
}
