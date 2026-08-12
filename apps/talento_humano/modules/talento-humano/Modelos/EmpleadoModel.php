<?php

class EmpleadoModel extends Model
{
    public function listarDirectorio(?int $estado = null): array
    {
        try {
            $stmt = $this->db->prepare('EXEC dbo.sp_th_consultar_directorio :usuario, :ip, :estado');
            $stmt->execute([':usuario' => Auth::username(), ':ip' => Auth::clientIp(), ':estado' => $estado]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'Talento_Humano', false);
            return [];
        }
    }

    /**
     * Catálogo liviano para autocompletados de personal.
     * Se obtiene en una sola lectura auditada y se filtra en memoria en el
     * navegador, evitando una petición SQL por cada tecla pulsada.
     */
    public function listarSelectorPersonal(): array
    {
        return array_map(static function (array $fila): array {
            return [
                'id'        => (int)($fila['id'] ?? $fila['empleado_id'] ?? 0),
                'cedula'    => (string)($fila['cedula'] ?? ''),
                'apellidos' => (string)($fila['apellidos'] ?? ''),
                'nombres'   => (string)($fila['nombres'] ?? ''),
                'cargo'     => (string)($fila['cargo'] ?? ''),
                'area'      => (string)($fila['direccion_area'] ?? ''),
                'estado'    => (int)($fila['estado'] ?? 0),
            ];
        }, $this->listarDirectorio());
    }

    public function obtenerRbuVigente(): string
    {
        try {
            $this->auditarLectura('Parametros', 'Consulta de RBU vigente.');
            $stmt = $this->db->query("SELECT valor FROM dbo.th_parametros WHERE parametro_id='RBU_2026'");
            return (string)($stmt->fetchColumn() ?: '460.00');
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'Talento_Humano', false);
            return '460.00';
        }
    }

    public function obtenerPorId(int $id): ?array
    {
        try {
            $this->auditarLectura('Directorio', "Consulta de empleado #{$id}.");
            $stmt = $this->db->prepare('SELECT * FROM dbo.th_empleados WHERE empleado_id=:id');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'Talento_Humano', false);
            return null;
        }
    }

    public function obtenerPorCedula(string $cedula): ?array
    {
        try {
            $this->auditarLectura('Directorio', 'Consulta de perfil por identificacion terminada en '.substr($cedula, -4).'.');
            $stmt = $this->db->prepare(
                'SELECT v.*,u.tipo_proceso
                 FROM dbo.vw_th_directorio_empleados v
                 LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=v.unidad_id
                 WHERE v.cedula=:cedula'
            );
            $stmt->execute([':cedula' => $cedula]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'Talento_Humano', false);
            return null;
        }
    }

    public function obtenerDetalleCompleto(int $id): ?array
    {
        try {
            $this->auditarLectura('Directorio', "Consulta de expediente completo #{$id}.");
            $stmt = $this->db->prepare(
                'SELECT v.*,u.tipo_proceso
                 FROM dbo.vw_th_directorio_empleados v
                 LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=v.unidad_id
                 WHERE v.id=:id'
            );
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'Talento_Humano', false);
            return null;
        }
    }

    public function obtenerExpedienteImpresion(int $id): ?array
    {
        try {
            $stmt=$this->db->prepare('EXEC dbo.sp_th_obtener_expediente_impresion :id,:usuario,:ip');
            $stmt->execute([':id'=>$id,':usuario'=>Auth::username(),':ip'=>Auth::clientIp()]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e,'Talento_Humano',false);
            return null;
        }
    }

    public function listarNacionalidades(): array
    {
        try {
            $stmt=$this->db->prepare('EXEC dbo.sp_th_consultar_nacionalidades :usuario,:ip');
            $stmt->execute([':usuario'=>Auth::username(),':ip'=>Auth::clientIp()]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e,'Talento_Humano',false);
            return [];
        }
    }

    public function obtenerNacionalidadesEmpleado(int $empleadoId): array
    {
        try {
            $this->auditarLectura('Directorio',"Consulta de nacionalidades del empleado #{$empleadoId}.");
            $stmt=$this->db->prepare('SELECT en.nacionalidad_id,n.nombre,n.pais,en.es_principal,en.orden FROM dbo.th_empleado_nacionalidades en JOIN dbo.th_nacionalidades n ON n.nacionalidad_id=en.nacionalidad_id WHERE en.empleado_id=:id ORDER BY en.orden');
            $stmt->execute([':id'=>$empleadoId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e,'Talento_Humano',false);
            return [];
        }
    }

    public function buscarPersonal(string $termino, ?int $unidadId, ?string $contrato, ?int $estado): array
    {
        try {
            $stmt=$this->db->prepare('EXEC dbo.sp_th_buscar_personal :termino,:unidad,:contrato,:estado,1,1000,:usuario,:ip');
            $stmt->execute([':termino'=>$termino,':unidad'=>$unidadId,':contrato'=>$contrato,':estado'=>$estado,':usuario'=>Auth::username(),':ip'=>Auth::clientIp()]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e,'Talento_Humano',false);
            return [];
        }
    }

    public function auditarExportacionDirectorio(): void
    {
        $stmt=$this->db->prepare('EXEC dbo.sp_th_registrar_auditoria :usuario,:modulo,:accion,:detalle,:ip');
        $stmt->execute([
            ':usuario'=>Auth::username(),':modulo'=>'Directorio',':accion'=>'EXPORTAR_CSV',
            ':detalle'=>'Exportación completa del directorio institucional.',':ip'=>Auth::clientIp(),
        ]);
        while ($stmt->nextRowset()) {}
    }

    public function listarAreas(bool $soloActivas = true): array
    {
        try {
            $this->auditarLectura('Maestros', 'Consulta del catalogo organizacional.');
            $sql = "SELECT unidad_id,nombre_unidad,unidad_padre_id,direccion_padre,tipo_unidad,tipo_proceso,activo
                    FROM dbo.vw_th_maestros_organizacionales";
            if ($soloActivas) {
                $sql .= ' WHERE activo=1';
            }
            $sql .= " ORDER BY CASE WHEN unidad_padre_id IS NULL THEN unidad_id ELSE unidad_padre_id END,
                              CASE WHEN unidad_padre_id IS NULL THEN 0 ELSE 1 END,nombre_unidad";
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'Talento_Humano', false);
            return [];
        }
    }

    public function listarCargos(bool $soloActivos = true): array
    {
        try {
            $this->auditarLectura('Maestros', 'Consulta del catalogo de cargos.');
            $sql = 'SELECT puesto_id,codigo_puesto,nombre_puesto,remuneracion_unificada,activo FROM dbo.th_puestos';
            if ($soloActivos) {
                $sql .= ' WHERE activo=1';
            }
            $sql .= ' ORDER BY nombre_puesto';
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'Talento_Humano', false);
            return [];
        }
    }

    public function obtenerReporteFiltrado(?string $tipoCargo = null, ?int $empleadoId = null): array
    {
        try {
            $stmt = $this->db->prepare(
                'EXEC dbo.sp_th_consultar_historial :usuario, :ip, :cargo, :empleado_id'
            );
            $stmt->execute([
                ':usuario' => Auth::username(),
                ':ip' => Auth::clientIp(),
                ':cargo' => $tipoCargo,
                ':empleado_id' => $empleadoId,
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'Talento_Humano', false);
            return [];
        }
    }

    public function insertar(array $data): bool
    {
        return $this->ejecutarGuardado('sp_th_guardar_empleado', null, $data);
    }

    public function modificar(int $id, array $data): bool
    {
        return $this->ejecutarGuardado('sp_th_modificar_empleado', $id, $data);
    }

    public function eliminar(int $id): bool
    {
        try {
            $stmt = $this->db->prepare('EXEC dbo.sp_th_eliminar_empleado :id,:usuario,:ip');
            $stmt->execute([':id' => $id, ':usuario' => Auth::username(), ':ip' => Auth::clientIp()]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['exito'] ?? 0) === 1;
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'Talento_Humano', false);
            return false;
        }
    }

    public function mover(int $empleadoId, int $unidadId, int $puestoId, string $fecha, string $motivo): array
    {
        try {
            $stmt = $this->db->prepare(
                'EXEC dbo.sp_th_mover_empleado :empleado,:unidad,:puesto,:fecha,:motivo,:usuario,:ip'
            );
            $stmt->execute([
                ':empleado' => $empleadoId,
                ':unidad' => $unidadId,
                ':puesto' => $puestoId,
                ':fecha' => $fecha,
                ':motivo' => $motivo,
                ':usuario' => Auth::username(),
                ':ip' => Auth::clientIp(),
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['exito' => 0, 'mensaje' => 'Sin respuesta del servidor.'];
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e, 'Talento_Humano', false);
            return ['exito' => 0, 'mensaje' => 'No fue posible registrar el movimiento.'];
        }
    }

    public function moverLote(array $empleados, int $unidadId, int $puestoId, string $fecha, string $motivo): array
    {
        try {
            $stmt=$this->db->prepare('EXEC dbo.sp_th_mover_empleados_lote :empleados,:unidad,:puesto,:fecha,:motivo,:usuario,:ip');
            $stmt->execute([
                ':empleados'=>json_encode(array_values(array_unique(array_map('intval',$empleados)))),
                ':unidad'=>$unidadId,':puesto'=>$puestoId,':fecha'=>$fecha,':motivo'=>$motivo,
                ':usuario'=>Auth::username(),':ip'=>Auth::clientIp(),
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['exito'=>0,'mensaje'=>'Sin respuesta del servidor.'];
        } catch (PDOException $e) {
            Conexion::registrarErrorLog($e,'Talento_Humano',false);
            return ['exito'=>0,'mensaje'=>'No fue posible registrar el movimiento grupal.'];
        }
    }

    private function ejecutarGuardado(string $procedure, ?int $id, array $data): bool
    {
        try {
            $this->db->beginTransaction();
            $prefix = $id === null ? '' : '@id=:id,';
            $sql = "EXEC dbo.{$procedure} {$prefix}
                @cedula=:cedula,@apellidos=:apellidos,@nombres=:nombres,@fecha_nac=:fecha_nac,
                @condicion=:condicion,@tipo_disc=:tipo_disc,@porcentaje_disc=:porcentaje_disc,
                @sexo=:sexo,@estado_civil=:estado_civil,@nacionalidad=:nacionalidad,
                @tipo_sangre=:tipo_sangre,@depto=:depto,@puesto=:puesto,@tipo_contrato=:tipo_contrato,
                @fecha_ing=:fecha_ing,@sueldo=:sueldo,@jornada=:jornada,@correo=:correo,
                @celular=:celular,@convencional=:convencional,@ciudad=:ciudad,@direccion=:direccion,
                @contacto_emerg=:contacto_emerg,@parentesco=:parentesco,@tel_emerg=:tel_emerg,
                @nivel_estudio=:nivel_estudio,@titulo=:titulo,@iess=:iess,@foto=:foto,@obs=:obs,
                @usuario=:usuario,@ip=:ip";
            $params = $this->mapParams($data);
            if ($id !== null) {
                $params[':id'] = $id;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ((int)($result['exito'] ?? 0) !== 1) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                return false;
            }
            $empleadoId=$id ?? (int)($result['nuevo_id']??0);
            $ids=array_values(array_unique(array_filter(array_map('intval',(array)($data['nacionalidad_ids']??[])))));
            $sync=$this->db->prepare('EXEC dbo.sp_th_sincronizar_nacionalidades_empleado :id,:json,:usuario,:ip');
            $sync->execute([':id'=>$empleadoId,':json'=>json_encode($ids),':usuario'=>Auth::username(),':ip'=>Auth::clientIp()]);
            $syncResult=$sync->fetch(PDO::FETCH_ASSOC);
            if ((int)($syncResult['exito']??0)!==1) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                return false;
            }
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            Conexion::registrarErrorLog($e, 'Talento_Humano', false);
            return false;
        }
    }

    private function mapParams(array $d): array
    {
        return [
            ':cedula' => trim((string)($d['cedula'] ?? '')),
            ':apellidos' => strtoupper(trim((string)($d['apellidos'] ?? ''))),
            ':nombres' => strtoupper(trim((string)($d['nombres'] ?? ''))),
            ':fecha_nac' => ($d['fecha_nac'] ?? null) ?: null,
            ':condicion' => $d['condicion_especial'] ?? 'Ninguna',
            ':tipo_disc' => ($d['tipo_discapacidad'] ?? null) ?: null,
            ':porcentaje_disc' => ($d['porcentaje_discapacidad'] ?? null) ?: null,
            ':sexo' => match ($d['genero'] ?? null) {'Masculino','M'=>'M','Femenino','F'=>'F',default=>null},
            ':estado_civil' => ($d['estado_civil'] ?? null) ?: null,
            ':nacionalidad' => ($d['nacionalidad'] ?? null) ?: null,
            ':tipo_sangre' => ($d['sangre'] ?? null) ?: null,
            ':depto' => ($d['unidad_id'] ?? null) ?: null,
            ':puesto' => ($d['puesto_id'] ?? null) ?: null,
            ':tipo_contrato' => ($d['tipo_contrato'] ?? null) ?: null,
            ':fecha_ing' => ($d['fecha_ingreso'] ?? null) ?: null,
            ':sueldo' => ($d['sueldo'] ?? null) ?: null,
            ':jornada' => $d['jornada'] ?? 'Completa',
            ':correo' => ($d['correo'] ?? null) ?: null,
            ':celular' => ($d['telefono'] ?? null) ?: null,
            ':convencional' => ($d['telefono_convencional'] ?? null) ?: null,
            ':ciudad' => ($d['ciudad_residencia'] ?? null) ?: null,
            ':direccion' => ($d['direccion'] ?? null) ?: null,
            ':contacto_emerg' => ($d['contacto_emergencia'] ?? null) ?: null,
            ':parentesco' => ($d['emergencia_relacion'] ?? null) ?: null,
            ':tel_emerg' => ($d['tel_emergencia'] ?? null) ?: null,
            ':nivel_estudio' => ($d['nivel_estudio'] ?? null) ?: null,
            ':titulo' => ($d['titulo'] ?? null) ?: null,
            ':iess' => ($d['iess'] ?? null) ?: null,
            ':foto' => $d['ruta_foto'] ?? 'public/img/default_avatar.png',
            ':obs' => ($d['observaciones'] ?? null) ?: null,
            ':usuario' => Auth::username(),
            ':ip' => Auth::clientIp(),
        ];
    }
}
