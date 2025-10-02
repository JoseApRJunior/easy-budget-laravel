<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    public static function format( $date, $format = 'd/m/Y' ): string
    {
        return Carbon::parse( $date )->format( $format );
    }

    public static function formatBR( $date ): string
    {
        return Carbon::parse( $date )->format( 'd/m/Y' );
    }

}
