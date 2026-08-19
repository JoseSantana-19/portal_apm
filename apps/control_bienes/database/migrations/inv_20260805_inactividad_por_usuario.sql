/* Tiempo de inactividad personalizado por usuario.
   NULL significa que la cuenta hereda el parámetro global. */
USE [inventario];
GO

IF COL_LENGTH('dbo.inv_usuarios', 'tiempo_inactividad') IS NULL
    ALTER TABLE dbo.inv_usuarios ADD tiempo_inactividad INT NULL;
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.check_constraints
    WHERE name = 'CK_inv_usuarios_tiempo_inactividad'
)
    ALTER TABLE dbo.inv_usuarios ADD CONSTRAINT CK_inv_usuarios_tiempo_inactividad
        CHECK (tiempo_inactividad IS NULL OR tiempo_inactividad BETWEEN 60 AND 14400);
GO

UPDATE dbo.inv_parametros
SET valor = '300',
    descripcion = 'Tolerancia fija para responder el aviso antes de cerrar sesión (segundos)'
WHERE clave = 'tiempo_gracia_sesion';
GO
