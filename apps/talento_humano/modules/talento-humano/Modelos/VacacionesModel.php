<?php

class VacacionesModel extends Model
{
    public function listar(?string $estado = null, ?int $empleadoId = null): array
    {
        try {
            $where = [];$params = [];
            if ($estado !== null && $estado !== '') {$where[] = 'estado_vacacion=:estado';$params[':estado'] = strtoupper($estado);}
            if ($empleadoId !== null && $empleadoId > 0) {$where[] = 'empleado_id=:empleado';$params[':empleado'] = $empleadoId;}
            $sql = 'SELECT * FROM dbo.vw_th_vacaciones_acciones' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY fecha_inicio DESC,accion_id DESC';
            $stmt = $this->db->prepare($sql);$stmt->execute($params);$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->auditarLectura('Vacaciones', 'Consulta de vacaciones originadas en acciones aprobadas.');
            return $rows;
        } catch (PDOException $e) {Conexion::registrarErrorLog($e, 'talento-humano', false);return [];}
    }

    public function resumen(): array
    {
        try {
            return $this->db->query("SELECT COUNT_BIG(*) total,SUM(IIF(estado_vacacion='PROGRAMADA',1,0)) programadas,SUM(IIF(estado_vacacion='VIGENTE',1,0)) vigentes,SUM(IIF(estado_vacacion='FINALIZADA',1,0)) finalizadas FROM dbo.vw_th_vacaciones_acciones")->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {Conexion::registrarErrorLog($e, 'talento-humano', false);return [];}
    }
}
