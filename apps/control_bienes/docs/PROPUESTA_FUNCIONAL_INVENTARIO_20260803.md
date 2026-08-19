# Propuesta funcional de inventario

Fecha: 2026-08-03

## Decisiones recomendadas

### 1. Tiempo de sesion personalizado

Mantener `inv_parametros` para el tiempo predeterminado del sistema y crear una
tabla de excepciones por usuario. El orden de aplicacion debe ser:

1. tiempo configurado para la persona;
2. tiempo configurado para su rol;
3. tiempo general de `inv_parametros`.

La interfaz debe permitir heredar el valor general o asignar minutos concretos.
Todo cambio debe quedar en bitacora. No conviene duplicar una fila completa de
parametros por cada persona.

### 2. Catalogo de consumo corriente y activos fijos

No se recomienda crear dos tablas de productos. Se debe conservar el catalogo
unico `inv_productos`, usando `tipo_bien` como discriminador y mostrando dos
vistas o pestanas operativas:

- `CC`: consumo corriente, administrado por cantidades.
- `AF`: activo fijo, administrado por unidades individualizadas.
- `CA`: control administrativo historico, separado de la operacion ordinaria.

Asi se evita duplicar marcas, categorias, unidades e IVA, pero cada pantalla
puede aplicar sus propias reglas.

### 3. IVA y tasas simultaneas

El IVA debe definirse por tasa y vigencia, no como un unico porcentaje global.
El sistema puede tener varias tasas vigentes al mismo tiempo y cada producto o
detalle de movimiento selecciona la que le corresponde. El movimiento guarda
una copia de la tasa aplicada para que el historial no cambie en el futuro.

Modelo recomendado:

- catalogo de tasas (`codigo`, `nombre`, `porcentaje`);
- vigencias (`fecha_desde`, `fecha_hasta`, `activo`);
- referencia de tasa en el producto o en el detalle;
- copia de `porcentaje_iva` y `valor_iva` en cada detalle contabilizado.

Si un mismo producto necesitara dos impuestos acumulados, se debe usar una tabla
de impuestos por detalle. No se deben guardar dos columnas fijas como `iva_1` e
`iva_2`.

### 4. Edicion sin perder la posicion

Crear y editar registros debe hacerse de forma asincrona. La respuesta devuelve
el ID y los datos actualizados para reemplazar solamente la fila afectada. La
lista debe conservar pagina, filtros, busqueda, orden, desplazamiento y fila
seleccionada. Si la fila deja de cumplir el filtro activo, se informa al usuario
antes de retirarla visualmente.

### 5. Flujo digital de egresos

Separar la solicitud de la salida real de bodega:

1. El area genera una solicitud digital con cabecera, centro de consumo y detalle.
2. Bodega revisa cada renglon y marca disponible, parcial o no disponible.
3. El sistema muestra existencias; el bodeguero no escoge una hoja fisica.
4. Al confirmar los productos disponibles, se genera la nota de pedido.
5. Al entregar, se genera el egreso y se descuenta stock en una transaccion.
6. Las entregas parciales conservan pendiente la cantidad faltante.

Estados sugeridos: `BORRADOR`, `EN_REVISION`, `PARCIAL`, `APROBADA`,
`DESPACHADA`, `ANULADA`.

El centro de consumo representa el destino organizacional. El funcionario es el
receptor o custodio relacionado con ese centro; no debe convertirse cada empleado
en un centro de consumo. Al seleccionar el centro, el sistema propone de forma
automatica el funcionario vigente desde Talento Humano y permite registrar quien
recibio realmente.

### 6. Regla documental por tipo de bien

- Activo fijo: cantidad operativa siempre igual a 1 por detalle individual. Si
  se solicitan 10 aires acondicionados, el sistema crea 10 notas vinculadas a la
  misma solicitud, cada una con su codigo, serie y custodio.
- Consumo corriente: permite cantidades mayores a 1. Diez plumas se registran en
  una nota con un detalle de cantidad 10.
- Una nota no debe mezclar activos fijos y consumo corriente; el sistema divide
  automaticamente una solicitud mixta.

La division debe ser automatica y transaccional para evitar que el usuario tenga
que capturar diez veces la misma informacion de cabecera.

### 7. Kardex

El kardex debe ser un libro inmutable de movimientos por producto. Cada renglon
registra fecha y hora, tipo de movimiento, documento origen, entrada, salida,
saldo, costo unitario, costo promedio, bodega, centro de consumo y usuario.

No se deben editar ni borrar movimientos confirmados. Una correccion se registra
como reverso y nuevo movimiento. Para activos fijos, el kardex se consulta por
unidad individual; para consumo corriente, por producto y cantidades.

## Orden de implementacion

1. Integracion de Talento Humano mediante vistas (realizada).
2. Catalogo unico con pantallas separadas para `CC` y `AF`.
3. Tasas de IVA con vigencias simultaneas.
4. Solicitud digital, revision de bodega y generacion automatica de notas.
5. Egreso transaccional y reglas unitarias de activos fijos.
6. Kardex inmutable.
7. Edicion asincrona de maestros conservando la posicion de la tabla.
8. Tiempo de sesion por persona y rol.

El modulo de ingresos queda fuera de este alcance hasta una autorizacion expresa.
