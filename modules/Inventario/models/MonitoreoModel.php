<?php
/**
 * MonitoreoModel — Movimientos de bodega: ingresos y egresos
 * (inv_bod_ingresos / inv_bod_egresos y sus detalles).
 * Port nativo sqlsrv (sin PDO).
 */
class MonitoreoModel extends InvBaseModel
{
    public function listarIngresos(): array
    {
        return $this->fetchAll(
            "SELECT bi.*, pers.nombre AS responsable_nombre,
                    (SELECT COUNT(*) FROM inv_bod_ingresos_detalles d WHERE d.ingreso_id = bi.id) AS num_items,
                    (SELECT COALESCE(SUM(d.cantidad * d.valor_unitario), 0) FROM inv_bod_ingresos_detalles d WHERE d.ingreso_id = bi.id) AS total
             FROM inv_bod_ingresos bi
             LEFT JOIN inv_talento_personal pers ON bi.responsable_id = pers.id
             ORDER BY bi.fecha DESC, bi.id DESC");
    }

    public function listarEgresos(): array
    {
        return $this->fetchAll(
            "SELECT be.*, pers.nombre AS responsable_nombre, ta.nombre AS area_nombre,
                    (SELECT COUNT(*) FROM inv_bod_egresos_detalles d WHERE d.egreso_id = be.id) AS num_items
             FROM inv_bod_egresos be
             LEFT JOIN inv_talento_personal pers ON be.responsable_id = pers.id
             LEFT JOIN inv_talento_areas ta      ON be.area_id = ta.id
             ORDER BY be.fecha DESC, be.id DESC");
    }

    public function detalleIngreso(int $id): array
    {
        return $this->fetchAll(
            "SELECT d.*, i.nombre AS item_nombre, i.secuencial
             FROM inv_bod_ingresos_detalles d
             JOIN inv_inventario i ON d.item_id = i.id
             WHERE d.ingreso_id = ?", [[$id, SQLSRV_PARAM_IN]]);
    }

    public function detalleEgreso(int $id): array
    {
        return $this->fetchAll(
            "SELECT d.*, i.nombre AS item_nombre, i.secuencial
             FROM inv_bod_egresos_detalles d
             JOIN inv_inventario i ON d.item_id = i.id
             WHERE d.egreso_id = ?", [[$id, SQLSRV_PARAM_IN]]);
    }

    public function statsBodega(): array
    {
        return [
            'ingresos'    => (int)$this->fetchColumn("SELECT COUNT(*) FROM inv_bod_ingresos"),
            'egresos'     => (int)$this->fetchColumn("SELECT COUNT(*) FROM inv_bod_egresos"),
            'proveedores' => (int)$this->fetchColumn("SELECT COUNT(*) FROM inv_proveedores"),
        ];
    }
}
