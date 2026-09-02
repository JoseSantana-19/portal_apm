# Estado del proyecto

Última actualización documental y de código: **2 de septiembre de 2026**.

## Culminado en el entorno local

- MVC nativo, autenticación, MFA, sesiones seguras, RBAC y CSRF.
- Gestión de usuarios, roles, permisos, auditoría general y por usuario.
- Asignación controlada de roles por puesto, usuario derivado de la cédula y perfil de lectura seguro para puestos sin excepción.
- Directorio, expedientes, maestros organizacionales, cargos y ciclo laboral.
- Acción de Personal con aprobación, auditoría y PDF oficial de dos páginas.
- Separación entre LOSEP y Código del Trabajo, con formulario laboral abreviado y serie `CdgT` parametrizada.
- Series anuales independientes `MP`, `CA`, `LI`, `RD` y `VAC`, reservadas dentro de la transacción SQL al guardar.
- Movimiento interno individual y grupal sin documento legal.
- Vacaciones operativas originadas exclusivamente en Acciones de Personal aprobadas, con detalle desde Inicio.
- Períodos de vinculación y reingreso, antigüedad efectiva sin contar interrupciones e hitos de 5, 10, 15, 20, 25 y 30 años.
- Estadísticas de género en Reportes Generales y exportación XLSX integral.
- Paz y Salvo auditable de cinco áreas, cierre controlado y PDF institucional de dos páginas.
- Custodia versionada del formulario impreso, firmado, escaneado y cargado nuevamente al expediente.
- Estudio socioeconómico persistente, precarga y PDF de cuatro páginas; la página 4 contiene mapa, coordenadas, referencia y QR.
- Biblioteca de formularios, reportes, exportaciones y perfiles.
- Búsqueda global de funcionarios por nombre, identificación, cargo, área y estado en todos los módulos.
- SQL Server con usuario de mínimo privilegio, cifrado forzado y certificado validado.
- IIS con PHP 8.5 NTS, HTTPS, HSTS, CSP y bloqueo de archivos internos.
- Migraciones, trabajos de respaldo y pruebas automatizadas.
- Ledger esperado: veinte migraciones con checksum SHA-256 y verificación contra los archivos versionados. La migración 20 corrige la creación automática del período inicial y canaliza la edición de borradores mediante un procedimiento con permiso `EXECUTE`, sin conceder `UPDATE` directo al usuario del portal. La certificación se realiza con `tests/migration_ledger.php` en una consola con acceso a las claves TLS del equipo.
- Respaldos verificados con checksum: FULL, diferencial y log; cuatro trabajos SQL Agent habilitados y sin último fallo.
- Certificados SQL/Web renovados, enlazados y validados; huellas obsoletas retiradas después de superar la compuerta.
- Prueba real de navegador: HTTPS, logo, seis hojas CSS locales y consola sin errores.
- Dependencias visuales alojadas localmente, sin Google Fonts ni CDN en tiempo de ejecución.
- UAT transaccional de RBAC/MFA/auditoría sin residuos y recorrido funcional autenticado de los flujos laborales principales. La evidencia y los pendientes de limpieza del recorrido del 2 de septiembre constan en `PRUEBAS_Y_ACEPTACION.md`.
- Errores HTTP centralizados con referencia, respuesta JSON segura y log técnico privado.
- Monitor diario limitado al usuario, evidencia JSON de salud y retención de logs con simulación obligatoria.
- CI en PHP 8.4 y 8.5 para sintaxis, seguridad, interfaz, rutas, PDF y controles operativos.
- Workflow manual para SQL Server, IIS y UAT autenticado en un runner Windows de preproducción con la etiqueta `portal-apm-preprod`.
- Manual de usuario y diccionario de datos versionados junto al sistema.
- DataTables local, sin CDN ni jQuery, aplicado únicamente a los listados operativos que requieren búsqueda, ordenamiento o paginación.

## Correo electrónico

La versión actual conserva la **constancia documental de notificación** y añade transporte SMTP real para las Acciones de Personal LOSEP aprobadas. Admite varios destinatarios, valida y almacena las direcciones y mantiene la constancia en el expediente/PDF. El envío se activa exclusivamente con `PORTAL_SMTP_ENABLED=true` y un relay institucional TLS con certificado validado; las credenciales permanecen fuera de Git.

Si el relay no está configurado, la aprobación continúa y el portal informa que solo quedó registrada la constancia. Si el relay rechaza el mensaje, la Acción de Personal no se revierte ni se duplica: la incidencia queda en `storage/logs/smtp` para atención operativa. Para certificar el envío real todavía se requieren host, puerto, remitente y credenciales proporcionados por la institución.

## Estado de certificación del espejo local

- Preflight PHP/TLS/SQL ejecutado en consola Windows real: aprobado, `Fallos: 0`.
- Suite estática, renderizado, PDF, XLSX y JavaScript: 34 comprobaciones aprobadas.
- UAT transaccional de autenticación, MFA, RBAC y auditoría: aprobado y sin residuos.
- Pruebas SQL de fuerza laboral, roles por puesto, vigencias, seguridad, asistente, documentos firmados y geolocalización: aprobadas.
- La compuerta SQL del 2 de septiembre detectó dos funcionarios sin período inicial y un ledger de 19/20 migraciones. La migración correctiva `2026.08.30.1` queda como última migración obligatoria y debe aplicarse desde una consola elevada con respaldo verificado.
- El UAT autenticado del 2 de septiembre aprobó altas LOSEP/Código del Trabajo, redirección y PDF abreviado, fecha y responsable automáticos, aprobación, rechazo, destinatarios múltiples, carga firmada, historial integral, búsqueda/DataTables y recorrido visual claro/oscuro sin errores de consola.
- La edición de un borrador reveló correctamente que `portal_app` no debe tener permiso de tabla. El código ya usa `dbo.sp_th_actualizar_borrador_accion_personal`; su instalación forma parte de la migración pendiente `2026.08.30.1`.
- El recorrido interactivo con el rol Asistente sigue pendiente: existe una cuenta nominativa activa, pero no se alteró su clave ni se utilizaron credenciales no proporcionadas. La matriz RBAC sí permanece cubierta por la prueba transaccional automatizada.
- La entrega SMTP real sigue pendiente de un relay institucional autorizado; con SMTP deshabilitado se verificó que la aprobación conserva la constancia y muestra el estado correcto.
- Preflight aprobado con cero fallos. Diez de trece comprobaciones SQL aprobaron; las tres pendientes (`migration_ledger`, `talent_operation_db_smoke` y `regimen_laboral_db_smoke`) dependen del ledger/checksum y de aplicar la migración 20. La prueba documental transaccional volvió a aprobar después de asegurar el cierre de cursores ODBC.
- El ambiente no puede declararse cerrado hasta aplicar la migración 20, aprobar `tests/migration_ledger.php`, `tests/talent_operation_db_smoke.php`, repetir la edición autenticada y retirar de forma controlada los datos/documentos UAT conservando la auditoría.

## Fuera del alcance actual

- Asistencia y turnos.
- Ausencias como módulo independiente.
- Evaluación y desempeño.
- Capacitación como módulo operativo independiente.

Estos elementos permanecen como prototipos o reservas y no deben considerarse funcionalidad productiva.

## Requisitos externos antes del servidor definitivo

1. Emitir certificados para el DNS real mediante la PKI institucional o una autoridad confiable.
2. Crear secretos, cuenta SQL y configuración privada exclusivos del ambiente.
3. Crear cuentas nominativas para operadores y retirar cuentas compartidas.
4. Ejecutar `database/administracion/configurar_respaldo_externo_alertas.sql` con la ruta UNC, operador, perfil Database Mail y certificado de cifrado definitivos.
5. Ejecutar migraciones, preflight, pruebas SQL, pruebas estáticas, validación de PDF y restauración de respaldo.
6. Realizar aceptación funcional con Talento Humano y Seguridad de la Información.

El proyecto está preparado como espejo local; esto no equivale a autorizar su publicación en producción sin los controles externos anteriores.
