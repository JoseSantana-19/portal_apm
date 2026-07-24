# Diseño: Integración Talento Humano (PortalPortuario) → portal_apm

**Fecha:** 2026-07-05
**Estado:** Aprobado (arquitectura + defaults)

## 1. Objetivo

Integrar el sistema de RRHH del proyecto externo `PortalPortuario` (BD `Talento_Humano`, tablas `th_*`) como módulo nativo de `portal_apm`, **reemplazando** el módulo TH básico actual (`/th`: Empleados/Contratos), sin datos duplicados ni incongruencias.

## 2. Decisiones (confirmadas con el usuario)

| Tema | Decisión |
|---|---|
| Ejecución | BD `Talento_Humano` restaurada en `.\VICTUS`, accesible → se construye contra el **esquema real** |
| Maestro de empleados | `th_empleados` (dedup por `identificacion`, ya UNIQUE) |
| Módulo TH básico | **Reemplazar** por el nuevo |
| Alcance | Los 6 submódulos TH (incluidos los mock) |
| RBU | En `PORTAL_APM.CORE_Config` (modulo='TH', clave='RBU_VIGENTE') |
| Unidades duplicadas | Limpiar (DIR-PLAN, DIR-TICS, DIR-JUR repetidas) |
| RBAC muerto del origen | Dropear `th_usuarios_sistema/th_roles/th_modulos/th_permisos_rol` |

## 3. Realidad de la BD (verificada por introspección)

Tablas reales en `Talento_Humano`: `th_empleados`(2), `th_puestos`(5), `th_unidades_organizacionales`(19, con 3 duplicadas), `th_acciones_personal`(0), `th_acciones_personal_old`(1), `th_titulos`(0), + RBAC muerto.
Vistas reales: `view_th_iddatosempledo`, `view_th_empleados`, `view_th_cedempleados`, `view_th_idempleados`, `view_th_ceddatosempledo`.
SPs reales: `sp_th_guardar_empleado`(14 params), `sp_th_modificar_empleado`(15), `sp_th_eliminar_empleado`, `sp_th_obtener_siguiente_id` (roto: usa `th_secuenciales` inexistente).

**Drift del código fuente (llama objetos inexistentes):** `th_parametros`, `th_historial_laboral`, `th_secuenciales`, `vw_th_reporte_historial_jerarquico`, `sp_th_guardar_accion_personal`; el modelo manda 29 params a un SP de 14; `AccionPersonalModel` inserta `usuario_crea` (no existe) y hace join por `e.cedula` (la columna real es `identificacion`).

### th_empleados (columnas reales)
empleado_id(PK), tipo_identificacion, identificacion(UQ), nombres, apellidos, fecha_nacimiento, sexo, estado_civil, nacionalidad, unidad_id, puesto_id, fecha_ingreso, sueldo_rmu, correo_institucional, correo_personal, telefono_movil, ciudad_residencia, direccion_domiciliaria, cuenta_bancaria, codigo_iess, estado(int), fecha_creacion, cargas_familiares, tipo_cuenta_bancaria, numero_cuenta_bancaria, institucion_bancaria.

### th_acciones_personal (columnas reales)
accion_id(PK), numero_accion, fecha_elaboracion, empleado_id, tipo_accion, fecha_rige_desde, fecha_rige_hasta, explicacion_legal, actual_unidad_id, actual_puesto_id, actual_lugar_trabajo, actual_remuneracion, actual_partida_presupuestaria, propuesta_unidad_id, propuesta_puesto_id, propuesta_lugar_trabajo, propuesta_remuneracion, propuesta_partida_presupuestaria, estado_documento. **(No hay usuario_crea.)**

## 4. Arquitectura

- **BD separada** `Talento_Humano` vía singleton `ThHrDatabase` (clon de `InvDatabase`, sqlsrv nativo, sin PDO, `?` posicional). Constante `DB_TH_NAME` en `config/app.php`.
- Módulo = **MOIS módulo 11** ("Talento Humano", `fa-users`, `#e83e8c`). Prefijo de rutas `/th`.
- Autocontenido en `Talento_Humano`. Cruces a `PORTAL_APM`: (a) menú/permisos MOIS, (b) `CORE_Config` (RBU), (c) `CORE_Auditoria` (auditoría) — vía `Database::getInstance()`.
- Login = `CORE_Usuarios` (portal). Módulo = herramienta admin sobre `th_empleados`.
- **Descarte del origen:** auth (`th_usuarios_sistema`), RBAC (`th_roles/th_modulos/th_permisos_rol`), módulos `admin` y `auditoria` (mock). Portal ya tiene `/admin/*` y `CORE_Auditoria`.

## 5. Estructura de archivos

```
modules/Talento_Humano/            (reconstruido; básico retirado)
  controllers/  ThDirectorioController · ThAccionPersonalController ·
                ThAsistenciaController · ThVacacionesController ·
                ThDesempenoController · ThCapacitacionController
  models/       ThHrDatabase · ThHrBaseModel · ThEmpleadoModel · ThAccionPersonalModel
  views/        inicio · directorio · formulario · perfil · historial ·
                accion_personal · asistencia · vacaciones · desempeno · capacitacion
libs/fpdf/                         (fichas PDF)
public/img/empleados/              (fotos)
db/th_integration.sql              (migración PORTAL_APM: menú + RBU)
db/th_hr_schema_fix.sql            (migración Talento_Humano)
```
Clases con prefijo `Th…` para no chocar con el autoloader por nombre de clase.

## 6. Submódulos

| Submódulo | Datos | Estado |
|---|---|---|
| Directorio + ficha + PDF | th_empleados | Funcional (CRUD reescrito a esquema real) |
| Acción de Personal + PDF LOSEP | th_acciones_personal | Funcional (SQL reconciliado) |
| Historial / Reportes | th_historial_laboral (nueva) | Funcional, sin datos seed |
| Asistencia/Vacaciones/Desempeño/Capacitación | mock | UI reskin, marcado "demo/fase siguiente" |

## 7. Reconciliaciones código → esquema real

1. **ThEmpleadoModel**: `insertar/modificar` → INSERT/UPDATE directo parametrizado (sqlsrv `?`) sobre las 26 columnas reales (no el SP parcial). `listarDirectorio` → `view_th_iddatosempledo`. `obtenerRbuVigente` → lee `CORE_Config`. `obtenerReporteFiltrado` → `vw_th_reporte_historial_jerarquico` (creada).
2. **ThAccionPersonalModel**: quitar `usuario_crea` del INSERT; `obtenerAccionCruzada` join `e.identificacion`; `generarSiguienteSecuencial` sqlsrv. Guardar partidas presupuestarias (opcional).
3. Todo PDO → sqlsrv posicional; quitar `Conexion::registrarErrorLog`.

## 8. Migraciones SQL (idempotentes)

**Talento_Humano** (`db/th_hr_schema_fix.sql`):
- Consolidar unidades duplicadas (repuntar FKs de empleados a la fila superviviente, borrar repetidas).
- `DROP` de `th_usuarios_sistema, th_permisos_rol, th_roles, th_modulos` (orden por FKs).
- `ALTER th_unidades_organizacionales ADD sucedido_por_id INT NULL` (+FK) para el reporte jerárquico.
- `CREATE TABLE th_historial_laboral` (historial_id, empleado_id, puesto_id, unidad_id, fecha_desde, fecha_hasta) + FKs.
- `CREATE VIEW vw_th_reporte_historial_jerarquico` (adaptada al esquema real).

**PORTAL_APM** (`db/th_integration.sql`):
- `CORE_Config`: upsert ('TH','RBU_VIGENTE','460.00','string').
- Menú módulo 11: capturar roles con acceso previo + su MAX(nivel_crud); borrar nodos/permisos módulo 11 con opcion>=1; insertar nueva estructura L1/L2/L3; re-otorgar permisos a esos roles con su nivel.
- Tablas `TH_Empleados/Contratos/Adendas/Novedades` de PORTAL_APM: **se conservan** (sin borrar), solo salen del menú/rutas.

### Menú nuevo (módulo 11)
```
L1 (11,0,0,0) Talento Humano
 L2 (11,1,0,0) Personal
   L3 (11,1,1,0) Directorio de Personal   /th/directorio
   L3 (11,1,2,0) Acción de Personal        /th/accion-personal
   L3 (11,1,3,0) Historial / Reportes      /th/reporte
 L2 (11,2,0,0) Gestión Operativa
   L3 (11,2,1,0) Asistencia                /th/asistencia
   L3 (11,2,2,0) Vacaciones                /th/vacaciones
   L3 (11,2,3,0) Desempeño                 /th/desempeno
   L3 (11,2,4,0) Capacitación             /th/capacitacion
```

## 9. Rutas (`routes.php`, reemplazan el bloque `/th` actual)
```
GET  /th                         ThDirectorioController@index
GET  /th/directorio              ThDirectorioController@directorio
GET  /th/empleado/nuevo          ThDirectorioController@crear
GET  /th/empleado/{id}/editar    ThDirectorioController@editar
POST /th/empleado/guardar        ThDirectorioController@guardar
POST /th/empleado/eliminar       ThDirectorioController@eliminar
GET  /th/empleado/perfil/{cedula} ThDirectorioController@perfil
GET  /th/empleado/ficha          ThDirectorioController@imprimirFicha
GET  /th/reporte                 ThDirectorioController@reporte
GET  /th/accion-personal         ThAccionPersonalController@index
POST /th/accion-personal/guardar ThAccionPersonalController@guardar
GET  /th/accion-personal/ver     ThAccionPersonalController@ver
GET  /th/accion-personal/imprimir ThAccionPersonalController@imprimirAccion
GET  /th/accion-personal/buscar-servidor   ThAccionPersonalController@buscarServidor
GET  /th/accion-personal/buscar-por-cedula ThAccionPersonalController@buscarPorCedula
GET  /th/asistencia              ThAsistenciaController@index
GET  /th/vacaciones              ThVacacionesController@index
GET  /th/desempeno               ThDesempenoController@index
GET  /th/capacitacion            ThCapacitacionController@index
```

## 10. Seguridad + Frontend
- Cada controlador: `requireAuth()`; cada POST: `verifyCsrf()`; forms con campo CSRF del portal. PDF: `requireAuth()` + salida binaria (sin shell).
- Vistas: quitar `<!DOCTYPE>/<head>`/sidebar propio → fragmento SPA; `bi-*`→`fa-*`; colores → tokens `--surface-app/--text-app/--border-app` (temas `body.t1/t2/t3`); links `data-spa`; quitar mock donde el controlador ya trae datos reales.

## 11. Verificación
- `php -l` en cada archivo nuevo.
- Introspección post-migración (nodos módulo 11, RBU, tablas droppeadas, unidades sin duplicados, th_historial_laboral + vista).
- Smoke test vía `php -S`: login admin → módulo 11 → cada pantalla carga por SPA; alta de empleado no duplica `identificacion`; PDFs se generan; permisos MOIS respetados.

## 12. Fuera de alcance
Biométrico real de asistencia; flujo con datos de vacaciones/desempeño/capacitación; módulos `admin`/`auditoria` del origen.
