USE PORTAL_APM;
GO

-- Fix: sp_GetMenuUsuario ignoraba CORE_Permisos_Nodo_Usuario (override por
-- usuario individual, editable desde /admin/usuarios/{id}/permisos).
-- fn_TienePermisoNodo (usada para el acceso real por Bienes/Bitacoras
-- nativos) SI la respeta -- pero el sidebar del PORTAL, que arma su arbol
-- desde esta SP, la ignoraba por completo: una seccion otorgada por
-- override no aparecia en el sidebar, y una seccion revocada por override
-- seguia apareciendo (bloqueada recien al hacer click). Confirmado con
-- reproduccion real 2026-08-12.
--
-- Reescrita para que el nivel efectivo por nodo sea:
--   COALESCE(override_del_usuario, nivel_de_su_rol, 0) >= 1
-- igual que el fallback que ya usa fn_TienePermisoNodo.
CREATE OR ALTER PROCEDURE [dbo].[sp_GetMenuUsuario]
    @id_usuario INT
AS
BEGIN
    SET NOCOUNT ON;

    ;WITH EfectivoPorRol AS (
        SELECT pn.id_modulo, pn.opcion, pn.items, pn.subitems, MAX(pn.nivel_crud) AS nivel_crud
        FROM CORE_Usuarios u
        INNER JOIN CORE_Usuarios_Roles ur ON ur.id_usuario = u.id_usuario AND ur.estado = 1
        INNER JOIN CORE_Roles r           ON r.id_rol = ur.id_rol AND r.estado = 1
        INNER JOIN CORE_Permisos_Nodo pn  ON pn.id_rol = r.id_rol AND pn.acceso = 1 AND pn.estado = 1
        WHERE u.id_usuario = @id_usuario AND u.estado = 1
        GROUP BY pn.id_modulo, pn.opcion, pn.items, pn.subitems
    )
    SELECT
        mn.id_nodo, mn.id_modulo, mn.opcion, mn.items, mn.subitems,
        mn.descripcion, mn.url_ruta, mn.icono, mn.orden, mn.target_spa,
        COALESCE(pu.nivel_crud, er.nivel_crud) AS nivel_crud
    FROM CORE_Menu_Nodos mn
    LEFT JOIN EfectivoPorRol er ON er.id_modulo = mn.id_modulo AND er.opcion = mn.opcion
                                AND er.items = mn.items AND er.subitems = mn.subitems
    LEFT JOIN CORE_Permisos_Nodo_Usuario pu ON pu.id_usuario = @id_usuario AND pu.estado = 1
                                            AND pu.id_modulo = mn.id_modulo AND pu.opcion = mn.opcion
                                            AND pu.items = mn.items AND pu.subitems = mn.subitems
    WHERE mn.estado = 1
      AND COALESCE(pu.nivel_crud, er.nivel_crud, 0) >= 1
    ORDER BY mn.id_modulo, mn.opcion, mn.items, mn.subitems;
END;
