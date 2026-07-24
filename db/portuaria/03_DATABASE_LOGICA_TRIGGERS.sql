/*
  03_DATABASE_LOGICA_TRIGGERS.sql
  Lógica de visitas + función de turnos + triggers de visitas/novedades/rondas.
*/

USE PortuariaDemo;
GO
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.bit_visitas', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_visitas (
        id_visita            INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        id_persona           INT NOT NULL,
        tipo_visitante       NVARCHAR(20) NOT NULL,
        id_empresa           INT NULL,
        id_funcionario       INT NOT NULL,
        id_destino           INT NOT NULL,
        id_motivo            INT NOT NULL,
        detalle_motivo       NVARCHAR(MAX) NULL CONSTRAINT DF_visitas_detalle_motivo DEFAULT (N''),
        id_nivel_incidente   INT NOT NULL CONSTRAINT DF_visitas_id_nivel DEFAULT (1),
        fecha_visita         DATE NOT NULL CONSTRAINT DF_visitas_fecha DEFAULT (CONVERT(date, GETDATE())),
        hora_entrada         TIME NOT NULL CONSTRAINT DF_visitas_hora_entrada DEFAULT (CONVERT(time, GETDATE())),
        hora_salida          TIME NULL,
        estado               BIT NOT NULL CONSTRAINT DF_visitas_estado DEFAULT (1),
        CONSTRAINT FK_visitas_personas       FOREIGN KEY (id_persona)       REFERENCES dbo.bit_personas(id_persona),
        CONSTRAINT FK_visitas_empresas       FOREIGN KEY (id_empresa)       REFERENCES dbo.bit_empresas(id_empresa),
        CONSTRAINT FK_visitas_funcionarios   FOREIGN KEY (id_funcionario)   REFERENCES dbo.bit_funcionarios(id_funcionario),
        CONSTRAINT FK_visitas_destinos       FOREIGN KEY (id_destino)       REFERENCES dbo.bit_destinos(id_destino),
        CONSTRAINT FK_visitas_motivos        FOREIGN KEY (id_motivo)        REFERENCES dbo.bit_motivos(id_motivo),
        CONSTRAINT FK_visitas_niveles        FOREIGN KEY (id_nivel_incidente) REFERENCES dbo.bit_niveles_incidente(id_incidentes)
    );
END
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID(N'dbo.bit_visitas') AND name = N'detalle_motivo'
)
BEGIN
    ALTER TABLE dbo.bit_visitas
    ADD detalle_motivo NVARCHAR(MAX) NULL CONSTRAINT DF_bit_visitas_detalle_motivo DEFAULT (N'');
END
GO

IF EXISTS (
    SELECT 1
    FROM sys.columns
    WHERE object_id = OBJECT_ID(N'dbo.bit_visitas')
      AND name = N'detalle_motivo'
      AND max_length <> -1
)
BEGIN
    ALTER TABLE dbo.bit_visitas ALTER COLUMN detalle_motivo NVARCHAR(MAX) NULL;
END
GO

IF OBJECT_ID(N'dbo.totales_visitas', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_totales_visitas (
        idtotal             INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        fecha               DATE NOT NULL,
        total_visitas       INT NOT NULL CONSTRAINT DF_totales_visitas_total_visitas DEFAULT (0),
        total_activas       INT NOT NULL CONSTRAINT DF_totales_visitas_total_activas DEFAULT (0),
        total_proveedores   INT NOT NULL CONSTRAINT DF_totales_visitas_total_proveedores DEFAULT (0),
        CONSTRAINT UQ_totales_visitas_fecha UNIQUE (fecha)
    );
END
GO

IF COL_LENGTH(N'dbo.totales_visitas', N'total_proveedores') IS NULL
BEGIN
    ALTER TABLE dbo.bit_totales_visitas
    ADD total_proveedores INT NOT NULL
        CONSTRAINT DF_totales_visitas_total_proveedores DEFAULT (0);
END
GO

IF OBJECT_ID(N'dbo.bit_movimientos', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_movimientos (
        id_movimiento BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        id_usuario    INT NULL,
        tipo_evento   NVARCHAR(20) NOT NULL,
        descripcion   NVARCHAR(500) NOT NULL,
        turno         NVARCHAR(10) NOT NULL,
        fecha_hora    DATETIME NOT NULL CONSTRAINT DF_movimientos_fecha_hora DEFAULT (GETDATE())
    );
END
GO

IF OBJECT_ID(N'dbo.fn_turno_por_hora', N'FN') IS NOT NULL
    DROP FUNCTION dbo.fn_turno_por_hora;
GO
CREATE FUNCTION dbo.fn_turno_por_hora (@hora TIME)
RETURNS NVARCHAR(10)
AS
BEGIN
    RETURN (
        CASE
            WHEN @hora >= '07:00:00' AND @hora < '15:00:00' THEN N'Mañana'
            WHEN @hora >= '15:00:00' AND @hora < '23:00:00' THEN N'Tarde'
            ELSE N'Noche'
        END
    );
END;
GO

;WITH TotalesRecalc AS (
    SELECT
        CONVERT(date, v.fecha_visita) AS fecha,
        COUNT(1) AS total_visitas,
        SUM(CASE WHEN v.hora_salida IS NULL THEN 1 ELSE 0 END) AS total_activas,
        SUM(CASE WHEN v.tipo_visitante = N'Empresa' THEN 1 ELSE 0 END) AS total_proveedores
    FROM dbo.bit_visitas v
    GROUP BY CONVERT(date, v.fecha_visita)
)
MERGE dbo.bit_totales_visitas AS tgt
USING TotalesRecalc AS src
ON tgt.fecha = src.fecha
WHEN MATCHED THEN
    UPDATE SET
        tgt.total_visitas = src.total_visitas,
        tgt.total_activas = src.total_activas,
        tgt.total_proveedores = src.total_proveedores
WHEN NOT MATCHED BY TARGET THEN
    INSERT (fecha, total_visitas, total_activas, total_proveedores)
    VALUES (src.fecha, src.total_visitas, src.total_activas, src.total_proveedores)
WHEN NOT MATCHED BY SOURCE THEN
    DELETE;
GO

IF OBJECT_ID(N'dbo.trg_visitas_sync_totales', N'TR') IS NOT NULL
    DROP TRIGGER dbo.trg_visitas_sync_totales;
GO
CREATE TRIGGER dbo.trg_visitas_sync_totales
ON dbo.bit_visitas
AFTER INSERT, UPDATE, DELETE
AS
BEGIN
    SET NOCOUNT ON;

    ;WITH FechasAfectadas AS (
        SELECT DISTINCT CONVERT(date, fecha_visita) AS fecha
        FROM inserted
        WHERE fecha_visita IS NOT NULL
        UNION
        SELECT DISTINCT CONVERT(date, fecha_visita) AS fecha
        FROM deleted
        WHERE fecha_visita IS NOT NULL
    ),
    TotalesFecha AS (
        SELECT
            CONVERT(date, v.fecha_visita) AS fecha,
            COUNT(1) AS total_visitas,
            SUM(CASE WHEN v.hora_salida IS NULL THEN 1 ELSE 0 END) AS total_activas,
            SUM(CASE WHEN v.tipo_visitante = N'Empresa' THEN 1 ELSE 0 END) AS total_proveedores
        FROM dbo.bit_visitas v
        INNER JOIN FechasAfectadas f ON f.fecha = CONVERT(date, v.fecha_visita)
        GROUP BY CONVERT(date, v.fecha_visita)
    )
    MERGE dbo.bit_totales_visitas AS tgt
    USING TotalesFecha AS src
    ON tgt.fecha = src.fecha
    WHEN MATCHED THEN
        UPDATE SET
            tgt.total_visitas = src.total_visitas,
            tgt.total_activas = src.total_activas,
            tgt.total_proveedores = src.total_proveedores
    WHEN NOT MATCHED BY TARGET THEN
        INSERT (fecha, total_visitas, total_activas, total_proveedores)
        VALUES (src.fecha, src.total_visitas, src.total_activas, src.total_proveedores)
    WHEN NOT MATCHED BY SOURCE AND tgt.fecha IN (SELECT fecha FROM FechasAfectadas) THEN
        DELETE;

    DECLARE @idUsuario INT = TRY_CONVERT(INT, CONVERT(VARBINARY(4), CONTEXT_INFO()));

    ;WITH Ingresos AS (
        SELECT i.id_visita, i.id_persona
        FROM inserted i
        LEFT JOIN deleted d ON d.id_visita = i.id_visita
        WHERE d.id_visita IS NULL
    ),
    Salidas AS (
        SELECT i.id_visita, i.id_persona
        FROM inserted i
        INNER JOIN deleted d ON d.id_visita = i.id_visita
        WHERE d.hora_salida IS NULL
          AND i.hora_salida IS NOT NULL
    )
    INSERT INTO dbo.bit_movimientos (id_usuario, tipo_evento, descripcion, turno)
    SELECT
        @idUsuario,
        N'INGRESO',
        N'Ingreso de visitante [' + COALESCE(p.nombres + N' ' + p.apellidos, N'Sin nombre') + N'] por garita. [REF:V' + CAST(x.id_visita AS NVARCHAR(20)) + N']',
        dbo.fn_turno_por_hora(CONVERT(TIME, GETDATE()))
    FROM Ingresos x
    LEFT JOIN dbo.bit_personas p ON p.id_persona = x.id_persona
    UNION ALL
    SELECT
        @idUsuario,
        N'SALIDA',
        N'Salida de visitante [' + COALESCE(p.nombres + N' ' + p.apellidos, N'Sin nombre') + N'] por garita. [REF:V' + CAST(x.id_visita AS NVARCHAR(20)) + N']',
        dbo.fn_turno_por_hora(CONVERT(TIME, GETDATE()))
    FROM Salidas x
    LEFT JOIN dbo.bit_personas p ON p.id_persona = x.id_persona;
END;
GO

IF OBJECT_ID(N'dbo.trg_novedades_turno', N'TR') IS NOT NULL
    DROP TRIGGER dbo.trg_novedades_turno;
GO
CREATE TRIGGER dbo.trg_novedades_turno
ON dbo.bit_novedades
AFTER INSERT, UPDATE
AS
BEGIN
    SET NOCOUNT ON;
    UPDATE n
    SET turno = dbo.fn_turno_por_hora(CAST(n.hora AS TIME))
    FROM dbo.bit_novedades n
    INNER JOIN inserted i ON i.idnovedad = n.idnovedad
    WHERE n.turno IS NULL OR LTRIM(RTRIM(n.turno)) = N'';
END;
GO

IF OBJECT_ID(N'dbo.bit_niveles_alerta', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_niveles_alerta (
        id_alerta     INT NOT NULL PRIMARY KEY,
        descripcion   NVARCHAR(80) NOT NULL,
        color_hex     NVARCHAR(7) NOT NULL,
        estado        BIT NOT NULL CONSTRAINT DF_niveles_alerta_estado DEFAULT (1)
    );
END
GO

IF OBJECT_ID(N'dbo.bit_totales_actividades', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_totales_actividades (
        id_actividades      INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        fecha               DATE NOT NULL,
        total_detalles      INT NOT NULL CONSTRAINT DF_totales_actividades_total DEFAULT (0),
        CONSTRAINT UQ_totales_actividades_fecha UNIQUE (fecha)
    );
END
GO

IF OBJECT_ID(N'dbo.bit_rondas_cabecera', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_rondas_cabecera (
        id_ronda      INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        id_usuario    INT NOT NULL,
        fecha         DATE NOT NULL,
        turno         NVARCHAR(20) NOT NULL,
        hora_inicio   TIME NULL,
        hora_fin      TIME NULL,
        estado        BIT NOT NULL CONSTRAINT DF_rondas_cabecera_estado DEFAULT (1),
        CONSTRAINT FK_rondas_cabecera_usuario FOREIGN KEY (id_usuario) REFERENCES dbo.bit_usuarios_apm(id_usuario)
    );
    CREATE INDEX IX_rondas_cabecera_usuario_fecha ON dbo.bit_rondas_cabecera (id_usuario, fecha, turno);
END
GO

IF OBJECT_ID(N'dbo.bit_rondas_detalles', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_rondas_detalles (
        id_detalle      INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        id_ronda        INT NOT NULL,
        hora_registro   DATETIME NOT NULL CONSTRAINT DF_rondas_detalles_hora DEFAULT (GETDATE()),
        actividad       NVARCHAR(MAX) NOT NULL,
        id_alerta       INT NOT NULL,
        CONSTRAINT FK_rondas_detalles_ronda FOREIGN KEY (id_ronda) REFERENCES dbo.bit_rondas_cabecera(id_ronda) ON DELETE CASCADE,
        CONSTRAINT FK_rondas_detalles_alerta FOREIGN KEY (id_alerta) REFERENCES dbo.bit_niveles_alerta(id_alerta)
    );
    CREATE INDEX IX_rondas_detalles_ronda ON dbo.bit_rondas_detalles (id_ronda);
END
GO

IF OBJECT_ID(N'dbo.trg_rondas_sync_totales', N'TR') IS NOT NULL
    DROP TRIGGER dbo.trg_rondas_sync_totales;
GO
CREATE TRIGGER dbo.trg_rondas_sync_totales
ON dbo.bit_rondas_detalles
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;
    ;WITH agg AS (
        SELECT c.fecha AS d, COUNT(*) AS cnt
        FROM inserted i
        INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = i.id_ronda
        GROUP BY c.fecha
    )
    MERGE dbo.bit_totales_actividades AS tgt
    USING agg AS src ON tgt.fecha = src.d
    WHEN MATCHED THEN
        UPDATE SET total_detalles = tgt.total_detalles + src.cnt
    WHEN NOT MATCHED BY TARGET THEN
        INSERT (fecha, total_detalles) VALUES (src.d, src.cnt);
END
GO

PRINT '03_DATABASE_LOGICA_TRIGGERS.sql ejecutado correctamente.';
GO

