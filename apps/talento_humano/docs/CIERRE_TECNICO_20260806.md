# Cierre técnico del 06-08-2026

## Funcionalidad culminada

- guardado transaccional y validado del estudio socioeconómico;
- selección y precarga del funcionario, incluida la información de contacto de emergencia;
- conservación de los datos ingresados cuando el formulario devuelve un error;
- tres filas autorizadas para hijos, capacitaciones y experiencia laboral;
- PDF socioeconómico de cuatro páginas, con la cuarta hoja expresamente reservada hasta recibir el formato autorizado;
- ajuste multilínea de la experiencia laboral para impedir textos superpuestos;
- alta rápida y persistente de estructura organizacional y cargos;
- RBAC para usuarios, roles, documentos, maestros, estudios y acciones críticas;
- paginación y filtros de auditoría, más auditoría explícita de exportaciones;
- catálogo único de tipos de proceso;
- política de contraseñas fuertes y cabeceras de seguridad web;
- compensación de fotografías nuevas si falla el guardado y eliminación segura de la fotografía sustituida;
- respaldo completo, respaldo del log y trabajos automáticos de SQL Server;
- migraciones ordenadas y retiro de SQL históricos sustituidos y del CSV con datos personales.

## Datos de prueba controlados

El estudio socioeconómico `#2` pertenece al funcionario activo Michael Javier Palma Tejena (`empleado_id=1121`, identificación `1316312766`). Incluye el contacto Carmen Tejena, declaración de bienes, cuenta bancaria, formación, dos capacitaciones, experiencia laboral y vehículo indicados para la prueba. La creación quedó registrada en auditoría.

## Acciones externas necesarias antes de producción

1. Un administrador de Windows debe dejar `SQLSERVERAGENT` en estado `Running` e inicio `Automatic`; los trabajos ya están instalados y habilitados.
2. Infraestructura debe instalar/configurar un certificado TLS confiable para SQL Server. Después se cambia `PORTAL_DB_ENCRYPT=true` y se conserva `PORTAL_DB_TRUST_CERT=false`.
3. El administrador funcional debe crear cuentas nominativas para los operadores reales y desactivar el uso compartido de una cuenta administrativa.
4. Configurar alertas por correo y una copia cifrada externa para los respaldos.

La unidad histórica `GENERICO` quedó desactivada porque no contiene funcionarios activos; se conserva la referencia del expediente inactivo para no romper la trazabilidad.

Los módulos de asistencia, vacaciones, desempeño y capacitación permanecen como prototipos por decisión del proyecto. La página 4 oficial del socioeconómico también queda fuera del alcance hasta que la institución la entregue.
