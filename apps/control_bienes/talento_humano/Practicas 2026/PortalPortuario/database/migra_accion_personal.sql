-- ============================================================================
-- MIGRACIÓN: th_acciones_personal
-- Sistema: Portal Portuario APM – Módulo Talento Humano
-- Versión: 1.0  |  Fecha: 2026-05
-- Descripción: Tabla de documentos "Acción de Personal" (Fase 2).
--              Registra cada movimiento legal de un servidor según Art. 21 LOSEP.
-- ============================================================================

USE [PortalPortuario];   -- ← Reemplaza con el nombre real de la BD en producción
GO

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. Tabla principal: th_acciones_personal
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE [dbo].[th_acciones_personal] (
    -- PK auto-incremental
    [accion_id]             INT IDENTITY(1,1) PRIMARY KEY,

    -- Relación con el expediente del empleado
    [empleado_id]           INT NOT NULL,

    -- Numeración oficial (Ej: APM-TH-2026-001)
    [numero_accion]         VARCHAR(50)  NOT NULL UNIQUE,
    [fecha_elaboracion]     DATE         NOT NULL DEFAULT GETDATE(),

    -- ── Tipo de acción (checkbox del formulario físico) ───────────────────
    [tipo_accion]           VARCHAR(100) NOT NULL,
    -- INGRESO | ASCENSO | TRASLADO | COMISION | VACACIONES | CESACION | OTRO
    [explicacion_otro]      VARCHAR(255) NULL,

    -- ── Vigencia de la acción ─────────────────────────────────────────────
    [rige_desde]            DATE         NOT NULL,
    [rige_hasta]            DATE         NULL,   -- NULL = permanente / indefinido

    -- ── Declaración juramentada ───────────────────────────────────────────
    [presento_declaracion]  VARCHAR(20)  NOT NULL DEFAULT 'NO APLICA',
    -- 'SI' | 'NO' | 'NO APLICA'

    -- ── Motivación / fundamento legal ─────────────────────────────────────
    [motivacion_texto]      VARCHAR(MAX) NOT NULL,

    -- ── SITUACIÓN ACTUAL (fotografía del momento exacto) ─────────────────
    [actual_proceso]        VARCHAR(150) NULL,
    [actual_unidad]         VARCHAR(150) NULL,
    [actual_puesto]         VARCHAR(150) NULL,
    [actual_remuneracion]   DECIMAL(10,2) NULL,
    [actual_contrato]       VARCHAR(80)  NULL,

    -- ── SITUACIÓN PROPUESTA (hacia dónde va el servidor) ──────────────────
    [propuesta_proceso]     VARCHAR(150) NULL,
    [propuesta_unidad]      VARCHAR(150) NULL,
    [propuesta_puesto]      VARCHAR(150) NULL,
    [propuesta_remuneracion] DECIMAL(10,2) NULL,
    [propuesta_contrato]    VARCHAR(80)  NULL,

    -- ── Estado del documento y flujo de aprobación ────────────────────────
    [estado_documento]      VARCHAR(30)  NOT NULL DEFAULT 'BORRADOR',
    -- 'BORRADOR' → 'PENDIENTE' → 'APROBADO' / 'ANULADO'

    -- ── Notificación electrónica ──────────────────────────────────────────
    [notificacion_electronica] BIT       NOT NULL DEFAULT 0,
    [correo_notificacion]   VARCHAR(100) NULL,

    -- ── Trazabilidad ──────────────────────────────────────────────────────
    [usuario_crea]          VARCHAR(50)  NOT NULL,
    [fecha_creacion]        DATETIME     NOT NULL DEFAULT GETDATE(),
    [usuario_aprueba]       VARCHAR(50)  NULL,
    [fecha_aprobacion]      DATETIME     NULL,

    -- ── FK al expediente del empleado ─────────────────────────────────────
    CONSTRAINT [FK_AccionPersonal_Empleado]
        FOREIGN KEY ([empleado_id]) REFERENCES [dbo].[th_empleados]([empleado_id])
        ON UPDATE CASCADE
        ON DELETE NO ACTION
);
GO

-- ─────────────────────────────────────────────────────────────────────────────
-- 2. Índices de búsqueda frecuente
-- ─────────────────────────────────────────────────────────────────────────────
CREATE INDEX [IX_AccionPersonal_EmpleadoId]
    ON [dbo].[th_acciones_personal]([empleado_id]);

CREATE INDEX [IX_AccionPersonal_Tipo]
    ON [dbo].[th_acciones_personal]([tipo_accion]);

CREATE INDEX [IX_AccionPersonal_Estado]
    ON [dbo].[th_acciones_personal]([estado_documento]);

CREATE INDEX [IX_AccionPersonal_Fecha]
    ON [dbo].[th_acciones_personal]([fecha_elaboracion] DESC);
GO

-- ─────────────────────────────────────────────────────────────────────────────
-- 3. Vista para el portal (documenta historial de acciones por empleado)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE OR ALTER VIEW [dbo].[vw_th_acciones_resumen] AS
SELECT
    ap.accion_id,
    ap.numero_accion,
    ap.fecha_elaboracion,
    ap.tipo_accion,
    ap.estado_documento,
    ap.rige_desde,
    ap.rige_hasta,
    -- Datos del empleado
    e.cedula_pasaporte,
    e.apellidos_nombres,
    -- Comparativa
    ap.actual_puesto,
    ap.actual_remuneracion,
    ap.propuesta_puesto,
    ap.propuesta_remuneracion,
    -- Diferencia salarial calculada
    (ISNULL(ap.propuesta_remuneracion, 0) - ISNULL(ap.actual_remuneracion, 0))
        AS diferencia_remuneracion,
    ap.usuario_crea,
    ap.fecha_creacion
FROM [dbo].[th_acciones_personal] ap
JOIN [dbo].[th_empleados]         e  ON e.empleado_id = ap.empleado_id;
GO

-- ─────────────────────────────────────────────────────────────────────────────
-- 4. Procedimiento almacenado: sp_th_guardar_accion_personal
--    Inserta el documento Y activa el SP de historial laboral si la acción
--    implica un cambio de puesto/área (Ascenso, Traslado, Ingreso).
-- ─────────────────────────────────────────────────────────────────────────────
CREATE OR ALTER PROCEDURE [dbo].[sp_th_guardar_accion_personal]
    @empleado_id            INT,
    @numero_accion          VARCHAR(50),
    @fecha_elaboracion      DATE,
    @tipo_accion            VARCHAR(100),
    @explicacion_otro       VARCHAR(255)  = NULL,
    @rige_desde             DATE,
    @rige_hasta             DATE         = NULL,
    @presento_declaracion   VARCHAR(20)  = 'NO APLICA',
    @motivacion_texto       VARCHAR(MAX),
    @actual_proceso         VARCHAR(150) = NULL,
    @actual_unidad          VARCHAR(150) = NULL,
    @actual_puesto          VARCHAR(150) = NULL,
    @actual_remuneracion    DECIMAL(10,2) = NULL,
    @actual_contrato        VARCHAR(80)  = NULL,
    @propuesta_proceso      VARCHAR(150) = NULL,
    @propuesta_unidad       VARCHAR(150) = NULL,
    @propuesta_puesto       VARCHAR(150) = NULL,
    @propuesta_remuneracion DECIMAL(10,2) = NULL,
    @propuesta_contrato     VARCHAR(80)  = NULL,
    @notificacion           BIT          = 0,
    @correo_notificacion    VARCHAR(100) = NULL,
    @usuario_crea           VARCHAR(50)
AS
BEGIN
    SET NOCOUNT ON;

    BEGIN TRANSACTION;

    BEGIN TRY
        -- Insertar el documento de acción
        INSERT INTO [dbo].[th_acciones_personal] (
            empleado_id, numero_accion, fecha_elaboracion,
            tipo_accion, explicacion_otro,
            rige_desde, rige_hasta, presento_declaracion, motivacion_texto,
            actual_proceso, actual_unidad, actual_puesto,
            actual_remuneracion, actual_contrato,
            propuesta_proceso, propuesta_unidad, propuesta_puesto,
            propuesta_remuneracion, propuesta_contrato,
            estado_documento, notificacion_electronica, correo_notificacion,
            usuario_crea
        ) VALUES (
            @empleado_id, @numero_accion, @fecha_elaboracion,
            @tipo_accion, @explicacion_otro,
            @rige_desde, @rige_hasta, @presento_declaracion, @motivacion_texto,
            @actual_proceso, @actual_unidad, @actual_puesto,
            @actual_remuneracion, @actual_contrato,
            @propuesta_proceso, @propuesta_unidad, @propuesta_puesto,
            @propuesta_remuneracion, @propuesta_contrato,
            'BORRADOR', @notificacion, @correo_notificacion,
            @usuario_crea
        );

        -- Si la acción implica cambio de posición, actualizar el historial laboral
        IF @tipo_accion IN ('INGRESO', 'ASCENSO', 'TRASLADO')
        BEGIN
            -- TODO: Llamar a sp_th_guardar_nuevo_registro_laboral para cerrar
            --       el historial anterior y abrir el nuevo registro con la situación propuesta.
            --       (Se implementará en la Fase 3 cuando se active la BD completa.)
            --
            -- EXEC [dbo].[sp_th_guardar_nuevo_registro_laboral]
            --     @empleado_id        = @empleado_id,
            --     @nueva_unidad       = @propuesta_unidad,
            --     @nuevo_puesto       = @propuesta_puesto,
            --     @nueva_remuneracion = @propuesta_remuneracion,
            --     @fecha_inicio       = @rige_desde,
            --     @usuario_crea       = @usuario_crea;
            PRINT 'INFO: Historial laboral pendiente de actualización (Fase 3).';
        END;

        COMMIT TRANSACTION;
        SELECT 1 AS Exito, 'Acción de Personal guardada correctamente.' AS Mensaje;

    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        SELECT 0 AS Exito, ERROR_MESSAGE() AS Mensaje;
    END CATCH;
END;
GO

-- ─────────────────────────────────────────────────────────────────────────────
-- 5. Comentario de verificación
-- ─────────────────────────────────────────────────────────────────────────────
PRINT '✅ Migración th_acciones_personal completada.';
PRINT '   Tablas : th_acciones_personal';
PRINT '   Vistas : vw_th_acciones_resumen';
PRINT '   SPs    : sp_th_guardar_accion_personal';
GO
