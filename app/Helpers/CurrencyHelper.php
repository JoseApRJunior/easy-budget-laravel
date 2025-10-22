<?php

namespace App\Helpers;

class CurrencyHelper
{
    /**
     * Formata valor monetário em BRL.
     */
    public static function format( $value, int $decimals = 2 ): string
    {
        return 'R$ ' . number_format( (float) $value, $decimals, ',', '.' );
    }

    /**
     * Remove formatação de moeda e retorna float.
     */
    public static function unformat( $value ): float
    {
        // Remove tudo que não for número, vírgula ou ponto
        $clean = preg_replace( '/[^0-9,.-]/', '', $value );

        // Troca vírgula por ponto para decimal
        $clean = str_replace( ',', '.', $clean );

        return (float) $clean;
    }

}
