# Migraciones de base de datos

Para una base `Talento_Humano` ya existente, el cierre funcional de julio de 2026 requiere:

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

Todos deben ejecutarse con `sqlcmd -b`, respaldo previo y una cuenta DBA. La última migración es idempotente y crea RBAC, seguridad de cuentas, repositorio documental, flujo de aprobación, protección de auditoría, respaldo lógico y rol SQL de aplicación.

Después de la cuarta migración, ejecutar una sola vez `php scripts/sincronizar_nacionalidades.php` con una conexión DBA temporal. El script carga el catálogo ISO/gentilicios; la aplicación continúa operando con la cuenta restringida `portal_app`.

La migración operativa agrega búsqueda compuesta y sin acentos, nacionalidades normalizadas, expediente completo para impresión y movimientos internos grupales atómicos con auditoría.

La quinta migración consolida unidades equivalentes sin borrar el histórico, crea respaldos lógicos, evita nombres activos duplicados y amplía la vista del expediente para precargar el socioeconómico. El sexto script instala un procedimiento de mínimo privilegio para conciliar el CSV legado. Después puede ejecutarse:

`php scripts/reconciliar_rolmaes.php --source="ruta/rolmaes.DBF.csv" --apply`

El modo sin `--apply` es siempre una simulación. La conciliación no renumera `empleado_id`; la numeración consecutiva visible se calcula por separado para no romper las claves foráneas de acciones, estudios, usuarios, movimientos e historial.

El correlativo definitivo de Acción de Personal se asigna en SQL Server al guardar, dentro de la misma transacción y con bloqueo de concurrencia. El número mostrado inicialmente en el formulario es solamente una vista previa.

Las cuatro migraciones históricas sustituidas fueron retiradas del árbol actual para impedir su ejecución accidental. Permanecen recuperables desde el historial de Git. La importación inicial se conserva únicamente como referencia en `database/archive/legacy_import_rolmaes/`; el CSV con datos personales fue eliminado del proyecto.

La migración de cierre crea `th_schema_migrations`, unifica los tipos de proceso, limita a tres las filas autorizadas de hijos, capacitaciones y experiencias, protege un único estudio vigente por funcionario, agrega el índice de paginación de auditoría y completa los permisos mínimos de administración de roles.

La novena migración incorpora segundo factor TOTP cifrado, protección contra reutilización de códigos, un índice de auditoría por usuario y la vista consolidada `vw_th_resumen_auditoria_usuarios`.

Los SQL no son cargados por PHP durante la ejecución del portal. Deben permanecer en el repositorio privado para recuperación, auditoría y creación de nuevos ambientes, pero pueden excluirse del artefacto publicado en el servidor web.

## Administración

`administracion/configurar_respaldos.sql` crea o actualiza los trabajos de respaldo completo, diferencial, log e integridad. No es una migración de la aplicación y debe ejecutarse en `msdb` con una cuenta DBA. El servicio `SQLSERVERAGENT` debe permanecer en inicio automático para que los horarios se cumplan.
