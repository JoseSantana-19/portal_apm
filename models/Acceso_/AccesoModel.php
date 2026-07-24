<?php
/**
 * AccesoModel. Manages CRUD operations for users, roles, menus, and forms.
 * Integrates directly with the SQL Server relational schema of PORTAL_APM.
 * Uses the native sqlsrv driver.
 */

require_once dirname(__DIR__, 2) . '/core/Model.php';

class AccesoModel extends Model {
    
    /**
     * Get all users, their main department, and associated roles.
     */
    public function getUsuarios(): array {
        $sql = "SELECT U.id_usuario, U.nombre_usuario, U.nombre_completo, U.correo, 
                       U.cargo_institucional, U.telefono, U.activo, U.id_dep_principal,
                       DM.nombre_departamento AS departamento,
                       (SELECT STRING_AGG(G.nombre_grupo_rol, ' | ') 
                        FROM dbo.Usuarios_Grupos_Roles UG 
                        INNER JOIN dbo.Grupos_Roles G ON UG.id_grupo_rol = G.id_grupo_rol
                        WHERE UG.id_usuario = U.id_usuario AND UG.activo = 1) AS roles
                FROM dbo.Usuarios U
                LEFT JOIN dbo.Departamentos_Modulos DM ON U.id_dep_principal = DM.id_dep_modulo
                ORDER BY U.nombre_completo ASC";
        $stmt = $this->query($sql);
        $res = $stmt->fetchAll();
        $stmt->closeCursor();
        return $res;
    }

    /**
     * Get all active roles and their principal departments.
     */
    public function getGruposRoles(): array {
        $sql = "SELECT G.id_grupo_rol, G.nombre_grupo_rol, G.descripcion, G.id_dep_modulo, 
                       G.nivel_jerarquia, G.activo, DM.nombre_departamento AS departamento
                FROM dbo.Grupos_Roles G
                LEFT JOIN dbo.Departamentos_Modulos DM ON G.id_dep_modulo = DM.id_dep_modulo
                ORDER BY G.nombre_grupo_rol ASC";
        $stmt = $this->query($sql);
        $res = $stmt->fetchAll();
        $stmt->closeCursor();
        return $res;
    }

    /**
     * Get all active departments/modules.
     */
    public function getDepartamentosModulos(): array {
        $sql = "SELECT id_dep_modulo, nombre_departamento, codigo_depto, color_tema 
                FROM dbo.Departamentos_Modulos 
                WHERE habilitado = 1 
                ORDER BY nombre_departamento ASC";
        $stmt = $this->query($sql);
        $res = $stmt->fetchAll();
        $stmt->closeCursor();
        return $res;
    }

    /**
     * Get all registered menu options grouped by department/module.
     */
    public function getMenuOpciones(): array {
        $sql = "SELECT MO.id_menu_op, MO.descripcion_interfaz, MO.url_formulario, MO.opcion_nivel, 
                       MO.codigo_secuencial, DM.nombre_departamento AS modulo
                FROM dbo.Menu_Opciones MO
                INNER JOIN dbo.Departamentos_Modulos DM ON MO.id_dep_modulo = DM.id_dep_modulo
                WHERE MO.activo = 1
                ORDER BY DM.nombre_departamento ASC, MO.orden ASC";
        $stmt = $this->query($sql);
        $res = $stmt->fetchAll();
        $stmt->closeCursor();
        return $res;
    }

    /**
     * Get all active forms.
     */
    public function getFormularios(): array {
        $sql = "SELECT F.idform, F.nombre_formulario, F.descripcion, DM.nombre_departamento AS modulo
                FROM dbo.Formularios F
                LEFT JOIN dbo.Departamentos_Modulos DM ON F.id_dep_modulo = DM.id_dep_modulo
                WHERE F.activo = 1
                ORDER BY DM.nombre_departamento ASC, F.nombre_formulario ASC";
        $stmt = $this->query($sql);
        $res = $stmt->fetchAll();
        $stmt->closeCursor();
        return $res;
    }

    /**
     * Save or update a user and synchronize their roles/profiles.
     */
    public function saveUsuario(array $data): void {
        self::beginTransaction();
        try {
            $id = isset($data['id_usuario']) && $data['id_usuario'] !== '' ? (int)$data['id_usuario'] : null;
            $nombre_usuario = trim($data['nombre_usuario']);
            $nombre_completo = trim($data['nombre_completo']);
            $correo = trim($data['correo']);
            $cargo = trim($data['cargo_institucional'] ?? '');
            $telefono = trim($data['telefono'] ?? '');
            $id_dep = !empty($data['id_dep_principal']) ? (int)$data['id_dep_principal'] : null;
            $activo = isset($data['activo']) ? (int)$data['activo'] : 1;
            
            if ($id) {
                // Update
                $sql = "UPDATE dbo.Usuarios 
                        SET nombre_usuario = ?, nombre_completo = ?, correo = ?, 
                            cargo_institucional = ?, telefono = ?, id_dep_principal = ?, activo = ?
                        WHERE id_usuario = ?";
                $this->query($sql, [$nombre_usuario, $nombre_completo, $correo, $cargo, $telefono, $id_dep, $activo, $id]);
            } else {
                // Insert
                $pass = !empty($data['contrasena']) ? $data['contrasena'] : 'Cambiar2026!';
                $passHash = password_hash($pass, PASSWORD_BCRYPT);
                $sql = "INSERT INTO dbo.Usuarios (nombre_usuario, nombre_completo, correo, cargo_institucional, telefono, contrasena_hash, id_dep_principal, activo, req_cambio_pass) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
                $this->query($sql, [$nombre_usuario, $nombre_completo, $correo, $cargo, $telefono, $passHash, $id_dep, $activo]);
                $id = self::lastInsertId();
            }

            // Synchronize User Roles
            $this->query("DELETE FROM dbo.Usuarios_Grupos_Roles WHERE id_usuario = ?", [$id]);
            
            if (!empty($data['roles'])) {
                foreach ($data['roles'] as $rolId) {
                    $this->query("INSERT INTO dbo.Usuarios_Grupos_Roles (id_usuario, id_grupo_rol, activo) VALUES (?, ?, 1)", [$id, (int)$rolId]);
                }
            }
            self::commit();
        } catch (Exception $e) {
            self::rollback();
            throw $e;
        }
    }

    /**
     * Save or update a Group / Role.
     */
    public function saveRol(array $data): void {
        $id = isset($data['id_grupo_rol']) && $data['id_grupo_rol'] !== '' ? (int)$data['id_grupo_rol'] : null;
        $nombre = trim($data['nombre_grupo_rol']);
        $descripcion = trim($data['descripcion'] ?? '');
        $id_dep = !empty($data['id_dep_modulo']) ? (int)$data['id_dep_modulo'] : null;
        $jerarquia = isset($data['nivel_jerarquia']) ? (int)$data['nivel_jerarquia'] : 0;
        $activo = isset($data['activo']) ? (int)$data['activo'] : 1;

        if ($id) {
            $sql = "UPDATE dbo.Grupos_Roles 
                    SET nombre_grupo_rol = ?, descripcion = ?, id_dep_modulo = ?, nivel_jerarquia = ?, activo = ?
                    WHERE id_grupo_rol = ?";
            $this->query($sql, [$nombre, $descripcion, $id_dep, $jerarquia, $activo, $id]);
        } else {
            $sql = "INSERT INTO dbo.Grupos_Roles (nombre_grupo_rol, descripcion, id_dep_modulo, nivel_jerarquia, activo) 
                    VALUES (?, ?, ?, ?, ?)";
            $this->query($sql, [$nombre, $descripcion, $id_dep, $jerarquia, $activo]);
        }
    }

    /**
     * Get menu option permissions for a single role.
     */
    public function getPermisosMenuPorRol(int $id_grupo_rol): array {
        $sql = "SELECT id_menu_op, tipo_permiso 
                FROM dbo.Permisos_Grupos_Roles 
                WHERE id_grupo_rol = ? AND activo = 1";
        $stmt = $this->query($sql, [$id_grupo_rol]);
        $res = $stmt->fetchAll();
        $stmt->closeCursor();
        
        $mapped = [];
        foreach ($res as $row) {
            $mapped[$row['id_menu_op']] = (int)$row['tipo_permiso'];
        }
        return $mapped;
    }

    /**
     * Clear and save all menu permissions for a role.
     */
    public function savePermisosMenu(int $id_grupo_rol, array $permisos): void {
        self::beginTransaction();
        try {
            $this->query("DELETE FROM dbo.Permisos_Grupos_Roles WHERE id_grupo_rol = ?", [$id_grupo_rol]);
            
            foreach ($permisos as $menuId => $tipoPermiso) {
                $tipoPermiso = (int)$tipoPermiso;
                if ($tipoPermiso > 0) {
                    $this->query("INSERT INTO dbo.Permisos_Grupos_Roles (id_grupo_rol, id_menu_op, tipo_permiso, activo) 
                                  VALUES (?, ?, ?, 1)", [$id_grupo_rol, (int)$menuId, $tipoPermiso]);
                }
            }
            self::commit();
        } catch (Exception $e) {
            self::rollback();
            throw $e;
        }
    }

    /**
     * Get form permissions for a single role.
     */
    public function getPermisosFormularioPorRol(int $id_grupo_rol): array {
        $sql = "SELECT id_form, permiso 
                FROM dbo.Per_Formulario 
                WHERE id_grupo_rol = ? AND estado = 1";
        $stmt = $this->query($sql, [$id_grupo_rol]);
        $res = $stmt->fetchAll();
        $stmt->closeCursor();
        
        $mapped = [];
        foreach ($res as $row) {
            $mapped[$row['id_form']] = (int)$row['permiso'];
        }
        return $mapped;
    }

    /**
     * Clear and save all form permissions for a role.
     */
    public function savePermisosFormulario(int $id_grupo_rol, array $permisos): void {
        self::beginTransaction();
        try {
            $this->query("DELETE FROM dbo.Per_Formulario WHERE id_grupo_rol = ?", [$id_grupo_rol]);
            
            foreach ($permisos as $formId => $nivelPermiso) {
                $nivelPermiso = (int)$nivelPermiso;
                if ($nivelPermiso > 0) {
                    $this->query("INSERT INTO dbo.Per_Formulario (id_grupo_rol, id_form, permiso, estado) 
                                  VALUES (?, ?, ?, 1)", [$id_grupo_rol, (int)$formId, $nivelPermiso]);
                }
            }
            self::commit();
        } catch (Exception $e) {
            self::rollback();
            throw $e;
        }
    }
}
