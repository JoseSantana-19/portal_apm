<?php

/**
 * Nombre de fila para la ventana de edición de bitácora (guardias).
 * Debe coincidir con dbo.bit_parametro.nombre en BD.
 */
const APM_CONFIG_DIAS_EDICION_BITACORA = 'dias_edicion';

/**
 * Configuración general persistida en BD (SQL Server), tabla dbo.bit_parametro (nombre, valor).
 */
function apm_config_tabla_ensure($conn): bool
{
    $sqlCheck = "SELECT 1 FROM sys.tables WHERE name = N'bit_parametro' AND schema_id = SCHEMA_ID(N'dbo')";
    $stCheck = sqlsrv_query($conn, $sqlCheck);
    if ($stCheck && sqlsrv_fetch_array($stCheck, SQLSRV_FETCH_ASSOC)) {
        return true;
    }

    $sqlCreate = "CREATE TABLE dbo.bit_parametro (
        nombre VARCHAR(100) NOT NULL PRIMARY KEY,
        valor VARCHAR(255) NOT NULL
    )";
    $stCreate = sqlsrv_query($conn, $sqlCreate);
    return $stCreate !== false;
}

function apm_config_get($conn, string $nombreParam, ?string $default = null): ?string
{
    $sql = 'SELECT TOP 1 valor FROM dbo.bit_parametro WHERE nombre = ?';
    $st = sqlsrv_query($conn, $sql, [$nombreParam]);
    if ($st && ($row = sqlsrv_fetch_array($st, SQLSRV_FETCH_ASSOC))) {
        return isset($row['valor']) ? (string) $row['valor'] : $default;
    }
    return $default;
}

function apm_config_set($conn, string $nombreParam, string $valor): bool
{
    $sqlUpd = 'UPDATE dbo.bit_parametro SET valor = ? WHERE nombre = ?';
    $stUpd = sqlsrv_query($conn, $sqlUpd, [$valor, $nombreParam]);
    if ($stUpd === false) {
        return false;
    }
    $rows = sqlsrv_rows_affected($stUpd);
    if (is_int($rows) && $rows > 0) {
        return true;
    }
    $sqlIns = 'INSERT INTO dbo.bit_parametro (nombre, valor) VALUES (?, ?)';
    $stIns = sqlsrv_query($conn, $sqlIns, [$nombreParam, $valor]);
    return $stIns !== false;
}

function apm_config_get_dias_edicion_bitacora($conn): int
{
    $default = 1;
    $raw = apm_config_get($conn, APM_CONFIG_DIAS_EDICION_BITACORA, (string) $default);
    $dias = (int) $raw;
    $dias = apm_bitacora_guardia_dias_permitidos($dias);

    if ($raw === null) {
        apm_config_set($conn, APM_CONFIG_DIAS_EDICION_BITACORA, (string) $dias);
    }

    return $dias;
}
