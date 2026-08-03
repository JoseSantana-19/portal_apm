# Modelo de inventario v2

La migración `database/migrations/inv_20260727_modelo_inventario.sql` incorpora la primera fase del nuevo diseño sin eliminar ni transformar datos existentes.

## Decisiones funcionales

- `CC` identifica bienes de consumo corriente y `AF` activos fijos.
- El `id` interno se mantiene separado del número secuencial y del código visible.
- El responsable permanente se almacena únicamente en `inv_activos_fijos`.
- Los funcionarios, proveedores y otros beneficiarios se normalizan mediante `inv_terceros`.
- Cada período admite un solo cierre mediante la restricción única de `inv_cierres_periodo`.
- Los totales de consumo corriente y activo fijo se almacenan por separado.
- Los saldos de cierre son históricos y no deben eliminarse.
- Los valores monetarios nuevos utilizan `DECIMAL(19,4)`.

## Aplicación

1. Generar un respaldo completo de la base `inventario`.
2. Ejecutar la migración en un ambiente de pruebas con SQL Server 2014.
3. Revisar los códigos heredados y llenar `codigo_legacy` antes de migrar datos DBF.
4. Validar duplicados de código e identificación antes de activar la migración de datos.
5. No ejecutar todavía sobre producción hasta incorporar el servicio de cierre transaccional.

## Procedimiento de Talento Humano

`sp_inv_buscar_funcionario` consulta la copia local sincronizada y permite buscar por cédula o nombre. La sincronización desde Talento Humano debe marcar funcionarios inactivos en vez de eliminarlos, para conservar la historia.

## Pendientes de la segunda fase

- Servicio transaccional de cierre y generación de saldos.
- Promedio ponderado en ingresos.
- Asociación de `proveedor_id` en documentos de ingreso.
- Adaptador configurable de consulta SRI.
- Migración controlada desde DBF con reporte de inconsistencias.
