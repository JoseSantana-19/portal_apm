<?php
/**
 * InvPanelModel — Datos del Panel de Control de Bienes (hub nativo del portal).
 * Fuente: BD inventario (tablas inv_*).
 */
class InvPanelModel extends InvBaseModel
{
    public function stats(): array
    {
        return [
            'bienes'      => (int)$this->fetchColumn("SELECT COUNT(*) FROM inv_inventario WHERE activo = 1"),
            'productos'   => (int)$this->fetchColumn("SELECT COUNT(*) FROM inv_productos"),
            'ingresos'    => (int)$this->fetchColumn("SELECT COUNT(*) FROM inv_bod_ingresos"),
            'egresos'     => (int)$this->fetchColumn("SELECT COUNT(*) FROM inv_bod_egresos"),
            'proveedores' => (int)$this->fetchColumn("SELECT COUNT(*) FROM inv_proveedores"),
            'valor_total' => (float)$this->fetchColumn("SELECT ISNULL(SUM(valor * cantidad), 0) FROM inv_inventario WHERE activo = 1"),
        ];
    }

    /** Bienes activos por categoría (top N, para gráfico). */
    public function bienesPorCategoria(int $top = 6): array
    {
        return $this->fetchAll(
            "SELECT TOP (" . (int)$top . ")
                    ISNULL(c.nombre, N'Sin categoría') AS label,
                    COUNT(*) AS value
             FROM inv_inventario i
             LEFT JOIN inv_categorias c ON c.id = i.categoria_id
             WHERE i.activo = 1
             GROUP BY c.nombre
             ORDER BY COUNT(*) DESC"
        );
    }

    /** Bienes activos por zona (top N, para gráfico). */
    public function bienesPorZona(int $top = 6): array
    {
        return $this->fetchAll(
            "SELECT TOP (" . (int)$top . ")
                    ISNULL(z.nombre, N'Sin zona') AS label,
                    COUNT(*) AS value
             FROM inv_inventario i
             LEFT JOIN inv_zonas z ON z.id = i.zona_id
             WHERE i.activo = 1
             GROUP BY z.nombre
             ORDER BY COUNT(*) DESC"
        );
    }

    /** Últimos bienes registrados en el inventario general. */
    public function ultimosBienes(int $limit = 6): array
    {
        return $this->fetchAll(
            "SELECT TOP (" . (int)$limit . ")
                    i.secuencial, i.nombre, i.marca, i.valor, i.cantidad, i.fecha_registro,
                    ISNULL(c.nombre, N'—') AS categoria,
                    ISNULL(z.nombre, N'—') AS zona
             FROM inv_inventario i
             LEFT JOIN inv_categorias c ON c.id = i.categoria_id
             LEFT JOIN inv_zonas z ON z.id = i.zona_id
             WHERE i.activo = 1
             ORDER BY i.id DESC"
        );
    }
}
