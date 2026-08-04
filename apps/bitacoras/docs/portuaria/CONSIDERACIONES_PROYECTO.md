# Consideraciones del Proyecto (Operación y Permisos)

Documento de referencia rápida para entender reglas de negocio, validaciones y permisos por módulo.

## 1) Acceso general al sistema

- Todo módulo protegido requiere sesión iniciada (`auth_guard`).
- Si no hay sesión válida, el sistema redirige a `bit_login.php`.
- La navegación visible depende del departamento del usuario autenticado.

## 2) Roles y alcance funcional

## 2.1 Departamentos clave

- **TI / Tecnología de la Información**: `id_departamento = 1`.
- **Gerencia**: `id_departamento = 5`.
- **Seguridad Integral (guardia operativo)**: permisos operativos de bitácora y visitas.
- **Edificio Administrativo**: comparte permisos administrativos con TI.

## 2.2 Permisos principales por módulo

- **Registrar ingreso de visita**: TI, Edificio Administrativo, Seguridad Integral.
- **Listado de visitas**: TI, Edificio Administrativo, Seguridad Integral.
- **Registrar salida**: TI, Edificio Administrativo, Seguridad Integral.
- **Editar visita desde listado**: TI, Edificio Administrativo, Seguridad Integral.
- **Asignar cédula real a visitante Guest**: solo TI y Edificio Administrativo.
- **Registros base / catálogos**: solo TI y Edificio Administrativo.
- **Bitácora de rondas**: TI, Edificio Administrativo, Seguridad Integral.
- **Configurar días de edición de bitácora**: TI (y áreas admin).
- **Panel de jefatura (`bit_dashboard_jefe.php`)**: solo TI y Gerencia.
- **Reporte supervisor**: disponible para usuarios autenticados.

## 3) Registro de visitas: validaciones y reglas

## 3.1 Validaciones de datos de ingreso

- Nombres, apellidos, funcionario, destino y motivo son obligatorios.
- Identificación:
  - Si es visitante Guest, se usa `9999999999`.
  - Si no es Guest, valida cédula (10 dígitos) o RUC (13 dígitos) de Ecuador.
- Nivel de incidente:
  - Si no llega uno válido, usa el nivel por defecto activo.
- Empresa:
  - Si no se selecciona empresa válida, se considera visita tipo `Personal`.
  - Si empresa existe y está activa, se considera `Empresa`.

## 3.2 Duplicados en registro de visitas (regla crítica)

- **No se permite más de una visita activa por persona**.
- Antes de insertar, el sistema busca una visita sin `hora_salida` para la misma persona.
- Si existe visita activa, el ingreso se bloquea y se solicita registrar salida primero.

## 3.3 Fechas y horas

- El formulario muestra fecha/hora de visita y el backend normaliza fecha.
- Si la fecha es inválida, el registro se rechaza.
- En salida, la hora se toma del servidor (`GETDATE()`), no del cliente.

## 4) Listado de visitas: comportamiento operativo

- Muestra histórico completo con estado (`Dentro` o `Finalizada`).
- Acciones visibles por fila dependen del permiso del usuario:
  - Registrar salida (si sigue dentro y tiene permiso).
  - Editar visita (si tiene permiso).
  - Asignar cédula a Guest (solo admin/TI).
- Si el usuario no tiene permisos de edición/salida, queda en modo lectura.

## 5) Bitácora de rondas: qué hace admin vs guardia

## 5.1 Qué puede hacer un guardia operativo

- Crear y editar sus propios registros de ronda.
- Consultar turno actual y búsqueda histórica.
- Registrar actividad con nivel de alerta.
- Ajustar hora de registro dentro de la franja del turno.
- **Limitación por ventana de edición**: solo puede editar hasta N días atrás (N configurable).

## 5.2 Qué puede hacer un administrador (TI / área admin)

- Ver registros de todos los guardias en turno/fecha.
- Configurar cuántos días puede editar un guardia (`1, 3, 5, 7`).
- Operar el módulo con mayor alcance de supervisión.

## 5.3 Reglas de fecha/hora en bitácora

- Turnos:
  - Mañana: `07:00 - 15:00`
  - Tarde: `15:00 - 23:00`
  - Noche: `23:00 - 07:00` (cruza medianoche)
- La fecha operativa se calcula según turno.
- No se permite registrar fecha futura (respecto al servidor).
- La hora del registro debe caer dentro de la ventana del turno configurada.

## 5.4 Edición según fecha (muy importante)

- La configuración se guarda en `dbo.bit_parametro`, clave `dias_edicion`.
- Valores válidos para guardia: `1`, `3`, `5`, `7`.
- Si un guardia intenta editar más antiguo que la ventana permitida, el sistema bloquea con `403`.
- Usuarios con permisos de configuración no quedan limitados por esa ventana.

## 6) Reporte diario de supervisor

- Permite crear/consultar reporte por fecha.
- Permite gestionar novedades del día (crear, editar, eliminar).
- Al editar novedad, se puede cambiar hora, descripción y estado.
- La fecha del reporte controla el contexto del día.
- Usa totales diarios y gráficos por hora/tipo; si no hay datos en fecha seleccionada, puede mostrar acumulado para no dejar panel vacío.

## 7) Panel de jefatura

- Acceso estricto: solo TI y Gerencia.
- Incluye:
  - KPIs (visitas activas, rondas del día, alertas críticas 24h).
  - Gráficos semanales.
  - Actividad reciente con actualización por polling cada 10 segundos.
- Desde actividad reciente se puede abrir detalle de visita/bitácora según referencia del movimiento.

## 8) Menú por tipo de usuario (resumen práctico)

- **TI**: ve prácticamente todo (incluye Panel jefatura y configuración de días bitácora).
- **Gerencia**: ve Panel jefatura; no necesariamente módulos operativos de edición.
- **Seguridad Integral (guardia)**: foco en registrar ingresos/salidas, listado, bitácora y reporte.
- **Otros departamentos**: acceso más limitado; si no cumple permiso de módulo, recibe denegación.

## 9) Recomendaciones para operación diaria

- Ejecutar primero salida antes de intentar reingresar a la misma persona.
- Mantener catálogo de departamentos limpio en `dbo.bit_departamentos` (fuente oficial para login/register).
- No usar fecha/hora del navegador como verdad operativa; el sistema prioriza servidor y reglas de turno.
- Revisar periódicamente el valor de `dias_edicion` para evitar bloqueos inesperados a guardias.

