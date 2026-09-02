/* Portal Portuario APM - geolocalizacion del estudio socioeconomico
   Version 2026.08.25.2
   Idempotente. Agrega pagina 4, coordenadas, mapa privado y QR. */
USE [Talento_Humano];
GO
SET NOCOUNT ON;
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET ARITHABORT ON;
SET NUMERIC_ROUNDABORT OFF;
GO

IF OBJECT_ID('dbo.th_estudios_socioeconomicos','U') IS NULL
    THROW 52200,'Debe aplicar primero migracion_formatos_oficiales_2026.sql.',1;
GO

IF COL_LENGTH('dbo.th_estudios_socioeconomicos','mapa_url_original') IS NULL
    ALTER TABLE dbo.th_estudios_socioeconomicos ADD mapa_url_original NVARCHAR(2048) NULL;
IF COL_LENGTH('dbo.th_estudios_socioeconomicos','latitud') IS NULL
    ALTER TABLE dbo.th_estudios_socioeconomicos ADD latitud DECIMAL(9,6) NULL;
IF COL_LENGTH('dbo.th_estudios_socioeconomicos','longitud') IS NULL
    ALTER TABLE dbo.th_estudios_socioeconomicos ADD longitud DECIMAL(9,6) NULL;
IF COL_LENGTH('dbo.th_estudios_socioeconomicos','indicaciones_llegada') IS NULL
    ALTER TABLE dbo.th_estudios_socioeconomicos ADD indicaciones_llegada NVARCHAR(750) NULL;
IF COL_LENGTH('dbo.th_estudios_socioeconomicos','origen_geolocalizacion') IS NULL
    ALTER TABLE dbo.th_estudios_socioeconomicos ADD origen_geolocalizacion VARCHAR(15) NULL;
IF COL_LENGTH('dbo.th_estudios_socioeconomicos','mapa_imagen') IS NULL
    ALTER TABLE dbo.th_estudios_socioeconomicos ADD mapa_imagen NVARCHAR(260) NULL;
IF COL_LENGTH('dbo.th_estudios_socioeconomicos','qr_imagen') IS NULL
    ALTER TABLE dbo.th_estudios_socioeconomicos ADD qr_imagen NVARCHAR(260) NULL;
ALTER TABLE dbo.th_estudios_socioeconomicos ALTER COLUMN referencia_domiciliaria NVARCHAR(500) NULL;
GO

IF OBJECT_ID('dbo.CK_th_estudio_latitud','C') IS NULL
    ALTER TABLE dbo.th_estudios_socioeconomicos WITH CHECK ADD CONSTRAINT CK_th_estudio_latitud
        CHECK(latitud IS NULL OR latitud BETWEEN -90 AND 90);
IF OBJECT_ID('dbo.CK_th_estudio_longitud','C') IS NULL
    ALTER TABLE dbo.th_estudios_socioeconomicos WITH CHECK ADD CONSTRAINT CK_th_estudio_longitud
        CHECK(longitud IS NULL OR longitud BETWEEN -180 AND 180);
IF OBJECT_ID('dbo.CK_th_estudio_coordenadas_par','C') IS NULL
    ALTER TABLE dbo.th_estudios_socioeconomicos WITH CHECK ADD CONSTRAINT CK_th_estudio_coordenadas_par
        CHECK((latitud IS NULL AND longitud IS NULL) OR (latitud IS NOT NULL AND longitud IS NOT NULL));
IF OBJECT_ID('dbo.CK_th_estudio_origen_geo','C') IS NULL
    ALTER TABLE dbo.th_estudios_socioeconomicos WITH CHECK ADD CONSTRAINT CK_th_estudio_origen_geo
        CHECK(origen_geolocalizacion IS NULL OR origen_geolocalizacion IN('URL','MAPA','MANUAL'));
GO

IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_estudios_socioeconomicos') AND name='IX_th_estudios_geolocalizacion')
    CREATE INDEX IX_th_estudios_geolocalizacion ON dbo.th_estudios_socioeconomicos(latitud,longitud)
    WHERE latitud IS NOT NULL AND longitud IS NOT NULL;
GO

/* Los permisos existentes de tabla se conservan al agregar columnas. Se
   explicitan para que un ambiente nuevo tenga el mismo minimo privilegio. */
IF DATABASE_PRINCIPAL_ID('portal_app_role') IS NOT NULL
BEGIN
    GRANT SELECT,INSERT,UPDATE ON dbo.th_estudios_socioeconomicos TO portal_app_role;
END;
GO

IF OBJECT_ID('dbo.th_schema_migrations','U') IS NOT NULL
AND NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.25.2')
    INSERT dbo.th_schema_migrations(version,nombre_archivo)
    VALUES('2026.08.25.2','migracion_geolocalizacion_socioeconomica_20260825.sql');
GO

EXEC dbo.sp_th_registrar_auditoria
    'MIGRACION','Sistema','MIGRACION_GEOLOCALIZACION_SOCIO_20260825',
    'Pagina 4 socioeconomica, coordenadas, mapa privado y QR instalados.','127.0.0.1';
GO
PRINT 'Migracion 2026.08.25.2 completada.';
GO
