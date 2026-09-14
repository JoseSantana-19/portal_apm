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

    /**
     * Sincronización con RBAC propio — UI de CORE_Modulos_Sync_Config /
     * CORE_Modulos_Sync_Nodos (ver core/SyncPermisosModulo.php y
     * db/core_modulos_sync_generico.sql). Agregar un módulo nuevo acá es
     * lo que reemplaza escribir un método PHP hardcodeado por módulo.
     */
    public function sync(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 1]);
        $db = $this->db();
        $modulo = $db->fetch($db->query('SELECT * FROM CORE_Modulos WHERE id_modulo=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$modulo) { http_response_code(404); exit; }
        $config = $db->fetch($db->query('SELECT * FROM CORE_Modulos_Sync_Config WHERE id_modulo=?', [[$id, SQLSRV_PARAM_IN]]));
        $nodos  = $db->fetchAll($db->query('SELECT * FROM CORE_Modulos_Sync_Nodos WHERE id_modulo=? ORDER BY opcion', [[$id, SQLSRV_PARAM_IN]]));
        $this->render('Central/admin/modulo_sync', [
            'pageTitle' => 'Sincronización — ' . $modulo['nombre'],
            'modulo'    => $modulo,
            'config'    => $config,
            'nodos'     => $nodos,
            'success'   => SessionHelper::getFlash('success'),
            'error'     => SessionHelper::getFlash('error'),
            'errors'    => $_SESSION['_form_errors'] ?? [],
            'csrf'      => $this->csrfToken(),
        ]);
        unset($_SESSION['_form_errors']);
    }

    public function guardarSync(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 3]);
        $this->verifyCsrf();

        if (!FormHelper::validate($_POST, [
            'tabla_permisos'        => 'required|min:1|max:128',
            'columna_rol_id'        => 'required|min:1|max:128',
            'columna_identificador' => 'required|min:1|max:128',
            'modo_escritura'        => 'required|in:UPDATE_JOIN,MERGE_DIRECTO',
        ])) {
            $_SESSION['_form_errors'] = FormHelper::errors();
            $this->redirect("/admin/modulos/{$id}/sync");
        }

        $modoJoin = $_POST['modo_escritura'] === 'UPDATE_JOIN';
        if ($modoJoin && (trim($_POST['tabla_join'] ?? '') === '' || trim($_POST['columna_join_permisos'] ?? '') === '' || trim($_POST['columna_join_externa'] ?? '') === '')) {
            $_SESSION['_form_errors'] = ['Modo UPDATE_JOIN requiere tabla_join, columna_join_permisos y columna_join_externa.'];
            $this->redirect("/admin/modulos/{$id}/sync");
        }

        $db = $this->db();
        $existe = $db->fetch($db->query('SELECT id_modulo FROM CORE_Modulos_Sync_Config WHERE id_modulo=?', [[$id, SQLSRV_PARAM_IN]]));

        $vals = [
            'tabla_permisos'        => trim($_POST['tabla_permisos']),
            'columna_rol_id'        => trim($_POST['columna_rol_id']),
            'columna_identificador' => trim($_POST['columna_identificador']),
            'tabla_join'            => $modoJoin ? trim($_POST['tabla_join']) : null,
            'columna_join_permisos' => $modoJoin ? trim($_POST['columna_join_permisos']) : null,
            'columna_join_externa'  => $modoJoin ? trim($_POST['columna_join_externa']) : null,
            'columna_visualizar'    => trim($_POST['columna_visualizar'] ?? '') ?: 'puede_visualizar',
            'columna_crear'         => trim($_POST['columna_crear'] ?? '') ?: 'puede_crear',
            'columna_editar'        => trim($_POST['columna_editar'] ?? '') ?: 'puede_editar',
            'columna_eliminar'      => trim($_POST['columna_eliminar'] ?? '') ?: 'puede_eliminar',
            'modo_escritura'        => $_POST['modo_escritura'],
            'sp_auditoria'          => trim($_POST['sp_auditoria'] ?? '') ?: null,
            'estado'                => isset($_POST['estado']) ? 1 : 0,
            'notas'                 => trim($_POST['notas'] ?? '') ?: null,
        ];

        if ($existe) {
            $db->query(
                'UPDATE CORE_Modulos_Sync_Config SET
                    tabla_permisos=?, columna_rol_id=?, columna_identificador=?,
                    tabla_join=?, columna_join_permisos=?, columna_join_externa=?,
                    columna_visualizar=?, columna_crear=?, columna_editar=?, columna_eliminar=?,
                    modo_escritura=?, sp_auditoria=?, estado=?, notas=?, fecha_actualizacion=SYSDATETIME()
                 WHERE id_modulo=?',
                [
                    [$vals['tabla_permisos'], SQLSRV_PARAM_IN], [$vals['columna_rol_id'], SQLSRV_PARAM_IN], [$vals['columna_identificador'], SQLSRV_PARAM_IN],
                    [$vals['tabla_join'], SQLSRV_PARAM_IN], [$vals['columna_join_permisos'], SQLSRV_PARAM_IN], [$vals['columna_join_externa'], SQLSRV_PARAM_IN],
                    [$vals['columna_visualizar'], SQLSRV_PARAM_IN], [$vals['columna_crear'], SQLSRV_PARAM_IN], [$vals['columna_editar'], SQLSRV_PARAM_IN], [$vals['columna_eliminar'], SQLSRV_PARAM_IN],
                    [$vals['modo_escritura'], SQLSRV_PARAM_IN], [$vals['sp_auditoria'], SQLSRV_PARAM_IN], [$vals['estado'], SQLSRV_PARAM_IN], [$vals['notas'], SQLSRV_PARAM_IN],
                    [$id, SQLSRV_PARAM_IN],
                ]
            );
        } else {
            $db->query(
                'INSERT INTO CORE_Modulos_Sync_Config
                    (id_modulo, tabla_permisos, columna_rol_id, columna_identificador,
                     tabla_join, columna_join_permisos, columna_join_externa,
                     columna_visualizar, columna_crear, columna_editar, columna_eliminar,
                     modo_escritura, sp_auditoria, estado, notas)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                [
                    [$id, SQLSRV_PARAM_IN],
                    [$vals['tabla_permisos'], SQLSRV_PARAM_IN], [$vals['columna_rol_id'], SQLSRV_PARAM_IN], [$vals['columna_identificador'], SQLSRV_PARAM_IN],
                    [$vals['tabla_join'], SQLSRV_PARAM_IN], [$vals['columna_join_permisos'], SQLSRV_PARAM_IN], [$vals['columna_join_externa'], SQLSRV_PARAM_IN],
                    [$vals['columna_visualizar'], SQLSRV_PARAM_IN], [$vals['columna_crear'], SQLSRV_PARAM_IN], [$vals['columna_editar'], SQLSRV_PARAM_IN], [$vals['columna_eliminar'], SQLSRV_PARAM_IN],
                    [$vals['modo_escritura'], SQLSRV_PARAM_IN], [$vals['sp_auditoria'], SQLSRV_PARAM_IN], [$vals['estado'], SQLSRV_PARAM_IN], [$vals['notas'], SQLSRV_PARAM_IN],
                ]
            );
        }

        ModuleSecurity::audit('CORE', $existe ? 'ACTUALIZAR' : 'CREAR', 'CORE_Modulos_Sync_Config', (string)$id, null, $vals, 'EXITO', 'Config de sync genérico');
        SessionHelper::flash('success', 'Configuración de sincronización guardada.');
        $this->redirect("/admin/modulos/{$id}/sync");
    }

    public function agregarNodoSync(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 3]);
        $this->verifyCsrf();

        $opcion = (int)($_POST['opcion'] ?? 0);
        $identificador = trim($_POST['identificador_externo'] ?? '');
        if ($opcion < 1 || $identificador === '') {
            SessionHelper::flash('error', 'Opción e identificador externo son obligatorios.');
            $this->redirect("/admin/modulos/{$id}/sync");
        }

        $db = $this->db();
        $db->query(
            'MERGE CORE_Modulos_Sync_Nodos AS t
             USING (SELECT ? AS id_modulo, ? AS opcion) AS s ON t.id_modulo=s.id_modulo AND t.opcion=s.opcion
             WHEN MATCHED THEN UPDATE SET identificador_externo=?, descripcion=?
             WHEN NOT MATCHED THEN INSERT (id_modulo, opcion, identificador_externo, descripcion) VALUES (s.id_modulo, s.opcion, ?, ?);',
            [
                [$id, SQLSRV_PARAM_IN], [$opcion, SQLSRV_PARAM_IN],
                [$identificador, SQLSRV_PARAM_IN], [trim($_POST['descripcion'] ?? '') ?: null, SQLSRV_PARAM_IN],
                [$identificador, SQLSRV_PARAM_IN], [trim($_POST['descripcion'] ?? '') ?: null, SQLSRV_PARAM_IN],
            ]
        );
        ModuleSecurity::audit('CORE', 'CREAR', 'CORE_Modulos_Sync_Nodos', "{$id}-{$opcion}", null, $_POST, 'EXITO', null);
        SessionHelper::flash('success', "Nodo opción={$opcion} → '{$identificador}' guardado.");
        $this->redirect("/admin/modulos/{$id}/sync");
    }

    public function eliminarNodoSync(int $id, int $op): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MODULOS, 3]);
        $this->verifyCsrf();
        $db = $this->db();
        $db->query('DELETE FROM CORE_Modulos_Sync_Nodos WHERE id_modulo=? AND opcion=?', [[$id, SQLSRV_PARAM_IN], [$op, SQLSRV_PARAM_IN]]);
        ModuleSecurity::audit('CORE', 'ELIMINAR', 'CORE_Modulos_Sync_Nodos', "{$id}-{$op}", null, null, 'EXITO', null);
        SessionHelper::flash('success', 'Nodo eliminado.');
        $this->redirect("/admin/modulos/{$id}/sync");
    }
}
