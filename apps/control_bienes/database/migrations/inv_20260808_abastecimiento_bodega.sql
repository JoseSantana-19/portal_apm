/*
  Flujo de abastecimiento de bodega - SQL Server 2014.
  Migracion aditiva e idempotente: no elimina las notas/egresos historicos.
*/
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
BEGIN TRANSACTION;

IF OBJECT_ID('dbo.inv_abast_notas_pedido', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_abast_notas_pedido (
        id_nota INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        secuencial NVARCHAR(50) NOT NULL UNIQUE,
        fecha_solicitud DATE NOT NULL,
        solicitante NVARCHAR(255) NOT NULL,
        area_solicitante NVARCHAR(255) NULL,
        estado NVARCHAR(20) NOT NULL CONSTRAINT df_inv_abast_notas_estado DEFAULT ('PENDIENTE'),
        observaciones NVARCHAR(2000) NULL,
        creado_por NVARCHAR(255) NOT NULL,
        fecha_creacion DATETIME2(0) NOT NULL CONSTRAINT df_inv_abast_notas_fecha DEFAULT (SYSDATETIME()),
        CONSTRAINT ck_inv_abast_notas_estado CHECK (estado IN ('PENDIENTE','EN_ORDEN','ATENDIDA','CANCELADA'))
    );
END;

IF OBJECT_ID('dbo.inv_abast_notas_pedido_detalles', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_abast_notas_pedido_detalles (
        id_detalle INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        nota_id INT NOT NULL,
        item_id INT NOT NULL,
        cantidad_solicitada INT NOT NULL,
        CONSTRAINT ck_inv_abast_notas_det_cantidad CHECK (cantidad_solicitada > 0),
        CONSTRAINT ux_inv_abast_notas_det UNIQUE (nota_id, item_id),
        CONSTRAINT fk_inv_abast_notas_det_nota FOREIGN KEY (nota_id) REFERENCES dbo.inv_abast_notas_pedido(id_nota),
        CONSTRAINT fk_inv_abast_notas_det_item FOREIGN KEY (item_id) REFERENCES dbo.inv_inventario(id)
    );
END;

IF OBJECT_ID('dbo.inv_ordenes_compra', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_ordenes_compra (
        id_orden INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        secuencial NVARCHAR(50) NOT NULL UNIQUE,
        fecha DATE NOT NULL,
        nota_pedido_id INT NULL,
        proveedor_id INT NOT NULL,
        origen NVARCHAR(20) NOT NULL CONSTRAINT df_inv_oc_origen DEFAULT ('MANUAL'),
        estado NVARCHAR(20) NOT NULL CONSTRAINT df_inv_oc_estado DEFAULT ('PENDIENTE'),
        fecha_aprobacion DATETIME2(0) NULL,
        aprobado_por NVARCHAR(255) NULL,
        observaciones NVARCHAR(2000) NULL,
        creado_por NVARCHAR(255) NOT NULL,
        fecha_creacion DATETIME2(0) NOT NULL CONSTRAINT df_inv_oc_fecha DEFAULT (SYSDATETIME()),
        CONSTRAINT ck_inv_oc_origen CHECK (origen IN ('NOTA_PEDIDO','MANUAL','FACTURA')),
        CONSTRAINT ck_inv_oc_estado CHECK (estado IN ('PENDIENTE','APROBADA','CERRADA','CANCELADA')),
        CONSTRAINT fk_inv_oc_nota FOREIGN KEY (nota_pedido_id) REFERENCES dbo.inv_abast_notas_pedido(id_nota),
        CONSTRAINT fk_inv_oc_proveedor FOREIGN KEY (proveedor_id) REFERENCES dbo.inv_proveedores(id)
    );
END;

IF OBJECT_ID('dbo.inv_ordenes_compra_detalles', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_ordenes_compra_detalles (
        id_detalle INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        orden_id INT NOT NULL,
        item_id INT NOT NULL,
        cantidad INT NOT NULL,
        precio_unitario_estimado DECIMAL(28,12) NOT NULL CONSTRAINT df_inv_oc_det_precio DEFAULT (0),
        CONSTRAINT ck_inv_oc_det_cantidad CHECK (cantidad > 0),
        CONSTRAINT ck_inv_oc_det_precio CHECK (precio_unitario_estimado >= 0),
        CONSTRAINT ux_inv_oc_det UNIQUE (orden_id, item_id),
        CONSTRAINT fk_inv_oc_det_orden FOREIGN KEY (orden_id) REFERENCES dbo.inv_ordenes_compra(id_orden),
        CONSTRAINT fk_inv_oc_det_item FOREIGN KEY (item_id) REFERENCES dbo.inv_inventario(id)
    );
END;

IF OBJECT_ID('dbo.inv_facturas', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_facturas (
        id_factura INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        numero_factura NVARCHAR(100) NOT NULL,
        fecha_factura DATE NOT NULL,
        proveedor_id INT NOT NULL,
        orden_compra_id INT NOT NULL,
        iva_porcentaje DECIMAL(7,4) NOT NULL CONSTRAINT df_inv_fac_iva DEFAULT (0),
        base_cero DECIMAL(30,8) NOT NULL CONSTRAINT df_inv_fac_base0 DEFAULT (0),
        subtotal_gravado DECIMAL(30,8) NOT NULL CONSTRAINT df_inv_fac_gravado DEFAULT (0),
        valor_iva DECIMAL(30,8) NOT NULL CONSTRAINT df_inv_fac_valor_iva DEFAULT (0),
        total DECIMAL(30,8) NOT NULL CONSTRAINT df_inv_fac_total DEFAULT (0),
        estado NVARCHAR(20) NOT NULL CONSTRAINT df_inv_fac_estado DEFAULT ('REGISTRADA'),
        creado_por NVARCHAR(255) NOT NULL,
        fecha_creacion DATETIME2(0) NOT NULL CONSTRAINT df_inv_fac_fecha DEFAULT (SYSDATETIME()),
        CONSTRAINT ux_inv_fac_numero_proveedor UNIQUE (proveedor_id, numero_factura),
        CONSTRAINT ux_inv_fac_orden UNIQUE (orden_compra_id),
        CONSTRAINT ck_inv_fac_estado CHECK (estado IN ('REGISTRADA','INGRESADA','ANULADA')),
        CONSTRAINT fk_inv_fac_proveedor FOREIGN KEY (proveedor_id) REFERENCES dbo.inv_proveedores(id),
        CONSTRAINT fk_inv_fac_orden FOREIGN KEY (orden_compra_id) REFERENCES dbo.inv_ordenes_compra(id_orden)
    );
END;

IF OBJECT_ID('dbo.inv_facturas_detalles', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_facturas_detalles (
        id_detalle INT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        factura_id INT NOT NULL,
        item_id INT NOT NULL,
        cantidad INT NOT NULL,
        precio_unitario DECIMAL(28,12) NOT NULL,
        grava_iva BIT NOT NULL CONSTRAINT df_inv_fac_det_grava DEFAULT (1),
        codigo_presupuestario NVARCHAR(100) NULL,
        CONSTRAINT ck_inv_fac_det_cantidad CHECK (cantidad > 0),
        CONSTRAINT ck_inv_fac_det_precio CHECK (precio_unitario >= 0),
        CONSTRAINT ux_inv_fac_det UNIQUE (factura_id, item_id),
        CONSTRAINT fk_inv_fac_det_factura FOREIGN KEY (factura_id) REFERENCES dbo.inv_facturas(id_factura),
        CONSTRAINT fk_inv_fac_det_item FOREIGN KEY (item_id) REFERENCES dbo.inv_inventario(id)
    );
END;

IF COL_LENGTH('dbo.inv_bod_ingresos', 'factura_id') IS NULL
    ALTER TABLE dbo.inv_bod_ingresos ADD factura_id INT NULL;
IF COL_LENGTH('dbo.inv_bod_ingresos', 'orden_compra_id') IS NULL
    ALTER TABLE dbo.inv_bod_ingresos ADD orden_compra_id INT NULL;
IF COL_LENGTH('dbo.inv_bod_ingresos_detalles', 'existencia_anterior') IS NULL
    ALTER TABLE dbo.inv_bod_ingresos_detalles ADD existencia_anterior INT NULL;
IF COL_LENGTH('dbo.inv_bod_ingresos_detalles', 'existencia_nueva') IS NULL
    ALTER TABLE dbo.inv_bod_ingresos_detalles ADD existencia_nueva INT NULL;
IF COL_LENGTH('dbo.inv_bod_ingresos_detalles', 'costo_promedio_actualizado') IS NULL
    ALTER TABLE dbo.inv_bod_ingresos_detalles ADD costo_promedio_actualizado DECIMAL(28,12) NULL;
IF COL_LENGTH('dbo.inv_facturas', 'archivo_nombre_original') IS NULL
    ALTER TABLE dbo.inv_facturas ADD archivo_nombre_original NVARCHAR(255) NULL;
IF COL_LENGTH('dbo.inv_facturas', 'archivo_ruta') IS NULL
    ALTER TABLE dbo.inv_facturas ADD archivo_ruta NVARCHAR(500) NULL;
IF COL_LENGTH('dbo.inv_facturas', 'archivo_mime') IS NULL
    ALTER TABLE dbo.inv_facturas ADD archivo_mime NVARCHAR(100) NULL;
IF COL_LENGTH('dbo.inv_facturas', 'ocr_texto') IS NULL
    ALTER TABLE dbo.inv_facturas ADD ocr_texto NVARCHAR(MAX) NULL;
IF COL_LENGTH('dbo.inv_facturas', 'fecha_escaneo') IS NULL
    ALTER TABLE dbo.inv_facturas ADD fecha_escaneo DATETIME2(0) NULL;

/* Nueva compilacion para que SQL Server 2014 reconozca las columnas agregadas. */
COMMIT TRANSACTION;
GO
BEGIN TRANSACTION;

IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_inv_ingreso_factura')
    ALTER TABLE dbo.inv_bod_ingresos WITH CHECK ADD CONSTRAINT fk_inv_ingreso_factura
        FOREIGN KEY (factura_id) REFERENCES dbo.inv_facturas(id_factura);
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'fk_inv_ingreso_orden')
    ALTER TABLE dbo.inv_bod_ingresos WITH CHECK ADD CONSTRAINT fk_inv_ingreso_orden
        FOREIGN KEY (orden_compra_id) REFERENCES dbo.inv_ordenes_compra(id_orden);
IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'ux_inv_ingreso_factura' AND object_id = OBJECT_ID('dbo.inv_bod_ingresos'))
    CREATE UNIQUE INDEX ux_inv_ingreso_factura ON dbo.inv_bod_ingresos(factura_id) WHERE factura_id IS NOT NULL;

IF NOT EXISTS (SELECT 1 FROM dbo.inv_secuenciales WHERE modulo = 'npa')
    INSERT INTO dbo.inv_secuenciales (modulo, prefijo, ultimo_numero) VALUES ('npa', 'NPA-', 0);
IF NOT EXISTS (SELECT 1 FROM dbo.inv_secuenciales WHERE modulo = 'ocp')
    INSERT INTO dbo.inv_secuenciales (modulo, prefijo, ultimo_numero) VALUES ('ocp', 'OCP-', 0);

COMMIT TRANSACTION;
GO
