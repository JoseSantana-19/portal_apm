/* Enlaza grupos y centros de consumo con el personal oficial sin borrar textos históricos. */
USE [inventario];
GO

IF COL_LENGTH('dbo.inv_grupo_centros_consumo', 'representante_id') IS NULL
    ALTER TABLE dbo.inv_grupo_centros_consumo ADD representante_id INT NULL;

IF COL_LENGTH('dbo.inv_centros_consumo', 'funcionario_id') IS NULL
    ALTER TABLE dbo.inv_centros_consumo ADD funcionario_id INT NULL;
GO

IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_inv_grupo_centros_representante')
    ALTER TABLE dbo.inv_grupo_centros_consumo WITH CHECK
    ADD CONSTRAINT FK_inv_grupo_centros_representante
        FOREIGN KEY (representante_id) REFERENCES dbo.inv_talento_personal(id);

IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_inv_centros_funcionario')
    ALTER TABLE dbo.inv_centros_consumo WITH CHECK
    ADD CONSTRAINT FK_inv_centros_funcionario
        FOREIGN KEY (funcionario_id) REFERENCES dbo.inv_talento_personal(id);
GO

CREATE OR ALTER VIEW dbo.vw_inv_grupos_centros_personal
AS
SELECT gcc.id, gcc.codigo, gcc.nombre, gcc.representante_id,
       COALESCE(NULLIF(persona.nombre, ''), gcc.representante) AS representante,
       persona.identificacion AS representante_identificacion,
       persona.area AS representante_area,
       persona.estado AS representante_activo
FROM dbo.inv_grupo_centros_consumo gcc
LEFT JOIN dbo.inv_talento_personal persona ON persona.id = gcc.representante_id;
GO

CREATE OR ALTER VIEW dbo.vw_inv_centros_consumo_personal
AS
SELECT cc.id, cc.grupo_id, cc.codigo, cc.nombre, cc.funcionario_id,
       COALESCE(NULLIF(persona.nombre, ''), cc.funcionario) AS funcionario,
       persona.identificacion AS funcionario_identificacion,
       persona.area AS funcionario_area,
       persona.estado AS funcionario_activo,
       gcc.nombre AS grupo_nombre
FROM dbo.inv_centros_consumo cc
JOIN dbo.inv_grupo_centros_consumo gcc ON gcc.id = cc.grupo_id
LEFT JOIN dbo.inv_talento_personal persona ON persona.id = cc.funcionario_id;
GO
