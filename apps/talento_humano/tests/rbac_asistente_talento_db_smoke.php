<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este control solo puede ejecutarse desde consola.\n");
    exit(1);
}

define('ROOT', dirname(__DIR__));
require ROOT . '/core/Config.php';
require ROOT . '/core/Database.php';

$inventoryOnly = in_array('--inventory', $argv, true);

try {
    $db = Conexion::conectar();

    if ($inventoryOnly) {
        $inventory = [
            'roles' => $db->query(
                'SELECT rol_id, nombre_rol, estado FROM dbo.th_roles ORDER BY rol_id'
            )->fetchAll(PDO::FETCH_ASSOC),
            'modulos' => $db->query(
                'SELECT modulo_id, codigo_modulo, nombre_modulo FROM dbo.th_modulos ORDER BY codigo_modulo'
            )->fetchAll(PDO::FETCH_ASSOC),
            'personal_talento_humano' => $db->query(
                "SELECT DISTINCT TOP (100)
                        e.empleado_id, e.identificacion, p.puesto_id, p.nombre_puesto,
                        u.unidad_id, u.nombre_unidad
                 FROM dbo.th_empleados e
                 LEFT JOIN dbo.th_puestos p ON p.puesto_id = e.puesto_id
                 LEFT JOIN dbo.th_unidades_organizacionales u ON u.unidad_id = e.unidad_id
                 WHERE e.estado = 1
                   AND (
                        UPPER(ISNULL(p.nombre_puesto, '')) LIKE '%TALENTO%'
                        OR UPPER(ISNULL(p.nombre_puesto, '')) LIKE '%TTHH%'
                        OR UPPER(ISNULL(p.nombre_puesto, '')) LIKE '%RECURSOS HUMANOS%'
                        OR UPPER(ISNULL(u.nombre_unidad, '')) LIKE '%TALENTO%'
                        OR UPPER(ISNULL(u.nombre_unidad, '')) LIKE '%TTHH%'
                        OR UPPER(ISNULL(u.nombre_unidad, '')) LIKE '%RECURSOS HUMANOS%'
                   )
                 ORDER BY p.nombre_puesto, e.empleado_id"
            )->fetchAll(PDO::FETCH_ASSOC),
        ];
        echo json_encode($inventory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        exit(0);
    }

    $roleName = 'Asistente de Talento Humano';
    $role = $db->prepare('SELECT rol_id, estado FROM dbo.th_roles WHERE nombre_rol = :nombre');
    $role->execute([':nombre' => $roleName]);
    $roleRow = $role->fetch(PDO::FETCH_ASSOC);
    if (!$roleRow || !(bool)$roleRow['estado']) {
        throw new RuntimeException('El rol Asistente de Talento Humano no existe o está inactivo.');
    }

    $expected = [
        'acciones' => [1, 1, 1, 0],
        'biblioteca' => [1, 0, 0, 0],
        'dashboard' => [1, 0, 0, 0],
        'directorio' => [1, 0, 0, 0],
        'documentos_firmados' => [1, 1, 1, 0],
        'empleados' => [1, 1, 1, 0],
        'maestros' => [1, 1, 1, 0],
        'movimientos' => [1, 1, 0, 0],
        'paz_salvo' => [1, 1, 1, 0],
        'reportes' => [1, 0, 0, 0],
        'socioeconomico' => [1, 1, 1, 0],
        'vacaciones' => [1, 0, 0, 0],
    ];

    $stmt = $db->prepare(
        'SELECT m.codigo_modulo,
                CONVERT(int, p.puede_visualizar) visualizar,
                CONVERT(int, p.puede_crear) crear,
                CONVERT(int, p.puede_editar) editar,
                CONVERT(int, p.puede_eliminar) eliminar
         FROM dbo.th_modulos m
         LEFT JOIN dbo.th_permisos_rol p
           ON p.modulo_id = m.modulo_id AND p.rol_id = :rol
         ORDER BY m.codigo_modulo'
    );
    $stmt->execute([':rol' => (int)$roleRow['rol_id']]);
    $actual = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $actual[(string)$row['codigo_modulo']] = [
            (int)($row['visualizar'] ?? 0),
            (int)($row['crear'] ?? 0),
            (int)($row['editar'] ?? 0),
            (int)($row['eliminar'] ?? 0),
        ];
    }

    foreach ($actual as $module => $permissions) {
        $wanted = $expected[$module] ?? [0, 0, 0, 0];
        if ($permissions !== $wanted) {
            throw new RuntimeException(
                "Permisos incorrectos para {$module}: " . json_encode($permissions)
                . ' (esperado ' . json_encode($wanted) . ').'
            );
        }
    }
    foreach (array_keys($expected) as $module) {
        if (!array_key_exists($module, $actual)) {
            throw new RuntimeException("No existe el módulo RBAC requerido {$module}.");
        }
    }

    $unmapped = (int)$db->query(
        "SELECT COUNT_BIG(*)
         FROM dbo.th_puestos p
         WHERE p.activo = 1
           AND (
                UPPER(p.nombre_puesto) LIKE '%ASISTENTE%TALENTO%'
                OR UPPER(p.nombre_puesto) LIKE '%ASISTENTE%TTHH%'
                OR UPPER(p.nombre_puesto) LIKE '%ASITENTE%TTHH%'
                OR UPPER(p.nombre_puesto) LIKE '%ASISTENTE%RECURSOS HUMANOS%'
           )
           AND NOT EXISTS (
                SELECT 1
                FROM dbo.th_puesto_rol_mapa prm
                WHERE prm.puesto_id = p.puesto_id
                  AND prm.rol_id = " . (int)$roleRow['rol_id'] . '
           )'
    )->fetchColumn();
    if ($unmapped !== 0) {
        throw new RuntimeException('Hay cargos de Asistente de Talento Humano sin asociación al nuevo rol.');
    }

    $candidate = $db->query(
        "SELECT TOP (1) e.empleado_id,e.identificacion
         FROM dbo.th_empleados e
         JOIN dbo.th_puestos p ON p.puesto_id=e.puesto_id
         LEFT JOIN dbo.th_usuarios_sistema u ON u.empleado_id=e.empleado_id
         WHERE e.estado=1 AND u.usuario_id IS NULL
           AND (
                UPPER(p.nombre_puesto) LIKE '%ASISTENTE%TALENTO%'
                OR UPPER(p.nombre_puesto) LIKE '%ASISTENTE%TTHH%'
                OR UPPER(p.nombre_puesto) LIKE '%ASITENTE%TTHH%'
           )
         ORDER BY e.empleado_id"
    )->fetch(PDO::FETCH_ASSOC);
    if (!$candidate) {
        throw new RuntimeException('No existe un asistente sin cuenta para probar el alta transaccional.');
    }

    $suggested = $db->prepare('EXEC dbo.sp_th_rol_sugerido_por_empleado :empleado');
    $suggested->execute([':empleado' => (int)$candidate['empleado_id']]);
    $suggestedRoles = array_map(
        static fn(array $row): int => (int)$row['rol_id'],
        $suggested->fetchAll(PDO::FETCH_ASSOC)
    );
    $suggested->closeCursor();
    if (!in_array((int)$roleRow['rol_id'], $suggestedRoles, true)) {
        throw new RuntimeException('El cargo de asistente no sugiere el nuevo rol en la pantalla de usuarios.');
    }

    $db->beginTransaction();
    try {
        $suffix = bin2hex(random_bytes(4));
        $create = $db->prepare(
            'EXEC dbo.sp_th_crear_usuario_sistema :usuario,:hash,:correo,:nombre,:empleado,:rol'
        );
        $create->execute([
            ':usuario' => 'qa_asistente_' . $suffix,
            ':hash' => password_hash('Temporal!12345', PASSWORD_DEFAULT),
            ':correo' => 'qa_asistente_' . $suffix . '@apm.test',
            ':nombre' => 'QA Asistente Talento Humano',
            ':empleado' => (int)$candidate['empleado_id'],
            ':rol' => (int)$roleRow['rol_id'],
        ]);
        if ((int)$create->fetchColumn() <= 0) {
            throw new RuntimeException('El procedimiento no devolvió la cuenta temporal creada.');
        }
        while ($create->nextRowset()) {}
    } finally {
        if ($db->inTransaction()) $db->rollBack();
    }

    $superAdminMissing = (int)$db->query(
        "SELECT COUNT_BIG(*)
         FROM dbo.th_modulos m
         CROSS JOIN dbo.th_roles r
         LEFT JOIN dbo.th_permisos_rol p ON p.modulo_id=m.modulo_id AND p.rol_id=r.rol_id
         WHERE r.nombre_rol='Super Administrador'
           AND (p.permiso_id IS NULL OR p.puede_visualizar=0 OR p.puede_crear=0
                OR p.puede_editar=0 OR p.puede_eliminar=0)"
    )->fetchColumn();
    if ($superAdminMissing !== 0) {
        throw new RuntimeException('Super Administrador no conserva control total sobre todos los módulos.');
    }

    echo "[OK] RBAC y alta transaccional de Asistente de Talento Humano validados.\n";
} catch (Throwable $error) {
    fwrite(STDERR, '[FAIL] ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
