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

    public function obtenerTodos($tablaKey) {
        $tabla = $this->getNombreTablaReal($tablaKey);
        if ($tablaKey === 'productos') {
            $sql = "SELECT p.*, g.nombre as grupo_nombre, u.nombre as unidad_nombre 
                    FROM inv_productos p
                    JOIN inv_categorias g ON p.grupo_id = g.id
                    JOIN inv_unidades u ON p.unidad_id = u.id
                    ORDER BY p.id ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } elseif ($tablaKey === 'grupo_centros_consumo') {
            $sql = "SELECT gcc.*,
                           COALESCE(NULLIF(p.nombre, ''), gcc.representante) AS representante,
                           p.identificacion AS representante_identificacion
                    FROM inv_grupo_centros_consumo gcc
                    LEFT JOIN inv_talento_personal p ON p.id = gcc.representante_id
                    ORDER BY gcc.id ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        } elseif ($tablaKey === 'centros_consumo') {
            $sql = "SELECT cc.*, gcc.nombre as grupo_nombre,
                           COALESCE(NULLIF(p.nombre, ''), cc.funcionario) AS funcionario,
                           p.identificacion AS funcionario_identificacion
                    FROM inv_centros_consumo cc
                    JOIN inv_grupo_centros_consumo gcc ON cc.grupo_id = gcc.id
                    LEFT JOIN inv_talento_personal p ON p.id = cc.funcionario_id
                    ORDER BY cc.id ASC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
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
            $stmt = $this->db->prepare("INSERT INTO {$tabla} (nombre, ruc, extra) VALUES (:nombre, :ruc, :extra)");
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':ruc' => $datos['ruc'],
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
            $stmt = $this->db->prepare("UPDATE {$tabla} SET nombre = :nombre, ruc = :ruc, extra = :extra WHERE id = :id");
            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':ruc' => $datos['ruc'],
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
    public function obtenerConteos() {
        $conteos = [];
        foreach ($this->tablasValidas as $key => $table) {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM {$table}");
            $res = $stmt->fetch();
            $conteos[$key] = (int)$res['total'];
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
                'tabla' => 'inv_grupo_centros_consumo',
                'label' => 'Grupo de Centros de Consumo',
                'campos' => ['id', 'nombre', 'codigo', 'representante'],
                'route' => 'inv_maestros'
            ],
            'productos' => [
                'tabla' => 'inv_productos',
                'label' => 'Catálogo de Productos',
                'campos' => ['id', 'nombre', 'codigo'],
                'route' => 'inv_maestros'
            ],
            'centros_consumo' => [
                'tabla' => 'inv_centros_consumo',
                'label' => 'Centros de Consumo',
                'campos' => ['id', 'nombre', 'codigo', 'funcionario'],
                'route' => 'inv_maestros'
            ],
            'proveedores' => [
                'tabla' => 'inv_proveedores',
                'label' => 'Proveedores Oficiales',
                'campos' => ['id', 'nombre', 'ruc', 'extra'],
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
                if ($col === 'id' || $col === 'idestado' || $col === 'tasa_iva') {
                    $whereParts[] = "CAST({$col} AS {$castType}) LIKE :term_{$key}_{$i}";
                } else {
                    $whereParts[] = "{$col} LIKE :term_{$key}_{$i}";
                }
                $bindParams[":term_{$key}_{$i}"] = $likeTermino;
            }
            $whereClause = implode(" OR ", $whereParts);

            // Seleccionar id, nombre, y código/extra de forma genérica
            if ($key === 'estados') {
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
            
            $orderByCol = ($key === 'estados') ? 'idestado' : 'id';
            
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
                    $resultados[] = [
                        'tabla' => $key,
                        'tabla_label' => $label,
                        'id' => (int)$row['id'],
                        'nombre' => $row['nombre'],
                        'codigo' => $row['codigo'],
                        'extra' => $row['extra'],
                        'url' => "index.php?route={$route}&tabla={$key}&highlight={$row['id']}"
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

