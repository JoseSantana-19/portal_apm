USE PortuariaDemo;
GO

/* =========================================================
   22 - VERIFICACIÓN DE SELECTS CCTV DESDE dbo.estados
   Los selectores del módulo deben usar estos IDs activos:
   100 = NO OPERATIVA
   101 = OPERATIVA
   102 = ACTIVIDAD_DIARIA
   103 = NOVEDAD_CAMARA
   104 = NORMAL
   105 = MEDIO
   106 = CRITICO
========================================================= */

SELECT
    idestado,
    descripcion,
    detalle,
    estado
FROM dbo.estados
WHERE idestado IN (100, 101, 102, 103, 104, 105, 106)
ORDER BY idestado;
GO

SELECT
    COLUMN_NAME AS columna,
    DATA_TYPE AS tipo_dato,
    IS_NULLABLE AS permite_null
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'bitacora_camaras'
  AND COLUMN_NAME IN ('estado_camara', 'tipo_registro', 'nivel_alerta')
ORDER BY COLUMN_NAME;
GO

SELECT TOP 20
    bc.id_bitacora_camara,
    bc.tipo_registro,
    etipo.descripcion AS tipo_registro_texto,
    bc.estado_camara,
    eestado.descripcion AS estado_camara_texto,
    bc.nivel_alerta,
    enivel.descripcion AS nivel_alerta_texto,
    bc.novedad,
    bc.fecha,
    bc.hora_registro
FROM dbo.bit_camaras bc
LEFT JOIN dbo.estados etipo ON bc.tipo_registro = etipo.idestado
LEFT JOIN dbo.estados eestado ON bc.estado_camara = eestado.idestado
LEFT JOIN dbo.estados enivel ON bc.nivel_alerta = enivel.idestado
ORDER BY bc.id_bitacora_camara DESC;
GO
