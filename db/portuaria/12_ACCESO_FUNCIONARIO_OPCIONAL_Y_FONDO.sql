/*
  12_ACCESO_FUNCIONARIO_OPCIONAL_Y_FONDO.sql
  Permite registrar visitas sin funcionario solicitante obligatorio.
  La columna id_funcionario queda NULL para casos donde no aplica o no fue indicado.
*/

USE PortuariaDemo;
GO
SET NOCOUNT ON;
GO

IF OBJECT_ID(N'dbo.bit_visitas', N'U') IS NOT NULL
AND COL_LENGTH(N'dbo.bit_visitas', N'id_funcionario') IS NOT NULL
BEGIN
    ALTER TABLE dbo.bit_visitas ALTER COLUMN id_funcionario INT NULL;
END
GO

-- Validación rápida
SELECT
    TABLE_NAME = N'bit_visitas',
    COLUMN_NAME = N'id_funcionario',
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = N'dbo'
  AND TABLE_NAME = N'bit_visitas'
  AND COLUMN_NAME = N'id_funcionario';
GO
