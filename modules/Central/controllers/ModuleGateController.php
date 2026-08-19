<?php
/**
 * ModuleGateController — punto de paso obligatorio hacia los módulos
 * integrados (apps/talento_humano, apps/control_bienes, apps/bitacoras)
 * para los usuarios que tienen MFA activo. Los links del sidebar hacia
 * esos módulos pasan por acá (/ir?destino=...) en vez de apuntar directo
 * a la URL final -- así hay un solo lugar que decide "hace falta reconfirmar
 * el segundo factor" antes de dejar pasar, sin tocar el código de cada app
 * integrada (que es de otro equipo).
 *
 * "Reconfirmar" no es cada click: usa una ventana de frescura (Config
 * MFA_FRESCURA_SEGUNDOS, default 20 min) — un usuario que ya confirmó su
 * MFA hace 3 minutos puede seguir moviéndose entre módulos sin que le
 * vuelvan a pedir el código en cada uno; pasada la ventana, sí.
 */
class ModuleGateController extends Controller {

    private const FRESH_DEFAULT = 1200; // 20 min

    private function freshWindow(): int {
        try {
            $db  = Database::getInstance();
            $row = $db->fetch($db->query("SELECT valor FROM CORE_Config WHERE modulo='CORE' AND clave='MFA_FRESCURA_SEGUNDOS'"));
            if ($row && is_numeric($row['valor'])) return (int)$row['valor'];
        } catch (Throwable $e) {
            // usa el default
        }
        return self::FRESH_DEFAULT;
    }

    /** Único destino válido: una ruta relativa que empiece con apps/ (nunca una URL externa). */
    private function destinoSeguro(): ?string {
        $destino = (string)($_GET['destino'] ?? $_POST['destino'] ?? '');
        $destino = ltrim($destino, '/');
        if ($destino === '' || !str_starts_with($destino, 'apps/') || str_contains($destino, '://')) {
            return null;
        }
        return $destino;
    }

    public function abrir(): void {
        $this->requireAuth();
        $destino = $this->destinoSeguro();
        if ($destino === null) {
            $this->redirect('/dashboard');
        }

        if (empty($_SESSION['_requiere_mfa'])) {
            $this->redirect('/' . $destino);
        }

        $verificadoEn = (int)($_SESSION['_mfa_verificado_en'] ?? 0);
        if ((time() - $verificadoEn) < $this->freshWindow()) {
            $this->redirect('/' . $destino);
        }

        $_SESSION['_mfa_gate_destino'] = $destino;
        $this->render('Central/mfa_gate', [
            'pageTitle' => 'Confirmar identidad',
            'error'     => SessionHelper::getFlash('mfa_gate_error'),
            'csrf'      => $this->csrfToken(),
        ], false);
    }

    public function verificar(): void {
        $this->requireAuth();
        $this->verifyCsrf();

        $destino = $_SESSION['_mfa_gate_destino'] ?? null;
        if (!$destino) {
            $this->redirect('/dashboard');
        }

        $codigo = trim($_POST['codigo'] ?? '');
        $db     = Database::getInstance();
        $row    = $db->fetch($db->query(
            'SELECT mfa_secreto, mfa_ultimo_paso FROM CORE_Usuarios WHERE id_usuario=?',
            [[(int)$_SESSION['user_id'], SQLSRV_PARAM_IN]]
        ));

        $matched = null;
        $ok = false;
        if ($row && !empty($row['mfa_secreto'])) {
            try {
                $secret   = MfaHelper::decryptSecret($row['mfa_secreto']);
                $lastStep = $row['mfa_ultimo_paso'] !== null ? (int)$row['mfa_ultimo_paso'] : null;
                $ok = MfaHelper::verify($secret, $codigo, $lastStep, $matched);
            } catch (Throwable $e) {
                $ok = false;
            }
        }

        if (!$ok) {
            SessionHelper::flash('mfa_gate_error', 'Código incorrecto. Intenta de nuevo.');
            $this->redirect('/ir?destino=' . rawurlencode($destino));
        }

        $db->query('UPDATE CORE_Usuarios SET mfa_ultimo_paso=? WHERE id_usuario=?', [
            [$matched, SQLSRV_PARAM_IN], [(int)$_SESSION['user_id'], SQLSRV_PARAM_IN],
        ]);

        $_SESSION['_mfa_verificado_en'] = time();
        unset($_SESSION['_mfa_gate_destino']);
        $this->redirect('/' . $destino);
    }
}
