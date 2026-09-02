<?php
class AdminController extends Controller {

    // Nodos MOIS (id_modulo,opcion,items,subitems) bajo Central > Administración.
    // nivel_crud: 1=Ver, 2=Crear, 3=Editar, 4=Total (ver PORTAL_APM_COMPLETO.sql
    // CORE_Menu_Nodos / CORE_Permisos_Nodo).
    private const NODO_USUARIOS  = [1, 2, 1, 0];
    private const NODO_ROLES     = [1, 2, 3, 0];
    private const NODO_AUDITORIA = [1, 2, 5, 0];

    private function db(): Database {
        return Database::getInstance();
    }

    // ─── Usuarios ─────────────────────────────────────────────────────────────

    public function usuarios(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_USUARIOS, 1]);
        $db = $this->db();
        $dbTh  = defined('DB_TH_NAME') ? DB_TH_NAME : 'Talento_Humano';
        $dbInv = defined('DB_INV_NAME') ? DB_INV_NAME : 'inventario';
        $dbBit = defined('DB_PORTUARIA_NAME') ? DB_PORTUARIA_NAME : 'PortuariaDemo';

        // 1. Usuarios Centrales
        $coreUsers = $db->fetchAll($db->query(
            'SELECT u.id_usuario, u.cedula, u.nombre_usuario, u.nombre_completo, u.correo,
                    u.nivel_jerarquia, u.estado, d.nombre AS departamento, u.requiere_mfa,
                    u.fecha_creacion, u.mfa_activado_en, u.id_empleado_th
             FROM CORE_Usuarios u
             LEFT JOIN CORE_Departamentos d ON d.id_departamento = u.id_departamento
             ORDER BY u.nombre_completo'
        ));

        $usuariosMap = [];

        foreach ($coreUsers as $u) {
            $ced = trim((string)($u['cedula'] ?? ''));
            $usr = trim((string)($u['nombre_usuario'] ?? ''));
            $key = $ced !== '' ? $ced : $usr;
            $usuariosMap[$key] = [
                'id_usuario'        => (int)$u['id_usuario'],
                'id_empleado_th'    => $u['id_empleado_th'] ?? null,
                'cedula'            => $ced,
                'nombre_usuario'    => $usr,
                'nombre_completo'   => strip_tags((string)$u['nombre_completo']),
                'correo'            => $u['correo'],
                'departamento'      => $u['departamento'] ?? 'General',
                'nivel_jerarquia'   => (int)$u['nivel_jerarquia'],
                'estado'            => (int)$u['estado'],
                'requiere_mfa'      => (bool)$u['requiere_mfa'],
                'mfa_activado_en'   => $u['mfa_activado_en'],
                'modulos'           => ['Portal Central'],
                'es_central'        => true,
            ];
        }

        // 2. Talento Humano (Nómina y Servidores)
        try {
            $thUsers = $db->fetchAll($db->query(
                "SELECT e.empleado_id, e.identificacion AS cedula, e.nombres + ' ' + e.apellidos AS nombre_completo,
                        e.correo_institucional, e.correo_personal, u.nombre_unidad, e.estado
                 FROM [{$dbTh}].dbo.th_empleados e
                 LEFT JOIN [{$dbTh}].dbo.th_unidades_organizacionales u ON u.unidad_id = e.unidad_id
                 WHERE e.estado = 1"
            ));
            foreach ($thUsers as $th) {
                $ced = trim((string)($th['cedula'] ?? ''));
                if (!$ced) continue;
                if (isset($usuariosMap[$ced])) {
                    if (!in_array('Talento Humano', $usuariosMap[$ced]['modulos'], true)) {
                        $usuariosMap[$ced]['modulos'][] = 'Talento Humano';
                    }
                    if (empty($usuariosMap[$ced]['id_empleado_th'])) {
                        $usuariosMap[$ced]['id_empleado_th'] = (int)$th['empleado_id'];
                    }
                } else {
                    $usuariosMap[$ced] = [
                        'id_usuario'        => null,
                        'id_empleado_th'    => (int)$th['empleado_id'],
                        'cedula'            => $ced,
                        'nombre_usuario'    => $ced,
                        'nombre_completo'   => strip_tags((string)$th['nombre_completo']),
                        'correo'            => $th['correo_institucional'] ?: ($th['correo_personal'] ?: ''),
                        'departamento'      => $th['nombre_unidad'] ?? 'Talento Humano',
                        'nivel_jerarquia'   => 0,
                        'estado'            => (int)$th['estado'],
                        'requiere_mfa'      => false,
                        'mfa_activado_en'   => null,
                        'modulos'           => ['Talento Humano'],
                        'es_central'        => false,
                    ];
                }
            }
        } catch (Throwable $e) {}

        // 3. Control de Bienes / Inventario
        try {
            $invUsers = $db->fetchAll($db->query(
                "SELECT id, nombre, usuario, rol, activo FROM [{$dbInv}].dbo.inv_usuarios"
            ));
            foreach ($invUsers as $inv) {
                $usr = trim((string)($inv['usuario'] ?? ''));
                $ced = preg_match('/^\d{10}$/', $usr) ? $usr : '';
                $key = $ced !== '' ? $ced : $usr;
                if (isset($usuariosMap[$key])) {
                    if (!in_array('Control de Bienes', $usuariosMap[$key]['modulos'], true)) {
                        $usuariosMap[$key]['modulos'][] = 'Control de Bienes';
                    }
                } else {
                    $usuariosMap[$key] = [
                        'id_usuario'        => null,
                        'id_empleado_th'    => null,
                        'cedula'            => $ced ?: '—',
                        'nombre_usuario'    => $usr,
                        'nombre_completo'   => strip_tags((string)$inv['nombre']),
                        'correo'            => '',
                        'departamento'      => 'Control de Bienes (' . ($inv['rol'] ?? 'Operador') . ')',
                        'nivel_jerarquia'   => ($inv['rol'] === 'Administrador' ? 3 : 0),
                        'estado'            => (int)$inv['activo'],
                        'requiere_mfa'      => false,
                        'mfa_activado_en'   => null,
                        'modulos'           => ['Control de Bienes'],
                        'es_central'        => false,
                    ];
                }
            }
        } catch (Throwable $e) {}

        // 4. Bitácoras Portuarias
        try {
            $bitUsers = $db->fetchAll($db->query(
                "SELECT id_usuario, cedula, nombres, id_departamento, estado FROM [{$dbBit}].dbo.bit_usuarios_apm"
            ));
            foreach ($bitUsers as $bit) {
                $ced = trim((string)($bit['cedula'] ?? ''));
                $key = $ced;
                if ($key && isset($usuariosMap[$key])) {
                    if (!in_array('Bitácoras Portuarias', $usuariosMap[$key]['modulos'], true)) {
                        $usuariosMap[$key]['modulos'][] = 'Bitácoras Portuarias';
                    }
                } elseif ($key) {
                    $usuariosMap[$key] = [
                        'id_usuario'        => null,
                        'id_empleado_th'    => null,
                        'cedula'            => $ced,
                        'nombre_usuario'    => $ced,
                        'nombre_completo'   => strip_tags((string)$bit['nombres']),
                        'correo'            => '',
                        'departamento'      => 'Operaciones Portuarias',
                        'nivel_jerarquia'   => 0,
                        'estado'            => (int)$bit['estado'],
                        'requiere_mfa'      => false,
                        'mfa_activado_en'   => null,
                        'modulos'           => ['Bitácoras Portuarias'],
                        'es_central'        => false,
                    ];
                }
            }
        } catch (Throwable $e) {}

        $this->render('Central/admin/usuarios', [
            'pageTitle' => 'Gestión de Usuarios Multi-Módulo',
            'usuarios'  => array_values($usuariosMap),
            'csrf'      => $this->csrfToken(),
        ]);
    }

    // "Nuevo Usuario" ya NO crea cuentas manuales — toda cuenta nueva se crea
    // exclusivamente desde un empleado de Talento Humano (ver empleadosTh() /
    // crearUsuarioDesdeEmpleado() más abajo). El botón "Nuevo Usuario" de
    // /admin/usuarios enlaza directo a /admin/usuarios/desde-th.

    public function editarUsuario(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_USUARIOS, 1]);

        $db      = $this->db();
        $usuario = $db->fetch($db->query('SELECT * FROM CORE_Usuarios WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$usuario) { http_response_code(404); exit; }

        $asignados = $db->fetchAll($db->query(
            'SELECT id_rol FROM CORE_Usuarios_Roles WHERE id_usuario=? AND estado=1',
            [[$id, SQLSRV_PARAM_IN]]
        ));

        // Permisos individuales (Fase 0 del sistema central de permisos):
        // cascada usuario > rol — mismo árbol que rolPermisos(), acá con
        // el override propio de ESTE usuario en vez del permiso del rol.
        $nodosPermisos = $db->fetchAll($db->query(
            'SELECT id_nodo, id_modulo, opcion, items, subitems, descripcion, url_ruta, icono
             FROM CORE_Menu_Nodos WHERE estado=1
             ORDER BY id_modulo, opcion, items, subitems'
        ));
        $overrides = $db->fetchAll($db->query(
            'SELECT id_modulo, opcion, items, subitems, nivel_crud FROM CORE_Permisos_Nodo_Usuario WHERE id_usuario=? AND estado=1',
            [[$id, SQLSRV_PARAM_IN]]
        ));
        $overridesMap = [];
        foreach ($overrides as $o) {
            $overridesMap["{$o['id_modulo']}-{$o['opcion']}-{$o['items']}-{$o['subitems']}"] = (int)$o['nivel_crud'];
        }
        $treePermisosUsuario = $this->construirArbolPermisos($nodosPermisos, $overridesMap);

        // Nivel que le daría SOLO el/los rol(es) asignado(s), sin ninguna
        // excepción individual — se muestra como referencia ("hereda: Ver")
        // en cada fila del checklist para que quede claro qué cambia una
        // excepción de lo que ya tendría por rol.
        $rolNivelMap = [];
        if (!empty($asignados)) {
            $rolIds = array_map('intval', array_column($asignados, 'id_rol'));
            $placeholders = implode(',', array_fill(0, count($rolIds), '?'));
            $rolPermisos = $db->fetchAll($db->query(
                "SELECT id_modulo, opcion, items, subitems, MAX(nivel_crud) AS nivel_crud
                 FROM CORE_Permisos_Nodo WHERE id_rol IN ({$placeholders}) AND acceso=1 AND estado=1
                 GROUP BY id_modulo, opcion, items, subitems",
                array_map(fn($r) => [$r, SQLSRV_PARAM_IN], $rolIds)
            ));
            foreach ($rolPermisos as $p) {
                $rolNivelMap["{$p['id_modulo']}-{$p['opcion']}-{$p['items']}-{$p['subitems']}"] = (int)$p['nivel_crud'];
            }
        }

        $this->render('Central/admin/usuario_form', [
            'pageTitle'            => 'Editar Usuario',
            'usuario'              => $usuario,
            'deptos'               => $this->deptos(),
            'todosRoles'           => $db->fetchAll($db->query('SELECT id_rol, nombre, codigo FROM CORE_Roles WHERE estado=1 ORDER BY nombre')),
            'rolesAsignadosIds'    => array_map('intval', array_column($asignados, 'id_rol')),
            'treePermisosUsuario'  => $treePermisosUsuario,
            // Claves que SÍ tienen una fila de override real (distingue "sin
            // excepción" de "excepción guardada en nivel 0 = revocado") —
            // construirArbolPermisos() solo expone el nivel resultante,
            // que por defecto también es 0 para un nodo sin fila alguna.
            'overridesActivos'     => $overridesMap,
            'rolNivelMap'          => $rolNivelMap,
            'csrf'                 => $this->csrfToken(),
        ]);
    }

    /**
     * POST /admin/usuarios/{id}/permisos — override de permiso por USUARIO
     * individual (cascada usuario > rol, Fase 0 del sistema central de
     * permisos). Mismo POST shape que guardarPermisos() (permisos[mod-op-it-sub]
     * = nivel 0-4), pero nivel 0 acá SÍ se guarda (revoca explícitamente
     * aunque el rol dé acceso) en vez de omitirse.
     */
    public function guardarPermisosUsuario(int $id): void {
        $this->requireAuth();
        $this->requireLevel(4, [...self::NODO_USUARIOS, 4]);
        $this->verifyCsrf();

        $db = $this->db();
        $antes = $db->fetchAll($db->query(
            'SELECT id_modulo, opcion, items, subitems, nivel_crud FROM CORE_Permisos_Nodo_Usuario WHERE id_usuario=?',
            [[$id, SQLSRV_PARAM_IN]]
        ));
        $db->query('DELETE FROM CORE_Permisos_Nodo_Usuario WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]);

        $despues = [];
        foreach ($_POST['overrides'] ?? [] as $key => $nivel) {
            $nivel = (int)$nivel;
            if ($nivel < 0 || $nivel > 4) continue;
            $parts = explode('-', (string)$key);
            if (count($parts) !== 4) continue;
            [$mod, $op, $it, $sub] = array_map('intval', $parts);
            $db->query(
                'INSERT INTO CORE_Permisos_Nodo_Usuario (id_usuario, id_modulo, opcion, items, subitems, nivel_crud, estado, asignado_por)
                 VALUES (?,?,?,?,?,?,1,?)',
                [
                    [$id, SQLSRV_PARAM_IN], [$mod, SQLSRV_PARAM_IN], [$op, SQLSRV_PARAM_IN],
                    [$it, SQLSRV_PARAM_IN], [$sub, SQLSRV_PARAM_IN], [$nivel, SQLSRV_PARAM_IN],
                    [(int)($_SESSION['user_id'] ?? 0), SQLSRV_PARAM_IN],
                ]
            );
            $despues[(string)$key] = $nivel;
        }

        ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Permisos_Nodo_Usuario', (string)$id, $antes ?: null, $despues ?: null, 'EXITO', 'Override de permisos por usuario individual');

        if (View::isAjax()) {
            $this->json(['ok' => true, 'msg' => 'Permisos individuales guardados.']);
        }

        SessionHelper::flash('success', 'Permisos individuales guardados.');
        $this->redirect('/admin/usuarios/' . $id . '/editar');
    }

    public function actualizarUsuario(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_USUARIOS, 3]);
        $this->verifyCsrf();

        $db     = $this->db();
        $antes  = $db->fetch($db->query(
            'SELECT nombre_completo, correo, nivel_jerarquia, id_departamento, estado FROM CORE_Usuarios WHERE id_usuario=?',
            [[$id, SQLSRV_PARAM_IN]]
        ));

        $db->query(
            'UPDATE CORE_Usuarios SET nombre_completo=?, correo=?, nivel_jerarquia=?, id_departamento=?, estado=? WHERE id_usuario=?',
            [
                [trim($_POST['nombre_completo']),      SQLSRV_PARAM_IN],
                [trim($_POST['correo']),               SQLSRV_PARAM_IN],
                [(int)$_POST['nivel_jerarquia'],       SQLSRV_PARAM_IN],
                [(int)$_POST['id_departamento'],       SQLSRV_PARAM_IN],
                [(int)($_POST['estado'] ?? 1),         SQLSRV_PARAM_IN],
                [$id,                                  SQLSRV_PARAM_IN],
            ]
        );

        $db->query('DELETE FROM CORE_Usuarios_Roles WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]);
        $rolesNuevos = [];
        foreach (array_map('intval', $_POST['roles'] ?? []) as $rolId) {
            if ($rolId > 0) {
                $db->query(
                    'INSERT INTO CORE_Usuarios_Roles (id_usuario, id_rol) VALUES (?,?)',
                    [[$id, SQLSRV_PARAM_IN], [$rolId, SQLSRV_PARAM_IN]]
                );
                $rolesNuevos[] = $rolId;
            }
        }

        ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Usuarios', (string)$id,
            $antes ? [
                'nombre_completo' => $antes['nombre_completo'], 'correo' => $antes['correo'],
                'nivel_jerarquia' => (int)$antes['nivel_jerarquia'], 'id_departamento' => $antes['id_departamento'],
                'estado' => (int)$antes['estado'],
            ] : null,
            [
                'nombre_completo' => trim($_POST['nombre_completo']), 'correo' => trim($_POST['correo']),
                'nivel_jerarquia' => (int)$_POST['nivel_jerarquia'], 'id_departamento' => (int)$_POST['id_departamento'],
                'estado' => (int)($_POST['estado'] ?? 1), 'roles' => $rolesNuevos,
            ]
        );

        SessionHelper::flash('success', 'Usuario actualizado correctamente.');
        $this->redirect('/admin/usuarios');
    }

    /**
     * POST /admin/usuarios/{id}/completo — guardado ÚNICO de la pantalla
     * Editar Usuario (a pedido explícito del usuario 2026-08-13, antes eran
     * 2 acciones/botones separados: actualizarUsuario() para datos+roles y
     * guardarPermisosUsuario() para las excepciones individuales, con el
     * botón de excepciones fallando en silencio por el bug de $PortalAlert).
     * Un solo POST, una sola transacción: si algo falla, no queda a medias.
     */
    public function guardarUsuarioCompleto(int $id): void {
        $this->requireAuth();
        $this->requireLevel(4, [...self::NODO_USUARIOS, 4]);
        $this->verifyCsrf();

        $db = $this->db();
        $usuarioExiste = $db->fetch($db->query('SELECT 1 FROM CORE_Usuarios WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$usuarioExiste) {
            if (View::isAjax()) { $this->json(['ok' => false, 'msg' => 'Usuario no encontrado.'], 404); }
            http_response_code(404); exit;
        }

        $antesDatos = $db->fetch($db->query(
            'SELECT nombre_completo, correo, nivel_jerarquia, id_departamento, estado FROM CORE_Usuarios WHERE id_usuario=?',
            [[$id, SQLSRV_PARAM_IN]]
        ));
        $antesOverrides = $db->fetchAll($db->query(
            'SELECT id_modulo, opcion, items, subitems, nivel_crud FROM CORE_Permisos_Nodo_Usuario WHERE id_usuario=?',
            [[$id, SQLSRV_PARAM_IN]]
        ));

        $db->beginTransaction();
        try {
            $db->query(
                'UPDATE CORE_Usuarios SET nombre_completo=?, correo=?, nivel_jerarquia=?, id_departamento=?, estado=? WHERE id_usuario=?',
                [
                    [trim($_POST['nombre_completo'] ?? ''), SQLSRV_PARAM_IN],
                    [trim($_POST['correo'] ?? ''),          SQLSRV_PARAM_IN],
                    [(int)($_POST['nivel_jerarquia'] ?? 0), SQLSRV_PARAM_IN],
                    [(int)($_POST['id_departamento'] ?? 0), SQLSRV_PARAM_IN],
                    [(int)($_POST['estado'] ?? 1),          SQLSRV_PARAM_IN],
                    [$id, SQLSRV_PARAM_IN],
                ]
            );

            $db->query('DELETE FROM CORE_Usuarios_Roles WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]);
            $rolesNuevos = [];
            foreach (array_map('intval', $_POST['roles'] ?? []) as $rolId) {
                if ($rolId > 0) {
                    $db->query('INSERT INTO CORE_Usuarios_Roles (id_usuario, id_rol) VALUES (?,?)', [[$id, SQLSRV_PARAM_IN], [$rolId, SQLSRV_PARAM_IN]]);
                    $rolesNuevos[] = $rolId;
                }
            }

            $db->query('DELETE FROM CORE_Permisos_Nodo_Usuario WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]);
            $overridesNuevos = [];
            foreach ($_POST['overrides'] ?? [] as $key => $nivel) {
                $nivel = (int)$nivel;
                if ($nivel < 0 || $nivel > 4) continue;
                $parts = explode('-', (string)$key);
                if (count($parts) !== 4) continue;
                [$mod, $op, $it, $sub] = array_map('intval', $parts);
                $db->query(
                    'INSERT INTO CORE_Permisos_Nodo_Usuario (id_usuario, id_modulo, opcion, items, subitems, nivel_crud, estado, asignado_por)
                     VALUES (?,?,?,?,?,?,1,?)',
                    [
                        [$id, SQLSRV_PARAM_IN], [$mod, SQLSRV_PARAM_IN], [$op, SQLSRV_PARAM_IN],
                        [$it, SQLSRV_PARAM_IN], [$sub, SQLSRV_PARAM_IN], [$nivel, SQLSRV_PARAM_IN],
                        [(int)($_SESSION['user_id'] ?? 0), SQLSRV_PARAM_IN],
                    ]
                );
                $overridesNuevos[(string)$key] = $nivel;
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollback();
            if (View::isAjax()) { $this->json(['ok' => false, 'msg' => 'No se pudo guardar: ' . $e->getMessage()], 500); }
            SessionHelper::flash('error', 'No se pudo guardar el usuario.');
            $this->redirect('/admin/usuarios/' . $id . '/editar');
        }

        ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Usuarios', (string)$id,
            $antesDatos ? [
                'nombre_completo' => $antesDatos['nombre_completo'], 'correo' => $antesDatos['correo'],
                'nivel_jerarquia' => (int)$antesDatos['nivel_jerarquia'], 'id_departamento' => $antesDatos['id_departamento'],
                'estado' => (int)$antesDatos['estado'],
            ] : null,
            [
                'nombre_completo' => trim($_POST['nombre_completo'] ?? ''), 'correo' => trim($_POST['correo'] ?? ''),
                'nivel_jerarquia' => (int)($_POST['nivel_jerarquia'] ?? 0), 'id_departamento' => (int)($_POST['id_departamento'] ?? 0),
                'estado' => (int)($_POST['estado'] ?? 1), 'roles' => $rolesNuevos,
            ],
            'EXITO', 'Guardado unico (datos + roles + permisos individuales)'
        );
        $antesOverridesMap = [];
        foreach ($antesOverrides as $r) { $antesOverridesMap["{$r['id_modulo']}-{$r['opcion']}-{$r['items']}-{$r['subitems']}"] = (int)$r['nivel_crud']; }
        ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Permisos_Nodo_Usuario', (string)$id,
            $antesOverridesMap ?: null, $overridesNuevos ?: null, 'EXITO', 'Override de permisos por usuario individual');

        if (View::isAjax()) {
            $this->json(['ok' => true, 'msg' => 'Usuario, roles y permisos individuales guardados.']);
        }
        SessionHelper::flash('success', 'Usuario actualizado correctamente.');
        $this->redirect('/admin/usuarios');
    }

    public function eliminarUsuario(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_USUARIOS, 4]);
        $this->verifyCsrf();

        $db    = $this->db();
        $antes = $db->fetch($db->query('SELECT nombre_completo, estado FROM CORE_Usuarios WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]));
        $db->query('UPDATE CORE_Usuarios SET estado=0 WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]);

        ModuleSecurity::audit('CORE', 'DESACTIVAR', 'CORE_Usuarios', (string)$id,
            $antes ? ['estado' => (int)$antes['estado']] : null,
            ['estado' => 0],
            'EXITO', $antes['nombre_completo'] ?? null
        );

        SessionHelper::flash('success', 'Usuario desactivado.');
        $this->redirect('/admin/usuarios');
    }

    public function activarUsuario(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_USUARIOS, 4]);
        $this->verifyCsrf();

        $db    = $this->db();
        $antes = $db->fetch($db->query('SELECT nombre_completo, estado FROM CORE_Usuarios WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]));
        $db->query('UPDATE CORE_Usuarios SET estado=1 WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]);

        ModuleSecurity::audit('CORE', 'ACTIVAR', 'CORE_Usuarios', (string)$id,
            $antes ? ['estado' => (int)$antes['estado']] : null,
            ['estado' => 1],
            'EXITO', $antes['nombre_completo'] ?? null
        );

        SessionHelper::flash('success', 'Usuario activado.');
        $this->redirect('/admin/usuarios');
    }

    // ─── Roles ────────────────────────────────────────────────────────────────

    public function roles(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 1]);

        $db    = $this->db();
        $roles = $db->fetchAll($db->query(
            'SELECT r.id_rol, r.codigo, r.nombre, r.descripcion, r.nivel_jerarquia, r.estado,
                    d.nombre AS departamento
             FROM CORE_Roles r
             LEFT JOIN CORE_Departamentos d ON d.id_departamento = r.id_departamento
             ORDER BY r.nombre'
        ));

        $this->render('Central/admin/roles', [
            'pageTitle' => 'Gestión de Roles',
            'roles'     => $roles,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** GET /admin/departamentos — listado, sincronizado desde Talento Humano. */
    public function departamentos(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 1]);

        $db = $this->db();
        $deptos = $db->fetchAll($db->query(
            'SELECT id_departamento, codigo, nombre, descripcion, id_padre, nivel, estado,
                    icono, color_badge, codigo_uorg_th, origen_th
             FROM CORE_Departamentos
             ORDER BY nivel, nombre'
        ));

        $this->render('Central/admin/departamentos', [
            'pageTitle' => 'Departamentos',
            'deptos'    => $deptos,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /** GET /admin/departamentos/{id}/editar */
    public function editarDepartamento(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 1]);

        $db     = $this->db();
        $depto  = $db->fetch($db->query('SELECT * FROM CORE_Departamentos WHERE id_departamento=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$depto) { http_response_code(404); exit; }

        $this->render('Central/admin/departamento_form', [
            'pageTitle' => 'Editar Departamento',
            'depto'     => $depto,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /**
     * POST /admin/departamentos/{id} — solo campos propios del portal
     * (icono, color, descripción). El nombre/estructura vienen de Talento
     * Humano cuando el departamento está sincronizado (origen_th=1) — un
     * cambio manual de nombre ahí se sobrescribe en la próxima sincronización,
     * así que no se ofrece editar nombre para esos. Los departamentos sin
     * vínculo a TH (origen_th=0) sí permiten nombre libre, como siempre.
     */
    public function actualizarDepartamento(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 3]);
        $this->verifyCsrf();

        $db    = $this->db();
        $depto = $db->fetch($db->query('SELECT * FROM CORE_Departamentos WHERE id_departamento=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$depto) { http_response_code(404); exit; }

        $icono      = trim($_POST['icono'] ?? '');
        $colorBadge = trim($_POST['color_badge'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $estado     = (int)($_POST['estado'] ?? 1);

        if ((int)$depto['origen_th'] === 1) {
            // Sincronizado desde TH: nombre no se toca acá.
            $db->query(
                'UPDATE CORE_Departamentos SET icono=?, color_badge=?, descripcion=?, estado=? WHERE id_departamento=?',
                [
                    [$icono ?: null,      SQLSRV_PARAM_IN],
                    [$colorBadge ?: null, SQLSRV_PARAM_IN],
                    [$descripcion,        SQLSRV_PARAM_IN],
                    [$estado,             SQLSRV_PARAM_IN],
                    [$id,                 SQLSRV_PARAM_IN],
                ]
            );
        } else {
            $nombre = trim($_POST['nombre'] ?? '');
            if ($nombre === '') {
                $_SESSION['_form_errors'] = ['nombre' => 'El nombre es obligatorio.'];
                $this->redirect('/admin/departamentos/' . $id . '/editar');
            }
            $db->query(
                'UPDATE CORE_Departamentos SET nombre=?, icono=?, color_badge=?, descripcion=?, estado=? WHERE id_departamento=?',
                [
                    [$nombre,             SQLSRV_PARAM_IN],
                    [$icono ?: null,      SQLSRV_PARAM_IN],
                    [$colorBadge ?: null, SQLSRV_PARAM_IN],
                    [$descripcion,        SQLSRV_PARAM_IN],
                    [$estado,             SQLSRV_PARAM_IN],
                    [$id,                 SQLSRV_PARAM_IN],
                ]
            );
        }

        ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Departamentos', (string)$id,
            [
                'nombre' => $depto['nombre'], 'icono' => $depto['icono'],
                'color_badge' => $depto['color_badge'], 'descripcion' => $depto['descripcion'], 'estado' => (int)$depto['estado'],
            ],
            [
                'nombre' => (int)$depto['origen_th'] === 1 ? $depto['nombre'] : trim($_POST['nombre'] ?? ''),
                'icono' => $icono ?: null, 'color_badge' => $colorBadge ?: null,
                'descripcion' => $descripcion, 'estado' => $estado,
            ]
        );

        SessionHelper::flash('success', 'Departamento actualizado correctamente.');
        $this->redirect('/admin/departamentos');
    }

    public function nuevoRol(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 1]);

        $this->render('Central/admin/rol_form', [
            'pageTitle' => 'Nuevo Rol',
            'rol'       => null,
            'deptos'    => $this->deptos(),
            'puestos'   => $this->puestosTh(),
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function crearRol(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 2]);
        $this->verifyCsrf();

        if (!FormHelper::validate($_POST, [
            'codigo'          => 'required|min:2|max:30',
            'nombre'          => 'required|min:3|max:100',
            'nivel_jerarquia' => 'required|numeric',
        ])) {
            $_SESSION['_form_errors'] = FormHelper::errors();
            $_SESSION['_old_input']   = $_POST;
            $this->redirect('/admin/roles/nuevo');
        }

        // El nombre del rol se elige del catálogo real de cargos de Talento
        // Humano — no texto libre. Verificar que coincide con uno existente.
        $nombreRol = trim($_POST['nombre']);
        $existePuesto = $this->db()->fetch($this->db()->query(
            'SELECT TOP 1 1 FROM Talento_Humano.dbo.th_puestos WHERE nombre_puesto = ? AND activo = 1',
            [[$nombreRol, SQLSRV_PARAM_IN]]
        ));
        if (!$existePuesto) {
            $_SESSION['_form_errors'] = ['nombre' => 'El nombre debe ser un cargo real y activo del catálogo de Talento Humano.'];
            $_SESSION['_old_input']   = $_POST;
            $this->redirect('/admin/roles/nuevo');
        }

        $deptoId = !empty($_POST['id_departamento']) ? (int)$_POST['id_departamento'] : null;
        $stmt = $this->db()->query(
            'INSERT INTO CORE_Roles (codigo, nombre, descripcion, id_departamento, nivel_jerarquia, estado)
             OUTPUT INSERTED.id_rol
             VALUES (?,?,?,?,?,1)',
            [
                [strtoupper(trim($_POST['codigo'])),   SQLSRV_PARAM_IN],
                [trim($_POST['nombre']),               SQLSRV_PARAM_IN],
                [trim($_POST['descripcion'] ?? ''),    SQLSRV_PARAM_IN],
                [$deptoId,                             SQLSRV_PARAM_IN],
                [(int)$_POST['nivel_jerarquia'],       SQLSRV_PARAM_IN],
            ]
        );
        $idRol = (int)$this->db()->fetch($stmt)['id_rol'];

        ModuleSecurity::audit('CORE', 'CREAR', 'CORE_Roles', (string)$idRol, null, [
            'codigo' => strtoupper(trim($_POST['codigo'])), 'nombre' => trim($_POST['nombre']),
            'descripcion' => trim($_POST['descripcion'] ?? ''), 'id_departamento' => $deptoId,
            'nivel_jerarquia' => (int)$_POST['nivel_jerarquia'],
        ]);

        SessionHelper::flash('success', 'Rol creado correctamente.');
        $this->redirect('/admin/roles');
    }

    public function editarRol(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 1]);

        $db  = $this->db();
        $rol = $db->fetch($db->query('SELECT * FROM CORE_Roles WHERE id_rol=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$rol) { http_response_code(404); exit; }

        $this->render('Central/admin/rol_form', [
            'pageTitle' => 'Editar Rol',
            'rol'       => $rol,
            'deptos'    => $this->deptos(),
            'puestos'   => $this->puestosTh(),
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function actualizarRol(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 3]);
        $this->verifyCsrf();

        $nombreRol = trim($_POST['nombre'] ?? '');
        $existePuesto = $this->db()->fetch($this->db()->query(
            'SELECT TOP 1 1 FROM Talento_Humano.dbo.th_puestos WHERE nombre_puesto = ? AND activo = 1',
            [[$nombreRol, SQLSRV_PARAM_IN]]
        ));
        if (!$existePuesto) {
            $_SESSION['_form_errors'] = ['nombre' => 'El nombre debe ser un cargo real y activo del catálogo de Talento Humano.'];
            $_SESSION['_old_input']   = $_POST;
            $this->redirect('/admin/roles/' . $id . '/editar');
        }

        $db    = $this->db();
        $antes = $db->fetch($db->query('SELECT nombre, descripcion, id_departamento, nivel_jerarquia, estado FROM CORE_Roles WHERE id_rol=?', [[$id, SQLSRV_PARAM_IN]]));

        $deptoId = !empty($_POST['id_departamento']) ? (int)$_POST['id_departamento'] : null;
        $db->query(
            'UPDATE CORE_Roles SET nombre=?, descripcion=?, id_departamento=?, nivel_jerarquia=?, estado=? WHERE id_rol=?',
            [
                [trim($_POST['nombre']),             SQLSRV_PARAM_IN],
                [trim($_POST['descripcion'] ?? ''),  SQLSRV_PARAM_IN],
                [$deptoId,                           SQLSRV_PARAM_IN],
                [(int)$_POST['nivel_jerarquia'],     SQLSRV_PARAM_IN],
                [(int)($_POST['estado'] ?? 1),       SQLSRV_PARAM_IN],
                [$id,                                SQLSRV_PARAM_IN],
            ]
        );

        ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Roles', (string)$id,
            $antes ? [
                'nombre' => $antes['nombre'], 'descripcion' => $antes['descripcion'],
                'id_departamento' => $antes['id_departamento'], 'nivel_jerarquia' => (int)$antes['nivel_jerarquia'],
                'estado' => (int)$antes['estado'],
            ] : null,
            [
                'nombre' => trim($_POST['nombre']), 'descripcion' => trim($_POST['descripcion'] ?? ''),
                'id_departamento' => $deptoId, 'nivel_jerarquia' => (int)$_POST['nivel_jerarquia'],
                'estado' => (int)($_POST['estado'] ?? 1),
            ]
        );

        SessionHelper::flash('success', 'Rol actualizado correctamente.');
        $this->redirect('/admin/roles');
    }

    public function eliminarRol(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 4]);
        $this->verifyCsrf();

        $db    = $this->db();
        $antes = $db->fetch($db->query('SELECT nombre, estado FROM CORE_Roles WHERE id_rol=?', [[$id, SQLSRV_PARAM_IN]]));
        $db->query('UPDATE CORE_Roles SET estado=0 WHERE id_rol=?', [[$id, SQLSRV_PARAM_IN]]);

        ModuleSecurity::audit('CORE', 'DESACTIVAR', 'CORE_Roles', (string)$id,
            $antes ? ['estado' => (int)$antes['estado']] : null,
            ['estado' => 0],
            'EXITO', $antes['nombre'] ?? null
        );

        SessionHelper::flash('success', 'Rol desactivado.');
        $this->redirect('/admin/roles');
    }

    public function activarRol(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 4]);
        $this->verifyCsrf();

        $db    = $this->db();
        $antes = $db->fetch($db->query('SELECT nombre, estado FROM CORE_Roles WHERE id_rol=?', [[$id, SQLSRV_PARAM_IN]]));
        $db->query('UPDATE CORE_Roles SET estado=1 WHERE id_rol=?', [[$id, SQLSRV_PARAM_IN]]);

        ModuleSecurity::audit('CORE', 'ACTIVAR', 'CORE_Roles', (string)$id,
            $antes ? ['estado' => (int)$antes['estado']] : null,
            ['estado' => 1],
            'EXITO', $antes['nombre'] ?? null
        );

        SessionHelper::flash('success', 'Rol activado.');
        $this->redirect('/admin/roles');
    }

    // ─── Permisos ─────────────────────────────────────────────────────────────

    public function rolPermisos(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 1]);

        $db  = $this->db();
        $rol = $db->fetch($db->query(
            'SELECT r.*, d.nombre AS departamento
             FROM CORE_Roles r LEFT JOIN CORE_Departamentos d ON d.id_departamento = r.id_departamento
             WHERE r.id_rol=?',
            [[$id, SQLSRV_PARAM_IN]]
        ));
        if (!$rol) { http_response_code(404); exit; }

        $usuariosConRol = (int)($db->fetch($db->query(
            'SELECT COUNT(*) AS n FROM CORE_Usuarios_Roles WHERE id_rol=? AND estado=1',
            [[$id, SQLSRV_PARAM_IN]]
        ))['n'] ?? 0);

        $nodos = $db->fetchAll($db->query(
            'SELECT id_nodo, id_modulo, opcion, items, subitems, descripcion, url_ruta, icono
             FROM CORE_Menu_Nodos WHERE estado=1
             ORDER BY id_modulo, opcion, items, subitems'
        ));

        $permisosBD = $db->fetchAll($db->query(
            'SELECT id_modulo, opcion, items, subitems, nivel_crud
             FROM CORE_Permisos_Nodo WHERE id_rol=? AND estado=1',
            [[$id, SQLSRV_PARAM_IN]]
        ));

        $permisosMap = [];
        foreach ($permisosBD as $p) {
            $permisosMap["{$p['id_modulo']}-{$p['opcion']}-{$p['items']}-{$p['subitems']}"] = (int)$p['nivel_crud'];
        }

        $tree = $this->construirArbolPermisos($nodos, $permisosMap);

        $this->render('Central/admin/rol_permisos', [
            'pageTitle'      => 'Permisos: ' . htmlspecialchars($rol['nombre'], ENT_QUOTES),
            'rol'            => $rol,
            'usuariosConRol' => $usuariosConRol,
            'tree'           => $tree,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    /**
     * Arma el árbol Módulo>Opción>Ítem>Sub-ítem para la tabla-checklist de
     * permisos, marcando el nivel_crud vigente en cada nodo según
     * $permisosMap (clave "mod-op-it-sub" => nivel_crud). Reutilizado por
     * rolPermisos() (permiso por ROL) y editarUsuario() (override por
     * USUARIO individual, Fase 0 del sistema central de permisos) — misma
     * forma de árbol, distinta fuente de $permisosMap.
     */
    private function construirArbolPermisos(array $nodos, array $permisosMap): array {
        // Mismo ícono/color que CatalogoModulos — para que Estructura del
        // Menú y Roles y Permisos "hablen el mismo idioma" visual.
        $moduleMeta = CatalogoModulos::todos();

        $tree = [];
        foreach ($nodos as $n) {
            $mod = (int)$n['id_modulo'];
            $op  = (int)$n['opcion'];
            $it  = (int)$n['items'];
            $sub = (int)$n['subitems'];
            $key = "{$mod}-{$op}-{$it}-{$sub}";
            $n['key']     = $key;
            $n['permiso'] = $permisosMap[$key] ?? 0;

            if (!isset($tree[$mod])) {
                $meta = $moduleMeta[$mod] ?? ['label' => "Módulo $mod", 'icon' => 'fa-folder', 'color' => '#6c757d'];
                $tree[$mod] = ['label' => $meta['label'], 'icon' => $meta['icon'], 'color' => $meta['color'], 'raiz' => null, 'areas' => []];
            }
            if ($op === 0) {
                $tree[$mod]['raiz'] = $n;
            } elseif ($it === 0) {
                $tree[$mod]['areas'][$op] = ['nodo' => $n, 'items' => []];
            } elseif ($sub === 0) {
                if (!isset($tree[$mod]['areas'][$op])) {
                    $tree[$mod]['areas'][$op] = ['nodo' => null, 'items' => []];
                }
                $tree[$mod]['areas'][$op]['items'][$it] = ['nodo' => $n, 'subitems' => []];
            } else {
                if (!isset($tree[$mod]['areas'][$op])) {
                    $tree[$mod]['areas'][$op] = ['nodo' => null, 'items' => []];
                }
                if (!isset($tree[$mod]['areas'][$op]['items'][$it])) {
                    $tree[$mod]['areas'][$op]['items'][$it] = ['nodo' => null, 'subitems' => []];
                }
                $tree[$mod]['areas'][$op]['items'][$it]['subitems'][] = $n;
            }
        }
        return $tree;
    }

    public function guardarPermisos(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 4]);
        $this->verifyCsrf();

        $db = $this->db();

        $antesRows = $db->fetchAll($db->query(
            'SELECT id_modulo, opcion, items, subitems, nivel_crud FROM CORE_Permisos_Nodo WHERE id_rol=?',
            [[$id, SQLSRV_PARAM_IN]]
        ));
        $antesMap = [];
        foreach ($antesRows as $r) {
            $antesMap["{$r['id_modulo']}-{$r['opcion']}-{$r['items']}-{$r['subitems']}"] = (int)$r['nivel_crud'];
        }

        $db->query('DELETE FROM CORE_Permisos_Nodo WHERE id_rol=?', [[$id, SQLSRV_PARAM_IN]]);

        $despuesMap = [];
        foreach ($_POST['permisos'] ?? [] as $key => $nivel) {
            $nivel = (int)$nivel;
            if ($nivel < 1 || $nivel > 4) continue;
            $parts = explode('-', (string)$key);
            if (count($parts) !== 4) continue;
            [$mod, $op, $it, $sub] = array_map('intval', $parts);
            $db->query(
                'INSERT INTO CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, estado)
                 VALUES (?,?,?,?,?,?,1)',
                [
                    [$id,    SQLSRV_PARAM_IN],
                    [$mod,   SQLSRV_PARAM_IN],
                    [$op,    SQLSRV_PARAM_IN],
                    [$it,    SQLSRV_PARAM_IN],
                    [$sub,   SQLSRV_PARAM_IN],
                    [$nivel, SQLSRV_PARAM_IN],
                ]
            );
            $despuesMap[(string)$key] = $nivel;
        }

        ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Permisos_Nodo', (string)$id,
            $antesMap ?: null, $despuesMap ?: null,
            'EXITO', 'nivel_crud por nodo MOIS (id_modulo-opcion-items-subitems)'
        );

        // Sync bidireccional Fase 1/2: si este rol tiene mapeo en un módulo
        // con RBAC propio (TH, Bienes), refleja el cambio allá también. No
        // bloquea el guardado si el módulo destino no está disponible --
        // ver SyncPermisosModulo::registrarFalloSync().
        SyncPermisosModulo::centralHaciaTh($id, $despuesMap);
        SyncPermisosModulo::centralHaciaBienes($id, $despuesMap);

        if (View::isAjax()) {
            $this->json(['ok' => true, 'msg' => 'Permisos guardados correctamente.']);
        }

        SessionHelper::flash('success', 'Permisos guardados correctamente.');
        $this->redirect('/admin/roles/' . $id . '/permisos');
    }

    // moduleMeta() se retiró: ver CatalogoModulos::todos() (tabla CORE_Modulos,
    // Fase 0 del sistema central de permisos) — misma fuente que MenuController.

    /**
     * GET /admin/roles/matriz — resumen visual de qué rol tiene acceso a qué
     * módulo y con qué nivel, sin tener que abrir cada rol uno por uno.
     * Solo incluye módulos que realmente tienen nodos de menú construidos
     * (hoy: Central/Portal APM, Talento Humano, Control de Bienes, Bitácoras
     * — los demás id_modulo de moduleMeta() son slots organizacionales
     * reservados sin contenido todavía).
     */
    public function permisosMatriz(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 1]);

        $db = $this->db();

        $roles = $db->fetchAll($db->query(
            'SELECT r.id_rol, r.codigo, r.nombre, r.estado, d.nombre AS departamento
             FROM CORE_Roles r LEFT JOIN CORE_Departamentos d ON d.id_departamento = r.id_departamento
             ORDER BY r.estado DESC, r.nombre'
        ));

        // opcion>0 excluye el nodo raíz del módulo (solo contenedor visual,
        // no un permiso navegable en sí) — se cuentan los ítems reales.
        $nodos = $db->fetchAll($db->query(
            'SELECT id_nodo, id_modulo, opcion, items, subitems, descripcion
             FROM CORE_Menu_Nodos WHERE estado=1 AND opcion > 0
             ORDER BY id_modulo, opcion, items, subitems'
        ));
        $nodosPorModulo = [];
        foreach ($nodos as $n) {
            $nodosPorModulo[(int)$n['id_modulo']][] = $n;
        }

        $permisos = $db->fetchAll($db->query(
            'SELECT id_rol, id_modulo, opcion, items, subitems, nivel_crud
             FROM CORE_Permisos_Nodo WHERE estado=1 AND opcion > 0'
        ));
        $permisosPorRolModulo = [];
        foreach ($permisos as $p) {
            $permisosPorRolModulo[(int)$p['id_rol']][(int)$p['id_modulo']][] = $p;
        }

        $modulosActivos = array_keys($nodosPorModulo);
        sort($modulosActivos);

        $usuariosPorRol = [];
        foreach ($db->fetchAll($db->query(
            'SELECT id_rol, COUNT(*) AS n FROM CORE_Usuarios_Roles WHERE estado=1 GROUP BY id_rol'
        )) as $u) {
            $usuariosPorRol[(int)$u['id_rol']] = (int)$u['n'];
        }

        $matriz = [];
        foreach ($roles as $r) {
            $idRol = (int)$r['id_rol'];
            $celdas = [];
            foreach ($modulosActivos as $idMod) {
                $nodosDelModulo   = $nodosPorModulo[$idMod] ?? [];
                $permisosRolMod   = $permisosPorRolModulo[$idRol][$idMod] ?? [];
                $permisoPorNodo   = [];
                $nivelMax         = 0;
                foreach ($permisosRolMod as $p) {
                    $permisoPorNodo["{$p['opcion']}-{$p['items']}-{$p['subitems']}"] = (int)$p['nivel_crud'];
                    $nivelMax = max($nivelMax, (int)$p['nivel_crud']);
                }
                $detalle = [];
                $conAcceso = 0;
                foreach ($nodosDelModulo as $n) {
                    $key   = "{$n['opcion']}-{$n['items']}-{$n['subitems']}";
                    $nivel = $permisoPorNodo[$key] ?? 0;
                    if ($nivel > 0) $conAcceso++;
                    $detalle[] = ['nombre' => $n['descripcion'], 'nivel' => $nivel];
                }
                $celdas[$idMod] = [
                    'con_acceso' => $conAcceso,
                    'total'      => count($nodosDelModulo),
                    'nivel_max'  => $nivelMax,
                    'detalle'    => $detalle,
                ];
            }
            $matriz[] = ['rol' => $r, 'usuarios' => $usuariosPorRol[$idRol] ?? 0, 'celdas' => $celdas];
        }

        $sinPermisos = array_values(array_filter($matriz, function ($fila) {
            foreach ($fila['celdas'] as $c) { if ($c['con_acceso'] > 0) return false; }
            return true;
        }));

        $this->render('Central/admin/roles_matriz', [
            'pageTitle'    => 'Matriz de Permisos',
            'matriz'       => $matriz,
            'modulos'      => $modulosActivos,
            'moduleMeta'   => CatalogoModulos::todos(),
            'nivelLabels'  => [1 => 'Ver', 2 => 'Crear', 3 => 'Editar', 4 => 'Total'],
            'sinPermisos'  => count($sinPermisos),
            'totalRoles'   => count($roles),
        ]);
    }

    // ─── Auditoría ────────────────────────────────────────────────────────────

    private const AUDIT_PER_PAGE = 25;

    public function auditoria(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_AUDITORIA, 1]);

        $db   = $this->db();
        [$where, $params, $f] = $this->auditoriaFiltros();

        $page   = max(1, (int)($_GET['pagina'] ?? 1));
        $offset = ($page - 1) * self::AUDIT_PER_PAGE;

        $total = (int)($db->fetch($db->query("SELECT COUNT(*) AS n FROM vw_AuditoriaGlobal{$where}", $params))['n'] ?? 0);
        $sum   = $db->fetch($db->query(
            "SELECT SUM(CASE WHEN resultado='EXITO' THEN 1 ELSE 0 END) AS ok,
                    SUM(CASE WHEN resultado<>'EXITO' THEN 1 ELSE 0 END) AS err
             FROM vw_AuditoriaGlobal{$where}", $params));

        $rows = $db->fetchAll($db->query(
            "SELECT * FROM vw_AuditoriaGlobal{$where} ORDER BY fecha_registro DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            array_merge($params, [[$offset, SQLSRV_PARAM_IN], [self::AUDIT_PER_PAGE, SQLSRV_PARAM_IN]])
        ));

        $this->render('Central/admin/auditoria', [
            'pageTitle'   => 'Auditoría del Sistema',
            'registros'   => $rows,
            'page'        => $page,
            'total'       => $total,
            'perPage'     => self::AUDIT_PER_PAGE,
            'totalPages'  => max(1, (int)ceil($total / self::AUDIT_PER_PAGE)),
            'exitos'      => (int)($sum['ok'] ?? 0),
            'errores'     => (int)($sum['err'] ?? 0),
            'filtros'     => $f,
            'modulos'     => array_column($db->fetchAll($db->query("SELECT DISTINCT modulo FROM vw_AuditoriaGlobal WHERE modulo IS NOT NULL ORDER BY modulo")), 'modulo'),
            'operaciones' => array_column($db->fetchAll($db->query("SELECT DISTINCT operacion FROM vw_AuditoriaGlobal WHERE operacion IS NOT NULL ORDER BY operacion")), 'operacion'),
            'resultados'  => array_column($db->fetchAll($db->query("SELECT DISTINCT resultado FROM vw_AuditoriaGlobal WHERE resultado IS NOT NULL ORDER BY resultado")), 'resultado'),
        ]);
    }

    /** Construye WHERE + params desde los filtros GET (compartido por vista y export). */
    private function auditoriaFiltros(): array {
        $f = [
            'q'         => trim($_GET['q']         ?? ''),
            'modulo'    => trim($_GET['modulo']    ?? ''),
            'operacion' => trim($_GET['operacion'] ?? ''),
            'resultado' => trim($_GET['resultado'] ?? ''),
            'desde'     => trim($_GET['desde']     ?? ''),
            'hasta'     => trim($_GET['hasta']     ?? ''),
        ];
        $cond = []; $params = [];
        if ($f['q'] !== '') {
            $like = '%' . $f['q'] . '%';
            $cond[] = '(nombre_usuario LIKE ? OR detalle LIKE ? OR ip_address LIKE ? OR tabla_afectada LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }
        if ($f['modulo']    !== '') { $cond[] = 'modulo = ?';    $params[] = $f['modulo']; }
        if ($f['operacion'] !== '') { $cond[] = 'operacion = ?'; $params[] = $f['operacion']; }
        if ($f['resultado'] !== '') { $cond[] = 'resultado = ?'; $params[] = $f['resultado']; }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['desde'])) { $cond[] = 'fecha_registro >= ?'; $params[] = $f['desde']; }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['hasta'])) { $cond[] = 'fecha_registro < DATEADD(DAY,1,?)'; $params[] = $f['hasta']; }

        $where = $cond ? (' WHERE ' . implode(' AND ', $cond)) : '';
        return [$where, $params, $f];
    }

    /** Todas las filas que cumplen el filtro (para export). Límite de seguridad. */
    private function auditoriaTodas(int $max = 10000): array {
        [$where, $params] = $this->auditoriaFiltros();
        $db = $this->db();
        return $db->fetchAll($db->query(
            "SELECT TOP {$max} * FROM vw_AuditoriaGlobal{$where} ORDER BY fecha_registro DESC", $params));
    }

    private function auditoriaFechaTxt($v): string {
        if ($v instanceof DateTime) return $v->format('d/m/Y H:i:s');
        if (is_string($v) && $v !== '') return date('d/m/Y H:i:s', strtotime($v));
        return '';
    }

    /** GET /admin/auditoria/export/excel — .xlsx nativo con los filtros aplicados. */
    public function exportarAuditoriaExcel(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_AUDITORIA, 1]);
        require_once ROOT . '/libs/XlsxWriter.php';

        $rows = $this->auditoriaTodas();
        $x = new XlsxWriter('Auditoría');
        $x->setColumns([
            ['Fecha', 20], ['Usuario', 26], ['Módulo', 16], ['Operación', 20],
            ['Tabla afectada', 24], ['ID registro', 14], ['IP', 18], ['Resultado', 14], ['Detalle', 60],
        ]);
        foreach ($rows as $r) {
            $x->addRow([
                $this->auditoriaFechaTxt($r['fecha_registro'] ?? null),
                $r['nombre_usuario'] ?? 'Sistema',
                $r['modulo'] ?? '', $r['operacion'] ?? '',
                $r['tabla_afectada'] ?? '', $r['id_registro'] ?? '',
                $r['ip_address'] ?? '', $r['resultado'] ?? '', $r['detalle'] ?? '',
            ]);
        }
        $file = 'auditoria_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Cache-Control: max-age=0');
        echo $x->build();
        exit;
    }

    /** GET /admin/auditoria/export/pdf — PDF apaisado con los filtros aplicados. */
    public function exportarAuditoriaPdf(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_AUDITORIA, 1]);
        require_once ROOT . '/libs/fpdf/fpdf.php';

        $rows = $this->auditoriaTodas();
        [, , $f] = $this->auditoriaFiltros();
        $utf = fn($s) => (string)(@iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', (string)($s ?? '')) ?: '');

        // Columnas (mm) en A4 apaisado (usable 277)
        $cols = [
            ['Fecha', 32], ['Usuario', 38], ['Módulo', 20], ['Operación', 26],
            ['Tabla', 30], ['IP', 26], ['Resultado', 22], ['Detalle', 83],
        ];
        $logo = ROOT . '/imgs/logoapm.png';

        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);

        $drawHead = function () use ($pdf, $cols, $utf, $logo, $f) {
            $pdf->AddPage();
            if (file_exists($logo)) { @$pdf->Image($logo, 10, 8, 20); }
            $pdf->SetXY(32, 9); $pdf->SetFont('Arial', 'B', 13);
            $pdf->Cell(0, 6, $utf('AUTORIDAD PORTUARIA DE MANTA'), 0, 1, 'L');
            $pdf->SetX(32); $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(0, 5, $utf('Reporte de Auditoría del Sistema — generado ' . date('d/m/Y H:i')), 0, 1, 'L');
            // Resumen de filtros
            $ff = [];
            if ($f['q'] !== '')         $ff[] = 'texto="' . $f['q'] . '"';
            if ($f['modulo'] !== '')    $ff[] = 'módulo=' . $f['modulo'];
            if ($f['operacion'] !== '') $ff[] = 'operación=' . $f['operacion'];
            if ($f['resultado'] !== '') $ff[] = 'resultado=' . $f['resultado'];
            if ($f['desde'] !== '')     $ff[] = 'desde=' . $f['desde'];
            if ($f['hasta'] !== '')     $ff[] = 'hasta=' . $f['hasta'];
            $pdf->SetX(32); $pdf->SetFont('Arial', 'I', 8); $pdf->SetTextColor(90, 90, 90);
            $pdf->Cell(0, 5, $utf('Filtros: ' . ($ff ? implode(' · ', $ff) : 'ninguno')), 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(10, 26);
            // Encabezado de tabla
            $pdf->SetFont('Arial', 'B', 8); $pdf->SetFillColor(22, 50, 79); $pdf->SetTextColor(255, 255, 255);
            foreach ($cols as $c) { $pdf->Cell($c[1], 7, $utf($c[0]), 1, 0, 'C', true); }
            $pdf->Ln(); $pdf->SetTextColor(0, 0, 0); $pdf->SetFont('Arial', '', 7);
        };

        $fit = function (string $s, float $w) use ($pdf, $utf): string {
            $s = $utf($s);
            if ($pdf->GetStringWidth($s) <= $w - 2) return $s;
            while (strlen($s) > 1 && $pdf->GetStringWidth($s . '...') > $w - 2) { $s = substr($s, 0, -1); }
            return $s . '...';
        };

        $drawHead();
        $fill = false;
        foreach ($rows as $r) {
            if ($pdf->GetY() > 195) { $drawHead(); $fill = false; }
            $vals = [
                $this->auditoriaFechaTxt($r['fecha_registro'] ?? null),
                $r['nombre_usuario'] ?? 'Sistema',
                $r['modulo'] ?? '', $r['operacion'] ?? '',
                $r['tabla_afectada'] ?? '', $r['ip_address'] ?? '',
                $r['resultado'] ?? '', $r['detalle'] ?? '',
            ];
            $pdf->SetFillColor(244, 247, 250);
            $esErr = ($r['resultado'] ?? '') !== 'EXITO';
            foreach ($cols as $i => $c) {
                if ($i === 6 && $esErr) { $pdf->SetTextColor(180, 0, 0); }
                $pdf->Cell($c[1], 6, $fit((string)$vals[$i], $c[1]), 1, 0, ($i === 6 ? 'C' : 'L'), $fill);
                if ($i === 6 && $esErr) { $pdf->SetTextColor(0, 0, 0); }
            }
            $pdf->Ln(); $fill = !$fill;
        }
        if (empty($rows)) { $pdf->Cell(277, 8, $utf('Sin registros para los filtros seleccionados.'), 1, 1, 'C'); }

        // Pie: total
        $pdf->SetY(200); $pdf->SetFont('Arial', 'I', 7); $pdf->SetTextColor(90, 90, 90);
        $pdf->Cell(0, 5, $utf('Total de registros exportados: ' . count($rows)), 0, 0, 'R');

        $pdf->Output('I', 'auditoria_' . date('Ymd_His') . '.pdf');
        exit;
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    private function deptos(): array {
        $db = $this->db();
        return $db->fetchAll($db->query(
            'SELECT id_departamento, nombre AS nombre FROM CORE_Departamentos WHERE estado=1 ORDER BY nombre'
        ));
    }

    /** Catálogo de cargos reales de Talento Humano — fuente del nombre de un rol nuevo. */
    private function puestosTh(): array {
        $db = $this->db();
        return $db->fetchAll($db->query(
            'SELECT nombre_puesto FROM Talento_Humano.dbo.th_puestos WHERE activo = 1 ORDER BY nombre_puesto'
        ));
    }

    // ─── Export de Usuarios (todos + individual) ──────────────────────────────

    /** Datos completos de usuario(s) con departamento y roles concatenados. */
    private function usuariosData(?int $id = null): array {
        $db = $this->db();
        $where  = $id ? ' WHERE u.id_usuario = ?' : '';
        $params = $id ? [[$id, SQLSRV_PARAM_IN]] : [];
        return $db->fetchAll($db->query(
            "SELECT u.id_usuario, u.nombre_usuario, u.nombre_completo, u.correo, u.cedula,
                    u.nivel_jerarquia, u.estado, u.requiere_mfa, u.requiere_cambio_pass,
                    u.intentos_fallidos, u.tema_preferido, u.fecha_creacion, u.fecha_modificacion,
                    d.nombre AS departamento,
                    STUFF((SELECT ', ' + r.nombre FROM CORE_Usuarios_Roles ur
                           JOIN CORE_Roles r ON r.id_rol = ur.id_rol
                           WHERE ur.id_usuario = u.id_usuario AND ur.estado = 1
                           ORDER BY r.nombre FOR XML PATH(''), TYPE).value('.','NVARCHAR(MAX)'),1,2,'') AS roles
             FROM CORE_Usuarios u
             LEFT JOIN CORE_Departamentos d ON d.id_departamento = u.id_departamento
             {$where}
             ORDER BY u.nombre_completo", $params));
    }

    private function nivelLabel($n): string {
        return [0=>'Operativo',1=>'Analista',2=>'Jefatura',3=>'Director/Gerente',4=>'SuperAdmin'][(int)$n] ?? (string)$n;
    }
    private function fmtFechaHora($v): string {
        if ($v instanceof DateTime) return $v->format('d/m/Y H:i');
        if (is_string($v) && $v !== '') return date('d/m/Y H:i', strtotime($v));
        return '';
    }
    private function descargarExcel(XlsxWriter $x, string $base): void {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $base . '_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        echo $x->build();
        exit;
    }

    /** GET /admin/usuarios/export/excel — todos los usuarios. */
    public function exportarUsuariosExcel(): void {
        $this->requireAuth(); $this->requireLevel(3, [...self::NODO_USUARIOS, 1]);
        require_once ROOT . '/libs/XlsxWriter.php';
        $x = new XlsxWriter('Usuarios');
        $x->setColumns([
            ['Usuario',20],['Nombre completo',30],['Cédula',14],['Correo',30],['Departamento',26],
            ['Nivel',18],['Roles',36],['Estado',10],['MFA',6],['Cambio pass',12],['Creado',18],
        ]);
        foreach ($this->usuariosData() as $u) {
            $x->addRow([
                $u['nombre_usuario'] ?? '', $u['nombre_completo'] ?? '', $u['cedula'] ?? '',
                $u['correo'] ?? '', $u['departamento'] ?? '', $this->nivelLabel($u['nivel_jerarquia'] ?? 0),
                $u['roles'] ?? '', ((int)($u['estado'] ?? 0) === 1 ? 'Activo' : 'Inactivo'),
                ((int)($u['requiere_mfa'] ?? 0) ? 'Sí' : 'No'),
                ((int)($u['requiere_cambio_pass'] ?? 0) ? 'Sí' : 'No'),
                $this->fmtFechaHora($u['fecha_creacion'] ?? null),
            ]);
        }
        $this->descargarExcel($x, 'usuarios');
    }

    /** GET /admin/usuarios/export/pdf — todos los usuarios. */
    public function exportarUsuariosPdf(): void {
        $this->requireAuth(); $this->requireLevel(3, [...self::NODO_USUARIOS, 1]);
        require_once ROOT . '/libs/fpdf/fpdf.php';
        require_once ROOT . '/libs/ReportPdf.php';
        $cols = [
            ['Usuario',30],['Nombre completo',48],['Cédula',24],['Correo',48],
            ['Departamento',36],['Nivel',26],['Roles',43],['Estado',22],
        ];
        $rows = [];
        foreach ($this->usuariosData() as $u) {
            $rows[] = [
                $u['nombre_usuario'] ?? '', $u['nombre_completo'] ?? '', $u['cedula'] ?? '',
                $u['correo'] ?? '', $u['departamento'] ?? '', $this->nivelLabel($u['nivel_jerarquia'] ?? 0),
                $u['roles'] ?? '', ((int)($u['estado'] ?? 0) === 1 ? 'Activo' : 'Inactivo'),
            ];
        }
        ReportPdf::tabla('L', 'Listado de Usuarios del Sistema', '', $cols, $rows, 'usuarios_' . date('Ymd') . '.pdf');
    }

    /** GET /admin/usuarios/{id}/export/excel — un usuario (Campo/Valor). */
    public function exportarUsuarioExcel(int $id): void {
        $this->requireAuth(); $this->requireLevel(3, [...self::NODO_USUARIOS, 1]);
        require_once ROOT . '/libs/XlsxWriter.php';
        $u = $this->usuariosData($id)[0] ?? null;
        if (!$u) { http_response_code(404); die('Usuario no encontrado.'); }
        $x = new XlsxWriter('Usuario');
        $x->setColumns([['Campo',30],['Valor',60]]);
        foreach ($this->usuarioCampos($u) as [$k, $v]) { $x->addRow([$k, $v]); }
        $this->descargarExcel($x, 'usuario_' . preg_replace('/\W+/', '', (string)($u['nombre_usuario'] ?? $id)));
    }

    /** GET /admin/usuarios/{id}/export/pdf — un usuario (ficha completa Talento Humano + Portal). */
    public function exportarUsuarioPdf(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_USUARIOS, 1]);
        $u = $this->usuariosData($id)[0] ?? null;
        if (!$u) { http_response_code(404); die('Usuario no encontrado.'); }
        $this->generarFichaUsuarioPdfCompleta($u);
    }

    /** GET /perfil/export/pdf — Ficha completa del usuario logueado en "MI PERFIL". */
    public function exportarMiPerfilPdf(): void {
        $this->requireAuth();
        $id = (int)SessionHelper::userId();
        $u = $this->usuariosData($id)[0] ?? null;
        if (!$u) {
            // Fallback a sesión
            $u = [
                'id_usuario'       => $id,
                'cedula'           => $_SESSION['cedula'] ?? '',
                'nombre_usuario'   => $_SESSION['nombre_usuario'] ?? '',
                'nombre_completo'  => $_SESSION['nombre_completo'] ?? '',
                'correo'           => $_SESSION['correo'] ?? '',
                'departamento'     => $_SESSION['nombre_departamento'] ?? 'General',
                'nivel_jerarquia'  => $_SESSION['nivel_jerarquia'] ?? 0,
                'roles'            => implode(', ', (array)($_SESSION['roles'] ?? [])),
                'estado'           => 1,
                'requiere_mfa'     => !empty($_SESSION['_requiere_mfa']),
                'fecha_creacion'   => null,
            ];
        }
        $this->generarFichaUsuarioPdfCompleta($u);
    }

    private function fmtFecha($v): string {
        if ($v instanceof DateTime) return $v->format('d/m/Y');
        if (is_string($v) && $v !== '') return date('d/m/Y', strtotime($v));
        return '';
    }

    private function generarFichaUsuarioPdfCompleta(array $u): void {
        require_once ROOT . '/libs/fpdf/fpdf.php';
        require_once ROOT . '/libs/ReportPdf.php';

        $db = $this->db();
        $cedula = trim((string)($u['cedula'] ?? ''));
        $empId  = (int)($u['id_empleado_th'] ?? 0);

        $emp = null;
        try {
            $emp = $db->fetch($db->query(
                "SELECT e.*, u.nombre_unidad, p.nombre_puesto 
                 FROM Talento_Humano.dbo.th_empleados e
                 LEFT JOIN Talento_Humano.dbo.th_unidades_organizacionales u ON u.unidad_id = e.unidad_id
                 LEFT JOIN Talento_Humano.dbo.th_puestos p ON p.puesto_id = e.puesto_id
                 WHERE e.identificacion = ? OR (e.empleado_id = ? AND ? > 0)",
                [[$cedula, SQLSRV_PARAM_IN], [$empId, SQLSRV_PARAM_IN], [$empId, SQLSRV_PARAM_IN]]
            ));
        } catch (Throwable $e) {}

        $secciones = [];

        // 1. Identidad y Datos Personales
        $secIdentidad = [
            ['Nombre Completo', $emp ? ($emp['nombres'] . ' ' . $emp['apellidos']) : ($u['nombre_completo'] ?? '')],
            ['Cédula / Identificación', $u['cedula'] ?? ($emp['identificacion'] ?? '—')],
            ['Tipo de Documento', $emp['tipo_identificacion'] ?? 'Cédula de Identidad'],
        ];
        if ($emp) {
            $secIdentidad[] = ['Fecha de Nacimiento', $this->fmtFecha($emp['fecha_nacimiento'] ?? null) ?: '—'];
            $secIdentidad[] = ['Sexo / Género', ($emp['sexo'] ?? '') === 'M' ? 'Masculino' : (($emp['sexo'] ?? '') === 'F' ? 'Femenino' : ($emp['sexo'] ?? '—'))];
            $secIdentidad[] = ['Estado Civil', $emp['estado_civil'] ?? '—'];
            $secIdentidad[] = ['Nacionalidad', $emp['nacionalidad'] ?? 'Ecuatoriana'];
            if (!empty($emp['tipo_sangre'])) $secIdentidad[] = ['Tipo de Sangre', $emp['tipo_sangre']];
        }
        $secciones[] = ['1. Identidad y Datos Personales', $secIdentidad];

        // 2. Ubicación y Contacto
        $secContacto = [
            ['Correo Institucional', $u['correo'] ?? ($emp['correo_institucional'] ?? '—')],
        ];
        if ($emp) {
            $secContacto[] = ['Correo Personal', $emp['correo_personal'] ?? '—'];
            $secContacto[] = ['Teléfono Móvil', $emp['telefono_movil'] ?? '—'];
            $secContacto[] = ['Teléfono Convencional', $emp['telefono_convencional'] ?? '—'];
            $secContacto[] = ['Ciudad de Residencia', $emp['ciudad_residencia'] ?? 'Manta'];
            $secContacto[] = ['Dirección Domiciliaria', $emp['direccion_domiciliaria'] ?? '—'];
            if (!empty($emp['contacto_emergencia'])) {
                $secContacto[] = ['Contacto de Emergencia', $emp['contacto_emergencia'] . (!empty($emp['emergencia_relacion']) ? " ({$emp['emergencia_relacion']})" : '') . (!empty($emp['tel_emergencia']) ? " - {$emp['tel_emergencia']}" : '')];
            }
        }
        $secciones[] = ['2. Ubicación y Contacto Domiciliario', $secContacto];

        // 3. Información Institucional y Régimen Laboral
        $secLaboral = [
            ['Dirección / Unidad', $emp['nombre_unidad'] ?? ($u['departamento'] ?? 'General')],
            ['Puesto / Cargo Oficial', $emp['nombre_puesto'] ?? 'Servidor Público'],
        ];
        if ($emp) {
            $secLaboral[] = ['Fecha de Ingreso APM', $this->fmtFecha($emp['fecha_ingreso'] ?? null) ?: '—'];
            if (!empty($emp['sueldo_rmu'])) {
                $secLaboral[] = ['Remuneración (RMU)', '$' . number_format((float)$emp['sueldo_rmu'], 2) . ' USD'];
            }
            if (!empty($emp['tipo_contrato'])) $secLaboral[] = ['Régimen / Contrato', $emp['tipo_contrato']];
            if (!empty($emp['codigo_iess'])) $secLaboral[] = ['Afiliación IESS', $emp['codigo_iess']];
            if (!empty($emp['grupo_ocupacional'])) $secLaboral[] = ['Grupo Ocupacional', $emp['grupo_ocupacional']];
            if (!empty($emp['partida_individual'])) $secLaboral[] = ['Partida Presupuestaria', $emp['partida_individual']];
        }
        $secciones[] = ['3. Información Institucional y Laboral (Talento Humano)', $secLaboral];

        // 4. Seguridad y Credenciales del Portal Central
        $secPortal = [
            ['Usuario de Acceso', $u['nombre_usuario'] ?? ''],
            ['Nivel Jerárquico en Portal', $this->nivelLabel((int)($u['nivel_jerarquia'] ?? 0))],
            ['Roles Asignados', !empty($u['roles']) ? $u['roles'] : 'Acceso Estándar'],
            ['Estado de Cuenta', (int)($u['estado'] ?? 1) === 1 ? 'Activo / Habilitado' : 'Inactivo'],
            ['Autenticación MFA (2FA)', (int)($u['requiere_mfa'] ?? 0) ? 'Activo (TOTP RFC 6238)' : 'Inactivo'],
            ['Fecha de Registro', $this->fmtFechaHora($u['fecha_creacion'] ?? null)],
        ];
        $secciones[] = ['4. Credenciales y Ciberseguridad (Portal APM)', $secPortal];

        ReportPdf::ficha(
            'FICHA INSTITUCIONAL DE USUARIO',
            ($u['nombre_completo'] ?? '') . ' · ' . ($u['cedula'] ?? ''),
            $secciones,
            'ficha_usuario_' . preg_replace('/\W+/', '', (string)($u['cedula'] ?? ($u['nombre_usuario'] ?? 'apm'))) . '.pdf'
        );
    }

    /** Pares Campo/Valor de un usuario (para Excel individual). */
    private function usuarioCampos(array $u): array {
        return [
            ['Usuario', $u['nombre_usuario'] ?? ''],
            ['Nombre completo', $u['nombre_completo'] ?? ''],
            ['Cédula', $u['cedula'] ?? ''],
            ['Correo', $u['correo'] ?? ''],
            ['Departamento', $u['departamento'] ?? ''],
            ['Nivel jerárquico', $this->nivelLabel($u['nivel_jerarquia'] ?? 0)],
            ['Roles asignados', $u['roles'] ?? ''],
            ['Estado', (int)($u['estado'] ?? 0) === 1 ? 'Activo' : 'Inactivo'],
            ['Requiere MFA', (int)($u['requiere_mfa'] ?? 0) ? 'Sí' : 'No'],
            ['Requiere cambio de contraseña', (int)($u['requiere_cambio_pass'] ?? 0) ? 'Sí' : 'No'],
            ['Intentos fallidos', (string)($u['intentos_fallidos'] ?? 0)],
            ['Tema preferido', $u['tema_preferido'] ?? ''],
            ['Creado', $this->fmtFechaHora($u['fecha_creacion'] ?? null)],
            ['Última modificación', $this->fmtFechaHora($u['fecha_modificacion'] ?? null)],
        ];
    }

    // ─── Cuenta de acceso desde empleado de Talento Humano ─────────────────────
    // La identidad (nombre/cédula) de un usuario ligado a un empleado TH se lee
    // en vivo desde Talento_Humano.dbo.th_empleados (vw_Usuarios_Identidad) —
    // acá solo se crea el vínculo (id_empleado_th) y se sugiere departamento/rol
    // según su unidad organizacional (TH_Unidad_Map).

    private const TH_POR_PAGINA = 20;

    /** GET /admin/usuarios/desde-th — empleados de TH sin cuenta de portal aún (paginado). */
    public function empleadosTh(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_USUARIOS, 2]);

        $buscar = trim((string)($_GET['q'] ?? ''));
        $page   = max(1, (int)($_GET['pagina'] ?? 1));
        $offset = ($page - 1) * self::TH_POR_PAGINA;

        $where  = $buscar !== ''
            ? "AND (e.nombres LIKE ? OR e.apellidos LIKE ? OR e.identificacion LIKE ?)"
            : '';
        $likeParams = $buscar !== ''
            ? (function () use ($buscar) { $l = '%' . $buscar . '%'; return [[$l, SQLSRV_PARAM_IN], [$l, SQLSRV_PARAM_IN], [$l, SQLSRV_PARAM_IN]]; })()
            : [];

        $db = $this->db();

        $total = (int)($db->fetch($db->query(
            "SELECT COUNT(*) AS n
             FROM Talento_Humano.dbo.th_empleados e
             WHERE e.estado = 1
               AND NOT EXISTS (SELECT 1 FROM CORE_Usuarios cu WHERE cu.id_empleado_th = e.empleado_id)
               $where",
            $likeParams
        ))['n'] ?? 0);

        $stmt = $db->query(
            "SELECT e.empleado_id, e.identificacion AS cedula, e.nombres + ' ' + e.apellidos AS nombre_completo,
                    e.correo_institucional, u.codigo_uorg, u.nombre_unidad
             FROM Talento_Humano.dbo.th_empleados e
             LEFT JOIN Talento_Humano.dbo.th_unidades_organizacionales u ON u.unidad_id = e.unidad_id
             WHERE e.estado = 1
               AND NOT EXISTS (SELECT 1 FROM CORE_Usuarios cu WHERE cu.id_empleado_th = e.empleado_id)
               $where
             ORDER BY e.apellidos
             OFFSET ? ROWS FETCH NEXT ? ROWS ONLY",
            array_merge($likeParams, [[$offset, SQLSRV_PARAM_IN], [self::TH_POR_PAGINA, SQLSRV_PARAM_IN]])
        );

        $this->render('Central/admin/empleados_th', [
            'pageTitle'  => 'Nuevo Usuario — desde Talento Humano',
            'empleados'  => $db->fetchAll($stmt),
            'buscar'     => $buscar,
            'page'       => $page,
            'totalPages' => max(1, (int)ceil($total / self::TH_POR_PAGINA)),
            'total'      => $total,
        ]);
    }

    /** GET /admin/usuarios/desde-th/{id}/nuevo — formulario con depto/rol autosugeridos. */
    public function nuevoUsuarioDesdeEmpleado(int $idEmpleadoTh): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_USUARIOS, 2]);

        $emp = $this->db()->fetch($this->db()->query(
            "SELECT e.empleado_id, e.identificacion AS cedula, e.nombres + ' ' + e.apellidos AS nombre_completo,
                    e.correo_institucional, u.codigo_uorg, u.nombre_unidad,
                    p.nombre_puesto AS cargo,
                    e.telefono_movil, e.telefono_convencional, e.fecha_ingreso,
                    e.tipo_contrato, e.jornada, e.estado, e.ruta_foto
             FROM Talento_Humano.dbo.th_empleados e
             LEFT JOIN Talento_Humano.dbo.th_unidades_organizacionales u ON u.unidad_id = e.unidad_id
             LEFT JOIN Talento_Humano.dbo.th_puestos p ON p.puesto_id = e.puesto_id
             WHERE e.empleado_id = ?",
            [[$idEmpleadoTh, SQLSRV_PARAM_IN]]
        ));
        if (!$emp) {
            SessionHelper::flash('error', 'Empleado no encontrado en Talento Humano.');
            $this->redirect('/admin/usuarios/desde-th');
        }

        // Autosugerencia por unidad organizacional (TH_Unidad_Map). Si la
        // unidad no está mapeada, el admin elige departamento/rol a mano.
        $mapa = $this->db()->fetch($this->db()->query(
            'SELECT m.id_departamento, m.id_rol_director, m.id_rol_analista
             FROM TH_Unidad_Map m WHERE m.codigo_uorg = ?',
            [[$emp['codigo_uorg'] ?? '', SQLSRV_PARAM_IN]]
        ));

        // Foto real del empleado (la sirve apps/talento_humano/) — si quedó
        // en el default genérico o vacía, no hay foto real que mostrar.
        $rutaFoto = trim((string)($emp['ruta_foto'] ?? ''));
        $fotoUrl  = ($rutaFoto !== '' && $rutaFoto !== 'public/img/default_avatar.png')
            ? APP_URL . '/apps/talento_humano/' . ltrim($rutaFoto, '/')
            : null;

        $this->render('Central/admin/usuario_desde_empleado', [
            'pageTitle'  => 'Nueva cuenta — ' . $emp['nombre_completo'],
            'empleado'   => $emp,
            'fotoUrl'    => $fotoUrl,
            'sugerido'   => $mapa ?: null,
            'deptos'     => $this->deptos(),
            'todosRoles' => $this->db()->fetchAll($this->db()->query('SELECT id_rol, nombre FROM CORE_Roles WHERE estado=1 ORDER BY nombre')),
            'csrf'       => $this->csrfToken(),
        ]);
    }

    /** POST /admin/usuarios/desde-th — crea la cuenta ligada al empleado TH. */
    public function crearUsuarioDesdeEmpleado(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_USUARIOS, 2]);
        $this->verifyCsrf();

        // Ya no se pide "nombre de usuario": el login es SOLO por cédula, y la
        // cédula viene de Talento Humano (fuente única, ya única por empleado).
        if (!FormHelper::validate($_POST, [
            'id_empleado_th'  => 'required|numeric',
            'contrasena'      => 'required|min:8',
            'nivel_jerarquia' => 'required|numeric',
            'id_departamento' => 'required|numeric',
            'id_rol'          => 'required|numeric',
        ])) {
            $_SESSION['_form_errors'] = FormHelper::errors();
            $_SESSION['_old_input']   = $_POST;
            $this->redirect('/admin/usuarios/desde-th/' . (int)($_POST['id_empleado_th'] ?? 0) . '/nuevo');
        }

        $idEmpleadoTh = (int)$_POST['id_empleado_th'];
        $emp = $this->db()->fetch($this->db()->query(
            "SELECT e.identificacion AS cedula, e.correo_institucional
             FROM Talento_Humano.dbo.th_empleados e WHERE e.empleado_id = ?",
            [[$idEmpleadoTh, SQLSRV_PARAM_IN]]
        ));
        if (!$emp) {
            SessionHelper::flash('error', 'Empleado no encontrado en Talento Humano.');
            $this->redirect('/admin/usuarios/desde-th');
        }

        $yaExiste = $this->db()->fetch($this->db()->query(
            'SELECT 1 FROM CORE_Usuarios WHERE cedula=? OR nombre_usuario=?',
            [[$emp['cedula'], SQLSRV_PARAM_IN], [$emp['cedula'], SQLSRV_PARAM_IN]]
        ));
        if ($yaExiste) {
            SessionHelper::flash('error', 'Ya existe una cuenta con esa cédula.');
            $this->redirect('/admin/usuarios/desde-th');
        }

        $hash = SecurityHelper::hashPassword($_POST['contrasena']);
        $db   = $this->db();
        $db->beginTransaction();

        // nombre_completo/cedula locales quedan como respaldo (por si el
        // vínculo se rompe); vw_Usuarios_Identidad prioriza el dato en vivo.
        // nombre_usuario = cédula: el login es únicamente por cédula, no se
        // maneja un "usuario" separado (columna se mantiene por compatibilidad
        // con sp_Login y el contrato SSO de TH/Bienes, pero es transparente).
        // OUTPUT INSERTED en vez de SCOPE_IDENTITY() aparte: con sqlsrv_prepare()
        // el driver ODBC ejecuta el INSERT como sp_execute, un scope anidado que
        // SCOPE_IDENTITY() pierde de vista en cuanto termina esa llamada — una
        // consulta SEPARADA después siempre devuelve NULL. OUTPUT INSERTED lee el
        // id en la MISMA sentencia, sin depender de ningún scope posterior.
        $stmt = $db->query(
            'INSERT INTO CORE_Usuarios
                (nombre_usuario, nombre_completo, correo, cedula, hash_contrasena,
                 nivel_jerarquia, id_departamento, id_empleado_th, estado)
             OUTPUT INSERTED.id_usuario
             VALUES (?,?,?,?,?,?,?,?,1)',
            [
                [$emp['cedula'], SQLSRV_PARAM_IN],
                [$this->empleadoNombreCompleto($idEmpleadoTh), SQLSRV_PARAM_IN],
                [trim($_POST['correo'] ?? $emp['correo_institucional'] ?? ''), SQLSRV_PARAM_IN],
                [$emp['cedula'], SQLSRV_PARAM_IN],
                [$hash, SQLSRV_PARAM_IN],
                [(int)$_POST['nivel_jerarquia'], SQLSRV_PARAM_IN],
                [(int)$_POST['id_departamento'], SQLSRV_PARAM_IN],
                [$idEmpleadoTh, SQLSRV_PARAM_IN],
            ]
        );

        $idUsuario = (int)$db->fetch($stmt)['id_usuario'];
        $db->query(
            'INSERT INTO CORE_Usuarios_Roles (id_usuario, id_rol, asignado_por) VALUES (?,?,?)',
            [
                [$idUsuario, SQLSRV_PARAM_IN],
                [(int)$_POST['id_rol'], SQLSRV_PARAM_IN],
                [(int)($_SESSION['user_id'] ?? 0), SQLSRV_PARAM_IN],
            ]
        );
        $db->commit();

        ModuleSecurity::audit('CORE', 'CREAR', 'CORE_Usuarios', (string)$idUsuario, null, [
            'cedula' => $emp['cedula'], 'nombre_completo' => $this->empleadoNombreCompleto($idEmpleadoTh),
            'nivel_jerarquia' => (int)$_POST['nivel_jerarquia'], 'id_departamento' => (int)$_POST['id_departamento'],
            'id_rol' => (int)$_POST['id_rol'], 'id_empleado_th' => $idEmpleadoTh,
        ], 'EXITO', 'Cuenta creada desde empleado de Talento Humano');

        $puenteOk = $this->provisionarCuentaTh($idEmpleadoTh, (int)$_POST['id_rol'], $emp['cedula'],
            $this->empleadoNombreCompleto($idEmpleadoTh), trim($_POST['correo'] ?? $emp['correo_institucional'] ?? ''), $hash);

        SessionHelper::flash('success', $puenteOk
            ? 'Cuenta creada y vinculada al empleado de Talento Humano. Ya puede acceder al módulo de TH con la misma contraseña.'
            : 'Cuenta creada, pero no se pudo provisionar el acceso nativo a Talento Humano (revisar conexión a esa BD) — el usuario no podrá entrar a ese módulo hasta que se resuelva.');
        $this->redirect('/admin/usuarios');
    }

    /**
     * Crea la fila espejo en Talento_Humano.dbo.th_usuarios_sistema para que
     * el puente de identidad (Auth::loginTrusted() del lado de TH) encuentre
     * la cuenta por empleado_id y autentique sin pedir credenciales de nuevo.
     *
     * Bug real corregido 2026-08-13: crearUsuarioDesdeEmpleado() solo creaba
     * la cuenta en CORE_Usuarios — sin esta fila, el JOIN cross-DB que hace
     * el puente (CORE_Usuarios.id_empleado_th = th_usuarios_sistema.empleado_id)
     * no encontraba nada, TH caía a su login propio, y ahí la cuenta
     * genuinamente no existía ("credenciales incorrectas" con cualquier
     * contraseña). Reusa el MISMO hash bcrypt ya calculado para el portal —
     * misma contraseña de un solo lado, sin pedirla dos veces.
     *
     * Nota de alcance: th_usuarios_sistema.rol_id es un ÚNICO rol nativo de
     * TH (th_permisos_rol), no tiene equivalente de "permiso individual por
     * usuario" — cualquier override individual otorgado desde
     * /admin/usuarios/{id}/permisos para nodos del módulo 11 gobierna el
     * acceso vía el portal, pero TH puertas adentro sigue viendo solo el rol
     * nativo mapeado. Si el rol de portal no tiene equivalente en
     * CORE_Roles_Modulo_Map, se usa el rol TH de menor privilegio
     * ("Funcionario (Lectura)") como base seguridad-primero.
     */
    private function provisionarCuentaTh(int $idEmpleadoTh, int $idRolPortal, string $cedula, string $nombre, string $correo, string $hash): bool {
        try {
            $conn = require dirname(__DIR__, 3) . '/config/connections.php';
            $opts = ['CharacterSet' => 'UTF-8', 'TrustServerCertificate' => (bool)($conn['options']['trust_cert'] ?? true)];
            if (!empty($conn['credentials']['user'])) { $opts['UID'] = $conn['credentials']['user']; $opts['PWD'] = $conn['credentials']['pass']; }
            $opts['Database'] = $conn['databases']['talento']['name'] ?? 'Talento_Humano';
            $c = @sqlsrv_connect($conn['databases']['talento']['server'] ?? $conn['server_default'], $opts);
            if ($c === false) return false;

            $yaExiste = sqlsrv_query($c, 'SELECT usuario_id FROM dbo.th_usuarios_sistema WHERE empleado_id=?', [$idEmpleadoTh]);
            if ($yaExiste !== false && sqlsrv_fetch_array($yaExiste, SQLSRV_FETCH_ASSOC)) {
                sqlsrv_close($c);
                return true; // ya provisionada (idempotente)
            }

            $mapa = sqlsrv_query($c,
                'SELECT id_rol_externo FROM PORTAL_APM.dbo.CORE_Roles_Modulo_Map WHERE id_modulo=11 AND id_rol_portal=?', [$idRolPortal]);
            $rolTh = 4; // "Funcionario (Lectura)" -- default seguro si el rol de portal no tiene mapeo nativo
            if ($mapa !== false) {
                $row = sqlsrv_fetch_array($mapa, SQLSRV_FETCH_ASSOC);
                if ($row) $rolTh = (int)$row['id_rol_externo'];
            }

            $ok = sqlsrv_query($c,
                'INSERT INTO dbo.th_usuarios_sistema (usuario, password_hash, correo, nombre, empleado_id, rol_id, estado, debe_cambiar_clave)
                 VALUES (?,?,?,?,?,?,1,0)',
                [$cedula, $hash, $correo !== '' ? $correo : ($cedula . '@apm.gob.ec'), $nombre, $idEmpleadoTh, $rolTh]
            );
            sqlsrv_close($c);
            return $ok !== false;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function empleadoNombreCompleto(int $idEmpleadoTh): string {
        $r = $this->db()->fetch($this->db()->query(
            "SELECT nombres + ' ' + apellidos AS n FROM Talento_Humano.dbo.th_empleados WHERE empleado_id = ?",
            [[$idEmpleadoTh, SQLSRV_PARAM_IN]]
        ));
        return $r['n'] ?? '';
    }

    // ─── Inactividad de sesión ─────────────────────────────────────────────
    // Solo Administrador general (nivel_jerarquia 4) — requireLevel(4) sin
    // nodo MOIS es a propósito: es un ajuste global de seguridad, no una
    // opción de menú de negocio como Usuarios/Roles.

    private const MODULOS_INACTIVIDAD = [
        'CENTRAL'         => 'Portal APM (Central)',
        'TALENTO_HUMANO'  => 'Talento Humano',
        'CONTROL_BIENES'  => 'Control de Bienes',
        'BITACORAS'       => 'Bitácoras',
    ];

    private function upsertConfig(Database $db, string $modulo, string $clave, string $valor): void {
        $r = $db->query(
            'UPDATE CORE_Config SET valor=?, fecha_mod=GETDATE() WHERE modulo=? AND clave=?',
            [[$valor, SQLSRV_PARAM_IN], [$modulo, SQLSRV_PARAM_IN], [$clave, SQLSRV_PARAM_IN]]
        );
        if ($db->rowsAffected($r) === 0) {
            $db->query(
                'INSERT INTO CORE_Config (modulo,clave,valor,tipo,fecha_mod,estado) VALUES (?,?,?,?,GETDATE(),1)',
                [[$modulo, SQLSRV_PARAM_IN], [$clave, SQLSRV_PARAM_IN], [$valor, SQLSRV_PARAM_IN], ['int', SQLSRV_PARAM_IN]]
            );
        }
    }

    /** GET /admin/inactividad */
    public function inactividad(): void {
        $this->requireAuth();
        $this->requireLevel(4);

        $db = $this->db();
        $global = $db->fetch($db->query(
            "SELECT (SELECT valor FROM CORE_Config WHERE modulo='CORE' AND clave='INACTIVIDAD_SEGUNDOS') AS segundos,
                    (SELECT valor FROM CORE_Config WHERE modulo='CORE' AND clave='INACTIVIDAD_AVISO_SEGUNDOS') AS aviso"
        ));

        $porModulo = [];
        foreach (self::MODULOS_INACTIVIDAD as $code => $label) {
            $row = $db->fetch($db->query(
                "SELECT (SELECT valor FROM CORE_Config WHERE modulo=? AND clave='INACTIVIDAD_SEGUNDOS') AS segundos,
                        (SELECT valor FROM CORE_Config WHERE modulo=? AND clave='INACTIVIDAD_AVISO_SEGUNDOS') AS aviso",
                [[$code, SQLSRV_PARAM_IN], [$code, SQLSRV_PARAM_IN]]
            ));
            $porModulo[$code] = ['label' => $label, 'segundos' => $row['segundos'] ?? null, 'aviso' => $row['aviso'] ?? null];
        }

        // Tabla completa (no solo los que ya tienen ajuste) — se puede fijar
        // un override desde cualquier fila, y de un vistazo se ve quién ya
        // tiene uno propio vs quién hereda módulo/global.
        $usuarios = $db->fetchAll($db->query(
            "SELECT u.id_usuario, u.nombre_completo, u.cedula, u.nivel_jerarquia, u.estado,
                    d.nombre AS departamento,
                    u.inactividad_segundos_override, u.inactividad_aviso_segundos_override
             FROM CORE_Usuarios u
             LEFT JOIN CORE_Departamentos d ON d.id_departamento = u.id_departamento
             ORDER BY u.estado DESC, u.nombre_completo"
        ));

        $this->render('Central/admin/inactividad', [
            'pageTitle'  => 'Tiempo de Inactividad',
            'global'     => $global ?: ['segundos' => 1800, 'aviso' => 60],
            'porModulo'  => $porModulo,
            'usuarios'   => $usuarios,
            'csrf'       => $this->csrfToken(),
        ]);
    }

    /** POST /admin/inactividad/global */
    public function actualizarInactividadGlobal(): void {
        $this->requireAuth();
        $this->requireLevel(4);
        $this->verifyCsrf();

        $segundos = max(30, (int)($_POST['segundos'] ?? 1800));
        $aviso    = max(5, min($segundos - 5, (int)($_POST['aviso'] ?? 60)));

        $db    = $this->db();
        $antes = $db->fetch($db->query(
            "SELECT (SELECT valor FROM CORE_Config WHERE modulo='CORE' AND clave='INACTIVIDAD_SEGUNDOS') AS s,
                    (SELECT valor FROM CORE_Config WHERE modulo='CORE' AND clave='INACTIVIDAD_AVISO_SEGUNDOS') AS a"
        ));

        $this->upsertConfig($db, 'CORE', 'INACTIVIDAD_SEGUNDOS', (string)$segundos);
        $this->upsertConfig($db, 'CORE', 'INACTIVIDAD_AVISO_SEGUNDOS', (string)$aviso);

        ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Config', 'CORE',
            $antes ? ['segundos' => $antes['s'], 'aviso' => $antes['a']] : null,
            ['segundos' => $segundos, 'aviso' => $aviso],
            'EXITO', 'Tiempo de inactividad global'
        );

        $this->invalidarCacheInactividad();
        SessionHelper::flash('success', 'Tiempo global de inactividad actualizado.');
        $this->redirect('/admin/inactividad');
    }

    /** POST /admin/inactividad/modulo/{modulo} */
    public function actualizarInactividadModulo(string $modulo): void {
        $this->requireAuth();
        $this->requireLevel(4);
        $this->verifyCsrf();

        if (!isset(self::MODULOS_INACTIVIDAD[$modulo])) { http_response_code(404); exit; }

        $db    = $this->db();
        $antes = $db->fetch($db->query(
            "SELECT (SELECT valor FROM CORE_Config WHERE modulo=? AND clave='INACTIVIDAD_SEGUNDOS') AS s,
                    (SELECT valor FROM CORE_Config WHERE modulo=? AND clave='INACTIVIDAD_AVISO_SEGUNDOS') AS a",
            [[$modulo, SQLSRV_PARAM_IN], [$modulo, SQLSRV_PARAM_IN]]
        ));

        if (!empty($_POST['usar_global'])) {
            $db->query(
                "DELETE FROM CORE_Config WHERE modulo=? AND clave IN ('INACTIVIDAD_SEGUNDOS','INACTIVIDAD_AVISO_SEGUNDOS')",
                [[$modulo, SQLSRV_PARAM_IN]]
            );
            $despues = null;
        } else {
            $segundos = max(30, (int)($_POST['segundos'] ?? 1800));
            $aviso    = max(5, min($segundos - 5, (int)($_POST['aviso'] ?? 60)));
            $this->upsertConfig($db, $modulo, 'INACTIVIDAD_SEGUNDOS', (string)$segundos);
            $this->upsertConfig($db, $modulo, 'INACTIVIDAD_AVISO_SEGUNDOS', (string)$aviso);
            $despues = ['segundos' => $segundos, 'aviso' => $aviso];
        }

        ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Config', $modulo,
            $antes ? ['segundos' => $antes['s'], 'aviso' => $antes['a']] : null,
            $despues, 'EXITO', 'Tiempo de inactividad — módulo ' . self::MODULOS_INACTIVIDAD[$modulo]
        );

        $this->invalidarCacheInactividad();
        SessionHelper::flash('success', 'Configuración de ' . self::MODULOS_INACTIVIDAD[$modulo] . ' actualizada.');
        $this->redirect('/admin/inactividad');
    }

    /** POST /admin/inactividad/usuario/{id} */
    public function actualizarInactividadUsuario(int $id): void {
        $this->requireAuth();
        $this->requireLevel(4);
        $this->verifyCsrf();

        $db    = $this->db();
        $antes = $db->fetch($db->query(
            'SELECT nombre_completo, inactividad_segundos_override, inactividad_aviso_segundos_override FROM CORE_Usuarios WHERE id_usuario=?',
            [[$id, SQLSRV_PARAM_IN]]
        ));
        if (!$antes) { $this->json(['error' => 'Usuario no encontrado'], 404); }

        if (!empty($_POST['quitar_override'])) {
            $segundos = null; $aviso = null;
        } else {
            $segundos = max(30, (int)($_POST['segundos'] ?? 1800));
            $aviso    = max(5, min($segundos - 5, (int)($_POST['aviso'] ?? 60)));
        }

        $db->query(
            'UPDATE CORE_Usuarios SET inactividad_segundos_override=?, inactividad_aviso_segundos_override=? WHERE id_usuario=?',
            [[$segundos, SQLSRV_PARAM_IN], [$aviso, SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN]]
        );

        ModuleSecurity::audit('CORE', 'ACTUALIZAR', 'CORE_Usuarios', (string)$id,
            ['segundos' => $antes['inactividad_segundos_override'], 'aviso' => $antes['inactividad_aviso_segundos_override']],
            ['segundos' => $segundos, 'aviso' => $aviso],
            'EXITO', 'Override individual de inactividad — ' . $antes['nombre_completo']
        );

        // Si el admin se está editando a sí mismo, que su propia sesión lo
        // note de inmediato — si es otro usuario, no hay forma de tocar la
        // sesión de otra persona desde acá; a esa le llega en máximo 5 min
        // (o en su próximo login) por el cache normal de resolveInactividad().
        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            $this->invalidarCacheInactividad();
        }

        if ($this->isAjax()) { $this->json(['ok' => true]); }
        SessionHelper::flash('success', 'Override de inactividad de ' . $antes['nombre_completo'] . ' actualizado.');
        $this->redirect('/admin/inactividad');
    }

    /** Fuerza a que la sesión ACTUAL vuelva a resolver su tiempo de inactividad
     *  en el próximo request, en vez de servir el valor cacheado hasta 5 min. */
    private function invalidarCacheInactividad(): void {
        unset($_SESSION['_inactividad_segundos'], $_SESSION['_inactividad_aviso'], $_SESSION['_inactividad_resuelto_en']);
    }
}
