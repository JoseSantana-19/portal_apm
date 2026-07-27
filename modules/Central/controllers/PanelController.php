<?php
/**
 * PanelController — panel nativo (KPIs en vivo, dentro del shell del portal)
 * de un módulo integrado, antes de abrir su sistema completo externo.
 * Mismo rol que PortalPortuariaController@hub cumple para Portuaria.
 */
class PanelController extends Controller {

    private function model(): PanelModel {
        return new PanelModel();
    }

    /** GET /panel/talento-humano */
    public function talentoHumano(): void {
        $this->requireAuth();
        $this->render('Central/panel/talento_humano', [
            'pageTitle' => 'Panel — Talento Humano',
            'kpis'      => $this->model()->getKpisTH(),
        ]);
    }

    /** GET /panel/bienes */
    public function bienes(): void {
        $this->requireAuth();
        $this->render('Central/panel/bienes', [
            'pageTitle' => 'Panel — Control de Bienes',
            'kpis'      => $this->model()->getKpisBienes(),
        ]);
    }
}
