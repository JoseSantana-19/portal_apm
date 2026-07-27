-- Portal APM v2.0 - Actualizar hash de contrasena inicial
-- Generado: 2026-06-05 08:18
-- Ejecutar DESPUES de Z.BASES DE DATOS/PORTAL_APM_COMPLETO.sql

SET QUOTED_IDENTIFIER ON;
GO

USE PORTAL_APM;
GO

UPDATE CORE_Usuarios
SET hash_contrasena = '$2y$12$Y6INR.wqj.aWyJ2kDbWfWebVGs9s6cuWesk9klMC4n2MhWNQTrNKW'
WHERE id_usuario > 0;
GO

PRINT 'Hash bcrypt actualizado para todos los usuarios.';
PRINT 'Acceder con: admin / Apm2024*';
GO
