/* db/bitacoras_restaurar_acceso_completo.sql
   Fase 3 (2026-08-11) solo re-otorgó el nivel preservado de cada rol sobre
   "Dashboard" (opcion=1) -- el sistema viejo de Bitácoras daba acceso
   UNIFORME por departamento a TODAS las pantallas (isAdminArea() era un
   solo booleano reusado en canRegistrarIngreso/canVerListadoAdmin/
   canAccederBitacoraRondas/etc, sin distinción de pantalla). Con el árbol
   MOIS real de 13 opciones, esos 6 roles quedaron con acceso real SOLO al
   Dashboard -- el resto del menú lateral y funcionalidad interna de
   Bitácoras se veía vacío para todos, incluido ADMIN.

   Extiende el nivel ya otorgado en opcion=1 a las 12 opciones restantes
   (2-13), por rol -- restaura el acceso funcional completo que esos roles
   tenían antes de la migración. Idempotente. */
USE [PORTAL_APM];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

INSERT dbo.CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion)
SELECT p.id_rol, 13, x.opcion, 0, 0, p.nivel_crud, 1, 1, SYSDATETIME()
FROM dbo.CORE_Permisos_Nodo p
CROSS JOIN (VALUES (2),(3),(4),(5),(6),(7),(8),(9),(10),(11),(12),(13)) AS x(opcion)
WHERE p.id_modulo = 13 AND p.opcion = 1
  AND NOT EXISTS (
      SELECT 1 FROM dbo.CORE_Permisos_Nodo q
      WHERE q.id_rol=p.id_rol AND q.id_modulo=13 AND q.opcion=x.opcion AND q.items=0 AND q.subitems=0
  );
GO
