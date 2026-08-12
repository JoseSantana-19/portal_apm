# Formatos oficiales de impresión

Implementación: 29 de julio de 2026.

## Alcance

- Formulario Principal de Registro: PDF A4 de 2 páginas (`APM-TH-FO-001`) con todos los campos del alta de personal: información personal, foto, nacionalidades, datos laborales, contacto, formación, observaciones, declaración y firmas.
- Acción de Personal: PDF A4 de 2 páginas, con datos del registro, casilleros, situación actual/propuesta, posesión, aprobaciones, recepción, responsables y notificación.
- Estudio de Seguridad Socioeconómico: PDF A4 de 4 páginas, con datos generales, contacto, bienes, banco, cónyuge, hijos, formación, tres capacitaciones, tres experiencias, vivienda y vehículo.
- Página 4 socioeconómica: hoja reservada claramente identificada. No se añadieron campos no autorizados. Debe sustituirse cuando se reciba la imagen oficial.
- Biblioteca: descarga formatos oficiales vacíos y permite abrir los PDF de registros guardados. El formato principal en blanco y el botón verde del Directorio usan la misma plantilla `APM-TH-FO-001`.
- Auditoría: registra creación, actualización, consulta e impresión con usuario, IP y fecha/hora del servidor SQL.

## Instalación en otra estación

1. Respaldar la base `Talento_Humano` y el proyecto.
2. Ejecutar `database/migracion_formatos_oficiales_2026.sql` con un usuario autorizado de SQL Server.
3. Confirmar que existen las tablas `th_estudios_socioeconomicos`, `th_estudio_hijos`, `th_estudio_capacitaciones` y `th_estudio_experiencias`.
4. Confirmar la vista `vw_th_estudios_socioeconomicos` y el procedimiento `sp_th_consultar_estudios_socioeconomicos`.
5. Ingresar al Portal, abrir Biblioteca y probar las tres opciones “Descargar Formato”.
6. Guardar un estudio y una acción de prueba; abrir sus PDF desde “Ver registros”.
7. Verificar en `th_logs_auditoria` los eventos `CREAR`, `ACTUALIZAR`, `CONSULTAR_VISTA` e `IMPRIMIR`.

## Rutas funcionales

- Formato principal vacío: `/talento-humano/empleado/formato-principal-blanco`
- Formulario principal con datos: `/talento-humano/empleado/imprimir-ficha?id={empleado_id}`
- Formato vacío de Acción de Personal: `/talento-humano/accion-personal/formato-blanco`
- Acción guardada: `/talento-humano/accion-personal/imprimir-accion?id={accion_id}`
- Formato vacío socioeconómico: `/talento-humano/estudio-seguridad/imprimir?blank=1`
- Estudio guardado: `/talento-humano/estudio-seguridad/imprimir?estudio_id={estudio_id}`
- Edición de estudio: `/talento-humano/estudio-seguridad?estudio_id={estudio_id}`

## Verificación técnica

Ejecutar desde la raíz del proyecto:

```powershell
php scripts\generar_muestras_formatos.php
php -l modules\talento-humano\Servicios\PdfFormularioPrincipal.php
php -l modules\talento-humano\Servicios\PdfEstudioSocioeconomico.php
php -l modules\talento-humano\Controladores\AccionPersonalController.php
php -l modules\talento-humano\Controladores\EstudioSeguridadController.php
```

Las muestras verificadas quedan en `output/pdf/`.

## Incorporación futura de la página 4

Modificar únicamente `pagina4Reservada()` en `PdfEstudioSocioeconomico.php`, reemplazando la hoja reservada por la estructura y campos de la imagen autorizada. Si la hoja incorpora campos nuevos, deben agregarse primero a la matriz de campos, a la migración SQL, al modelo y al formulario antes de imprimirlos.
