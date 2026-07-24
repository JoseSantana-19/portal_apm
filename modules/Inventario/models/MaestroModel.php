<?php
/**
 * MaestroModel — Tablas maestras del inventario (categorías, zonas, estados,
 * marcas, líneas, unidades, tipos_iva, productos, proveedores, centros).
 * Port nativo sqlsrv (sin PDO) de EstacionModel.
 */
class MaestroModel extends InvBaseModel
{
    private array $tablas = [
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
        'centros_consumo' => 'inv_centros_consumo',
    ];

    private function tabla(string $key): string
    {
        if (!isset($this->tablas[$key])) {
            throw new InvalidArgumentException("Tabla maestra no válida: {$key}");
        }
        return $this->tablas[$key];
    }

    public function tablasDisponibles(): array
    {
        return array_keys($this->tablas);
    }

    public function obtenerTodos(string $key): array
    {
        $tabla = $this->tabla($key);
        if ($key === 'productos') {
            return $this->fetchAll(
                "SELECT p.*, g.nombre AS grupo_nombre, u.nombre AS unidad_nombre
                 FROM inv_productos p
                 JOIN inv_categorias g ON p.grupo_id = g.id
                 JOIN inv_unidades u   ON p.unidad_id = u.id
                 ORDER BY p.id ASC");
        }
        if ($key === 'centros_consumo') {
            return $this->fetchAll(
                "SELECT cc.*, gcc.nombre AS grupo_nombre
                 FROM inv_centros_consumo cc
                 JOIN inv_grupo_centros_consumo gcc ON cc.grupo_id = gcc.id
                 ORDER BY cc.id ASC");
        }
        if ($key === 'estados') {
            return $this->fetchAll(
                "SELECT idestado AS id, descripcion AS nombre, detalle AS extra, estado
                 FROM inv_estados ORDER BY idestado ASC");
        }
        return $this->fetchAll("SELECT * FROM {$tabla} ORDER BY id ASC");
    }

    public function buscarPorId(string $key, int $id): ?array
    {
        $tabla = $this->tabla($key);
        if ($key === 'estados') {
            return $this->fetch(
                "SELECT idestado AS id, descripcion AS nombre, detalle AS extra, estado FROM inv_estados WHERE idestado = ?",
                [[$id, SQLSRV_PARAM_IN]]);
        }
        return $this->fetch("SELECT * FROM {$tabla} WHERE id = ?", [[$id, SQLSRV_PARAM_IN]]);
    }

    public function obtenerConteos(): array
    {
        $conteos = [];
        foreach ($this->tablas as $key => $tabla) {
            $conteos[$key] = (int)$this->fetchColumn("SELECT COUNT(*) FROM {$tabla}");
        }
        return $conteos;
    }

    public function crear(string $key, array $d): ?array
    {
        $tabla = $this->tabla($key);
        switch ($key) {
            case 'estados':
                $maxId = $this->fetchColumn("SELECT MAX(idestado) FROM inv_estados WHERE idestado BETWEEN 111 AND 120");
                $newId = $maxId ? (int)$maxId + 1 : 111;
                if ($newId > 120) throw new Exception("Límite de estados de inventario alcanzado (111-120).");
                $this->query("INSERT INTO inv_estados (idestado, descripcion, detalle, estado) VALUES (?,?,?,1)", [
                    [$newId, SQLSRV_PARAM_IN], [$d['nombre'], SQLSRV_PARAM_IN], [$d['extra'] ?? '', SQLSRV_PARAM_IN],
                ]);
                return $this->buscarPorId($key, $newId);
            case 'categorias':
                $this->query("INSERT INTO inv_categorias (nombre, codigo, extra) VALUES (?,?,?)", [
                    [$d['nombre'], SQLSRV_PARAM_IN], [$d['codigo'] ?? '', SQLSRV_PARAM_IN], [$d['extra'] ?? '', SQLSRV_PARAM_IN],
                ]);
                break;
            case 'tipos_iva':
                $this->query("INSERT INTO inv_tipos_iva (nombre, tasa_iva) VALUES (?,?)", [
                    [$d['nombre'], SQLSRV_PARAM_IN], [(float)$d['tasa_iva'], SQLSRV_PARAM_IN],
                ]);
                break;
            case 'productos':
                $this->query("INSERT INTO inv_productos (nombre, grupo_id, unidad_id, aplica_iva, codigo) VALUES (?,?,?,?,?)", [
                    [$d['nombre'], SQLSRV_PARAM_IN], [(int)$d['grupo_id'], SQLSRV_PARAM_IN],
                    [(int)$d['unidad_id'], SQLSRV_PARAM_IN], [(int)($d['aplica_iva'] ?? 1), SQLSRV_PARAM_IN],
                    [$d['codigo'] ?? str_pad((string)random_int(1, 999999), 6, '0', STR_PAD_LEFT), SQLSRV_PARAM_IN],
                ]);
                break;
            case 'proveedores':
                $this->query("INSERT INTO inv_proveedores (nombre, ruc, extra) VALUES (?,?,?)", [
                    [$d['nombre'], SQLSRV_PARAM_IN], [$d['ruc'] ?? '', SQLSRV_PARAM_IN], [$d['extra'] ?? '', SQLSRV_PARAM_IN],
                ]);
                break;
            default:
                $this->query("INSERT INTO {$tabla} (nombre, extra) VALUES (?,?)", [
                    [$d['nombre'], SQLSRV_PARAM_IN], [$d['extra'] ?? '', SQLSRV_PARAM_IN],
                ]);
        }
        return $this->buscarPorId($key, (int)$this->lastInsertId());
    }

    public function actualizar(string $key, int $id, array $d): ?array
    {
        $tabla = $this->tabla($key);
        switch ($key) {
            case 'estados':
                $this->query("UPDATE inv_estados SET descripcion = ?, detalle = ? WHERE idestado = ?", [
                    [$d['nombre'], SQLSRV_PARAM_IN], [$d['extra'] ?? '', SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN],
                ]);
                break;
            case 'categorias':
                $this->query("UPDATE inv_categorias SET nombre = ?, codigo = ?, extra = ? WHERE id = ?", [
                    [$d['nombre'], SQLSRV_PARAM_IN], [$d['codigo'] ?? '', SQLSRV_PARAM_IN], [$d['extra'] ?? '', SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN],
                ]);
                break;
            case 'tipos_iva':
                $this->query("UPDATE inv_tipos_iva SET nombre = ?, tasa_iva = ? WHERE id = ?", [
                    [$d['nombre'], SQLSRV_PARAM_IN], [(float)$d['tasa_iva'], SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN],
                ]);
                break;
            case 'productos':
                $this->query("UPDATE inv_productos SET nombre = ?, grupo_id = ?, unidad_id = ?, aplica_iva = ? WHERE id = ?", [
                    [$d['nombre'], SQLSRV_PARAM_IN], [(int)$d['grupo_id'], SQLSRV_PARAM_IN],
                    [(int)$d['unidad_id'], SQLSRV_PARAM_IN], [(int)($d['aplica_iva'] ?? 1), SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN],
                ]);
                break;
            case 'proveedores':
                $this->query("UPDATE inv_proveedores SET nombre = ?, ruc = ?, extra = ? WHERE id = ?", [
                    [$d['nombre'], SQLSRV_PARAM_IN], [$d['ruc'] ?? '', SQLSRV_PARAM_IN], [$d['extra'] ?? '', SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN],
                ]);
                break;
            default:
                $this->query("UPDATE {$tabla} SET nombre = ?, extra = ? WHERE id = ?", [
                    [$d['nombre'], SQLSRV_PARAM_IN], [$d['extra'] ?? '', SQLSRV_PARAM_IN], [$id, SQLSRV_PARAM_IN],
                ]);
        }
        return $this->buscarPorId($key, $id);
    }

    public function eliminar(string $key, int $id): bool
    {
        $tabla = $this->tabla($key);
        $col   = ($key === 'estados') ? 'idestado' : 'id';
        return $this->exec("DELETE FROM {$tabla} WHERE {$col} = ?", [[$id, SQLSRV_PARAM_IN]]) > 0;
    }
}
