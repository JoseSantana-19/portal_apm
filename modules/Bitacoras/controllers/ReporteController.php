<?php
class ReporteController extends Controller {

    public function index(): void {
        $this->requireAuth();
        $db = Database::getInstance();

        $resumen = $db->fetchAll($db->query(
            "SELECT estado_evento AS estado, COUNT(*) AS total
             FROM BIT_Eventos
             GROUP BY estado_evento"
        ));
        $porCategoria = $db->fetchAll($db->query(
            "SELECT c.nombre AS categoria, COUNT(b.id_evento) AS total
             FROM BIT_Eventos b
             JOIN BIT_Categorias c ON c.id_categoria = b.id_categoria
             GROUP BY c.nombre
             ORDER BY total DESC"
        ));

        $this->render('Bitacoras/reportes/index', [
            'pageTitle'   => 'Reportes de Bitácoras',
            'resumen'     => $resumen,
            'porCategoria'=> $porCategoria,
        ]);
    }
}
