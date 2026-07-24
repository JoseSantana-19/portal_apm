<?php
/**
 * Lector del archivo rolmaes.DBF (Visual FoxPro) para obtener funcionarios.
 * Solo se leen los campos necesarios (como si fuera otra fuente de datos); el resto del DBF se ignora.
 * Detecta automáticamente los nombres de columnas (p. ej. NUM_CEDUL, NOMBRE, CARGO, FEC_SALIDA) por coincidencia.
 * Activo = sin fecha de salida real. Valores como ####, ###*, vacío = activo.
 */

/**
 * Resuelve la ruta al archivo rolmaes.DBF (prueba varias ubicaciones y extensiones).
 * @return string|null Ruta absoluta al archivo o null si no existe.
 */
function _dbf_ruta_rolmaes($rutaDbf = null) {
    if ($rutaDbf !== null && $rutaDbf !== '' && is_file($rutaDbf)) {
        return $rutaDbf;
    }
    $candidatos = ['rolmaes.DBF', 'rolmaes.dbf', 'ROLMAES.DBF'];
    $bases = [
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'dbf' . DIRECTORY_SEPARATOR,
        (function_exists('getcwd') ? getcwd() . DIRECTORY_SEPARATOR : '') . 'dbf' . DIRECTORY_SEPARATOR,
    ];
    foreach ($bases as $base) {
        if ($base === '' || !is_dir($base)) continue;
        foreach ($candidatos as $nombre) {
            $ruta = $base . $nombre;
            if (is_file($ruta)) return $ruta;
        }
    }
    return null;
}

/**
 * Detecta qué claves del registro corresponden a cedula, nombre, cargo, DEPARTAMEN (código área), seccion y fecha_salida.
 * Las claves en DBF pueden tener espacios o nombres abreviados (p. ej. NUM_CEDUL, FEC_SALIDA).
 * DEPARTAMEN es el nombre alineado con dbo.bit_departamentos.DEPARTAMEN (origen FoxPro).
 * @return array ['cedula' => claveReal|null, 'nombre' => ..., 'cargo' => ..., 'DEPARTAMEN' => ..., 'seccion' => ..., 'fecha_salida' => ...]
 */
function _dbf_detectar_campos($row) {
    $out = ['cedula' => null, 'nombre' => null, 'cargo' => null, 'DEPARTAMEN' => null, 'seccion' => null, 'fecha_salida' => null];
    $keys = array_keys($row);

    // Prioridad explícita: si existe exactamente FEC_SALIDA, usar ese campo.
    foreach ($keys as $k0) {
        if (strtoupper(trim((string)$k0)) === 'FEC_SALIDA') {
            $out['fecha_salida'] = $k0;
            break;
        }
    }

    foreach ($keys as $k) {
        if ($k === 'deleted') continue;
        $u = strtoupper(trim($k));
        if ($u === '' || strlen($u) < 2) continue;
        if (($out['cedula'] === null) && (strpos($u, 'CEDUL') !== false || $u === 'CI' || $u === 'CEDULA')) $out['cedula'] = $k;
        if (($out['nombre'] === null) && (strpos($u, 'NOMBRE') !== false || $u === 'NOMBRES')) $out['nombre'] = $k;
        if (($out['cargo'] === null) && (strpos($u, 'CARGO') !== false || strpos($u, 'PUESTO') !== false)) $out['cargo'] = $k;
        if (($out['DEPARTAMEN'] === null) && ($u === 'DEPARTAMEN' || strpos($u, 'COD_DEP') !== false || strpos($u, 'DEPARTAMENTO') !== false || $u === 'DEPTO' || $u === 'DEPART')) {
            $out['DEPARTAMEN'] = $k;
        }
        if (($out['seccion'] === null) && (strpos($u, 'SECCION') !== false || $u === 'SECCION')) $out['seccion'] = $k;
        if (($out['fecha_salida'] === null) && (strpos($u, 'SALIDA') !== false || strpos($u, 'BAJA') !== false || strpos($u, 'FEC_SAL') !== false)) $out['fecha_salida'] = $k;
    }
    return $out;
}

/**
 * Obtiene el valor de un campo del registro DBF probando varios nombres posibles.
 */
function _dbf_campo($row, $nombres) {
    foreach ($nombres as $n) {
        if (isset($row[$n])) {
            $v = trim((string)$row[$n]);
            if ($v !== '') return $v;
        }
    }
    return '';
}

/** Valor de un campo por clave detectada o por lista de nombres. */
function _dbf_valor($row, $claveReal, $nombresFallback) {
    if ($claveReal !== null && isset($row[$claveReal])) {
        return trim((string)$row[$claveReal]);
    }
    return _dbf_campo($row, $nombresFallback);
}

/** Nombres posibles para el campo cédula en el DBF (FoxPro puede truncar a 10 chars: NUM_CEDUL). */
function _dbf_nombres_cedula() {
    return ['NUM_CEDULA', 'NUM_CEDUL', 'num_cedula', 'num_cedul', 'CEDULA', 'cedula', 'NUMCEDULA', 'numcedula', 'NUMERO_CEDULA', 'CI'];
}

/** Nombres posibles para nombre. */
function _dbf_nombres_nombre() {
    return ['NOMBRE', 'nombre', 'NOMBRES', 'nombres', 'NOMBRE_COMPLETO', 'NOMBRES_APELLIDOS', 'APELLIDOS_NOMBRES'];
}

/** Nombres posibles para cargo. */
function _dbf_nombres_cargo() {
    return ['CARGO', 'cargo', 'PUESTO', 'puesto', 'CARGO_DESC'];
}

/** Nombres posibles para código/nombre de departamento que viene del DBF. */
function _dbf_nombres_departamen() {
    return ['DEPARTAMEN', 'COD_DEPARTAMENTO', 'COD_DEPTO', 'COD_DEP', 'DEPARTAMENTO', 'DEPTO', 'DEPART', 'AREA', 'AREA_LABORAL'];
}

/** Nombres posibles para sección. */
function _dbf_nombres_seccion() {
    return ['SECCION', 'SECC', 'SECCION_LABORAL'];
}

/**
 * Indica si un valor del DBF parece una fecha REAL de salida (funcionario inactivo).
 * En FoxPro las fechas "vacías" NO son NULL; suelen ser: '', 0, 00000000, 0000-00-00,
 * 00/00/0000, ####, ###*, espacios, o fechas con solo ceros. Todas = ACTIVO (no tiene fecha).
 */
function _dbf_es_fecha_valida($v) {
    $v = trim((string)$v);
    if ($v === '') return false;
    if (preg_match('/^0+$/', $v)) return false;
    if (preg_match('/^0*\/0*\/0*$/', $v) || preg_match('/^0000\-00\-00$/', $v)) return false;
    if (preg_match('/^[\s#*\-\.\/]+$/u', $v) && !preg_match('/[1-9]/', $v)) return false;
    if (preg_match('/^#+$/', $v) || preg_match('/^#+\*?$/', $v) || preg_match('/^\*+$/', $v)) return false;
    if (strpos($v, '/') !== false || strpos($v, '-') !== false) {
        if (preg_match('/[1-9]\d{0,3}/', $v)) return true;
    }
    $digits = preg_replace('/[^0-9]/', '', $v);
    if (strlen($digits) >= 6 && preg_match('/[1-9]/', $digits)) return true;
    return false;
}

/**
 * Comprueba si el valor de fecha de salida indica "inactivo" (tiene fecha real).
 * Cualquier valor "vacío" o placeholder = ACTIVO (no tiene fecha de salida).
 */
function _dbf_es_fecha_salida_rellena($v) {
    $v = trim((string)$v);
    if ($v === '' || $v === '0' || $v === '00000000') return false;
    if (preg_match('/^0+$/', $v)) return false;
    if (preg_match('/^0*\/0*\/0*$/', $v) || $v === '00/00/0000' || $v === '0000-00-00') return false;
    if (preg_match('/^[\s#*\-\.\/]+$/u', $v) && !preg_match('/[1-9]/', $v)) return false;
    if (preg_match('/^#+$/', $v) || preg_match('/^#+\*?$/', $v) || preg_match('/^\*+$/', $v)) return false;
    return _dbf_es_fecha_valida($v);
}

/**
 * Comprueba si el registro tiene fecha de salida real (funcionario inactivo).
 * Si se pasa $claveFechaSalida (detectada), solo se mira esa columna.
 */
function _dbf_tiene_fecha_salida($row, $claveFechaSalida = null) {
    // Prioridad estricta al campo exacto solicitado.
    if (isset($row['FEC_SALIDA'])) {
        return _dbf_es_fecha_salida_rellena($row['FEC_SALIDA']);
    }
    if ($claveFechaSalida !== null && isset($row[$claveFechaSalida])) {
        return _dbf_es_fecha_salida_rellena($row[$claveFechaSalida]);
    }
    $nombres = ['FEC_SALIDA', 'FECHA_SALIDA', 'FECHASALIDA', 'FECHA_SAL', 'FEC_SAL', 'fecha_salida', 'fechasalida', 'fecha_sal', 'FECHA_BAJA', 'FECHABAJA', 'BAJA'];
    foreach ($nombres as $n) {
        if (isset($row[$n])) {
            if (_dbf_es_fecha_salida_rellena($row[$n])) return true;
        }
    }
    return false;
}

if (!function_exists('leer_funcionario_por_cedula_dbf')) {

/**
 * Busca un funcionario por cédula en el archivo DBF.
 * Regla: si tiene fecha de salida real (FEC_SALIDA / BAJA), se considera INACTIVO y NO se devuelve.
 * @param string $cedula Número de cédula (se normaliza a dígitos para comparar).
 * @param string|null $rutaDbf Ruta completa al archivo .DBF; si es null se usa la carpeta dbf/ en la raíz del proyecto.
 * @return array|null ['cedula' => string, 'nombre' => string, 'cargo' => string, 'DEPARTAMEN' => string, 'seccion' => string] o null si no existe o no se puede leer.
 */
function leer_funcionario_por_cedula_dbf($cedula, $rutaDbf = null) {
    $cedula = preg_replace('/[^0-9]/', '', trim($cedula));
    if ($cedula === '') return null;

    $rutaDbf = _dbf_ruta_rolmaes($rutaDbf);
    if ($rutaDbf === null) return null;

    if (!extension_loaded('dbase')) return null;

    $db = @dbase_open($rutaDbf, 0);
    if ($db === false) return null;

    $numRecords = dbase_numrecords($db);
    $map = null;
    for ($j = 1; $j <= $numRecords; $j++) {
        $r = dbase_get_record_with_names($db, $j);
        if (is_array($r) && empty($r['deleted'])) {
            $map = _dbf_detectar_campos($r);
            break;
        }
    }
    if ($map === null) {
        dbase_close($db);
        return null;
    }

    for ($i = 1; $i <= $numRecords; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        if (!is_array($row)) continue;
        if (!empty($row['deleted'])) continue;

        $numCedula = preg_replace('/[^0-9]/', '', _dbf_valor($row, $map['cedula'], _dbf_nombres_cedula()));
        if ($numCedula !== $cedula) continue;

        // Si tiene fecha de salida real, NO se considera activo (no se devuelve).
        if (_dbf_tiene_fecha_salida($row, $map['fecha_salida'])) {
            dbase_close($db);
            return null;
        }

        $nombre = _dbf_valor($row, $map['nombre'], _dbf_nombres_nombre()) ?: 'Sin nombre';
        $cargo  = _dbf_valor($row, $map['cargo'], _dbf_nombres_cargo()) ?: 'Sin cargo';
        $departamen = _dbf_valor($row, $map['DEPARTAMEN'], _dbf_nombres_departamen());
        $seccion = _dbf_valor($row, $map['seccion'], _dbf_nombres_seccion());

        dbase_close($db);
        return [
            'cedula' => $cedula,
            'nombre' => $nombre,
            'cargo' => $cargo,
            'DEPARTAMEN' => $departamen,
            'seccion' => $seccion
        ];
    }

    dbase_close($db);
    return null;
}
}

if (!function_exists('obtener_cedulas_inactivas_dbf')) {
/**
 * Obtiene cédulas que están INACTIVAS en el DBF (tienen fecha de salida real).
 * @param string|null $rutaDbf Ruta al .DBF; null = dbf/rolmaes.DBF en la raíz del proyecto.
 * @return array Lista de cédulas (solo dígitos).
 */
function obtener_cedulas_inactivas_dbf($rutaDbf = null) {
    $rutaDbf = _dbf_ruta_rolmaes($rutaDbf);
    if ($rutaDbf === null) return [];

    if (!extension_loaded('dbase')) return [];

    $db = @dbase_open($rutaDbf, 0);
    if ($db === false) return [];

    $numRecords = dbase_numrecords($db);
    $map = null;
    for ($i = 1; $i <= $numRecords; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        if (is_array($row) && empty($row['deleted'])) {
            $map = _dbf_detectar_campos($row);
            break;
        }
    }
    if ($map === null) {
        dbase_close($db);
        return [];
    }

    $out = [];
    for ($i = 1; $i <= $numRecords; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        if (!is_array($row)) continue;
        if (!empty($row['deleted'])) continue;
        if (!_dbf_tiene_fecha_salida($row, $map['fecha_salida'])) continue;
        $cedula = preg_replace('/[^0-9]/', '', _dbf_valor($row, $map['cedula'], _dbf_nombres_cedula()));
        if ($cedula === '') continue;
        $out[] = $cedula;
    }

    dbase_close($db);
    return array_values(array_unique($out));
}
}

if (!function_exists('obtener_funcionarios_activos_dbf')) {

/**
 * Obtiene todos los funcionarios activos del DBF (sin fecha de salida).
 * @param string|null $rutaDbf Ruta al .DBF; null = dbf/rolmaes.DBF en la raíz del proyecto.
 * @return array Lista de ['cedula' => string, 'nombre' => string, 'cargo' => string, 'DEPARTAMEN' => string, 'seccion' => string].
 */
function obtener_funcionarios_activos_dbf($rutaDbf = null) {
    $rutaDbf = _dbf_ruta_rolmaes($rutaDbf);
    if ($rutaDbf === null) return [];

    if (!extension_loaded('dbase')) return [];

    $db = @dbase_open($rutaDbf, 0);
    if ($db === false) return [];

    $numRecords = dbase_numrecords($db);
    $map = null;
    for ($i = 1; $i <= $numRecords; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        if (is_array($row) && empty($row['deleted'])) {
            $map = _dbf_detectar_campos($row);
            break;
        }
    }
    if ($map === null) {
        dbase_close($db);
        return [];
    }

    $lista = [];
    for ($i = 1; $i <= $numRecords; $i++) {
        $row = dbase_get_record_with_names($db, $i);
        if (!is_array($row)) continue;
        if (!empty($row['deleted'])) continue;

        if (_dbf_tiene_fecha_salida($row, $map['fecha_salida'])) continue;

        $cedula = preg_replace('/[^0-9]/', '', _dbf_valor($row, $map['cedula'], _dbf_nombres_cedula()));
        if ($cedula === '') continue;

        $nombre = _dbf_valor($row, $map['nombre'], _dbf_nombres_nombre()) ?: 'Sin nombre';
        $cargo  = _dbf_valor($row, $map['cargo'], _dbf_nombres_cargo()) ?: 'Sin cargo';
        $departamen = _dbf_valor($row, $map['DEPARTAMEN'], _dbf_nombres_departamen());
        $seccion = _dbf_valor($row, $map['seccion'], _dbf_nombres_seccion());
        $lista[] = [
            'cedula' => $cedula,
            'nombre' => $nombre,
            'cargo' => $cargo,
            'DEPARTAMEN' => $departamen,
            'seccion' => $seccion
        ];
    }

    dbase_close($db);
    return $lista;
}
}
