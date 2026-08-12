/*
  Portal Portuario APM - Migracion critica 2026
  Base objetivo: Talento_Humano

  Caracteristicas:
  - Idempotente: puede ejecutarse nuevamente.
  - No elimina datos: las bajas de maestros son logicas (activo 1/0).
  - Las lecturas auditadas deben consumirse mediante los SP sp_th_consultar_*.
*/
USE [Talento_Humano];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET XACT_ABORT ON;
GO

/* --------------------------------------------------------------------------
   1. Estructura para movimientos internos sin Accion de Personal
   -------------------------------------------------------------------------- */
IF OBJECT_ID(N'dbo.th_movimientos_personal', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.th_movimientos_personal (
        movimiento_id       INT IDENTITY(1,1) NOT NULL
            CONSTRAINT PK_th_movimientos_personal PRIMARY KEY,
        empleado_id         INT NOT NULL,
        unidad_origen_id    INT NULL,
        puesto_origen_id    INT NULL,
        unidad_destino_id   INT NOT NULL,
        puesto_destino_id   INT NOT NULL,
        fecha_movimiento    DATE NOT NULL,
        motivo              VARCHAR(500) NOT NULL,
        usuario_crea        VARCHAR(50) NOT NULL,
        direccion_ip        VARCHAR(45) NOT NULL CONSTRAINT DF_th_mov_ip DEFAULT ('0.0.0.0'),
        fecha_creacion      DATETIME2(3) NOT NULL CONSTRAINT DF_th_mov_fecha DEFAULT (SYSDATETIME()),
        CONSTRAINT FK_th_mov_empleado FOREIGN KEY (empleado_id) REFERENCES dbo.th_empleados(empleado_id),
        CONSTRAINT FK_th_mov_unidad_origen FOREIGN KEY (unidad_origen_id) REFERENCES dbo.th_unidades_organizacionales(unidad_id),
        CONSTRAINT FK_th_mov_puesto_origen FOREIGN KEY (puesto_origen_id) REFERENCES dbo.th_puestos(puesto_id),
        CONSTRAINT FK_th_mov_unidad_destino FOREIGN KEY (unidad_destino_id) REFERENCES dbo.th_unidades_organizacionales(unidad_id),
        CONSTRAINT FK_th_mov_puesto_destino FOREIGN KEY (puesto_destino_id) REFERENCES dbo.th_puestos(puesto_id)
    );

    CREATE INDEX IX_th_mov_empleado_fecha
        ON dbo.th_movimientos_personal(empleado_id, fecha_movimiento DESC, movimiento_id DESC);
END;
GO

/* --------------------------------------------------------------------------
   2. Vistas normalizadas (las aplicaciones consumen sus SP auditados)
   -------------------------------------------------------------------------- */
CREATE OR ALTER VIEW dbo.vw_th_directorio_empleados
AS
SELECT
    e.empleado_id AS id,
    e.empleado_id,
    e.identificacion AS cedula,
    e.apellidos,
    e.nombres,
    LTRIM(RTRIM(CONCAT(e.apellidos, ' ', e.nombres))) AS apellidos_nombres,
    e.unidad_id,
    e.puesto_id,
    ISNULL(p.nombre_puesto, '') AS cargo,
    ISNULL(u.nombre_unidad, '') AS direccion_area,
    e.correo_institucional,
    e.estado,
    ISNULL(e.cargas_familiares, 0) AS cargas_familiares,
    e.tipo_cuenta_bancaria,
    e.numero_cuenta_bancaria,
    e.institucion_bancaria,
    e.tipo_contrato,
    e.sueldo_rmu AS remuneracion_mensual,
    e.sueldo_rmu,
    e.fecha_ingreso,
    e.fecha_nacimiento,
    e.telefono_movil,
    e.ciudad_residencia,
    e.ruta_foto
FROM dbo.th_empleados e
LEFT JOIN dbo.th_puestos p ON p.puesto_id = e.puesto_id
LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id = e.unidad_id;
GO

CREATE OR ALTER VIEW dbo.view_th_iddatosempledo
AS
SELECT * FROM dbo.vw_th_directorio_empleados;
GO

CREATE OR ALTER VIEW dbo.vw_th_maestros_organizacionales
AS
SELECT
    u.unidad_id,
    u.codigo_uorg,
    u.nombre_unidad,
    CASE WHEN u.unidad_padre_id IS NULL THEN 'Direccion' ELSE 'Area' END AS tipo_unidad,
    u.tipo_proceso,
    u.unidad_padre_id,
    padre.nombre_unidad AS direccion_padre,
    CONVERT(BIT, ISNULL(u.activo, 0)) AS activo
FROM dbo.th_unidades_organizacionales u
LEFT JOIN dbo.th_unidades_organizacionales padre ON padre.unidad_id = u.unidad_padre_id;
GO

CREATE OR ALTER VIEW dbo.vw_th_movimientos_personal
AS
SELECT
    m.movimiento_id,
    m.empleado_id,
    e.identificacion AS cedula,
    LTRIM(RTRIM(CONCAT(e.apellidos, ' ', e.nombres))) AS funcionario,
    m.fecha_movimiento,
    uo.nombre_unidad AS unidad_origen,
    po.nombre_puesto AS puesto_origen,
    ud.nombre_unidad AS unidad_destino,
    pd.nombre_puesto AS puesto_destino,
    m.motivo,
    m.usuario_crea,
    m.direccion_ip,
    m.fecha_creacion
FROM dbo.th_movimientos_personal m
JOIN dbo.th_empleados e ON e.empleado_id = m.empleado_id
LEFT JOIN dbo.th_unidades_organizacionales uo ON uo.unidad_id = m.unidad_origen_id
LEFT JOIN dbo.th_puestos po ON po.puesto_id = m.puesto_origen_id
JOIN dbo.th_unidades_organizacionales ud ON ud.unidad_id = m.unidad_destino_id
JOIN dbo.th_puestos pd ON pd.puesto_id = m.puesto_destino_id;
GO

/* --------------------------------------------------------------------------
   3. Auditoria central y consultas auditadas
   -------------------------------------------------------------------------- */
CREATE OR ALTER PROCEDURE dbo.sp_th_registrar_auditoria
    @usuario     VARCHAR(50),
    @modulo      VARCHAR(50),
    @accion      VARCHAR(50),
    @descripcion VARCHAR(500),
    @ip          VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    INSERT dbo.th_logs_auditoria
        (fecha_hora, usuario, modulo, accion, descripcion_detalle, direccion_ip)
    VALUES
        (GETDATE(), LEFT(COALESCE(NULLIF(@usuario, ''), 'ANONIMO'), 50),
         LEFT(@modulo, 50), LEFT(@accion, 50), LEFT(@descripcion, 500),
         LEFT(COALESCE(NULLIF(@ip, ''), '0.0.0.0'), 45));
END;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.th_usuarios_sistema WHERE usuario='admin_apm')
BEGIN
    INSERT dbo.th_usuarios_sistema
        (usuario,password_hash,correo,nombre,empleado_id,rol_id,estado,fecha_creacion)
    SELECT 'admin_apm',
           '$2y$10$tYaa/tz5gT5ccWBThckxru1SoQbuSX7Wb19YGrV1wLIC6b312nkwO',
           'admin@apm.gob.ec', 'Administrador APM', NULL, rol_id, 1, GETDATE()
    FROM dbo.th_roles WHERE nombre_rol='Super Administrador';

    EXEC dbo.sp_th_registrar_auditoria 'MIGRACION_2026','Sistema','CREAR_USUARIO',
         'Se creo el usuario inicial admin_apm. La clave temporal debe cambiarse al entregar el sistema.','127.0.0.1';
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_consultar_directorio
    @usuario VARCHAR(50),
    @ip      VARCHAR(45) = '0.0.0.0',
    @estado  INT = NULL
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria
        @usuario, 'Directorio de Personal', 'CONSULTAR_VISTA',
        'Consulta de vw_th_directorio_empleados', @ip;

    SELECT *
    FROM dbo.vw_th_directorio_empleados
    WHERE @estado IS NULL OR estado = @estado
    ORDER BY apellidos, nombres;
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_consultar_historial
    @usuario     VARCHAR(50),
    @ip          VARCHAR(45) = '0.0.0.0',
    @cargo       VARCHAR(150) = NULL,
    @empleado_id INT = NULL
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria
        @usuario, 'Historial Laboral', 'CONSULTAR_VISTA',
        'Consulta de vw_th_reporte_historial_jerarquico', @ip;

    SELECT *
    FROM dbo.vw_th_reporte_historial_jerarquico
    WHERE (@cargo IS NULL OR nombre_puesto LIKE '%' + @cargo + '%')
      AND (@empleado_id IS NULL OR empleado_id = @empleado_id)
    ORDER BY tipo_proceso DESC, direccion_actual_unificada, funcionario, fecha_desde;
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_consultar_acciones
    @usuario VARCHAR(50),
    @ip      VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria
        @usuario, 'Accion de Personal', 'CONSULTAR_VISTA',
        'Consulta de vw_th_acciones_resumen', @ip;

    SELECT * FROM dbo.vw_th_acciones_resumen ORDER BY accion_id DESC;
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_consultar_unidades
    @usuario VARCHAR(50), @ip VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario, 'Maestros Organizacionales', 'CONSULTAR_VISTA',
         'Consulta de vw_th_maestros_organizacionales', @ip;
    SELECT * FROM dbo.vw_th_maestros_organizacionales
    ORDER BY CASE WHEN unidad_padre_id IS NULL THEN unidad_id ELSE unidad_padre_id END,
             CASE WHEN unidad_padre_id IS NULL THEN 0 ELSE 1 END, nombre_unidad;
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_consultar_puestos
    @usuario VARCHAR(50), @ip VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    EXEC dbo.sp_th_registrar_auditoria @usuario, 'Denominaciones de Puestos', 'CONSULTAR_VISTA',
         'Consulta de th_puestos', @ip;
    SELECT puesto_id,codigo_puesto,nombre_puesto,remuneracion_unificada,CONVERT(BIT,ISNULL(activo,0)) activo
    FROM dbo.th_puestos ORDER BY nombre_puesto;
END;
GO

/* --------------------------------------------------------------------------
   4. CRUD de Direcciones, Areas y denominaciones de puestos
   -------------------------------------------------------------------------- */
CREATE OR ALTER PROCEDURE dbo.sp_th_guardar_unidad
    @unidad_id       INT = NULL,
    @nombre          VARCHAR(150),
    @tipo_proceso    VARCHAR(50),
    @unidad_padre_id INT = NULL,
    @activo          BIT = 1,
    @usuario         VARCHAR(50),
    @ip              VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;

    BEGIN TRY
        BEGIN TRAN;

        SET @nombre = UPPER(LTRIM(RTRIM(@nombre)));
        IF @nombre = '' THROW 51001, 'El nombre de la unidad es obligatorio.', 1;
        IF @unidad_padre_id = @unidad_id THROW 51002, 'Una unidad no puede ser su propio padre.', 1;
        IF @unidad_padre_id IS NOT NULL
           AND NOT EXISTS (SELECT 1 FROM dbo.th_unidades_organizacionales WHERE unidad_id = @unidad_padre_id AND activo = 1)
            THROW 51003, 'La direccion padre no existe o esta inactiva.', 1;
        IF EXISTS (
            SELECT 1 FROM dbo.th_unidades_organizacionales
            WHERE UPPER(LTRIM(RTRIM(nombre_unidad))) = @nombre
              AND ISNULL(unidad_padre_id, 0) = ISNULL(@unidad_padre_id, 0)
              AND unidad_id <> ISNULL(@unidad_id, 0)
        ) THROW 51004, 'Ya existe una unidad con ese nombre en el mismo nivel.', 1;

        DECLARE @accion VARCHAR(20);
        IF @unidad_id IS NULL OR @unidad_id = 0
        BEGIN
            INSERT dbo.th_unidades_organizacionales
                (codigo_uorg, nombre_unidad, tipo_proceso, unidad_padre_id, activo, fecha_inicio)
            VALUES
                (CONCAT('TMP-', RIGHT(REPLACE(CONVERT(VARCHAR(36), NEWID()), '-', ''), 16)), @nombre, @tipo_proceso, @unidad_padre_id, @activo, CONVERT(DATE, GETDATE()));
            SET @unidad_id = CONVERT(INT, SCOPE_IDENTITY());
            UPDATE dbo.th_unidades_organizacionales
            SET codigo_uorg = CONCAT(CASE WHEN @unidad_padre_id IS NULL THEN 'DIR-' ELSE 'ARE-' END,
                                     RIGHT('000000' + CONVERT(VARCHAR(10), @unidad_id), 6))
            WHERE unidad_id = @unidad_id;
            SET @accion = 'CREAR';
        END
        ELSE
        BEGIN
            UPDATE dbo.th_unidades_organizacionales
            SET nombre_unidad = @nombre,
                tipo_proceso = @tipo_proceso,
                unidad_padre_id = @unidad_padre_id,
                activo = @activo,
                fecha_fin = CASE WHEN @activo = 0 THEN COALESCE(fecha_fin, CONVERT(DATE, GETDATE())) ELSE NULL END
            WHERE unidad_id = @unidad_id;
            IF @@ROWCOUNT = 0 THROW 51005, 'La unidad indicada no existe.', 1;
            SET @accion = 'ACTUALIZAR';
        END;

        DECLARE @detalle_auditoria VARCHAR(500) = CONCAT('Unidad #', @unidad_id, ': ', @nombre, '; estado=', @activo);
        EXEC dbo.sp_th_registrar_auditoria @usuario, 'Maestros Organizacionales', @accion,
             @detalle_auditoria, @ip;
        COMMIT;
        SELECT 1 AS exito, @unidad_id AS id, 'Unidad guardada correctamente.' AS mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;
        SELECT 0 AS exito, ISNULL(@unidad_id, 0) AS id, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_guardar_puesto
    @puesto_id     INT = NULL,
    @nombre        VARCHAR(150),
    @remuneracion  DECIMAL(10,2) = 0,
    @activo        BIT = 1,
    @usuario       VARCHAR(50),
    @ip            VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        SET @nombre = UPPER(LTRIM(RTRIM(@nombre)));
        IF @nombre = '' THROW 51011, 'La denominacion del puesto es obligatoria.', 1;
        IF @remuneracion < 0 THROW 51012, 'La remuneracion no puede ser negativa.', 1;
        IF EXISTS (SELECT 1 FROM dbo.th_puestos
                   WHERE UPPER(LTRIM(RTRIM(nombre_puesto))) = @nombre
                     AND puesto_id <> ISNULL(@puesto_id, 0))
            THROW 51013, 'Ya existe una denominacion con ese nombre.', 1;

        DECLARE @accion VARCHAR(20);
        IF @puesto_id IS NULL OR @puesto_id = 0
        BEGIN
            INSERT dbo.th_puestos(codigo_puesto, nombre_puesto, remuneracion_unificada, activo)
            VALUES (CONCAT('TMP-', RIGHT(REPLACE(CONVERT(VARCHAR(36), NEWID()), '-', ''), 16)), @nombre, @remuneracion, @activo);
            SET @puesto_id = CONVERT(INT, SCOPE_IDENTITY());
            UPDATE dbo.th_puestos
            SET codigo_puesto = CONCAT('PST-', RIGHT('000000' + CONVERT(VARCHAR(10), @puesto_id), 6))
            WHERE puesto_id = @puesto_id;
            SET @accion = 'CREAR';
        END
        ELSE
        BEGIN
            UPDATE dbo.th_puestos
            SET nombre_puesto = @nombre,
                remuneracion_unificada = @remuneracion,
                activo = @activo
            WHERE puesto_id = @puesto_id;
            IF @@ROWCOUNT = 0 THROW 51014, 'El puesto indicado no existe.', 1;
            SET @accion = 'ACTUALIZAR';
        END;

        DECLARE @detalle_auditoria VARCHAR(500) = CONCAT('Puesto #', @puesto_id, ': ', @nombre, '; estado=', @activo);
        EXEC dbo.sp_th_registrar_auditoria @usuario, 'Denominaciones de Puestos', @accion,
             @detalle_auditoria, @ip;
        COMMIT;
        SELECT 1 AS exito, @puesto_id AS id, 'Denominacion guardada correctamente.' AS mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;
        SELECT 0 AS exito, ISNULL(@puesto_id, 0) AS id, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO

/* --------------------------------------------------------------------------
   5. Empleados: nombres/apellidos correctos y auditoria de escritura
   -------------------------------------------------------------------------- */
CREATE OR ALTER PROCEDURE dbo.sp_th_guardar_empleado
    @cedula VARCHAR(20), @apellidos VARCHAR(100), @nombres VARCHAR(100),
    @fecha_nac DATE = NULL, @condicion VARCHAR(50) = 'Ninguna',
    @tipo_disc VARCHAR(80) = NULL, @porcentaje_disc DECIMAL(5,2) = NULL,
    @sexo CHAR(1) = NULL, @estado_civil VARCHAR(30) = NULL,
    @nacionalidad VARCHAR(50) = NULL, @tipo_sangre VARCHAR(10) = NULL,
    @depto INT = NULL, @puesto INT = NULL, @tipo_contrato VARCHAR(100) = NULL,
    @fecha_ing DATE = NULL, @sueldo DECIMAL(10,2) = NULL,
    @jornada VARCHAR(30) = 'Completa', @correo VARCHAR(100) = NULL,
    @celular VARCHAR(20) = NULL, @convencional VARCHAR(20) = NULL,
    @ciudad VARCHAR(50) = NULL, @direccion VARCHAR(MAX) = NULL,
    @contacto_emerg VARCHAR(150) = NULL, @parentesco VARCHAR(50) = NULL,
    @tel_emerg VARCHAR(20) = NULL, @nivel_estudio VARCHAR(80) = NULL,
    @titulo VARCHAR(150) = NULL, @iess VARCHAR(30) = NULL,
    @foto VARCHAR(300) = 'public/img/default_avatar.png', @obs VARCHAR(MAX) = NULL,
    @usuario VARCHAR(50) = 'SISTEMA', @ip VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF NULLIF(LTRIM(RTRIM(@apellidos)), '') IS NULL OR NULLIF(LTRIM(RTRIM(@nombres)), '') IS NULL
            THROW 51021, 'Nombres y apellidos son obligatorios y deben enviarse por separado.', 1;
        IF EXISTS (SELECT 1 FROM dbo.th_empleados WHERE identificacion = @cedula)
            THROW 51022, 'Ya existe un empleado con esa identificacion.', 1;

        INSERT dbo.th_empleados (
            identificacion, apellidos, nombres, fecha_nacimiento, sexo, estado_civil, nacionalidad,
            unidad_id, puesto_id, fecha_ingreso, sueldo_rmu, correo_institucional, telefono_movil,
            telefono_convencional, ciudad_residencia, direccion_domiciliaria, codigo_iess, ruta_foto,
            observaciones, tipo_contrato, jornada, condicion_especial, tipo_discapacidad,
            porcentaje_discapacidad, tipo_sangre, contacto_emergencia, emergencia_relacion,
            tel_emergencia, nivel_estudio, titulo, estado, cargas_familiares, fecha_creacion
        ) VALUES (
            @cedula, UPPER(LTRIM(RTRIM(@apellidos))), UPPER(LTRIM(RTRIM(@nombres))), @fecha_nac,
            @sexo, @estado_civil, @nacionalidad, @depto, @puesto, @fecha_ing, @sueldo,
            ISNULL(@correo, ''), ISNULL(@celular, ''), @convencional, ISNULL(@ciudad, ''),
            ISNULL(@direccion, ''), @iess, ISNULL(@foto, 'public/img/default_avatar.png'), @obs,
            @tipo_contrato, @jornada, @condicion, @tipo_disc, @porcentaje_disc, @tipo_sangre,
            @contacto_emerg, @parentesco, @tel_emerg, @nivel_estudio, @titulo, 1, 0, GETDATE()
        );
        DECLARE @nuevo_id INT = CONVERT(INT, SCOPE_IDENTITY());
        DECLARE @detalle_auditoria VARCHAR(500) = CONCAT('Empleado #', @nuevo_id, ' C.I. ', @cedula, ' registrado.');
        EXEC dbo.sp_th_registrar_auditoria @usuario, 'Directorio de Personal', 'CREAR',
             @detalle_auditoria, @ip;
        COMMIT;
        SELECT @nuevo_id AS nuevo_id, 1 AS exito, 'Empleado guardado correctamente.' AS mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;
        SELECT 0 AS nuevo_id, 0 AS exito, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_modificar_empleado
    @id INT, @cedula VARCHAR(20), @apellidos VARCHAR(100), @nombres VARCHAR(100),
    @fecha_nac DATE = NULL, @condicion VARCHAR(50) = 'Ninguna',
    @tipo_disc VARCHAR(80) = NULL, @porcentaje_disc DECIMAL(5,2) = NULL,
    @sexo CHAR(1) = NULL, @estado_civil VARCHAR(30) = NULL,
    @nacionalidad VARCHAR(50) = NULL, @tipo_sangre VARCHAR(10) = NULL,
    @depto INT = NULL, @puesto INT = NULL, @tipo_contrato VARCHAR(100) = NULL,
    @fecha_ing DATE = NULL, @sueldo DECIMAL(10,2) = NULL,
    @jornada VARCHAR(30) = 'Completa', @correo VARCHAR(100) = NULL,
    @celular VARCHAR(20) = NULL, @convencional VARCHAR(20) = NULL,
    @ciudad VARCHAR(50) = NULL, @direccion VARCHAR(MAX) = NULL,
    @contacto_emerg VARCHAR(150) = NULL, @parentesco VARCHAR(50) = NULL,
    @tel_emerg VARCHAR(20) = NULL, @nivel_estudio VARCHAR(80) = NULL,
    @titulo VARCHAR(150) = NULL, @iess VARCHAR(30) = NULL,
    @foto VARCHAR(300) = NULL, @obs VARCHAR(MAX) = NULL,
    @usuario VARCHAR(50) = 'SISTEMA', @ip VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF NULLIF(LTRIM(RTRIM(@apellidos)), '') IS NULL OR NULLIF(LTRIM(RTRIM(@nombres)), '') IS NULL
            THROW 51023, 'Nombres y apellidos son obligatorios.', 1;
        IF EXISTS (SELECT 1 FROM dbo.th_empleados WHERE identificacion = @cedula AND empleado_id <> @id)
            THROW 51024, 'La identificacion pertenece a otro empleado.', 1;

        UPDATE dbo.th_empleados SET
            identificacion=@cedula, apellidos=UPPER(LTRIM(RTRIM(@apellidos))), nombres=UPPER(LTRIM(RTRIM(@nombres))),
            fecha_nacimiento=@fecha_nac, sexo=@sexo, estado_civil=@estado_civil, nacionalidad=@nacionalidad,
            unidad_id=@depto, puesto_id=@puesto, fecha_ingreso=@fecha_ing, sueldo_rmu=@sueldo,
            correo_institucional=ISNULL(@correo, correo_institucional), telefono_movil=ISNULL(@celular, telefono_movil),
            telefono_convencional=@convencional, ciudad_residencia=ISNULL(@ciudad, ciudad_residencia),
            direccion_domiciliaria=ISNULL(@direccion, direccion_domiciliaria), codigo_iess=@iess,
            ruta_foto=ISNULL(@foto, ruta_foto), observaciones=@obs, tipo_contrato=@tipo_contrato,
            jornada=@jornada, condicion_especial=@condicion, tipo_discapacidad=@tipo_disc,
            porcentaje_discapacidad=@porcentaje_disc, tipo_sangre=@tipo_sangre,
            contacto_emergencia=@contacto_emerg, emergencia_relacion=@parentesco,
            tel_emergencia=@tel_emerg, nivel_estudio=@nivel_estudio, titulo=@titulo
        WHERE empleado_id=@id;
        IF @@ROWCOUNT = 0 THROW 51025, 'El empleado indicado no existe.', 1;
        DECLARE @detalle_auditoria VARCHAR(500) = CONCAT('Empleado #', @id, ' C.I. ', @cedula, ' actualizado.');
        EXEC dbo.sp_th_registrar_auditoria @usuario, 'Directorio de Personal', 'ACTUALIZAR',
             @detalle_auditoria, @ip;
        COMMIT;
        SELECT 1 AS filas_afectadas, 1 AS exito, 'Empleado actualizado.' AS mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;
        SELECT 0 AS filas_afectadas, 0 AS exito, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO

CREATE OR ALTER PROCEDURE dbo.sp_th_eliminar_empleado
    @id INT, @usuario VARCHAR(50) = 'SISTEMA', @ip VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        UPDATE dbo.th_empleados SET estado = 0 WHERE empleado_id = @id AND estado <> 0;
        IF @@ROWCOUNT = 0 THROW 51026, 'El empleado no existe o ya se encuentra inactivo.', 1;
        DECLARE @detalle_auditoria VARCHAR(500) = CONCAT('Empleado #', @id, ' fue marcado inactivo.');
        EXEC dbo.sp_th_registrar_auditoria @usuario, 'Directorio de Personal', 'DAR_DE_BAJA',
             @detalle_auditoria, @ip;
        COMMIT;
        SELECT 1 AS filas_afectadas, 1 AS exito, 'Empleado dado de baja.' AS mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;
        SELECT 0 AS filas_afectadas, 0 AS exito, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO

/* --------------------------------------------------------------------------
   6. Accion de Personal con log atomico
   -------------------------------------------------------------------------- */
CREATE OR ALTER PROCEDURE dbo.sp_th_registrar_accion_personal
    @numero_accion VARCHAR(50), @empleado_id INT, @tipo_accion VARCHAR(100),
    @fecha_rige_desde DATE, @fecha_rige_hasta DATE = NULL, @explicacion_legal VARCHAR(MAX),
    @actual_unidad_id INT = NULL, @actual_puesto_id INT = NULL,
    @actual_lugar_trabajo VARCHAR(150) = NULL, @actual_remuneracion DECIMAL(10,2) = NULL,
    @propuesta_unidad_id INT = NULL, @propuesta_puesto_id INT = NULL,
    @propuesta_lugar_trabajo VARCHAR(150) = NULL, @propuesta_remuneracion DECIMAL(10,2) = NULL,
    @usuario VARCHAR(50), @ip VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        IF NOT EXISTS (SELECT 1 FROM dbo.th_empleados WHERE empleado_id=@empleado_id)
            THROW 51031, 'El empleado indicado no existe.', 1;
        IF EXISTS (SELECT 1 FROM dbo.th_acciones_personal WHERE numero_accion=@numero_accion)
            THROW 51032, 'El numero de Accion de Personal ya existe.', 1;

        INSERT dbo.th_acciones_personal (
            numero_accion, fecha_elaboracion, empleado_id, tipo_accion, fecha_rige_desde,
            fecha_rige_hasta, explicacion_legal, actual_unidad_id, actual_puesto_id,
            actual_lugar_trabajo, actual_remuneracion, propuesta_unidad_id,
            propuesta_puesto_id, propuesta_lugar_trabajo, propuesta_remuneracion,
            estado_documento, usuario_crea, fecha_creacion
        ) VALUES (
            @numero_accion, CONVERT(DATE, GETDATE()), @empleado_id, @tipo_accion, @fecha_rige_desde,
            @fecha_rige_hasta, @explicacion_legal, NULLIF(@actual_unidad_id,0), NULLIF(@actual_puesto_id,0),
            @actual_lugar_trabajo, @actual_remuneracion, NULLIF(@propuesta_unidad_id,0),
            NULLIF(@propuesta_puesto_id,0), @propuesta_lugar_trabajo, @propuesta_remuneracion,
            'Aprobado', @usuario, GETDATE()
        );
        DECLARE @accion_id INT = CONVERT(INT, SCOPE_IDENTITY());
        DECLARE @detalle_auditoria VARCHAR(500) = CONCAT('Genero ', @numero_accion, ' para empleado #', @empleado_id, '.');
        EXEC dbo.sp_th_registrar_auditoria @usuario, 'Accion de Personal', 'CREAR',
             @detalle_auditoria, @ip;
        COMMIT;
        SELECT 1 AS exito, @accion_id AS accion_id, 'Accion registrada y auditada.' AS mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;
        SELECT 0 AS exito, 0 AS accion_id, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO

/* --------------------------------------------------------------------------
   7. Movimiento interno: cambia asignacion sin documento legal
   -------------------------------------------------------------------------- */
CREATE OR ALTER PROCEDURE dbo.sp_th_mover_empleado
    @empleado_id INT, @unidad_destino_id INT, @puesto_destino_id INT,
    @fecha_movimiento DATE, @motivo VARCHAR(500),
    @usuario VARCHAR(50), @ip VARCHAR(45) = '0.0.0.0'
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;
    BEGIN TRY
        BEGIN TRAN;
        DECLARE @unidad_origen_id INT, @puesto_origen_id INT;
        SELECT @unidad_origen_id=unidad_id, @puesto_origen_id=puesto_id
        FROM dbo.th_empleados WITH (UPDLOCK, HOLDLOCK)
        WHERE empleado_id=@empleado_id AND estado=1;
        IF @unidad_origen_id IS NULL OR @puesto_origen_id IS NULL
            THROW 51041, 'El empleado no existe, esta inactivo o no tiene asignacion actual.', 1;
        IF NOT EXISTS (SELECT 1 FROM dbo.th_unidades_organizacionales WHERE unidad_id=@unidad_destino_id AND activo=1)
            THROW 51042, 'El area de destino no existe o esta inactiva.', 1;
        IF NOT EXISTS (SELECT 1 FROM dbo.th_puestos WHERE puesto_id=@puesto_destino_id AND activo=1)
            THROW 51043, 'El puesto de destino no existe o esta inactivo.', 1;
        IF @unidad_origen_id=@unidad_destino_id AND @puesto_origen_id=@puesto_destino_id
            THROW 51044, 'El destino coincide con la asignacion actual.', 1;
        IF NULLIF(LTRIM(RTRIM(@motivo)), '') IS NULL
            THROW 51045, 'El motivo del movimiento es obligatorio.', 1;

        UPDATE dbo.th_historial_laboral
        SET fecha_hasta=DATEADD(DAY,-1,@fecha_movimiento)
        WHERE empleado_id=@empleado_id AND fecha_hasta IS NULL;

        INSERT dbo.th_historial_laboral
            (empleado_id, puesto_id, unidad_id, fecha_desde, fecha_hasta, observaciones, usuario_crea, fecha_creacion)
        VALUES
            (@empleado_id, @puesto_destino_id, @unidad_destino_id, @fecha_movimiento, NULL,
             CONCAT('Movimiento interno sin Accion de Personal. ', LTRIM(RTRIM(@motivo))), @usuario, GETDATE());

        UPDATE dbo.th_empleados
        SET unidad_id=@unidad_destino_id, puesto_id=@puesto_destino_id
        WHERE empleado_id=@empleado_id;

        INSERT dbo.th_movimientos_personal
            (empleado_id, unidad_origen_id, puesto_origen_id, unidad_destino_id,
             puesto_destino_id, fecha_movimiento, motivo, usuario_crea, direccion_ip)
        VALUES
            (@empleado_id, @unidad_origen_id, @puesto_origen_id, @unidad_destino_id,
             @puesto_destino_id, @fecha_movimiento, LTRIM(RTRIM(@motivo)), @usuario, @ip);
        DECLARE @movimiento_id INT = CONVERT(INT, SCOPE_IDENTITY());

        DECLARE @detalle_auditoria VARCHAR(500) = CONCAT('Movimiento #', @movimiento_id, ' empleado #', @empleado_id,
                    ': unidad ', @unidad_origen_id, ' -> ', @unidad_destino_id,
                    '; puesto ', @puesto_origen_id, ' -> ', @puesto_destino_id, '.');
        EXEC dbo.sp_th_registrar_auditoria @usuario, 'Movimiento de Personal', 'MOVER',
             @detalle_auditoria, @ip;
        COMMIT;
        SELECT 1 AS exito, @movimiento_id AS movimiento_id, 'Movimiento interno registrado.' AS mensaje;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0 ROLLBACK;
        SELECT 0 AS exito, 0 AS movimiento_id, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO

/* --------------------------------------------------------------------------
   8. Reparacion puntual del registro afectado confirmado el 2026-07-27
   -------------------------------------------------------------------------- */
IF EXISTS (
    SELECT 1 FROM dbo.th_empleados
    WHERE identificacion='1316312766'
      AND LTRIM(RTRIM(apellidos))='MICHAEL JAVIER PALMA TEJENA'
      AND NULLIF(LTRIM(RTRIM(nombres)), '') IS NULL
)
BEGIN
    UPDATE dbo.th_empleados
    SET nombres='MICHAEL JAVIER', apellidos='PALMA TEJENA'
    WHERE identificacion='1316312766'
      AND LTRIM(RTRIM(apellidos))='MICHAEL JAVIER PALMA TEJENA'
      AND NULLIF(LTRIM(RTRIM(nombres)), '') IS NULL;

    EXEC dbo.sp_th_registrar_auditoria
        'MIGRACION_2026', 'Directorio de Personal', 'CORREGIR_NOMBRES',
        'Se separaron nombres y apellidos del empleado con C.I. 1316312766.', '127.0.0.1';
END;
GO

PRINT 'Migracion critica 2026 aplicada correctamente en Talento_Humano.';
GO
