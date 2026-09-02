# Seguridad y auditoría

## Controles implementados

- Login con política de contraseñas, bloqueo temporal e invalidación de sesiones.
- Segundo factor TOTP compatible con aplicaciones Authenticator.
- Secreto TOTP cifrado mediante AES-256-GCM y rechazo de códigos reutilizados.
- Token de sesión autenticado, regeneración de ID, CSRF y cookies `Secure`, `HttpOnly` y `SameSite=Strict`.
- Aviso de inactividad y cierre automático auditado.
- RBAC para lectura, creación, edición, eliminación y administración.
- Auditoría de consultas, escrituras, exportaciones, impresiones, sesiones y cambios administrativos.
- Reporte general y reporte filtrado por usuario y período.
- Cuenta SQL `portal_app` asociada al rol de aplicación y sin privilegio `sysadmin`.
- Altas y operaciones sensibles encapsuladas en procedimientos almacenados.

## Rutas funcionales

| Función | Ruta |
|---|---|
| Acceso | `/login` |
| Verificación MFA | `/login/verificar` |
| Seguridad de la cuenta | `/cuenta/seguridad` |
| Bitácora completa | `/auditoria/logs` |
| Reporte general o por usuario | `/auditoria/reportes` |
| Reporte institucional | `/reportes` |

## Activación de MFA

1. Iniciar sesión.
2. Abrir **Seguridad de la cuenta** desde el perfil.
3. Seleccionar **Configurar doble autenticación**.
4. Registrar la clave en una aplicación Authenticator.
5. Confirmar con el código de seis dígitos.
6. Cerrar sesión y verificar el flujo completo.

Si se pierde el dispositivo, un administrador puede usar **Gestión de Usuarios → Restablecer 2FA**. La operación se audita e invalida sesiones existentes.

## Verificación

```powershell
C:\php85-nts\php.exe tests\security_static.php
C:\php85-nts\php.exe tests\security_audit_static.php
C:\php85-nts\php.exe tests\security_db_smoke.php
C:\php85-nts\php.exe tests\db_privilege_test.php
C:\php85-nts\php.exe tests\uat_access_control.php
```

La migración relacionada es `database/migracion_seguridad_auditoria_20260810.sql`. Para antecedentes de su incorporación, consulte el [cierre histórico del 10-08-2026](historico/SEGURIDAD_AUDITORIA_20260810.md).

## UAT técnica de acceso del 13-08-2026

La prueba transaccional confirmó cuatro roles, una cuenta local y quince módulos
con códigos únicos. Recorrió login real, token AES-256-GCM, permisos concedidos y
denegados, CSRF, cambio de clave, activación TOTP, segundo login con MFA, rechazo
del mismo paso TOTP y registro de los eventos de auditoría. El rol y la cuenta UAT
se revirtieron al terminar, por lo que no quedaron identidades de prueba.

La migración `database/migracion_integridad_rbac_20260813.sql` corrigió cinco
registros históricos que compartían el código `maestros`. Periodos y Títulos
mantienen códigos propios; Cargos, Direcciones y Departamentos se consolidaron en
`Estructura y cargos`. El índice único evita que esa ambigüedad vuelva a aparecer.

La creación de cuentas nominales adicionales no se automatiza: cada cuenta debe
vincularse con un responsable autorizado, un rol aprobado y su segundo factor
personal. En el espejo local es válido conservar únicamente la cuenta
administrativa y usar la UAT transaccional para pruebas repetibles.
