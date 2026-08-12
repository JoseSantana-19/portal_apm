<?php

final class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) {
            header('Location: ' . BASE_URL . '/talento-humano/inicio');
            exit;
        }
        if(Auth::mfaPending()){
            header('Location: '.BASE_URL.'/login/verificar');
            exit;
        }
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        $this->render('core/Vistas/login', ['error' => $error]);
    }

    public function authenticate(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
        Auth::requireCsrf($_POST['_csrf'] ?? null);
        if (Auth::attempt((string)($_POST['usuario'] ?? ''), (string)($_POST['clave'] ?? ''))) {
            header('Location: ' . BASE_URL . '/talento-humano/inicio');
            exit;
        }
        if(Auth::mfaPending()){
            header('Location: '.BASE_URL.'/login/verificar');
            exit;
        }
        $_SESSION['login_error'] = 'Usuario o clave incorrectos.';
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    public function mfaForm(): void
    {
        if(!Auth::mfaPending()){header('Location: '.BASE_URL.'/login');exit;}
        $error=$_SESSION['mfa_error']??null;unset($_SESSION['mfa_error']);
        $this->render('core/Vistas/verificar_mfa',['error'=>$error]);
    }

    public function verifyMfa(): void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit('Método no permitido.');}
        Auth::requireCsrf($_POST['_csrf']??null);$result=Auth::verifyMfa((string)($_POST['codigo']??''));
        if($result['success']){header('Location: '.BASE_URL.'/talento-humano/inicio');exit;}
        $_SESSION['mfa_error']=$result['message'];
        header('Location: '.BASE_URL.(Auth::mfaPending()?'/login/verificar':'/login'));exit;
    }

    public function cancelMfa(): void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit('Método no permitido.');}
        Auth::requireCsrf($_POST['_csrf']??null);Auth::cancelMfa();header('Location: '.BASE_URL.'/login');exit;
    }

    public function securityForm(): void
    {
        $status=Auth::mfaStatus();$enrollment=Auth::pendingEnrollment();
        $mensaje=$_SESSION['security_message']??null;$error=$_SESSION['security_error']??null;
        unset($_SESSION['security_message'],$_SESSION['security_error']);
        $this->render('core/Vistas/seguridad_cuenta',compact('status','enrollment','mensaje','error'));
    }

    public function prepareMfa(): void
    {
        Auth::requireCsrf($_POST['_csrf']??null);Auth::prepareMfaEnrollment();header('Location: '.BASE_URL.'/cuenta/seguridad');exit;
    }

    public function activateMfa(): void
    {
        Auth::requireCsrf($_POST['_csrf']??null);$r=Auth::activateMfa((string)($_POST['codigo']??''));
        $_SESSION[$r['success']?'security_message':'security_error']=$r['message'];header('Location: '.BASE_URL.'/cuenta/seguridad');exit;
    }

    public function disableMfa(): void
    {
        Auth::requireCsrf($_POST['_csrf']??null);$r=Auth::disableMfa((string)($_POST['clave']??''),(string)($_POST['codigo']??''));
        $_SESSION[$r['success']?'security_message':'security_error']=$r['message'];header('Location: '.BASE_URL.'/cuenta/seguridad');exit;
    }

    public function renewSession(): void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit;}
        Auth::requireCsrf($_SERVER['HTTP_X_CSRF_TOKEN']??($_POST['_csrf']??null));
        header('Content-Type: application/json; charset=UTF-8');
        if(!Auth::renewSession(($_POST['manual']??'0')==='1')){http_response_code(401);echo json_encode(['ok'=>false]);return;}
        echo json_encode(['ok'=>true,'expires_in'=>Auth::idleTtl()]);
    }

    public function expireSession(): void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit;}
        Auth::requireCsrf($_POST['_csrf']??null);Auth::expireForInactivity();header('Location: '.BASE_URL.'/login?expired=1');exit;
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Metodo no permitido.');
        }
        Auth::requireCsrf($_POST['_csrf'] ?? null);
        Auth::logout();
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    public function changePasswordForm(): void
    {
        $error=$_SESSION['password_error']??null;unset($_SESSION['password_error']);
        $this->render('core/Vistas/cambiar_clave',['error'=>$error]);
    }

    public function changePassword(): void
    {
        if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit('Metodo no permitido.');}
        Auth::requireCsrf($_POST['_csrf']??null);
        $new=(string)($_POST['nueva_clave']??'');$confirm=(string)($_POST['confirmar_clave']??'');
        if(!hash_equals($new,$confirm))$result=['success'=>false,'message'=>'La confirmación no coincide.'];
        else $result=Auth::changePassword((string)($_POST['clave_actual']??''),$new);
        if(!$result['success']){$_SESSION['password_error']=$result['message'];header('Location: '.BASE_URL.'/cuenta/cambiar-clave');exit;}
        header('Location: '.BASE_URL.'/talento-humano/inicio?ok=1&msg='.urlencode($result['message']));exit;
    }
}
