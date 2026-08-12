# Portal Portuario APM — mejoras críticas 2026

## Estado

La migración `database/migracion_critica_2026.sql` fue aplicada en `Talento_Humano` el 28-07-2026. Es idempotente y no elimina información: empleados, unidades y puestos se desactivan mediante estado `1/0`.

## 1. Base de datos y auditoría

Ejecutar, si se instala en otro servidor:

```powershell
sqlcmd -S SERVIDOR -U USUARIO -d Talento_Humano -b -i database\migracion_critica_2026.sql
```

Objetos principales:

- `vw_th_directorio_empleados`, `vw_th_maestros_organizacionales` y `vw_th_movimientos_personal`.
- `sp_th_consultar_directorio`, `sp_th_consultar_historial`, `sp_th_consultar_acciones`, `sp_th_consultar_unidades` y `sp_th_consultar_puestos`: registran usuario, módulo, vista, acción, IP y hora antes de devolver datos.
- `sp_th_guardar_unidad` y `sp_th_guardar_puesto`: alta, edición y baja lógica de Direcciones, Áreas y denominaciones.
- `sp_th_guardar_empleado`, `sp_th_modificar_empleado` y `sp_th_eliminar_empleado`: escritura transaccional con auditoría.
- `sp_th_registrar_accion_personal`: guarda documento y log en una sola transacción.
- `sp_th_mover_empleado`: cambia área/cargo, cierra el período previo, crea historial y bitácora sin Acción de Personal.

Importante: el usuario de aplicación no puede obtenerse de `SUSER_SNAME()` porque toda la aplicación usa una única conexión SQL. Por eso los modelos envían el usuario autenticado a los procedimientos auditados. Las lecturas de la aplicación deben pasar por `sp_th_consultar_*`, no consultar las vistas directamente.

## 2. Configuración privada y login

Las credenciales SQL ya no están en `core/Database.php`. Se cargan desde variables de entorno:

- `PORTAL_DB_SERVER`
- `PORTAL_DB_NAME`
- `PORTAL_DB_USER`
- `PORTAL_DB_PASSWORD`
- `PORTAL_TOKEN_KEY` (opcional: hexadecimal de 64 caracteres o Base64 de 32 bytes)

En esta instalación se creó una configuración local fuera del directorio público, en `.portal-portuario-private/database.php`. No debe copiarse al repositorio ni publicarse. Si no existe `PORTAL_TOKEN_KEY`, `core/Config.php` genera una clave aleatoria de 256 bits en ese directorio privado.

La clave inicial no debe documentarse ni compartirse por el repositorio. El sistema obliga a cambiarla, aplica bloqueo temporal por intentos fallidos y revoca sesiones cuando cambia el estado, la clave o el rol.

El login usa token AES-256-GCM autenticado, IV y nonce aleatorios, vencimiento de ocho horas, vínculo al navegador, regeneración de ID de sesión, cookies `HttpOnly`/`SameSite=Strict` y protección CSRF en escrituras.

## 3. Uso funcional

1. Ingresar por `/PortalPortuario/login`.
2. Abrir **Maestros y denominaciones** en el menú Administración.
3. Para crear una Dirección, dejar “Dirección padre” vacía. Para crear un Área, seleccionar su Dirección padre.
4. Crear denominaciones de puestos libremente en el bloque inferior. “Inactivo” realiza una baja lógica.
5. En el Directorio, usar el botón de flechas para mover a una persona sin generar Acción de Personal.
6. El buscador acepta varios términos en cualquier orden, mayúsculas/minúsculas, acentos, signos, espacios y cédulas parciales o completas.
7. En el Dashboard, hacer clic en la tarjeta **Activos** para abrir el Directorio filtrado.
8. Consultar **Logs de Actividad** para ver la bitácora real de SQL Server.

## 4. Encabezado fijo

Las reglas están centralizadas en `public/css/layout.css`: `.topbar` usa `position: fixed`, `top: 0`, desplazamiento según el sidebar y `z-index`; `.main` reserva el espacio superior para que el contenido no quede oculto. También incluye ajustes para sidebar colapsado y pantallas móviles.

## 5. Verificación

Ya se comprobó:

- sintaxis PHP de todos los archivos modificados;
- login real, rol, token cifrado y token CSRF;
- creación de unidades y puestos dentro de una transacción revertida;
- Acción de Personal y su log dentro de una transacción revertida;
- movimiento interno, historial y log dentro de una transacción revertida;
- reparación del registro afectado: `PALMA TEJENA / MICHAEL JAVIER`;
- ausencia de datos de prueba después de cada `ROLLBACK`.
