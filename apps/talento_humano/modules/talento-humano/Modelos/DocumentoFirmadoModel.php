<?php

class DocumentoFirmadoModel extends Model
{
    public const TIPOS = ['FICHA_PERSONAL','ACCION_PERSONAL','ESTUDIO_SOCIOECONOMICO','PAZ_SALVO'];

    public function resolverOrigen(string $tipo, int $origenId): ?array
    {
        $tipo = strtoupper(trim($tipo));
        if (!in_array($tipo, self::TIPOS, true) || $origenId <= 0) return null;
        $consultas = [
            'FICHA_PERSONAL' => "SELECT e.empleado_id,e.identificacion,e.apellidos,e.nombres,CONCAT('EXP-',e.identificacion) numero_documento,'EXPEDIENTE' estado,CAST(1 AS bit) legalizable,
                CONCAT('talento-humano/empleado/imprimir-ficha?id=',e.empleado_id) imprimir_url
                FROM dbo.th_empleados e WHERE e.empleado_id=:id",
            'ACCION_PERSONAL' => "SELECT a.empleado_id,e.identificacion,e.apellidos,e.nombres,a.numero_accion numero_documento,a.estado_documento estado,CAST(IIF(a.estado_documento='APROBADO',1,0) AS bit) legalizable,
                CONCAT('talento-humano/accion-personal/imprimir-accion?id=',a.accion_id) imprimir_url
                FROM dbo.th_acciones_personal a JOIN dbo.th_empleados e ON e.empleado_id=a.empleado_id WHERE a.accion_id=:id",
            'ESTUDIO_SOCIOECONOMICO' => "SELECT s.empleado_id,e.identificacion,e.apellidos,e.nombres,CONCAT(s.codigo_formato,' #',s.estudio_id) numero_documento,IIF(s.estado=1,'REGISTRADO','INACTIVO') estado,CAST(s.estado AS bit) legalizable,
                CONCAT('talento-humano/estudio-seguridad/imprimir?estudio_id=',s.estudio_id) imprimir_url
                FROM dbo.th_estudios_socioeconomicos s JOIN dbo.th_empleados e ON e.empleado_id=s.empleado_id WHERE s.estudio_id=:id",
            'PAZ_SALVO' => "SELECT p.empleado_id,e.identificacion,e.apellidos,e.nombres,p.numero_documento,p.estado,CAST(IIF(p.estado='CERRADO',1,0) AS bit) legalizable,
                CONCAT('talento-humano/paz-salvo/imprimir?id=',p.paz_salvo_id) imprimir_url
                FROM dbo.th_paz_salvo p JOIN dbo.th_empleados e ON e.empleado_id=p.empleado_id WHERE p.paz_salvo_id=:id",
        ];
        try {
            $stmt=$this->db->prepare($consultas[$tipo]);$stmt->execute([':id'=>$origenId]);
            $fila=$stmt->fetch(PDO::FETCH_ASSOC);if(!$fila)return null;
            $fila['tipo_documento']=$tipo;$fila['origen_id']=$origenId;
            return $fila;
        } catch(PDOException $e){Conexion::registrarErrorLog($e,'talento-humano',false);return null;}
    }

    public function listar(string $tipo, int $origenId, ?int $empleadoId=null): array
    {
        try {
            $stmt=$this->db->prepare('EXEC dbo.sp_th_consultar_documentos_firmados :tipo,:origen,:empleado,:usuario,:ip');
            $stmt->execute([':tipo'=>$tipo,':origen'=>$origenId,':empleado'=>$empleadoId,':usuario'=>Auth::username(),':ip'=>Auth::clientIp()]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e){Conexion::registrarErrorLog($e,'talento-humano',false);return [];}
    }

    public function registrar(array $d): array
    {
        try {
            $stmt=$this->db->prepare('EXEC dbo.sp_th_registrar_documento_firmado :empleado,:tipo,:origen,:nombre,:ruta,:mime,:tamano,:sha,:observaciones,:usuario,:ip');
            $stmt->execute([
                ':empleado'=>(int)$d['empleado_id'],':tipo'=>$d['tipo_documento'],':origen'=>(int)$d['origen_id'],
                ':nombre'=>$d['nombre_original'],':ruta'=>$d['ruta_privada'],':mime'=>$d['mime_type'],
                ':tamano'=>(int)$d['tamano_bytes'],':sha'=>$d['sha256'],':observaciones'=>$d['observaciones'],
                ':usuario'=>Auth::username(),':ip'=>Auth::clientIp(),
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['exito'=>0,'mensaje'=>'El servidor no confirmó la incorporación documental.'];
        } catch(PDOException $e){Conexion::registrarErrorLog($e,'talento-humano',false);return ['exito'=>0,'mensaje'=>'No fue posible registrar el documento firmado.'];}
    }

    public function obtener(int $id): ?array
    {
        try {
            $stmt=$this->db->prepare('SELECT * FROM dbo.vw_th_documentos_firmados WHERE documento_id=:id');
            $stmt->execute([':id'=>$id]);$fila=$stmt->fetch(PDO::FETCH_ASSOC);
            return $fila ?: null;
        } catch(PDOException $e){Conexion::registrarErrorLog($e,'talento-humano',false);return null;}
    }

    public function auditarDescarga(array $documento): void
    {
        try {
            $detalle="Descargó documento firmado #{$documento['documento_id']} ({$documento['tipo_documento']} #{$documento['origen_id']}), SHA-256 {$documento['sha256']}.";
            $stmt=$this->db->prepare("EXEC dbo.sp_th_registrar_auditoria :usuario,'Expediente Documental','DESCARGAR_FIRMADO',:detalle,:ip");
            $stmt->execute([':usuario'=>Auth::username(),':detalle'=>$detalle,':ip'=>Auth::clientIp()]);while($stmt->nextRowset()){}
        } catch(PDOException $e){Conexion::registrarErrorLog($e,'talento-humano',false);}
    }
}
