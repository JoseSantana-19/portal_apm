/* Portal Portuario APM — régimen laboral y formulario abreviado
   Versión 2026.08.29.1
   - Distingue LOSEP de Código del Trabajo en el expediente.
   - Parametriza la serie CdgT y la asigna con bloqueo transaccional.
   - Conserva el régimen como instantánea en cada documento.
   Idempotente. No renumera documentos históricos. */
USE [Talento_Humano];
GO
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

IF COL_LENGTH('dbo.th_empleados','regimen_laboral') IS NULL
    ALTER TABLE dbo.th_empleados ADD regimen_laboral VARCHAR(20) NULL;
GO
UPDATE dbo.th_empleados
SET regimen_laboral=CASE WHEN UPPER(ISNULL(tipo_contrato,'')) COLLATE Modern_Spanish_CI_AI LIKE '%CODIGO%TRABAJO%'
                         THEN 'CODIGO_TRABAJO' ELSE 'LOSEP' END
WHERE regimen_laboral IS NULL OR LTRIM(RTRIM(regimen_laboral))='';
UPDATE dbo.th_empleados SET tipo_contrato=N'Contrato Indefinido'
WHERE regimen_laboral='CODIGO_TRABAJO' AND ISNULL(tipo_contrato,'')<>N'Contrato Indefinido';
ALTER TABLE dbo.th_empleados ALTER COLUMN regimen_laboral VARCHAR(20) NOT NULL;
GO
IF OBJECT_ID('dbo.DF_th_empleados_regimen_laboral','D') IS NULL
    ALTER TABLE dbo.th_empleados ADD CONSTRAINT DF_th_empleados_regimen_laboral DEFAULT('LOSEP') FOR regimen_laboral;
IF OBJECT_ID('dbo.CK_th_empleados_regimen_laboral','C') IS NULL
    ALTER TABLE dbo.th_empleados ADD CONSTRAINT CK_th_empleados_regimen_laboral CHECK(regimen_laboral IN('LOSEP','CODIGO_TRABAJO'));
GO

IF COL_LENGTH('dbo.th_historial_laboral','regimen_laboral') IS NULL
    ALTER TABLE dbo.th_historial_laboral ADD regimen_laboral VARCHAR(20) NULL;
GO
UPDATE h SET regimen_laboral=e.regimen_laboral
FROM dbo.th_historial_laboral h JOIN dbo.th_empleados e ON e.empleado_id=h.empleado_id
WHERE h.regimen_laboral IS NULL;
GO

IF COL_LENGTH('dbo.th_acciones_personal','regimen_laboral') IS NULL
    ALTER TABLE dbo.th_acciones_personal ADD regimen_laboral VARCHAR(20) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','tipo_documento') IS NULL
    ALTER TABLE dbo.th_acciones_personal ADD tipo_documento VARCHAR(30) NULL;
GO
UPDATE a SET regimen_laboral=e.regimen_laboral,
             tipo_documento=CASE WHEN e.regimen_laboral='CODIGO_TRABAJO' THEN 'FORMULARIO_ABREVIADO' ELSE 'ACCION_PERSONAL_LOSEP' END
FROM dbo.th_acciones_personal a JOIN dbo.th_empleados e ON e.empleado_id=a.empleado_id
WHERE a.regimen_laboral IS NULL OR a.tipo_documento IS NULL;
GO

IF OBJECT_ID('dbo.th_secuencias_documentos','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_secuencias_documentos(
        secuencia_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_secuencias_documentos PRIMARY KEY,
        regimen_laboral VARCHAR(20) NOT NULL,
        tipo_documento VARCHAR(30) NOT NULL,
        prefijo VARCHAR(12) NOT NULL,
        anio SMALLINT NOT NULL,
        contador_actual INT NOT NULL CONSTRAINT DF_th_secuencias_documentos_contador DEFAULT(0),
        activo BIT NOT NULL CONSTRAINT DF_th_secuencias_documentos_activo DEFAULT(1),
        fecha_actualizacion DATETIME2(0) NOT NULL CONSTRAINT DF_th_secuencias_documentos_fecha DEFAULT(SYSDATETIME()),
        CONSTRAINT UQ_th_secuencias_documentos UNIQUE(regimen_laboral,tipo_documento,anio),
        CONSTRAINT CK_th_secuencias_documentos_anio CHECK(anio BETWEEN 2000 AND 9999),
        CONSTRAINT CK_th_secuencias_documentos_contador CHECK(contador_actual>=0)
    );
END;
GO
DECLARE @anio_actual SMALLINT=YEAR(dbo.fn_th_fecha_institucional());
IF NOT EXISTS(SELECT 1 FROM dbo.th_secuencias_documentos WHERE regimen_laboral='CODIGO_TRABAJO' AND tipo_documento='FORMULARIO_ABREVIADO' AND anio=@anio_actual)
    INSERT dbo.th_secuencias_documentos(regimen_laboral,tipo_documento,prefijo,anio,contador_actual)
    VALUES('CODIGO_TRABAJO','FORMULARIO_ABREVIADO','CdgT',@anio_actual,0);
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_asignar_regimen_empleado
    @empleado_id INT,@regimen_laboral VARCHAR(20),@tipo_contrato NVARCHAR(100),@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;SET XACT_ABORT ON;
    BEGIN TRY
        SET @regimen_laboral=UPPER(LTRIM(RTRIM(@regimen_laboral)));
        SET @tipo_contrato=LTRIM(RTRIM(@tipo_contrato));
        IF @regimen_laboral NOT IN('LOSEP','CODIGO_TRABAJO') THROW 52300,'El régimen laboral no es válido.',1;
        IF @regimen_laboral='LOSEP' AND @tipo_contrato NOT IN(N'Nombramiento Permanente',N'Nombramiento Provisional',N'Contrato Ocasional')
            THROW 52301,'El contrato no corresponde al régimen LOSEP.',1;
        IF @regimen_laboral='CODIGO_TRABAJO' AND @tipo_contrato<>N'Contrato Indefinido'
            THROW 52302,'Código del Trabajo requiere Contrato Indefinido.',1;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_empleados WITH(UPDLOCK,HOLDLOCK) WHERE empleado_id=@empleado_id)
            THROW 52303,'El funcionario no existe.',1;

        UPDATE dbo.th_empleados SET regimen_laboral=@regimen_laboral,tipo_contrato=@tipo_contrato WHERE empleado_id=@empleado_id;
        UPDATE dbo.th_historial_laboral SET regimen_laboral=@regimen_laboral,tipo_contrato=@tipo_contrato
        WHERE empleado_id=@empleado_id AND fecha_hasta IS NULL;
        DECLARE @detalle NVARCHAR(500)=CONCAT('Asignó régimen ',@regimen_laboral,' y vínculo ',@tipo_contrato,' al funcionario #',@empleado_id,'.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Expediente Laboral','ASIGNAR_REGIMEN',@detalle,@ip;
        SELECT 1 exito,'Régimen laboral sincronizado.' mensaje;
    END TRY
    BEGIN CATCH
        SELECT 0 exito,ERROR_MESSAGE() mensaje;
    END CATCH
END;
GO

CREATE OR ALTER VIEW dbo.vw_th_situacion_laboral_efectiva
AS
SELECT e.empleado_id,e.regimen_laboral,
    COALESCE(v.propuesta_unidad_id,e.unidad_id) unidad_id,COALESCE(v.propuesta_puesto_id,e.puesto_id) puesto_id,
    COALESCE(v.propuesta_remuneracion,e.sueldo_rmu) sueldo_rmu,COALESCE(v.propuesta_proceso,e.proceso_institucional) proceso_institucional,
    COALESCE(v.propuesta_nivel_gestion,e.nivel_gestion) nivel_gestion,COALESCE(v.propuesta_lugar_trabajo,e.lugar_trabajo) lugar_trabajo,
    COALESCE(v.propuesta_grupo_ocupacional,e.grupo_ocupacional) grupo_ocupacional,COALESCE(v.propuesta_grado,e.grado_laboral) grado_laboral,
    COALESCE(v.propuesta_partida,e.partida_individual) partida_individual,COALESCE(j.jornada_temporal,v.propuesta_jornada,e.jornada) jornada,
    COALESCE(j.horas_diarias,v.propuesta_horas_jornada,e.horas_jornada) horas_jornada,COALESCE(v.propuesta_tipo_contrato,e.tipo_contrato) tipo_contrato,
    v.vigencia_id,v.tipo_vigencia,v.fecha_desde vigencia_desde,v.fecha_hasta vigencia_hasta,
    j.jornada_especial_id,j.tipo_novedad,j.fecha_desde jornada_desde,j.fecha_hasta jornada_hasta,
    CONVERT(bit,IIF(v.vigencia_id IS NOT NULL OR j.jornada_especial_id IS NOT NULL,1,0)) situacion_temporal
FROM dbo.th_empleados e
OUTER APPLY(SELECT TOP(1) x.* FROM dbo.th_vigencias_laborales x WHERE x.empleado_id=e.empleado_id
    AND ((x.tipo_vigencia='TEMPORAL' AND x.estado IN('PROGRAMADA','VIGENTE') AND dbo.fn_th_fecha_institucional() BETWEEN x.fecha_desde AND x.fecha_hasta)
      OR (x.tipo_vigencia='PERMANENTE' AND x.estado='PROGRAMADA' AND dbo.fn_th_fecha_institucional()>=x.fecha_desde))
    ORDER BY x.fecha_desde DESC,x.vigencia_id DESC)v
OUTER APPLY(SELECT TOP(1) x.* FROM dbo.th_jornadas_especiales x WHERE x.empleado_id=e.empleado_id AND x.estado IN('PROGRAMADA','VIGENTE')
    AND dbo.fn_th_fecha_institucional() BETWEEN x.fecha_desde AND x.fecha_hasta ORDER BY x.fecha_desde DESC,x.jornada_especial_id DESC)j;
GO

CREATE OR ALTER VIEW dbo.vw_th_directorio_empleados
AS
SELECT e.empleado_id id,e.empleado_id,ROW_NUMBER() OVER(ORDER BY e.apellidos,e.nombres,e.empleado_id) numero_registro,
    e.tipo_identificacion,e.identificacion cedula,e.apellidos,e.nombres,LTRIM(RTRIM(CONCAT(e.apellidos,' ',e.nombres))) apellidos_nombres,
    s.unidad_id,s.puesto_id,ISNULL(p.nombre_puesto,'') cargo,ISNULL(u.nombre_unidad,'') direccion_area,e.correo_institucional,e.correo_personal,
    e.estado,e.estado_fecha_efectiva,e.estado_motivo,e.estado_origen,e.estado_accion_id,ISNULL(e.cargas_familiares,0) cargas_familiares,
    e.tipo_cuenta_bancaria,e.numero_cuenta_bancaria,e.institucion_bancaria,s.regimen_laboral,s.tipo_contrato,s.sueldo_rmu remuneracion_mensual,s.sueldo_rmu,
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

/* El régimen del expediente es autoritativo. El trigger asigna el tipo de
   documento y su correlativo dentro de la misma transacción del INSERT. */
CREATE OR ALTER TRIGGER dbo.tr_th_acciones_asignar_serie
ON dbo.th_acciones_personal
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @accion_id INT,@empleado_id INT,@tipo NVARCHAR(100),@fecha DATE,@regimen VARCHAR(20),@tipo_documento VARCHAR(30),
            @serie VARCHAR(12),@anio SMALLINT,@numero INT,@lock INT,@recurso NVARCHAR(255);
    DECLARE acciones CURSOR LOCAL FAST_FORWARD FOR
        SELECT i.accion_id,i.empleado_id,i.tipo_accion,COALESCE(i.fecha_elaboracion,dbo.fn_th_fecha_institucional()),e.regimen_laboral
        FROM inserted i JOIN dbo.th_empleados e ON e.empleado_id=i.empleado_id ORDER BY i.accion_id;
    OPEN acciones;FETCH NEXT FROM acciones INTO @accion_id,@empleado_id,@tipo,@fecha,@regimen;
    WHILE @@FETCH_STATUS=0
    BEGIN
        SET @anio=YEAR(@fecha);SET @tipo_documento=CASE WHEN @regimen='CODIGO_TRABAJO' THEN 'FORMULARIO_ABREVIADO' ELSE 'ACCION_PERSONAL_LOSEP' END;
        IF @regimen='CODIGO_TRABAJO'
        BEGIN
            SELECT TOP(1) @serie=prefijo FROM dbo.th_secuencias_documentos
            WHERE regimen_laboral='CODIGO_TRABAJO' AND tipo_documento='FORMULARIO_ABREVIADO' AND activo=1 ORDER BY IIF(anio=@anio,0,1),anio DESC;
            SET @serie=COALESCE(NULLIF(@serie,''),'CdgT');SET @recurso=CONCAT('th_documento_CdgT_',@anio);
            EXEC @lock=sys.sp_getapplock @Resource=@recurso,@LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=10000;
            IF @lock<0 THROW 52310,'No fue posible reservar la secuencia CdgT.',1;
            UPDATE dbo.th_secuencias_documentos WITH(UPDLOCK,HOLDLOCK)
               SET contador_actual=contador_actual+1,fecha_actualizacion=SYSDATETIME(),@numero=contador_actual+1
             WHERE regimen_laboral='CODIGO_TRABAJO' AND tipo_documento='FORMULARIO_ABREVIADO' AND anio=@anio;
            IF @@ROWCOUNT=0 BEGIN SET @numero=1;INSERT dbo.th_secuencias_documentos(regimen_laboral,tipo_documento,prefijo,anio,contador_actual)
                VALUES('CODIGO_TRABAJO','FORMULARIO_ABREVIADO',@serie,@anio,@numero);END;
        END
        ELSE
        BEGIN
            SET @serie=dbo.fn_th_serie_accion(@tipo);SET @recurso=CONCAT('th_serie_accion_',@serie,'_',@anio);
            EXEC @lock=sys.sp_getapplock @Resource=@recurso,@LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=10000;
            IF @lock<0 THROW 52311,'No fue posible reservar la serie documental LOSEP.',1;
            UPDATE dbo.th_contadores_series_accion WITH(UPDLOCK,HOLDLOCK)
               SET ultimo_numero=ultimo_numero+1,fecha_actualizacion=SYSDATETIME(),@numero=ultimo_numero+1 WHERE serie=@serie AND anio=@anio;
            IF @@ROWCOUNT=0 BEGIN SET @numero=1;INSERT dbo.th_contadores_series_accion(serie,anio,ultimo_numero) VALUES(@serie,@anio,@numero);END;
        END;
        UPDATE dbo.th_acciones_personal SET numero_accion=CONCAT(@serie,'-',RIGHT(CONCAT('000',@numero),CASE WHEN @numero<1000 THEN 3 ELSE LEN(CONVERT(VARCHAR(12),@numero)) END),'-',@anio),
            regimen_laboral=@regimen,tipo_documento=@tipo_documento WHERE accion_id=@accion_id;
        FETCH NEXT FROM acciones INTO @accion_id,@empleado_id,@tipo,@fecha,@regimen;
    END
    CLOSE acciones;DEALLOCATE acciones;
END;
GO

IF DATABASE_PRINCIPAL_ID('portal_app_role') IS NOT NULL
BEGIN
    GRANT SELECT ON dbo.th_secuencias_documentos TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_asignar_regimen_empleado TO portal_app_role;
END;
GO
IF OBJECT_ID('dbo.th_schema_migrations','U') IS NOT NULL AND NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.29.1')
    INSERT dbo.th_schema_migrations(version,nombre_archivo) VALUES('2026.08.29.1','migracion_regimen_laboral_20260829.sql');
GO
