/* ============================================================================
   Portal Portuario APM - Formatos oficiales de Talento Humano
   Base objetivo: Talento_Humano
   Version: 2026-07-29

   - Completa los campos imprimibles de Accion de Personal (2 paginas).
   - Crea persistencia normalizada del Estudio Socioeconomico (4 paginas).
   - Mantiene auditoria de altas, modificaciones, consultas e impresiones.
   - Es idempotente: puede ejecutarse nuevamente sin duplicar objetos.
   ============================================================================ */
USE [Talento_Humano];
GO

/* Campos faltantes del formato vigente de Accion de Personal. */
IF COL_LENGTH('dbo.th_acciones_personal','detalle_otro') IS NULL ALTER TABLE dbo.th_acciones_personal ADD detalle_otro NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','presento_declaracion') IS NULL ALTER TABLE dbo.th_acciones_personal ADD presento_declaracion VARCHAR(20) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','actual_proceso') IS NULL ALTER TABLE dbo.th_acciones_personal ADD actual_proceso NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','actual_nivel_gestion') IS NULL ALTER TABLE dbo.th_acciones_personal ADD actual_nivel_gestion NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','actual_grupo_ocupacional') IS NULL ALTER TABLE dbo.th_acciones_personal ADD actual_grupo_ocupacional NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','actual_grado') IS NULL ALTER TABLE dbo.th_acciones_personal ADD actual_grado NVARCHAR(50) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','actual_partida_presupuestaria') IS NULL ALTER TABLE dbo.th_acciones_personal ADD actual_partida_presupuestaria NVARCHAR(100) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','propuesta_proceso') IS NULL ALTER TABLE dbo.th_acciones_personal ADD propuesta_proceso NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','propuesta_nivel_gestion') IS NULL ALTER TABLE dbo.th_acciones_personal ADD propuesta_nivel_gestion NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','propuesta_grupo_ocupacional') IS NULL ALTER TABLE dbo.th_acciones_personal ADD propuesta_grupo_ocupacional NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','propuesta_grado') IS NULL ALTER TABLE dbo.th_acciones_personal ADD propuesta_grado NVARCHAR(50) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','propuesta_partida_presupuestaria') IS NULL ALTER TABLE dbo.th_acciones_personal ADD propuesta_partida_presupuestaria NVARCHAR(100) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','notificacion_electronica') IS NULL ALTER TABLE dbo.th_acciones_personal ADD notificacion_electronica BIT NULL;
IF COL_LENGTH('dbo.th_acciones_personal','correo_notificacion') IS NULL ALTER TABLE dbo.th_acciones_personal ADD correo_notificacion NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','medio_notificacion') IS NULL ALTER TABLE dbo.th_acciones_personal ADD medio_notificacion NVARCHAR(100) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','documento_notificacion') IS NULL ALTER TABLE dbo.th_acciones_personal ADD documento_notificacion NVARCHAR(100) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','fecha_notificacion') IS NULL ALTER TABLE dbo.th_acciones_personal ADD fecha_notificacion DATETIME2(0) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','responsable_th_nombre') IS NULL ALTER TABLE dbo.th_acciones_personal ADD responsable_th_nombre NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','responsable_th_puesto') IS NULL ALTER TABLE dbo.th_acciones_personal ADD responsable_th_puesto NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','autoridad_nombre') IS NULL ALTER TABLE dbo.th_acciones_personal ADD autoridad_nombre NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','autoridad_puesto') IS NULL ALTER TABLE dbo.th_acciones_personal ADD autoridad_puesto NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','elaborador_nombre') IS NULL ALTER TABLE dbo.th_acciones_personal ADD elaborador_nombre NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','elaborador_puesto') IS NULL ALTER TABLE dbo.th_acciones_personal ADD elaborador_puesto NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','revisor_nombre') IS NULL ALTER TABLE dbo.th_acciones_personal ADD revisor_nombre NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','revisor_puesto') IS NULL ALTER TABLE dbo.th_acciones_personal ADD revisor_puesto NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','registrador_nombre') IS NULL ALTER TABLE dbo.th_acciones_personal ADD registrador_nombre NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','registrador_puesto') IS NULL ALTER TABLE dbo.th_acciones_personal ADD registrador_puesto NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','notificador_nombre') IS NULL ALTER TABLE dbo.th_acciones_personal ADD notificador_nombre NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','notificador_puesto') IS NULL ALTER TABLE dbo.th_acciones_personal ADD notificador_puesto NVARCHAR(150) NULL;
GO

IF OBJECT_ID('dbo.th_estudios_socioeconomicos','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_estudios_socioeconomicos (
        estudio_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_estudios_socioeconomicos PRIMARY KEY,
        empleado_id INT NOT NULL,
        codigo_formato VARCHAR(30) NOT NULL CONSTRAINT DF_th_estudio_codigo DEFAULT 'APM-BASC-TH-FO-002',
        fecha_formato DATE NOT NULL CONSTRAINT DF_th_estudio_fecha_formato DEFAULT '20190401',
        version_formato VARCHAR(20) NOT NULL CONSTRAINT DF_th_estudio_version DEFAULT '01',
        fecha_vinculacion DATE NULL, cargo_cabecera NVARCHAR(180) NULL, nombre_cabecera NVARCHAR(220) NULL,
        tipo_doc_ident NVARCHAR(50) NULL, nro_documento NVARCHAR(30) NULL, nacionalidad NVARCHAR(80) NULL,
        anios_residencia NVARCHAR(30) NULL, libreta_militar NVARCHAR(30) NULL, nro_libreta_militar NVARCHAR(40) NULL,
        tipo_relacion NVARCHAR(80) NULL, apellidos NVARCHAR(150) NULL, nombres NVARCHAR(150) NULL,
        fecha_nacimiento DATE NULL, edad NVARCHAR(20) NULL, lugar_nacimiento NVARCHAR(120) NULL,
        provincia_ciudad_nac NVARCHAR(150) NULL, genero NVARCHAR(40) NULL, tipo_sangre NVARCHAR(20) NULL,
        estado_civil NVARCHAR(40) NULL, discapacidad NVARCHAR(20) NULL, tipo_discapacidad NVARCHAR(100) NULL,
        nro_carnet_conadis NVARCHAR(40) NULL, servidor_carrera NVARCHAR(30) NULL, nro_servidor_carrera NVARCHAR(50) NULL,
        auto_identificacion NVARCHAR(80) NULL, nacionalidad_indigena NVARCHAR(100) NULL,
        dir_calle_principal NVARCHAR(150) NULL, numero_domicilio NVARCHAR(30) NULL,
        calle_secundaria NVARCHAR(150) NULL, parroquia NVARCHAR(100) NULL, canton NVARCHAR(100) NULL,
        provincia_dom NVARCHAR(100) NULL, referencia_domiciliaria NVARCHAR(250) NULL,
        tel_domicilio NVARCHAR(40) NULL, tel_celular NVARCHAR(40) NULL, tel_trabajo NVARCHAR(40) NULL,
        extension NVARCHAR(20) NULL, correo_institucional NVARCHAR(150) NULL, correo_alternativo NVARCHAR(150) NULL,
        contacto_nombre NVARCHAR(180) NULL, contacto_parentesco NVARCHAR(80) NULL,
        contacto_tel_conv NVARCHAR(40) NULL, contacto_tel_cel NVARCHAR(40) NULL,
        nro_otorgamiento NVARCHAR(80) NULL, fecha_ingreso_bienes DATE NULL,
        banco NVARCHAR(120) NULL, tipo_cuenta NVARCHAR(50) NULL, nro_cuenta NVARCHAR(60) NULL,
        conyuge_nombres NVARCHAR(180) NULL, conyuge_tipo_doc NVARCHAR(50) NULL, conyuge_nro_doc NVARCHAR(40) NULL,
        conyuge_fecha_nac DATE NULL, conyuge_tipo_relacion NVARCHAR(80) NULL,
        conyuge_nivel_instruccion NVARCHAR(100) NULL, conyuge_ocupacion NVARCHAR(120) NULL,
        nivel_instruccion NVARCHAR(100) NULL, institucion_educativa NVARCHAR(180) NULL,
        tipo_periodo NVARCHAR(80) NULL, area_conocimiento NVARCHAR(150) NULL, egresado NVARCHAR(20) NULL,
        titulo_academico NVARCHAR(200) NULL,
        vivienda_tipo NVARCHAR(30) NULL, vehiculo_marca NVARCHAR(80) NULL, vehiculo_modelo NVARCHAR(80) NULL,
        vehiculo_placa NVARCHAR(30) NULL, vehiculo_valor DECIMAL(12,2) NULL,
        estado BIT NOT NULL CONSTRAINT DF_th_estudio_estado DEFAULT 1,
        usuario_crea VARCHAR(50) NOT NULL, fecha_creacion DATETIME2(0) NOT NULL CONSTRAINT DF_th_estudio_creacion DEFAULT SYSDATETIME(),
        usuario_modifica VARCHAR(50) NULL, fecha_modificacion DATETIME2(0) NULL, direccion_ip VARCHAR(45) NULL,
        CONSTRAINT FK_th_estudio_empleado FOREIGN KEY (empleado_id) REFERENCES dbo.th_empleados(empleado_id)
    );
END;
GO

IF OBJECT_ID('dbo.th_estudio_hijos','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_estudio_hijos (
        hijo_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_estudio_hijos PRIMARY KEY,
        estudio_id INT NOT NULL, orden TINYINT NOT NULL,
        nombres_apellidos NVARCHAR(180) NULL, fecha_nacimiento DATE NULL, tipo_documento NVARCHAR(50) NULL,
        numero_documento NVARCHAR(40) NULL, edad NVARCHAR(20) NULL, nivel_instruccion NVARCHAR(100) NULL,
        ocupacion NVARCHAR(120) NULL,
        CONSTRAINT UQ_th_estudio_hijo_orden UNIQUE(estudio_id,orden),
        CONSTRAINT FK_th_estudio_hijo FOREIGN KEY(estudio_id) REFERENCES dbo.th_estudios_socioeconomicos(estudio_id) ON DELETE CASCADE
    );
END;
GO

IF OBJECT_ID('dbo.th_estudio_capacitaciones','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_estudio_capacitaciones (
        capacitacion_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_estudio_capacitaciones PRIMARY KEY,
        estudio_id INT NOT NULL, orden TINYINT NOT NULL,
        evento NVARCHAR(250) NULL, tipo_evento NVARCHAR(100) NULL, auspiciante NVARCHAR(180) NULL,
        tipo_certificado NVARCHAR(100) NULL, certificado_por NVARCHAR(180) NULL, fecha_inicio DATE NULL,
        CONSTRAINT UQ_th_estudio_capacitacion_orden UNIQUE(estudio_id,orden),
        CONSTRAINT FK_th_estudio_capacitacion FOREIGN KEY(estudio_id) REFERENCES dbo.th_estudios_socioeconomicos(estudio_id) ON DELETE CASCADE
    );
END;
GO

IF OBJECT_ID('dbo.th_estudio_experiencias','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_estudio_experiencias (
        experiencia_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_estudio_experiencias PRIMARY KEY,
        estudio_id INT NOT NULL, orden TINYINT NOT NULL,
        institucion NVARCHAR(180) NULL, tipo_institucion NVARCHAR(80) NULL, unidad_administrativa NVARCHAR(150) NULL,
        cargo NVARCHAR(150) NULL, antiguedad NVARCHAR(50) NULL, jefe_inmediato NVARCHAR(150) NULL,
        telefono NVARCHAR(40) NULL, fecha_ingreso DATE NULL, motivo_ingreso NVARCHAR(180) NULL,
        fecha_retiro DATE NULL, motivo_retiro NVARCHAR(180) NULL,
        CONSTRAINT UQ_th_estudio_experiencia_orden UNIQUE(estudio_id,orden),
        CONSTRAINT FK_th_estudio_experiencia FOREIGN KEY(estudio_id) REFERENCES dbo.th_estudios_socioeconomicos(estudio_id) ON DELETE CASCADE
    );
END;
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_estudios_socioeconomicos') AND name='IX_th_estudio_empleado_fecha')
    CREATE INDEX IX_th_estudio_empleado_fecha ON dbo.th_estudios_socioeconomicos(empleado_id,fecha_creacion DESC);
GO

CREATE OR ALTER VIEW dbo.vw_th_estudios_socioeconomicos
AS
SELECT s.estudio_id,s.empleado_id,s.codigo_formato,s.fecha_formato,s.version_formato,
       s.fecha_vinculacion,s.cargo_cabecera,s.nro_documento,s.nombres,s.apellidos,
       e.identificacion,e.nombres AS nombres_empleado,e.apellidos AS apellidos_empleado,
       s.estado,s.usuario_crea,s.fecha_creacion,s.usuario_modifica,s.fecha_modificacion
FROM dbo.th_estudios_socioeconomicos s
JOIN dbo.th_empleados e ON e.empleado_id=s.empleado_id;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_consultar_estudios_socioeconomicos
    @usuario VARCHAR(50), @ip VARCHAR(45)='0.0.0.0', @estudio_id INT=NULL
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario,'Estudio Socioeconomico','CONSULTAR_VISTA',
         'Consulta de vw_th_estudios_socioeconomicos',@ip;
    SELECT * FROM dbo.vw_th_estudios_socioeconomicos
    WHERE (@estudio_id IS NULL OR estudio_id=@estudio_id)
    ORDER BY fecha_creacion DESC;
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_registrar_accion_personal
    @numero_accion VARCHAR(50), @empleado_id INT, @tipo_accion VARCHAR(100),
    @fecha_rige_desde DATE, @fecha_rige_hasta DATE=NULL, @explicacion_legal VARCHAR(MAX),
    @detalle_otro NVARCHAR(255)=NULL, @presento_declaracion VARCHAR(20)=NULL,
    @actual_unidad_id INT=NULL, @actual_puesto_id INT=NULL, @actual_lugar_trabajo NVARCHAR(150)=NULL,
    @actual_remuneracion DECIMAL(10,2)=NULL, @actual_proceso NVARCHAR(150)=NULL,
    @actual_nivel_gestion NVARCHAR(150)=NULL, @actual_grupo_ocupacional NVARCHAR(150)=NULL,
    @actual_grado NVARCHAR(50)=NULL, @actual_partida_presupuestaria NVARCHAR(100)=NULL,
    @propuesta_unidad_id INT=NULL, @propuesta_puesto_id INT=NULL, @propuesta_lugar_trabajo NVARCHAR(150)=NULL,
    @propuesta_remuneracion DECIMAL(10,2)=NULL, @propuesta_proceso NVARCHAR(150)=NULL,
    @propuesta_nivel_gestion NVARCHAR(150)=NULL, @propuesta_grupo_ocupacional NVARCHAR(150)=NULL,
    @propuesta_grado NVARCHAR(50)=NULL, @propuesta_partida_presupuestaria NVARCHAR(100)=NULL,
    @notificacion_electronica BIT=0, @correo_notificacion NVARCHAR(150)=NULL,
    @medio_notificacion NVARCHAR(100)=NULL, @documento_notificacion NVARCHAR(100)=NULL,
    @fecha_notificacion DATETIME2(0)=NULL,
    @responsable_th_nombre NVARCHAR(150)=NULL, @responsable_th_puesto NVARCHAR(150)=NULL,
    @autoridad_nombre NVARCHAR(150)=NULL, @autoridad_puesto NVARCHAR(150)=NULL,
    @elaborador_nombre NVARCHAR(150)=NULL, @elaborador_puesto NVARCHAR(150)=NULL,
    @revisor_nombre NVARCHAR(150)=NULL, @revisor_puesto NVARCHAR(150)=NULL,
    @registrador_nombre NVARCHAR(150)=NULL, @registrador_puesto NVARCHAR(150)=NULL,
    @notificador_nombre NVARCHAR(150)=NULL, @notificador_puesto NVARCHAR(150)=NULL,
    @usuario VARCHAR(50), @ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF NOT EXISTS (SELECT 1 FROM dbo.th_empleados WHERE empleado_id=@empleado_id)
            THROW 51031,'El empleado indicado no existe.',1;

        /* El correlativo se calcula dentro de la misma transaccion y no se confia
           en el valor enviado por el navegador. El application lock evita que dos
           solicitudes concurrentes reciban el mismo numero. */
        DECLARE @anio CHAR(4)=CONVERT(CHAR(4),YEAR(GETDATE()));
        DECLARE @prefijo VARCHAR(30)=CONCAT('APM-TH-',@anio,'-');
        DECLARE @resultado_lock INT;
        DECLARE @recurso_lock NVARCHAR(255)=CONCAT('th_acciones_personal_secuencial_',@anio);
        EXEC @resultado_lock=sys.sp_getapplock
            @Resource=@recurso_lock,
            @LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=10000;
        IF @resultado_lock<0 THROW 51032,'No fue posible reservar el numero de Accion de Personal.',1;

        DECLARE @siguiente INT=COALESCE((
            SELECT MAX(TRY_CONVERT(INT,SUBSTRING(numero_accion,LEN(@prefijo)+1,20)))
            FROM dbo.th_acciones_personal WITH(UPDLOCK,HOLDLOCK)
            WHERE numero_accion LIKE @prefijo+'%'
        ),0)+1;
        SET @numero_accion=CONCAT(@prefijo,
            CASE WHEN @siguiente<1000 THEN RIGHT(CONCAT('000',@siguiente),3)
                 ELSE CONVERT(VARCHAR(20),@siguiente) END);

        INSERT dbo.th_acciones_personal (
            numero_accion,fecha_elaboracion,empleado_id,tipo_accion,fecha_rige_desde,fecha_rige_hasta,
            explicacion_legal,detalle_otro,presento_declaracion,
            actual_unidad_id,actual_puesto_id,actual_lugar_trabajo,actual_remuneracion,actual_proceso,
            actual_nivel_gestion,actual_grupo_ocupacional,actual_grado,actual_partida_presupuestaria,
            propuesta_unidad_id,propuesta_puesto_id,propuesta_lugar_trabajo,propuesta_remuneracion,propuesta_proceso,
            propuesta_nivel_gestion,propuesta_grupo_ocupacional,propuesta_grado,propuesta_partida_presupuestaria,
            notificacion_electronica,correo_notificacion,medio_notificacion,documento_notificacion,fecha_notificacion,
            responsable_th_nombre,responsable_th_puesto,autoridad_nombre,autoridad_puesto,
            elaborador_nombre,elaborador_puesto,revisor_nombre,revisor_puesto,
            registrador_nombre,registrador_puesto,notificador_nombre,notificador_puesto,
            estado_documento,usuario_crea,fecha_creacion
        ) VALUES (
            @numero_accion,CONVERT(DATE,GETDATE()),@empleado_id,@tipo_accion,@fecha_rige_desde,@fecha_rige_hasta,
            @explicacion_legal,@detalle_otro,@presento_declaracion,
            NULLIF(@actual_unidad_id,0),NULLIF(@actual_puesto_id,0),@actual_lugar_trabajo,@actual_remuneracion,@actual_proceso,
            @actual_nivel_gestion,@actual_grupo_ocupacional,@actual_grado,@actual_partida_presupuestaria,
            NULLIF(@propuesta_unidad_id,0),NULLIF(@propuesta_puesto_id,0),@propuesta_lugar_trabajo,@propuesta_remuneracion,@propuesta_proceso,
            @propuesta_nivel_gestion,@propuesta_grupo_ocupacional,@propuesta_grado,@propuesta_partida_presupuestaria,
            @notificacion_electronica,@correo_notificacion,@medio_notificacion,@documento_notificacion,@fecha_notificacion,
            @responsable_th_nombre,@responsable_th_puesto,@autoridad_nombre,@autoridad_puesto,
            @elaborador_nombre,@elaborador_puesto,@revisor_nombre,@revisor_puesto,
            @registrador_nombre,@registrador_puesto,@notificador_nombre,@notificador_puesto,
            'Aprobado',@usuario,SYSDATETIME()
        );
        DECLARE @accion_id INT=CONVERT(INT,SCOPE_IDENTITY());
        DECLARE @detalle_auditoria VARCHAR(500)=CONCAT('Genero ',@numero_accion,' para empleado #',@empleado_id,'.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Accion de Personal','CREAR',@detalle_auditoria,@ip;
        COMMIT;
        SELECT 1 exito,@accion_id accion_id,@numero_accion numero_accion,'Accion registrada y auditada.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,0 accion_id,ERROR_MESSAGE() mensaje;
    END CATCH;
END;
GO

PRINT 'Migracion de formatos oficiales completada.';
GO
