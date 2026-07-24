<?php
/**
 * ThHubController — Panel del módulo Talento Humano (hub nativo del shell).
 * GET /th — KPIs en vivo de la BD Talento_Humano + accesos a las secciones.
 * Mismo patrón que el Panel Portuario (/portuaria) y Panel de Bienes (/inventario/panel).
 */
class ThHubController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $m = new ThHubModel();

        $this->render('Talento_Humano/hub', [
            'pageTitle'     => 'Talento Humano — Panel',
            'stats'         => $m->stats(),
            'empleados'     => $m->ultimosEmpleados(6),
            'acciones'      => $m->ultimasAcciones(5),
            'chartUnidades' => $m->empleadosPorUnidad(6),
            'chartAcciones' => $m->accionesPorMes(6),
        ]);
    }
}
