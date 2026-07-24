<?php
// modules/auditoria/Controladores/AuditoriaController.php
// Módulo: Auditoría y Control – Reportes generales y Logs de actividad

class AuditoriaController extends Controller
{
    /** GET /reportes – Reportes generales jerárquicos con exportación */
    public function reportes(): void
    {
        // Agrupación jerárquica simulada: Procesos Gobernantes, Sustantivos, Adjetivos
        $grupos = [
            [
                'tipo'   => 'Procesos Gobernantes',
                'color'  => 'primary',
                'icono'  => 'bi-building',
                'areas'  => [
                    ['nombre' => 'Dirección General',            'empleados' => 3,  'activos' => 3, 'contratos' => ['Nombramiento' => 3]],
                    ['nombre' => 'Asesoría Jurídica',            'empleados' => 5,  'activos' => 4, 'contratos' => ['Nombramiento' => 4, 'Contrato' => 1]],
                    ['nombre' => 'Planificación Institucional',  'empleados' => 4,  'activos' => 4, 'contratos' => ['Nombramiento' => 2, 'Contrato' => 2]],
                ],
            ],
            [
                'tipo'   => 'Procesos Sustantivos',
                'color'  => 'success',
                'icono'  => 'bi-anchor',
                'areas'  => [
                    ['nombre' => 'Operaciones Portuarias',       'empleados' => 28, 'activos' => 25, 'contratos' => ['Nombramiento' => 15, 'Contrato' => 13]],
                    ['nombre' => 'Comercialización y Servicios', 'empleados' => 12, 'activos' => 11, 'contratos' => ['Nombramiento' => 8, 'Contrato' => 4]],
                    ['nombre' => 'Seguridad Portuaria',          'empleados' => 18, 'activos' => 18, 'contratos' => ['Nombramiento' => 10, 'Contrato' => 8]],
                ],
            ],
            [
                'tipo'   => 'Procesos Adjetivos',
                'color'  => 'info',
                'icono'  => 'bi-briefcase',
                'areas'  => [
                    ['nombre' => 'Financiero',                   'empleados' => 9,  'activos' => 9,  'contratos' => ['Nombramiento' => 7, 'Contrato' => 2]],
                    ['nombre' => 'Talento Humano',               'empleados' => 7,  'activos' => 6,  'contratos' => ['Nombramiento' => 5, 'Contrato' => 2]],
                    ['nombre' => 'Tecnologías de la Información','empleados' => 6,  'activos' => 6,  'contratos' => ['Nombramiento' => 3, 'Contrato' => 3]],
                    ['nombre' => 'Comunicación Social',          'empleados' => 4,  'activos' => 3,  'contratos' => ['Nombramiento' => 2, 'Contrato' => 2]],
                ],
            ],
        ];

        $totales = [
            'empleados' => array_sum(array_map(fn($g) => array_sum(array_column($g['areas'], 'empleados')), $grupos)),
            'activos'   => array_sum(array_map(fn($g) => array_sum(array_column($g['areas'], 'activos')),   $grupos)),
        ];

        $datos = [
            'grupos'  => $grupos,
            'totales' => $totales,
        ];
        $this->cargarVista('auditoria', 'reportes', $datos);
    }

    /** GET /auditoria/logs – Logs de actividad del sistema (solo lectura, Admin) */
    public function logs(): void
    {
        $registros = [
            ['id' => 1, 'fecha' => '2026-05-30 11:42:03', 'usuario' => 'admin.th',   'ip' => '192.168.1.105', 'accion' => 'INSERT',  'modulo' => 'Empleados', 'descripcion' => 'Nuevo expediente creado: García LÓPEZ ROBERTO',     'nivel' => 'info'],
            ['id' => 2, 'fecha' => '2026-05-30 10:15:22', 'usuario' => 'a.torres',   'ip' => '192.168.1.112', 'accion' => 'UPDATE',  'modulo' => 'Empleados', 'descripcion' => 'Expediente modificado ID #14: cargo actualizado',     'nivel' => 'info'],
            ['id' => 3, 'fecha' => '2026-05-30 09:58:44', 'usuario' => 'j.perez',    'ip' => '192.168.1.108', 'accion' => 'LOGIN',   'modulo' => 'Sistema',   'descripcion' => 'Inicio de sesión exitoso',                           'nivel' => 'success'],
            ['id' => 4, 'fecha' => '2026-05-30 09:30:11', 'usuario' => 'desconocido','ip' => '45.32.111.200', 'accion' => 'LOGIN',   'modulo' => 'Sistema',   'descripcion' => 'Intento de login fallido: usuario no encontrado',    'nivel' => 'danger'],
            ['id' => 5, 'fecha' => '2026-05-29 17:22:35', 'usuario' => 'admin.th',   'ip' => '192.168.1.105', 'accion' => 'DELETE',  'modulo' => 'Empleados', 'descripcion' => 'Expediente eliminado ID #7: baja voluntaria',        'nivel' => 'warning'],
            ['id' => 6, 'fecha' => '2026-05-29 16:10:08', 'usuario' => 'admin.th',   'ip' => '192.168.1.105', 'accion' => 'EXPORT',  'modulo' => 'Reportes',  'descripcion' => 'Reporte PDF exportado: Personal por procesos 2026', 'nivel' => 'info'],
            ['id' => 7, 'fecha' => '2026-05-29 14:50:27', 'usuario' => 'a.torres',   'ip' => '192.168.1.112', 'accion' => 'UPDATE',  'modulo' => 'Vacaciones', 'descripcion' => 'Solicitud ID #2 aprobada: TORRES ANA MARIA',       'nivel' => 'success'],
            ['id' => 8, 'fecha' => '2026-05-28 11:05:19', 'usuario' => 'superadmin', 'ip' => '127.0.0.1',     'accion' => 'ROLE',    'modulo' => 'Admin',     'descripcion' => 'Rol actualizado: usuario j.perez → Consultor',       'nivel' => 'warning'],
        ];

        $resumen = [
            'total_hoy'    => count(array_filter($registros, fn($r) => str_starts_with($r['fecha'], date('Y-m-d')))),
            'alertas'      => count(array_filter($registros, fn($r) => in_array($r['nivel'], ['danger', 'warning']))),
            'usuarios_activos' => count(array_unique(array_column($registros, 'usuario'))),
        ];

        $datos = [
            'registros' => $registros,
            'resumen'   => $resumen,
        ];
        $this->cargarVista('auditoria', 'logs', $datos);
    }
}
