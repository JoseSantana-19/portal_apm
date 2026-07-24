<?php
/**
 * ThDesempenoController — Evaluación y Desempeño.
 * DEMO: datos de muestra (aún sin tabla en la BD). Fase siguiente: evaluaciones reales.
 */
class ThDesempenoController extends Controller
{
    public function __construct() { parent::__construct(); }

    public function index(): void
    {
        $this->requireAuth();
        $evaluaciones = [
            ['nombre'=>'ZAMBRANO DELGADO HECTOR','cargo'=>'Jefe de Sistemas','departamento'=>'Tecnologías de la Información','periodo'=>'2025 - Anual','calificacion'=>94.5,'nivel'=>'Excelente','evaluador'=>'Dir. Sistemas','fecha'=>'2025-12-15','estado'=>'Completada','objetivos_met'=>9,'objetivos_total'=>10],
            ['nombre'=>'PEREZ MORALES JUAN CARLOS','cargo'=>'Economista','departamento'=>'Financiero','periodo'=>'2025 - Anual','calificacion'=>78.0,'nivel'=>'Satisfactorio','evaluador'=>'Dir. Financiero','fecha'=>'2025-12-20','estado'=>'Completada','objetivos_met'=>7,'objetivos_total'=>10],
            ['nombre'=>'TORRES VEGA ANA MARIA','cargo'=>'Analista de RRHH','departamento'=>'Talento Humano','periodo'=>'2026 - Semestral','calificacion'=>null,'nivel'=>'Pendiente','evaluador'=>'Dir. Talento Humano','fecha'=>null,'estado'=>'Pendiente','objetivos_met'=>0,'objetivos_total'=>8],
            ['nombre'=>'PALMA TEJENA MICHAEL','cargo'=>'Supervisor','departamento'=>'Operaciones Portuarias','periodo'=>'2026 - Semestral','calificacion'=>null,'nivel'=>'En proceso','evaluador'=>'Dir. Operaciones','fecha'=>null,'estado'=>'En proceso','objetivos_met'=>4,'objetivos_total'=>8],
        ];
        $conCalif = array_filter(array_column($evaluaciones, 'calificacion'));
        $resumen = [
            'completadas' => count(array_filter($evaluaciones, fn($e) => $e['estado'] === 'Completada')),
            'pendientes'  => count(array_filter($evaluaciones, fn($e) => $e['estado'] === 'Pendiente')),
            'en_proceso'  => count(array_filter($evaluaciones, fn($e) => $e['estado'] === 'En proceso')),
            'promedio'    => round(array_sum($conCalif) / max(1, count($conCalif)), 1),
        ];
        $this->render('Talento_Humano/desempeno', [
            'pageTitle' => 'Evaluación y Desempeño',
            'evaluaciones' => $evaluaciones, 'resumen' => $resumen,
        ]);
    }
}
