-- =============================================================================
-- Migración: PORTAL_APM — mapeo unidad TH → departamento + rol del portal
-- Para crear cuentas de acceso (CORE_Usuarios) desde empleados de Talento_Humano
-- con autosugerencia de departamento y rol. Idempotente. Keyed por codigo_uorg
-- (estable ante recargas de la BD de Talento Humano).
-- =============================================================================
SET QUOTED_IDENTIFIER ON;
SET ANSI_NULLS ON;
GO
USE [PORTAL_APM];
GO

IF OBJECT_ID('TH_Unidad_Map','U') IS NULL
    CREATE TABLE TH_Unidad_Map (
        codigo_uorg      VARCHAR(20) NOT NULL PRIMARY KEY,
        id_departamento  INT NOT NULL,   -- CORE_Departamentos
        id_rol_director  INT NOT NULL,   -- rol sugerido si el puesto es de jefatura
        id_rol_analista  INT NOT NULL    -- rol sugerido para el resto de puestos
    );
GO

-- Re-siembra (idempotente)
DELETE FROM TH_Unidad_Map;
GO

INSERT INTO TH_Unidad_Map (codigo_uorg, id_departamento, id_rol_director, id_rol_analista) VALUES
('DIR-TH',   10, 11, 12),   -- Dirección de Talento Humano
('DEP-NOM',  10, 11, 12),   -- Nóminas
('DEP-SEL',  10, 11, 12),   -- Selección
('DEP-BS',   10, 11, 12),   -- Bienestar Social
('DIR-PLAN',  3, 19, 19),   -- Planificación Estratégica
('DEP-PLAN',  3, 19, 19),   -- Departamento de Planificación
('DIR-TICS', 11,  1,  1),   -- Gestión de TI  (único rol TI disponible: Administrador TI)
('DIR-JUR',   4,  3,  4),   -- Asesoría Jurídica: Director Jurídico / Abogado
('DIR-TES',   9, 17, 18),   -- Tesorería → Financiera
('DIR-CON',  18, 17, 18),   -- Contabilidad
('DIR-FIN',   9, 17, 18),   -- Dirección Financiera
('DEP-FAC',   9, 17, 18),   -- Facturación
('DEP-TES',   9, 17, 18),   -- Tesorería (dep)
('DEP-CON',  18, 17, 18),   -- Contabilidad (dep)
('DEP-PRE',  19, 17, 18);   -- Presupuesto
GO
