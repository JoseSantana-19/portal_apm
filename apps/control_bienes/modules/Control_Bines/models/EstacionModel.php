<?php
require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';

class EstacionModel extends Model {
    // Lista de tablas permitidas para prevenir inyección SQL en nombres de tablas
    private $tablasValidas = [
        'categorias' => 'inv_categorias',
        'zonas' => 'inv_zonas',
        'estados' => 'inv_estados',
        'marcas' => 'inv_marcas',
        'lineas' => 'inv_lineas',
        'unidades' => 'inv_unidades',
        'tipos_iva' => 'inv_tipos_iva',
        'productos' => 'inv_productos',
        'proveedores' => 'inv_proveedores',
        'grupo_centros_consumo' => 'inv_grupo_centros_consumo',
        'centros_consumo' => 'inv_centros_consumo'
    ];

    public function __construct() {
        parent::__construct();
        $this->migrarResponsablesCentros();
    }

    /** Mantiene los textos históricos y agrega el enlace oficial al personal. */
    private function migrarResponsablesCentros(): void {
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        try {
            if ($driver === 'sqlsrv') {
                $this->db->exec("IF COL_LENGTH('dbo.inv_grupo_centros_consumo','representante_id') IS NULL ALTER TABLE dbo.inv_grupo_centros_consumo ADD representante_id INT NULL");
                $this->db->exec("IF COL_LENGTH('dbo.inv_centros_consumo','funcionario_id') IS NULL ALTER TABLE dbo.inv_centros_consumo ADD funcionario_id INT NULL");
            } elseif ($driver === 'pgsql') {
                $this->db->exec("ALTER TABLE inv_grupo_centros_consumo ADD COLUMN IF NOT EXISTS representante_id INT NULL");
                $this->db->exec("ALTER TABLE inv_centros_consumo ADD COLUMN IF NOT EXISTS funcionario_id INT NULL");
            } else {
                $grupoCols = $this->db->query("PRAGMA table_info(inv_grupo_centros_consumo)")->fetchAll(PDO::FETCH_COLUMN, 1);
                $centroCols = $this->db->query("PRAGMA table_info(inv_centros_consumo)")->fetchAll(PDO::FETCH_COLUMN, 1);
                if (!in_array('representante_id', $grupoCols, true)) $this->db->exec("ALTER TABLE inv_grupo_centros_consumo ADD COLUMN representante_id INTEGER NULL");
                if (!in_array('funcionario_id', $centroCols, true)) $this->db->exec("ALTER TABLE inv_centros_consumo ADD COLUMN funcionario_id INTEGER NULL");
            }
        } catch (Exception $e) {
            // La migración SQL formal agrega también las claves foráneas.
        }
    }


    private function getNombreTablaReal($tablaKey) {
        if (!isset($this->tablasValidas[$tablaKey])) {
            throw new InvalidArgumentException("Tabla maestra no válida: " . $tablaKey);
        }
        return $this->tablasValidas[$tablaKey];
    }

    public function obtenerTodos($tablaKey, ?string $tipoBien = null, bool $modoMaestros = false) {
        $tabla = $this->getNombreTablaReal($tablaKey);
        if ($tablaKey === 'categorias' && in_array($tipoBien, ['CC', 'AF'], true)) {
            $prefijo = $tipoBien === 'AF' ? '1.4.%' : '1.3.%';
            $stmt = $this->db->prepare("SELECT *, :tipo_bien AS tipo_bien
                                        FROM inv_categorias
                                        WHERE codigo LIKE :prefijo
                                        ORDER BY codigo ASC, id ASC");
            $stmt->execute([':tipo_bien' => $tipoBien, ':prefijo' => $prefijo]);
            return $stmt->fetchAll();
        } elseif ($tablaKey === 'productos' && $tipoBien === 'AF') {
            $sql = "SELECT MIN(i.id) AS id, MIN(i.secuencial) AS codigo,
                           MIN(i.nombre) AS nombre, i.categoria_id AS grupo_id,
                           c.nombre AS grupo_nombre,
                           'Unidad individual' AS unidad_nombre, 0 AS aplica_iva,
                           MIN(i.marca) AS extra, i.tipo_bien,
                           COUNT(*) AS unidades_registradas, 1 AS solo_lectura
                    FROM inv_inventario i
                    JOIN inv_categorias c ON c.id = i.categoria_id
                    WHERE i.tipo_bien = 'AF'
                    GROUP BY UPPER(LTRIM(RTRIM(i.nombre))), i.categoria_id,
                             c.nombre, i.tipo_bien
                    ORDER BY MIN(i.nombre), MIN(i.id)";
            return $this->db->query($sql)->fetchAll();
        } elseif ($tablaKey === 'productos') {
            $sql = "SELECT p.*, g.nombre as grupo_nombre, u.nombre as unidad_nombre 
                    FROM inv_productos p
                    JOIN inv_categorias g ON p.grupo_id = g.id
                    JOIN inv_unidades u ON p.unidad_id = u.id
                    WHERE (p.tipo_bien = 'CC' OR p.tipo_bien IS NULL)
                      AND g.codigo LIKE '1.3.%'
                    ORDER BY p.id ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } elseif ($tablaKey === 'grupo_centros_consumo' && $modoMaestros) {
            $sql = "SELECT unidad_id AS id, codigo_uorg AS codigo,
                           nombre_unidad AS nombre,
                           COALESCE(direccion_padre, 'Estructura principal') AS representante,
                           tipo_proceso AS extra, 1 AS solo_lectura
                    FROM Talento_Humano.dbo.vw_th_maestros_organizacionales
                    WHERE activo = 1
                    ORDER BY nombre_unidad ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } elseif ($tablaKey === 'grupo_centros_consumo') {
            $sql = "SELECT gcc.*,
                           COALESCE(NULLIF(p.nombre, ''), gcc.representante) AS representante,
                           p.identificacion AS representante_identificacion
                    FROM inv_grupo_centros_consumo gcc
                    LEFT JOIN vw_inv_talento_personal p ON p.id = gcc.representante_id
                    ORDER BY gcc.id ASC";
            return $this->db->query($sql)->fetchAll();
        } elseif ($tablaKey === 'centros_consumo' && $modoMaestros) {
            $sql = "SELECT empleado_id AS id, cedula AS codigo,
                           apellidos_nombres AS nombre,
                           apellidos_nombres AS funcionario,
                           unidad_id AS grupo_id,
                           direccion_area AS grupo_nombre,
                           cargo AS extra, correo_institucional,
                           1 AS solo_lectura
                    FROM Talento_Humano.dbo.vw_th_directorio_empleados
                    WHERE estado = 1
                    ORDER BY apellidos, nombres";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } elseif ($tablaKey === 'centros_consumo') {
            $sql = "SELECT cc.*, gcc.nombre AS grupo_nombre,
                           COALESCE(NULLIF(p.nombre, ''), cc.funcionario) AS funcionario,
                           p.identificacion AS funcionario_identificacion
                    FROM inv_centros_consumo cc
                    JOIN inv_grupo_centros_consumo gcc ON cc.grupo_id = gcc.id
                    LEFT JOIN vw_inv_talento_personal p ON p.id = cc.funcionario_id
                    ORDER BY cc.id ASC";
            return $this->db->query($sql)->fetchAll();
        } elseif ($tablaKey === 'estados') {
            $sql = "SELECT idestado as id, descripcion as nombre, detalle as extra, estado FROM inv_estados ORDER BY idestado ASC";
            $stmt = $this->db->query($sql);
            $results = [];
            foreach ($stmt->fetchAll() as $row) {
                $desc = mb_strtoupper($row['nombre'] ?? '', 'UTF-8');
                $clase = 'inactive';
                if (in_array($desc, ['APROBADO', 'AUTORIZADO', 'VIGENTE', 'VERIFICADO', 'ATENDIDO', 'ACEPTADO', 'CORRECTO', 'FAVORABLE', 'REVISADO', 'OPERATIVO', 'TODOS', 'REGISTRADO'])) {
                    $clase = 'active';
                } elseif (in_array($desc, ['EN TRAMITE', 'SOLICITADO', 'PENDIENTE', 'EN MANTENIMIENTO', 'EN TRANSITO'])) {
                    $clase = 'pending';
                } elseif ($desc === 'DESPACHADO') {
                    $clase = 'dispatched';
                }
                $row['clase'] = $clase;
                $results[] = $row;
            }
            return $results;
        } else {
            $stmt = $this->db->query("SELECT * FROM {$tabla} ORDER BY id ASC");
            return $stmt->fetchAll();
        }
    }

    /** Página optimizada para las listas grandes de Maestros. */
    public function obtenerPaginaMaestros(string $tablaKey, string $tipoBien, string $busqueda, int $pagina, int $porPagina): array {
        $pagina = max(1, $pagina);
        $porPagina = in_array($porPagina, [25, 50, 100], true) ? $porPagina : 50;
        $offset = ($pagina - 1) * $porPagina;
        $termino = trim($busqueda);
        $params = [];
        $filtro = '';

        if ($tablaKey === 'productos' && $tipoBien === 'AF') {
            if ($termino !== '') {
                $filtro = " AND (i.nombre LIKE :termino OR i.secuencial LIKE :termino OR c.nombre LIKE :termino)";
                $params[':termino'] = '%' . $termino . '%';
            }
            $base = "FROM inv_inventario i
                     JOIN inv_categorias c ON c.id = i.categoria_id
                     WHERE i.tipo_bien = 'AF'{$filtro}
                     GROUP BY UPPER(LTRIM(RTRIM(i.nombre))), i.categoria_id, c.nombre, i.tipo_bien";
            $sqlTotal = "SELECT COUNT(*) FROM (SELECT MIN(i.id) AS id {$base}) agrupados";
            $sqlDatos = "SELECT MIN(i.id) AS id, MIN(i.secuencial) AS codigo,
                                MIN(i.nombre) AS nombre, i.categoria_id AS grupo_id,
                                c.nombre AS grupo_nombre, 'Unidad individual' AS unidad_nombre,
                                0 AS aplica_iva, MIN(i.marca) AS extra, i.tipo_bien,
                                COUNT(*) AS unidades_registradas, 1 AS solo_lectura
                         {$base}
                         ORDER BY MIN(i.nombre), MIN(i.id)
                         OFFSET {$offset} ROWS FETCH NEXT {$porPagina} ROWS ONLY";
        } elseif ($tablaKey === 'productos') {
            if ($termino !== '') {
                $filtro = " AND (p.nombre LIKE :termino OR p.codigo LIKE :termino OR g.nombre LIKE :termino)";
                $params[':termino'] = '%' . $termino . '%';
            }
            $base = "FROM inv_productos p
                     JOIN inv_categorias g ON p.grupo_id = g.id
                     JOIN inv_unidades u ON p.unidad_id = u.id
                     WHERE (p.tipo_bien = 'CC' OR p.tipo_bien IS NULL)
                       AND g.codigo LIKE '1.3.%'{$filtro}";
            $sqlTotal = "SELECT COUNT(*) {$base}";
            $sqlDatos = "SELECT p.*, g.nombre AS grupo_nombre, u.nombre AS unidad_nombre
                         {$base}
                         ORDER BY p.id
                         OFFSET {$offset} ROWS FETCH NEXT {$porPagina} ROWS ONLY";
        } elseif ($tablaKey === 'centros_consumo') {
            if ($termino !== '') {
                $filtro = " AND (cedula LIKE :termino OR apellidos_nombres LIKE :termino OR cargo LIKE :termino OR direccion_area LIKE :termino)";
                $params[':termino'] = '%' . $termino . '%';
            }
            $base = "FROM Talento_Humano.dbo.vw_th_directorio_empleados WHERE estado = 1{$filtro}";
            $sqlTotal = "SELECT COUNT(*) {$base}";
            $sqlDatos = "SELECT empleado_id AS id, cedula AS codigo,
                                apellidos_nombres AS nombre, apellidos_nombres AS funcionario,
                                unidad_id AS grupo_id, direccion_area AS grupo_nombre,
                                cargo AS extra, correo_institucional, 1 AS solo_lectura
                         {$base}
                         ORDER BY apellidos, nombres
                         OFFSET {$offset} ROWS FETCH NEXT {$porPagina} ROWS ONLY";
        } else {
            $items = $this->obtenerTodos($tablaKey, $tipoBien, true);
            return ['items' => $items, 'total' => count($items), 'pagina' => 1, 'por_pagina' => count($items), 'total_paginas' => 1];
        }

        $stmtTotal = $this->db->prepare($sqlTotal);
        $stmtTotal->execute($params);
        $total = (int)$stmtTotal->fetchColumn();
        $totalPaginas = max(1, (int)ceil($total / $porPagina));
        if ($pagina > $totalPaginas) {
            return $this->obtenerPaginaMaestros($tablaKey, $tipoBien, $busqueda, $totalPaginas, $porPagina);
        }

        $stmtDatos = $this->db->prepare($sqlDatos);
        $stmtDatos->execute($params);
        return [
            'items' => $stmtDatos->fetchAll(),
            'total' => $total,
            'pagina' => $pagina,
            'por_pagina' => $porPagina,
            'total_paginas' => $totalPaginas,
        ];
    }

    public function buscarPorId($tablaKey, $id) {
        $tabla = $this->getNombreTablaReal($tablaKey);
        if ($tablaKey === 'estados') {
            $stmt = $this->db->prepare("SELECT idestado as id, descripcion as nombre, detalle as extra, estado FROM inv_estados WHERE idestado = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            if ($row) {
                $desc = mb_strtoupper($row['nombre'] ?? '', 'UTF-8');
                $clase = 'inactive';
                if (in_array($desc, ['APROBADO', 'AUTORIZADO', 'VIGENTE', 'VERIFICADO', 'ATENDIDO', 'ACEPTADO', 'CORRECTO', 'FAVORABLE', 'REVISADO', 'OPERATIVO', 'TODOS', 'REGISTRADO'])) {
                    $clase = 'active';
                } elseif (in_array($desc, ['EN TRAMITE', 'SOLICITADO', 'PENDIENTE', 'EN MANTENIMIENTO', 'EN TRANSITO'])) {
                    $clase = 'pending';
                } elseif ($desc === 'DESPACHADO') {
                    $clase = 'dispatched';
                }
                $row['clase'] = $clase;
            }
            return $row;
        }
        if ($tablaKey === 'productos') {
            $stmt = $this->db->prepare(
                "SELECT p.*, g.nombre AS grupo_nombre, u.nombre AS unidad_nombre
                 FROM inv_productos p
                 JOIN inv_categorias g ON g.id = p.grupo_id
                 JOIN inv_unidades u ON u.id = p.unidad_id
                 WHERE p.id = :id"
            );
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        }
        $stmt = $this->db->prepare("SELECT * FROM {$tabla} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function crear($tablaKey, $datos) {
        $tabla = $this->getNombreTablaReal($tablaKey);
        
        if ($tablaKey === 'estados') {
            $queryMax = $this->db->query("SELECT MAX(idestado) FROM inv_estados WHERE idestado >= 111 AND idestado <= 120");
            $maxId = $queryMax->fetchColumn();
            $newId = $maxId ? (int)$maxId + 1 : 111;
            
            if ($newId > 120) {
                throw new Exception("Límite de estados para el módulo de Inventario alcanzado (111-120).");
            }
            
            $stmt = $this->db->prepare("INSERT INTO inv_estados (idestado, descripcion, detalle, estado) VALUES (:idestado, :descripcion, :detalle, 1)");
            $stmt->execute([
                ':idestado' => $newId,
                ':descripcion' => $datos['nombre'],
                ':detalle' => isset($datos['extra']) ? $datos['extra'] : ''
            ]);
            return $this->buscarPorId($tablaKey, $newId);
        } elseif ($tablaKey === 'categorias') {
            $stmt = $this->db->prepare("INSERT INTO {$tabla} (nombre, codigo, extra) VALUES (:nombre, :codigo, :extra)");
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':codigo' => isset($datos['codigo']) ? $datos['codigo'] : '',
                ':extra' => isset($datos['extra']) ? $datos['extra'] : ''
            ]);
        } elseif ($tablaKey === 'productos') {
            // Auto-generar código secuencial si no tiene
            $nuevo = (new InvSecuencial())->generarNumero('itm');
            $codigo = str_pad($nuevo, 6, '0', STR_PAD_LEFT);

            $stmt = $this->db->prepare("INSERT INTO {$tabla} (nombre, grupo_id, unidad_id, aplica_iva, codigo) VALUES (:nombre, :grupo_id, :unidad_id, :aplica_iva, :codigo)");
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':grupo_id' => (int)$datos['grupo_id'],
                ':unidad_id' => (int)$datos['unidad_id'],
                ':aplica_iva' => isset($datos['aplica_iva']) ? (int)$datos['aplica_iva'] : 1,
                ':codigo' => $codigo
            ]);
            $newId = (int)$this->db->lastInsertId();

            require_once __DIR__ . '/InvItemSistema.php';
            InvItemSistema::syncProductToInventory($this->db, $newId, $datos['nombre'], (int)$datos['grupo_id'], (int)($datos['aplica_iva'] ?? 1));
        } elseif ($tablaKey === 'tipos_iva') {
            $stmt = $this->db->prepare("INSERT INTO {$tabla} (nombre, tasa_iva) VALUES (:nombre, :tasa_iva)");
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':tasa_iva' => (float)$datos['tasa_iva']
            ]);
        } elseif ($tablaKey === 'proveedores') {
            $codigo = trim((string)($datos['codigo'] ?? ''));
            if ($codigo === '') {
                $siguiente = (int)$this->db->query("SELECT COALESCE(MAX(id), 0) + 1 FROM inv_proveedores")->fetchColumn();
                $codigo = 'PRV-' . str_pad((string)$siguiente, 5, '0', STR_PAD_LEFT);
            }
            $stmt = $this->db->prepare("INSERT INTO {$tabla}
                (codigo, nombre, ruc, representante, direccion, ciudad, email, telefono1, telefono2, fax, referencia, extra)
                VALUES (:codigo, :nombre, :ruc, :representante, :direccion, :ciudad, :email, :telefono1, :telefono2, :fax, :referencia, :extra)");
            $stmt->execute([
                ':codigo' => $codigo,
                ':nombre' => $datos['nombre'],
                ':ruc' => $datos['ruc'],
                ':representante' => $datos['representante'] ?? '',
                ':direccion' => $datos['direccion'] ?? '',
                ':ciudad' => $datos['ciudad'] ?? '',
                ':email' => $datos['email'] ?? '',
                ':telefono1' => $datos['telefono1'] ?? '',
                ':telefono2' => $datos['telefono2'] ?? '',
                ':fax' => $datos['fax'] ?? '',
                ':referencia' => $datos['referencia'] ?? '',
                ':extra' => isset($datos['extra']) ? $datos['extra'] : ''
            ]);
        } elseif ($tablaKey === 'grupo_centros_consumo') {
            $stmt = $this->db->prepare("INSERT INTO {$tabla} (codigo, nombre, representante, representante_id) VALUES (:codigo, :nombre, :representante, :representante_id)");
            $stmt->execute([
                ':codigo' => $datos['codigo'],
                ':nombre' => $datos['nombre'],
                ':representante' => isset($datos['representante']) ? $datos['representante'] : '',
                ':representante_id' => !empty($datos['representante_id']) ? (int)$datos['representante_id'] : null
            ]);
        } elseif ($tablaKey === 'centros_consumo') {
            $stmt = $this->db->prepare("INSERT INTO {$tabla} (grupo_id, codigo, nombre, funcionario, funcionario_id) VALUES (:grupo_id, :codigo, :nombre, :funcionario, :funcionario_id)");
            $stmt->execute([
                ':grupo_id' => (int)$datos['grupo_id'],
                ':codigo' => $datos['codigo'],
                ':nombre' => $datos['nombre'],
                ':funcionario' => isset($datos['funcionario']) ? $datos['funcionario'] : '',
                ':funcionario_id' => !empty($datos['funcionario_id']) ? (int)$datos['funcionario_id'] : null
            ]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO {$tabla} (nombre, extra) VALUES (:nombre, :extra)");
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':extra' => isset($datos['extra']) ? $datos['extra'] : ''
            ]);
        }
        
        return $this->buscarPorId($tablaKey, $this->db->lastInsertId());
    }

    public function actualizar($tablaKey, $id, $datos) {
        $tabla = $this->getNombreTablaReal($tablaKey);
        
        if ($tablaKey === 'estados') {
            $stmt = $this->db->prepare("UPDATE inv_estados SET descripcion = :descripcion, detalle = :detalle WHERE idestado = :idestado");
            $stmt->execute([
                ':descripcion' => $datos['nombre'],
                ':detalle' => isset($datos['extra']) ? $datos['extra'] : '',
                ':idestado' => $id
            ]);
            return $this->buscarPorId($tablaKey, $id);
        } elseif ($tablaKey === 'categorias') {
            $stmt = $this->db->prepare("UPDATE {$tabla} SET nombre = :nombre, codigo = :codigo, extra = :extra WHERE id = :id");
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':codigo' => isset($datos['codigo']) ? $datos['codigo'] : '',
                ':extra' => isset($datos['extra']) ? $datos['extra'] : '',
                ':id' => $id
            ]);
        } elseif ($tablaKey === 'productos') {
            $stmt = $this->db->prepare("UPDATE {$tabla} SET nombre = :nombre, grupo_id = :grupo_id, unidad_id = :unidad_id, aplica_iva = :aplica_iva WHERE id = :id");
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':grupo_id' => (int)$datos['grupo_id'],
                ':unidad_id' => (int)$datos['unidad_id'],
                ':aplica_iva' => isset($datos['aplica_iva']) ? (int)$datos['aplica_iva'] : 1,
                ':id' => $id
            ]);

            require_once __DIR__ . '/InvItemSistema.php';
            InvItemSistema::syncProductToInventory($this->db, $id, $datos['nombre'], (int)$datos['grupo_id'], (int)($datos['aplica_iva'] ?? 1));
        } elseif ($tablaKey === 'tipos_iva') {
            $stmt = $this->db->prepare("UPDATE {$tabla} SET nombre = :nombre, tasa_iva = :tasa_iva WHERE id = :id");
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':tasa_iva' => (float)$datos['tasa_iva'],
                ':id' => $id
            ]);
        } elseif ($tablaKey === 'proveedores') {
            $stmt = $this->db->prepare("UPDATE {$tabla} SET codigo = :codigo, nombre = :nombre, ruc = :ruc,
                representante = :representante, direccion = :direccion, ciudad = :ciudad, email = :email,
                telefono1 = :telefono1, telefono2 = :telefono2, fax = :fax, referencia = :referencia, extra = :extra
                WHERE id = :id");
            $stmt->execute([
                ':codigo' => trim((string)($datos['codigo'] ?? '')) ?: 'PRV-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT),
                ':nombre' => $datos['nombre'],
                ':ruc' => $datos['ruc'],
                ':representante' => $datos['representante'] ?? '',
                ':direccion' => $datos['direccion'] ?? '',
                ':ciudad' => $datos['ciudad'] ?? '',
                ':email' => $datos['email'] ?? '',
                ':telefono1' => $datos['telefono1'] ?? '',
                ':telefono2' => $datos['telefono2'] ?? '',
                ':fax' => $datos['fax'] ?? '',
                ':referencia' => $datos['referencia'] ?? '',
                ':extra' => isset($datos['extra']) ? $datos['extra'] : '',
                ':id' => $id
            ]);
        } elseif ($tablaKey === 'grupo_centros_consumo') {
            $stmt = $this->db->prepare("UPDATE {$tabla} SET codigo = :codigo, nombre = :nombre, representante = :representante, representante_id = :representante_id WHERE id = :id");
            $stmt->execute([
                ':codigo' => $datos['codigo'],
                ':nombre' => $datos['nombre'],
                ':representante' => isset($datos['representante']) ? $datos['representante'] : '',
                ':representante_id' => !empty($datos['representante_id']) ? (int)$datos['representante_id'] : null,
                ':id' => $id
            ]);
        } elseif ($tablaKey === 'centros_consumo') {
            $stmt = $this->db->prepare("UPDATE {$tabla} SET grupo_id = :grupo_id, codigo = :codigo, nombre = :nombre, funcionario = :funcionario, funcionario_id = :funcionario_id WHERE id = :id");
            $stmt->execute([
                ':grupo_id' => (int)$datos['grupo_id'],
                ':codigo' => $datos['codigo'],
                ':nombre' => $datos['nombre'],
                ':funcionario' => isset($datos['funcionario']) ? $datos['funcionario'] : '',
                ':funcionario_id' => !empty($datos['funcionario_id']) ? (int)$datos['funcionario_id'] : null,
                ':id' => $id
            ]);
        } else {
            $stmt = $this->db->prepare("UPDATE {$tabla} SET nombre = :nombre, extra = :extra WHERE id = :id");
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':extra' => isset($datos['extra']) ? $datos['extra'] : '',
                ':id' => $id
            ]);
        }
        
        return $this->buscarPorId($tablaKey, $id);
    }

    public function eliminar($tablaKey, $id) {
        $tabla = $this->getNombreTablaReal($tablaKey);
        if ($tablaKey === 'estados') {
            $stmt = $this->db->prepare("DELETE FROM inv_estados WHERE idestado = :id");
            return $stmt->execute([':id' => $id]);
        }
        $stmt = $this->db->prepare("DELETE FROM {$tabla} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Obtiene los conteos totales de todas las tablas maestras
     */
    public function obtenerConteos(bool $modoMaestros = false) {
        $conteos = [];
        foreach ($this->tablasValidas as $key => $table) {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM {$table}");
            $res = $stmt->fetch();
            $conteos[$key] = (int)$res['total'];
        }
        $conteos['categorias_cc'] = (int)$this->db->query("SELECT COUNT(*) FROM inv_categorias WHERE codigo LIKE '1.3.%'")->fetchColumn();
        $conteos['categorias_af'] = (int)$this->db->query("SELECT COUNT(*) FROM inv_categorias WHERE codigo LIKE '1.4.%'")->fetchColumn();
        $conteos['productos_cc'] = (int)$this->db->query("SELECT COUNT(*) FROM inv_productos p JOIN inv_categorias c ON c.id=p.grupo_id WHERE (p.tipo_bien='CC' OR p.tipo_bien IS NULL) AND c.codigo LIKE '1.3.%'")->fetchColumn();
        $conteos['activos_af_unidades'] = (int)$this->db->query("SELECT COUNT(*) FROM inv_inventario WHERE tipo_bien='AF'")->fetchColumn();
        $conteos['productos_af'] = (int)$this->db->query("SELECT COUNT(*) FROM (SELECT 1 AS item FROM inv_inventario WHERE tipo_bien='AF' GROUP BY UPPER(LTRIM(RTRIM(nombre))), categoria_id) catalogo_af")->fetchColumn();
        if ($modoMaestros) {
            $conteos['grupo_centros_consumo'] = (int)$this->db->query("SELECT COUNT(*) FROM Talento_Humano.dbo.vw_th_maestros_organizacionales WHERE activo=1")->fetchColumn();
            $conteos['centros_consumo'] = (int)$this->db->query("SELECT COUNT(*) FROM Talento_Humano.dbo.vw_th_directorio_empleados WHERE estado=1")->fetchColumn();
        }
        return $conteos;
    }

    /**
     * Realiza una búsqueda global en todas las tablas maestras
     */
    public function buscarGeneral($termino) {
        $resultados = [];
        $likeTermino = '%' . $termino . '%';

        // Estructura de las tablas y sus campos a buscar
        $configBusqueda = [
            'categorias' => [
                'tabla' => 'inv_categorias',
                'label' => 'Grupo de Productos (Categorías)',
                'campos' => ['id', 'nombre', 'codigo', 'extra'],
                'route' => 'inv_maestros'
            ],
            'grupo_centros_consumo' => [
                'tabla' => 'Talento_Humano.dbo.vw_th_maestros_organizacionales',
                'label' => 'Grupo de Centros de Consumo',
                'campos' => ['unidad_id', 'codigo_uorg', 'nombre_unidad', 'direccion_padre', 'tipo_proceso'],
                'route' => 'inv_maestros'
            ],
            'productos' => [
                'tabla' => 'inv_productos',
                'label' => 'Catálogo de Productos',
                'campos' => ['id', 'nombre', 'codigo'],
                'route' => 'inv_maestros'
            ],
            'centros_consumo' => [
                'tabla' => 'Talento_Humano.dbo.vw_th_directorio_empleados',
                'label' => 'Personal de Centros de Consumo',
                'campos' => ['empleado_id', 'cedula', 'apellidos_nombres', 'nombres', 'apellidos', 'cargo', 'direccion_area', 'correo_institucional'],
                'route' => 'inv_maestros'
            ],
            'proveedores' => [
                'tabla' => 'inv_proveedores',
                'label' => 'Proveedores Oficiales',
                'campos' => ['id', 'codigo', 'nombre', 'ruc', 'representante', 'direccion', 'ciudad', 'email', 'telefono1', 'telefono2', 'fax', 'referencia', 'extra'],
                'route' => 'inv_maestros'
            ],
            'unidades' => [
                'tabla' => 'inv_unidades',
                'label' => 'Unidades de Medida',
                'campos' => ['id', 'nombre', 'extra'],
                'route' => 'inv_maestros'
            ],
            'tipos_iva' => [
                'tabla' => 'inv_tipos_iva',
                'label' => 'Tasas de IVA',
                'campos' => ['id', 'nombre', 'tasa_iva'],
                'route' => 'inv_maestros'
            ],
            'zonas' => [
                'tabla' => 'inv_zonas',
                'label' => 'Zonas / Terminales',
                'campos' => ['id', 'nombre', 'extra'],
                'route' => 'cabeceras'
            ],
            'estados' => [
                'tabla' => 'inv_estados',
                'label' => 'Estados Operativos',
                'campos' => ['idestado', 'descripcion', 'detalle'],
                'route' => 'cabeceras'
            ],
            'marcas' => [
                'tabla' => 'inv_marcas',
                'label' => 'Marcas',
                'campos' => ['id', 'nombre', 'extra'],
                'route' => 'cabeceras'
            ],
            'lineas' => [
                'tabla' => 'inv_lineas',
                'label' => 'Líneas Navieras',
                'campos' => ['id', 'nombre', 'extra'],
                'route' => 'cabeceras'
            ]
        ];

        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        $castType = ($driver === 'sqlsrv') ? 'VARCHAR(50)' : 'TEXT';

        foreach ($configBusqueda as $key => $conf) {
            $columnas = $conf['campos'];
            $tabla = $conf['tabla'];
            $label = $conf['label'];
            $route = $conf['route'];

            // Construir WHERE dinámico con OR
            $whereParts = [];
            $bindParams = [];
            foreach ($columnas as $i => $col) {
                if (in_array($col, ['id', 'idestado', 'tasa_iva', 'unidad_id', 'empleado_id'], true)) {
                    $whereParts[] = "CAST({$col} AS {$castType}) LIKE :term_{$key}_{$i}";
                } else {
                    $whereParts[] = "{$col} LIKE :term_{$key}_{$i}";
                }
                $bindParams[":term_{$key}_{$i}"] = $likeTermino;
            }
            $whereClause = implode(" OR ", $whereParts);

            // Seleccionar id, nombre, y código/extra de forma genérica
            if ($key === 'grupo_centros_consumo') {
                $whereClause = "activo = 1 AND ({$whereClause})";
                $selectClause = "unidad_id AS id, nombre_unidad AS nombre, codigo_uorg AS codigo,
                                 CONCAT(COALESCE(direccion_padre, ''),
                                        CASE WHEN direccion_padre IS NOT NULL AND tipo_proceso IS NOT NULL THEN ' | ' ELSE '' END,
                                        COALESCE(tipo_proceso, '')) AS extra";
            } elseif ($key === 'centros_consumo') {
                $whereClause = "estado = 1 AND ({$whereClause})";
                $selectClause = "empleado_id AS id, apellidos_nombres AS nombre, cedula AS codigo,
                                 CONCAT(COALESCE(cargo, ''),
                                        CASE WHEN cargo IS NOT NULL AND direccion_area IS NOT NULL THEN ' | ' ELSE '' END,
                                        COALESCE(direccion_area, ''),
                                        CASE WHEN correo_institucional IS NOT NULL THEN ' | ' ELSE '' END,
                                        COALESCE(correo_institucional, '')) AS extra";
            } elseif ($key === 'estados') {
                $selectClause = "idestado as id, descripcion as nombre, NULL as codigo, detalle as extra";
            } else {
                $selectFields = ["id", "nombre"];
                if (in_array('codigo', $columnas)) {
                    $selectFields[] = "codigo";
                } else {
                    $selectFields[] = "NULL as codigo";
                }
                if (in_array('extra', $columnas)) {
                    $selectFields[] = "extra";
                } elseif (in_array('representante', $columnas)) {
                    $selectFields[] = "representante as extra";
                } elseif (in_array('funcionario', $columnas)) {
                    $selectFields[] = "funcionario as extra";
                } elseif (in_array('ruc', $columnas)) {
                    $selectFields[] = "ruc as extra";
                } elseif (in_array('tasa_iva', $columnas)) {
                    $selectFields[] = "CAST(tasa_iva AS {$castType}) as extra";
                } else {
                    $selectFields[] = "NULL as extra";
                }
                $selectClause = implode(", ", $selectFields);
            }
            
            if ($key === 'estados') {
                $orderByCol = 'idestado';
            } elseif ($key === 'grupo_centros_consumo') {
                $orderByCol = 'nombre_unidad';
            } elseif ($key === 'centros_consumo') {
                $orderByCol = 'apellidos_nombres';
            } else {
                $orderByCol = 'id';
            }
            
            if ($driver === 'sqlsrv') {
                $sql = "SELECT TOP 20 {$selectClause} FROM {$tabla} WHERE {$whereClause} ORDER BY {$orderByCol} ASC";
            } else {
                $sql = "SELECT {$selectClause} FROM {$tabla} WHERE {$whereClause} ORDER BY {$orderByCol} ASC LIMIT 20";
            }
            
            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute($bindParams);
                $rows = $stmt->fetchAll();

                foreach ($rows as $row) {
                    if ($key === 'productos') {
                        $url = "index.php?route=inv_items_sistema&edit_id={$row['id']}";
                    } elseif ($key === 'centros_consumo') {
                        $url = 'index.php?route=inv_maestros&tabla=centros_consumo&buscar_maestro=' . urlencode((string)$row['codigo']) . '&highlight=' . $row['id'];
                    } else {
                        $url = "index.php?route={$route}&tabla={$key}&highlight={$row['id']}";
                    }

                    $resultados[] = [
                        'tabla' => $key,
                        'tabla_label' => $label,
                        'id' => (int)$row['id'],
                        'nombre' => $row['nombre'],
                        'codigo' => $row['codigo'],
                        'extra' => $row['extra'],
                        'url' => $url
                    ];
                }
            } catch (Exception $e) {
                // Continuar si falla
                continue;
            }
        }

        return $resultados;
    }
}

// Clase de compatibilidad hacia atrás
class InvCabecera extends EstacionModel {}

