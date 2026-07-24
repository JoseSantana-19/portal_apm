/* ============================================================
   Modo "apartado individual" por módulo
   Los ítems del menú de los módulos integrados navegan con
   RECARGA COMPLETA (target_spa=0) — igual que Bitácoras — para
   que el sidebar entre en modo enfocado (solo ese módulo +
   botón de volver al portal). Los temas del portal se mantienen.
   Idempotente.
   ============================================================ */
USE PORTAL_APM;
GO
SET NOCOUNT ON;

UPDATE CORE_Menu_Nodos SET target_spa = 0 WHERE id_modulo IN (11, 12);

PRINT 'Modo apartado activado para módulos 11 (TH) y 12 (Bienes).';
SELECT id_modulo, COUNT(*) AS nodos_no_spa
FROM CORE_Menu_Nodos WHERE id_modulo IN (11,12,13) AND target_spa = 0
GROUP BY id_modulo;
GO
