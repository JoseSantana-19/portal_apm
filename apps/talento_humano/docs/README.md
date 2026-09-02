# Documentación del Portal Portuario APM

Este índice distingue las guías vigentes de los cierres históricos. Las instrucciones operativas deben tomarse únicamente de la sección **Documentación vigente**.

## Documentación vigente

| Área | Documento | Propósito |
|---|---|---|
| Estado | [Estado del proyecto](ESTADO_PROYECTO.md) | Alcance culminado, exclusiones y pasos externos pendientes. |
| Uso | [Manual de usuario](MANUAL_USUARIO.md) | Operación diaria por rol y flujos completos del portal. |
| Datos | [Diccionario de datos](DICCIONARIO_DATOS.md) | Tablas, claves, dominios, relaciones, vistas y conservación. |
| Desarrollo local | [Entorno local IIS](ENTORNO_LOCAL_IIS.md) | Reproducir IIS, PHP 8.5, HTTPS y SQL cifrado. |
| Instalación | [Equipo nuevo](INSTALACION_NUEVO_EQUIPO.md) | Descargas, versiones, dependencias y orden completo para reproducir el portal. |
| Despliegue | [Despliegue seguro](DESPLIEGUE_PRODUCCION.md) | Preparar un ambiente definitivo sin reutilizar secretos locales. |
| Incidencias | [Incidencias y soluciones](INCIDENCIAS_Y_SOLUCIONES.md) | Errores conocidos, causa, corrección y validación. |
| Base de datos | [Migraciones](../database/README.md) | Orden de SQL, administración y recuperación. |
| Seguridad | [Seguridad y auditoría](SEGURIDAD_Y_AUDITORIA.md) | MFA, sesiones, RBAC, bitácora y reportes. |
| Formularios | [Formatos oficiales](FORMATOS_IMPRESION_OFICIALES.md) | Alcance, rutas y verificación de PDF. |
| Formularios | [Matriz de campos](MATRIZ_FORMATOS_OFICIALES.md) | Correspondencia entre campos, tablas y salida impresa. |
| Operación | [Respaldos y recuperación](RESPALDOS_Y_RECUPERACION.md) | Trabajos SQL Agent, controles y restauración. |
| Operación | [Monitoreo local](OPERACION_LOCAL.md) | Salud diaria, alertas locales y retención segura. |
| Operación | [Talento Humano integral](OPERACION_TALENTO_HUMANO.md) | Series, vacaciones, reingresos, hitos, género y Paz y Salvo. |
| Calidad | [Pruebas y aceptación](PRUEBAS_Y_ACEPTACION.md) | Evidencia UAT, E2E, PDF y comandos repetibles. |

## Historial

Los archivos de [`historico/`](historico/) registran hitos anteriores. Se conservan para trazabilidad y no reemplazan las guías vigentes:

- [Mejoras críticas 2026](historico/MEJORAS_CRITICAS_2026.md)
- [Cierre técnico del 06-08-2026](historico/CIERRE_TECNICO_20260806.md)
- [Cierre de seguridad y auditoría del 10-08-2026](historico/SEGURIDAD_AUDITORIA_20260810.md)

## Regla de mantenimiento

- Actualizar primero el documento vigente relacionado.
- Registrar fallos reproducibles en `INCIDENCIAS_Y_SOLUCIONES.md`.
- Mover cierres fechados a `historico/`.
- No documentar credenciales, huellas activas, datos personales ni rutas de respaldos con información sensible.
- Comprobar enlaces y ejecutar `git diff --check` antes de confirmar cambios.

## Convención de nombres

- Las guías vigentes usan nombres descriptivos sin fecha.
- Los cierres históricos incluyen la fecha `AAAAMMDD`.
- Los scripts ejecutables pertenecen a `deployment/`, `scripts/` o `database/`, no a `docs/`.
- Las evidencias temporales y archivos generados no se versionan.
