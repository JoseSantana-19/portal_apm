<?php

final class MaestroModel extends Model
{
    public function unidades(): array
    {
        return $this->consultar('sp_th_consultar_unidades');
    }

    public function puestos(): array
    {
        return $this->consultar('sp_th_consultar_puestos');
    }

    public function guardarUnidad(array $d): array
    {
        $proceso=trim((string)($d['tipo_proceso'] ?? 'Apoyo'));
        if (!Catalogos::tipoProcesoValido($proceso)) {
            return ['exito'=>0,'mensaje'=>'Seleccione un tipo de proceso institucional válido.'];
        }
        try {
            $stmt = $this->db->prepare(
                'EXEC dbo.sp_th_guardar_unidad :id,:nombre,:proceso,:padre,:activo,:usuario,:ip'
            );
            $stmt->execute([
                ':id' => !empty($d['unidad_id']) ? (int)$d['unidad_id'] : null,
                ':nombre' => trim((string)($d['nombre_unidad'] ?? '')),
                ':proceso' => $proceso,
                ':padre' => !empty($d['unidad_padre_id']) ? (int)$d['unidad_padre_id'] : null,
                ':activo' => (int)($d['activo'] ?? 1),
                ':usuario' => Auth::username(),
                ':ip' => Auth::clientIp(),
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['exito' => 0, 'mensaje' => 'Sin respuesta.'];
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'admin', false);
            return ['exito' => 0, 'mensaje' => 'No fue posible guardar la unidad.'];
        }
    }

    public function guardarPuesto(array $d): array
    {
        try {
            $stmt = $this->db->prepare(
                'EXEC dbo.sp_th_guardar_puesto :id,:nombre,:remuneracion,:activo,:usuario,:ip'
            );
            $stmt->execute([
                ':id' => !empty($d['puesto_id']) ? (int)$d['puesto_id'] : null,
                ':nombre' => trim((string)($d['nombre_puesto'] ?? '')),
                ':remuneracion' => (float)($d['remuneracion_unificada'] ?? 0),
                ':activo' => (int)($d['activo'] ?? 1),
                ':usuario' => Auth::username(),
                ':ip' => Auth::clientIp(),
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['exito' => 0, 'mensaje' => 'Sin respuesta.'];
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'admin', false);
            return ['exito' => 0, 'mensaje' => 'No fue posible guardar la denominacion.'];
        }
    }

    private function consultar(string $procedure): array
    {
        try {
            $stmt = $this->db->prepare("EXEC dbo.{$procedure} :usuario,:ip");
            $stmt->execute([':usuario' => Auth::username(), ':ip' => Auth::clientIp()]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'admin', false);
            return [];
        }
    }
}
