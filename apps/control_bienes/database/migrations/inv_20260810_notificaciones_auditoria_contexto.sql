/* Notificaciones personales y auditoría contextual — SQL Server */
SET XACT_ABORT ON;
BEGIN TRANSACTION;

IF COL_LENGTH('dbo.inv_notificaciones', 'usuario_id') IS NULL
    ALTER TABLE dbo.inv_notificaciones ADD usuario_id INT NULL;
IF COL_LENGTH('dbo.inv_notificaciones', 'creado_por_id') IS NULL
    ALTER TABLE dbo.inv_notificaciones ADD creado_por_id INT NULL;

IF OBJECT_ID('dbo.inv_notificaciones_lecturas', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_notificaciones_lecturas (
        id INT IDENTITY(1,1) PRIMARY KEY,
        notificacion_id INT NOT NULL,
        usuario_id INT NOT NULL,
        visto TINYINT NOT NULL CONSTRAINT df_notif_lectura_visto DEFAULT 0,
        eliminada TINYINT NOT NULL CONSTRAINT df_notif_lectura_eliminada DEFAULT 0,
        fecha_lectura DATETIME2 NULL,
        CONSTRAINT uq_notificacion_lector UNIQUE (notificacion_id, usuario_id),
        CONSTRAINT fk_notif_lectura_notificacion FOREIGN KEY (notificacion_id)
            REFERENCES dbo.inv_notificaciones(id) ON DELETE CASCADE
    );
END;

IF COL_LENGTH('dbo.inv_bitacora', 'usuario_id') IS NULL ALTER TABLE dbo.inv_bitacora ADD usuario_id INT NULL;
IF COL_LENGTH('dbo.inv_bitacora', 'usuario_nombre') IS NULL ALTER TABLE dbo.inv_bitacora ADD usuario_nombre NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.inv_bitacora', 'usuario_login') IS NULL ALTER TABLE dbo.inv_bitacora ADD usuario_login NVARCHAR(150) NULL;
IF COL_LENGTH('dbo.inv_bitacora', 'rol') IS NULL ALTER TABLE dbo.inv_bitacora ADD rol NVARCHAR(80) NULL;
IF COL_LENGTH('dbo.inv_bitacora', 'ip') IS NULL ALTER TABLE dbo.inv_bitacora ADD ip NVARCHAR(64) NULL;
IF COL_LENGTH('dbo.inv_bitacora', 'equipo') IS NULL ALTER TABLE dbo.inv_bitacora ADD equipo NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.inv_bitacora', 'user_agent') IS NULL ALTER TABLE dbo.inv_bitacora ADD user_agent NVARCHAR(1000) NULL;
IF COL_LENGTH('dbo.inv_bitacora', 'metodo_http') IS NULL ALTER TABLE dbo.inv_bitacora ADD metodo_http NVARCHAR(10) NULL;
IF COL_LENGTH('dbo.inv_bitacora', 'ruta') IS NULL ALTER TABLE dbo.inv_bitacora ADD ruta NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.inv_bitacora', 'accion') IS NULL ALTER TABLE dbo.inv_bitacora ADD accion NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.inv_bitacora', 'request_id') IS NULL ALTER TABLE dbo.inv_bitacora ADD request_id NVARCHAR(64) NULL;
IF COL_LENGTH('dbo.inv_bitacora', 'resultado') IS NULL ALTER TABLE dbo.inv_bitacora ADD resultado NVARCHAR(30) NULL;
IF COL_LENGTH('dbo.inv_bitacora', 'datos_contexto') IS NULL ALTER TABLE dbo.inv_bitacora ADD datos_contexto NVARCHAR(MAX) NULL;
IF COL_LENGTH('dbo.inv_bitacora', 'duracion_ms') IS NULL ALTER TABLE dbo.inv_bitacora ADD duracion_ms DECIMAL(12,2) NULL;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'ix_notificaciones_usuario' AND object_id = OBJECT_ID('dbo.inv_notificaciones'))
    CREATE INDEX ix_notificaciones_usuario ON dbo.inv_notificaciones(usuario_id, id DESC);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'ix_bitacora_usuario_fecha' AND object_id = OBJECT_ID('dbo.inv_bitacora'))
    CREATE INDEX ix_bitacora_usuario_fecha ON dbo.inv_bitacora(usuario_id, fecha DESC);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'ix_bitacora_ip_fecha' AND object_id = OBJECT_ID('dbo.inv_bitacora'))
    CREATE INDEX ix_bitacora_ip_fecha ON dbo.inv_bitacora(ip, fecha DESC);

COMMIT TRANSACTION;
