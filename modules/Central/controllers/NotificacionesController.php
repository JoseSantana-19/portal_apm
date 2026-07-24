<?php
class NotificacionesController extends Controller {

    public function index(): void {
        $this->requireAuth();
        $id    = (int)($_SESSION['user_id'] ?? 0);
        $db    = Database::getInstance();
        $notifs = $db->fetchAll($db->query(
            'SELECT TOP(50) id_notif, titulo, mensaje, tipo, prioridad, leida, url_accion, fecha_creacion
             FROM CORE_Notificaciones
             WHERE id_usuario=? OR id_usuario IS NULL
             ORDER BY leida ASC, fecha_creacion DESC',
            [[$id, SQLSRV_PARAM_IN]]
        ));
        $this->render('Central/notificaciones/index', [
            'pageTitle' => 'Notificaciones',
            'notifs'    => $notifs,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function recientes(): void {
        $this->requireAuth();
        $id   = (int)($_SESSION['user_id'] ?? 0);
        $stmt = (new DashboardModel())->getAlertasPendientes(20);

        $items = array_map(fn($n) => [
            'id_notificacion' => $n['id_notif'],
            'titulo'          => $n['titulo'],
            'mensaje'         => $n['mensaje'],
            'tipo'            => $n['tipo'] ?? 'info',
            'leida'           => false,
            'icono'           => 'fa-solid fa-bell',
            'url_accion'      => $n['url_accion'] ?? null,
            'fecha_relativa'  => $this->relativa($n['fecha_creacion']),
        ], $stmt);

        $this->json(['ok' => true, 'items' => $items]);
    }

    public function marcarLeidas(): void {
        $this->requireAuth();
        $id = (int)($_SESSION['user_id'] ?? 0);
        $db = Database::getInstance();
        $db->query(
            'UPDATE CORE_Notificaciones SET leida=1 WHERE id_usuario=? AND leida=0',
            [[$id, SQLSRV_PARAM_IN]]
        );
        $this->json(['ok' => true]);
    }

    private function relativa(mixed $fecha): string {
        if ($fecha instanceof DateTime) { $ts = $fecha->getTimestamp(); }
        elseif (is_string($fecha) && $fecha) { $ts = strtotime($fecha); }
        else { return '—'; }

        $diff = time() - $ts;
        if ($diff < 60)   return 'hace un momento';
        if ($diff < 3600) return 'hace ' . (int)($diff / 60) . ' min';
        if ($diff < 86400) return 'hace ' . (int)($diff / 3600) . ' h';
        return date('d/m/Y', $ts);
    }
}
