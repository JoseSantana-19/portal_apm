<?php
/**
 * PortalPortuariaController — Vistas NATIVAS del módulo Portuaria dentro del
 * shell del portal (temas t1/t2/t3, sidebar SPA).
 *
 * Es la "cara" del módulo en el portal principal:
 *   GET /portuaria                  → hub: KPIs en vivo + accesos a todo el módulo
 *   GET /portuaria/visitas-resumen  → vista rápida de visitas (filtros, solo lectura)
 *   GET /portuaria/actividad        → actividad de seguridad (rondas del día + CCTV)
 *
 * La operación completa (registrar, editar, DataTables, exportaciones) vive en
 * el módulo con layout propio (/visitas, /rondas, /camaras, /catalogos…).
 */
class PortalPortuariaController extends Controller
{
    private function model(): PortDashboardModel
    {
        return new PortDashboardModel();
    }

    public function hub(): void
    {
        $this->requireAuth();
        Auth::hydrateFromPortal();

        $m = $this->model();

        $this->render('Portuaria/portal/hub', [
            'pageTitle'     => 'Portuaria — Control de Acceso',
            'stats'         => $m->statsHub(),
            'ultimas'       => $m->ultimasVisitas(6),
            'chartDias'     => $m->visitasPorDia(7),
            'chartDestinos' => $m->visitasPorDestino(6),
        ]);
    }

    public function visitasResumen(): void
    {
        $this->requireAuth();
        Auth::hydrateFromPortal();

        $fecha = trim((string)$this->input('fecha', 'get', ''));
        $q     = trim((string)$this->input('q', 'get', ''));

        // Sin filtros: mostrar las más recientes (sin limitar a hoy)
        $visitas = $this->model()->visitasFiltradas($fecha, $q, 150);

        $this->render('Portuaria/portal/visitas_resumen', [
            'pageTitle' => 'Visitas — Vista Rápida',
            'visitas'   => $visitas,
            'fecha'     => $fecha,
            'q'         => $q,
        ]);
    }

    public function actividad(): void
    {
        $this->requireAuth();
        Auth::hydrateFromPortal();

        $fecha = trim((string)$this->input('fecha', 'get', ''));
        $m = $this->model();

        $this->render('Portuaria/portal/actividad', [
            'pageTitle' => 'Actividad de Seguridad',
            'fecha'     => ($fecha !== '') ? $fecha : date('Y-m-d'),
            'rondas'    => $m->rondasDelDia($fecha),
            'cctv'      => $m->actividadCamaras(15),
            'kpis'      => $m->kpisJefe(),
        ]);
    }
}
