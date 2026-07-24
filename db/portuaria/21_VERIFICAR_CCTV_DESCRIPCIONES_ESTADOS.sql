USE PortuariaDemo;
GO

/* =========================================================
   Verificación: CCTV guarda IDs numéricos, pero se interpreta
   con la tabla general dbo.estados.
========================================================= */

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
LEFT JOIN dbo.estados etipo
    ON bc.tipo_registro = etipo.idestado
LEFT JOIN dbo.estados eestado
    ON bc.estado_camara = eestado.idestado
LEFT JOIN dbo.estados enivel
    ON bc.nivel_alerta = enivel.idestado
ORDER BY bc.id_bitacora_camara DESC;
GO

SELECT
    e.idestado,
    e.descripcion,
    e.detalle,
    e.estado
FROM dbo.estados e
WHERE e.idestado IN (100, 101, 102, 103, 104, 105, 106)
ORDER BY e.idestado;
GO
