/*
  Flujo digital de solicitudes, notas de pedido, egresos y Kardex.
  SQL Server Enterprise 2014. Migracion idempotente y sin cambios en ingresos.
*/
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
BEGIN TRANSACTION;

IF OBJECT_ID('dbo.inv_notas_pedido', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_notas_pedido (
        id_nota INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        secuencial NVARCHAR(50) NOT NULL,
        centro_consumo_id INT NOT NULL,
        solicitante_id INT NOT NULL,
        receptor_id INT NULL,
        fecha_solicitud DATE NOT NULL,
        motivo NVARCHAR(1000) NOT NULL,
        observaciones NVARCHAR(2000) NULL,
        tipo_bien CHAR(2) NOT NULL,
        estado NVARCHAR(20) NOT NULL CONSTRAINT df_inv_notas_estado DEFAULT ('ENVIADA'),
        grupo_solicitud UNIQUEIDENTIFIER NOT NULL CONSTRAINT df_inv_notas_grupo DEFAULT (NEWID()),
        creado_por NVARCHAR(255) NOT NULL,
        fecha_creacion DATETIME2(0) NOT NULL CONSTRAINT df_inv_notas_fecha DEFAULT (SYSDATETIME()),
        fecha_actualizacion DATETIME2(0) NOT NULL CONSTRAINT df_inv_notas_actualizacion DEFAULT (SYSDATETIME()),
        CONSTRAINT ux_inv_notas_secuencial UNIQUE (secuencial),
        CONSTRAINT ck_inv_notas_tipo CHECK (tipo_bien IN ('CC','AF')),
        CONSTRAINT ck_inv_notas_estado CHECK (estado IN ('ENVIADA','EN_REVISION','DISPONIBLE','PARCIAL','SIN_EXISTENCIAS','ATENDIDA','CERRADA','CANCELADA')),
        CONSTRAINT fk_inv_notas_centro FOREIGN KEY (centro_consumo_id) REFERENCES dbo.inv_centros_consumo(id),
        CONSTRAINT fk_inv_notas_solicitante FOREIGN KEY (solicitante_id) REFERENCES dbo.inv_talento_personal(id),
        CONSTRAINT fk_inv_notas_receptor FOREIGN KEY (receptor_id) REFERENCES dbo.inv_talento_personal(id)
    );
END;

IF OBJECT_ID('dbo.inv_notas_pedido_detalles', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_notas_pedido_detalles (
        id_detalle INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        nota_id INT NOT NULL,
        item_id INT NOT NULL,
        cantidad_solicitada INT NOT NULL,
        cantidad_entregada INT NOT NULL CONSTRAINT df_inv_notas_det_entregada DEFAULT (0),
        observacion_bodega NVARCHAR(1000) NULL,
        CONSTRAINT ck_inv_notas_det_solicitada CHECK (cantidad_solicitada > 0),
        CONSTRAINT ck_inv_notas_det_entregada CHECK (cantidad_entregada >= 0 AND cantidad_entregada <= cantidad_solicitada),
        CONSTRAINT ux_inv_notas_det_item UNIQUE (nota_id, item_id),
        CONSTRAINT fk_inv_notas_det_nota FOREIGN KEY (nota_id) REFERENCES dbo.inv_notas_pedido(id_nota),
        CONSTRAINT fk_inv_notas_det_item FOREIGN KEY (item_id) REFERENCES dbo.inv_inventario(id)
    );
END;

IF COL_LENGTH('dbo.inv_bod_egresos', 'nota_pedido_id') IS NULL
    ALTER TABLE dbo.inv_bod_egresos ADD nota_pedido_id INT NULL;

IF COL_LENGTH('dbo.inv_bod_egresos', 'centro_consumo_id') IS NULL
    ALTER TABLE dbo.inv_bod_egresos ADD centro_consumo_id INT NULL;

IF COL_LENGTH('dbo.inv_bod_egresos', 'estado') IS NULL
    ALTER TABLE dbo.inv_bod_egresos ADD estado NVARCHAR(20) NOT NULL
        CONSTRAINT df_inv_egresos_estado DEFAULT ('CONFIRMADO');

IF OBJECT_ID('dbo.inv_kardex', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_kardex (
        id_movimiento BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        item_id INT NOT NULL,
        fecha_movimiento DATETIME2(0) NOT NULL CONSTRAINT df_inv_kardex_fecha DEFAULT (SYSDATETIME()),
        tipo_movimiento NVARCHAR(20) NOT NULL,
        documento_tipo NVARCHAR(20) NOT NULL,
        documento_id INT NOT NULL,
        documento_secuencial NVARCHAR(50) NOT NULL,
        entrada INT NOT NULL CONSTRAINT df_inv_kardex_entrada DEFAULT (0),
        salida INT NOT NULL CONSTRAINT df_inv_kardex_salida DEFAULT (0),
        saldo_anterior INT NOT NULL,
        saldo_resultante INT NOT NULL,
        centro_consumo_id INT NULL,
        responsable_id INT NULL,
        usuario_registro NVARCHAR(255) NOT NULL,
        observaciones NVARCHAR(1000) NULL,
        CONSTRAINT ck_inv_kardex_cantidades CHECK (entrada >= 0 AND salida >= 0 AND (entrada > 0 OR salida > 0)),
        CONSTRAINT fk_inv_kardex_item FOREIGN KEY (item_id) REFERENCES dbo.inv_inventario(id),
        CONSTRAINT fk_inv_kardex_centro FOREIGN KEY (centro_consumo_id) REFERENCES dbo.inv_centros_consumo(id),
        CONSTRAINT fk_inv_kardex_responsable FOREIGN KEY (responsable_id) REFERENCES dbo.inv_talento_personal(id)
    );
    CREATE INDEX ix_inv_kardex_item_fecha ON dbo.inv_kardex(item_id, fecha_movimiento DESC, id_movimiento DESC);
    CREATE INDEX ix_inv_kardex_documento ON dbo.inv_kardex(documento_tipo, documento_id);
END;

IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_inv_egresos_nota')
    ALTER TABLE dbo.inv_bod_egresos WITH CHECK ADD CONSTRAINT fk_inv_egresos_nota
        FOREIGN KEY (nota_pedido_id) REFERENCES dbo.inv_notas_pedido(id_nota);

IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_inv_egresos_centro')
    ALTER TABLE dbo.inv_bod_egresos WITH CHECK ADD CONSTRAINT fk_inv_egresos_centro
        FOREIGN KEY (centro_consumo_id) REFERENCES dbo.inv_centros_consumo(id);

IF NOT EXISTS (SELECT 1 FROM dbo.inv_secuenciales WHERE modulo = 'npe')
    INSERT INTO dbo.inv_secuenciales (modulo, prefijo, ultimo_numero) VALUES ('npe', 'NPE-', 0);

COMMIT TRANSACTION;
GO
