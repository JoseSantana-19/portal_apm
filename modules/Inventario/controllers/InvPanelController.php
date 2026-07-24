<?php
/**
 * InvPanelController — Panel del módulo Control de Bienes (hub nativo del shell).
 * GET /inventario/panel — KPIs en vivo de la BD inventario + accesos a secciones.
 * Mismo patrón que el Panel Portuario (/portuaria) y el Panel TH (/th).
 */
class InvPanelController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $m = new InvPanelModel();

        $this->render('Inventario/hub', [
            'pageTitle'       => 'Control de Bienes — Panel',
            'stats'           => $m->stats(),
            'bienes'          => $m->ultimosBienes(6),
            'chartCategorias' => $m->bienesPorCategoria(6),
            'chartZonas'      => $m->bienesPorZona(6),
        ]);
    }
}
