USE [Talento_Humano];
GO
SET NOCOUNT ON;
SET XACT_ABORT ON;

IF (SELECT COUNT_BIG(*) FROM dbo.th_empleados)<>620 THROW 52000,'Cantidad inesperada de empleados.',1;
IF EXISTS(SELECT 1 FROM dbo.th_empleados WHERE unidad_id IS NULL OR puesto_id IS NULL) THROW 52001,'Persisten empleados sin asignacion.',1;
IF NOT EXISTS(SELECT 1 FROM dbo.th_permisos_rol) THROW 52002,'La matriz RBAC esta vacia.',1;
IF OBJECT_ID('dbo.tr_th_logs_auditoria_append_only','TR') IS NULL THROW 52003,'Falta trigger append-only.',1;

BEGIN TRAN;
DECLARE @empleado INT=1,@unidad INT,@puesto INT,@accion INT;
SELECT @unidad=unidad_id,@puesto=puesto_id FROM dbo.th_empleados WHERE empleado_id=@empleado;
INSERT dbo.th_acciones_personal(numero_accion,fecha_elaboracion,empleado_id,tipo_accion,fecha_rige_desde,explicacion_legal,
    actual_unidad_id,actual_puesto_id,propuesta_unidad_id,propuesta_puesto_id,estado_documento,usuario_crea,fecha_creacion)
VALUES('TEST-SMOKE-ROLLBACK',CONVERT(date,GETDATE()),@empleado,'TRASPASO',CONVERT(date,GETDATE()),'Prueba transaccional',
    @unidad,@puesto,@unidad,@puesto,'APROBADO','QA',GETDATE());
SET @accion=CONVERT(INT,SCOPE_IDENTITY());
IF (SELECT estado_documento FROM dbo.th_acciones_personal WHERE accion_id=@accion)<>'BORRADOR' THROW 52004,'La accion no nacio como borrador.',1;
EXEC dbo.sp_th_aprobar_accion_personal @accion,'QA','127.0.0.1';
IF (SELECT estado_documento FROM dbo.th_acciones_personal WHERE accion_id=@accion)<>'APROBADO' THROW 52005,'La accion no fue aprobada.',1;
IF NOT EXISTS(SELECT 1 FROM dbo.th_historial_laboral WHERE empleado_id=@empleado AND observaciones LIKE '%TEST-SMOKE-ROLLBACK%') THROW 52006,'La aprobacion no actualizo el historial.',1;
ROLLBACK;

BEGIN TRAN;
EXEC dbo.sp_th_registrar_accion_personal
    @numero_accion='VALOR-NO-CONFIABLE',@empleado_id=1,@tipo_accion='TRASLADO',
    @fecha_rige_desde='2026-07-29',@explicacion_legal='Prueba de correlativo transaccional',
    @usuario='QA_SECUENCIA',@ip='127.0.0.1';
EXEC dbo.sp_th_registrar_accion_personal
    @numero_accion='VALOR-NO-CONFIABLE',@empleado_id=1,@tipo_accion='TRASLADO',
    @fecha_rige_desde='2026-07-29',@explicacion_legal='Prueba de correlativo transaccional',
    @usuario='QA_SECUENCIA',@ip='127.0.0.1';
IF (SELECT COUNT(*) FROM dbo.th_acciones_personal WHERE usuario_crea='QA_SECUENCIA')<>2
    THROW 52008,'No se registraron las acciones de prueba.',1;
IF (SELECT COUNT(DISTINCT numero_accion) FROM dbo.th_acciones_personal WHERE usuario_crea='QA_SECUENCIA')<>2
    THROW 52009,'El correlativo permitio numeros duplicados.',1;
IF EXISTS(SELECT 1 FROM dbo.th_acciones_personal WHERE usuario_crea='QA_SECUENCIA' AND numero_accion='VALOR-NO-CONFIABLE')
    THROW 52010,'El procedimiento confio en el correlativo enviado por el navegador.',1;
ROLLBACK;

BEGIN TRY
    UPDATE dbo.th_logs_auditoria SET accion='ALTERADO' WHERE log_id=(SELECT MIN(log_id) FROM dbo.th_logs_auditoria);
    THROW 52007,'La bitacora permitio una actualizacion.',1;
END TRY
BEGIN CATCH
    IF ERROR_NUMBER()=52007 THROW;
END CATCH;

SELECT 'SQL_SMOKE_OK' resultado;
GO
