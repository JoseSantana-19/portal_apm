# Pendientes

Scripts sueltos movidos aquí desde la raíz del proyecto (2026-07-26). No están
ruteados en `routes.php` ni siguen la estructura MVC del portal (`modules/`) —
son páginas standalone de un módulo aparte, todavía sin terminar de definir.
Se dejaron sin tocar ni borrar a la espera de decidir qué hacer con ellos.

| Archivo | Qué es |
|---|---|
| `bit_consulta.php` | Detalle de un registro de bitácora (modal), enlazado desde el panel jefe |
| `bit_dashboard_jefe.php` | Panel estadístico para jefatura/gerencia, actualizado por polling |
| `bit_dashboard_permisos_demo.php` | Demo de permisos del dashboard |
| `bit_diagnostico_dbf.php` | Diagnóstico del archivo `rolmaes.DBF` (ruta, extensión, lectura) |
| `bit_reporte_diario_supervisor.php` | Reporte operativo diario del supervisor |
| `bit_test_conexiones.php` | Prueba de conexión a `PortuariaDemo` y `PortuariaExterna` |
| `bit_test_empresa_externa.php` | Prueba directa sobre `PortuariaExterna.dbo.reg_empresas` |
| `bit_test_rolmaes_dbf.php` | Prueba de lectura directa de `rolmaes.DBF` |

Son distintos de `modules/Portuaria` (Bitácoras, ya integrado y corriendo).
