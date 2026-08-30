<?php
require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/BitacoraModel.php';

class InvNotaPedido extends Model
{
    /**
     * Vincula el área/departamento de Talento Humano con el grupo de centros y
     * al responsable con el centro interno requerido por notas y egresos.
     */
    public function obtenerOCrearCentroParaPersona(int $personaId, int $unidadId = 0): int
    {
        if ($personaId <= 0) {
            throw new InvalidArgumentException('Seleccione al responsable del centro de consumo.');
        }

        $personaStmt = $this->db->prepare(
            "SELECT empleado_id AS id, apellidos_nombres AS nombre, cedula AS identificacion,
                    direccion_area AS area, unidad_id
             FROM Talento_Humano.dbo.vw_th_directorio_empleados
             WHERE empleado_id = :id AND estado = 1"
        );
        $personaStmt->execute([':id' => $personaId]);
        $persona = $personaStmt->fetch();
        if (!$persona) {
            throw new InvalidArgumentException('La persona seleccionada no existe o ya no se encuentra activa.');
        }
        $unidadId = $unidadId > 0 ? $unidadId : (int)$persona['unidad_id'];
        if ($unidadId <= 0) {
            throw new InvalidArgumentException('Seleccione el área o departamento del centro de consumo.');
        }
        if ((int)$persona['unidad_id'] !== $unidadId) {
            throw new InvalidArgumentException('El responsable seleccionado no pertenece al centro de consumo indicado.');
        }

        $unidadStmt = $this->db->prepare(
            "SELECT unidad_id, codigo_uorg, nombre_unidad
             FROM Talento_Humano.dbo.vw_th_maestros_organizacionales
             WHERE unidad_id = :id AND activo = 1"
        );
        $unidadStmt->execute([':id' => $unidadId]);
        $unidad = $unidadStmt->fetch();
        if (!$unidad) {
            throw new InvalidArgumentException('El área o departamento seleccionado no está activo.');
        }

        $codigoGrupo = 'ORG-' . $unidadId;
        $grupoStmt = $this->db->prepare(
            "SELECT id FROM inv_grupo_centros_consumo
             WHERE codigo = :codigo OR nombre = :nombre
             ORDER BY CASE WHEN codigo = :codigo_orden THEN 0 ELSE 1 END, id"
        );
        $grupoStmt->execute([
            ':codigo' => $codigoGrupo,
            ':nombre' => $unidad['nombre_unidad'],
            ':codigo_orden' => $codigoGrupo,
        ]);
        $grupoId = (int)$grupoStmt->fetchColumn();
        if ($grupoId <= 0) {
            $crearGrupo = $this->db->prepare(
                "INSERT INTO inv_grupo_centros_consumo (codigo, nombre, representante, representante_id)
                 VALUES (:codigo, :nombre, :representante, :persona)"
            );
            $crearGrupo->execute([
                ':codigo' => $codigoGrupo,
                ':nombre' => $unidad['nombre_unidad'],
                ':representante' => $persona['nombre'],
                ':persona' => $personaId,
            ]);
            $grupoStmt->execute([
                ':codigo' => $codigoGrupo,
                ':nombre' => $unidad['nombre_unidad'],
                ':codigo_orden' => $codigoGrupo,
            ]);
            $grupoId = (int)$grupoStmt->fetchColumn();
        }

        $centroStmt = $this->db->prepare(
            "SELECT id FROM inv_centros_consumo
             WHERE funcionario_id = :persona OR codigo = :codigo
             ORDER BY id ASC"
        );
        $codigoCentro = 'PER-' . $personaId;
        $centroStmt->execute([':persona' => $personaId, ':codigo' => $codigoCentro]);
        $centroId = (int)$centroStmt->fetchColumn();
        if ($centroId > 0) {
            $actualizar = $this->db->prepare(
                "UPDATE inv_centros_consumo
                 SET grupo_id = :grupo, nombre = :nombre, funcionario = :funcionario, funcionario_id = :persona
                 WHERE id = :id"
            );
            $actualizar->execute([
                ':grupo' => $grupoId,
                ':nombre' => $persona['nombre'],
                ':funcionario' => $persona['nombre'],
                ':persona' => $personaId,
                ':id' => $centroId,
            ]);
            return $centroId;
        }

        $crearCentro = $this->db->prepare(
            "INSERT INTO inv_centros_consumo
                (grupo_id, codigo, nombre, funcionario, funcionario_id)
             VALUES
                (:grupo, :codigo, :nombre, :funcionario, :persona)"
        );
        $crearCentro->execute([
            ':grupo' => $grupoId,
            ':codigo' => $codigoCentro,
            ':nombre' => $persona['nombre'],
            ':funcionario' => $persona['nombre'],
            ':persona' => $personaId,
        ]);

        $centroStmt->execute([':persona' => $personaId, ':codigo' => $codigoCentro]);
        $centroId = (int)$centroStmt->fetchColumn();
        if ($centroId <= 0) {
            throw new RuntimeException('No fue posible vincular la persona con el centro de consumo.');
        }
        return $centroId;
    }

    public function obtenerTodos(array $filtros = []): array
    {
        $sql = "SELECT n.*, gcc.codigo AS centro_codigo, gcc.nombre AS centro_consumo,
                       sol.nombre AS solicitante,
                       COALESCE(rec.nombre, cen.nombre, cc.funcionario) AS receptor,
                       COUNT(d.id_detalle) AS total_productos,
                       SUM(d.cantidad_solicitada) AS total_solicitado,
                       SUM(d.cantidad_entregada) AS total_entregado
                FROM inv_notas_pedido n
                JOIN inv_centros_consumo cc ON cc.id = n.centro_consumo_id
                JOIN inv_grupo_centros_consumo gcc ON gcc.id = cc.grupo_id
                LEFT JOIN vw_inv_talento_personal sol ON sol.id = n.solicitante_id
                LEFT JOIN vw_inv_talento_personal rec ON rec.id = n.receptor_id
                LEFT JOIN vw_inv_talento_personal cen ON cen.id = cc.funcionario_id
                JOIN inv_notas_pedido_detalles d ON d.nota_id = n.id_nota
                WHERE 1 = 1";
        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= " AND n.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['termino'])) {
            $sql .= " AND (n.secuencial LIKE :termino OR gcc.nombre LIKE :termino OR cc.funcionario LIKE :termino
                       OR n.motivo LIKE :termino OR EXISTS (
                           SELECT 1
                           FROM inv_notas_pedido_detalles bus_det
                           JOIN inv_inventario bus_inv ON bus_inv.id = bus_det.item_id
                           LEFT JOIN inv_productos bus_prod ON bus_prod.id = bus_inv.producto_id
                           WHERE bus_det.nota_id = n.id_nota
                             AND (bus_inv.nombre LIKE :termino OR bus_inv.secuencial LIKE :termino OR bus_prod.codigo LIKE :termino)
                       ))";
            $params[':termino'] = '%' . trim($filtros['termino']) . '%';
        }

        $sql .= " GROUP BY n.id_nota, n.secuencial, n.centro_consumo_id, n.solicitante_id,
                         n.receptor_id, n.fecha_solicitud, n.motivo, n.observaciones,
                         n.tipo_bien, n.estado, n.grupo_solicitud, n.creado_por,
                         n.fecha_creacion, n.fecha_actualizacion, gcc.codigo, gcc.nombre,
                         cc.funcionario, sol.nombre, rec.nombre, cen.nombre
                  ORDER BY CASE WHEN n.estado IN ('ENVIADA','EN_REVISION','DISPONIBLE','PARCIAL','SIN_EXISTENCIAS') THEN 0 ELSE 1 END,
                           n.fecha_creacion DESC, n.id_nota DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Listado paginado para DataTables; evita cargar todas las requisiciones al abrir la vista. */
    public function requisicionesDataTable(array $peticion): array
    {
        $draw = max(0, (int)($peticion['draw'] ?? 0));
        $inicio = max(0, (int)($peticion['start'] ?? 0));
        $largo = min(100, max(10, (int)($peticion['length'] ?? 10)));
        $busqueda = trim((string)($peticion['search']['value'] ?? ''));
        $where = ['1=1']; $params = [];
        if ($busqueda !== '') {
            $where[] = '(n.secuencial LIKE :b1 OR gcc.nombre LIKE :b2 OR n.motivo LIKE :b3 OR cc.funcionario LIKE :b4 OR EXISTS (SELECT 1 FROM inv_notas_pedido_detalles bd JOIN inv_inventario bi ON bi.id=bd.item_id LEFT JOIN inv_productos bp ON bp.id=bi.producto_id WHERE bd.nota_id=n.id_nota AND (bi.nombre LIKE :b5 OR bi.secuencial LIKE :b6 OR bp.codigo LIKE :b7)))';
            foreach (range(1, 7) as $i) $params[':b'.$i] = '%'.$busqueda.'%';
        }
        $desde = trim((string)($peticion['fecha_desde'] ?? ''));
        $hasta = trim((string)($peticion['fecha_hasta'] ?? ''));
        if ($desde !== '') { $where[] = 'n.fecha_solicitud >= :desde'; $params[':desde'] = $desde; }
        if ($hasta !== '') { $where[] = 'n.fecha_solicitud <= :hasta'; $params[':hasta'] = $hasta; }
        $estado = strtoupper(trim((string)($peticion['estado'] ?? '')));
        if ($estado === 'PENDIENTE') $where[] = "n.estado IN ('ENVIADA','EN_REVISION','DISPONIBLE','PARCIAL','SIN_EXISTENCIAS','PENDIENTE')";
        elseif ($estado === 'ANULADA') $where[] = "n.estado IN ('CANCELADA','ANULADA')";
        elseif (in_array($estado, ['ATENDIDA','CERRADA'], true)) { $where[] = 'n.estado=:estado'; $params[':estado']=$estado; }
        $whereSql = implode(' AND ', $where);
        $from = ' FROM inv_notas_pedido n JOIN inv_centros_consumo cc ON cc.id=n.centro_consumo_id JOIN inv_grupo_centros_consumo gcc ON gcc.id=cc.grupo_id';
        $total = (int)$this->db->query('SELECT COUNT(*) FROM inv_notas_pedido')->fetchColumn();
        $conteo = $this->db->prepare('SELECT COUNT(*)'.$from.' WHERE '.$whereSql); $conteo->execute($params);
        $filtrados = (int)$conteo->fetchColumn();
        $columnas = ['n.secuencial','n.fecha_solicitud','gcc.nombre','n.motivo','n.id_nota','n.id_nota','n.id_nota','n.estado','n.id_nota'];
        $indice = (int)($peticion['order'][0]['column'] ?? 1); $orden = $columnas[$indice] ?? 'n.fecha_solicitud';
        $direccion = strtolower((string)($peticion['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $sql = "SELECT n.id_nota,n.secuencial,n.fecha_solicitud,n.motivo,n.estado,gcc.nombre centro_consumo,
                       (SELECT COUNT(*) FROM inv_notas_pedido_detalles d WHERE d.nota_id=n.id_nota) total_productos,
                       (SELECT COALESCE(SUM(d.cantidad_solicitada),0) FROM inv_notas_pedido_detalles d WHERE d.nota_id=n.id_nota) total_solicitado,
                       (SELECT COALESCE(SUM(d.cantidad_entregada),0) FROM inv_notas_pedido_detalles d WHERE d.nota_id=n.id_nota) total_entregado
                {$from} WHERE {$whereSql} ORDER BY {$orden} {$direccion},n.id_nota DESC OFFSET {$inicio} ROWS FETCH NEXT {$largo} ROWS ONLY";
        $stmt=$this->db->prepare($sql); $stmt->execute($params);
        return ['draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$filtrados,'data'=>$stmt->fetchAll()];
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT n.*, gcc.codigo AS centro_codigo, gcc.nombre AS centro_consumo,
                    sol.nombre AS solicitante,
                    COALESCE(rec.nombre, cen.nombre, cc.funcionario) AS receptor
             FROM inv_notas_pedido n
             JOIN inv_centros_consumo cc ON cc.id = n.centro_consumo_id
             JOIN inv_grupo_centros_consumo gcc ON gcc.id = cc.grupo_id
             LEFT JOIN vw_inv_talento_personal sol ON sol.id = n.solicitante_id
             LEFT JOIN vw_inv_talento_personal rec ON rec.id = n.receptor_id
             LEFT JOIN vw_inv_talento_personal cen ON cen.id = cc.funcionario_id
             WHERE n.id_nota = :id"
        );
        $stmt->execute([':id' => $id]);
        $nota = $stmt->fetch();
        if (!$nota) {
            return null;
        }

        $detalle = $this->db->prepare(
            "SELECT d.*, COALESCE(NULLIF(p.codigo, ''), i.secuencial) AS item_codigo,
                    COALESCE(NULLIF(p.codigo, ''), i.secuencial) AS item_secuencial, i.nombre AS item_nombre,
                    i.marca, i.cantidad AS stock_actual,
                    COALESCE(i.tipo_bien, p.tipo_bien, 'CC') AS tipo_bien,
                    (d.cantidad_solicitada - d.cantidad_entregada) AS cantidad_pendiente
             FROM inv_notas_pedido_detalles d
             JOIN inv_inventario i ON i.id = d.item_id
             LEFT JOIN inv_productos p ON p.id = i.producto_id
             WHERE d.nota_id = :id
             ORDER BY d.id_detalle"
        );
        $detalle->execute([':id' => $id]);
        $nota['detalles'] = $detalle->fetchAll();
        return $nota;
    }

    /**
     * Busca una nota de pedido por su secuencial visible. Acepta el código
     * completo (NPA-00024/NPE-00024) o únicamente su parte numérica.
     */
    public function buscarNotaPedidoPorNumero(string $numero): ?array
    {
        $numero = strtoupper(preg_replace('/\s+/', '', trim($numero)) ?? '');
        if ($numero === '') return null;
        $digitos = ctype_digit($numero) ? $numero : (preg_match('/(\d+)$/', $numero, $coincidencia) ? $coincidencia[1] : '');
        $numeroCanonico = $digitos !== '' ? str_pad(ltrim($digitos, '0') ?: '0', 5, '0', STR_PAD_LEFT) : '';
        if (preg_match('/^([A-Z]+)[-_]?0*\d+$/', $numero, $prefijo)) {
            $sufijo = $prefijo[1] . '%-' . $numeroCanonico;
        } else {
            $sufijo = $digitos !== '' ? '%-' . $numeroCanonico : $numero;
        }
        $limiteInicio = defined('DB_DRIVER') && DB_DRIVER === 'sqlsrv' ? 'TOP 1 ' : '';
        $limiteFin = defined('DB_DRIVER') && DB_DRIVER === 'sqlsrv' ? '' : ' LIMIT 1';

        if ($this->tablaExiste('inv_abast_notas_pedido')) {
            $stmt = $this->db->prepare(
                "SELECT {$limiteInicio}n.*
                 FROM inv_abast_notas_pedido n
                 WHERE UPPER(REPLACE(n.secuencial, ' ', '')) = :exacto OR UPPER(REPLACE(n.secuencial, ' ', '')) LIKE :sufijo
                 ORDER BY CASE WHEN n.secuencial = :orden_exacto THEN 0 ELSE 1 END, n.id_nota DESC{$limiteFin}"
            );
            $stmt->execute([':exacto' => $numero, ':sufijo' => $sufijo, ':orden_exacto' => $numero]);
            $nota = $stmt->fetch();
            if ($nota) {
                $detalle = $this->db->prepare(
                    "SELECT d.item_id, d.cantidad_solicitada,
                            COALESCE(NULLIF(p.codigo, ''), i.secuencial) AS item_codigo,
                            COALESCE(NULLIF(p.codigo, ''), i.secuencial) AS item_secuencial,
                            i.nombre AS item_nombre, i.cantidad AS existencia, i.valor AS precio_promedio,
                            COALESCE(p.aplica_iva, 1) AS aplica_iva,
                            c.codigo AS codigo_contable, c.nombre AS cuenta_contable, c.nombre AS grupo_nombre
                     FROM inv_abast_notas_pedido_detalles d
                     JOIN inv_inventario i ON i.id = d.item_id
                     LEFT JOIN inv_productos p ON p.id = i.producto_id
                     LEFT JOIN inv_categorias c ON c.id = i.categoria_id
                     WHERE d.nota_id = :id ORDER BY d.id_detalle"
                );
                $detalle->execute([':id' => $nota['id_nota']]);
                return [
                    'numero' => $nota['secuencial'],
                    'fecha' => $nota['fecha_solicitud'],
                    'solicitante' => $nota['solicitante'],
                    'solicitante_id' => null,
                    'centro_consumo' => $nota['area_solicitante'] ?: $nota['solicitante'],
                    'centro_persona_id' => null,
                    'centro_unidad_id' => $this->buscarUnidadOrganizacional('', (string)($nota['area_solicitante'] ?: $nota['solicitante'])),
                    'motivo' => $nota['observaciones'] ?? '',
                    'observaciones' => $nota['observaciones'] ?? '',
                    'estado' => $nota['estado'] ?? '',
                    'referencia' => $nota['observaciones'] ?? '',
                    'origen' => 'ABASTECIMIENTO',
                    'detalles' => $detalle->fetchAll(),
                ];
            }
        }

        $stmt = $this->db->prepare(
            "SELECT {$limiteInicio}n.*, gcc.nombre AS centro_consumo, gcc.codigo AS centro_grupo_codigo,
                    COALESCE(n.receptor_id, cc.funcionario_id) AS funcionario_id
             FROM inv_notas_pedido n
             JOIN inv_centros_consumo cc ON cc.id = n.centro_consumo_id
             JOIN inv_grupo_centros_consumo gcc ON gcc.id = cc.grupo_id
             WHERE UPPER(REPLACE(n.secuencial, ' ', '')) = :exacto OR UPPER(REPLACE(n.secuencial, ' ', '')) LIKE :sufijo
             ORDER BY CASE WHEN n.secuencial = :orden_exacto THEN 0 ELSE 1 END, n.id_nota DESC{$limiteFin}"
        );
        $stmt->execute([':exacto' => $numero, ':sufijo' => $sufijo, ':orden_exacto' => $numero]);
        $nota = $stmt->fetch();
        if (!$nota) return null;
        $detalle = $this->db->prepare(
            "SELECT d.item_id, d.cantidad_solicitada, d.observacion_bodega,
                    COALESCE(NULLIF(p.codigo, ''), i.secuencial) AS item_codigo,
                    COALESCE(NULLIF(p.codigo, ''), i.secuencial) AS item_secuencial, i.nombre AS item_nombre,
                    i.cantidad AS existencia, i.valor AS precio_promedio,
                    COALESCE(p.aplica_iva, 1) AS aplica_iva,
                    c.codigo AS codigo_contable, c.nombre AS cuenta_contable, c.nombre AS grupo_nombre
             FROM inv_notas_pedido_detalles d
             JOIN inv_inventario i ON i.id = d.item_id
             LEFT JOIN inv_productos p ON p.id = i.producto_id
             LEFT JOIN inv_categorias c ON c.id = i.categoria_id
             WHERE d.nota_id = :id ORDER BY d.id_detalle"
        );
        $detalle->execute([':id' => $nota['id_nota']]);
        return [
            'numero' => $nota['secuencial'],
            'fecha' => $nota['fecha_solicitud'],
            'solicitante_id' => (int)$nota['solicitante_id'],
            'solicitante' => '',
            'centro_consumo' => $nota['centro_consumo'],
            'centro_persona_id' => $nota['funcionario_id'] ? (int)$nota['funcionario_id'] : null,
            'centro_unidad_id' => $this->buscarUnidadOrganizacional(
                (string)$nota['centro_grupo_codigo'],
                (string)$nota['centro_consumo']
            ),
            'motivo' => $nota['motivo'] ?? '',
            'observaciones' => $nota['observaciones'] ?? '',
            'estado' => $nota['estado'] ?? '',
            'tipo_bien' => $nota['tipo_bien'] ?? '',
            'referencia' => $nota['observaciones'] ?? '',
            'origen' => 'REQUISICION',
            'detalles' => $detalle->fetchAll(),
        ];
    }

    private function tablaExiste(string $tabla): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = :tabla');
        $stmt->execute([':tabla' => $tabla]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function buscarUnidadOrganizacional(string $codigoGrupo, string $nombreGrupo): ?int
    {
        if (preg_match('/^ORG-(\d+)$/', $codigoGrupo, $coincidencia)) {
            return (int)$coincidencia[1];
        }
        $nombreGrupo = trim($nombreGrupo);
        if ($nombreGrupo === '') return null;
        try {
            $stmt = $this->db->prepare(
                "SELECT TOP 1 unidad_id
                 FROM Talento_Humano.dbo.vw_th_maestros_organizacionales
                 WHERE activo = 1 AND nombre_unidad = :nombre
                 ORDER BY unidad_id"
            );
            $stmt->execute([':nombre' => $nombreGrupo]);
            $unidadId = (int)$stmt->fetchColumn();
            return $unidadId > 0 ? $unidadId : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /** Búsqueda limitada para no enviar todo el inventario a la ventana. */
    public function buscarProductos(string $termino, int $limite = 20): array
    {
        $termino = trim($termino);
        if (mb_strlen($termino, 'UTF-8') < 2) return [];
        $limite = max(1, min(40, $limite));
        $inicio = defined('DB_DRIVER') && DB_DRIVER === 'sqlsrv' ? 'TOP ' . $limite . ' ' : '';
        $fin = defined('DB_DRIVER') && DB_DRIVER === 'sqlsrv' ? '' : ' LIMIT ' . $limite;
        $ignoradas = ['de','del','la','el','los','las','y','para','con','un','una'];
        $tokens = preg_split('/\s+/u', mb_strtolower($termino, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_values(array_filter($tokens, static fn(string $token): bool => mb_strlen($token, 'UTF-8') >= 2 && !in_array($token, $ignoradas, true)));
        if (!$tokens) $tokens = [$termino];
        $where = ['i.activo = 1']; $params = [':exacto'=>$termino, ':prefijo'=>$termino.'%'];
        $campos = [
            'i.secuencial','p.codigo','i.nombre COLLATE Modern_Spanish_CI_AI',
            'p.nombre COLLATE Modern_Spanish_CI_AI','i.marca COLLATE Modern_Spanish_CI_AI',
            'c.codigo','c.nombre COLLATE Modern_Spanish_CI_AI','u.nombre COLLATE Modern_Spanish_CI_AI',
            'u.extra COLLATE Modern_Spanish_CI_AI','i.tipo_bien'
        ];
        foreach (array_slice($tokens, 0, 6) as $tokenIndex=>$token) {
            $alternativas=[];
            foreach ($campos as $fieldIndex=>$campo) {
                $param=':t'.$tokenIndex.'_'.$fieldIndex;
                $alternativas[]=$campo.' LIKE '.$param; $params[$param]='%'.$token.'%';
            }
            $where[]='('.implode(' OR ',$alternativas).')';
        }
        $stmt = $this->db->prepare(
            "SELECT {$inicio}i.id, COALESCE(NULLIF(p.codigo, ''), NULLIF(c.codigo,''), i.secuencial) AS codigo,
                    i.secuencial, i.secuencial AS codigo_interno, c.codigo AS codigo_clasificacion,
                    p.codigo AS codigo_maestro, c.codigo AS codigo_contable,
                    i.nombre, COALESCE(NULLIF(p.nombre,''),i.nombre) AS nombre_maestro, i.marca,
                    i.cantidad AS existencia, i.valor AS precio_promedio,
                    c.nombre AS grupo_nombre, u.nombre AS unidad_nombre, u.extra AS unidad_abrev,
                    COALESCE(i.tipo_bien,p.tipo_bien,'CC') AS tipo_bien
             FROM inv_inventario i
             LEFT JOIN inv_productos p ON p.id = i.producto_id
             LEFT JOIN inv_categorias c ON c.id = i.categoria_id
             LEFT JOIN inv_unidades u ON u.id = p.unidad_id
             WHERE ".implode(' AND ',$where)."
             ORDER BY CASE WHEN p.codigo=:exacto OR i.secuencial=:exacto OR c.codigo=:exacto THEN 0
                           WHEN p.codigo LIKE :prefijo OR i.secuencial LIKE :prefijo OR c.codigo LIKE :prefijo THEN 1 ELSE 2 END,
                      i.nombre, i.id{$fin}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Crea una nota para consumo corriente y una nota por cada unidad de activo fijo.
     * Una solicitud mixta queda vinculada mediante grupo_solicitud.
     */
    public function crearSolicitud(array $datos, array $detalles): array
    {
        if (empty($datos['centro_consumo_id']) || empty($datos['solicitante_id']) || trim($datos['motivo'] ?? '') === '') {
            throw new InvalidArgumentException('Complete el centro de consumo, responsable y motivo.');
        }
        if (empty($detalles)) {
            throw new InvalidArgumentException('Agregue al menos un producto a la solicitud.');
        }

        $ids = [];
        foreach ($detalles as $detalle) {
            $itemId = (int)($detalle['item_id'] ?? 0);
            $cantidad = (int)($detalle['cantidad'] ?? 0);
            if ($itemId <= 0 || $cantidad <= 0) {
                throw new InvalidArgumentException('Todos los productos deben tener una cantidad válida.');
            }
            $referencia = trim((string)($detalle['referencia'] ?? ''));
            if (isset($ids[$itemId])) {
                $ids[$itemId]['cantidad'] += $cantidad;
                if ($referencia !== '') {
                    $referenciasActuales = array_filter(array_map('trim', explode(' || ', $ids[$itemId]['referencia'])));
                    if (!in_array($referencia, $referenciasActuales, true)) $referenciasActuales[] = $referencia;
                    $ids[$itemId]['referencia'] = implode(' || ', $referenciasActuales);
                }
            } else {
                $ids[$itemId] = ['cantidad' => $cantidad, 'referencia' => $referencia];
            }
        }

        try {
            $this->db->beginTransaction();
            $items = [];
            $bloqueo = defined('DB_DRIVER') && DB_DRIVER === 'sqlsrv' ? ' WITH (UPDLOCK, HOLDLOCK)' : '';
            $buscar = $this->db->prepare(
                "SELECT i.id, i.nombre, i.cantidad AS existencia,
                        COALESCE(NULLIF(p.codigo, ''), i.secuencial) AS codigo,
                        i.categoria_id, COALESCE(i.tipo_bien, p.tipo_bien, 'CC') AS tipo_bien
                 FROM inv_inventario i{$bloqueo}
                 LEFT JOIN inv_productos p ON p.id = i.producto_id
                 WHERE i.id = :id AND i.activo = 1"
            );
            foreach ($ids as $itemId => $solicitud) {
                $buscar->execute([':id' => $itemId]);
                $item = $buscar->fetch();
                if (!$item) {
                    throw new RuntimeException("El producto con ID {$itemId} no existe o está inactivo.");
                }
                if ((int)$item['existencia'] < (int)$solicitud['cantidad']) {
                    throw new RuntimeException(
                        "Stock insuficiente para {$item['codigo']} · {$item['nombre']}. " .
                        "Disponible: {$item['existencia']}; solicitado: {$solicitud['cantidad']}."
                    );
                }
                $item['cantidad_solicitada'] = (int)$solicitud['cantidad'];
                $item['referencia'] = $solicitud['referencia'];
                $items[] = $item;
            }

            $grupo = $this->crearGuid();
            $creadas = [];
            $consumo = array_values(array_filter($items, static function ($item) {
                return $item['tipo_bien'] !== 'AF';
            }));
            if ($consumo) {
                $creadas[] = $this->insertarNota($datos, 'CC', $grupo, $consumo);
            }

            foreach ($items as $item) {
                if ($item['tipo_bien'] !== 'AF') {
                    continue;
                }
                $unidades = $this->buscarUnidadesActivoFijo($item, (int)$item['cantidad_solicitada']);
                for ($unidad = 0; $unidad < (int)$item['cantidad_solicitada']; $unidad++) {
                    // Si aún no existe una unidad individual suficiente, la nota conserva
                    // el bien solicitado como pendiente hasta que Bodega tenga disponibilidad.
                    $detalleUnitario = $unidades[$unidad] ?? $item;
                    $detalleUnitario['cantidad_solicitada'] = 1;
                    $detalleUnitario['referencia'] = $item['referencia'] ?? '';
                    $creadas[] = $this->insertarNota($datos, 'AF', $grupo, [$detalleUnitario]);
                }
            }

            (new InvBitacora())->registrar(
                'CREAR',
                'bod',
                'Solicitud digital registrada. Notas generadas: ' . implode(', ', array_column($creadas, 'secuencial'))
            );
            $this->db->commit();
            return $creadas;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function marcarSinExistencias(int $notaId, string $usuario): void
    {
        $nota = $this->buscarPorId($notaId);
        if (!$nota) {
            throw new RuntimeException('La nota de pedido no existe.');
        }
        if (in_array($nota['estado'], ['ATENDIDA', 'CERRADA', 'CANCELADA'], true)) {
            throw new RuntimeException('La nota ya no admite revisión.');
        }
        foreach ($nota['detalles'] as $detalle) {
            if ((int)$detalle['cantidad_pendiente'] > 0 && (int)$detalle['stock_actual'] > 0) {
                throw new RuntimeException('La nota tiene productos disponibles; registre la entrega o ajuste las cantidades.');
            }
        }
        $stmt = $this->db->prepare(
            "UPDATE inv_notas_pedido
             SET estado = 'SIN_EXISTENCIAS', fecha_actualizacion = SYSDATETIME()
             WHERE id_nota = :id"
        );
        $stmt->execute([':id' => $notaId]);
        (new InvBitacora())->registrar('EDITAR', 'bod', "Nota {$nota['secuencial']} revisada sin existencias por {$usuario}.");
    }

    /** Anula una requisición sin eliminar su cabecera ni sus detalles históricos. */
    public function anular(int $notaId, string $motivo, string $usuario): void
    {
        $motivo = trim($motivo);
        if ($notaId <= 0) throw new InvalidArgumentException('La requisición no es válida.');
        if ($motivo === '') throw new InvalidArgumentException('Indique el motivo de la anulación.');
        $nota = $this->buscarPorId($notaId);
        if (!$nota) throw new RuntimeException('La requisición no existe.');
        if (in_array($nota['estado'], ['ATENDIDA', 'CERRADA', 'CANCELADA'], true)) {
            throw new RuntimeException($nota['estado'] === 'CANCELADA'
                ? 'La requisición ya se encuentra anulada.'
                : 'Una requisición atendida o cerrada no puede anularse.');
        }
        if (array_sum(array_map(static fn(array $detalle): int => (int)$detalle['cantidad_entregada'], $nota['detalles'])) > 0) {
            throw new RuntimeException('La requisición ya tiene entregas registradas y no puede anularse directamente.');
        }
        $observacion = trim((string)($nota['observaciones'] ?? ''));
        $marca = 'Anulada por ' . $usuario . ' el ' . date('Y-m-d H:i:s') . '. Motivo: ' . $motivo;
        $observacion = $observacion === '' ? $marca : $observacion . ' | ' . $marca;
        $stmt = $this->db->prepare(
            "UPDATE inv_notas_pedido
             SET estado = 'CANCELADA', observaciones = :observaciones, fecha_actualizacion = SYSDATETIME()
             WHERE id_nota = :id AND estado NOT IN ('ATENDIDA','CERRADA','CANCELADA')"
        );
        $stmt->execute([':observaciones' => $observacion, ':id' => $notaId]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('La requisición cambió de estado antes de ser anulada.');
        (new InvBitacora())->registrar('ANULAR', 'bod', "Requisición {$nota['secuencial']} anulada por {$usuario}. Motivo: {$motivo}");
    }

    private function insertarNota(array $datos, string $tipoBien, string $grupo, array $detalles): array
    {
        $secuencial = (new InvSecuencial())->generarSiguiente('npe');
        $stmt = $this->db->prepare(
            "INSERT INTO inv_notas_pedido
                (secuencial, centro_consumo_id, solicitante_id, receptor_id, fecha_solicitud, motivo,
                 observaciones, tipo_bien, estado, grupo_solicitud, creado_por)
             VALUES
                (:secuencial, :centro, :solicitante, :receptor, :fecha, :motivo,
                 :observaciones, :tipo_bien, 'ENVIADA', :grupo, :creado_por)"
        );
        $stmt->execute([
            ':secuencial' => $secuencial,
            ':centro' => (int)$datos['centro_consumo_id'],
            ':solicitante' => (int)$datos['solicitante_id'],
            ':receptor' => (int)($datos['responsable_id'] ?? 0) ?: null,
            ':fecha' => $datos['fecha_solicitud'] ?? date('Y-m-d'),
            ':motivo' => trim($datos['motivo']),
            ':observaciones' => trim($datos['observaciones'] ?? ''),
            ':tipo_bien' => $tipoBien,
            ':grupo' => $grupo,
            ':creado_por' => $datos['creado_por'] ?? 'Sistema',
        ]);
        $notaId = (int)$this->db->lastInsertId();

        $insertarDetalle = $this->db->prepare(
            "INSERT INTO inv_notas_pedido_detalles (nota_id, item_id, cantidad_solicitada, cantidad_entregada, observacion_bodega)
             VALUES (:nota, :item, :cantidad, 0, :referencia)"
        );
        foreach ($detalles as $detalle) {
            $insertarDetalle->execute([
                ':nota' => $notaId,
                ':item' => (int)$detalle['id'],
                ':cantidad' => (int)$detalle['cantidad_solicitada'],
                ':referencia' => trim((string)($detalle['referencia'] ?? '')),
            ]);
        }
        return ['id_nota' => $notaId, 'secuencial' => $secuencial, 'tipo_bien' => $tipoBien];
    }

    private function buscarUnidadesActivoFijo(array $item, int $cantidad): array
    {
        $cantidad = max(1, $cantidad);
        $limiteInicio = DB_DRIVER === 'sqlsrv' ? 'TOP ' . $cantidad . ' ' : '';
        $limiteFin = DB_DRIVER === 'sqlsrv' ? '' : ' LIMIT ' . $cantidad;
        $stmt = $this->db->prepare(
            "SELECT {$limiteInicio}i.id, i.nombre, i.categoria_id, 'AF' AS tipo_bien
             FROM inv_inventario i
             WHERE i.activo = 1 AND i.tipo_bien = 'AF' AND i.cantidad > 0
               AND i.categoria_id = :categoria AND LOWER(i.nombre) = LOWER(:nombre)
             ORDER BY CASE WHEN i.id = :preferido THEN 0 ELSE 1 END, i.id
             {$limiteFin}"
        );
        $stmt->execute([
            ':categoria' => (int)$item['categoria_id'],
            ':nombre' => $item['nombre'],
            ':preferido' => (int)$item['id'],
        ]);
        return $stmt->fetchAll();
    }

    private function crearGuid(): string
    {
        $datos = random_bytes(16);
        $datos[6] = chr((ord($datos[6]) & 0x0f) | 0x40);
        $datos[8] = chr((ord($datos[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($datos), 4));
    }
}
