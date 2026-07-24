<?php
/**
 * MonitoreoController — Movimientos de bodega (ingresos / egresos).
 * Estructura MVC nativa de portal_apm. Datos vía sqlsrv nativo (sin PDO).
 */
class MonitoreoController extends Controller
{
    private MonitoreoModel $mon;

    public function __construct()
    {
        $this->mon = new MonitoreoModel();
    }

    public function ingresos(): void
    {
        $this->requireAuth();
        $this->render('Inventario/ingresos', [
            'pageTitle' => 'Ingresos de Bodega',
            'ingresos'  => $this->mon->listarIngresos(),
            'stats'     => $this->mon->statsBodega(),
        ]);
    }

    public function egresos(): void
    {
        $this->requireAuth();
        $this->render('Inventario/egresos', [
            'pageTitle' => 'Egresos de Bodega',
            'egresos'   => $this->mon->listarEgresos(),
            'stats'     => $this->mon->statsBodega(),
        ]);
    }
}
