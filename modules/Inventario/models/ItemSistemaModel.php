<?php
/**
 * ItemSistemaModel — Catálogo de productos del sistema (inv_productos).
 * Port nativo sqlsrv (sin PDO) de InvItemSistema.
 */
class ItemSistemaModel extends InvBaseModel
{
    private const SELECT = "
        SELECT p.*, g.nombre AS grupo_nombre, u.nombre AS unidad_nombre, u.extra AS unidad_abrev
        FROM inv_productos p
        JOIN inv_categorias g ON p.grupo_id = g.id
        JOIN inv_unidades u   ON p.unidad_id = u.id";

    public function obtenerTodos(int $grupoId = 0): array
    {
        if ($grupoId > 0) {
            return $this->fetchAll(self::SELECT . " WHERE p.grupo_id = ? ORDER BY g.nombre, p.nombre", [[$grupoId, SQLSRV_PARAM_IN]]);
        }
        return $this->fetchAll(self::SELECT . " ORDER BY g.nombre, p.nombre");
    }

    public function buscar(string $termino): array
    {
        $like = $this->likeParam($termino);
        return $this->fetchAll(self::SELECT . " WHERE p.nombre LIKE ? OR p.codigo LIKE ? ORDER BY g.nombre, p.nombre", [
            [$like, SQLSRV_PARAM_IN], [$like, SQLSRV_PARAM_IN],
        ]);
    }

    public function buscarPorId(int $id): ?array
    {
        return $this->fetch(self::SELECT . " WHERE p.id = ?", [[$id, SQLSRV_PARAM_IN]]);
    }

    public function crear(array $d): ?array
    {
        $this->query(
            "INSERT INTO inv_productos
                (nombre, grupo_id, unidad_id, aplica_iva, codigo, descripcion, ubicacion, existencia_min, existencia_max, precio_promedio, existencia_actual)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)", [
            [$d['nombre'],                          SQLSRV_PARAM_IN],
            [(int)$d['grupo_id'],                   SQLSRV_PARAM_IN],
            [(int)$d['unidad_id'],                  SQLSRV_PARAM_IN],
            [(int)($d['aplica_iva'] ?? 1),          SQLSRV_PARAM_IN],
            [$d['codigo'] ?? '',                    SQLSRV_PARAM_IN],
            [$d['descripcion'] ?? '',               SQLSRV_PARAM_IN],
            [$d['ubicacion'] ?? '',                 SQLSRV_PARAM_IN],
            [(float)($d['existencia_min'] ?? 0),    SQLSRV_PARAM_IN],
            [(float)($d['existencia_max'] ?? 0),    SQLSRV_PARAM_IN],
            [(float)($d['precio_promedio'] ?? 0),   SQLSRV_PARAM_IN],
            [(float)($d['existencia_actual'] ?? 0), SQLSRV_PARAM_IN],
        ]);
        return $this->buscarPorId((int)$this->lastInsertId());
    }

    public function actualizar(int $id, array $d): ?array
    {
        $this->query(
            "UPDATE inv_productos SET
                nombre = ?, grupo_id = ?, unidad_id = ?, aplica_iva = ?, codigo = ?,
                descripcion = ?, ubicacion = ?, existencia_min = ?, existencia_max = ?,
                precio_promedio = ?, existencia_actual = ?
             WHERE id = ?", [
            [$d['nombre'],                          SQLSRV_PARAM_IN],
            [(int)$d['grupo_id'],                   SQLSRV_PARAM_IN],
            [(int)$d['unidad_id'],                  SQLSRV_PARAM_IN],
            [(int)($d['aplica_iva'] ?? 1),          SQLSRV_PARAM_IN],
            [$d['codigo'] ?? '',                    SQLSRV_PARAM_IN],
            [$d['descripcion'] ?? '',               SQLSRV_PARAM_IN],
            [$d['ubicacion'] ?? '',                 SQLSRV_PARAM_IN],
            [(float)($d['existencia_min'] ?? 0),    SQLSRV_PARAM_IN],
            [(float)($d['existencia_max'] ?? 0),    SQLSRV_PARAM_IN],
            [(float)($d['precio_promedio'] ?? 0),   SQLSRV_PARAM_IN],
            [(float)($d['existencia_actual'] ?? 0), SQLSRV_PARAM_IN],
            [$id,                                   SQLSRV_PARAM_IN],
        ]);
        return $this->buscarPorId($id);
    }
}
