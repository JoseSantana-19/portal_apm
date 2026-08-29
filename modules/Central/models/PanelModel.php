<?php
/**
 * Paneles nativos de módulos integrados (Talento Humano, Control de Bienes),
 * al estilo del hub de Portuaria: un vistazo con datos reales (cross-DB)
 * dentro del propio portal, antes de abrir el sistema completo externo.
 */
class PanelModel extends Model {

    public function getKpisTH(): array {
        $db = self::db();

        $resumen = $db->fetch($db->query(
            "SELECT
                COUNT(*)                                                        AS total,
                SUM(CASE WHEN sexo='M' THEN 1 ELSE 0 END)                       AS masculino,
                SUM(CASE WHEN sexo='F' THEN 1 ELSE 0 END)                       AS femenino,
                SUM(CASE WHEN MONTH(fecha_ingreso)=MONTH(GETDATE()) AND YEAR(fecha_ingreso)=YEAR(GETDATE()) THEN 1 ELSE 0 END) AS nuevos_mes
             FROM Talento_Humano.dbo.th_empleados WHERE estado=1"
        ));

        $porUnidad = $db->fetchAll($db->query(
            "SELECT TOP 6 u.nombre_unidad, COUNT(*) AS total
             FROM Talento_Humano.dbo.th_empleados e
             JOIN Talento_Humano.dbo.th_unidades_organizacionales u ON u.unidad_id = e.unidad_id
             WHERE e.estado=1
             GROUP BY u.nombre_unidad
             ORDER BY total DESC"
        ));

        return [
            'total'      => (int)($resumen['total'] ?? 0),
            'masculino'  => (int)($resumen['masculino'] ?? 0),
            'femenino'   => (int)($resumen['femenino'] ?? 0),
            'nuevos_mes' => (int)($resumen['nuevos_mes'] ?? 0),
            'por_unidad' => $porUnidad,
        ];
    }

    public function getKpisBienes(): array {
        $db = self::db();

        $resumen = $db->fetch($db->query(
            "SELECT
                COUNT(*)                                              AS total,
                SUM(CASE WHEN estado_id = 1 THEN 1 ELSE 0 END)        AS operativos,
                SUM(CASE WHEN estado_id = 2 THEN 1 ELSE 0 END)        AS mantenimiento,
                SUM(CASE WHEN estado_id IN (3, 5) THEN 1 ELSE 0 END)  AS fuera_servicio,
                SUM(ISNULL(valor,0))                                  AS valor_total
             FROM inventario.dbo.inv_inventario WHERE activo=1"
        ));

        $porCategoria = $db->fetchAll($db->query(
            "SELECT c.nombre AS categoria, COUNT(*) AS total
             FROM inventario.dbo.inv_inventario i
             LEFT JOIN inventario.dbo.inv_categorias c ON c.id = i.categoria_id
             WHERE i.activo=1
             GROUP BY c.nombre
             ORDER BY total DESC"
        ));

        return [
            'total'          => (int)($resumen['total'] ?? 0),
            'operativos'     => (int)($resumen['operativos'] ?? 0),
            'mantenimiento'  => (int)($resumen['mantenimiento'] ?? 0),
            'fuera_servicio' => (int)($resumen['fuera_servicio'] ?? 0),
            'valor_total'    => (float)($resumen['valor_total'] ?? 0),
            'por_categoria'  => $porCategoria,
        ];
    }
}
