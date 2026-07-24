<?php
/**
 * Usuario Model (legacy adapter). Manages user authentication and profile retrieval.
 * Mirrors the new sp_Login 9-param signature (username IN + 8 OUT).
 * Uses the native sqlsrv driver — NO PDO, NO closeCursor().
 */

require_once dirname(__DIR__, 2) . '/core/Model.php';

class Usuario extends Model {

    /**
     * Authenticate a user. Returns array with 'success'=>true and user data, or 'success'=>false + 'error'.
     * Delegates bcrypt verification to PHP after fetching hash from sp_Login.
     */
    public function authenticate(string $username, string $password): array {
        try {
            $conn = self::db()->getConn();

            $resultado   = null;
            $idUsuario   = null;
            $hash        = null;
            $nivel       = null;
            $reqCambio   = null;
            $nombre      = null;
            $tema        = null;
            $idDepto     = null;

            $params = [
                [$username,   SQLSRV_PARAM_IN],
                [&$resultado, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(30)],
                [&$idUsuario, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_INT,                     SQLSRV_SQLTYPE_INT],
                [&$hash,      SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(512)],
                [&$nivel,     SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_INT,                     SQLSRV_SQLTYPE_TINYINT],
                [&$reqCambio, SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_INT,                     SQLSRV_SQLTYPE_BIT],
                [&$nombre,    SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(150)],
                [&$tema,      SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(20)],
                [&$idDepto,   SQLSRV_PARAM_INOUT, SQLSRV_PHPTYPE_INT,                     SQLSRV_SQLTYPE_INT],
            ];

            $stmt = sqlsrv_prepare($conn, '{CALL sp_Login(?,?,?,?,?,?,?,?,?)}', $params);
            if ($stmt === false || sqlsrv_execute($stmt) === false) {
                sqlsrv_free_stmt($stmt);
                return ['success' => false, 'error' => 'Error interno de autenticación.'];
            }
            while (sqlsrv_next_result($stmt) !== null) {}
            sqlsrv_free_stmt($stmt);

            if ($resultado !== 'OK') {
                $msgs = [
                    'NO_ENCONTRADO' => 'Usuario o contraseña incorrectos.',
                    'INACTIVO'      => 'Usuario inactivo. Contacte al Administrador.',
                    'BLOQUEADO'     => 'Cuenta bloqueada temporalmente. Intente más tarde.',
                ];
                return ['success' => false, 'error' => $msgs[$resultado] ?? 'Error de autenticación.'];
            }

            if (!password_verify($password, $hash)) {
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                sqlsrv_query($conn, '{CALL sp_RegistrarFalloLogin(?,?)}', [
                    [$username, SQLSRV_PARAM_IN],
                    [$ip,       SQLSRV_PARAM_IN],
                ]);
                return ['success' => false, 'error' => 'Usuario o contraseña incorrectos.'];
            }

            return [
                'success'          => true,
                'id'               => (int)$idUsuario,
                'username'         => $username,
                'name'             => $nombre,
                'nivel_jerarquia'  => (int)$nivel,
                'dept_id'          => (int)$idDepto,
                'tema_preferido'   => $tema ?? 'light',
                'req_change_pass'  => (bool)$reqCambio,
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error de autenticación: ' . $e->getMessage()];
        }
    }

    /**
     * Get profile details of a user from CORE_Usuarios.
     */
    public function getProfile(int $idUsuario): array {
        $stmt = $this->query(
            "SELECT u.id_usuario, u.nombre_usuario, u.nombre_completo, u.correo,
                    u.cargo_institucional, u.telefono, u.ultimo_acceso,
                    d.nombre AS nombre_departamento
             FROM CORE_Usuarios u
             LEFT JOIN CORE_Departamentos d ON d.id_departamento = u.id_departamento
             WHERE u.id_usuario = ?",
            [[$idUsuario, SQLSRV_PARAM_IN]]
        );
        $data = $this->fetch($stmt);
        $this->free($stmt);
        return $data ?: [];
    }
}
