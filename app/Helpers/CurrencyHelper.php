<?php

namespace App\Helpers;

class CurrencyHelper
{
    public static function format( $value, $decimals = 2 ): string
    {
        return 'R$ ' . number_format( $value, $decimals, ',', '.' );
    }

    public static function unformat( $value ): float
    {
        return (float) str_replace( [ 'R$', '.', ',' ], [ '', '', '.' ], $value );
    }

}
