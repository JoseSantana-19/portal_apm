# Ejecución final SQL (entrega)

Se consolidó la instalación en **5 scripts maestros autocontenidos** dentro de `sql/`.

## Requisito

- Abrir en **SQL Server Management Studio** y ejecutar normalmente (sin activar opciones especiales).

## Orden de ejecución

1. `sql/01_DATABASE_BASE.sql`
2. `sql/02_DATABASE_ESTRUCTURA.sql`
3. `sql/03_DATABASE_LOGICA_TRIGGERS.sql`
4. `sql/04_DATABASE_AUTH.sql`
5. `sql/05_DATABASE_DATOS_MAESTROS.sql`

## Qué cubre

- Creación/preparación de base.
- Estructura completa de tablas y relaciones.
- Funciones y triggers (`fn_turno_por_hora`, `trg_visitas_sync_totales`, `trg_novedades_turno`, `trg_rondas_sync_totales`).
- Ajuste de movimientos para referencias `[REF:V...]` en dashboard.
- Seguridad/auth (`departamentos`, `usuarios_apm`, fixes de `id_departamento`).
- Datos maestros y configuración de sistema (`parametro` con `dias_edicion`).

## Nota


