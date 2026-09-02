<?php

define('ROOT', dirname(__DIR__));
require ROOT.'/core/Config.php';
require ROOT.'/core/Database.php';

$db = Conexion::conectar();
$objects = [
    'dbo.th_documentos_firmados' => 'U',
    'dbo.vw_th_documentos_firmados' => 'V',
    'dbo.vw_th_eventos_laborales' => 'V',
    'dbo.sp_th_registrar_documento_firmado' => 'P',
    'dbo.sp_th_consultar_documentos_firmados' => 'P',
    'dbo.sp_th_consultar_eventos_laborales' => 'P',
];
foreach ($objects as $name => $type) {
    $stmt = $db->prepare('SELECT OBJECT_ID(:name,:type)');
    $stmt->execute([':name'=>$name, ':type'=>$type]);
    $exists = (bool)$stmt->fetchColumn();
    $stmt->closeCursor();
    if (!$exists) throw new RuntimeException("Falta {$name}.");
}

$vacations = (int)$db->query("SELECT COUNT_BIG(*) FROM dbo.th_acciones_personal WHERE UPPER(LTRIM(RTRIM(tipo_accion))) COLLATE Modern_Spanish_CI_AI=N'VACACIONES'")->fetchColumn();
$vacationEvents = (int)$db->query("SELECT COUNT_BIG(*) FROM dbo.vw_th_eventos_laborales WHERE categoria='VACACIONES'")->fetchColumn();
if ($vacations !== $vacationEvents) throw new RuntimeException('El historial no cubre todas las vacaciones.');

$movements = (int)$db->query('SELECT COUNT_BIG(*) FROM dbo.th_movimientos_personal')->fetchColumn();
$movementEvents = (int)$db->query("SELECT COUNT_BIG(*) FROM dbo.vw_th_eventos_laborales WHERE categoria='MOVIMIENTO_INTERNO'")->fetchColumn();
if ($movements !== $movementEvents) throw new RuntimeException('El historial no cubre todos los movimientos internos.');

$migration = $db->query("SELECT COUNT_BIG(*) FROM dbo.th_schema_migrations WHERE version='2026.08.27.1'")->fetchColumn();
if ((int)$migration !== 1) throw new RuntimeException('La migración 2026.08.27.1 no está registrada.');

$draft = $db->query("SELECT TOP (1) accion_id,empleado_id FROM dbo.th_acciones_personal WHERE estado_documento<>'APROBADO' ORDER BY accion_id DESC")->fetch(PDO::FETCH_ASSOC);
if ($draft) {
    $blocked = $db->prepare("EXEC dbo.sp_th_registrar_documento_firmado
        @empleado_id=:empleado,@tipo_documento='ACCION_PERSONAL',@origen_id=:origen,
        @nombre_original=N'borrador.pdf',@ruta_privada=N'documentos-firmados/pruebas/borrador.pdf',
        @mime_type='application/pdf',@tamano_bytes=256,@sha256=:hash,
        @observaciones=N'Prueba de bloqueo',@usuario=N'smoke_test',@ip=N'127.0.0.1'");
    $blocked->execute([':empleado'=>(int)$draft['empleado_id'],':origen'=>(int)$draft['accion_id'],':hash'=>hash('sha256','draft-action')]);
    $blockedResult = $blocked->fetch(PDO::FETCH_ASSOC) ?: [];
    $blocked->closeCursor();
    if ((int)($blockedResult['exito'] ?? 1) !== 0) throw new RuntimeException('Se permitió legalizar una Acción de Personal sin aprobar.');
}

// Comprueba versionado, reemplazo y proyección en el historial sin dejar datos
// ficticios: toda la operación se ejecuta dentro de una transacción exterior.
$employeeId = (int)$db->query('SELECT TOP (1) empleado_id FROM dbo.th_empleados ORDER BY empleado_id')->fetchColumn();
if ($employeeId <= 0) throw new RuntimeException('No existe un funcionario para la prueba documental.');
$maxVersionStmt = $db->prepare("SELECT COALESCE(MAX(version_documento),0) FROM dbo.th_documentos_firmados WHERE empleado_id=:empleado AND tipo_documento='FICHA_PERSONAL' AND origen_id=:origen");
$maxVersionStmt->execute([':empleado'=>$employeeId, ':origen'=>$employeeId]);
$initialVersion = (int)$maxVersionStmt->fetchColumn();
$maxVersionStmt->closeCursor();

$db->beginTransaction();
try {
    $register = static function (PDO $db, int $employeeId, string $suffix): int {
        $stmt = $db->prepare("EXEC dbo.sp_th_registrar_documento_firmado
            @empleado_id=:empleado,
            @tipo_documento='FICHA_PERSONAL',
            @origen_id=:origen,
            @nombre_original=:nombre,
            @ruta_privada=:ruta,
            @mime_type='application/pdf',
            @tamano_bytes=256,
            @sha256=:hash,
            @observaciones=N'Prueba transaccional automatizada',
            @usuario=N'smoke_test',
            @ip=N'127.0.0.1'");
        $stmt->execute([
            ':empleado'=>$employeeId,
            ':origen'=>$employeeId,
            ':nombre'=>"firmado-{$suffix}.pdf",
            ':ruta'=>"documentos-firmados/pruebas/firmado-{$suffix}.pdf",
            ':hash'=>hash('sha256', "signed-document-{$suffix}"),
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $stmt->closeCursor();
        if ((int)($result['exito'] ?? 0) !== 1) {
            throw new RuntimeException((string)($result['mensaje'] ?? 'El procedimiento rechazó el documento de prueba.'));
        }
        return (int)($result['documento_id'] ?? 0);
    };

    $firstId = $register($db, $employeeId, 'v1');
    $secondId = $register($db, $employeeId, 'v2');
    if ($firstId <= 0 || $secondId <= $firstId) throw new RuntimeException('El procedimiento no devolvió identificadores válidos.');

    $state = $db->prepare('SELECT documento_id, version_documento, estado FROM dbo.th_documentos_firmados WHERE documento_id IN (:first_id,:second_id) ORDER BY documento_id');
    $state->execute([':first_id'=>$firstId, ':second_id'=>$secondId]);
    $rows = $state->fetchAll(PDO::FETCH_ASSOC);
    $state->closeCursor();
    if (count($rows) !== 2
        || (int)$rows[0]['version_documento'] !== $initialVersion + 1
        || $rows[0]['estado'] !== 'REEMPLAZADO'
        || (int)$rows[1]['version_documento'] !== $initialVersion + 2
        || $rows[1]['estado'] !== 'FIRMADO') {
        throw new RuntimeException('El versionado documental o el reemplazo no es consistente.');
    }

    $events = $db->prepare("SELECT COUNT_BIG(*) FROM dbo.vw_th_eventos_laborales WHERE origen_tipo='DOCUMENTO_FIRMADO' AND origen_id IN (:first_id,:second_id)");
    $events->execute([':first_id'=>$firstId, ':second_id'=>$secondId]);
    $eventCount = (int)$events->fetchColumn();
    $events->closeCursor();
    if ($eventCount !== 2) throw new RuntimeException('La carga firmada no se refleja en el historial integral.');
} finally {
    if ($db->inTransaction()) $db->rollBack();
}
$residue = $db->query("SELECT COUNT_BIG(*) FROM dbo.th_documentos_firmados WHERE usuario_carga='smoke_test' AND ruta_privada LIKE 'documentos-firmados/pruebas/%'")->fetchColumn();
if ((int)$residue !== 0) throw new RuntimeException('La prueba transaccional dejó documentos ficticios en la base.');
echo "[OK] Repositorio documental e historial integral instalados en SQL Server.\n";
