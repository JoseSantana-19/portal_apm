-- =============================================================================
-- PATCH v3 – OBJETOS FALTANTES: Portal Portuario APM
-- Base de datos : Talento_Humano
-- Instancia     : JAVIER
-- Ejecutar en   : SSMS o sqlcmd como usuario sa
-- Descripción   : Crea los objetos ausentes detectados en la auditoría técnica.
--                 Script idempotente – seguro para re-ejecución.
-- =============================================================================

USE [Talento_Humano];
GO

PRINT '=== PATCH v3 – Portal Portuario APM ===';
PRINT 'Iniciando creación de objetos faltantes...';
GO

-- =============================================================================
-- 1. TABLA th_historial_laboral
--    Registra cada período de asignación de un empleado a una unidad/puesto.
--    Requerida por: vw_th_reporte_historial_jerarquico, rectificacion_v2_APM.sql
-- =============================================================================
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'th_historial_laboral')
BEGIN
    CREATE TABLE [dbo].[th_historial_laboral] (
        historial_id    INT IDENTITY(1,1) PRIMARY KEY,
        empleado_id     INT          NOT NULL,
        puesto_id       INT          NOT NULL,
        unidad_id       INT          NOT NULL,
        fecha_desde     DATE         NOT NULL,
        fecha_hasta     DATE         NULL,   -- NULL = cargo actual vigente
        observaciones   VARCHAR(500) NULL,
        usuario_crea    VARCHAR(50)  NULL,
        fecha_creacion  DATETIME     DEFAULT GETDATE(),

        CONSTRAINT FK_Historial_Empleado FOREIGN KEY (empleado_id)
            REFERENCES [dbo].[th_empleados](empleado_id) ON UPDATE CASCADE ON DELETE NO ACTION,
        CONSTRAINT FK_Historial_Puesto   FOREIGN KEY (puesto_id)
            REFERENCES [dbo].[th_puestos](puesto_id)     ON UPDATE NO ACTION ON DELETE NO ACTION,
        CONSTRAINT FK_Historial_Unidad   FOREIGN KEY (unidad_id)
            REFERENCES [dbo].[th_unidades_organizacionales](unidad_id) ON UPDATE NO ACTION ON DELETE NO ACTION
    );

    CREATE INDEX IX_Historial_Empleado ON [dbo].[th_historial_laboral](empleado_id);
    CREATE INDEX IX_Historial_Unidad   ON [dbo].[th_historial_laboral](unidad_id);
    CREATE INDEX IX_Historial_Fechas   ON [dbo].[th_historial_laboral](fecha_desde, fecha_hasta);

    PRINT '✅ Tabla th_historial_laboral creada.';
END
ELSE PRINT '⚠️  th_historial_laboral ya existe — omitida.';
GO

-- =============================================================================
-- 2. TABLA th_parametros
--    Almacena configuraciones del sistema (RBU, tasas, valores legales).
--    Requerida por: EmpleadoModel::obtenerRbuVigente()
-- =============================================================================
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'th_parametros')
BEGIN
    CREATE TABLE [dbo].[th_parametros] (
        parametro_id    VARCHAR(50)  NOT NULL PRIMARY KEY,
        valor           VARCHAR(200) NOT NULL,
        descripcion     VARCHAR(300) NULL,
        fecha_vigencia  DATE         NOT NULL DEFAULT GETDATE(),
        usuario_crea    VARCHAR(50)  NULL,
        fecha_creacion  DATETIME     DEFAULT GETDATE()
    );

    -- Dato inicial: RBU Ecuador 2026
    INSERT INTO [dbo].[th_parametros] (parametro_id, valor, descripcion, fecha_vigencia, usuario_crea)
    VALUES
        ('RBU_2026',    '460.00',  'Remuneración Básica Unificada 2026 – Ecuador',   '2026-01-01', 'sistema'),
        ('IESS_APORTE', '11.45',   'Aporte personal IESS (%)',                        '2026-01-01', 'sistema'),
        ('IESS_PATRON', '12.15',   'Aporte patronal IESS (%)',                        '2026-01-01', 'sistema'),
        ('INSTITUCION', 'Autoridad Portuaria de Manta', 'Nombre de la institución',  '2026-01-01', 'sistema'),
        ('RUC_PATRON',  '1360034020001', 'RUC del empleador',                         '2026-01-01', 'sistema');

    PRINT '✅ Tabla th_parametros creada con datos iniciales.';
END
ELSE PRINT '⚠️  th_parametros ya existe — omitida.';
GO

-- =============================================================================
-- 3. TABLA th_usuarios_sistema
--    Usuarios del portal con autenticación y roles.
--    Requerida por: AdminController (actualmente con datos mock)
-- =============================================================================
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'th_usuarios_sistema')
BEGIN
    CREATE TABLE [dbo].[th_usuarios_sistema] (
        usuario_id      INT IDENTITY(1,1) PRIMARY KEY,
        usuario         VARCHAR(50)  NOT NULL UNIQUE,
        password_hash   VARCHAR(255) NOT NULL,
        correo          VARCHAR(100) NOT NULL,
        nombre          VARCHAR(150) NOT NULL,
        empleado_id     INT          NULL,
        rol_id          INT          NOT NULL,
        estado          BIT          DEFAULT 1,
        ultimo_acceso   DATETIME     NULL,
        fecha_creacion  DATETIME     DEFAULT GETDATE(),

        CONSTRAINT FK_Usuario_Rol      FOREIGN KEY (rol_id)
            REFERENCES [dbo].[th_roles](rol_id),
        CONSTRAINT FK_Usuario_Empleado FOREIGN KEY (empleado_id)
            REFERENCES [dbo].[th_empleados](empleado_id)
    );

    PRINT '✅ Tabla th_usuarios_sistema creada.';
END
ELSE PRINT '⚠️  th_usuarios_sistema ya existe — omitida.';
GO

-- =============================================================================
-- 4. TABLA th_logs_auditoria
--    Registro inmutable de todas las acciones del sistema.
--    Requerida por: AuditoriaController::logs()
-- =============================================================================
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'th_logs_auditoria')
BEGIN
    CREATE TABLE [dbo].[th_logs_auditoria] (
        log_id              INT IDENTITY(1,1) PRIMARY KEY,
        fecha_hora          DATETIME     DEFAULT GETDATE() NOT NULL,
        usuario             VARCHAR(50)  NOT NULL,
        modulo              VARCHAR(50)  NOT NULL,
        accion              VARCHAR(50)  NOT NULL,
        descripcion_detalle VARCHAR(500) NOT NULL,
        direccion_ip        VARCHAR(45)  NOT NULL DEFAULT '0.0.0.0'
        -- SIN ON DELETE CASCADE: los logs son INMUTABLES por diseño
    );

    CREATE INDEX IX_Logs_FechaHora ON [dbo].[th_logs_auditoria](fecha_hora DESC);
    CREATE INDEX IX_Logs_Usuario   ON [dbo].[th_logs_auditoria](usuario);
    CREATE INDEX IX_Logs_Modulo    ON [dbo].[th_logs_auditoria](modulo, accion);

    -- Log inicial del sistema
    INSERT INTO [dbo].[th_logs_auditoria] (usuario, modulo, accion, descripcion_detalle, direccion_ip)
    VALUES ('sistema', 'Sistema', 'INICIALIZAR',
            'Base de datos Talento_Humano inicializada – Patch v3 Portal Portuario APM', '127.0.0.1');

    PRINT '✅ Tabla th_logs_auditoria creada con log inicial.';
END
ELSE PRINT '⚠️  th_logs_auditoria ya existe — omitida.';
GO

-- =============================================================================
-- 5. Agregar columnas faltantes en th_unidades_organizacionales
--    Requeridas por: rectificacion_v2_APM.sql (fusiones organizacionales)
-- =============================================================================
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('th_unidades_organizacionales') AND name = 'sucedido_por_id'
)
BEGIN
    ALTER TABLE [dbo].[th_unidades_organizacionales] ADD sucedido_por_id INT NULL;
    ALTER TABLE [dbo].[th_unidades_organizacionales]
        ADD CONSTRAINT FK_Unidad_Sucedida
        FOREIGN KEY (sucedido_por_id)
        REFERENCES [dbo].[th_unidades_organizacionales](unidad_id);
    PRINT '✅ Columna sucedido_por_id agregada a th_unidades_organizacionales.';
END
ELSE PRINT '⚠️  sucedido_por_id ya existe — omitida.';
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('th_unidades_organizacionales') AND name = 'fecha_fin'
)
BEGIN
    ALTER TABLE [dbo].[th_unidades_organizacionales] ADD fecha_fin DATE NULL;
    PRINT '✅ Columna fecha_fin agregada a th_unidades_organizacionales.';
END
ELSE PRINT '⚠️  fecha_fin ya existe — omitida.';
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('th_unidades_organizacionales') AND name = 'fecha_inicio'
)
BEGIN
    ALTER TABLE [dbo].[th_unidades_organizacionales] ADD fecha_inicio DATE NULL;
    PRINT '✅ Columna fecha_inicio agregada a th_unidades_organizacionales.';
END
ELSE PRINT '⚠️  fecha_inicio ya existe — omitida.';
GO

-- =============================================================================
-- 6. Agregar columnas faltantes en th_empleados
--    Requeridas por: sp_th_guardar_empleado / EmpleadoModel (mapeo de parámetros)
-- =============================================================================

-- tipo_contrato — para el CSV export en EmpleadoController::exportarCsv()
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('th_empleados') AND name = 'tipo_contrato'
)
BEGIN
    ALTER TABLE [dbo].[th_empleados] ADD tipo_contrato VARCHAR(100) NULL;
    PRINT '✅ Columna tipo_contrato agregada a th_empleados.';
END
ELSE PRINT '⚠️  tipo_contrato ya existe — omitida.';
GO

-- cedula como alias de identificacion (usado en el modelo)
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('th_empleados') AND name = 'cedula'
)
BEGIN
    ALTER TABLE [dbo].[th_empleados] ADD cedula AS identificacion PERSISTED;
    PRINT '✅ Columna computada cedula (alias de identificacion) agregada.';
END
ELSE PRINT '⚠️  cedula ya existe — omitida.';
GO

-- remuneracion_mensual como alias de sueldo_rmu
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('th_empleados') AND name = 'remuneracion_mensual'
)
BEGIN
    ALTER TABLE [dbo].[th_empleados] ADD remuneracion_mensual AS sueldo_rmu PERSISTED;
    PRINT '✅ Columna computada remuneracion_mensual (alias de sueldo_rmu) agregada.';
END
ELSE PRINT '⚠️  remuneracion_mensual ya existe — omitida.';
GO

-- ruta_foto — requerida por EmpleadoModel::insertar() / _mapParams()
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('th_empleados') AND name = 'ruta_foto'
)
BEGIN
    ALTER TABLE [dbo].[th_empleados] ADD ruta_foto VARCHAR(300) NULL
        CONSTRAINT DF_Empleado_Foto DEFAULT 'public/img/default_avatar.png';
    PRINT '✅ Columna ruta_foto agregada a th_empleados.';
END
ELSE PRINT '⚠️  ruta_foto ya existe — omitida.';
GO

-- observaciones — campo libre de notas
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('th_empleados') AND name = 'observaciones'
)
BEGIN
    ALTER TABLE [dbo].[th_empleados] ADD observaciones VARCHAR(MAX) NULL;
    PRINT '✅ Columna observaciones agregada a th_empleados.';
END
ELSE PRINT '⚠️  observaciones ya existe — omitida.';
GO

-- telefono_convencional — requerido por _mapParams()
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('th_empleados') AND name = 'telefono_convencional'
)
BEGIN
    ALTER TABLE [dbo].[th_empleados] ADD telefono_convencional VARCHAR(20) NULL;
    PRINT '✅ Columna telefono_convencional agregada a th_empleados.';
END
ELSE PRINT '⚠️  telefono_convencional ya existe — omitida.';
GO

-- contacto_emergencia + parentesco + tel_emergencia — requeridos por _mapParams()
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('th_empleados') AND name = 'contacto_emergencia')
    ALTER TABLE [dbo].[th_empleados] ADD contacto_emergencia VARCHAR(150) NULL;
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('th_empleados') AND name = 'emergencia_relacion')
    ALTER TABLE [dbo].[th_empleados] ADD emergencia_relacion VARCHAR(50) NULL;
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('th_empleados') AND name = 'tel_emergencia')
    ALTER TABLE [dbo].[th_empleados] ADD tel_emergencia VARCHAR(20) NULL;
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('th_empleados') AND name = 'nivel_estudio')
    ALTER TABLE [dbo].[th_empleados] ADD nivel_estudio VARCHAR(80) NULL;
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('th_empleados') AND name = 'titulo')
    ALTER TABLE [dbo].[th_empleados] ADD titulo VARCHAR(150) NULL;
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('th_empleados') AND name = 'jornada')
    ALTER TABLE [dbo].[th_empleados] ADD jornada VARCHAR(30) NULL DEFAULT 'Completa';
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('th_empleados') AND name = 'condicion_especial')
    ALTER TABLE [dbo].[th_empleados] ADD condicion_especial VARCHAR(50) NULL DEFAULT 'Ninguna';
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('th_empleados') AND name = 'tipo_discapacidad')
    ALTER TABLE [dbo].[th_empleados] ADD tipo_discapacidad VARCHAR(80) NULL;
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('th_empleados') AND name = 'porcentaje_discapacidad')
    ALTER TABLE [dbo].[th_empleados] ADD porcentaje_discapacidad DECIMAL(5,2) NULL;
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('th_empleados') AND name = 'tipo_sangre')
    ALTER TABLE [dbo].[th_empleados] ADD tipo_sangre VARCHAR(10) NULL;
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('th_empleados') AND name = 'fecha_salida')
    ALTER TABLE [dbo].[th_empleados] ADD fecha_salida DATE NULL;
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('th_empleados') AND name = 'cod_emplea')
    ALTER TABLE [dbo].[th_empleados] ADD cod_emplea VARCHAR(20) NULL;
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('th_empleados') AND name = 'num_iess')
    ALTER TABLE [dbo].[th_empleados] ADD num_iess VARCHAR(30) NULL;
PRINT '✅ Columnas adicionales agregadas a th_empleados.';
GO

-- =============================================================================
-- 7. VISTA view_th_iddatosempledo – ACTUALIZADA
--    Agrega las columnas requeridas por EmpleadoController::exportarCsv()
-- =============================================================================
CREATE OR ALTER VIEW [dbo].[view_th_iddatosempledo] AS
SELECT
    e.empleado_id,
    e.empleado_id               AS id,
    e.identificacion            AS cedula,
    e.apellidos,
    e.nombres,
    ISNULL(p.nombre_puesto, '')  AS cargo,
    ISNULL(u.nombre_unidad, '')  AS direccion_area,
    e.correo_institucional,
    e.estado,
    ISNULL(e.cargas_familiares, 0)          AS cargas_familiares,
    e.tipo_cuenta_bancaria,
    e.numero_cuenta_bancaria,
    e.institucion_bancaria,
    -- Columnas adicionales para exportarCsv()
    e.tipo_contrato,
    e.sueldo_rmu                AS remuneracion_mensual,
    e.sueldo_rmu,
    e.fecha_ingreso,
    e.fecha_nacimiento,
    e.telefono_movil,
    e.ciudad_residencia,
    e.ruta_foto
FROM [dbo].[th_empleados] e
LEFT JOIN [dbo].[th_puestos] p
       ON e.puesto_id = p.puesto_id
LEFT JOIN [dbo].[th_unidades_organizacionales] u
       ON e.unidad_id = u.unidad_id;
GO
PRINT '✅ Vista view_th_iddatosempledo actualizada.';
GO

-- =============================================================================
-- 8. VISTA vw_th_reporte_historial_jerarquico
--    Requerida por: EmpleadoModel::obtenerReporteFiltrado()
-- =============================================================================
CREATE OR ALTER VIEW [dbo].[vw_th_reporte_historial_jerarquico] AS
SELECT
    e.empleado_id,
    e.identificacion                            AS cedula,
    e.apellidos + ' ' + e.nombres              AS funcionario,
    p.codigo_puesto,
    p.nombre_puesto,

    -- Nombre histórico de la dirección cuando el funcionario trabajó ahí
    u.nombre_unidad                             AS departamento_historico,

    -- Dirección padre (si la unidad tiene padre)
    CASE
        WHEN u_padre.unidad_id IS NOT NULL THEN u_padre.nombre_unidad
        ELSE u.nombre_unidad
    END                                         AS direccion_padre,

    -- Sub-área / departamento específico
    CASE
        WHEN u_padre.unidad_id IS NOT NULL THEN u.nombre_unidad
        ELSE NULL
    END                                         AS sub_area,

    -- Dirección actual unificada (considera fusiones organizacionales)
    ISNULL(u_nueva.nombre_unidad, u.nombre_unidad)  AS direccion_actual_unificada,
    ISNULL(u_nueva.tipo_proceso,  u.tipo_proceso)   AS tipo_proceso,

    h.fecha_desde,
    h.fecha_hasta,

    -- Años de permanencia
    DATEDIFF(year, h.fecha_desde, ISNULL(h.fecha_hasta, GETDATE())) AS anios_permanencia,

    -- Días al próximo cumpleaños
    DATEDIFF(day, CAST(GETDATE() AS DATE),
        DATEFROMPARTS(
            YEAR(GETDATE()) + CASE
                WHEN DATEFROMPARTS(
                         YEAR(GETDATE()),
                         MONTH(e.fecha_nacimiento),
                         DAY(e.fecha_nacimiento)
                     ) < CAST(GETDATE() AS DATE)
                THEN 1 ELSE 0 END,
            MONTH(e.fecha_nacimiento),
            DAY(e.fecha_nacimiento)
        )
    ) AS dias_para_cumpleanos

FROM [dbo].[th_historial_laboral] h
JOIN  [dbo].[th_empleados]                  e       ON h.empleado_id = e.empleado_id
JOIN  [dbo].[th_puestos]                    p       ON h.puesto_id   = p.puesto_id
JOIN  [dbo].[th_unidades_organizacionales]  u       ON h.unidad_id   = u.unidad_id
LEFT JOIN [dbo].[th_unidades_organizacionales] u_padre
                                                    ON u.unidad_padre_id = u_padre.unidad_id
LEFT JOIN [dbo].[th_unidades_organizacionales] u_nueva
                                                    ON u.sucedido_por_id = u_nueva.unidad_id;
GO
PRINT '✅ Vista vw_th_reporte_historial_jerarquico creada.';
GO

-- =============================================================================
-- 9. VISTA vw_th_acciones_resumen
--    Requerida por: migra_accion_personal.sql (historial de acciones por empleado)
-- =============================================================================
CREATE OR ALTER VIEW [dbo].[vw_th_acciones_resumen] AS
SELECT
    ap.accion_id,
    ap.numero_accion,
    ap.fecha_elaboracion,
    ap.tipo_accion,
    ap.estado_documento,
    ap.fecha_rige_desde,
    ap.fecha_rige_hasta,
    -- Datos del empleado
    e.identificacion                AS cedula_pasaporte,
    e.apellidos + ' ' + e.nombres  AS apellidos_nombres,
    -- Situación actual vs propuesta
    p_act.nombre_puesto             AS actual_puesto,
    ap.actual_remuneracion,
    p_prop.nombre_puesto            AS propuesta_puesto,
    ap.propuesta_remuneracion,
    -- Diferencia salarial calculada
    (ISNULL(ap.propuesta_remuneracion, 0) - ISNULL(ap.actual_remuneracion, 0))
                                    AS diferencia_remuneracion,
    ap.usuario_crea,
    ap.fecha_elaboracion            AS fecha_creacion
FROM [dbo].[th_acciones_personal] ap
JOIN  [dbo].[th_empleados]         e
        ON e.empleado_id = ap.empleado_id
LEFT JOIN [dbo].[th_puestos]       p_act
        ON ap.actual_puesto_id   = p_act.puesto_id
LEFT JOIN [dbo].[th_puestos]       p_prop
        ON ap.propuesta_puesto_id = p_prop.puesto_id;
GO
PRINT '✅ Vista vw_th_acciones_resumen creada.';
GO

-- =============================================================================
-- 10. SP sp_th_guardar_empleado – Corregido para columnas reales de th_empleados
-- =============================================================================
CREATE OR ALTER PROCEDURE [dbo].[sp_th_guardar_empleado]
    @cedula             VARCHAR(20),
    @nombres            VARCHAR(100),
    @fecha_nac          DATE         = NULL,
    @condicion          VARCHAR(50)  = 'Ninguna',
    @tipo_disc          VARCHAR(80)  = NULL,
    @porcentaje_disc    DECIMAL(5,2) = NULL,
    @sexo               CHAR(1)      = NULL,
    @estado_civil       VARCHAR(30)  = NULL,
    @nacionalidad       VARCHAR(50)  = NULL,
    @tipo_sangre        VARCHAR(10)  = NULL,
    @depto              INT          = NULL,
    @puesto             INT          = NULL,
    @tipo_contrato      VARCHAR(100) = NULL,
    @fecha_ing          DATE         = NULL,
    @sueldo             DECIMAL(10,2)= NULL,
    @jornada            VARCHAR(30)  = 'Completa',
    @correo             VARCHAR(100) = NULL,
    @celular            VARCHAR(20)  = NULL,
    @convencional       VARCHAR(20)  = NULL,
    @ciudad             VARCHAR(50)  = NULL,
    @direccion          VARCHAR(MAX) = NULL,
    @contacto_emerg     VARCHAR(150) = NULL,
    @parentesco         VARCHAR(50)  = NULL,
    @tel_emerg          VARCHAR(20)  = NULL,
    @nivel_estudio      VARCHAR(80)  = NULL,
    @titulo             VARCHAR(150) = NULL,
    @iess               VARCHAR(30)  = NULL,
    @foto               VARCHAR(300) = 'public/img/default_avatar.png',
    @obs                VARCHAR(MAX) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        -- Separar nombres (último token = nombres, resto = apellidos)
        DECLARE @partes    NVARCHAR(200) = LTRIM(RTRIM(@nombres));
        DECLARE @apellidos VARCHAR(100)  = '';
        DECLARE @nombresFin VARCHAR(100) = '';

        -- Convencion: el CSV viene "APELLIDO1 APELLIDO2 NOMBRE1 NOMBRE2"
        -- Usamos el campo tal como viene (el controlador ya trae separados en el POST normal)
        SET @apellidos   = @partes;
        SET @nombresFin  = '';

        INSERT INTO [dbo].[th_empleados] (
            identificacion, apellidos, nombres, fecha_nacimiento,
            sexo, estado_civil, nacionalidad,
            unidad_id, puesto_id, fecha_ingreso, sueldo_rmu,
            correo_institucional, telefono_movil, telefono_convencional,
            ciudad_residencia, direccion_domiciliaria,
            codigo_iess, ruta_foto, observaciones,
            tipo_contrato, jornada, condicion_especial,
            tipo_discapacidad, porcentaje_discapacidad, tipo_sangre,
            contacto_emergencia, emergencia_relacion, tel_emergencia,
            nivel_estudio, titulo, estado, cargas_familiares, fecha_creacion
        ) VALUES (
            @cedula, @apellidos, @nombresFin, @fecha_nac,
            @sexo, @estado_civil, @nacionalidad,
            @depto, @puesto, @fecha_ing, @sueldo,
            ISNULL(@correo, ''), ISNULL(@celular, ''), @convencional,
            ISNULL(@ciudad, ''), ISNULL(@direccion, ''),
            @iess, ISNULL(@foto, 'public/img/default_avatar.png'), @obs,
            @tipo_contrato, @jornada, @condicion,
            @tipo_disc, @porcentaje_disc, @tipo_sangre,
            @contacto_emerg, @parentesco, @tel_emerg,
            @nivel_estudio, @titulo, 1, 0, GETDATE()
        );

        SELECT SCOPE_IDENTITY() AS nuevo_id, 1 AS exito, 'Empleado guardado correctamente.' AS mensaje;
    END TRY
    BEGIN CATCH
        SELECT 0 AS nuevo_id, 0 AS exito, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO
PRINT '✅ SP sp_th_guardar_empleado creado/actualizado.';
GO

-- =============================================================================
-- 11. SP sp_th_modificar_empleado
-- =============================================================================
CREATE OR ALTER PROCEDURE [dbo].[sp_th_modificar_empleado]
    @id                 INT,
    @cedula             VARCHAR(20),
    @nombres            VARCHAR(100),
    @fecha_nac          DATE         = NULL,
    @condicion          VARCHAR(50)  = 'Ninguna',
    @tipo_disc          VARCHAR(80)  = NULL,
    @porcentaje_disc    DECIMAL(5,2) = NULL,
    @sexo               CHAR(1)      = NULL,
    @estado_civil       VARCHAR(30)  = NULL,
    @nacionalidad       VARCHAR(50)  = NULL,
    @tipo_sangre        VARCHAR(10)  = NULL,
    @depto              INT          = NULL,
    @puesto             INT          = NULL,
    @tipo_contrato      VARCHAR(100) = NULL,
    @fecha_ing          DATE         = NULL,
    @sueldo             DECIMAL(10,2)= NULL,
    @jornada            VARCHAR(30)  = 'Completa',
    @correo             VARCHAR(100) = NULL,
    @celular            VARCHAR(20)  = NULL,
    @convencional       VARCHAR(20)  = NULL,
    @ciudad             VARCHAR(50)  = NULL,
    @direccion          VARCHAR(MAX) = NULL,
    @contacto_emerg     VARCHAR(150) = NULL,
    @parentesco         VARCHAR(50)  = NULL,
    @tel_emerg          VARCHAR(20)  = NULL,
    @nivel_estudio      VARCHAR(80)  = NULL,
    @titulo             VARCHAR(150) = NULL,
    @iess               VARCHAR(30)  = NULL,
    @foto               VARCHAR(300) = NULL,
    @obs                VARCHAR(MAX) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        UPDATE [dbo].[th_empleados] SET
            identificacion          = @cedula,
            fecha_nacimiento        = @fecha_nac,
            sexo                    = @sexo,
            estado_civil            = @estado_civil,
            nacionalidad            = @nacionalidad,
            unidad_id               = @depto,
            puesto_id               = @puesto,
            fecha_ingreso           = @fecha_ing,
            sueldo_rmu              = @sueldo,
            correo_institucional    = ISNULL(@correo, correo_institucional),
            telefono_movil          = ISNULL(@celular, telefono_movil),
            telefono_convencional   = @convencional,
            ciudad_residencia       = ISNULL(@ciudad, ciudad_residencia),
            direccion_domiciliaria  = ISNULL(@direccion, direccion_domiciliaria),
            codigo_iess             = @iess,
            ruta_foto               = ISNULL(@foto, ruta_foto),
            observaciones           = @obs,
            tipo_contrato           = @tipo_contrato,
            jornada                 = @jornada,
            condicion_especial      = @condicion,
            tipo_discapacidad       = @tipo_disc,
            porcentaje_discapacidad = @porcentaje_disc,
            tipo_sangre             = @tipo_sangre,
            contacto_emergencia     = @contacto_emerg,
            emergencia_relacion     = @parentesco,
            tel_emergencia          = @tel_emerg,
            nivel_estudio           = @nivel_estudio,
            titulo                  = @titulo
        WHERE empleado_id = @id;

        SELECT @@ROWCOUNT AS filas_afectadas, 1 AS exito, 'Empleado actualizado correctamente.' AS mensaje;
    END TRY
    BEGIN CATCH
        SELECT 0 AS filas_afectadas, 0 AS exito, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO
PRINT '✅ SP sp_th_modificar_empleado creado/actualizado.';
GO

-- =============================================================================
-- 12. SP sp_th_eliminar_empleado – Baja lógica (estado = 0)
-- =============================================================================
CREATE OR ALTER PROCEDURE [dbo].[sp_th_eliminar_empleado]
    @id INT
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRY
        UPDATE [dbo].[th_empleados]
        SET estado = 0
        WHERE empleado_id = @id;

        SELECT @@ROWCOUNT AS filas_afectadas, 1 AS exito, 'Empleado dado de baja del sistema.' AS mensaje;
    END TRY
    BEGIN CATCH
        SELECT 0 AS filas_afectadas, 0 AS exito, ERROR_MESSAGE() AS mensaje;
    END CATCH;
END;
GO
PRINT '✅ SP sp_th_eliminar_empleado creado/actualizado.';
GO

-- =============================================================================
-- RESUMEN FINAL
-- =============================================================================
PRINT '=================================================================';
PRINT '✅ PATCH v3 completado exitosamente.';
PRINT '   Tablas creadas  : th_historial_laboral, th_parametros,';
PRINT '                     th_usuarios_sistema, th_logs_auditoria';
PRINT '   Columnas nuevas : th_empleados (+14 cols), th_unidades (+3 cols)';
PRINT '   Vistas          : view_th_iddatosempledo (actualizada),';
PRINT '                     vw_th_reporte_historial_jerarquico,';
PRINT '                     vw_th_acciones_resumen';
PRINT '   Procedimientos  : sp_th_guardar_empleado,';
PRINT '                     sp_th_modificar_empleado,';
PRINT '                     sp_th_eliminar_empleado';
PRINT '=================================================================';
GO
