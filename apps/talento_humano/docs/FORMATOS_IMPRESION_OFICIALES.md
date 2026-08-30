# Formatos oficiales de impresión

Implementación: 29 de julio de 2026.

## Alcance

- Formulario Principal de Registro: PDF A4 de 2 páginas (`APM-TH-FO-001`) con todos los campos del alta de personal: información personal, foto, nacionalidades, datos laborales, contacto, formación, observaciones, declaración y firmas.
- Acción de Personal: PDF A4 de 2 páginas, con datos del registro, casilleros, situación actual/propuesta, posesión, aprobaciones, recepción, responsables y notificación.
- Estudio de Seguridad Socioeconómico: PDF A4 de 4 páginas, con datos generales, contacto, bienes, banco, cónyuge, hijos, formación, tres capacitaciones, tres experiencias, vivienda y vehículo.
- Paz y Salvo: PDF A4 de 2 páginas para desvinculación, con certificaciones de Jefatura inmediata, Talento Humano, Financiero, Administrativo y TIC, responsables, observaciones y firmas.
- Página 4 socioeconómica: ubicación domiciliaria con mapa, coordenadas, enlace universal, referencias, QR y firmas de verificación.
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
- Paz y Salvo vacío: `/talento-humano/paz-salvo/formato-blanco`
- Paz y Salvo registrado: `/talento-humano/paz-salvo/imprimir?id={paz_salvo_id}`

## Verificación técnica

Ejecutar desde la raíz del proyecto:

```powershell
C:\php85-nts\php.exe scripts\generar_muestras_formatos.php
C:\php85-nts\php.exe tests\pdf_compatibility_static.php
C:\php85-nts\php.exe -l modules\talento-humano\Servicios\PdfFormularioPrincipal.php
C:\php85-nts\php.exe -l modules\talento-humano\Servicios\PdfEstudioSocioeconomico.php
C:\php85-nts\php.exe -l modules\talento-humano\Controladores\AccionPersonalController.php
C:\php85-nts\php.exe -l modules\talento-humano\Controladores\EstudioSeguridadController.php
```

Las muestras verificadas quedan en `output/pdf/`, directorio de trabajo que no debe usarse como repositorio documental ni versionarse.

La correspondencia completa entre datos y salida se mantiene en la [matriz de campos](MATRIZ_FORMATOS_OFICIALES.md).

## Aceptación técnica y visual del 13-08-2026

Se regeneraron y renderizaron a imagen las versiones precargadas y en blanco de
los tres documentos. El resultado comprobado fue:

- Formulario Principal: 2 páginas A4, campos, declaración y firmas legibles;
- Acción de Personal: 2 páginas A4, casillero seleccionado, situaciones,
  responsables, recepción, notificación y pies completos;
- Socioeconómico: páginas 1 a 3 A4 alineadas con las referencias autorizadas;
- página 4: ubicación domiciliaria, QR y trazabilidad de la captura;
- seis PDF con texto extraíble y sin caracteres mojibake;
- generación en PHP 8.5 sin avisos de funciones obsoletas.

Esta es una aceptación técnica del render. La incorporación de una nueva hoja o
el cambio de contenido oficial continúa requiriendo aprobación documental de APM.

## Página 4 de ubicación

La implementación se concentra en `pagina4Ubicacion()` de `PdfEstudioSocioeconomico.php`. Los archivos de mapa y QR se leen exclusivamente desde el repositorio privado y la base almacena únicamente nombres opacos, coordenadas, enlace y referencias.
