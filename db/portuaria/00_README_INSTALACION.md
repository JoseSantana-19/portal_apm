# Base de datos del módulo Portuaria (integrado de portuaria_demoV4)

El módulo usa **dos** bases de datos propias en la misma instancia del portal
(`DB_SERVER` de `config/app.php`, por defecto `.\VICTUS`):

| BD | Contenido | Constante |
|---|---|---|
| `PortuariaDemo` | Operativa: bit_visitas, bit_rondas*, bit_camaras/CCTV, catálogos, reporte supervisor | `DB_PORTUARIA_NAME` |
| `PortuariaExterna` | Maestros externos APM (bit_personas de referencia) | `DB_PORTUARIA_EXT_NAME` |

> El código las **auto-crea vacías** si no existen (PortDatabase / conexion/*.php),
> pero el esquema/datos hay que cargarlos con estos scripts.

## Instalación desde cero (recomendada)

**Opción A — Dump completo:** ejecutar `99_DUMP_COMPLETO_PortuariaDemo.sql`
(generado de la BD real el 11-jul-2026; incluye esquema + datos). Revisar las
rutas de los archivos .mdf/.ldf del `CREATE DATABASE` inicial si la instancia
no es `MSSQL16.VICTUS`. Después ejecutar `01_DATABASE_BASE.sql` para crear y
sembrar `PortuariaExterna`.

**Opción B — Scripts numerados:** ejecutar en orden `01` → `24` (omitir `17`,
no existe). Notas:

- ⚠️ **`02_DATABASE_ESTRUCTURA.sql` es DESTRUCTIVO**: hace `DROP TABLE` de
  todas las bit_* antes de recrearlas. Solo para instalación limpia.
- `03` triggers, `04` auth demo (bit_usuarios_apm — **no se usa en el portal**,
  el login es central), `05` datos maestros, `06`-`23` CCTV/cámaras/estados,
  `24` género en personas, `FIX_TRIGGER_RONDAS.sql` al final si aplica.
- `README_EJECUCION_FINAL_SQL.md` es el orden original del proyecto origen.

## Autenticación

El login propio del origen (bit_usuarios_apm + apm_aplicar_passwords_demo)
**quedó reemplazado** por el login central del portal (CORE_Usuarios +
sp_Login). Para que otros módulos logoneen contra el portal, ver
`db/sso_module_login.sql` (SPs `sp_SSO_*`, tabla `CORE_Aplicaciones`, rol
`rol_sso_modulos`) y el cliente `libs/SsoClient.php` / endpoints `/api/sso/*`.

## Menú / permisos del portal

`db/portuaria_menu_integration.sql` registra el módulo MOIS 13 "Bitácoras
Portuarias" en `CORE_Menu_Nodos` y otorga permisos al rol ADMIN (idempotente).

## Runner

Para ejecutar un .sql por lotes (split por `GO`) sin SSMS:
`php db/run_sql.php <archivo.sql>` (ver también `db/run_migration.php`).
