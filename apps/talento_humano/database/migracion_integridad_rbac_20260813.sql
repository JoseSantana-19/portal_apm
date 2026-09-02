USE [Talento_Humano];
GO
SET NOCOUNT ON;
SET XACT_ABORT ON;
GO

BEGIN TRY
    BEGIN TRAN;

    UPDATE dbo.th_modulos
    SET codigo_modulo='maestros_periodos'
    WHERE codigo_modulo='maestros' AND nombre_modulo='Maestro de Periodos';

    UPDATE dbo.th_modulos
    SET codigo_modulo='maestros_titulos'
    WHERE codigo_modulo='maestros' AND nombre_modulo='Maestro de Títulos';

    DECLARE @modulo_canonico INT=(
        SELECT TOP(1) modulo_id
        FROM dbo.th_modulos
        WHERE codigo_modulo='maestros'
        ORDER BY CASE WHEN nombre_modulo='Maestro de Cargos' THEN 0 ELSE 1 END,modulo_id
    );
    IF @modulo_canonico IS NULL
        THROW 52100,'No existe un módulo maestro para consolidar.',1;

    DECLARE @permisos TABLE(
        rol_id INT PRIMARY KEY,
        puede_visualizar BIT,
        puede_crear BIT,
        puede_editar BIT,
        puede_eliminar BIT
    );
    INSERT @permisos
    SELECT p.rol_id,
           CONVERT(BIT,MAX(CONVERT(TINYINT,p.puede_visualizar))),
           CONVERT(BIT,MAX(CONVERT(TINYINT,p.puede_crear))),
           CONVERT(BIT,MAX(CONVERT(TINYINT,p.puede_editar))),
           CONVERT(BIT,MAX(CONVERT(TINYINT,p.puede_eliminar)))
    FROM dbo.th_permisos_rol p
    JOIN dbo.th_modulos m ON m.modulo_id=p.modulo_id
    WHERE m.codigo_modulo='maestros'
    GROUP BY p.rol_id;

    MERGE dbo.th_permisos_rol AS destino
    USING @permisos AS origen
      ON destino.rol_id=origen.rol_id AND destino.modulo_id=@modulo_canonico
    WHEN MATCHED THEN UPDATE SET
        puede_visualizar=origen.puede_visualizar,
        puede_crear=origen.puede_crear,
        puede_editar=origen.puede_editar,
        puede_eliminar=origen.puede_eliminar
    WHEN NOT MATCHED THEN INSERT(
        rol_id,modulo_id,puede_visualizar,puede_crear,puede_editar,puede_eliminar
    ) VALUES(
        origen.rol_id,@modulo_canonico,origen.puede_visualizar,origen.puede_crear,
        origen.puede_editar,origen.puede_eliminar
    );

    DELETE p
    FROM dbo.th_permisos_rol p
    JOIN dbo.th_modulos m ON m.modulo_id=p.modulo_id
    WHERE m.codigo_modulo='maestros' AND m.modulo_id<>@modulo_canonico;

    DELETE FROM dbo.th_modulos
    WHERE codigo_modulo='maestros' AND modulo_id<>@modulo_canonico;

    UPDATE dbo.th_modulos
    SET nombre_modulo='Estructura y cargos',ruta_frontend='admin/maestros',codigo_modulo='maestros'
    WHERE modulo_id=@modulo_canonico;

    UPDATE dbo.th_modulos
    SET codigo_modulo=CONCAT('modulo_',modulo_id)
    WHERE codigo_modulo IS NULL OR LTRIM(RTRIM(codigo_modulo))='';

    IF EXISTS(
        SELECT codigo_modulo FROM dbo.th_modulos
        GROUP BY codigo_modulo HAVING COUNT_BIG(*)>1
    ) THROW 52101,'Persisten códigos RBAC duplicados.',1;

    IF EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_modulos') AND name='IX_th_modulos_codigo')
        DROP INDEX IX_th_modulos_codigo ON dbo.th_modulos;
    IF NOT EXISTS(SELECT 1 FROM sys.indexes WHERE object_id=OBJECT_ID('dbo.th_modulos') AND name='UX_th_modulos_codigo')
        CREATE UNIQUE INDEX UX_th_modulos_codigo ON dbo.th_modulos(codigo_modulo);

    UPDATE u SET token_version=token_version+1
    FROM dbo.th_usuarios_sistema u
    WHERE EXISTS(SELECT 1 FROM @permisos p WHERE p.rol_id=u.rol_id);

    EXEC dbo.sp_th_registrar_auditoria
        'MIGRACION','Seguridad RBAC','CONSOLIDAR_MODULOS',
        'Se normalizaron códigos únicos y se consolidó Estructura y cargos.',
        '127.0.0.1';

    IF NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.13')
        INSERT dbo.th_schema_migrations(version,nombre_archivo)
        VALUES('2026.08.13','migracion_integridad_rbac_20260813.sql');

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT>0 ROLLBACK;
    THROW;
END CATCH;
GO

SELECT version,nombre_archivo,fecha_aplicacion,aplicado_por
FROM dbo.th_schema_migrations
WHERE version='2026.08.13';
GO
