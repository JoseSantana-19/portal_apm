-- =============================================================================
-- Migración: Talento_Humano — saneamiento de esquema para integración portal_apm
-- Idempotente. Ejecutar sobre la BD `Talento_Humano` (instancia .\VICTUS).
-- =============================================================================
SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;
GO
USE [Talento_Humano];
GO

-- -----------------------------------------------------------------------------
-- 1. Consolidar unidades organizacionales duplicadas (codigo_uorg repetido)
--    Conserva la fila de menor unidad_id; repunta FKs; borra las repetidas.
-- -----------------------------------------------------------------------------
IF OBJECT_ID('tempdb..#dup_uorg') IS NOT NULL DROP TABLE #dup_uorg;
SELECT u.unidad_id AS doomed_id, s.keep_id
INTO #dup_uorg
FROM th_unidades_organizacionales u
JOIN (
    SELECT codigo_uorg, MIN(unidad_id) AS keep_id
    FROM th_unidades_organizacionales
    GROUP BY codigo_uorg
    HAVING COUNT(*) > 1
) s ON u.codigo_uorg = s.codigo_uorg
WHERE u.unidad_id <> s.keep_id;

UPDATE e  SET e.unidad_id         = d.keep_id FROM th_empleados e                 JOIN #dup_uorg d ON e.unidad_id          = d.doomed_id;
UPDATE u  SET u.unidad_padre_id   = d.keep_id FROM th_unidades_organizacionales u JOIN #dup_uorg d ON u.unidad_padre_id    = d.doomed_id;

IF OBJECT_ID('th_acciones_personal','U') IS NOT NULL
BEGIN
    UPDATE a SET a.actual_unidad_id    = d.keep_id FROM th_acciones_personal a JOIN #dup_uorg d ON a.actual_unidad_id    = d.doomed_id;
    UPDATE a SET a.propuesta_unidad_id = d.keep_id FROM th_acciones_personal a JOIN #dup_uorg d ON a.propuesta_unidad_id = d.doomed_id;
END

DELETE u FROM th_unidades_organizacionales u JOIN #dup_uorg d ON u.unidad_id = d.doomed_id;
DROP TABLE #dup_uorg;
GO

-- -----------------------------------------------------------------------------
-- 2. Eliminar el RBAC/auth muerto del origen (portal_apm usa CORE_*).
--    Orden por dependencias de FK.
-- -----------------------------------------------------------------------------
IF OBJECT_ID('th_permisos_rol','U')     IS NOT NULL DROP TABLE th_permisos_rol;
IF OBJECT_ID('th_usuarios_sistema','U') IS NOT NULL DROP TABLE th_usuarios_sistema;
IF OBJECT_ID('th_roles','U')            IS NOT NULL DROP TABLE th_roles;
IF OBJECT_ID('th_modulos','U')          IS NOT NULL DROP TABLE th_modulos;
GO

-- -----------------------------------------------------------------------------
-- 3. Columna de fusión organizacional (para el reporte jerárquico).
-- -----------------------------------------------------------------------------
IF NOT EXISTS (SELECT 1 FROM sys.columns
               WHERE object_id = OBJECT_ID('th_unidades_organizacionales') AND name = 'sucedido_por_id')
    ALTER TABLE th_unidades_organizacionales ADD sucedido_por_id INT NULL;
GO
IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_Unidad_Sucedida')
    ALTER TABLE th_unidades_organizacionales
        ADD CONSTRAINT FK_Unidad_Sucedida
        FOREIGN KEY (sucedido_por_id) REFERENCES th_unidades_organizacionales(unidad_id);
GO

-- -----------------------------------------------------------------------------
-- 4. Historial laboral (tabla que el reporte jerárquico necesita).
-- -----------------------------------------------------------------------------
IF OBJECT_ID('th_historial_laboral','U') IS NULL
    CREATE TABLE th_historial_laboral (
        historial_id INT IDENTITY(1,1) PRIMARY KEY,
        empleado_id  INT  NOT NULL,
        puesto_id    INT  NULL,
        unidad_id    INT  NULL,
        fecha_desde  DATE NOT NULL,
        fecha_hasta  DATE NULL,
        CONSTRAINT FK_HistLab_Emp FOREIGN KEY (empleado_id) REFERENCES th_empleados(empleado_id),
        CONSTRAINT FK_HistLab_Pue FOREIGN KEY (puesto_id)   REFERENCES th_puestos(puesto_id),
        CONSTRAINT FK_HistLab_Uni FOREIGN KEY (unidad_id)   REFERENCES th_unidades_organizacionales(unidad_id)
    );
GO

-- -----------------------------------------------------------------------------
-- 5. Vista de reporte jerárquico (adaptada al esquema real).
-- -----------------------------------------------------------------------------
CREATE OR ALTER VIEW vw_th_reporte_historial_jerarquico AS
SELECT
    e.empleado_id,
    e.identificacion               AS cedula,
    e.apellidos + ' ' + e.nombres  AS funcionario,
    p.codigo_puesto,
    p.nombre_puesto,
    u.nombre_unidad                AS departamento_historico,
    CASE WHEN u_padre.unidad_id IS NOT NULL THEN u_padre.nombre_unidad ELSE u.nombre_unidad END AS direccion_padre,
    CASE WHEN u_padre.unidad_id IS NOT NULL THEN u.nombre_unidad ELSE NULL END                   AS sub_area,
    ISNULL(u_nueva.nombre_unidad, u.nombre_unidad) AS direccion_actual_unificada,
    ISNULL(u_nueva.tipo_proceso,  u.tipo_proceso)  AS tipo_proceso,
    h.fecha_desde,
    h.fecha_hasta,
    DATEDIFF(year, h.fecha_desde, ISNULL(h.fecha_hasta, GETDATE())) AS anios_permanencia,
    DATEDIFF(day, CAST(GETDATE() AS DATE),
        DATEFROMPARTS(
            YEAR(GETDATE()) + CASE
                WHEN DATEFROMPARTS(YEAR(GETDATE()), MONTH(e.fecha_nacimiento), DAY(e.fecha_nacimiento)) < CAST(GETDATE() AS DATE)
                THEN 1 ELSE 0 END,
            MONTH(e.fecha_nacimiento), DAY(e.fecha_nacimiento))
    ) AS dias_para_cumpleanos
FROM th_historial_laboral h
JOIN th_empleados e                 ON h.empleado_id = e.empleado_id
JOIN th_puestos p                   ON h.puesto_id   = p.puesto_id
JOIN th_unidades_organizacionales u ON h.unidad_id   = u.unidad_id
LEFT JOIN th_unidades_organizacionales u_padre ON u.unidad_padre_id  = u_padre.unidad_id
LEFT JOIN th_unidades_organizacionales u_nueva ON u.sucedido_por_id  = u_nueva.unidad_id;
GO
