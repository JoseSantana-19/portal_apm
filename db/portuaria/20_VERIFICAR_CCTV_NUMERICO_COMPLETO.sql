USE PortuariaDemo;
GO

SELECT *
FROM dbo.estados
WHERE idestado IN (100,101,102,103,104,105,106)
ORDER BY idestado;
GO

SELECT
    COLUMN_NAME AS columna,
    DATA_TYPE AS tipo_dato,
    IS_NULLABLE AS permite_null
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'bitacora_camaras'
  AND COLUMN_NAME IN ('estado_camara', 'tipo_registro', 'nivel_alerta');
GO

SELECT TOP 20
    id_bitacora_camara,
    tipo_registro,
    CASE WHEN tipo_registro = 102 THEN 'ACTIVIDAD_DIARIA'
         WHEN tipo_registro = 103 THEN 'NOVEDAD_CAMARA'
         ELSE 'DESCONOCIDO' END AS tipo_texto,
    estado_camara,
    CASE WHEN estado_camara = 100 THEN 'NO OPERATIVA'
         WHEN estado_camara = 101 THEN 'OPERATIVA'
         ELSE 'NO APLICA' END AS estado_camara_texto,
    nivel_alerta,
    CASE WHEN nivel_alerta = 104 THEN 'NORMAL'
         WHEN nivel_alerta = 105 THEN 'MEDIO'
         WHEN nivel_alerta = 106 THEN 'CRITICO'
         ELSE 'DESCONOCIDO' END AS nivel_texto,
    novedad,
    fecha,
    hora_registro
FROM dbo.bit_camaras
ORDER BY id_bitacora_camara DESC;
GO
