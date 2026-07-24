<?php
$root = dirname(__DIR__);
require_once "{$root}/core/Model.php";

class SPAlterer extends Model {
    public function run() {
        $sql = "
        CREATE OR ALTER PROCEDURE dbo.sp_GetMenuUsuario
            @id_usuario   INT,
            @codigo_depto VARCHAR(30) = NULL
        AS
        BEGIN
            SET NOCOUNT ON;
            SELECT
                MO.id_menu_op, 
                MO.id_dep_modulo, 
                DM.codigo_depto, 
                DM.nombre_departamento, 
                DM.url_base, 
                DM.color_tema, 
                DM.icono_modulo, 
                DM.id_dep_padre, 
                DM.nivel_dep, 
                DM_PAD.nombre_departamento AS parent_nombre,
                DM_PAD.codigo_depto AS parent_codigo,
                DM_PAD.color_tema AS parent_color,
                DM_PAD.icono_modulo AS parent_icono,
                MO.descripcion_interfaz, 
                MO.url_formulario, 
                MO.icono, 
                MO.opcion_nivel, 
                MO.item_subnivel, 
                MO.orden, 
                MO.seccion, 
                MO.requiere_mfa,
                MAX(PG.tipo_permiso) AS max_permiso
            FROM dbo.Menu_Opciones MO
            INNER JOIN dbo.Departamentos_Modulos DM ON MO.id_dep_modulo = DM.id_dep_modulo
            LEFT JOIN dbo.Departamentos_Modulos DM_PAD ON DM.id_dep_padre = DM_PAD.id_dep_modulo
            INNER JOIN dbo.Permisos_Grupos_Roles PG  ON MO.id_menu_op    = PG.id_menu_op AND PG.activo = 1
            INNER JOIN dbo.Usuarios_Grupos_Roles UG  ON PG.id_grupo_rol  = UG.id_grupo_rol   AND UG.activo = 1
            WHERE UG.id_usuario = @id_usuario AND MO.activo = 1 AND MO.visible = 1 AND DM.habilitado = 1 AND (@codigo_depto IS NULL OR DM.codigo_depto = @codigo_depto)
            GROUP BY
                MO.id_menu_op, 
                MO.id_dep_modulo, 
                DM.codigo_depto, 
                DM.nombre_departamento, 
                DM.url_base, 
                DM.color_tema, 
                DM.icono_modulo, 
                DM.id_dep_padre, 
                DM.nivel_dep, 
                DM_PAD.nombre_departamento,
                DM_PAD.codigo_depto,
                DM_PAD.color_tema,
                DM_PAD.icono_modulo,
                MO.descripcion_interfaz, 
                MO.url_formulario, 
                MO.icono, 
                MO.opcion_nivel, 
                MO.item_subnivel, 
                MO.orden, 
                MO.seccion, 
                MO.requiere_mfa, 
                DM.orden_display
            ORDER BY DM.orden_display, MO.orden, MO.opcion_nivel;
        END;
        ";
        try {
            self::$db->exec($sql);
            echo "Successfully altered sp_GetMenuUsuario stored procedure!\n";
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
$a = new SPAlterer();
$a->run();
