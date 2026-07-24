/*
    24_PERSONAS_GENERO.sql
    Agrega el campo "genero" a dbo.bit_personas, pedido para el KPI de
    Dashboard Ejecutivo "desglose entre hombres y mujeres".

    NOTA: es un campo NUEVO y NULLABLE — los registros existentes van a
    quedar con genero = NULL ("Sin dato") hasta que se capturen de nuevo o
    se actualicen manualmente. No se puede reconstruir el género de
    visitantes ya registrados.

    Idempotente: se puede correr más de una vez sin error.
*/

IF COL_LENGTH('dbo.bit_personas', 'genero') IS NULL
BEGIN
    ALTER TABLE dbo.bit_personas
    ADD genero CHAR(1) NULL; -- 'M' = Masculino, 'F' = Femenino, NULL = sin dato
END
GO

-- Restringir a valores válidos (o NULL), sin bloquear los registros ya existentes.
IF NOT EXISTS (
    SELECT 1 FROM sys.check_constraints
    WHERE name = 'CK_bit_personas_genero'
)
BEGIN
    ALTER TABLE dbo.bit_personas
    ADD CONSTRAINT CK_bit_personas_genero CHECK (genero IS NULL OR genero IN ('M', 'F'));
END
GO

-- Verificación
SELECT COUNT(*) AS total_personas,
       SUM(CASE WHEN genero = 'M' THEN 1 ELSE 0 END) AS con_genero_m,
       SUM(CASE WHEN genero = 'F' THEN 1 ELSE 0 END) AS con_genero_f,
       SUM(CASE WHEN genero IS NULL THEN 1 ELSE 0 END) AS sin_dato
FROM dbo.bit_personas;
GO
