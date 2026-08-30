# Guía funcional del módulo Abastecimiento de Bodega

## 1. Objetivo

El módulo **Abastecimiento de Bodega** controla el proceso mediante el cual una compra se convierte en existencias disponibles dentro del inventario institucional.

Integra cuatro momentos dentro de una sola opción del menú:

1. Creación de la orden de compra.
2. Aprobación de la orden.
3. Registro de la factura del proveedor.
4. Ingreso físico, actualización del inventario y generación del Kardex.

El proceso visible ya no utiliza Nota de Pedido. La operación comienza desde una orden de compra o desde una factura directa.

### Requisiciones internas

La lista y la creación de requisiciones se presentan en pestañas independientes. **Nueva requisición** abre una página completa y no utiliza una ventana flotante. Su número no se escribe manualmente: el sistema asigna el siguiente secuencial al guardar.

El número de nota de pedido es opcional y se ingresa en la cabecera. Puede escribirse como código completo o únicamente con su parte numérica. Al buscarlo, el sistema carga fecha, detalle, solicitante, centro/responsable, observaciones y todos los productos de la nota; la información continúa editable antes de guardar.

Al salir del campo, si la nota existe, el sistema completa su fecha y, cuando la referencia lo permite, selecciona el centro y su responsable en el bloque general. Si contiene varios productos, utiliza la fila actual para el primero y agrega una fila adicional por cada producto restante, repitiendo el número de nota. Después se puede escribir otra nota en una nueva fila. Si la nota no existe, conserva el número escrito y permite completar manualmente los demás campos. Cuando dos notas contienen el mismo producto, el sistema suma sus cantidades al guardar y conserva las referencias de ambas.

La grilla reúne pedido, fecha, código y descripción del producto, cantidad, precio promedio, subtotal, referencias y existencia. El centro no se repite por producto: se selecciona una sola vez en el bloque inferior. La búsqueda consulta únicamente coincidencias por código, descripción o grupo y evita cargar el inventario completo al abrir la página.

El **Centro de consumo** corresponde al área o departamento institucional. Después de seleccionarlo, **Responsable del centro** muestra únicamente las personas activas que pertenecen a esa área. La relación entre ambos datos se valida nuevamente al guardar.

La lista histórica ocupa su propia pestaña e incluye filtros inmediatos por texto, estado y fecha.

### Búsqueda en centros y listas

En Centro de consumo, Responsable del centro y en los demás selectores extensos se puede comenzar a escribir el nombre, área, código o dato visible. El sistema abre una lista de coincidencias y permite escoger con el ratón o con las teclas de dirección y **Enter**. Para mantener ágil la pantalla, primero muestra hasta cien coincidencias y solicita escribir más cuando el resultado todavía es muy amplio.

Las tablas comunes con diez filas o más incorporan automáticamente un campo de búsqueda. Los listados que ya tienen filtros especializados o búsqueda paginada conservan sus propios controles.

### Proveedores históricos

El maestro de proveedores incluye los 1.083 registros activos encontrados en `bases/provee.DBF`, además de los cuatro proveedores que ya estaban registrados. La carga conserva literalmente los datos disponibles del archivo anterior y marca su origen para poder distinguirlos de los proveedores creados en el sistema moderno.

La importación puede repetirse de forma segura: actualiza cada proveedor histórico por su código y no elimina proveedores creados manualmente. Existe además una verificación que compara cada campo importado con el archivo DBF.

---

## 2. Estructura de la pantalla

| Etapa | Finalidad |
|---|---|
| Orden | Registrar proveedor, productos, cantidades y precios estimados. |
| Aprobación | Autorizar la orden para continuar con la factura. |
| Factura | Registrar el documento real entregado por el proveedor. |
| Ingreso y movimiento | Confirmar la recepción, actualizar inventario y generar Kardex. |

La pantalla muestra indicadores de órdenes pendientes, facturas pendientes e ingresos realizados. La lista interna permite cambiar entre:

- Órdenes de compra.
- Facturas.
- Ingresos a bodega.
- Movimientos o Kardex.

---

## 3. Flujo desde una orden de compra

Antes de habilitar una nueva orden, el sistema comprueba que exista un período activo. Si no existe, bloquea la creación, edición y aprobación y muestra el motivo al usuario. La validación se repite en el servidor.

### 3.1 Crear la orden

La lista de órdenes y la captura de una nueva orden están en pestañas independientes. **Nueva orden** abre una página completa, no una ventana flotante. Desde allí el usuario completa:

- Proveedor.
- Fecha.
- Detalle de la compra.
- Dirección requirente.
- Memorando de solicitud.
- Responsable que autoriza.
- Acta de selección de proveedor.
- Certificación presupuestaria.
- Plazo de entrega, forma y condiciones de pago.

El proveedor puede buscarse por nombre, RUC o código. Los productos pueden buscarse por código, descripción o grupo.

La creación de factura utiliza el mismo selector con búsqueda: al abrir Proveedor aparece un campo para filtrar por código, nombre o RUC, evitando recorrer manualmente el catálogo histórico completo.

La orden puede originarse desde una requisición o registrarse directamente. Al seleccionar una requisición, sus productos pendientes se copian a la misma grilla y la relación queda registrada. La requisición y la nota de pedido son referencias opcionales.

Cada producto ocupa una fila de la misma grilla. Las columnas disponibles son pedido, requisición, artículo, descripción, referencia, cantidad, precio unitario, subtotal, aplicación y porcentaje de IVA, total y especificaciones técnicas. Al cambiar cantidad, precio o IVA, la pantalla recalcula subtotal, base 0 %, base gravada, IVA y total general.

Desde el selector de proveedor se puede crear uno nuevo sin cerrar ni perder la orden en edición. El proveedor recién registrado se selecciona automáticamente.

Al guardar, el sistema valida que exista un proveedor y al menos un producto, impide productos repetidos, cantidades inválidas y precios negativos, genera un secuencial y deja la orden en estado **PENDIENTE**.

### 3.2 Aprobar la orden

Una orden pendiente debe aprobarse antes de facturarse. Al aprobarla:

- Cambia a **APROBADA**.
- Se registra la fecha de aprobación.
- Se registra el usuario que aprobó.
- Se habilita el registro de la factura.

### 3.3 Registrar la factura asociada

El sistema carga automáticamente el proveedor, la referencia, los productos y las cantidades de la orden. El usuario verifica o completa:

- Número y fecha de factura.
- Porcentaje de IVA.
- Precio unitario real.
- Aplicación de IVA por línea.
- Código presupuestario.
- Documento original.

Los productos y cantidades deben coincidir línea por línea con la orden aprobada.

---

## 4. Factura directa

También puede registrarse una factura sin orden previa:

1. Se abre **Crear o escanear factura**.
2. Se conserva el origen **Factura directa / registro manual**.
3. Se selecciona el proveedor.
4. Se ingresan número, fecha e IVA.
5. Se agregan productos, cantidades, precios y códigos presupuestarios.
6. Se registra la factura.

Para conservar la trazabilidad, el sistema genera automáticamente una orden interna con origen **FACTURA**, estado **APROBADA** y los mismos productos de la factura.

---

## 5. Carga y escaneo de facturas

Se aceptan archivos PDF, JPG, PNG y WEBP de hasta **10 MB**.

La lectura OCR intenta detectar:

- Número de factura.
- Fecha.
- RUC del proveedor.
- Porcentaje de IVA.
- Precios presentes en las líneas.

Si el RUC coincide con un proveedor registrado, el sistema actualiza el selector. Si coincide con una orden aprobada, puede asociarla como origen.

El OCR es una ayuda y sus resultados siempre deben revisarse. La precisión depende de la calidad, resolución, inclinación e iluminación del documento.

El sistema conserva el nombre original, la ruta interna segura, el tipo de archivo, el texto OCR y la fecha de escaneo. El documento puede consultarse posteriormente sin exponer su ubicación interna.

---

## 6. Cálculos de factura

Cada línea contiene producto, grupo, existencia, cantidad, precio, subtotal, IVA, total y código presupuestario.

```text
Subtotal de línea = Cantidad × Precio unitario
IVA de línea = Subtotal de línea × Porcentaje de IVA
Total de línea = Subtotal de línea + IVA de línea
Total factura = Base 0 % + Base gravada + IVA
```

Solo las líneas marcadas como **Aplica IVA** forman parte de la base gravada.

---

## 7. Botón Movimiento

El botón **Movimiento** abre la consulta completa de la factura y muestra:

- Factura, orden, proveedor y fecha.
- Descripción y código del producto.
- Cantidad y precio unitario.
- Subtotal, IVA y total por línea.
- Referencia o código presupuestario.
- Resumen por grupo de producto.
- Base 0 %, base gravada, IVA y total general.
- Acceso al documento original, cuando existe.

Este botón es de consulta y no actualiza existencias.

---

## 8. Ingreso físico a bodega

Una factura **REGISTRADA** aparece como pendiente. Para confirmar la recepción se seleccionan:

- Factura pendiente.
- Fecha de ingreso.
- Responsable de bodega.
- Observaciones o novedades.

Al confirmar, el sistema ejecuta una sola transacción. Si algo falla, toda la operación se revierte.

Por cada producto:

1. Bloquea temporalmente su registro.
2. Obtiene existencia y costo anteriores.
3. Suma la cantidad recibida.
4. Calcula la nueva existencia.
5. Recalcula el costo promedio ponderado.
6. Actualiza el inventario.
7. Guarda el detalle histórico.
8. Genera la entrada de Kardex.

Finalmente, la factura cambia a **INGRESADA**, la orden a **CERRADA**, se genera el secuencial del ingreso y se registra la operación en bitácora.

Una factura ingresada no puede procesarse nuevamente.

---

## 9. Costo promedio ponderado

```text
Nuevo costo promedio =
((Existencia anterior × Costo anterior) + (Cantidad recibida × Precio nuevo))
÷ Nueva existencia
```

Ejemplo: existen 10 unidades a $5 y se reciben 5 a $8.

```text
Nueva existencia = 15
Nuevo costo = ((10 × 5) + (5 × 8)) ÷ 15 = $6
```

El valor se conserva con cuatro decimales.

---

## 10. Kardex y trazabilidad

Cada producto ingresado genera un registro con:

- Tipo de movimiento **INGRESO**.
- Documento y secuencial relacionado.
- Cantidad de entrada.
- Saldo anterior y resultante.
- Responsable.
- Usuario que registró.
- Observación vinculada a la factura.

Esto permite conocer qué ingresó, cuánto, cuándo, desde qué factura y orden, quién fue responsable y cómo cambió la existencia.

---

## 11. Estados

| Documento | Estado | Significado |
|---|---|---|
| Orden | PENDIENTE | Esperando aprobación. |
| Orden | APROBADA | Autorizada para facturarse. |
| Orden | CERRADA | La factura ya ingresó a bodega. |
| Factura | REGISTRADA | Pendiente de recepción. |
| Factura | INGRESADA | Inventario y Kardex actualizados. |

---

## 12. Permisos

Solo el **Administrador** puede modificar permisos. Abastecimiento puede configurarse por separado para Órdenes, Facturas, Ingresos y Kardex.

Cada sección permite asignar:

- Ver o lectura.
- Crear.
- Editar o procesar.
- Control total.

La eliminación no forma parte de la matriz y queda reservada al Administrador. La validación se realiza en el servidor, por lo que no puede evitarse escribiendo una URL manualmente.

---

## 13. Validaciones principales

- Las modificaciones requieren solicitudes POST.
- Cada documento debe tener al menos una línea válida.
- No se permiten productos repetidos.
- Las cantidades deben ser mayores a cero.
- Los precios no pueden ser negativos.
- Solo se factura una orden aprobada.
- La factura debe coincidir con la orden vinculada.
- Solo una factura registrada puede ingresar.
- El responsable es obligatorio.
- Los archivos se validan por tipo real y tamaño.
- Las operaciones críticas usan transacciones y bloqueo de registros.
- Los eventos relevantes se guardan en bitácora.

---

## 14. Ejemplo para explicar el proceso

1. Se crea una orden por 10 taladros a un precio estimado de $80.
2. La orden queda pendiente y luego es aprobada.
3. El proveedor entrega la factura por 10 taladros a $82.
4. El usuario registra o escanea la factura y revisa el IVA.
5. Bodega consulta **Movimiento** y confirma los productos.
6. Se selecciona al responsable y se confirma el ingreso.
7. El inventario suma 10 unidades y recalcula el costo promedio.
8. Se crea el Kardex, la factura queda ingresada y la orden cerrada.

---

## 15. Guion breve para presentación

> El módulo reúne en una sola opción todo el proceso desde la orden de compra hasta la actualización del inventario. Primero se crea y aprueba la orden. Después se registra la factura manualmente o se adjunta un PDF o imagen para ayudar a detectar sus datos. El botón Movimiento permite revisar productos, cantidades, precios, IVA y referencias. Finalmente, al confirmar la recepción, el sistema actualiza existencias, recalcula el costo promedio, genera el Kardex, cierra la orden y conserva la trazabilidad con el proveedor, la factura, el responsable y el usuario que realizó la operación.

---

## 16. Registro y movimiento de egresos

La pantalla de egresos comienza con una cabecera única. El número de egreso se genera al grabar; el usuario completa la fecha, el ingreso de referencia cuando corresponda, el área, el centro de consumo o receptor y el detalle de la entrega.

La tabla no se presenta como un “egreso directo” separado. Se abre únicamente mediante el botón **Movimiento**, después de validar la cabecera. Desde esa ventana se agregan o eliminan productos y se revisan grupo, existencia, cantidad, costo promedio, subtotal y saldo posterior.

Al confirmar, el sistema valida nuevamente el stock, registra el egreso, resta las cantidades de las existencias y genera el Kardex dentro de una sola transacción. Así, la recepción de una factura suma inventario y el egreso lo resta sin alterar el historial.

## 17. Archivos relacionados

- `modules/Control_Bines/views/monitoreo/egresos.php`.
- `modules/Control_Bines/controllers/MonitoreoController.php`.
- `modules/Control_Bines/models/InvAbastecimiento.php`.
- `database/migrations/inv_20260808_abastecimiento_bodega.sql`.
- `database/migrations/inv_20260808_facturas_documentos.sql`.
- `database/migrations/inv_20260808_permisos_granulares.sql`.

Documento actualizado: **8 de agosto de 2026**.
