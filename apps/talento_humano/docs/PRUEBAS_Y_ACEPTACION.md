# Pruebas y aceptación local

## Cobertura vigente al 02-09-2026

- Restauración real de FULL, diferencial y cinco logs en una base temporal, `DBCC CHECKDB`, comparación de empleados, migraciones y auditoría, y eliminación confirmada del destino temporal.
- UAT transaccional de login, AES-256-GCM, RBAC permitido/denegado, CSRF, cambio de clave, activación TOTP, segundo login con MFA, rechazo de reutilización y auditoría. La identidad de prueba se revierte.
- Revisión visual A4 de Acción de Personal (2 páginas), Formulario Principal (2 páginas) y Socioeconómico (4 páginas, incluida ubicación domiciliaria y QR), tanto poblados como en blanco.
- Navegación HTTPS real por dashboard, directorio, movimientos, Acción de Personal, Socioeconómico, biblioteca, maestros, usuarios, roles, políticas, reportes, auditoría y seguridad de cuenta.
- Búsqueda inmediata por cédula parcial/completa y nombre sin distinguir mayúsculas, minúsculas o tildes.
- Movimiento grupal visible únicamente en su modo y después de seleccionar dos funcionarios activos.
- Acordeones RBAC divididos en módulos operativos y tablas maestras.
- Apertura del Formulario Principal desde el botón de impresión: PDF oficial de dos páginas con datos cargados.
- Cierre de sesión y eliminación verificada de la cuenta temporal E2E.
- Asignación transaccional de serie `CA-###-AAAA` validada dentro de una prueba con reversión.
- Veinte migraciones y veinte huellas SHA-256 exigidas contra los archivos del repositorio.
- Mapa de roles por puesto completo, rol seguro de lectura para cargos sin excepción y rechazo transaccional de asignaciones incompatibles.
- Consultas con el usuario mínimo sobre períodos, vacaciones, hitos, género y Paz y Salvo.
- XLSX OOXML real de 383 KB con funcionarios, historial, acciones, estudios, jornadas, vigencias, períodos, vacaciones, hitos y Paz y Salvo.
- PDF Paz y Salvo de dos páginas renderizado y revisado sin desbordes.
- Régimen LOSEP/Código del Trabajo, serie `CdgT`, rol Asistente de Talento Humano, repositorio documental firmado y DataTables cubiertos por pruebas estáticas específicas.
- La compuerta local incorpora pruebas SQL de régimen laboral, rol asistente, geolocalización, documentos firmados y UAT transaccional.
- El cierre de períodos comprueba que ninguna alta quede sin episodio de vinculación y que futuras inserciones queden protegidas por trigger.

## Comandos repetibles

```powershell
C:\php85-nts\php.exe tests\uat_access_control.php
C:\php85-nts\php.exe scripts\generar_muestras_formatos.php
C:\php85-nts\php.exe tests\pdf_compatibility_static.php
C:\php85-nts\php.exe tests\frontend_assets_static.php
C:\php85-nts\php.exe tests\error_handling_static.php
C:\php85-nts\php.exe tests\operations_static.php
C:\php85-nts\php.exe tests\talent_operation_static.php
C:\php85-nts\php.exe tests\talent_operation_ui_render.php
C:\php85-nts\php.exe tests\talent_operation_db_smoke.php
C:\php85-nts\php.exe tests\role_position_static.php
C:\php85-nts\php.exe tests\role_position_db_smoke.php
C:\php85-nts\php.exe tests\regimen_laboral_static.php
C:\php85-nts\php.exe tests\regimen_laboral_db_smoke.php
C:\php85-nts\php.exe tests\rbac_asistente_talento_static.php
C:\php85-nts\php.exe tests\rbac_asistente_talento_db_smoke.php
C:\php85-nts\php.exe tests\signed_documents_static.php
C:\php85-nts\php.exe tests\signed_documents_db_smoke.php
C:\php85-nts\php.exe tests\signed_document_pdf_validation.php
C:\php85-nts\php.exe tests\full_report_xlsx.php
C:\php85-nts\php.exe tests\paz_salvo_pdf.php output\pdf\Paz_y_Salvo_APM_QA.pdf
C:\php85-nts\php.exe tests\smtp_static.php
C:\php85-nts\php.exe tests\smtp_config.php
C:\php85-nts\php.exe tests\release_readiness_static.php
```

Las pruebas que modifican temporalmente SQL exigen credenciales DBA solo durante su ejecución, trabajan dentro de una transacción o con nombres E2E restringidos y verifican su limpieza. Ninguna credencial se versiona.

Las pruebas SQL cifradas deben ejecutarse en una consola Windows real con acceso a las claves del certificado. Un entorno aislado puede informar que el cliente no admite cifrado aunque el mismo binario PHP apruebe el preflight fuera del aislamiento; la evidencia válida es la salida de `deployment/run-local-validation-gate.ps1` ejecutada como administrador.

Si la compuerta informa un funcionario sin período, no se debe insertar manualmente el registro ni modificar una migración histórica: aplique `database/migracion_integridad_periodos_20260830.sql` mediante `deployment/apply-local-migration.ps1`. El script crea respaldo, reconcilia únicamente faltantes, instala la protección para nuevas altas y actualiza el ledger.

La misma migración instala `dbo.sp_th_actualizar_borrador_accion_personal` y concede únicamente `EXECUTE` a `portal_app_role`. Esto permite corregir borradores sin otorgar privilegios directos de modificación sobre `dbo.th_acciones_personal`.

## UAT posterior a cambios laborales

La aceptación final debe realizarse con datos sintéticos identificados por el prefijo `UAT` y limpiar todos los residuos al terminar:

1. registrar un funcionario LOSEP y otro de Código del Trabajo;
2. confirmar la redirección del segundo al Formulario Abreviado Laboral;
3. verificar fecha automática y responsable de elaboración derivado de la sesión;
4. imprimir Acción de Personal completa y Formulario Abreviado;
5. cargar un PDF firmado de prueba y comprobar su aparición en el historial integral;
6. editar un borrador, rechazar una acción y aprobar otra;
7. comprobar varios destinatarios y, con un relay de pruebas autorizado, la entrega SMTP;
8. filtrar la Nómina, cambiar paginación DataTables y buscar desde la barra global;
9. recorrer modo claro y oscuro con Super Administrador y Asistente de Talento Humano;
10. eliminar empleados/documentos UAT mediante el procedimiento controlado y confirmar que no queden cuentas, acciones o archivos temporales.

El workflow manual `.github/workflows/preproduction-gate.yml` automatiza la compuerta SQL/IIS en un runner Windows confiable. No se ejecuta en pull requests ni en runners públicos porque necesita certificados de equipo, SQL Server e IIS locales.

## Evidencia del UAT autenticado — 2 de septiembre de 2026

Se ejecutó con la cuenta administrativa suministrada para la validación, sin registrar su clave en archivos, documentación ni Git. Se usaron únicamente expedientes y documentos marcados como UAT.

| Comprobación | Resultado | Evidencia funcional |
|---|---|---|
| Alta LOSEP | Aprobada | Expediente sintético creado y disponible en Nómina. |
| Alta Código del Trabajo | Aprobada | Expediente sintético creado con Contrato Indefinido. |
| Formulario Abreviado Laboral | Aprobada | Redirección, serie `CdgT-001-2026`, PDF y resumen sin opciones exclusivas de LOSEP. |
| Automatización documental | Aprobada | Fecha institucional y responsable de elaboración tomados de la sesión. |
| Acción LOSEP | Aprobada | `MP-002-2026`, PDF completo, aplicación laboral e historial. |
| Rechazo | Aprobada | `RD-001-2026` quedó anulada con motivo auditado mediante diálogo accesible. |
| Varios correos | Aprobada como constancia | Dos destinatarios sintéticos validados y almacenados; SMTP real no se intentó sin relay autorizado. |
| Documento firmado | Aprobada | PDF sintético versión 1, documento 17, visible desde el historial integral. |
| Historial integral | Aprobada | Muestra acciones aprobadas/anuladas, cambio laboral y documento firmado. |
| Nómina/DataTables | Aprobada | Búsqueda por cédula devuelve un registro; selector 10/25/50/100 y paginación al pie. |
| Claro/oscuro | Aprobada para Administrador | Inicio, Nómina, Socioeconómico, Vacaciones, Paz y Salvo, Biblioteca, Reportes, Auditoría y ambos formularios sin errores de consola. |
| Edición de borrador | Código corregido; reejecución pendiente | Se sustituyó el `UPDATE` directo por el procedimiento seguro incluido en la migración 20 aún no aplicada. |
| Rol Asistente | Automatizado aprobado; navegador pendiente | Existe cuenta nominativa, pero no se modificó su clave ni se usaron credenciales ajenas. |
| Limpieza UAT | Pendiente de DBA | Deben eliminarse de forma controlada los expedientes sintéticos, acciones 94–96, documento 17 y archivo privado; no se debe borrar la trazabilidad de auditoría. |

Para completar la aceptación: aplicar `2026.08.30.1` con una cuenta SQL DBA válida, ejecutar la compuerta completa, repetir la edición del borrador, realizar el recorrido con credenciales nominativas de Asistente y ejecutar la limpieza UAT mediante una transacción DBA verificada.

## Alcance excluido

No se acepta todavía como funcional Asistencia, Ausencias, Desempeño o Capacitación. La página 4 socioeconómica debe validarse con mapa, coordenadas, referencia y QR antes de cada entrega.
