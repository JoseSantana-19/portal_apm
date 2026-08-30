<?php
declare(strict_types=1);

final class DraftController extends Controller
{
    public function load(): void
    {
        $this->jsonHeaders();
        Auth::requireCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        try {
            $draft = DraftService::load((int)(Auth::user()['sub'] ?? 0), (string)($_GET['context'] ?? ''));
            echo json_encode(['success'=>true,'draft'=>$draft], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            Conexion::registrarErrorLog($e, 'Borradores', false);
            http_response_code(422); echo json_encode(['success'=>false,'message'=>'No fue posible recuperar el borrador.']);
        }
    }

    public function save(): void
    {
        $this->jsonHeaders();
        Auth::requireCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        try {
            $body = json_decode((string)file_get_contents('php://input'), true, 64, JSON_THROW_ON_ERROR);
            DraftService::save((int)(Auth::user()['sub'] ?? 0), (string)($body['context'] ?? ''), (array)($body['fields'] ?? []));
            echo json_encode(['success'=>true,'saved_at'=>InstitutionalClock::now()->format('H:i:s')]);
        } catch (Throwable $e) {
            Conexion::registrarErrorLog($e, 'Borradores', false);
            http_response_code(422); echo json_encode(['success'=>false,'message'=>'No fue posible guardar el borrador.']);
        }
    }

    public function delete(): void
    {
        $this->jsonHeaders();
        Auth::requireCsrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        try {
            $body = json_decode((string)file_get_contents('php://input'), true, 16, JSON_THROW_ON_ERROR);
            DraftService::delete((int)(Auth::user()['sub'] ?? 0), (string)($body['context'] ?? ''));
            echo json_encode(['success'=>true]);
        } catch (Throwable $e) {
            http_response_code(422); echo json_encode(['success'=>false]);
        }
    }

    private function jsonHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }
}
