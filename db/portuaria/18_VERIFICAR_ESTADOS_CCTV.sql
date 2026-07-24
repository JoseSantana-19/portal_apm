USE PortuariaDemo;
GO

SELECT *
FROM dbo.estados
WHERE idestado IN (100,101,102,103)
ORDER BY idestado;
GO

SELECT
    COLUMN_NAME AS columna,
    DATA_TYPE AS tipo_dato,
    IS_NULLABLE AS permite_null
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'bitacora_camaras'
  AND COLUMN_NAME IN ('estado_camara', 'tipo_registro');
GO

SELECT
    tipo_registro,
    CASE
        WHEN tipo_registro = 102 THEN 'ACTIVIDAD_DIARIA'
        WHEN tipo_registro = 103 THEN 'NOVEDAD_CAMARA'
        ELSE 'DESCONOCIDO'
    END AS tipo_registro_texto,
    COUNT(*) AS total
FROM dbo.bit_camaras
GROUP BY tipo_registro
ORDER BY tipo_registro;
GO

SELECT
    estado_camara,
    CASE
        WHEN estado_camara = 100 THEN 'NO OPERATIVA'
        WHEN estado_camara = 101 THEN 'OPERATIVA'
        ELSE 'NO APLICA'
    END AS estado_camara_texto,
    COUNT(*) AS total
FROM dbo.bit_camaras
GROUP BY estado_camara
ORDER BY estado_camara;
GO
