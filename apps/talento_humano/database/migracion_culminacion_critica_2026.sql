USE [Talento_Humano];
GO
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

/* Seguridad de cuentas y revocacion de sesiones */
IF COL_LENGTH('dbo.th_usuarios_sistema','token_version') IS NULL
    ALTER TABLE dbo.th_usuarios_sistema ADD token_version INT NOT NULL CONSTRAINT DF_th_usuarios_token_version DEFAULT(1);
IF COL_LENGTH('dbo.th_usuarios_sistema','intentos_fallidos') IS NULL
    ALTER TABLE dbo.th_usuarios_sistema ADD intentos_fallidos INT NOT NULL CONSTRAINT DF_th_usuarios_intentos DEFAULT(0);
IF COL_LENGTH('dbo.th_usuarios_sistema','bloqueado_hasta') IS NULL
    ALTER TABLE dbo.th_usuarios_sistema ADD bloqueado_hasta DATETIME2(0) NULL;
IF COL_LENGTH('dbo.th_usuarios_sistema','debe_cambiar_clave') IS NULL
    ALTER TABLE dbo.th_usuarios_sistema ADD debe_cambiar_clave BIT NOT NULL CONSTRAINT DF_th_usuarios_cambiar DEFAULT(1);
GO

/* Codigo estable para aplicar RBAC desde las rutas */
IF COL_LENGTH('dbo.th_modulos','codigo_modulo') IS NULL
    ALTER TABLE dbo.th_modulos ADD codigo_modulo VARCHAR(50) NULL;
GO
UPDATE dbo.th_modulos SET codigo_modulo=CASE modulo_id
    WHEN 1 THEN 'dashboard' WHEN 2 THEN 'directorio' WHEN 3 THEN 'empleados'
    WHEN 4 THEN 'acciones' WHEN 5 THEN 'prototipos' WHEN 6 THEN 'usuarios'
    WHEN 7 THEN 'roles' WHEN 8 THEN 'maestros' WHEN 9 THEN 'maestros'
    WHEN 10 THEN 'maestros' WHEN 11 THEN 'maestros' WHEN 12 THEN 'maestros'
    ELSE COALESCE(codigo_modulo,CONCAT('modulo_',modulo_id)) END
WHERE codigo_modulo IS NULL;

DECLARE @NuevosModulos TABLE(codigo VARCHAR(50),nombre VARCHAR(100),ruta VARCHAR(150));
INSERT @NuevosModulos VALUES
('socioeconomico','Estudio de Seguridad Socioeconomico','talento-humano/estudio-seguridad'),
('biblioteca','Biblioteca de Formularios','talento-humano/biblioteca'),
('auditoria','Auditoria y Bitacora','auditoria/logs'),
('reportes','Reportes Institucionales','reportes'),
('politicas','Politicas y Normativas','admin/politicas'),
('movimientos','Movimientos Internos','talento-humano/empleado/movimiento');
INSERT dbo.th_modulos(nombre_modulo,ruta_frontend,codigo_modulo)
SELECT n.nombre,n.ruta,n.codigo FROM @NuevosModulos n
WHERE NOT EXISTS(SELECT 1 FROM dbo.th_modulos m WHERE m.codigo_modulo=n.codigo);
GO

IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_modulos') AND name='IX_th_modulos_codigo')
    CREATE INDEX IX_th_modulos_codigo ON dbo.th_modulos(codigo_modulo);
GO

/* Una fila por rol/modulo; se conservan IDs y se actualizan permisos de forma idempotente */
;WITH Permisos AS (
    SELECT r.rol_id,m.modulo_id,
        CAST(CASE
            WHEN r.rol_id=1 THEN 1
            WHEN r.rol_id=2 THEN 1
            WHEN r.rol_id=3 AND m.codigo_modulo IN ('dashboard','directorio','empleados','acciones','socioeconomico','biblioteca','reportes','movimientos','prototipos') THEN 1
            WHEN r.rol_id=4 AND m.codigo_modulo IN ('dashboard','directorio','biblioteca','prototipos') THEN 1
            ELSE 0 END AS BIT) ver,
        CAST(CASE
            WHEN r.rol_id=1 THEN 1
            WHEN r.rol_id=2 AND m.codigo_modulo NOT IN ('roles') THEN 1
            WHEN r.rol_id=3 AND m.codigo_modulo IN ('empleados','acciones','socioeconomico','movimientos') THEN 1
            ELSE 0 END AS BIT) crear,
        CAST(CASE
            WHEN r.rol_id=1 THEN 1
            WHEN r.rol_id=2 AND m.codigo_modulo NOT IN ('roles') THEN 1
            WHEN r.rol_id=3 AND m.codigo_modulo IN ('directorio','empleados','acciones','socioeconomico','movimientos') THEN 1
            ELSE 0 END AS BIT) editar,
        CAST(CASE WHEN r.rol_id=1 THEN 1 ELSE 0 END AS BIT) eliminar
    FROM dbo.th_roles r CROSS JOIN dbo.th_modulos m WHERE r.estado=1
)
MERGE dbo.th_permisos_rol AS t
USING Permisos AS s ON s.rol_id=t.rol_id AND s.modulo_id=t.modulo_id
WHEN NOT MATCHED THEN INSERT(rol_id,modulo_id,puede_visualizar,puede_crear,puede_editar,puede_eliminar)
VALUES(s.rol_id,s.modulo_id,s.ver,s.crear,s.editar,s.eliminar);
GO
IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_permisos_rol') AND name='UX_th_permisos_rol_modulo')
    CREATE UNIQUE INDEX UX_th_permisos_rol_modulo ON dbo.th_permisos_rol(rol_id,modulo_id);
GO

/* Repositorio real de politicas y normativas */
IF OBJECT_ID('dbo.th_politicas_documentos','U') IS NULL
BEGIN
    CREATE TABLE dbo.th_politicas_documentos(
        documento_id INT IDENTITY(1,1) PRIMARY KEY,
        titulo NVARCHAR(200) NOT NULL,
        categoria NVARCHAR(80) NOT NULL,
        version NVARCHAR(30) NOT NULL,
        descripcion NVARCHAR(500) NULL,
        nombre_archivo NVARCHAR(255) NOT NULL,
        ruta_privada NVARCHAR(500) NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        tamano_bytes BIGINT NOT NULL,
        vigente BIT NOT NULL CONSTRAINT DF_th_politicas_vigente DEFAULT(1),
        descargas INT NOT NULL CONSTRAINT DF_th_politicas_descargas DEFAULT(0),
        usuario_crea VARCHAR(50) NOT NULL,
        fecha_creacion DATETIME2(3) NOT NULL CONSTRAINT DF_th_politicas_fecha DEFAULT(SYSDATETIME()),
        CONSTRAINT CK_th_politicas_tamano CHECK(tamano_bytes BETWEEN 1 AND 20971520)
    );
END;
GO

/* Rol SQL de minimo privilegio. El DBA debe asociar un LOGIN secreto a este rol. */
IF DATABASE_PRINCIPAL_ID('portal_app_role') IS NULL CREATE ROLE portal_app_role AUTHORIZATION dbo;
GRANT SELECT ON SCHEMA::dbo TO portal_app_role;
GRANT EXECUTE ON SCHEMA::dbo TO portal_app_role;
GRANT INSERT,UPDATE ON dbo.th_usuarios_sistema TO portal_app_role;
GRANT UPDATE ON dbo.th_permisos_rol TO portal_app_role;
GRANT INSERT,UPDATE ON dbo.th_politicas_documentos TO portal_app_role;
GRANT INSERT,UPDATE ON dbo.th_estudios_socioeconomicos TO portal_app_role;
GRANT INSERT,UPDATE,DELETE ON dbo.th_estudio_hijos TO portal_app_role;
GRANT INSERT,UPDATE,DELETE ON dbo.th_estudio_capacitaciones TO portal_app_role;
GRANT INSERT,UPDATE,DELETE ON dbo.th_estudio_experiencias TO portal_app_role;
DENY UPDATE,DELETE ON dbo.th_logs_auditoria TO portal_app_role;
GO

/* Flujo documental de Accion de Personal */
IF COL_LENGTH('dbo.th_acciones_personal','usuario_aprueba') IS NULL
    ALTER TABLE dbo.th_acciones_personal ADD usuario_aprueba VARCHAR(50) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','fecha_aprobacion') IS NULL
    ALTER TABLE dbo.th_acciones_personal ADD fecha_aprobacion DATETIME2(3) NULL;
IF COL_LENGTH('dbo.th_acciones_personal','motivo_anulacion') IS NULL
    ALTER TABLE dbo.th_acciones_personal ADD motivo_anulacion NVARCHAR(500) NULL;
GO

IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_acciones_personal') AND name='UX_th_acciones_numero')
    CREATE UNIQUE INDEX UX_th_acciones_numero ON dbo.th_acciones_personal(numero_accion);
GO

CREATE OR ALTER TRIGGER dbo.tr_th_accion_estado_inicial
ON dbo.th_acciones_personal
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE a SET estado_documento='BORRADOR'
    FROM dbo.th_acciones_personal a JOIN inserted i ON i.accion_id=a.accion_id;
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_aprobar_accion_personal
    @accion_id INT,@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        DECLARE @empleado INT,@unidad INT,@puesto INT,@fecha DATE,@numero VARCHAR(50),@rmu DECIMAL(10,2);
        SELECT @empleado=empleado_id,@unidad=COALESCE(propuesta_unidad_id,actual_unidad_id),
               @puesto=COALESCE(propuesta_puesto_id,actual_puesto_id),@fecha=fecha_rige_desde,
               @numero=numero_accion,@rmu=propuesta_remuneracion
        FROM dbo.th_acciones_personal WITH(UPDLOCK,HOLDLOCK)
        WHERE accion_id=@accion_id AND UPPER(estado_documento) IN ('BORRADOR','PENDIENTE');
        IF @empleado IS NULL THROW 51100,'La accion no existe o ya fue resuelta.',1;

        UPDATE dbo.th_acciones_personal SET estado_documento='APROBADO',usuario_aprueba=@usuario,
            fecha_aprobacion=SYSDATETIME() WHERE accion_id=@accion_id;

        IF @unidad IS NOT NULL AND @puesto IS NOT NULL
        BEGIN
            UPDATE dbo.th_historial_laboral SET fecha_hasta=DATEADD(DAY,-1,@fecha)
            WHERE empleado_id=@empleado AND fecha_hasta IS NULL;
            INSERT dbo.th_historial_laboral(empleado_id,puesto_id,unidad_id,fecha_desde,fecha_hasta,observaciones,usuario_crea,fecha_creacion)
            VALUES(@empleado,@puesto,@unidad,@fecha,NULL,CONCAT('Aplicado por Accion de Personal ',@numero),@usuario,GETDATE());
            UPDATE dbo.th_empleados SET unidad_id=@unidad,puesto_id=@puesto,
                sueldo_rmu=COALESCE(NULLIF(@rmu,0),sueldo_rmu) WHERE empleado_id=@empleado;
        END;
        DECLARE @detalle_aprobacion VARCHAR(500)=CONCAT('Aprobo ',@numero,' y aplico la situacion laboral.');
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Accion de Personal','APROBAR',@detalle_aprobacion,@ip;
        COMMIT;
        SELECT 1 exito,'Accion aprobada y aplicada al historial laboral.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,ERROR_MESSAGE() mensaje;
    END CATCH
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_anular_accion_personal
    @accion_id INT,@motivo NVARCHAR(500),@usuario VARCHAR(50),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON; SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF NULLIF(LTRIM(RTRIM(@motivo)),'') IS NULL THROW 51101,'Debe registrar el motivo de anulacion.',1;
        DECLARE @numero VARCHAR(50);
        SELECT @numero=numero_accion FROM dbo.th_acciones_personal WITH(UPDLOCK,HOLDLOCK)
        WHERE accion_id=@accion_id AND UPPER(estado_documento) IN ('BORRADOR','PENDIENTE');
        IF @numero IS NULL THROW 51102,'La accion no existe o ya fue resuelta.',1;
        UPDATE dbo.th_acciones_personal SET estado_documento='ANULADO',motivo_anulacion=@motivo WHERE accion_id=@accion_id;
        DECLARE @detalle_anulacion VARCHAR(500)=CONCAT('Anulo ',@numero,'. Motivo: ',@motivo);
        EXEC dbo.sp_th_registrar_auditoria @usuario,'Accion de Personal','ANULAR',@detalle_anulacion,@ip;
        COMMIT;
        SELECT 1 exito,'Accion anulada.' mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT>0 ROLLBACK;
        SELECT 0 exito,ERROR_MESSAGE() mensaje;
    END CATCH
END;
GO

/* La bitacora es append-only para todos los usuarios, incluido el usuario de aplicacion. */
CREATE OR ALTER TRIGGER dbo.tr_th_logs_auditoria_append_only
ON dbo.th_logs_auditoria
INSTEAD OF UPDATE,DELETE
AS
BEGIN
    SET NOCOUNT ON;
    THROW 51200,'La bitacora de auditoria es inmutable: UPDATE y DELETE no estan permitidos.',1;
END;
GO

/* Se conserva DATETIME para no invalidar el indice y el default existentes. */

CREATE OR ALTER PROCEDURE dbo.sp_th_auditar_lectura
    @usuario VARCHAR(50),@modulo VARCHAR(50),@recurso VARCHAR(200),@ip VARCHAR(45)='0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario,@modulo,'CONSULTAR',@recurso,@ip;
END;
GO

/* Respaldo logico previo a normalizar asignaciones importadas. */
IF OBJECT_ID('dbo.th_respaldo_normalizacion_20260729','U') IS NULL
BEGIN
    SELECT empleado_id,unidad_id,puesto_id,tipo_contrato,titulo,SYSDATETIME() fecha_respaldo
    INTO dbo.th_respaldo_normalizacion_20260729 FROM dbo.th_empleados;
    ALTER TABLE dbo.th_respaldo_normalizacion_20260729 ADD CONSTRAINT PK_th_respaldo_normalizacion_20260729 PRIMARY KEY(empleado_id);
END;
GO

/* Consolidacion no destructiva de unidades activas con igual denominacion. */
IF OBJECT_ID('tempdb..#UnidadesDuplicadas') IS NOT NULL DROP TABLE #UnidadesDuplicadas;
;WITH Base AS (
    SELECT unidad_id,nombre_unidad,MIN(unidad_id) OVER(PARTITION BY nombre_unidad) unidad_canonica,
           COUNT(*) OVER(PARTITION BY nombre_unidad) repeticiones
    FROM dbo.th_unidades_organizacionales WHERE activo=1
)
SELECT unidad_id duplicada,unidad_canonica INTO #UnidadesDuplicadas FROM Base
WHERE repeticiones>1 AND unidad_id<>unidad_canonica;

UPDATE e SET unidad_id=m.unidad_canonica FROM dbo.th_empleados e JOIN #UnidadesDuplicadas m ON m.duplicada=e.unidad_id;
UPDATE h SET unidad_id=m.unidad_canonica FROM dbo.th_historial_laboral h JOIN #UnidadesDuplicadas m ON m.duplicada=h.unidad_id;
UPDATE a SET actual_unidad_id=m.unidad_canonica FROM dbo.th_acciones_personal a JOIN #UnidadesDuplicadas m ON m.duplicada=a.actual_unidad_id;
UPDATE a SET propuesta_unidad_id=m.unidad_canonica FROM dbo.th_acciones_personal a JOIN #UnidadesDuplicadas m ON m.duplicada=a.propuesta_unidad_id;
UPDATE a SET unidad_id=m.unidad_canonica FROM dbo.th_acciones_personal_old a JOIN #UnidadesDuplicadas m ON m.duplicada=a.unidad_id;
UPDATE mv SET unidad_origen_id=m.unidad_canonica FROM dbo.th_movimientos_personal mv JOIN #UnidadesDuplicadas m ON m.duplicada=mv.unidad_origen_id;
UPDATE mv SET unidad_destino_id=m.unidad_canonica FROM dbo.th_movimientos_personal mv JOIN #UnidadesDuplicadas m ON m.duplicada=mv.unidad_destino_id;
UPDATE u SET unidad_padre_id=m.unidad_canonica FROM dbo.th_unidades_organizacionales u JOIN #UnidadesDuplicadas m ON m.duplicada=u.unidad_padre_id;
UPDATE u SET activo=0,sucedido_por_id=m.unidad_canonica,fecha_fin=COALESCE(u.fecha_fin,CONVERT(date,GETDATE()))
FROM dbo.th_unidades_organizacionales u JOIN #UnidadesDuplicadas m ON m.duplicada=u.unidad_id;
DROP TABLE #UnidadesDuplicadas;
GO

EXEC dbo.sp_th_registrar_auditoria 'MIGRACION','Sistema','MIGRACION_CRITICA',
    'Se aplico seguridad RBAC, flujo documental, repositorio y bitacora append-only.','127.0.0.1';
GO
PRINT 'migracion_culminacion_critica_2026 aplicada correctamente';
GO
