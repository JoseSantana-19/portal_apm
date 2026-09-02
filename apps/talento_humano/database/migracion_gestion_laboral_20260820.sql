/* Portal Portuario APM - Gestion laboral integral 2026.08.20 */
USE [Talento_Humano];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET XACT_ABORT ON;
GO

/* Situacion laboral vigente del expediente. */
IF COL_LENGTH('dbo.th_empleados','proceso_institucional') IS NULL ALTER TABLE dbo.th_empleados ADD proceso_institucional NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_empleados','nivel_gestion') IS NULL ALTER TABLE dbo.th_empleados ADD nivel_gestion NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_empleados','lugar_trabajo') IS NULL ALTER TABLE dbo.th_empleados ADD lugar_trabajo NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_empleados','grupo_ocupacional') IS NULL ALTER TABLE dbo.th_empleados ADD grupo_ocupacional NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_empleados','grado_laboral') IS NULL ALTER TABLE dbo.th_empleados ADD grado_laboral NVARCHAR(50) NULL;
IF COL_LENGTH('dbo.th_empleados','partida_individual') IS NULL ALTER TABLE dbo.th_empleados ADD partida_individual NVARCHAR(100) NULL;
IF COL_LENGTH('dbo.th_empleados','horas_jornada') IS NULL ALTER TABLE dbo.th_empleados ADD horas_jornada DECIMAL(4,1) NULL;
GO
UPDATE dbo.th_empleados SET horas_jornada=CASE WHEN UPPER(ISNULL(jornada,''))='COMPLETA' THEN 8 ELSE horas_jornada END WHERE horas_jornada IS NULL;
UPDATE dbo.th_empleados SET jornada='Especial',horas_jornada=6 WHERE UPPER(ISNULL(condicion_especial,''))='SUSTITUTO';
GO

/* Fotografia inmutable de cada periodo laboral. */
IF COL_LENGTH('dbo.th_historial_laboral','tipo_contrato') IS NULL ALTER TABLE dbo.th_historial_laboral ADD tipo_contrato NVARCHAR(100) NULL;
IF COL_LENGTH('dbo.th_historial_laboral','sueldo_rmu') IS NULL ALTER TABLE dbo.th_historial_laboral ADD sueldo_rmu DECIMAL(10,2) NULL;
IF COL_LENGTH('dbo.th_historial_laboral','proceso_institucional') IS NULL ALTER TABLE dbo.th_historial_laboral ADD proceso_institucional NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_historial_laboral','nivel_gestion') IS NULL ALTER TABLE dbo.th_historial_laboral ADD nivel_gestion NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_historial_laboral','lugar_trabajo') IS NULL ALTER TABLE dbo.th_historial_laboral ADD lugar_trabajo NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_historial_laboral','grupo_ocupacional') IS NULL ALTER TABLE dbo.th_historial_laboral ADD grupo_ocupacional NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.th_historial_laboral','grado_laboral') IS NULL ALTER TABLE dbo.th_historial_laboral ADD grado_laboral NVARCHAR(50) NULL;
IF COL_LENGTH('dbo.th_historial_laboral','partida_individual') IS NULL ALTER TABLE dbo.th_historial_laboral ADD partida_individual NVARCHAR(100) NULL;
IF COL_LENGTH('dbo.th_historial_laboral','jornada') IS NULL ALTER TABLE dbo.th_historial_laboral ADD jornada NVARCHAR(30) NULL;
IF COL_LENGTH('dbo.th_historial_laboral','horas_jornada') IS NULL ALTER TABLE dbo.th_historial_laboral ADD horas_jornada DECIMAL(4,1) NULL;
IF COL_LENGTH('dbo.th_historial_laboral','condicion_especial') IS NULL ALTER TABLE dbo.th_historial_laboral ADD condicion_especial NVARCHAR(50) NULL;
IF COL_LENGTH('dbo.th_historial_laboral','accion_id') IS NULL ALTER TABLE dbo.th_historial_laboral ADD accion_id INT NULL;
IF COL_LENGTH('dbo.th_historial_laboral','movimiento_id') IS NULL ALTER TABLE dbo.th_historial_laboral ADD movimiento_id INT NULL;
GO
UPDATE h SET tipo_contrato=e.tipo_contrato,sueldo_rmu=e.sueldo_rmu,
    proceso_institucional=e.proceso_institucional,nivel_gestion=e.nivel_gestion,lugar_trabajo=e.lugar_trabajo,
    grupo_ocupacional=e.grupo_ocupacional,grado_laboral=e.grado_laboral,partida_individual=e.partida_individual,
    jornada=e.jornada,horas_jornada=e.horas_jornada,condicion_especial=e.condicion_especial
FROM dbo.th_historial_laboral h JOIN dbo.th_empleados e ON e.empleado_id=h.empleado_id
WHERE h.fecha_hasta IS NULL;
GO

/* Jornadas temporales originadas por Accion de Personal. */
IF OBJECT_ID('dbo.th_jornadas_especiales','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_jornadas_especiales(
        jornada_especial_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_jornadas_especiales PRIMARY KEY,
        empleado_id INT NOT NULL, accion_id INT NOT NULL, tipo_novedad NVARCHAR(80) NOT NULL,
        fecha_desde DATE NOT NULL, fecha_hasta DATE NOT NULL, horas_diarias DECIMAL(4,1) NOT NULL,
        hora_entrada TIME(0) NULL, hora_salida TIME(0) NULL, dias_aplicables NVARCHAR(100) NULL,
        documento_respaldo NVARCHAR(200) NULL, observaciones NVARCHAR(500) NULL,
        estado VARCHAR(20) NOT NULL CONSTRAINT DF_th_jornada_esp_estado DEFAULT('VIGENTE'),
        usuario_crea VARCHAR(50) NOT NULL, fecha_creacion DATETIME2(3) NOT NULL CONSTRAINT DF_th_jornada_esp_fecha DEFAULT(SYSDATETIME()),
        CONSTRAINT FK_th_jornada_esp_emp FOREIGN KEY(empleado_id) REFERENCES dbo.th_empleados(empleado_id),
        CONSTRAINT FK_th_jornada_esp_acc FOREIGN KEY(accion_id) REFERENCES dbo.th_acciones_personal(accion_id),
        CONSTRAINT CK_th_jornada_esp_fechas CHECK(fecha_hasta>=fecha_desde),
        CONSTRAINT CK_th_jornada_esp_horas CHECK(horas_diarias>0 AND horas_diarias<=24)
    );
    CREATE INDEX IX_th_jornada_esp_empleado_fecha ON dbo.th_jornadas_especiales(empleado_id,fecha_desde DESC,fecha_hasta DESC);
END;
GO
IF COL_LENGTH('dbo.th_acciones_personal','actual_jornada') IS NULL ALTER TABLE dbo.th_acciones_personal ADD actual_jornada NVARCHAR(30) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','actual_horas_jornada') IS NULL ALTER TABLE dbo.th_acciones_personal ADD actual_horas_jornada DECIMAL(4,1) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','propuesta_jornada') IS NULL ALTER TABLE dbo.th_acciones_personal ADD propuesta_jornada NVARCHAR(30) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','propuesta_horas_jornada') IS NULL ALTER TABLE dbo.th_acciones_personal ADD propuesta_horas_jornada DECIMAL(4,1) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','tipo_novedad_jornada') IS NULL ALTER TABLE dbo.th_acciones_personal ADD tipo_novedad_jornada NVARCHAR(80) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','hora_entrada_propuesta') IS NULL ALTER TABLE dbo.th_acciones_personal ADD hora_entrada_propuesta TIME(0) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','hora_salida_propuesta') IS NULL ALTER TABLE dbo.th_acciones_personal ADD hora_salida_propuesta TIME(0) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','dias_jornada_propuesta') IS NULL ALTER TABLE dbo.th_acciones_personal ADD dias_jornada_propuesta NVARCHAR(100) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','documento_jornada') IS NULL ALTER TABLE dbo.th_acciones_personal ADD documento_jornada NVARCHAR(200) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','actual_tipo_contrato') IS NULL ALTER TABLE dbo.th_acciones_personal ADD actual_tipo_contrato NVARCHAR(100) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','propuesta_tipo_contrato') IS NULL ALTER TABLE dbo.th_acciones_personal ADD propuesta_tipo_contrato NVARCHAR(100) NULL;
GO

/* Borradores cifrados. El payload nunca se almacena en texto plano. */
IF OBJECT_ID('dbo.th_borradores_formulario','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_borradores_formulario(
        borrador_id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_borradores_formulario PRIMARY KEY,
        usuario_id INT NOT NULL, contexto VARCHAR(180) NOT NULL, payload_cifrado NVARCHAR(MAX) NOT NULL,
        iv VARCHAR(64) NOT NULL, tag_auth VARCHAR(64) NOT NULL,
        fecha_actualizacion DATETIME2(3) NOT NULL CONSTRAINT DF_th_borrador_fecha DEFAULT(SYSDATETIME()),
        fecha_expiracion DATETIME2(3) NOT NULL,
        CONSTRAINT FK_th_borrador_usuario FOREIGN KEY(usuario_id) REFERENCES dbo.th_usuarios_sistema(usuario_id),
        CONSTRAINT UX_th_borrador_usuario_contexto UNIQUE(usuario_id,contexto)
    );
END;
GO
CREATE OR ALTER PROCEDURE dbo.sp_th_guardar_borrador
    @usuario_id INT,@contexto VARCHAR(180),@payload_cifrado NVARCHAR(MAX),@iv VARCHAR(64),@tag_auth VARCHAR(64)
AS
BEGIN
    SET NOCOUNT ON;
    IF NULLIF(@contexto,'') IS NULL OR DATALENGTH(@payload_cifrado)>2097152 THROW 51800,'Borrador no valido.',1;
    UPDATE dbo.th_borradores_formulario SET payload_cifrado=@payload_cifrado,iv=@iv,tag_auth=@tag_auth,
        fecha_actualizacion=SYSDATETIME(),fecha_expiracion=DATEADD(DAY,15,SYSDATETIME())
    WHERE usuario_id=@usuario_id AND contexto=@contexto;
    IF @@ROWCOUNT=0 INSERT dbo.th_borradores_formulario(usuario_id,contexto,payload_cifrado,iv,tag_auth,fecha_expiracion)
        VALUES(@usuario_id,@contexto,@payload_cifrado,@iv,@tag_auth,DATEADD(DAY,15,SYSDATETIME()));
END;
GO
CREATE OR ALTER PROCEDURE dbo.sp_th_obtener_borrador @usuario_id INT,@contexto VARCHAR(180)
AS
BEGIN
    SET NOCOUNT ON;
    DELETE dbo.th_borradores_formulario WHERE fecha_expiracion<SYSDATETIME();
    SELECT payload_cifrado,iv,tag_auth,fecha_actualizacion FROM dbo.th_borradores_formulario
    WHERE usuario_id=@usuario_id AND contexto=@contexto;
END;
GO
CREATE OR ALTER PROCEDURE dbo.sp_th_eliminar_borrador @usuario_id INT,@contexto VARCHAR(180)
AS BEGIN SET NOCOUNT ON; DELETE dbo.th_borradores_formulario WHERE usuario_id=@usuario_id AND contexto=@contexto; END;
GO

/* Guardado integral del expediente. */
CREATE OR ALTER PROCEDURE dbo.sp_th_guardar_empleado_v2
    @cedula VARCHAR(20),@apellidos VARCHAR(100),@nombres VARCHAR(100),@fecha_nac DATE=NULL,@condicion VARCHAR(50)='Ninguna',
    @tipo_disc VARCHAR(80)=NULL,@porcentaje_disc DECIMAL(5,2)=NULL,@sexo CHAR(1)=NULL,@estado_civil VARCHAR(30)=NULL,
    @nacionalidad VARCHAR(50)=NULL,@tipo_sangre VARCHAR(10)=NULL,@depto INT=NULL,@puesto INT=NULL,@tipo_contrato VARCHAR(100)=NULL,
    @fecha_ing DATE=NULL,@sueldo DECIMAL(10,2)=NULL,@jornada VARCHAR(30)='Completa',@horas_jornada DECIMAL(4,1)=NULL,
    @proceso_institucional NVARCHAR(150)=NULL,@nivel_gestion NVARCHAR(150)=NULL,@lugar_trabajo NVARCHAR(150)=NULL,
    @grupo_ocupacional NVARCHAR(150)=NULL,@grado_laboral NVARCHAR(50)=NULL,@partida_individual NVARCHAR(100)=NULL,
    @correo VARCHAR(100)=NULL,@celular VARCHAR(20)=NULL,@convencional VARCHAR(20)=NULL,@ciudad VARCHAR(50)=NULL,@direccion VARCHAR(MAX)=NULL,
    @contacto_emerg VARCHAR(150)=NULL,@parentesco VARCHAR(50)=NULL,@tel_emerg VARCHAR(20)=NULL,@nivel_estudio VARCHAR(80)=NULL,
    @titulo VARCHAR(150)=NULL,@iess VARCHAR(30)=NULL,@foto VARCHAR(300)='public/img/default_avatar.png',@obs VARCHAR(MAX)=NULL,
    @usuario VARCHAR(50)='SISTEMA',@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF NULLIF(LTRIM(RTRIM(@apellidos)),'') IS NULL OR NULLIF(LTRIM(RTRIM(@nombres)),'') IS NULL THROW 51801,'Nombres y apellidos son obligatorios.',1;
        IF EXISTS(SELECT 1 FROM dbo.th_empleados WHERE identificacion=@cedula) THROW 51802,'Ya existe un empleado con esa identificacion.',1;
        IF @horas_jornada<=0 OR @horas_jornada>24 THROW 51803,'Horas de jornada no validas.',1;
        IF UPPER(@condicion)='SUSTITUTO' AND (UPPER(@jornada)<>'ESPECIAL' OR @horas_jornada<>6) THROW 51804,'Sustituto requiere jornada especial de 6 horas.',1;
        INSERT dbo.th_empleados(identificacion,apellidos,nombres,fecha_nacimiento,sexo,estado_civil,nacionalidad,unidad_id,puesto_id,
            fecha_ingreso,sueldo_rmu,correo_institucional,telefono_movil,telefono_convencional,ciudad_residencia,direccion_domiciliaria,
            codigo_iess,ruta_foto,observaciones,tipo_contrato,jornada,horas_jornada,condicion_especial,tipo_discapacidad,porcentaje_discapacidad,
            tipo_sangre,contacto_emergencia,emergencia_relacion,tel_emergencia,nivel_estudio,titulo,estado,cargas_familiares,fecha_creacion,
            proceso_institucional,nivel_gestion,lugar_trabajo,grupo_ocupacional,grado_laboral,partida_individual)
        VALUES(@cedula,UPPER(LTRIM(RTRIM(@apellidos))),UPPER(LTRIM(RTRIM(@nombres))),@fecha_nac,@sexo,@estado_civil,@nacionalidad,@depto,@puesto,
            @fecha_ing,@sueldo,ISNULL(@correo,''),ISNULL(@celular,''),@convencional,ISNULL(@ciudad,''),ISNULL(@direccion,''),@iess,
            ISNULL(@foto,'public/img/default_avatar.png'),@obs,@tipo_contrato,@jornada,@horas_jornada,@condicion,@tipo_disc,@porcentaje_disc,
            @tipo_sangre,@contacto_emerg,@parentesco,@tel_emerg,@nivel_estudio,@titulo,1,0,SYSDATETIME(),@proceso_institucional,@nivel_gestion,
            @lugar_trabajo,@grupo_ocupacional,@grado_laboral,@partida_individual);
        DECLARE @nuevo_id INT=CONVERT(INT,SCOPE_IDENTITY());
        IF @depto IS NOT NULL AND @puesto IS NOT NULL
            INSERT dbo.th_historial_laboral(empleado_id,puesto_id,unidad_id,fecha_desde,fecha_hasta,observaciones,usuario_crea,fecha_creacion,
                tipo_contrato,sueldo_rmu,proceso_institucional,nivel_gestion,lugar_trabajo,grupo_ocupacional,grado_laboral,partida_individual,jornada,horas_jornada,condicion_especial)
            VALUES(@nuevo_id,@puesto,@depto,COALESCE(@fecha_ing,CONVERT(date,SYSDATETIME())),NULL,'Registro inicial del expediente.',@usuario,SYSDATETIME(),
                @tipo_contrato,@sueldo,@proceso_institucional,@nivel_gestion,@lugar_trabajo,@grupo_ocupacional,@grado_laboral,@partida_individual,@jornada,@horas_jornada,@condicion);
        DECLARE @auditoria_crear NVARCHAR(500)=CONCAT('Empleado #',@nuevo_id,' C.I. ',@cedula,' registrado.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Directorio de Personal','CREAR',@auditoria_crear,@ip;
        COMMIT;SELECT @nuevo_id nuevo_id,1 exito,'Empleado guardado correctamente.' mensaje;
    END TRY BEGIN CATCH IF @@TRANCOUNT>0 ROLLBACK;SELECT 0 nuevo_id,0 exito,ERROR_MESSAGE() mensaje;END CATCH
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_modificar_empleado_v2
    @id INT,@cedula VARCHAR(20),@apellidos VARCHAR(100),@nombres VARCHAR(100),@fecha_nac DATE=NULL,@condicion VARCHAR(50)='Ninguna',
    @tipo_disc VARCHAR(80)=NULL,@porcentaje_disc DECIMAL(5,2)=NULL,@sexo CHAR(1)=NULL,@estado_civil VARCHAR(30)=NULL,
    @nacionalidad VARCHAR(50)=NULL,@tipo_sangre VARCHAR(10)=NULL,@depto INT=NULL,@puesto INT=NULL,@tipo_contrato VARCHAR(100)=NULL,
    @fecha_ing DATE=NULL,@sueldo DECIMAL(10,2)=NULL,@jornada VARCHAR(30)='Completa',@horas_jornada DECIMAL(4,1)=NULL,
    @proceso_institucional NVARCHAR(150)=NULL,@nivel_gestion NVARCHAR(150)=NULL,@lugar_trabajo NVARCHAR(150)=NULL,
    @grupo_ocupacional NVARCHAR(150)=NULL,@grado_laboral NVARCHAR(50)=NULL,@partida_individual NVARCHAR(100)=NULL,
    @correo VARCHAR(100)=NULL,@celular VARCHAR(20)=NULL,@convencional VARCHAR(20)=NULL,@ciudad VARCHAR(50)=NULL,@direccion VARCHAR(MAX)=NULL,
    @contacto_emerg VARCHAR(150)=NULL,@parentesco VARCHAR(50)=NULL,@tel_emerg VARCHAR(20)=NULL,@nivel_estudio VARCHAR(80)=NULL,
    @titulo VARCHAR(150)=NULL,@iess VARCHAR(30)=NULL,@foto VARCHAR(300)=NULL,@obs VARCHAR(MAX)=NULL,@usuario VARCHAR(50)='SISTEMA',@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF EXISTS(SELECT 1 FROM dbo.th_empleados WHERE identificacion=@cedula AND empleado_id<>@id) THROW 51805,'La identificacion pertenece a otro empleado.',1;
        IF @horas_jornada<=0 OR @horas_jornada>24 THROW 51806,'Horas de jornada no validas.',1;
        IF UPPER(@condicion)='SUSTITUTO' AND (UPPER(@jornada)<>'ESPECIAL' OR @horas_jornada<>6) THROW 51807,'Sustituto requiere jornada especial de 6 horas.',1;
        UPDATE dbo.th_empleados SET identificacion=@cedula,apellidos=UPPER(LTRIM(RTRIM(@apellidos))),nombres=UPPER(LTRIM(RTRIM(@nombres))),
            fecha_nacimiento=@fecha_nac,sexo=@sexo,estado_civil=@estado_civil,nacionalidad=@nacionalidad,unidad_id=@depto,puesto_id=@puesto,
            fecha_ingreso=@fecha_ing,sueldo_rmu=@sueldo,correo_institucional=ISNULL(@correo,correo_institucional),telefono_movil=ISNULL(@celular,telefono_movil),
            telefono_convencional=@convencional,ciudad_residencia=ISNULL(@ciudad,ciudad_residencia),direccion_domiciliaria=ISNULL(@direccion,direccion_domiciliaria),
            codigo_iess=@iess,ruta_foto=ISNULL(@foto,ruta_foto),observaciones=@obs,tipo_contrato=@tipo_contrato,jornada=@jornada,horas_jornada=@horas_jornada,
            condicion_especial=@condicion,tipo_discapacidad=@tipo_disc,porcentaje_discapacidad=@porcentaje_disc,tipo_sangre=@tipo_sangre,
            contacto_emergencia=@contacto_emerg,emergencia_relacion=@parentesco,tel_emergencia=@tel_emerg,nivel_estudio=@nivel_estudio,titulo=@titulo,
            proceso_institucional=@proceso_institucional,nivel_gestion=@nivel_gestion,lugar_trabajo=@lugar_trabajo,grupo_ocupacional=@grupo_ocupacional,
            grado_laboral=@grado_laboral,partida_individual=@partida_individual WHERE empleado_id=@id;
        IF @@ROWCOUNT=0 THROW 51808,'El empleado indicado no existe.',1;
        UPDATE dbo.th_historial_laboral SET puesto_id=@puesto,unidad_id=@depto,tipo_contrato=@tipo_contrato,sueldo_rmu=@sueldo,
            proceso_institucional=@proceso_institucional,nivel_gestion=@nivel_gestion,lugar_trabajo=@lugar_trabajo,grupo_ocupacional=@grupo_ocupacional,
            grado_laboral=@grado_laboral,partida_individual=@partida_individual,jornada=@jornada,horas_jornada=@horas_jornada,condicion_especial=@condicion
        WHERE empleado_id=@id AND fecha_hasta IS NULL;
        DECLARE @auditoria_actualizar NVARCHAR(500)=CONCAT('Empleado #',@id,' actualizado.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Directorio de Personal','ACTUALIZAR',@auditoria_actualizar,@ip;
        COMMIT;SELECT 1 filas_afectadas,1 exito,'Empleado actualizado.' mensaje;
    END TRY BEGIN CATCH IF @@TRANCOUNT>0 ROLLBACK;SELECT 0 filas_afectadas,0 exito,ERROR_MESSAGE() mensaje;END CATCH
END;
GO

/* Registro documental ampliado con la jornada vigente y la novedad temporal. */
CREATE OR ALTER PROCEDURE dbo.sp_th_registrar_accion_personal_v2
    @numero_accion VARCHAR(50),@empleado_id INT,@tipo_accion VARCHAR(100),@fecha_rige_desde DATE,@fecha_rige_hasta DATE=NULL,
    @explicacion_legal VARCHAR(MAX),@detalle_otro NVARCHAR(255)=NULL,@presento_declaracion VARCHAR(20)=NULL,
    @actual_unidad_id INT=NULL,@actual_puesto_id INT=NULL,@actual_lugar_trabajo NVARCHAR(150)=NULL,@actual_remuneracion DECIMAL(10,2)=NULL,
    @actual_proceso NVARCHAR(150)=NULL,@actual_nivel_gestion NVARCHAR(150)=NULL,@actual_grupo_ocupacional NVARCHAR(150)=NULL,
    @actual_grado NVARCHAR(50)=NULL,@actual_partida_presupuestaria NVARCHAR(100)=NULL,
    @propuesta_unidad_id INT=NULL,@propuesta_puesto_id INT=NULL,@propuesta_lugar_trabajo NVARCHAR(150)=NULL,@propuesta_remuneracion DECIMAL(10,2)=NULL,
    @propuesta_proceso NVARCHAR(150)=NULL,@propuesta_nivel_gestion NVARCHAR(150)=NULL,@propuesta_grupo_ocupacional NVARCHAR(150)=NULL,
    @propuesta_grado NVARCHAR(50)=NULL,@propuesta_partida_presupuestaria NVARCHAR(100)=NULL,
    @actual_jornada NVARCHAR(30)=NULL,@actual_horas_jornada DECIMAL(4,1)=NULL,@propuesta_jornada NVARCHAR(30)=NULL,
    @propuesta_horas_jornada DECIMAL(4,1)=NULL,@tipo_novedad_jornada NVARCHAR(80)=NULL,@hora_entrada_propuesta TIME(0)=NULL,
    @hora_salida_propuesta TIME(0)=NULL,@dias_jornada_propuesta NVARCHAR(100)=NULL,@documento_jornada NVARCHAR(200)=NULL,
    @actual_tipo_contrato NVARCHAR(100)=NULL,@propuesta_tipo_contrato NVARCHAR(100)=NULL,
    @notificacion_electronica BIT=0,@correo_notificacion NVARCHAR(150)=NULL,@medio_notificacion NVARCHAR(100)=NULL,
    @documento_notificacion NVARCHAR(100)=NULL,@fecha_notificacion DATETIME2(0)=NULL,
    @responsable_th_nombre NVARCHAR(150)=NULL,@responsable_th_puesto NVARCHAR(150)=NULL,@autoridad_nombre NVARCHAR(150)=NULL,
    @autoridad_puesto NVARCHAR(150)=NULL,@elaborador_nombre NVARCHAR(150)=NULL,@elaborador_puesto NVARCHAR(150)=NULL,
    @revisor_nombre NVARCHAR(150)=NULL,@revisor_puesto NVARCHAR(150)=NULL,@registrador_nombre NVARCHAR(150)=NULL,
    @registrador_puesto NVARCHAR(150)=NULL,@notificador_nombre NVARCHAR(150)=NULL,@notificador_puesto NVARCHAR(150)=NULL,
    @usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_empleados WHERE empleado_id=@empleado_id)
            THROW 51840,'El empleado indicado no existe.',1;
        IF NULLIF(LTRIM(RTRIM(@tipo_accion)),'') IS NULL OR NULLIF(LTRIM(RTRIM(@explicacion_legal)),'') IS NULL
            THROW 51841,'El tipo y la motivacion de la accion son obligatorios.',1;
        IF @fecha_rige_hasta IS NOT NULL AND @fecha_rige_hasta<@fecha_rige_desde
            THROW 51842,'El fin de vigencia no puede ser anterior al inicio.',1;

        DECLARE @anio CHAR(4)=CONVERT(CHAR(4),YEAR(SYSDATETIME()));
        DECLARE @prefijo VARCHAR(30)=CONCAT('APM-TH-',@anio,'-');
        DECLARE @resultado_lock INT,@recurso_lock NVARCHAR(255)=CONCAT('th_acciones_personal_secuencial_',@anio);
        EXEC @resultado_lock=sys.sp_getapplock @Resource=@recurso_lock,@LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=10000;
        IF @resultado_lock<0 THROW 51843,'No fue posible reservar el numero de Accion de Personal.',1;
        DECLARE @siguiente INT=COALESCE((SELECT MAX(TRY_CONVERT(INT,SUBSTRING(numero_accion,LEN(@prefijo)+1,20)))
            FROM dbo.th_acciones_personal WITH(UPDLOCK,HOLDLOCK) WHERE numero_accion LIKE @prefijo+'%'),0)+1;
        SET @numero_accion=CONCAT(@prefijo,CASE WHEN @siguiente<1000 THEN RIGHT(CONCAT('000',@siguiente),3) ELSE CONVERT(VARCHAR(20),@siguiente) END);

        INSERT dbo.th_acciones_personal(
            numero_accion,fecha_elaboracion,empleado_id,tipo_accion,fecha_rige_desde,fecha_rige_hasta,explicacion_legal,
            detalle_otro,presento_declaracion,actual_unidad_id,actual_puesto_id,actual_lugar_trabajo,actual_remuneracion,
            actual_proceso,actual_nivel_gestion,actual_grupo_ocupacional,actual_grado,actual_partida_presupuestaria,
            propuesta_unidad_id,propuesta_puesto_id,propuesta_lugar_trabajo,propuesta_remuneracion,propuesta_proceso,
            propuesta_nivel_gestion,propuesta_grupo_ocupacional,propuesta_grado,propuesta_partida_presupuestaria,
            actual_jornada,actual_horas_jornada,propuesta_jornada,propuesta_horas_jornada,tipo_novedad_jornada,
            hora_entrada_propuesta,hora_salida_propuesta,dias_jornada_propuesta,documento_jornada,
            actual_tipo_contrato,propuesta_tipo_contrato,notificacion_electronica,correo_notificacion,medio_notificacion,
            documento_notificacion,fecha_notificacion,responsable_th_nombre,responsable_th_puesto,autoridad_nombre,
            autoridad_puesto,elaborador_nombre,elaborador_puesto,revisor_nombre,revisor_puesto,registrador_nombre,
            registrador_puesto,notificador_nombre,notificador_puesto,estado_documento,usuario_crea,fecha_creacion
        ) VALUES(
            @numero_accion,CONVERT(date,SYSDATETIME()),@empleado_id,@tipo_accion,@fecha_rige_desde,@fecha_rige_hasta,@explicacion_legal,
            NULLIF(@detalle_otro,''),NULLIF(@presento_declaracion,''),NULLIF(@actual_unidad_id,0),NULLIF(@actual_puesto_id,0),
            NULLIF(@actual_lugar_trabajo,''),@actual_remuneracion,NULLIF(@actual_proceso,''),NULLIF(@actual_nivel_gestion,''),
            NULLIF(@actual_grupo_ocupacional,''),NULLIF(@actual_grado,''),NULLIF(@actual_partida_presupuestaria,''),
            NULLIF(@propuesta_unidad_id,0),NULLIF(@propuesta_puesto_id,0),NULLIF(@propuesta_lugar_trabajo,''),NULLIF(@propuesta_remuneracion,0),
            NULLIF(@propuesta_proceso,''),NULLIF(@propuesta_nivel_gestion,''),NULLIF(@propuesta_grupo_ocupacional,''),
            NULLIF(@propuesta_grado,''),NULLIF(@propuesta_partida_presupuestaria,''),NULLIF(@actual_jornada,''),
            NULLIF(@actual_horas_jornada,0),NULLIF(@propuesta_jornada,''),NULLIF(@propuesta_horas_jornada,0),
            NULLIF(@tipo_novedad_jornada,''),@hora_entrada_propuesta,@hora_salida_propuesta,NULLIF(@dias_jornada_propuesta,''),
            NULLIF(@documento_jornada,''),NULLIF(@actual_tipo_contrato,''),NULLIF(@propuesta_tipo_contrato,''),
            @notificacion_electronica,NULLIF(@correo_notificacion,''),NULLIF(@medio_notificacion,''),NULLIF(@documento_notificacion,''),
            @fecha_notificacion,NULLIF(@responsable_th_nombre,''),NULLIF(@responsable_th_puesto,''),NULLIF(@autoridad_nombre,''),
            NULLIF(@autoridad_puesto,''),NULLIF(@elaborador_nombre,''),NULLIF(@elaborador_puesto,''),NULLIF(@revisor_nombre,''),
            NULLIF(@revisor_puesto,''),NULLIF(@registrador_nombre,''),NULLIF(@registrador_puesto,''),NULLIF(@notificador_nombre,''),
            NULLIF(@notificador_puesto,''),'BORRADOR',@usuario,SYSDATETIME()
        );
        DECLARE @accion_id INT=CONVERT(INT,SCOPE_IDENTITY());
        DECLARE @auditoria_borrador NVARCHAR(500)=CONCAT('Genero ',@numero_accion,' para empleado #',@empleado_id,'; pendiente de aprobacion.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Accion de Personal','CREAR_BORRADOR',@auditoria_borrador,@ip;
        COMMIT;
        SELECT 1 exito,@accion_id accion_id,@numero_accion numero_accion,'Accion registrada como borrador y auditada.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,0 accion_id,CAST(NULL AS VARCHAR(50)) numero_accion,ERROR_MESSAGE() mensaje;
    END CATCH
END;
GO

/* Aprobacion atomica: los cambios temporales no fragmentan el cargo historico. */
CREATE OR ALTER PROCEDURE dbo.sp_th_aprobar_accion_personal_v2
    @accion_id INT,@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        DECLARE @empleado INT,@fecha DATE,@hasta DATE,@numero VARCHAR(50),@tipo NVARCHAR(100),@tipo_norm NVARCHAR(100),@estado BIT,
            @u_actual INT,@p_actual INT,@u_nueva INT,@p_nuevo INT,@rmu DECIMAL(10,2),@proceso NVARCHAR(150),@nivel NVARCHAR(150),
            @lugar NVARCHAR(150),@grupo NVARCHAR(150),@grado NVARCHAR(50),@partida NVARCHAR(100),@jornada NVARCHAR(30),
            @horas DECIMAL(4,1),@contrato NVARCHAR(100),@novedad NVARCHAR(80),@entrada TIME(0),@salida TIME(0),
            @dias NVARCHAR(100),@documento NVARCHAR(200);
        SELECT @empleado=a.empleado_id,@fecha=a.fecha_rige_desde,@hasta=a.fecha_rige_hasta,@numero=a.numero_accion,@tipo=a.tipo_accion,
            @u_actual=e.unidad_id,@p_actual=e.puesto_id,@u_nueva=COALESCE(a.propuesta_unidad_id,e.unidad_id),
            @p_nuevo=COALESCE(a.propuesta_puesto_id,e.puesto_id),@rmu=COALESCE(NULLIF(a.propuesta_remuneracion,0),e.sueldo_rmu),
            @proceso=COALESCE(NULLIF(a.propuesta_proceso,''),e.proceso_institucional),@nivel=COALESCE(NULLIF(a.propuesta_nivel_gestion,''),e.nivel_gestion),
            @lugar=COALESCE(NULLIF(a.propuesta_lugar_trabajo,''),e.lugar_trabajo),@grupo=COALESCE(NULLIF(a.propuesta_grupo_ocupacional,''),e.grupo_ocupacional),
            @grado=COALESCE(NULLIF(a.propuesta_grado,''),e.grado_laboral),@partida=COALESCE(NULLIF(a.propuesta_partida_presupuestaria,''),e.partida_individual),
            @jornada=COALESCE(NULLIF(a.propuesta_jornada,''),e.jornada),@horas=COALESCE(NULLIF(a.propuesta_horas_jornada,0),e.horas_jornada),
            @contrato=COALESCE(NULLIF(a.propuesta_tipo_contrato,''),e.tipo_contrato),@novedad=NULLIF(a.tipo_novedad_jornada,''),
            @entrada=a.hora_entrada_propuesta,@salida=a.hora_salida_propuesta,@dias=a.dias_jornada_propuesta,@documento=a.documento_jornada,@estado=e.estado
        FROM dbo.th_acciones_personal a WITH(UPDLOCK,HOLDLOCK)
        JOIN dbo.th_empleados e WITH(UPDLOCK,HOLDLOCK) ON e.empleado_id=a.empleado_id
        WHERE a.accion_id=@accion_id AND UPPER(a.estado_documento) IN ('BORRADOR','PENDIENTE');
        IF @empleado IS NULL THROW 51830,'La accion no existe o ya fue resuelta.',1;
        SET @tipo_norm=UPPER(LTRIM(RTRIM(ISNULL(@tipo,'')))) COLLATE Modern_Spanish_CI_AI;
        DECLARE @es_cese BIT=IIF(@tipo_norm IN ('CESACION DE FUNCIONES','DESTITUCION'),1,0),
                @es_reingreso BIT=IIF(@tipo_norm IN ('INGRESO','REINGRESO','RESTITUCION','REINTEGRO'),1,0);
        IF @es_cese=1
            EXEC dbo.sp_th_cambiar_estado_empleado @empleado,0,@fecha,N'Cesacion mediante Accion de Personal.','ACCION_PERSONAL',@accion_id,@usuario,@ip,1,0;
        ELSE IF @es_reingreso=1
            EXEC dbo.sp_th_cambiar_estado_empleado @empleado,1,@fecha,N'Ingreso o reingreso mediante Accion de Personal.','ACCION_PERSONAL',@accion_id,@usuario,@ip,0,0;
        ELSE IF @estado=0 THROW 51831,'El funcionario inactivo requiere una accion de reingreso.',1;

        IF @novedad IS NOT NULL
        BEGIN
            IF @hasta IS NULL OR @hasta<@fecha OR @horas IS NULL OR @horas<=0 OR @horas>24 THROW 51832,'La jornada temporal requiere fechas y horas validas.',1;
            INSERT dbo.th_jornadas_especiales(empleado_id,accion_id,tipo_novedad,fecha_desde,fecha_hasta,horas_diarias,hora_entrada,hora_salida,
                dias_aplicables,documento_respaldo,observaciones,usuario_crea)
            VALUES(@empleado,@accion_id,@novedad,@fecha,@hasta,@horas,@entrada,@salida,@dias,@documento,
                CONCAT('Registrada por Accion de Personal ',@numero,'.'),@usuario);
        END
        ELSE IF @es_cese=0
        BEGIN
            DECLARE @cambio BIT=IIF(ISNULL(@u_nueva,0)<>ISNULL(@u_actual,0) OR ISNULL(@p_nuevo,0)<>ISNULL(@p_actual,0)
                OR EXISTS(SELECT 1 FROM dbo.th_empleados WHERE empleado_id=@empleado AND (
                    ISNULL(sueldo_rmu,0)<>ISNULL(@rmu,0) OR ISNULL(proceso_institucional,'')<>ISNULL(@proceso,'') OR ISNULL(nivel_gestion,'')<>ISNULL(@nivel,'')
                    OR ISNULL(lugar_trabajo,'')<>ISNULL(@lugar,'') OR ISNULL(grupo_ocupacional,'')<>ISNULL(@grupo,'') OR ISNULL(grado_laboral,'')<>ISNULL(@grado,'')
                    OR ISNULL(partida_individual,'')<>ISNULL(@partida,'') OR ISNULL(jornada,'')<>ISNULL(@jornada,'') OR ISNULL(horas_jornada,0)<>ISNULL(@horas,0)
                    OR ISNULL(tipo_contrato,'')<>ISNULL(@contrato,''))),1,0);
            IF @cambio=1
            BEGIN
                UPDATE dbo.th_historial_laboral SET fecha_hasta=CASE WHEN fecha_desde<@fecha THEN DATEADD(DAY,-1,@fecha) ELSE @fecha END
                WHERE empleado_id=@empleado AND fecha_hasta IS NULL;
                INSERT dbo.th_historial_laboral(empleado_id,puesto_id,unidad_id,fecha_desde,observaciones,usuario_crea,fecha_creacion,accion_id,
                    tipo_contrato,sueldo_rmu,proceso_institucional,nivel_gestion,lugar_trabajo,grupo_ocupacional,grado_laboral,partida_individual,jornada,horas_jornada,condicion_especial)
                SELECT empleado_id,@p_nuevo,@u_nueva,@fecha,CONCAT('Aplicado por Accion de Personal ',@numero),@usuario,SYSDATETIME(),@accion_id,
                    @contrato,@rmu,@proceso,@nivel,@lugar,@grupo,@grado,@partida,@jornada,@horas,condicion_especial
                FROM dbo.th_empleados WHERE empleado_id=@empleado;
            END;
            UPDATE dbo.th_empleados SET unidad_id=@u_nueva,puesto_id=@p_nuevo,sueldo_rmu=@rmu,proceso_institucional=@proceso,
                nivel_gestion=@nivel,lugar_trabajo=@lugar,grupo_ocupacional=@grupo,grado_laboral=@grado,partida_individual=@partida,
                jornada=@jornada,horas_jornada=@horas,tipo_contrato=@contrato WHERE empleado_id=@empleado;
        END;
        UPDATE dbo.th_acciones_personal SET estado_documento='APROBADO',usuario_aprueba=@usuario,fecha_aprobacion=SYSDATETIME() WHERE accion_id=@accion_id;
        DECLARE @auditoria_aprobar NVARCHAR(500)=CONCAT('Aprobo ',@numero,' (',@tipo,').');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Accion de Personal','APROBAR',@auditoria_aprobar,@ip;
        COMMIT;SELECT 1 exito,'Accion aprobada; expediente, jornada e historial sincronizados.' mensaje;
    END TRY BEGIN CATCH IF @@TRANCOUNT>0 ROLLBACK;SELECT 0 exito,ERROR_MESSAGE() mensaje;END CATCH
END;
GO

/* Movimientos internos: cambia exclusivamente el area y conserva cada cargo. */
IF COL_LENGTH('dbo.th_movimientos_lote','puesto_destino_id') IS NOT NULL ALTER TABLE dbo.th_movimientos_lote ALTER COLUMN puesto_destino_id INT NULL;
GO
CREATE OR ALTER PROCEDURE dbo.sp_th_mover_empleado
    @empleado_id INT,@unidad_destino_id INT,@fecha_movimiento DATE,@motivo VARCHAR(500),@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        DECLARE @unidad_origen_id INT,@puesto_actual_id INT,@movimiento_id INT;
        SELECT @unidad_origen_id=unidad_id,@puesto_actual_id=puesto_id FROM dbo.th_empleados WITH(UPDLOCK,HOLDLOCK) WHERE empleado_id=@empleado_id AND estado=1;
        IF @unidad_origen_id IS NULL OR @puesto_actual_id IS NULL THROW 51810,'Empleado inactivo o sin asignacion actual.',1;
        IF @unidad_origen_id=@unidad_destino_id THROW 51811,'El area de destino coincide con el area actual.',1;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_unidades_organizacionales WHERE unidad_id=@unidad_destino_id AND activo=1) THROW 51812,'Area de destino no valida.',1;
        IF NULLIF(LTRIM(RTRIM(@motivo)),'') IS NULL THROW 51813,'El motivo es obligatorio.',1;
        IF EXISTS(SELECT 1 FROM dbo.th_historial_laboral WHERE empleado_id=@empleado_id AND fecha_hasta IS NULL AND @fecha_movimiento<fecha_desde) THROW 51814,'Fecha anterior al periodo vigente.',1;
        INSERT dbo.th_movimientos_personal(empleado_id,unidad_origen_id,puesto_origen_id,unidad_destino_id,puesto_destino_id,fecha_movimiento,motivo,usuario_crea,direccion_ip)
        VALUES(@empleado_id,@unidad_origen_id,@puesto_actual_id,@unidad_destino_id,@puesto_actual_id,@fecha_movimiento,LTRIM(RTRIM(@motivo)),@usuario,@ip);
        SET @movimiento_id=CONVERT(INT,SCOPE_IDENTITY());
        UPDATE dbo.th_historial_laboral SET fecha_hasta=CASE WHEN fecha_desde<@fecha_movimiento THEN DATEADD(DAY,-1,@fecha_movimiento) ELSE @fecha_movimiento END
        WHERE empleado_id=@empleado_id AND fecha_hasta IS NULL;
        INSERT dbo.th_historial_laboral(empleado_id,puesto_id,unidad_id,fecha_desde,observaciones,usuario_crea,fecha_creacion,movimiento_id,
            tipo_contrato,sueldo_rmu,proceso_institucional,nivel_gestion,lugar_trabajo,grupo_ocupacional,grado_laboral,partida_individual,jornada,horas_jornada,condicion_especial)
        SELECT empleado_id,puesto_id,@unidad_destino_id,@fecha_movimiento,CONCAT('Movimiento interno de area. ',LTRIM(RTRIM(@motivo))),@usuario,SYSDATETIME(),@movimiento_id,
            tipo_contrato,sueldo_rmu,proceso_institucional,nivel_gestion,lugar_trabajo,grupo_ocupacional,grado_laboral,partida_individual,jornada,horas_jornada,condicion_especial
        FROM dbo.th_empleados WHERE empleado_id=@empleado_id;
        UPDATE dbo.th_empleados SET unidad_id=@unidad_destino_id WHERE empleado_id=@empleado_id;
        DECLARE @auditoria_movimiento NVARCHAR(500)=CONCAT('Movimiento #',@movimiento_id,' empleado #',@empleado_id,'; cargo conservado #',@puesto_actual_id,'.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Movimiento de Personal','MOVER_AREA',@auditoria_movimiento,@ip;
        COMMIT;SELECT 1 exito,@movimiento_id movimiento_id,'Movimiento de area registrado; el cargo fue conservado.' mensaje;
    END TRY BEGIN CATCH IF @@TRANCOUNT>0 ROLLBACK;SELECT 0 exito,0 movimiento_id,ERROR_MESSAGE() mensaje;END CATCH
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_mover_empleados_lote
    @empleados_json NVARCHAR(MAX),@unidad_destino_id INT,@fecha_movimiento DATE,@motivo VARCHAR(500),@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF ISJSON(@empleados_json)<>1 THROW 51820,'Seleccion no valida.',1;
        DECLARE @s TABLE(empleado_id INT PRIMARY KEY,unidad_id INT,puesto_id INT);
        INSERT @s(empleado_id) SELECT DISTINCT TRY_CONVERT(INT,[value]) FROM OPENJSON(@empleados_json) WHERE TRY_CONVERT(INT,[value]) IS NOT NULL;
        IF (SELECT COUNT(*) FROM @s)<2 THROW 51821,'Seleccione al menos dos empleados.',1;
        UPDATE s SET unidad_id=e.unidad_id,puesto_id=e.puesto_id FROM @s s JOIN dbo.th_empleados e WITH(UPDLOCK,HOLDLOCK) ON e.empleado_id=s.empleado_id AND e.estado=1;
        IF EXISTS(SELECT 1 FROM @s WHERE unidad_id IS NULL OR puesto_id IS NULL) THROW 51822,'La seleccion contiene empleados no disponibles.',1;
        IF EXISTS(SELECT 1 FROM @s WHERE unidad_id=@unidad_destino_id) THROW 51823,'Al menos un empleado ya pertenece al area de destino.',1;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_unidades_organizacionales WHERE unidad_id=@unidad_destino_id AND activo=1) THROW 51824,'Area de destino no valida.',1;
        IF NULLIF(LTRIM(RTRIM(@motivo)),'') IS NULL THROW 51825,'El motivo es obligatorio.',1;
        INSERT dbo.th_movimientos_lote(unidad_destino_id,puesto_destino_id,fecha_movimiento,motivo,cantidad,usuario_crea,direccion_ip)
        VALUES(@unidad_destino_id,NULL,@fecha_movimiento,LTRIM(RTRIM(@motivo)),(SELECT COUNT(*) FROM @s),@usuario,@ip);
        DECLARE @lote_id INT=CONVERT(INT,SCOPE_IDENTITY());
        INSERT dbo.th_movimientos_personal(empleado_id,unidad_origen_id,puesto_origen_id,unidad_destino_id,puesto_destino_id,fecha_movimiento,motivo,usuario_crea,direccion_ip,lote_id)
        SELECT empleado_id,unidad_id,puesto_id,@unidad_destino_id,puesto_id,@fecha_movimiento,LTRIM(RTRIM(@motivo)),@usuario,@ip,@lote_id FROM @s;
        UPDATE h SET fecha_hasta=CASE WHEN h.fecha_desde<@fecha_movimiento THEN DATEADD(DAY,-1,@fecha_movimiento) ELSE @fecha_movimiento END
        FROM dbo.th_historial_laboral h JOIN @s s ON s.empleado_id=h.empleado_id WHERE h.fecha_hasta IS NULL;
        INSERT dbo.th_historial_laboral(empleado_id,puesto_id,unidad_id,fecha_desde,observaciones,usuario_crea,fecha_creacion,movimiento_id,
            tipo_contrato,sueldo_rmu,proceso_institucional,nivel_gestion,lugar_trabajo,grupo_ocupacional,grado_laboral,partida_individual,jornada,horas_jornada,condicion_especial)
        SELECT e.empleado_id,e.puesto_id,@unidad_destino_id,@fecha_movimiento,CONCAT('Movimiento grupal de area #',@lote_id,'. ',LTRIM(RTRIM(@motivo))),@usuario,SYSDATETIME(),m.movimiento_id,
            e.tipo_contrato,e.sueldo_rmu,e.proceso_institucional,e.nivel_gestion,e.lugar_trabajo,e.grupo_ocupacional,e.grado_laboral,e.partida_individual,e.jornada,e.horas_jornada,e.condicion_especial
        FROM dbo.th_empleados e JOIN @s s ON s.empleado_id=e.empleado_id JOIN dbo.th_movimientos_personal m ON m.lote_id=@lote_id AND m.empleado_id=e.empleado_id;
        UPDATE e SET unidad_id=@unidad_destino_id FROM dbo.th_empleados e JOIN @s s ON s.empleado_id=e.empleado_id;
        DECLARE @cantidad INT=(SELECT COUNT(*) FROM @s);
        DECLARE @auditoria_lote NVARCHAR(500)=CONCAT('Lote #',@lote_id,'; ',@cantidad,' empleados; cargos conservados.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Movimiento de Personal','MOVER_LOTE_AREA',@auditoria_lote,@ip;
        COMMIT;SELECT 1 exito,@lote_id lote_id,@cantidad cantidad,'Movimiento grupal aplicado; cargos conservados.' mensaje;
    END TRY BEGIN CATCH IF @@TRANCOUNT>0 ROLLBACK;SELECT 0 exito,0 lote_id,0 cantidad,ERROR_MESSAGE() mensaje;END CATCH
END;
GO

CREATE OR ALTER VIEW dbo.vw_th_reporte_historial_jerarquico
AS
SELECT h.historial_id,h.empleado_id,e.identificacion cedula,LTRIM(RTRIM(CONCAT(e.apellidos,' ',e.nombres))) funcionario,
    h.unidad_id,h.puesto_id,u.tipo_proceso,COALESCE(padre.nombre_unidad,u.nombre_unidad) direccion_padre,
    CASE WHEN u.unidad_padre_id IS NOT NULL THEN u.nombre_unidad END sub_area,u.nombre_unidad departamento_historico,
    COALESCE(padre_actual.nombre_unidad,unidad_actual.nombre_unidad) direccion_actual_unificada,p.nombre_puesto,
    h.fecha_desde,h.fecha_hasta,DATEDIFF(YEAR,h.fecha_desde,COALESCE(h.fecha_hasta,CONVERT(date,SYSDATETIME()))) anios_permanencia,
    h.observaciones,h.tipo_contrato,h.sueldo_rmu,h.proceso_institucional,h.nivel_gestion,h.lugar_trabajo,h.grupo_ocupacional,
    h.grado_laboral,h.partida_individual,h.jornada,h.horas_jornada,h.condicion_especial,h.accion_id,h.movimiento_id,h.usuario_crea,h.fecha_creacion
FROM dbo.th_historial_laboral h JOIN dbo.th_empleados e ON e.empleado_id=h.empleado_id
JOIN dbo.th_puestos p ON p.puesto_id=h.puesto_id JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=h.unidad_id
LEFT JOIN dbo.th_unidades_organizacionales padre ON padre.unidad_id=u.unidad_padre_id
LEFT JOIN dbo.th_unidades_organizacionales unidad_actual ON unidad_actual.unidad_id=e.unidad_id
LEFT JOIN dbo.th_unidades_organizacionales padre_actual ON padre_actual.unidad_id=unidad_actual.unidad_padre_id;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_consultar_jornadas_especiales
    @empleado_id INT=NULL,@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario,'Historial Laboral','CONSULTAR_JORNADAS','Consulta de jornadas especiales temporales.',@ip;
    SELECT j.*,a.numero_accion,a.tipo_accion
    FROM dbo.th_jornadas_especiales j JOIN dbo.th_acciones_personal a ON a.accion_id=j.accion_id
    WHERE @empleado_id IS NULL OR j.empleado_id=@empleado_id
    ORDER BY j.empleado_id,j.fecha_desde DESC;
END;
GO

CREATE OR ALTER VIEW dbo.vw_th_directorio_empleados
AS
SELECT e.empleado_id id,e.empleado_id,ROW_NUMBER() OVER(ORDER BY e.apellidos,e.nombres,e.empleado_id) numero_registro,
    e.tipo_identificacion,e.identificacion cedula,e.apellidos,e.nombres,LTRIM(RTRIM(CONCAT(e.apellidos,' ',e.nombres))) apellidos_nombres,
    e.unidad_id,e.puesto_id,ISNULL(p.nombre_puesto,'') cargo,ISNULL(u.nombre_unidad,'') direccion_area,e.correo_institucional,e.correo_personal,
    e.estado,e.estado_fecha_efectiva,e.estado_motivo,e.estado_origen,e.estado_accion_id,ISNULL(e.cargas_familiares,0) cargas_familiares,
    e.tipo_cuenta_bancaria,e.numero_cuenta_bancaria,e.institucion_bancaria,e.tipo_contrato,e.sueldo_rmu remuneracion_mensual,e.sueldo_rmu,
    e.fecha_ingreso,e.fecha_salida,e.fecha_nacimiento,e.sexo,e.estado_civil,e.nacionalidad,e.tipo_sangre,e.telefono_movil,e.telefono_convencional,
    e.ciudad_residencia,e.direccion_domiciliaria,e.contacto_emergencia,e.emergencia_relacion,e.tel_emergencia,e.nivel_estudio,e.titulo,
    e.jornada,e.horas_jornada,e.condicion_especial,e.tipo_discapacidad,e.porcentaje_discapacidad,e.cuenta_bancaria,e.codigo_iess,e.cod_emplea,
    e.num_iess,e.ruta_foto,e.observaciones,e.proceso_institucional,e.nivel_gestion,e.lugar_trabajo,e.grupo_ocupacional,e.grado_laboral,e.partida_individual
FROM dbo.th_empleados e LEFT JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id;
GO
CREATE OR ALTER VIEW dbo.view_th_iddatosempledo AS SELECT * FROM dbo.vw_th_directorio_empleados;
GO

IF DATABASE_PRINCIPAL_ID('portal_app_role') IS NOT NULL
BEGIN
    GRANT EXECUTE ON dbo.sp_th_guardar_empleado_v2 TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_modificar_empleado_v2 TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_registrar_accion_personal_v2 TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_aprobar_accion_personal_v2 TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_consultar_jornadas_especiales TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_guardar_borrador TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_obtener_borrador TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_eliminar_borrador TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_mover_empleado TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_mover_empleados_lote TO portal_app_role;
END;
GO

IF OBJECT_ID('dbo.th_schema_migrations','U') IS NOT NULL AND NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.20')
    INSERT dbo.th_schema_migrations(version,nombre_archivo) VALUES('2026.08.20','migracion_gestion_laboral_20260820.sql');
GO
EXEC dbo.sp_th_registrar_auditoria 'MIGRACION','Sistema','MIGRACION_GESTION_LABORAL','Expediente laboral, borradores, jornadas especiales y movimiento solo de area.','127.0.0.1';
GO
PRINT 'Migracion 2026.08.20 completada.';
GO
