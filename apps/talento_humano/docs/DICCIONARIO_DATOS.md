# Diccionario de datos

Sistema: **Portal Portuario APM — Talento Humano**

Motor: **Microsoft SQL Server**

Esquema de aplicación: **`dbo`**
Versión documental: **30 de agosto de 2026**

## 1. Alcance y fuente autoritativa

Este documento describe el modelo lógico utilizado por la aplicación y por las migraciones versionadas en [`database/`](../database/). La definición física instalada debe verificarse siempre contra `sys.tables`, `sys.columns`, `sys.foreign_keys` y el ledger `th_schema_migrations`; este manual no reemplaza el respaldo ni los scripts SQL.

Convenciones:

- Las tablas operativas usan el prefijo `th_`.
- Las vistas de consulta usan `vw_th_`; `view_th_iddatosempledo` es un alias legado compatible.
- Las claves primarias numéricas terminan en `_id` y normalmente son `INT IDENTITY`.
- Las fechas institucionales se almacenan como `DATE`; las marcas técnicas usan `DATETIME2`.
- Los importes usan `DECIMAL`; nunca deben tratarse como texto.
- `estado` puede ser `BIT` en maestros o un dominio textual controlado en flujos documentales.
- Los campos de auditoría conservan usuario, fecha y dirección IP. No deben exponerse en formularios públicos.

## 2. Mapa general de relaciones

```text
th_unidades_organizacionales ─┐
                              ├─< th_empleados >─ th_puestos
th_nacionalidades >─ th_empleado_nacionalidades ─┘
                                      │
                                      ├─< th_historial_laboral
                                      ├─< th_periodos_vinculacion
                                      ├─< th_acciones_personal
                                      │      ├─< th_vigencias_laborales
                                      │      └─< th_jornadas_especiales
                                      ├─< th_movimientos_personal
                                      ├─< th_estudios_socioeconomicos
                                      │      ├─< th_estudio_hijos
                                      │      ├─< th_estudio_capacitaciones
                                      │      └─< th_estudio_experiencias
                                      ├─< th_paz_salvo ─< th_paz_salvo_secciones
                                      ├─< th_reconocimientos_servicio
                                      ├─< th_documentos_firmados
                                      └─0..1 th_usuarios_sistema

th_roles ─< th_permisos_rol >─ th_modulos
th_puestos ─< th_puesto_rol_mapa >─ th_roles
```

## 3. Núcleo de personal y estructura

### `th_empleados`

Registro maestro del funcionario. Una fila representa a una persona, no a cada contrato.

| Campo | Tipo lógico | Regla / descripción |
|---|---|---|
| `empleado_id` | `INT` | PK, identidad interna inmutable. |
| `identificacion` | `VARCHAR(15)` | Cédula o pasaporte; obligatorio y único. |
| `apellidos`, `nombres` | `NVARCHAR` | Identificación nominal oficial. |
| `fecha_nacimiento` | `DATE` | Base para edad y cumpleaños. |
| `sexo` | `VARCHAR/NVARCHAR` | Dominio normalizado usado en estadísticas de género. |
| `estado_civil`, `tipo_sangre`, `condicion_especial` | Texto controlado | Datos personales del expediente. |
| `correo_institucional`, `telefono`, `direccion` | Texto | Contacto institucional/personal autorizado. |
| `unidad_id` | `INT` | FK a `th_unidades_organizacionales`. |
| `puesto_id` | `INT` | FK a `th_puestos`. |
| `regimen_laboral` | `VARCHAR` | `LOSEP` o `CODIGO_TRABAJO`. |
| `tipo_contrato` | `NVARCHAR` | LOSEP: nombramientos/ocasional; Código del Trabajo: indefinido. |
| `fecha_ingreso`, `fecha_salida` | `DATE` | Fechas del vínculo actual; el histórico se conserva aparte. |
| `remuneracion_mensual`, `partida_individual` | Decimal / texto | Datos presupuestarios laborales. |
| `jornada`, `horas_jornada` | Texto / decimal | Jornada contractual base. |
| `estado` | `BIT` | `1` activo, `0` inactivo. |
| `estado_fecha_efectiva`, `estado_motivo`, `estado_origen`, `estado_accion_id` | Varios | Trazabilidad de altas, bajas y reingresos. |
| `ruta_foto` | `NVARCHAR` | Ruta relativa administrada; nunca contenido binario en SQL. |

### `th_unidades_organizacionales`

Catálogo jerárquico de procesos, direcciones y áreas.

| Campo | Descripción |
|---|---|
| `unidad_id` | PK. |
| `codigo_uorg` | Código institucional de la unidad. |
| `nombre_unidad` | Denominación oficial. |
| `unidad_padre_id` | FK autorreferenciada; nulo para nivel raíz. |
| `tipo_unidad`, `tipo_proceso`, `nivel_gestion` | Clasificación organizacional. |
| `activo` | Habilita la unidad para nuevas asignaciones sin borrar historia. |

### `th_puestos`

Catálogo de cargos o denominaciones de puesto.

| Campo | Descripción |
|---|---|
| `puesto_id` | PK. |
| `codigo_puesto` | Código institucional. |
| `nombre_puesto` | Denominación mostrada en formularios. |
| `remuneracion_unificada` | RMU de referencia. |
| `grupo_ocupacional`, `grado` | Clasificación ocupacional. |
| `activo` | Disponibilidad para nuevas asignaciones. |

### `th_nacionalidades` y `th_empleado_nacionalidades`

`th_nacionalidades` contiene el catálogo normalizado (`nacionalidad_id`, nombre, país y estado). La tabla puente asocia una o varias nacionalidades con el funcionario mediante `empleado_id`, `nacionalidad_id`, `es_principal` y `orden`. La pareja funcionario/nacionalidad no debe duplicarse.

### `th_historial_laboral`

Fotografía cronológica de la situación jerárquica: funcionario, unidad, puesto, fechas desde/hasta, tipo y observación del movimiento. No se elimina cuando cambia la situación vigente.

### `th_periodos_vinculacion`

Períodos efectivos de trabajo utilizados para reingresos y antigüedad.

| Campo | Descripción |
|---|---|
| `periodo_id` | PK. |
| `empleado_id` | FK al funcionario. |
| `fecha_inicio`, `fecha_fin` | Intervalo; fin nulo significa vínculo abierto. |
| `tipo_inicio`, `tipo_fin` | Ingreso/reingreso y motivo de terminación. |
| `accion_inicio_id`, `accion_fin_id` | Acciones que originan el período. |

## 4. Acciones, vigencias y movimientos

### `th_acciones_personal`

Documento central de cambios laborales.

| Grupo de campos | Contenido |
|---|---|
| Identificación | `accion_id` PK, `numero_accion`, `empleado_id`, `tipo_accion`, `regimen_laboral`. |
| Vigencia | `fecha_elaboracion`, `fecha_rige_desde`, `fecha_rige_hasta`, `modalidad_vigencia`. |
| Situación actual | Unidad, puesto, proceso, nivel, lugar, grupo, grado, RMU, partida, contrato, jornada y horas de origen. |
| Situación propuesta | Los mismos atributos con prefijo o semántica `propuesta_`. |
| Jornada temporal | `tipo_novedad_jornada`, horas, horario, documento y observaciones. |
| Documento | `explicacion`, declaración, notificación y correos destinatarios. |
| Control | `estado_documento`, usuario/fecha de creación, aprobación o anulación. |

Estados documentales: `BORRADOR`, `APROBADO` y `ANULADO`. Solo un documento aprobado afecta la situación efectiva y puede legalizarse con un PDF firmado.

### `th_catalogo_series_accion`, `th_contadores_series_accion` y `th_secuencias_documentos`

- `th_catalogo_series_accion`: asigna prefijos (`MP`, `CA`, `LI`, `RD`, `VAC`) a tipos de acción.
- `th_contadores_series_accion`: conserva el último correlativo por serie y año bajo bloqueo transaccional.
- `th_secuencias_documentos`: parametriza otras secuencias, incluido `CdgT` para Código del Trabajo.

El número definitivo se reserva al guardar; no se debe calcular en el navegador.

### `th_vigencias_laborales`

Cambio temporal con retorno automático. Conserva `accion_id`, `empleado_id`, rango de fechas, estado, fotografía de origen y propuesta. Estados: `PROGRAMADA`, `VIGENTE`, `APLICADA`, `FINALIZADA`, `CANCELADA` o `ERROR`.

### `th_jornadas_especiales`

Excepción temporal a la jornada base. Registra tipo de novedad, fechas, horas, horario, documento, estado y Acción de Personal. Maternidad y paternidad usan cero horas; sustituto usa la jornada especial autorizada.

### `th_movimientos_personal` y `th_movimientos_lote`

Movimientos internos que cambian el área sin emitir Acción de Personal. La primera tabla conserva cada movimiento individual; la segunda agrupa operaciones atómicas de varios funcionarios, con cantidad, destino, fecha, motivo, estado y auditoría.

## 5. Estudio socioeconómico

### `th_estudios_socioeconomicos`

Cabecera y contenido vigente del formulario socioeconómico. Incluye:

- funcionario y datos personales ampliados;
- familia, vivienda, ingresos, egresos, salud, referencias y formación;
- `latitud`, `longitud`, enlace/origen cartográfico e indicaciones;
- rutas privadas de imagen de mapa y QR;
- estado, usuario y fechas de creación/actualización.

Solo debe existir un estudio vigente por funcionario.

### Tablas detalle

| Tabla | Propósito | FK principal |
|---|---|---|
| `th_estudio_hijos` | Hasta tres hijos o dependientes del formulario. | `estudio_id` |
| `th_estudio_capacitaciones` | Hasta tres capacitaciones declaradas. | `estudio_id` |
| `th_estudio_experiencias` | Experiencia laboral registrada. | `estudio_id` |

## 6. Vacaciones, reconocimientos y Paz y Salvo

### `th_reconocimientos_servicio`

Controla reconocimientos de 5, 10, 15, 20, 25 y 30 años mediante `empleado_id`, `hito_anios`, `fecha_hito` y estado `PENDIENTE` o `ENTREGADO`.

### `th_paz_salvo`

Cabecera del documento de salida: `paz_salvo_id` PK, funcionario, Acción de Personal de salida, número, fechas de emisión/salida, lugar, observaciones, estado y auditoría. Estados: `BORRADOR`, `EN_REVISION`, `OBSERVADO`, `PARCIAL`, `COMPLETO`, `CERRADO`.

### `th_paz_salvo_secciones`

Cinco certificaciones por Paz y Salvo. La clave funcional combina documento y `codigo_seccion`. Conserva estado (`PENDIENTE`, `CONFORME`, `OBSERVADO`), datos JSON, observaciones, responsable, puesto y sumilla.

Vacaciones no usa una tabla duplicada: su fuente autoritativa es la vista `vw_th_vacaciones_acciones`, derivada de Acciones de Personal `VACACIONES` aprobadas.

## 7. Documentos y borradores

### `th_documentos_firmados`

Metadatos del formulario impreso, firmado y escaneado.

| Campo | Descripción |
|---|---|
| `documento_firmado_id` | PK. |
| `tipo_documento`, `origen_id`, `empleado_id` | Identifican el registro legalizado. |
| `version` | Secuencia por documento de origen. |
| `ruta_archivo` | Ruta privada opaca; nunca URL pública. |
| `nombre_original`, `mime_type`, `tamano_bytes`, `sha256` | Integridad y características del archivo. |
| `estado` | `FIRMADO` para la versión actual; `REEMPLAZADO` para anteriores. |
| `usuario_carga`, `direccion_ip`, `fecha_carga` | Cadena de custodia. |

### `th_borradores_formulario`

Borrador cifrado por usuario y contexto. Guarda datos cifrados, nonce/etiqueta, fecha y versión. No contiene formularios en texto plano.

### `th_politicas_documentos`

Repositorio de metadatos para políticas y normativas: nombre, tipo, ruta privada, estado, versión y auditoría.

## 8. Seguridad, acceso y auditoría

| Tabla | Finalidad | Claves / reglas relevantes |
|---|---|---|
| `th_usuarios_sistema` | Cuenta autenticable vinculada opcionalmente a un funcionario. | PK `usuario_id`; usuario único; hash de clave; rol; estado; MFA; contador de intentos; versión de token. |
| `th_roles` | Perfiles de autorización. | PK `rol_id`; nombre/código único; estado. |
| `th_modulos` | Catálogo de capacidades del portal. | PK `modulo_id`; `codigo_modulo` único. |
| `th_permisos_rol` | Matriz RBAC. | FK rol/módulo; permisos de visualizar, crear, editar y eliminar. |
| `th_puesto_rol_mapa` | Roles admitidos para cada cargo. | FK `puesto_id` y `rol_id`; evita asignaciones incompatibles. |
| `th_logs_auditoria` | Bitácora inmutable de accesos y operaciones. | Usuario, módulo, acción, detalle, IP y fecha. |
| `th_parametros` | Configuración institucional de negocio. | Clave de parámetro y valor; no almacenar secretos. |

## 9. Control técnico

| Tabla | Propósito |
|---|---|
| `th_schema_migrations` | Versión, archivo, checksum y fecha de cada migración aplicada. |
| `th_titulos` | Catálogo legado o auxiliar de títulos académicos. |

Las tablas con prefijo `th_respaldo_` y `th_acciones_personal_old` son copias lógicas de conciliaciones históricas. No participan en la operación diaria y no deben borrarse sin acta, respaldo verificado y autorización DBA.

## 10. Vistas de consulta

| Vista | Uso principal |
|---|---|
| `vw_th_directorio_empleados` | Fuente consolidada del directorio, formularios y búsqueda global. |
| `view_th_iddatosempledo` | Alias compatible para integraciones legadas. |
| `vw_th_situacion_laboral_efectiva` | Aplica por fecha vigencias y jornadas temporales sin alterar la base. |
| `vw_th_reporte_historial_jerarquico` | Períodos jerárquicos del funcionario. |
| `vw_th_eventos_laborales` | Acciones, vacaciones, jornadas, movimientos, estudios, Paz y Salvo y documentos firmados. |
| `vw_th_vacaciones_acciones` | Vacaciones programadas, vigentes y finalizadas. |
| `vw_th_antiguedad_empleados` | Días/años efectivos acumulados. |
| `vw_th_hitos_servicio` | Hitos anuales de reconocimiento. |
| `vw_th_estadisticas_genero` | Conteos institucionales por género. |
| `vw_th_estudios_socioeconomicos` | Resumen consultable de estudios vigentes. |
| `vw_th_documentos_firmados` | Versiones y metadatos legalizados. |
| `vw_th_maestros_organizacionales` | Unidades y jerarquía para selectores. |
| `vw_th_movimientos_personal` | Consulta de movimientos internos. |
| `vw_th_resumen_auditoria_usuarios` | Actividad consolidada por usuario. |

## 11. Procedimientos de negocio principales

- Empleados: `sp_th_guardar_empleado_v2`, `sp_th_modificar_empleado_v2`, `sp_th_eliminar_empleado`, `sp_th_cambiar_estado_empleado`.
- Acciones: `sp_th_registrar_accion_personal_v3`, `sp_th_aprobar_accion_personal_v3`, `sp_th_anular_accion_personal`.
- Vigencias: `sp_th_refrescar_vigencias_laborales`, `sp_th_consultar_vigencias_laborales`, `sp_th_consultar_jornadas_especiales`.
- Movimientos: `sp_th_mover_empleado`, `sp_th_mover_empleados_lote`.
- Paz y Salvo: `sp_th_crear_paz_salvo`, `sp_th_guardar_seccion_paz_salvo`, `sp_th_cerrar_paz_salvo`.
- Documentos: `sp_th_registrar_documento_firmado`, `sp_th_consultar_documentos_firmados`, `sp_th_consultar_eventos_laborales`.
- Seguridad: `sp_th_crear_usuario_sistema`, `sp_th_rol_sugerido_por_empleado`, `sp_th_mapa_roles_puestos`.
- Auditoría/consulta: `sp_th_registrar_auditoria`, `sp_th_auditar_lectura`, `sp_th_consultar_directorio`, `sp_th_consultar_historial`.

## 12. Reglas de conservación y privacidad

1. No eliminar físicamente funcionarios, acciones, historial, auditoría ni documentos firmados desde la aplicación.
2. Desactivar maestros y cuentas antes que borrar registros referenciados.
3. Mantener fotos, mapas, QR, políticas, respaldos y PDFs firmados fuera de Git y, cuando corresponda, fuera de `public/`.
4. No registrar contraseñas, secretos MFA, claves criptográficas ni credenciales SQL en tablas de parámetros o logs.
5. Toda restauración o depuración material requiere respaldo con checksum y prueba de recuperación.
6. Los reportes deben leer vistas consolidadas; no replicar estados calculados en nuevas tablas.

## 13. Consulta de verificación para el DBA

```sql
SELECT t.name AS tabla, c.column_id, c.name AS columna, ty.name AS tipo,
       c.max_length, c.precision, c.scale, c.is_nullable
FROM sys.tables t
JOIN sys.columns c ON c.object_id = t.object_id
JOIN sys.types ty ON ty.user_type_id = c.user_type_id
WHERE t.schema_id = SCHEMA_ID('dbo') AND t.name LIKE 'th[_]%'
ORDER BY t.name, c.column_id;
```

La salida debe archivarse como evidencia del ambiente, no reemplazar este documento versionado.
