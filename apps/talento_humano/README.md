# Portal Portuario APM

Sistema MVC nativo en PHP y SQL Server para la gestión de Talento Humano de la Autoridad Portuaria de Manta.

## Estado actual

El entorno local de preproducción está operativo con IIS, PHP 8.5 NTS, HTTPS obligatorio y conexión cifrada a SQL Server mediante ODBC Driver 18. El portal incluye:

- nómina de personal con búsqueda compuesta y paginación;
- expedientes, estructura organizacional y cargos;
- Acciones de Personal y movimientos internos individuales o grupales;
- correlativos documentales MP, CA, LI, RD y VAC asignados transaccionalmente;
- Vacaciones productivas derivadas de Acciones de Personal aprobadas;
- períodos de vinculación, reingresos, antigüedad efectiva e hitos de servicio;
- Paz y Salvo auditable para personal saliente, con PDF de dos páginas;
- estudio socioeconómico con persistencia y generación PDF;
- biblioteca de formularios oficiales;
- usuarios, roles, permisos, MFA, auditoría y reportes;
- asignación de cuentas y roles compatible con el puesto institucional;
- perfil operativo de Asistente de Talento Humano con mínimo privilegio;
- constancia documental y entrega SMTP configurable para notificaciones aprobadas;
- respaldos y migraciones reproducibles;
- gate manual de SQL Server, IIS y UAT para runner Windows de preproducción.

Asistencia, Desempeño y Capacitación siguen siendo prototipos y no forman parte del alcance operativo. La página 4 del formato socioeconómico incorpora la ubicación domiciliaria autorizada por el proyecto: coordenadas, referencia, mapa privado y QR auditado.

## Puesta en marcha

1. Consulte el [índice de documentación](docs/README.md).
2. Para operación funcional consulte el [Manual de usuario](docs/MANUAL_USUARIO.md).
3. Para estructura y gobierno de información consulte el [Diccionario de datos](docs/DICCIONARIO_DATOS.md).
4. Prepare el equipo con la guía de [entorno local IIS](docs/ENTORNO_LOCAL_IIS.md).
5. Para reproducir el sistema desde cero consulte [Instalación en un equipo nuevo](docs/INSTALACION_NUEVO_EQUIPO.md).
6. Revise el orden de [migraciones de base de datos](database/README.md).
7. Ejecute las verificaciones:

```powershell
C:\php85-nts\php.exe scripts\preflight.php
C:\php85-nts\php.exe tests\environment_static.php
C:\php85-nts\php.exe tests\run_sql_smoke.php
C:\php85-nts\php.exe tests\security_db_smoke.php
powershell -ExecutionPolicy Bypass -File deployment\run-local-validation-gate.ps1
```

La compuerta local debe ejecutarse en una consola administrativa real porque valida claves privadas de equipo, SQL cifrado e IIS. Una consola aislada de automatización puede carecer de credenciales Schannel aunque el sitio y el pool funcionen correctamente.

Para preparar el servidor definitivo, siga [Despliegue seguro](docs/DESPLIEGUE_PRODUCCION.md).

### Servidor integrado de PHP (solo desarrollo rápido)

Si se requiere una prueba puntual sin IIS, el comando debe ejecutarse desde la raíz del repositorio:

```powershell
Set-Location 'C:\Users\palmi\Downloads\Practicas 2026\PortalPortuario'
C:\php85-nts\php.exe -S localhost:8000 index.php
```

Abra `http://localhost:8000/login`. El front controller permite que el servidor integrado entregue directamente únicamente los recursos existentes de `public/` (CSS, JavaScript e imágenes). Este modo no sustituye el espejo local validado en IIS; la dirección de referencia sigue siendo `https://portal-apm-preprod.local/`.

## Seguridad

Las credenciales, claves de token, sesiones, respaldos, logs y documentos personales deben permanecer fuera de Git y fuera de la raíz pública. La configuración privada se toma desde `PORTAL_PRIVATE_DIR` o variables de entorno; `.env.example` contiene únicamente marcadores.
