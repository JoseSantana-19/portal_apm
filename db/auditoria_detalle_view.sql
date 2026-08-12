-- Extiende vw_AuditoriaGlobal para exponer id_registro/datos_antes/datos_despues
-- (existen en CORE_Auditoria desde el inicio pero la vista no los exponia).
-- Necesario para que /admin/auditoria pueda mostrar el detalle real de que
-- cambio en cada operacion (antes -> despues), no solo login/logout.
USE PORTAL_APM;
GO

CREATE OR ALTER VIEW dbo.vw_AuditoriaGlobal AS
SELECT a.id_auditoria, a.modulo, a.operacion, a.tabla_afectada, a.id_registro,
       a.datos_antes, a.datos_despues, a.resultado,
       a.ip_address, a.detalle, a.fecha_registro,
       ISNULL(u.nombre_completo, a.nombre_usuario) AS nombre_usuario
FROM CORE_Auditoria a LEFT JOIN CORE_Usuarios u ON u.id_usuario = a.id_usuario;
GO
