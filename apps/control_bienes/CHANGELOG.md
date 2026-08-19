# Changelog

## 4.1.0 - 2026-08-13

- Bodega quedó organizada como cuatro funciones consecutivas e independientes: **Requisiciones**, **Órdenes de compra**, **Ingresos con Factura** y **Egresos de bodega**.
- Requisiciones incorporó una ficha propia para solicitante, destinatario, motivo y productos, además del historial de cantidades solicitadas y entregadas.
- Órdenes de compra reutiliza la lógica existente de creación, edición y aprobación dentro de una pantalla exclusiva del segundo paso.
- Ingresos con Factura conserva la ficha desarrollada y ahora muestra el recorrido común del proceso como tercer paso.
- Egresos de bodega dispone de una bandeja de requisiciones pendientes, cantidades disponibles para entregar e historial de salidas confirmadas.
- Las cuatro funciones se incorporan automáticamente a Gestión de Permisos con lectura, creación, edición y control total independientes.
- La factura recuperó el bloque **Información del producto** sin el campo Grupo Producto; ahora muestra existencia, precio unitario y aplicación contable del renglón seleccionado.
- Al seleccionar otra línea del detalle se actualizan automáticamente el código contable, su descripción y el total correspondiente.
- Las facturas registradas abren en modo consulta: fecha, número, IVA, proveedor, concepto y productos solo se habilitan al pulsar **Editar**, mientras la orden automática permanece siempre bloqueada.
- El alta de proveedor utiliza un icono `+` compatible con la versión de Font Awesome cargada por el sistema.
- El menú lateral y Gestión de Permisos ahora consumen un catálogo central único; los nuevos módulos publicados en navegación se incorporan automáticamente a la matriz con acciones de lectura, creación, edición y control total.
- **Ingresos con Factura** quedó disponible en Gestión de Permisos con la sección “Facturas e ingresos a bodega”.
- El listado de ingresos con factura adoptó una presentación moderna y compacta con las columnas número, fecha, código de proveedor, descripción, monto, estado y acceso a la ficha.
- Las facturas ya no se consultan al abrir el apartado: el usuario debe pulsar **Mostrar datos**, siguiendo el mismo comportamiento de carga diferida de Inventario General.
- La consulta visible se conserva temporalmente dentro de la misma sesión y se libera al superar el tiempo configurado fuera del apartado o al iniciar una sesión nueva.
- La creación de facturas muestra el código junto al nombre del proveedor y reemplazó el enlace textual de alta por un botón compacto con icono.
- Se modernizó la tarjeta de datos de la factura con campos adaptables, mejor jerarquía visual y controles contenidos correctamente en cualquier ancho de pantalla.
- El encabezado de detalle de productos se compactó para eliminar textos redundantes y aprovechar mejor el espacio vertical.
- La ficha permite escanear facturas PDF en el navegador, revisar la cabecera y los productos detectados antes de aplicarlos y asociar proveedor, IVA y artículos con sus respectivos maestros.
- Los artículos detectados por PDF conservan el precio unitario registrado en inventario; las coincidencias dudosas no se agregan automáticamente.
- La ficha de factura eliminó los campos visuales de RUC, código de proveedor y estado para concentrarse en los datos necesarios de captura.
- Agregar producto ahora utiliza un apartado compacto de preparación: primero se selecciona el artículo, luego se completan sus datos y solo al confirmar aparece en la tabla de detalle.
- La tabla dejó de ser un formulario permanente; muestra líneas confirmadas y ofrece acciones discretas para editar o retirar cada producto.
- Se retiró el panel contable permanente y se compactó el resumen tributario para reducir desplazamiento y espacio innecesario.
- La creación de factura incorporó acceso directo para registrar proveedores faltantes y actualización automática del selector al regresar a la ficha.
- El precio unitario de cada producto ahora se toma de inventario y permanece bloqueado; la aplicación contable enumera todas las líneas con código, producto, cuenta, precio y total.
- El resumen tributario muestra permanentemente la base 0% y todas las tasas configuradas en el maestro, aunque su valor sea cero, resaltando únicamente las bases aplicadas.
- Se modernizó el catálogo de productos con búsqueda, existencia, precio base, configuración IVA y aplicación contable visibles antes de agregar una línea.
- El listado amplió sus columnas de factura, proveedor, RUC, orden, productos, total y estado, y sustituyó los iconos aislados por una sola acción clara: **Abrir ficha**.
- La consulta y creación dejaron de utilizar ventanas flotantes: cada factura dispone de una página completa con las acciones Nuevo, Editar, Anular, Guardar, Limpiar, Ingresar a bodega, Imprimir y Vista previa.
- El catálogo DataTable de más de diez mil productos quedó integrado en la propia ficha y agrega líneas directamente a la cuadrícula detallada de la factura.
- Se reemplazó el ingreso manual a bodega por el módulo **Ingresos con Factura**.
- El listado de facturas y el selector de productos ahora usan DataTables con paginación y búsqueda ejecutadas en el servidor.
- El proveedor se identifica por su código corto del maestro de proveedores y conserva nombre y RUC como información auxiliar.
- Cada factura genera automáticamente su número de orden de compra mediante el secuencial `ocp`.
- El detalle funciona como una cuadrícula editable con pedido, requisición, cantidad, precio, referencia y cálculos automáticos.
- Cada producto puede marcarse como sujeto o no sujeto a IVA y seleccionar una tasa vigente de `inv_tipos_iva`; la tasa aplicada queda almacenada como valor histórico por línea.
- Se añadieron consulta, edición previa al ingreso, anulación justificada y confirmación del ingreso con actualización de existencias, costo promedio y Kardex.
- Se agregó la migración `inv_20260813_ingresos_factura_v2.sql` y pruebas de humo específicas del módulo.

## 2026-08-06

- Se rediseñó la creación de solicitudes con un formulario por pasos, tarjetas de productos, controles de cantidad, identificación de tipo de bien, resumen automático y distribución adaptable a móviles.
- Se reemplazó el registro manual de salidas por una bandeja digital de notas de pedido dentro de Egresos.
- Las solicitudes mixtas se dividen automáticamente: consumo corriente en una nota con cantidades y activos fijos en una nota individual por unidad.
- Bodega visualiza pedido, entregado, pendiente, existencia y disponibilidad antes de confirmar cada despacho.
- Las entregas parciales conservan automáticamente el saldo pendiente de la nota.
- El egreso, el descuento de existencias, el avance de la nota y el movimiento de Kardex se confirman en una sola transacción.
- Se incorporó el Kardex inmutable de egresos con saldo anterior, saldo resultante, documento, centro de consumo, receptor y usuario.
- Se agregó la migración idempotente `inv_20260806_flujo_digital_egresos.sql`, compatible con SQL Server 2014 y sin cambios en Ingresos.
- El menú de Bodega ahora expone la opción `Pedidos y Egresos`.

## 2026-08-05

- Se reparó la Búsqueda Global, cuyo controlador intentaba cargar una clase con un nombre distinto al declarado por el modelo; ahora busca IDs y códigos relacionados, incluye el directorio y la estructura organizacional vigentes de Talento Humano para Centros de Consumo, ordena por relevancia, respeta el límite total y excluye sus propias consultas de la Bitácora.
- El lápiz del Catálogo de Ítems ahora abre el producto maestro correspondiente en Ítems del Sistema, que es el apartado autorizado para modificarlo.
- El Catálogo de Ítems oculta códigos y secuenciales en tarjetas, tablas y detalles, aunque conserva su búsqueda interna; además, estrena un diseño visual de identidad azul con resumen, buscador destacado y grupos diferenciados.
- Los filtros de Reportes Varios ahora se adaptan a cada listado, permiten consultar por ID exacto, rango de IDs y rango de fechas cuando existe una fecha aplicable; además, no heredan parámetros al cambiar de pestaña ni limitan las compras históricas con fechas implícitas.
- Se corrigieron las rutas físicas de la impresión de Reportes Varios y de las actas de ingreso/egreso, que apuntaban fuera de sus subcarpetas reales.
- La administración de usuarios y de todos los tiempos quedó protegida exclusivamente para el rol Administrador, tanto en la interfaz como en el servidor.
- Las abreviaturas nulas de Unidades de Medida ahora se muestran como `—` sin avisos deprecados de PHP 8.4.
- Ítems del Sistema y Maestros ahora guardan mediante AJAX, actualizan únicamente la fila afectada y conservan búsqueda, página y posición de la lista.
- La fila creada o editada se mantiene visible y se resalta después del guardado, sin recargar la página ni abrir pestañas adicionales.
- Se agregó un tiempo de inactividad opcional por usuario; las cuentas sin valor particular heredan la configuración global.
- La tolerancia posterior al aviso de inactividad quedó fijada en 5 minutos para todos los usuarios.
- Se restauraron `inventario` y `Talento_Humano` desde los respaldos oficiales entregados para el proyecto.
- Se seleccionó la copia completa más reciente de Talento Humano (2026-08-02), con 620 funcionarios y 218 activos.
- Se corrigieron la instancia local de SQL Server y el manejo del puerto para conexiones a instancias con nombre.
- El instalador ahora localiza ambos respaldos desde la estructura real del proyecto y aplica las migraciones versionadas en orden.
- Se corrigió la migración del modelo de inventario v2 para recompilar columnas nuevas y usar las opciones requeridas por índices filtrados en SQL Server.
- Antes de la restauración se generaron respaldos recuperables de las dos bases existentes.

## 2026-08-03

- Se rediseñó el panel de tiempos con controles en minutos, tarjetas adaptables, mejor jerarquía visual y acciones que ya no se cortan en anchos medianos.
- El menú lateral conserva la decisión de permanecer abierto o contraído al navegar; en móvil usa un estado independiente y se cierra al seleccionar una opción.
- Inventario General conserva la tabla mientras se trabaja en el apartado y la libera únicamente al iniciar una nueva sesión o después de permanecer fuera durante el intervalo configurable de 5 a 60 minutos.
- Se rediseñó la carga bajo demanda con un estado inicial limpio, indicador de consulta, explicación visible y acciones diferenciadas para mostrar o actualizar datos.
- Se añadió un aviso de inactividad con cuenta regresiva y botón “Sí, continuar”; la tolerancia configurable es de 5, 7 o 10 minutos antes del cierre automático.
- La carga inicial de Inventario General dejó de consultar innecesariamente el catálogo completo de más de ocho mil productos.
- Se optimizó Maestros con paginación de 25/50/100 registros y búsqueda completa en servidor para catálogos y funcionarios.
- El catálogo de activos fijos agrupa nombres iguales por categoría, conserva todos los códigos patrimoniales y muestra la cantidad de unidades individuales.
- Se verificó que no existen productos, grupos, cédulas ni secuenciales patrimoniales duplicados; se añadieron índices únicos preventivos para códigos de categorías y productos.
- Se actualizó exclusivamente la opción Maestros: grupos de productos separados entre códigos `1.3` y `1.4`, catálogo separado entre 8.302 bienes de consumo corriente y 4.056 activos fijos.
- Los grupos de consumo ahora consultan 28 áreas/departamentos activos de Talento Humano y los centros de consumo consultan 218 funcionarios activos con su cargo y área.
- Las secciones provenientes de Talento Humano y el catálogo individual de activos fijos son de solo consulta dentro de Maestros.
- Se restauro `Talento_Humano` desde el respaldo completo del 2026-08-02, con 620 funcionarios y 218 activos.
- Control de Bienes ahora consulta el directorio oficial mediante `vw_th_directorio_empleados` y vistas de integracion en `inventario`.
- Se conservaron las referencias historicas locales y sus claves foraneas; la tabla espejo ya no es la fuente principal de lectura.
- Se agrego la migracion reproducible `inv_20260803_talento_humano_vistas.sql` compatible con SQL Server 2014.
- Se documento la propuesta funcional para catalogos, IVA, egresos digitales, activos fijos, kardex y parametros por persona.

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
