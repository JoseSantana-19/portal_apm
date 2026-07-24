<?php
// modules/Talento_Humano/controllers/CapacitacionController.php
// Módulo: Capacitación y Desarrollo – Registro de cursos, talleres y certificaciones

class CapacitacionController extends Controller
{
    /** GET /talento_capacitacion – Vista principal del módulo */
    public function index()
    {
        $this->registrarAuditoria('ACCESO', 'th', 'Acceso al módulo de Capacitación y Desarrollo');
        
        $capacitaciones = [
            [
                'id'           => 1,
                'titulo'       => 'Administración Pública y Transparencia',
                'tipo'         => 'Taller',
                'institucion'  => 'Ministerio del Trabajo',
                'modalidad'    => 'Presencial',
                'fecha_inicio' => '2026-03-10',
                'fecha_fin'    => '2026-03-14',
                'duracion_h'   => 40,
                'participantes'=> ['ZAMBRANO DELGADO HECTOR', 'PEREZ MORALES JUAN CARLOS'],
                'certificados' => 2,
                'estado'       => 'Completado',
                'categoria'    => 'Gestión Pública',
            ],
            [
                'id'           => 2,
                'titulo'       => 'Seguridad Portuaria ISPS Code',
                'tipo'         => 'Certificación',
                'institucion'  => 'Autoridad Marítima',
                'modalidad'    => 'Presencial',
                'fecha_inicio' => '2026-04-05',
                'fecha_fin'    => '2026-04-09',
                'duracion_h'   => 60,
                'participantes'=> ['PALMA TEJENA MICHAEL'],
                'certificados' => 1,
                'estado'       => 'Completado',
                'categoria'    => 'Seguridad',
            ],
            [
                'id'           => 3,
                'titulo'       => 'Ofimática Avanzada y Office 365',
                'tipo'         => 'Curso',
                'institucion'  => 'SECAP',
                'modalidad'    => 'Virtual',
                'fecha_inicio' => '2026-05-15',
                'fecha_fin'    => '2026-06-15',
                'duracion_h'   => 80,
                'participantes'=> ['TORRES VEGA ANA MARIA', 'PEREZ MORALES JUAN CARLOS'],
                'certificados' => 0,
                'estado'       => 'En Curso',
                'categoria'    => 'Tecnología',
            ],
            [
                'id'           => 4,
                'titulo'       => 'Liderazgo y Gestión de Equipos',
                'tipo'         => 'Taller',
                'institucion'  => 'IAEN',
                'modalidad'    => 'Híbrido',
                'fecha_inicio' => '2026-07-01',
                'fecha_fin'    => '2026-07-05',
                'duracion_h'   => 32,
                'participantes'=> [],
                'certificados' => 0,
                'estado'       => 'Planificado',
                'categoria'    => 'Liderazgo',
            ],
        ];

        $resumen = [
            'total'       => count($capacitaciones),
            'completados' => count(array_filter($capacitaciones, fn($c) => $c['estado'] === 'Completado')),
            'en_curso'    => count(array_filter($capacitaciones, fn($c) => $c['estado'] === 'En Curso')),
            'planificados'=> count(array_filter($capacitaciones, fn($c) => $c['estado'] === 'Planificado')),
            'total_horas' => array_sum(array_column($capacitaciones, 'duracion_h')),
            'certificados'=> array_sum(array_column($capacitaciones, 'certificados')),
        ];

        $datos = [
            'capacitaciones' => $capacitaciones,
            'resumen'        => $resumen,
        ];
        $this->render('capacitacion', $datos, 'Capacitación y Desarrollo - SysPort');
    }
}
