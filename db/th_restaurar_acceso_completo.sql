/* db/th_restaurar_acceso_completo.sql
   Mismo bug que Bitácoras (ver db/bitacoras_restaurar_acceso_completo.sql):
   la migración Fase 1 (permisos_centrales_fase1_th.sql) solo re-otorgó el
   nivel preservado de cada rol sobre "Inicio" (opcion=1), no sobre las
   otras 13 pantallas reales del árbol MOIS de Talento Humano.

   A diferencia de Bitácoras, TH SÍ tiene un sistema nativo de permisos real
   y vigente (Talento_Humano.dbo.th_permisos_rol, flags puede_visualizar/
   crear/editar/eliminar por rol nativo x módulo) -- se usa como fuente de
   verdad real en vez de "extender el mismo nivel a todo".

   Roles cubiertos (los únicos con mapeo real en CORE_Roles_Modulo_Map,
   id_modulo=11):
     - id_rol=1  (ADMIN)       <-> th_roles.rol_id=1 (Super Administrador): full cada pantalla
     - id_rol=12 (ANALISTA_TH) <-> th_roles.rol_id=3 (Analista de Nómina): granular real
     - id_rol=21 (LECTOR)      <-> th_roles.rol_id=4 (Funcionario Lectura): granular real,
       Y le faltaban INCLUSO opcion=0/1 (nunca recibió ni el Dashboard)
   id_rol=11 (DIR_TH) ya tiene las 15 opciones cubiertas (verificado, no se toca).
   id_rol=2 (AUDITOR) y 13/14 (GERENTE/ASIST_GCIA) NO están en
   CORE_Roles_Modulo_Map -- su acceso Dashboard-only es una concesión de
   visibilidad ejecutiva genérica preexistente, no una regresión de esta
   migración; se dejan intactos.

   Mapeo opcion (CORE_Menu_Nodos, id_modulo=11) -> th_permisos_rol.modulo_id:
     2 Directorio->2 | 3 Form.Ingreso->3 | 4 Acción Personal->4
     5 Movimientos internos->1018 | 6 Estudio Socioeconómico->1013
     7 Biblioteca->1014 | 8 Estructura y cargos->(8,9,10,11,12 maestros, uniformes por rol)
     9 Admin Usuarios->6 | 10 Roles y Permisos->7 | 11 Políticas->1017
     12 Auditoría->1015 | 13 Reportes->1016 | 14 Prototipos->5

   Idempotente (NOT EXISTS por fila). */
USE [PORTAL_APM];
GO
SET ANSI_NULLS ON;
SET QUOTED_IDENTIFIER ON;
GO

INSERT dbo.CORE_Permisos_Nodo (id_rol, id_modulo, opcion, items, subitems, nivel_crud, acceso, estado, fecha_asignacion)
SELECT x.id_rol, 11, x.opcion, 0, 0, x.nivel_crud, 1, 1, SYSDATETIME()
FROM (VALUES
    -- id_rol=1 ADMIN: full en las 13 pantallas restantes (th_roles.rol_id=1, todo V/C/E/D=1)
    (1, 2, 4), (1, 3, 4), (1, 4, 4), (1, 5, 4), (1, 6, 4), (1, 7, 4), (1, 8, 4),
    (1, 9, 4), (1, 10, 4), (1, 11, 4), (1, 12, 4), (1, 13, 4), (1, 14, 4),
    -- id_rol=12 ANALISTA_TH: granular real desde th_permisos_rol.rol_id=3
    (12, 2, 3), (12, 3, 3), (12, 4, 3), (12, 5, 3), (12, 6, 3), (12, 7, 1),
    (12, 13, 1), (12, 14, 1),
    -- id_rol=21 LECTOR: granular real desde th_permisos_rol.rol_id=4 (más el
    -- baseline opcion=0/1 que nunca se otorgó)
    (21, 0, 1), (21, 1, 1), (21, 2, 1), (21, 7, 1), (21, 14, 1)
) AS x(id_rol, opcion, nivel_crud)
WHERE NOT EXISTS (
    SELECT 1 FROM dbo.CORE_Permisos_Nodo p
    WHERE p.id_rol=x.id_rol AND p.id_modulo=11 AND p.opcion=x.opcion AND p.items=0 AND p.subitems=0
);
GO
