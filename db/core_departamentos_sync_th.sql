/* ============================================================================
   Migración: CORE_Departamentos pasa a sincronizarse desde
   Talento_Humano.dbo.th_unidades_organizacionales (fuente de verdad para el
   NOMBRE/estructura de departamentos), preservando lo propio del portal
   (icono, color_badge, y cualquier departamento sin equivalente en TH).

   Mismo patrón que trg_sync_th_empleados_to_inventario (Control de Bienes,
   2026-07-27): trigger AFTER INSERT/UPDATE en la BD de TH, escribe cross-DB
   en PORTAL_APM. Nunca borra físicamente — soft-estado.

   IMPORTANTE — dato real de TH (verificado 2026-08-04): de 95 filas en
   th_unidades_organizacionales, 66 son basura de importación
   (tipo_proceso='IMPORTADO - PENDIENTE CLASIFICAR', todas activo=0, nombres
   con problemas de encoding y duplicados). El trigger las excluye a
   propósito (WHERE activo=1 AND tipo_proceso <> esa etiqueta).

   codigo_uorg NO es único en TH (ej. 'DEP-PLAN' aparece 2 veces, una activa
   y una inactiva vieja) — el trigger se queda con MIN(unidad_id) por cada
   codigo_uorg activo al determinar el "representante" de cada unidad.

   Idempotente. Ejecutar sobre PORTAL_APM (crea columnas) y luego sobre
   Talento_Humano (crea el trigger).
   ============================================================================ */

USE PORTAL_APM;
GO

IF COL_LENGTH('dbo.CORE_Departamentos', 'codigo_uorg_th') IS NULL
    ALTER TABLE dbo.CORE_Departamentos ADD codigo_uorg_th NVARCHAR(20) NULL;
GO
IF COL_LENGTH('dbo.CORE_Departamentos', 'origen_th') IS NULL
    ALTER TABLE dbo.CORE_Departamentos ADD origen_th BIT NOT NULL CONSTRAINT DF_CoreDepto_OrigenTh DEFAULT (0);
GO

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'UQ_CoreDepto_CodigoUorgTh')
    CREATE UNIQUE NONCLUSTERED INDEX UQ_CoreDepto_CodigoUorgTh
        ON dbo.CORE_Departamentos(codigo_uorg_th)
        WHERE codigo_uorg_th IS NOT NULL;
GO

/* ── Backfill: solo los emparejamientos de NOMBRE inequívocos entre los 20
   departamentos ya existentes y las unidades TH reales (activo=1, no
   importadas). El resto de los 20 (DELEGACION, TI, AUDITORIA,
   JURIDICA_ABOG, CCTV, GARITA, INSPECTORES, TH_ANALISTAS, SECRETARIA,
   PORTAL) NO tienen equivalente TH claro — quedan sin vincular
   (codigo_uorg_th NULL), tal cual están, hasta que el usuario confirme o
   corrija el mapeo. No vincular a ciegas es la opción segura: un vínculo
   mal puesto contamina el nombre del departamento en la próxima sync. ── */
UPDATE d SET d.codigo_uorg_th = v.codigo_uorg, d.origen_th = 1
FROM dbo.CORE_Departamentos d
JOIN (VALUES
    ('GERENCIA',     'GER-GEN'),
    ('PLANIFICACION','DIR-PLAN'),
    ('JURIDICA',     'DIR-JUR'),
    ('INFRA',        'DIR-INF'),
    ('OPERACIONES',  'DIR-OPE'),
    ('ADMIN',        'DIR-ADM'),
    ('FINANCIERO',   'DIR-FIN'),
    ('TH',           'DIR-TH'),
    ('CONTABILIDAD', 'AREA-CON'),
    ('PRESUPUESTO',  'DEP-PRE')
) AS v(codigo, codigo_uorg) ON v.codigo = d.codigo
WHERE d.codigo_uorg_th IS NULL;
GO

PRINT 'CORE_Departamentos: columnas de sync + backfill de 10 emparejamientos confiables aplicados.';
SELECT codigo, nombre, codigo_uorg_th, origen_th FROM CORE_Departamentos ORDER BY id_departamento;
GO

/* ============================================================================
   Trigger de sincronización — vive en Talento_Humano (dispara sobre su
   propia tabla, escribe cross-DB en PORTAL_APM.dbo.CORE_Departamentos).
   ============================================================================ */

USE Talento_Humano;
GO

CREATE OR ALTER TRIGGER dbo.trg_sync_th_unidades_to_departamentos
ON dbo.th_unidades_organizacionales
AFTER INSERT, UPDATE
AS
BEGIN
    SET NOCOUNT ON;

    -- Temp tables (no CTE): el trigger necesita el mismo "origen" filtrado
    -- en dos sentencias (UPDATE y luego INSERT) — una CTE con WITH solo
    -- vive para la sentencia inmediatamente siguiente, no alcanza para dos.
    SELECT u.codigo_uorg, u.nombre_unidad, u.unidad_padre_id
    INTO #origen
    FROM (
        SELECT *, ROW_NUMBER() OVER (PARTITION BY codigo_uorg ORDER BY unidad_id) AS rn
        FROM inserted
        WHERE activo = 1 AND tipo_proceso <> N'IMPORTADO - PENDIENTE CLASIFICAR'
    ) u
    WHERE u.rn = 1;

    SELECT p.unidad_id, p.codigo_uorg
    INTO #padre_codigo
    FROM dbo.th_unidades_organizacionales p
    WHERE p.activo = 1 AND p.tipo_proceso <> N'IMPORTADO - PENDIENTE CLASIFICAR';

    -- Actualiza los que ya están vinculados (nombre/estado en vivo desde TH;
    -- icono/color_badge/codigo son del portal, no se tocan).
    UPDATE d
    SET d.nombre = o.nombre_unidad,
        d.estado = 1,
        d.id_padre = COALESCE(padreDep.id_departamento, d.id_padre),
        d.nivel = CASE WHEN o.unidad_padre_id IS NULL THEN 1 ELSE 2 END
    FROM PORTAL_APM.dbo.CORE_Departamentos d
    JOIN #origen o ON o.codigo_uorg = d.codigo_uorg_th
    LEFT JOIN #padre_codigo pc ON pc.unidad_id = o.unidad_padre_id
    LEFT JOIN PORTAL_APM.dbo.CORE_Departamentos padreDep ON padreDep.codigo_uorg_th = pc.codigo_uorg;

    -- Inserta las unidades TH activas y válidas que todavía no tienen
    -- departamento vinculado en el portal.
    INSERT INTO PORTAL_APM.dbo.CORE_Departamentos
        (codigo, nombre, id_padre, nivel, estado, fecha_creacion, codigo_uorg_th, origen_th)
    SELECT
        o.codigo_uorg,
        o.nombre_unidad,
        COALESCE(padreDep.id_departamento, (SELECT id_departamento FROM PORTAL_APM.dbo.CORE_Departamentos WHERE codigo = N'PORTAL')),
        CASE WHEN o.unidad_padre_id IS NULL THEN 1 ELSE 2 END,
        1,
        SYSUTCDATETIME(),
        o.codigo_uorg,
        1
    FROM #origen o
    LEFT JOIN #padre_codigo pc ON pc.unidad_id = o.unidad_padre_id
    LEFT JOIN PORTAL_APM.dbo.CORE_Departamentos padreDep ON padreDep.codigo_uorg_th = pc.codigo_uorg
    WHERE NOT EXISTS (
        SELECT 1 FROM PORTAL_APM.dbo.CORE_Departamentos d2 WHERE d2.codigo_uorg_th = o.codigo_uorg
    )
    AND NOT EXISTS (
        SELECT 1 FROM PORTAL_APM.dbo.CORE_Departamentos d3 WHERE d3.codigo = o.codigo_uorg
    );

    DROP TABLE #origen;
    DROP TABLE #padre_codigo;
END;
GO

/* ── Backfill único: procesa TODAS las unidades TH activas y válidas que
   existen HOY (el trigger de arriba solo dispara a futuro, sobre filas que
   cambien después de creado — no reprocesa retroactivamente lo que ya
   estaba ahí). Reusa la misma lógica simulando un "inserted" con la tabla
   completa vía sp_rename temporal no hace falta: se llama al mismo cuerpo
   manualmente contra la tabla real. ── */
USE Talento_Humano;
GO

SELECT u.codigo_uorg, u.nombre_unidad, u.unidad_padre_id
INTO #origen_bf
FROM (
    SELECT *, ROW_NUMBER() OVER (PARTITION BY codigo_uorg ORDER BY unidad_id) AS rn
    FROM dbo.th_unidades_organizacionales
    WHERE activo = 1 AND tipo_proceso <> N'IMPORTADO - PENDIENTE CLASIFICAR'
) u
WHERE u.rn = 1;

SELECT p.unidad_id, p.codigo_uorg
INTO #padre_codigo_bf
FROM dbo.th_unidades_organizacionales p
WHERE p.activo = 1 AND p.tipo_proceso <> N'IMPORTADO - PENDIENTE CLASIFICAR';

UPDATE d
SET d.nombre = o.nombre_unidad,
    d.estado = 1,
    d.id_padre = COALESCE(padreDep.id_departamento, d.id_padre),
    d.nivel = CASE WHEN o.unidad_padre_id IS NULL THEN 1 ELSE 2 END
FROM PORTAL_APM.dbo.CORE_Departamentos d
JOIN #origen_bf o ON o.codigo_uorg = d.codigo_uorg_th
LEFT JOIN #padre_codigo_bf pc ON pc.unidad_id = o.unidad_padre_id
LEFT JOIN PORTAL_APM.dbo.CORE_Departamentos padreDep ON padreDep.codigo_uorg_th = pc.codigo_uorg;
GO

INSERT INTO PORTAL_APM.dbo.CORE_Departamentos
    (codigo, nombre, id_padre, nivel, estado, fecha_creacion, codigo_uorg_th, origen_th)
SELECT
    o.codigo_uorg,
    o.nombre_unidad,
    COALESCE(padreDep.id_departamento, (SELECT id_departamento FROM PORTAL_APM.dbo.CORE_Departamentos WHERE codigo = N'PORTAL')),
    CASE WHEN o.unidad_padre_id IS NULL THEN 1 ELSE 2 END,
    1,
    SYSUTCDATETIME(),
    o.codigo_uorg,
    1
FROM #origen_bf o
LEFT JOIN #padre_codigo_bf pc ON pc.unidad_id = o.unidad_padre_id
LEFT JOIN PORTAL_APM.dbo.CORE_Departamentos padreDep ON padreDep.codigo_uorg_th = pc.codigo_uorg
WHERE NOT EXISTS (SELECT 1 FROM PORTAL_APM.dbo.CORE_Departamentos d2 WHERE d2.codigo_uorg_th = o.codigo_uorg)
  AND NOT EXISTS (SELECT 1 FROM PORTAL_APM.dbo.CORE_Departamentos d3 WHERE d3.codigo = o.codigo_uorg);
GO

DROP TABLE #origen_bf;
DROP TABLE #padre_codigo_bf;
GO

PRINT 'Backfill único de unidades TH activas aplicado.';
SELECT COUNT(*) AS total_departamentos, SUM(CASE WHEN origen_th=1 THEN 1 ELSE 0 END) AS sincronizados_desde_th
FROM PORTAL_APM.dbo.CORE_Departamentos;
GO

PRINT 'Trigger trg_sync_th_unidades_to_departamentos creado en Talento_Humano.';
GO
