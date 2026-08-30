<?php
// core/Controller.php – Clase base; provee render() y cargarVista()

class Controller
{
    /**
     * Carga una vista extrayendo el array $datos como variables locales.
     * @param string $vista  Ruta relativa a ROOT sin extensión .php
     * @param array  $datos  Variables disponibles dentro de la vista
     */
    protected function render(string $vista, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        $rutaVista = ROOT . '/' . ltrim($vista, '/') . '.php';

        if (!file_exists($rutaVista)) {
            throw new RuntimeException('La vista solicitada no está disponible.');
        }

        require_once $rutaVista;
    }

    /**
     * Carga una vista extrayendo el array $datos como variables locales.
     * @param string $modulo  Nombre del módulo
     * @param string $archivo Nombre del archivo de vista
     * @param array  $datos   Variables disponibles dentro de la vista
     */
    protected function cargarVista(string $modulo, string $archivo, array $datos = []): void
    {
        $ruta = "modules/{$modulo}/Vistas/{$archivo}";
        $this->render($ruta, $datos);
    }
}
