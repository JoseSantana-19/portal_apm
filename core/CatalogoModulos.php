<?php
/**
 * CatalogoModulos — fuente única de la lista de módulos del portal
 * (CORE_Modulos), reemplaza los arrays PHP antes duplicados en
 * MenuController::MODULES y AdminController::moduleMeta().
 */
class CatalogoModulos {
    private static ?array $cache = null;

    public static function todos(): array {
        if (self::$cache !== null) return self::$cache;
        $db = Database::getInstance();
        $rows = $db->fetchAll($db->query(
            'SELECT id_modulo, codigo, nombre, icono, color, tipo, base_url, conexion_bd
             FROM CORE_Modulos WHERE estado=1 ORDER BY orden'
        ));
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['id_modulo']] = [
                'label'       => $r['nombre'],
                'icon'        => $r['icono'],
                'color'       => $r['color'],
                'tipo'        => $r['tipo'],
                'base_url'    => $r['base_url'],
                'conexion_bd' => $r['conexion_bd'],
            ];
        }
        return self::$cache = $out;
    }

    public static function meta(int $idModulo): array {
        return self::todos()[$idModulo]
            ?? ['label' => "Módulo $idModulo", 'icon' => 'fa-folder', 'color' => '#6c757d', 'tipo' => 'nativo', 'base_url' => null, 'conexion_bd' => null];
    }
}
