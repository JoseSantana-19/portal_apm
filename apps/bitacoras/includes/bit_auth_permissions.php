<?php
require_once __DIR__ . '/bit_auth_session.php';
require_once __DIR__ . '/bit_config_constants.php';

require_once __DIR__ . '/../modules/Portuaria/models/Auth.php';

/**
 * apm_can_*() -- wrappers delgados sobre Auth::canXxx(), que a su vez
 * consulta fn_TienePermisoNodo real (id_modulo=13). Antes cada una de
 * estas comparaba $_SESSION['apm_auth']['nom_departa'] contra strings
 * literales por igualdad exacta, mientras que Auth::canXxx() (usado por
 * los controladores MVC) hacía la MISMA comparación pero por substring —
 * dos implementaciones vivas al mismo tiempo, capaces de divergir para
 * cualquier departamento cuyo nombre contuviera "ADMINISTRATIV" o
 * "SEGURIDAD" como substring sin ser un match exacto. Colapsar ambas en
 * una sola (permisos_centrales Fase 3, 2026-08-11) elimina la divergencia
 * en la raíz en vez de solo documentarla. Se conservan los NOMBRES de
 * función (usados por bit_sidebar.php y varias vistas) para no tener que
 * tocar cada punto de uso.
 */
function apm_can_acceder_dashboard_jefe()
{
    return Auth::canAccederDashboardJefe();
}

function apm_can_registrar_ingreso()
{
    return Auth::canRegistrarIngreso();
}

function apm_can_ver_listado_admin()
{
    return Auth::canVerListadoAdmin();
}

function apm_can_ver_bloque_admin()
{
    return apm_can_ver_listado_admin();
}

function apm_can_registrar_salida()
{
    return Auth::canRegistrarSalida();
}

function apm_can_editar_visita()
{
    return Auth::canEditarVisita();
}

function apm_can_gestionar_maestros_acceso()
{
    return Auth::canGestionarMaestrosAcceso();
}

function apm_can_editar_visita_desde_listado()
{
    return Auth::canEditarVisitaDesdeListado();
}

function apm_can_asignar_cedula_guest()
{
    return Auth::canAsignarCedulaGuest();
}

function apm_can_acceder_bitacora_rondas()
{
    return Auth::canAccederBitacoraRondas();
}

function apm_can_acceder_cctv()
{
    return Auth::canAccederCctv();
}

function apm_can_acceder_reporte_supervisor()
{
    return Auth::canAccederReporteSupervisor();
}

function apm_can_importar_funcionarios()
{
    return Auth::canImportarFuncionarios();
}

function apm_can_configurar_dias_bitacora()
{
    return Auth::canConfigurarDiasBitacora();
}

function apm_can_ver_catalogo_personas() { return Auth::canVerCatalogoPersonas(); }
function apm_can_ver_catalogo_empresas() { return Auth::canVerCatalogoEmpresas(); }
function apm_can_ver_catalogo_destinos() { return Auth::canVerCatalogoDestinos(); }
function apm_can_ver_catalogo_motivos() { return Auth::canVerCatalogoMotivos(); }
function apm_can_ver_catalogo_funcionarios() { return Auth::canVerCatalogoFuncionarios(); }
function apm_can_ver_catalogo_niveles_incidente() { return Auth::canVerCatalogoNivelesIncidente(); }
function apm_can_ver_cctv_inventario() { return Auth::canVerCctvInventario(); }
function apm_can_ver_cctv_motivos() { return Auth::canVerCctvMotivos(); }
function apm_can_ver_cctv_bitacora() { return Auth::canVerCctvBitacora(); }

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

