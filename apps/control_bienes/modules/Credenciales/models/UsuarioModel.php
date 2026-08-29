<?php
/**
 * USUARIOMODEL.PHP - Modelo de Control de Acceso (Usuarios)
 * Compatible con PHP 7.4, 8.3 y 8.4
 */

require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';

class UsuarioModel extends Model {

    /**
     * Mantiene las cuentas operativas alineadas con el personal activo de
     * Talento Humano. La cuenta local "admin" no participa en la sincronizacion.
     * Para una cuenta nueva, la cedula es el usuario y la contrasena inicial.
     */
    public function sincronizarTalentoHumano(): void {
        static $sincronizado = false;
        if ($sincronizado || (defined('DB_DRIVER') && DB_DRIVER !== 'sqlsrv')) {
            return;
        }
        $sincronizado = true;

        try {
            $codigoCuenta = "'TH-' + RIGHT(REPLICATE('0', 6) + CAST(origen.empleado_id AS VARCHAR(20)), 6)";

            $this->db->exec("UPDATE destino
                SET destino.nombre = origen.apellidos_nombres,
                    destino.usuario = LTRIM(RTRIM(origen.cedula)),
                    destino.activo = 1
                FROM dbo.inv_usuarios destino
                JOIN Talento_Humano.dbo.vw_th_directorio_empleados origen
                  ON destino.secuencial = {$codigoCuenta}
                WHERE origen.estado = 1
                  AND NULLIF(LTRIM(RTRIM(origen.cedula)), '') IS NOT NULL;");

            $this->db->exec("UPDATE destino SET destino.activo = 0
                FROM dbo.inv_usuarios destino
                WHERE destino.secuencial LIKE 'TH-%'
                  AND NOT EXISTS (
                      SELECT 1
                      FROM Talento_Humano.dbo.vw_th_directorio_empleados origen
                      WHERE destino.secuencial = 'TH-' + RIGHT(REPLICATE('0', 6) + CAST(origen.empleado_id AS VARCHAR(20)), 6)
                        AND origen.estado = 1
                  );");

            $stmtNuevos = $this->db->query("SELECT origen.empleado_id,
                    LTRIM(RTRIM(origen.cedula)) AS cedula,
                    origen.apellidos_nombres AS nombre
                FROM Talento_Humano.dbo.vw_th_directorio_empleados origen
                WHERE origen.estado = 1
                  AND NULLIF(LTRIM(RTRIM(origen.cedula)), '') IS NOT NULL
                  AND NOT EXISTS (
                      SELECT 1 FROM dbo.inv_usuarios destino
                      WHERE destino.secuencial = 'TH-' + RIGHT(REPLICATE('0', 6) + CAST(origen.empleado_id AS VARCHAR(20)), 6)
                  )
                ORDER BY origen.empleado_id;");

            $insertar = $this->db->prepare("INSERT INTO dbo.inv_usuarios
                (secuencial, nombre, usuario, contrasena, rol, activo, tiempo_inactividad)
                VALUES (:secuencial, :nombre, :usuario, :contrasena, 'Operador', 1, NULL)");

            foreach ($stmtNuevos->fetchAll() as $persona) {
                $cedula = trim((string)$persona['cedula']);
                $insertar->execute([
                    ':secuencial' => 'TH-' . str_pad((string)$persona['empleado_id'], 6, '0', STR_PAD_LEFT),
                    ':nombre' => $persona['nombre'],
                    ':usuario' => $cedula,
                    // hash('sha256', $cedula) primero: la clave inicial de
                    // estas cuentas auto-provisionadas ES la propia cédula, y
                    // cuando la escriban en el login, js/password-hash.js la
                    // va a hashear en el navegador antes de mandarla -- hay
                    // que simular ese mismo paso acá para que calce.
                    ':contrasena' => hash_password_secure(hash('sha256', $cedula))
                ]);
            }

            // Todo funcionario activo necesita al menos una pagina de entrada
            // valida. Los demas modulos siguen administrandose desde Permisos.
            $this->db->exec("INSERT INTO dbo.inv_permisos (usuario_id, route_key)
                SELECT cuenta.id, 'inventario'
                FROM dbo.inv_usuarios cuenta
                WHERE cuenta.secuencial LIKE 'TH-%'
                  AND cuenta.activo = 1
                  AND NOT EXISTS (
                      SELECT 1 FROM dbo.inv_permisos permiso
                      WHERE permiso.usuario_id = cuenta.id
                        AND permiso.route_key = 'inventario'
                  );");
        } catch (Exception $e) {
            // El administrador local debe poder ingresar incluso si TH no esta disponible.
            error_log('No se pudo sincronizar usuarios desde Talento Humano: ' . $e->getMessage());
        }
    }

    public function obtenerTodos() {
        $this->sincronizarTalentoHumano();
        $stmt = $this->db->query("SELECT *, CASE WHEN secuencial LIKE 'TH-%' THEN 'Talento Humano' ELSE 'Sistema' END AS origen FROM inv_usuarios ORDER BY CASE WHEN usuario = 'admin' THEN 0 ELSE 1 END, nombre ASC");
        return $stmt->fetchAll();
    }

    /**
     * Obtiene solamente el bloque de usuarios que se mostrara en pantalla.
     * El limite se aplica en la base de datos para evitar cargar y renderizar
     * todas las cuentas cuando el directorio de Talento Humano crezca.
     */
    public function obtenerPagina(int $pagina = 1, int $porPagina = 25): array {
        $this->sincronizarTalentoHumano();

        $pagina = max(1, $pagina);
        $porPagina = in_array($porPagina, [25, 50, 100], true) ? $porPagina : 25;

        $total = (int)$this->db->query("SELECT COUNT(*) FROM inv_usuarios")->fetchColumn();
        $totalPaginas = max(1, (int)ceil($total / $porPagina));
        $pagina = min($pagina, $totalPaginas);
        $offset = ($pagina - 1) * $porPagina;

        $orden = "CASE WHEN usuario = 'admin' THEN 0 ELSE 1 END, nombre ASC, id ASC";
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';

        if ($driver === 'sqlsrv') {
            $sql = "SELECT id, secuencial, nombre, usuario, rol, activo, tiempo_inactividad,
                           CASE WHEN secuencial LIKE 'TH-%' THEN 'Talento Humano' ELSE 'Sistema' END AS origen
                    FROM inv_usuarios
                    ORDER BY {$orden}
                    OFFSET {$offset} ROWS FETCH NEXT {$porPagina} ROWS ONLY";
        } else {
            $sql = "SELECT id, secuencial, nombre, usuario, rol, activo, tiempo_inactividad,
                           CASE WHEN secuencial LIKE 'TH-%' THEN 'Talento Humano' ELSE 'Sistema' END AS origen
                    FROM inv_usuarios
                    ORDER BY {$orden}
                    LIMIT {$porPagina} OFFSET {$offset}";
        }

        return [
            'items' => $this->db->query($sql)->fetchAll(),
            'total' => $total,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total_paginas' => $totalPaginas
        ];
    }

    public function obtenerActivos() {
        $this->sincronizarTalentoHumano();
        $stmt = $this->db->query("SELECT * FROM inv_usuarios WHERE activo = 1 ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function buscarPorId($id) {
        $stmt = $this->db->prepare("SELECT * FROM inv_usuarios WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function buscarPorUsuario($usuario) {
        $this->sincronizarTalentoHumano();
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        $sql = ($driver === 'sqlsrv') ? 
               "SELECT TOP 1 * FROM inv_usuarios WHERE usuario = :usr AND activo = 1" : 
               "SELECT * FROM inv_usuarios WHERE usuario = :usr AND activo = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':usr' => $usuario]);
        return $stmt->fetch();
    }

    /** Datos breves para la tarjeta de perfil de la cabecera. */
    public function obtenerContextoPerfil(array $usuario): array {
        $rol = trim((string)($usuario['rol'] ?? ''));
        $resultado = [
            'departamento' => strtolower($rol) === 'administrador' ? 'Administración del Sistema' : 'Departamento no asignado',
            'cargo' => $rol,
        ];

        $secuencial = (string)($usuario['secuencial'] ?? '');
        if (strpos($secuencial, 'TH-') !== 0 || (defined('DB_DRIVER') && DB_DRIVER !== 'sqlsrv')) {
            return $resultado;
        }

        $empleadoId = (int)substr($secuencial, 3);
        if ($empleadoId <= 0) return $resultado;

        try {
            $stmt = $this->db->prepare("SELECT TOP 1 direccion_area AS departamento, cargo
                                        FROM Talento_Humano.dbo.vw_th_directorio_empleados
                                        WHERE empleado_id = :empleado_id");
            $stmt->execute([':empleado_id' => $empleadoId]);
            $perfil = $stmt->fetch();
            if ($perfil) {
                $resultado['departamento'] = trim((string)($perfil['departamento'] ?? '')) ?: $resultado['departamento'];
                $resultado['cargo'] = trim((string)($perfil['cargo'] ?? '')) ?: $resultado['cargo'];
            }
        } catch (Exception $e) {
            // La tarjeta mantiene valores de respaldo si Talento Humano no responde.
        }

        return $resultado;
    }

    public function crear($datos) {
        $secuencialObj = new InvSecuencial();
        $secuencial = $secuencialObj->generarSiguiente('acc');

        $hash = hash_password_secure($datos['contrasena']);

        $stmt = $this->db->prepare("INSERT INTO inv_usuarios (secuencial, nombre, usuario, contrasena, rol, activo, tiempo_inactividad) VALUES (:sec, :nombre, :usuario, :pass, :rol, 1, :tiempo_inactividad)");
        $stmt->execute([
            ':sec' => $secuencial,
            ':nombre' => $datos['nombre'],
            ':usuario' => $datos['usuario'],
            ':pass' => $hash,
            ':rol' => $datos['rol'],
            ':tiempo_inactividad' => $datos['tiempo_inactividad']
        ]);
        
        return $this->buscarPorId($this->db->lastInsertId());
    }

    public function actualizar($id, $datos) {
        if (!empty($datos['contrasena'])) {
            $hash = hash_password_secure($datos['contrasena']);
            $stmt = $this->db->prepare("UPDATE inv_usuarios SET nombre = :nombre, usuario = :usuario, contrasena = :pass, rol = :rol, activo = :activo, tiempo_inactividad = :tiempo_inactividad WHERE id = :id");
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':usuario' => $datos['usuario'],
                ':pass' => $hash,
                ':rol' => $datos['rol'],
                ':activo' => isset($datos['activo']) ? (int)$datos['activo'] : 1,
                ':tiempo_inactividad' => $datos['tiempo_inactividad'],
                ':id' => $id
            ]);
        } else {
            $stmt = $this->db->prepare("UPDATE inv_usuarios SET nombre = :nombre, usuario = :usuario, rol = :rol, activo = :activo, tiempo_inactividad = :tiempo_inactividad WHERE id = :id");
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':usuario' => $datos['usuario'],
                ':rol' => $datos['rol'],
                ':activo' => isset($datos['activo']) ? (int)$datos['activo'] : 1,
                ':tiempo_inactividad' => $datos['tiempo_inactividad'],
                ':id' => $id
            ]);
        }
        return $this->buscarPorId($id);
    }

    public function eliminar($id) {
        $stmt = $this->db->prepare("DELETE FROM inv_usuarios WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}

class InvUsuario extends UsuarioModel {}
