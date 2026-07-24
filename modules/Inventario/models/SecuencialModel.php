<?php
/**
 * SecuencialModel — Contadores automáticos por módulo (inv_secuenciales).
 * Port nativo sqlsrv (sin PDO).
 */
class SecuencialModel extends InvBaseModel
{
    private const PREFIJOS = ['inv'=>'INV-','th'=>'TH-','bit'=>'BIT-','acc'=>'ACC-','ing'=>'ING-','egr'=>'EGR-','itm'=>'ITM-'];

    public function obtenerTodos(): array
    {
        return $this->fetchAll("SELECT * FROM inv_secuenciales ORDER BY modulo ASC");
    }

    public function generarSiguiente(string $modulo): string
    {
        $seq = $this->fetch("SELECT * FROM inv_secuenciales WHERE modulo = ?", [[$modulo, SQLSRV_PARAM_IN]]);

        if (!$seq) {
            $prefijo = self::PREFIJOS[$modulo] ?? 'GEN-';
            $this->query("INSERT INTO inv_secuenciales (modulo, prefijo, ultimo_numero) VALUES (?,?,0)", [
                [$modulo, SQLSRV_PARAM_IN], [$prefijo, SQLSRV_PARAM_IN],
            ]);
            $num = 1;
        } else {
            $prefijo = $seq['prefijo'];
            $num     = (int)$seq['ultimo_numero'] + 1;
        }

        $this->exec("UPDATE inv_secuenciales SET ultimo_numero = ? WHERE modulo = ?", [
            [$num, SQLSRV_PARAM_IN], [$modulo, SQLSRV_PARAM_IN],
        ]);

        $pad = match ($modulo) {
            'inv', 'ing', 'egr' => 5,
            'bit'               => 6,
            default             => 4,
        };
        return $prefijo . str_pad((string)$num, $pad, '0', STR_PAD_LEFT);
    }

    public function reiniciar(string $modulo): bool
    {
        return $this->exec("UPDATE inv_secuenciales SET ultimo_numero = 0 WHERE modulo = ?", [[$modulo, SQLSRV_PARAM_IN]]) > 0;
    }
}
