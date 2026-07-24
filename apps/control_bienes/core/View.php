<?php
/**
 * VIEW.PHP - Compilador y Renderizador de Vistas
 * Provee un método unificado para renderizar plantillas PHP limpias.
 */

class View {
    /**
     * Renderiza una vista inyectando los datos provistos y retorna la salida como string.
     * @param string $viewPath Ruta absoluta al archivo de vista
     * @param array $data Variables locales para la vista
     * @return string Contenido HTML renderizado
     */
    public static function render(string $viewPath, array $data = []): string {
        if (!file_exists($viewPath)) {
            return "<div class='panel'><h3 style='color:var(--danger);'>Error: La vista no existe en {$viewPath}.</h3></div>";
        }
        
        extract($data);
        ob_start();
        require $viewPath;
        return ob_get_clean();
    }
}
