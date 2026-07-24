<?php
/**
 * test_historial.php - Script de Verificación de Historial de Productos e IVA
 */
header('Content-Type: text/html; charset=utf-8');

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . '/');
}
require_once __DIR__ . '/config/globals.php';
require_once __DIR__ . '/core/Database.php';

echo "<h2>🧪 Iniciando Pruebas del Historial de Productos (SCD Tipo 2 y Snapshots)</h2>";

try {
    $db = Database::getInstance()->getConnection();
    echo "🟢 Conexión exitosa a la base de datos.<br><br>";
} catch (Exception $e) {
    echo "🔴 Error al conectar con la base de datos: " . $e->getMessage() . "<br>";
    exit;
}

// 1. Instanciar el modelo para forzar la ejecución de las migraciones
echo "⚙️ Instanciando ItemSistemaModel para ejecutar migraciones...<br>";
require_once __DIR__ . '/modules/Control_bines/models/InvItemSistema.php';
$itemModel = new InvItemSistema();
echo "🟢 Inicialización de modelo completada.<br><br>";

// 2. Verificar existencia de tablas y columnas
echo "📋 <b>Verificación de Esquema:</b><br>";

try {
    $count = $db->query("SELECT COUNT(*) FROM inv_productos_historial")->fetchColumn();
    echo "✅ La tabla `inv_productos_historial` existe y contiene $count registros históricos.<br>";
} catch (Exception $e) {
    echo "❌ La tabla `inv_productos_historial` NO existe o falló: " . $e->getMessage() . "<br>";
}

try {
    $db->query("SELECT grupo_id_historico, porcentaje_iva, valor_iva FROM inv_bod_ingresos_detalles WHERE 1=0");
    echo "✅ Las columnas de snapshot (`grupo_id_historico`, `porcentaje_iva`, `valor_iva`) existen en `inv_bod_ingresos_detalles`.<br>";
} catch (Exception $e) {
    echo "❌ Las columnas de snapshot NO existen en `inv_bod_ingresos_detalles`: " . $e->getMessage() . "<br>";
}

try {
    $db->query("SELECT grupo_id_historico FROM inv_bod_egresos_detalles WHERE 1=0");
    echo "✅ La columna de snapshot (`grupo_id_historico`) existe en `inv_bod_egresos_detalles`.<br><br>";
} catch (Exception $e) {
    echo "❌ La columna de snapshot NO existe en `inv_bod_egresos_detalles`: " . $e->getMessage() . "<br><br>";
}

// 3. Prueba lógica: Creación y Actualización con Historial
echo "📦 <b>Prueba de Catálogo Histórico (SCD Tipo 2):</b><br>";

try {
    // Buscar o crear grupo y unidad base
    $grupoId = (int)$db->query("SELECT id FROM inv_categorias")->fetchColumn();
    if (!$grupoId) {
        $db->exec("INSERT INTO inv_categorias (nombre) VALUES ('Grupo Test de Historial')");
        $grupoId = (int)$db->lastInsertId();
    }
    $unidadId = (int)$db->query("SELECT id FROM inv_unidades")->fetchColumn();
    if (!$unidadId) {
        $db->exec("INSERT INTO inv_unidades (nombre) VALUES ('Unidades Test')");
        $unidadId = (int)$db->lastInsertId();
    }

    // A. Crear producto de prueba
    $nombreTest = "Producto de Prueba " . time();
    $producto = $itemModel->crear([
        'nombre' => $nombreTest,
        'grupo_id' => $grupoId,
        'unidad_id' => $unidadId,
        'aplica_iva' => 1,
        'precio_promedio' => 100.0,
        'existencia_actual' => 10.0
    ]);
    echo "✅ Producto de prueba creado (ID: " . $producto['id'] . ") con Grupo ID: $grupoId y Precio: $100.0.<br>";

    // B. Verificar registro inicial en el historial
    $stmtHist = $db->prepare("SELECT * FROM inv_productos_historial WHERE producto_id = :id AND es_actual = 1");
    $stmtHist->execute([':id' => $producto['id']]);
    $histAct = $stmtHist->fetch();
    
    if ($histAct && (int)$histAct['grupo_id'] === $grupoId && (float)$histAct['precio'] === 100.0) {
        echo "✅ Historial inicial abierto correctamente (Grupo: " . $histAct['grupo_id'] . ", Precio: $" . $histAct['precio'] . ").<br>";
    } else {
        throw new Exception("El historial inicial no se abrió correctamente.");
    }

    // C. Crear un segundo grupo y actualizar el producto
    $db->exec("INSERT INTO inv_categorias (nombre) VALUES ('Grupo Test Alternativo " . time() . "')");
    $grupoId2 = (int)$db->lastInsertId();

    echo "🔄 Actualizando el producto a un nuevo Grupo ID: $grupoId2 y Precio: $150.0...<br>";
    $itemModel->actualizar($producto['id'], [
        'nombre' => $nombreTest,
        'grupo_id' => $grupoId2,
        'unidad_id' => $unidadId,
        'aplica_iva' => 1,
        'precio_promedio' => 150.0,
        'existencia_actual' => 10.0
    ]);

    // D. Verificar que el historial anterior se haya cerrado y el nuevo se haya abierto
    $stmtClosed = $db->prepare("SELECT * FROM inv_productos_historial WHERE producto_id = :id AND es_actual = 0");
    $stmtClosed->execute([':id' => $producto['id']]);
    $histOld = $stmtClosed->fetch();

    if ($histOld && $histOld['fecha_fin'] !== null) {
        echo "✅ Historial anterior cerrado correctamente (Fecha Fin: " . $histOld['fecha_fin'] . ").<br>";
    } else {
        throw new Exception("El historial anterior no se cerró.");
    }

    $stmtNew = $db->prepare("SELECT * FROM inv_productos_historial WHERE producto_id = :id AND es_actual = 1");
    $stmtNew->execute([':id' => $producto['id']]);
    $histNew = $stmtNew->fetch();

    if ($histNew && (int)$histNew['grupo_id'] === $grupoId2 && (float)$histNew['precio'] === 150.0) {
        echo "✅ Nuevo historial abierto correctamente (Grupo: " . $histNew['grupo_id'] . ", Precio: $" . $histNew['precio'] . ").<br><br>";
    } else {
        throw new Exception("El nuevo historial no se abrió o tiene datos incorrectos.");
    }

    // 4. Prueba lógica: Transacción e Instantánea (Snapshot)
    echo "📊 <b>Prueba de Snapshot Transaccional (Ingresos y Egresos):</b><br>";

    // Buscar item_id de inv_inventario asociado al producto
    $itemId = (int)$db->query("SELECT id FROM inv_inventario WHERE producto_id = " . $producto['id'])->fetchColumn();
    
    // Obtener un responsable de talento humano
    $responsableId = (int)$db->query("SELECT id FROM inv_talento_personal")->fetchColumn();
    if (!$responsableId) {
        $db->exec("INSERT INTO inv_talento_personal (nombre, identificacion) VALUES ('Inspector de Prueba', '1313131313')");
        $responsableId = (int)$db->lastInsertId();
    }

    require_once __DIR__ . '/modules/Control_bines/models/InvIngreso.php';
    $ingresoModel = new InvIngreso();

    echo "🛒 Generando un ingreso de bodega para congelar instantáneas...<br>";
    $ingresoId = $ingresoModel->crear([
        'proveedor' => 'Distribuidor Test',
        'fecha' => date('Y-m-d'),
        'observaciones' => 'Ingreso de validación de snapshots',
        'responsable_id' => $responsableId,
        'creado_por' => 'Test System'
    ], [
        [
            'item_id' => $itemId,
            'cantidad' => 3,
            'valor_unitario' => 150.0
        ]
     ]);

    // Verificar snapshot en detalles del ingreso
    $stmtDet = $db->prepare("SELECT * FROM inv_bod_ingresos_detalles WHERE ingreso_id = :ing_id");
    $stmtDet->execute([':ing_id' => $ingresoId]);
    $detalle = $stmtDet->fetch();

    if ($detalle && (int)$detalle['grupo_id_historico'] === $grupoId2 && (float)$detalle['porcentaje_iva'] > 0) {
        echo "✅ Snapshot de ingreso registrado: Grupo Histórico = " . $detalle['grupo_id_historico'] . ", IVA = " . $detalle['porcentaje_iva'] . "%, Valor IVA = $" . $detalle['valor_iva'] . ".<br>";
    } else {
        throw new Exception("El snapshot del detalle de ingreso no se grabó correctamente.");
    }

    // Limpieza de datos de prueba
    echo "<br>🧹 Limpiando registros de prueba...<br>";
    $db->exec("DELETE FROM inv_bod_ingresos_detalles WHERE ingreso_id = $ingresoId");
    $db->exec("DELETE FROM inv_bod_ingresos WHERE id = $ingresoId");
    $db->exec("DELETE FROM inv_productos_historial WHERE producto_id = " . $producto['id']);
    $db->exec("DELETE FROM inv_inventario WHERE producto_id = " . $producto['id']);
    $db->exec("DELETE FROM inv_productos WHERE id = " . $producto['id']);
    echo "✅ Limpieza completada.<br><br>";

    echo "<h3>🎉 ¡TODAS LAS PRUEBAS COMPLETADAS CON ÉXITO!</h3>";

} catch (Exception $e) {
    echo "🔴 Ocurrió un error durante las pruebas lógicas: " . $e->getMessage() . "<br>";
    // Intentar limpiar en caso de error
    if (isset($producto['id'])) {
        $db->exec("DELETE FROM inv_productos_historial WHERE producto_id = " . $producto['id']);
        $db->exec("DELETE FROM inv_inventario WHERE producto_id = " . $producto['id']);
        $db->exec("DELETE FROM inv_productos WHERE id = " . $producto['id']);
    }
}
