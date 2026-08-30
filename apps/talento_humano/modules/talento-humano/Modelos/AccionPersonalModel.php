<?php

class AccionPersonalModel extends Model
{
    public function generarSiguienteSecuencial(string $tipoAccion = 'INGRESO'): string
    {
        $anio = (int)InstitutionalClock::today()->format('Y');
        $fallback=['CAMBIO ADMINISTRATIVO'=>'CA','LICENCIA'=>'LI','SANCIONES'=>'RD','VACACIONES'=>'VAC'];
        $serie=$fallback[mb_strtoupper(trim($tipoAccion),'UTF-8')]??'MP';
        try {
            $this->auditarLectura('Accion de Personal', 'Consulta del siguiente secuencial documental.');
            $stmt=$this->db->prepare('SELECT dbo.fn_th_serie_accion(:tipo) serie,COALESCE((SELECT ultimo_numero+1 FROM dbo.th_contadores_series_accion WHERE serie=dbo.fn_th_serie_accion(:tipo2) AND anio=:anio),1) siguiente');
            $stmt->execute([':tipo'=>$tipoAccion,':tipo2'=>$tipoAccion,':anio'=>$anio]);$r=$stmt->fetch(PDO::FETCH_ASSOC);$serie=(string)($r['serie']??$serie);$numero=(int)($r['siguiente']??1);
            return $serie.'-'.str_pad((string)$numero,3,'0',STR_PAD_LEFT).'-'.$anio;
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'talento-humano', false);
            return $serie.'-001-'.$anio;
        }
    }

    public function registrarAccion(array $d): ?string
    {
        try {
            $stmt = $this->db->prepare(
                'EXEC dbo.sp_th_registrar_accion_personal_v3
                 @numero_accion=:numero,@empleado_id=:empleado,@tipo_accion=:tipo,
                 @modalidad_vigencia=:modalidad,
                 @fecha_rige_desde=:desde,@fecha_rige_hasta=:hasta,@explicacion_legal=:explicacion,
                 @detalle_otro=:detalle_otro,@presento_declaracion=:declaracion,
                 @actual_unidad_id=:unidad_actual,@actual_puesto_id=:puesto_actual,
                 @actual_lugar_trabajo=:lugar_actual,@actual_remuneracion=:rmu_actual,
                 @actual_proceso=:proceso_actual,@actual_nivel_gestion=:nivel_actual,
                 @actual_grupo_ocupacional=:grupo_actual,@actual_grado=:grado_actual,
                 @actual_partida_presupuestaria=:partida_actual,
                 @propuesta_unidad_id=:unidad_propuesta,@propuesta_puesto_id=:puesto_propuesto,
                 @propuesta_lugar_trabajo=:lugar_propuesto,@propuesta_remuneracion=:rmu_propuesta,
                 @propuesta_proceso=:proceso_propuesto,@propuesta_nivel_gestion=:nivel_propuesto,
                 @propuesta_grupo_ocupacional=:grupo_propuesto,@propuesta_grado=:grado_propuesto,
                 @propuesta_partida_presupuestaria=:partida_propuesta,
                 @actual_jornada=:jornada_actual,@actual_horas_jornada=:horas_actual,
                 @propuesta_jornada=:jornada_propuesta,@propuesta_horas_jornada=:horas_propuesta,
                 @tipo_novedad_jornada=:novedad_jornada,@hora_entrada_propuesta=:hora_entrada,
                 @hora_salida_propuesta=:hora_salida,@dias_jornada_propuesta=:dias_jornada,
                 @documento_jornada=:documento_jornada,
                 @actual_tipo_contrato=:contrato_actual,@propuesta_tipo_contrato=:contrato_propuesto,
                 @notificacion_electronica=:notificacion,@correo_notificacion=:correo,
                 @medio_notificacion=:medio,@documento_notificacion=:documento,@fecha_notificacion=:fecha_notificacion,
                 @responsable_th_nombre=:responsable_th_nombre,@responsable_th_puesto=:responsable_th_puesto,
                 @autoridad_nombre=:autoridad_nombre,@autoridad_puesto=:autoridad_puesto,
                 @elaborador_nombre=:elaborador_nombre,@elaborador_puesto=:elaborador_puesto,
                 @revisor_nombre=:revisor_nombre,@revisor_puesto=:revisor_puesto,
                 @registrador_nombre=:registrador_nombre,@registrador_puesto=:registrador_puesto,
                 @notificador_nombre=:notificador_nombre,@notificador_puesto=:notificador_puesto,
                 @usuario=:usuario,@ip=:ip'
            );
            $stmt->execute([
                ':numero' => $d['numero_accion'],
                ':empleado' => $d['empleado_id'],
                ':tipo' => $d['tipo_accion'],
                ':modalidad' => $d['modalidad_vigencia'],
                ':desde' => $d['fecha_rige_desde'],
                ':hasta' => $d['fecha_rige_hasta'] ?: null,
                ':explicacion' => $d['explicacion_legal'],
                ':detalle_otro' => $d['detalle_otro'] ?: null,
                ':declaracion' => $d['presento_declaracion'] ?: null,
                ':unidad_actual' => $d['actual_unidad_id'] ?: null,
                ':puesto_actual' => $d['actual_puesto_id'] ?: null,
                ':lugar_actual' => $d['actual_lugar_trabajo'],
                ':rmu_actual' => $d['actual_remuneracion'],
                ':proceso_actual' => $d['actual_proceso'] ?: null,
                ':nivel_actual' => $d['actual_nivel_gestion'] ?: null,
                ':grupo_actual' => $d['actual_grupo_ocupacional'] ?: null,
                ':grado_actual' => $d['actual_grado'] ?: null,
                ':partida_actual' => $d['actual_partida_presupuestaria'] ?: null,
                ':unidad_propuesta' => $d['propuesta_unidad_id'] ?: null,
                ':puesto_propuesto' => $d['propuesta_puesto_id'] ?: null,
                ':lugar_propuesto' => $d['propuesta_lugar_trabajo'],
                ':rmu_propuesta' => $d['propuesta_remuneracion'],
                ':proceso_propuesto' => $d['propuesta_proceso'] ?: null,
                ':nivel_propuesto' => $d['propuesta_nivel_gestion'] ?: null,
                ':grupo_propuesto' => $d['propuesta_grupo_ocupacional'] ?: null,
                ':grado_propuesto' => $d['propuesta_grado'] ?: null,
                ':partida_propuesta' => $d['propuesta_partida_presupuestaria'] ?: null,
                ':jornada_actual' => $d['actual_jornada'] ?: null,
                ':horas_actual' => $d['actual_horas_jornada'] ?: null,
                ':jornada_propuesta' => $d['propuesta_jornada'] ?: null,
                // Cero es un valor funcional para licencia por maternidad; en los
                // demás casos, cero significa que el operador no propuso un cambio.
                ':horas_propuesta' => in_array(strtoupper((string)$d['tipo_novedad_jornada']), ['MATERNIDAD','PATERNIDAD'], true)
                    ? 0.0
                    : ((float)$d['propuesta_horas_jornada'] > 0 ? (float)$d['propuesta_horas_jornada'] : null),
                ':novedad_jornada' => $d['tipo_novedad_jornada'] ?: null,
                ':hora_entrada' => $d['hora_entrada_propuesta'] ?: null,
                ':hora_salida' => $d['hora_salida_propuesta'] ?: null,
                ':dias_jornada' => $d['dias_jornada_propuesta'] ?: null,
                ':documento_jornada' => $d['documento_jornada'] ?: null,
                ':contrato_actual' => $d['actual_tipo_contrato'] ?: null,
                ':contrato_propuesto' => $d['propuesta_tipo_contrato'] ?: null,
                ':notificacion' => $d['notificacion_electronica'],
                ':correo' => $d['correo_notificacion'] ?: null,
                ':medio' => $d['medio_notificacion'] ?: null,
                ':documento' => $d['documento_notificacion'] ?: null,
                ':fecha_notificacion' => $d['fecha_notificacion'] ?: null,
                ':responsable_th_nombre' => $d['responsable_th_nombre'] ?: null,
                ':responsable_th_puesto' => $d['responsable_th_puesto'] ?: null,
                ':autoridad_nombre' => $d['autoridad_nombre'] ?: null,
                ':autoridad_puesto' => $d['autoridad_puesto'] ?: null,
                ':elaborador_nombre' => $d['elaborador_nombre'] ?: null,
                ':elaborador_puesto' => $d['elaborador_puesto'] ?: null,
                ':revisor_nombre' => $d['revisor_nombre'] ?: null,
                ':revisor_puesto' => $d['revisor_puesto'] ?: null,
                ':registrador_nombre' => $d['registrador_nombre'] ?: null,
                ':registrador_puesto' => $d['registrador_puesto'] ?: null,
                ':notificador_nombre' => $d['notificador_nombre'] ?: null,
                ':notificador_puesto' => $d['notificador_puesto'] ?: null,
                ':usuario' => $d['usuario_crea'],
                ':ip' => $d['direccion_ip'],
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if((int)($result['exito']??0)!==1)return null;
            $accionId=(int)($result['accion_id']??0);$real=$this->db->prepare('SELECT numero_accion FROM dbo.th_acciones_personal WHERE accion_id=:id');$real->execute([':id'=>$accionId]);$numero=(string)($real->fetchColumn()?:$result['numero_accion']??'');
            $audit=$this->db->prepare("EXEC dbo.sp_th_registrar_auditoria :usuario,'Accion de Personal','ASIGNAR_SERIE',:detalle,:ip");$audit->execute([':usuario'=>$d['usuario_crea'],':detalle'=>"Serie documental definitiva {$numero} asignada a la acción #{$accionId}.",':ip'=>$d['direccion_ip']]);while($audit->nextRowset()){}
            return $numero;
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'talento-humano', false);
            return null;
        }
    }

    public function obtenerPorId(int $id): ?array
    {
        try {
            $this->auditarLectura('Accion de Personal', "Consulta de accion #{$id}.");
            $stmt = $this->db->prepare('SELECT * FROM dbo.th_acciones_personal WHERE accion_id=:id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'talento-humano', false);
            return null;
        }
    }

    public function obtenerAccionCruzada(int $accionId): ?array
    {
        try {
            $this->auditarLectura('Accion de Personal', "Consulta de documento cruzado #{$accionId}.");
            $sql = "SELECT a.*,e.identificacion,e.nombres,e.apellidos,
                           u_act.nombre_unidad actual_area,p_act.nombre_puesto actual_cargo,
                           u_prop.nombre_unidad propuesta_area,p_prop.nombre_puesto propuesta_cargo
                    FROM dbo.th_acciones_personal a
                    JOIN dbo.th_empleados e ON e.empleado_id=a.empleado_id
                    LEFT JOIN dbo.th_unidades_organizacionales u_act ON u_act.unidad_id=a.actual_unidad_id
                    LEFT JOIN dbo.th_puestos p_act ON p_act.puesto_id=a.actual_puesto_id
                    LEFT JOIN dbo.th_unidades_organizacionales u_prop ON u_prop.unidad_id=a.propuesta_unidad_id
                    LEFT JOIN dbo.th_puestos p_prop ON p_prop.puesto_id=a.propuesta_puesto_id
                    WHERE a.accion_id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $accionId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'talento-humano', false);
            return null;
        }
    }

    public function listar(): array
    {
        try {
            $audit=$this->db->prepare("EXEC dbo.sp_th_registrar_auditoria :usuario,'Accion de Personal','CONSULTAR_VISTA','Consulta de acciones desde Biblioteca',:ip");
            $audit->execute([':usuario'=>Auth::username(),':ip'=>Auth::clientIp()]);
            while($audit->nextRowset()){}
            $stmt=$this->db->query("SELECT a.accion_id,a.numero_accion,a.fecha_elaboracion,a.tipo_accion,a.estado_documento,e.identificacion,e.nombres,e.apellidos FROM dbo.th_acciones_personal a JOIN dbo.th_empleados e ON e.empleado_id=a.empleado_id ORDER BY a.fecha_creacion DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            Conexion::registrarErrorLog($e,'talento-humano',false);
            return [];
        }
    }

    public function auditarImpresion(int $id): void
    {
        try {
            $stmt=$this->db->prepare("EXEC dbo.sp_th_registrar_auditoria :usuario,'Accion de Personal','IMPRIMIR',:detalle,:ip");
            $stmt->execute([':usuario'=>Auth::username(),':detalle'=>"Impresión de Acción de Personal #{$id}.",':ip'=>Auth::clientIp()]);
            while($stmt->nextRowset()){}
        } catch(PDOException $e) {
            Conexion::registrarErrorLog($e,'talento-humano',false);
        }
    }

    public function aprobar(int $id): array
    {
        return $this->resolver('sp_th_aprobar_accion_personal_v3', $id, null);
    }

    public function anular(int $id, string $motivo): array
    {
        return $this->resolver('sp_th_anular_accion_personal', $id, $motivo);
    }

    private function resolver(string $procedure, int $id, ?string $motivo): array
    {
        try {
            $sql = $motivo === null
                ? "EXEC dbo.{$procedure} :id,:usuario,:ip"
                : "EXEC dbo.{$procedure} :id,:motivo,:usuario,:ip";
            $params = [':id'=>$id,':usuario'=>Auth::username(),':ip'=>Auth::clientIp()];
            if ($motivo !== null) {
                $params[':motivo'] = $motivo;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['exito'=>0,'mensaje'=>'Sin respuesta del servidor.'];
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e,'talento-humano',false);
            return ['exito'=>0,'mensaje'=>'No fue posible resolver la accion.'];
        }
    }
}
