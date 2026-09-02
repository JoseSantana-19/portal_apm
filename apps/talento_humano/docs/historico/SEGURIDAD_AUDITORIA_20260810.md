# Seguridad, doble autenticación y auditoría — 10/08/2026

> Documento histórico. Para la guía vigente consulte [Seguridad y auditoría](../SEGURIDAD_Y_AUDITORIA.md).

## Resultado implementado

- Login renovado con identidad visual APM, controles accesibles y contraseña visible/oculta.
- Segundo factor TOTP compatible con Microsoft Authenticator, Google Authenticator y aplicaciones equivalentes.
- Secreto TOTP cifrado con AES-256-GCM; nunca se almacena en texto plano.
- Bloqueo por cinco códigos MFA inválidos y rechazo de códigos reutilizados.
- Aviso de inactividad durante el último minuto de una sesión de 30 minutos.
- Renovación explícita o cierre automático, ambos registrados en auditoría.
- Reporte general de auditoría y filtro individual por usuario, período y exportación CSV.
- Recuperación administrativa de MFA que invalida las sesiones del usuario afectado.
- Vista SQL de resumen por usuario e índice optimizado por usuario/fecha.
- Alta de cuentas encapsulada en `dbo.sp_th_crear_usuario_sistema`.
- El rol `portal_app_role` ya no posee `INSERT` general ni `UPDATE` general sobre cuentas; solo ejecuta el procedimiento de alta y actualiza las columnas necesarias.

## Rutas funcionales

| Función | Ruta |
|---|---|
| Acceso | `/login` |
| Verificación del segundo factor | `/login/verificar` |
| Seguridad de la cuenta | `/cuenta/seguridad` |
| Bitácora completa | `/auditoria/logs` |
| Reporte general o por usuario | `/auditoria/reportes` |
| Reporte institucional general | `/reportes` |

## Activación de doble autenticación

La migración deja MFA desactivado inicialmente para no bloquear cuentas existentes sin que el titular haya vinculado su dispositivo.

1. Iniciar sesión normalmente.
2. Presionar el nombre del usuario en la barra superior o el pie del menú.
3. En **Seguridad de la cuenta**, seleccionar **Configurar doble autenticación**.
4. Registrar la clave mostrada en una aplicación Authenticator.
5. Escribir el código de seis dígitos y confirmar.
6. Cerrar sesión y verificar el nuevo flujo usuario + contraseña + código TOTP.

Si se pierde el dispositivo, un administrador puede usar **Gestión de Usuarios → Restablecer 2FA**. Esta operación queda auditada e invalida las sesiones existentes.

## Base de datos y recuperación

- Migración: `database/migracion_seguridad_auditoria_20260810.sql`.
- Versión registrada: `2026.08.10` en `dbo.th_schema_migrations`.
- Respaldo previo verificado: `Talento_Humano_pre_seguridad_20260810.bak` en la carpeta de respaldos de la instancia SQL Server.
- La migración es transaccional e idempotente.

## Validaciones ejecutadas

- Sintaxis de 75 archivos PHP.
- Regresiones de rutas, seguridad, operación, formularios, directorio y JavaScript.
- Algoritmo Base32/TOTP y código de seis dígitos.
- Conexión restringida como `portal_app`, sin privilegios `sysadmin`.
- Procedimiento de alta mediante prueba transaccional con `ROLLBACK`.
- Denegación de inserción directa y de actualización de columnas no autorizadas.
- Consultas reales de resumen general y por usuario en SQL Server.
- Inspección visual del login en 1280×720 y 390×844, sin desbordamiento horizontal ni errores de consola.
