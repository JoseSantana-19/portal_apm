-- Bloqueo de login escalado: 3 intentos fallidos -> bloqueo temporal, y la
-- duracion del bloqueo aumenta cada vez que el mismo usuario vuelve a
-- agotar sus 3 intentos despues de un bloqueo anterior (1a vez: 5 min,
-- 2a: 15 min, 3a: 30 min, 4a en adelante: 24h). Se resetea a cero solo con
-- un login EXITOSO -- un bloqueo que simplemente expira no cuenta como
-- "resuelto", sigue escalando si la cuenta real está bajo ataque.
USE PORTAL_APM;
GO

-- 1) Contador persistente de "veces bloqueado" (no se resetea al expirar
-- el bloqueo, solo con un login exitoso -- ahi esta la escalada real).
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('dbo.CORE_Usuarios') AND name = 'veces_bloqueado')
    ALTER TABLE dbo.CORE_Usuarios ADD veces_bloqueado TINYINT NOT NULL DEFAULT 0;
GO

-- 2) Configuracion (mismo patron CORE_Config ya usado por LOGIN_MAX_INTENTOS
-- y por el sistema de inactividad) -- editable a futuro sin tocar la SP.
IF NOT EXISTS (SELECT 1 FROM CORE_Config WHERE modulo='CORE' AND clave='LOGIN_MAX_INTENTOS')
    INSERT INTO CORE_Config (modulo, clave, valor, tipo, descripcion, fecha_mod, estado)
    VALUES ('CORE', 'LOGIN_MAX_INTENTOS', '3', 'int', 'Intentos fallidos permitidos antes de bloquear la cuenta', GETDATE(), 1);
ELSE
    UPDATE CORE_Config SET valor='3', fecha_mod=GETDATE() WHERE modulo='CORE' AND clave='LOGIN_MAX_INTENTOS';
GO

IF NOT EXISTS (SELECT 1 FROM CORE_Config WHERE modulo='CORE' AND clave='LOGIN_BLOQUEO_MIN_1')
    INSERT INTO CORE_Config (modulo, clave, valor, tipo, descripcion, fecha_mod, estado)
    VALUES ('CORE', 'LOGIN_BLOQUEO_MIN_1', '5', 'int', 'Minutos de bloqueo la 1a vez que se agotan los intentos', GETDATE(), 1);
IF NOT EXISTS (SELECT 1 FROM CORE_Config WHERE modulo='CORE' AND clave='LOGIN_BLOQUEO_MIN_2')
    INSERT INTO CORE_Config (modulo, clave, valor, tipo, descripcion, fecha_mod, estado)
    VALUES ('CORE', 'LOGIN_BLOQUEO_MIN_2', '15', 'int', 'Minutos de bloqueo la 2a vez', GETDATE(), 1);
IF NOT EXISTS (SELECT 1 FROM CORE_Config WHERE modulo='CORE' AND clave='LOGIN_BLOQUEO_MIN_3')
    INSERT INTO CORE_Config (modulo, clave, valor, tipo, descripcion, fecha_mod, estado)
    VALUES ('CORE', 'LOGIN_BLOQUEO_MIN_3', '30', 'int', 'Minutos de bloqueo la 3a vez', GETDATE(), 1);
IF NOT EXISTS (SELECT 1 FROM CORE_Config WHERE modulo='CORE' AND clave='LOGIN_BLOQUEO_MIN_4')
    INSERT INTO CORE_Config (modulo, clave, valor, tipo, descripcion, fecha_mod, estado)
    VALUES ('CORE', 'LOGIN_BLOQUEO_MIN_4', '1440', 'int', 'Minutos de bloqueo la 4a vez en adelante (1440 = 24h)', GETDATE(), 1);
GO

-- 3) sp_RegistrarFalloLogin -- ahora escala minutos_bloqueo segun
-- veces_bloqueado en vez de dejar el default fijo de 30 sin usar.
CREATE OR ALTER PROCEDURE dbo.sp_RegistrarFalloLogin
    @nombre_usuario NVARCHAR(50),
    @ip_address     NVARCHAR(45) = NULL
AS
BEGIN
    SET NOCOUNT ON;
    DECLARE @id INT, @int TINYINT, @max INT, @veces TINYINT, @minutos SMALLINT, @clave NVARCHAR(30);

    SELECT @id=id_usuario, @int=intentos_fallidos, @veces=veces_bloqueado
    FROM CORE_Usuarios WHERE nombre_usuario=@nombre_usuario;
    IF @id IS NULL RETURN;

    SELECT @max=CAST(valor AS INT) FROM CORE_Config WHERE modulo='CORE' AND clave='LOGIN_MAX_INTENTOS' AND estado=1;
    IF @max IS NULL SET @max=3;

    SET @int=@int+1;

    IF @int>=@max
    BEGIN
        SET @veces = ISNULL(@veces,0) + 1;
        SET @clave = CASE
            WHEN @veces=1 THEN 'LOGIN_BLOQUEO_MIN_1'
            WHEN @veces=2 THEN 'LOGIN_BLOQUEO_MIN_2'
            WHEN @veces=3 THEN 'LOGIN_BLOQUEO_MIN_3'
            ELSE 'LOGIN_BLOQUEO_MIN_4'
        END;
        SELECT @minutos=CAST(valor AS INT) FROM CORE_Config WHERE modulo='CORE' AND clave=@clave AND estado=1;
        IF @minutos IS NULL
            SET @minutos = CASE WHEN @veces=1 THEN 5 WHEN @veces=2 THEN 15 WHEN @veces=3 THEN 30 ELSE 1440 END;

        -- Reinicia intentos_fallidos: el PROXIMO ciclo de bloqueo necesita
        -- sus propios 3 intentos fallidos frescos para escalar de nuevo.
        UPDATE CORE_Usuarios
        SET intentos_fallidos=0, veces_bloqueado=@veces, fecha_bloqueo=GETDATE(), minutos_bloqueo=@minutos
        WHERE id_usuario=@id;
    END
    ELSE
        UPDATE CORE_Usuarios SET intentos_fallidos=@int WHERE id_usuario=@id;

    INSERT INTO CORE_Auditoria(id_usuario,modulo,operacion,ip_address,resultado,detalle)
    VALUES(@id,'CORE','LOGIN',@ip_address,'ERROR','Fallo intento '+CAST(@int AS NVARCHAR)
        + CASE WHEN @int>=@max THEN ' -> bloqueada (' + CAST(ISNULL(@veces,1) AS NVARCHAR) + 'a vez)' ELSE '' END);
END;
GO
