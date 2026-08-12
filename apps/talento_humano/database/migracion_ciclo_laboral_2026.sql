/* ============================================================================
   PORTAL PORTUARIO APM - CICLO LABORAL CONSISTENTE
   - Sincroniza estado, fecha de salida e historial laboral.
   - Aplica cesaciones/reingresos al aprobar la Accion de Personal.
   - Conserva auditoria y una copia lógica previa de los estados.
   Ejecutar despues de migracion_culminacion_critica_2026.sql.
   ============================================================================ */
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF OBJECT_ID('dbo.th_respaldo_estado_empleados_2026','U') IS NULL
BEGIN
    SELECT empleado_id,estado,fecha_ingreso,fecha_salida,unidad_id,puesto_id,
           SYSDATETIME() AS fecha_respaldo
    INTO dbo.th_respaldo_estado_empleados_2026
    FROM dbo.th_empleados;
END;
GO

IF COL_LENGTH('dbo.th_empleados','estado_fecha_efectiva') IS NULL
    ALTER TABLE dbo.th_empleados ADD estado_fecha_efectiva DATE NULL;
IF COL_LENGTH('dbo.th_empleados','estado_motivo') IS NULL
    ALTER TABLE dbo.th_empleados ADD estado_motivo NVARCHAR(500) NULL;
IF COL_LENGTH('dbo.th_empleados','estado_origen') IS NULL
    ALTER TABLE dbo.th_empleados ADD estado_origen VARCHAR(40) NULL;
IF COL_LENGTH('dbo.th_empleados','estado_accion_id') IS NULL
    ALTER TABLE dbo.th_empleados ADD estado_accion_id INT NULL;
GO

UPDATE dbo.th_empleados
SET estado_fecha_efectiva = CASE WHEN estado=1 THEN COALESCE(fecha_ingreso,CONVERT(date,fecha_creacion),CONVERT(date,GETDATE()))
                                 ELSE COALESCE(fecha_salida,CONVERT(date,fecha_creacion),CONVERT(date,GETDATE())) END,
    estado_motivo = COALESCE(NULLIF(estado_motivo,''),CASE WHEN estado=1 THEN N'Registro laboral vigente.' ELSE N'Estado histórico importado o baja previa.' END),
    estado_origen = COALESCE(NULLIF(estado_origen,''),'MIGRACION')
WHERE estado_fecha_efectiva IS NULL OR estado_motivo IS NULL OR estado_origen IS NULL;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_cambiar_estado_empleado
    @empleado_id INT,
    @estado BIT,
    @fecha_efectiva DATE,
    @motivo NVARCHAR(500),
    @origen VARCHAR(40),
    @accion_id INT = NULL,
    @usuario VARCHAR(50) = 'SISTEMA',
    @ip VARCHAR(45) = '0.0.0.0',
    @gestionar_historial BIT = 1,
    @emitir_resultado BIT = 1
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF @empleado_id<=0 OR @fecha_efectiva IS NULL
            THROW 51500,'Empleado y fecha efectiva son obligatorios.',1;
        IF NULLIF(LTRIM(RTRIM(@motivo)),'') IS NULL
            THROW 51501,'El motivo del cambio de estado es obligatorio.',1;
        IF NULLIF(LTRIM(RTRIM(@origen)),'') IS NULL
            THROW 51502,'El origen del cambio de estado es obligatorio.',1;

        DECLARE @estado_anterior BIT,@unidad INT,@puesto INT,@nombre NVARCHAR(220);
        SELECT @estado_anterior=estado,@unidad=unidad_id,@puesto=puesto_id,
               @nombre=LTRIM(RTRIM(CONCAT(apellidos,' ',nombres)))
        FROM dbo.th_empleados WITH(UPDLOCK,HOLDLOCK) WHERE empleado_id=@empleado_id;
        IF @estado_anterior IS NULL THROW 51503,'El empleado indicado no existe.',1;

        UPDATE dbo.th_empleados
        SET estado=@estado,
            fecha_salida=CASE WHEN @estado=0 THEN @fecha_efectiva ELSE NULL END,
            estado_fecha_efectiva=@fecha_efectiva,
            estado_motivo=LTRIM(RTRIM(@motivo)),
            estado_origen=LEFT(UPPER(LTRIM(RTRIM(@origen))),40),
            estado_accion_id=@accion_id
        WHERE empleado_id=@empleado_id;

        IF @gestionar_historial=1 AND @estado=0
            UPDATE dbo.th_historial_laboral
            SET fecha_hasta=CASE WHEN fecha_desde<@fecha_efectiva THEN DATEADD(DAY,-1,@fecha_efectiva) ELSE @fecha_efectiva END
            WHERE empleado_id=@empleado_id AND fecha_hasta IS NULL;

        IF @gestionar_historial=1 AND @estado=1
           AND @unidad IS NOT NULL AND @puesto IS NOT NULL
           AND NOT EXISTS(SELECT 1 FROM dbo.th_historial_laboral WHERE empleado_id=@empleado_id AND fecha_hasta IS NULL)
            INSERT dbo.th_historial_laboral
                (empleado_id,puesto_id,unidad_id,fecha_desde,fecha_hasta,observaciones,usuario_crea,fecha_creacion)
            VALUES
                (@empleado_id,@puesto,@unidad,@fecha_efectiva,NULL,N'Reactivación del ciclo laboral.',@usuario,SYSDATETIME());

        DECLARE @detalle VARCHAR(500)=CONCAT('Empleado #',@empleado_id,' ',@nombre,
            '; estado ',@estado_anterior,'->',@estado,'; fecha=',CONVERT(VARCHAR(10),@fecha_efectiva,23),
            '; origen=',@origen,'; accion=',COALESCE(CONVERT(VARCHAR(20),@accion_id),'N/A'),'; motivo=',LEFT(@motivo,220));
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Ciclo Laboral','CAMBIAR_ESTADO',@detalle,@ip;
        COMMIT;
        IF @emitir_resultado=1 SELECT 1 exito,@estado estado,N'Estado laboral actualizado correctamente.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        IF @emitir_resultado=1 SELECT 0 exito,ERROR_MESSAGE() mensaje;
        ELSE THROW;
    END CATCH
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_eliminar_empleado
    @id INT,@usuario VARCHAR(50)='SISTEMA',@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @estado BIT;
    SELECT @estado=estado FROM dbo.th_empleados WHERE empleado_id=@id;
    IF @estado IS NULL BEGIN SELECT 0 exito,'El empleado no existe.' mensaje; RETURN; END;
    IF @estado=0 BEGIN SELECT 0 exito,'El empleado ya se encuentra inactivo.' mensaje; RETURN; END;
    DECLARE @fecha DATE=CONVERT(date,GETDATE());
    EXEC dbo.sp_th_cambiar_estado_empleado @empleado_id=@id,@estado=0,@fecha_efectiva=@fecha,
         @motivo=N'Baja lógica registrada desde el Directorio.',@origen='DIRECTORIO',@accion_id=NULL,
         @usuario=@usuario,@ip=@ip,@gestionar_historial=1,@emitir_resultado=0;
    SELECT 1 exito,'Funcionario marcado inactivo y ciclo laboral cerrado.' mensaje;
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_aprobar_accion_personal
    @accion_id INT,@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        DECLARE @empleado INT,@unidad INT,@puesto INT,@fecha DATE,@numero VARCHAR(50),
                @rmu DECIMAL(10,2),@tipo NVARCHAR(100),@tipo_norm NVARCHAR(100),@estado_actual BIT;
        SELECT @empleado=empleado_id,@unidad=COALESCE(propuesta_unidad_id,actual_unidad_id),
               @puesto=COALESCE(propuesta_puesto_id,actual_puesto_id),@fecha=fecha_rige_desde,
               @numero=numero_accion,@rmu=propuesta_remuneracion,@tipo=tipo_accion
        FROM dbo.th_acciones_personal WITH(UPDLOCK,HOLDLOCK)
        WHERE accion_id=@accion_id AND UPPER(estado_documento) IN ('BORRADOR','PENDIENTE');
        IF @empleado IS NULL THROW 51510,'La acción no existe o ya fue resuelta.',1;
        SELECT @estado_actual=estado FROM dbo.th_empleados WITH(UPDLOCK,HOLDLOCK) WHERE empleado_id=@empleado;
        SET @tipo_norm=UPPER(LTRIM(RTRIM(ISNULL(@tipo,'')))) COLLATE Modern_Spanish_CI_AI;

        DECLARE @es_cese BIT=CASE WHEN @tipo_norm IN ('CESACION DE FUNCIONES','DESTITUCION') THEN 1 ELSE 0 END;
        DECLARE @es_reingreso BIT=CASE WHEN @tipo_norm IN ('INGRESO','REINGRESO','RESTITUCION','REINTEGRO') THEN 1 ELSE 0 END;

        IF @es_cese=1
            EXEC dbo.sp_th_cambiar_estado_empleado @empleado,0,@fecha,
                 N'Cesación aplicada mediante Acción de Personal.','ACCION_PERSONAL',@accion_id,@usuario,@ip,1,0;
        ELSE IF @es_reingreso=1
            EXEC dbo.sp_th_cambiar_estado_empleado @empleado,1,@fecha,
                 N'Ingreso o reingreso aplicado mediante Acción de Personal.','ACCION_PERSONAL',@accion_id,@usuario,@ip,0,0;
        ELSE IF @estado_actual=0
            THROW 51511,'Un funcionario inactivo solo puede reactivarse mediante ingreso, reingreso, restitución o reintegro.',1;

        IF @es_cese=0 AND @unidad IS NOT NULL AND @puesto IS NOT NULL
        BEGIN
            UPDATE dbo.th_historial_laboral
            SET fecha_hasta=CASE WHEN fecha_desde<@fecha THEN DATEADD(DAY,-1,@fecha) ELSE @fecha END
            WHERE empleado_id=@empleado AND fecha_hasta IS NULL;
            INSERT dbo.th_historial_laboral
                (empleado_id,puesto_id,unidad_id,fecha_desde,fecha_hasta,observaciones,usuario_crea,fecha_creacion)
            VALUES(@empleado,@puesto,@unidad,@fecha,NULL,CONCAT('Aplicado por Acción de Personal ',@numero),@usuario,SYSDATETIME());
            UPDATE dbo.th_empleados SET unidad_id=@unidad,puesto_id=@puesto,
                sueldo_rmu=COALESCE(NULLIF(@rmu,0),sueldo_rmu) WHERE empleado_id=@empleado;
        END;

        UPDATE dbo.th_acciones_personal SET estado_documento='APROBADO',usuario_aprueba=@usuario,
            fecha_aprobacion=SYSDATETIME() WHERE accion_id=@accion_id;
        DECLARE @detalle VARCHAR(500)=CONCAT('Aprobó ',@numero,' (',@tipo,') y aplicó la situación laboral.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Accion de Personal','APROBAR',@detalle,@ip;
        COMMIT;
        SELECT 1 exito,'Acción aprobada; estado e historial laboral sincronizados.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,ERROR_MESSAGE() mensaje;
    END CATCH
END;
GO

CREATE OR ALTER VIEW dbo.vw_th_directorio_empleados
AS
SELECT
    e.empleado_id AS id,e.empleado_id,
    ROW_NUMBER() OVER(ORDER BY e.apellidos,e.nombres,e.empleado_id) AS numero_registro,
    e.tipo_identificacion,e.identificacion AS cedula,e.apellidos,e.nombres,
    LTRIM(RTRIM(CONCAT(e.apellidos,' ',e.nombres))) AS apellidos_nombres,
    e.unidad_id,e.puesto_id,ISNULL(p.nombre_puesto,'') AS cargo,ISNULL(u.nombre_unidad,'') AS direccion_area,
    e.correo_institucional,e.correo_personal,e.estado,e.estado_fecha_efectiva,e.estado_motivo,e.estado_origen,e.estado_accion_id,
    ISNULL(e.cargas_familiares,0) AS cargas_familiares,e.tipo_cuenta_bancaria,e.numero_cuenta_bancaria,e.institucion_bancaria,
    e.tipo_contrato,e.sueldo_rmu AS remuneracion_mensual,e.sueldo_rmu,e.fecha_ingreso,e.fecha_salida,e.fecha_nacimiento,
    e.sexo,e.estado_civil,e.nacionalidad,e.tipo_sangre,e.telefono_movil,e.telefono_convencional,e.ciudad_residencia,
    e.direccion_domiciliaria,e.contacto_emergencia,e.emergencia_relacion,e.tel_emergencia,e.nivel_estudio,e.titulo,
    e.jornada,e.condicion_especial,e.tipo_discapacidad,e.porcentaje_discapacidad,e.cuenta_bancaria,e.codigo_iess,
    e.cod_emplea,e.num_iess,e.ruta_foto,e.observaciones
FROM dbo.th_empleados e
LEFT JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id
LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id;
GO
CREATE OR ALTER VIEW dbo.view_th_iddatosempledo AS SELECT * FROM dbo.vw_th_directorio_empleados;
GO

IF DATABASE_PRINCIPAL_ID('portal_app_role') IS NOT NULL
BEGIN
    GRANT EXECUTE ON dbo.sp_th_cambiar_estado_empleado TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_eliminar_empleado TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_aprobar_accion_personal TO portal_app_role;
END;
GO

PRINT 'Migración de ciclo laboral completada.';
GO
