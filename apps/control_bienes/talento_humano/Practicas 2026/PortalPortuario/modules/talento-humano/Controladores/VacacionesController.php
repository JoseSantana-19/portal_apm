<?php
// modules/talento-humano/Controladores/VacacionesController.php
// Módulo: Vacaciones y Ausencias – Portal de solicitudes y saldo vacacional

class VacacionesController extends Controller
{
    /** GET /talento-humano/vacaciones – Vista principal del módulo */
    public function index(): void
    {
        // Solicitudes de vacaciones / ausencias registradas
        $solicitudes = [
            [
                'id'              => 1,
                'empleado_id'     => 2,
                'nombre'          => 'PEREZ MORALES JUAN CARLOS',
                'cargo'           => 'Economista',
                'departamento'    => 'Financiero',
                'tipo'            => 'Vacaciones',
                'fecha_inicio'    => '2026-06-10',
                'fecha_fin'       => '2026-06-20',
                'dias_solicitados'=> 10,
                'motivo'          => 'Vacaciones anuales planificadas',
                'estado'          => 'Pendiente',
                'fecha_solicitud' => '2026-05-28',
                'aprobado_por'    => null,
            ],
            [
                'id'              => 2,
                'empleado_id'     => 3,
                'nombre'          => 'TORRES VEGA ANA MARIA',
                'cargo'           => 'Analista de RRHH',
                'departamento'    => 'Talento Humano',
                'tipo'            => 'Permiso Médico',
                'fecha_inicio'    => '2026-05-30',
                'fecha_fin'       => '2026-05-31',
                'dias_solicitados'=> 2,
                'motivo'          => 'Cita médica especialidad',
                'estado'          => 'Aprobada',
                'fecha_solicitud' => '2026-05-29',
                'aprobado_por'    => 'Director Talento Humano',
            ],
            [
                'id'              => 3,
                'empleado_id'     => 4,
                'nombre'          => 'PALMA TEJENA MICHAEL',
                'cargo'           => 'Supervisor',
                'departamento'    => 'Operaciones Portuarias',
                'tipo'            => 'Vacaciones',
                'fecha_inicio'    => '2026-07-01',
                'fecha_fin'       => '2026-07-15',
                'dias_solicitados'=> 15,
                'motivo'          => 'Período vacacional semestral',
                'estado'          => 'Rechazada',
                'fecha_solicitud' => '2026-05-20',
                'aprobado_por'    => 'Director Operaciones',
            ],
        ];

        // Saldo vacacional por empleado
        $saldos = [
            ['nombre' => 'ZAMBRANO DELGADO HECTOR',   'dias_acumulados' => 30, 'dias_usados' => 5,  'dias_disponibles' => 25],
            ['nombre' => 'PEREZ MORALES JUAN CARLOS',  'dias_acumulados' => 22, 'dias_usados' => 0,  'dias_disponibles' => 22],
            ['nombre' => 'TORRES VEGA ANA MARIA',       'dias_acumulados' => 18, 'dias_usados' => 4,  'dias_disponibles' => 14],
            ['nombre' => 'PALMA TEJENA MICHAEL',        'dias_acumulados' => 26, 'dias_usados' => 10, 'dias_disponibles' => 16],
        ];

        $datos = [
            'solicitudes'    => $solicitudes,
            'saldos'         => $saldos,
            'total_pendientes' => count(array_filter($solicitudes, fn($s) => $s['estado'] === 'Pendiente')),
            'total_aprobadas'  => count(array_filter($solicitudes, fn($s) => $s['estado'] === 'Aprobada')),
        ];
        $this->cargarVista('talento-humano', 'vacaciones', $datos);
    }
}
