# Operación integral de Talento Humano

Vigente desde el 25 de agosto de 2026. Esta guía describe las reglas funcionales incorporadas por `migracion_operacion_talento_20260825.sql`.

## Acción de Personal y series

Antes del vínculo contractual, el expediente define el **Régimen laboral**. `LOSEP` admite Nombramiento Permanente, Nombramiento Provisional o Contrato Ocasional y genera la Acción de Personal completa. `Código del Trabajo` fija Contrato Indefinido y genera el Formulario Laboral Abreviado con serie `CdgT`.

El régimen del expediente es la fuente autoritativa. Al registrar el documento, SQL Server guarda una instantánea del régimen y asigna el correlativo definitivo dentro de la misma transacción, con bloqueo de concurrencia para evitar duplicados.

El número definitivo se asigna al insertar el documento, dentro de la misma transacción SQL y bajo un bloqueo por serie y año. La selección en pantalla solo muestra una vista previa y no consume números.

| Serie | Uso |
|---|---|
| `MP` | Ingreso, reingreso, restitución, reintegro, ascenso, traslado, traspaso, intercambio, comisión, subrogación, encargo, cesación, destitución y otros movimientos. |
| `CA` | Cambio administrativo. |
| `LI` | Licencia. |
| `RD` | Sanciones o régimen disciplinario. |
| `VAC` | Vacaciones. |

Cada serie reinicia su contador al cambiar el año. Los documentos históricos no se renumeran.

## Vacaciones

Vacaciones dejó de ser un prototipo. Su fuente única es una Acción de Personal de tipo `VACACIONES`, temporal, aprobada y con fechas desde/hasta. Inicio muestra las vacaciones vigentes y enlaza al detalle. El módulo clasifica automáticamente cada registro como programado, vigente o finalizado.

## Reingreso y antigüedad

Cada alta abre un período de vinculación y cada salida aprobada lo cierra. Un reingreso abre un período nuevo sin borrar el anterior. La antigüedad efectiva suma únicamente los días de los períodos vinculados, por lo que una interrupción de tres años no se contabiliza como servicio.

Los hitos de 5, 10, 15, 20, 25 y 30 años se calculan sobre esa antigüedad acumulada. Inicio y Reportes Generales muestran todos los hitos que ocurren durante el año institucional, incluidos los ya cumplidos.

## Paz y Salvo

Solo puede iniciarse desde una Acción de Personal aprobada de cesación o destitución. El documento contiene cinco certificaciones independientes:

1. Jefe inmediato.
2. Talento Humano.
3. Financiero.
4. Administrativo.
5. Tecnologías de la Información.

Cada área registra resultado, datos, responsable, cargo, sumilla y observaciones. El documento solo cambia a `COMPLETO` cuando las cinco secciones están conformes y únicamente entonces puede cerrarse. Creación, actualización, cierre e impresión quedan auditados.

## Reportes

Reportes Generales incorpora estadísticas de género e hitos de servicio. La descarga Excel genera un archivo `.xlsx` OOXML real con hojas separadas para funcionarios, historial, acciones, estudios, jornadas, vigencias, períodos, vacaciones, hitos y Paz y Salvo.

## Ubicación socioeconómica

La página 4 permite pegar un enlace HTTPS oficial de Google Maps o seleccionar el punto directamente en el mapa. El portal conserva coordenadas, origen, referencia e indicaciones en SQL Server; la captura cartográfica y el QR se almacenan con nombres opacos dentro de `PORTAL_PRIVATE_DIR/socio-geolocation`, nunca bajo `public/`.

Los enlaces cortos se resuelven mediante `POST`, CSRF y una lista cerrada de dominios Google. El PDF crea un enlace universal `https://www.google.com/maps/search/?api=1&query=latitud,longitud`; el QR contiene ese mismo destino. El acceso al proveedor de mosaicos puede revelar las coordenadas consultadas, por lo que producción debe definir un proveedor institucional autorizado mediante `PORTAL_MAP_TILE_URL` y mantener su atribución en `PORTAL_MAP_ATTRIBUTION`.

## Legalización y expediente documental

Los formatos oficiales no cambian. El operador imprime el PDF generado, obtiene las firmas manuscritas, escanea el documento completo en un único PDF sin contraseña y lo incorpora desde **Documento firmado**. La ficha principal puede cargarse al existir el expediente; la Acción de Personal debe estar aprobada; el estudio socioeconómico, registrado; y el Paz y Salvo, cerrado.

El archivo se conserva fuera del árbol público en `PORTAL_PRIVATE_DIR/documentos-firmados`, con nombre opaco, límite de 20 MB, versión, SHA-256, usuario, IP y fecha. Una nueva carga no destruye la anterior: la versión previa queda como `REEMPLAZADO` y la última como `FIRMADO`. Tanto la carga como la consulta y descarga se auditan.

El historial jerárquico reúne por funcionario los períodos y eventos vinculados: Acciones de Personal, vacaciones, jornadas temporales, movimientos internos, estudios socioeconómicos, Paz y Salvo y versiones firmadas. Los eventos técnicos no vinculados a un funcionario, como accesos o administración de seguridad, permanecen en los reportes de auditoría para evitar mezclar la bitácora técnica con la trayectoria laboral.

## Cuentas del personal asistente

El rol `Asistente de Talento Humano` se asigna desde **Gestión de Usuarios** únicamente a funcionarios activos cuyo cargo institucional sea compatible. En el catálogo actual corresponden `ASISTENTE DE TALENTO HUMANO` y la denominación histórica `ASITENTE DE TTHH`. Al seleccionar al funcionario, el portal deriva el usuario desde su cédula, propone el rol y deshabilita las opciones incompatibles; SQL Server repite la validación al crear la cuenta.

Para habilitar una cuenta se requiere:

1. Funcionario activo y todavía sin usuario vinculado.
2. Cargo asociado al rol en `th_puesto_rol_mapa`.
3. Correo válido.
4. Clave inicial de al menos 12 caracteres con mayúscula, minúscula, número y símbolo.

El asistente ve Inicio, Nómina, Acción de Personal, Movimientos internos, Estudio Socioeconómico, Vacaciones, Paz y Salvo, Biblioteca, Estructura y cargos y Reportes Generales. No recibe Gestión de Usuarios, Roles y Permisos, Políticas, logs, reportes de auditoría, prototipos ni permisos de eliminación. La clave inicial debe cambiarse en el primer acceso y el segundo factor se administra desde la seguridad de la cuenta.

## Exclusiones vigentes

Asistencia, Ausencias, Desempeño y Capacitación continúan como prototipos. La página 4 socioeconómica registra ubicación, referencias y QR; sus imágenes se conservan fuera del árbol público.
