/* ============================================================================
   APM - Calidad de datos, catálogo organizacional y autocompletado inmediato
   Fuente de nombres institucionales:
   DIRECCIONES-AREAS-OPCIONES-ITEMS.xlsx (entregado el 29/07/2026)

   - No elimina unidades ni cambia claves primarias.
   - Conserva los alias históricos como inactivos y apunta sucedido_por_id.
   - Actualiza todas las claves foráneas hacia la unidad canónica.
   - Impide duplicados activos ignorando mayúsculas y acentos.
   - Amplía la vista de expediente para precargar el socioeconómico.
   ============================================================================ */
USE [Talento_Humano];
GO
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET ARITHABORT ON;
SET NUMERIC_ROUNDABORT OFF;
GO

BEGIN TRY
    BEGIN TRAN;

    IF OBJECT_ID('dbo.th_respaldo_unidades_calidad_20260729','U') IS NULL
        SELECT * INTO dbo.th_respaldo_unidades_calidad_20260729
        FROM dbo.th_unidades_organizacionales;

    IF OBJECT_ID('dbo.th_respaldo_empleados_calidad_20260729','U') IS NULL
        SELECT * INTO dbo.th_respaldo_empleados_calidad_20260729
        FROM dbo.th_empleados;

    DECLARE @Canon TABLE(
        codigo VARCHAR(30) NOT NULL PRIMARY KEY,
        nombre VARCHAR(180) NOT NULL,
        tipo_proceso VARCHAR(100) NOT NULL,
        codigo_padre VARCHAR(30) NULL,
        unidad_id INT NULL
    );

    INSERT @Canon(codigo,nombre,tipo_proceso,codigo_padre) VALUES
    ('GER-GEN','GERENCIA GENERAL','Gobernante',NULL),
    ('DIR-PLAN','DIRECCIÓN DE PLANIFICACIÓN ESTRATÉGICA','Estratégico',NULL),
    ('DIR-JUR','DIRECCIÓN DE ASESORÍA JURÍDICA','Asesoría',NULL),
    ('DIR-INF','DIRECCIÓN DE INFRAESTRUCTURA PORTUARIA','Sustantivo',NULL),
    ('DIR-OPE','DIRECCIÓN DE OPERACIONES PORTUARIAS','Sustantivo',NULL),
    ('DIR-DSP','DIRECCIÓN DE DELEGACIÓN DE SERVICIOS PORTUARIOS','Sustantivo',NULL),
    ('DIR-ADM','DIRECCIÓN ADMINISTRATIVA','Apoyo',NULL),
    ('DIR-FIN','DIRECCIÓN FINANCIERA','Apoyo',NULL),
    ('DIR-TH','DIRECCIÓN DE ADMINISTRACIÓN DE TALENTO HUMANO','Apoyo',NULL),
    ('AREA-TICS','GESTIÓN DE TECNOLOGÍA DE LA INFORMACIÓN','Apoyo','DIR-PLAN'),
    ('AREA-BIENES','CONTROL DE BIENES','Apoyo','DIR-ADM'),
    ('AREA-ARCH','DEPARTAMENTO DE ARCHIVO CENTRAL','Apoyo','DIR-ADM'),
    ('AREA-TES','TESORERÍA','Apoyo','DIR-FIN'),
    ('AREA-CON','CONTABILIDAD','Apoyo','DIR-FIN'),
    ('AREA-PROM','PROMOCIÓN Y COMERCIALIZACIÓN','Sustantivo','DIR-DSP'),
    ('AREA-CONC','CONCESIÓN','Sustantivo','DIR-DSP'),
    ('AREA-PROY','PROYECTOS DE INVERSIÓN','Sustantivo','DIR-INF'),
    ('AREA-SEG','SEGURIDAD INTEGRAL','Sustantivo','DIR-OPE'),
    ('AREA-COM','COMUNICACIÓN SOCIAL Y ATENCIÓN AL CLIENTE','Asesoría','GER-GEN'),
    ('AREA-AUD','AUDITORÍA INTERNA','Asesoría',NULL);

    DECLARE @codigo VARCHAR(30),@nombre VARCHAR(180),@tipo VARCHAR(100),@id INT;
    DECLARE canon_cursor CURSOR LOCAL FAST_FORWARD FOR
        SELECT codigo,nombre,tipo_proceso FROM @Canon ORDER BY CASE WHEN codigo_padre IS NULL THEN 0 ELSE 1 END,codigo;
    OPEN canon_cursor;
    FETCH NEXT FROM canon_cursor INTO @codigo,@nombre,@tipo;
    WHILE @@FETCH_STATUS=0
    BEGIN
        SET @id=NULL;
        SELECT TOP(1) @id=unidad_id
        FROM dbo.th_unidades_organizacionales
        WHERE UPPER(LTRIM(RTRIM(nombre_unidad))) COLLATE Modern_Spanish_CI_AI =
              UPPER(LTRIM(RTRIM(@nombre))) COLLATE Modern_Spanish_CI_AI
        ORDER BY activo DESC,unidad_id;

        IF @id IS NULL
        BEGIN
            INSERT dbo.th_unidades_organizacionales(codigo_uorg,nombre_unidad,tipo_proceso,unidad_padre_id,activo,fecha_inicio)
            VALUES(@codigo,@nombre,@tipo,NULL,1,CONVERT(date,GETDATE()));
            SET @id=CONVERT(INT,SCOPE_IDENTITY());
        END;

        UPDATE @Canon SET unidad_id=@id WHERE codigo=@codigo;
        UPDATE dbo.th_unidades_organizacionales
        SET codigo_uorg=@codigo,nombre_unidad=@nombre,tipo_proceso=@tipo,
            activo=1,fecha_fin=NULL,sucedido_por_id=NULL
        WHERE unidad_id=@id;
        FETCH NEXT FROM canon_cursor INTO @codigo,@nombre,@tipo;
    END;
    CLOSE canon_cursor;
    DEALLOCATE canon_cursor;

    DECLARE @Alias TABLE(codigo VARCHAR(30) NOT NULL,alias VARCHAR(180) NOT NULL);
    INSERT @Alias(codigo,alias) VALUES
    ('DIR-PLAN','ADM. ESTRATEGIC'),('DIR-PLAN','PLANIFICACIËN Y GESTIËN INTEGRAL'),
    ('DIR-PLAN','PLANIFICACION Y GESTION INTEGRAL'),('DIR-PLAN','CTRL.GESTION'),
    ('DIR-JUR','ASE. JURIDICA'),('DIR-JUR','ASESORIA JURID.'),('DIR-JUR','ASESORIA JURIDICA'),
    ('DIR-JUR','DIRECCIËN DE ASESORIA JURIDICA'),('DIR-JUR','DIRECCION DE ASESORIA JURIDICA'),('DIR-JUR','JURIDICO'),
    ('DIR-ADM','ADMINISTRACION'),('DIR-ADM','ADMINISTRATIVA'),('DIR-ADM','ADMINISTRATIVO'),('DIR-ADM','ADMINSITRATIVA'),
    ('DIR-TH','ADM. TALENTO HUMANO'),('DIR-TH','ADMINISTRACION DEL TALENTO HUMANO'),
    ('DIR-TH','ADMINISTRATIVA DEL TALENTO HUMANO'),('DIR-TH','ADMINSITRACION DEL TALENTO HUMANO'),
    ('DIR-TH','DIRECCIËN TALENTO HUMANO'),('DIR-TH','DIRECCION DE ADM. DEL TALENTO HUMANO'),
    ('DIR-TH','DIRECCION DE ADMINISTRACION DEL TALENTO HUMANO'),('DIR-TH','RECURSOS HUMANO'),('DIR-TH','TALENTO HUMANO'),
    ('DIR-FIN','DIRECCIËN FIANNCIERA'),('DIR-FIN','DIRECCION FINANCIERA'),('DIR-FIN','FINANCIERA'),
    ('DIR-FIN','FINANCIERA.'),('DIR-FIN','FINANCIERO'),('DIR-FIN','FINANZAS'),
    ('AREA-TICS','DIRECCION DE TECNOLOGIA DE LA INFORMACION'),('AREA-TICS','TECNOLOGIA DE LA INFORMACIËN'),
    ('AREA-TICS','TECNOLOGIAS DE LA INFORMACION'),
    ('DIR-OPE','DIRECCION DE OPERACIONES PORTUARIAS'),('DIR-OPE','GESTION DE OEPRACIONES'),
    ('DIR-OPE','GESTION DE OPERACIONES'),('DIR-OPE','OPERACIONES'),('DIR-OPE','OPERACIONES PORTUARIA'),
    ('DIR-OPE','OPERACIONES PORTUARIAS'),('DIR-OPE','TERMINAL PESQUE'),
    ('AREA-SEG','DIRECCIËN DE SEGURIDAD INTEGRAL'),('AREA-SEG','DIRECCION DE SEGURIDD INTEGRAL'),
    ('AREA-SEG','DIRECTOR DE SEGURIDAD INTEGRAL'),('AREA-SEG','SEGURIDAD INDUSTRIAL'),
    ('AREA-SEG','SEGURIDAD INTEG'),('AREA-SEG','PROTECCION'),('AREA-SEG','PRTOTECCION'),
    ('AREA-SEG','SEGURIDAD PROMOCIËN Y COMERCIALIZACIËN'),
    ('AREA-PROM','DIRECCION DE PROMOCION Y COMERCILAIZACION'),('AREA-PROM','PROMOCIËN Y COMERCIALIZACION'),
    ('AREA-PROM','PROMOCION Y COEMRCIALIZACION'),('AREA-PROM','PROMOCION Y COM'),
    ('AREA-PROM','PROMOCION Y COMERCIALIZACIËN'),('AREA-PROM','PROMOCION Y COMERCIALIZACION'),
    ('AREA-PROY','DIRECCION DE PROYECTOS DE INVERSIËN'),('AREA-PROY','DIRECCION DE PROYECTOS DE INVERSION'),
    ('AREA-PROY','PROYECTOS DE IN'),('AREA-PROY','PROYECTOS DE INVERSION'),('AREA-PROY','TECNICO'),
    ('AREA-COM','COMUNICACIËN SOCIAL'),('AREA-COM','COMUNICACION'),('AREA-COM','COMUNICACION SO'),
    ('AREA-COM','COMUNICACION SOCIAL'),('AREA-COM','COMUNICACION SOCIAL Y ATENCION AL CLIENTE'),
    ('GER-GEN','ASESORIA DE GERENCIA'),('GER-GEN','DIRECTORIO DE PRESIDENCIA'),('GER-GEN','GERENCIA'),
    ('GER-GEN','GERENCIA GENERA'),('GER-GEN','GERENCIA GRL.'),('GER-GEN','STAFF GERENCIA'),
    ('AREA-CONC','CONCESION'),('AREA-AUD','AUDITORIA INTERNA');

    CREATE TABLE #Mapa(alias_id INT NOT NULL PRIMARY KEY,canon_id INT NOT NULL);
    INSERT #Mapa(alias_id,canon_id)
    SELECT DISTINCT u.unidad_id,c.unidad_id
    FROM dbo.th_unidades_organizacionales u
    JOIN @Alias a ON UPPER(LTRIM(RTRIM(u.nombre_unidad))) COLLATE Modern_Spanish_CI_AI =
                     UPPER(LTRIM(RTRIM(a.alias))) COLLATE Modern_Spanish_CI_AI
    JOIN @Canon c ON c.codigo=a.codigo
    WHERE u.unidad_id<>c.unidad_id;

    UPDATE e SET unidad_id=m.canon_id FROM dbo.th_empleados e JOIN #Mapa m ON m.alias_id=e.unidad_id;
    UPDATE h SET unidad_id=m.canon_id FROM dbo.th_historial_laboral h JOIN #Mapa m ON m.alias_id=h.unidad_id;
    UPDATE a SET actual_unidad_id=m.canon_id FROM dbo.th_acciones_personal a JOIN #Mapa m ON m.alias_id=a.actual_unidad_id;
    UPDATE a SET propuesta_unidad_id=m.canon_id FROM dbo.th_acciones_personal a JOIN #Mapa m ON m.alias_id=a.propuesta_unidad_id;
    UPDATE a SET unidad_id=m.canon_id FROM dbo.th_acciones_personal_old a JOIN #Mapa m ON m.alias_id=a.unidad_id;
    UPDATE l SET unidad_destino_id=m.canon_id FROM dbo.th_movimientos_lote l JOIN #Mapa m ON m.alias_id=l.unidad_destino_id;
    UPDATE p SET unidad_origen_id=m.canon_id FROM dbo.th_movimientos_personal p JOIN #Mapa m ON m.alias_id=p.unidad_origen_id;
    UPDATE p SET unidad_destino_id=m.canon_id FROM dbo.th_movimientos_personal p JOIN #Mapa m ON m.alias_id=p.unidad_destino_id;
    UPDATE u SET unidad_padre_id=m.canon_id FROM dbo.th_unidades_organizacionales u JOIN #Mapa m ON m.alias_id=u.unidad_padre_id;
    UPDATE u SET sucedido_por_id=m.canon_id FROM dbo.th_unidades_organizacionales u JOIN #Mapa m ON m.alias_id=u.sucedido_por_id;

    UPDATE u SET activo=0,fecha_fin=COALESCE(fecha_fin,CONVERT(date,GETDATE())),sucedido_por_id=m.canon_id
    FROM dbo.th_unidades_organizacionales u JOIN #Mapa m ON m.alias_id=u.unidad_id;

    UPDATE hijo SET unidad_padre_id=padre.unidad_id
    FROM dbo.th_unidades_organizacionales hijo
    JOIN @Canon c ON c.unidad_id=hijo.unidad_id
    JOIN @Canon padre ON padre.codigo=c.codigo_padre
    WHERE c.codigo_padre IS NOT NULL;

    IF EXISTS(
        SELECT 1 FROM dbo.th_unidades_organizacionales WHERE activo=1
        GROUP BY UPPER(LTRIM(RTRIM(nombre_unidad))) COLLATE Modern_Spanish_CI_AI HAVING COUNT(*)>1
    ) THROW 51520,'Persisten unidades organizacionales activas duplicadas.',1;

    IF COL_LENGTH('dbo.th_unidades_organizacionales','nombre_busqueda') IS NULL
        ALTER TABLE dbo.th_unidades_organizacionales ADD nombre_busqueda AS
            (CONVERT(VARCHAR(180),UPPER(LTRIM(RTRIM(nombre_unidad))) COLLATE Modern_Spanish_CI_AI)) PERSISTED;

    IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_unidades_organizacionales') AND name='UX_th_unidades_nombre_activo')
        CREATE UNIQUE INDEX UX_th_unidades_nombre_activo
            ON dbo.th_unidades_organizacionales(nombre_busqueda) WHERE activo=1;

    EXEC dbo.sp_th_registrar_auditoria 'MIGRACION','Maestros','CONSOLIDAR_UNIDADES',
         'Unidades equivalentes consolidadas desde la matriz institucional; alias conservados como inactivos.','127.0.0.1';

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT>0 ROLLBACK;
    THROW;
END CATCH;
GO

CREATE OR ALTER VIEW dbo.vw_th_directorio_empleados
AS
SELECT
    e.empleado_id AS id,
    e.empleado_id,
    ROW_NUMBER() OVER(ORDER BY e.apellidos,e.nombres,e.empleado_id) AS numero_registro,
    e.tipo_identificacion,
    e.identificacion AS cedula,
    e.apellidos,
    e.nombres,
    LTRIM(RTRIM(CONCAT(e.apellidos, ' ', e.nombres))) AS apellidos_nombres,
    e.unidad_id,e.puesto_id,
    ISNULL(p.nombre_puesto, '') AS cargo,
    ISNULL(u.nombre_unidad, '') AS direccion_area,
    e.correo_institucional,e.correo_personal,e.estado,
    ISNULL(e.cargas_familiares, 0) AS cargas_familiares,
    e.tipo_cuenta_bancaria,e.numero_cuenta_bancaria,e.institucion_bancaria,
    e.tipo_contrato,e.sueldo_rmu AS remuneracion_mensual,e.sueldo_rmu,
    e.fecha_ingreso,e.fecha_salida,e.fecha_nacimiento,e.sexo,e.estado_civil,e.nacionalidad,e.tipo_sangre,
    e.telefono_movil,e.telefono_convencional,e.ciudad_residencia,e.direccion_domiciliaria,
    e.contacto_emergencia,e.emergencia_relacion,e.tel_emergencia,e.nivel_estudio,e.titulo,
    e.jornada,e.condicion_especial,e.tipo_discapacidad,e.porcentaje_discapacidad,
    e.cuenta_bancaria,e.codigo_iess,e.cod_emplea,e.num_iess,e.ruta_foto,e.observaciones
FROM dbo.th_empleados e
LEFT JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id
LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id=e.unidad_id;
GO

CREATE OR ALTER VIEW dbo.view_th_iddatosempledo
AS SELECT * FROM dbo.vw_th_directorio_empleados;
GO

PRINT 'Migración de calidad, búsqueda y catálogo organizacional completada.';
GO
