<?php
/**
 * InvItemSistema.php - Modelo de Ítems del Sistema de Inventarios
 * Gestiona productos con campos extendidos del catálogo maestro
 * Compatible con PHP 7.4, 8.3 y 8.4
 */

require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';

class ItemSistemaModel extends Model {

    private function generarNombreCopia(string $nombre): string {
        $base = trim($nombre);
        $numero = 1;

        do {
            $sufijo = $numero === 1 ? ' (copia)' : ' (copia ' . $numero . ')';
            $candidato = $base . $sufijo;
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM inv_productos WHERE nombre = :nombre");
            $stmt->execute([':nombre' => $candidato]);
            $existe = (int)$stmt->fetchColumn() > 0;
            $numero++;
        } while ($existe);

        return $candidato;
    }

    private function detectarTipoBien(int $grupoId): string {
        $stmt = $this->db->prepare("SELECT codigo FROM inv_categorias WHERE id = :id");
        $stmt->execute([':id' => $grupoId]);
        $codigo = trim((string)$stmt->fetchColumn());
        if ($codigo === '') {
            throw new InvalidArgumentException('El grupo contable seleccionado no existe.');
        }

        $stmtHijas = $this->db->prepare(
            "SELECT COUNT(*) FROM inv_categorias WHERE id <> :id AND codigo LIKE :prefijo"
        );
        $stmtHijas->execute([':id' => $grupoId, ':prefijo' => $codigo . '%']);
        if ((int)$stmtHijas->fetchColumn() > 0) {
            throw new InvalidArgumentException('Seleccione una categoria contable final, no un grupo padre.');
        }
        return strpos($codigo, '1.4.') === 0 ? 'AF' : 'CC';
    }

    public function __construct() {
        parent::__construct();
        $this->migrarCamposExtendidos();
    }

    private function migrarCamposExtendidos(): void {
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        
        if ($driver === 'sqlsrv' || $driver === 'pgsql') {
            $check = $this->db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'inv_productos'");
            $cols  = array_map('strtolower', $check->fetchAll(PDO::FETCH_COLUMN));
            
            $nuevos = [
                'codigo'           => $driver === 'pgsql' ? "ALTER TABLE inv_productos ADD COLUMN codigo VARCHAR(50) DEFAULT ''" : "ALTER TABLE inv_productos ADD codigo NVARCHAR(50) DEFAULT ''",
                'descripcion'      => $driver === 'pgsql' ? "ALTER TABLE inv_productos ADD COLUMN descripcion TEXT DEFAULT ''" : "ALTER TABLE inv_productos ADD descripcion NVARCHAR(MAX) DEFAULT ''",
                'ubicacion'        => $driver === 'pgsql' ? "ALTER TABLE inv_productos ADD COLUMN ubicacion TEXT DEFAULT ''" : "ALTER TABLE inv_productos ADD ubicacion NVARCHAR(MAX) DEFAULT ''",
                'existencia_min'   => $driver === 'pgsql' ? "ALTER TABLE inv_productos ADD COLUMN existencia_min DOUBLE PRECISION DEFAULT 0" : "ALTER TABLE inv_productos ADD existencia_min FLOAT DEFAULT 0",
                'existencia_max'   => $driver === 'pgsql' ? "ALTER TABLE inv_productos ADD COLUMN existencia_max DOUBLE PRECISION DEFAULT 0" : "ALTER TABLE inv_productos ADD existencia_max FLOAT DEFAULT 0",
                'precio_promedio'  => $driver === 'pgsql' ? "ALTER TABLE inv_productos ADD COLUMN precio_promedio DOUBLE PRECISION DEFAULT 0" : "ALTER TABLE inv_productos ADD precio_promedio FLOAT DEFAULT 0",
                'existencia_actual'=> $driver === 'pgsql' ? "ALTER TABLE inv_productos ADD COLUMN existencia_actual DOUBLE PRECISION DEFAULT 0" : "ALTER TABLE inv_productos ADD existencia_actual FLOAT DEFAULT 0",
                'tipo_bien'        => $driver === 'pgsql' ? "ALTER TABLE inv_productos ADD COLUMN tipo_bien CHAR(2) NOT NULL DEFAULT 'CC'" : "ALTER TABLE inv_productos ADD tipo_bien CHAR(2) NOT NULL DEFAULT 'CC'",
            ];
        } else {
            $check = $this->db->query("PRAGMA table_info(inv_productos)");
            $cols  = $check->fetchAll(PDO::FETCH_COLUMN, 1);
            
            $nuevos = [
                'codigo'           => "ALTER TABLE inv_productos ADD COLUMN codigo TEXT DEFAULT ''",
                'descripcion'      => "ALTER TABLE inv_productos ADD COLUMN descripcion TEXT DEFAULT ''",
                'ubicacion'        => "ALTER TABLE inv_productos ADD COLUMN ubicacion TEXT DEFAULT ''",
                'existencia_min'   => "ALTER TABLE inv_productos ADD COLUMN existencia_min REAL DEFAULT 0",
                'existencia_max'   => "ALTER TABLE inv_productos ADD COLUMN existencia_max REAL DEFAULT 0",
                'precio_promedio'  => "ALTER TABLE inv_productos ADD COLUMN precio_promedio REAL DEFAULT 0",
                'existencia_actual'=> "ALTER TABLE inv_productos ADD COLUMN existencia_actual REAL DEFAULT 0",
                'tipo_bien'        => "ALTER TABLE inv_productos ADD COLUMN tipo_bien TEXT NOT NULL DEFAULT 'CC'",
            ];
        }

        foreach ($nuevos as $col => $sql) {
            if (!in_array(strtolower($col), $cols)) {
                try {
                    $this->db->exec($sql);
                } catch (Exception $e) {
                    // Ignorar si ya existe
                }
            }
        }

        // Clasificar automaticamente como activo fijo los grupos contables historicos 1.4.x.
        if ($driver === 'sqlsrv') {
            $this->db->exec("UPDATE p SET tipo_bien = 'AF' FROM inv_productos p JOIN inv_categorias c ON c.id = p.grupo_id WHERE c.codigo LIKE '1.4.%'");
            $this->db->exec("UPDATE p SET aplica_iva = CASE WHEN iva.tasa_iva = 0 THEN 0 ELSE 1 END FROM inv_productos p LEFT JOIN inv_tipos_iva iva ON iva.id = p.aplica_iva WHERE p.aplica_iva NOT IN (0,1)");
            if (!$this->db->query("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='inv_inventario' AND COLUMN_NAME='tipo_bien'")->fetchColumn()) {
                $this->db->exec("ALTER TABLE inv_inventario ADD tipo_bien CHAR(2) NOT NULL DEFAULT 'CC'");
            }
        } elseif ($driver === 'pgsql') {
            $this->db->exec("UPDATE inv_productos p SET tipo_bien = 'AF' FROM inv_categorias c WHERE c.id = p.grupo_id AND c.codigo LIKE '1.4.%'");
            $this->db->exec("UPDATE inv_productos p SET aplica_iva = CASE WHEN iva.tasa_iva = 0 THEN 0 ELSE 1 END FROM inv_tipos_iva iva WHERE iva.id = p.aplica_iva AND p.aplica_iva NOT IN (0,1)");
            $this->db->exec("ALTER TABLE inv_inventario ADD COLUMN IF NOT EXISTS tipo_bien CHAR(2) NOT NULL DEFAULT 'CC'");
        } else {
            $this->db->exec("UPDATE inv_productos SET tipo_bien = 'AF' WHERE grupo_id IN (SELECT id FROM inv_categorias WHERE codigo LIKE '1.4.%')");
            $this->db->exec("UPDATE inv_productos SET aplica_iva = CASE WHEN COALESCE((SELECT tasa_iva FROM inv_tipos_iva WHERE id=aplica_iva),0)=0 THEN 0 ELSE 1 END WHERE aplica_iva NOT IN (0,1)");
            $invCols = $this->db->query("PRAGMA table_info(inv_inventario)")->fetchAll(PDO::FETCH_COLUMN, 1);
            if (!in_array('tipo_bien', $invCols)) {
                $this->db->exec("ALTER TABLE inv_inventario ADD COLUMN tipo_bien TEXT NOT NULL DEFAULT 'CC'");
            }
        }

        // Crear secuencial para ítems si no existe
        if ($driver === 'sqlsrv') {
            $this->db->exec("IF NOT EXISTS (SELECT 1 FROM inv_secuenciales WHERE modulo = 'itm')
                             BEGIN
                                 INSERT INTO inv_secuenciales (modulo, prefijo, ultimo_numero) VALUES ('itm', 'ITM-', 0)
                             END");
        } elseif ($driver === 'pgsql') {
            $this->db->exec("INSERT INTO inv_secuenciales (modulo, prefijo, ultimo_numero) VALUES ('itm', 'ITM-', 0) ON CONFLICT (modulo) DO NOTHING");
        } else {
            $this->db->exec("INSERT OR IGNORE INTO inv_secuenciales (modulo, prefijo, ultimo_numero) VALUES ('itm', 'ITM-', 0)");
        }
    }

    /**
     * Obtiene todos los ítems del sistema con información de grupo y unidad
     */
    public function obtenerTodos(int $grupoId = 0): array {
        if ($grupoId > 0) {
            $sql = "SELECT p.*, g.nombre as grupo_nombre, u.nombre as unidad_nombre, u.extra as unidad_abrev, i.responsable_id
                    FROM inv_productos p
                    JOIN inv_categorias g ON p.grupo_id = g.id
                    JOIN inv_unidades u ON p.unidad_id = u.id
                    LEFT JOIN inv_inventario i ON i.producto_id = p.id
                    WHERE p.grupo_id = :gid
                    ORDER BY p.codigo ASC, p.nombre ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':gid' => $grupoId]);
        } else {
            $sql = "SELECT p.*, g.nombre as grupo_nombre, u.nombre as unidad_nombre, u.extra as unidad_abrev, i.responsable_id
                    FROM inv_productos p
                    JOIN inv_categorias g ON p.grupo_id = g.id
                    JOIN inv_unidades u ON p.unidad_id = u.id
                    LEFT JOIN inv_inventario i ON i.producto_id = p.id
                    ORDER BY g.nombre ASC, p.codigo ASC, p.nombre ASC";
            $stmt = $this->db->query($sql);
        }
        return $stmt->fetchAll();
    }

    /**
     * Busca un ítem por ID
     */
    public function buscarPorId(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT p.*, g.nombre as grupo_nombre, u.nombre as unidad_nombre, u.extra as unidad_abrev, i.responsable_id
             FROM inv_productos p
             JOIN inv_categorias g ON p.grupo_id = g.id
             JOIN inv_unidades u ON p.unidad_id = u.id
             LEFT JOIN inv_inventario i ON i.producto_id = p.id
             WHERE p.id = :id"
        );
        $stmt->execute([':id' => $id]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    /**
     * Busca ítems por término de búsqueda
     */
    public function buscar(string $termino, int $grupoId = 0): array {
        $filtroGrupo = $grupoId > 0 ? " AND p.grupo_id = :grupo_id" : "";
        $stmt = $this->db->prepare(
            "SELECT p.*, g.nombre as grupo_nombre, u.nombre as unidad_nombre, u.extra as unidad_abrev, i.responsable_id
             FROM inv_productos p
             JOIN inv_categorias g ON p.grupo_id = g.id
             JOIN inv_unidades u ON p.unidad_id = u.id
             LEFT JOIN inv_inventario i ON i.producto_id = p.id
             WHERE (TRANSLATE(LOWER(p.nombre), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun') LIKE :term1
                OR p.codigo LIKE :raw_term
                OR TRANSLATE(LOWER(p.descripcion), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun') LIKE :term2
                OR TRANSLATE(LOWER(g.nombre), 'áéíóúüñÁÉÍÓÚÜÑ', 'aeiouunaeiouun') LIKE :term3)
             {$filtroGrupo}
             ORDER BY p.codigo ASC"
        );
        
        $normTerm = $this->normalizarTexto($termino);
        $params = [
            ':term1' => '%' . $normTerm . '%',
            ':term2' => '%' . $normTerm . '%',
            ':term3' => '%' . $normTerm . '%',
            ':raw_term' => '%' . $termino . '%'
        ];
        if ($grupoId > 0) $params[':grupo_id'] = $grupoId;
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function normalizarTexto($str) {
        $str = mb_strtolower($str, 'UTF-8');
        $replaces = [
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ü'=>'u',
            'Á'=>'a', 'É'=>'e', 'Í'=>'i', 'Ó'=>'o', 'Ú'=>'u', 'Ü'=>'u',
            'ñ'=>'n', 'Ñ'=>'n'
        ];
        return strtr($str, $replaces);
    }

    /**
     * Crea un nuevo ítem del sistema
     */
    public function crear(array $datos): array {
        $datos['tipo_bien'] = $this->detectarTipoBien((int)$datos['grupo_id']);
        if ($datos['tipo_bien'] !== 'AF') $datos['responsable_id'] = null;
        if (empty($datos['codigo'])) {
            $nuevo = (new InvSecuencial())->generarNumero('itm');
            $datos['codigo'] = str_pad($nuevo, 6, '0', STR_PAD_LEFT);
        } else {
            $codigo = $datos['codigo'];
            while (true) {
                $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM inv_productos WHERE codigo = :cod");
                $stmtCheck->execute([':cod' => $codigo]);
                if ($stmtCheck->fetchColumn() == 0) {
                    break;
                }
                $nuevo = (new InvSecuencial())->generarNumero('itm');
                $codigo = str_pad($nuevo, 6, '0', STR_PAD_LEFT);
            }
            $datos['codigo'] = $codigo;
        }

        $stmtNameCheck = $this->db->prepare("SELECT id FROM inv_productos WHERE nombre = :nombre");
        $stmtNameCheck->execute([':nombre' => $datos['nombre']]);
        $existingId = $stmtNameCheck->fetchColumn();

        if ($existingId) {
            // Una copia siempre crea otro registro; no debe sobrescribir la
            // plantilla original aunque el usuario conserve el mismo nombre.
            if (!empty($datos['copiar_desde_id'])) {
                $datos['nombre'] = $this->generarNombreCopia($datos['nombre']);
            } else {
                return $this->actualizar((int)$existingId, $datos);
            }
        }

        $stmt = $this->db->prepare(
            "INSERT INTO inv_productos
             (nombre, grupo_id, unidad_id, aplica_iva, codigo, descripcion, ubicacion, tipo_bien,
              existencia_min, existencia_max, precio_promedio, existencia_actual)
             VALUES
             (:nombre, :grupo_id, :unidad_id, :aplica_iva, :codigo, :desc, :ubicacion, :tipo_bien,
              :exmin, :exmax, :precio, :exactu)"
        );
        $stmt->execute([
            ':nombre'    => $datos['nombre'],
            ':grupo_id'  => (int)$datos['grupo_id'],
            ':unidad_id' => (int)$datos['unidad_id'],
            ':aplica_iva'=> isset($datos['aplica_iva']) ? (int)$datos['aplica_iva'] : 1,
            ':codigo'    => $datos['codigo'],
            ':desc'      => $datos['descripcion'] ?? '',
            ':ubicacion' => $datos['ubicacion'] ?? '',
            ':tipo_bien' => $datos['tipo_bien'] ?? 'CC',
            ':exmin'     => (float)($datos['existencia_min'] ?? 0),
            ':exmax'     => (float)($datos['existencia_max'] ?? 0),
            ':precio'    => (float)($datos['precio_promedio'] ?? 0),
            ':exactu'    => (float)($datos['existencia_actual'] ?? 0),
        ]);

        $id = (int)$this->db->lastInsertId();
        self::syncProductToInventory($this->db, $id, $datos['nombre'], (int)$datos['grupo_id'], (int)($datos['aplica_iva'] ?? 1), (float)($datos['precio_promedio'] ?? 0), (float)($datos['existencia_actual'] ?? 1), $datos['tipo_bien'] ?? 'CC', $datos['responsable_id'] ?? null);
        return $this->buscarPorId($id);
    }

    /**
     * Actualiza un ítem del sistema
     */
    public function actualizar(int $id, array $datos): array {
        $datos['tipo_bien'] = $this->detectarTipoBien((int)$datos['grupo_id']);
        if ($datos['tipo_bien'] !== 'AF') $datos['responsable_id'] = null;
        $stmt = $this->db->prepare(
            "UPDATE inv_productos SET
             nombre = :nombre, grupo_id = :grupo_id, unidad_id = :unidad_id,
             aplica_iva = :aplica_iva, codigo = :codigo, descripcion = :desc,
             ubicacion = :ubicacion, tipo_bien = :tipo_bien, existencia_min = :exmin, existencia_max = :exmax,
             precio_promedio = :precio, existencia_actual = :exactu
             WHERE id = :id"
        );
        $stmt->execute([
            ':nombre'    => $datos['nombre'],
            ':grupo_id'  => (int)$datos['grupo_id'],
            ':unidad_id' => (int)$datos['unidad_id'],
            ':aplica_iva'=> isset($datos['aplica_iva']) ? (int)$datos['aplica_iva'] : 1,
            ':codigo'    => $datos['codigo'] ?? '',
            ':desc'      => $datos['descripcion'] ?? '',
            ':ubicacion' => $datos['ubicacion'] ?? '',
            ':tipo_bien' => $datos['tipo_bien'] ?? 'CC',
            ':exmin'     => (float)($datos['existencia_min'] ?? 0),
            ':exmax'     => (float)($datos['existencia_max'] ?? 0),
            ':precio'    => (float)($datos['precio_promedio'] ?? 0),
            ':exactu'    => (float)($datos['existencia_actual'] ?? 0),
            ':id'        => $id,
        ]);
        self::syncProductToInventory($this->db, $id, $datos['nombre'], (int)$datos['grupo_id'], (int)($datos['aplica_iva'] ?? 1), (float)($datos['precio_promedio'] ?? 0), (float)($datos['existencia_actual'] ?? 1), $datos['tipo_bien'] ?? 'CC', $datos['responsable_id'] ?? null);
        return $this->buscarPorId($id);
    }

    public static function syncProductToInventory($db, int $productId, string $nombre, int $grupoId, int $aplicaIva, float $valor = 0.0, float $cantidad = 1.0, string $tipoBien = 'CC', ?int $responsableId = null): void {
        $tipoBien = $tipoBien === 'AF' ? 'AF' : 'CC';
        $responsableId = $tipoBien === 'AF' && $responsableId ? $responsableId : null;
        $stmt = $db->prepare("SELECT id FROM inv_inventario WHERE producto_id = :prod_id");
        $stmt->execute([':prod_id' => $productId]);
        $invRecord = $stmt->fetch();

        if ($invRecord) {
            $updateStmt = $db->prepare(
                "UPDATE inv_inventario SET
                    nombre = :nombre,
                    categoria_id = :cat_id,
                    valor = :valor,
                    cantidad = :cantidad,
                    tipo_bien = :tipo_bien,
                    responsable_id = :responsable_id
                 WHERE id = :id"
            );
            $updateStmt->execute([
                ':nombre'    => $nombre,
                ':cat_id'    => $grupoId,
                ':valor'     => $valor,
                ':cantidad'  => $cantidad,
                ':tipo_bien' => $tipoBien,
                ':responsable_id' => $responsableId,
                ':id'        => (int)$invRecord['id']
            ]);
        } else {
            require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';
            $secuencialObj = new InvSecuencial();
            $secuencial = $secuencialObj->generarSiguiente('inv');

            while (true) {
                $stmtCheck = $db->prepare("SELECT COUNT(*) FROM inv_inventario WHERE secuencial = :sec");
                $stmtCheck->execute([':sec' => $secuencial]);
                if ($stmtCheck->fetchColumn() == 0) {
                    break;
                }
                $secuencial = $secuencialObj->generarSiguiente('inv');
            }

            $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
            $sqlZona = ($driver === 'sqlsrv') ? "SELECT TOP 1 id FROM inv_zonas" : "SELECT id FROM inv_zonas LIMIT 1";
            $zonaId = (int)$db->query($sqlZona)->fetchColumn();
            if (!$zonaId) $zonaId = 1;

            $sqlEstado = ($driver === 'sqlsrv') ? "SELECT TOP 1 idestado FROM inv_estados" : "SELECT idestado FROM inv_estados LIMIT 1";
            $estadoId = (int)$db->query($sqlEstado)->fetchColumn();
            if (!$estadoId) $estadoId = 111;

            $insertStmt = $db->prepare(
                "INSERT INTO inv_inventario (
                    secuencial, nombre, marca, categoria_id, zona_id, estado_id,
                    responsable_id, valor, fecha_registro, observaciones, activo, cantidad, producto_id, tipo_bien
                ) VALUES (
                    :sec, :nombre, :marca, :cat_id, :zona_id, :est_id,
                    :responsable_id, :valor, :fecha, :obs, 1, :cantidad, :prod_id, :tipo_bien
                )"
            );
            $insertStmt->execute([
                ':sec'       => $secuencial,
                ':nombre'    => $nombre,
                ':marca'     => 'Genérico',
                ':cat_id'    => $grupoId,
                ':zona_id'   => $zonaId,
                ':est_id'    => $estadoId,
                ':valor'     => $valor,
                ':fecha'     => date('Y-m-d'),
                ':obs'       => 'Creado automáticamente desde Ítems del Sistema',
                ':cantidad'  => $cantidad,
                ':responsable_id' => $responsableId,
                ':tipo_bien' => $tipoBien,
                ':prod_id'   => $productId
            ]);
        }
    }

    public static function calcularTotal(float $precio, float $existencia): float {
        return round($precio * $existencia, 4);
    }
}

class InvItemSistema extends ItemSistemaModel {}
