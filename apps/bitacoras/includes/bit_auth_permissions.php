<?php
require_once __DIR__ . '/bit_auth_session.php';
require_once __DIR__ . '/bit_config_constants.php';

function apm_normalize_text($value)
{
    $txt = trim((string)$value);
    $txt = mb_strtolower($txt, 'UTF-8');
    $txt = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
        ['a', 'e', 'i', 'o', 'u', 'n'],
        $txt
    );
    return preg_replace('/\s+/', ' ', $txt);
}

/**
 * Clave para comparar permisos: trim, espacios colapsados, mayúsculas (UTF-8).
 * Inmune a variaciones de formato respecto a datos de la vista / APM.
 */
function apm_departamento_cmp_key($value)
{
    $t = trim((string)$value);
    $t = preg_replace('/\s+/', ' ', $t);
    return mb_strtoupper($t, 'UTF-8');
}

function apm_current_area_key()
{
    $departamento = isset($_SESSION['apm_auth']['nom_departa'])
        ? $_SESSION['apm_auth']['nom_departa']
        : (
            isset($_SESSION['apm_auth']['DEP_NOMBRE'])
                ? $_SESSION['apm_auth']['DEP_NOMBRE']
                : (isset($_SESSION['apm_auth']['nombre_departamento']) ? $_SESSION['apm_auth']['nombre_departamento'] : '')
        );
    return apm_departamento_cmp_key($departamento);
}

function apm_current_departamento_id()
{
    return (int) ($_SESSION['apm_auth']['id_departamento'] ?? 0);
}

function apm_is_tecnologia_informacion()
{
    return apm_current_area_key() === 'TECNOLOGIA DE LA INFORMACION';
}

function apm_is_edificio_administrativo()
{
    return apm_current_area_key() === 'EDIFICIO ADMINISTRATIVO';
}

function apm_is_admin_area()
{
    // Acceso total para Tecnología de la Información y Edificio Administrativo.
    return apm_is_tecnologia_informacion() || apm_is_edificio_administrativo();
}

function apm_is_seguridad_operativa()
{
    $k = apm_current_area_key();
    // Seguridad Integral hereda los permisos operativos que antes tenía Garita.
    return $k === 'SEGURIDAD INTEGRAL';
}

function apm_is_talento_humano()
{
    return false;
}

function apm_is_jefe_area()
{
    return apm_is_tecnologia_informacion();
}

/** Gerencia (departamento ID 5 o nombre normalizado). */
function apm_is_gerencia()
{
    if (apm_current_departamento_id() === (int) ID_DEPARTAMENTO_GERENCIA) {
        return true;
    }
    return apm_current_area_key() === 'GERENCIA';
}

/**
 * Panel estadístico en tiempo real (dashboard jefe): solo TI o Gerencia.
 * No usar solo apm_is_admin_area() porque incluiría Edificio Administrativo.
 */
function apm_can_acceder_dashboard_jefe()
{
    $id = apm_current_departamento_id();
    if ($id === (int) ID_DEPARTAMENTO_ADMIN || $id === (int) ID_DEPARTAMENTO_GERENCIA) {
        return true;
    }
    return apm_is_tecnologia_informacion() || apm_is_gerencia();
}

function apm_can_registrar_ingreso()
{
    return apm_is_admin_area() || apm_is_seguridad_operativa();
}

function apm_can_ver_listado_admin()
{
    return apm_is_admin_area() || apm_is_seguridad_operativa() || apm_is_talento_humano();
}

function apm_can_ver_bloque_admin()
{
    return apm_can_ver_listado_admin();
}

function apm_can_registrar_salida()
{
    return apm_is_admin_area() || apm_is_seguridad_operativa();
}

/**
 * Gestionar catálogos, horas desde vistas administrativas, etc. Solo edificio administrativo.
 * (No confundir con el modal «Editar visita» en el listado, que también permite Garita.)
 */
function apm_can_editar_visita()
{
    return apm_is_admin_area();
}

/**
 * Gestión de maestros para control de accesos.
 * Se permite a Administración y Seguridad Integral porque estos catálogos
 * alimentan el registro de ingreso de visitas/proveedores.
 */
function apm_can_gestionar_maestros_acceso()
{
    return apm_is_admin_area() || apm_is_seguridad_operativa();
}

/** Modal «Editar visita» en listado_visitas: administración o seguridad operativa / garita. */
function apm_can_editar_visita_desde_listado()
{
    return apm_is_admin_area() || apm_is_seguridad_operativa();
}

/** Asignar cédula real a visitante Guest: solo administración del edificio. */
function apm_can_asignar_cedula_guest()
{
    return apm_is_admin_area();
}

/** Bitácora de rondas / reporte diario de protección (Seguridad Operativa / Garita o Administración). */
function apm_can_acceder_bitacora_rondas()
{
    return apm_is_admin_area() || apm_is_seguridad_operativa();
}

function apm_can_configurar_dias_bitacora()
{
    return apm_is_admin_area() || apm_is_jefe_area();
}

function apm_bitacora_guardia_dias_permitidos(?int $diasConfigurados = null): int
{
    $dias = $diasConfigurados !== null ? (int) $diasConfigurados : 1;
    $permitidos = [1, 3, 5, 7];
    if (!in_array($dias, $permitidos, true)) {
        return 1;
    }
    return $dias;
}

function apm_bitacora_dias_edicion_permitidos(?int $diasConfigurados = null): ?int
{
    if (apm_can_configurar_dias_bitacora()) {
        return null;
    }
    return apm_bitacora_guardia_dias_permitidos($diasConfigurados);
}

function apm_bitacora_fecha_minima_edicion(?DateTimeInterface $base = null, ?int $diasConfigurados = null): ?string
{
    $diasPermitidos = apm_bitacora_dias_edicion_permitidos($diasConfigurados);
    if ($diasPermitidos === null) {
        return null;
    }
    
    if ($base instanceof DateTimeInterface) {
        $ref = new DateTime($base->format('Y-m-d H:i:s'), $base->getTimezone());
    } else {
        $ref = new DateTime('now');
    }
    
    $ref->modify('-' . $diasPermitidos . ' days');
    return $ref->format('Y-m-d');
}

function apm_deny_json($message, $statusCode)
{
    http_response_code((int)$statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

