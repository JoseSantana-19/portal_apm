/*
  05_DATABASE_DATOS_MAESTROS.sql
  Seeds y parámetros del sistema.
*/

USE PortuariaDemo;
GO
SET NOCOUNT ON;
GO

INSERT INTO dbo.bit_departamentos (nom_departa, nota, estado)
SELECT v.nom_departa, v.nota, 1
FROM (VALUES
    (N'TECNOLOGIA DE LA INFORMACION', NULL),
    (N'EDIFICIO ADMINISTRATIVO', NULL),
    (N'ASESORIA JURIDICA', NULL),
    (N'SEGURIDAD INTEGRAL', NULL),
    (N'OPERACIONES PORTUARIAS', NULL),
    (N'GERENCIA', NULL),
    (N'ARCHIVO CENTRAL', NULL),
    (N'TESORERIA', NULL)
) AS v(nom_departa, nota)
WHERE NOT EXISTS (
    SELECT 1
    FROM dbo.bit_departamentos d
    WHERE UPPER(LTRIM(RTRIM(d.nom_departa))) = UPPER(LTRIM(RTRIM(v.nom_departa)))
);
GO

IF NOT EXISTS (SELECT 1 FROM dbo.bit_usuarios_apm)
BEGIN
    INSERT INTO dbo.bit_usuarios_apm (cedula, nombres, id_departamento, estado)
    SELECT N'1301234567', N'Usuario Gerencia', iddepart, 1
    FROM dbo.bit_departamentos WHERE nom_departa = N'GERENCIA';

    INSERT INTO dbo.bit_usuarios_apm (cedula, nombres, id_departamento, estado)
    SELECT N'1307654321', N'Usuario Seguridad Integral', iddepart, 1
    FROM dbo.bit_departamentos WHERE nom_departa = N'SEGURIDAD INTEGRAL';

    INSERT INTO dbo.bit_usuarios_apm (cedula, nombres, id_departamento, estado)
    SELECT N'1302223344', N'Usuario Tesoreria', iddepart, 1
    FROM dbo.bit_departamentos WHERE nom_departa = N'TESORERIA';
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.bit_niveles_incidente)
BEGIN
    INSERT INTO dbo.bit_niveles_incidente (nivel, descripcion, estado) VALUES
        (1, N'Normal', 1),
        (2, N'Medio', 1),
        (3, N'Crítico', 1);
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.bit_empresas)
BEGIN
    INSERT INTO dbo.bit_empresas (empresa, razonsocial, ruc, estado) VALUES
        (N'Naviera Manta S.A.',            N'Naviera Manta Sociedad Anónima',          N'1790012345001', 1),
        (N'Logística Pacífico',            N'Logística Pacífico Cía. Ltda.',            N'1790012345002', 1),
        (N'Seguridad Portuaria Andina',    N'Seguridad Portuaria Andina S.A.',         N'1790012345003', 1),
        (N'Servimar Operaciones',          N'Servimar Operaciones S.A.',               N'1790012345004', 1),
        (N'Empresarios Unidos del Mar',    N'Empresarios Unidos del Mar Cía. Ltda.',   N'1790012345005', 1);
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.bit_personas)
BEGIN
    INSERT INTO dbo.bit_personas (nidentificacion, tidentif, nombres, apellidos, estado) VALUES
        (N'1311001001', N'Cédula', N'Luis',      N'García Mora', 1),
        (N'1311001002', N'Cédula', N'Ana',       N'Torres Salazar', 1),
        (N'1311001003', N'Cédula', N'Pedro',     N'Martínez Vera', 1),
        (N'1311001004', N'Cédula', N'José',      N'López Intriago', 1),
        (N'1311001005', N'Cédula', N'Marta',     N'Díaz Zambrano', 1),
        (N'1311001006', N'Cédula', N'Carolina',  N'Vera Pincay', 1),
        (N'1311001007', N'Cédula', N'Juan',      N'Rivera Cedeño', 1),
        (N'1311001008', N'Cédula', N'Pablo',     N'Castillo Ruiz', 1),
        (N'1311001009', N'Cédula', N'Gloria',    N'Paredes Baque', 1),
        (N'1311001010', N'Cédula', N'Ricardo',   N'Peña Delgado', 1);
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.bit_funcionarios)
BEGIN
    INSERT INTO dbo.bit_funcionarios (nombre, cargo, estado) VALUES
        (N'Ing. Carlos Zambrano', N'Gerente General', 1),
        (N'Lcda. María López',   N'Jefe de Talento Humano', 1),
        (N'Econ. Juan Pérez',    N'Jefe Financiero', 1);
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.bit_destinos)
BEGIN
    INSERT INTO dbo.bit_destinos (nombre, estado) VALUES
        (N'Gerencia General', 1),
        (N'Talento Humano', 1),
        (N'Finanzas', 1),
        (N'Sistemas', 1),
        (N'Bodega / Logística', 1);
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.bit_motivos)
BEGIN
    INSERT INTO dbo.bit_motivos (descripcion, estado) VALUES
        (N'Reunión de trabajo', 1),
        (N'Entrega de documentos', 1),
        (N'Mantenimiento', 1),
        (N'Consulta administrativa', 1),
        (N'Proveedor de servicios', 1);
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.bit_visitas)
BEGIN
    SET DATEFORMAT dmy;
    INSERT INTO dbo.bit_visitas
        (id_persona, tipo_visitante, id_empresa, id_funcionario, id_destino, id_motivo, id_nivel_incidente,
         fecha_visita, hora_entrada, hora_salida, estado)
    VALUES
        (1, N'Empresa',  1, 1, 1, 1, 1, '04/03/2026', '08:15', '09:05', 1),
        (2, N'Empresa',  2, 2, 2, 2, 2, '04/03/2026', '08:30', NULL, 1),
        (3, N'Personal', NULL, 3, 3, 3, 1, '04/03/2026', '09:00', NULL, 1),
        (4, N'Empresa',  3, 1, 4, 4, 3, '03/03/2026', '10:10', '11:00', 1),
        (5, N'Empresa',  4, 2, 5, 5, 1, '03/03/2026', '09:45', '10:30', 1),
        (6, N'Personal', NULL, 1, 1, 2, 2, '02/03/2026', '15:20', NULL, 1),
        (7, N'Empresa',  5, 3, 2, 1, 1, '02/03/2026', '14:00', '15:00', 1),
        (8, N'Empresa',  1, 2, 3, 3, 2, '01/03/2026', '08:05', '08:50', 1),
        (9, N'Empresa',  2, 1, 4, 4, 3, '01/03/2026', '09:30', '10:10', 1),
        (10, N'Personal', NULL, 3, 5, 5, 1, '28/02/2026', '11:00', '11:40', 1);
END
GO

IF OBJECT_ID(N'dbo.apm_aplicar_passwords_demo', N'P') IS NOT NULL
    EXEC dbo.apm_aplicar_passwords_demo;
GO

IF NOT EXISTS (SELECT 1 FROM dbo.bit_niveles_alerta WHERE id_alerta = 1)
BEGIN
    INSERT INTO dbo.bit_niveles_alerta (id_alerta, descripcion, color_hex, estado) VALUES
    (1, N'Normal',  N'#6c757d', 1),
    (2, N'Medio',   N'#ffc107', 1),
    (3, N'Crítico', N'#dc3545', 1);
END
GO

IF NOT EXISTS (
    SELECT 1
    FROM sys.tables
    WHERE name = N'parametro'
      AND schema_id = SCHEMA_ID(N'dbo')
)
BEGIN
    CREATE TABLE dbo.bit_parametro (
        nombre VARCHAR(100) NOT NULL PRIMARY KEY,
        valor VARCHAR(255) NOT NULL
    );
END
GO

IF EXISTS (
    SELECT 1 FROM sys.tables WHERE name = N'configuraciones_sistema' AND schema_id = SCHEMA_ID(N'dbo')
)
AND EXISTS (
    SELECT 1 FROM dbo.configuraciones_sistema WHERE parametro = 'dias_edicion_bitacora'
)
AND NOT EXISTS (SELECT 1 FROM dbo.bit_parametro WHERE nombre = 'dias_edicion')
BEGIN
    INSERT INTO dbo.bit_parametro (nombre, valor)
    SELECT 'dias_edicion', valor
    FROM dbo.configuraciones_sistema
    WHERE parametro = 'dias_edicion_bitacora';
END
GO

IF NOT EXISTS (
    SELECT 1
    FROM dbo.bit_parametro
    WHERE nombre = 'dias_edicion'
)
BEGIN
    INSERT INTO dbo.bit_parametro (nombre, valor)
    VALUES ('dias_edicion', '1');
END
GO

PRINT '05_DATABASE_DATOS_MAESTROS.sql ejecutado correctamente.';
GO

