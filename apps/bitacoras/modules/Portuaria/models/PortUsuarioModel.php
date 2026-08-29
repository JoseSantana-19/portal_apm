<?php
/**
 * Modelo de usuarios del sistema.
 * Extrae la lógica de consultas que estaba en bit_login.php y bit_register.php
 */
class PortUsuarioModel extends PortBaseModel
{
    /**
     * Obtiene la lista de departamentos activos (para el select de login/register).
     */
    public function departamentosActivos(): array
    {
        return $this->fetchAll(
            "SELECT iddepartamento AS iddepart, nombre AS nom_departa
             FROM dbo.bit_departamentos
             WHERE ISNULL(estado,1) = 1
             ORDER BY nombre"
        );
    }

    /**
     * Busca un usuario por cédula para login (solo cédula + contraseña, por ahora).
     * NOTA: cedula+id_departamento es único en la tabla, pero una misma cédula
     * puede existir en más de un departamento. Por ahora se toma la primera
     * cuenta activa encontrada (id_usuario más bajo). Si en el futuro se
     * necesita desambiguar, este es el punto para hacerlo.
     * @return array|false
     */
    public function buscarParaLogin(string $cedula)
    {
        return $this->fetchOne(
            "SELECT TOP 1
                u.id_usuario,
                u.cedula,
                u.nombres,
                u.id_departamento,
                d.nombre AS nom_departa,
                u.password_hash
            FROM dbo.bit_usuarios_apm u
            INNER JOIN dbo.bit_departamentos d ON d.iddepartamento = u.id_departamento
            WHERE u.cedula = ?
              AND ISNULL(u.estado, 1) = 1
              AND ISNULL(d.estado, 1) = 1
            ORDER BY u.id_usuario ASC",
            [$cedula]
        );
    }

    /**
     * Verifica si ya existe un usuario con esa cédula y departamento.
     */
    public function existeUsuario(string $cedula, int $idDepartamento): bool
    {
        $row = $this->fetchOne(
            "SELECT TOP 1 id_usuario FROM dbo.bit_usuarios_apm WHERE cedula = ? AND id_departamento = ?",
            [$cedula, $idDepartamento]
        );
        return $row !== false;
    }

    /**
     * Registra un nuevo usuario. Devuelve true si se insertó correctamente.
     */
    public function registrar(string $cedula, string $nombre, int $idDepartamento, string $passwordHash): bool
    {
        $columnas = ['cedula', 'nombres', 'id_departamento', 'estado', 'password_hash'];
        $values = ['?', '?', '?', '1', '?'];
        $params = [$cedula, $nombre, $idDepartamento, $passwordHash];

        // Compatibilidad: si existe columna id_area, mapear
        if ($this->tieneColumna('id_area')) {
            $idArea = $this->resolverIdArea($idDepartamento);
            $columnas[] = 'id_area';
            $values[] = '?';
            $params[] = $idArea;
        }

        $sql = "INSERT INTO dbo.bit_usuarios_apm (" . implode(', ', $columnas) . ") VALUES (" . implode(', ', $values) . ")";
        $stmt = $this->query($sql, $params);
        return $stmt !== false;
    }

    /**
     * Obtiene el hash de contraseña del usuario actual.
     */
    public function obtenerPasswordHash(int $idUsuario): string
    {
        $row = $this->fetchOne(
            "SELECT TOP 1 password_hash FROM dbo.bit_usuarios_apm WHERE id_usuario = ? AND ISNULL(estado, 1) = 1",
            [$idUsuario]
        );
        return $row ? trim((string)$row['password_hash']) : '';
    }

    /**
     * Actualiza la contraseña de un usuario.
     */
    public function actualizarPassword(int $idUsuario, string $nuevoHash): bool
    {
        $stmt = $this->query(
            "UPDATE dbo.bit_usuarios_apm SET password_hash = ? WHERE id_usuario = ?",
            [$nuevoHash, $idUsuario]
        );
        return $stmt !== false;
    }

    /**
     * Busca un usuario recién registrado para auto-login.
     * @return array|false
     */
    public function buscarRecienRegistrado(string $cedula, int $idDepartamento)
    {
        return $this->fetchOne(
            "SELECT TOP 1
                u.id_usuario, u.cedula, u.nombres, u.id_departamento,
                d.nombre AS nom_departa
            FROM dbo.bit_usuarios_apm u
            INNER JOIN dbo.bit_departamentos d ON d.iddepartamento = u.id_departamento
            WHERE u.cedula = ? AND u.id_departamento = ? AND ISNULL(u.estado,1) = 1",
            [$cedula, $idDepartamento]
        );
    }

    /**
     * Valida al usuario contra BASE_CORE (identidad central de APM).
     *
     * PENDIENTE REAL: BASE_CORE todavía no está conectada desde este proyecto.
     * Cuando exista la conexión, esto debería ejecutar algo como:
     *
     *   SELECT * FROM dbo.Usuario_core WHERE Cedula = ?
     *   -- o, si el CORE expone un stored procedure:
     *   EXEC dbo.sp_ValidarUsuarioCore @Cedula = ?
     *
     * contra la vista/BD de seguridad que indicó el Ing. Zambrano, y devolver
     * si el usuario existe/está activo ahí (más los campos de seguridad que
     * agregue esa vista: rol, estado, bloqueo, etc.).
     *
     * SIMULADO POR AHORA: siempre responde válido, para no bloquear el login
     * real (que sigue validando 100% contra dbo.bit_usuarios_apm local, sin
     * ningún cambio). Esto solo deja el punto de enganche listo para cuando
     * BASE_CORE esté disponible — no reemplaza la seguridad actual.
     *
     * @return array{ok:bool,valido:bool,origen:string,cedula:string}
     */
    public function validarUsuarioCore(string $cedula): array
    {
        // TODO cuando BASE_CORE esté disponible: reemplazar este bloque por
        // la consulta real de arriba contra la conexión de BASE_CORE
        // (mismo patrón que PortDatabase::connExterna(), agregar
        // Database::connCore() en core/Database.php).
        return [
            'ok' => true,
            'valido' => true,
            'origen' => 'SIMULADO_BASE_CORE',
            'cedula' => $cedula,
        ];
    }

    // --- Utilidades internas ---

    private function tieneColumna(string $columna): bool
    {
        $row = $this->fetchOne(
            "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = N'dbo' AND TABLE_NAME = N'bit_usuarios_apm' AND COLUMN_NAME = ?",
            [$columna]
        );
        return $row !== false;
    }

    private function resolverIdArea(int $idDepartamento): int
    {
        // Intentar mapear directamente
        $row = $this->fetchOne(
            "SELECT TOP 1 id_area FROM dbo.areas_laborales WHERE id_area = ?",
            [$idDepartamento]
        );
        if ($row) {
            return (int)$row['id_area'];
        }

        // Fallback: primer id_area disponible
        $row = $this->fetchOne("SELECT TOP 1 id_area FROM dbo.areas_laborales ORDER BY id_area");
        return $row ? (int)$row['id_area'] : $idDepartamento;
    }

    /**
     * Verifica la contraseña contra el hash almacenado (soporta bcrypt, argon2 y sha256 legado).
     */
    public function verificarPassword(string $password, string $storedHash): bool
    {
        if ($storedHash === '') {
            return false;
        }

        // Esquema nuevo compartido con todo el sistema (portal, TH, Bienes)
        // -- pepper + bcrypt, ver SecurityHelper::hashPassword().
        if (str_starts_with($storedHash, 'peppered:')) {
            return SecurityHelper::verifyPassword($password, $storedHash);
        }

        if (
            substr($storedHash, 0, 4) === '$2y$' ||
            substr($storedHash, 0, 4) === '$2a$' ||
            substr($storedHash, 0, 4) === '$2b$' ||
            substr($storedHash, 0, 7) === '$argon2'
        ) {
            return password_verify($password, $storedHash);
        }

        // Fallback SHA-256 legado
        return hash_equals(strtolower($storedHash), hash('sha256', $password));
    }
}
