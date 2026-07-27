/* ============================================================================
   Migración: PORTAL_APM — simplificar el menú de Portuaria (módulo 13) al
   mismo patrón que Talento Humano/Bienes: header + "Panel" + un link al
   sistema completo. Nada más.

   Contexto: Portuaria es, como TH y Bienes, un módulo independiente (con su
   propio stack/BDs) — solo que a diferencia de esos dos, en el árbol de
   menú todavía tenía expuestos ~25 nodos de detalle (Visitas, Seguridad
   Operativa, CCTV, Catálogos) que en los datos YA estaban con estado=0
   (deshabilitados, invisibles para cualquier usuario) desde antes — es
   decir, el sidebar real de navegación YA mostraba solo "Panel Portuario" +
   "Sistema de Bitácoras". Lo que quedaba desprolijo era la pantalla de
   administración "Estructura del Menú" (/admin/menu), que sí lista los
   nodos deshabilitados — mostraba un árbol de 29 nodos para Portuaria
   contra 2-3 para TH/Bienes.

   Esta migración BORRA (no solo deshabilita) esos nodos ya inertes y sus
   permisos asociados — la funcionalidad real (rutas /visitas, /rondas,
   /camaras, /catalogos, etc.) no se toca, sigue funcionando igual y sigue
   siendo alcanzable desde los accesos directos dentro de /portuaria (hub).
   Solo se retira del árbol MOIS lo que ya estaba muerto ahí.

   Idempotente. Ejecutar sobre PORTAL_APM.
   ============================================================================ */

USE PORTAL_APM;
GO

DELETE FROM CORE_Permisos_Nodo
WHERE id_modulo = 13
  AND NOT (opcion IN (0,1) AND items IN (0,1,7));

DELETE FROM CORE_Menu_Nodos
WHERE id_modulo = 13
  AND NOT (opcion IN (0,1) AND items IN (0,1,7));
GO

PRINT 'Menú de Portuaria simplificado: header + Panel + Sistema de Bitácoras.';
SELECT opcion,items,subitems,descripcion,url_ruta,estado FROM CORE_Menu_Nodos WHERE id_modulo=13 ORDER BY opcion,items;
GO
