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

    public static function monthYearPt( $date ): string
    {
        return Carbon::parse( $date )->translatedFormat( 'M/Y' );
    }

    public static function dayMonthYearPt( $date ): string
    {
        return Carbon::parse( $date )->format( 'd/m/Y' );
    }

    public static function timeDiff( $datetime ): string
    {
        return Carbon::parse( $datetime )->diffForHumans();
    }

    public static function formatDateOrDefault( $date, $format = 'd/m/Y', $default = 'Não informado' ): string
    {
        if ( empty( $date ) ) {
            return $default;
        }

        try {
            return Carbon::parse( $date )->format( $format );
        } catch ( \Exception $e ) {
            return $default;
        }
    }

}
