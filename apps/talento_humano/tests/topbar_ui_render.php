<?php
declare(strict_types=1);
require dirname(__DIR__,2) . '/helpers/polyfills_php74.php';

define('ROOT', dirname(__DIR__));
define('BASE_URL', '');
define('IMG_URL', '/public/img');
require_once ROOT.'/core/Config.php';
require_once ROOT.'/core/InstitutionalClock.php';

final class Auth
{
    public static function user(): array
    {
        return ['sub'=>7, 'name'=>'Analista APM', 'role'=>'Talento Humano'];
    }

    public static function csrfToken(): string { return 'csrf-topbar-test'; }
}

final class TopbarService
{
    public static function context(array $user): array
    {
        return [
            'name'=>'María Prueba',
            'role'=>'Analista de Talento Humano',
            'email'=>'maria@apm.gob.ec',
            'identification'=>'1300000001',
            'photo'=>'public/img/default_avatar.png',
            'initials'=>'MP',
            'notifications'=>[
                ['tone'=>'warning','icon'=>'bi-shield-exclamation','title'=>'Proteja su cuenta','text'=>'Active el doble factor.','url'=>'/cuenta/seguridad'],
                ['tone'=>'info','icon'=>'bi-cake2-fill','title'=>'Cumpleaños de hoy','text'=>'2 funcionarios cumplen años hoy.','url'=>'/talento-humano/inicio#seccion-cumpleanos'],
            ],
        ];
    }
}

ob_start();
require ROOT.'/shared/topbar.php';
$html=(string)ob_get_clean();

$failures=[];
$assert=static function(bool $condition,string $message)use(&$failures):void{if(!$condition)$failures[]=$message;};
$assert(str_contains($html,'id="themeToggle"'),'Falta el selector de modo claro/oscuro.');
$assert(str_contains($html,'id="notificationToggle"')&&str_contains($html,'id="notificationPanel"'),'Falta el centro de notificaciones.');
$assert(str_contains($html,'topbar-notification-badge')&&str_contains($html,'>2</span>'),'El contador de notificaciones no refleja los avisos.');
$assert(str_contains($html,'id="profileToggle"')&&str_contains($html,'id="profilePanel"'),'Falta el menú de perfil.');
$assert(str_contains($html,'Foto de María Prueba')&&str_contains($html,'/talento-humano/empleado/perfil/1300000001'),'La imagen o el enlace al perfil institucional no se renderiza.');
$assert(str_contains($html,'global-search-form')&&str_contains($html,'Buscar personal en la plataforma'),'El buscador superior no está disponible.');
$assert(str_contains($html,'action="/logout"')&&str_contains($html,'csrf-topbar-test'),'El cierre de sesión no mantiene protección CSRF.');

$head=(string)file_get_contents(ROOT.'/shared/head_assets.php');
$script=(string)file_get_contents(ROOT.'/public/js/topbar.js');
$css=(string)file_get_contents(ROOT.'/public/css/topbar-enhanced.css');
$theme=(string)file_get_contents(ROOT.'/public/css/theme.css');
$assert(str_contains($head,"localStorage.getItem('apm.theme')")&&str_contains($head,'theme.css'),'La carga inicial del tema no evita el parpadeo visual.');
$assert(str_contains($script,"localStorage.setItem('apm.theme'")&&str_contains($script,"event.key !== 'Escape'"),'El tema o la interacción accesible de los paneles está incompleta.');
$assert(str_contains($css,'.global-search-form {')&&str_contains($css,'position: relative;'),'La lupa no está anclada al campo de búsqueda.');
$assert(str_contains($theme,'html[data-theme="dark"] .topbar')&&str_contains($theme,'html[data-theme="dark"] input'),'El tema oscuro no cubre encabezado y formularios.');

$viewFiles=array_merge(
    glob(ROOT.'/modules/*/Vistas/*.php') ?: [],
    glob(ROOT.'/core/Vistas/*.php') ?: []
);
foreach($viewFiles as $viewFile){
    $viewSource=(string)file_get_contents($viewFile);
    if(!str_contains($viewSource,'shared/topbar.php')) continue;
    $relative=str_replace(ROOT.DIRECTORY_SEPARATOR,'',$viewFile);
    $assert(str_contains($viewSource,'shared/head_assets.php'),"{$relative} usa el encabezado sin cargar sus estilos compartidos.");
    $assert(str_contains($viewSource,'shared/footer_scripts.php'),"{$relative} usa el encabezado sin cargar su comportamiento compartido.");
}

if($failures){foreach($failures as $failure)fwrite(STDERR,"[FAIL] {$failure}\n");exit(1);}
echo "[OK] topbar con tema, notificaciones, perfil y búsqueda alineada\n";
