<?php
// includes/bit_dev_auto_login.php  (versión Portal APM — integración portuaria_demoV4)
//
// El auto-login de desarrollo del proyecto origen queda DESACTIVADO: en el
// portal integrador TODA autenticación pasa por /login (CORE_Usuarios + sp_Login).
//
// Este archivo ahora actúa como PUENTE de sesión: si el usuario ya inició
// sesión en el portal ($_SESSION['user_id']) construye la estructura
// $_SESSION['apm_auth'] que esperan las páginas/APIs legacy del módulo
// Portuaria, resolviendo cédula y departamento desde PORTAL_APM.
//
// Mapeo de áreas para la matriz de permisos legacy (bit_auth_permissions.php
// compara contra los nombres de departamento del proyecto origen):
//   nivel_jerarquia >= 3  → 'TECNOLOGIA DE LA INFORMACION'  (área admin total)
//   nivel_jerarquia == 2  → 'SEGURIDAD INTEGRAL'            (seguridad operativa)
//   otros                 → nombre real del departamento (por si coincide)
// El nombre real siempre queda disponible en apm_auth['nom_departa_real'].

$APM_DEV_SIN_LOGIN = false; // NO activar en el portal integrador.

if (empty($_SESSION['apm_auth']['id_usuario']) && !empty($_SESSION['user_id'])) {

    if (!defined('DB_SERVER')) {
        // Acceso directo (página/API legacy sin pasar por index.php del portal)
        require_once __DIR__ . '/../config/app.php';
    }

    $apmCedula = '';
    $apmDepReal = '';
    try {
        $opts = [
            'Database'               => DB_NAME,
            'CharacterSet'           => 'UTF-8',
            'ReturnDatesAsStrings'   => true,
            'TrustServerCertificate' => true,
            'Encrypt'                => defined('DB_ENCRYPT') ? DB_ENCRYPT : false,
        ];
        if (DB_USER !== '') { $opts['UID'] = DB_USER; $opts['PWD'] = DB_PASS; }
        $connCore = @sqlsrv_connect(DB_SERVER, $opts);
        if ($connCore !== false) {
            $st = sqlsrv_query(
                $connCore,
                'SELECT u.cedula, d.nombre AS nom_departa
                   FROM CORE_Usuarios u
              LEFT JOIN CORE_Departamentos d ON d.id_departamento = u.id_departamento
                  WHERE u.id_usuario = ?',
                [(int) $_SESSION['user_id']]
            );
            if ($st && ($row = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC))) {
                $apmCedula  = (string) ($row['cedula'] ?? '');
                $apmDepReal = (string) ($row['nom_departa'] ?? '');
            }
            if ($st) sqlsrv_free_stmt($st);
            sqlsrv_close($connCore);
        }
    } catch (Throwable $e) {
        // sin datos extra; el puente sigue con lo que hay en sesión
    }

    $nivel = (int) ($_SESSION['nivel_jerarquia'] ?? 0);
    $depUpper = mb_strtoupper($apmDepReal, 'UTF-8');
    if ($nivel >= 3) {
        $depLegacy = 'TECNOLOGIA DE LA INFORMACION';
    } elseif ($nivel === 2 || preg_match('/GARITA|CCTV|VIGILANCIA|SEGURID/u', $depUpper)) {
        // Operativos de seguridad (Garita de Acceso, Vigilancia CCTV…)
        $depLegacy = 'SEGURIDAD INTEGRAL';
    } else {
        $depLegacy = $apmDepReal;
    }

    $_SESSION['apm_auth'] = [
        'id_usuario'       => (int) $_SESSION['user_id'],
        'cedula'           => $apmCedula,
        'nombres'          => (string) ($_SESSION['nombre_completo'] ?? $_SESSION['nombre_usuario'] ?? ''),
        'id_departamento'  => (int) ($_SESSION['id_departamento'] ?? 0),
        'nom_departa'      => $depLegacy,
        'nom_departa_real' => $apmDepReal,
        'nivel_jerarquia'  => $nivel,
        'login_at'         => date('c'),
        'core_origen'      => 'portal_apm',
    ];
}
