<?php
/**
 * ModuloController — alta/edición del registro central de módulos
 * (CORE_Modulos). Antes esta lista era un array PHP hardcodeado y
 * duplicado en MenuController y AdminController; ahora vive en la BD y
 * un módulo nuevo (nativo o embebido/Patrón B) queda disponible en
 * Estructura del Menú y Roles y Permisos sin tocar código.
 */
class ModuloController extends Controller {
    // Nodo MOIS propio (Central > Administración > Módulos, id_nodo nuevo
    // junto a "Estructura del Menú") — permiso configurable por separado
    // desde /admin/roles/{id}/permisos, no comparte fila con otro nodo.
    private const NODO_MODULOS = [1, 2, 8, 0];

    private function db(): Database { return Database::getInstance(); }

    public function index(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 1]);
        $db = $this->db();
        $modulos = $db->fetchAll($db->query('SELECT * FROM CORE_Modulos ORDER BY orden'));
        $this->render('Central/admin/modulos', [
            'pageTitle' => 'Módulos del Portal',
            'modulos'   => $modulos,
            'total'     => count($modulos),
            'activos'   => count(array_filter($modulos, fn($m) => (int)$m['estado'] === 1)),
            'success'   => SessionHelper::getFlash('success'),
            'error'     => SessionHelper::getFlash('error'),
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function nuevo(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 2]);
        $db = $this->db();
        $maxId = (int)($db->fetch($db->query('SELECT MAX(id_modulo) AS m FROM CORE_Modulos'))['m'] ?? 0);
        $this->render('Central/admin/modulo_form', [
            'pageTitle'   => 'Nuevo Módulo',
            'modulo'      => null,
            'siguienteId' => $maxId + 1,
            'errors'      => $_SESSION['_form_errors'] ?? [],
            'oldInput'    => $_SESSION['_old_input'] ?? [],
            'csrf'        => $this->csrfToken(),
        ]);
        unset($_SESSION['_form_errors'], $_SESSION['_old_input']);
    }

    public function crear(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 2]);
        $this->verifyCsrf();

        if (!FormHelper::validate($_POST, [
            'id_modulo' => 'required|numeric',
            'codigo'    => 'required|min:2|max:30',
            'nombre'    => 'required|min:3|max:150',
            'tipo'      => 'required|in:nativo,embebido',
        ])) {
            $_SESSION['_form_errors'] = FormHelper::errors();
            $_SESSION['_old_input']   = $_POST;
            $this->redirect('/admin/modulos/nuevo');
        }

        $db = $this->db();
        $existe = $db->fetch($db->query(
            'SELECT id_modulo FROM CORE_Modulos WHERE id_modulo=? OR codigo=?',
            [[(int)$_POST['id_modulo'], SQLSRV_PARAM_IN], [strtoupper(trim($_POST['codigo'])), SQLSRV_PARAM_IN]]
        ));
        if ($existe) {
            $_SESSION['_form_errors'] = ['Ya existe un módulo con ese ID o código.'];
            $_SESSION['_old_input']   = $_POST;
            $this->redirect('/admin/modulos/nuevo');
        }

        $db->query(
            'INSERT INTO CORE_Modulos (id_modulo, codigo, nombre, icono, color, tipo, base_url, conexion_bd, orden, estado)
             VALUES (?,?,?,?,?,?,?,?,?,1)',
            [
                [(int)$_POST['id_modulo'],                    SQLSRV_PARAM_IN],
                [strtoupper(trim($_POST['codigo'])),          SQLSRV_PARAM_IN],
                [trim($_POST['nombre']),                      SQLSRV_PARAM_IN],
                [trim($_POST['icono'] ?? '') ?: 'fa-folder',  SQLSRV_PARAM_IN],
                [trim($_POST['color'] ?? '') ?: '#6c757d',    SQLSRV_PARAM_IN],
                [$_POST['tipo'],                              SQLSRV_PARAM_IN],
                [trim($_POST['base_url'] ?? '') ?: null,      SQLSRV_PARAM_IN],
                [trim($_POST['conexion_bd'] ?? '') ?: null,   SQLSRV_PARAM_IN],
                [(int)($_POST['orden'] ?? 0),                 SQLSRV_PARAM_IN],
            ]
        );

        ModuleSecurity::audit('CORE', 'CREAR', 'CORE_Modulos', (string)$_POST['id_modulo'], null, $_POST, 'EXITO', 'Alta de módulo desde /admin/modulos');
        SessionHelper::flash('success', 'Módulo creado. Ya está disponible en Estructura del Menú y Roles y Permisos.');
        $this->redirect('/admin/modulos');
    }

    public function editar(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 1]);
        $db = $this->db();
        $modulo = $db->fetch($db->query('SELECT * FROM CORE_Modulos WHERE id_modulo=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$modulo) { http_response_code(404); exit; }
        $this->render('Central/admin/modulo_form', [
            'pageTitle' => 'Editar Módulo',
            'modulo'    => $modulo,
            'errors'    => $_SESSION['_form_errors'] ?? [],
            'oldInput'  => $_SESSION['_old_input'] ?? [],
            'csrf'      => $this->csrfToken(),
        ]);
        unset($_SESSION['_form_errors'], $_SESSION['_old_input']);
    }

    public function actualizar(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 3]);
        $this->verifyCsrf();
        $db = $this->db();
        $db->query(
            'UPDATE CORE_Modulos SET nombre=?, icono=?, color=?, tipo=?, base_url=?, conexion_bd=?, orden=? WHERE id_modulo=?',
            [
                [trim($_POST['nombre']),                     SQLSRV_PARAM_IN],
                [trim($_POST['icono'] ?? '') ?: 'fa-folder', SQLSRV_PARAM_IN],
                [trim($_POST['color'] ?? '') ?: '#6c757d',   SQLSRV_PARAM_IN],
                [$_POST['tipo'],                             SQLSRV_PARAM_IN],
                [trim($_POST['base_url'] ?? '') ?: null,     SQLSRV_PARAM_IN],
                [trim($_POST['conexion_bd'] ?? '') ?: null,  SQLSRV_PARAM_IN],
                [(int)($_POST['orden'] ?? 0),                SQLSRV_PARAM_IN],
                [$id,                                        SQLSRV_PARAM_IN],
            ]
        );
        ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Modulos', (string)$id, null, $_POST, 'EXITO', null);
        SessionHelper::flash('success', 'Módulo actualizado.');
        $this->redirect('/admin/modulos');
    }

    public function toggle(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 3]);
        $this->verifyCsrf();
        $db = $this->db();
        $modulo = $db->fetch($db->query('SELECT estado FROM CORE_Modulos WHERE id_modulo=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$modulo) { $this->json(['ok' => false], 404); }
        $nuevo = $modulo['estado'] ? 0 : 1;
        $db->query('UPDATE CORE_Modulos SET estado=? WHERE id_modulo=?', [[$nuevo, SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN]]);
        $this->json(['ok' => true, 'estado' => $nuevo]);
    }
}
