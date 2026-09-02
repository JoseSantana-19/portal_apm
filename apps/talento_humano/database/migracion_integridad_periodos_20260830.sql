/* Portal Portuario APM — Integridad de períodos de vinculación
   Versión 2026.08.30.1
   Garantiza que cada alta de funcionario cree su período inicial y reconcilia
   expedientes incorporados después de la migración operativa del 25-08-2026. */
SET NOCOUNT ON;
SET XACT_ABORT ON;
GO

IF OBJECT_ID('dbo.th_empleados','U') IS NULL
    THROW 52500,'Falta dbo.th_empleados.',1;
IF OBJECT_ID('dbo.th_periodos_vinculacion','U') IS NULL
    THROW 52501,'Falta dbo.th_periodos_vinculacion.',1;
GO

CREATE OR ALTER TRIGGER dbo.tr_th_empleados_crear_periodo_inicial
ON dbo.th_empleados
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;
    INSERT dbo.th_periodos_vinculacion
        (empleado_id,fecha_desde,fecha_hasta,tipo_ingreso,motivo_salida,usuario_crea)
    SELECT i.empleado_id,base.fecha_desde,
           CASE WHEN i.estado=0
                THEN CASE WHEN i.fecha_salida>=base.fecha_desde THEN i.fecha_salida ELSE base.fecha_desde END
           END,
           N'INGRESO INICIAL',
           CASE WHEN i.estado=0 THEN N'Período inicial creado desde el expediente.' END,
           'ALTA_EXPEDIENTE'
    FROM inserted i
    CROSS APPLY(
        SELECT COALESCE(i.fecha_ingreso,CONVERT(date,i.fecha_creacion),CONVERT(date,SYSDATETIME())) fecha_desde
    ) base
    WHERE NOT EXISTS(
        SELECT 1 FROM dbo.th_periodos_vinculacion p WHERE p.empleado_id=i.empleado_id
    );
END;
GO

/* La aplicación trabaja con privilegios mínimos: la corrección de borradores
   se concentra en un procedimiento y no requiere UPDATE directo sobre la tabla. */
CREATE OR ALTER PROCEDURE dbo.sp_th_actualizar_borrador_accion_personal
    @accion_id INT,@fecha_elaboracion DATE,@modalidad_vigencia VARCHAR(12),
    @fecha_rige_desde DATE,@fecha_rige_hasta DATE=NULL,@explicacion_legal VARCHAR(MAX),
    @detalle_otro NVARCHAR(255)=NULL,@presento_declaracion VARCHAR(20)=NULL,
    @actual_unidad_id INT=NULL,@actual_puesto_id INT=NULL,@actual_lugar_trabajo NVARCHAR(150)=NULL,
    @actual_remuneracion DECIMAL(10,2)=NULL,@actual_proceso NVARCHAR(150)=NULL,
    @actual_nivel_gestion NVARCHAR(150)=NULL,@actual_grupo_ocupacional NVARCHAR(150)=NULL,
    @actual_grado NVARCHAR(50)=NULL,@actual_partida_presupuestaria NVARCHAR(100)=NULL,
    @propuesta_unidad_id INT=NULL,@propuesta_puesto_id INT=NULL,@propuesta_lugar_trabajo NVARCHAR(150)=NULL,
    @propuesta_remuneracion DECIMAL(10,2)=NULL,@propuesta_proceso NVARCHAR(150)=NULL,
    @propuesta_nivel_gestion NVARCHAR(150)=NULL,@propuesta_grupo_ocupacional NVARCHAR(150)=NULL,
    @propuesta_grado NVARCHAR(50)=NULL,@propuesta_partida_presupuestaria NVARCHAR(100)=NULL,
    @actual_jornada NVARCHAR(30)=NULL,@actual_horas_jornada DECIMAL(4,1)=NULL,
    @propuesta_jornada NVARCHAR(30)=NULL,@propuesta_horas_jornada DECIMAL(4,1)=NULL,
    @tipo_novedad_jornada NVARCHAR(80)=NULL,@hora_entrada_propuesta TIME(0)=NULL,
    @hora_salida_propuesta TIME(0)=NULL,@dias_jornada_propuesta NVARCHAR(100)=NULL,
    @documento_jornada NVARCHAR(200)=NULL,@actual_tipo_contrato NVARCHAR(100)=NULL,
    @propuesta_tipo_contrato NVARCHAR(100)=NULL,@notificacion_electronica BIT=0,
    @correo_notificacion NVARCHAR(150)=NULL,@medio_notificacion NVARCHAR(100)=NULL,
    @documento_notificacion NVARCHAR(100)=NULL,@fecha_notificacion DATETIME2(0)=NULL,
    @responsable_th_nombre NVARCHAR(150)=NULL,@responsable_th_puesto NVARCHAR(150)=NULL,
    @autoridad_nombre NVARCHAR(150)=NULL,@autoridad_puesto NVARCHAR(150)=NULL,
    @elaborador_nombre NVARCHAR(150)=NULL,@elaborador_puesto NVARCHAR(150)=NULL,
    @revisor_nombre NVARCHAR(150)=NULL,@revisor_puesto NVARCHAR(150)=NULL,
    @registrador_nombre NVARCHAR(150)=NULL,@registrador_puesto NVARCHAR(150)=NULL,
    @notificador_nombre NVARCHAR(150)=NULL,@notificador_puesto NVARCHAR(150)=NULL,
    @usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;

        SET @modalidad_vigencia=UPPER(LTRIM(RTRIM(ISNULL(@modalidad_vigencia,''))));
        IF @modalidad_vigencia NOT IN('PERMANENTE','TEMPORAL')
            THROW 52520,'Seleccione si la vigencia es permanente o temporal.',1;
        IF @modalidad_vigencia='PERMANENTE' AND @fecha_rige_hasta IS NOT NULL
            THROW 52521,'Una acción permanente no debe tener fecha final.',1;
        IF @modalidad_vigencia='TEMPORAL' AND (@fecha_rige_hasta IS NULL OR @fecha_rige_hasta<@fecha_rige_desde)
            THROW 52522,'Una acción temporal requiere fechas válidas.',1;

        DECLARE @numero_accion VARCHAR(50);
        SELECT @numero_accion=numero_accion
        FROM dbo.th_acciones_personal WITH(UPDLOCK,HOLDLOCK)
        WHERE accion_id=@accion_id AND UPPER(estado_documento) IN('BORRADOR','PENDIENTE');
        IF @numero_accion IS NULL
            THROW 52523,'La acción no existe o ya no admite edición.',1;

        UPDATE dbo.th_acciones_personal SET
            fecha_elaboracion=@fecha_elaboracion,modalidad_vigencia=@modalidad_vigencia,
            fecha_rige_desde=@fecha_rige_desde,fecha_rige_hasta=@fecha_rige_hasta,
            explicacion_legal=@explicacion_legal,detalle_otro=NULLIF(@detalle_otro,''),
            presento_declaracion=NULLIF(@presento_declaracion,''),actual_unidad_id=NULLIF(@actual_unidad_id,0),
            actual_puesto_id=NULLIF(@actual_puesto_id,0),actual_lugar_trabajo=NULLIF(@actual_lugar_trabajo,''),
            actual_remuneracion=@actual_remuneracion,actual_proceso=NULLIF(@actual_proceso,''),
            actual_nivel_gestion=NULLIF(@actual_nivel_gestion,''),actual_grupo_ocupacional=NULLIF(@actual_grupo_ocupacional,''),
            actual_grado=NULLIF(@actual_grado,''),actual_partida_presupuestaria=NULLIF(@actual_partida_presupuestaria,''),
            propuesta_unidad_id=NULLIF(@propuesta_unidad_id,0),propuesta_puesto_id=NULLIF(@propuesta_puesto_id,0),
            propuesta_lugar_trabajo=NULLIF(@propuesta_lugar_trabajo,''),propuesta_remuneracion=NULLIF(@propuesta_remuneracion,0),
            propuesta_proceso=NULLIF(@propuesta_proceso,''),propuesta_nivel_gestion=NULLIF(@propuesta_nivel_gestion,''),
            propuesta_grupo_ocupacional=NULLIF(@propuesta_grupo_ocupacional,''),propuesta_grado=NULLIF(@propuesta_grado,''),
            propuesta_partida_presupuestaria=NULLIF(@propuesta_partida_presupuestaria,''),actual_jornada=NULLIF(@actual_jornada,''),
            actual_horas_jornada=NULLIF(@actual_horas_jornada,0),propuesta_jornada=NULLIF(@propuesta_jornada,''),
            propuesta_horas_jornada=@propuesta_horas_jornada,tipo_novedad_jornada=NULLIF(@tipo_novedad_jornada,''),
            hora_entrada_propuesta=@hora_entrada_propuesta,hora_salida_propuesta=@hora_salida_propuesta,
            dias_jornada_propuesta=NULLIF(@dias_jornada_propuesta,''),documento_jornada=NULLIF(@documento_jornada,''),
            actual_tipo_contrato=NULLIF(@actual_tipo_contrato,''),propuesta_tipo_contrato=NULLIF(@propuesta_tipo_contrato,''),
            notificacion_electronica=@notificacion_electronica,correo_notificacion=NULLIF(@correo_notificacion,''),
            medio_notificacion=NULLIF(@medio_notificacion,''),documento_notificacion=NULLIF(@documento_notificacion,''),
            fecha_notificacion=@fecha_notificacion,responsable_th_nombre=NULLIF(@responsable_th_nombre,''),
            responsable_th_puesto=NULLIF(@responsable_th_puesto,''),autoridad_nombre=NULLIF(@autoridad_nombre,''),
            autoridad_puesto=NULLIF(@autoridad_puesto,''),elaborador_nombre=NULLIF(@elaborador_nombre,''),
            elaborador_puesto=NULLIF(@elaborador_puesto,''),revisor_nombre=NULLIF(@revisor_nombre,''),
            revisor_puesto=NULLIF(@revisor_puesto,''),registrador_nombre=NULLIF(@registrador_nombre,''),
            registrador_puesto=NULLIF(@registrador_puesto,''),notificador_nombre=NULLIF(@notificador_nombre,''),
            notificador_puesto=NULLIF(@notificador_puesto,'')
        WHERE accion_id=@accion_id AND UPPER(estado_documento) IN('BORRADOR','PENDIENTE');
        IF @@ROWCOUNT<>1
            THROW 52524,'No fue posible actualizar el borrador.',1;

        DECLARE @detalle_auditoria NVARCHAR(500)=CONCAT('Corrigió el borrador ',@numero_accion,' antes de aprobación.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Accion de Personal','EDITAR_BORRADOR',@detalle_auditoria,@ip;
        COMMIT;
        SELECT 1 exito,@numero_accion numero_accion,'Borrador actualizado y auditado.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,CAST(NULL AS VARCHAR(50)) numero_accion,ERROR_MESSAGE() mensaje;
    END CATCH
END;
GO

IF DATABASE_PRINCIPAL_ID('portal_app_role') IS NOT NULL
    GRANT EXECUTE ON dbo.sp_th_actualizar_borrador_accion_personal TO portal_app_role;
GO

BEGIN TRY
    BEGIN TRAN;

    INSERT dbo.th_periodos_vinculacion
        (empleado_id,fecha_desde,fecha_hasta,tipo_ingreso,motivo_salida,usuario_crea)
    SELECT e.empleado_id,base.fecha_desde,
           CASE WHEN e.estado=0
                THEN CASE WHEN e.fecha_salida>=base.fecha_desde THEN e.fecha_salida ELSE base.fecha_desde END
           END,
           N'INGRESO INICIAL',
           CASE WHEN e.estado=0
                THEN N'Período histórico conciliado por cierre controlado.'
                ELSE N'Período inicial conciliado por cierre controlado.'
           END,
           'MIGRACION'
    FROM dbo.th_empleados e
    CROSS APPLY(
        SELECT COALESCE(e.fecha_ingreso,CONVERT(date,e.fecha_creacion),CONVERT(date,SYSDATETIME())) fecha_desde
    ) base
    WHERE NOT EXISTS(
        SELECT 1 FROM dbo.th_periodos_vinculacion p WHERE p.empleado_id=e.empleado_id
    );

    IF EXISTS(
        SELECT 1 FROM dbo.th_empleados e
        WHERE NOT EXISTS(SELECT 1 FROM dbo.th_periodos_vinculacion p WHERE p.empleado_id=e.empleado_id)
    ) THROW 52502,'La conciliación dejó funcionarios sin período de vinculación.',1;

    IF OBJECT_ID('dbo.th_schema_migrations','U') IS NOT NULL
       AND NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.30.1')
        INSERT dbo.th_schema_migrations(version,nombre_archivo)
        VALUES('2026.08.30.1','migracion_integridad_periodos_20260830.sql');

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT>0 ROLLBACK;
    THROW;
END CATCH;
GO
