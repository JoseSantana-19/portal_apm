<?php
/**
 * SyncPermisosModulo — sincroniza CORE_Permisos_Nodo con el RBAC propio
 * de un módulo embebido que tenga mapeo en CORE_Roles_Modulo_Map. Fase 1:
 * solo Talento Humano (id_modulo=11) tiene contraparte real que sincronizar.
 */
class SyncPermisosModulo {
    /** Opción MOIS (bajo id_modulo=11) -> codigo_modulo real de th_modulos. */
    private const NODOS_TH = [
        1  => 'dashboard',
        2  => 'directorio',
        3  => 'empleados',
        4  => 'acciones',
        5  => 'movimientos',
        6  => 'socioeconomico',
        7  => 'biblioteca',
        8  => 'maestros',
        9  => 'usuarios',
        10 => 'roles',
        11 => 'politicas',
        12 => 'auditoria',
        13 => 'reportes',
        14 => 'prototipos',
    ];

    public static function centralHaciaTh(int $idRolPortal, array $cambiosPorNodo): void {
        $mapa = self::mapaRolTh($idRolPortal);
        if ($mapa === null) return; // rol sin contraparte en TH, nada que sincronizar

        $conn = require dirname(__DIR__) . '/config/connections.php';
        $opts = ['CharacterSet' => 'UTF-8', 'TrustServerCertificate' => (bool)($conn['options']['trust_cert'] ?? true)];
        if (!empty($conn['credentials']['user'])) {
            $opts['UID'] = $conn['credentials']['user'];
            $opts['PWD'] = $conn['credentials']['pass'];
        }
        $opts['Database'] = $conn['databases']['talento']['name'];
        $c = @sqlsrv_connect($conn['databases']['talento']['server'] ?? $conn['server_default'], $opts);
        if ($c === false) { self::registrarFalloSync($idRolPortal, sqlsrv_errors()); return; }

        foreach ($cambiosPorNodo as $key => $nivel) {
            $parts = explode('-', (string)$key);
            if (count($parts) !== 4) continue;
            [$mod, $op, $it, $sub] = array_map('intval', $parts);
            if ($mod !== 11 || $it !== 0 || $sub !== 0) continue; // solo nodos de opción, único nivel que TH puede reflejar
            $codigoModulo = self::NODOS_TH[$op] ?? null;
            if ($codigoModulo === null) continue;

            $puedeV = $nivel >= 1 ? 1 : 0; $puedeC = $nivel >= 2 ? 1 : 0;
            $puedeE = $nivel >= 3 ? 1 : 0; $puedeD = $nivel >= 4 ? 1 : 0;

            sqlsrv_query($c,
                'UPDATE p SET p.puede_visualizar=?, p.puede_crear=?, p.puede_editar=?, p.puede_eliminar=?
                 FROM dbo.th_permisos_rol p JOIN dbo.th_modulos m ON m.modulo_id=p.modulo_id
                 WHERE p.rol_id=? AND m.codigo_modulo=?',
                [$puedeV, $puedeC, $puedeE, $puedeD, $mapa, $codigoModulo]
            );
            sqlsrv_query($c,
                "EXEC dbo.sp_th_registrar_auditoria ?, 'Sistema', 'SYNC_PERMISO_DESDE_PORTAL', ?, '127.0.0.1'",
                ['CENTRAL', "Nivel {$nivel} aplicado a {$codigoModulo} (rol_id={$mapa}) desde /admin/roles/{$idRolPortal}/permisos."]
            );
        }
        sqlsrv_close($c);
    }

    /** Opción MOIS (bajo id_modulo=12) -> route_key real de Bienes. */
    private const NODOS_BIENES = [
        1 => 'dashboard', 2 => 'inventario', 3 => 'items', 4 => 'inv_items_sistema',
        5 => 'cabeceras', 6 => 'inv_maestros', 7 => 'ingresos', 8 => 'egresos',
        9 => 'talento_directorio', 10 => 'inv_bitacora', 11 => 'reportes',
        12 => 'inv_periodos', 13 => 'inv_secuenciales', 14 => 'usuarios', 15 => 'inv_permisos',
    ];

    public static function centralHaciaBienes(int $idRolPortal, array $cambiosPorNodo): void {
        $mapa = self::mapaRolExterno($idRolPortal, 12);
        if ($mapa === null) return;

        $conn = require dirname(__DIR__) . '/config/connections.php';
        $opts = ['CharacterSet' => 'UTF-8', 'TrustServerCertificate' => (bool)($conn['options']['trust_cert'] ?? true)];
        if (!empty($conn['credentials']['user'])) {
            $opts['UID'] = $conn['credentials']['user'];
            $opts['PWD'] = $conn['credentials']['pass'];
        }
        $opts['Database'] = 'inventario';
        $c = @sqlsrv_connect($conn['databases']['inventario']['server'] ?? $conn['server_default'], $opts);
        if ($c === false) { self::registrarFalloSync($idRolPortal, sqlsrv_errors()); return; }

        foreach ($cambiosPorNodo as $key => $nivel) {
            $parts = explode('-', (string)$key);
            if (count($parts) !== 4) continue;
            [$mod, $op, $it, $sub] = array_map('intval', $parts);
            if ($mod !== 12 || $it !== 0 || $sub !== 0) continue;
            $routeKey = self::NODOS_BIENES[$op] ?? null;
            if ($routeKey === null) continue;

            $puedeV = $nivel >= 1 ? 1 : 0; $puedeC = $nivel >= 2 ? 1 : 0;
            $puedeE = $nivel >= 3 ? 1 : 0; $puedeD = $nivel >= 4 ? 1 : 0;

            sqlsrv_query($c,
                'MERGE dbo.inv_permisos_rol AS t
                 USING (SELECT ? AS rol_id, ? AS route_key) AS s
                 ON t.rol_id=s.rol_id AND t.route_key=s.route_key
                 WHEN MATCHED THEN UPDATE SET puede_visualizar=?, puede_crear=?, puede_editar=?, puede_eliminar=?
                 WHEN NOT MATCHED THEN INSERT (rol_id, route_key, puede_visualizar, puede_crear, puede_editar, puede_eliminar)
                     VALUES (s.rol_id, s.route_key, ?, ?, ?, ?);',
                [$mapa, $routeKey, $puedeV, $puedeC, $puedeE, $puedeD, $puedeV, $puedeC, $puedeE, $puedeD]
            );
        }
        sqlsrv_close($c);
    }

    private static function mapaRolTh(int $idRolPortal): ?int {
        return self::mapaRolExterno($idRolPortal, 11);
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

    private static function registrarFalloSync(int $idRol, $errores): void {
        ModuleSecurity::audit('CORE', 'SYNC_FALLO', 'CORE_Roles_Modulo_Map', (string)$idRol, null, null, 'FALLO',
            'CENTRAL_A_TH_O_BIENES: no se pudo conectar al módulo destino. ' . json_encode($errores));
    }
}
