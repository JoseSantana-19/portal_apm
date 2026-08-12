<?php
/**
 * PERMISOMODEL.PHP - Modelo de Permisos por Rol y por Usuario
 * inv_permisos_rol = permisos por rol (nuevo). inv_permisos = override
 * individual por usuario nativo (upgradeado a nivel_crud real).
 */

require_once ROOT_PATH . 'core/Model.php';

class PermisoModel extends Model {

    public function __construct() {
        parent::__construct();
    }

    /** Nivel de override individual del usuario para esa route, 0 si no hay fila. */
    private function nivelUsuario(int $usuarioId, string $routeKey): int {
        $stmt = $this->db->prepare("SELECT nivel_crud FROM inv_permisos WHERE usuario_id = :uid AND route_key = :rk");
        $stmt->execute([':uid' => $usuarioId, ':rk' => $routeKey]);
        $v = $stmt->fetchColumn();
        return $v === false ? 0 : (int)$v;
    }

    /** Nivel del rol para esa route, 0 si no hay fila. */
    private function nivelRol(int $rolId, string $routeKey): int {
        $stmt = $this->db->prepare("SELECT
                CASE WHEN puede_eliminar=1 THEN 4 WHEN puede_editar=1 THEN 3
                     WHEN puede_crear=1 THEN 2 WHEN puede_visualizar=1 THEN 1 ELSE 0 END AS nivel
            FROM inv_permisos_rol WHERE rol_id = :rid AND route_key = :rk");
        $stmt->execute([':rid' => $rolId, ':rk' => $routeKey]);
        $v = $stmt->fetchColumn();
        return $v === false ? 0 : (int)$v;
    }

    /** Cascada usuario > rol para cuentas NATIVAS de Bienes (sin puente al portal). */
    public function nivelEfectivoNativo(int $usuarioId, int $rolId, string $routeKey): int {
        $nivelUsr = $this->nivelUsuario($usuarioId, $routeKey);
        if ($nivelUsr > 0) {
            return $nivelUsr;
        }
        return $this->nivelRol($rolId, $routeKey);
    }

    /** true si el nivel efectivo (nativo) cubre $nivelMin. Administrador siempre pasa. */
    public function tieneNivelNativo(int $usuarioId, int $rolId, string $rolNombre, string $routeKey, int $nivelMin): bool {
        if (strtolower($rolNombre) === 'administrador') {
            return true;
        }
        return $this->nivelEfectivoNativo($usuarioId, $rolId, $routeKey) >= $nivelMin;
    }

    /** ['route_key' => nivel_crud] del override individual de un usuario. */
    public function obtenerNivelesUsuario(int $usuarioId): array {
        $stmt = $this->db->prepare("SELECT route_key, nivel_crud FROM inv_permisos WHERE usuario_id = :uid");
        $stmt->execute([':uid' => $usuarioId]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[$row['route_key']] = (int)$row['nivel_crud'];
        }
        return $out;
    }

    /** Reemplaza el override individual completo de un usuario. $niveles = ['route_key' => nivel_crud 1-4]. */
    public function actualizarPermisos(int $usuarioId, array $niveles): void {
        $del = $this->db->prepare("DELETE FROM inv_permisos WHERE usuario_id = :uid");
        $del->execute([':uid' => $usuarioId]);

        if (!empty($niveles)) {
            $ins = $this->db->prepare("INSERT INTO inv_permisos (usuario_id, route_key, nivel_crud) VALUES (:uid, :rk, :nv)");
            foreach ($niveles as $rk => $nivel) {
                $nivel = (int)$nivel;
                $rk = trim((string)$rk);
                if ($rk === '' || $nivel < 1 || $nivel > 4) continue;
                $ins->execute([':uid' => $usuarioId, ':rk' => $rk, ':nv' => $nivel]);
            }
        }
    }

    /** Los 4 roles nativos de Bienes. */
    public function listarRoles(): array {
        $stmt = $this->db->query("SELECT id, nombre FROM inv_roles ORDER BY id");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function rolIdPorNombre(string $nombre): ?int {
        $stmt = $this->db->prepare("SELECT id FROM inv_roles WHERE nombre = :n");
        $stmt->execute([':n' => $nombre]);
        $v = $stmt->fetchColumn();
        return $v === false ? null : (int)$v;
    }

    /** ['route_key' => nivel_crud] de un rol. */
    public function nivelesPorRol(int $rolId): array {
        $stmt = $this->db->prepare("SELECT route_key,
                CASE WHEN puede_eliminar=1 THEN 4 WHEN puede_editar=1 THEN 3
                     WHEN puede_crear=1 THEN 2 WHEN puede_visualizar=1 THEN 1 ELSE 0 END AS nivel
            FROM inv_permisos_rol WHERE rol_id = :rid");
        $stmt->execute([':rid' => $rolId]);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[$row['route_key']] = (int)$row['nivel'];
        }
        return $out;
    }

    /** Reemplaza los permisos completos de un rol. $niveles = ['route_key' => nivel_crud 0-4]. */
    public function guardarPermisosRol(int $rolId, array $niveles): void {
        $del = $this->db->prepare("DELETE FROM inv_permisos_rol WHERE rol_id = :rid");
        $del->execute([':rid' => $rolId]);

        $ins = $this->db->prepare(
            "INSERT INTO inv_permisos_rol (rol_id, route_key, puede_visualizar, puede_crear, puede_editar, puede_eliminar)
             VALUES (:rid, :rk, :v, :c, :e, :d)"
        );
        foreach ($niveles as $rk => $nivel) {
            $nivel = (int)$nivel;
            $rk = trim((string)$rk);
            if ($rk === '' || $nivel < 1) continue;
            $ins->execute([
                ':rid' => $rolId, ':rk' => $rk,
                ':v' => $nivel >= 1 ? 1 : 0, ':c' => $nivel >= 2 ? 1 : 0,
                ':e' => $nivel >= 3 ? 1 : 0, ':d' => $nivel >= 4 ? 1 : 0,
            ]);
        }

        $this->sincronizarHaciaCentral($rolId, $niveles);
    }

    /** Refleja el guardado de permisos de un rol nativo hacia CORE_Permisos_Nodo del portal. */
    private function sincronizarHaciaCentral(int $rolIdNativo, array $niveles): void {
        $rolPortal = $this->rolPortalDesdeInv($rolIdNativo);
        if ($rolPortal === null) return;

        $rutaAOpcion = [
            'dashboard' => 1, 'inventario' => 2, 'items' => 3, 'inv_items_sistema' => 4,
            'cabeceras' => 5, 'inv_maestros' => 6, 'ingresos' => 7, 'egresos' => 8,
            'talento_directorio' => 9, 'inv_bitacora' => 10, 'reportes' => 11,
            'inv_periodos' => 12, 'inv_secuenciales' => 13, 'usuarios' => 14, 'inv_permisos' => 15,
        ];

        try {
            $conexionesPath = dirname(ROOT_PATH, 2) . '/config/connections.php';
            if (!is_file($conexionesPath)) return;
            $conn = require $conexionesPath;
            $opts = ['CharacterSet' => 'UTF-8', 'TrustServerCertificate' => (bool)($conn['options']['trust_cert'] ?? true)];
            if (!empty($conn['credentials']['user'])) { $opts['UID'] = $conn['credentials']['user']; $opts['PWD'] = $conn['credentials']['pass']; }
            $opts['Database'] = $conn['databases']['portal']['name'] ?? 'PORTAL_APM';
            $c = @sqlsrv_connect($conn['databases']['portal']['server'] ?? $conn['server_default'], $opts);
            if ($c === false) return;

            foreach ($rutaAOpcion as $rk => $opcion) {
                $nivel = (int)($niveles[$rk] ?? 0);
                sqlsrv_query($c,
                    'MERGE dbo.CORE_Permisos_Nodo AS t
                     USING (SELECT ? AS id_rol, 12 AS id_modulo, ? AS opcion, 0 AS items, 0 AS subitems, ? AS nivel_crud) AS s
                     ON t.id_rol=s.id_rol AND t.id_modulo=s.id_modulo AND t.opcion=s.opcion AND t.items=s.items AND t.subitems=s.subitems
                     WHEN MATCHED AND s.nivel_crud > 0 THEN UPDATE SET nivel_crud=s.nivel_crud, acceso=1, estado=1
                     WHEN MATCHED AND s.nivel_crud = 0 THEN DELETE
                     WHEN NOT MATCHED AND s.nivel_crud > 0 THEN INSERT (id_rol,id_modulo,opcion,items,subitems,nivel_crud,acceso,estado,fecha_asignacion)
                         VALUES (s.id_rol,s.id_modulo,s.opcion,s.items,s.subitems,s.nivel_crud,1,1,SYSDATETIME());',
                    [$rolPortal, $opcion, $nivel]
                );
            }
            sqlsrv_close($c);
        } catch (Exception $e) {
            // No bloquear el guardado nativo si el portal no esta disponible.
        }
    }

    /** Resuelve el id_rol del portal mapeado a este rol nativo de Bienes (CORE_Roles_Modulo_Map), o null. */
    private function rolPortalDesdeInv(int $rolIdNativo): ?int {
        try {
            $conexionesPath = dirname(ROOT_PATH, 2) . '/config/connections.php';
            if (!is_file($conexionesPath)) return null;
            $conn = require $conexionesPath;
            $opts = ['CharacterSet' => 'UTF-8', 'TrustServerCertificate' => (bool)($conn['options']['trust_cert'] ?? true)];
            if (!empty($conn['credentials']['user'])) { $opts['UID'] = $conn['credentials']['user']; $opts['PWD'] = $conn['credentials']['pass']; }
            $opts['Database'] = $conn['databases']['portal']['name'] ?? 'PORTAL_APM';
            $c = @sqlsrv_connect($conn['databases']['portal']['server'] ?? $conn['server_default'], $opts);
            if ($c === false) return null;
            $stmt = sqlsrv_query($c, 'SELECT id_rol_portal FROM dbo.CORE_Roles_Modulo_Map WHERE id_modulo=12 AND id_rol_externo=?', [$rolIdNativo]);
            $row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : false;
            sqlsrv_close($c);
            return $row ? (int)$row['id_rol_portal'] : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Obtiene los permisos de todos los usuarios (para la pantalla de admin)
     */
    public function obtenerTodosLosPermisos(): array {
        $stmt = $this->db->query(
            "SELECT p.usuario_id, p.route_key, p.nivel_crud, u.nombre
             FROM inv_permisos p
             JOIN inv_usuarios u ON p.usuario_id = u.id
             ORDER BY u.nombre, p.route_key"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

class InvPermiso extends PermisoModel {}
