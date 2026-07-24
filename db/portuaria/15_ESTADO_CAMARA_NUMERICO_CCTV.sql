/* =========================================================
   15_ESTADO_CAMARA_NUMERICO_CCTV.sql
   Objetivo:
   Convertir dbo.bit_camaras.estado_camara a valor numérico.

   Equivalencias usadas:
   100 = NO OPERATIVA
   101 = OPERATIVA

   Ejecutar en la base PortuariaDemo antes de copiar los archivos PHP/JS.
========================================================= */

SET NOCOUNT ON;

BEGIN TRY
    BEGIN TRANSACTION;

    /* 1. Eliminar CHECK anterior si existe */
    DECLARE @constraintName SYSNAME;

    SELECT @constraintName = cc.name
    FROM sys.check_constraints cc
    INNER JOIN sys.tables t
        ON cc.parent_object_id = t.object_id
    INNER JOIN sys.schemas s
        ON t.schema_id = s.schema_id
    WHERE s.name = 'dbo'
      AND t.name = 'bitacora_camaras'
      AND cc.name = 'CK_bitacora_camaras_estado_camara';

    IF @constraintName IS NOT NULL
    BEGIN
        EXEC('ALTER TABLE dbo.bit_camaras DROP CONSTRAINT ' + @constraintName);
    END;

    /* 2. Si la columna aún no es INT, normalizar primero como texto numérico */
    IF EXISTS (
        SELECT 1
        FROM sys.columns c
        INNER JOIN sys.types ty
            ON c.user_type_id = ty.user_type_id
        WHERE c.object_id = OBJECT_ID('dbo.bit_camaras')
          AND c.name = 'estado_camara'
          AND ty.name <> 'int'
    )
    BEGIN
        UPDATE dbo.bit_camaras
        SET estado_camara = CASE
            WHEN UPPER(LTRIM(RTRIM(CONVERT(NVARCHAR(50), estado_camara)))) IN ('OPER', 'OPERATIVA', '101') THEN '101'
            WHEN UPPER(LTRIM(RTRIM(CONVERT(NVARCHAR(50), estado_camara)))) IN ('NO OPER', 'NO OPERATIVA', '100') THEN '100'
            WHEN estado_camara IS NULL THEN NULL
            ELSE NULL
        END;

        ALTER TABLE dbo.bit_camaras
        ALTER COLUMN estado_camara INT NULL;
    END
    ELSE
    BEGIN
        UPDATE dbo.bit_camaras
        SET estado_camara = CASE
            WHEN estado_camara = 101 THEN 101
            WHEN estado_camara = 100 THEN 100
            ELSE NULL
        END;
    END;

    /* 3. Crear CHECK numérico */
    IF NOT EXISTS (
        SELECT 1
        FROM sys.check_constraints
        WHERE parent_object_id = OBJECT_ID('dbo.bit_camaras')
          AND name = 'CK_bitacora_camaras_estado_camara'
    )
    BEGIN
        ALTER TABLE dbo.bit_camaras
        ADD CONSTRAINT CK_bitacora_camaras_estado_camara
        CHECK (estado_camara IS NULL OR estado_camara IN (100, 101));
    END;

    COMMIT TRANSACTION;

    PRINT 'OK: estado_camara convertido a INT. 100 = NO OPERATIVA, 101 = OPERATIVA.';
END TRY
BEGIN CATCH
    IF @@TRANCOUNT > 0
        ROLLBACK TRANSACTION;

    DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();
    RAISERROR(@ErrorMessage, 16, 1);
END CATCH;

/* Verificación rápida */
SELECT
    estado_camara AS codigo_estado,
    CASE
        WHEN estado_camara = 100 THEN 'NO OPERATIVA'
        WHEN estado_camara = 101 THEN 'OPERATIVA'
        ELSE 'SIN ESTADO'
    END AS estado_camara_texto,
    COUNT(*) AS total
FROM dbo.bit_camaras
GROUP BY estado_camara
ORDER BY estado_camara;
