/* db/bienes_restaurar_acceso_completo.sql
   Mismo patrón de bug que Bitácoras/TH: la migración Fase 2
   (permisos_centrales_fase2_bienes_portal.sql) solo re-otorgó el nivel
   preservado de 6 roles genéricos preexistentes (ADMIN, AUDITOR, GERENTE,
   ASIST_GCIA, DIR_ADMIN, ANALISTA_ADMIN) sobre "Dashboard" (opcion=1), no
   sobre las otras 14 pantallas reales del árbol MOIS de Control de Bienes.

   PARTE 1 -- mismo fix mecánico que Bitácoras: extender el nivel ya
   otorgado en opcion=1 a las 14 opciones restantes (2-15), por rol.

   PARTE 2 -- gap distinto y más severo: los 3 roles creados EN Fase 2
   específicamente para Bienes (BIENES_SUPERVISOR=24, BIENES_OPERADOR=25,
   BIENES_AUDITOR=26) tienen CERO filas en CORE_Permisos_Nodo -- ni
   siquiera Dashboard. No hay fuente nativa de la que restaurar
   (Inventario.dbo.inv_permisos_rol existe pero está vacía, nunca se
   sembró) -- son roles nuevos sin historial previo que preservar. Se les
   otorga una matriz de acceso por defecto razonable según su nombre/rol
   (Supervisor: operativo amplio; Operador: alta/consulta día a día sin
   eliminar ni pantallas de sistema; Auditor: solo lectura en todo excepto
   Usuarios/Permisos), usando Router::POLITICAS (apps/control_bienes/core/
   Router.php) como referencia de nivel mínimo por pantalla. Esto es una
   decisión de política nueva, no una restauración -- revisar y ajustar
   vía /admin/roles/{id}/permisos si no calza con el uso real esperado.

   Idempotente (NOT EXISTS por fila). */
USE [PORTAL_APM];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

-- PARTE 1: extender nivel ya otorgado (opcion=1) a opciones 2-15, roles preexistentes
INSERT dbo.CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion)
SELECT p.id_rol, 12, x.opcion, 0, 0, p.nivel_crud, 1, 1, SYSDATETIME()
FROM dbo.CORE_Permisos_Nodo p
CROSS JOIN (VALUES (2),(3),(4),(5),(6),(7),(8),(9),(10),(11),(12),(13),(14),(15)) AS x(opcion)
WHERE p.id_modulo = 12 AND p.opcion = 1
  AND NOT EXISTS (
      SELECT 1 FROM dbo.CORE_Permisos_Nodo q
      WHERE q.id_rol=p.id_rol AND q.id_modulo=12 AND q.opcion=x.opcion AND q.items=0 AND q.subitems=0
  );
GO

-- PARTE 2: matriz por defecto para los 3 roles nuevos de Bienes (sin fila previa)
INSERT dbo.CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion)
SELECT r.id_rol, 12, x.opcion, 0, 0, x.nivel_crud, 1, 1, SYSDATETIME()
FROM dbo.CORE_Roles r
JOIN (VALUES
    -- BIENES_SUPERVISOR: operativo amplio (Ver+Crear+Editar en pantallas de trabajo diario,
    -- incluye cierre de período), solo lectura en bitácora/reportes, sin acceso a pantallas de sistema
    ('BIENES_SUPERVISOR', 0, 3), ('BIENES_SUPERVISOR', 1, 3),
    ('BIENES_SUPERVISOR', 2, 3), ('BIENES_SUPERVISOR', 3, 3), ('BIENES_SUPERVISOR', 4, 3),
    ('BIENES_SUPERVISOR', 5, 3), ('BIENES_SUPERVISOR', 6, 3), ('BIENES_SUPERVISOR', 7, 3),
    ('BIENES_SUPERVISOR', 8, 3), ('BIENES_SUPERVISOR', 9, 3),
    ('BIENES_SUPERVISOR', 10, 1), ('BIENES_SUPERVISOR', 11, 1),
    ('BIENES_SUPERVISOR', 12, 3),
    -- BIENES_OPERADOR: alta/consulta día a día (Ver+Crear, sin editar/eliminar ni pantallas de sistema)
    ('BIENES_OPERADOR', 0, 2), ('BIENES_OPERADOR', 1, 2),
    ('BIENES_OPERADOR', 2, 2), ('BIENES_OPERADOR', 3, 2), ('BIENES_OPERADOR', 4, 1),
    ('BIENES_OPERADOR', 5, 2), ('BIENES_OPERADOR', 6, 2), ('BIENES_OPERADOR', 7, 2),
    ('BIENES_OPERADOR', 8, 2), ('BIENES_OPERADOR', 9, 1),
    ('BIENES_OPERADOR', 10, 1), ('BIENES_OPERADOR', 11, 1),
    -- BIENES_AUDITOR: solo lectura, en todo excepto Usuarios/Permisos
    ('BIENES_AUDITOR', 0, 1), ('BIENES_AUDITOR', 1, 1),
    ('BIENES_AUDITOR', 2, 1), ('BIENES_AUDITOR', 3, 1), ('BIENES_AUDITOR', 4, 1),
    ('BIENES_AUDITOR', 5, 1), ('BIENES_AUDITOR', 6, 1), ('BIENES_AUDITOR', 7, 1),
    ('BIENES_AUDITOR', 8, 1), ('BIENES_AUDITOR', 9, 1), ('BIENES_AUDITOR', 10, 1),
    ('BIENES_AUDITOR', 11, 1), ('BIENES_AUDITOR', 12, 1), ('BIENES_AUDITOR', 13, 1)
) AS x(codigo, opcion, nivel_crud) ON x.codigo = r.codigo
WHERE NOT EXISTS (
    SELECT 1 FROM dbo.CORE_Permisos_Nodo p
    WHERE p.id_rol=r.id_rol AND p.id_modulo=12 AND p.opcion=x.opcion AND p.items=0 AND p.subitems=0
);
GO
