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

        $stmt = $this->db()->query(
            'SELECT u.id_usuario, u.cedula, u.nombre_completo, u.correo,
                    u.nivel_jerarquia, u.estado, d.nombre AS departamento
             FROM CORE_Usuarios u
             LEFT JOIN CORE_Departamentos d ON d.id_departamento = u.id_departamento
             ORDER BY u.nombre_completo'
        );
        $this->render('Central/admin/usuarios', [
            'pageTitle' => 'Gestión de Usuarios',
            'usuarios'  => $this->db()->fetchAll($stmt),
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

        $this->render('Central/admin/usuario_form', [
            'pageTitle'         => 'Editar Usuario',
            'usuario'           => $usuario,
            'deptos'            => $this->deptos(),
            'todosRoles'        => $db->fetchAll($db->query('SELECT id_rol, nombre, codigo FROM CORE_Roles WHERE estado=1 ORDER BY nombre')),
            'rolesAsignadosIds' => array_map('intval', array_column($asignados, 'id_rol')),
            'csrf'              => $this->csrfToken(),
        ]);
    }

    public function actualizarUsuario(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_USUARIOS, 3]);
        $this->verifyCsrf();

        $db = $this->db();
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
        foreach (array_map('intval', $_POST['roles'] ?? []) as $rolId) {
            if ($rolId > 0) {
                $db->query(
                    'INSERT INTO CORE_Usuarios_Roles (id_usuario, id_rol) VALUES (?,?)',
                    [[$id, SQLSRV_PARAM_IN], [$rolId, SQLSRV_PARAM_IN]]
                );
            }
        }

        SessionHelper::flash('success', 'Usuario actualizado correctamente.');
        $this->redirect('/admin/usuarios');
    }

    public function eliminarUsuario(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_USUARIOS, 4]);
        $this->verifyCsrf();

        $this->db()->query('UPDATE CORE_Usuarios SET estado=0 WHERE id_usuario=?', [[$id, SQLSRV_PARAM_IN]]);
        SessionHelper::flash('success', 'Usuario desactivado.');
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

    public function nuevoRol(): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 1]);

        $this->render('Central/admin/rol_form', [
            'pageTitle' => 'Nuevo Rol',
            'rol'       => null,
            'deptos'    => $this->deptos(),
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

        $deptoId = !empty($_POST['id_departamento']) ? (int)$_POST['id_departamento'] : null;
        $this->db()->query(
            'INSERT INTO CORE_Roles (codigo, nombre, descripcion, id_departamento, nivel_jerarquia, estado) VALUES (?,?,?,?,?,1)',
            [
                [strtoupper(trim($_POST['codigo'])),   SQLSRV_PARAM_IN],
                [trim($_POST['nombre']),               SQLSRV_PARAM_IN],
                [trim($_POST['descripcion'] ?? ''),    SQLSRV_PARAM_IN],
                [$deptoId,                             SQLSRV_PARAM_IN],
                [(int)$_POST['nivel_jerarquia'],       SQLSRV_PARAM_IN],
            ]
        );
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
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function actualizarRol(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 3]);
        $this->verifyCsrf();

        $deptoId = !empty($_POST['id_departamento']) ? (int)$_POST['id_departamento'] : null;
        $this->db()->query(
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
        SessionHelper::flash('success', 'Rol actualizado correctamente.');
        $this->redirect('/admin/roles');
    }

    public function eliminarRol(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 4]);
        $this->verifyCsrf();

        $this->db()->query('UPDATE CORE_Roles SET estado=0 WHERE id_rol=?', [[$id, SQLSRV_PARAM_IN]]);
        SessionHelper::flash('success', 'Rol desactivado.');
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

        // Mismo ícono/color que MenuController::MODULES — para que Estructura
        // del Menú y Roles y Permisos "hablen el mismo idioma" visual.
        $moduleMeta = [
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

        $this->render('Central/admin/rol_permisos', [
            'pageTitle'      => 'Permisos: ' . htmlspecialchars($rol['nombre'], ENT_QUOTES),
            'rol'            => $rol,
            'usuariosConRol' => $usuariosConRol,
            'tree'           => $tree,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function guardarPermisos(int $id): void {
        $this->requireAuth();
        $this->requireLevel(3, [...self::NODO_ROLES, 4]);
        $this->verifyCsrf();

        $db = $this->db();
        $db->query('DELETE FROM CORE_Permisos_Nodo WHERE id_rol=?', [[$id, SQLSRV_PARAM_IN]]);

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
        }

        if (View::isAjax()) {
            $this->json(['ok' => true, 'msg' => 'Permisos guardados correctamente.']);
        }

        SessionHelper::flash('success', 'Permisos guardados correctamente.');
        $this->redirect('/admin/roles/' . $id . '/permisos');
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
    private function descargarExcel(XlsxWriter $x, string $base): never {
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

    /** GET /admin/usuarios/{id}/export/pdf — un usuario (ficha). */
    public function exportarUsuarioPdf(int $id): void {
        $this->requireAuth(); $this->requireLevel(3, [...self::NODO_USUARIOS, 1]);
        require_once ROOT . '/libs/fpdf/fpdf.php';
        require_once ROOT . '/libs/ReportPdf.php';
        $u = $this->usuariosData($id)[0] ?? null;
        if (!$u) { http_response_code(404); die('Usuario no encontrado.'); }
        $secciones = [
            ['Identidad', [
                ['Usuario', $u['nombre_usuario'] ?? ''],
                ['Nombre completo', $u['nombre_completo'] ?? ''],
                ['Cédula', $u['cedula'] ?? ''],
                ['Correo', $u['correo'] ?? ''],
            ]],
            ['Acceso y permisos', [
                ['Departamento', $u['departamento'] ?? ''],
                ['Nivel jerárquico', $this->nivelLabel($u['nivel_jerarquia'] ?? 0)],
                ['Roles asignados', $u['roles'] ?? '(sin roles)'],
                ['Estado', (int)($u['estado'] ?? 0) === 1 ? 'Activo' : 'Inactivo'],
                ['Requiere MFA', (int)($u['requiere_mfa'] ?? 0) ? 'Sí' : 'No'],
                ['Requiere cambio de contraseña', (int)($u['requiere_cambio_pass'] ?? 0) ? 'Sí' : 'No'],
                ['Intentos fallidos', (string)($u['intentos_fallidos'] ?? 0)],
                ['Tema preferido', $u['tema_preferido'] ?? ''],
            ]],
            ['Fechas', [
                ['Creado', $this->fmtFechaHora($u['fecha_creacion'] ?? null)],
                ['Última modificación', $this->fmtFechaHora($u['fecha_modificacion'] ?? null) ?: '—'],
            ]],
        ];
        ReportPdf::ficha('Ficha de Usuario', ($u['nombre_completo'] ?? '') . ' · ' . ($u['nombre_usuario'] ?? ''),
            $secciones, 'usuario_' . preg_replace('/\W+/', '', (string)($u['nombre_usuario'] ?? $id)) . '.pdf');
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
            ? "AND (e.nombres LIKE ? OR e.apellidos LIKE ? OR e.cedula LIKE ?)"
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
            "SELECT e.empleado_id, e.cedula, e.nombres + ' ' + e.apellidos AS nombre_completo,
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
            "SELECT e.empleado_id, e.cedula, e.nombres + ' ' + e.apellidos AS nombre_completo,
                    e.correo_institucional, u.codigo_uorg, u.nombre_unidad
             FROM Talento_Humano.dbo.th_empleados e
             LEFT JOIN Talento_Humano.dbo.th_unidades_organizacionales u ON u.unidad_id = e.unidad_id
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

        $this->render('Central/admin/usuario_desde_empleado', [
            'pageTitle'  => 'Nueva cuenta — ' . $emp['nombre_completo'],
            'empleado'   => $emp,
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
            "SELECT e.cedula, e.correo_institucional
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

        // nombre_completo/cedula locales quedan como respaldo (por si el
        // vínculo se rompe); vw_Usuarios_Identidad prioriza el dato en vivo.
        // nombre_usuario = cédula: el login es únicamente por cédula, no se
        // maneja un "usuario" separado (columna se mantiene por compatibilidad
        // con sp_Login y el contrato SSO de TH/Bienes, pero es transparente).
        $db->query(
            'INSERT INTO CORE_Usuarios
                (nombre_usuario, nombre_completo, correo, cedula, hash_contrasena,
                 nivel_jerarquia, id_departamento, id_empleado_th, estado)
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

        $idUsuario = (int)$db->fetch($db->query('SELECT SCOPE_IDENTITY() AS id'))['id'];
        $db->query(
            'INSERT INTO CORE_Usuarios_Roles (id_usuario, id_rol, asignado_por) VALUES (?,?,?)',
            [
                [$idUsuario, SQLSRV_PARAM_IN],
                [(int)$_POST['id_rol'], SQLSRV_PARAM_IN],
                [(int)($_SESSION['user_id'] ?? 0), SQLSRV_PARAM_IN],
            ]
        );

        SessionHelper::flash('success', 'Cuenta creada y vinculada al empleado de Talento Humano.');
        $this->redirect('/admin/usuarios');
    }

    private function empleadoNombreCompleto(int $idEmpleadoTh): string {
        $r = $this->db()->fetch($this->db()->query(
            "SELECT nombres + ' ' + apellidos AS n FROM Talento_Humano.dbo.th_empleados WHERE empleado_id = ?",
            [[$idEmpleadoTh, SQLSRV_PARAM_IN]]
        ));
        return $r['n'] ?? '';
    }
}
