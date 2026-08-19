<?php
require_once ROOT_PATH . 'core/Model.php';
require_once ROOT_PATH . 'modules/Central/models/InvSecuencial.php';
require_once ROOT_PATH . 'modules/Bitacoras/models/BitacoraModel.php';

/**
 * Flujo trazable de abastecimiento: nota -> orden -> factura -> ingreso.
 * Las notas de este modelo son de compra y no se mezclan con las notas internas
 * que alimentan los egresos de bodega existentes.
 */
class InvAbastecimiento extends Model
{
    public function prepararDocumentosFactura(): bool
    {
        if (!$this->esquemaDisponible()) return false;
        try {
            $columnas = [
                'archivo_nombre_original' => 'NVARCHAR(255) NULL',
                'archivo_ruta' => 'NVARCHAR(500) NULL',
                'archivo_mime' => 'NVARCHAR(100) NULL',
                'ocr_texto' => 'NVARCHAR(MAX) NULL',
                'fecha_escaneo' => 'DATETIME2(0) NULL',
            ];
            foreach ($columnas as $nombre => $definicion) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM sys.columns WHERE object_id = OBJECT_ID('dbo.inv_facturas') AND name = :nombre");
                $stmt->execute([':nombre' => $nombre]);
                if ((int)$stmt->fetchColumn() === 0) $this->db->exec("ALTER TABLE dbo.inv_facturas ADD {$nombre} {$definicion}");
            }
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function esquemaDisponible(): bool
    {
        try {
            $this->db->query('SELECT 1 FROM inv_abast_notas_pedido WHERE 1 = 0');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function resumen(): array
    {
        return [
            'notas' => (int)$this->db->query('SELECT COUNT(*) FROM inv_abast_notas_pedido')->fetchColumn(),
            'ordenes_pendientes' => (int)$this->db->query("SELECT COUNT(*) FROM inv_ordenes_compra WHERE estado = 'PENDIENTE'")->fetchColumn(),
            'facturas_pendientes' => (int)$this->db->query("SELECT COUNT(*) FROM inv_facturas WHERE estado = 'REGISTRADA'")->fetchColumn(),
            'ingresos' => (int)$this->db->query('SELECT COUNT(*) FROM inv_bod_ingresos WHERE factura_id IS NOT NULL')->fetchColumn(),
        ];
    }

    public function listarNotas(): array
    {
        $sql = "SELECT n.*,
                       (SELECT COUNT(*) FROM inv_abast_notas_pedido_detalles d WHERE d.nota_id = n.id_nota) total_lineas,
                       (SELECT COALESCE(SUM(d.cantidad_solicitada), 0) FROM inv_abast_notas_pedido_detalles d WHERE d.nota_id = n.id_nota) total_unidades
                FROM inv_abast_notas_pedido n
                ORDER BY n.fecha_solicitud DESC, n.id_nota DESC";
        $notas = $this->db->query($sql)->fetchAll();
        foreach ($notas as &$nota) {
            $nota['detalles'] = $this->detallesNota((int)$nota['id_nota']);
        }
        return $notas;
    }

    public function listarOrdenes(): array
    {
        $sql = "SELECT o.*, p.nombre proveedor, p.ruc proveedor_ruc, n.secuencial nota_secuencial,
                       (SELECT COUNT(*) FROM inv_ordenes_compra_detalles d WHERE d.orden_id = o.id_orden) total_lineas,
                       (SELECT COALESCE(SUM(d.cantidad * d.precio_unitario_estimado), 0) FROM inv_ordenes_compra_detalles d WHERE d.orden_id = o.id_orden) total_estimado
                FROM inv_ordenes_compra o
                JOIN inv_proveedores p ON p.id = o.proveedor_id
                LEFT JOIN inv_abast_notas_pedido n ON n.id_nota = o.nota_pedido_id
                ORDER BY o.fecha DESC, o.id_orden DESC";
        $ordenes = $this->db->query($sql)->fetchAll();
        foreach ($ordenes as &$orden) {
            $orden['detalles'] = $this->detallesOrden((int)$orden['id_orden']);
        }
        return $ordenes;
    }

    public function listarFacturas(): array
    {
        $sql = "SELECT f.*, p.nombre proveedor, p.ruc proveedor_ruc, o.secuencial orden_secuencial,
                       o.nota_pedido_id, o.origen orden_origen, n.secuencial nota_secuencial,
                       (SELECT COUNT(*) FROM inv_facturas_detalles d WHERE d.factura_id = f.id_factura) total_lineas
                FROM inv_facturas f
                JOIN inv_proveedores p ON p.id = f.proveedor_id
                JOIN inv_ordenes_compra o ON o.id_orden = f.orden_compra_id
                LEFT JOIN inv_abast_notas_pedido n ON n.id_nota = o.nota_pedido_id
                ORDER BY f.fecha_factura DESC, f.id_factura DESC";
        $facturas = $this->db->query($sql)->fetchAll();
        foreach ($facturas as &$factura) {
            $factura['detalles'] = $this->detallesFactura((int)$factura['id_factura']);
        }
        return $facturas;
    }

    public function listarIngresos(): array
    {
        return $this->db->query(
            "SELECT i.id, i.secuencial, i.fecha, i.proveedor, i.observaciones, i.factura_id,
                    f.numero_factura, o.secuencial orden_secuencial, p.nombre responsable,
                    (SELECT COALESCE(SUM(d.cantidad), 0) FROM inv_bod_ingresos_detalles d WHERE d.ingreso_id = i.id) total_unidades
             FROM inv_bod_ingresos i
             JOIN inv_facturas f ON f.id_factura = i.factura_id
             JOIN inv_ordenes_compra o ON o.id_orden = i.orden_compra_id
             JOIN inv_talento_personal p ON p.id = i.responsable_id
             ORDER BY i.fecha DESC, i.id DESC"
        )->fetchAll();
    }

    public function listarKardexEntradas(): array
    {
        return $this->db->query(
            "SELECT k.*, i.nombre item_nombre, i.secuencial item_secuencial
             FROM inv_kardex k
             JOIN inv_inventario i ON i.id = k.item_id
             WHERE k.tipo_movimiento = 'INGRESO'
             ORDER BY k.fecha_movimiento DESC, k.id_movimiento DESC"
        )->fetchAll();
    }

    public function crearNota(array $datos, array $detalles): int
    {
        $detalles = $this->normalizarDetalles($detalles, false);
        if (!$detalles) throw new InvalidArgumentException('Agregue al menos un ítem a la nota de pedido.');
        if (trim((string)($datos['solicitante'] ?? '')) === '') throw new InvalidArgumentException('Indique el solicitante.');

        try {
            $this->db->beginTransaction();
            $secuencial = (new InvSecuencial())->generarSiguiente('npa');
            $stmt = $this->db->prepare(
                "INSERT INTO inv_abast_notas_pedido
                    (secuencial, fecha_solicitud, solicitante, area_solicitante, estado, observaciones, creado_por)
                 VALUES (:sec, :fecha, :solicitante, :area, 'PENDIENTE', :obs, :usuario)"
            );
            $stmt->execute([
                ':sec' => $secuencial,
                ':fecha' => $datos['fecha_solicitud'] ?? date('Y-m-d'),
                ':solicitante' => trim((string)$datos['solicitante']),
                ':area' => trim((string)($datos['area_solicitante'] ?? '')),
                ':obs' => trim((string)($datos['observaciones'] ?? '')),
                ':usuario' => $datos['creado_por'] ?? 'Sistema',
            ]);
            $id = (int)$this->db->lastInsertId();
            $insert = $this->db->prepare(
                'INSERT INTO inv_abast_notas_pedido_detalles (nota_id, item_id, cantidad_solicitada) VALUES (:nota, :item, :cantidad)'
            );
            foreach ($detalles as $detalle) {
                $insert->execute([':nota' => $id, ':item' => $detalle['item_id'], ':cantidad' => $detalle['cantidad']]);
            }
            (new InvBitacora())->registrar('CREAR', 'bod', "Nota de abastecimiento {$secuencial} registrada.");
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function crearOrden(array $datos, array $detalles): int
    {
        $detalles = $this->normalizarDetalles($detalles, true);
        if (!$detalles) throw new InvalidArgumentException('Agregue al menos un ítem a la orden de compra.');
        $proveedorId = (int)($datos['proveedor_id'] ?? 0);
        if ($proveedorId <= 0) throw new InvalidArgumentException('Seleccione un proveedor.');
        $notaId = (int)($datos['nota_pedido_id'] ?? 0);

        try {
            $this->db->beginTransaction();
            if ($notaId > 0) {
                $nota = $this->buscarUno('inv_abast_notas_pedido', 'id_nota', $notaId);
                if (!$nota || $nota['estado'] !== 'PENDIENTE') throw new RuntimeException('La nota seleccionada ya no está disponible.');
            }
            $secuencial = (new InvSecuencial())->generarSiguiente('ocp');
            $stmt = $this->db->prepare(
                "INSERT INTO inv_ordenes_compra
                    (secuencial, fecha, nota_pedido_id, proveedor_id, origen, estado, observaciones, creado_por)
                 VALUES (:sec, :fecha, :nota, :proveedor, :origen, 'PENDIENTE', :obs, :usuario)"
            );
            $stmt->execute([
                ':sec' => $secuencial, ':fecha' => $datos['fecha'] ?? date('Y-m-d'),
                ':nota' => $notaId ?: null, ':proveedor' => $proveedorId,
                ':origen' => $notaId ? 'NOTA_PEDIDO' : 'MANUAL',
                ':obs' => trim((string)($datos['observaciones'] ?? '')),
                ':usuario' => $datos['creado_por'] ?? 'Sistema',
            ]);
            $id = (int)$this->db->lastInsertId();
            $insert = $this->db->prepare(
                'INSERT INTO inv_ordenes_compra_detalles (orden_id, item_id, cantidad, precio_unitario_estimado) VALUES (:orden, :item, :cantidad, :precio)'
            );
            foreach ($detalles as $detalle) {
                $insert->execute([':orden' => $id, ':item' => $detalle['item_id'], ':cantidad' => $detalle['cantidad'], ':precio' => $detalle['precio']]);
            }
            if ($notaId) {
                $up = $this->db->prepare("UPDATE inv_abast_notas_pedido SET estado = 'EN_ORDEN' WHERE id_nota = :id");
                $up->execute([':id' => $notaId]);
            }
            (new InvBitacora())->registrar('CREAR', 'bod', "Orden de compra {$secuencial} registrada pendiente de aprobación.");
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function actualizarOrden(int $id, array $datos, array $detalles, string $usuario): void
    {
        $detalles = $this->normalizarDetalles($detalles, true);
        if ($id <= 0) throw new InvalidArgumentException('La orden indicada no es válida.');
        if (!$detalles) throw new InvalidArgumentException('Agregue al menos un ítem a la orden de compra.');
        $proveedorId = (int)($datos['proveedor_id'] ?? 0);
        if ($proveedorId <= 0) throw new InvalidArgumentException('Seleccione un proveedor.');

        try {
            $this->db->beginTransaction();
            $orden = $this->buscarUno('inv_ordenes_compra', 'id_orden', $id);
            if (!$orden || $orden['estado'] !== 'PENDIENTE') {
                throw new RuntimeException('Solo se pueden editar órdenes que todavía estén pendientes.');
            }

            $stmt = $this->db->prepare(
                'UPDATE inv_ordenes_compra SET fecha = :fecha, proveedor_id = :proveedor, observaciones = :obs WHERE id_orden = :id'
            );
            $stmt->execute([
                ':fecha' => $datos['fecha'] ?? date('Y-m-d'), ':proveedor' => $proveedorId,
                ':obs' => trim((string)($datos['observaciones'] ?? '')), ':id' => $id,
            ]);
            $this->db->prepare('DELETE FROM inv_ordenes_compra_detalles WHERE orden_id = :id')->execute([':id' => $id]);
            $insert = $this->db->prepare(
                'INSERT INTO inv_ordenes_compra_detalles (orden_id, item_id, cantidad, precio_unitario_estimado) VALUES (:orden, :item, :cantidad, :precio)'
            );
            foreach ($detalles as $detalle) {
                $insert->execute([':orden' => $id, ':item' => $detalle['item_id'], ':cantidad' => $detalle['cantidad'], ':precio' => $detalle['precio']]);
            }
            (new InvBitacora())->registrar('ACTUALIZAR', 'bod', "Orden de compra {$orden['secuencial']} editada por {$usuario}.");
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function aprobarOrden(int $id, string $usuario): void
    {
        $stmt = $this->db->prepare(
            "UPDATE inv_ordenes_compra SET estado = 'APROBADA', fecha_aprobacion = CURRENT_TIMESTAMP, aprobado_por = :usuario
             WHERE id_orden = :id AND estado = 'PENDIENTE'"
        );
        $stmt->execute([':id' => $id, ':usuario' => $usuario]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('La orden no existe o ya fue procesada.');
        (new InvBitacora())->registrar('APROBAR', 'bod', "Orden de compra ID {$id} aprobada.");
    }

    public function crearFactura(array $datos, array $detalles): int
    {
        $ordenId = (int)($datos['orden_compra_id'] ?? 0);
        $detalles = $this->normalizarDetalles($detalles, true);
        if (!$detalles) throw new InvalidArgumentException('La factura debe contener sus líneas.');
        if (trim((string)($datos['numero_factura'] ?? '')) === '') throw new InvalidArgumentException('Indique el número de factura.');

        try {
            $this->db->beginTransaction();
            if ($ordenId > 0) {
                $orden = $this->buscarUno('inv_ordenes_compra', 'id_orden', $ordenId);
                if (!$orden || $orden['estado'] !== 'APROBADA') throw new RuntimeException('Solo se puede facturar una orden aprobada.');
                $esperados = $this->detallesOrden($ordenId);
                $this->validarCoincidencia($esperados, $detalles);
            } else {
                $proveedorId = (int)($datos['proveedor_id'] ?? 0);
                if ($proveedorId <= 0) throw new InvalidArgumentException('Seleccione el proveedor de la factura directa.');
                $secuencialOrden = (new InvSecuencial())->generarSiguiente('ocp');
                $insertOrden = $this->db->prepare(
                    "INSERT INTO inv_ordenes_compra
                        (secuencial, fecha, nota_pedido_id, proveedor_id, origen, estado, observaciones, creado_por, fecha_aprobacion, aprobado_por)
                     VALUES (:sec, :fecha, NULL, :proveedor, 'FACTURA', 'APROBADA', :obs, :usuario, CURRENT_TIMESTAMP, :usuario)"
                );
                $insertOrden->execute([
                    ':sec' => $secuencialOrden, ':fecha' => $datos['fecha_factura'] ?? date('Y-m-d'),
                    ':proveedor' => $proveedorId, ':obs' => 'Orden generada automáticamente desde factura directa.',
                    ':usuario' => $datos['creado_por'] ?? 'Sistema',
                ]);
                $ordenId = (int)$this->db->lastInsertId();
                $insertOrdenDetalle = $this->db->prepare('INSERT INTO inv_ordenes_compra_detalles (orden_id, item_id, cantidad, precio_unitario_estimado) VALUES (:orden, :item, :cantidad, :precio)');
                foreach ($detalles as $detalle) {
                    $insertOrdenDetalle->execute([':orden' => $ordenId, ':item' => $detalle['item_id'], ':cantidad' => $detalle['cantidad'], ':precio' => $detalle['precio']]);
                }
                $orden = ['id_orden' => $ordenId, 'secuencial' => $secuencialOrden, 'proveedor_id' => $proveedorId, 'estado' => 'APROBADA'];
            }

            $subtotal0 = 0.0; $subtotalGravado = 0.0;
            foreach ($detalles as $d) {
                $base = $d['cantidad'] * $d['precio'];
                if (!empty($d['grava_iva'])) $subtotalGravado += $base; else $subtotal0 += $base;
            }
            $iva = max(0, (float)($datos['iva_porcentaje'] ?? 0));
            $valorIva = round($subtotalGravado * $iva / 100, 2);
            $total = round($subtotal0 + $subtotalGravado + $valorIva, 2);

            $stmt = $this->db->prepare(
                "INSERT INTO inv_facturas
                    (numero_factura, fecha_factura, proveedor_id, orden_compra_id, iva_porcentaje, base_cero, subtotal_gravado, valor_iva, total, estado, creado_por,
                     archivo_nombre_original, archivo_ruta, archivo_mime, ocr_texto, fecha_escaneo)
                 VALUES (:numero, :fecha, :proveedor, :orden, :iva, :base0, :gravado, :valor_iva, :total, 'REGISTRADA', :usuario,
                         :archivo_nombre, :archivo_ruta, :archivo_mime, :ocr_texto, :fecha_escaneo)"
            );
            $stmt->execute([
                ':numero' => trim((string)$datos['numero_factura']), ':fecha' => $datos['fecha_factura'] ?? date('Y-m-d'),
                ':proveedor' => (int)$orden['proveedor_id'], ':orden' => $ordenId, ':iva' => $iva,
                ':base0' => $subtotal0, ':gravado' => $subtotalGravado, ':valor_iva' => $valorIva,
                ':total' => $total, ':usuario' => $datos['creado_por'] ?? 'Sistema',
                ':archivo_nombre' => $datos['archivo_nombre_original'] ?? null,
                ':archivo_ruta' => $datos['archivo_ruta'] ?? null,
                ':archivo_mime' => $datos['archivo_mime'] ?? null,
                ':ocr_texto' => $datos['ocr_texto'] ?? null,
                ':fecha_escaneo' => !empty($datos['ocr_texto']) ? date('Y-m-d H:i:s') : null,
            ]);
            $id = (int)$this->db->lastInsertId();
            $insert = $this->db->prepare(
                'INSERT INTO inv_facturas_detalles (factura_id, item_id, cantidad, precio_unitario, grava_iva, codigo_presupuestario) VALUES (:factura, :item, :cantidad, :precio, :grava, :codigo)'
            );
            foreach ($detalles as $d) {
                $insert->execute([
                    ':factura' => $id, ':item' => $d['item_id'], ':cantidad' => $d['cantidad'], ':precio' => $d['precio'],
                    ':grava' => !empty($d['grava_iva']) ? 1 : 0, ':codigo' => trim((string)($d['codigo_presupuestario'] ?? '')),
                ]);
            }
            (new InvBitacora())->registrar('CREAR', 'bod', 'Factura ' . trim((string)$datos['numero_factura']) . " vinculada a {$orden['secuencial']}.");
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function actualizarFactura(int $id, array $datos, array $detalles, string $usuario): ?string
    {
        $detalles = $this->normalizarDetalles($detalles, true);
        if ($id <= 0) throw new InvalidArgumentException('La factura indicada no es válida.');
        if (!$detalles) throw new InvalidArgumentException('La factura debe contener sus líneas.');
        $numero = trim((string)($datos['numero_factura'] ?? ''));
        if ($numero === '') throw new InvalidArgumentException('Indique el número de factura.');

        try {
            $this->db->beginTransaction();
            $factura = $this->buscarFactura($id);
            if (!$factura || $factura['estado'] !== 'REGISTRADA') {
                throw new RuntimeException('Solo se pueden editar facturas que todavía no hayan ingresado a bodega.');
            }
            $esFacturaDirecta = ($factura['orden_origen'] ?? '') === 'FACTURA';
            $proveedorId = (int)$factura['proveedor_id'];
            if ($esFacturaDirecta) {
                $proveedorId = (int)($datos['proveedor_id'] ?? 0);
                if ($proveedorId <= 0) throw new InvalidArgumentException('Seleccione el proveedor de la factura directa.');
                $this->db->prepare('UPDATE inv_ordenes_compra SET proveedor_id = :proveedor, fecha = :fecha WHERE id_orden = :id')->execute([
                    ':proveedor' => $proveedorId, ':fecha' => $datos['fecha_factura'] ?? date('Y-m-d'), ':id' => $factura['orden_compra_id'],
                ]);
                $this->db->prepare('DELETE FROM inv_ordenes_compra_detalles WHERE orden_id = :id')->execute([':id' => $factura['orden_compra_id']]);
                $insertOrden = $this->db->prepare('INSERT INTO inv_ordenes_compra_detalles (orden_id, item_id, cantidad, precio_unitario_estimado) VALUES (:orden, :item, :cantidad, :precio)');
                foreach ($detalles as $detalle) {
                    $insertOrden->execute([':orden' => $factura['orden_compra_id'], ':item' => $detalle['item_id'], ':cantidad' => $detalle['cantidad'], ':precio' => $detalle['precio']]);
                }
            } else {
                $esperados = $this->detallesOrden((int)$factura['orden_compra_id']);
                $this->validarCoincidencia($esperados, $detalles);
            }

            $subtotal0 = 0.0; $subtotalGravado = 0.0;
            foreach ($detalles as $detalle) {
                $base = $detalle['cantidad'] * $detalle['precio'];
                if (!empty($detalle['grava_iva'])) $subtotalGravado += $base; else $subtotal0 += $base;
            }
            $iva = max(0, (float)($datos['iva_porcentaje'] ?? 0));
            $valorIva = round($subtotalGravado * $iva / 100, 2);
            $total = round($subtotal0 + $subtotalGravado + $valorIva, 2);
            $reemplazaArchivo = !empty($datos['archivo_ruta']);

            $sqlArchivo = $reemplazaArchivo
                ? ', archivo_nombre_original = :archivo_nombre, archivo_ruta = :archivo_ruta, archivo_mime = :archivo_mime, ocr_texto = :ocr_texto, fecha_escaneo = :fecha_escaneo'
                : '';
            $stmt = $this->db->prepare(
                'UPDATE inv_facturas SET numero_factura = :numero, fecha_factura = :fecha, proveedor_id = :proveedor, iva_porcentaje = :iva, base_cero = :base0, subtotal_gravado = :gravado, valor_iva = :valor_iva, total = :total' . $sqlArchivo . ' WHERE id_factura = :id'
            );
            $params = [
                ':numero' => $numero, ':fecha' => $datos['fecha_factura'] ?? date('Y-m-d'), ':proveedor' => $proveedorId, ':iva' => $iva,
                ':base0' => $subtotal0, ':gravado' => $subtotalGravado, ':valor_iva' => $valorIva,
                ':total' => $total, ':id' => $id,
            ];
            if ($reemplazaArchivo) {
                $params += [
                    ':archivo_nombre' => $datos['archivo_nombre_original'] ?? null,
                    ':archivo_ruta' => $datos['archivo_ruta'], ':archivo_mime' => $datos['archivo_mime'] ?? null,
                    ':ocr_texto' => $datos['ocr_texto'] ?? null,
                    ':fecha_escaneo' => !empty($datos['ocr_texto']) ? date('Y-m-d H:i:s') : null,
                ];
            }
            $stmt->execute($params);
            $this->db->prepare('DELETE FROM inv_facturas_detalles WHERE factura_id = :id')->execute([':id' => $id]);
            $insert = $this->db->prepare(
                'INSERT INTO inv_facturas_detalles (factura_id, item_id, cantidad, precio_unitario, grava_iva, codigo_presupuestario) VALUES (:factura, :item, :cantidad, :precio, :grava, :codigo)'
            );
            foreach ($detalles as $detalle) {
                $insert->execute([
                    ':factura' => $id, ':item' => $detalle['item_id'], ':cantidad' => $detalle['cantidad'], ':precio' => $detalle['precio'],
                    ':grava' => !empty($detalle['grava_iva']) ? 1 : 0, ':codigo' => trim((string)($detalle['codigo_presupuestario'] ?? '')),
                ]);
            }
            (new InvBitacora())->registrar('ACTUALIZAR', 'bod', "Factura {$numero} editada por {$usuario} antes del ingreso a bodega.");
            $this->db->commit();
            return $reemplazaArchivo ? (string)($factura['archivo_ruta'] ?? '') : null;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function crearIngresoDesdeFactura(int $facturaId, int $responsableId, string $usuario, string $observaciones = '', ?string $fechaIngreso = null): int
    {
        if ($responsableId <= 0) throw new InvalidArgumentException('Seleccione el bodeguero responsable.');
        try {
            $this->db->beginTransaction();
            $factura = $this->buscarFactura($facturaId);
            if (!$factura || $factura['estado'] !== 'REGISTRADA') throw new RuntimeException('La factura no existe o ya fue ingresada.');
            $detalles = $this->detallesFactura($facturaId);
            if (!$detalles) throw new RuntimeException('La factura no tiene detalle.');
            $secuencial = (new InvSecuencial())->generarSiguiente('ing');

            $stmt = $this->db->prepare(
                'INSERT INTO inv_bod_ingresos (secuencial, proveedor, fecha, observaciones, responsable_id, creado_por, factura_id, orden_compra_id)
                 VALUES (:sec, :proveedor, :fecha, :obs, :responsable, :usuario, :factura, :orden)'
            );
            $stmt->execute([
                ':sec' => $secuencial, ':proveedor' => $factura['proveedor'], ':fecha' => $fechaIngreso ?: date('Y-m-d'),
                ':obs' => $observaciones, ':responsable' => $responsableId, ':usuario' => $usuario,
                ':factura' => $facturaId, ':orden' => (int)$factura['orden_compra_id'],
            ]);
            $ingresoId = (int)$this->db->lastInsertId();

            $insertDetalle = $this->db->prepare(
                'INSERT INTO inv_bod_ingresos_detalles
                    (ingreso_id, item_id, cantidad, valor_unitario, existencia_anterior, existencia_nueva, costo_promedio_actualizado)
                 VALUES (:ingreso, :item, :cantidad, :precio, :anterior, :nueva, :promedio)'
            );
            $updateStock = $this->db->prepare('UPDATE inv_inventario SET cantidad = :cantidad, valor = :promedio WHERE id = :item');
            $insertKardex = $this->db->prepare(
                "INSERT INTO inv_kardex
                    (item_id, tipo_movimiento, documento_tipo, documento_id, documento_secuencial, entrada, salida,
                     saldo_anterior, saldo_resultante, responsable_id, usuario_registro, observaciones)
                 VALUES (:item, 'INGRESO', 'INGRESO', :documento, :secuencial, :entrada, 0, :anterior, :saldo, :responsable, :usuario, :obs)"
            );

            foreach ($detalles as $d) {
                $item = $this->buscarInventarioBloqueado((int)$d['item_id']);
                if (!$item) throw new RuntimeException('Uno de los ítems de la factura ya no existe en inventario.');
                $anterior = (int)$item['cantidad'];
                $cantidad = (int)$d['cantidad'];
                $nueva = $anterior + $cantidad;
                $costoAnterior = (float)$item['valor'];
                $promedio = $nueva > 0 ? round((($anterior * $costoAnterior) + ($cantidad * (float)$d['precio_unitario'])) / $nueva, 4) : (float)$d['precio_unitario'];
                $insertDetalle->execute([
                    ':ingreso' => $ingresoId, ':item' => $d['item_id'], ':cantidad' => $cantidad,
                    ':precio' => $d['precio_unitario'], ':anterior' => $anterior, ':nueva' => $nueva, ':promedio' => $promedio,
                ]);
                $updateStock->execute([':cantidad' => $nueva, ':promedio' => $promedio, ':item' => $d['item_id']]);
                $insertKardex->execute([
                    ':item' => $d['item_id'], ':documento' => $ingresoId, ':secuencial' => $secuencial,
                    ':entrada' => $cantidad, ':anterior' => $anterior, ':saldo' => $nueva,
                    ':responsable' => $responsableId, ':usuario' => $usuario,
                    ':obs' => 'Ingreso por factura ' . $factura['numero_factura'],
                ]);
            }
            $upFactura = $this->db->prepare("UPDATE inv_facturas SET estado = 'INGRESADA' WHERE id_factura = :id");
            $upFactura->execute([':id' => $facturaId]);
            $upOrden = $this->db->prepare("UPDATE inv_ordenes_compra SET estado = 'CERRADA' WHERE id_orden = :id");
            $upOrden->execute([':id' => $factura['orden_compra_id']]);
            if (!empty($factura['nota_pedido_id'])) {
                $upNota = $this->db->prepare("UPDATE inv_abast_notas_pedido SET estado = 'ATENDIDA' WHERE id_nota = :id");
                $upNota->execute([':id' => $factura['nota_pedido_id']]);
            }
            (new InvBitacora())->registrar('CREAR', 'bod', "Ingreso {$secuencial} generado desde factura {$factura['numero_factura']}.");
            $this->db->commit();
            return $ingresoId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    private function detallesNota(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT d.*, i.nombre item_nombre, i.secuencial item_secuencial FROM inv_abast_notas_pedido_detalles d JOIN inv_inventario i ON i.id = d.item_id WHERE d.nota_id = :id ORDER BY d.id_detalle'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }

    private function detallesOrden(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT d.*, i.nombre item_nombre, i.secuencial item_secuencial FROM inv_ordenes_compra_detalles d JOIN inv_inventario i ON i.id = d.item_id WHERE d.orden_id = :id ORDER BY d.id_detalle'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }

    private function detallesFactura(int $id): array
    {
        $stmt = $this->db->prepare(
            'SELECT d.*, i.nombre item_nombre, i.secuencial item_secuencial,
                    i.cantidad existencia_actual, i.valor costo_actual,
                    c.nombre grupo_nombre, c.codigo grupo_codigo
             FROM inv_facturas_detalles d
             JOIN inv_inventario i ON i.id = d.item_id
             LEFT JOIN inv_categorias c ON c.id = i.categoria_id
             WHERE d.factura_id = :id ORDER BY d.id_detalle'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }

    private function buscarFactura(int $id)
    {
        $stmt = $this->db->prepare(
            'SELECT f.*, p.nombre proveedor, o.secuencial orden_secuencial, o.nota_pedido_id, o.origen orden_origen FROM inv_facturas f JOIN inv_proveedores p ON p.id = f.proveedor_id JOIN inv_ordenes_compra o ON o.id_orden = f.orden_compra_id WHERE f.id_factura = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function obtenerDocumentoFactura(int $id)
    {
        $stmt = $this->db->prepare('SELECT id_factura, numero_factura, archivo_nombre_original, archivo_ruta, archivo_mime FROM inv_facturas WHERE id_factura = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    private function buscarUno(string $tabla, string $campo, int $id)
    {
        $permitidos = ['inv_abast_notas_pedido.id_nota', 'inv_ordenes_compra.id_orden'];
        if (!in_array($tabla . '.' . $campo, $permitidos, true)) throw new InvalidArgumentException('Consulta no permitida.');
        $stmt = $this->db->prepare("SELECT * FROM {$tabla} WHERE {$campo} = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    private function buscarInventarioBloqueado(int $id)
    {
        $driver = defined('DB_DRIVER') ? DB_DRIVER : 'sqlite';
        if ($driver === 'sqlsrv') $sql = 'SELECT id, cantidad, valor FROM inv_inventario WITH (UPDLOCK, HOLDLOCK) WHERE id = :id';
        elseif ($driver === 'pgsql') $sql = 'SELECT id, cantidad, valor FROM inv_inventario WHERE id = :id FOR UPDATE';
        else $sql = 'SELECT id, cantidad, valor FROM inv_inventario WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    private function normalizarDetalles(array $detalles, bool $conPrecio): array
    {
        $salida = []; $vistos = [];
        foreach ($detalles as $detalle) {
            $item = (int)($detalle['item_id'] ?? 0);
            $cantidad = (int)($detalle['cantidad'] ?? 0);
            if ($item <= 0 || $cantidad <= 0) continue;
            if (isset($vistos[$item])) throw new InvalidArgumentException('No repita ítems en el mismo documento.');
            $vistos[$item] = true;
            $fila = ['item_id' => $item, 'cantidad' => $cantidad];
            if ($conPrecio) {
                $precio = (float)($detalle['precio'] ?? $detalle['precio_unitario'] ?? 0);
                if ($precio < 0) throw new InvalidArgumentException('El precio unitario no puede ser negativo.');
                $fila['precio'] = $precio;
                $fila['grava_iva'] = !empty($detalle['grava_iva']);
                $fila['codigo_presupuestario'] = $detalle['codigo_presupuestario'] ?? '';
            }
            $salida[] = $fila;
        }
        return $salida;
    }

    private function validarCoincidencia(array $orden, array $factura): void
    {
        $esperados = [];
        foreach ($orden as $d) $esperados[(int)$d['item_id']] = (int)$d['cantidad'];
        $recibidos = [];
        foreach ($factura as $d) $recibidos[(int)$d['item_id']] = (int)$d['cantidad'];
        ksort($esperados); ksort($recibidos);
        if ($esperados !== $recibidos) {
            throw new InvalidArgumentException('El detalle de la factura debe coincidir línea a línea con la orden aprobada.');
        }
    }
}
