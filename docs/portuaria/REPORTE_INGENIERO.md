# Portuaria DemoV4 — Avance de migración a MVC

**Fecha:** 10/07/2026 · **Para:** Ing. Fernando Zambrano

## Resumen

Se está migrando el sistema de archivos PHP sueltos a una arquitectura MVC propia (sin frameworks de terceros), manteniendo el sistema funcional durante todo el proceso. Avance actual: **Credenciales, Catálogos, Visitas, Cámaras, Rondas y Dashboard Ejecutivo migrados y funcionando; Talento Humano y Roles/Permisos pendientes.**

## Completado

| Módulo | Estado |
|---|---|
| Credenciales (login, logout, registro, cambio de clave) | ✅ Migrado y archivos viejos eliminados |
| Catálogos (personas, empresas, destinos, motivos, funcionarios, niveles) | ✅ Migrado y archivos viejos eliminados |
| Visitas (registro, listado, actualización, detalle) | ✅ Migrado y verificado |
| Cámaras (bitácora, motivos, inventario) | ✅ Migrado, verificado y 100% limpio (sin archivos viejos) |
| Rondas (bitácora de guardias) | ✅ Migrado y verificado, incluida la API interna |
| Dashboard Ejecutivo (Python/Streamlit) | ✅ Con los KPIs pedidos (visitas, género, incidentes críticos, top personas/destinos, demanda mensual, funcionario top), integrado al menú vía iframe, conexión configurable (ya no hardcodeada a una máquina) |
| Header configurable (logo o imagen, un solo archivo) | ✅ Ya existía, confirmado funcionando |
| Header fijo con contenido debajo | ✅ Ya existía, confirmado funcionando |
| Restructuración de carpetas (assets a `public/`) | ✅ Completa |
| Bug de estilos no cargando | ✅ Corregido y verificado |

Durante el proceso se corrigieron los archivos ya migrados que seguían apuntando a páginas antiguas (links de menú, redirecciones de sesión y de permisos) y se eliminaron **21 archivos** que ya no cumplían ninguna función (código muerto y páginas 100% reemplazadas). La lógica de negocio más sensible (cálculo de turnos y ventanas horarias de Rondas) se verificó con pruebas unitarias automatizadas.

## Pendiente

| Ítem | Detalle |
|---|---|
| Talento Humano | No iniciado (hoy vive dentro de Catálogos) |
| Control de Bines | No iniciado |
| Roles y Permisos | Hoy son reglas fijas en código; falta UI de administración |
| Diccionario de datos | No iniciado |
| Manual de usuario | No iniciado |
| KPI "Fechas de ingreso de autoridades" | El sistema no distingue una autoridad de un visitante común — falta definir el criterio (¿motivo específico? ¿campo nuevo en Personas?) antes de poder armarlo |
| Instalar el Dashboard como servicio real de Windows | Ya está listo el paso a paso en `analytics/README.md` (con NSSM); falta ejecutarlo en el servidor de producción |

## Nota técnica

Todo lo migrado se probó de forma automatizada en un entorno de pruebas. Las partes que requieren conexión a la base de datos real (SQL Server) quedan pendientes de confirmación en el servidor de Laragon, ya que este entorno de desarrollo no tiene acceso a esa base.
