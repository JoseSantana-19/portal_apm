<?php
/**
 * ThCuentaController — Crea una cuenta de acceso del portal (CORE_Usuarios, BD PORTAL_APM)
 * a partir de un empleado de la BD Talento_Humano, con AUTOSUGERENCIA de
 * departamento y rol (tabla TH_Unidad_Map + puesto del empleado).
 *
 * Solo administradores (nivel >= 3). Deduplica por cédula (identificacion).
 */
class ThCuentaController extends Controller
{
    private ThEmpleadoModel $emp;

    public function __construct()
    {
        parent::__construct();
        $this->emp = new ThEmpleadoModel();
    }

    private function db(): Database { return Database::getInstance(); }

    /** Cédulas que YA tienen cuenta en el portal (para marcar el directorio). */
    public static function cedulasConCuenta(): array
    {
        $db  = Database::getInstance();
        $rows = $db->fetchAll($db->query("SELECT cedula FROM CORE_Usuarios WHERE cedula IS NOT NULL"));
        return array_column($rows, 'cedula');
    }

    /** GET /th/empleado/{id}/cuenta — formulario de creación de cuenta. */
    public function crear(int $id): void
    {
        $this->requireLevel(3);

        $e = $this->emp->obtenerDetalleCompleto($id);
        if (!$e) {
            SessionHelper::flash('error', 'Empleado no encontrado.');
            $this->redirect('/th/directorio');
        }

        $cedula = $e['identificacion'] ?? $e['cedula'] ?? '';
        // ¿Ya tiene cuenta?
        if ($cedula !== '' && in_array($cedula, self::cedulasConCuenta(), true)) {
            SessionHelper::flash('error', "El funcionario (cédula {$cedula}) ya tiene una cuenta de acceso.");
            $this->redirect('/th/directorio');
        }

        [$deptoId, $rolId] = $this->autoMapeo($e);

        $this->render('Talento_Humano/cuenta', [
            'pageTitle'    => 'Crear cuenta de acceso',
            'empleado'     => $e,
            'sugUsuario'   => $this->sugerirUsuario($e['nombres'] ?? '', $e['apellidos'] ?? ''),
            'sugPassword'  => 'Apm' . substr(preg_replace('/\D/', '', $cedula) ?: '2026', -4) . '*',
            'deptoAuto'    => $deptoId,
            'rolAuto'      => $rolId,
            'departamentos'=> $this->db()->fetchAll($this->db()->query(
                'SELECT id_departamento, nombre FROM CORE_Departamentos WHERE estado=1 ORDER BY nombre')),
            'roles'        => $this->db()->fetchAll($this->db()->query(
                'SELECT id_rol, nombre, nivel_jerarquia FROM CORE_Roles WHERE estado=1 ORDER BY nombre')),
            'csrf'         => $this->csrfToken(),
        ]);
    }

    /** POST /th/empleado/cuenta — crea la cuenta. */
    public function guardar(): void
    {
        $this->requireLevel(3);
        $this->verifyCsrf();

        $db       = $this->db();
        $cedula   = trim($_POST['identificacion'] ?? '');
        $nombre   = trim($_POST['nombre_completo'] ?? '');
        $correo   = trim($_POST['correo'] ?? '');
        $usuario  = strtolower(trim($_POST['nombre_usuario'] ?? ''));
        $pass     = $_POST['password'] ?? '';
        $deptoId  = (int)($_POST['id_departamento'] ?? 0);
        $rolId    = (int)($_POST['id_rol'] ?? 0);

        // Validación mínima
        if ($cedula === '' || $nombre === '' || $correo === '' || $usuario === '' || strlen($pass) < 6 || $rolId === 0) {
            SessionHelper::flash('error', 'Datos incompletos (usuario, correo, contraseña ≥6, rol y departamento).');
            $this->redirect('/th/directorio');
        }

        // Deduplicación / unicidad
        if (in_array($cedula, self::cedulasConCuenta(), true)) {
            SessionHelper::flash('error', "Ya existe una cuenta para la cédula {$cedula}.");
            $this->redirect('/th/directorio');
        }
        if ($db->fetch($db->query('SELECT id_usuario FROM CORE_Usuarios WHERE nombre_usuario=?', [$usuario]))) {
            SessionHelper::flash('error', "El nombre de usuario '{$usuario}' ya está en uso.");
            $this->redirect('/th/directorio');
        }
        if ($db->fetch($db->query('SELECT id_usuario FROM CORE_Usuarios WHERE correo=?', [$correo]))) {
            SessionHelper::flash('error', "El correo '{$correo}' ya está registrado.");
            $this->redirect('/th/directorio');
        }

        // Nivel de jerarquía = el del rol elegido
        $rol   = $db->fetch($db->query('SELECT nivel_jerarquia FROM CORE_Roles WHERE id_rol=? AND estado=1', [[$rolId, SQLSRV_PARAM_IN]]));
        if (!$rol) {
            SessionHelper::flash('error', 'Rol inválido.');
            $this->redirect('/th/directorio');
        }
        $nivel = (int)$rol['nivel_jerarquia'];
        $hash  = SecurityHelper::hashPassword($pass);

        $db->beginTransaction();
        try {
            $db->query(
                'INSERT INTO CORE_Usuarios
                    (nombre_usuario, correo, nombre_completo, hash_contrasena, nivel_jerarquia,
                     id_departamento, cedula, requiere_cambio_pass, estado)
                 VALUES (?,?,?,?,?,?,?,1,1)',
                [
                    [$usuario, SQLSRV_PARAM_IN],
                    [$correo,  SQLSRV_PARAM_IN],
                    [$nombre,  SQLSRV_PARAM_IN],
                    [$hash,    SQLSRV_PARAM_IN],
                    [$nivel,   SQLSRV_PARAM_IN],
                    [$deptoId ?: null, SQLSRV_PARAM_IN],
                    [$cedula,  SQLSRV_PARAM_IN],
                ]
            );
            // SCOPE_IDENTITY() es NULL tras un INSERT parametrizado (corre en el
            // scope de sp_executesql). Recuperamos el id por la cédula (UNIQUE).
            $newRow = $db->fetch($db->query('SELECT id_usuario FROM CORE_Usuarios WHERE cedula=?', [$cedula]));
            $newId  = (int)($newRow['id_usuario'] ?? 0);
            if ($newId === 0) { throw new \RuntimeException('No se pudo obtener el id del usuario recién creado.'); }

            $db->query(
                'INSERT INTO CORE_Usuarios_Roles (id_usuario, id_rol, asignado_por) VALUES (?,?,?)',
                [
                    [(int)$newId, SQLSRV_PARAM_IN],
                    [$rolId,      SQLSRV_PARAM_IN],
                    [(int)($_SESSION['user_id'] ?? 0) ?: null, SQLSRV_PARAM_IN],
                ]
            );
            $db->commit();
        } catch (\Throwable $ex) {
            $db->rollback();
            SessionHelper::flash('error', 'No se pudo crear la cuenta: ' . $ex->getMessage());
            $this->redirect('/th/directorio');
        }

        SessionHelper::flash('success',
            "Cuenta creada: usuario '{$usuario}'. Contraseña temporal entregada al funcionario (deberá cambiarla al ingresar).");
        $this->redirect('/th/directorio');
    }

    /* ── Helpers de mapeo ─────────────────────────────────────────────── */

    /** Devuelve [id_departamento, id_rol] autosugeridos para el empleado. */
    private function autoMapeo(array $e): array
    {
        $codigo = $e['codigo_uorg'] ?? '';
        $db     = $this->db();
        $map = $codigo !== '' ? $db->fetch($db->query(
            'SELECT id_departamento, id_rol_director, id_rol_analista FROM TH_Unidad_Map WHERE codigo_uorg=?',
            [$codigo]
        )) : null;

        // Fallback si la unidad no está mapeada: Talento Humano / Analista TH
        if (!$map) {
            return [10, 12];
        }

        $cargo    = mb_strtoupper($e['cargo'] ?? '');
        $esJefe   = (bool)preg_match('/DIRECTOR|DIRECTORA|JEFE|GERENTE|COORDINADOR/u', $cargo);
        $rolId    = $esJefe ? (int)$map['id_rol_director'] : (int)$map['id_rol_analista'];
        return [(int)$map['id_departamento'], $rolId];
    }

    /** Sugerencia de usuario: inicial(nombre) + primer apellido, sin acentos. */
    private function sugerirUsuario(string $nombres, string $apellidos): string
    {
        $limpia = function (string $s): string {
            $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
            return strtolower(preg_replace('/[^a-zA-Z]/', '', $s));
        };
        $n1 = trim(explode(' ', trim($nombres))[0] ?? '');
        $a1 = trim(explode(' ', trim($apellidos))[0] ?? '');
        $base = $limpia(mb_substr($n1, 0, 1)) . $limpia($a1);
        if ($base === '') $base = 'usuario';

        // Asegura unicidad
        $db = $this->db();
        $cand = $base; $i = 1;
        while ($db->fetch($db->query('SELECT id_usuario FROM CORE_Usuarios WHERE nombre_usuario=?', [$cand]))) {
            $cand = $base . (++$i);
        }
        return $cand;
    }
}
