/* ============================================================================
   Migración: PORTAL_APM — borrar tablas genéricas huérfanas de los módulos
   integrados (TH, Bienes, Bitácoras).

   Contexto: estas tablas vivían dentro de PORTAL_APM como soporte de las
   reescrituras nativas iniciales (modules/Talento_Humano, modules/Inventario,
   modules/Control_Bienes, modules/Bitacoras) que ya se dieron de baja — la
   integración real de cada módulo vive en su propia BD:
     - TH_*      → reemplazada por Talento_Humano.dbo.th_empleados (real)
     - BIENES_*  → reemplazada por inventario.dbo.inv_inventario (real)
     - BIT_*     → reemplazada por PortuariaDemo.dbo.bit_visitas (real)

   Verificado antes de escribir esta migración que ningún archivo PHP
   referencia ya estas tablas/vistas (modules/Central/models/DashboardModel.php
   y modules/Control_Acceso/models/AccesoModel.php ya se repuntaron a las BDs
   reales vía db/identidad_cross_db.sql y el fix directo en AccesoModel).

   ACCESO_* NO se toca — Control de Acceso sigue siendo nativo de PORTAL_APM,
   sin BD externa equivalente.

   Idempotente (los DROP llevan IF EXISTS). Ejecutar sobre PORTAL_APM.
   ============================================================================ */

USE PORTAL_APM;
GO

-- Vistas que dependen de las tablas a borrar
IF OBJECT_ID('vw_FichaEmpleado', 'V') IS NOT NULL DROP VIEW vw_FichaEmpleado;
IF OBJECT_ID('vw_KPIs_TH', 'V')       IS NOT NULL DROP VIEW vw_KPIs_TH;
IF OBJECT_ID('vw_KPIs_Bienes', 'V')   IS NOT NULL DROP VIEW vw_KPIs_Bienes;
IF OBJECT_ID('vw_KPIs_Bitacoras', 'V') IS NOT NULL DROP VIEW vw_KPIs_Bitacoras;
GO

-- Talento Humano (huérfanas)
IF OBJECT_ID('TH_Adendas', 'U')          IS NOT NULL DROP TABLE TH_Adendas;
IF OBJECT_ID('TH_Novedades_Medicas', 'U') IS NOT NULL DROP TABLE TH_Novedades_Medicas;
IF OBJECT_ID('TH_Auditoria', 'U')        IS NOT NULL DROP TABLE TH_Auditoria;
IF OBJECT_ID('TH_Contratos', 'U')        IS NOT NULL DROP TABLE TH_Contratos;
IF OBJECT_ID('TH_Empleados', 'U')        IS NOT NULL DROP TABLE TH_Empleados;
GO

-- Bienes (huérfanas)
IF OBJECT_ID('BIENES_Movimientos', 'U') IS NOT NULL DROP TABLE BIENES_Movimientos;
IF OBJECT_ID('BIENES_Auditoria', 'U')   IS NOT NULL DROP TABLE BIENES_Auditoria;
IF OBJECT_ID('BIENES_Activos', 'U')     IS NOT NULL DROP TABLE BIENES_Activos;
IF OBJECT_ID('BIENES_Categorias', 'U')  IS NOT NULL DROP TABLE BIENES_Categorias;
GO

-- Bitácoras (huérfanas)
IF OBJECT_ID('BIT_Archivos', 'U')   IS NOT NULL DROP TABLE BIT_Archivos;
IF OBJECT_ID('BIT_Auditoria', 'U')  IS NOT NULL DROP TABLE BIT_Auditoria;
IF OBJECT_ID('BIT_Eventos', 'U')    IS NOT NULL DROP TABLE BIT_Eventos;
IF OBJECT_ID('BIT_Categorias', 'U') IS NOT NULL DROP TABLE BIT_Categorias;
GO

PRINT 'Tablas genéricas huérfanas de TH/Bienes/Bitácoras eliminadas de PORTAL_APM.';
GO
