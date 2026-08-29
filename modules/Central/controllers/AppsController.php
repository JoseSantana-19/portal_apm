<?php
/**
 * AppsController — Acceso robusto a los sistemas ORIGEN embebidos (apps/).
 *
 * En Apache las apps se sirven como directorios reales; este controller solo
 * captura los casos en que la petición llega al router del portal (p. ej.
 * /apps/talento_humano sin barra final desde un enlace viejo o navegación
 * SPA) y redirige a la URL correcta del sistema origen.
 */
class AppsController extends Controller
{
    private const APPS = [
        'talento_humano' => '/apps/talento_humano/',
        'control_bienes' => '/apps/control_bienes/',
        'bitacoras'      => '/apps/bitacoras/',
    ];

    public function abrir(string $app = ''): void
    {
        $this->requireAuth();

        $app = strtolower(trim($app));
        if (isset(self::APPS[$app])) {
            $this->redirect(self::APPS[$app]);
        }
        $this->redirect('/dashboard');
    }
}
