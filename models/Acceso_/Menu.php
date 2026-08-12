<?php
/**
 * Menu Model. Builds the 4-level MOIS navigation tree for a given user.
 * sp_GetMenuUsuario returns (id_nodo, id_modulo, opcion, items, subitems,
 *   descripcion, url_ruta, icono, orden, target_spa, nivel_crud).
 * Hierarchy: id_modulo header → opcion header → items → subitems (children).
 */

require_once dirname(__DIR__, 2) . '/core/Model.php';

class Menu extends Model {

    /**
     * Build the 4-level MOIS tree from sp_GetMenuUsuario for the given user.
     * Returns: $modules[$id_modulo]['areas'][$opcion]['items'][$items]['children'][]
     */
    public function getUserMenu(int $userId): array {
        try {
            $stmt = $this->query('{CALL sp_GetMenuUsuario(?)}', [[$userId, SQLSRV_PARAM_IN]]);
            $rows = $this->fetchAll($stmt);
            $this->free($stmt);

            $modules = [];

            foreach ($rows as $row) {
                $mod   = (int)$row['id_modulo'];
                $op    = (int)$row['opcion'];
                $it    = (int)$row['items'];
                $sub   = (int)$row['subitems'];
                $label = (string)$row['descripcion'];
                $url   = $row['url_ruta'] ?? null;
                $icon  = $row['icono']    ?? 'fa-circle';
                $spa   = (bool)($row['target_spa'] ?? true);
                $crud  = (int)($row['nivel_crud']  ?? 1);

                if ($op === 0 && $it === 0 && $sub === 0) {
                    // Level 1 — module header
                    if (!isset($modules[$mod])) {
                        $modules[$mod] = ['id' => $mod, 'label' => $label, 'icon' => $icon, 'areas' => []];
                    }
                } elseif ($it === 0 && $sub === 0) {
                    // Level 2 — area/section header
                    $this->ensureModule($modules, $mod);
                    if (!isset($modules[$mod]['areas'][$op])) {
                        $modules[$mod]['areas'][$op] = ['id' => $op, 'label' => $label, 'icon' => $icon, 'items' => []];
                    }
                    // Nodos de opción con URL propia (TH/Bienes/Bitácoras,
                    // Fase 1-3 2026-08-11: cada pantalla real es un nodo
                    // "opcion" plano con items=0, porque fn_TienePermisoNodo
                    // solo distingue por id_modulo+opcion+items+subitems y
                    // esos 3 módulos no necesitan más niveles) también se
                    // registran como ítem clickeable de su propia área — sin
                    // esto, la limpieza de "módulos sin contenido navegable"
                    // de más abajo los descarta enteros, aunque el usuario sí
                    // tenga el permiso real. Se ve reflejado en producción:
                    // el módulo completo desaparecía del sidebar.
                    if ($url !== null && $url !== '') {
                        $modules[$mod]['areas'][$op]['items'][0] = [
                            'id' => 0, 'label' => $label, 'url' => $url, 'icon' => $icon,
                            'spa' => $spa, 'crud' => $crud, 'children' => [],
                        ];
                    }
                } elseif ($sub === 0) {
                    // Level 3 — menu item
                    $this->ensureModule($modules, $mod);
                    $this->ensureArea($modules, $mod, $op);
                    $modules[$mod]['areas'][$op]['items'][$it] = [
                        'id'       => $it,
                        'label'    => $label,
                        'url'      => $url,
                        'icon'     => $icon,
                        'spa'      => $spa,
                        'crud'     => $crud,
                        'children' => [],
                    ];
                } else {
                    // Level 4 — submenu child
                    $this->ensureModule($modules, $mod);
                    $this->ensureArea($modules, $mod, $op);
                    $this->ensureItem($modules, $mod, $op, $it);
                    $modules[$mod]['areas'][$op]['items'][$it]['children'][] = [
                        'id'    => $sub,
                        'label' => $label,
                        'url'   => $url,
                        'icon'  => $icon,
                        'spa'   => $spa,
                        'crud'  => $crud,
                    ];
                }
            }

            // Remove modules/areas with no navigable content
            foreach ($modules as $mk => $m) {
                foreach ($m['areas'] as $ak => $a) {
                    if (empty($a['items'])) {
                        unset($modules[$mk]['areas'][$ak]);
                    }
                }
                if (empty($modules[$mk]['areas'])) {
                    unset($modules[$mk]);
                }
            }

            // El auto-registro de arriba (items[0] = la propia área) evita que
            // un área de pantalla única desaparezca del sidebar -- pero si esa
            // misma área YA tiene ítems reales propios (ej. "Registros Base"
            // con 6 catálogos reales debajo), items[0] queda como una fila
            // duplicada con el mismo nombre que el encabezado del área. Si uno
            // de esos ítems reales apunta a la MISMA url que el auto-registro
            // (patrón: se agregó a propósito un ítem real con el nombre
            // correcto para esa pantalla, ej. "Ver todo (catálogos)"), el
            // auto-registro es redundante -- se descarta y queda solo el
            // ítem real, correctamente nombrado.
            foreach ($modules as $mk => $m) {
                foreach ($m['areas'] as $ak => $a) {
                    if (!isset($a['items'][0]) || count($a['items']) <= 1) continue;
                    $urlRaiz = $a['items'][0]['url'] ?? null;
                    if ($urlRaiz === null) continue;
                    foreach ($a['items'] as $itId => $it) {
                        if ($itId !== 0 && ($it['url'] ?? null) === $urlRaiz) {
                            unset($modules[$mk]['areas'][$ak]['items'][0]);
                            break;
                        }
                    }
                }
            }

            return $modules;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Check if user has permission for a MOIS node via fn_TienePermisoNodo.
     */
    public function hasPermission(int $userId, int $idModulo, int $opcion = 0, int $items = 0, int $subitems = 0, int $nivelMin = 1): bool {
        try {
            $stmt = $this->query(
                "SELECT dbo.fn_TienePermisoNodo(?,?,?,?,?,?,1) AS tiene_permiso",
                [
                    [$userId,   SQLSRV_PARAM_IN],
                    [$idModulo, SQLSRV_PARAM_IN],
                    [$opcion,   SQLSRV_PARAM_IN],
                    [$items,    SQLSRV_PARAM_IN],
                    [$subitems, SQLSRV_PARAM_IN],
                    [$nivelMin, SQLSRV_PARAM_IN],
                ]
            );
            $res = $this->fetch($stmt);
            $this->free($stmt);
            return (bool)($res['tiene_permiso'] ?? false);
        } catch (Exception $e) {
            return false;
        }
    }

    private function ensureModule(array &$modules, int $mod): void {
        if (!isset($modules[$mod])) {
            $modules[$mod] = ['id' => $mod, 'label' => "Módulo $mod", 'icon' => 'fa-folder', 'areas' => []];
        }
    }

    private function ensureArea(array &$modules, int $mod, int $op): void {
        if (!isset($modules[$mod]['areas'][$op])) {
            $modules[$mod]['areas'][$op] = ['id' => $op, 'label' => "Área $op", 'icon' => 'fa-folder', 'items' => []];
        }
    }

    private function ensureItem(array &$modules, int $mod, int $op, int $it): void {
        if (!isset($modules[$mod]['areas'][$op]['items'][$it])) {
            $modules[$mod]['areas'][$op]['items'][$it] = [
                'id' => $it, 'label' => "Item $it", 'url' => null,
                'icon' => 'fa-circle', 'spa' => true, 'crud' => 1, 'children' => [],
            ];
        }
    }
}
