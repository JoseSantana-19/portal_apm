/* ============================================================================
   Portal Portuario APM - Expediente documental firmado e historial integral
   Base objetivo: Talento_Humano
   Version: 2026.08.27.1

   No modifica los formatos oficiales ni sus PDF. Conserva el PDF firmado y
   escaneado como evidencia privada, versionada, íntegra y auditada.
   ============================================================================ */
USE [Talento_Humano];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET XACT_ABORT ON;
GO

IF OBJECT_ID('dbo.th_documentos_firmados','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_documentos_firmados(
        documento_id BIGINT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_documentos_firmados PRIMARY KEY,
        empleado_id INT NOT NULL,
        tipo_documento VARCHAR(30) NOT NULL,
        accion_id INT NULL,
        estudio_id INT NULL,
        paz_salvo_id INT NULL,
        origen_id AS CONVERT(BIGINT,CASE
            WHEN tipo_documento='FICHA_PERSONAL' THEN empleado_id
            WHEN tipo_documento='ACCION_PERSONAL' THEN accion_id
            WHEN tipo_documento='ESTUDIO_SOCIOECONOMICO' THEN estudio_id
            WHEN tipo_documento='PAZ_SALVO' THEN paz_salvo_id END) PERSISTED,
        version_documento INT NOT NULL,
        nombre_original NVARCHAR(255) NOT NULL,
        ruta_privada NVARCHAR(500) NOT NULL,
        mime_type VARCHAR(100) NOT NULL CONSTRAINT DF_th_doc_firmado_mime DEFAULT('application/pdf'),
        tamano_bytes BIGINT NOT NULL,
        sha256 CHAR(64) NOT NULL,
        estado VARCHAR(20) NOT NULL CONSTRAINT DF_th_doc_firmado_estado DEFAULT('FIRMADO'),
        observaciones NVARCHAR(500) NULL,
        usuario_carga VARCHAR(50) NOT NULL,
        direccion_ip VARCHAR(45) NOT NULL,
        fecha_carga DATETIME2(3) NOT NULL CONSTRAINT DF_th_doc_firmado_fecha DEFAULT(SYSDATETIME()),
        CONSTRAINT FK_th_doc_firmado_empleado FOREIGN KEY(empleado_id) REFERENCES dbo.th_empleados(empleado_id),
        CONSTRAINT FK_th_doc_firmado_accion FOREIGN KEY(accion_id) REFERENCES dbo.th_acciones_personal(accion_id),
        CONSTRAINT FK_th_doc_firmado_estudio FOREIGN KEY(estudio_id) REFERENCES dbo.th_estudios_socioeconomicos(estudio_id),
        CONSTRAINT FK_th_doc_firmado_paz FOREIGN KEY(paz_salvo_id) REFERENCES dbo.th_paz_salvo(paz_salvo_id),
        CONSTRAINT CK_th_doc_firmado_tipo CHECK(tipo_documento IN('FICHA_PERSONAL','ACCION_PERSONAL','ESTUDIO_SOCIOECONOMICO','PAZ_SALVO')),
        CONSTRAINT CK_th_doc_firmado_origen CHECK(
            (tipo_documento='FICHA_PERSONAL' AND accion_id IS NULL AND estudio_id IS NULL AND paz_salvo_id IS NULL)
         OR (tipo_documento='ACCION_PERSONAL' AND accion_id IS NOT NULL AND estudio_id IS NULL AND paz_salvo_id IS NULL)
         OR (tipo_documento='ESTUDIO_SOCIOECONOMICO' AND accion_id IS NULL AND estudio_id IS NOT NULL AND paz_salvo_id IS NULL)
         OR (tipo_documento='PAZ_SALVO' AND accion_id IS NULL AND estudio_id IS NULL AND paz_salvo_id IS NOT NULL)
        ),
        CONSTRAINT CK_th_doc_firmado_version CHECK(version_documento>0),
        CONSTRAINT CK_th_doc_firmado_tamano CHECK(tamano_bytes>0 AND tamano_bytes<=20971520),
        CONSTRAINT CK_th_doc_firmado_hash CHECK(sha256 NOT LIKE '%[^0-9A-Fa-f]%'),
        CONSTRAINT CK_th_doc_firmado_estado CHECK(estado IN('FIRMADO','REEMPLAZADO'))
    );
    CREATE INDEX IX_th_doc_firmado_empleado_fecha ON dbo.th_documentos_firmados(empleado_id,fecha_carga DESC,documento_id DESC);
    CREATE UNIQUE INDEX UX_th_doc_firmado_version ON dbo.th_documentos_firmados(tipo_documento,origen_id,version_documento);
    CREATE UNIQUE INDEX UX_th_doc_firmado_actual ON dbo.th_documentos_firmados(tipo_documento,origen_id) WHERE estado='FIRMADO';
END;
GO

CREATE OR ALTER VIEW dbo.vw_th_documentos_firmados
AS
SELECT d.documento_id,d.empleado_id,e.identificacion,e.apellidos,e.nombres,
       d.tipo_documento,d.origen_id,d.accion_id,d.estudio_id,d.paz_salvo_id,
       COALESCE(a.numero_accion,s.codigo_formato,ps.numero_documento,
                CONCAT('EXP-',e.identificacion)) numero_documento,
       d.version_documento,d.nombre_original,d.ruta_privada,d.mime_type,
       d.tamano_bytes,d.sha256,d.estado,d.observaciones,d.usuario_carga,
       d.direccion_ip,d.fecha_carga
FROM dbo.th_documentos_firmados d
JOIN dbo.th_empleados e ON e.empleado_id=d.empleado_id
LEFT JOIN dbo.th_acciones_personal a ON a.accion_id=d.accion_id
LEFT JOIN dbo.th_estudios_socioeconomicos s ON s.estudio_id=d.estudio_id
LEFT JOIN dbo.th_paz_salvo ps ON ps.paz_salvo_id=d.paz_salvo_id;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_registrar_documento_firmado
    @empleado_id INT,@tipo_documento VARCHAR(30),@origen_id BIGINT,
    @nombre_original NVARCHAR(255),@ruta_privada NVARCHAR(500),
    @mime_type VARCHAR(100),@tamano_bytes BIGINT,@sha256 CHAR(64),
    @observaciones NVARCHAR(500),@usuario VARCHAR(50),@ip VARCHAR(45)
AS
BEGIN
    SET NOCOUNT ON;SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        SET @tipo_documento=UPPER(LTRIM(RTRIM(@tipo_documento)));
        IF @tipo_documento NOT IN('FICHA_PERSONAL','ACCION_PERSONAL','ESTUDIO_SOCIOECONOMICO','PAZ_SALVO')
            THROW 52200,'Tipo de documento firmado no válido.',1;
        IF NOT EXISTS(SELECT 1 FROM dbo.th_empleados WHERE empleado_id=@empleado_id)
            THROW 52201,'El funcionario no existe.',1;
        IF @tipo_documento='FICHA_PERSONAL' AND @origen_id<>@empleado_id
            THROW 52202,'El origen de la ficha no corresponde al funcionario.',1;
        IF @tipo_documento='ACCION_PERSONAL' AND NOT EXISTS(SELECT 1 FROM dbo.th_acciones_personal WHERE accion_id=@origen_id AND empleado_id=@empleado_id)
            THROW 52203,'La Acción de Personal no corresponde al funcionario.',1;
        IF @tipo_documento='ACCION_PERSONAL' AND NOT EXISTS(SELECT 1 FROM dbo.th_acciones_personal WHERE accion_id=@origen_id AND estado_documento='APROBADO')
            THROW 52207,'La Acción de Personal debe estar aprobada antes de incorporar el documento firmado.',1;
        IF @tipo_documento='ESTUDIO_SOCIOECONOMICO' AND NOT EXISTS(SELECT 1 FROM dbo.th_estudios_socioeconomicos WHERE estudio_id=@origen_id AND empleado_id=@empleado_id)
            THROW 52204,'El estudio socioeconómico no corresponde al funcionario.',1;
        IF @tipo_documento='PAZ_SALVO' AND NOT EXISTS(SELECT 1 FROM dbo.th_paz_salvo WHERE paz_salvo_id=@origen_id AND empleado_id=@empleado_id)
            THROW 52205,'El Paz y Salvo no corresponde al funcionario.',1;
        IF @tipo_documento='PAZ_SALVO' AND NOT EXISTS(SELECT 1 FROM dbo.th_paz_salvo WHERE paz_salvo_id=@origen_id AND estado='CERRADO')
            THROW 52208,'El Paz y Salvo debe estar cerrado antes de incorporar el documento firmado.',1;

        DECLARE @version INT,@recurso NVARCHAR(255)=CONCAT('th_documento_firmado_',@tipo_documento,'_',@origen_id),@lock INT;
        EXEC @lock=sys.sp_getapplock @Resource=@recurso,@LockMode='Exclusive',@LockOwner='Transaction',@LockTimeout=10000;
        IF @lock<0 THROW 52206,'No fue posible reservar la versión documental.',1;
        SELECT @version=COALESCE(MAX(version_documento),0)+1 FROM dbo.th_documentos_firmados WITH(UPDLOCK,HOLDLOCK)
         WHERE tipo_documento=@tipo_documento AND origen_id=@origen_id;
        UPDATE dbo.th_documentos_firmados SET estado='REEMPLAZADO'
         WHERE tipo_documento=@tipo_documento AND origen_id=@origen_id AND estado='FIRMADO';
        INSERT dbo.th_documentos_firmados(
            empleado_id,tipo_documento,accion_id,estudio_id,paz_salvo_id,version_documento,
            nombre_original,ruta_privada,mime_type,tamano_bytes,sha256,observaciones,
            usuario_carga,direccion_ip)
        VALUES(
            @empleado_id,@tipo_documento,
            IIF(@tipo_documento='ACCION_PERSONAL',CONVERT(INT,@origen_id),NULL),
            IIF(@tipo_documento='ESTUDIO_SOCIOECONOMICO',CONVERT(INT,@origen_id),NULL),
            IIF(@tipo_documento='PAZ_SALVO',CONVERT(INT,@origen_id),NULL),
            @version,@nombre_original,@ruta_privada,@mime_type,@tamano_bytes,LOWER(@sha256),
            NULLIF(@observaciones,''),@usuario,@ip);
        DECLARE @id BIGINT=SCOPE_IDENTITY(),@detalle NVARCHAR(500)=CONCAT(N'Cargó documento firmado ',@tipo_documento,N' #',@origen_id,N', versión ',@version,N', SHA-256 ',LOWER(@sha256),N'.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Expediente Documental','CARGAR_FIRMADO',@detalle,@ip;
        COMMIT;
        SELECT 1 exito,@id documento_id,@version version_documento,'Documento firmado incorporado al expediente.' mensaje;
    END TRY BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,CAST(NULL AS BIGINT) documento_id,CAST(NULL AS INT) version_documento,ERROR_MESSAGE() mensaje;
    END CATCH
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_consultar_documentos_firmados
    @tipo_documento VARCHAR(30)=NULL,@origen_id BIGINT=NULL,@empleado_id INT=NULL,
    @usuario VARCHAR(50),@ip VARCHAR(45)
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario,'Expediente Documental','CONSULTAR',
         'Consultó documentos firmados del expediente.',@ip;
    SELECT * FROM dbo.vw_th_documentos_firmados
     WHERE (@tipo_documento IS NULL OR tipo_documento=@tipo_documento)
       AND (@origen_id IS NULL OR origen_id=@origen_id)
       AND (@empleado_id IS NULL OR empleado_id=@empleado_id)
     ORDER BY fecha_carga DESC,documento_id DESC;
END;
GO

/* Un evento laboral es una novedad del expediente del funcionario. No incluye
   accesos, consultas ni administración del sistema; estos permanecen en la
   bitácora de auditoría. */
CREATE OR ALTER VIEW dbo.vw_th_eventos_laborales
AS
SELECT CONCAT('ACCION-',a.accion_id) evento_clave,a.empleado_id,
       CASE WHEN UPPER(LTRIM(RTRIM(a.tipo_accion))) COLLATE Modern_Spanish_CI_AI=N'VACACIONES' THEN 'VACACIONES'
            WHEN NULLIF(LTRIM(RTRIM(a.tipo_novedad_jornada)),'') IS NOT NULL THEN 'JORNADA'
            ELSE 'ACCION_PERSONAL' END categoria,
       COALESCE(NULLIF(a.tipo_novedad_jornada,''),a.tipo_accion) subtipo,
       CASE WHEN UPPER(LTRIM(RTRIM(a.tipo_accion))) COLLATE Modern_Spanish_CI_AI=N'VACACIONES' THEN N'Vacaciones'
            WHEN NULLIF(LTRIM(RTRIM(a.tipo_novedad_jornada)),'') IS NOT NULL THEN N'Cambio temporal de jornada'
            ELSE N'Acción de Personal' END titulo,
       a.explicacion_legal detalle,a.fecha_rige_desde fecha_evento,a.fecha_rige_desde fecha_desde,
       a.fecha_rige_hasta fecha_hasta,a.estado_documento estado,'ACCION_PERSONAL' origen_tipo,
       CONVERT(BIGINT,a.accion_id) origen_id,a.numero_accion numero_documento,a.fecha_creacion fecha_registro
FROM dbo.th_acciones_personal a
UNION ALL
SELECT CONCAT('MOVIMIENTO-',m.movimiento_id),m.empleado_id,'MOVIMIENTO_INTERNO','CAMBIO_AREA',N'Movimiento interno',
       CONCAT(COALESCE(uo.nombre_unidad,N'Sin área'),N' → ',ud.nombre_unidad,N'. ',m.motivo),
       m.fecha_movimiento,m.fecha_movimiento,NULL,'REGISTRADO','MOVIMIENTO_INTERNO',CONVERT(BIGINT,m.movimiento_id),NULL,m.fecha_creacion
FROM dbo.th_movimientos_personal m
LEFT JOIN dbo.th_unidades_organizacionales uo ON uo.unidad_id=m.unidad_origen_id
JOIN dbo.th_unidades_organizacionales ud ON ud.unidad_id=m.unidad_destino_id
UNION ALL
SELECT CONCAT('SOCIO-',s.estudio_id),s.empleado_id,'FORMULARIO','ESTUDIO_SOCIOECONOMICO',N'Estudio socioeconómico',
       CONCAT(N'Formulario ',s.codigo_formato,N' registrado.'),CONVERT(DATE,s.fecha_creacion),CONVERT(DATE,s.fecha_creacion),NULL,
       IIF(s.estado=1,'REGISTRADO','INACTIVO'),'ESTUDIO_SOCIOECONOMICO',CONVERT(BIGINT,s.estudio_id),s.codigo_formato,s.fecha_creacion
FROM dbo.th_estudios_socioeconomicos s
UNION ALL
SELECT CONCAT('PAZ-',p.paz_salvo_id),p.empleado_id,'FORMULARIO','PAZ_SALVO',N'Paz y Salvo',
       p.observaciones_generales,p.fecha_emision,p.fecha_emision,p.fecha_salida,p.estado,'PAZ_SALVO',CONVERT(BIGINT,p.paz_salvo_id),p.numero_documento,p.fecha_creacion
FROM dbo.th_paz_salvo p
UNION ALL
SELECT CONCAT('FIRMADO-',d.documento_id),d.empleado_id,'DOCUMENTO_FIRMADO',d.tipo_documento,N'Documento firmado incorporado',
       CONCAT(d.nombre_original,N' · versión ',d.version_documento,N' · SHA-256 ',d.sha256),
       CONVERT(DATE,d.fecha_carga),CONVERT(DATE,d.fecha_carga),NULL,d.estado,'DOCUMENTO_FIRMADO',CONVERT(BIGINT,d.documento_id),
       COALESCE(a.numero_accion,s.codigo_formato,p.numero_documento,CONCAT('EXP-',e.identificacion)),d.fecha_carga
FROM dbo.th_documentos_firmados d
JOIN dbo.th_empleados e ON e.empleado_id=d.empleado_id
LEFT JOIN dbo.th_acciones_personal a ON a.accion_id=d.accion_id
LEFT JOIN dbo.th_estudios_socioeconomicos s ON s.estudio_id=d.estudio_id
LEFT JOIN dbo.th_paz_salvo p ON p.paz_salvo_id=d.paz_salvo_id;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_consultar_eventos_laborales
    @empleado_id INT=NULL,@usuario VARCHAR(50),@ip VARCHAR(45)
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario,'Historial Laboral','CONSULTAR_EVENTOS',
         'Consultó la línea integral de eventos laborales.',@ip;
    SELECT * FROM dbo.vw_th_eventos_laborales
     WHERE @empleado_id IS NULL OR empleado_id=@empleado_id
     ORDER BY empleado_id,fecha_evento,fecha_registro,evento_clave;
END;
GO

IF NOT EXISTS(SELECT 1 FROM dbo.th_modulos WHERE codigo_modulo='documentos_firmados')
    INSERT dbo.th_modulos(nombre_modulo,ruta_frontend,codigo_modulo)
    VALUES(N'Documentos firmados',N'talento-humano/documentos-firmados','documentos_firmados');
GO
MERGE dbo.th_permisos_rol AS d
USING (SELECT r.rol_id,m.modulo_id,
       CAST(IIF(r.rol_id IN(1,2,3),1,0) AS BIT) ver,
       CAST(IIF(r.rol_id IN(1,2,3),1,0) AS BIT) crear,
       CAST(IIF(r.rol_id IN(1,2,3),1,0) AS BIT) editar,
       CAST(IIF(r.rol_id=1,1,0) AS BIT) eliminar
       FROM dbo.th_roles r CROSS JOIN dbo.th_modulos m
       WHERE m.codigo_modulo='documentos_firmados') s
ON d.rol_id=s.rol_id AND d.modulo_id=s.modulo_id
WHEN NOT MATCHED THEN INSERT(rol_id,modulo_id,puede_visualizar,puede_crear,puede_editar,puede_eliminar)
VALUES(s.rol_id,s.modulo_id,s.ver,s.crear,s.editar,s.eliminar);
GO

IF DATABASE_PRINCIPAL_ID('portal_app_role') IS NOT NULL
BEGIN
    GRANT SELECT ON dbo.vw_th_documentos_firmados TO portal_app_role;
    GRANT SELECT ON dbo.vw_th_eventos_laborales TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_registrar_documento_firmado TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_consultar_documentos_firmados TO portal_app_role;
    GRANT EXECUTE ON dbo.sp_th_consultar_eventos_laborales TO portal_app_role;
END;
GO

IF OBJECT_ID('dbo.th_schema_migrations','U') IS NOT NULL
AND NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.27.1')
    INSERT dbo.th_schema_migrations(version,nombre_archivo)
    VALUES('2026.08.27.1','migracion_expediente_documental_historial_20260827.sql');
GO
EXEC dbo.sp_th_registrar_auditoria 'MIGRACION','Sistema','MIGRACION_EXPEDIENTE_20260827',
     'Expediente documental firmado e historial integral instalados.','127.0.0.1';
GO
PRINT 'Migración 2026.08.27.1 completada.';
GO
