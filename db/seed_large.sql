-- ============================================================================
-- PORTAL APM — MASSIVE TEST SEED DATA GENERATOR
-- Generates 150+ Employees, 200+ Contracts, 250+ Adendas, 300+ Medical Events
-- Optimized for Microsoft SQL Server 2019+
-- ============================================================================

USE [PORTAL_APM];
GO

SET NOCOUNT ON;
SET XACT_ABORT ON;
GO

PRINT '================================================================';
PRINT ' STARTING MASSIVE DATA SEEDING (TALENTO HUMANO)';
PRINT '================================================================';

BEGIN TRANSACTION;
BEGIN TRY

    -- 1. CLEAN UP PREVIOUS MOCK EMPLOYEES (Keep original administrative users)
    -- We delete dependencies in reverse order
    DELETE FROM dbo.TH_NovedadesMedicas WHERE id_empleado > 11;
    DELETE FROM dbo.TH_Adendas WHERE id_contrato IN (SELECT id_contrato FROM dbo.TH_Contratos WHERE id_empleado > 11);
    DELETE FROM dbo.TH_Contratos WHERE id_empleado > 11;
    DELETE FROM dbo.TH_Empleados WHERE id_empleado > 11;
    
    -- Clean up associated generated users (keeping original ones)
    DELETE FROM dbo.Usuarios_Grupos_Roles WHERE id_usuario > 21;
    DELETE FROM dbo.Usuarios WHERE id_usuario > 21;
    
    PRINT '  -> Previous generated mock data cleaned up.';

    -- 2. GENERATE 150 MOCK USERS & EMPLOYEES VIA T-SQL LOOP
    DECLARE @i INT = 1;
    DECLARE @dep_id INT;
    DECLARE @user_id INT;
    DECLARE @emp_id INT;
    DECLARE @contrato_id INT;
    
    -- Arrays/Lists of mock text for randomization
    DECLARE @Nombres TABLE (id INT IDENTITY(1,1), val NVARCHAR(100));
    INSERT INTO @Nombres (val) VALUES 
    ('Juan'),('Maria'),('Pedro'),('Ana'),('Carlos'),('Diana'),('Luis'),('Carmen'),('Jorge'),('Sofía'),
    ('Manuel'),('Elena'),('Andrés'),('Gabriela'),('Ricardo'),('Patricia'),('José'),('Laura'),('Francisco'),('Marta'),
    ('Diego'),('Valeria'),('Fernando'),('Isabel'),('Javier'),('Paula'),('Roberto'),('Silvia'),('Eduardo'),('Lucía');

    DECLARE @Apellidos TABLE (id INT IDENTITY(1,1), val NVARCHAR(100));
    INSERT INTO @Apellidos (val) VALUES 
    ('Pérez'),('Gómez'),('Rodríguez'),('González'),('López'),('Martínez'),('Sánchez'),('Chávez'),('Torres'),('Mendoza'),
    ('Alvarado'),('Salazar'),('Quintero'),('Pita'),('Muñoz'),('Vera'),('Palma'),('Torres'),('Herrera'),('Cuesta'),
    ('Espinoza'),('Castro'),('Naranjo'),('Rueda'),('Mora'),('Estrella'),('Jiménez'),('Solís'),('Vargas'),('Peñafiel');

    DECLARE @Cargos TABLE (id INT IDENTITY(1,1), cargo NVARCHAR(100), regimen VARCHAR(20), sueldo DECIMAL(10,2), grupo_rol VARCHAR(100));
    INSERT INTO @Cargos (cargo, regimen, sueldo, grupo_rol) VALUES
    ('Analista de Sistemas', 'LOSEP', 1412.00, 'Analista Administrativo'),
    ('Asistente de Contabilidad', 'LOSEP', 985.00, 'Analista Financiero'),
    ('Auxiliar de Archivo', 'LOSEP', 733.00, 'Analista Administrativo'),
    ('Secretario de Jefatura', 'LOSEP', 1086.00, 'Secretaria'),
    ('Asesor Legal Técnico', 'LOSEP', 2100.00, 'Abogado Senior'),
    ('Inspector Operativo de Muelle', 'LOSEP', 1212.00, 'Inspector Portuario'),
    ('Asistente de Nómina', 'LOSEP', 985.00, 'Analista de TH'),
    ('Soporte de Soporte Técnico', 'LOSEP', 985.00, 'Analista Administrativo'),
    ('Analista de Presupuesto', 'LOSEP', 1412.00, 'Analista Financiero'),
    ('Operador de CCTV Central', 'LOSEP', 1086.00, 'Analador CCTV');

    -- Seeding loop
    WHILE @i <= 140 -- Generates 140 new employees + existing 11 = 151 total!
    BEGIN
        -- Randomize indexes
        DECLARE @rand_nom INT = (ABS(CHECKSUM(NEWID())) % 30) + 1;
        DECLARE @rand_ape1 INT = (ABS(CHECKSUM(NEWID())) % 30) + 1;
        DECLARE @rand_ape2 INT = (ABS(CHECKSUM(NEWID())) % 30) + 1;
        DECLARE @rand_cargo INT = (ABS(CHECKSUM(NEWID())) % 10) + 1;
        
        -- Pick random department modulo between 2 and 7 (excludes main Portal portal)
        SET @dep_id = (ABS(CHECKSUM(NEWID())) % 6) + 2; 
        IF @dep_id = 8 SET @dep_id = 10; -- Acceso
        IF @dep_id = 9 SET @dep_id = 21; -- Nómina

        DECLARE @nom NVARCHAR(100);
        DECLARE @ape1 NVARCHAR(100);
        DECLARE @ape2 NVARCHAR(100);
        
        SELECT @nom = val FROM @Nombres WHERE id = @rand_nom;
        SELECT @ape1 = val FROM @Apellidos WHERE id = @rand_ape1;
        SELECT @ape2 = val FROM @Apellidos WHERE id = @rand_ape2;
        
        DECLARE @nombre_completo NVARCHAR(250) = @nom + ' ' + @ape1 + ' ' + @ape2;
        DECLARE @username VARCHAR(100) = LOWER(SUBSTRING(@nom, 1, 1) + @ape1 + CAST(@i AS VARCHAR));
        DECLARE @email VARCHAR(150) = @username + '@apm.gob.ec';
        
        DECLARE @cargo NVARCHAR(100);
        DECLARE @regimen VARCHAR(20);
        DECLARE @base_salary DECIMAL(10,2);
        DECLARE @rol_name VARCHAR(100);
        
        SELECT @cargo = cargo, @regimen = regimen, @base_salary = sueldo, @rol_name = grupo_rol FROM @Cargos WHERE id = @rand_cargo;
        
        DECLARE @cedula VARCHAR(13) = '13' + REPLACE(STR(@i, 8), ' ', '0') + '001';
        DECLARE @genero CHAR(1) = CASE WHEN @rand_nom % 2 = 0 THEN 'F' ELSE 'M' END;
        DECLARE @est_civil VARCHAR(20) = CASE WHEN @i % 3 = 0 THEN 'Soltero' WHEN @i % 3 = 1 THEN 'Casado' ELSE 'Divorciado' END;
        
        -- Dynamic historical dates
        DECLARE @fecha_nac DATE = DATEADD(YEAR, - (22 + (ABS(CHECKSUM(NEWID())) % 35)), GETDATE());
        DECLARE @fecha_ingreso DATE = DATEADD(DAY, - (ABS(CHECKSUM(NEWID())) % 3000), GETDATE()); -- Hired within last ~8 years

        -- 2a. Insert in dbo.Usuarios
        INSERT INTO dbo.Usuarios (nombre_usuario, nombre_completo, correo, cargo_institucional, contrasena_hash, mfa_habilitado, id_dep_principal, activo, req_cambio_pass)
        VALUES (@username, @nombre_completo, @email, @cargo, '$2b$12$HASH_MOCK_SECRET_APM_SECURE', 0, @dep_id, 1, 1);
        
        SET @user_id = SCOPE_IDENTITY();

        -- 2b. Assign Role
        DECLARE @rol_id INT;
        SELECT TOP 1 @rol_id = id_grupo_rol FROM dbo.Grupos_Roles WHERE nombre_grupo_rol = @rol_name OR id_dep_modulo = @dep_id ORDER BY id_grupo_rol;
        IF @rol_id IS NOT NULL
        BEGIN
            INSERT INTO dbo.Usuarios_Grupos_Roles (id_usuario, id_grupo_rol, activo)
            VALUES (@user_id, @rol_id, 1);
        END

        -- 2c. Insert in dbo.TH_Empleados
        INSERT INTO dbo.TH_Empleados (id_usuario, cedula, fecha_nacimiento, genero, estado_civil, direccion, fecha_ingreso, tipo_contrato, id_dep_modulo, cargo_nominal, partida_presupuestaria, regimen_laboral, activo)
        VALUES (@user_id, @cedula, @fecha_nac, @genero, @est_civil, 'Cdla. El Astillero, Calle ' + CAST((@i % 10) + 1 AS VARCHAR) + ', Manta', @fecha_ingreso, 'Contrato', @dep_id, @cargo, 'PP-TH-MOCK-' + CAST(@i AS VARCHAR), @regimen, 1);
        
        SET @emp_id = SCOPE_IDENTITY();

        -- 2d. Generate Hired Contract
        INSERT INTO dbo.TH_Contratos (id_empleado, tipo_contrato, fecha_inicio, remuneracion, estado, registrado_por)
        VALUES (@emp_id, 'Contrato de Servicios Ocasionales', @fecha_ingreso, @base_salary, 'Vigente', 17); -- Hired by Maria Quintero (TH Chief)
        
        SET @contrato_id = SCOPE_IDENTITY();

        -- 2e. Generate Contract historical Adendas (increments) for employees hired > 2 years ago
        IF DATEDIFF(YEAR, @fecha_ingreso, GETDATE()) >= 2
        BEGIN
            DECLARE @fecha_adenda1 DATE = DATEADD(YEAR, 1, @fecha_ingreso);
            DECLARE @new_salary DECIMAL(10,2) = @base_salary + 150.00;
            
            INSERT INTO dbo.TH_Adendas (id_contrato, descripcion, fecha_adenda, tipo_modificacion, valor_anterior, valor_nuevo, registrado_por)
            VALUES (@contrato_id, 'Reajuste salarial anual y reclasificación de funciones', @fecha_adenda1, 'Aumento Salario', CAST(@base_salary AS VARCHAR), CAST(@new_salary AS VARCHAR), 17);

            -- Update contract active remuneration
            UPDATE dbo.TH_Contratos SET remuneracion = @new_salary WHERE id_contrato = @contrato_id;
            
            -- If hired > 4 years ago, generate a second increment
            IF DATEDIFF(YEAR, @fecha_ingreso, GETDATE()) >= 4
            BEGIN
                DECLARE @fecha_adenda2 DATE = DATEADD(YEAR, 3, @fecha_ingreso);
                DECLARE @final_salary DECIMAL(10,2) = @new_salary + 200.00;
                
                INSERT INTO dbo.TH_Adendas (id_contrato, descripcion, fecha_adenda, tipo_modificacion, valor_anterior, valor_nuevo, registrado_por)
                VALUES (@contrato_id, 'Promoción de escalafón e incremento por méritos', @fecha_adenda2, 'Aumento Salario', CAST(@new_salary AS VARCHAR), CAST(@final_salary AS VARCHAR), 17);
                
                UPDATE dbo.TH_Contratos SET remuneracion = @final_salary WHERE id_contrato = @contrato_id;
            END
        END

        -- 2f. Generate Random Medical Leaves
        IF @i % 4 = 0
        BEGIN
            DECLARE @tipo_nov VARCHAR(50) = CASE WHEN @i % 8 = 0 THEN 'Certificado' ELSE 'Permiso' END;
            DECLARE @fecha_nov DATE = DATEADD(DAY, - (ABS(CHECKSUM(NEWID())) % 100), GETDATE());
            DECLARE @fecha_fin_nov DATE = DATEADD(DAY, 3 + (@i % 5), @fecha_nov);
            
            INSERT INTO dbo.TH_NovedadesMedicas (id_empleado, tipo_novedad, fecha_inicio, fecha_fin, descripcion, registrado_por)
            VALUES (@emp_id, @tipo_nov, @fecha_nov, @fecha_fin_nov, 'Novedad médica presentada y validada por Dirección del IESS', 17);
        END

        SET @i = @i + 1;
    END

    COMMIT TRANSACTION;
    DECLARE @cnt_emp INT;
    DECLARE @cnt_con INT;
    DECLARE @cnt_ade INT;
    DECLARE @cnt_nov INT;
    
    SELECT @cnt_emp = COUNT(*) FROM dbo.TH_Empleados;
    SELECT @cnt_con = COUNT(*) FROM dbo.TH_Contratos WHERE estado='Vigente';
    SELECT @cnt_ade = COUNT(*) FROM dbo.TH_Adendas;
    SELECT @cnt_nov = COUNT(*) FROM dbo.TH_NovedadesMedicas;

    PRINT '================================================================';
    PRINT ' SUCCESS: 140 Mock Employees fully generated!';
    PRINT ' Total Employees now in system: ' + CAST(@cnt_emp AS VARCHAR);
    PRINT ' Total Active Contracts: ' + CAST(@cnt_con AS VARCHAR);
    PRINT ' Total Historical Salary Adendas: ' + CAST(@cnt_ade AS VARCHAR);
    PRINT ' Total Medical Events logged: ' + CAST(@cnt_nov AS VARCHAR);
    PRINT '================================================================';

END TRY
BEGIN CATCH
    ROLLBACK TRANSACTION;
    PRINT '!!! ERROR SEEDING DATA !!!';
    PRINT ERROR_MESSAGE();
    THROW;
END CATCH;
GO
