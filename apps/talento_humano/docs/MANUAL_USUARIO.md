# Manual de usuario

Sistema: **Portal Portuario APM — Talento Humano**
Versión: **30 de agosto de 2026**

## 1. Objetivo

Este manual explica el uso diario del portal para Administradores APM y personal autorizado de Talento Humano. Las opciones visibles dependen del rol; si una función no aparece, solicite la revisión del permiso y no utilice enlaces copiados de otra cuenta.

Los módulos Asistencia y Turnos, Evaluación/Desempeño y Capacitación continúan identificados como **Demo**. No deben usarse como registro institucional.

## 2. Acceso y seguridad

1. Abra la dirección institucional entregada por TI.
2. Ingrese usuario y contraseña.
3. Si es el primer acceso, defina una clave personal de al menos 12 caracteres con mayúscula, minúscula, número y símbolo.
4. Si la cuenta tiene segundo factor, escriba el código vigente de la aplicación autenticadora.
5. Use el menú de perfil para cambiar clave, administrar el segundo factor o cerrar sesión.

Nunca comparta la cuenta. El sistema registra usuario, fecha, módulo, operación e IP.

## 3. Pantalla y navegación

- **Menú lateral:** muestra únicamente los módulos autorizados.
- **Búsqueda superior:** escriba cédula, nombre, cargo o área; filtre por Todos, Activos o Inactivos y seleccione al funcionario para abrir su perfil.
- **Campana:** informa documentos pendientes y alertas operativas.
- **Fecha institucional:** es la referencia para vigencias, vacaciones, cumpleaños e hitos.
- **Perfil:** contiene seguridad y cierre de sesión.

Los selectores extensos de funcionarios, áreas y cargos permiten escribir para filtrar. Debe elegir una opción real de la lista; escribir texto sin seleccionarlo no completa el campo.

## 4. Roles principales

### Administrador APM

Acceso integral a personal, documentos, maestros, usuarios, roles, políticas, auditoría y reportes. Las acciones sensibles deben realizarse con cuentas nominativas.

### Asistente de Talento Humano

Acceso operativo a Inicio, Nómina, Acción de Personal, Movimientos internos, Estudio Socioeconómico, Vacaciones, Paz y Salvo, Biblioteca, Estructura y cargos y Reportes Generales. No administra usuarios, roles, políticas ni auditoría técnica.

### Funcionario de lectura

Consulta restringida según los permisos asignados; no puede aprobar, eliminar ni administrar catálogos.

## 5. Inicio

El panel presenta:

- funcionarios registrados y activos;
- vacaciones vigentes;
- cumpleaños próximos;
- aniversarios de servicio de 5, 10, 15, 20, 25 o 30 años.

En **Agenda de Talento Humano**, alterne Cumpleaños/Aniversarios y aplique los rangos disponibles. Use **Ver perfil** para confirmar la información antes de cualquier reconocimiento o contacto.

## 6. Registrar un funcionario

Ruta: **Nómina de Personal → Nuevo expediente**.

1. Complete la pestaña Personal.
2. Ingrese obligatoriamente la cédula o pasaporte. El sistema no guarda el formulario vacío y avisa si la identificación ya existe.
3. Complete apellidos, nombres y demás datos personales.
4. En Laboral, seleccione área y cargo desde los catálogos.
5. Seleccione el régimen:
   - **LOSEP:** Nombramiento Permanente, Nombramiento Provisional o Contrato Ocasional; permite Acción de Personal completa.
   - **Código del Trabajo:** fija Contrato Indefinido y utiliza Formulario Laboral Abreviado.
6. Registre fecha de ingreso, remuneración, jornada base y horas contractuales.
7. Complete Contacto, Formación y Notas.
8. Pulse **Guardar funcionario**.

La condición Sustituto establece la jornada especial definida por la normativa del proyecto. Las excepciones temporales no se cambian aquí; se registran mediante Acción de Personal.

## 7. Consultar y editar la Nómina

La tabla permite buscar y filtrar por área, contrato y estado. Desde cada fila, según permisos, puede:

- abrir el perfil;
- editar el expediente;
- consultar historial;
- imprimir la ficha;
- iniciar una Acción de Personal;
- dar de baja mediante el flujo autorizado.

No cree otro funcionario para representar un reingreso. Use la Acción de Personal correspondiente para conservar períodos y antigüedad.

## 8. Acción de Personal

Ruta: **Gestión de Talento Humano → Acción de Personal**.

### Crear el borrador

1. Busque y seleccione al funcionario.
2. Confirme el código previsto, régimen y plantilla.
3. Elija **Permanente** o **Temporal con retorno automático**.
4. Indique fecha de elaboración y `Rige desde`. En temporal, `Rige hasta` es obligatorio.
5. Seleccione el tipo de acción.
6. Elija el modo:
   - **Cambio laboral:** área, cargo, RMU, contrato u otras condiciones.
   - **Solo jornada temporal:** lactancia, maternidad, paternidad, sustituto u horario especial; no cambia el cargo permanente.
7. Complete únicamente los campos propuestos que correspondan.
8. Escriba motivación y fundamento.
9. Si debe notificarse, agregue uno o varios correos. Las direcciones frecuentes quedan disponibles en el equipo para futuras capturas.
10. Guarde el borrador.

El número definitivo se asigna en SQL Server. No transcriba ni modifique manualmente el correlativo.

### Revisión, edición, aprobación o rechazo

En **Biblioteca de Formularios → Acción de Personal**:

- **Editar:** corrige puesto, fechas, propuesta, motivación o notificación mientras está en borrador.
- **Aprobar:** aplica el cambio de forma auditada.
- **Rechazar/Anular:** exige motivo y evita que el documento afecte al funcionario.
- **PDF:** genera la plantilla que corresponde al régimen.

Una acción permanente sigue vigente hasta otra acción. Una temporal muestra la propuesta solo dentro del rango y luego reaparece automáticamente la situación base.

Maternidad y paternidad se registran como licencia temporal de cero horas, sin borrar la jornada contractual.

## 9. Vacaciones

Vacaciones se origina exclusivamente en una Acción de Personal:

1. Cree una acción tipo **Vacaciones**.
2. Seleccione vigencia temporal.
3. Registre fechas desde/hasta.
4. Guarde y apruebe.
5. Consulte **Vacaciones** para ver estado Programada, Vigente o Finalizada.

El módulo de Vacaciones es de consulta; no duplique solicitudes ni saldos allí.

## 10. Movimientos internos

Use esta opción cuando solo cambia el área y se conserva el cargo, sin documento legal:

1. Abra **Movimientos internos**.
2. Seleccione uno o varios funcionarios activos.
3. Elija área de destino y fecha efectiva.
4. Indique el motivo.
5. Confirme.

La operación grupal es atómica: si un integrante no cumple las reglas, no se mueve ninguno.

## 11. Estudio Socioeconómico

1. Busque y seleccione al funcionario.
2. Complete las secciones del formulario.
3. En ubicación domiciliaria puede:
   - pegar un enlace HTTPS oficial de Google Maps y pulsar **Ubicar**;
   - hacer clic o mover el marcador en el mapa;
   - ingresar coordenadas válidas cuando corresponda.
4. Escriba referencia e indicaciones del domicilio.
5. Verifique el QR con un móvil; debe abrir las coordenadas registradas.
6. Guarde y genere el PDF de cuatro páginas.

Los enlaces cortos que no contengan coordenadas deben resolverse con el botón **Ubicar**. Si no se resuelven, seleccione el punto manualmente.

## 12. Paz y Salvo

Solo aparece una Acción de salida aprobada de **Cesación de funciones** o **Destitución**.

1. Abra **Paz y Salvo → Crear documento**.
2. Busque la Acción de salida por número, cédula o funcionario.
3. Confirme lugar, fecha de emisión y salida.
4. Cada área completa su certificación: Jefe inmediato, Talento Humano, Financiero, Administrativo y TI.
5. Registre resultado, responsable, puesto, sumilla y observaciones.
6. Todas las secciones deben quedar `CONFORME` para obtener estado `COMPLETO`.
7. Cierre el documento y genere el PDF.

## 13. Imprimir, firmar, escanear y legalizar

Los formatos del sistema no se modifican.

1. Genere e imprima el PDF autorizado.
2. Obtenga todas las firmas manuscritas requeridas.
3. Escanee el documento completo en un único PDF legible y sin contraseña.
4. Abra **Documento firmado** desde el registro o historial.
5. Seleccione el archivo, agregue observaciones y pulse **Subir documento firmado**.

El máximo es 20 MB. Una nueva versión no elimina la anterior: la sustituye como versión vigente y conserva la cadena de custodia.

Condiciones previas:

- ficha principal: expediente existente;
- Acción de Personal: aprobada;
- estudio socioeconómico: registrado;
- Paz y Salvo: cerrado.

## 14. Perfil e historial laboral

El perfil muestra la situación vigente y el historial completo vinculado al funcionario:

- períodos de ingreso, salida y reingreso;
- cambios jerárquicos;
- Acciones de Personal;
- vacaciones;
- jornadas temporales;
- movimientos internos;
- estudio socioeconómico;
- Paz y Salvo;
- documentos firmados.

Los accesos, cambios de roles y operaciones técnicas se consultan en Auditoría, no en la trayectoria laboral.

## 15. Biblioteca de Formularios

La Biblioteca agrupa formatos en blanco y registros existentes. Los modales permiten buscar por cédula o nombre, crear un registro, abrir el PDF, editar borradores, aprobar/anular cuando corresponda y acceder al documento firmado.

Use el formato en blanco solo cuando el procedimiento lo autorice; para datos institucionales prefiera el PDF generado desde el expediente.

## 16. Reportes Generales

- **Exportar PDF:** genera el informe institucional.
- **Exportar Excel:** descarga un `.xlsx` real con hojas de funcionarios, historial, acciones, estudios, jornadas, vigencias, períodos, vacaciones, hitos y Paz y Salvo.
- **Distribución por género:** usa el sexo normalizado del expediente.
- **Hitos de servicio:** incluye los reconocimientos que ocurren durante el año, aunque la fecha ya haya pasado.

Si una estadística aparece vacía, revise primero que el dato fuente del funcionario esté completo.

## 17. Estructura y cargos

Los usuarios autorizados pueden crear o editar unidades y puestos. Antes de crear:

1. Busque la denominación existente.
2. Respete el nivel jerárquico y la dirección padre.
3. Evite variantes por mayúsculas, tildes o abreviaturas.
4. Desactive una denominación que ya no deba usarse; no borre historia.

## 18. Usuarios, roles y permisos

Para crear una cuenta:

1. Seleccione un funcionario activo sin cuenta.
2. El usuario se deriva de su identificación.
3. El sistema propone roles compatibles con el cargo.
4. Registre correo y clave inicial segura.
5. Entregue la clave por un canal seguro y exija su cambio inmediato.

El Administrador puede desactivar cuentas, restablecer clave o segundo factor y mantener la matriz de permisos. Aplique mínimo privilegio.

## 19. Auditoría y políticas

- **Logs de Actividad:** consulta eventos técnicos y funcionales.
- **Reportes de Auditoría:** filtra por período y usuario y permite exportar.
- **Políticas y Normativas:** administra documentos vigentes sin exponer rutas privadas.

No use los logs como historial laboral; cada módulo tiene una finalidad distinta.

## 20. Estados frecuentes

| Registro | Estados principales |
|---|---|
| Funcionario | Activo, Inactivo |
| Acción de Personal | Borrador, Aprobado, Anulado |
| Vigencia | Programada, Vigente, Aplicada, Finalizada, Cancelada, Error |
| Vacación | Programada, Vigente, Finalizada |
| Paz y Salvo | Borrador, En revisión, Observado, Parcial, Completo, Cerrado |
| Sección Paz y Salvo | Pendiente, Conforme, Observado |
| Documento legalizado | Firmado, Reemplazado |

## 21. Mensajes y solución rápida

- **Cédula obligatoria:** regrese a Personal e ingrese una identificación válida.
- **Cédula registrada:** abra el expediente existente; no duplique.
- **Seleccione una opción del catálogo:** elija el resultado de la lista, no deje texto libre.
- **Acceso denegado:** solicite al Administrador revisar el rol; no reintente con URL manual.
- **Formulario vencido:** recargue la pantalla y repita la operación.
- **Borrador encontrado:** recupérelo solo si corresponde al trámite actual.
- **PDF firmado rechazado:** confirme que sea PDF, legible, completo, sin contraseña y menor de 20 MB.
- **Mapa vacío:** revise conexión al proveedor autorizado; las coordenadas pueden seguir seleccionándose manualmente.
- **Excel abre como XML/texto:** descargue nuevamente desde **Exportar Excel** y confirme extensión `.xlsx`.

Para soporte, entregue la referencia de error mostrada, módulo, hora aproximada y pasos realizados. No envíe contraseñas, códigos MFA ni documentos personales por canales no autorizados.
