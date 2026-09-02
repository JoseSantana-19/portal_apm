USE Talento_Humano;
GO
SET NOCOUNT ON;
SET XACT_ABORT ON;
GO

/* 1. Semántica explícita: una fecha final ya no se confunde con permanencia. */
IF COL_LENGTH('dbo.th_acciones_personal','modalidad_vigencia') IS NULL
    ALTER TABLE dbo.th_acciones_personal ADD modalidad_vigencia VARCHAR(12) NULL;
GO
UPDATE dbo.th_acciones_personal
SET modalidad_vigencia=CASE WHEN NULLIF(tipo_novedad_jornada,'') IS NOT NULL THEN 'TEMPORAL' ELSE 'PERMANENTE' END
WHERE modalidad_vigencia IS NULL;
ALTER TABLE dbo.th_acciones_personal ALTER COLUMN modalidad_vigencia VARCHAR(12) NOT NULL;
IF OBJECT_ID('dbo.CK_th_accion_modalidad_vigencia','C') IS NULL
    ALTER TABLE dbo.th_acciones_personal ADD CONSTRAINT CK_th_accion_modalidad_vigencia CHECK(modalidad_vigencia IN('PERMANENTE','TEMPORAL'));
GO

/* 2. Vigencias de área/cargo y demás condiciones. La situación permanente
      queda intacta durante una asignación temporal y reaparece por fecha. */
IF OBJECT_ID('dbo.th_vigencias_laborales','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_vigencias_laborales(
        vigencia_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_vigencias_laborales PRIMARY KEY,
        accion_id INT NOT NULL,empleado_id INT NOT NULL,tipo_vigencia VARCHAR(12) NOT NULL,
        fecha_desde DATE NOT NULL,fecha_hasta DATE NULL,estado VARCHAR(20) NOT NULL,
        original_unidad_id INT NULL,original_puesto_id INT NULL,original_remuneracion DECIMAL(10,2) NULL,
        original_proceso NVARCHAR(150) NULL,original_nivel_gestion NVARCHAR(150) NULL,original_lugar_trabajo NVARCHAR(150) NULL,
        original_grupo_ocupacional NVARCHAR(150) NULL,original_grado NVARCHAR(50) NULL,original_partida NVARCHAR(100) NULL,
        original_jornada NVARCHAR(30) NULL,original_horas_jornada DECIMAL(4,1) NULL,original_tipo_contrato NVARCHAR(100) NULL,
        propuesta_unidad_id INT NULL,propuesta_puesto_id INT NULL,propuesta_remuneracion DECIMAL(10,2) NULL,
        propuesta_proceso NVARCHAR(150) NULL,propuesta_nivel_gestion NVARCHAR(150) NULL,propuesta_lugar_trabajo NVARCHAR(150) NULL,
        propuesta_grupo_ocupacional NVARCHAR(150) NULL,propuesta_grado NVARCHAR(50) NULL,propuesta_partida NVARCHAR(100) NULL,
        propuesta_jornada NVARCHAR(30) NULL,propuesta_horas_jornada DECIMAL(4,1) NULL,propuesta_tipo_contrato NVARCHAR(100) NULL,
        usuario_crea VARCHAR(50) NOT NULL,fecha_creacion DATETIME2(3) NOT NULL CONSTRAINT DF_th_vigencia_creacion DEFAULT(SYSDATETIME()),
        fecha_aplicacion DATETIME2(3) NULL,fecha_finalizacion DATETIME2(3) NULL,observaciones NVARCHAR(500) NULL,
        CONSTRAINT UQ_th_vigencia_accion UNIQUE(accion_id),
        CONSTRAINT FK_th_vigencia_accion FOREIGN KEY(accion_id) REFERENCES dbo.th_acciones_personal(accion_id),
        CONSTRAINT FK_th_vigencia_empleado FOREIGN KEY(empleado_id) REFERENCES dbo.th_empleados(empleado_id),
        CONSTRAINT FK_th_vigencia_unidad_original FOREIGN KEY(original_unidad_id) REFERENCES dbo.th_unidades_organizacionales(unidad_id),
        CONSTRAINT FK_th_vigencia_puesto_original FOREIGN KEY(original_puesto_id) REFERENCES dbo.th_puestos(puesto_id),
        CONSTRAINT FK_th_vigencia_unidad_propuesta FOREIGN KEY(propuesta_unidad_id) REFERENCES dbo.th_unidades_organizacionales(unidad_id),
        CONSTRAINT FK_th_vigencia_puesto_propuesto FOREIGN KEY(propuesta_puesto_id) REFERENCES dbo.th_puestos(puesto_id),
        CONSTRAINT CK_th_vigencia_tipo CHECK(tipo_vigencia IN('PERMANENTE','TEMPORAL')),
        CONSTRAINT CK_th_vigencia_estado CHECK(estado IN('PROGRAMADA','VIGENTE','APLICADA','FINALIZADA','CANCELADA','ERROR')),
        CONSTRAINT CK_th_vigencia_fechas CHECK((tipo_vigencia='PERMANENTE' AND fecha_hasta IS NULL) OR (tipo_vigencia='TEMPORAL' AND fecha_hasta>=fecha_desde))
    );
    CREATE INDEX IX_th_vigencia_empleado_fechas ON dbo.th_vigencias_laborales(empleado_id,fecha_desde,fecha_hasta) INCLUDE(estado,tipo_vigencia,accion_id);
END;
GO

IF COL_LENGTH('dbo.th_jornadas_especiales','jornada_base') IS NULL ALTER TABLE dbo.th_jornadas_especiales ADD jornada_base NVARCHAR(30) NULL;
IF COL_LENGTH('dbo.th_jornadas_especiales','horas_base') IS NULL ALTER TABLE dbo.th_jornadas_especiales ADD horas_base DECIMAL(4,1) NULL;
IF COL_LENGTH('dbo.th_jornadas_especiales','jornada_temporal') IS NULL ALTER TABLE dbo.th_jornadas_especiales ADD jornada_temporal NVARCHAR(30) NULL;
GO
IF OBJECT_ID('dbo.CK_th_jornada_esp_horas','C') IS NOT NULL ALTER TABLE dbo.th_jornadas_especiales DROP CONSTRAINT CK_th_jornada_esp_horas;
ALTER TABLE dbo.th_jornadas_especiales ADD CONSTRAINT CK_th_jornada_esp_horas CHECK((UPPER(tipo_novedad)='MATERNIDAD' AND horas_diarias=0) OR (horas_diarias>0 AND horas_diarias<=24));
GO
UPDATE j SET jornada_base=COALESCE(j.jornada_base,e.jornada,'Completa'),horas_base=COALESCE(j.horas_base,e.horas_jornada,8),
    jornada_temporal=COALESCE(j.jornada_temporal,'Especial'),
    estado=CASE WHEN CONVERT(date,SYSUTCDATETIME() AT TIME ZONE 'UTC' AT TIME ZONE 'SA Pacific Standard Time')<j.fecha_desde THEN 'PROGRAMADA'
               WHEN CONVERT(date,SYSUTCDATETIME() AT TIME ZONE 'UTC' AT TIME ZONE 'SA Pacific Standard Time')>j.fecha_hasta THEN 'FINALIZADA' ELSE 'VIGENTE' END
FROM dbo.th_jornadas_especiales j JOIN dbo.th_empleados e ON e.empleado_id=j.empleado_id
WHERE j.estado<>'CANCELADA';
GO

CREATE OR ALTER FUNCTION dbo.fn_th_fecha_institucional()
RETURNS DATE
AS
BEGIN
    RETURN CONVERT(date,SYSUTCDATETIME() AT TIME ZONE 'UTC' AT TIME ZONE 'SA Pacific Standard Time');
END;
GO

/* 3. Fuente única de la situación que debe mostrarse hoy. El cálculo por fecha
      hace que la restitución funcione aunque el servidor se haya apagado. */
CREATE OR ALTER VIEW dbo.vw_th_situacion_laboral_efectiva
AS
SELECT e.empleado_id,
    COALESCE(v.propuesta_unidad_id,e.unidad_id) unidad_id,
    COALESCE(v.propuesta_puesto_id,e.puesto_id) puesto_id,
    COALESCE(v.propuesta_remuneracion,e.sueldo_rmu) sueldo_rmu,
    COALESCE(v.propuesta_proceso,e.proceso_institucional) proceso_institucional,
    COALESCE(v.propuesta_nivel_gestion,e.nivel_gestion) nivel_gestion,
    COALESCE(v.propuesta_lugar_trabajo,e.lugar_trabajo) lugar_trabajo,
    COALESCE(v.propuesta_grupo_ocupacional,e.grupo_ocupacional) grupo_ocupacional,
    COALESCE(v.propuesta_grado,e.grado_laboral) grado_laboral,
    COALESCE(v.propuesta_partida,e.partida_individual) partida_individual,
    COALESCE(j.jornada_temporal,v.propuesta_jornada,e.jornada) jornada,
    COALESCE(j.horas_diarias,v.propuesta_horas_jornada,e.horas_jornada) horas_jornada,
    COALESCE(v.propuesta_tipo_contrato,e.tipo_contrato) tipo_contrato,
    v.vigencia_id,v.tipo_vigencia,v.fecha_desde vigencia_desde,v.fecha_hasta vigencia_hasta,
    j.jornada_especial_id,j.tipo_novedad,j.fecha_desde jornada_desde,j.fecha_hasta jornada_hasta,
    CONVERT(bit,IIF(v.vigencia_id IS NOT NULL OR j.jornada_especial_id IS NOT NULL,1,0)) situacion_temporal
FROM dbo.th_empleados e
OUTER APPLY(
    SELECT TOP(1) x.* FROM dbo.th_vigencias_laborales x
    WHERE x.empleado_id=e.empleado_id
      AND ((x.tipo_vigencia='TEMPORAL' AND x.estado IN('PROGRAMADA','VIGENTE') AND dbo.fn_th_fecha_institucional() BETWEEN x.fecha_desde AND x.fecha_hasta)
        OR (x.tipo_vigencia='PERMANENTE' AND x.estado='PROGRAMADA' AND dbo.fn_th_fecha_institucional()>=x.fecha_desde))
    ORDER BY x.fecha_desde DESC,x.vigencia_id DESC
)v
OUTER APPLY(
    SELECT TOP(1) x.* FROM dbo.th_jornadas_especiales x
    WHERE x.empleado_id=e.empleado_id AND x.estado IN('PROGRAMADA','VIGENTE')
      AND dbo.fn_th_fecha_institucional() BETWEEN x.fecha_desde AND x.fecha_hasta
    ORDER BY x.fecha_desde DESC,x.jornada_especial_id DESC
)j;
GO

CREATE OR ALTER VIEW dbo.vw_th_directorio_empleados
AS
SELECT e.empleado_id id,e.empleado_id,ROW_NUMBER() OVER(ORDER BY e.apellidos,e.nombres,e.empleado_id) numero_registro,
    e.tipo_identificacion,e.identificacion cedula,e.apellidos,e.nombres,LTRIM(RTRIM(CONCAT(e.apellidos,' ',e.nombres))) apellidos_nombres,
    s.unidad_id,s.puesto_id,ISNULL(p.nombre_puesto,'') cargo,ISNULL(u.nombre_unidad,'') direccion_area,e.correo_institucional,e.correo_personal,
    e.estado,e.estado_fecha_efectiva,e.estado_motivo,e.estado_origen,e.estado_accion_id,ISNULL(e.cargas_familiares,0) cargas_familiares,
    e.tipo_cuenta_bancaria,e.numero_cuenta_bancaria,e.institucion_bancaria,s.tipo_contrato,s.sueldo_rmu remuneracion_mensual,s.sueldo_rmu,
    e.fecha_ingreso,e.fecha_salida,e.fecha_nacimiento,e.sexo,e.estado_civil,e.nacionalidad,e.tipo_sangre,e.telefono_movil,e.telefono_convencional,
    e.ciudad_residencia,e.direccion_domiciliaria,e.contacto_emergencia,e.emergencia_relacion,e.tel_emergencia,e.nivel_estudio,e.titulo,
    s.jornada,s.horas_jornada,e.condicion_especial,e.tipo_discapacidad,e.porcentaje_discapacidad,e.cuenta_bancaria,e.codigo_iess,e.cod_emplea,
    e.num_iess,e.ruta_foto,e.observaciones,s.proceso_institucional,s.nivel_gestion,s.lugar_trabajo,s.grupo_ocupacional,s.grado_laboral,s.partida_individual,
    s.situacion_temporal,s.vigencia_id,s.vigencia_desde,s.vigencia_hasta,s.jornada_especial_id,s.tipo_novedad,s.jornada_desde,s.jornada_hasta
FROM dbo.th_empleados e JOIN dbo.vw_th_situacion_laboral_efectiva s ON s.empleado_id=e.empleado_id
LEFT JOIN dbo.th_puestos p ON p.puesto_id=s.puesto_id LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=s.unidad_id;
GO
CREATE OR ALTER VIEW dbo.view_th_iddatosempledo AS SELECT * FROM dbo.vw_th_directorio_empleados;
GO

/* 4. Registro v3: conserva el documento y su modalidad de vigencia. */
CREATE OR ALTER PROCEDURE dbo.sp_th_registrar_accion_personal_v3
    @numero_accion VARCHAR(50),@empleado_id INT,@tipo_accion VARCHAR(100),@modalidad_vigencia VARCHAR(12),
    @fecha_rige_desde DATE,@fecha_rige_hasta DATE=NULL,@explicacion_legal VARCHAR(MAX),@detalle_otro NVARCHAR(255)=NULL,@presento_declaracion VARCHAR(20)=NULL,
    @actual_unidad_id INT=NULL,@actual_puesto_id INT=NULL,@actual_lugar_trabajo NVARCHAR(150)=NULL,@actual_remuneracion DECIMAL(10,2)=NULL,
    @actual_proceso NVARCHAR(150)=NULL,@actual_nivel_gestion NVARCHAR(150)=NULL,@actual_grupo_ocupacional NVARCHAR(150)=NULL,@actual_grado NVARCHAR(50)=NULL,@actual_partida_presupuestaria NVARCHAR(100)=NULL,
    @propuesta_unidad_id INT=NULL,@propuesta_puesto_id INT=NULL,@propuesta_lugar_trabajo NVARCHAR(150)=NULL,@propuesta_remuneracion DECIMAL(10,2)=NULL,
    @propuesta_proceso NVARCHAR(150)=NULL,@propuesta_nivel_gestion NVARCHAR(150)=NULL,@propuesta_grupo_ocupacional NVARCHAR(150)=NULL,@propuesta_grado NVARCHAR(50)=NULL,@propuesta_partida_presupuestaria NVARCHAR(100)=NULL,
    @actual_jornada NVARCHAR(30)=NULL,@actual_horas_jornada DECIMAL(4,1)=NULL,@propuesta_jornada NVARCHAR(30)=NULL,@propuesta_horas_jornada DECIMAL(4,1)=NULL,
    @tipo_novedad_jornada NVARCHAR(80)=NULL,@hora_entrada_propuesta TIME(0)=NULL,@hora_salida_propuesta TIME(0)=NULL,@dias_jornada_propuesta NVARCHAR(100)=NULL,@documento_jornada NVARCHAR(200)=NULL,
    @actual_tipo_contrato NVARCHAR(100)=NULL,@propuesta_tipo_contrato NVARCHAR(100)=NULL,@notificacion_electronica BIT=0,@correo_notificacion NVARCHAR(150)=NULL,
    @medio_notificacion NVARCHAR(100)=NULL,@documento_notificacion NVARCHAR(100)=NULL,@fecha_notificacion DATETIME2(0)=NULL,
    @responsable_th_nombre NVARCHAR(150)=NULL,@responsable_th_puesto NVARCHAR(150)=NULL,@autoridad_nombre NVARCHAR(150)=NULL,@autoridad_puesto NVARCHAR(150)=NULL,
    @elaborador_nombre NVARCHAR(150)=NULL,@elaborador_puesto NVARCHAR(150)=NULL,@revisor_nombre NVARCHAR(150)=NULL,@revisor_puesto NVARCHAR(150)=NULL,
    @registrador_nombre NVARCHAR(150)=NULL,@registrador_puesto NVARCHAR(150)=NULL,@notificador_nombre NVARCHAR(150)=NULL,@notificador_puesto NVARCHAR(150)=NULL,
    @usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        SET @modalidad_vigencia=UPPER(LTRIM(RTRIM(ISNULL(@modalidad_vigencia,''))));
        IF @modalidad_vigencia NOT IN('PERMANENTE','TEMPORAL') THROW 51900,'Seleccione si la vigencia es permanente o temporal.',1;
        IF @modalidad_vigencia='PERMANENTE' AND @fecha_rige_hasta IS NOT NULL THROW 51901,'Una accion permanente no debe tener fecha final.',1;
        IF @modalidad_vigencia='TEMPORAL' AND (@fecha_rige_hasta IS NULL OR @fecha_rige_hasta<@fecha_rige_desde) THROW 51902,'Una accion temporal requiere fechas validas.',1;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_empleados WHERE empleado_id=@empleado_id) THROW 51903,'El empleado indicado no existe.',1;
        IF NULLIF(LTRIM(RTRIM(@tipo_accion)),'') IS NULL OR NULLIF(LTRIM(RTRIM(@explicacion_legal)),'') IS NULL THROW 51904,'El tipo y la motivacion son obligatorios.',1;

        DECLARE @anio CHAR(4)=CONVERT(CHAR(4),YEAR(dbo.fn_th_fecha_institucional())),@resultado_lock INT;
        DECLARE @prefijo VARCHAR(30)=CONCAT('APM-TH-',@anio,'-'),@recurso_lock NVARCHAR(255)=CONCAT('th_acciones_personal_secuencial_',@anio);
        EXEC @resultado_lock=sys.sp_getapplock @Resource=@recurso_lock,@LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=10000;
        IF @resultado_lock<0 THROW 51905,'No fue posible reservar el numero de Accion de Personal.',1;
        DECLARE @siguiente INT=COALESCE((SELECT MAX(TRY_CONVERT(INT,SUBSTRING(numero_accion,LEN(@prefijo)+1,20))) FROM dbo.th_acciones_personal WITH(UPDLOCK,HOLDLOCK) WHERE numero_accion LIKE @prefijo+'%'),0)+1;
        SET @numero_accion=CONCAT(@prefijo,CASE WHEN @siguiente<1000 THEN RIGHT(CONCAT('000',@siguiente),3) ELSE CONVERT(VARCHAR(20),@siguiente) END);

        INSERT dbo.th_acciones_personal(numero_accion,fecha_elaboracion,empleado_id,tipo_accion,modalidad_vigencia,fecha_rige_desde,fecha_rige_hasta,explicacion_legal,
            detalle_otro,presento_declaracion,actual_unidad_id,actual_puesto_id,actual_lugar_trabajo,actual_remuneracion,actual_proceso,actual_nivel_gestion,
            actual_grupo_ocupacional,actual_grado,actual_partida_presupuestaria,propuesta_unidad_id,propuesta_puesto_id,propuesta_lugar_trabajo,propuesta_remuneracion,
            propuesta_proceso,propuesta_nivel_gestion,propuesta_grupo_ocupacional,propuesta_grado,propuesta_partida_presupuestaria,actual_jornada,actual_horas_jornada,
            propuesta_jornada,propuesta_horas_jornada,tipo_novedad_jornada,hora_entrada_propuesta,hora_salida_propuesta,dias_jornada_propuesta,documento_jornada,
            actual_tipo_contrato,propuesta_tipo_contrato,notificacion_electronica,correo_notificacion,medio_notificacion,documento_notificacion,fecha_notificacion,
            responsable_th_nombre,responsable_th_puesto,autoridad_nombre,autoridad_puesto,elaborador_nombre,elaborador_puesto,revisor_nombre,revisor_puesto,
            registrador_nombre,registrador_puesto,notificador_nombre,notificador_puesto,estado_documento,usuario_crea,fecha_creacion)
        VALUES(@numero_accion,dbo.fn_th_fecha_institucional(),@empleado_id,@tipo_accion,@modalidad_vigencia,@fecha_rige_desde,@fecha_rige_hasta,@explicacion_legal,
            NULLIF(@detalle_otro,''),NULLIF(@presento_declaracion,''),NULLIF(@actual_unidad_id,0),NULLIF(@actual_puesto_id,0),NULLIF(@actual_lugar_trabajo,''),@actual_remuneracion,
            NULLIF(@actual_proceso,''),NULLIF(@actual_nivel_gestion,''),NULLIF(@actual_grupo_ocupacional,''),NULLIF(@actual_grado,''),NULLIF(@actual_partida_presupuestaria,''),
            NULLIF(@propuesta_unidad_id,0),NULLIF(@propuesta_puesto_id,0),NULLIF(@propuesta_lugar_trabajo,''),NULLIF(@propuesta_remuneracion,0),NULLIF(@propuesta_proceso,''),
            NULLIF(@propuesta_nivel_gestion,''),NULLIF(@propuesta_grupo_ocupacional,''),NULLIF(@propuesta_grado,''),NULLIF(@propuesta_partida_presupuestaria,''),
            NULLIF(@actual_jornada,''),NULLIF(@actual_horas_jornada,0),NULLIF(@propuesta_jornada,''),@propuesta_horas_jornada,NULLIF(@tipo_novedad_jornada,''),
            @hora_entrada_propuesta,@hora_salida_propuesta,NULLIF(@dias_jornada_propuesta,''),NULLIF(@documento_jornada,''),NULLIF(@actual_tipo_contrato,''),
            NULLIF(@propuesta_tipo_contrato,''),@notificacion_electronica,NULLIF(@correo_notificacion,''),NULLIF(@medio_notificacion,''),NULLIF(@documento_notificacion,''),
            @fecha_notificacion,NULLIF(@responsable_th_nombre,''),NULLIF(@responsable_th_puesto,''),NULLIF(@autoridad_nombre,''),NULLIF(@autoridad_puesto,''),
            NULLIF(@elaborador_nombre,''),NULLIF(@elaborador_puesto,''),NULLIF(@revisor_nombre,''),NULLIF(@revisor_puesto,''),NULLIF(@registrador_nombre,''),
            NULLIF(@registrador_puesto,''),NULLIF(@notificador_nombre,''),NULLIF(@notificador_puesto,''),'BORRADOR',@usuario,SYSDATETIME());
        DECLARE @accion_id INT=CONVERT(INT,SCOPE_IDENTITY()),@auditoria NVARCHAR(500)=CONCAT('Genero ',@numero_accion,' (',@modalidad_vigencia,') para empleado #',@empleado_id,'.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Accion de Personal','CREAR_BORRADOR',@auditoria,@ip;
        COMMIT;SELECT 1 exito,@accion_id accion_id,@numero_accion numero_accion,'Accion registrada como borrador y auditada.' mensaje;
    END TRY BEGIN CATCH IF @@TRANCOUNT>0 ROLLBACK;SELECT 0 exito,0 accion_id,CAST(NULL AS VARCHAR(50)) numero_accion,ERROR_MESSAGE() mensaje;END CATCH
END;
GO

/* 5. Aprobación v3: permanente inmediato, permanente programado, asignación
      temporal o jornada temporal; todas las ramas son atómicas y auditadas. */
CREATE OR ALTER PROCEDURE dbo.sp_th_aprobar_accion_personal_v3 @accion_id INT,@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        DECLARE @empleado INT,@desde DATE,@hasta DATE,@numero VARCHAR(50),@tipo NVARCHAR(100),@modalidad VARCHAR(12),@novedad NVARCHAR(80),@hoy DATE=dbo.fn_th_fecha_institucional(),
            @u INT,@p INT,@rmu DECIMAL(10,2),@proceso NVARCHAR(150),@nivel NVARCHAR(150),@lugar NVARCHAR(150),@grupo NVARCHAR(150),@grado NVARCHAR(50),
            @partida NVARCHAR(100),@jornada NVARCHAR(30),@horas DECIMAL(4,1),@contrato NVARCHAR(100),@entrada TIME(0),@salida TIME(0),@dias NVARCHAR(100),@documento NVARCHAR(200),
            @ou INT,@op INT,@ormu DECIMAL(10,2),@oproceso NVARCHAR(150),@onivel NVARCHAR(150),@olugar NVARCHAR(150),@ogrupo NVARCHAR(150),@ogrado NVARCHAR(50),
            @opartida NVARCHAR(100),@ojornada NVARCHAR(30),@ohoras DECIMAL(4,1),@ocontrato NVARCHAR(100),@estado_empleado BIT;
        SELECT @empleado=a.empleado_id,@desde=a.fecha_rige_desde,@hasta=a.fecha_rige_hasta,@numero=a.numero_accion,@tipo=a.tipo_accion,
            @modalidad=a.modalidad_vigencia,@novedad=NULLIF(a.tipo_novedad_jornada,''),@ou=e.unidad_id,@op=e.puesto_id,@ormu=e.sueldo_rmu,
            @oproceso=e.proceso_institucional,@onivel=e.nivel_gestion,@olugar=e.lugar_trabajo,@ogrupo=e.grupo_ocupacional,@ogrado=e.grado_laboral,
            @opartida=e.partida_individual,@ojornada=e.jornada,@ohoras=e.horas_jornada,@ocontrato=e.tipo_contrato,@estado_empleado=e.estado,
            @u=COALESCE(a.propuesta_unidad_id,e.unidad_id),@p=COALESCE(a.propuesta_puesto_id,e.puesto_id),@rmu=COALESCE(NULLIF(a.propuesta_remuneracion,0),e.sueldo_rmu),
            @proceso=COALESCE(NULLIF(a.propuesta_proceso,''),e.proceso_institucional),@nivel=COALESCE(NULLIF(a.propuesta_nivel_gestion,''),e.nivel_gestion),
            @lugar=COALESCE(NULLIF(a.propuesta_lugar_trabajo,''),e.lugar_trabajo),@grupo=COALESCE(NULLIF(a.propuesta_grupo_ocupacional,''),e.grupo_ocupacional),
            @grado=COALESCE(NULLIF(a.propuesta_grado,''),e.grado_laboral),@partida=COALESCE(NULLIF(a.propuesta_partida_presupuestaria,''),e.partida_individual),
            @jornada=COALESCE(NULLIF(a.propuesta_jornada,''),e.jornada),@horas=COALESCE(a.propuesta_horas_jornada,e.horas_jornada),
            @contrato=COALESCE(NULLIF(a.propuesta_tipo_contrato,''),e.tipo_contrato),@entrada=a.hora_entrada_propuesta,@salida=a.hora_salida_propuesta,
            @dias=a.dias_jornada_propuesta,@documento=a.documento_jornada
        FROM dbo.th_acciones_personal a WITH(UPDLOCK,HOLDLOCK) JOIN dbo.th_empleados e WITH(UPDLOCK,HOLDLOCK) ON e.empleado_id=a.empleado_id
        WHERE a.accion_id=@accion_id AND UPPER(a.estado_documento) IN('BORRADOR','PENDIENTE');
        IF @empleado IS NULL THROW 51910,'La accion no existe o ya fue resuelta.',1;
        IF @estado_empleado=0 AND UPPER(@tipo) NOT IN('INGRESO','REINGRESO','RESTITUCION','REINTEGRO') THROW 51911,'El funcionario inactivo requiere una accion de reingreso.',1;
        IF @modalidad='TEMPORAL' AND (@hasta IS NULL OR @hasta<@desde) THROW 51912,'La vigencia temporal requiere fecha final.',1;
        IF @modalidad='PERMANENTE' AND @hasta IS NOT NULL THROW 51913,'La vigencia permanente no admite fecha final.',1;

        IF @novedad IS NOT NULL
        BEGIN
            IF @modalidad<>'TEMPORAL' OR @horas IS NULL OR @horas>24 OR (UPPER(@novedad)<>'MATERNIDAD' AND @horas<=0) OR (UPPER(@novedad)='MATERNIDAD' AND @horas<>0)
                THROW 51914,'La jornada especial requiere modalidad temporal y horas validas; maternidad se registra con cero horas.',1;
            IF EXISTS(SELECT 1 FROM dbo.th_jornadas_especiales WHERE empleado_id=@empleado AND estado NOT IN('FINALIZADA','CANCELADA') AND fecha_desde<=@hasta AND fecha_hasta>=@desde)
                THROW 51915,'El funcionario ya tiene una jornada temporal que se cruza con este periodo.',1;
            INSERT dbo.th_jornadas_especiales(empleado_id,accion_id,tipo_novedad,fecha_desde,fecha_hasta,horas_diarias,hora_entrada,hora_salida,dias_aplicables,
                documento_respaldo,observaciones,usuario_crea,estado,jornada_base,horas_base,jornada_temporal)
            VALUES(@empleado,@accion_id,@novedad,@desde,@hasta,@horas,@entrada,@salida,@dias,@documento,CONCAT('Accion de Personal ',@numero,'.'),@usuario,
                CASE WHEN @desde>@hoy THEN 'PROGRAMADA' ELSE 'VIGENTE' END,@ojornada,@ohoras,COALESCE(@jornada,'Especial'));
        END
        ELSE IF @modalidad='TEMPORAL' OR @desde>@hoy
        BEGIN
            IF EXISTS(SELECT 1 FROM dbo.th_vigencias_laborales WHERE empleado_id=@empleado AND estado NOT IN('FINALIZADA','CANCELADA','ERROR')
                AND fecha_desde<=COALESCE(@hasta,'99991231') AND COALESCE(fecha_hasta,'99991231')>=@desde)
                THROW 51916,'El funcionario ya tiene otra vigencia laboral que se cruza con este periodo.',1;
            INSERT dbo.th_vigencias_laborales(accion_id,empleado_id,tipo_vigencia,fecha_desde,fecha_hasta,estado,
                original_unidad_id,original_puesto_id,original_remuneracion,original_proceso,original_nivel_gestion,original_lugar_trabajo,original_grupo_ocupacional,
                original_grado,original_partida,original_jornada,original_horas_jornada,original_tipo_contrato,propuesta_unidad_id,propuesta_puesto_id,propuesta_remuneracion,
                propuesta_proceso,propuesta_nivel_gestion,propuesta_lugar_trabajo,propuesta_grupo_ocupacional,propuesta_grado,propuesta_partida,propuesta_jornada,
                propuesta_horas_jornada,propuesta_tipo_contrato,usuario_crea,observaciones)
            VALUES(@accion_id,@empleado,@modalidad,@desde,@hasta,CASE WHEN @modalidad='TEMPORAL' AND @desde<=@hoy THEN 'VIGENTE' ELSE 'PROGRAMADA' END,
                @ou,@op,@ormu,@oproceso,@onivel,@olugar,@ogrupo,@ogrado,@opartida,@ojornada,@ohoras,@ocontrato,@u,@p,@rmu,@proceso,@nivel,@lugar,@grupo,@grado,@partida,
                @jornada,@horas,@contrato,@usuario,CONCAT('Vigencia originada por ',@numero,'.'));
        END
        ELSE
        BEGIN
            DECLARE @tipo_norm NVARCHAR(100)=UPPER(LTRIM(RTRIM(ISNULL(@tipo,'')))) COLLATE Modern_Spanish_CI_AI;
            DECLARE @es_cese BIT=IIF(@tipo_norm IN('CESACION DE FUNCIONES','DESTITUCION'),1,0),@es_reingreso BIT=IIF(@tipo_norm IN('INGRESO','REINGRESO','RESTITUCION','REINTEGRO'),1,0);
            IF @es_cese=1 EXEC dbo.sp_th_cambiar_estado_empleado @empleado,0,@desde,N'Cesacion mediante Accion de Personal.','ACCION_PERSONAL',@accion_id,@usuario,@ip,1,0;
            ELSE IF @es_reingreso=1 EXEC dbo.sp_th_cambiar_estado_empleado @empleado,1,@desde,N'Ingreso o reingreso mediante Accion de Personal.','ACCION_PERSONAL',@accion_id,@usuario,@ip,0,0;
            IF @es_cese=0
            BEGIN
                UPDATE dbo.th_historial_laboral SET fecha_hasta=CASE WHEN fecha_desde<@desde THEN DATEADD(DAY,-1,@desde) ELSE @desde END WHERE empleado_id=@empleado AND fecha_hasta IS NULL;
                INSERT dbo.th_historial_laboral(empleado_id,puesto_id,unidad_id,fecha_desde,observaciones,usuario_crea,fecha_creacion,accion_id,tipo_contrato,sueldo_rmu,
                    proceso_institucional,nivel_gestion,lugar_trabajo,grupo_ocupacional,grado_laboral,partida_individual,jornada,horas_jornada,condicion_especial)
                SELECT empleado_id,@p,@u,@desde,CONCAT('Aplicado por Accion de Personal ',@numero),@usuario,SYSDATETIME(),@accion_id,@contrato,@rmu,@proceso,@nivel,@lugar,@grupo,
                    @grado,@partida,@jornada,@horas,condicion_especial FROM dbo.th_empleados WHERE empleado_id=@empleado;
                UPDATE dbo.th_empleados SET unidad_id=@u,puesto_id=@p,sueldo_rmu=@rmu,proceso_institucional=@proceso,nivel_gestion=@nivel,lugar_trabajo=@lugar,
                    grupo_ocupacional=@grupo,grado_laboral=@grado,partida_individual=@partida,jornada=@jornada,horas_jornada=@horas,tipo_contrato=@contrato WHERE empleado_id=@empleado;
            END
        END;
        UPDATE dbo.th_acciones_personal SET estado_documento='APROBADO',usuario_aprueba=@usuario,fecha_aprobacion=SYSDATETIME() WHERE accion_id=@accion_id;
        DECLARE @auditoria NVARCHAR(500)=CONCAT('Aprobo ',@numero,'; modalidad ',@modalidad,CASE WHEN @hasta IS NULL THEN '.' ELSE CONCAT('; retorno automatico despues de ',CONVERT(varchar(10),@hasta,23),'.') END);
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Accion de Personal','APROBAR',@auditoria,@ip;
        COMMIT;SELECT 1 exito,'Accion aprobada y vigencia sincronizada.' mensaje;
    END TRY BEGIN CATCH IF @@TRANCOUNT>0 ROLLBACK;SELECT 0 exito,ERROR_MESSAGE() mensaje;END CATCH
END;
GO

/* 6. Mantenimiento idempotente. La vista ya resuelve la situación efectiva;
      este procedimiento materializa permanentes programados y audita cierres. */
CREATE OR ALTER PROCEDURE dbo.sp_th_refrescar_vigencias_laborales @usuario VARCHAR(50)='SQL_AGENT',@ip VARCHAR(45)='LOCAL'
AS
BEGIN
    SET NOCOUNT ON;SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        DECLARE @hoy DATE=dbo.fn_th_fecha_institucional(),@vigentes INT=0,@finalizadas INT=0,@aplicadas INT=0;
        UPDATE dbo.th_vigencias_laborales SET estado='VIGENTE',fecha_aplicacion=COALESCE(fecha_aplicacion,SYSDATETIME())
        WHERE tipo_vigencia='TEMPORAL' AND estado='PROGRAMADA' AND @hoy BETWEEN fecha_desde AND fecha_hasta;SET @vigentes+=@@ROWCOUNT;
        UPDATE dbo.th_vigencias_laborales SET estado='FINALIZADA',fecha_finalizacion=COALESCE(fecha_finalizacion,SYSDATETIME())
        WHERE tipo_vigencia='TEMPORAL' AND estado IN('PROGRAMADA','VIGENTE') AND @hoy>fecha_hasta;SET @finalizadas+=@@ROWCOUNT;
        UPDATE dbo.th_jornadas_especiales SET estado='VIGENTE' WHERE estado='PROGRAMADA' AND @hoy BETWEEN fecha_desde AND fecha_hasta;SET @vigentes+=@@ROWCOUNT;
        UPDATE dbo.th_jornadas_especiales SET estado='FINALIZADA' WHERE estado IN('PROGRAMADA','VIGENTE') AND @hoy>fecha_hasta;SET @finalizadas+=@@ROWCOUNT;

        DECLARE @id INT,@empleado INT,@accion INT,@desde DATE,@numero VARCHAR(50),@u INT,@p INT,@rmu DECIMAL(10,2),@proceso NVARCHAR(150),@nivel NVARCHAR(150),
            @lugar NVARCHAR(150),@grupo NVARCHAR(150),@grado NVARCHAR(50),@partida NVARCHAR(100),@jornada NVARCHAR(30),@horas DECIMAL(4,1),@contrato NVARCHAR(100);
        DECLARE permanentes CURSOR LOCAL FAST_FORWARD FOR
            SELECT v.vigencia_id,v.empleado_id,v.accion_id,v.fecha_desde,a.numero_accion,v.propuesta_unidad_id,v.propuesta_puesto_id,v.propuesta_remuneracion,
                v.propuesta_proceso,v.propuesta_nivel_gestion,v.propuesta_lugar_trabajo,v.propuesta_grupo_ocupacional,v.propuesta_grado,v.propuesta_partida,
                v.propuesta_jornada,v.propuesta_horas_jornada,v.propuesta_tipo_contrato
            FROM dbo.th_vigencias_laborales v JOIN dbo.th_acciones_personal a ON a.accion_id=v.accion_id
            WHERE v.tipo_vigencia='PERMANENTE' AND v.estado='PROGRAMADA' AND v.fecha_desde<=@hoy ORDER BY v.fecha_desde,v.vigencia_id;
        OPEN permanentes;FETCH NEXT FROM permanentes INTO @id,@empleado,@accion,@desde,@numero,@u,@p,@rmu,@proceso,@nivel,@lugar,@grupo,@grado,@partida,@jornada,@horas,@contrato;
        WHILE @@FETCH_STATUS=0
        BEGIN
            UPDATE dbo.th_historial_laboral SET fecha_hasta=CASE WHEN fecha_desde<@desde THEN DATEADD(DAY,-1,@desde) ELSE @desde END WHERE empleado_id=@empleado AND fecha_hasta IS NULL;
            INSERT dbo.th_historial_laboral(empleado_id,puesto_id,unidad_id,fecha_desde,observaciones,usuario_crea,fecha_creacion,accion_id,tipo_contrato,sueldo_rmu,
                proceso_institucional,nivel_gestion,lugar_trabajo,grupo_ocupacional,grado_laboral,partida_individual,jornada,horas_jornada,condicion_especial)
            SELECT empleado_id,@p,@u,@desde,CONCAT('Aplicacion programada de ',@numero),@usuario,SYSDATETIME(),@accion,@contrato,@rmu,@proceso,@nivel,@lugar,@grupo,@grado,
                @partida,@jornada,@horas,condicion_especial FROM dbo.th_empleados WITH(UPDLOCK,HOLDLOCK) WHERE empleado_id=@empleado;
            UPDATE dbo.th_empleados SET unidad_id=@u,puesto_id=@p,sueldo_rmu=@rmu,proceso_institucional=@proceso,nivel_gestion=@nivel,lugar_trabajo=@lugar,
                grupo_ocupacional=@grupo,grado_laboral=@grado,partida_individual=@partida,jornada=@jornada,horas_jornada=@horas,tipo_contrato=@contrato WHERE empleado_id=@empleado;
            UPDATE dbo.th_vigencias_laborales SET estado='APLICADA',fecha_aplicacion=SYSDATETIME() WHERE vigencia_id=@id;SET @aplicadas+=1;
            FETCH NEXT FROM permanentes INTO @id,@empleado,@accion,@desde,@numero,@u,@p,@rmu,@proceso,@nivel,@lugar,@grupo,@grado,@partida,@jornada,@horas,@contrato;
        END
        CLOSE permanentes;DEALLOCATE permanentes;
        IF @vigentes+@finalizadas+@aplicadas>0
        BEGIN
            DECLARE @detalle NVARCHAR(500)=CONCAT('Vigencias activadas: ',@vigentes,'; finalizadas con restitucion automatica: ',@finalizadas,'; permanentes aplicadas: ',@aplicadas,'.');
            EXEC dbo.sp_th_registrar_auditoria @usuario,'Vigencias Laborales','REFRESCAR',@detalle,@ip;
        END
        COMMIT;SELECT 1 exito,@vigentes vigentes,@finalizadas finalizadas,@aplicadas aplicadas;
    END TRY
    BEGIN CATCH
        IF CURSOR_STATUS('local','permanentes')>=0 CLOSE permanentes;
        IF CURSOR_STATUS('local','permanentes')>-3 DEALLOCATE permanentes;
        IF @@TRANCOUNT>0 ROLLBACK;
        THROW;
    END CATCH
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_consultar_vigencias_laborales @empleado_id INT=NULL,@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario,'Historial Laboral','CONSULTAR_VIGENCIAS','Consulta de cambios laborales temporales y programados.',@ip;
    SELECT v.*,a.numero_accion,a.tipo_accion,uo.nombre_unidad area_original,po.nombre_puesto cargo_original,
        up.nombre_unidad area_propuesta,pp.nombre_puesto cargo_propuesto
    FROM dbo.th_vigencias_laborales v JOIN dbo.th_acciones_personal a ON a.accion_id=v.accion_id
    LEFT JOIN dbo.th_unidades_organizacionales uo ON uo.unidad_id=v.original_unidad_id LEFT JOIN dbo.th_puestos po ON po.puesto_id=v.original_puesto_id
    LEFT JOIN dbo.th_unidades_organizacionales up ON up.unidad_id=v.propuesta_unidad_id LEFT JOIN dbo.th_puestos pp ON pp.puesto_id=v.propuesta_puesto_id
    WHERE @empleado_id IS NULL OR v.empleado_id=@empleado_id ORDER BY v.empleado_id,v.fecha_desde DESC;
END;
GO

IF DATABASE_PRINCIPAL_ID('portal_app_role') IS NOT NULL
BEGIN
    GRANT SELECT ON dbo.vw_th_situacion_laboral_efectiva TO portal_app_role;
    GRANT SELECT ON dbo.th_vigencias_laborales TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_registrar_accion_personal_v3 TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_aprobar_accion_personal_v3 TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_refrescar_vigencias_laborales TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_consultar_vigencias_laborales TO portal_app_role;
END;
GO

IF OBJECT_ID('dbo.th_schema_migrations','U') IS NOT NULL AND NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.20.1')
    INSERT dbo.th_schema_migrations(version,nombre_archivo) VALUES('2026.08.20.1','migracion_vigencias_temporales_20260820.sql');
GO

/* 7. Refresco periódico para auditoría/estados. El retorno visible por fecha
      no depende del trabajo, pero el job mantiene estados y logs al día. */
USE msdb;
GO
IF NOT EXISTS(SELECT 1 FROM dbo.sysjobs WHERE name=N'APM - Vigencias laborales')
BEGIN
    EXEC dbo.sp_add_job @job_name=N'APM - Vigencias laborales',@enabled=1,@description=N'Activa, finaliza y audita vigencias laborales del Portal APM.';
    EXEC dbo.sp_add_jobstep @job_name=N'APM - Vigencias laborales',@step_name=N'Refrescar vigencias',@subsystem=N'TSQL',@database_name=N'Talento_Humano',
        @command=N'EXEC dbo.sp_th_refrescar_vigencias_laborales @usuario=''SQL_AGENT'',@ip=''LOCAL'';',@on_success_action=1,@on_fail_action=2;
    EXEC dbo.sp_add_schedule @schedule_name=N'APM - Cada 5 minutos - Vigencias',@freq_type=4,@freq_interval=1,@freq_subday_type=4,@freq_subday_interval=5,@active_start_time=0;
    EXEC dbo.sp_attach_schedule @job_name=N'APM - Vigencias laborales',@schedule_name=N'APM - Cada 5 minutos - Vigencias';
    EXEC dbo.sp_add_jobserver @job_name=N'APM - Vigencias laborales';
END;
GO
USE Talento_Humano;
GO
