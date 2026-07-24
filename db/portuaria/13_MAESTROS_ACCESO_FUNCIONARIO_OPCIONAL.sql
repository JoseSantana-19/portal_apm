/* =========================================================
   13_MAESTROS_ACCESO_FUNCIONARIO_OPCIONAL.sql
   Ajuste para permitir registrar visitas sin funcionario asignado.
   PHP puro + maestros de acceso.
========================================================= */

IF OBJECT_ID(N'dbo.bit_visitas', N'U') IS NOT NULL
AND COL_LENGTH(N'dbo.bit_visitas', N'id_funcionario') IS NOT NULL
BEGIN
    ALTER TABLE dbo.bit_visitas ALTER COLUMN id_funcionario INT NULL;
END;
GO

PRINT 'OK: id_funcionario permite NULL para visitas sin funcionario asignado.';
GO
