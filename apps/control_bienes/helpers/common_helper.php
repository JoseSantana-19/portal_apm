<?php
// helpers/common_helper.php

class CommonHelper {
    private static $parametrosCache = [];

    /**
     * Genera el siguiente secuencial transaccional para evitar colisiones y saltos de 1000.
     * @param string $modulo Identificador del módulo (ej. 'inv', 'th', 'egr', 'ing')
     * @param string $prefijo Prefijo de la secuencia (ej. 'INV-', 'TH-', 'EGR-', 'ING-')
     * @return string Secuencial formateado
     */
    public static function generarSecuencia(string $modulo, string $prefijo): string {
        $db = Database::getInstance()->getConnection();
        
        try {
            $inTransaction = $db->inTransaction();
            if (!$inTransaction) {
                $db->beginTransaction();
            }

            // Seleccionar y bloquear la fila para evitar concurrencia en transacciones paralelas
            $stmt = $db->prepare("SELECT ultimo_numero, prefijo FROM inv_secuenciales WHERE modulo = :modulo");
            $stmt->execute([':modulo' => $modulo]);
            $seq = $stmt->fetch();

            if (!$seq) {
                // Insertar secuencia inicial si no existe
                $stmtInsert = $db->prepare("INSERT INTO inv_secuenciales (modulo, prefijo, ultimo_numero) VALUES (:modulo, :prefijo, 1)");
                $stmtInsert->execute([':modulo' => $modulo, ':prefijo' => $prefijo]);
                $ultimoNumero = 1;
                $prefijoFinal = $prefijo;
            } else {
                $ultimoNumero = (int)$seq['ultimo_numero'] + 1;
                $prefijoFinal = $seq['prefijo'];
                
                // Actualizar contador
                $stmtUpdate = $db->prepare("UPDATE inv_secuenciales SET ultimo_numero = :num WHERE modulo = :modulo");
                $stmtUpdate->execute([':num' => $ultimoNumero, ':modulo' => $modulo]);
            }

            if (!$inTransaction) {
                $db->commit();
            }

            // Formateo de la longitud del secuencial con ceros a la izquierda
            $padLen = 5;
            if ($modulo === 'bit') $padLen = 6;
            elseif ($modulo === 'th' || $modulo === 'acc') $padLen = 4;
            
            return $prefijoFinal . str_pad($ultimoNumero, $padLen, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            if (!$inTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtiene el valor de un parámetro del sistema de la tabla inv_parametros.
     * @param string $clave Clave del parámetro
     * @param string $default Valor de retorno si no se encuentra
     * @return string Valor del parámetro
     */
    public static function obtenerParametro(string $clave, string $default = ''): string {
        if (array_key_exists($clave, self::$parametrosCache)) {
            return self::$parametrosCache[$clave];
        }
        $db = Database::getInstance()->getConnection();
        
        try {
            $stmt = $db->prepare("SELECT valor FROM inv_parametros WHERE clave = :clave");
            $stmt->execute([':clave' => $clave]);
            $res = $stmt->fetch();
            return self::$parametrosCache[$clave] = ($res ? (string)$res['valor'] : $default);
        } catch (Exception $e) {
            return self::$parametrosCache[$clave] = $default;
        }
    }

    public static function decimalesPrecio(): int {
        return max(0, min(12, (int)self::obtenerParametro('decimales_precio_unitario', '8')));
    }

    public static function decimalesImporte(): int {
        return max(0, min(8, (int)self::obtenerParametro('decimales_importe_monetario', '2')));
    }

    public static function pasoPrecio(): string {
        $decimales = self::decimalesPrecio();
        return $decimales === 0 ? '1' : '0.' . str_repeat('0', $decimales - 1) . '1';
    }

    public static function redondearPrecio($valor): float {
        return round((float)$valor, self::decimalesPrecio());
    }

    public static function redondearImporte($valor): float {
        return round((float)$valor, self::decimalesImporte());
    }

    public static function formatearPrecio($valor, bool $simbolo = true): string {
        return ($simbolo ? '$' : '') . number_format((float)$valor, self::decimalesPrecio(), '.', ',');
    }

    public static function formatearImporte($valor, bool $simbolo = true): string {
        return ($simbolo ? '$' : '') . number_format((float)$valor, self::decimalesImporte(), '.', ',');
    }

    public static function configuracionMonetaria(): array {
        return [
            'priceDecimals' => self::decimalesPrecio(),
            'amountDecimals' => self::decimalesImporte(),
            'priceStep' => self::pasoPrecio(),
        ];
    }
}
