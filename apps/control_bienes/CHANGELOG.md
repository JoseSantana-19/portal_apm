# Changelog

## 4.1.0 - 2026-07-27

- El grupo activo de Ítems del Sistema filtra la lista, la búsqueda, la navegación y las plantillas disponibles.
- Los grupos y centros de consumo enlazan sus funcionarios con los IDs oficiales de Talento Humano.
- El autocompletado del receptor de un egreso usa `funcionario_id` y deja de depender de comparaciones de nombres.
- Se incorporó la migración `inv_20260727_centros_consumo_personal.sql` con claves foráneas y vistas normalizadas.
- Se restauró y verificó la base `Talento_Humano` suministrada, con 619 funcionarios y 217 activos.
- Se respaldó el estado anterior antes de restaurar y se conservó una copia verificada del `.bak` recibido.
- Se implementó `sp_th_buscar_responsables` y se conectó al modelo utilizado para asignar custodios.
- Se sincronizó el personal con `inv_talento_personal`, incluyendo cargo, área, correo, estado y fecha de sincronización.
- Se instaló el disparador de sincronización y la migración idempotente `th_20260727_responsables_inventario.sql`.
- Se añadió la migración inicial del modelo de inventario v2 para SQL Server 2014.
- Se separaron conceptualmente consumo corriente y activo fijo.
- Se añadieron terceros, activos fijos, cabecera de cierre y saldos históricos.
- Se añadió un procedimiento almacenado para buscar funcionarios por cédula o nombre.
- Se hizo atómica la reserva de números secuenciales.
- El tablero muestra por separado los totales de consumo corriente y activo fijo.
- El responsable solo puede asignarse al crear o editar un bien de activo fijo.
- Se importaron 4.056 activos históricos desde `activos.DBF`: 1.976 vigentes y 2.080 dados de baja.
- Se documentó la clasificación histórica `CA` (control administrativo) sin mezclarla con `AF`.
- Se automatizó la clasificación `CC`/`AF` a partir del código contable de la categoría.
- `aplica_iva` pasó a ser una bandera sí/no y la tasa se toma del período vigente.
