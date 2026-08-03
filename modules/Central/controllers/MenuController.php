<?php
class MenuController extends Controller {

    private const MODULES = [
        1  => ['label' => 'Dirección de Planificación Estratégica', 'icon' => 'fa-chart-gantt',       'color' => '#6f42c1'],
        2  => ['label' => 'Gestión de Tecnología de la Información','icon' => 'fa-server',            'color' => '#0056b3'],
        3  => ['label' => 'Dirección de Asesoría Jurídica',         'icon' => 'fa-scale-balanced',    'color' => '#dc3545'],
        4  => ['label' => 'Dirección de Infraestructura Portuaria', 'icon' => 'fa-hard-hat',          'color' => '#fd7e14'],
        5  => ['label' => 'Garita de Acceso / Control de Acceso',   'icon' => 'fa-door-open',         'color' => '#20c997'],
        6  => ['label' => 'Dirección de Operaciones',               'icon' => 'fa-ship',              'color' => '#17a2b8'],
        7  => ['label' => 'Gerencia General',                       'icon' => 'fa-building',          'color' => '#343a40'],
        8  => ['label' => 'Delegación de Servicios Portuarios',     'icon' => 'fa-landmark',          'color' => '#6f42c1'],
        9  => ['label' => 'Dirección Administrativa',               'icon' => 'fa-briefcase',         'color' => '#0056b3'],
        10 => ['label' => 'Dirección Financiera',                   'icon' => 'fa-wallet',            'color' => '#28a745'],
        11 => ['label' => 'Dirección de Talento Humano',            'icon' => 'fa-users',             'color' => '#e83e8c'],
        12 => ['label' => 'Control de Bienes (Inventario)',         'icon' => 'fa-boxes-stacked',     'color' => '#fd7e14'],
        13 => ['label' => 'Bitácoras Portuarias (CCTV/Visitas)',    'icon' => 'fa-anchor',            'color' => '#0891b2'],
    ];

    // Nodo MOIS de "Estructura del Menú" (Central > Administración).
    // nivel_crud: 1=Ver, 2=Crear, 3=Editar, 4=Total.
    private const NODO_MENU = [1, 2, 4, 0];

    private function db(): Database { return Database::getInstance(); }

    public function index(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MENU, 1]);

        $db    = $this->db();
        $nodos = $db->fetchAll($db->query(
            'SELECT id_nodo, id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado
             FROM CORE_Menu_Nodos
             ORDER BY id_modulo, opcion, items, subitems, orden'
        ));

        $tree    = [];
        $total   = count($nodos);
        $activos = 0;

        foreach ($nodos as $n) {
            $mod = (int)$n['id_modulo'];
            $op  = (int)$n['opcion'];
            $it  = (int)$n['items'];
            $sub = (int)$n['subitems'];
            if ($n['estado']) $activos++;

            if (!isset($tree[$mod])) {
                $tree[$mod] = ['meta' => self::MODULES[$mod] ?? ['label' => "Módulo $mod", 'icon' => 'fa-folder', 'color' => '#6c757d'], 'raiz' => null, 'areas' => []];
            }
            if ($op === 0) {
                $tree[$mod]['raiz'] = $n;
            } elseif ($it === 0) {
                $tree[$mod]['areas'][$op]['raiz']  = $n;
                if (!isset($tree[$mod]['areas'][$op]['items'])) $tree[$mod]['areas'][$op]['items'] = [];
            } elseif ($sub === 0) {
                if (!isset($tree[$mod]['areas'][$op])) $tree[$mod]['areas'][$op] = ['raiz' => null, 'items' => []];
                $tree[$mod]['areas'][$op]['items'][$it]['raiz']     = $n;
                if (!isset($tree[$mod]['areas'][$op]['items'][$it]['children'])) $tree[$mod]['areas'][$op]['items'][$it]['children'] = [];
            } else {
                if (!isset($tree[$mod]['areas'][$op])) $tree[$mod]['areas'][$op] = ['raiz' => null, 'items' => []];
                if (!isset($tree[$mod]['areas'][$op]['items'][$it])) $tree[$mod]['areas'][$op]['items'][$it] = ['raiz' => null, 'children' => []];
                $tree[$mod]['areas'][$op]['items'][$it]['children'][] = $n;
            }
        }

        $error = SessionHelper::getFlash('error');

        $this->render('Central/admin/menu/index', [
            'pageTitle' => 'Estructura del Menú',
            'tree'      => $tree,
            'total'     => $total,
            'activos'   => $activos,
            'modules'   => self::MODULES,
            'error'     => $error,
            'success'   => SessionHelper::getFlash('success'),
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function nuevo(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MENU, 1]);

        // Se cargan TODOS los nodos (incluidos inactivos): un nodo inactivo
        // sigue ocupando su tupla MOIS, y el formulario los necesita para
        // sugerir la siguiente coordenada libre y avisar de duplicados.
        $db    = $this->db();
        $nodos = $db->fetchAll($db->query(
            'SELECT id_nodo, id_modulo, opcion, items, subitems, descripcion
             FROM CORE_Menu_Nodos
             ORDER BY id_modulo, opcion, items, subitems'
        ));

        $errors   = $_SESSION['_form_errors'] ?? [];
        $oldInput = $_SESSION['_old_input']   ?? [];
        unset($_SESSION['_form_errors'], $_SESSION['_old_input']);

        $this->render('Central/admin/menu/form', [
            'pageTitle' => 'Nuevo Nodo de Menú',
            'nodo'      => null,
            'nodos'     => $nodos,
            'modules'   => self::MODULES,
            'errors'    => $errors,
            'oldInput'  => $oldInput,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function crear(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MENU, 2]);
        $this->verifyCsrf();

        if (!FormHelper::validate($_POST, [
            'id_modulo'   => 'required|numeric',
            'descripcion' => 'required|min:3|max:200',
        ])) {
            $_SESSION['_form_errors'] = FormHelper::errors();
            $_SESSION['_old_input']   = $_POST;
            $this->redirect('/admin/menu/nuevo');
        }

        $db       = $this->db();
        $idModulo = (int)$_POST['id_modulo'];
        $opcion   = max(0, (int)($_POST['opcion']   ?? 0));
        $items    = max(0, (int)($_POST['items']    ?? 0));
        $subitems = max(0, (int)($_POST['subitems'] ?? 0));

        $existe = $db->fetch($db->query(
            'SELECT id_nodo FROM CORE_Menu_Nodos WHERE id_modulo=? AND opcion=? AND items=? AND subitems=?',
            [[$idModulo, SQLSRV_PARAM_IN], [$opcion, SQLSRV_PARAM_IN], [$items, SQLSRV_PARAM_IN], [$subitems, SQLSRV_PARAM_IN]]
        ));

        if ($existe) {
            $_SESSION['_form_errors'] = ['La tupla Módulo/Opción/Ítem/Sub-ítem ya existe. Elige valores diferentes.'];
            $_SESSION['_old_input']   = $_POST;
            $this->redirect('/admin/menu/nuevo');
        }

        $urlRuta = trim($_POST['url_ruta'] ?? '');
        $db->query(
            'INSERT INTO CORE_Menu_Nodos (id_modulo, opcion, items, subitems, descripcion, url_ruta, icono, orden, requiere_mfa, target_spa, estado)
             VALUES (?,?,?,?,?,?,?,?,?,?,1)',
            [
                [$idModulo,                                  SQLSRV_PARAM_IN],
                [$opcion,                                    SQLSRV_PARAM_IN],
                [$items,                                     SQLSRV_PARAM_IN],
                [$subitems,                                  SQLSRV_PARAM_IN],
                [trim($_POST['descripcion']),                SQLSRV_PARAM_IN],
                [$urlRuta ?: null,                           SQLSRV_PARAM_IN],
                [trim($_POST['icono'] ?? '') ?: 'fa-circle', SQLSRV_PARAM_IN],
                [(int)($_POST['orden'] ?? 0),                SQLSRV_PARAM_IN],
                [(int)($_POST['requiere_mfa'] ?? 0),         SQLSRV_PARAM_IN],
                [(int)($_POST['target_spa'] ?? 1),           SQLSRV_PARAM_IN],
            ]
        );

        SessionHelper::flash('success', 'Nodo creado correctamente.');
        $this->redirect('/admin/menu');
    }

    public function editar(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MENU, 1]);

        $db   = $this->db();
        $nodo = $db->fetch($db->query('SELECT * FROM CORE_Menu_Nodos WHERE id_nodo=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$nodo) { http_response_code(404); exit; }

        $errors   = $_SESSION['_form_errors'] ?? [];
        $oldInput = $_SESSION['_old_input']   ?? [];
        unset($_SESSION['_form_errors'], $_SESSION['_old_input']);

        $this->render('Central/admin/menu/form', [
            'pageTitle' => 'Editar Nodo',
            'nodo'      => $nodo,
            'nodos'     => [],
            'modules'   => self::MODULES,
            'errors'    => $errors,
            'oldInput'  => $oldInput,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function actualizar(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MENU, 3]);
        $this->verifyCsrf();

        $urlRuta = trim($_POST['url_ruta'] ?? '');
        $this->db()->query(
            'UPDATE CORE_Menu_Nodos SET descripcion=?, url_ruta=?, icono=?, orden=?, requiere_mfa=?, target_spa=?, estado=? WHERE id_nodo=?',
            [
                [trim($_POST['descripcion']),                SQLSRV_PARAM_IN],
                [$urlRuta ?: null,                           SQLSRV_PARAM_IN],
                [trim($_POST['icono'] ?? '') ?: 'fa-circle', SQLSRV_PARAM_IN],
                [(int)($_POST['orden'] ?? 0),                SQLSRV_PARAM_IN],
                [(int)($_POST['requiere_mfa'] ?? 0),         SQLSRV_PARAM_IN],
                [(int)($_POST['target_spa'] ?? 1),           SQLSRV_PARAM_IN],
                [(int)($_POST['estado'] ?? 1),               SQLSRV_PARAM_IN],
                [$id,                                        SQLSRV_PARAM_IN],
            ]
        );

        SessionHelper::flash('success', 'Nodo actualizado.');
        $this->redirect('/admin/menu');
    }

    /**
     * Aplica un cambio de estado a un nodo con la misma cascada que siempre
     * tuvo el toggle individual: descendente al desactivar (oculta hijos) y
     * ascendente al activar (activa ancestros para no quedar invisible).
     * Devuelve los IDs de TODOS los nodos afectados (para reportar al frontend).
     * Reutilizado por toggle() (single) y guardarLote() (batch, en transacción).
     */
    private function aplicarCascadaEstado(Database $db, int $id, array $nodo, int $nuevo): array {
        $mod = (int)$nodo['id_modulo'];
        $op  = (int)$nodo['opcion'];
        $it  = (int)$nodo['items'];
        $sub = (int)$nodo['subitems'];

        // Determinar alcance según nivel MOIS
        if ($op === 0) {
            // L1 (Menú): cascada sobre todo el módulo
            $scopeWhere  = 'id_modulo = ?';
            $scopeParams = [[$mod, SQLSRV_PARAM_IN]];
        } elseif ($it === 0) {
            // L2 (Opción): cascada sobre toda la opción
            $scopeWhere  = 'id_modulo = ? AND opcion = ?';
            $scopeParams = [[$mod, SQLSRV_PARAM_IN], [$op, SQLSRV_PARAM_IN]];
        } elseif ($sub === 0) {
            // L3 (Ítem): cascada sobre el ítem y sus sub-ítems
            $scopeWhere  = 'id_modulo = ? AND opcion = ? AND items = ?';
            $scopeParams = [[$mod, SQLSRV_PARAM_IN], [$op, SQLSRV_PARAM_IN], [$it, SQLSRV_PARAM_IN]];
        } else {
            // L4 (Sub-ítem): solo el nodo
            $scopeWhere  = 'id_nodo = ?';
            $scopeParams = [[$id, SQLSRV_PARAM_IN]];
        }

        // IDs afectados por la cascada DESCENDENTE (el nodo + sus descendientes)
        $affected = $db->fetchAll($db->query(
            "SELECT id_nodo FROM CORE_Menu_Nodos WHERE $scopeWhere", $scopeParams
        ));
        $ids = array_map('intval', array_column($affected, 'id_nodo'));

        // Actualizar en cascada descendente:
        //   - Desactivar un padre  → oculta el nodo y TODOS sus descendientes.
        //   - Activar un padre     → muestra el nodo y su sub-árbol.
        $db->query(
            "UPDATE CORE_Menu_Nodos SET estado = ? WHERE $scopeWhere",
            [[$nuevo, SQLSRV_PARAM_IN], ...$scopeParams]
        );

        // Cascada ASCENDENTE al ACTIVAR: un hijo activo exige que sus ancestros
        // (Módulo → Opción → Ítem) también estén activos; de lo contrario el nodo
        // quedaría "activo pero invisible". Se activan y se reportan al frontend.
        if ($nuevo === 1) {
            $ancestros = [];
            if ($op > 0) {
                $ancestros[] = [$mod, 0,   0,   0];        // L1 Módulo
                if ($it > 0) {
                    $ancestros[] = [$mod, $op, 0,   0];    // L2 Opción
                    if ($sub > 0) {
                        $ancestros[] = [$mod, $op, $it, 0]; // L3 Ítem
                    }
                }
            }
            foreach ($ancestros as [$am, $ao, $ai, $as]) {
                $row = $db->fetch($db->query(
                    'SELECT id_nodo FROM CORE_Menu_Nodos WHERE id_modulo=? AND opcion=? AND items=? AND subitems=?',
                    [[$am, SQLSRV_PARAM_IN], [$ao, SQLSRV_PARAM_IN], [$ai, SQLSRV_PARAM_IN], [$as, SQLSRV_PARAM_IN]]
                ));
                if ($row) {
                    $aid = (int)$row['id_nodo'];
                    $db->query('UPDATE CORE_Menu_Nodos SET estado = 1 WHERE id_nodo = ?', [[$aid, SQLSRV_PARAM_IN]]);
                    if (!in_array($aid, $ids, true)) $ids[] = $aid;
                }
            }
        }

        return $ids;
    }

    /** POST /admin/menu/{id}/toggle — cambio individual inmediato (uso puntual/API). */
    public function toggle(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MENU, 3]);
        $this->verifyCsrf();

        $db   = $this->db();
        $nodo = $db->fetch($db->query('SELECT * FROM CORE_Menu_Nodos WHERE id_nodo=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$nodo) { $this->json(['ok' => false, 'msg' => 'Not found'], 404); return; }

        $nuevo = $nodo['estado'] ? 0 : 1;
        $ids   = $this->aplicarCascadaEstado($db, $id, $nodo, $nuevo);

        $this->json(['ok' => true, 'estado' => $nuevo, 'cascaded' => $ids]);
    }

    /**
     * POST /admin/menu/guardar-lote — aplica varios toggles pendientes de una
     * vez, en una sola transacción (si uno falla, no queda estado parcial).
     * Body: {cambios: [{id_nodo, estado}, ...]}
     */
    public function guardarLote(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_MENU, 3]);
        $this->verifyCsrf();

        $body    = json_decode(file_get_contents('php://input'), true);
        $cambios = is_array($body['cambios'] ?? null) ? $body['cambios'] : [];
        if (!$cambios) { $this->json(['ok' => false, 'msg' => 'Sin cambios.'], 400); return; }

        $db = $this->db();
        $db->beginTransaction();
        try {
            $todosAfectados = [];
            foreach ($cambios as $c) {
                $id    = (int)($c['id_nodo'] ?? 0);
                $nuevo = ((int)($c['estado'] ?? 0)) ? 1 : 0;
                if ($id <= 0) continue;

                $nodo = $db->fetch($db->query('SELECT * FROM CORE_Menu_Nodos WHERE id_nodo=?', [[$id, SQLSRV_PARAM_IN]]));
                if (!$nodo) continue;
                if ((int)$nodo['estado'] === $nuevo) continue; // ya está en ese estado, nada que hacer

                $ids = $this->aplicarCascadaEstado($db, $id, $nodo, $nuevo);
                foreach ($ids as $aid) { $todosAfectados[$aid] = true; }
            }

            // Estado final real de todos los afectados (varios cambios del
            // mismo lote pueden pisarse entre sí — leemos la verdad post-cascada).
            $estados = [];
            if ($todosAfectados) {
                $ids   = array_map('intval', array_keys($todosAfectados));
                $place = implode(',', array_fill(0, count($ids), '?'));
                $rows  = $db->fetchAll($db->query(
                    "SELECT id_nodo, estado FROM CORE_Menu_Nodos WHERE id_nodo IN ($place)",
                    array_map(fn($i) => [$i, SQLSRV_PARAM_IN], $ids)
                ));
                foreach ($rows as $r) { $estados[(int)$r['id_nodo']] = (int)$r['estado']; }
            }

            $db->commit();
        } catch (Throwable $e) {
            $db->rollback();
            $this->json(['ok' => false, 'msg' => 'No se pudieron guardar los cambios.'], 500);
            return;
        }

        $this->json(['ok' => true, 'estados' => (object)$estados]);
    }

    /** GET /admin/menu/sidebar-fragmento — sidebar recién renderizado, para refrescarlo sin recargar la página. */
    public function sidebarFragmento(): void {
        $this->requireAuth();
        View::partial('Central/layouts/sidebar');
    }

    public function eliminar(int $id): void {
        $this->requireAuth();
        $this->requireLevel(4, [...self::NODO_MENU, 4]);
        $this->verifyCsrf();

        $db   = $this->db();
        $nodo = $db->fetch($db->query('SELECT * FROM CORE_Menu_Nodos WHERE id_nodo=?', [[$id, SQLSRV_PARAM_IN]]));
        if (!$nodo) { $this->redirect('/admin/menu'); }

        $enUso = $db->fetch($db->query(
            'SELECT TOP 1 id_perm_nodo FROM CORE_Permisos_Nodo WHERE id_modulo=? AND opcion=? AND items=? AND subitems=?',
            [[(int)$nodo['id_modulo'], SQLSRV_PARAM_IN], [(int)$nodo['opcion'], SQLSRV_PARAM_IN],
             [(int)$nodo['items'], SQLSRV_PARAM_IN], [(int)$nodo['subitems'], SQLSRV_PARAM_IN]]
        ));

        if ($enUso) {
            SessionHelper::flash('error', 'No se puede eliminar: este nodo tiene permisos asignados a roles. Desactívalo con el toggle.');
        } else {
            $db->query('DELETE FROM CORE_Menu_Nodos WHERE id_nodo=?', [[$id, SQLSRV_PARAM_IN]]);
            SessionHelper::flash('success', 'Nodo eliminado.');
        }
        $this->redirect('/admin/menu');
    }
}
