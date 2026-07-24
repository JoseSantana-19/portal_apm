<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Conexión SQL Server</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: #1e293b;
            border-radius: 16px;
            padding: 40px 50px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
            max-width: 550px;
            width: 100%;
            border: 1px solid #334155;
        }

        .icon { font-size: 48px; text-align: center; margin-bottom: 16px; }

        h1 {
            text-align: center;
            color: #f1f5f9;
            font-size: 22px;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            font-size: 13px;
            margin-bottom: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 24px;
        }

        .info-box {
            background: #0f172a;
            border-radius: 10px;
            padding: 14px 16px;
            border: 1px solid #334155;
        }

        .info-box label {
            display: block;
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-box span {
            font-size: 14px;
            color: #e2e8f0;
            font-weight: 500;
        }

        .status-box {
            border-radius: 12px;
            padding: 20px 24px;
            text-align: center;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .status-ok {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
        }

        .status-inv_error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        .status-box .status-icon { font-size: 32px; display: block; margin-bottom: 8px; }

        .detail {
            background: #0f172a;
            border-radius: 10px;
            padding: 16px;
            font-size: 12px;
            color: #94a3b8;
            border: 1px solid #334155;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .tables-list {
            margin-top: 20px;
        }

        .tables-list h3 {
            color: #94a3b8;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .table-item {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #cbd5e1;
            font-size: 13px;
        }

        .table-item span { color: #38bdf8; }
    </style>
</head>
<body>
<?php
require_once 'db/connection.php';

$serverDisplay = DB_SERVER;
$dbDisplay     = DB_DATABASE;
$userDisplay   = DB_USER;

// --- Intentar conexión ---
$connectionInfo = [
    "Database"               => DB_DATABASE,
    "UID"                    => DB_USER,
    "PWD"                    => DB_PASSWORD,
    "CharacterSet"           => "UTF-8",
    "TrustServerCertificate" => true
];

$conn    = sqlsrv_connect(DB_SERVER, $connectionInfo);
$success = ($conn !== false);

// --- Obtener tablas si la conexión fue exitosa ---
$tables = [];
if ($success) {
    $sql    = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME";
    $result = sqlsrv_query($conn, $sql);
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $tables[] = $row['TABLE_NAME'];
    }
    sqlsrv_close($conn);
}
?>

<div class="card">
    <div class="icon"><?= $success ? '🟢' : '🔴' ?></div>
    <h1>Test de Conexión SQL Server</h1>
    <p class="subtitle">Verificando conexión desde PHP + XAMPP</p>

    <div class="info-grid">
        <div class="info-box">
            <label>Servidor</label>
            <span><?= htmlspecialchars($serverDisplay) ?></span>
        </div>
        <div class="info-box">
            <label>Base de Datos</label>
            <span><?= htmlspecialchars($dbDisplay) ?></span>
        </div>
        <div class="info-box">
            <label>InvUsuario</label>
            <span><?= htmlspecialchars($userDisplay) ?></span>
        </div>
        <div class="info-box">
            <label>PHP Version</label>
            <span><?= phpversion() ?></span>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="status-box status-ok">
            <span class="status-icon">✅</span>
            ¡Conexión exitosa a SQL Server!
        </div>

        <?php if (!empty($tables)): ?>
        <div class="tables-list">
            <h3>📋 Tablas en "<?= htmlspecialchars($dbDisplay) ?>"</h3>
            <?php foreach ($tables as $table): ?>
            <div class="table-item">
                <span>▸</span> <?= htmlspecialchars($table) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="detail">ℹ️ La base de datos no tiene tablas aún.</div>
        <?php endif; ?>

    <?php else: ?>
        <div class="status-box status-inv_error">
            <span class="status-icon">❌</span>
            Error al conectar con SQL Server
        </div>
        <div class="detail"><?php
            $errors = sqlsrv_errors();
            if ($errors) {
                foreach ($errors as $err) {
                    echo "SQLSTATE: " . $err['SQLSTATE'] . "\n";
                    echo "Código:   " . $err['code']    . "\n";
                    echo "Mensaje:  " . $err['message'] . "\n";
                }
            }
        ?></div>
    <?php endif; ?>
</div>

</body>
</html>
