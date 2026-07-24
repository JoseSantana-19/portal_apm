IF OBJECT_ID(N'dbo.bit_inv_camaras', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.bit_inv_camaras (
        id_camara INT IDENTITY(1,1) NOT NULL PRIMARY KEY,

        cod_old NVARCHAR(30) NULL,
        codigo NVARCHAR(30) NULL,
        tipo NVARCHAR(80) NULL,
        marca NVARCHAR(80) NULL,
        modelo NVARCHAR(120) NULL,
        tecnologia NVARCHAR(50) NULL,
        caracteristica NVARCHAR(250) NULL,

        ip NVARCHAR(50) NULL,
        mac NVARCHAR(80) NULL,
        serie NVARCHAR(120) NULL,

        ubicacion NVARCHAR(150) NULL,
        detalle NVARCHAR(200) NULL,
        grabador NVARCHAR(120) NULL,

        estado BIT NOT NULL DEFAULT 1,
        fecha_creacion DATETIME NOT NULL DEFAULT GETDATE(),
        fecha_actualizacion DATETIME NULL
    );

    CREATE INDEX IX_inv_camaras_ip
    ON dbo.bit_inv_camaras(ip);

    CREATE INDEX IX_inv_camaras_codigo
    ON dbo.bit_inv_camaras(codigo);

    CREATE INDEX IX_inv_camaras_detalle
    ON dbo.bit_inv_camaras(detalle);
END;
GO

/* =========================================================
   DATOS INICIALES - INVENTARIO DE CÁMARAS
   Tabla: dbo.bit_inv_camaras
========================================================= */

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006088')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006088', NULL, 'Fija + Housing', 'SAMSUNG', 'SNB-5000', 'IP', '1.3MP + housing IP66', '20.20.20.17', '00:16:6c:7a:cb:ec', 'C5E46V2C201018P', 'TPyC', 'Entrada peatonal', 'NVR-Fijas');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006079')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006079', '11827201', 'Fija + Housing', 'SAMSUNG', 'SNB-5000', 'IP', '1.3MP + housing IP66', '20.20.20.19', '00:16:6C:7A:CB:EE', 'C5E46V2C201020J', 'TPyC', 'Salida peatonal', 'NVR-Fijas');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006069')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006069', '11817199', 'Bala', 'Hikvision', 'DS-2CD1023GOE-I', 'IP', '2MP, 2.8MM Leds IR IP67', '20.20.20.15', '40:ac:bf:7a:72:47', '20220308AAWRI58573349', 'TPyC', 'Muelle Internacional Final', 'NVR-Fijas');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006082')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006082', '11817204', 'Bala', 'Dahua', 'DH-IPC-B1E20', 'IP', '23MP Leds IR IP66', '20.20.20.26', 'c4:aa:c4:52:36:45', 'AE03C68PAGB4BED', 'TPyC', 'Escalera Movil 1', 'NVR-Fijas');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006083')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006083', '11817205', 'Bala', 'Hikvision', 'DS-2CD1023GOE-I', 'IP', '2MP, 2.8MM Leds IR IP67', '20.20.20.20', '40:ac:bf:7a:74:0d', '20220308AAWRJ58573803', 'TPyC', 'Muelle Internacional inicio', 'NVR-Fijas');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006073')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006073', '11817225', 'Bala', 'Hikvision', 'DS-2CD1023GOE-I', 'IP', '2MP, 2.8MM Leds IR IP67', '192.168.3.219', '40:ac:bf:7a:74:80', '20220308AAWRJ58573918', 'TPyC', 'Puerta 3', 'NVR-Fijas');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006080')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006080', '11817202', 'Bala', 'Hikvision', 'DS-2CD1023GOE-I', 'IP', '2MP, 2.8MM Leds IR IP67', '20.20.20.31', '40:ac:bf:7a:78:ac', '20220308AAWRJ58574986', 'TPyC', 'Puerta 5', 'NVR-Fijas');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006086')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006086', '11817208', 'Bala', 'Hikvision', 'DS-2CD1023GOE-I', 'IP', '2MP, 2.8MM Leds IR IP67', '192.168.3.220', '2c:a5:9c:7d:6d:91', '20210127AAWRF48313096', 'TPyC', 'Kiosco-Parqueadero / Puerta 3', 'NVR-Fijas');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006060')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006060', '11817241', 'DOMO-PTZ', 'Hikvision', 'DS-2DE7232IW-AE', 'IP', '32X, 2MP, PoE, IP66 con soporte', '20.20.20.40', 'f8:4d:fc:71:b8:dc', '20181229AAWRC83155068W', 'TPyC', 'PTZ2 Puerta 4', 'NVR-PTZ');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006066')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006066', '11817247', 'DOMO-PTZ', 'Hikvision', 'DS-2DE7232IW-AE', 'IP', '32X, 2MP, PoE, IP66 con soporte', '20.20.20.56', 'f8:4d:fc:71:b8:eb', '20181229AAWRC83155083W', 'TPyC', 'PTZ1 Puerta 5', 'NVR-PTZ');
END;


/* Cámaras del Edificio Administrativo */

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006884')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006884', NULL, 'Bala', 'Dahua', 'DH-IPC-HFW1200DN', 'ANALOGA', '2MP, 3.6MM Leds IR IP66', 'n/a', 'n/a', '4E07407PAF472A6', 'Edif Administrativo', 'Balcon Servicios', 'DVR:Adm');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006886')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006886', NULL, 'Bala', 'Dahua', 'DH-IPC-HFW1200DN', 'ANALOGA', '2MP, 3.6MM Leds IR IP66', 'n/a', 'n/a', '4A06246PAF43A5A', 'Edif Administrativo', 'Escalera, Piso 2', 'DVR:Adm');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006893')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006893', NULL, 'Bala', 'Dahua', 'DH-IPC-HFW1200DN', 'ANALOGA', '2MP, 3.6MM Leds IR IP66', 'n/a', 'n/a', '4A06246PAFFd65E', 'Edif Administrativo', 'Escalera, Piso 3', 'DVR:Adm');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006881')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006881', NULL, 'Bala', 'Dahua', 'DH-IPC-HFW1200DN', 'ANALOGA', '2MP, 3.6MM Leds IR IP66', 'n/a', 'n/a', '4E07407PAFAC3A2', 'Edif Administrativo', 'Escalera, Piso 4', 'DVR:Adm');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006894')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006894', NULL, 'Bala', 'Dahua', 'DH-IPC-HFW1200DN', 'ANALOGA', '2MP, 3.6MM Leds IR IP66', 'n/a', 'n/a', '4A06246PAF4FF0C', 'Edif Administrativo', 'Oficina CCTV pasillo frente, Piso 3', 'DVR:Adm');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006895')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006895', NULL, 'Bala', 'Dahua', 'DH-IPC-HFW1200DN', 'ANALOGA', '2MP, 3.6MM Leds IR IP66', 'n/a', 'n/a', '4A06246PAF64186', 'Edif Administrativo', 'Pasillo CCTV piso 3', 'DVR:Adm');
END;

IF NOT EXISTS (SELECT 1 FROM dbo.bit_inv_camaras WHERE cod_old = '006888')
BEGIN
    INSERT INTO dbo.bit_inv_camaras
    (cod_old, codigo, tipo, marca, modelo, tecnologia, caracteristica, ip, mac, serie, ubicacion, detalle, grabador)
    VALUES
    ('006888', NULL, 'Bala', 'Dahua', 'DH-IPC-HFW1200DN', 'ANALOGA', '2MP, 3.6MM Leds IR IP66', 'n/a', 'n/a', '4e07407pafa3341', 'Edif Administrativo', 'Pasillo Financiero', 'DVR:Adm');
END;
GO