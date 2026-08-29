<?php

final class AdminModel extends Model
{
    public function usuarios(): array
    {
        $this->auditarLectura('Usuarios', 'Consulta del directorio de cuentas de acceso.');
        return $this->db->query(
            "SELECT u.usuario_id id,u.usuario,u.nombre,u.correo,u.empleado_id,u.estado,u.ultimo_acceso,
                    u.debe_cambiar_clave,u.mfa_habilitado,u.mfa_activado_en,r.nombre_rol rol,r.rol_id
             FROM dbo.th_usuarios_sistema u JOIN dbo.th_roles r ON r.rol_id=u.rol_id
             ORDER BY u.nombre"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function empleadosDisponibles(): array
    {
        return $this->db->query(
            "SELECT e.empleado_id,e.identificacion,CONCAT(e.apellidos,' ',e.nombres) nombre,e.correo_institucional
             FROM dbo.th_empleados e LEFT JOIN dbo.th_usuarios_sistema u ON u.empleado_id=e.empleado_id
             WHERE e.estado=1 AND u.usuario_id IS NULL ORDER BY e.apellidos,e.nombres"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function roles(): array
    {
        $this->auditarLectura('Roles', 'Consulta de roles y matriz de permisos.');
        return $this->db->query(
            'SELECT r.rol_id id,r.nombre_rol nombre,r.estado,COUNT(u.usuario_id) usuarios
             FROM dbo.th_roles r LEFT JOIN dbo.th_usuarios_sistema u ON u.rol_id=r.rol_id
             GROUP BY r.rol_id,r.nombre_rol,r.estado ORDER BY r.rol_id'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function modulosPermisos(): array
    {
        return $this->db->query(
            'SELECT m.modulo_id,m.codigo_modulo,m.nombre_modulo,p.rol_id,p.puede_visualizar,p.puede_crear,p.puede_editar,p.puede_eliminar
             FROM dbo.th_modulos m CROSS JOIN dbo.th_roles r
             LEFT JOIN dbo.th_permisos_rol p ON p.modulo_id=m.modulo_id AND p.rol_id=r.rol_id
             ORDER BY m.nombre_modulo,r.rol_id'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crearUsuario(array $d): array
    {
        $usuario = strtolower(trim((string)($d['usuario'] ?? '')));
        $correo = trim((string)($d['correo'] ?? ''));
        $nombre = trim((string)($d['nombre'] ?? ''));
        $clave = (string)($d['password'] ?? '');
        $rol = (int)($d['rol_id'] ?? 0);
        $empleado = (int)($d['empleado_id'] ?? 0) ?: null;
        // El navegador hashea la clave (SHA-256) antes de enviarla -- el
        // atributo pattern del <input> ya exige mayúscula/minúscula/número/
        // símbolo/12+ caracteres del lado cliente, ANTES de hashear. Acá solo
        // queda confirmar que sí llegó un hash real, no revisar su "forma".
        $claveSegura = (bool)preg_match('/^[a-f0-9]{64}$/', $clave);
        if (!preg_match('/^[a-z0-9._-]{4,50}$/',$usuario) || !filter_var($correo,FILTER_VALIDATE_EMAIL)
            || $nombre==='' || !$claveSegura || $rol<=0) {
            return ['exito'=>0,'mensaje'=>'Revise usuario, correo, rol y clave. La clave no llegó correctamente -- recargue la página e intente de nuevo.'];
        }
        try {
            $stmt=$this->db->prepare('EXEC dbo.sp_th_crear_usuario_sistema :usuario,:hash,:correo,:nombre,:empleado,:rol');
            $stmt->execute([':usuario'=>$usuario,':hash'=>Auth::hashPasswordSecure($clave),':correo'=>$correo,':nombre'=>$nombre,':empleado'=>$empleado,':rol'=>$rol]);
            $this->auditarCambio('Usuarios','CREAR',"Creo la cuenta {$usuario}.");
            return ['exito'=>1,'mensaje'=>'Cuenta creada. El usuario debe cambiar la clave inicial.'];
        } catch(PDOException $e) {
            Conexion::registrarErrorLog($e,'admin',false);
            return ['exito'=>0,'mensaje'=>'No fue posible crear la cuenta; verifique que usuario y empleado no estén registrados.'];
        }
    }

    public function cambiarEstadoUsuario(int $id, bool $estado): array
    {
        if ($id===(int)(Auth::user()['sub']??0) && !$estado) return ['exito'=>0,'mensaje'=>'No puede desactivar su propia cuenta.'];
        $stmt=$this->db->prepare('UPDATE dbo.th_usuarios_sistema SET estado=:estado,token_version=token_version+1 WHERE usuario_id=:id');
        $stmt->execute([':estado'=>$estado?1:0,':id'=>$id]);
        $this->auditarCambio('Usuarios','CAMBIAR_ESTADO',"Cuenta #{$id}: ".($estado?'activa':'inactiva').'.');
        return ['exito'=>$stmt->rowCount()>0?1:0,'mensaje'=>'Estado de la cuenta actualizado.'];
    }

    public function restablecerClave(int $id): array
    {
        $clave='Apm!'.bin2hex(random_bytes(6));
        // hash('sha256', $clave) primero: esta clave temporal NUNCA pasa por
        // el navegador antes de guardarse (el admin la lee de $claveTemporal
        // y se la comunica al usuario), pero cuando ESE usuario la escriba
        // en el login, js/password-hash.js SÍ la va a hashear en cliente --
        // hay que simular ese mismo paso acá para que el hash final calce.
        $stmt=$this->db->prepare('UPDATE dbo.th_usuarios_sistema SET password_hash=:hash,debe_cambiar_clave=1,token_version=token_version+1,intentos_fallidos=0,bloqueado_hasta=NULL WHERE usuario_id=:id');
        $stmt->execute([':hash'=>Auth::hashPasswordSecure(hash('sha256', $clave)),':id'=>$id]);
        $this->auditarCambio('Usuarios','RESTABLECER_CLAVE',"Restablecio credenciales de la cuenta #{$id}.");
        return ['exito'=>$stmt->rowCount()>0?1:0,'mensaje'=>'Clave temporal generada.','clave_temporal'=>$clave];
    }

    public function restablecerMfa(int $id): array
    {
        $stmt=$this->db->prepare('UPDATE dbo.th_usuarios_sistema SET mfa_habilitado=0,mfa_secreto_enc=NULL,mfa_activado_en=NULL,mfa_ultimo_paso=NULL,token_version=token_version+1 WHERE usuario_id=:id AND mfa_habilitado=1');
        $stmt->execute([':id'=>$id]);$this->auditarCambio('Usuarios','RESTABLECER_MFA',"Restableció el segundo factor de la cuenta #{$id}.");
        return ['exito'=>$stmt->rowCount()>0?1:0,'mensaje'=>$stmt->rowCount()>0?'Segundo factor restablecido; las sesiones del usuario fueron cerradas.':'La cuenta no tenía doble autenticación activa.'];
    }

    public function crearRol(string $nombre): array
    {
        $nombre=trim(preg_replace('/\s+/', ' ', $nombre) ?? '');
        if (mb_strlen($nombre)<3 || mb_strlen($nombre)>80) {
            return ['exito'=>0,'mensaje'=>'El nombre del rol debe tener entre 3 y 80 caracteres.'];
        }
        $this->db->beginTransaction();
        try {
            $stmt=$this->db->prepare('INSERT dbo.th_roles(nombre_rol,estado) OUTPUT INSERTED.rol_id VALUES(:nombre,1)');
            $stmt->execute([':nombre'=>$nombre]);
            $rolId=(int)$stmt->fetchColumn();
            $permisos=$this->db->prepare('INSERT dbo.th_permisos_rol(rol_id,modulo_id,puede_visualizar,puede_crear,puede_editar,puede_eliminar) SELECT :rol,modulo_id,0,0,0,0 FROM dbo.th_modulos');
            $permisos->execute([':rol'=>$rolId]);
            $this->auditarCambio('Roles','CREAR',"Creó el rol {$nombre} (#{$rolId}).");
            $this->db->commit();
            return ['exito'=>1,'mensaje'=>'Rol creado. Configure ahora sus permisos.'];
        } catch(Throwable $e) {
            if($this->db->inTransaction())$this->db->rollBack();
            Conexion::registrarErrorLog($e,'admin',false);
            return ['exito'=>0,'mensaje'=>'No fue posible crear el rol; verifique que el nombre no esté repetido.'];
        }
    }

    public function cambiarEstadoRol(int $rolId, bool $estado): array
    {
        if ($rolId===1) return ['exito'=>0,'mensaje'=>'El rol Super Administrador no puede desactivarse.'];
        $usuarios=$this->db->prepare('SELECT COUNT_BIG(*) FROM dbo.th_usuarios_sistema WHERE rol_id=:rol AND estado=1');
        $usuarios->execute([':rol'=>$rolId]);
        if (!$estado && (int)$usuarios->fetchColumn()>0) {
            return ['exito'=>0,'mensaje'=>'Desactive o reasigne primero las cuentas activas de este rol.'];
        }
        $stmt=$this->db->prepare('UPDATE dbo.th_roles SET estado=:estado WHERE rol_id=:rol');
        $stmt->execute([':estado'=>$estado?1:0,':rol'=>$rolId]);
        $this->db->prepare('UPDATE dbo.th_usuarios_sistema SET token_version=token_version+1 WHERE rol_id=:rol')->execute([':rol'=>$rolId]);
        $this->auditarCambio('Roles','CAMBIAR_ESTADO',"Rol #{$rolId}: ".($estado?'activo':'inactivo').'.');
        return ['exito'=>$stmt->rowCount()>0?1:0,'mensaje'=>'Estado del rol actualizado.'];
    }

    public function guardarPermisos(int $rolId, array $matriz): array
    {
        if ($rolId===1) return ['exito'=>0,'mensaje'=>'El rol Super Administrador mantiene acceso total.'];
        $this->db->beginTransaction();
        try {
            $stmt=$this->db->prepare('UPDATE dbo.th_permisos_rol SET puede_visualizar=:v,puede_crear=:c,puede_editar=:e,puede_eliminar=:d WHERE rol_id=:rol AND modulo_id=:modulo');
            $cambios = [];
            foreach ($this->db->query('SELECT modulo_id, codigo_modulo FROM dbo.th_modulos')->fetchAll(PDO::FETCH_ASSOC) as $m) {
                $moduloId = (int)$m['modulo_id'];
                $p=$matriz[(string)$moduloId]??[];
                $v = isset($p['visualizar'])?1:0; $c = isset($p['crear'])?1:0;
                $e = isset($p['editar'])?1:0;     $d = isset($p['eliminar'])?1:0;
                $stmt->execute([':v'=>$v,':c'=>$c,':e'=>$e,':d'=>$d,':rol'=>$rolId,':modulo'=>$moduloId]);
                $cambios[$m['codigo_modulo']] = [$v,$c,$e,$d];
            }
            $this->db->prepare('UPDATE dbo.th_usuarios_sistema SET token_version=token_version+1 WHERE rol_id=:rol')->execute([':rol'=>$rolId]);
            $this->auditarCambio('Roles','ACTUALIZAR_PERMISOS',"Actualizo permisos del rol #{$rolId}.");
            $this->db->commit();
            $this->sincronizarHaciaCentral($rolId, $cambios);
            return ['exito'=>1,'mensaje'=>'Permisos guardados y sesiones del rol revocadas.'];
        } catch(Throwable $e) {
            if($this->db->inTransaction())$this->db->rollBack();
            Conexion::registrarErrorLog($e,'admin',false);
            return ['exito'=>0,'mensaje'=>'No fue posible guardar la matriz de permisos.'];
        }
    }

    /**
     * Sync bidireccional Fase 1 (sistema central de permisos, 2026-08-11):
     * refleja el cambio de permisos hacia CORE_Permisos_Nodo del portal, si
     * este rol de TH tiene mapeo en CORE_Roles_Modulo_Map. Traduce las 4
     * columnas independientes de TH al nivel_crud jerárquico 0-4 del
     * central -- sin pérdida si la combinación ya es jerárquica (v,c,e,d
     * en orden creciente), con pérdida documentada (se guarda el prefijo
     * contiguo más alto) si no lo es, y se deja constancia en la auditoría
     * de TH en ese caso. No bloquea el guardado si el portal no está
     * disponible -- ver catch en guardarPermisos().
     */
    private function sincronizarHaciaCentral(int $rolIdTh, array $cambiosPorCodigo): void
    {
        $rolPortal = $this->rolPortalDesdeTh($rolIdTh);
        if ($rolPortal === null) return;

        $codigoAOpcion = [
            'dashboard'=>1,'directorio'=>2,'empleados'=>3,'acciones'=>4,'movimientos'=>5,
            'socioeconomico'=>6,'biblioteca'=>7,'maestros'=>8,'usuarios'=>9,'roles'=>10,
            'politicas'=>11,'auditoria'=>12,'reportes'=>13,'prototipos'=>14,
        ];

        try {
            $conn = require dirname(__DIR__, 5) . '/config/connections.php';
            $opts = ['CharacterSet' => 'UTF-8', 'TrustServerCertificate' => (bool)($conn['options']['trust_cert'] ?? true)];
            if (!empty($conn['credentials']['user'])) { $opts['UID'] = $conn['credentials']['user']; $opts['PWD'] = $conn['credentials']['pass']; }
            $opts['Database'] = $conn['databases']['portal']['name'];
            $c = @sqlsrv_connect($conn['databases']['portal']['server'] ?? $conn['server_default'], $opts);
            if ($c === false) {
                $this->auditarCambio('Roles','SYNC_FALLO',"No se pudo conectar al portal para sincronizar el rol #{$rolIdTh}.");
                return;
            }

            foreach ($cambiosPorCodigo as $codigo => [$v,$cr,$e,$d]) {
                $opcion = $codigoAOpcion[$codigo] ?? null;
                if ($opcion === null) continue;

                // nivel = el prefijo contiguo más largo empezando en "visualizar"
                $nivel = 0;
                if ($v) { $nivel = 1; if ($cr) { $nivel = 2; if ($e) { $nivel = 3; if ($d) { $nivel = 4; } } } }
                // no contiguo: alguna bandera posterior al corte de arriba seguía en 1
                $noContiguo = ($cr && !$v) || ($e && !$cr) || ($d && !$e);

                sqlsrv_query($c,
                    'MERGE dbo.CORE_Permisos_Nodo AS t
                     USING (SELECT ? AS id_rol, 11 AS id_modulo, ? AS opcion, 0 AS items, 0 AS subitems, ? AS nivel_crud) AS s
                     ON t.id_rol=s.id_rol AND t.id_modulo=s.id_modulo AND t.opcion=s.opcion AND t.items=s.items AND t.subitems=s.subitems
                     WHEN MATCHED THEN UPDATE SET nivel_crud=s.nivel_crud, acceso=1, estado=1
                     WHEN NOT MATCHED THEN INSERT (id_rol,id_modulo,opcion,items,subitems,nivel_crud,acceso,estado,fecha_asignacion)
                         VALUES (s.id_rol,s.id_modulo,s.opcion,s.items,s.subitems,s.nivel_crud,1,1,SYSDATETIME());',
                    [$rolPortal, $opcion, $nivel]
                );
                if ($noContiguo) {
                    $this->auditarCambio('Roles','SYNC_PERMISO_NO_CONTIGUO', "Módulo {$codigo}: combinación no jerárquica simplificada a nivel {$nivel} al sincronizar hacia el portal.");
                }
            }
            sqlsrv_close($c);
        } catch (Throwable $e) {
            Conexion::registrarErrorLog($e, 'admin', false);
        }
    }

    /** Resuelve el id_rol del portal mapeado a este rol de TH (CORE_Roles_Modulo_Map), o null si no hay mapeo. */
    private function rolPortalDesdeTh(int $rolIdTh): ?int
    {
        try {
            $conn = require dirname(__DIR__, 5) . '/config/connections.php';
            $opts = ['CharacterSet' => 'UTF-8', 'TrustServerCertificate' => (bool)($conn['options']['trust_cert'] ?? true)];
            if (!empty($conn['credentials']['user'])) { $opts['UID'] = $conn['credentials']['user']; $opts['PWD'] = $conn['credentials']['pass']; }
            $opts['Database'] = $conn['databases']['portal']['name'];
            $c = @sqlsrv_connect($conn['databases']['portal']['server'] ?? $conn['server_default'], $opts);
            if ($c === false) return null;
            $stmt = sqlsrv_query($c, 'SELECT id_rol_portal FROM dbo.CORE_Roles_Modulo_Map WHERE id_modulo=11 AND id_rol_externo=?', [$rolIdTh]);
            $row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : false;
            sqlsrv_close($c);
            return $row ? (int)$row['id_rol_portal'] : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function documentos(): array
    {
        $this->auditarLectura('Politicas', 'Consulta del repositorio documental.');
        return $this->db->query('SELECT documento_id id,titulo,categoria,version,descripcion,nombre_archivo,mime_type,tamano_bytes,vigente,descargas,usuario_crea subido_por,fecha_creacion fecha_subida FROM dbo.th_politicas_documentos ORDER BY fecha_creacion DESC')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registrarDocumento(array $d): int
    {
        $stmt=$this->db->prepare('INSERT dbo.th_politicas_documentos(titulo,categoria,version,descripcion,nombre_archivo,ruta_privada,mime_type,tamano_bytes,usuario_crea) OUTPUT INSERTED.documento_id VALUES(:titulo,:categoria,:version,:descripcion,:nombre,:ruta,:mime,:tamano,:usuario)');
        $stmt->execute($d);
        $id=(int)$stmt->fetchColumn();
        $this->auditarCambio('Politicas','PUBLICAR',"Publico el documento #{$id}.");
        return $id;
    }

    public function documento(int $id): ?array
    {
        $stmt=$this->db->prepare('SELECT * FROM dbo.th_politicas_documentos WHERE documento_id=:id AND vigente=1');$stmt->execute([':id'=>$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)?:null;
    }

    public function registrarDescarga(int $id): void
    {
        $this->db->prepare('UPDATE dbo.th_politicas_documentos SET descargas=descargas+1 WHERE documento_id=:id')->execute([':id'=>$id]);
        $this->auditarCambio('Politicas','DESCARGAR',"Descargo el documento #{$id}.");
    }

    public function retirarDocumento(int $id): array
    {
        $stmt=$this->db->prepare('UPDATE dbo.th_politicas_documentos SET vigente=0 WHERE documento_id=:id AND vigente=1');
        $stmt->execute([':id'=>$id]);
        if ($stmt->rowCount()===0) return ['exito'=>0,'mensaje'=>'El documento no existe o ya fue retirado.'];
        $this->auditarCambio('Politicas','RETIRAR',"Retiró la vigencia del documento #{$id}.");
        return ['exito'=>1,'mensaje'=>'Documento retirado del repositorio vigente.'];
    }

    private function auditarCambio(string $modulo,string $accion,string $detalle): void
    {
        $stmt=$this->db->prepare('EXEC dbo.sp_th_registrar_auditoria :usuario,:modulo,:accion,:detalle,:ip');
        $stmt->execute([':usuario'=>Auth::username(),':modulo'=>$modulo,':accion'=>$accion,':detalle'=>$detalle,':ip'=>Auth::clientIp()]);
        while($stmt->nextRowset()){}
    }
}
