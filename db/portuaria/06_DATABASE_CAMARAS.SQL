IF OBJECT_ID(N'dbo.bit_camaras', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_camaras (
        id_bitacora_camara INT IDENTITY(1,1) PRIMARY KEY,

        fecha DATE NOT NULL,
        secuencia NVARCHAR(100) NULL,
        turno NVARCHAR(50) NULL,
        hora_inicio TIME NULL,
        hora_fin TIME NULL,
        consolista NVARCHAR(150) NULL,

        hora_registro TIME NOT NULL,
        novedad NVARCHAR(MAX) NULL,
        camara_ip NVARCHAR(50) NULL,
        ubicacion NVARCHAR(150) NULL,
        sitio NVARCHAR(80) NULL,
        estado_camara NVARCHAR(20) NOT NULL,
        observaciones NVARCHAR(MAX) NULL,

        usuario_registro NVARCHAR(150) NULL,
        estado BIT NOT NULL DEFAULT 1,
        fecha_creacion DATETIME NOT NULL DEFAULT GETDATE(),
        fecha_actualizacion DATETIME NULL,

        CONSTRAINT CK_bitacora_camaras_estado_camara
        CHECK (estado_camara IN ('OPER', 'NO OPER'))
    );
END;
GO