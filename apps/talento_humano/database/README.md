# Migraciones de base de datos

Para una base `Talento_Humano` ya existente, el cierre funcional vigente requiere exactamente estas 20 migraciones:

1. `migracion_critica_2026.sql`
2. `migracion_formatos_oficiales_2026.sql`
3. `migracion_culminacion_critica_2026.sql`
4. `migracion_mejoras_operativas_2026.sql`
5. `migracion_calidad_busqueda_2026.sql`
6. `patch_reconciliacion_rolmaes_2026.sql`
7. `migracion_ciclo_laboral_2026.sql`
8. `migracion_cierre_produccion_20260806.sql`
9. `migracion_seguridad_auditoria_20260810.sql`
   - MFA TOTP cifrado, resumen de auditoría por usuario, optimización de bitácora y privilegios mínimos para cuentas.
   - Encapsula el alta de usuarios en `dbo.sp_th_crear_usuario_sistema` y retira `INSERT`/`UPDATE` generales heredados.
10. `migracion_integridad_rbac_20260813.sql`
    - Normaliza códigos de módulo únicos, consolida Estructura y cargos e invalida sesiones afectadas.
11. `migracion_gestion_laboral_20260820.sql`
    - Amplía el expediente y su historial con proceso, nivel, lugar, grupo, grado, partida, jornada y horas.
    - Incorpora condición `Sustituto`, jornadas temporales aprobadas mediante Acción de Personal y movimientos internos que conservan el cargo.
    - Agrega borradores de formularios cifrados con AES-256-GCM, consultas detalladas y procedimientos v2 transaccionales.
    - Sustituye las vistas consolidadas del directorio e historial para que reportes, perfil y formularios lean el mismo origen.
12. `migracion_vigencias_temporales_20260820.sql`
    - Distingue explícitamente acciones permanentes y temporales; una temporal exige fecha final y nunca reemplaza la situación base.
    - Incorpora la vista de situación efectiva, restitución automática por fecha, jornadas de lactancia, maternidad y sustitución, y alertas operativas.
    - Instala el trabajo `APM - Vigencias laborales`, que actualiza estados y deja trazabilidad periódica sin que el retorno dependa de una sesión abierta.
13. `migracion_licencias_parentales_20260820.sql`
    - Separa la jornada base contractual de las excepciones temporales en la captura y validación.
    - Registra maternidad y paternidad como licencias parentales de cero horas, conservando intacta la jornada base para el retorno automático.
14. `migracion_operacion_talento_20260825.sql`
    - Asigna correlativos anuales independientes MP, CA, LI, RD y VAC sin renumerar documentos históricos.
    - Registra períodos de vinculación para reingresos, antigüedad efectiva e hitos de 5 a 30 años.
    - Convierte Vacaciones en un módulo productivo derivado de acciones aprobadas e incorpora estadísticas de género.
    - Instala el flujo auditable de Paz y Salvo, sus cinco certificaciones, RBAC y estados de cierre.
15. `migracion_geolocalizacion_socioeconomica_20260825.sql`
    - Completa la página 4 del estudio socioeconómico con coordenadas validadas, enlace cartográfico, indicaciones, mapa privado y código QR.
    - Conserva la referencia domiciliaria oficial de la página 1 y evita almacenar imágenes Base64 dentro de SQL Server.
16. `migracion_roles_por_puesto_20260826.sql`
    - Vincula puestos institucionales con los roles RBAC autorizados y deriva el usuario desde la cédula del funcionario.
    - Aplica `Funcionario (Lectura)` como perfil seguro para puestos sin excepción, restringe Dirección y Analistas de Talento Humano a sus roles correspondientes y valida el alta en SQL Server.
17. `migracion_expediente_documental_historial_20260827.sql`
    - Incorpora un repositorio lógico, privado y versionado para el PDF completo que se imprime, firma, escanea y vuelve a cargar, sin alterar ningún formato autorizado.
    - Valida origen y funcionario, conserva SHA-256, tamaño, usuario, IP y fecha de cada versión, y marca como vigente únicamente el último documento firmado.
    - Unifica en el historial laboral las Acciones de Personal, vacaciones, jornadas temporales, movimientos internos, estudios socioeconómicos, Paz y Salvo y documentos legalizados.
18. `migracion_rol_asistente_talento_20260827.sql`
    - Incorpora el rol operativo `Asistente de Talento Humano` y lo asocia a las denominaciones institucionales existentes, incluida la variante histórica `ASITENTE DE TTHH`.
    - Limita el menú a Principal, Gestión de Talento Humano, Biblioteca, Estructura y cargos y Reportes Generales; excluye usuarios, roles, políticas, auditoría administrativa y prototipos.
    - Conserva control total para `Super Administrador` y habilita los permisos contextuales necesarios para completar expedientes y custodiar documentos firmados.
19. `migracion_regimen_laboral_20260829.sql`
    - Separa el régimen `LOSEP` de `CODIGO_TRABAJO` en el expediente, la situación laboral efectiva, el historial y cada documento laboral.
    - Restringe los contratos por régimen y asigna `Contrato Indefinido` al personal sujeto al Código del Trabajo.
    - Parametriza y asigna transaccionalmente la serie anual `CdgT-###-AAAA` para el Formulario Laboral Abreviado, sin mezclarla con los correlativos LOSEP.
20. `migracion_integridad_periodos_20260830.sql`
    - Reconcilia expedientes creados después de la migración operativa que todavía no poseen período de vinculación.
    - Crea el trigger idempotente `tr_th_empleados_crear_periodo_inicial` para garantizar el período inicial en cada nueva alta.
    - Instala `sp_th_actualizar_borrador_accion_personal` y concede solo `EXECUTE` al rol de la aplicación para editar borradores sin permiso directo sobre la tabla.
    - No modifica períodos existentes ni altera el cálculo de reingresos.

Todos deben ejecutarse con `sqlcmd -b`, respaldo previo y una cuenta DBA. Las migraciones 17 a 20 son idempotentes para facilitar una verificación controlada: incorporan, respectivamente, el expediente documental, el rol Asistente de Talento Humano, el régimen laboral con serie `CdgT` y la integridad de períodos de vinculación. Las capacidades anteriores permanecen definidas por sus migraciones originales; ninguna migración aislada sustituye la ejecución ordenada de las 20.

Después de la cuarta migración, ejecutar una sola vez `php scripts/sincronizar_nacionalidades.php` con una conexión DBA temporal. El script carga el catálogo ISO/gentilicios; la aplicación continúa operando con la cuenta restringida `portal_app`.

La migración operativa agrega búsqueda compuesta y sin acentos, nacionalidades normalizadas, expediente completo para impresión y movimientos internos grupales atómicos con auditoría.

La quinta migración consolida unidades equivalentes sin borrar el histórico, crea respaldos lógicos, evita nombres activos duplicados y amplía la vista del expediente para precargar el socioeconómico. El sexto script instala un procedimiento de mínimo privilegio para conciliar el CSV legado. Después puede ejecutarse:

`php scripts/reconciliar_rolmaes.php --source="ruta/rolmaes.DBF.csv" --apply`

El modo sin `--apply` es siempre una simulación. La conciliación no renumera `empleado_id`; la numeración consecutiva visible se calcula por separado para no romper las claves foráneas de acciones, estudios, usuarios, movimientos e historial.

El correlativo definitivo de Acción de Personal se asigna en SQL Server al guardar, dentro de la misma transacción y con bloqueo de concurrencia. El número mostrado inicialmente en el formulario es solamente una vista previa.

Las cuatro migraciones históricas sustituidas fueron retiradas del árbol actual para impedir su ejecución accidental. Permanecen recuperables desde el historial de Git. La importación inicial se conserva únicamente como referencia en `database/archive/legacy_import_rolmaes/`; el CSV con datos personales fue eliminado del proyecto.

La migración de cierre crea `th_schema_migrations`, unifica los tipos de proceso, limita a tres las filas autorizadas de hijos, capacitaciones y experiencias, protege un único estudio vigente por funcionario, agrega el índice de paginación de auditoría y completa los permisos mínimos de administración de roles.

La novena migración incorpora segundo factor TOTP cifrado, protección contra reutilización de códigos, un índice de auditoría por usuario y la vista consolidada `vw_th_resumen_auditoria_usuarios`. La décima garantiza códigos RBAC únicos y elimina la ambigüedad histórica del permiso `maestros`. La undécima cierra la gestión laboral integral y debe aplicarse antes de probar los nuevos formularios, borradores y exportaciones. La duodécima (`migracion_vigencias_temporales_20260820.sql`) incorpora modalidad permanente/temporal explícita, situación laboral efectiva por fecha, restitución automática y el trabajo de SQL Agent para seguimiento de vigencias. La decimotercera amplía este flujo con licencia por paternidad y la regla común de cero horas para licencias parentales. La decimoséptima no modifica el contenido ni la composición de los PDF oficiales: agrega exclusivamente la custodia del escaneo firmado y la trazabilidad integral del expediente.

Los SQL no son cargados por PHP durante la ejecución del portal. Deben permanecer en el repositorio privado para recuperación, auditoría y creación de nuevos ambientes, pero pueden excluirse del artefacto publicado en el servidor web.

No deben eliminarse del repositorio por el hecho de que los objetos ya existan en la base: constituyen la fuente reproducible y auditable del esquema. Los scripts históricos sustituidos pertenecen a `archive/`; los vigentes permanecen en la raíz de `database/` y se ejecutan una sola vez según `th_schema_migrations`.

## Administración

`administracion/configurar_respaldos.sql` crea o actualiza los trabajos de respaldo completo, diferencial, log e integridad. No es una migración de la aplicación y debe ejecutarse en `msdb` con una cuenta DBA. El servicio `SQLSERVERAGENT` debe permanecer en inicio automático para que los horarios se cumplan.

`administracion/verificar_respaldos.sql` es de solo lectura: informa el modelo de recuperación, estado y última ejecución de los trabajos, y los respaldos recientes con su indicador de checksum. Debe formar parte de la revisión operativa semanal.

`administracion/reconciliar_historial_migraciones.sql` registra las diez migraciones históricas que fueron aplicadas antes de existir `th_schema_migrations`. No vuelve a ejecutar cambios: primero exige las firmas de objetos distintivos y valida el SHA-256 de cada archivo vigente. Las migraciones posteriores se agregan normalmente al ledger y la compuerta administrativa exige exactamente las 20 versiones esperadas.

En el espejo local, `deployment/close-local-database.php` centraliza el cierre administrativo. Recibe `PORTAL_DBA_USER` y `PORTAL_DBA_PASSWORD` únicamente como variables temporales, exige conexión cifrada y `sysadmin`, valida base, trabajos y respaldos, y solo concilia el ledger cuando se añade `--reconcile`. No contiene ni persiste credenciales.

Para aplicar una migración nueva en el espejo Windows se utiliza una consola elevada y autenticación integrada, sin escribir credenciales en archivos ni en la línea de comandos:

`powershell -ExecutionPolicy Bypass -File deployment\apply-local-migration.ps1`

El script exige `sysadmin`, cifra la conexión, genera un respaldo `COPY_ONLY` con checksum en la carpeta oficial de SQL Server, ejecuta con aborto ante error, registra el SHA-256 de cada migración vigente y verifica los objetos distintivos antes de finalizar. La transcripción queda fuera del repositorio en `C:\ProgramData\PortalAPM`.

Cuando la consola no puede elevarse pero dispone de una cuenta DBA temporal, `deployment/apply-migration-pdo.php` ofrece la misma compuerta esencial mediante PDO_SQLSRV: exige TLS validado, comprueba `sysadmin`, crea y verifica el respaldo, procesa los lotes `GO`, registra checksums y valida los objetos. Las credenciales se suministran solo como variables de proceso `PORTAL_DB_USER` y `PORTAL_DB_PASSWORD`; nunca se escriben en el repositorio.
