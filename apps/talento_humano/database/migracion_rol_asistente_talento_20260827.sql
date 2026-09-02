SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET XACT_ABORT ON;
GO

/*
  Portal Portuario APM - Rol Asistente de Talento Humano
  Version    : 2026.08.27.2
  Objetivo   : Incorporar un perfil operativo de minimo privilegio para los
               asistentes de Talento Humano y conservar control total para el
               Super Administrador.
  Idempotente: Si.
*/

BEGIN TRY
    BEGIN TRAN;

    DECLARE @rol_asistente INT;

    SELECT @rol_asistente = rol_id
    FROM dbo.th_roles WITH (UPDLOCK, HOLDLOCK)
    WHERE nombre_rol = N'Asistente de Talento Humano';

    IF @rol_asistente IS NULL
    BEGIN
        INSERT dbo.th_roles(nombre_rol, estado)
        VALUES(N'Asistente de Talento Humano', 1);
        SET @rol_asistente = CONVERT(INT, SCOPE_IDENTITY());
    END
    ELSE
        UPDATE dbo.th_roles SET estado = 1 WHERE rol_id = @rol_asistente;

    INSERT dbo.th_permisos_rol(
        rol_id, modulo_id, puede_visualizar, puede_crear, puede_editar, puede_eliminar
    )
    SELECT @rol_asistente, m.modulo_id, 0, 0, 0, 0
    FROM dbo.th_modulos m
    WHERE NOT EXISTS(
        SELECT 1
        FROM dbo.th_permisos_rol p
        WHERE p.rol_id = @rol_asistente AND p.modulo_id = m.modulo_id
    );

    /*
      empleados y documentos_firmados son permisos contextuales: no agregan
      entradas al menu, pero permiten completar expedientes y legalizar los PDF.
    */
    UPDATE p
       SET puede_visualizar = CASE WHEN m.codigo_modulo IN(
               'dashboard','directorio','acciones','movimientos','socioeconomico',
               'vacaciones','paz_salvo','biblioteca','maestros','reportes',
               'empleados','documentos_firmados'
           ) THEN 1 ELSE 0 END,
           puede_crear = CASE WHEN m.codigo_modulo IN(
               'acciones','movimientos','socioeconomico','paz_salvo','maestros',
               'empleados','documentos_firmados'
           ) THEN 1 ELSE 0 END,
           puede_editar = CASE WHEN m.codigo_modulo IN(
               'acciones','socioeconomico','paz_salvo','maestros',
               'empleados','documentos_firmados'
           ) THEN 1 ELSE 0 END,
           puede_eliminar = 0
    FROM dbo.th_permisos_rol p
    JOIN dbo.th_modulos m ON m.modulo_id = p.modulo_id
    WHERE p.rol_id = @rol_asistente;

    DECLARE @rol_admin INT = (
        SELECT rol_id FROM dbo.th_roles WHERE nombre_rol = N'Super Administrador'
    );
    IF @rol_admin IS NULL
        THROW 52420, 'No existe el rol Super Administrador.', 1;

    INSERT dbo.th_permisos_rol(
        rol_id, modulo_id, puede_visualizar, puede_crear, puede_editar, puede_eliminar
    )
    SELECT @rol_admin, m.modulo_id, 1, 1, 1, 1
    FROM dbo.th_modulos m
    WHERE NOT EXISTS(
        SELECT 1 FROM dbo.th_permisos_rol p
        WHERE p.rol_id = @rol_admin AND p.modulo_id = m.modulo_id
    );

    UPDATE p
       SET puede_visualizar=1, puede_crear=1, puede_editar=1, puede_eliminar=1
    FROM dbo.th_permisos_rol p
    WHERE p.rol_id = @rol_admin;

    /* Cargos existentes autorizados, incluida la denominacion historica ASITENTE. */
    INSERT dbo.th_puesto_rol_mapa(puesto_id, rol_id, es_principal, usuario_crea)
    SELECT p.puesto_id, @rol_asistente, 1, 'MIGRACION_20260827'
    FROM dbo.th_puestos p
    WHERE p.activo = 1
      AND (
           UPPER(LTRIM(RTRIM(p.nombre_puesto))) LIKE '%ASISTENTE%TALENTO%HUMANO%'
           OR UPPER(LTRIM(RTRIM(p.nombre_puesto))) LIKE '%ASISTENTE%TTHH%'
           OR UPPER(LTRIM(RTRIM(p.nombre_puesto))) LIKE '%ASITENTE%TTHH%'
           OR UPPER(LTRIM(RTRIM(p.nombre_puesto))) LIKE '%ASISTENTE%RECURSOS%HUMANOS%'
      )
      AND NOT EXISTS(
          SELECT 1 FROM dbo.th_puesto_rol_mapa prm
          WHERE prm.puesto_id=p.puesto_id AND prm.rol_id=@rol_asistente
      );

    IF NOT EXISTS(
        SELECT 1
        FROM dbo.th_puesto_rol_mapa prm
        JOIN dbo.th_puestos p ON p.puesto_id=prm.puesto_id
        WHERE prm.rol_id=@rol_asistente AND p.activo=1
    )
        THROW 52421, 'No se encontro un cargo activo compatible con el rol Asistente de Talento Humano.', 1;

    IF OBJECT_ID('dbo.th_schema_migrations','U') IS NOT NULL
       AND NOT EXISTS(SELECT 1 FROM dbo.th_schema_migrations WHERE version='2026.08.27.2')
        INSERT dbo.th_schema_migrations(version,nombre_archivo)
        VALUES('2026.08.27.2','migracion_rol_asistente_talento_20260827.sql');

    EXEC dbo.sp_th_registrar_auditoria
        'MIGRACION', 'Seguridad RBAC', 'CREAR_ROL_ASISTENTE_TALENTO',
        'Se creo y valido el rol Asistente de Talento Humano con permisos de minimo privilegio.',
        '127.0.0.1';

    COMMIT;
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0 ROLLBACK;
    THROW;
END CATCH;
GO

SELECT r.rol_id,r.nombre_rol,r.estado,m.codigo_modulo,
       p.puede_visualizar,p.puede_crear,p.puede_editar,p.puede_eliminar
FROM dbo.th_roles r
JOIN dbo.th_permisos_rol p ON p.rol_id=r.rol_id
JOIN dbo.th_modulos m ON m.modulo_id=p.modulo_id
WHERE r.nombre_rol=N'Asistente de Talento Humano'
ORDER BY m.codigo_modulo;
GO
