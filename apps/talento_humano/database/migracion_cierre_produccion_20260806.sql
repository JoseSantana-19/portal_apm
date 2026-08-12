USE [Talento_Humano];
GO
SET NOCOUNT ON;
SET XACT_ABORT ON;
GO

BEGIN TRY
    BEGIN TRAN;

    IF OBJECT_ID('dbo.th_schema_migrations','U') IS NULL
    BEGIN
        CREATE TABLE dbo.th_schema_migrations(
            migration_id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_th_schema_migrations PRIMARY KEY,
            version VARCHAR(30) NOT NULL CONSTRAINT UQ_th_schema_migrations_version UNIQUE,
            nombre_archivo VARCHAR(180) NOT NULL,
            checksum_sha256 CHAR(64) NULL,
            fecha_aplicacion DATETIME2(0) NOT NULL CONSTRAINT DF_th_schema_migrations_fecha DEFAULT SYSDATETIME(),
            aplicado_por SYSNAME NOT NULL CONSTRAINT DF_th_schema_migrations_usuario DEFAULT ORIGINAL_LOGIN()
        );
    END;

    UPDATE dbo.th_unidades_organizacionales
       SET tipo_proceso=CASE
            WHEN tipo_proceso IN ('Estrategico','Procesos Estratégicos') THEN N'Estratégico'
            WHEN tipo_proceso IN ('Asesoria','Procesos de Asesoría') THEN N'Asesoría'
            WHEN tipo_proceso IN ('Procesos Adjetivos') THEN N'Adjetivo'
            WHEN tipo_proceso IN ('Procesos Gobernantes') THEN N'Gobernante'
            WHEN tipo_proceso IN ('Procesos Sustantivos') THEN N'Sustantivo'
            ELSE tipo_proceso END
     WHERE tipo_proceso IN ('Estrategico','Procesos Estratégicos','Asesoria','Procesos de Asesoría','Procesos Adjetivos','Procesos Gobernantes','Procesos Sustantivos');

    IF NOT EXISTS(SELECT 1 FROM sys.check_constraints WHERE name='CK_th_estudio_hijos_orden')
        ALTER TABLE dbo.th_estudio_hijos WITH CHECK ADD CONSTRAINT CK_th_estudio_hijos_orden CHECK(orden BETWEEN 1 AND 3);
    IF NOT EXISTS(SELECT 1 FROM sys.check_constraints WHERE name='CK_th_estudio_capacitaciones_orden')
        ALTER TABLE dbo.th_estudio_capacitaciones WITH CHECK ADD CONSTRAINT CK_th_estudio_capacitaciones_orden CHECK(orden BETWEEN 1 AND 3);
    IF NOT EXISTS(SELECT 1 FROM sys.check_constraints WHERE name='CK_th_estudio_experiencias_orden')
        ALTER TABLE dbo.th_estudio_experiencias WITH CHECK ADD CONSTRAINT CK_th_estudio_experiencias_orden CHECK(orden BETWEEN 1 AND 3);
    IF NOT EXISTS(SELECT 1 FROM sys.check_constraints WHERE name='CK_th_estudios_vehiculo_valor')
        ALTER TABLE dbo.th_estudios_socioeconomicos WITH CHECK ADD CONSTRAINT CK_th_estudios_vehiculo_valor CHECK(vehiculo_valor IS NULL OR vehiculo_valor>=0);

    IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_estudios_socioeconomicos') AND name='UX_th_estudios_empleado_vigente')
        CREATE UNIQUE INDEX UX_th_estudios_empleado_vigente ON dbo.th_estudios_socioeconomicos(empleado_id) WHERE estado=1;
    IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_logs_auditoria') AND name='IX_th_logs_fecha_log')
        CREATE INDEX IX_th_logs_fecha_log ON dbo.th_logs_auditoria(fecha_hora DESC,log_id DESC) INCLUDE(usuario,modulo,accion,direccion_ip);

    IF DATABASE_PRINCIPAL_ID('portal_app_role') IS NOT NULL
    BEGIN
        GRANT INSERT,UPDATE ON dbo.th_roles TO portal_app_role;
        GRANT INSERT ON dbo.th_permisos_rol TO portal_app_role;
        GRANT SELECT ON dbo.th_schema_migrations TO portal_app_role;
    END;

    IF NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.06')
        INSERT dbo.th_schema_migrations(version,nombre_archivo)
        VALUES('2026.08.06','migracion_cierre_produccion_20260806.sql');

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT>0 ROLLBACK;
    THROW;
END CATCH;
GO

SELECT version,nombre_archivo,fecha_aplicacion,aplicado_por
FROM dbo.th_schema_migrations
ORDER BY migration_id;
GO
