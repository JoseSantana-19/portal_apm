# Portal Portuario APM

Sistema MVC nativo en PHP y SQL Server para la gestion de Talento Humano de la Autoridad Portuaria de Manta.

## Estado

La aplicacion incluye directorio con busqueda compuesta, catalogos de nacionalidades y maestros organizacionales, movimientos internos individuales y grupales, Acciones de Personal, estudios socioeconomicos, formularios PDF oficiales, auditoria y control de acceso por roles.

El buscador del directorio y los selectores de Acción de Personal y estudio socioeconómico filtran en memoria por cédula, nombres o apellidos para responder mientras se escribe. Los identificadores técnicos permanecen estables; el número de registro mostrado y exportado es consecutivo e independiente de las claves foráneas.

El Directorio incluye paginación, acciones según permisos y movimientos grupales contextuales. El ciclo laboral se sincroniza mediante `database/migracion_ciclo_laboral_2026.sql`: las bajas, cesaciones y reingresos actualizan estado, fecha efectiva, historial y auditoría en una sola transacción.

Los modulos de Asistencia, Vacaciones, Desempeno y Capacitacion se conservan temporalmente como prototipos y no deben usarse como fuentes oficiales de informacion.

## Instalacion

Consulte [docs/DESPLIEGUE_PRODUCCION.md](docs/DESPLIEGUE_PRODUCCION.md) para la configuracion del servidor, SQL Server, secretos, permisos, migraciones y verificaciones posteriores al despliegue.

Nunca almacene credenciales, claves de token, sesiones, respaldos, logs ni documentos personales en Git.
