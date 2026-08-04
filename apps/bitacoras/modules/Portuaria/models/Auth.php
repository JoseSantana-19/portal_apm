<?php
/**
 * Auth (compat Portuaria) — misma API estática que core/Auth.php de
 * portuaria_demoV4, alimentada por la SESIÓN DEL PORTAL APM.
 *
 * El login/logout real lo hace el portal (CORE_Usuarios + sp_Login). Esta
 * clase solo traduce la sesión del portal a la estructura $_SESSION['apm_auth']
 * que esperan las vistas, APIs y páginas legacy del módulo Portuaria.
 *
 * Mapa de permisos de área (origen usaba nombres de departamento de demoV4):
 *  - Área admin (TI/Edificio Administrativo)  → nivel_jerarquia >= 3 o depto admin/TI.
 *  - Seguridad Operativa (Seguridad Integral) → nivel_jerarquia >= 2 o depto con 'SEGURIDAD'.
 *  - Gerencia                                 → nivel_jerarquia >= 4 o depto 'GERENCIA'.
 */
class Auth
{
    // ============ SESIÓN ============

    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        self::hydrateFromPortal();
    }

    /**
     * Puebla $_SESSION['apm_auth'] desde la sesión del portal.
     * La cédula y el nombre del departamento se resuelven una sola vez
     * contra PORTAL_APM y quedan cacheados en la sesión.
     */
    public static function hydrateFromPortal(): void
    {
        if (empty($_SESSION['user_id'])) {
            return;
        }
        $needsHydrate = empty($_SESSION['apm_auth']['id_usuario_portal'])
            || (int)$_SESSION['apm_auth']['id_usuario_portal'] !== (int)$_SESSION['user_id']
            || empty($_SESSION['apm_auth']['id_usuario']);
        if (!$needsHydrate) {
            return;
        }

        $cedula = '';
        $nomDep = '';
        try {
            $db   = Database::getInstance();
            $stmt = $db->query(
                'SELECT u.cedula, d.nombre AS nom_departa
                   FROM CORE_Usuarios u
              LEFT JOIN CORE_Departamentos d ON d.id_departamento = u.id_departamento
                  WHERE u.id_usuario = ?',
                [[(int)$_SESSION['user_id'], SQLSRV_PARAM_IN]]
            );
            $row = $db->fetch($stmt);
            $db->free($stmt);
            if ($row) {
                $cedula = (string)($row['cedula'] ?? '');
                $nomDep = (string)($row['nom_departa'] ?? '');
            }
        } catch (Throwable $e) {
            // sin BD central seguimos con datos mínimos de la sesión
        }

        // Mapeo de área legacy: la matriz de permisos del origen
        // (includes/bit_auth_permissions.php y estas mismas canX()) compara
        // contra los nombres de departamento EXACTOS de demoV4. Se traduce el
        // nivel/departamento del portal a esas áreas; el nombre real queda en _real.
        $nivel = (int)($_SESSION['nivel_jerarquia'] ?? 0);
        $depUpper = mb_strtoupper($nomDep, 'UTF-8');
        if ($nivel >= 3) {
            $depLegacy = 'TECNOLOGIA DE LA INFORMACION';
        } elseif ($nivel === 2 || preg_match('/GARITA|CCTV|VIGILANCIA|SEGURID/u', $depUpper)) {
            // Operativos de seguridad (Garita de Acceso, Vigilancia CCTV…)
            $depLegacy = 'SEGURIDAD INTEGRAL';
        } else {
            $depLegacy = $nomDep;
        }

        $nombres = (string)($_SESSION['nombre_completo'] ?? $_SESSION['nombre_usuario'] ?? '');

        // id_usuario acá NO es CORE_Usuarios.id_usuario: bit_movimientos y
        // bit_rondas_cabecera tienen FK a bit_usuarios_apm.id_usuario, un
        // espacio de IDs completamente distinto. Usar el ID del portal
        // directo (como se hacía antes) coincide por pura casualidad con
        // filas de bit_usuarios_apm y atribuye mal los registros — bug real
        // encontrado en producción: los 7 registros de bit_rondas_cabecera y
        // los 41 de bit_movimientos existentes quedaron todos con
        // id_usuario=2 ("Usuario Seguridad Integral", cuenta demo vieja) en
        // vez del usuario portal que realmente los generó. Se resuelve (o
        // crea) la fila de bit_usuarios_apm por cédula, que es el dato
        // estable compartido entre ambos sistemas de identidad.
        $idUsuarioApm = self::resolveUsuarioApmId($cedula, $nombres, $nomDep);

        $_SESSION['apm_auth'] = [
            'id_usuario'        => $idUsuarioApm,
            'id_usuario_portal' => (int)$_SESSION['user_id'],
            'cedula'           => $cedula,
            'nombres'          => $nombres,
            'id_departamento'  => (int)($_SESSION['id_departamento'] ?? 0),
            'nom_departa'      => $depLegacy,
            'nom_departa_real' => $nomDep,
            'nivel_jerarquia'  => $nivel,
            'login_at'         => date('c'),
            'core_origen'      => 'portal_apm',
        ];
    }

    /**
     * Busca (o crea) la fila de dbo.bit_usuarios_apm correspondiente a la
     * cédula de la sesión del portal. Devuelve 0 si no se pudo resolver
     * (sin cédula, sin catálogo de departamentos, o error de BD) — el
     * llamador debe tratar 0 como "sin atribución de usuario", nunca
     * insertarlo tal cual en una FK.
     */
    private static function resolveUsuarioApmId(string $cedula, string $nombres, string $nomDepartamentoPortal): int
    {
        if ($cedula === '') {
            return 0;
        }
        try {
            require_once __DIR__ . '/PortDatabase.php';
            $conn = PortDatabase::conn();

            $stmt = sqlsrv_query($conn, 'SELECT TOP 1 id_usuario FROM dbo.bit_usuarios_apm WHERE cedula = ?', [$cedula]);
            $row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : false;
            if ($row) {
                return (int)$row['id_usuario'];
            }

            // No existe: crear. bit_usuarios_apm.id_departamento es FK a
            // bit_departamentos (catálogo propio del módulo, espacio de IDs
            // distinto de CORE_Departamentos del portal) — se busca por
            // nombre y si no hay match se cae al primer departamento activo.
            $stmtDep = sqlsrv_query(
                $conn,
                "SELECT TOP 1 iddepartamento FROM dbo.bit_departamentos WHERE ISNULL(estado,1)=1 AND nombre LIKE ? ORDER BY iddepartamento",
                ['%' . $nomDepartamentoPortal . '%']
            );
            $rowDep = $stmtDep ? sqlsrv_fetch_array($stmtDep, SQLSRV_FETCH_ASSOC) : false;
            if (!$rowDep) {
                $stmtDep = sqlsrv_query($conn, "SELECT TOP 1 iddepartamento FROM dbo.bit_departamentos WHERE ISNULL(estado,1)=1 ORDER BY iddepartamento");
                $rowDep = $stmtDep ? sqlsrv_fetch_array($stmtDep, SQLSRV_FETCH_ASSOC) : false;
            }
            $idDep = $rowDep ? (int)$rowDep['iddepartamento'] : 0;
            if ($idDep <= 0) {
                return 0;
            }

            $insert = sqlsrv_query(
                $conn,
                'INSERT INTO dbo.bit_usuarios_apm (cedula, nombres, id_departamento, estado, creado_en) VALUES (?, ?, ?, 1, SYSUTCDATETIME())',
                [$cedula, $nombres !== '' ? $nombres : $cedula, $idDep]
            );
            if ($insert === false) {
                return 0;
            }
            $idStmt = sqlsrv_query($conn, 'SELECT SCOPE_IDENTITY() AS id');
            $idRow = $idStmt ? sqlsrv_fetch_array($idStmt, SQLSRV_FETCH_ASSOC) : false;
            return $idRow ? (int)$idRow['id'] : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function check(): bool
    {
        self::start();
        return !empty($_SESSION['apm_auth']['id_usuario']);
    }

    /** Compat: en el portal el alta de sesión la hace SessionHelper::login(). */
    public static function login(array $userData): void
    {
        self::start();
        $_SESSION['apm_auth'] = [
            'id_usuario'      => (int)($userData['id_usuario'] ?? 0),
            'cedula'          => (string)($userData['cedula'] ?? ''),
            'nombres'         => (string)($userData['nombres'] ?? ''),
            'id_departamento' => (int)($userData['id_departamento'] ?? 0),
            'nom_departa'     => (string)($userData['nom_departa'] ?? ''),
            'nivel_jerarquia' => (int)($userData['nivel_jerarquia'] ?? 0),
            'login_at'        => date('c'),
            'core_origen'     => (string)($userData['core_origen'] ?? 'portal_apm'),
        ];
    }

    public static function logout(): void
    {
        self::start();
        unset($_SESSION['apm_auth']);
        // El cierre real de la sesión del portal es /logout (AuthController del portal).
    }

    public static function user(): ?array
    {
        self::start();
        return self::check() ? $_SESSION['apm_auth'] : null;
    }

    public static function userId(): int
    {
        return (int)($_SESSION['apm_auth']['id_usuario'] ?? $_SESSION['user_id'] ?? 0);
    }

    public static function userName(): string
    {
        return (string)($_SESSION['apm_auth']['nombres'] ?? $_SESSION['nombre_completo'] ?? '');
    }

    public static function departamento(): string
    {
        return (string)($_SESSION['apm_auth']['nom_departa']
            ?? $_SESSION['apm_auth']['DEP_NOMBRE']
            ?? $_SESSION['apm_auth']['nombre_departamento']
            ?? '');
    }

    public static function departamentoId(): int
    {
        return (int)($_SESSION['apm_auth']['id_departamento'] ?? $_SESSION['id_departamento'] ?? 0);
    }

    private static function nivel(): int
    {
        return (int)($_SESSION['nivel_jerarquia'] ?? $_SESSION['apm_auth']['nivel_jerarquia'] ?? 0);
    }

    // ============ GUARD ============

    public static function guard(): void
    {
        if (!self::check()) {
            header('Location: ' . self::loginUrl());
            exit;
        }
    }

    public static function guardApi(): void
    {
        if (!self::check()) {
            self::denyJson('Sesión no activa.', 401);
        }
    }

    // ============ PERMISOS (semántica del origen sobre datos del portal) ============

    private static function areaKey(): string
    {
        $t = preg_replace('/\s+/', ' ', trim(self::departamento()));
        return mb_strtoupper($t, 'UTF-8');
    }

    public static function isTecnologiaInformacion(): bool
    {
        $k = self::areaKey();
        return $k === 'TECNOLOGIA DE LA INFORMACION'
            || str_contains($k, 'TECNOLOG');
    }

    public static function isEdificioAdministrativo(): bool
    {
        return str_contains(self::areaKey(), 'ADMINISTRATIV');
    }

    public static function isAdminArea(): bool
    {
        return self::nivel() >= 3
            || self::isTecnologiaInformacion()
            || self::isEdificioAdministrativo();
    }

    public static function isSeguridadOperativa(): bool
    {
        return str_contains(self::areaKey(), 'SEGURIDAD');
    }

    public static function isJefeArea(): bool
    {
        return self::nivel() >= 3 || self::isTecnologiaInformacion();
    }

    public static function isGerencia(): bool
    {
        if (self::nivel() >= 4) return true;
        if (self::departamentoId() === (int)ID_DEPARTAMENTO_GERENCIA) return true;
        return self::areaKey() === 'GERENCIA';
    }

    // --- Permisos funcionales (misma matriz del origen) ---

    public static function canAccederDashboardJefe(): bool
    {
        $id = self::departamentoId();
        if ($id === (int)ID_DEPARTAMENTO_ADMIN || $id === (int)ID_DEPARTAMENTO_GERENCIA) {
            return true;
        }
        return self::isAdminArea() || self::isGerencia();
    }

    public static function canRegistrarIngreso(): bool
    {
        return self::isAdminArea() || self::isSeguridadOperativa() || self::nivel() >= 2;
    }

    public static function canVerListadoAdmin(): bool
    {
        return self::isAdminArea() || self::isSeguridadOperativa() || self::nivel() >= 2;
    }

    public static function canRegistrarSalida(): bool
    {
        return self::isAdminArea() || self::isSeguridadOperativa() || self::nivel() >= 2;
    }

    public static function canEditarVisita(): bool
    {
        return self::isAdminArea();
    }

    public static function canGestionarMaestrosAcceso(): bool
    {
        return self::isAdminArea() || self::isSeguridadOperativa();
    }

    public static function canEditarVisitaDesdeListado(): bool
    {
        return self::isAdminArea() || self::isSeguridadOperativa();
    }

    public static function canAsignarCedulaGuest(): bool
    {
        return self::isAdminArea();
    }

    public static function canAccederBitacoraRondas(): bool
    {
        return self::isAdminArea() || self::isSeguridadOperativa() || self::nivel() >= 2;
    }

    public static function canConfigurarDiasBitacora(): bool
    {
        return self::isAdminArea() || self::isJefeArea();
    }

    // ============ UTILIDADES ============

    public static function denyJson(string $message, int $statusCode = 403): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private static function loginUrl(): string
    {
        return (defined('APP_URL') ? APP_URL : '') . '/login';
    }
}
