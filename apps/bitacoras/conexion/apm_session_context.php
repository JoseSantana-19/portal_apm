<?php
/**
 * Propaga el id_usuario de la sesión PHP a SQL Server CONTEXT_INFO,
 * compatible con SQL Server 2012/2014+.
 *
 * Si no hay sesión o usuario, no hace nada (el trigger usa NULL).
 */
if (!function_exists('apm_sql_set_session_context_usuario')) {
    function apm_sql_set_session_context_usuario($conn)
    {
        if ($conn === false) {
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        if (empty($_SESSION['apm_auth']['id_usuario'])) {
            return;
        }
        $id = (int) $_SESSION['apm_auth']['id_usuario'];
        if ($id <= 0) {
            return;
        }
        $sql = 'DECLARE @id INT = ?; SET CONTEXT_INFO CAST(@id AS VARBINARY(128));';
        @sqlsrv_query($conn, $sql, [$id]);
    }
}
