/* ============================================================================
   NOTIFICACIONES REALES — habilita notificaciones globales (id_usuario NULL)
   ============================================================================
   BD destino: PORTAL_APM.

   CORE_Notificaciones ya existía completa (tipo/prioridad/leida/url_accion/
   estado) y el código PHP en varios puntos (DashboardModel::getAlertasPendientes,
   NotificacionesController::index) ya asumía `id_usuario IS NULL` como
   "notificación global, visible para todos" -- pero la columna real estaba
   definida NOT NULL, así que esa rama nunca pudo insertarse: la tabla
   siempre tuvo 0 filas, el generador (ver core/NotificacionGenerador.php)
   es el primer código que realmente escribe en esta tabla.

   Idempotente: se puede ejecutar múltiples veces.
   ============================================================================ */

USE PORTAL_APM;
GO

IF EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID(N'dbo.CORE_Notificaciones')
      AND name = N'id_usuario'
      AND is_nullable = 0
)
BEGIN
    ALTER TABLE dbo.CORE_Notificaciones ALTER COLUMN id_usuario INT NULL;
    PRINT 'CORE_Notificaciones.id_usuario ahora acepta NULL (notificación global).';
END
ELSE
    PRINT 'CORE_Notificaciones.id_usuario ya aceptaba NULL -- sin cambios.';
GO

PRINT 'notificaciones_reales.sql ejecutado correctamente.';
GO
