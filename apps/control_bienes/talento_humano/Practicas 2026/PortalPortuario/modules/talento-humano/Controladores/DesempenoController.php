<?php
// modules/talento-humano/Controladores/DesempenoController.php
// Módulo: Evaluación y Desempeño – Formularios de calificación del personal

class DesempenoController extends Controller
{
    /** GET /talento-humano/desempeno – Vista principal del módulo */
    public function index(): void
    {
        // Evaluaciones de desempeño registradas
        $evaluaciones = [
            [
                'id'              => 1,
                'nombre'          => 'ZAMBRANO DELGADO HECTOR',
                'cargo'           => 'Jefe de Sistemas',
                'departamento'    => 'Tecnologías de la Información',
                'periodo'         => '2025 - Anual',
                'calificacion'    => 94.5,
                'nivel'           => 'Excelente',
                'evaluador'       => 'Dir. Sistemas',
                'fecha'           => '2025-12-15',
                'estado'          => 'Completada',
                'objetivos_met'   => 9,
                'objetivos_total' => 10,
            ],
            [
                'id'              => 2,
                'nombre'          => 'PEREZ MORALES JUAN CARLOS',
                'cargo'           => 'Economista',
                'departamento'    => 'Financiero',
                'periodo'         => '2025 - Anual',
                'calificacion'    => 78.0,
                'nivel'           => 'Satisfactorio',
                'evaluador'       => 'Dir. Financiero',
                'fecha'           => '2025-12-20',
                'estado'          => 'Completada',
                'objetivos_met'   => 7,
                'objetivos_total' => 10,
            ],
            [
                'id'              => 3,
                'nombre'          => 'TORRES VEGA ANA MARIA',
                'cargo'           => 'Analista de RRHH',
                'departamento'    => 'Talento Humano',
                'periodo'         => '2026 - Semestral',
                'calificacion'    => null,
                'nivel'           => 'Pendiente',
                'evaluador'       => 'Dir. Talento Humano',
                'fecha'           => null,
                'estado'          => 'Pendiente',
                'objetivos_met'   => 0,
                'objetivos_total' => 8,
            ],
            [
                'id'              => 4,
                'nombre'          => 'PALMA TEJENA MICHAEL',
                'cargo'           => 'Supervisor',
                'departamento'    => 'Operaciones Portuarias',
                'periodo'         => '2026 - Semestral',
                'calificacion'    => null,
                'nivel'           => 'En proceso',
                'evaluador'       => 'Dir. Operaciones',
                'fecha'           => null,
                'estado'          => 'En proceso',
                'objetivos_met'   => 4,
                'objetivos_total' => 8,
            ],
        ];

        $resumen = [
            'completadas' => count(array_filter($evaluaciones, fn($e) => $e['estado'] === 'Completada')),
            'pendientes'  => count(array_filter($evaluaciones, fn($e) => $e['estado'] === 'Pendiente')),
            'en_proceso'  => count(array_filter($evaluaciones, fn($e) => $e['estado'] === 'En proceso')),
            'promedio'    => round(array_sum(array_filter(array_column($evaluaciones, 'calificacion'))) /
                             max(1, count(array_filter($evaluaciones, fn($e) => !is_null($e['calificacion'])))), 1),
        ];

        $datos = [
            'evaluaciones' => $evaluaciones,
            'resumen'      => $resumen,
        ];
        $this->cargarVista('talento-humano', 'desempeno', $datos);
    }
}
