<?php

final class Catalogos
{
    public const TIPOS_PROCESO = ['Gobernante','Estratégico','Sustantivo','Adjetivo','Apoyo','Asesoría'];

    public static function tipoProcesoValido(string $valor): bool
    {
        return in_array($valor, self::TIPOS_PROCESO, true);
    }
}
