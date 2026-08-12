USE [Talento_Humano];
GO
SET NOCOUNT ON;
SET XACT_ABORT ON;

IF (SELECT COUNT(*) FROM dbo.th_nacionalidades WHERE activo=1)<200 THROW 52500,'El catalogo de nacionalidades esta incompleto.',1;
IF OBJECT_ID('dbo.sp_th_obtener_expediente_impresion','P') IS NULL THROW 52501,'Falta consulta de impresion completa.',1;
IF OBJECT_ID('dbo.sp_th_buscar_personal','P') IS NULL THROW 52502,'Falta busqueda compuesta.',1;
IF OBJECT_ID('dbo.sp_th_mover_empleados_lote','P') IS NULL THROW 52503,'Falta movimiento grupal.',1;
IF OBJECT_ID('dbo.sp_th_cambiar_estado_empleado','P') IS NULL THROW 52508,'Falta el ciclo laboral centralizado.',1;
IF COL_LENGTH('dbo.th_empleados','estado_fecha_efectiva') IS NULL THROW 52509,'Falta la fecha efectiva del estado laboral.',1;
IF COL_LENGTH('dbo.th_empleados','estado_motivo') IS NULL THROW 52510,'Falta el motivo del estado laboral.',1;

DECLARE @busqueda TABLE(empleado_id INT,total_resultados BIGINT);
INSERT @busqueda EXEC dbo.sp_th_buscar_personal @termino=N'michael palma',@usuario='QA',@ip='127.0.0.1';
IF NOT EXISTS(SELECT 1 FROM @busqueda WHERE empleado_id=(SELECT empleado_id FROM dbo.th_empleados WHERE identificacion='1316312766'))
    THROW 52504,'La busqueda compuesta no encontro nombre y apellido.',1;
DELETE FROM @busqueda;
INSERT @busqueda EXEC dbo.sp_th_buscar_personal @termino=N'131631',@usuario='QA',@ip='127.0.0.1';
IF NOT EXISTS(SELECT 1 FROM @busqueda WHERE empleado_id=(SELECT empleado_id FROM dbo.th_empleados WHERE identificacion='1316312766'))
    THROW 52505,'La busqueda parcial de cedula fallo.',1;

BEGIN TRAN;
DECLARE @ids TABLE(id INT PRIMARY KEY,unidad INT,puesto INT);
INSERT @ids SELECT TOP 2 empleado_id,unidad_id,puesto_id FROM dbo.th_empleados WHERE estado=1 AND unidad_id IS NOT NULL AND puesto_id IS NOT NULL ORDER BY empleado_id;
DECLARE @unidadDestino INT=(SELECT TOP 1 unidad_id FROM dbo.th_unidades_organizacionales u WHERE activo=1 AND NOT EXISTS(SELECT 1 FROM @ids i WHERE i.unidad=u.unidad_id) ORDER BY unidad_id);
DECLARE @puestoDestino INT=(SELECT TOP 1 puesto_id FROM dbo.th_puestos p WHERE activo=1 AND NOT EXISTS(SELECT 1 FROM @ids i WHERE i.puesto=p.puesto_id) ORDER BY puesto_id);
DECLARE @json NVARCHAR(MAX)=(SELECT '['+STRING_AGG(CONVERT(VARCHAR(20),id),',')+']' FROM @ids);
DECLARE @fecha DATE=CONVERT(date,GETDATE());
EXEC dbo.sp_th_mover_empleados_lote @json,@unidadDestino,@puestoDestino,@fecha,'Prueba grupal con rollback','QA','127.0.0.1';
DECLARE @lote INT=(SELECT MAX(lote_id) FROM dbo.th_movimientos_lote WHERE usuario_crea='QA');
IF @lote IS NULL OR (SELECT COUNT(*) FROM dbo.th_movimientos_personal WHERE lote_id=@lote)<>2 THROW 52506,'El lote no genero dos movimientos.',1;
IF EXISTS(SELECT 1 FROM @ids i JOIN dbo.th_empleados e ON e.empleado_id=i.id WHERE e.unidad_id<>@unidadDestino OR e.puesto_id<>@puestoDestino) THROW 52507,'El lote no actualizo todos los empleados.',1;
ROLLBACK;

DECLARE @empleadoEstado INT=(SELECT TOP 1 empleado_id FROM dbo.th_empleados WHERE estado=1 ORDER BY empleado_id);
IF @empleadoEstado IS NULL THROW 52511,'No existe un empleado activo para probar el ciclo laboral.',1;
BEGIN TRAN;
DECLARE @fechaEstado DATE=CONVERT(date,GETDATE());
EXEC dbo.sp_th_cambiar_estado_empleado
    @empleado_id=@empleadoEstado,
    @estado=0,
    @fecha_efectiva=@fechaEstado,
    @motivo=N'Prueba de baja con rollback',
    @origen=N'QA',
    @accion_id=NULL,
    @gestionar_historial=1,
    @usuario=N'QA',
    @ip=N'127.0.0.1',
    @emitir_resultado=0;
IF NOT EXISTS(
    SELECT 1 FROM dbo.th_empleados
    WHERE empleado_id=@empleadoEstado AND estado=0
      AND estado_fecha_efectiva=@fechaEstado
      AND estado_motivo=N'Prueba de baja con rollback'
) THROW 52512,'El ciclo laboral no sincronizo estado, fecha y motivo.',1;
ROLLBACK;
IF NOT EXISTS(SELECT 1 FROM dbo.th_empleados WHERE empleado_id=@empleadoEstado AND estado=1)
    THROW 52513,'La prueba del ciclo laboral no restauro el estado.',1;

SELECT 'SQL_MEJORAS_OPERATIVAS_OK' resultado;
GO
