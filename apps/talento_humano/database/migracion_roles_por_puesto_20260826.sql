USE [Talento_Humano];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET XACT_ABORT ON;
GO

/*
  Portal Portuario APM - Migracion roles por puesto 2026-08-26
  Objetivo  : Hacer que la creacion de usuarios en el Administrador APM
              derive el nombre de usuario de la cedula del funcionario y
              valide que el rol asignado corresponda al puesto registrado.
  Idempotente: Si, puede ejecutarse nuevamente sin datos duplicados.
*/

BEGIN TRY
    BEGIN TRAN;

    /* -----------------------------------------------------------------------
       1. Tabla de mapeo puesto -> rol(es) validos
       ----------------------------------------------------------------------- */
    IF OBJECT_ID(N'dbo.th_puesto_rol_mapa', N'U') IS NULL
    BEGIN
        CREATE TABLE dbo.th_puesto_rol_mapa (
            mapa_id      INT IDENTITY(1,1) NOT NULL
                CONSTRAINT PK_th_puesto_rol_mapa PRIMARY KEY,
            puesto_id    INT NOT NULL
                CONSTRAINT FK_prm_puesto REFERENCES dbo.th_puestos(puesto_id),
            rol_id       INT NOT NULL
                CONSTRAINT FK_prm_rol   REFERENCES dbo.th_roles(rol_id),
            es_principal BIT NOT NULL
                CONSTRAINT DF_prm_principal DEFAULT (1),
            fecha_crea   DATETIME2(0) NOT NULL
                CONSTRAINT DF_prm_fecha DEFAULT (SYSDATETIME()),
            usuario_crea VARCHAR(50) NOT NULL
                CONSTRAINT DF_prm_usuario DEFAULT ('MIGRACION'),
            CONSTRAINT UQ_puesto_rol UNIQUE (puesto_id, rol_id)
        );

        CREATE INDEX IX_prm_puesto ON dbo.th_puesto_rol_mapa(puesto_id);
        CREATE INDEX IX_prm_rol    ON dbo.th_puesto_rol_mapa(rol_id);
    END;

    /* -----------------------------------------------------------------------
       2. Datos iniciales: excepciones explicitas por puesto.
          Los puestos sin excepcion reciben Funcionario (Lectura) en los SP.
       ----------------------------------------------------------------------- */

    -- Director de Talento Humano
    INSERT INTO dbo.th_puesto_rol_mapa (puesto_id, rol_id, es_principal, usuario_crea)
    SELECT p.puesto_id, r.rol_id, 1, 'MIGRACION_2026'
    FROM dbo.th_puestos p
    CROSS JOIN dbo.th_roles r
    WHERE p.nombre_puesto LIKE '%Director%Talento%'
      AND r.nombre_rol    LIKE '%Director%Talento%'
      AND NOT EXISTS (
          SELECT 1 FROM dbo.th_puesto_rol_mapa x
          WHERE x.puesto_id = p.puesto_id AND x.rol_id = r.rol_id
      );

    -- Analistas de Talento Humano
    INSERT INTO dbo.th_puesto_rol_mapa (puesto_id, rol_id, es_principal, usuario_crea)
    SELECT p.puesto_id, r.rol_id, 1, 'MIGRACION_2026'
    FROM dbo.th_puestos p
    CROSS JOIN dbo.th_roles r
    WHERE p.nombre_puesto LIKE '%Analista%Talento%'
      AND r.nombre_rol    LIKE '%Analista%'
      AND NOT EXISTS (
          SELECT 1 FROM dbo.th_puesto_rol_mapa x
          WHERE x.puesto_id = p.puesto_id AND x.rol_id = r.rol_id
      );

    -- Super Administrador solo para puestos de TI
    INSERT INTO dbo.th_puesto_rol_mapa (puesto_id, rol_id, es_principal, usuario_crea)
    SELECT p.puesto_id, r.rol_id, 1, 'MIGRACION_2026'
    FROM dbo.th_puestos p
    CROSS JOIN dbo.th_roles r
    WHERE p.nombre_puesto LIKE '%Administrador%Sistema%'
      AND r.nombre_rol    = 'Super Administrador'
      AND NOT EXISTS (
          SELECT 1 FROM dbo.th_puesto_rol_mapa x
          WHERE x.puesto_id = p.puesto_id AND x.rol_id = r.rol_id
      );

    IF NOT EXISTS(SELECT 1 FROM dbo.th_roles WHERE nombre_rol='Funcionario (Lectura)' AND estado=1)
        THROW 52301, 'Falta el rol activo Funcionario (Lectura), requerido como rol seguro por defecto.', 1;

    IF EXISTS(
        SELECT 1 FROM dbo.th_puestos p
        WHERE p.activo=1 AND p.nombre_puesto LIKE '%Director%Talento%'
          AND NOT EXISTS(SELECT 1 FROM dbo.th_puesto_rol_mapa m WHERE m.puesto_id=p.puesto_id)
    )
        THROW 52302, 'Existen puestos de Direccion de Talento Humano sin rol compatible.', 1;

    IF EXISTS(
        SELECT 1 FROM dbo.th_puestos p
        WHERE p.activo=1 AND p.nombre_puesto LIKE '%Analista%Talento%'
          AND NOT EXISTS(SELECT 1 FROM dbo.th_puesto_rol_mapa m WHERE m.puesto_id=p.puesto_id)
    )
        THROW 52303, 'Existen puestos de Analista de Talento Humano sin rol compatible.', 1;

    /* -----------------------------------------------------------------------
       3. SP: Roles sugeridos para un empleado (consumido via PHP/AJAX)
       ----------------------------------------------------------------------- */
    EXEC(N'CREATE OR ALTER PROCEDURE dbo.sp_th_rol_sugerido_por_empleado
        @empleado_id INT
    AS
    BEGIN
        SET NOCOUNT ON;
        ;WITH RolesValidos AS(
            SELECT m.rol_id,m.es_principal
            FROM dbo.th_empleados e
            JOIN dbo.th_puesto_rol_mapa m ON m.puesto_id=e.puesto_id
            WHERE e.empleado_id=@empleado_id
            UNION ALL
            SELECT r.rol_id,CONVERT(BIT,1)
            FROM dbo.th_empleados e
            JOIN dbo.th_roles r ON r.nombre_rol=''Funcionario (Lectura)'' AND r.estado=1
            WHERE e.empleado_id=@empleado_id
              AND NOT EXISTS(SELECT 1 FROM dbo.th_puesto_rol_mapa m WHERE m.puesto_id=e.puesto_id)
        )
        SELECT r.rol_id,r.nombre_rol,v.es_principal,e.identificacion AS cedula
        FROM RolesValidos v
        JOIN dbo.th_roles r ON r.rol_id=v.rol_id AND r.estado=1
        CROSS JOIN dbo.th_empleados e
        WHERE e.empleado_id=@empleado_id
        ORDER BY v.es_principal DESC,r.nombre_rol;
    END;');

    /* -----------------------------------------------------------------------
       4. SP: Mapa completo puesto -> roles (precarga en vista admin)
       ----------------------------------------------------------------------- */
    EXEC(N'CREATE OR ALTER PROCEDURE dbo.sp_th_mapa_roles_puestos
    AS
    BEGIN
        SET NOCOUNT ON;
        ;WITH MapaCompleto AS(
            SELECT m.puesto_id,m.rol_id,m.es_principal
            FROM dbo.th_puesto_rol_mapa m
            UNION ALL
            SELECT p.puesto_id,r.rol_id,CONVERT(BIT,1)
            FROM dbo.th_puestos p
            JOIN dbo.th_roles r ON r.nombre_rol=''Funcionario (Lectura)'' AND r.estado=1
            WHERE p.activo=1
              AND NOT EXISTS(SELECT 1 FROM dbo.th_puesto_rol_mapa m WHERE m.puesto_id=p.puesto_id)
        )
        SELECT m.puesto_id,m.rol_id,m.es_principal,p.nombre_puesto,r.nombre_rol
        FROM MapaCompleto m
        JOIN dbo.th_puestos p ON p.puesto_id=m.puesto_id AND p.activo=1
        JOIN dbo.th_roles r ON r.rol_id=m.rol_id AND r.estado=1
        ORDER BY m.puesto_id,m.es_principal DESC,r.nombre_rol;
    END;');

    /* -----------------------------------------------------------------------
       5. SP actualizado: sp_th_crear_usuario_sistema con validacion puesto<->rol
       ----------------------------------------------------------------------- */
    EXEC(N'CREATE OR ALTER PROCEDURE dbo.sp_th_crear_usuario_sistema
        @usuario       NVARCHAR(50),
        @password_hash NVARCHAR(255),
        @correo        NVARCHAR(150),
        @nombre        NVARCHAR(150),
        @empleado_id   INT = NULL,
        @rol_id        INT
    AS
    BEGIN
        SET NOCOUNT ON;
        SET XACT_ABORT ON;

        IF NOT EXISTS(SELECT 1 FROM dbo.th_roles WHERE rol_id = @rol_id AND estado = 1)
            THROW 51001, ''El rol indicado no existe o se encuentra inactivo.'', 1;

        IF EXISTS(SELECT 1 FROM dbo.th_usuarios_sistema WHERE usuario = @usuario)
            THROW 51002, ''El nombre de usuario ya se encuentra registrado.'', 1;

        IF @empleado_id IS NOT NULL AND EXISTS(
            SELECT 1 FROM dbo.th_usuarios_sistema WHERE empleado_id = @empleado_id
        )
            THROW 51003, ''El funcionario ya tiene una cuenta de acceso.'', 1;

        IF @empleado_id IS NOT NULL
        BEGIN
            IF NOT EXISTS(SELECT 1 FROM dbo.th_empleados WHERE empleado_id=@empleado_id AND estado=1)
                THROW 51005, ''El funcionario indicado no existe o se encuentra inactivo.'', 1;

            DECLARE @puesto_id INT = (
                SELECT puesto_id FROM dbo.th_empleados WHERE empleado_id = @empleado_id
            );
            DECLARE @rol_valido BIT=0;
            IF EXISTS(SELECT 1 FROM dbo.th_puesto_rol_mapa WHERE puesto_id=@puesto_id)
                SET @rol_valido=CASE WHEN EXISTS(
                    SELECT 1 FROM dbo.th_puesto_rol_mapa WHERE puesto_id=@puesto_id AND rol_id=@rol_id
                ) THEN 1 ELSE 0 END;
            ELSE IF EXISTS(
                SELECT 1 FROM dbo.th_roles WHERE rol_id=@rol_id AND nombre_rol=''Funcionario (Lectura)'' AND estado=1
            )
                SET @rol_valido=1;

            IF @rol_valido=0
            BEGIN
                DECLARE @nombre_puesto NVARCHAR(150) = (
                    SELECT nombre_puesto FROM dbo.th_puestos WHERE puesto_id = @puesto_id
                );
                DECLARE @msg NVARCHAR(500) = CONCAT(
                    ''El rol seleccionado no corresponde al puesto "'',
                    ISNULL(@nombre_puesto, ''(sin puesto)''),
                    ''". Verifique la asignacion con el administrador del sistema.''
                );
                THROW 51004, @msg, 1;
            END
        END

        INSERT dbo.th_usuarios_sistema
            (usuario, password_hash, correo, nombre, empleado_id, rol_id,
             estado, fecha_creacion, debe_cambiar_clave)
        VALUES
            (@usuario, @password_hash, @correo, @nombre, @empleado_id, @rol_id,
             1, SYSDATETIME(), 1);

        SELECT CONVERT(INT, SCOPE_IDENTITY()) usuario_id;
    END;');

    /* -----------------------------------------------------------------------
       6. Permisos para portal_app_role
       ----------------------------------------------------------------------- */
    IF DATABASE_PRINCIPAL_ID('portal_app_role') IS NOT NULL
    BEGIN
        GRANT SELECT  ON dbo.th_puesto_rol_mapa                 TO portal_app_role;
        GRANT EXECUTE ON dbo.sp_th_rol_sugerido_por_empleado    TO portal_app_role;
        GRANT EXECUTE ON dbo.sp_th_mapa_roles_puestos           TO portal_app_role;
    END;

    /* -----------------------------------------------------------------------
       7. Registro en historial de migraciones
       ----------------------------------------------------------------------- */
    IF NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version = '2026.08.26')
        INSERT dbo.th_schema_migrations(version, nombre_archivo)
        VALUES('2026.08.26', 'migracion_roles_por_puesto_20260826.sql');

    EXEC dbo.sp_th_registrar_auditoria
        'MIGRACION_2026', 'Seguridad RBAC', 'CREAR_MAPA_PUESTO_ROL',
        'Se creo th_puesto_rol_mapa y se actualizo sp_th_crear_usuario_sistema con validacion de puesto.',
        '127.0.0.1';

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK;
    THROW;
END CATCH;
GO

-- Verificacion post-migracion
SELECT version, nombre_archivo, fecha_aplicacion, aplicado_por
FROM dbo.th_schema_migrations
WHERE version = '2026.08.26';
GO

SELECT p.nombre_puesto, r.nombre_rol, m.es_principal
FROM dbo.th_puesto_rol_mapa m
JOIN dbo.th_puestos p ON p.puesto_id = m.puesto_id
JOIN dbo.th_roles   r ON r.rol_id    = m.rol_id
ORDER BY p.nombre_puesto, m.es_principal DESC;
GO
