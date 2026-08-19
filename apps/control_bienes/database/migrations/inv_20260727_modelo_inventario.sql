/*
  Modelo de inventario v2 - SQL Server Enterprise 2014
  Migracion idempotente. No elimina ni transforma datos existentes.
*/
SET XACT_ABORT ON;
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
SET ANSI_PADDING ON;
SET ANSI_WARNINGS ON;
SET CONCAT_NULL_YIELDS_NULL ON;
SET ARITHABORT ON;
SET NUMERIC_ROUNDABORT OFF;
BEGIN TRANSACTION;

IF COL_LENGTH('dbo.inv_productos', 'tipo_bien') IS NULL
    ALTER TABLE dbo.inv_productos ADD tipo_bien CHAR(2) NOT NULL
        CONSTRAINT df_inv_productos_tipo_bien DEFAULT ('CC');

IF COL_LENGTH('dbo.inv_inventario', 'tipo_bien') IS NULL
    ALTER TABLE dbo.inv_inventario ADD tipo_bien CHAR(2) NOT NULL
        CONSTRAINT df_inv_inventario_tipo_bien DEFAULT ('CC');

IF COL_LENGTH('dbo.inv_productos', 'secuencial_numero') IS NULL
    ALTER TABLE dbo.inv_productos ADD secuencial_numero BIGINT NULL;

IF COL_LENGTH('dbo.inv_productos', 'codigo_legacy') IS NULL
    ALTER TABLE dbo.inv_productos ADD codigo_legacy NVARCHAR(50) NULL;

/* Fuerza una nueva compilacion para que SQL Server 2014 reconozca las
   columnas recien agregadas en las instrucciones siguientes. */
COMMIT TRANSACTION;
GO
BEGIN TRANSACTION;

/* Clasificacion inicial usando los grupos contables historicos de activos (1.4.x). */
UPDATE p
SET p.tipo_bien = 'AF'
FROM dbo.inv_productos p
INNER JOIN dbo.inv_categorias c ON c.id = p.grupo_id
WHERE c.codigo LIKE '1.4.%';

IF NOT EXISTS (SELECT 1 FROM sys.check_constraints WHERE name = 'ck_inv_productos_tipo_bien')
    ALTER TABLE dbo.inv_productos ADD CONSTRAINT ck_inv_productos_tipo_bien
        CHECK (tipo_bien IN ('CC', 'AF'));

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'ux_inv_productos_codigo' AND object_id = OBJECT_ID('dbo.inv_productos'))
    CREATE UNIQUE INDEX ux_inv_productos_codigo ON dbo.inv_productos(codigo) WHERE codigo IS NOT NULL AND codigo <> '';

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'ux_inv_productos_tipo_secuencia' AND object_id = OBJECT_ID('dbo.inv_productos'))
    CREATE UNIQUE INDEX ux_inv_productos_tipo_secuencia ON dbo.inv_productos(tipo_bien, secuencial_numero)
        WHERE secuencial_numero IS NOT NULL;

IF OBJECT_ID('dbo.inv_activos_fijos', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_activos_fijos (
        id_activo BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        id_producto INT NOT NULL,
        codigo_activo NVARCHAR(50) NOT NULL,
        serie NVARCHAR(100) NULL,
        modelo NVARCHAR(100) NULL,
        marca NVARCHAR(100) NULL,
        fecha_adquisicion DATE NULL,
        id_responsable INT NULL,
        id_centro_consumo INT NULL,
        estado NVARCHAR(20) NOT NULL CONSTRAINT df_inv_activos_estado DEFAULT ('ACTIVO'),
        fecha_baja DATE NULL,
        motivo_baja NVARCHAR(500) NULL,
        codigo_legacy NVARCHAR(50) NULL,
        fecha_creacion DATETIME2(0) NOT NULL CONSTRAINT df_inv_activos_fecha DEFAULT (SYSDATETIME()),
        CONSTRAINT ux_inv_activos_codigo UNIQUE (codigo_activo),
        CONSTRAINT ck_inv_activos_estado CHECK (estado IN ('ACTIVO','MANTENIMIENTO','BAJA')),
        CONSTRAINT fk_inv_activos_producto FOREIGN KEY (id_producto) REFERENCES dbo.inv_productos(id),
        CONSTRAINT fk_inv_activos_responsable FOREIGN KEY (id_responsable) REFERENCES dbo.inv_talento_personal(id)
    );
END;

IF OBJECT_ID('dbo.inv_terceros', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_terceros (
        id_tercero BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        tipo NVARCHAR(20) NOT NULL,
        tipo_identificacion NVARCHAR(20) NOT NULL CONSTRAINT df_inv_terceros_tipo_id DEFAULT ('RUC'),
        identificacion NVARCHAR(20) NOT NULL,
        nombre_razon_social NVARCHAR(250) NOT NULL,
        nombre_comercial NVARCHAR(250) NULL,
        direccion NVARCHAR(500) NULL,
        telefono NVARCHAR(50) NULL,
        correo NVARCHAR(150) NULL,
        origen NVARCHAR(20) NOT NULL CONSTRAINT df_inv_terceros_origen DEFAULT ('LOCAL'),
        referencia_externa_id NVARCHAR(100) NULL,
        fecha_ultima_verificacion DATETIME2(0) NULL,
        estado BIT NOT NULL CONSTRAINT df_inv_terceros_estado DEFAULT (1),
        fecha_creacion DATETIME2(0) NOT NULL CONSTRAINT df_inv_terceros_fecha DEFAULT (SYSDATETIME()),
        CONSTRAINT ux_inv_terceros_identificacion UNIQUE (identificacion),
        CONSTRAINT ck_inv_terceros_tipo CHECK (tipo IN ('FUNCIONARIO','PROVEEDOR','OTRO')),
        CONSTRAINT ck_inv_terceros_origen CHECK (origen IN ('LOCAL','TALENTO_HUMANO','SRI'))
    );
END;

IF OBJECT_ID('dbo.inv_cierres_periodo', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_cierres_periodo (
        id_cierre BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        id_periodo INT NOT NULL,
        fecha_cierre DATETIME2(0) NOT NULL CONSTRAINT df_inv_cierres_fecha DEFAULT (SYSDATETIME()),
        id_usuario INT NULL,
        estado NVARCHAR(20) NOT NULL CONSTRAINT df_inv_cierres_estado DEFAULT ('EN_PROCESO'),
        total_consumo_corriente DECIMAL(19,4) NOT NULL CONSTRAINT df_inv_cierres_total_cc DEFAULT (0),
        total_activos_fijos DECIMAL(19,4) NOT NULL CONSTRAINT df_inv_cierres_total_af DEFAULT (0),
        cantidad_items_consumo DECIMAL(19,4) NOT NULL CONSTRAINT df_inv_cierres_cantidad_cc DEFAULT (0),
        cantidad_activos BIGINT NOT NULL CONSTRAINT df_inv_cierres_cantidad_af DEFAULT (0),
        observaciones NVARCHAR(1000) NULL,
        CONSTRAINT ux_inv_cierres_periodo UNIQUE (id_periodo),
        CONSTRAINT ck_inv_cierres_estado CHECK (estado IN ('EN_PROCESO','CERRADO','REABIERTO','ANULADO')),
        CONSTRAINT fk_inv_cierres_periodo FOREIGN KEY (id_periodo) REFERENCES dbo.inv_periodos(id),
        CONSTRAINT fk_inv_cierres_usuario FOREIGN KEY (id_usuario) REFERENCES dbo.inv_usuarios(id)
    );
END;

IF OBJECT_ID('dbo.inv_cierre_saldos', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.inv_cierre_saldos (
        id_saldo BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY,
        id_cierre BIGINT NOT NULL,
        id_producto INT NOT NULL,
        tipo_bien CHAR(2) NOT NULL,
        codigo NVARCHAR(50) NOT NULL,
        descripcion NVARCHAR(500) NOT NULL,
        cantidad DECIMAL(19,4) NOT NULL,
        precio_promedio DECIMAL(19,4) NOT NULL,
        valor_total DECIMAL(19,4) NOT NULL,
        id_centro_consumo INT NULL,
        id_responsable INT NULL,
        fecha_registro DATETIME2(0) NOT NULL CONSTRAINT df_inv_cierre_saldos_fecha DEFAULT (SYSDATETIME()),
        CONSTRAINT ux_inv_cierre_saldos_item UNIQUE (id_cierre, id_producto),
        CONSTRAINT ck_inv_cierre_saldos_tipo CHECK (tipo_bien IN ('CC','AF')),
        CONSTRAINT ck_inv_cierre_saldos_responsable CHECK (tipo_bien = 'AF' OR id_responsable IS NULL),
        CONSTRAINT fk_inv_cierre_saldos_cierre FOREIGN KEY (id_cierre) REFERENCES dbo.inv_cierres_periodo(id_cierre),
        CONSTRAINT fk_inv_cierre_saldos_producto FOREIGN KEY (id_producto) REFERENCES dbo.inv_productos(id)
    );
END;

IF COL_LENGTH('dbo.inv_secuenciales', 'estado') IS NULL
    ALTER TABLE dbo.inv_secuenciales ADD estado BIT NOT NULL
        CONSTRAINT df_inv_secuenciales_estado DEFAULT (1);

IF COL_LENGTH('dbo.inv_secuenciales', 'longitud') IS NULL
    ALTER TABLE dbo.inv_secuenciales ADD longitud TINYINT NOT NULL
        CONSTRAINT df_inv_secuenciales_longitud DEFAULT (6);

IF COL_LENGTH('dbo.inv_secuenciales', 'reinicia_por_periodo') IS NULL
    ALTER TABLE dbo.inv_secuenciales ADD reinicia_por_periodo BIT NOT NULL
        CONSTRAINT df_inv_secuenciales_reinicio DEFAULT (0);

COMMIT TRANSACTION;
GO

IF OBJECT_ID('dbo.sp_inv_buscar_funcionario', 'P') IS NOT NULL
    DROP PROCEDURE dbo.sp_inv_buscar_funcionario;
GO

CREATE PROCEDURE dbo.sp_inv_buscar_funcionario
    @identificacion NVARCHAR(50) = NULL,
    @nombre NVARCHAR(255) = NULL,
    @solo_activos BIT = 1
AS
BEGIN
    SET NOCOUNT ON;
    SELECT p.id AS id_funcionario, p.identificacion, p.nombre,
           a.id AS id_area, a.nombre AS area
    FROM dbo.inv_talento_personal p
    LEFT JOIN dbo.inv_talento_asignaciones ta
      ON ta.personal_id = p.id AND ta.fecha_fin IS NULL
    LEFT JOIN dbo.inv_talento_areas a ON a.id = ta.area_id
    WHERE (@identificacion IS NULL OR p.identificacion = @identificacion)
      AND (@nombre IS NULL OR p.nombre LIKE '%' + @nombre + '%')
      AND (@solo_activos = 0 OR ta.fecha_fin IS NULL)
    ORDER BY p.nombre;
END;
GO
