<?php
// modules/talento-humano/Controladores/AsistenciaController.php
// Módulo: Asistencia y Turnos – Registro y cálculo de jornadas del personal

class AsistenciaController extends Controller
{
    /** GET /talento-humano/asistencia – Vista principal del módulo */
    public function index(): void
    {
        // Datos de demostración: registros de asistencia de la semana
        $registros = [
            [
                'empleado_id'    => 1,
                'cedula'         => '1312345678',
                'nombre'         => 'ZAMBRANO DELGADO HECTOR',
                'cargo'          => 'Jefe de Sistemas',
                'departamento'   => 'Tecnologías de la Información',
                'fecha'          => date('Y-m-d'),
                'hora_entrada'   => '08:02',
                'hora_salida'    => '17:05',
                'horas_trabajadas'=> 9.05,
                'horas_extras'   => 1.05,
                'atraso_min'     => 2,
                'estado'         => 'Normal',
            ],
            [
                'empleado_id'    => 2,
                'cedula'         => '1398765432',
                'nombre'         => 'PEREZ MORALES JUAN CARLOS',
                'cargo'          => 'Economista',
                'departamento'   => 'Financiero',
                'fecha'          => date('Y-m-d'),
                'hora_entrada'   => '08:45',
                'hora_salida'    => '17:00',
                'horas_trabajadas'=> 8.25,
                'horas_extras'   => 0,
                'atraso_min'     => 45,
                'estado'         => 'Atraso',
            ],
            [
                'empleado_id'    => 3,
                'cedula'         => '1312344567',
                'nombre'         => 'TORRES VEGA ANA MARIA',
                'cargo'          => 'Analista de RRHH',
                'departamento'   => 'Talento Humano',
                'fecha'          => date('Y-m-d'),
                'hora_entrada'   => null,
                'hora_salida'    => null,
                'horas_trabajadas'=> 0,
                'horas_extras'   => 0,
                'atraso_min'     => 0,
                'estado'         => 'Ausente',
            ],
            [
                'empleado_id'    => 4,
                'cedula'         => '1305671234',
                'nombre'         => 'PALMA TEJENA MICHAEL',
                'cargo'          => 'Supervisor',
                'departamento'   => 'Operaciones Portuarias',
                'fecha'          => date('Y-m-d'),
                'hora_entrada'   => '07:55',
                'hora_salida'    => '18:10',
                'horas_trabajadas'=> 10.25,
                'horas_extras'   => 2.25,
                'atraso_min'     => 0,
                'estado'         => 'Horas Extra',
            ],
        ];

        $resumen = [
            'total_registros' => count($registros),
            'presentes'       => count(array_filter($registros, fn($r) => !is_null($r['hora_entrada']) && $r['estado'] !== 'Ausente')),
            'ausentes'        => count(array_filter($registros, fn($r) => $r['estado'] === 'Ausente')),
            'atrasos'         => count(array_filter($registros, fn($r) => $r['atraso_min'] > 0)),
            'horas_extras'    => array_sum(array_column($registros, 'horas_extras')),
        ];

        $datos = [
            'registros' => $registros,
            'resumen'   => $resumen,
            'fecha_hoy' => date('d/m/Y'),
        ];
        $this->cargarVista('talento-humano', 'asistencia', $datos);
    }
}
