<?php

class PazSalvoModel extends Model
{
    public const SECCIONES = ['JEFE_INMEDIATO','TALENTO_HUMANO','FINANCIERO','ADMINISTRATIVO','TIC'];

    public function listar(): array
    {
        try {
            $this->auditarLectura('Paz y Salvo', 'Consulta de documentos de salida.');
            return $this->db->query("SELECT ps.*,e.identificacion,e.apellidos,e.nombres,p.nombre_puesto cargo,u.nombre_unidad area,a.numero_accion
                FROM dbo.th_paz_salvo ps JOIN dbo.th_empleados e ON e.empleado_id=ps.empleado_id
                JOIN dbo.th_acciones_personal a ON a.accion_id=ps.accion_salida_id
                LEFT JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id
                ORDER BY ps.fecha_creacion DESC,ps.paz_salvo_id DESC")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {Conexion::registrarErrorLog($e,'talento-humano',false);return [];}
    }

    public function accionesSalidaDisponibles(): array
    {
        try {
            return $this->db->query("SELECT a.accion_id,a.numero_accion,a.empleado_id,a.fecha_rige_desde,e.identificacion,e.apellidos,e.nombres,p.nombre_puesto cargo,u.nombre_unidad area
                FROM dbo.th_acciones_personal a JOIN dbo.th_empleados e ON e.empleado_id=a.empleado_id
                LEFT JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id
                WHERE UPPER(a.estado_documento)='APROBADO' AND UPPER(a.tipo_accion) COLLATE Modern_Spanish_CI_AI IN(N'CESACION DE FUNCIONES',N'DESTITUCION')
                  AND NOT EXISTS(SELECT 1 FROM dbo.th_paz_salvo ps WHERE ps.accion_salida_id=a.accion_id)
                ORDER BY a.fecha_rige_desde DESC,e.apellidos,e.nombres")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {Conexion::registrarErrorLog($e,'talento-humano',false);return [];}
    }

    public function crear(array $d): array
    {
        try {
            $stmt=$this->db->prepare('EXEC dbo.sp_th_crear_paz_salvo :empleado,:accion,:emision,:salida,:lugar,:observaciones,:usuario,:ip');
            $stmt->execute([':empleado'=>(int)$d['empleado_id'],':accion'=>(int)$d['accion_salida_id'],':emision'=>$d['fecha_emision'],':salida'=>$d['fecha_salida'],':lugar'=>$d['lugar'],':observaciones'=>$d['observaciones_generales'],':usuario'=>Auth::username(),':ip'=>Auth::clientIp()]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['exito'=>0,'mensaje'=>'Sin respuesta del servidor.'];
        } catch(PDOException $e){Conexion::registrarErrorLog($e,'talento-humano',false);return ['exito'=>0,'mensaje'=>'No fue posible crear el documento.'];}
    }

    public function guardarSeccion(array $d): array
    {
        try {
            $stmt=$this->db->prepare('EXEC dbo.sp_th_guardar_seccion_paz_salvo :id,:codigo,:estado,:json,:observaciones,:responsable,:puesto,:sumilla,:usuario,:ip');
            $stmt->execute([':id'=>(int)$d['paz_salvo_id'],':codigo'=>$d['codigo_seccion'],':estado'=>$d['estado'],':json'=>json_encode($d['datos'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),':observaciones'=>$d['observaciones'],':responsable'=>$d['responsable_nombre'],':puesto'=>$d['responsable_puesto'],':sumilla'=>$d['sumilla'],':usuario'=>Auth::username(),':ip'=>Auth::clientIp()]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['exito'=>0,'mensaje'=>'Sin respuesta del servidor.'];
        } catch(PDOException $e){Conexion::registrarErrorLog($e,'talento-humano',false);return ['exito'=>0,'mensaje'=>'No fue posible guardar la sección.'];}
    }

    public function obtener(int $id): ?array
    {
        try {
            $stmt=$this->db->prepare("SELECT ps.*,e.identificacion,e.apellidos,e.nombres,e.fecha_ingreso,e.tipo_contrato,p.nombre_puesto cargo,u.nombre_unidad area,a.numero_accion,a.tipo_accion
                FROM dbo.th_paz_salvo ps JOIN dbo.th_empleados e ON e.empleado_id=ps.empleado_id JOIN dbo.th_acciones_personal a ON a.accion_id=ps.accion_salida_id
                LEFT JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id WHERE ps.paz_salvo_id=:id");
            $stmt->execute([':id'=>$id]);$doc=$stmt->fetch(PDO::FETCH_ASSOC);if(!$doc)return null;
            $sec=$this->db->prepare('SELECT * FROM dbo.th_paz_salvo_secciones WHERE paz_salvo_id=:id ORDER BY seccion_id');$sec->execute([':id'=>$id]);
            $doc['secciones']=[];foreach($sec->fetchAll(PDO::FETCH_ASSOC) as $s){$s['datos']=json_decode((string)$s['datos_json'],true)?:[];$doc['secciones'][$s['codigo_seccion']]=$s;}
            $this->auditarLectura('Paz y Salvo', "Consulta del documento #{$id}.");return $doc;
        } catch(PDOException $e){Conexion::registrarErrorLog($e,'talento-humano',false);return null;}
    }

    public function cerrar(int $id): array
    {
        try{$s=$this->db->prepare('EXEC dbo.sp_th_cerrar_paz_salvo :id,:usuario,:ip');$s->execute([':id'=>$id,':usuario'=>Auth::username(),':ip'=>Auth::clientIp()]);return $s->fetch(PDO::FETCH_ASSOC)?:['exito'=>0,'mensaje'=>'Sin respuesta.'];}
        catch(PDOException $e){Conexion::registrarErrorLog($e,'talento-humano',false);return ['exito'=>0,'mensaje'=>'No fue posible cerrar el documento.'];}
    }

    public function auditarImpresion(int $id,bool $blanco=false): void
    {
        try{$s=$this->db->prepare("EXEC dbo.sp_th_registrar_auditoria :usuario,'Paz y Salvo','IMPRIMIR',:detalle,:ip");$s->execute([':usuario'=>Auth::username(),':detalle'=>$blanco?'Descargó formato blanco de Paz y Salvo.':"Imprimió Paz y Salvo #{$id}.",':ip'=>Auth::clientIp()]);while($s->nextRowset()){} }catch(PDOException $e){Conexion::registrarErrorLog($e,'talento-humano',false);}
    }
}
