<?php

class EstudioSeguridadModel extends Model
{
    private const CAMPOS = [
        'empleado_id','fecha_vinculacion','cargo_cabecera','nombre_cabecera','tipo_doc_ident','nro_documento',
        'nacionalidad','anios_residencia','libreta_militar','nro_libreta_militar','tipo_relacion','apellidos','nombres',
        'fecha_nacimiento','edad','lugar_nacimiento','provincia_ciudad_nac','genero','tipo_sangre','estado_civil',
        'discapacidad','tipo_discapacidad','nro_carnet_conadis','servidor_carrera','nro_servidor_carrera',
        'auto_identificacion','nacionalidad_indigena','dir_calle_principal','numero_domicilio','calle_secundaria',
        'parroquia','canton','provincia_dom','referencia_domiciliaria','tel_domicilio','tel_celular','tel_trabajo',
        'extension','correo_institucional','correo_alternativo','contacto_nombre','contacto_parentesco',
        'contacto_tel_conv','contacto_tel_cel','nro_otorgamiento','fecha_ingreso_bienes','banco','tipo_cuenta',
        'nro_cuenta','conyuge_nombres','conyuge_tipo_doc','conyuge_nro_doc','conyuge_fecha_nac',
        'conyuge_tipo_relacion','conyuge_nivel_instruccion','conyuge_ocupacion','nivel_instruccion',
        'institucion_educativa','tipo_periodo','area_conocimiento','egresado','titulo_academico','vivienda_tipo',
        'vehiculo_marca','vehiculo_modelo','vehiculo_placa','vehiculo_valor'
    ];

    private const FECHAS = ['fecha_vinculacion','fecha_nacimiento','fecha_ingreso_bienes','conyuge_fecha_nac'];

    private const LIMITES = [
        'cargo_cabecera'=>180,'nombre_cabecera'=>220,'tipo_doc_ident'=>50,'nro_documento'=>30,
        'nacionalidad'=>80,'anios_residencia'=>30,'libreta_militar'=>30,'nro_libreta_militar'=>40,
        'tipo_relacion'=>80,'apellidos'=>150,'nombres'=>150,'edad'=>20,'lugar_nacimiento'=>120,
        'provincia_ciudad_nac'=>150,'genero'=>40,'tipo_sangre'=>20,'estado_civil'=>40,
        'discapacidad'=>20,'tipo_discapacidad'=>100,'nro_carnet_conadis'=>40,'servidor_carrera'=>30,
        'nro_servidor_carrera'=>50,'auto_identificacion'=>80,'nacionalidad_indigena'=>100,
        'dir_calle_principal'=>150,'numero_domicilio'=>30,'calle_secundaria'=>150,'parroquia'=>100,
        'canton'=>100,'provincia_dom'=>100,'referencia_domiciliaria'=>250,'tel_domicilio'=>40,
        'tel_celular'=>40,'tel_trabajo'=>40,'extension'=>20,'correo_institucional'=>150,
        'correo_alternativo'=>150,'contacto_nombre'=>180,'contacto_parentesco'=>80,
        'contacto_tel_conv'=>40,'contacto_tel_cel'=>40,'nro_otorgamiento'=>80,'banco'=>120,
        'tipo_cuenta'=>50,'nro_cuenta'=>60,'conyuge_nombres'=>180,'conyuge_tipo_doc'=>50,
        'conyuge_nro_doc'=>40,'conyuge_tipo_relacion'=>80,'conyuge_nivel_instruccion'=>100,
        'conyuge_ocupacion'=>120,'nivel_instruccion'=>100,'institucion_educativa'=>180,
        'tipo_periodo'=>80,'area_conocimiento'=>150,'egresado'=>20,'titulo_academico'=>200,
        'vivienda_tipo'=>30,'vehiculo_marca'=>80,'vehiculo_modelo'=>80,'vehiculo_placa'=>30,
    ];

    public function guardar(array $entrada, string $usuario, string $ip): int
    {
        $datos = [];
        foreach (self::CAMPOS as $campo) {
            $valor = $entrada[$campo] ?? null;
            if (is_string($valor)) {
                $valor = trim($valor);
            }
            if (in_array($campo, self::FECHAS, true) && $valor === '') {
                $valor = null;
            }
            if ($campo === 'vehiculo_valor') {
                $valor = ($valor === '' || $valor === null) ? null : number_format((float)$valor, 2, '.', '');
            }
            $datos[$campo] = $valor === '' ? null : $valor;
        }

        if ((int)($datos['empleado_id'] ?? 0) <= 0) {
            throw new InvalidArgumentException('Debe seleccionar un servidor público.');
        }

        $this->validarDatos($datos, $entrada);

        $id = (int)($entrada['estudio_id'] ?? 0);
        $this->db->beginTransaction();
        try {
            if ($id > 0) {
                $verificar = $this->db->prepare('SELECT empleado_id FROM dbo.th_estudios_socioeconomicos WHERE estudio_id=:id AND estado=1');
                $verificar->execute([':id'=>$id]);
                if ((int)$verificar->fetchColumn() !== (int)$datos['empleado_id']) {
                    throw new InvalidArgumentException('El estudio no corresponde al servidor seleccionado.');
                }
                $asignaciones = [];
                foreach (self::CAMPOS as $campo) {
                    $asignaciones[] = "{$campo}=:{$campo}";
                }
                $sql = 'UPDATE dbo.th_estudios_socioeconomicos SET ' . implode(',', $asignaciones)
                     . ',usuario_modifica=:usuario_modifica,fecha_modificacion=SYSDATETIME(),direccion_ip=:direccion_ip '
                     . 'WHERE estudio_id=:estudio_id';
                $params = $this->parametros($datos);
                $params[':usuario_modifica'] = $usuario;
                $params[':direccion_ip'] = $ip;
                $params[':estudio_id'] = $id;
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                if ($stmt->rowCount() === 0) {
                    throw new RuntimeException('El estudio solicitado no existe.');
                }
                $accion = 'ACTUALIZAR';
            } else {
                $columnas = array_merge(
                    ['codigo_formato','fecha_formato','version_formato'], self::CAMPOS,
                    ['usuario_crea','direccion_ip']
                );
                $marcas = array_map(static fn(string $c): string => ':' . $c, $columnas);
                $sql = 'SET NOCOUNT ON; INSERT dbo.th_estudios_socioeconomicos (' . implode(',', $columnas) . ') VALUES ('
                     . implode(',', $marcas) . '); SELECT CONVERT(INT,SCOPE_IDENTITY()) AS estudio_id;';
                $params = $this->parametros($datos);
                $params[':codigo_formato'] = 'APM-BASC-TH-FO-002';
                $params[':fecha_formato'] = '2019-04-01';
                $params[':version_formato'] = '01';
                $params[':usuario_crea'] = $usuario;
                $params[':direccion_ip'] = $ip;
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
                do {
                    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                } while (!$fila && $stmt->nextRowset());
                $id = (int)($fila['estudio_id'] ?? 0);
                if ($id <= 0) {
                    throw new RuntimeException('No fue posible obtener el identificador del estudio.');
                }
                $accion = 'CREAR';
            }

            $this->reemplazarHijos($id, $entrada);
            $this->reemplazarCapacitaciones($id, $entrada);
            $this->reemplazarExperiencias($id, $entrada);

            $detalle = "Estudio socioeconómico #{$id} del empleado #{$datos['empleado_id']}.";
            $audit = $this->db->prepare("EXEC dbo.sp_th_registrar_auditoria :usuario,'Estudio Socioeconomico',:accion,:detalle,:ip");
            $audit->execute([':usuario'=>$usuario,':accion'=>$accion,':detalle'=>$detalle,':ip'=>$ip]);
            $this->consumirResultados($audit);
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function obtenerPorId(int $id): ?array
    {
        $this->auditarLectura('Estudio Socioeconomico', "Consulta del estudio #{$id}.");
        $stmt = $this->db->prepare(
            'SELECT s.*,e.identificacion,e.nombres nombres_empleado,e.apellidos apellidos_empleado '
          . 'FROM dbo.th_estudios_socioeconomicos s JOIN dbo.th_empleados e ON e.empleado_id=s.empleado_id '
          . 'WHERE s.estudio_id=:id AND s.estado=1'
        );
        $stmt->execute([':id'=>$id]);
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$datos) {
            return null;
        }
        $datos['hijos'] = $this->hijos($id);
        $datos['capacitaciones'] = $this->capacitaciones($id);
        $datos['experiencias'] = $this->experiencias($id);
        return $datos;
    }

    public function ultimoPorEmpleado(int $empleadoId): ?array
    {
        $this->auditarLectura('Estudio Socioeconomico', "Consulta del ultimo estudio del empleado #{$empleadoId}.");
        $stmt = $this->db->prepare('SELECT TOP 1 estudio_id FROM dbo.th_estudios_socioeconomicos WHERE empleado_id=:id AND estado=1 ORDER BY fecha_creacion DESC');
        $stmt->execute([':id'=>$empleadoId]);
        $id = (int)$stmt->fetchColumn();
        return $id > 0 ? $this->obtenerPorId($id) : null;
    }

    public function listar(string $usuario, string $ip): array
    {
        $stmt = $this->db->prepare('EXEC dbo.sp_th_consultar_estudios_socioeconomicos :usuario,:ip,NULL');
        $stmt->execute([':usuario'=>$usuario,':ip'=>$ip]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function auditarImpresion(int $id, string $usuario, string $ip): void
    {
        $stmt = $this->db->prepare("EXEC dbo.sp_th_registrar_auditoria :usuario,'Estudio Socioeconomico','IMPRIMIR',:detalle,:ip");
        $stmt->execute([':usuario'=>$usuario,':detalle'=>"Impresión del estudio socioeconómico #{$id}.",':ip'=>$ip]);
        $this->consumirResultados($stmt);
    }

    private function parametros(array $datos): array
    {
        $salida = [];
        foreach ($datos as $campo=>$valor) {
            $salida[':' . $campo] = $valor;
        }
        return $salida;
    }

    private function reemplazarHijos(int $id, array $e): void
    {
        $this->db->prepare('DELETE dbo.th_estudio_hijos WHERE estudio_id=:id')->execute([':id'=>$id]);
        $sql = 'INSERT dbo.th_estudio_hijos(estudio_id,orden,nombres_apellidos,fecha_nacimiento,tipo_documento,numero_documento,edad,nivel_instruccion,ocupacion) VALUES(:id,:orden,:nombre,:fecha,:tipo,:numero,:edad,:nivel,:ocupacion)';
        $stmt = $this->db->prepare($sql);
        for ($i=1;$i<=3;$i++) {
            $fila = [trim($e["hijo_nombre_{$i}"]??''),trim($e["hijo_fnac_{$i}"]??''),trim($e["hijo_tipo_doc_{$i}"]??''),trim($e["hijo_nro_doc_{$i}"]??''),trim($e["hijo_edad_{$i}"]??''),trim($e["hijo_instruccion_{$i}"]??''),trim($e["hijo_ocupacion_{$i}"]??'')];
            if (!array_filter($fila, static fn($v): bool=>$v!=='')) continue;
            $stmt->execute([':id'=>$id,':orden'=>$i,':nombre'=>$fila[0]?:null,':fecha'=>$fila[1]?:null,':tipo'=>$fila[2]?:null,':numero'=>$fila[3]?:null,':edad'=>$fila[4]?:null,':nivel'=>$fila[5]?:null,':ocupacion'=>$fila[6]?:null]);
        }
    }

    private function reemplazarCapacitaciones(int $id, array $e): void
    {
        $this->db->prepare('DELETE dbo.th_estudio_capacitaciones WHERE estudio_id=:id')->execute([':id'=>$id]);
        $stmt = $this->db->prepare('INSERT dbo.th_estudio_capacitaciones(estudio_id,orden,evento,tipo_evento,auspiciante,tipo_certificado,certificado_por,fecha_inicio) VALUES(:id,:orden,:evento,:tipo,:auspiciante,:tipo_cert,:certificado,:fecha)');
        for ($i=1;$i<=3;$i++) {
            $fila=[trim($e["cap{$i}_evento"]??''),trim($e["cap{$i}_tipo"]??''),trim($e["cap{$i}_auspiciante"]??''),trim($e["cap{$i}_tipo_cert"]??''),trim($e["cap{$i}_certificado_por"]??''),trim($e["cap{$i}_fecha_inicio"]??'')];
            if (!array_filter($fila, static fn($v): bool=>$v!=='')) continue;
            $stmt->execute([':id'=>$id,':orden'=>$i,':evento'=>$fila[0]?:null,':tipo'=>$fila[1]?:null,':auspiciante'=>$fila[2]?:null,':tipo_cert'=>$fila[3]?:null,':certificado'=>$fila[4]?:null,':fecha'=>$fila[5]?:null]);
        }
    }

    private function reemplazarExperiencias(int $id, array $e): void
    {
        $this->db->prepare('DELETE dbo.th_estudio_experiencias WHERE estudio_id=:id')->execute([':id'=>$id]);
        $stmt=$this->db->prepare('INSERT dbo.th_estudio_experiencias(estudio_id,orden,institucion,tipo_institucion,unidad_administrativa,cargo,antiguedad,jefe_inmediato,telefono,fecha_ingreso,motivo_ingreso,fecha_retiro,motivo_retiro) VALUES(:id,:orden,:institucion,:tipo,:unidad,:cargo,:antiguedad,:jefe,:telefono,:ingreso,:motivo_ingreso,:retiro,:motivo_retiro)');
        for($i=1;$i<=3;$i++){
            $fila=[trim($e["exp_institucion_{$i}"]??''),trim($e["exp_tipo_{$i}"]??''),trim($e["exp_unidad_{$i}"]??''),trim($e["exp_cargo_{$i}"]??''),trim($e["exp_antiguedad_{$i}"]??''),trim($e["exp_jefe_{$i}"]??''),trim($e["exp_tel_{$i}"]??''),trim($e["exp_fecha_ingreso_{$i}"]??''),trim($e["exp_motivo_ingreso_{$i}"]??''),trim($e["exp_fecha_retiro_{$i}"]??''),trim($e["exp_motivo_retiro_{$i}"]??'')];
            if(!array_filter($fila,static fn($v):bool=>$v!=='')) continue;
            $stmt->execute([':id'=>$id,':orden'=>$i,':institucion'=>$fila[0]?:null,':tipo'=>$fila[1]?:null,':unidad'=>$fila[2]?:null,':cargo'=>$fila[3]?:null,':antiguedad'=>$fila[4]?:null,':jefe'=>$fila[5]?:null,':telefono'=>$fila[6]?:null,':ingreso'=>$fila[7]?:null,':motivo_ingreso'=>$fila[8]?:null,':retiro'=>$fila[9]?:null,':motivo_retiro'=>$fila[10]?:null]);
        }
    }

    private function hijos(int $id): array { return $this->coleccion('SELECT * FROM dbo.th_estudio_hijos WHERE estudio_id=:id ORDER BY orden',$id); }
    private function capacitaciones(int $id): array { return $this->coleccion('SELECT * FROM dbo.th_estudio_capacitaciones WHERE estudio_id=:id ORDER BY orden',$id); }
    private function experiencias(int $id): array { return $this->coleccion('SELECT * FROM dbo.th_estudio_experiencias WHERE estudio_id=:id ORDER BY orden',$id); }
    private function coleccion(string $sql,int $id): array { $s=$this->db->prepare($sql);$s->execute([':id'=>$id]);return $s->fetchAll(PDO::FETCH_ASSOC); }
    private function consumirResultados(PDOStatement $stmt): void { while($stmt->nextRowset()){} }

    private function validarDatos(array $datos, array $entrada): void
    {
        $empleado = $this->db->prepare('SELECT COUNT_BIG(*) FROM dbo.th_empleados WHERE empleado_id=:id');
        $empleado->execute([':id'=>(int)$datos['empleado_id']]);
        if ((int)$empleado->fetchColumn() !== 1) {
            throw new InvalidArgumentException('El servidor seleccionado no existe en el directorio.');
        }

        foreach (self::LIMITES as $campo=>$limite) {
            if ($datos[$campo] !== null && mb_strlen((string)$datos[$campo]) > $limite) {
                throw new InvalidArgumentException("El campo {$campo} supera el máximo de {$limite} caracteres.");
            }
        }
        foreach (self::FECHAS as $campo) {
            $this->validarFecha($datos[$campo] ?? null, $campo);
        }
        foreach (['correo_institucional','correo_alternativo'] as $campo) {
            if ($datos[$campo] !== null && !filter_var($datos[$campo], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Revise los correos electrónicos ingresados.');
            }
        }
        foreach (['tel_domicilio','tel_celular','tel_trabajo','contacto_tel_conv','contacto_tel_cel'] as $campo) {
            $valor=(string)($datos[$campo] ?? '');
            if ($valor !== '' && strtoupper($valor) !== 'S/N' && !preg_match('/^[0-9+() .-]{5,40}$/', $valor)) {
                throw new InvalidArgumentException('Revise los números telefónicos ingresados.');
            }
        }
        if ($datos['vehiculo_valor'] !== null && ((float)$datos['vehiculo_valor'] < 0 || (float)$datos['vehiculo_valor'] > 9999999999.99)) {
            throw new InvalidArgumentException('El valor del vehículo no es válido.');
        }

        for ($i=1;$i<=3;$i++) {
            $this->validarFecha($entrada["hijo_fnac_{$i}"] ?? null, "fecha de nacimiento del hijo {$i}");
            $this->validarFecha($entrada["cap{$i}_fecha_inicio"] ?? null, "fecha de capacitación {$i}");
            $this->validarFecha($entrada["exp_fecha_ingreso_{$i}"] ?? null, "fecha de ingreso laboral {$i}");
            $this->validarFecha($entrada["exp_fecha_retiro_{$i}"] ?? null, "fecha de retiro laboral {$i}");
        }
    }

    private function validarFecha($valor, string $campo): void
    {
        if ($valor === null || trim((string)$valor) === '') return;
        $fecha=DateTimeImmutable::createFromFormat('!Y-m-d', (string)$valor);
        $errores=DateTimeImmutable::getLastErrors();
        if (!$fecha || ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))) {
            throw new InvalidArgumentException("La {$campo} no tiene un formato válido.");
        }
    }
}
