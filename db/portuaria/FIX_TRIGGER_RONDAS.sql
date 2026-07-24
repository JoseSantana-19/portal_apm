-- ============================================================================
-- DIAGNÓSTICO: encontrar el objeto que todavía tiene mal el nombre de tabla
-- ============================================================================
-- Corré esto primero. Busca en TODA la base cualquier trigger/vista/función/
-- procedimiento cuyo texto real (el que está instalado ahora mismo) contenga
-- "dbo.rondas_cabecera" o "dbo.rondas_detalles" SIN el prefijo "bit_".
-- Si tu error es el que mostraste, esto debería devolver una fila.

SELECT
    o.name          AS objeto_con_el_bug,
    o.type_desc     AS tipo,
    m.definition    AS definicion_actual
FROM sys.sql_modules m
JOIN sys.objects o ON o.object_id = m.object_id
WHERE m.definition LIKE '%dbo.rondas_cabecera%'
   OR m.definition LIKE '%dbo.rondas_detalles%';
GO

-- ============================================================================
-- ARREGLO: recrear el trigger con la definición correcta
-- ============================================================================
-- El archivo sql/03_DATABASE_LOGICA_TRIGGERS.sql de tu proyecto YA tiene el
-- texto correcto (dbo.bit_rondas_cabecera). El problema es que nunca se
-- volvió a ejecutar contra tu base real después de corregirlo — así que tu
-- base sigue con la versión vieja del trigger.
--
-- Es seguro volver a correr TODO sql/03_DATABASE_LOGICA_TRIGGERS.sql de
-- nuevo: las tablas están protegidas con "IF OBJECT_ID(...) IS NULL" (no
-- las toca si ya existen), y el trigger se borra y se vuelve a crear siempre
-- (por eso este archivo puede correrse las veces que haga falta sin miedo).
--
-- Si preferís arreglar solo el trigger sin tocar nada más, esto alcanza:

IF OBJECT_ID(N'dbo.trg_rondas_sync_totales', N'TR') IS NOT NULL
    DROP TRIGGER dbo.trg_rondas_sync_totales;
GO

CREATE TRIGGER dbo.trg_rondas_sync_totales
ON dbo.bit_rondas_detalles
AFTER INSERT
AS
BEGIN
    SET NOCOUNT ON;
    ;WITH agg AS (
        SELECT c.fecha AS d, COUNT(*) AS cnt
        FROM inserted i
        INNER JOIN dbo.bit_rondas_cabecera c ON c.id_ronda = i.id_ronda
        GROUP BY c.fecha
    )
    MERGE dbo.bit_totales_actividades AS tgt
    USING agg AS src ON tgt.fecha = src.d
    WHEN MATCHED THEN
        UPDATE SET total_detalles = tgt.total_detalles + src.cnt
    WHEN NOT MATCHED BY TARGET THEN
        INSERT (fecha, total_detalles) VALUES (src.d, src.cnt);
END
GO

PRINT 'Trigger trg_rondas_sync_totales recreado correctamente.';
GO
