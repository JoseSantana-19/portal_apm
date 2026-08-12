# Matriz de campos - formatos oficiales APM

Esta matriz define la fuente de cada dato impreso. Prioridad: registro guardado del formulario, maestro del empleado, catalogos institucionales y, finalmente, espacio en blanco para firma manual.

## Accion de Personal - 2 paginas

| Seccion oficial | Campo impreso | Origen |
|---|---|---|
| Cabecera | Numero y fecha de elaboracion | `th_acciones_personal` |
| Servidor | Apellidos, nombres, tipo y numero de identificacion | `th_empleados` |
| Vigencia | Desde y hasta | `th_acciones_personal` |
| Tipo de accion | Casillero seleccionado y detalle de Otro | `tipo_accion`, `detalle_otro` |
| Declaracion | Si / No aplica | `presento_declaracion` |
| Motivacion | Texto legal | `explicacion_legal` |
| Situacion actual | Proceso, nivel, unidad, ciudad, puesto, grupo, grado, RMU y partida | accion + catalogos |
| Situacion propuesta | Proceso, nivel, unidad, ciudad, puesto, grupo, grado, RMU y partida | accion + catalogos |
| Posesion | Servidor, identificacion, lugar y fecha | empleado + accion |
| Aprobacion | Responsable TH y autoridad nominadora | accion/configuracion |
| Firmas | Elaboracion, revision, registro y control | accion/configuracion |
| Notificacion | Medio, fecha, hora, documento, responsable y puesto | accion |

## Estudio de Seguridad Socioeconomico - 4 paginas

| Pagina | Seccion oficial | Campos |
|---|---|---|
| 1 | Cabecera | codigo, fecha, pagina, vinculacion, cargo, nombre |
| 1 | Informacion del servidor | documento, nacionalidad, residencia, libreta militar, relacion, apellidos, nombres, nacimiento, edad, genero, sangre, estado civil, discapacidad, CONADIS, carrera, etnia y nacionalidad indigena |
| 1 | Domicilio y contacto | calles, numero, parroquia, canton, provincia, referencia, telefonos, extension y correos |
| 1 | Contacto de emergencia | nombre, parentesco, telefono convencional y celular |
| 1 | Declaracion de bienes | numero de otorgamiento y fecha de ingreso |
| 2 | Informacion bancaria | institucion, tipo y numero de cuenta |
| 2 | Conyuge | nombres, documento, nacimiento, relacion, instruccion y ocupacion |
| 2 | Hijos | hasta tres filas: nombre, nacimiento, documento, edad, instruccion y ocupacion |
| 2 | Informacion academica | nivel, institucion, periodo, area, egresado y titulo |
| 2 | Capacitacion 1 | evento, tipo, auspiciante, certificado, emisor y fecha |
| 3 | Capacitaciones 2 y 3 | mismos campos de capacitacion |
| 3 | Experiencia laboral | tres ultimos empleos con los once campos del formato |
| 3 | Bienes | vivienda; vehiculo: marca, modelo, placa y valor |
| 3 | Certificacion | nota oficial y espacios de firma del servidor y responsable |
| 4 | Hoja oficial pendiente | cabecera y area reservada, sin inventar campos; reemplazable al recibir la referencia autorizada |

## Modos de salida

- **En blanco:** etiquetas, tablas, casilleros, firmas y metadatos oficiales sin datos personales.
- **Precargado:** todos los datos persistidos y cruzados por ID de registro.
- Ambos modos usan el mismo generador PDF para evitar diferencias entre Biblioteca y los modulos operativos.
