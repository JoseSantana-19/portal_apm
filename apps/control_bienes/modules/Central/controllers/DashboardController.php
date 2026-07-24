<?php
/**
 * DashboardController.php - Controlador del Módulo Central (Dashboard)
 */

class DashboardController extends Controller {
    public function index() {
        $this->redirect('inventario');
    }
}
